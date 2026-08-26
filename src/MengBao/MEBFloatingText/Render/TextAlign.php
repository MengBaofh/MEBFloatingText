<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Render;

/**
 * 浮空字的水平对齐方式
 *
 * 客户端总是把nametag的每一行居中渲染，左/右对齐是用空格补位模拟出来的：
 * 把所有行补到同样的像素宽度，居中之后它们的左(右)边缘自然就对齐了。
 */
enum TextAlign: string
{
    case LEFT = "left";
    case CENTER = "center";
    case RIGHT = "right";

    public static function tryParse(?string $name): ?self
    {
        if ($name === null) {
            return null;
        }
        return match (strtolower(trim($name))) {
            "left", "l", "左", "左对齐" => self::LEFT,
            "center", "centre", "c", "middle", "中", "居中" => self::CENTER,
            "right", "r", "右", "右对齐" => self::RIGHT,
            default => null,
        };
    }

    public function displayName(): string
    {
        return match ($this) {
            self::LEFT => "左对齐",
            self::CENTER => "居中",
            self::RIGHT => "右对齐",
        };
    }
}