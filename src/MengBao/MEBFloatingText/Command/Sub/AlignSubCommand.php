<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use MengBao\MEBFloatingText\Render\TextAlign;
use pocketmine\command\CommandSender;

final class AlignSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "align";
    }

    public function getAliases(): array
    {
        return ["对齐"];
    }


    /**
     * 权限由requireManageable按归属逐条判断，不在路由层拦
     */
    public function isOpOnly(): bool
    {
        return false;
    }

    public function getDescriptionKey(): string
    {
        return "desc_align";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 2) {
            $this->sendUsage($sender);
            return;
        }
        $text = $this->requireManageable($sender, $args[0]);
        if ($text === null) {
            return;
        }
        $align = TextAlign::tryParse($args[1]);
        if ($align === null) {
            $this->error($sender, "align_invalid");
            return;
        }
        $text->setAlign($align);
        $this->getManager()->update($text);
        $this->success($sender, "align_set", [
            "id" => $text->getId(),
            "align" => $this->alignName($align),
        ]);
    }
}