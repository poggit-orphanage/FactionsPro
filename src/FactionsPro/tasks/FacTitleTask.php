<?php

namespace FactionsPro\tasks;

use pocketmine\Server;

use pocketmine\scheduler\PluginTask;

use pocketmine\Player;

use FactionsPro\FactionMain;







class FacTitleTask extends PluginTask {




   public function __construct(FactionMain $main) {


        parent::__construct($main);


        $this->main = $main;


        $this->server = $main->getServer();


    }




   public function onRun(int $tick) {


        $this->main->getLogger()->debug('Task ' . get_class($this) . ' is running on $tick'); 


    }




}
