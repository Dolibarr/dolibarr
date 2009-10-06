<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C)      2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C)      2007 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file       htdocs/theme/rodolphe/rodoplhe.css.php
 *		\brief      Fichier de style CSS du theme Rodolphe
 *		\version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1'); // We need to use translation files to know direction
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');

//require_once("../../conf/conf.php");
require_once("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

if (! empty($_GET["lang"])) $langs->setDefaultLang($_GET["lang"]);	// If language was forced on URL by the main.inc.php
$langs->load("main",0,1);
$right=($langs->direction=='rtl'?'left':'right');
$left=($langs->direction=='rtl'?'right':'left');
?>


/***** Style du fond *****/
body {
<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
	background-color: #FFFFFF;
<?php } else { ?>
	background-color: #AAA5A0;
<?php } ?>
  font-size: 12px;
  font-family: helvetica, verdana, arial, sans-serif;
  margin-top: 0;
  margin-bottom: 0;
  margin-right: 0;
  margin-left: 0;
}


/***** Styles par defaut *****/
a:link    { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:visited { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:active  { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:hover   { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: underline; }
input
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
    border: 0px;
}
textarea  {
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
    border: 0px;
}
select.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
}
.button  {
	font-family: arial,verdana,helvetica, sans-serif;
	font-size: 100%;
	font-weight: normal;
	border: 1px solid #bbbb99;
	background-position : bottom;
}
form
{
    padding: 0em 0em 0em 0em;
    margin: 0em 0em 0em 0em;
}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

div.fiche
{
	margin-left: 4px;
	margin-right: 2px;
}

/* ============================================================================== */
/* Menu top et 1ere ligne tableau                                                 */
/* ============================================================================== */

div.tmenu
{
<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
	display:none;
<?php } else { ?>
    display:block;
    white-space: nowrap;
    border:0;
    border-right: 1px solid #000000;
    border-bottom: 1px solid #000000;
    padding: 2px 1em 0em;
    margin: 0em 0em 0.2em 0em;
    font-weight:bold;
    font-size:12px;
    height: 19px;
    background: #ded8d2;
    color: #000000;
    text-decoration: none;
<?php } ?>
}

a.tmenu:link
{
  color: #234046;
  padding: 0em 1em;
  margin: 0em 0em 1.5em 0em;
  border: 1px solid #ded8d2;
  font-weight:bold;
  font-size:12px;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0em 1em;
  margin: 0em 0em 1.5em 0em;
  border: 1px solid #ded8d2;
  font-weight:bold;
  font-size:12px;
}
a.tmenusel
{
  color: #ffffff;
  background: #78746d;
  padding: 0em 1em;
  margin: 0em 0em 1.5em 0em;
  border: 1px solid #78746d;
}
a.tmenu:hover
{
  color: #234046;
  background: #78746d;
  padding: 0em 1em;
  margin: 0em 0em 1.5em 0em;
  border: 1px solid #78746d;
  text-decoration: none;
}


/* Top menu */

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
}

ul.tmenu {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
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
li.tmenu a
{
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
* html li.tmenu a{
	width:40px;
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



/* Login */

a.login
{
  position: absolute;
  <?php print $right; ?>: 32px;
  top: 4px;

  color: #234046;
  padding: 0px 4px;
  margin: 0px 0px 1px 0px;
  border: 1px solid #ded8d2;
  font-weight:bold;
  font-size:12px;
}
a.login:hover
{
  color:black;
}

img.login
{
  position: absolute;
  <?php print $right; ?>: 20px;
  top: 5px;

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

<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
.vmenu {
	display:none;
}
<?php } ?>

td.vmenu
{
    padding-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 164px;
}

a.vmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }

a.vsmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
font.vsmenudisabled { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #aaa593; margin: 1em 1em 1em 1em; }

a.help:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }

div.blockvmenupair
{

  border-bottom: 1px solid #000000;
  background: #ded8d2;
  font-family: helvetica, verdana, arial, sans-serif;
  color: #000000;
  text-align:left;
  text-decoration: none;
  padding: 3px;
  margin: 0px 0px 0px 0px;
  }

div.blockvmenuimpair
{

  border-bottom: 1px solid #000000;
  background: #ded8d2;
  font-family: helvetica, verdana, arial, sans-serif;
  color: #000000;
  text-align:left;
  text-decoration: none;
  padding: 3px;
  margin: 0px 0px 0px 0px;
  }

div.help
{
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #f0f0f0;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
}


td.barre {
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #b3c5cc;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none
}

td.barre_select {
                  background: #b3c5cc;
                  color: #ffffff
}
td.photo {
           background: #FFFFFF;
           color: #000000
           }



/*
 *   Barre onglets
 */
div.tabBar {
    background: #dcdcd3;
    padding-top: 14px;
    padding-left: 14px;
    padding-right: 14px;
    padding-bottom: 14px;
    margin: 0px 0px 10px 0px;
    border: 1px solid #999999;
    border-top: 1px solid #999999;
}

div.tabs {
    top: 20px;
    margin: 1px 0em 0em 0em;
    padding: 0em 0.5em;
    text-align: left;
}

div.tabsAction {
    margin: 24px 0em 1px 0em;
  padding: 0em 0em;
  text-align: right;
}

a.tabTitle {
    background: #436976;
    border: 1px solid #8CACBB;
    color: white;
    font-weight: normal;
    padding: 0em 0.5em;
    margin: 0em 1em;
    text-decoration: none;
    white-space: nowrap;
}

a.tab:link {
  background: white;
  border: 1px solid #999999;
  color: #436976;
  padding: 0em 1em;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab:visited {
  background: white;
  border: 1px solid #999999;
  color: #436976;
  padding: 0em 1em;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab#active {
  background: #dcdcd3;
  border-bottom: #dcdcd3 1px solid;
  text-decoration: none;
}
a.tab:hover {
  background: #78746d;
  text-decoration: none;
}

a.tabimage {
    color: #436976;
    text-decoration: none;
    white-space: nowrap;
}


/*
 *   Boutons actions
 */

a.butAction:link    { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:visited { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:active  { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:hover   { font-family: helvetica, verdana, arial, sans-serif; background: #78746d; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }

.butActionRefused         { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #AAAAAA; color: #AAAAAA !important; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; cursor: not-allowed; }

a.butActionDelete:link    { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:active  { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:visited { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:hover   { font-family: helvetica, verdana, arial, sans-serif; background: #FFe7ec; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.nocellnopadd {
list-style-type:none;
margin:0px;
padding:0px;
cursor:move;
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
font-family: helvetica, verdana, arial, sans-serif;
border-collapse: collapse;
border: 1px white ridge;
}
table.border td {
border: 1px solid #6C7C8B;
padding: 1px 2px;
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
background: #ddddcc;
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

div.menus {
            background: #78746d;
            color: #bbbb88;
            font-size: 0.95em;
            border-top:    1px dashed #ccccb3;
            border-right:  1px dashed #ccccb3;
            border-bottom: 1px dashed #ccccb3;
            border-left:   1px dashed #ccccb3;
            }


a.leftmenu {
             font-weight: bold;
             color: #202020;
             }



div.leftmenu {
               background: #ccccb3;
               text-align: left;
               border-right: 1px solid #000000;
               border-bottom: 1px solid #000000;
               margin: 1px 0em 0em 0em;
               padding: 2px;
               }




/*
 *   Normal, warning, erreurs
 */
.ok      { color: #114466; }
.warning { color: #777711; }
.error   { color: #550000; }

td.warning
{
  background: #FF9988;
  font-weight: bold;
  font-size: 0.95em;
  color: #000000;
  text-decoration: none
}

div.ok {
  color: #114466;
}

div.warning {
  color: #777711;
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #000000;
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

#pictotitle {
	<?php print !empty($conf->browser->phone)?'display: none;':''; ?>
}

div.titre {
	font-family: helvetica, verdana, arial, sans-serif;
    font-weight: normal;
    color: #666633;
    text-decoration: none;
}


/*
 *  Tableaux
 */

input.liste_titre {
    background: #cc9966;
}

tr.liste_titre {
    background: #cc9966;
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
                 border-top: 1px solid #78746d;
                 border-left: 1px solid #78746d;
                 border-right: 1px solid #78746d;
                 white-space: nowrap;
}

tr.liste_search {
    background: #cc9966;
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
                 border-left: 1px solid #78746d;
                 border-right: 1px solid #78746d;
                  white-space: nowrap;
}

td.liste_titre {
    background: #cc9966;
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

.liste_titre_sel
{
    color: #fcfffc;
    background: #BBBB88;
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

tr.liste_total {
                 background: #F0F0F0;
                 font-weight: bold;
                 white-space: nowrap;
                 border-top: 1px solid #FFFFFF;
                 border-bottom: 1px solid #78746d;


}

th {
    background: #BBBB88;
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
    border-left: 1px solid #FFFFFF;
    border-right: 1px solid #FFFFFF;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
    font-size: 1.0em;
}

tr.pair {
          background: #ded8d2;
          font-size: 1.0em;
          border: 1px solid #78746d;
          }

tr.impair {
            background: #ded8d2;
            font-size: 1.0em;
            border: 1px solid #78746d;
            }

/*
 *
 */
tr.box_titre {
               background: #BBBB88;
               font-family: Helvetica, Verdana;
               font-size: 1.0em;
               font-weight: bold; }

tr.box_pair {
              background: #ded8d2;
              font-size: 0.95em; }

tr.box_impair {
                background: #78746d;
                font-family: Helvetica, Verdana;
                font-size: 0.95em; }

tr.fiche {
           font-family: Helvetica, Verdana;
           font-size: 0.6em; }
/*
 * Widgets standard
 */

td.delete {
            background: red;
            font-weight: bold;
            }

td.valid {
           background: pink;
           font-weight: bold;
           }


/* ============================================================================== */
/* Formulaire confirmation (HTML)                                                 */
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
/* Formulaire confirmation (AJAX)                                                 */
/* ============================================================================== */

.overlay_alert {
	background-color: #DDDDDD;
	filter: alpha(opacity=50); /* Does not respect CSS standard, but required to avoid IE bug */
	-moz-opacity: 0.5;
	opacity: 0.5;
}

.alert_nw {
	width: 5px;
	height: 5px;
	background: transparent url(alert/top_left.gif) no-repeat bottom left;
}

.alert_n {
	height: 5px;
	background: transparent url(alert/top.gif) repeat-x bottom left;
}

.alert_ne {
	width: 5px;
	height: 5px;
	background: transparent url(alert/top_right.gif) no-repeat bottom left
}

.alert_e {
	width: 5px;
	background: transparent url(alert/right.gif) repeat-y 0 0;
}

.alert_w {
	width: 5px;
	background: transparent url(alert/left.gif) repeat-y 0 0;
}

.alert_sw {
	width: 5px;
	height: 5px;
	background: transparent url(alert/bottom_left.gif) no-repeat 0 0;
}

.alert_s {
	height: 5px;
	background: transparent url(alert/bottom.gif) repeat-x 0 0;
}

.alert_se, .alert_sizer {
	width: 5px;
	height: 5px;
	background: transparent url(alert/bottom_right.gif) no-repeat 0 0;
}

.alert_close {
	width:0px;
	height:0px;
	display:none;
}

.alert_minimize {
	width:0px;
	height:0px;
	display:none;
}

.alert_maximize {
	width:0px;
	height:0px;
	display:none;
}

.alert_title {
	float:left;
	height:1px;
	width:100%;
}

.alert_content {
	overflow:visible;
	color: #000;
	font-family: Tahoma, Arial, sans-serif;
  	font: 12px arial;
	background: #FFF;
}

/* For alert/confirm dialog */
.alert_window {
	background: #FFF;
	padding:30px;
	margin-left:auto;
	margin-right:auto;
	width:400px;
}

.alert_message {
  font: 12px arial;
  text-align:left;
	width:100%;
	color:#012;
	padding-top:5px;
	padding-left:5px;
	padding-bottom:5px;
}

.alert_buttons {
	text-align:center;
	width:100%;
}

.alert_buttons input {
	width:20%;
	margin:5px;
}

.alert_progress {
	float:left;
	margin:auto;
	text-align:center;
	width:100%;
	height:16px;
	background: #FFF url('alert/progress.gif') no-repeat center center
}

.dialog {
	display: block;
	position: absolute;
}

.dialog table.table_window  {
  border-collapse: collapse;
  border-spacing: 0;
  width: 100%;
	margin: 0px;
	padding:0px;
}

.dialog table.table_window td , .dialog table.table_window th {
  padding: 0;
}

.dialog .title_window {
  -moz-user-select:none;
}



/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

#dhtmltooltip
{
position: absolute;
width: <?php print dol_size(450,'width'); ?>px;
border: 1px solid #444444;
padding: 2px;
background-color: lightyellow;
visibility: hidden;
z-index: 100;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */
.bodyline {
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
 background-color : #DED8D2;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : #DED8D2;
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

