<?php
/**
 *  ____  _            _______ _          _____
 * |  _ \| |          |__   __| |        |  __ \
 * | |_) | | __ _ _______| |  | |__   ___| |  | | _____   __
 * |  _ <| |/ _` |_  / _ \ |  | '_ \ / _ \ |  | |/ _ \ \ / /
 * | |_) | | (_| |/ /  __/ |  | | | |  __/ |__| |  __/\ V /
 * |____/|_|\__,_/___\___|_|  |_| |_|\___|_____/ \___| \_/
 *
 * Copyright (C) 2019 iiFlamiinBlaze
 *
 * iiFlamiinBlaze's plugins are licensed under MIT license!
 * Made by iiFlamiinBlaze for the PocketMine-MP Community!
 *
 * @author iiFlamiinBlaze
 * Twitter: https://twitter.com/iiFlamiinBlaze
 * GitHub: https://github.com/iiFlamiinBlaze
 * Discord: https://discord.gg/znEsFsG
 */
declare(strict_types=1);

namespace iiFlamiinBlaze\BlazinHud;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class BlazinHud extends PluginBase implements Listener{

	const VERSION = "v1.0.5";
	const PREFIX = TextFormat::RED . "§lWARNING" . TextFormat::GRAY . " »§r§7 ";

	/** @var self $instance */
	protected static $instance;
	/** @var array $hud */
	public $hud = [];

	public function onEnable() : void{
		self::$instance = $this;
		@mkdir($this->getDataFolder());
		$this->saveDefaultConfig();
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->economyCheck();
		$this->getLogger()->info("BlazinHud " . self::VERSION . " by iiFlamiinBlaze is enabled");
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$this->multiWorldCheck($event->getPlayer());
		$this->hud[$event->getPlayer()->getName()] = true;
	}

	public function onLevelChange(EntityLevelChangeEvent $event) : void{
		$this->multiWorldCheck($event->getEntity());
	}

	protected function multiWorldCheck(Entity $entity) : bool{
		if(!$entity instanceof Player) return false;
		if($this->getConfig()->get("multi-world") === "on"){
			if(in_array($entity->getLevel()->getName(), $this->getConfig()->get("worlds"))){
				$this->getScheduler()->scheduleRepeatingTask(new HudTask($entity), 30);
			}else{
				$entity->sendMessage(self::PREFIX . TextFormat::RED . "You are not in the right world for your hud to appear");
				return false;
			}
		}elseif($this->getConfig()->get("multi-world") === "off"){
			$this->getScheduler()->scheduleRepeatingTask(new HudTask($entity), 30);
			return false;
		}
		return true;
	}

	protected function economyCheck() : bool{
		if($this->getConfig()->get("economy") === "on"){
			if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null){
				$this->getServer()->getPluginManager()->disablePlugin($this);
				$this->getLogger()->error(TextFormat::RED . "Plugin Disabled! Please turn off economy support in the config or enable/install EconomyAPI");
				return false;
			}
		}elseif($this->getConfig()->get("economy") === "off") return false;
		return true;
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		if($command->getName() === "blazinhud"){
			if(!$sender instanceof Player){
				$sender->sendMessage(self::PREFIX . TextFormat::RED . "Use this command in game");
				return false;
			}
			if(!$sender->hasPermission("blazinhud.command")){
				$sender->sendMessage(self::PREFIX . TextFormat::RED . "You do not have permission to use this command");
				return false;
			}
			if(empty($args[0])){
				$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload | on | off> <hud | multiworld> <message>");
				return false;
			}
			switch($args[0]){
				case "info":
					foreach([TextFormat::DARK_GRAY . "-=========" . TextFormat::GOLD . "BlazinHud " . self::VERSION . TextFormat::DARK_GRAY . "=========-",
								TextFormat::GREEN . "Author: " . TextFormat::AQUA . "BlazeTheDev",
								TextFormat::GREEN . "GitHub: " . TextFormat::AQUA . "https://github.com/iiFlamiinBlaze",
								TextFormat::GREEN . "Support: " . TextFormat::AQUA . "https://discord.gg/znEsFsG",
								TextFormat::GREEN . "Description: " . TextFormat::AQUA . "Allows you to customize a message that will pop up above your hotbar",
								TextFormat::DARK_GRAY . "-===============================-"] as $msg) $sender->sendMessage($msg);

					break;
				case "on":
					if(!isset($this->hud[$sender->getName()])){
						$this->hud[$sender->getName()] = true;
						$sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have turned on your hud");
					}elseif(isset($this->hud[$sender->getName()])){
						$sender->sendMessage(self::PREFIX . TextFormat::RED . "Your hud is already on");
						return false;
					}
					break;
				case "off":
					if(isset($this->hud[$sender->getName()])){
						unset($this->hud[$sender->getName()]);
						$sender->sendMessage(self::PREFIX . TextFormat::RED . "You have turned off your hud");
					}elseif(!isset($this->hud[$sender->getName()])){
						$sender->sendMessage(self::PREFIX . TextFormat::RED . "Your hud is already off");
						return false;
					}
					break;
				case "set":
					if(empty($args[1])){
						$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud set <hud | multiworld> <message>");
						return false;
					}
					switch($args[1]){
						case "hud":
							if(empty($args[2])){
								$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud set hud <message>");
								return false;
							}
							if(is_string($args[2])){
								$this->getConfig()->set("hud-message", $args[2]);
								$this->getConfig()->save();
								$sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have successfully set the hud to $args[2]");
							}else{
								$sender->sendMessage(self::PREFIX . TextFormat::RED . "Please enter a string to set the hud too");
								return false;
							}
							break;
						case "multiworld":
							if(empty($args[2])){
								$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud set multiworld <on | off>");
								return false;
							}
							switch($args[2]){
								case "on":
									$this->getConfig()->set("multiworld", "on");
									$sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have successfully turned multiworld on");
									break;
								case "off":
									$this->getConfig()->set("multiworld", "off");
									$sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have successfully turned multiworld off");
									break;
								default:
									$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud set multiworld <on | off>");
									break;
							}
							break;
						default:
							$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload | on | off> <hud | multiworld> <message>");
							break;
					}
					break;
				case "reload":
					$this->getConfig()->save();
					$this->getConfig()->reload();
					$sender->sendMessage(self::PREFIX . TextFormat::GREEN . "BlazinHud successfully reloaded");
					break;
				default:
					$sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload | on | off> <hud | multiworld> <message>");
					break;
			}
		}
		return true;
	}

	public static function getInstance() : self{
		return self::$instance;
	}
}
