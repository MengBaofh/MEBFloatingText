<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

final class WidthSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "width";
    }

    public function getAliases(): array
    {
        return ["宽度"];
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
        return "desc_width";
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
        if (!ctype_digit($args[1])) {
            $this->error($sender, "width_must_int");
            return;
        }
        $width = (int) $args[1];
        //太小的宽度会把每个字都拆成一行，没有意义
        if ($width !== 0 && $width < 16) {
            $this->error($sender, "width_too_small");
            return;
        }
        $text->setMaxWidth($width);
        $this->getManager()->update($text);
        if ($width === 0) {
            $this->success($sender, "width_off", ["id" => $text->getId()]);
        } else {
            $this->success($sender, "width_set", ["id" => $text->getId(), "width" => $width]);
        }
    }
}