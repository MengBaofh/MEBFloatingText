<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command;

use MengBao\MEBFloatingText\Lang\LanguageManager;
use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Text\FloatingText;
use MengBao\MEBFloatingText\Text\FloatingTextManager;
use MengBao\MEBFloatingText\Text\TextGuard;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * 子指令基类
 */
abstract class SubCommand
{
    public function __construct(
        protected readonly Main $plugin,
    ) {
    }

    /** 子指令名 */
    abstract public function getName(): string;

    /** 别名 @return string[] */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * 用法，用于帮助与参数错误提示
     *
     * 默认取语言文件里的 usage_<子指令名>，这样中英文能各写一份，
     * 参数说明("页码"/"page")也会跟着语言切换。
     */
    public function getUsage(): string
    {
        return $this->tr("usage_" . $this->getName());
    }

    /** 说明文字在语言文件里的键名 */
    abstract public function getDescriptionKey(): string;

    public function getDescription(): string
    {
        return $this->lang()->get($this->getDescriptionKey());
    }

    /**
     * 是否需要op权限
     *
     * 注意: 改动单条浮空字的子指令(remove/move/line/...)返回false，
     * 因为浮空字可能属于某个普通玩家，户主本人必须能管理自己的浮空字。
     * 这类子指令的权限由requireManageable按归属逐条判断，
     * 不属于自己又不是op的话照样会被拦下来。
     */
    public function isOpOnly(): bool
    {
        return true;
    }

    /** 是否必须由玩家执行 */
    public function isPlayerOnly(): bool
    {
        return false;
    }

    /**
     * @param string[] $args 已去掉子指令名的参数
     */
    abstract public function execute(CommandSender $sender, array $args): void;

    protected function getManager(): FloatingTextManager
    {
        return $this->plugin->getManager();
    }

    protected function lang(): LanguageManager
    {
        return $this->plugin->getLang();
    }

    /**
     * 取一条语言文本
     *
     * @param array<string, string|int|float> $params
     */
    protected function tr(string $key, array $params = []): string
    {
        return $this->lang()->get($key, $params);
    }

    protected function success(CommandSender $sender, string $key, array $params = []): void
    {
        $sender->sendMessage($this->plugin->getPrefix() . "§a" . $this->tr($key, $params));
    }

    protected function error(CommandSender $sender, string $key, array $params = []): void
    {
        $sender->sendMessage($this->plugin->getPrefix() . "§c" . $this->tr($key, $params));
    }

    protected function info(CommandSender $sender, string $key, array $params = []): void
    {
        $sender->sendMessage($this->plugin->getPrefix() . "§e" . $this->tr($key, $params));
    }

    protected function sendUsage(CommandSender $sender): void
    {
        $sender->sendMessage($this->plugin->getPrefix() . $this->tr("usage", ["usage" => $this->getUsage()]));
    }

    /**
     * 按id取浮空字，不存在时自动提示
     */
    protected function requireText(CommandSender $sender, string $id): ?FloatingText
    {
        $text = $this->getManager()->get($id);
        if ($text === null) {
            $sender->sendMessage($this->plugin->getPrefix() . $this->tr("not_exist", ["id" => $id]));
            return null;
        }
        return $text;
    }

    /**
     * 按id取浮空字，同时检查管理权限
     *
     * 有户主的浮空字op也动不了，所以改动类的子指令都要走这里，
     * 不能只用requireText。
     */
    protected function requireManageable(CommandSender $sender, string $id): ?FloatingText
    {
        $text = $this->requireText($sender, $id);
        if ($text === null) {
            return null;
        }
        if (!TextGuard::canManage($this->plugin, $sender, $text)) {
            $this->error($sender, TextGuard::denyKey($text), ["owner" => (string) $text->getOwner()]);
            return null;
        }
        return $text;
    }

    /**
     * 按id取浮空字，同时检查内容编辑权限
     *
     * 托管中的浮空字内容由插件生成，改了会被覆盖，所以单独拦一层。
     */
    protected function requireEditable(CommandSender $sender, string $id): ?FloatingText
    {
        $text = $this->requireManageable($sender, $id);
        if ($text === null) {
            return null;
        }
        if ($text->isManaged()) {
            $this->error($sender, "managed_by_plugin", ["plugin" => (string) $text->getManagedBy()]);
            return null;
        }
        return $text;
    }

    /**
     * 取出行号参数(玩家输入从1开始，内部从0开始)
     */
    protected function parseLineIndex(CommandSender $sender, FloatingText $text, string $raw): ?int
    {
        if (!ctype_digit($raw)) {
            $this->error($sender, "line_must_int");
            return null;
        }
        $index = (int) $raw - 1;
        if ($index < 0 || $index >= $text->getLineCount()) {
            $this->error($sender, "line_out_of_range", ["count" => $text->getLineCount()]);
            return null;
        }
        return $index;
    }

    /**
     * 把剩余参数拼成文本，同时把&转成§、\n转成真正的换行
     *
     * @param string[] $args
     */
    protected function joinText(array $args): string
    {
        return str_replace(["\\n", "&"], ["\n", "§"], implode(" ", $args));
    }

    protected function asPlayer(CommandSender $sender): ?Player
    {
        return $sender instanceof Player ? $sender : null;
    }

    /**
     * 对齐方式的翻译名
     */
    protected function alignName(\MengBao\MEBFloatingText\Render\TextAlign $align): string
    {
        return $this->tr("align_" . $align->value);
    }
}