<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Command;

use MengBao\MEBFloatingText\Command\Sub\AlignSubCommand;
use MengBao\MEBFloatingText\Command\Sub\CreateSubCommand;
use MengBao\MEBFloatingText\Command\Sub\GuiSubCommand;
use MengBao\MEBFloatingText\Command\Sub\HelpSubCommand;
use MengBao\MEBFloatingText\Command\Sub\InfoSubCommand;
use MengBao\MEBFloatingText\Command\Sub\LangSubCommand;
use MengBao\MEBFloatingText\Command\Sub\LineSubCommand;
use MengBao\MEBFloatingText\Command\Sub\ListSubCommand;
use MengBao\MEBFloatingText\Command\Sub\MoveSubCommand;
use MengBao\MEBFloatingText\Command\Sub\OwnerSubCommand;
use MengBao\MEBFloatingText\Command\Sub\ReloadSubCommand;
use MengBao\MEBFloatingText\Command\Sub\RemoveSubCommand;
use MengBao\MEBFloatingText\Command\Sub\SpacingSubCommand;
use MengBao\MEBFloatingText\Command\Sub\VarsSubCommand;
use MengBao\MEBFloatingText\Command\Sub\VisibleSubCommand;
use MengBao\MEBFloatingText\Command\Sub\WidthSubCommand;
use MengBao\MEBFloatingText\Main;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

/**
 * 子指令注册与分发
 */
final class CommandRouter
{
    /** @var array<string, SubCommand> 键为子指令名或别名 */
    private array $commands = [];

    /** @var SubCommand[] 用于帮助列表，保持注册顺序且不含别名 */
    private array $ordered = [];

    public function __construct(
        private readonly Main $plugin,
    ) {
        $this->register(new HelpSubCommand($plugin));
        $this->register(new GuiSubCommand($plugin));
        $this->register(new CreateSubCommand($plugin));
        $this->register(new RemoveSubCommand($plugin));
        $this->register(new ListSubCommand($plugin));
        $this->register(new InfoSubCommand($plugin));
        $this->register(new LineSubCommand($plugin));
        $this->register(new AlignSubCommand($plugin));
        $this->register(new WidthSubCommand($plugin));
        $this->register(new SpacingSubCommand($plugin));
        $this->register(new MoveSubCommand($plugin));
        $this->register(new VisibleSubCommand($plugin));
        $this->register(new OwnerSubCommand($plugin));
        $this->register(new VarsSubCommand($plugin));
        $this->register(new LangSubCommand($plugin));
        $this->register(new ReloadSubCommand($plugin));
    }

    private function register(SubCommand $command): void
    {
        $this->ordered[] = $command;
        $this->commands[strtolower($command->getName())] = $command;
        foreach ($command->getAliases() as $alias) {
            $this->commands[strtolower($alias)] = $command;
        }
    }

    /** @return SubCommand[] */
    public function getOrdered(): array
    {
        return $this->ordered;
    }

    /**
     * @param string[] $args
     */
    public function dispatch(CommandSender $sender, array $args): bool
    {
        //玩家不带参数执行时直接开GUI，比刷一屏帮助更直观
        if ($args === [] && $sender instanceof Player && $this->plugin->getFormFactory()->isAvailable()) {
            $this->plugin->getFormFactory()->openMain($sender);
            return true;
        }

        $name = strtolower($args[0] ?? "help");
        $command = $this->commands[$name] ?? null;
        $lang = $this->plugin->getLang();
        if ($command === null) {
            $sender->sendMessage($this->plugin->getPrefix() . $lang->get("unknown_command", ["name" => $name]));
            return true;
        }

        if ($command->isOpOnly() && !$sender->hasPermission("MEBFloatingText.op")) {
            $sender->sendMessage($this->plugin->getPrefix() . $lang->get("no_permission"));
            return true;
        }
        if ($command->isPlayerOnly() && !$sender instanceof Player) {
            $sender->sendMessage($this->plugin->getPrefix() . $lang->get("only_player"));
            return true;
        }

        $command->execute($sender, array_slice($args, 1));
        return true;
    }
}