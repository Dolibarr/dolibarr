<?php
/* Copyright (C) 2018	    Quentin Vial-Gouteyron      <quentin.vial-gouteyron@atm-consulting.fr>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/admin/reception_setup.php
 *		\ingroup    reception
 *		\brief      Page to setup reception module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/reception.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array("admin", "receptions", 'other'));


if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'reception';


/*
 * Actions
 */

if (isModEnabled('reception') && !getDolGlobalString('MAIN_SUBMODULE_RECEPTION')) {
	// This option should always be set to on when module is on.
	dolibarr_set_const($db, "MAIN_SUBMODULE_RECEPTION", "1", 'chaine', 0, '', $conf->entity);
}

if (!getDolGlobalString('RECEPTION_ADDON_NUMBER')) {
	$conf->global->RECEPTION_ADDON_NUMBER = 'mod_reception_beryl';
}


/*
 * Actions
 */
$error = 0;

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconstreception', 'aZ09');
	$maskvalue = GETPOST('maskreception', 'alpha');
	if (!empty($maskconst) && preg_match('/_MASK$/', $maskconst)) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
	}

	if (isset($res)) {
		if ($res > 0) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
} elseif ($action == 'set_param') {
	$freetext = GETPOST('RECEPTION_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res = dolibarr_set_const($db, "RECEPTION_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
	if ($res <= 0) {
		$error++;
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

	$draft = GETPOST('RECEPTION_DRAFT_WATERMARK', 'alpha');
	$res = dolibarr_set_const($db, "RECEPTION_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);
	if ($res <= 0) {
		$error++;
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$exp = new Reception($db);
	$exp->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/reception/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePdfReception $module';
		/** @var ModelePdfReception $module */

		if ($module->write_file($exp, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=reception&file=SPECIMEN.pdf");
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
		if (getDolGlobalString('RECEPTION_ADDON_PDF') == "$value") {
			dolibarr_del_const($db, 'RECEPTION_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "RECEPTION_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// so we go through a variable to get a consistent display
		$conf->global->RECEPTION_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmodel') {
	dolibarr_set_const($db, "RECEPTION_ADDON_NUMBER", $value, 'chaine', 0, '', $conf->entity);
}




/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$form = new Form($db);

llxHeader('', $langs->trans("ReceptionsSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-reception_setup');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("ReceptionsSetup"), $linkback, 'title_setup');
print '<br>';
$head = reception_admin_prepare_head();

print dol_get_fiche_head($head, 'reception', $langs->trans("Receptions"), -1, 'reception');

// Reception numbering model

$reception = new Reception($db);
$reception->initAsSpecimen();

printNumberingModuleList($db, $langs, $form, $dirmodels, 'reception', 'mod_reception_', 'RECEPTION_ADDON_NUMBER', $langs->trans("ReceptionsNumberingModules"), $reception, 'setmodel');

print '<br>';

/*
 *  Documents models for Receptions Receipt
 */
printDocumentModelList($db, $langs, $form, $dirmodels, 'reception', 'reception', 'RECEPTION_ADDON_PDF', $langs->trans("ReceptionsReceiptModel"), array(
	'Logo' => 'option_logo',
	'PaymentMode' => 'option_modereg',
	'PaymentConditions' => 'option_condreg',
	'MultiLanguage' => 'option_multilang',
	'WatermarkOnDraftOrders' => 'option_draft_watermark',
));

print '<br>';


/*
 * Other options
 */

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>\n";
print "<td></td>\n";
print '</tr>';

// Notifications
/*
print '<tr class="oddeven">';
print '<td>'.img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("Notifications").'</td>';
print '<td>';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification");
print '</td></tr>';
*/

// More PDF options
print '<tr class="oddeven">';
print '<td>'.img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("MoreOptionsRelatedToPDF").'</td>';
print '<td>';
print img_picto('', 'url', 'class="pictofixedwidth"').'<a href="'.DOL_URL_ROOT.'/admin/pdf_other.php">'.$langs->trans("SeeInPDFSetupPage").'</a>';
print '</td></tr>';

print '</table>';


print '</form>';

llxFooter();
$db->close();
