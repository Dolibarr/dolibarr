<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013-2018 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
 *	\file       htdocs/admin/stock.php
 *	\ingroup    stock
 *	\brief      Page d'administration/configuration du module gestion de stock
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "stocks"));

// Securit check
if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if($action)
{
	$db->begin();

	if ($action == 'STOCK_SUPPORTS_SERVICES')
	{
		$res = dolibarr_set_const($db, "STOCK_SUPPORTS_SERVICES", GETPOST('STOCK_SUPPORTS_SERVICES','alpha'),'chaine',0,'',$conf->entity);
	}
	if ($action == 'STOCK_USERSTOCK_AUTOCREATE')
	{
		$res = dolibarr_set_const($db, "STOCK_USERSTOCK_AUTOCREATE", GETPOST('STOCK_USERSTOCK_AUTOCREATE','alpha'),'chaine',0,'',$conf->entity);
	}
	if ($action == 'STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') {
		$res = dolibarr_set_const($db, "STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE", GETPOST('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE','alpha'),'chaine',0,'',$conf->entity);
	}
	if ($action == 'STOCK_ALLOW_NEGATIVE_TRANSFER')
	{
		$res = dolibarr_set_const($db, "STOCK_ALLOW_NEGATIVE_TRANSFER", GETPOST('STOCK_ALLOW_NEGATIVE_TRANSFER','alpha'),'chaine',0,'',$conf->entity);
	}
	// Mode of stock decrease
	if ($action == 'STOCK_CALCULATE_ON_BILL'
	|| $action == 'STOCK_CALCULATE_ON_VALIDATE_ORDER'
	|| $action == 'STOCK_CALCULATE_ON_SHIPMENT'
	|| $action == 'STOCK_CALCULATE_ON_SHIPMENT_CLOSE')
	{
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_BILL", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_VALIDATE_ORDER", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT_CLOSE", '','chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_BILL')           $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_BILL", GETPOST('STOCK_CALCULATE_ON_BILL','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_VALIDATE_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_VALIDATE_ORDER", GETPOST('STOCK_CALCULATE_ON_VALIDATE_ORDER','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SHIPMENT')       $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT", GETPOST('STOCK_CALCULATE_ON_SHIPMENT','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SHIPMENT_CLOSE')       $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT_CLOSE", GETPOST('STOCK_CALCULATE_ON_SHIPMENT_CLOSE','alpha'),'chaine',0,'',$conf->entity);
	}
	// Mode of stock increase
	if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_BILL'
	|| $action == 'STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER'
	|| $action == 'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER')
	{
		//Use variable cause empty(GETPOST()) do not work with php version < 5.4
		$valdispatch=GETPOST('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER','alpha');

		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_BILL", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER", '','chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_BILL')           $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_BILL", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_BILL','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER", $valdispatch,'chaine',0,'',$conf->entity);
		if (empty($valdispatch)) {
			$res=dolibarr_set_const($db, "SUPPLIER_ORDER_USE_DISPATCH_STATUS", '','chaine',0,'',$conf->entity);
		}
	}

	if($action == 'SUPPLIER_ORDER_USE_DISPATCH_STATUS') {
		$res = dolibarr_set_const($db, "SUPPLIER_ORDER_USE_DISPATCH_STATUS", GETPOST('SUPPLIER_ORDER_USE_DISPATCH_STATUS','alpha'),'chaine',0,'',$conf->entity);
	}

	if($action == 'STOCK_USE_VIRTUAL_STOCK') {
	    $res = dolibarr_set_const($db, "STOCK_USE_VIRTUAL_STOCK", GETPOST('STOCK_USE_VIRTUAL_STOCK','alpha'),'chaine',0,'',$conf->entity);
	}

	if($action == 'STOCK_MUST_BE_ENOUGH_FOR_INVOICE') {
	    $res = dolibarr_set_const($db, "STOCK_MUST_BE_ENOUGH_FOR_INVOICE", GETPOST('STOCK_MUST_BE_ENOUGH_FOR_INVOICE','alpha'),'chaine',0,'',$conf->entity);
	}
	if($action == 'STOCK_MUST_BE_ENOUGH_FOR_ORDER') {
	    $res = dolibarr_set_const($db, "STOCK_MUST_BE_ENOUGH_FOR_ORDER", GETPOST('STOCK_MUST_BE_ENOUGH_FOR_ORDER','alpha'),'chaine',0,'',$conf->entity);
	}
	if($action == 'STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT') {
	    $res = dolibarr_set_const($db, "STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT", GETPOST('STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT','alpha'),'chaine',0,'',$conf->entity);
	}
	if($action == 'INDEPENDANT_SUBPRODUCT_STOCK') {
	    $res = dolibarr_set_const($db, "INDEPENDANT_SUBPRODUCT_STOCK", GETPOST('INDEPENDANT_SUBPRODUCT_STOCK','alpha'),'chaine',0,'',$conf->entity);
	}

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

llxHeader('',$langs->trans("StockSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("StockSetup"),$linkback,'title_setup');

$form=new Form($db);


$disabled='';
if (! empty($conf->productbatch->enabled))
{
	$langs->load("productbatch");
	$disabled=' disabled';
	print info_admin($langs->trans("WhenProductBatchModuleOnOptionAreForced"));
}

//if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) || ! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT))
//{
print info_admin($langs->trans("IfYouUsePointOfSaleCheckModule"));
print '<br>';
//}

// Title rule for stock decrease
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockManagementDecrease")."</td>\n";
print "  <td align=\"right\">&nbsp;</td>\n";
print '</tr>'."\n";

$found=0;

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnBill").'</td>';
print '<td align="right">';
if (! empty($conf->facture->enabled))
{
    print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_BILL\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_BILL",$conf->global->STOCK_CALCULATE_ON_BILL,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module30Name"));
}
print "</td>\n</tr>\n";
$found++;


print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnValidateOrder").'</td>';
print '<td align="right">';
if (! empty($conf->commande->enabled))
{
    print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_VALIDATE_ORDER\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_VALIDATE_ORDER",$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module25Name"));
}
print "</td>\n</tr>\n";
$found++;

//if (! empty($conf->expedition->enabled))
//{

print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnShipment").'</td>';
print '<td align="right">';
if (! empty($conf->expedition->enabled))
{
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SHIPMENT\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SHIPMENT",$conf->global->STOCK_CALCULATE_ON_SHIPMENT,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module80Name"));
}
print "</td>\n</tr>\n";
$found++;


print '<tr class="oddeven">';
print '<td>'.$langs->trans("DeStockOnShipmentOnClosing").'</td>';
print '<td align="right">';
if (! empty($conf->expedition->enabled))
{
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SHIPMENT_CLOSE\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SHIPMENT_CLOSE",$conf->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module80Name"));
}
print "</td>\n</tr>\n";
$found++;

/*if (! $found)
{

	print '<tr class="oddeven">';
	print '<td colspan="2">'.$langs->trans("NoModuleToManageStockDecrease").'</td>';
	print "</tr>\n";
}*/

print '</table>';

print '<br>';

// Title rule for stock increase
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockManagementIncrease")."</td>\n";
print "  <td align=\"right\">&nbsp;</td>\n";
print '</tr>'."\n";

$found=0;

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ReStockOnBill").'</td>';
print '<td align="right">';
if (! empty($conf->fournisseur->enabled))
{
    print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_BILL\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_BILL",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module40Name"));
}
print "</td>\n</tr>\n";
$found++;



print '<tr class="oddeven">';
print '<td>'.$langs->trans("ReStockOnValidateOrder").'</td>';
print '<td align="right">';
if (! empty($conf->fournisseur->enabled))
{
    print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module40Name"));
}
print "</td>\n</tr>\n";
$found++;


print '<tr class="oddeven">';
print '<td>'.$langs->trans("ReStockOnDispatchOrder").'</td>';
print '<td align="right">';
if (! empty($conf->fournisseur->enabled))
{
    print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n";
}
else
{
    print $langs->trans("ModuleMustBeEnabledFirst", $langs->transnoentitiesnoconv("Module40Name"));
}
print "</td>\n</tr>\n";
$found++;

/*if (! $found)
{

	print '<tr class="oddeven">';
	print '<td colspan="2">'.$langs->trans("NoModuleToManageStockIncrease").'</td>';
	print "</tr>\n";
}*/

print '</table>';

print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockAvailability")."</td>\n";
print "  <td align=\"right\">&nbsp;</td>\n";
print '</tr>'."\n";


print '<tr class="oddeven">';
print '<td>'.$langs->trans("WarehouseAllowNegativeTransfer").'</td>';
print '<td align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"STOCK_ALLOW_NEGATIVE_TRANSFER\">";
print $form->selectyesno("STOCK_ALLOW_NEGATIVE_TRANSFER",$conf->global->STOCK_ALLOW_NEGATIVE_TRANSFER,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print "</td>\n";
print "</tr>\n";

// Option to force stock to be enough before adding a line into document
if($conf->invoice->enabled)
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("StockMustBeEnoughForInvoice").'</td>';
	print '<td align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_MUST_BE_ENOUGH_FOR_INVOICE\">";
	print $form->selectyesno("STOCK_MUST_BE_ENOUGH_FOR_INVOICE",$conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print "</td>\n";
	print "</tr>\n";
}

if($conf->order->enabled)
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("StockMustBeEnoughForOrder").'</td>';
	print '<td align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_MUST_BE_ENOUGH_FOR_ORDER\">";
	print $form->selectyesno("STOCK_MUST_BE_ENOUGH_FOR_ORDER",$conf->global->STOCK_MUST_BE_ENOUGH_FOR_ORDER,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print "</td>\n";
	print "</tr>\n";
}

if($conf->expedition->enabled)
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("StockMustBeEnoughForShipment").'</td>';
	print '<td align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT\">";
	print $form->selectyesno("STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT",$conf->global->STOCK_MUST_BE_ENOUGH_FOR_SHIPMENT,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print "</td>\n";
	print "</tr>\n";
}
print '</table>';

print '<br>';

$virtualdiffersfromphysical=0;
if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)
	|| ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
	) $virtualdiffersfromphysical=1;		// According to increase/decrease stock options, virtual and physical stock may differs.

if ($virtualdiffersfromphysical)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print "  <td>".$langs->trans("RuleForStockReplenishment")." ".img_help('help',$langs->trans("VirtualDiffersFromPhysical"))."</td>\n";
	print "  <td align=\"right\">&nbsp;</td>\n";
	print '</tr>'."\n";

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("UseVirtualStockByDefault").'</td>';
	print '<td align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_USE_VIRTUAL_STOCK\">";
	print $form->selectyesno("STOCK_USE_VIRTUAL_STOCK",$conf->global->STOCK_USE_VIRTUAL_STOCK,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print "</td>\n";
	print "</tr>\n";
	print '</table>';
	print '<br>';
}


print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "  <td>".$langs->trans("Other")."</td>\n";
print "  <td align=\"right\">&nbsp;</td>\n";
print '</tr>'."\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("UserWarehouseAutoCreate").'</td>';
print '<td align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"STOCK_USERSTOCK_AUTOCREATE\">";
print $form->selectyesno("STOCK_USERSTOCK_AUTOCREATE",$conf->global->STOCK_USERSTOCK_AUTOCREATE,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print "</td>\n";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("StockSupportServices"), $langs->trans("StockSupportServicesDesc"));
print '</td>';
print '<td align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"STOCK_SUPPORTS_SERVICES\">";
print $form->selectyesno("STOCK_SUPPORTS_SERVICES",$conf->global->STOCK_SUPPORTS_SERVICES,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print "</td>\n";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("AllowAddLimitStockByWarehouse").'</td>';
print '<td align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE\">";
print $form->selectyesno("STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE",$conf->global->STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print "</td>\n";
print "</tr>\n";

if (! empty($conf->fournisseur->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)) {

    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("UseDispatchStatus").'</td>';
    print '<td align="right">';
    print "<form method=\"post\" action=\"stock.php\">";
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print "<input type=\"hidden\" name=\"action\" value=\"SUPPLIER_ORDER_USE_DISPATCH_STATUS\">";
    print $form->selectyesno("SUPPLIER_ORDER_USE_DISPATCH_STATUS",$conf->global->SUPPLIER_ORDER_USE_DISPATCH_STATUS,1);
    print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
    print "</form>\n";
    print "</td>\n</tr>\n";
}

print '</table>';

print '<br>';
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Inventory").'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center">&nbsp;</td>'."\n";

	// Example with a yes / no select
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("INVENTORY_DISABLE_VIRTUAL").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_INVENTORY_DISABLE_VIRTUAL">';
	print $form->selectyesno("INVENTORY_DISABLE_VIRTUAL",$conf->global->INVENTORY_DISABLE_VIRTUAL,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	// Example with a yes / no select
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("INVENTORY_USE_MIN_PA_IF_NO_LAST_PA").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_INVENTORY_USE_MIN_PA_IF_NO_LAST_PA">';
	print $form->selectyesno("INVENTORY_USE_MIN_PA_IF_NO_LAST_PA",$conf->global->INVENTORY_USE_MIN_PA_IF_NO_LAST_PA,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	// Example with a yes / no select
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("INVENTORY_USE_INVENTORY_DATE_FROM_DATEMVT").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="300">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_INVENTORY_USE_INVENTORY_DATE_FROM_DATEMVT">';
	print $form->selectyesno("INVENTORY_USE_INVENTORY_DATE_FROM_DATEMVT",$conf->global->INVENTORY_USE_INVENTORY_DATE_FROM_DATEMVT,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	print '</table>';
}

/* I keep the option/feature, but hidden to end users for the moment. If feature is used by module, no need to have users see it.
If not used by a module, I still need to understand in which case user may need this now we can set rule on product page.
if ($conf->global->PRODUIT_SOUSPRODUITS)
{


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("IndependantSubProductStock").'</td>';
	print '<td align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"INDEPENDANT_SUBPRODUCT_STOCK\">";
	print $form->selectyesno("INDEPENDANT_SUBPRODUCT_STOCK",$conf->global->INDEPENDANT_SUBPRODUCT_STOCK,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print "</td>\n";
	print "</tr>\n";
}
*/

// End of page
llxFooter();
$db->close();
