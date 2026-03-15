<?php

declare(strict_types=1);

namespace MCSets\command;

use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\BaseSubCommand;
use MCSets\command\subcommand\APIKeySubCommand;
use MCSets\command\subcommand\DebugSubCommand;
use MCSets\command\subcommand\HelpSubCommand;
use MCSets\command\subcommand\QueueSubCommand;
use MCSets\command\subcommand\ReconnectSubCommand;
use MCSets\command\subcommand\ReloadSubCommand;
use MCSets\command\subcommand\StatusSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class SetStoreCommand extends BaseCommand
{
    public function getPermission(): string
    {
        return "mcsets.admin";
    }

    private function createSubCommand(string $class, string $name, string $description = "", array $aliases = []): BaseSubCommand
    {
        $plugin = $this->getOwningPlugin();
        $ctor = (new \ReflectionClass($class))->getConstructor();

        if ($ctor !== null) {
            $params = $ctor->getParameters();
            if (isset($params[0]) && $params[0]->hasType()) {
                $type = $params[0]->getType();
                if ($type instanceof \ReflectionNamedType) {
                    $typeName = $type->getName();
                    if (
                        $typeName === Plugin::class ||
                        $typeName === PluginBase::class ||
                        is_subclass_of($typeName, Plugin::class)
                    ) {
                        return new $class($plugin, $name, $description, $aliases);
                    }
                }
            }
        }

        return new $class($name, $description, $aliases);
    }

    protected function prepare(): void
    {
        $this->registerSubCommand($this->createSubCommand(APIKeySubCommand::class, "apikey", "Set your API key", ["key", "token", "secret"]));
        $this->registerSubCommand($this->createSubCommand(DebugSubCommand::class, "debug", "Toggle debug logging"));
        $this->registerSubCommand($this->createSubCommand(HelpSubCommand::class, "help", "Show available commands"));
        $this->registerSubCommand($this->createSubCommand(QueueSubCommand::class, "queue", "Process pending deliveries"));
        $this->registerSubCommand($this->createSubCommand(ReconnectSubCommand::class, "reconnect", "Reconnect to MCSets"));
        $this->registerSubCommand($this->createSubCommand(ReloadSubCommand::class, "reload", "Reload configuration"));
        $this->registerSubCommand($this->createSubCommand(StatusSubCommand::class, "status", "View connection status"));
    }
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        //TODO: make a form?
        $sender->sendMessage(TextFormat::YELLOW . "MCSets SetStore - Use /{$aliasUsed} help for commands");
    }
}
