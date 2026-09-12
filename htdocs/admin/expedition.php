<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2011-2018	Philippe Grand			<philippe.grand@atoo-net.com>
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
 *	\file       htdocs/admin/expedition.php
 *	\ingroup    expedition
 *	\brief      Page d'administration/configuration du module Expedition
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expedition.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "sendings", "other"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'shipping';

if (!getDolGlobalString('EXPEDITION_ADDON_NUMBER')) {
	$conf->global->EXPEDITION_ADDON_NUMBER = 'mod_expedition_safor';
}


/*
 * Actions
 */
$error = 0;

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconstexpedition', 'aZ09');
	$maskvalue = GETPOST('maskexpedition', 'alpha');
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
	$freetext = GETPOST('SHIPPING_FREE_TEXT', 'restricthtml'); // No alpha here, we want exact string
	$res = dolibarr_set_const($db, "SHIPPING_FREE_TEXT", $freetext, 'chaine', 0, '', $conf->entity);
	if ($res <= 0) {
		$error++;
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

	$draft = GETPOST('SHIPPING_DRAFT_WATERMARK', 'alpha');
	$res = dolibarr_set_const($db, "SHIPPING_DRAFT_WATERMARK", trim($draft), 'chaine', 0, '', $conf->entity);
	if ($res <= 0) {
		$error++;
		setEventMessages($langs->trans("Error"), null, 'errors');
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$exp = new Expedition($db);
	$exp->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/expedition/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);
		'@phan-var-force ModelePdfExpedition $module';

		if ($module->write_file($exp, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=expedition&file=SPECIMEN.pdf");
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
		if (getDolGlobalString('EXPEDITION_ADDON_PDF') == "$value") {
			dolibarr_del_const($db, 'EXPEDITION_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "EXPEDITION_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// so we go through a variable to get a consistent display
		$conf->global->EXPEDITION_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'setmodel') {
	dolibarr_set_const($db, "EXPEDITION_ADDON_NUMBER", $value, 'chaine', 0, '', $conf->entity);
}


/*
 * View
 */

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$form = new Form($db);

llxHeader("", $langs->trans("SendingsSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-expedition');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("SendingsSetup"), $linkback, 'title_setup');
print '<br>';
$head = expedition_admin_prepare_head();

print dol_get_fiche_head($head, 'shipment', $langs->trans("Sendings"), -1, 'shipment');

// Shipment numbering model

$expedition = new Expedition($db);
$expedition->initAsSpecimen();

printNumberingModuleList('expedition', 'mod_expedition_', 'EXPEDITION_ADDON_NUMBER', $langs->trans("SendingsNumberingModules"), $expedition, 'setmodel');

print '<br>';


/*
 *  Documents models for Sendings Receipt
 */
printDocumentModelList('shipping', 'expedition', 'EXPEDITION_ADDON_PDF', $langs->trans("SendingsReceiptModel"), array(
	'Logo' => 'option_logo',
	'PaymentMode' => 'option_modereg',
	'PaymentConditions' => 'option_condreg',
	'MultiLanguage' => 'option_multilang',
	'WatermarkOnDraftOrders' => 'option_draft_watermark',
));


/*
 * Other options
 */

print load_fiche_titre($langs->trans("OtherOptions"), '', '');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_param">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>\n";
print "<td></td>\n";
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans('SHIPPING_DISPLAY_STOCK_ENTRY_DATE');
print '</td>';
print '<td>';
print ajax_constantonoff('SHIPPING_DISPLAY_STOCK_ENTRY_DATE');
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans('SHIPPING_SELL_EAT_BY_DATE_PRE_SELECT_EARLIEST');
print '</td>';
print '<td>';
print ajax_constantonoff('SHIPPING_SELL_EAT_BY_DATE_PRE_SELECT_EARLIEST');
print '</td></tr>';

$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<tr><td colspan="2">';
print $form->textwithpicto($langs->trans("FreeLegalTextOnShippings"), $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
$variablename = 'SHIPPING_FREE_TEXT';
if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print "</td></tr>\n";

print '<tr><td>';
print $form->textwithpicto($langs->trans("WatermarkOnDraftContractCards"), $htmltext, 1, 'help', '', 0, 2, 'watermarktooltip');
print '</td><td>';
print '<input class="flat minwidth200" type="text" name="SHIPPING_DRAFT_WATERMARK" value="'.dol_escape_htmltag(getDolGlobalString('SHIPPING_DRAFT_WATERMARK')).'">';
print "</td></tr>\n";

// Allow OnLine Sign
print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowOnLineSign");
print '</td>';
print '<td>';
print ajax_constantonoff('EXPEDITION_ALLOW_ONLINESIGN', array(), null, 0, 0, 0, 2, 0, 1, '', '', 'inline-block', 0, $langs->transnoentitiesnoconv("WarningOnlineSignature", 'https://www.dolistore.com'));
print '</td></tr>';

// Pre fill shipment qty option
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DontPrefillShipmentQty");
print '</td>';
print '<td>';
print ajax_constantonoff('SHIPMENT_DONT_PREFILL_QTY', array(), null, 0, 0, 0, 2, 0, 1, '', '', 'inline-block', 0, '');
print '</td></tr>';


// Notifications
print '<tr class="oddeven">';
print '<td>'.img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("Notifications").'</td>';
print '<td>';
print $langs->trans("YouMayFindNotificationsFeaturesIntoModuleNotification");
print '</td></tr>';

// More PDF options
print '<tr class="oddeven">';
print '<td>'.img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("MoreOptionsRelatedToPDF").'</td>';
print '<td>';
print img_picto('', 'url', 'class="pictofixedwidth"').'<a href="'.DOL_URL_ROOT.'/admin/pdf_other.php">'.$langs->trans("SeeInPDFSetupPage").'</a>';
print '</td></tr>';

print '</table>';

print $form->buttonsSaveCancel("Modify", '');

print '</form>';

// End of page
llxFooter();
$db->close();
