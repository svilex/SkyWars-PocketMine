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


use pocketmine\event\Listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockPlaceEvent;

use pocketmine\level\Position;
use pocketmine\level\Location;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;


class SWlistener implements Listener
{
    /** @var SWmain */
    private $pg;

    public function __construct(SWmain $plugin)
    {
        $this->pg = $plugin;
    }

    public function onSignChange(SignChangeEvent $ev)
    {
        if ($ev->getLine(0) != 'sw' or $ev->getPlayer()->isOp() == false)
            return;

        //Checks if the arena exists
        $SWname = TextFormat::clean(trim($ev->getLine(1)));
        if (!array_key_exists($SWname, $this->pg->arenas)) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'This arena doesn\'t exist, try ' . TextFormat::WHITE . '/sw create');
            return;
        }

        //Checks if a sign already exists for the arena
        if (in_array($SWname, $this->pg->signs)) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'A sign for this arena already exist, try ' . TextFormat::WHITE . '/sw signdelete');
            return;
        }

        //Checks if the sign is placed in a different world from the arena one
        $world = $ev->getPlayer()->getLevel()->getName();
        if ($world == $this->pg->arenas[$SWname]->getWorld()) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'You can\'t place the join sign in the arena');
            return;
        }

        //Checks arena spawns
        if (!$this->pg->arenas[$SWname]->setSpawn(true, '')) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'Not all the spawns are set in this arena, try ' . TextFormat::WHITE . ' /sw setspawn');
            return;
        }

        //Saves the sign
        if (!$this->pg->setSign($SWname, ($ev->getBlock()->getX() + 0), ($ev->getBlock()->getY() + 0), ($ev->getBlock()->getZ() + 0), $world))
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'An error occured, please contact the developer');
        else
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::GREEN . 'SW join sign created !');

        //Sets format
        $format = new \pocketmine\utils\Config($this->pg->getDataFolder() . 'sign_format.yml', 2, array(
            '1st line' => '§l§c[§bSW§c]',
            '2nd line' => '§l§e{SWNAME}',
        ));
        $ev->setLine(0, $format->get('1st line', '§l§c[§bSW§c]'));
        $ev->setLine(1, str_replace('{SWNAME}', $SWname, $format->get('2nd line', '§l§e{SWNAME}')));
        $ev->setLine(2, TextFormat::GREEN . '0' . TextFormat::BOLD . TextFormat::DARK_GRAY . '/' . TextFormat::RESET . TextFormat::GREEN . $this->pg->arenas[$SWname]->getSlot());
        $ev->setLine(3, TextFormat::WHITE . 'Tap to join');
        $this->pg->refreshSigns(true);
        unset($SWname, $world);
    }

    public function onInteract(PlayerInteractEvent $ev)
    {
        if ($ev->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK)
            return;

        //In-arena Tap
        foreach ($this->pg->arenas as $a) {
            if ($a->inArena($ev->getPlayer()->getName())) {
                if ($a->GAME_STATE == 0)
                    $ev->setCancelled();
                return;
            }
        }

        //Join sign Tap check
        $key = $ev->getBlock()->x . ':' . $ev->getBlock()->y . ':' . $ev->getBlock()->z . ':' . $ev->getBlock()->getLevel()->getName();
        if (array_key_exists($key, $this->pg->signs))
            $this->pg->arenas[$this->pg->signs[$key]]->join($ev->getPlayer());
        unset($key);
    }

    public function onMove(PlayerMoveEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->inArena($ev->getPlayer()->getName())) {
                if ($a->GAME_STATE == 0) {
                    $spawn = $a->getWorld(true, $ev->getPlayer()->getName());
                    if ($ev->getPlayer()->getPosition()->distanceSquared(new Position($spawn['x'], $spawn['y'], $spawn['z'])) > 2)
                        $ev->setTo(new Location($spawn['x'], $spawn['y'], $spawn['z']));
                    break;
                }
                if ($a->void >= $ev->getPlayer()->getFloorY() and $ev->getPlayer()->isAlive()) {
                    $event = new EntityDamageEvent($ev->getPlayer(), EntityDamageEvent::CAUSE_VOID, 10);
                    $ev->getPlayer()->attack($event->getFinalDamage(), $event);
                    unset($event);
                }
                break;
            }
        }
        //Checks if knockBack is enabled
        if ($this->pg->configs['sign_knockBack']) {
            foreach ($this->pg->signs as $key => $val) {
                $ex = explode(':', $key);
                if ($ev->getPlayer()->getLevel()->getName() == $ex[3]) {
                    $x = $ev->getPlayer()->getFloorX();
                    $z = $ev->getPlayer()->getFloorZ();
                    $radius = $this->pg->configs['knockBack_radius_from_sign'];
                    //If is inside the sign radius, knockBack
                    if (($x >= ($ex[0] - $radius) and $x <= ($ex[0] + $radius)) and ($z >= ($ex[2] - $radius) and $z <= ($ex[2] + $radius))) {
                        //If the block is not a sign, break
                        $block = $ev->getPlayer()->getLevel()->getBlock(new Vector3($ex[0], $ex[1], $ex[2]));
                        if ($block->getId() != 63 and $block->getId() != 68)
                            break;
                        //Finds sign yaw
                        switch ($block->getId()):
                            case 68:
                                switch ($block->getDamage()) {
                                    case 3:
                                        $yaw = 0;
                                        break;
                                    case 4:
                                        $yaw = 0x5a;
                                        break;
                                    case 2:
                                        $yaw = 0xb4;
                                        break;
                                    case 5:
                                        $yaw = 0x10e;
                                        break;
                                    default:
                                        $yaw = 0;
                                        break;
                                }
                                break;
                            case 63:
                                switch ($block->getDamage()) {
                                    case 0:
                                        $yaw = 0;
                                        break;
                                    case 1:
                                        $yaw = 22.5;
                                        break;
                                    case 2:
                                        $yaw = 0x2d;
                                        break;
                                    case 3:
                                        $yaw = 67.5;
                                        break;
                                    case 4:
                                        $yaw = 0x5a;
                                        break;
                                    case 5:
                                        $yaw = 112.5;
                                        break;
                                    case 6:
                                        $yaw = 0x87;
                                        break;
                                    case 7:
                                        $yaw = 157.5;
                                        break;
                                    case 8:
                                        $yaw = 0xb4;
                                        break;
                                    case 9:
                                        $yaw = 202.5;
                                        break;
                                    case 10:
                                        $yaw = 0xe1;
                                        break;
                                    case 11:
                                        $yaw = 247.5;
                                        break;
                                    case 12:
                                        $yaw = 0x10e;
                                        break;
                                    case 13:
                                        $yaw = 292.5;
                                        break;
                                    case 14:
                                        $yaw = 0x13b;
                                        break;
                                    case 15:
                                        $yaw = 337.5;
                                        break;
                                    default:
                                        $yaw = 0;
                                        break;
                                }
                                break;
                            default:
                                $yaw = 0;
                        endswitch;
                        //knockBack
                        $vector = (new Vector3((-(cos(deg2rad(90))) * sin(deg2rad($yaw))), (-sin(deg2rad(0))), ((cos(deg2rad(90))) * cos(deg2rad($yaw)))))->normalize();
                        $ev->getPlayer()->knockBack($ev->getPlayer(), 0, $vector->getX(), $vector->getZ(), ($this->pg->configs['knockBack_intensity'] / 0xa));
                        break;
                    }
                    unset($ex, $block, $x, $z, $radius, $yaw, $vector);
                }
            }
        }
    }

    public function onQuit(PlayerQuitEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->quit($ev->getPlayer()->getName(), true))
                break;
        }
    }

    public function onDeath(PlayerDeathEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->quit($ev->getEntity()->getName())) {
                $ev->setDeathMessage('');
                if (($ev->getEntity()->getLastDamageCause() instanceof EntityDamageByEntityEvent) and $ev->getEntity()->getLastDamageCause()->getDamager() instanceof \pocketmine\Player) {
                    foreach ($this->pg->getServer()->getLevelByName($a->getWorld())->getPlayers() as $p) {
                        $p->sendMessage(str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{KILLER}', $ev->getEntity()->getLastDamageCause()->getDamager()->getName(), str_replace('{PLAYER}', $ev->getEntity()->getName(), $this->pg->lang['player.kill']))));
                    }
                } elseif ($ev->getEntity()->getLastDamageCause()->getCause() == EntityDamageEvent::CAUSE_VOID) {
                    foreach ($this->pg->getServer()->getLevelByName($a->getWorld())->getPlayers() as $p) {
                        $p->sendMessage(str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{PLAYER}', $ev->getEntity()->getName(), $this->pg->lang['void.kill'])));
                    }
                } else {
                    foreach ($this->pg->getServer()->getLevelByName($a->getWorld())->getPlayers() as $p) {
                        $p->sendMessage(str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{PLAYER}', $ev->getEntity()->getName(), $this->pg->lang['game.left'])));
                    }
                }
                if (!$this->pg->configs['drops_in_arena'])
                    $ev->setDrops(array());
                break;
            }
        }
    }

    public function onDamage(EntityDamageEvent $ev)
    {
        if ($ev->getCause() == 0b100 or $ev->getCause() == 0b1100 or $ev->getCause() == 0b11) {
            $ev->setCancelled();
            return;
        }
        foreach ($this->pg->arenas as $a) {
            if ($ev->getEntity() instanceof Player) {
                if ($a->inArena($ev->getEntity()->getName())) {
                    if ($ev->getCause() == 0b1111 and $this->pg->configs['starvation_can_damage_inArena_players'] == false)
                        $ev->setCancelled();
                    if ($a->GAME_STATE == 0)
                        $ev->setCancelled();
                    break;
                }
            }
        }
    }

    public function onRespawn(PlayerRespawnEvent $ev)
    {
        if ($this->pg->configs['always_spawn_in_defaultLevel'])
            $ev->setRespawnPosition($this->pg->getServer()->getDefaultLevel()->getSpawnLocation());
        //Removes player things
        if ($this->pg->configs['clear_inventory_on_respawn&join'])
            $ev->getPlayer()->getInventory()->clearAll();
        if ($this->pg->configs['clear_effects_on_respawn&join'])
            $ev->getPlayer()->removeAllEffects();
    }

    public function onBreak(BlockBreakEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->inArena($ev->getPlayer()->getName())) {
                if ($a->GAME_STATE == 0)
                    $ev->setCancelled();
                break;
            }
        }
        if (!$ev->getPlayer()->isOp())
            return;
        $key = (($ev->getBlock()->getX() + 0) . ':' . ($ev->getBlock()->getY() + 0) . ':' . ($ev->getBlock()->getZ() + 0) . ':' . $ev->getPlayer()->getLevel()->getName());
        if (array_key_exists($key, $this->pg->signs)) {
            $this->pg->arenas[$this->pg->signs[$key]]->stop();
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::GREEN . 'Arena reloaded !');
            if ($this->pg->setSign($this->pg->signs[$key], 0, 0, 0, 'world', true, false)) {
                $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::GREEN . 'SW join sign deleted !');
            } else {
                $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'An error occured, please contact the developer');
            }
        }
        unset($key);
    }

    public function onPlace(BlockPlaceEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->inArena($ev->getPlayer()->getName())) {
                if ($a->GAME_STATE == 0)
                    $ev->setCancelled();
                break;
            }
        }
    }

    public function onCommand(PlayerCommandPreprocessEvent $ev)
    {
        $command = strtolower($ev->getMessage());
        if ($command{0} == '/') {
            $command = explode(' ', $command)[0];
            foreach ($this->pg->arenas as $a) {
                if ($a->inArena($ev->getPlayer()->getName())) {
                    if (in_array($command, $this->pg->configs['banned_commands_while_in_game'])) {
                        $ev->getPlayer()->sendMessage(str_replace('@', '§', $this->pg->configs['banned_command_message']));
                        $ev->setCancelled();
                    }
                    break;
                }
            }
        }
        unset($command);
    }
}