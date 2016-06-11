# SkyWars - PocketMine plugin
![skywars](https://raw.githubusercontent.com/svilex/res/master/skywars.png)
[![Build Status](https://travis-ci.org/svilex/SkyWars-PocketMine.svg?branch=master)](https://travis-ci.org/svilex/SkyWars-PocketMine)
---
###About
This is a [PocketMine-MP](https://github.com/PocketMine/PocketMine-MP) (or forks) plugin that allows you to simply create multiple SkyWars mini-games for mcpe 0.15! :grin:

######This plugin was tested (and should work) on:
- [ ] **[PocketMine-MP](https://github.com/PocketMine/PocketMine-MP)**
- [ ] **[ImagicalMine](https://github.com/ImagicalCorp/ImagicalMine)**
- [x] **[ClearSky](https://github.com/ClearSkyTeam/ClearSky)**
- [x] **[Genisys](https://github.com/iTXTech/Genisys)**

---
###Donate
_Making a **donation** is an act of generosity. Your support, however modest it might be, is necessary._<br/>
_Your **donations** helps me to continue creating plugins and improve this project!_<br/>
_This plugin helped you? Do you like it? **Support it by donating!**_

_Benefits: You will be credited in the source code as a generous **donor**_ :smile:

**GOAL: :moneybag: €3 / €20**

- ![Paypal](https://raw.githubusercontent.com/svilex/res/master/paypal.png) Paypal: [**Donate**](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=G596HU7YZ8HAG) :money_with_wings:

---
###Releases - Downloads

* **pre-Release [_0.6_](https://github.com/svilex/SkyWars-PocketMine/releases/tag/v0.6) (May 7, 2016)**

>- Beautiful config file with descriptions.<br>
>- Spectator mode when a player dies.<br>
>- Economy support for money rewards.<br>
>- Reward command.<br>
>- /sw join & /sw quit.<br>
>- New sign knockBack type.<br>
>- More death messages.<br>
>- An option to set the player max health.<br>
>- An option to choose if a player can drop items.<br>
>- Added COBBLESTONE & DIRT to chests.<br>
>- Bug Fixes that you don't need to know because are a lot :smirk:

* **pre-Release [_0.4_](https://github.com/svilex/SkyWars-PocketMine/releases/tag/v0.4) (April 24, 2016)**

>- Added air generator option.<br>
>- Added a config for signs format.<br>
>- Better and faster world reset.<br>
>- Bug Fixes.

* **pre-Release [_0.3_](https://github.com/svilex/SkyWars-PocketMine/releases/tag/v0.3) (April 14, 2016)**

>- Maybe fixed #3<br>
>- Added a sound on arena join.

<!--
* **pre-Release [_0.2_](https://github.com/svilex/SkyWars-PocketMine/releases/tag/v0.2) (April 13, 2016)**

>- Now removing players effects on respawn, arena join, quit.<br>
>- Added a config option to set the needed players for the countdown start.<br>
>- Now players are no more able to interact before the game start.<br>
>- Added a sound for the last 10 seconds of the countdown.<br>
-->

_Click [**here**](https://github.com/svilex/SkyWars-PocketMine/releases) for other releases_.

---
###How to use

#####Installation
**1.** Download a plugin release (the last is recommended) from above.<br/>
**2.** Choose the `SW_svile_php*.phar` file according to your php version.<br/>
**3.** Extract the file into the **plugins/** folder of your server and restart it.<br/>
**4.** Done, you can now join the game and create arenas _(SkyWars\_mini-games)_.

#####How to create an arena
**1.** Teleport yourself in the world where you would like to create it (not default one).<br/>
**2.** Now you can use the command `/sw create [SWname] [slots] [countdown] [maxGameTime]` to create an arena.<br/>
**3.** Go back in the arena world and depending on its spawns/slots use the command `/sw setspawn [slot]` x times.<br/>
**4.** Place a sign with `sw` in the 1st line and `SWname` in the 2nd.<br/>
**5.** Done, now players can tap the sign to join the game!

#####Commands
######These commands cannot be used in console.
Command | Description
-----------|-----------
/sw        | SW main command, shows the usage (subcommands)
/sw create **[**SWname**]** **[**slots**]** **[**countdown**]** **[**maxGameTime**]** | It's used for creating arenas.<br/>- **SWname** indicates the name of the arena, is used for distinguish arenas, for example on join signs.<br/>- **slots** indicates the number of spawns of the arena<br/>- **countdown** is the time in seconds before the game starts.<br/>- **maxGameTime** is the time in seconds after the countdown, if go over this, the game will finish.
/sw setspawn **[**slot**]** | It's used to set each spawn using the CommandSender position.<br/>- **slot** indicates the number of the slot. Example: an arena with 4 slots need 4 different spawns; to set these 4 spawns you need to run this command 4 times: `/sw setspawn 1`, `/sw setspawn 2`, `/sw setspawn 3`, `/sw setspawn 4`.<br/>*If you set spawns above glass, it will be broken once the game starts.*
/sw list  | Displays the list of loaded arenas with the corresponding world + players playing in them. Example: `TestArena [5/16] => TestWorld` etc.
/sw delete **[**SWname**]** | This command just deletes an arena.<br/>- **SWname** is the name of the arena that you must give to delete it
/sw signdelete **[**SWname**\|**all**]** | Do you want to delete a join sign but you forgot where you placed it? This command can help you.<br/>- **SWname** is the arena name, if gived, all the signs pointing to the given arena will be deleted.<br/>- **all** If used as the arena name like `/sw signdelete all`, all the SW signs wil be deleted.<br/>_Are you thinking this command is useless? You'll change your idea about it when you'll have the need._:laughing:
/sw join **[**SWname**]** [PlayerName] | Anyone except ops can use this command to join SW games.<br/>- **PlayerName** can be used only by CONSOLE to force the player to join the specified arena.
/sw quit | Anyone except ops can use this command to left the current SW game.

#####Here there are some videos that explains how to create an arena in different languages:
- [Deutsch](//TODO add a video) no video yet
- [English](//TODO add a video) no video yet
- [Español](//TODO add a video) no video yet
- [Français](//TODO add a video) no video yet
- [Italiano](//TODO add a video) no video yet

######Have you made a video? Contact me to put it here:exclamation:

---
###Contact
<br/>
- **Kik:** \_svile\_<br/>
- **Telegram_Gruop:** :link: https://telegram.me/svile<br/>
- **E-mail:** thesville@gmail.com<br/>

<br/>
<br/>
###### _fell free to make pull requests and to contact me for any help_.
<br/>
<br/>

---
###License
This plugin is licensed under the [GPLv3](http://www.gnu.org/licenses/gpl-3.0.html)

>This program is free software: you can redistribute it and/or modify<br/>
>it under the terms of the GNU General Public License as published by<br/>
>the Free Software Foundation, either version 3 of the License, or<br/>
>(at your option) any later version.<br/>
>
>This program is distributed in the hope that it will be useful,<br/>
>but WITHOUT ANY WARRANTY; without even the implied warranty of<br/>
>MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the<br/>
>GNU General Public License for more details.<br/>
>
>You should have received a copy of the GNU General Public License<br/>
>along with this program.  If not, see http://www.gnu.org/licenses/

###Stats
This plugin is sending your server **PORT** and **SW_VERSION** to a web server to collect stats.<br/>
Using this plugin you agree to send these info.<br/>
- Click [**here**](http://svile.altervista.org/sw_log.html) for the full list.
