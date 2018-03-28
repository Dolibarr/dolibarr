<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier      <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2012      Marcos García         <marcosgdf@gmail.com>
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

// Protection to avoid direct call of template
if (empty($langs) || ! is_object($langs))
{
	print "Error, template page can't be called as URL";
	exit;
}


include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
include_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

/*if (!empty($_SESSION["CASHDESK_ID_THIRDPARTY"]))
{
	$company=new Societe($db);
	$company->fetch($_SESSION["CASHDESK_ID_THIRDPARTY"]);
	$companyLink = $company->getNomUrl(1);
}*/
if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]))
{
	$bankcash=new Account($db);
	$bankcash->fetch($_SESSION["CASHDESK_ID_BANKACCOUNT_CASH"]);
	$bankcash->label=$bankcash->ref;
	$bankcashLink = $bankcash->getNomUrl(1);
}
if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]))
{
	$bankcb=new Account($db);
	$bankcb->fetch($_SESSION["CASHDESK_ID_BANKACCOUNT_CB"]);
	$bankcbLink = $bankcb->getNomUrl(1);
}
if (!empty($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]))
{
	$bankcheque=new Account($db);
	$bankcheque->fetch($_SESSION["CASHDESK_ID_BANKACCOUNT_CHEQUE"]);
	$bankchequeLink = $bankcheque->getNomUrl(1);
}
if (!empty($_SESSION["CASHDESK_ID_WAREHOUSE"]) && ! empty($conf->stock->enabled))
{
	$warehouse=new Entrepot($db);
	$warehouse->fetch($_SESSION["CASHDESK_ID_WAREHOUSE"]);
	$warehouseLink = $warehouse->getNomUrl(1);
}


$langs->load("cashdesk");
$langs->load("main");

print "\n".'<!-- menu.tpl.php -->'."\n";
print '<div class="menu_bloc">';
print '<ul class="menu">';
// Link to new sell
print '<li class="menu_choix1"><a href="affIndex.php?menutpl=facturation&id=NOUV"><span class="hideonsmartphone">'.$langs->trans("NewSell").'</span></a></li>';
// Open new tab on backoffice (this is not a disconnect from POS)
print '<li class="menu_choix2"><a href=".." target="backoffice"><span class="hideonsmartphone">'.$langs->trans("BackOffice").'</span></a></li>';
// Disconnect
print '<li class="menu_choix0">'.$langs->trans("User").': '.$_SESSION['firstname'].' '.$_SESSION['lastname'];
print ' <a href="deconnexion.php">'.img_picto($langs->trans('Logout'), 'logout.png').'</a><br>';
print '<form id="frmThirdparty" class="formulaire1 inline-block" method="post" action="facturation_verif.php?action=change_thirdparty">';
print $langs->trans("CashDeskThirdParty").': ';
print $form->select_company($_SESSION["CASHDESK_ID_THIRDPARTY"], 'CASHDESK_ID_THIRDPARTY', 's.client IN (1,3) AND s.status = 1', '', 0, 0, null, 0, 'valignmiddle inline-block');
print '<input class="button bouton_change_thirdparty inline-block valignmiddle" type="submit" id="bouton_change_thirdparty" value="'.$langs->trans("Modify").'">';
//print $companyLink;
print '<br>';
print '</form>';
/*print $langs->trans("CashDeskBankCash").': '.$bankcashLink.'<br>';
print $langs->trans("CashDeskBankCB").': '.$bankcbLink.'<br>';
print $langs->trans("CashDeskBankCheque").': '.$bankchequeLink.'<br>';*/
print '<div class="clearboth">';
if (!empty($_SESSION["CASHDESK_ID_WAREHOUSE"]) && ! empty($conf->stock->enabled) && empty($conf->global->CASHDESK_NO_DECREASE_STOCK))
{
	print $langs->trans("CashDeskWarehouse").': '.$warehouseLink;
}
print '</div></li></ul>';
print '</div>';
print "\n".'<!-- menu.tpl.php end -->'."\n";
