<?php

declare(strict_types=1);

namespace ReactionPM;

use customiesdevs\customies\entity\CustomiesEntityFactory;
use customiesdevs\customies\item\CustomiesItemFactory;
use pocketmine\entity\EntityDataHelper;
use pocketmine\inventory\CreativeCategory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use ReactionPM\command\ReactionCommand;
use ReactionPM\command\EmojiCommand;
use ReactionPM\entity\EmojiEntity;
use ReactionPM\item\EmojiWand;
use ReactionPM\listener\ChatListener;

class Main extends PluginBase {
    use SingletonTrait;

    protected function onLoad(): void {
        self::setInstance($this);
    }

    protected function onEnable(): void {
        $this->saveDefaultConfig();

        // Initialize emoji registry
        EmojiRegistry::init();

        // Register custom entity with armour stand behaviour for client rendering
        CustomiesEntityFactory::getInstance()->registerEntity(
            EmojiEntity::class,
            'reactionpm:emoji',
            static function (World $world, CompoundTag $nbt): EmojiEntity {
                return new EmojiEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
            },
            'minecraft:armor_stand'
        );

        // Register event listeners
        $this->getServer()->getPluginManager()->registerEvents(
            new ChatListener($this),
            $this
        );

        // Register commands
        $this->getServer()->getCommandMap()->registerAll('reactionpm', [
            new ReactionCommand($this),
            new EmojiCommand($this),
        ]);

        // Register Customies items
        if ($this->getConfig()->get('emoji-wand-enabled', true)) {
            $this->registerCustomItems();
        }

        $this->getLogger()->info("ReactionPM enabled \u2014 {$this->countEmojis()} emojis loaded.");
    }

    private function registerCustomItems(): void {
        CustomiesItemFactory::getInstance()->registerItem(
            EmojiWand::class,
            'reactionpm:emoji_wand',
            'Emoji Wand',
            CreativeCategory::ITEMS
        );
    }

    private function countEmojis(): int {
        return count(EmojiRegistry::getAllNames());
    }
}
