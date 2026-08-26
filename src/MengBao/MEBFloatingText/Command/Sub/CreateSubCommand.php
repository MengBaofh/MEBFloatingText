<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

final class CreateSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "create";
    }

    public function getAliases(): array
    {
        return ["add", "创建"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_create";
    }

    public function isPlayerOnly(): bool
    {
        return true;
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 2) {
            $this->sendUsage($sender);
            return;
        }
        $player = $this->asPlayer($sender);
        if ($player === null) {
            return;
        }

        $id = $args[0];
        if (!preg_match('/^[A-Za-z0-9_\-]{1,32}$/', $id)) {
            $this->error($sender, "id_invalid");
            return;
        }
        if ($this->getManager()->exists($id)) {
            $this->error($sender, "already_exists", ["id" => $id]);
            return;
        }
        $limit = $this->plugin->getMaxTexts();
        if ($limit > 0 && $this->getManager()->count() >= $limit) {
            $this->error($sender, "count_limit", ["limit" => $limit]);
            return;
        }

        //站立点稍微抬高一点，让文字浮在头顶附近而不是埋进地里
        $position = $player->getPosition()->add(0, $this->plugin->getSpawnOffset(), 0);
        $lines = explode("\n", $this->joinText(array_slice($args, 1)));
        $text = $this->getManager()->create($id, $player->getWorld()->getFolderName(), $position, $lines);
        if ($text === null) {
            $this->error($sender, "create_failed");
            return;
        }

        $this->success($sender, "create_success", ["id" => $id, "count" => $text->getLineCount()]);
    }
}