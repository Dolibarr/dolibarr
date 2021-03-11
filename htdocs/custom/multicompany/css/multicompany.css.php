<?php
/* Copyright (C) 2011-2020	Regis Houssin	<regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *		\file       /multicompany/css/multicompany.css.php
 *		\brief      Fichier de style CSS complementaire du module Multi-Company
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');
if (! defined('NOREQUIREHOOK'))  define('NOREQUIREHOOK','1');  // Disable "main.inc.php" hooks

define('ISLOADEDBYSTEELSHEET', '1');

session_cache_limiter(FALSE);

$res=@include '../../main.inc.php';					// For "root" directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include '../../../main.inc.php';	// For "custom" directory


// Define css type
header('Content-type: text/css');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

$dol_optimize_smallscreen=$conf->dol_optimize_smallscreen;

?>

.minwidth180 { min-width: 175px !important; }
div.login_block_other { max-width: unset; }

#login_right #entity {
	min-width: 190px !important;
}
#login_right .multicompany-trinputlogin .select2-container {
	padding-left: inherit;
}
#login_right .nowrap {
	white-space: nowrap!important;
}
#login_right .multicompany-trinputlogin {
	padding-top: 10px
}
.icon-multicompany-blue {
	background-image: url(<?php echo dol_buildpath('/multicompany/img/object_multicompany.png',1); ?>);
	background-repeat: no-repeat;
	background-position: left center;
}
.icon-multicompany-white {
	background-image: url(<?php echo dol_buildpath('/multicompany/img/object_multicompany_eldy.png',1); ?>);
	background-repeat: no-repeat;
	background-position: left center;
}
.icon-multicompany-black {
	background-image: url(<?php echo dol_buildpath('/multicompany/img/object_multicompany_md.png',1); ?>);
	background-repeat: no-repeat;
	background-position: left center;
}
.multicompany_select {
	font-family: roboto,arial,tahoma,verdana,helvetica !important;
}
#multicompany_title .opacityhigh {
	opacity: 0.8 !important;
}
.multicompany_block .fa-globe,
.modify-entity {
	cursor:pointer;
}
.modify-entity-disabled {
	cursor:not-allowed!important;
}
div.entity_box {
	margin-top: 10px;
}
div#login_right {
	min-width:0!important;
}
#changeentity, #entitythirdparty, #entitycontact, #referring_entity {
	width: 200px;
}
#select2-changeentity-container {
	color: #444!important;
}
#entity_box #entity, #search_entity {
	width: 135px;
}
#entity_box .select2-container {
	margin-left: 8px!important;
}
.padding-left5 {
	padding-left: 5px;
}
.padding-top3 {
	padding-top: 3px;
}
.padding-left15 {
	padding-left: 15px!important;
}
.fifty-percent {
	width: 50%;
}
.seventy-percent {
	width: 70%;
}
.float-right {
	float: right;
}
.float-left {
	float: left;
}
.valign-middle {
	vertical-align: middle;
}
.text-align-right {
	text-align: right;
}
.text-align-center {
	text-align: center;
}
.text-align-left {
	text-align: left;
}
.button-align-right {
	text-align: right;
	padding-right: 30px;
}
.button-not-allowed {
	cursor: not-allowed !important;
	opacity: 0.6;
}
div.mc-upgrade-alert {
  color: #302020;
  padding: 0.5em 0.5em 0.5em 0.5em;
  margin: 0.5em 1.5em 0.5em 1.5em;
  border: 1px solid #e0d0b0;
  -moz-border-radius: 4px;
  -webkit-border-radius: 4px;
  border-radius: 4px;
  background: #EFCFCF;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);
}
.dataTables_length select {
	font-size: unset;
}
table.dataTable thead tr {
    background-color: rgb(220,220,223)!important;
}
table.dataTable tbody tr.odd {
    background-color: #f8f8f8!important;
    border-bottom: 1px solid #ddd;
}
table.dataTable tbody tr.even {
    background-color: #ffffff!important;
    border-bottom: 1px solid #ddd;
}
table.dataTable tbody tr:hover {
    background-color: #edf4fb!important;
}
.multicompany-entity-container {
	background: #e1e7e1;
	padding: 2px;
	margin-right: 10px;
	margin-bottom: 5px;
	border-radius: 10px;
	display: inline-block;
}
.multicompany-entity-card-container {
	background: #e1e7e1;
	padding-left: 4px!important;
	padding-right: 4px!important;
	margin-top: 5px !important;
	border-radius: 10px;
	display: inline-block;
}
.multicompany-entity-container:hover,
.multicompany-entity-card-container:hover {
	background: #d5dbd5;
}
.multicompany-button-disabled,
.multicompany-switch-disabled,
.multicompany-switch-on-not-allowed,
.multicompany-switch-off-not-allowed {
	margin: 5px;
	cursor: not-allowed;
	opacity: 0.5;
}
.multicompany-switch-on,
.multicompany-switch-off,
.multicompany-button-active-on,
.multicompany-button-active-off,
.multicompany-button-visible-on,
.multicompany-button-visible-off {
	margin: 5px;
	cursor: pointer;
}
.multicompany-button-template,
.multicompany-button-lock-on {
	margin: 5px;
}
.multicompany-button-template {
	color: rgb(59, 91, 152);
}
.multicompany-button-setup {
	margin: 7px;
	cursor: pointer;
}
.multicompany-button-clonerights {
	cursor: pointer;
}
.multicompany-button-delete {
	margin: 5px;
	cursor: pointer;
}
.multicompany-flag-language,
.multicompany-flag-country {
	margin-top: 5px;
}
#dialog-duplicate {
	overflow-x: hidden;
}
.multiselect-title {
	padding-left: 5px;
}
.multiselect-available-title, .multiselect-selected-title {
	padding-bottom: 5px;
	color: #73808c;
}
.multiselect-available-title-text, .multiselect-selected-title-text {
	padding-left: 5px;
}
.multiselect-menu {
	/*padding-top: 23px;*/
	padding-top: 20px;
}
.multiselect-menu-btn-color {
	background-color: #e2e2e2!important;
}
.multiselect-menu-btn-color:hover {
	background-color: #f2f2f2!important;
}
.multiselect-select {
	overflow-y:auto;
	padding: 6px 6px!important;
}
.multiselect-option {
	background-color: #f2f2f2!important;
	cursor: pointer;
	border: 5px;
}
tr.multiselect-separator td {
	padding: 1px!important;
	line-height: 1px!important;
	height: 10px!important;
}

[data-tooltip]:before {
	content: attr(data-tooltip);
}
[data-tooltip] {
	display: inline-block;
	position: relative;
	cursor: pointer;
	/*padding: 4px;*/
}
/* Tooltip styling */
[data-tooltip]:before {
	content: attr(data-tooltip);
	display: none;
	position: absolute;
	background: #fff;
	color: #444;
	border-top: solid 1px #BBBBBB;
	border-left: solid 1px #BBBBBB;
	border-right: solid 1px #BBBBBB;
	border-bottom: solid 1px #BBBBBB;
	padding: 4px 10px;
	border-radius: 0;
	box-shadow: 0 0 4px grey;
	margin: 2px;
	font-stretch: condensed;
	min-width: 320px;
}
/* Dynamic horizontal centering */
[data-tooltip-position="top"]:before,
[data-tooltip-position="bottom"]:before {
	left: 50%;
	-ms-transform: translateX(-50%);
	-moz-transform: translateX(-50%);
	-webkit-transform: translateX(-50%);
	transform: translateX(-50%);
}
/* Dynamic vertical centering */
[data-tooltip-position="right"]:before,
[data-tooltip-position="left"]:before {
	top: 50%;
	-ms-transform: translateY(-50%);
	-moz-transform: translateY(-50%);
	-webkit-transform: translateY(-50%);
	transform: translateY(-50%);
}
[data-tooltip-position="top"]:before {
	bottom: 100%;
	margin-bottom: 6px;
}
[data-tooltip-position="right"]:before {
	left: 100%;
	margin-left: 6px;
}
[data-tooltip-position="bottom"]:before {
	top: 100%;
	margin-top: 6px;
}
[data-tooltip-position="left"]:before {
	right: 100%;
	margin-right: 6px;
}
/* Show the tooltip when hovering */
[data-tooltip]:hover:before,
[data-tooltip]:hover:after {
	display: block;
	z-index: 3000;
}

<?php
if (($conf->global->MAIN_THEME === 'eldy' && empty($conf->global->MULTICOMPANY_DROPDOWN_MENU_DISABLED) && ! GETPOSTISSET('theme')) || (GETPOSTISSET('theme') && GETPOST('theme', 'aZ', 1) === 'eldy')) {
	include dol_buildpath('/multicompany/css/dropdown.inc.php');
}
