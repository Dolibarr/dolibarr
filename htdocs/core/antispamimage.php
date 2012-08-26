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
 *		\file       htdocs/core/antispamimage.php
 *		\brief      Return antispam image
 */

define('NOLOGIN',1);

if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER',1);
if (! defined('NOREQUIREDB'))     define('NOREQUIREDB',1);
if (! defined('NOREQUIRETRAN'))   define('NOREQUIRETRAN',1);
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);

require_once '../main.inc.php';


/*
 * View
 */

$length=5;
$letters = 'aAbBCDeEFgGhHJKLmMnNpPqQRsStTuVwWXYZz2345679';
$number = strlen($letters);
$string = '';
for($i = 0; $i < $length; $i++)
{
    $string .= $letters{mt_rand(0, $number - 1)};
}
//print $string;


$sessionkey='dol_antispam_value';
$_SESSION[$sessionkey]=$string;

header("Content-type: image/png");

$img = imagecreate(80,32);
if (empty($img))
{
    dol_print_error('',"Problem with GD creation");
    exit;
}

$background_color = imagecolorallocate($img, 250, 250, 250);
$ecriture_color = imagecolorallocate($img, 0, 0, 0);
imagestring($img, 4, 24, 8, $string, $ecriture_color);
imagepng($img);

?>