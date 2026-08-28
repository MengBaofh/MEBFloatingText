<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * 浮空字列表
 */
final class ListForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $lang = $plugin->getLang();
        $texts = $plugin->getManager()->getAll();
        if ($texts === []) {
            FormHelper::error($plugin, $player, $lang->get("list_empty"));
            return;
        }

        $form = new SimpleForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                return;
            }
            //"!"不在id允许的字符里，不会和真实id撞车
            if ($data === "!back") {
                MainMenuForm::open($plugin, $player);
                return;
            }
            $text = $plugin->getManager()->get((string) $data);
            if ($text === null) {
                //列表打开期间被别人删掉了
                FormHelper::error($plugin, $player, $plugin->getLang()->get("not_exist", ["id" => (string) $data]));
                return;
            }
            DetailForm::open($plugin, $player, (string) $data);
        });

        $form->setTitle($lang->get("gui_list_title"));
        $form->setContent($lang->get("gui_list_content"));

        foreach ($texts as $text) {
            $state = $text->isVisible() ? $lang->get("state_shown") : $lang->get("state_hidden");
            //按钮上带首行预览，去掉颜色代码免得花屏
            $preview = TextFormat::clean($text->getLines()[0] ?? "");
            if (mb_strlen($preview, "UTF-8") > 14) {
                $preview = mb_substr($preview, 0, 14, "UTF-8") . "...";
            }
            $position = $text->getPosition();
            //有户主的浮空字标一下，免得op点进去才发现自己动不了
            $ownerTag = $text->hasOwner() ? " §6" . $text->getOwner() : "";
            $buttonText = "§e" . $text->getId() . " §7[" . $state . "§7]" . $ownerTag . "\n§7"
                . $text->getWorldName() . " ("
                . (int) $position->x . "," . (int) $position->y . "," . (int) $position->z . ") §f" . $preview;
            //用id作为label，回调里直接拿到id，不用再按索引反查
            $form->addButton($buttonText, 0, "textures/ui/sign", $text->getId());
        }
        $form->addButton($lang->get("gui_btn_back"), 0, "textures/ui/arrow_left", "!back");

        $player->sendForm($form);
    }
}