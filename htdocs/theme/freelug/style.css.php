<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/theme/freelug/style.css.php
 *		\brief      Fichier de style CSS du theme Freelug
 *		\version    $Id: style.css.php,v 1.44 2011/07/31 23:22:05 eldy Exp $
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

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));  // If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,1);
$right=($langs->trans("DIRECTION")=='rtl'?'left':'right');
$left=($langs->trans("DIRECTION")=='rtl'?'right':'left');
?>

/* ============================================================================== */
/* Styles by default                                                              */
/* ============================================================================== */

body {
<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background: #F8F8F8 url(<?php echo DOL_URL_ROOT.'/theme/freelug/img/background.png' ?>);
<?php } ?>
	text-decoration: none ;
	color: #101010;
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
	font-family: helvetica, verdana, arial, sans-serif;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
}

a:link    { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:visited { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:active  { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:hover   { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: underline; }
input
{
    font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input:disabled {
background:#ddd;
}
textarea  {
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
	font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea:disabled {
background:#ddd;
}
select.flat
{
	background: #FDFDFD;
    font-size: <?php print empty($conf->browser->phone)?'12':'9'; ?>px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button  {
	font-family: arial,verdana,heletica, sans-serif;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
	border-left: 1px solid #cccccc;
	border-right: 1px solid #aaaaaa;
	border-top: 1px solid #dddddd;
	border-bottom: 1px solid #aaaaaa;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/freelug/img/button_bg.png' ?>);
	background-position: bottom;
	background-repeat: repeat-x;
}
.buttonajax  {
	font-family: arial,verdana,heletica, sans-serif;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px;
	border-left: 1px solid #cccccc;
	border-right: 1px solid #aaaaaa;
	border-top: 1px solid #dddddd;
	border-bottom: 1px solid #aaaaaa;
	background-image: url(<?php echo DOL_URL_ROOT.'/theme/freelug/img/button_bg.png' ?>);
	background-position: bottom;
	background-repeat: repeat-x;
}
form
{
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
div.float
{
    float:<?php print $left; ?>;
}

/* For hide object and add pointer cursor */

.hideobject { display: none; }
.linkobject { cursor: pointer; }

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

div.fiche
{
	margin-left: 5px;
}

/* ============================================================================== */
/* Menu superieur et 1ere ligne tableau                                           */
/* ============================================================================== */

div.tmenu
{
<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
	display:none;
<?php } else { ?>
    position: relative;
    display: block;
    white-space: nowrap;
    border-top: 1px solid #D3E5EC;
    border-left: 0px;
    border-right: 0px solid #555555;
    border-bottom: 1px solid #8B9999;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    font-weight: bold;
    height: 20px;
    background: #dddddd;
    color: #000000;
    text-decoration: none;
<?php } ?>
}

a.tmenudisabled
{
	color: #757575;
    padding: 0px 8px;
    margin: 0px 0px 6px 0px;
	cursor: not-allowed;
}
a.tmenudisabled:link
{
	color: #757575;
    font-weight: normal;
}
a.tmenudisabled:visited
{
	color: #757575;
    font-weight: normal;
}
a.tmenudisabled:hover
{
	color: #757575;
    font-weight: normal;
}
a.tmenudisabled:active
{
	color: #757575;
    font-weight: normal;
}

a.tmenu:link
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dddddd;
  font-weight: bold;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dddddd;
  font-weight: bold;
}
a.tmenu:hover
{
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeedd;
  text-decoration: none;
}
a.tmenu:active
{
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeedd;
  text-decoration: none;
}

a.tmenusel
{
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeeff;
}


/* Top menu */

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
}

* html li.tmenu a
{
	width:40px;
}

ul.tmenu {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
	list-style: none;

}
li.tmenu {
	float: left;
	border-right: solid 1px #000000;
	height: 18px;
	position:relative;
	display: block;
	margin:0;
	padding:0;
}
li.tmenu a{
  	font-size: 13px;
	color:#000000;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 18px;
	display: block;
	font-weight: normal;
}
li.tmenu a.tmenusel
{
	background:#FFFFFF;
	color:#000000;
	font-weight: normal;
}
li.tmenu a:visited
{
	color:#000000;
	font-weight: normal;
}
li.tmenu a:hover
{
	background:#FFFFFF;
	color:#000000;
	font-weight: normal;
}
li.tmenu a:active
{
	color:#000000;
	font-weight: normal;
}
li.tmenu a:link
{

	font-weight: normal;
}

.tmenuimage {
    padding:0 0 0 0 !important;
    margin:0 0px 0 0 !important;
}


/* Login */

div.login_block {
	<?php if (GETPOST("optioncss") == 'print') { ?>
	display: none;
	<?php } ?>
}

div.login {
  position: absolute;
  <?php print $right; ?>: 30px;
  top: 3px;
  padding: 0px 8px;
  margin: 0px 0px 1px 0px;
  font-weight: bold;
}
div.login a {
	color: #234046;
}
div.login a:hover {
	color: black;
	text-decoration:underline;
}

img.login
{
  position: absolute;
  <?php print $right; ?>: 20px;
  top: 3px;

  text-decoration:none;
  color:white;
  font-weight:bold;
}
img.printer
{
  position: absolute;
  <?php print $right; ?>: 4px;
  top: 4px;

  text-decoration: none;
  color: white;
  font-weight: bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

<?php if ((! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print')) { ?>
.vmenu {
    display: none;
}
<?php } ?>

td.vmenu
{
    padding-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 180px;
}

a.vmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
font.vmenudisabled { font-size:<?php print empty($conf->browser->phone)?'12':'9'; ?>px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #a3a590; }

a.vsmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
font.vsmenudisabled { font-size:<?php print empty($conf->browser->phone)?'12':'9'; ?>px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #a3a590; margin: 1px 1px 1px 6px; }

a.help:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }


div.blockvmenupair, div.blockvmenuimpair
{
	border-right: 1px solid #555555;
	border-bottom: 1px solid #555555;
	background: #dddddd;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #202020;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}

div.blockvmenusearch
{
	border-right: 1px solid #555555;
	border-bottom: 1px solid #555555;
	background: #dddddd;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #202020;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}

div.blockvmenubookmarks
{
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
    background: #dddddd;
    font-family: helvetica, verdana, arial, sans-serif;
    color: #202020;
    text-align:left;
    text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}

div.blockvmenuhelp
{
<?php if (empty($conf->browser->phone)) { ?>
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #f0f0f0;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
<?php } else { ?>
    display: none;
<?php } ?>
}

td.barre {
    border-right: 1px solid #000000;
    border-bottom: 1px solid #000000;
    background: #DDDDDD;
    font-family: helvetica, verdana;
    color: #000000;
    text-align:left;
    text-decoration: none
}

td.barre_select {
           background: #b3cccc;
           color: #000000
}

td.photo {
	background: #FCFCFC;
	color: #000000;
    border: 1px solid #b3c5cc;
}


/* ============================================================================== */
/* Toolbar for ECM or Filemanager                                                 */
/* ============================================================================== */

.toolbar {
    background-image: url(<?php echo DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/tmenu2.png' ?>) !important;
    background-repeat: repeat-x !important;
    border: 1px solid #BBB !important;
}

.toolbarbutton {
    margin-top: 2px;
    margin-left: 4px;
/*    border: solid 1px #AAAAAA;
    width: 34px;*/
    height: 34px;
/*    background: #FFFFFF;*/
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
.ui-layout-pane { /* all 'panes' */
    background: #FFF;
    border:     1px solid #BBB;
    /* DO NOT add scrolling (or padding) to 'panes' that have a content-div,
       otherwise you may get double-scrollbars - on the pane AND on the content-div
    */
    padding:    0px;
    overflow:   auto;
    }
    /* (scrolling) content-div inside pane allows for fixed header(s) and/or footer(s) */
    .ui-layout-content {
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
    /* .ui-layout-resizer-open-hover , */ /* hover-color to 'resize' */
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

.ecm-in-layout-center {
    border-left: 0px !important;
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
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: left;
}

div.tabBar {
    background: #dcdcd0;
    padding-top: 14px;
    padding-left: 14px;
    padding-right: 14px;
    padding-bottom: 14px;
    margin: 0px 0px 10px 0px;
    border: 1px solid #9999BB;
    border-top: 1px solid #9999BB;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}

a.tabTitle {
    background: #bbbbcc;
    border: 1px solid #9999BB;
    color: #000000;
    font-weight: normal;
    padding: 0em 0.5em;
    margin: 0em 1em;
    text-decoration: none;
    white-space: nowrap;
}

a.tab:link {
  background: white;
  border: 1px solid #9999BB;
  color: #436976;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab:visited {
  background: white;
  border: 1px solid #9999BB;
  color: #436976;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab#active {
  background: #dcdcd0;
  border-bottom: #dcdcd0 1px solid;
  text-decoration: none;
}
a.tab:hover {
  background: #ebebe0;
  text-decoration: none;
}

a.tabimage {
    color: #436976;
    text-decoration: none;
    white-space: nowrap;
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

a.butAction:link    { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:visited { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:active  { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:hover   { font-family: helvetica, verdana, arial, sans-serif; background: #eeeedd; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }

.butActionRefused         { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #AAAAAA; color: #AAAAAA !important; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none !important; white-space: nowrap; cursor: not-allowed; }

a.butActionDelete:link    { font-family: helvetica, verdana, arial, sans-serif;
                      background: white;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:active    { font-family: helvetica, verdana, arial, sans-serif;
                      background: white;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:visited    { font-family: helvetica, verdana, arial, sans-serif;
                      background: white;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:hover    { font-family: helvetica, verdana, arial, sans-serif;
                      background: #FFe7ec;
                      border: 1px solid #997777;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

span.butAction, span.butActionDelete {
    cursor: pointer;
}


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.nocellnopadd {
list-style-type:none;
margin:0px;
padding:0px;
}

.notopnoleft {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-left: 0px;
padding-right: 4px;
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
margin: 0px 0px;
}

table.border {
border-collapse: collapse;
border: 1px white ridge;
}
table.border td {
border: 1px solid #6C7C8B;
padding: 1px 2px;
border-collapse: collapse;
}

table.noborder {
border-collapse: collapse;
border: 0px;
}
table.noborder td {
border: 0px;
padding: 1px 2px;
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

table.liste {
border-collapse: collapse;
border: 0px;
width: 100%;
}


/*
 *  Tableaux
 */






td.border {
            border-top: 1px solid #000000;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
            border-left: 1px solid #000000;
            }


/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #777711; }
.error   { color: #550000; font-weight: bold; }

td.highlights { background: #f9c5c6; }

div.ok {
  color: #114466;
}

div.warning {
  color: #777711;
  padding: 0.2em 0.2em 0.2em 0.2em;
  border: 1px solid #ebebd4;
  -moz-border-radius:6px;
  background: #efefd4;
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #969090;
}

div.info {
  color: #555555;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #ACACAB;
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

.fieldrequired { font-weight: bold; color: #000055; }

#pictotitle {
	<?php print !empty($conf->browser->phone)?'display: none;':''; ?>
}

.photo {
border: 0px;
/* filter:alpha(opacity=55); */
/* opacity:.55; */
}

div.titre {
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
    color: #777799;
    text-decoration: none;
}



input.liste_titre {
    background: #777799;
    border: 0px;
}

tr.liste_titre {
    color: #FFFFFF;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-left: 1px solid #FFFFFF;
    border-right: 1px solid #FFFFFF;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

td.liste_titre {
    color: #FFFFFF;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

.liste_titre_sel
{
    color: #DCCCBB;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

tr.liste_total td {
    background: #F0F0F0;
    font-weight: bold;
    white-space: nowrap;
    border-top: 1px solid #888888;
}

th {
    color: #FFFFFF;
    background: #777799;
    font-family: helvetica, verdana, arial, sans-serif;
    border-left: 1px solid #FFFFFF;
    border-right: 1px solid #FFFFFF;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}


.pair {
    background: #dcdcd0;
    }

.impair {
    background: #eeeedd;
    }

/*
 *  Boxes
 */

.boxtable {
-moz-box-shadow: 2px 4px 2px #AAA;
-webkit-box-shadow: 2px 4px 2px #AAA;
box-shadow: 2px 4px 2px #AAA;
}

.box {
	padding-right: 4px;
	padding-bottom: 4px;
}

tr.box_titre {
    color: #FFFFFF;
    background: #777799;
    font-family: Helvetica, Verdana;
}

tr.box_pair {
    background: #dcdcd0;
}

tr.box_impair {
    background: #eeeedd;
    font-family: Helvetica, Verdana;
}

tr.fiche {
    font-family: Helvetica, Verdana;
}



/* ============================================================================== */
/* Formulaire confirmation (When Ajax JQuery is used)                             */
/* ============================================================================== */

.ui-dialog-titlebar {
}
.ui-dialog-content {
    font-size: 12px !important;
}

/* ============================================================================== */
/* Formulaire confirmation (When HTML is used)                                    */
/* ============================================================================== */

table.valid {
    border-top: solid 1px #E6E6E6;
    border-left: solid 1px #E6E6E6;
    border-right: solid 1px #444444;
    border-bottom: solid 1px #555555;
	padding-top: 0px;
	padding-left: 0px;
	padding-right: 0px;
	padding-bottom: 0px;
	margin: 0px 0px;
    background: #DC9999;
}

.validtitre {
    background: #DC9999;
	font-weight: bold;
}

.valid {
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
	z-index: 3000;
	-moz-border-radius:8px;
	border: 1px #E4ECEC outset;
	padding:0px;
	margin-bottom:5px;
}
table.dp {
    width: 180px;
    background-color: #FFFFFF;
    border-top: solid 2px #DDDDDD;
    border-left: solid 2px #DDDDDD;
    border-right: solid 1px #222222;
    border-bottom: solid 1px #222222;
}
.dp td, .tpHour td, .tpMinute td{padding:2px; font-size:10px;}
/* Barre titre */
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color:#777799;
	color:white;
	font-size:11px;
	cursor:auto;
}
/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#bbbbcc;
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
.dpSelected{background-color:#777799;color:white;font-weight:bold; }

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
color:#062342;
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

.cal_other_month   { background: #DDDDDD; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { background: #EEEEEE; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #6C7C7B; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event    { border-collapse: collapse; margin-bottom: 1px; }
table.cal_event td { border: 0px; padding-left: 0px; padding-right: 2px; padding-top: 0px; padding-bottom: 0px; } */
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; }



/* ============================================================================== */
/* Admin Menu                                                                     */
/* ============================================================================== */

/* CSS a  appliquer a  l'arbre hierarchique */

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
	margin-left:270px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;
}

div.menuEdit
{
	margin-top:-15px;
	margin-left:250px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuDel
{
	margin-top:-20px;
	margin-left:290px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

}

div.menuFleche
{
	margin-top:-16px;
	margin-left:320px;
	height:20px;
	padding:0px;
	width:30px;
	position:relative;

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
 background-color : #DDDDDD;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : #DDDDDD;
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
 font-family: helvetica, verdana, arial, sans-serif;
 font-size : 8pt;
 color : black;
 text-align : center;
}
.btnColor {
 width : 100%;
 font-family: helvetica, verdana, arial, sans-serif;
 font-size : 10pt;
 padding : 0px;
 margin : 0px;
}
.btnPalette {
 width : 100%;
 font-family: helvetica, verdana, arial, sans-serif;
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
.ui-button { margin-left: -1px; }
.ui-button-icon-only .ui-button-text { height: 8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding: 3px 0px 8px 0px; }
.ui-button-text
{
    line-height: 1em !important;
}
.ui-autocomplete-input { margin: 0; padding: 1px; }


/* ============================================================================== */
/*  CKEditor                                                                      */
/* ============================================================================== */

.cke_editor table, .cke_editor tr, .cke_editor td
{
    border: 0px solid #FF0000 !important;
}
span.cke_skin_kama { padding: 0 ! important; }



/* ============================================================================== */
/*  File upload                                                                   */
/* ============================================================================== */

.template-upload {
    height: 72px !important;
}
