<?php
/* Copyright (C) 2009-2010 Regis Houssin	<regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/theme/phones/smartphone/theme/default/default.css.php
 *		\brief      Fichier de style CSS du theme Smartphone default
 *		\version    $Id: default.css.php,v 1.14 2011/08/03 01:39:44 eldy Exp $
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1'); // We need to use translation files to know direction
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');

session_cache_limiter( FALSE );

require_once("../../../../../master.inc.php");

// Define css type
header('Content-type: text/css');
// Important: Avoid page request by browser and dynamic build at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

?>
.ui-mobile-viewport {
/*width:600px;
height:600px;
min-height: 200px;
min-width: 600px;
overflow:scroll; */
}

.landscape, .landscape .ui-page {
}

#dol-homeheader { height: 40px; font-size: 16px; }

.ui-mobile-viewport {
    margin: 0;
}

.ui-header { height: 40px; font-size: 16px; }

.ui-content {
padding-top: 1px;
padding-right: 0;
padding-bottom: 0px;
padding-left: 1px;
}

.ui-content .ui-listview {
    margin-top: 0px;   /* Use here negative value of ui-content top padding */
    margin-right: 0px;
    margin-bottom: 0px;
    margin-left: 0px;
    /* overflow: scroll; */
}

.ui-mobile #dol-homeheader { padding: 10px 5px 0; text-align: center }
.ui-mobile #dol-homeheader h1 { margin: 0 0 10px; }
.ui-mobile #dol-homeheader p { margin: 0; }

.ui-li-icon {
	left:5px;
	top:0.3em;
}

.ui-li .ui-btn-inner {
    padding: 0.4em 5px 0.4em 5px;
}

input.ui-input-text, textarea.ui-input-text {
	padding: 0.2em;
}

.ui-body-b {
    background: #FFFFFF;
}

.ui-body-c {
    background: #FFFFFF;
    text-shadow: none;
}

.loginform {
    margin-left: 10px;
    margin-right: 10px;
    padding: 5px;
}




div.titre {
	font-family: <?php print $fontlist ?>;
	font-weight: normal;
	color: #336666;
	text-decoration: none;
}


/* ============================================================================== */
/* Tables                                                                         */
/* ============================================================================== */

/*
#undertopmenu {
background-image: url("<?php echo DOL_URL_ROOT.'/theme/eldy/img/gradient.gif' ?>");
background-repeat: repeat-x;
}
*/


.nocellnopadd {
list-style-type:none;
margin: 0px;
padding: 0px;
}

.notopnoleft {
border-collapse: collapse;
border: 0px;
padding-top: 0px;
padding-<?php print $left; ?>: 0px;
padding-<?php print $right; ?>: 4px;
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
margin: 0px 0px 0px 0px;
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

td.border {
border-top: 1px solid #000000;
border-right: 1px solid #000000;
border-bottom: 1px solid #000000;
border-left: 1px solid #000000;
}


/* Main boxes */

table.noborder {
border-collapse: collapse;
border-top-color: #FEFEFE;

border-right-width: 1px;
border-right-color: #BBBBBB;
border-right-style: solid;

border-left-width: 1px;
border-left-color: #BBBBBB;
border-left-style: solid;

border-bottom-width: 1px;
border-bottom-color: #BBBBBB;
border-bottom-style: solid;

margin: 0px 0px 2px 0px;
}

table.noborder tr {
border-top-color: #FEFEFE;

border-right-width: 1px;
border-right-color: #BBBBBB;
border-right-style: solid;

border-left-width: 1px;
border-left-color: #BBBBBB;
border-left-style: solid;
height: 16px;
}

table.noborder td {
padding: 1px 2px 0px 1px;			/* t r b l */
}

table.nobordernopadding {
border-collapse: collapse;
border: 0px;
}
table.nobordernopadding tr {
border: 0px;
padding: 0px 0px;
}
table.nobordernopadding td {
border: 0px;
padding: 0px 0px;
}
