<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015-2018 Alexandre Spangaro   <aspangaro@zendsi.com>
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
 *     \file       htdocs/admin/taxes.php
 *     \ingroup    tax
 *     \brief      Page de configuration du module tax
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

$langs->load('admin');
$langs->load('objects');
$langs->load("companies");
$langs->load("products");

if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');

// Other parameters
$list = array (
		'ACCOUNTING_VAT_PAY_ACCOUNT'
);


/*
 * Actions
 */

// 0=normal, 1=option vat for services is on debit

// TAX_MODE=0 (most cases):
//              Buy                     Sell
// Product      On delivery             On delivery
// Service      On payment              On payment

// TAX_MODE=1 (option):
//              Buy                     Sell
// Product      On delivery             On delivery
// Service      On invoice              On invoice

$tax_mode = empty($conf->global->TAX_MODE)?0:$conf->global->TAX_MODE;

if ($action == 'update') {
	$error = 0;

	// Tax mode
	$tax_mode = GETPOST('tax_mode','alpha');

	$db->begin();

	$res = dolibarr_set_const($db, 'TAX_MODE', $tax_mode,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;

	switch ($tax_mode)
	{
		case 0:
			$value = 'payment';
			break;
		case 1:
			$value = 'invoice';
			break;
	}

	$res = dolibarr_set_const($db, 'TAX_MODE_SELL_PRODUCT', 'invoice','chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'TAX_MODE_BUY_PRODUCT', 'invoice','chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'TAX_MODE_SELL_SERVICE', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;
	$res = dolibarr_set_const($db, 'TAX_MODE_BUY_SERVICE', $value,'chaine',0,'',$conf->entity);
	if (! $res > 0) $error++;

	dolibarr_set_const($db, "MAIN_INFO_TVAINTRA", GETPOST("tva",'alpha'),'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_INFO_VAT_RETURN", GETPOST("MAIN_INFO_VAT_RETURN",'alpha'),'chaine',0,'',$conf->entity);

	// Others options
	foreach ($list as $constname) {
		$constvalue = GETPOST($constname, 'alpha');

		if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	}

	if (! $error) {
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

llxHeader('', $langs->trans("TaxSetup"));

$form=new Form($db);
if (! empty($conf->accounting->enabled)) $formaccounting = new FormAccounting($db);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('TaxSetup'),$linkback,'title_setup');

//dol_fiche_head(null, '', '', -1);

if (empty($mysoc->tva_assuj))
{
	print $langs->trans("YourCompanyDoesNotUseVAT").'<br>';
}
else
{
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td><label for="intra_vat">'.$langs->trans("VATIntra").'</label></td><td>';
	print '<input name="tva" id="intra_vat" class="minwidth200" value="' . (! empty($conf->global->MAIN_INFO_TVAINTRA) ? $conf->global->MAIN_INFO_TVAINTRA : '') . '">';
	print '</td></tr>';

	print '<tr class="oddeven"><td><label for="activate_MAIN_INFO_VAT_RETURN">'.$langs->trans("VATReturn").'</label></td>';
	if (! $conf->use_javascript_ajax)
	{
		print '<td class="nowrap" align="right">';
		print $langs->trans("NotAvailableWhenAjaxDisabled");
		print "</td>";
	}
	else
	{
		print '<td width="120">';
		$listval=array('0'=>$langs->trans(""),
		'1'=>$langs->trans("Monthly"),
		'2'=>$langs->trans("Quarterly"),
		'3'=>$langs->trans("Annual"),
		);
		print $form->selectarray("MAIN_INFO_VAT_RETURN", $listval, $conf->global->MAIN_INFO_VAT_RETURN);
		print "</td>";
	}
	print '</tr>';

	print '</table>';

	print '<br>';

	print '<table class="noborder" width="100%">';

	// Cas des parametres TAX_MODE_SELL/BUY_SERVICE/PRODUCT
	print '<tr class="liste_titre">';
	print '<td class="titlefield">'.$langs->trans('OptionVatMode').'</td><td>'.$langs->trans('Description').'</td>';
	print "</tr>\n";
	print '<tr class="oddeven"><td width="200"><input type="radio" name="tax_mode" value="0"'.($tax_mode != 1 ? ' checked' : '').'> '.$langs->trans('OptionVATDefault').'</td>';
	print '<td>'.nl2br($langs->trans('OptionVatDefaultDesc'));
	print "</td></tr>\n";
	print '<tr class="oddeven"><td width="200"><input type="radio" name="tax_mode" value="1"'.($tax_mode == 1 ? ' checked' : '').'> '.$langs->trans('OptionVATDebitOption').'</td>';
	print '<td>'.nl2br($langs->trans('OptionVatDebitOptionDesc'))."</td></tr>\n";

	print "</table>\n";

	print '<br>';
	print ' -> '.$langs->trans("SummaryOfVatExigibilityUsedByDefault");
	//print ' ('.$langs->trans("CanBeChangedWhenMakingInvoice").')';


	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">&nbsp;</td><td>'.$langs->trans("Buy").'</td><td>'.$langs->trans("Sell").'</td></tr>';

	// Products
	print '<tr class="oddeven"><td>'.$langs->trans("Product").'</td>';
	print '<td>';
	print $langs->trans("OnDelivery");
	print ' ('.$langs->trans("SupposedToBeInvoiceDate").')';
	print '</td>';
	print '<td>';
	print $langs->trans("OnDelivery");
	print ' ('.$langs->trans("SupposedToBeInvoiceDate").')';
	print '</td></tr>';

	// Services
	print '<tr class="oddeven"><td>'.$langs->trans("Services").'</td>';
	print '<td>';
	if ($tax_mode == 0)
	{
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	}
	if ($tax_mode == 1)
	{
		print $langs->trans("OnInvoice");
		print ' ('.$langs->trans("InvoiceDateUsed").')';
	}
	print '</td>';
	print '<td>';
	if ($tax_mode == 0)
	{
		print $langs->trans("OnPayment");
		print ' ('.$langs->trans("SupposedToBePaymentDate").')';
	}
	if ($tax_mode == 1)
	{
		print $langs->trans("OnInvoice");
		print ' ('.$langs->trans("InvoiceDateUsed").')';
	}
	print '</td></tr>';

	print '</table>';
}

print "<br>\n";



/*
 *  Others params
 */

print load_fiche_titre($langs->trans("OtherOptions"),'','');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td class="titlefield">' . $langs->trans('Parameters') . '</td>';
print '<td><td>';
print "</tr>\n";

foreach ($list as $key)
{
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td><label for="'.$key.'">'.$label.'</label></td>';

	// Value
	print '<td>';
	if (! empty($conf->accounting->enabled))
	{
		print $formaccounting->select_account($conf->global->$key, $key, 1, '', 1, 1);
	}
	else
	{
		print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	}
	print '</td></tr>';
}

print '</table>';


//dol_fiche_end();


print '<div class="center">';
print '<input type="submit" class="button" value="' . $langs->trans("Modify") . '" name="button">';
print '</div>';

print '</form>';

llxFooter();
$db->close();
