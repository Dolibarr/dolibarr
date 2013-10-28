<?php
/* Copyright (C) 2004-2013	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2010	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2011		Juanjo Menent        <jmenent@2byte.es>
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
 *
 * FIXME: This is a bugged theme:
 * No wrapping of top menu entries when window not large enough
 * Not compatible with jmobile
 */

/**
 *		\file       htdocs/theme/bureau2crea/style.css.php
 *		\brief      Fichier de style CSS du theme bureau2crea
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

require_once '../../main.inc.php';

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


$path='';    			// This value may be used in future for external module to overwrite theme
$theme='bureau2crea';	// Value of theme
if (! empty($conf->global->MAIN_OVERWRITE_THEME_RES)) { $path='/'.$conf->global->MAIN_OVERWRITE_THEME_RES; $theme=$conf->global->MAIN_OVERWRITE_THEME_RES; }

// Define image path files
$fontlist='arial, sans-serif, verdana, helvetica';
//$fontlist='Verdana,Helvetica,Arial,sans-serif';

$dol_hide_topmenu=$conf->dol_hide_topmenu;
$dol_hide_leftmenu=$conf->dol_hide_leftmenu;
$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;
$dol_no_mouse_hover=$conf->dol_no_mouse_hover;
$dol_use_jmobile=$conf->dol_use_jmobile;

$colorshadowtitle='000';
?>

/* ============================================================================== */
/* Styles par defaut                                                              */
/* ============================================================================== */

body {
/*	background-color: #FFFFFF; */
	color: #101010;
	<?php if (empty($dol_use_jmobile) || 1==1) { ?>
	font-size: <?php print $fontsize ?>px;
    <?php } ?>
    font-family: <?php print $fontlist ?>;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
    <?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
}

#mainbody .main_box {
	position: absolute;
    width: 100%;
    margin: 0px;
    }

#mainbody .connexion_box {
	position: absolute;
    top: 2px;
    right: 5px;
    height: 12px;
    text-align: left;
    }

#mainbody .connexion_box .login, #mainbody .connexion_box .printer {
	margin-left: 10px;
    font-size: 10px;
    line-height: 14px;
    padding: 0px !important;
    padding-right: 10px !important;
    }

#mainbody .connexion_box .login a {
	color: #333;
    text-decoration: none;
    }

#mainbody .connexion_box table {
	margin-left: 10px;
    display: block;
    }

#mainbody .content_box {
	margin: 0px 20px 20px 20px;
    }

#ad_banner {
	text-align: center;
	vertical-align: bottom;
}

a:link, a:visited, a:hover, a:active { font-family: <?php print $fontlist ?>; font-weight: bold; color: #000000; text-decoration: none; }

input, input.flat, textarea, textarea.flat, form.flat select, select.flat {
    font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
	background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat, form.flat select {
	font-weight: normal;
}
input:disabled {
	background:#ddd;
}
textarea:disabled {
	background:#ddd;
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

input.button[type=submit] {
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_btnGreen.jpg',1); ?>);
    display: block;
    height: 18px;
    line-height: 16px;
    padding: 0px 10px 0px 10px;
    margin: 0px;
    background-repeat: repeat-x;
    /*border: 2px solid #336600;*/
    color: #FFFFFF;
    cursor: pointer;
    font-size: 10px;
    display: inline;
}
::-webkit-input-placeholder { color:#ccc; }
::-moz-placeholder { color:#ccc; } /* firefox 19+ */
:-ms-input-placeholder { color:#ccc; } /* ie */
input:-moz-placeholder { color:#ccc; }

<?php if (! empty($dol_use_jmobile)) { ?>
legend { margin-bottom: 8px; }
<?php } ?>

.button {
    font-family: <?php print $fontlist ?>;
	border: 0px;
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1); ?>);
	background-position: bottom;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
}
.button:focus  {
    font-family: <?php print $fontlist ?>;
	color: #222244;
	border: 0px;
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1); ?>);
	background-position: bottom;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
}
.buttonajax {
    font-family: <?php print $fontlist ?>;
	border: 0px;
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1); ?>);
	background-position: bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
form {
    padding: 0em 0em 0em 0em;
    margin: 0em 0em 0em 0em;
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
	min-width: 10px;
}


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

div.leftContent {
	margin-left: 20px !important;
    width: 220px !important;
}

div.fiche {
	margin-<?php print $left; ?>: <?php print empty($conf->dol_optimize_smallscreen)?'10':'2'; ?>px;
	margin-<?php print $right; ?>: <?php print empty($conf->dol_optimize_smallscreen)?'10':''; ?>px;
    padding: 0px;
    position: relative;
    height: auto;
}

div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichethirdleft {
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "width: 35%;\n"; } ?>
}
div.fichetwothirdright {
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "float: ".$right.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "width: 65%;\n"; } ?>
}
div.fichehalfleft {
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "float: ".$right.";\n"; } ?>
	<?php if (empty($conf->dol_optimize_smallscreen)) { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if (empty($conf->dol_optimize_smallscreen))   { print "padding-left: 16px;\n"; }
	else print "margin-top: 10px;\n"; ?>
}


/* ============================================================================== */
/* Barre de redimensionnement menu                                                */
/* ============================================================================== */

.ui-layout-resizer-west-open {
	/*left: 240px !important;*/
}


/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

<?php
if (! empty($conf->dol_optimize_smallscreen))
{
	$minwidthtmenu=70;
	$heightmenu=39;
}
else
{
	$minwidthtmenu=70;
	$heightmenu=39;
}
?>

/* This theme is bugged. If width not large enough, menu are not wrapped on next line
div#tmenu_tooltip {
	padding-right: 100px;
}
*/

div.tmenu {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
<?php } else { ?>
    position: relative;
    display: block;
    white-space: nowrap;
    border-left: 0px;
    padding: 0px;
    margin: 5px 0px 10px 0px;
    font-size: 13px;
    background-image : url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_mainNav.jpg',1); ?>);
    background-repeat: no-repeat;
    background-color: #996644;
    height: 22px;
    border-bottom: 2px solid #842F00;
<?php } ?>
}



/*
div.mainmenu {
	position : relative;
	color: white;
	background-repeat:no-repeat;
	background-position:center top;
	height: <?php echo ($heightmenu-19); ?>px;
	margin-left: 0px;
}
*/

<?php if (empty($conf->dol_optimize_smallscreen)) {

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

$mainmenuusedarray=array();	// Disable

$generic=1;
$divalreadydefined=array('home','companies','products','commercial','accountancy','project','tools','members','shop','agenda','ecm','cashdesk');
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
			$url=dol_buildpath($path.'/'.$val.'/img/'.$val.'.png',1);
			$found=1;
			break;
		}
	}
	// Img file not found
	if (! $found && $generic <= 4)
	{
		$url=dol_buildpath($path.'/theme/'.$theme.'/img/menus/generic'.$generic.'.png',1);
		$found=1;
		$generic++;
	}
	if ($found)
	{
		print "/* A mainmenu entry but img file ".$val.".png not found, so we use a generic one */\n";
		print "div.mainmenu.".$val." {\n";
		print "	background-image: url(".$url.");\n";
		print "	height:28px;\n";
		print "}\n";
	}
}
// End of part to add more div class css
?>

<?php
}	// End test if not phone
?>

ul.tmenu {
	padding: 0px;
    margin: 0px 5px 0px 5px;
	list-style: none;
    width: auto;
    height: 22px;
}

li.tmenu, li.tmenusel {
	float: <?php print $left; ?>;
	position:relative;
	display: block;
	padding: 0px;
    margin: 0px 2px 0px 0px;
}

li.tmenu span, li.tmenusel span {
	margin: 0px 10px 0px 10px;
    }

.tmenuimage {
	margin: 0 !important;
	padding: 0 !important;
}

li.tmenu a, li.tmenusel a {
	position: relative;
	display: block;
    font-size: 12px;
    font-family: Geneva, Verdana, sans-serif;
    line-height: 20px;
    color: #FFF;
    font-weight: normal;
    float: <?php print $left; ?>;
}

li.tmenu a:hover {
	color: #FFFFFF;
    background-color: #D45416;
}

li.tmenu a.tmenusel, li.tmenu a.tmenusel:hover, li.tmenusel a.tmenusel, li.tmenusel a.tmenusel:hover {
	color: #842F00;
    font-weight: bold;
    background-color: #FFF;
    font-weight: normal;
}

li.tmenu .tmenusel, li.tmenusel .tmenusel {
    background: #FFFFFF;
}

li.tmenusel {
    background-image : url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_tmenusel_btnD.jpg',1); ?>);
    background-position: right;
}

li.tmenusel a:hover {
	color: #474747;
}

li.tmenu a.tmenudisabled {
	color: #CCC;
}

/* --- end nav --- */




/* Login */

form#login {
	margin-top: <?php echo $dol_optimize_smallscreen?'30':'60' ?>px;
	margin-bottom: 30px;
	font-size: 13px;
}
.login_table_title {
	margin-left: 10px;
	margin-right: 10px;
	max-width: 540px;
	color: #888888;
	text-shadow: 1px 1px 1px #FFF;
}
.login_table label {
	text-shadow: 1px 1px 1px #FFF;
}
.login_table {
	margin-left: 10px;
	margin-right: 10px;
	padding:12px;
	max-width: 540px;
	border: 1px solid #C0C0C0;
	background-color: #E0E0E0;

    -moz-box-shadow: 4px 4px 4px #CCC;
    -webkit-box-shadow: 4px 4px 4px #CCC;
    box-shadow: 4px 4px 4px #CCC;

	border-radius: 12px;
	border:solid 1px rgba(168,168,168,.4);
	border-top:solid 1px f8f8f8;
	background-color: #f8f8f8;
	background-image: -o-linear-gradient(top, rgba(240,240,240,.3) 0%, rgba(192,192,192,.3) 100%);
	background-image: -moz-linear-gradient(top, rgba(240,240,240,.3) 0%, rgba(192,192,192,.3) 100%);
	background-image: -webkit-linear-gradient(top, rgba(240,240,240,.3) 0%, rgba(192,192,192,.3) 100%);
	background-image: -ms-linear-gradient(top, rgba(240,240,240,.3) 0%, rgba(192,192,192,.3) 100%);
	background-image: linear-gradient(top, rgba(240,240,240,.3) 0%, rgba(192,192,192,.3) 100%);
}
#img_securitycode {
	border: 1px solid #DDDDDD;
}
#img_logo {
	max-width: 200px;
}

div.login_block {
    width: 180px;
	position: absolute;
	<?php print $right; ?>: 5px;
	top: 2px;
	font-weight: bold;
	<?php if (GETPOST("optioncss") == 'print') { ?>
	display: none;
    color: #FFF;
	<?php } ?>
}

div.login_block table {
	display: inline;
}

div#login_left, div#login_right {
	display: inline-block;
	min-width: 220px;
	text-align: center;
	vertical-align: middle;
}

div.login {
	white-space:nowrap;
	padding: <?php echo ($conf->dol_optimize_smallscreen?'0':'8')?>px 0px 0px 0px;
	margin: 0px 0px 0px 8px;
	font-weight: bold;
}
div.login a {
	color: #FFFFFF;
}
div.login a:hover {
	color: black;
	text-decoration:underline;
}
.login_block_user {
	float: right;
}
.login_block_elem {
	float: right;
	vertical-align: top;
	padding: 0px 0px 0px 4px !important;
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
	padding: <?php echo ($conf->dol_optimize_smallscreen?'0':'8')?>px 0px 0px 0px;
	margin: 0px 0px 0px 8px;
	text-decoration: none;
	color: white;
	font-weight: bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

.vmenu {
	margin: 0;
	position: relative;
	width: 180px;
}
<?php if (GETPOST("optioncss") == 'print') { ?>
.vmenu {
	display: none;
}
<?php } ?>

div.vmenu {
	position: relative;
    float: left;
    margin: 0px;
    width: 180px;
    margin-left: 10px;
}

a.vmenu:link        { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
a.vmenu:visited     { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
a.vmenu:active      { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
a.vmenu:hover       { font-size:12px; text-align:left; font-weight: normal; color: #7F0A29; margin: 1px 1px 1px 4px; }
font.vmenudisabled  { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }

a.vsmenu:link       { font-size:11px; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 4px; }
a.vsmenu:visited    { font-size:11px; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 4px; }
a.vsmenu:active     { font-size:11px; text-align:left; font-weight: normal; color: RGB(94,148,181); margin: 1px 1px 1px 4px; }
a.vsmenu:hover      { font-size:11px; text-align:left; font-weight: normal; color: #7F0A29; margin: 1px 1px 1px 4px; }
font.vsmenudisabled { font-size:11px; text-align:left; font-weight: normal; color: #202020; }
font.vsmenudisabledmargin { margin: 1px 1px 1px 4px; }

a.help:link         { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #68ACCF; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:visited      { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #68ACCF; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:active       { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #6198BA; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:hover        { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #6198BA; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


div.blockvmenupair
{
	margin-bottom: 15px;
	border-spacing: 0px;
	padding: 0px;
	width: 100%;
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_leftCategorie.jpg',1); ?>);
    background-position: top right;
    background-repeat: no-repeat;

}
div.blockvmenuimpair
{
	margin-bottom: 15px;
	border-spacing: 0px;
	padding: 0px;
	width: 100%;
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_leftCategorie.jpg',1); ?>);
    background-position: top right;
    background-repeat: no-repeat;

}

div.blockvmenuimpair form a.vmenu, div.blockvmenupair form a.vmenu
{
	width: 166px;
	border-spacing: 0px;
	color: #000000;
	text-align:left;
	text-decoration: none;
	padding: 4px;
	margin: 0px;
	background: #FFFFFF;
	margin-bottom: -12px;
}

div.menu_titre
{
	padding: 0px;
	padding-left:0px;
	margin-top: 8px;
	margin: 0px;
	height: 16px;
    text-align: left;
    font-size : 12px;
    color : #FFFFFF;
    font-weight: bold;
    height: 20px;
    line-height: 20px;
}

div.menu_titre a.vmenu {
	/*font-weight: bold;*/
    /*font-family: "Trebuchet MS",Arial,Helvetica,sans-serif;*/
    font-size: 12px;
}

div.blockvmenusearch
{
	margin: 3px 0px 15px 0px;
	padding: 25px 0px 2px 2px;
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_leftMenu.jpg',1); ?>);
    background-position: top right;
    background-repeat: no-repeat;
}

div.blockvmenusearch input[type="text"] {
	float: left;
    width: 110px;
    border: 1px solid #333;
    font-size: 10px;
    height: 16px;
}

div.blockvmenusearch input.button[type="submit"] {
	float: left;
    margin-left: 10px;
}

div.blockvmenusearch div.menu_titre {
	margin-top: 5px;
}

#blockvmenusearch div.menu_titre, #blockvmenusearch form
{
	padding-top: 1px;
	padding-bottom: 1px;
	min-height: 14px;
}

div.blockvmenubookmarks
{
	margin: 0px;
	border-spacing: 0px;
	padding: 0px;
	width: 100%;
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_leftCategorie.jpg',1); ?>);
    background-position: top right;
    background-repeat: no-repeat;
    margin-bottom: 15px;
}

div.blockvmenuhelp
{
<?php if (empty($conf->dol_optimize_smallscreen)) { ?>
	text-align: center;
	border-spacing: 0px;
    width: 162px;
	background: transparent;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-decoration: none;
    padding-left: 0px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 0px 0px;
<?php } else { ?>
    display: none;
<?php } ?>
}

div.menu_contenu {
	margin: 0px;
	padding: 1px;

	padding-right: 8px;
    font-size : 11px;
    font-weight:normal;
    color : #000000;
    text-align: left;
}

div.menu_end {
/*	border-top: 1px solid #436981; */
	margin: 0px;
	padding: 0px;
	height: 6px;
    width: 165px;
    background-repeat:no-repeat;
    display: none;
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
	box-shadow: 2px 4px 2px #CCCCCC;
	-moz-box-shadow: 2px 4px 2px #CCCCCC;
	-webkit-box-shadow: 2px 4px 2px #CCCCCC;
	border-collapse: collapse;
	border: 1px solid #666;
    background-color: #EDEDED;
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

.toolbar {
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$conf->theme.'/img/tmenu2.png',1); ?>) !important;
    background-repeat: repeat-x !important;
    border: 1px solid #BBB !important;
}

a.toolbarbutton {
    margin-top: 1px;
    margin-left: 4px;
    margin-right: 4px;
    height: 30px;
/*    border: solid 1px #AAAAAA;
    width: 32px;
    background: #FFFFFF;*/
}
img.toolbarbutton {
	margin-top: 2px;
    height: 28px;
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
/* sliding resizer - add 'outside-border' to resizer on-hover
 * this sample illustrates how to target specific panes and states */
.ui-layout-resizer-north-sliding-hover  { border-bottom-width:  1px; }
.ui-layout-resizer-south-sliding-hover  { border-top-width:     1px; }
.ui-layout-resizer-west-sliding-hover   { border-right-width:   1px; }
.ui-layout-resizer-east-sliding-hover   { border-left-width:    1px; }

/*
 *  TOGGLER-BUTTONS
 */
.ui-layout-toggler {
    border-top: 1px solid #AAA; /* match pane-border */
    border-right: 1px solid #AAA; /* match pane-border */
    border-bottom: 1px solid #AAA; /* match pane-border */
    background-color: #DDD;
    top: 5px !important;
    }
.ui-layout-toggler-open {
	height: 48px !important;
	width: 5px !important;
    -moz-border-radius:0px 10px 10px 0px;
	-webkit-border-radius:0px 10px 10px 0px;
	border-radius:0px 10px 10px 0px;
}
.ui-layout-toggler-closed {
	height: 48px !important;
	width: 5px !important;
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
	height: <?php print (empty($conf->dol_optimize_smallscreen)?'40':'40'); ?>px !important;
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
    background-color: #DDD;
    }
.ecm-layout-toggler-open {
	height: 48px !important;
	width: 6px !important;
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
    top: 20px;
    margin: 0px 0px 10px 0px;
    text-align: left;
    width: 100%;
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_navHorizontal.jpg',1); ?>);
    height: 25px;
    background-repeat: repeat-x;
    background-position: left;
}

div.tabs a.tabTitle {
	padding: 4px 10px;
    margin-left: 25px;
    position: relative;
    float: left;
    height: 17px;
    color: #FFF;
    line-height: 16px;
    font-weight: bold;
    font-size: 14px;
    display: block;
    background-color: #202020;
}

div.tabs a.tabTitle img {
position: absolute;
top: 4px;
left: -20px;
}

div.tabs a.tab {
	display: block;
	width: auto;
    font-size: 11px;
    height: 25px;
    line-height: 25px;
    color: #FFFFFF;
    text-decoration: none;
    position: relative;
    padding: 0px 10px 0px 10px;
}

div.tabs a.tab#active {
    background-color: #FFF;
    color: #D45416;
    border-bottom: 0px;
    background-image: none;

}

div.tabs a.tab:hover {
	color: #FFFFFF;
    background-color: #505050;
}

/*div.tabs {
    top: 20px;
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: <?php print $left; ?>;
}

*/

div.tabBar {
    margin: 0px 0px 10px 0px;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}

/*
a.tabTitle {
    background: #5088A9;
    color: white;
	font-family: <?php print $fontlist ?>;
    font-weight: normal;
    padding: 0px 6px;
    margin: 0px 6px;
    text-decoration: none;
    white-space: nowrap;
    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

a.tab:link {
    background: #dee7ec;
    color: #436976;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}
a.tab:visited {
    background: #dee7ec;
    color: #436976;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}
a.tab#active {
    background: white;
    border-bottom: #dee7ec 1px solid;
	font-family: <?php print $fontlist ?>;
    color: #436976;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
    border-bottom: 1px solid white;
}
a.tab:hover {
    background: white;
    color: #436976;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

a.tabimage {
    color: #436976;
	font-family: <?php print $fontlist ?>;
    text-decoration: none;
    white-space: nowrap;
}
*/
td.tab {
    background: #dee7ec;
}

span.tabspan {
    background: #dee7ec;
    color: #436976;
	font-family: <?php print $fontlist ?>;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-<?php print $right; ?>: 1px solid #555555;
    border-<?php print $left; ?>: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

div.divButAction { margin-bottom: 1.4em; }

.butAction, .butAction:link, .butAction:visited, .butAction:hover, .butAction:active, .butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
	font-family:"Trebuchet MS",Arial,Helvetica,sans-serif;
	font-weight: bold;
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_btnBlue.jpg',1); ?>) repeat-x;
	color: #FFF !important;
	padding: 0px 10px 0px 10px;
	margin: 0px 10px 0px 10px;
	text-decoration: none;
	white-space: nowrap;
    font-size: 12px;
    height: 18px;
    line-height: 18px;
    cursor: pointer;
    margin-bottom: 10px;
}

.butAction:hover   {
}

.butActionDelete    {
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_btnRed.jpg',1); ?>) repeat-x !important;
    color: #FFF;
}

.butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
}

.butActionDelete:hover {
}

.butActionRefused {
	font-family:"Trebuchet MS",Arial,Helvetica,sans-serif;
	font-weight: bold;
	background: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_btnBlue.jpg',1); ?>) repeat-x;
	color: #AAA !important;
	padding: 0px 10px 0px 10px;
	margin: 0px 10px 0px 10px;
	text-decoration: none;
	white-space: nowrap;
    font-size: 12px;
    height: 18px;
    line-height: 18px;
    margin-bottom: 10px;
	cursor: not-allowed;
}

<?php if (! empty($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)) { ?>
.butActionRefused {
	display: none;
}
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

/*
#undertopmenu {
background-image: url("<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/gradient.gif',1); ?>");
background-repeat: repeat-x;
}
*/

.nocellnopadd {
list-style-type:none;
margin: 0px;
padding: 0px;
}

.boxhandle {
	margin: 1px 1px 0px 0px;
}

.notopnoleft {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-<?php print $left; ?>: 0px;
padding-<?php print $right; ?>: 10px;
padding-bottom: 4px;
margin: 0px 0px;
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
	border-collapse: collapse;
	padding: 10px;
	border-spacing: 2px;
	border: 1px solid #EFEFEF;
}
table.border, table.dataTable, .table-border {
	-webkit-box-shadow: #CCCCCC 2px 4px 2px;
	border: 2px solid #BBBBBB;
}
table.border.formdoc {
	padding: 0px;
	border-collapse: collapse;
	border: 1px solid #666;
}

table.border.formdoc td {
	margin: 0px;
	padding: 0px;
	border: none;
}

table.border td, div.border div div.tagtd {
	padding: 4px;
	border: 1px solid #EFEFEF;
	border-spacing: 0px;
	/*border-collapse: collapse;*/
	margin: 0px;
}

table.border table td {
	border: none;
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

table.noborder, div.noborder form {
	/*box-shadow: 2px 4px 2px #CCCCCC;
	-moz-box-shadow: 2px 4px 2px #CCCCCC;
	-webkit-box-shadow: 2px 4px 2px #CCCCCC; */
	border-collapse: collapse;
	/*border: 1px solid #666;*/

}

table.noborder tr, div.noborder form {
}

table.noborder td, div.noborder div {
}

table.nobordernopadding {
border-collapse: collapse;
border: 0px;
}
table.nobordernopadding tr {
border: 0px;
padding: 0px 0px;
}
table.nobordernopadding td {
    border: 0px;
    padding: 0px 0px 0px 0px !important;
}

/* For lists */

table.liste {
    /*background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_centerBlock-title.jpg',1); ?>);*/
    background-repeat: no-repeat;
    background-position: top right;
    vertical-align: text-top;
    border-collapse: collapse;
}
table.liste td {
padding: 0px 5px;
}

table.noborder {
    /*background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_centerBlock-title.jpg',1); ?>);*/
    background-repeat: no-repeat;
    background-position: top right;
    vertical-align: text-top;
}

.tagtable, .table-border { display: table; }
.tagtr, .table-border-row  { display: table-row; }
.tagtd, .table-border-col, .table-key-border-col, .table-val-border-col { display: table-cell; }
.tagtable form, .tagtable div { display: table-row; }
.tagtable form div, .tagtable div div { display: table-cell; }

tr.liste_titre, form.liste_titre {
    height: 25px;
    background-repeat: no-repeat;
    background-color: #C8C8C8;
    color: #333333;
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    /* text-decoration: underline; */
    /* border-bottom: 1px solid #FDFFFF; */
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_centerBlock-title2.jpg',1); ?>);
    background-position: top right;
}

th.liste_titre_sel, td.liste_titre_sel, th.liste_titre {
    color: #FFFFFF !important;
}

tr.liste_total, form.liste_total {
	height: 25px;
	border-top: 1px solid #333;
}

div#stats {
}

div.liste_titre, tr.liste_titre, form.liste_titre {
    text-align: <?php echo $left; ?>;
}

div.liste_titre a, tr.liste_titre a, form.liste_titre a {
	color: #333333;
	margin: 0px 5px;
}

div.liste_titre td, tr.liste_titre td, form.liste_titre div {
	padding: 0px 5px;
    vertical-align: middle;
    background: none !important;
}

div.liste_titre, th.liste_titre, td.liste_titre
{
    background-repeat: repeat-x;
    color: #333333;
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    background-image: none;
    background: none;
}

div.liste_titre input.button, tr.liste_titre input.button, form.liste_titre input.button {
	float: left;
    position: relative;
    /*margin: 30px 10px 10px 0px;*/
}

th.liste_titre_sel, td.liste_titre_sel, div.liste_titre_sel
{
    background: #505050;
    background-repeat: repeat-x;
    color: #FFFFFF;
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    /* text-decoration: underline; */
    /* border-bottom: 1px solid #FDFFFF; */
}

input.liste_titre {
    background: transparent;
    background-repeat: repeat-x;
    border: 0px;
}

tr.liste_total td, form.liste_total div {
	border-top: 1px solid #DDDDDD;
	background: #F0F0F0;
	background-repeat: repeat-x;
	color: #332266;
	font-weight: normal;
	padding: 5px;
}

.impair {
	background: #eaeaea;
	font-family: <?php print $fontlist ?>;
	border: 0px;
}

.impair:hover {
	background: #c0c4c7;
	border: 0px;
}

tr.impair td.nohover, form.impair div.nohover {
	background: #eaeaea;
}

.pair	{
	background: #FFFFFF;
	font-family: <?php print $fontlist ?>;
	border: 0px;
}

.pair:hover {
	background: #c0c4c7;
	border: 0px;
}

tr.pair td.nohover {
	background: #FFFFFF;
}

.pair td, .impair td, .pair div, .impair div
{
	padding: 2px 3px !important;
}

.noshadow {
	-moz-box-shadow: 0px 0px 0px #CCC !important;
	-webkit-box-shadow: 0px 0px 0px #CCC !important;
	box-shadow: 0px 0px 0px #CCC !important;
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

.box {
	padding-right: 0px;
	padding-left: 0px;
	padding-bottom: 4px;
}

tr.box_titre {
	height: 24px;
	background: #7699A9;
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/menus/trtitle.png',1); ?>);
	background-repeat: repeat-x;
	color: #FFFFFF;
	font-family: <?php print $fontlist ?>, sans-serif;
	font-weight: normal;
	border-bottom: 1px solid #FDFFFF;
  -moz-border-radius-topleft:6px;
  -moz-border-radius-topright:6px;
}

tr.box_titre td.boxclose {
	width: 30px;
}

tr.box_impair {
/* background: #e6ebed; */
background: #eaeaea;
font-family: <?php print $fontlist ?>;
}

tr.box_pair {
/* background: #d0d4d7; */
background: #f4f4f4;
font-family: <?php print $fontlist ?>;
}

.formboxfilter {
	vertical-align: middle;
	margin-bottom: 6px;
}
.formboxfilter input[type=image]
{
	top: 3px;
	position: relative;
}



/*
 *   Ok, Warning, Error
 */

.ok      { color: #114466; }
.warning { color: #887711; }
.error   { color: #550000 !important; font-weight: bold; }

td.highlights { background: #f9c5c6; }

div.ok {
  color: #114466;
}

div.warning {
  color: #997711;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #c0c0d0;
  -moz-border-radius:6px;
  background: #efefd4;
}

div.error {
  color: #FFFFFF;
  font-weight: bold;
  text-align: left;
  padding-left: 10px;
  background-color: #AD1800;
  height: 20px;
  line-height: 20px;
  margin-bottom: 20px;
}

#login div.error {
	margin-top: 20px;
    }

/* Info admin */
div.info {
  color: #505050;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #878003;
  background: #F4EAA2;
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

.photo {
border: 0px;
/* filter:alpha(opacity=55); */
/* opacity:.55; */
}

div.titre {
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
	color: #842F00;
    font-size: 16px;
	text-decoration: none;
}

#pictotitle {
	margin-right: 15px;
}

#dolpaymenttable { width: 600px; font-size: 13px; }
#tablepublicpayment { border: 1px solid #CCCCCC !important; width: 100%; }
#tablepublicpayment .CTableRow1  { background-color: #F0F0F0 !important; }
#tablepublicpayment tr.liste_total { border-bottom: 1px solid #CCCCCC !important; }
#tablepublicpayment tr.liste_total td { border-top: none; }


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
background-color: #FFFFF0;
opacity: 1;
-moz-border-radius:6px;
}



/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */

img.datecallink { padding-left: 2px !important; padding-right: 2px !important; padding-bottom: 2px !important; }

.ui-datepicker-trigger {
	vertical-align: middle;
	cursor: pointer;
}

.bodyline {
	-moz-border-radius:8px;
	border: 1px #E4ECEC outset;
	padding: 0px;
	margin-bottom: 5px;
	z-index: 3000;
}
table.dp {
    width: 180px;
    background-color: #FFFFFF;
    border-top: solid 2px #DDDDDD;
    border-<?php print $left; ?>: solid 2px #DDDDDD;
    border-<?php print $right; ?>: solid 1px #222222;
    border-bottom: solid 1px #222222;
}
.dp td, .tpHour td, .tpMinute td{padding:2px; font-size:10px;}
/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color:#BB6644;
	color:white !important;
	font-size:11px;
	cursor:auto;
	text-align: center;
}
/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#BB6644;
	color:#FFFFFF;
	font-weight:bold;
	border: 1px outset black;
	cursor:pointer;
}
.dpButtons:Active,.tpButtons:Active{border: 1px outset black;}
.dpDayNames td,.dpExplanation {background-color:#D9DBE1; font-weight:bold; text-align:center; font-size:11px;}
.dpExplanation{ font-weight:normal; font-size:11px;}
.dpWeek td{text-align:center}

.dpToday,.dpReg,.dpSelected { cursor:pointer; }
.dpToday{font-weight:bold; color:black; background-color:#DDDDDD;}
.dpReg:Hover,.dpToday:Hover{background-color:black;color:white}

/* Jour courant */
.dpSelected {background-color:#BB6644;color:white;font-weight:bold; }

.tpHour {border-top:1px solid #DDDDDD; border-right:1px solid #DDDDDD;}
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
.cal_other_month   { border-top: 0; border-left: solid 1px #C0C0C0; border-right: 0; border-bottom: solid 1px #C0C0C0; }
.cal_current_month_right { border-right: solid 1px #E0E0E0; }
.cal_other_month_right   { border-right: solid 1px #C0C0C0; }
.cal_other_month   { background: #DDDDDD; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { background: #EEEEEE; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #C0C0C0; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event    { border-collapse: collapse; margin-bottom: 1px; }
table.cal_event td { border: 0px; padding-<?php print $left; ?>: 0px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
ul.cal_event       { padding-right: 2px; padding-top: 1px; border: none; list-style-type: none; margin: 0 auto; padding-left: 0px; padding-start: 0px; -khtml-padding-start: 0px; -o-padding-start: 0px; -webkit-padding-start: 0px; -webkit-padding-start: 0px; }
li.cal_event       { border: none; list-style-type: none; }
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; }


/* ============================================================================== */
/*  Ajax - Liste deroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-autocomplete-loading { background: white url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/working.gif',1); ?>) right center no-repeat; }
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
.ui-button { margin-left: -1px; padding-top: 1px; }
.ui-button-icon-only .ui-button-text { height: 8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding: 2px 0px 6px 0px; }
.ui-button-text
{
    line-height: 1em !important;
}
.ui-autocomplete-input { margin: 0; padding: 1px; }


table.noborder {
	margin-bottom: 10px;
    position: relative;
    border: none;
}

div.leftContent {
	background-color: #FFF;
}


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */

.cke_editor table, .cke_editor tr, .cke_editor td
{
    border: 0px solid #FF0000 !important;
}
span.cke_skin_kama { padding: 0 !important; }
.cke_wrapper { padding: 4px !important; }

a.cke_dialog_ui_button
{
    font-family: <?php print $fontlist ?> !important;
	background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/button_bg.png',1); ?>) !important;
	background-position: bottom !important;
    border: 1px solid #ACBCBB !important;
	padding: 0.1em 0.7em !important;
	margin: 0em 0.5em !important;
    -moz-border-radius:0px 5px 0px 5px !important;
	-webkit-border-radius:0px 5px 0px 5px !important;
	border-radius:0px 5px 0px 5px !important;
    -moz-box-shadow: 4px 4px 4px #CCC !important;
    -webkit-box-shadow: 4px 4px 4px #CCC !important;
    box-shadow: 4px 4px 4px #CCC !important;
}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {
    height: 72px !important;
}


/* ============================================================================== */
/*  Login                                                                   */
/* ============================================================================== */

#login {
    display: block;
    height: auto;
    top: 20px;
    margin-bottom: 30px;
    }

div#infoVersion {
	position: relative;
    width: 100%;
    height: 15px;
    line-height: 15px;
    text-align: center;
    font-size: 10px;
    background-color: #D6D6D6;
    }

div#logoBox {
	position: relative;
    width: 100%;
    height: auto;
    margin-top: 30px;
    text-align: center;
    }

div#parameterBox {
	position: relative;
    width: 100%;
    height: auto;
    border: 1px solid #666;
    border-top: 2px solid #842F00;
    background-image: url(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_connectionBox.jpg',1); ?>);
    background-repeat: no-repeat;
    background-position: top center;
    }

div#parameterBox div {
	width: 100%;
    height: 20px;
    position: relative;
    line-height: 20px;
    }

div#parameterBox div label {
	width: 190px;
    text-align: right;
    display: block;
    float: left;
    margin-right: 10px;
    }

div#parameterBox div input[type="text"],
div#parameterBox div input[type="password"] {
	width: 180px;
    height: 16px;
    font-size: 10px;
    margin-top: 2px;
    }

div#connectionLine {
	margin: 10px 0px;
	text-align: center;
    }

div#logBox {
margin-top: 25px;
}

div.captchaBox {
	margin-top: 10px;
    }

div.captchaImg {
	margin-bottom: 10px;
    margin-top: 5px;
    height: 40px !important;
    }

img#captcha {
	margin-left: 200px;
    }

#captcha_refresh_img {
	margin-bottom: 6px;
}

div#infoLogin {
    width: 100%;
    height: auto;
    text-align: center;
    margin-top: 20px;
    color: #666;
    position: relative;
    margin-bottom: 20px;
}

div.other {
	margin: 10px 0px;
    text-align: center;
    position: relative;
    width: 480px;
    left: 50%;
    margin-left: -240px;
    }

form.listactionsfilter img {
	display: none;
    }

form.listactionsfilter input[type="submit"] {
	margin: 2px 0px;
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
	right:8px;
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
a.ui-link, a.ui-link:hover, .ui-btn:hover, span.ui-btn-text:hover, span.ui-btn-inner:hover {
	text-decoration: none !important;
}

.ui-btn-inner {
	padding-left: 10px;
	padding-right: 10px;
	/* white-space: normal; */		/* Warning, enable this break the truncate feature */
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
}
div.ui-controlgroup-controls div.tabsElem a
{
	-moz-box-shadow: 0 -3px 6px rgba(0,0,0,.2);
	-webkit-box-shadow: 0 -3px 6px rgba(0,0,0,.2);
	box-shadow: 0 -3px 6px rgba(0,0,0,.2);
	border: none;
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
.ui-body-c, .ui-btn-up-c, .ui-btn-hover-c {
	border: none !important;
}

/* Style for first level menu with jmobile */
.ui-bar-b, .lilevel0 {
    background: rgb(<?php echo dol_buildpath($path.'/theme/'.$theme.'/img/bg_centerBlock-title2.jpg',1); ?>);
    background-repeat: repeat-x;
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
