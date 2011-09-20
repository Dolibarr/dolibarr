<?php
/* Copyright (C) 2009-2010 Regis Houssin	<regis@dolibarr.fr>
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
 *		\file       htdocs/theme/phones/smartphone/theme/default/default.css.php
 *		\brief      Fichier de style CSS du theme Smartphone default
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

require_once("../../../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
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

?>
.ui-mobile-viewport {
/*width:600px;
height:600px;
min-height: 200px;
min-width: 600px;
overflow:scroll; */
}

.landscape, .landscape .ui-page {
}

#dol-homeheader { height: 40px; font-size: 16px; }

.ui-mobile-viewport {
    margin: 0;
}

.ui-header { height: 40px; font-size: 16px; }

.ui-content {
padding-top: 1px;
padding-right: 0;
padding-bottom: 0px;
padding-left: 1px;
}

.ui-content .ui-listview {
    margin-top: 0px;   /* Use here negative value of ui-content top padding */
    margin-right: 0px;
    margin-bottom: 0px;
    margin-left: 0px;
    /* overflow: scroll; */
}

.ui-mobile #dol-homeheader { padding: 10px 5px 0; text-align: center }
.ui-mobile #dol-homeheader h1 { margin: 0 0 10px; }
.ui-mobile #dol-homeheader p { margin: 0; }

.ui-li-icon {
	left:5px;
	top:0.3em;
}

.ui-li .ui-btn-inner {
    padding: 0.4em 5px 0.4em 5px;
}

input.ui-input-text, textarea.ui-input-text {
	padding: 0.2em;
}

.ui-body-b {
    background: #FFFFFF;
}

.ui-body-c {
    background: #FFFFFF;
    text-shadow: none;
}

.loginform {
    margin-left: 10px;
    margin-right: 10px;
    padding: 5px;
}




/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

div.fiche {
	margin-<?php print $left; ?>: <?php print (empty($conf->browser->phone)?'10':'2'); ?>px;
	margin-<?php print $right; ?>: <?php print (empty($conf->browser->phone)?'6':''); ?>px;
}

div.fichecenter {
	width: 100%;
	clear: both;	/* This is to have div fichecenter that are true rectangles */
}
div.fichethirdleft {
	<?php if (empty($conf->browser->phone)) { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone)) { print "width: 35%;\n"; } ?>
}
div.fichetwothirdright {
	<?php if (empty($conf->browser->phone)) { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone)) { print "width: 65%;\n"; } ?>
}
div.fichehalfleft {
	<?php if (empty($conf->browser->phone)) { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone)) { print "width: 50%;\n"; } ?>
}
div.fichehalfright {
	<?php if (empty($conf->browser->phone)) { print "float: ".$left.";\n"; } ?>
	<?php if (empty($conf->browser->phone)) { print "width: 50%;\n"; } ?>
}
div.ficheaddleft {
	<?php if (empty($conf->browser->phone)) { print "padding-left: 6px;\n"; } ?>
}


/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

.butAction, .butAction:link, .butAction:visited, .butAction:hover, .butAction:active, .butActionDelete, .butActionDelete:link, .butActionDelete:visited, .butActionDelete:hover, .butActionDelete:active {
    overflow: hidden;
    padding: 0.6em 25px;
    position: relative;
    white-space: nowrap;

	font-family: <?php print $fontlist ?>;
	background: white;
	border: 1px solid #8CACBB;
	color: #436976;
	padding: 0em 0.7em;
	margin: 0em 0.5em;
	text-decoration: none;
	white-space: nowrap;
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

span.butAction, span.butActionDelete {
	cursor: pointer;
}


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

/*
#undertopmenu {
background-image: url("<?php echo DOL_URL_ROOT.'/theme/eldy/img/gradient.gif' ?>");
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
height: 16px;
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



tr.liste_titre
{
    height: 24px;
    background: -moz-linear-gradient(center top , #81A8CE, #5E87B0) repeat scroll 0 0 #5E87B0;
    border: 1px solid #456F9A;
    color: #FFFFFF;
    font-family: <?php print $fontlist ?>;
    /* border-bottom: 1px solid #FDFFFF; */
    white-space: nowrap;
}
th.liste_titre, td.liste_titre
{
    background: -moz-linear-gradient(center top , #81A8CE, #5E87B0) repeat scroll 0 0 #5E87B0;
    border: 1px solid #456F9A;
    color: #FFFFFF;
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    /* border-bottom: 1px solid #FDFFFF; */
    white-space: nowrap;
    text-align: <?php echo $left; ?>;
}
th.liste_titre_sel, td.liste_titre_sel
{
    background: -moz-linear-gradient(center top , #81A8CE, #5E87B0) repeat scroll 0 0 #5E87B0;
    color: #FFFFFF;
    font-family: <?php print $fontlist ?>;
    font-weight: normal;
    /* text-decoration: underline; */
    /* border-bottom: 1px solid #FDFFFF; */
    white-space: nowrap;
    text-align: <?php echo $left; ?>;
}
input.liste_titre {
background: transparent;
background-repeat: repeat-x;
border: 0px;
}

tr.liste_total td {
border-top: 1px solid #DDDDDD;
background: #F0F0F0;
background-repeat: repeat-x;
color: #332266;
font-weight: normal;
white-space: nowrap;
}


.impair {
/* background: #d0d4d7; */
background: #eaeaea;
font-family: <?php print $fontlist ?>;
border: 0px;
}
/*
.impair:hover {
background: #c0c4c7;
border: 0px;
}
*/

.pair	{
/* background: #e6ebed; */
background: #f4f4f4;
font-family: <?php print $fontlist ?>;
border: 0px;
}
/*
.pair:hover {
background: #c0c4c7;
border: 0px;
}
*/



div.titre {
	padding-top: 10px;
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
	color: #336666;
	text-decoration: none;
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
    color: #234046;
    padding-top: 10px;
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
    background: #dee7ec url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/tab_background.png' ?>) repeat-x;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}


a.tabTitle {
	display: none;
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

