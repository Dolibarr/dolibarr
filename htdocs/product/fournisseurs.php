<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
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
 *  \file       htdocs/product/fournisseurs.php
 *  \ingroup    product
 *  \brief      Page of tab suppliers for products
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_expression.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

$langs->load("products");
$langs->load("suppliers");
$langs->load("bills");
if (! empty($conf->margin->enabled)) $langs->load("margins");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$rowid=GETPOST('rowid','int');
$action=GETPOST('action', 'alpha');
$socid=GETPOST('socid', 'int');
$backtopage=GETPOST('backtopage','alpha');
$error=0;

// If socid provided by ajax company selector
if (! empty($_REQUEST['search_fourn_id']))
{
	$_GET['id_fourn'] = $_GET['search_fourn_id'];
	$_POST['id_fourn'] = $_POST['search_fourn_id'];
	$_REQUEST['id_fourn'] = $_REQUEST['search_fourn_id'];
}

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service&fournisseur',$fieldvalue,'product&product','','',$fieldtype);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('pricesuppliercard','globalcard'));
$product = new ProductFournisseur($db);
$product->fetch($id,$ref);

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');

if (! $sortfield) $sortfield="s.nom";
if (! $sortorder) $sortorder="ASC";


/*
 * Actions
 */

$parameters=array('socid'=>$socid, 'id_prod'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$product,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($action == 'remove_pf')
	{
		$product = new ProductFournisseur($db);
		if ($product->fetch($id) > 0)
		{
			if ($rowid)
			{
				$result=$product->remove_product_fournisseur_price($rowid);
				$action = '';
				setEventMessage($langs->trans("PriceRemoved"));
			}
		}
	}

if ($action == 'updateprice' && GETPOST('cancel') <> $langs->trans("Cancel"))
{
    $id_fourn=GETPOST("id_fourn");
    if (empty($id_fourn)) $id_fourn=GETPOST("search_id_fourn");
    $ref_fourn=GETPOST("ref_fourn");
    if (empty($ref_fourn)) $ref_fourn=GETPOST("search_ref_fourn");
    $quantity=GETPOST("qty");
	$remise_percent=price2num(GETPOST('remise_percent','alpha'));
    $npr = preg_match('/\*/', $_POST['tva_tx']) ? 1 : 0 ;
    $tva_tx = str_replace('*','', GETPOST('tva_tx','alpha'));
    $tva_tx = price2num($tva_tx);
	$price_expression = GETPOST('eid', 'int') ? GETPOST('eid', 'int') : ''; // Discard expression if not in expression mode
	$delivery_time_days = GETPOST('delivery_time_days', 'int') ? GETPOST('delivery_time_days', 'int') : '';

    if ($tva_tx == '')
    {
		$error++;
	    setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("VATRateForSupplierProduct")), 'errors');
    }
	if (empty($quantity))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Qty")), 'errors');
	}
	if (empty($ref_fourn))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("RefSupplier")), 'errors');
	}
	if ($id_fourn <= 0)
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Supplier")), 'errors');
	}
	if ($_POST["price"] < 0 || $_POST["price"] == '')
	{
		if ($price_expression === '')	// Return error of missing price only if price_expression not set
		{
			$error++;
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Price")), 'errors');
		}
		else
		{
			$_POST["price"] = 0;
		}
	}
	
		$product = new ProductFournisseur($db);
		$result=$product->fetch($id);
		if ($result <= 0)
		{
			$error++;
			setEventMessage($product->error, 'errors');
		}

		if (! $error)
		{
			$db->begin();

			if (! $error)
			{
				$ret=$product->add_fournisseur($user, $id_fourn, $ref_fourn, $quantity);    // This insert record with no value for price. Values are update later with update_buyprice
				if ($ret == -3)
				{
					$error++;

					$product->fetch($product->product_id_already_linked);
					$productLink = $product->getNomUrl(1,'supplier');

					setEventMessage($langs->trans("ReferenceSupplierIsAlreadyAssociatedWithAProduct",$productLink), 'errors');
				}
				else if ($ret < 0)
				{
					$error++;
					setEventMessage($product->error, 'errors');
				}
			}

			if (! $error)
			{
				$supplier=new Fournisseur($db);
				$result=$supplier->fetch($id_fourn);
				if (isset($_POST['ref_fourn_price_id']))
					$product->fetch_product_fournisseur_price($_POST['ref_fourn_price_id']);

				$ret=$product->update_buyprice($quantity, $_POST["price"], $user, $_POST["price_base_type"], $supplier, $_POST["oselDispo"], $ref_fourn, $tva_tx, $_POST["charges"], $remise_percent, 0, $npr, $delivery_time_days);
				if ($ret < 0)
				{

					$error++;
					setEventMessage($product->error, 'errors');
				}
				else
				{
					if ($price_expression !== '') 
					{
						//Check the expression validity by parsing it
						$priceparser = new PriceParser($db);
						$price_result = $priceparser->parseProductSupplier($id, $price_expression, $quantity, $tva_tx);
						if ($price_result < 0) { //Expression is not valid
							$error++;
							setEventMessage($priceparser->translatedError(), 'errors');
						}
					}
					if (! $error && ! empty($conf->dynamicprices->enabled)) {
						$ret=$product->setPriceExpression($price_expression);
						if ($ret < 0)
						{
							$error++;
							setEventMessage($product->error, 'errors');
						}
					}
				}
			}

			if (! $error)
			{
				$db->commit();
				$action='';
			}
			else
			{
				$db->rollback();
			}
		}
	}

	if (GETPOST('cancel') == $langs->trans("Cancel"))
	{
		$action = '';
		header("Location: fournisseurs.php?id=".$_GET["id"]);
		exit;
	}
}


/*
 * view
 */

$form = new Form($db);

if ($id || $ref)
{
	if ($action <> 're-edit')
	{
		$product = new ProductFournisseur($db);
		$result = $product->fetch($id,$ref);
		//$result = $product->fetch_fourn_data($_REQUEST["id_fourn"]);
		llxHeader("","",$langs->trans("CardProduct".$product->type));
	}

	if ($result)
	{
		if ($action <> 'edit' && $action <> 're-edit')
		{
			/*
			 *  En mode visu
			 */

			$head=product_prepare_head($product, $user);
			$titre=$langs->trans("CardProduct".$product->type);
			$picto=($product->type== Product::TYPE_SERVICE?'service':'product');
			dol_fiche_head($head, 'suppliers', $titre, 0, $picto);


			print '<table class="border" width="100%">';

			// Reference
			print '<tr>';
			print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
			print $form->showrefnav($product,'ref','',1,'ref');
			print '</td>';
			print '</tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';

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

			// Status (to buy)
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
			print $product->getLibStatut(2,1);
			print '</td></tr>';

			print '</table>';

			print "</div>\n";

			// Form to add or update a price
			if (($action == 'add_price' || $action == 'updateprice' ) && ($user->rights->produit->creer || $user->rights->service->creer))
			{
				$langs->load("suppliers");

				if ($rowid)
				{
					$product->fetch_product_fournisseur_price($rowid, 1); //Ignore the math expression when getting the price
					print_fiche_titre($langs->trans("ChangeSupplierPrice"));
				}
				else
				{
					print_fiche_titre($langs->trans("AddSupplierPrice"));
				}

				print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$product->id.'" method="POST">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="updateprice">';

				print '<table class="border" width="100%">';

				print '<tr><td class="fieldrequired" width="25%">'.$langs->trans("Supplier").'</td><td>';
				if ($rowid)
				{
					$supplier=new Fournisseur($db);
					$supplier->fetch($socid);
					print $supplier->getNomUrl(1);
					print '<input type="hidden" name="id_fourn" value="'.$socid.'">';
					print '<input type="hidden" name="ref_fourn" value="'.$product->fourn_ref.'">';				
					print '<input type="hidden" name="ref_fourn_price_id" value="'.$rowid.'">';
				}
				else
				{
					$events=array();
					$events[]=array('method' => 'getVatRates', 'url' => dol_buildpath('/core/ajax/vatrates.php',1), 'htmlname' => 'tva_tx', 'params' => array());
					print $form->select_company(GETPOST("id_fourn"),'id_fourn','fournisseur=1',1,0,0,$events);

					$parameters=array('filtre'=>"fournisseur=1",'html_name'=>'id_fourn','selected'=>GETPOST("id_fourn"),'showempty'=>1,'prod_id'=>$product->id);
				    $reshook=$hookmanager->executeHooks('formCreateThirdpartyOptions',$parameters,$object,$action);
					if (empty($reshook))
					{
						if (empty($form->result))
						{
							print ' - <a href="'.DOL_URL_ROOT.'/societe/soc.php?action=create&type=f&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$product->id.'&action='.$action).'">'.$langs->trans("CreateDolibarrThirdPartySupplier").'</a>';
						}
					}
				}
				print '</td></tr>';

				// Ref supplier
				print '<tr><td class="fieldrequired">'.$langs->trans("SupplierRef").'</td><td>';
				if ($rowid)
				{
					print $product->fourn_ref;
				}
				else
				{
					print '<input class="flat" name="ref_fourn" size="12" value="'.(GETPOST("ref_fourn")?GETPOST("ref_fourn"):'').'">';
				}
				print '</td>';
				print '</tr>';

				// Availability
				if (! empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
				{
					$langs->load("propal");
					print '<tr><td>'.$langs->trans("Availability").'</td><td>';
					$form->selectAvailabilityDelay($product->fk_availability,"oselDispo",1);
					print '</td></tr>'."\n";
				}

				// Qty min
				print '<tr>';
				print '<td class="fieldrequired">'.$langs->trans("QtyMin").'</td>';
				print '<td>';
				$quantity = GETPOST('qty') ? GETPOST('qty') : "1";
				if ($rowid)
				{
					print '<input type="hidden" name="qty" value="'.$product->fourn_qty.'">';
					print $product->fourn_qty;
				}
				else
				{
					print '<input class="flat" name="qty" size="5" value="'.$quantity.'">';
				}
				print '</td></tr>';				

				// Vat rate
				$default_vat='';

				// We don't have supplier, so we try to guess.
				// For this we build a fictive supplier with same properties than user but using vat)
				$mysoc2=dol_clone($mysoc);
				$mysoc2->name='Fictive seller with same country';
				$mysoc2->tva_assuj=1;
				$default_vat=get_default_tva($mysoc2, $mysoc, $product->id, 0);

				print '<tr><td class="fieldrequired">'.$langs->trans("VATRateForSupplierProduct").'</td>';
				print '<td>';
				//print $form->load_tva('tva_tx',$product->tva_tx,$supplier,$mysoc);    // Do not use list here as it may be any vat rates for any country
				if (! empty($socid))	// When update
				{
					$default_vat=get_default_tva($supplier, $mysoc, $product->id);
					if (empty($default_vat)) $default_vat=$product->tva_tx;
				}
				print '<input type="text" class="flat" size="5" name="tva_tx" value="'.(GETPOST("tva_tx")?vatrate(GETPOST("tva_tx")):($default_vat!=''?vatrate($default_vat):'')).'">';
				print '</td></tr>';

				if (! empty($conf->dynamicprices->enabled)) //Only show price mode and expression selector if module is enabled
				{
					// Price mode selector
					print '<tr><td class="fieldrequired">'.$langs->trans("PriceMode").'</td><td>';
					$price_expression = new PriceExpression($db);
					$price_expression_list = array(0 => $langs->trans("PriceNumeric")); //Put the numeric mode as first option
					foreach ($price_expression->list_price_expression() as $entry) {
						$price_expression_list[$entry->id] = $entry->title;
					}
					$price_expression_preselection = GETPOST('eid') ? GETPOST('eid') : ($product->fk_supplier_price_expression ? $product->fk_supplier_price_expression : '0');
					print $form->selectarray('eid', $price_expression_list, $price_expression_preselection);
					print '&nbsp; <div id="expression_editor" class="button">'.$langs->trans("PriceExpressionEditor").'</div>';
					print '</td></tr>';
					// This code hides the numeric price input if is not selected, loads the editor page if editor button is pressed
					print '<script type="text/javascript">
						jQuery(document).ready(run);
						function run() {
							jQuery("#expression_editor").click(on_click);
							jQuery("#eid").change(on_change);
							on_change();
						}
						function on_click() {
							window.location = "'.DOL_URL_ROOT.'/product/dynamic_price/editor.php?id='.$id.'&tab=fournisseurs&eid=" + $("#eid").attr("value");
						}
						function on_change() {
							if ($("#eid").attr("value") == 0) {
								jQuery("#price_numeric").show();
							} else {
								jQuery("#price_numeric").hide();
							}
						}
					</script>';
				}

				// Price qty min
				print '<tr><td class="fieldrequired">'.$langs->trans("PriceQtyMin").'</td>';
				print '<td><input class="flat" name="price" size="8" value="'.(GETPOST('price')?price(GETPOST('price')):(isset($product->fourn_price)?price($product->fourn_price):'')).'">';
				print '&nbsp;';
				print $form->select_PriceBaseType((GETPOST('price_base_type')?GETPOST('price_base_type'):$product->price_base_type), "price_base_type");
				print '</td></tr>';

				// Discount qty min
				print '<tr><td>'.$langs->trans("DiscountQtyMin").'</td>';
				print '<td><input class="flat" name="remise_percent" size="4" value="'.(GETPOST('remise_percent')?vatrate(GETPOST('remise_percent')):(isset($product->fourn_remise_percent)?vatrate($product->fourn_remise_percent):'')).'"> %';
				print '</td>';
				print '</tr>';

				
				// Delai livraison jours
				print '<tr>';
				print '<td>'.$langs->trans('NbDaysToDelivery').'</td>';
				print '<td><input class="flat" name="delivery_time_days" size="4" value="'.($rowid ? $product->delivery_time_days : '').'">&nbsp;'.$langs->trans('days').'</td>';
				print '</tr>';

				// Charges ????
				if ($conf->global->PRODUCT_CHARGES)
				{
					if (! empty($conf->margin->enabled))
					{
						print '<tr>';
						print '<td>'.$langs->trans("Charges").'</td>';
						print '<td><input class="flat" name="charges" size="8" value="'.(GETPOST('charges')?price(GETPOST('charges')):(isset($product->fourn_charges)?price($product->fourn_charges):'')).'">';
		        		print '</td>';
						print '</tr>';
					}
				}

				if (is_object($hookmanager))
				{
					$parameters=array('id_fourn'=>$id_fourn,'prod_id'=>$product->id);
				    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);
				}

				print '</table>';

				print '<br><div class="center">';
				print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
				print '&nbsp; &nbsp;';
				print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';

				print '</form>';
			}

			/* ************************************************************************** */
			/*                                                                            */
			/* Barre d'action                                                             */
			/*                                                                            */
			/* ************************************************************************** */

			print "\n<div class=\"tabsAction\">\n";

			if ($action != 'add_price' && $action != 'updateprice')
			{
				if ($user->rights->produit->creer || $user->rights->service->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$product->id.'&amp;action=add_price">';
					print $langs->trans("AddSupplierPrice").'</a>';
				}
			}

			print "\n</div>\n";
			print '<br>';


			if ($user->rights->fournisseur->lire)
			{
				// Suppliers list title
				print '<table class="noborder" width="100%">';
				if ($product->isproduct()) $nblignefour=4;
				else $nblignefour=4;

				$param="&id=".$product->id;
				print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans("Suppliers"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
				print '<td class="liste_titre">'.$langs->trans("SupplierRef").'</td>';
				if (!empty($conf->global->FOURN_PRODUCT_AVAILABILITY)) print_liste_field_titre($langs->trans("Availability"),$_SERVER["PHP_SELF"],"pfp.fk_availability","",$param,"",$sortfield,$sortorder);
				print_liste_field_titre($langs->trans("QtyMin"),$_SERVER["PHP_SELF"],"pfp.quantity","",$param,'align="right"',$sortfield,$sortorder);
				print '<td class="liste_titre" align="right">'.$langs->trans("VATRate").'</td>';
				print '<td class="liste_titre" align="right">'.$langs->trans("PriceQtyMinHT").'</td>';
				// Charges ????
				if ($conf->global->PRODUCT_CHARGES)
				{
					if (! empty($conf->margin->enabled)) print '<td align="right">'.$langs->trans("Charges").'</td>';
				}
				print_liste_field_titre($langs->trans("UnitPriceHT"),$_SERVER["PHP_SELF"],"pfp.unitprice","",$param,'align="right"',$sortfield,$sortorder);
				print '<td class="liste_titre" align="right">'.$langs->trans("DiscountQtyMin").'</td>';
				// Charges ????
				if ($conf->global->PRODUCT_CHARGES)
				{
					if (! empty($conf->margin->enabled)) print '<td align="right">'.$langs->trans("UnitCharges").'</td>';
				}
				print '<td class="liste_titre"></td>';
				print "</tr>\n";

				$product_fourn = new ProductFournisseur($db);
				$product_fourn_list = $product_fourn->list_product_fournisseur_price($product->id, $sortfield, $sortorder);

				if (count($product_fourn_list)>0)
				{
					$var=true;

					foreach($product_fourn_list as $productfourn)
					{
						$var=!$var;

						print "<tr ".$bc[$var].">";

						print '<td>'.$productfourn->getSocNomUrl(1,'supplier').'</td>';

						// Supplier
						print '<td align="left">'.$productfourn->fourn_ref.'</td>';

						//Availability
						if(!empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
						{
							$form->load_cache_availability();
                			$availability= $form->cache_availability[$productfourn->fk_availability]['label'];
							print '<td align="left">'.$availability.'</td>';
						}

						// Quantity
						print '<td align="right">';
						print $productfourn->fourn_qty;
						print '</td>';

						// VAT rate
						print '<td align="right">';
						print vatrate($productfourn->fourn_tva_tx,true);
						print '</td>';

						// Price for the quantity
						print '<td align="right">';
						print $productfourn->fourn_price?price($productfourn->fourn_price):"";
						print '</td>';

						// Charges ????
						if ($conf->global->PRODUCT_CHARGES)
						{
							if (! empty($conf->margin->enabled))
							{
								print '<td align="right">';
								print $productfourn->fourn_charges?price($productfourn->fourn_charges):"";
								print '</td>';
							}
						}

						// Unit price
						print '<td align="right">';
						print price($productfourn->fourn_unitprice);
						//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
						print '</td>';

						// Discount
						print '<td align="right">';
						print price2num($productfourn->fourn_remise_percent).'%';
						print '</td>';

						// Charges ????
						if ($conf->global->PRODUCT_CHARGES)
						{
							if (! empty($conf->margin->enabled))
							{
								print '<td align="right">';
								print $productfourn->fourn_unitcharges?price($productfourn->fourn_unitcharges) : ($productfourn->fourn_qty?price($productfourn->fourn_charges/$productfourn->fourn_qty):"&nbsp;");
								print '</td>';
							}
						}

						if (is_object($hookmanager))
						{
							$parameters=array('id_pfp'=>$productfourn->product_fourn_price_id,'id_fourn'=>$id_fourn,'prod_id'=>$product->id);
						    $reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$object,$action);
						}

						// Modify-Remove
						print '<td align="center">';
						if ($user->rights->produit->creer || $user->rights->service->creer)
						{
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$product->id.'&amp;socid='.$productfourn->fourn_id.'&amp;action=add_price&amp;rowid='.$productfourn->product_fourn_price_id.'">'.img_edit()."</a>";
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$product->id.'&amp;socid='.$productfourn->fourn_id.'&amp;action=remove_pf&amp;rowid='.$productfourn->product_fourn_price_id.'">'.img_picto($langs->trans("Remove"),'disable.png').'</a>';
						}

						print '</td>';

						print '</tr>';
					}
				}

				print '</table>';
			}
		}
	}
}
else
{
	print $langs->trans("ErrorUnknown");
}


// End of page
llxFooter();
$db->close();
