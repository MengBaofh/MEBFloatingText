<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\ModalForm;
use pocketmine\player\Player;

/**
 * 删除确认
 *
 * 删除不可撤销，所以单独用一个确认窗口挡一下。
 */
final class DeleteConfirmForm
{
    public static function open(Main $plugin, Player $player, string $id): void
    {
        $lang = $plugin->getLang();
        if (FormHelper::requireManageable($plugin, $player, $id) === null) {
            return;
        }

        $form = new ModalForm(function (Player $player, $data) use ($plugin, $lang, $id): void {
            //关闭窗口等同于选了第二个按钮，都按"不删除"处理
            if ($data !== true) {
                DetailForm::open($plugin, $player, $id);
                return;
            }
            if (FormHelper::requireManageable($plugin, $player, $id) === null) {
                return;
            }
            if (!$plugin->getManager()->remove($id)) {
                FormHelper::error($plugin, $player, $lang->get("not_exist", ["id" => $id]));
                return;
            }
            FormHelper::success($plugin, $player, $lang->get("remove_success", ["id" => $id]));
        });

        $form->setTitle($lang->get("gui_delete_title"));
        $form->setContent($lang->get("gui_delete_content", ["id" => $id]));
        $form->setButton1($lang->get("gui_btn_confirm_delete"));
        $form->setButton2($lang->get("gui_btn_cancel"));
        $player->sendForm($form);
    }
}