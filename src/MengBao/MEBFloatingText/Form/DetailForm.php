<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Render\TextRenderer;
use MengBao\MEBFloatingText\Text\ManagedPlacement;
use MengBao\MEBFloatingText\Text\TextGuard;
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

        //按归属决定按钮，而不是简单看op：
        //有户主的浮空字op也管不了，反过来户主本人是普通玩家也得能管自己的
        $canManage = TextGuard::canManage($plugin, $player, $text);
        //托管中的浮空字内容由插件生成，改了会被下次刷新覆盖，所以不给编辑按钮
        $canEdit = TextGuard::canEditContent($plugin, $player, $text);

        $actions = ["back"];
        if ($canManage) {
            $actions = $canEdit
                ? ["content", "style", "move", "toggle", "delete", "back"]
                : ["style", "move", "toggle", "delete", "back"];
        }

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
            ]) . "\n"
            . $lang->get("info_owner", [
                "owner" => $text->hasOwner() ? (string) $text->getOwner() : $lang->get("owner_none"),
            ]) . "\n"
            . $lang->get("info_managed", [
                "plugin" => $text->isManaged() ? (string) $text->getManagedBy() : $lang->get("owner_unmanaged"),
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
        $text = FormHelper::requireManageable($plugin, $player, $id);
        if ($text === null) {
            return;
        }
        $worldName = $player->getWorld()->getFolderName();
        $position = $player->getPosition()->add(0, $plugin->getSpawnOffset(), 0);
        //托管插件对位置可能有自己的规矩，比如领地浮空字必须留在领地范围内
        if (!ManagedPlacement::canMoveTo($text, $worldName, $position)) {
            FormHelper::error($plugin, $player, $plugin->getLang()->get(
                ManagedPlacement::denyKey($text),
                ["plugin" => (string) $text->getManagedBy()]
            ));
            self::open($plugin, $player, $id);
            return;
        }
        //跨世界时先撤掉旧世界里的实体，否则那边会留下一份
        if ($text->getWorldName() !== $worldName) {
            foreach ($plugin->getServer()->getOnlinePlayers() as $online) {
                $plugin->getManager()->despawn($online, $text);
            }
        }
        $text->setPosition($worldName, $position);
        $plugin->getManager()->update($text);
        //把新位置回写给托管插件，否则它下次刷新会按旧坐标搬回去
        ManagedPlacement::commitMove($text, $worldName, $position);

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
        $text = FormHelper::requireManageable($plugin, $player, $id);
        if ($text === null) {
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