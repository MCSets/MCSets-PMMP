<?php

declare(strict_types=1);

namespace MCSets\thread\task;

use MCSets\thread\MCSetsThread;
use MCSets\Loader;
use pocketmine\scheduler\Task;

class ReadResponsesTask extends Task
{
    private MCSetsThread $thread;

    public function __construct(MCSetsThread $thread)
    {
        $this->thread = $thread;
    }

    public function onRun(): void
    {
        if (!$this->thread->isRunning()) {
            $this->thread = Loader::getInstance()->createNewThread();
        }
        $this->thread->checkResponses();
    }
}
