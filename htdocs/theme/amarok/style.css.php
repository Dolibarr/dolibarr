<?php
/* Copyright (C) 2012	Nicolas Péré			<nicolas@amarok2.net>
 * Copyright (C) 2012	Xavier Peyronnet		<xavier.peyronnet@free.fr>
 * Copyright (C) 2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *		\file       htdocs/theme/amarok/style.css.php
 *		\brief      File for CSS style sheet Amarok
 */



//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);			// File must be accessed by logon page so without login
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

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));  // If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');
$fontsize=empty($conf->dol_optimize_smallscreen)?'12':'12';
$fontsizesmaller=empty($conf->dol_optimize_smallscreen)?'11':'11';

$path='';    		// This value may be used in future for external module to overwrite theme
$theme='amarok';	// Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define image path files
$fontlist='helvetica,arial,tahoma,verdana';    //$fontlist='Verdana,Helvetica,Arial,sans-serif';
//'/theme/auguria/img/menus/trtitle.png';
$img_liste_titre=dol_buildpath($path.'/theme/'.$theme.'/img/menus/trtitle.png',1);
$img_head=dol_buildpath($path.'/theme/'.$theme.'/img/headbg2.jpg',1);
$img_button=dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1);
$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_hide_leftmenu=$conf->dol_hide_leftmenu;
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
$colorback1=($colred-3).','.($colgreen-3).','.($colblue-3);         // topmenu
$colorback2=($colred+5).','.($colgreen+5).','.($colblue+5);
$colorbacktab1=($colred+15).','.($colgreen+16).','.($colblue+17);      // vmenu
$colorbacktab1b=($colred+5).','.($colgreen+6).','.($colblue+7);        // vmenu (not menu)
$colorbacktab2=($colred-15).','.($colgreen-15).','.($colblue-15);
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
$colorbackbody='#f5f5f5';
$colortext='40,40,40';
$fontsize=empty($conf->dol_optimize_smallscreen)?'12':'14';
$fontsizesmaller=empty($conf->dol_optimize_smallscreen)?'11':'14';

$colorback1          =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK1)?$colorback1:$conf->global->THEME_ELDY_TOPMENU_BACK1)        :(empty($user->conf->THEME_ELDY_TOPMENU_BACK1)?$colorback1:$user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorback2          =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK2)?$colorback2:$conf->global->THEME_ELDY_TOPMENU_BACK2)        :(empty($user->conf->THEME_ELDY_TOPMENU_BACK2)?$colorback2:$user->conf->THEME_ELDY_TOPMENU_BACK2);
$colorbacktab1       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1)?$colorbacktab1:$conf->global->THEME_ELDY_VERMENU_BACK1)     :(empty($user->conf->THEME_ELDY_VERMENU_BACK1)?$colorbacktab1:$user->conf->THEME_ELDY_VERMENU_BACK1);
$colorbacktab1b      =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1b)?$colorbacktab1:$conf->global->THEME_ELDY_VERMENU_BACK1b)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK1b)?$colorbacktab1b:$user->conf->THEME_ELDY_VERMENU_BACK1b);
$colorbacktab2       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK2)?$colorbacktab2:$conf->global->THEME_ELDY_VERMENU_BACK2)     :(empty($user->conf->THEME_ELDY_VERMENU_BACK2)?$colorbacktab2:$user->conf->THEME_ELDY_VERMENU_BACK2);
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
$tmppart=explode(',',$colorback1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '');
$tmpval+=(! empty($tmppart[2]) ? $tmppart[2] : '');
$tmpval+=(! empty($tmppart[3]) ? $tmppart[3] : '');
//print $tmpval;
if ($tmpval < 340) $colortextmain='FFFFFF';
else $colortextmain='101010';
if ($tmpval <= 360) { $colortexttitle='FFF'; $colorshadowtitle='000'; }
else { $colortexttitle='444'; $colorshadowtitle='FFF'; }

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
/* ============================================================================== */

*, html {
	margin:0;
	padding:0;
font-size:100%;
}

body {
<?php if (GETPOST("optioncss") == 'print' || ! empty($conf->dol_optimize_smallscreen)) {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background-color: <?php print $colorbackbody; ?>;
<?php } ?>
	color:#232323;
	<?php if (empty($dol_use_jmobile) || 1==1) { ?>
	font-size:<?php print $fontsize ?>px;
	<?php } ?>
   	font-family:<?php print $fontlist ?>;
    <?php print 'direction:'.$langs->trans("DIRECTION").";\n"; ?>
}

a {
	font-family:<?php print $fontlist ?>;
	font-weight:bold;
	text-decoration:none;
	color:#232323;
}

a:hover, a:active {
	color:rgba(0,0,0,.6);
}

<?php if (empty($dol_use_jmobile)) { ?>

input, input.flat, textarea, textarea.flat, form.flat select, select.flat {
	padding: 1px;
}
input, textarea {
    font-size:<?php print $fontsize ?>px;
    font-family:<?php print $fontlist ?>;
    border-radius:4px;
    border:solid 1px rgba(0,0,0,.3);
    border-top:solid 1px rgba(0,0,0,.4);
    border-bottom:solid 1px rgba(0,0,0,.2);
    box-shadow:1px 1px 2px rgba(0,0,0,.2) inset;
}

<?php } ?>

input[type="image"] {
	border-radius:0px;
	border:none;
	box-shadow:none;
}

input.flat {
	font-size:<?php print $fontsize ?>px;
	font-family:<?php print $fontlist ?>;
    border-radius:4px;
    border:solid 1px rgba(0,0,0,.3);
    border-top:solid 1px rgba(0,0,0,.4);
    border-bottom:solid 1px rgba(0,0,0,.2);
    box-shadow:1px 1px 2px rgba(0,0,0,.2) inset;
}

input:disabled {background:#b6b6b6;}

input[type=checkbox] { background-color: transparent; border: none; box-shadow: none; }
input[type=radio]    { background-color: transparent; border: none; box-shadow: none; }
input[type=image]    { background-color: transparent; border: none; box-shadow: none; }
input[type=text]     { min-width: 20px; }
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

textarea.flat {
	font-size:<?php print $fontsize ?>px;
	font-family:<?php print $fontlist ?>;
    border-radius:4px;
    border:solid 1px rgba(0,0,0,.3);
    border-top:solid 1px rgba(0,0,0,.4);
    border-bottom:solid 1px rgba(0,0,0,.2);
    box-shadow:1px 1px 2px rgba(0,0,0,.2) inset;
}

textarea:disabled {background:#dddddd;}

select.flat {
    font-size:<?php print $fontsize ?>px;
	font-family:<?php print $fontlist ?>;
	border-radius:4px;
	border:solid 1px rgba(0,0,0,.3);
	border-top:solid 1px rgba(0,0,0,.4);
	border-bottom:solid 1px rgba(0,0,0,.2);
	box-shadow:1px 1px 2px rgba(0,0,0,.2) inset;
	background: #FDFDFD;
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
.nobold {
	font-weight: normal !important;
}
.nounderline {
    text-decoration: none;
}

.blockvmenubookmarks .menu_contenu {
	background-color: transparent;
}

/* ! Message d'erreur lors du login : */
center .error { padding:8px !important; padding-left:26px !important; padding-right:20px; width:inherit; max-width:450px;color:#552323 !important; font-size:14px; border-radius:8px; text-align: left;}



/* ============================================================================== */
/* Styles to hide objects                                                         */
/* ============================================================================== */

.hideobject { display:none; }
<?php if (! empty($dol_optimize_smallscreen)) { ?>
.hideonsmartphone { display: none; }
.noenlargeonsmartphone { width : 50px !important; display: inline !important; }
<?php } ?>
.linkobject { cursor:pointer; }


/* ============================================================================== */
/* Styles for dragging lines                                                      */
/* ============================================================================== */

.dragClass {
	color: #333333;
}
td.showDragHandle {
	cursor: move;
}
.tdlineupdown {
	white-space: nowrap;
	min-width: 10px;
}

/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

div.tmenu {
	<?php if (GETPOST("optioncss") == 'print') {?>
	display:none;
	<?php } else {?>
	position:relative;
	display:block;
	margin:0;
	padding:0;
	padding-left:1em;
	top:0;
	left:0;
	right:0;
    white-space:nowrap;
	height:36px;
	<?php if ($conf->browser->name != 'ie') echo "line-height:36px; /* disabled for ie9 */ \n"; ?>
	background:#333333;
    background-image:linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
	border-bottom:solid 1px rgba(0,0,0,.8);
	box-shadow:0 0 6px rgba(0,0,0,.4) !important;
	z-index:100;
	<?php } ?>
}

div.tmenu a {
	font-weight:normal;
}

div.tmenu li {
	display:inline-table;
	margin-right:1em;
	text-transform:uppercase;
}

div.tmenu li a {color:#cccccc;}
div.tmenu li a:hover { color:rgba(255,255,255,1);}

div.tmenu ul li a.tmenusel {/* texte du menu principal sélectionné */
	color:#ffffff;
	font-weight:bold;
}

.tmenudisabled { color:#808080 !important; cursor: not-allowed; }



/* Login */

body.body center { color:white; margin-top: 100px; }

form#login {
	border:solid 1px rgba(0,0,0,.4);
	border-top:solid 1px #ffffff;
	background-color:#c7d0db;
	background-image:linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
	margin-left:auto;
	margin-right:auto;
	margin-bottom:25px;
	padding:20px 20px 10px;
	max-width:500px;
	border-radius:12px;
	box-shadow:0 0 16px rgba(0,0,0,.8);
}
form#login img  {width:auto; height:auto; opacity:.7;}
form#login img#img_logo {
	width:190px;
	max-width:190px;
	height:auto;
	border-radius:6px;
	padding:6px;
	background-color:#ffffff;
	border:solid 1px rgba(0,0,0,.4);
	border-top:solid 1px rgba(0,0,0,.5);
	border-bottom:solid 1px rgba(0,0,0,.3);
	box-shadow:1px 1px 6px rgba(0,0,0,.3) inset , 0 0 1px rgba(255,255,255,.6);
}

form#login input {
	padding:6px;
	font-size:120%;
}

form#login label, form#login td b {
	vertical-align:middle;
	line-height:40px;
	color:rgba(0,0,0,.4);
	text-shadow:1px 1px 1px rgba(255,255,255,.6);
}

form#login table.login_table {
	margin:10px 0px;
	border:none;
	background:none !important;
}

div#login_left, div#login_right {
	display: inline-block;
	min-width: 220px;
	text-align: center;
	vertical-align: middle;
}

table.login_table { background-color: red  !important;}
table.login_table tr td {vertical-align:middle;}
table.login_table tr.vmenu td {font-size:18px;}
table.login_table tr td a {color:#333333 !important;}
table.login_table tr td a:hover {color:#000000 !important;}

table.login_table .button {
	padding:2px;
	padding-left:6px;
	padding-right:6px;
	margin-right:6px;
	border-radius:.6em;

    background-image: linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
}

table.login_table .button:hover {
	background-image: linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(1, rgba(255,255,255,.3)),
		color-stop(0, rgba(0,0,0,.3))
	);
}

table.login_table .vmenu {
	color:rgba(0,0,0,.6);
	text-shadow:1px 1px 1px rgba(255,255,255,.6);
	font-size:120%;
}

div.login_block {
	position:absolute;
	top:0px;
	right:8px;
	z-index:100;
	<?php if (GETPOST("optioncss") == 'print') {?>
	display:none;
	<?php } ?>
}
div.login_block_user, div.login_block_other { clear: both; }

div.login_block a {color:rgba(255,255,255,.6);}
div.login_block a:hover {color:#ffffff}

div.login_block table {
	display:inline;
}

div.login {
	white-space:nowrap;
	/* padding: <?php echo ($conf->dol_optimize_smallscreen?'0':'8')?>px 0px 0px 0px; */
	/* margin:0px 0px 0px 8px; */
	font-weight:bold;
	float: right;
}
.login_block_user {
	float: right;
}
.login_block_elem {
	float: right;
	vertical-align: top;
	padding: 0px 0px 0px 8px !important;
	height: 16px;
}

img.login, img.printer, img.entity {
	/* padding: <?php echo ($conf->dol_optimize_smallscreen?'0':'8')?>px 0px 0px 0px; */
	margin:2px 0px 0px 0px;
	text-decoration:none;
	color: white;
	font-weight:bold;
}

.alogin {
	color: #FFF;
	font-weight: normal;
}
.alogin:hover {
	color: #FFF;
	text-decoration:underline;
}

div.login_main_home {
	color: #000000;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

td.vmenu {
	<?php if (GETPOST("optioncss") != 'print') {?>
    margin-right:2px;
    padding:0px;
    width:170px;
    /* border-right: 1px solid #666666; */
    <?php } ?>
}

div.vmenu {
	<?php if (GETPOST("optioncss") == 'print') {?>
	display:none;
	<?php } else {?>
	width:170px;
	-moz-box-shadow: 3px 0px 6px #CCC;
	-webkit-box-shadow: 3px 0px 6px #CCC;
	box-shadow: 3px 0px 6px #CCC;
	<?php } ?>
}

.blockvmenupair .menu_titre, .blockvmenuimpair .menu_titre {
	height:22px;
	line-height:22px;
	/* text-align:center; */
	background-color:rgba(0,0,0,.08);
	background-image:linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
	padding-left:5px;
	border-top:solid 1px rgba(255,255,255,.5);
	border-bottom:solid 1px rgba(0,0,0,.5);
}

.blockvmenupair .menu_titre a, .blockvmenuimpair .menu_titre a {font-weight:normal;}

.menu_contenu {
	background-color:#ffffff;
	padding-left:6px;
	border-top:solid 1px rgba(0,0,0,.05);
}

.menu_contenu:hover {background-color:#f7f7f7;}
.menu_contenu a.vsmenu {
	color:#000000;
	line-height:18px;
	font-weight:normal;
}

.blockvmenusearch {
	border-top:solid 1px rgba(0,0,0,.3);
	padding:10px 5px 20px;
	text-align:center;
}

.blockvmenusearch .menu_titre {
	margin-top:6px;
	text-align:left;
	padding-left:0px;
}

#blockvmenuhelp {
	border-top:solid 1px rgba(0,0,0,.1);
	padding:12px;
	text-align:center;
}

a.help:link, a.help:visited, a.help:hover, a.help:active { font-size:<?php print $fontsizesmaller ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #666666; }


/* ============================================================================== */
/* Panes for Main                                                   			  */
/* ============================================================================== */

#mainContent {
	background-color:#ffffff;
}

#mainContent, #leftContent .ui-layout-pane {
    padding:0px;
    overflow:auto;
}

#mainContent, #leftContent .ui-layout-center {
	padding:0px;
	position:relative; /* contain floated or positioned elements */
    overflow:auto;  /* add scrolling to content-div */
}


/* ============================================================================== */
/* Toolbar for ECM or Filemanager                                                 */
/* ============================================================================== */


.largebutton {
    background-image: -o-linear-gradient(bottom, rgb(<?php echo '240,240,240'; ?>) 15%, rgb(<?php echo '255,255,255'; ?>) 100%) !important;
    background-image: -moz-linear-gradient(bottom, rgb(<?php echo '240,240,240'; ?>) 15%, rgb(<?php echo '255,255,255'; ?>) 100%) !important;
    background-image: -webkit-linear-gradient(bottom, rgb(<?php echo '240,240,240'; ?>) 15%, rgb(<?php echo '255,255,255'; ?>) 100%) !important;
    background-image: -ms-linear-gradient(bottom, rgb(<?php echo '240,240,240'; ?>) 15%, rgb(<?php echo '255,255,255'; ?>) 100%) !important;
    background-image: linear-gradient(bottom, rgb(<?php echo '240,240,240'; ?>) 15%, rgb(<?php echo '255,255,255'; ?>) 100%) !important;
    border: 1px solid #CCC !important;

    -moz-border-radius: 5px 5px 5px 5px !important;
	-webkit-border-radius: 5px 5px 5px 5px !important;
	border-radius: 5px 5px 5px 5px !important;
    -moz-box-shadow: 4px 4px 4px #EEE;
    -webkit-box-shadow: 4px 4px 4px #EEE;
    box-shadow: 4px 4px 4px #EEE;

    padding: 0 4px 0 4px !important;
}

.toolbar {}
.toolbarbutton {}


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
    font-size:      12px;
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
    font-size:      12px;
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
	margin: 0px 0px 0px 6px;
	padding: 0px 6px 0px 0px;
	clear:both;
	height:100%;
}
div.tabsElem { margin-top: 10px; }		/* To avoid overlap of tabs when not browser */

div.tabBar {
    background-color:#ffffff;
    padding:6px;
    margin:0px 0px 14px 0px;
    border:1px solid #bbbbbb;
    -moz-box-shadow: 4px 4px 4px #DDD;
	-webkit-box-shadow: 4px 4px 4px #DDD;
	box-shadow: 4px 4px 4px #DDD;
}

div.tabBar table.notopnoleftnoright {
	white-space:nowrap;
}

div.tabsAction {
    margin-top:12px !important;
    text-align:right;
}

a.tabTitle {
    color:rgba(0,0,0,.5);
    margin-right:10px;
    text-shadow:1px 1px 1px #ffffff;
    padding-left:5px;
    vertical-align:middle;
}

a.tabTitle img {
	vertical-align:middle;
}

a.tab {
	padding: 5px 12px 3px;
	margin: 0em 0.2em;
	background-color:rgba(0,0,0,.2);
	color:#666666;
	border:solid 1px rgba(0,0,0,.3);
	border-bottom:0px;
	-webkit-border-top-left-radius:6px;
	-webkit-border-top-right-radius:6px;
}

a.tab#active {
	color:#232323;
	font-weight:bold;
	background-color:#ffffff;
	<?php echo $dol_use_jmobile?'':'border-bottom:solid 1px #ffffff;'; ?>
}

a.tab:hover {color:#333333;}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

#id-container {
  display: table;
  table-layout: fixed;
}
#id-right, #id-left {
  display: table-cell;
  float: none;
  vertical-align: top;
}
#id-<?php echo $right; ?> {
	width: 100%;
}

div.fiche {
	padding: 8px 5px 10px;
	margin-<?php print $left; ?>: <?php print (GETPOST("optioncss") == 'print'?6:((empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))?($dol_hide_leftmenu?'3':'16'):'24')); ?>px;
	margin-<?php print $right; ?>: <?php print (GETPOST("optioncss") == 'print'?8:(empty($conf->dol_optimize_smallscreen)?'12':'3')); ?>px;
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
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 65%;\n"; } ?>
	<?php if (! empty($conf->dol_optimize_smallscreen)) { print "padding-bottom: 6px\n"; } ?>
}
div.fichehalfleft {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "padding-left: 16px;\n"; }
	else print "margin-top: 10px;\n"; ?>
}



/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

div.divButAction { margin-bottom: 1.4em; }

.button, .butAction {background: #999; border: solid 1px #888; font-weight: normal; }
.butActionRefused {background: #eaeaea; color:rgba(0,0,0,0.6); font-weight: normal !important;	cursor: not-allowed; }
.butActionDelete {background: #b33c37; border:solid 1px #8d2f2b; font-weight: normal;}

.button, .butAction, .butActionRefused, .butActionDelete {
	padding:2px;
	padding-left:6px;
	padding-right:6px;
	margin-right:6px;
	/*
border-left: solid 1px rgba(0,0,0,.3);
	border-right: solid 1px rgba(0,0,0,.3);
	border-bottom: solid 1px rgba(0,0,0,.6);
	border-top:solid 1px rgba(0,0,0,.1);
*/
	border-radius:.6em;

    background-image: linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
	}

.button, a.butAction {color: white; font-weight: normal !important;}

.butAction, .butActionDelete {color:white;}

td.formdocbutton {padding-top:6px;}

.button:hover, .butAction:hover, .butActionDelete:hover {
	background-image: linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 100%, rgba(0,0,0,.3) 0%);
	background-image: -webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(1, rgba(255,255,255,.3)),
		color-stop(0, rgba(0,0,0,.3))
	);
	color:white;
}

/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

#undertopmenu {
}

table img, div.tagtable img {
	padding:0px 2px;
	vertical-align:middle;
}

table.liste img, div.list img {
	padding:0px;
}

table a, div.tagtable a {
	vertical-align:middle;
}

.nocellnopadd {
	list-style-type:none;
	margin:0px;
	padding:0px;
}

.allwidth {
width: 100%;
}

.notopnoleft {
	border-collapse:collapse;
	border:0px;
	padding-top:0px;
	padding-left:0px;
	padding-right:10px;
	padding-bottom:4px;
	margin:0px 0px;
}

table.notopnoleftnoright {
	border:0px;
	border-collapse:collapse;
	padding-top:0px;
	padding-left:0px;
	padding-right:10px;
	padding-bottom:4px;
	margin:0px;
}

table.border, table.dataTable, .table-border, .table-border-col, .table-key-border-col, .table-val-border-col, div.border {
	border:1px solid #dddddd;
	border-collapse:collapse;
	padding:1px 0px;
	padding-left:2px;
}

table.border td, div.border div div.tagtd {
	padding:1px 0px;
	border:1px solid #dddddd;
	border-collapse:collapse;
	padding-left:2px;
}

.table-key-border-col {
	width: 25%;
	vertical-align:top;
}
.table-val-border-col {
	width:auto;
}

/* Main boxes */

table.border.formdoc {
	background-color:#f7f7f7;
	border:1px solid #dddddd;
	margin:0px;
	width:60%;
}

table.border.formdoc td { padding:1px 3px; }

table.noborder, div.noborder {
	border:1px solid #bbbbbb;
	padding:0px;
	margin:3px 0px 8px;
	border-spacing:0px;
	-moz-box-shadow: 2px 2px 2px #cccccc;
	-webkit-box-shadow: 2px 2px 2px #cccccc;
	box-shadow: 2px 2px 2px #cccccc;
}

table.noborder tr, div.noborder form {}

table.noborder td, , div.noborder div { padding:1px 2px 1px 3px; }

table.nobordernopadding {
	border-collapse:collapse;
	border:0px;
}

table.nobordernopadding tr {
	border:0px;
	padding:0px 0px;
}

table.nobordernopadding td {
	border:0px;
	padding:1px 0px;
}

table.notopnoleftnopadd {
	background-color:#ffffff;
	border:1px solid #bbbbbb;
	padding:6px;
}

/* For lists */

table.liste {
	padding:0px;
	border:1px solid #bbbbbb;
	border-spacing:0px;
	background-image:linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
}

table.liste td {padding:1px 2px 1px 0px;}

.tagtable, .table-border { display: table; }
.tagtr, .table-border-row  { display: table-row; }
.tagtd, .table-border-col, .table-key-border-col, .table-val-border-col { display: table-cell; }
.tagtable form, .tagtable div { display: table-row; }
.tagtable form div, .tagtable div div { display: table-cell; }

tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel
{
	height: 22px;
}
div.liste_titre, tr.liste_titre, form.liste_titre, tr.box_titre {
	padding:4px;
	background-color:rgba(0,0,0,.2);
	background-image:linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
	text-align: <?php echo $left; ?>;
}

tr.box_titre td.boxclose {
	width: 36px;
}

tr.liste_titre td, tr.liste_titre th, form.liste_titre div {
	padding:2px;
	padding-left:2px !important;
	white-space:nowrap;
	text-shadow:1px 1px 1px #ffffff;
}

td.liste_titre_sel, form.liste_titre div.liste_titre_sel {
	font-weight:bold;
	white-space:nowrap;
}

tr.liste_total td, form.liste_total div {
	padding:1px 2px;
	border-top:solid 1px #cccccc;
	background-color:#eaeaea;
	font-weight:bold;
	white-space:nowrap;
}

tr.impair td, tr.pair td, form.impair div, form.pair div { padding:1px 1px 1px 2px; }

tr.impair table.nobordernopadding td, tr.pair table.nobordernopadding td { padding:1px 0px; }

.impair {
	background:#fdfdfd;
	font-family:<?php print $fontlist ?>;
	border:0px;
}

.pair {
	background:#f4f4f4;
	font-family:<?php print $fontlist ?>;
	border:0px;
}



/*
 *  Boxes
 */

.boxstats {
    <?php print "float: ".$left.";\n"; ?>
    margin: 4px;
    padding: 4px;
	/*-moz-box-shadow: 4px 4px 4px #DDD;
    -webkit-box-shadow: 4px 4px 4px #DDD;
    box-shadow: 4px 4px 4px #DDD;
    margin-bottom: 8px !important;*/
    border: 1px solid #AAA;
    text-align: center;
    border-radius: 5px;
}

.boxtable {
	-moz-box-shadow: 2px 2px 2px #cccccc;
	-webkit-box-shadow: 2px 2px 2px #cccccc;
	box-shadow: 2px 2px 2px #cccccc;
	/*white-space:nowrap;*/
}

.box {
	padding-right:0px;
	padding-left:0px;
	padding-bottom:4px;
}

tr.box_impair {
	background:#fdfdfd;
	font-family:<?php print $fontlist ?>;
}

tr.box_pair {
	background:#f4f4f4;
	font-family:<?php print $fontlist ?>;
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


/*
 *   Ok, Warning, Error
 */

.ok {
	color:#159e26;
	background:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/ok.png',1); ?>) left center no-repeat !important;
	padding-left:20px !important;
	font-weight:bold;
}

.warning {
	color:#bca936;
	background:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/warning.png',1); ?>) left center no-repeat !important;
	padding-left:20px !important;
	font-weight:bold;
}

.error {
	color:#a61111;
	background:#f58080 url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/error.png',1); ?>) left center no-repeat !important;
	padding-left:20px !important;
	font-weight:bold;
}

td.highlights {background:#f9c5c6;}

div.ok {
	background:#61e372; /* url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/ok.png',1); ?>) 3px center no-repeat; */
	/*color:#ffffff;*/
	padding:2px 4px 2px 6px;
	margin:0.5em 0em;
	font-weight:normal;
}

div.warning, div.info {
	background:#fcf5b8; /* url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/warning.png',1); ?>) 3px center no-repeat; */
	/*color:#232323; */
	padding:2px 4px 2px 6px;
	margin:0.5em 0em;
	border:1px solid #bca936;
	font-weight:normal;
}

div.error {
	background:#f58080; /* url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/error.png',1); ?>) 3px center no-repeat; */
	/* color:#ffffff; */
	padding:2px 4px 2px 6px;
	margin:0.5em 0em;
	border:1px solid #a61111;
	font-weight:normal;
}

/*
 *  Other
 */

.product_line_stock_ok { color: #002200; }
.product_line_stock_too_low { color: #664400; }

.fieldrequired {
	font-weight:bold;
	color:#333333;
}

.dolgraphtitle { margin-top: 6px; margin-bottom: 4px; }
.dolgraphtitlecssboxes { margin: 0px; }

#pictotitle {
	padding-left:5px;
	padding-right:1px;
}

.photo {border:0px;}

div.titre {
	color:rgba(0,0,0,.5);
	margin-right:12px;
	text-shadow:1px 1px 1px #ffffff;
	font-weight:bold;
	padding-left:1px;
	padding-bottom:2px;
}

#dolpaymenttable { width: 600px; font-size: 13px; }
#tablepublicpayment { border: 1px solid #CCCCCC !important; width: 100%; }
#tablepublicpayment .CTableRow1  { background-color: #F0F0F0 !important; }
#tablepublicpayment tr.liste_total { border-bottom: 1px solid #CCCCCC !important; }
#tablepublicpayment tr.liste_total td { border-top: none; }

#divsubscribe { width: 700px; }
#tablesubscribe { width: 100%; }



/* ============================================================================== */
/* Formulaire confirmation (When Ajax JQuery is used)                             */
/* ============================================================================== */

.ui-dialog-titlebar {}
.ui-dialog-content {font-size:<?php print $fontsize; ?>px !important;}


/* ============================================================================== */
/* Formulaire de confirmation (When HTML is used)                                 */
/* ============================================================================== */

table.valid {
    border-top:solid 1px #e6e6e6;
    border-left:solid 1px #e6e6e6;
    border-right:solid 1px #444444;
    border-bottom:solid 1px #555555;
	padding-top:0px;
	padding-left:0px;
	padding-right:0px;
	padding-bottom:0px;
	margin:0px 0px;
    background:#d5baa8;
}

.validtitre {
    background:#d5baa8;
	font-weight:bold;
}


/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

#tooltip {
	position:absolute;
	width:<?php print dol_size(450,'width'); ?>px;
	border-top:solid 1px #bbbbbb;
	border-left:solid 1px #bbbbbb;
	borderright:solid 1px #444444;
	border-bottom:solid 1px #444444;
	padding:2px;
	z-index:3000;
	background-color:#fffff0;
	opacity:1;
	-moz-border-radius:6px;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */

.ui-datepicker-title {
    margin:0 !important;
    line-height:28px;
}
.ui-datepicker-month {
    margin:0 !important;
    padding:0 !important;
}
.ui-datepicker-header {
    height:28px !important;
}

.bodyline {
	-moz-border-radius:8px;
	padding:0px;
	margin-bottom:5px;
	z-index:3000;
}

table.dp {
	width:180px;
	margin-top:3px;
	background-color:#ffffff;
	border:1px solid #bbbbbb;
	border-spacing:0px;
	-moz-box-shadow: 2px 2px 2px #cccccc;
	-webkit-box-shadow: 2px 2px 2px #cccccc;
	box-shadow: 2px 2px 2px #cccccc;
}

.dp td, .tpHour td, .tpMinute td {
	padding:2px;
	font-size:11px;
}

td.dpHead {
	padding:4px;
	font-size:11px;
	font-weight:bold;
}

/* Barre titre */
.dpHead, .tpHead, .tpHour td:Hover .tpHead {
	background-color:rgba(0,0,0,.2);
	background-image:linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image:-webkit-gradient(
		linear,
		left top,
		left bottom,
		color-stop(0, rgba(255,255,255,.3)),
		color-stop(1, rgba(0,0,0,.3))
	);
	font-size:10px;
	cursor:auto;
}

/* Barre navigation */
.dpButtons, .tpButtons {
	text-align:center;
	background-color:#eaeaea;
	color:#232323;
	font-weight:bold;
	cursor:pointer;
}

.dpDayNames td, .dpExplanation {
	background-color:#eaeaea;
	font-weight:bold;
	text-align:center;
	font-size:11px;
}

.dpWeek td {text-align:center}

.dpToday, .dpReg, .dpSelected {cursor:pointer;}

.dpToday {
	font-weight:bold;
	color:#232323;
	background-color:#dddddd;
}

.dpReg:Hover, .dpToday:Hover {
	background-color:#333333;
	color:#ffffff;
}

/* Jour courant */
.dpSelected {
	background-color:#0B63A2;
	color:#ffffff;
	font-weight:bold;
}

.tpHour {
	border-top:1px solid #dddddd;
	border-right:1px solid #dddddd;
}

.tpHour td {
	border-left:1px solid #dddddd;
	border-bottom:1px solid #dddddd;
	cursor:pointer;
}

.tpHour td:Hover {
	background-color:#232323;
	color:#ffffff;
}

.tpMinute {margin-top:5px;}

.tpMinute td:Hover {
	background-color:#333333;
	color:#ffffff;
}
.tpMinute td {
	background-color:#eaeaea;
	text-align:center;
	cursor:pointer;
}

.fulldaystarthour {margin-right:2px;}
.fulldaystartmin {margin-right:2px;}
.fulldayendhour {margin-right:2px;}
.fulldayendmin {margin-right:2px;}

/* Bouton X fermer */
.dpInvisibleButtons {
	border-style:none;
	background-color:transparent;
	padding:0px 2px;
	font-size:9px;
	border-width:0px;
	color:#0B63A2;
	vertical-align:middle;
	cursor:pointer;
}

td.dpHead .dpInvisibleButtons {
	color:#232323;
	font-weight:bold;
}


/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

div.visible {display:block;}
div.hidden {display:none;}
tr.visible {display:block;}
td.hidden {display:none;}


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

.cal_today {
	background:#ffffff;
	border:solid 2px #bbbbbb;
}

div.dayevent table.nobordernopadding tr td {padding:1px;}

table.cal_event    { border: none; border-collapse: collapse; margin-bottom: 1px; -webkit-border-radius: 6px; border-radius: 6px;
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
/*  Afficher/cacher                                                               */
/* ============================================================================== */

#evolForm input.error {
	font-weight:bold;
	border:solid 1px #ff0000;
	padding:1px;
	margin:1px;
}

#evolForm input.focuserr {
	font-weight:bold;
	background:#faf8e8;
	color:#333333;
	border:solid 1px #ff0000;
	padding:1px;
	margin:1px;
}


#evolForm input.focus {	/*** Mise en avant des champs en cours d'utilisation ***/
	background:#faf8e8;
	color:#333333;
	border:solid 1px #000000;
	padding:1px;
	margin:1px;
}

#evolForm input.normal { /*** Retour a l'état normal après l'utilisation ***/
	background:#ffffff;
	color:#333333;
	border:solid 1px #ffffff;
	padding:1px;
	margin:1px;
}


/* ============================================================================== */
/*  Ajax - Liste déroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-widget {font-family:Verdana,Arial,sans-serif; font-size:0.9em;}
.ui-autocomplete-loading {background:#ffffff url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/working.gif',1); ?>) right center no-repeat;}


/* ============================================================================== */
/*  Ajax - In place editor                                                        */
/* ============================================================================== */

form.inplaceeditor-form {/* The form */
}

form.inplaceeditor-form input[type="text"] {/* Input box */
}

form.inplaceeditor-form textarea {/* Textarea, if multiple columns */
	background:#FAF8E8;
	color:#333333;
}

form.inplaceeditor-form input[type="submit"] {/* The submit button */
	font-size:100%;
	font-weight:normal;
	border:0px;
	cursor:pointer;
}

form.inplaceeditor-form a {/* The cancel link */
	margin-left:5px;
	font-size:11px;
	font-weight:normal;
	border:0px;
	cursor:pointer;
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

.table_data {
	border-style:ridge;
	border:1px solid;
}

.tab_base {
	background:#C5D0DD;
	font-weight:bold;
	border-style:ridge;
	border:1px solid;
	cursor:pointer;
}

.table_sub_heading {
	background:#CCCCCC;
	font-weight:bold;
	border-style:ridge;
	border:1px solid;
}

.table_body {
	background:#F0F0F0;
	font-weight:normal;
	font-family:sans-serif;
	border-style:ridge;
	border:1px solid;
	border-spacing:0px;
	border-collapse:collapse;
}

.tab_loaded {
	background:#232323;
	color:#ffffff;
	font-weight:bold;
	border-style:groove;
	border:1px solid;
	cursor:pointer;
}


/* ============================================================================== */
/*  CSS for color picker                                                          */
/* ============================================================================== */

a.color, a.color:active, a.color:visited {
	position:relative;
	display:block;
	text-decoration:none;
	width:10px;
	height:10px;
	line-height:10px;
	margin:0px;
	padding:0px;
	border:1px inset #ffffff;
}

a.color:hover {border:1px outset #ffffff;}

a.none, a.none:active, a.none:visited, a.none:hover {
	position:relative;
	display:block;
	text-decoration:none;
	width:10px;
	height:10px;
	line-height:10px;
	margin:0px;
	padding:0px;
	cursor:default;
	border:1px solid #b3c5cc;
}

.tblColor {display:none;}
.tdColor {padding:1px;}
.tblContainer {background-color:#b3c5cc;}

.tblGlobal {
	position:absolute;
	top:0px;
	left:0px;
	display:none;
	background-color:#b3c5cc;
	border:2px outset;
}

.tdContainer {padding:5px;}

.tdDisplay {
	width:50%;
	height:20px;
	line-height:20px;
	border:1px outset #ffffff;
}

.tdDisplayTxt {
	width:50%;
	height:24px;
	line-height:12px;
	font-family:<?php print $fontlist ?>;
	font-size:8pt;
	color:#333333;
	text-align:center;
}

.btnColor {
	width:100%;
	font-family:<?php print $fontlist ?>;
	font-size:10pt;
	padding:0px;
	margin:0px;
}

.btnPalette {
	width:100%;
	font-family:<?php print $fontlist ?>;
	font-size:8pt;
	padding:0px;
	margin:0px;
}

/* Style to overwrites JQuery styles */
.ui-menu .ui-menu-item a {
    text-decoration:none;
    display:block;
    padding:.2em .4em;
    line-height:1.5;
    zoom:1;
    font-weight:normal;
    font-family:<?php echo $fontlist; ?>;
    font-size:1em;
}

.ui-widget {
    font-family:<?php echo $fontlist; ?>;
    font-size:<?php echo $fontsize; ?>px;
}

.ui-button { margin-left:-2px; padding-top: 1px; }
.ui-button-icon-only .ui-button-text { height:8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding:2px 0px 6px 0px; }
.ui-button-text { line-height:1em !important; }
.ui-autocomplete-input { margin:0; padding:1px; }


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */

.cke_editor table, .cke_editor tr, .cke_editor td {border:0px solid #FF0000 !important;}
span.cke_skin_kama {padding:0px !important;}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {height:72px !important;}


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
	line-height: 16px;
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
.ecmjqft LI.wait { font-weight:normal; background: url(<?php echo dol_buildpath('/theme/eldy/img/working.gif',1); ?>) left top no-repeat; }



/* ============================================================================== */
/*  jNotify                                                                       */
/* ============================================================================== */

.jnotify-container {
	position: fixed !important;
<?php if (! empty($conf->global->MAIN_JQUERY_JNOTIFY_BOTTOM)) { ?>
	top: auto !important;
	bottom: 4px !important;
<?php } ?>
	text-align: center;
	min-width: <?php echo $dol_optimize_smallscreen?'200':'480'; ?>px;
	width: auto;
	padding-left: 10px !important;
	padding-right: 10px !important;
}

/* use or not ? */
div.jnotify-background {
	opacity : 0.95 !important;
    -moz-box-shadow: 4px 4px 4px #AAA !important;
    -webkit-box-shadow: 4px 4px 4px #AAA !important;
    box-shadow: 4px 4px 4px #AAA !important;
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
/*  Datatable                                                                     */
/* ============================================================================== */

.sorting_asc  { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_asc.png',1); ?>') no-repeat center right; }
.sorting_desc { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_desc.png',1); ?>') no-repeat center right; }
.sorting_asc_disabled  { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_asc_disabled',1); ?>') no-repeat center right; }
.sorting_desc_disabled { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_desc_disabled',1); ?>') no-repeat center right; }


/* ============================================================================== */
/*  JMobile                                                                       */
/* ============================================================================== */

li.ui-li-divider .ui-link {
	color: #FFF !important;
}
.ui-btn {
	margin: 0.1em 2px
}
.ui-btn-inner {
	min-width: .4em;
	padding-left: 10px;
	padding-right: 10px;
	white-space: normal;
	<?php if (empty($dol_use_jmobile) || 1==1) { ?>
	font-size: <?php print $fontsize ?>px;
	<?php } ?>
}
.ui-btn-icon-right .ui-btn-inner {
	padding-right: 34px;
}
.ui-btn-icon-left .ui-btn-inner {
	padding-left: 34px;
}
.ui-select .ui-btn-icon-right .ui-btn-inner {
	padding-right: 36px;
}
.ui-select .ui-btn-icon-left .ui-btn-inner {
	padding-left: 36px;
}

.fiche .ui-controlgroup {
	margin: 0px;
	padding-bottom: 0px;
}
div.ui-controlgroup-controls div.tabsElem
{
	margin-top: 2px;
	margin-right: 8px;
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

a.tab span.ui-btn-inner, a.tab span.ui-btn-inner span.ui-btn-text
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

div.tabsElem a.ui-btn-corner-all {
	-webkit-border-bottom-left-radius: 0px;
	-moz-border-radius-bottomleft: 0px;
	border-bottom-left-radius: 0px;
	-webkit-border-bottom-right-radius: 0px;
	-moz-border-radius-bottomright: 0px;
	border-bottom-right-radius: 0px;
}

.ui-btn-icon-left .ui-icon {
	left: 8px;
}
.ui-btn-icon-right .ui-icon {
	right: 8px;
}

div.ui-radio
{
	display: inline-block;
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
.ui-body-c, .ui-btn-up-c, .ui-btn-hover-c {
	border: none !important;
}

/* Style for first level menu with jmobile */
.ui-bar-b {
    background: rgb(<?php echo $colorbacktitle1; ?>);
    background-repeat: repeat-x;
	<?php if ($usecss3) { ?>
	background-image: -o-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -moz-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -ms-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
    font-weight: bold;
	<?php } ?>
    color: #<?php echo $colortexttitle; ?> !important;
}
.alilevel0 {
    color: #<?php echo $colortexttitle; ?> !important;
	text-shadow: 1px 0px 1px #<?php echo $colorshadowtitle; ?>;
}
.alilevel1 {
    color: #<?php echo $colortexttitle; ?> !important;
	text-shadow: 1px 0px 1px #<?php echo $colorshadowtitle; ?>;
}
.lilevel1 {
	background-image: -webkit-gradient(linear,left top,left bottom,from( #eee ),to( #e1e1e1 )) !important;
	background-image: -webkit-linear-gradient( #eee,#e1e1e1 ) !important;
	background-image: -moz-linear-gradient( #eee,#e1e1e1 ) !important;
	background-image: -ms-linear-gradient( #eee,#e1e1e1 ) !important;
	background-image: -o-linear-gradient( #eee,#e1e1e1 ) !important;
	background-image: linear-gradient( #eee,#e1e1e1 ) !important;
}
.lilevel1:hover, .lilevel2:hover, .lilevel3:hover, .lilevel4:hover {
	background-image: -webkit-gradient(linear,left top,left bottom,from( #ddd ),to( #d1d1d1 )) !important;
	background-image: -webkit-linear-gradient( #ddd,#d1d1d1 ) !important;
	background-image: -moz-linear-gradient( #ddd,#d1d1d1 ) !important;
	background-image: -ms-linear-gradient( #ddd,#d1d1d1 ) !important;
	background-image: -o-linear-gradient( #ddd,#d1d1d1 ) !important;
	background-image: linear-gradient( #ddd,#d1d1d1 ) !important;
}



<?php
if (is_object($db)) $db->close();
?>
