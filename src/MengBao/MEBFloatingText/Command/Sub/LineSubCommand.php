<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command\Sub;

use MengBao\MEBFloatingText\Command\SubCommand;
use pocketmine\command\CommandSender;

/**
 * 行编辑，把增删改行合并到一个子指令下面
 */
final class LineSubCommand extends SubCommand
{
    public function getName(): string
    {
        return "line";
    }

    public function getAliases(): array
    {
        return ["行"];
    }


    public function getDescriptionKey(): string
    {
        return "desc_line";
    }

    public function execute(CommandSender $sender, array $args): void
    {
        if (count($args) < 2) {
            $this->sendUsage($sender);
            return;
        }
        $text = $this->requireText($sender, $args[0]);
        if ($text === null) {
            return;
        }

        switch (strtolower($args[1])) {
            case "add":
            case "追加":
                if (count($args) < 3) {
                    $this->error($sender, "line_usage_add");
                    return;
                }
                $text->addLine($this->joinText(array_slice($args, 2)));
                $this->getManager()->update($text);
                $this->success($sender, "line_added", ["line" => $text->getLineCount()]);
                return;

            case "set":
            case "修改":
                if (count($args) < 4) {
                    $this->error($sender, "line_usage_set");
                    return;
                }
                $index = $this->parseLineIndex($sender, $text, $args[2]);
                if ($index === null) {
                    return;
                }
                $text->setLine($index, $this->joinText(array_slice($args, 3)));
                $this->getManager()->update($text);
                $this->success($sender, "line_set", ["line" => $index + 1]);
                return;

            case "insert":
            case "插入":
                if (count($args) < 4) {
                    $this->error($sender, "line_usage_insert");
                    return;
                }
                if (!ctype_digit($args[2])) {
                    $this->error($sender, "line_must_int");
                    return;
                }
                //插入允许等于行数+1，表示插到末尾
                $index = (int) $args[2] - 1;
                if (!$text->insertLine($index, $this->joinText(array_slice($args, 3)))) {
                    $this->error($sender, "line_insert_range", ["max" => $text->getLineCount() + 1]);
                    return;
                }
                $this->getManager()->update($text);
                $this->success($sender, "line_inserted", ["line" => $index + 1]);
                return;

            case "del":
            case "delete":
            case "remove":
            case "删除":
                if (count($args) < 3) {
                    $this->error($sender, "line_usage_del");
                    return;
                }
                $index = $this->parseLineIndex($sender, $text, $args[2]);
                if ($index === null) {
                    return;
                }
                if ($text->getLineCount() <= 1) {
                    $this->error($sender, "keep_one_line");
                    return;
                }
                $text->removeLine($index);
                $this->getManager()->update($text);
                $this->success($sender, "line_removed", ["line" => $index + 1]);
                return;

            default:
                $this->sendUsage($sender);
        }
    }
}