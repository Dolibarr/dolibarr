<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2013  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015-2022  Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2023  		Joachim Kueter			<git-jk@bloxera.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *     \file       htdocs/admin/taxes.php
 *     \ingroup    tax
 *     \brief      Page to setup module tax
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('admin', 'objects', 'companies', 'products'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');



/*
 * Actions
 */

// 0=normal, 1=option vat for services is on debit, 2=option vat for product and service on credit

// TAX_MODE=0 (most cases in FR):
//              Buy                     Sell
// Product      On delivery             On delivery
// Service      On payment              On payment

// TAX_MODE=1 (option, VAT is due at invoice date):
//              Buy                     Sell
// Product      On delivery/invoice     On delivery/invoice
// Service      On invoice              On invoice

// TAX_MODE=2 (option, VAT is due on payment date):
//              Buy                     Sell
// Product      On payment              On payment
// Service      On payment              On payment

$tax_mode = getDolGlobalInt('TAX_MODE');

if ($action == 'update') {
	$error = 0;

	// Tax mode
	$tax_mode = GETPOSTINT('tax_mode');

	$db->begin();

	$res = dolibarr_set_const($db, 'TAX_MODE', $tax_mode, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	switch ($tax_mode) {
		case 0:
			$valuesellproduct = 'invoice';
			$valuebuyproduct = 'invoice';
			$valuesellservice = 'payment';
			$valuebuyservice = 'payment';
			break;
		case 1:
			$valuesellproduct = 'invoice';
			$valuebuyproduct = 'invoice';
			$valuesellservice = 'invoice';
			$valuebuyservice = 'invoice';
			break;
		case 2:
			$valuesellproduct = 'payment';
			$valuebuyproduct = 'payment';
			$valuesellservice = 'payment';
			$valuebuyservice = 'payment';
			break;
	}

	$res = dolibarr_set_const($db, 'TAX_MODE_SELL_PRODUCT', $valuesellproduct, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	$res = dolibarr_set_const($db, 'TAX_MODE_BUY_PRODUCT', $valuebuyproduct, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	$res = dolibarr_set_const($db, 'TAX_MODE_SELL_SERVICE', $valuesellservice, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}
	$res = dolibarr_set_const($db, 'TAX_MODE_BUY_SERVICE', $valuebuyservice, 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	dolibarr_set_const($db, "MAIN_INFO_TVAINTRA", GETPOST("tva", 'alpha'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_INFO_VAT_RETURN", GETPOST("MAIN_INFO_VAT_RETURN", 'alpha'), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_INFO_TVA_DAY_DEADLINE_SUBMISSION", GETPOSTINT("deadline_day_vat"), 'chaine', 0, '', $conf->entity);

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif (preg_match('/^(set|del)_?([A-Z_]+)$/', $action, $reg)) {
	// Set boolean (on/off) constants
	if (!dolibarr_set_const($db, $reg[2], ($reg[1] === 'set' ? '1' : '0'), 'chaine', 0, '', $conf->entity) > 0) {
		dol_print_error($db);
	}
}


/*
 * View
 */

llxHeader('', $langs->trans("TaxSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-taxes');

$form = new Form($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('TaxSetup'), $linkback, 'title_setup');

//print dol_get_fiche_head(null, '', '', -1);

if (empty($mysoc->tva_assuj)) {
	print $langs->trans("YourCompanyDoesNotUseVAT").'<br>';
} else {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td><label for="intra_vat">'.$langs->trans("VATIntra").'</label></td><td>';
	print '<input name="tva" id="intra_vat" class="minwidth200" value="'.getDolGlobalString('MAIN_INFO_TVAINTRA').'">';
	print '</td></tr>';

	print '<tr class="oddeven"><td><label for="activate_MAIN_INFO_VAT_RETURN">'.$langs->trans("VATPaymentFrequency").'</label></td>';
	if (!$conf->use_javascript_ajax) {
		print '<td class="nowrap right">';
		print $langs->trans("NotAvailableWhenAjaxDisabled");
		print "</td>";
	} else {
		print '<td width="120">';
		$listval = array(
			'0'=>$langs->trans(""),
			'1'=>$langs->trans("Monthly"),
			'2'=>$langs->trans("Quarterly"),
			'3'=>$langs->trans("Annual"),
		);
		print $form->selectarray("MAIN_INFO_VAT_RETURN", $listval, getDolGlobalString('MAIN_INFO_VAT_RETURN'));
		print "</td>";
	}
	print '</tr>';

	print '<tr class="oddeven"><td><label for="deadline_day_vat">'.$langs->trans("DeadlineDayVATSubmission").'</label></td><td>';
	print '<input placeholder="'.$langs->trans("Example").':21" name="deadline_day_vat" id="deadline_day_vat" class="minwidth200" value="'.(getDolGlobalString('MAIN_INFO_TVA_DAY_DEADLINE_SUBMISSION') ? $conf->global->MAIN_INFO_TVA_DAY_DEADLINE_SUBMISSION : '').'">';
	print '</td></tr>';

	$key = 'CREATE_NEW_VAT_WITHOUT_AUTO_PAYMENT';
	print '<tr><td>'.$langs->trans($key).'</td><td>'.ajax_constantonoff($key).'</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';


	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';

	// Case for the parameters TAX_MODE_SELL/BUY_SERVICE/PRODUCT
	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans('OptionVatMode').'</td><td>'.$langs->trans('Description').'</td>';
	print "</tr>\n";
	// Standard
	print '<tr class="oddeven"><td><input type="radio" id="tax_mode0" name="tax_mode" value="0"'.(empty($tax_mode) ? ' checked' : '').'> <label for="tax_mode0">'.$langs->trans('OptionVATDefault').'</label></td>';
	print '<td>'.nl2br($langs->trans('OptionVatDefaultDesc'));
	print "</td></tr>\n";
	// On debit for services
	print '<tr class="oddeven"><td><input type="radio" id="tax_mode1" name="tax_mode" value="1"'.($tax_mode == 1 ? ' checked' : '').'> <label for="tax_mode1">'.$langs->trans('OptionVATDebitOption').'</label></td>';
	print '<td>'.nl2br($langs->trans('OptionVatDebitOptionDesc'))."</td></tr>\n";
	// On payment for both products and services
	if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1) {
		print '<tr class="oddeven"><td><input type="radio" id="tax_mode2"  name="tax_mode" value="2"'.($tax_mode == 2 ? ' checked' : '').'> <label for="tax_mode2">'.$langs->trans('OptionPaymentForProductAndServices').'</label></td>';
		print '<td>'.nl2br($langs->trans('OptionPaymentForProductAndServicesDesc'))."</td></tr>\n";
	}
	print "</table>\n";
	print '</div>';


	print '<br>';
	print load_fiche_titre('', '', '', 0, '', '', '-> '.$langs->trans("SummaryOfVatExigibilityUsedByDefault"));
	//print ' ('.$langs->trans("CanBeChangedWhenMakingInvoice").')';


	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="oddeven"><td class="titlefield">&nbsp;</td><td>'.$langs->trans("Buy").'</td><td>'.$langs->trans("Sell").'</td></tr>';

	// Products
	print '<tr class="oddeven"><td>'.$langs->trans("Product").'</td>';
	print '<td>';
	if (getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'payment') {
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	} else {
		print $langs->trans("OnDelivery");
		print ' ('.$langs->trans("SupposedToBeInvoiceDate").')';
	}
	print '</td>';
	print '<td>';
	if (getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'payment') {
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	} else {
		print $langs->trans("OnDelivery");
		print ' ('.$langs->trans("SupposedToBeInvoiceDate").')';
	}
	print '</td></tr>';

	// Services
	print '<tr class="oddeven"><td>'.$langs->trans("Services").'</td>';
	print '<td>';
	if (getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'payment') {
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	} else {
		print $langs->trans("OnInvoice");
		print ' ('.$langs->trans("InvoiceDateUsed").')';
	}
	print '</td>';
	print '<td>';
	if (getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'payment') {
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	} else {
		print $langs->trans("OnInvoice");
		print ' ('.$langs->trans("InvoiceDateUsed").')';
	}
	print '</td></tr>';

	print '</table>';
	print '</div>';
}

print "<br>\n";


print '<div class="center">';
print '<input type="submit" class="button button-edit" name="Button"value="'.$langs->trans("Modify").'">';
print '<br><br>';
print '</div>';

print '</form>';


print '<br>';

if (isModEnabled('accounting')) {
	$langs->load("accountancy");
	print '<br><br><span class="opacitymedium">'.$langs->trans("AccountingAccountForSalesTaxAreDefinedInto", $langs->transnoentitiesnoconv("MenuAccountancy"), $langs->transnoentitiesnoconv("Setup")).'</span>';
}

// End of page
llxFooter();
$db->close();
