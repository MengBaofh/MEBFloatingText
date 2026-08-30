<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Text;

use pocketmine\math\Vector3;
use pocketmine\Server;

/**
 * 托管浮空字的位置约束
 *
 * 移动前问一句托管插件位置是否合法，移动后把新位置回写给它。
 * 托管插件没装或不认识这条浮空字时一律放行。
 */
final class ManagedPlacement
{
    /** 托管插件名 => 它的API类名 */
    private const HANDLERS = [
        "MEBTerritory" => "\\MengBao\\MEBTerritory\\API\\TerritoryAPI",
    ];

    private function __construct()
    {
        //工具类，禁止实例化
    }

    /**
     * 这条浮空字能不能移到该位置
     */
    public static function canMoveTo(FloatingText $text, string $worldName, Vector3 $position): bool
    {
        $api = self::resolveApi($text);
        if ($api === null) {
            return true;
        }
        try {
            return (bool) call_user_func([$api, "canPlaceTextAt"], $text->getId(), $worldName, $position);
        } catch (\Throwable) {
            //问不出来就放行
            return true;
        }
    }

    /**
     * 移动完成后把新位置回写给托管插件，避免下次刷新被搬回旧坐标
     */
    public static function commitMove(FloatingText $text, string $worldName, Vector3 $position): void
    {
        $api = self::resolveApi($text);
        if ($api === null) {
            return;
        }
        try {
            call_user_func([$api, "updateTextPosition"], $text->getId(), $worldName, $position);
        } catch (\Throwable) {
            //回写失败不影响本次移动
        }
    }

    /**
     * 拒绝移动时该显示哪条提示
     */
    public static function denyKey(FloatingText $text): string
    {
        return $text->getManagedBy() === "MEBTerritory" ? "move_outside_territory" : "move_denied_by_plugin";
    }

    /**
     * 这条浮空字对应的托管插件API，没有则返回null
     */
    private static function resolveApi(FloatingText $text): ?string
    {
        $managedBy = $text->getManagedBy();
        if ($managedBy === null) {
            return null;
        }
        $api = self::HANDLERS[$managedBy] ?? null;
        if ($api === null || !class_exists($api)) {
            return null;
        }
        $plugin = Server::getInstance()->getPluginManager()->getPlugin($managedBy);
        if ($plugin === null || !$plugin->isEnabled()) {
            return null;
        }
        return $api;
    }
}
