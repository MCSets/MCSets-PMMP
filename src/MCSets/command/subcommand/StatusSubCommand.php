<?php

declare(strict_types=1);

namespace MCSets\command\subcommand;

use CortexPE\Commando\BaseSubCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class StatusSubCommand extends BaseSubCommand
{
    protected function prepare(): void {}

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $api = Loader::getInstance()->getAPI();
        if ($api === null) {
            $sender->sendMessage(TextFormat::RED . "API not initialized");
            return;
        }

        $sender->sendMessage(TextFormat::GREEN . "------ MCSets Status ------");
        $sender->sendMessage(TextFormat::YELLOW . "Connected: " . ($api->isConnected() ? TextFormat::GREEN . "Yes" : TextFormat::RED . "No"));

        if ($api->isConnected()) {
            $sender->sendMessage(TextFormat::YELLOW . "Server Name: " . TextFormat::WHITE . $api->getServerName());
            $sender->sendMessage(TextFormat::YELLOW . "Server ID: " . TextFormat::WHITE . $api->getServerId());
            $sender->sendMessage(TextFormat::YELLOW . "Active Verifications: " . TextFormat::WHITE . $api->getActiveVerificationsCount());
            $sender->sendMessage(TextFormat::GRAY . "Fetching Deliveries...");

            $api->getThread()->submitRequest("/queue", "GET", [], function (array $response) use ($sender): void {
                if ($sender instanceof Player && !$sender->isConnected()) return;

                if ($response["success"] && isset($response["data"]["deliveries"])) {
                    $count = count($response["data"]["deliveries"]);
                    $sender->sendMessage(TextFormat::YELLOW . "Pending Deliveries: " . TextFormat::WHITE . $count);
                } else {
                    $sender->sendMessage(TextFormat::RED . "Failed to fetch API queue");
                }
                $sender->sendMessage(TextFormat::GREEN . "----------------------");
            });
        } else {
            $sender->sendMessage(TextFormat::GREEN . "----------------------");
        }
    }
}
