<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

/**
 * 打开图形界面
 */
final class GuiSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "gui";
    }

    public function getAliases(): array
    {
        return ["ui", "menu", "界面"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_gui";
    }

    public function isOpOnly(): bool
    {
        return false;
    }

    public function isPlayerOnly(): bool
    {
        return true;
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $player = $this->asPlayer($sender);
        if ($player === null) {
            return;
        }
        $this->plugin->getFormFactory()->openMain($player);
    }
}