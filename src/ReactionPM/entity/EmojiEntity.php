<?php

declare(strict_types=1);

namespace ReactionPM\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\player\Player;
use ReactionPM\EmojiRegistry;

class EmojiEntity extends Entity {

    /** Offset above the player's head */
    private const HEAD_OFFSET_Y = 2.3;

    /** Default lifetime in ticks (3 seconds = 60 ticks) */
    private const DEFAULT_LIFETIME_TICKS = 60;

    private int $emojiVariant = 0;
    private string $emojiName = '';
    private ?int $ownerRuntimeId = null;
    private int $maxLifetimeTicks;

    public static function getNetworkTypeId(): string {
        return 'reactionpm:emoji';
    }

    public function __construct(Location $location, CompoundTag $nbt, int $emojiVariant = 0, string $emojiName = '', ?Player $owner = null, int $lifetimeTicks = self::DEFAULT_LIFETIME_TICKS) {
        $this->emojiVariant = $emojiVariant;
        $this->emojiName = $emojiName;
        $this->ownerRuntimeId = $owner?->getId();
        $this->maxLifetimeTicks = $lifetimeTicks;
        parent::__construct($location, $nbt);
    }

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo(0.01, 0.01);
    }

    protected function getInitialDragMultiplier(): float {
        return 0.0;
    }

    protected function getInitialGravity(): float {
        return 0.0;
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);
        $this->setHasGravity(false);
        $this->setNoClientPredictions(true);
        $this->setScale(0.001);

        // Use nametag with emoji glyph as the visual display
        $glyph = EmojiRegistry::getGlyph($this->emojiName);
        if ($glyph !== null) {
            $this->setNameTag($glyph);
            $this->setNameTagVisible(true);
            $this->setNameTagAlwaysVisible(true);
        }
    }

    protected function syncNetworkData(EntityMetadataCollection $properties): void {
        parent::syncNetworkData($properties);
        $properties->setInt(EntityMetadataProperties::VARIANT, $this->emojiVariant);
        $properties->setGenericFlag(EntityMetadataFlags::NO_AI, true);
        $properties->setGenericFlag(EntityMetadataFlags::SILENT, true);
    }

    protected function entityBaseTick(int $tickDiff = 1): bool {
        $hasUpdate = parent::entityBaseTick($tickDiff);

        // Despawn after lifetime expires
        if ($this->ticksLived >= $this->maxLifetimeTicks) {
            $this->flagForDespawn();
            return true;
        }

        // Follow the owner player
        if ($this->ownerRuntimeId !== null) {
            $owner = $this->getWorld()->getEntity($this->ownerRuntimeId);
            if ($owner instanceof Player && $owner->isOnline()) {
                $ownerPos = $owner->getPosition();
                $newPos = new Vector3(
                    $ownerPos->x,
                    $ownerPos->y + self::HEAD_OFFSET_Y,
                    $ownerPos->z
                );

                // Use teleport only when position actually changed
                $current = $this->getPosition();
                if ($current->distanceSquared($newPos) > 0.0001) {
                    $this->teleport($newPos);
                }
                return true;
            } else {
                $this->flagForDespawn();
                return true;
            }
        }

        return $hasUpdate;
    }

    public function getEmojiVariant(): int {
        return $this->emojiVariant;
    }

    public function canBeCollidedWith(): bool {
        return false;
    }

    public function canBeMovedByCurrents(): bool {
        return false;
    }
}
