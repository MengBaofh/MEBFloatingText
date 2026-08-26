<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * 切换语言
 */
final class LanguageForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $lang = $plugin->getLang();
        $available = $lang->getAvailable();

        $form = new SimpleForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                return;
            }
            if ($data === "!back") {
                MainMenuForm::open($plugin, $player);
                return;
            }
            $plugin->switchLanguage((string) $data);
            //切换后用新语言提示，顺便让玩家看到界面已经变了
            FormHelper::success($plugin, $player, $plugin->getLang()->get("lang_switched", ["lang" => (string) $data]));
            MainMenuForm::open($plugin, $player);
        });

        $form->setTitle($lang->get("gui_lang_title"));
        $form->setContent($lang->get("gui_lang_content"));
        foreach ($available as $code) {
            //当前语言前面加个标记，让玩家知道现在用的是哪个
            $mark = $code === $lang->getCurrent() ? "§a> " : "§7";
            $form->addButton($mark . $code, 0, "textures/ui/language_glyph", $code);
        }
        $form->addButton($lang->get("gui_btn_back"), 0, "textures/ui/arrow_left", "!back");
        $player->sendForm($form);
    }
}