<?php
/* Copyright (C) 2024	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
 *		\file       htdocs/public/webportal/css/style.css.php
 *		\brief      File for CSS style sheet of Web portal
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
	define('NOLOGIN', 1); // File must be accessed by logon page so without login.
}
//if (!defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);  	// We load menu manager class (note that object loaded may have wrong content because NOLOGIN is set and some values depends on login)
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

session_cache_limiter('public');

require_once __DIR__.'/../webportal.main.inc.php'; // __DIR__ allow this script to be included in custom themes
/**
 * @var Conf $conf
 * @var Translate $langs
 *
 * @var	string	$dolibarr_nocache
 */

$fontlist = 'arial,tahoma,verdana,helvetica';
$colorbacktitle1 = '#fff';
$langs->load("main", 0, 1);
$right = ($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right');
$left = ($langs->trans("DIRECTION") == 'rtl' ? 'right' : 'left');

// Define css type
top_httphead('text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) {
	header('Cache-Control: max-age=10800, public, must-revalidate');
} else {
	header('Cache-Control: no-cache');
}
?>
@charset "UTF-8";

@import "pico.css.php";
@import "mixin.css";
@import "login.css";

/**
This file can overwrite default pico css
 */


/**
 * Navs
 */

body > nav {
  --nav-link-spacing-vertical: 1rem;
  -webkit-backdrop-filter: saturate(180%) blur(10px);
  z-index: 99;
  position: fixed;
  top: 0;
  right: 0;
  left: 0;
  backdrop-filter: blur(60px) ;
  background-color: var(--nav-background-color);
  box-shadow: 0px 1px 0 var(--nav-border-color);
}

nav.primary-top-nav ul:first-of-type {
	clear: both;
	min-width: 100px;
}

.primary-top-nav{
  --border-radius: 0;
}

ul.brand {
	max-width:	80px;
}
nav.primary-top-nav ul:first-of-type {
	margin-left: unset !important;
}

ul.menu-entries li {
	display: block;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
ul.menu-entries-alt {
	display: none;
}

.maxwidthdate {
	max-width: 110px;
}

@media (max-width: 576px) {
	ul.brand li.brand {
		padding-left: 0px;
	}
	ul.menu-entries li {
		display: none;
	}
	ul.menu-entries-alt {
		display: block;
	}
}




/**
  NAV BRAND LOGO
 */
.brand__logo-link{
  max-height: 100%;
  margin: 0;
  padding: 0;
}

.brand__logo-link:focus{
  background: none;
}

.spacer{
  --spacer-margin: calc(var(--font-size) * 2);
  margin-top: var(--spacer-margin);
  margin-bottom: var(--spacer-margin);
}

html{
  scroll-padding-top:100px;
}

#main-container{
  padding-top: 200px;
}

/*
a:link, a:visited, a:hover, a:active, .classlink {
	color: unset;
}
*/

.pages-nav-list__icon::after {
  display: block;
  width: 1rem;
  height: 1rem;
  -webkit-margin-start: calc(var(--spacing, 1rem) * 0.5);
  margin-inline-start: calc(var(--spacing, 1rem) * 0.5);
  float: right;
  background-image: var(--icon-chevron);
  background-position: right center;
  background-size: 1rem auto;
  background-repeat: no-repeat;
  content: "";
  transition: transform var(--transition);
}

.pages-nav-list__icon.--prev::after {
  transform: rotate(90deg);
}
.pages-nav-list__icon.--next::after {
   transform: rotate(-90deg);
}

.pages-nav-list__link.--active{
  outline: 1px solid hsla(var(--primary-color-hue), var(--primary-color-saturation), var(--primary-color-lightness), 0.3);
}

.hero-header{
  background-color: #f2f2f2;
  background-image: var(--banner-background);
  padding: 100px 0 64px 0;
  margin: 0;

  background-position: center center;
  background-size: cover;
  background-repeat: no-repeat;
}

/**
  Search list
 */
[role="search-row"] :is(button, input[type=submit], input[type=button], [role=button]) {
  --background-color: #ededed;
  --border-color: #ededed;
  --color: #666;
}

.btn-filter-icon{
  --icon-url : var(--icon-time);
  --icon-size : 16px;

  display: inline-block;
  width: auto;
}

.btn-filter-icon::before{
  content: " ";
  display: inline-block;
  height: var(--icon-size);
  width: var(--icon-size);
  background-color: transparent;
  background-image: var(--icon-url);
  background-repeat: no-repeat;
  background-size: var(--icon-size) var(--icon-size);
  background-position: center;
}

.btn-filter-icon.btn-remove-search-filters-icon::before {
  --icon-url : var(--icon-close);
}

.btn-filter-icon.btn-search-filters-icon::before {
  --icon-url : var(--icon-search);
}

[role="search-row"] [data-col="row-checkbox"]{
  white-space: nowrap;
}

/**
Home Styles
 */
.home-links-card{

}



:root {
	--colorbackbody: #fff;
	--colortext: #000;
}


.width150 { width: 150px; }


/* ============================================================================== */
/* Calendar date picker                                                           */
/* ============================================================================== */

.ui-datepicker-calendar .ui-state-default, .ui-datepicker-calendar .ui-widget-content .ui-state-default,
.ui-datepicker-calendar .ui-widget-header .ui-state-default, .ui-datepicker-calendar .ui-button,
html .ui-datepicker-calendar .ui-button.ui-state-disabled:hover, html .ui-button.ui-state-disabled:active
{
	border: unset;
}

div#ui-datepicker-div {
	width: 300px;
	box-shadow: 2px 5px 15px #aaa;
	border: unset;
	padding-left: 5px;
	padding-right: 5px;
	padding-top: 5px;
	z-index: 1010 !important;	/* must be over menu bar */
}
.ui-datepicker .ui-datepicker table {
	font-size: unset;
}
.ui-datepicker .ui-widget-header {
	border: unset;
	background: unset;
}

/* the button Previous / Next month */
.ui-datepicker .ui-datepicker-prev, .ui-datepicker .ui-datepicker-next {
	width: 2.5em;
	height: 2.7em;
}


img.datecallink { padding-left: 2px !important; padding-right: 2px !important; }

select.ui-datepicker-year {
	margin-left: 2px !important;
}
.ui-datepicker-trigger {
	vertical-align: middle;
	cursor: pointer;
	padding-left: 2px;
	padding-right: 2px;
}

/*
.bodyline {
	border-radius: 8px;
	border: 1px #E4ECEC outset;
	padding: 0px;
	margin-bottom: 5px;
}
*/
table.dp {
	width: 180px;
	background-color: var(--inputbackgroundcolor);
	border-top: solid 2px #DDDDDD;
	border-<?php print $left; ?>: solid 2px #DDDDDD;
	border-<?php print $right; ?>: solid 1px #222222;
	border-bottom: solid 1px #222222;
	padding: 0px;
	border-spacing: 0px;
	border-collapse: collapse;
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
	font-size: 0.85em;
	border-width:0px;
	color:#0B63A2;
	vertical-align:middle;
	cursor: pointer;
}
.datenowlink {
	color: var(--colortextlink);
	font-size: 0.8em;
	opacity: 0.7;
}



/* ============================================================================== */
/*  UI features                                                                   */
/* ============================================================================== */

.ui-widget-content {
	border: solid 1px rgba(0,0,0,.3);
	background: var(--colorbackbody) !important;
	color: var(--colortext) !important;
}

/* Style to overwrites UI JQuery styles */
.ui-state-highlight, .ui-widget-content .ui-state-highlight, .ui-widget-header .ui-state-highlight {
	/* border: 1px solid #888; */
	background: rgb(<?php echo $colorbacktitle1; ?>);
	color: unset;
	font-weight: bold;
}
.ui-state-active, .ui-widget-content .ui-state-active, .ui-widget-header .ui-state-active, a.ui-button:active, .ui-button:active, .ui-button.ui-state-active:hover {
	background: #007fff !important;
	color: #ffffff !important;
}

.ui-menu .ui-menu-item a {
	text-decoration:none;
	display:block;
	padding:.2em .4em;
	line-height:1.5;
	font-weight: normal;
	font-family:<?php echo $fontlist; ?>;
	font-size:1em;
}
.ui-widget {
	font-family:<?php echo $fontlist; ?>;
}
.ui-button { margin-left: -2px; <?php print(preg_match('/chrome/', $conf->browser->name) ? 'padding-top: 1px;' : ''); ?> }
.ui-button-icon-only .ui-button-text { height: 8px; }
.ui-button-icon-only .ui-button-text, .ui-button-icons-only .ui-button-text { padding: 2px 0px 6px 0px; }
.ui-button-text
{
	line-height: 1em !important;
}
