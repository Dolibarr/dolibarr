<?php
/* Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

header('Content-type: text/css');

require("../../conf/conf.php");

?>

/***** Style du fond *****/
body {
    background-color: #F4F4F4;
	font: 12px helvetica, verdana, arial, sans-serif;
    margin-top: 0;
    margin-bottom: 0;
    margin-right: 0;
    margin-left: 0;
}
.body {
    background-color: #F4F4F4;
	color: #101010;
	font: 12px helvetica, verdana, arial, sans-serif;
}

/***** Styles par défaut *****/
a:link    { font: verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:visited { font: verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:active  { font: verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: none; }
a:hover   { font: verdana, arial, helvetica, sans-serif; font-weight: bold; color: #000000; text-decoration: underline; }
input
{ 
    font: 12px helvetica, verdana, arial, sans-serif; 
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{ 
    font: 12px helvetica, verdana, arial, sans-serif;
    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {
    font: 12px helvetica, verdana, arial, sans-serif;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{ 
    font: 12px helvetica, verdana, arial, sans-serif;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{
    font: 12px helvetica, verdana, arial, sans-serif;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button  {
    font: 12px helvetica, verdana, arial, sans-serif;
	font-size: 100%;
	border: 0px;
	background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/button_bg.png' ?>);
	background-position : bottom;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}


div.body
{
    margin-top: 0px;
    margin-bottom: 0;
    margin-right: 0px;
    margin-left: 0px;
    padding: 0px;
    font-family:arial,tahoma,verdana,helvetica;
    font-size:12px;
}

div.vmenuplusfiche
{
    top: auto;
    left: auto;
    position: static;
    float: left; 
    display: block;
    margin-right: 6px;
    margin-left: 1px;
}

div.vmenu
{
    top: auto;
    left: auto;
    position: relative;
    float: top;
    display: block;
    margin-right: 2px;
    padding: 0px;
    padding-bottom: 0px;
    width: 160px;
}

div.fiche
{
    top: 28px;
    left: auto;
    position: absolute;
    float: top;
    display: block;
    margin-right: 6px;
    margin-top: 0px;
}


/*
 *   Menu superieur et 1ere ligne tableau
 */

div.tmenu
{
    position: relative;
    float: top;
    display: block;
    white-space: nowrap;
    border-top: 1px solid #D3E5EC;
    border-left: 0px;
    border-right: 0px solid #555555;
    border-bottom: 1px solid #8B9999;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    font-weight:bold;
    font-size: 12px;
    height: 19px;
    background: #b3c5cc; 
    background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu.jpg' ?>);
    color: #000000; 
    text-decoration: none;
}

table.tmenu
{
    padding: 0px 0px 10px 0px;
    margin: 0px 0px 0px 10px;
}

a.tmenu:link
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 1px 1.5px 1px;
  font: 12px helvetica, verdana, arial, sans-serif;
  font-weight: bold;
}
a.tmenu:visited
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 1px 1.5px 1px;
  border: 0px solid #b3c5cc;
  font: 12px helvetica, verdana, arial, sans-serif;
  font-weight: bold;
}
a.tmenu#sel
{ 
  color: #234046;
  padding: 0px 8px;
  margin: 0px 0px 1.5px 0px;
  font: 12px helvetica, verdana, arial, sans-serif;
  font-weight: bold;
  background: #dee7ec;
    background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu_inverse.jpg' ?>);
  border-right: 1px solid #555555;
  border-top: 1px solid #D8D8D8;
  border-left: 1px solid #D8D8D8;
}
a.tmenu:hover
{
  color: #234046;
  padding: 0px 8px;
  margin: 0px 1px 1.5px 1px;
  text-decoration: none;
  font: 12px helvetica, verdana, arial, sans-serif;
  font-weight: bold;
  background: #dee7ec;
}

font.tmenudisabled
{
  color: #93a5aa;
  padding: 0px 8px;
  margin: 0px 0px 1.5px 0px;
  font-weight:bold;
  font-size:12px;
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


/*
 *   Barre de gauche
 */

a.vmenu:link        { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:visited     { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:active      { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
a.vmenu:hover       { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; }
font.vmenudisabled  { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: bold; color: #93a5aa; }

a.vsmenu:link       { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:visited    { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:active     { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:hover      { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
font.vsmenudisabled { font-size:12px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; color: #93a5aa; margin: 1em 1em 1em 1em; }

a.help:link         { font-size:11px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:visited      { font-size:11px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:active       { font-size:11px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }
a.help:hover        { font-size:11px; font: helvetica, verdana, arial, sans-serif; text-align:left; font-weight: normal; }

div.blockvmenupair
{
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
	font: helvetica, verdana, arial, sans-serif;
	color: #000000; 
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
	background: #A3BCC6;
    background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu.jpg' ?>);
    background-position:top;
    background-repeat:repeat-x;
}

div.blockvmenuimpair
{
    border-right: 1px solid #555555;
    border-bottom: 1px solid #555555;
	font: helvetica, verdana, arial, sans-serif;
	color: #000000; 
	text-align:left;
	text-decoration: none;
    padding: 3px;
    margin: 1px 0px 0px 0px;
	background: #A3BCC6;
    background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/tmenu.jpg' ?>);
    background-position:top;
    background-repeat:repeat-x;
}

div.help
{
	border-right: 1px solid #000000;
	border-bottom: 1px solid #000000;
	background: #f0f0f0;
	font: helvetica, verdana, arial, sans-serif;
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
	font: helvetica, verdana, arial, sans-serif;
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

form {
    padding: 0em 0em 0em 0em;
    margin: 0em 0em 0em 0em;
}


/*
 *   Barre recherche
 */
div.formsearch
{
  display: block;
  clear: right;
  background: #d0d4d7;
  top: 0px;
  left: 130px;
  padding: 4px; 
  vertical-align: center;
  margin-bottom: 10px;
}


/*
 *   Barre onglets
 */
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


a.tabTitle {
    background: #436976;
    color: white;
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


/*
 *   Boutons actions
 *   Nouvelle syntaxe à utiliser
 */

a.butAction:link    { font: 12px helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white; 
                      border: 1px solid #8CACBB; 
                      color: #436976; 
                      padding: 0em 0.7em; 
                      margin: 0em 0.5em; 
                      text-decoration: none; 
                      white-space: nowrap; }

a.butAction:visited { font: 12px helvetica, verdana, arial, sans-serif; 
                      font-weight: bold;
                      background: white; 
                      border: 1px solid #8CACBB; 
                      color: #436976; 
                      padding: 0em 0.7em; 
                      margin: 0em 0.5em; 
                      text-decoration: none; 
                      white-space: nowrap; }

a.butAction:active  { font: 12px helvetica, verdana, arial, sans-serif; 
                      font-weight: bold;
                      background: white; 
                      border: 1px solid #8CACBB; 
                      color: #436976;
                      padding: 0em 0.7em; 
                      margin: 0em 0.5em; 
                      text-decoration: none; 
                      white-space: nowrap; }

a.butAction:hover   { font: 12px helvetica, verdana, arial, sans-serif; 
                      font-weight: bold;
                      background: #dee7ec; 
                      border: 1px solid #8CACBB; 
                      color: #436976; 
                      padding: 0em 0.7em; 
                      margin: 0em 0.5em; 
                      text-decoration: none; 
                      white-space: nowrap; }

a.butActionRefuse    { font: 12px helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white; 
                      border: 1px solid red; 
                      color: #436976; 
                      padding: 0em 0.7em; 
                      margin: 0em 0.5em; 
                      text-decoration: none; 
                      white-space: nowrap; }

a.butActionRefuse:hover { font: 12px helvetica, verdana, arial, sans-serif;
                          font-weight: bold;
                          background: #dee7ec; }


a.butActionDelete    { font: 12px helvetica, verdana, arial, sans-serif;
                      font-weight: bold;
                      background: white; 
                      border: 1px solid red; 
                      color: #436976; 
                      padding: 0em 0.7em; 
                      margin: 0em 0.5em; 
                      text-decoration: none; 
                      white-space: nowrap; }

a.butActionDelete:link    { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:active  { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:visited { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butActionDelete:hover   { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: #FFe7ec; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


/*
* Historique
* A supprimer quand remplace par butXxx
*/

a.tabAction:link    { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #8CACBB; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.tabAction:visited { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #8CACBB; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.tabAction:active  { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #8CACBB; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.tabAction:hover   { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: #dee7ec; border: 1px solid #8CACBB; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
                             
a.butDelete:link    { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butDelete:active  { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butDelete:visited { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: white; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.butDelete:hover   { font: 12px helvetica, verdana, arial, sans-serif; font-weight: bold; background: #FFe7ec; border: 1px solid #997777; color: #436976; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }

                      
/*
 *   Tables
 */

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
border-width: 1px;
border-collapse: collapse;
}

table.border td {
padding: 1px 2px;
border: 1px solid #9CACBB;
border-width: 1px;
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


/*
 *  Tableaux
 */ 

tr.liste_titre { 
background: #7699A9;
background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #334444;
font: helvetica, verdana, arial, sans-serif;
font-weight: bold;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
td.liste_titre { 
background: #7699A9;
background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #334444;
font: helvetica, verdana, arial, sans-serif;
font-weight: bold;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
td.liste_titre_sel
{ 
background: #7699A9;
background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #F5FFFF; 
font: helvetica, verdana, arial, sans-serif;
font-weight: bold; 
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}
input.liste_titre { 
background: #7699A9;
background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
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
font: helvetica, verdana, arial, sans-serif;
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
font: helvetica, verdana, arial, sans-serif;
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
font: helvetica, verdana, arial, sans-serif;
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
tr.box_titre { 
background: #7699A9;
background-image : url(<?php echo $dolibarr_main_url_root.'/theme/eldy/img/liste_titre.png' ?>);
color: #334444;
font: 12px arial, helvetica, verdana, sans-serif;
font-weight: bold;
border-bottom: 1px solid #FDFFFF;
white-space: nowrap;
}

tr.box_impair { 
background: #e6ebed;
font: 12px arial, helvetica, verdana, sans-serif;
}

tr.box_pair { 
background: #d0d4d7;
font: 12px arial, helvetica, verdana, sans-serif;
}

tr.fiche { 
font: helvetica, verdana, arial, sans-serif;
}




/*
 *   Ok, Warning, Error
 */
.ok      { color: #114466; }
.warning { color: #887711; }
.error   { color: #550000; font-weight: bold; }

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
	font: helvetica, verdana, arial, sans-serif;
	font-weight: bold; 
	color: #336666; 
	text-decoration: none }


/*
 * Formulaire confirmation
 */

td.validtitle { 
           font-weight: bold;
           background: #EECC55;
           }
td.valid { 
           background: #EECC55;
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
border-width:0px;
color:#0B63A2;
vertical-align:middle;
cursor: pointer; 
cursor: hand;
}



/*
 *  Autre
 */


#corpForm fieldset {	/*** Mise en forme des cadres ***/
	margin: 0;
	font-style: normal;
	padding: 0 1em 1em;
	font-size: 12px;
}

#corpForm .focus {	/*** Mise en avant des champs en cours d'utilisation ***/
	background: beige;
	color: black;
}
#corpForm .normal {	/*** Retour à l'état normal après l'utilisation ***/
	background: white;
	color: black;
}
