<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013      Florian Henry       <florian.henry@open-concept.pro>
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

if ($action == 'STOCK_USERSTOCK_AUTOCREATE')
{
	$db->begin();
	$res = dolibarr_set_const($db, "STOCK_USERSTOCK_AUTOCREATE", GETPOST('STOCK_USERSTOCK_AUTOCREATE','alpha'),'chaine',0,'',$conf->entity);
}
// Mode of stock decrease
if ($action == 'STOCK_CALCULATE_ON_BILL'
|| $action == 'STOCK_CALCULATE_ON_VALIDATE_ORDER'
|| $action == 'STOCK_CALCULATE_ON_SHIPMENT')
{
	$db->begin();
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
	$db->begin();
	$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_BILL", '','chaine',0,'',$conf->entity);
	$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER", '','chaine',0,'',$conf->entity);
	$res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER", '','chaine',0,'',$conf->entity);
	if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_BILL')           $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_BILL", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_BILL','alpha'),'chaine',0,'',$conf->entity);
	if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER','alpha'),'chaine',0,'',$conf->entity);
	if ($action == 'STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') $res=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER", GETPOST('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER','alpha'),'chaine',0,'',$conf->entity);
}

if($action == 'USE_VIRTUAL_STOCK') {
    $db->begin();
    $res = dolibarr_set_const($db, "USE_VIRTUAL_STOCK", GETPOST('USE_VIRTUAL_STOCK','alpha'),'chaine',0,'',$conf->entity);
}

if($action)
{
	if (! $res > 0) $error++;

 	if (! $error)
    {
    	$db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
    	$db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}


/*
 * View
 */

llxHeader('',$langs->trans("StockSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("StockSetup"),$linkback,'setup');
print '<br>';

$h = 0;

$head[$h][0] = DOL_URL_ROOT."/admin/stock.php";
$head[$h][1] = $langs->trans("Miscellaneous");
$head[$h][2] = 'general';
$hselected=$h;
$h++;

dol_fiche_head($head, $hselected, $langs->trans("ModuleSetup"));

$form=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "  <td>".$langs->trans("Parameters")."</td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>'."\n";

/*
 * Formulaire parametres divers
 */

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

print "<tr ".$bc[$var].">";
print '<td width="60%">'.$langs->trans("UseVirtualStock").'</td>';
print '<td width="160" align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<input type=\"hidden\" name=\"action\" value=\"USE_VIRTUAL_STOCK\">";
print $form->selectyesno("USE_VIRTUAL_STOCK",$conf->global->USE_VIRTUAL_STOCK,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print "</td>\n";
print "</tr>\n";

print '<br>';
print '</table>';
print '<br>';

// Title rule for stock decrease
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockManagementDecrease")."</td>\n";
print "  <td align=\"right\" width=\"160\">&nbsp;</td>\n";
print '</tr>'."\n";
$var=true;

if (! empty($conf->facture->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("DeStockOnBill").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_BILL\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_BILL",$conf->global->STOCK_CALCULATE_ON_BILL,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</form>\n</td>\n</tr>\n";
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
	print $form->selectyesno("STOCK_CALCULATE_ON_VALIDATE_ORDER",$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</form>\n</td>\n</tr>\n";
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
	print $form->selectyesno("STOCK_CALCULATE_ON_SHIPMENT",$conf->global->STOCK_CALCULATE_ON_SHIPMENT,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</form>\n</td>\n</tr>\n";
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

if (! empty($conf->fournisseur->enabled))
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	print '<td width="60%">'.$langs->trans("ReStockOnBill").'</td>';
	print '<td width="160" align="right">';
	print "<form method=\"post\" action=\"stock.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"STOCK_CALCULATE_ON_SUPPLIER_BILL\">";
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_BILL",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</form>\n</td>\n</tr>\n";
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
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</form>\n</td>\n</tr>\n";
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
	print $form->selectyesno("STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER",$conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER,1);
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print "</form>\n</td>\n</tr>\n";
}

print '</table>';

dol_htmloutput_mesg($mesg);

$db->close();

llxFooter();
?>
