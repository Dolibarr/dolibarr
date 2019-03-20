<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/takepos/admin/setup.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

require '../../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// If socid provided by ajax company selector
if (! empty($_REQUEST['CASHDESK_ID_THIRDPARTY_id']))
{
	$_GET['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id', 'alpha');
}

// Security check
if (!$user->admin) accessforbidden();

$langs->loadLangs(array("admin", "cashdesk"));

/*
 * Actions
 */
if (GETPOST('action', 'alpha') == 'set')
{
	$db->begin();

	if (GETPOST('socid', 'int') < 0) $_POST["socid"]='';

	$res = dolibarr_set_const($db, "CASHDESK_ID_THIRDPARTY", (GETPOST('socid', 'int') > 0 ? GETPOST('socid', 'int') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CASH", (GETPOST('CASHDESK_ID_BANKACCOUNT_CASH', 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CASH', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CHEQUE", (GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE', 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CB", (GETPOST('CASHDESK_ID_BANKACCOUNT_CB', 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CB', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_WAREHOUSE", (GETPOST('CASHDESK_ID_WAREHOUSE', 'alpha') > 0 ? GETPOST('CASHDESK_ID_WAREHOUSE', 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK", GETPOST('CASHDESK_NO_DECREASE_STOCK', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_SERVICES", GETPOST('CASHDESK_SERVICES', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ROOT_CATEGORY_ID", GETPOST('TAKEPOS_ROOT_CATEGORY_ID', 'alpha'), 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, "TAKEPOSCONNECTOR", GETPOST('TAKEPOSCONNECTOR', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_BAR_RESTAURANT", GETPOST('TAKEPOS_BAR_RESTAURANT', 'alpha'), 'chaine', 0, '', $conf->entity);
    $res = dolibarr_set_const($db, "TAKEPOS_PRINT_SERVER", GETPOST('TAKEPOS_PRINT_SERVER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTERS", GETPOST('TAKEPOS_ORDER_PRINTERS', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_NOTES", GETPOST('TAKEPOS_ORDER_NOTES', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_HEADER", GETPOST('TAKEPOS_HEADER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_FOOTER", GETPOST('TAKEPOS_FOOTER', 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_NUMPAD", GETPOST('TAKEPOS_NUMPAD', 'alpha'), 'chaine', 0, '', $conf->entity);

	if ($conf->global->TAKEPOS_ORDER_NOTES==1)
	{
		$extrafields = new ExtraFields($db);
		$extrafields->addExtraField('order_notes', 'Order notes', 'varchar', 0, 255, 'facturedet', 0, 0, '', '', 0, '', 0, 1);
	}

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
	    setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

llxHeader('', $langs->trans("CashDeskSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup").' (TakePOS)', $linkback, 'title_setup');
print '<br>';


// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

if (! empty($conf->service->enabled))
{
	print '<tr class="oddeven"><td>';
	print $langs->trans("CashdeskShowServices");
	print '<td colspan="2">';
	print $form->selectyesno("CASHDESK_SERVICES", $conf->global->CASHDESK_SERVICES, 1);
	print "</td></tr>\n";
}

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("RootCategoryForProductsToSell"), $langs->trans("RootCategoryForProductsToSellDesc"));
print '<td colspan="2">';
print $form->select_all_categories(Categorie::TYPE_PRODUCT, $conf->global->TAKEPOS_ROOT_CATEGORY_ID, 'TAKEPOS_ROOT_CATEGORY_ID', 64, 0, 0);
print ajax_combobox('TAKEPOS_ROOT_CATEGORY_ID');
print "</td></tr>\n";

// Use Takepos printing
print '<tr class="oddeven"><td>';
print $langs->trans("DolibarrReceiptPrinter").' (<a href="http://en.takepos.com/connector">'.$langs->trans("TakeposConnectorNecesary").'</a>)';
print '<td colspan="2">';
print $form->selectyesno("TAKEPOSCONNECTOR", $conf->global->TAKEPOSCONNECTOR, 1);
print "</td></tr>\n";

if ($conf->global->TAKEPOSCONNECTOR){
	print '<tr class="oddeven value"><td>';
	print $langs->trans("IPAddress").' (<a href="http://en.takepos.com/connector">'.$langs->trans("TakeposConnectorNecesary").'</a>)';
	print '<td colspan="2">';
	print '<input type="text" size="20" id="TAKEPOS_PRINT_SERVER" name="TAKEPOS_PRINT_SERVER" value="'.$conf->global->TAKEPOS_PRINT_SERVER.'">';
	print '</td></tr>';
}

// Bar Restaurant mode
print '<tr class="oddeven"><td>';
print 'Bar Restaurant';
print '<td colspan="2">';
print $form->selectyesno("TAKEPOS_BAR_RESTAURANT", $conf->global->TAKEPOS_BAR_RESTAURANT, 1);
print "</td></tr>\n";

if ($conf->global->TAKEPOS_BAR_RESTAURANT && $conf->global->TAKEPOSCONNECTOR){
	print '<tr class="oddeven value"><td>';
	print $langs->trans("OrderPrinters").' (<a href="orderprinters.php?leftmenu=setup">'.$langs->trans("Setup").'</a>)';
	print '<td colspan="2">';
	print $form->selectyesno("TAKEPOS_ORDER_PRINTERS", $conf->global->TAKEPOS_ORDER_PRINTERS, 1);
	print '</td></tr>';

	print '<tr class="oddeven value"><td>';
	print $langs->trans("OrderNotes");
	print '<td colspan="2">';
	print $form->selectyesno("TAKEPOS_ORDER_NOTES", $conf->global->TAKEPOS_ORDER_NOTES, 1);
	print '</td></tr>';
}

// Payment numpad
print '<tr class="oddeven"><td>';
print $langs->trans("Paymentnumpad");
print '<td colspan="2">';
$array=array(0=>$langs->trans("Numberspad"), 1=>$langs->trans("BillsCoinsPad"));
print $form->selectarray('TAKEPOS_NUMPAD', $array, (empty($conf->global->TAKEPOS_NUMPAD)?'0':$conf->global->TAKEPOS_NUMPAD), 0);
print "</td></tr>\n";

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices")." - ".$langs->trans("Header"), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
print '</td><td>';
$variablename='TAKEPOS_HEADER';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
    print $doleditor->Create();
}
print "</td></tr>\n";

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("FreeLegalTextOnInvoices")." - ".$langs->trans("Footer"), $htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';
print '</td><td>';
$variablename='TAKEPOS_FOOTER';
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->$variablename.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor($variablename, $conf->global->$variablename, '', 80, 'dolibarr_notes');
    print $doleditor->Create();
}
print "</td></tr>\n";

print '</table>';

print '<br>';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Terminal").' 0</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td width=\"50%\">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td colspan="2">';
print $form->select_company($conf->global->CASHDESK_ID_THIRDPARTY, 'socid', 's.client in (1,3) AND s.status = 1', 1, 0, 0, array(), 0);
print '</td></tr>';
if (! empty($conf->banque->enabled))
{

	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSell").'</td>';
	print '<td colspan="2">';
	$form->select_comptes($conf->global->CASHDESK_ID_BANKACCOUNT_CASH, 'CASHDESK_ID_BANKACCOUNT_CASH', 0, "courant=2", 1);
	print '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCheque").'</td>';
	print '<td colspan="2">';
	$form->select_comptes($conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE, 'CASHDESK_ID_BANKACCOUNT_CHEQUE', 0, "courant=1", 1);
	print '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCB").'</td>';
	print '<td colspan="2">';
	$form->select_comptes($conf->global->CASHDESK_ID_BANKACCOUNT_CB, 'CASHDESK_ID_BANKACCOUNT_CB', 0, "courant=1", 1);
	print '</td></tr>';
}

if (! empty($conf->stock->enabled))
{

	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskDoNotDecreaseStock").'</td>';	// Force warehouse (this is not a default value)
	print '<td colspan="2">';
	if (empty($conf->productbatch->enabled)) {
	   print $form->selectyesno('CASHDESK_NO_DECREASE_STOCK', $conf->global->CASHDESK_NO_DECREASE_STOCK, 1);
	}
	else
	{
	    if (!$conf->global->CASHDESK_NO_DECREASE_STOCK) {
	       $res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK", 1, 'chaine', 0, '', $conf->entity);
	    }
	    print $langs->trans("Yes").'<br>';
	    print '<span class="opacitymedium">'.$langs->trans('StockDecreaseForPointOfSaleDisabledbyBatch').'</span>';
	}
	print '</td></tr>';

	$disabled=$conf->global->CASHDESK_NO_DECREASE_STOCK;


	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskIdWareHouse").'</td>';	// Force warehouse (this is not a default value)
	print '<td colspan="2">';
	if (! $disabled)
	{
		print $formproduct->selectWarehouses($conf->global->CASHDESK_ID_WAREHOUSE, 'CASHDESK_ID_WAREHOUSE', '', 1, $disabled);
		print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">('.$langs->trans("Create").')</a>';
	}
	else
	{
		print '<span class="opacitymedium">'.$langs->trans("StockDecreaseForPointOfSaleDisabled").'</span>';
	}
	print '</td></tr>';
}

print '</table>';
print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";


print '<br><br>';

// Marketplace
print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td colspan="2">'.$langs->trans("WebSiteDesc").'</td>';
print '<td>'.$langs->trans("URL").'</td>';
print '</tr>';

print "<tr class=\"oddeven\">\n";
$url='https://www.dolistore.com/45-pos';
    print '<td class="left"><a href="'.$url.'" target="_blank" rel="external"><img border="0" class="imgautosize imgmaxwidth180" src="'.DOL_URL_ROOT.'/theme/dolistore_logo.png"></a></td>';
print '<td>'.$langs->trans("DolistorePosCategory").'</td>';
print '<td><a href="'.$url.'" target="_blank" rel="external">'.$url.'</a></td>';
print '</tr>';

print "</table>\n";
print '<br>';

// Support
print "<table summary=\"list_of_modules\" class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td colspan="2">TakePOS Support</td>';
print '<td>'.$langs->trans("URL").'</td>';
print '</tr>';

print "<tr class=\"oddeven\">\n";
$url='http://www.takepos.com';
print '<td class="left"><a href="'.$url.'" target="_blank" rel="external"><img border="0" class="imgautosize imgmaxwidth180" src="../img/takepos.png"></a></td>';
print '<td>TakePOS original developers</td>';
print '<td><a href="'.$url.'" target="_blank" rel="external">'.$url.'</a></td>';
print '</tr>';

print "</table>\n";
print '<br>';

llxFooter();
$db->close();
