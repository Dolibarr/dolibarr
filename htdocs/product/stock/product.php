<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/product/stock/product.php
 *	\ingroup    product, stock
 *	\brief      Page to list detailed stock of a product
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formproduct.class.php");

$langs->load("products");
$langs->load("orders");
$langs->load("bills");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit&stock',$id,'product','','',$fieldid);

$mesg = '';

/*
 *	Actions
 */

if ($_POST["action"] == "create_stock" && ! $_POST["cancel"])
{
	$product = new Product($db);
	$product->id = $_GET["id"];
	$product->create_stock($user, $_POST["id_entrepot"], $_POST["nbpiece"]);
}

if ($_POST["action"] == "correct_stock" && ! $_POST["cancel"])
{
	if (is_numeric($_POST["nbpiece"]))
	{
		$product = new Product($db);
		$product->id = $_GET["id"];
		$product->correct_stock($user,
		$_POST["id_entrepot"],
		$_POST["nbpiece"],
		$_POST["mouvement"]);
	}
}

if ($_POST["action"] == "transfert_stock" && ! $_POST["cancel"])
{
	if ($_POST["id_entrepot_source"] <> $_POST["id_entrepot_destination"])
	{
		if (is_numeric($_POST["nbpiece"]))
		{
			$product = new Product($db);
			$product->id = $_GET["id"];

			$db->begin();

			$result1=$product->correct_stock($user,
			$_POST["id_entrepot_source"],
			$_POST["nbpiece"],
			1);

			$result2=$product->correct_stock($user,
			$_POST["id_entrepot_destination"],
			$_POST["nbpiece"],
			0);

			if ($result1 >= 0 && $result2 >= 0)
			{
				$db->commit();
			}
			else
			{
				$mesg=$product->error;
				$db->rollback();
			}
		}
	}
}


/*
 * View
 */

$formproduct=new FormProduct($db);


if ($_GET["id"] || $_GET["ref"])
{
	$product = new Product($db);
	if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("",$langs->trans("CardProduct".$product->type),$help_url);

	if ($result > 0)
	{
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'stock', $titre, 0, $picto);

		$html = new Form($db);

		print($mesg);

		print '<table class="border" width="100%">';

		// Ref
		print '<tr>';
		print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
		print $html->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '</tr>';

		// Price
		print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">';
		if ($product->price_base_type == 'TTC')
		{
			print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
		}
		else
		{
			print price($product->price).' '.$langs->trans($product->price_base_type);
		}
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>';
		print $product->getLibStatut(2);
		print '</td></tr>';

		// Stock physique
		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$product->stock_reel.'</td>';
		print '</tr>';

		// Calculating a theorical value of stock if stock increment is done on real sending
		if ($conf->global->STOCK_CALCULATE_ON_SHIPMENT)
		{
			$stock_commande_client=$stock_commande_fournisseur=0;

			if ($conf->commande->enabled)
			{
				$result=$product->load_stats_commande(0,'1,2');
				if ($result < 0) dol_print_error($db,$product->error);
				$stock_commande_client=$product->stats_commande['qty'];
			}
			if ($conf->fournisseur->enabled)
			{
				$result=$product->load_stats_commande_fournisseur(0,'3');
				if ($result < 0) dol_print_error($db,$product->error);
				$stock_commande_fournisseur=$product->stats_commande_fournisseur['qty'];
			}

			$product->stock_theorique=$product->stock_reel-($stock_commande_client+$stock_sending_client)+$stock_commande_fournisseur;

			// Stock theorique
			print '<tr><td>'.$langs->trans("VirtualStock").'</td>';
			print "<td>".$product->stock_theorique;
			if ($product->stock_theorique < $product->seuil_stock_alerte)
			{
				print ' '.img_warning($langs->trans("StockTooLow"));
			}
			print '</td>';
			print '</tr>';

			print '<tr><td>';
			if ($product->stock_theorique != $product->stock_reel) print $langs->trans("StockDiffPhysicTeoric");
			else print $langs->trans("RunningOrders");
			print '</td>';
			print '<td>';

			$found=0;

			// Nbre de commande clients en cours
			if ($conf->commande->enabled)
			{
				if ($found) print '<br>'; else $found=1;
				print $langs->trans("CustomersOrdersRunning").': '.($stock_commande_client+$stock_sending_client);
				$result=$product->load_stats_commande(0,'0');
				if ($result < 0) dol_print_error($db,$product->error);
				print ' ('.$langs->trans("Draft").': '.$product->stats_commande['qty'].')';
				//print '<br>';
				//print $langs->trans("CustomersSendingRunning").': '.$stock_sending_client;
			}

			// Nbre de commande fournisseurs en cours
			if ($conf->fournisseur->enabled)
			{
				if ($found) print '<br>'; else $found=1;
				print $langs->trans("SuppliersOrdersRunning").': '.$stock_commande_fournisseur;
				$result=$product->load_stats_commande_fournisseur(0,'0,1,2');
				if ($result < 0) dol_print_error($db,$product->error);
				print ' ('.$langs->trans("DraftOrWaitingApproved").': '.$product->stats_commande_fournisseur['qty'].')';
			}
			print '</td></tr>';
		}

		// Stock
		print '<tr><td>'.$langs->trans("StockLimit").'</td>';
		print '<td>'.$product->seuil_stock_alerte.'</td>';
		print '</tr>';

		// Last movement
		$sql = "SELECT max(m.datem) as datem";
		$sql.= " FROM llx_stock_mouvement as m";
		$sql.= " WHERE m.fk_product = '".$product->id."'";
		$resqlbis = $db->query($sql);
		if ($resqlbis)
		{
			$obj = $db->fetch_object($resqlbis);
			$lastmovementdate=$db->jdate($obj->datem);
		}
		else
		{
			dol_print_error($db);
		}
		print '<tr><td valign="top">'.$langs->trans("LastMovement").'</td><td colspan="3">';
		if ($lastmovementdate) print dol_print_date($lastmovementdate,'dayhour').' ';
		print '(<a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$product->id.'">'.$langs->trans("FullList").'</a>)';
		print "</td></tr>";

		print "</table>";

	}
	print '</div>';

	/*
	 * Correct stock
	 */
	if ($_GET["action"] == "correction")
	{
		print_titre($langs->trans("StockCorrection"));
		print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="correct_stock">';
		print '<table class="border" width="100%"><tr>';
		print '<td width="20%">'.$langs->trans("Warehouse").'</td>';

		// Entrepot
		print '<td width="20%">';
		$formproduct->selectWarehouses($_GET["dwid"],'id_entrepot','',1);
		print '</td>';
		print '<td width="20%">';
		print '<select name="mouvement" class="flat">';
		print '<option value="0">'.$langs->trans("Add").'</option>';
		print '<option value="1">'.$langs->trans("Delete").'</option>';
		print '</select></td>';
		print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" size="10" value=""></td>';

		print '</tr>';
		print '<tr><td colspan="5" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
		print '</table>';
		print '</form>';

	}

	/*
	 * Transfert of units
	 */
	if ($_GET["action"] == "transfert")
	{
		print_titre($langs->trans("Transfer"));
		print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="transfert_stock">';
		print '<table class="border" width="100%"><tr>';
		print '<td width="20%">'.$langs->trans("WarehouseSource").'</td><td width="20%">';
		$formproduct->selectWarehouses($_GET["dwid"],'id_entrepot_source','',1);
		print '</td>';

		print '<td width="20%">'.$langs->trans("WarehouseTarget").'</td><td width="20%">';
		$formproduct->selectWarehouses('','id_entrepot_destination','',1);
		print '</td>';

		print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
		print '<tr><td colspan="6" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
		print '</table>';
		print '</form>';

	}

	/*
	 * Set initial stock
	 */
	if ($_GET["action"] == "definir")
	{
		print_titre($langs->trans("SetStock"));
		print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="create_stock">';
		print '<table class="border" width="100%"><tr>';
		print '<td width="20%">'.$langs->trans("Warehouse").'</td><td width="40%">';
		$formproduct->selectWarehouses('','id_entrepot','',1);
		print '</td><td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
		print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td></tr>';
		print '</table>';
		print '</form>';
	}
}
else
{
	dol_print_error();
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "<div class=\"tabsAction\">\n";

if ($_GET["action"] == '' )
{
	if ($user->rights->stock->mouvement->creer)
	{
		print '<a class="butAction" href="product.php?id='.$product->id.'&amp;action=transfert">'.$langs->trans("StockMovement").'</a>';
	}

	if ($user->rights->stock->creer)
	{
		print '<a class="butAction" href="product.php?id='.$product->id.'&amp;action=correction">'.$langs->trans("StockCorrection").'</a>';
	}
}
print '</div>';




/*
 * Contenu des stocks
 */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="40%">'.$langs->trans("Warehouse").'</td>';
print '<td align="right">'.$langs->trans("NumberOfUnit").'</td>';
print '<td align="right">'.$langs->trans("AverageUnitPricePMPShort").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueShort").'</td>';
print '</tr>';

$sql = "SELECT e.rowid, e.label, ps.reel, ps.pmp";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql.= ", ".MAIN_DB_PREFIX."product_stock as ps";
$sql.= " WHERE ps.reel != 0";
$sql.= " AND ps.fk_entrepot = e.rowid";
$sql.= " AND e.entity = ".$conf->entity;
$sql.= " AND ps.fk_product = ".$product->id;
$sql.= " ORDER BY lower(e.label)";

$entrepotstatic=new Entrepot($db);
$total=0;
$totalvalue=0;

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i=0; $total=0; $var=false;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$entrepotstatic->id=$obj->rowid;
		$entrepotstatic->libelle=$obj->label;
		print '<tr '.$bc[$var].'>';
		print '<td>'.$entrepotstatic->getNomUrl(1).'</td>';
		print '<td align="right">'.$obj->reel.'</td>';
		print '<td align="right">'.price2num($obj->pmp,'MU').'</td>';
		print '<td align="right">'.price(price2num($obj->pmp*$obj->reel,'MT')).'</td>';
		print '</tr>'; ;
		$total = $total + $obj->reel;
		$totalvalue = $totalvalue + price2num($obj->pmp,'MT')*$obj->reel;
		$i++;
		$var=!$var;
	}
}
print '<tr class="liste_total"><td align="right" class="liste_total">'.$langs->trans("Total").':</td>';
print '<td class="liste_total" align="right">'.$total.'</td>';
print '<td class="liste_total" align="right">&nbsp;</td>';
print '<td class="liste_total" align="right">'.price($totalvalue).'</td>';
print "</tr>";
print "</table>";



$db->close();


llxFooter('$Date$ - $Revision$');
?>
