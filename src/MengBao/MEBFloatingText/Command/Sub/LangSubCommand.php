<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

/**
 * 切换插件语言
 */
final class LangSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "lang";
    }

    public function getAliases(): array
    {
        return ["language", "语言"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_lang";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $available = $this->lang()->getAvailable();
        if (count($args) < 1) {
            //没给参数时列出可用语言，顺便标出当前用的是哪个
            $this->info($sender, "lang_invalid", ["list" => implode(", ", $available)]);
            return;
        }
        $code = $args[0];
        if (!$this->lang()->isAvailable($code)) {
            $this->error($sender, "lang_invalid", ["list" => implode(", ", $available)]);
            return;
        }
        $this->plugin->switchLanguage($code);
        //用切换后的语言回消息
        $this->success($sender, "lang_switched", ["lang" => $code]);
    }
}