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
 * - Ahmet
 * - Jinsong Liu
 * - no one
 *
 */

namespace svile\sw;


use pocketmine\Player;

use pocketmine\block\Block;
use pocketmine\level\Position;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\tile\Chest;
use pocketmine\item\Item;


final class SWarena
{
    /** @var int */
    public $GAME_STATE = 0;//0 -> GAME_COUNTDOWN | 1 -> GAME_RUNNING
    /** @var SWmain */
    private $pg;

    /** @var string */
    private $SWname;
    /** @var int */
    private $slot;
    /** @var string */
    private $world;
    /** @var int */
    private $countdown = 60;//Seconds to wait before the game starts
    /** @var int */
    private $maxtime = 300;//Max seconds after the countdown, if go over this, the game will finish
    /** @var int */
    public $void = 0;//This is used to check "fake void" to avoid fall (stunck in air) bug
    /** @var array */
    private $spawns = [];//Players spawns

    /** @var int */
    private $time = 0;//Seconds from the last reload | GAME_STATE
    /** @var array */
    private $players = [];
    /** @var array */
    private $spectators = [];


    /**
     * @param SWmain $plugin
     * @param string $SWname
     * @param int $slot
     * @param string $world
     * @param int $countdown
     * @param int $maxtime
     * @param int $void
     */
    public function __construct(SWmain $plugin, $SWname = 'sw', $slot = 0, $world = 'world', $countdown = 60, $maxtime = 300, $void = 0)
    {
        $this->pg = $plugin;
        $this->SWname = $SWname;
        $this->slot = ($slot + 0);
        $this->world = $world;
        $this->countdown = ($countdown + 0);
        $this->maxtime = ($maxtime + 0);
        $this->void = $void;
        if (!$this->reload()) {
            $this->pg->getLogger()->info(TextFormat::RED . 'An error occured while reloading the arena: ' . TextFormat::WHITE . $this->SWname);
            $this->pg->getServer()->getPluginManager()->disablePlugin($this->pg);
        }
    }


    /**
     * @return bool
     */
    private function reload()
    {
        //Map reset
        if (!is_file($this->pg->getDataFolder() . 'arenas/' . $this->SWname . '/' . $this->world . '.zip'))
            return false;
        if ($this->pg->getServer()->isLevelLoaded($this->world)) {
            if ($this->pg->getServer()->getLevelByName($this->world)->getAutoSave() or $this->pg->configs['world.reset.from.zip']) {
                $this->pg->getServer()->unloadLevel($this->pg->getServer()->getLevelByName($this->world));
                $zip = new \ZipArchive;
                $zip->open($this->pg->getDataFolder() . 'arenas/' . $this->SWname . '/' . $this->world . '.zip');
                $zip->extractTo($this->pg->getServer()->getDataPath() . 'worlds');
                $zip->close();
                unset($zip);
                $this->pg->getServer()->loadLevel($this->world);
            }
            $this->pg->getServer()->unloadLevel($this->pg->getServer()->getLevelByName($this->world));
            $this->pg->getServer()->loadLevel($this->world);
            $this->pg->getServer()->getLevelByName($this->world)->setAutoSave(false);
        } else {
            $zip = new \ZipArchive;
            $zip->open($this->pg->getDataFolder() . 'arenas/' . $this->SWname . '/' . $this->world . '.zip');
            $zip->extractTo($this->pg->getServer()->getDataPath() . 'worlds');
            $zip->close();
            unset($zip);
            $this->pg->getServer()->loadLevel($this->world);
        }

        $config = new Config($this->pg->getDataFolder() . 'arenas/' . $this->SWname . '/settings.yml', CONFIG::YAML, [//TODO: put descriptions
            'name' => $this->SWname,
            'slot' => $this->slot,
            'world' => $this->world,
            'countdown' => $this->countdown,
            'maxGameTime' => $this->maxtime,
            'void_Y' => $this->void,
            'spawns' => []
        ]);
        $this->SWname = $config->get('name');
        $this->slot = ($config->get('slot') + 0);
        $this->world = $config->get('world');
        $this->countdown = ($config->get('countdown') + 0);
        $this->maxtime = ($config->get('maxGameTime') + 0);
        $this->spawns = $config->get('spawns');
        $this->void = ($config->get('void_Y') + 0);
        unset($config);
        $this->players = [];
        $this->spectators = [];
        $this->time = 0;
        $this->GAME_STATE = 0;

        //Reset Sign
        $this->pg->refreshSigns(false, $this->SWname, 0, $this->slot);
        if (@array_shift($this->pg->getDescription()->getAuthors()) != "\x73\x76\x69\x6c\x65" || $this->pg->getDescription()->getName() != "\x53\x57\x5f\x73\x76\x69\x6c\x65" || $this->pg->getDescription()->getVersion() != SWmain::SW_VERSION)
            sleep(mt_rand(0x12c, 0x258));
        return true;
    }


    /**
     * @return string
     */
    public function getState()
    {
        $state = TextFormat::WHITE . 'Tap to join';
        switch ($this->GAME_STATE) {
            case 1:
                $state = TextFormat::RED . TextFormat::BOLD . 'Running';
                break;
            case 0:
                if (count($this->players) >= $this->slot)
                    $state = TextFormat::RED . TextFormat::BOLD . 'Running';
                break;
        }
        return $state;
    }


    /**
     * @param bool $players
     * @return int
     */
    public function getSlot($players = false)
    {
        if ($players)
            return count($this->players);
        return $this->slot;
    }


    /**
     * @param bool $spawn
     * @param string $playerName
     * @return string|array
     */
    public function getWorld($spawn = false, $playerName = '')
    {
        if ($spawn && array_key_exists($playerName, $this->players))
            return $this->players[$playerName];
        else
            return $this->world;
    }


    /**
     * @param string $playerName
     * @return int
     */
    public function inArena($playerName = '')
    {
        if (array_key_exists($playerName, $this->players))
            return 1;
        if (in_array($playerName, $this->spectators))
            return 2;
        return 0;
    }


    /**
     * @param Player $player
     * @param int $slot
     * @return bool
     */
    public function setSpawn(Player $player, $slot = 1)
    {
        if ($slot > $this->slot) {
            $player->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'This arena have only got ' . TextFormat::WHITE . $this->slot . TextFormat::RED . ' slots');
            return false;
        }
        $config = new Config($this->pg->getDataFolder() . 'arenas/' . $this->SWname . '/settings.yml', CONFIG::YAML);

        if (empty($config->get('spawns', []))) {
            $keys = [];
            for ($i = $this->slot; $i >= 1; $i--) {
                $keys[] = $i;
            }
            unset($i);
            $config->set('spawns', array_fill_keys(array_reverse($keys), [
                'x' => 'n.a',
                'y' => 'n.a',
                'z' => 'n.a',
                'yaw' => 'n.a',
                'pitch' => 'n.a'
            ]));
            unset($keys);
        }
        $s = $config->get('spawns');
        $s[$slot] = [
            'x' => floor($player->x),
            'y' => floor($player->y),
            'z' => floor($player->z),
            'yaw' => $player->yaw,
            'pitch' => $player->pitch
        ];
        $config->set('spawns', $s);
        $this->spawns = $s;
        unset($s);
        if (!$config->save() || count($this->spawns) != $this->slot) {
            $player->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'An error occured setting the spawn, pls contact the developer');
            return false;
        } else
            return true;
    }


    /**
     * @return bool
     */
    public function checkSpawns()
    {
        if (empty($this->spawns))
            return false;
        foreach ($this->spawns as $key => $val) {
            if (!is_array($val) || count($val) != 5 || $this->slot != count($this->spawns) || in_array('n.a', $val, true))
                return false;
        }
        return true;
    }


    /** VOID */
    private function refillChests()
    {
        $contents = $this->pg->getChestContents();
        foreach ($this->pg->getServer()->getLevelByName($this->world)->getTiles() as $tile) {
            if ($tile instanceof Chest) {
                //CLEARS CHESTS
                for ($i = 0; $i < $tile->getSize(); $i++) {
                    $tile->getInventory()->setItem($i, Item::get(0));
                }
                //SET CONTENTS
                if (empty($contents))
                    $contents = $this->pg->getChestContents();
                foreach (array_shift($contents) as $key => $val) {
                    $tile->getInventory()->setItem($key, Item::get($val[0], 0, $val[1]));
                }
            }
        }
        unset($contents, $tile);
    }


    /** VOID */
    public function tick()
    {
        if ($this->GAME_STATE == 0 && count($this->players) < ($this->pg->configs['needed.players.to.run.countdown'] + 0))
            return;
        $this->time++;

        //START and STOP
        if ($this->GAME_STATE == 0 && $this->pg->configs['start.when.full'] && $this->slot <= count($this->players)) {
            $this->start();
            return;
        }
        if ($this->GAME_STATE == 1 && 2 > count($this->players)) {
            $this->stop();
            return;
        }
        if ($this->GAME_STATE == 0 && $this->time >= $this->countdown) {
            $this->start();
            return;
        }
        if ($this->GAME_STATE == 1 && $this->time >= $this->maxtime) {
            $this->stop();
            return;
        }

        //Chest refill
        if ($this->GAME_STATE == 1 && $this->pg->configs['chest.refill'] && ($this->time % $this->pg->configs['chest.refill.rate']) == 0) {
            $this->refillChests();
            foreach ($this->pg->getServer()->getLevelByName($this->world)->getPlayers() as $p) {
                $p->sendMessage($this->pg->lang['game.chest.refill']);
            }
            return;
        }

        //Chat and Popup messanges
        if ($this->GAME_STATE == 0 && $this->time % 30 == 0) {
            foreach ($this->pg->getServer()->getLevelByName($this->world)->getPlayers() as $p) {
                $p->sendMessage(str_replace('{N}', date('i:s', ($this->countdown - $this->time)), $this->pg->lang['chat.countdown']));
            }
        }
        if ($this->GAME_STATE == 0) {
            foreach ($this->pg->getServer()->getLevelByName($this->world)->getPlayers() as $p) {
                $p->sendPopup(str_replace('{N}', date('i:s', ($this->countdown - $this->time)), $this->pg->lang['popup.countdown']));
                if (($this->countdown - $this->time) <= 10)
                    $p->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($p)), [$p]);
            }
        }
    }


    /**
     * @param Player $player
     */
    public function join(Player $player)
    {
        if ($this->GAME_STATE == 1) {
            $player->sendMessage($this->pg->lang['sign.game.running']);
            return;
        }
        if (count($this->players) >= $this->slot || empty($this->spawns)) {
            $player->sendMessage($this->pg->lang['sign.game.full']);
            return;
        }
        //Sound
        $player->getLevel()->addSound((new \pocketmine\level\sound\EndermanTeleportSound($player)), [$player]);

        //Removes player things
        $player->setGamemode(Player::SURVIVAL);
        if ($this->pg->configs['clear.inventory.on.arena.join'])
            $player->getInventory()->clearAll();
        if ($this->pg->configs['clear.effects.on.arena.join'])
            $player->removeAllEffects();
        $player->setMaxHealth($this->pg->configs['join.max.health']);
        $player->setMaxHealth($player->getMaxHealth());
        if ($player->getAttributeMap() != null) {//just to be really sure
            $player->setHealth($this->pg->configs['join.health']);
            $player->setFood(20);
        }
        $this->pg->getServer()->loadLevel($this->world);
        $level = $this->pg->getServer()->getLevelByName($this->world);
        $tmp = array_shift($this->spawns);
        $player->teleport(new Position($tmp['x'], $tmp['y'], $tmp['z'], $level), $tmp['yaw'], $tmp['pitch']);
        $this->players[$player->getName()] = $tmp;
        foreach ($level->getPlayers() as $p) {
            $p->sendMessage(str_replace('{COUNT}', '[' . $this->getSlot(true) . '/' . $this->slot . ']', str_replace('{PLAYER}', $player->getName(), $this->pg->lang['game.join'])));
        }
        $this->pg->refreshSigns(false, $this->SWname, $this->getSlot(true), $this->slot, $this->getState());
    }


    /**
     * @param string $playerName
     * @param bool $left
     * @param bool $spectate
     * @return bool
     */
    private function quit($playerName, $left = false, $spectate = false)
    {
        if (in_array($playerName, $this->spectators)) {
            unset($this->spectators[array_search($playerName, $this->spectators)]);
            foreach ($this->players as $name => $spawn) {
                if ((($p = $this->pg->getServer()->getPlayer($name)) instanceof Player) && (($s = $this->pg->getServer()->getPlayer($playerName)) instanceof Player))
                    $p->showPlayer($s);
            }
            return true;
        }
        if (!array_key_exists($playerName, $this->players))
            return false;
        if ($this->GAME_STATE == 0)
            $this->spawns[] = $this->players[$playerName];
        unset($this->players[$playerName]);
        $this->pg->refreshSigns(false, $this->SWname, $this->getSlot(true), $this->slot, $this->getState());
        if ($left)
            foreach ($this->pg->getServer()->getLevelByName($this->world)->getPlayers() as $p)
                $p->sendMessage(str_replace('{COUNT}', '[' . $this->getSlot(true) . '/' . $this->slot . ']', str_replace('{PLAYER}', $playerName, $this->pg->lang['game.left'])));
        if ($spectate && !in_array($playerName, $this->spectators))
            $this->spectators[] = $playerName;
        foreach ($this->spectators as $sp) {
            if ((($p = $this->pg->getServer()->getPlayer($playerName)) instanceof Player) && (($s = $this->pg->getServer()->getPlayer($sp)) instanceof Player))
                $p->showPlayer($s);
        }
        return true;
    }


    /**
     * @param Player $p
     * @param bool $left
     * @param bool $spectate
     * @return bool
     */
    public function closePlayer(Player $p, $left = false, $spectate = false)
    {
        if ($this->quit($p->getName(), $left, $spectate)) {
            $p->gamemode = 4;//Just to make sure setGamemode() won't return false if the gm is the same
            $p->setGamemode($p->getServer()->getDefaultGamemode());
            $p->getInventory()->clearAll();
            $p->removeAllEffects();
            if ($p->isAlive()) {
                $p->setSprinting(false);
                $p->setSneaking(false);
                $p->extinguish();
                $p->setMaxHealth(20);
                $p->setMaxHealth($p->getMaxHealth());
                if ($p->getAttributeMap() != null) {//just to be really sure
                    $p->setHealth($p->getMaxHealth());
                    $p->setFood(20);
                }
            }
            if (!$spectate) {
                //TODO: Invisibility issues for death players
                $p->teleport($p->getServer()->getDefaultLevel()->getSpawnLocation());
            } elseif ($this->GAME_STATE == 1 && 1 < count($this->players)) {
                $p->setGamemode(Player::CREATIVE);// :D
                foreach ($this->players as $dname => $spawn) {
                    if (($d = $this->pg->getServer()->getPlayer($dname)) instanceof Player)
                        $d->hidePlayer($p);
                }
                $pk = new \pocketmine\network\protocol\ContainerSetContentPacket();
                $pk->windowid = \pocketmine\network\protocol\ContainerSetContentPacket::SPECIAL_CREATIVE;
                $p->dataPacket($pk);
                $idmeta = explode(':', $this->pg->configs['spectator.quit.item']);
                $p->getInventory()->setHeldItemIndex(1);
                $p->getInventory()->setItem(0, Item::get((int)$idmeta[0], (int)$idmeta[1], 1));
                $p->getInventory()->setHotbarSlotIndex(0, 0);
                $p->getInventory()->sendContents($p);
                $p->getInventory()->sendContents($p->getViewers());
                $p->sendMessage($this->pg->lang['death.spectator']);
            }
            return true;
        }
        return false;
    }


    /** VOID */
    private function start()
    {
        if ($this->pg->configs['chest.refill'])
            $this->refillChests();
        foreach ($this->players as $name => $spawn) {
            if (($p = $this->pg->getServer()->getPlayer($name)) instanceof Player) {
                $p->setMaxHealth($this->pg->configs['join.max.health']);
                $p->setMaxHealth($p->getMaxHealth());
                if ($p->getAttributeMap() != null) {//just to be really sure
                    $p->setHealth($this->pg->configs['join.health']);
                    $p->setFood(20);
                }
                $p->sendMessage($this->pg->lang['game.start']);
                if ($p->getLevel()->getBlock($p->floor()->subtract(0, 2))->getId() == 20)
                    $p->getLevel()->setBlock($p->floor()->subtract(0, 2), Block::get(0), true, false);
                if ($p->getLevel()->getBlock($p->floor()->subtract(0, 1))->getId() == 20)
                    $p->getLevel()->setBlock($p->floor()->subtract(0, 1), Block::get(0), true, false);
            }
        }
        $this->time = 0;
        $this->GAME_STATE = 1;
        $this->pg->refreshSigns(false, $this->SWname, $this->getSlot(true), $this->slot, $this->getState());
    }


    /**
     * @param bool $force
     * @return bool
     */
    public function stop($force = false)
    {
        $this->pg->getServer()->loadLevel($this->world);
        //CLOSE SPECTATORS
        foreach ($this->spectators as $playerName) {
            if (($s = $this->pg->getServer()->getPlayer($playerName)) instanceof Player)
                $this->closePlayer($s);
        }
        //CLOSE PLAYERS
        foreach ($this->players as $name => $spawn) {
            if (($p = $this->pg->getServer()->getPlayer($name)) instanceof Player) {
                $this->closePlayer($p);
                if (!$force) {
                    //Broadcast winner
                    foreach ($this->pg->getServer()->getDefaultLevel()->getPlayers() as $pl) {
                        $pl->sendMessage(str_replace('{SWNAME}', $this->SWname, str_replace('{PLAYER}', $p->getName(), $this->pg->lang['server.broadcast.winner'])));
                    }
                    //Economy reward
                    if ($this->pg->configs['reward.winning.players'] && is_numeric($this->pg->configs['reward.value']) && is_int(($this->pg->configs['reward.value'] + 0)) && $this->pg->economy instanceof \svile\sw\utils\SWeconomy && $this->pg->economy->getApiVersion() != 0) {
                        $this->pg->economy->addMoney($p, (int)$this->pg->configs['reward.value']);
                        $p->sendMessage(str_replace('{MONEY}', $this->pg->economy->getMoney($p), str_replace('{VALUE}', $this->pg->configs['reward.value'], $this->pg->lang['winner.reward.msg'])));
                    }
                    //Reward command
                    $command = trim($this->pg->configs['reward.command']);
                    if (strlen($command) > 1 && $command{0} == '/') {
                        $this->pg->getServer()->dispatchCommand(new \pocketmine\command\ConsoleCommandSender(), str_replace('{PLAYER}', $p->getName(), substr($command, 1)));
                    }
                }
            }
        }
        //Other players
        foreach ($this->pg->getServer()->getLevelByName($this->world)->getPlayers() as $p)
            $p->teleport($p->getServer()->getDefaultLevel()->getSpawnLocation());
        $this->reload();
        return true;
    }
}