<?php
/* Copyright (C) 2011-2019      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2018      Philippe Grand	    <philippe.grand@atoo-net.com>
 * Copyright (C) 2018		    Charlene Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/admin/contract.php
 *	\ingroup    contract
 *	\brief      Setup page of module Contracts
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/holiday.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors", "holiday", "other"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'contract';

if (!getDolGlobalString('HOLIDAY_ADDON')) {
	$conf->global->HOLIDAY_ADDON = 'mod_holiday_madonna';
}


/*
 * Actions
 */
$error = 0;

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconstholiday', 'aZ09');
	$maskvalue = GETPOST('maskholiday', 'alpha');
	$res = 0;
	if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
	}

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') { // For contract
	$modele = GETPOST('module', 'alpha');

	$holiday = new Holiday($db);
	$holiday->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/holiday/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFHoliday $module';

		if ($module->write_file($holiday, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=holiday&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if (getDolGlobalString('HOLIDAY_ADDON_PDF') == "$value") {
			dolibarr_del_const($db, 'HOLIDAY_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "HOLIDAY_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// so we go through a variable to get a consistent display
		$conf->global->HOLIDAY_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmod') {
	// TODO Verifier si module numerotation choisi peut etre active
	// par appel method canBeActivated

	dolibarr_set_const($db, "HOLIDAY_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'set_other') {
	$freetext = GETPOST('HOLIDAY_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res1 = dolibarr_set_const($db, "HOLIDAY_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	$draft = GETPOST('HOLIDAY_DRAFT_WATERMARK', 'alpha');
	$res2 = dolibarr_set_const($db, "HOLIDAY_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	if (!($res1 > 0) || !($res2 > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-holiday');

$form = new Form($db);

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("HolidaySetup"), $linkback, 'title_setup');

$head = holiday_admin_prepare_head();

print dol_get_fiche_head($head, 'holiday', $langs->trans("Holidays"), -1, 'holiday');

/*
 * Holiday Numbering model
 */

$holiday = new Holiday($db);
$holiday->initAsSpecimen();

printNumberingModuleList($db, $langs, $form, $dirmodels, 'holiday', 'mod_holiday_', 'HOLIDAY_ADDON', $langs->trans("HolidaysNumberingModules"), $holiday);

print '<br>';


/*
 *  Documents models for Holidays
 */

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	printDocumentModelList($db, $langs, $form, $dirmodels, $type, 'holiday', 'HOLIDAY_ADDON_PDF', $langs->trans("TemplatePDFHolidays"), array(
		'Logo' => 'option_logo',
		'PaymentMode' => 'option_modereg',
		'PaymentConditions' => 'option_condreg',
		'MultiLanguage' => 'option_multilang',
		'WatermarkOnDraftOrders' => 'option_draft_watermark',
	));
	print "<br>";
}


/*
 * Type of leaves
 */

print load_fiche_titre($langs->trans("LeaveType"), '', '');

print '<div class="urllink">';
print img_picto('', 'url', 'class="pictofixedwidth"').' <a href="'.DOL_URL_ROOT.'/admin/dict.php?id=28&search_country_id='.($conf->entity).'">'.$langs->trans("ClickHereToGoToDictionary", $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Dictionary"), $langs->transnoentitiesnoconv("LeaveType")).'</a>';
print '</div>';

print '<br><br>';


/*
 * Type of leaves
 */

print load_fiche_titre($langs->trans("DictionaryPublicHolidays"), '', '');

print '<div class="urllink">';
print img_picto('', 'url', 'class="pictofixedwidth"').' <a href="'.DOL_URL_ROOT.'/admin/dict.php?id=32&search_country_id='.($conf->entity).'">'.$langs->trans("ClickHereToGoToDictionary", $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Dictionary"), $langs->transnoentitiesnoconv("DictionaryPublicHolidays")).'</a>';
print '</div>';

print '<br><br>';


/*
 * Other options
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_other">';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td align="center" width="60"></td>';
print "</tr>\n";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Monday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY', array(), null, 0);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_MONDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Friday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY', array(), null, 0);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_FRIDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Saturday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SATURDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set working days
print '<tr class="oddeven">';
print "<td>".$langs->trans("XIsAUsualNonWorkingDay", $langs->transnoentitiesnoconv("Sunday"))."</td>";
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	if (getDolGlobalString('MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&MAIN_NON_WORKING_DAYS_INCLUDE_SUNDAY=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

// Set holiday decrease at the end of month
print '<tr class="oddeven">';
print "<td>".$langs->trans("ConsumeHolidaysAtTheEndOfTheMonthTheyAreTakenAt").' <span class="opacitymedium">('.$langs->trans("ConsumeHolidaysAtTheEndOfTheMonthTheyAreTakenAtBis").')</span></td>';
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('HOLIDAY_DECREASE_AT_END_OF_MONTH', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	if (getDolGlobalString('HOLIDAY_DECREASE_AT_END_OF_MONTH')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&HOLIDAY_DECREASE_AT_END_OF_MONTH=1">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_other&token='.newToken().'&HOLIDAY_DECREASE_AT_END_OF_MONTH=0">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print "</td>";
print "</tr>";

if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	$substitutionarray = pdf_getSubstitutionArray($langs, array('objectamount'), null, 2);
	$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
	$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
	foreach ($substitutionarray as $key => $val) {
		$htmltext .= $key.'<br>';
	}
	$htmltext .= '</i>';

	print '<tr class="oddeven"><td colspan="2">';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnHolidays"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'tooltiphelp');
	print '<br>';
	$variablename = 'HOLIDAY_FREE_TEXT';
	if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print '</td></tr>'."\n";

	//Use draft Watermark

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("WatermarkOnDraftHolidayCards"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
	print '</td><td>';
	print '<input class="flat minwidth200" type="text" name="HOLIDAY_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('HOLIDAY_DRAFT_WATERMARK')).'">';
	print '</td></tr>'."\n";
}

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';



print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
