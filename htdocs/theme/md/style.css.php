<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
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
if (!defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOLOGIN'))         define('NOLOGIN', 1); // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (!defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');


define('ISLOADEDBYSTEELSHEET', '1');


require __DIR__.'/theme_vars.inc.php';
if (defined('THEME_ONLY_CONSTANT')) return;

session_cache_limiter('public');


require_once __DIR__.'/../../main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
// and permission, so we can later calculate number of top menu ($nbtopmenuentries) according to user profile.
if (empty($user->id) && !empty($_SESSION['dol_login']))
{
	$user->fetch('', $_SESSION['dol_login'], '', 1);
	$user->getrights();
}


// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

if (GETPOST('theme', 'alpha')) $conf->theme = GETPOST('theme', 'alpha'); // If theme was forced on URL
if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL

$langs->load("main", 0, 1);
$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');

$path = ''; // This value may be used in future for external module to overwrite theme
$theme = 'md'; // Value of theme
if (!empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path = '/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme = $conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define image path files and other constants
$fontlist = 'roboto,arial,tahoma,verdana,helvetica'; //$fontlist='verdana,helvetica,arial,sans-serif';
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

// Case of option always editable
if (!isset($conf->global->THEME_ELDY_BACKBODY)) $conf->global->THEME_ELDY_BACKBODY = $colorbackbody;
if (!isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) $conf->global->THEME_ELDY_TOPMENU_BACK1 = $colorbackhmenu1;
if (!isset($conf->global->THEME_ELDY_VERMENU_BACK1)) $conf->global->THEME_ELDY_VERMENU_BACK1 = $colorbackvmenu1;
if (!isset($conf->global->THEME_ELDY_BACKTITLE1)) $conf->global->THEME_ELDY_BACKTITLE1 = $colorbacktitle1;
if (!isset($conf->global->THEME_ELDY_USE_HOVER)) $conf->global->THEME_ELDY_USE_HOVER = $colorbacklinepairhover;
if (!isset($conf->global->THEME_ELDY_USE_CHECKED)) $conf->global->THEME_ELDY_USE_CHECKED = $colorbacklinepairchecked;
if (!isset($conf->global->THEME_ELDY_LINEBREAK)) $conf->global->THEME_ELDY_LINEBREAK = $colorbacklinebreak;
if (!isset($conf->global->THEME_ELDY_TEXTTITLENOTAB)) $conf->global->THEME_ELDY_TEXTTITLENOTAB = $colortexttitlenotab;
if (!isset($conf->global->THEME_ELDY_TEXTLINK)) $conf->global->THEME_ELDY_TEXTLINK = $colortextlink;

// Case of option editable only if option THEME_ELDY_ENABLE_PERSONALIZED is on
if (empty($conf->global->THEME_ELDY_ENABLE_PERSONALIZED))
{
	// 90A4AE, 607D8B, 455A64, 37474F
	$conf->global->THEME_ELDY_BACKTABCARD1 = '255,255,255'; // card
	$conf->global->THEME_ELDY_BACKTABACTIVE = '234,234,234';
	$conf->global->THEME_ELDY_TEXT = '0,0,0';
	$conf->global->THEME_ELDY_FONT_SIZE1 = $fontsize;
	$conf->global->THEME_ELDY_FONT_SIZE2 = '11';
}

// Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
$colorbackhmenu1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackvmenu1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_VERMENU_BACK1) ? $colorbackvmenu1 : $conf->global->THEME_ELDY_VERMENU_BACK1) : (empty($user->conf->THEME_ELDY_VERMENU_BACK1) ? $colorbackvmenu1 : $user->conf->THEME_ELDY_VERMENU_BACK1);
$colortopbordertitle1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPBORDER_TITLE1) ? $colortopbordertitle1 : $conf->global->THEME_ELDY_TOPBORDER_TITLE1) : (empty($user->conf->THEME_ELDY_TOPBORDER_TITLE1) ? $colortopbordertitle1 : $user->conf->THEME_ELDY_TOPBORDER_TITLE1);
$colorbacktitle1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_BACKTITLE1) ? $colorbacktitle1 : $conf->global->THEME_ELDY_BACKTITLE1) : (empty($user->conf->THEME_ELDY_BACKTITLE1) ? $colorbacktitle1 : $user->conf->THEME_ELDY_BACKTITLE1);
$colorbacktabcard1   = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_BACKTABCARD1) ? $colorbacktabcard1 : $conf->global->THEME_ELDY_BACKTABCARD1) : (empty($user->conf->THEME_ELDY_BACKTABCARD1) ? $colorbacktabcard1 : $user->conf->THEME_ELDY_BACKTABCARD1);
$colorbacktabactive  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_BACKTABACTIVE) ? $colorbacktabactive : $conf->global->THEME_ELDY_BACKTABACTIVE) : (empty($user->conf->THEME_ELDY_BACKTABACTIVE) ? $colorbacktabactive : $user->conf->THEME_ELDY_BACKTABACTIVE);
$colorbacklineimpair1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_LINEIMPAIR1) ? $colorbacklineimpair1 : $conf->global->THEME_ELDY_LINEIMPAIR1) : (empty($user->conf->THEME_ELDY_LINEIMPAIR1) ? $colorbacklineimpair1 : $user->conf->THEME_ELDY_LINEIMPAIR1);
$colorbacklineimpair2 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_LINEIMPAIR2) ? $colorbacklineimpair2 : $conf->global->THEME_ELDY_LINEIMPAIR2) : (empty($user->conf->THEME_ELDY_LINEIMPAIR2) ? $colorbacklineimpair2 : $user->conf->THEME_ELDY_LINEIMPAIR2);
$colorbacklinepair1  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_LINEPAIR1) ? $colorbacklinepair1 : $conf->global->THEME_ELDY_LINEPAIR1) : (empty($user->conf->THEME_ELDY_LINEPAIR1) ? $colorbacklinepair1 : $user->conf->THEME_ELDY_LINEPAIR1);
$colorbacklinepair2  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_LINEPAIR2) ? $colorbacklinepair2 : $conf->global->THEME_ELDY_LINEPAIR2) : (empty($user->conf->THEME_ELDY_LINEPAIR2) ? $colorbacklinepair2 : $user->conf->THEME_ELDY_LINEPAIR2);
$colorbacklinebreak  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_LINEBREAK) ? $colorbacklinebreak : $conf->global->THEME_ELDY_LINEBREAK) : (empty($user->conf->THEME_ELDY_LINEBREAK) ? $colorbacklinebreak : $user->conf->THEME_ELDY_LINEBREAK);
$colorbackbody       = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_BACKBODY) ? $colorbackbody : $conf->global->THEME_ELDY_BACKBODY) : (empty($user->conf->THEME_ELDY_BACKBODY) ? $colorbackbody : $user->conf->THEME_ELDY_BACKBODY);
$colortexttitlenotab = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TEXTTITLENOTAB) ? $colortexttitlenotab : $conf->global->THEME_ELDY_TEXTTITLENOTAB) : (empty($user->conf->THEME_ELDY_TEXTTITLENOTAB) ? $colortexttitlenotab : $user->conf->THEME_ELDY_TEXTTITLENOTAB);
$colortexttitle      = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TEXTTITLE) ? $colortext : $conf->global->THEME_ELDY_TEXTTITLE) : (empty($user->conf->THEME_ELDY_TEXTTITLE) ? $colortexttitle : $user->conf->THEME_ELDY_TEXTTITLE);
$colortexttitlelink  = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TEXTTITLELINK) ? $colortexttitlelink : $conf->global->THEME_ELDY_TEXTTITLELINK) : (empty($user->conf->THEME_ELDY_TEXTTITLELINK) ? $colortexttitlelink : $user->conf->THEME_ELDY_TEXTTITLELINK);
$colortext           = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TEXT) ? $colortext : $conf->global->THEME_ELDY_TEXT) : (empty($user->conf->THEME_ELDY_TEXT) ? $colortext : $user->conf->THEME_ELDY_TEXT);
$colortextlink       = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TEXTLINK) ? $colortext : $conf->global->THEME_ELDY_TEXTLINK) : (empty($user->conf->THEME_ELDY_TEXTLINK) ? $colortextlink : $user->conf->THEME_ELDY_TEXTLINK);
$fontsize            = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_FONT_SIZE1) ? $fontsize : $conf->global->THEME_ELDY_FONT_SIZE1) : (empty($user->conf->THEME_ELDY_FONT_SIZE1) ? $fontsize : $user->conf->THEME_ELDY_FONT_SIZE1);
$fontsizesmaller     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_FONT_SIZE2) ? $fontsize : $conf->global->THEME_ELDY_FONT_SIZE2) : (empty($user->conf->THEME_ELDY_FONT_SIZE2) ? $fontsize : $user->conf->THEME_ELDY_FONT_SIZE2);

// Hover color
$colorbacklinepairhover = ((!isset($conf->global->THEME_ELDY_USE_HOVER) || (string) $conf->global->THEME_ELDY_USE_HOVER === '255,255,255') ? '' : ($conf->global->THEME_ELDY_USE_HOVER === '1' ? 'edf4fb' : $conf->global->THEME_ELDY_USE_HOVER));
$colorbacklinepairchecked = ((!isset($conf->global->THEME_ELDY_USE_CHECKED) || (string) $conf->global->THEME_ELDY_USE_CHECKED === '255,255,255') ? '' : ($conf->global->THEME_ELDY_USE_CHECKED === '1' ? 'edf4fb' : $conf->global->THEME_ELDY_USE_CHECKED));
if (!empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED))
{
	$colorbacklinepairhover = ((!isset($user->conf->THEME_ELDY_USE_HOVER) || $user->conf->THEME_ELDY_USE_HOVER === '255,255,255') ? '' : ($user->conf->THEME_ELDY_USE_HOVER === '1' ? 'edf4fb' : $user->conf->THEME_ELDY_USE_HOVER));
	$colorbacklinepairchecked = ((!isset($user->conf->THEME_ELDY_USE_CHECKED) || $user->conf->THEME_ELDY_USE_CHECKED === '255,255,255') ? '' : ($user->conf->THEME_ELDY_USE_CHECKED === '1' ? 'edf4fb' : $user->conf->THEME_ELDY_USE_CHECKED));
}

if (empty($colortopbordertitle1)) $colortopbordertitle1 = $colorbackhmenu1;

// Set text color to black or white
$colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbackhmenu1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) $colortextbackhmenu = 'FFFFFF';
else $colortextbackhmenu = '000000';

$colorbackvmenu1 = join(',', colorStringToArray($colorbackvmenu1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbackvmenu1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) { $colortextbackvmenu = 'FFFFFF'; }
else { $colortextbackvmenu = '000000'; }

$colorbacktitle1 = join(',', colorStringToArray($colorbacktitle1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbacktitle1);
if ($colortexttitle == '')
{
	$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
	if ($tmpval <= 460) { $colortexttitle = 'FFFFFF'; $colorshadowtitle = '888888'; }
	else { $colortexttitle = '101010'; $colorshadowtitle = 'FFFFFF'; }
}
else $colorshadowtitle = '888888';

$colorbacktabcard1 = join(',', colorStringToArray($colorbacktabcard1)); // Normalize value to 'x,y,z'
$tmppart = explode(',', $colorbacktabcard1);
$tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) { $colortextbacktab = 'FFFFFF'; }
else { $colortextbacktab = '111111'; }

// Format color value to match expected format (may be 'FFFFFF' or '255,255,255')
$colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1));
$colorbackvmenu1 = join(',', colorStringToArray($colorbackvmenu1));
$colorbacktitle1 = join(',', colorStringToArray($colorbacktitle1));
$colorbacktabcard1 = join(',', colorStringToArray($colorbacktabcard1));
$colorbacktabactive = join(',', colorStringToArray($colorbacktabactive));
$colorbacklineimpair1 = join(',', colorStringToArray($colorbacklineimpair1));
$colorbacklineimpair2 = join(',', colorStringToArray($colorbacklineimpair2));
$colorbacklinepair1 = join(',', colorStringToArray($colorbacklinepair1));
$colorbacklinepair2 = join(',', colorStringToArray($colorbacklinepair2));
if ($colorbacklinepairhover != '') $colorbacklinepairhover = join(',', colorStringToArray($colorbacklinepairhover));
if ($colorbacklinepairchecked != '') $colorbacklinepairchecked = join(',', colorStringToArray($colorbacklinepairchecked));
$colorbackbody = join(',', colorStringToArray($colorbackbody));
$colortexttitlenotab = join(',', colorStringToArray($colortexttitlenotab));
$colortexttitle = join(',', colorStringToArray($colortexttitle));
$colortext = join(',', colorStringToArray($colortext));
$colortextlink = join(',', colorStringToArray($colortextlink));

$nbtopmenuentries = $menumanager->showmenu('topnb');
if ($conf->browser->layout == 'phone') $nbtopmenuentries = max($nbtopmenuentries, 10);

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
print '$colortexttitlenotab='.$colortexttitlenotab."\n";
print '$colortexttitle='.$colortexttitle."\n";
print '$colortext='.$colortext."\n";
print '$colortextlink='.$colortextlink."\n";
print '$colortextbackhmenu='.$colortextbackhmenu."\n";
print '$colortextbackvmenu='.$colortextbackvmenu."\n";
print 'dol_hide_topmenu='.$dol_hide_topmenu."\n";
print 'dol_hide_leftmenu='.$dol_hide_leftmenu."\n";
print 'dol_optimize_smallscreen='.$dol_optimize_smallscreen."\n";
print 'dol_no_mouse_hover='.$dol_no_mouse_hover."\n";
print 'dol_screenwidth='.$_SESSION['dol_screenwidth']."\n";
print 'dol_screenheight='.$_SESSION['dol_screenheight']."\n";
print 'fontsize='.$fontsize."\n";
print 'nbtopmenuentries='.$nbtopmenuentries."\n";
print '*/'."\n";

?>

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
	--colortexttitlenotab: rgb(<?php print $colortexttitlenotab; ?>);
	--colortexttitle: rgb(<?php print $colortexttitle; ?>);
	--colortext: rgb(<?php print $colortext; ?>);
	--colortextlink: rgb(<?php print $colortextlink; ?>);
	--colortextbackhmenu: #<?php echo $colortextbackhmenu; ?>;
	--colortextbackvmenu: #<?php print $colortextbackvmenu; ?>;
	--listetotal: #551188;
	--inputbackgroundcolor: #FFF;
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
	--amountpaymentcomplete:#008800;
	--amountremaintopaybackcolor:none;
}

body {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background: rgb(<?php print $colorbackbody; ?>);
<?php } ?>
	color: rgb(<?php echo $colortext; ?>);
	font-size: <?php print is_numeric($fontsize) ? $fontsize.'px' : $fontsize; ?>;
	line-height: 1.3;
	font-family: <?php print $fontlist ?>;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
    <?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
}

.sensiblehtmlcontent * {
	position: static !important;
}

.thumbstat { font-weight: bold !important; }
th a { font-weight: <?php echo ($useboldtitle ? 'bold' : 'normal'); ?> !important; }
a.tab { font-weight: 500 !important; }

a:link, a:visited, a:hover, a:active { font-family: <?php print $fontlist ?>; font-weight: normal; color: rgb(<?php print $colortextlink; ?>); text-decoration: none;  }
a:hover { text-decoration: underline; color: rgb(<?php print $colortextlink; ?>); }
a.commonlink { color: rgb(<?php print $colortextlink; ?>) !important; text-decoration: none; }

input, input.flat, textarea, textarea.flat, form.flat select, select, select.flat, .dataTables_length label select {
    background-color: #FDFDFD;
}
select.vmenusearchselectcombo {
	background-color: unset;
}

textarea:focus, button:focus {
    /* v6 box-shadow: 0 0 4px #8091BF; */
	border: 1px solid #aaa !important;
}
input:focus, textarea:focus, button:focus, select:focus {
	border-bottom: 1px solid #666;
}

textarea.cke_source:focus
{
	box-shadow: none;
}

th.wrapcolumntitle.liste_titre:not(.maxwidthsearch), td.wrapcolumntitle.liste_titre:not(.maxwidthsearch) {
    overflow: hidden;
    white-space: nowrap;
    max-width: 120px;
    text-overflow: ellipsis;
}
.liste_titre input[name=month_date_when], .liste_titre input[name=monthvalid], .liste_titre input[name=search_ordermonth], .liste_titre input[name=search_deliverymonth],
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
select#date_startday, select#date_startmonth, select#date_endday, select#date_endmonth, select#reday, select#remonth
{
	margin-right: 4px;
}
input, input.flat, textarea, textarea.flat, form.flat select, select, select.flat, .dataTables_length label select {
	font-family: <?php print $fontlist ?>;
    border: none;
    border<?php echo empty($conf->global->THEME_HIDE_BORDER_ON_INPUT) ? '-bottom' : ''; ?>: solid 1px rgba(0,0,0,.2);
    outline: none;
    margin: 0px 0px 0px 0px;
}

input {
    line-height: 17px;
	padding: 4px;
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
input.button.massactionconfirmed {
    margin: 4px;
}

textarea {
	border-radius: 0;
	border-top:solid 1px rgba(0,0,0,.1);
	border-left:solid 1px rgba(0,0,0,.1);
	border-right:solid 1px rgba(0,0,0,.1);
	border-bottom:solid 1px rgba(0,0,0,.2);

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
input.buttonpaymentcheque {
	background-image: url(<?php echo dol_buildpath($path.'/paypal/img/object_paypal.png', 1) ?>);
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
a.buttonticket {
	padding-left: 5px;
	padding-right: 5px;
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
td.leftborder, td.hide0 {
	border-left: 1px solid #ccc;
}
td.leftborder, td.hide6 {
	border-right: 1px solid #ccc;
}
td.rightborder {
	border-right: 1px solid #ccc;
}

td.actionbuttons a {
    padding-left: 6px;
}
select.flat, form.flat select, .pageplusone {
	font-weight: normal;
	font-size: unset;
	height: 2em;
}
input.pageplusone {
    padding-bottom: 4px;
    padding-top: 4px;
}

.optionblue {
	color: rgb(<?php echo $colortextlink; ?>);
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
	opacity: 0.2;
}
.opacitytransp {
	opacity: 0;
}
.colorwhite {
	color: #fff;
}
.colorblack {
	color: #000;
}

select:invalid {
	color: gray;
}
input:disabled, textarea:disabled, select[disabled='disabled']
{
	background:#eee;
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

input[type=file ]    { background-color: transparent; border-top: none; border-left: none; border-right: none; box-shadow: none; }
input[type=checkbox] { background-color: transparent; border: none; box-shadow: none; }
input[type=radio]    { background-color: transparent; border: none; box-shadow: none; }
input[type=image]    { background-color: transparent; border: none; box-shadow: none; }
input:-webkit-autofill {
	background-color: #FBFFEA !important;
	background-image:none !important;
	-webkit-box-shadow: 0 0 0 50px #FBFFEA inset;
}
::-webkit-input-placeholder { color:#ccc; }
:-moz-placeholder { color:#bbb; } 			/* firefox 18- */
::-moz-placeholder { color:#bbb; } 			/* firefox 19+ */
:-ms-input-placeholder { color:#ccc; } 		/* ie */
input:-moz-placeholder { color:#ccc; }
input[name=price], input[name=weight], input[name=volume], input[name=surface], input[name=sizeheight], input[name=net_measure], select[name=incoterm_id] { margin-right: 6px; }
fieldset { border: 1px solid #AAAAAA !important; }
.legendforfieldsetstep { padding-bottom: 10px; }
input#onlinepaymenturl, input#directdownloadlink {
	opacity: 0.7;
}

div#moretabsList, div#moretabsListaction {
    z-index: 5;
}

hr { border: 0; border-top: 1px solid #ccc; }

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
.button:focus, .buttonDelete:focus  {
	-webkit-box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
}
.button:hover, .buttonDelete:hover   {
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
    background: unset;
}
.button_search:hover, .button_removefilter:hover {
    cursor: pointer;
}
form {
    padding:0px;
    margin:0px;
}
div.float
{
    float:<?php print $left; ?>;
}
div.floatright
{
    float:<?php print $right; ?>;
}
.inline-block
{
	display:inline-block;
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
.quatrevingtpercent, .inputsearch {
	width: 80%;
}
.soixantepercent {
	width: 60%;
}
.quatrevingtquinzepercent {
	width: 95%;
}
textarea.centpercent {
	width: 96%;
}
.small, small {
    font-size: 85%;
}

.h1 .small, .h1 small, .h2 .small, .h2 small, .h3 .small, .h3 small, h1 .small, h1 small, h2 .small, h2 small, h3 .small, h3 small {
    font-size: 65%;
}
.h1 .small, .h1 small, .h2 .small, .h2 small, .h3 .small, .h3 small, .h4 .small, .h4 small, .h5 .small, .h5 small, .h6 .small, .h6 small, h1 .small, h1 small, h2 .small, h2 small, h3 .small, h3 small, h4 .small, h4 small, h5 .small, h5 small, h6 .small, h6 small {
    font-weight: 400;
    line-height: 1;
    color: #777;
}

.center {
    text-align: center;
    margin: 0px auto;
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
	white-space: <?php print ($dol_optimize_smallscreen ? 'normal' : 'nowrap'); ?>;
}
.liste_titre .nowrap {
	white-space: nowrap;
}
.nowraponall {	/* no wrap on all devices */
	white-space: nowrap;
}
.wrapimp {
	white-space: normal !important;
}
.wordwrap {
	word-wrap: break-word;
}
.wordbreakimp {
	word-break: break-word;
}
.wordbreak {
	word-break: break-all;
}
.bold {
	font-weight: bold !important;
}
.nobold {
	font-weight: normal !important;
}
.nounderline {
    text-decoration: none;
}
.paddingleft {
	padding-<?php print $left; ?>: 4px;
}
.paddingleft2 {
	padding-<?php print $left; ?>: 2px;
}
.paddingright {
	padding-<?php print $right; ?>: 4px;
}
.paddingright2 {
	padding-<?php print $right; ?>: 2px;
}
.marginleft2 {
	margin-<?php print $left; ?>: 2px;
}
.marginright2 {
	margin-<?php print $right; ?>: 2px;
}
.cursordefault {
	cursor: default;
}
.cursorpointer {
	cursor: pointer;
}
.cursormove {
	cursor: move;
}
.cursornotallowed {
	cursor: not-allowed;
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

.editfielda span.fa-pencil-alt, .editfielda span.fa-trash {
    color: #ccc !important;
}
.editfielda span.fa-pencil-alt:hover, .editfielda span.fa-trash:hover {
    color: rgb(<?php echo $colortexttitle; ?>) !important;
}

.fa-toggle-on, .fa-toggle-off { font-size: 2em; }
.websiteselectionsection .fa-toggle-on, .websiteselectionsection .fa-toggle-off,
.asetresetmodule .fa-toggle-on, .asetresetmodule .fa-toggle-off {
	font-size: 1.5em; vertical-align: text-bottom;
}

.floatnone {
	float: none !important;
}


/* Themes for badges */
<?php include dol_buildpath($path.'/theme/'.$theme.'/badges.inc.php', 0); ?>

.borderrightlight
{
	border-right: 1px solid #f4f4f4;
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
.divsocialnetwork:not(:first-child) {
    padding-left: 20px;
}
div.divsearchfield {
	float: <?php print $left; ?>;
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
<?php
// Add a nowrap on smartphone, so long list of field used for filter are overflowed with clip
if ($conf->browser->layout == 'phone') {
	?>
.divsearchfieldfilter {
   	white-space: nowrap;
}
<?php } ?>
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
	margin-right: 10px !important;
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
.margintoponly {
	margin-top: 10px !important;
}
.marginbottomonly {
	margin-bottom: 10px !important;
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
.tdoverflow {
    max-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.tdoverflowmax50 {			/* For tdoverflow, the max-midth become a minimum ! */
    max-width: 50px;
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
.tdoverflowmax300 {			/* For tdoverflow, the max-midth become a minimum ! */
    max-width: 300px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.tdoverflowauto {
    max-width: 0;
    overflow: auto;
}
.divintodwithtwolinesmax {
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
}

.tablelistofcalendars {
	margin-top: 25px !important;
}
.amountalreadypaid {
}
.amountpaymentcomplete {
	color: #008800;
	font-weight: bold;
}
.amountremaintopay {
	color: #880000;
	font-weight: bold;
}
.amountremaintopayback {
	font-weight: bold;
}
.amountpaymentneutral {
	font-weight: bold;
	font-size: 1.4em;
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
    line-height: 120%;
}
/* Style used for full page tables with field selector and no content after table (priority before previous for such tables) */
div.fiche>form>div.div-table-responsive, div.fiche>form>div.div-table-responsive-no-min {
    overflow-x: auto;
}
div.fiche>form>div.div-table-responsive {
    min-height: 392px;
}

.flexcontainer {
    <?php if (in_array($conf->browser->name, array('chrome', 'firefox'))) echo 'display: inline-flex;' ?>
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

.img-skinthumb {
	width: 160px;
	height: 100px;
}


/* ============================================================================== */
/* Styles to hide objects                                                         */
/* ============================================================================== */

.clearboth  { clear:both; }
.hideobject { display: none; }
.minwidth50  { min-width: 50px; }
.minwidth75  { min-width: 75px; }
/* rule for not too small screen only */
@media only screen and (min-width: <?php echo round($nbtopmenuentries * $fontsize * 3.4, 0) + 7; ?>px)
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
	.minwidth300 { min-width: 300px; }
	.minwidth400 { min-width: 400px; }
	.minwidth500 { min-width: 500px; }
	.minwidth50imp  { min-width: 50px !important; }
    .minwidth75imp  { min-width: 75px !important; }
	.minwidth100imp { min-width: 100px !important; }
	.minwidth200imp { min-width: 200px !important; }
    .minwidth250imp { min-width: 250px !important; }
	.minwidth300imp { min-width: 300px !important; }
	.minwidth400imp { min-width: 400px !important; }
	.minwidth500imp { min-width: 500px !important; }
}
.widthauto { width: auto; }
.width20  { width: 20px; }
.width25  { width: 25px; }
.width50  { width: 50px; }
.width75  { width: 75px; }
.width100 { width: 100px; }
.width150 { width: 150px; }
.width200 { width: 200px; }
.maxwidth25  { max-width: 25px; }
.maxwidth50  { max-width: 50px; }
.maxwidth75  { max-width: 75px; }
.maxwidth100 { max-width: 100px; }
.maxwidth125 { max-width: 125px; }
.maxwidth150 { max-width: 150px; }
.maxwidth200 { max-width: 200px; }
.maxwidth300 { max-width: 300px; }
.maxwidth400 { max-width: 400px; }
.maxwidth500 { max-width: 500px; }
.maxwidth50imp  { max-width: 50px !important; }
.maxwidth75imp  { max-width: 75px !important; }
.minheight20 { min-height: 20px; }
.minheight40 { min-height: 40px; }
.titlefieldcreate { width: 20%; }
.titlefield       { /* width: 25%; */ width: 250px; }
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
    .minwidth150imp { min-width: 110px !important; }
    .minwidth200imp { min-width: 110px !important; }
    .minwidth250imp { min-width: 115px !important; }
    .minwidth300imp { min-width: 120px !important; }
    .minwidth400imp { min-width: 150px !important; }
    .minwidth500imp { min-width: 250px !important; }
}

/* Force values for small screen 767 */
@media only screen and (max-width: 767px)
{
	body {
		font-size: <?php print is_numeric($fontsize) ? ($fontsize).'px' : $fontsize; ?>;
	}
	div.refidno {
		font-size: <?php print is_numeric($fontsize) ? ($fontsize).'px' : $fontsize; ?> !important;
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

	.divmainbodylarge { margin-left: 20px; margin-right: 20px; }

    .tdoverflowonsmartphone {
        max-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
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
		    margin-top: <?php print ($dol_hide_topmenu ? '12' : '6'); ?>px !important;
	}
    .border tbody tr, .border tbody tr td, div.tabBar table.border tr, div.tabBar table.border tr td, div.tabBar div.border .table-border-row, div.tabBar div.border .table-key-border-col, div.tabBar div.border .table-val-border-col {
    	height: 40px !important;
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

    .hideonsmartphone { display: none; }
    .hideonsmartphoneimp { display: none !important; }
    .noenlargeonsmartphone { width : 50px !important; display: inline !important; }
    .maxwidthonsmartphone, #search_newcompany.ui-autocomplete-input { max-width: 100px; }
    .maxwidth50onsmartphone { max-width: 40px; }
    .maxwidth75onsmartphone { max-width: 50px; }
    .maxwidth100onsmartphone { max-width: 70px; }
    .maxwidth150onsmartphone { max-width: 120px; }
    .maxwidth150onsmartphoneimp { max-width: 120px !important; }
    .maxwidth200onsmartphone { max-width: 200px; }
    .maxwidth250onsmartphone { max-width: 250px; }
    .maxwidth300onsmartphone { max-width: 300px; }
    .maxwidth400onsmartphone { max-width: 400px; }
	.minwidth50imp  { min-width: 50px !important; }
	.minwidth75imp  { min-width: 75px !important; }
    .minwidth100imp { min-width: 100px !important; }
    .minwidth150imp { min-width: 110px !important; }
    .minwidth200imp { min-width: 110px !important; }
    .minwidth250imp { min-width: 115px !important; }
    .minwidth300imp { min-width: 120px !important; }
    .minwidth400imp { min-width: 150px !important; }
    .minwidth500imp { min-width: 250px !important; }
    .titlefield { width: auto; }
    .titlefieldcreate { width: auto; }

	#tooltip {
		position: absolute;
		width: <?php print dol_size(300, 'width'); ?>px;
	}

	/* intput, input[type=text], */
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
.linkobject { cursor: pointer; }

table.tableforfield tr>td:first-of-type, div.tableforfield div.tagtr>div.tagtd:first-of-type {
	color: #666;
}

<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
.hideonprint { display: none; }
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
}
#id-left {
	min-height: 100%;
	position: relative;
	width: 213px;
}
#id-right {	/* This must stay id-right and not be replaced with echo $right */
	width: 100%;
	padding-bottom: 10px;
<?php if (GETPOST('optioncss', 'aZ09') != 'print') { ?>
	padding-left: 229px;
	padding-top: 12px;
<?php } ?>
}

.side-nav {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
	display: none;
<?php } else { ?>
	background: rgb(<?php echo $colorbackvmenu1; ?>);
	border-right: 1px solid rgba(0,0,0,0.2);
	box-shadow: 3px 0 6px -2px #eee;
	bottom: 0;
	color: #333;
	display: block;
	font-family: "RobotoDraft","Roboto",sans-serif;
	left: 0;
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	} else { ?>
	position: fixed;
	top: 50px;
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
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	} else { ?>
	overflow-x: hidden;
	overflow-y: auto;
	<?php }
}
?>
}

/*
*	Slide animation
*/
.side-nav-vert, #id-right {
	transition: padding-left 0.5s ease, margin-left 0.5s ease;
}

.side-nav, .login_block {
	transition: left 0.5s ease;
}

body.sidebar-collapse .side-nav-vert, body.sidebar-collapse #id-right {
	margin-left: 0;padding-left:0
}


.side-nav-vert {
	margin-left: 228px;
}

/* body.sidebar-collapse .side-nav, body.sidebar-collapse .login_block_other, body.sidebar-collapse #topmenu-login-dropdown */
body.sidebar-collapse .side-nav, body.sidebar-collapse .login_block
{
	display: none;
}
<?php if (empty($conf->global->THEME_DISABLE_STICKY_TOPMENU)) {  ?>
.side-nav-vert {
	position: sticky;
	top: 0px;
	z-index: 1001;
}
<?php } ?>

/* For smartphone (testmenuhider is on) */
<?php if (in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) { ?>
#id-container {
	width: 100%;
}
.side-nav-vert {
	margin-left: 0;
}
div.login_block {
	/* border-right: none ! important; */
	top: inherit !important;
}

.side-nav {
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
	} else { ?>
	overflow-x: initial !important;
	overflow-y: scroll;
	<?php } ?>
	display: block;

	position: relative;
}



div.backgroundsemitransparent {
	background:rgba(255,255,255,0.6);
	padding-left: 10px;
	padding-right: 10px;
}



/* Login */

div.login_block {
	/* position: initial !important;*/
	/*display: none;*/
}
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
	padding-left: 0 ! important;
}
#id-left {
	z-index: 91;
	background: rgb(<?php echo $colorbackvmenu1; ?>);
	border-right: 1px solid rgba(0,0,0,0.3);
	<?php
	if (in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) { ?>
	top: 50px ! important;
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
	margin-<?php print $left; ?>: <?php print (GETPOST('optioncss', 'aZ09') == 'print' ? 6 : (empty($conf->dol_optimize_smallscreen) ? '24' : '6')); ?>px;
	margin-<?php print $right; ?>: <?php print (GETPOST('optioncss', 'aZ09') == 'print' ? 6 : (empty($conf->dol_optimize_smallscreen) ? '22' : '6')); ?>px;
	<?php if (!empty($dol_hide_leftmenu) && !empty($dol_hide_topmenu)) print 'margin-top: 12px;'; ?>
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
	<?php if ($conf->browser->layout != 'phone') { print "float: ".$left.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone') { print "width: 50%;\n"; } ?>
	<?php if ($conf->browser->layout == 'phone') { print "padding-bottom: 6px;\n"; } ?>
}
div.fichetwothirdright {
	<?php if ($conf->browser->layout != 'phone') { print "float: ".$right.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone') { print "width: 50%;\n"; } ?>
	<?php if ($conf->browser->layout == 'phone') { print "padding-bottom: 6px\n"; } ?>
}
div.fichetwothirdright div.ficheaddleft {
    padding-left: 20px;
}
div.fichehalfleft {
	<?php if ($conf->browser->layout != 'phone') { print "float: ".$left.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone') { print "width: calc(50% - 10px);\n"; } ?>
}
div.fichehalfright {
	<?php if ($conf->browser->layout != 'phone') { print "float: ".$right.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone') { print "width: calc(50% - 10px);\n"; } ?>
}
div.fichehalfright {
	<?php if ($conf->browser->layout == 'phone') { print "margin-top: 10px;\n"; } ?>
}
div.firstcolumn div.box {
	padding-right: 10px;
}
div.secondcolumn div.box {
	padding-left: 10px;
}
/* Force values on one colum for small screen */
@media only screen and (max-width: 900px)
{
    div.fiche {
    	margin-<?php print $left; ?>: <?php print (GETPOST('optioncss', 'aZ09') == 'print' ? 6 : ($dol_hide_leftmenu ? '4' : '20')); ?>px;
    	margin-<?php print $right; ?>: <?php print (GETPOST('optioncss', 'aZ09') == 'print' ? 8 : 16); ?>px;
    	<?php if (!empty($conf->dol_hide_leftmenu) && !empty($conf->dol_hide_topmenu)) print 'margin-top: 4px;'; ?>
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
    div.fichetwothirdright div.ficheaddleft {
    	padding-left: 0;
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

.pictotitle {
	margin-<?php echo $right; ?>: 8px;
	margin-bottom: 4px;
}
.pictoobjectwidth {
	width: 14px;
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
.pictowarning, .pictopreview {
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
.colorthumb {
	padding-left: 1px !important;
	padding-right: 1px;
	padding-top: 1px;
	padding-bottom: 1px;
	width: 44px;
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
div.divphotoref {
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
img.fitcontain {
    object-fit: contain;
}
div.photoref {
	display:table-cell;
	vertical-align:middle;
	text-align:center;
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
	border-bottom: <?php echo $borderwidth ?>px solid rgb(<?php echo $colortopbordertitle1 ?>);
}

.trextrafieldseparator td, .trextrafields_collapse_last td {
    border-bottom: 1px solid rgb(<?php echo $colortopbordertitle1 ?>) !important;
}
.tdhrthin {
	margin: 0;
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
if (!empty($conf->global->THEME_TOPMENU_DISABLE_IMAGE)) { $heightmenu = 30; $disableimages = 1; $maxwidthloginblock = 180; $minwidthtmenu = 0; }
?>

div#tmenu_tooltip {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	display:none;
<?php } else { ?>
	background: rgb(<?php echo $colorbackhmenu1 ?>);
	/*
	background-image: linear-gradient(to top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -webkit-gradient( linear, left top, left bottom, color-stop(0, rgba(255,255,255,.3)), color-stop(1, rgba(128,128,128,.3)) );
	*/
<?php } ?>
}

div#tmenu_tooltip {
<?php if (GETPOST('optioncss', 'aZ09') == 'print') {  ?>
	display:none;
<?php } else { ?>
	/* padding-<?php echo $right; ?>: <?php echo ($maxwidthloginblock - 10); ?>px; */
<?php } ?>
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
	color: #<?php echo $colortextbackhmenu; ?>;
	text-decoration: none;
	cursor: not-allowed;
}

a.tmenu:link, a.tmenu:visited, a.tmenu:hover, a.tmenu:active {
    font-weight: normal;
	padding: 0px 5px 0px 5px;
	white-space: nowrap;
	/*	text-shadow: 1px 1px 1px #000000; */
	color: #<?php echo $colortextbackhmenu; ?>;
    text-decoration: none;
}
a.tmenusel:link, a.tmenusel:visited, a.tmenusel:hover, a.tmenusel:active {
	font-weight: normal;
	padding: 0px 5px 0px 5px;
	margin: 0px 0px 0px 0px;
	white-space: nowrap;
	color: #<?php echo $colortextbackhmenu; ?>;
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
	background: rgb(<?php echo $colorbackhmenu1 ?>);
	/*
	background-image: linear-gradient(to top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-gradient( linear, left top, left bottom, color-stop(0, rgba(255,255,255,.3)), color-stop(1, rgba(0,0,0,.3)) );
	*/
}
li.tmenu, li.tmenusel {
	<?php print $minwidthtmenu ? 'min-width: '.$minwidthtmenu.'px;' : ''; ?>
	text-align: center;
	vertical-align: bottom;
	<?php if (empty($conf->global->MAIN_MENU_INVERT)) { ?>
	float: <?php print $left; ?>;
    	<?php if (!$disableimages) { ?>
    height: <?php print $heightmenu; ?>px;
	padding: 0px 0px 2px 0px;
    	<?php } else { ?>
    padding: 0px 0px 0px 0px;
		<?php } } ?>
	position:relative;
	display: block;
	margin: 0px 0px 0px 0px;
	font-weight: normal;
}
li.tmenu:hover {
	opacity: .50; /* show only a slight shadow */
}
li.tmenusel {
	text-decoration: underline;
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
	padding-bottom: 2px;
	overflow: hidden;
    text-overflow: ellipsis;
}
.mainmenuaspan
{
	padding-<?php print $left; ?>: 2px;
	padding-<?php print $right; ?>: 2px;
}

div.mainmenu {
	position : relative;
	background-repeat:no-repeat;
	background-position:center top;
	height: <?php echo ($heightmenu - 22); ?>px;
	margin-left: 0px;
	min-width: 40px;
}
a.tmenuimage:focus, .mainmenu.topmenuimage:focus {
    outline: none;
}

/* Do not load menu img if hidden to save bandwidth */
<?php if (empty($dol_hide_topmenu)) { ?>
    <?php if (!defined('DISABLE_FONT_AWSOME')) { ?>
        <?php include dol_buildpath($path.'/theme/'.$theme.'/main_menu_fa_icons.inc.php', 0); ?>
    <?php } ?>

div.mainmenu.home{
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/home.png', 1) ?>);
	background-position-x: center;
}

div.mainmenu.billing {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/money.png', 1) ?>);
}

div.mainmenu.accountancy {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/money.png', 1) ?>);
}

div.mainmenu.agenda {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/agenda.png', 1) ?>);
}

div.mainmenu.bank {
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/bank.png', 1) ?>);
}

div.mainmenu.cashdesk {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/pointofsale.png', 1) ?>);
}

div.mainmenu.takepos {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/pointofsale.png', 1) ?>);
}

div.mainmenu.companies {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/company.png', 1) ?>);
}

div.mainmenu.commercial {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/commercial.png', 1) ?>);
}

div.mainmenu.ecm {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/ecm.png', 1) ?>);
}

div.mainmenu.externalsite {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/externalsite.png', 1) ?>);
}

div.mainmenu.ftp {
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/tools.png', 1) ?>);
}

div.mainmenu.hrm {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/holiday.png', 1) ?>);
}

div.mainmenu.members {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/members.png', 1) ?>);
}

div.mainmenu.menu {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/menu.png', 1) ?>);
	top: 10px;
	left: 1px;
}

div.mainmenu.products {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/products.png', 1) ?>);
}

div.mainmenu.mrp {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/products.png', 1) ?>);
}

div.mainmenu.project {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/project.png', 1) ?>);
}

div.mainmenu.ticket {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/ticket.png', 1) ?>);
}

div.mainmenu.tools {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/tools.png', 1) ?>);
}

div.mainmenu.website {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/externalsite.png', 1) ?>);
}

	<?php
	// Add here more div for other menu entries. moduletomainmenu=array('module name'=>'name of class for div')

	$moduletomainmenu = array(
		'user'=>'', 'syslog'=>'', 'societe'=>'companies', 'projet'=>'project', 'propale'=>'commercial', 'commande'=>'commercial',
		'produit'=>'products', 'service'=>'products', 'stock'=>'products',
		'don'=>'accountancy', 'tax'=>'accountancy', 'banque'=>'accountancy', 'facture'=>'accountancy', 'compta'=>'accountancy', 'accounting'=>'accountancy', 'adherent'=>'members', 'import'=>'tools', 'export'=>'tools', 'mailing'=>'tools',
		'contrat'=>'commercial', 'ficheinter'=>'commercial', 'ticket'=>'ticket', 'deplacement'=>'commercial',
		'fournisseur'=>'companies',
		'barcode'=>'', 'fckeditor'=>'', 'categorie'=>'',
	);
	$mainmenuused = 'home';
	foreach ($conf->modules as $val)
	{
		$mainmenuused .= ','.(isset($moduletomainmenu[$val]) ? $moduletomainmenu[$val] : $val);
	}
	$mainmenuusedarray = array_unique(explode(',', $mainmenuused));

	$generic = 1;
	// Put here list of menu entries when the div.mainmenu.menuentry was previously defined
	$divalreadydefined = array('home', 'companies', 'products', 'mrp', 'commercial', 'externalsite', 'accountancy', 'project', 'tools', 'members', 'agenda', 'ftp', 'holiday', 'hrm', 'bookmark', 'cashdesk', 'takepos', 'ecm', 'geoipmaxmind', 'gravatar', 'clicktodial', 'paypal', 'stripe', 'webservices', 'website');
	// Put here list of menu entries we are sure we don't want
	$divnotrequired = array('multicurrency', 'salaries', 'ticket', 'margin', 'opensurvey', 'paybox', 'expensereport', 'incoterm', 'prelevement', 'propal', 'workflow', 'notification', 'supplier_proposal', 'cron', 'product', 'productbatch', 'expedition');
	foreach ($mainmenuusedarray as $val)
	{
		if (empty($val) || in_array($val, $divalreadydefined)) continue;
		if (in_array($val, $divnotrequired)) continue;
		//print "XXX".$val;

		// Search img file in module dir
		$found = 0; $url = '';
		foreach ($conf->file->dol_document_root as $dirroot)
		{
		    if (file_exists($dirroot."/".$val."/img/".$val.".png"))
			{
			    $url = dol_buildpath('/'.$val.'/img/'.$val.'.png', 1);
			    $found = 1;
			    break;
			}
		}
		// Img file not found
		if (!$found)
		{
		    if (!defined('DISABLE_FONT_AWSOME')) {
		        print "/* A mainmenu entry was found but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one */\n";
		        print 'div.mainmenu.'.$val.'::before {
	                    content: "\f249";
	                }';
		    }
		    else
		    {
		    	print "/* A mainmenu entry was found but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one. */\n";
		    	print "/* Overwrite this definition in your own css with a different content to use your own font awesome icon. */\n";
		        $url = dol_buildpath($path.'/theme/'.$theme.'/img/menus/generic'.(min($generic, 4))."_over.png", 1);
		        print "div.mainmenu.".$val." {\n";
		        print "	background-image: url(".$url.");\n";
		        print "}\n";
	    	}
	    	$generic++;
		}
		else
		{
			print "div.mainmenu.".$val." {\n";
			print "	background-image: url(".$url.");\n";
			print "}\n";
		}
	}
	// End of part to add more div class css
}	// End test if $dol_hide_topmenu ?>

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
	font-size: 14px;
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
	margin-left: 18px;
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
    border: 2px solid #ccc;
    min-width: 220px;
    border-radius: 2px;
}
.login_table .tdinputlogin .fa {
	padding-left: 10px;
	width: 14px;
}

.login_main_home {
    word-break: break-word;
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


.atoplogin.dropdown .dropdown-menu {
	display: none;
}

div.login_block {
	border-right: 1px solid rgba(0,0,0,0.3);
    padding-top: 3px;
    padding-bottom: 3px;
	<?php print $left; ?>: 0;
	top: 0px;
<?php if (in_array($conf->browser->layout, array('phone', 'tablet')) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) { ?>
	position: absolute;
<?php } else { ?>
	position: fixed;
<?php } ?>
	z-index: 10;
	text-align: center;
	vertical-align: middle;
	background: rgb(<?php echo $colorbackvmenu1; ?>);
	width: 228px;
	height: 45px;
	<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
	display: none;
	<?php } ?>
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
	color: #<?php echo $colortextbackvmenu; ?>;
}
div.login a:hover {
	color: #<?php echo $colortextbackvmenu; ?>;
	text-decoration:underline;
}
div.login_block_user, div.login_block_other { clear: both; }
div.login_block_other { padding-top: 3px; }

.topnav div.login_block_user {
	display: inline-block;
    vertical-align: middle;
	line-height: <?php echo $disableimages ? '25' : '50'; ?>px;
	height: <?php echo $disableimages ? '25' : '50'; ?>px;
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

.login_block_elem {
	float: right;
	vertical-align: top;
	padding: 0px 0px 0px 2px !important;
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
	color: #<?php echo $colortextbackvmenu; ?> !important;
}
.alogin, .alogin:hover {
	color: #888 !important;
	font-weight: normal !important;
	font-size: <?php echo $fontsizesmaller; ?>px !important;
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
	border-radius: 8px;
	width: 16px;
	height: 16px;
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
img.userphotosmall {			/* size for user photo in lists */
	border-radius: 0.6em;
	width: 1.2em;
    height: 1.2em;
    background-size: contain;
    vertical-align: middle;
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
    float: left;
    padding: 0px;
    padding-bottom: 0px;
    padding-top: 0px;
    width: 222px;
}

.vmenu {
	margin-left: 4px;
	<?php if (GETPOST('optioncss', 'aZ09') == 'print') { ?>
    display: none;
	<?php } ?>
}

.vmenusearchselectcombo {
	width: 202px;
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

a.vmenu:link, a.vmenu:visited, a.vmenu:hover, a.vmenu:active, span.vmenu, span.vsmenu { white-space: nowrap; font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; }
font.vmenudisabled  { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; color: #aaa; margin-left: 4px; }
a.vmenu:link, a.vmenu:visited { color: #<?php echo $colortextbackvmenu; ?>; }

a.vsmenu:link, a.vsmenu:visited, a.vsmenu:hover, a.vsmenu:active, span.vsmenu { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #202020; margin: 1px 1px 1px 8px; }
font.vsmenudisabled { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #aaa; }
a.vsmenu:link, a.vsmenu:visited { color: #<?php echo $colortextbackvmenu; ?>; white-space: nowrap; }
font.vsmenudisabledmargin { margin: 1px 1px 1px 8px; }

a.help:link, a.help:visited, a.help:hover, a.help:active, span.help { text-align: <?php print $left; ?>; font-weight: normal; color: #999; text-decoration: none; }

div.blockvmenulogo
{
	border-bottom: 0 !important;
}
.menulogocontainer {
    margin: <?php echo $disableimages ? '0' : '6'; ?>px;
    margin-left: 12px;
    margin-right: 6px;
    padding: 0;
    height: <?php echo $disableimages ? '20' : '32'; ?>px;
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
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding-left: 5px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 8px 0px;

    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
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
/*	border-top: 1px solid #e0e0e0; */
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
    color: #<?php echo $colortextbacktab; ?>;
    padding-top: 16px;
    padding-left: 16px;
    padding-right: 16px;
	padding-bottom: 16px;
    margin: 0px 0px 16px 0px;
    -webkit-border-radius: 3px;
	border-radius: 3px;
    border-right: 1px solid #BBB;
    border-left: 1px solid #BBB;
    border-top: 1px solid #CCC;
	width: auto;
	background: rgb(<?php echo $colorbacktabcard1; ?>);
	border-bottom: 1px solid #aaa;
}

div.tabBar tr.titre td {
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

a.tab:link, a.tab:visited, a.tab:hover, a.tab#active {
	font-family: <?php print $fontlist ?>;
	padding: 12px 13px 12px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
	background-image: none !important;
}


.tabunactive {	/* We add some border on tabunactive to avoid change of position of title when switching tabs (border of tabunactive = border of tabactive) */
    border-right: 1px solid rgb(<?php echo $colorbackbody; ?>);
    border-left: 1px solid rgb(<?php echo $colorbackbody; ?>);
}

.tabactive, a.tab#active {
	color: #<?php echo $colortextbacktab; ?> !important;
	background: rgb(<?php echo $colorbacktabcard1; ?>) !important;

	border-right: 1px solid #AAA !important;
	border-left: 1px solid #AAA !important;
	border-top: 2px solid #111 !important;
}
a.tab:hover
{
	/*
	background: rgba(<?php echo $colorbacktabcard1; ?>, 0.5)  url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/nav-overlay3.png', 1); ?>) 50% 0 repeat-x;
	color: #<?php echo $colortextbacktab; ?>;
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
	margin-top: <?php echo ($dol_hide_topmenu ? '6' : '0'); ?>px;
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
.nopaddingleft {
	padding-<?php print $left; ?>: 0px;
}
.nopaddingright {
	padding-<?php print $right; ?>: 0px;
}
.notopnoleft {
	border-collapse: collapse;
	border: 0px;
	padding-top: 0px;
	padding-<?php print $left; ?>: 0px;
	padding-<?php print $right; ?>: 16px;
	padding-bottom: 4px;
	margin-right: 0px 0px;
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
	border: 1px solid #f4f4f4;
	border-collapse: collapse !important;
	padding: 1px 2px 1px 3px;			/* t r b l */
}
table.borderplus {
	border: 1px solid #BBB;
}

.border tbody tr, .bordernooddeven tbody tr, .border tbody tr td, .bordernooddeven tbody tr td, div.tabBar table.border tr, div.tabBar table.border tr td, div.tabBar div.border .table-border-row, div.tabBar div.border .table-key-border-col, div.tabBar div.border .table-val-border-col {
	height: 26px;
}
tr.liste_titre.box_titre td table td, .bordernooddeven tr td {
    height: 26px;
}

table.border td, table.bordernooddeven td, div.border div div.tagtd {
	padding: 2px 4px 2px 4px;
	border: 1px solid #f0f0f0;
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


/* Main boxes */
.nobordertop, .nobordertop tr:first-of-type td {
    border-top: none !important;
}
.noborderbottom, .noborderbottom tr:last-of-type td {
    border-bottom: none !important;
}
.bordertop {
	border-top: 1px solid rgb(<?php echo $colortopbordertitle1 ?>);
}
.borderbottom {
	border-bottom: 1px solid rgb(<?php echo $colortopbordertitle1 ?>);
}

.fichehalfright table.noborder {
	margin: 0px 0px 0px 0px;
}
div.colorback
{
	background: rgb(<?php echo $colorbacktitle1; ?>);
	padding: 10px;
	margin-top: 5px;
}
.liste_titre_bydiv {
    border-right: 1px solid #ccc;
    border-left: 1px solid #ccc;
}
table.liste, table.noborder, table.formdoc, div.noborder {
	width: calc(100% - 1px);	/* -1 to fix a bug. Without, a scroll appears due to overflow-x: auto; of div-table-responsive */

	border-collapse: separate !important;
	border-spacing: 0px;

	border-top-width: <?php echo $borderwidth ?>px;
	border-top-color: rgb(<?php echo $colortopbordertitle1 ?>);
	border-top-style: solid;

	border-bottom-width: 1px;
	border-bottom-color: #BBB;
	border-bottom-style: solid;

    border-right: 1px solid #ccc;
    border-left: 1px solid #ccc;

	margin: 0px 0px 8px 0px;

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

.liste_titre_filter {
	background: rgb(<?php echo $colorbacktitle1; ?>) !important;
}
tr.liste_titre_filter td.liste_titre {
	padding-top: 4px;
	padding-bottom: 3px;
}
.liste_titre_create td, .liste_titre_create th, .liste_titre_create .tagtd
{
    /*border-top-width: 1px;
    border-top-color: rgb(<?php echo $colortopbordertitle1 ?>);
    border-top-style: solid;*/
}
.liste_titre_add td, .liste_titre_add th, .liste_titre_add .tagtd
{
    border-top-width: 2px;
    border-top-color: rgb(<?php echo $colortopbordertitle1 ?>);
    border-top-style: solid;
}
.liste_titre_add td, .liste_titre_add .tagtd
{
    border-top-width: 1px;
    border-top-color: rgb(<?php echo $colortopbordertitle1 ?>);
    border-top-style: solid;
}

table.liste th, table.noborder th, table.noborder tr.liste_titre td {
	padding: 8px 6px 8px 6px;			/* t r b l */
}
table.noborder td, div.noborder form, div.noborder form div, table.tableforservicepart1 td, table.tableforservicepart2 td {
	padding: 4px 6px 4px 6px;			/* t r b l */
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

/* For table with no filter before */
table.listwithfilterbefore {
	border-top: none !important;
}

.tagtable, .table-border { display: table; }
.tagtr, .table-border-row  { display: table-row; }
.tagtd, .table-border-col, .table-key-border-col, .table-val-border-col { display: table-cell; }
.confirmquestions .tagtr .tagtd:not(:first-child)  { padding-left: 10px; }


/* Pagination */
div.refidpadding  {
	padding-top: 3px;
}
div.refid  {
	font-weight: bold;
	color: rgb(<?php print $colortexttitlenotab; ?>);
  	font-size: 160%;
}
div.refidno  {
	padding-top: 8px;
	font-weight: normal;
  	color: #444;
  	font-size: <?php print $fontsize ?>px;
  	line-height: 21px;
}
div.refidno form {
    display: inline-block;
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
  padding-top: 6px;
  padding-bottom: 5px;
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
  color: #000;
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
  background-color: rgb(<?php echo $colorbackhmenu1 ?>);
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


/* Prepare to remove class pair - impair
.noborder > tbody > tr:nth-child(even) td {
	background: linear-gradient(to bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
	min-height: 18px;
}

.noborder > tbody > tr:nth-child(odd) td {
	background: linear-gradient(to bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
}
*/

ul.noborder li:nth-child(odd):not(.liste_titre) {
	background-color: rgb(<?php echo $colorbacklinepair2; ?>) !important;
	background-color: rgb(<?php echo $colorbacklinepair2; ?>) !important;
	background-color: rgb(<?php echo $colorbacklinepair2; ?>) !important;
	background-color: rgb(<?php echo $colorbacklinepair2; ?>) !important;
	background-color: rgb(<?php echo $colorbacklinepair2; ?>) !important;
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

.oddeven:hover, .evenodd:hover, .impair:hover, .pair:hover
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

.oddeven, .evenodd, .impair, .nohover .impair:hover, tr.impair td.nohover, .tagtr.oddeven
{
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
}
.impair, .nohover .impair:hover, tr.impair td.nohover
{
	background: var(--colorbacklineimpair1);
}
#GanttChartDIV {
	background-color: var(--colorbacklineimpair1);
}

.oddeven, .evenodd, .pair, .nohover .pair:hover, tr.pair td.nohover, .tagtr.oddeven {
	font-family: <?php print $fontlist ?>;
	margin-bottom: 1px;
	color: #202020;
}
.pair, .nohover .pair:hover, tr.pair td.nohover {
	background-color: var(--colorbacklinepair1);
}

table.dataTable tr.oddeven {
	background-color: var(--colorbacklinepair1) !important;
}

/* For no hover style */
td.oddeven, table.nohover tr.impair, table.nohover tr.pair, table.nohover tr.impair td, table.nohover tr.pair td, tr.nohover td, form.nohover, form.nohover:hover {
	background-color: var(--colorbacklineimpair1) !important; !important;
	background: var(--colorbacklineimpair1) !important; !important;
}
td.evenodd, tr.nohoverpair td, #trlinefordates td {
	background-color: var(--colorbacklinepair1) !important; !important;
	background: var(--colorbacklinepair1) !important; !important;
}
.trforbreak td {
	font-weight: bold;
    border-bottom: 1pt solid black !important;
	background-color: var(--colorbacklinebreak) !important;
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
	background-color: #fff;
	border-bottom: 0px !important;
}
*/
div.liste_titre .tagtd {
	vertical-align: middle;
}
div.liste_titre {
	min-height: 26px !important;	/* We cant use height because it's a div and it should be higher if content is more. but min-height doe not work either for div */

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
	border-top-width: <?php echo $borderwidth ?>px;
    border-top-color: rgb(<?php echo $colortopbordertitle1 ?>);
    border-top-style: solid;

	box-shadow: none;
	border-collapse: collapse;
	display: table;
	padding: 2px 0px 2px 0;
	width: calc(100% - 1px);
}
tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel, table.dataTable.tr, tagtr.liste_titre
{
	height: 26px !important;
}
div.liste_titre_bydiv, .liste_titre div.tagtr, tr.liste_titre, tr.liste_titre_sel, .tagtr.liste_titre, .tagtr.liste_titre_sel, form.liste_titre, form.liste_titre_sel, table.dataTable thead tr
{
	background: rgb(<?php echo $colorbacktitle1; ?>);
	font-weight: <?php echo $useboldtitle ? 'bold' : 'normal'; ?>;
    border-bottom: 1px solid #FDFFFF;

    color: rgb(<?php echo $colortexttitle; ?>);
    font-family: <?php print $fontlist ?>;
    text-align: <?php echo $left; ?>;
}
tr.liste_titre th, tr.liste_titre td, th.liste_titre
{
	border-bottom: 1px solid #aaa;
}
/* TODO Once title line is moved under title search, make border bottom of all th black and force to whit when it's first tr */
tr:first-child th.liste_titre, tr:first-child th.liste_titre_sel {
    border-bottom: 1px solid #FFF ! important;
}
tr.liste_titre th, th.liste_titre, tr.liste_titre td, td.liste_titre, form.liste_titre div, div.liste_titre
{
    font-family: <?php print $fontlist ?>;
    font-weight: <?php echo $useboldtitle ? 'bold' : 'normal'; ?>;
    vertical-align: middle;
    height: 24px;
}
tr.liste_titre th a, th.liste_titre a, tr.liste_titre td a, td.liste_titre a, form.liste_titre div a, div.liste_titre a {
	text-shadow: none !important;
	color: rgb(<?php echo $colortexttitlelink ?>);
}
tr.liste_titre_topborder td {
	border-top-width: <?php echo $borderwidth; ?>px;
    border-top-color: rgb(<?php echo $colortopbordertitle1 ?>);
    border-top-style: solid;
}
.liste_titre td a {
	text-shadow: none !important;
	color: rgb(<?php echo $colortexttitle; ?>);
}
.liste_titre td a.notasortlink {
	color: rgb(<?php echo $colortextlink; ?>);
}
.liste_titre td a.notasortlink:hover {
	background: transparent;
}
tr.liste_titre:last-child th.liste_titre, tr.liste_titre:last-child th.liste_titre_sel, tr.liste_titre td.liste_titre, tr.liste_titre td.liste_titre_sel, form.liste_titre div.tagtd {				/* For last line of table headers only */
    border-bottom: 1px solid rgb(<?php echo $colortopbordertitle1 ?>);
}

div.liste_titre {
	padding-left: 3px;
}
tr.liste_titre_sel th, th.liste_titre_sel, tr.liste_titre_sel td, td.liste_titre_sel, form.liste_titre_sel div
{
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    border-bottom: 1px solid #FDFFFF;
    text-decoration: underline;
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
	border-top-width: <?php echo $borderwidth ?>px !important;
	border-top-color: rgb(<?php echo $colortopbordertitle1 ?>) !important;
	border-top-style: solid !important;
	margin: 0px 0px 0px 0px !important;
}
.paymenttable tr td:first-child, .margintable tr td:first-child
{
	padding-left: 2px;
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

div.tabBar .noborder {
	-webkit-box-shadow: 0px 0px 0px #f4f4f4 !important;
	box-shadow: 0px 0px 0px #f4f4f4 !important;
}
div .tdtop {
    vertical-align: top !important;
	padding-top: 5px !important;
	padding-bottom: 0px !important;
}

#tablelines tr.liste_titre td, .paymenttable tr.liste_titre td, .margintable tr.liste_titre td, .tableforservicepart1 tr.liste_titre td {
	border-bottom: 1px solid #AAA !important;
}
#tablelines tr td {
    height: unset;
}


/* Prepare to remove class pair - impair */

.noborder > tbody > tr:nth-child(even):not(.liste_titre), .liste > tbody > tr:nth-child(even):not(.liste_titre),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright):not(.ficheaddleft) > .border > tbody > tr:nth-of-type(even):not(.liste_titre), .liste > tbody > tr:nth-of-type(even):not(.liste_titre),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright):not(.ficheaddleft) .oddeven.tagtr:nth-of-type(even):not(.liste_titre)
{
	background: linear-gradient(to bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
	background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
}
.noborder > tbody > tr:nth-child(even):not(:last-child) td:not(.liste_titre), .liste > tbody > tr:nth-child(even):not(:last-child) td:not(.liste_titre),
.noborder .tagtr:nth-child(even):not(:last-child) .oddeven.tagtd:not(.liste_titre)
{
	border-bottom: 1px solid #ddd;
}

.noborder > tbody > tr:nth-child(odd):not(.liste_titre), .liste > tbody > tr:nth-child(odd):not(.liste_titre),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright):not(.ficheaddleft) > .border > tbody > tr:nth-of-type(odd):not(.liste_titre), .liste > tbody > tr:nth-of-type(odd):not(.liste_titre),
div:not(.fichecenter):not(.fichehalfleft):not(.fichehalfright):not(.ficheaddleft) .oddeven.tagtr:nth-of-type(odd):not(.liste_titre)
{
	background: linear-gradient(to bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
	background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
}
.noborder > tbody > tr:nth-child(odd):not(:last-child) td:not(.liste_titre), .liste > tbody > tr:nth-child(odd):not(:last-child) td:not(.liste_titre),
.noborder .tagtr:nth-child(odd):not(:last-child) .oddeven.tagtd:not(.liste_titre)
{
	border-bottom: 1px solid #ddd;
}

ul.noborder li:nth-child(even):not(.liste_titre) {
	background-color: rgb(<?php echo $colorbacklinepair2; ?>) !important;
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
.ficheaddleft div.boxstats, .ficheaddright div.boxstats {
    border: none;
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
@media only screen and (max-width: 767px)
{
	div.tabs {
		padding-left: 0 !important;
		margin-left: 0 !important;
		margin-right: 0 !important;
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
	font-color: rgb(<?php print $colortextlink; ?>);
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
.boxtable {
    margin-bottom: 8px !important;
    border-bottom-width: 1px;
}
.boxtable {
    margin-bottom: 8px !important;
    border-bottom-width: 1px;
}
.boxtablenotop {
    /* border-top-width: 0 !important; */
}
.boxtablenobottom {
    /* border-bottom-width: 0 !important; */
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

tr.box_titre {
    height: 26px !important;

    /* TO MATCH BOOTSTRAP */
	/*background: #ddd;
	color: #000 !important; */

	/* TO MATCH ELDY */
	background: rgb(<?php echo $colorbacktitle1; ?>);

    background-repeat: repeat-x;
    color: rgb(<?php echo $colortexttitle; ?>);
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
.green   { color: #118822; }

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
}

/* Warning message */
div.warning {
    border-<?php print $left; ?>: solid 5px #f2cf87;
	padding-top: 8px;
	padding-left: 10px;
	padding-right: 4px;
	padding-bottom: 8px;
	margin: 0.5em 0em 0.5em 0em;
    background: #fcf8e3;
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

.widthpictotitle { width: 40px; font-size: 1.4em; text-align: <?php echo $left; ?>; }
table.titlemodulehelp tr td img.widthpictotitle { width: 80px; }

.dolgraphtitle { margin-top: 6px; margin-bottom: 4px; }
.dolgraphtitlecssboxes { /* margin: 0px; */ }
.dolgraphchart canvas { width: calc(100% - 20px) !important; }
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
	margin-bottom: 2px;
	margin-top: 2px;
}
div.divphotoref > a > .photowithmargin {		/* Margin right for photo not inside a div.photoref frame only */
    margin-right: 15px;
}
.photowithborder {
	border: 1px solid #f0f0f0;
}
.photointoolitp {
	margin-top: 8px;
	margin-bottom: 6px;
	text-align: center;
}
.photodelete {
	margin-top: 6px !important;
}

.logo_setup
{
	content:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/logo_setup.svg', 1) ?>);	/* content is used to best fit the container */
	display: inline-block;
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
	/* text-shadow: 1px 1px 2px #FFFFFF; */
	<?php print (empty($conf->dol_optimize_smallscreen) ? '' : 'margin-top: 4px;'); ?>
}
.secondary, div.titre {
	color: var(--colortexttitlenotab);
}
.tertiary {
	color: var(--colortexttitlenotab);
}

table.centpercent.notopnoleftnoright.table-fiche-title {
	margin-bottom: 10px !important;
}
table.table-fiche-title .col-title div.titre{
	line-height: 40px;
}

div.backgreypublicpayment { background-color: #f0f0f0; padding: 20px; border-bottom: 1px solid #ddd; }
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
#dolpaymenttable { min-width: 320px; font-size: 16px; }	/* Width must have min to make stripe input area visible. Lower than 320 makes input area crazy for credit card that need zip code */
#tablepublicpayment { border: 1px solid #CCCCCC !important; width: 100%; padding: 20px; }
#tablepublicpayment .CTableRow1  { background-color: #F0F0F0 !important; }
#tablepublicpayment tr.liste_total { border-bottom: 1px solid #CCCCCC !important; }
#tablepublicpayment tr.liste_total td { border-top: none; }

.divmainbodylarge { margin-left: 40px; margin-right: 40px; }
#divsubscribe { max-width: 900px; }
#tablesubscribe { width: 100%; }

div#card-element {
    border: 1px solid #ccc;
}
div#card-errors {
	color: #fa755a;
    text-align: center;
    padding-top: 3px;
    max-width: 320px;
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
/* Formulaire confirmation (When Ajax JQuery is used)                             */
/* ============================================================================== */

.ui-dialog-titlebar {
}
.ui-dialog-content {
    font-size: <?php print $fontsize; ?>px !important;
}

.ui-dialog.ui-corner-all.ui-widget.ui-widget-content.ui-front.ui-dialog-buttons.ui-draggable {
	z-index: 1002 !important;		/* Default 101 with jquery, top menu have a z-index of 1000 */
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
	max-width: <?php print dol_size(600, 'width'); ?>px !important;
}

.mytooltip {
	width: <?php print dol_size(450, 'width'); ?>px;
	border-top: solid 1px #BBBBBB;
	border-<?php print $left; ?>: solid 1px #BBBBBB;
	border-<?php print $right; ?>: solid 1px #444444;
	border-bottom: solid 1px #444444;
	padding: 5px 20px;
	border-radius: 0;
	box-shadow: 0 0 4px grey;
	margin: 2px;
	font-stretch: condensed;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */

.ui-datepicker-calendar .ui-state-default, .ui-datepicker-calendar .ui-widget-content .ui-state-default,
.ui-datepicker-calendar .ui-widget-header .ui-state-default, .ui-datepicker-calendar .ui-button,
html .ui-datepicker-calendar .ui-button.ui-state-disabled:hover, html .ui-button.ui-state-disabled:active
{
    border: unset;
}

img.datecallink { padding-left: 2px !important; padding-right: 2px !important; }

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
.datenowlink
{
	color: rgb(<?php print $colortextlink; ?>);
}

.categtextwhite, .treeview .categtextwhite.hover {
	color: #fff !important;
}
.categtextblack {
	color: #000 !important;
}


/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

div.visible {
    display: block;
}

div.hidden, td.hidden, img.hidden, span.hidden {
    display: none;
}

tr.visible {
    display: block;
}


/* ============================================================================== */
/*  Module website                                                                */
/* ============================================================================== */

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
.bordertransp {
    background-color: transparent;
    background-image: none;
    border: 1px solid #aaa;
	font-weight: normal;
	color: #444 !important;
}
.websitebar {
	border-bottom: 1px solid #ccc;
	background: #eee;
	display: inline-block;
    padding: 4px 0 4px 0;
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
	padding-left: 10px;
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
    line-height: 28px;
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
    padding-top: 8px;
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
.cal_other_month   { /* opacity: 0.6; */ background: #EAEAEA; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
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
table.cal_event    { border: none; border-collapse: collapse; margin-bottom: 1px; -webkit-border-radius: 3px; border-radius: 3px; min-height: 20px;	}
table.cal_event td { border: none; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event td.cal_event { padding: 4px 4px !important; }
table.cal_event td.cal_event_right { padding: 4px 4px !important; }
.cal_event              { font-size: 1em; }
.cal_event a:link       { color: #111111; font-weight: normal !important; }
.cal_event a:visited    { color: #111111; font-weight: normal !important; }
.cal_event a:active     { color: #111111; font-weight: normal !important; }
.cal_event_busy a:hover { color: #111111; font-weight: normal !important; color:rgba(255,255,255,.75); }
.cal_event_busy      { }
.cal_peruserviewname { max-width: 140px; height: 22px; }

.calendarviewcontainertr { height: 100px; }

.topmenuimage {
	background-size: 24px auto;
}

td.cal_other_month {
	opacity: 0.8;
}


/* ============================================================================== */
/*  Ajax - Liste deroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-widget-content { border: solid 1px rgba(0,0,0,.3); background: #fff !important; }

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
.treeview ul { background-color: transparent !important; margin-top: 0; }
.treeview li { background-color: transparent !important; padding: 0 0 0 16px !important; min-height: 20px; }
.treeview .hover { color: rgb(<?php print $colortextlink; ?>) !important; text-decoration: underline !important; }



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


/* Style to overwrites JQuery styles */
.ui-state-highlight, .ui-widget-content .ui-state-highlight, .ui-widget-header .ui-state-highlight {
    border: 1px solid #888;
    background: rgb(<?php echo $colorbacktitle1; ?>);
    color: unset;
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
.ui-button { margin-left: -2px; <?php print (preg_match('/chrome/', $conf->browser->name) ? 'padding-top: 1px;' : ''); ?> }
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
	vertical-align: bottom ! important;
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
	width: <?php print isset($_SESSION['dol_screenwidth']) ?max($_SESSION['dol_screenwidth'] - 830, 450) : '450'; ?>px !important;
}

.gtaskname div, .gtaskname {
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
    width: calc(100% - 390px);
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
<?php if (!empty($conf->global->MAIN_JQUERY_JNOTIFY_BOTTOM)) { ?>
	top: auto !important;
	bottom: 4px !important;
<?php } ?>
	text-align: center;
	min-width: <?php echo $dol_optimize_smallscreen ? '200' : '480'; ?>px;
	width: auto;
	max-width: 1024px;
	padding-left: 10px !important;
	padding-right: 10px !important;
	word-wrap: break-word;
}
.jnotify-container .jnotify-notification .jnotify-message {
	font-weight: normal;
}
.jnotify-container .jnotify-notification-warning .jnotify-close, .jnotify-container .jnotify-notification-warning .jnotify-message {
    color: #a28918 !important;
}

/* use or not ? */
div.jnotify-background {
	opacity : 0.95 !important;
    -webkit-box-shadow: 2px 2px 4px #888 !important;
    box-shadow: 2px 2px 4px #888 !important;
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

.blockvmenusearch .select2-container--default .select2-selection--single,
.blockvmenubookmarks .select2-container--default .select2-selection--single
{
    background-color: unset;
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
  padding: 2px 5px 1px 5px;
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
  float: left;
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


/* ============================================================================== */
/*  For categories                                                                */
/* ============================================================================== */

.noborderoncategories {
	border: none !important;
	border-radius: 5px !important;
	box-shadow: none;
	-webkit-box-shadow: none !important;
    box-shadow: none !important;
}
span.noborderoncategories a, li.noborderoncategories a {
	line-height: normal;
}
span.noborderoncategories {
	padding: 3px 5px 0px 5px;
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
  border-color: #444 transparent transparent transparent;
  margin-left: 0.4em;
}

.multi-select-container--open .multi-select-menu { display: block; }

.multi-select-container--open .multi-select-button:after {
  border-width: 0 0.4em 0.4em 0.4em;
  border-color: transparent transparent #999 transparent;
}

.multi-select-menuitem {
    clear: both;
    float: left;
    padding-left: 5px
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
    padding-bottom: 3px;
}
.searchpage .tagtr .tagtd .button {
	background: unset;
    border: unset;
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

/* Warning: setting this may make screen not beeing refreshed after a combo selection */
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
  background-image: -ms-linear-gradient(bottom, rgba(0,0,0,0.1) 0%, rgba(230,230,230,0.4) 100%);
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
/* CSS style used for jFlot                                                       */
/* ============================================================================== */

.dol-xaxis-vertical .flot-x-axis .flot-tick-label.tickLabel {
    text-orientation: sideways;
    font-weight: 400;
    writing-mode: vertical-rl;
    white-space: nowrap;
}


/* ============================================================================== */
/* CSS style used for small screen                                                */
/* ============================================================================== */

.imgopensurveywizard
{
	padding: 0 4px 0 4px;
}
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

	.box-flex-container {
	    margin: 0 0 0 -8px !important;
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

/* nboftopmenuentries = <?php echo $nbtopmenuentries ?>, fontsize=<?php echo $fontsize ?> */
/* disableimages = <?php echo $disableimages; ?> */
/* rule to reduce top menu - 1st reduction */
@media only screen and (max-width:  <?php echo round($nbtopmenuentries * $fontsize * 7, 0) + 300; ?>px)
{
	div.tmenucenter {
	    max-width: <?php echo round($fontsize * 4); ?>px;	/* size of viewport */
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
/* rule to reduce top menu - 2nd reduction */
@media only screen and (max-width: <?php echo round($nbtopmenuentries * $fontsize * 4.5, 0) + 300; ?>px)
{
	li.tmenucompanylogo {
		display: none;
	}

	div.tmenucenter {
	    max-width: <?php echo round($fontsize * 2); ?>px;	/* size of viewport */
  		text-overflow: clip;
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
    	border: none;
    	-webkit-box-shadow: none;
        box-shadow: none;
        padding: 4px;
    	height: 20px;
    	width: 20px;
        object-fit: contain;
    }

	.titlefield {
		width: auto !important;		/* We want to ignore the 30%, try to use more if you can */
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
}


<?php
include dol_buildpath($path.'/theme/'.$theme.'/dropdown.inc.php', 0);
include dol_buildpath($path.'/theme/'.$theme.'/info-box.inc.php', 0);
include dol_buildpath($path.'/theme/'.$theme.'/progress.inc.php', 0);
include dol_buildpath($path.'/theme/eldy/timeline.inc.php', 0); // actually md use same style as eldy theme

if (!empty($conf->global->THEME_CUSTOM_CSS)) print $conf->global->THEME_CUSTOM_CSS;

if (is_object($db)) $db->close();
?>

/* This must be at end */
::-webkit-scrollbar {
	width: 12px;
}
::-webkit-scrollbar-button {
	background: #aaa;
}
::-webkit-scrollbar-track-piece {
	background: #fff;
}
::-webkit-scrollbar-thumb {
	background: #ddd;
}​

div#topmenu-bookmark-dropdown {
position: fixed;
right: 20px;
}
