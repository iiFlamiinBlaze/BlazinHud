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

use pocketmine\scheduler\PluginTask;

class HudTask extends PluginTask{

    public function __construct(BlazinHud $main){
        parent::__construct($main);
    }

    public function onRun(int $tick) : void{
        $hud = BlazinHud::getInstance()->getConfig()->get("hud-message");
        foreach(BlazinHud::getInstance()->getServer()->getOnlinePlayers() as $player){
            $hud = str_replace([
                "{line}",
                "{max_players}",
                "{online_players}",
                "&",
                "{x}",
                "{y}",
                "{z}",
                "{level}",
                "{tps}",
                "{motd}",
            ], [
                "\n",
                BlazinHud::getInstance()->getServer()->getMaxPlayers(),
                count(BlazinHud::getInstance()->getServer()->getOnlinePlayers()),
                "§",
                (string)round($player->getX()),
                (string)round($player->getY()),
                (string)round($player->getZ()),
                $player->getLevel()->getName(),
                BlazinHud::getInstance()->getServer()->getTicksPerSecond(),
                BlazinHud::getInstance()->getServer()->getMotd()
            ], $hud);
            $player->sendPopup($hud);
        }
    }
}
