<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net> 
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
 * 
 * $Id$
 * $Source$
 */

/**
		\file       htdocs/theme/freelug/freelug.css.php
		\brief      Fichier de style CSS du theme Freelug
		\version    $Revision$
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
	margin: 0px;
	background-color: #F8F8F8;
	background-image: url(<?php echo $dolibarr_main_url_root.'/theme/freelug/img/background.png' ?>);
	text-decoration: none ;
	color: #101010;
	font-size: 12px;
	font-family: helvetica, verdana, arial, sans-serif;

}

a:link    { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:visited { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:active  { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:hover   { font-family: helvetica, verdana, arial, sans-serif; font-weight: bold; color: #000000; text-decoration: underline; }
input
{ 
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif; 
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{ 
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif; 
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{
	font-size: 12px;
    font-family: helvetica, verdana, arial, sans-serif;
    background: #FFFFFF;
    border: 1px solid #8C9C9B;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{
    font-size: <?php print (eregi('MSIE 6',$_SERVER['HTTP_USER_AGENT']) ? "11" : "12"); ?>px;
	font-family: helvetica, verdana, arial, sans-serif;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button  {
	font-size: 11px;
	font-family: arial,verdana,heletica, sans-serif;
    padding: 0px 2px 0px 2px;
    margin: 0px 0px 0px 0px; 
	border-left: 1px solid #cccccc;
	border-right: 1px solid #aaaaaa;
	border-top: 1px solid #dddddd;
	border-bottom: 1px solid #aaaaaa;
	background-image: url(<?php echo $dolibarr_main_url_root.'/theme/freelug/img/button_bg.png' ?>);
	background-position: bottom;
	background-repeat: repeat-x;
}

form
{
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
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
    margin-top: 4px;
    
}

div.vmenu
{
    float: left;
    margin-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 160px;
}

div.fiche
{
<?php
	//print "_SERVER['HTTP_USER_AGENT']=".$_SERVER['HTTP_USER_AGENT'];
	if (! eregi('MSIE 7\.0',$_SERVER['HTTP_USER_AGENT'])) print "    position: absolute;\n";
?>
    display: block;
	margin-left: 2px;
	margin-right: 4px;
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
    font-weight: bold;
    font-size: 12px;
    height: 20px;
    background: #dddddd; 
    color: #000000; 
    text-decoration: none;
}

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 6px;
}

.menu 
{ 
  background: #ccccb3; 
  font-size: 12px;
  color: #000000; 
  text-decoration: none;
}


a.tmenu:link
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dddddd;
  font-weight: bold;
  font-size:12px;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #dddddd;
  font-weight: bold;
  font-size:12px;
}
a.tmenu#sel
{ 
  color: #202020;
  background: #bbbbcc;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #eeeeff;
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

font.tmenudisabled
{
  color: #93a5aa;
  padding: 0px 8px;
  margin: 0px 0px 6px 0px;
  border: 1px solid #b3c5cc;
  font-weight:bold;
  font-size:12px;
}


/* Pour menu TOP auguria uniquement */
div.tmenu ul {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
	list-style: none;
	
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
* html div.tmenu li a{
	width:40px;
}
div.tmenu li a#sel
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
  margin: 0px 0px 6px 0px;
  border: 1px solid #dcdcd0;
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
  right: 9px;
  top: 3px;

  padding:2px;
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
font.vmenudisabled { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #a3a590; }

a.vsmenu:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
font.vsmenudisabled { font-size:12px; font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #a3a590; margin: 1em 1em 1em 1em; }

a.help:link    { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active  { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover   { font-family: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }

div.blockvmenupair
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

div.blockvmenuimpair
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
           background: #DDDDDD;
		   font-size: 12px;
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


/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

/* Nouvelle syntaxe à utiliser */

a.butAction:link    { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:visited { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:active  { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butAction:hover   { font-family: helvetica, verdana, arial, sans-serif; background: #eeeedd; border: 1px solid #999999; color: #436969; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }

.butActionRefused         { font-family: helvetica, verdana, arial, sans-serif; background: white; border: 1px solid #AAAAAA; color: #AAAAAA !important; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; cursor: not-allowed; }

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

div.menus { 
            background: #eeeedd; 
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



/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #777711; }
.error   { color: #550000; font-weight: bold; }

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
 *  Lignes titre espace
 */
div.titre {
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
    color: #777799; 
    text-decoration: none
}


/*
 * Widgets standard
 */ 
input.liste_titre { 
    background: #777799;
    border: 0px;
}

tr.liste_titre { 
    color: #FFFFFF;
    background: #777799; 
    font-family: helvetica, verdana, arial, sans-serif;
    font-weight: bold;
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
    font-weight: bold;
    border-top: 1px solid #FFFFFF;
    border-bottom: 1px solid #FFFFFF;
    white-space: nowrap;
}

.liste_titre_sel
{ 
    color: #DCCCBB;
    background: #777799; 
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
    color: #FFFFFF;
    background: #777799; 
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
    background: #dcdcd0; 
    font-size: 12px; 
    }

.impair { 
    background: #eeeedd;           
    font-size: 12px; 
    }

/*
 *  Boxes
 */
tr.box_titre { 
               color: #FFFFFF;
               background: #777799;
               font-family: Helvetica, Verdana;
               font-size: 1.0em;
               font-weight: bold; }

tr.box_pair { 
              background: #dcdcd0; 
              font-size: 0.95em; }

tr.box_impair { 
                background: #eeeedd; 
                font-family: Helvetica, Verdana;
                font-size: 0.95em; }

tr.fiche { 
           font-family: Helvetica, Verdana;
           font-size: 0.6em; }

td.delete { 
  background: red;
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
    background: #DC9999;
}

.validtitre {
    background: #DC9999;
	font-weight: bold;
}

.valid {
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

