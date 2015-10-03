<?php
/* Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
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
 *		\file       htdocs/theme/md/style.css.php
 *		\brief      File for CSS style sheet Md (Material Design)
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load user to have $user->conf loaded (not done into main because of NOLOGIN constant defined)
if (empty($user->id) && ! empty($_SESSION['dol_login'])) $user->fetch('',$_SESSION['dol_login']);


// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));	// If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');

$path='';    	// This value may be used in future for external module to overwrite theme
$theme='eldy';	// Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define image path files and other constants
$fontlist='Open Sans,sans-serif';    //$fontlist='Verdana,Helvetica,Arial,sans-serif';
$img_head='';
$img_button=dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1);
$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_hide_leftmenu=$conf->dol_hide_leftmenu;
$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;
$dol_no_mouse_hover=$conf->dol_no_mouse_hover;
$dol_use_jmobile=$conf->dol_use_jmobile;


//$conf->global->THEME_ELDY_ENABLE_PERSONALIZED=0;
//$user->conf->THEME_ELDY_ENABLE_PERSONALIZED=0;
//var_dump($user->conf->THEME_ELDY_RGB);

// Colors
$colorbackhmenu1='140,150,180';      // topmenu
$colorbackvmenu1='255,255,255';      // vmenu
$colorbacktitle1='230,230,230';      // title of array
$colorbacktabcard1='255,255,255';  // card
$colorbacktabactive='234,234,234';
$colorbacklineimpair1='255,255,255';    // line impair
$colorbacklineimpair2='255,255,255';    // line impair
$colorbacklinepair1='242,242,242';    // line pair
$colorbacklinepair2='248,248,248';    // line pair
$colorbacklinepairhover='238,246,252';    // line pair
$colorbackbody='255,255,255';
$colortext='50,50,130';
$fontsize='13';
$fontsizesmaller='11';
$usegradient=1;

// Case of option always editable
if (! isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) $conf->global->THEME_ELDY_TOPMENU_BACK1=join(',',colorStringToArray('#37474F'));   // topmenu (140,160,185)
if (! isset($conf->global->THEME_ELDY_BACKTITLE1)) $conf->global->THEME_ELDY_BACKTITLE1='140,150,180';       	// title of arrays TO MATCH ELDY (140,160,185)
//if (! isset($conf->global->THEME_ELDY_BACKTITLE1)) $conf->global->THEME_ELDY_BACKTITLE1='230,230,230';     // title of arrays TO MATCH BOOTSTRAP
//if (! isset($conf->global->THEME_ELDY_USE_HOVER)) $conf->global->THEME_ELDY_USE_HOVER='';			            // color for links
    
// Fields not yet editable by default
if (empty($conf->global->THEME_ELDY_ENABLE_PERSONALIZED))
{
	// Cas of option editable if option THEME_ELDY_ENABLE_PERSONALIZED is on
    // 90A4AE, 607D8B, 455A64, 37474F
	$conf->global->THEME_ELDY_VERMENU_BACK1='255,255,255';    // vmenu
    $conf->global->THEME_ELDY_BACKTABCARD1='255,255,255';     // card
    $conf->global->THEME_ELDY_BACKTABACTIVE='234,234,234';
    $conf->global->THEME_ELDY_LINEIMPAIR1='255,255,255';
    $conf->global->THEME_ELDY_LINEIMPAIR2='255,255,255';
    $conf->global->THEME_ELDY_LINEPAIR1='242,242,242';
    $conf->global->THEME_ELDY_LINEPAIR2='248,248,248';
    $conf->global->THEME_ELDY_LINEPAIRHOVER='238,246,252';
    $conf->global->THEME_ELDY_USE_HOVER=='238,246,252';
    $conf->global->THEME_ELDY_BACKBODY='255,255,255';
    
    $conf->global->THEME_ELDY_TEXT='50,50,130';
    $conf->global->THEME_ELDY_FONT_SIZE1='13';
    $conf->global->THEME_ELDY_FONT_SIZE2='11';
}
//var_dump($conf->global->THEME_ELDY_BACKBODY);
//var_dump($user->conf->THEME_ELDY_BACKTITLE1);

// Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
$colorbackhmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$conf->global->THEME_ELDY_TOPMENU_BACK1)   :(empty($user->conf->THEME_ELDY_TOPMENU_BACK1)?$colorbackhmenu1:$user->conf->THEME_ELDY_TOPMENU_BACK1);
$colorbackvmenu1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$conf->global->THEME_ELDY_VERMENU_BACK1)   :(empty($user->conf->THEME_ELDY_VERMENU_BACK1)?$colorbackvmenu1:$user->conf->THEME_ELDY_VERMENU_BACK1);
$colorbacktitle1     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTITLE1)   ?$colorbacktitle1:$conf->global->THEME_ELDY_BACKTITLE1)      :(empty($user->conf->THEME_ELDY_BACKTITLE1)?$colorbacktitle1:$user->conf->THEME_ELDY_BACKTITLE1);
$colorbacktabcard1   =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABCARD1) ?$colorbacktabcard1:$conf->global->THEME_ELDY_BACKTABCARD1)  :(empty($user->conf->THEME_ELDY_BACKTABCARD1)?$colorbacktabcard1:$user->conf->THEME_ELDY_BACKTABCARD1);
$colorbacktabactive  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$conf->global->THEME_ELDY_BACKTABACTIVE):(empty($user->conf->THEME_ELDY_BACKTABACTIVE)?$colorbacktabactive:$user->conf->THEME_ELDY_BACKTABACTIVE);
$colorbacklineimpair1=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR1)  ?$colorbacklineimpair1:$conf->global->THEME_ELDY_LINEIMPAIR1):(empty($user->conf->THEME_ELDY_LINEIMPAIR1)?$colorbacklineimpair1:$user->conf->THEME_ELDY_LINEIMPAIR1);
$colorbacklineimpair2=empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEIMPAIR2)  ?$colorbacklineimpair2:$conf->global->THEME_ELDY_LINEIMPAIR2):(empty($user->conf->THEME_ELDY_LINEIMPAIR2)?$colorbacklineimpair2:$user->conf->THEME_ELDY_LINEIMPAIR2);
$colorbacklinepair1  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR1)    ?$colorbacklinepair1:$conf->global->THEME_ELDY_LINEPAIR1)    :(empty($user->conf->THEME_ELDY_LINEPAIR1)?$colorbacklinepair1:$user->conf->THEME_ELDY_LINEPAIR1);
$colorbacklinepair2  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIR2)    ?$colorbacklinepair2:$conf->global->THEME_ELDY_LINEPAIR2)    :(empty($user->conf->THEME_ELDY_LINEPAIR2)?$colorbacklinepair2:$user->conf->THEME_ELDY_LINEPAIR2);
$colorbacklinepairhover  =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_LINEPAIRHOVER)    ?$colorbacklinepairhover:$conf->global->THEME_ELDY_LINEPAIRHOVER)    :(empty($user->conf->THEME_ELDY_LINEPAIRHOVER)?$colorbacklinepairhover:$user->conf->THEME_ELDY_LINEPAIRHOVER);
$colorbackbody       =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_BACKBODY)     ?$colorbackbody:$conf->global->THEME_ELDY_BACKBODY)          :(empty($user->conf->THEME_ELDY_BACKBODY)?$colorbackbody:$user->conf->THEME_ELDY_BACKBODY);
$colortext           =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_TEXT)         ?$colortext:$conf->global->THEME_ELDY_TEXT)                  :(empty($user->conf->THEME_ELDY_TEXT)?$colortext:$user->conf->THEME_ELDY_TEXT);
$fontsize            =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE1)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE1)             :(empty($user->conf->THEME_ELDY_FONT_SIZE1)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE1);
$fontsizesmaller     =empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED)?(empty($conf->global->THEME_ELDY_FONT_SIZE2)   ?$fontsize:$conf->global->THEME_ELDY_FONT_SIZE2)             :(empty($user->conf->THEME_ELDY_FONT_SIZE2)?$fontsize:$user->conf->THEME_ELDY_FONT_SIZE2);

// Hover color
$colorbacklinepairhover=((! isset($conf->global->THEME_ELDY_USE_HOVER) || (string) $conf->global->THEME_ELDY_USE_HOVER === '0')?'':($conf->global->THEME_ELDY_USE_HOVER === '1'?'edf4fb':$conf->global->THEME_ELDY_USE_HOVER));
if (! empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED))
{
    $colorbacklinepairhover=((! isset($user->conf->THEME_ELDY_USE_HOVER) || $user->conf->THEME_ELDY_USE_HOVER === '0')?'':($user->conf->THEME_ELDY_USE_HOVER === '1'?'edf4fb':$user->conf->THEME_ELDY_USE_HOVER));
}

// Format color value to match expected format (may be 'FFFFFF' or '255,255,255')
$colorbackhmenu1=join(',',colorStringToArray($colorbackhmenu1));
$colorbackvmenu1=join(',',colorStringToArray($colorbackvmenu1));
$colorbacktitle1=join(',',colorStringToArray($colorbacktitle1));
$colorbacktabcard1=join(',',colorStringToArray($colorbacktabcard1));
$colorbacktabactive=join(',',colorStringToArray($colorbacktabactive));
$colorbacklineimpair1=join(',',colorStringToArray($colorbacklineimpair1));
$colorbacklineimpair2=join(',',colorStringToArray($colorbacklineimpair2));
$colorbacklinepair1=join(',',colorStringToArray($colorbacklinepair1));
$colorbacklinepair2=join(',',colorStringToArray($colorbacklinepair2));
if ($colorbacklinepairhover != '') $colorbacklinepairhover=join(',',colorStringToArray($colorbacklinepairhover));
$colorbackbody=join(',',colorStringToArray($colorbackbody));
$colortext=join(',',colorStringToArray($colorbackvmenu1));

// Set text color to black or white
$tmppart=explode(',',$colorbackhmenu1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) $colortextbackhmenu='FFF';
else $colortextbackhmenu='111';
$tmppart=explode(',',$colorbackvmenu1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) { $colortextbackvmenu='FFF'; }
else { $colortextbackvmenu='111'; }
$tmppart=explode(',',$colorbacktitle1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 360) { $colortexttitle='FFF'; $colorshadowtitle='888'; }
else { $colortexttitle='111'; $colorshadowtitle='FFF'; }
$tmppart=explode(',',$colorbacktabcard1);
$tmpval=(! empty($tmppart[1]) ? $tmppart[1] : '')+(! empty($tmppart[2]) ? $tmppart[2] : '')+(! empty($tmppart[3]) ? $tmppart[3] : '');
if ($tmpval <= 340) { $colortextbacktab='FFF'; }
else { $colortextbacktab='111'; }


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
print 'dol_hide_topmenu='.$dol_hide_topmenu."\n";
print 'dol_hide_leftmenu='.$dol_hide_leftmenu."\n";
print 'dol_optimize_smallscreen='.$dol_optimize_smallscreen."\n";
print 'dol_no_mouse_hover='.$dol_no_mouse_hover."\n";
print 'dol_use_jmobile='.$dol_use_jmobile."\n";
print 'dol_screenwidth='.$_SESSION['dol_screenwidth']."\n";
print 'dol_screenheight='.$_SESSION['dol_screenheight']."\n";
print '*/'."\n";

if (! empty($conf->dol_optimize_smallscreen)) $fontsize=11;
?>

/* ============================================================================== */
/* Default styles                                                                 */
/* ============================================================================== */


body {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background: rgb(<?php print $colorbackbody; ?>);
<?php } ?>
	color: #101010;
	font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
    <?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
}

a:link, a:visited, a:hover, a:active { font-family: <?php print $fontlist ?>; font-weight: bold; color: #4A4A4A; text-decoration: none;  }

a:hover { text-decoration: underline; color: #000000;}

<?php if (empty($dol_use_jmobile)) { ?>

input:focus, textarea:focus, button:focus, select:focus {
    box-shadow: 0 0 4px #8091BF;
}
textarea.cke_source:focus
{
	box-shadow: none;
}

input, input.flat, textarea, textarea.flat, form.flat select select, select.flat, .dataTables_length label select {
    font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
	background: #FDFDFD;
    border: 1px solid #C0C0C0;
    margin: 0px 0px 0px 0px;
}

input, textarea, select {
	border-radius:2px;
	border:solid 1px rgba(0,0,0,.3);
	border-top:solid 1px rgba(0,0,0,.3);
	border-bottom:solid 1px rgba(0,0,0,.2);
	/* box-shadow: 1px 1px 1px rgba(0,0,0,.2) inset;*/
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
<?php } ?>

select.flat, form.flat select {
	font-weight: normal;
}
input:disabled {
	background:#f4f4f4;
}

input.liste_titre {
	box-shadow: none !important;
}
input.removedfile {
	padding: 0px !important;
	border: 0px !important;
	vertical-align: text-bottom;
}
textarea:disabled {
	background:#f4f4f4;
}
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
fieldset { border: 1px solid #AAAAAA !important; box-shadow: 2px 2px 3px #f4f4f4; }


.button, sbmtConnexion {
    font-family: <?php print $fontlist ?>;
	border-color: #c5c5c5;
	border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);
	display: inline-block;
	padding: 4px 14px;
	margin-bottom: 0;
	margin-top: 0;
	text-align: center;
	cursor: pointer;
	color: #333333;
	text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe6e6e6', GradientType=0);
	border-color: #e6e6e6 #e6e6e6 #bfbfbf;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
	border: 1px solid #bbbbbb;
	border-bottom-color: #a2a2a2;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	-moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
}
.button:focus  {
	-moz-box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	-webkit-box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	box-shadow: 0px 0px 6px 1px rgba(0, 0, 60, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
}
.button:hover   {
	-moz-box-shadow: 0px 0px 6px 1px rgba(0, 0, 0, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	-webkit-box-shadow: 0px 0px 6px 1px rgba(0, 0, 0, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
	box-shadow: 0px 0px 6px 1px rgba(0, 0, 0, 0.2), 0px 0px 0px rgba(60,60,60,0.1);
}
.button:disabled {
	opacity: 0.4;
    filter: alpha(opacity=40); /* For IE8 and earlier */
    box-shadow: none;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    cursor: auto;
}
.buttonRefused {
	pointer-events: none;
   	cursor: default;
	opacity: 0.4;
    filter: alpha(opacity=40); /* For IE8 and earlier */
    box-shadow: none;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
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
    -moz-box-shadow: none !important;
    -webkit-box-shadow: none !important;
    box-shadow: none !important;
	-moz-border-radius:0px !important;
	-webkit-border-radius:0px !important;
	border-radius:0px !important;
}
.maxwidthsearch {		/* Max width of column with the search picto */
	width: 54px;
}

.valignmiddle {
	vertical-align: middle;
}
.centpercent {
	width: 100%;
}
textarea.centpercent {
	width: 96%;
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
.nowrap {
	white-space: <?php print ($dol_optimize_smallscreen?'normal':'nowrap'); ?>;
}
.nobold {
	font-weight: normal !important;
}
.nounderline {
    text-decoration: none;
}
.cursorpointer {
	cursor: pointer;
}
.badge {
	display: inline-block;
	min-width: 10px;
	padding: 2px 5px;
	font-size: 10px;
	font-weight: 700;
	line-height: 0.9em;
	color: #fff;
	text-align: center;
	white-space: nowrap;
	vertical-align: baseline;
	background-color: #777;
	border-radius: 10px;
}
.movable {
	cursor: move;
}

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
div.divsearchfield {
	float: <?php print $left; ?>;
	margin-<?php print $right; ?>: 12px;
}
div.confirmmessage {
	padding-top: 6px;
}
/* Style to move picto into left of button */
/*
.buttonactionview {
	padding-left: 15px;
}
.pictoactionview {
	padding-left: 10px;
	margin-right: -24px;
	z-index: 999999;
}
*/
.googlerefreshcal {
	padding-top: 4px;
	padding-bottom: 4px;
}

/* ============================================================================== */
/* Styles to hide objects                                                         */
/* ============================================================================== */

.clearboth  { clear:both; }
.hideobject { display: none; }
.minwidth100 { min-width: 100px; }
.minwidth200 { min-width: 200px; }
.minwidth300 { min-width: 300px; }
.maxwidth100 { max-width: 100px; }
.maxwidth200 { max-width: 200px; }
.maxwidth300 { max-width: 300px; }
<?php if (! empty($dol_optimize_smallscreen)) { ?>
.hideonsmartphone { display: none; }
.noenlargeonsmartphone { width : 50px !important; display: inline !important; }
.maxwidthonsmartphone { max-width: 100px; }
.maxwidth100onsmartphone { max-width: 100px; }
.maxwidth200onsmartphone { max-width: 200px; }
.maxwidth300onsmartphone { max-width: 300px; }
<?php } ?>
.linkobject { cursor: pointer; }
<?php if (GETPOST("optioncss") == 'print') { ?>
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
/*	min-width: 100%;
	position: relative;
	heigth: 52px;
	background: #f00;*/
}
#id-left {
	min-height: 100%;
	position: relative;
	width: 193px;
}
#id-right {	/* This must stay id-right and not be replaced with echo $right */
	width: 100%;
	padding-left: 194px;
	padding-top: 12px;
}

.side-nav {
	background: #FFF;
	border-right: 1px solid rgba(0,0,0,0.2);
	bottom: 0;
	color: #333;
	display: block;
	font-family: "RobotoDraft","Roboto",sans-serif;
	left: 0;
	position: fixed;
	top: 50px;
	z-index: 4;
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
	overflow-x: hidden;
	overflow-y: auto;
}
.side-nav-vert {
	margin-left: 194px;
}


div.fiche {
	margin-<?php print $left; ?>: <?php print (GETPOST("optioncss") == 'print'?6:((empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))?($dol_hide_leftmenu?'4':'20'):'24')); ?>px;
	margin-<?php print $right; ?>: <?php print (GETPOST("optioncss") == 'print'?8:(empty($conf->dol_optimize_smallscreen)?'12':'4')); ?>px;
	<?php if (! empty($conf->dol_hide_leftmenu) && ! empty($conf->dol_hide_topmenu)) print 'margin-top: 4px;'; ?>
}

div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichethirdleft {
	<?php if ($conf->browser->layout != 'phone')   { print "float: ".$left.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone')   { print "width: 35%;\n"; } ?>
	<?php if ($conf->browser->layout == 'phone') { print "padding-bottom: 6px;\n"; } ?>
}
div.fichetwothirdright {
	<?php if ($conf->browser->layout != 'phone')   { print "float: ".$right.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone')   { print "width: 65%;\n"; } ?>
	<?php if ($conf->browser->layout == 'phone') { print "padding-bottom: 6px\n"; } ?>
}
div.fichehalfleft {
	<?php if ($conf->browser->layout != 'phone')   { print "float: ".$left.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone')   { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if ($conf->browser->layout != 'phone')   { print "float: ".$right.";\n"; } ?>
	<?php if ($conf->browser->layout != 'phone')   { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if ($conf->browser->layout != 'phone')   { print "padding-".$left.": 16px;\n"; }
	else print "margin-top: 10px;\n"; ?>
}
.containercenter {
	display : table;
	margin : 0px auto;
}

#pictotitle {
	margin-right: 8px;
	margin-bottom: 4px;
}
.pictosubstatus {
    padding-left: 2px;
    padding-right: 2px;
}
.pictowarning {
    padding-left: 3px;
}
.colorthumb {
	padding-left: 1px !important;
	padding-right: 1px;
	padding-top: 1px;
	padding-bottom: 1px;
}
div.attacharea {
	padding-top: 10px;
	padding-bottom: 10px;
}


/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

<?php
$minwidthtmenu=66;
$heightmenu=48;			/* height of top menu, part with image */
$heightmenu2=48;        /* height of top menu, ârt with login  */
$disableimages = 0;
$maxwidthloginblock = 110;
if (! empty($conf->global->THEME_ELDY_DISABLE_IMAGE) || $dol_optimize_smallscreen) { $disableimages = 1; $maxwidthloginblock = 180; }
?>

div#tmenu_tooltip {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
<?php } else { ?>
	background: rgb(<?php echo $colorbackhmenu1 ?>);
	/*
	background-image: linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(128,128,128,.3) 100%);
	background-image: -webkit-gradient( linear, left top, left bottom, color-stop(0, rgba(255,255,255,.3)), color-stop(1, rgba(128,128,128,.3)) );
	*/
<?php } ?>
}

div#tmenu_tooltip {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
<?php } else { ?>
	/* padding-<?php echo $right; ?>: <? echo ($maxwidthloginblock - 10); ?>px; */
<?php } ?>
}

div.tmenudiv {
<?php if (GETPOST("optioncss") == 'print') {  ?>
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
	/* box-shadow: 0 0 6px rgba(0, 0, 0, .4) !important; */
}
ul.tmenu li {
	background: rgb(<?php echo $colorbackhmenu1 ?>);

	background-image: linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -o-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(255,255,255,.3) 0%, rgba(0,0,0,.3) 100%);
	background-image: -webkit-gradient( linear, left top, left bottom, color-stop(0, rgba(255,255,255,.3)), color-stop(1, rgba(0,0,0,.3)) );
}
li.tmenu, li.tmenusel {
	<?php print $minwidthtmenu?'min-width: '.$minwidthtmenu.'px;':''; ?>
	text-align: center;
	vertical-align: bottom;
	<?php if (empty($conf->global->MAIN_MENU_INVERT)) { ?>
	float: <?php print $left; ?>;
    height: <?php print $heightmenu; ?>px;
    <?php } ?>
	position:relative;
	display: block;
	padding: 0px 0px 2px 0px;
	margin: 0px 0px 0px 0px;
	font-weight: normal;
}
li.tmenusel, li.tmenu:hover {
    background-image: -o-linear-gradient(bottom, rgba(250,250,250,0.3) 0%, rgba(0,0,0,0.3) 100%) !important;
    background-image: -moz-linear-gradient(bottom, rgba(250,250,250,0.3) 0%, rgba(0,0,0,0.3) 100%) !important;
    background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0) 100%) !important;
    background-image: -ms-linear-gradient(bottom, rgba(250,250,250,0.3) 0%, rgba(0,0,0,0.3) 100%) !important;
    background-image: linear-gradient(bottom, rgba(250,250,250,0.3) 0%, rgba(0,0,0,0.3) 100%) !important;
	background: rgb(<?php echo $colorbackhmenu1 ?>);
	/* background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/nav-overlay3.png',1); ?>) 50% 0 repeat-x !important; Nicer but problem when menu wrap on 2 lines */
}
.tmenuend .tmenuleft { width: 0px; }
/* .tmenuend { display: none; } */
div.tmenuleft
{
	float: <?php print $left; ?>;
	margin-top: 0px;
	<?php if (empty($conf->dol_optimize_smallscreen)) { ?>
	width: 5px;
	height: <?php print $heightmenu+4; ?>px;
	/* background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menutab-r.png',1); ?>) 0 -6px no-repeat; */
	<?php } ?>
}
div.tmenucenter
{
	padding-top: 2px;
	padding-left: 0px;
	padding-right: 0px;
    height: <?php print $heightmenu; ?>px;
    width: 100%;
}
div.menu_titre {
	padding-bottom: 2px;
}
.mainmenuaspan
{
	padding-right: 4px;
}

div.mainmenu {
	position : relative;
	background-repeat:no-repeat;
	background-position:center top;
	height: <?php echo ($heightmenu-19); ?>px;
	margin-left: 0px;
	min-width: 40px;
}

/* Do not load menu img if hidden to save bandwidth */
<?php if (empty($dol_hide_topmenu)) { ?>

div.mainmenu.home{
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/home.png',1) ?>);
	background-position-x: middle;
}

div.mainmenu.accountancy {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/money.png',1) ?>);
}

div.mainmenu.agenda {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/agenda.png',1) ?>);
}

div.mainmenu.bank {
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/bank.png',1) ?>);
}

div.mainmenu.cashdesk {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/pointofsale.png',1) ?>);
}

div.mainmenu.companies {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/company.png',1) ?>);
}

div.mainmenu.commercial {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/commercial.png',1) ?>);
}

div.mainmenu.ecm {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/ecm.png',1) ?>);
}

div.mainmenu.externalsite {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/externalsite.png',1) ?>);
}

div.mainmenu.ftp {
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/tools.png',1) ?>);
}

div.mainmenu.hrm {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/holiday.png',1) ?>);
}

div.mainmenu.members {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/members.png',1) ?>);
}

div.mainmenu.products {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/products.png',1) ?>);
	margin-left: 10px;
}

div.mainmenu.project {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/project.png',1) ?>);
}

div.mainmenu.tools {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/tools.png',1) ?>);
}

<?php
// Add here more div for other menu entries. moduletomainmenu=array('module name'=>'name of class for div')

$moduletomainmenu=array('user'=>'','syslog'=>'','societe'=>'companies','projet'=>'project','propale'=>'commercial','commande'=>'commercial',
	'produit'=>'products','service'=>'products','stock'=>'products',
	'don'=>'accountancy','tax'=>'accountancy','banque'=>'accountancy','facture'=>'accountancy','compta'=>'accountancy','accounting'=>'accountancy','adherent'=>'members','import'=>'tools','export'=>'tools','mailing'=>'tools',
	'contrat'=>'commercial','ficheinter'=>'commercial','deplacement'=>'commercial',
	'fournisseur'=>'companies',
	'barcode'=>'','fckeditor'=>'','categorie'=>'',
);
$mainmenuused='home';
foreach($conf->modules as $val)
{
	$mainmenuused.=','.(isset($moduletomainmenu[$val])?$moduletomainmenu[$val]:$val);
}
//var_dump($mainmenuused);
$mainmenuusedarray=array_unique(explode(',',$mainmenuused));

$generic=1;
$divalreadydefined=array('home','companies','products','commercial','accountancy','project','tools','members','agenda','holiday','bookmark','cashdesk','ecm','geoipmaxmind','gravatar','clicktodial','paypal','webservices');
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
	// Img file not found
	if (! $found)
	{
		$url=dol_buildpath($path.'/theme/'.$theme.'/img/menus/generic'.$generic.".png",1);
		$found=1;
		if ($generic < 4) $generic++;
		print "/* A mainmenu entry but img file ".$val.".png not found (check /".$val."/img/".$val.".png), so we use a generic one */\n";
	}
	if ($found)
	{
		print "div.mainmenu.".$val." {\n";
		print "	background-image: url(".$url.");\n";
		print "}\n";
	}
}
// End of part to add more div class css
?>

<?php
}	// End test if $dol_hide_topmenu
?>

.tmenuimage {
    padding:0 0 0 0 !important;
    margin:0 0px 0 0 !important;
    <?php if ($disableimages) { ?>
    	display: none;
    <?php } ?>
}



/* Login */

.bodylogin
{
	background: #f0f0f0;
}
.login_vertical_align {
	padding: 10px;
}
form#login {
	margin-top: <?php echo $dol_optimize_smallscreen?'30':'60' ?>px;
	margin-bottom: 30px;
	font-size: 13px;
	vertical-align: middle;
}
.login_table_title {
	max-width: 530px;
	color: #888888;
	text-shadow: 1px 1px 1px #FFF;
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

	-moz-box-shadow: 0 4px 23px 5px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);
	-webkit-box-shadow: 0 4px 23px 5px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);
	box-shadow: 0 4px 23px 5px rgba(0, 0, 0, 0.2), 0 2px 6px rgba(60,60,60,0.15);
	/*-moz-box-shadow: 3px 2px 20px #CCC;
    -webkit-box-shadow: 3px 2px 20px #CCC;
    box-shadow: 3px 2px 20px #CCC;*/

	border-radius: 4px;
	border:solid 1px rgba(80,80,80,.4);

	border-top:solid 1px f8f8f8;
}
.login_main_message {
	text-align: center;
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
#img_logo {
	max-width: 200px;
	max-height: 100px;
}

div.login_block {
	border-right: 1px solid rgba(0,0,0,0.3);
    padding-top: 5px;
    padding-bottom: 3px;
	<?php print $left; ?>: 0;
	top: 0px;
	position: fixed;
	font-weight: bold;
	z-index: 10;
	text-align: center;
	vertical-align: middle;
	background: #FFF;
	width: 193px;
	height: 42px;
	<?php if (GETPOST("optioncss") == 'print') { ?>
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
.login_block_elem {
	float: right;
	vertical-align: top;
	padding: 0px 0px 0px 4px !important;
	height: 16px;
}
.login_block_elem_name {
	margin-top: 5px;
}
.alogin, .alogin:hover {
	color: #888 !important;
	font-weight: normal !important;
	font-size: <?php echo $fontsizesmaller; ?>px !important;
}
.alogin:hover {
	text-decoration:underline !important;
}
img.login, img.printer, img.entity {
	/* padding: 0px 0px 0px 4px; */
	/* margin: 0px 0px 0px 8px; */
	text-decoration: none;
	color: white;
	font-weight: bold;
}
img.loginphoto {
	border-radius: 5px;
	margin-top: -4px;
	width: 20px;
	height: 20px;
}

.span-icon-user {
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/object_user.png',1); ?>) no-repeat scroll 7px 7px;
}
.span-icon-password {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/lock.png',1); ?>);
	background-repeat: no-repeat;
}
/*
.span-icon-user input, .span-icon-password input {
	margin-right: 30px;
}
*/

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
    width: 174px;
}

.vmenu {
	margin-left: 4px;
	<?php if (GETPOST("optioncss") == 'print') { ?>
    display: none;
	<?php } ?>
}

.menu_contenu { padding-top: 4px; padding-bottom: 3px;}
#menu_contenu_logo { padding-right: 4px; }

a.vmenu:link, a.vmenu:visited, a.vmenu:hover, a.vmenu:active { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; }
font.vmenudisabled  { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; color: #93a5aa; }
a.vmenu:link, a.vmenu:visited { color: #<?php echo $colortextbackvmenu; ?>; }

a.vsmenu:link, a.vsmenu:visited, a.vsmenu:hover, a.vsmenu:active, span.vsmenu { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #202020; margin: 1px 1px 1px 8px; }
font.vsmenudisabled { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #93a5aa; }
a.vsmenu:link, a.vsmenu:visited { color: #<?php echo $colortextbackvmenu; ?>; }
font.vsmenudisabledmargin { margin: 1px 1px 1px 8px; }

a.help:link, a.help:visited, a.help:hover, a.help:active { font-size:<?php print $fontsizesmaller ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #666666; text-decoration: none; }


div.blockvmenupair, div.blockvmenuimpair, div.blockvmenubookmarks
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

div.blockvmenusearch
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
    border-bottom: 1px solid #f4f4f4;
}

div.blockvmenuhelp
{
<?php if (empty($conf->dol_optimize_smallscreen)) { ?>
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: center;
	text-decoration: none;
    padding-left: 0px;
    padding-right: 3px;
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
    background-image: -o-linear-gradient(bottom, rgba(200,200,200,0.1) 0%, rgba(255,255,255,0.3) 120%) !important;
    background-image: -moz-linear-gradient(bottom, rgba(200,200,200,0.1) 0%, rgba(255,255,255,0.3) 120%) !important;
    background-image: -webkit-linear-gradient(bottom, rgba(200,200,200,0.1) 0%, rgba(255,255,255,0.3) 120%) !important;
    background-image: -ms-linear-gradient(bottom, rgba(200,200,200,0.1) 0%, rgba(255,255,255,0.3) 120%) !important;
    background-image: linear-gradient(bottom, rgba(200,200,200,0.1) 0%, rgba(255,255,255,0.3) 120%) !important;

    background: #FFF;
    background-repeat: repeat-x !important;
	border: 1px solid #CCC !important;

    -moz-border-radius: 2px 2px 2px 2px !important;
	-webkit-border-radius: 2px 2px 2px 2px !important;
	border-radius: 2px 2px 2px 2px !important;
    -moz-box-shadow: 2px 2px 4px #f4f4f4;
    -webkit-box-shadow: 2px 2px 4px #f4f4f4;
    box-shadow: 2px 2px 4px #f4f4f4;

    padding: 0 4px 0 4px !important;
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
    background: #f4f4f4;
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
    background-color: #f4f4f4;
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
    background-color: #f4f4f4;
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
    text-align: <?php print $left; ?>;
    margin-left: 6px !important;
    margin-right: 6px !important;
    clear:both;
	height:100%;
}
div.tabsElem { margin-top: 8px; }		/* To avoid overlap of tabs when not browser */

div.tabBar {
    color: #<?php echo $colortextbacktab; ?>;
    padding-top: 9px;
    padding-left: <?php echo ($dol_optimize_smallscreen?'4':'8'); ?>px;
    padding-right: <?php echo ($dol_optimize_smallscreen?'4':'8'); ?>px;
    padding-bottom: 8px;
    margin: 0px 0px 14px 0px;
    -moz-border-radius:3px;
    -webkit-border-radius: 3px;
	border-radius: 3px;
    border-right: 1px solid #BBB;
    border-bottom: 1px solid #BBB;
    border-left: 1px solid #BBB;
    border-top: 1px solid #CCC;
	width: auto;

	background: rgb(<?php echo $colorbacktabcard1; ?>);

	<?php if (empty($dol_optimize_smallscreen)) { ?>
    -moz-box-shadow: 3px 3px 4px #f4f4f4;
    -webkit-box-shadow: 3px 3px 4px #f4f4f4;
    box-shadow: 3px 3px 4px #f4f4f4;
	<?php } ?>
}

div.tabsAction {
    margin: 20px 0em 10px 0em;
    padding: 0em 0em;
    text-align: right;
}


a.tabTitle {
/*    background: #657090;
    color: white;*/
    color:rgba(0,0,0,.5);
    margin-right:10px;
    text-shadow:1px 1px 1px #ffffff;
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
    padding: 4px 6px 2px 6px;
    margin: 0px 6px;
    text-decoration: none;
    white-space: nowrap;
}

a.tab:link, a.tab:visited, a.tab:hover, a.tab#active {
	font-family: <?php print $fontlist ?>;
	padding: 5px 12px 5px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;

    /*-moz-border-radius:3px 3px 0px 0px;
	-webkit-border-radius:3px 3px 0px 0px;
	border-radius:3px 3px 0px 0px;

	-moz-box-shadow: 0 -1px 4px rgba(0,0,0,.1);
	-webkit-box-shadow: 0 -1px 4px rgba(0,0,0,.1);
	box-shadow: 0 -1px 4px rgba(0,0,0,.1);

	border-bottom: none;
	border-right: 1px solid #CCCCCC;
	border-left: 1px solid #f4f4f4;
	border-top: 1px solid #D8D8D8;
    */

	background-image: none !important;
}

.tabactive, a.tab#active {
	color: #<?php echo $colortextbacktab; ?> !important;
	background: rgb(<?php echo $colorbacktabcard1; ?>) !important;
	-moz-box-shadow: 0 -1px 4px rgba(0,0,0,.1);
	-webkit-box-shadow: 0 -1px 4px rgba(0,0,0,.1);
	box-shadow: 0 -1px 4px rgba(0,0,0,.1);

    border-right: 1px solid #CCCCCC;
	border-left: 1px solid #f4f4f4;
	border-top: 1px solid #D8D8D8;

	-moz-border-radius:3px 3px 0px 0px;
	-webkit-border-radius:3px 3px 0px 0px;
	border-radius:3px 3px 0px 0px;

}
a.tab:hover
{
	/*
	background: rgba(<?php echo $colorbacktabcard1; ?>, 0.5)  url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/nav-overlay3.png',1); ?>) 50% 0 repeat-x;
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
    -moz-border-radius:3px 3px 0px 0px;
	-webkit-border-radius:3px 3px 0px 0px;
	border-radius:3px 3px 0px 0px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

div.divButAction { margin-bottom: 1.4em; }

.butAction, .butAction:link, .butAction:visited, .butAction:hover, .butAction:active, .butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
	text-decoration: none;
	white-space: nowrap;
	padding: 0.4em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
	font-family: <?php print $fontlist ?>;

	font-weight: bold;
	background: white;
	border: 1px solid #8CACBB;
	color: #434956;
    -moz-border-radius:0px 2px 0px 2px;
	-webkit-border-radius:0px 2px 0px 2px;
	border-radius:0px 2px 0px 2px;
    -moz-box-shadow: 2px 2px 3px #f4f4f4;
    -webkit-box-shadow: 2px 2px 3px #f4f4f4;
    box-shadow: 2px 2px 3px #f4f4f4;
}

.butAction:hover   {
	background: #dee7ec;
}

.butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
	border: 1px solid #997777;
}

.butActionDelete:hover {
	background: #FFe7ec;
}

.butActionRefused {
	text-decoration: none !important;
	white-space: nowrap !important;
	cursor: not-allowed;
	padding: 0.4em <?php echo ($dol_optimize_smallscreen?'0.4':'0.7'); ?>em;
	margin: 0em <?php echo ($dol_optimize_smallscreen?'0.7':'0.9'); ?>em;
    font-family: <?php print $fontlist ?> !important;

	font-weight: bold !important;
	background: white !important;
	border: 1px solid #AAAAAA !important;
	color: #AAAAAA !important;
    -moz-border-radius:0px 2px 0px 2px;
	-webkit-border-radius:0px 2px 0px 2px;
	border-radius:0px 2px 0px 2px;
    -moz-box-shadow: 3px 3px 4px #f4f4f4;
    -webkit-box-shadow: 3px 3px 4px #f4f4f4;
    box-shadow: 3px 3px 4px #f4f4f4;
}

<?php if (! empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)) { ?>
.butActionRefused {
	display: none;
}
<?php } ?>

span.butAction, span.butActionDelete {
	cursor: pointer;
}

/* Prepare for bootstrap look
.butAction, .butActionDelete, .butActionRefused {
	border-color: #c5c5c5;
	border-color: rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.15) rgba(0, 0, 0, 0.25);
	display: inline-block;
	padding: 4px 14px;
	margin-bottom: 0;
	line-height: 20px;
	text-align: center;
	vertical-align: middle;
	cursor: pointer;
	color: #333333;
	text-shadow: 0 1px 1px rgba(255, 255, 255, 0.75);
	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffffffff', endColorstr='#ffe6e6e6', GradientType=0);
	border-color: #e6e6e6 #e6e6e6 #bfbfbf;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
	border: 1px solid #bbbbbb;
	border-bottom-color: #a2a2a2;
	-webkit-border-radius: 2px;
	-moz-border-radius: 2px;
	border-radius: 2px;
	-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	-moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
	box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 1px 2px rgba(0, 0, 0, 0.05);
}

.butAction {
	color: #ffffff;
	text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
	background-color: #006dcc;
	background-image: -moz-linear-gradient(top, #0088cc, #0044cc);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#0088cc), to(#0044cc));
	background-image: -webkit-linear-gradient(top, #0088cc, #0044cc);
	background-image: -o-linear-gradient(top, #0088cc, #0044cc);
	background-image: linear-gradient(to bottom, #0088cc, #0044cc);
	background-repeat: repeat-x;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ff0088cc', endColorstr='#ff0044cc', GradientType=0);
	border-color: #0044cc #0044cc #002a80;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
}

.butActionDelete {
	color: #ffffff;
	text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.25);
	background-color: #cc6d00;
	background-image: -moz-linear-gradient(top, #cc8800, #cc4400);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#cc8800), to(#cc4400));
	background-image: -webkit-linear-gradient(top, #cc8800, #cc4400);
	background-image: -o-linear-gradient(top, #cc8800, #cc4400);
	background-image: linear-gradient(to bottom, #cc8800, #cc4400);
	background-repeat: repeat-x;
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#ffcc8800', endColorstr='#ffcc4400', GradientType=0);
	border-color: #cc4400 #cc4400 #802a00;
	border-color: rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.1) rgba(0, 0, 0, 0.25);
	filter: progid:DXImageTransform.Microsoft.gradient(enabled = false);
}
a.butAction:link, a.butAction:visited, a.butAction:hover, a.butAction:active {
	color: #FFFFFF;
}
End bootstrap */



/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.allwidth {
	width: 100%;
}

#undertopmenu {
	background-repeat: repeat-x;
	margin-top: <?php echo ($dol_hide_topmenu?'6':'0'); ?>px;
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
tr.nocellnopadd td.nobordernopadding, tr.nocellnopadd td.nocellnopadd
{
	border: 0px;
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


table.border, table.dataTable, .table-border, .table-border-col, .table-key-border-col, .table-val-border-col, div.border {
	border: 1px solid #f4f4f4;
	border-collapse: collapse;
	padding: 1px 2px 1px 3px;			/* t r b l */
}

.border tbody tr, .border tbody tr td {
	height: 20px;
}
div.tabBar table.border tr {
	height: 20px;
}

table.border td, div.border div div.tagtd {
	padding: 2px 2px 2px 2px;
	border: 1px solid #f4f4f4;
	border-collapse: collapse;
}

td.border, div.tagtable div div.border {
	border-top: 1px solid #000000;
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	border-left: 1px solid #000000;
}

.table-key-border-col {
	width: 25%;
	vertical-align:top;
}
.table-val-border-col {
	width:auto;
}

/* Main boxes */

table.noborder, table.formdoc, div.noborder {
	width: 100%;

	border-collapse: separate !important;
	border-spacing: 0px;

	border-width: 1px;
	border-color: #BBB;
	border-style: solid;

	margin: 0px 0px 2px 0px;

	-moz-box-shadow: 2px 2px 4px #CCC;
	-webkit-box-shadow: 2px 2px 4px #CCC;
	box-shadow: 2px 2px 4px #CCC;

	-moz-border-radius: 0.1em;
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
	height: 26px;
}

table.noborder th, table.noborder td, div.noborder form, div.noborder form div {
	padding: 1px 2px 1px 3px;			/* t r b l */
}

table.nobordernopadding {
	border-collapse: collapse !important;
	border: 0px;
}
table.nobordernopadding tr {
	border: 0px !important;
	padding: 0px 0px !important;
}
table.nobordernopadding tr td {
	border: 0px;
	padding: 0 3px 0 0;
}
table.border tr td table.nobordernopadding tr td {
	padding-top: 0px;
	padding-bottom: 0px;
}
td.borderright {
    border: none;	/* to erase value for table.nobordernopadding td */
	border-right-width: 1px !important;
	border-right-color: #BBB !important;
	border-right-style: solid !important;
}

/* For lists */

table.liste {
	width: 100%;
	border-collapse: collapse;
	border-top-color: #FEFEFE;

	border-right-width: 1px;
	border-right-color: #CCC;
	border-right-style: solid;

/*
	border-bottom-width: 1px;
	border-bottom-color: #BBBBBB;
	border-bottom-style: solid;
*/
	border-left-width: 1px;
	border-left-color: #CCC;
	border-left-style: solid;

	margin-bottom: 2px;
	margin-top: 0px;

    -moz-box-shadow: 0px 3px 4px #CCC;
    -webkit-box-shadow: 0px 3px 4px #CC;
    box-shadow: 0px 3px 4px #CCC;
}
table.liste td {
	padding-right: 2px;
}

.tagtable, .table-border { display: table; }
.tagtr, .table-border-row  { display: table-row; }
.tagtd, .table-border-col, .table-key-border-col, .table-val-border-col { display: table-cell; }

/* Pagination */
div.refidpadding  {
	padding-top: <?php print empty($conf->dol_use_jmobile)?'8':'12'; ?>px;
}
div.refid  {
	padding-top: <?php print empty($conf->dol_use_jmobile)?'5':'12'; ?>px;
	font-weight: bold;
  	color: #766;
  	font-size: 120%;
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
<?php if (empty($conf->dol_use_jmobile)) { ?>
  padding-top: 6px;
  padding-bottom: 5px;
<?php } ?>
}
.pagination {
  display: inline-block;
  padding-left: 0;
  border-radius: 4px;
}

div.pagination li.pagination a,
div.pagination li.pagination span {
<?php if (empty($conf->dol_use_jmobile)) { ?>
  padding: 6px 12px;
  margin-left: -1px;
  line-height: 1.42857143;
  color: #000;
  text-decoration: none;

	background-color: #f5f5f5;
	background-image: -moz-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -webkit-gradient(linear, 0 0, 0 100%, from(#ffffff), to(#e6e6e6));
	background-image: -webkit-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: -o-linear-gradient(top, #ffffff, #e6e6e6);
	background-image: linear-gradient(to bottom, #ffffff, #e6e6e6);
	background-repeat: repeat-x;
<?php } ?>
}
div.pagination li.pagination span.inactive {
  cursor: default;
}
div.pagination li.litext a {
border: none;
  padding-right: 10px;
  padding-left: 4px;
  font-weight: bold;
}
<?php if (! empty($conf->dol_use_jmobile)) { ?>
div.pagination li.litext {
  padding-top: 13px;
  vertical-align: top;
}
<?php } ?>
<?php if (empty($conf->dol_use_jmobile)) { ?>
div.pagination li.noborder a:hover {
  border: none;
  background-color: transparent;
}
div.pagination li a,
div.pagination li span {
  background-color: #fff;
  border: 1px solid #ddd;
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
  background-color: <?php $colorbackhmenu1 ?>;
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
<?php } ?>
div.pagination li.pagination .active {
  text-decoration: underline;
}
div.pagination li.paginationafterarrows {
	margin-left: 10px;
}

/* Prepare to remove class pair - impair
.noborder > tbody > tr:nth-child(even) td {
	background: linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
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
	background: linear-gradient(bottom, rgb(<?php echo $colorbacklinepair1; ?>) 85%, rgb(<?php echo $colorbacklinepair2; ?>) 100%);
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

/* Set the color for hover lines */
.odd:hover, .impair:hover, .even:hover, .pair:hover, .even:hover, .pair:hover, table.dataTable tr.even:hover, table.dataTable tr.odd:hover, .box_pair:hover, .box_impair:hover
{
<?php if ($colorbacklinepairhover) { ?>
	background: rgb(<?php echo $colorbacklinepairhover; ?>) !important;
<?php } else { ?>
	/* background: rgba(0, 0, 0, 0.05) !important; */
<?php } ?>
}

.odd, .impair, .nohover .odd:hover, .nohover .impair:hover, tr.odd td.nohover, tr.impair td.nohover,  tr.box_pair td.nohover, tr.box_impair td.nohover
{
	font-family: <?php print $fontlist ?>;
	border: 0px;
	margin-bottom: 1px;
	color: #202020;
	min-height: 18px; /* seems to not be used */
	
	background: #<?php echo colorArrayToHex(colorStringToArray($colorbacklineimpair1)); ?>;
}
#GanttChartDIV {
	background: #<?php echo colorArrayToHex(colorStringToArray($colorbacklineimpair1)); ?>;
}

.even, .pair, .nohover .even:hover, .nohover .pair:hover, tr.even td.nohover, tr.pair td.nohover {
	font-family: <?php print $fontlist ?>;
	margin-bottom: 1px;
	color: #202020;

	background-color: #<?php echo colorArrayToHex(colorStringToArray($colorbacklinepair1)); ?>;
}
table.dataTable tr.odd {
	background-color: #<?php echo colorArrayToHex(colorStringToArray($colorbacklinepair1)); ?> !important;
}

/* For no hover style */
table.nohover tr.impair, table.nohover tr.pair, table.nohover tr.impair td, table.nohover tr.pair td, tr.nohover td {
	background-color: #<?php echo colorArrayToHex(colorStringToArray($colorbacklineimpair1)); ?> !important;
}
tr.nohoverpair td {
	background-color: #<?php echo colorArrayToHex(colorStringToArray($colorbacklinepair1)); ?> !important;
}

table.dataTable td {
    padding: 5px 2px 5px 3px !important;
}
tr.even td, tr.pair td, tr.odd td, tr.impair td, form.odd div.tagtd, form.impair div.tagtd, form.pair div.tagtd, div.impair div.tagtd, div.pair div.tagtd, div.liste_titre div.tagtd {
    padding: 5px 2px 5px 3px;
    border-bottom: 1px solid #f4f4f4;
}
tr.even td .nobordernopadding tr td, tr.pair td .nobordernopadding tr td, tr.impair td .nobordernopadding tr td, tr.odd td .nobordernopadding tr td {
    border-bottom: 0px !important;
}
td.nobottom, td.nobottom {
    border-bottom: 0px !important;
}
div.liste_titre .tagtd {
	vertical-align: middle;
}
div.liste_titre {
	min-height: 26px !important;	/* We cant use height because it's a div and it should be higher if content is more. but min-height doe not work either for div */
}
tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel, table.dataTable.tr
{
	height: 26px !important;
}
div.liste_titre, tr.liste_titre, tr.liste_titre_sel, form.liste_titre, form.liste_titre_sel, table.dataTable thead tr
{
    /* TO MATCH BOOTSTRAP */
	background: #ddd;
	color: #000 !important;

	/* TO MATCH ELDY */
	/*
	background: rgb(<?php echo $colorbacktitle1; ?>);
	<?php if ($usegradient) { ?>
	background-image: -o-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -moz-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -ms-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	<?php } ?>
	font-weight: bold;
	*/
	
    color: #<?php echo $colortexttitle; ?>;
    font-family: <?php print $fontlist ?>;
    border-bottom: 1px solid #FDFFFF;
    text-align: <?php echo $left; ?>;
}
tr.liste_titre th, th.liste_titre, form.liste_titre div, div.liste_titre
{
	border-bottom: 1px solid #FDFFFF;
}
tr.liste_titre th, th.liste_titre, tr.liste_titre td, td.liste_titre, form.liste_titre div, div.liste_titre
{
    font-family: <?php print $fontlist ?>;
    font-weight: bold;
    vertical-align: middle;
}
tr.liste_titre th a, th.liste_titre a, tr.liste_titre td a, td.liste_titre a, form.liste_titre div a, div.liste_titre a {
	text-shadow: none !important;
}
.liste_titre td a {
	text-shadow: none !important;
	color: #<?php echo $colortexttitle; ?>;
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

.noborder tr.liste_total, .noborder tr.liste_total td, tr.liste_total, form.liste_total {
	background: #F0F0F0;
}
.noborder tr.liste_total td, tr.liste_total td, form.liste_total div {
    border-top: 1px solid #f4f4f4;
    color: #332266;
    font-weight: normal;
    white-space: nowrap;
    padding: 4px;
}


.tableforservicepart1 .impair, .tableforservicepart1 .pair, .tableforservicepart2 .impair, .tableforservicepart2 .pair {
	background: #FFF;
}
.tableforservicepart1 tbody tr td, .tableforservicepart2 tbody tr td {
	border-bottom: none;
}

.margintable td {
	border: 0px !important;
}

/* Disable shadows */
.noshadow {
	-moz-box-shadow: 0px 0px 0px #f4f4f4 !important;
	-webkit-box-shadow: 0px 0px 0px #f4f4f4 !important;
	box-shadow: 0px 0px 0px #f4f4f4 !important;
}

div.tabBar .noborder {
	-moz-box-shadow: 0px 0px 0px #f4f4f4 !important;
	-webkit-box-shadow: 0px 0px 0px #f4f4f4 !important;
	box-shadow: 0px 0px 0px #f4f4f4 !important;
}


/*
 *  Boxes
 */

.boxstats {
    <?php print "float: ".$left.";\n"; ?>
    margin: 3px;
    padding: 3px;
	/*-moz-box-shadow: 3px 3px 4px #f4f4f4;
    -webkit-box-shadow: 3px 3px 4px #f4f4f4;
    box-shadow: 3px 3px 4px #f4f4f4;
    margin-bottom: 8px !important;*/
    border: 1px solid #AAA;
    text-align: center;
    border-radius: 2px;
}
.boxstats:hover {
	background-color: #<?php echo colorArrayToHex(colorStringToArray($colorbacklinepair1)); ?>;
}

.boxtable {
    -moz-box-shadow: 3px 3px 4px #f4f4f4;
    -webkit-box-shadow: 3px 3px 4px #f4f4f4;
    box-shadow: 3px 3px 4px #f4f4f4;
    margin-bottom: 8px !important;
}


.box {
    padding-right: 0px;
    padding-left: 0px;
    padding-bottom: 4px;
}

tr.box_titre {
    height: 26px !important;
    
    /* TO MATCH BOOTSTRAP */
	background: #ddd;
	color: #000 !important;

	/* TO MATCH ELDY */
	/*
	background: rgb(<?php echo $colorbacktitle1; ?>);
	<?php if ($usegradient) { ?>
	background-image: -o-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -moz-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -ms-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	<?php } ?>
	*/
	
    background-repeat: repeat-x;
    color: #<?php echo $colortexttitle; ?>;
    font-family: <?php print $fontlist ?>, sans-serif;
    font-weight: bold;
    border-bottom: 1px solid #FDFFFF;
    white-space: nowrap;
}

tr.box_titre td.boxclose {
	width: 30px;
}

tr.box_impair {
    background: -o-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
    background: -moz-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
    background: -webkit-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
    background: -ms-linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
    background: linear-gradient(bottom, rgb(<?php echo $colorbacklineimpair1; ?>) 85%, rgb(<?php echo $colorbacklineimpair2; ?>) 100%);
    font-family: <?php print $fontlist ?>;
}

tr.box_pair {
    font-family: <?php print $fontlist ?>;

	background-color: #f9f9f9;
}

tr.box_pair td, tr.box_impair td {
	padding: 4px;
    border-bottom: 1px solid #f4f4f4;
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
.ok      { color: #114466; }
.warning { color: #887711; }
.error   { color: #550000 !important; font-weight: bold; }

div.ok {
  color: #114466;
}

div.warning {
  color: #302020;
  padding: 0.3em 0.3em 0.3em 0.3em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #e0d0b0;
  -moz-border-radius:3px;
  -webkit-border-radius: 3px;
  border-radius: 3px;
  background: #EFDF9A;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.3em 0.3em 0.3em 0.3em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #DC9CAB;
  -moz-border-radius:3px;
  -webkit-border-radius: 3px;
  border-radius: 3px;
  background: #EFCFCF;
}

/* Info admin */
div.info {
  color: #302010;
  padding: 0.4em 0.4em 0.4em 0.4em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #DFBF9A;
  -moz-border-radius:3px;
  -webkit-border-radius: 3px;
  border-radius:3px;
  background: #EFCFAA;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
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

.dolgraphtitle { margin-top: 6px; margin-bottom: 4px; }
.dolgraphtitlecssboxes { margin: 0px; }
.legendColorBox, .legendLabel { border: none !important; }
div.dolgraph div.legend, div.dolgraph div.legend div { background-color: rgba(255,255,255,0) !important; }
div.dolgraph div.legend table tbody tr { height: auto; }
td.legendColorBox { padding: 2px 2px 2px 0 !important; }
td.legendLabel { padding: 2px 2px 2px 0 !important; }

.photo {
	border: 0px;
}
.photowithmargin {
	margin-bottom: 2px;
	margin-top: 2px;
}
.photointooltip {
	margin-top: 8px;
	float: left;
	/*text-align: center; */
}
.photodelete {
	margin-top: 6px !important;
}

.logo_setup
{
	content:url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/logo_setup.svg',1) ?>);
	display: inline-block;
}

div.titre {
	font-family: <?php print $fontlist ?>;
	font-weight: bold;
	color: rgb(<?php print $colortext; ?>);
	text-decoration: none;
	text-shadow: 1px 1px 2px #FFFFFF;
	<?php print (empty($conf->dol_optimize_smallscreen)?'':'margin-top: 4px;'); ?>
}

#dolpaymenttable { width: 600px; font-size: 13px; }
#tablepublicpayment { border: 1px solid #CCCCCC !important; width: 100%; }
#tablepublicpayment .CTableRow1  { background-color: #F0F0F0 !important; }
#tablepublicpayment tr.liste_total { border-bottom: 1px solid #CCCCCC !important; }
#tablepublicpayment tr.liste_total td { border-top: none; }

#divsubscribe { width: 700px; }
#tablesubscribe { width: 100%; }


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
  -moz-box-shadow: 0 15px 10px #777;
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

/* ============================================================================== */
/* Formulaire confirmation (When HTML is used)                                    */
/* ============================================================================== */

table.valid {
    border-top: solid 1px #E6E6E6;
    border-<?php print $left; ?>: solid 1px #E6E6E6;
    border-<?php print $right; ?>: solid 1px #444444;
    border-bottom: solid 1px #555555;
	padding-top: 0px;
	padding-left: 0px;
	padding-right: 0px;
	padding-bottom: 0px;
	margin: 0px 0px;
    background: #D5BAA8;
}

.validtitre {
    background: #D5BAA8;
	font-weight: bold;
}


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
background-color: #EFCFAA;
opacity: 1;
-moz-border-radius:0px;
-webkit-border-radius: 0px;
border-radius: 0px;
}

#tiptip_content {
-moz-border-radius:0px;
-webkit-border-radius: 0px;
border-radius: 0px;
background-color: rgb(255,255,255);
/*    background-color: rgb(252,248,246);
	background-color: rgba(252,248,246,0.95);*/
	line-height: 1.4em;
	min-width: 200px;
}

/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */

img.datecallink { padding-left: 2px !important; padding-right: 2px !important; }

.ui-datepicker-trigger {
	vertical-align: middle;
	cursor: pointer;
}

.bodyline {
	-moz-border-radius: 4px;
	-webkit-border-radius: 4px;
	border-radius: 4px;
	border: 1px #E4ECEC outset;
	padding: 0px;
	margin-bottom: 5px;
}
table.dp {
    width: 180px;
    background-color: #FFFFFF;
    border-top: solid 2px #f4f4f4;
    border-<?php print $left; ?>: solid 2px #f4f4f4;
    border-<?php print $right; ?>: solid 1px #222222;
    border-bottom: solid 1px #222222;
    padding: 0px;
	border-spacing: 0px;
	border-collapse: collapse;
}
.dp td, .tpHour td, .tpMinute td{padding:2px; font-size:10px;}
/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color:#b3c5cc;
	color:white;
	font-size:11px;
	cursor:auto;
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
    font-size:9px;
    border-width:0px;
    color:#0B63A2;
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
.cal_current_month_peruserleft { border-top: 0; border-left: solid 3px #6C7C7B; border-right: 0; border-bottom: solid 1px #E0E0E0; }
.cal_current_month_oneday { border-right: solid 1px #E0E0E0; }
.cal_other_month   { border-top: 0; border-left: solid 1px #C0C0C0; border-right: 0; border-bottom: solid 1px #C0C0C0; }
.cal_other_month_peruserleft { border-top: 0; border-left: solid 3px #6C7C7B !important; border-right: 0; }
.cal_current_month_right { border-right: solid 1px #E0E0E0; }
.cal_other_month_right   { border-right: solid 1px #C0C0C0; }
.cal_other_month   { opacity: 0.6; background: #EAEAEA; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { opacity: 0.6; background: #EEEEEE; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border-left: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month_peruserleft { background: #FFFFFF; border-left: solid 3px #6C7C7B; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FDFDF0; border-left: solid 1px #E0E0E0; border-bottom: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today_peruser { background: #FDFDF0; border-right: solid 1px #E0E0E0; border-bottom: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today_peruser_peruserleft { background: #FDFDF0; border-left: solid 3px #6C7C7B; border-right: solid 1px #E0E0E0; border-bottom: solid 1px #E0E0E0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past          { }
.cal_peruser       { padding: 0px; }
.cal_impair        { background: #F8F8F8; }
.cal_today_peruser_impair { background: #F8F8F0; }
.peruser_busy      { background: #CC8888; }
.peruser_notbusy   { background: #EEDDDD; opacity: 0.5; }
table.cal_event    { border: none; border-collapse: collapse; margin-bottom: 1px; -webkit-border-radius: 3px; border-radius: 3px;
						-webkit-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 1px 2px rgba(0, 0, 0, 0.25);
						moz-box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 1px 2px rgba(0, 0, 0, 0.25);
						box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25), 0 1px 2px rgba(0, 0, 0, 0.25);
						background: -webkit-gradient(linear, left top, left bottom, from(#006aac), to(#00438d));
						min-height: 20px;
						}
table.cal_event td { border: none; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event td.cal_event { padding: 4px 4px !important; }
table.cal_event td.cal_event_right { padding: 4px 4px !important; }
ul.cal_event       { padding-right: 2px; padding-top: 1px; border: none; list-style-type: none; margin: 0 auto; padding-left: 0px; padding-start: 0px; -khtml-padding-start: 0px; -o-padding-start: 0px; -moz-padding-start: 0px; -webkit-padding-start: 0px; }
li.cal_event       { border: none; list-style-type: none; }
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; color:rgba(255,255,255,.75); }
.cal_event_busy      { }
.cal_peruserviewname { max-width: 100px; height: 22px; }

.topmenuimage {
	background-size: 28px auto;
}

/* ============================================================================== */
/*  Ajax - Liste deroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-widget-content { border: solid 1px rgba(0,0,0,.3); background: #fff !important; }

.ui-autocomplete-loading { background: white url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/working.gif',1) ?>) right center no-repeat; }
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
.ui-button { margin-left: -2px; <?php print (preg_match('/chrome/',$conf->browser->name)?'padding-top: 1px;':''); ?> }
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

.cke_editable
{
	margin: 5px !important;
}
.cke_editor table, .cke_editor tr, .cke_editor td
{
    border: 0px solid #FF0000 !important;
}
span.cke_skin_kama { padding: 0 !important; }
.cke_wrapper { padding: 4px !important; }
a.cke_dialog_ui_button
{
    font-family: <?php print $fontlist ?> !important;
	background-image: url(<?php echo $img_button ?>) !important;
	background-position: bottom !important;
    border: 1px solid #C0C0C0 !important;
    -moz-border-radius:0px 2px 0px 2px !important;
	-webkit-border-radius:0px 2px 0px 2px !important;
	border-radius:0px 2px 0px 2px !important;
    -moz-box-shadow: 3px 3px 4px #f4f4f4 !important;
    -webkit-box-shadow: 3px 3px 4px #f4f4f4 !important;
    box-shadow: 3px 3px 4px #f4f4f4 !important;
}
.cke_dialog_ui_hbox_last
{
	vertical-align: bottom ! important;
}
.cke_editable
{
	line-height: 1.4 !important;
	margin: 6px !important;
}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {
    height: 72px !important;
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
    border: solid 1px #f4f4f4;
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
.ecmjqft LI.wait { font-weight:normal; background: url(<?php echo dol_buildpath('/theme/'.$theme.'/img/working.gif',1); ?>) left top no-repeat; }


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
    -moz-box-shadow: 3px 3px 4px #888 !important;
    -webkit-box-shadow: 3px 3px 4px #888 !important;
    box-shadow: 3px 3px 4px #888 !important;
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
/*  Datatable                                                                     */
/* ============================================================================== */

table.dataTable tr.odd td.sorting_1, table.dataTable tr.even td.sorting_1 {
  background: none !important;
}
.sorting_asc  { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_asc.png',1); ?>') no-repeat center right !important; }
.sorting_desc { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_desc.png',1); ?>') no-repeat center right !important; }
.sorting_asc_disabled  { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_asc_disabled.png',1); ?>') no-repeat center right !important; }
.sorting_desc_disabled { background: url('<?php echo dol_buildpath('/theme/'.$theme.'/img/sort_desc_disabled.png',1); ?>') no-repeat center right !important; }
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
.paging_full_numbers a.paginate_button_disabled:hover {
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
	filter: Alpha(Opacity=35);
	background-image: none;
}

/* ============================================================================== */
/*  Select2                                                                       */
/* ============================================================================== */

.select2-choice,
.select2-drop.select2-drop-above.select2-drop-active,
.select2-container-active .select2-choice,
.select2-container-active .select2-choices,
.select2-dropdown-open.select2-drop-above .select2-choice,
.select2-dropdown-open.select2-drop-above .select2-choices,
.select2-container-multi.select2-container-active .select2-choices
{
	border: 1px solid #aaa;
}
.select2-disabled
{
	color: #888;
}
.select2-drop-active
{
	border: 1px solid #aaa;
	border-top: none;
}
a span.select2-chosen
{
	font-weight: normal !important;
}
.select2-container .select2-choice {
	background-image: none;
	height: 24px;
	line-height: 24px;
}
.select2-choices .select2-search-choice {
  border: 1px solid #aaa !important;
}
.select2-results .select2-no-results, .select2-results .select2-searching, .select2-results .select2-ajax-error, .select2-results .select2-selection-limit
{
	background: #FFFFFF;
}
.select2-container-multi.select2-container-disabled .select2-choices {
	background-color: #FFFFFF;
	background-image: none;
	border: none;
	cursor: default;
}
.select2-container-multi .select2-choices .select2-search-choice {
  margin-bottom: 3px;
}
/* To emulate select 2 style */
.select2-container-multi-dolibarr .select2-choices-dolibarr .select2-search-choice-dolibarr {
  padding: 2px 5px 1px 5px;
  margin: 0 0 2px 3px;
  position: relative;
  line-height: 13px;
  color: #333;
  cursor: default;
  border: 1px solid #aaaaaa;
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
  filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#eeeeee', endColorstr='#f4f4f4', GradientType=0);
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
	<?php if (! empty($dol_use_jmobile)) { ?>
	font-size: 13px;
	<?php } else { ?>
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

.ui-link {
	color: rgb(<?php print $colortext; ?>);
}
.liste_titre .ui-link {
	color: #<?php print $colortexttitle; ?> !important;
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
.ui-bar-b, .lilevel0 {
	border: 1px solid #5f5f7a !important;
    background: rgb(<?php echo $colorbacktitle1; ?>);
    background-repeat: repeat-x;

	background-image: -o-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -moz-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -webkit-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: -ms-linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
	background-image: linear-gradient(bottom, rgba(0,0,0,0.3) 0%, rgba(250,250,250,0.3) 100%);
    font-weight: bold;

    color: #<?php echo $colortexttitle; ?> !important;
}
.alilevel0 {
    color: #<?php echo $colortexttitle; ?> !important;
	text-shadow: 1px 0px 1px #<?php echo $colorshadowtitle; ?>;
}
.ui-btn-icon-right {
	border-right: 1px solid #ccc !important;
}
.ui-body-c {
	border: 1px solid #ccc;
	text-shadow: none;
}
.ui-btn-up-c, .ui-btn-hover-c {
	border: 1px solid #ccc;
	text-shadow: none;
}
.ui-body-c .ui-link, .ui-body-c .ui-link:visited, .ui-body-c .ui-link:hover {
	color: rgb(<?php print $colortext; ?>);
}
.ui-btn-up-c .vsmenudisabled {
	color: #<?php echo $colorshadowtitle; ?> !important;
	text-shadow: none !important;
}
.ui-controlgroup-horizontal .ui-btn.ui-first-child {
-webkit-border-top-left-radius: 6px;
border-top-left-radius: 6px;
}
.ui-controlgroup-horizontal .ui-btn.ui-last-child {
-webkit-border-top-right-radius: 6px;
border-top-right-radius: 6px;
}
.alilevel1 {
    color: #<?php echo $colortexttitle; ?> !important;
	text-shadow: 1px 0px 1px #<?php echo $colorshadowtitle; ?>;
}
.lilevel1 {
	background-image: -webkit-gradient(linear,left top,left bottom,from( #f4f4f4 ),to( #d1d1d1 )) !important;
	background-image: -webkit-linear-gradient( #f4f4f4,#d1d1d1 ) !important;
	background-image: -moz-linear-gradient( #f4f4f4,#d1d1d1 ) !important;
	background-image: -ms-linear-gradient( #f4f4f4,#d1d1d1 ) !important;
	background-image: -o-linear-gradient( #f4f4f4,#d1d1d1 ) !important;
	background-image: linear-gradient( #f4f4f4,#d1d1d1 ) !important;
}
.lilevel2
{
	padding-left: 22px;
}
.lilevel3
{
	padding-left: 54px;
}



/* The theme for public pages */
.public_body {
	margin: 20px;
}
.public_border {
	border: 1px solid #888;
}



/* CSS style used for small screen */

.imgopensurveywizard
{
	padding: 0 4px 0 4px;
}
@media only screen and (max-width: 767px)
{
	.mainmenuaspan {
    	display: none;
    }
    li.tmenu, li.tmenusel {
    	min-width: 32px;
    }
    div.mainmenu {
    	min-width: auto;
    }
    .topmenuimage {
    	background-size: 26px auto;
    	margin-top: 8px;
	}
	div.tmenuleft {
		display: none;
	}
	
	.imgopensurveywizard { width:95%; height: auto; }
	
	#tooltip {
		position: absolute;
		width: <?php print dol_size(350,'width'); ?>px;
	}	
}
@media only screen and (max-width: 570px)
{
    div.mainmenu {
    	min-width: 20px;
    }
	.topmenuimage {
    	background-size: 20px auto;
    }
    
	#tooltip {
		position: absolute;
		width: <?php print dol_size(300,'width'); ?>px;
	}	
}



<?php
if (is_object($db)) $db->close();
