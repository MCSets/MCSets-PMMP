<?php

declare(strict_types=1);

namespace MCSets\utils;

use mcsets\Loader;
use pocketmine\utils\Config;

final class ConfigManager
{
    private Config $config;

    public function __construct(Loader $plugin)
    {
        $plugin->saveDefaultConfig();
        $this->config = $plugin->getConfig();
    }

    public function getToken(): string
    {
        return (string) $this->config->get("token", "");
    }

    public function setToken(string $token): void
    {
        $this->config->set("token", $token);
        $this->config->save();
    }

    public function getWorkerLimit(): int
    {
        return (int) $this->config->get("worker-limit", 2);
    }

    public function getCommandName(): string
    {
        return (string) $this->config->get("command-name", "mcsets");
    }
}
