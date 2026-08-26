<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

final class ReloadSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "reload";
    }

    public function getAliases(): array
    {
        return ["重载"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_reload";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $this->plugin->reload();
        $this->success($sender, "reload_success", ["count" => $this->getManager()->count()]);
    }
}