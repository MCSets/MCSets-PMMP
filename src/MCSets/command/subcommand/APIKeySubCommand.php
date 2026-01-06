<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class APIKeySubCommand extends BaseSubCommand
{
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("api-key"));
    }
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $apiKey = $args["api-key"];
        $loader = Loader::getInstance();
        $loader->getConfigManager()->setApiKey($apiKey);
        $sender->sendMessage(TextFormat::GREEN . "API key saved! Connecting to MCSets...");
        $loader->initializeAPI($apiKey);
    }
}
