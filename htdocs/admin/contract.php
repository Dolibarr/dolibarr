<?php
/* Copyright (C) 2011-2013      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2018      Philippe Grand	    <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/contract.php
 *	\ingroup    contract
 *	\brief      Setup page of module Contracts
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "errors", "contracts"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'contract';

if (!getDolGlobalString('CONTRACT_ADDON')) {
	$conf->global->CONTRACT_ADDON = 'mod_contract_serpis';
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$error = 0;

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconstcontract', 'aZ09');
	$maskvalue = GETPOST('maskcontract', 'alpha');
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

	$contract = new Contrat($db);
	$contract->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/contract/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePDFContract $module';

		/** @var ModelePDFContract $module */
		if ($module->write_file($contract, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=contract&file=SPECIMEN.pdf");
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
		if (getDolGlobalString('CONTRACT_ADDON_PDF') == "$value") {
			dolibarr_del_const($db, 'CONTRACT_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "CONTRACT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// so we go through a variable to get a consistent display
		$conf->global->CONTRACT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'unsetdoc') {
	dolibarr_del_const($db, "CONTRACT_ADDON_PDF", $conf->entity);
} elseif ($action == 'setmod') {
	// TODO Verify si the chosen numbering module can be activated by
	// the method call canBeActivated

	dolibarr_set_const($db, "CONTRACT_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'set_other') {
	$freetext = GETPOST('CONTRACT_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res1 = dolibarr_set_const($db, "CONTRACT_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);

	$draft = GETPOST('CONTRACT_DRAFT_WATERMARK', 'alpha');
	$res2 = dolibarr_set_const($db, "CONTRACT_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);

	$value = GETPOST('activate_hideClosedServiceByDefault', 'alpha');
	$res3 = dolibarr_set_const($db, "CONTRACT_HIDE_CLOSED_SERVICES_BY_DEFAULT", $value, 'chaine', 0, '', $conf->entity);

	if (!($res1 > 0) || !($res2 > 0) || !($res3 > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == "allowonlinesign") {
	if (!dolibarr_set_const($db, "CONTRACT_ALLOW_ONLINESIGN", $value, 'int', 0, '', $conf->entity)) {
		$error++;
	}
} elseif (preg_match('/set_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code) ? GETPOST($code) : 1);

	$res = dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}
} elseif (preg_match('/del_(.*)/', $action, $reg)) {
	$code = $reg[1];
	$res = dolibarr_del_const($db, $code, $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if ($error) {
		setEventMessages($langs->trans('Error'), null, 'errors');
	} else {
		setEventMessages($langs->trans('SetupSaved'), null, 'mesgs');
		header("Location: " . $_SERVER["PHP_SELF"]);
		exit();
	}
}



/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-contract');

$form = new Form($db);

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("ContractsSetup"), $linkback, 'title_setup');

$head = contract_admin_prepare_head();

print dol_get_fiche_head($head, 'contract', $langs->trans("Contracts"), -1, 'contract');

/*
 * Contracts Numbering model
 */

$contract = new Contrat($db);
$contract->initAsSpecimen();

printNumberingModuleList('contract', 'mod_contract_', 'CONTRACT_ADDON', $langs->trans("ContractsNumberingModules"), $contract);

print '<br>';

/*
 *  Documents models for Contracts
 */

printDocumentModelList($type, 'contract', 'CONTRACT_ADDON_PDF', $langs->trans("TemplatePDFContracts"), array(
	'Logo' => 'option_logo',
	'PaymentMode' => 'option_modereg',
	'PaymentConditions' => 'option_condreg',
	'MultiLanguage' => 'option_multilang',
	'WatermarkOnDraftOrders' => 'option_draft_watermark',
), false, '', 1, 'unsetdoc');
print "<br>";

/*
 * Other options
 *
 */

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_other">';

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="right"></td>';
print "</tr>\n";

$substitutionarray = pdf_getSubstitutionArray($langs, array('objectamount'), null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<tr class="oddeven"><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnContracts"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'tooltiphelp');
print '<br>';
$variablename = 'CONTRACT_FREE_TEXT';
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
print $form->textwithpicto($langs->trans("WatermarkOnDraftContractCards"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip').'<br>';
print '</td><td class="right">';
print '<input class="flat minwidth200" type="text" name="CONTRACT_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('CONTRACT_DRAFT_WATERMARK')).'">';
print '</td></tr>'."\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("HideClosedServiceByDefault").'</td>';
print '<td class="right">';
print $form->selectyesno("activate_hideClosedServiceByDefault", getDolGlobalInt('CONTRACT_HIDE_CLOSED_SERVICES_BY_DEFAULT', 0), 1);
print '</td>';
print '</tr>';

// Allow online signing
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowOnlineSign").'</td>';
print '<td class="right">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('CONTRACT_ALLOW_ONLINESIGN', array(), null, 0, 0, 0, 2, 0, 1, '', '', 'inline-block', 0, $langs->transnoentitiesnoconv("WarningOnlineSignature", "https://www.dolistore.com"));
} else {
	if (getDolGlobalString('CONTRACT_ALLOW_ONLINESIGN')) {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=allowonlinesign&token='.newToken().'&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a>';
	} else {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=allowonlinesign&token='.newToken().'&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a>';
	}
}
print '</td>';
print '</tr>';

// Allow external download
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowExternalDownload").'</td>';
print '<td class="right">';
print ajax_constantonoff('CONTRACT_ALLOW_EXTERNAL_DOWNLOAD', array(), null, 0, 0, 0, 2, 0, 1);
print '</td>';
print '</tr>';

// Reminder by email before a contract service expires
if (isModEnabled('cron')) {
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("SendReminderForExpiredServicesTitle").'</td>';
	print '<td class="right">';
	print '<a href="'.DOL_URL_ROOT.'cron/list.php?search_label=SendReminderForExpiredServicesTitle&status=-1">'.$langs->trans("ConfigureContractReminderCronjobToSetFrequency").'</a>';
	print '</td>';
	print '</tr>';
}
print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
