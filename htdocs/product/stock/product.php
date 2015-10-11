<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador.gpcsolutions.fr>
 * Copyright (C) 2013-2015 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014      Cédric Gross         <c.gross@kreiz-it.fr>
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
 *	\file       htdocs/product/stock/product.php
 *	\ingroup    product stock
 *	\brief      Page to list detailed stock of a product
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';

$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");
if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");


$action=GETPOST("action");
$cancel=GETPOST('cancel');

// Security check
$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$stocklimit = GETPOST('stocklimit');
$desiredstock = GETPOST('desiredstock');
$cancel = GETPOST('cancel');
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit&stock',$id,'product&product','','',$fieldid);


/*
 *	Actions
 */

if ($cancel) $action='';

// Set stock limit
if ($action == 'setstocklimit')
{
    $product = new Product($db);
    $result=$product->fetch($id);
    $product->seuil_stock_alerte=$stocklimit;
    $result=$product->update($product->id,$user,1,0,1);
    if ($result < 0)
    	setEventMessage($product->error, 'errors');
    $action='';
}

// Set desired stock
if ($action == 'setdesiredstock')
{
    $product = new Product($db);
    $result=$product->fetch($id);
    $product->desiredstock=$desiredstock;
    $result=$product->update($product->id,$user,1,0,1);
    if ($result < 0)
    	setEventMessage($product->error, 'errors');
    $action='';
}


// Correct stock
if ($action == "correct_stock" && ! $cancel)
{
	if (! (GETPOST("id_entrepot") > 0))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Warehouse")), 'errors');
		$error++;
		$action='correction';
	}
	if (! GETPOST("nbpiece"))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("NumberOfUnit")), 'errors');
		$error++;
		$action='correction';
	}

	if (! empty($conf->productbatch->enabled))
	{
		$product = new Product($db);
		$result=$product->fetch($id);

		if ($product->hasbatch() && (! GETPOST("sellby")) && (! GETPOST("eatby")) && (! GETPOST("batch_number"))) {
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("atleast1batchfield")), 'errors');
			$error++;
			$action='correction';
		}
	}

	if (! $error)
	{
		$priceunit=price2num(GETPOST("price"));
		if (is_numeric(GETPOST("nbpiece")) && $id)
		{
			if (empty($product)) {
			$product = new Product($db);
			$result=$product->fetch($id);
			}
			if ($product->hasbatch()) {
				$d_eatby=dol_mktime(12, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
				$d_sellby=dol_mktime(12, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
				$result=$product->correct_stock_batch(
					$user,
					GETPOST("id_entrepot"),
					GETPOST("nbpiece"),
					GETPOST("mouvement"),
					GETPOST("label"),
					$priceunit,
					$d_eatby,
					$d_sellby,
					GETPOST('batch_number')
				);		// We do not change value of stock for a correction
			} else {
			$result=$product->correct_stock(
	    		$user,
	    		GETPOST("id_entrepot"),
	    		GETPOST("nbpiece"),
	    		GETPOST("mouvement"),
	    		GETPOST("label"),
	    		$priceunit
			);		// We do not change value of stock for a correction
			}

			if ($result > 0)
			{
	            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$product->id);
				exit;
			}
		}
	}
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && ! $cancel)
{
	if (! (GETPOST("id_entrepot_source") > 0) || ! (GETPOST("id_entrepot_destination") > 0))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Warehouse")), 'errors');
		$error++;
		$action='transfert';
	}
	if (! GETPOST("nbpiece"))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("NumberOfUnit")), 'errors');
		$error++;
		$action='transfert';
	}

	if (! $error)
	{
		if (GETPOST("id_entrepot_source") <> GETPOST("id_entrepot_destination"))
		{
			if (is_numeric(GETPOST("nbpiece")) && $id)
			{
				$product = new Product($db);
				$result=$product->fetch($id);

				$db->begin();

				$product->load_stock();	// Load array product->stock_warehouse

				// Define value of products moved
				$pricesrc=0;
				if (isset($product->stock_warehouse[GETPOST("id_entrepot_source")]->pmp)) $pricesrc=$product->stock_warehouse[GETPOST("id_entrepot_source")]->pmp;
				$pricedest=$pricesrc;

				//print 'price src='.$pricesrc.', price dest='.$pricedest;exit;

				// Remove stock
				$result1=$product->correct_stock(
	    			$user,
	    			GETPOST("id_entrepot_source"),
	    			GETPOST("nbpiece"),
	    			1,
	    			GETPOST("label"),
	    			$pricesrc
				);

				// Add stock
				$result2=$product->correct_stock(
	    			$user,
	    			GETPOST("id_entrepot_destination"),
	    			GETPOST("nbpiece"),
	    			0,
	    			GETPOST("label"),
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
					setEventMessage($product->error, 'errors');
					$db->rollback();
				}
			}
		}
	}
}


/*
 * View
 */

$formproduct=new FormProduct($db);


if ($id > 0 || $ref)
{
	$product = new Product($db);
	$result = $product->fetch($id,$ref);
	$product->load_stock();

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("",$langs->trans("CardProduct".$product->type),$help_url);

	if ($result > 0)
	{
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'stock', $titre, 0, $picto);

		$form = new Form($db);

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

		if ($conf->productbatch->enabled) {
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("l_sellby").')</td><td>';
			print $product->getLibStatut(2,2);
			print '</td></tr>';
		}

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

        // Stock
        print '<tr><td>'.$form->editfieldkey("StockLimit",'stocklimit',$product->seuil_stock_alerte,$product,$user->rights->produit->creer).'</td><td colspan="2">';
        print $form->editfieldval("StockLimit",'stocklimit',$product->seuil_stock_alerte,$product,$user->rights->produit->creer);
        print '</td></tr>';

        // Desired stock
        print '<tr><td>'.$form->editfieldkey("DesiredStock",'desiredstock',$product->desiredstock,$product,$user->rights->produit->creer).'</td><td colspan="2">';
        print $form->editfieldval("DesiredStock",'desiredstock',$product->desiredstock,$product,$user->rights->produit->creer);
        print '</td></tr>';

        // Real stock
        $product->load_stock();
		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$product->stock_reel;
		if ($product->seuil_stock_alerte && ($product->stock_reel < $product->seuil_stock_alerte)) print ' '.img_warning($langs->trans("StockLowerThanLimit"));
		print '</td>';
		print '</tr>';

		// Calculating a theorical value

		// If stock if stock increment is done on real sending
		if (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT))
		{
			// Stock theorique
			print '<tr><td>'.$langs->trans("VirtualStock").'</td>';
			print "<td>".$product->stock_theorique;
			if ($product->stock_theorique < $product->seuil_stock_alerte)
			{
				print ' '.img_warning($langs->trans("StockLowerThanLimit"));
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
			if (! empty($conf->commande->enabled))
			{
				if ($found) print '<br>'; else $found=1;
				print $langs->trans("CustomersOrdersRunning").': '.($product->stats_commande['qty']-$product->stats_expedition['qty']);
				$result=$product->load_stats_commande(0,'0');
				if ($result < 0) dol_print_error($db,$product->error);
				print ' ('.$langs->trans("Draft").': '.$product->stats_commande['qty'].')';
				//print '<br>';
				//print $langs->trans("CustomersSendingRunning").': '.$stock_sending_client;
			}

			// Nbre de commande fournisseurs en cours
			if (! empty($conf->fournisseur->enabled))
			{
				if ($found) print '<br>'; else $found=1;
				print $langs->trans("SuppliersOrdersRunning").': '.($product->stats_commande_fournisseur['qty']-$product->stats_reception['qty']);
				$result=$product->load_stats_commande_fournisseur(0,'0,1,2');
				if ($result < 0) dol_print_error($db,$product->error);
				print ' ('.$langs->trans("DraftOrWaitingApproved").': '.$product->stats_commande_fournisseur['qty'].')';
			}
			print '</td></tr>';
		}

		// If stock if stock increment is done on
		// TODO Add information when stock increment is done on other option

		// TODO Add also information on possible decrease stock accroding to stock decrease option


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
	if ($action == "correction")
	{
		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			function init_price()
			{
				if (jQuery("#mouvement").val() == \'0\') jQuery("#unitprice").removeAttr(\'disabled\');
				else jQuery("#unitprice").attr(\'disabled\',\'disabled\');
			}
			init_price();
			jQuery("#mouvement").change(function() {
				init_price();
			});
		});
		</script>';

		print_titre($langs->trans("StockCorrection"));
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'" method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="correct_stock">';
		print '<table class="border" width="100%">';

		// Warehouse
		print '<tr>';
		print '<td width="20%" class="fieldrequired" colspan="2">'.$langs->trans("Warehouse").'</td>';
		print '<td width="20%">';
		print $formproduct->selectWarehouses(($_GET["dwid"]?$_GET["dwid"]:GETPOST('id_entrepot')),'id_entrepot','',1);
		print '</td>';
		print '<td width="20%">';
		print '<select name="mouvement" id="mouvement" class="flat">';
		print '<option value="0">'.$langs->trans("Add").'</option>';
		print '<option value="1">'.$langs->trans("Delete").'</option>';
		print '</select></td>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" id="nbpiece" size="10" value="'.GETPOST("nbpiece").'"></td>';
		print '</tr>';

		// Label
		print '<tr>';
		print '<td width="20%" colspan="2">'.$langs->trans("Label").'</td>';
		print '<td colspan="2">';
		print '<input type="text" name="label" size="40" value="'.GETPOST("label").'">';
		print '</td>';
		print '<td width="20%">'.$langs->trans("UnitPurchaseValue").'</td><td width="20%"><input class="flat" name="price" id="unitprice" size="10" value="'.GETPOST("unitprice").'"></td>';
		print '</tr>';

		//eat-by date
		if ((! empty($conf->productbatch->enabled)) && $product->hasbatch()) {
			print '<tr>';
			print '<td width="15%">'.$langs->trans("l_eatby").'</td><td width="15%">';
			$form->select_date('','eatby','','',1,"");
			print '</td>';
			print '<td width="15%">'.$langs->trans("l_sellby").'</td><td width="15%">';
			$form->select_date('','sellby','','',1,"");
			print '</td>';
			print '<td width="15%">'.$langs->trans("batch_number").'</td><td width="15%">';
			print '<input type="text" name="batch_number" size="40" value="'.GETPOST("batch_number").'">';
			print '</td></tr>';
		}
		print '</table>';

		print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';
		print '</form>';
	}

	/*
	 * Transfer of units
	 */
	if ($action == "transfert")
	{
		print_titre($langs->trans("StockTransfer"));
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'" method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="transfert_stock">';
		print '<table class="border" width="100%">';

		print '<tr>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("WarehouseSource").'</td><td width="20%">';
		print $formproduct->selectWarehouses(($_GET["dwid"]?$_GET["dwid"]:GETPOST('id_entrepot_source')),'id_entrepot_source','',1);
		print '</td>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("WarehouseTarget").'</td><td width="20%">';
		print $formproduct->selectWarehouses(GETPOST('id_entrepot_destination'),'id_entrepot_destination','',1);
		print '</td>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input type="text" class="flat" name="nbpiece" size="10" value="'.dol_escape_htmltag(GETPOST("nbpiece")).'"></td>';
		print '</tr>';

		// Label
		print '<tr>';
		print '<td width="20%">'.$langs->trans("LabelMovement").'</td>';
		print '<td colspan="5">';
		print '<input type="text" name="label" size="80" value="'.dol_escape_htmltag(GETPOST("label")).'">';
		print '</td>';
		print '</tr>';

		print '</table>';

		print '<center><input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Save')).'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"></center>';

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


if (empty($action) && $product->id)
{
    print "<div class=\"tabsAction\">\n";

    if ($user->rights->stock->mouvement->creer)
    {
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'&amp;action=correction">'.$langs->trans("StockCorrection").'</a>';
    }

    if (($user->rights->stock->mouvement->creer) && !$product->hasbatch())
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'&amp;action=transfert">'.$langs->trans("StockMovement").'</a>';
	}

	print '</div>';
}




/*
 * Contenu des stocks
 */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="40%" colspan="4">'.$langs->trans("Warehouse").'</td>';
print '<td align="right">'.$langs->trans("NumberOfUnit").'</td>';
print '<td align="right">'.$langs->trans("AverageUnitPricePMPShort").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueShort").'</td>';
print '<td align="right">'.$langs->trans("SellPriceMin").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueSellShort").'</td>';
print '</tr>';
if ( (! empty($conf->productbatch->enabled)) && $product->hasbatch()) {
	print '<tr class="liste_titre"><td width="10%"></td><td align="right" width="10%">'.$langs->trans("l_eatby").'</td>';
	print '<td align="right" width="10%">'.$langs->trans("l_sellby").'</td>';
	print '<td align="right" width="10%">'.$langs->trans("batch_number").'</td>';
	print '<td align="right" colspan="5"></td>';
	print '</tr>';
}

$sql = "SELECT e.rowid, e.label, ps.reel, ps.pmp, ps.rowid as product_stock_id";
$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
$sql.= " ".MAIN_DB_PREFIX."product_stock as ps";
$sql.= " WHERE ps.reel != 0";
$sql.= " AND ps.fk_entrepot = e.rowid";
$sql.= " AND e.entity IN (".getEntity('stock',1).")";
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
		print '<td colspan="4">'.$entrepotstatic->getNomUrl(1).'</td>';
		print '<td align="right">'.$obj->reel.($obj->reel<0?' '.img_warning():'').'</td>';
		// PMP
		print '<td align="right">'.(price2num($obj->pmp)?price2num($obj->pmp,'MU'):'').'</td>'; // Ditto : Show PMP from movement or from product
		print '<td align="right">'.(price2num($obj->pmp)?price(price2num($obj->pmp*$obj->reel,'MT')):'').'</td>'; // Ditto : Show PMP from movement or from product
        // Sell price
		print '<td align="right">';
        if (empty($conf->global->PRODUIT_MULTI_PRICES)) print price(price2num($product->price,'MU'),1);
        else print $langs->trans("Variable");
        print '</td>'; // Ditto : Show PMP from movement or from product
        print '<td align="right">';
        if (empty($conf->global->PRODUIT_MULTI_PRICES)) print price(price2num($product->price*$obj->reel,'MT'),1).'</td>'; // Ditto : Show PMP from movement or from product
        else print $langs->trans("Variable");
		print '</tr>'; ;
		$total += $obj->reel;
		if (price2num($obj->pmp)) $totalwithpmp += $obj->reel;
		$totalvalue = $totalvalue + price2num($obj->pmp*$obj->reel,'MU'); // Ditto : Show PMP from movement or from product
        $totalvaluesell = $totalvaluesell + price2num($product->price*$obj->reel,'MU'); // Ditto : Show PMP from movement or from product
		//Batch Detail
		if ((! empty($conf->productbatch->enabled)) && $product->hasbatch()) {
			$details=Productbatch::findAll($db,$obj->product_stock_id);
			if ($details<0) dol_print_error($db);
			foreach ($details as $pdluo) {
				print "\n".'<tr><td width="10%"></td><td width="10%" align="right">'. dol_print_date($pdluo->eatby,'day') .'</td>';
				print '<td align="right" width="10%">'. dol_print_date($pdluo->sellby,'day') .'</td>';
				print '<td align="right" width="10%">'.$pdluo->batch.'</td>';
				print '<td align="right" width="10%">'.$pdluo->qty.($pdluo->qty<0?' '.img_warning():'').'</td>';
				print '<td colspan="4" width="50%"></td></tr>';
			}
		}
		$i++;
		$var=!$var;
	}
}
else dol_print_error($db);
print '<tr class="liste_total"><td align="right" class="liste_total" colspan="4">'.$langs->trans("Total").':</td>';
print '<td class="liste_total" align="right">'.$total.'</td>';
print '<td class="liste_total" align="right">';
print ($totalwithpmp?price($totalvalue/$totalwithpmp):'&nbsp;');
print '</td>';
print '<td class="liste_total" align="right">';
print price(price2num($totalvalue,'MT'),1);
print '</td>';
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MULTI_PRICES)) print ($total?price($totalvaluesell/$total,1):'&nbsp;');
else print $langs->trans("Variable");
print '</td>';
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MULTI_PRICES)) print price(price2num($totalvaluesell,'MT'),1);
else print $langs->trans("Variable");
print '</td>';
print "</tr>";
print "</table>";


llxFooter();

$db->close();
