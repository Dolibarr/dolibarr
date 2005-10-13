<?
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005 Simon Tosser  <simon@kornog-computing.com>
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
 *
 * $Id$
 * $Source$
 */

/**
    	\file       htdocs/admin/delais.php
		\brief      Page d'administration des d?lais de retard
		\version    $Revision$
*/
require("./pre.inc.php");

$langs->load("admin");
$langs->load("orders");
$langs->load("propal");
$langs->load("contracts");
$langs->load("bills");
$langs->load("banks");

if (!$user->admin)
  accessforbidden();
if ( (isset($_POST["action"]) && $_POST["action"] == 'update')
  || (isset($_POST["action"]) && $_POST["action"] == 'updateedit') )
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
	dolibarr_set_const($db, "MAIN_DELAY_MEMBERS",$_POST["Members"]);
    if ($_POST['action'] != 'updateedit')
    {
        Header("Location: delais.php");
    }
}



llxHeader();

$form = new Form($db);
$countrynotdefined='<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';


print_titre($langs->trans("D&eacute;lais"));


print "<br>\n";

if ((isset($_GET["action"]) && $_GET["action"] == 'edit')
 || (isset($_POST["action"]) && $_POST["action"] == 'updateedit') )
{
    /*
     * Edition des param?tres
     */
    print '
    <script language="javascript" type="text/javascript">
    <!--
    function save_refresh()
    {
    	document.form_index.action.value="updateedit";
    	document.form_index.submit();
    //	location.href = "delais.php?action=updateedit";
    }
    -->
    </script>
    ';

    print '<form method="post" action="delais.php" name="form_index">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("DolibarrWorkBoard").'</td><td>'.$langs->trans("Value").'</td></tr>';

	//
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("ActionsToDo").'</td><td>';
    print '<input name="ActionsToDo" value="'. $conf->global->MAIN_DELAY_ACTIONS_TODO . '"> ' . $langs->trans("days") . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("OrdersToProcess").'</td><td>';
    print '<input name="OrdersToProcess" value="'. $conf->global->MAIN_DELAY_ORDERS_TO_PROCESS . '"> ' . $langs->trans("days") . '</td></tr>';
   
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("PropalsToClose").'</td><td>';
    print '<input name="PropalsToClose" value="'. $conf->global->MAIN_DELAY_PROPALS_TO_CLOSE . '"> ' . $langs->trans("days") . '</td></tr>';    

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("PropalsToBill").'</td><td>';
    print '<input name="PropalsToBill" value="'. $conf->global->MAIN_DELAY_PROPALS_TO_BILL . '"> ' . $langs->trans("days") . '</td></tr>';	

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("BoardNotActivatedServices").'</td><td>';
    print '<input name="BoardNotActivatedServices" value="'. $conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES . '"> ' . $langs->trans("days") . '</td></tr>';
    
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("BoardRunningServices").'</td><td>';
    print '<input name="BoardRunningServices" value="'. $conf->global->MAIN_DELAY_RUNNING_SERVICES . '"> ' . $langs->trans("days") . '</td></tr>';
    
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("SupplierBillsToPay").'</td><td>';
    print '<input name="SupplierBillsToPay" value="'. $conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY . '"> ' . $langs->trans("days") . '</td></tr>';
    
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("CustomerBillsUnpayed").'</td><td>';
    print '<input name="CustomerBillsUnpayed" value="'. $conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED . '"> ' . $langs->trans("days") . '</td></tr>';
    
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("TransactionsToConciliate").'</td><td>';
    print '<input name="TransactionsToConciliate" value="'. $conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE . '"> ' . $langs->trans("days") . '</td></tr>'; 

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Members").'</td><td>';
    print '<input name="Members" value="'. $conf->global->MAIN_DELAY_MEMBERS . '"> ' . $langs->trans("days") . '</td></tr>';
    
    print '</table>';

    print '<br>';

 
    


    print '<br><center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';
    print '<br>';
    
    print '</form>';
}
else
{
    /*
     * Affichage des param?tres
     */

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("DolibarrWorkBoard").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=true;

    $var=!$var;
    //Conversion des secondes en jours  
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ActionsToDo").'</td><td>' . $conf->global->MAIN_DELAY_ACTIONS_TODO . ' ' . $langs->trans("days") . '</td></tr>';

	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("OrdersToProcess").'</td><td>' . $conf->global->MAIN_DELAY_ORDERS_TO_PROCESS . ' ' . $langs->trans("days") . '</td></tr>';
 
	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("PropalsToClose").'</td><td>' . $conf->global->MAIN_DELAY_PROPALS_TO_CLOSE . ' ' . $langs->trans("days") . '</td></tr>';

	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("PropalsToBill").'</td><td>' . $conf->global->MAIN_DELAY_PROPALS_TO_BILL . ' ' . $langs->trans("days") . '</td></tr>';

	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("BoardNotActivatedServices").'</td><td>' . $conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES . ' ' . $langs->trans("days") . '</td></tr>';
    
	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("BoardRunningServices").'</td><td>' . $conf->global->MAIN_DELAY_RUNNING_SERVICES . ' ' . $langs->trans("days") . '</td></tr>';

	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("SupplierBillsToPay").'</td><td>' . $conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY . ' ' . $langs->trans("days") . '</td></tr>';
    
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("CustomerBillsUnpayed").'</td><td>' . $conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED . ' ' . $langs->trans("days") . '</td></tr>';

	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("TransactionsToConciliate").'</td><td>' . $conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE . ' ' . $langs->trans("days") . '</td></tr>';    

$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("Members").'</td><td>' . $conf->global->MAIN_DELAY_MEMBERS . ' ' . $langs->trans("days") . '</td></tr>';

    print '</table>';

    print '<br>';
 
    
    // Boutons d'action
    print '<div class="tabsAction">';
    print '<a class="tabAction" href="delais.php?action=edit">'.$langs->trans("Edit").'</a>';
    print '</div>';

}


llxFooter('$Date$ - $Revision$');

?>