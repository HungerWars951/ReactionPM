<?php

declare(strict_types=1);

namespace ReactionPM;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;

final class EmojiSpawner {

    private const HEAD_OFFSET_Y = 2.3;

    private function __construct() {}

    /**
     * Spawn a floating emoji above a player's head using packet-level entity.
     */
    public static function spawn(Player $player, string $emojiName, Main $plugin): void {
        $glyph = EmojiRegistry::getGlyph($emojiName);
        if ($glyph === null) {
            return;
        }

        $config = $plugin->getConfig();
        $lifetimeSeconds = (int) $config->get('particle-lifetime', 3);
        $pos = $player->getPosition();

        // Create a packet-level floating entity (same technique as FloatingTextParticle)
        $entityId = Entity::nextRuntimeId();
        $spawnPos = new Vector3($pos->x, $pos->y + self::HEAD_OFFSET_Y, $pos->z);

        $actorFlags = (1 << EntityMetadataFlags::NO_AI) | (1 << EntityMetadataFlags::SILENT);
        $actorMetadata = [
            EntityMetadataProperties::FLAGS => new LongMetadataProperty($actorFlags),
            EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01),
            EntityMetadataProperties::BOUNDING_BOX_WIDTH => new FloatMetadataProperty(0.0),
            EntityMetadataProperties::BOUNDING_BOX_HEIGHT => new FloatMetadataProperty(0.0),
            EntityMetadataProperties::NAMETAG => new StringMetadataProperty($glyph),
            EntityMetadataProperties::VARIANT => new IntMetadataProperty(
                TypeConverter::getInstance()->getBlockTranslator()->internalIdToNetworkId(
                    VanillaBlocks::AIR()->getStateId()
                )
            ),
            EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => new ByteMetadataProperty(1),
        ];

        $spawnPacket = AddActorPacket::create(
            $entityId,
            $entityId,
            EntityIds::FALLING_BLOCK,
            $spawnPos,
            null,
            0, 0, 0, 0,
            [],
            $actorMetadata,
            new PropertySyncData([], []),
            []
        );

        // Send spawn packet to all players in the world
        $world = $player->getWorld();
        foreach ($world->getPlayers() as $viewer) {
            $viewer->getNetworkSession()->sendDataPacket($spawnPacket);
        }

        // Schedule position updates to follow the player
        $tickCount = 0;
        $maxTicks = $lifetimeSeconds * 20;
        $playerId = $player->getId();

        $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function () use ($plugin, $entityId, $playerId, &$tickCount, $maxTicks): void {
                $tickCount++;

                $server = $plugin->getServer();
                $player = null;
                foreach ($server->getOnlinePlayers() as $p) {
                    if ($p->getId() === $playerId) {
                        $player = $p;
                        break;
                    }
                }

                // Remove if lifetime expired or player left
                if ($tickCount >= $maxTicks || $player === null) {
                    $removePacket = RemoveActorPacket::create($entityId);
                    foreach ($server->getOnlinePlayers() as $viewer) {
                        $viewer->getNetworkSession()->sendDataPacket($removePacket);
                    }
                    throw new \pocketmine\scheduler\CancelTaskException();
                }

                // Move entity to follow player (every 2 ticks to reduce packets)
                if ($tickCount % 2 === 0) {
                    $pos = $player->getPosition();
                    $movePacket = MoveActorAbsolutePacket::create(
                        $entityId,
                        new Vector3($pos->x, $pos->y + self::HEAD_OFFSET_Y, $pos->z),
                        0.0, 0.0, 0.0,
                        MoveActorAbsolutePacket::FLAG_TELEPORT
                    );
                    foreach ($player->getWorld()->getPlayers() as $viewer) {
                        $viewer->getNetworkSession()->sendDataPacket($movePacket);
                    }
                }
            }
        ), 1);

        // Also spawn particle effect (floats upward)
        if ($config->get('particles-enabled', true)) {
            $particlePos = new Vector3($pos->x, $pos->y + 2.2, $pos->z);
            $particleId = EmojiRegistry::getParticleId($emojiName);

            $pk = SpawnParticleEffectPacket::create(
                DimensionIds::OVERWORLD,
                $player->getId(),
                $particlePos,
                $particleId,
                null
            );

            foreach ($world->getPlayers() as $viewer) {
                $viewer->getNetworkSession()->sendDataPacket($pk);
            }
        }
    }
}
