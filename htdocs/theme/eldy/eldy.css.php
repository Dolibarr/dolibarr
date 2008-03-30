<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file       htdocs/theme/eldy/eldy.css.php
		\brief      Fichier de style CSS du theme Eldy
		\version    $Id$
*/

require("../../conf/conf.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (empty($conf->global->MAIN_FEATURES_LEVEL) || $conf->global->MAIN_FEATURES_LEVEL < 2)
{
	header('Cache-Control: max-age=3600, public, must-revalidate');
}
?>

/* ============================================================================== */
/* Styles par defaut                                                              */
/* ============================================================================== */

body {
    background-color: #F4F4F4;
	color: #101010;
	font-size: 12px;
    font-family: arial,tahoma,verdana,helvetica;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
}

a:link    { font-family: helvetica, verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:visited { font-family: helvetica, verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:active  { font-family: helvetica, verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:hover   { font-family: helvetica, verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
input
{
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{
    font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {
    font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
    font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{
    font-size: <?php print (eregi('MSIE 6',$_SERVER['HTTP_USER_AGENT']) ? "11" : "12"); ?>px;
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button
{
    font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
	border: 0px;
	background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/button_bg.png' ?>);
	background-position: bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button:focus  {
    font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
	color: #222244;
	border: 0px;
	background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/button_bg.png' ?>);
	background-position: bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.buttonajax
{
    font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
	border: 0px;
	background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/button_bg.png' ?>);
	background-position: bottom;
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

div.vmenuplusfiche
{
    top: auto;
    left: auto;

	position: static;
    float: left;

    display: block;
    margin-right: 6px;
    margin-left: 1px;
    margin-top: 2px;
}

div.vmenu
{
    float: left;

    margin-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 162px;
}

div.fiche
{
	float: right;
<?php
	//print "_SERVER['HTTP_USER_AGENT']=".$_SERVER['HTTP_USER_AGENT'];
	if (! eregi('MSIE 7\.0',$_SERVER['HTTP_USER_AGENT'])) print "    position: absolute;\n";
?>
    display: block;
	margin-left: 2px;
	margin-right: 2px;
    width:auto;
}

/* ============================================================================== */
/* Menu superieur et 1ere ligne tableau                                           */
/* ============================================================================== */

div.tmenu
{
    position: relative;
    display: block;
    white-space: nowrap;
    border-top: 1px solid #D3E5EC;
    border-left: 0px;
    border-right: 0px solid #555555;
    border-bottom: 1px solid #8B9999;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    font-weight: normal;
    font-size: 12px;
    height: 19px;
    background: #b3c5cc;
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu.jpg' ?>);
    color: #000000;
    text-decoration: none;
}
div.tmenu  .tmenudisabled
{
	color: #757575;
	font-size: 12px;
	padding-left:10px;
	padding-right:10px;
	padding-top:3px;
	cursor: not-allowed;
}

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
}

a.tmenu:link
{
  color: #234046;
  padding: 0px 5px 0px 5px;
  margin: 0px 1px 2px 1px;
  font-size: 12px; 
  font-family: helvetica, verdana, arial, sans-serif;
  font-weight: normal;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0px 5px 0px 5px;
  margin: 0px 1px 2px 1px;
  border: 0px solid #b3c5cc;
  font-size: 12px; 
  font-family: helvetica, verdana, arial, sans-serif;
  font-weight: normal;
}
a.tmenu#sel
{
  color: #234046;
  padding: 0px 5px 0px 5px;
  margin: 0px 0px 0px 0px;
  font-size: 12px;
  font-family: helvetica, verdana, arial, sans-serif;
  font-weight: normal;
  background: #F4F4F4;
/*  background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu_inverse.jpg' ?>); */
  border-right: 1px solid #555555;
  border-top: 1px solid #D8D8D8;
  border-left: 1px solid #D8D8D8;
  border-bottom: 2px solid #F4F4F4;
}
a.tmenu:hover
{
  color: #234046;
  padding: 0px 5px 0px 5px;
  margin: 0px 0px 0px 0px;
  text-decoration: none;
  font-size: 12px;
  font-family: helvetica, verdana, arial, sans-serif;
  font-weight: normal;
  background: #dee7ec;
  border-right: 1px solid #555555;
  border-top: 1px solid #D8D8D8;
  border-left: 1px solid #D8D8D8;
  border-bottom: 2px solid #dee7ec;
}

font.tmenudisabled
{
  color: #93a5aa;
  padding: 0px 5px 0px 5px;
  margin: 0px 0px 2px 0px;
  font-weight: normal;
  font-size: 12px;
  cursor: not-allowed;
}

a.tmenu:active
{
  padding: 0px 5px 0px 5px;
  margin: 0px 0px 0px 0px;
  text-decoration: none;
    font-size: 12px;
  font-family: helvetica, verdana, arial, sans-serif;
  font-weight: normal;
  background:#F4F4F4;
  border-right: 1px solid #555555;
  border-top: 1px solid #D8D8D8;
  border-left: 1px solid #D8D8D8;
  border-bottom: 2px solid #dee7ec;
}



/* Pour menu TOP auguria uniquement */
div.tmenu ul {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
	list-style: none;
	
}
div.tmenu li {
	float: left;
	border-right: solid 1px #7699A9;
	height: 18px;
	position:relative;
	display: block;
	margin:0;
	padding:0;
	}
div.tmenu li a{
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
* html div.tmenu li a{
	width:40px;
}
div.tmenu li a#sel
{
	background:#F4F4F4;
	color:#000000;
	font-weight: normal;
}
div.tmenu li a:visited
{
	color:#000000;
	font-weight: normal;
}
div.tmenu li a:hover
{
	background:#F4F4F4;
	color:#000000;
	font-weight: normal;
}
div.tmenu li a:active
{
	color:#4F9EC9;
	font-weight: normal;
}
div.tmenu li a:link
{
	
	font-weight: normal;
}



/* Login */

a.login
{
  position: absolute;
  right: 24px;
  top: 3px;

  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 1px 0px;
  font-weight: bold;
  font-size: 12px;
}
a.login:hover
{
  color: black;
}

img.login
{
  position: absolute;
  right: 8px;
  top: 3px;

  text-decoration: none;
  color: white;
  font-weight: bold;
}


/* ============================================================================== */
/* Barre de gauche                                                                */
/* ============================================================================== */

a.vmenu:link        { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:visited     { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:active      { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:hover       { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
font.vmenudisabled  { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; color: #93a5aa; }

a.vsmenu:link       { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:visited    { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:active     { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:hover      { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
font.vsmenudisabled { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #93a5aa; margin: 1px 1px 1px 6px; }

a.help:link         { font-size:11px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited      { font-size:11px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active       { font-size:11px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover        { font-size:11px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }


div.blockvmenupair
{
    width:160px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 0px 0px;
	background: #A3BCC6;
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu.jpg' ?>);
    background-position:top;
    background-repeat:repeat-x;
}

div.blockvmenuimpair
{
    width:160px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 0px 0px;
	background: #A3BCC6;
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu.jpg' ?>);
    background-position:top;
    background-repeat:repeat-x;
}

div.help
{
    width:160px;
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #f0f0f0;
	font-family: helvetica, verdana, arial, sans-serif;
	color: #000000;
	text-align:left;
	text-decoration: none;
    padding-left: 3px;
    padding-right: 1px;
    padding-top: 3px;
    padding-bottom: 3px;
    margin: 1px 0px 0px 0px;
}


td.barre {
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #b3c5cc;
	font-family: helvetica, verdana, arial, sans-serif;
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
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: left;
}

div.tabBar {
    color: #234046;
    background: #dee7ec;
    padding-top: 12px;
    padding-left: 12px;
    padding-right: 12px;
    padding-bottom: 12px;
    margin: 0px 0px 10px 0px;
    -moz-border-radius-topleft:6px;
    -moz-border-radius-topright:6px;
    -moz-border-radius-bottomleft:6px;
    -moz-border-radius-bottomright:6px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
    border-left: 1px solid #D0D0D0;
    border-top: 1px solid #D8D8D8;
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
    background: #436976;
    color: white;
	font-family: helvetica, verdana, arial, sans-serif;
    font-weight: normal;
    padding: 0px 6px;
    margin: 0px 6px;
    text-decoration: none;
    white-space: nowrap;
    border-right: 1px solid #555555;
    border-left: 1px solid #D8D8D8;
    border-top: 1px solid #D8D8D8;
}

a.tab:link {
    background: white;
    color: #436976;
	font-family: helvetica, verdana, arial, sans-serif;
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
    background: white;
    color: #436976;
	font-family: helvetica, verdana, arial, sans-serif;
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
    background: #dee7ec;
    border-bottom: #dee7ec 1px solid;
	font-family: helvetica, verdana, arial, sans-serif;
    color: #436976;
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
    background: #dee7ec;
    color: #436976;
	font-family: helvetica, verdana, arial, sans-serif;
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
	font-family: helvetica, verdana, arial, sans-serif;
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

a.butAction:link    {     font-size: 12px;
					  font-family: helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white;
                      border: 1px solid #8CACBB;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butAction:visited {     font-size: 12px;
					  font-family: helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white;
                      border: 1px solid #8CACBB;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butAction:active  {     font-size: 12px;
					  font-family: helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white;
                      border: 1px solid #8CACBB;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butAction:hover   {     font-size: 12px;
					  font-family: helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: #dee7ec;
                      border: 1px solid #8CACBB;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

.butActionRefused    {     font-size: 12px !important;
					  font-family: helvetica, verdana, arial, sans-serif !important;
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

a.butActionDelete    {     font-size: 12px;
					  font-family: helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white;
                      border: 1px solid red;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionDelete:link    { font-size: 12px; font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:active  { font-size: 12px; font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:visited { font-size: 12px; font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:hover   { font-size: 12px; font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; background: #FFe7ec; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

.nocellnopadd {
list-style-type:none;
margin: 0px;
padding: 0px;
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
border: 1px solid #9CACBB;
border-collapse: collapse;
}

table.border td {
padding: 1px 2px;
border: 1px solid #9CACBB;
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
table.nobordernopadding td {
border: 0px;
padding: 0px 0px;
}

table.liste {
border-collapse: collapse;
width: 100%;
}

tr.liste_titre {
background: #7699A9;
background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #334444;
font-family: helvetica, verdana, arial, sans-serif;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
td.liste_titre {
background: #7699A9;
background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #334444;
font-family: helvetica, verdana, arial, sans-serif;
font-weight: bold;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
td.liste_titre_sel
{
background: #7699A9;
background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #F5FFFF;
font-family: helvetica, verdana, arial, sans-serif;
font-weight: bold;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
input.liste_titre {
background: #7699A9;
background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
border: 0px;
}

tr.liste_total td {
border-top: 1px solid #888888;
background: #F4F4F4;
font-weight: bold;
white-space: nowrap;
}

th {
background: #7699A9;
color: #334444;
font-family: helvetica, verdana, arial, sans-serif;
font-weight: bold;
border-left: 1px solid #FFFFFF;
border-right: 1px solid #FFFFFF;
border-top: 1px solid #FFFFFF;
border-bottom: 1px solid #FFFFFF;
white-space: nowrap;
}

td.border {
border-top: 1px solid #000000;
border-right: 1px solid #000000;
border-bottom: 1px solid #000000;
border-left: 1px solid #000000;
}

.pair	{
background: #e6ebed;
font-family: helvetica, verdana, arial, sans-serif;
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
font-family: helvetica, verdana, arial, sans-serif;
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
background: #7699A9;
background-image: url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #334444;
font-size: 12px; 
font-family: arial, helvetica, verdana, sans-serif;
font-weight: bold;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
-moz-border-radius-topleft:6px;
-moz-border-radius-topright:6px;
}

tr.box_impair {
background: #e6ebed;
font-size: 12px; 
font-family: arial, helvetica, verdana, sans-serif;
}

tr.box_pair {
background: #d0d4d7;
font-size: 12px; 
font-family: arial, helvetica, verdana, sans-serif;
}

tr.fiche {
font-family: helvetica, verdana, arial, sans-serif;
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




/*
 *  Lignes titre espace
 */
div.titre {
	font-family: helvetica, verdana, arial, sans-serif;
	font-weight: bold;
	color: #336666;
	text-decoration: none }


/* ============================================================================== */
/* Formulaire confirmation                                                        */
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
    background: #D5BAA8;
}

.validtitre {
    background: #D5BAA8;
	font-weight: bold;
}


td.small {
           font-size: 10px;
           }

tr.nonpayed {
           font-weight: bold;
           }


div.version {
background: #F4F4F4;
text-align: right;
font-size: 9px;
margin: 1px 0em 0em 0em;
padding: 2px;
}


/* ============================================================================== */
/* Tooltips                                                                       */
/* ============================================================================== */

#dhtmltooltip
{
position: absolute;
width: 420px;
border-top: solid 1px #BBBBBB;
border-left: solid 1px #BBBBBB;
border-right: solid 1px #444444;
border-bottom: solid 1px #444444;
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
/*  Autre (telephonie)                                                            */
/* ============================================================================== */

#corpForm fieldset {	/*** Mise en forme des cadres ***/
	margin: 0;
	font-style: normal;
	padding: 0 1em 1em;
	font-size: 12px;
}

#corpForm .focus {	/*** Mise en avant des champs en cours d'utilisation ***/
	background: #FFF0F0;
	color: black;
}
#corpForm .normal {	/*** Retour e l'etat normal apres l'utilisation ***/
	background: white;
	color: black;
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

.cal_other_month   { background: #DDDDDD; border: solid 1px #ACBCBB; }
.cal_past_month    { background: #EEEEEE; border: solid 1px #ACBCBB; }
.cal_current_month { background: #FFFFFF; border: solid 1px #ACBCBB; }
.cal_today         { background: #DDFFDD; border: solid 1px #ACBCBB; }



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
	background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}

form.inplaceeditor-form a { /* The cancel link */
  margin-left: 5px;
  font-size: 11px;
	font-weight:normal;
	border: 0px;
	background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}


    
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
    background-image: url(<?php echo $dolibarr_main_url_root.'/admin/menus/images/img/arbre-puce.png' ?>);
    background-repeat: no-repeat;
    background-position: 1px 50%;
}
ul.arbre strong.arbre-plier {
    background-image: url(<?php echo $dolibarr_main_url_root.'/admin/menus/images/arbre-plier.png' ?>);
    cursor: pointer;
}
ul.arbre strong.arbre-deplier {
    background-image: url(<?php echo $dolibarr_main_url_root.'/admin/menus/images/arbre-deplier.png' ?>);
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
/* Uniquement pour les navigateurs a  moteur gecko */
ul.arbre li:last-child {
    border-left: 0;
    background: url(<?php echo $dolibarr_main_url_root.'/admin/mens/images/arbre-trait.png' ?>) no-repeat 0 0;
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