<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
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
        //这里不用requireText：损坏的数据取不出对象，但同样需要能删掉
        if (!$this->getManager()->remove($args[0])) {
            $this->error($sender, "not_exist", ["id" => $args[0]]);
            return;
        }
        $this->success($sender, "remove_success", ["id" => $args[0]]);
    }
}