<?php
/* Copyright (C) 2012	Nicolas Péré	<nicolas@amarok2.net>
 * Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
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
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions.lib.php");

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
$fontlist='Helvetica,Verdana,Arial,sans-serif';
?>



/* STYLES COMMUNS : */
*, html{margin:0;padding:0;font-size:100%;}
body {
	background: #888 url(img/back_apple_03.png);
	color: #101010;
	margin:0;
	font-size: <?php print $fontsize ?>px;
    font-family: <?php print $fontlist ?>;
     <?php print 'direction: '.$langs->trans("DIRECTION").";\n"; ?>
	}

a {
	text-decoration: none;
	color:#333;
	}

a:hover, a:sel{
	color:black;
	}
img {width:16px; height:16px; vertical-align:bottom;}
img#pictotitle {width:32px; height:32px;}
td img {width:auto; height:auto;}

input, textarea {
	border-radius:4px;
	border:solid 1px rgba(0,0,0,.3);
	border-top:solid 1px rgba(0,0,0,.4);
	border-bottom:solid 1px rgba(0,0,0,.2);
	box-shadow: 1px 1px 2px rgba(0,0,0,.2) inset;
	padding:2px;
	margin-bottom:6px;
	}

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

.tabsAction {margin-top:12px !important; text-align:center;}
.menu_titre img{padding-bottom:2px;}

/* LOGIN : */
form#login {
	margin-top: 70px;
	margin-bottom: 30px;
	display:block;
	border:solid 1px rgba(0,0,0,.4);
	border-top:solid 1px white;
	background-color: #c7d0db;
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
	margin-left:auto;
	margin-right:auto;
	padding:12px;
	width:500px;
	border-radius: 12px;
	box-shadow: 0 0 16px rgba(0,0,0,.8);
	}


form#login img {
	width:120px;
	max-width:120px;
	height:auto;
	border-radius:6px;
	padding:6px;
	background-color: white;
	border:solid 1px rgba(0,0,0,.4);
	border-top:solid 1px rgba(0,0,0,.5);
	border-bottom:solid 1px rgba(0,0,0,.3);
	box-shadow: 1px 1px 6px rgba(0,0,0,.3) inset , 0 0 1px rgba(255,255,255,.6);
	}

form#login input{
	padding:8px;
	font-size:120%;
	}

form#login label {
	vertical-align:middle;
	line-height:46px;
	color:rgba(0,0,0,.4);
	text-shadow: 1px 1px 1px rgba(255,255,255,.6);
	}

form#login table.login{margin:0;border:none;background:none !important;}
table.login tr td a {color:#333 !important;}
table.login tr td a:hover {color:#000 !important;}

table.login .button {
	font-size:120%;
	background-color:#168ac2;
	color:white;
	padding:6px;
	border-radius:1.6em;
	border:solid 1px #2e7992;
	box-shadow: 1px 1px 3px rgba(0,0,0,.4);
	}

table.login .vmenu{
	color:rgba(0,0,0,.3);
	text-shadow: 1px 1px 1px rgba(255,255,255,.6);
	font-size:120%;
	}

td div.error{color:white;}


/* MENUS PRINCIPAUX : */
div.tmenu {
	position:fixed;
	margin:0;
	padding:0;
	padding-left:1em;
	top:0;
	left:0;
	right:0;
    white-space: nowrap;
	height:36px;
	line-height:36px;

	background: #168ac2; /* bleu */
	background: #b7e0e7; /* bleu_clair */
	background: #6d6887; /* violet */
	background: #333; /* obsidienne */

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

	border-bottom:solid 1px rgba(0,0,0,.8);
	box-shadow: 0 0 6px rgba(0,0,0,.4) !important;
	z-index:100;
	/* hack pour ie : */
	behavior: url(/theme/amarok/PIE.htc);
	}

div.tmenu li {
	display:inline-table;
	margin-right:1em;
	text-transform: uppercase;
	}
div.tmenu li a {
	color: #ccc;
	}
div.tmenu li a:hover {
	color: rgba(255,255,255,.2);
	}
div.tmenu ul li a.tmenusel{ /* texte du menu principal sélectionné */
	color: #fff;
	font-weight:bold;
}


/* PARTIE LOGIN : */
div.login_block {
	position:fixed;
	top:6px;
	right:6px;
	z-index:101;
	}
div.login_block a{color:white;color:rgba(255,255,255,.6);}


/* MENUS SUR LA GAUCHE : */
div.vmenu {
	position:fixed;
	top:37px;
	left:0;
	bottom:0;
	width:164px;
	border-right:solid 1px rgba(0,0,0,.3);
	background-color: #dde2e9;
	}

.blockvmenupair .menu_titre, .blockvmenuimpair .menu_titre {
	height:22px;
	line-height:22px;
	text-align:center;
	background-color:rgba(0,0,0,.08);
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
	padding-left:3px;
	border-top: solid 1px rgba(255,255,255,.5);
	border-bottom: solid 1px rgba(0,0,0,.5);

	}

.menu_contenu {
	background-color: white;
	padding-left:12px;
	border-bottom:solid 1px rgba(0,0,0,.05);
	}
.menu_contenu:hover{background-color:#f7f7f7;}
.menu_contenu a.vsmenu {color:black;line-height: 18px;}

.blockvmenusearch {
	border-top:solid 1px rgba(0,0,0,.3);
	padding:6px;
	padding-top: 22px;
	}

.blockvmenusearch .menu_titre {
	margin-top:6px;
	}


/* AIDE EN LIGNE : */
#blockvmenuhelp {
	border-top: solid 1px rgba(0,0,0,.1);
	padding:12px;
	text-align:center;
	}


/* ONGLETS : */
.tabs {
	margin-top:8px;
	margin-bottom:-2px;
	padding-bottom:0;
	}

.tabTitle {
	color:rgba(0,0,0,.5);
	margin-right:12px;
	text-shadow: 1px 1px 1px white;
	}

.tab {
	margin-left:2px;
	margin-right:2px;
	padding:2px;
	padding-left:8px;
	padding-right:8px;
	height:23px;
	background-color: rgba(0,0,0,.2);
	color:#666;
	border:solid 1px rgba(0,0,0,.3);
	border-bottom:solid 1px rgba(0,0,0,.08);
	-webkit-border-top-left-radius:6px;
	-webkit-border-top-right-radius:6px;
	}
.tab#active {
	color:#222;
	font-weight:bold;
	background-color: white;
	border-bottom: solid 1px white;
	}
.tab:hover{color:black;}

.tabBar table.border {border:none;}
.tabBar table.border tr td{margin-left:0 !important;padding-left:2px}

/* TABLEAU PRINCIPAL : */
table {
	margin:0;
	padding:0;
	border-collapse: collapse !important;
	}

table.liste{border:solid 0px #aaa; padding:.6em;}

table.liste tbody tr.liste_titre>td, table.liste .liste_total, table.liste .liste_titre {
	padding:4px;
	background-color:white;
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

tr.liste_titre td.liste_titre_sel {
	background-color: rgba(0,0,0,.2);
	color:black;
	}

table.liste a img{padding: 1px;}

table#undertopmenu { /* tableau contenant tous les autres : */
	display:block;
	position:fixed;
	top:37px;
	left:165px;
	bottom:0;
	background-color: #efefef;
	overflow-y: auto;
/* 	width:88.6%; */
	width:100%;
	}

tr.liste_titre td {
	background-color: rgba(255,255,255,.5);
	padding:2px;
	padding-left: 12px !important;
	border-top:solid 1px rgba(0,0,0,.08);
	color:rgba(0,0,0,.6);
	}

table.noborder, table.border, div.tabBar, table.liste {
	background-color:white;
	border:solid 1px #aaa;
	margin:.4em;
	width:99% !important;
	border-bottom:solid 2px #aaa;
	}

table.liste tr td img {padding-right:2px;}
table.noborder tr td{padding-right:2px;}
table.noborder tr.liste_total td{ /* total en bas d'un tableau */
	padding:2px;
	border-top: solid 1px #ccc;
	background-color: #eee;
	font-weight: bold;
	}

table.noborder tbody tr.liste_titre td { /* titre des tableaux : */
	color:black;
	background-color: #ccc;
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
	border-bottom:solid 1px rgba(0,0,0,.2);
	}

table.noborder tbody{
	border:solid 1px rgba(0,0,0,.3);
	border-bottom:solid 1px rgba(0,0,0,.08);
	-webkit-border-top-left-radius:6px;
	-webkit-border-top-right-radius:6px;
	}

table.noborder tr td .flat {margin-top:4px;margin-bottom:4px;}

table.noborder img {padding:1px;}
tr.impair {background-color: white;}
tr.pair {background-color:#f7f7f7;}
tr.impair td, tr.pair td {padding-left:8px;}
/*tr.impair:hover, tr.pair:hover {background-color:#dde6f2;} /* survol d'une ligne */




/* ! nobordernopadding : */
table tr td {/* padding-left:4px; */border:solid 1px #ddd;}
table.nobordernopadding tr td{/* padding-left:4px; */border-left:none;}
table.nobordernopadding tr td img {padding:2px;}
/* table.nobordernopadding tr.nobordernopadding td{margin-left:6px;} */

/* informations : */
.info {
	margin:1em;
	margin-left:3em;
	margin-right:3em;
	padding:1em;
	color:#645a53;
	background-color:#fcfeb5;
	border:solid 1px #fed86f;
	border-radius:1em;
	box-shadow: 3px 3px 6px rgba(0,0,0,.2);
	}