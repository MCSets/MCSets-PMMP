<?php

declare(strict_types=1);

namespace MCSets\api;

use MCSets\Loader;
use MCSets\thread\MCSetsThread;
use MCSets\thread\ThreadCallableCache;
use MCSets\utils\ConfigManager;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class MCSetsAPI
{
    private const VERIFICATION_POLL_INTERVAL = 40;
    private const VERIFICATION_MAX_ATTEMPTS = 120;

    private MCSetsThread $thread;
    private Loader $plugin;
    private ConfigManager $config;
    private bool $connected = false;
    private string $serverName = "Unknown";
    private int $serverId = 0;
    private int $reconnectAttempts = 0;
    private array $activeVerifications = [];
    private array $processingDeliveryIds = [];

    public function __construct(Loader $plugin, string $apiKey)
    {
        $this->plugin = $plugin;
        $this->config = $plugin->getConfigManager();
        $this->thread = new MCSetsThread(
            $apiKey,
            $this->config->getBaseUrl(),
            $this->config->getApiTimeout(),
            $this->config->getPollingInterval()
        );
        $this->setupQueuePollCallback();
    }

    public function startThread(): void
    {
        $this->thread->start();
    }

    private function setupQueuePollCallback(): void
    {
        ThreadCallableCache::$queuePollCallback = function (array $response): void {
            if (!$response["success"] || !isset($response["data"]["deliveries"])) {
                return;
            }

            $deliveries = $response["data"]["deliveries"];
            if (count($deliveries) === 0) {
                return;
            }

            foreach ($deliveries as $delivery) {
                $id = $delivery["id"];
                if(in_array($id, $this->processingDeliveryIds)) {
                    continue;
                }
                $this->processingDeliveryIds[] = $id;

                if ($this->config->isDebugEnabled()) {
                    $this->plugin->getLogger()->info(TextFormat::YELLOW . "New purchase: {$delivery["package_name"]} for {$delivery["player_username"]}");
                }
                $this->processDelivery($delivery);
            }
        };
    }

    public function connect(): void
    {
        $server = $this->plugin->getServer();
        $config = $this->plugin->getConfigManager();
        $players = [];
        foreach ($server->getOnlinePlayers() as $player) {
            $players[] = $player->getName();
        }
        $serverIp = $config->getServerIp() !== "" ? $config->getServerIp() : $server->getIp();
        $serverPort = $config->getServerPort() !== 0 ? $config->getServerPort() : $server->getPort();
        $data = [
            "api_key" => $config->getApiKey(),
            "server_ip" => $serverIp,
            "server_port" => $serverPort,
            "server_version" => $server->getVersion(),
            "online_players" => $players
        ];
        $this->thread->submitRequest("/connect", "POST", $data, function (array $response): void {
            if ($response["success"] && isset($response["data"]["success"]) && $response["data"]["success"]) {
                $this->connected = true;
                $this->reconnectAttempts = 0;
                $this->serverName = $response["data"]["server"]["name"] ?? "Unknown";
                $this->serverId = $response["data"]["server"]["id"] ?? 0;
                $pendingCount = $response["data"]["pending_deliveries"] ?? 0;
                $message = TextFormat::GREEN . "Successfully connected to " . TextFormat::YELLOW . $this->serverName . TextFormat::GREEN . "!";
                if ($this->config->isDebugEnabled()) {
                    TextFormat::GREEN . "Successfully connected to " . TextFormat::YELLOW . $this->serverName . TextFormat::GREEN . "! Server ID: "  . TextFormat::YELLOW . $this->serverId;
                }
                $this->plugin->getLogger()->info($message);
                if ($pendingCount > 0 && $this->config->isDebugEnabled()) {
                    $this->plugin->getLogger()->info(TextFormat::YELLOW . "Found {$pendingCount} pending deliveries");
                }
            } else {
                $error = $response["error"] ?? "Unknown error";
                $httpCode = $response["http_code"] ?? 0;
                if ($this->config->isDebugEnabled() || $this->reconnectAttempts === 0) {
                    $this->plugin->getLogger()->warning("Failed to connect to MCSets (HTTP {$httpCode}): " . $error);
                }
                $this->scheduleReconnect();
            }
        });
    }

    public function reportDelivery(int $deliveryId, string $status, array $actionsExecuted, ?string $errorMessage = null, int $durationMs = 0): void
    {
        $data = [
            "delivery_id" => $deliveryId,
            "status" => $status,
            "actions_executed" => $actionsExecuted,
            "duration_ms" => $durationMs
        ];
        if ($errorMessage !== null) {
            $data["error_message"] = $errorMessage;
        }
        $this->thread->submitRequest("/deliver", "POST", $data, function (array $response): void {
            if (!$response["success"] && $this->config->isDebugEnabled()) {
                $this->plugin->getLogger()->warning("Failed to report delivery: " . ($response["error"] ?? "Unknown error"));
            }
        });
    }

    public function reportOnlinePlayers(array $players): void
    {
        if (!$this->connected) return;
        $data = ["players" => $players];
        $this->thread->submitRequest("/online", "POST", $data, null);
    }

    public function sendHeartbeat(): void
    {
        if (!$this->connected) return;
        $this->thread->submitRequest("/heartbeat", "POST", [], null);
    }

    public function generateVerifyCode(string $username, string $uuid, callable $callback): void
    {
        $data = [
            "username" => $username,
            "uuid" => $uuid
        ];
        $this->thread->submitRequest("/verify", "POST", $data, function (array $response) use ($callback, $username): void {
            if ($response["success"] && isset($response["data"]["code"])) {
                $code = $response["data"]["code"];
                $this->activeVerifications[$username] = [
                    "code" => $code,
                    "attempts" => 0
                ];
                $this->startVerificationPolling($username);
            }
            $callback($response);
        });
    }

    private function startVerificationPolling(string $username): void
    {
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($username): void {
            $this->pollVerificationStatus($username);
        }), self::VERIFICATION_POLL_INTERVAL);
    }

    private function pollVerificationStatus(string $username): void
    {
        if (!isset($this->activeVerifications[$username])) {
            return;
        }
        $verificationData = $this->activeVerifications[$username];
        $code = $verificationData["code"];
        $attempts = $verificationData["attempts"];
        if ($attempts >= self::VERIFICATION_MAX_ATTEMPTS) {
            unset($this->activeVerifications[$username]);
            $player = Server::getInstance()->getPlayerExact($username);
            if ($player !== null && $player->isConnected()) {
                $player->sendMessage(TextFormat::RED . "Verification polling timed out. Please use /verify again if needed.");
            }
            return;
        }
        $this->thread->submitRequest("/verify?code=" . $code, "GET", [], function (array $response) use ($username, $attempts): void {
            if (!isset($this->activeVerifications[$username])) {
                return;
            }
            $player = Server::getInstance()->getPlayerExact($username);
            if ($player === null || !$player->isConnected()) {
                unset($this->activeVerifications[$username]);
                return;
            }
            if ($response["success"] && isset($response["data"])) {
                $data = $response["data"];
                if (isset($data["verified"]) && $data["verified"] === true) {
                    $player->sendMessage(TextFormat::GREEN . "------------------------------");
                    $player->sendMessage(TextFormat::GREEN . TextFormat::BOLD . "Successfully Verified!");
                    $player->sendMessage(TextFormat::GRAY . "Your account has been linked.");
                    $player->sendMessage(TextFormat::GREEN . "------------------------------");
                    unset($this->activeVerifications[$username]);
                    return;
                }
                if (isset($data["expired"]) && $data["expired"] === true) {
                    $player->sendMessage(TextFormat::RED . "Your verification code has expired. Please use /verify again.");
                    unset($this->activeVerifications[$username]);
                    return;
                }
            }
            $this->activeVerifications[$username]["attempts"] = $attempts + 1;
            $this->startVerificationPolling($username);
        });
    }

    public function onPlayerJoin(Player $player): void {}

    public function onPlayerQuit(Player $player): void
    {
        $username = $player->getName();
        if (isset($this->activeVerifications[$username])) {
            unset($this->activeVerifications[$username]);
        }
    }

    public function processDelivery(array $delivery): void
    {
        $username = $delivery["player_username"];
        $requireOnline = $delivery["require_online"] ?? true;
        $player = Server::getInstance()->getPlayerExact($username);

        if ($requireOnline && $player === null) {
            return;
        }

        $config = $this->plugin->getConfigManager();
        $commandDelay = $config->getCommandDelay();

        if ($commandDelay > 0) {
            $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($delivery, $username, $requireOnline): void {
                $this->executeDelivery($delivery, $username, $requireOnline);
            }), $commandDelay * 20);
        } else {
            $this->executeDelivery($delivery, $username, $requireOnline);
        }
    }

    private function executeDelivery(array $delivery, string $username, bool $requireOnline): void
    {
        $player = Server::getInstance()->getPlayerExact($username);

        if ($requireOnline && $player === null) {
            return;
        }

        $startTime = microtime(true);
        $actionsExecuted = [];
        $failedActions = [];

        foreach ($delivery["actions"] as $action) {
            if ($action["type"] === "command") {
                $command = $action["parsed_value"] ?? $action["value"];
                try {
                    $consoleSender = new ConsoleCommandSender($this->plugin->getServer(), $this->plugin->getServer()->getLanguage());
                    Server::getInstance()->dispatchCommand($consoleSender, $command);
                    $actionsExecuted[] = $command;
                } catch (\Exception $e) {
                    $failedActions[] = $command . " (Error: " . $e->getMessage() . ")";
                }
            }
        }

        $duration = (int)((microtime(true) - $startTime) * 1000);
        $status = "success";
        $errorMessage = null;

        if (count($failedActions) > 0) {
            $status = count($actionsExecuted) > 0 ? "partial" : "failed";
            $errorMessage = "Failed commands: " . implode(", ", $failedActions);
        }

        $id = $delivery["id"];

        $this->reportDelivery($id, $status, $actionsExecuted, $errorMessage, $duration);
        if (($key = array_search($id, $this->processingDeliveryIds)) !== false) {
            unset($this->processingDeliveryIds[$key]);
        }

        if ($this->config->isDebugEnabled()) {
            $this->plugin->getLogger()->info(TextFormat::GREEN . "Delivered '{$delivery["package_name"]}' to {$username}");
        }
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getServerName(): string
    {
        return $this->serverName;
    }

    public function getServerId(): int
    {
        return $this->serverId;
    }

    public function getThread(): MCSetsThread
    {
        return $this->thread;
    }

    public function getActiveVerificationsCount(): int
    {
        return count($this->activeVerifications);
    }

    private function scheduleReconnect(): void
    {
        if ($this->reconnectAttempts === 0) {
            $this->plugin->getLogger()->notice("Attempting to reconnect...");
        }
        $this->reconnectAttempts++;
        $maxAttempts = $this->plugin->getConfigManager()->getApiMaxReconnectAttempts();
        if ($this->reconnectAttempts > $maxAttempts) {
            //if ($this->config->isDebugEnabled()) {
            $this->plugin->getLogger()->error(TextFormat::RED . "Failed to connect to MCSets after {$maxAttempts} attempts. Please check your API key");
            //}
            return;
        }
        $delay = $this->plugin->getConfigManager()->getApiReconnectDelay();
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($maxAttempts): void {
            if ($this->config->isDebugEnabled()) {
                $this->plugin->getLogger()->info("Retrying connection to MCSets (Attempt {$this->reconnectAttempts}/{$maxAttempts})...");
            }
            $this->connect();
        }), $delay * 20);
    }

    public function shutdown(): void
    {
        $this->activeVerifications = [];
        if ($this->thread->isRunning()) {
            $this->thread->quit();
        }
    }
}
