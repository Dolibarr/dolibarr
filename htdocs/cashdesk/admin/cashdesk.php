<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012 Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/cashdesk/admin/cashdesk.php
 *	\ingroup    cashdesk
 *	\brief      Setup page for cashdesk module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// If socid provided by ajax company selector
if (! empty($_REQUEST['CASHDESK_ID_THIRDPARTY_id']))
{
	$_GET['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id','alpha');
	$_POST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id','alpha');
	$_REQUEST['CASHDESK_ID_THIRDPARTY'] = GETPOST('CASHDESK_ID_THIRDPARTY_id','alpha');
}

// Security check
if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("cashdesk");


/*
 * Actions
 */
if (GETPOST('action','alpha') == 'set')
{
	$db->begin();

	if (GETPOST('socid','int') < 0) $_POST["socid"]='';

	$res = dolibarr_set_const($db,"CASHDESK_ID_THIRDPARTY",(GETPOST('socid','int') > 0 ? GETPOST('socid','int') : ''),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHDESK_ID_BANKACCOUNT_CASH",(GETPOST('CASHDESK_ID_BANKACCOUNT_CASH','alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CASH','alpha') : ''),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHDESK_ID_BANKACCOUNT_CHEQUE",(GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE','alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CHEQUE','alpha') : ''),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHDESK_ID_BANKACCOUNT_CB",(GETPOST('CASHDESK_ID_BANKACCOUNT_CB','alpha') > 0 ? GETPOST('CASHDESK_ID_BANKACCOUNT_CB','alpha') : ''),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHDESK_ID_WAREHOUSE",(GETPOST('CASHDESK_ID_WAREHOUSE','alpha') > 0 ? GETPOST('CASHDESK_ID_WAREHOUSE','alpha') : ''),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHDESK_NO_DECREASE_STOCK",GETPOST('CASHDESK_NO_DECREASE_STOCK','alpha'),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHDESK_SERVICES", GETPOST('CASHDESK_SERVICES','alpha'),'chaine',0,'',$conf->entity);

	dol_syslog("admin/cashdesk: level ".GETPOST('level','alpha'));

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $db->commit();
	    setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        $db->rollback();
	    setEventMessage($langs->trans("Error"), 'errors');
    }
}

/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

llxHeader('',$langs->trans("CashDeskSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashDeskSetup"),$linkback,'title_setup');
print '<br>';


// Mode
$var=true;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width=\"50%\">'.$langs->trans("CashDeskThirdPartyForSell").'</td>';
print '<td colspan="2">';
print $form->select_company($conf->global->CASHDESK_ID_THIRDPARTY,'socid','s.client in (1,3)',1,0,1,array(),0);
print '</td></tr>';
if (! empty($conf->banque->enabled))
{
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("CashDeskBankAccountForSell").'</td>';
	print '<td colspan="2">';
	$form->select_comptes($conf->global->CASHDESK_ID_BANKACCOUNT_CASH,'CASHDESK_ID_BANKACCOUNT_CASH',0,"courant=2",1);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("CashDeskBankAccountForCheque").'</td>';
	print '<td colspan="2">';
	$form->select_comptes($conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE,'CASHDESK_ID_BANKACCOUNT_CHEQUE',0,"courant=1",1);
	print '</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("CashDeskBankAccountForCB").'</td>';
	print '<td colspan="2">';
	$form->select_comptes($conf->global->CASHDESK_ID_BANKACCOUNT_CB,'CASHDESK_ID_BANKACCOUNT_CB',0,"courant=1",1);
	print '</td></tr>';
}

if (! empty($conf->stock->enabled))
{
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("CashDeskDoNotDecreaseStock").'</td>';	// Force warehouse (this is not a default value)
	print '<td colspan="2">';
	if (empty($conf->productbatch->enabled)) {
	   print $form->selectyesno('CASHDESK_NO_DECREASE_STOCK',$conf->global->CASHDESK_NO_DECREASE_STOCK,1);
	}
	else
	{
	    if (!$conf->global->CASHDESK_NO_DECREASE_STOCK) {
	       $res = dolibarr_set_const($db,"CASHDESK_NO_DECREASE_STOCK",1,'chaine',0,'',$conf->entity);
	    }
	    print $langs->trans('StockDecreaseForPointOfSaleDisabledbyBatch');
	}
	print '</td></tr>';

	$disabled=$conf->global->CASHDESK_NO_DECREASE_STOCK;

	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("CashDeskIdWareHouse").'</td>';	// Force warehouse (this is not a default value)
	print '<td colspan="2">';
	if (! $disabled)
	{
		print $formproduct->selectWarehouses($conf->global->CASHDESK_ID_WAREHOUSE,'CASHDESK_ID_WAREHOUSE','',1,$disabled);
		print ' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">('.$langs->trans("Create").')</a>';
	}
	else
	{
		print $langs->trans("StockDecreaseForPointOfSaleDisabled");
	}
	print '</td></tr>';
}

if (! empty($conf->service->enabled))
{
    $var=! $var;
    print '<tr '.$bc[$var].'><td>';
    print $langs->trans("CashdeskShowServices");
    print '<td colspan="2">';
    print $form->selectyesno("CASHDESK_SERVICES",$conf->global->CASHDESK_SERVICES,1);
    print "</td></tr>\n";
}

print '</table>';
print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";

$db->close();

llxFooter();
