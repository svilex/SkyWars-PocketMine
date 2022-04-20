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
 * @Authors: svile, Laith98Dev
 * @Kik: _svile_
 * @Telegram_Group: https://telegram.me/svile
 * @E-mail: thesville@gmail.com
 * @Github: https://github.com/svilex/SkyWars-PocketMine
 * @Github: https://github.com/Laith98Dev/SkyWars-svile
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

namespace svile\skywars;

use pocketmine\block\BaseSign;
use pocketmine\block\utils\SignText;
use pocketmine\plugin\PluginBase;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\tile\Sign;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

class SWmain extends PluginBase
{
    /** Plugin Version */
    const SW_VERSION = '1.8.0';

    /** @var SWcommands */
    private $commands;
    /** @var SWarena[] */
    public $arenas = [];
    /** @var array */
    public $signs = [];
    /** @var array */
    public $configs;
    /** @var array */
    public $lang;
    /** @var \SQLite3 */
    private $db;
    /** @var null | \svile\skywars\utils\SWeconomy */
    public $economy;

    public function onEnable(): void
    {
        @mkdir($this->getDataFolder() . "arenas");
        if ($this->getDescription()->getVersion() != self::SW_VERSION)
            $this->getLogger()->critical("There is a problem with the plugin version");
        
        //Creates the database that is needed to store signs info (what a bad idea -_-)
        try {
            if (!is_file($this->getDataFolder() . "SW_signs.db")) {// also should use libasynql
                $this->db = new \SQLite3($this->getDataFolder() . "SW_signs.db", SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            } else {
                $this->db = new \SQLite3($this->getDataFolder() . "SW_signs.db", SQLITE3_OPEN_READWRITE);
            }
            $this->db->exec("CREATE TABLE IF NOT EXISTS signs (arena TEXT PRIMARY KEY COLLATE NOCASE, x INTEGER , y INTEGER , z INTEGER, world TEXT);");
        } catch (\Exception $e) {
            $this->getLogger()->critical($e->getMessage() . ' in §b' . $e->getFile() . '§c on line §b' . $e->getLine());
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        //Config file...
        $v = ((new Config($this->getDataFolder() . 'SW_configs.yml', Config::YAML))->get('CONFIG_VERSION', '1st'));
        if ($v != '1st' && $v != self::SW_VERSION) {
            $this->getLogger()->notice('You are using old configs, deleting them.Make sure to delete old arenas if aren\'t working');
            @unlink($this->getDataFolder() . 'SW_configs.yml');
            @unlink($this->getDataFolder() . 'SW_lang.yml');
            $this->saveResource('SW_configs.yml', true);
        } elseif ($v == '1st') {
            $this->saveResource('SW_configs.yml', true);
        }
        unset($v);

        //Config files: /SW_configs.yml /SW_lang.yml & for arenas: /arenas/SWname/settings.yml

        /*
                                       __  _                                   _
                   ___   ___   _ __   / _|(_)  __ _  ___     _   _  _ __ ___  | |
                  / __| / _ \ | '_ \ | |_ | | / _` |/ __|   | | | || '_ ` _ \ | |
                 | (__ | (_) || | | ||  _|| || (_| |\__ \ _ | |_| || | | | | || |
                  \___| \___/ |_| |_||_|  |_| \__, ||___/(_) \__, ||_| |_| |_||_|
                                              |___/          |___/
        */
        $configs = new Config($this->getDataFolder() . 'SW_configs.yml', Config::YAML, [
            'CONFIG_VERSION' => self::SW_VERSION,
            'banned.commands.while.in.game' => array('/hub', '/lobby', '/spawn', '/tpa', '/tp', '/tpaccept', '/back', '/home', '/f', '/kill'),
            'start.when.full' => true,
            'needed.players.to.run.countdown' => 1,
            'join.max.health' => 20,
            'join.health' => 20,
            'damage.cancelled.causes' => [0, 3, 4, 8, 12, 15],
            'drops.on.death' => false,
            'player.drop.item' => true,
            'chest.refill' => true,
            'chest.refill.rate' => 0xf0,
            'no.pvp.countdown' => 20,
            'death.spectator' => true,
            'spectator.quit.item' => '120:0',
            'reward.winning.players' => false,
            'reward.value' => 100,
            'reward.command' => '/',
            '1st_line' => '§l§c[§bSW§c]',
            '2nd_line' => '§l§e{SWNAME}',
            'sign.tick' => false,
            'sign.knockBack' => true,
            'knockBack.radius.from.sign' => 1,
            'knockBack.intensity' => 0b10,
            'knockBack.follow.sign.direction' => false,
            'always.spawn.in.defaultLevel' => true,
            'clear.inventory.on.respawn&join' => false,//many people don't know on respawn means also on join
            'clear.inventory.on.arena.join' => true,
            'clear.effects.on.respawn&join' => false,//many people don't know on respawn means also on join
            'clear.effects.on.arena.join' => true,
            'world.generator.air' => true,
            'world.compress.tar' => false,
            'world.reset.from.tar' => true
        ]);
        $this->configs = $configs->getAll();

        /*
                  _                                                   _
                 | |   __ _   _ __     __ _       _   _   _ __ ___   | |
                 | |  / _` | | '_ \   / _` |     | | | | | '_ ` _ \  | |
                 | | | (_| | | | | | | (_| |  _  | |_| | | | | | | | | |
                 |_|  \__,_| |_| |_|  \__, | (_)  \__, | |_| |_| |_| |_|
                                      |___/       |___/
        */
        $lang = new Config($this->getDataFolder() . 'SW_lang.yml', Config::YAML, [
            'banned.command.msg' => '@b→@cYou can\'t use this command here',
            'sign.game.full' => '@b→@cThis game is full, please wait',
            'sign.game.running' => '@b→@cThe game is running, please wait',
            'game.join' => '@b→@f{PLAYER} @ejoined the game @b{COUNT}',
            'popup.countdown' => '@bThe game starts in @f{N}',
            'chat.countdown' => '@b→@7The game starts in @b{N}',
            'game.start' => '@b→@dThe game start now, good luck !',
            'no.pvp.countdown' => '@bYou can\'t PvP for @f{COUNT} @bseconds',
            'game.chest.refill' => '@b→@aChests has been refilled !',
            'game.left' => '@f→@7{PLAYER} left the game @b{COUNT}',
            'death.player' => '@c→@f{PLAYER} @cwas killed by @f{KILLER} @b{COUNT}',
            'death.arrow' => '@c→@f{PLAYER} @cwas killed by @f{KILLER} @b{COUNT}',
            'death.void' => '@c→@f{PLAYER} @cwas killed by @fVOID @b{COUNT}',
            'death.lava' => '@c→@f{PLAYER} @cwas killed by @fLAVA @b{COUNT}',//TODO: add more?
            'death.spectator' => '@f→@bYou are now a spectator!_EOL_@f→@bType @f/sw quit @bto exit from the game',
            'server.broadcast.winner' => '@0→@f{PLAYER} @bwon the game on SW: @f{SWNAME}',
            'winner.reward.msg' => '@f→@bYou won @f{VALUE}$_EOL_@f→@7Your money: @f{MONEY}$'
        ]);

        $this->lang = $lang->getAll();
       
        $newlang = [];
        foreach ($this->lang as $key => $val) {
            $newlang[$key] = str_replace('  ', ' ', str_replace('_EOL_', "\n", str_replace('@', '§', trim($val))));
        }

        $this->lang = $newlang;
        unset($newlang);

        //Register timer and listener
        $this->getScheduler()->scheduleRepeatingTask(new SWtimer($this), 20);
        $this->getServer()->getPluginManager()->registerEvents(new SWlistener($this), $this);

        //Calls loadArenas() & loadSigns() to loads arenas & signs...
        if (!($this->loadSigns() && $this->loadArenas())) {
            $this->getLogger()->error('An error occurred loading the SW_svile plugin, try deleting the plugin folder');
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }

        //svile\sw\SWcommands
        $this->commands = new SWcommands($this);
        if ($this->configs['reward.winning.players']) {
            //\svile\sw\utils\SWeconomy
            $this->economy = new \svile\skywars\utils\SWeconomy($this);
            if ($this->economy->getApiVersion()) {
                $this->getLogger()->info('§aUsing: §f' . $this->economy->getApiVersion(true) . '§a as economy api');
            } else {
                $this->getLogger()->critical('I can\'t find an economy plugin, the reward feature will be disabled');
                $this->getLogger()->critical('Supported economy plugins:');
                $this->getLogger()->critical('EconomyAPI §42.0.9');
                $this->getLogger()->critical('PocketMoney §44.0.1');
                $this->getLogger()->critical('MassiveEconomy §41.0 R3');
                $this->economy = null;
            }
        }

        $this->getLogger()->info(str_replace('\n', PHP_EOL, @gzinflate(@base64_decode("\x70\x5a\x42\x4e\x43\x6f\x4d\x77\x45\x45\x61\x76knVBs3dVS8VFWym00I0gUaZJMD8Sk1JP5D08WUlqFm7bWb7vzTcwtarVMotl7na/zLoMubNMmwwt83N8cQGRn3\x67fYBNoE/EdBFBDZFMa7YZgMGuHMcPYrlEqAW+qikQSLoJrGfhIwJ56lnZaRqvklrl200gD8tK38I1v/fQgZkyuuuvBXriKR9\x6f1QYNwlCvUTiis+D5SVPnhXBz//NcH"))));
    }


    public function onDisable(): void
    {
        foreach ($this->arenas as $name => $arena)
            $arena->stop(true);
    }


    public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool
    {
        $this->commands->onCommand($sender, $command, $label, $args);
        return true;
    }

    /*
                      _
       __ _   _ __   (_)
      / _` | | '_ \  | |
     | (_| | | |_) | | |
      \__,_| | .__/  |_|
             |_|

    */

    /**
     * @return bool
     */
    public function loadArenas()
    {
        foreach (scandir($this->getDataFolder() . 'arenas/') as $arenadir) {
            if (!in_array($arenadir, [".", ".."]) && is_dir($this->getDataFolder() . 'arenas/' . $arenadir)) {
                if (is_file($this->getDataFolder() . 'arenas/' . $arenadir . '/settings.yml')) {
                    $config = new Config($this->getDataFolder() . 'arenas/' . $arenadir . '/settings.yml', Config::YAML, [
                        'name' => 'default',
                        'slot' => 0,
                        'world' => 'world_1',
                        'countdown' => 180,
                        'maxGameTime' => 600,
                        'void_Y' => 0,
                        'spawns' => [],
                    ]);
                    $this->arenas[$config->get('name')] = new SWarena($this, $config->get('name'), ($config->get('slot') + 0), $config->get('world'), ($config->get('countdown') + 0), ($config->get('maxGameTime') + 0), ($config->get('void_Y') + 0));
                    unset($config);
                } else {
                    return false;
                }
            }
        }
        return true;
    }


    /**
     * @return bool
     */
    public function loadSigns()
    {
        $this->signs = [];
        $r = $this->db->query("SELECT * FROM signs;");
        while ($array = $r->fetchArray(SQLITE3_ASSOC))
            $this->signs[$array['x'] . ':' . $array['y'] . ':' . $array['z'] . ':' . $array['world']] = $array['arena'];
        if (empty($this->signs))
            return false;
        else
            return true;
    }


    /**
     * @param string $SWname
     * @param int $x
     * @param int $y
     * @param int $z
     * @param string $world
     * @param bool $delete
     * @param bool $all
     * @return bool
     */
    public function setSign($SWname, $x, $y, $z, $world, $delete = false, $all = true)
    {
        if ($delete) {
            if ($all)
                $this->db->query("DELETE FROM signs;");
            else
                $this->db->query("DELETE FROM signs WHERE arena='$SWname';");
            if ($this->loadSigns())
                return true;
            else
                return false;
        } else {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO signs (arena, x, y, z, world) VALUES (:arena, :x, :y, :z, :world);");
            $stmt->bindValue(":arena", $SWname);
            $stmt->bindValue(":x", $x);
            $stmt->bindValue(":y", $y);
            $stmt->bindValue(":z", $z);
            $stmt->bindValue(":world", $world);
            $stmt->execute();
            if ($this->loadSigns())
                return true;
            else
                return false;
        }
    }


    /**
     * @param bool $all
     * @param string $SWname
     * @param int $players
     * @param int $slot
     * @param string $state
     */
    public function refreshSigns($all = true, $SWname = '', $players = 0, $slot = 0, $state = '§fTap to join')
    {
        if (!$all) {
            $ex = explode(':', array_search($SWname, $this->signs));
            if (count($ex) == 4) {
                $this->getServer()->getWorldManager()->loadWorld($ex[3]);
                if (($level = $this->getServer()->getWorldManager()->getWorldByName($ex[3])) instanceof World) {
                    $pos = new Position((int)$ex[0], (int)$ex[1], (int)$ex[2], $level);
					$tile = $level->getBlock($pos);
                    if ($tile !== null && $tile instanceof BaseSign) {
                        $text = $tile->getText();
                        $tile->setText(new SignText([
							$this->configs['1st_line'],
							str_replace('{SWNAME}', $SWname, $this->configs['2nd_line']),
							TextFormat::GREEN . $players . TextFormat::BOLD . TextFormat::DARK_GRAY . '/' . TextFormat::RESET . TextFormat::GREEN . $slot,
							$state
						]));
						$tile->getPosition()->getWorld()->setBlock($pos, $tile);
					} else {
						if(count($this->getServer()->getOnlinePlayers()) > 0)
							$this->getLogger()->critical('Can\'t get ' . $SWname . ' sign.Error finding sign on level: ' . $ex[3] . ' x:' . $ex[0] . ' y:' . $ex[1] . ' z:' . $ex[2]);
                    }
                }
            }
        } else {
            foreach ($this->signs as $key => $val) {
                $ex = explode(':', $key);
                $this->getServer()->getWorldManager()->loadWorld($ex[3]);
                if (($level = $this->getServer()->getWorldManager()->getWorldByName($ex[3])) instanceof World) {
                    $pos = new Position(intval($ex[0]), intval($ex[1]), intval($ex[2]), $level);
					$tile = $level->getBlock($pos);
					if ($tile instanceof BaseSign) {
                        $text = $tile->getText();
                        $tile->setText(new SignText([
							$this->configs['1st_line'],
							str_replace('{SWNAME}', $val, $this->configs['2nd_line']),
							TextFormat::GREEN . $this->arenas[$val]->getSlot(true) . TextFormat::BOLD . TextFormat::DARK_GRAY . '/' . TextFormat::RESET . TextFormat::GREEN . $this->arenas[$val]->getSlot(),
							$text->getLine(3)
						]));
						$tile->getPosition()->getWorld()->setBlock($pos, $tile);
					} else {
						if(count($this->getServer()->getOnlinePlayers()) > 0)
							$this->getLogger()->critical('Can\'t get ' . $val . ' sign.Error finding sign on level: ' . $ex[3] . ' x:' . $ex[0] . ' y:' . $ex[1] . ' z:' . $ex[2]);
                    }
                }
            }
        }
    }


    /**
     * @param string $playerName
     * @return bool
     */
    public function inArena($playerName = '')
    {
        foreach ($this->arenas as $a) {
            if ($a->inArena($playerName)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @return array
     */
    public function getChestContents() //TODO: **rewrite** this and let the owner decide the contents of the chest
    {
        $items = array(
            //ARMOR
            'armor' => array(
                array(
                    ItemIds::LEATHER_CAP,
                    ItemIds::LEATHER_TUNIC,
                    ItemIds::LEATHER_PANTS,
                    ItemIds::LEATHER_BOOTS
                ),
                array(
                    ItemIds::GOLD_HELMET,
                    ItemIds::GOLD_CHESTPLATE,
                    ItemIds::GOLD_LEGGINGS,
                    ItemIds::GOLD_BOOTS
                ),
                array(
                    ItemIds::CHAIN_HELMET,
                    ItemIds::CHAIN_CHESTPLATE,
                    ItemIds::CHAIN_LEGGINGS,
                    ItemIds::CHAIN_BOOTS
                ),
                array(
                    ItemIds::IRON_HELMET,
                    ItemIds::IRON_CHESTPLATE,
                    ItemIds::IRON_LEGGINGS,
                    ItemIds::IRON_BOOTS
                ),
                array(
                    ItemIds::DIAMOND_HELMET,
                    ItemIds::DIAMOND_CHESTPLATE,
                    ItemIds::DIAMOND_LEGGINGS,
                    ItemIds::DIAMOND_BOOTS
                )
            ),

            //WEAPONS
            'weapon' => array(
                array(
                    ItemIds::WOODEN_SWORD,
                    ItemIds::WOODEN_AXE,
                ),
                array(
                    ItemIds::GOLD_SWORD,
                    ItemIds::GOLD_AXE
                ),
                array(
                    ItemIds::STONE_SWORD,
                    ItemIds::STONE_AXE
                ),
                array(
                    ItemIds::IRON_SWORD,
                    ItemIds::IRON_AXE
                ),
                array(
                    ItemIds::DIAMOND_SWORD,
                    ItemIds::DIAMOND_AXE
                )
            ),

            //FOOD
            'food' => array(
                array(
                    ItemIds::RAW_PORKCHOP,
                    ItemIds::RAW_CHICKEN,
                    ItemIds::MELON_SLICE,
                    ItemIds::COOKIE
                ),
                array(
                    ItemIds::RAW_BEEF,
                    ItemIds::CARROT
                ),
                array(
                    ItemIds::APPLE,
                    ItemIds::GOLDEN_APPLE
                ),
                array(
                    ItemIds::BEETROOT_SOUP,
                    ItemIds::BREAD,
                    ItemIds::BAKED_POTATO
                ),
                array(
                    ItemIds::MUSHROOM_STEW,
                    ItemIds::COOKED_CHICKEN
                ),
                array(
                    ItemIds::COOKED_PORKCHOP,
                    ItemIds::STEAK,
                    ItemIds::PUMPKIN_PIE
                ),
            ),

            //THROWABLE
            'throwable' => array(
                array(
                    ItemIds::BOW,
                    ItemIds::ARROW
                ),
                array(
                    ItemIds::SNOWBALL
                ),
                array(
                    ItemIds::EGG
                )
            ),

            //BLOCKS
            'block' => array(
                ItemIds::STONE,
                ItemIds::WOODEN_PLANKS,
                ItemIds::COBBLESTONE,
                ItemIds::DIRT
            ),

            //OTHER
            'other' => array(
                array(
                    ItemIds::WOODEN_PICKAXE,
                    ItemIds::GOLD_PICKAXE,
                    ItemIds::STONE_PICKAXE,
                    ItemIds::IRON_PICKAXE,
                    ItemIds::DIAMOND_PICKAXE
                ),
                array(
                    ItemIds::STICK,
                    ItemIds::STRING
                )
            )
        );

        $templates = [];
        for ($i = 0; $i < 10; $i++) {

            $armorq = mt_rand(0, 1);
            $armortype = $items['armor'][mt_rand(0, (count($items['armor']) - 1))];
            $armor1 = array($armortype[mt_rand(0, (count($armortype) - 1))], 1);
            if ($armorq) {
                $armortype = $items['armor'][mt_rand(0, (count($items['armor']) - 1))];
                $armor2 = array($armortype[mt_rand(0, (count($armortype) - 1))], 1);
            } else {
                $armor2 = array(0, 1);
            }
            unset($armorq, $armortype);

            $weapontype = $items['weapon'][mt_rand(0, (count($items['weapon']) - 1))];
            $weapon = array($weapontype[mt_rand(0, (count($weapontype) - 1))], 1);
            unset($weapontype);

            $ftype = $items['food'][mt_rand(0, (count($items['food']) - 1))];
            $food = array($ftype[mt_rand(0, (count($ftype) - 1))], mt_rand(2, 5));
            unset($ftype);

            $add = mt_rand(0, 1);
            if ($add) {
                $tr = $items['throwable'][mt_rand(0, (count($items['throwable']) - 1))];
                if (count($tr) == 2) {
                    $throwable1 = array($tr[1], mt_rand(10, 20));
                    $throwable2 = array($tr[0], 1);
                } else {
                    $throwable1 = array(0, 1);
                    $throwable2 = array($tr[0], mt_rand(5, 10));
                }
                $other = array(0, 1);
            } else {
                $throwable1 = array(0, 1);
                $throwable2 = array(0, 1);
                $ot = $items['other'][mt_rand(0, (count($items['other']) - 1))];
                $other = array($ot[mt_rand(0, (count($ot) - 1))], 1);
            }
            unset($add, $tr, $ot);

            $block = array($items['block'][mt_rand(0, (count($items['block']) - 1))], 64);

            $contents = array(
                $armor1,
                $armor2,
                $weapon,
                $food,
                $throwable1,
                $throwable2,
                $block,
                $other
            );
            shuffle($contents);
            $fcontents = array(
                mt_rand(0, 1) => array_shift($contents),
                mt_rand(2, 4) => array_shift($contents),
                mt_rand(5, 9) => array_shift($contents),
                mt_rand(10, 14) => array_shift($contents),
                mt_rand(15, 16) => array_shift($contents),
                mt_rand(17, 19) => array_shift($contents),
                mt_rand(20, 23) => array_shift($contents),
                mt_rand(25, 26) => array_shift($contents),
            );
            $templates[] = $fcontents;

        }

        shuffle($templates);
        return $templates;
    }
}