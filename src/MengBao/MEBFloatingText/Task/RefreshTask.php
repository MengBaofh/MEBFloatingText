<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Task;

use MengBao\MEBFloatingText\Main;
use pocketmine\scheduler\Task;

/**
 * 定时刷新任务
 *
 * 两件事分开计时：
 * 变量刷新按配置的间隔走，距离检测则用固定的较短间隔，
 * 这样玩家走近走远的响应快，又不会因为频繁替换变量浪费性能。
 */
final class RefreshTask extends Task
{
    private int $ticks = 0;

    public function __construct(
        private readonly Main $plugin,
        private readonly int $visibilityInterval,
        private readonly int $dynamicInterval,
    ) {
    }

    public function onRun(): void
    {
        ++$this->ticks;
        if ($this->ticks % $this->visibilityInterval === 0) {
            $this->plugin->getManager()->tickVisibility();
        }
        if ($this->ticks % $this->dynamicInterval === 0) {
            $this->plugin->getManager()->tick();
        }
    }
}