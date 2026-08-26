<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use MengBao\MEBFloatingText\Text\PlaceholderResolver;
use pocketmine\command\CommandSender;

final class VarsSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "vars";
    }

    public function getAliases(): array
    {
        return ["var", "变量"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_vars";
    }

    public function isOpOnly(): bool
    {
        return false;
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $sender->sendMessage($this->tr("vars_title"));
        foreach (PlaceholderResolver::getDescriptionKeys() as $name => $langKey) {
            $sender->sendMessage("§e" . $name . " §7- " . $this->tr($langKey));
        }
        $sender->sendMessage($this->tr("vars_tip"));
        $sender->sendMessage($this->tr("divider"));
    }
}