<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Network;

use MengBao\MEBFloatingText\Text\FloatingText;
use pocketmine\player\Player;

/**
 * 一条浮空字在某个玩家客户端上的显示状态
 *
 * 保存已生成的每行实体，刷新时做差量更新：
 * 行数不变就只改文本，行数变化才增删实体。
 */
final class TextView
{
    /** @var TextActor[] 索引即行号 */
    private array $actors = [];

    public function __construct(
        private readonly FloatingText $text,
    ) {
    }

    /**
     * 按排版结果同步到客户端
     *
     * @param string[] $segments 已完成变量替换与排版的每一行
     */
    public function sync(Player $player, array $segments): void
    {
        $count = count($segments);

        //多出来的行直接移除
        for ($i = $count, $total = count($this->actors); $i < $total; ++$i) {
            $this->actors[$i]->despawnFrom($player);
            unset($this->actors[$i]);
        }
        $this->actors = array_values($this->actors);

        for ($i = 0; $i < $count; ++$i) {
            $position = $this->text->getLinePosition($i);
            if (!isset($this->actors[$i])) {
                $actor = new TextActor($position);
                $actor->spawnTo($player, $segments[$i]);
                $this->actors[$i] = $actor;
                continue;
            }

            $actor = $this->actors[$i];
            //坐标或行间距被改过，移动而不是重建
            if (!$actor->getPosition()->equals($position)) {
                $actor->move($player, $position);
            }
            $actor->updateText($player, $segments[$i]);
        }
    }

    public function despawn(Player $player): void
    {
        foreach ($this->actors as $actor) {
            $actor->despawnFrom($player);
        }
        $this->actors = [];
    }

    public function isSpawned(): bool
    {
        return $this->actors !== [];
    }
}