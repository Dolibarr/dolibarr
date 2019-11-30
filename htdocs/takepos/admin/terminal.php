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
 *	\file       htdocs/takepos/admin/terminal.php
 *	\ingroup    takepos
 *	\brief      Setup page for TakePos module
 */

require '../../main.inc.php';	// Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/takepos.lib.php";

$terminal=GETPOST('terminal', 'int');
// If socid provided by ajax company selector
if (! empty($_REQUEST['CASHDESK_ID_THIRDPARTY'.$terminal.'_id']))
{
	$_GET['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'.$terminal] = GETPOST('CASHDESK_ID_THIRDPARTY'.$terminal.'_id', 'alpha');
}

// Security check
if (!$user->admin) accessforbidden();

$langs->loadLangs(array("admin", "cashdesk"));

global $db;

$sql = "SELECT code, libelle FROM ".MAIN_DB_PREFIX."c_paiement";
$sql.= " WHERE entity IN (".getEntity('c_paiement').")";
$sql.= " AND active = 1";
$sql.= " ORDER BY libelle";
$resql = $db->query($sql);
$paiements = array();
if($resql){
	while ($obj = $db->fetch_object($resql)){
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
	if (GETPOST('socid', 'int') < 0) $_POST["socid"]='';

	$res = dolibarr_set_const($db, "CASHDESK_ID_THIRDPARTY".$terminaltouse, (GETPOST('socid', 'int') > 0 ? GETPOST('socid', 'int') : ''), 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CASH".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CHEQUE".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "CASHDESK_ID_BANKACCOUNT_CB".$terminaltouse, (GETPOST('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
    foreach($paiements as $modep) {
        if (in_array($modep->code, array('LIQ', 'CB', 'CHQ'))) continue;
        $name="CASHDESK_ID_BANKACCOUNT_".$modep->code.$terminaltouse;
		$res = dolibarr_set_const($db, $name, (GETPOST($name, 'alpha') > 0 ? GETPOST($name, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
    }
    $res = dolibarr_set_const($db, "CASHDESK_ID_WAREHOUSE".$terminaltouse, (GETPOST('CASHDESK_ID_WAREHOUSE'.$terminaltouse, 'alpha') > 0 ? GETPOST('CASHDESK_ID_WAREHOUSE'.$terminaltouse, 'alpha') : ''), 'chaine', 0, '', $conf->entity);
    $res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK".$terminaltouse, GETPOST('CASHDESK_NO_DECREASE_STOCK'.$terminaltouse, 'alpha'), 'chaine', 0, '', $conf->entity);

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
$head = takepos_prepare_head();
dol_fiche_head($head, 'terminal'.$terminal, 'TakePOS', -1);
print '<br>';


// Mode
print '<form action="'.$_SERVER["PHP_SELF"].'?terminal='.(empty($terminal)?1:$terminal).'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td class="fieldrequired">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td>';
print $form->select_company($conf->global->{'CASHDESK_ID_THIRDPARTY'.$terminaltouse}, 'socid', '(s.client IN (1, 3) AND s.status = 1)', 1, 0, 0, array(), 0);
print '</td></tr>';

$atleastonefound = 0;
if (! empty($conf->banque->enabled))
{
    print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSell").'</td>';
	print '<td>';
	$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse, 0, "courant=2", 1);
	if (! empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_CASH'.$terminaltouse})) $atleastonefound++;
	print '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCheque").'</td>';
	print '<td>';
	$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse, 0, "courant=1", 1);
	if (! empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_CHEQUE'.$terminaltouse})) $atleastonefound++;
	print '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCB").'</td>';
	print '<td>';
	$form->select_comptes($conf->global->{'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse}, 'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse, 0, "courant=1", 1);
	if (! empty($conf->global->{'CASHDESK_ID_BANKACCOUNT_CB'.$terminaltouse})) $atleastonefound++;
	print '</td></tr>';

	foreach($paiements as $modep) {
        if (in_array($modep->code, array('LIQ', 'CB', 'CHQ'))) continue;	// Already managed before
        $name="CASHDESK_ID_BANKACCOUNT_".$modep->code.$terminaltouse;
		print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountFor").' '.$langs->trans($modep->libelle).'</td>';
		print '<td>';
		if (! empty($conf->global->$name)) $atleastonefound++;
		$cour=preg_match('/^LIQ.*/', $modep->code)?2:1;
		$form->select_comptes($conf->global->$name, $name, 0, "courant=".$cour, 1);
		print '</td></tr>';
	}
}

if (! empty($conf->stock->enabled))
{

	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskDoNotDecreaseStock").'</td>';	// Force warehouse (this is not a default value)
	print '<td>';
	if (empty($conf->productbatch->enabled)) {
	   print $form->selectyesno('CASHDESK_NO_DECREASE_STOCK'.$terminal, $conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal}, 1);
	}
	else
	{
	    if (!$conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal}) {
	       $res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK".$terminal, 1, 'chaine', 0, '', $conf->entity);
	    }
	    print $langs->trans("Yes").'<br>';
	    print '<span class="opacitymedium">'.$langs->trans('StockDecreaseForPointOfSaleDisabledbyBatch').'</span>';
	}
	print '</td></tr>';

	$disabled=$conf->global->{'CASHDESK_NO_DECREASE_STOCK'.$terminal};


	print '<tr class="oddeven"><td>'.$langs->trans("CashDeskIdWareHouse").'</td>';	// Force warehouse (this is not a default value)
	print '<td>';
	if (! $disabled)
	{
		print $formproduct->selectWarehouses($conf->global->{'CASHDESK_ID_WAREHOUSE'.$terminal}, 'CASHDESK_ID_WAREHOUSE'.$terminal, '', 1, $disabled);
		print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">('.$langs->trans("Create").')</a>';
	}
	else
	{
		print '<span class="opacitymedium">'.$langs->trans("StockDecreaseForPointOfSaleDisabled").'</span>';
	}
	print '</td></tr>';
}

print '</table>';

if ($atleastonefound == 0 && ! empty($conf->banque->enabled))
{
	print info_admin($langs->trans("AtLeastOneDefaultBankAccountMandatory"), 0, 0, 'error');
}

print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";

print '<br>';

llxFooter();
$db->close();
