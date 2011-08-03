<?PHP
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *		\file       htdocs/theme/yellow/graph-color.php
 *		\brief      File to declare colors to use to build graphics with theme Yellow
 *      \ingroup    core
 *		\version    $Id: graph-color.php,v 1.6 2011/07/31 23:22:04 eldy Exp $
 */

global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;
$theme_bordercolor = array(235,235,224);
$theme_datacolor = array(array(160,140,120), array(140,160,130), array(190,190,220), array(140,170,150));
$theme_bgcolor = array(hexdec('F4'),hexdec('F4'),hexdec('F4'));
$theme_bgcoloronglet = array(hexdec('DC'),hexdec('DC'),hexdec('D3'));

?>
