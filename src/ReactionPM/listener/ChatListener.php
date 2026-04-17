<?php

declare(strict_types=1);

namespace ReactionPM\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use ReactionPM\EmojiRegistry;
use ReactionPM\EmojiSpawner;
use ReactionPM\Main;

class ChatListener implements Listener {

    /** @var array<string, float> playerName => cooldown expiry timestamp */
    private array $cooldowns = [];

    public function __construct(
        private readonly Main $plugin
    ) {}

    /**
     * @priority NORMAL
     */
    public function onChat(PlayerChatEvent $event): void {
        $player = $event->getPlayer();
        if (!$player->hasPermission('reactionpm.chat.emoji')) {
            return;
        }

        $message = $event->getMessage();
        $maxEmojis = $this->plugin->getConfig()->get('max-emojis-per-message', 5);

        // Try :emoji_name: pattern first
        $result = EmojiRegistry::transformMessage($message, $maxEmojis);

        // If no :emoji: found, try emoticon matching (only for standalone messages)
        if ($result === null && $this->plugin->getConfig()->get('emoticons-enabled', true)) {
            $result = EmojiRegistry::matchEmoticon($message);
        }

        if ($result === null) {
            return;
        }

        // Check cooldown
        $cooldownSeconds = (float) $this->plugin->getConfig()->get('cooldown', 2);
        $now = microtime(true);
        $playerName = $player->getName();
        if (isset($this->cooldowns[$playerName]) && $now < $this->cooldowns[$playerName]) {
            $event->cancel();
            $remaining = ceil($this->cooldowns[$playerName] - $now);
            $player->sendMessage("\u00a7cPlease wait {$remaining}s before sending another reaction.");
            return;
        }
        $this->cooldowns[$playerName] = $now + $cooldownSeconds;

        // Replace the message text with glyph-substituted version
        $event->setMessage($result['text']);

        // Spawn emoji entities and particles above the player
        $spawned = [];
        foreach ($result['emojis'] as $emojiName) {
            if (isset($spawned[$emojiName])) {
                continue;
            }
            $spawned[$emojiName] = true;
            EmojiSpawner::spawn($player, $emojiName, $this->plugin);
        }
    }

    public function clearCooldown(string $playerName): void {
        unset($this->cooldowns[$playerName]);
    }
}
