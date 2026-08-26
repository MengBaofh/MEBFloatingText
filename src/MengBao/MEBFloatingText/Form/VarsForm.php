<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Form;

use MengBao\MEBFloatingText\Main;
use MengBao\MEBFloatingText\Text\PlaceholderResolver;
use MengBao\MEBForms\SimpleForm;
use pocketmine\player\Player;

/**
 * 变量说明
 */
final class VarsForm
{
    public static function open(Main $plugin, Player $player): void
    {
        $lang = $plugin->getLang();

        $form = new SimpleForm(function (Player $player, $data) use ($plugin): void {
            if ($data === null) {
                return;
            }
            MainMenuForm::open($plugin, $player);
        });

        $content = "";
        foreach (PlaceholderResolver::getDescriptionKeys() as $name => $langKey) {
            $content .= "§e" . $name . " §7- " . $lang->get($langKey) . "\n";
        }
        $content .= "\n" . $lang->get("vars_tip");

        $form->setTitle($lang->get("gui_vars_title"));
        $form->setContent($content);
        $form->addButton($lang->get("gui_btn_back"), 0, "textures/ui/arrow_left");
        $player->sendForm($form);
    }
}