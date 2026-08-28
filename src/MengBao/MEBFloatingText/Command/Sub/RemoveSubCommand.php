<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use MengBao\MEBFloatingText\Text\TextGuard;
use pocketmine\command\CommandSender;

final class RemoveSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "remove";
    }

    public function getAliases(): array
    {
        return ["del", "delete", "删除"];
    }


    /**
     * 权限在execute里按归属逐条判断，不在路由层拦
     */
    public function isOpOnly(): bool
    {
        return false;
    }

    public function getDescriptionKey(): string
    {
        return "desc_remove";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 1) {
            $this->sendUsage($sender);
            return;
        }

        //这里不能直接用requireManageable：损坏的数据取不出对象，但同样需要能删掉。
        //取得出对象时才有归属信息，才去判权限；取不出来的就只有op能清。
        $text = $this->getManager()->get($args[0]);
        if ($text !== null && !TextGuard::canManage($this->plugin, $sender, $text)) {
            $this->error($sender, TextGuard::denyKey($text), ["owner" => (string) $text->getOwner()]);
            return;
        }

        if (!$this->getManager()->remove($args[0])) {
            $this->error($sender, "not_exist", ["id" => $args[0]]);
            return;
        }
        $this->success($sender, "remove_success", ["id" => $args[0]]);
    }
}