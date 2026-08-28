<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Text;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Network\TextView;
use MengBao\MEBFloatingText\Render\TextAlign;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\Config;

/**
 * 浮空字管理器
 *
 * 统一负责浮空字的增删改查、持久化，以及每个玩家客户端的显示同步。
 */
final class FloatingTextManager
{
    /** @var array<string, FloatingText> 键为小写id */
    private array $texts = [];

    /** @var array<string, array<string, TextView>> 玩家名(小写) => id => 视图 */
    private array $views = [];

    /**
     * 读取失败的原始数据
     *
     * 手动改配置写错了格式的话，这些数据加载不出来，
     * 但保存时要原样写回去，否则玩家的内容就被我们悄悄删掉了。
     *
     * @var array<string, mixed>
     */
    private array $invalid = [];

    private readonly Config $storage;

    private readonly PlaceholderResolver $resolver;

    public function __construct(
        private readonly Main $plugin,
    ) {
        $this->storage = new Config($plugin->getDataFolder() . "FloatingTexts.yml", Config::YAML, []);
        $this->resolver = new PlaceholderResolver(
            $plugin->getServer(),
            (string) $plugin->getSettings()->get("时区", "Asia/Shanghai"),
        );
        $this->load();
    }

    public function getResolver(): PlaceholderResolver
    {
        return $this->resolver;
    }

    // ------------------------------------------------------------------
    // 持久化
    // ------------------------------------------------------------------

    private function load(): void
    {
        foreach ($this->storage->getAll() as $id => $data) {
            $id = (string) $id;
            $lang = $this->plugin->getLang();
            if (!is_array($data)) {
                $this->plugin->getLogger()->warning($lang->get("data_bad_format", ["id" => $id]));
                $this->invalid[$id] = $data;
                continue;
            }
            $text = FloatingText::fromArray($id, $data);
            if ($text === null) {
                $this->plugin->getLogger()->warning($lang->get("data_broken", ["id" => $id]));
                $this->invalid[$id] = $data;
                continue;
            }
            $this->texts[strtolower($id)] = $text;
        }
        $this->plugin->getLogger()->info($this->plugin->getLang()->get("loaded_count", ["count" => count($this->texts)]));
    }

    public function save(): void
    {
        //先写回读不出来的数据，避免手动改配置写错格式后内容丢失
        $out = $this->invalid;
        foreach ($this->texts as $text) {
            $out[$text->getId()] = $text->toArray();
        }
        $this->storage->setAll($out);
        $this->storage->save();
    }

    // ------------------------------------------------------------------
    // 增删改查
    // ------------------------------------------------------------------

    public function get(string $id): ?FloatingText
    {
        return $this->texts[strtolower($id)] ?? null;
    }

    /**
     * id是否已被占用
     *
     * 读不出来的数据也算占用，否则新建同名浮空字保存时会把它顶掉。
     */
    public function exists(string $id): bool
    {
        if (isset($this->texts[strtolower($id)])) {
            return true;
        }
        foreach (array_keys($this->invalid) as $invalidId) {
            if (strtolower((string) $invalidId) === strtolower($id)) {
                return true;
            }
        }
        return false;
    }

    /** @return array<string, FloatingText> */
    public function getAll(): array
    {
        return $this->texts;
    }

    public function count(): int
    {
        return count($this->texts);
    }

    /**
     * 新建一条浮空字，id重复时返回null
     *
     * @param string[]    $lines
     * @param string|null $owner     户主(玩家名)，给了之后op就管不了这条浮空字
     * @param string|null $managedBy 托管插件名，给了之后内容不允许手改
     */
    public function create(
        string $id,
        string $worldName,
        Vector3 $position,
        array $lines,
        ?string $owner = null,
        ?string $managedBy = null,
    ): ?FloatingText {
        if ($this->exists($id)) {
            return null;
        }
        $settings = $this->plugin->getSettings();
        $text = new FloatingText(
            $id,
            $worldName,
            $position,
            $lines,
            TextAlign::tryParse((string) $settings->get("默认对齐方式", "center")) ?? TextAlign::CENTER,
            max(0, (int) $settings->get("默认最大宽度", 0)),
            (float) $settings->get("默认行间距", FloatingText::DEFAULT_LINE_SPACING),
            true,
            $owner === null || $owner === "" ? null : strtolower($owner),
            $managedBy === null || $managedBy === "" ? null : $managedBy,
        );
        $this->texts[strtolower($id)] = $text;
        $this->save();
        $this->refreshAll($text);
        return $text;
    }

    /**
     * 某个玩家名下的全部浮空字
     *
     * @return array<string, FloatingText>
     */
    public function getByOwner(string $playerName): array
    {
        $playerName = strtolower($playerName);
        $found = [];
        foreach ($this->texts as $key => $text) {
            if ($text->isOwnedBy($playerName)) {
                $found[$key] = $text;
            }
        }
        return $found;
    }

    /**
     * 某个插件托管的全部浮空字
     *
     * @return array<string, FloatingText>
     */
    public function getByManager(string $pluginName): array
    {
        $found = [];
        foreach ($this->texts as $key => $text) {
            if ($text->getManagedBy() === $pluginName) {
                $found[$key] = $text;
            }
        }
        return $found;
    }

    public function remove(string $id): bool
    {
        $text = $this->get($id);
        if ($text === null) {
            //损坏的数据也允许删除，否则它只能手动改配置才能清掉
            return $this->removeInvalid($id);
        }
        //先从所有客户端撤掉实体，再删数据
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $this->despawn($player, $text);
        }
        unset($this->texts[strtolower($id)]);
        $this->save();
        return true;
    }

    /**
     * 删除一条读取失败的数据
     */
    private function removeInvalid(string $id): bool
    {
        foreach (array_keys($this->invalid) as $invalidId) {
            if (strtolower((string) $invalidId) !== strtolower($id)) {
                continue;
            }
            unset($this->invalid[$invalidId]);
            $this->save();
            return true;
        }
        return false;
    }

    /**
     * 修改浮空字后调用：保存并刷新所有玩家的显示
     */
    public function update(FloatingText $text): void
    {
        $this->save();
        $this->refreshAll($text);
    }

    // ------------------------------------------------------------------
    // 显示同步
    // ------------------------------------------------------------------

    /**
     * 刷新某个玩家能看到的所有浮空字
     */
    public function refreshPlayer(Player $player): void
    {
        foreach ($this->texts as $text) {
            $this->refresh($player, $text);
        }
    }

    /**
     * 刷新所有在线玩家对某条浮空字的显示
     */
    public function refreshAll(FloatingText $text): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $this->refresh($player, $text);
        }
    }

    /**
     * 刷新单个玩家对单条浮空字的显示
     */
    public function refresh(Player $player, FloatingText $text): void
    {
        if (!$this->shouldShow($player, $text)) {
            $this->despawn($player, $text);
            return;
        }

        $view = $this->getView($player, $text);
        //没有变量就不用逐玩家替换，直接走排版缓存
        $lines = $text->isDynamic()
            ? $this->resolver->resolveLines($text->getLines(), $player)
            : $text->getLines();
        $segments = $text->layout($lines);
        if ($segments === []) {
            $this->despawn($player, $text);
            return;
        }
        $view->sync($player, $segments);
    }

    /**
     * 判断浮空字是否应该显示给该玩家
     *
     * 距离太远时撤掉实体，一是省流量，二是避免客户端在未加载区块里堆积实体。
     */
    private function shouldShow(Player $player, FloatingText $text): bool
    {
        if (!$text->isVisible()) {
            return false;
        }
        $world = $player->getWorld();
        if ($world->getFolderName() !== $text->getWorldName()) {
            return false;
        }
        $range = $this->plugin->getViewRange();
        if ($range > 0 && $player->getPosition()->distanceSquared($text->getPosition()) > $range ** 2) {
            return false;
        }
        //所在区块没加载时客户端收到实体也显示不出来
        $position = $text->getPosition();
        return $world->isChunkLoaded((int) floor($position->x) >> 4, (int) floor($position->z) >> 4);
    }

    private function getView(Player $player, FloatingText $text): TextView
    {
        $name = strtolower($player->getName());
        return $this->views[$name][strtolower($text->getId())] ??= new TextView($text);
    }

    public function despawn(Player $player, FloatingText $text): void
    {
        $name = strtolower($player->getName());
        $id = strtolower($text->getId());
        $view = $this->views[$name][$id] ?? null;
        if ($view === null) {
            return;
        }
        $view->despawn($player);
        unset($this->views[$name][$id]);
    }

    /**
     * 玩家退出/切换世界时清理其视图状态
     */
    public function clearPlayer(Player $player, bool $despawn = false): void
    {
        $name = strtolower($player->getName());
        if (!isset($this->views[$name])) {
            return;
        }
        if ($despawn) {
            foreach ($this->views[$name] as $view) {
                $view->despawn($player);
            }
        }
        unset($this->views[$name]);
    }

    /**
     * 定时任务入口：只刷新含变量的浮空字
     */
    public function tick(): void
    {
        $dynamic = [];
        foreach ($this->texts as $text) {
            if ($text->isDynamic()) {
                $dynamic[] = $text;
            }
        }
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            foreach ($dynamic as $text) {
                $this->refresh($player, $text);
            }
        }
    }

    /**
     * 距离检测任务入口：处理玩家走近/走远导致的显示变化
     */
    public function tickVisibility(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $this->refreshPlayer($player);
        }
    }

    /**
     * 插件关闭时撤掉所有实体，避免客户端残留
     */
    public function shutdown(): void
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            $this->clearPlayer($player, true);
        }
        $this->save();
    }
}