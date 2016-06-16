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

namespace svile\sw\utils\skin;


use pocketmine\Player;


abstract class Skin
{
    /** @var string */
    private $bytes = '';
    /** @var string */
    private $path = '';
    /** @var bool */
    protected $ok = false;


    /**
     * Skin constructor.
     * @param string $path
     * @param string $bytes
     */
    public function __construct($path, $bytes)
    {
        $this->ok = $this->setPath((string)$path) && $this->setBytes((string)$bytes);
    }


    public function __toString()
    {
        return basename($this->getPath());
    }


    /**
     * @param bool $real
     * @return string
     */
    final public function getPath($real = true)
    {
        if ($real)
            return (string)realpath($this->path);
        return (string)$this->path;
    }


    /**
     * @param string $path
     * @return bool
     */
    final public function setPath($path)
    {
        if (!is_dir(pathinfo($path, PATHINFO_DIRNAME)))
            return false;
        $this->path = (string)$path;
        $this->ok = (strlen($this->bytes) == 8192 || strlen($this->bytes) == 16384);
        return true;
    }


    /**
     * @return string|bool
     */
    final public function getBytes()
    {
        if (strlen($this->bytes) != 8192 && strlen($this->bytes) != 16384)
            return false;
        return (string)$this->bytes;
    }


    /**
     * @param string $bytes
     * @return bool
     */
    final public function setBytes($bytes)
    {
        if (strlen($bytes) != 8192 && strlen($bytes) != 16384)
            return false;
        $this->bytes = (string)$bytes;
        $this->ok = is_dir(pathinfo($this->path, PATHINFO_DIRNAME));
        return true;
    }


    /**
     * @return int
     *
     * 0 = NULL
     * 1 = PNG
     * 2 = RAW
     */
    final public function getType()
    {
        if (!$this->ok || !is_file($this->getPath()))
            return 0;
        $ext = strtolower(pathinfo($this->getPath(), PATHINFO_EXTENSION));
        if ($ext == 'png' && extension_loaded('gd')) {
            $f = fopen($this->getPath(), 'rb');
            $header = fread($f, 8);
            fclose($f);
            $png = @getimagesize($this->getPath());
            if ($png[0] == 64 && ($png[1] == 32 || $png[1] == 64) && $png[2] == IMAGETYPE_PNG && $header == "\x89PNG\x0d\x0a\x1a\x0a")
                return 1;
        } elseif ($ext == 'skin') {
            $byteslen = strlen(@zlib_decode(@file_get_contents($this->getPath())));
            if ($byteslen == 8192 || $byteslen == 16384)
                return 2;
        }
        return 0;
    }


    /**
     * @param Player $p
     * @param bool $slim
     * @return bool
     */
    final public function apply(Player $p, $slim = false)
    {
        if (!$this->ok || !$this->load())
            return false;
        (bool)$slim ? $slim = 'Standard_CustomSlim' : $slim = 'Standard_Custom';
        $p->setSkin($this->getBytes(), $slim);
        //From ClearSky , needed for Genisys ...
        //---
        foreach ($p->getServer()->getOnlinePlayers() as $player) {
            $p->getServer()->removePlayerListData($player->getUniqueId());
        }
        foreach ($p->getServer()->getOnlinePlayers() as $player) {
            $p->getServer()->sendFullPlayerListData($player);
        }
        //---
        $p->despawnFromAll();
        $p->spawnToAll();
        return true;
    }


    abstract public function load();


    abstract public function save();
}