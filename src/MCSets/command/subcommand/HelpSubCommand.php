<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class HelpSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $subCommands = $this->parent->getSubCommands();

        $uniqueCommands = [];
        foreach ($subCommands as $command) {
            $uniqueCommands[spl_object_hash($command)] = $command;
        }

        if (empty($uniqueCommands)) {
            $sender->sendMessage(TextFormat::RED . "No commands available.");
            return;
        }

        $sender->sendMessage(TextFormat::GREEN . "------ MCSets Commands ------");

        foreach ($uniqueCommands as $command) {
            $desc = $command->getDescription() ?? "No description";
            $sender->sendMessage(TextFormat::YELLOW . "/" . $aliasUsed . " " . $command->getName() .
                TextFormat::GRAY . " - " . $desc);
        }

        $sender->sendMessage(TextFormat::GREEN . "----------------------------");
    }
}
