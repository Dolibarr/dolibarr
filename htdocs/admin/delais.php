<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
    	\file       htdocs/admin/delais.php
		\brief      Page d'administration des d?lais de retard
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("orders");
$langs->load("propal");
$langs->load("contracts");
$langs->load("bills");
$langs->load("banks");

if (!$user->admin)
  accessforbidden();
  
if ( (isset($_POST["action"]) && $_POST["action"] == 'update'))
{
	//Conversion des jours en secondes
    dolibarr_set_const($db, "MAIN_DELAY_ACTIONS_TODO",$_POST["ActionsToDo"]);
    dolibarr_set_const($db, "MAIN_DELAY_ORDERS_TO_PROCESS",$_POST["OrdersToProcess"]);
    dolibarr_set_const($db, "MAIN_DELAY_PROPALS_TO_CLOSE",$_POST["PropalsToClose"]);
    dolibarr_set_const($db, "MAIN_DELAY_PROPALS_TO_BILL",$_POST["PropalsToBill"]);
    dolibarr_set_const($db, "MAIN_DELAY_NOT_ACTIVATED_SERVICES",$_POST["BoardNotActivatedServices"]);
    dolibarr_set_const($db, "MAIN_DELAY_RUNNING_SERVICES",$_POST["BoardRunningServices"]);
    dolibarr_set_const($db, "MAIN_DELAY_SUPPLIER_BILLS_TO_PAY",$_POST["SupplierBillsToPay"]);
    dolibarr_set_const($db, "MAIN_DELAY_CUSTOMER_BILLS_UNPAYED",$_POST["CustomerBillsUnpayed"]);
    dolibarr_set_const($db, "MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE",$_POST["TransactionsToConciliate"]);
    dolibarr_set_const($db, "MAIN_DELAY_CHEQUES_TO_DEPOSIT",$_POST["ChequesToDeposit"]);
	dolibarr_set_const($db, "MAIN_DELAY_MEMBERS",$_POST["Members"]);
}


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("DelaysOfToleranceBeforeWarning"),'','setup');

print $langs->transnoentities("DelaysOfToleranceDesc",img_warning());
print " ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";
print "<br>\n";

$form = new Form($db);
$countrynotdefined='<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';


if ((isset($_GET["action"]) && $_GET["action"] == 'edit'))
{
    print '<form method="post" action="delais.php" name="form_index">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2" width="60%">'.$langs->trans("DolibarrWorkBoard").'</td><td>'.$langs->trans("Value").'</td></tr>';

	//
    if ($conf->agenda->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','task').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceActionsToDo").'</td><td>';
        print '<input size="5" name="ActionsToDo" value="'. ($conf->global->MAIN_DELAY_ACTIONS_TODO+0) . '"> ' . $langs->trans("days") . '</td></tr>';
    }
    if ($conf->commande->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','order').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceOrdersToProcess").'</td><td>';
        print '<input size="5" name="OrdersToProcess" value="'. ($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS+0) . '"> ' . $langs->trans("days") . '</td></tr>';
    }
    if ($conf->propal->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','propal').'</td>';
        print '<td>'.$langs->trans("DelaysOfTolerancePropalsToClose").'</td><td>';
        print '<input size="5" name="PropalsToClose" value="'. ($conf->global->MAIN_DELAY_PROPALS_TO_CLOSE+0) . '"> ' . $langs->trans("days") . '</td></tr>';    
    }
    if ($conf->propal->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','propal').'</td>';
        print '<td>'.$langs->trans("DelaysOfTolerancePropalsToBill").'</td><td>';
        print '<input size="5" name="PropalsToBill" value="'. ($conf->global->MAIN_DELAY_PROPALS_TO_BILL+0) . '"> ' . $langs->trans("days") . '</td></tr>';	
    }
    if ($conf->service->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','service').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceNotActivatedServices").'</td><td>';
        print '<input size="5" name="BoardNotActivatedServices" value="'. ($conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES+0) . '"> ' . $langs->trans("days") . '</td></tr>';
    }
    if ($conf->service->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','service').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceRunningServices").'</td><td>';
        print '<input size="5" name="BoardRunningServices" value="'. ($conf->global->MAIN_DELAY_RUNNING_SERVICES +0). '"> ' . $langs->trans("days") . '</td></tr>';
    }
    if ($conf->fournisseur->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','bill').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceSupplierBillsToPay").'</td><td>';
        print '<input size="5" name="SupplierBillsToPay" value="'. ($conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY+0) . '"> ' . $langs->trans("days") . '</td></tr>';
    }    
    if ($conf->facture->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','bill').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceCustomerBillsUnpayed").'</td><td>';
        print '<input size="5" name="CustomerBillsUnpayed" value="'. ($conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED+0) . '"> ' . $langs->trans("days") . '</td></tr>';
    }    
    if ($conf->banque->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','account').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceTransactionsToConciliate").'</td><td>';
        print '<input size="5" name="TransactionsToConciliate" value="'. ($conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE+0) . '"> ' . $langs->trans("days") . '</td></tr>'; 
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','account').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceChequesToDeposit").'</td><td>';
        print '<input size="5" name="ChequesToDeposit" value="'. ($conf->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT+0) . '"> ' . $langs->trans("days") . '</td></tr>'; 
    }
    if ($conf->adherent->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','user').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceMembers").'</td><td>';
        print '<input size="5" name="Members" value="'. ($conf->global->MAIN_DELAY_MEMBERS+0). '"> ' . $langs->trans("days") . '</td></tr>';
    }
        
    print '</table>';


    print '<br><center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';
    print '<br>';
    
    print '</form>';
}
else
{
    /*
     * Affichage des parametres
     */

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2" width="60%">'.$langs->trans("DolibarrWorkBoard").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=true;

    $var=!$var;

    if ($conf->agenda->enabled)
    {
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','task').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceActionsToDo").'</td><td>' . ($conf->global->MAIN_DELAY_ACTIONS_TODO+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }
    
    if ($conf->commande->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','order').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceOrdersToProcess").'</td><td>' . ($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }
     
    if ($conf->propal->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','propal').'</td>';
        print '<td>'.$langs->trans("DelaysOfTolerancePropalsToClose").'</td><td>' . ($conf->global->MAIN_DELAY_PROPALS_TO_CLOSE+0). ' ' . $langs->trans("days") . '</td></tr>';
    }

    if ($conf->propal->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','propal').'</td>';
        print '<td>'.$langs->trans("DelaysOfTolerancePropalsToBill").'</td><td>' . ($conf->global->MAIN_DELAY_PROPALS_TO_BILL+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }

    if ($conf->service->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','service').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceNotActivatedServices").'</td><td>' . ($conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }
    
    if ($conf->service->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','service').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceRunningServices").'</td><td>' . ($conf->global->MAIN_DELAY_RUNNING_SERVICES+0). ' ' . $langs->trans("days") . '</td></tr>';
    }

    if ($conf->fournisseur->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','bill').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceSupplierBillsToPay").'</td><td>' . ($conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }
    
    if ($conf->facture->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','bill').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceCustomerBillsUnpayed").'</td><td>' . ($conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }

    if ($conf->banque->enabled)
    {
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','account').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceTransactionsToConciliate").'</td><td>' . ($conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE+0) . ' ' . $langs->trans("days") . '</td></tr>';    
    	$var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','account').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceChequesToDeposit").'</td><td>' . ($conf->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT+0) . ' ' . $langs->trans("days") . '</td></tr>';    
    }

    if ($conf->adherent->enabled)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td width="20px">'.img_object('','user').'</td>';
        print '<td>'.$langs->trans("DelaysOfToleranceMembers").'</td><td>' . ($conf->global->MAIN_DELAY_MEMBERS+0) . ' ' . $langs->trans("days") . '</td></tr>';
    }

    print '</table>';

    // Boutons d'action
    print '<div class="tabsAction">';
    print '<a class="butAction" href="delais.php?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';

}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
