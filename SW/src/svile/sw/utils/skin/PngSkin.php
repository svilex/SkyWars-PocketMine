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

namespace svile\sw\utils\skin;

class PngSkin extends Skin
{
    /**
     * PngSkin constructor.
     * @param string $path
     * @param string $bytes
     */
    public function __construct($path, $bytes = '')
    {
        parent::__construct($path, $bytes);
    }

    /**
     * @return bool
     */
    public function load()
    {
        if ($this->getType() != 1)
            return false;
        $img = @imagecreatefrompng($this->getPath());
        if (!$img)
            return false;
        $bytes = '';
        for ($y = 0; $y < 33; $y++) {
            for ($x = 0; $x < 65; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                //$a = ;
                $bytes .= chr($r) . chr($g) . chr($b) . chr(255);
            }
        }
        imagedestroy($img);
        if ($this->setBytes($bytes))
            return true;
        return false;
    }

    public function save()
    {
        if (strtolower(pathinfo($this->getPath(false), PATHINFO_EXTENSION)) != 'png')
            return false;
        $img = @imagecreatetruecolor(64, 32);
        @imagealphablending($img, false);
        @imagesavealpha($img, true);
        $bytes = $this->getBytes();
        $i = 0;
        for ($y = 0; $y < 32; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgb = substr($bytes, $i, 4);
                $i += 4;
                $color = @imagecolorallocatealpha($img, ord($rgb{0}), ord($rgb{1}), ord($rgb{2}), (((~((int)ord($rgb{3}))) & 0xff) >> 1));
                @imagesetpixel($img, $x, $y, $color);
            }
        }
        if (@imagepng($img, $this->getPath(false))) {
            @imagedestroy($img);
            return true;
        }
        @imagedestroy($img);
        return false;
    }
}