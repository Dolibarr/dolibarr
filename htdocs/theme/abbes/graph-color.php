<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/theme/eldy/graph-color.php
 *	\brief      File to declare colors to use to build graphics with theme Eldy
 *  \ingroup    core
 *
 *  To include file, do this:
 *              $color_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/graph-color.php';
 *              if (is_readable($color_file)) include_once $color_file;
 */

global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;
$theme_bordercolor = array(235,235,224);
$theme_datacolor = array(array(140,140,220), array(190,120,120), array(0,160,140), array(190,190,100), array(115,125,150), array(100,170,20), array(250,190,30), array(150,135,125), array(85,135,150), array(150,135,80), array(150,80,150));
$theme_bgcolor = array(hexdec('F4'),hexdec('F4'),hexdec('F4'));
$theme_bgcoloronglet = array(hexdec('DE'),hexdec('E7'),hexdec('EC'));

