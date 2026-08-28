<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use MengBao\MEBFloatingText\Text\TextGuard;
use pocketmine\command\CommandSender;

/**
 * 查看或修改浮空字的户主
 *
 * 指定户主之后op就管不了这条浮空字了，所以"改户主"这个动作本身
 * 只允许master执行，否则op给自己设成户主就能反过来把master锁在外面。
 */
final class OwnerSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "owner";
    }

    public function getAliases(): array
    {
        return ["归属", "户主"];
    }

    /**
     * 查询谁都能用，改归属在execute里单独要求master
     */
    public function isOpOnly(): bool
    {
        return false;
    }

    public function getDescriptionKey(): string
    {
        return "desc_owner";
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

        //只给id时是查询，任何能看到列表的人都可以查
        if (count($args) < 2) {
            $this->info($sender, "owner_current", [
                "id" => $text->getId(),
                "owner" => $text->hasOwner() ? (string) $text->getOwner() : $this->tr("owner_none"),
                "manager" => $text->isManaged() ? (string) $text->getManagedBy() : $this->tr("owner_unmanaged"),
            ]);
            return;
        }

        //改归属会直接影响op的管理权，只有master能改
        if (!TextGuard::isMaster($this->plugin, $sender)) {
            $this->error($sender, "owner_master_only");
            return;
        }

        $target = $args[1];
        if ($target === "-" || strtolower($target) === "none") {
            $text->setOwner(null);
            $this->getManager()->save();
            $this->success($sender, "owner_cleared", ["id" => $text->getId()]);
            return;
        }
        if (!preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $target)) {
            $this->error($sender, "owner_name_invalid");
            return;
        }

        $text->setOwner($target);
        $this->getManager()->save();
        $this->success($sender, "owner_set", [
            "id" => $text->getId(),
            "owner" => (string) $text->getOwner(),
        ]);
    }
}
