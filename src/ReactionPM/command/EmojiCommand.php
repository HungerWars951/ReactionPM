<?php

declare(strict_types=1);

namespace ReactionPM\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ReactionPM\form\EmojiForm;
use ReactionPM\Main;

class EmojiCommand extends Command {

    public function __construct(
        private readonly Main $plugin
    ) {
        parent::__construct('emoji', 'Open the emoji selector', '/emoji');
        $this->setPermission('reactionpm.command.emoji');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage('\u00a7cThis command can only be used in-game.');
            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }

        $sender->sendForm(new EmojiForm($this->plugin));
    }
}
