<?php
/* Copyright (C) 2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       htdocs/core/js/datepicker.js.php
 * \brief      File that include javascript functions for datepickers
 */

//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter(FALSE);

require_once("../../main.inc.php");

// Define javascript type
header('Content-type: text/javascript');
header("Content-type: text/html; charset=UTF-8");
// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');


// Define tradMonths javascript array (we define this in datepicker AND in parent page to avoid errors with IE8)
$tradMonths=array(
$langs->trans("January"),
$langs->trans("February"),
$langs->trans("March"),
$langs->trans("April"),
$langs->trans("May"),
$langs->trans("June"),
$langs->trans("July"),
$langs->trans("August"),
$langs->trans("September"),
$langs->trans("October"),
$langs->trans("November"),
$langs->trans("December")
);
?>


// For eldy date picker
var tradMonths = <?php echo json_encode($tradMonths) ?>;


// For JQuery date picker
$(document).ready(function() {
	$.datepicker.setDefaults({
		altField: '#timeStamp',
		altFormat: '@'			// Gives a timestamp dateformat
	});
});

jQuery(function($){
	$.datepicker.regional['<?php echo $langs->defaultlang ?>'] = {
		closeText: '<?php echo $langs->trans("Close") ?>',
		prevText: '<?php echo $langs->trans("Previous") ?>',
		nextText: '<?php echo $langs->trans("Next") ?>',
		currentText: '<?php echo $langs->trans("Now") ?>',
		monthNames: [<?php echo "'".$langs->trans("January")."',".
		"'".$langs->trans("February")."',".
		"'".$langs->trans("March")."',".
		"'".$langs->trans("April")."',".
		"'".$langs->trans("May")."',".
		"'".$langs->trans("June")."',".
		"'".$langs->trans("July")."',".
		"'".$langs->trans("August")."',".
		"'".$langs->trans("September")."',".
		"'".$langs->trans("October")."',".
		"'".$langs->trans("November")."',".
		"'".$langs->trans("December")."'" ?>],
		monthNamesShort: [<?php echo "'".$langs->trans("JanuaryMin")."',".
		"'".$langs->trans("FebruaryMin")."',".
		"'".$langs->trans("MarchMin")."',".
		"'".$langs->trans("AprilMin")."',".
		"'".$langs->trans("MayMin")."',".
		"'".$langs->trans("JuneMin")."',".
		"'".$langs->trans("JulyMin")."',".
		"'".$langs->trans("AugustMin")."',".
		"'".$langs->trans("SeptemberMin")."',".
		"'".$langs->trans("OctoberMin")."',".
		"'".$langs->trans("NovemberMin")."',".
		"'".$langs->trans("DecemberMin")."'" ?>],
		dayNames: [<?php echo "'".$langs->trans("Sunday")."',".
		"'".$langs->trans("Monday")."',".
		"'".$langs->trans("Tuesday")."',".
		"'".$langs->trans("Wednesday")."',".
		"'".$langs->trans("Thursday")."',".
		"'".$langs->trans("Friday")."',".
		"'".$langs->trans("Saturday")."'" ?>],
		dayNamesShort: [<?php echo "'".$langs->trans("SundayMin")."',".
		"'".$langs->trans("MondayMin")."',".
		"'".$langs->trans("TuesdayMin")."',".
		"'".$langs->trans("WednesdayMin")."',".
		"'".$langs->trans("ThursdayMin")."',".
		"'".$langs->trans("FridayMin")."',".
		"'".$langs->trans("SaturdayMin")."'" ?>],
		dayNamesMin: [<?php echo "'".$langs->trans("ShortSunday")."',".
		"'".$langs->trans("ShortMonday")."',".
		"'".$langs->trans("ShortTuesday")."',".
		"'".$langs->trans("ShortWednesday")."',".
		"'".$langs->trans("ShortThursday")."',".
		"'".$langs->trans("ShortFriday")."',".
		"'".$langs->trans("ShortSaturday")."'" ?>],
		weekHeader: 'Sem.',			// TODO add specific to country
		dateFormat: '<?php echo $langs->trans("FormatDateShortJQuery"); ?>',
		firstDay: 1,				// TODO add specific to country
		isRTL: false,				// TODO add specific to country
		showMonthAfterYear: false,	// TODO add specific to country
		yearSuffix: ''				// TODO add specific to country
	};
	$.datepicker.setDefaults($.datepicker.regional['<?php echo $langs->defaultlang ?>']);
});
