<?php

namespace FactionsPro;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\Listener;
use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};
use pocketmine\Player;
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};
use pocketmine\utils\{Config, TextFormat};
use pocketmine\event\player\{PlayerQuitEvent, PlayerJoinEvent, PlayerMoveEvent, PlayerDeathEvent, PlayerChatEvent, PlayerInteractEvent};
use pocketmine\block\Block;

//EssentialsPE imports
use EssentialsPE\BaseFiles\BaseAPI; //To-do fix some bugs to do with Essentials and it's compatibility with this plugin.

//TeaSpoon imports
use CortexPE\tile\MobSpawner; //To-do - Fix bugs with MobSpawning and its compatibility with this plugin.

use FactionsPro\FactionMain;

class FactionListener implements Listener {
	
	public $plugin;
	
	public function __construct(FactionMain $plugin) {
		$this->plugin = $plugin;
	}
	
	public function factionChat(PlayerChatEvent $PCE) : void {
		
		$player = $PCE->getPlayer();
	     if($player instanceof Player){
         $e = $player->getPlayer()->getName();
		//MOTD Check
		if($this->plugin->motdWaiting($e)) {
			if(time() - $this->plugin->getMOTDTime($e) > 30) { //To-do make this configurable.
				$PCE->getPlayer()->sendMessage($this->plugin->formatMessage("§cTimed out. §bPlease use: §3/f desc again."));
				$this->plugin->db->query("DELETE FROM motdrcv WHERE player='$e';");
				$PCE->setCancelled(true);
			} else {
				$motd = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($e);
				$this->plugin->setMOTD($faction, $e, $motd);
				$PCE->setCancelled(true);
				$PCE->getPlayer()->sendMessage($this->plugin->formatMessage("§dSuccessfully updated the faction description. Type §5/f who", true));
			}
		}
		if(isset($this->plugin->factionChatActive[$e])){
			if($this->plugin->factionChatActive[$e]){
				$msg = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($e);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$PCE->setCancelled(true);
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage(TextFormat::DARK_GREEN."[$faction]".TextFormat::BLUE." $e: ".TextFormat::AQUA. $msg);
						}
					}
				}
			}
		}
		if(isset($this->plugin->allyChatActive[$e])){
			if($this->plugin->allyChatActive[$e]){
				$msg = $PCE->getMessage();
				$faction = $this->plugin->getPlayerFaction($e);
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->areAllies($this->plugin->getPlayerFaction($fP->getName()), $faction)){
						if($this->plugin->getServer()->getPlayerExact($fP->getName())){
							$PCE->setCancelled(true);
							$this->plugin->getServer()->getPlayerExact($fP->getName())->sendMessage(TextFormat::DARK_GREEN."[$faction]".TextFormat::BLUE." $e: ".TextFormat::AQUA. $msg);
							$PCE->getPlayer()->sendMessage(TextFormat::DARK_GREEN."[$faction]".TextFormat::BLUE." $e: ".TextFormat::AQUA. $msg);
						  }
					  }
				  }
			  }
		  }
	  }
	}
	public function factionPVP(EntityDamageEvent $factionDamage) : void {
		if($factionDamage instanceof EntityDamageByEntityEvent) {
			if(!($factionDamage->getEntity() instanceof Player) or !($factionDamage->getDamager() instanceof Player)) {
			}
			if(($this->plugin->isInFaction($factionDamage->getEntity()->getPlayer()->getName()) == false) or ($this->plugin->isInFaction($factionDamage->getDamager()->getPlayer()->getName()) == false)) {
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
	
	public function onInteract(PlayerInteractEvent $PIE) : void{ //PIE stands for PlayerInteractEvent, funny that.
		$user = $PIE->getPlayer();
	     if($user instanceof Player){
         $e = $user->getPlayer()->getName();
		if($this->plugin->isInPlot($PIE->getPlayer())){
			if(!$this->plugin->inOwnPlot($e)){
				if($e->getPlayer()->isCreative()){
					$PIE->getPlayer()->sendMessage($this->plugin->formatMessage("§c§lRaiding environment detected. Switching to survival mode."));
					$PIE->getPlayer()->setGamemode(0);
					$PIE->setCancelled(true);
				}
				if($this->plugin->essentialspe->getAPI()->isGod($PIE->getPlayer())){
					$PIE->getPlayer()->sendMessage($this->plugin->formatMessage("§c§lRaiding environment detected. Disabling god mode."));
					 $this->plugin->essentialspe->getAPI()->getSession($PIE->getPlayer()->setGod($PIE->getPlayer()->getGodMode()));
					$PIE->setCancelled(true);
				  }
			  }
		  }
	  }
	}
	public function factionBlockBreakProtect(BlockBreakEvent $BBE) : void { //BBE stands for BlockBreakEvent.
		$x = $BBE->getBlock()->getX();
		$y = $BBE->getBlock()->getY();
		$z = $BBE->getBlock()->getZ();
		if($this->plugin->pointIsInPlot((int) $x, (int) $z)){
			if($this->plugin->factionFromPoint((int) $x, (int) $z) === $this->plugin->getFaction($BBE->getPlayer()->getName())){
			}else{
				$BBE->setCancelled(true);
				$BBE->getPlayer()->sendMessage($this->plugin->formatMessage("§6You cannot break blocks here. This is already a property of a faction. Type §2/f plotinfo §6for details."));
			}
			if($BBE->isCancelled()) {
			$ent = $BBE->getPlayer();
	     if($ent instanceof Player){
         $e = $ent->getPlayer()->getName();
	      $player = $BBE->getPlayer();
	      if(!$this->plugin->isInFaction($e)){
	      $block = $BBE->getBlock();
	      if($block->getId() === Block::MONSTER_SPAWNER){ //To-do improve mob spawning.
		      $fHere = $this->plugin->factionFromPoint((int) $block->x, (int) $block->y);
		      $playerF = $this->plugin->getPlayerFaction($e);
		      if($fHere !== $playerF and !$player->isOp()){
	        $BBE->setCancelled(true);
	      }
        }
		 }
      }
     }
    }
   }
	public function factionBlockPlaceProtect(BlockPlaceEvent $BPE) : void { //BPE stands for BlockPlaceEvent
      		$x = $BPE->getBlock()->getX();
		$y = $BPE->getBlock()->getY();
     		$z = $BPE->getBlock()->getZ();
		if($this->plugin->pointIsInPlot((int) $x, (int) $z)) {
			if($this->plugin->factionFromPoint((int) $x, (int) $z) === $this->plugin->getFaction($BPE->getPlayer()->getName())) {
			} else {
				$BPE->setCancelled(true);
				$BPE->getPlayer()->sendMessage($this->plugin->formatMessage("§6You cannot place blocks here. This is already a property of a faction. Type §2/f plotinfo for details."));
			}
		}
	}
	public function onKill(PlayerDeathEvent $PDE) : void { //PDE stands for PlayerDeathEvent.
        $ent = $PDE->getEntity();
        $cause = $PDE->getEntity()->getLastDamageCause();
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
    }
    public function PlayerJoinEvent(PlayerJoinEvent $PJE) : void { //PJE stands for PlayerJoinEvent
       $user = $PJE->getPlayer();
	     if($user instanceof Player){
	 $name = $user->getName();
         $e = $user->getPlayer()->getName();
            if($this->plugin->isInFaction($e) == true) {
               $faction = $this->plugin->getPlayerFaction($e);
               $db = $this->plugin->db->query("SELECT * FROM master WHERE faction='$faction'");
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage("§l§a(!)§r§e " . $user->getName() . " §ais now online"); //To-do make this configurable.
							$this->plugin->updateTag($event->getPlayer()->getName());
                               }
                          }
                    }
            }
       }
    }
    public function broadcastTeamQuit(PlayerQuitEvent $PQE) : void { //PQE stands for PlayerQuitEvent.
       $user = $PQE->getPlayer();
	     if($user instanceof Player){
	 $name = $user->getName();
         $e = $user->getPlayer()->getName();
               if($this->plugin->isInFaction($e) == true) {
               $faction = $this->plugin->getPlayerFaction($e);
               $db = $this->plugin->db->query("SELECT * FROM master WHERE faction='$faction'");
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
                                                    $this->plugin->getServer()->getPlayer($fP->getName())->sendMessage("§l§c(!)§r§4 " . $user->getName() . " §cis now offline"); //To-do make this configurable.
              }
            }
          }
        }
      }
    }
    public function onMoveMAP(PlayerMoveEvent $PME) : void { //PME stands for PlayerMoveEvent
        
    $x = floor($PME->getPlayer()->getX());
    $y = floor($PME->getPlayer()->getY());
    $z = floor($PME->getPlayer()->getZ());
	  
	     if($user instanceof Player){
		     $e = $user->getPlayer()->getName();
		     $user = $PME->getPlayer()->getName();
       $Faction = $this->plugin->factionFromPoint($x,$z);
           $asciiCompass = self::getASCIICompass($PME->getPlayer()->getYaw(), TextFormat::RED, TextFormat::GREEN);
             $compass = "     " . $asciiCompass[0] . "\n     " . $asciiCompass[1] . "\n     " . $asciiCompass[2] . "\n";
          if(isset($this->plugin->factionMapActive[$PME->getPlayer()->getName()])){
          if($this->plugin->factionMapActive[$PME->getPlayer()->getName()]){
        
          if($this->plugin->isInPlot($PME->getPlayer()->getName())) {
             if($this->plugin->inOwnPlot($PME->getPlayer()->getName())) {
                $tip = $compass . "§l§6Protected area§r";
                $PME->getPlayer()->sendTip($tip);
            } else {
                $tip = $compass . "§l§c".$Faction;
                $PME->getPlayer()->sendTip($tip);
                }
        }
        if(!$this->plugin->ip->canGetHurt($PME->getPlayer()->getName())) {
               $tip = $compass . "§l§aPublic area§r";
               $PME->getPlayer()->sendTip($tip);
            }
        if(!$this->plugin->isInPlot($PME->getPlayer()->getName())){
               $tip = $compass . "§l§2Zona Book§r"; //To-do translate this to the actual english spelling
               $PME->getPlayer()->sendTip($tip);
            }
          }
        }
	   }
    }
    public const N = 'N',
    NE = '/',
    E = 'E',
    SE = '\\',
    S = 'S',
    SW = '/',
    W = 'W',
    NW = '\\';
	
    public static function getASCIICompass(int $degrees, string $colorActive, string $colorDefault) : array {
        $ret = [];
        $point = self::getCompassPointForDirection($degrees);
        $row = "";
        $row .= ($point === self::NW ? $colorActive : $colorDefault) . self::NW;
        $row .= ($point === self::N ? $colorActive : $colorDefault) . self::N;
        $row .= ($point === self::NE ? $colorActive : $colorDefault) . self::NE;
        $ret[] = $row;
        $row = "";
        $row .= ($point === self::W ? $colorActive : $colorDefault) . self::W;
        $row .= $colorDefault . "+";
        $row .= ($point === self::E ? $colorActive : $colorDefault) . self::E;
        $ret[] = $row;
        $row = "";
        $row .= ($point === self::SW ? $colorActive : $colorDefault) . self::SW;
        $row .= ($point === self::S ? $colorActive : $colorDefault) . self::S;
        $row .= ($point === self::SE ? $colorActive : $colorDefault) . self::SE;
        $ret[] = $row;
        return $ret;
    }
    public static function getCompassPointForDirection(int $degrees) {
        $degrees = ($degrees - 180) % 360;
        if ($degrees < 0)
            $degrees += 360;
        if (0 <= $degrees && $degrees < 22.5)
            return self::N;
        elseif (22.5 <= $degrees && $degrees < 67.5)
            return self::NE;
        elseif (67.5 <= $degrees && $degrees < 112.5)
            return self::E;
        elseif (112.5 <= $degrees && $degrees < 157.5)
            return self::SE;
        elseif (157.5 <= $degrees && $degrees < 202.5)
            return self::S;
        elseif (202.5 <= $degrees && $degrees < 247.5)
            return self::SW;
        elseif (247.5 <= $degrees && $degrees < 292.5)
            return self::W;
        elseif (292.5 <= $degrees && $degrees < 337.5)
            return self::NW;
        elseif (337.5 <= $degrees && $degrees < 360.0)
            return self::N;
        else
            return null;    
           }
    }
