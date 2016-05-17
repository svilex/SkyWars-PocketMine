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
 * - Ahmet , thanks a lot !
 * - no one
 * - no one
 *
 */

namespace svile\sw\utils;


use pocketmine\entity\Human;


abstract class Skin
{
    /** @var string */
    private $bytes = '';
    /** @var string */
    private $path = '';

    /**
     * Skin constructor.
     * @param string $bytes
     * @param string $path
     */
    public function __construct($bytes = '', $path = '')
    {
        return $this->setBytes((string)$bytes) && $this->setPath((string)$path);
    }

    public function __toString()
    {
        return \basename($this->getPath());
    }

    /**
     * @return string
     */
    final public function getBytes()
    {
        return (string)$this->bytes;
    }

    /**
     * @param string $bytes
     * @return bool
     */
    final public function setBytes($bytes = '')
    {
        if (\strlen($bytes) != 64 * 64 * 4 && \strlen($bytes) != 64 * 32 * 4)
            return false;
        $this->bytes = (string)$bytes;
        return true;
    }

    /**
     * @return string
     */
    final public function getPath()
    {
        return (string)\realpath($this->path);
    }

    /**
     * @param string $path
     * @return bool
     */
    final public function setPath($path = '')
    {
        if (!\is_dir(\pathinfo($path, PATHINFO_DIRNAME)))
            return false;
        $this->path = (string)$path;
        return true;
    }

    /**
     * @param Human $h
     * @param bool $slim
     */
    final public function apply(Human $h, $slim = false)
    {
        $h->setSkin($this->getBytes(), (bool)$slim);
    }

    abstract public function load();

    abstract public function save();
}