<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/admin/stock.php
  \ingroup    stock
   \brief      Page d'administration/configuration du module gestion de stock
    \version    $Id$
*/
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");
$langs->load("stocks");

if (!$user->admin)
  accessforbidden();

/*
* Actions
*/
if ($_POST["action"] == 'stock_userstock')
{
  dolibarr_set_const($db, "STOCK_USERSTOCK", $_POST["stock_userstock"]);
  //On d�sactive l'autocr�ation si l'option "stock personnel" est d�sactiv�e
  if ($_POST["stock_userstock"] == 0)
  {
  	dolibarr_set_const($db, "STOCK_USERSTOCK_AUTOCREATE", 0);
  }
  Header("Location: stock.php");
  exit;
}
elseif ($_POST["action"] == 'stock_userstock_autocreate')
{
  dolibarr_set_const($db, "STOCK_USERSTOCK_AUTOCREATE", $_POST["stock_userstock_autocreate"]);
  Header("Location: stock.php");
  exit;
}
// Mode of stock changement 
elseif (    $_POST["action"] == 'stock_bill'
		 || $_POST["action"] == 'stock_validateorder'
		 || $_POST["action"] == 'stock_shipment')
{
	$count=0;
	$db->begin();
	$count+=dolibarr_set_const($db, "STOCK_CALCULATE_ON_BILL", '');
	$count+=dolibarr_set_const($db, "STOCK_CALCULATE_ON_VALIDATE_ORDER", '');
	$count+=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT", '');
	if ($_POST["action"] == 'stock_bill')          $count+=dolibarr_set_const($db, "STOCK_CALCULATE_ON_BILL", $_POST["stock_bill"]);
	if ($_POST["action"] == 'stock_validateorder') $count+=dolibarr_set_const($db, "STOCK_CALCULATE_ON_VALIDATE_ORDER", $_POST["stock_validateorder"]);
	if ($_POST["action"] == 'stock_shipment')      $count+=dolibarr_set_const($db, "STOCK_CALCULATE_ON_SHIPMENT", $_POST["stock_shipment"]);
	if ($count == 4)
	{
		$db->commit();
  		Header("Location: stock.php");
  		exit;
	}
	else
	{	
		$db->rollback();
    	dolibarr_print_error("Error in some requests", LOG_ERR);
	}
}	



/*
 * View
 */

llxHeader('',$langs->trans("StockSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("StockSetup"),$linkback,'setup');
print '<br>';

$html=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "  <td>".$langs->trans("Parameters")."</td>\n";
print "  <td align=\"right\" width=\"160\">".$langs->trans("Value")."</td>\n";
print '</tr>'."\n";

/*
 * Formulaire parametres divers
 */
// sousproduits activation/desactivation
$var=!$var;

print "<tr ".$bc[$var].">";
print '<td width="60%">'.$langs->trans("UserWarehouse").'</td>';
print '<td width="160" align="right">';
print "<form method=\"post\" action=\"stock.php\">";
print "<input type=\"hidden\" name=\"action\" value=\"stock_userstock\">";
print $html->selectyesno("stock_userstock",$conf->global->STOCK_USERSTOCK,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</form>\n</td>\n</tr>\n";

if ($conf->global->STOCK_USERSTOCK == 1)
{
  $var=!$var;

  print "<tr ".$bc[$var].">";
  print '<td width="60%">'.$langs->trans("UserWarehouseAutoCreate").'</td>';
  
  print '<td width="160" align="right">';
  print "<form method=\"post\" action=\"stock.php\">";
  print "<input type=\"hidden\" name=\"action\" value=\"stock_userstock_autocreate\">";
  print $html->selectyesno("stock_userstock_autocreate",$conf->global->STOCK_USERSTOCK_AUTOCREATE,1);
  
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print '</form>';
  print "</td>\n";   
  print "</tr>\n";
}


print '<tr class="liste_titre">';
print "  <td>".$langs->trans("RuleForStockManagement")."</td>\n";
print "  <td align=\"right\" width=\"160\">".$langs->trans("Value")."</td>\n";
print '</tr>'."\n";
$var=true;

if ($conf->facture->enabled)
{
	$var=!$var;
  print "<tr ".$bc[$var].">";
  print '<td width="60%">'.$langs->trans("DeStockReStockOnBill").'</td>';
  print '<td width="160" align="right">';
  print "<form method=\"post\" action=\"stock.php\">";
  print "<input type=\"hidden\" name=\"action\" value=\"stock_bill\">";
  print $html->selectyesno("stock_bill",$conf->global->STOCK_CALCULATE_ON_BILL,1);
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print "</form>\n</td>\n</tr>\n";
}

if ($conf->commande->enabled)
{
	$var=!$var;
  print "<tr ".$bc[$var].">";
  print '<td width="60%">'.$langs->trans("DeStockReStockOnValidateOrder").'</td>';
  print '<td width="160" align="right">';
  print "<form method=\"post\" action=\"stock.php\">";
  print "<input type=\"hidden\" name=\"action\" value=\"stock_validateorder\">";
  print $html->selectyesno("stock_validateorder",$conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER,1);
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print "</form>\n</td>\n</tr>\n";
}

if ($conf->expedition->enabled)
{
	$var=!$var;
  print "<tr ".$bc[$var].">";
  print '<td width="60%">'.$langs->trans("DeStockReStockOnShipment").'</td>';
  print '<td width="160" align="right">';
  print "<form method=\"post\" action=\"stock.php\">";
  print "<input type=\"hidden\" name=\"action\" value=\"stock_shipment\">";
  print $html->selectyesno("stock_shipment",$conf->global->STOCK_CALCULATE_ON_SHIPMENT,1);
  print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
  print "</form>\n</td>\n</tr>\n";
}

print '</table>';
$db->close();

llxFooter('$Date$ - $Revision$');
?>
