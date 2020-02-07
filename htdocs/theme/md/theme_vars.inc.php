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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/theme/md/theme_vars.inc.php
 *	\brief      File to declare variables of CSS style sheet
 *  \ingroup    core
 *
 *  To include file, do this:
 *              $var_file = DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
 *              if (is_readable($var_file)) include $var_file;
 */

global $theme_bordercolor, $theme_datacolor, $theme_bgcolor, $theme_bgcoloronglet;
$theme_bordercolor = array(235, 235, 224);
$theme_datacolor = array(array(137, 86, 161), array(60, 147, 183), array(250, 190, 80), array(191, 75, 57), array(80, 166, 90), array(140, 140, 220), array(190, 120, 120), array(190, 190, 100), array(115, 125, 150), array(100, 170, 20), array(150, 135, 125), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
if (!defined('ISLOADEDBYSTEELSHEET'))	// File is run after an include of a php page, not by the style sheet, if the constant is not defined.
{
	if (!empty($conf->global->MAIN_OPTIMIZEFORCOLORBLIND)) // user is loaded by dolgraph.class.php
	{
		if ($conf->global->MAIN_OPTIMIZEFORCOLORBLIND == 'flashy')
		{
			$theme_datacolor = array(array(157, 56, 191), array(0, 147, 183), array(250, 190, 30), array(221, 75, 57), array(0, 166, 90), array(140, 140, 220), array(190, 120, 120), array(190, 190, 100), array(115, 125, 150), array(100, 170, 20), array(150, 135, 125), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
		}
		else
		{
			// for now we use the same configuration for all types of color blind
			$theme_datacolor = array(array(248, 220, 1), array(9, 85, 187), array(42, 208, 255), array(0, 0, 0), array(169, 169, 169), array(253, 102, 136), array(120, 154, 190), array(146, 146, 55), array(0, 52, 251), array(196, 226, 161), array(222, 160, 41), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
		}
	}
}
$theme_bgcolor = array(hexdec('F4'), hexdec('F4'), hexdec('F4'));
$theme_bgcoloronglet = array(hexdec('DE'), hexdec('E7'), hexdec('EC'));


// Colors
$colorbackhmenu1 = '90,50,120'; // topmenu
$colorbackvmenu1 = '255,255,255'; // vmenu
$colortopbordertitle1 = ''; // top border of tables-lists title. not defined = default to colorbackhmenu1
$colorbacktitle1 = '240,240,240'; // title of tables-lists
$colorbacktabcard1 = '255,255,255'; // card
$colorbacktabactive = '234,234,234';
$colorbacklineimpair1 = '255,255,255'; // line impair
$colorbacklineimpair2 = '255,255,255'; // line impair
$colorbacklinepair1 = '248,248,248'; // line pair
$colorbacklinepair2 = '246,246,246'; // line pair
$colorbacklinepairhover = '230,237,244'; // line hover
$colorbacklinepairchecked = '230,237,244'; // line checked
$colorbacklinebreak = '214,218,220';
$colorbackbody = '248,248,248';
$colortexttitlenotab = '90,90,90';
$colortexttitle = '20,20,20';
$colortext = '0,0,0';
$colortextlink = '0,0,120';
$fontsize = '14';
$fontsizesmaller = '11';

// text color
$textSuccess   = '#28a745';
$colorblind_deuteranopes_textSuccess = '#37de5d';
$textWarning   = '#a37c0d'; // See $badgeWarning
$textDanger    = '#8c4446'; // See $badgeDanger
$colorblind_deuteranopes_textWarning = $textWarning; // currently not tested with a color blind people so use default color

// Badges colors
$badgePrimary   = '#007bff';
$badgeSecondary = '#999999';
$badgeSuccess   = '#28a745';
$badgeWarning   = '#a37c0d'; // See $textWarning
$badgeDanger    = '#8c4446'; // See $textDanger
$badgeInfo      = '#17a2b8';
$badgeDark      = '#343a40';
$badgeLight     = '#f8f9fa';

/* default color for status : After a quick check, somme status can have oposite function according to objects
*  So this badges status uses default value according to theme eldy status img
*  TODO: use color definition vars above for define badges color status X -> expemple $badgeStatusValidate, $badgeStatusClosed, $badgeStatusActive ....
*/
$badgeStatus0 = '#cbd3d3';
$badgeStatus1 = '#bc9526';
$badgeStatus2 = '#e6f0f0';
$badgeStatus3 = '#bca52b';
$badgeStatus4 = '#277d1e';
$badgeStatus5 = '#cad2d2';
$badgeStatus6 = '#cad2d2';
$badgeStatus7 = '#baa32b';
$badgeStatus8 = '#993013';
$badgeStatus9 = '#e7f0f0';
