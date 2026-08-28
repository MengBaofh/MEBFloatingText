<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use MengBao\MEBFloatingText\Render\TextRenderer;
use pocketmine\command\CommandSender;

final class InfoSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "info";
    }

    public function getAliases(): array
    {
        return ["show", "详情"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_info";
    }

    public function isOpOnly(): bool
    {
        return false;
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

        $position = $text->getPosition();
        $width = TextRenderer::measure($text->getLines(), $text->getMaxWidth());
        $sender->sendMessage($this->tr("info_title", ["id" => $text->getId()]));
        $sender->sendMessage($this->tr("info_world", ["world" => $text->getWorldName()]));
        $sender->sendMessage($this->tr("info_position", [
            "x" => sprintf("%.2f", $position->x),
            "y" => sprintf("%.2f", $position->y),
            "z" => sprintf("%.2f", $position->z),
        ]));
        $sender->sendMessage($this->tr("info_align", ["align" => $this->alignName($text->getAlign())]));
        $sender->sendMessage($this->tr("info_max_width", [
            "width" => $text->getMaxWidth() > 0
                ? $text->getMaxWidth() . $this->tr("pixels")
                : $this->tr("unlimited"),
        ]));
        $sender->sendMessage($this->tr("info_real_width", ["width" => $width]));
        $sender->sendMessage($this->tr("info_spacing", ["spacing" => $text->getLineSpacing()]));
        $sender->sendMessage($this->tr("info_state", [
            "state" => $text->isVisible()
                ? $this->tr("info_state_shown")
                : $this->tr("info_state_hidden"),
        ]));
        $sender->sendMessage($this->tr("info_owner", [
            "owner" => $text->hasOwner() ? (string) $text->getOwner() : $this->tr("owner_none"),
        ]));
        $sender->sendMessage($this->tr("info_managed", [
            "plugin" => $text->isManaged() ? (string) $text->getManagedBy() : $this->tr("owner_unmanaged"),
        ]));
        $sender->sendMessage($this->tr("info_content", ["count" => $text->getLineCount()]));
        foreach ($text->getLines() as $index => $line) {
            $sender->sendMessage($this->tr("info_content_line", ["index" => $index + 1, "text" => $line]));
        }
        $sender->sendMessage($this->tr("divider"));
    }
}