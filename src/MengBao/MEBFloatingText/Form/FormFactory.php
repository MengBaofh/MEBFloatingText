<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use pocketmine\player\Player;

/**
 * GUI入口与可用性判断
 *
 * MEBForms是softdepend，没装的时候插件仍然能用指令，
 * 所以每次打开界面之前都要先确认表单类存在。
 */
final class FormFactory
{
    public function __construct(
        private readonly Main $plugin,
    ) {
    }

    /**
     * MEBForms是否可用
     */
    public function isAvailable(): bool
    {
        return class_exists(\MengBao\MEBForms\SimpleForm::class);
    }

    /**
     * 打开主菜单，不可用时给出提示
     */
    public function openMain(Player $player): void
    {
        if (!$this->isAvailable()) {
            $player->sendMessage($this->plugin->getPrefix() . $this->plugin->getLang()->get("gui_unavailable"));
            return;
        }
        MainMenuForm::open($this->plugin, $player);
    }
}