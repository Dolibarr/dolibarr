<?php
/* Copyright (C) 2004-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 *
 * Copyright (C) 2013-2014  Nicolas Rivera          <theme@creajutsu.com>
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
 *		\file       htdocs/theme/oblion/style.css.php
 *		\brief      File for CSS style sheet Oblion
 */
 

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);          // File must be accessed by logon page so without login
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

require_once '../../main.inc.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
if (empty($user->id) && ! empty($_SESSION['dol_login'])) $user->fetch('',$_SESSION['dol_login']);


// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));	// If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');
$fontsize=empty($conf->dol_optimize_smallscreen)?'12':'12';
$fontsizesmaller=empty($conf->dol_optimize_smallscreen)?'11':'11';

$path='';    	// This value may be used in future for external module to overwrite theme
$theme='oblyon';	// Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define Fonts //
$fontlist= '"Open Sans",Tahoma,Arial,Helvetica';    //initial: sans-serif';
$fontboxtitle= 'Oswald,Verdana,Arial,Helvetica';
//vertical menu
$fontvmenu= 'Verdana,Arial,Helvetica';
$fontvmenusearch= '"Open Sans",Verdana,Arial,Helvetica';
$fontvmenubookmarks= 'Verdana,Arial,Helvetica';
$fontvmenuhelp= 'Verdana,Arial,Helvetica';
// horizontal menu
$fonthmenu= '"Open Sans",Verdana,Arial,Helvetica';
$fonthmenu2= '"Open Sans",Verdana,Arial,Helvetica';


// Define image path files //
$img_background=dol_buildpath($path.'/theme/'.$theme.'/img/minimal-earth.jpg',1);
$img_head=dol_buildpath($path.'/theme/'.$theme.'/img/headbg2.jpg',1);
$img_button=dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1);

$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;
$dol_no_mouse_hover=$conf->dol_no_mouse_hover;
$dol_use_jmobile=$conf->dol_use_jmobile;


// Define reference colors
// Example: Light grey: $colred=235;$colgreen=235;$colblue=235;
// Example: Pink:       $colred=230;$colgreen=210;$colblue=230;
// Example: Green:      $colred=210;$colgreen=230;$colblue=210;
// Example: Ocean:      $colred=220;$colgreen=220;$colblue=240;
//$conf->global->THEME_ELDY_ENABLE_PERSONALIZED=0;
//$user->conf->THEME_ELDY_ENABLE_PERSONALIZED=0;
//var_dump($user->conf->THEME_ELDY_RGB);
$colred  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_RGB)?235:hexdec(substr($conf->global->THEME_ELDY_RGB,0,2))):(empty($user->conf->THEME_ELDY_RGB)?235:hexdec(substr($user->conf->THEME_ELDY_RGB,0,2)));
$colgreen=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_RGB)?235:hexdec(substr($conf->global->THEME_ELDY_RGB,2,2))):(empty($user->conf->THEME_ELDY_RGB)?235:hexdec(substr($user->conf->THEME_ELDY_RGB,2,2)));
$colblue =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_RGB)?235:hexdec(substr($conf->global->THEME_ELDY_RGB,4,2))):(empty($user->conf->THEME_ELDY_RGB)?235:hexdec(substr($user->conf->THEME_ELDY_RGB,4,2)));

// Colors
$isred=max(0,(2*$colred-$colgreen-$colblue)/2);        // 0 - 255
$isgreen=max(0,(2*$colgreen-$colred-$colblue)/2);      // 0 - 255
$isblue=max(0,(2*$colblue-$colred-$colgreen)/2);       // 0 - 255
$colorbackhmenu1=($colred-3).','.($colgreen-3).','.($colblue-3);         // topmenu
$colorbackhmenu2=($colred+5).','.($colgreen+5).','.($colblue+5);
$colorbackvmenu1=($colred+15).','.($colgreen+16).','.($colblue+17);      // vmenu
$colorbackvmenu1b=($colred+5).','.($colgreen+6).','.($colblue+7);        // vmenu (not menu)
$colorbackvmenu2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktitle1=($colred-5).','.($colgreen-5).','.($colblue-5);    // title of array
$colorbacktitle2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktabcard1=($colred+15).','.($colgreen+16).','.($colblue+17);  // card
$colorbacktabcard2=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacktabactive=($colred-15).','.($colgreen-15).','.($colblue-15);
$colorbacklineimpair1=(244+round($isred/3)).','.(244+round($isgreen/3)).','.(244+round($isblue/3));    // line impair
$colorbacklineimpair2=(250+round($isred/3)).','.(250+round($isgreen/3)).','.(250+round($isblue/3));    // line impair
$colorbacklineimpairhover=(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9));    // line impair
$colorbacklinepair1='255,255,255';    // line pair
$colorbacklinepair2='255,255,255';    // line pair
$colorbacklinepairhover=(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9)).','.(230+round(($isred+$isgreen+$isblue)/9));
//$colorbackbody='#ffffff url('.$img_head.') 0 0 no-repeat;';
$colorbackbody='#fcfcfc';
$colortext='40,40,40';
$fontsize=empty($conf->dol_optimize_smallscreen)?'12':'14';
$fontsizesmaller=empty($conf->dol_optimize_smallscreen)?'11':'14';

// Eldy colors
if (empty($conf->global->THEME_ELDY_ENABLE_PERSONALIZED))
{
    $conf->global->THEME_ELDY_TOPMENU_BACK1='140,160,185';    // topmenu
    $conf->global->THEME_ELDY_TOPMENU_BACK2='236,236,236';
    $conf->global->THEME_ELDY_VERMENU_BACK1='255,255,255';    // vmenu
    $conf->global->THEME_ELDY_VERMENU_BACK1b='230,232,232';   // vmenu (not menu)
    $conf->global->THEME_ELDY_VERMENU_BACK2='240,240,240';
    $conf->global->THEME_ELDY_BACKTITLE1='140,160,185';       // title of arrays
    $conf->global->THEME_ELDY_BACKTITLE2='230,230,230';
    $conf->global->THEME_ELDY_BACKTABCARD2='210,210,210';     // card
    $conf->global->THEME_ELDY_BACKTABCARD1='234,234,234';
    $conf->global->THEME_ELDY_BACKTABACTIVE='234,234,234';
    //$conf->global->THEME_ELDY_BACKBODY='#ffffff url('.$img_head.') 0 0 no-repeat;';
    $conf->global->THEME_ELDY_BACKBODY='#fcfcfc;';
    $conf->global->THEME_ELDY_LINEIMPAIR1='242,242,242';
    $conf->global->THEME_ELDY_LINEIMPAIR2='248,248,248';
    $conf->global->THEME_ELDY_LINEIMPAIRHOVER='238,246,252';
    $conf->global->THEME_ELDY_LINEPAIR1='255,255,255';
    $conf->global->THEME_ELDY_LINEPAIR2='255,255,255';
    $conf->global->THEME_ELDY_LINEPAIRHOVER='238,246,252';
    $conf->global->THEME_ELDY_TEXT='50,50,130';
    if ($dol_use_jmobile)
    {
        $conf->global->THEME_ELDY_BACKTABCARD1='245,245,245';    // topmenu
        $conf->global->THEME_ELDY_BACKTABCARD2='245,245,245';
        $conf->global->THEME_ELDY_BACKTABACTIVE='245,245,245';
    }
}

$colorbackhmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$conf->global->THEME_ELDY_TOPMENU_BACK1)   :(empty($user->conf->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackhmenu2     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK2)?$colorbackhmenu2:$conf->global->THEME_ELDY_TOPMENU_BACK2)   :(empty($user->conf->THEME_ELDY_TOPMENU_BACK2)?$colorbackhmenu2:$user->conf->THEME_ELDY_TOPMENU_BACK2);
$colorbackvmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$conf->global->THEME_ELDY_VERMENU_BACK1)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$user->conf->THEME_ELDY_VERMENU_BACK1);
$colorbackvmenu1b    =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1b)?$colorbackvmenu1:$conf->global->THEME_ELDY_VERMENU_BACK1b) :(empty($user->conf->THEME_ELDY_VERMENU_BACK1b)?$colorbackvmenu1b:$user->conf->THEME_ELDY_VERMENU_BACK1b);
$colorbackvmenu2     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK2)?$colorbackvmenu2:$conf->global->THEME_ELDY_VERMENU_BACK2)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK2)?$colorbackvmenu2:$user->conf->THEME_ELDY_VERMENU_BACK2);
$colorbacktitle1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTITLE1)   ?$colorbacktitle1:$conf->global->THEME_ELDY_BACKTITLE1)      :(empty($user->conf->THEME_ELDY_BACKTITLE1)?$colorbacktitle1:$user->conf->THEME_ELDY_BACKTITLE1);
$colorbacktitle2     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTITLE2)   ?$colorbacktitle2:$conf->global->THEME_ELDY_BACKTITLE2)      :(empty($user->conf->THEME_ELDY_BACKTITLE2)?$colorbacktitle2:$user->conf->THEME_ELDY_BACKTITLE2);
$colorbacktabcard1   =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABCARD1) ?$colorbacktabcard1:$conf->global->THEME_ELDY_BACKTABCARD1)  :(empty($user->conf->THEME_ELDY_BACKTABCARD1)?$colorbacktabcard1:$user->conf->THEME_ELDY_BACKTABCARD1);
$colorbacktabcard2   =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABCARD2) ?$colorbacktabcard2:$conf->global->THEME_ELDY_BACKTABCARD2)  :(empty($user->conf->THEME_ELDY_BACKTABCARD2)?$colorbacktabcard2:$user->conf->THEME_ELDY_BACKTABCARD2);
$colorbacktabactive  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$conf->global->THEME_ELDY_BACKTABACTIVE):(empty($user->conf->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$user->conf->THEME_ELDY_BACKTABACTIVE);
$colorbacklineimpair1=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR1)  ?$colorbacklineimpair1:$conf->global->THEME_ELDY_LINEIMPAIR1):(empty($user->conf->THEME_ELDY_LINEIMPAIR1)?$colorbacklineimpair1:$user->conf->THEME_ELDY_LINEIMPAIR1);
$colorbacklineimpair2=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR2)  ?$colorbacklineimpair2:$conf->global->THEME_ELDY_LINEIMPAIR2):(empty($user->conf->THEME_ELDY_LINEIMPAIR2)?$colorbacklineimpair2:$user->conf->THEME_ELDY_LINEIMPAIR2);
$colorbacklineimpairhover=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIRHOVER)  ?$colorbacklineimpairhover:$conf->global->THEME_ELDY_LINEIMPAIRHOVER):(empty($user->conf->THEME_ELDY_LINEIMPAIRHOVER)?$colorbacklineimpairhover:$user->conf->THEME_ELDY_LINEIMPAIRHOVER);
$colorbacklinepair1  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR1)    ?$colorbacklinepair1:$conf->global->THEME_ELDY_LINEPAIR1)    :(empty($user->conf->THEME_ELDY_LINEPAIR1)?$colorbacklinepair1:$user->conf->THEME_ELDY_LINEPAIR1);
$colorbacklinepair2  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR2)    ?$colorbacklinepair2:$conf->global->THEME_ELDY_LINEPAIR2)    :(empty($user->conf->THEME_ELDY_LINEPAIR2)?$colorbacklinepair2:$user->conf->THEME_ELDY_LINEPAIR2);
$colorbacklinepairhover  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIRHOVER)    ?$colorbacklinepairhover:$conf->global->THEME_ELDY_LINEPAIRHOVER)    :(empty($user->conf->THEME_ELDY_LINEPAIRHOVER)?$colorbacklinepairhover:$user->conf->THEME_ELDY_LINEPAIRHOVER);
$colorbackbody       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKBODY)     ?$colorbackbody:$conf->global->THEME_ELDY_BACKBODY)          :(empty($user->conf->THEME_ELDY_BACKBODY)?$colorbackbody:$user->conf->THEME_ELDY_BACKBODY);
$colortext           =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXT)         ?$colortext:$conf->global->THEME_ELDY_TEXT)                  :(empty($user->conf->THEME_ELDY_TEXT)?$colortext:$user->conf->THEME_ELDY_TEXT);
$fontsize            =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE1)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE1)             :(empty($user->conf->THEME_ELDY_FONT_SIZE1)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE1);
$fontsizesmaller     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE2)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE2)             :(empty($user->conf->THEME_ELDY_FONT_SIZE2)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE2);
// No hover by default, we keep only if we set var THEME_ELDY_USE_HOVER
if ((! empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) && empty($user->conf->THEME_ELDY_USE_HOVER))
    || (empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) && empty($conf->global->THEME_ELDY_USE_HOVER)))
{
    $colorbacklineimpairhover='';
    $colorbacklinepairhover='';
}

// Set text color to black or white
$tmppart=explode(',',$colorbackhmenu1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) $colortextbackhmenu='FFF';
else $colortextbackhmenu='444';
$tmppart=explode(',',$colorbackvmenu1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) { $colortextbackvmenu='FFF'; }
else { $colortextbackvmenu='444'; }
$tmppart=explode(',',$colorbacktitle1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) { $colortexttitle='FFF'; $colorshadowtitle='000'; }
else { $colortexttitle='444'; $colorshadowtitle='FFF'; }
$tmppart=explode(',',$colorbacktabcard1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 340) { $colortextbacktab='FFF'; }
else { $colortextbacktab='444'; }


$usecss3=true;
if ($conf->browser->name == 'ie' && round($conf->browser->version,2) < 10) $usecss3=false;
elseif ($conf->browser->name == 'iceweasel') $usecss3=false;
elseif ($conf->browser->name == 'epiphany')  $usecss3=false;

print '/*'."\n";
print 'colred='.$colred.' colgreen='.$colgreen.' colblue='.$colblue."\n";
print 'isred='.$isred.' isgreen='.$isgreen.' isblue='.$isblue."\n";
print 'colorbacklineimpair1='.$colorbacklineimpair1."\n";
print 'colorbacklineimpair2='.$colorbacklineimpair2."\n";
print 'colorbacklineimpairhover='.$colorbacklineimpairhover."\n";
print 'colorbacklinepair1='.$colorbacklinepair1."\n";
print 'colorbacklinepair2='.$colorbacklinepair2."\n";
print 'colorbacklinepairhover='.$colorbacklinepairhover."\n";
print 'usecss3='.$usecss3."\n";
print 'dol_hide_topmenu='.$dol_hide_topmenu."\n";
print 'dol_hide_leftmenu='.$dol_hide_leftmenu."\n";
print 'dol_optimize_smallscreen='.$dol_optimize_smallscreen."\n";
print 'dol_no_mouse_hover='.$dol_no_mouse_hover."\n";
print 'dol_use_jmobile='.$dol_use_jmobile."\n";
print '*/'."\n";

if (! empty($conf->dol_optimize_smallscreen)) $fontsize=11;
?>

/* ============================================================================== */
/* Default styles                                                                 */
/* ============================================================================== */*

/* Reset CSS */
ul, li {
	margin: 0;
	padding: 0;
	border: 0;
	font-size: 100%;
	font: inherit;
	vertical-align: baseline;
}

body {
<?php if (GETPOST("optioncss") == 'print') {  ?>
    background-color: #FFFFFF;
<?php } else { ?>
    background-color: #f4f4f4;
<?php } ?>
    color: #101010;
    <?php if (empty($dol_use_jmobile) || 1==1) { ?>
    font-size: <?php print $fontsize ?>px;
    <?php } ?>
    font-family: <?php print $fontlist ?>;
    -webkit-font-smoothing: subpixel-antialiased;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
    <?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
}

/* Images styles */
img {vertical-align: middle;}
img[src*=pdf] { vertical-align: sub!important; }
img[src*=globe] { vertical-align: sub!important; }
img[src*=star] { vertical-align: baseline; }
input[type=image] { vertical-align: middle; }
img[src*=stcomm] { vertical-align: text-top; }

/* Graphes styles */
.dolgraphtitlecssboxes + div, #stats {
    margin: 0 auto;
}
.pieLabelBackground {
    background-color: #333!important;
    opacity: 1!important;
    color: #f7f7f7!important;
}

.jPicker .Icon { margin-left: .5em; }


h1 {
    font-size: 1.7rem!important;
    color: #0083a2!important;
    line-height: 1.4;
}

a:link, a:visited, a:active { 
    font-family: <?php print $fontlist ?>; 
    font-weight: bold; color: #4A4A4A; 
    text-decoration: none;  
}
a:hover { 
    text-decoration: underline; 
    color: #0083a2;
}

.vmenu a:link, .vmenu a:visited, .vmenu a:active { 
    font-family: <?php print $fontvmenu ?>; 
    font-weight: bold; 
    color: #eee; 
    text-decoration: none;  
}
.tmenu a:hover { color:#0083a2;}

hr {
    display: block;
    margin:0.5em 0;
    border: 1px dashed #777;
}

<?php if (empty($dol_use_jmobile)) { ?>

input:focus, textarea:focus, button:focus, select:focus {
    box-shadow: 0 0 2px #8091BF;
}

textarea, input[type=text], input[type=password], 
input[type=email], input[type=number], input[type=search],
input[type=tel], input[type=url], .titlewrap input, select {
    border-color: #ddd;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
    padding: 4px 8px;
}

input, input.flat, textarea, textarea.flat, form.flat select, select.flat {
    font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
	background: #fff;
    color: #777;
    border-width: 1px;
    border-style: solid;
    <?php if (empty($dol_use_jmobile)) { ?>
    padding: 4px 8px;
    margin:3px 0;
<!--    margin: 0;-->
    <?php } ?>
    font-size: 1.2em;
    line-height: 100%;
    outline: 0;
}

.liste_titre .flat, .liste_titre select.flat {
padding: 2px 4px;
margin: 2px;
}

input, textarea, select {
	border-color: #ddd;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.07);
	margin:3px 10px 3px 0;
	}
<?php } ?>

select.flat, form.flat select {
	font-weight: normal;
}
input:disabled, select:disabled {
	background:#ddd;
}

input.liste_titre {
	box-shadow: none !important;
}
input.removedfile {
	padding: 0px !important;
	border: 0px !important;
}
textarea:disabled {
	background:#ddd;
}
input[type=checkbox] { 
    background-color: transparent; 
    border: none; box-shadow: none; 
    vertical-align: middle;
    margin: 0 2px 0 8px;
}
input[type=radio] { 
    background-color: transparent; 
    border: none; box-shadow: none; 
    vertical-align: middle;
}
input[type=image] { 
    background-color: transparent; 
    border: none; box-shadow: none; 
}
input:-webkit-autofill {
	background-color: <?php echo empty($dol_use_jmobile)?'#FBFFEA':'#FFFFFF' ?> !important;
	background-image:none !important;
	-webkit-box-shadow: 0 0 0 50px <?php echo empty($dol_use_jmobile)?'#FBFFEA':'#FFFFFF' ?> inset;
}
::-webkit-input-placeholder { color:#ccc; }
::-moz-placeholder { color:#ccc; } /* firefox 19+ */
:-ms-input-placeholder { color:#ccc; } /* ie */
input:-moz-placeholder { color:#ccc; }

<?php if (! empty($dol_use_jmobile)) { ?>
legend { margin-bottom: 8px; }
<?php } ?>


.button, .button:link, .button:active, .button:visited {
    font-family: <?php print $fontlist ?>;
    border: 1px solid #C0C0C0;
    padding: 0.5em 1em;
	margin: 0.2em 0.5em;
    background: #0083a2;
    border-color: #197489;
    -webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    color: #fff;
    font-size: 14px;
    cursor: pointer;
}
.button:focus  {
    font-family: <?php print $fontlist ?>;
	color: #eee;
    border: 1px solid #C0C0C0;
}
.button:hover   {
	background: #197489; 
    border-color: #0083a2;
    -webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    color: #fff;
}
.button:disabled {
	background: #ddd;
    cursor: not-allowed;
}

table[summary] .button[name=viewcal] { 
    min-width: 120px; width: 
    inherit!important; 
  /*  margin-right: 0;*/
    }

.liste_titre input[type=submit] {
    padding: 0.4em 0.8em;
    background: #444;
    border-color: #555;
    -webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    color: #fff;
}
.liste_titre input[type=submit]:hover {
    background: #333;
    border-color: #444;
}

div.noborder .button { padding: 0.4em 0.8em }

#blockvmenusearch .button {
    font-family: <?php print $fontlist ?>;
    font-size: inherit;
    border: 1px solid #C0C0C0;
	padding: 7px 8px;
	margin: 0em 0.5em;
    background: #444;
    border-color: #555;
    -webkit-box-shadow: inset 0 1px 0 rgba(150, 172, 180, 0.6);
    box-shadow: inset 0 1px 0 rgba(150, 172, 180, 0.6);
    color: #fff;
}
#blockvmenusearch .button:hover   {
	background: #333; 
    border-color: #444;
    -webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    color: #fff;
}

.buttonajax {
    font-family: <?php print $fontlist ?>;
	border: 0px;
	background-image: url(<?php echo $img_button ?>);
	background-position: bottom;
	padding: 0.1em 0.7em;
	margin: 0em 0.5em;
    -moz-border-radius:0px 5px 0px 5px;
	-webkit-border-radius:0px 5px 0px 5px;
	border-radius:0px 5px 0px 5px;
    -moz-box-shadow: 4px 4px 4px #DDD;
    -webkit-box-shadow: 4px 4px 4px #DDD;
    box-shadow: 4px 4px 4px #DDD;
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
div.inline-block
{
	display:inline-block;
}

th .button {
    -moz-box-shadow: none !important;
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
	-moz-border-radius:0px !important;
	-webkit-border-radius:0px !important;
	border-radius:0px !important;
}

.valignmiddle {
	vertical-align: middle;
}
.centpercent {
	width: 100%;
}
.center {
    text-align: center;
}
.left {
	text-align: <?php print $left; ?>;
}
.right {
	text-align: <?php print $right; ?>;
}
.nowrap {
	white-space: <?php print ($dol_optimize_smallscreen?'normal':'nowrap'); ?>;
}

/* Stats box main page v3.5 */
.tdboxstats {
    text-align: center!important;
}
.boxstats {
    display: inline-block;
    margin: 3px 5px;
    padding: 2px 10px;
    border: 1px dashed #0083a2;
    text-align: center;
}

/* new horizontal menu */
#tmenu_tooltip .hmenu ul {
    background: rgb(200,216,246);
    padding: 10px 0;
    margin: 0;
    position: fixed;
    width: 100%;
    height: 40px;
    left: 180px;
}

#tmenu_tooltip * {
    line-height: 40px;
    font-size: 13px;
    letter-spacing: normal;
    -webkit-box-sizing: content-box;
    -moz-box-sizing: content-box;
    box-sizing: content-box;
}

#tmenu_tooltip {
    <?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
    <?php } else { ?>
    height: 40px;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 10;
    margin:0;
    background: #333; 
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,0.2);
    box-shadow: 0 1px 1px rgba(0,0,0,0.2);  
    <?php } ?>
}

#tmenu_tooltip ul {
    direction: ltr; 
    position: fixed;
    left: 185px;
    margin-left: 1em;
    top: 0;
    z-index: 20;
}

#tmenu_tooltip ul li.menu_titre {
color: #eee;    
    margin:0;
    padding: 0 9px;
    list-style: none;
    position: relative;
    z-index: 30;
}

#tmenu_tooltip ul li:hover {
    background-color: #444;
    color: #2ea2cc;
}
ul.hmenu li:firstchild { margin-left: 15px }

#tmenu_tooltip li { float: left; }

#tmenu_tooltip a, #tmenu_tooltip a:hover, #tmenu_tooltip a img, #tmenu_tooltip a img:hover {
    outline: 0;
    border: 0;
    text-decoration: none;
    background: 0 0;
}
#tmenu_tooltip img {
    vertical-align: text-bottom;
}
#tmenu_tooltip a:hover {
    color: #2ea2cc;
}

/* submenus */
#tmenu_tooltip ul li > div:last-child { margin-bottom: 15px; }  
#tmenu_tooltip ul li > div {     
    display: none;
    height: 26px;
    background-color: #444; 
    text-align: left;
}

#tmenu_tooltip ul li > div.menu_contenu1 a:link, 
#tmenu_tooltip ul li > div.menu_contenu1 a:visited, 
#tmenu_tooltip ul li > div.menu_contenu1 a:active { 
    color: #eee;
    /*font-size:<?php print $fontsize; ?>px; */
    font-family: <?php print $fonthmenu; ?>; 
}
#tmenu_tooltip ul li > div.menu_contenu1 a:hover { color: #2ea2cc; }

#tmenu_tooltip ul li > div.menu_contenu2 a:link, 
#tmenu_tooltip ul li > div.menu_contenu2 a:visited, 
#tmenu_tooltip ul li > div.menu_contenu2 a:active { 
    color: #ddd;
    /*font-size:<?php print $fontsize; ?>px; */
    font-family: <?php print $fonthmenu2; ?>; 
}
#tmenu_tooltip ul li > div.menu_contenu2 a:hover { color: #4FB7DE; }

/*#tmenu_tooltip ul li:hover {  
    -webkit-columns: 2;
    -moz-columns: 2;
    columns: 2;  
    column-width: auto; 
}*/
#tmenu_tooltip ul li:hover > div { display: block!important; }

div.mainvmenu {
    position: relative;
    float: left;
    height: 40px;
    text-align: center;
    font-family: 'dolibarr-icons';
    margin-right: 5px;
}
div.mainvmenu:before {
    speak: none;
    width: 23px;
    font-size: 16px;
    -moz-transition: all .2s ease-in-out;
    -webkit-transition: all .2s ease-in-out;
    transition: all .2s ease-in-out;
}



/* ============================================================================== */
/* Icomoon icons for left and top menu                                            */
/* ============================================================================== */
@font-face {
	font-family: 'dolibarr-icons';
	src:url('fonts/dolibarr-icons.eot');
	src:url('fonts/dolibarr-icons.eot?#iefix') format('embedded-opentype'),
		url('fonts/dolibarr-icons.ttf') format('truetype'),
		url('fonts/dolibarr-icons.woff') format('woff'),
		url('fonts/dolibarr-icons.svg#dolibarr-icons') format('svg');
	font-weight: normal;
	font-style: normal;
}

[class^="icon-"], [class*=" icon-"] {
	font-family: 'dolibarr-icons';
	font-size: 20px;
	speak: none;
	font-style: normal;
	font-weight: normal;
	font-variant: normal;
	text-transform: none;
	line-height: 1;
	
	/* Better Font Rendering =========== */
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
}

.icon-users:before {
	content: "\e600";
}
.icon-gravatar:before {
	content: "\e601";
}
.icon-home:before {
	content: "\e602";
}
.icon-ftp:before {
	content: "\e603";
}
.icon-contracts:before {
	content: "\e604";
}
.icon-commercial:before {
	content: "\e606";
}
.icon-cat:before {
	content: "\e607";
}
.icon-externalsite:before {
	content: "\e608";
}
.icon-filemanager:before {
	content: "\e609";
}
.icon-members:before {
	content: "\e60a";
}
.icon-tools:before {
	content: "\e60b";
}
.icon-geopipmaxmind:before {
	content: "\e60c";
}
.icon-cashdesk:before {
	content: "\e60d";
}
.icon-shop:before {
	content: "\e60d";
}
.icon-orders_suppliers:before {
	content: "\e60d";
}
.icon-orders:before {
	content: "\e60d";
}   
.icon-margins:before {
	content: "\e60e";
}
.icon-project:before {
	content: "\e60f";
}
.icon-projects:before {
	content: "\e60f";
}
.icon-products:before {
	content: "\e610";
}
.icon-product:before {
	content: "\e610";
}
.icon-companies:before {
	content: "\e611";
}
.icon-thirdparties:before {
	content: "\e611";
}
.icon-accountancy:before {
	content: "\e612";
}
.icon-bank:before {
	content: "\e613";
}
.icon-admintools:before {
	content: "\e614";
}
.icon-setup:before {
	content: "\e615";
}
.icon-holiday:before {
	content: "\e616";
}
.icon-service:before {
	content: "\e617";
}
.icon-withdraw:before {
	content: "\e618";
}
.icon-agenda:before {
	content: "\e619";
}
.icon-ecm:before {
	content: "\e61a";
}
.icon-checks:before {
	content: "\e61b";
}
.icon-click2dial:before {
	content: "\e61d";
}
.icon-paypal:before {
	content: "\e61e";
}
.icon-google:before {
	content: "\e61f";
}
.icon-webservices:before {
	content: "\e620";
}
.icon-modulesadmintools:before {
	content: "\e621";
}
.icon-contacts:before {
	content: "\e622";
}
.icon-sendings:before {
	content: "\e623";
}
.icon-ficheinter:before {
	content: "\e624";
}
.icon-tax:before {
	content: "\e625";
}
.icon-donations:before {
	content: "\e626";
}
.icon-ca:before {
	content: "\e627";
}
.icon-mailing:before {
	content: "\e628";
}
.icon-export:before {
	content: "\e629";
}
.icon-import:before {
	content: "\e62a";
}
.icon-propals:before {
	content: "\e62c";
}
.icon-suppliers_bills:before {
	content: "\e62e";
}
.icon-customers_bills:before {
	content: "\e630";
}
.icon-stock:before {
	content: "\e631";
}
.icon-tripsandexpenses:before {
	content: "\e632";
}
.icon-opensurvey:before {
	content: "\e62d";
}

/* External modules */
.icon-reports:before {
	content: "\e605";
}
.icon-cron:before {
	content: "\e62f";
}
.icon-bittorrent:before {
	content: "\e62b";
}
.icon-accounting:before {
    content: "\e612";
}
.icon-scanner:before {
	content: "\e61c";
}

/* ============================================================================== */
/* Styles to hide objects                                                         */
/* ============================================================================== */

.hideobject { display: none; }
<?php if (! empty($dol_optimize_smallscreen)) { ?>
.hideonsmartphone { display: none; }
.noenlargeonsmartphone { width : 50px !important; display: inline !important; }
<?php } ?>
.linkobject { cursor: pointer; }


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
}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

#id-container {
  display: table;
  table-layout: fixed;
  clear: both;
}

#tiptip_holder.tip_left #tiptip_arrow_inner { border-left-color: rgba(85, 85, 85, 0.94)!important; border-left-color: #555!important; }
#tiptip_holder.tip_right #tiptip_arrow_inner { border-right-color: rgba(85, 85, 85, 0.94)!important; border-right-color: #555!important; }
#tiptip_holder.tip_top #tiptip_arrow_inner { border-top-color: rgba(85, 85, 85, 0.94)!important; border-top-color: #555!important; }
#tiptip_holder.tip_bottom #tiptip_arrow_inner { border-bottom-color: rgba(85, 85, 85, 0.94)!important; border-bottom-color: #555!important; }

#tiptip_holder.tip_left #tiptip_arrow_inner { margin-left: -6px!important; }
#tiptip_holder.tip_right #tiptip_arrow_inner { margin-right: -6px!important; }
#tiptip_holder.tip_bottom #tiptip_arrow_inner { margin-top: -6px!important; }
#tiptip_holder.tip_top #tiptip_arrow_inner { margin-bottom: -6px!important; }

#tiptip_content {
font-size: 11px;
color: #222!important;
padding: 0.7em 1.2em!important;
border: 1px solid rgba(255,255,255,0.25);
background-color: rgb(247, 247, 247)!important;
background-color: rgba(247, 247, 247, 0.94)!important;
border-radius: inherit!important;
-webkit-border-radius: inherit!important;
-moz-border-radius: inherit!important;
box-shadow: 0 0 2px #555!important;
-webkit-box-shadow: 0 0 2px #555!important;
-moz-box-shadow: 0 0 2px rgba(85, 85, 85, 0.94)!important;
}

#id-right, #id-left {
  display: table-cell;
  float: none;
  vertical-align: top;
}
#id-right{
    <?php if (GETPOST("optioncss") == 'print') {  ?>
	     padding-top: 10px;
    <?php } else { ?>
        padding-top: 52px;
    <?php } ?>
}
#id-<?php echo $right; ?> {
	width: 100%;
}

div.fiche {
	margin-<?php print $left; ?>: <?php print (GETPOST("optioncss") == 'print'?6:((empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))?($dol_hide_topmenu?'4':'20'):'24')); ?>px;
	margin-<?php print $right; ?>: <?php print (GETPOST("optioncss") == 'print'?8:(empty($conf->dol_optimize_smallscreen)?'15':'4')); ?>px;
	<?php if (! empty($conf->dol_hide_topmenu) && ! empty($conf->dol_hide_topmenu)) print 'margin-top: 4px;'; ?>
}

div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichethirdleft {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 35%;\n"; } ?>
	<?php if (! empty($conf->dol_optimize_smallscreen)) { print "padding-bottom: 6px;\n"; } ?>
}
div.fichetwothirdright {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$right.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 65%;\n"; } ?>
	<?php if (! empty($conf->dol_optimize_smallscreen)) { print "padding-bottom: 6px\n"; } ?>
}
div.fichehalfleft {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$right.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "padding-left: 16px;\n"; }
	else print "margin-top: 10px;\n"; ?>
}


/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

<?php
if (! empty($conf->dol_optimize_smallscreen))
{
	$minwidthtmenu=0;
	$heightmenu=19;
}
else
{
	$minwidthtmenu=66;
	$heightmenu=52;
}
?>

div.tmenudiv {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
<?php } else { ?>
    position: relative;
    display: block;
    white-space: nowrap;
    padding: 0;
    margin: 0;
	font-size: 14px;
    font-weight: normal;
<!--    height: <?php print ($heightmenu+1); ?>px;-->
	background-image: rgb(<?php echo $colorback1; ?>);
    color: #fcfcfc;
    text-decoration: none;
<?php } ?>
}

<!-- Liens menu vertical -->
a.tmenudisabled:link, a.tmenudisabled:visited, a.tmenudisabled:hover, a.tmenudisabled:active {
	color: #808080;
    font-weight: normal;
	padding: 0 5px;
	margin: 0 1px 2px 1px;
	cursor: not-allowed;
    font-weight: normal;
	white-space: nowrap;
	text-decoration: none;
}

a.tmenu:link, a.tmenu:visited, a.tmenu:hover, a.tmenu:active {
    font-weight: normal;
	padding: 0 5px;
	white-space: nowrap;
	color: #<?php echo $colortexttopmenu; ?>;
    text-decoration: none;
}
a.tmenu:link, a.tmenu:visited {
	color: #<?php echo $colortexttopmenu; ?>;
}
a.tmenu:hover, a.tmenu:active {
	margin: 0;
	color: #<?php echo $colortexttopmenu; ?>;
}

.tmenu li a, .tmenu:visited li a, .tmenu:hover li a {
    font-weight: normal!important;
}
a.tmenusel:link, a.tmenusel:visited, a.tmenusel:hover, a.tmenusel:active {
	font-weight: normal!important;
	padding: 0 5px;
	margin: 0;
    /*	background: #F4F4F4; */
	white-space: nowrap;
	color: #<?php echo $colortexttopmenu; ?>;
}

ul.tmenu {	
    padding:0;
    margin-bottom: 20px;
	list-style: none;
}
li.tmenu, li.tmenusel {
	position:relative;
	display: block;
	height: 40px;
	padding:0;
	margin:0;
}

li.tmenusel {
    background-color: #0083a2;
    color: #fff;
}
li.tmenusel a,li.tmenusel a:hover, li.tmenusel a:active, li.tmenusel a:link {
    color: #fff!important;
    font-weight: bold!important;
}

li.tmenu:hover {
    background-color: #444;
    color: #2ea2cc;
}
div.tmenuleft {
	width: 5px;
	float: <?php print $left; ?>;
    height: <?php print $heightmenu+4; ?>px;
	margin-top: -4px;
}
div.tmenucenter {
	padding-top: 2px;
	padding-left: 0;
	padding-right: 0;
    height: <?php print $heightmenu; ?>px;
    width: 100%;
}
.mainmenuaspan {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "padding: 12px 0!important;\n"; }
	else print "display:none;\n"; ?>
}

div.mainmenu {
    position : relative;
    float: left;
    width: 40px;
    height: 40px;
    margin: 0;
    text-align: center;
}

div.mainmenu:before {
    speak: none;
    padding: 10px 0;
    display: inline-block;
    transition: 0.2s all;
}

div.mainmenu.accounting { 
    background: none!important;
}

<?php if (empty($conf->dol_optimize_smallscreen)) {

// Add here more div for other menu entries. moduletomainmenu=array('module name'=>'name of class for div')

$moduletomainmenu=array('user'=>'','syslog'=>'','societe'=>'companies','projet'=>'project','propale'=>'commercial','commande'=>'commercial',
	'produit'=>'products','service'=>'products','stock'=>'products',
	'don'=>'accountancy','tax'=>'accountancy','banque'=>'accountancy','facture'=>'accountancy','compta'=>'accountancy','accounting'=>'accountancy','adherent'=>'members','import'=>'tools','export'=>'tools','mailing'=>'tools',
	'contrat'=>'commercial','ficheinter'=>'commercial','deplacement'=>'commercial',
	'fournisseur'=>'companies', 'ftp'=>'', 'externalsite'=>'',
	'barcode'=>'','fckeditor'=>'','categorie'=>'',
    'opensurvey' => '', 'bittorrent'=>'', 'cron'=>'', 'scanner'=>'', 'reports'=>'',
);
$mainmenuused='home';
foreach($conf->modules as $val)
{
	$mainmenuused.=','.(isset($moduletomainmenu[$val])?$moduletomainmenu[$val]:$val);
}
//var_dump($mainmenuused);
$mainmenuusedarray=array_unique(explode(',',$mainmenuused));

$generic=1;
$divalreadydefined=array('home','companies','products','commercial','accountancy','project','tools','members','shop','agenda','holiday','bookmark','cashdesk','ecm','geoipmaxmind','gravatar','clicktodial','paypal','webservices');
foreach($mainmenuusedarray as $val)
{
	if (empty($val) || in_array($val,$divalreadydefined)) continue;
	//print "XXX".$val;

	// Search img file in module dir
	$found=0; $url='';
	foreach($conf->file->dol_document_root as $dirroot)
	{
		if (file_exists($dirroot."/".$val."/img/".$val.".png"))
		{
			$url=dol_buildpath('/'.$val.'/img/'.$val.'.png', 1);
			$found=1;
			break;
		}
	}
	//Img file not found
	if (! $found)
	{
	    $url=dol_buildpath($path.'/theme/'.$theme.'/img/menus/generic.svg',1);
        $found=1;
	}
	if ($found)
	{
		print "div.mainmenu.".$val." {\n";
		print "	background: url(".$url.") no-repeat center;\n";
        print " background-size: 22px;\n";
		print "}\n";
	}
}
//End of part to add more div class css
?>

<?php
}	// End test if not phone
?>

.tmenuimage {
    padding:0 !important;
    margin:0 !important;
}


/* Login */

form#login {
	margin-top: <?php echo $dol_optimize_smallscreen?'30':'60' ?>px;
	margin-bottom: 30px;
	font-size: 13px;
	vertical-align: middle;
}
.login_table_title {
	max-width: 540px;
	color: #888888;
}
.login_table label {
}
.login_table {
	margin-left: 10px;
	margin-right: 10px;
	padding-left:6px;
	padding-right:6px;
	padding-top:12px;
	padding-bottom:12px;
	max-width: 540px;
	background-color: #E0E0E0;
    border: 1px solid #dbdbdb; 
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04); 
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
div#login_left, div#login_right {
	display: inline-block;
	min-width: 250px;
	padding-top: 10px;
	text-align: center;
	vertical-align: middle;
}
table.login_table tr td table.none tr td {
	padding: 2px;
}
#img_securitycode {
	border: 1px solid #DDDDDD;
}
#img_logo {
	max-width: 200px;
}

div.login_block {
	position: fixed;
	<?php print $right; ?>: 10px;
	top: 0px;
	padding-bottom: 6px;
	font-weight: bold;
	z-index: 40;
	<?php if (GETPOST("optioncss") == 'print') { ?>
	display: none;
	<?php } ?>
}
/* for v3.5 */
div.login_block_user{
    float: left;
    height: 40px;
}
div.login_block_user .login a {
    padding: 12px;
}
div.login_block_user > .classfortooltip.login_block_elem2 {
    padding: 11px 0;
}
div.login_block_other {
    float: left;
}
div.login_block_other > .login_block_elem {
    display: inline-block;
    padding: 12px 0;
}
div.login_block_other > .classfortooltip .login_block_elem a {
    padding: 12px 0;
}

/* login page message */
.login_main_home {
    background-color: #0083a2;
    color: #f9f9f9;
    line-height: 1.5em;
    font-size: 1.2em;
    max-width: 540px!important;
    margin: 0 10px;
    padding: 12px 6px;
    border: #197489 1px solid;
    -webkit-box-shadow: 0 1px 0 rgba(120,200,230,.6);
    box-shadow: 0 1px 0 rgba(120,200,230,.6);
}

/* message of the day not working */
/*.fiche > .notopnoleftnoright:nth-child(2) { width: 45%; }
.fiche > .notopnoleftnoright:nth-child(2) tr td {
    background-color: #f07b6e;
    color: #f9f9f9;
    line-height: 1.5em;
    font-size: 1.2em;
    padding: 10px 12px;
    border: #e0796e 1px solid;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}*/

div.login_block table {
	display: inline;
}

/* inf v3.5 */
td div.login {
	white-space:nowrap;
	padding: 0 12px;
    margin:0;
	font-weight: bold;
	color: #eee;
}
div.login a {
	color: #eee;
}
div.login a:hover {
	color: #0083a2;
	text-decoration: inherit;
}

.alogin {
	font-weight: normal !important;
	font-size: <?php echo $fontsizesmaller; ?>px !important;
}
.alogin:hover {
	text-decoration:underline !important;
	color: #0083a2!important;
}
img.login, img.printer, img.entity {
	padding:0;
	margin: 0px 10px 0px 10px!important;
	text-decoration: none;
	color: white;
	font-weight: bold;
}


/* ============================================================================== */
/* Menu gauche (main)                                                             */
/* ============================================================================== */

div.vmenu, td.vmenu {
    margin-<?php print $right; ?>: 6px;
    position: relative;
    background: #333;
    float: left;
    padding: 0px;
    padding-bottom: 0px;
    z-index: 20;
    <?php if (empty($conf->dol_optimize_smallscreen))   { ?>  
    width: 100%;
    min-width: 180px;
    max-width: 200px;
    <?php }  ?>  
}

.vmenu {
	<?php if (GETPOST("optioncss") == 'print') { ?>
    display: none;
	<?php } ?>
}

.vmenu .blockvmenuimpair { 
    margin-bottom: 2em; 
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,0.3);
    box-shadow: 0 1px 1px rgba(0,0,0,0.3);
}

/*.menu_contenu_disable { 
    background: url(../common/treemenu/branchbottom.gif) -2px 8px no-repeat;
}*/

.menu_contenu2 { 
    background: url(../common/treemenu/branchbottom.gif) -2px 8px no-repeat;
    break-inside: column ;
}
.menu_contenu2 a { font-size: 12px!important; }

a.vmenu:link, a.vmenu:visited, a.vmenu:hover, a.vmenu:active { 
    font-size:<?php print $fontsize; ?>px; 
    font-family: <?php print $fontlist; ?>; 
    text-align: <?php print $left; ?>; 
    font-weight: bold; 
}
font.vmenudisabled  { 
    font-size:<?php print $fontsize ?>px; 
    font-family: <?php print $fontlist; ?>; 
    text-align: <?php print $left; ?>; 
    font-weight: bold; 
    color: #93a5aa; 
}
a.vmenu:link, a.vmenu:visited { color: #eee; }
div.blockvmenubookmarks a.vmenu:link, div.blockvmenubookmarks a.vmenu:visited { color: #333; }
div.blockvmenubookmarks a.vmenu:hover { color: #222; }

a.vsmenu:link, a.vsmenu:visited, a.vsmenu:hover, a.vsmenu:active { 
    font-weight: normal; 
}
a.vsmenu:hover { 
     color: #333;
}
font.vsmenudisabled { 
    font-size:<?php print $fontsize; ?>px; 
    font-family: <?php print $fontlist; ?>; 
    text-align: <?php print $left; ?>; 
    font-weight: normal; 
    color: #93a5aa; 
}
font.vsmenudisabledmargin { 
    margin: 1px 1px 1px 8px; 
}

a.vsmenu img{
    vertical-align: bottom;
}

a.help:link, a.help:visited, a.help:active { 
    font-size:<?php print $fontsizesmaller ?>px; 
    font-family: <?php print $fontlist; ?>; 
    text-align: <?php print $left; ?>; 
    font-weight: normal; 
    color: #333; 
    text-decoration: none;
}
a.help[href="http://www.dolibarr.org"] {
    font-size: 16px;
    color: #333!important;
}
a.help img{
    vertical-align: top;
}

.vmenu div.blockvmenupair, .vmenu div.blockvmenuimpair
{
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding: 3px 0;
	background-color: #0083a2;
	width: 100%;
	border-bottom: 1px solid #e5e5e5;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.vmenu div.blockvmenuimpair:first-child { background-color: #eee }

div.blockvmenusearch
{
    text-decoration: none;
    padding-left: 10px;
    padding-right: 1px;
    padding-top: 10px;
    padding-bottom: 20px;
    border-bottom: 1px solid #0d6283;
    -webkit-box-shadow: 0 0px 1px rgba(0,0,0,.04);
    box-shadow: 0 0px 1px rgba(0,0,0,.04);
    background: #0083a2;
    clear: both;
}
div.blockvmenusearch .menu_titre {
    margin: 8px 0 1px 0;
}
div.blockvmenusearch a:link, 
div.blockvmenusearch a:visited, 
div.blockvmenusearch a:active{
    font-family: <?php print $fontvmenusearch; ?>;
    font-size:<?php print $fontsize; ?>px;
    color: #eee;
    text-align: <?php print $left; ?>;
}
div.blockvmenusearch a:hover { color: #333; }
div.blockvmenubookmarks {
    padding: 5px;
    padding-bottom: 10px;
	background: #F7F7F7;
	width: 100%;
	border-bottom: 1px solid #e5e5e5;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
div.blockvmenubookmarks .menu_titre { 
    text-align: center; 
    margin: 5px 0;
}
div.blockvmenubookmarks a:link, 
div.blockvmenubookmarks a:visited, 
div.blockvmenubookmarks a:active{
    font-family: <?php print $fontvmenubookmarks; ?>;
    font-size:<?php print $fontsize; ?>px;
     color: #333;
}
div.blockvmenubookmarks .menu_titre a { font-size: 13px; }

div.blockvmenubookmarks .menu_contenu {
    padding: 2px 6px;
    -webkit-hyphens: auto;
      -moz-hyphens: auto;
      -ms-hyphens: auto;
      -o-hyphens: auto;
      hyphens: auto;
    word-wrap: break-word;
}
   
div.blockvmenubookmarks a.vsmenu:hover { color: #0083a2; }

div.blockvmenubookmarks img:hover {
    background-image: url('img/object_bookmark_full.png');
}

div.blockvmenuhelp {
<?php if (empty($conf->dol_optimize_smallscreen)) { ?>
	font-family: <?php print $fontmenu; ?>;
	color: #333;
	background-color: #E6E6E6;
	text-align: center;
    margin:0;
    padding-top: 15px; 
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,0.3);
    box-shadow: 0 1px 1px rgba(0,0,0,0.3);
<?php } else { ?>
    display: none;
<?php } ?>
}
div.blockvmenuhelp a {
    color: #eee;
    font-family: <?php print $fontvmenuhelp; ?>;
    font-size:<?php print $fontsize; ?>px;
}
div.blockvmenuhelp a:hover {
    color: #0083a2!important;
}
div.blockvmenuhelp a[href="http://www.dolibarr.fr"]{
    font-size: 14px;
}
div.blockvmenuhelp:last-child {  
    padding-bottom: 15px; 
}

td.barre {
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #b3c5cc;
	font-family: <?php print $fontlist; ?>;
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
    border: 1px solid #b3c5cc;
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

.largebutton {
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$conf->theme.'/img/tmenu2.jpg',1); ?>) !important;
    background-repeat: repeat-x !important;
    border: 1px solid #CCC !important;

    -moz-box-shadow: 4px 4px 4px #EEE;
    -webkit-box-shadow: 4px 4px 4px #EEE;
    box-shadow: 4px 4px 4px #EEE;

    padding: 0 4px 0 4px !important;
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

/* ============================================================================== */
/* Panes for ECM or Filemanager                                                   */
/* ============================================================================== */

#containerlayout .layout-with-no-border {
    border: 0 !important;
    border-width: 0 !important;
}

#containerlayout .layout-padding {
    padding: 2px !important;
}

/*
 *  PANES and CONTENT-DIVs
 */
#containerlayout .ui-layout-pane { /* all 'panes' */
    background: #FFF;
    border:     1px solid #BBB;
    /* DO NOT add scrolling (or padding) to 'panes' that have a content-div,
       otherwise you may get double-scrollbars - on the pane AND on the content-div
    */
    padding:    0px;
    overflow:   auto;
}
/* (scrolling) content-div inside pane allows for fixed header(s) and/or footer(s) */
#containerlayout .ui-layout-content {
	padding:    10px;
	position:   relative; /* contain floated or positioned elements */
	overflow:   auto; /* add scrolling to content-div */
}

/*
 *  RESIZER-BARS
 */
.ui-layout-resizer  { /* all 'resizer-bars' */
	width: <?php echo (empty($conf->dol_optimize_smallscreen)?'8':'24'); ?>px !important;
}
.ui-layout-resizer-hover    {   /* affects both open and closed states */
}
/* NOTE: It looks best when 'hover' and 'dragging' are set to the same color,
    otherwise color shifts while dragging when bar can't keep up with mouse */
/*.ui-layout-resizer-open-hover ,*/ /* hover-color to 'resize' */
.ui-layout-resizer-dragging {   /* resizer beging 'dragging' */
    background: #DDD;
    width: <?php echo (empty($conf->dol_optimize_smallscreen)?'8':'24'); ?>px;
}
.ui-layout-resizer-dragging {   /* CLONED resizer being dragged */
    border-left:  1px solid #BBB;
    border-right: 1px solid #BBB;
}
/* NOTE: Add a 'dragging-limit' color to provide visual feedback when resizer hits min/max size limits */
.ui-layout-resizer-dragging-limit { /* CLONED resizer at min or max size-limit */
    background: #E1A4A4; /* red */
}
.ui-layout-resizer-closed {
    background-color: #DDDDDD;
}
.ui-layout-resizer-closed:hover {
    background-color: #EEDDDD;
}
.ui-layout-resizer-sliding {    /* resizer when pane is 'slid open' */
    opacity: .10; /* show only a slight shadow */
    filter:  alpha(opacity=10);
}
.ui-layout-resizer-sliding-hover {  /* sliding resizer - hover */
    opacity: 1.00; /* on-hover, show the resizer-bar normally */
    filter:  alpha(opacity=100);
}
/* sliding resizer - add 'outside-border' to resizer on-hover */
/* this sample illustrates how to target specific panes and states */
/*.ui-layout-resizer-north-sliding-hover  { border-bottom-width:  1px; }
.ui-layout-resizer-south-sliding-hover  { border-top-width:     1px; }
.ui-layout-resizer-west-sliding-hover   { border-right-width:   1px; }
.ui-layout-resizer-east-sliding-hover   { border-left-width:    1px; }
*/

/*
 *  TOGGLER-BUTTONS
 */
.ui-layout-toggler {
    <?php if (empty($conf->dol_optimize_smallscreen)) { ?>
    border-top: 1px solid #AAA; /* match pane-border */
    border-right: 1px solid #AAA; /* match pane-border */
    border-bottom: 1px solid #AAA; /* match pane-border */
    background-color: #DDD;
    top: 5px !important;
	<?php } else { ?>
	diplay: none;
	<?php } ?>
}
.ui-layout-toggler-open {
	height: 54px !important;
	width: <?php echo (empty($conf->dol_optimize_smallscreen)?'7':'22'); ?>px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ui-layout-toggler-closed {
	height: <?php echo (empty($conf->dol_optimize_smallscreen)?'54':'2'); ?>px !important;
	width: <?php echo (empty($conf->dol_optimize_smallscreen)?'7':'22'); ?>px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ui-layout-toggler .content {	/* style the text we put INSIDE the togglers */
    color:          #666;
    font-size:<?php print $fontsize ?>px;
    font-weight:    bold;
    width:          100%;
    padding-bottom: 0.35ex; /* to 'vertically center' text inside text-span */
}

/* hide the toggler-button when the pane is 'slid open' */
.ui-layout-resizer-sliding  ui-layout-toggler {
    display: none;
}

.ui-layout-north {
	height: <?php print (empty($conf->dol_optimize_smallscreen)?'54':'21'); ?>px !important;
}


/* ECM */

#containerlayout .ecm-layout-pane { /* all 'panes' */
    background: #FFF;
    border:     1px solid #BBB;
    /* DO NOT add scrolling (or padding) to 'panes' that have a content-div,
       otherwise you may get double-scrollbars - on the pane AND on the content-div
    */
    padding:    0px;
    overflow:   auto;
}
/* (scrolling) content-div inside pane allows for fixed header(s) and/or footer(s) */
#containerlayout .ecm-layout-content {
	padding:    10px;
	position:   relative; /* contain floated or positioned elements */
	overflow:   auto; /* add scrolling to content-div */
}

.ecm-layout-toggler {
    border-top: 1px solid #AAA; /* match pane-border */
    border-right: 1px solid #AAA; /* match pane-border */
    border-bottom: 1px solid #AAA; /* match pane-border */
    background-color: #CCC;
    }
.ecm-layout-toggler-open {
	height: 48px !important;
	width: 6px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ecm-layout-toggler-closed {
	height: 48px !important;
	width: 6px !important;
}

.ecm-layout-toggler .content {	/* style the text we put INSIDE the togglers */
    color:          #666;
    font-size:<?php print $fontsize ?>px;
    font-weight:    bold;
    width:          100%;
    padding-bottom: 0.35ex; /* to 'vertically center' text inside text-span */
}
#ecm-layout-west-resizer {
	width: 6px !important;
}

.ecm-layout-resizer  { /* all 'resizer-bars' */
    border:         1px solid #BBB;
    border-width:   0;
    }
.ecm-layout-resizer-closed {
}

.ecm-in-layout-center {
    border-left: 1px !important;
    border-right: 0px !important;
    border-top: 0px !important;
}

.ecm-in-layout-south {
    border-left: 0px !important;
    border-right: 0px !important;
    border-bottom: 0px !important;
    padding: 4px 0 4px 4px !important;
}



/* ============================================================================== */
/* Onglets                                                                        */
/* ============================================================================== */
div.tabs {
    margin: 0px 0px 1px 6px;
    padding: 0px 6px 3px 0px;
    text-align: <?php print $left; ?>;
    font-weight: normal;
	clear:both;
	height:100%;
}
div.tabsElem { margin-top: 8px; }		/* To avoid overlap of tabs when not browser */

div.tabBar {
    color: #<?php echo $colortextmain; ?>;
    padding-top: 8px;
    padding-left: <?php echo ($dol_optimize_smallscreen?'4':'8'); ?>px;
    padding-right: <?php echo ($dol_optimize_smallscreen?'4':'8'); ?>px;
    padding-bottom: 8px;
    margin-bottom: 10px;
    border: 1px solid #e5e5e5;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
	width: auto;
	background-color: #f8f8f8;
}

div.tabsAction {
    margin: 20px 0em 10px 0em;
    padding: 0em 0em;
    text-align: right;
}


a.tabTitle {
    color:#666;
    margin-right:10px;
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
    padding: 4px 6px 2px 6px;
    margin: 0 1em;
    text-decoration: none;
    white-space: nowrap;
}
a.tabTitle img { vertical-align: bottom; }

a.tab:link, a.tab:visited, a.tab:hover, a.tab#active {
	font-family: <?php print $fontlist ?>;
	padding: 5px 12px 5px;
    margin: 0em 0.3em;
    text-decoration: none;
    white-space: nowrap;
	background: rgb(234,234,234);
	-webkit-box-shadow: 0 0 1px rgba(0,0,0,.04);
    box-shadow: 0 0 1px rgba(0,0,0,.04);
}

a.tab#active {
	position: relative;
	bottom: -1px;
    background: #f8f8f8;
    font-weight: bold;
	border: 1px solid #e5e5e5;
    border-bottom: none;
    -webkit-box-shadow: 0 -1px 0 rgba(0,0,0,.04);
    box-shadow: 0 -1px 0 rgba(0,0,0,.04);
}

a.tab:link, a.tab:visited, a.tab:hover
{
    color: #<?php echo $colortextmain; ?>;
	font-weight: normal;
}
a.tab:hover
{
	background: rgb(222, 222, 222);
}
a.tabimage {
    color: #434956;
	font-family: <?php print $fontlist ?>;
    text-decoration: none;
    white-space: nowrap;
}

td.tab {
    background: #f9f9f9;
    border: 1px solid #e5e5e5!important;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin: 5px;
    padding: 0 0.5em;
}
table.notopnoleft td.liste_titre {
    padding: 0.8em 0.5em!important;
    border: 1px solid #e5e5e5!important;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin: 0px 0px 2px 0px;
}

span.tabspan {
    background: #dee7ec;
    color: #434956;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
}

div.tabBar ul li {
    margin-left: 30px!important;
}

/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

div.divButAction { margin-bottom: 1.4em; }

.butAction, .butAction:link, .butAction:visited {
    font-family: <?php print $fontlist ?>;
	font-weight: bold;
	background: #f8f8f8;
    border: 1px solid #C0C0C0;
	color: #434956;
	text-decoration: none;
	white-space: nowrap;
	padding: 0.3em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
    -webkit-box-shadow: inset 0 1px 0 rgba(170, 200, 210, 0.6);
    box-shadow: inset 0 1px 0 rgba(170, 200, 210, 0.6);
}
.butAction:hover, .butAction:active {
	border-color: #0c7b96;
    background: #0083a2;
    -webkit-box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    box-shadow: inset 0 1px 0 rgba(120,200,230,.6);
    color: #f7f7f7;
}

.butActionDelete, .butActionDelete:link, .butActionDelete:visited {
    font-family: <?php print $fontlist ?>;
	font-weight: bold;
	background: #f8f8f8;
    border: 1px solid #dd887f;
	color: #434956;
	text-decoration: none;
	white-space: nowrap;
	padding: 0.3em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
    -webkit-box-shadow: inset 0 1px 0 rgba(210, 170, 170, 0.6);
    box-shadow: inset 0 1px 0 rgba(210, 170, 170, 0.6);
}
.butActionDelete:hover, .butActionDelete:active {
	background: #f07b6e;
	color: #fff;
	-webkit-box-shadow: inset 0 1px 0 rgba(210, 170, 170, 0.6)!important;
    box-shadow: inset 0 1px 0 rgba(210, 170, 170, 0.6)!important;
}

.butActionRefused {
	font-family: <?php print $fontlist ?> !important;
	font-weight: normal!important;
    background: #ddd!important;
	border: 1px solid #AAAAAA !important;
	-webkit-box-shadow: inset 0 1px 0 rgba(170, 170, 170, 0.6);
    box-shadow: inset 0 1px 0 rgba(170, 170, 170, 0.6);
	color: #777!important;
	text-decoration: none !important;
	white-space: nowrap !important;
	cursor: not-allowed;
	padding: 0.3em 0.7em;
	margin: 0em 0.7em;
}
<?php if (! empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)) { ?>
.butActionRefused { display: none; }
<?php } ?>

span.butAction, span.butActionDelete {
	cursor: pointer;
}

/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.allwidth {
	width: 100%;
}

#undertopmenu {
	/*	background-image: url("<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/gradient.gif',1) ?>"); */
	background-repeat: repeat-x;
	margin-top: <?php echo ($dol_hide_topmenu?'6':'0'); ?>px;
}


.paddingrightonly {
	border-collapse: collapse;
	border: 0px;
	margin-left: 0px;
	spacing-left: 0px;
	padding-left: 0px;
	padding-right: 4px;
}
.nocellnopadd {
	list-style-type:none;
	margin: 0px !important;
	padding: 0px !important;
}

.notopnoleft {
	border-collapse: collapse;
	border: 0px;
	padding-top: 0px;
	padding-<?php print $left; ?>: 0px;
	padding-<?php print $right; ?>: 16px;
	padding-bottom: 4px;
	margin-bottom: 10px;
}
.notopnoleftnoright {
	border-collapse: collapse;
	border: 0px;
	padding-top: 0px;
	padding-left: 0px;
	padding-right: 0px;
	padding-bottom: 4px;
	margin: 0;
}


table.border {
	border: 1px solid #9CACBB;
	border-collapse: collapse;
}

table.border td {
    padding: 3px 5px 3px 5px;
	border: 1px solid #9CACBB;
	border-collapse: collapse;
	vertical-align: middle;
}
table.border td img { margin: 0 0.1em; }

td.border {
	border-top: 1px solid #000000;
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	border-left: 1px solid #000000;
}

/* Main boxes */

table.noborder, table.formdoc, div.noborder {
	width: 100%;
	border-collapse: separate !important;
	border-spacing: 0px;
	border: 1px solid #e5e5e5;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
	
	margin: 0px 0px 2px 0px;
	/*padding: 1px 2px 1px 2px;*/
}

table.noborder[summary="list_of_modules"] tr.pair, table.noborder[summary="list_of_modules"] tr.impair  { line-height: 2.2em; }
table.noborder tr, div.noborder form {
	line-height: 1.8em;
}

/* boxes padding */
/* table titles main page */
table.noborder th { padding: 3px; }
table.noborder th:first-child { padding-left: 10px; }
table.noborder th:last-child { padding-right: 10px; }

/* table content all pages */
table.noborder td {padding: 3px; }
table.noborder td:first-child { padding-left: 10px!important; /* !important corrige le padding manquant sur valeur "Aucune" */	 }
table.noborder td:last-child, div.noborder form div:last-child { padding-right: 10px;}

/* titles others pages */
table.noborder .liste_titre td { padding: 3px; }
table.noborder .liste_titre td:first-child { padding-left: 10px; }
table.noborder .liste_titre td:last-child { padding-right: 10px; }

form#searchFormList div.liste_titre { padding: 3px 10px 3px 10px; }


/* table liste -bank- e-mailing */
table.liste .liste_titre td, table.liste .liste_total td { padding: 3px 10px 3px 10px; }
table.liste .liste_titre th { padding: 3px 10px 3px 10px; }


table.liste .liste_titre td:first-child { text-align: left; }
table.liste .liste_titre th:first-child { text-align: left; }
table tr.liste_titre td:first-child { text-align: left; }


/* templates avec form au lieu de table */
div.noborder form div {padding: 3px; }
 div.noborder form>div:first-child { padding-left: 10px; }
 div.noborder form div:last-child { padding-right: 10px; }

table.nobordernopadding td img {
    margin-left: 0.2em;
}
.flat+img {
    margin-left: 0.4em;
}
table.nobordernopadding {
	border-collapse: collapse !important;
	border: 0px;
}
table.nobordernopadding tr {
	border: 0px !important;
	padding: 0px 0px;
}
table.nobordernopadding td {
	border: 0px !important;
	padding: 0px 0px;
	vertical-align: middle;
}
.login_block td.classfortooltip { height: 40px; }
.login_block .classfortooltip:hover { background-color: #444; }

.login_block td.classfortooltip a { 	padding: 12px 1px;  }
/* For lists */

table.liste {
	width: 100%;
	border-collapse: collapse;
	border-bottom-width: 1px;
	border-bottom-color: #BBBBBB;
	border-bottom-style: solid;
	
	margin-bottom: 2px;
	margin-top: 2px;
}
table.liste .impair td, table.liste .pair td { padding: 5px 10px; }
/*table.liste .impair td:first-child, table.liste .pair td:first-child { padding: 5px 0 5px 10px; }
table.liste .impair td:last-child, table.liste .pair td:last-child { padding: 5px 10px 5px 0; }*/

table .liste_titre td { padding: 2px 1px; }
table .liste_titre td:first-child { padding-left: 10px; }
table .liste_titre td:last-child { padding-right: 10px; }

table.nobordernopadding .pair td:first-child { padding-left: 10px; }
table.liste th:last-child { padding-right: 10px; }

table.liste td a img {
    vertical-align: middle;
}

.tagtable { display: table; }
.tagtable form { display: table-row; }
.tagtable form div { display: table-cell; }
.tagtr { display: table-row; }
.tagtd { display: table-cell; }

tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel
{
	height: 20px !important;
}
div.liste_titre, tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel
{
    background: #0083a2;
    color: #f8f8f8;
    font-family: <?php print $fontboxtitle ?>;
    font-size: 1.1em;
    font-weight: normal;
    line-height: 2.2em;
    white-space: <?php echo $dol_optimize_smallscreen?'normal':'nowrap'; ?>;
    text-align: <?php echo $left; ?>;
}
.liste_titre_sel { font-weight: bold!important; }

tr.liste_titre th, th.liste_titre, tr.liste_titre td, td.liste_titre, form.liste_titre div, div.liste_titre
{
    font-family: <?php print $fontboxtitle ?>;
    font-weight: normal;
<!--  permet de mettre une bordure sous les titres des boites
      border-bottom: 1px solid #FDFFFF;-->
    white-space: <?php echo $dol_optimize_smallscreen?'normal':'nowrap'; ?>;
}

table td.liste_titre a:link, table td.liste_titre a:visited, table td.liste_titre a:active { color: #eee; }
table td.liste_titre a:hover { color: #0083a2; }

table.noborder tr td a:link, table.noborder tr td a:visited, table.noborder tr td a:active,
table.noborder tr th a:link, table.noborder tr th a:visited, table.noborder tr th a:active { 
    color: #333;
    font-family: <?php echo $fontboxtitle ?>;
}
table.noborder tr td a:hover { color: #222; }

tr.liste_titre td, tr.liste_titre th { text-align: center; }
tr.liste_titre td[align=right], tr.liste_titre th[align=right] { text-align: right; }
tr.liste_titre td[align=left], tr.liste_titre th[align=left] { text-align: left; }
tr.liste_titre td[align=center], tr.liste_titre th[align=center] { text-align: center; }

table.noborder td[align=right], table.noborder th[align=right] { text-align: right; }
table.noborder td[align=left], table.noborder th[align=left] { text-align: left; }
table.noborder td[align=center], table.noborder th[align=center] { text-align: center; }
table.noborder td, table.noborder th { text-align: left; }

table.noborder td[valign=top], table.noborder td[valign=center], table.noborder td[valign=bottom] { vertical-align: middle; } 
table.noborder tr[valign=top], table.noborder tr[valign=center], table.noborder tr[valign=bottom] { vertical-align: middle; } 
table.valid td[valign=top], table.valid td[valign=center], table.valid td[valign=bottom] { vertical-align: middle; } 
table.valid tr[valign=top], table.valid tr[valign=center], table.valid tr[valign=bottom] { vertical-align: middle; } 

/*style main boxes */
div.fichethirdleft table.noborder td[align=right], div.fichethirdleft table.noborder th[align=right] { text-align: right; }
div.fichethirdleft table.noborder td[align=left], div.fichethirdleft table.noborder th[align=left] { text-align: left; }
div.fichethirdleft table.noborder td[align=center], div.fichethirdleft table.noborder th[align=center] { text-align: center; }
div.fichethirdleft table.noborder td, div.fichethirdleft table.noborder th { text-align: left; }

div.fichetwothirdright table.noborder td[align=right], div.fichetwothirdright table.noborder th[align=right] { text-align: right; }
div.fichetwothirdright table.noborder td[align=left], div.fichetwothirdright table.noborder th[align=left] { text-align: left; }
div.fichetwothirdright table.noborder td[align=center], div.fichetwothirdright table.noborder th[align=center] { text-align: center; }
div.fichetwothirdright table.noborder td, div.fichetwothirdright table.noborder th { text-align: left; }


.liste tr.liste_titre:nth-child(3) { 
    background: #333;
    line-height: 3em;
}
tr.liste_titre:nth-child(3) { 
    background: #333;
    line-height: 2em;
}

tr.liste_titre_sel th, th.liste_titre_sel, tr.liste_titre_sel td, td.liste_titre_sel, form.liste_titre_sel div
{
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    border: 1px solid #333;
    white-space: <?php echo $dol_optimize_smallscreen?'normal':'nowrap'; ?>;
    text-decoration: none;
    background: #333;
    color: #f7f7f7;
}

th.liste_titre>img, th.liste_titre_sel>img { padding-left: 5px; }

input.liste_titre {
    background: transparent;
    border: 0px;
    /* a verifier */
    margin: inherit;
    padding: 0;
}

tr.liste_total, form.liste_total {
	background: #f9f9f9;
}
tr.liste_total td, form.liste_total div {
    border-top: 1px solid #eee;
    color: #332266;
    font-weight: normal;
    white-space: nowrap;
}
tr.liste_total td[align=right], form.liste_total td[align=right] { color: #33cc66; font-weight: bold; }

.impair:hover {
<?php if ($colorbacklineimpairhover) { if ($usecss3) { ?>
	background: rgb(<?php echo $colorbacklineimpairhover; ?>);
<?php } else { ?>
	background: #fafafa;
<?php } } ?>
	border: 0px;
}

.impair, .nohover .impair:hover, tr.impair td.nohover {
	background: #f9f9f9;
	border: none!important;
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
}

.pair:hover {
<?php if ($colorbacklinepairhover) { if ($usecss3) { ?>
	background: rgb(<?php echo $colorbacklinepairhover; ?>);
<?php } else { ?>
	background: #fafafa;
<?php } }?>
	border: 0px;
}

.pair, .nohover .pair:hover, tr.pair td.nohover {
	background: #fff;
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
}


/* Disable shadows */
.noshadow {
	-moz-box-shadow: 0px 0px 0px #DDD !important;
	-webkit-box-shadow: 0px 0px 0px #DDD !important;
	box-shadow: 0px 0px 0px #DDD !important;
}

div.tabBar .noborder {
	-moz-box-shadow: 0px 0px 0px #DDD !important;
	-webkit-box-shadow: 0px 0px 0px #DDD !important;
	box-shadow: 0px 0px 0px #DDD !important;
}


/*
 *  Boxes
 */

.boxtable {
    -moz-box-shadow: 4px 4px 4px #DDD;
    -webkit-box-shadow: 4px 4px 4px #DDD;
    box-shadow: 4px 4px 4px #DDD;
    margin-bottom: 8px !important;
}


.box {
    padding-right: 0;
    padding-left: 0;
    padding-bottom: 6px;
}

tr.box_titre {
    background-color: #E6E6E6;
    color: #222;
    font-family: <?php print $fontboxtitle ?>, sans-serif;
    font-weight: normal;
    border-bottom: 1px solid #eee;
    white-space: nowrap;
}

tr.box_titre td.boxclose {
	width: 30px;
}

tr.box_impair {
	background: #f9f9f9;
    vertical-align: middle;
    font-family: <?php print $fontlist ?>;
}

tr.box_pair {
	background: #ffffff;
    vertical-align: middle;
    font-family: <?php print $fontlist ?>;
}

tr.fiche {
	font-family: <?php print $fontlist ?>;
}




/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { 
    color: rgb(224,121,110);
    display: inline-block;
}
.error   { color: #7e1515 !important; font-weight: bold; }

div.ok {
  color: #114466;
}

div.warning {
  color: #222;
  padding: 1.2em 1.5em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #d8c59a;
  background: rgb(255, 218, 135);
}

div.error {
    color: #7e1515; font-weight: bold;
    padding: 1.2em 1.5em;
    margin: 0.5em 0em 0.5em 0em;
    border: 1px solid #e0796e;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    background: #f07b6e;
}

/* Info admin */
div.info {
  color: #222;
  padding: 1.2em 1.5em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #e0796e;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
  background: #f07b6e;
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
 *  Other
 */

.product_line_stock_ok { color: #002200; }
.product_line_stock_too_low { color: #664400; }

.fieldrequired { font-weight: bold; color: #000055; }

.photo {
border: 0px;
/* filter:alpha(opacity=55); */
/* opacity:.55; */
}

.logo_setup {
	content:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/logo_setup.svg',1) ?>);
}

table.notopnoleftnoright div.titre {
	font-size: 13px;
    text-transform: uppercase;
}
div.titre {
    font-family: <?php print $fontlist ?>;
    font-weight: bold;
    color: #0083a2;
}

#dolpaymenttable { width: 600px; font-size: 13px; }
#tablepublicpayment { border: 1px solid #CCCCCC !important; width: 100%; }
#tablepublicpayment .CTableRow1  { background-color: #F0F0F0 !important; }
#tablepublicpayment tr.liste_total { border-bottom: 1px solid #CCCCCC !important; }
#tablepublicpayment tr.liste_total td { border-top: none; }

#divsubscribe { width: 700px; }
#tablesubscribe { width: 100%; }

div.table-border {
	display:table;
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #9CACBB;
}
div.table-border-row {
	display:table-row;
}
div.table-key-border-col {
	display:table-cell;
	width: 25%;
	vertical-align:top;
	padding: 1px 2px 1px 1px;
	border: 1px solid #9CACBB;
	border-collapse: collapse;
}
div.table-val-border-col {
	display:table-cell;
	width:auto;
	padding: 1px 2px 1px 1px;
	border: 1px solid #9CACBB;
	border-collapse: collapse;
}


/* ============================================================================== */
/* Formulaire confirmation (When Ajax JQuery is used)                             */
/* ============================================================================== */

.ui-dialog-titlebar {
}
.ui-dialog-content {
    font-size: <?php print $fontsize; ?>px !important;
}

/* ============================================================================== */
/* Formulaire confirmation (When HTML is used)                                    */
/* ============================================================================== */

table.valid {    
    padding: 1.2em 1.5em;
    margin: 0.5em 0em 0.5em 0em;
    border: 1px solid #e0796e;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    background: #f07b6e;
}
table.valid img { vertical-align: sub; }

.validtitre { font-weight: bold; }


/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

#tooltip {
position: absolute;
width: <?php print dol_size(450,'width'); ?>px;
border-top: solid 1px #BBBBBB;
border-<?php print $left; ?>: solid 1px #BBBBBB;
border-<?php print $right; ?>: solid 1px #444444;
border-bottom: solid 1px #444444;
padding: 2px;
z-index: 3000;
background-color: #FFFFF0;
opacity: 1;
}

/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */

img.datecallink { padding-right: 2px !important; }

.ui-datepicker-trigger {
	vertical-align: middle;
	cursor: pointer;
}

.bodyline {
	border: 1px #E4ECEC solid;
	padding: 0px;
	margin-bottom: 5px;
}
table.dp {
    width: 190px;
    background-color: #f9f9f9;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    padding: 0px;
	border-spacing: 0px;
	border-collapse: collapse;
}
.dp td, .tpHour td, .tpMinute td{padding:3px; font-size:10px;}
#dpExp {
    text-transform: capitalize;
    font-size:<?php print $fontsize ?>px;
    padding: 5px 0;
    color: #eee;
    background-color: #0083a2;
}

/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	text-transform: capitalize;
	font-weight:bold;
	background-color: #333;
    padding: 5px 0 7px 7px!important;
    color: #f7f7f7;
	font-size:11px;
	cursor:auto;
}
/* Close link */
#DPCancel {
    color: #f7f7f7;
    margin-left: 6px;
}
#DPCancel:hover {
color: #0083a2;
}

/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#617389;
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
.dpToday{font-weight:bold; color:black; background-color:#DDDDDD;}
.dpReg:Hover,.dpToday:Hover{background-color:#333;color:#f7f7f7}

/* Jour courant */
.dpSelected{background-color:#0B63A2;color:white;font-weight:bold; }

.tpHour{border-top:1px solid #DDDDDD; border-right:1px solid #DDDDDD;}
.tpHour td {border-left:1px solid #DDDDDD; border-bottom:1px solid #DDDDDD; cursor:pointer;}
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
    font-size:9px;
    border-width:0px;
    color:#0083a2;
    vertical-align:middle;
    cursor: pointer;
}


/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

div.visible {
    display: block;
}

div.hidden {
    display: none;
}

tr.visible {
    display: block;
}

td.hidden {
    display: none;
}


/* ============================================================================== */
/*  Module agenda                                                                 */
/* ============================================================================== */

table.cal_month    { border-spacing: 0px; }
.cal_current_month { border-top: 0; border-left: solid 1px #E0E0E0; border-right: 0; border-bottom: solid 1px #E0E0E0; }
.cal_other_month   { border-top: 0; border-left: solid 1px #C0C0C0; border-right: 0; border-bottom: solid 1px #C0C0C0; }
.cal_current_month_right { border-right: solid 1px #E0E0E0; }
.cal_other_month_right   { border-right: solid 1px #C0C0C0; }
.cal_other_month   { opacity: 0.6; background: #EAEAEA; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { opacity: 0.6; background: #EEEEEE; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border-left: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #6C7C7B; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past          { }
table.cal_event    { border: none; border-collapse: collapse; margin-bottom: 1px;
						-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 1px 2px rgba(0, 0, 0, 0.25);
						moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 1px 2px rgba(0, 0, 0, 0.25);
						box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 1px 2px rgba(0, 0, 0, 0.25);
						background: -webkit-gradient(linear, left top, left bottom, from(#006aac), to(#00438d));
						}
table.cal_event td { border: none; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
ul.cal_event       { padding-right: 2px; padding-top: 1px; border: none; list-style-type: none; margin: 0 auto; padding-left: 0px; padding-start: 0px; -khtml-padding-start: 0px; -o-padding-start: 0px; -moz-padding-start: 0px; -webkit-padding-start: 0px; }
li.cal_event       { border: none; list-style-type: none; }
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; color:rgba(255,255,255,.75); }


/* ============================================================================== */
/*  Ajax - Liste deroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-autocomplete-loading { background: white url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/working.gif',1) ?>) right center no-repeat; }
.ui-autocomplete {
	       position:absolute;
	       width:auto;
	       font-size: 1.0em;
	       background-color:white;
	       border:1px solid #888;
	       margin:0px;
	       padding:0px;
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
/*  jQuery - jeditable                                                            */
/* ============================================================================== */

.editkey_textarea, .editkey_ckeditor, .editkey_string, .editkey_email, .editkey_numeric, .editkey_select, .editkey_autocomplete {
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/edit.png',1) ?>) right top no-repeat;
	cursor: pointer;
}

.editkey_datepicker {
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/calendar.png',1) ?>) right center no-repeat;
	cursor: pointer;
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
.treeview .hover { color: black !important; }



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
.ui-menu .ui-menu-item a {
    text-decoration:none;
    display:block;
    padding:.2em .4em;
    line-height:1.5;
    zoom:1;
    font-weight: normal;
    font-family:<?php echo $fontlist; ?>;
    font-size:1em;
}
.ui-widget {
    font-family:<?php echo $fontlist; ?>;
    font-size:<?php echo $fontsize; ?>px;
}
.ui-button { margin-left: -2px; padding-top: 1px; }
.ui-button-icon-only .ui-button-text { height: 8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding: 2px 0px 6px 0px; }
.ui-button-text
{
    line-height: 1em !important;
}
.ui-autocomplete-input { margin: 0; padding: 1px; }


/* confirmation box */
.ui-widget-content {
    background: #f7f7f7!important;
}
.ui-state-default, .ui-widget-content .ui-state-default, .ui-widget-header .ui-state-default {
    background: #e6e6e6!important;
}
.ui-widget-header {
    background: #ccc!important;
}
.ui-dialog .ui-dialog-content { padding-top: 1em!important }
.ui-corner-all, .ui-corner-bottom, .ui-corner-right, .ui-corner-br { 
    -moz-border-radius-bottomright: 0!important;
    -webkit-border-bottom-right-radius: 0!important;
    -khtml-border-bottom-right-radius: 0!important;
    border-bottom-right-radius: 0!important;
}
.ui-corner-all, .ui-corner-bottom, .ui-corner-left, .ui-corner-bl { 
    -moz-border-radius-bottomleft: 0!important;
    -webkit-border-bottom-left-radius: 0!important;
    -khtml-border-bottom-left-radius: 0!important;
    border-bottom-left-radius: 0!important;
}
.ui-corner-all, .ui-corner-top, .ui-corner-right, .ui-corner-tr { 
    -moz-border-radius-topright: 0!important;
    -webkit-border-top-right-radius: 0!important;
    -khtml-border-top-right-radius: 0!important;
    border-top-right-radius: 0!important;
}
.ui-corner-all, .ui-corner-top, .ui-corner-left, .ui-corner-tl{ 
    -moz-border-radius-topleft: 0!important;
    -webkit-border-top-left-radius: 0!important;
    -khtml-border-top-left-radius: 0!important;
    border-bottom-top-radius: 0!important;
}


/* ============================================================================== */
/*  CASHDESK MODULE                                                               */
/* ============================================================================== */
body {
    background: inherit!important;
    font-family: "Open Sans",Tahoma,Arial,Helvetica !important;
}

.conteneur {
    background: #f9f9f9!important;
    width: 940px!important;
    border: 1px solid #e5e5e5;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.conteneur_img_gauche {
    background: none!important;
}

.conteneur_img_droite {
    background: none!important;
}

/* ------------------- Header ------------------- */
.entete {
    height: 0!important;
    background: none!important;
}

/* ------------------- Menu ------------------- */
.menu_principal {
    margin: 0 0 20px !important;
    width: 100%!important;
    background: #0083a2!Important;
    background-image: none!important;
}
.menu {
    padding: 15px 0!important;
}
.menu li {
    margin: 0 20px;
    line-height: 1.5em;
}

.menu_choix1,.menu_choix2 {
    font-size: 1.3em!important;
    width: initial!important;
}

.menu_choix1 a,.menu_choix2 a {
    width: initial!important;
}
.menu_choix1 a span,.menu_choix2 a span{
    display: inline-block;
    padding: 13px 0;
}

.menu_choix1 a:hover,.menu_choix2 a:hover {
    color: #333!important;
}

.menu_choix0 {
    float: right!important;
    margin-right: 20px!important;
    font-size: 13px!important;
    text-align: left!important;
    font-style: normal!important;
    width: 235px!important;
    color: #eee!important;
}
.menu_choix0 a {
    font-weight: bold!important;
    color: #333;
}
.menu_choix0 a:hover {
    color: #222;
    text-decoration: underline;
}
.menu_choix0 a img {
    vertical-align: sub;
}

.liste_articles {
    width: 245px!important;
    border: none!important;
}
.liste_articles_bas {
    background-color: #333;    
    padding-bottom: 15px;
    color: #eee;
    border: 1px solid #e5e5e5 !important;
}

p.titre {
    padding: 8px;
    color: #f4f4f4 !important;
    background: #0083a2;
    border-bottom: none !important;
}

.cadre_article {
    width: 200px!important;
    border-bottom: 1px solid #eee;
}

.cadre_article p {
    color: #eee!important;
}

.cadre_article p a {
    color: #eee!important;
}

.cadre_article p a:hover {
    text-decoration: underline;
}

.cadre_prix_total {
    color: #33cc66 !important;
    background-color: #f6f6f6;
    border: 1px solid #ddd!important;
    margin-left: 23px!important;
    margin-right: 23px!important;
}


.bouton_login input {
    cursor: pointer;
    border: 1px solid #C0C0C0!important;
    background: #0083a2!Important;
    background-image: none!important;
    font-weight: bold;
    color: #eee;
    padding: 1em;
    text-decoration: none;
    white-space: nowrap;
    -webkit-box-shadow: inset 0 1px 0 rgba(170, 200, 210, 0.6);
    box-shadow: inset 0 1px 0 rgba(170, 200, 210, 0.6);
}

.bouton_login input:hover {   background-color: #00708B!important; }

.principal {
    margin: 0 20px!important;
}

.titre1 {
    color: #f07b6e!important;
    font-size: 1.3em!important;
}

.cadre_facturation {
    border: 2px solid #E6E6E6!important;
    background: #fff;
}

.select_tva select {
    width: 70px!important;
}


.texte1_off,.texte2_off {
    cursor: not-allowed;
}

/* -------------- Boutons --------------------- */
.bouton_ajout_article {
    cursor: pointer;
    width: initial !important;
    display: block;
    margin: 15px auto 0 !important;
    padding: 15px !important;
    border: 1px solid #33cc66!important;
    background: #f8f8f8!important;
    background-image: none!important;
    font-weight: bold!important;
    text-transform: uppercase;
    color: #33cc66;
    text-decoration: none;
    white-space: nowrap;
    -webkit-box-shadow: inset 0 1px 0 rgba(51, 204, 102, 0.6);
    box-shadow: inset 0 1px 0 rgba(51, 204, 102, 0.6);
}
.bouton_ajout_article:hover {
    border-color: #33cc66;
    background: #33cc66!important;
    color: #f7f7f7;
    background-image: none!important;
}

.bouton_mode_reglement,.bouton_validation {
    cursor: pointer;
    border: 1px solid #C0C0C0!important;
    background: #f8f8f8!important;
    background-image: none!important;
    font-weight: bold;
    color: #434956;
    text-decoration: none;
    white-space: nowrap;
    -webkit-box-shadow: inset 0 1px 0 rgba(170, 200, 210, 0.6);
    box-shadow: inset 0 1px 0 rgba(170, 200, 210, 0.6);
}

.bouton_mode_reglement:hover,.bouton_validation:hover {
    border-color: #0c7b96;
    -webkit-box-shadow: inset 0 5px 0 rgba(51, 204, 102, 0.6);
    box-shadow: inset 0 5px 0 rgba(51, 204, 102, 0.6);
    background-image: none!important;
}
.bouton_mode_reglement:active, .bouton_mode_reglement:focus{
    border-color: #33cc66;
    background: #33cc66!important;
    color: #f7f7f7;
}

.bouton_mode_reglement_disabled {
    font-weight: normal!important;
    background: #ddd!important;
    border: 1px solid #AAAAAA !important;
    -webkit-box-shadow: inset 0 1px 0 rgba(170, 170, 170, 0.6);
    box-shadow: inset 0 1px 0 rgba(170, 170, 170, 0.6);
    color: #777!important;
    text-decoration: none !important;
    white-space: nowrap !important;
    cursor: not-allowed;
}

.pied { 
    height: 0!important;
    background: none!important; 
}


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */
span.cke_skin_kama {
    -moz-border-radius: 0px!important;
    -webkit-border-radius: 0px!important;
    border-radius: 0px!important;
    padding: 0 !important;
}
.cke_skin_kama .cke_wrapper {
    -moz-border-radius: 0px!important;
    -webkit-border-radius: 0px!important;
    border-radius: 0px!important;
}

.cke_wrapper.cke_ltr { background: #444!important; }
.cke_skin_kama a.cke_toolbox_collapser, .cke_skin_kama a:hover.cke_toolbox_collapser {
    background-color: #eee!important;
    border: none!important;
}

.cke_skin_kama .cke_toolgroup, .cke_skin_kama .cke_rcombo a, 
.cke_skin_kama .cke_rcombo a:active, .cke_skin_kama .cke_rcombo a:hover {
    -moz-border-radius: 0px!important;
    -webkit-border-radius: 0px!important;
    border-radius: 0px!important;
    background-color: #eee!important;
    background-image: none!important;
    background-repeat: no-repeat!important;
    background-position: initial!important;
}
.cke_skin_kama a.cke_toolbox_collapser_min, .cke_skin_kama a:hover.cke_toolbox_collapser_min {  }
.cke_editor table, .cke_editor tr, .cke_editor td
{
    border: 0px!important;
}

.cke_wrapper { padding: 4px !important; }

.cke_skin_kama .cke_contents iframe { 
    color: #777;
    border-width: 1px;
    border-style: solid;
    line-height: 100%;
    outline: 0;
}

a.cke_dialog_ui_button
{
    font-family: <?php print $fontlist ?> !important;
	background-image: url(<?php echo $img_button ?>) !important;
	background-position: bottom !important;
	padding: 0.1em 0.7em !important;
	margin: 0em 0.5em !important;
}
.cke_dialog_ui_hbox_last
{
	vertical-align: bottom ! important;
}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {
    height: 72px !important;
}


/* ============================================================================== */
/*  JSGantt                                                                       */
/* ============================================================================== */

div.scroll2 {
	width: <?php print isset($_SESSION['dol_screenwidth'])?max($_SESSION['dol_screenwidth']-830,450):'450'; ?>px !important;
}


/* ============================================================================== */
/*  jFileTree                                                                     */
/* ============================================================================== */

.ecmfiletree {
	width: 99%;
	height: 99%;
	background: #FFF;
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
    border: solid 1px #DDDDDD;
}

ui-layout-north {

}

ul.ecmjqft {
	font-size: 11px;
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
	white-space: nowrap;
	display: block;
}

ul.ecmjqft a {
	line-height: 20px;
	vertical-align: middle;
	color: #333;
	padding: 0px 0px;
	font-weight:normal;
	display: inline-block !important;
/*	float: left;*/
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
	position:absolute;
	right:4px;
}

/* Core Styles */
.ecmjqft LI.directory { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/folder2.png',1); ?>) left top no-repeat; }
.ecmjqft LI.expanded { font-weight:normal; background: url(<?php echo dol_buildpath($path.'/theme/common/treemenu/folder2-expanded.png',1); ?>) left top no-repeat; }
.ecmjqft LI.wait { font-weight:normal; background: url(<?php echo dol_buildpath('/theme/'.$theme.'/img/working.gif',1); ?>) left top no-repeat; }


/* ============================================================================== */
/*  jNotify                                                                       */
/* ============================================================================== */

.jnotify-container {
	position: fixed !important;
	top: 43px !important;
<?php if (! empty($conf->global->MAIN_JQUERY_JNOTIFY_BOTTOM)) { ?>
	top: auto !important;
	bottom: 4px !important;
<?php } ?>
	text-align: center;
	min-width: <?php echo $dol_optimize_smallscreen?'200':'480'; ?>px;
	width: auto;
	padding-left: 10px !important;
	padding-right: 10px !important;
	border-radius: initial!important;
}

.jnotify-container .jnotify-notification a.jnotify-close{ top: 5px !important } 

div.jnotify-background {
	opacity : 0.95 !important;
    border-radius: initial!important;
    background-color: #33cc66!important;
    border: 1px solid #29a352!important;
    -webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
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

.divmap, #google-visualization-geomap-embed-0, #google-visualization-geomap-embed-1, google-visualization-geomap-embed-2 {
    -moz-box-shadow: 0px 0px 10px #AAA;
    -webkit-box-shadow: 0px 0px 10px #AAA;
    box-shadow: 0px 0px 10px #AAA;
}




/* ============================================================================== */
/*  JMobile                                                                       */
/* ============================================================================== */

li.ui-li-divider .ui-link {
	color: #FFF !important;
}
.ui-btn {
	margin: 0.1em 2px
}
a.ui-link, a.ui-link:hover, .ui-btn:hover, span.ui-btn-text:hover, span.ui-btn-inner:hover {
	text-decoration: none !important;
}

.ui-btn-inner {
	min-width: .4em;
	padding-left: 10px;
	padding-right: 10px;
	<?php if (empty($dol_use_jmobile) || 1==1) { ?>
	font-size: <?php print $fontsize ?>px;
    <?php } ?>
	/* white-space: normal; */		/* Warning, enable this break the truncate feature */
}
.ui-btn-icon-right .ui-btn-inner {
	padding-right: 34px;
}
.ui-btn-icon-left .ui-btn-inner {
	padding-left: 34px;
}
.ui-select .ui-btn-icon-right .ui-btn-inner {
	padding-right: 38px;
}
.ui-select .ui-btn-icon-left .ui-btn-inner {
	padding-left: 38px;
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
	-moz-box-shadow: 0 -3px 6px rgba(0,0,0,.2);
	-webkit-box-shadow: 0 -3px 6px rgba(0,0,0,.2);
	box-shadow: 0 -3px 6px rgba(0,0,0,.2);
}
div.ui-controlgroup-controls div.tabsElem a#active {
	-moz-box-shadow: 0 -3px 6px rgba(0,0,0,.3);
	-webkit-box-shadow: 0 -3px 6px rgba(0,0,0,.3);
	box-shadow: 0 -3px 6px rgba(0,0,0,.3);
}

a.tab span.ui-btn-inner
{
	border: none;
	padding: 0;
}

.ui-body-c {
	border: 1px solid #CCC;
	text-shadow: none;
}
.ui-link {
	color: rgb(<?php print $colortext; ?>) !important;
}

a.ui-link {
	word-wrap: break-word;
}

/* force wrap possible onto field overflow does not works */
.formdoc .ui-btn-inner
{
	white-space: normal;
	overflow: hidden;
	text-overflow: hidden;
}

/* Warning: setting this may make screen not beeing refreshed after a combo selection */
.ui-body-c {
	background: #fff;
}

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

<!-- end zlib compression -->
<?php if(extension_loaded('zlib')){ob_end_flush();}?>
<?php
if (is_object($db)) $db->close();
?>


