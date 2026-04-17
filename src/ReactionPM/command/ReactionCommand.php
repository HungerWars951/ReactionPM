<?php

declare(strict_types=1);

namespace ReactionPM\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use ReactionPM\EmojiRegistry;
use ReactionPM\EmojiSpawner;
use ReactionPM\Main;

class ReactionCommand extends Command {

    public function __construct(
        private readonly Main $plugin
    ) {
        parent::__construct('react', 'Send an emoji reaction', '/react <emoji>');
        $this->setPermission('reactionpm.command.react');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage('\u00a7cThis command can only be used in-game.');
            return;
        }

        if (!$this->testPermission($sender)) {
            return;
        }

        if (count($args) === 0) {
            $sender->sendMessage('\u00a7eUsage: /react <emoji>');
            $sender->sendMessage('\u00a77Example: /react smile');
            return;
        }

        $emojiName = strtolower($args[0]);
        $emojiName = EmojiRegistry::resolveAlias($emojiName);

        if (!EmojiRegistry::isValidEmoji($emojiName)) {
            $sender->sendMessage("\u00a7cUnknown emoji: \u00a7f{$args[0]}");
            $sender->sendMessage('\u00a77Use \u00a7f/emoji \u00a77to see all available emojis.');
            return;
        }

        $glyph = EmojiRegistry::getGlyph($emojiName);
        if ($glyph === null) {
            $sender->sendMessage('\u00a7cFailed to resolve emoji glyph.');
            return;
        }

        // Broadcast the reaction
        if ($this->plugin->getConfig()->get('broadcast-reactions', true)) {
            $this->plugin->getServer()->broadcastMessage(
                "\u00a77[\u00a7eReaction\u00a77] \u00a7f{$sender->getName()} \u00a77reacted: {$glyph}"
            );
        }

        // Spawn emoji entity and particle
        EmojiSpawner::spawn($sender, $emojiName, $this->plugin);
    }
}
