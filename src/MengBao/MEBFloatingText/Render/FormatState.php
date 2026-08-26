<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Render;

use pocketmine\utils\TextFormat;

/**
 * §格式状态跟踪器
 *
 * 客户端在每一行的开头都会重置文本格式，所以自动换行以后
 * 必须把上一行仍然生效的颜色/样式补回到新行的开头，
 * 否则换行处会突然掉色。
 */
final class FormatState
{
    /** 颜色代码(含基岩版扩展色) */
    private const COLORS = "0123456789abcdefghijmnpqstu";
    /** 样式代码 */
    private const STYLES = "klmno";

    private ?string $color = null;

    /** @var array<string, true> */
    private array $styles = [];

    public static function isColor(string $code): bool
    {
        return strpos(self::COLORS, strtolower($code)) !== false;
    }

    public static function isStyle(string $code): bool
    {
        return strpos(self::STYLES, strtolower($code)) !== false;
    }

    /**
     * 喂入一个格式代码，更新当前状态
     */
    public function feed(string $code): void
    {
        $code = strtolower($code);
        if ($code === "r") {
            $this->reset();
        } elseif (self::isColor($code)) {
            //颜色代码会清掉除自身以外的样式
            $this->color = $code;
            $this->styles = [];
        } elseif (self::isStyle($code)) {
            $this->styles[$code] = true;
        }
    }

    /**
     * 扫描整行文本，把其中的格式代码依次喂入状态机
     */
    public function feedLine(string $line): void
    {
        $chars = FontMetrics::split($line);
        $count = count($chars);
        for ($i = 0; $i < $count; ++$i) {
            if ($chars[$i] === TextFormat::ESCAPE && $i + 1 < $count) {
                $this->feed($chars[++$i]);
            }
        }
    }

    public function reset(): void
    {
        $this->color = null;
        $this->styles = [];
    }

    /**
     * 生成新行开头的格式前缀，$following为新行已有的内容
     *
     * 如果新行本身就以颜色代码或§r开头，那么补前缀是多余的：
     * 颜色代码会清掉之前的颜色和样式，效果完全一样，
     * 这里跳过可以让nametag短一些。
     */
    public function prefixFor(string $following): string
    {
        $chars = FontMetrics::split($following);
        if (($chars[0] ?? "") === TextFormat::ESCAPE && isset($chars[1])) {
            $code = strtolower($chars[1]);
            if ($code === "r" || self::isColor($code)) {
                return "";
            }
        }
        return $this->prefix();
    }

    /**
     * 生成用于新行开头的格式前缀
     */
    public function prefix(): string
    {
        $prefix = "";
        if ($this->color !== null) {
            $prefix .= TextFormat::ESCAPE . $this->color;
        }
        foreach (array_keys($this->styles) as $style) {
            $prefix .= TextFormat::ESCAPE . $style;
        }
        return $prefix;
    }

    /**
     * 当前是否处于粗体状态，粗体会影响字符像素宽度
     */
    public function isBold(): bool
    {
        return isset($this->styles["l"]);
    }

    public function isEmpty(): bool
    {
        return $this->color === null && $this->styles === [];
    }
}