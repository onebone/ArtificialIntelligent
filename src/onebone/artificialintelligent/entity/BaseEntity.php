<?php

namespace onebone\artificialintelligent\entity;

use pocketmine\entity\Living;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\Network;
use pocketmine\math\AxisAlignedBB;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\Server;

abstract class BaseEntity extends Living{
  protected $target = null;
  protected $gravity = 0.08;

  public function onUpdate($currentTick){
    parent::onUpdate($currentTick);

    if($this instanceof MovingEntity){
      $this->processMove();
    }
  }

  public function processMove(){
    if($this->target instanceof Vector3){
      $xDiff = $this->target->x - $this->x;
      $zDiff = $this->target->z - $this->z;
      if($xDiff ** 2 + $zDiff ** 2 < 2){
        $this->target = null;
        return;
      }
      $diff = abs($xDiff) + abs($zDiff);
      $speed = 0.1;
      $this->motionX = $speed * (($this->target->x - $this->x) / $diff);
      $this->motionZ = $speed * (($this->target->z - $this->z) / $diff);

      $radius = $this->width / 2;

      $boundingBox = new AxisAlignedBB(round($this->x - $radius + ($this->motionX * 10)), $this->y, round($this->z - $radius + ($this->motionZ * 10)), round($this->x + $radius + ($this->motionX * 10)), ceil($this->y + $this->height), round($this->z + $radius + ($this->motionZ * 10)));

      $block = $this->getLevel()->getBlock($this->getSide(0));

      if(!$block->isSolid()){
        $this->motionY -= $this->gravity;
      }else{
        $this->motionY = 0;
      }

      $collision = $this->getLevel()->getCollisionCubes($this, $boundingBox, false);
      $height = 0;
      foreach($collision as $block){
        $height += ($block->maxY - $block->minY);
      }

      if($height > 1){
        $this->motionX = 0;
        $this->motionZ = 0;
        $this->target = null;
        return;
      }elseif($height > 0){
        $this->motionY = 0.25;
      }

      $angle = atan2($this->target->z - $this->z, $this->target->x - $this->x);
      $this->yaw = (($angle * 180) / M_PI) - 90;

      $this->move($this->motionX, $this->motionY, $this->motionZ);

      $this->getLevel()->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
    }else{
      $this->motionX = 0;
      $this->motionZ = 0;
      $rand = mt_rand(1, 150);
      if($rand === 1){
        $this->target = new Vector3($this->x + rand(-8, 8), $this->y, $this->z + rand(-8, 8));
      }elseif($rand > 1 and $rand < 5){
        $this->yaw = max(-180, min(180, ($this->yaw + rand(-90, 90))));
        $this->getLevel()->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, $this->yaw, $this->pitch);
      }
      if(!$this->getLevel()->getBlock($this->round())->isSolid()){
        $this->motionY -= $this->gravity;
      }else{
        $this->motionY = 0;
      }
      $this->move($this->motionX, $this->motionY, $this->motionZ);
    }
  }

  public function kill(){
		if(!$this->isAlive() or $this->closed){
			return;
		}

    $pk = new EntityEventPacket();
    $pk->eid = $this->id;
    $pk->event = EntityEventPacket::DEATH_ANIMATION;
    foreach($this->getLevel()->getPlayers() as $player){
      $player->dataPacket($pk->setChannel(Network::CHANNEL_WORLD_EVENTS));
    }

    parent::kill();
  }

  public function attack($damage, EntityDamageEvent $source){
    parent::attack($damage, $source);

    if(!$source->isCancelled()){
      $pk = new EntityEventPacket();
  		$pk->eid = $this->id;
  		$pk->event = EntityEventPacket::HURT_ANIMATION;
      foreach($this->getLevel()->getPlayers() as $player){
        $player->dataPacket($pk->setChannel(Network::CHANNEL_WORLD_EVENTS));
      }
    /*  if($source instanceof EntityDamageByEntityEvent){
  			$e = $source->getDamager();
  			if($source instanceof EntityDamageByChildEntityEvent){
  				$e = $source->getChild();
  			}

  			$deltaX = $this->x - $e->x;
  			$deltaZ = $this->z - $e->z;
  			$this->knockBack($e, $damage, $deltaX, $deltaZ, $source->getKnockBack());
  		}*/
    }
  }
}
