<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador.gpcsolutions.fr>
 * Copyright (C) 2013      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2014-2015 Cédric Gross         <c.gross@kreiz-it.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';

$langs->load("products");
$langs->load("orders");
$langs->load("bills");
$langs->load("stocks");
$langs->load("sendings");
if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");

$backtopage=GETPOST('backtopage');
$action=GETPOST("action");
$cancel=GETPOST('cancel');

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$stocklimit = GETPOST('stocklimit');
$desiredstock = GETPOST('desiredstock');
$cancel = GETPOST('cancel');
$fieldid = isset($_GET["ref"])?'ref':'rowid';
$d_eatby=dol_mktime(12, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
$d_sellby=dol_mktime(12, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
$pdluoid=GETPOST('pdluoid','int');

// Security check
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
    $result=$product->update($product->id,$user,0,'update');
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
    $result=$product->update($product->id,$user,0,'update');
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
			if ($product->hasbatch())
			{
				$result=$product->correct_stock_batch(
					$user,
					GETPOST("id_entrepot"),
					GETPOST("nbpiece"),
					GETPOST("mouvement"),
					GETPOST("label"),		// label movement
					$priceunit,
					$d_eatby,
					$d_sellby,
					GETPOST('batch_number'),
					GETPOST('inventorycode')
				);		// We do not change value of stock for a correction
			}
			else
			{
				$result=$product->correct_stock(
		    		$user,
		    		GETPOST("id_entrepot"),
		    		GETPOST("nbpiece"),
		    		GETPOST("mouvement"),
		    		GETPOST("label"),
		    		$priceunit,
					GETPOST('inventorycode')
				);		// We do not change value of stock for a correction
			}

			if ($result > 0)
			{
				if ($backtopage)
				{
					header("Location: ".$backtopage);
					exit;
				}
				else
				{
	            	header("Location: ".$_SERVER["PHP_SELF"]."?id=".$product->id);
					exit;
				}
			}
			else
			{
			    setEventMessage($product->error,'errors');
			    $action='correction';
			}
		}
	}
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && ! $cancel)
{
	if (! (GETPOST("id_entrepot_source",'int') > 0) || ! (GETPOST("id_entrepot_destination",'int') > 0))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Warehouse")), 'errors');
		$error++;
		$action='transfert';
	}
	if (! GETPOST("nbpiece",'int'))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("NumberOfUnit")), 'errors');
		$error++;
		$action='transfert';
	}
	if (GETPOST("id_entrepot_source",'int') == GETPOST("id_entrepot_destination",'int'))
	{
		setEventMessage($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), 'errors');
		$error++;
		$action='transfert';
	}

	if (! $error)
	{
		if ($id)
		{
			$product = new Product($db);
			$result=$product->fetch($id);

			$db->begin();

			$product->load_stock();	// Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc=0;
			//if (isset($product->stock_warehouse[GETPOST("id_entrepot_source")]->pmp)) $pricesrc=$product->stock_warehouse[GETPOST("id_entrepot_source")]->pmp;
			if (isset($product->pmp)) $pricesrc=$product->pmp;
			$pricedest=$pricesrc;

			if ($product->hasbatch())
			{
				$pdluo = new Productbatch($db);

				if ($pdluoid > 0)
				{
					$result=$pdluo->fetch($pdluoid);
					if ($result)
					{
						$srcwarehouseid=$pdluo->warehouseid;
						$batch=$pdluo->batch;
						$eatby=$pdluo->eatby;
						$sellby=$pdluo->sellby;
					}
					else
					{
						setEventMessages($pdluo->error, $pdluo->errors, 'errors');
						$error++;
					}
				}
				else
				{
					$srcwarehouseid=GETPOST('id_entrepot_source','int');
					$batch=GETPOST('batch_number');
					$eatby=$d_eatby;
					$sellby=$d_sellby;
				}

				if (! $error)
				{
					// Remove stock
					$result1=$product->correct_stock_batch(
						$user,
						$srcwarehouseid,
						GETPOST("nbpiece",'int'),
						1,
						GETPOST("label",'san_alpha'),
						$pricesrc,
						$eatby,$sellby,$batch,
						GETPOST('inventorycode')
					);
					// Add stock
					$result2=$product->correct_stock_batch(
						$user,
						GETPOST("id_entrepot_destination",'int'),
						GETPOST("nbpiece",'int'),
						0,
						GETPOST("label",'san_alpha'),
						$pricedest,
						$eatby,$sellby,$batch,
						GETPOST('inventorycode')
					);
				}
			}
			else
			{
				// Remove stock
				$result1=$product->correct_stock(
					$user,
					GETPOST("id_entrepot_source"),
					GETPOST("nbpiece"),
					1,
					GETPOST("label"),
					$pricesrc,
					GETPOST('inventorycode')
				);

				// Add stock
				$result2=$product->correct_stock(
					$user,
					GETPOST("id_entrepot_destination"),
					GETPOST("nbpiece"),
					0,
					GETPOST("label"),
					$pricedest,
					GETPOST('inventorycode')
				);
			}
			if (! $error && $result1 >= 0 && $result2 >= 0)
			{
				$db->commit();

				if ($backtopage)
				{
					header("Location: ".$backtopage);
					exit;
				}
				else
				{
					header("Location: product.php?id=".$product->id);
					exit;
				}
			}
			else
			{
				setEventMessages($product->error, $product->errors, 'errors');
				$db->rollback();
				$action='transfert';
			}
		}
	}
}

// Update batch information
if ($action == 'updateline' && GETPOST('save') == $langs->trans('Save'))
{

    $pdluo = new Productbatch($db);
    $result=$pdluo->fetch(GETPOST('pdluoid','int'));

    if ($result>0)
    {
        if ($pdluo->id)
        {
            if ((! GETPOST("sellby")) && (! GETPOST("eatby")) && (! GETPOST("batch_number"))) {
                setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("atleast1batchfield")), 'errors');
            }
            else
            {
                $d_eatby=dol_mktime(12, 0, 0, $_POST['eatbymonth'], $_POST['eatbyday'], $_POST['eatbyyear']);
                $d_sellby=dol_mktime(12, 0, 0, $_POST['sellbymonth'], $_POST['sellbyday'], $_POST['sellbyyear']);
                $pdluo->batch=GETPOST("batch_number",'san_alpha');
                $pdluo->eatby=$d_eatby;
                $pdluo->sellby=$d_sellby;
                $result=$pdluo->update($user);
                if ($result<0)
                {
                    setEventMessages($pdluo->error,$pdluo->errors, 'errors');
                }
            }
        }
        else
        {
            setEventMessages($langs->trans('BatchInformationNotfound'),null, 'errors');
        }
    }
    else
    {
        setEventMessages($pdluo->error,null, 'errors');
    }
    header("Location: product.php?id=".$id);
    exit;
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
		$picto=($product->type==Product::TYPE_SERVICE?'service':'product');
		dol_fiche_head($head, 'stock', $titre, 0, $picto);

		dol_htmloutput_events();

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
        if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
            print ajax_object_onoff($product, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
        } else {
            print $product->getLibStatut(2,0);
        }
        print '</td></tr>';

        // Status (to buy)
        print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
        if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
            print ajax_object_onoff($product, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
        } else {
            print $product->getLibStatut(2,1);
        }
        print '</td></tr>';

		if ($conf->productbatch->enabled) {
			print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td>';
			print $product->getLibStatut(0,2);
			print '</td></tr>';
		}

		// PMP
		print '<tr><td>'.$langs->trans("AverageUnitPricePMP").'</td>';
		print '<td>'.price($product->pmp).' '.$langs->trans("HT").'</td>';
		print '</tr>';

		// Minimum Price
		print '<tr><td>'.$langs->trans("BuyingPriceMin").'</td>';
		print '<td colspan="2">';
		$product_fourn = new ProductFournisseur($db);
		if ($product_fourn->find_min_price_product_fournisseur($product->id) > 0)
		{
			if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur();
			else print $langs->trans("NotDefined");
		}
		print '</td></tr>';

		$object = $product;
		if (empty($conf->global->PRODUIT_MULTIPRICES))
		{
			// Price
			print '<tr><td>' . $langs->trans("SellingPrice") . '</td><td>';
			if ($object->price_base_type == 'TTC') {
				print price($object->price_ttc) . ' ' . $langs->trans($object->price_base_type);
			} else {
				print price($object->price) . ' ' . $langs->trans($object->price_base_type);
			}
			print '</td></tr>';

			// Price minimum
			print '<tr><td>' . $langs->trans("MinPrice") . '</td><td>';
			if ($object->price_base_type == 'TTC') {
				print price($object->price_min_ttc) . ' ' . $langs->trans($object->price_base_type);
			} else {
				print price($object->price_min) . ' ' . $langs->trans($object->price_base_type);
			}
			print '</td></tr>';
		}
		else
		{
			// Price
			print '<tr><td>' . $langs->trans("SellingPrice") . '</td><td>';
			print $langs->trans("Variable");
			print '</td></tr>';

			// Price minimum
			print '<tr><td>' . $langs->trans("MinPrice") . '</td><td>';
			print $langs->trans("Variable");
			print '</td></tr>';
		}

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
        $text_stock_options = '';
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)?$langs->trans("DeStockOnShipment").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER)?$langs->trans("DeStockOnValidateOrder").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_BILL)?$langs->trans("DeStockOnBill").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)?$langs->trans("ReStockOnBill").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)?$langs->trans("ReStockOnValidateOrder").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)?$langs->trans("ReStockOnDispatchOrder").'<br>':'');
        print '<tr><td>';
        print $form->textwithtooltip($langs->trans("PhysicalStock"),$text_stock_options,2,1,img_picto('', 'info'),'',0);
        print '</td>';
		print '<td>'.$product->stock_reel;
		if ($product->seuil_stock_alerte && ($product->stock_reel < $product->seuil_stock_alerte)) print ' '.img_warning($langs->trans("StockLowerThanLimit"));
		print '</td>';
		print '</tr>';

        // Calculating a theorical value
        print '<tr><td>'.$langs->trans("VirtualStock").'</td>';
        print "<td>".(empty($product->stock_theorique)?0:$product->stock_theorique);
        if ($product->stock_theorique < $product->seuil_stock_alerte) {
            print ' '.img_warning($langs->trans("StockLowerThanLimit"));
        }
        print '</td>';
        print '</tr>';

        print '<tr><td>';
        print $langs->trans("StockDiffPhysicTeoric");
        print '</td>';
        print '<td>';

        $found=0;
        // Number of customer orders running
        if (! empty($conf->commande->enabled))
        {
            if ($found) print '<br>'; else $found=1;
            print $langs->trans("ProductQtyInCustomersOrdersRunning").': '.$product->stats_commande['qty'];
            $result=$product->load_stats_commande(0,'0');
            if ($result < 0) dol_print_error($db,$product->error);
            print ' ('.$langs->trans("ProductQtyInDraft").': '.$product->stats_commande['qty'].')';
        }

        // Number of product from customer order already sent (partial shipping)
        if (! empty($conf->expedition->enabled))
        {
            if ($found) print '<br>'; else $found=1;
            $result=$product->load_stats_sending(0,'2');
            print $langs->trans("ProductQtyInShipmentAlreadySent").': '.$product->stats_expedition['qty'];
        }

        // Number of supplier order running
        if (! empty($conf->fournisseur->enabled)) {
            if ($found) print '<br>'; else $found=1;
            $result=$product->load_stats_commande_fournisseur(0,'3,4');
            print $langs->trans("ProductQtyInSuppliersOrdersRunning").': '.$product->stats_commande_fournisseur['qty'];
            $result=$product->load_stats_commande_fournisseur(0,'0,1,2');
            if ($result < 0) dol_print_error($db,$product->error);
            print ' ('.$langs->trans("ProductQtyInDraftOrWaitingApproved").': '.$product->stats_commande_fournisseur['qty'].')';
        }

	    // Number of product from supplier order already received (partial receipt)
        if (! empty($conf->fournisseur->enabled)) {
            if ($found) print '<br>'; else $found=1;
            print $langs->trans("ProductQtyInSuppliersShipmentAlreadyRecevied").': '.$product->stats_reception['qty'];
        }

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
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<table class="border" width="100%">';

		// Warehouse
		print '<tr>';
		print '<td width="20%" class="fieldrequired" colspan="2">'.$langs->trans("Warehouse").'</td>';
		print '<td width="20%">';
		print $formproduct->selectWarehouses((GETPOST("dwid")?GETPOST("dwid",'int'):(GETPOST('id_entrepot')?GETPOST('id_entrepot','int'):'ifone')),'id_entrepot','',1);
		print '</td>';
		print '<td width="20%">';
		print '<select name="mouvement" id="mouvement" class="flat">';
		print '<option value="0">'.$langs->trans("Add").'</option>';
		print '<option value="1">'.$langs->trans("Delete").'</option>';
		print '</select></td>';
		print '<td width="20%" class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" id="nbpiece" size="10" value="'.GETPOST("nbpiece").'"></td>';
		print '</tr>';

		// Purchase price
		print '<tr>';
		print '<td width="20%" colspan="2">'.$langs->trans("UnitPurchaseValue").'</td>';
		print '<td colspan="4"><input class="flat" name="price" id="unitprice" size="10" value="'.GETPOST("unitprice").'"></td>';
		print '</tr>';

		// Eat-by date
		if ((! empty($conf->productbatch->enabled)) && $product->hasbatch())
		{
			print '<tr>';
			print '<td colspan="2">'.$langs->trans("batch_number").'</td><td colspan="4">';
			print '<input type="text" name="batch_number" size="40" value="'.GETPOST("batch_number").'">';
			print '</td>';
			print '</tr><tr>';
			print '<td colspan="2">'.$langs->trans("l_eatby").'</td><td>';
			$form->select_date('','eatby','','',1,"");
			print '</td>';
			print '<td></td>';
			print '<td>'.$langs->trans("l_sellby").'</td><td>';
			$form->select_date('','sellby','','',1,"");
			print '</td>';
			print '</tr>';
		}

		// Label of mouvement of id of inventory
		$valformovementlabel=(GETPOST("label")?GETPOST("label"):$langs->trans("MovementCorrectStock", $product->ref));
		print '<tr>';
		print '<td width="20%" colspan="2">'.$langs->trans("MovementLabel").'</td>';
		print '<td colspan="2">';
		print '<input type="text" name="label" size="40" value="'.$valformovementlabel.'">';
		print '</td>';
		print '<td width="20%">'.$langs->trans("InventoryCode").'</td><td width="20%"><input class="flat" name="inventorycode" id="inventorycode" size="14" value="'.GETPOST("inventorycode").'"></td>';
		print '</tr>';

		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</div>';
		print '</form>';
	}

	/*
	 * Transfer of units
	 */
	if ($action == "transfert")
	{
	    $pdluoid=GETPOST('pdluoid','int');

	    $pdluo = new Productbatch($db);

	    if ($pdluoid > 0)
	    {
	        $result=$pdluo->fetch($pdluoid);

	        if ($result > 0)
	        {
	            $pdluoid=$pdluo->id;
	        }
	        else
	        {
	            dol_print_error($db);
	        }
	    }

		print_titre($langs->trans("StockTransfer"));
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'" method="post">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="transfert_stock">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		if ($pdluoid)
		{
		    print '<input type="hidden" name="pdluoid" value="'.$pdluoid.'">';
		}
		print '<table class="border" width="100%">';

		print '<tr>';
		print '<td width="15%" class="fieldrequired">'.$langs->trans("WarehouseSource").'</td><td width="15%">';
		if ($pdluoid > 0)
		{
		    print $formproduct->selectWarehouses($pdluo->warehouseid,'id_entrepot_source','',1,1);
		}
		else
		{
            print $formproduct->selectWarehouses((GETPOST("dwid")?GETPOST("dwid",'int'):(GETPOST('id_entrepot_source')?GETPOST('id_entrepot_source','int'):'ifone')),'id_entrepot_source','',1);
		}
		print '</td>';
		print '<td width="15%" class="fieldrequired">'.$langs->trans("WarehouseTarget").'</td><td width="15%">';
		print $formproduct->selectWarehouses(GETPOST('id_entrepot_destination'),'id_entrepot_destination','',1);
		print '</td>';
		print '<td width="15%" class="fieldrequired">'.$langs->trans("NumberOfUnit").'</td><td width="15%"><input type="text" class="flat" name="nbpiece" size="10" value="'.dol_escape_htmltag(GETPOST("nbpiece")).'"></td>';
		print '</tr>';

		// Eat-by date
		if ((! empty($conf->productbatch->enabled)) && $product->hasbatch())
		{
			print '<tr>';
			print '<td>'.$langs->trans("batch_number").'</td><td colspan="5">';
			print '<input type="text" name="batch_number" size="40"'.($pdluoid > 0 ? ' disabled="true"':'').' value="'.(GETPOST('batch_number')?GETPOST('batch_number'):$pdluo->batch).'">';			// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '</tr><tr>';
			print '<td>'.$langs->trans("l_eatby").'</td><td>';
			print $form->select_date(($d_eatby?$d_eatby:$pdluo->eatby),'eatby','','',1,"", 1, 0, 1, ($pdluoid > 0 ? 1 : 0));		// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '<td>'.$langs->trans("l_sellby").'</td><td>';
			print $form->select_date(($d_sellby?$d_sellby:$pdluo->sellby),'sellby','','',1,"", 1, 0, 1, ($pdluoid > 0 ? 1 : 0));		// If form was opened for a specific pdluoid, field is disabled
			print '</td>';
			print '<td colspan="2"></td>';
			print '</tr>';
		}

		// Label
		$valformovementlabel=(GETPOST("label")?GETPOST("label"):$langs->trans("MovementTransferStock", $product->ref));
		print '<tr>';
		print '<td width="15%">'.$langs->trans("MovementLabel").'</td>';
		print '<td colspan="3">';
		print '<input type="text" name="label" size="60" value="'.dol_escape_htmltag($valformovementlabel).'">';
		print '</td>';
		print '<td width="20%">'.$langs->trans("InventoryCode").'</td><td width="20%"><input class="flat" name="inventorycode" id="inventorycode" size="14" value="'.GETPOST("inventorycode").'"></td>';
		print '</tr>';

		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Save')).'">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'">';
		print '</div>';

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
		print '<td width="15%">'.$langs->trans("Warehouse").'</td><td width="40%">';
		print $formproduct->selectWarehouses('','id_entrepot','',1);
		print '</td><td width="15%">'.$langs->trans("NumberOfUnit").'</td><td width="15%"><input name="nbpiece" size="10" value=""></td></tr>';
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

    if ($user->rights->stock->creer)
    {
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'&amp;action=correction">'.$langs->trans("StockCorrection").'</a>';
    }

    //if (($user->rights->stock->mouvement->creer) && ! $product->hasbatch())
    if (($user->rights->stock->mouvement->creer))
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'&amp;action=transfert">'.$langs->trans("StockMovement").'</a>';
	}

	print '</div>';
}




/*
 * Stock detail
 */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="40%" colspan="4">'.$langs->trans("Warehouse").'</td>';
print '<td align="right">'.$langs->trans("NumberOfUnit").'</td>';
print '<td align="right">'.$langs->trans("AverageUnitPricePMPShort").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueShort").'</td>';
print '<td align="right">'.$langs->trans("SellPriceMin").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueSellShort").'</td>';
print '</tr>';
if ((! empty($conf->productbatch->enabled)) && $product->hasbatch())
{
	print '<tr class="liste_titre"><td width="10%"></td>';
	print '<td align="right" width="10%">'.$langs->trans("batch_number").'</td>';
	print '<td align="center" width="10%">'.$langs->trans("l_eatby").'</td>';
	print '<td align="center" width="10%">'.$langs->trans("l_sellby").'</td>';
	print '<td align="right" colspan="5"></td>';
	print '</tr>';
}

$sql = "SELECT e.rowid, e.label, e.lieu, ps.reel, ps.pmp, ps.rowid as product_stock_id";
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
		$entrepotstatic->lieu=$obj->lieu;
		print '<tr '.$bc[$var].'>';
		print '<td colspan="4">'.$entrepotstatic->getNomUrl(1).'</td>';
		print '<td align="right">'.$obj->reel.($obj->reel<0?' '.img_warning():'').'</td>';
		// PMP
		print '<td align="right">'.(price2num($product->pmp)?price2num($product->pmp,'MU'):'').'</td>'; // Ditto : Show PMP from movement or from product
		// Value purchase
		print '<td align="right">'.(price2num($product->pmp)?price(price2num($product->pmp*$obj->reel,'MT')):'').'</td>'; // Ditto : Show PMP from movement or from product
        // Sell price
		print '<td align="right">';
        if (empty($conf->global->PRODUIT_MULTI_PRICES)) print price(price2num($product->price,'MU'),1);
        else print $langs->trans("Variable");
        print '</td>';
        // Value sell
        print '<td align="right">';
        if (empty($conf->global->PRODUIT_MULTI_PRICES)) print price(price2num($product->price*$obj->reel,'MT'),1).'</td>'; // Ditto : Show PMP from movement or from product
        else print $langs->trans("Variable");
		print '</tr>'; ;
		$total += $obj->reel;
		if (price2num($product->pmp)) $totalwithpmp += $obj->reel;
		$totalvalue = $totalvalue + ($product->pmp*$obj->reel); // Ditto : Show PMP from movement or from product
        $totalvaluesell = $totalvaluesell + ($product->price*$obj->reel); // Ditto : Show PMP from movement or from product
		//Batch Detail
		if ((! empty($conf->productbatch->enabled)) && $product->hasbatch())
		{
			$details=Productbatch::findAll($db,$obj->product_stock_id);
			if ($details<0) dol_print_error($db);
			foreach ($details as $pdluo)
			{
			    if ( $action == 'editline' && GETPOST('lineid',int)==$pdluo->id )
			    { //Current line edit
			        print "\n".'<tr><td colspan="9">';
			        print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST"><input type="hidden" name="pdluoid" value="'.$pdluo->id.'"><input type="hidden" name="action" value="updateline"><input type="hidden" name="id" value="'.$id.'"><table class="noborder" width="100%"><tr><td width="10%"></td>';
			        print '<td align="right" width="10%"><input type="text" name="batch_number" value="'.$pdluo->batch.'"></td>';
			        print '<td align="center" width="10%">';
			        $form->select_date($pdluo->eatby,'eatby','','',1,"");
			        print '</td>';
			        print '<td align="center" width="10%">';
			        $form->select_date($pdluo->sellby,'sellby','','',1,"");
			        print '</td>';
			        print '<td align="right" width="10%">'.$pdluo->qty.($pdluo->qty<0?' '.img_warning():'').'</td>';
			        print '<td colspan="4"><input type="submit" class="button" id="savelinebutton" name="save" value="'.$langs->trans("Save").'">';
		            print '<input type="submit" class="button" id="cancellinebutton" name="Cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
			        print '</table></form>';
			    }
			    else
				{
                    print "\n".'<tr><td align="right">';
                    print img_picto($langs->trans("Tranfer"),'uparrow','class="hideonsmartphone"').' ';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'&amp;action=transfert&amp;pdluoid='.$pdluo->id.'">'.$langs->trans("StockMovement").'</a>';
					// Disabled, because edition of stock content must use the "Correct stock menu".
					// Do not use this, or data will be wrong (bad tracking of movement label, inventory code, ...
                    //print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=editline&amp;lineid='.$pdluo->id.'#'.$pdluo->id.'">';
                    //print img_edit().'</a></td>';
                    print '<td align="right">'.$pdluo->batch.'</td>';
                    print '<td align="center">'. dol_print_date($pdluo->eatby,'day') .'</td>';
                    print '<td align="center">'. dol_print_date($pdluo->sellby,'day') .'</td>';
                    print '<td align="right">'.$pdluo->qty.($pdluo->qty<0?' '.img_warning():'').'</td>';
                    print '<td colspan="4"></td></tr>';
			    }
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
print ($totalwithpmp?price(price2num($totalvalue/$totalwithpmp,'MU')):'&nbsp;');	// This value may have rounding errors
print '</td>';
// Value purchase
print '<td class="liste_total" align="right">';
print $totalvalue?price(price2num($totalvalue,'MT'),1):'&nbsp;';
print '</td>';
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MULTI_PRICES)) print ($total?price($totalvaluesell/$total,1):'&nbsp;');
else print $langs->trans("Variable");
print '</td>';
// Value to sell
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MULTI_PRICES)) print price(price2num($totalvaluesell,'MT'),1);
else print $langs->trans("Variable");
print '</td>';
print "</tr>";
print "</table>";


llxFooter();

$db->close();
