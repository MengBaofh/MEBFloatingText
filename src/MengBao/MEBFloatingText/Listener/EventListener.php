<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Listener;

use MengBao\MEBFloatingText\Main;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

final class EventListener implements Listener
{
    public function __construct(
        private readonly Main $plugin,
    ) {
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        //刚进服时客户端还没接收完区块，延迟一点再发实体，否则可能显示不出来
        $this->refreshLater($event->getPlayer(), $this->plugin->getJoinDelay());
    }

    public function onQuit(PlayerQuitEvent $event): void
    {
        //玩家已经断开，不需要再发移除包，只清理服务端记录
        $this->plugin->getManager()->clearPlayer($event->getPlayer(), false);
    }

    public function onTeleport(EntityTeleportEvent $event): void
    {
        $player = $event->getEntity();
        if (!$player instanceof Player) {
            return;
        }
        //跨世界传送时客户端会丢掉全部实体，必须清掉记录重新生成
        if ($event->getFrom()->getWorld() !== $event->getTo()->getWorld()) {
            $this->plugin->getManager()->clearPlayer($player, false);
        }
        $this->refreshLater($player, 20);
    }

    /**
     * 延迟若干tick后刷新该玩家的浮空字
     */
    private function refreshLater(Player $player, int $delay): void
    {
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
            function () use ($player): void {
                if (!$player->isOnline()) {
                    return;
                }
                $this->plugin->getManager()->refreshPlayer($player);
            }
        ), max(1, $delay));
    }
}