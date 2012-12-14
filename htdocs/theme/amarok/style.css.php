<?php
/* Copyright (C) 2012	Nicolas Péré		<nicolas@amarok2.net>
 * Copyright (C) 2012	Xavier Peyronnet	<xavier.peyronnet@free.fr>
 * Copyright (C) 2012	Regis Houssin		<regis@dolibarr.fr>
 * Version 0.6 (2012-10-30)
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/amarok/style.css.php
 *		\brief      Fichier de style CSS du theme amarok
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

require_once("../../main.inc.php");

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
$fontsize=empty($conf->browser->phone)?'12':'12';
$fontsizesmaller=empty($conf->browser->phone)?'11':'11';

$path='';    // This value may be used in future for external module to overwrite theme

// Define image path files
$fontlist='helvetica,arial,tahoma,verdana';    //$fontlist='Verdana,Helvetica,Arial,sans-serif';
//'/theme/auguria/img/menus/trtitle.png';
$img_liste_titre=dol_buildpath($path.'/theme/amarok/img/menus/trtitle.png',1);
$img_head=dol_buildpath($path.'/theme/amarok/img/headbg2.jpg',1);
$img_button=dol_buildpath($path.'/theme/amarok/img/button_bg.png',1);

?>


/* ============================================================================== */
/* Styles par défaut                                                              */
/* ============================================================================== */

*, html {
	margin:0;
	padding:0;
	font-size:100%;
}

body {
	background-color:#f5f5f5;
	<?php if ($_SESSION['dol_login'] != '') {?>
	<?php if (GETPOST("optioncss") != 'print') {?>
	background-image:url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/vmenu.png' ?>);
	background-repeat:repeat-y;
	margin:0px;
	<?php } ?>
	<?php } else {?>
	background-image:url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/background_login.png' ?>);
	margin:100px;
	<?php } ?>
	color:#232323;
	font-size:<?php print $fontsize ?>px;
   	font-family:<?php print $fontlist ?>;
	
    <?php print 'direction:'.$langs->trans("DIRECTION").";\n"; ?>
}

.checkVatPopup {
	background-color:#f5f5f5;
	background-image:none;
	margin:10px;
	line-height:16px;
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

input, textarea {
    font-size:<?php print $fontsize ?>px;
    font-family:<?php print $fontlist ?>;
    border-radius:4px;
    border:solid 1px rgba(0,0,0,.3);
    border-top:solid 1px rgba(0,0,0,.4);
    border-bottom:solid 1px rgba(0,0,0,.2);
    box-shadow:1px 1px 2px rgba(0,0,0,.2) inset;
}

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
}

form {
    padding:0px;
    margin:0px;
}


/* ============================================================================== */
/* Login																		  */
/* ============================================================================== */

form#login {
	display:block;
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
	width:500px;
	border-radius:12px;
	box-shadow:0 0 16px rgba(0,0,0,.8);
}
form#login img  {width:auto; height:auto; opacity:.7;}
form#login img#img_logo {
	width:220px;
	max-width:220px;
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

body.body center{color:white;}

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

/* ! Message d'erreur lors du login : */
center .error { padding:8px !important; padding-left:26px !important; padding-right:20px; width:inherit; max-width:450px;color:#552323 !important; font-size:14px; border-radius:8px; text-align: left;}

/* For hide object and add pointer cursor */

.hideobject {display:none;}
.linkobject {cursor:pointer;}

/* For dragging lines */

.dragClass {color:#333333;}
td.showDragHandle {cursor:move;}
.tdlineupdown {white-space:nowrap;}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

td.vmenu {
	<?php if (GETPOST("optioncss") != 'print') {?>
    margin-right:2px;
    padding:0px;
    width:170px;
    <?php } ?>
}

div.fiche {
	padding:8px 12px 10px;
}


/* ============================================================================== */
/* Menu top et 1ère ligne tableau                                                 */
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
	line-height:36px;
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
div.tmenu li a:hover {color:rgba(255,255,255,.2);}
	
div.tmenu ul li a.tmenusel {/* texte du menu principal sélectionné */
	color:#ffffff;
	font-weight:bold;
}

.tmenudisabled {color:#d0d0d0 !important;}

/* --- end nav --- */

/* Login */

div.login_block {
	position:absolute;
	top:5px;
	right:10px;
	z-index:100;
	<?php if (GETPOST("optioncss") == 'print') {?>
	display:none;
	<?php } ?>
}

div.login_block a {color:rgba(255,255,255,.6);}
div.login_block a:hover {color:#ffffff}

div.login_block table {
	display:inline;
}

div.login {
	white-space:nowrap;
	padding:8px 0px 0px 0px;
	margin:0px 0px 0px 8px;
	font-weight:bold;
}

img.login, img.printer, img.entity {
	padding:8px 0px 0px 0px;
	margin:0px 0px 0px 8px;
	text-decoration:none;
	color:#ffffff;
	font-weight:bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

div.vmenu {
	<?php if (GETPOST("optioncss") == 'print') {?>
	display:none;
	<?php } else {?>
	width:170px;
	background-color:#dde2e9;
	<?php } ?>
}

.blockvmenupair .menu_titre, .blockvmenuimpair .menu_titre {
	height:22px;
	line-height:22px;
	text-align:center;
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
	padding-left:3px;
	border-top:solid 1px rgba(255,255,255,.5);
	border-bottom:solid 1px rgba(0,0,0,.5);
}

.blockvmenupair .menu_titre a, .blockvmenuimpair .menu_titre a {font-weight:normal;}

.menu_contenu {
	background-color:#ffffff;
	padding-left:12px;
	border-bottom:solid 1px rgba(0,0,0,.05);
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
	padding-left:18px;
}

#blockvmenuhelp {
	border-top:solid 1px rgba(0,0,0,.1);
	padding:12px;
	text-align:center;
}


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

.toolbar {}
.toolbarbutton {}


/* ============================================================================== */
/* Panes for ECM or Filemanager                                                   */
/* ============================================================================== */

#containerlayout .layout-with-no-border {
    border:0 !important;
    border-width:0 !important;
}

#containerlayout .layout-padding {
    padding:2px !important;
}

#containerlayout .ui-layout-pane {/* all 'panes' */
    background:#ffffff;
    border:1px solid #bbbbbb;
    padding:0px;
    overflow:auto;
}

#containerlayout .ui-layout-content {
	padding:10px;
	position:relative; /* contain floated or positioned elements */
	overflow:auto; /* add scrolling to content-div */
}

#containerlayout .pane-in.ecm-in-layout-center.ui-layout-pane.ui-layout-pane-center {
	border:0px solid #bbbbbb;
	border-bottom:1px solid #bbbbbb;
}

#containerlayout .pane-in.ecm-in-layout-south.layout-padding.ui-layout-pane.ui-layout-pane-south {
	border:0px solid #bbbbbb;
	border-top:1px solid #bbbbbb;
}

/* ============================================================================== */
/* Onglets                                                                        */
/* ============================================================================== */

div.tabs {
    margin-top:8px;
}

div.tabBar {
    background-color:#ffffff;
    padding:6px;
    margin:3px 0px 5px;
    border:1px solid #bbbbbb;
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
	vertical-align:top;
	margin-top:-1px;
}

.tab {
	margin-left:2px;
	margin-right:2px;
	padding:3px 0px 4px;
	padding-left:8px;
	padding-right:8px;
	background-color:rgba(0,0,0,.2);
	color:#666666;
	border:solid 1px rgba(0,0,0,.3);
	border-bottom:0px;
	-webkit-border-top-left-radius:6px;
	-webkit-border-top-right-radius:6px;
}
	
.tab#active {
	color:#232323;
	font-weight:bold;
	background-color:#ffffff;
	border-bottom:solid 1px #ffffff;
}
	
.tab:hover {color:#333333;}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */
 
div.fiche {
	margin-<?php print $left; ?>: <?php print (empty($conf->browser->phone) || empty($conf->global->MAIN_MENU_USE_JQUERY_LAYOUT))?'20':'24'; ?>px;
	margin-<?php print $right; ?>: <?php print empty($conf->browser->phone)?'12':'6'; ?>px;
}
 
div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichethirdleft {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 35%;\n"; } ?>
	<?php if (! empty($conf->browser->phone)) { print "padding-bottom: 6px;\n"; } ?>
}
div.fichetwothirdright {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 65%;\n"; } ?>
	<?php if (! empty($conf->browser->phone)) { print "padding-bottom: 6px\n"; } ?>
}
div.fichehalfleft {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if (empty($conf->browser->phone))   { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone))   { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if (empty($conf->browser->phone))   { print "padding-left: 16px;\n"; } ?>
}



/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

/* boutons : */
.button, .butAction {background: #999;border: solid 1px #666;}
.butActionRefused {background: #ccc;}
.butActionDelete {background: #b33c37;border:solid 1px #8d2f2b;}

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

.button,  a.butAction{color:white;}

.butActionDelete{color:white;}

td.formdocbutton {padding-top:6px;}

.button:hover, .butAction:hover, .butActionRefused:hover, .butActionDelete:hover {
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

/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

#undertopmenu {
}

table img {
	padding:0px 2px;
	vertical-align:middle;
}

table.liste img {
	padding:0px;
}

table a {
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

table.border {
	border:1px solid #bbbbbb;
	border-collapse:collapse;
}

table.border td {
	padding:1px 0px;
	border:1px solid #dddddd;
	border-collapse:collapse;
	padding-left:2px;
}

/*
td.border {
	border:1px solid #000000;
}
*/

/* Main boxes */

table.border.formdoc {
	background-color:#f7f7f7;
	border:1px solid #dddddd;
	margin:0px;
	width:60%;
}

table.border.formdoc td {padding:1px 3px;}

table.noborder {
	border:1px solid #bbbbbb;
	padding:0px;
	margin:3px 0px 8px;
	border-spacing:0px;
	-moz-box-shadow:2px 4px 2px #cccccc;
	-webkit-box-shadow:2px 4px 2px #cccccc;
	box-shadow:2px 4px 2px #cccccc;
}

table.noborder tr {}

table.noborder td {padding:1px 2px 1px 3px;}

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

tr.liste_titre, tr.box_titre {
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
}

tr.liste_titre td {
	padding:2px;
	padding-left:2px !important;
	white-space:nowrap;
}

tr.liste_titre td input.flat {
    width:70%;
}

td.liste_titre_sel {
	font-weight:bold;
	white-space:nowrap;
}

tr.liste_total td {
	padding:1px 2px;
	border-top:solid 1px #cccccc;
	background-color:#eaeaea;
	font-weight:bold;
	white-space:nowrap;
}

tr.impair td, tr.pair td {padding:1px 1px 1px 2px;}

tr.impair table.nobordernopadding td, tr.pair table.nobordernopadding td {padding:1px 0px;}

.impair {
	background:#f4f4f4;
	font-family:<?php print $fontlist ?>;
	border:0px;
}

.pair {
	background:#eaeaea;
	font-family:<?php print $fontlist ?>;
	border:0px;
}



/*
 *  Boxes
 */

.boxtable {
	-moz-box-shadow:2px 4px 2px #cccccc;
	-webkit-box-shadow:2px 4px 2px #cccccc;
	box-shadow:2px 4px 2px #cccccc;
	white-space:nowrap;
}

.box {
	padding-right:0px;
	padding-left:0px;
	padding-bottom:4px;
}

tr.box_impair {
	background:#f4f4f4;
	font-family:<?php print $fontlist ?>;
}

tr.box_pair {
	background:#eaeaea;
	font-family:<?php print $fontlist ?>;
}

tr.fiche {
	font-family:<?php print $fontlist ?>;
}

/*
 *   Ok, Warning, Error
 */

.ok {
	color:#159e26;
	background:url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/ok.png' ?>) left center no-repeat;
	padding-left:20px;
	font-weight:bold;
}

.warning {
	color:#bca936;
	background:url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/warning.png' ?>) left center no-repeat;
	padding-left:20px;
	font-weight:bold;
}

.error {
	color:#a61111;
	background:url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/error.png' ?>) left center no-repeat;
	padding-left:20px;
	font-weight:bold;
}

td.highlights {background:#f9c5c6;}

div.ok {
	background:#61e372 url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/ok.png' ?>) 3px center no-repeat;
	color:#ffffff;
	padding:2px 4px 2px 24px;
	margin:0.5em 0em;
	border:1px solid #159e26;
	font-weight:normal;
}

div.warning, div.info {
	background:#fcf5b8 url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/warning.png' ?>) 3px center no-repeat;
	color:#232323;
	padding:2px 4px 2px 24px;
	margin:0.5em 0em;
	border:1px solid #bca936;
	font-weight:normal;
}

div.error {
	background:#f58080 url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/error.png' ?>) 3px center no-repeat;
	color:#ffffff;
	padding:2px 4px 2px 24px;
	margin:0.5em 0em;
	border:1px solid #a61111;
	font-weight:normal;
}

/*
 *  Other
 */

.fieldrequired {
	font-weight:bold;
	color:#333333;
}

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
	-moz-box-shadow:2px 4px 2px #cccccc;
	-webkit-box-shadow:2px 4px 2px #cccccc;
	box-shadow:2px 4px 2px #cccccc;
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
	background-color:#a61111;
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
	color:#a61111;
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

.cal_other_month {
	background:#dddddd;
	border:solid 1px #bbbbbb;
}

.cal_past_month {
	background:#eeeeee;
	border:solid 1px #bbbbbb;
}

.cal_current_month {
	background:#ffffff;
	border:solid 1px #bbbbbb;
}

.cal_today {
	background:#ffffff;
	border:solid 2px #bbbbbb;
}

div.dayevent table.nobordernopadding tr td {padding:1px;}

table.cal_event {
	border-collapse:collapse;
	margin-bottom:1px;
}

.cal_event a:link {
	color:#232323;
	font-size:11px;
	font-weight:normal !important;
}

.cal_event a:visited {
	color:#232323;
	font-size:11px;
	font-weight:normal !important;
}

.cal_event a:active {
	color:#232323;
	font-size:11px;
	font-weight:normal !important;
}

.cal_event a:hover {
	color:rgba(255,255,255,.75);
	font-size:11px;
	font-weight:normal !important;
}


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
.ui-autocomplete-loading {background:#ffffff url(<?php echo DOL_URL_ROOT.'/theme/amarok/img/working.gif' ?>) right center no-repeat;}


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

/* CSS à appliquer à l'arbre hierarchique */

/* Lien plier / déplier tout */
.arbre-switch {
    text-align:right;
    padding:0 5px;
    margin:0 0 -18px 0;
}

/* Arbre */
ul.arbre {padding:5px 10px;}

/* strong:A modifier en fonction de la balise choisie */
ul.arbre strong {
    font-weight:normal;
    padding:0 0 0 20px;
    margin:0 0 0 -7px;
    background-image:url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/branch.gif' ?>);
    background-repeat:no-repeat;
    background-position:1px 50%;
}

ul.arbre strong.arbre-plier {
    background-image:url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/plus.gif' ?>);
    cursor:pointer;
}

ul.arbre strong.arbre-deplier {
    background-image:url(<?php echo DOL_URL_ROOT.'/theme/common/treemenu/minus.gif' ?>);
    cursor:pointer;
}

ul.arbre ul {
    padding:0;
    margin:0;
}

ul.arbre li {
    padding:0;
    margin:0;
    list-style:none;
}

/* This is to create an indent */
ul.arbre li li {margin:0 0 0 16px;}

/* Classe pour masquer */
.hide {display:none;}

img.menuNew {
	display:block;
	border:0px;
}

img.menuEdit {
	border:0px;
	display:block;
}

img.menuDel {
	display:none;
	border:0px;
}

div.menuNew {
	margin-top:-20px;
	margin-left:270px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuEdit {
	margin-top:-15px;
	margin-left:250px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuDel {
	margin-top:-20px;
	margin-left:290px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuFleche {
	margin-top:-16px;
	margin-left:320px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}


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

.ui-button {margin-left:-1px;}
.ui-button-icon-only .ui-button-text {height:8px;}
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text {padding:2px 0px 6px 0px;}
.ui-button-text {line-height:1em !important;}
.ui-autocomplete-input {margin:0; padding:1px;}


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */

.cke_editor table, .cke_editor tr, .cke_editor td {border:0px solid #FF0000 !important;}
span.cke_skin_kama {padding:0px !important;}


/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {height:72px !important;}
