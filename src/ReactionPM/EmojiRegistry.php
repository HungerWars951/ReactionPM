<?php

declare(strict_types=1);

namespace ReactionPM;

/**
 * Central registry of all emoji names, glyph mappings, aliases, and emoticon rules.
 * Glyph mapping matches the Resource Pack font/glyph_F2.png sprite sheet exactly.
 */
final class EmojiRegistry {

    private const GLYPH_START = 0xF200;

    /** @var string[] Sorted emoji names — order MUST match the RP glyph sprite sheet */
    private const EMOJI_NAMES = [
        'angry', 'anguished', 'astonished', 'blush', 'clouds', 'clown', 'cold',
        'cold_sweat', 'confounded', 'confused', 'cool', 'cowboy', 'diagonal_mouth',
        'disappointed', 'disappointed_relieved', 'disguised', 'dizzy', 'dotted_line',
        'drool', 'exhale', 'explode', 'expressionless', 'fearful', 'flushed', 'frown',
        'grimacing', 'grin', 'grinning', 'hand_over_mouth', 'heart_eyes', 'holding_tears',
        'hot', 'hugging', 'hushed', 'imp', 'innocent', 'joy', 'kissing',
        'kissing_closed_eyes', 'kissing_heart', 'kissing_smiling_eyes', 'laugh', 'lying',
        'mask', 'melt', 'money_mouth', 'monocle', 'nerd', 'neutral', 'no_mouth',
        'open_eyes_hand_mouth', 'open_mouth', 'partying', 'peeking_eye', 'pensive',
        'plead', 'poop', 'rage', 'raised_eyebrow', 'relieved', 'salute', 'scream',
        'shush', 'sick', 'skull', 'sleep', 'sleepy', 'slight_smile', 'smile', 'smiley',
        'smiling_heart', 'smiling_imp', 'smiling_tear', 'smirk', 'sob', 'spiral_eyes',
        'star_struck', 'stuck_out_tongue', 'sweat', 'sweat_smile', 'symbols_over_mouth',
        'thermometer', 'tired', 'tongue_close', 'tongue_wink', 'triumph', 'vomit',
        'weary', 'wink', 'woozy', 'worried', 'yawn', 'zany', 'zipper_mouth',
    ];

    /** @var array<string, string> name aliases */
    private const ALIASES = [
        'smiling' => 'smile',
        'happy' => 'smiley',
        'sad' => 'frown',
        'crying' => 'sob',
        'love' => 'heart_eyes',
        'sunglasses' => 'cool',
        'dead' => 'skull',
        'zzz' => 'sleep',
        'thinking' => 'monocle',
        'devil' => 'smiling_imp',
    ];

    /**
     * Emoticon patterns — order matters (more specific patterns first).
     * @var array{emoji: string, pattern: string}[]
     */
    private const EMOTICON_RULES = [
        ['emoji' => 'smiling_heart', 'pattern' => '/^<3+$/'],
        ['emoji' => 'smiling_imp', 'pattern' => '/^>:[-^]?-?\\)+$/'],
        ['emoji' => 'rage', 'pattern' => '/^>:[-^]?-?\\(+$/'],
        ['emoji' => 'joy', 'pattern' => '/^:\'[-^]?d+$/i'],
        ['emoji' => 'joy', 'pattern' => '/^:\'[-^]?\\)+$/'],
        ['emoji' => 'disappointed_relieved', 'pattern' => '/^:\'[-^]?\\(+$/'],
        ['emoji' => 'sob', 'pattern' => '/^(?:t_t|t\\.t|;_;|;-;|t-t)$/i'],
        ['emoji' => 'laugh', 'pattern' => '/^x[-^]?-?d+$/i'],
        ['emoji' => 'cool', 'pattern' => '/^b[-^]?-?\\)+$/i'],
        ['emoji' => 'scream', 'pattern' => '/^d:$/i'],
        ['emoji' => 'astonished', 'pattern' => '/^(?:o_o|0_0|o\\.o|0\\.0)$/i'],
        ['emoji' => 'expressionless', 'pattern' => '/^(?:-+_+-+|\\._.|\\.-.)/'],
        ['emoji' => 'neutral', 'pattern' => '/^[:;=][-^]?-?\\|+$/'],
        ['emoji' => 'wink', 'pattern' => '/^;[-^]?-?\\)+$/'],
        ['emoji' => 'tongue_wink', 'pattern' => '/^;[-^]?-?p+$/i'],
        ['emoji' => 'stuck_out_tongue', 'pattern' => '/^[:=][-^]?-?p+$/i'],
        ['emoji' => 'kissing', 'pattern' => '/^[:;=][-^]?-?\\*+$/'],
        ['emoji' => 'zipper_mouth', 'pattern' => '/^[:;=][-^]?-?x+$/i'],
        ['emoji' => 'blush', 'pattern' => '/^[:;=][-^]?-?\\$+$/'],
        ['emoji' => 'diagonal_mouth', 'pattern' => '/^[:;=][-^]?-?[\\/\\\\]+$/'],
        ['emoji' => 'confused', 'pattern' => '/^[:;=][-^]?-?s+$/i'],
        ['emoji' => 'open_mouth', 'pattern' => '/^[:;=][-^]?-?o+$/i'],
        ['emoji' => 'grin', 'pattern' => '/^[:;=][-^]?-?d+$/i'],
        ['emoji' => 'smile', 'pattern' => '/^[:;=][-^]?-?\\)+$/'],
        ['emoji' => 'frown', 'pattern' => '/^[:;=][-^]?-?\\(+$/'],
    ];

    /** @var array<string, string> emoji name => Unicode glyph character */
    private static array $glyphMap = [];

    /** @var array<string, bool> fast lookup set */
    private static array $nameSet = [];

    private static bool $initialized = false;

    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        foreach (self::EMOJI_NAMES as $index => $name) {
            self::$glyphMap[$name] = mb_chr(self::GLYPH_START + $index, 'UTF-8');
            self::$nameSet[$name] = true;
        }
        self::$initialized = true;
    }

    public static function isValidEmoji(string $name): bool {
        return isset(self::$nameSet[$name]);
    }

    public static function resolveAlias(string $name): string {
        return self::ALIASES[$name] ?? $name;
    }

    public static function getGlyph(string $name): ?string {
        return self::$glyphMap[$name] ?? null;
    }

    public static function getParticleId(string $name): string {
        return "re:{$name}_emoji";
    }

    /**
     * Get the sorted index of an emoji (used for entity variant selection).
     */
    public static function getIndex(string $name): ?int {
        $index = array_search($name, self::EMOJI_NAMES, true);
        return $index !== false ? $index : null;
    }

    /** @return string[] */
    public static function getAllNames(): array {
        return self::EMOJI_NAMES;
    }

    /**
     * Transform a chat message, replacing :emoji: codes with glyphs.
     * @return array{text: string, emojis: string[]}|null
     */
    public static function transformMessage(string $message, int $maxEmojis = 5): ?array {
        $usedEmojis = [];
        $replaced = preg_replace_callback('/:([a-z][a-z0-9_]*):?/i', function (array $matches) use (&$usedEmojis, $maxEmojis): string {
            if (count($usedEmojis) >= $maxEmojis) {
                return $matches[0];
            }
            $name = strtolower($matches[1]);
            $name = self::resolveAlias($name);
            if (!self::isValidEmoji($name)) {
                return $matches[0];
            }
            $glyph = self::getGlyph($name);
            if ($glyph === null) {
                return $matches[0];
            }
            $usedEmojis[] = $name;
            return $glyph;
        }, $message);

        if (count($usedEmojis) > 0 && $replaced !== null) {
            return ['text' => $replaced, 'emojis' => array_unique($usedEmojis)];
        }
        return null;
    }

    /**
     * Check if a message is a standalone emoticon (e.g. ":)" or "XD").
     * @return array{text: string, emojis: string[]}|null
     */
    public static function matchEmoticon(string $message): ?array {
        $trimmed = trim($message);
        foreach (self::EMOTICON_RULES as $rule) {
            if (!self::isValidEmoji($rule['emoji'])) {
                continue;
            }
            if (preg_match($rule['pattern'], $trimmed)) {
                $glyph = self::getGlyph($rule['emoji']);
                if ($glyph === null) {
                    continue;
                }
                return ['text' => $glyph, 'emojis' => [$rule['emoji']]];
            }
        }
        return null;
    }
}
