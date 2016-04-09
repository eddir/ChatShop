<?php

namespace eddir;

/*
 * ChatShop - plugin for PocketMine-MP.
 *
 * @author Eddir
 * @link eddirworkmail@gmail.com
 * @link http://vk.com/eddir
 */

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\block\Block;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\sound\ClickSound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\tile\Sign;
use pocketmine\utils\TextFormat;
use pocketmine\utils\TextFormat as F;
use pocketmine\utils\Config;

class ChatShop extends PluginBase implements Listener {

	public $EconomyS, $config, $messages;

	public function onEnable(){
		$this->saveDefaultConfig();
		$this->saveResource("messages.yml", false);	
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		if(!($this->EconomyS = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI"))){
			$this->getLogger()->error("Ошибка: EconomyAPI не установлен");
			$this->getServer()->shutdown();
			return;
		}
		$this->config = (new Config($this->getDataFolder() . "config.yml", Config::YAML))->getAll();
		$this->messages = (new Config($this->getDataFolder() . "messages.yml", Config::YAML))->getAll();
 	}
	
	public function onCommand(CommandSender $entity, Command $cmd, $label, array $args) {
		switch($cmd->getName()){
			case "shop":
				$entity->sendMessage($this->messages["shop-command"]);
			return true;
			break;
			case "price":
				if(isset($args[0])){
					if(isset($this->config[$args[0]])){
						$id = $args[0];
						$thing = $this->config[$args[0]];
						$price = $thing["price"];
						$entity->sendMessage(str_replace(["%id%", "%price%"],[$id, $price], $this->messages["price"]));
						return true;
					}else{
						$entity->sendMessage($this->messages["not-found"]);
						return true;
					}
				}else{
					return false;
				}
			break;
			case "buy":
				if(isset($args[0])){
					if(isset($args[1])){
						if(isset($this->config[$args[0]])){
							if(is_numeric($args[1]) && $args[1] > 0){
								$thing = $this->config[$args[0]];
								$price = $thing["price"];
								$id = $thing["id"];
								$data = $thing["data"];
								$colv = $args[1];
								$sum = $price * $colv;
								if($entity->getInventory()->canAddItem(Item::get($id, $data, $colv))){
									if(($moneyNow = $this->EconomyS->myMoney($entity->getName())) >= $sum){
										$entity->getInventory()->addItem(Item::get($id, $data, $colv));
										$this->EconomyS->setMoney($entity->getName(), $moneyNow - $sum);
										$entity->sendMessage(str_replace(["%amount%", "%name%", "%sum%"],[$colv, $args[0], $sum], $this->messages["sucses-buy"]));
										return true;
									}else{
										$entity->sendMessage($this->messages["no-money"]);
										return true;
									}
								}else{
									$entity->sendMessage($this->messages["cannot-add"]);
								}
							}else{
								$entity->sendMessage($this->messages["math-erorr"]);
								return true;
							}
						}else{
							$entity->sendMessage($this->messages["not-found"]);
							return true;
						}
					}else{
						return false;
					}              
				}else{
					return false;
				}
			break;
			case "sell":
				if(isset($args[0])){
					if(isset($args[1])){
						if(isset($this->config[$args[0]])){
							if(is_numeric($args[1]) && $args[1] > 0){
								$thing = $this->config[$args[0]];
								$price = $thing["price"];
								$id = $thing["id"];
								$data = $thing["data"];
								$colv = $args[1];
								$sum = $price * $colv;
								$item = new Item($id, $data, $colv);
								$moneyNow = $this->EconomyS->myMoney($entity->getName());
								if($entity->getInventory()->contains($item)){
									$this->EconomyS->setMoney($entity->getName(), $moneyNow + $sum);          
									$entity->getInventory()->removeItem(Item::get($id, $data, $colv));
									$entity->sendMessage(str_replace(["%amount%", "%name%", "%sum%"],[$colv, $args[0], $sum], $this->messages["sucses-sell"]));
									return true;
								}else{
									$entity->sendMessage($this->messages["no-sell"]);
									return true;
								}
							}else{
								$entity->sendMessage($this->messages["math-erorr"]);
								return true;
							}
						}else{
							$entity->sendMessage($this->messages["not-found"]);
							return true;
						}
					}else{
						return false;
					}              
				}else{
					return false;
				}            
			break;
			default:
				return false;
			break;
		}
	}
}
