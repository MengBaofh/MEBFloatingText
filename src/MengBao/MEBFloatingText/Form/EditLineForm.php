<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\CustomForm;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * 单行编辑：改本行、在本行前后插入、删除本行
 *
 * 每个落库入口都重新取一次浮空字并重判权限，因为表单响应由客户端发来。
 */
final class EditLineForm
{
    /**
     * 某一行的操作菜单
     */
    public static function open(Main $plugin, Player $player, string $id, int $index): void
    {
        $lang = $plugin->getLang();
        $text = FormHelper::requireEditable($plugin, $player, $id);
        if ($text === null) {
            return;
        }
        $lines = $text->getLines();
        if (!isset($lines[$index])) {
            FormHelper::error($plugin, $player, $lang->get("line_out_of_range", ["count" => count($lines)]));
            EditContentForm::open($plugin, $player, $id);
            return;
        }

        $actions = ["edit", "insert_before", "insert_after", "delete", "back"];
        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $id, $index, $actions): void {
            if ($data === null) {
                return;
            }
            match ($actions[$data] ?? null) {
                "edit" => self::openEdit($plugin, $player, $id, $index),
                "insert_before" => self::openInsert($plugin, $player, $id, $index),
                "insert_after" => self::openInsert($plugin, $player, $id, $index + 1),
                "delete" => self::delete($plugin, $player, $id, $index),
                "back" => EditContentForm::open($plugin, $player, $id),
                default => null,
            };
        });

        $form->setTitle($lang->get("gui_line_title", ["index" => $index + 1, "id" => $id]));
        //原文按输入框的写法展示(§换成&)，玩家看到的和待会儿要改的是同一份东西
        $form->setContent($lang->get("gui_line_content", [
            "index" => $index + 1,
            "count" => count($lines),
            "raw" => FormHelper::toInput((string) $lines[$index]),
            "text" => (string) $lines[$index],
        ]));
        $buttons = [
            "edit" => ["gui_btn_line_edit", "textures/ui/book_edit_default"],
            "insert_before" => ["gui_btn_line_insert_before", "textures/ui/color_plus"],
            "insert_after" => ["gui_btn_line_insert_after", "textures/ui/color_plus"],
            "delete" => ["gui_btn_line_delete", "textures/ui/trash"],
            "back" => ["gui_btn_back", "textures/ui/arrow_left"],
        ];
        foreach ($actions as $action) {
            [$key, $icon] = $buttons[$action];
            $form->addButton($lang->get($key), 0, $icon);
        }
        $player->sendForm($form);
    }

    /**
     * 修改某一行的内容
     */
    private static function openEdit(Main $plugin, Player $player, string $id, int $index): void
    {
        $lang = $plugin->getLang();
        $text = FormHelper::requireEditable($plugin, $player, $id);
        if ($text === null) {
            return;
        }
        $lines = $text->getLines();
        if (!isset($lines[$index])) {
            FormHelper::error($plugin, $player, $lang->get("line_out_of_range", ["count" => count($lines)]));
            return;
        }

        $form = new CustomForm(function (Player $player, $data) use ($plugin, $lang, $id, $index): void {
            if ($data === null) {
                return;
            }
            $text = FormHelper::requireEditable($plugin, $player, $id);
            if ($text === null) {
                return;
            }
            $line = FormHelper::parseLine((string) ($data[0] ?? ""));
            if ($line === "") {
                FormHelper::error($plugin, $player, $lang->get("gui_line_empty"));
                return;
            }
            if (!$text->setLine($index, $line)) {
                FormHelper::error($plugin, $player, $lang->get("line_out_of_range", ["count" => $text->getLineCount()]));
                return;
            }
            $plugin->getManager()->update($text);
            FormHelper::success($plugin, $player, $lang->get("line_set", ["line" => $index + 1]));
            EditContentForm::open($plugin, $player, $id);
        });

        $form->setTitle($lang->get("gui_line_edit_title", ["index" => $index + 1]));
        $form->addInput(
            $lang->get("gui_line_label", ["index" => $index + 1]),
            $lang->get("gui_edit_content_ph"),
            FormHelper::toInput((string) $lines[$index])
        );
        $form->addLabel($lang->get("gui_line_tip"));
        $player->sendForm($form);
    }

    /**
     * 在第$index行的位置插入一行($index等于行数时就是追加到末尾)
     */
    private static function openInsert(Main $plugin, Player $player, string $id, int $index): void
    {
        $lang = $plugin->getLang();
        if (FormHelper::requireEditable($plugin, $player, $id) === null) {
            return;
        }

        $form = new CustomForm(function (Player $player, $data) use ($plugin, $lang, $id, $index): void {
            if ($data === null) {
                return;
            }
            $text = FormHelper::requireEditable($plugin, $player, $id);
            if ($text === null) {
                return;
            }
            $line = FormHelper::parseLine((string) ($data[0] ?? ""));
            if ($line === "") {
                FormHelper::error($plugin, $player, $lang->get("gui_line_empty"));
                return;
            }
            if (!$text->insertLine($index, $line)) {
                FormHelper::error($plugin, $player, $lang->get("line_insert_range", ["max" => $text->getLineCount() + 1]));
                return;
            }
            $plugin->getManager()->update($text);
            FormHelper::success($plugin, $player, $lang->get("line_inserted", ["line" => $index + 1]));
            EditContentForm::open($plugin, $player, $id);
        });

        $form->setTitle($lang->get("gui_line_insert_title", ["index" => $index + 1]));
        $form->addInput($lang->get("gui_line_new_label"), $lang->get("gui_edit_content_ph"));
        $form->addLabel($lang->get("gui_line_tip"));
        $player->sendForm($form);
    }

    /**
     * 追加一行到末尾
     */
    public static function openAppend(Main $plugin, Player $player, string $id): void
    {
        $text = FormHelper::requireEditable($plugin, $player, $id);
        if ($text === null) {
            return;
        }
        self::openInsert($plugin, $player, $id, $text->getLineCount());
    }

    /**
     * 删除某一行，至少保留一行
     */
    private static function delete(Main $plugin, Player $player, string $id, int $index): void
    {
        $lang = $plugin->getLang();
        $text = FormHelper::requireEditable($plugin, $player, $id);
        if ($text === null) {
            return;
        }
        if ($text->getLineCount() <= 1) {
            FormHelper::error($plugin, $player, $lang->get("keep_one_line"));
            EditContentForm::open($plugin, $player, $id);
            return;
        }
        if (!$text->removeLine($index)) {
            FormHelper::error($plugin, $player, $lang->get("line_out_of_range", ["count" => $text->getLineCount()]));
            EditContentForm::open($plugin, $player, $id);
            return;
        }
        $plugin->getManager()->update($text);
        FormHelper::success($plugin, $player, $lang->get("line_removed", ["line" => $index + 1]));
        EditContentForm::open($plugin, $player, $id);
    }
}
