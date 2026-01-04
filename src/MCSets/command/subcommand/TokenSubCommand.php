<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\args\RawStringArgument;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use MCSets\Loader;
use pocketmine\utils\TextFormat;

class TokenSubCommand extends BaseSubCommand
{
    protected function prepare(): void
    {
        $this->registerArgument(0, new RawStringArgument("token"));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $token = $args["token"];
        //TODO: any checking needed?

        /** @var Loader $loader */
        $loader = $this->getOwningPlugin();
        $loader->setToken($token);

        $sender->sendMessage(TextFormat::GREEN . "Successfully set your token to: " . TextFormat::YELLOW . $token);
    }
}
