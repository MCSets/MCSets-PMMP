<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class QueueSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $api = Loader::getInstance()->getAPI();
        if ($api === null || !$api->isConnected()) {
            $sender->sendMessage(TextFormat::RED . "Not connected to MCSets");
            return;
        }

        $sender->sendMessage(TextFormat::YELLOW . "Fetching queue...");

        $api->getThread()->submitRequest("/queue", "GET", [], function (array $response) use ($sender): void {
            if (!$response["success"]) {
                $sender->sendMessage(TextFormat::RED . "Failed to fetch queue");
                return;
            }

            $count = $response["data"]["count"] ?? 0;
            $sender->sendMessage(TextFormat::GREEN . "Pending deliveries: " . TextFormat::WHITE . $count);
        });
    }
}
