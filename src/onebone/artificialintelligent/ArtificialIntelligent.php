<?php

namespace onebone\artificialintelligent;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Float;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\utils\Config;
use pocketmine\utils\Utils;

use onebone\artificialintelligent\entity\Sheep;
use onebone\artificialintelligent\entity\Pig;
use onebone\artificialintelligent\entity\Cow;

class ArtificialIntelligent extends PluginBase implements Listener{
  /** @var BaseEntity */
  private $entities = [];

  private static $registered;

  public function onLoad(){
    self::$registered = [
      "Sheep", "Pig", "Cow"
    ];

    Item::addCreativeItem(new Item(383, 11));
    Item::addCreativeItem(new Item(383, 12));
    Item::addCreativeItem(new Item(383, 13));

    Entity::registerEntity("onebone\\artificialintelligent\\entity\\Sheep", true);
    Entity::registerEntity("onebone\\artificialintelligent\\entity\\Pig", true);
    Entity::registerEntity("onebone\\artificialintelligent\\entity\\Cow", true);
  }

  public function onEnable(){
    $this->saveResource("auto-update.yml");

    $update = new Config($this->getDataFolder()."auto-update.yml", Config::YAML);
		if($update->get("enabled")){
			try{
				$url = "http://onebone.me/plugins/artificialintelligent/api/?version=".$this->getDescription()->getVersion();
				$content = Utils::getUrl($url);

				$data = json_decode($content, true);
				if($data["update-available"] === true){
					$this->getLogger()->notice("New version of ArtificialIntelligent was released. Version : ".$data["new-version"]);
					if($update->get("force-update") and $this->isPhar()){
						$address = file_get_contents($data["download-address"]);
						$e = explode("/", $data["download-address"]);
						$filename = end($e);
						file_put_contents($this->getDataFolder()."../".$filename, $address);
						if($this->isPhar()){
							$file = substr($this->getFile(), 7, -1);
							@unlink($file);
						}

						$this->getLogger()->notice("ArtificialIntelligent was updated automatically to version ".$data["new-version"]);
            $this->getServer()->shutdown();
            return;
					}
				}else{
					$this->getLogger()->notice("ArtificialIntelligent is currently up-to-date.");
				}
        if($data["notice"] !== ""){
          $this->getLogger()->notice($data["notice"]);
        }
			}catch(\Exception $e){
				$this->getLogger()->error("Error while retrieving data from server : \n".$e);
			}
		}

    $this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new TickTask($this), 240, 240);

    $this->getServer()->getPluginManager()->registerEvents($this, $this);
  }

  public function onSpawn(EntitySpawnEvent $event){
    $entity = $event->getEntity();

    if($entity instanceof Sheep or $entity instanceof Cow or $entity instanceof Pig){
      $this->entities[$entity->getId()] = $entity;
    }
  }

  public function onUpdate($currentTick){
    $spawnLimit = $this->getServer()->getProperty("spawn-limits", ["animals" => 15])["animals"];

    if($spawnLimit >= count($this->entities)){
      $players = $this->getServer()->getOnlinePlayers();
      if(count($players) <= 0) return;
      $player = $players[array_rand($players)];

      $x = $player->getX() + mt_rand(-50, 50);
      $z = $player->getZ() + mt_rand(-50, 50);

      $y = $player->getLevel()->getHighestBlockAt($x, $z) + 1;
			Entity::createEntity(self::$registered[array_rand(self::$registered)], $player->getLevel()->getChunk($x >> 4, $z >> 4), new Compound("", [
  			"Pos" => new Enum("Pos", [
  				new Double("", $x),
  				new Double("", $y),
  				new Double("", $z)
  			]),
  			"Motion" => new Enum("Motion", [
  				new Double("", 0),
  				new Double("", 0),
  				new Double("", 0)
  			]),
  			"Rotation" => new Enum("Rotation", [
  				new Float("", 0),
  				new Float("", 0)
  			]),
  		]));
    }
  }
}
