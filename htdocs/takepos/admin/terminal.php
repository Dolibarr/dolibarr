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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

$terminal = GETPOST('terminal', 'int');
// If socid provided by ajax company selector
if (!empty($_REQUEST['CASHDESK_ID_THIRDPARTY'.$terminal.'_id']))
{
	$_GET['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
}

// Security check
if (!$user->admin) accessforbidden();

$langs->loadLangs(array("admin", "cashdesk", "printing", "receiptprinter"));

global $db;

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

if (GETPOST('action', 'alpha') == 'set')
{
	$db->begin();
	if (GETPOST('socid', 'int') < 0) $_POST["socid"] = '';

	$res = dolibarr_set_const($db, "CASHDESK_ID_THIRDPARTY".$terminaltouse, (GETPOST('socid', 'int') > 0 ? GETPOST('socid', 'int') : ''), 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CASH".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CHEQUE".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CB".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	if (!empty($conf->global->TAKEPOS_ENABLE_SUMUP)) {
		$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_SUMUP".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	foreach ($paiements as $modep) {
		if (in_array($modep->code, array('LIQ', 'CB', 'CHQ'))) continue;
		$name = "CASHDESK_ID_BANKACCOUNT_".$modep->code.$terminaltouse;
		$res = dolibarr_set_const($db, $name, (GETPOST($name, 'alpha') > 0 ? GETPOST($name, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	}
	$res = dolibarr_set_const($db, "CASHDESK_ID_WAREHOUSE".$terminaltouse, (GETPOST('CASHDESK_ID_WAREHOUSE'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_WAREHOUSE'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK".$terminaltouse, GETPOST('CASHDESK_NO_DECREASE_STOCK'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_PRINTER_TO_USE".$terminaltouse, GETPOST('TAKEPOS_PRINTER_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTER1_TO_USE".$terminaltouse, GETPOST('TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTER2_TO_USE".$terminaltouse, GETPOST('TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_ORDER_PRINTER3_TO_USE".$terminaltouse, GETPOST('TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES".$terminaltouse, GETPOST('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS".$terminaltouse, GETPOST('TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, 'CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse, (GETPOST('CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse, 'int') > 0 ? GETPOST('CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse, 'int') : ''), 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, "TAKEPOS_ADDON".$terminaltouse, GETPOST('TAKEPOS_ADDON'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);

	// add free text on each terminal of cash desk
	$res = dolibarr_set_const($db, 'TAKEPOS_HEADER'.$terminaltouse, GETPOST('TAKEPOS_HEADER'.$terminaltouse, 'restricthtml'), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, 'TAKEPOS_FOOTER'.$terminaltouse, GETPOST('TAKEPOS_FOOTER'.$terminaltouse, 'restricthtml'), 'chaine', 0, '', $conf->entity);

	dol_syslog("admin/cashdesk: level ".GETPOST('level', 'alpha'));

	if (!($res > 0)) $error++;

 	if (!$error)
	{
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

llxHeader('', $langs->trans("CashDeskSetup"));

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
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td>';
print $form->select_company($conf->global->{'CASHDESK_ID_THIRDPARTY'.$terminaltouse}, 'socid', '(s.client IN (1, 3) AND s.status = 1)', 1, 0, 0, array(), 0);
print '</td></tr>';

$atleastonefound = 0;
if (!empty($conf->banque->enabled))
{
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSell").'</td>';
	print '<td>';
	$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 0, "courant=2", 1);
	if (!empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse})) $atleastonefound++;
	print '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCheque").'</td>';
	print '<td>';
	$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 0, "courant=1", 1);
	if (!empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse})) $atleastonefound++;
	print '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCB").'</td>';
	print '<td>';
	$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 0, "courant=1", 1);
	if (!empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse})) $atleastonefound++;
	print '</td></tr>';
	if ($conf->global->TAKEPOS_ENABLE_SUMUP) {
		print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSumup").'</td>';
		print '<td>';
		$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse, 0, "courant=1", 1);
		if (!empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_SUMUP'.$terminaltouse})) $atleastonefound++;
		print '</td></tr>';
	}

	foreach ($paiements as $modep) {
		if (in_array($modep->code, array('LIQ', 'CB', 'CHQ'))) continue; // Already managed before
		$name = "CASHDESK_ID_BANKACCOUNT_".$modep->code.$terminaltouse;
		print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountFor").' '.$langs->trans($modep->label).'</td>';
		print '<td>';
		if (!empty($conf->global->$name)) $atleastonefound++;
		$cour = preg_match('/^LIQ.*/', $modep->code) ? 2 : 1;
		$form->select_comptes($conf->global->$name, $name, 0, "courant=".$cour, 1);
		print '</td></tr>';
	}
}

if (!empty($conf->stock->enabled))
{
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskDoNotDecreaseStock").'</td>'; // Force warehouse (this is not a default value)
	print '<td>';
	if (empty($conf->productbatch->enabled) || !empty($conf->global->CASHDESK_FORCE_DECREASE_STOCK)) {
		print $form->selectyesno('CASHDESK_NO_DECREASE_STOCK'.$terminal, $conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal}, 1);
	} else {
		if (!$conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal}) {
			$res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK".$terminal, 1, 'chaine', 0, '', $conf->entity);
		}
		print $langs->trans("Yes").'<br>';
		print '<span class="opacitymedium">'.$langs->trans('StockDecreaseForPointOfSaleDisabledbyBatch').'</span>';
	}
	print '</td></tr>';

	$disabled = $conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal};


	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskIdWareHouse").'</td>'; // Force warehouse (this is not a default value)
	print '<td class="minwidth300">';
	if (!$disabled)
	{
		print $formproduct->selectWarehouses($conf->global->{'CASHDESK_ID_WAREHOUSE'.$terminal}, 'CASHDESK_ID_WAREHOUSE'.$terminal, '', 1, $disabled, 0, '', 0, 0, array(), 'maxwidth250');
		print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?&terminal='.$terminal).'"><span class="fa fa-plus-circle valignmiddle"></span></a>';
	} else {
		print '<span class="opacitymedium">'.$langs->trans("StockDecreaseForPointOfSaleDisabled").'</span>';
	}
	print '</td></tr>';

	if (!empty($conf->productbatch->enabled) && !empty($conf->global->CASHDESK_FORCE_DECREASE_STOCK) && !$conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal}) {
		print '<tr class="oddeven"><td>'.$langs->trans('CashDeskForceDecreaseStockLabel').'</td>';
		print '<td>';
		print '<span class="opacitymedium">'.$langs->trans('CashDeskForceDecreaseStockDesc').'</span>';
		print '</td></tr>';
	}
}

if ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter" || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") {
	// Select printer to use with terminal
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	$printer = new dolReceiptPrinter($db);
	if ($conf->global->TAKEPOS_PRINT_METHOD == "receiptprinter") {
		$printer->listprinters();
		$printers = array();
		foreach ($printer->listprinters as $key => $value) {
			$printers[$value['rowid']] = $value['name'];
		}
		print '<tr class="oddeven"><td>'.$langs->trans("MainPrinterToUse").'</td>';
		print '<td>';
		print $form->selectarray('TAKEPOS_PRINTER_TO_USE'.$terminal, $printers, (empty($conf->global->{'TAKEPOS_PRINTER_TO_USE'.$terminal}) ? '0' : $conf->global->{'TAKEPOS_PRINTER_TO_USE'.$terminal}), 1);
		print '</td></tr>';
		if ($conf->global->TAKEPOS_ORDER_PRINTERS) {
			print '<tr class="oddeven"><td>'.$langs->trans("OrderPrinterToUse").' - '.$langs->trans("Printer").' 1</td>';
			print '<td>';
			print $form->selectarray('TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminal, $printers, (empty($conf->global->{'TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminal}) ? '0' : $conf->global->{'TAKEPOS_ORDER_PRINTER1_TO_USE'.$terminal}), 1);
			print '</td></tr>';
			print '<tr class="oddeven"><td>'.$langs->trans("OrderPrinterToUse").' - '.$langs->trans("Printer").' 2</td>';
			print '<td>';
			print $form->selectarray('TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminal, $printers, (empty($conf->global->{'TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminal}) ? '0' : $conf->global->{'TAKEPOS_ORDER_PRINTER2_TO_USE'.$terminal}), 1);
			print '</td></tr>';
			print '<tr class="oddeven"><td>'.$langs->trans("OrderPrinterToUse").' - '.$langs->trans("Printer").' 3</td>';
			print '<td>';
			print $form->selectarray('TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminal, $printers, (empty($conf->global->{'TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminal}) ? '0' : $conf->global->{'TAKEPOS_ORDER_PRINTER3_TO_USE'.$terminal}), 1);
			print '</td></tr>';
		}
	}
	$printer->listPrintersTemplates();
	$templates = array();
	foreach ($printer->listprinterstemplates as $key => $value) {
		$templates[$value['rowid']] = $value['name'];
	}
	print '<tr class="oddeven"><td>'.$langs->trans("MainTemplateToUse").' (<a href="'.DOL_URL_ROOT.'/admin/receiptprinter.php?mode=template">'.$langs->trans("SetupReceiptTemplate").'</a>)</td>';
	print '<td>';
	print $form->selectarray('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminal, $templates, (empty($conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminal}) ? '0' : $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$terminal}), 1);
	print '</td></tr>';
	if ($conf->global->TAKEPOS_ORDER_PRINTERS) {
		print '<tr class="oddeven"><td>'.$langs->trans("OrderTemplateToUse").'</td>';
		print '<td>';
		print $form->selectarray('TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminal, $templates, (empty($conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminal}) ? '0' : $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_ORDERS'.$terminal}), 1);
		print '</td></tr>';
	}
}

print '<tr class="oddeven"><td>'.$langs->trans('CashDeskReaderKeyCodeForEnter').'</td>';
print '<td>';
print '<input type="text" name="CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse.'" value="'.$conf->global->{'CASHDESK_READER_KEYCODE_FOR_ENTER'.$terminaltouse}.'" />';
print '</td></tr>';

// Numbering module
if ($conf->global->TAKEPOS_ADDON == "terminal") {
	print '<tr class="oddeven"><td>';
	print $langs->trans("BillsNumberingModule");
	print '<td colspan="2">';
	$array = array(0=>$langs->trans("Default"));
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir)
	{
		$dir = dol_buildpath($reldir."core/modules/facture/");
		if (is_dir($dir))
		{
			$handle = opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle)) !== false)
				{
					if (!is_dir($dir.$file) || (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS'))
					{
						$filebis = $file;
						$classname = preg_replace('/\.php$/', '', $file);
						// For compatibility
						if (!is_file($dir.$filebis))
						{
							$filebis = $file."/".$file.".modules.php";
							$classname = "mod_facture_".$file;
						}
						// Check if there is a filter on country
						preg_match('/\-(.*)_(.*)$/', $classname, $reg);
						if (!empty($reg[2]) && $reg[2] != strtoupper($mysoc->country_code)) continue;

						$classname = preg_replace('/\-.*$/', '', $classname);
						if (!class_exists($classname) && is_readable($dir.$filebis) && (preg_match('/mod_/', $filebis) || preg_match('/mod_/', $classname)) && substr($filebis, dol_strlen($filebis) - 3, 3) == 'php')
						{
							// Charging the numbering class
							require_once $dir.$filebis;

							$module = new $classname($db);

							// Show modules according to features level
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

							if ($module->isEnabled())
							{
								$array[preg_replace('/\-.*$/', '', preg_replace('/\.php$/', '', $file))] = preg_replace('/\-.*$/', '', preg_replace('/mod_facture_/', '', preg_replace('/\.php$/', '', $file)));
							}
						}
					}
				}
				closedir($handle);
			}
		}
	}
	print $form->selectarray('TAKEPOS_ADDON'.$terminaltouse, $array, (empty($conf->global->{'TAKEPOS_ADDON'.$terminaltouse}) ? '0' : $conf->global->{'TAKEPOS_ADDON'.$terminaltouse}), 0);
	print "</td></tr>\n";
	print '</table>';
	print '</div>';
}

print '</table>';
print '</div>';

// add free text on each terminal of cash desk
$substitutionarray = pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__'] = $langs->trans('Translation');
$htmltext = '<i>'.$langs->trans('AvailableVariables').':<br>';
foreach ($substitutionarray as $key => $val)	$htmltext .= $key.'<br>';
$htmltext .= '</i>';

print '<br>';
print load_fiche_titre($langs->trans('FreeLegalTextOnInvoices'), '', '');

print '<div class="div-table-responsive">';
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
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->{$variablename}.'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, $conf->global->{$variablename}, '', 80, 'dolibarr_notes');
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
if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) {
	print '<textarea name="'.$variablename.'" class="flat" cols="120">'.$conf->global->{$variablename}.'</textarea>';
} else {
	include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor($variablename, $conf->global->{$variablename}, '', 80, 'dolibarr_notes');
	print $doleditor->Create();
}
print '</td></tr>';

print '</table>';
print '</div>';

if ($atleastonefound == 0 && !empty($conf->banque->enabled))
{
	print info_admin($langs->trans("AtLeastOneDefaultBankAccountMandatory"), 0, 0, 'error');
}

print '<br>';

print '<div class="center"><input type="submit" class="button button-save" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";

print '<br>';

llxFooter();
$db->close();
