<?php

declare(strict_types=1);

namespace MCSets\utils;

use MCSets\Loader;
use pocketmine\utils\Config;

final class ConfigManager
{
    private const CURRENT_VERSION = 1;

    private Config $config;
    private Loader $plugin;

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
        $this->loadAndMigrateConfig();
    }

    private function loadAndMigrateConfig(): void
    {
        $configPath = $this->plugin->getDataFolder() . "config.yml";
        $configExists = file_exists($configPath);

        if (!$configExists) {
            $this->plugin->saveDefaultConfig();
            $this->config = $this->plugin->getConfig();
            return;
        }

        $this->config = new Config($configPath, Config::YAML);
        $currentVersion = (int) $this->config->get("config-version", 0);

        if ($currentVersion < self::CURRENT_VERSION) {
            $this->plugin->getLogger()->warning("Config file is outdated (v{$currentVersion}). Migrating to v" . self::CURRENT_VERSION . "...");
            $this->migrateConfig($currentVersion);
            $this->config->set("config-version", self::CURRENT_VERSION);
            $this->config->save();
            $this->plugin->getLogger()->info("Config migration completed successfully!");
        }
    }

    private function migrateConfig(int $fromVersion): void
    {
        $oldData = [];
        foreach ($this->config->getAll() as $key => $value) {
            $oldData[$key] = $value;
        }

        $this->plugin->saveResource("config.yml", true);
        $this->config->reload();

        if ($fromVersion === 0) {
            $this->migrateFromV0($oldData);
        }
    }

    private function migrateFromV0(array $oldData): void
    {
        if (isset($oldData["api-key"])) {
            $this->config->set("api-key", $oldData["api-key"]);
        }

        if (isset($oldData["api"]["base-url"])) {
            $this->config->setNested("api.base-url", $oldData["api"]["base-url"]);
        }
        if (isset($oldData["api"]["timeout"])) {
            $this->config->setNested("api.timeout", $oldData["api"]["timeout"]);
        }
        if (isset($oldData["api"]["reconnect-delay"])) {
            $this->config->setNested("api.reconnect-delay", $oldData["api"]["reconnect-delay"]);
        } elseif (isset($oldData["websocket"]["reconnect-delay"])) {
            $this->config->setNested("api.reconnect-delay", $oldData["websocket"]["reconnect-delay"]);
        }
        if (isset($oldData["api"]["max-reconnect-attempts"])) {
            $this->config->setNested("api.max-reconnect-attempts", $oldData["api"]["max-reconnect-attempts"]);
        } elseif (isset($oldData["websocket"]["max-reconnect-attempts"])) {
            $this->config->setNested("api.max-reconnect-attempts", $oldData["websocket"]["max-reconnect-attempts"]);
        }

        if (isset($oldData["server"]["ip"])) {
            $this->config->setNested("server.ip", $oldData["server"]["ip"]);
        }
        if (isset($oldData["server"]["port"])) {
            $this->config->setNested("server.port", $oldData["server"]["port"]);
        }

        if (isset($oldData["polling"]["enabled"])) {
            $this->config->setNested("polling.enabled", $oldData["polling"]["enabled"]);
        }
        if (isset($oldData["polling"]["interval"])) {
            $this->config->setNested("polling.interval", $oldData["polling"]["interval"]);
        }

        if (isset($oldData["heartbeat"]["interval"])) {
            $this->config->setNested("heartbeat.interval", $oldData["heartbeat"]["interval"]);
        }

        if (isset($oldData["delivery"]["command-delay"])) {
            $this->config->setNested("delivery.command-delay", $oldData["delivery"]["command-delay"]);
        }

        if (isset($oldData["commands"]["setstore"])) {
            $this->config->setNested("commands.setstore", $oldData["commands"]["setstore"]);
        }
        if (isset($oldData["commands"]["verify"])) {
            $this->config->setNested("commands.verify", $oldData["commands"]["verify"]);
        }

        $this->config->save();

        $backupPath = $this->plugin->getDataFolder() . "config.yml.old";
        file_put_contents($backupPath, yaml_emit($oldData));
        $this->plugin->getLogger()->info("Old config backed up to config.yml.old");
    }

    public function getApiKey(): string
    {
        return (string) $this->config->get("api-key", "");
    }

    public function setApiKey(string $apiKey): void
    {
        $this->config->set("api-key", $apiKey);
        $this->config->save();
    }

    public function getBaseUrl(): string
    {
        return (string) $this->config->getNested("api.base-url", "https://mcsets.com/api/v1/setstore");
    }

    public function getApiTimeout(): int
    {
        return (int) $this->config->getNested("api.timeout", 30);
    }

    public function getApiReconnectDelay(): int
    {
        return (int) $this->config->getNested("api.reconnect-delay", 3);
    }

    public function getApiMaxReconnectAttempts(): int
    {
        return (int) $this->config->getNested("api.max-reconnect-attempts", 2);
    }

    public function getServerIp(): string
    {
        return (string) $this->config->getNested("server.ip", "");
    }

    public function getServerPort(): int
    {
        return (int) $this->config->getNested("server.port", 0);
    }

    public function getPollingInterval(): int
    {
        return (int) $this->config->getNested("polling.interval", 5);
    }

    public function getHeartbeatInterval(): int
    {
        return (int) $this->config->getNested("heartbeat.interval", 300);
    }

    public function getCommandDelay(): int
    {
        return (int) $this->config->getNested("delivery.command-delay", 0);
    }

    public function getSetStoreCommandName(): string
    {
        return (string) $this->config->getNested("commands.setstore", "setstore");
    }

    public function getVerifyCommandName(): string
    {
        return (string) $this->config->getNested("commands.verify", "verify");
    }
}
