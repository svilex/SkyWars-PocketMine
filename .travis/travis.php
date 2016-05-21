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
 */

{
    function run($command)
    {
        exec($command, $output, $status);

        if ($status < 1)
            return $output;
        else
            return false;

    }

    /*
    $server = proc_open(PHP_BINARY . " -dphar.readonly=0 PM.phar --no-wizard --disable-readline", [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["pipe", "w"]
    ], $pipes);

    if (!is_resource($server))
        die('Failed to create process');

    fwrite($pipes[0], "version\nmakeplugin SW_svile\nstop\n\n");
    fclose($pipes[0]);

    while (!feof($pipes[1]))
        echo fgets($pipes[1]);

    fclose($pipes[1]);
    fclose($pipes[2]);
    */

    if (count(glob("./plugins/Genisys/SW_svile*.phar")) === 0) {
        echo "\n" . `tput bold` . `tput setaf 1` . "No Phar created!";
        exit(1);
    } else {
        if ($upload = run("curl --silent --upload-file ./plugins/Genisys/SW_svile*.phar https://transfer.sh/sw-svile.phar"))
            echo "\nPhar download link: " . `tput bold` . `tput setaf 5` . $upload[0];
        else
            echo "\n" . `tput bold` . `tput setaf 1` . "Phar upload failed!";
        exit(0);
    }
}