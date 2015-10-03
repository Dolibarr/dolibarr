<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2013 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013      Philippe Grand       <philippe.grand@atoo-net.com>
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

$langs->load("admin");
$langs->load("stocks");

// Securit check
if (!$user->admin) accessforbidden();

$action = GETPOST('action','alpha');


/*
 * Actions
 */

if($action)
{
	$db->begin();

	if ($action == 'STOCK_USERSTOCK_AUTOCREATE')
	{
		$res = dolibarr_set_const($db, "STOCK_USERSTOCK_AUTOCREATE", GETPOST('STOCK_USERSTOCK_AUTOCREATE','alpha'),'chaine',0,'',$conf->entity);
	}
	// Mode of stock decrease
	if ($action == 'STOCK_CALCULATE_ON_BILL'
	|| $action == 'STOCK_CALCULATE_ON_VALIDATE_ORDER'
	|| $action == 'STOCK_CALCULATE_ON_SHIPMENT')
	{
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_BILL", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_VALIDATE_ORDER", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT", '','chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_BILL')           $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_BILL", GETPOST('STOCK_CALCULATE_ON_BILL','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_VALIDATE_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_VALIDATE_ORDER", GETPOST('STOCK_CALCULATE_ON_VALIDATE_ORDER','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SHIPMENT')       $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT", GETPOST('STOCK_CALCULATE_ON_SHIPMENT','alpha'),'chaine',0,'',$conf->entity);
	}
	// Mode of stock increase
	if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_BILL'
	|| $action == 'STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER'
	|| $action == 'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER')
	{
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_BILL", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER", '','chaine',0,'',$conf->entity);
		$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER", '','chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_BILL')           $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_BILL", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_BILL','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER','alpha'),'chaine',0,'',$conf->entity);
		if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER','alpha'),'chaine',0,'',$conf->entity);
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
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
    	$db->rollback();
        setEventMessage($langs->trans("Error"),'errors');
    }
}


/*
 * View
 */

llxHeader('',$langs->trans("StockSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
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
//}

// Title rule for stock decrease
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockManagementDecrease")."</td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>'."\n";
$var=true;

$found=0;

if (! empty($conf->facture->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("DeStockOnBill").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_BILL\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_BILL",$conf->global->STOCK_CALCULATE_ON_BILL,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n</td>\n</tr>\n";
	$found++;
}

if (! empty($conf->commande->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("DeStockOnValidateOrder").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_VALIDATE_ORDER\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_VALIDATE_ORDER",$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n</td>\n</tr>\n";
	$found++;
}

if (! empty($conf->expedition->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("DeStockOnShipment").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SHIPMENT\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SHIPMENT",$conf->global->STOCK_CALCULATE_ON_SHIPMENT,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n</td>\n</tr>\n";
	$found++;
}

if (! $found)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td colspan="2">'.$langs->trans("NoModueToManageStockDecrease").'</td>';
	print "</tr>\n";
}

print '</table>';

print '<br>';

// Title rule for stock increase
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockManagementIncrease")."</td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>'."\n";
$var=true;

$found=0;

if (! empty($conf->fournisseur->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("ReStockOnBill").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_BILL\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_BILL",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n</td>\n</tr>\n";
	$found++;
}

if (! empty($conf->fournisseur->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("ReStockOnValidateOrder").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n</td>\n</tr>\n";
	$found++;
}
if (! empty($conf->fournisseur->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("ReStockOnDispatchOrder").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER,1,$disabled);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'"'.$disabled.'>';
	print "</form>\n</td>\n</tr>\n";
	$found++;
}

if (! $found)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td colspan="2">'.$langs->trans("NoModueToManageStockIncrease").'</td>';
	print "</tr>\n";
}

print '</table>';

// Optio to force stock to be enough before adding a line into document
if ($conf->invoice->enabled || $conf->order->enabled || $conf->expedition->enabled)
{
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print "  <td>".$langs->trans("RuleForStockAvailability")."</td>\n";
	print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
	print '</tr>'."\n";

	if($conf->invoice->enabled) {
		$var = !$var;
		print "<tr ".$bc[$var].">";
		print '<td width="60%">'.$langs->trans("StockMustBeEnoughForInvoice").'</td>';
		print '<td width="160" align="right">';
		print "<form method=\"post\" action=\"stock.php\">";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print "<input type=\"hidden\" name=\"action\" value=\"STOCK_MUST_BE_ENOUGH_FOR_INVOICE\">";
		print $form->selectyesno("STOCK_MUST_BE_ENOUGH_FOR_INVOICE",$conf->global->STOCK_MUST_BE_ENOUGH_FOR_INVOICE,1);
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
		print '</form>';
		print "</td>\n";
		print "</tr>\n";
	}

	if($conf->order->enabled) {
		$var = !$var;
		print "<tr ".$bc[$var].">";
		print '<td width="60%">'.$langs->trans("StockMustBeEnoughForOrder").'</td>';
		print '<td width="160" align="right">';
		print "<form method=\"post\" action=\"stock.php\">";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print "<input type=\"hidden\" name=\"action\" value=\"STOCK_MUST_BE_ENOUGH_FOR_ORDER\">";
		print $form->selectyesno("STOCK_MUST_BE_ENOUGH_FOR_ORDER",$conf->global->STOCK_MUST_BE_ENOUGH_FOR_ORDER,1);
		print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
		print '</form>';
		print "</td>\n";
		print "</tr>\n";
	}

	if($conf->expedition->enabled) {
		$var = !$var;
		print "<tr ".$bc[$var].">";
		print '<td width="60%">'.$langs->trans("StockMustBeEnoughForShipment").'</td>';
		print '<td width="160" align="right">';
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
}

$virtualdiffersfromphysical=0;
if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)
	|| ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)
	) $virtualdiffersfromphysical=1;		// According to increase/decrease stock options, virtual and physical stock may differs.

if ($virtualdiffersfromphysical)
{
	print '<br>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print "  <td>".$langs->trans("RuleForStockReplenishment")." ".img_help('help',$langs->trans("VirtualDiffersFromPhysical"))."</td>\n";
	print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
	print '</tr>'."\n";
	$var = !$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("UseVirtualStockByDefault").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_USE_VIRTUAL_STOCK\">";
	print $form->selectyesno("STOCK_USE_VIRTUAL_STOCK",$conf->global->STOCK_USE_VIRTUAL_STOCK,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print "</td>\n";
	print "</tr>\n";
	print '</table>';
}


$var=true;
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "  <td>".$langs->trans("Other")."</td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>'."\n";

$var=!$var;

print "<tr ".$bc[$var].">";
print '<td width="60%">'.$langs->trans("UserWarehouseAutoCreate").'</td>';

print '<td width="160" align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"STOCK_USERSTOCK_AUTOCREATE\">";
print $form->selectyesno("STOCK_USERSTOCK_AUTOCREATE",$conf->global->STOCK_USERSTOCK_AUTOCREATE,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print "</td>\n";
print "</tr>\n";
print '<br>';

/* I keep the option/feature, but hidden to end users for the moment. If feature is used by module, no need to have users see it.
If not used by a module, I still need to understand in which case user may need this now we can set rule on product page.
if ($conf->global->PRODUIT_SOUSPRODUITS) 
{
	$var=!$var;
	
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("IndependantSubProductStock").'</td>';
	
	print '<td width="160" align="right">';
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

print '</table>';

llxFooter();

$db->close();
