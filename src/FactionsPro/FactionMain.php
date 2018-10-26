<?php

namespace FactionsPro;

//Pocketmine imports
use pocketmine\plugin\PluginBase;
use pocketmine\command\{Command, CommandSender};
use pocketmine\event\Listener;
use pocketmine\{Server, Player};
use pocketmine\utils\{Config, TextFormat};
use pocketmine\block\Snow;
use pocketmine\math\Vector3;
use pocketmine\level\{Position, Level};

//TeaSpoon imports
use CortexPE\entity\mob\{Skeleton, Pig, Chicken, Zombie, Creeper, Cow, Spider, Blaze, Ghast}; //To-Do improve spawners

//EconomyAPI imports
use onebone\economyapi\EconomyAPI;

//FactionsPro imports
use FactionsPro\commands\FactionCommands;
use FactionsPro\listeners\FactionListener;
use FactionsPro\utils\SpoonDetector;

class FactionMain extends PluginBase implements Listener {
    
    public $db;
    public $prefs;
    public $war_req = [];
    public $wars = [];
    public $war_players = [];
    public $antispam;
    public $purechat;
    public $esssentialspe;
    public $economyapi;
    public $teaspoon;
    public $factionChatActive = [];
    public $allyChatActive = [];
    private $prefix = "§7[§6Void§bFactions§cPE§7]"; //This can easilly be changed in configurations (prefs.yml)
    
    public const HEX_SYMBOL = "e29688";
    
    //Let's the console know that this plugin's loading.
   protected function onLoad() : void {
       $this->getLogger()->info("Plugin loading..");
       $this->getLogger()->info("Loading in all checks..");
   }
	//All checks before plugin enables.
   public function checkConfigurations() : void { //Checks and loads configurations within this plugin.
	    $this->getLogger()->info("Checking configurations..");
	    @mkdir($this->getDataFolder());
        if (!file_exists($this->getDataFolder() . "BannedNames.txt")) {
            $file = fopen($this->getDataFolder() . "BannedNames.txt", "w");
            $txt = "Admin:admin:Staff:staff:Owner:owner:Builder:builder:Op:OP:op";
            fwrite($file, $txt);
        }
         $this->prefs = new Config($this->getDataFolder() . "Prefs.yml", CONFIG::YAML, array(
            "MaxFactionNameLength" => 15,
            "MaxPlayersPerFaction" => 30,
            "OnlyLeadersAndOfficersCanInvite" => true,
            "OfficersCanClaim" => false,
	    "ClaimingEnabled" => true,
            "PlotSize" => 16,
            "PlayersNeededInFactionToClaimAPlot" => 5,
            "PowerNeededToClaimAPlot" => 1000,
            "PowerNeededToSetOrUpdateAHome" => 250,
            "PowerGainedPerPlayerInFaction" => 50,
            "PowerGainedPerKillingAnEnemy" => 10,
            "PowerGainedPerAlly" => 100,
            "AllyLimitPerFaction" => 5,
            "TheDefaultPowerEveryFactionStartsWith" => 0,
	    "EnableOverClaim" => true,
            "ClaimWorlds" => [],
            "AllowChat" => true,
            "AllowFactionPvp" => false,
            "AllowAlliedPvp" => false,
            "defaultFactionBalance" => 0,
	    "MoneyGainedPerPlayerInFaction" => 20,
	    "MoneyGainedPerAlly" => 50, //To-do make this feature work
            "MoneyNeededToClaimAPlot" => 0,
	    "accept_time" => 60,
	    "deny_time" => 60,
	    "ServerName" => "§6Void§bFactions§cPE",
                "prefix" => "§7[§6Void§bFactions§cPE§7]",
                "spawnerPrices" => [ //To-Do make this system actually work.
                	"skeleton" => 500,
                	"pig" => 200,
                	"chicken" => 100,
                	"iron golem" => 5000,
                	"zombie" => 800,
                	"creeper" => 4000,
                	"cow" => 700,
                	"spider" => 500,
                	"magma" => 10000,
                	"ghast" => 10000,
                	"blaze" => 15000,
			"empty" => 100
                ],
		));
		$this->prefix = $this->prefs->get("prefix", $this->prefix);
		if(sqrt($size = $this->prefs->get("PlotSize")) % 2 !== 0){
			$this->getLogger()->notice("Square Root Of Plot Size ($size) Must Not Be An unknown Number in the plugin! (The size was Currently: ".(sqrt($size = $this->prefs->get("PlotSize"))).")");
			$this->getLogger()->notice("Available Sizes: 2, 4, 8, 16, 32, 64, 128, 256, 512, 1024");
			$this->getLogger()->notice("Plot Size Set To 16 automatically");
			$this->prefs->set("PlotSize", 16);
		}
        $this->db = new \SQLite3($this->getDataFolder() . "FactionsPro.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS master (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, rank TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS confirm (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, invitedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS alliance (player TEXT PRIMARY KEY COLLATE NOCASE, faction TEXT, requestedby TEXT, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motdrcv (player TEXT PRIMARY KEY, timestamp INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS motd (faction TEXT PRIMARY KEY, message TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS plots(faction TEXT PRIMARY KEY, x1 INT, z1 INT, x2 INT, z2 INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS home(faction TEXT PRIMARY KEY, x INT, y INT, z INT, world TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS strength(faction TEXT PRIMARY KEY, power INT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS allies(ID INT PRIMARY KEY,faction1 TEXT, faction2 TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS enemies(ID INT PRIMARY KEY,faction1 TEXT, faction2 TEXT);");
        $this->db->exec("CREATE TABLE IF NOT EXISTS alliescountlimit(faction TEXT PRIMARY KEY, count INT);");
        
        $this->db->exec("CREATE TABLE IF NOT EXISTS balance(faction TEXT PRIMARY KEY, cash INT)");
        try{
            $this->db->exec("ALTER TABLE plots ADD COLUMN world TEXT default null");
            Server::getInstance()->getLogger()->info(TextFormat::GREEN . "FactionPro: Added 'world' column to plots");
            $this->getLogger()->info("All configurations checked.");
        }catch(\ErrorException $ex){
        }
    }

    public function registerEvents() : void { //Handles all the events within this plugin.
	$this->getLogger()->info("Checking events..");
	$this->fCommand = new FactionCommands($this);
	$this->getServer()->getPluginManager()->registerEvents(new FactionListener($this), $this);
   $this->getLogger()->info("All events checked.");
    }
    
    public function checkPlugins() : void { //Checks for plugins and it's compatibility with FactionsPro.
	    $this->getLogger()->info("Checking for plugins..");
	    $this->antispam = $this->getServer()->getPluginManager()->getPlugin("AntiSpamPro");
        if (!$this->antispam) {
            $this->getLogger()->info("AntiSpamPro is not installed. If you want to ban rude Faction names, then AntiSpamPro needs to be installed. Disabling Rude faction names system...");
        }
        
        $this->purechat = $this->getServer()->getPluginManager()->getPlugin("PureChat");
        if (!$this->purechat) {
            $this->getLogger()->info("PureChat is not installed. If you want to display Faction ranks in chat, then PureChat needs to be installed. Disabling Faction chat system...");
        }
        
        $this->essentialspe = $this->getServer()->getPluginManager()->getPlugin("EssentialsPE");
        if (!$this->essentialspe) {
            $this->getLogger()->info("EssentialsPE is not installed. If you want to use the new Faction Raiding system, then EssentialsPE needs to be installed. Disabling Raiding system...");
    	}
    	
	$this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
	if (!$this->economyapi) {
	    $this->getLogger()->info("EconomyAPI is not installed. If you want to use the Faction Values system, then EconomyAPI needs to be installed. Disabling the Factions Value system...");
	}
	
	$this->teaspoon = $this->getServer()->getPluginManager()->getPlugin("TeaSpoon");
        if (!$this->teaspoon) {
            $this->getLogger()->info("TeaSpoon is currently not installed. If you want mob spawners implementations, then TeaSpoon needs to be installed. Disabling the Mob spawners system..");
        $this->getLogger()->info("All plugins checked.");
        }
    }
    
    public function checkSpoons() : void { //Checks for spoons!
    $this->getLogger()->info("Checking for spoons..");
	    //This is the check if you have the plugin, but have a spoon installed.
	   SpoonDetector::printSpoon($this, "spoon.txt"); //If you're using a spoon, this file will be generated.
    $this->getLogger()->info("All spoons checked.");
	   
    }
    public function checkOriginal() : void { //Checks if this plugin's from this repo, and not from other repos.
    if ($this->getDescription()->getAuthors() !== ["Tethered, edited by VMPE Development Team"] || $this->getDescription()->getName() !== "FactionsPro") {
            $this->getLogger()->error("You are not using the original version of FactionsPro by Tethered, edited by VMPE Development Team. Disabling plugin.");
             $this->getServer()->getPluginManager()->disablePlugin($this); //We stop people from changing the author's names when they probably never did any of the work, by disabling the plugin if the player or user were to do so.
    $this->getLogger()->info("Original author checked.");
    }
    }
    protected function onEnable() : void { //Main class file to handle all the checks
              $this->registerEvents();
              $this->checkConfigurations();
              $this->checkPlugins();
	      $this->checkSpoons();
	      $this->checkOriginal();
	      $this->getLogger()->info("All checks have been processed. Finding any errors.. (If none, won't display)");
	      }
	
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) :bool {
        return $this->fCommand->onCommand($sender, $command, $label, $args);
    }
    public function setEnemies(string $faction1, string $faction2) {
        $stmt = $this->db->prepare("INSERT INTO enemies (faction1, faction2) VALUES (:faction1, :faction2);");
        $stmt->bindValue(":faction1", $faction1);
        $stmt->bindValue(":faction2", $faction2);
        $stmt->execute();
    }
    public function unsetEnemies(string $faction1, string $faction2) {
		$stmt = $this->db->prepare("DELETE FROM enemies WHERE (faction1 = :faction1 AND faction2 = :faction2) OR (faction1 = :faction2 AND faction2 = :faction1);");
		$stmt->bindValue(":faction1", $faction1);
		$stmt->bindValue(":faction2", $faction2);
		$stmt->execute();
	}
    public function areEnemies(string $faction1, string $faction2) {
        $result = $this->db->query("SELECT ID FROM enemies WHERE (faction1 = '$faction1' AND faction2 = '$faction2') OR (faction1 = '$faction2' AND faction2 = '$faction1');");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        if (empty($resultArr) == false) {
            return true;
        }
    }
    
    public function isInFaction(string $player) { //To-do see if this is correct.
        $result = $this->db->query("SELECT player FROM master WHERE player='$player';");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }
    
    public function getFaction(string $player) { //To-do see if this is correct.
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["faction"];
    }
    
    public function setFactionPower(string $faction, int $power) {
        if ($power < 0) {
            $power = 0;
        }
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO strength (faction, power) VALUES (:faction, :power);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":power", $power);
        $stmt->execute();
    }
    public function setAllies(string $faction1, string $faction2) {
        $stmt = $this->db->prepare("INSERT INTO allies (faction1, faction2) VALUES (:faction1, :faction2);");
        $stmt->bindValue(":faction1", $faction1);
        $stmt->bindValue(":faction2", $faction2);
        $stmt->execute();
    }
    public function areAllies(string $faction1, string $faction2) {
        $result = $this->db->query("SELECT ID FROM allies WHERE (faction1 = '$faction1' AND faction2 = '$faction2') OR (faction1 = '$faction2' AND faction2 = '$faction1');");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        if (empty($resultArr) == false) {
            return true;
        }
    }
    public function updateAllies(string $faction) : int {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO alliescountlimit(faction, count) VALUES (:faction, :count);");
        $stmt->bindValue(":faction", $faction);
        $result = $this->db->query("SELECT ID FROM allies WHERE faction1='$faction' OR faction2='$faction';");
        $i = 0;
        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $i = $i + 1;
        }
        $stmt->bindValue(":count", (int) $i);
        $stmt->execute();
    }
    public function getAlliesCount(string $faction) : int {
        $result = $this->db->query("SELECT count FROM alliescountlimit WHERE faction = '$faction';");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        return (int) $resultArr["count"];
    }
    public function getAlliesLimit() : int {
        return (int) $this->prefs->get("AllyLimitPerFaction");
    }
    public function deleteAllies(string $faction1, string $faction2) {
        $stmt = $this->db->prepare("DELETE FROM allies WHERE (faction1 = '$faction1' AND faction2 = '$faction2') OR (faction1 = '$faction2' AND faction2 = '$faction1');");
        $stmt->execute();
    }
    public function getFactionPower(string $faction) : int {
        $result = $this->db->query("SELECT power FROM strength WHERE faction = '$faction';");
        $resultArr = $result->fetchArray(SQLITE3_ASSOC);
        return (int) $resultArr["power"];
    }
    public function addFactionPower(string $faction, int $power) {
        if ($this->getFactionPower($faction) + $power < 0) {
            $power = $this->getFactionPower($faction);
        }
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO strength (faction, power) VALUES (:faction, :power);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":power", $this->getFactionPower($faction) + $power);
        $stmt->execute();
    }
    public function subtractFactionPower(string $faction, int $power) {
        if ($this->getFactionPower($faction) - $power < 0) {
            $power = $this->getFactionPower($faction);
        }
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO strength (faction, power) VALUES (:faction, :power);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":power", $this->getFactionPower($faction) - $power);
        $stmt->execute();
    }
    public function isLeader(string $player) { //To-do see if this is correct.
        $faction = $this->db->query("SELECT rank FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["rank"] == "Leader";
    }
    public function isOfficer(string $player) { //To-do see if this is correct.
        $faction = $this->db->query("SELECT rank FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["rank"] == "Officer";
    }
    public function isMember(string $player) { //To-do see if this is correct.
        $faction = $this->db->query("SELECT rank FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["rank"] == "Member";
    }
    public function getPlayersInFactionByRank(Player $s, string $faction, string $rank) {
        if ($rank != "Leader") {
            $rankname = $rank . 's';
        } else {
            $rankname = $rank;
        }
        $team = "";
        $result = $this->db->query("SELECT player FROM master WHERE faction='$faction' AND rank='$rank';");
        $row = array();
        $i = 0;
        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $row[$i]['player'] = $resultArr['player'];
            if ($this->getServer()->getPlayerExact($row[$i]['player']) instanceof Player) {
                $team .= TextFormat::ITALIC . TextFormat::AQUA . $row[$i]['player'] . TextFormat::GREEN . "[ON]" . TextFormat::RESET . TextFormat::WHITE . "||" . TextFormat::RESET;
            } else {
                $team .= TextFormat::ITALIC . TextFormat::AQUA . $row[$i]['player'] . TextFormat::RED . "[OFF]" . TextFormat::RESET . TextFormat::WHITE . "||" . TextFormat::RESET;
            }
            $i = $i + 1;
        }
        $s->sendMessage($this->formatMessage("~ *<$rankname> of |$faction|* ~", true));
        $s->sendMessage($team);
    }
     public function getAllAllies(Player $s, string $faction) {
        $team = "";
        $result = $this->db->query("SELECT faction1, faction2 FROM allies WHERE faction1='$faction' OR faction2='$faction';");
        $i = 0;
        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $alliedFaction = $resultArr['faction1'] != $faction ? $resultArr['faction1'] : $resultArr['faction2'];
            $team .= TextFormat::ITALIC . TextFormat::RED . $alliedFaction . TextFormat::RESET . TextFormat::WHITE . "||" . TextFormat::RESET;
            $i = $i + 1;
        }
		if($i > 0) {
			$s->sendMessage($this->formatMessage("§3_____§2[§5§lAllies of §d*$faction*§r§2]§3_____", true));
			$s->sendMessage($team);
		} else {
			$s->sendMessage($this->formatMessage("~ *$faction* has no allies ~", true));
		}
	}
    public function sendListOfTop10FactionsTo(Player $s) {
        $tf = "";
        $result = $this->db->query("SELECT faction FROM strength ORDER BY power DESC LIMIT 10;");
        $row = array();
        $i = 0;
        $s->sendMessage($this->formatMessage("§3_____§2[§5§lTop 10 BEST Factions§r§2]§3_____", true));
        while ($resultArr = $result->fetchArray(SQLITE3_ASSOC)) {
            $j = $i + 1;
            $cf = $resultArr['faction'];
            $pf = $this->getFactionPower($cf);
            $df = $this->getNumberOfPlayers($cf);
            $s->sendMessage(TextFormat::ITALIC . TextFormat::GOLD . "§6§l$j -> " . TextFormat::GREEN . "§r§d$cf" . TextFormat::GOLD . " §b| " . TextFormat::RED . "§e$pf STR" . TextFormat::GOLD . " §b| " . TextFormat::LIGHT_PURPLE . "§a$df/50" . TextFormat::RESET);
            $i = $i + 1;
        }
    }
    public function getPlayerFaction(string $player) { //To-do see if this is correct.
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player';");
        $factionArray = $faction->fetchArray(SQLITE3_ASSOC);
        return $factionArray["faction"];
    }
    public function getLeader(string $faction) {
        $leader = $this->db->query("SELECT player FROM master WHERE faction='$faction' AND rank='Leader';");
        $leaderArray = $leader->fetchArray(SQLITE3_ASSOC);
        return $leaderArray['player'];
    }
    public function factionExists(string $faction) {
        $result = $this->db->query("SELECT player FROM master WHERE faction='$faction';");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }
    public function sameFaction(string $player1, string $player2) { //To-do see if this is correct.
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player1';");
        $player1Faction = $faction->fetchArray(SQLITE3_ASSOC);
        $faction = $this->db->query("SELECT faction FROM master WHERE player='$player2';");
        $player2Faction = $faction->fetchArray(SQLITE3_ASSOC);
        return $player1Faction["faction"] == $player2Faction["faction"];
    }
    public function getNumberOfPlayers(string $faction) : int {
        $query = $this->db->query("SELECT COUNT(player) as count FROM master WHERE faction='$faction';");
        $number = $query->fetchArray();
        return $number['count'];
    }
    public function isFactionFull(string $faction) : int {
        return $this->getNumberOfPlayers($faction) >= $this->prefs->get("MaxPlayersPerFaction");
    }
    public function isNameBanned(string $name) {
        $bannedNames = file_get_contents($this->getDataFolder() . "BannedNames.txt");
        $isbanned = false;
        if (isset($name) && $this->antispam && $this->antispam->getProfanityFilter()->hasProfanity($name)) $isbanned = true;
        return (strpos(strtolower($bannedNames), strtolower($name)) > 0 || $isbanned);
    }
    public function newPlot(string $faction, int $x1, int $z1, int $x2, int $z2) {
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO plots (faction, x1, z1, x2, z2) VALUES (:faction, :x1, :z1, :x2, :z2);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":x1", (int) $x1);
        $stmt->bindValue(":z1", (int) $z1);
        $stmt->bindValue(":x2", (int) $x2);
        $stmt->bindValue(":z2", (int) $z2);
        $stmt->execute();
    }
    public function drawPlot(Player $sender, string $faction, int $x, int $y, int $z, Level $level, int $size) { //To-do see if this is correct.
        $arm = ($size - 1) / 2;
        $block = new Snow();
        if ($this->cornerIsInPlot($x + $arm, $z + $arm, $x - $arm, $z - $arm)) { //To-do see if anything needs changing.
            $claimedBy = $this->factionFromPoint($x, $z);
            $power_claimedBy = $this->getFactionPower($claimedBy);
            $power_sender = $this->getFactionPower($faction);
           
	    if ($this->prefs->get("EnableOverClaim")) {
                if ($power_sender < $power_claimedBy) {
                    $sender->sendMessage($this->formatMessage("§cYou don't have enough power to overclaim this plot."));
                } else {
                    $sender->sendMessage($this->formatMessage("§bYou have enough STR power to overclaim this plot! Now, Type §3/f overclaim to overclaim this plot if you want."));
                }
                return false;
            } else {
                $sender->sendMessage($this->formatMessage("§2Overclaiming is disabled."));
                return false;
	    }
        }
        $level->setBlock(new Vector3($x + $arm, $y, $z + $arm), $block);
        $level->setBlock(new Vector3($x - $arm, $y, $z - $arm), $block);
        $this->newPlot($faction, $x + $arm, $z + $arm, $x - $arm, $z - $arm);
        return true;
    }
    public function isInPlot(string $player) : Position { //To-do see if this is correct.
        $x = $player->getFloorX();
        $z = $player->getFloorZ();
        $result = $this->db->query("SELECT faction FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return empty($array) == false;
    }
    public function factionFromPoint(int $x, int $z) {
        $result = $this->db->query("SELECT faction FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return $array["faction"];
    }
    public function inOwnPlot(string $player) : Position { //To-do see if this is correct.
        $playerName = $player->getName();
        $x = $player->getFloorX();
        $z = $player->getFloorZ();
        return $this->getPlayerFaction($playerName) == $this->factionFromPoint($x, $z);
    }
    public function pointIsInPlot(int $x, int $z) {
        $result = $this->db->query("SELECT faction FROM plots WHERE $x <= x1 AND $x >= x2 AND $z <= z1 AND $z >= z2;");
        $array = $result->fetchArray(SQLITE3_ASSOC);
        return !empty($array);
    }
    public function cornerIsInPlot(int $x1, int $z1, int $x2, int $z2) {
        return($this->pointIsInPlot($x1, $z1) || $this->pointIsInPlot($x1, $z2) || $this->pointIsInPlot($x2, $z1) || $this->pointIsInPlot($x2, $z2));
    }
    public function formatMessage(string $string, bool $confirm = false) {
        if ($confirm) {
            return TextFormat::GREEN . "$string";
        } else {
            return TextFormat::YELLOW . "$string";
        }
    }
    public function motdWaiting(string $player) { //To-do see if this is correct.
        $stmt = $this->db->query("SELECT player FROM motdrcv WHERE player='$player';");
        $array = $stmt->fetchArray(SQLITE3_ASSOC);
        return !empty($array);
    }
    public function getMOTDTime(string $player) : int { //To-do see if this is correct.
        $stmt = $this->db->query("SELECT timestamp FROM motdrcv WHERE player='$player';");
        $array = $stmt->fetchArray(SQLITE3_ASSOC);
        return $array['timestamp'];
    }
    public function setMOTD(string $faction, string $player, string $msg) { //To-do see if this is correct.
        $stmt = $this->db->prepare("INSERT OR REPLACE INTO motd (faction, message) VALUES (:faction, :message);");
        $stmt->bindValue(":faction", $faction);
        $stmt->bindValue(":message", $msg);
        $result = $stmt->execute();
        $this->db->query("DELETE FROM motdrcv WHERE player='$player';");
    }
    public function getMapBlock() : string{
        
    $symbol = hex2bin(self::HEX_SYMBOL);
        
    return $symbol;
    }
    public function getBalance(string $faction) : int {
		$stmt = $this->db->query("SELECT * FROM balance WHERE `faction` LIKE '$faction';");
		$array = $stmt->fetchArray(SQLITE3_ASSOC);
		if(!$array){
			$this->setBalance($faction, $this->prefs->get("defaultFactionBalance", 0));
			$this->getBalance($faction);
		}
		return $array["cash"];
	}
	public function setBalance(string $faction, int $money){
		$stmt = $this->db->prepare("INSERT OR REPLACE INTO balance (faction, cash) VALUES (:faction, :cash);");
		$stmt->bindValue(":faction", (string) $faction);
		$stmt->bindValue(":cash", (int) $money);
		return $stmt->execute();
	}
	public function addToBalance(string $faction, int $money){
		if($money < 0) return false;
		return $this->setBalance($faction, $this->getBalance($faction) + $money);
	}
	public function takeFromBalance(string $faction, int $money){
		if($money < 0) return false;
		return $this->setBalance($faction, $this->getBalance($faction) - $money);
	}
	public function sendListOfTop10RichestFactionsTo(Player $s){
        $result = $this->db->query("SELECT * FROM balance ORDER BY cash DESC LIMIT 10;");
        $i = 0;
        $s->sendMessage(TextFormat::BOLD.TextFormat::AQUA."§5§lTop 10 Richest Factions".TextFormat::RESET);
        while($resultArr = $result->fetchArray(SQLITE3_ASSOC)){
        	var_dump($resultArr);
            $j = $i + 1;
            $cf = $resultArr['faction'];
            $pf = $resultArr['cash'];
            $s->sendMessage(TextFormat::BOLD.TextFormat::GOLD.$j.". ".TextFormat::RESET.TextFormat::AQUA.$cf.TextFormat::RED.TextFormat::BOLD." §c- ".TextFormat::LIGHT_PURPLE."§d$".$pf);
            $i = $i + 1;
        } 
    }
	public function getSpawnerPrice(string $type) : int {
		$sp = $this->prefs->get("spawnerPrices");
		if(isset($sp[$type])) return $sp[$type];
		return 0;
	}
    public function updateTag(string $playername) { //To-do see if this is correct.
        $p = $this->getServer()->getPlayerExact($playername);
        $f = $this->getPlayerFaction($playername);
        if (!$this->isInFaction($playername)) {
            if(isset($this->purechat)){
                $levelName = $this->purechat->getConfig()->get("enable-multiworld-chat") ? $p->getLevel()->getName() : null;
                $nameTag = $this->purechat->getNametag($p, $levelName);
                $p->setNameTag($nameTag);
            }else{
                $p->setNameTag("§a§lPlayer - §r§b$p \n§a§lhasfaction: §r§dfalse");
            }
        }elseif(isset($this->purechat)) {
            $levelName = $this->purechat->getConfig()->get("enable-multiworld-chat") ? $p->getLevel()->getName() : null;
            $nameTag = $this->purechat->getNametag($p, $levelName);
            $p->setNameTag($nameTag);
        } else {
            $p->setNameTag("§b§lPlayer: §r§c$p \n§b§lhasFaction: §r§ctrue \n§b§lFaction: §r§c$f"); //To-do make some changes
        }
    }
    protected function onDisable() : void {
         if (isset($this->db)) $this->db->close();
    }
}
