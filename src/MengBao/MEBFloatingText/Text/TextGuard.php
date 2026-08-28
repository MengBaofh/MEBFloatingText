<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Text;

use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\plugin\PluginBase;

/**
 * 浮空字的归属权判定
 *
 * 1.2.0之前所有浮空字都是"服务器的"，op想删就删。
 * 加入领地等插件后，浮空字可能属于某个玩家(户主)，
 * 这种时候op不该有权限动它——op换一批人，玩家的领地提示牌不能跟着被清掉。
 *
 * 判定规则:
 *  - 没有户主(服务器公共浮空字): op可管理，和旧版行为一致
 *  - 有户主: 只有户主本人和master可管理，op不行
 *  - 被托管的浮空字: 内容由托管插件生成，谁都不能手改内容，
 *    但户主仍然可以隐藏/移动/删除它
 */
final class TextGuard
{
    private function __construct()
    {
        //工具类，禁止实例化
    }

    /**
     * 是否为最高权限
     *
     * master的判定按优先级走三条路:
     *  1. 控制台恒为master，否则服主在配置写错时会把自己锁在外面
     *  2. MEBFloatingText.master权限节点，不装MEBSociety也能用
     *  3. MEBSociety的"最高权限"，让两个插件的master保持同一个人
     */
    public static function isMaster(PluginBase $plugin, CommandSender $sender): bool
    {
        if ($sender instanceof ConsoleCommandSender) {
            return true;
        }
        if ($sender->hasPermission("MEBFloatingText.master")) {
            return true;
        }
        return self::isSocietyMaster($plugin, strtolower($sender->getName()));
    }

    /**
     * 查询MEBSociety里的最高权限
     *
     * MEBSociety是softdepend，没装时这里恒为false。
     */
    private static function isSocietyMaster(PluginBase $plugin, string $playerName): bool
    {
        $society = $plugin->getServer()->getPluginManager()->getPlugin("MEBSociety");
        if ($society === null || !$society->isEnabled()) {
            return false;
        }
        $players = "\\MengBao\\MEBSociety\\Units\\Players";
        if (!class_exists($players)) {
            return false;
        }
        try {
            return (bool) $players::getInstance($society)->isMaster($playerName);
        } catch (\Throwable) {
            //MEBSociety的配置还没初始化好时会抛异常，当作不是master
            return false;
        }
    }

    /**
     * 能否管理这条浮空字(隐藏/移动/删除/改样式)
     */
    public static function canManage(PluginBase $plugin, CommandSender $sender, FloatingText $text): bool
    {
        if (self::isMaster($plugin, $sender)) {
            return true;
        }
        if ($text->hasOwner()) {
            //有户主的浮空字，op也不能动
            return $text->isOwnedBy($sender->getName());
        }
        return $sender->hasPermission("MEBFloatingText.op");
    }

    /**
     * 能否修改这条浮空字的文本内容
     *
     * 托管中的浮空字内容由插件生成，手改了下次刷新就没了，
     * 所以直接拦住，免得玩家以为是插件有bug。
     */
    public static function canEditContent(PluginBase $plugin, CommandSender $sender, FloatingText $text): bool
    {
        return !$text->isManaged() && self::canManage($plugin, $sender, $text);
    }

    /**
     * 拒绝管理时该显示哪条提示
     */
    public static function denyKey(FloatingText $text): string
    {
        return $text->hasOwner() ? "owned_by_other" : "no_permission";
    }
}