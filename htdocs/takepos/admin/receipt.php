<?php
/* Copyright (C) 2008-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2019       Andreu Bisquerra Gaya   <jove@bisquerra.com>
 * Copyright (C) 2021       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2022       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/takepos/admin/receipt.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "cashdesk", "commercial"));


/*
 * Actions
 */
$error = 0;

if (GETPOST('action', 'alpha') == 'set') {
	$db->begin();

	$res = dolibarr_set_const($db, "TAKEPOS_HEADER", GETPOST('TAKEPOS_HEADER', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_FOOTER", GETPOST('TAKEPOS_FOOTER', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_RECEIPT_NAME", GETPOST('TAKEPOS_RECEIPT_NAME', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_PRINT_SERVER", GETPOST('TAKEPOS_PRINT_SERVER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, 'TAKEPOS_PRINT_WITHOUT_DETAILS_LABEL_DEFAULT', GETPOST('TAKEPOS_PRINT_WITHOUT_DETAILS_LABEL_DEFAULT', 'alphanohtml'), 'chaine', 0, '', $conf->entity);

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif (GETPOST('action', 'alpha') == 'setmethod') {
	dolibarr_set_const($db, "TAKEPOS_PRINT_METHOD", GETPOST('value', 'alpha'), 'chaine', 0, '', $conf->entity);
	// TakePOS connector require ReceiptPrinter module
	if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector" && !isModEnabled('receiptprinter')) {
		activateModule("modReceiptPrinter");
	}
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"), '', '', 0, 0, '', '', '', 'mod-takepos page-admin_receipt');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'receipt', 'TakePOS', -1, 'cash-register');

print '<form action="'.$_SERVER["PHP_SELF"].'?terminal='.(empty($terminal) ? 1 : $terminal).'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print load_fiche_titre($langs->trans("Receipt"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

// VAT Grouped on ticket
print '<tr class="oddeven"><td>';
print $langs->trans('TicketVatGrouped');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_TICKET_VAT_GROUPPED", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "browser" || getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector") {
	$substitutionarray = pdf_getSubstitutionArray($langs, array('ticket', 'member', 'candidate'), null, 2, array('company', 'user', 'object', 'system'));
	$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");

	$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
	foreach ($substitutionarray as $key => $val) {
		$htmltext .= $key.'<br>';
	}
	$htmltext .= '</i>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices")." - ".$langs->trans("Header"), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
	print '</td><td>';
	$variablename = 'TAKEPOS_HEADER';
	if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print "</td></tr>\n";

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices")." - ".$langs->trans("Footer"), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
	print '</td><td>';
	$variablename = 'TAKEPOS_FOOTER';
	if (!getDolGlobalString('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print "</td></tr>\n";

	print '<tr class="oddeven"><td><label for="receipt_name">'.$langs->trans("ReceiptName").'</label></td><td>';
	print '<input name="TAKEPOS_RECEIPT_NAME" id="TAKEPOS_RECEIPT_NAME" class="minwidth200" value="'.getDolGlobalString('TAKEPOS_RECEIPT_NAME').'">';
	print '</td></tr>';

	// Customer information
	print '<tr class="oddeven"><td>';
	print $langs->trans('PrintCustomerOnReceipts');
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_SHOW_CUSTOMER", array(), $conf->entity, 0, 0, 1, 0);
	print "</td></tr>\n";

	// Print payment method
	print '<tr class="oddeven"><td>';
	print $langs->trans('PrintPaymentMethodOnReceipts');
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_PRINT_PAYMENT_METHOD", array(), $conf->entity, 0, 0, 1, 0);
	print "</td></tr>\n";
}

// Show price without vat
print '<tr class="oddeven"><td>';
print $langs->trans('ShowPriceHTOnReceipt');
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_SHOW_HT_RECEIPT", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";

if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector" && filter_var(getDolGlobalString('TAKEPOS_PRINT_SERVER'), FILTER_VALIDATE_URL) == true) {
	print '<tr class="oddeven"><td>';
	print $langs->trans('WeighingScale');
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_WEIGHING_SCALE", array(), $conf->entity, 0, 0, 1, 0);
	print "</td></tr>\n";
}

if (getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector" && filter_var(getDolGlobalString('TAKEPOS_PRINT_SERVER'), FILTER_VALIDATE_URL) == true) {
	print '<tr class="oddeven"><td>';
	print $langs->trans('CustomerDisplay');
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_CUSTOMER_DISPLAY", array(), $conf->entity, 0, 0, 1, 0);
	print "</td></tr>\n";
}

// Print without details
print '<tr class="oddeven"><td>';
print $langs->trans('PrintWithoutDetailsButton');
print '<td colspan="2">';
print ajax_constantonoff('TAKEPOS_PRINT_WITHOUT_DETAILS', array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";
if (getDolGlobalString('TAKEPOS_PRINT_WITHOUT_DETAILS')) {
	print '<tr class="oddeven"><td>';
	print $langs->trans('PrintWithoutDetailsLabelDefault');
	print '<td colspan="2">';
	print '<input type="text" name="TAKEPOS_PRINT_WITHOUT_DETAILS_LABEL_DEFAULT" value="' . getDolGlobalString('TAKEPOS_PRINT_WITHOUT_DETAILS_LABEL_DEFAULT') . '" />';
	print "</td></tr>\n";
}

// Auto print tickets
print '<tr class="oddeven"><td>';
print $langs->trans("AutoPrintTickets");
print '<td colspan="2">';
print ajax_constantonoff("TAKEPOS_AUTO_PRINT_TICKETS", array(), $conf->entity, 0, 0, 1, 0);
print "</td></tr>\n";


print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";


print '<br>';


print load_fiche_titre($langs->trans("Preview"), '', '');
print '<div style="width: 50%; float:center;background-color:#606060">';
print '<center>';
print '<iframe id="iframe" allowtransparency="true" style="background: #FFFFFF;" src="../receipt.php" width="80%" height="600"></iframe>';
print '</center>';
print '</div>';

print '<br>';

llxFooter();
$db->close();
