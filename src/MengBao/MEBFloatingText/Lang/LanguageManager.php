<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Lang;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

/**
 * 多语言管理器
 *
 * 语言文件放在插件的resources/language/下，首次启动会复制到数据目录，
 * 之后服主可以直接改数据目录里的文件，不会被插件更新覆盖。
 */
final class LanguageManager
{
    /** 内置的语言文件，新增语言时往这里加一项 */
    public const BUILTIN = ["zh_CN", "en_US"];

    /** 找不到任何语言文件时的兜底语言 */
    private const FALLBACK = "zh_CN";

    /** @var array<string, string> 当前语言的键值表 */
    private array $data = [];

    private string $current = self::FALLBACK;

    public function __construct(
        private readonly PluginBase $plugin,
    ) {
        $this->saveDefaults();
    }

    /**
     * 把内置语言文件复制到数据目录
     */
    private function saveDefaults(): void
    {
        foreach (self::BUILTIN as $code) {
            //第二个参数false表示已存在时不覆盖，保护服主的修改
            $this->plugin->saveResource("language/{$code}.yml", false);
        }
    }

    /**
     * 加载指定语言，文件缺失时回退
     */
    public function load(string $code): void
    {
        $file = $this->getLanguageFile($code);
        if ($file === null) {
            $this->plugin->getLogger()->warning("Language file {$code}.yml not found, falling back to " . self::FALLBACK);
            $code = self::FALLBACK;
            $file = $this->getLanguageFile($code);
        }

        //连兜底语言都没有的话保持空表，get()会返回键名，至少不会崩
        $this->data = $file === null ? [] : (new Config($file, Config::YAML))->getAll();
        $this->current = $code;

        if ($this->data === []) {
            $this->plugin->getLogger()->error("No language file could be loaded, messages will show raw keys");
            return;
        }
        $this->plugin->getLogger()->info($this->get("lang_loaded", ["lang" => $code]));
    }

    private function getLanguageFile(string $code): ?string
    {
        if (!$this->isValidCode($code)) {
            return null;
        }
        $file = $this->plugin->getDataFolder() . "language/" . $code . ".yml";
        return is_file($file) ? $file : null;
    }

    /**
     * 语言代码只允许字母数字和下划线，避免被用来跳出目录
     */
    private function isValidCode(string $code): bool
    {
        return preg_match('/^[A-Za-z0-9_\-]{1,32}$/', $code) === 1;
    }

    /**
     * 取一条语言文本并替换{xxx}变量
     *
     * @param array<string, string|int|float> $params
     */
    public function get(string $key, array $params = []): string
    {
        $text = $this->data[$key] ?? null;
        if (!is_string($text)) {
            //缺键时把键名显示出来，方便补翻译，而不是显示成空白
            return "§c[" . $key . "]";
        }
        foreach ($params as $name => $value) {
            $text = str_replace("{" . $name . "}", (string) $value, $text);
        }
        return $text;
    }

    public function getCurrent(): string
    {
        return $this->current;
    }

    /**
     * 数据目录里实际可用的语言列表
     *
     * @return string[]
     */
    public function getAvailable(): array
    {
        $dir = $this->plugin->getDataFolder() . "language/";
        $found = [];
        foreach (glob($dir . "*.yml") ?: [] as $path) {
            $code = basename($path, ".yml");
            if ($this->isValidCode($code)) {
                $found[] = $code;
            }
        }
        sort($found);
        //一个都没找到时至少报出兜底语言，避免GUI里出现空列表
        return $found === [] ? [self::FALLBACK] : $found;
    }

    public function isAvailable(string $code): bool
    {
        return $this->getLanguageFile($code) !== null;
    }
}