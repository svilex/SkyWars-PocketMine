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

class RawSkin extends Skin
{
    /**
     * RawSkin constructor.
     * @param string $path
     * @param string $bytes
     */
    public function __construct($path, $bytes)
    {
        parent::__construct($path, $bytes);
    }


    /**
     * @return bool
     */
    final public function load()
    {
        if ($this->getType() != 2)
            return false;
        $bytes = @zlib_decode(@file_get_contents($this->getPath()));
        if ($this->setBytes($bytes))
            return true;
        return false;
    }


    final public function save()
    {
        if (!$this->ok || strtolower(pathinfo($this->getPath(false), PATHINFO_EXTENSION)) != 'skin' || !is_dir(pathinfo($this->getPath(false), PATHINFO_DIRNAME)))
            return false;
        if (is_file($this->getPath(false)))
            @unlink($this->getPath(false));
        @file_put_contents($this->getPath(false), @zlib_encode($this->getBytes(), ZLIB_ENCODING_DEFLATE, 9));
        if ($this->getType() == 2)
            return true;
        @unlink($this->getPath(false));
        return false;
    }
}