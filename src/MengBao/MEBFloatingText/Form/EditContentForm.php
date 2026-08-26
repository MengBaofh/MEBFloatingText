<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\CustomForm;
use pocketmine\player\Player;

/**
 * 编辑文本内容
 */
final class EditContentForm
{
    public static function open(Main $plugin, Player $player, string $id): void
    {
        $lang = $plugin->getLang();
        $text = $plugin->getManager()->get($id);
        if ($text === null) {
            FormHelper::error($plugin, $player, $lang->get("not_exist", ["id" => $id]));
            return;
        }

        $form = new CustomForm(function (Player $player, $data) use ($plugin, $lang, $id): void {
            if ($data === null) {
                return;
            }
            //回调触发时可能已经被别人删掉了，重新取一次
            $text = $plugin->getManager()->get($id);
            if ($text === null) {
                FormHelper::error($plugin, $player, $lang->get("not_exist", ["id" => $id]));
                return;
            }
            $lines = FormHelper::parseLines((string) $data[0]);
            if ($lines === []) {
                FormHelper::error($plugin, $player, $lang->get("gui_content_empty"));
                return;
            }
            $text->setLines($lines);
            $plugin->getManager()->update($text);
            FormHelper::success($plugin, $player, $lang->get("gui_edit_success", ["id" => $id]));
            DetailForm::open($plugin, $player, $id);
        });

        $form->setTitle($lang->get("gui_edit_title", ["id" => $id]));
        //把现有内容回填成一行，玩家可以直接改
        $form->addInput(
            $lang->get("gui_edit_content"),
            $lang->get("gui_edit_content_ph"),
            FormHelper::joinLines($text->getLines())
        );
        $form->addLabel($lang->get("gui_edit_tip"));
        $player->sendForm($form);
    }
}