<?php

declare(strict_types=1);

namespace MengBao\MEBFloatingText\Network;

use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\player\Player;

/**
 * 单行浮空字对应的客户端假实体
 *
 * 这里用的是和核心FloatingTextParticle相同的思路：
 * 一个缩放到0.01、碰撞箱为0的空气falling_block，只保留nametag可见。
 * 每一行文本一个实体，这样才能自由控制行间距，
 * 也能只更新变化了的那一行，避免整块文本重建时的闪烁。
 */
final class TextActor
{
    private static ?int $airNetworkId = null;

    private string $nametag = "";

    private readonly int $actorId;

    public function __construct(
        private Vector3 $position,
    ) {
        $this->actorId = Entity::nextRuntimeId();
    }

    public function getActorId(): int
    {
        return $this->actorId;
    }

    public function getPosition(): Vector3
    {
        return $this->position;
    }

    public function getNametag(): string
    {
        return $this->nametag;
    }

    /**
     * 生成实体并显示文本
     */
    public function spawnTo(Player $player, string $nametag): void
    {
        $this->nametag = $nametag;
        $player->getNetworkSession()->sendDataPacket(AddActorPacket::create(
            $this->actorId,
            $this->actorId,
            EntityIds::FALLING_BLOCK,
            $this->position,
            null,
            0.0,
            0.0,
            0.0,
            0.0,
            [],
            $this->buildMetadata($nametag),
            new PropertySyncData([], []),
            [],
        ));
    }

    /**
     * 只在文本确实变化时才发包，返回是否发生了更新
     */
    public function updateText(Player $player, string $nametag): bool
    {
        if ($nametag === $this->nametag) {
            return false;
        }
        $this->nametag = $nametag;
        $player->getNetworkSession()->sendDataPacket(SetActorDataPacket::create(
            $this->actorId,
            [EntityMetadataProperties::NAMETAG => new StringMetadataProperty($nametag)],
            new PropertySyncData([], []),
            0,
        ));
        return true;
    }

    /**
     * 移动到新坐标，不需要重建实体
     */
    public function move(Player $player, Vector3 $position): void
    {
        $this->position = $position;
        $player->getNetworkSession()->sendDataPacket(MoveActorAbsolutePacket::create(
            $this->actorId,
            $position,
            0.0,
            0.0,
            0.0,
            MoveActorAbsolutePacket::FLAG_TELEPORT,
        ));
    }

    public function despawnFrom(Player $player): void
    {
        $player->getNetworkSession()->sendDataPacket(RemoveActorPacket::create($this->actorId));
    }

    /**
     * @return array<int, \pocketmine\network\mcpe\protocol\types\entity\MetadataProperty>
     */
    private function buildMetadata(string $nametag): array
    {
        return [
            //只加NO_AI，和核心FloatingTextParticle保持一致。
            //方块本体靠下面的缩放隐藏，不用INVISIBLE：
            //部分客户端在实体隐身时会连nametag一起藏掉，反而弄丢文字。
            EntityMetadataProperties::FLAGS => new LongMetadataProperty(1 << EntityMetadataFlags::NO_AI),
            //不能填0，部分客户端会当作异常值处理
            EntityMetadataProperties::SCALE => new FloatMetadataProperty(0.01),
            EntityMetadataProperties::BOUNDING_BOX_WIDTH => new FloatMetadataProperty(0.0),
            EntityMetadataProperties::BOUNDING_BOX_HEIGHT => new FloatMetadataProperty(0.0),
            EntityMetadataProperties::NAMETAG => new StringMetadataProperty($nametag),
            EntityMetadataProperties::VARIANT => new IntMetadataProperty(self::getAirNetworkId()),
            //1表示无论是否注视都始终显示，这样远处也能看清
            EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => new ByteMetadataProperty(1),
        ];
    }

    /**
     * 空气方块的网络ID，只需要查一次
     */
    private static function getAirNetworkId(): int
    {
        return self::$airNetworkId ??= TypeConverter::getInstance()
            ->getBlockTranslator()
            ->internalIdToNetworkId(VanillaBlocks::AIR()->getStateId());
    }
}