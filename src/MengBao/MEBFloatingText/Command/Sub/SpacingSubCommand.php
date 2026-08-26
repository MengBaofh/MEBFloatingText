<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

final class SpacingSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "spacing";
    }

    public function getAliases(): array
    {
        return ["行间距"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_spacing";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 2) {
            $this->sendUsage($sender);
            return;
        }
        $text = $this->requireText($sender, $args[0]);
        if ($text === null) {
            return;
        }
        if (!is_numeric($args[1])) {
            $this->error($sender, "spacing_must_num");
            return;
        }
        $spacing = (float) $args[1];
        if ($spacing < 0.05 || $spacing > 2.0) {
            $this->error($sender, "spacing_range");
            return;
        }
        $text->setLineSpacing($spacing);
        $this->getManager()->update($text);
        $this->success($sender, "spacing_set", [
            "id" => $text->getId(),
            "spacing" => $text->getLineSpacing(),
        ]);
    }
}