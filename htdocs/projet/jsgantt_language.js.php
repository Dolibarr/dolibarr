<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/projet/jsgantt_language.js.php
 *		\brief      Fichier de javascript de traduction pour JSGantt
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))     define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');


require_once '../main.inc.php';

// Define css type
header('Content-type: text/javascript');

global $langs;
?>
var i18n = new Array();
i18n["sMinute"]= "<?php echo $langs->transnoentities("Minute") ?>";
i18n["sMinutes"]= "<?php echo $langs->transnoentities("Minutes") ?>";
i18n["sHour"]= "<?php echo $langs->transnoentities("Hour") ?>";
i18n["sHours"]= "<?php echo $langs->transnoentities("Hours") ?>";
i18n["sDay"]= "<?php echo $langs->transnoentities("Day") ?>";
i18n["sDays"]= "<?php echo $langs->transnoentities("Days") ?>";
i18n["sWeek"] = "<?php echo $langs->transnoentities("Week") ?>";
i18n["sMonth"] = "<?php echo $langs->transnoentities("Month") ?>";
i18n["sQuarter"] = "<?php echo $langs->transnoentities("Quadri") ?>";
i18n["View"] = "<?php echo $langs->transnoentities("View") ?>";
i18n["Resource"] = "<?php echo $langs->transnoentities("Resources") ?>";
i18n["Duration"] = "<?php echo $langs->transnoentities("Duration") ?>";
i18n["Start_Date"] = "<?php echo $langs->transnoentities("DateStart") ?>";
i18n["End_Date"] = "<?php echo $langs->transnoentities("DateEnd") ?>";
i18n["Date_Format"] = "<?php echo $langs->transnoentities("Format") ?>";
i18n["January"] = "<?php echo $langs->transnoentities("January") ?>";
i18n["February"] = "<?php echo $langs->transnoentities("February") ?>";
i18n["March"] = "<?php echo $langs->transnoentities("March") ?>";
i18n["April"] = "<?php echo $langs->transnoentities("April") ?>";
i18n["May"] = "<?php echo $langs->transnoentities("May") ?>";
i18n["June"] = "<?php echo $langs->transnoentities("June") ?>";
i18n["July"] = "<?php echo $langs->transnoentities("July") ?>";
i18n["August"] = "<?php echo $langs->transnoentities("August") ?>";
i18n["September"] = "<?php echo $langs->transnoentities("Septembre") ?>";
i18n["October"] = "<?php echo $langs->transnoentities("October") ?>";
i18n["November"] = "<?php echo $langs->transnoentities("November") ?>";
i18n["December"] = "<?php echo $langs->transnoentities("December") ?>";
i18n["Quarter"] = "<?php echo $langs->transnoentities("Quarter") ?>";
i18n["Period"] = "<?php echo $langs->transnoentities("Period") ?>";


<?php
if (is_object($db)) $db->close();
