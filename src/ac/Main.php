<?php
namespace ac;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
    class Main extends PluginBase implements Listener {
        public $movePlayers = [];
        public $point = [];
        public $npcs = [];


        public function onEnable() {
            $id = Entity::$entityCount++;
            $uuid = UUID::fromRandom();
            $pkadd = new AddPlayerPacket();
            $pkadd->uuid = $uuid;
            $pkadd->username = "";
            $pkadd->eid = $id;
            $pkadd->x = 0;
            $pkadd->y = 0;
            $pkadd->z = 0;
            $pkadd->yaw = 0;
            $pkadd->pitch = 0;
            $pkadd->item = Item::fromString(0);;
            $flags = 0;
            $flags |= 1 << 5;
            $flags |= 1 << 14;
            $flags |= 1 << 15;
            $flags |= 1 << 16;
            $pkadd->metadata = [ 
                Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
                Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""]
            ];
            $pkremove = new RemoveEntityPacket();
            $pkremove->eid = $id;
            $this->npcs["add"] = $pkadd;
            $this->npcs["id"] = $id;
            $this->npcs["remove"] = $pkremove;
            $this->getServer()->getPluginManager()->registerEvents($this, $this);
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new CheckTask($this), 20);
        }


        public function onPlayerKick(PlayerKickEvent $event){
            if($event->getReason() === "Sorry, hack mods are not permitted on Steadfast... at all."){
                //$event->setCancelled(true);
            }
    	}


        public function onPlayerJoin(PlayerJoinEvent $event){
            $player = $event->getPlayer();
            $this->movePlayers[$player->getName()]["distance"] = (float) 0;
            $this->point[$player->getName()]["distance"] = (float) 0;
            $this->movePlayers[$player->getName()]["fly"] = (float) 0;
            $this->point[$player->getName()]["fly"] = (float) 0;
    	}


        public function onPlayerQuit(PlayerQuitEvent $event){
            $player = $event->getPlayer();
            unset($this->movePlayers[$player->getName()]);
            unset($this->point[$player->getName()]);
    	}


        public function onPlayerMove(PlayerMoveEvent $event){
            $player = $event->getPlayer();
            $oldPos= $event->getFrom();
		    $newPos = $event->getTo();
            if(!$player->isCreative() and !$player->isSpectator() and !$player->isOp() and !$player->getAllowFlight()){
                $this->movePlayers[$player->getName()]["distance"] += sqrt(($newPos->getX() - $oldPos->getX()) ** 2 + ($newPos->getZ() - $oldPos->getZ()) ** 2);
            }
    	}


        public function onRecieve(DataPacketReceiveEvent $event) {
            $player = $event->getPlayer();
            $packet = $event->getPacket();
            $player = $event->getPlayer();
            if($packet instanceof UpdateAttributesPacket){ 
                $this->getServer()->broadcastMessage(TextFormat::GOLD . $player->getName() . " was ban becuase using Hack!");
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(),"kick " . $player->getName() . " Hack");
            }
            if($packet instanceof SetPlayerGameTypePacket){ 
                $player->kick(TextFormat::RED."#HACK SetPlayerGameTypePacket");
                $this->getServer()->broadcastMessage(TextFormat::GOLD . $player->getName() . " was ban becuase using Hack!");
                $this->getServer()->dispatchCommand(new ConsoleCommandSender(),"kick " . $player->getName() . " Hack");
            }
            if($packet instanceof AdventureSettingsPacket){
                if(!$player->isCreative() and !$player->isSpectator() and !$player->isOp() and !$player->getAllowFlight()){
                    switch ($packet->flags){ //Packet ส่งขอลอย
                        case 614:
                        case 615:
                        case 103:
                        case 102:
                        case 38:
                        case 39:
                            $this->getServer()->broadcastMessage(TextFormat::GOLD . $player->getName() . " was ban becuase using Hack!");
                            $this->getServer()->dispatchCommand(new ConsoleCommandSender(),"kick " . $player->getName() . " Hack");
                            break;
                        default:
                            break;
                    }
                    if((($packet->flags >> 9) & 0x01 === 1) or (($packet->flags >> 7) & 0x01 === 1) or (($packet->flags >> 6) & 0x01 === 1)){
                        $this->getServer()->broadcastMessage(TextFormat::GOLD . $player->getName() . " was ban becuase using Hack!");
                        $this->getServer()->dispatchCommand(new ConsoleCommandSender(),"kick " . $player->getName() . " Hack");
                    }
                }
                if (($packet->allowFlight || $packet->isFlying) && $player->getAllowFlight() && $player->isSpectator() && $player->isOp() !== true) {
                    $this->getServer()->broadcastMessage(TextFormat::GOLD . $player->getName() . " was ban becuase using Hack!");
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender(),"kick " . $player->getName() . " Hack");
                }
                if ($packet->noClip && $player->isSpectator() !== true) {
                    $this->getServer()->broadcastMessage(TextFormat::GOLD . $player->getName() . " was ban becuase using Hack!");
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender(),"kick " . $player->getName() . " Hack");
                }
            $player->sendSettings();
            $event->setCancelled();
            }
        }
    }
