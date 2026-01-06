<?php

declare(strict_types=1);

namespace MCSets\command;

use CortexPE\Commando\BaseCommand;
use MCSets\Loader;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class VerifyCommand extends BaseCommand
{
    public function getPermission(): string
    {
        return "mcsets.verify";
    }
    protected function prepare(): void {}
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used by players");
            return;
        }
        $api = Loader::getInstance()->getAPI();
        if ($api === null || !$api->isConnected()) {
            $sender->sendMessage(TextFormat::RED . "MCSets is not connected. Please contact an administrator.");
            return;
        }
        $username = $sender->getName();
        $xuid = $sender->getXuid();
        if ($xuid === "") {
            $sender->sendMessage(TextFormat::RED . "Failed to get your XUID. Please rejoin the server.");
            return;
        }
        $sender->sendMessage(TextFormat::GRAY . "Generating verification code...");
        $api->generateVerifyCode($username, $xuid, function (array $response) use ($sender): void {
            if (!$sender->isConnected()) {
                return;
            }
            if (!$response["success"] || !isset($response["data"]["code"])) {
                $error = $response["error"] ?? "Unknown error";
                $sender->sendMessage(TextFormat::RED . "Failed to generate verification code: " . $error);
                return;
            }
            $code = $response["data"]["code"];
            $storeUrl = $response["data"]["store_url"] ?? "the store";
            $expiresIn = $response["data"]["expires_in"] ?? 600;
            $sender->sendMessage(TextFormat::GREEN . "------------------------------");
            $sender->sendMessage(TextFormat::YELLOW . "Your Verification Code: " . TextFormat::BOLD . TextFormat::AQUA . $code);
            $sender->sendMessage(TextFormat::GRAY . "Visit: " . TextFormat::WHITE . $storeUrl);
            $sender->sendMessage(TextFormat::GRAY . "Expires in: " . TextFormat::WHITE . ($expiresIn / 60) . " minutes");
            $sender->sendMessage(TextFormat::GREEN . "------------------------------");
        });
    }
}
