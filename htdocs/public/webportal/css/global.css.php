<?php
/* Copyright (C) 2024	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *		\file       htdocs/public/webportal/css/global.css.php
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

require_once __DIR__.'/../../../main.inc.php'; // __DIR__ allow this script to be included in custom themes


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
@import "./themes/custom.css.php";

/**
This file car overwrite default pico css
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
