<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

final class VisibleSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "visible";
    }

    public function getAliases(): array
    {
        //不用show/hide作别名：别名带不了意图，/mebft hide会变成切换，容易误解
        return ["toggle", "显示"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_visible";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 1) {
            $this->sendUsage($sender);
            return;
        }
        $text = $this->requireText($sender, $args[0]);
        if ($text === null) {
            return;
        }

        $visible = match (strtolower($args[1] ?? "")) {
            "on", "true", "1", "show", "显示" => true,
            "off", "false", "0", "hide", "隐藏" => false,
            //没给参数就按当前状态取反
            "" => !$text->isVisible(),
            default => null,
        };
        if ($visible === null) {
            $this->error($sender, "visible_invalid");
            return;
        }

        $text->setVisible($visible);
        $this->getManager()->update($text);
        $this->success($sender, $visible ? "visible_on" : "visible_off", ["id" => $text->getId()]);
    }
}