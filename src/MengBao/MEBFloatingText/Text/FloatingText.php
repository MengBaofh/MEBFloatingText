<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Text;

use MengBao\MEBFloatingText\Render\TextAlign;
use MengBao\MEBFloatingText\Render\TextRenderer;
use pocketmine\math\Vector3;

/**
 * 一条浮空字的数据模型
 *
 * 只负责保存配置与排版结果，不涉及任何网络细节。
 */
final class FloatingText
{
    /** 默认行间距(方块)，与客户端nametag的行高接近 */
    public const DEFAULT_LINE_SPACING = 0.28;

    /**
     * 不含变量时的排版结果缓存
     *
     * 距离检测每秒都会跑一遍所有浮空字，静态内容没必要反复排版。
     *
     * @var string[]|null
     */
    private ?array $renderCache = null;

    /** 是否含变量的缓存 */
    private ?bool $dynamicCache = null;

    /** @param string[] $lines */
    public function __construct(
        private readonly string $id,
        private string $worldName,
        private Vector3 $position,
        private array $lines,
        private TextAlign $align = TextAlign::CENTER,
        private int $maxWidth = 0,
        private float $lineSpacing = self::DEFAULT_LINE_SPACING,
        private bool $visible = true,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getWorldName(): string
    {
        return $this->worldName;
    }

    public function getPosition(): Vector3
    {
        return $this->position;
    }

    public function setPosition(string $worldName, Vector3 $position): void
    {
        $this->worldName = $worldName;
        $this->position = $position;
    }

    /** @return string[] */
    public function getLines(): array
    {
        return $this->lines;
    }

    /** @param string[] $lines */
    public function setLines(array $lines): void
    {
        $this->lines = array_values(array_map('strval', $lines));
        $this->invalidate();
    }

    /**
     * 内容或排版参数变化后清掉缓存
     */
    private function invalidate(): void
    {
        $this->renderCache = null;
        $this->dynamicCache = null;
    }

    public function getLineCount(): int
    {
        return count($this->lines);
    }

    public function addLine(string $line): void
    {
        $this->lines[] = $line;
        $this->invalidate();
    }

    /**
     * 替换指定行，索引从0开始
     */
    public function setLine(int $index, string $line): bool
    {
        if (!isset($this->lines[$index])) {
            return false;
        }
        $this->lines[$index] = $line;
        $this->invalidate();
        return true;
    }

    public function insertLine(int $index, string $line): bool
    {
        if ($index < 0 || $index > count($this->lines)) {
            return false;
        }
        array_splice($this->lines, $index, 0, [$line]);
        $this->invalidate();
        return true;
    }

    public function removeLine(int $index): bool
    {
        if (!isset($this->lines[$index])) {
            return false;
        }
        array_splice($this->lines, $index, 1);
        $this->invalidate();
        return true;
    }

    public function getAlign(): TextAlign
    {
        return $this->align;
    }

    public function setAlign(TextAlign $align): void
    {
        $this->align = $align;
        $this->invalidate();
    }

    public function getMaxWidth(): int
    {
        return $this->maxWidth;
    }

    public function setMaxWidth(int $maxWidth): void
    {
        $this->maxWidth = max(0, $maxWidth);
        $this->invalidate();
    }

    public function getLineSpacing(): float
    {
        return $this->lineSpacing;
    }

    public function setLineSpacing(float $lineSpacing): void
    {
        $this->lineSpacing = max(0.05, min(2.0, $lineSpacing));
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): void
    {
        $this->visible = $visible;
    }

    /**
     * 是否含有需要定时刷新的变量
     */
    public function isDynamic(): bool
    {
        if ($this->dynamicCache !== null) {
            return $this->dynamicCache;
        }
        $dynamic = false;
        foreach ($this->lines as $line) {
            if (str_contains($line, "{")) {
                $dynamic = true;
                break;
            }
        }
        return $this->dynamicCache = $dynamic;
    }

    /**
     * 排版后的每一行，$lines应为已经替换过变量的文本
     *
     * @param string[] $lines
     * @return string[]
     */
    public function layout(array $lines): array
    {
        //静态内容每次排版结果都一样，缓存下来给每秒的距离检测省开销
        if (!$this->isDynamic()) {
            return $this->renderCache ??= TextRenderer::renderLines($lines, $this->align, $this->maxWidth);
        }
        return TextRenderer::renderLines($lines, $this->align, $this->maxWidth);
    }

    /**
     * 第$index行(从上往下)相对于基准点的坐标
     */
    public function getLinePosition(int $index): Vector3
    {
        return $this->position->add(0, -$index * $this->lineSpacing, 0);
    }

    /**
     * 序列化为配置文件结构
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            "世界" => $this->worldName,
            "x" => round($this->position->x, 3),
            "y" => round($this->position->y, 3),
            "z" => round($this->position->z, 3),
            "对齐方式" => $this->align->value,
            "最大宽度" => $this->maxWidth,
            "行间距" => round($this->lineSpacing, 3),
            "是否显示" => $this->visible,
            "内容" => $this->lines,
        ];
    }

    /**
     * 从配置文件结构还原，数据非法时返回null
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(string $id, array $data): ?self
    {
        $worldName = $data["世界"] ?? null;
        if (!is_string($worldName) || $worldName === "") {
            return null;
        }
        if (!is_numeric($data["x"] ?? null) || !is_numeric($data["y"] ?? null) || !is_numeric($data["z"] ?? null)) {
            return null;
        }
        $lines = $data["内容"] ?? [];
        if (!is_array($lines)) {
            return null;
        }

        return new self(
            $id,
            $worldName,
            new Vector3((float) $data["x"], (float) $data["y"], (float) $data["z"]),
            array_values(array_map('strval', $lines)),
            TextAlign::tryParse(is_string($data["对齐方式"] ?? null) ? $data["对齐方式"] : null) ?? TextAlign::CENTER,
            max(0, (int) ($data["最大宽度"] ?? 0)),
            (float) ($data["行间距"] ?? self::DEFAULT_LINE_SPACING),
            (bool) ($data["是否显示"] ?? true),
        );
    }
}