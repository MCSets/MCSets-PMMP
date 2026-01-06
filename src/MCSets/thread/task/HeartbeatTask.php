<?php

declare(strict_types=1);

namespace MCSets\thread\task;

use MCSets\Loader;
use pocketmine\scheduler\Task;

class HeartbeatTask extends Task
{
    public function onRun(): void
    {
        $api = Loader::getInstance()->getAPI();
        if ($api !== null && $api->isConnected()) {
            $api->sendHeartbeat();
        }
    }
}
