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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/eldy/style.css.php
 *		\brief      File for CSS style sheet Eldy
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (! defined('NOLOGIN'))         define('NOLOGIN', 1);          // File must be accessed by logon page so without login
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', 1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');


define('ISLOADEDBYSTEELSHEET', '1');


require __DIR__ . '/theme_vars.inc.php';
if (defined('THEME_ONLY_CONSTANT')) return;


session_cache_limiter('public');

require_once __DIR__.'/../../main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
// and permission, so we can later calculate number of top menu ($nbtopmenuentries) according to user profile.
if (empty($user->id) && ! empty($_SESSION['dol_login']))
{
    $user->fetch('', $_SESSION['dol_login'], '', 1);
    $user->getrights();
}


// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=10800, public, must-revalidate');
else header('Cache-Control: no-cache');

if (GETPOST('theme', 'alpha')) $conf->theme=GETPOST('theme', 'alpha');  // If theme was forced on URL
if (GETPOST('lang', 'aZ09')) $langs->setDefaultLang(GETPOST('lang', 'aZ09'));	// If language was forced on URL

$langs->load("main", 0, 1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');

$path='';    	// This value may be used in future for external module to overwrite theme
$theme='eldy';	// Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define image path files and other constants
$fontlist='roboto,arial,tahoma,verdana,helvetica';    //$fontlist='helvetica, verdana, arial, sans-serif';
//$fontlist='"open sans", "Helvetica Neue", Helvetica, Arial, sans-serif;';
$img_head='';
$img_button=dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png', 1);
$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_hide_leftmenu=$conf->dol_hide_leftmenu;
$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;
$dol_no_mouse_hover=$conf->dol_no_mouse_hover;

//$conf->global->THEME_ELDY_ENABLE_PERSONALIZED=0;
//$user->conf->THEME_ELDY_ENABLE_PERSONALIZED=0;
//var_dump($user->conf->THEME_ELDY_RGB);

$useboldtitle=(isset($conf->global->THEME_ELDY_USEBOLDTITLE)?$conf->global->THEME_ELDY_USEBOLDTITLE:0);
$borderwidth=1;

// Case of option always editable
if (! isset($conf->global->THEME_ELDY_BACKBODY)) $conf->global->THEME_ELDY_BACKBODY=$colorbackbody;
if (! isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) $conf->global->THEME_ELDY_TOPMENU_BACK1=$colorbackhmenu1;
if (! isset($conf->global->THEME_ELDY_VERMENU_BACK1)) $conf->global->THEME_ELDY_VERMENU_BACK1=$colorbackvmenu1;
if (! isset($conf->global->THEME_ELDY_BACKTITLE1)) $conf->global->THEME_ELDY_BACKTITLE1=$colorbacktitle1;
if (! isset($conf->global->THEME_ELDY_USE_HOVER)) $conf->global->THEME_ELDY_USE_HOVER=$colorbacklinepairhover;
if (! isset($conf->global->THEME_ELDY_USE_CHECKED)) $conf->global->THEME_ELDY_USE_CHECKED=$colorbacklinepairchecked;
if (! isset($conf->global->THEME_ELDY_LINEBREAK)) $conf->global->THEME_ELDY_LINEBREAK=$colorbacklinebreak;
if (! isset($conf->global->THEME_ELDY_TEXTTITLENOTAB)) $conf->global->THEME_ELDY_TEXTTITLENOTAB=$colortexttitlenotab;
if (! isset($conf->global->THEME_ELDY_TEXTLINK)) $conf->global->THEME_ELDY_TEXTLINK=$colortextlink;

// Case of option editable only if option THEME_ELDY_ENABLE_PERSONALIZED is on
if (empty($conf->global->THEME_ELDY_ENABLE_PERSONALIZED))
{
    $conf->global->THEME_ELDY_BACKTABCARD1='255,255,255';     // card
    $conf->global->THEME_ELDY_BACKTABACTIVE='234,234,234';
    $conf->global->THEME_ELDY_TEXT='0,0,0';
    $conf->global->THEME_ELDY_FONT_SIZE1='0.86em';
    $conf->global->THEME_ELDY_FONT_SIZE2='0.75em';
}

// Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
$colorbackhmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$conf->global->THEME_ELDY_TOPMENU_BACK1)   :(empty($user->conf->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackvmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$conf->global->THEME_ELDY_VERMENU_BACK1)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$user->conf->THEME_ELDY_VERMENU_BACK1);
$colortopbordertitle1=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPBORDER_TITLE1)?$colortopbordertitle1:$conf->global->THEME_ELDY_TOPBORDER_TITLE1)   :(empty($user->conf->THEME_ELDY_TOPBORDER_TITLE1)?$colortopbordertitle1:$user->conf->THEME_ELDY_TOPBORDER_TITLE1);
$colorbacktitle1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTITLE1)   ?$colorbacktitle1:$conf->global->THEME_ELDY_BACKTITLE1)      :(empty($user->conf->THEME_ELDY_BACKTITLE1)?$colorbacktitle1:$user->conf->THEME_ELDY_BACKTITLE1);
$colorbacktabcard1   =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABCARD1) ?$colorbacktabcard1:$conf->global->THEME_ELDY_BACKTABCARD1)  :(empty($user->conf->THEME_ELDY_BACKTABCARD1)?$colorbacktabcard1:$user->conf->THEME_ELDY_BACKTABCARD1);
$colorbacktabactive  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$conf->global->THEME_ELDY_BACKTABACTIVE):(empty($user->conf->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$user->conf->THEME_ELDY_BACKTABACTIVE);
$colorbacklineimpair1=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR1)  ?$colorbacklineimpair1:$conf->global->THEME_ELDY_LINEIMPAIR1):(empty($user->conf->THEME_ELDY_LINEIMPAIR1)?$colorbacklineimpair1:$user->conf->THEME_ELDY_LINEIMPAIR1);
$colorbacklineimpair2=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR2)  ?$colorbacklineimpair2:$conf->global->THEME_ELDY_LINEIMPAIR2):(empty($user->conf->THEME_ELDY_LINEIMPAIR2)?$colorbacklineimpair2:$user->conf->THEME_ELDY_LINEIMPAIR2);
$colorbacklinepair1  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR1)    ?$colorbacklinepair1:$conf->global->THEME_ELDY_LINEPAIR1)    :(empty($user->conf->THEME_ELDY_LINEPAIR1)?$colorbacklinepair1:$user->conf->THEME_ELDY_LINEPAIR1);
$colorbacklinepair2  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR2)    ?$colorbacklinepair2:$conf->global->THEME_ELDY_LINEPAIR2)    :(empty($user->conf->THEME_ELDY_LINEPAIR2)?$colorbacklinepair2:$user->conf->THEME_ELDY_LINEPAIR2);
$colorbacklinebreak  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEBREAK)    ?$colorbacklinebreak:$conf->global->THEME_ELDY_LINEBREAK)    :(empty($user->conf->THEME_ELDY_LINEBREAK)?$colorbacklinebreak:$user->conf->THEME_ELDY_LINEBREAK);
$colorbackbody       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKBODY)     ?$colorbackbody:$conf->global->THEME_ELDY_BACKBODY)          :(empty($user->conf->THEME_ELDY_BACKBODY)?$colorbackbody:$user->conf->THEME_ELDY_BACKBODY);
$colortexttitlenotab =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXTTITLENOTAB)?$colortexttitlenotab:$conf->global->THEME_ELDY_TEXTTITLENOTAB)             :(empty($user->conf->THEME_ELDY_TEXTTITLENOTAB)?$colortexttitlenotab:$user->conf->THEME_ELDY_TEXTTITLENOTAB);
$colortexttitle      =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXTTITLE)    ?$colortexttitle:$conf->global->THEME_ELDY_TEXTTITLE)             :(empty($user->conf->THEME_ELDY_TEXTTITLE)?$colortexttitle:$user->conf->THEME_ELDY_TEXTTITLE);
$colortext           =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXT)         ?$colortext:$conf->global->THEME_ELDY_TEXT)                  :(empty($user->conf->THEME_ELDY_TEXT)?$colortext:$user->conf->THEME_ELDY_TEXT);
$colortextlink       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXTLINK)     ?$colortextlink:$conf->global->THEME_ELDY_TEXTLINK)              :(empty($user->conf->THEME_ELDY_TEXTLINK)?$colortextlink:$user->conf->THEME_ELDY_TEXTLINK);
$fontsize            =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE1)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE1)             :(empty($user->conf->THEME_ELDY_FONT_SIZE1)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE1);
$fontsizesmaller     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE2)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE2)             :(empty($user->conf->THEME_ELDY_FONT_SIZE2)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE2);

// Hover color
$colorbacklinepairhover=((! isset($conf->global->THEME_ELDY_USE_HOVER) || (string) $conf->global->THEME_ELDY_USE_HOVER === '255,255,255')?'':($conf->global->THEME_ELDY_USE_HOVER === '1'?'e6edf0':$conf->global->THEME_ELDY_USE_HOVER));
$colorbacklinepairchecked=((! isset($conf->global->THEME_ELDY_USE_CHECKED) || (string) $conf->global->THEME_ELDY_USE_CHECKED === '255,255,255')?'':($conf->global->THEME_ELDY_USE_CHECKED === '1'?'e6edf0':$conf->global->THEME_ELDY_USE_CHECKED));
if (! empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED))
{
    $colorbacklinepairhover=((! isset($user->conf->THEME_ELDY_USE_HOVER) || $user->conf->THEME_ELDY_USE_HOVER === '0')?'':($user->conf->THEME_ELDY_USE_HOVER === '1'?'e6edf0':$user->conf->THEME_ELDY_USE_HOVER));
    $colorbacklinepairchecked=((! isset($user->conf->THEME_ELDY_USE_CHECKED) || $user->conf->THEME_ELDY_USE_CHECKED === '0')?'':($user->conf->THEME_ELDY_USE_CHECKED === '1'?'e6edf0':$user->conf->THEME_ELDY_USE_CHECKED));
}


// Set text color to black or white
$colorbackhmenu1=join(',', colorStringToArray($colorbackhmenu1));    // Normalize value to 'x,y,z'
$tmppart=explode(',', $colorbackhmenu1);
$tmpval=(! empty($tmppart[0]) ? $tmppart[0] : 0)+(! empty($tmppart[1]) ? $tmppart[1] : 0)+(! empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) $colortextbackhmenu='FFFFFF';
else $colortextbackhmenu='000000';

$colorbackvmenu1=join(',', colorStringToArray($colorbackvmenu1));    // Normalize value to 'x,y,z'
$tmppart=explode(',', $colorbackvmenu1);
$tmpval=(! empty($tmppart[0]) ? $tmppart[0] : 0)+(! empty($tmppart[1]) ? $tmppart[1] : 0)+(! empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) { $colortextbackvmenu='FFFFFF'; }
else { $colortextbackvmenu='000000'; }

$colorbacktitle1=join(',', colorStringToArray($colorbacktitle1));    // Normalize value to 'x,y,z'
$tmppart=explode(',', $colorbacktitle1);
if ($colortexttitle == '')
{
    $tmpval=(! empty($tmppart[0]) ? $tmppart[0] : 0)+(! empty($tmppart[1]) ? $tmppart[1] : 0)+(! empty($tmppart[2]) ? $tmppart[2] : 0);
    if ($tmpval <= 460) { $colortexttitle='FFFFFF'; $colorshadowtitle='888888'; }
    else { $colortexttitle='000000'; $colorshadowtitle='FFFFFF'; }
}
else $colorshadowtitle='888888';

$colorbacktabcard1=join(',', colorStringToArray($colorbacktabcard1));    // Normalize value to 'x,y,z'
$tmppart=explode(',', $colorbacktabcard1);
$tmpval=(! empty($tmppart[0]) ? $tmppart[0] : 0)+(! empty($tmppart[1]) ? $tmppart[1] : 0)+(! empty($tmppart[2]) ? $tmppart[2] : 0);
if ($tmpval <= 460) { $colortextbacktab='FFFFFF'; }
else { $colortextbacktab='000000'; }


// Format color value to match expected format (may be 'FFFFFF' or '255,255,255')
$colorbackhmenu1=join(',', colorStringToArray($colorbackhmenu1));
$colorbackvmenu1=join(',', colorStringToArray($colorbackvmenu1));
$colorbacktitle1=join(',', colorStringToArray($colorbacktitle1));
$colorbacktabcard1=join(',', colorStringToArray($colorbacktabcard1));
$colorbacktabactive=join(',', colorStringToArray($colorbacktabactive));
$colorbacklineimpair1=join(',', colorStringToArray($colorbacklineimpair1));
$colorbacklineimpair2=join(',', colorStringToArray($colorbacklineimpair2));
$colorbacklinepair1=join(',', colorStringToArray($colorbacklinepair1));
$colorbacklinepair2=join(',', colorStringToArray($colorbacklinepair2));
if ($colorbacklinepairhover != '') $colorbacklinepairhover=join(',', colorStringToArray($colorbacklinepairhover));
if ($colorbacklinepairchecked != '') $colorbacklinepairchecked=join(',', colorStringToArray($colorbacklinepairchecked));
$colorbackbody=join(',', colorStringToArray($colorbackbody));
$colortexttitlenotab=join(',', colorStringToArray($colortexttitlenotab));
$colortexttitle=join(',', colorStringToArray($colortexttitle));
$colortext=join(',', colorStringToArray($colortext));
$colortextlink=join(',', colorStringToArray($colortextlink));

$nbtopmenuentries=$menumanager->showmenu('topnb');
if ($conf->browser->layout == 'phone') $nbtopmenuentries = max($nbtopmenuentries, 10);


$minwidthtmenu=66;		/* minimum width for one top menu entry */
$heightmenu=48;			/* height of top menu, part with image */
$heightmenu2=49;        /* height of top menu, part with login  */
$disableimages = 0;
$maxwidthloginblock = 180;
if (! empty($conf->global->THEME_TOPMENU_DISABLE_IMAGE)) { $disableimages = 1; $maxwidthloginblock = 180; $minwidthtmenu=0; }


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
print 'fontsizesmaller='.$fontsizesmaller;
print 'topMenuFontSize='.$topMenuFontSize."\n";
print 'toolTipBgColor='.$toolTipBgColor."\n";
print 'toolTipFontColor='.$toolTipFontColor."\n";
print '*/'."\n";


require __DIR__ . '/global.inc.php';


if (is_object($db)) $db->close();
