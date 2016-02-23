<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador.gpcsolutions.fr>
 * Copyright (C) 2013-2015 Juanjo Menent	    <jmenent@2byte.es>
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
    $object = new Product($db);
    $result=$object->fetch($id);
    $object->seuil_stock_alerte=$stocklimit;
    $result=$object->update($object->id,$user,0,'update');
    if ($result < 0)
    	setEventMessages($object->error, $object->errors, 'errors');
    $action='';
}

// Set desired stock
if ($action == 'setdesiredstock')
{
    $object = new Product($db);
    $result=$object->fetch($id);
    $object->desiredstock=$desiredstock;
    $result=$object->update($object->id,$user,0,'update');
    if ($result < 0)
    	setEventMessages($object->error, $object->errors, 'errors');
    $action='';
}


// Correct stock
if ($action == "correct_stock" && ! $cancel)
{
	if (! (GETPOST("id_entrepot") > 0))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action='correction';
	}
	if (! GETPOST("nbpiece"))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action='correction';
	}

	if (! empty($conf->productbatch->enabled))
	{
		$object = new Product($db);
		$result=$object->fetch($id);

		if ($object->hasbatch() && ! GETPOST("batch_number"))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
			$error++;
			$action='correction';
		}
	}

	if (! $error)
	{
		$priceunit=price2num(GETPOST("unitprice"));
		if (is_numeric(GETPOST("nbpiece")) && $id)
		{
			if (empty($object)) {
				$object = new Product($db);
				$result=$object->fetch($id);
			}
			if ($object->hasbatch())
			{
				$result=$object->correct_stock_batch(
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
				$result=$object->correct_stock(
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
	            	header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
					exit;
				}
			}
			else
			{
			    setEventMessages($object->error, $object->errors, 'errors');
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
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action='transfert';
	}
	if (! GETPOST("nbpiece",'int'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action='transfert';
	}
	if (GETPOST("id_entrepot_source",'int') == GETPOST("id_entrepot_destination",'int'))
	{
		setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
		$error++;
		$action='transfert';
	}
	if (! empty($conf->productbatch->enabled))
	{
	    $object = new Product($db);
	    $result=$object->fetch($id);
	
	    if ($object->hasbatch() && ! GETPOST("batch_number"))
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
	        $error++;
	        $action='transfert';
	    }
	}
	
	if (! $error)
	{
		if ($id)
		{
			$object = new Product($db);
			$result=$object->fetch($id);

			$db->begin();

			$object->load_stock();	// Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc=0;
			//if (isset($object->stock_warehouse[GETPOST("id_entrepot_source")]->pmp)) $pricesrc=$object->stock_warehouse[GETPOST("id_entrepot_source")]->pmp;
			if (isset($object->pmp)) $pricesrc=$object->pmp;
			$pricedest=$pricesrc;

			if ($object->hasbatch())
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
					$result1=$object->correct_stock_batch(
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
					$result2=$object->correct_stock_batch(
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
				$result1=$object->correct_stock(
					$user,
					GETPOST("id_entrepot_source"),
					GETPOST("nbpiece"),
					1,
					GETPOST("label"),
					$pricesrc,
					GETPOST('inventorycode')
				);

				// Add stock
				$result2=$object->correct_stock(
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
					header("Location: product.php?id=".$object->id);
					exit;
				}
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
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
                setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("atleast1batchfield")), null, 'errors');
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

$form = new Form($db);
$formproduct=new FormProduct($db);


if ($id > 0 || $ref)
{
	$object = new Product($db);
	$result = $object->fetch($id,$ref);
	$object->load_stock();

	$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("",$langs->trans("CardProduct".$object->type),$help_url);

	if ($result > 0)
	{
		$head=product_prepare_head($object);
		$titre=$langs->trans("CardProduct".$object->type);
		$picto=($object->type==Product::TYPE_SERVICE?'service':'product');
		dol_fiche_head($head, 'stock', $titre, 0, $picto);

		dol_htmloutput_events();

        dol_banner_tab($object, 'ref', '', ($user->societe_id?0:1), 'ref');
        
        print '<div class="fichecenter">';
        
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';
		
		if ($conf->productbatch->enabled) 
		{
			print '<tr><td class="titlefield">'.$langs->trans("ManageLotSerial").'</td><td>';
			print $object->getLibStatut(0,2);
			print '</td></tr>';
		}

		// PMP
		print '<tr><td class="titlefield">'.$langs->trans("AverageUnitPricePMP").'</td>';
		print '<td>';
		if ($object->pmp > 0) print price($object->pmp).' '.$langs->trans("HT");
		print '</td>';
		print '</tr>';

		// Minimum Price
		print '<tr><td>'.$langs->trans("BuyingPriceMin").'</td>';
		print '<td colspan="2">';
		$product_fourn = new ProductFournisseur($db);
		if ($product_fourn->find_min_price_product_fournisseur($object->id) > 0)
		{
			if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur();
			else print $langs->trans("NotDefined");
		}
		print '</td></tr>';

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

        // Stock alert threshold
        print '<tr><td>'.$form->editfieldkey("StockLimit",'stocklimit',$object->seuil_stock_alerte,$object,$user->rights->produit->creer).'</td><td colspan="2">';
        print $form->editfieldval("StockLimit",'stocklimit',$object->seuil_stock_alerte,$object,$user->rights->produit->creer);
        print '</td></tr>';

        // Desired stock
        print '<tr><td>'.$form->editfieldkey("DesiredStock",'desiredstock',$object->desiredstock,$object,$user->rights->produit->creer).'</td><td colspan="2">';
        print $form->editfieldval("DesiredStock",'desiredstock',$object->desiredstock,$object,$user->rights->produit->creer);
        print '</td></tr>';

        // Real stock
        $object->load_stock();
        $text_stock_options = '';
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SHIPMENT)?$langs->trans("DeStockOnShipment").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER)?$langs->trans("DeStockOnValidateOrder").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_BILL)?$langs->trans("DeStockOnBill").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)?$langs->trans("ReStockOnBill").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER)?$langs->trans("ReStockOnValidateOrder").'<br>':'');
        $text_stock_options.= (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER)?$langs->trans("ReStockOnDispatchOrder").'<br>':'');
        print '<tr><td>';
        print $form->textwithtooltip($langs->trans("PhysicalStock"), $text_stock_options, 2, 1, img_picto('', 'info'), '', 2);
        print '</td>';
		print '<td>'.$object->stock_reel;
		if ($object->seuil_stock_alerte != '' && ($object->stock_reel < $object->seuil_stock_alerte)) print ' '.img_warning($langs->trans("StockLowerThanLimit"));
		print '</td>';
		print '</tr>';

        // Calculating a theorical value
        print '<tr><td>'.$langs->trans("VirtualStock").'</td>';
        print "<td>".(empty($object->stock_theorique)?0:$object->stock_theorique);
        if ($object->seuil_stock_alerte != '' && ($object->stock_theorique < $object->seuil_stock_alerte)) print ' '.img_warning($langs->trans("StockLowerThanLimit"));
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
            print $langs->trans("ProductQtyInCustomersOrdersRunning").': '.$object->stats_commande['qty'];
            $result=$object->load_stats_commande(0,'0');
            if ($result < 0) dol_print_error($db,$object->error);
            print ' ('.$langs->trans("ProductQtyInDraft").': '.$object->stats_commande['qty'].')';
        }

        // Number of product from customer order already sent (partial shipping)
        if (! empty($conf->expedition->enabled))
        {
            if ($found) print '<br>'; else $found=1;
            $result=$object->load_stats_sending(0,'2');
            print $langs->trans("ProductQtyInShipmentAlreadySent").': '.$object->stats_expedition['qty'];
        }

        // Number of supplier order running
        if (! empty($conf->fournisseur->enabled))
        {
            if ($found) print '<br>'; else $found=1;
            $result=$object->load_stats_commande_fournisseur(0,'3,4');
            print $langs->trans("ProductQtyInSuppliersOrdersRunning").': '.$object->stats_commande_fournisseur['qty'];
            $result=$object->load_stats_commande_fournisseur(0,'0,1,2');
            if ($result < 0) dol_print_error($db,$object->error);
            print ' ('.$langs->trans("ProductQtyInDraftOrWaitingApproved").': '.$object->stats_commande_fournisseur['qty'].')';
        }

	    // Number of product from supplier order already received (partial receipt)
        if (! empty($conf->fournisseur->enabled))
        {
            if ($found) print '<br>'; else $found=1;
            print $langs->trans("ProductQtyInSuppliersShipmentAlreadyRecevied").': '.$object->stats_reception['qty'];
        }

        print '</td></tr>';

		// Last movement
		$sql = "SELECT max(m.datem) as datem";
		$sql.= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
		$sql.= " WHERE m.fk_product = '".$object->id."'";
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
		    print '(<a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$object->id.'">'.$langs->trans("FullList").'</a>)';
		}
		else
		{
		     print '<a href="'.DOL_URL_ROOT.'/product/stock/mouvement.php?idproduct='.$object->id.'">'.$langs->trans("None").'</a>';
		}
		print "</td></tr>";

		print "</table>";

        print '</div>';
        print '<div style="clear:both"></div>';
    		
		dol_fiche_end();
	}

	/*
	 * Correct stock
	 */
	if ($action == "correction")
	{
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stockcorrection.tpl.php';
		print '<br>';
	}

	/*
	 * Transfer of units
	 */
	if ($action == "transfert")
	{
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stocktransfer.tpl.php';
		print '<br>';
	}

	/*
	 * Set initial stock
	 */
	/*
	if ($_GET["action"] == "definir")
	{
		print load_fiche_titre($langs->trans("SetStock"));
		print "<form action=\"product.php?id=$object->id\" method=\"post\">\n";
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


if (empty($action) && $object->id)
{
    print "<div class=\"tabsAction\">\n";

    if ($user->rights->stock->mouvement->creer)
    {
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=correction">'.$langs->trans("StockCorrection").'</a>';
    }

    //if (($user->rights->stock->mouvement->creer) && ! $object->hasbatch())
    if ($user->rights->stock->mouvement->creer)
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=transfert">'.$langs->trans("StockTransfer").'</a>';
	}

	print '</div>';
}




/*
 * Stock detail (by warehouse). Do not go down into batch.
 */

print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="40%" colspan="4">'.$langs->trans("Warehouse").'</td>';
print '<td align="right">'.$langs->trans("NumberOfUnit").'</td>';
print '<td align="right">'.$langs->trans("AverageUnitPricePMPShort").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueShort").'</td>';
print '<td align="right">'.$langs->trans("SellPriceMin").'</td>';
print '<td align="right">'.$langs->trans("EstimatedStockValueSellShort").'</td>';
print '</tr>';
if ((! empty($conf->productbatch->enabled)) && $object->hasbatch())
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
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
$sql.= " WHERE ps.reel != 0";
$sql.= " AND ps.fk_entrepot = e.rowid";
$sql.= " AND e.entity IN (".getEntity('stock', 1).")";
$sql.= " AND ps.fk_product = ".$object->id;
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
		print '<td align="right">'.(price2num($object->pmp)?price2num($object->pmp,'MU'):'').'</td>';
		// Value purchase
		print '<td align="right">'.(price2num($object->pmp)?price(price2num($object->pmp*$obj->reel,'MT')):'').'</td>';
        // Sell price
		print '<td align="right">';
        if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($object->price,'MU'),1);
        else print $langs->trans("Variable");
        print '</td>';
        // Value sell
        print '<td align="right">';
        if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($object->price*$obj->reel,'MT'),1).'</td>';
        else print $langs->trans("Variable");
		print '</tr>'; ;
		$total += $obj->reel;
		if (price2num($object->pmp)) $totalwithpmp += $obj->reel;
		$totalvalue = $totalvalue + ($object->pmp*$obj->reel);
        $totalvaluesell = $totalvaluesell + ($object->price*$obj->reel);
		//Batch Detail
		if ((! empty($conf->productbatch->enabled)) && $object->hasbatch())
		{
			$details=Productbatch::findAll($db,$obj->product_stock_id);
			if ($details<0) dol_print_error($db);
			foreach ($details as $pdluo)
			{
			    if ( $action == 'editline' && GETPOST('lineid','int')==$pdluo->id )
			    { //Current line edit
			        print "\n".'<tr><td colspan="9">';
			        print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST"><input type="hidden" name="pdluoid" value="'.$pdluo->id.'"><input type="hidden" name="action" value="updateline"><input type="hidden" name="id" value="'.$id.'"><table class="noborder" width="100%"><tr><td width="10%"></td>';
			        print '<td align="right" width="10%"><input type="text" name="batch_number" value="'.$pdluo->batch.'"></td>';
			        print '<td align="center" width="10%">';
			        $form->select_date($pdluo->eatby,'eatby','','',1,'',1,0,1);
			        print '</td>';
			        print '<td align="center" width="10%">';
			        $form->select_date($pdluo->sellby,'sellby','','',1,'',1,0,1);
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
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=transfert&amp;pdluoid='.$pdluo->id.'">'.$langs->trans("StockTransfer").'</a>';
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
if (empty($conf->global->PRODUIT_MULTIPRICES)) print ($total?price($totalvaluesell/$total,1):'&nbsp;');
else print $langs->trans("Variable");
print '</td>';
// Value to sell
print '<td class="liste_total" align="right">';
if (empty($conf->global->PRODUIT_MULTIPRICES)) print price(price2num($totalvaluesell,'MT'),1);
else print $langs->trans("Variable");
print '</td>';
print "</tr>";
print "</table>";


llxFooter();

$db->close();
