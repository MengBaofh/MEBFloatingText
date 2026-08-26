<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Text;

use pocketmine\player\Player;
use pocketmine\Server;

/**
 * 变量替换器
 *
 * 浮空字里可以写{online}这类变量，每次刷新时按玩家逐一替换。
 * 因为每个玩家看到的实体是独立的，所以像{player}这种和玩家相关的变量也能正常工作。
 */
final class PlaceholderResolver
{
    public function __construct(
        private readonly Server $server,
        private readonly string $timezone = "Asia/Shanghai",
    ) {
    }

    /**
     * 支持的变量，值是语言文件里对应的说明键名
     *
     * @return array<string, string>
     */
    public static function getDescriptionKeys(): array
    {
        return [
            "{player}" => "var_player",
            "{online}" => "var_online",
            "{max}" => "var_max",
            "{world}" => "var_world",
            "{tps}" => "var_tps",
            "{load}" => "var_load",
            "{ping}" => "var_ping",
            "{time}" => "var_time",
            "{date}" => "var_date",
            "{money}" => "var_money",
            "{line}" => "var_line",
            "{br}" => "var_br",
        ];
    }

    /**
     * 对整块文本做变量替换
     *
     * @param string[] $lines
     * @return string[]
     */
    public function resolveLines(array $lines, Player $player): array
    {
        $replacements = $this->buildReplacements($player);
        $out = [];
        foreach ($lines as $line) {
            $out[] = strtr((string) $line, $replacements);
        }
        return $out;
    }

    /**
     * @return array<string, string>
     */
    private function buildReplacements(Player $player): array
    {
        $timezone = date_default_timezone_get();
        date_default_timezone_set($this->timezone);
        $time = date("H:i:s");
        $date = date("Y-m-d");
        date_default_timezone_set($timezone);

        return [
            "{player}" => $player->getName(),
            "{online}" => (string) count($this->server->getOnlinePlayers()),
            "{max}" => (string) $this->server->getMaxPlayers(),
            "{world}" => $player->getWorld()->getDisplayName(),
            "{tps}" => sprintf("%.1f", $this->server->getTicksPerSecondAverage()),
            "{load}" => sprintf("%.1f%%", $this->server->getTickUsageAverage()),
            "{ping}" => (string) ($player->getNetworkSession()->getPing() ?? 0),
            "{time}" => $time,
            "{date}" => $date,
            "{money}" => $this->resolveMoney($player),
            "{line}" => "§7--------------------",
            "{br}" => "\n",
        ];
    }

    /**
     * 游戏币来自MEBSociety，没装该插件时返回0
     */
    private function resolveMoney(Player $player): string
    {
        $plugin = $this->server->getPluginManager()->getPlugin("MEBSociety");
        if ($plugin === null || !$plugin->isEnabled()) {
            return "0";
        }
        $economy = "\\MengBao\\MEBSociety\\Units\\Economy";
        if (!class_exists($economy)) {
            return "0";
        }
        try {
            $money = $economy::getInstance($plugin)->getMoney(strtolower($player->getName()));
            return (string) $money;
        } catch (\Throwable) {
            //MEBSociety未初始化该玩家时会抛异常，忽略即可
            return "0";
        }
    }
}