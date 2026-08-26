<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Render;

use pocketmine\utils\TextFormat;

/**
 * 字形宽度表
 *
 * MC客户端的浮空字(nametag)每一行都是居中渲染的，想要实现左/右对齐、
 * 自动换行等排版效果，就必须知道每个字符占多少像素。
 * 这里按MC默认字体的字形宽度建表，半角字符大多为6像素，
 * 全角(中日韩)字符为8像素，空格为4像素。
 */
final class FontMetrics
{
    /** 半角空格宽度(像素) */
    public const SPACE_WIDTH = 4;
    /** 半角字符默认宽度(像素) */
    public const DEFAULT_WIDTH = 6;
    /** 全角字符宽度(像素) */
    public const WIDE_WIDTH = 8;

    /** 宽度与默认值不同的半角字符 */
    private const WIDTHS = [
        ' ' => 4,
        '!' => 2,
        '"' => 5,
        "'" => 3,
        '(' => 5,
        ')' => 5,
        '*' => 5,
        ',' => 2,
        '.' => 2,
        ':' => 2,
        ';' => 2,
        '<' => 5,
        '>' => 5,
        '@' => 7,
        'I' => 4,
        '[' => 4,
        ']' => 4,
        '`' => 3,
        'f' => 5,
        'i' => 2,
        'k' => 5,
        'l' => 3,
        't' => 4,
        '{' => 5,
        '|' => 2,
        '}' => 5,
        '~' => 7,
    ];

    /** 全角字符的Unicode码点区间 */
    private const WIDE_RANGES = [
        [0x1100, 0x115F],
        [0x2E80, 0x303E],
        [0x3041, 0x33FF],
        [0x3400, 0x4DBF],
        [0x4E00, 0x9FFF],
        [0xA000, 0xA4CF],
        [0xA960, 0xA97F],
        [0xAC00, 0xD7A3],
        [0xF900, 0xFAFF],
        [0xFE10, 0xFE19],
        [0xFE30, 0xFE6F],
        [0xFF00, 0xFF60],
        [0xFFE0, 0xFFE6],
        [0x1F300, 0x1FAFF],
    ];

    private function __construct()
    {
        //工具类，禁止实例化
    }

    /**
     * 按UTF-8字符切分字符串
     *
     * @return string[]
     */
    public static function split(string $text): array
    {
        if ($text === "") {
            return [];
        }
        return mb_str_split($text, 1, "UTF-8");
    }

    /**
     * 判断是否为全角字符
     */
    public static function isWide(string $char): bool
    {
        $code = mb_ord($char, "UTF-8");
        if ($code === false) {
            return false;
        }
        foreach (self::WIDE_RANGES as [$start, $end]) {
            if ($code >= $start && $code <= $end) {
                return true;
            }
        }
        return false;
    }

    /**
     * 单个字符的像素宽度
     *
     * @param bool $bold 粗体每个字符会额外占1像素
     */
    public static function charWidth(string $char, bool $bold = false): int
    {
        if ($char === "\n" || $char === "\r") {
            return 0;
        }
        if (self::isWide($char)) {
            return self::WIDE_WIDTH + ($bold ? 1 : 0);
        }
        $width = self::WIDTHS[$char] ?? self::DEFAULT_WIDTH;
        if ($bold && $char !== " ") {
            ++$width;
        }
        return $width;
    }

    /**
     * 计算一行文本的像素宽度，自动忽略§颜色代码并处理粗体
     */
    public static function stringWidth(string $text): int
    {
        $chars = self::split($text);
        $count = count($chars);
        $width = 0;
        $bold = false;
        for ($i = 0; $i < $count; ++$i) {
            $char = $chars[$i];
            if ($char === TextFormat::ESCAPE && $i + 1 < $count) {
                $code = strtolower($chars[++$i]);
                if ($code === "l") {
                    $bold = true;
                } elseif ($code === "r" || FormatState::isColor($code)) {
                    $bold = false;
                }
                continue;
            }
            $width += self::charWidth($char, $bold);
        }
        return $width;
    }

    /**
     * 用半角空格填充指定像素宽度，返回最接近的空格串
     */
    public static function spaces(int $pixels): string
    {
        if ($pixels <= 0) {
            return "";
        }
        return str_repeat(" ", (int) round($pixels / self::SPACE_WIDTH));
    }
}