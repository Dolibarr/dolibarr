<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2021-2023  Anthony Berton          <anthony.berton@bb2a.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France				<frederic.france@free.fr>
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
 *		\file       htdocs/theme/md/style.css.php
 *		\brief      File for CSS style sheet Md (Material Design)
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


define('ISLOADEDBYSTEELSHEET', '1');


require __DIR__.'/theme_vars.inc.php';
if (defined('THEME_ONLY_CONSTANT')) {
	return;
}

session_cache_limiter('public');


require_once __DIR__.'/../../main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
// and permission, so we can later calculate number of top menu ($nbtopmenuentries) according to user profile.
if (empty($user->id) && !empty($_SESSION['dol_login'])) {
	$user->fetch('', $_SESSION['dol_login'], '', 1);
	$user->getrights();
	//$user->loadPersonalConf();

	// Reload menu now we have the good user (and we need the good menu to have ->showmenu('topnb') correct.
	$menumanager = new MenuManager($db, empty($user->socid) ? 0 : 1);
	$menumanager->loadMenu();
}


// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}

if (GETPOST('theme', 'aZ09')) {
	$conf->theme = GETPOST('theme', 'aZ09'); // If theme was forced on URL
}
if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL
}

if (GETPOSTISSET('THEME_DARKMODEENABLED')) {
	$conf->global->THEME_DARKMODEENABLED = GETPOSTINT('THEME_DARKMODEENABLED'); // If darkmode was forced on URL
}

$langs->load("main", 0, 1);
$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');

$path = ''; // This value may be used in future for external module to overwrite theme
$theme = 'md'; // Value of theme
if (getDolGlobalString('MAIN_OVERWRITE_THEME_RES')) {
	$path = '/' . getDolGlobalString('MAIN_OVERWRITE_THEME_RES');
	$theme = getDolGlobalString('MAIN_OVERWRITE_THEME_RES');
}

// Define image path files and other constants
$fontlist = 'roboto,arial,tahoma,verdana,helvetica'; //$fontlist='verdana,helvetica,arial,sans-serif';
if (getDolGlobalString('THEME_FONT_FAMILY')) {
	$fontlist = getDolGlobalString('THEME_FONT_FAMILY').', '.$fontlist;
}

$img_head = '';
$img_button = dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png', 1);
$dol_hide_topmenu = $conf->dol_hide_topmenu;
$dol_hide_leftmenu = $conf->dol_hide_leftmenu;
$dol_optimize_smallscreen = $conf->dol_optimize_smallscreen;
$dol_no_mouse_hover = $conf->dol_no_mouse_hover;


//$conf->global->THEME_ELDY_ENABLE_PERSONALIZED=0;
//$user->conf->THEME_ELDY_ENABLE_PERSONALIZED=0;
//var_dump($user->conf->THEME_ELDY_RGB);

$useboldtitle = (isset($conf->global->THEME_ELDY_USEBOLDTITLE) ? $conf->global->THEME_ELDY_USEBOLDTITLE : 0);
$borderwidth = 2;
$userborderontable = getDolGlobalInt('THEME_ELDY_USEBORDERONTABLE');

// Case of option always editable
if (!isset($conf->global->THEME_ELDY_BACKBODY)) {
	$conf->global->THEME_ELDY_BACKBODY = $colorbackbody;
}
if (!isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) {
	$conf->global->THEME_ELDY_TOPMENU_BACK1 = $colorbackhmenu1;
}
if (!isset($conf->global->THEME_ELDY_VERMENU_BACK1)) {
	$conf->global->THEME_ELDY_VERMENU_BACK1 = $colorbackvmenu1;
}
if (!isset($conf->global->THEME_ELDY_BACKTITLE1)) {
	$conf->global->THEME_ELDY_BACKTITLE1 = $colorbacktitle1;
}
if (!isset($conf->global->THEME_ELDY_USE_HOVER)) {
	$conf->global->THEME_ELDY_USE_HOVER = $colorbacklinepairhover;
}
if (!isset($conf->global->THEME_ELDY_USE_CHECKED)) {
	$conf->global->THEME_ELDY_USE_CHECKED = $colorbacklinepairchecked;
}
if (!isset($conf->global->THEME_ELDY_LINEBREAK)) {
	$conf->global->THEME_ELDY_LINEBREAK = $colorbacklinebreak;
}
if (!isset($conf->global->THEME_ELDY_TEXTTITLENOTAB)) {
	$conf->global->THEME_ELDY_TEXTTITLENOTAB = $colortexttitlenotab;
}
if (!isset($conf->global->THEME_ELDY_TEXTLINK)) {
	$conf->global->THEME_ELDY_TEXTLINK = $colortextlink;
}
if (!isset($conf->global->THEME_ELDY_BTNACTION)) {
	$conf->global->THEME_ELDY_BTNACTION = $butactionbg;
}
if (!isset($conf->global->THEME_ELDY_TEXTBTNACTION)) {
	$conf->global->THEME_ELDY_TEXTBTNACTION = $textbutaction;
}

// Case of option editable only if option THEME_ELDY_ENABLE_PERSONALIZED is on
if (!getDolGlobalString('THEME_ELDY_ENABLE_PERSONALIZED')) {
	// 90A4AE, 607D8B, 455A64, 37474F
	$conf->global->THEME_ELDY_BACKTABCARD1 = '255,255,255'; // card
	$conf->global->THEME_ELDY_BACKTABACTIVE = '234,234,234';
	$conf->global->THEME_ELDY_TEXT = '0,0,0';
	$conf->global->THEME_ELDY_FONT_SIZE1 = $fontsize;
	$conf->global->THEME_ELDY_FONT_SIZE2 = '11';
}

// Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
$colorbackhmenu1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TOPMENU_BACK1') ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackvmenu1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_VERMENU_BACK1') ? $colorbackvmenu1 : $conf->global->THEME_ELDY_VERMENU_BACK1) : (empty($user->conf->THEME_ELDY_VERMENU_BACK1) ? $colorbackvmenu1 : $user->conf->THEME_ELDY_VERMENU_BACK1);
$colortopbordertitle1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TOPBORDER_TITLE1') ? $colortopbordertitle1 : $conf->global->THEME_ELDY_TOPBORDER_TITLE1) : (empty($user->conf->THEME_ELDY_TOPBORDER_TITLE1) ? $colortopbordertitle1 : $user->conf->THEME_ELDY_TOPBORDER_TITLE1);
$colorbacktitle1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_BACKTITLE1') ? $colorbacktitle1 : $conf->global->THEME_ELDY_BACKTITLE1) : (empty($user->conf->THEME_ELDY_BACKTITLE1) ? $colorbacktitle1 : $user->conf->THEME_ELDY_BACKTITLE1);
$colorbacktabcard1   = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_BACKTABCARD1') ? $colorbacktabcard1 : $conf->global->THEME_ELDY_BACKTABCARD1) : (empty($user->conf->THEME_ELDY_BACKTABCARD1) ? $colorbacktabcard1 : $user->conf->THEME_ELDY_BACKTABCARD1);
$colorbacktabactive  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_BACKTABACTIVE') ? $colorbacktabactive : $conf->global->THEME_ELDY_BACKTABACTIVE) : (empty($user->conf->THEME_ELDY_BACKTABACTIVE) ? $colorbacktabactive : $user->conf->THEME_ELDY_BACKTABACTIVE);
$colorbacklineimpair1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_LINEIMPAIR1') ? $colorbacklineimpair1 : $conf->global->THEME_ELDY_LINEIMPAIR1) : (empty($user->conf->THEME_ELDY_LINEIMPAIR1) ? $colorbacklineimpair1 : $user->conf->THEME_ELDY_LINEIMPAIR1);
$colorbacklineimpair2 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_LINEIMPAIR2') ? $colorbacklineimpair2 : $conf->global->THEME_ELDY_LINEIMPAIR2) : (empty($user->conf->THEME_ELDY_LINEIMPAIR2) ? $colorbacklineimpair2 : $user->conf->THEME_ELDY_LINEIMPAIR2);
$colorbacklinepair1  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_LINEPAIR1') ? $colorbacklinepair1 : $conf->global->THEME_ELDY_LINEPAIR1) : (empty($user->conf->THEME_ELDY_LINEPAIR1) ? $colorbacklinepair1 : $user->conf->THEME_ELDY_LINEPAIR1);
$colorbacklinepair2  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_LINEPAIR2') ? $colorbacklinepair2 : $conf->global->THEME_ELDY_LINEPAIR2) : (empty($user->conf->THEME_ELDY_LINEPAIR2) ? $colorbacklinepair2 : $user->conf->THEME_ELDY_LINEPAIR2);
$colorbacklinebreak  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_LINEBREAK') ? $colorbacklinebreak : $conf->global->THEME_ELDY_LINEBREAK) : (empty($user->conf->THEME_ELDY_LINEBREAK) ? $colorbacklinebreak : $user->conf->THEME_ELDY_LINEBREAK);
$colorbackbody       = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_BACKBODY') ? $colorbackbody : $conf->global->THEME_ELDY_BACKBODY) : (empty($user->conf->THEME_ELDY_BACKBODY) ? $colorbackbody : $user->conf->THEME_ELDY_BACKBODY);
$colortexttitlenotab = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TEXTTITLENOTAB') ? $colortexttitlenotab : $conf->global->THEME_ELDY_TEXTTITLENOTAB) : (empty($user->conf->THEME_ELDY_TEXTTITLENOTAB) ? $colortexttitlenotab : $user->conf->THEME_ELDY_TEXTTITLENOTAB);
$colortexttitle      = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TEXTTITLE') ? $colortext : $conf->global->THEME_ELDY_TEXTTITLE) : (empty($user->conf->THEME_ELDY_TEXTTITLE) ? $colortexttitle : $user->conf->THEME_ELDY_TEXTTITLE);
$colortexttitlelink  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TEXTTITLELINK') ? $colortexttitlelink : $conf->global->THEME_ELDY_TEXTTITLELINK) : (empty($user->conf->THEME_ELDY_TEXTTITLELINK) ? $colortexttitlelink : $user->conf->THEME_ELDY_TEXTTITLELINK);
$colortext           = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TEXT') ? $colortext : $conf->global->THEME_ELDY_TEXT) : (empty($user->conf->THEME_ELDY_TEXT) ? $colortext : $user->conf->THEME_ELDY_TEXT);
$colortextlink       = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TEXTLINK') ? $colortext : $conf->global->THEME_ELDY_TEXTLINK) : (empty($user->conf->THEME_ELDY_TEXTLINK) ? $colortextlink : $user->conf->THEME_ELDY_TEXTLINK);
$butactionbg       	 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_BTNACTION') ? $butactionbg : $conf->global->THEME_ELDY_BTNACTION) : (empty($user->conf->THEME_ELDY_BTNACTION) ? $butactionbg : $user->conf->THEME_ELDY_BTNACTION);
$textbutaction     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_TEXTBTNACTION') ? $textbutaction : $conf->global->THEME_ELDY_TEXTBTNACTION) : (empty($user->conf->THEME_ELDY_TEXTBTNACTION) ? $textbutaction : $user->conf->THEME_ELDY_TEXTBTNACTION);
$fontsize            = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_FONT_SIZE1') ? $fontsize : $conf->global->THEME_ELDY_FONT_SIZE1) : (empty($user->conf->THEME_ELDY_FONT_SIZE1) ? $fontsize : $user->conf->THEME_ELDY_FONT_SIZE1);
$fontsizesmaller     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_FONT_SIZE2') ? $fontsize : $conf->global->THEME_ELDY_FONT_SIZE2) : (empty($user->conf->THEME_ELDY_FONT_SIZE2) ? $fontsize : $user->conf->THEME_ELDY_FONT_SIZE2);
$heightrow			 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (!getDolGlobalString('THEME_ELDY_USECOMOACTROW') ? '155%' : '300%') : (empty($user->conf->THEME_ELDY_USECOMOACTROW) ? '155%' : '300%');

// Hover color
$colorbacklinepairhover = ((!isset($conf->global->THEME_ELDY_USE_HOVER) || (string) $conf->global->THEME_ELDY_USE_HOVER === '255,255,255') ? '' : ($conf->global->THEME_ELDY_USE_HOVER === '1' ? 'edf4fb' : $conf->global->THEME_ELDY_USE_HOVER));
$colorbacklinepairchecked = ((!isset($conf->global->THEME_ELDY_USE_CHECKED) || (string) $conf->global->THEME_ELDY_USE_CHECKED === '255,255,255') ? '' : ($conf->global->THEME_ELDY_USE_CHECKED === '1' ? 'edf4fb' : $conf->global->THEME_ELDY_USE_CHECKED));
if (!empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)) {
	$colorbacklinepairhover = ((!isset($user->conf->THEME_ELDY_USE_HOVER) || $user->conf->THEME_ELDY_USE_HOVER === '255,255,255') ? '' : ($user->conf->THEME_ELDY_USE_HOVER === '1' ? 'edf4fb' : $user->conf->THEME_ELDY_USE_HOVER));
	$colorbacklinepairchecked = ((!isset($user->conf->THEME_ELDY_USE_CHECKED) || $user->conf->THEME_ELDY_USE_CHECKED === '255,255,255') ? '' : ($user->conf->THEME_ELDY_USE_CHECKED === '1' ? 'edf4fb' : $user->conf->THEME_ELDY_USE_CHECKED));
}

if (empty($colortopbordertitle1)) {
	$colortopbordertitle1 = $colorbackhmenu1;
}

// Set text color to black or white
$colorbackhmenu1 = implode(',', colorStringToArray($colorbackhmenu1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbackhmenu1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) {
	$colortextbackhmenu = 'FFFFFF';
} else {
	$colortextbackhmenu = '000000';
}

$colorbackvmenu1 = implode(',', colorStringToArray($colorbackvmenu1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbackvmenu1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) {
	$colortextbackvmenu = 'FFFFFF';
} else {
	$colortextbackvmenu = '222222';
}

$colortopbordertitle1 = implode(',', colorStringToArray($colortopbordertitle1)); // Normalize value to 'x,y,z'

$colorbacktitle1 = implode(',', colorStringToArray($colorbacktitle1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbacktitle1);
if ($colortexttitle == '') {
	$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
	if ($tmpval <= 460) {
		$colortexttitle = 'FFFFFF';
		$colorshadowtitle = '888888';
	} else {
		$colortexttitle = '101010';
		$colorshadowtitle = 'FFFFFF';
	}
} else {
	$colorshadowtitle = '888888';
}

$colorbacktabcard1 = implode(',', colorStringToArray($colorbacktabcard1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbacktabcard1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) {
	$colortextbacktab = 'FFFFFF';
} else {
	$colortextbacktab = '111111';
}

// Format color value to match expected format (may be 'FFFFFF' or '255,255,255')
$colorbackhmenu1 = implode(',', colorStringToArray($colorbackhmenu1));
$colorbackvmenu1 = implode(',', colorStringToArray($colorbackvmenu1));
$colorbacktitle1 = implode(',', colorStringToArray($colorbacktitle1));
$colorbacktabcard1 = implode(',', colorStringToArray($colorbacktabcard1));
$colorbacktabactive = implode(',', colorStringToArray($colorbacktabactive));
$colorbacklineimpair1 = implode(',', colorStringToArray($colorbacklineimpair1));
$colorbacklineimpair2 = implode(',', colorStringToArray($colorbacklineimpair2));
$colorbacklinepair1 = implode(',', colorStringToArray($colorbacklinepair1));
$colorbacklinepair2 = implode(',', colorStringToArray($colorbacklinepair2));
if ($colorbacklinepairhover != '') {
	$colorbacklinepairhover = implode(',', colorStringToArray($colorbacklinepairhover));
}
if ($colorbacklinepairchecked != '') {
	$colorbacklinepairchecked = implode(',', colorStringToArray($colorbacklinepairchecked));
}
$colorbackbody = implode(',', colorStringToArray($colorbackbody));
$colortexttitlenotab = implode(',', colorStringToArray($colortexttitlenotab));
$colortexttitle = implode(',', colorStringToArray($colortexttitle));
$colortext = implode(',', colorStringToArray($colortext));
$colortextlink = implode(',', colorStringToArray($colortextlink));

$nbtopmenuentries = $menumanager->showmenu('topnb');
$nbtopmenuentriesreal = $nbtopmenuentries;
if ($conf->browser->layout == 'phone') {
	$nbtopmenuentries = max($nbtopmenuentries, 10);
}

print '/*'."\n";
print 'colorbackbody='.$colorbackbody."\n";
print 'colorbackvmenu1='.$colorbackvmenu1."\n";
print 'colorbackhmenu1='.$colorbackhmenu1."\n";
print 'colorbacktitle1='.$colorbacktitle1."\n";
print 'colorbacklineimpair1='.$colorbacklineimpair1."\n";
print 'colorbacklineimpair2='.$colorbacklineimpair2."\n";
print 'colorbacklinepair1='.$colorbacklinepair1."\n";
print 'colorbacklinepair2='.$colorbacklinepair2."\n";
print 'colorbacklinepairhover='.$colorbacklinepairhover."\n";
print 'colorbacklinepairchecked='.$colorbacklinepairchecked."\n";
print 'colortexttitlenotab='.$colortexttitlenotab."\n";
print 'colortexttitle='.$colortexttitle."\n";
print 'colortext='.$colortext."\n";
print 'colortextlink='.$colortextlink."\n";
print 'colortexttitlelink='.$colortexttitlelink."\n";
print 'colortextbackhmenu='.$colortextbackhmenu."\n";
print 'colortextbackvmenu='.$colortextbackvmenu."\n";
print 'dol_hide_topmenu='.$dol_hide_topmenu."\n";
print 'dol_hide_leftmenu='.$dol_hide_leftmenu."\n";
print 'dol_optimize_smallscreen='.$dol_optimize_smallscreen."\n";
print 'dol_no_mouse_hover='.$dol_no_mouse_hover."\n";
print 'dol_screenwidth='.$_SESSION['dol_screenwidth']."\n";
print 'dol_screenheight='.$_SESSION['dol_screenheight']."\n";
print 'fontsize='.$fontsize."\n";
print 'nbtopmenuentries='.$nbtopmenuentries."\n";
print '*/'."\n";

$leftmenuwidth = 242;

?>
/* <style type="text/css" > */


/* ============================================================================== */
/* Default styles                                                                 */
/* ============================================================================== */

:root {
	--colorbackhmenu1: rgb(<?php print $colorbackhmenu1; ?>);
	--colorbackvmenu1: rgb(<?php print $colorbackvmenu1; ?>);
	--colorbacktitle1: rgb(<?php print $colorbacktitle1; ?>);
	--colorbacktabcard1: rgb(<?php print $colorbacktabcard1; ?>);
	--colorbacktabactive: rgb(<?php print $colorbacktabactive; ?>);
	--colorbacklineimpair1: rgb(<?php print $colorbacklineimpair1; ?>);
	--colorbacklineimpair2: rgb(<?php print $colorbacklineimpair2; ?>);
	--colorbacklinepair1: rgb(<?php print $colorbacklinepair1; ?>);
	--colorbacklinepair2: rgb(<?php print $colorbacklinepair2; ?>);
	--colorbacklinepairhover: rgb(<?php print $colorbacklinepairhover; ?>);
	--colorbacklinepairchecked: rgb(<?php print $colorbacklinepairchecked; ?>);
	--colorbacklinebreak: rgb(<?php print $colorbacklinebreak; ?>);
	--colorbackbody: rgb(<?php print $colorbackbody; ?>);
	--colorbackmobilemenu: #f8f8f8;
	--colorbackgrey: #f0f0f0;
	--colortexttitlenotab: rgb(<?php print $colortexttitlenotab; ?>);
	--colortexttitlenotab2: rgb(<?php print $colortexttitlenotab2; ?>);
	--colortexttitle: rgb(<?php print $colortexttitle; ?>);
	--colortexttitlelink: rgba(<?php print $colortexttitlelink; ?>, 0.9);
	--colortext: rgb(<?php print $colortext; ?>);
	--colortextlink: rgb(<?php print $colortextlink; ?>);
	--colortextbackhmenu: #<?php print $colortextbackhmenu; ?>;
	--colortextbackvmenu: #<?php print $colortextbackvmenu; ?>;
	--colortopbordertitle1: rgb(<?php print $colortopbordertitle1; ?>);
	--listetotal: #551188;
	--inputbackgroundcolor: #FFF;
	--inputbackgroundcolordisabled: #eee;
	--inputcolordisabled: rgb(80, 80, 80);
	--inputbordercolor: rgba(0,0,0,.2);
	--tooltipbgcolor: <?php print $toolTipBgColor; ?>;
	--tooltipfontcolor : <?php print $toolTipFontColor; ?>;
	--oddevencolor: #202020;
	--colorboxstatsborder: #ddd;
	--dolgraphbg: rgba(255,255,255,0);
	--fieldrequiredcolor: #000055;
	--colortextbacktab: #<?php print $colortextbacktab; ?>;
	--colorboxiconbg: #eee;
	--refidnocolor:#444;
	--tableforfieldcolor:#666;
	--amountremaintopaycolor:#880000;
	--amountpaymentcomplete:#008855;
	--amountremaintopaybackcolor:none;
	--productlinestockod: #002200;
	--productlinestocktoolow: #884400;
	--infoboxmoduleenabledbgcolor : linear-gradient(0.4turn, #fff, #fff, #fff, #e4efe8);
	--tablevalidbgcolor: rgb(252, 248, 227);
	--butactionbg : #<?php print $butactionbg; ?>;
	--textbutaction : #<?php print $textbutaction; ?>;
	--colorblack: #000;
	--colorwhite: #fff;
	--heightrow: <?php print $heightrow; ?>;
}

<?php
if (getDolGlobalString('THEME_DARKMODEENABLED')) {
	print "/* For dark mode */\n";
	if (getDolGlobalInt('THEME_DARKMODEENABLED') != 2) {
		print "@media (prefers-color-scheme: dark) {";	// To test, click on the 3 dots menu, then Other options then Display then emulate prefer-color-schemes
	} else {
		print "@media not print {";
	}
	print ":root {
	            --colorbackhmenu1: #3d3e40;
	            --colorbackvmenu1: #2b2c2e;
	            --colorbacktitle1: #2b2d2f;
	            --colorbacktabcard1: #1d1e20;				/* Must be same than colorbackbody */
	            --colorbacktabactive: rgb(220,220,220);
	            --colorbacklineimpair1: #38393d;
	            --colorbacklineimpair2: #2b2d2f;
	            --colorbacklinepair1: #38393d;
	            --colorbacklinepair2: #2b2d2f;
	            --colorbacklinepairhover: #2b2d2f;
	            --colorbacklinepairchecked: #0e5ccd;
	            --colorbackbody: #1d1e20;
				--colorbackmobilemenu: #080808;
				--colorbackgrey: #0f0f0f;
	            --tooltipbgcolor: #2b2d2f;
	            --colortexttitlenotab: rgb(220,220,220);
	            --colortexttitlenotab2: rgb(220,220,220);
	            --colortexttitle: rgb(220,220,220);
	            --colortext: rgb(220,220,220);
	            --colortextlink: #4390dc;
	            --colortexttitlelink: #4390dc;
	            --colortextbackhmenu: rgb(220,220,220);
	            --colortextbackvmenu: rgb(220,220,220);
				--tooltipfontcolor : rgb(220,220,220);
	            --listetotal: rgb(245, 83, 158);
	            --inputbackgroundcolor: rgb(70, 70, 70);
				--inputbackgroundcolordisabled: rgb(60, 60, 60);
				--inputcolordisabled: rgb(140, 140, 140);
	            --inputbordercolor: rgb(220,220,220);
	            --oddevencolor: rgb(220,220,220);
	            --colorboxstatsborder: rgb(65,100,138);
	            --dolgraphbg: #1d1e20;
	            --fieldrequiredcolor: rgb(250,183,59);
	            --colortextbacktab: rgb(220,220,220);
	            --colorboxiconbg: rgb(36,38,39);
	            --refidnocolor: rgb(220,220,220);
	            --tableforfieldcolor:rgb(220,220,220);
	            --amountremaintopaycolor:rgb(252,84,91);
	            --amountpaymentcomplete:rgb(101,184,77);
	            --amountremaintopaybackcolor:rbg(245,130,46);
				--infoboxmoduleenabledbgcolor : linear-gradient(0.4turn, #000, #000, #000, #274231);
				--tablevalidbgcolor: rgb(80, 64, 33);
				--colorblack: #fff;
				--colorwhite: #000;
	      }

		body, button {
			color: #bbb;
		}\n
	}\n";
}
?>

body {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	background-color: #FFFFFF;
<?php } ?>
	font-size: <?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize; ?>;
	line-height: 1.4;
	font-family: <?php print $fontlist ?>;
	margin-top: 0;
	margin-bottom: 0;
	margin-right: 0;
	margin-left: 0;
	font-weight: 400;
	background-color: var(--colorbackbody);
	<?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
}

/* Style used to protect html content in output to avoid attack by replacing full page with js content */
.sensiblehtmlcontent * {
	position: static !important;
}

.thumbstat { font-weight: bold !important; }
th a { font-weight: <?php echo($useboldtitle ? 'bold' : 'normal'); ?> !important; }
a.tab { font-weight: 500 !important; }

a:link, a:visited, a:hover, a:active, .classlink { font-family: <?php print $fontlist ?>; font-weight: normal; color: var(--colortextlink); text-decoration: none;  }
a:hover { text-decoration: underline; color: var(--colortextlink); }
a.commonlink { color: var(--colortextlink) !important; text-decoration: none; }

input {
	font-size: unset;
}
.tableforfield input, .refidno input {
	padding: 2px;
}

/*
input, input.flat, textarea, textarea.flat, form.flat select, select, select.flat, .dataTables_length label select {
	background-color: #FDFDFD;
}
*/
select.vmenusearchselectcombo {
	background-color: unset;
}

textarea:focus {
	/* v6 box-shadow: 0 0 4px #8091BF; */
	border: 1px solid #aaa !important;
	padding-left: 3px;
	padding-right: 3px;
	padding-top: 3px;
}
input:focus, textarea:focus,
button:focus:not(.button_search_x):not(.button_search):not(.button_removefilter), select:focus,
.select2-container--focus span.selection span.select2-selection {
	border-bottom: 1px solid #666;
}

textarea.cke_source:focus
{
	box-shadow: none;
}

th.wrapcolumntitle.liste_titre:not(.maxwidthsearch), td.wrapcolumntitle.liste_titre:not(.maxwidthsearch),
th.wrapcolumntitle.liste_titre_sel:not(.maxwidthsearch), td.wrapcolumntitle.liste_titre_sel:not(.maxwidthsearch) {
	overflow: hidden;
	white-space: nowrap;
	max-width: 120px;
	text-overflow: ellipsis;
}
th.wrapcolumntitle dl dt a span.fas.fa-list {
	padding-top: 1px;
	vertical-align: middle;
	padding-bottom: 1px;
}

/*.liste_titre input[name=month_date_when], .liste_titre input[name=monthvalid], .liste_titre input[name=search_ordermonth], .liste_titre input[name=search_deliverymonth],
.liste_titre input[name=search_smonth], .liste_titre input[name=search_month], .liste_titre input[name=search_emonth], .liste_titre input[name=smonth], .liste_titre input[name=month],
.liste_titre input[name=month_lim], .liste_titre input[name=month_start], .liste_titre input[name=month_end], .liste_titre input[name=month_create],
.liste_titre input[name=search_month_lim], .liste_titre input[name=search_month_start], .liste_titre input[name=search_month_end], .liste_titre input[name=search_month_create],
.liste_titre input[name=search_month_create], .liste_titre input[name=search_month_start], .liste_titre input[name=search_month_end],
.liste_titre input[name=day_date_when], .liste_titre input[name=dayvalid], .liste_titre input[name=search_orderday], .liste_titre input[name=search_deliveryday],
.liste_titre input[name=search_sday], .liste_titre input[name=search_day], .liste_titre input[name=search_eday], .liste_titre input[name=sday], .liste_titre input[name=day], .liste_titre select[name=day],
.liste_titre input[name=day_lim], .liste_titre input[name=day_start], .liste_titre input[name=day_end], .liste_titre input[name=day_create],
.liste_titre input[name=search_day_lim], .liste_titre input[name=search_day_start], .liste_titre input[name=search_day_end], .liste_titre input[name=search_day_create],
.liste_titre input[name=search_day_create], .liste_titre input[name=search_day_start], .liste_titre input[name=search_day_end],
.liste_titre input[name=search_day_date_when], .liste_titre input[name=search_month_date_when], .liste_titre input[name=search_year_date_when],
.liste_titre input[name=search_dtstartday], .liste_titre input[name=search_dtendday], .liste_titre input[name=search_dtstartmonth], .liste_titre input[name=search_dtendmonth],
*/
.liste_titre input[name=search_month], .liste_titre input[name=search_month_start], .liste_titre input[name=search_month_end] {
	margin-right: 4px;
}
select#date_startday, select#date_startmonth, select#date_endday, select#date_endmonth, select#reday, select#remonth
{
	margin-right: 4px;
}

input, input.flat, textarea, textarea.flat, form.flat select, select, select.flat, .dataTables_length label select {
	color: var(--colortext);
	border: none;
	border<?php echo !getDolGlobalString('THEME_SHOW_BORDER_ON_INPUT') ? '-bottom' : ''; ?>: solid 1px var(--inputbordercolor);
	font-family: <?php print $fontlist ?>;
	outline: none;
	margin: 0px 0px 0px 0px;
	background-color: var(--inputbackgroundcolor);
	<?php if (!getDolGlobalString('THEME_ADD_BACKGROUND_ON_INPUT')) { ?>
		border<?php echo !getDolGlobalString('THEME_SHOW_BORDER_ON_INPUT') ? '-bottom' : ''; ?>: solid 1px var(--inputbordercolor);
	<?php } ?>
}

input {
	line-height: 17px;
	padding: 5px;
	padding-left: 5px;
}
select {
	padding-top: 4px;
	padding-right: 4px;
	padding-bottom: 4px;
	padding-left: 2px;
}
input, select {
	margin-left:0px;
	margin-bottom:1px;
	margin-top:1px;
}
#mainbody input.button:not(.buttongen):not(.bordertransp), #mainbody a.button:not(.buttongen):not(.bordertransp) {
	background: var(--butactionbg);
	color: var(--textbutaction) !important;
	border-radius: 3px;
	border-collapse: collapse;
	border: none;
	text-shadow: none;
	text-transform: uppercase;
	/* font-weight: bold; */
	margin: 0em 0.8em;
	padding: 0.6em 0.7em;
	line-height: 17px;
}
#mainbody input.button:not(.buttongen):not(.bordertransp):hover, #mainbody a.button:not(.buttongen):not(.bordertransp):hover {
	-webkit-box-shadow: 0px 0px 6px 1px rgb(50 50 50 / 40%), 0px 0px 0px rgb(60 60 60 / 10%);
	box-shadow: 0px 0px 6px 1px rgb(50 50 50 / 40%), 0px 0px 0px rgb(60 60 60 / 10%);
}
#mainbody input.buttongen, #mainbody button.buttongen {
	padding: 3px 4px;
}

input.button.massactionconfirmed {
	margin: 4px;
}
input.short {
	width: 40px;
}

input:invalid, select:invalid, input.--error , select.--error {
	border-color: #ea1212;
}

section.setupsection {
	padding: 20px;
	background-color: var(--colorbacktitle1);
	border-radius: 5px;
}

.field-error-icon { color: #ea1212 !important; }

textarea {
	border-radius: 0;
	border-top:solid 1px var(--inputbordercolor);
	border-left:solid 1px var(--inputbordercolor);
	border-right:solid 1px var(--inputbordercolor);
	border-bottom:solid 1px var(--inputbordercolor);

	background-color: #FFF;
	padding:4px;
	margin-left:1px;
	margin-bottom:1px;
	margin-top:1px;
	}
input.removedassigned  {
	padding: 2px !important;
	vertical-align: text-bottom;
	margin-bottom: -3px;
}
input.smallpadd {	/* Used for timesheet input */
	padding-left: 1px !important;
	padding-right: 1px !important;
}
input.buttongen {
	vertical-align: middle;
}
input.buttonpayment, button.buttonpayment, div.buttonpayment {
	min-width: 290px;
	margin-bottom: 15px;
	margin-top: 15px;
	margin-left: 5px;
	margin-right: 5px;
	background-image: none;
	line-height: 24px;
	padding: 8px;
	background: none;
	text-align: center;
	border: 2px solid #ccc;
	background-color: #eee;
	white-space: normal;
	color: #888 !important;
	height: 60px;
}
.nofocusvisible:focus-visible {
	outline: none;
}

div.buttonpayment input {
	background-color: unset;
	border-bottom: unset;
	font-weight: bold;
	text-transform: uppercase;
	color: #333;
	cursor: pointer;
}
div.buttonpayment input:focus {
	color: #008;
}
input.buttonpaymentcb {
	background-image: url(<?php echo dol_buildpath($path.'/theme/common/credit_card.png', 1) ?>);
	background-size: 26px;
	background-repeat: no-repeat;
	background-position: 5px 5px;
}
input.buttonpaymentcheque {
	background-image: url(<?php echo dol_buildpath($path.'/theme/common/cheque.png', 1) ?>);
	background-repeat: no-repeat;
	background-position: 8px 7px;
}
input.buttonpaymentcb {
	background-image: url(<?php echo dol_buildpath($path.'/theme/common/credit_card.png', 1) ?>);
	background-size: 24px;
	background-repeat: no-repeat;
	background-position: 5px 4px;
}
input.buttonpaymentpaypal {
	background-image: url(<?php echo dol_buildpath($path.'/paypal/img/object_paypal.png', 1) ?>);
	background-repeat: no-repeat;
	background-position: 8px 7px;
}
input.buttonpaymentpaybox {
	background-image: url(<?php echo dol_buildpath($path.'/paybox/img/object_paybox.png', 1) ?>);
	background-repeat: no-repeat;
	background-position: 8px 7px;
}
input.buttonpaymentstripe {
	background-image: url(<?php echo dol_buildpath($path.'/stripe/img/object_stripe.png', 1) ?>);
	background-repeat: no-repeat;
	background-position: 8px 7px;
}
.logopublicpayment #dolpaymentlogo {
	max-height: 80px;
	max-width: 300px;
	image-rendering: -webkit-optimize-contrast;		/* better rendering on public page header */
}
a.butStatus {
	padding-left: 5px;
	padding-right: 5px;
	background-color: transparent;
	color: var(--colortext) !important;
	border: 1px solid #888;
	margin: 0 0.45em !important;
}

span.userimg.notfirst, div.userimg.notfirst {
	margin-left: -5px;
}
div.userimg.notfirst {
	display: block-inline;
}

/* Used by timesheets */
span.timesheetalreadyrecorded input {
	border: none;
	border-bottom: solid 1px rgba(0,0,0,0.1);
	margin-right: 1px !important;
}
td.onholidaymorning, td.onholidayafternoon {
	background-color: #fdf6f2;
}
td.onholidayallday {
	background-color: #f4eede;
}
td.onholidayallday:not(.weekend) input {
	background-color: #f8f7f0;
}
td.weekend {	/* must be after td.onholidayallday */
	background-color: #eee;
}
td.weekend input {
	/* background-color: #f8f8f8; */
}
td.leftborder, td.hide0 {
	border-left: 1px solid #ccc;
}
td.leftborder, td.hide6 {
	border-right: 1px solid #ccc;
}
td.rightborder {
	border-right: 1px solid #ccc;
}

td.linecoldescription.bomline {
	width: 400px;
}

td.amount, span.amount, div.amount, b.amount {
	color: #006666;
	white-space: nowrap;
}
span.amount {
	white-space: nowrap;
}
td.actionbuttons a {
	padding-left: 6px;
}
select.flat, form.flat select, .pageplusone, .divadvancedsearchfieldcompinput, {
	font-weight: normal;
	font-size: unset;
	height: 2em;
}
input.pageplusone, .divadvancedsearchfieldcompinput, {
	padding-bottom: 4px;
	padding-top: 4px;
}

.saturatemedium {
	filter: saturate(0.8);
}

.optionblue {
	color: var(--colortextlink);
}
.optiongrey, .opacitymedium {
	opacity: 0.5;
}
.opacitymediumbycolor {
	color: rgba(0, 0, 0, 0.4);
}
.opacitylow {
	opacity: 0.6;
}
.opacityhigh {
	opacity: 0.24;
}
.opacitytransp {
	opacity: 0;
}
.colorwhite {
	color: var(--colorwhite);
}
.colorgrey {
	color: #888 !important;
}
.colorblack {
	color: var(--colorblack);
}
.fontsizeunset {
	font-size: unset !important;
}

.vmirror {
	transform: scale(1, -1);
}
.hmirror {
	transform: scale(-1, 1);
}

select:invalid, select.--error {
	color: gray;
}
input:disabled, textarea:disabled, select[disabled='disabled']
{
	background: var(--inputbackgroundcolordisabled);
	color: var(--inputcolordisabled);
}

input.liste_titre {
	box-shadow: none !important;
}
.listactionlargetitle .liste_titre {
	line-height: 24px;
}
input.removedfile {
	padding: 0px !important;
	border: 0px !important;
	vertical-align: text-bottom;
}

input[type=file]     { background-color: transparent; border-top: none; border-left: none; border-right: none; box-shadow: none; }
input[type=checkbox] { background-color: transparent; border: none; box-shadow: none; vertical-align: middle; }
input[type=radio]    { background-color: transparent; border: none; box-shadow: none; vertical-align: middle; }
input[type=image]    { background-color: transparent; border: none; box-shadow: none; }
input:-webkit-autofill {
	background-color: #FBFFEA !important;
	background-image:none !important;
	-webkit-box-shadow: 0 0 0 50px #FBFFEA inset;
}

input[type=checkbox], input[type=radio] {
	margin: 0 3px 0 1px;
}
.kanban input.checkforselect {
	margin-right: 0px;
	margin-top: 5px;
}

/* CSS for placeholder */
.placeholder { color: #ccc; }
::-webkit-input-placeholder { color:#ccc; }
:-moz-placeholder { color:#bbb; } 			/* firefox 18- */
::-moz-placeholder { color:#bbb; } 			/* firefox 19+ */
:-ms-input-placeholder { color:#ccc; } 		/* ie */
input:-moz-placeholder { color:#ccc; }

input[name=price], input[name=weight], input[name=volume], input[name=surface], input[name=sizeheight], input[name=net_measure], select[name=incoterm_id] { margin-right: 6px; }
fieldset {
	border: 1px solid #AAAAAA !important;
	padding-inline-start: 2em;
	padding-inline-end: 2em;
	min-inline-size: auto;
}
.legendforfieldsetstep { padding-bottom: 10px; }
input#onlinepaymenturl, input#directdownloadlink {
	opacity: 0.7;
}

.formconsumeproduce {
	background: #f3f3f3;
	padding: 20px 0px 0px 0px;
	border-radius: 8px;
}

div#moretabsList, div#moretabsListaction {
	z-index: 5;
}

hr { border: 0; border-top: 1px solid #ccc; }
.tabBar hr { margin-top: 20px; margin-bottom: 17px; }


table.tableforfield .button:not(.bordertransp):not(.buttonpayment),
table.tableforfield .buttonDelete:not(.bordertransp):not(.buttonpayment) {
	margin-bottom: 2px;
	margin-top: 2px;
}

.button:not(.bordertransp):not(.buttonpayment), .buttonDelete:not(.bordertransp):not(.buttonpayment) {
	border-color: #c5c5c5;
	border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);
	display: inline-block;
	padding: 4px 14px;
	margin-bottom: 0;
	margin-top: 0;
	font-family: <?php print $fontlist ?>;
	text-align: center;
	cursor: pointer;
	color: #333333 !important;
	text-decoration: none !important;
	text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x;
	border-color: #e6e6e6 #e6e6e6 #bfbfbf;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	border: 1px solid #bbbbbb;
	border-bottom-color: #a2a2a2;
	-webkit-border-radius: 2px;
	border-radius: 2px;
	-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
}
.refidno .button.smallpaddingimp {
	font-size: 0.85em !important;
}
.button:focus, .buttonDelete:focus  {
	-webkit-box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
}
.button:hover:not(.nohover), .buttonDelete:hover:not(.nohover)   {
	-webkit-box-shadow: 0px 0px 6px 1px rgba(0, 0, 0, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	box-shadow: 0px 0px 6px 1px rgba(0, 0, 0, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
}
.button:disabled, .buttonDelete:disabled, .button.disabled, .buttonDelete.disabled {
	opacity: 0.4;
	box-shadow: none;
	-webkit-box-shadow: none;
	cursor: auto;
}
.buttonRefused {
	pointer-events: none;
	   cursor: default;
	opacity: 0.4;
	box-shadow: none;
	-webkit-box-shadow: none;
}
.button_search, .button_removefilter {
	border: unset;
	background-color: unset;
}
.button_search:hover, .button_removefilter:hover {
	cursor: pointer;
}
form {
	padding:0px;
	margin:0px;
}
form#addproduct {
	padding-top: 20px;
}
div.float, span.floatleft
{
	float:<?php print $left; ?>;
}
div.floatright
{
	float:<?php print $right; ?>;
}
.block
{
	display:block;
}
.inline
{
	display:inline;
}
.inline-block
{
	display:inline-block;
}
.inline-blockimp
{
	display:inline-block !important;
}
.largenumber {
	font-size: 1.4em;
}
button:focus {
	outline: none;
}
.fa-info-circle {
	padding-<?php echo $left; ?>: 3px;
}
.line-height-large {
	line-height: 1.8em;
}

th .button {
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
	-webkit-border-radius:0px !important;
	border-radius:0px !important;
}
.maxwidthsearch {		/* Max width of column with the search picto */
	width: 54px;
	min-width: 54px;
}

.valigntop {
	vertical-align: top;
}
.valignmiddle {
	vertical-align: middle;
}
.valignbottom {
	vertical-align: bottom;
}
.valigntextbottom {
	vertical-align: text-bottom;
}
.centpercent {
	width: 100%;
}
.centpercentimp {
	width: 100% !important;
}
.centpercentwithout1imp {
	width: calc(100% - 1px) !important;
}
.centpercentwithoutmenu {
	width: calc(100% - 200px);
}
.quatrevingtpercent, .inputsearch {
	width: 80%;
}
.maxquatrevingtpercent {
	max-width: 80%;
}
.soixantepercent {
	width: 60%;
}
.quatrevingtquinzepercent {
	width: 95%;
}
.quatrevingtpercentminusx {
	width: calc(80% - 52px);
}
.centpercentminusx {
	width: calc(100% - 52px);
}
textarea.centpercent {
	width: 96%;
}
.small, small {
	font-size: 85%;
}
.large {
	font-size: 125%;
}
.double {
	font-size: 2em;
}

.h1 .small, .h1 small, .h2 .small, .h2 small, .h3 .small, .h3 small, h1 .small, h1 small, h2 .small, h2 small, h3 .small, h3 small {
	font-size: 65%;
}
.h1 .small, .h1 small, .h2 .small, .h2 small, .h3 .small, .h3 small, .h4 .small, .h4 small, .h5 .small, .h5 small, .h6 .small, .h6 small, h1 .small, h1 small, h2 .small, h2 small, h3 .small, h3 small, h4 .small, h4 small, h5 .small, h5 small, h6 .small, h6 small {
	font-weight: 400;
	line-height: 1;
	/* color: #777; */
}

.flip {
	transform: scaleX(-1) translate(<?php print($left == 'left' ? '' : '-'); ?>2px, 0);
}
.rotate90 {
	transform: rotate(90deg) translate(0, <?php print($left == 'left' ? '' : '-'); ?>2px);
}
.center {
	text-align: center;
	margin: 0px auto;
}
.centerimp {
	text-align: center !important;
}
.alignstart {
	text-align: start;
}
.start {
	text-align: start;
}
.end {
	text-align: end;
}
.left {
	text-align: <?php print $left; ?>;
}
.right {
	text-align: <?php print $right; ?>;
}
.justify {
	text-align: justify;
}
.pull-left {
	float: left!important;
}
.pull-right {
	float: right!important;
}
.nowrap {
	white-space: <?php print($dol_optimize_smallscreen ? 'normal' : 'nowrap'); ?>;
}
.nowraponsmartphone {
	white-space: <?php print($dol_optimize_smallscreen ? 'nowrap' : 'normal'); ?>;
}
.wraponsmartphone {
	white-space: <?php print($dol_optimize_smallscreen ? 'normal' : 'nowrap'); ?>;
}
.liste_titre .nowrap {
	white-space: nowrap;
}
.nowraponall {	/* no wrap on all devices */
	white-space: nowrap;
}
.nowrapfordate {	/* no wrap on all devices for dates */
	white-space: nowrap;
	display: inline-block;
}
.wrapimp, .wrapimp pre {
	white-space: normal !important;
}
.wordwrap {
	word-wrap: break-word;
}
.wordbreakimp {
	word-break: break-word !important;
}
.wordbreak {
	word-break: break-word;	/* cut fist between word, inside word if not possible */
}
.wordbreakall {
	word-break: break-all;
}
td.wordbreak img, td.wordbreakimp img {
	max-width: 100%;
}
.bold {
	font-weight: bold !important;
}
.nobold {
	font-weight: normal !important;
}
.uppercase {
	text-transform: uppercase;
}
.marginpopup {
	margin: 20px;
}
.nounderline {
	text-decoration: none;
}
.nounderlineimp {
	text-decoration: none !important;
}
.nopadding {
	padding: 0;
}
.nopaddingleft {
	padding-left: 0;
}
.nopaddingright {
	padding-right: 0;
}
.nopaddingleftimp {
	padding-left: 0 !important;
}
.nopaddingrightimp {
	padding-right: 0 !important;
}
.paddingleft {
	padding-<?php print $left; ?>: 4px;
}
.paddingleftimp {
	padding-<?php print $left; ?>: 4px !important;
}
.paddingleft2 {
	padding-<?php print $left; ?>: 2px;
}
.paddingleft2imp {
	padding-<?php print $left; ?>: 2px !important;
}
.paddingright {
	padding-<?php print $right; ?>: 4px;
}
.paddingrightimp {
	padding-<?php print $right; ?>: 4px !important;
}
.paddingright2 {
	padding-<?php print $right; ?>: 2px;
}
.paddingright2imp {
	padding-<?php print $right; ?>: 2px !important;
}
.paddingtop {
	padding-top: 4px;
}
.paddingtop2 {
	padding-top: 2px;
}
.paddingbottom {
	padding-bottom: 4px;
}
.paddingbottom2 {
	padding-bottom: 2px;
}
.marginleft2 {
	margin-<?php print $left; ?>: 2px;
}
.marginright2 {
	margin-<?php print $right; ?>: 2px;
}
.nowidthimp {
	width: unset !important;
}

.cursordefault {
	cursor: default;
}
.cursorpointer {
	cursor: pointer;
}
.classfortooltiponclick .fa-question-circle {
	cursor: pointer;
}
.cursormove {
	cursor: move;
}
.cursornotallowed {
	cursor: not-allowed;
}
.cursorwait {
	cursor: wait;
}
.backgroundblank {
	background-color: #fff;
}
.nobackground, .nobackground tr {
	background: unset !important;
}
.checkboxattachfilelabel {
	font-size: 0.85em;
	opacity: 0.7;
}
.borderimp {
	border: 1px solid #888 !important;
}
.text-warning{
	color : <?php print $textWarning; ?>
}
/* CSS used for extrafield text */
.shortmessagecut {
	max-height: 125px;
	max-width: 100%;
	overflow-y: auto;
}
.longmessagecut {
	max-height: 250px;
	max-width: 100%;
	overflow-y: auto;
}
div.urllink {
	padding: 5px;
	margin-top: 5px;
	margin-bottom: 5px;
	/* border: 1px solid #ccc; */
	border-radius: 5px;
	/* width: fit-content; */
	background-color: #e0e0e8;
	opacity: 0.8;
}
div.urllink, div.urllink a {
	color: #339 !important;
}
.divsection {
	padding: 10px;
	border: 1px solid #DFDFDF;
	border-radius: 10px;
	margin-top: 5px;
	margin-bottom: 20px;
	/* background-color: rgba(0, 0, 0, 0.02); */
}

i.fa-mars::before, i.fa-venus::before, i.fa-genderless::before, i.fa-transgender::before  {
	color: #888 !important;
	opacity: 0.4;
	padding-<?php echo $left; ?>: 3px;
}
.stockmovemententry {
	color: #080;
	transform: rotate(0.25turn);
	font-size: 1.2em;
}
.stockmovementexit {
	color: #968822;
	transform: rotate(0.3turn);
	font-size: 1.2em;
}
.stockmovement {
	font-size: 1.4em;
}
.publisherlogoinline {
	vertical-align: middle;
	height: 14px;
	width: 14px;
	margin-left: 5px;
}


.linecolht {
	white-space: nowrap;
}

.text-warning{
	color : <?php print $textWarning; ?>
}
body[class*="colorblind-"] .text-warning{
	color : <?php print $colorblind_deuteranopes_textWarning; ?>
}
.text-success{
	color : <?php print $textSuccess; ?>
}
body[class*="colorblind-"] .text-success{
	color : <?php print $colorblind_deuteranopes_textSuccess; ?>
}

.text-danger{
	color : <?php print $textDanger; ?>
}

.editfielda span.fa-pencil-alt, .editfielda span.fa-pencil-ruler, .editfielda span.fa-trash, .editfielda span.fa-crop, .editfielda span.fa-eye,
.editfieldlang {
	color: #ccc !important;
}
.editfielda span.fa-pencil-alt:hover, .editfielda span.fa-pencil-ruler:hover, .editfielda span.fa-trash:hover, .editfielda span.fa-crop:hover,
.editfieldlang:hover {
	color: var(--colortexttitle) !important;
}
a.editfielda.nohover *:hover:before {
	color: #ccc !important;
}

.fawidth30 {
	width: 20px;
}
.floatnone {
	float: none !important;
}

span.fa.fa-plus-circle.paddingleft {
	padding-right: 4px;
	padding-top: 3px;
	padding-bottom: 2px;
}

.size12x { font-size: 1.2em !important; }
.size15x { font-size: 1.5em !important; }
.fa-toggle-on, .fa-toggle-off, .size2x { font-size: 2em; }
.websiteselectionsection .fa-toggle-on, .websiteselectionsection .fa-toggle-off,
.asetresetmodule .fa-toggle-on, .asetresetmodule .fa-toggle-off,
.tdwebsitesearchresult .fa-toggle-on, .tdwebsitesearchresult .fa-toggle-off {
	font-size: 1.5em; vertical-align: text-bottom;
}

.divoverflow {
	overflow: hidden;
	white-space: nowrap;
	vertical-align: middle;
	text-overflow: ellipsis;
}


/* Themes for badges */
<?php include dol_buildpath($path.'/theme/'.$theme.'/badges.inc.php', 0); ?>
<?php include dol_buildpath($path.'/theme/'.$theme.'/flags-sprite.inc.php', 0); ?>

.borderrightlight
{
	border-right: 1px solid #f4f4f4;
}
.borderleftlight
{
	border-left: 1px solid #f4f4f4;
}

#formuserfile {
	margin-top: 4px;
}
#formuserfile_link {
	margin-left: 1px;
}
.listofinvoicetype {
	height: 28px;
	vertical-align: middle;
}
.divsocialnetwork:not(:last-child) {
	padding-<?php print $right; ?>: 20px;
}
.divfilteralone {
	background-color: rgba(0, 0, 0, 0.08);
	border-radius: 5px;
	padding-left: 5px;
}
div.divsearchfield {
	/* float: <?php print $left; ?>; */
	display: inline-block;
	margin-<?php print $right; ?>: 12px;
	margin-<?php print $left; ?>: 2px;
	margin-top: 4px;
	margin-bottom: 4px;
	padding-left: 2px;
}
.divsearchfieldfilter {
	text-overflow: clip;
	overflow: auto;
	white-space: nowrap;
	padding-bottom: 5px;
	opacity: 0.6;
	font-size: small;
}
.divadvancedsearchfield:first-child {
	margin-top: 3px;
}
.divadvancedsearchfield {
	float: left;
	padding-left: 15px;
	padding-right: 15px;
	padding-bottom: 2px;
	padding-top: 2px;
}
.divadvancedsearchfield span.select2.select2-container.select2-container--default {
	padding-bottom: 4px;
}
.divadvancedsearchfieldcompinput {
	background: #fff;
	border-bottom: solid 1px var(--inputbordercolor);
}

.search_component_params {
	/*display: flex; */
	-webkit-flex-flow: row wrap;
	flex-flow: row wrap;
	background: #fff;
	padding-top: 3px;
	padding-bottom: 3px;
	padding-<?php echo $left; ?>: 0;
	padding-<?php echo $right; ?>: 0;
	border-bottom: solid 1px var(--inputbordercolor);
	line-height: 24px;
}
.search_component_searchtext {
	padding-top: 2px;
}
.search_component_params_text, .search_component_params_text:focus {
	border-bottom: none;
	width: auto;
	margin: 0 !important;
	padding: 3px;
}
.tagsearch {
	padding: 2px;
	padding-right: 4px;
	padding-bottom: 3px;
	background: #ddd;
	border-radius: 4px;
	display: inline-block;
}
.tagsearchdelete {
	color: #999;
	cursor: pointer;
	display: inline-block;
	font-weight: bold;
	margin-right: 2px;
	padding-left: 4px;
}

.caretleftaxis {
	margin-left: -13px;
	margin-top: -1px;
	position: absolute;
}
.caretdownaxis {
	margin-left: -12px;
	margin-top: 0;
	position: absolute;
}

<?php
// Add a nowrap on smartphone, so long list of field used for filter are overflowed with clip
if ($conf->browser->layout == 'phone') {
	?>
.divsearchfieldfilter {
	   white-space: nowrap;
}
	<?php
} ?>


.a-filter, .a-mesure {
	border-radius: 50px;
	background: var(--colortexttitlenotab);
	color: #fff;
	padding: 8px 10px 8px 6px;
}
.a-filter:before {
	content: "\f0b0";
}
.a-mesure:before {
	content: "\f080";
}
.a-filter:before, .a-mesure:before {
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>";
	font-weight: 600;
	padding-right: 5px;
	padding-left: 5px;
}
.a-filter-disabled, .a-mesure-disabled {
	border-radius: 50px;
	background: var(--colorbacktitle1);
	padding: 8px;
	opacity: 0.6;
}


/* ============================================================================== */
/* Styles for scan tool                                                           */
/* ============================================================================== */

div.div-for-modal {
	/* display: none; */
	position:absolute;
	top:calc(50% - 200px);
	left:calc(50% - 250px);
	width:500px;  /* adjust as per your needs */
	height:400px;   /* adjust as per your needs */
	background: #fff;
	border: 1px solid #bbb;
	box-shadow: 2px 2px 20px #ddd;
	z-index: 100;
}

#scantoolmessage {
	height: 3em;
	border: none;
	overflow-y: auto;
}

div.div-for-modal-topright {
	/* display: none; */
	position: fixed;
	top: 0;
	right: 0;
	width:50%;  /* adjust as per your needs */
	height:300px;   /* adjust as per your needs */
	background: #fff;
	border: 1px solid #bbb;
	box-shadow: 2px 2px 20px #ddd;
	z-index: 1100;
}



div.confirmmessage {
	padding-top: 6px;
}
ul.attendees {
	padding-top: 0;
	padding-bottom: 0;
	padding-left: 0;
	margin-top: 0;
	margin-bottom: 0;
}
ul.attendees li {
	list-style-type: none;
}
input > ul.attendees {
	margin-top: 6px;
}
.googlerefreshcal {
	padding-top: 4px;
	padding-bottom: 4px;
}
.paddingtopbottom {
	padding-top: 10px;
	padding-bottom: 10px;
}
.checkallactions {
	margin-left: 2px;		/* left must be same than right to keep checkbox centered */
	margin-right: 2px;		/* left must be same than right to keep checkbox centered */
	vertical-align: middle;
}
select.flat.selectlimit {
	max-width: 62px;
}
.selectlimit, .marginrightonly {
	margin-<?php echo $right; ?>: 10px !important;
}
.marginleftonly {
	margin-<?php echo $left; ?>: 10px !important;
}
.marginleftonlyshort {
	margin-<?php echo $left; ?>: 4px !important;
}
.nomarginleft {
	margin-<?php echo $left; ?>: 0px !important;
}
.nomarginright {
	margin-<?php echo $right; ?>: 0px !important;
}
.marginrightonly {
	margin-<?php echo $right; ?>: 10px !important;
}
.marginrightonlyshort {
	margin-<?php echo $right; ?>: 4px !important;
}
.marginrightonlylarge {
	margin-<?php echo $right; ?>: 20px !important;
}
.margintoponly {
	margin-top: 10px !important;
}
.margintoponlyshort {
	margin-top: 3px !important;
}
.marginbottomonly {
	margin-bottom: 10px !important;
}
.marginbottomonlyshort {
	margin-bottom: 3px !important;
}
.nomargintop {
	margin-top: 0 !important;
}
.nomarginbottom {
	margin-bottom: 0 !important;
}

.selectlimit, .selectlimit:focus {
	border-left: none !important;
	border-top: none !important;
	border-right: none !important;
	outline: none;
}
.strikefordisabled {
	text-decoration: line-through;
}
.widthdate {
	width: 130px;
}
/* using a tdoverflowxxx make the min-width not working */
.tdnooverflowimp {
   text-overflow: unset;
}
.tdoverflow {
	max-width: 0;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.spanoverflow {
	overflow-x: clip;
	text-overflow: ellipsis;
}
.tdoverflowmax50 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 50px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax60 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 60px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax80 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 80px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax80imp {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 80px !important;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax100 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 100px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax100imp {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 100px !important;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax125 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 125px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax150 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 150px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax200 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 200px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax250 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 250px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax300 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 300px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax350 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 350px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax400 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 400px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowmax500 {			/* For tdoverflow, the max-midth become a minimum ! */
	max-width: 500px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.tdoverflowauto {
	max-width: 0;
	overflow: auto;
}
.divintowithtwolinesmax {
	width: 75px;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
}
.twolinesmax {
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 2;
	overflow: hidden;
	height: auto !important;
	word-break: break-word;
}
.tenlinesmax {
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 10;
	overflow: hidden;
}

.tablelistofcalendars {
	margin-top: 25px !important;
}
.amountalreadypaid {
	white-space: nowrap;
}
.amountpaymentcomplete {
	color: var(--amountpaymentcomplete);
	font-weight: bold;
	white-space: nowrap;
}
.amountremaintopay {
	color: var(--amountremaintopaycolor);
	font-weight: bold;
	white-space: nowrap;
}
.amountremaintopayback {
	font-weight: bold;
	white-space: nowrap;
}
.amountpaymentneutral {
	color: var(--amountremaintopaybackcolor);
	font-weight: bold;
	font-size: 1.4em;
	white-space: nowrap;
}

.onlinepaymentbody .amountpaymentcomplete {
	background-color: var(--amountpaymentcomplete);
	color: #fff;
	padding: 5px;
	border-radius: 5px;
}

.savingdocmask {
	margin-top: 6px;
	margin-bottom: 12px;
}
#builddoc_form ~ .showlinkedobjectblock {
	margin-top: 20px;
}

/* For the long description of module */
.moduledesclong p img,.moduledesclong p a img {
	max-width: 90% !important;
	height: auto !important;
}
.imgdoc {
	margin: 18px;
	border: 1px solid #ccc;
	box-shadow: 1px 1px 25px #aaa;
	max-width: calc(100% - 56px);
}
.fa-file-text-o, .fa-file-code-o, .fa-file-powerpoint-o, .fa-file-excel-o, .fa-file-word-o, .fa-file-o, .fa-file-image-o, .fa-file-video-o, .fa-file-audio-o, .fa-file-archive-o, .fa-file-pdf-o {
	color: #505;
}

.fa-15 {
	font-size: 1.5em;
}
.text-security {
	-webkit-text-security: disc;
}

/* DOL_XXX for future usage (when left menu has been removed). If we do not use datatable */
/*.table-responsive {
	width: calc(100% - 330px);
	margin-bottom: 15px;
	overflow-y: hidden;
	-ms-overflow-style: -ms-autohiding-scrollbar;
}*/
/* Style used for most tables */
div.fiche>div.tabBar>form>div.div-table-responsive {
	min-height: 392px;
}
.div-table-responsive, .div-table-responsive-no-min {
	overflow-x: auto;
	min-height: 0.01%;
}
.div-table-responsive {
	line-height: var(--heightrow);
}
/* Style used for full page tables with field selector and no content after table (priority before previous for such tables) */
div.fiche>form>div.div-table-responsive, div.fiche>form>div.div-table-responsive-no-min {
	overflow-x: auto;
}
div.fiche>form>div.div-table-responsive {
	min-height: 392px;
}

.display-flex {
	display: flex;
	flex-wrap: wrap;
	  justify-content: space-between;
}
.flex-item {
	flex:1;
}

.flexcontainer {
	<?php if (in_array($conf->browser->name, array('chrome', 'firefox'))) {
		echo 'display: inline-flex;'."\n";
	} ?>
	flex-flow: row wrap;
	justify-content: flex-start;
}
.thumbstat {
	flex: 1 1 116px;
}
.thumbstat150 {
	flex: 1 1 150px;
}
.thumbstat, .thumbstat150 {
	flex-grow: 1;
	flex-shrink: 1;
	/* flex-basis: 140px; */
	/* min-width: 150px; */
	width: 158px;
	justify-content: flex-start;
	align-self: flex-start;
}

select.selectarrowonleft {
	direction: rtl;
}
select.selectarrowonleft option {
	direction: ltr;
}

table[summary="list_of_modules"] .fa-cog {
	font-size: 1.5em;
}

.linkedcol-element {
	min-width: 100px;
}
.linkedcol-amount {
	white-space: nowrap;
}
.linkedcol-date {
	text-align: center;
}

.img-skinthumb {
	width: 160px;
	height: 100px;
}

.maxscreenheightless200 {
	max-height: <?php echo isset($_SESSION['dol_screenheight']) ? max(500, (int) $_SESSION['dol_screenheight'] - 200) : 700; ?>px;	/* we guarantee height of 500 */
}
.maxscreenheightless300 {
	max-height: <?php echo isset($_SESSION['dol_screenheight']) ? max(400, (int) $_SESSION['dol_screenheight'] - 300) : 700; ?>px;	/* we guarantee height of 500 */
}

tr.nobottom td {
	border-bottom: 0px !important;
}


/* ============================================================================== */
/* Styles to hide objects                                                         */
/* ============================================================================== */

.clearboth  { clear:both; }

.hideobject { display: none; }
.showonsmartphone { display: none; }

.minwidth25  { min-width: 25px; }
.minwidth50  { min-width: 50px; }
.minwidth75  { min-width: 75px; }
.nominwidth { min-width: fit-content !important; }
/* rule for not too small screen only */
@media only screen and (min-width: <?php echo !getDolGlobalString('THEME_ELDY_WITDHOFFSET_FOR_REDUC3') ? round($nbtopmenuentries * 47, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC3; ?>px)
{
	.width20  { width: 20px; }
	.width25  { width: 25px; }
	.width50  { width: 50px; }
	.width75  { width: 75px; }
	.width100 { width: 100px; }
	.width200 { width: 200px; }
	.minwidth100 { min-width: 100px; }
	.minwidth150 { min-width: 150px; }
	.minwidth200 { min-width: 200px; }
	.minwidth250 { min-width: 250px; }
	.minwidth300 { min-width: 300px; }
	.minwidth400 { min-width: 400px; }
	.minwidth500 { min-width: 500px; }
	.minwidth50imp  { min-width: 50px !important; }
	.minwidth75imp  { min-width: 75px !important; }
	.minwidth100imp { min-width: 100px !important; }
	.minwidth125imp { min-width: 125px !important; }
	.minwidth200imp { min-width: 200px !important; }
	.minwidth250imp { min-width: 250px !important; }
	.minwidth300imp { min-width: 300px !important; }
	.minwidth400imp { min-width: 400px !important; }
	.minwidth500imp { min-width: 500px !important; }
}
.widthauto { width: auto; }
.width20  { width: 20px; }
.width25  { width: 25px; }
.width40  { width: 40px; }
.width50  { width: 50px; }
.width75  { width: 75px; }
.width100 { width: 100px; }
.width125 { width: 125px; }
.width150 { width: 150px; }
.width200 { width: 200px; }
.width250 { width: 250px; }
.width300 { width: 300px; }
.width400 { width: 400px; }
.width500 { width: 500px; }
.maxwidth25  { max-width: 25px; }
.maxwidth40  { max-width: 40px; }
.maxwidth50  { max-width: 50px; }
.maxwidth75  { max-width: 75px; }
.maxwidthdate  { max-width: 85px; }
.maxwidth100 { max-width: 100px; }
.maxwidth125 { max-width: 125px; }
.maxwidth150 { max-width: 150px; }
.maxwidth200 { max-width: 200px; }
.maxwidth250 { max-width: 250px; }
.maxwidth300 { max-width: 300px; }
.maxwidth400 { max-width: 400px; }
.maxwidth500 { max-width: 500px; }
.maxwidth750 { max-width: 750px; }
.maxwidth1000 { max-width: 1000px; }
.maxwidth50imp  { max-width: 50px !important; }
.maxwidth75imp  { max-width: 75px !important; }

.minwidth100onall { min-width: 100px !important; }
.minwidth200onall { min-width: 200px !important; }
.minwidth250onall { min-width: 250px !important; }

.minheight20 { min-height: 20px; }
.minheight30 { min-height: 30px; }
.minheight40 { min-height: 40px; }
.titlefieldcreate { width: 20%; }
.titlefield       { /* width: 25%; */ min-width: 150px; width: 25%; }
.titlefieldmiddle { width: 50%; }
.imgmaxwidth180 { max-width: 180px; }
.imgmaxheight50 { max-height: 50px; }

.width20p { width:20%; }
.width25p { width:25%; }
.width40p { width:40%; }
.width50p { width:50%; }
.width60p { width:60%; }
.width75p { width:75%; }
.width80p { width:80%; }
.width100p { width:100%; }


/* Force values for small screen 1400 */
@media only screen and (max-width: 1400px)
{
	.titlefield { /* width: 30% !important; */ }
	.titlefieldcreate { width: 30% !important; }
	.minwidth50imp  { min-width: 50px !important; }
	.minwidth75imp  { min-width: 75px !important; }
	.minwidth100imp { min-width: 100px !important; }
	.minwidth125imp { min-width: 125px !important; }
	.minwidth150imp { min-width: 150px !important; }
	.minwidth200imp { min-width: 200px !important; }
	.minwidth250imp { min-width: 250px !important; }
	.minwidth300imp { min-width: 300px !important; }
	.minwidth400imp { min-width: 300px !important; }
	.minwidth500imp { min-width: 300px !important; }

	.linkedcol-element {
		min-width: unset;
	}
}

/* Force values for small screen 1000 */
@media only screen and (max-width: 1000px)
{
	.maxwidthonsmartphone { max-width: 100px; }
	.minwidth50imp  { min-width: 50px !important; }
	.minwidth75imp  { min-width: 70px !important; }
	.minwidth100imp { min-width: 100px !important; }
	.minwidth125imp { min-width: 125px !important; }
	.minwidth150imp { min-width: 110px !important; }
	.minwidth200imp { min-width: 110px !important; }
	.minwidth250imp { min-width: 115px !important; }
	.minwidth300imp { min-width: 120px !important; }
	.minwidth400imp { min-width: 150px !important; }
	.minwidth500imp { min-width: 250px !important; }
}

/* Set a width. Note: add also a max-width, for example maxwidth500, that will be used in priority */
select.widthcentpercentminusx, span.widthcentpercentminusx:not(.select2-selection), input.widthcentpercentminusx {
	width: calc(100% - 50px) !important;
	display: inline-block;
	min-width: 100px;
}
select.widthcentpercentminusxx, span.widthcentpercentminusxx:not(.select2-selection), input.widthcentpercentminusxx {
	width: calc(100% - 70px) !important;
	display: inline-block;
	min-width: 100px;
}

/* Force values for small screen 768 */
@media only screen and (max-width: 768px)
{
	body {
		font-size: <?php print is_numeric($fontsize) ? ($fontsize).'px' : $fontsize; ?>;
	}
	div.refidno {
		font-size: <?php print is_numeric($fontsize) ? ($fontsize).'px' : $fontsize; ?> !important;
	}
	.divadvancedsearchfield {
		padding-left: 5px;
		padding-right: 5px;
	}

	div.divphotoref {
		padding-right: 10px !important;
	}

	table.liste tr.trkanban td {
		padding: 10px 6px 10px 6px;			/* t r b l */
	}

	.hideonsmartphone { display: none; }
	.hideonsmartphoneimp { display: none !important; }
	.showonsmartphone { display: block !important; }

	.margintoponsmartphone { margin-top: 6px; }

	select.minwidth100imp, select.minwidth100, select.minwidth200, select.minwidth200imp, select.minwidth300 {
		width: calc(100% - 40px) !important;
		display: inline-block;
	}
	select.widthcentpercentminusxx, span.widthcentpercentminusxx:not(.select2-selection), input.widthcentpercentminusxx {
		width: calc(100% - 70px) !important;
		display: inline-block;
		min-width: 100px;
	}

	input.maxwidthinputfileonsmartphone {
		width: 175px;
	}

	input.buttonpayment, button.buttonpayment, div.buttonpayment {
		min-width: 270px;
	}

	.smallonsmartphone {
		font-size: 0.8em;
	}

	.nopaddingtoponsmartphone {
		padding-top: 0 !important;
	}
	.nopaddingbottomonsmartphone {
		padding-bottom: 0 !important;
	}
}

/* Force values for small screen 570 */
@media only screen and (max-width: 570px)
{
	body {
		font-size: <?php print is_numeric($fontsize) ? ($fontsize).'px' : $fontsize; ?>;
	}

	div.refidno {
		font-size: <?php print is_numeric($fontsize) ? ($fontsize).'px' : $fontsize; ?> !important;
	}

	.login_vertical_align {
		padding-left: 0;
	}
	.login_table input#username, .login_table input#password, .login_table input#securitycode {
		margin-left: 5px !important;
	}
	div#login_left, div#login_right {
		min-width: 150px !important;
		padding-left: 5px !important;
		padding-right: 5px !important;
	}
	.login_table div#login_right .tdinputlogin, .login_table div#login_right .tdinputlogin input {
		min-width: 150px !important;
	}

	.divmainbodylarge { margin-left: 10px; margin-right: 10px; }

	.tdoverflowmax100onsmartphone {			/* For tdoverflow, the max-midth become a minimum ! */
		max-width: 100px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	.tdoverflowmax150onsmartphone {			/* For tdoverflow, the max-midth become a minimum ! */
		max-width: 100px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	div.fiche {
		margin-top: <?php print($dol_hide_topmenu ? '12' : '6'); ?>px !important;
	}
	.border tbody tr, .border tbody tr td, div.tabBar table.border tr, div.tabBar table.border tr td, div.tabBar div.border .table-border-row, div.tabBar div.border .table-key-border-col, div.tabBar div.border .table-val-border-col {
		height: 40px !important;
	}
	div.tabBar .listofinvoicetype table tr, div.tabBar .listofinvoicetype table tr td {
		height: 28px !important;
	}

	div.tabs div.tab a.tab  {
		max-width: 200px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.quatrevingtpercent, .inputsearch {
		width: 95%;
	}

	select {
		padding-top: 4px;
		padding-bottom: 5px;
	}

	.login_table .tdinputlogin {
		min-width: unset !important;
	}
	input, input[type=text], input[type=password], select, textarea     {
		min-width: 20px;
		min-height: 1.4em;
		line-height: 1.4em;
	 }

	.noenlargeonsmartphone { width : 50px !important; display: inline !important; }
	.maxwidthonsmartphone, #search_newcompany.ui-autocomplete-input { max-width: 100px; }
	.maxwidth50onsmartphone { max-width: 40px; }
	.maxwidth75onsmartphone { max-width: 50px; }
	.maxwidth100onsmartphone { max-width: 70px; }
	.maxwidth125onsmartphone { max-width: 100px; }
	.maxwidth150onsmartphone { max-width: 120px; }
	.maxwidth150onsmartphoneimp { max-width: 120px !important; }
	.maxwidth200onsmartphone { max-width: 200px; }
	.maxwidth250onsmartphone { max-width: 250px; }
	.maxwidth300onsmartphone { max-width: 300px; }
	.maxwidth400onsmartphone { max-width: 400px; }
	.minwidth50imp  { min-width: 50px !important; }
	.minwidth75imp  { min-width: 75px !important; }
	.minwidth100imp { min-width: 100px !important; }
	.minwidth125imp { min-width: 125px !important; }
	.minwidth150imp { min-width: 110px !important; }
	.minwidth200imp { min-width: 110px !important; }
	.minwidth250imp { min-width: 115px !important; }
	.minwidth300imp { min-width: 120px !important; }
	.minwidth400imp { min-width: 150px !important; }
	.minwidth500imp { min-width: 250px !important; }
	.titlefield { width: auto; min-width: unset; }
	.titlefieldcreate { min-width: 125px; /* width: auto !important; */ }

	#tooltip {
		position: absolute;
		width: <?php print dol_size(300, 'width'); ?>px;
	}

	/* input, input[type=text], */
	select {
		width: 98%;
		min-width: 40px;
	}

	div.divphotoref {
		padding-<?php echo $right; ?>: 5px;
		padding-bottom: 5px;
	}
	img.photoref, div.photoref {
		border: none;
		-webkit-box-shadow: none;
		box-shadow: none;
		padding: 4px;
		height: 20px;
		width: 20px;
		object-fit: contain;
	}

	div.statusref {
		padding-right: 10px;
		max-width: 55%;
	   }
	div.statusref img {
		padding-right: 3px !important;
	   }
	div.statusrefbis {
		padding-right: 3px !important;
	   }

	   input.buttonpayment {
		min-width: 300px;
	   }
}

/* Force values for small screen 320 */
@media only screen and (max-width: 320px)
{
	.maxwidth300 { max-width: 260px; }
}

.linkobject { cursor: pointer; }

table.tableforfield tr:not(.liste_titre)>td:first-of-type, tr.trforfield:not(.liste_titre)>td:first-of-type, div.tableforfield div.tagtr:not(.liste_titre)>div.tagtd:first-of-type {
	color: var(--tableforfieldcolor);
}

<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
.hideonprint { display: none !important; }
<?php } ?>



/* ============================================================================== */
/* Styles for dragging lines                                                      */
/* ============================================================================== */

.dragClass {
	color: #002255;
}
td.showDragHandle {
	cursor: move;
}
.tdlineupdown {
	white-space: nowrap;
	min-width: 10px;
}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

#id-container {
	margin-top: 0px;
	margin-bottom: 0px;
	display: table;
	table-layout: fixed;
	width: 100%;
}
#id-right, #id-left {
	display: table-cell;
	float: none;
	vertical-align: top;
}
#id-top {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	display:none;
<?php } else { ?>
	<?php if ($colorbackhmenu1 == '255,255,255') { ?>
	border-bottom: 1px solid rgba(0,0,0,0.2);	/* must be same than border-right of .side-nav */
	<?php } ?>
<?php } ?>
}
#id-left {
	min-height: 100%;
	position: relative;
	width: 213px;
	padding-top: 20px;
}
#id-right {	/* This must stay id-right and not be replaced with echo $right */
	width: 100%;
	padding-bottom: 20px;
<?php if (GETPOST('optioncss', 'aZ09') != 'print') { ?>
	padding-<?php print $left; ?>: <?php echo $leftmenuwidth + 9; ?>px;
	padding-top: 16px;
<?php } ?>
}
.bodyforlist #id-right {
	padding-bottom: 4px;
}

/* DOL_XXX For having horizontal scroll into array (like with smartphone) */

.classforhorizontalscrolloftabs #id-container {
	width: 100%;
}
.classforhorizontalscrolloftabs .side-nav {
	display: block;
	float: left;
}
.classforhorizontalscrolloftabs #id-right {
	width:calc(100% - 210px);
	display: inline-block;
}



.side-nav {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
	display: none;
<?php } else { ?>
	background: var(--colorbackvmenu1);
	/* border-<?php echo $right; ?>: 1px solid rgba(0,0,0,0.2); */
	box-shadow: 3px 0 6px -2px #eee;
	bottom: 0;
	color: #333;
	display: block;
	font-family: "RobotoDraft","Roboto",sans-serif;
	<?php echo $left; ?>: 0;
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
	} else { ?>
	position: fixed;
	top: 45px;
	<?php } ?>
	z-index: 90;
	-webkit-transform: translateZ(0);
	-moz-transform: translateZ(0);
	-ms-transform: translateZ(0);
	-o-transform: translateZ(0);
	transform: translateZ(0);
	-webkit-transform-style: preserve-3d;
	-moz-transform-style: preserve-3d;
	-ms-transform-style: preserve-3d;
	-o-transform-style: preserve-3d;
	transform-style: preserve-3d;
	-webkit-transition-delay: 0.1s;
	-moz-transition-delay: 0.1s;
	transition-delay: 0.1s;
	-webkit-transition-duration: 0.2s;
	-moz-transition-duration: 0.2s;
	transition-duration: 0.2s;
	-webkit-transition-property: -webkit-transform;
	-moz-transition-property: -moz-transform;
	transition-property: transform;
	-webkit-transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
	-moz-transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
	transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
	-webkit-overflow-scrolling: touch;
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
	} else { ?>
	overflow-x: hidden;
	overflow-y: auto;
	<?php }
}
?>
}


/**
 *	Slide animation
 */
.side-nav-vert, #id-right {
	transition: padding-left 0.5s ease, margin-left 0.5s ease;
}

.side-nav, .login_block {
	transition: left 0.5s ease;
}

body.sidebar-collapse .side-nav-vert, body.sidebar-collapse #id-right {
	margin-left: 0;
	padding-left:0
}


.side-nav-vert {
	margin-<?php echo $left; ?>: <?php echo $leftmenuwidth + 6; ?>px;
}

/* body.sidebar-collapse .side-nav, body.sidebar-collapse .login_block_other, body.sidebar-collapse #topmenu-login-dropdown */
body.sidebar-collapse .side-nav, body.sidebar-collapse .login_block_other
{
	display: none;
}
<?php if (getDolGlobalString('THEME_STICKY_TOPMENU') != 'disabled') {  ?>
.side-nav-vert {
	position: sticky;
	top: 0px;
	z-index: 1005;
}
<?php } ?>


/* For smartphone (testmenuhider is on) */
<?php if (in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) { ?>
#id-container {
	width: 100%;
}
.side-nav-vert {
	margin-left: 0;
}

.side-nav {
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
	} else { ?>
	overflow-x: initial !important;
	overflow-y: scroll;
	<?php } ?>
	display: block;

	position: relative;
}



div.backgroundsemitransparent {
	background:rgba(255, 255, 255, 0.7);
	padding-left: 10px;
	padding-right: 10px;
}



/* Login */

.login_block_getinfo {
	text-align: center;
}
.login_block_getinfo div.login_block_user {
	display: block;
}
.login_block_getinfo .atoplogin, .login_block_getinfo .atoplogin:hover {
	color: #333 !important;
}
.login_block_elem a span.atoplogin, .login_block_elem span.atoplogin {
	vertical-align: middle;
}


#id-right {
	padding-<?php print $left; ?>: 0 ! important;
}
#id-left {
	z-index: 91;
	background: var(--colorbackvmenu1);
	border-right: 1px solid rgba(0,0,0,0.3);
	/* padding-top: 20px; */
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) { ?>
	top: 66px ! important;
	<?php } else { ?>
	top: 60px ! important;
	<?php } ?>
}
div.fiche {
	margin-<?php print $left; ?>: 6px !important;
	margin-<?php print $right; ?>: 6px !important;
}
<?php } ?>

div.fiche {
	margin-<?php print $left; ?>: <?php print(GETPOST('optioncss', 'aZ09') == 'print' ? 6 : (empty($conf->dol_optimize_smallscreen) ? '35' : '6')); ?>px;
	margin-<?php print $right; ?>: <?php print(GETPOST('optioncss', 'aZ09') == 'print' ? 6 : (empty($conf->dol_optimize_smallscreen) ? '33' : '6')); ?>px;
	<?php if (!empty($dol_hide_leftmenu) && !empty($dol_hide_topmenu)) {
		print 'margin-top: 12px;';
	} ?>
	margin-bottom: 15px;
}
body.onlinepaymentbody div.fiche {	/* For online payment page */
	margin: 20px !important;
}
div.fiche>table:first-child {
	margin-bottom: 15px !important;
}
div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichecenterbis {
	margin-top: 8px;
}
div.fichethirdleft {
	<?php if ($conf->browser->layout != 'phone') {
		print "float: ".$left.";\n";
	} ?>
	<?php if ($conf->browser->layout != 'phone') {
		print "width: calc(50% - 16px);\n";
	} ?>
	<?php if ($conf->browser->layout == 'phone') {
		print "padding-bottom: 6px;\n";
	} ?>
}
div.fichetwothirdright {
	<?php if ($conf->browser->layout != 'phone') {
		print "float: ".$right.";\n";
	} ?>
	<?php if ($conf->browser->layout != 'phone') {
		print "width: calc(50% - 16px);\n";
	} ?>
	<?php if ($conf->browser->layout == 'phone') {
		print "padding-bottom: 6px\n";
	} ?>
}
div.fichehalfleft {
	<?php if ($conf->browser->layout != 'phone') {
		print "float: ".$left.";\n";
	} ?>
	<?php if ($conf->browser->layout != 'phone') {
		print "width: calc(50% - 16px);\n";
	} ?>
}
div.fichehalfright {
	<?php if ($conf->browser->layout != 'phone') {
		print "float: ".$right.";\n";
	} ?>
	<?php if ($conf->browser->layout != 'phone') {
		print "width: calc(50% - 16px);\n";
	} ?>
}
div.fichehalfright {
	<?php if ($conf->browser->layout == 'phone') {
		print "margin-top: 10px;\n";
	} ?>
}

/* Force values on one column for small screen */
@media only screen and (max-width: 1499px)
{
	div.fichehalfleft-lg {
		float: none;
		width: auto;
	}
	div.fichehalfright-lg {
		float: none;
		width: auto;
	}

	.fichehalfright-lg .fichehalfright {
		padding-left:0;
	}
}

/*div.firstcolumn div.box {
	padding-right: 10px;
}
div.secondcolumn div.box {
	padding-left: 10px;
}*/

/* Force values on one column for small screen */
@media only screen and (max-width: 900px)
{
	div.fiche {
		margin-<?php print $left; ?>: <?php print(GETPOST('optioncss', 'aZ09') == 'print' ? 6 : ($dol_hide_leftmenu ? '4' : '20')); ?>px;
		margin-<?php print $right; ?>: <?php print(GETPOST('optioncss', 'aZ09') == 'print' ? 8 : 16); ?>px;
		<?php if (!empty($conf->dol_hide_leftmenu) && !empty($conf->dol_hide_topmenu)) {
			print 'margin-top: 4px;';
		} ?>
		margin-bottom: 15px;
	}
	div.fichecenter {
		width: 100%;
		clear: both;	/* This is to have div fichecenter that are true rectangles */
	}
	div.fichecenterbis {
		margin-top: 8px;
	}
	div.fichethirdleft {
		float: none;
		width: auto;
		padding-bottom: 6px;
	}
	div.fichetwothirdright {
		float: none;
		width: auto;
		padding-bottom: 6px;
	}
	div.fichehalfleft {
		float: none;
		width: auto;
	}
	div.fichehalfright {
		float: none;
		width: auto;
	}
	div.fichehalfright {
		margin-top: 10px;
	}
	div.firstcolumn div.box {
		padding-right: 0px;
	}
	div.secondcolumn div.box {
		padding-left: 0px;
	}
}

/* For table into table into card */
div.fichehalfright tr.liste_titre:first-child td table.nobordernopadding td {
	padding: 0 0 0 0;
}
div.nopadding {
	padding: 0 !important;
}

.containercenter {
	display : table;
	margin : 0px auto;
}

td.nobordernopadding.widthpictotitle.col-picto {
	color: #bbb;
	opacity: 0.9;
}
.pictotitle {
	margin-<?php echo $right; ?>: 8px;
	margin-bottom: 4px;
}
.pictoobjectwidth {
	width: 14px;
}
span.widthpictotitle {
	font-size: 1.3em;
}
.table-list-of-attached-files .col-picto, .table-list-of-links .col-picto {
	opacity: 0.7 !important;
	font-size: 1em;
	width: 20px;
}
.table-list-of-attached-files .col-picto .widthpictotitle, .table-list-of-links .col-picto .widthpictotitle {
	width: unset;
	color: #999;
}
.pictosubstatus {
	padding-left: 2px;
	padding-right: 2px;
}
.pictostatus {
	width: 15px;
	vertical-align: middle;
	margin-top: -3px
}
.pictowarning, .pictoerror, .pictopreview, .pictonopreview {
	padding-<?php echo $left; ?>: 3px;
}
.pictowarning {
	/* vertical-align: text-bottom; */
	color: <?php echo $badgeWarning; ?>;
}
.pictoerror {
	color: <?php echo $badgeDanger ?>;
}
.pictomodule {
	width: 14px;
}
.fiche .arearef img.pictoedit, .fiche .arearef span.pictoedit,
.fiche .fichecenter img.pictoedit, .fiche .fichecenter span.pictoedit,
.tagtdnote span.pictoedit {
	opacity: 0.6;
}
img.hideonsmartphone.pictoactionview {
	vertical-align: bottom;
}

.pictofixedwidth {
	text-align: start;
	width: 20px;
	/* padding-right: 0; */
}

.colorthumb {
	padding-left: 1px !important;
	padding-right: 1px;
	padding-top: 1px;
	padding-bottom: 1px;
	width: 50px;
	text-align:center;
}
div.attacharea {
	padding-top: 18px;
	padding-bottom: 10px;
}
div.attachareaformuserfileecm {
	padding-top: 0;
	padding-bottom: 0;
}
div.arearef {
	padding-top: 2px;
	padding-bottom: 5px;
	margin-bottom: 10px;
}
div.arearefnobottom {
	padding-top: 2px;
	padding-bottom: 4px;
}
div.heightref {
	min-height: 80px;
}
div.divphotoref:last-child {
	padding-<?php echo $right; ?>: 20px;
}
div.paginationref {
	padding-bottom: 10px;
}
div.statusref {
	float: right;
	padding-left: 12px;
	margin-top: 8px;
	margin-bottom: 10px;
	clear: both;
}
div.statusref img {
	padding-left: 8px;
	padding-right: 9px;
	vertical-align: text-bottom;
	width: 18px;
}
div.statusrefbis {
	padding-left: 8px;
	   padding-right: 9px;
	   vertical-align: text-bottom;
}
img.photoref, div.photoref {
	border: 1px solid #CCC;
	-webkit-box-shadow: 3px 3px 4px #DDD;
	box-shadow: 3px 3px 4px #DDD;
	padding: 4px;
	height: 80px;
	width: 80px;
	object-fit: contain;
}
img.photokanban, div.photokanban {
	padding: 0;
	border: none;
	box-shadow: none;
	vertical-align: middle;
}

div.photoref .fa, div.photoref .fas, div.photoref .far {
	font-size: 2.5em;
}

img.fitcontain {
	object-fit: contain;
}
div.photoref {
	display:table-cell;
	vertical-align:middle;
	text-align:center;
}
.difforspanimgright {
	display: table-cell;
	padding-right: 10px;
}
img.photorefnoborder {
	padding: 2px;
	height: 48px;
	width: 48px;
	object-fit: contain;
	border: 1px solid #AAA;
	border-radius: 100px;
}
.underrefbanner {
}
.underbanner {
	border-bottom: <?php echo $borderwidth ?>px solid var(--colortopbordertitle1);
}

.trextrafieldseparator td, .trextrafields_collapse_last td {
	border-bottom: 1px solid var(--colortopbordertitle1) !important;
}
.tdhrthin {
	margin: 0 !important;
	padding-bottom: 0 !important;
}
/* Payment Screen : Pointer cursor in the autofill image */
.AutoFillAmount {
	cursor:pointer;
}


/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

<?php
$minwidthtmenu = 66; /* minimum width for one top menu entry */
$heightmenu = 48; /* height of top menu, part with image */
$heightmenu2 = 48; /* height of top menu, ârt with login  */
$disableimages = 0;
$maxwidthloginblock = 110;
if (getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE') == 1 || !empty($user->conf->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	$heightmenu = 30;
	$disableimages = 1;
	$maxwidthloginblock = 180;
	$minwidthtmenu = 0;
}
?>

div#tmenu_tooltip {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	display:none;
<?php } else { ?>
	background: var(--colorbackhmenu1);
	/*
	background-image: linear-gradient(to top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -webkit-gradient( linear, left top, left bottom, color-stop(0, rgba(255,255,255,.3)), color-stop(1, rgba(128,128,128,.3)) );
	*/
<?php } ?>
}

div#tmenu_tooltip {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	display:none;
<?php } else { ?>
	/* padding-<?php echo $right; ?>: <?php echo($maxwidthloginblock - 10); ?>px; */
<?php } ?>
}

li#mainmenutd_home {
	margin-left: 5px;
}
li.tmenusel::after, li.tmenusel:hover::after {
	content: "";
	position: absolute;
	bottom: 0px;
	left: 50%;
	left: calc(50% - 6px);
	width: 0;
	height: 0;
	border-style: solid;
	border-width: 0px 6px 5px 6px;
	border-color: transparent transparent #ffffff transparent;
}

div.tmenusep {
<?php if ($disableimages) { ?>
	display: none;
<?php } ?>
}

div.tmenudiv {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	display:none;
<?php } else { ?>
	position: relative;
	display: block;
	white-space: nowrap;
	border-top: 0px;
	border-<?php print $left; ?>: 0px;
	border-<?php print $right; ?>: 0px;
	padding: 0px 0px 0px 0px;	/* t r b l */
	margin: 0px 0px 0px 0px;	/* t r b l */
	font-size: 13px;
	font-weight: normal;
	color: #000000;
	text-decoration: none;
<?php } ?>
}
div.tmenudisabled, a.tmenudisabled {
	opacity: 0.6;
}
a.tmenudisabled:link, a.tmenudisabled:visited, a.tmenudisabled:hover, a.tmenudisabled:active {
	font-weight: normal;
	padding: 0px 5px 0px 5px;
	white-space: nowrap;
	color: var(--colortextbackhmenu);
	text-decoration: none;
	cursor: not-allowed;
}
span.mainmenuaspan.tmenudisabled {
	color: var(--colortextbackhmenu);
	opacity: 0.5;
	cursor: not-allowed;
}

a.disabled, span.tmenu {
	color: #aaa;
	text-decoration: none !important;
	cursor: default;
}

a.tmenu:link, a.tmenu:visited, a.tmenu:hover, a.tmenu:active {
	font-weight: normal;
	padding: 0px 5px 0px 5px;
	white-space: nowrap;
	/*	text-shadow: 1px 1px 1px #000000; */
	color: var(--colortextbackhmenu);
	text-decoration: none;
}
a.tmenusel:link, a.tmenusel:visited, a.tmenusel:hover, a.tmenusel:active {
	font-weight: normal;
	padding: 0px 5px 0px 5px;
	margin: 0px 0px 0px 0px;
	white-space: nowrap;
	color: var(--colortextbackhmenu);
	text-decoration: none !important;
}


ul.tmenu {	/* t r b l */
	padding: 0px 0px 0px 0px;
	margin: 0px 0px 0px 0px;
	list-style: none;
	display: table;
	margin-right: 65px;		/* to keep space for bookmark */
	padding-left: 5px;
}
ul.tmenu li {
	background: var(--colorbackhmenu1);
	/*
	background-image: linear-gradient(to top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-gradient( linear, left top, left bottom, color-stop(0, rgba(255,255,255,.3)), color-stop(1, rgba(0,0,0,.3)) );
	*/
}
li.tmenu, li.tmenusel {
	<?php print $minwidthtmenu ? 'min-width: '.$minwidthtmenu.'px;' : ''; ?>
	text-align: center;
	vertical-align: bottom;
	<?php if (!getDolGlobalString('MAIN_MENU_INVERT')) { ?>
	float: <?php print $left; ?>;
		<?php if (!$disableimages) { ?>
	height: <?php print $heightmenu; ?>px;
	padding: 0px 0px 2px 0px;
		<?php } else { ?>
	padding: 0px 0px 0px 0px;
		<?php }
	} ?>
	position:relative;
	display: block;
	margin: 0px 0px 0px 0px;
	font-weight: normal;
}
li.tmenu:hover {
	opacity: .50; /* show only a slight shadow */
}

.tmenuend .tmenuleft { width: 0px; }
.tmenuend { display: none; }

div.tmenuleft
{
	float: <?php print $left; ?>;
	margin-top: 0px;
	<?php if (empty($conf->dol_optimize_smallscreen)) { ?>
	width: 5px;
		<?php if (!$disableimages) { ?>
	height: <?php print $heightmenu + 4; ?>px;
		<?php } ?>
	<?php } ?>
}
div.tmenucenter
{
	padding-left: 0px;
	padding-right: 0px;
	<?php if ($disableimages) { ?>
	padding-top: 10px;
	height: 26px;
	<?php } else { ?>
	padding-top: 2px;
	height: <?php print $heightmenu; ?>px;
	<?php } ?>
	width: 100%;
}
div.menu_titre {
	padding-bottom: 5px;
	overflow: hidden;
	text-overflow: ellipsis;
}
.mainmenuaspan
{
	padding-<?php print $left; ?>: 2px;
	padding-<?php print $right; ?>: 2px;
	font-family: Roboto,<?php echo $fontlist; ?>;
	font-weight: 400;
	opacity: 0.9;
}

div.mainmenu {
	position : relative;
	background-repeat:no-repeat;
	background-position:center top;
	height: <?php echo($heightmenu - 22); ?>px;
	margin-left: 0px;
	min-width: 40px;
}
a.tmenuimage:focus, .mainmenu.topmenuimage:focus {
	outline: none;
}

div.mainmenu.home{
	background-position-x: center;
}

div.mainmenu.menu {
	top: 10px;
	left: 1px;
}


/* To show text of top menu according to option THEME_TOPMENU_DISABLE_IMAGE */

/* Text hidden by default */
<?php if (in_array(getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE'), array(2, 3, 4))) { ?>
.tmenulabel:not(.menuhider), .tmenulabel:not(.menuhider)::before {
	 display: none;
	 /* opacity: 0; To show text after transition */
}
a.tmenuimage:not(.menuhider), a.tmenuimage:not(.menuhider)::before,
div.tmenuimage:not(.menuhider), div.tmenuimage:not(.menuhider)::before,
span.tmenuimage:not(.menuhider), span.tmenuimage:not(.menuhider)::before {
	font-size: 1.3em;
	margin-top: 8px !important;
}
<?php } ?>

/* Test of picto visible on hover, for all picto */
<?php if (getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE') == 2) { ?>
ul.tmenu:hover .tmenulabel:not(.menuhider), ul.tmenu:hover .tmenulabel:not(.menuhider)::before {
	display: block;
	position: relative;
	overflow: hidden;
	text-overflow: ellipsis;
	/* For transition transition-delay: 1000ms;
	transition-property: all; */
	opacity: 1;
	display: initial !important;
	line-height: 0.6em !important;
	height: 1em !important;
	overflow: hidden;
	text-overflow: ellipsis;
	color: var(--colortextbackhmenu);
	top: 0px;
}
ul.tmenu:hover .tmenuimage:not(.menuhider), ul.tmenu:hover .tmenuimage:not(.menuhider)::before {
	/* For transition transition-delay: 1000ms;
	transition-property: all; */
	margin-top: 0px !important;
}
<?php } ?>

/* Text of picto visible on hover, for the picto only */
<?php if (getDolGlobalInt('THEME_TOPMENU_DISABLE_IMAGE') == 3) { ?>
li.tmenu:hover .tmenulabel:not(.menuhider), li.tmenu:hover .tmenulabel:not(.menuhider)::before {
	display: initial !important;
}
li.tmenu:hover .tmenuimage:not(.menuhider), li.tmenu:hover .tmenuimage:not(.menuhider):before {
	font-size: 1.1em !important;
	margin-top: 0px !important;
}
<?php } ?>


/* Do not load menu img if hidden to save bandwidth */
<?php if (empty($dol_hide_topmenu)) { ?>
	<?php include dol_buildpath($path.'/theme/'.$theme.'/main_menu_fa_icons.inc.php', 0); ?>

	<?php
			// Add here more div for other menu entries. moduletomainmenu=array('module name'=>'name of class for div')

			$moduletomainmenu = array(
				'user' => '', 'syslog' => '', 'societe' => 'companies', 'projet' => 'project', 'propale' => 'commercial', 'commande' => 'commercial',
				'produit' => 'products', 'service' => 'products', 'stock' => 'products',
				'don' => 'accountancy', 'tax' => 'accountancy', 'banque' => 'accountancy', 'facture' => 'accountancy', 'compta' => 'accountancy', 'accounting' => 'accountancy', 'adherent' => 'members', 'import' => 'tools', 'export' => 'tools', 'mailing' => 'tools',
				'contrat' => 'commercial', 'ficheinter' => 'commercial', 'ticket' => 'ticket', 'deplacement' => 'commercial',
				'fournisseur' => 'companies',
				'barcode' => '', 'fckeditor' => '', 'categorie' => '',
			);
			$mainmenuused = 'home';
			foreach ($conf->modules as $val) {
				$mainmenuused .= ','.(isset($moduletomainmenu[$val]) ? $moduletomainmenu[$val] : $val);
			}
			$mainmenuusedarray = array_unique(explode(',', $mainmenuused));

			$generic = 1;
			// Put here list of menu entries when the div.mainmenu.menuentry was previously defined
			$divalreadydefined = array('home', 'companies', 'products', 'mrp', 'commercial', 'externalsite', 'accountancy', 'project', 'tools', 'members', 'agenda', 'ftp', 'holiday', 'hrm', 'bookmark', 'cashdesk', 'takepos', 'ecm', 'geoipmaxmind', 'gravatar', 'clicktodial', 'paypal', 'stripe', 'webservices', 'website');
			// Put here list of menu entries we are sure we don't want
			$divnotrequired = array('multicurrency', 'salaries', 'ticket', 'margin', 'opensurvey', 'paybox', 'expensereport', 'incoterm', 'prelevement', 'propal', 'workflow', 'notification', 'supplier_proposal', 'cron', 'product', 'productbatch', 'expedition');
			foreach ($mainmenuusedarray as $val) {
				if (empty($val) || in_array($val, $divalreadydefined)) {
					continue;
				}
				if (in_array($val, $divnotrequired)) {
					continue;
				}
				//print "XXX".$val;

				$found = 0;
				$url = '';
				$constformoduleicon = 'MAIN_MODULE_'.strtoupper($val).'_ICON';
				$iconformodule = getDolGlobalString($constformoduleicon);
				if ($iconformodule) {
					if (preg_match('/^fa\-/', $iconformodule)) {
						// This is a fa icon
					} else {
						$url = 	dol_buildpath('/'.$val.'/img/'.$iconformodule.'.png', 1);
					}
					$found = 1;
				} else {
					// Search img file in module dir
					foreach ($conf->file->dol_document_root as $dirroot) {
						if (file_exists($dirroot."/".$val."/img/".$val.".png")) {
							$url = dol_buildpath('/'.$val.'/img/'.$val.'.png', 1);
							$found = 1;
							break;
						}
					}
				}

				// Output entry for menu icon in CSS
				if (!$found) {
					print "/* A mainmenu entry was found but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one */\n";
					print 'div.mainmenu.'.$val.' span::before {'."\n";
					print 'content: "\f249";'."\n";
					print '}'."\n";
					$generic++;
				} else {
					if ($url) {
						print "div.mainmenu.".$val." {\n";
						print "	background-image: url(".$url.");\n";
						print " background-position-y: 3px;\n";
						print " filter: saturate(0);\n";
						print "}\n";
					} else {
						print '/* icon for module '.$val.' is a fa icon */'."\n";
					}
				}
			}
			// End of part to add more div class css
}	// End test if $dol_hide_topmenu?>


.tmenuimage {
	padding:0 0 0 0 !important;
	margin:0 0px 0 0 !important;
	<?php if ($disableimages) { ?>
		display: none;
	<?php } ?>
}
.topmenuimage {
	<?php if ($disableimages) { ?>
		display: none;
	<?php } ?>
}
a.tmenuimage:hover {
	text-decoration: none;
}
a.tmenuimage {
	display: block;
}
a.tmenuimage:focus {
	outline: none;
}


/* Login */

.bodylogin
{
	background: #f0f0f0;
	display: table;
	position: absolute;
	height: 100%;
	width: 100%;
}
.login_center {
	display: table-cell;
	vertical-align: middle;
}
.login_vertical_align {
	padding: 10px;
	padding-bottom: 80px;
}
form#login {
	padding-bottom: 30px;
	font-size: 1.2em;
	vertical-align: middle;
}
.login_table_title {
	max-width: 530px;
	color: #aaa !important;
	padding-bottom: 20px;
	/* text-shadow: 1px 1px 1px #FFF; */
}
.login_table label {
	text-shadow: 1px 1px 1px #FFF;
}
.login_table {
	margin: 0px auto;  /* Center */
	padding-left:6px;
	padding-right:6px;
	padding-top:16px;
	padding-bottom:12px;
	max-width: 560px;

	background-color: #FFFFFF;

	-webkit-box-shadow: 0 4px 23px 5px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);
	box-shadow: 0 4px 23px 5px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);

	border-radius: 4px;
	border:solid 1px rgba(80,80,80,.4);

	border-top:solid 1px #f8f8f8;
}
.login_table input#username, .login_table input#password, .login_table input#securitycode{
	border: none;
	/* border-bottom: solid 1px rgba(180,180,180,.4); */
	padding: 5px;
	margin-left: 5px;
	margin-top: 5px;
	margin-bottom: 5px;
}
.login_table input#username:focus, .login_table input#password:focus, .login_table input#securitycode:focus {
	outline: none !important;
}
.login_table .trinputlogin {
	margin: 8px;
}
.login_table .tdinputlogin {
	background-color: #fff;
	min-width: 220px;
	border-radius: 2px;
}
.login_table .tdinputlogin {
	border-bottom: 1px solid #ccc;
}
.login_table .tdinputlogin .fa {
	padding-left: 10px;
	width: 14px;
}

.login_main_home {
	word-break: break-word;
	width: fit-content;
}
.login_main_message {
	text-align: center;
	max-width: 570px;
	margin-bottom: 10px;
}
.login_main_message .error {
	border: 1px solid #caa;
	padding: 10px;
}
div#login_left, div#login_right {
	display: inline-block;
	min-width: 245px;
	padding-top: 10px;
	padding-left: 16px;
	padding-right: 16px;
	text-align: center;
	vertical-align: middle;
}
div#login_right select#entity {
	margin-top: 10px;
}
table.login_table tr td table.none tr td {
	padding: 2px;
}
table.login_table_securitycode {
	border-spacing: 0px;
}
table.login_table_securitycode tr td {
	padding-left: 0px;
	padding-right: 4px;
}
#securitycode {
	min-width: 60px;
}
#img_securitycode {
	border: 1px solid #f4f4f4;
}
#img_logo, .img_logo {
	max-width: 170px;
	max-height: 90px;
}
.loginbuttonexternal {
	width: 300px;
	margin: auto;
	border: 1px solid #ccc;
	padding: 10px;
	border-radius: 5px;
}


.atoplogin.dropdown .dropdown-menu {
	display: none;
}

div.login_block {
	top: 0;
	padding-top: 3px;
	padding-bottom: 3px;
	/* border-right: 1px solid rgba(0,0,0,0.2); */
	<?php print $left; ?>: 0;
<?php if (in_array($conf->browser->layout, array('phone', 'tablet')) && !getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) { ?>
	position: absolute;
<?php } else { ?>
	position: fixed;
<?php } ?>
	z-index: 10;
	text-align: center;
	vertical-align: middle;
	background: var(--colorbackvmenu1);
	width: <?php echo $leftmenuwidth + 6; ?>px;
	height: 68px;
	<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
	display: none;
	<?php } ?>
}
.sidebar-collapse div.login_block {
	top: -1000px;
}
div.login_block a {
	color: var(--colortextbackvmenu);
	display: inline-block;
}
div.login_block a .atoploginusername {
	color: var(--colortextbackhmenu);
	display: inline-block;
	overflow: hidden;
	max-width: 50px;
	text-overflow: ellipsis;
}
div.login_block span.aversion {
	color: var(--colortextbackvmenu);
	filter: contrast(0.7);
}
div.login_block table {
	display: inline;
}
div.login {
	white-space:nowrap;
	font-weight: bold;
	float: right;
}
div.login a {
	color: var(--colortextvmenu);
}
div.login a:hover {
	color: var(--colortextvmenu);
	text-decoration:underline;
}
div.login_block_user, div.login_block_other { clear: both; }
div.login_block_other { padding-top: 15px; }

.topnav div.login_block_user {
	display: inline-block;
	vertical-align: middle;
	line-height: <?php echo $disableimages ? '25' : '70'; ?>px;
	height: <?php echo $disableimages ? '25' : '70'; ?>px;
}
.topnav div.login_block_other {
	display: inline-block;
	vertical-align: middle;
	clear: <?php echo $disableimages ? 'none' : 'both'; ?>;
	padding-top: 0;
	text-align: right;
	margin-right: 8px;
	max-width: 200px;
}

.login_block_other .login_block_elem {
	height: 25px;
	line-height: 25px;
}
.login_block_elem {
	float: right;
	vertical-align: middle;
	padding: 0px 3px 0px 3px !important;
	height: 18px;
}
.login_block_elem_name {
	margin-top: 1px;
}
a.aversion {
	white-space: nowrap;
	width: 48px;
	overflow: hidden;
	text-overflow: ellipsis;
	display: block;
}

.atoplogin, .atoplogin:hover {
	color: var(--colortextbackvmenu) !important;
}
.alogin, .alogin:hover {
	color: #888 !important;
	font-weight: normal !important;
	font-size: <?php echo is_numeric($fontsizesmaller) ? $fontsizesmaller.'px' : $fontsizesmaller; ?> !important;
}
.alogin:hover, .atoplogin:hover {
	text-decoration:underline !important;
}
span.fa.atoplogin, span.fa.atoplogin:hover {
	font-size: 16px;
	text-decoration: none !important;
}
img.login, img.printer, img.entity {
	/* padding: 0px 0px 0px 4px; */
	/* margin: 0px 0px 0px 8px; */
	text-decoration: none;
	color: white;
	font-weight: bold;
}
.userimg.atoplogin img.userphoto, .userimgatoplogin img.userphoto {		/* size for user photo in login bar */
	/* border-radius: 8px; */
	width: 20px;
	height: 20px;
	background-size: contain;
	vertical-align: text-bottom;
	background-color: #FFF;
}
img.userphoto {			/* size for user photo in lists */
	border-radius: 0.75em;
	width: 1.5em;
	height: 1.5em;
	background-size: contain;
	vertical-align: middle;
}
span.userimg div.userphoto {
	background-color: #eee;
	border-radius: 0.72em;
	width: 1.4em;
	height: 1.4em;
	padding-top: 1px;
	display: inline-block;
}
img.userphotosmall {			/* size for user photo in lists */
	border-radius: 0.6em;
	width: 1.2em;
	height: 1.2em;
	background-size: contain;
	vertical-align: middle;
}
img.userphoto[alt="Gravatar avatar"], img.photouserphoto.dropdown-user-image[alt="Gravatar avatar"] {
	background: #fff;
}
img.userphotopublicvcard {
	width: 60px;
	height: 60px;
	border-radius: 50%;
	background-size: contain;
	border: 1px solid;
	border-color: rgba(128, 128, 128, 0.5);
	position: relative;
	top: 25px;
	left: -110px;
}
form[name="addtime"] img.userphoto {
	border: 1px solid #444;
}
.span-icon-user {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/object_user.png', 1); ?>);
	background-repeat: no-repeat;
}
.span-icon-password {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/lock.png', 1); ?>);
	background-repeat: no-repeat;
}

/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

div.vmenu, td.vmenu {
	margin-<?php print $right; ?>: 2px;
	position: relative;
	float: <?php print $left; ?>;
	padding: 0px;
	padding-bottom: 0px;
	padding-top: 0px;
	width: <?php echo $leftmenuwidth; ?>px;
}

.vmenu {
	margin-<?php print $left; ?>: 4px;
	<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
	display: none;
	<?php } ?>
}

.vmenusearchselectcombo {
	width: <?php echo $leftmenuwidth - 20; ?>px;
}

.menu_contenu {
	padding-top: 4px;
	padding-bottom: 3px;
	overflow: hidden;
	text-overflow: ellipsis;
}
#menu_contenu_logo { padding-right: 4px; }
.companylogo { padding-top: 4px; }
.searchform { padding-top: 10px; }
.searchform .bordertransp { border: 0; }
#divsearchforms2 form.searchform .button { border: none !important; }

a.vmenu:link, a.vmenu:visited, a.vmenu:hover, a.vmenu:active, span.vmenu, span.vsmenu {
	white-space: nowrap; font-size:<?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize ?>; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold;
}
span.vmenudisabled { font-size:<?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize ?>; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; color: #aaa; margin-left: 4px; white-space: nowrap; }
a.vmenu:link, a.vmenu:visited {
	color: var(--colortextbackvmenu);
}

a.vsmenu:link, a.vsmenu:visited, a.vsmenu:hover, a.vsmenu:active, span.vsmenu { font-size:<?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize ?>; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #202020; margin: 1px 1px 1px 8px; }
span.vsmenudisabled:not(.spanlilevel0), font.vsmenudisabled:not(.spanlilevel0) {
	font-size:<?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize ?>;
}
span.vsmenudisabled, font.vsmenudisabled {
	font-family: <?php print $fontlist ?>;
	text-align: <?php print $left; ?>;
	font-weight: normal;
	color: var(--colortextbackvmenu);
	white-space: nowrap;
}
a.vsmenu:link, a.vsmenu:visited {
	color: var(--colortextbackvmenu);
	white-space: nowrap;
}
span.vsmenudisabledmargin, font.vsmenudisabledmargin { margin: 1px 1px 1px 8px; }

a.help:link, a.help:visited, a.help:hover, a.help:active, span.help {
	text-align: <?php print $left; ?>; font-weight: normal; color: #999; text-decoration: none;
}

.helppresentcircle {
	/*
	color: var(--colorbackhmenu1);
	filter: invert(0.5);
	*/
	color: var(--colortextbackhmenu);
	margin-left: -4px;
	display: inline-block;
	font-size: x-small;
	vertical-align: super;
	opacity: 0.95;
	transform: rotate(<?php echo($left == 'left' ? '55deg' : '305deg'); ?>);
}

div.blockvmenulogo
{
	border-bottom: 0 !important;
}
.menulogocontainer {
	margin: <?php echo $disableimages ? '-1' : '6'; ?>px;
	margin-left: 12px;
	margin-right: 6px;
	padding: 0;
	height: <?php echo $disableimages ? '18' : '32'; ?>px;
	/* width: 100px; */
	max-width: 100px;
	vertical-align: middle;
}
.backgroundforcompanylogo {
	background-color: rgba(255,255,255,0.7);
	border-radius: 5px;
}
.menulogocontainer img.mycompany {
	object-fit: contain;
	width: inherit;
	height: inherit;
	image-rendering: -webkit-optimize-contrast;
}
#mainmenutd_companylogo::after {
	content: unset;
}
li#mainmenutd_companylogo .tmenucenter {
	width: unset;
}
li#mainmenutd_companylogo {
	min-width: unset !important;
}
<?php if ($disableimages) { ?>
	li#mainmenutd_home {
		min-width: unset !important;
	}
	li#mainmenutd_home .tmenucenter {
		width: unset;
	}
<?php } ?>

div.blockvmenupair, div.blockvmenuimpair
{
	font-family: <?php print $fontlist ?>;
	text-align: <?php print $left; ?>;
	text-decoration: none;
	padding-left: 5px;
	padding-right: 1px;
	padding-top: 3px;
	padding-bottom: 3px;
	margin: 1px 0px 8px 0px;

	color: var(--colortext);
	background: var(--colorbackvmenu1);

	padding-bottom: 10px;
	border-bottom: 1px solid #e8e8e8;
}
div.blockvmenubookmarks
{
	padding-bottom: 16px !important;
}
div.blockvmenuend {
	border: none !important;
	padding-left: 0 !important;
}
a.vsmenu.addbookmarkpicto {
	padding-right: 10px;
}
div.blockvmenufirst {
	padding-top: 10px;
	<?php if (getDolGlobalString('MAIN_USE_TOP_MENU_SEARCH_DROPDOWN')) { ?>
	border-top: 1px solid #e0e0e0;
	<?php } ?>
}
div.blockvmenusearch, div.blockvmenubookmarks
{
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
	padding-left: 5px;
	padding-right: 1px;
	padding-top: 3px;
	padding-bottom: 3px;
	margin: 1px 0px 2px 0px;
	background: var(--colorbackvmenu1);

	padding-bottom: 10px;
	/* border-bottom: 1px solid #f4f4f4; */
}
div.blockvmenusearchphone
{
	border-bottom: none;
	margin-bottom: 0px;
}

div.blockvmenuhelp
{
<?php if (empty($conf->dol_optimize_smallscreen)) { ?>
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: center;
	text-decoration: none;
	padding-left: 0px;
	padding-right: 8px;
	padding-top: 3px;
	padding-bottom: 3px;
	margin: 4px 0px 0px 0px;
<?php } else { ?>
	display: none;
<?php } ?>
}


td.barre {
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #b3c5cc;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
}

td.barre_select {
	background: #b3c5cc;
	color: #000000;
}

td.photo {
	background: #F4F4F4;
	color: #000000;
	border: 1px solid #bbb;
}

/* ============================================================================== */
/* Panes for Main                                                   */
/* ============================================================================== */

/*
 *  PANES and CONTENT-DIVs
 */

#mainContent, #leftContent .ui-layout-pane {
	padding:    0px;
	overflow:	auto;
}

#mainContent, #leftContent .ui-layout-center {
	padding:    0px;
	position:   relative; /* contain floated or positioned elements */
	overflow:   auto;  /* add scrolling to content-div */
}


/* ============================================================================== */
/* Toolbar for ECM or Filemanager                                                 */
/* ============================================================================== */

td.ecmroot {
	padding-bottom: 0 !important;
}

.largebutton {
	/* border-top: 1px solid #CCC !important; */
	padding: 0px 4px 14px 4px !important;
	min-height: 32px;
}


a.toolbarbutton {
	margin-top: 0px;
	margin-left: 4px;
	margin-right: 4px;
	height: 30px;
}
img.toolbarbutton {
	margin-top: 1px;
	height: 30px;
}

li.expanded > a.fmdirlia.jqft.ecmjqft {
	font-weight: bold !important;
}

.divfmdirlia {
	width: calc(100% - 100px);
}

a.fmdirlia {
	white-space: break-spaces;
	word-break: break-all;
}


/* ============================================================================== */
/* Onglets                                                                        */
/* ============================================================================== */
div.tabs {
	text-align: <?php print $left; ?>;
	margin-left: 6px !important;
	margin-right: 6px !important;
	clear:both;
	height:100%;
}
div.tabsElem {
	margin-top: 6px;
}		/* To avoid overlap of tabs when not browser */
div.tabsElem a {
	font-weight: normal !important;
}
div.tabBar {
	color: var(--colortextbacktab);
	padding-top: 23px;
	padding-left: 24px;
	padding-right: 24px;
	padding-bottom: 23px;
	margin: 0px 0px 20px 0px;
	-webkit-border-radius: 3px;
	border-radius: 3px;
	border-right: 1px solid #CCC;
	border-left: 1px solid #CCC;
	border-top: 1px solid #CCC;
	border-bottom: 1px solid #CCC;
	width: auto;
	background: var(--colorbacktabcard1);
}
div.tabBar tr.titre td {
	padding-top: 20px;
}
div.fiche table:not(.table-fiche-title) tr.titre td {
	padding-top: 10px;
}

/*
div.tabBar.tabBarNoTop {
	padding-top: 0;
	border-top: 0;
}
*/

/* tabBar used for creation/update/send forms */
div.tabBarWithBottom {
	padding-bottom: 18px;
	border-bottom: 1px solid #aaa;
}
div.tabBar table.tableforservicepart2:last-child {
	border-bottom: 1px solid #aaa;
}
.tableforservicepart1 .tdhrthin {
	height: unset;
}
/* Payment Screen : Pointer cursor in the autofill image */
.AutoFillAmount {
	cursor:pointer;
}

/* ============================================================================== */
/* Buttons for actions                                                            */
/* ============================================================================== */

div.divButAction {
	margin-bottom: 1.4em;
}
div.tabsAction {
	margin: 20px 0em 20px 0em;
	padding: 0em 0em;
	text-align: right;
}
div.tabsActionNoBottom {
	margin-bottom: 0px;
}
div.tabsAction > a {
	margin-bottom: 16px !important;
}

div.popuptabset {
	padding: 6px;
	background: #fff;
	border: 1px solid #888;
}
div.popuptab {
	padding-top: 5px;
	padding-bottom: 5px;
	padding-left: 5px;
	padding-right: 5px;
}

a.tabTitle {
	color:rgba(0,0,0,.5);
	margin-<?php print $right; ?>: 10px;
	text-shadow:1px 1px 1px #ffffff;
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
	padding: 4px 6px 2px 6px;
	margin: 0px 6px;
	text-decoration: none;
	white-space: nowrap;
}
.tabTitleText {
	display: none;
}
.imgTabTitle {
	max-height: 14px;
}
div.tabs div.tabsElem:first-of-type a.tab {
	margin-left: 0px !important;
}

a.tabunactive {
	color: var(--colortextlink) !important;
}
a.tab:link, a.tab:visited, a.tab:hover, a.tab#active {
	font-family: <?php print $fontlist ?>;
	padding: 12px 13px 12px;
	margin: 0em 0.2em;
	text-decoration: none;
	white-space: nowrap;
	background-image: none !important;
}

.tabactive, a.tab#active {
	color: var(--colortextbacktab) !important;
	background: var(--colorbacktabcard1) !important;

	border-right: 1px solid #AAA !important;
	border-left: 1px solid #AAA !important;
	border-top: 2px solid #111 !important;
}
.tabunactive, a.tab#unactive {
	border-right: 1px solid transparent;
	border-left: 1px solid transparent;
	border-top: 1px solid transparent;
	border-bottom: 0px !important;
}

a.tab:hover
{
	/*
	background: var(--colorbacktabcard1) url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/nav-overlay3.png', 1); ?>) 50% 0 repeat-x;
	color: var(--colortextbacktab);
	*/
	text-decoration: underline;
}
a.tabimage {
	color: #434956;
	font-family: <?php print $fontlist ?>;
	text-decoration: none;
	white-space: nowrap;
}

td.tab {
	background: #dee7ec;
}

span.tabspan {
	background: #dee7ec;
	color: #434956;
	font-family: <?php print $fontlist ?>;
	padding: 0px 6px;
	margin: 0em 0.2em;
	text-decoration: none;
	white-space: nowrap;
	-webkit-border-radius:3px 3px 0px 0px;
	border-radius:3px 3px 0px 0px;

	border-<?php print $right; ?>: 1px solid #555555;
	border-<?php print $left; ?>: 1px solid #D8D8D8;
	border-top: 1px solid #D8D8D8;
}

/* ============================================================================== */
/* Buttons for actions                                                            */
/* ============================================================================== */
<?php include dol_buildpath($path.'/theme/'.$theme.'/btn.inc.php', 0); ?>



/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.allwidth {
	width: 100%;
}

#undertopmenu {
	background-repeat: repeat-x;
	margin-top: <?php echo($dol_hide_topmenu ? '6' : '0'); ?>px;
}

.paddingrightonly {
	border-collapse: collapse;
	border: 0px;
	margin-left: 0px;
	padding-<?php print $left; ?>: 0px !important;
	padding-<?php print $right; ?>: 4px !important;
}
.nocellnopadd {
	list-style-type:none;
	margin: 0px !important;
	padding: 0px !important;
}
.noborderspacing {
	border-spacing: 0;
}
tr.nocellnopadd td.nobordernopadding, tr.nocellnopadd td.nocellnopadd
{
	border: 0px;
}

.unsetcolor {
	color: unset !important;
}

.smallpaddingimp {
	padding: 4px !important;
}
input.buttonlink {
	color: var(--colortextlink);
	background-color: transparent;
	cursor: pointer;
}
input.buttonlink:hover {
	text-decoration: underline;
}
input.buttonreset {
	margin-top: 3px;
	margin-bottom: 3px;
	padding: 8px 15px;
	text-decoration: underline;
	color: var(--colortextlink);
	background-color: transparent;
	cursor: pointer;
}

.notopnoleft {
	border-collapse: collapse;
	border: 0px;
	padding-top: 0px;
	padding-<?php print $left; ?>: 0px;
	padding-<?php print $right; ?>: 16px;
	padding-bottom: 4px;
	margin-right: 0px;
}
.notopnoleftnoright {
	border-collapse: collapse;
	border: 0px;
	padding-top: 0px;
	padding-left: 0px;
	padding-right: 0px;
	padding-bottom: 4px;
	margin: 0px 0px 0px 0px;
}

table.tableforemailform tr td {
	padding-top: 3px;
	padding-bottom: 3px;
}

table.border, table.bordernooddeven, table.dataTable, .table-border, .table-border-col, .table-key-border-col, .table-val-border-col, div.border {
	/* border: 1px solid #f4f4f4; */
	border-collapse: collapse !important;
	padding: 1px 2px 1px 3px;			/* t r b l */
}
table.borderplus {
	border: 1px solid #BBB;
}

.border tbody tr, .bordernooddeven tbody tr, .border tbody tr td, .bordernooddeven tbody tr td,
div.tabBar table.border tr, div.tabBar table.border tr td, div.tabBar div.border .table-border-row, div.tabBar div.border .table-key-border-col, div.tabBar div.border .table-val-border-col,
tr.liste_titre.box_titre td table td, .bordernooddeven tr td {
	height: 28px;
}

table.border td, table.bordernooddeven td, div.border div div.tagtd {
	padding: 3px 4px 3px 4px;
	/* border: 1px solid #f0f0f0; */
	border-collapse: collapse;
}

td.border, div.tagtable div div.border {
	border-top: 1px solid #000000;
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	border-left: 1px solid #000000;
}

.table-key-border-col {
	/* width: 25%; */
	vertical-align:top;
}
.table-val-border-col {
	width:auto;
}


.thsticky, .tdsticky {
	position: sticky;
	left: 0px;
}
.thstickyright, .tdstickyright {
	position: sticky;
	right: 0px;
}
.thstickygray, .tdstickygray {
	background-color: lightgray;
}
.thstickyghostwhite, .tdstickyghostwhite {
	background-color: ghostwhite;
}
.thstickyinherit, .tdstickyinherit {
	background-color: inherit;
}

<?php if (getDolGlobalString('THEME_ENABLE_STICKY_COLUMN_REF')) { ?>
/* To have left column sticky */
.tagtable td[data-key="ref"], .tagtable th[data-key="ref"] {
	position: sticky;
	left: 0;
	top: 0;
	max-width: 150px !important;
	/*background-color: inherit;*/
	background-color: gainsboro;
	z-index: 2;
}
<?php } ?>

<?php if (getDolGlobalString('THEME_ENABLE_STICKY_COLUMN_ACTION')) { ?>
/* To have right column sticky */
.tagtable td.actioncolumn, .tagtable th.actioncolumn {
	position: sticky;
	right: 0;
	top: 0;
	max-width: 150px !important;
	/*background-color: inherit;*/
	background-color: gainsboro;
	z-index: 2;
}
<?php } ?>



/* Main boxes */
.nobordertop, .nobordertop tr:first-of-type td {
	border-top: none !important;
}
.noborderbottom, .noborderbottom tr:last-of-type td {
	border-bottom: none !important;
}
.bordertop {
	border-top: 1px solid var(--colortopbordertitle1);
}
.borderbottom {
	border-bottom: 1px solid var(--colortopbordertitle1);
}

.fichehalfright table.noborder {
	margin: 0px 0px 0px 0px;
}
div.colorback
{
	background: var(--colorbacktitle1);
	padding: 10px;
	margin-top: 5px;
}
.liste_titre_bydiv {
	<?php if ($userborderontable) { ?>
	border-right: 1px solid #ccc;
	border-left: 1px solid #ccc;
	<?php } ?>
}
table.liste, table.noborder:not(.paymenttable):not(.margintable):not(.tableforcontact), table.formdoc, div.noborder:not(.paymenttable):not(.margintable):not(.tableforcontact) {
	<?php
	if ($userborderontable) { ?>
	/* border-left: 1px solid var(--colortopbordertitle1);
	border-right: 1px solid var(--colortopbordertitle1); */
	border-left: 1px solid #ccc;
	border-right: 1px solid #ccc;
	<?php } else { ?>
	border-left: 1px solid #f0f0f0;
	border-right: 1px solid #f0f0f0;
	<?php } ?>
}
table.liste, table.noborder, table.formdoc, div.noborder {
	width: calc(100% - 2px);	/* -2 to fix a bug. Without, a scroll appears due to overflow-x: auto; of div-table-responsive */

	border-collapse: separate !important;
	border-spacing: 0px;

	border-top-width: <?php echo $borderwidth ?>px;
	border-top-color: var(--colortopbordertitle1);
	border-top-style: solid;

	border-bottom-width: 1px;
	border-bottom-color:  #BBB;
	border-bottom-style: solid;

	margin: 0px 0px 20px 0px;

	-webkit-border-radius: 0.1em;
	border-radius: 0.1em;
}
table.noborder tr, div.noborder form {
	border-top-color: #FEFEFE;

	border-right-width: 1px;
	border-right-color: #BBBBBB;
	border-right-style: solid;

	border-left-width: 1px;
	border-left-color: #BBBBBB;
	border-left-style: solid;
	min-height: 26px;
}
table.paddingtopbottomonly tr td {
	padding-top: 1px;
	padding-bottom: 2px;
}
/* CSS to remove the interline border */
table.nointerlines tr:not(:last-child) td {
	border-bottom: unset !important;
	border-top: unset !important;
}

.liste_titre_filter {
	background: var(--colorbacktitle1) !important;
}
tr.liste_titre_filter td.liste_titre {
	padding-top: 4px;
	padding-bottom: 3px;
}
.liste_titre_create td, .liste_titre_create th, .liste_titre_create .tagtd
{
	border-top-width: 1px;
	border-top-color: var(--colortopbordertitle1);
	border-top-style: solid;
}
tr#trlinefordates td {
	border-bottom: 0px !important;
}
.liste_titre_add td, .liste_titre_add th, .liste_titre_add .tagtd
{
	border-top-width: 2px;
	border-top-color: var(--colortopbordertitle1);
	border-top-style: solid;
}
.liste_titre_add td, .liste_titre_add .tagtd
{
	border-top-width: 1px;
	border-top-color: var(--colortopbordertitle1);
	border-top-style: solid;
}

table.liste th, table.noborder th, table.noborder tr.liste_titre td, table.noborder tr.box_titre td {
	padding: 8px 8px 8px 10px;			/* t r b l */
}

table.liste td, table.noborder td, div.noborder form div, table.tableforservicepart1 td, table.tableforservicepart2 td {
	padding: 4px 8px 4px 10px;			/* t r b l */
	height: 22px;
}
table.liste tr.trkanban td {
	padding: 12px 15px 12px 15px;			/* t r b l */
}

form.tagtable {
	padding: unset !important;
	border: unset !important;
}

table.liste td, table.noborder td, div.noborder form div {
	padding: 8px 6px 8px 6px;			/* t r b l */
}
div.liste_titre_bydiv .divsearchfield {
	padding: 2px 1px 2px 6px;			/* t r b l */
}

table.nobordernopadding {
	border-collapse: collapse !important;
	border: 0;
}
table.nobordernopadding tr {
	border: 0 !important;
	padding: 0 0 !important;
}
table.nobordernopadding tr td {
	border: 0 !important;
	padding: 0 3px 0 0;
}
table.border tr td table.nobordernopadding tr td {
	padding-top: 0;
	padding-bottom: 0;
}
td.borderright {
	border: none;	/* to erase value for table.nobordernopadding td */
	border-right-width: 1px !important;
	border-right-color: #BBB !important;
	border-right-style: solid !important;
}
td.borderleft {
	border: none;	/* to erase value for table.nobordernopadding td */
	border-left-width: 1px !important;
	border-left-color: #BBB !important;
	border-left-style: solid !important;
}

/* For table with no filter before */
table.listwithfilterbefore {
	border-top: none !important;
}

.tagtable, .table-border { display: table; }
.tagtr, .table-border-row  { display: table-row; }
.tagtd, .table-border-col, .table-key-border-col, .table-val-border-col { display: table-cell; }
.confirmquestions .tagtr .tagtd:not(:first-child)  { padding-left: 10px; }
.confirmquestions { margin-top: 5px; }


/* Pagination */
div.refidpadding  {
	padding-top: 3px;
}
div.refid  {
	font-weight: bold;
	color: var(--colortexttitlenotab);
	font-size: 120%;
}
a.refid {
	color: var(--colortexttitlenotab) !important;
}
div.refidno  {
	padding-top: 8px;
	font-weight: normal;
	color: var(--refidnocolor);
	font-size: 92%;
	line-height: 1.3em;
}
div.refidno form {
	display: inline-block;
}
div.refaddress div.address {
	line-height: 1.2em;
	font-size: 0.9em;
}

div.pagination {
	float: right;
}
div.pagination a {
	font-weight: normal;
}
div.pagination ul
{
  list-style: none;
  display: inline-block;
  padding-left: 0px;
  padding-right: 0px;
  margin: 0;
}
div.pagination li {
  display: inline-block;
  padding-left: 0px;
  padding-right: 0px;
  /* padding-top: 6px;
  padding-bottom: 5px; */
}
.pagination {
  display: inline-block;
  padding-left: 0;
  border-radius: 4px;
}

div.pagination li.pagination a,
div.pagination li.pagination span {
  padding: 6px 12px;
  padding-top: 8px;
  line-height: 1.42857143;
  color: var(--color-black);
  text-decoration: none;
}
div.pagination li.pagination span.inactive {
  cursor: default;
  color: #ccc;
}

div.pagination li.litext a {
border: none;
  padding-right: 10px;
  padding-left: 4px;
  font-weight: bold;
}
div.pagination li.noborder a:hover {
  border: none;
  background-color: transparent;
}
div.pagination li:first-child a,
div.pagination li:first-child span {
  margin-left: 0;
  border-top-left-radius: 4px;
  border-bottom-left-radius: 4px;
}
div.pagination li:last-child a,
div.pagination li:last-child span {
  border-top-right-radius: 4px;
  border-bottom-right-radius: 4px;
}
div.pagination li a:hover,
div.pagination li span:hover,
div.pagination li a:focus,
div.pagination li span:focus {
	color: #000;
	background-color: #eee;
	border-color: #ddd;
	  /* padding-top: 8px; */
}
div.pagination li .active a,
div.pagination li .active span,
div.pagination li .active a:hover,
div.pagination li .active span:hover,
div.pagination li .active a:focus,
div.pagination li .active span:focus {
  z-index: 2;
  color: #fff;
  cursor: default;
  background-color: var(--colorbackhmenu1);
  border-color: #337ab7;
}
div.pagination .disabled span,
div.pagination .disabled span:hover,
div.pagination .disabled span:focus,
div.pagination .disabled a,
div.pagination .disabled a:hover,
div.pagination .disabled a:focus {
  color: #777;
  cursor: not-allowed;
  background-color: #fff;
  border-color: #ddd;
}
div.pagination li.pagination .active {
  text-decoration: underline;
  box-shadow: none;
}
.paginationafterarrows .nohover {
  box-shadow: none !important;
}
div.pagination li.paginationafterarrows {
	margin-left: 10px;
}
.paginationatbottom {
	margin-top: 9px;
}
table.hidepaginationprevious .paginationprevious {
	display: none;
}
table.hidepaginationnext .paginationnext {
	display: none;
}
.paginationafterarrows a.btnTitlePlus:hover span:before {
	/* text-shadow: 0px 0px 5px #ccc; */
	/* filter: invert(0.3); */
	font-size: 1.03em;
}


/* Prepare to remove class pair - impair
.noborder > tbody > tr:nth-child(even) td {
	background: linear-gradient(to bottom, var(--colorbacklineimpai2) 85%, var(--colorbacklineimpair2) 100%);
	background: -o-linear-gradient(bottom, var(--colorbacklineimpair2) 85%, var(--colorbacklineimpair2) 100%);
	background: -moz-linear-gradient(bottom, var(--colorbacklineimpair2) 85%, var(--colorbacklineimpair2) 100%);
	background: -webkit-linear-gradient(bottom, var(--colorbacklineimpair2) 85%, var(--colorbacklineimpair2) 100%);
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
	min-height: 18px;
}

.noborder > tbody > tr:nth-child(odd) td {
	background: linear-gradient(to bottom, var(--colorbacklinepair2) 85%, var(--colorbacklinepair2) 100%);
	background: -o-linear-gradient(bottom, var(--colorbacklinepair2) 85%, var(--colorbacklinepair2) 100%);
	background: -moz-linear-gradient(bottom, var(--colorbacklinepair2) 85%, var(--colorbacklinepair2) 100%);
	background: -webkit-linear-gradient(bottom, var(--colorbacklinepair2) 85%, var(--colorbacklinepair2) 100%);
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
}
*/

ul.noborder li:nth-child(odd):not(.liste_titre) {
	background-color: var(--colorbacklinepair2) !important;
}


/* Set the color for hover lines */

.tmenucompanylogo.nohover, .tmenucompanylogo.nohover:hover {
	opacity: unset !important;
}
.nohoverborder:hover {
	border: unset;
	box-shadow: unset;
	-webkit-box-shadow: unset;
}

.oddeven:hover, .evenodd:hover, .oddevenimport:hover, .evenoddimport:hover, .impair:hover, .pair:hover
{
	background: rgb(<?php echo $colorbacklinepairhover; ?>) !important;
}
.tredited {
	background: rgb(<?php echo $colorbacklinepairchecked; ?>) !important;   /* Must be background to be stronger than background of odd or even */
}
<?php if ($colorbacklinepairchecked) { ?>
.highlight {
	background: rgb(<?php echo $colorbacklinepairchecked; ?>) !important; /* Must be background to be stronger than background of odd or even */
}
<?php } ?>

.nohover:hover {
	background: unset !important;
}
.nohoverborder:hover {
	border: unset;
	box-shadow: unset;
	-webkit-box-shadow: unset;
}

.oddeven, .evenodd, .impair, .pair, .nohover .impair:hover, tr.impair td.nohover, tr.pair td.nohover, .tagtr.oddeven
{
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: var(--oddevencolor);
}
.impair, .nohover .impair:hover, tr.impair td.nohover
{
	background: var(--colorbacklineimpair2);
}
.pair, .nohover .pair:hover, tr.pair td.nohover {
	background-color: var(--colorbacklinepair1);
}
tr.oddeven.oddevendouble {
	height: 60px !important;
}

#GanttChartDIV {
	background-color: var(--colorbacklineimpair2);
}
table.dataTable tr.oddeven {
	background-color: var(--colorbacklinepair1) !important;
}

/* For no hover style */
td.oddeven, table.nohover tr.impair, table.nohover tr.pair, table.nohover tr.impair td, table.nohover tr.pair td, tr.nohover td, form.nohover, form.nohover:hover {
	background-color: var(--colorbacklineimpair2) !important;
	background: var(--colorbacklineimpair2) !important;
}
td.evenodd, tr.nohoverpair td, #trlinefordates td {
	background-color: var(--colorbacklinepair2) !important;
	background: var(--colorbacklinepair2) !important;
}
.trforbreak td {
	font-weight: bold;
	border-bottom: 1pt solid black !important;
	background-color: var(--colorbacklinebreak) !important;
}
.trforbreak.nobold td a, .trforbreak.nobold span.secondary {
	font-weight: normal !important;
}

table.dataTable td {
	padding: 5px 2px 5px 3px !important;
}
tr.pair td, tr.impair td, form.impair div.tagtd, form.pair div.tagtd, div.impair div.tagtd, div.pair div.tagtd, div.liste_titre div.tagtd {
	padding: 5px 2px 5px 3px;
	border-bottom: 1px solid #eee;
}
form.pair, form.impair {
	font-weight: normal;
}
tr.pair:last-of-type td, tr.impair:last-of-type td {
	border-bottom: 0px !important;
}
tr.pair td .nobordernopadding tr td, tr.impair td .nobordernopadding tr td {
	border-bottom: 0px !important;
}
/*
table.nobottomiftotal tr.liste_total td {
	background-color: var(--inputbackgroundcolor);
	<?php if (!$userborderontable) { ?>
	border-bottom: 0px !important;
	<?php } ?>
}
*/
div.liste_titre .tagtd {
	vertical-align: middle;
}
div.liste_titre {
	min-height: 26px !important;	/* We can't use height because it's a div and it should be higher if content is more. but min-height doe not work either for div */

	padding-top: 2px;
	padding-bottom: 2px;

	/*border-right-width: 1px;
	border-right-color: #BBB;
	border-right-style: solid;

	border-left-width: 1px;
	border-left-color: #BBB;
	border-left-style: solid;*/

	border-top-width: 1px;
	border-top-color: #BBB;
	border-top-style: solid;
}
div.liste_titre_bydiv {
	<?php if ($userborderontable) { ?>
	border-top-width: <?php echo $borderwidth ?>px;
	border-top-color: var(--colortopbordertitle1);
	border-top-style: solid;
	<?php } ?>
	border-collapse: collapse;
	display: table;
	padding: 2px 0px 2px 0;
	box-shadow: none;
	width: calc(100% - 2px);	/* -2px because the width for table class="tagtable" under this is cal(100% - 2px) so it is aligned. */
}
/*
div.liste_titre_bydiv_inlineblock {
	display: inline-block;
	width: 100%;
}
*/

tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel, table.dataTable.tr, tagtr.liste_titre
{
	height: 26px !important;
}
div.liste_titre_bydiv, .liste_titre div.tagtr, tr.liste_titre, tr.liste_titre_sel, .tagtr.liste_titre, .tagtr.liste_titre_sel, form.liste_titre, form.liste_titre_sel, table.dataTable thead tr
{
	background: var(--colorbacktitle1);
	font-weight: <?php echo $useboldtitle ? 'bold' : 'normal'; ?>;
	/* border-bottom: 1px solid #FDFFFF; */

	color: var(--colortexttitle);
	font-family: <?php print $fontlist ?>;
	text-align: <?php echo $left; ?>;
}
tr.liste_titre th, tr.liste_titre td, th.liste_titre
{
	border-bottom: 1px solid #aaa;
}
/* TODO Once the title line is moved under title search, make th border bottoms black and force to white when it's the first tr */
tr:first-child th.liste_titre, tr:first-child th.liste_titre_sel {
	border-bottom: 1px solid #FFF ! important;
}
tr.liste_titre th, th.liste_titre, tr.liste_titre td, td.liste_titre, form.liste_titre div, div.liste_titre
{
	font-family: <?php print $fontlist ?>;
	font-weight: <?php echo $useboldtitle ? 'bold' : 'normal'; ?>;
	vertical-align: middle;
	height: 28px;
}
tr.liste_titre th a, th.liste_titre a, tr.liste_titre td a, td.liste_titre a, form.liste_titre div a, div.liste_titre a {
	text-shadow: none !important;
	color: var(--colortexttitlelink);
}
tr.liste_titre_topborder td {
	border-top-width: <?php echo $borderwidth; ?>px;
	border-top-color: var(--colortopbordertitle1);
	border-top-style: solid;
}
.liste_titre td a {
	text-shadow: none !important;
	color: var(--colortexttitle);
}
.liste_titre td a.notasortlink {
	color: var(--colortextlink);
}
.liste_titre td a.notasortlink:hover {
	background: transparent;
}
tr.liste_titre:last-child th.liste_titre, tr.liste_titre:last-child th.liste_titre_sel, tr.liste_titre td.liste_titre, tr.liste_titre td.liste_titre_sel, form.liste_titre div.tagtd {				/* For last line of table headers only */
	/* border-bottom: 1px solid var(--colortopbordertitle1); */
	border-bottom: none;
}
tr.liste_titre_filter th.liste_titre { text-align: unset; }

div.liste_titre {
	padding-left: 3px;
}
tr.liste_titre_sel th, th.liste_titre_sel, tr.liste_titre_sel td, td.liste_titre_sel, form.liste_titre_sel div
{
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
	border-bottom: 1px solid #FDFFFF;
	/* text-decoration: underline; */
}
input.liste_titre {
	background: transparent;
	border: 0px;
}

.noborder tr.liste_total td, tr.liste_total td, form.liste_total div, .noborder tr.liste_total_wrap td, tr.liste_total_wrap td, form.liste_total_wrap div {
	color: #332266;
	/* padding: 4px; */
}
.noborder tr.liste_total td, tr.liste_total td, form.liste_total div {
	white-space: nowrap;
	line-height: 1.5em;
}
.noborder tr.liste_total_wrap td, tr.liste_total_wrap td, form.liste_total_wrap div {
	white-space: normal;
}

tr.liste_sub_total, tr.liste_sub_total td {
	border-bottom: 2px solid #aaa;
}

.tableforservicepart1 .impair, .tableforservicepart1 .pair, .tableforservicepart2 .impair, .tableforservicepart2 .pair {
	background: #FFF;
}
.tableforservicepart1 tbody tr td, .tableforservicepart2 tbody tr td {
	border-bottom: none;
}

.paymenttable, .margintable {
	margin: 0px 0px 0px 0px !important;
}
.paymenttable, .margintable:not(.margintablenotop) {
	border-top-width: <?php echo $borderwidth ?>px !important;
	border-top-color: var(--colortopbordertitle1) !important;
	border-top-style: solid !important;
}
.margintable.margintablenotop {
	border-top-width: 0;
}
.paymenttable tr td:first-child, .margintable tr td:first-child
{
	/*padding-left: 2px;*/
}
.paymenttable, .margintable tr td {
	height: 22px;
}

/* Disable shadows */
.noshadow {
	-webkit-box-shadow: 0px 0px 0px #f4f4f4 !important;
	box-shadow: 0px 0px 0px #f4f4f4 !important;
}
.shadow {
	-webkit-box-shadow: 2px 2px 5px #CCC !important;
	box-shadow: 2px 2px 5px #CCC !important;
}

.boxshadow {
	-webkit-box-shadow: 0px 0px 5px #888;
	box-shadow: 0px 0px 5px #888;
}

div.tabBar .noborder {
	-webkit-box-shadow: 0px 0px 0px #f4f4f4 !important;
	box-shadow: 0px 0px 0px #f4f4f4 !important;
}
div .tdtop:not(.tagtdnote) {
	vertical-align: top !important;
	padding-top: 8px !important;
	padding-bottom: 0px !important;
}

#tablelines tr.liste_titre td, #tablelinesservice tr.liste_titre td, .paymenttable tr.liste_titre td, .margintable tr.liste_titre td, .tableforservicepart1 tr.liste_titre td {
	border-bottom: 1px solid #AAA !important;
}
#tablelines tr td, #tablelinesservice tr td {
	height: unset;
}


/* Prepare to remove class pair - impair */

.noborder > tbody > tr:nth-child(even):not(.liste_titre):not(.nooddeven),
.liste > tbody > tr:nth-child(even):not(.liste_titre):not(.nooddeven),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright) > .border > tbody > table:not(.tableforfieldedit) > tr:nth-of-type(even):not(.liste_titre):not(.nooddeven),
.liste > tbody > tr:nth-of-type(even):not(.liste_titre):not(.nooddeven),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright) .oddeven.tagtr:nth-of-type(even):not(.liste_titre):not(.nooddeven)
{
	background: linear-gradient(to bottom, var(--colorbacklineimpair2) 0%, var(--colorbacklineimpair2) 100%);
	background: -o-linear-gradient(bottom, var(--colorbacklineimpair2) 0%, var(--colorbacklineimpair2) 100%);
	background: -moz-linear-gradient(bottom, var(--colorbacklineimpair2) 0%, var(--colorbacklineimpair2) 100%);
	background: -webkit-linear-gradient(bottom, var(--colorbacklineimpair2) 0%, var(--colorbacklineimpair2) 100%);
}
.noborder > tbody > tr:nth-child(even):not(:last-of-type) td:not(.liste_titre),
.liste > tbody > tr:nth-child(even):not(:last-of-type) td:not(.liste_titre),
.noborder .tagtr:nth-child(even):not(:last-of-type) .oddeven.tagtd:not(.liste_titre)
{
	border-bottom: 1px solid #e8e8e8;
}

.noborder > tbody > tr:nth-child(odd):not(.liste_titre):not(.nooddeven),
.liste > tbody > tr:nth-child(odd):not(.liste_titre):not(.nooddeven),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright) > .border > tbody > table:not(.tableforfieldedit) > tr:nth-of-type(odd):not(.liste_titre):not(.nooddeven),
.liste > tbody > tr:nth-of-type(odd):not(.liste_titre):not(.nooddeven),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright) .oddeven.tagtr:nth-of-type(odd):not(.liste_titre):not(.nooddeven)
{
	background: linear-gradient(to bottom, var(--colorbacklinepair1) 0%, var(--colorbacklinepair2) 100%);
	background: -o-linear-gradient(bottom, var(--colorbacklinepair1) 0%, var(--colorbacklinepair2) 100%);
	background: -moz-linear-gradient(bottom, var(--colorbacklinepair1) 0%, var(--colorbacklinepair2) 100%);
	background: -webkit-linear-gradient(bottom, var(--colorbacklinepair1) 0%, var(--colorbacklinepair2) 100%);
}
.noborder > tbody > tr:nth-child(odd):not(:last-child) td:not(.liste_titre),
.liste > tbody > tr:nth-child(odd):not(:last-child) td:not(.liste_titre),
.noborder .tagtr:nth-child(odd):not(:last-child) .oddeven.tagtd:not(.liste_titre)
{
	border-bottom: 1px solid #e8e8e8;
}

ul.noborder li:nth-child(even):not(.liste_titre) {
	background-color: var(--colorbacklinepair2) !important;
}


/*
 *  Boxes
 */

.box {
	overflow-x: auto;
	min-height: 40px;
	padding-right: 0px;
	padding-left: 0px;
	padding-bottom: 12px;
}
.boxstatsborder {
	/* border: 1px solid #CCC !important; */
}
.boxstats, .boxstats130 {
	display: inline-block;
	margin: 8px;
	/* border: 1px solid #CCC; */
	text-align: center;
	border-radius: 2px;
	background: #eee;
}
.boxstats, .boxstats130, .boxstatscontent {
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.boxstats {
	padding: 3px;
	width: 100px;
	min-height: 40px;
}
.boxstats130 {
	width: 135px;
	height: 54px;
	padding: 3px;
}
@media only screen and (max-width: 768px)
{
	.tabBar .arearef .pagination.paginationref {
		max-width: calc(50%);
	}

	div.pagination ul li {
		margin-top: 3px;
		margin-bottom: 3px;
	}
	div.pagination .button-title-separator {
		display: none;
	}

	.clearbothonsmartphone {
		clear: both;
		display: block !important;
	}

	div.tabs {
		padding-left: 0 !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
	}

	td.tdwidgetstate {
		text-align: center;
	}

	.boxstats, .boxstats130 {
		margin: 3px;
		border: 1px solid #ddd;
		box-shadow: none;
		background: #eee;
	}
	.thumbstat {
		flex: 1 1 110px;
	}
	.thumbstat150 {
		flex: 1 1 110px;
	}
	.dashboardlineindicator {
		float: left;
		padding-left: 5px;
	}
	.boxstats130 {
		width: 148px;
	}
	.boxstats {
		width: 100px;
	}
}
.boxstats:hover {
	box-shadow: 0px 0px 8px 0px rgba(0,0,0,0.20);
}
span.boxstatstext {
	/* opacity: 0.7; */		/* a bug if browser make z-index infintie when opacity is set so we disable it */
	line-height: 18px;
	color: #000;
}
.boxstatsindicator.thumbstat150 {	/* If we remove this, box position is ko on ipad */
	display: inline-flex;
}
span.boxstatsindicator {
	font-size: 110%;
	font-weight: normal;
	color: rgb(<?php print $colortextlink; ?>);
}
span.dashboardlineindicator, span.dashboardlineindicatorlate {
	font-size: 120%;
	font-weight: normal;
}
a.dashboardlineindicatorlate:hover {
	text-decoration: none;
}
.dashboardlineindicatorlate img {
	width: 16px;
}
span.dashboardlineok {
	color: #008800;
}
span.dashboardlineko {
	color: #FFF;
	font-size: 80%;
}
.dashboardlinelatecoin {
	float: right;
	position: relative;
	text-align: right;
	top: -24px;
	padding: 1px 6px 1px 6px;
	background-color: #8c4446;
	color: #FFFFFF ! important;
	border-radius: .25em;
}
.divboxtable {
	margin-bottom: 20px !important;
}
.boxtable {
	border-bottom-width: 1px;
}
.boxtablenotop {
	/* border-top-width: 0 !important; */
}
.boxtablenobottom {
	/* border-bottom-width: 0 !important; */
}
.boxtablenomarginbottom {
	margin-bottom: 0 !important;
}
.boxtable .fichehalfright, .boxtable .fichehalfleft {
	min-width: 275px;
}
.tdboxstats {
	text-align: center;
}
.boxworkingboard .tdboxstats {
	padding-left: 1px !important;
	padding-right: 1px !important;
}
a.valignmiddle.dashboardlineindicator {
	line-height: 30px;
}
.height30 {
	height: 30px !important;
}
.height50 {
	height: 50px !important;
}

tr.box_titre {
	height: 26px !important;

	/* TO MATCH BOOTSTRAP */
	/*background: #ddd;
	color: #000 !important; */

	/* TO MATCH ELDY */
	background: var(--colorbacktitle1);
	color: var(--colortexttitle);
	font-family: <?php print $fontlist ?>, sans-serif;
	font-weight: <?php echo $useboldtitle ? 'bold' : 'normal'; ?>;
	border-bottom: 1px solid #FDFFFF;
	white-space: nowrap;
}

tr.box_titre td.boxclose {
	width: 30px;
}
img.boxhandle, img.boxclose {
	padding-left: 5px;
}

.formboxfilter {
	vertical-align: middle;
	margin-bottom: 6px;
}
.formboxfilter input[type=image]
{
	top: 5px;
	position: relative;
}
.boxfilter {
	margin-bottom: 2px;
	margin-right: 1px;
}

.prod_entry_mode_free, .prod_entry_mode_predef {
	height: 26px !important;
	vertical-align: middle;
}

.modulebuilderbox {
	border: 1px solid #888;
	padding: 16px;
}



/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #887711 !important; }
.error   { color: #550000 !important; font-weight: bold; }
.green   { color: #118822 !important; }

div.ok {
  color: #114466;
}

/* Info admin */
div.info {
	border-<?php print $left; ?>: solid 5px #87cfd2;
	padding-top: 8px;
	padding-left: 10px;
	padding-right: 4px;
	padding-bottom: 8px;
	margin: 0.5em 0em 0.5em 0em;
	background: #eff8fc;
	border-radius: 3px;
}

/* Warning message */
div.warning, div.warningborder {
	border-<?php print $left; ?>: solid 5px #f2cf87;
}
div.warning {
	padding-top: 8px;
	padding-left: 10px;
	padding-right: 4px;
	padding-bottom: 8px;
	margin: 0.5em 0em 0.5em 0em;
	background: #fcf8e3;
	border-radius: 3px;
}
div.warning a, div.info a, div.error a {
	color: rgb(<?php echo $colortextlink; ?>);
}

/* Error message */
div.error {
	border-<?php print $left; ?>: solid 5px #f28787;
	padding-top: 8px;
	padding-left: 10px;
	padding-right: 4px;
	padding-bottom: 8px;
	margin: 0.5em 0em 0.5em 0em;
	background: #EFCFCF;
	border-radius: 3px;
}


/*
 *   Liens Payes/Non payes
 */

a.normal:link { font-weight: normal }
a.normal:visited { font-weight: normal }
a.normal:active { font-weight: normal }
a.normal:hover { font-weight: normal }

a.impayee:link { font-weight: bold; color: #550000; }
a.impayee:visited { font-weight: bold; color: #550000; }
a.impayee:active { font-weight: bold; color: #550000; }
a.impayee:hover { font-weight: bold; color: #550000; }



/*
 *  External web site
 */

.framecontent {
	width: 100%;
	height: 100%;
}

.framecontent iframe {
	width: 100%;
	height: 100%;
}



/*
 *  Other
 */

.opened-dash-board-wrap {
	margin-bottom: 25px;
}

div.boximport {
	min-height: unset;
}

.product_line_stock_ok { color: #002200; }
.product_line_stock_too_low { color: #884400; }

.fieldrequired { font-weight: bold; color: #000055; }

td.widthpictotitle, .table-fiche-title img.widthpictotitle { width: 32px; font-size: 1.4em; text-align: <?php echo $left; ?>; }
table.titlemodulehelp tr td img.widthpictotitle { width: 80px; }

.dolgraphtitle { margin-top: 6px; margin-bottom: 4px; }
.dolgraphtitlecssboxes { /* margin: 0px; */ }
.dolgraphchart canvas {
	/* width: calc(100% - 20px) !important; */
}
.legendColorBox, .legendLabel { border: none !important; }
div.dolgraph div.legend, div.dolgraph div.legend div { background-color: rgba(255,255,255,0) !important; }
div.dolgraph div.legend table tbody tr { height: auto; }
td.legendColorBox { padding: 2px 2px 2px 0 !important; }
td.legendLabel { padding: 2px 2px 2px 0 !important; }
td.legendLabel {
	text-align: <?php echo $left; ?>;
}

label.radioprivate {
	white-space: nowrap;
}

.photo {
	border: 0px;
}
.photowithmargin {
/*	margin-bottom: 2px;
	margin-top: 2px; */
}
div.divphotoref > img.photowithmargin, div.divphotoref > a > .photowithmargin {		/* Margin right for photo not inside a div.photoref frame only */
	margin-right: 15px;
}
.photowithborder {
	border: 1px solid #f0f0f0;
}
.photointooltip {
	margin-top: 8px;
	margin-bottom: 6px;
	text-align: center !important;
}
.photodelete {
	margin-top: 6px !important;
}

.logo_setup
{
	content:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/logo_setup.svg', 1) ?>);	/* content is used to best fit the container */
	display: inline-block;
	opacity: 0.2;
}
.nographyet
{
	content:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/nographyet.svg', 1) ?>);
	display: inline-block;
	opacity: 0.1;
	background-repeat: no-repeat;
}
.nographyettext
{
	opacity: 0.5;
}

div.titre {
	font-size: 14px;
	text-decoration: none;
	padding-top: 5px;
	padding-bottom: 5px;
	text-transform: uppercase;
	/* text-shadow: 1px 1px 2px #FFFFFF; */
}
div.titre.small {
	font-size: 1em;
}
div.titre {
	color: var(--colortexttitlenotab);
}
.secondary {
	color: var(--colortexttitlenotab);
}
.tertiary {
	color: var(--colortexttitlenotab2);
}

table.notopnoleftnoright.table-fiche-title {
	margin-bottom: 14px;
}
.fichehalfleft table.table-fiche-title, .fichehalfright table.table-fiche-title {
	margin-bottom: 8px;
}


table.table-fiche-title .col-title div.titre, .col-center .btnTitle-icon, .col-right .btnTitle-icon {
	line-height: 40px;
}

div.backgreypublicpayment {
	background-color: #f0f0f0;
	padding: 20px;
	border-bottom: 1px solid #ddd;
	text-align: center;
	position: sticky;
	top: 0;
	z-index: 1005;
}
.backgreypublicpayment a { color: #222 !important; }
.poweredbypublicpayment {
	float: right;
	top: 8px;
	right: 8px;
	position: absolute;
	font-size: 0.8em;
	color: #222;
	opacity: 0.3;
}
span.buttonpaymentsmall {
	text-shadow: none;
}

#dolpublictable {
	min-width: 300px; font-size: 16px;
	padding: 6px;
}
#dolpaymenttable {
	min-width: 320px; font-size: 16px;
}	/* Width must have min to make stripe input area visible. Lower than 320 makes input area crazy for credit card that need zip code */

#tablepublicpayment { border: 1px solid #CCCCCC !important; width: 100%; padding: 20px; margin-bottom: 20px; }
#tablepublicpayment .CTableRow1  { background-color: #F0F0F0 !important; }
#tablepublicpayment tr.liste_total { border-bottom: 1px solid #CCCCCC !important; }
#tablepublicpayment tr.liste_total td { border-top: none; }

.divmainbodylarge { margin-left: 40px; margin-right: 40px; }
.publicnewmemberform div.titre { font-size: 2em; }
#divsubscribe { max-width: 900px; }
#divsubscribe .eventlabel { font-size: 1.5em; }
#tablesubscribe { width: 100%; }
#tablesubscribe tr td { font-size: 1.15em; }
#tablesubscribe .price-registration { font-size: 1.5em; }


div#card-element {
	border: 1px solid #ccc;
}
div#card-errors {
	color: #fa755a;
	text-align: center;
	padding-top: 3px;
	/* max-width: 320px; */
}


/*
 * Effect Postit
 */

.effectpostit
{
  position: relative;
}
.effectpostit:before, .effectpostit:after
{
  z-index: -1;
  position: absolute;
  content: "";
  bottom: 15px;
  left: 10px;
  width: 50%;
  top: 80%;
  max-width:300px;
  background: #777;
  -webkit-box-shadow: 0 15px 10px #777;
  box-shadow: 0 15px 10px #777;
  -webkit-transform: rotate(-3deg);
  -moz-transform: rotate(-3deg);
  -o-transform: rotate(-3deg);
  -ms-transform: rotate(-3deg);
  transform: rotate(-3deg);
}
.effectpostit:after
{
  -webkit-transform: rotate(3deg);
  -moz-transform: rotate(3deg);
  -o-transform: rotate(3deg);
  -ms-transform: rotate(3deg);
  transform: rotate(3deg);
  right: 10px;
  left: auto;
}



/* ============================================================================== */
/* Form confirmation (When Ajax JQuery is used) and Dialog popups                 */
/* ============================================================================== */

.ui-dialog-titlebar {
}
.ui-dialog-content {
	font-size: <?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize; ?> !important;
}
.ui-dialog.ui-corner-all.ui-widget.ui-widget-content.ui-front.ui-draggable {
	z-index: 1005 !important;		/* Default 101 with ui-jquery, top menu have a z-index of 1000 */
}
.ui-menu.ui-widget.ui-widget-content.ui-autocomplete.ui-front {
	z-index:1006 !important; 		/* To always be over the dialog box */
}

.ui-dialog.ui-widget.ui-widget-content {
	border: 1px solid #e0e0e0;
	border-radius: 6px;
}

div#dialogforpopup {
	background-color: #f8f8f8 !important;
}


/* ============================================================================== */
/* For content of image preview                                                   */
/* ============================================================================== */

/*
.ui-dialog-content.ui-widget-content > object {
	 max-height: none;
	 width: auto; margin-left: auto; margin-right: auto; display: block;
}
*/


/* ============================================================================== */
/* Formulaire confirmation (When HTML is used)                                    */
/* ============================================================================== */

table.valid {
	/* border-top: solid 1px #E6E6E6; */
	border-<?php print $left; ?>: solid 5px #f2cf87;
	/* border-<?php print $right; ?>: solid 1px #444444;
	border-bottom: solid 1px #555555; */
	padding-top: 8px;
	padding-left: 10px;
	padding-right: 4px;
	padding-bottom: 4px;
	margin: 0px 0px;
	background: #fcf8e3;
}

.validtitre {
	font-weight: bold;
}


/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

/* For tooltip using dialog */
.ui-dialog.highlight.ui-widget.ui-widget-content.ui-front {
	z-index: 3000;
}

div.ui-tooltip {
	max-width: <?php print dol_size(700, 'width'); ?>px !important;
}

div.ui-tooltip.mytooltip {
	border-top: solid 1px #BBBBBB;
	border-<?php print $left; ?>: solid 1px #BBBBBB;
	border-<?php print $right; ?>: solid 1px #444444;
	border-bottom: solid 1px #444444;
	padding: 10px 20px;
	border-radius: 0;
	box-shadow: 0 0 4px grey;
	margin: 2px;
	font-stretch: condensed;
	/*background: var(--tooltipbgcolor) !important;
	color : var(--tooltipfontcolor);*/
	line-height: 1.6em;
	min-width: 550px;
}
@media only screen and (max-width: 768px)
{
	div.ui-tooltip.mytooltip {
		max-width: 400px;
	}
}
@media only screen and (max-width: 480px)
{
	div.ui-tooltip.mytooltip {
		max-width: 300px;
	}
}
@media only screen and (max-width: 320px)
{
	div.ui-tooltip.mytooltip {
		max-width: 230px;
	}
}


/* ============================================================================== */
/* Calendar date picker                                                                */
/* ============================================================================== */

.ui-datepicker-calendar .ui-state-default, .ui-datepicker-calendar .ui-widget-content .ui-state-default,
.ui-datepicker-calendar .ui-widget-header .ui-state-default, .ui-datepicker-calendar .ui-button,
html .ui-datepicker-calendar .ui-button.ui-state-disabled:hover, html .ui-button.ui-state-disabled:active
{
	border: unset;
}

div#ui-datepicker-div {
	width: 300px;
	box-shadow: 2px 5px 15px #aaa;
	border: unset;
	padding-left: 5px;
	padding-right: 5px;
	padding-top: 5px;
}
.ui-datepicker .ui-datepicker table {
	font-size: unset;
}
.ui-datepicker .ui-widget-header {
	border: unset;
	background: unset;
}

img.datecallink { padding-left: 2px !important; padding-right: 2px !important; }

select.ui-datepicker-year {
	margin-left: 2px !important;
}
.ui-datepicker-trigger {
	vertical-align: middle;
	cursor: pointer;
	padding-left: 2px;
	padding-right: 2px;
}

.bodyline {
	-webkit-border-radius: 4px;
	border-radius: 4px;
	border: 1px #E4ECEC outset;
	padding: 0px;
	margin-bottom: 5px;
}
table.dp {
	width: 180px;
	background-color: #FFFFFF;
	/*border-top: solid 2px #f4f4f4;
	border-<?php print $left; ?>: solid 2px #f4f4f4;
	border-<?php print $right; ?>: solid 1px #222222;
	border-bottom: solid 1px #222222; */
	padding: 0px;
	border-spacing: 0px;
	border-collapse: collapse;
}
.dp td, .tpHour td, .tpMinute td{padding:2px; font-size:10px;}
/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color: #888;
	color:white;
	font-size:11px;
	cursor:auto;
}
/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color: #888;
	color:#FFFFFF;
	font-weight:bold;
	cursor:pointer;
}
.dpButtons:Active,.tpButtons:Active{border: 1px outset black;}
.dpDayNames td,.dpExplanation {background-color:#D9DBE1; font-weight:bold; text-align:center; font-size:11px;}
.dpExplanation{ font-weight:normal; font-size:11px;}
.dpWeek td{text-align:center}

.dpToday,.dpReg,.dpSelected{
	cursor:pointer;
}
.dpToday{font-weight:bold; color:black; background-color:#f4f4f4;}
.dpReg:Hover,.dpToday:Hover{background-color:black;color:white}

/* Jour courant */
.dpSelected{background-color:#0B63A2;color:white;font-weight:bold; }

.tpHour{border-top:1px solid #f4f4f4; border-right:1px solid #f4f4f4;}
.tpHour td {border-left:1px solid #f4f4f4; border-bottom:1px solid #f4f4f4; cursor:pointer;}
.tpHour td:Hover {background-color:black;color:white;}

.tpMinute {margin-top:5px;}
.tpMinute td:Hover {background-color:black; color:white; }
.tpMinute td {background-color:#D9DBE1; text-align:center; cursor:pointer;}

/* Bouton X fermer */
.dpInvisibleButtons
{
	border-style:none;
	background-color:transparent;
	padding:0px;
	font-size: 0.85em;
	border-width:0px;
	color: #eee;
	vertical-align:middle;
	cursor: pointer;
}
.datenowlink {
	color: rgb(<?php print $colortextlink; ?>);
	font-size: 0.8em;
	opacity: 0.7;
}

.categtextwhite, .treeview .categtextwhite.hover {
	color: #fff !important;
}
.categtextblack {
	color: #000 !important;
}


/* ============================================================================== */
/*  Show/Hide                                                                     */
/* ============================================================================== */

div.visible {
	display: block;
}

div.hidden, div.hiddenforpopup, header.hidden, tr.hidden, td.hidden,
img.hidden, span.hidden, br.hidden, div.showifmore {
	display: none;
}

.unvisible {
	visibility: hidden;
}

tr.visible {
	display: block;
}


/* ============================================================================== */
/*  Module website                                                                */
/* ============================================================================== */


.previewnotyetavailable {
	opacity: 0.5;
}

.websiteformtoolbar {
	position: sticky;
	top: <?php echo empty($dol_hide_topmenu) ? ($disableimages ? '36px' : '50px') : '0'; ?>;
	z-index: 1002;	/* Dolibarr menu is 1001, Website menu is 1002 */
}

.exampleapachesetup {
	overflow-y: auto;
	max-height: 100px;
	font-size: 0.8em;
	border: 1px solid #aaa;
}

span[phptag] {
	background: #ddd; border: 1px solid #ccc; border-radius: 4px;
}

.nobordertransp {
	border: 0px;
	background-color: transparent;
	background-image: none;
	color: #000 !important;
	text-shadow: none;
}
.bordertransp:not(.nobordertransp) {
	background-color: transparent;
	background-image: none;
	border: 1px solid #aaa;
	font-weight: normal;
	color: #444 !important;
}
.websitebar .button.bordertransp {
	color: unset;
	text-decoration: unset !important;
	/* margin: 0px 4px 0px 4px  !important */
}

.websitebar {
	border-bottom: 1px solid #ccc;
	background: #eee;
	display: inline-block;
}
.centpercent.websitebar {
	width: calc(100% - 10px);
	padding: 5px 5px 5px 5px;
	font-size: 0.94em;
}
.websitebar .buttonDelete, .websitebar .button {
	text-shadow: none;
}
.websitebar .button, .websitebar .buttonDelete
{
	padding: 2px 4px 2px 4px !important;
	margin: 2px 4px 2px 4px  !important;
	line-height: normal;
}
.websitebar input.button.bordertransp, .websitebar input.buttonDelete.bordertransp {
	color: #444 !important;
	text-shadow: none;
}
.websiteselection {
	/* display: inline-block; */
	padding-<?php echo $right; ?>: 10px;
	vertical-align: middle;
}
.websitetools {
	float: right;
}
.websiteselection, .websitetools {
	/* margin-top: 3px;
	padding-top: 3px;
	padding-bottom: 3px; */
}
.websiteinputurl {
	display: inline-block;
	vertical-align: top;
	line-height: 26px;
}
.websiteiframenoborder {
	border: 0px;
}
span.websitebuttonsitepreview, a.websitebuttonsitepreview {
	vertical-align: middle;
}
span.websitebuttonsitepreview img, a.websitebuttonsitepreview img {
	width: 26px;
	display: inline-block;
}
span.websitebuttonsitepreviewdisabled img, a.websitebuttonsitepreviewdisabled img {
	opacity: 0.2;
}
.websiteiframenoborder {
	border: 0px;
}
.websitehelp {
	vertical-align: middle;
	float: right;
	padding-top: 5px;
}
.websiteselectionsection {
	border-left: 1px solid #bbb;
	border-right: 1px solid #bbb;
	margin-left: 0px;
	padding-left: 8px;
	margin-right: 5px;
}
.websitebar input#previewpageurl {
	line-height: 1em;
}

#divbodywebsite section p {
	margin: unset;
}


/* ============================================================================== */
/*  Module agenda                                                                 */
/* ============================================================================== */

.dayevent .tagtr:first-of-type {
	height: 24px;
}
.agendacell { height: 60px; }
table.cal_month    { border-spacing: 0px; }
table.cal_month td:first-child  { border-left: 0px; }
table.cal_month td:last-child   { border-right: 0px; }
.cal_current_month { border-top: 0; border-left: solid 1px #E0E0E0; border-right: 0; border-bottom: solid 1px #E0E0E0; }
.cal_current_month_peruserleft { border-top: 0; border-left: solid 2px #6C7C7B; border-right: 0; border-bottom: solid 1px #E0E0E0; }
.cal_current_month_oneday { border-right: solid 1px #E0E0E0; }
.cal_other_month   { border-top: 0; border-left: solid 1px #C0C0C0; border-right: 0; border-bottom: solid 1px #C0C0C0; }
.cal_other_month_peruserleft { border-top: 0; border-left: solid 2px #6C7C7B !important; border-right: 0; }
.cal_current_month_right { border-right: solid 1px #E0E0E0; }
.cal_other_month_right   { border-right: solid 1px #C0C0C0; }
.cal_other_month   { /* opacity: 0.6; */ background: #FAFAFA; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { /* opacity: 0.6; */ background: #EEEEEE; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border-left: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month_peruserleft { background: #FFFFFF; border-left: solid 2px #6C7C7B; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FDFDF0; border-left: solid 1px #E0E0E0; border-bottom: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today_peruser { background: #FDFDF0; border-right: solid 1px #E0E0E0; border-bottom: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today_peruser_peruserleft { background: #FDFDF0; border-left: solid 2px #6C7C7B; border-right: solid 1px #E0E0E0; border-bottom: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past          { }
.cal_peruser       { padding: 0px; }
.cal_impair        { background: #F8F8F8; }
.cal_today_peruser_impair { background: #F8F8F0; }
.peruser_busy      { background: #CC8888; }
.peruser_notbusy   { background: #EEDDDD; opacity: 0.5; }
div.event { margin-top: 4px; margin-bottom: 4px; margin-left: 2px; margin-right: 2px; border-radius: 4px; box-shadow: 2px 2px 5px rgba(100, 100, 100, 0.2); }
table.cal_event    { border: none; border-collapse: collapse; margin-bottom: 1px; -webkit-border-radius: 3px; border-radius: 3px; min-height: 20px;	}
table.cal_event td { border: none; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event td.cal_event { padding: 4px 4px !important; padding-bottom: 2px !important; padding-top: 2px !important; }
table.cal_event td.cal_event_right { padding: 4px 4px !important; }
.cal_event              { font-size: 1em; }
.cal_event a:link       { color: #111111; font-weight: normal !important; }
.cal_event a:visited    { color: #111111; font-weight: normal !important; }
.cal_event a:active     { color: #111111; font-weight: normal !important; }
.cal_event_busy a:hover { color: #111111; font-weight: normal !important; color:rgba(255,255,255,.75); }
.cal_event_busy      { }
.cal_peruserviewname { max-width: 140px; height: 22px; }
a.dayevent-aday {
	margin-left: 8px;
}
td.small.cal_event {
	font-size: 0.9em;
}

.calendarviewcontainertr { height: 100px; }

.topmenuimage {
	background-size: 24px auto;
}

td.cal_other_month {
	opacity: 0.7;
}

.cal_available { background: #0060d450; }
.cal_chosen { background: #0060d4; }

/* ============================================================================== */
/*  Ajax - Combo list for autocompletion                                          */
/* ============================================================================== */

.ui-widget-content {
	border: solid 1px rgba(0,0,0,.3);
	background: var(--colorbackbody) !important;
	color: var(--colortext) !important;
}

.ui-autocomplete-loading { background: white url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/working.gif', 1) ?>) right center no-repeat; }
.ui-autocomplete {
		   position:absolute;
		   width:auto;
		   font-size: 1.0em;
		   background-color:white;
		   border:1px solid #888;
		   margin:0px;
/*	       padding:0px; This make combo crazy */
		 }
.ui-autocomplete ul {
		   list-style-type:none;
		   margin:0px;
		   padding:0px;
		 }
.ui-autocomplete ul li.selected { background-color: #D3E5EC;}
.ui-autocomplete ul li {
		   list-style-type:none;
		   display:block;
		   margin:0;
		   padding:2px;
		   height:18px;
		   cursor:pointer;
		 }


/* ============================================================================== */
/* Gantt
/* ============================================================================== */

td.gtaskname {
	overflow: hidden;
	text-overflow: ellipsis;
}


/* ============================================================================== */
/*  jQuery - jeditable for inline edit                                            */
/* ============================================================================== */

.editkey_textarea, .editkey_ckeditor, .editkey_string, .editkey_email, .editkey_numeric, .editkey_select, .editkey_autocomplete {
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/edit.png', 1) ?>) right top no-repeat;
	cursor: pointer;
	margin-right: 3px;
	margin-top: 3px;
}

.editkey_datepicker {
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/calendar.png', 1) ?>) right center no-repeat;
	cursor: pointer;
	margin-right: 3px;
	margin-top: 3px;
}

.editval_textarea.active:hover, .editval_ckeditor.active:hover, .editval_string.active:hover, .editval_email.active:hover, .editval_numeric.active:hover, .editval_select.active:hover, .editval_autocomplete.active:hover, .editval_datepicker.active:hover {
	background: white;
	cursor: pointer;
}

.viewval_textarea.active:hover, .viewval_ckeditor.active:hover, .viewval_string.active:hover, .viewval_email.active:hover, .viewval_numeric.active:hover, .viewval_select.active:hover, .viewval_autocomplete.active:hover, .viewval_datepicker.active:hover {
	background: white;
	cursor: pointer;
}

.viewval_hover {
	background: white;
}


/* ============================================================================== */
/* Admin Menu                                                                     */
/* ============================================================================== */

/* CSS for treeview */
.treeview ul { background-color: transparent !important; margin-top: 0 !important; /* margin-bottom: 4px !important; padding-top: 2px !important; */ }
.treeview li { background-color: transparent !important; padding: 0 0 0 20px !important; min-height: 30px; }
.treeview .hitarea { width: 20px !important; margin-left: -20px !important; margin-top: 3px; }
.treeview li table { min-height: 30px; }
.treeview .hover { color: var(--colortextlink) !important; text-decoration: underline !important; }



/* ============================================================================== */
/*  Show Excel tabs                                                               */
/* ============================================================================== */

.table_data
{
	border-style:ridge;
	border:1px solid;
}
.tab_base
{
	background:#C5D0DD;
	font-weight:bold;
	border-style:ridge;
	border: 1px solid;
	cursor:pointer;
}
.table_sub_heading
{
	background:#CCCCCC;
	font-weight:bold;
	border-style:ridge;
	border: 1px solid;
}
.table_body
{
	background:#F0F0F0;
	font-weight:normal;
	font-family:sans-serif;
	border-style:ridge;
	border: 1px solid;
	border-spacing: 0px;
	border-collapse: collapse;
}
.tab_loaded
{
	background:#222222;
	color:white;
	font-weight:bold;
	border-style:groove;
	border: 1px solid;
	cursor:pointer;
}


/* ============================================================================== */
/*  CSS for color picker                                                          */
/* ============================================================================== */

div.jPicker table.jPicker {
	padding-bottom: 20px;
	padding-right: 20px;
	padding-left: 20px;
}
.jPicker .Move {
	background: unset !important;
	border: unset !important;
}
.jPicker .Preview div span {
	border: unset !important;
	width: unset !important;
	height: 50% !important;
}
table.jPicker {
	border-radius: 5px;
	border: 1px solid #bbb !important;
	background-color: #f4f4f4 !important;
	box-shadow: 0px 0px 10px #ccc;
}
.jPicker .Grid {
	background-image: unset !important;
}
.jPicker .Grid span.QuickColor {
	border: unset !important;
}
.jPicker td.Radio {
	min-width: 34px;
}
.jPicker td.Text {
	white-space: nowrap;
}

A.color, A.color:active, A.color:visited {
 position : relative;
 display : block;
 text-decoration : none;
 width : 10px;
 height : 10px;
 line-height : 10px;
 margin : 0px;
 padding : 0px;
 border : 1px inset white;
}
A.color:hover {
 border : 1px outset white;
}
A.none, A.none:active, A.none:visited, A.none:hover {
 position : relative;
 display : block;
 text-decoration : none;
 width : 10px;
 height : 10px;
 line-height : 10px;
 margin : 0px;
 padding : 0px;
 cursor : default;
 border : 1px solid #b3c5cc;
}
.tblColor {
 display : none;
}
.tdColor {
 padding : 1px;
}
.tblContainer {
 background-color : #b3c5cc;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : #b3c5cc;
 border : 2px outset;
}
.tdContainer {
 padding : 5px;
}
.tdDisplay {
 width : 50%;
 height : 20px;
 line-height : 20px;
 border : 1px outset white;
}
.tdDisplayTxt {
 width : 50%;
 height : 24px;
 line-height : 12px;
 font-family : <?php print $fontlist ?>;
 font-size : 8pt;
 color : black;
 text-align : center;
}
.btnColor {
 width : 100%;
 font-family : <?php print $fontlist ?>;
 font-size : 10pt;
 padding : 0px;
 margin : 0px;
}
.btnPalette {
 width : 100%;
 font-family : <?php print $fontlist ?>;
 font-size : 8pt;
 padding : 0px;
 margin : 0px;
}
.colorselector {
	border: solid 1px #ddd !important;
}


/* Style to overwrites JQuery styles */
.ui-state-highlight, .ui-widget-content .ui-state-highlight, .ui-widget-header .ui-state-highlight {
	/* border: 1px solid #888; */
	background: rgb(<?php echo $colorbacktitle1; ?>);
	color: unset;
	font-weight: bold;
}
.ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active, a.ui-button:active, .ui-button:active, .ui-button.ui-state-active:hover {
	background: #007fff !important;
	color: #ffffff !important;
}

.ui-menu .ui-menu-item a {
	text-decoration:none;
	display:block;
	padding:.2em .4em;
	line-height:1.5;
	font-weight: normal;
	font-family:<?php echo $fontlist; ?>;
	font-size:1em;
}
.ui-widget {
	font-family:<?php echo $fontlist; ?>;
}
.ui-button { margin-left: -2px; <?php print(preg_match('/chrome/', $conf->browser->name) ? 'padding-top: 1px;' : ''); ?> }
.ui-button-icon-only .ui-button-text { height: 8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding: 2px 0px 6px 0px; }
.ui-button-text
{
	line-height: 1em !important;
}
.ui-autocomplete-input { margin: 0; padding: 4px; }


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */

body.cke_show_borders {
	margin: 5px !important;
}

.cke_dialog {
	border: 1px #bbb solid ! important;
}
/*.cke_editor table, .cke_editor tr, .cke_editor td
{
	border: 0px solid #FF0000 !important;
}
span.cke_skin_kama { padding: 0 !important; }*/
.cke_wrapper { padding: 4px !important; }
a.cke_dialog_ui_button
{
	font-family: <?php print $fontlist ?> !important;
	background-image: url(<?php echo $img_button ?>) !important;
	background-position: bottom !important;
	border: 1px solid #C0C0C0 !important;
	-webkit-border-radius:0px 2px 0px 2px !important;
	border-radius:0px 2px 0px 2px !important;
	-webkit-box-shadow: 3px 3px 4px #f4f4f4 !important;
	box-shadow: 3px 3px 4px #f4f4f4 !important;
}
.cke_dialog_ui_hbox_last
{
	vertical-align: bottom !important;
}
.cke_dialog_ui_hbox_last
{
	vertical-align: bottom !important;
}
.cke_combo_text {
	width: 40px !important;
}
/*
.cke_editable
{
	line-height: 1.4 !important;
	margin: 6px !important;
}
*/
a.cke_dialog_ui_button_ok span {
	text-shadow: none !important;
	color: #333 !important;
}
a.cke_button, a.cke_combo_button {
	height: 18px !important;
}
div.cke_notifications_area .cke_notification_warning {
	visibility: hidden;
}


/* ============================================================================== */
/*  ACE editor                                                                    */
/* ============================================================================== */
.ace_editor {
	border: 1px solid #ddd;
	margin: 0;
}
.aceeditorstatusbar {
		margin: 0;
		padding: 0;
		padding-<?php echo $left; ?>: 10px;
		left: 0;
		right: 0;
		bottom: 0;
		color: #666;
		height: 28px;
		line-height: 2.2em;
}
.ace_status-indicator {
		color: gray;
		position: relative;
		right: 0;
		border-left: 1px solid;
}
pre#editfilecontentaceeditorid {
	margin-top: 5px;
}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {
	height: 72px !important;
}


/* ============================================================================== */
/*  Custom reports                                                                */
/* ============================================================================== */

.customreportsoutput, .customreportsoutputnotdata {
	padding-top: 20px;
}
.customreportsoutputnotdata {
	text-align: center;
}


/* ============================================================================== */
/*  Holiday                                                                       */
/* ============================================================================== */

#types .btn {
	cursor: pointer;
}

#types .btn-primary {
	font-weight: bold;
}

#types form {
	padding: 20px;
}

#types label {
	display:inline-block;
	width:100px;
	margin-right: 20px;
	padding: 4px;
	text-align: right;
	vertical-align: top;
}

#types input.text, #types textarea {
	width: 400px;
}

#types textarea {
	height: 100px;
}


/* ============================================================================== */
/*  Comments                                                                   	  */
/* ============================================================================== */

#comment div {
	box-sizing:border-box;
}
#comment .comment {
	border-radius:7px;
	margin-bottom:10px;
	overflow:hidden;
}
#comment .comment-table {
	display:table;
	height:100%;
}
#comment .comment-cell {
	display:table-cell;
}
#comment .comment-info {
	font-size:0.8em;
	border-right:1px solid #dedede;
	margin-right:10px;
	width:160px;
	text-align:center;
	background:rgba(255,255,255,0.5);
	vertical-align:middle;
	padding:10px 2px;
}
#comment .comment-info a {
	color:inherit;
}
#comment .comment-right {
	vertical-align:top;
}
#comment .comment-description {
	padding:10px;
	vertical-align:top;
}
#comment .comment-delete {
	width: 100px;
	text-align:center;
	vertical-align:middle;
}
#comment .comment-delete:hover {
	background:rgba(250,20,20,0.8);
}
#comment .comment-edit {
	width: 100px;
	text-align:center;
	vertical-align:middle;
}
#comment .comment-edit:hover {
	background:rgba(0,184,148,0.8);
}
#comment textarea {
	width: 100%;
}


/* ============================================================================== */
/*  JSGantt                                                                       */
/* ============================================================================== */

div.scroll2 {
	width: <?php print isset($_SESSION['dol_screenwidth']) ? max((int) $_SESSION['dol_screenwidth'] - 830, 450) : '450'; ?>px !important;
}

div#GanttChartDIVglisthead, div#GanttChartDIVgcharthead {
	line-height: 2;
}

.gtaskname div, .gtaskname, .gstartdate div, .gstartdate, .genddate div, .genddate {
	font-size: unset !important;
}

div.gantt, .gtaskheading, .gmajorheading, .gminorheading, .gminorheadingwkend {
	font-size: unset !important;
	font-weight: normal !important;
	color: #000 !important;
}
div.gTaskInfo {
	background: #f0f0f0 !important;
}
.gtaskblue {
	background: rgb(108,152,185) !important;
}
.gtaskgreen {
	background: rgb(160,173,58) !important;
}
td.gtaskname {
	overflow: hidden;
	text-overflow: ellipsis;
}
td.gminorheadingwkend {
	color: #888 !important;
}
td.gminorheading {
	color: #666 !important;
}
.glistlbl, .glistgrid {
	width: 582px !important;
}
.gtaskname div, .gtaskname {
	min-width: 250px !important;
	max-width: 250px !important;
	width: 250px !important;
}
.gpccomplete div, .gpccomplete {
	min-width: 40px !important;
	max-width: 40px !important;
	width: 40px !important;
}
td.gtaskheading.gstartdate, td.gtaskheading.genddate {
	white-space: break-spaces;
}
.gtasktableh tr:nth-child(2) td:nth-child(2), .gtasktableh tr:nth-child(2) td:nth-child(3), .gtasktableh tr:nth-child(2) td:nth-child(4), .gtasktableh tr:nth-child(2) td:nth-child(5), .gtasktableh tr:nth-child(2) td:nth-child(6), .gtasktableh tr:nth-child(2) td:nth-child(7) {
	color: transparent !important;
	border-left: none;
	border-right: none;
	border-top: none;
}


/* ============================================================================== */
/*  jFileTree                                                                     */
/* ============================================================================== */

.ecmfiletree {
	width: 99%;
	height: 99%;
	padding-left: 2px;
	font-weight: normal;
}

.fileview {
	width: 99%;
	height: 99%;
	background: #FFF;
	padding-left: 2px;
	padding-top: 4px;
	font-weight: normal;
}

div.filedirelem {
	position: relative;
	display: block;
	text-decoration: none;
}

ul.filedirelem {
	padding: 2px;
	margin: 0 5px 5px 5px;
}
ul.filedirelem li {
	list-style: none;
	padding: 2px;
	margin: 0 10px 20px 10px;
	width: 160px;
	height: 120px;
	text-align: center;
	display: block;
	float: <?php print $left; ?>;
	border: solid 1px #f4f4f4;
}

ul.ecmjqft {
	line-height: 16px;
	padding: 0px;
	margin: 0px;
	font-weight: normal;
}

ul.ecmjqft li {
	list-style: none;
	padding: 0px;
	padding-left: 20px;
	margin: 0px;
	display: block;
}

ul.ecmjqft a {
	line-height: 24px;
	vertical-align: middle;
	color: #333;
	padding: 0px 0px;
	font-weight:normal;
	display: inline-block !important;
}
ul.ecmjqft > a {
	width: calc(100% - 100px);
	overflow: hidden;
	white-space: break-spaces;
	word-break: break-all;
}
ul.ecmjqft a:active {
	font-weight: bold !important;
}
ul.ecmjqft a:hover {
	text-decoration: underline;
}

div.ecmjqft {
	vertical-align: middle;
	display: inline-block !important;
	text-align: right;
	float: right;
	right:4px;
	clear: both;
}
div#ecm-layout-west {
	width: 380px;
	vertical-align: top;
}
div#ecm-layout-center {
	width: calc(100% - 405px);
	vertical-align: top;
	float: right;
}

.ecmjqft LI.directory { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/folder2.png', 1); ?>) left top no-repeat; }
.ecmjqft LI.expanded { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/folder2-expanded.png', 1); ?>) left top no-repeat; }
.ecmjqft LI.wait { font-weight:normal; background: url(<?php echo dol_buildpath('/theme/'.$theme.'/img/working.gif', 1); ?>) left top no-repeat; }


/* ============================================================================== */
/*  jNotify                                                                       */
/* ============================================================================== */

.jnotify-container {
	position: fixed !important;
<?php if (getDolGlobalString('MAIN_JQUERY_JNOTIFY_BOTTOM')) { ?>
	top: auto !important;
	bottom: 4px !important;
<?php } ?>
	text-align: center;
	min-width: <?php echo $dol_optimize_smallscreen ? '200' : '480'; ?>px;
	width: auto;
	max-width: 1024px;
	padding-left: 10px !important;
	padding-right: 10px !important;
	padding-top: 10px !important;
	word-wrap: break-word;
}
.jnotify-container .jnotify-notification .jnotify-message {
	font-weight: normal;
	text-align: start;
	word-break: break-word;
}
.jnotify-container .jnotify-notification-warning .jnotify-close, .jnotify-container .jnotify-notification-warning .jnotify-message {
	color: #a28918 !important;
}

/* use or not ? */
div.jnotify-background {
	opacity : 0.95 !important;
	-webkit-box-shadow: 2px 2px 4px #8888 !important;
	box-shadow: 2px 2px 4px #8888 !important;
}


/* ============================================================================== */
/*  blockUI                                                                      */
/* ============================================================================== */

/*div.growlUI { background: url(check48.png) no-repeat 10px 10px }*/
div.dolEventValid h1, div.dolEventValid h2 {
	color: #567b1b;
	background-color: #e3f0db;
	padding: 5px 5px 5px 5px;
	text-align: left;
}
div.dolEventError h1, div.dolEventError h2 {
	color: #a72947;
	background-color: #d79eac;
	padding: 5px 5px 5px 5px;
	text-align: left;
}

/* ============================================================================== */
/*  Maps                                                                          */
/* ============================================================================== */

.divmap, #google-visualization-geomap-embed-0, #google-visualization-geomap-embed-1, #google-visualization-geomap-embed-2 {
}


/* ============================================================================== */
/*  Datatable                                                                     */
/* ============================================================================== */

table.dataTable tr.odd td.sorting_1, table.dataTable tr.even td.sorting_1 {
  background: none !important;
}
.sorting_asc  { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_asc.png', 1); ?>') no-repeat center right !important; }
.sorting_desc { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_desc.png', 1); ?>') no-repeat center right !important; }
.sorting_asc_disabled  { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_asc_disabled.png', 1); ?>') no-repeat center right !important; }
.sorting_desc_disabled { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_desc_disabled.png', 1); ?>') no-repeat center right !important; }
.dataTables_paginate {
	margin-top: 8px;
}
.paginate_button_disabled {
  opacity: 1 !important;
  color: #888 !important;
  cursor: default !important;
}
.paginate_disabled_previous:hover, .paginate_enabled_previous:hover, .paginate_disabled_next:hover, .paginate_enabled_next:hover
{
	font-weight: normal;
}
.paginate_enabled_previous:hover, .paginate_enabled_next:hover
{
	text-decoration: underline !important;
}
.paginate_active
{
	text-decoration: underline !important;
}
.paginate_button
{
	font-weight: normal !important;
	text-decoration: none !important;
}
.paging_full_numbers {
	height: inherit !important;
}
.paging_full_numbers a.paginate_active:hover, .paging_full_numbers a.paginate_button:hover {
	background-color: #DDD !important;
}
.paging_full_numbers, .paging_full_numbers a.paginate_active, .paging_full_numbers a.paginate_button {
	background-color: #FFF !important;
	border-radius: inherit !important;
}
.paging_full_numbers a.paginate_button_disabled:hover, .paging_full_numbers a.disabled:hover {
	background-color: #FFF !important;
}
.paginate_button, .paginate_active {
  border: 1px solid #ddd !important;
  padding: 6px 12px !important;
  margin-left: -1px !important;
  line-height: 1.42857143 !important;
  margin: 0 0 !important;
}

/* For jquery plugin combobox */
/* Disable this. It breaks wrapping of boxes
.ui-corner-all { white-space: nowrap; } */

.ui-state-disabled, .ui-widget-content .ui-state-disabled, .ui-widget-header .ui-state-disabled, .paginate_button_disabled {
	opacity: .35;
	background-image: none;
}

div.dataTables_length {
	float: right !important;
	padding-left: 8px;
}
div.dataTables_length select {
	background: #fff;
}
.dataTables_wrapper .dataTables_paginate {
	padding-top: 0px !important;
}


/* ============================================================================== */
/*  Select2                                                                       */
/* ============================================================================== */

span#select2-taskid-container[title^='--'] {
	opacity: 0.3;
}

input.select2-input {
	border-bottom: none ! important;
}
.select2-choice {
	border: none;
	border-bottom: 1px solid #ccc !important;
}
.select2-results .select2-highlighted.optionblue {
	color: #FFF !important;
}

.select2-container .select2-selection--multiple {
	min-height: 28px !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
	border: 1px solid #e4e4e4;
}

.blockvmenusearch .select2-container--default .select2-selection--single,
.blockvmenubookmarks .select2-container--default .select2-selection--single
{
	background-color: unset;
}
#blockvmenusearch .select2-container--default .select2-selection--single .select2-selection__placeholder {
	color: var(--colortextbackvmenu);
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
	color: unset;
}
.select2-container .select2-choice {
	border-bottom: 1px solid #ccc;
}
.select2-container .select2-choice > .select2-chosen {
	margin-right: 23px;
}
.select2-container .select2-choice .select2-arrow {
	border-radius: 0;
}
.select2-container-multi .select2-choices {
	background-image: none;
}
.select2-container .select2-choice {
	color: #000;
	border-radius: 0;
}
.selectoptiondisabledwhite {
	background: #FFFFFF !important;
}

.select2-arrow {
	border: none;
	border-left: none !important;
	background: none !important;
}
.select2-choice
{
	border-top: none !important;
	border-left: none !important;
	border-right: none !important;
	border-bottom: 1px solid #ccc;
}
.select2-drop.select2-drop-above {
	box-shadow: none !important;
}
.select2-container--open .select2-dropdown--above {
	border-bottom: solid 1px rgba(0,0,0,.2);
}
.select2-drop.select2-drop-above.select2-drop-active {
	border-top: 1px solid #ccc;
	border-bottom: 1px solid #ccc;
}
.select2-container--default .select2-selection--single
{
	outline: none;
	border-top: none;
	border-left: none;
	border-right: none;
	border-bottom: solid 1px rgba(0,0,0,.2);
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
	border-radius: 0 !important;
}
.select2-container--default.select2-container--focus .select2-selection--multiple {
	border-top: none;
	border-left: none;
	border-right: none;
}
.select2-container--default .select2-selection--multiple {
	border-bottom: solid 1px rgba(0,0,0,.2);
	border-top: none;
	border-left: none;
	border-right: none;
	border-radius: 0 !important;
}
.select2-selection--multiple input.select2-search__field {
	border-bottom: none !important;
}
.select2-search__field
{
	outline: none;
	border-top: none !important;
	border-left: none !important;
	border-right: none !important;
	border-bottom: solid 1px rgba(0,0,0,.2) !important;
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
	border-radius: 0 !important;
}
.select2-container-active .select2-choice, .select2-container-active .select2-choices
{
	outline: none;
	border-top: none;
	border-left: none;
	border-bottom: none;
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
}
.select2-dropdown-open {
	background-color: #fff;
}
.select2-dropdown-open .select2-choice, .select2-dropdown-open .select2-choices
{
	outline: none;
	border-top: none;
	border-left: none;
	border-bottom: none;
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
	background-color: #fff;
}
.select2-disabled
{
	color: #888;
}
.select2-drop.select2-drop-above.select2-drop-active, .select2-drop {
	border-radius: 0;
}
.select2-drop.select2-drop-above {
	border-radius:  0;
}
.select2-dropdown-open.select2-drop-above .select2-choice, .select2-dropdown-open.select2-drop-above .select2-choices {
	background-image: none;
	border-radius: 0 !important;
}
div.select2-drop-above
{
	background: #fff;
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
}
.select2-drop-active
{
	border: 1px solid #ccc;
	padding-top: 4px;
}
.select2-search input {
	border: none;
}
a span.select2-chosen
{
	font-weight: normal !important;
}
.select2-container .select2-choice {
	background-image: none;
	line-height: 24px;
}
.select2-results .select2-no-results, .select2-results .select2-searching, .select2-results .select2-ajax-error, .select2-results .select2-selection-limit
{
	background: #FFFFFF;
}
.select2-results {
	max-height:	400px;
}
.select2-results__option {
	word-break: break-word;
	text-align: <?php echo $left; ?>;
}
.select2-container.select2-container-disabled .select2-choice, .select2-container-multi.select2-container-disabled .select2-choices {
	background-color: #FFFFFF;
	background-image: none;
	border: none;
	cursor: default;
}
.select2-container-disabled .select2-choice .select2-arrow b {
	opacity: 0.5;
}
.select2-container-multi .select2-choices .select2-search-choice {
  margin-bottom: 3px;
}
.select2-dropdown-open.select2-drop-above .select2-choice, .select2-dropdown-open.select2-drop-above .select2-choices, .select2-container-multi .select2-choices,
.select2-container-multi.select2-container-active .select2-choices
{
	border-bottom: 1px solid #ccc;
	border-right: none;
	border-top: none;
	border-left: 1px solid #ddd;
}
.select2-container--default .select2-results>.select2-results__options{
	max-height: 400px;
}

/* Special case for the select2 add widget */
#addbox .select2-container .select2-choice > .select2-chosen, #actionbookmark .select2-container .select2-choice > .select2-chosen {
	text-align: <?php echo $left; ?>;
	opacity: 0.3;
}
.select2-container--default .select2-selection--single .select2-selection__placeholder {
	color: unset;
	opacity: 0.5;
}
span#select2-boxbookmark-container, span#select2-boxcombo-container {
	text-align: <?php echo $left; ?>;
	opacity: 0.5;
}
.select2-container .select2-selection--single .select2-selection__rendered {
	padding-left: 6px;
}
/* Style used before the select2 js is executed on boxcombo */
#boxbookmark.boxcombo, #boxcombo.boxcombo {
	text-align: left;
	opacity: 0.3;
	border-bottom: solid 1px rgba(0,0,0,.4) !important;
	height: 26px;
	line-height: 24px;
	padding: 0 0 5px 5px;
	vertical-align: top;
}

/* To emulate select 2 style */
.select2-container-multi-dolibarr .select2-choices-dolibarr .select2-search-choice-dolibarr {
  padding: 3px 5px 2px 5px;
  margin: 0 0 2px 3px;
  position: relative;
  line-height: 13px;
  color: #444;
  cursor: default;
  border: 1px solid #ddd;
  border-radius: 3px;
  -webkit-box-shadow: 0 0 2px #fff inset, 0 1px 0 rgba(0, 0, 0, 0.05);
  box-shadow: 0 0 2px #fff inset, 0 1px 0 rgba(0, 0, 0, 0.05);
  background-clip: padding-box;
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  background-color: #e4e4e4;
  background-image: -webkit-gradient(linear, 0% 0%, 0% 100%, color-stop(20%, #f4f4f4), color-stop(50%, #f0f0f0), color-stop(52%, #e8e8e8), color-stop(100%, #eee));
  background-image: -webkit-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #eee 100%);
  background-image: -moz-linear-gradient(top, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #eee 100%);
  background-image: linear-gradient(to bottom, #f4f4f4 20%, #f0f0f0 50%, #e8e8e8 52%, #eee 100%);
}
.select2-container-multi-dolibarr .select2-choices-dolibarr .select2-search-choice-dolibarr a {
	font-weight: normal;
}
.select2-container-multi-dolibarr .select2-choices-dolibarr li {
  float: <?php echo $left; ?>;
  list-style: none;
}
.select2-container-multi-dolibarr .select2-choices-dolibarr {
  height: auto !important;
  height: 1%;
  margin: 0;
  padding: 0 5px 0 0;
  position: relative;
  cursor: text;
  overflow: hidden;
}

span.select2.select2-container.select2-container--default {
	text-align: initial;
}

ul.select2-results__options li {
	font-size: 0.95em;
}

select.multiselectononeline {
	padding: 0;
	vertical-align: middle;
	min-height: unset;
	height: 28px !important;
	opacity: 0;
	/* width: 1px !important; */
}

@media only screen and (min-width: 767px)
{
	/* CSS to have the dropdown boxes larger that the input search area */
	.select2-container.select2-container--open:not(.graphtype) .select2-dropdown.ui-dialog {
		min-width: 240px !important;
	}
	.select2-container.select2-container--open:not(.graphtype) .select2-dropdown--below:not(.onrightofpage),
	.select2-container.select2-container--open:not(.graphtype) .select2-dropdown--above:not(.onrightofpage) {
		min-width: 240px !important;
	}
	.onrightofpage span.select2-dropdown.ui-dialog.select2-dropdown--below,
	.onrightofpage span.select2-dropdown.ui-dialog.select2-dropdown--above {
		min-width: 140px !important;
	}

	.select2-container--open .select2-dropdown--below {
		border-top: 1px solid var(--inputbordercolor);
		/* border-top: 1px solid #aaaaaa; */
	}
}

/* must be after the other .select2-container.select2-container--open .select2-dropdown.ui-dialog */
.limit.select2-container.select2-container--open .select2-dropdown.ui-dialog {
	min-width: 100px !important;
}

.parentonrightofpage {
  direction: rtl;
}


/* ============================================================================== */
/*  For categories                                                                */
/* ============================================================================== */

.noborderoncategories {
	border: none !important;
	border-radius: 5px !important;
	box-shadow: none;
	-webkit-box-shadow: none !important;
	box-shadow: none !important;
	margin-bottom: 0 !important;
}
span.noborderoncategories a, li.noborderoncategories a {
	line-height: normal;
}
span.noborderoncategories {
	padding: 3px 5px 3px 5px;
}
.categtextwhite, .treeview .categtextwhite.hover {
	color: #fff !important;
}
.categtextblack {
	color: #000 !important;
}


/* ============================================================================== */
/*  External lib multiselect with checkbox                                        */
/* ============================================================================== */

.multi-select-menu {
	z-index: 10;
}

.multi-select-container {
  display: inline-block;
  position: relative;
}

.multi-select-menu {
  position: absolute;
  left: 0;
  top: 0.8em;
  float: left;
  min-width: 100%;
  background: #fff;
  margin: 1em 0;
  padding: 0.4em 0;
  border: 1px solid #aaa;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  display: none;
}

div.multi-select-menu[role="menu"] {
	min-width: 220px !important;
}

.multi-select-menu input {
  margin-right: 0.3em;
  vertical-align: 0.1em;
}

.multi-select-button {
  display: inline-block;
  max-width: 20em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: middle;
  background-color: #fff;
  cursor: default;

  border: none;
  border-bottom: solid 1px rgba(0,0,0,.2);
  padding: 5px;
  padding-left: 2px;
  height: 17px;
}
.multi-select-button:focus {
  outline: none;
  border-bottom: 1px solid #666;
}

.multi-select-button:after {
  content: "";
  display: inline-block;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 0.5em 0.23em 0em 0.23em;
  border-color: #888 transparent transparent transparent;
  margin-left: 0.4em;
}

.multi-select-container--open .multi-select-menu { display: block; }

.multi-select-container--open .multi-select-button:after {
  border-width: 0 0.4em 0.4em 0.4em;
  border-color: transparent transparent #888 transparent;
}

.multi-select-menuitem {
	clear: both;
	float: left;
	padding-left: 5px
}
label.multi-select-menuitem {
	line-height: 24px;
	text-align: start;
}


/* ============================================================================== */
/*  Native multiselect with checkbox                                              */
/* ============================================================================== */

ul.ulselectedfields {
	z-index: 90;			/* To have the select box appears on first plan even when near buttons are decorated by jmobile */
}
dl.dropdown {
	margin:0px;
	padding:0px;
	margin-left: 2px;
	margin-right: 2px;
	vertical-align: middle;
	display: inline-block;
}
.dropdown dd, .dropdown dt {
	margin:0px;
	padding:0px;
}
.dropdown ul {
	margin: -1px 0 0 0;
	text-align: <?php echo $left; ?>;
}
.dropdown dd {
	position:relative;
}
.dropdown dt a {
	display:block;
	overflow: hidden;
	border:0;
}
.dropdown dt a span, .multiSel span {
	cursor:pointer;
	display:inline-block;
	padding: 0 3px 2px 0;
}
.maxwidthsearch .dropdown dt a span, .multiSel span {
	padding: 0 3px 2px 3px;
}
.dropdown span.value {
	display:none;
}
.dropdown dd ul {
	background-color: #FFF;
	box-shadow: 1px 1px 10px #aaa;
	display:none;
	<?php echo $right; ?>:0px;						/* pop is align on right */
	padding: 0 0 0 0;
	position:absolute;
	top:2px;
	list-style:none;
	max-height: 264px;
	overflow: auto;
	z-index: 1;
}
.dropdown dd ul.selectedfieldsleft {
	<?php echo $right; ?>: auto;
}
.dropdown dd ul li {
	white-space: nowrap;
	font-weight: normal;
	padding: 7px 8px 7px 8px;
	/* color: rgb(<?php print $colortext; ?>); */
	color: #000;
}
.dropdown dd ul li:hover {
	background: #eee;
}
.dropdown dd ul li input[type="checkbox"] {
	margin-<?php echo $right; ?>: 3px;
}
.dropdown dd ul li a, .dropdown dd ul li span {
	padding: 3px;
	display: block;
}
.dropdown dd ul li span {
	color: #888;
}
.dropdown dd ul li a:hover {
	background-color: #eee;
}

dd.dropdowndd ul li {
	text-overflow: ellipsis;
	overflow: hidden;
	white-space: nowrap;
}


/* ============================================================================== */
/* Kanban                                                                         */
/* ============================================================================== */

.info-box-label {
	max-width: 180px;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}


/* ============================================================================== */
/*  Markdown rendering                                                             */
/* ============================================================================== */

.imgmd {
	width: 90%;
}
.moduledesclong h1 {
	padding-top: 10px;
	padding-bottom: 20px;
}


/* ============================================================================== */
/*  JMobile - Android                                                             */
/* ============================================================================== */

.searchpage .tagtr .tagtd {
	padding-top: 2px;
	padding-bottom: 2px;
}
.searchpage .tagtr .tagtd .button {
	background: unset;
	border: unset;
}
.searchpage .searchform input {
	font-size: 1.15em;
}

li.ui-li-divider .ui-link {
	color: #FFF !important;
}
.ui-btn {
	margin: 0.1em 2px
}
a.ui-link, a.ui-link:hover, .ui-btn:hover, span.ui-btn-text:hover, span.ui-btn-inner:hover {
	text-decoration: none !important;
}
.ui-body-c {
	background: #fff;
}

.ui-btn-inner {
	min-width: .4em;
	padding-left: 6px;
	padding-right: 6px;
	font-size: <?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize; ?>;
	/* white-space: normal; */		/* Warning, enable this break the truncate feature */
}
.ui-btn-icon-right .ui-btn-inner {
	padding-right: 30px;
}
.ui-btn-icon-left .ui-btn-inner {
	padding-left: 30px;
}
.ui-select .ui-btn-icon-right .ui-btn-inner {
	padding-right: 30px;
}
.ui-select .ui-btn-icon-left .ui-btn-inner {
	padding-left: 30px;
}
.ui-select .ui-btn-icon-right .ui-icon {
	right: 8px;
}
.ui-btn-icon-left > .ui-btn-inner > .ui-icon, .ui-btn-icon-right > .ui-btn-inner > .ui-icon {
	margin-top: -10px;
}
select {
	/* display: inline-block; */	/* We can't set this. This disable ability to make */
	overflow:hidden;
	white-space: nowrap;			/* Enabling this make behaviour strange when selecting the empty value if this empty value is '' instead of '&nbsp;' */
	text-overflow: ellipsis;
}
.fiche .ui-controlgroup {
	margin: 0px;
	padding-bottom: 0px;
}
div.ui-controlgroup-controls div.tabsElem
{
	margin-top: 2px;
}
div.ui-controlgroup-controls div.tabsElem a
{
	-webkit-box-shadow: 0 -3px 6px rgba(0,0,0,.2);
	box-shadow: 0 -3px 6px rgba(0,0,0,.2);
}
div.ui-controlgroup-controls div.tabsElem a#active {
	-webkit-box-shadow: 0 -3px 6px rgba(0,0,0,.3);
	box-shadow: 0 -3px 6px rgba(0,0,0,.3);
}

a.tab span.ui-btn-inner
{
	border: none;
	padding: 0;
}

.ui-link {
	color: rgb(<?php print $colortext; ?>);
}
.liste_titre .ui-link {
	color: rgb(<?php print $colortexttitle; ?>) !important;
}

a.ui-link {
	word-wrap: break-word;
}

/* force wrap possible onto field overflow does not works */
.formdoc .ui-btn-inner
{
	white-space: normal;
	overflow: hidden;
	text-overflow: clip; /* "hidden" : do not exists as a text-overflow value (https://developer.mozilla.org/fr/docs/Web/CSS/text-overflow) */
}

/* Warning: setting this may make screen not being refreshed after a combo selection */
/*.ui-body-c {
	background: #fff;
}*/

div.ui-radio, div.ui-checkbox
{
	display: inline-block;
	border-bottom: 0px !important;
}
.ui-checkbox input, .ui-radio input {
	height: auto;
	width: auto;
	margin: 4px;
	position: static;
}
div.ui-checkbox label+input, div.ui-radio label+input {
	position: absolute;
}
.ui-mobile fieldset
{
	padding-bottom: 10px; margin-bottom: 4px; border-bottom: 1px solid #AAAAAA !important;
}

ul.ulmenu {
	border-radius: 0;
	-webkit-border-radius: 0;
}

.ui-field-contain label.ui-input-text {
	vertical-align: middle !important;
}
.ui-mobile fieldset {
	border-bottom: none !important;
}

/* Style for first level menu with jmobile */
.ui-li .ui-btn-inner a.ui-link-inherit, .ui-li-static.ui-li {
	padding: 1em 15px;
	display: block;
}
.ui-btn-up-c {
	font-weight: normal;
}
.ui-focus, .ui-btn:focus {
	-webkit-box-shadow: none;
	box-shadow: none;
}
.ui-bar-b {
	/*border: 1px solid #888;*/
	border: none;
	background: none;
	text-shadow: none;
	color: rgb(<?php print $colortexttitlenotab; ?>) !important;
}
.ui-bar-b, .lilevel0 {
	background-repeat: repeat-x;
	border: none;
	background: none;
	text-shadow: none;
	color: rgb(<?php print $colortexttitlenotab; ?>) !important;
}
.alilevel0 {
	font-weight: normal !important;
}

.ui-li.ui-last-child, .ui-li.ui-field-contain.ui-last-child {
	border-bottom-width: 0px !important;
}
.alilevel0 {
	color: rgb(<?php echo $colortexttitle; ?>) !important;
}
.ulmenu {
	box-shadow: none !important;
	border-bottom: 1px solid #ccc;
}
.ui-btn-icon-right {
	border-right: 1px solid #ccc !important;
}
.ui-body-c {
	border: 1px solid #ccc;
	text-shadow: none;
}
.ui-btn-up-c, .ui-btn-hover-c {
	/* border: 1px solid #ccc; */
	text-shadow: none;
}
.ui-body-c .ui-link, .ui-body-c .ui-link:visited, .ui-body-c .ui-link:hover {
	color: rgb(<?php print $colortextlink; ?>);
}
.ui-btn-up-c .vsmenudisabled {
	color: #<?php echo $colorshadowtitle; ?> !important;
	text-shadow: none !important;
}
/*
.ui-btn-up-c {
	background: transparent;
}
*/
div.tabsElem a.tab {
	background: transparent;
}

/*.ui-controlgroup-horizontal .ui-btn.ui-first-child {
-webkit-border-top-left-radius: 6px;
border-top-left-radius: 6px;
}
.ui-controlgroup-horizontal .ui-btn.ui-last-child {
-webkit-border-top-right-radius: 6px;
border-top-right-radius: 6px;
}*/

.alilevel1 {
	color: rgb(<?php print $colortexttitlenotab; ?>) !important;
}
.lilevel1 {
	border-top: 2px solid #444;
	background: #fff ! important;
}
.lilevel1 div div a {
	font-weight: bold !important;
}
.lilevel2
{
	padding-left: 22px;
	background: #fff ! important;
}
.lilevel3
{
	padding-left: 44px;
	background: #fff ! important;
}
.lilevel4
{
	padding-left: 66px;
	background: #fff ! important;
}
.lilevel5
{
	padding-left: 88px;
	background: #fff ! important;
}



/* ============================================================================== */
/*  POS                                                                           */
/* ============================================================================== */

.menu_choix1 a {
	background: url('<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/money.png', 1) ?>') top left no-repeat;
	background-position-y: 15px;
}

.menu_choix2 a {
	background: url('<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/home.png', 1) ?>') top left no-repeat;
	background-position-y: 15px;
}
.menu_choix1,.menu_choix2 {
	font-size: 1.4em;
	text-align: left;
	border: 1px solid #666;
	margin-right: 20px;
}
.menu_choix1 a, .menu_choix2 a {
	display: block;
	color: #fff;
	text-decoration: none;
	padding-top: 18px;
	padding-left: 54px;
	font-size: 14px;
	height: 40px;
}
.menu_choix1 a:hover,.menu_choix2 a:hover {
	color: #6d3f6d;
}
.menu li.menu_choix1 {
	padding-top: 6px;
	padding-right: 10px;
	padding-bottom: 2px;
}
.menu li.menu_choix2 {
	padding-top: 6px;
	padding-right: 10px;
	padding-bottom: 2px;
}
@media only screen and (max-width: 767px)
{
	.menu_choix1 a, .menu_choix2 a {
		background-size: 36px 36px;
		background-position-y: 6px;
		padding-left: 40px;
	}
	.menu li.menu_choix1, .menu li.menu_choix2 {
		padding-left: 4px;
		padding-right: 0;
	}
	.liste_articles {
		margin-right: 0 !important;
	}
}


/* ============================================================================== */
/*  Public                                                                        */
/* ============================================================================== */

/* The theme for public pages */
.public_body {
	margin: 20px;
}
.public_border {
	border: 1px solid #888;
}
.publicnewmemberform div.tabBarWithBottom {
	border: 1px solid #e8e8e8;
	padding: 30px;
	border-radius: 8px;
	background-color: #f8f8f8;
	/*box-shadow: 2px 2px 10px #ddd;*/
}

.publicnewmemberform #tablesubscribe {
	color: #666;
}


/* ============================================================================== */
/* Ticket module                                                                  */
/* ============================================================================== */

.ticketpublicarea {
	margin-left: 15%;
	margin-right: 15%;
}
.publicnewticketform {
	/* margin-top: 25px !important; */
}
.ticketlargemargin {
	padding-left: 50px;
	padding-right: 50px;
	padding-top: 10px;
}
@media only screen and (max-width: 767px)
{
	.ticketlargemargin {
		padding-left: 5px; padding-right: 5px;
	}
	.ticketpublicarea {
		margin-left: 10px;
		margin-right: 10px;
	}
}

#cd-timeline {
  position: relative;
  padding: 2em 0;
  margin-bottom: 2em;
}
#cd-timeline::before {
  /* this is the vertical line */
  content: '';
  position: absolute;
  top: 0;
  left: 18px;
  height: 100%;
  width: 4px;
  background: #d7e4ed;
}
@media only screen and (min-width: 1170px) {
  #cd-timeline {
	margin-bottom: 3em;
  }
  #cd-timeline::before {
	left: 50%;
	margin-left: -2px;
  }
}

.cd-timeline-block {
  position: relative;
  margin: 2em 0;
}
.cd-timeline-block:after {
  content: "";
  display: table;
  clear: both;
}
.cd-timeline-block:first-child {
  margin-top: 0;
}
.cd-timeline-block:last-child {
  margin-bottom: 0;
}
@media only screen and (min-width: 1170px) {
  .cd-timeline-block {
	margin: 4em 0;
  }
  .cd-timeline-block:first-child {
	margin-top: 0;
  }
  .cd-timeline-block:last-child {
	margin-bottom: 0;
  }
}

.cd-timeline-img {
  position: absolute;
  top: 0;
  left: 0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  box-shadow: 0 0 0 4px white, inset 0 2px 0 rgba(0, 0, 0, 0.08), 0 3px 0 4px rgba(0, 0, 0, 0.05);
  background: #d7e4ed;
}
.cd-timeline-img img {
  display: block;
  width: 24px;
  height: 24px;
  position: relative;
  left: 50%;
  top: 50%;
  margin-left: -12px;
  margin-top: -12px;
}
.cd-timeline-img.cd-picture {
  background: #75ce66;
}
.cd-timeline-img.cd-movie {
  background: #c03b44;
}
.cd-timeline-img.cd-location {
  background: #f0ca45;
}
@media only screen and (min-width: 1170px) {
  .cd-timeline-img {
	width: 60px;
	height: 60px;
	left: 50%;
	margin-left: -30px;
	/* Force Hardware Acceleration in WebKit */
	-webkit-transform: translateZ(0);
	-webkit-backface-visibility: hidden;
  }
  .cssanimations .cd-timeline-img.is-hidden {
	visibility: hidden;
  }
  .cssanimations .cd-timeline-img.bounce-in {
	visibility: visible;
	-webkit-animation: cd-bounce-1 0.6s;
	-moz-animation: cd-bounce-1 0.6s;
	animation: cd-bounce-1 0.6s;
  }
}

@-webkit-keyframes cd-bounce-1 {
  0% {
	opacity: 0;
	-webkit-transform: scale(0.5);
  }

  60% {
	opacity: 1;
	-webkit-transform: scale(1.2);
  }

  100% {
	-webkit-transform: scale(1);
  }
}
@-moz-keyframes cd-bounce-1 {
  0% {
	opacity: 0;
	-moz-transform: scale(0.5);
  }

  60% {
	opacity: 1;
	-moz-transform: scale(1.2);
  }

  100% {
	-moz-transform: scale(1);
  }
}
@keyframes cd-bounce-1 {
  0% {
	opacity: 0;
	-webkit-transform: scale(0.5);
	-moz-transform: scale(0.5);
	-ms-transform: scale(0.5);
	-o-transform: scale(0.5);
	transform: scale(0.5);
  }

  60% {
	opacity: 1;
	-webkit-transform: scale(1.2);
	-moz-transform: scale(1.2);
	-ms-transform: scale(1.2);
	-o-transform: scale(1.2);
	transform: scale(1.2);
  }

  100% {
	-webkit-transform: scale(1);
	-moz-transform: scale(1);
	-ms-transform: scale(1);
	-o-transform: scale(1);
	transform: scale(1);
  }
}
.cd-timeline-content {
  position: relative;
  margin-left: 60px;
  background: white;
  border-radius: 0.25em;
  padding: 1em;
  background-image: -o-linear-gradient(bottom, rgba(0,0,0,0.1) 0%, rgba(230,230,230,0.4) 100%);
  background-image: -moz-linear-gradient(bottom, rgba(0,0,0,0.1) 0%, rgba(230,230,230,0.4) 100%);
  background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.1) 0%, rgba(230,230,230,0.4) 100%);
  background-image: linear-gradient(bottom, rgba(0,0,0,0.1) 0%, rgba(230,230,230,0.4) 100%);
}
.cd-timeline-content:after {
  content: "";
  display: table;
  clear: both;
}
.cd-timeline-content h2 {
  color: #303e49;
}
.cd-timeline-content .cd-date {
  font-size: 13px;
  font-size: 0.8125rem;
}
.cd-timeline-content .cd-date {
  display: inline-block;
}
.cd-timeline-content p {
  margin: 1em 0;
  line-height: 1.6;
}

.cd-timeline-content .cd-date {
  float: left;
  padding: .2em 0;
  opacity: .7;
}
.cd-timeline-content::before {
  content: '';
  position: absolute;
  top: 16px;
  right: 100%;
  height: 0;
  width: 0;
  border: 7px solid transparent;
  border-right: 7px solid white;
}
@media only screen and (min-width: 768px) {
  .cd-timeline-content h2 {
	font-size: 20px;
	font-size: 1.25rem;
  }
  .cd-timeline-content {
	font-size: 16px;
	font-size: 1rem;
  }
  .cd-timeline-content .cd-read-more, .cd-timeline-content .cd-date {
	font-size: 14px;
	font-size: 0.875rem;
  }
}
@media only screen and (min-width: 1170px) {
  .cd-timeline-content {
	margin-left: 0;
	padding: 1.6em;
	width: 43%;
  }
  .cd-timeline-content::before {
	top: 24px;
	left: 100%;
	border-color: transparent;
	border-left-color: white;
  }
  .cd-timeline-content .cd-read-more {
	float: left;
  }
  .cd-timeline-content .cd-date {
	position: absolute;
	width: 55%;
	left: 115%;
	top: 6px;
	font-size: 16px;
	font-size: 1rem;
  }
  .cd-timeline-block:nth-child(even) .cd-timeline-content {
	float: right;
  }
  .cd-timeline-block:nth-child(even) .cd-timeline-content::before {
	top: 24px;
	left: auto;
	right: 100%;
	border-color: transparent;
	border-right-color: white;
  }
  .cd-timeline-block:nth-child(even) .cd-timeline-content .cd-read-more {
	float: right;
  }
  .cd-timeline-block:nth-child(even) .cd-timeline-content .cd-date {
	left: auto;
	right: 115%;
	text-align: right;
  }

}


/* ============================================================================== */
/* CSS style for debugbar                                                         */
/* ============================================================================== */

span.phpdebugbar-tooltip.phpdebugbar-tooltip-extra-wide, span.phpdebugbar-tooltip.phpdebugbar-tooltip-wide {
	width: 250px !important;
}
.phpdebugbar-indicator span.phpdebugbar-tooltip {
	opacity: .95 !important;
}
a.phpdebugbar-tab.phpdebugbar-active {
	background-image: unset !important;
}
.phpdebugbar-indicator .fa {
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>";
	font-weight: 600;
}
div.phpdebugbar-widgets-messages li.phpdebugbar-widgets-list-item span.phpdebugbar-widgets-value.phpdebugbar-widgets-warning:before,
div.phpdebugbar-widgets-messages li.phpdebugbar-widgets-list-item span.phpdebugbar-widgets-value.phpdebugbar-widgets-error:before,
div.phpdebugbar-widgets-exceptions a.phpdebugbar-widgets-editor-link:before,
div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-database:before,
div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-duration:before,
div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-memory:before,
div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-row-count:before,
div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-copy-clipboard:before,
div.phpdebugbar-widgets-sqlqueries span.phpdebugbar-widgets-stmt-id:before,
div.phpdebugbar-widgets-templates span.phpdebugbar-widgets-render-time:before,
div.phpdebugbar-widgets-templates span.phpdebugbar-widgets-memory:before,
div.phpdebugbar-widgets-templates span.phpdebugbar-widgets-param-count:before,
div.phpdebugbar-widgets-templates span.phpdebugbar-widgets-type:before,
div.phpdebugbar-widgets-templates a.phpdebugbar-widgets-editor-link:before
{
	font-family: "<?php echo getDolGlobalString('MAIN_FONTAWESOME_FAMILY', 'Font Awesome 5 Free'); ?>" !important;
}


/* ============================================================================== */
/* CSS style used for jCrop                                                       */
/* ============================================================================== */

.jcrop-holder { background: unset !important; }


/* ============================================================================== */
/* CSS style used for jFlot                                                       */
/* ============================================================================== */

.dol-xaxis-vertical .flot-x-axis .flot-tick-label.tickLabel {
	text-orientation: sideways;
	font-weight: 400;
	writing-mode: vertical-rl;
	white-space: nowrap;
}


/* ============================================================================== */
/* For copy-paste feature                                                         */
/* ============================================================================== */

span.clipboardCPValueToPrint, div.clipboardCPValueToPrint {
	display: inline-block;
}
span.clipboardCPValue.hidewithsize {
	width: 0 !important;
	display: inline-block;	/* this will be modify on the fly by the copy-paste js code in lib_foot.js.php to have copy feature working */
	color: transparent;
	white-space: nowrap;
	overflow-x: hidden;
	vertical-align: middle;
}
div.clipboardCPValue.hidewithsize {
	width: 0 !important;
	display: none;
	color: transparent;
	white-space: nowrap;
}

.clipboardCPShowOnHover .clipboardCPButton {
	display: none;
}

/* To make a div popup, we must use a position absolute inside a position relative */

.clipboardCPText {
	position: relative;
}
.clipboardCPTextDivInside {
	position: absolute;
	background: #EEE;
	color: #888;
	border: 1px solid #DDD;
	opacity: 1;
	z-index: 20;
	padding: 2px;
	padding-left: 4px;
	padding-right: 4px;
	top: -5px;
	left: 0px;
	border-radius: 5px;
	white-space: nowrap;
	font-size: 0.95em;
	box-shadow: 1px 1px 6px #ddd;
}


/* ============================================================================== */
/* CSS style used for hrm skill/rank (may be we can remove this)                  */
/* ============================================================================== */

.radio_js_bloc_number {
	display:inline-block;
	padding:5px 7px;
	min-width:20px;
	border-radius:3px;
	border:1px solid #ccc;
	background:#eee;
	color:#555;
	cursor:pointer;
	margin:2px;
	text-align:center;
}
.radio_js_bloc_number.selected {
	transition:0.2s ease background;
	background:#888;
	color:#fff;
	border-color:#555;
}


/* ============================================================================== */
/* Virtual business card                                                          */
/* ============================================================================== */

.virtualcard-div {
	overflow: hidden;
	vertical-align: top;
	/* background: #aaa; */
}

#virtualcard-iframe {
	border: 40px solid #aaa;
	vertical-align: top;
	width: 10%;
	min-width: 100px;
	border-radius: 10px;
	aspect-ratio: 0.6;
}
.nopointervent {
	pointer-events: none;
}
.scalepreview {
	/* transform: scale(0.5); */
	zoom: 0.20;
}

/* ============================================================================== */
/* For drag and drop file feature                                                 */
/* ============================================================================== */

.cssDragDropArea{
	position: relative;
}
.highlightDragDropArea{
	border: 2px #000 dashed !important;
	background-color: #eee !important;
}
.highlightDragDropArea * :not(.dragDropAreaMessage *){
	opacity:0.8;
	filter: blur(1px) grayscale(90%);
}
.dragDropAreaMessage {
	position: absolute;
	left:50%;
	top:50%;
	transform: translate(-50%, -50%);
	text-align:center;
	font-size: 2em;
}

/* ============================================================================== */
/* CSS style used for color jPicker                                               */
/* ============================================================================== */

table.jPicker {
	border: 1px solid #bbb !important;
}

/* ============================================================================== */
/* CSS style used for survey                                                      */
/* ============================================================================== */

.opensurveydescription * {
	width: 100%;
}
.imgopensurveywizard
{
	padding: 0 4px 0 4px;
}
.survey_borders {
	margin-left: 100px;
	margin-right: 100px;
	text-align: start;
}
.survey_intro {
	background-color: #f0f0f0;
	padding: 15px;
	border-radius: 8px;
}
.survey_borders .resultats .nom {
	text-align: <?php echo $left; ?>
}
.survey_borders .resultats .sujet {
	min-width: 100px;
}


/* ============================================================================== */
/* CSS style used for BookCal                                                     */
/* ============================================================================== */

.center.bookingtab {
	margin-left: 20px;
}
#bookinghoursection {
	width: 145px;
	height: 320px;
	overflow-y: auto;
	overflow-x: hidden;
	text-align: left;
}
.bookcalform {
	border: 1px solid #000;
	padding: 15px;
	border-radius: 5px;
	margin-bottom: 15px;
}


/* ============================================================================== */
/* CSS style used for small screen                                                */
/* ============================================================================== */

@media only screen and (max-width: 767px)
{
	.imgopensurveywizard, .imgautosize { width:95%; height: auto; }

	#tooltip {
		position: absolute;
		width: <?php print dol_size(350, 'width'); ?>px;
	}

	div.tabBar {
		padding-left: 8px;
		padding-right: 8px;
		-webkit-border-radius: 0;
		border-radius: 0px;
		border-right: none;
		border-left: none;
	}

	td.widthpictotitle { width: 30px; }

	.logopublicpayment #dolpaymentlogo {
		max-width: 260px;
	}
	#tablepublicpayment {
		width:	auto !important;
		border: none !important;
	}
	.poweredbypublicpayment {
		float: unset !important;
		top: unset !important;
		/* bottom: 8px; */
		right: -10px !important;
		position: relative !important;
	}
	.poweredbyimg {
		width: 48px;
	}

	.survey_borders {
		margin-left: 10px;
		margin-right: 10px;
		text-align: start;
	}

	.bookcalform.boxtable .minwidth75 {
		min-width: auto;
	}
	.center.bookingtab {
		margin-left: 6px;
	}
	#bookinghoursection {
		font-size: small;
		width: 122px;
	}
}

@media only screen and (max-width: 1024px)
{
	div#ecm-layout-west {
		width: 100%;
		clear: both;
	}
	div#ecm-layout-center {
		width: 100%;
	}
}

.menuhider {
	width: <?php echo $disableimages ? 'auto' : '44'; ?>px;
}

/* nboftopmenuentries = <?php echo $nbtopmenuentries ?>, fontsize=<?php echo is_numeric($fontsize) ? $fontsize.'px' : $fontsize ?> */
/* rule to reduce top menu - 1st reduction: Reduce width of top menu icons */
@media only screen and (max-width: <?php echo !getDolGlobalString('THEME_ELDY_WITDHOFFSET_FOR_REDUC1') ? round($nbtopmenuentries * 90, 0) + 340 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC1; ?>px)	/* reduction 1 */
{
	div.tmenucenter {
		max-width: 56px;	/* size of viewport */
		width: 52px;
		white-space: nowrap;
		  overflow: hidden;
		  text-overflow: ellipsis;
		  color: #<?php echo $colortextbackhmenu; ?>;
	}
	.mainmenuaspan {
		  font-size: 0.9em;
		  /* font-weight: 300; */
	}
	.topmenuimage {
		background-size: 24px auto;
		margin-top: 0px;
	}
	li.tmenu, li.tmenusel {
		min-width: 34px;
	}
	div.mainmenu {
		min-width: auto;
	}
	div.tmenuleft {
		display: none;
	}
}
/* rule to reduce top menu - 2nd reduction: Reduce width of top menu icons again */
@media only screen and (max-width: <?php echo !getDolGlobalString('THEME_ELDY_WITDHOFFSET_FOR_REDUC2') ? round($nbtopmenuentries * 69, 0) + 130 : $conf->global->THEME_ELDY_WITDHOFFSET_FOR_REDUC2; ?>px)	/* reduction 2 */
{
	li.tmenucompanylogo {
		display: none;
	}

	div.tmenucenter {
		max-width: <?php echo max(24, ceil(300 / ($nbtopmenuentriesreal + 2))); ?>px;	/* size of viewport */
		text-overflow: clip;
	}
	.menuhider div.tmenucenter {
		max-width: 28px;
	}
	.mainmenuaspan {
		  font-size: 10px;
		  padding-left: 0;
		  padding-right: 0;
	}
	.topmenuimage {
		background-size: 20px auto;
		margin-top: 2px;
	}
}
/* rule to reduce top menu - 3rd reduction */
@media only screen and (max-width: 570px)
{
	div.login_block {
		border-right: 1px solid rgba(0,0,0,0.3);
		top: auto;
	}

	div#tmenu_tooltip {
	<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
		display:none;
	<?php } else { ?>
		/* padding-<?php echo $right; ?>: 78px; */
	<?php } ?>
	}
	li.tmenu, li.tmenusel {
		min-width: 30px;
	}
	a.tmenu:link, a.tmenu:visited, a.tmenu:hover, a.tmenu:active,
	a.tmenusel:link, a.tmenusel:visited, a.tmenusel:hover, a.tmenusel:active {
		padding-left: 1px;
		padding-right: 1px;
	}

	div.login_block {
		border-right: 1px solid rgba(0,0,0,0.3);
	}
	.loginbuttonexternal {
		width: 260px;
	}

	div.tmenucenter {
		  text-overflow: clip;
	}
	.topmenuimage {
		background-size: 20px auto;
		margin-top: 2px !important;
	}
	div.mainmenu {
		min-width: 20px;
	}

	#tooltip {
		position: absolute;
		width: <?php print dol_size(300, 'width'); ?>px;
	}
	select {
		width: 98%;
		min-width: 0 !important;
	}
	div.divphotoref {
		padding-right: 5px;
	}
	img.photoref, div.photoref {
		border: 1px solid rgba(0, 0, 0, 0.2);
		-webkit-box-shadow: none;
		box-shadow: none;
		padding: 4px;
		object-fit: contain;
	}

	.titlefield {
		width: auto !important;		/* We want to ignore the 30%, try to use more if you can */
		min-width: unset;
	}
	.tableforfield>tr>td:first-child, .tableforfield>tbody>tr>td:first-child, div.tableforfield div.tagtr>div.tagtd:first-of-type {
		/* max-width: 100px; */			/* but no more than 100px */
	}
	.tableforfield>tr>td:nth-child(2), .tableforfield>tbody>tr>td:nth-child(2), div.tableforfield div.tagtr>div.tagtd:nth-child(2) {
		word-break: break-word;
	}

	table.table-fiche-title .col-title div.titre{
		line-height: unset;
	}

	input#addedfile {
		width: 95%;
	}

	#divbodywebsite {
		word-break: break-word;
	}

	.websiteselectionsection {
		border-left: unset;
		border-right: unset;
		padding-left: 5px;
	}

	.a-mesure, .a-mesure-disabled {
		display: block;
		margin-bottom: 6px;
		padding-left: 12px;
		padding-right: 12px;
	}

	.a-mesure, .a-mesure-disabled {
		text-align: center;
	}


	.underbanner.underbanner-before-box {
		border-bottom: none;
	}

	div.divButAction {
		margin-bottom: 0.5em;
	}

	div#card-errors {
		max-width: unset;
	}

	#dolpaymenttable {
		padding: 5px;
	}

	.lilevel1 span.paddingright {
		padding-right: 3px;
	}

	img.userphotopublicvcard {
		left: unset;
		top: unset;
		margin-top: 30px;
	}
}

<?php
if (getDolUserString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
	// Set a max height on multiselect when using multiselect
	?>
	select[multiple] {
		height: 42px;
	}
	<?php
}

include dol_buildpath($path.'/theme/'.$theme.'/dropdown.inc.php', 0);
include dol_buildpath($path.'/theme/eldy/emaillayout.inc.php', 0); // actually md use same style as eldy theme
include dol_buildpath($path.'/theme/'.$theme.'/info-box.inc.php', 0);
include dol_buildpath($path.'/theme/'.$theme.'/progress.inc.php', 0);
include dol_buildpath($path.'/theme/eldy/timeline.inc.php', 0); // actually md use same style as eldy theme

if (getDolGlobalString('THEME_CUSTOM_CSS')) {
	print $conf->global->THEME_CUSTOM_CSS;
}

if (is_object($db)) {
	$db->close();
}
?>

/* This must be at end */
::-webkit-scrollbar {
	width: 12px;
}
/*::-webkit-scrollbar-button {
	background: #bbb;
}*/
::-webkit-scrollbar-track-piece {
	background: #f4f4f4;
}
::-webkit-scrollbar-thumb {
	background: #ddd;
}​



/* Must be at end */
div.flot-text .flot-tick-label .tickLabel, .fa-color-unset {
	color: unset;
}
