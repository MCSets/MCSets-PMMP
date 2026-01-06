<?php

declare(strict_types=1);

namespace MCSets\thread\task;

use MCSets\Loader;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class OnlinePlayersTask extends Task
{
    private array $lastPlayers = [];

    public function onRun(): void
    {
        $api = Loader::getInstance()->getAPI();
        if ($api === null || !$api->isConnected()) {
            return;
        }

        $currentPlayers = [];
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $currentPlayers[] = $player->getName();
        }

        sort($currentPlayers);
        sort($this->lastPlayers);

        if ($currentPlayers !== $this->lastPlayers) {
            $api->reportOnlinePlayers($currentPlayers);
            $this->lastPlayers = $currentPlayers;
        }
    }
}
