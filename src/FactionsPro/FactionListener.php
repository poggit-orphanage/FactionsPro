<?php

namespace FactionsPro;

use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\Listener;
use pocketmine\event\block\{BlockPlaceEvent, BlockBreakEvent};
use pocketmine\Player;
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};
use EssentialsPE\BaseFiles\BaseAPI;
use pocketmine\tile\MobSpawner;
use pocketmine\utils\{Config, TextFormat};
use pocketmine\event\player\{PlayerQuitEvent, PlayerJoinEvent, PlayerMoveEvent, PlayerDeathEvent, PlayerChatEvent, PlayerInteractEvent};
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
					$e->getPlayer()->setGamemode(0);
					$e->setCancelled(true);
				}
				if($this->plugin->essentialspe->getAPI()->isGod($e->getPlayer())){
					$e->getPlayer()->sendMessage($this->plugin->formatMessage("§c§lRaiding environment detected. Disabling god mode."));
					 $this->plugin->essentialspe->getAPI()->getSession($e->getPlayer()->setGod($e->getPlayer()->getGodMode()));
					$e->setCancelled(true);
				}
			}
		}
	}
	
	public function factionBlockBreakProtect(BlockBreakEvent $event) {
		$x = $event->getBlock()->getX();
		$y = $event->getBlock()->getY();
		$z = $event->getBlock()->getZ();
		if($this->plugin->pointIsInPlot($x, $z)){
			if($this->plugin->factionFromPoint($x, $z) === $this->plugin->getFaction($event->getPlayer()->getName())){
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
		$y = $event->getBlock()->getY();
     		$z = $event->getBlock()->getZ();
		if($this->plugin->pointIsInPlot($x, $z)) {
			if($this->plugin->factionFromPoint($x, $z) === $this->plugin->getFaction($event->getPlayer()->getName())) {
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
    public function onBlockBreak(BlockBreakEvent $event){
	      if($event->isCancelled()) return true;
	      $player = $event->getPlayer();
	      if(!$this->plugin->isInFaction($player->getName())) return true;
	      $block = $event->getBlock();
	      if($block->getId() === Block::MONSTER_SPAWNER){
		      $fHere = $this->plugin->factionFromPoint($block->x, $block->y);
		      $playerF = $this->plugin->getPlayerFaction($player->getName());
		      if($fHere !== $playerF and !$player->isOp()){ $event->setCancelled(true); return true; };
	      }
    }
    public function broadcastTeamJoin(PlayerJoinEvent $event){
       $player = $event->getPlayer();
        
            if($this->plugin->isInFaction($player->getName()) == true) {
               $faction = $this->plugin->getPlayerFaction($player->getName());
               $db = $this->plugin->db->query("SELECT * FROM master WHERE faction='$faction'");
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
							$this->plugin->getServer()->getPlayer($fP->getName())->sendMessage("§l§a(!)§r§e " . $player->getName() . " §ais now online");
                               }
                          }
                    }
            }
    }
    public function broadcastTeamQuit(PlayerQuitEvent $event){
       $player = $event->getPlayer();
       $name = $player->getName();
        
               if($this->plugin->isInFaction($player->getName()) == true) {
               $faction = $this->plugin->getPlayerFaction($player->getName());
               $db = $this->plugin->db->query("SELECT * FROM master WHERE faction='$faction'");
				foreach($this->plugin->getServer()->getOnlinePlayers() as $fP){
					if($this->plugin->getPlayerFaction($fP->getName()) == $faction){
						if($this->plugin->getServer()->getPlayer($fP->getName())){
                                                    $this->plugin->getServer()->getPlayer($fP->getName())->sendMessage("§l§c(!)§r§4 " . $player->getName() . " §cis now offline");
            }
          }
        }
               }
    }
    public function onPlayerJoin(PlayerJoinEvent $event) {
		$this->plugin->updateTag($event->getPlayer()->getName());
    }
    public function onMoveMAP(PlayerMoveEvent $event){
        
    $x = floor($event->getPlayer()->getX());
    $y = floor($event->getPlayer()->getY());
    $z = floor($event->getPlayer()->getZ());
       $Faction = $this->plugin->factionFromPoint($x,$z);
           $asciiCompass = self::getASCIICompass($event->getPlayer()->getYaw(), TextFormat::RED, TextFormat::GREEN);
             $compass = "     " . $asciiCompass[0] . "\n     " . $asciiCompass[1] . "\n     " . $asciiCompass[2] . "\n";
          if(isset($this->plugin->factionMapActive[$event->getPlayer()->getName()])){
          if($this->plugin->factionMapActive[$event->getPlayer()->getName()]){
        
          if($this->plugin->isInPlot($event->getPlayer())) {
             if($this->plugin->inOwnPlot($event->getPlayer())) {
                $tip = $compass . "§l§6Protected area§r";
                $event->getPlayer()->sendTip($tip);
            } else {
                $tip = $compass . "§l§c".$Faction;
                $event->getPlayer()->sendTip($tip);
                }
            }
        if(!$this->plugin->ip->canGetHurt($event->getPlayer())) {
               $tip = $compass . "§l§aPublic area§r";
               $event->getPlayer()->sendTip($tip);
            }
        if(!$this->plugin->isInPlot($event->getPlayer())){
               $tip = $compass . "§l§2Zona Book§r";
               $event->getPlayer()->sendTip($tip);
            }
        }
    }
        }
        const N = 'N';
    const NE = '/';
    const E = 'E';
    const SE = '\\';
    const S = 'S';
    const SW = '/';
    const W = 'W';
    const NW = '\\';
    public static function getASCIICompass($degrees, $colorActive, $colorDefault) : array
    {
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
    public static function getCompassPointForDirection($degrees)
    {
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
