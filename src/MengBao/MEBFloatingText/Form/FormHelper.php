<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Render\TextAlign;
use pocketmine\player\Player;

/**
 * GUI里反复用到的小工具
 */
final class FormHelper
{
    private function __construct()
    {
        //工具类，禁止实例化
    }

    /**
     * 下拉框里对齐方式的取值顺序，和alignLabels一一对应
     *
     * @return string[]
     */
    public static function alignOptions(): array
    {
        return [TextAlign::LEFT->value, TextAlign::CENTER->value, TextAlign::RIGHT->value];
    }

    /**
     * 下拉框显示用的对齐方式名称(已翻译)
     *
     * @return string[]
     */
    public static function alignLabels(Main $plugin): array
    {
        $lang = $plugin->getLang();
        return [
            $lang->get("align_left"),
            $lang->get("align_center"),
            $lang->get("align_right"),
        ];
    }

    /**
     * 某个对齐方式在下拉框里的下标
     */
    public static function alignIndex(TextAlign $align): int
    {
        return match ($align) {
            TextAlign::LEFT => 0,
            TextAlign::CENTER => 1,
            TextAlign::RIGHT => 2,
        };
    }

    /**
     * 把输入框里的一段文本拆成多行
     *
     * 客户端的输入框只有一行，所以约定用\n分隔，和指令里的写法保持一致；
     * 顺手把&转成§，这样GUI里也能写颜色。
     *
     * @return string[]
     */
    public static function parseLines(string $content): array
    {
        $content = str_replace(["\\n", "&"], ["\n", "§"], $content);
        $lines = [];
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            if (trim($line) !== "") {
                $lines[] = $line;
            }
        }
        return $lines;
    }

    /**
     * 把多行内容还原成输入框里的一行，供编辑时回填
     *
     * @param string[] $lines
     */
    public static function joinLines(array $lines): string
    {
        return str_replace("§", "&", implode("\\n", $lines));
    }

    public static function success(Main $plugin, Player $player, string $message): void
    {
        $player->sendMessage($plugin->getPrefix() . "§a" . $message);
    }

    public static function error(Main $plugin, Player $player, string $message): void
    {
        $player->sendMessage($plugin->getPrefix() . "§c" . $message);
    }
}