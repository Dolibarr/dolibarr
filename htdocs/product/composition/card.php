<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *  \file       htdocs/product/composition/card.php
 *  \ingroup    product
 *  \brief      Page de la fiche produit
 */

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'products', 'stocks'));

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$cancel=GETPOST('cancel', 'alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

$object = new Product($db);
$objectid=0;
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id, $ref);
	$objectid=$object->id;
	$id=$object->id;
}


/*
 * Actions
 */

if ($cancel) $action ='';

// Action association d'un sousproduit
if ($action == 'add_prod' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	$error=0;
	for ($i=0; $i<$_POST["max_prod"]; $i++)
	{
		if ($_POST["prod_qty_".$i] > 0)
		{
			if ($object->add_sousproduit($id, $_POST["prod_id_".$i], $_POST["prod_qty_".$i], $_POST["prod_incdec_".$i]) > 0)
			{
				//var_dump($id.' - '.$_POST["prod_id_".$i].' - '.$_POST["prod_qty_".$i]);exit;
				$action = 'edit';
			}
			else
			{
				$error++;
				$action = 're-edit';
				if ($object->error == "isFatherOfThis") {
					setEventMessages($langs->trans("ErrorAssociationIsFatherOfThis"), null, 'errors');
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
		else
		{
			if ($object->del_sousproduit($id, $_POST["prod_id_".$i]) > 0)
			{
				$action = 'edit';
			}
			else
			{
				$error++;
				$action = 're-edit';
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}
	if (! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}
elseif($action==='save_composed_product')
{
	$TProduct = GETPOST('TProduct', 'array');
	if (!empty($TProduct))
	{
		foreach ($TProduct as $id_product => $row)
		{
			if ($row['qty'] > 0) $object->update_sousproduit($id, $id_product, $row['qty'], isset($row['incdec']) ? 1 : 0);
			else $object->del_sousproduit($id, $id_product);
		}
		setEventMessages('RecordSaved', null);
	}
	$action='';
}


/*
 * View
 */

$product_fourn = new ProductFournisseur($db);
$productstatic = new Product($db);
$form = new Form($db);

// action recherche des produits par mot-cle et/ou par categorie
if ($action == 'search')
{
	$current_lang = $langs->getDefaultLang();

    $sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.fk_product_type as type, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
    $sql.= ' p.fk_product_type, p.tms as datem';
	if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= ', pl.label as labelm, pl.description as descriptionm';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON p.rowid = cp.fk_product';
	if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND lang='".($current_lang)."'";
	$sql.= ' WHERE p.entity IN ('.getEntity('product').')';
	if ($key != "")
	{
		// For natural search
		$params = array('p.ref', 'p.label', 'p.description', 'p.note');
		// multilang
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$params[] = 'pl.label';
			$params[] = 'pl.description';
			$params[] = 'pl.note';
		}
		if (! empty($conf->barcode->enabled)) {
			$params[] = 'p.barcode';
		}
		$sql .= natural_search($params, $key);
	}
	if (! empty($conf->categorie->enabled) && ! empty($parent) && $parent != -1)
	{
		$sql.= " AND cp.fk_categorie ='".$db->escape($parent)."'";
	}
	$sql.= " ORDER BY p.ref ASC";

	$resql = $db->query($sql);
}

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT))
{
	$title = $langs->trans('Product')." ". $shortlabel ." - ".$langs->trans('AssociatedProducts');
	$helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE))
{
	$title = $langs->trans('Service')." ". $shortlabel ." - ".$langs->trans('AssociatedProducts');
	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
}

llxHeader('', $title, $helpurl);

$head=product_prepare_head($object);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==Product::TYPE_SERVICE?'service':'product');
dol_fiche_head($head, 'subproduct', $titre, -1, $picto);


if ($id > 0 || ! empty($ref))
{
	/*
	 * Fiche en mode edition
	 */
	if ($user->rights->produit->lire || $user->rights->service->lire)
	{
        $linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        $shownav = 1;
        if ($user->societe_id && ! in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

        dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', '', '', '', 0, '', '', 0);

        if ($object->type!=Product::TYPE_SERVICE || empty($conf->global->PRODUIT_MULTIPRICES))
        {
            print '<div class="fichecenter">';
    	    print '<div class="underbanner clearboth"></div>';

    	    print '<table class="border centpercent tableforfield">';

    		// Nature
    		if ($object->type!=Product::TYPE_SERVICE)
    		{
    			print '<tr><td class="titlefield">'.$langs->trans("Nature").'</td><td>';
    			print $object->getLibFinished();
    			print '</td></tr>';
    		}

    		if (empty($conf->global->PRODUIT_MULTIPRICES))
    		{
    		    // Price
    			print '<tr><td class="titlefield">'.$langs->trans("SellingPrice").'</td><td>';
    			if ($object->price_base_type == 'TTC')
    			{
    				print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
    			}
    			else
    			{
    				print price($object->price).' '.$langs->trans($object->price_base_type?$object->price_base_type:'HT');
    			}
    			print '</td></tr>';

    			// Price minimum
    			print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
    			if ($object->price_base_type == 'TTC')
    			{
    				print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
    			}
    			else
    			{
    				print price($object->price_min).' '.$langs->trans($object->price_base_type?$object->price_base_type:'HT');
    			}
    			print '</td></tr>';
    		}

            print '</table>';
            print '</div>';
        }

		dol_fiche_end();

        print '<br>';

		$prodsfather = $object->getFather(); 		// Parent Products
		$object->get_sousproduits_arbo();			// Load $object->sousprods
		$prods_arbo=$object->get_arbo_each_prod();

		$nbofsubsubproducts=count($prods_arbo);		// This include sub sub product into nb
		$prodschild = $object->getChildsArbo($id, 1);
		$nbofsubproducts=count($prodschild);		// This include only first level of childs


		print '<div class="fichecenter">';

		print load_fiche_titre($langs->trans("ProductParentList"), '', '');

		print '<table class="liste">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('ParentProducts').'</td>';
		print '<td>'.$langs->trans('Label').'</td>';
		print '<td>'.$langs->trans('Qty').'</td>';
		print '</td>';
		if (count($prodsfather) > 0)
		{
			foreach($prodsfather as $value)
			{
				$idprod= $value["id"];
				$productstatic->id=$idprod;// $value["id"];
				$productstatic->type=$value["fk_product_type"];
				$productstatic->ref=$value['ref'];
				$productstatic->label=$value['label'];
				$productstatic->entity=$value['entity'];

				print '<tr class="oddeven">';
				print '<td>'.$productstatic->getNomUrl(1, 'composition').'</td>';
				print '<td>'.$productstatic->label.'</td>';
				print '<td>'.$value['qty'].'</td>';
				print '</tr>';
			}
		}
		else
		{
			print '<tr class="oddeven">';
			print '<td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';

		print '<br>'."\n";


		print '<div class="fichecenter">';

		$atleastonenotdefined=0;
		print load_fiche_titre($langs->trans("ProductAssociationList"), '', '');

		print '<form name="formComposedProduct" action="'.$_SERVER['PHP_SELF'].'" method="post">';
		print '<input type="hidden" name="action" value="save_composed_product" />';
		print '<input type="hidden" name="id" value="'.$id.'" />';

		print '<table class="liste">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('ComposedProduct').'</td>';
		print '<td>'.$langs->trans('Label').'</td>';
		print '<td class="right" colspan="2">'.$langs->trans('MinSupplierPrice').'</td>';
		print '<td class="right" colspan="2">'.$langs->trans('MinCustomerPrice').'</td>';
		if (! empty($conf->stock->enabled)) print '<td class="right">'.$langs->trans('Stock').'</td>';
		print '<td class="center">'.$langs->trans('Qty').'</td>';
		print '<td class="center">'.$langs->trans('ComposedProductIncDecStock').'</td>';
		print '</tr>'."\n";

		$totalsell=0;
		if (count($prods_arbo))
		{
			foreach($prods_arbo as $value)
			{
				$productstatic->fetch($value['id']);

				if ($value['level'] <= 1)
				{
					print '<tr class="oddeven">';

					$notdefined=0;
					$nb_of_subproduct = $value['nb'];

					print '<td>'.$productstatic->getNomUrl(1, 'composition').'</td>';
					print '<td>'.$productstatic->label.'</td>';

					// Best buying price
					print '<td class="right">';
					if ($product_fourn->find_min_price_product_fournisseur($productstatic->id) > 0)
					{
						print $langs->trans("BuyingPriceMinShort").': ';
				    	if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur(0, 0);
				    	else { print $langs->trans("NotDefined"); $notdefined++; $atleastonenotdefined++; }
					}
					print '</td>';

					// For avoid a non-numeric value
					$fourn_unitprice = (!empty($product_fourn->fourn_unitprice)?$product_fourn->fourn_unitprice:0);
					$fourn_remise_percent = (!empty($product_fourn->fourn_remise_percent)?$product_fourn->fourn_remise_percent:0);
					$fourn_remise = (!empty($product_fourn->fourn_remise)?$product_fourn->fourn_remise:0);

					$totalline=price2num($value['nb'] * ($fourn_unitprice * (1 - $fourn_remise_percent/100) - $fourn_remise), 'MT');
					$total+=$totalline;

					print '<td class="right">';
					print ($notdefined?'':($value['nb']> 1 ? $value['nb'].'x' : '').price($fourn_unitprice, '', '', 0, 0, -1, $conf->currency));
					print '</td>';

					// Best selling price
					$pricesell=$productstatic->price;
					if (! empty($conf->global->PRODUIT_MULTIPRICES))
					{
						$pricesell='Variable';
					}
					else
					{
						$totallinesell=price2num($value['nb'] * ($pricesell), 'MT');
						$totalsell+=$totallinesell;
					}
					print '<td class="right" colspan="2">';
					print ($notdefined?'':($value['nb']> 1 ? $value['nb'].'x' : ''));
					if (is_numeric($pricesell)) print price($pricesell, '', '', 0, 0, -1, $conf->currency);
					else print $langs->trans($pricesell);
					print '</td>';

					// Stock
					if (! empty($conf->stock->enabled)) print '<td class="right">'.$value['stock'].'</td>';	// Real stock

					// Qty + IncDec
					if ($user->rights->produit->creer || $user->rights->service->creer)
					{
						print '<td class="center"><input type="text" value="'.$nb_of_subproduct.'" name="TProduct['.$productstatic->id.'][qty]" size="4" /></td>';
						print '<td class="center"><input type="checkbox" name="TProduct['.$productstatic->id.'][incdec]" value="1" '.($value['incdec']==1?'checked':''  ).' /></td>';
					}
					else{
						print '<td>'.$nb_of_subproduct.'</td>';
						print '<td>'.($value['incdec']==1?'x':''  ).'</td>';
					}

					print '</tr>'."\n";
				}
				else
				{
					$hide='';
					if (empty($conf->global->PRODUCT_SHOW_SUB_SUB_PRODUCTS)) $hide=' hideobject';	// By default, we do not show this. It makes screen very difficult to understand

					print '<tr class="oddeven'.$hide.'" id="sub-'.$value['id_parent'].'">';

					//$productstatic->ref=$value['label'];
					$productstatic->ref=$value['ref'];
					print '<td>';
					for ($i=0; $i < $value['level']; $i++)	print ' &nbsp; &nbsp; ';	// Add indentation
					print $productstatic->getNomUrl(1, 'composition').'</td>';
					print '<td>'.$productstatic->label.'</td>';

					// Best buying price
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';
					// Best selling price
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';

					if (! empty($conf->stock->enabled)) print '<td></td>';	// Real stock
					print '<td class="center">'.$value['nb'].'</td>';
					print '<td>&nbsp;</td>';

					print '</tr>'."\n";
				}
			}

			print '<tr class="liste_total">';
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';

			// Minimum buying price
			print '<td class="liste_total right">';
			print $langs->trans("TotalBuyingPriceMinShort");
			print '</td>';

			print '<td class="liste_total right">';
			if ($atleastonenotdefined) print $langs->trans("Unknown").' ('.$langs->trans("SomeSubProductHaveNoPrices").')';
			print ($atleastonenotdefined?'':price($total, '', '', 0, 0, -1, $conf->currency));
			print '</td>';

			// Minimum selling price
			print '<td class="liste_total right">';
			print $langs->trans("TotalSellingPriceMinShort");
			print '</td>';

			print '<td class="liste_total right">';
			if ($atleastonenotdefined) print $langs->trans("Unknown").' ('.$langs->trans("SomeSubProductHaveNoPrices").')';
			print ($atleastonenotdefined?'':price($totalsell, '', '', 0, 0, -1, $conf->currency));
			print '</td>';

			// Stock
			if (! empty($conf->stock->enabled)) print '<td class="liste_total right">&nbsp;</td>';

			print '<td class="right" colspan="2">';
			if ($user->rights->produit->creer || $user->rights->service->creer)
			{
				print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
			}
			print '</td>';
			print '</tr>'."\n";
		}
		else
		{
			$colspan=8;
			if (! empty($conf->stock->enabled)) $colspan++;

			print '<tr class="oddeven">';
			print '<td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("None").'</td>';
			print '</tr>';
		}

		print '</table>';

		/*if($user->rights->produit->creer || $user->rights->service->creer) {
			print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
		}*/

		print '</form>';
		print '</div>';



		// Form with product to add
		if ((empty($action) || $action == 'view' || $action == 'edit' || $action == 'search' || $action == 're-edit') && ($user->rights->produit->creer || $user->rights->service->creer))
		{
			print '<br>';

			$rowspan=1;
			if (! empty($conf->categorie->enabled)) $rowspan++;

	        print load_fiche_titre($langs->trans("ProductToAddSearch"), '', '');
			print '<form action="'.DOL_URL_ROOT.'/product/composition/card.php?id='.$id.'" method="POST">';
			print '<input type="hidden" name="action" value="search">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '<div class="inline-block">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print $langs->trans("KeywordFilter").': ';
			print '<input type="text" name="key" value="'.$key.'"> &nbsp; ';
			print '</div>';
			if (! empty($conf->categorie->enabled))
			{
				require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
				print '<div class="inline-block">'.$langs->trans("CategoryFilter").': ';
				print $form->select_all_categories(Categorie::TYPE_PRODUCT, $parent, 'parent').' &nbsp; </div>';
				print ajax_combobox('parent');
			}
			print '<div class="inline-block">';
			print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
			print '</div>';
			print '</form>';
		}


		// List of products
		if ($action == 'search')
		{
			print '<br>';
			print '<form action="'.DOL_URL_ROOT.'/product/composition/card.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add_prod">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th class="liste_titre">'.$langs->trans("ComposedProduct").'</td>';
			print '<th class="liste_titre">'.$langs->trans("Label").'</td>';
			//print '<th class="liste_titre center">'.$langs->trans("IsInPackage").'</td>';
			print '<th class="liste_titre right">'.$langs->trans("Qty").'</td>';
			print '<th class="center">'.$langs->trans('ComposedProductIncDecStock').'</th>';
			print '</tr>';
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i=0;

				if($num == 0) print '<tr><td colspan="4">'.$langs->trans("NoMatchFound").'</td></tr>';

				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					if($objp->rowid != $id)
					{
						// check if a product is not already a parent product of this one
						$prod_arbo=new Product($db);
						$prod_arbo->id=$objp->rowid;
						// This type is not supported (not required to have virtual products working).
						if ($prod_arbo->type == Product::TYPE_ASSEMBLYKIT || $prod_arbo->type == Product::TYPE_STOCKKIT)
						{
							$is_pere=0;
							$prod_arbo->get_sousproduits_arbo();
							// associations sousproduits
							$prods_arbo = $prod_arbo->get_arbo_each_prod();
							if (count($prods_arbo) > 0)
							{
								foreach($prods_arbo as $key => $value)
								{
									if ($value[1]==$id)
									{
										$is_pere=1;
									}
								}
							}
							if ($is_pere==1)
							{
								$i++;
								continue;
							}
						}

						print "\n".'<tr class="oddeven">';

						$productstatic->id=$objp->rowid;
						$productstatic->ref=$objp->ref;
						$productstatic->label=$objp->label;
						$productstatic->type=$objp->type;
						$productstatic->entity=$objp->entity;

						print '<td>'.$productstatic->getNomUrl(1, '', 24).'</td>';
						$labeltoshow=$objp->label;
						if ($conf->global->MAIN_MULTILANGS && $objp->labelm) $labeltoshow=$objp->labelm;

						print '<td>'.$labeltoshow.'</td>';


						if($object->is_sousproduit($id, $objp->rowid))
						{
							//$addchecked = ' checked';
							$qty=$object->is_sousproduit_qty;
							$incdec=$object->is_sousproduit_incdec;
						}
						else
						{
							//$addchecked = '';
							$qty=0;
							$incdec=0;
						}
						// Contained into package
						/*print '<td class="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
						print '<input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';*/
						// Qty
						print '<td class="right"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'"><input type="text" size="2" name="prod_qty_'.$i.'" value="'.($qty?$qty:'').'"></td>';

						// Inc Dec
						print '<td class="center">';
						if ($qty) print '<input type="checkbox" name="prod_incdec_'.$i.'" value="1" '.($incdec?'checked':'').'>';
						else
						{
							// TODO Hide field and show it when setting a qty
							print '<input type="checkbox" name="prod_incdec_'.$i.'" value="1" checked>';
							//print '<input type="checkbox" disabled name="prod_incdec_'.$i.'" value="1" checked>';
						}
						print '</td>';

						print '</tr>';
					}
					$i++;
				}
			}
			else
			{
				dol_print_error($db);
			}
			print '</table>';
			print '<input type="hidden" name="max_prod" value="'.$i.'">';

			if($num > 0)
			{
				print '<br><div class="center">';
				print '<input type="submit" class="button" name="save" value="'.$langs->trans("Add").'/'.$langs->trans("Update").'">';
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
			}

			print '</form>';
		}
	}
}

// End of page
llxFooter();
$db->close();
