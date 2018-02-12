<?php

namespace FactionsPro;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;

class FactionTitle implements Listener {

    private $infac = array();
	
	public function onJoin(PlayerJoinEvent $ev) {
          $p = $ev->getPlayer();
		 $this->infac[] = $p->getName();
	}
   public function onMove(PlayerMoveEvent $ev){
      $p = $ev->getPlayer();
	  $name = $p->getName();
	   
						if(!$this->plugin->isInPlot($p)){
							$id = array_search($p->getName(), $this->infac);
							unset($this->infac[$id]);
                        }else{
						if (!in_array("$name", $this->infac)) {
							 $x = floor($p->getX());
							 $y = floor($p->getY());
							 $z = floor($p->getZ());
							 $fac = $this->plugin->factionFromPoint($x, $z, $level);
							 $this->infac[] = $p->getName();
							 $title = "§a§lNow Entering ";
							 $subtitle = "§b§l" . $fac . " ";
							 $p->addTitle($title, $subtitle);
						}
					}
	 }
}
