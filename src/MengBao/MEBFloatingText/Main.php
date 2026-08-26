<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText;

use MengBao\MEBFloatingText\Command\CommandRouter;
use MengBao\MEBFloatingText\Form\FormFactory;
use MengBao\MEBFloatingText\Lang\LanguageManager;
use MengBao\MEBFloatingText\Listener\EventListener;
use MengBao\MEBFloatingText\Task\RefreshTask;
use MengBao\MEBFloatingText\Text\FloatingText;
use MengBao\MEBFloatingText\Text\FloatingTextManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase
{
    private Config $settings;

    private LanguageManager $lang;

    private FloatingTextManager $manager;

    private CommandRouter $router;

    private FormFactory $formFactory;

    public function onLoad(): void
    {
        $this->getLogger()->info("§c--------------------");
        $this->getLogger()->info("§aMEBFloatingText插件加载中...");
        $this->getLogger()->info("§a作者:梦宝(fanghao)");
        $this->getLogger()->info("§c--------------------");
    }

    public function onEnable(): void
    {
        @mkdir($this->getDataFolder(), 0777, true);
        $this->loadSettings();

        //语言要最先准备好，后面的日志和提示都依赖它
        $this->lang = new LanguageManager($this);
        $this->lang->load((string) $this->settings->get("语言", "zh_CN"));

        $this->manager = new FloatingTextManager($this);
        $this->router = new CommandRouter($this);
        $this->formFactory = new FormFactory($this);

        //MEBForms是softdepend，没装也能跑，只是GUI不可用
        if (!$this->formFactory->isAvailable()) {
            $this->getLogger()->warning($this->lang->get("lib_missing"));
        }

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        //一个任务里跑两种刷新，避免注册多个定时器
        $visibility = max(1, (int) round(20 * (float) $this->settings->get("距离检测间隔(s)", 1)));
        $dynamic = max(1, (int) round(20 * (float) $this->settings->get("变量刷新间隔(s)", 5)));
        $this->getScheduler()->scheduleRepeatingTask(new RefreshTask($this, $visibility, $dynamic), 1);

        //热重载时玩家已经在线，需要主动补发一次
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->manager->refreshPlayer($player);
        }
    }

    public function onDisable(): void
    {
        if (isset($this->manager)) {
            $this->manager->shutdown();
        }
    }

    private function loadSettings(): void
    {
        $this->settings = new Config(
            $this->getDataFolder() . "Settings.yml",
            Config::YAML,
            [
                "语言" => "zh_CN",
                "可视距离" => 48,
                "距离检测间隔(s)" => 1,
                "变量刷新间隔(s)" => 5,
                "进服延迟显示(tick)" => 30,
                "创建时的高度偏移" => 1.6,
                "浮空字数量上限" => 200,
                "列表每页显示数量" => 8,
                "默认对齐方式" => "center",
                "默认最大宽度" => 0,
                "默认行间距" => FloatingText::DEFAULT_LINE_SPACING,
                "时区" => "Asia/Shanghai",
            ]
        );
        //Config构造时已经会自动补齐缺失的默认键并写回文件，这里不需要再处理
    }

    /**
     * 切换语言并写回配置，下次启动仍然生效
     */
    public function switchLanguage(string $code): void
    {
        if (!$this->lang->isAvailable($code)) {
            return;
        }
        $this->lang->load($code);
        $this->settings->set("语言", $code);
        $this->settings->save();
    }

    /**
     * 重载配置与数据，会先撤掉客户端上的旧实体
     */
    public function reload(): void
    {
        $this->manager->shutdown();
        $this->loadSettings();
        $this->lang->load((string) $this->settings->get("语言", "zh_CN"));
        $this->manager = new FloatingTextManager($this);
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->manager->refreshPlayer($player);
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() !== "mebft") {
            return false;
        }
        return $this->router->dispatch($sender, $args);
    }

    // ------------------------------------------------------------------
    // 对外访问
    // ------------------------------------------------------------------

    public function getManager(): FloatingTextManager
    {
        return $this->manager;
    }

    public function getRouter(): CommandRouter
    {
        return $this->router;
    }

    public function getLang(): LanguageManager
    {
        return $this->lang;
    }

    public function getFormFactory(): FormFactory
    {
        return $this->formFactory;
    }

    public function getSettings(): Config
    {
        return $this->settings;
    }

    /**
     * 消息前缀，放在语言文件里方便改
     */
    public function getPrefix(): string
    {
        return $this->lang->get("prefix");
    }

    /** 超出该距离(方块)的浮空字不下发，0表示不限制 */
    public function getViewRange(): float
    {
        return max(0.0, (float) $this->settings->get("可视距离", 48));
    }

    public function getMaxTexts(): int
    {
        return max(0, (int) $this->settings->get("浮空字数量上限", 200));
    }

    public function getSpawnOffset(): float
    {
        return (float) $this->settings->get("创建时的高度偏移", 1.6);
    }

    public function getListPerPage(): int
    {
        return max(1, (int) $this->settings->get("列表每页显示数量", 8));
    }

    public function getJoinDelay(): int
    {
        return max(1, (int) $this->settings->get("进服延迟显示(tick)", 30));
    }
}