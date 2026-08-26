<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Render\TextRenderer;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * 单条浮空字的详情与管理
 */
final class DetailForm
{
    public static function open(Main $plugin, Player $player, string $id): void
    {
        $lang = $plugin->getLang();
        $text = $plugin->getManager()->get($id);
        if ($text === null) {
            FormHelper::error($plugin, $player, $lang->get("not_exist", ["id" => $id]));
            return;
        }
        $isOp = $player->hasPermission("MEBFloatingText.op");

        //只读用户只能看详情和返回
        $actions = $isOp
            ? ["content", "style", "move", "toggle", "delete", "back"]
            : ["back"];

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $id, $actions): void {
            if ($data === null) {
                return;
            }
            match ($actions[$data] ?? null) {
                "content" => EditContentForm::open($plugin, $player, $id),
                "style" => StyleForm::open($plugin, $player, $id),
                "move" => self::moveHere($plugin, $player, $id),
                "toggle" => self::toggle($plugin, $player, $id),
                "delete" => DeleteConfirmForm::open($plugin, $player, $id),
                "back" => ListForm::open($plugin, $player),
                default => null,
            };
        });

        $position = $text->getPosition();
        $width = TextRenderer::measure($text->getLines(), $text->getMaxWidth());
        $content = $lang->get("info_world", ["world" => $text->getWorldName()]) . "\n"
            . $lang->get("info_position", [
                "x" => sprintf("%.2f", $position->x),
                "y" => sprintf("%.2f", $position->y),
                "z" => sprintf("%.2f", $position->z),
            ]) . "\n"
            . $lang->get("info_align", ["align" => self::alignName($plugin, $text->getAlign())]) . "\n"
            . $lang->get("info_max_width", [
                "width" => $text->getMaxWidth() > 0
                    ? $text->getMaxWidth() . $lang->get("pixels")
                    : $lang->get("unlimited"),
            ]) . "\n"
            . $lang->get("info_real_width", ["width" => $width]) . "\n"
            . $lang->get("info_spacing", ["spacing" => $text->getLineSpacing()]) . "\n"
            . $lang->get("info_state", [
                "state" => $text->isVisible()
                    ? $lang->get("info_state_shown")
                    : $lang->get("info_state_hidden"),
            ]) . "\n\n"
            . $lang->get("info_content", ["count" => $text->getLineCount()]) . "\n";
        foreach ($text->getLines() as $index => $line) {
            $content .= $lang->get("info_content_line", ["index" => $index + 1, "text" => $line]) . "\n";
        }

        $form->setTitle($lang->get("gui_detail_title", ["id" => $text->getId()]));
        $form->setContent($content);

        $buttons = [
            "content" => ["gui_btn_edit_content", "textures/ui/book_edit_default"],
            "style" => ["gui_btn_edit_style", "textures/ui/gear"],
            "move" => ["gui_btn_move_here", "textures/ui/move"],
            "toggle" => ["gui_btn_toggle", "textures/ui/visibility"],
            "delete" => ["gui_btn_delete", "textures/ui/trash"],
            "back" => ["gui_btn_back", "textures/ui/arrow_left"],
        ];
        foreach ($actions as $action) {
            [$key, $icon] = $buttons[$action];
            $form->addButton($lang->get($key), 0, $icon);
        }

        $player->sendForm($form);
    }

    private static function alignName(Main $plugin, \MengBao\MEBFloatingText\Render\TextAlign $align): string
    {
        return $plugin->getLang()->get("align_" . $align->value);
    }

    private static function moveHere(Main $plugin, Player $player, string $id): void
    {
        $text = $plugin->getManager()->get($id);
        if ($text === null) {
            FormHelper::error($plugin, $player, $plugin->getLang()->get("not_exist", ["id" => $id]));
            return;
        }
        $worldName = $player->getWorld()->getFolderName();
        //跨世界时先撤掉旧世界里的实体，否则那边会留下一份
        if ($text->getWorldName() !== $worldName) {
            foreach ($plugin->getServer()->getOnlinePlayers() as $online) {
                $plugin->getManager()->despawn($online, $text);
            }
        }
        $position = $player->getPosition()->add(0, $plugin->getSpawnOffset(), 0);
        $text->setPosition($worldName, $position);
        $plugin->getManager()->update($text);

        FormHelper::success($plugin, $player, $plugin->getLang()->get("move_success", [
            "id" => $id,
            "world" => $worldName,
            "x" => sprintf("%.2f", $position->x),
            "y" => sprintf("%.2f", $position->y),
            "z" => sprintf("%.2f", $position->z),
        ]));
        self::open($plugin, $player, $id);
    }

    private static function toggle(Main $plugin, Player $player, string $id): void
    {
        $text = $plugin->getManager()->get($id);
        if ($text === null) {
            FormHelper::error($plugin, $player, $plugin->getLang()->get("not_exist", ["id" => $id]));
            return;
        }
        $text->setVisible(!$text->isVisible());
        $plugin->getManager()->update($text);
        FormHelper::success($plugin, $player, $plugin->getLang()->get(
            $text->isVisible() ? "visible_on" : "visible_off",
            ["id" => $id]
        ));
        self::open($plugin, $player, $id);
    }
}