<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

/**
 * 文本内容编辑入口：按行列出，点哪行改哪行
 *
 * 修改/插入/删除都按行来，和 /mebft line 子指令的能力对齐。
 */
final class EditContentForm
{
    public static function open(Main $plugin, Player $player, string $id): void
    {
        $lang = $plugin->getLang();
        $text = FormHelper::requireEditable($plugin, $player, $id);
        if ($text === null) {
            return;
        }

        $lines = $text->getLines();
        //按钮的label就是它对应的行号，回调里直接取，不用按下标反推
        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $id): void {
            if ($data === null) {
                return;
            }
            $action = (string) $data;
            if ($action === "!back") {
                DetailForm::open($plugin, $player, $id);
                return;
            }
            if ($action === "!add") {
                EditLineForm::openAppend($plugin, $player, $id);
                return;
            }
            EditLineForm::open($plugin, $player, $id, (int) $action);
        });

        $form->setTitle($lang->get("gui_edit_title", ["id" => $id]));
        $form->setContent($lang->get("gui_edit_pick_line", ["count" => count($lines)]));

        foreach ($lines as $index => $line) {
            //按钮上带一段无颜色码的预览，带§的话按钮文字会花掉
            $preview = TextFormat::clean((string) $line);
            if ($preview === "") {
                $preview = $lang->get("gui_line_blank");
            } elseif (mb_strlen($preview, "UTF-8") > 20) {
                $preview = mb_substr($preview, 0, 20, "UTF-8") . "...";
            }
            $form->addButton(
                $lang->get("gui_line_button", ["index" => $index + 1, "text" => $preview]),
                0,
                "textures/ui/book_edit_default",
                (string) $index
            );
        }
        $form->addButton($lang->get("gui_btn_line_add"), 0, "textures/ui/color_plus", "!add");
        $form->addButton($lang->get("gui_btn_back"), 0, "textures/ui/arrow_left", "!back");

        $player->sendForm($form);
    }
}
