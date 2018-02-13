<?php
namespace FactionsPro;
use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\Listener;
use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\tile\MobSpawner;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\{PlayerMoveEvent, PlayerDeathEvent, PlayerChatEvent, PlayerInteractEvent};
use pocketmine\block\Block;
class FactionListener implements Listener {
	
	public $plugin;
	
	public function __construct(FactionMain $pg) {
		$this->plugin = $pg;
	}
	
	public function factionChat(PlayerChatEvent $PCE) {
		
		$playerName = $PCE->getPlayer()->getName();
		//MOTD Check
		if($this->plugin->motdWaiting($playerName)) {
			if(time() - $this->plugin->getMOTDTime($playerName) > 30) {
				$PCE->getPlayer()->sendMessage($this->plugin->formatMessage("§cTimed out. §bPlease use: §3/f desc again."));
				$this->plugin->db->query("DELETE FROM motdrcv WHERE player='$playerName';");
				$PCE->setCancelled(true);
				return true;
			} else {
				$motd = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($playerName);
				$this->plugin->setMOTD($faction, $playerName, $motd);
				$PCE->setCancelled(true);
				$PCE->getPlayer()->sendMessage($this->plugin->formatMessage("§dSuccessfully updated the faction description. Type §5/f who", true));
			}
		}
		if(isset($this->plugin->factionChatActive[$playerName])){
			if($this->plugin->factionChatActive[$playerName]){
				$msg = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($playerName);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$PCE->setCancelled(true);
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::DARK_GREEN."[$faction]".TextFormat::BLUE." $playerName: ".TextFormat::AQUA. $msg);
						}
					}
				}
			}
		}
		if(isset($this->plugin->allyChatActive[$playerName])){
			if($this->plugin->allyChatActive[$playerName]){
				$msg = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($playerName);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->areAllies($this->plugin->getPlayerFaction($fP->getName()), $faction)){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$PCE->setCancelled(true);
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::DARK_GREEN."[$faction]".TextFormat::BLUE." $playerName: ".TextFormat::AQUA. $msg);
							$PCE->getPlayer()->sendMessage(TextFormat::DARK_GREEN."[$faction]".TextFormat::BLUE." $playerName: ".TextFormat::AQUA. $msg);
						}
					}
				}
			}
		}
	}
	
	public function factionPVP(EntityDamageEvent $factionDamage) {
		if($factionDamage instanceof EntityDamageByEntityEvent) {
			if(!($factionDamage->getEntity() instanceof Player) or !($factionDamage->getDamager() instanceof Player)) {
				return true;
			}
			if(($this->plugin->isInFaction($factionDamage->getEntity()->getPlayer()->getName()) == false) or ($this->plugin->isInFaction($factionDamage->getDamager()->getPlayer()->getName()) == false)) {
				return true;
			}
			if(($factionDamage->getEntity() instanceof Player) and ($factionDamage->getDamager() instanceof Player)) {
				$player1 = $factionDamage->getEntity()->getPlayer()->getName();
				$player2 = $factionDamage->getDamager()->getPlayer()->getName();
                		$f1 = $this->plugin->getPlayerFaction($player1);
				$f2 = $this->plugin->getPlayerFaction($player2);
				if((!$this->plugin->prefs->get("AllowFactionPvp") && $this->plugin->sameFaction($player1, $player2) == true) or (!$this->plugin->prefs->get("AllowAlliedPvp") && $this->plugin->areAllies($f1,$f2))) {
					$factionDamage->setCancelled(true);
				}
			}
		}
	}
	
	public function onInteract(PlayerInteractEvent $e){
		if($this->plugin->isInPlot($e->getPlayer())){
			if(!$this->plugin->inOwnPlot($e->getPlayer())){
				if($e->getPlayer()->isCreative()){
					$e->getPlayer()->sendMessage($this->plugin->formatMessage("§c§lRaiding environment detected. Switching to survival mode."));
					$p->setGamemode(0);
					$e->setCancelled();
				}
				if($this->plugin->essentialsPE->isGod($e->getPlayer())){
					$e->getPlayer()->sendMessage($this->plugin->formatMessage("§c§lRaiding environment detected. Disabling god mode."));
					$e->setCancelled();
				}
			}
		}
	}
	
	public function factionBlockBreakProtect(BlockBreakEvent $event) {
		$x = $event->getBlock()->getX();
		$z = $event->getBlock()->getZ();
		$level = $event->getBlock()->getLevel()->getName();
		if($this->plugin->pointIsInPlot($x, $z, $event->getBlock()->getLevel()->getName())){
			if($this->plugin->factionFromPoint($x, $z, $level) === $this->plugin->getFaction($event->getPlayer()->getName())){
				return true;
			}else{
				$event->setCancelled(true);
				$event->getPlayer()->sendMessage($this->plugin->formatMessage("§6You cannot break blocks here. This is already a property of a faction. Type §2/f plotinfo §6for details."));
				return true;
			}
		}
	}
	
	public function factionBlockPlaceProtect(BlockPlaceEvent $event) {
      		$x = $event->getBlock()->getX();
     		$z = $event->getBlock()->getZ();
     		$level = $event->getBlock()->getLevel()->getName();
		if($this->plugin->pointIsInPlot($x, $z, $level)) {
			if($this->plugin->factionFromPoint($x, $z, $level) === $this->plugin->getFaction($event->getPlayer()->getName())) {
				return true;
			} else {
				$event->setCancelled(true);
				$event->getPlayer()->sendMessage($this->plugin->formatMessage("§6You cannot place blocks here. This is already a property of a faction. Type §2/f plotinfo for details."));
				return true;
			}
		}
	}
	public function onKill(PlayerDeathEvent $event){
        $ent = $event->getEntity();
        $cause = $event->getEntity()->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent){
            $killer = $cause->getDamager();
            if($killer instanceof Player){
                $p = $killer->getPlayer()->getName();
                if($this->plugin->isInFaction($p)){
                    $f = $this->plugin->getPlayerFaction($p);
                    $e = $this->plugin->prefs->get("PowerGainedPerKillingAnEnemy");
                    if($ent instanceof Player){
                        if($this->plugin->isInFaction($ent->getPlayer()->getName())){
                           $this->plugin->addFactionPower($f,$e);
                        } else {
                           $this->plugin->addFactionPower($f,$e/2);
			}
		    $f = $this->plugin->getPlayerFaction($p);
                    $e = $this->plugin->prefs->get("MoneyGainedPerKillingAnEnemy");
                    if($ent instanceof Player){
                        if($this->plugin->isInFaction($ent->getPlayer()->getName())){
                           $this->plugin->addToBalance($f,$e);
                        } else {
                           $this->plugin->addToBalance($f,$e/2);
                        }
                    }
                }
            }
        }
        if($ent instanceof Player){
            $e = $ent->getPlayer()->getName();
            if($this->plugin->isInFaction($e)){
                $f = $this->plugin->getPlayerFaction($e);
                $e = $this->plugin->prefs->get("PowerGainedPerKillingAnEnemy");
                if($ent->getLastDamageCause() instanceof EntityDamageByEntityEvent && $ent->getLastDamageCause()->getDamager() instanceof Player){
                    if($this->plugin->isInFaction($ent->getLastDamageCause()->getDamager()->getPlayer()->getName())){      
                        $this->plugin->subtractFactionPower($f,$e*2);
                    } else {
                        $this->plugin->subtractFactionPower($f,$e);
		    }
		}
	    }
	}
	if($ent instanceof Player){
            $e = $ent->getPlayer()->getName();
            if($this->plugin->isInFaction($e)){
		$f = $this->plugin->getPlayerFaction($e);
                $e = $this->plugin->prefs->get("MoneyGainedPerKillingAnEnemy");
                if($ent->getLastDamageCause() instanceof EntityDamageByEntityEvent && $ent->getLastDamageCause()->getDamager() instanceof Player){
                    if($this->plugin->isInFaction($ent->getLastDamageCause()->getDamager()->getPlayer()->getName())){      
                        $this->plugin->takeFromBalance($f,$e*2);
                    } else {
                        $this->plugin->takeFromBalance($f,$e);
                    }
                }
            }
        }
    }
    /*public function onBlockBreak(BlockBreakEvent $event){
		if($event->isCancelled()) return;
		$playerName = $event->getPlayer();
		if(!$this->plugin->isInFaction($playerName->getName())) return;
		$block = $event->getBlock();
		if($block->getId() === Block::MONSTER_SPAWNER){
			$fHere = $this->plugin->factionFromPoint($block->x, $block->y, $block->z);
			$playerF = $this->plugin->getPlayerFaction($playerName->getName());
			if($fHere !== $playerF and !$playerName->isOp()){ $event->setCancelled(true); return; };
			TODO
		}
	}
}
