<?php

namespace onebone\artificialintelligent\entity;

use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\Network;
use pocketmine\math\AxisAlignedBB;
use pocketmine\item\Item;

class Cow extends BaseEntity implements MovingEntity{
  const NETWORK_ID = 11;

  public function __construct($chunk, $nbt){
    parent::__construct($chunk, $nbt);

    $this->width = 1;
    $this->height = 1;
  }

  public function getName(){
    return "Cow";
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
    parent::processMove();
  }

  public function getDrops(){
    return [
      Item::get(Item::LEATHER, 0, mt_rand(0, 2)),
      Item::get(Item::RAW_BEEF, 0, mt_rand(1, 3))
    ];
  }
}
