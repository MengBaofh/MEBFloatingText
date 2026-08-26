<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * GUI主菜单
 */
final class MainMenuForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $lang = $plugin->getLang();
        $isOp = $player->hasPermission("MEBFloatingText.op");

        //按钮按权限动态添加，这里先把"位置 => 操作"定下来，
        //再按同样的顺序加按钮，避免回调里索引对不上
        $actions = ["list", "vars"];
        if ($isOp) {
            //创建放在最前面，管理类操作放在最后
            $actions = ["create", "list", "vars", "lang", "reload"];
        }

        $form = new SimpleForm(function (Player $player, $data) use ($plugin, $actions): void {
            if ($data === null) {
                return;
            }
            match ($actions[$data] ?? null) {
                "create" => CreateForm::open($plugin, $player),
                "list" => ListForm::open($plugin, $player),
                "vars" => VarsForm::open($plugin, $player),
                "lang" => LanguageForm::open($plugin, $player),
                "reload" => self::reload($plugin, $player),
                default => null,
            };
        });

        $form->setTitle($lang->get("gui_main_title"));
        $form->setContent($lang->get("gui_main_content", ["count" => $plugin->getManager()->count()]));

        $icons = [
            "create" => "textures/ui/color_plus",
            "list" => "textures/ui/list",
            "vars" => "textures/ui/book_edit_default",
            "lang" => "textures/ui/language_glyph",
            "reload" => "textures/ui/refresh",
        ];
        $labels = [
            "create" => "gui_btn_create",
            "list" => "gui_btn_list",
            "vars" => "gui_btn_vars",
            "lang" => "gui_btn_lang",
            "reload" => "gui_btn_reload",
        ];
        foreach ($actions as $action) {
            $form->addButton($lang->get($labels[$action]), 0, $icons[$action]);
        }

        $player->sendForm($form);
    }

    private static function reload(Main $plugin, Player $player): void
    {
        $plugin->reload();
        $player->sendMessage($plugin->getPrefix() . "§a" . $plugin->getLang()->get(
            "reload_success",
            ["count" => $plugin->getManager()->count()]
        ));
    }
}