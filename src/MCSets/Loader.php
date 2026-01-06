<?php

declare(strict_types=1);

namespace MCSets;

use CortexPE\Commando\PacketHooker;
use MCSets\api\MCSetsAPI;
use MCSets\command\SetStoreCommand;
use MCSets\command\VerifyCommand;
use MCSets\thread\MCSetsThread;
use MCSets\thread\task\HeartbeatTask;
use MCSets\thread\task\OnlinePlayersTask;
use MCSets\thread\task\ReadResponsesTask;
use MCSets\utils\ConfigManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;

class Loader extends PluginBase
{
    use SingletonTrait;

    private ConfigManager $configManager;
    private ?MCSetsAPI $api = null;

    function onLoad(): void
    {
        $this->setInstance($this);
    }

    function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) PacketHooker::register($this);

        $this->configManager = new ConfigManager($this);

        $setStoreCommandName = $this->configManager->getSetStoreCommandName();
        $apiKey = $this->configManager->getApiKey();

        if ($apiKey === "") {
            $this->getLogger()->notice("Please configure your API key using /{$setStoreCommandName} apikey <api-key>");
        } else {
            $this->initializeAPI($apiKey);
        }

        $setStoreCommand = new SetStoreCommand($this, $setStoreCommandName, "Manage your MCSets Store");
        $verifyCommand = new VerifyCommand($this, $this->configManager->getVerifyCommandName(), "Verify your minecraft account with the store");

        $setStoreCommand->setPermission($setStoreCommand->getPermission());
        $verifyCommand->setPermission($verifyCommand->getPermission());

        $this->getServer()->getCommandMap()->register("setstore", $setStoreCommand);
        $this->getServer()->getCommandMap()->register("verify", $verifyCommand);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function onDisable(): void
    {
        if ($this->api !== null) {
            $this->api->shutdown();
        }
    }

    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    public function reloadConfig(): void
    {
        parent::reloadConfig();
        $this->configManager = new ConfigManager($this);
    }

    public function getAPI(): ?MCSetsAPI
    {
        return $this->api;
    }

    public function initializeAPI(string $apiKey): void
    {
        if ($this->api !== null) {
            $this->api->shutdown();
        }

        $this->api = new MCSetsAPI($this, $apiKey);
        $this->api->startThread();

        $this->getScheduler()->scheduleRepeatingTask(new ReadResponsesTask($this->api->getThread()), 1);

        $heartbeatInterval = $this->configManager->getHeartbeatInterval();
        $this->getScheduler()->scheduleRepeatingTask(new HeartbeatTask(), $heartbeatInterval * 20);

        $this->getScheduler()->scheduleRepeatingTask(new OnlinePlayersTask(), 100);

        $this->getLogger()->notice(TextFormat::YELLOW . "Connecting to MCSets...");
        $this->api->connect();
    }

    public function createNewThread(): MCSetsThread
    {
        $thread = $this->api?->getThread();

        if ($thread !== null && $thread->isRunning()) {
            return $thread;
        }

        $newThread = new MCSetsThread(
            $this->configManager->getApiKey(),
            $this->configManager->getBaseUrl(),
            $this->configManager->getApiTimeout(),
            $this->configManager->getPollingInterval()
        );
        $newThread->start();

        return $newThread;
    }
}
