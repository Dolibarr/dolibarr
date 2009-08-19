<?php
/* Copyright (C) 2007      Patrick raguin      <patrick.raguin@gmail.com>
 * Copyright (C) 2008-2009 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *		\file       htdocs/theme/auguria/auguria.css.php
 *		\brief      Fichier de style CSS du theme Auguria
 *		\version    $Id$
 */

//require_once("../../conf/conf.php");
require_once("../../master.inc.php");

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

/* ============================================================================== */
/* Styles par d�faut                                                              */
/* ============================================================================== */

body {
    background-color: #FFFFFF;
	color: #101010;
	font-size: 12px;
    font-family: arial, sans-serif, verdana, helvetica;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
}

a:link    {font-weight: bold; color: #000000; text-decoration: none; }
a:visited {font-weight: bold; color: #000000; text-decoration: none; }
a:active  {font-weight: bold; color: #000000; text-decoration: none; }
a:hover   {font-weight: bold; color: #000000; text-decoration: none; }
input
{
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button
{
	font-size: 100%;
	border: 0px;
	background-image : url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/button_bg.png' ?>);
	background-position : bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.buttonajax
{
	font-size: 100%;
	border: 0px;
	background-image : url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/button_bg.png' ?>);
	background-position : bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
form
{
    padding: 0em 0em 0em 0em;
    margin: 0em 0em 0em 0em;
}


/* ============================================================================== */
/* Styles de positionnement des zones                                             */
/* ============================================================================== */

/* Removed div.vmenuplusfiche, replaced div.vmenu by td.vmenu, removed div.fiche */
div.vmenu
{
    margin-left: 2px;
}

td.vmenu
{
    margin-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 164px;
}

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
    position: relative;
	display: block;
    white-space: nowrap;
    border-left: 0px;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    font-size: 13px;
    background-image : url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/nav.jpg' ?>) ;
    height: 22px;
<?php } ?>
}

a.tmenudisabled
{
	color: #9FCED9;
	font-size: 12px;
	padding-left: 6px;
	padding-right: 6px;
	padding-top: 2px;
	cursor: not-allowed;
    font-weight: normal;
	font-size: 13px;
}
a.tmenudisabled:link
{
	color: #9FCED9;
    font-weight: normal;
}
a.tmenudisabled:visited
{
	color: #9FCED9;
    font-weight: normal;
}
a.tmenudisabled:hover
{
	color: #9FCED9;
    font-weight: normal;
}
a.tmenudisabled:active
{
	color: #9FCED9;
    font-weight: normal;
}

a.tmenu
{
  	color:#FFFFFF;
	text-decoration:none;
	padding-left:6px;
	padding-right:6px;
	padding-top: 2px;
	height: 22px;
	font-weight: normal;
	font-size: 13px;
}
a.tmenu:link
{
  	color:#FFFFFF;
    font-weight: normal;
}
a.tmenu:visited
{
  	color:#FFFFFF;
    font-weight: normal;
}
a.tmenu:hover
{
	color:#234046;
    font-weight: normal;
}
a.tmenu:active
{
	color:#234046;
    font-weight: normal;
}

a.tmenusel
{
	color:#234046;
	text-decoration:none;
	padding-left: 6px;
	padding-right: 6px;
	padding-top: 2px;
	height: 22px;
	font-weight: normal;
	background: #FFFFFF;
	font-size: 13px;
}
a.tmenusel:link
{
  font-weight: normal;
}
a.tmenusel:visited
{
  font-weight: normal;
}
a.tmenusel:hover
{
  font-weight: normal;
}
a.tmenusel:active
{
  font-weight: normal;
}

table.tmenu
{
    padding: 1px 0px 0px 0px;	// x y z w x=top offset
    margin: 0px 0px 0px 0px;
}

* html li.tmenu a
{
	width:40px;
}

ul.tmenu {
	padding: 3px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
	list-style: none;
}
li.tmenu {
	float: left;
	border-right: solid 1px #4F9EC9;
	height: 18px;
	position:relative;
	display: block;
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}


/* Login */

a.login
{
  position: absolute;
  right: 24px;
  top: 3px;
  color: #234046;
  padding: 0em 1em;
  margin: 0px 0px 1.5px 0px;
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
  right: 8px;
  top: 3px;

  text-decoration:none;
  color:white;
  font-weight:bold;
}


/* ============================================================================== */
/* Menu gauche                                                                    */
/* ============================================================================== */

<?php if (! empty($_GET["optioncss"]) && $_GET["optioncss"] == 'print') {  ?>
.vmenu {
	display:none;
}
<?php } ?>

a.vmenu:link        { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
a.vmenu:visited     { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
a.vmenu:active      { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
a.vmenu:hover       { font-size:12px; text-align:left; font-weight: normal; color: #FFFFFF; margin: 1px 1px 1px 4px; }
font.vmenudisabled  { font-size:12px; text-align:left; font-weight: normal; color: #9FCED9; margin: 1px 1px 1px 4px; }

a.vsmenu:link       { font-size:11px; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 4px; }
a.vsmenu:visited    { font-size:11px; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 4px; }
a.vsmenu:active     { font-size:11px; text-align:left; font-weight: normal; color: RGB(94,148,181); margin: 1px 1px 1px 4px; }
a.vsmenu:hover      { font-size:11px; text-align:left; font-weight: normal; color: RGB(94,148,181); margin: 1px 1px 1px 4px; }
font.vsmenudisabled { font-size:11px; text-align:left; font-weight: normal; color: #9FCED9; margin: 1px 1px 1px 4px; }

a.help:link         { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #68ACCF; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:visited      { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #68ACCF; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:active       { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #6198BA; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:hover        { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #6198BA; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


div.blockvmenupair
{
	margin: 0px;
	border-spacing: 0px;
	padding: 0px;
	width: 166px;
    border : 0px solid #68ACCF;
}
div.blockvmenuimpair
{
	margin: 0px;
	border-spacing: 0px;
	padding: 0px;
	width: 166px;
    border : 0px solid #68ACCF;
}


div.blockvmenuimpair form a.vmenu, div.blockvmenupair form a.vmenu
{
	width: 166px;
	color: #000000;
	text-align:left;
	text-decoration: none;
	padding: 4px;
	margin: 0px;
	background: #FFFFFF;
	margin-bottom: -12px;
}

div.help
{
	width: 166px;
    padding: 0px;
    margin-top: 15px;
    text-align: center;
}


/* Pour menu gauche Auguria */

div.menu_titre {
	background: url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/bg-titre-rubrique.png' ?>);
	padding: 0px;
	padding-top:7px;
	padding-left:0px;
	margin-top: 8px;
	margin: 0px;
	height: 21px;
    text-align: left;
    font-size : 12px;
    color : #FFFFFF;
    font-weight: bold;
}

div.menu_contenu {
	background: url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/bg-rubrique.png' ?>);
	margin: 0px;
	padding: 1px;

	padding-right: 8px;
    font-size : 11px;
    font-weight:normal;
    color : #000000;
    text-align: left;
}

div.menu_fin {
	background: url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/bg-bas-rubrique.png' ?>);
	margin: 0px;
	padding: 0px;
	height:6px;
    width:165px;
    background-repeat:no-repeat;
}


td.barre {
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #b3c5cc;
	color: #000000;
	text-align:left;
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
/* Onglets                                                                        */
/* ============================================================================== */

div.tabs {
    top: 20px;
    margin: 10px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: left;
}

div.tabBar {
    color: #234046;
    padding-top: 12px;
    padding-left: 12px;
    padding-right: 12px;
    padding-bottom: 12px;
    margin: 0px 0px 10px 0px;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;
    -moz-border-radius-bottomleft:6px;
    -moz-border-radius-bottomright:6px;
    border-right: 1px solid #68ACCF ;
    border-bottom: 1px solid #68ACCF;
    border-left: 1px solid #68ACCF;
    border-top: 1px solid #68ACCF;
    background: #F0F0F0 url(<?php echo DOL_URL_ROOT.'/theme/login_background.png' ?>) repeat-x;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}

/* onglet_inf ne sert qu'a telephonie. A virer */
div.onglet_inf
{
    position: relative;
    display: block;
    white-space: nowrap;
    padding: 0px 0px 0px 0px;
    margin: -12px 0px 0px 10px;
    font-weight:bold;
    font-size: 12px;
    height: 19px;
    color: #000000;
    text-decoration: none;
}

a.onglet_inf {
    background: white;
    color: #436976;
    padding: 0px 06px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius-bottomleft:6px;
    -moz-border-radius-bottomright:6px;

    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-bottom: 1px solid #555555;
}


a.tabTitle {
    background: #EEEEEE;
    color: #6198BA;
    font-weight: bold;
    padding: 0px 6px;
    margin: 0px 6px;
    text-decoration: none;
    white-space: nowrap;
    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

a.tab:link {
    background: #68ACCF;
    color: #FFFFFF;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}
a.tab:visited {
    background: #68ACCF;
    color: #FFFFFF;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    white-space: nowrap;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}
a.tab#active {
    background: #FFFFFF;
    border-bottom: #FFFFFF 1px solid;
    color: #6198BA;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}
a.tab:hover {
    background: #FFFFFF;
    color: #6198BA;
    padding: 0px 6px;
    margin: 0em 0.2em;
    text-decoration: none;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;

    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

a.tabimage {
    color: #436976;
    text-decoration: none;
    white-space: nowrap;
}

td.tab {
    background: #dee7ec;
}

/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

/* Nouvelle syntaxe a utiliser */

a.butAction:link    {
                      font-weight: bold;
                      background: #68ACCF;
                      border: 1px solid #8CACBB;
                      color: #FFFFFF;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butAction:visited {
                      font-weight: bold;
                      background: #68ACCF;
                      border: 1px solid #8CACBB;
                      color: #FFFFFF;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butAction:active  {
                      font-weight: bold;
                      background: white;
                      border: 1px solid #8CACBB;
                      color: #6198BA;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butAction:hover   {
                      font-weight: bold;
                      background: #FFFFFF;
                      border: 1px solid #8CACBB;
                      color: #6198BA;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionRefused    {
                      font-weight: bold;
                      background: white;
                      border: 1px solid #AAAAAA;
                      color: #AAAAAA;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap;
                      cursor: not-allowed;
                      }

a.butActionDelete    {
                      font-weight: bold;
                      background: red;
                      border: 1px solid red;
                      color: red;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:link    {  font-weight: bold; background: white; border: 1px solid #997777; color: #801111; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:active  {  font-weight: bold; background: white; border: 1px solid #997777; color: #801111; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:visited {  font-weight: bold; background: white; border: 1px solid #997777; color: #801111; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:hover   {  font-weight: bold; background: #FFe7ec; border: 1px solid #997777; color: #801111; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


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
border: 0px solid #9CACBB;
border-collapse: collapse;
}
table.border td {
padding: 2px 2px;
padding-left: 4px;
border: 1px solid #9CACBB;
border-collapse: collapse;
}


table.noborder {
border: 1px solid #FFFFFF;
border-spacing: 1px;
}
table.noborder td {
border: 0px;
padding: 1px 2px;
}


table.nobordernopadding {
border-collapse: collapse;
border: 0px;
}
table.nobordernopadding td {
border: 0px;
padding: 0px 0px;
}

table.liste {
border-collapse: collapse;
width: 100%;
border: 0px;
}

tr.liste_titre {
background: #68ACCF;
color: #FFFFFF;
font-size: 12px;
font-family: arial, helvetica, verdana, sans-serif;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;

}

td.liste_titre {
background: #68ACCF;
color: #FFFFFF;
font-size: 12px;
font-family: arial, helvetica, verdana, sans-serif;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;

}

td.liste_titre_sel
{
background: #68ACCF;
color: #556262;
font-size: 12px;
font-family: arial, helvetica, verdana, sans-serif;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: normal;
  -moz-border-radius-topleft:6px;
  -moz-border-radius-topright:6px;
}
input.liste_titre {
background: #68ACCF;
border: 0px;
}

tr.liste_total td {
border-top: 0px solid #888888;
background: #F4F4F4;
font-weight: bold;
white-space: normal;
}

th {
background: #7699A9;
color: #334444;

font-weight: bold;
border-left: 1px solid #FFFFFF;
border-right: 1px solid #FFFFFF;
border-top: 1px solid #FFFFFF;
border-bottom: 1px solid #FFFFFF;
white-space: normal;
}

td.border {
border-top: 1px solid #000000;
border-right: 1px solid #000000;
border-bottom: 1px solid #000000;
border-left: 1px solid #000000;
}

.pair	{
background: #e6ebed;

border: 0px;
}
/*
.pair:hover {
background: #c0c4c7;
border: 0px;
}
*/

.impair {
background: #d0d4d7;

border: 0px;
}
/*
.impair:hover {
background: #c0c4c7;
border: 0px;
}
*/

/*
 *  Boxes
 */
table.box {
margin: 2px;
}

tr.box_titre {
background: #68ACCF;
color: #FFFFFF;
font-size: 12px;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: normal;
  -moz-border-radius-topleft:6px;
  -moz-border-radius-topright:6px;
}

tr.box_impair {
background: #e6ebed;
font-size: 12px;
}

tr.box_pair {
background: #d0d4d7;
font-size: 12px;
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
}

div.error {
  color: #550000; font-weight: bold;
  padding: 0.2em 0.2em 0.2em 0.2em;
  margin: 0.5em 0em 0.5em 0em;
  border: 1px solid #8C9CAB;
}

div.info {	/* Info admin */
  color: #888888;
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


td.small {
           font-size: 10px;
           }


/*
 *  Lignes titre espace
 */
div.titre {
	font-weight: bold;
	color: #57A2CA;
	text-decoration: none }


/* ============================================================================== */
/* Formulaire confirmation (HTML)                                                 */
/* ============================================================================== */

td.validtitle {
           font-weight: bold;
           background: #EECC55;
           }
td.valid {
           background: #EECC55;
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
/* Admin Menu                                                                       */
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
/* Tooltips                                                                       */
/* ============================================================================== */

#dhtmltooltip
{
position: absolute;
width: 450px;
border: 1px solid #444444;
padding: 2px;
background-color: #FFFFE0;
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
.dpHead,.tpHead,.tpHour td:Hover .tpHead{
	font-weight:bold;
	background-color:#b3c5cc;
	color:white;
	font-size:11px;
	cursor:auto;
}
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#617389;color:#FFFFFF; font-weight:bold;
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
.dpSelected{background-color:#0B63A2;color:white;font-weight:bold; }

.tpHour{border-top:1px solid #DDDDDD; border-right:1px solid #DDDDDD;}
.tpHour td {border-left:1px solid #DDDDDD; border-bottom:1px solid #DDDDDD; cursor:pointer;}
.tpHour td:Hover {background-color:black;color:white;}

.tpMinute {margin-top:5px;}
.tpMinute td:Hover {background-color:black; color:white; }
.tpMinute td {background-color:#D9DBE1; text-align:center; cursor:pointer;}

.dpInvisibleButtons
{
border-style:none;
background-color:transparent;
padding:0px;
font-size:9px;
border:0px;
color:#0B63A2;
vertical-align:middle;
cursor: pointer;
}


/* ============================================================================== */
/*  Module agenda                                                                 */
/* ============================================================================== */

.cal_other_month   { background: #DDDDDD; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_past_month    { background: #EEEEEE; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_current_month { background: #FFFFFF; border: solid 1px #ACBCBB; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
.cal_today         { background: #FFFFFF; border: solid 2px #6C7C7B; padding-left: 2px; padding-right: 1px; padding-top: 0px; padding-bottom: 0px; }
table.cal_event    { border-collapse: collapse; margin-bottom: 1px; }
table.cal_event td { border: 0px; padding-left: 0px; padding-right: 2px; padding-top: 0px; padding-bottom: 0px; }
.cal_event a:link    { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:visited { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:active  { color: #111111; font-size: 11px; font-weight: normal !important; }
.cal_event a:hover   { color: #111111; font-size: 11px; font-weight: normal !important; }



/* ============================================================================== */
/*  Ajax - Liste d�roulante de l'autocompletion                                   */
/* ============================================================================== */

div.autocomplete {
      position:absolute;
      width:250px;
      background-color:white;
      border:1px solid #888;
      margin:0px;
      padding:0px;
    }
div.autocomplete ul {
      list-style-type:none;
      margin:0px;
      padding:0px;
    }
div.autocomplete ul li.selected { background-color: #D3E5EC;}
div.autocomplete ul li {
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
	background-image : url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}

form.inplaceeditor-form a { /* The cancel link */
  margin-left: 5px;
  font-size: 11px;
	font-weight:normal;
	border: 0px;
	background-image : url(<?php echo DOL_URL_ROOT.'/theme/auguria/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}

/* ============================================================================== */
/*  Affichage tableau Excel                                                       */
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
 background-color : #FFFFFF;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : #FFFFFF;
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
