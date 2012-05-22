<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/product/fournisseurs.php
 *  \ingroup    product
 *  \brief      Page of tab suppliers for products
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.product.class.php");

$langs->load("products");
$langs->load("suppliers");
$langs->load("bills");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$socid=GETPOST('socid', 'int');
$error=0; $mesg = '';

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
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('pricesuppliercard'));


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');

if (! $sortfield) $sortfield="s.nom";
if (! $sortorder) $sortorder="ASC";


/*
 * Actions
 */

if ($action == 'remove_pf')
{
	$product = new ProductFournisseur($db);
	if ($product->fetch($id) > 0)
	{
		if ($_GET["rowid"])
		{
			$result=$product->remove_product_fournisseur_price($_GET["rowid"]);
			$action = '';
			$mesg = '<div class="ok">'.$langs->trans("PriceRemoved").'.</div>';
		}
	}
}

if ($action == 'updateprice' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
    $id_fourn=GETPOST("id_fourn");
    if (empty($id_fourn)) $id_fourn=GETPOST("search_id_fourn");
    $ref_fourn=GETPOST("ref_fourn");
    if (empty($ref_fourn)) $ref_fourn=GETPOST("search_ref_fourn");
    $quantity=GETPOST("qty");
    $tva_tx=GETPOST('tva_tx','alpha');

	if (empty($quantity))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Qty")).'</div>';
	}
	if (empty($ref_fourn))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("RefSupplier")).'</div>';
	}
	if ($id_fourn <= 0)
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Supplier")).'</div>';
	}
	if ($_POST["price"] < 0 || $_POST["price"] == '')
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Price")).'</div>';
	}

	$product = new ProductFournisseur($db);
	$result=$product->fetch($id);
	if ($result <= 0)
	{
	    $error++;
	    $mesg=$product->error;
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

				$mesg='<div class="error">'.$langs->trans("ReferenceSupplierIsAlreadyAssociatedWithAProduct",$productLink).'</div>';
			}
			else if ($ret < 0)
			{
				$error++;
				$mesg='<div class="error">'.$product->error.'</div>';
			}
		}

		if (! $error)
		{
			$supplier=new Fournisseur($db);
			$result=$supplier->fetch($id_fourn);

			$ret=$product->update_buyprice($quantity, $_POST["price"], $user, $_POST["price_base_type"], $supplier, $_POST["oselDispo"], $ref_fourn, $tva_tx);
			if ($ret < 0)
			{
				$error++;
				$mesg='<div class="error">'.$product->error.'</div>';
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

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
	$action = '';
	Header("Location: fournisseurs.php?id=".$_GET["id"]);
	exit;
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
			$picto=($product->type==1?'service':'product');
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


			dol_htmloutput_mesg($mesg);


			// Form to add or update a price
			if (($action == 'add_price' || $action == 'updateprice' ) && ($user->rights->produit->creer || $user->rights->service->creer))
			{
				$langs->load("suppliers");

				if ($_GET["rowid"])
				{
					$product->fetch_product_fournisseur_price($_GET["rowid"]);
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

				print '<tr><td class="fieldrequired">'.$langs->trans("Supplier").'</td><td colspan="3">';
				if ($_GET["rowid"])
				{
					$supplier=new Fournisseur($db);
					$supplier->fetch($socid);
					print $supplier->getNomUrl(1);
					print '<input type="hidden" name="id_fourn" value="'.$socid.'">';
					print '<input type="hidden" name="ref_fourn" value="'.$product->fourn_ref.'">';
					print '<input type="hidden" name="ref_fourn_price_id" value="'.$_GET["rowid"].'">';
				}
				else
				{
					$events=array();
					$events[]=array('method' => 'getVatRates', 'url' => dol_buildpath('/core/ajax/vatrates.php',1), 'htmlname' => 'tva_tx', 'params' => array());
					print $form->select_company(GETPOST("id_fourn"),'id_fourn','fournisseur=1',1,0,0,$events);
					
					if (is_object($hookmanager))
					{
						$parameters=array('filtre'=>"fournisseur=1",'html_name'=>'id_fourn','selected'=>GETPOST("id_fourn"),'showempty'=>1,'prod_id'=>$product->id);
					    echo $hookmanager->executeHooks('formCreateThirdpartyOptions',$parameters,$object,$action);
					}
				}
				print '</td></tr>';

				// Ref supplier
				print '<tr><td class="fieldrequired">'.$langs->trans("SupplierRef").'</td><td colspan="3">';
				if ($_GET["rowid"])
				{
					print $product->fourn_ref;
				}
				else
				{
					print '<input class="flat" name="ref_fourn" size="12" value="'.($_POST["ref_fourn"]?$_POST["ref_fourn"]:'').'">';
				}
				print '</td>';
				print '</tr>';
				
				// Vat rate
				print '<tr><td class="fieldrequired">'.$langs->trans("VATRate").'</td>';
				print '<td colspan="3">'.$form->load_tva('tva_tx',$product->tva_tx,$supplier,$mysoc).'</td></tr>';

				// Availability
				if (! empty($conf->global->FOURN_PRODUCT_AVAILABILITY))
				{
					$langs->load("propal");
					print '<tr><td>'.$langs->trans("Availability").'</td><td colspan="3">';
					$form->select_availability($product->fk_availability,"oselDispo",1);
					print '</td></tr>'."\n";
				}

				// Qty min
				print '<tr>';
				print '<td class="fieldrequired">'.$langs->trans("QtyMin").'</td>';
				print '<td>';
				$quantity = $_REQUEST["qty"] ? $_REQUEST["qty"] : "1";
				if ($_GET["rowid"])
				{
					print '<input type="hidden" name="qty" value="'.$product->fourn_qty.'">';
					print $product->fourn_qty;
				}
				else
				{
					print '<input class="flat" name="qty" size="5" value="'.$quantity.'">';
				}
				print '</td>';
				
				// Price qty min
				print '<td class="fieldrequired">'.$langs->trans("PriceQtyMin").'</td>';
				print '<td><input class="flat" name="price" size="8" value="'.($_POST["price"]?$_POST["price"]:(isset($product->fourn_price)?price($product->fourn_price):'')).'">';
				print '&nbsp;';
				print $form->select_PriceBaseType(($_POST["price_base_type"]?$_POST["price_base_type"]:$product->price_base_type), "price_base_type");
                print '</td>';
				print '</tr>';

				print '</table>';

				print '<br><center><input class="button" type="submit" value="'.$langs->trans("Save").'">';
				print '&nbsp; &nbsp;';
				print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

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
				print_liste_field_titre($langs->trans("UnitPriceHT"),$_SERVER["PHP_SELF"],"pfp.unitprice","",$param,'align="right"',$sortfield,$sortorder);
				print '<td class="liste_titre"></td>';
				print "</tr>\n";

				$product_fourn = new ProductFournisseur($db);
				$product_fourn_list = $product_fourn->list_product_fournisseur_price($product->id);

				if (count($product_fourn_list)>0)
				{
					$var=true;

					foreach($product_fourn_list as $productfourn)
					{
						$var=!$var;

						print "<tr ".$bc[$var].">";

						print '<td>'.$productfourn->getSocNomUrl(1).'</td>';

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

						// Price quantity
						print '<td align="right">';
						print $productfourn->fourn_price?price($productfourn->fourn_price):"";
						print '</td>';

						// Unit price
						print '<td align="right">';
						print price($productfourn->fourn_unitprice);
						//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
						print '</td>';

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
?>