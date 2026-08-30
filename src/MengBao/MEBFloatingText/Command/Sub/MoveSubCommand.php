<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use MengBao\MEBFloatingText\Text\ManagedPlacement;
use pocketmine\command\CommandSender;
use pocketmine\math\Vector3;

final class MoveSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "move";
    }

    public function getAliases(): array
    {
        return ["tp", "移动"];
    }


    /**
     * 权限由requireManageable按归属逐条判断，不在路由层拦
     */
    public function isOpOnly(): bool
    {
        return false;
    }

    public function getDescriptionKey(): string
    {
        return "desc_move";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 1) {
            $this->sendUsage($sender);
            return;
        }
        $text = $this->requireManageable($sender, $args[0]);
        if ($text === null) {
            return;
        }

        $player = $this->asPlayer($sender);
        if (count($args) >= 4) {
            if (!is_numeric($args[1]) || !is_numeric($args[2]) || !is_numeric($args[3])) {
                $this->error($sender, "coords_must_num");
                return;
            }
            //指定坐标时沿用原世界，控制台也能用
            $worldName = $text->getWorldName();
            $position = new Vector3((float) $args[1], (float) $args[2], (float) $args[3]);
        } elseif ($player !== null) {
            $worldName = $player->getWorld()->getFolderName();
            $position = $player->getPosition()->add(0, $this->plugin->getSpawnOffset(), 0);
        } else {
            $this->error($sender, "move_need_coords", ["usage" => $this->getUsage()]);
            return;
        }

        //托管插件对位置可能有自己的规矩，比如领地浮空字必须留在领地范围内
        if (!ManagedPlacement::canMoveTo($text, $worldName, $position)) {
            $this->error($sender, ManagedPlacement::denyKey($text), [
                "plugin" => (string) $text->getManagedBy(),
            ]);
            return;
        }

        //换世界时要先把旧世界里的实体撤掉
        if ($text->getWorldName() !== $worldName) {
            foreach ($this->plugin->getServer()->getOnlinePlayers() as $online) {
                $this->getManager()->despawn($online, $text);
            }
        }

        $text->setPosition($worldName, $position);
        $this->getManager()->update($text);
        //把新位置回写给托管插件，否则它下次刷新会按旧坐标搬回去
        ManagedPlacement::commitMove($text, $worldName, $position);
        $this->success($sender, "move_success", [
            "id" => $text->getId(),
            "world" => $worldName,
            "x" => sprintf("%.2f", $position->x),
            "y" => sprintf("%.2f", $position->y),
            "z" => sprintf("%.2f", $position->z),
        ]);
    }
}