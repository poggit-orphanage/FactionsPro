<?php

namespace FactionsPro\tasks;

//Pocketmine imports
use pocketmine\scheduler\Task;

//FactionsPro imports
use FactionsPro\FactionMain;

class FactionWarTask extends Task {
	
	public $plugin;
	public $requester;
	
	public function __construct(FactionMain $plugin, string $requester) {
        $this->plugin = $plugin;
		$this->requester = $requester;
    }
	
	public function onRun(int $currentTick): void {
		unset($this->plugin->wars[$this->requester]);
		$this->plugin->getScheduler()->cancelTask($this->getTaskId());
	}
	
}
