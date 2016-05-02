<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C)      2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       htdocs/theme/lime/style.css.php
 *		\brief      Fichier de style CSS du theme Lime
 *		\version    $Id: style.css.php,v 1.56 2011/01/12 14:56:32 eldy Exp $
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

session_cache_limiter( FALSE );

require_once("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");

// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }

if (! empty($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);	// If language was forced on URL
if (! empty($_GET["theme"])) $conf->theme=$_GET["theme"];  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');
$fontsize=empty($conf->browser->phone)?'12':'12';
$fontsizesmaller=empty($conf->browser->phone)?'11':'11';

$fontlist='georgia,verdana,helvetica,arial,tahoma';
//$fontlist='Verdana,Helvetica,Arial,sans-serif';

?>

/* ============================================================================== */
/* Styles par defaut                                                              */
/* ============================================================================== */

body {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background-color: #FFFFFF;
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

a:link, a:visited, a:hover, a:active { font-family: <?php print $fontlist ?>; font-weight: bold; color: #000000; text-decoration: none; }

input {
    font-size: <?php print $fontsize ?>px;
    font-family: <?php print $fontlist ?>;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat {
	font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input:disabled {
background:#ddd;
}
textarea  {
	font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat {
	font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea:disabled {
background:#ddd;
}
select.flat {
	background: #FDFDFD;
    font-size: <?php print $fontsize ?>px;
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button {
    font-family: <?php print $fontlist ?>;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/button_bg.png' ?>);
	background-position: bottom;
    border: 1px solid #ACBCBB;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
}
.button:focus  {
    font-family: <?php print $fontlist ?>;
	color: #222244;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/button_bg.png' ?>);
	background-position: bottom;
    border: 1px solid #ACBCBB;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
}
.buttonajax {
    font-family: <?php print $fontlist ?>;
	border: 0px;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/button_bg.png' ?>);
	background-position: bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
form {
    padding: 0em 0em 0em 0em;
    margin: 0em 0em 0em 0em;
}


/* For dragging lines */

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

td.vmenu {
    margin-<?php print $right; ?>: 2px;
    padding: 0px;
    padding-bottom: 0px;
    padding-top: 1px;
    width: 168px;
}

td.vmenu_login {
    margin-<?php print $right; ?>: 2px;
    padding: 0px;
    padding-bottom: 0px;
    padding-top: 1px;
    width: 168px;
}

div.fiche {
	margin-<?php print $left; ?>: 4px;
	margin-<?php print $right; ?>: 4px;
}

/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

<?php
if (! empty($conf->browser->phone))
{
	$minwidthtmenu=0;
	$heightmenu=19;
}
else
{
	$minwidthtmenu=60;
	$heightmenu=47;
}
?>

div.tmenu {
<?php if (GETPOST("optioncss") == 'print') {  ?>
	display:none;
<?php } else { ?>
    position: relative;
    display: block;
    white-space: nowrap;
    border-top: 0px solid #D3E5EC;
    border-<?php print $left; ?>: 0px;
    border-<?php print $right; ?>: 0px solid #555555;
    border-bottom: 1px solid #ABB9B9;
    padding: 0px 0px 0px 0px;	/* t r b l */
    margin: 0px 0px 5px 0px;	/* t r b l */
    font-weight: normal;
    height: <?php print $heightmenu; ?>px;
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_top.jpg' ?>);
    color: #000000;
    text-decoration: none;
<?php } ?>
}

table.tmenu {
    padding: 0px 0px 10px 0px;	/* t r b l */
    margin: 0px 0px 0px 0px;	/* t r b l */
	text-align: center;
}
td.tmenu {
	<?php print $minwidthtmenu?'width: '.$minwidthtmenu.'px;':''; ?>
	text-align: center;
	vertical-align: bottom;
	white-space: nowrap;
}

a.tmenudisabled:link, a.tmenudisabled:visited, a.tmenudisabled:hover, a.tmenudisabled:active {
	color: #757575;
    font-weight: normal;
	padding: 0px 5px 0px 5px;
	margin: 0px 1px 2px 1px;
	cursor: not-allowed;
    font-weight: normal;
	white-space: nowrap;
}

a.tmenu:link, a.tmenu:visited, a.tmenu:hover, a.tmenu:active {
	color: #000000;
	padding: 0px 5px 0px 5px;
	margin: 0px 1px 2px 1px;
	font-weight: normal;
	white-space: nowrap;
}
a.tmenu:hover, a.tmenu:active {
	margin: 0px 0px 0px 0px;
	background: #E6F6B0;
	border-<?php print $right; ?>: 1px solid #555555;
	border-<?php print $left; ?>: 1px solid #D8D8D8;
	border-top: 1px solid #D8D8D8;
	border-bottom: 2px solid #dee7ec;
}
a.tmenu:active {
	background: #E6F6B0 none;
}

a.tmenusel:link, a.tmenusel:visited, a.tmenusel:hover, a.tmenusel:active {
	padding: 0px 5px 0px 5px;
	margin: 0px 0px 0px 0px;
	font-weight: normal;
	color: #234046;
	background: #E6F6B0;;
	border-<?php print $right; ?>: 1px solid #555555;
	border-top: 1px solid #D8D8D8;
	border-<?php print $left; ?>: 1px solid #D8D8D8;
	border-bottom: 2px solid #F4F4F4;
	white-space: nowrap;
}


* html li.tmenu a {
	width:40px;
}

ul.tmenu {	/* t r b l */
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
	list-style: none;
}
li.tmenu {
	<?php print $minwidthtmenu?'min-width: '.$minwidthtmenu.'px;':''; ?>
	text-align: center;
	vertical-align: bottom;
	float: <?php print $left; ?>;
    height: <?php print $heightmenu; ?>px;
	position:relative;
	display: block;
	padding: 2px 5px 0px 5px;
    margin: 0px 0px 0px 0px;
	font-weight: normal;
}


div.mainmenu {
	position : relative;
	color: white;
	background-repeat:no-repeat;
	background-position:center top;
	height: <?php echo ($heightmenu-19); ?>px;
	margin-left: 0px;
}

<?php if (empty($conf->browser->phone)) { ?>

div.mainmenu.home{
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/home.png' ?>);
}

div.mainmenu.companies {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/company.png' ?>);
}

div.mainmenu.products {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/products.png' ?>);
	margin-left: 10px;
}

div.mainmenu.commercial {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/commercial.png' ?>);
}

div.mainmenu.accountancy {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/money.png' ?>);
}

div.mainmenu.bank {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/bank.png' ?>);
}

div.mainmenu.project {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/project.png' ?>);
}

div.mainmenu.tools {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/tools.png' ?>);
}

div.mainmenu.ftp {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/tools.png' ?>);
}

div.mainmenu.members {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/members.png' ?>);
}

div.mainmenu.shop {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/shop.png' ?>);
}

div.mainmenu.agenda {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/agenda.png' ?>);
}

div.mainmenu.ecm {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/ecm.png' ?>);
}

div.mainmenu.cashdesk {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/pointofsale.png' ?>);
}

div.mainmenu.webcal {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/agenda.png' ?>);
}

div.mainmenu.google {
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/menus/globe.png' ?>);
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
foreach($conf->modules as $key => $val)
{
	$mainmenuused.=','.(isset($moduletomainmenu[$val])?$moduletomainmenu[$val]:$val);
}
//var_dump($mainmenuused);
$mainmenuusedarray=array_unique(explode(',',$mainmenuused));

$generic=1;
$divalreadydefined=array('home','companies','products','commercial','accountancy','project','tools','members','shop','agenda','ecm','cashdesk');
foreach($mainmenuusedarray as $key => $val)
{
	if (empty($val) || in_array($val,$divalreadydefined)) continue;
	//print "XXX".$val;

	// Search img file in module dir
	$found=0; $url='';
	foreach($conf->file->dol_document_root as $dirroot)
	{
		if (file_exists($dirroot."/".$val."/img/".$val.".png"))
		{
			$url=DOL_URL_ROOT.'/'.$val.'/img/'.$val.'.png';
			$found=1;
			break;
		}
	}
	// Img file not found
	if (! $found && $generic <= 4)
	{
		$url=DOL_URL_ROOT."/theme/lime/img/menus/generic".$generic.".png";
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

.tmenuimage {
    padding:0 0 0 0 !important;
    margin:0 0px 0 0 !important;
}



/* Login */

div.login_block {
	position: absolute;
	<?php print $right; ?>: 5px;
	top: 3px;
	font-weight: bold;
	<?php if (GETPOST("optioncss") == 'print') { ?>
	display: none;
	<?php } ?>
}

div.login_block table {
	display: inline;
}

div.login {
	white-space:nowrap;
    padding: <?php echo ($conf->browser->phone?'0':'8')?>px 0px 0px 0px;
    margin: 0px 0px 0px 8px;
	font-weight: bold;
}
div.login a {
	color: #101010;
}
div.login a:hover {
	color: black;
	text-decoration:underline;
}

img.login, img.printer, img.entity {
	padding: <?php echo ($conf->browser->phone?'0':'8')?>px 0px 0px 0px;
	margin: 0px 0px 0px 8px;
	text-decoration: none;
	color: white;
	font-weight: bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

<?php if ((GETPOST("optioncss") == 'print')
|| (! empty($conf->browser->phone) && class_exists('Smartphone') && empty($conf->global->MAIN_SEARCHFORM_WITH_SMARTHPONE) && empty($conf->global->BOOKMARKS_SHOW_WITH_SMARTHPONE))) { ?>
.vmenu {
    display: none;
}
<?php } ?>

a.vmenu:link, a.vmenu:visited, a.vmenu:hover, a.vmenu:active { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; line-height:23px;}
font.vmenudisabled  { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: bold; color: #93a5aa; }

a.vsmenu:link, a.vsmenu:visited, a.vsmenu:hover, a.vsmenu:active { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
font.vsmenudisabled { font-size:<?php print $fontsize ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; color: #93a5aa; margin: 1px 1px 1px 6px; }

a.help:link, a.help:visited, a.help:hover, a.help:active { font-size:<?php print $fontsizesmaller ?>px; font-family: <?php print $fontlist ?>; text-align: <?php print $left; ?>; font-weight: normal; }


div.blockvmenupair, div.blockvmenuimpair
{
    width:168px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
	font-family: <?php print $fontlist ?>;
	color: #202020;
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 0px;
    padding-bottom: 0px;
    margin: 1px 0px 0px 0px;
	background: #bee644 url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_left.jpg' ?>);
    background-position:top;
    background-repeat:repeat-x;
}

div.blockvmenusearch
{
    width:168px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 4px 0px 0px 0px;
    background: #e6f6b0;
    background-position:top;
    background-repeat:repeat-x;
}

div.blockvmenubookmarks
{
    width:168px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
    font-family: <?php print $fontlist ?>;
    color: #101010;
    text-align: <?php print $left; ?>;
    text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 0px 0px;
    background: #f2f8db;
    background-position:top;
    background-repeat:repeat-x;
}

div.blockvmenuhelp
{
<?php if (empty($conf->browser->phone)) { ?>
    width:168px;
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #f0f0f0;
	font-family: <?php print $fontlist ?>;
	color: #000000;
	text-align: <?php print $left; ?>;
	text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 0px 0px;
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

#mainContent {
	background: #ffffff url(<?php echo DOL_URL_ROOT.'/theme/lime/img/headbg2.jpg' ?>) 0 0 no-repeat;
}

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
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tmenu2.png' ?>) !important;
    background-repeat: repeat-x !important;
}

.toolbarbutton {
    margin-top: 2px;
    margin-left: 4px;
    height: 34px;
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
    background:     #EEE;
    border:         1px solid #BBB;
    border-width:   0;
    }
    .ui-layout-resizer-drag {       /* REAL resizer while resize in progress */
    }
    .ui-layout-resizer-hover    {   /* affects both open and closed states */
    }
    /* NOTE: It looks best when 'hover' and 'dragging' are set to the same color,
        otherwise color shifts while dragging when bar can't keep up with mouse */
    /*.ui-layout-resizer-open-hover ,*/ /* hover-color to 'resize' */
    .ui-layout-resizer-dragging {   /* resizer beging 'dragging' */
        background: #AAA;
    }
    .ui-layout-resizer-dragging {   /* CLONED resizer being dragged */
        border-left:  1px solid #BBB;
        border-right: 1px solid #BBB;
    }
    /* NOTE: Add a 'dragging-limit' color to provide visual feedback when resizer hits min/max size limits */
    .ui-layout-resizer-dragging-limit { /* CLONED resizer at min or max size-limit */
        background: #E1A4A4; /* red */
    }

    .ui-layout-resizer-closed-hover { /* hover-color to 'slide open' */
        background: #EBD5AA;
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
    border: 1px solid #BBB; /* match pane-border */
    background-color: #BBB;
    }
    .ui-layout-resizer-hover .ui-layout-toggler {
        opacity: .60;
        filter:  alpha(opacity=60);
    }
    .ui-layout-resizer-hover .ui-layout-toggler-hover { /* need specificity */
        background-color: #FC6;
        opacity: 1.00;
        filter:  alpha(opacity=100);
    }
    .ui-layout-toggler-north ,
    .ui-layout-toggler-south {
        border-width: 0 1px; /* left/right borders */
    }
    .ui-layout-toggler-west ,
    .ui-layout-toggler-east {
        border-width: 1px 0; /* top/bottom borders */
    }
    /* hide the toggler-button when the pane is 'slid open' */
    .ui-layout-resizer-sliding  ui-layout-toggler {
        display: none;
    }
    /*
     *  style the text we put INSIDE the togglers
     */
    .ui-layout-toggler .content {
        color:          #666;
        font-size:      12px;
        font-weight:    bold;
        width:          100%;
        padding-bottom: 0.35ex; /* to 'vertically center' text inside text-span */
    }

.ui-in-layout-center {
    border-left: 0px !important;
    border-right: 0px !important;
    border-top: 0px !important;
}

.ui-in-layout-south {
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
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: <?php print $left; ?>;
}

div.tabBar {
    color: #101010;
    padding-top: 9px;
    padding-left: 8px;
    padding-right: 8px;
    padding-bottom: 8px;
    margin: 0px 0px 10px 0px;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;
    -moz-border-radius-bottomleft:6px;
    -moz-border-radius-bottomright:6px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
    border-left: 1px solid #D0D0D0;
    border-top: 1px solid #D8D8D8;
    background: #f6fcde;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}


a.tabTitle {
    background: #a0c829;
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
    background: #f6fcde;
    color: #677945;
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
    background: #f6fcde;
    color: #677945;
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
    color: #677945;
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
    color: #677945;
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

/* Nouvelle syntaxe a utiliser */

a.butAction:link, a.butAction:visited, a.butAction:hover, a.butAction:active, a.butActionDelete, a.butActionDelete:link, a.butActionDelete:visited, a.butActionDelete:hover, a.butActionDelete:active {
	font-family: <?php print $fontlist ?>;
	font-weight: bold;
	background: white;
	border: 1px solid #596c36;
	color: #596c36;
	padding: 0em 0.7em;
	margin: 0em 0.5em;
	text-decoration: none;
	white-space: nowrap;
}

a.butAction:hover   {
	background: #e5f3cb;
}

a.butActionDelete    {
	border: 1px solid red;
}

a.butActionDelete:link, a.butActionDelete:visited, a.butActionDelete:hover, a.butActionDelete:active {
	border: 1px solid #997777;
}

a.butActionDelete:hover {
	background: #FFe7ec;
}

.butActionRefused {
	font-family: <?php print $fontlist ?> !important;
	font-weight: bold !important;
	background: white !important;
	border: 1px solid #AAAAAA !important;
	color: #AAAAAA !important;
	padding: 0em 0.7em !important;
	margin: 0em 0.5em !important;
	text-decoration: none !important;
	white-space: nowrap !important;
	cursor: not-allowed;
}


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

/*
#undertopmenu {
background-image: url("<?php echo DOL_URL_ROOT.'/theme/lime/img/gradient.gif' ?>");
background-repeat: repeat-x;
}
*/

.nocellnopadd {
list-style-type:none;
margin: 0px;
padding: 0px;
}

.notopnoleft {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-<?php print $left; ?>: 0px;
padding-<?php print $right; ?>: 4px;
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

table.border {
border: 1px solid #9CACBB;
border-collapse: collapse;
}

table.border td {
padding: 1px 2px;
border: 1px solid #9CACBB;
border-collapse: collapse;
}

td.border {
border-top: 1px solid #000000;
border-right: 1px solid #000000;
border-bottom: 1px solid #000000;
border-left: 1px solid #000000;
}

/* Main boxes */

table.noborder {
border-collapse: collapse;
border-top-color: #FEFEFE;

border-right-width: 1px;
border-right-color: #BBBBBB;
border-right-style: solid;

border-left-width: 1px;
border-left-color: #BBBBBB;
border-left-style: solid;

border-bottom-width: 1px;
border-bottom-color: #BBBBBB;
border-bottom-style: solid;

margin: 0px 0px 2px 0px;
}

table.noborder tr {
border-top-color: #FEFEFE;

border-right-width: 1px;
border-right-color: #BBBBBB;
border-right-style: solid;

border-left-width: 1px;
border-left-color: #BBBBBB;
border-left-style: solid;
}

table.noborder td {
padding: 1px 2px 0px 1px;			/* t r b l */
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
padding: 0px 0px;
}

/* For lists */

table.liste {
width: 100%;
border-collapse: collapse;
border-top-color: #FEFEFE;

border-right-width: 1px;
border-right-color: #BBBBBB;
border-right-style: solid;

border-bottom-width: 1px;
border-bottom-color: #BBBBBB;
border-bottom-style: solid;

margin-bottom: 2px;
margin-top: 0px;
}
table.liste td {
padding-right: 2px;
}

tr.liste_titre {
height: 20px;
background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_title.jpg' ?>);
background-repeat: repeat-x;
font-family: <?php print $fontlist ?>;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
td.liste_titre {
background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_title.jpg' ?>);
background-repeat: repeat-x;
font-family: <?php print $fontlist ?>;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
td.liste_titre_sel
{
background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_title.jpg' ?>);
background-repeat: repeat-x;
font-family: <?php print $fontlist ?>;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
input.liste_titre {
background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_title.jpg' ?>);
background-repeat: repeat-x;
border: 0px;
}

tr.liste_total td {
border-top: 1px solid #bbbbbb;
font-weight: normal;
white-space: nowrap;
}

th {
background: #91ABB3;
color: #334444;
font-family: <?php print $fontlist ?>;
font-weight: bold;
border-left: 1px solid #FFFFFF;
border-right: 1px solid #FFFFFF;
border-top: 1px solid #FFFFFF;
border-bottom: 1px solid #FFFFFF;
white-space: nowrap;
}

.impair {
background: #fdfff6;
font-family: <?php print $fontlist ?>;
border: 0px;
}

.pair	{
background: #e9f3c6;
font-family: <?php print $fontlist ?>;
border: 0px;
}


/*
 *  Boxes
 */

.box {
padding-right: 0px;
padding-left: 0px;
padding-bottom: 4px;
}

tr.box_titre {
background-image: url(<?php echo DOL_URL_ROOT.'/theme/lime/img/header_title.jpg' ?>);
background-repeat: repeat-x;
font-family: <?php print $fontlist ?>, sans-serif;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
  -moz-border-radius-topleft:6px;
  -moz-border-radius-topright:6px;
}

tr.box_impair {
background: #fdfff6;
font-family: <?php print $fontlist ?>;
}

tr.box_pair {
background: #E9F3C6;
font-family: <?php print $fontlist ?>;
}

tr.fiche {
font-family: <?php print $fontlist ?>;
}




/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #887711; }
.error   { color: #550000; font-weight: bold; }

td.warning {	/* Utilise par Smarty */
  background: #FF99A9;
}

div.ok {
  color: #114466;
}

div.warning {
  color: #997711;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #e0e0d0;
  -moz-border-radius:6px;
  background: #efefd4;
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #8C9CAB;
  -moz-border-radius:6px;
}

/* Info admin */
div.info {
  color: #707070;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #e0e0d0;
  -moz-border-radius:6px;
  background: #efefd4;
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

.fieldrequired { font-weight: bold; color: #9a4848; }

#pictotitle {
	<?php print !empty($conf->browser->phone)?'display: none;':''; ?>
}

.photo {
border: 0px;
}

div.titre {
	font-family: <?php print $fontlist ?>;
	font-weight: bold;
	color: #101010;
	text-decoration: none;
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
background-color: #FFFFF0;
opacity: 1;
-moz-border-radius:6px;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */
.bodyline {
	-moz-border-radius:8px;
	border: 1px #E4ECEC outset;
	padding: 0px;
	margin-bottom: 5px;
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
	border: 1px outset black;
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
.dpReg:Hover,.dpToday:Hover{background-color:black;color:white}

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

.cal_other_month   { background: #DDDDDD; border: solid 1px #ACBCBB; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { background: #EEEEEE; border: solid 1px #ACBCBB; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border: solid 1px #ACBCBB; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #6C7C7B; padding-<?php print $left; ?>: 2px; padding-<?php print $right; ?>: 1px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event    { border-collapse: collapse; margin-bottom: 1px; }
table.cal_event td { border: 0px; padding-<?php print $left; ?>: 0px; padding-<?php print $right; ?>: 2px; padding-top: 0px; padding-bottom: 0px; }
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; }



/* ============================================================================== */
/*  Afficher/cacher                                                               */
/* ============================================================================== */

#evolForm input.error {
                        font-weight: bold;
                        border: solid 1px #FF0000;
                        padding: 1px 1px 1px 1px;
                        margin: 1px 1px 1px 1px;
              }

#evolForm input.focuserr {
                        font-weight: bold;
                        background: #FAF8E8;
                        color: black;
                        border: solid 1px #FF0000;
                        padding: 1px 1px 1px 1px;
                        margin: 1px 1px 1px 1px;
              }


#evolForm input.focus {	/*** Mise en avant des champs en cours d'utilisation ***/
                        background: #FAF8E8;
                        color: black;
                        border: solid 1px #000000;
                        padding: 1px 1px 1px 1px;
                        margin: 1px 1px 1px 1px;
              }

#evolForm input.normal {	/*** Retour a l'etat normal apres l'utilisation ***/
                         background: white;
                         color: black;
                         border: solid 1px white;
                         padding: 1px 1px 1px 1px;
                         margin: 1px 1px 1px 1px;
               }



/* ============================================================================== */
/*  Ajax - Liste deroulante de l'autocompletion                                   */
/* ============================================================================== */

.ui-autocomplete-loading { background: white url(<?php echo DOL_URL_ROOT.'/theme/lime/img/working.gif' ?>) right center no-repeat; }
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
	       height:16px;
	       cursor:pointer;
	     }

/* ============================================================================== */
/*  Ajax - In place editor                                                        */
/* ============================================================================== */

form.inplaceeditor-form { /* The form */
}

form.inplaceeditor-form input[type="text"] { /* Input box */
}

form.inplaceeditor-form textarea { /* Textarea, if multiple columns */
background: #FAF8E8;
color: black;
}

form.inplaceeditor-form input[type="submit"] { /* The submit button */
  font-size: 100%;
  font-weight:normal;
	border: 0px;
	background-image : url(<?php echo DOL_URL_ROOT.'/theme/lime/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}

form.inplaceeditor-form a { /* The cancel link */
  margin-left: 5px;
  font-size: 11px;
	font-weight:normal;
	border: 0px;
	background-image : url(<?php echo DOL_URL_ROOT.'/theme/lime/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}



/* ============================================================================== */
/* Admin Menu                                                                     */
/* ============================================================================== */

/* CSS for treeview */

/* Lien plier /deplier tout */
.arbre-switch {
    text-align: right;
    padding: 0 5px;
    margin: 0 0 -18px 0;
}

/* Arbre */
ul.arbre {
    padding: 5px 10px;
}
/* strong : A modifier en fonction de la balise choisie */
ul.arbre strong {
    font-weight: normal;
    padding: 0 0 0 20px;
    margin: 0 0 0 -7px;
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/branch.gif' ?>);
    background-repeat: no-repeat;
    background-position: 1px 50%;
}
ul.arbre strong.arbre-plier {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/plus.gif' ?>);
    cursor: pointer;
}
ul.arbre strong.arbre-deplier {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/minus.gif' ?>);
    cursor: pointer;
}
ul.arbre ul {
    padding: 0;
    margin: 0;
}
ul.arbre li {
    padding: 0;
    margin: 0;
    list-style: none;
}
/* This is to create an indent */
ul.arbre li li {
    margin: 0 0 0 16px;
}
/* Classe pour masquer */
.hide {
    display: none;
}

img.menuNew
{
	display:block;
	border:0px;
}

img.menuEdit
{
	border: 0px;
	display: block;
}

img.menuDel
{
	display:none;
	border: 0px;
}

div.menuNew
{
	margin-top:-20px;
	margin-<?php print $left; ?>:270px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuEdit
{
	margin-top:-15px;
	margin-<?php print $left; ?>:250px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuDel
{
	margin-top:-20px;
	margin-<?php print $left; ?>:290px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuFleche
{
	margin-top:-16px;
	margin-<?php print $left; ?>:320px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}


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
    font-family:Verdana,Arial,sans-serif;
    font-size:1em;
}


