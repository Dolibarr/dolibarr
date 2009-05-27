<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       htdocs/theme/yellow/yellow.css
 *		\brief      Fichier de style CSS du theme Yellow
 *		\version    $Id$
 */

require("../../conf/conf.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (! isset($conf->global->MAIN_FEATURES_LEVEL) || $conf->global->MAIN_FEATURES_LEVEL < 2)
{
	header('Cache-Control: max-age=3600, public, must-revalidate');
}

?>

/* ============================================================================== */
/* Styles par défaut                                                              */
/* ============================================================================== */

body {
  background: #ebebe0;
  font-size: 12px;
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
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    border: 1px solid #cccccc;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    border: 1px solid #cccccc;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    border: 1px solid #cccccc;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    border: 1px solid #cccccc;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
	font-weight: normal;
    border: 1px solid #cccccc;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button
{
	font-family: arial,verdana,helvetica, sans-serif;
	font-size: 100%;
	font-weight: normal;
	border: 1px solid #bbbb99;
	background-image : url(/theme/yellow/img/button_bg.png);
	background-position : bottom;
}
.buttonajax
{
	font-family: arial,verdana,helvetica, sans-serif;
	font-size: 100%;
	font-weight: normal;
	border: 1px solid #bbbb99;
	background-image : url(/theme/yellow/img/button_bg.png);
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
/* Menu superieur et 1ere ligne tableau                                           */
/* ============================================================================== */

div.tmenu
{
    position: relative;
    display: block;
    white-space: nowrap;
    border: 0px;
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    font-weight: bold;
    font-size: 12px;
    height: 20px;
    background: #dcdcb3;
    color: #000000;
    text-decoration: none;
}
a.tmenudisabled
{
	color: #757575;
	font-size: 12px;
    padding: 0px 5px;
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

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
}


a.tmenu:link
{
  color: #234046;
  padding: 0px 5px;
  border: 1px solid #dcdcb3;
  font-weight:bold;
  font-size:12px;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0px 5px;
  border: 1px solid #dcdcb3;
  font-weight:bold;
  font-size:12px;
}
a.tmenu:hover
{
  color: #234046;
  background: #eeeecc;
  padding: 0px 5px;
  border: 1px solid #eeeecc;
  text-decoration: none;
}

a.tmenusel
{
  color: #234046;
  background: #eeeecc;
  padding: 0px 5px;
  border: 1px solid #eeeecc;
}



/* Pour menu TOP auguria uniquement */
* html div.tmenu li a
{
	width:40px;
}
div.tmenu ul {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
div.tmenu li {
	float: left;
	border-right: solid 1px #000000;
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
div.tmenu li a.tmenusel
{
	background:#FFFFFF;
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
	background:#FFFFFF;
	color:#000000;
	font-weight: normal;
}
div.tmenu li a:active
{
	color:#000000;
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
  border: 1px solid #dcdcb3;
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
/* Barre de gauche                                                                */
/* ============================================================================== */


a.vmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
font.vmenudisabled { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #aaa593; margin: 0em 0em 0em 0em; }

a.vsmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
a.vsmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1px 1px 1px 6px; }
font.vsmenudisabled { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #aaa593; margin: 1px 1px 1px 6px; }

a.help:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }

div.blockvmenupair
{
	width:160px;
	border-right: 1px solid #555555;
	border-bottom: 1px solid #555555;
	background: #dcdcb3;
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

div.blockvmenuimpair
{
	width:160px;
	border-right: 1px solid #555555;
	border-bottom: 1px solid #555555;
	background: #dcdcb3;
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
	text-decoration: none
}

td.barre_select {
	background: #b3c5cc;
	color: #000000
}
td.photo {
	background: #FFFFFF;
	color: #000000
}


/* ============================================================================== */
/* Onglets                                                                        */
/* ============================================================================== */

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
    margin: 1px 0px 0px 0px;
    padding: 0px 6px 0px 0px;
    text-align: left;
}

div.tabsAction {
    margin: 20px 0em 1px 0em;
    padding: 0em 0em;
    text-align: right;
}

a.tabTitle {
    background: #436976;
    border: 1px solid #8CACBB;
    color: white;
    font-weight: normal;
    padding: 0px 6px;
    margin: 0px 6px;
    text-decoration: none;
    white-space: nowrap;
}

a.tab:link {
  background: white;
  border: 1px solid #999999;
  color: #436976;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab:visited {
  background: white;
  border: 1px solid #999999;
  color: #436976;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
  white-space: nowrap;
}
a.tab#active {
  background: #dcdcd3;
  border-bottom: #dcdcd3 1px solid;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
}
a.tab:hover {
  background: #eeeecc;
  padding: 0px 6px;
  margin: 0em 0.2em;
  text-decoration: none;
}

a.tabimage {
    color: #436976;
    text-decoration: none;
    white-space: nowrap;
}


/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

a.butAction:link    { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:visited { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:active  { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:hover   { font-family: helvetica, verdana, arial, sans-serif; background: #eeeecc; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }

.butActionRefused         { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #AAAAAA; color: #AAAAAA !important; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none !important; white-space: nowrap; cursor: not-allowed; }

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
border: 1px white;
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


td.border {
            border-top: 1px solid #000000;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
            border-left: 1px solid #000000;
            }

div.menus {
            background: #eeeecc;
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

div.info {
  color: #777777;
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
 *  ???
 */
div.titre {
	font-family: helvetica, verdana, arial, sans-serif;
            font-weight: bold;
            color: #666633;
            text-decoration: none }

/*
 *  Tableaux
 */

input.liste_titre {
    background: #BBBB88;
    border: 0px;
}

tr.liste_titre {
    background: #BBBB88;
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
    border-bottom: 1px solid #000000;
    white-space: nowrap;
}

td.liste_titre {
    background: #BBBB88;
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

tr.liste_total td {
    background: #F0F0F0;
    font-weight: bold;
    white-space: nowrap;
    border-top: 1px solid #888888;
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

.pair {
    background: #eeeecc;
    font-size: 1.0em;
}

.impair {
    background: #dcdcb3;
    font-size: 1.0em;
}


/*
 *  Boxes
 */
tr.box_titre {
               background: #BBBB88;
               font-family: Helvetica, Verdana;
               font-size: 1.0em;
               font-weight: bold; }

tr.box_pair {
              background: #dcdcb3;
              font-size: 0.95em; }

tr.box_impair {
                background: #eeeecc;
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

td.small {
           font-size: 10px;
           }

tr.nonpayed {
           font-weight: bold;
           }


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
    background: pink;
}

.validtitre {
    background: #D5BAA8;
	font-weight: bold;
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
background-color: lightyellow;
visibility: hidden;
z-index: 100;
}


/* ============================================================================== */
/* Calendar                                                                       */
/* ============================================================================== */
.bodyline {
	-moz-border-radius:8px;
	border: 1px #ECECE4 outset;
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
	background-color:#ccc5b3;
	color:black;
	font-size:11px;
	cursor:auto;
}
/* Barre navigation */
.dpButtons,.tpButtons {
	text-align:center;
	background-color:#dcdcb3;color:#000000; font-weight:bold;
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
.dpSelected{background-color:#eeeecc;color:black;font-weight:bold; }

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
color:#222222;
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
table.cal_event td { border: 0px; padding-left: 0px; padding-right: 2px; padding-top: 0px; padding-bottom: 0px; } */
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
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/common/treemenu/branch.gif' ?>);
    background-repeat: no-repeat;
    background-position: 1px 50%;
}
ul.arbre strong.arbre-plier {
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/common/treemenu/plus.gif' ?>);
    cursor: pointer;
}
ul.arbre strong.arbre-deplier {
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/common/treemenu/minus.gif' ?>);
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

FIELDSET {
 padding : 0px;
 margin : 0px;
 color : black;
}
LEGEND {
 font-family : Tahoma;
 font-size : 10pt;
 color : black;
}
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
 border : 1px solid threedface;
}
.tblColor {
 display : none;
}
.tdColor {
 padding : 1px;
}
.tblContainer {
 background-color : threedface;
}
.tblGlobal {
 position : absolute;
 top : 0px;
 left : 0px;
 display : none;
 background-color : threedface;
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
 font-family : Tahoma;
 font-size : 8pt;
 color : black;
 text-align : center;
}
.btnColor {
 width : 100%;
 font-family : Tahoma;
 font-size : 10pt;
 padding : 0px;
 margin : 0px;
}
.btnPalette {
 width : 100%;
 font-family : Tahoma;
 font-size : 8pt;
 padding : 0px;
 margin : 0px;
}
