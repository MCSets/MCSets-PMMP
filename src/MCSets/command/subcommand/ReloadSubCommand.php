<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ReloadSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $loader = Loader::getInstance();
        $loader->reloadConfig();
        $sender->sendMessage(TextFormat::GREEN . "Configuration reloaded!");
        $apiKey = $loader->getConfigManager()->getApiKey();
        if ($apiKey !== "") {
            $loader->initializeAPI($apiKey);
            $sender->sendMessage(TextFormat::YELLOW . "Reloaded Config & Reconnected");
        }
    }
}
