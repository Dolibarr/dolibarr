<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019 Andreu Bisquerra Gaya		<jove@bisquerra.com>
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
 *	\file       htdocs/takepos/admin/terminal.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

// Security check
if (!$user->admin) accessforbidden();

$langs->loadLangs(array("admin", "cashdesk", "commercial"));

/*
 * Actions
 */

if (GETPOST('action', 'alpha') == 'set')
{
	$db->begin();

	$res = dolibarr_set_const($db, "TAKEPOS_HEADER", GETPOST('TAKEPOS_HEADER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_FOOTER", GETPOST('TAKEPOS_FOOTER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_RECEIPT_NAME", GETPOST('TAKEPOS_RECEIPT_NAME', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_SHOW_CUSTOMER", GETPOST('TAKEPOS_SHOW_CUSTOMER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_AUTO_PRINT_TICKETS", GETPOST('TAKEPOS_AUTO_PRINT_TICKETS', 'int'), 'int', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_PRINT_SERVER", GETPOST('TAKEPOS_PRINT_SERVER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_PRINT_PAYMENT_METHOD", GETPOST('TAKEPOS_PRINT_PAYMENT_METHOD', 'alpha'), 'chaine', 0, '', $conf->entity);

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

	if (!$res > 0) $error++;

 	if (!$error)
	{
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif (GETPOST('action', 'alpha') == 'setmethod')
{
	dolibarr_set_const($db, "TAKEPOS_PRINT_METHOD", GETPOST('value', 'alpha'), 'chaine', 0, '', $conf->entity);
	// TakePOS connector require ReceiptPrinter module
	if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector" && !$conf->receiptprinter->enabled) activateModule("modReceiptPrinter");
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'receipt', 'TakePOS', -1, 'cash-register');

print '<form action="'.$_SERVER["PHP_SELF"].'?terminal='.(empty($terminal) ? 1 : $terminal).'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print load_fiche_titre($langs->trans("PrintMethod"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td><td>'.$langs->trans("Description").'</td><td class="right">'.$langs->trans("Status").'</td>';
print "</tr>\n";

// Browser method
print '<tr class="oddeven"><td>';
print $langs->trans('Browser');
print '<td>';
print $langs->trans('BrowserMethodDescription');
print '</td><td class="right">';
if ($conf->global->TAKEPOS_PRINT_METHOD == "browser")
{
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmethod&token='.newToken().'&value=browser">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print "</td></tr>\n";

// Receipt printer module
print '<tr class="oddeven"><td>';
print $langs->trans('DolibarrReceiptPrinter');
print '<td>';
print $langs->trans('ReceiptPrinterMethodDescription');
print '<br>';
print '<a href="'.DOL_URL_ROOT.'/admin/receiptprinter.php">'.$langs->trans("Setup").'</a>';
print '</td><td class="right">';
if ($conf->receiptprinter->enabled) {
	if ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter") {
		print img_picto($langs->trans("Activated"), 'switch_on');
	} else {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmethod&token='.newToken().'&value=receiptprinter">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
	}
} else {
	print '<span class="opacitymedium">';
	print $langs->trans("ModuleReceiptPrinterMustBeEnabled");
	print '</span>';
}
print "</td></tr>\n";

// TakePOS Connector
print '<tr class="oddeven"><td>';
print "TakePOS Connector";
print '<td>';
print $langs->trans('TakeposConnectorMethodDescription');
print '</td><td class="right">';
if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector")
{
	print img_picto($langs->trans("Activated"), 'switch_on');
} else {
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmethod&token='.newToken().'&value=takeposconnector">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
}
print "</td></tr>\n";
print '</table>';
print '</div>';


print load_fiche_titre($langs->trans("Setup"), '', '');

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
//print $form->selectyesno("TAKEPOS_TICKET_VAT_GROUPPED", $conf->global->TAKEPOS_TICKET_VAT_GROUPPED, 1);
print "</td></tr>\n";

if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
	print '<tr class="oddeven value"><td>';
	print $langs->trans("URL")." / ".$langs->trans("IPAddress").' (<a href="http://en.takepos.com/connector" target="_blank">'.$langs->trans("TakeposConnectorNecesary").'</a>)';
	print '<td colspan="2">';
	print '<input type="text" size="20" id="TAKEPOS_PRINT_SERVER" name="TAKEPOS_PRINT_SERVER" value="'.$conf->global->TAKEPOS_PRINT_SERVER.'">';
	print '</td></tr>';
}

if ($conf->global->TAKEPOS_PRINT_METHOD == "browser" || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
	$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
	$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans("Translation");
	$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
	foreach ($substitutionarray as $key => $val)	$htmltext .= $key.'<br>';
	$htmltext .= '</i>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices")." - ".$langs->trans("Header"), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
	print '</td><td>';
	$variablename = 'TAKEPOS_HEADER';
	if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
	{
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print "</td></tr>\n";

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices")." - ".$langs->trans("Footer"), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
	print '</td><td>';
	$variablename = 'TAKEPOS_FOOTER';
	if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
	{
		print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
	} else {
		include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
		print $doleditor->Create();
	}
	print "</td></tr>\n";

	print '<tr class="oddeven"><td><label for="receipt_name">'.$langs->trans("ReceiptName").'</label></td><td>';
	print '<input name="TAKEPOS_RECEIPT_NAME" id="TAKEPOS_RECEIPT_NAME" class="minwidth200" value="'.(!empty($conf->global->TAKEPOS_RECEIPT_NAME) ? $conf->global->TAKEPOS_RECEIPT_NAME : '').'">';
	print '</td></tr>';

	// Customer information
	print '<tr class="oddeven"><td>';
	print $langs->trans('PrintCustomerOnReceipts');
	print '<td colspan="2">';
	print $form->selectyesno("TAKEPOS_SHOW_CUSTOMER", $conf->global->TAKEPOS_SHOW_CUSTOMER, 1);
	print "</td></tr>\n";

	// Print payment method
	print '<tr class="oddeven"><td>';
	print $langs->trans('PrintPaymentMethodOnReceipts');
	print '<td colspan="2">';
	print $form->selectyesno("TAKEPOS_PRINT_PAYMENT_METHOD", $conf->global->TAKEPOS_PRINT_PAYMENT_METHOD, 1);
	print "</td></tr>\n";
}

// Auto print tickets
print '<tr class="oddeven"><td>';
print $langs->trans("AutoPrintTickets");
print '<td colspan="2">';
print $form->selectyesno("TAKEPOS_AUTO_PRINT_TICKETS", $conf->global->TAKEPOS_AUTO_PRINT_TICKETS, 1);
print "</td></tr>\n";

if ($conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector" && filter_var($conf->global->TAKEPOS_PRINT_SERVER, FILTER_VALIDATE_URL) == true) {
	print '<tr class="oddeven"><td>';
	print $langs->trans('WeighingScale');
	print '<td colspan="2">';
	print ajax_constantonoff("TAKEPOS_WEIGHING_SCALE", array(), $conf->entity, 0, 0, 1, 0);
	print "</td></tr>\n";
}

print '</table>';
print '</div>';

print '<br>';

print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";

print '<br>';

llxFooter();
$db->close();
