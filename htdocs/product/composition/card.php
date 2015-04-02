<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011-2014 Juanjo Menent        <jmenent@2byte.es>
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

$langs->load("bills");
$langs->load("products");
$langs->load("stocks");

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$cancel=GETPOST('cancel','alpha');
$key=GETPOST('key');
$parent=GETPOST('parent');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$object = new Product($db);
$objectid=0;
if ($id > 0 || ! empty($ref))
{
	$result = $object->fetch($id,$ref);
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
					setEventMessage($langs->trans("ErrorAssociationIsFatherOfThis"), 'errors');
				} else {
					setEventMessage($object->error, 'errors');
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
				setEventMessage($object->error, 'errors');
			}
		}
	}
	if (! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}
else if($action==='save_composed_product')
{
	$TProduct = GETPOST('TProduct', 'array');
	if(!empty($TProduct))
	{
		foreach ($TProduct as $id_product => $row)
		{
			if ($row['qty'] > 0) $object->update_sousproduit($id, $id_product, $row['qty'], isset($row['incdec']) ? 1 : 0 );
			else $object->del_sousproduit($id, $id_product);
		}
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

    $sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.barcode, p.price, p.price_ttc, p.price_base_type,';
    $sql.= ' p.fk_product_type, p.tms as datem';
	if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= ', pl.label as labelm, pl.description as descriptionm';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON p.rowid = cp.fk_product';
	if (! empty($conf->global->MAIN_MULTILANGS)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND lang='".($current_lang)."'";
	$sql.= ' WHERE p.entity IN ('.getEntity('product', 1).')';
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
//print $sql;


llxHeader("","",$langs->trans("CardProduct".$object->type));

$head=product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==Product::TYPE_SERVICE?'service':'product');
dol_fiche_head($head, 'subproduct', $titre, 0, $picto);


if ($id > 0 || ! empty($ref))
{
	/*
	 * Fiche en mode edition
	 */
	if ($user->rights->produit->lire || $user->rights->service->lire)
	{
		print '<table class="border" width="100%">';

		print "<tr>";

		$nblignes=6;
		if ($object->isproduct() && ! empty($conf->stock->enabled)) $nblignes++;
		if ($object->isservice()) $nblignes++;

			// Reference
			print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($object,'ref','',1,'ref');
			print '</td>';

		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->libelle.'</td>';
		print '</tr>';

		// Nature
		if($object->type!=Product::TYPE_SERVICE)
		{
			print '<tr><td>'.$langs->trans("Nature").'</td><td colspan="2">';
			print $object->getLibFinished();
			print '</td></tr>';
		}

		if (empty($conf->global->PRODUIT_MULTIPRICES))
		{
		    // Price
			print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
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

		dol_fiche_end();


		$prodsfather = $object->getFather(); 		// Parent Products
		$object->get_sousproduits_arbo();			// Load $object->sousprod
		$prods_arbo=$object->get_arbo_each_prod();
		$nbofsubsubproducts=count($prods_arbo);		// This include sub sub product into nb
		$prodschild = $object->getChildsArbo($id,1);
		$nbofsubproducts=count($prodschild);		// This include only first level of childs


		// Number of parent virtual products
		print $form->textwithpicto($langs->trans("ParentProductsNumber").': '.count($prodsfather), $langs->trans('IfZeroItIsNotUsedByVirtualProduct'));

		if (count($prodsfather) > 0)
		{
			print $langs->trans("ProductParentList").'<br>';
			print '<table class="nobordernopadding">';
			foreach($prodsfather as $value)
			{
				$idprod= $value["id"];
				$productstatic->id=$idprod;// $value["id"];
				$productstatic->type=$value["fk_product_type"];
				$productstatic->ref=$value['label'];
				print '<tr>';
				print '<td>'.$productstatic->getNomUrl(1,'composition').'</td>';;
				print '</tr>';
			}
			print '</table>';
		}


		print '<br>'."\n";


		// Number of subproducts
		print $form->textwithpicto($langs->trans("AssociatedProductsNumber").': '.(empty($conf->global->PRODUCT_SHOW_SUB_SUB_PRODUCTS)?$nbofsubproducts:$nbofsubsubproducts), $langs->trans('IfZeroItIsNotAVirtualProduct'));

		// List of subproducts
		if (count($prods_arbo) > 0)
		{
			$atleastonenotdefined=0;
			print $langs->trans("ProductAssociationList").'<br>';

			print '<form name="formComposedProduct" action="'.$_SERVER['PHP_SELF'].'" method="post">';
			print '<input type="hidden" name="action" value="save_composed_product" />';
			print '<input type="hidden" name="id" value="'.$id.'" />';

			print '<table class="centpercent noborder">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('ComposedProduct').'</td>';
			print '<td>'.$langs->trans('Label').'</td>';
			print '<td align="right" colspan="2">'.$langs->trans('MinSupplierPrice').'</td>';
			if (! empty($conf->stock->enabled)) print '<td align="right">'.$langs->trans('Stock').'</td>';
			print '<td align="center">'.$langs->trans('Qty').'</td>';
			print '<td align="center">'.$langs->trans('ComposedProductIncDecStock').'</td>';
			print '</tr>'."\n";

			foreach($prods_arbo as $value)
			{
				$productstatic->id=$value['id'];
				$productstatic->type=$value['type'];
				$productstatic->label=$value['label'];

				if ($value['level'] <= 1)
				{
					$class=($class=='impair')?'pair':'impair';
					print '<tr class="'.$class.'">';

					$notdefined=0;
					$productstatic->ref=$value['ref'];
					$nb_of_subproduct = $value['nb'];

					print '<td>'.$productstatic->getNomUrl(1,'composition').'</td>';
					print '<td>'.$productstatic->label.'</td>';

					// Best buying price
					print '<td align="right">';
					if ($product_fourn->find_min_price_product_fournisseur($productstatic->id) > 0)
					{
						print ' &nbsp; '.$langs->trans("BuyingPriceMinShort").': ';
				    	if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur(0,0);
				    	else { print $langs->trans("NotDefined"); $notdefined++; $atleastonenotdefined++; }
					}
					print '</td>';

					$totalline=price2num($value['nb'] * $product_fourn->fourn_unitprice, 'MT');
					$total+=$totalline;
					print '<td align="right">';
					print ($notdefined?'':($value['nb']> 1 ? $value['nb'].'x' : '').price($product_fourn->fourn_unitprice,'','',0,0,-1,$conf->currency));
					print '</td>';

					// Stock
					if (! empty($conf->stock->enabled)) print '<td align="right">'.$value['stock'].'</td>';	// Real stock

					// Qty + IncDec
					if ($user->rights->produit->creer || $user->rights->service->creer)
					{
						print '<td align="center"><input type="text" value="'.$nb_of_subproduct.'" name="TProduct['.$productstatic->id.'][qty]" size="4" /></td>';
						print '<td align="center"><input type="checkbox" name="TProduct['.$productstatic->id.'][incdec]" value="1" '.($value['incdec']==1?'checked="checked"':''  ).' /></td>';

					}
					else{
						print '<td>'.$nb_of_subproduct.'</td>';
						print '<td>'.($value['incdec']==1?'x':''  ).'</td>';
					}

					print '</tr>'."\n";
				}
				else 	// By default, we do not show this. It makes screen very difficult to understand
				{
					$hide='';
					if (empty($conf->global->PRODUCT_SHOW_SUB_SUB_PRODUCTS)) $hide=' hideobject';

					$class=($class=='impair')?'pair':'impair';
					print '<tr class="'.$class.$hide.'" id="sub-'.$value['id_parent'].'">';

					//$productstatic->ref=$value['label'];
					$productstatic->ref=$value['ref'];
					print '<td>';
					for ($i=0; $i < $value['level']; $i++)	print ' &nbsp; &nbsp; ';	// Add indentation
					print $productstatic->getNomUrl(1,'composition').'</td>';
					print '<td>'.$productstatic->label.'</td>';

					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';

					if (! empty($conf->stock->enabled)) print '<td></td>';	// Real stock
					print '<td align="center">'.$value['nb'].'</td>';
					print '<td>&nbsp;</td>';

					print '</tr>'."\n";
				}
			}
			print '<tr class="liste_total">';
			print '<td class="liste_total"></td>';
			print '<td class="liste_total"></td>';

			// Minimum buying price
			print '<td class="liste_total" align="right">';
			print $langs->trans("TotalBuyingPriceMin");
			print '</td>';

			print '<td class="liste_total" align="right">';
			if ($atleastonenotdefined) print $langs->trans("Unknown").' ('.$langs->trans("SomeSubProductHaveNoPrices").')';
			print ($atleastonenotdefined?'':price($total,'','',0,0,-1,$conf->currency));
			print '</td>';

			// Stock
			if (! empty($conf->stock->enabled)) print '<td class="liste_total" align="right">&nbsp;</td>';

			print '<td align="right" colspan="2">';
			if ($user->rights->produit->creer || $user->rights->service->creer)
			{
				print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
			}
			print '</td>';
			print '</tr>'."\n";
			print '</table>';

			/*if($user->rights->produit->creer || $user->rights->service->creer) {
				print '<input type="submit" class="button" value="'.$langs->trans('Save').'">';
			}*/

			print '</form>';
		}

		// Form with product to add
		if ((empty($action) || $action == 'view' || $action == 'edit' || $action == 'search' || $action == 're-edit') && ($user->rights->produit->creer || $user->rights->service->creer))
		{
			print '<br>';

			$rowspan=1;
			if (! empty($conf->categorie->enabled)) $rowspan++;

	        print_fiche_titre($langs->trans("ProductToAddSearch"),'','');
			print '<form action="'.DOL_URL_ROOT.'/product/composition/card.php?id='.$id.'" method="POST">';
			print '<table class="border" width="100%"><tr><td>';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print $langs->trans("KeywordFilter").' &nbsp; ';
			print '</td>';
			print '<td><input type="text" name="key" value="'.$key.'">';
			print '<input type="hidden" name="action" value="search">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '</td>';
			print '<td rowspan="'.$rowspan.'" valign="middle">';
			print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
			print '</td></tr>';
			if (! empty($conf->categorie->enabled))
			{
				print '<tr><td>'.$langs->trans("CategoryFilter").' &nbsp; </td>';
				print '<td class="maxwidthonsmartphone">'.$form->select_all_categories(0, $parent).'</td></tr>';
			}
			print '</table>';
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
			print '<table class="nobordernopadding" width="100%">';
			print '<tr class="liste_titre">';
			print '<th class="liste_titre">'.$langs->trans("ComposedProduct").'</td>';
			print '<th class="liste_titre">'.$langs->trans("Label").'</td>';
			//print '<th class="liste_titre" align="center">'.$langs->trans("IsInPackage").'</td>';
			print '<th class="liste_titre" align="right">'.$langs->trans("Qty").'</td>';
			print '<th align="center">'.$langs->trans('ComposedProductIncDecStock').'</th>';
			print '</tr>';
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i=0;
				$var=true;

				if($num == 0) print '<tr><td colspan="4">'.$langs->trans("NoMatchFound").'</td></tr>';

				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					if($objp->rowid != $id)
					{
						// check if a product is not already a parent product of this one
						$prod_arbo=new Product($db);
						$prod_arbo->id=$objp->rowid;
						if ($prod_arbo->type==Product::TYPE_ASSEMBLYKIT || $prod_arbo->type== Product::TYPE_STOCKKIT)
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
						$var=!$var;
						print "\n<tr ".$bc[$var].">";
						$productstatic->id=$objp->rowid;
						$productstatic->ref=$objp->ref;
						$productstatic->libelle=$objp->label;
						$productstatic->type=$objp->type;

						print '<td>'.$productstatic->getNomUrl(1,'',24).'</td>';
						$labeltoshow=$objp->label;
						if ($conf->global->MAIN_MULTILANGS && $objp->labelm) $labeltoshow=$objp->labelm;

						print '<td>'.$labeltoshow.'</td>';


						if($object->is_sousproduit($id, $objp->rowid))
						{
							//$addchecked = ' checked="checked"';
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
						/*print '<td align="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
						print '<input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';*/
						// Qty
						print '<td align="right"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'"><input type="text" size="2" name="prod_qty_'.$i.'" value="'.($qty?$qty:'').'"></td>';

						// Inc Dec
						print '<td align="center">';
						if ($qty) print '<input type="checkbox" name="prod_incdec_'.$i.'" value="1" '.($incdec?'checked="checked"':'').'>';
						else
						{
							// TODO Hide field and show it when setting a qty
							print '<input type="checkbox" name="prod_incdec_'.$i.'" value="1" checked="checked">';
							//print '<input type="checkbox" disabled="true" name="prod_incdec_'.$i.'" value="1" checked="checked">';
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


llxFooter();

$db->close();
