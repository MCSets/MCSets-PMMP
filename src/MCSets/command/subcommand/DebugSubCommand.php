<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class DebugSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $loader = Loader::getInstance();
        $loader->getConfigManager()->setDebugEnabled(!$loader->getConfigManager()->isDebugEnabled());
        $loader->reloadConfig();
        $sender->sendMessage(TextFormat::YELLOW . "Debug: " . ($loader->getConfigManager()->isDebugEnabled() ? TextFormat::GREEN . "Enabled" : TextFormat::RED . "Disabled"));
    }
}
