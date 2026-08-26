<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Render\TextAlign;
use MengBao\MEBForms\CustomForm;
use pocketmine\player\Player;

/**
 * 创建浮空字
 */
final class CreateForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $lang = $plugin->getLang();
        $aligns = FormHelper::alignOptions();

        $form = new CustomForm(function (Player $player, $data) use ($plugin, $lang, $aligns): void {
            if ($data === null) {
                return;
            }
            //下标顺序要和下面添加元素的顺序一致，label也占一位
            $id = trim((string) $data[0]);
            $content = (string) $data[1];
            $align = TextAlign::tryParse($aligns[(int) $data[2]] ?? null) ?? TextAlign::CENTER;
            $width = (int) round((float) $data[3]);
            $spacing = (float) $data[4];

            $lines = FormHelper::parseLines($content);
            if ($lines === []) {
                FormHelper::error($plugin, $player, $lang->get("gui_content_empty"));
                return;
            }
            if (!preg_match('/^[A-Za-z0-9_\-]{1,32}$/', $id)) {
                FormHelper::error($plugin, $player, $lang->get("id_invalid"));
                return;
            }
            if ($plugin->getManager()->exists($id)) {
                FormHelper::error($plugin, $player, $lang->get("already_exists", ["id" => $id]));
                return;
            }
            $limit = $plugin->getMaxTexts();
            if ($limit > 0 && $plugin->getManager()->count() >= $limit) {
                FormHelper::error($plugin, $player, $lang->get("count_limit", ["limit" => $limit]));
                return;
            }

            $position = $player->getPosition()->add(0, $plugin->getSpawnOffset(), 0);
            $text = $plugin->getManager()->create($id, $player->getWorld()->getFolderName(), $position, $lines);
            if ($text === null) {
                FormHelper::error($plugin, $player, $lang->get("create_failed"));
                return;
            }
            //创建时用的是配置里的默认值，这里再按玩家选的覆盖一次
            $text->setAlign($align);
            $text->setMaxWidth($width);
            $text->setLineSpacing($spacing);
            $plugin->getManager()->update($text);

            FormHelper::success($plugin, $player, $lang->get("create_success", [
                "id" => $id,
                "count" => $text->getLineCount(),
            ]));
        });

        $form->setTitle($lang->get("gui_create_title"));
        $form->addInput($lang->get("gui_create_id"), $lang->get("gui_create_id_ph"));
        $form->addInput($lang->get("gui_create_content"), $lang->get("gui_create_content_ph"));
        $form->addDropdown($lang->get("gui_create_align"), FormHelper::alignLabels($plugin), FormHelper::alignIndex(
            TextAlign::tryParse((string) $plugin->getSettings()->get("默认对齐方式", "center")) ?? TextAlign::CENTER
        ));
        //步长20保证滑块只会给出0或不小于20的值，绕开1-15这段无效区间
        $form->addSlider($lang->get("gui_create_width"), 0, 320, 20, (float) $plugin->getSettings()->get("默认最大宽度", 0));
        $form->addSlider($lang->get("gui_create_spacing"), 0.05, 2.0, 0.05, (float) $plugin->getSettings()->get("默认行间距", 0.28));
        $form->addLabel($lang->get("gui_create_tip"));
        $player->sendForm($form);
    }
}