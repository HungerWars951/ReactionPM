<?php

declare(strict_types=1);

namespace ReactionPM\form;

use pocketmine\player\Player;
use pocketmine\form\Form;
use ReactionPM\EmojiRegistry;
use ReactionPM\EmojiSpawner;
use ReactionPM\Main;

class EmojiForm implements Form {

    private const EMOJIS_PER_PAGE = 30;

    private readonly int $totalPages;
    /** @var string[] */
    private readonly array $pageEmojis;

    public function __construct(
        private readonly Main $plugin,
        private readonly int  $page = 0
    ) {
        $allNames = EmojiRegistry::getAllNames();
        $this->totalPages = (int) ceil(count($allNames) / self::EMOJIS_PER_PAGE);
        $offset = $this->page * self::EMOJIS_PER_PAGE;
        $this->pageEmojis = array_slice($allNames, $offset, self::EMOJIS_PER_PAGE);
    }

    public function handleResponse(Player $player, $data): void {
        if ($data === null) {
            return;
        }

        $buttonIndex = (int) $data;

        // Check navigation buttons
        $emojiCount = count($this->pageEmojis);
        $navOffset = $emojiCount;

        // Previous page button
        if ($this->page > 0 && $buttonIndex === $navOffset) {
            $player->sendForm(new self($this->plugin, $this->page - 1));
            return;
        }
        if ($this->page > 0) {
            $navOffset++;
        }

        // Next page button
        if ($this->page < $this->totalPages - 1 && $buttonIndex === $navOffset) {
            $player->sendForm(new self($this->plugin, $this->page + 1));
            return;
        }

        // Emoji button
        if ($buttonIndex < 0 || $buttonIndex >= $emojiCount) {
            return;
        }

        $emojiName = $this->pageEmojis[$buttonIndex];
        $glyph = EmojiRegistry::getGlyph($emojiName);
        if ($glyph === null) {
            return;
        }

        // Broadcast the reaction
        if ($this->plugin->getConfig()->get('broadcast-reactions', true)) {
            $this->plugin->getServer()->broadcastMessage(
                "\u00a77[\u00a7eReaction\u00a77] \u00a7f{$player->getName()} \u00a77reacted: {$glyph}"
            );
        }

        // Spawn emoji entity and particle
        EmojiSpawner::spawn($player, $emojiName, $this->plugin);
    }

    public function jsonSerialize(): mixed {
        $buttons = [];
        foreach ($this->pageEmojis as $name) {
            $glyph = EmojiRegistry::getGlyph($name);
            $displayName = str_replace('_', ' ', $name);
            $label = $glyph !== null ? "{$glyph} {$displayName}" : $displayName;
            $buttons[] = ['text' => $label];
        }

        // Navigation buttons
        if ($this->page > 0) {
            $buttons[] = ['text' => '\u00a7l<< Previous Page'];
        }
        if ($this->page < $this->totalPages - 1) {
            $buttons[] = ['text' => '\u00a7lNext Page >>'];
        }

        $pageDisplay = ($this->page + 1) . '/' . $this->totalPages;
        return [
            'type' => 'form',
            'title' => "\u00a7l\u00a76Emoji Reactions \u00a7r\u00a77({$pageDisplay})",
            'content' => '\u00a77Select an emoji to react with:',
            'buttons' => $buttons,
        ];
    }
}
