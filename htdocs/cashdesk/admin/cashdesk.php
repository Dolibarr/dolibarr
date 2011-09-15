<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\version    $Id: cashdesk.php,v 1.7 2011/08/03 00:46:38 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");

// If socid provided by ajax company selector
if (! empty($_REQUEST['CASHDESK_ID_THIRDPARTY_id']))
{
	$_GET['CASHDESK_ID_THIRDPARTY'] = $_GET['CASHDESK_ID_THIRDPARTY_id'];
	$_POST['CASHDESK_ID_THIRDPARTY'] = $_POST['CASHDESK_ID_THIRDPARTY_id'];
	$_REQUEST['CASHDESK_ID_THIRDPARTY'] = $_REQUEST['CASHDESK_ID_THIRDPARTY_id'];
}

// Security check
if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("cashdesk");


/*
 * Actions
 */
if ($_POST["action"] == 'set')
{
	if ($_POST["socid"] < 0) $_POST["socid"]='';
	if ($_POST["CASHDESK_ID_BANKACCOUNT"] < 0)  $_POST["CASHDESK_ID_BANKACCOUNT"]='';
	if ($_POST["CASHDESK_ID_WAREHOUSE"] < 0)  $_POST["CASHDESK_ID_WAREHOUSE"]='';

	dolibarr_set_const($db,"CASHDESK_ID_THIRDPARTY",$_POST["socid"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,"CASHDESK_ID_BANKACCOUNT_CASH",$_POST["CASHDESK_ID_BANKACCOUNT_CASH"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,"CASHDESK_ID_BANKACCOUNT_CHEQUE",$_POST["CASHDESK_ID_BANKACCOUNT_CHEQUE"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,"CASHDESK_ID_BANKACCOUNT_CB",$_POST["CASHDESK_ID_BANKACCOUNT_CB"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,"CASHDESK_ID_WAREHOUSE",$_POST["CASHDESK_ID_WAREHOUSE"],'chaine',0,'',$conf->entity);

	dol_syslog("admin/cashdesk: level ".$_POST["level"]);
}



/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("CashDeskSetup"),$linkback,'setup');
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
print $form->select_societes($conf->global->CASHDESK_ID_THIRDPARTY,'socid','s.client in (1,3)',1,1);
print '</td></tr>';
if ($conf->banque->enabled)
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

if ($conf->stock->enabled)
{
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("CashDeskIdWareHouse").'</td>';
	print '<td colspan="2">';
	$formproduct->selectWarehouses($conf->global->CASHDESK_ID_WAREHOUSE,'CASHDESK_ID_WAREHOUSE','',1);
	print '</td></tr>';
}

print '</table>';
print '<br>';

print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';

print "</form>\n";

llxFooter('$Date: 2011/08/03 00:46:38 $ - $Revision: 1.7 $');
?>
