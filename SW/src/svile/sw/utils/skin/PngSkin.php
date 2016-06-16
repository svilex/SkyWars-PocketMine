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

class PngSkin extends Skin
{
    /**
     * PngSkin constructor.
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
        if (!extension_loaded('gd') || $this->getType() != 1)
            return false;
        $img = @imagecreatefrompng($this->getPath());
        if (!$img)
            return false;
        $bytes = '';
        $l = (int)@getimagesize($this->getPath())[1];
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgba = @imagecolorat($img, $x, $y);
                //This will never be 255
                $a = ((~((int)($rgba >> 24))) << 1) & 0xff;
                $r = ($rgba >> 16) & 0xff;
                $g = ($rgba >> 8) & 0xff;
                $b = $rgba & 0xff;
                $bytes .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        if ($this->setBytes($bytes))
            return true;
        return false;
    }


    final public function save()
    {
        if (!extension_loaded('gd') || !$this->ok || strtolower(pathinfo($this->getPath(false), PATHINFO_EXTENSION)) != 'png' || !is_dir(pathinfo($this->getPath(false), PATHINFO_DIRNAME)))
            return false;
        if (is_file($this->getPath(false)))
            @unlink($this->getPath(false));
        strlen($this->getBytes()) == 8192 ? $l = 32 : $l = 64;
        $img = @imagecreatetruecolor(64, $l);
        @imagealphablending($img, false);
        @imagesavealpha($img, true);
        $bytes = $this->getBytes();
        $i = 0;
        for ($y = 0; $y < $l; $y++) {
            for ($x = 0; $x < 64; $x++) {
                $rgb = substr($bytes, $i, 4);
                $i += 4;
                $color = @imagecolorallocatealpha($img, ord($rgb{0}), ord($rgb{1}), ord($rgb{2}), (((~((int)ord($rgb{3}))) & 0xff) >> 1));
                @imagesetpixel($img, $x, $y, $color);
            }
        }
        if (@imagepng($img, $this->getPath(false)) && $this->getType() == 1) {
            @imagedestroy($img);
            return true;
        }
        @unlink($this->getPath(false));
        @imagedestroy($img);
        return false;
    }
}