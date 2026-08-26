<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

final class ListSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "list";
    }

    public function getAliases(): array
    {
        return ["ls", "列表"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_list";
    }

    public function isOpOnly(): bool
    {
        return false;
    }

    public function execute(CommandSender $sender, array $args): void
    {
        $texts = array_values($this->getManager()->getAll());
        if ($texts === []) {
            $this->info($sender, "list_empty");
            return;
        }

        $perPage = $this->plugin->getListPerPage();
        $maxPage = (int) ceil(count($texts) / $perPage);
        $page = max(1, min($maxPage, (int) ($args[0] ?? 1)));
        $slice = array_slice($texts, ($page - 1) * $perPage, $perPage);

        $sender->sendMessage($this->tr("list_title", ["page" => $page, "max" => $maxPage]));
        foreach ($slice as $text) {
            $state = $text->isVisible() ? $this->tr("state_shown") : $this->tr("state_hidden");
            $position = $text->getPosition();
            //首行内容作为预览，去掉颜色代码免得刷屏
            $preview = TextFormat::clean($text->getLines()[0] ?? "");
            if (mb_strlen($preview, "UTF-8") > 16) {
                $preview = mb_substr($preview, 0, 16, "UTF-8") . "...";
            }
            $sender->sendMessage($this->tr("list_line", [
                "id" => $text->getId(),
                "state" => $state,
                "world" => $text->getWorldName(),
                "x" => (int) $position->x,
                "y" => (int) $position->y,
                "z" => (int) $position->z,
                "count" => $text->getLineCount(),
                "preview" => $preview,
            ]));
        }
        $sender->sendMessage($this->tr("divider"));
    }
}