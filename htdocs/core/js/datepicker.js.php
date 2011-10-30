<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
 *
 * Script javascript that contains functions for datepicker default options
 */

/**
 * \file       htdocs/core/js/datepicker.js.php
 * \brief      File that include javascript functions for datepicker default options
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
?>


$(document).ready(function() {
	$.datepicker.setDefaults({
		altField: '#timeStamp',
		altFormat: '@'			// Gives a timestamp dateformat
	});
});

jQuery(function($){
	$.datepicker.regional['<?php echo $langs->defaultlang ?>'] = {
		closeText: '<?php echo dol_escape_js($langs->transnoentitiesnoconv("Close")) ?>',
		prevText: '<?php echo dol_escape_js($langs->transnoentitiesnoconv("Previous")) ?>',
		nextText: '<?php echo dol_escape_js($langs->transnoentitiesnoconv("Next")) ?>',
		currentText: '<?php echo dol_escape_js($langs->transnoentitiesnoconv("January")) ?>',
		monthNames: [<?php echo "'".$langs->transnoentitiesnoconv("January")."',".
		"'".$langs->transnoentitiesnoconv("February")."',".
		"'".$langs->transnoentitiesnoconv("March")."',".
		"'".$langs->transnoentitiesnoconv("April")."',".
		"'".$langs->transnoentitiesnoconv("May")."',".
		"'".$langs->transnoentitiesnoconv("June")."',".
		"'".$langs->transnoentitiesnoconv("July")."',".
		"'".$langs->transnoentitiesnoconv("August")."',".
		"'".$langs->transnoentitiesnoconv("September")."',".
		"'".$langs->transnoentitiesnoconv("October")."',".
		"'".$langs->transnoentitiesnoconv("November")."',".
		"'".$langs->transnoentitiesnoconv("December")."'" ?>],
		monthNamesShort: ['Janv.','Févr.','Mars','Avril','Mai','Juin','Juil.','Août','Sept.','Oct.','Nov.','Déc.'],
		dayNames: ['<?php echo dol_escape_js($langs->transnoentitiesnoconv("Sunday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("Monday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("Tuesday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("Wednesday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("Thursday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("Friday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("Saturday")) ?>'],
		dayNamesShort: ['<?php echo dol_escape_js($langs->transnoentitiesnoconv("SundayMin")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("MondayMin")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("TuesdayMin")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("WednesdayMin")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ThursdayMin")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("FridayMin")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("SaturdayMin")) ?>'],
		dayNamesMin: ['<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortSunday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortMonday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortTuesday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortWednesday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortThursday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortFriday")) ?>','<?php echo dol_escape_js($langs->transnoentitiesnoconv("ShortSaturday")) ?>'],
		weekHeader: 'Sem.',
		dateFormat: '<?php echo $langs->transnoentitiesnoconv("FormatDateShortJQuery"); ?>',
		firstDay: 1,
		isRTL: false,
		showMonthAfterYear: false,
		yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['<?php echo $langs->defaultlang ?>']);
});
