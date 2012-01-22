<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/product/stock/product.php
 *	\ingroup    product stock
 *	\brief      Page to list detailed stock of a product
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");

$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");

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

// Set stock limit
if ($_POST['action'] == 'setstocklimit')
{
    $product = new Product($db);
    $result=$product->fetch($_POST['id']);
    $product->seuil_stock_alerte=$_POST["stocklimit"];
    $result=$product->update($product->id,$user,1,0,1);
    if ($result < 0)
    {
        $mesg=join(',',$product->errors);
    }
    $POST["action"]="";
    $id=$_POST["id"];
    $_GET["id"]=$_POST["id"];
}

// Correct stock
if ($_POST["action"] == "correct_stock" && ! $_POST["cancel"])
{
	if (is_numeric($_POST["nbpiece"]))
	{
		$product = new Product($db);
		$result=$product->fetch($_GET["id"]);

		$result=$product->correct_stock(
    		$user,
    		$_POST["id_entrepot"],
    		$_POST["nbpiece"],
    		$_POST["mouvement"],
    		$_POST["label"],
    		0
		);		// We do not change value of stock for a correction

		if ($result > 0)
		{
			header("Location: product.php?id=".$product->id);
			exit;
		}
	}
}

// Transfer stock from a warehouse to another warehouse
if ($_POST["action"] == "transfert_stock" && ! $_POST["cancel"])
{
	if ($_POST["id_entrepot_source"] <> $_POST["id_entrepot_destination"])
	{
		if (is_numeric($_POST["nbpiece"]))
		{
			$product = new Product($db);
			$result=$product->fetch($_GET["id"]);

			$db->begin();

			$product->load_stock();	// Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc=0;
			if (isset($product->stock_warehouse[$_POST["id_entrepot_source"]]->pmp)) $pricesrc=$product->stock_warehouse[$_POST["id_entrepot_source"]]->pmp;
			$pricedest=$pricesrc;

			//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

			// Remove stock
			$result1=$product->correct_stock(
    			$user,
    			$_POST["id_entrepot_source"],
    			$_POST["nbpiece"],
    			1,
    			$_POST["label"],
    			$pricesrc
			);

			// Add stock
			$result2=$product->correct_stock(
    			$user,
    			$_POST["id_entrepot_destination"],
    			$_POST["nbpiece"],
    			0,
    			$_POST["label"],
    			$pricedest
			);

			if ($result1 >= 0 && $result2 >= 0)
			{
				$db->commit();
                header("Location: product.php?id=".$product->id);
				exit;
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

		$form = new Form($db);

		print($mesg);

		print '<table class="border" width="100%">';

		// Ref
		print '<tr>';
		print '<td width="30%">'.$langs->trans("Ref").'</td><td>';
		print $form->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
		print $product->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
		print $product->getLibStatut(2,1);
		print '</td></tr>';

		// PMP
		print '<tr><td>'.$langs->trans("AverageUnitPricePMP").'</td>';
		print '<td>'.price($product->pmp).' '.$langs->trans("HT").'</td>';
		print '</tr>';

        // Sell price
        print '<tr><td>'.$langs->trans("SellPriceMin").'</td>';
        print '<td>';
		if (empty($conf->global->PRODUIT_MULTIPRICES)) print price($product->price).' '.$langs->trans("HT");
        else print $langs->trans("Variable");
        print '</td>';
        print '</tr>';

        // Real stock
		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$product->stock_reel;
		if ($product->seuil_stock_alerte && ($product->stock_reel < $product->seuil_stock_alerte)) print ' '.img_warning($langs->trans("StockTooLow"));
		print '</td>';
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
        print '<tr><td>'.$form->editfieldkey("StockLimit",'stocklimit',$product->seuil_stock_alerte,$product,$user->rights->produit->creer).'</td><td colspan="2">';
        print $form->editfieldval("StockLimit",'stocklimit',$product->seuil_stock_alerte,$product,$user->rights->produit->creer);
        print '</td></tr>';

		// Last movement
		$sql = "SELECT max(m.datem) as datem";
		$sql.= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
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
		if ($lastmovementdate)
		{
		    print dol_print_date($lastmovementdate,'dayhour').' ';
		    print '(<a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$product->id.'">'.$langs->trans("FullList").'</a>)';
		}
		else
		{
		     print '<a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$product->id.'">'.$langs->trans("None").'</a>';
		}
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
		print '<table class="border" width="100%">';

		// Warehouse
		print '<tr>';
		print '<td width="20%">'.$langs->trans("Warehouse").'</td>';
		print '<td width="20%">';
		print $formproduct->selectWarehouses($_GET["dwid"],'id_entrepot','',1);
		print '</td>';
		print '<td width="20%">';
		print '<select name="mouvement" class="flat">';
		print '<option value="0">'.$langs->trans("Add").'</option>';
		print '<option value="1">'.$langs->trans("Delete").'</option>';
		print '</select></td>';
		print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" size="10" value=""></td>';
		print '</tr>';

		// Label
		print '<tr>';
		print '<td width="20%">'.$langs->trans("Label").'</td>';
		print '<td colspan="4">';
		print '<input type="text" name="label" size="40" value="">';
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';
		print '</form>';

	}

	/*
	 * Transfer of units
	 */
	if ($_GET["action"] == "transfert")
	{
		print_titre($langs->trans("Transfer"));
		print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="transfert_stock">';
		print '<table class="border" width="100%">';

		print '<tr>';
		print '<td width="20%">'.$langs->trans("WarehouseSource").'</td><td width="20%">';
		print $formproduct->selectWarehouses($_GET["dwid"],'id_entrepot_source','',1);
		print '</td>';
		print '<td width="20%">'.$langs->trans("WarehouseTarget").'</td><td width="20%">';
		print $formproduct->selectWarehouses('','id_entrepot_destination','',1);
		print '</td>';
		print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input name="nbpiece" size="10" value=""></td>';
		print '</tr>';

		// Label
		print '<tr>';
		print '<td width="20%">'.$langs->trans("Label").'</td>';
		print '<td colspan="5">';
		print '<input type="text" name="label" size="40" value="">';
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

		print '</form>';

	}

	/*
	 * Set initial stock
	 */
	/*
	if ($_GET["action"] == "definir")
	{
		print_titre($langs->trans("SetStock"));
		print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="create_stock">';
		print '<table class="border" width="100%"><tr>';
		print '<td width="20%">'.$langs->trans("Warehouse").'</td><td width="40%">';
		print $formproduct->selectWarehouses('','id_entrepot','',1);
		print '</td><td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
		print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td></tr>';
		print '</table>';
		print '</form>';
	}
	*/
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

//if (empty($_GET["action"]))
//{
    if ($user->rights->stock->creer)
    {
        print '<a class="butAction" href="product.php?id='.$product->id.'&amp;action=correction">'.$langs->trans("StockCorrection").'</a>';
    }

    if ($user->rights->stock->mouvement->creer)
	{
		print '<a class="butAction" href="product.php?id='.$product->id.'&amp;action=transfert">'.$langs->trans("StockMovement").'</a>';
	}
//}
print '</div>';




/*
 * Contenu des stocks
 */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="40%">'.$langs->trans("Warehouse").'</td>';
print '<td align="right">'.$langs->trans("NumberOfUnit").'</td>';
print '<td align="right">'.$langs->trans("AverageUnitPricePMPShort").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueShort").'</td>';
print '<td align="right">'.$langs->trans("SellPriceMin").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueSellShort").'</td>';
print '</tr>';

$sql = "SELECT e.rowid, e.label, ps.reel, ps.pmp";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
$sql.= " ".MAIN_DB_PREFIX."product_stock as ps";
$sql.= " WHERE ps.reel != 0";
$sql.= " AND ps.fk_entrepot = e.rowid";
$sql.= " AND e.entity = ".$conf->entity;
$sql.= " AND ps.fk_product = ".$product->id;
$sql.= " ORDER BY e.label";

$entrepotstatic=new Entrepot($db);
$total=0;
$totalvalue=$totalvaluesell=0;

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$total=$totalwithpmp;
	$i=0; $var=false;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$entrepotstatic->id=$obj->rowid;
		$entrepotstatic->libelle=$obj->label;
		print '<tr '.$bc[$var].'>';
		print '<td>'.$entrepotstatic->getNomUrl(1).'</td>';
		print '<td align="right">'.$obj->reel.($obj->reel<0?' '.img_warning():'').'</td>';
		// PMP
		print '<td align="right">'.(price2num($obj->pmp)?price2num($obj->pmp,'MU'):'').'</td>'; // Ditto : Show PMP from movement or from product
		print '<td align="right">'.(price2num($obj->pmp)?price(price2num($obj->pmp*$obj->reel,'MT')):'').'</td>'; // Ditto : Show PMP from movement or from product
        // Sell price
		print '<td align="right">';
        if (empty($conf->global->PRODUIT_MUTLI_PRICES)) print price(price2num($product->price,'MU'));
        else print $langs->trans("Variable");
        print '</td>'; // Ditto : Show PMP from movement or from product
        print '<td align="right">';
        if (empty($conf->global->PRODUIT_MUTLI_PRICES)) print price(price2num($product->price*$obj->reel,'MT')).'</td>'; // Ditto : Show PMP from movement or from product
        else print $langs->trans("Variable");
		print '</tr>'; ;
		$total += $obj->reel;
		if (price2num($obj->pmp)) $totalwithpmp += $obj->reel;
		$totalvalue = $totalvalue + price2num($obj->pmp*$obj->reel,'MU'); // Ditto : Show PMP from movement or from product
        $totalvaluesell = $totalvaluesell + price2num($product->price*$obj->reel,'MU'); // Ditto : Show PMP from movement or from product
		$i++;
		$var=!$var;
	}
}
else dol_print_error($db);
print '<tr class="liste_total"><td align="right" class="liste_total">'.$langs->trans("Total").':</td>';
print '<td class="liste_total" align="right">'.$total.'</td>';
print '<td class="liste_total" align="right">';
print ($totalwithpmp?price($totalvalue/$totalwithpmp):'&nbsp;');
print '</td>';
print '<td class="liste_total" align="right">';
print price(price2num($totalvalue,'MT'));
print '</td>';
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MUTLI_PRICES)) print ($total?price($totalvaluesell/$total):'&nbsp;');
else print $langs->trans("Variable");
print '</td>';
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MUTLI_PRICES)) print price(price2num($totalvaluesell,'MT'));
else print $langs->trans("Variable");
print '</td>';
print "</tr>";
print "</table>";



$db->close();


llxFooter();
?>
