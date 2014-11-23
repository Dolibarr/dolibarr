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

$product = new Product($db);
$productid=0;
if ($id > 0 || ! empty($ref))
{
	$result = $product->fetch($id,$ref);
	$productid=$product->id;
	$id=$product->id;
}


/*
 * Actions
 */

// Action association d'un sousproduit
if ($action == 'add_prod' &&
$cancel <> $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$error=0;
	for ($i=0; $i<$_POST["max_prod"]; $i++)
	{
		if ($_POST["prod_id_chk".$i] > 0)
		{
			if($product->add_sousproduit($id, $_POST["prod_id_".$i],$_POST["prod_qty_".$i]) > 0)
			{
				$action = 'edit';
			}
			else
			{
				$error++;
				$action = 're-edit';
				if ($product->error == "isFatherOfThis") {
					setEventMessage($langs->trans("ErrorAssociationIsFatherOfThis"), 'errors');
				} else {
					setEventMessage($product->error, 'errors');
				}
			}
		}
		else
		{
			if ($product->del_sousproduit($id, $_POST["prod_id_".$i]) > 0)
			{
				$action = 'edit';
			}
			else
			{
				$error++;
				$action = 're-edit';
				setEventMessage($product->error, 'errors');
			}
		}
	}
	if (! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$product->id);
		exit;
	}
}

if ($cancel == $langs->trans("Cancel"))
{
	$action = '';
	header("Location: card.php?id=".$_POST["id"]);
	exit;
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


llxHeader("","",$langs->trans("CardProduct".$product->type));

$head=product_prepare_head($product, $user);
$titre=$langs->trans("CardProduct".$product->type);
$picto=($product->type==1?'service':'product');
dol_fiche_head($head, 'subproduct', $titre, 0, $picto);


if ($id > 0 || ! empty($ref))
{
/*	if ($result)
	{
		if ($action <> 'edit' && $action <> 'search' && $action <> 're-edit')
		{
			// mode visu

			print '<table class="border" width="100%">';

			print "<tr>";

			$nblignes=6;
			if ($product->isproduct() && ! empty($conf->stock->enabled)) $nblignes++;
			if ($product->isservice()) $nblignes++;

			// Reference
			print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($product,'ref','',1,'ref');
			print '</td></tr>';

			// Libelle
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
			print '</tr>';

			// Number of subproducts
			$prodsfather = $product->getFather(); // Parent Products
			$product->get_sousproduits_arbo();
			$prods_arbo=$product->get_arbo_each_prod();
			$nbofsubproducts=count($prods_arbo);
			print '<tr><td>'.$langs->trans("AssociatedProductsNumber").'</td><td>';
			print $form->textwithpicto($nbofsubproducts, $langs->trans('IfZeroItIsNotAVirtualProduct'));
			print '</td>';

			dol_fiche_end();


			// List of products into this virtual product
			if (count($prods_arbo) > 0)
			{
				print '<tr><td colspan="2">';
				print '<b>'.$langs->trans("ProductAssociationList").'</b><br>';
				print '<table class="nobordernopadding">';
				foreach($prods_arbo as $value)
				{
					$productstatic->id=$value['id'];
					$productstatic->type=$value['type'];
					$productstatic->ref=$value['fullpath'];
					if (! empty($conf->stock->enabled)) $productstatic->load_stock();
					//var_dump($value);
					//print '<pre>'.$productstatic->ref.'</pre>';
					//print $productstatic->getNomUrl(1).'<br>';
					//print $value[0];	// This contains a tr line.
					print '<tr>';
					//print '<td>'.$productstatic->getNomUrl(1,'composition').' ('.$value['nb'].($value['nb_total'] > $value['nb']?'->'.$value['nb_total']:'').') &nbsp &nbsp</td>';
					print '<td>'.$productstatic->getNomUrl(1,'composition').' ('.$value['nb'].') &nbsp &nbsp</td>';
					if (! empty($conf->stock->enabled)) print '<td>'.$langs->trans("Stock").' : <b>'.$productstatic->stock_reel.'</b></td>';
					print '</tr>';
				}
				print '</table>';
				print '</td></tr>';
			}

			// Number of parent virtual products
			print '<tr><td>'.$langs->trans("ParentProductsNumber").'</td><td>';
			print $form->textwithpicto(count($prodsfather), $langs->trans('IfZeroItIsNotUsedByVirtualProduct'));
			print '</td>';

			if (count($prodsfather) > 0)
			{
				print '<tr><td colspan="2">';
				print '<b>'.$langs->trans("ProductParentList").'</b><br>';
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
				print '</td></tr>';
			}

			print "</table>\n";

			dol_fiche_end();
		}
	}
*/
	/*
	 * Fiche en mode edition
	 */
	if ($user->rights->produit->lire || $user->rights->service->lire)
	{
		print '<table class="border" width="100%">';

		print "<tr>";

		$nblignes=6;
		if ($product->isproduct() && ! empty($conf->stock->enabled)) $nblignes++;
		if ($product->isservice()) $nblignes++;

			// Reference
			print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($product,'ref','',1,'ref');
			print '</td>';

		print '</tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '</tr>';

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

		// Number of subproducts
		$prodsfather = $product->getFather(); //Parent Products
		$product->get_sousproduits_arbo();			// Defined $product->sousprod
		$prods_arbo=$product->get_arbo_each_prod();
		$nbofsubproducts=count($prods_arbo);
		print '<tr><td>'.$langs->trans("AssociatedProductsNumber").'</td><td>';
		print $form->textwithpicto($nbofsubproducts, $langs->trans('IfZeroItIsNotAVirtualProduct'));
		print '</td>';
		print '</tr>';

		// List of subproducts
		if (count($prods_arbo) > 0)
		{
			$atleastonenotdefined=0;
			print '<tr><td colspan="2">';
			print $langs->trans("ProductAssociationList").'<br>';
			print '<table class="nobordernopadding centpercent">';
			foreach($prods_arbo as $value)
			{
				$productstatic->id=$value['id'];
				$productstatic->type=$value['type'];
				//print '<pre>'.$productstatic->ref.'</pre>';
				//print $productstatic->getNomUrl(1).'<br>';
				//var_dump($value);
				print '<tr>';
				if ($value['level'] <= 1)
				{
					$notdefined=0;
					$productstatic->ref=$value['fullpath'];
					print '<td>'.$productstatic->getNomUrl(1,'composition').' ('.$value['nb'].')</td>';
					print '<td align="right">';
					if ($product_fourn->find_min_price_product_fournisseur($productstatic->id) > 0)
					{
						print $langs->trans("BuyingPriceMinShort").': ';
				    	if ($product_fourn->product_fourn_price_id > 0) print $product_fourn->display_price_product_fournisseur(0,0);
				    	else { print $langs->trans("NotDefined"); $notdefined++; $atleastonenotdefined++; }
					}
					print '</td>';
					$totalline=price2num($value['nb'] * $product_fourn->fourn_unitprice, 'MT');
					$total+=$totalline;
					print '<td align="right">'.($notdefined?'':price($totalline,'','',0,0,-1,$conf->currency)).'</td>';
					if (! empty($conf->stock->enabled)) print '<td align="right">'.$langs->trans("Stock").': '.$value['stock'].'</td>';	// Real stock
				}
				else {
					$productstatic->ref=$value['label'];
					print '<td>';
					for ($i=0; $i < $value['level']; $i++)
					{
						print ' &nbsp; &nbsp; ';
					}
					print $productstatic->getNomUrl(1,'composition').' ('.$value['nb'].')</td>';
					print '<td><td>';
					print '<td><td>';
					if (! empty($conf->stock->enabled)) print '<td align="right"></td>';	// Real stock
				}
				print '</tr>';
			}
			print '<tr>';
			print '<td colspan="2">'.$langs->trans("TotalBuyingPriceMin").': ';
			if ($atleastonenotdefined) print $langs->trans("Unknown").' ('.$langs->trans("SomeSubProductHaveNoPrices").')';
			print '</td>';
			print '<td align="right">'.($atleastonenotdefined?'':price($total,'','',0,0,-1,$conf->currency)).'</td>';
			if (! empty($conf->stock->enabled)) print '<td class="liste_total" align="right">&nbsp;</td>';
			print '</tr>';
			print '</table>';
			print '</td></tr>';
		}

		// Number of parent virtual products
		print '<tr><td>'.$langs->trans("ParentProductsNumber").'</td><td>';
		print $form->textwithpicto(count($prodsfather), $langs->trans('IfZeroItIsNotUsedByVirtualProduct'));
		print '</td>';

		if (count($prodsfather) > 0)
		{
			print '<tr><td colspan="2">';
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
			print '</td></tr>';
		}

		print '</table>';

		dol_fiche_end();


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
			print '<th class="liste_titre">'.$langs->trans("Ref").'</td>';
			print '<th class="liste_titre">'.$langs->trans("Label").'</td>';
			print '<th class="liste_titre" align="center">'.$langs->trans("AddDel").'</td>';
			print '<th class="liste_titre" align="right">'.$langs->trans("Qty").'</td>';
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
						if ($prod_arbo->type==2 || $prod_arbo->type==3)
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
						if($product->is_sousproduit($id, $objp->rowid))
						{
							$addchecked = ' checked="checked"';
							$qty=$product->is_sousproduit_qty;
						}
						else
						{
							$addchecked = '';
							$qty="1";
						}
						print '<td align="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
						print '<input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';
						print '<td align="right"><input type="text" size="2" name="prod_qty_'.$i.'" value="'.$qty.'"></td>';
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
				print '<br><center><input type="submit" class="button" value="'.$langs->trans("Add").'/'.$langs->trans("Update").'">';
				print ' &nbsp; &nbsp; <input type="submit" class="button" value="'.$langs->trans("Cancel").'">';
				print '</center>';
			}

			print '</form>';
		}

	}
}


llxFooter();

$db->close();
