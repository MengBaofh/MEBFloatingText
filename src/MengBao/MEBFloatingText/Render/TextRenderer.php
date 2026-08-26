<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Render;

use pocketmine\utils\TextFormat;

/**
 * 浮空字排版器
 *
 * 负责把多行原始文本处理成客户端能直接显示的nametag字符串，做了三件事：
 * 1. 按像素宽度自动折行(中英文混排都能正确断行)；
 * 2. 折行/换行以后把仍然生效的颜色与样式补到新行开头，避免掉色；
 * 3. 用空格补位模拟左对齐与右对齐。
 */
final class TextRenderer
{
    private function __construct()
    {
        //工具类，禁止实例化
    }

    /**
     * @param string[] $lines    原始文本行
     * @param int      $maxWidth 每行最大像素宽度，0表示不折行
     */
    public static function render(array $lines, TextAlign $align = TextAlign::CENTER, int $maxWidth = 0): string
    {
        return implode("\n", self::renderLines($lines, $align, $maxWidth));
    }

    /**
     * 排版并对齐，返回可直接写入nametag的每一行
     *
     * @param string[] $lines
     * @return string[]
     */
    public static function renderLines(array $lines, TextAlign $align = TextAlign::CENTER, int $maxWidth = 0): array
    {
        $segments = self::layout($lines, $maxWidth);
        if ($segments === []) {
            return [];
        }
        return self::align($segments, $align);
    }

    /**
     * 折行，返回排版后的每一行
     *
     * @param string[] $lines
     * @return string[]
     */
    public static function layout(array $lines, int $maxWidth = 0): array
    {
        $state = new FormatState();
        $out = [];
        foreach ($lines as $line) {
            $line = (string) $line;
            //配置里允许直接写\n，这里统一展开成硬换行
            foreach (preg_split('/\r\n|\r|\n/', $line) as $hardLine) {
                if ($maxWidth > 0) {
                    foreach (self::wrap($hardLine, $maxWidth, $state) as $segment) {
                        $out[] = $segment;
                    }
                } else {
                    $out[] = $state->prefixFor($hardLine) . $hardLine;
                    $state->feedLine($hardLine);
                }
            }
        }
        return $out;
    }

    /**
     * 把一行文本按像素宽度折成多行
     *
     * $state 会随着解析持续更新，调用结束后保存的是这一行末尾的格式状态。
     *
     * @return string[]
     */
    private static function wrap(string $line, int $maxWidth, FormatState $state): array
    {
        $chars = FontMetrics::split($line);
        $count = count($chars);
        if ($count === 0) {
            return [$state->prefix()];
        }

        $out = [];
        $current = $state->prefixFor($line);
        $width = 0;
        //可断行位置(位于$current中的字节偏移)，以及该处的格式状态
        $breakOffset = -1;
        $breakState = null;

        for ($i = 0; $i < $count; ++$i) {
            $char = $chars[$i];

            //格式代码不占宽度，原样保留并更新状态
            if ($char === TextFormat::ESCAPE && $i + 1 < $count) {
                $code = $chars[++$i];
                $current .= $char . $code;
                $state->feed($code);
                continue;
            }

            $charWidth = FontMetrics::charWidth($char, $state->isBold());

            if ($width + $charWidth > $maxWidth && $width > 0) {
                if ($breakOffset > 0 && $breakState !== null) {
                    //在最近的空格处断开，把余下的内容挪到新行
                    $out[] = rtrim(substr($current, 0, $breakOffset));
                    $rest = ltrim(substr($current, $breakOffset));
                    $current = $breakState->prefixFor($rest) . $rest;
                } else {
                    //整段没有空格(例如中文或超长单词)，直接硬断
                    $out[] = $current;
                    $current = $state->prefix();
                }
                $width = FontMetrics::stringWidth($current);
                $breakOffset = -1;
                $breakState = null;
            }

            $current .= $char;
            $width += $charWidth;

            //空格之后是天然的断行点
            if ($char === " ") {
                $breakOffset = strlen($current);
                $breakState = clone $state;
            }
        }

        $out[] = $current;
        return $out;
    }

    /**
     * 用空格补位实现对齐
     *
     * @param string[] $segments
     * @return string[]
     */
    private static function align(array $segments, TextAlign $align): array
    {
        if ($align === TextAlign::CENTER) {
            //客户端本身就是居中渲染，不需要补位
            return $segments;
        }

        $widths = [];
        $maxWidth = 0;
        foreach ($segments as $index => $segment) {
            $widths[$index] = FontMetrics::stringWidth($segment);
            $maxWidth = max($maxWidth, $widths[$index]);
        }

        foreach ($segments as $index => $segment) {
            $padding = FontMetrics::spaces($maxWidth - $widths[$index]);
            if ($padding === "") {
                continue;
            }
            $segments[$index] = $align === TextAlign::LEFT
                ? $segment . $padding
                : $padding . $segment;
        }
        return $segments;
    }

    /**
     * 计算排版后的整体像素宽度，供指令回显使用
     *
     * @param string[] $lines
     */
    public static function measure(array $lines, int $maxWidth = 0): int
    {
        $width = 0;
        foreach (self::layout($lines, $maxWidth) as $segment) {
            $width = max($width, FontMetrics::stringWidth($segment));
        }
        return $width;
    }
}