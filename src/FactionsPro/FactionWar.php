<?php

namespace FactionsPro;

use pocketmine\scheduler\PluginTask;

class FactionWar extends PluginTask {
	
	public $plugin;
	public $requester;
	
	public function __construct(FactionMain $pl, $requester) {
        parent::__construct($pl);
        $this->plugin = $pl;
		$this->requester = $requester;
    }
	
	public function onRun(int $currentTick) {
		unset($this->plugin->wars[$this->requester]);
		$this->plugin->getServer()->getScheduler()->cancelTask($this->getTaskId());
	}
	
}