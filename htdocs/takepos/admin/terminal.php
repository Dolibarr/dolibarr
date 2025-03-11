<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2021      Thibault FOUCART     <support@ptibogxiv.net>
 * Copyright (C) 2022      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/takepos/admin/terminal.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";
require_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';

$terminal = GETPOSTINT('terminal');
// If socid provided by ajax company selector
if (GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha')) {
	$_GET['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Security check
if (!$user->admin) {
	accessforbidden();
}

$langs->loadLangs(array("admin", "cashdesk", "printing", "receiptprinter"));

$sql = "SELECT code, libelle as label FROM ".MAIN_DB_PREFIX."c_paiement";
$sql .= " WHERE entity IN (".getEntity('c_paiement').")";
$sql .= " AND active = 1";
$sql .= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		array_push($paiements, $obj);
	}
}

$terminaltouse = $terminal;


/*
 * Actions
 */
$error = 0;

if (GETPOST('action', 'alpha') == 'set') {
	$db->begin();

	$res = dolibarr_set_const($db, "TAKEPOS_TERMINAL_NAME_".$terminaltouse, (!empty(GETPOST('terminalname'.$terminaltouse, 'restricthtml')) ? GETPOST('terminalname'.$terminaltouse, 'restricthtml') : $langs->trans("TerminalName", $terminaltouse)), 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, "CASHDESK_ID_THIRDPARTY".$terminaltouse, (GETPOSTINT('socid') > 0 ? GETPOSTINT('socid') : ''), 'chaine', 0, '', $conf->entity);

	if (GETPOSTISSET('projectid')) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_PROJECT".$terminaltouse, (GETPOSTINT('projectid') > 0 ? GETPOSTINT('projectid') : ''), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CASH".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CHEQUE".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CB".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('CASHDESK_ID_BANKACCOUNT_STRIPETERMINAL'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_STRIPETERMINAL".$terminaltouse, GETPOST('CASHDESK_ID_BANKACCOUNT_STRIPETERMINAL'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_SUMUP".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	foreach ($paiements as $modep) {
		if (in_array($modep->code, array('LIQ', 'CB', 'CHQ'))) {
			continue;
		}
		$name = "CASHDESK_ID_BANKACCOUNT_".$modep->code.$terminaltouse;
		if (GETPOSTISSET($name)) {
			$res = dolibarr_set_const($db, $name, (GETPOST($name, 'alpha') > 0 ? GETPOST($name, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
		}
	}
	if (GETPOSTISSET('CASHDESK_ID_WAREHOUSE'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_WAREHOUSE".$terminaltouse, (GETPOST('CASHDESK_ID_WAREHOUSE'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_WAREHOUSE'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('CASHDESK_NO_DECREASE_STOCK'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK".$terminaltouse, GETPOST('CASHDESK_NO_DECREASE_STOCK'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_PRINTER_TO_USE'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_PRINTER_TO_USE".$terminaltouse, GETPOST('TAKEPOS_PRINTER_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTER1_TO_USE".$terminaltouse, GETPOST('TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTER2_TO_USE".$terminaltouse, GETPOST('TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTER3_TO_USE".$terminaltouse, GETPOST('TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES".$terminaltouse, GETPOST('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS".$terminaltouse, GETPOST('TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse)) {
		$res = dolibarr_set_const($db, 'CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse, (GETPOSTINT('CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse) > 0 ? GETPOSTINT('CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse) : ''), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('TAKEPOS_ADDON'.$terminaltouse)) {
		$res = dolibarr_set_const($db, "TAKEPOS_ADDON".$terminaltouse, GETPOST('TAKEPOS_ADDON'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	// Add free text on each terminal of cash desk
	if (GETPOSTISSET('TAKEPOS_HEADER'.$terminaltouse)) {
		$res = dolibarr_set_const($db, 'TAKEPOS_HEADER'.$terminaltouse, GETPOST('TAKEPOS_HEADER'.$terminaltouse, 'restricthtml'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('TAKEPOS_FOOTER'.$terminaltouse)) {
		$res = dolibarr_set_const($db, 'TAKEPOS_FOOTER'.$terminaltouse, GETPOST('TAKEPOS_FOOTER'.$terminaltouse, 'restricthtml'), 'chaine', 0, '', $conf->entity);
	}

	dol_syslog("admin/terminal.php: level ".GETPOST('level', 'alpha'));

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
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"), '', '', 0, 0, '', '', '', 'mod-takepos page-admin_terminal');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
$head = takepos_admin_prepare_head();
print dol_get_fiche_head($head, 'terminal'.$terminal, 'TakePOS', -1, 'cash-register');
print '<br>';


// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'?terminal='.(empty($terminal) ? 1 : $terminal).'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set">';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td></td>';
print "</tr>\n";

print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("TerminalNameDesc").'</td>';
print '<td>';
print '<input type="text" name="terminalname'.$terminal.'" value="'.getDolGlobalString("TAKEPOS_TERMINAL_NAME_".$terminal, $langs->trans("TerminalName", $terminal)).'" >';
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("ForbidSalesToTheDefaultCustomer").'</td>';
print '<td>';
print ajax_constantonoff("TAKEPOS_FORBID_SALES_TO_DEFAULT_CUSTOMER", array(), $conf->entity, 0, 0, 1, 0);
print '</td></tr>';

if (!getDolGlobalString('TAKEPOS_FORBID_SALES_TO_DEFAULT_CUSTOMER')) {
	print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
	print '<td>';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	$filter = '((s.client:IN:1,2,3) AND (s.status:=:1))';
	print $form->select_company(getDolGlobalInt('CASHDESK_ID_THIRDPARTY'.$terminaltouse), 'socid', $filter, 1, 0, 0, array(), 0, 'maxwidth500 widthcentpercentminusx');
	print '</td></tr>';
}

$atleastonefound = 0;
if (isModEnabled("bank")) {
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSell").'</td>';
	print '<td>';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	print $form->select_comptes(getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse), 'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 0, "courant=2", 1, '', 0, 'maxwidth500 widthcentpercentminusxx', 1);
	print ' <a href="'.DOL_URL_ROOT.'/compta/bank/card.php?action=create&type=2&backtopage='.urlencode($_SERVER["PHP_SELF"].'?terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewBankAccount").'"></span></a>';
	if (getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse)) {
		$atleastonefound++;
	}
	print '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCheque").'</td>';
	print '<td>';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	print $form->select_comptes(getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse), 'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 0, "courant=1", 1, '', 0, 'maxwidth500 widthcentpercentminusxx', 1);
	print ' <a href="'.DOL_URL_ROOT.'/compta/bank/card.php?action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewBankAccount").'"></span></a>';
	if (getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse)) {
		$atleastonefound++;
	}
	print '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCB").'</td>';
	print '<td>';
	print img_picto('', 'bank_account', 'class="pictofixedwidth"');
	print $form->select_comptes(getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse), 'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 0, "courant=1", 1, '', 0, 'maxwidth500 widthcentpercentminusxx', 1);
	print ' <a href="'.DOL_URL_ROOT.'/compta/bank/card.php?action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewBankAccount").'"></span></a>';
	if (getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse)) {
		$atleastonefound++;
	}
	print '</td></tr>';

	if (isModEnabled('stripe') && getDolGlobalString('STRIPE_CARD_PRESENT')) {
		print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForStripeTerminal").'</td>'; // Force Stripe Terminal
		print '<td>';
		$service = 'StripeTest';
		$servicestatus = 0;
		if (getDolGlobalString('STRIPE_LIVE') && !GETPOST('forcesandbox', 'alpha')) {
			$service = 'StripeLive';
			$servicestatus = 1;
		}
		global $stripearrayofkeysbyenv;
		$site_account = $stripearrayofkeysbyenv[$servicestatus]['secret_key'];
		\Stripe\Stripe::setApiKey($site_account);
		if (isModEnabled('stripe') && (!getDolGlobalString('STRIPE_LIVE') || GETPOST('forcesandbox', 'alpha'))) {
			$service = 'StripeTest';
			$servicestatus = '0';
			dol_htmloutput_mesg($langs->trans('YouAreCurrentlyInSandboxMode', 'Stripe'), [], 'warning');
		} else {
			$service = 'StripeLive';
			$servicestatus = '1';
		}
		$stripe = new Stripe($db);
		$stripeacc = $stripe->getStripeAccount($service);
		if ($stripeacc) {
			$readers = \Stripe\Terminal\Reader::all('', array("location" => getDolGlobalString('STRIPE_LOCATION'), "stripe_account" => $stripeacc));
		} else {
			$readers = \Stripe\Terminal\Reader::all('', array("location" => getDolGlobalString('STRIPE_LOCATION')));
		}

		$reader = array();
		$reader[""] = $langs->trans("NoReader");
		foreach ($readers as $tmpreader) {
			$reader[$tmpreader->id] = $tmpreader->label.' ('.$tmpreader->status.')';
		}
		print $form->selectarray('CASHDESK_ID_BANKACCOUNT_STRIPETERMINAL'.$terminaltouse, $reader, getDolGlobalString('CASHDESK_ID_BANKACCOUNT_STRIPETERMINAL'.$terminaltouse));
		print '</td></tr>';
	}

	if (getDolGlobalInt('TAKEPOS_ENABLE_SUMUP')) {
		print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSumup").'</td>';
		print '<td>';
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		print $form->select_comptes(getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse), 'CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse, 0, "courant=1", 1, '', 0, 'maxwidth500 widthcentpercentminusxx', 1);
		print ' <a href="'.DOL_URL_ROOT.'/compta/bank/card.php?action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewBankAccount").'"></span></a>';
		if (getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse)) {
			$atleastonefound++;
		}
		print '</td></tr>';
	}

	foreach ($paiements as $modep) {
		if (in_array($modep->code, array('LIQ', 'CB', 'CHQ'))) {
			continue; // Already managed before
		}
		$name = "CASHDESK_ID_BANKACCOUNT_".$modep->code.$terminaltouse;
		print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountFor").' '.$langs->trans($modep->label).'</td>';
		print '<td>';
		if (getDolGlobalString($name)) {
			$atleastonefound++;
		}
		$cour = preg_match('/^LIQ.*/', $modep->code) ? 2 : 1;
		print img_picto('', 'bank_account', 'class="pictofixedwidth"');
		print $form->select_comptes(getDolGlobalInt($name), $name, 0, "courant=".$cour, 1, '', 0, 'maxwidth500 widthcentpercentminusxx', 1);
		print ' <a href="'.DOL_URL_ROOT.'/compta/bank/card.php?action=create&type=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("NewBankAccount").'"></span></a>';
		print '</td></tr>';
	}
}

if (isModEnabled('stock')) {
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("CashDeskDoNotDecreaseStock"), $langs->trans("CashDeskDoNotDecreaseStockHelp"));
	print '</td>'; // Force warehouse (this is not a default value)
	print '<td>';
	//print $form->selectyesno('CASHDESK_NO_DECREASE_STOCK'.$terminal, getDolGlobalInt('CASHDESK_NO_DECREASE_STOCK'.$terminal), 1);
	print ajax_constantonoff('CASHDESK_NO_DECREASE_STOCK'.$terminal, array(), $conf->entity, 0, 0, 1, 0);
	print '</td></tr>';


	$disabled = getDolGlobalString('CASHDESK_NO_DECREASE_STOCK'.$terminal);


	print '<tr class="oddeven"><td>';
	if (!$disabled) {
		print '<span class="fieldrequired">';
	}
	print $langs->trans("CashDeskIdWareHouse");
	if (!$disabled) {
		print '</span>';
	}
	if (!getDolGlobalString('CASHDESK_ID_WAREHOUSE'.$terminal)) {
		print img_warning($langs->trans("DisableStockChange").' - '.$langs->trans("NoWarehouseDefinedForTerminal"));
	}
	print '</td>'; // Force warehouse (this is not a default value)
	print '<td class="minwidth300">';
	if (!$disabled) {
		print img_picto('', 'stock', 'class="pictofixedwidth"');
		print $formproduct->selectWarehouses(getDolGlobalString('CASHDESK_ID_WAREHOUSE'.$terminal), 'CASHDESK_ID_WAREHOUSE'.$terminal, '', 1, $disabled, 0, '', 0, 0, array(), 'maxwidth500 widthcentpercentminusxx');
		print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?&terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle"></span></a>';
	} else {
		print '<span class="opacitymedium">'.$langs->trans("StockDecreaseForPointOfSaleDisabled").'</span>';
	}
	print '</td></tr>';

	// Deprecated: CASHDESK_FORCE_DECREASE_STOCK is now always false. No more required/used.
	if (isModEnabled('productbatch') && getDolGlobalString('CASHDESK_FORCE_DECREASE_STOCK') && !getDolGlobalString('CASHDESK_NO_DECREASE_STOCK'.$terminal)) {
		print '<tr class="oddeven"><td>'.$langs->trans('CashDeskForceDecreaseStockLabel').'</td>';
		print '<td>';
		print '<span class="opacitymedium">'.$langs->trans('CashDeskForceDecreaseStockDesc').'</span>';
		print '</td></tr>';
	}
}

if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	$formproject = new FormProjets($db);
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskDefaultProject").'</td><td>';
	print img_picto('', 'project', 'class="pictofixedwidth"');
	// select_projects($socid = -1, $selected = '', $htmlname = 'projectid', $maxlength = 16, $option_only = 0, $show_empty = 1, $discard_closed = 0, $forcefocus = 0, $disabled = 0, $mode = 0, $filterkey = '', $nooutput = 0, $forceaddid = 0, $morecss = '', $htmlid = '', $morefilter = '')
	$projectid = getDolGlobalInt('CASHDESK_ID_PROJECT'.$terminaltouse);
	print $formproject->select_projects(-1, $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 1, 'maxwidth500 widthcentpercentminusxx');
	print '</td></tr>';
}

if (isModEnabled('receiptprinter')) {
	// Select printer to use with terminal
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	$printer = new dolReceiptPrinter($db);

	$printer->listprinters();
	$printers = array();
	foreach ($printer->listprinters as $key => $value) {
		$printers[$value['rowid']] = $value['name'];
	}
	print '<tr class="oddeven"><td>'.$langs->trans("MainPrinterToUse");
	print ' <span class="opacitymedium">('.$langs->trans("MainPrinterToUseMore").')</span>';
	print '</td>';
	print '<td>';
	print $form->selectarray('TAKEPOS_PRINTER_TO_USE'.$terminal, $printers, getDolGlobalInt('TAKEPOS_PRINTER_TO_USE'.$terminal), 1);
	print '</td></tr>';
	if (getDolGlobalString('TAKEPOS_BAR_RESTAURANT') && getDolGlobalInt('TAKEPOS_ORDER_PRINTERS')) {
		print '<tr class="oddeven"><td>'.$langs->trans("OrderPrinterToUse").' - '.$langs->trans("Printer").' 1</td>';
		print '<td>';
		print $form->selectarray('TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminal, $printers, getDolGlobalInt('TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminal), 1);
		print '</td></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans("OrderPrinterToUse").' - '.$langs->trans("Printer").' 2</td>';
		print '<td>';
		print $form->selectarray('TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminal, $printers, getDolGlobalInt('TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminal), 1);
		print '</td></tr>';
		print '<tr class="oddeven"><td>'.$langs->trans("OrderPrinterToUse").' - '.$langs->trans("Printer").' 3</td>';
		print '<td>';
		print $form->selectarray('TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminal, $printers, getDolGlobalInt('TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminal), 1);
		print '</td></tr>';
	}
}

if (isModEnabled('receiptprinter') || getDolGlobalString('TAKEPOS_PRINT_METHOD') == "receiptprinter" || getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector") {
	// Select printer to use with terminal
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	$printer = new dolReceiptPrinter($db);
	$printer->listPrintersTemplates();
	$templates = array();
	foreach ($printer->listprinterstemplates as $key => $value) {
		$templates[$value['rowid']] = $value['name'];
	}
	print '<tr class="oddeven"><td>'.$langs->trans("MainTemplateToUse");
	print ' <span class="opacitymedium">('.$langs->trans("MainTemplateToUseMore").')</span>';
	print ' (<a href="'.DOL_URL_ROOT.'/admin/receiptprinter.php?mode=template">'.$langs->trans("SetupReceiptTemplate").'</a>)</td>';
	print '<td>';
	print $form->selectarray('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminal, $templates, getDolGlobalInt('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminal), 1);
	print '</td></tr>';
	if (getDolGlobalInt('TAKEPOS_ORDER_PRINTERS')) {
		print '<tr class="oddeven"><td>'.$langs->trans("OrderTemplateToUse").'</td>';
		print '<td>';
		print $form->selectarray('TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminal, $templates, getDolGlobalInt('TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminal), 1);
		print '</td></tr>';
	}
}

print '<tr class="oddeven"><td>'.$langs->trans('CashDeskReaderKeyCodeForEnter').'</td>';
print '<td>';
print '<input type="text" class="width50" name="CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse.'" value="'.getDolGlobalString('CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse).'" />';
print '</td></tr>';

// Numbering module
if (getDolGlobalString('TAKEPOS_ADDON') == "terminal") {
	print '<tr class="oddeven"><td>';
	print $langs->trans("BillsNumberingModule");
	print '<td colspan="2">';
	$array = array(0 => $langs->trans("Default"));
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/facture/");
		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (!is_dir($dir.$file) || (substr($file, 0, 1) != '.' && substr($file, 0, 3) != 'CVS')) {
						$filebis = $file;
						$classname = preg_replace('/\.php$/', '', $file);
						// For compatibility
						if (!is_file($dir.$filebis)) {
							$filebis = $file."/".$file.".modules.php";
							$classname = "mod_facture_".$file;
						}
						// Check if there is a filter on country
						preg_match('/\-(.*)_(.*)$/', $classname, $reg);
						if (!empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) {
							continue;
						}

						$classname = preg_replace('/\-.*$/', '', $classname);
						if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php') {
							// Charging the numbering class
							require_once $dir.$filebis;

							$module = new $classname($db);
							'@phan-var-force ModeleNumRefFactures $module';

							// Show modules according to features level
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								continue;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								continue;
							}

							if ($module->isEnabled()) {
								$array[preg_replace('/\-.*$/', '', preg_replace('/\.php$/', '', $file))] = preg_replace('/\-.*$/', '', preg_replace('/mod_facture_/', '', preg_replace('/\.php$/', '', $file)));
							}
						}
					}
				}
				closedir($handle);
			}
		}
	}
	print $form->selectarray('TAKEPOS_ADDON'.$terminaltouse, $array, getDolGlobalString('TAKEPOS_ADDON'.$terminaltouse, '0'), 0);
	print "</td></tr>\n";
	print '</table>';
	print '</div>';
}

print '</table>';

print $form->buttonsSaveCancel("Save", '');

print '</div>';

// add free text on each terminal of cash desk
$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans('Translation');
$htmltext = '<i>'.$langs->trans('AvailableVariables').':<br>';
foreach ($substitutionarray as $key => $val) {
	$htmltext .= $key.'<br>';
}
$htmltext .= '</i>';

print '<br>';
print load_fiche_titre($langs->trans('FreeLegalTextOnInvoices'), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans('Value').'</td>';
print '</tr>';

// free text on header
print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans('Header'), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
print '</td>';
print '<td>';
$variablename = 'TAKEPOS_HEADER'.$terminaltouse;
if (!getDolGlobalInt('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td></tr>';

// free text on footer
print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans('Footer'), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
print '</td>';
print '<td>';
$variablename = 'TAKEPOS_FOOTER'.$terminaltouse;
if (!getDolGlobalInt('PDF_ALLOW_HTML_FOR_FREE_TEXT')) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.getDolGlobalString($variablename).'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, getDolGlobalString($variablename), '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td></tr>';

print '</table>';
print '</div>';

if ($atleastonefound == 0 && isModEnabled("bank")) {
	print info_admin($langs->trans("AtLeastOneDefaultBankAccountMandatory"), 0, 0, 'error');
}

print $form->buttonsSaveCancel("Save", '');

print "</form>\n";

print '<br>';

llxFooter();
$db->close();
