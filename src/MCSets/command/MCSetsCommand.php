<?php

declare(strict_types=1);

namespace MCSets\command;

use CortexPE\Commando\BaseCommand;
use MCSets\command\form\MCSetsSettingsForm;
use MCSets\command\subcommand\TokenSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class MCSetsCommand extends BaseCommand
{
    public function getPermission()
    {
        return "mcsets.admin";
    }

    protected function prepare(): void
    {
        $this->registerSubCommand(new TokenSubCommand("token", "Set your store's token", ["secret"]));
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if ($sender instanceof Player) {
            $sender->sendForm(new MCSetsSettingsForm());
        } else {
            $sender->sendMessage(TextFormat::RED . "You must run a SubCommand when using CONSOLE");
            $this->sendUsage();
        }
    }
}
