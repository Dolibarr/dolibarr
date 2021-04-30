<?php
/* Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/eldy/style.css.php
 *		\brief      File for CSS style sheet Eldy
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled because need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled because need to do translations
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
//if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  // We need top menu content
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}


define('ISLOADEDBYSTEELSHEET', '1');


session_cache_limiter('public');

require_once __DIR__.'/../../main.inc.php'; // __DIR__ allow this script to be included in custom themes
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}


include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';
if (defined('THEME_ONLY_CONSTANT')) {
	return;
}

?>

html,body {
	box-sizing: border-box;
	padding:0px;
	margin:0;
	height:100%;
	width:100%;
}

.bodytakepos {
	background-color: #EEE;
}

.center {
	text-align: center;
}

button.calcbutton.poscolorblue {
	background-color: #0066AA;
}

button.calcbutton2.poscolordelete {
	background: rgb(255, 188, 185);
	color: #633;
	/*background-color: #884444;
	color: #fff;*/
}

button.calcbutton {
	display: inline-block;
	position: relative;
	padding: 0;
	line-height: normal;
	cursor: pointer;
	vertical-align: middle;
	text-align: center;
	overflow: visible; /* removes extra width in IE */
	width: calc(25% - 2px);
	height: calc(25% - 2px);
	font-weight: bold;
	background-color: #8c907e;
	color: #fff;
	/* border-color: unset; */
	border-width: 0;
	margin: 1px;
	font-size: 14pt;
	border-radius: 3px;
}

button.calcbutton2 {
	color: #fff;
	background-color: #5555AA;
	border-width: 0px;
	display: inline-block;
	position: relative;
	padding: 0;
	line-height: normal;
	cursor: pointer;
	vertical-align: middle;
	text-align: center;
	overflow: visible; /* removes extra width in IE */
	width: calc(25% - 2px);
	height: calc(25% - 2px);
	font-weight: bold;
	font-size: 10pt;
	margin: 1px;
	border-radius: 3px;
}

button.calcbutton2 .iconwithlabel {
	padding-bottom: 10px;
}

button.calcbutton3 {
	display: inline-block;
	position: relative;
	padding: 0;
	line-height: normal;
	cursor: pointer;
	vertical-align: middle;
	text-align: center;
	overflow: visible; /* removes extra width in IE */
	width: calc(25% - 2px);
	height: calc(25% - 2px);
	font-size: 14pt;
	margin: 1px;
	border-radius: 3px;
}

button.productbutton {
	display: inline-block;
	position: relative;
	padding: 0;
	line-height: normal;
	cursor: pointer;
	vertical-align: middle;
	text-align: center;
	overflow: visible; /* removes extra width in IE */
	width: calc(100% - 2px);
	height: calc(100% - 2px);
	font-weight: bold;
	background-color: #a3a6a3;
	color: #fff;
	/* border-color: unset; */
	border-width: 0;
	margin: 1px;
	font-size: 14pt;
	border-radius: 3px;
}

button.actionbutton {
	background: #EABCA6;
	border: 2px solid #EEE;
	min-height: 40px;
	border-radius: 3px;
}

button.actionbutton {
	display: inline-block;
	position: relative;
	padding: 0;
	line-height: normal;
	cursor: pointer;
	vertical-align: middle;
	text-align: center;
	overflow: visible; /* removes extra width in IE */
	width: calc(33.33% - 2px);
	height: calc(25% - 2px);
	margin: 1px;
	   border-width: 0;
}

button.item_value {
	background: #bbbbbb;
	border: #000000 1px solid;
	border-radius: 4px;
	padding: 8px;
}

button.item_value.selected {
	background: #ffffff;
	color: #000000;
	font-weight: bold;
}

div[aria-describedby="dialog-info"] button:before {
	content: "\f788";
	font-family: "Font Awesome 5 Free";
	font-weight: 900;
	padding-right: 5px;
}
div[aria-describedby="dialog-info"].ui-dialog .ui-dialog-buttonpane {
	border-width: 0;
}

.takepospay {
	font-size: 1.5em;
}

.fa.fa-trash:before {
	font-size: 1.5em;
}


div.wrapper{
	float:left; /* important */
	position:relative; /* important(so we can absolutely position the description div */
	width:25%;
	height:33%;
	margin:0;
	padding:1px;
	border: 2px solid #EEE;
	/*box-shadow: 3px 3px 3px #bbb; */
	text-align: center;
	box-sizing: border-box;
	background-color:#fff;
}

div.wrapper2{
	float:left; /* important */
	position:relative; /* important(so we can absolutely position the description div */
	width:12.5%;
	height:33%;
	margin:0;
	/* padding:1px; */
	border: 2px solid #EEE;
	/*box-shadow: 3px 3px 3px #bbb;*/
	text-align: center;
	box-sizing: border-box;
	background-color:#fff;
}

img.imgwrapper {
	max-width: 100%;
}

button:active{
	background:black;
	color: white;
}

div.description{
	position:absolute; /* absolute position (so we can position it where we want)*/
	bottom:0px; /* position will be on bottom */
	left:0px;
	width:100%;
	/* styling below */
	background-color:black;
	/*color:white;*/
	opacity:1; /* transparency */
	/*filter:alpha(opacity=80); IE transparency */
	text-align:center;

	padding-top: 30px;
	background: -webkit-linear-gradient(top, rgba(250,250,250,0), rgba(250,250,250,0.5), rgba(250,250,250,0.95), rgba(250,250,250,1));
}

div.catwatermark{
	position:absolute;
	top:3%;
	left:3%;
	width:20%;
	background-color:black;
	color:white;
	text-align:center;
	font-size: 20px;
	display: none;
	opacity: 0.25;
}

table.postablelines tr td {
	line-height: unset;
	padding-top: 3px;
	padding-bottom: 3px;
}

.posinvoiceline td {
	height: 40px !important;
	background-color: var(--colorbacklineimpair1);
}

.postablelines td.linecolht {
	line-height: 1.3em !important;
}

div.paymentbordline
{
	width:calc(50% - 16px);
	background-color:#888;
	border-radius: 8px;
	margin-bottom: 4px;
	display: inline-block;
	padding: 5px;
}

@media only screen and (max-aspect-ratio: 6/4) {
	div.description{
	min-height:20%;
	}
}

.container{
	width: 100%;
	height: 100%;
	margin: 0 auto;
	overflow: visible;
	box-sizing: border-box;
}

.row1{
	margin: 0 auto;
	width: 100%;
	height: 34%;
}

.row1withhead{
	margin: 0 auto;
	width: 100%;
	height: calc(45% - 50px);
	padding-top: 5px;
}

.row2{
	margin: 0 auto;
	width: 100%;
	height: 66%;
}

.row2withhead{
	margin: 0 auto;
	width: 100%;
	height: 55%;
}

.div1{
	height:100%;
	width: 34%;
	float: left;
	text-align: center;
	box-sizing: border-box;
	overflow: auto;
	/* background-color:white; */
	padding-top: 1px;
	padding-bottom: 0;
	padding-right: 5px;
	padding-left: 5px;
	min-height: 180px;
}

.div2{
	height: 100%;
	width: 33%;
	font-size: 0;
	float: left;
	box-sizing: border-box;
	padding-top: 0;
	padding-bottom: 0;
	padding-right: 5px;
	padding-left: 5px;
	min-height: 180px;
}

.div3{
	height: 100%;
	width: 33%;
	float: left;
	box-sizing: border-box;
	padding-top: 0;
	padding-bottom: 0;
	padding-right: 5px;
	padding-left: 5px;
}

.div4{
	height: 100%;
	width: 34%;
	float: left;
	box-sizing: border-box;
	font-size: 6px;
	padding-top: 10px;
	padding-bottom: 10px;
	padding-right: 5px;
	padding-left: 5px;
}

.div5{
	height: 100%;
	width: 66%;
	float: left;
	box-sizing: border-box;
	font-size: 6px;
	padding-top:10px;
	padding-bottom:10px;
	padding-right: 5px;
	padding-left: 5px;
}

tr.selected, tr.selected td {
	/* font-weight: bold; */
	background-color: rgb(240,230,210) !important;
}
.order {
	color: limegreen;
}

.colorwhite {
	color: white;
}
.colorred {
	color: red;
}
.colorgreen {
	color: green;
}
.poscolordelete {
	color: #844;
}
.poscolorgreen {
	color: #060;
}
.poscolorblue {
	color: #006;
}

.centerinmiddle {
	transform: translate(0,-50%);
	position: relative;
	top: 50%;
}
.trunc {
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden;
}

p.description_content{
	padding:10px;
	margin:0px;
}
div.description_content {
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: <?php echo $conf->global->TAKEPOS_LINES_TO_SHOW; ?>;
	overflow: hidden;
	padding-left: 2px;
	padding-right: 2px;
}

.header{
	margin: 0 auto;
	width: 100%;
	height: 52px;
	background: rgb(60,70,100);
}

.topnav-left {
	float: left;
}
.topnav-right {

}

.topnav div.login_block_other, .topnav div.login_block_user {
	max-width: unset;
	width: unset;
}
.topnav{
	background: var(--colorbackhmenu1);
	overflow: hidden;
	height: 100%;
}
.topnav .tmenu {
	display: block;
}

.topnav a{
	float: left;
	color: #f2f2f2;
	text-decoration: none;
}
.topnav .login_block_other a {
	padding: 5px 10px;
	margin-left: 4px;
	font-size: 1.3em;
}

@media screen and (max-width: 767px) {
	.topnav .login_block_other a {
		padding: 5px 5px;
		font-size: 1.2em;
	}
}

.topnav-right > a {
	font-size: 17px;
}

.topnav-left a {
	padding: 7px 4px 7px 4px;
	margin: 8px;
	margin-left: 4px;
}
.topnav-left a:hover, .topnav .login_block_other a:hover {
	background-color: #ddd;
	color: black;
}

.topnav-right{
	float: right;
}

.topnav input[type="text"] {
	background-color: #fff;
	color: #000;
	float: left;
	border-bottom: none !important;
	margin-left: 6px;
	font-size: 1.3em;
	max-width: 250px;
	border-radius: 5px;
}

div#moreinfo, div#infowarehouse {
	color: #aaa;
	padding: 0 8px 0 8px;
}

.productprice {
	position: absolute;
	top: 5px;
	right: 5px;
	background: var(--colorbackhmenu1);
	color: var(--colortextbackhmenu);
	font-size: 2em;
	padding: 5px;
	border-radius: 2px;
	opacity: 0.9;
	padding-left: 8px;
	padding-right: 8px;
}


@media screen and (min-width: 892px) {
	.actionbutton{
		font-size: 13px;
	}
	div.description{
		font-size: 15px;
	}
	.invoice{
		font-size: 14px;
	}
}

@media (max-width: 891px) and (min-width: 386px) {
	.actionbutton{
		font-size: 12px;
	}
	div.description{
		font-size: 13px;
	}
	.invoice{
		font-size: 12px;
	}
}

@media screen and (max-width: 385px){
	.actionbutton{
		font-size: 10px;
	}
	div.description{
		font-size: 11px;
	}
	.invoice{
		font-size: 10px;
	}
}

/* For small screens */

@media screen and (max-width: 1024px) {
	.topnav input[type="text"] {
		max-width: 150px;
	}
}

@media screen and (max-width: 767px) {
	.header {
		position: sticky;
		top: 0;
		z-index: 10;
	}

	.topnav input[type="text"] {
		max-width: 90px;
	}

	.topnav-right {
		float: unset;
	}
	.header {
		height: unset;
	}
	div.container {
		overflow-x: scroll;
	}
	div.wrapper {
		width: 50%;
	}
	div.wrapper2 {
		width: 25%;
	}

	.row1withhead{
		height: calc(45% - 100px);
	}

	div#moreinfo, div#infowarehouse {
		padding: 0 5px 0 5px;
	}

	div.div1 {
		padding-bottom: 0;
		margin-bottom: 10px;
	}
	div.div1, div.div2, div.div3 {
		width: 100%;
	}

	div.row1 {
		height: unset;
	}

	div.div2 {
		min-height: unset;
	}

	div.div3 {
		margin-top: 8px;
		height: unset;
	}

	button.calcbutton, button.calcbutton2 {
		min-height: 30px;
	}

	.takepospay {
		font-size: 1.2em;
	}

	button.actionbutton {
		min-height: 60px;
		padding-left: 4px;
		padding-right: 4px;
	}
}

/* Modal box */
.modal {
  display: none; /* Hidden by default */
  position: fixed;
  z-index: 20;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgb(0,0,0);
  background-color: rgba(0,0,0,0.4);
}

/* The Close Button */
.close {
  color: #aaa;
  float: right;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: black;
  text-decoration: none;
  cursor: pointer;
}

.modal-header {
  padding: 2px 16px;
  background-color: #2b4161;
  color: white;
}

.modal-body {padding: 2px 16px;}

.modal-content {
  position: relative;
  background-color: #fefefe;
  margin: 15% auto; /* 15% from the top and centered */
  padding: 0;
  border: 1px solid #888;
  width: 40%;
  box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
  animation-name: animatetop;
  animation-duration: 0.4s;
  min-width: 200px;
}

@keyframes animatetop {
  from {top: -300px; opacity: 0}
  to {top: 0; opacity: 1}
}

.block {
  display: block;
  width: 100%;
  border: none;
  color: white;
  background-color: #8c907e;
  padding: 14px 0px;
  font-size: 16px;
  cursor: pointer;
  text-align: center;
  margin: 2px;
}
