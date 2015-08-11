<?php

namespace onebone\artificialintelligent;

use pocketmine\scheduler\PluginTask;

use onebone\artificialintelligent\entity\BaseEntity;

class TickTask extends PluginTask{
  public function __construct(ArtificialIntelligent $plugin){
    parent::__construct($plugin);
  }

  public function onRun($currentTick){
    $this->getOwner()->onUpdate($currentTick);
  }
}
