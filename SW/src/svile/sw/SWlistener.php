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


use pocketmine\event\Listener;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
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
        if ($ev->getLine(0) != 'sw' || $ev->getPlayer()->isOp() == false)
            return;

        //Checks if the arena exists
        $SWname = TextFormat::clean(trim($ev->getLine(1)));
        if (!array_key_exists($SWname, $this->pg->arenas)) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'This arena doesn\'t exist, try ' . TextFormat::WHITE . '/sw create');
            return;
        }

        //Checks if a sign already exists for the arena
        if (in_array($SWname, $this->pg->signs)) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'A sign for this arena already exist, try ' . TextFormat::WHITE . '/sw signdelete');
            return;
        }

        //Checks if the sign is placed inside arenas
        $world = $ev->getPlayer()->getLevel()->getName();
        foreach ($this->pg->arenas as $name => $arena) {
            if ($world == $arena->getWorld()) {
                $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'You can\'t place the join sign inside arenas');
                return;
            }
        }

        //Checks arena spawns
        if (!$this->pg->arenas[$SWname]->checkSpawns()) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'Not all the spawns are set in this arena, try ' . TextFormat::WHITE . ' /sw setspawn');
            return;
        }

        //Saves the sign
        if (!$this->pg->setSign($SWname, ($ev->getBlock()->getX() + 0), ($ev->getBlock()->getY() + 0), ($ev->getBlock()->getZ() + 0), $world))
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'An error occured, please contact the developer');
        else
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::GREEN . 'SW join sign created !');

        //Sets sign format
        $ev->setLine(0, $this->pg->configs['1st_line']);
        $ev->setLine(1, str_replace('{SWNAME}', $SWname, $this->pg->configs['2nd_line']));
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
            if ($t = $a->inArena($ev->getPlayer()->getName())) {
                if ($t == 2)
                    $ev->setCancelled();
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


    public function onLevelChange(EntityLevelChangeEvent $ev)
    {
        if ($ev->getEntity() instanceof Player) {
            foreach ($this->pg->arenas as $a) {
                if ($a->inArena($ev->getEntity()->getName())) {
                    $ev->setCancelled();
                    break;
                }
            }
        }
    }


    public function onTeleport(EntityTeleportEvent $ev)
    {
        if ($ev->getEntity() instanceof Player) {
            foreach ($this->pg->arenas as $a) {
                if ($a->inArena($ev->getEntity()->getName())) {
                    //Allow near teleport
                    if ($ev->getFrom()->distanceSquared($ev->getTo()) < 20)
                        break;
                    $ev->setCancelled();
                    break;
                }
            }
        }
    }


    public function onDropItem(PlayerDropItemEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if (($f = $a->inArena($ev->getPlayer()->getName()))) {
                if ($f == 2) {
                    $ev->setCancelled();
                    break;
                }
                if (!$this->pg->configs['player.drop.item']) {
                    $ev->setCancelled();
                    break;
                }
                break;
            }
        }
    }


    public function onPickUp(InventoryPickupItemEvent $ev)
    {
        if (($p = $ev->getInventory()->getHolder()) instanceof Player) {
            foreach ($this->pg->arenas as $a) {
                if ($f = $a->inArena($p->getName())) {
                    if ($f == 2)
                        $ev->setCancelled();
                    break;
                }
            }
        }
    }


    public function onItemHeld(PlayerItemHeldEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($f = $a->inArena($ev->getPlayer()->getName())) {
                if ($f == 2) {
                    if (($ev->getItem()->getId() . ':' . $ev->getItem()->getDamage()) == $this->pg->configs['spectator.quit.item'])
                        $a->closePlayer($ev->getPlayer());
                    $ev->setCancelled();
                    $ev->getPlayer()->getInventory()->setHeldItemIndex(1);
                }
                break;
            }
        }
    }


    public function onMove(PlayerMoveEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->inArena($ev->getPlayer()->getName())) {
                if ($a->GAME_STATE == 0) {
                    $spawn = $a->getWorld(true, $ev->getPlayer()->getName());
                    if ($ev->getPlayer()->getPosition()->distanceSquared(new Position($spawn['x'], $spawn['y'], $spawn['z'])) > 4)
                        $ev->setTo(new Location($spawn['x'], $spawn['y'], $spawn['z'], $spawn['yaw'], $spawn['pitch']));
                    break;
                }
                if ($a->void >= $ev->getPlayer()->getFloorY() && $ev->getPlayer()->isAlive()) {
                    $event = new EntityDamageEvent($ev->getPlayer(), EntityDamageEvent::CAUSE_VOID, 10);
                    $ev->getPlayer()->attack($event->getFinalDamage(), $event);
                    unset($event);
                }
                return;
            }
        }
        //Checks if knockBack is enabled
        if ($this->pg->configs['sign.knockBack']) {
            foreach ($this->pg->signs as $key => $val) {
                $ex = explode(':', $key);
                $pl = $ev->getPlayer();
                if ($pl->getLevel()->getName() == $ex[3]) {
                    $x = (int)$pl->getFloorX();
                    $y = (int)$pl->getFloorY();
                    $z = (int)$pl->getFloorZ();
                    $radius = (int)$this->pg->configs['knockBack.radius.from.sign'];
                    //If is inside the sign radius, knockBack
                    if (($x >= ($ex[0] - $radius) && $x <= ($ex[0] + $radius)) && ($z >= ($ex[2] - $radius) && $z <= ($ex[2] + $radius)) && ($y >= ($ex[1] - $radius) && $y <= ($ex[1] + $radius))) {
                        //If the block is not a sign, break
                        $block = $pl->getLevel()->getBlock(new Vector3($ex[0], $ex[1], $ex[2]));
                        if ($block->getId() != 63 && $block->getId() != 68)
                            break;
                        //Max $i should be 90 to avoid bugs-lag, yes 90 is a magic number :P
                        $i = (int)$this->pg->configs['knockBack.intensity'];
                        if ($this->pg->configs['knockBack.follow.sign.direction']) {
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
                            //knockBack sign direction
                            $vector = (new Vector3(-sin(deg2rad($yaw)), 0, cos(deg2rad($yaw))))->normalize();
                            $pl->knockBack($pl, 0, $vector->x, $vector->z, ($i / 0xa));
                        } else {
                            //knockBack sign center
                            $pl->knockBack($pl, 0, ($pl->x - ($block->x + 0.5)), ($pl->z - ($block->z + 0.5)), ($i / 0xa));
                        }
                        break;
                    }
                    unset($ex, $pl, $x, $y, $z, $radius, $block, $i, $yaw);
                }
            }
        }
    }


    public function onQuit(PlayerQuitEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($a->closePlayer($ev->getPlayer(), true))
                break;
        }
    }


    public function onDeath(PlayerDeathEvent $event)
    {
        if ($event->getEntity() instanceof Player) {
            $p = $event->getEntity();
            foreach ($this->pg->arenas as $a) {
                if ($a->closePlayer($p)) {
                    $event->setDeathMessage('');
                    $cause = $event->getEntity()->getLastDamageCause()->getCause();
                    $ev = $event->getEntity()->getLastDamageCause();
                    $count = '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']';

                    switch ($cause):


                        case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                            if ($ev instanceof EntityDamageByEntityEvent) {
                                $d = $ev->getDamager();
                                if ($d instanceof Player)
                                    $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getDisplayName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.player'])));
                                elseif ($d instanceof \pocketmine\entity\Living)
                                    $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getNameTag() !== '' ? $d->getNameTag() : $d->getName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.player'])));
                                else
                                    $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', 'Unknown', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.player'])));
                            }
                            break;


                        case EntityDamageEvent::CAUSE_PROJECTILE:
                            if ($ev instanceof EntityDamageByEntityEvent) {
                                $d = $ev->getDamager();
                                if ($d instanceof Player)
                                    $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getDisplayName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.arrow'])));
                                elseif ($d instanceof \pocketmine\entity\Living)
                                    $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getNameTag() !== '' ? $d->getNameTag() : $d->getName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.arrow'])));
                                else
                                    $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', 'Unknown', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.arrow'])));
                            }
                            break;


                        case EntityDamageEvent::CAUSE_VOID:
                            $message = str_replace('{COUNT}', $count, str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.void']));
                            break;


                        case EntityDamageEvent::CAUSE_LAVA:
                            $message = str_replace('{COUNT}', $count, str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.lava']));
                            break;


                        default:
                            $message = str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['game.left']));
                            break;


                    endswitch;

                    foreach ($this->pg->getServer()->getLevelByName($a->getWorld())->getPlayers() as $pl)
                        $pl->sendMessage($message);

                    if (!$this->pg->configs['drops.on.death'])
                        $event->setDrops([]);
                    break;
                }
            }
        }
    }


    public function onDamage(EntityDamageEvent $ev)
    {
        if ($ev->getEntity() instanceof Player) {
            $p = $ev->getEntity();
            foreach ($this->pg->arenas as $a) {
                if ($f = $a->inArena($p->getName())) {
                    if ($f != 1) {
                        $ev->setCancelled();
                        break;
                    }
                    if ($ev instanceof EntityDamageByEntityEvent && ($d = $ev->getDamager()) instanceof Player) {
                        if (($f = $a->inArena($d->getName())) == 2 || $f == 0) {
                            $ev->setCancelled();
                            break;
                        }
                    }
                    $cause = (int)$ev->getCause();
                    if (in_array($cause, $this->pg->configs['damage.cancelled.causes'])) {
                        $ev->setCancelled();
                        break;
                    }
                    if ($a->GAME_STATE == 0) {
                        $ev->setCancelled();
                        break;
                    }

                    //SPECTATORS
                    $spectate = (bool)$this->pg->configs['death.spectator'];
                    if ($spectate && !$ev->isCancelled()) {
                        if (($p->getHealth() - $ev->getFinalDamage()) <= 0) {
                            $ev->setCancelled();
                            //FAKE KILL PLAYER MSG
                            $count = '[' . ($a->getSlot(true) - 1) . '/' . $a->getSlot() . ']';

                            switch ($cause):


                                case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                                    if ($ev instanceof EntityDamageByEntityEvent) {
                                        $d = $ev->getDamager();
                                        if ($d instanceof Player)
                                            $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getDisplayName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.player'])));
                                        elseif ($d instanceof \pocketmine\entity\Living)
                                            $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getNameTag() !== '' ? $d->getNameTag() : $d->getName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.player'])));
                                        else
                                            $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', 'Unknown', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.player'])));
                                    }
                                    break;


                                case EntityDamageEvent::CAUSE_PROJECTILE:
                                    if ($ev instanceof EntityDamageByEntityEvent) {
                                        $d = $ev->getDamager();
                                        if ($d instanceof Player)
                                            $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getDisplayName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.arrow'])));
                                        elseif ($d instanceof \pocketmine\entity\Living)
                                            $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', $d->getNameTag() !== '' ? $d->getNameTag() : $d->getName(), str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.arrow'])));
                                        else
                                            $message = str_replace('{COUNT}', $count, str_replace('{KILLER}', 'Unknown', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.arrow'])));
                                    }
                                    break;


                                case EntityDamageEvent::CAUSE_VOID:
                                    $message = str_replace('{COUNT}', $count, str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.void']));
                                    break;


                                case EntityDamageEvent::CAUSE_LAVA:
                                    $message = str_replace('{COUNT}', $count, str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['death.lava']));
                                    break;


                                default:
                                    $message = str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['game.left']));
                                    break;


                            endswitch;

                            foreach ($p->getLevel()->getPlayers() as $pl)
                                $pl->sendMessage($message);

                            //DROPS
                            if ($this->pg->configs['drops.on.death']) {
                                foreach ($p->getDrops() as $item) {
                                    $p->getLevel()->dropItem($p, $item);
                                }
                            }

                            //CLOSE
                            $a->closePlayer($p, false, true);
                        }
                    }
                    break;
                }
            }
        }
    }


    public function onRespawn(PlayerRespawnEvent $ev)
    {
        if ($this->pg->configs['always.spawn.in.defaultLevel'])
            $ev->setRespawnPosition($this->pg->getServer()->getDefaultLevel()->getSpawnLocation());
        //Removes player things
        if ($this->pg->configs['clear.inventory.on.respawn&join'])
            $ev->getPlayer()->getInventory()->clearAll();
        if ($this->pg->configs['clear.effects.on.respawn&join'])
            $ev->getPlayer()->removeAllEffects();
    }


    public function onBreak(BlockBreakEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($t = $a->inArena($ev->getPlayer()->getName())) {
                if ($t == 2)
                    $ev->setCancelled();
                if ($a->GAME_STATE == 0)
                    $ev->setCancelled();
                break;
            }
        }
        if (!$ev->getPlayer()->isOp())
            return;
        $key = (($ev->getBlock()->getX() + 0) . ':' . ($ev->getBlock()->getY() + 0) . ':' . ($ev->getBlock()->getZ() + 0) . ':' . $ev->getPlayer()->getLevel()->getName());
        if (array_key_exists($key, $this->pg->signs)) {
            $this->pg->arenas[$this->pg->signs[$key]]->stop(true);
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::GREEN . 'Arena reloaded !');
            if ($this->pg->setSign($this->pg->signs[$key], 0, 0, 0, 'world', true, false)) {
                $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::GREEN . 'SW join sign deleted !');
            } else {
                $ev->getPlayer()->sendMessage(TextFormat::AQUA . '>' . TextFormat::RED . 'An error occured, please contact the developer');
            }
        }
        unset($key);
    }


    public function onPlace(BlockPlaceEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($t = $a->inArena($ev->getPlayer()->getName())) {
                if ($t == 2)
                    $ev->setCancelled();
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
            if ($this->pg->inArena($ev->getPlayer()->getName())) {
                if (in_array($command, $this->pg->configs['banned.commands.while.in.game'])) {
                    $ev->getPlayer()->sendMessage($this->pg->lang['banned.command.msg']);
                    $ev->setCancelled();
                }
            }
        }
        unset($command);
    }
}