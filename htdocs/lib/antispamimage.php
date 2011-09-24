<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *		\file       htdocs/lib/antispamimage.php
 *		\brief      Return antispam image
 */

define('NOLOGIN',1);

if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER',1);
if (! defined('NOREQUIREDB'))     define('NOREQUIREDB',1);
if (! defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN',1);
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);

require_once("../main.inc.php");
require_once(ARTICHOW_PATH.'Artichow.cfg.php');
require_once(ARTICHOW.'/AntiSpam.class.php');

$object = new AntiSpam();

// Value of image will contains 5 characters
$value=$object->setRand(5);
$object->setSize(128,36);

// Set value in session variable dol_antispam_value
$object->save('dol_antispam_value');

$object->setNoise(0);
$object->setAntiAliasing(false);

$colorbg1=new Color(250,250,250);
$colorbg2=new Color(230,220,210);
$colorfg=new Color(100,100,100);
$colorbr=new Color(220,210,200);
$colorra=new LinearGradient($colorbg1,$colorbg2,90);
//$object->setBackgroundColor($colorbg);
$object->setBackgroundGradient($colorra);
$object->border->setColor($colorbr);

// On affiche l'image à l'écran
$object->draw();

?>