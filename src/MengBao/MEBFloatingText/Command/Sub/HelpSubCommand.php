<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

final class HelpSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "help";
    }

    public function getAliases(): array
    {
        return ["?", "帮助"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_help";
    }

    public function isOpOnly(): bool
    {
        return false;
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $isOp = $sender->hasPermission("MEBFloatingText.op");
        $sender->sendMessage($this->tr("help_title"));
        foreach ($this->plugin->getRouter()->getOrdered() as $command) {
            if ($command->isOpOnly() && !$isOp) {
                continue;
            }
            $sender->sendMessage("§e" . $command->getUsage() . " §7- " . $command->getDescription());
        }
        $sender->sendMessage($this->tr("color_tip"));
        $sender->sendMessage($this->tr("divider"));
    }
}