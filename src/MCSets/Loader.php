<?php

declare(strict_types=1);

namespace MCSets;

use CortexPE\Commando\PacketHooker;
use MCSets\command\MCSetsCommand;
use mcsets\utils\ConfigManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

class Loader extends PluginBase
{
    use SingletonTrait;

    private ConfigManager $configManager;

    function onLoad(): void
    {
        $this->setInstance($this);
    }

    function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) PacketHooker::register($this);

        $this->configManager = new ConfigManager($this);

        $token = $this->configManager->getToken();

        if ($token === "") {
            $this->getLogger()->notice("Please configure your Sets-Store private token using //TODO");
        } else {
            $this->setToken($token);
        }

        $command = new MCSetsCommand($this, $this->configManager->getCommandName(), "Manage your MCSets Store");
        $command->setPermission($command->getPermission()); //idk why commando doesnt auto do this
        $this->getServer()->getCommandMap()->register("MCSets", $command);
    }

    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    public function setToken(string $token): void
    {
        //TODO: call api to verify token, and connect store. and save to config
        $this->configManager->setToken($token);
    }
}
