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
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\utils\SignText;
use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeFactory;
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

use pocketmine\world\Position;
use pocketmine\entity\Location;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;


class SWlistener implements Listener
{
    public function __construct(
        private SWmain $pg
    ){
        // NOOP
    }

    public function onSignChange(SignChangeEvent $ev)
    {
		$text = $ev->getNewText();
		// $text = $ev->getSign()->getText();
        if ($text->getLine(0) !== 'sw'){
            var_dump("line " . $text->getLine(0));
            return;
        }
        if (!$ev->getPlayer()->hasPermission("skywars.setsign")){
            var_dump("permission");
            return;
        }

        //Checks if the arena exists
        $SWname = TextFormat::clean(trim($text->getLine(1)));
        if (!array_key_exists($SWname, $this->pg->arenas)) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'This arena doesn\'t exist, try ' . TextFormat::WHITE . '/sw create');
            return;
        }

        //Checks if a sign already exists for the arena
        if (in_array($SWname, $this->pg->signs)) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'A sign for this arena already exist, try ' . TextFormat::WHITE . '/sw signdelete');
            return;
        }

        //Checks if the sign is placed inside arenas
        $world = $ev->getPlayer()->getWorld()->getFolderName();
        foreach ($this->pg->arenas as $name => $arena) {
            if ($world == $arena->getWorld()) {
                $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'You can\'t place the join sign inside arenas');
                return;
            }
        }

        //Checks arena spawns
        if (!$this->pg->arenas[$SWname]->checkSpawns()) {
            $ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'Not all the spawns are set in this arena, try ' . TextFormat::WHITE . ' /sw setspawn');
            return;
        }

        //Saves the sign
        if (!$this->pg->setSign($SWname, $ev->getBlock()->getPosition()->getX(), $ev->getBlock()->getPosition()->getY(), $ev->getBlock()->getPosition()->getZ(), $world)){
			$ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::RED . 'An error occured, please contact the developer');
        } else {
			$ev->getPlayer()->sendMessage(TextFormat::AQUA . '→' . TextFormat::GREEN . 'SW join sign created !');
		}

        $block = $ev->getBlock();
        var_dump($block::class);
        if(!$block instanceof BaseSign)
            return;

        //Sets sign format
		$block->setText(new SignText([
			$this->pg->configs['1st_line'],
			str_replace('{SWNAME}', $SWname, $this->pg->configs['2nd_line']),
			TextFormat::GREEN . '0' . TextFormat::BOLD . TextFormat::DARK_GRAY . '/' . TextFormat::RESET . TextFormat::GREEN . $this->pg->arenas[$SWname]->getSlot(),
			TextFormat::WHITE . 'Tap to join'
		]));
		
		$ev->getBlock()->getPosition()->getWorld()->setBlock($ev->getBlock()->getPosition()->asVector3(), $ev->getBlock());
		
		$this->pg->getLogger()->info("New Sign At:" . join(":", [$ev->getBlock()->getPosition()->getX(), $ev->getBlock()->getPosition()->getY(), $ev->getBlock()->getPosition()->getZ()]));
		
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
                    $ev->cancel();
                if ($a->GAME_STATE == 0)
                    $ev->cancel();
                return;
            }
        }

        //Join sign Tap check
        $key = $ev->getBlock()->getPosition()->x . ':' . $ev->getBlock()->getPosition()->y . ':' . $ev->getBlock()->getPosition()->z . ':' . $ev->getBlock()->getPosition()->getWorld()->getFolderName();
        if (array_key_exists($key, $this->pg->signs))
            $this->pg->arenas[$this->pg->signs[$key]]->join($ev->getPlayer());
        unset($key);
    }
    
    // public function onLevelChange(EntityLevelChangeEvent $ev)
    // {
    //     if ($ev->getEntity() instanceof Player) {
    //         foreach ($this->pg->arenas as $a) {
    //             if ($a->inArena($ev->getEntity()->getName())) {
    //                 $ev->setCancelled();
    //                 $ev->cancel();
    //                 break;
    //             }
    //         }
    //     }
    // }


    public function onTeleport(EntityTeleportEvent $ev)
    {
        $player = $ev->getEntity();
        if ($player instanceof Player) {
            foreach ($this->pg->arenas as $a) {
                if ($a->inArena($player->getName())) {
                    //Allow near teleport
                    if ($ev->getFrom()->distanceSquared($ev->getTo()) < 20)
                        break;
                    $ev->cancel();
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
                    $ev->cancel();
                    break;
                }
                if (!$this->pg->configs['player.drop.item']) {
                    $ev->cancel();
                    break;
                }
                break;
            }
        }
    }


    public function onPickUp(EntityItemPickupEvent $ev)
    {
        $inv = $ev->getInventory();
        if($inv instanceof PlayerInventory){
            if (($p = $inv->getHolder()) instanceof Player) {
                foreach ($this->pg->arenas as $a) {
                    if ($f = $a->inArena($p->getName())) {
                        if ($f == 2)
                            $ev->cancel();
                        break;
                    }
                }
            }
        }
    }


    public function onItemHeld(PlayerItemHeldEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($f = $a->inArena($ev->getPlayer()->getName())) {
                if ($f == 2) {
                    if (($ev->getItem()->getId() . ':' . $ev->getItem()->getMeta()) == $this->pg->configs['spectator.quit.item'])
                        $a->closePlayer($ev->getPlayer());
                    $ev->cancel();
                    $ev->getPlayer()->getInventory()->setHeldItemIndex(1);
                }
                break;
            }
        }
    }


    public function onMove(PlayerMoveEvent $ev)
    {
        $player = $ev->getPlayer();
        $from = $ev->getFrom();
		$to = $ev->getTo();
        foreach ($this->pg->arenas as $a) {
            if ($a->inArena($ev->getPlayer()->getName())) {
                if ($a->GAME_STATE == 0) {
                    $spawn = $a->getWorld(true, $ev->getPlayer()->getName());
                    if ($ev->getPlayer()->getPosition()->distanceSquared(new Vector3($spawn['x'], $spawn['y'], $spawn['z'])) > 4)
                        $ev->setTo(new Location($spawn['x'], $spawn['y'], $spawn['z'], $ev->getPlayer()->getWorld(), $spawn['yaw'], $spawn['pitch']));
                    break;
                }
                if ($a->void >= $ev->getPlayer()->getPosition()->getFloorY() && $ev->getPlayer()->isAlive()) {
                    $event = new EntityDamageEvent($ev->getPlayer(), EntityDamageEvent::CAUSE_VOID, 10);
                    $ev->getPlayer()->attack($event);
                    unset($event);
                }
                return;
            }
        }
        //Checks if knockBack is enabled
        if ($this->pg->configs['sign.knockBack']) {
            $radius = intval($this->pg->configs['knockBack.radius.from.sign']);
            foreach ($this->getNearbySigns($to->asPosition(), $radius) as $pos) {
                $i = intval($this->pg->configs['knockBack.intensity']);
                $direction = $player->getDirectionVector();
                $dx = $direction->getX();
                $dz = $direction->getZ();
                $this->knockBack($player, 0, -$dx, -$dz, 0.3);// huh did u remember that? 'pm3 knockback better'
                break;
            }
        }
    }

    public function getNearbySigns(Position $pos, int $radius, &$arena = null)
    {
        $pos->x = floor($pos->x);
        $pos->y = floor($pos->y);
        $pos->z = floor($pos->z);

        $level = $pos->getWorld()->getFolderName();

        $minX = $pos->x - $radius;
        $minY = $pos->y - $radius;
        $minZ = $pos->z - $radius;

        $maxX = $pos->x + $radius;
        $maxY = $pos->y + $radius;
        $maxZ = $pos->z + $radius;
				
		$signs = [];

        foreach ($this->pg->signs as $key => $val) {
            $ex = explode(':', $key);
            if ($pos->getWorld()->getFolderName() == $ex[3]) {
                $pos_ = new Position(intval($ex[0]), intval($ex[1]), intval($ex[2]), $pos->getWorld());
                if($pos_->distance($pos) <= $radius){
                    $signs[] = $pos_;
                }
            }
        }
		
		return $signs;
    }
	
	public function knockBack(Player $attacker, float $damage, float $x, float $z, float $base = 0.4) : void{
		$f = sqrt($x * $x + $z * $z);
		if($f <= 0){
			return;
		}
		if(mt_rand() / mt_getrandmax() > AttributeFactory::getInstance()->mustGet(Attribute::KNOCKBACK_RESISTANCE)->getValue()){
			$f = 1 / $f;

			$motion = clone $attacker->getMotion();

			$motion->x /= 2;
			$motion->y /= 2;
			$motion->z /= 2;
			$motion->x += $x * $f * $base;
			$motion->y += $base;
			$motion->z += $z * $f * $base;

			if($motion->y > $base){
				$motion->y = $base;
			}

			$attacker->setMotion($motion);
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
                    $message = str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['game.left']));

                    switch ($cause){


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


                    }

                    foreach ($this->pg->getServer()->getWorldManager()->getWorldByName($a->getWorld())->getPlayers() as $pl)
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
                        $ev->cancel();
                        break;
                    }
                    if ($ev instanceof EntityDamageByEntityEvent && ($d = $ev->getDamager()) instanceof Player) {
                        if (($f = $a->inArena($d->getName())) == 2 || $f == 0) {
                            $ev->cancel();
                            break;
                        }
                    }
                    $cause = (int)$ev->getCause();
                    if (in_array($cause, $this->pg->configs['damage.cancelled.causes'])) {
                        $ev->cancel();
                        break;
                    }
                    if ($a->GAME_STATE == 0 || $a->GAME_STATE == 2) {
                        $ev->cancel();
                        break;
                    }

                    //SPECTATORS
                    $spectate = (bool)$this->pg->configs['death.spectator'];
                    if ($spectate && !$ev->isCancelled()) {
                        if (($p->getHealth() - $ev->getFinalDamage()) <= 0) {
                            $ev->cancel();
                            //FAKE KILL PLAYER MSG
                            $count = '[' . ($a->getSlot(true) - 1) . '/' . $a->getSlot() . ']';
                            $message = str_replace('{COUNT}', '[' . $a->getSlot(true) . '/' . $a->getSlot() . ']', str_replace('{PLAYER}', $p->getDisplayName(), $this->pg->lang['game.left']));
                            
                            switch ($cause){


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
                            }

                            foreach ($p->getWorld()->getPlayers() as $pl)
                                $pl->sendMessage($message);

                            //DROPS
                            if ($this->pg->configs['drops.on.death']) {
                                foreach ($p->getDrops() as $item) {
                                    $p->getWorld()->dropItem($p, $item);
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
            $ev->setRespawnPosition($this->pg->getServer()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
        //Removes player things
        if ($this->pg->configs['clear.inventory.on.respawn&join'])
            $ev->getPlayer()->getInventory()->clearAll();
        if ($this->pg->configs['clear.effects.on.respawn&join'])
            $ev->getPlayer()->getEffects()->clear();
    }


    public function onBreak(BlockBreakEvent $ev)
    {
        foreach ($this->pg->arenas as $a) {
            if ($t = $a->inArena($ev->getPlayer()->getName())) {
                if ($t == 2)
                    $ev->cancel();
                if ($a->GAME_STATE == 0)
                    $ev->cancel();
                break;
            }
        }
        if (!$ev->getPlayer()->hasPermission("skywars.removesign"))
            return;
        $key = (($ev->getBlock()->getPosition()->getX() + 0) . ':' . ($ev->getBlock()->getPosition()->getY() + 0) . ':' . ($ev->getBlock()->getPosition()->getZ() + 0) . ':' . $ev->getPlayer()->getWorld()->getFolderName());
        if (array_key_exists($key, $this->pg->signs)) {
            $this->pg->arenas[$this->pg->signs[$key]]->stop(true);
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
            if ($t = $a->inArena($ev->getPlayer()->getName())) {
                if ($t == 2)
                    $ev->cancel();
                if ($a->GAME_STATE == 0)
                    $ev->cancel();
                break;
            }
        }
    }


    public function onCommand(PlayerCommandPreprocessEvent $ev)
    {
        $command = strtolower($ev->getMessage());
        if ($command[0] == '/') {
            $command = explode(' ', $command)[0];
            if ($this->pg->inArena($ev->getPlayer()->getName())) {
                if (in_array($command, $this->pg->configs['banned.commands.while.in.game'])) {
                    $ev->getPlayer()->sendMessage($this->pg->lang['banned.command.msg']);
                    $ev->cancel();
                }
            }
        }
        unset($command);
    }
}