<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FI8TNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/eldy/theme_vars.css.php
 *		\brief      File for CSS style sheet Eldy
 */

if (defined('THEME_ONLY_CONSTANT')) return;


// Colors
$colorbackhmenu1='60,70,100';      // topmenu
$colorbackvmenu1='248,248,248';      // vmenu
$colortopbordertitle1='200,200,200';    // top border of title
$colorbacktitle1='220,220,223';      // title of tables,list
$colorbacktabcard1='255,255,255';  // card
$colorbacktabactive='234,234,234';
$colorbacklineimpair1='255,255,255';    // line impair
$colorbacklineimpair2='255,255,255';    // line impair
$colorbacklinepair1='250,250,250';    // line pair
$colorbacklinepair2='250,250,250';    // line pair
$colorbacklinepairhover='230,237,244';	// line hover
$colorbacklinebreak='239,231,224';		// line break
$colorbackbody='255,255,255';
$colortexttitlenotab='100,60,20';
$colortexttitle='0,0,0';
$colortext='0,0,0';
$colortextlink='0,0,100';
$fontsize='0.86em';
$fontsizesmaller='0.75em';
$topMenuFontSize='1.2em';
$toolTipBgColor='rgba(255, 255, 255, 0.96);';
$toolTipFontColor='#333';

// Badges colors
$badgePrimary   ='#007bff';
$badgeSecondary ='#6c757d';
$badgeSuccess   ='#28a745';
$badgeDanger    ='#dc3545';
$badgeWarning   ='#ffc107';
$badgeInfo      ='#17a2b8';
$badgeDark      ='#343a40';
$badgeLight     ='#f8f9fa';

/* default color for status : After a quick check, somme status can have oposite function according to objects
*  So this badges status uses default value according to theme eldy status img
*  TODO: use color definition vars above for define badges color status X -> expemple $badgeStatusValidate, $badgeStatusClosed, $badgeStatusActive ....
*/
$badgeStatus0='#cbd3d3';
$badgeStatus1='#bc9526';
$badgeStatus2='#e6f0f0';
$badgeStatus3='#bca52b';
$badgeStatus4='#277d1e';
$badgeStatus5='#cad2d2';
$badgeStatus6='#cad2d2';
$badgeStatus7='#baa32b';
$badgeStatus8='#be3013';
$badgeStatus9='#e7f0f0';
