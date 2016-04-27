<?php

/*
 *                _   _
 *  ___  __   __ (_) | |   ___
 * / __| \ \ / / | | | |  / _ \
 * \__ \  \ / /  | | | | |  __/
 * |___/   \_/   |_| |_|  \___|
 *
 * SkyWars plugin for PocketMine-MP & forks
 *
 * @Author: svile
 * @Kik: _svile_
 * @Telegram_Gruop: https://telegram.me/svile
 * @E-mail: thesville@gmail.com
 * @Github: https://github.com/svilex/SkyWars-PocketMine
 *
 * Copyright (C) 2016 svile
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * DONORS LIST :
 * - no one
 * - no one
 * - no one
 *
 */

namespace svile\sw;


use pocketmine\Player;


class SWeconomy
{
    /** @var SWmain */
    private $pg;
    /** @var bool|\pocketmine\plugin\Plugin */
    private $api;

    public function __construct(SWmain $plugin)
    {
        $this->pg = $plugin;
        /*
        if ($this->economy instanceof \pocketmine\plugin\Plugin) {
            return $this->economy;
        } else {
            $api = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
            if ($api != false && $api instanceof \pocketmine\plugin\Plugin) {
                if ($api->getDescription()->getVersion() == '2.0.9' && array_shift($api->getDescription()->getAuthors()) == "\x6f\x6e\x65\x62\x6f\x6e\x65") {
                    $this->economy = $api;
                    return $api;
                }
            }
        }
        return false;
        */
    }

    /**
     * @return bool|\pocketmine\plugin\Plugin
     */
    public function getApi()
    {
        return $this->api;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function addMoney(Player $player)
    {
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function takeMoney(Player $player)
    {
        return true;
    }

    /**
     * @param Player $player
     * @return bool
     */
    public function getMoney(Player $player)
    {
        return true;
    }
}