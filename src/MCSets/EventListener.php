<?php

declare(strict_types=1);

namespace MCSets;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener
{
    public function __construct(private Loader $plugin) {}

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $api = $this->plugin->getAPI();
        if ($api !== null) {
            $api->onPlayerJoin($event->getPlayer());
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $api = $this->plugin->getAPI();
        if ($api !== null) {
            $api->onPlayerQuit($event->getPlayer());
        }
    }
}
