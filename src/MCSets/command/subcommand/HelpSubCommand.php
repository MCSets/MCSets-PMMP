<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $config = Loader::getInstance()->getConfigManager();
        $setstoreCmd = $config->getSetStoreCommandName();
        $verifyCmd = $config->getVerifyCommandName();
        $sender->sendMessage(TextFormat::GREEN . "------ MCSets Commands ------");
        $sender->sendMessage(TextFormat::YELLOW . "/{$setstoreCmd} apikey <key> " . TextFormat::GRAY . "- Set your API key");
        $sender->sendMessage(TextFormat::YELLOW . "/{$setstoreCmd} status " . TextFormat::GRAY . "- View connection status");
        $sender->sendMessage(TextFormat::YELLOW . "/{$setstoreCmd} queue " . TextFormat::GRAY . "- View pending deliveries");
        $sender->sendMessage(TextFormat::YELLOW . "/{$setstoreCmd} reconnect " . TextFormat::GRAY . "- Reconnect to MCSets");
        $sender->sendMessage(TextFormat::YELLOW . "/{$setstoreCmd} reload " . TextFormat::GRAY . "- Reload configuration");
        $sender->sendMessage(TextFormat::YELLOW . "/{$verifyCmd} " . TextFormat::GRAY . "- Link your account");
        $sender->sendMessage(TextFormat::GREEN . "--------------------------");
    }
}
