<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\API;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Text\FloatingText;
use pocketmine\math\Vector3;
use pocketmine\Server;

/**
 * 给其他插件用的浮空字接口
 *
 * 别的插件不应该直接去碰FloatingTextManager，那是内部实现，
 * 这里只暴露"托管浮空字"需要的几个操作，签名保持稳定。
 *
 * 用法(MEBFloatingText建议写成softdepend，没装时插件仍要能跑):
 *
 *   if (FloatingTextAPI::isAvailable()) {
 *       FloatingTextAPI::put("territory_7", $world, $pos, $lines, "mengbao", "MEBTerritory");
 *   }
 *
 * 托管的浮空字有两个特点:
 *  - 内容由托管插件负责，玩家和op都改不了(改了也会被下次刷新覆盖)
 *  - 指定了户主之后，op无权删除或隐藏，只有户主本人和master可以
 */
final class FloatingTextAPI
{
    private function __construct()
    {
        //纯静态接口，禁止实例化
    }

    /**
     * MEBFloatingText是否已装且已启用
     */
    public static function isAvailable(): bool
    {
        return self::getPlugin() !== null;
    }

    private static function getPlugin(): ?Main
    {
        $plugin = Server::getInstance()->getPluginManager()->getPlugin("MEBFloatingText");
        if (!$plugin instanceof Main || !$plugin->isEnabled()) {
            return null;
        }
        return $plugin;
    }

    /**
     * 新建或更新一条托管浮空字
     *
     * 已存在时只改内容、坐标和归属，不动玩家自己调过的对齐/宽度/行间距，
     * 也不动显示开关——户主把它隐藏了，插件刷新一次内容不该又给它打开。
     *
     * @param string[] $lines 每行文本，支持§颜色码
     * @return bool 成功与否，MEBFloatingText没装时返回false
     */
    public static function put(
        string $id,
        string $worldName,
        Vector3 $position,
        array $lines,
        ?string $owner = null,
        ?string $managedBy = null,
    ): bool {
        $plugin = self::getPlugin();
        if ($plugin === null) {
            return false;
        }
        $manager = $plugin->getManager();
        $text = $manager->get($id);
        if ($text === null) {
            return $manager->create($id, $worldName, $position, $lines, $owner, $managedBy) !== null;
        }

        //换世界时要先把旧世界里的实体撤掉，否则那边会留一份撤不掉的
        if ($text->getWorldName() !== $worldName) {
            foreach ($plugin->getServer()->getOnlinePlayers() as $online) {
                $manager->despawn($online, $text);
            }
        }
        $text->setPosition($worldName, $position);
        $text->setLines($lines);
        $text->setOwner($owner);
        $text->setManagedBy($managedBy);
        $manager->update($text);
        return true;
    }

    /**
     * 只更新内容，浮空字不存在时返回false
     *
     * @param string[] $lines
     */
    public static function setLines(string $id, array $lines): bool
    {
        $plugin = self::getPlugin();
        if ($plugin === null) {
            return false;
        }
        $text = $plugin->getManager()->get($id);
        if ($text === null) {
            return false;
        }
        $text->setLines($lines);
        $plugin->getManager()->update($text);
        return true;
    }

    /**
     * 删除一条浮空字
     */
    public static function remove(string $id): bool
    {
        $plugin = self::getPlugin();
        if ($plugin === null) {
            return false;
        }
        return $plugin->getManager()->remove($id);
    }

    public static function exists(string $id): bool
    {
        $plugin = self::getPlugin();
        return $plugin !== null && $plugin->getManager()->exists($id);
    }

    public static function get(string $id): ?FloatingText
    {
        $plugin = self::getPlugin();
        return $plugin?->getManager()->get($id);
    }

    /**
     * 改户主，$owner为null表示改回服务器公共浮空字
     */
    public static function setOwner(string $id, ?string $owner): bool
    {
        $plugin = self::getPlugin();
        if ($plugin === null) {
            return false;
        }
        $text = $plugin->getManager()->get($id);
        if ($text === null) {
            return false;
        }
        $text->setOwner($owner);
        $plugin->getManager()->save();
        return true;
    }

    /**
     * 某个插件托管的全部浮空字id
     *
     * 插件启动时用它对账: 数据里还留着、但业务上已经没了的浮空字要清掉。
     *
     * @return string[]
     */
    public static function listManagedIds(string $pluginName): array
    {
        $plugin = self::getPlugin();
        if ($plugin === null) {
            return [];
        }
        $ids = [];
        foreach ($plugin->getManager()->getByManager($pluginName) as $text) {
            $ids[] = $text->getId();
        }
        return $ids;
    }

    /**
     * 某个玩家名下的全部浮空字id
     *
     * @return string[]
     */
    public static function listOwnedIds(string $playerName): array
    {
        $plugin = self::getPlugin();
        if ($plugin === null) {
            return [];
        }
        $ids = [];
        foreach ($plugin->getManager()->getByOwner($playerName) as $text) {
            $ids[] = $text->getId();
        }
        return $ids;
    }
}