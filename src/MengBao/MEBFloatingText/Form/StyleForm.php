<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Render\TextAlign;
use MengBao\MEBForms\CustomForm;
use pocketmine\player\Player;

/**
 * 调整排版样式(对齐、折行宽度、行间距、显示状态)
 */
final class StyleForm
{
    public static function open(Main $plugin, Player $player, string $id): void
    {
        $lang = $plugin->getLang();
        $text = FormHelper::requireManageable($plugin, $player, $id);
        if ($text === null) {
            return;
        }
        $aligns = FormHelper::alignOptions();

        $form = new CustomForm(function (Player $player, $data) use ($plugin, $lang, $id, $aligns): void {
            if ($data === null) {
                return;
            }
            $text = FormHelper::requireManageable($plugin, $player, $id);
            if ($text === null) {
                return;
            }
            $text->setAlign(TextAlign::tryParse($aligns[(int) $data[0]] ?? null) ?? TextAlign::CENTER);
            $text->setMaxWidth((int) round((float) $data[1]));
            $text->setLineSpacing((float) $data[2]);
            $text->setVisible((bool) $data[3]);
            $plugin->getManager()->update($text);

            FormHelper::success($plugin, $player, $lang->get("gui_style_success", ["id" => $id]));
            DetailForm::open($plugin, $player, $id);
        });

        $form->setTitle($lang->get("gui_style_title", ["id" => $id]));
        $form->addDropdown(
            $lang->get("gui_create_align"),
            FormHelper::alignLabels($plugin),
            FormHelper::alignIndex($text->getAlign())
        );
        //步长20，滑块只会产生0或不小于20的值，避开1-15这段无效宽度
        $form->addSlider($lang->get("gui_create_width"), 0, 320, 20, (float) $text->getMaxWidth());
        $form->addSlider($lang->get("gui_create_spacing"), 0.05, 2.0, 0.05, $text->getLineSpacing());
        $form->addToggle($lang->get("gui_btn_toggle"), $text->isVisible());
        $player->sendForm($form);
    }
}