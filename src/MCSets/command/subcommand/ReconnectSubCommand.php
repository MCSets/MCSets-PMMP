<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class ReconnectSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $loader = Loader::getInstance();
        $apiKey = $loader->getConfigManager()->getApiKey();
        if ($apiKey === "") {
            $sender->sendMessage(TextFormat::RED . "No API key configured");
            return;
        }
        $sender->sendMessage(TextFormat::YELLOW . "Reconnecting to MCSets...");
        $loader->initializeAPI($apiKey);
    }
}
