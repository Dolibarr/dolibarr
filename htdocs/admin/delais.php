<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon Tosser         <simon@kornog-computing.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *   	\file       htdocs/admin/delais.php
 *		\brief      Page to setup late delays
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("orders");
$langs->load("propal");
$langs->load("contracts");
$langs->load("bills");
$langs->load("banks");

if (! $user->admin) accessforbidden();

$action=GETPOST('action','alpha');

if ($action == 'update')
{
    //Conversion des jours en secondes
    if ($_POST["ActionsToDo"]) dolibarr_set_const($db, "MAIN_DELAY_ACTIONS_TODO",$_POST["ActionsToDo"],'chaine',0,'',$conf->entity);
    if ($_POST["OrdersToProcess"]) dolibarr_set_const($db, "MAIN_DELAY_ORDERS_TO_PROCESS",$_POST["OrdersToProcess"],'chaine',0,'',$conf->entity);
    if ($_POST["SuppliersOrdersToProcess"]) dolibarr_set_const($db, "MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS",$_POST["SuppliersOrdersToProcess"],'chaine',0,'',$conf->entity);
    if ($_POST["PropalsToClose"]) dolibarr_set_const($db, "MAIN_DELAY_PROPALS_TO_CLOSE",$_POST["PropalsToClose"],'chaine',0,'',$conf->entity);
    if ($_POST["PropalsToBill"]) dolibarr_set_const($db, "MAIN_DELAY_PROPALS_TO_BILL",$_POST["PropalsToBill"],'chaine',0,'',$conf->entity);
    if ($_POST["BoardNotActivatedServices"]) dolibarr_set_const($db, "MAIN_DELAY_NOT_ACTIVATED_SERVICES",$_POST["BoardNotActivatedServices"],'chaine',0,'',$conf->entity);
    if ($_POST["BoardRunningServices"]) dolibarr_set_const($db, "MAIN_DELAY_RUNNING_SERVICES",$_POST["BoardRunningServices"],'chaine',0,'',$conf->entity);
    if ($_POST["CustomerBillsUnpaid"]) dolibarr_set_const($db, "MAIN_DELAY_CUSTOMER_BILLS_UNPAYED",$_POST["CustomerBillsUnpaid"],'chaine',0,'',$conf->entity);
    if ($_POST["SupplierBillsToPay"]) dolibarr_set_const($db, "MAIN_DELAY_SUPPLIER_BILLS_TO_PAY",$_POST["SupplierBillsToPay"],'chaine',0,'',$conf->entity);
    if ($_POST["TransactionsToConciliate"]) dolibarr_set_const($db, "MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE",$_POST["TransactionsToConciliate"],'chaine',0,'',$conf->entity);
    if ($_POST["ChequesToDeposit"]) dolibarr_set_const($db, "MAIN_DELAY_CHEQUES_TO_DEPOSIT",$_POST["ChequesToDeposit"],'chaine',0,'',$conf->entity);
    if ($_POST["Members"]) dolibarr_set_const($db, "MAIN_DELAY_MEMBERS",$_POST["Members"],'chaine',0,'',$conf->entity);

    dolibarr_set_const($db, "MAIN_DISABLE_METEO",$_POST["MAIN_DISABLE_METEO"],'chaine',0,'',$conf->entity);
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

$modules=array(
		'agenda' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceActionsToDo"),
						'name' => 'ActionsToDo',
						'img' => 'action',
						'value' => (! empty($conf->global->MAIN_DELAY_ACTIONS_TODO)?$conf->global->MAIN_DELAY_ACTIONS_TODO:0)
				)
		),
		'propal' => array(
				array(
						'label' => $langs->trans("DelaysOfTolerancePropalsToClose"),
						'name' => 'PropalsToClose',
						'img' => 'propal',
						'value' => (! empty($conf->global->MAIN_DELAY_PROPALS_TO_CLOSE)?$conf->global->MAIN_DELAY_PROPALS_TO_CLOSE:0)
				),
				array(
						'label' => $langs->trans("DelaysOfTolerancePropalsToBill"),
						'name' => 'PropalsToBill',
						'img' => 'propal',
						'value' => (! empty($conf->global->MAIN_DELAY_PROPALS_TO_BILL)?$conf->global->MAIN_DELAY_PROPALS_TO_BILL:0)
				)
		),
		'commande' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceOrdersToProcess"),
						'name' => 'OrdersToProcess',
						'img' => 'order',
						'value' => (! empty($conf->global->MAIN_DELAY_ORDERS_TO_PROCESS)?$conf->global->MAIN_DELAY_ORDERS_TO_PROCESS:0)
				)
		),
		'facture' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceCustomerBillsUnpaid"),
						'name' => 'CustomerBillsUnpaid',
						'img' => 'bill',
						'value' => (! empty($conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED)?$conf->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED:0)
				)
		),
		'fournisseur' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceSuppliersOrdersToProcess"),
						'name' => 'SuppliersOrdersToProcess',
						'img' => 'order',
						'value' => (! empty($conf->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS)?$conf->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS:0)
				),
				array(
						'label' => $langs->trans("DelaysOfToleranceSupplierBillsToPay"),
						'name' => 'SupplierBillsToPay',
						'img' => 'bill',
						'value' => (! empty($conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY)?$conf->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY:0)
				)
		),
		'service' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceNotActivatedServices"),
						'name' => 'BoardNotActivatedServices',
						'img' => 'service',
						'value' => (! empty($conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES)?$conf->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES:0)
				),
				array(
						'label' => $langs->trans("DelaysOfToleranceRunningServices"),
						'name' => 'BoardRunningServices',
						'img' => 'service',
						'value' => (! empty($conf->global->MAIN_DELAY_RUNNING_SERVICES)?$conf->global->MAIN_DELAY_RUNNING_SERVICES:0)
				)
		),
		'banque' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceTransactionsToConciliate"),
						'name' => 'TransactionsToConciliate',
						'img' => 'account',
						'value' => (! empty($conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE)?$conf->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE:0)
				),
				array(
						'label' => $langs->trans("DelaysOfToleranceChequesToDeposit"),
						'name' => 'ChequesToDeposit',
						'img' => 'account',
						'value' => (! empty($conf->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT)?$conf->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT:0)
				)
		),
		'adherent' => array(
				array(
						'label' => $langs->trans("DelaysOfToleranceMembers"),
						'name' => 'Members',
						'img' => 'user',
						'value' => (! empty($conf->global->MAIN_DELAY_MEMBERS)?$conf->global->MAIN_DELAY_MEMBERS:0)
				)
		),
);

if ($action == 'edit')
{
    print '<form method="post" action="'.$_SERVER['PHP_SELF'].'" name="form_index">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("DelaysOfToleranceBeforeWarning").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';

    foreach($modules as $module => $delays)
    {
    	if (! empty($conf->$module->enabled))
    	{
    		foreach($delays as $delay)
    		{
    			$var=!$var;
    			print '<tr '.$bc[$var].'>';
    			print '<td width="20px">'.img_object('',$delay['img']).'</td>';
    			print '<td>'.$delay['label'].'</td><td>';
    			print '<input size="5" name="'.$delay['name'].'" value="'.$delay['value'].'"> '.$langs->trans("days").'</td></tr>';
    		}
    	}
    }

    print '</table>';

    print '<br>';

	// Show if meteo is enabled
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("MAIN_DISABLE_METEO").'</td><td>' .$form->selectyesno('MAIN_DISABLE_METEO',(isset($conf->global->MAIN_DISABLE_METEO)?1:0),1) . '</td></tr>';

	print '</table>';

	print '<br>';

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
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("DelaysOfToleranceBeforeWarning").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';
    $var=true;

    foreach($modules as $module => $delays)
    {
    	if (! empty($conf->$module->enabled))
    	{
    		foreach($delays as $delay)
    		{
    			$var=!$var;
    			print '<tr '.$bc[$var].'>';
    			print '<td width="20px">'.img_object('',$delay['img']).'</td>';
    			print '<td>'.$delay['label'].'</td>';
    			print '<td>'.$delay['value'].' '.$langs->trans("days").'</td></tr>';
    		}
    	}
    }

    print '</table>';

	print '<br>';

	// Show if meteo is enabled
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="120px">'.$langs->trans("Value").'</td></tr>';

	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("MAIN_DISABLE_METEO").'</td><td>' . yn($conf->global->MAIN_DISABLE_METEO) . '</td></tr>';

	print '</table>';

	print '<br>';

    // Boutons d'action
    print '<div class="tabsAction">';
    print '<a class="butAction" href="delais.php?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';

}

print '<br>';


// Show logo for weather
print $langs->trans("DescWeather").'<br>';

$offset=0;
$cursor=10; // By default
//if (! empty($conf->global->MAIN_METEO_OFFSET)) $offset=$conf->global->MAIN_METEO_OFFSET;
//if (! empty($conf->global->MAIN_METEO_GAP)) $cursor=$conf->global->MAIN_METEO_GAP;
$level0=$offset;           if (! empty($conf->global->MAIN_METEO_LEVEL0)) $level0=$conf->global->MAIN_METEO_LEVEL0;
$level1=$offset+1*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL1)) $level1=$conf->global->MAIN_METEO_LEVEL1;
$level2=$offset+2*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL2)) $level2=$conf->global->MAIN_METEO_LEVEL2;
$level3=$offset+3*$cursor; if (! empty($conf->global->MAIN_METEO_LEVEL3)) $level3=$conf->global->MAIN_METEO_LEVEL3;
$text=''; $options='height="60px"';
print '<table>';
print '<tr>';
print '<td>';
print img_picto_common($text,'weather/weather-clear.png',$options);
print '</td><td>= '.$level0.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td>';
print img_picto_common($text,'weather/weather-few-clouds.png',$options);
print '</td><td>&lt;= '.$level1.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td>';
print img_picto_common($text,'weather/weather-clouds.png',$options);
print '</td><td>&lt;= '.$level2.'</td>';
print '</tr>';

print '<tr><td>';
print img_picto_common($text,'weather/weather-many-clouds.png',$options);
print '</td><td>&lt;= '.$level3.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td>';
print img_picto_common($text,'weather/weather-storm.png',$options);
print '</td><td>&gt; '.$level3.'</td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '<td> &nbsp; &nbsp; &nbsp; &nbsp; </td>';
print '</tr>';

print '</table>';


llxFooter();
$db->close();
?>
