<?php
/**
 *  ____  _            _______ _          _____
 * |  _ \| |          |__   __| |        |  __ \
 * | |_) | | __ _ _______| |  | |__   ___| |  | | _____   __
 * |  _ <| |/ _` |_  / _ \ |  | '_ \ / _ \ |  | |/ _ \ \ / /
 * | |_) | | (_| |/ /  __/ |  | | | |  __/ |__| |  __/\ V /
 * |____/|_|\__,_/___\___|_|  |_| |_|\___|_____/ \___| \_/
 *
 * Copyright (C) 2018 iiFlamiinBlaze
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

    const VERSION = "v1.0.1";
    const PREFIX = TextFormat::AQUA . "BlazinHud" . TextFormat::GOLD . " > ";

    /** @var self $instance */
    private static $instance;

    public function onEnable() : void{
        self::$instance = $this;
        $this->getLogger()->info("BlazinHud " . self::VERSION . " by iiFlamiinBlaze is enabled");
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onJoin(PlayerJoinEvent $event) : void{
        $this->multiWorldCheck($event->getPlayer());
    }

    public function onLevelChange(EntityLevelChangeEvent $event) : void{
        $this->multiWorldCheck($event->getEntity());
    }

    private function multiWorldCheck(Entity $entity) : bool{
        if(!$entity instanceof Player) return false;
        if($this->getConfig()->get("multi-world") === "on"){
            if(in_array($entity->getLevel()->getName(), $this->getConfig()->get("worlds"))){
                $this->getServer()->getScheduler()->scheduleRepeatingTask(new HudTask($this), 30);
            }else{
                $entity->sendMessage(self::PREFIX . TextFormat::RED . "You are not in the right world for your hud to appear");
                return false;
            }
        }elseif($this->getConfig()->get("multi-world") === "off"){
            $this->getServer()->getScheduler()->scheduleRepeatingTask(new HudTask($this), 30);
            return false;
        }
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
            if(empty($args)){
                $sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload> <hud | multiworld> <message>");
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

                    return true;
                case "set":
                    if($args[1]){
                        switch($args[1]){
                            case "hud":
                                if($args[2]){
                                    if(is_string($args[2])){
                                        $this->getConfig()->set("hud-message", $args[2]);
                                        $this->getConfig()->save();
                                        $sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have successfully set the hud to $args[2]");
                                        return true;
                                    }else{
                                        $sender->sendMessage(self::PREFIX . TextFormat::RED . "Please enter a string to set the hud too");
                                        return false;
                                    }
                                }else{
                                    $sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload> <hud | multiworld> <message>");
                                    return false;
                                }
                            case "multiworld":
                                if($args[2]){
                                    switch($args[2]){
                                        case "on":
                                            $this->getConfig()->set("multiworld", "on");
                                            $sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have successfully turned multiworld on");
                                            return true;
                                        case "off":
                                            $this->getConfig()->set("multiworld", "off");
                                            $sender->sendMessage(self::PREFIX . TextFormat::GREEN . "You have successfully turned multiworld off");
                                            return true;
                                        default:
                                            $sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: " . TextFormat::GREEN . "/blazinhud multiworld " . TextFormat::RED . "on | off");
                                            return false;
                                    }
                                }
                                return false;
                            default:
                                $sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload> <hud | multiworld> <message>");
                                return false;
                        }
                    }else{
                        $sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload> <hud | multiworld> <message>");
                        return false;
                    }
                case "reload":
                    $this->getConfig()->save();
                    $this->getConfig()->reload();
                    $sender->sendMessage(self::PREFIX . TextFormat::GREEN . "BlazinHud successfully reloaded");
                    return true;
                default:
                    $sender->sendMessage(self::PREFIX . TextFormat::GRAY . "Usage: /blazinhud <info | set | reload> <hud | multiworld> <message>");
                    return true;
            }
        }
        return true;
    }

    public static function getInstance() : self{
        return self::$instance;
    }
}
