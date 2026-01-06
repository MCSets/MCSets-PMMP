<?php

declare(strict_types=1);

namespace MCSets\command;

use CortexPE\Commando\BaseCommand;
use MCSets\command\subcommand\APIKeySubCommand;
use MCSets\command\subcommand\DebugSubCommand;
use MCSets\command\subcommand\HelpSubCommand;
use MCSets\command\subcommand\QueueSubCommand;
use MCSets\command\subcommand\ReconnectSubCommand;
use MCSets\command\subcommand\ReloadSubCommand;
use MCSets\command\subcommand\StatusSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class SetStoreCommand extends BaseCommand
{
    public function getPermission(): string
    {
        return "mcsets.admin";
    }
    protected function prepare(): void
    {
        $this->registerSubCommand(new APIKeySubCommand("apikey", "Set your API key", ["key", "token", "secret"]));
        $this->registerSubCommand(new DebugSubCommand("debug", "Toggle debug logging"));
        $this->registerSubCommand(new HelpSubCommand("help", "Show available commands"));
        $this->registerSubCommand(new QueueSubCommand("queue", "Process pending deliveries"));
        $this->registerSubCommand(new ReconnectSubCommand("reconnect", "Reconnect to MCSets"));
        $this->registerSubCommand(new ReloadSubCommand("reload", "Reload configuration"));
        $this->registerSubCommand(new StatusSubCommand("status", "View connection status"));
    }
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        //TODO: make a form?
        $sender->sendMessage(TextFormat::YELLOW . "MCSets SetStore - Use /{$aliasUsed} help for commands");
    }
}
