<?php

declare(strict_types=1);

namespace ReactionPM\item;

use customiesdevs\customies\item\CreativeInventoryInfo;
use customiesdevs\customies\item\ItemComponents;
use customiesdevs\customies\item\ItemComponentsTrait;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemUseResult;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use ReactionPM\form\EmojiForm;
use ReactionPM\Main;

class EmojiWand extends Item implements ItemComponents {
    use ItemComponentsTrait;

    public function __construct(ItemIdentifier $identifier, string $name = "Emoji Wand") {
        parent::__construct($identifier, $name);
        $this->initComponent(
            "reactionpm_emoji_wand",
            new CreativeInventoryInfo(CreativeInventoryInfo::CATEGORY_ITEMS, CreativeInventoryInfo::NONE)
        );
    }

    public function getMaxStackSize(): int {
        return 1;
    }

    public function onClickAir(Player $player, Vector3 $directionVector, array &$returnedItems): ItemUseResult {
        $plugin = Main::getInstance();
        if ($plugin !== null && $player->hasPermission('reactionpm.item.wand')) {
            $player->sendForm(new EmojiForm($plugin));
        }
        return ItemUseResult::SUCCESS;
    }
}
