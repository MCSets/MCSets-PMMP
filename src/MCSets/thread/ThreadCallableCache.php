<?php

declare(strict_types=1);

namespace MCSets\thread;

class ThreadCallableCache
{
    public static array $callables = [];
    public static $queuePollCallback = null;
}
