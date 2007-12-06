<?php
/* Copyright (C) 2007 Patrick raguin  <patrick.raguin@gmail.com>
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
 */

/**
		\file       htdocs/theme/auguria/auguria.css.php
		\brief      Fichier de style CSS du theme Auguria
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
    background-color: #FFFFFF;
	color: #101010;
	font-size: 12px;
    font-family: arial,sans-serif,verdana, helvetica;
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

    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
input.flat
{

    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea  {

    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
textarea.flat
{

    background: #FDFDFD;
    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
select.flat
{

    border: 1px solid #ACBCBB;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
}
.button
{
	font-size: 100%;
	border: 0px;
	background-image : url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/button_bg.png' ?>);
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

div.vmenuplusfiche
{
    top: auto;
    left: auto;

	position: static;
    float: left;

    display: block;
    margin-right: 10px;
    margin-left: 1px;
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
	float: right;
<?php
	//print "_SERVER['HTTP_USER_AGENT']=".$_SERVER['HTTP_USER_AGENT'];
	if (! eregi('MSIE 7\.0',$_SERVER['HTTP_USER_AGENT'])) print "    position: absolute;\n";
?>
    display: block;
    margin-top: 8px;
    margin-left: 180px;
	margin-right: 4px;
    width:auto;
}

* html div.fiche
{
	width:800px;	
}


/* ============================================================================== */
/* Menu superieur et 1ere ligne tableau                                           */
/* ============================================================================== */

div.tmenu
{
    position: relative;
    white-space: nowrap;
    border-left: 0px;
    padding: 0px 0px 0px 0px;
    margin: 0px 0px 2px 0px;
    background-image : url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/nav.jpg' ?>) ;
    height: 22px;
}
div.tmenu  .tmenudisabled
{
	color: #757575;
	font-size: 13px;
	padding-left:10px;
	padding-right:10px;
	padding-top:3px;
}

table.tmenu
{
    padding: 0px 0px 0px 0px;
    margin: -2px 0px 0px 0px;
}

a.tmenu:link
{
  	color:#FFFFFF;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 21px;
	display: block;
	font-weight: normal;
}

a.tmenu:visited
{
  	color:#FFFFFF;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 21px;
	display: block;
	font-weight: normal;
}


a.tmenu#sel
{
	 color:#234046;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 21px;
	display: block;
	font-weight: normal;
	background: #FFFFFF;
}
a.tmenu:hover
{
	 color:#234046;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 21px;
	display: block;
	font-weight: normal;
	background: #FFFFFF;
}

font.tmenudisabled
{
  color: #93a5aa;
  padding: 0px 5px 0px 5px;
  margin: 0px 0px 2px 0px;
  font-weight:bold;
  font-size:12px;
}

a.tmenu:active
{
	background:#4F9EC9;
}



/* Pour menu TOP auguria uniquement */
div.tmenu ul {
	padding: 0px 0px 0px 0px;
    margin: 0px 0px 0px 0px;
	list-style: none;
}
div.tmenu li {
	float: left;
	border-right: solid 1px #4F9EC9;
	height: 22px;
	position:relative;
	display: block;
	margin:0;
	padding:0;
}
div.tmenu li a{
  	font-size: 13px;
	color:#FFFFFF;
	text-decoration:none;
	padding-left:10px;
	padding-right:10px;
	padding-top: 2px;
	height: 22px;
	display: block;
	font-weight: normal;
}
* html div.tmenu li a{
	width:40px;
}
div.tmenu li a#sel
{
	background:#FFFFFF;
	color:#4F9EC9;
	font-weight: normal;
}
div.tmenu li a:visited
{
	color:#FFFFFF;
	font-weight: normal;
}
div.tmenu li a:hover
{
	background:#FFFFFF;
	color:#4F9EC9;
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
/* Barre de gauche                                                                */
/* ============================================================================== */

a.vmenu:link        { font-size:11px; text-align:left; font-weight: bold; color:#000000}
a.vmenu:visited     { font-size:11px; text-align:left; font-weight: bold; color:#000000}
a.vmenu:active      { font-size:11px; text-align:left; font-weight: bold; color:#000000}
a.vmenu:hover       { font-size:11px; text-align:left; font-weight: bold; color:#000000}
font.vmenudisabled  { font-size:11px; text-align:left; font-weight: bold; color: #757575; }

a.vsmenu:link       { font-size:11px; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:visited    { font-size:11px; text-align:left; font-weight: normal; color: #202020; margin: 1em 1em 1em 1em; }
a.vsmenu:active     { font-size:11px; text-align:left; font-weight: normal; color: RGB(94,148,181); margin: 1em 1em 1em 1em; }
a.vsmenu:hover      { font-size:11px; text-align:left; font-weight: normal; color: RGB(94,148,181); margin: 1em 1em 1em 1em; }
font.vsmenudisabled { font-size:11px; text-align:left; font-weight: normal; color: #757575; margin: 1em 1em 1em 1em; }

a.help:link         { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #68ACCF; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:visited      { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #68ACCF; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:active       { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #6198BA; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }
a.help:hover        { font-size: 10px; font-weight: bold; background: #FFFFFF; border: 1px solid #8CACBB; color: #6198BA; padding: 0em 0.7em; margin: 0em 0.5em; text-decoration: none; white-space: nowrap; }


/* Pour menu gauche Auguria */

.menu_titre	a		{font-size:11px; text-align:left; font-weight: bold; color:#FFFFFF}
font.menu_titre_disabled  { font-size:11px; text-align:left; font-weight: bold; color: #757575; margin: 1em 1em 1em 1em; }

a.menu_titre:link       { font-size:11px; text-align:left; font-weight: bold; color: #FFFFFF; margin: 1em 1em 1em 1em; }
a.menu_titre:visited    { font-size:11px; text-align:left; font-weight: bold; color: #FFFFFF; margin: 1em 1em 1em 1em; }
a.menu_titre:active     { font-size:11px; text-align:left; font-weight: bold; color: #FFFFFF; margin: 1em 1em 1em 1em; }
a.menu_titre:hover      { font-size:11px; text-align:left; font-weight: bold; color: #FFFFFF; margin: 1em 1em 1em 1em; }

div.menu
{
	margin: 0px;
	border-spacing: 0px;
	padding: 0px;
	width: 166px;
    border : 0px solid #68ACCF;	
}


div.menu_titre {
	background: url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/bg-titre-rubrique.png' ?>);
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
	background: url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/bg-rubrique.png' ?>);
	margin: 0px;
	padding: 1px;

	padding-right: 8px;
    font-size : 11px;
    font-weight:normal;
    color : #000000;
    text-align: left;
}

div.menu_fin {
	background: url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/bg-bas-rubrique.png' ?>);
	margin: 0px;
	padding: 0px;	
	height:6px;
    width:165px;
    background-repeat:no-repeat;
}



div.blockvmenuimpair a.vmenu, div.blockvmenupair a.vmenu
{
	display: block;
	color: #FFFFFF;
	text-align:left;
	text-decoration: none;
	padding: 4px;
	margin: 0px;
	margin-bottom: -10px;
	background: #FFFFFF;
    background-image: url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/tmenu.jpg' ?>);
    background-position:top;
    background-repeat:repeat-x;
}

div.blockvmenuimpair form a.vmenu, div.blockvmenupair form a.vmenu
{
	display: block;
	color: #000000;
	text-align:left;
	text-decoration: none;
	padding: 4px;
	margin: 0px;
	background: #FFFFFF;
	    margin-bottom: -12px;
}

div.blockvmenuimpair form .button, div.blockvmenupair form .button
{
	margin: 0px;
	background: #6aabd1;
	color: #FFFFFF;
}

div.help
{
    margin-left:5px;
    margin-top:15px;
    text-align: center;
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
    width:auto;
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
    background: #FFFFFF;
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
    border-bottom: #dee7ec 1px solid;
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


/* ============================================================================== */
/* Boutons actions                                                                */
/* ============================================================================== */

/* Nouvelle syntaxe à utiliser */

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

a.butActionRefuse    {
                      font-weight: bold;
                      background: white;
                      border: 1px solid red;
                      color: #436976;
                      padding: 0em 0.7em;
                      margin: 0em 0.5em;
                      text-decoration: none;
                      white-space: nowrap; }

a.butActionRefuse:hover {
                          font-weight: bold;
                          background: #dee7ec; }


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
border: 0px;
border-collapse: collapse;
}
table.border td {
padding: 2px 2px;
padding-left: 4px;
border: 1px solid #9CACBB;
border-collapse: collapse;
}

table.list {
border: 0px solid #9CACBB;
border-collapse: collapse;
}
table.list td {
padding: 2px 2px;
padding-left: 4px;
padding-top:0px;
padding-bottom:0px;
border: 0px solid;
border-collapse: collapse;
}


table.noborder {
border: 6px;
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
color: #FFFFFF;
font-size: 12px;
font-family: arial, helvetica, verdana, sans-serif;
font-weight: normal;
border-bottom: 1px solid #FDFFFF;
white-space: normal;
  -moz-border-radius-topleft:6px;
  -moz-border-radius-topright:6px;
}
input.liste_titre {
background: #7699A9;
background-image : url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/liste_titre.png' ?>);
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
	font-weight: bold;
	color: #57A2CA;
	text-decoration: none }


/* ============================================================================== */
/* Formulaire confirmation                                                        */
/* ============================================================================== */

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
/* Tooltips                                                                       */
/* ============================================================================== */

#dhtmltooltip
{
position: absolute;
width: 420px;
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
#corpForm .normal {	/*** Retour à l'état normal après l'utilisation ***/
	background: white;
	color: black;
}

/* ============================================================================== */
/*  Ajax - Liste déroulante de l'autocompletion                                   */
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
	background-image : url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/button_bg.png' ?>);
	background-position : bottom;
	cursor:pointer;
}

form.inplaceeditor-form a { /* The cancel link */
  margin-left: 5px;
  font-size: 11px;
	font-weight:normal;
	border: 0px;
	background-image : url(<?php echo $dolibarr_main_url_root.'/theme/auguria/img/button_bg.png' ?>);
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
