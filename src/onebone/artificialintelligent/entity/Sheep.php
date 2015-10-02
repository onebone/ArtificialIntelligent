<?php

namespace onebone\artificialintelligent\entity;

use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\Network;
use pocketmine\math\AxisAlignedBB;
use pocketmine\block\Block;
use pocketmine\item\Item;

class Sheep extends BaseEntity implements MovingEntity{
  const NETWORK_ID = 13;

  public function __construct($chunk, $nbt){
    parent::__construct($chunk, $nbt);

    $this->width = 1;
    $this->height = 1;
  }

  public function getName(){
    return "Sheep";
  }

  public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);

		parent::spawnTo($player);
	}

  public function processMove(){
    $targetSet = false;

    $entities = $this->getLevel()->getNearbyEntities(new AxisAlignedBB($this->x - 7, $this->y - 3, $this->z - 7, $this->x + 7, $this->y + 3, $this->z + 7));
    foreach($entities as $entity){
      if($entity instanceof Player){
        if($entity->getInventory()->getItemInHand()->getId() === 296){
          $this->target = $entity;
          $targetSet = true;
          break;
        }
      }
    }
    if($targetSet === false){
      if($this->target instanceof Player){
        $this->target = null;
      }
    }

    if($this->motionX === 0 and $this->motionZ === 0){
      $rand = mt_rand(1, 1000);
      if($rand < 10 and $this->getLevel()->getBlock(($pos = $this->subtract(0, 1, 0)))->getId() === 2){
        $pk = new EntityEventPacket();
        $pk->eid = $this->id;
        $pk->event = $rand === 1 ? EntityEventPacket::EAT_GRASS_ANIMATION : EntityEventPacket::AMBIENT_SOUND;
        foreach($this->getLevel()->getPlayers() as $player){
          $player->dataPacket($pk->setChannel(Network::CHANNEL_WORLD_EVENTS));
        }

        if($rand === 1)
          $this->getLevel()->setBlock($pos, Block::get(Block::DIRT));
      }
    }
    parent::processMove();
  }

  public function getDrops(){
    return [
      Item::get(Item::WOOL, 0, 1)
    ];
  }
}
