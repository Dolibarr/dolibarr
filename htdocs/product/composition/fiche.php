<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Charles-Fr BENKE		<charles.fr@benke.fr>
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
 *  \file       htdocs/product/composition/fiche.php
 *  \ingroup    product
 *  \brief      Page de la fiche produit
 */

require "../../main.inc.php";

require_once DOL_DOCUMENT_ROOT."/core/lib/product.lib.php";
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
require_once DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php";

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
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype,$objcanvas);

$mesg = '';

$object = new Product($db);
$productid=0;
if ($id || $ref)
{
	$result = $object->fetch($id,$ref);
	$productid=$object->id;
}


/*
 * Actions
 */

// add sub-product to a product
if ($action == 'add_prod' &&
$cancel <> $langs->trans("Cancel") &&
($user->rights->produit->creer || $user->rights->service->creer))
{
	$error=0;
	for($i=0;$i<$_POST["max_prod"];$i++)
	{
		// print "<br> : ".$_POST["prod_id_chk".$i];
		if($_POST["prod_id_chk".$i] != "")
		{
			if($object->add_sousproduit($id, $_POST["prod_id_".$i],$_POST["prod_qty_".$i]) > 0)
			{
				$action = 'edit';
			}
			else
			{
				$error++;
				$action = 're-edit';
				if ($object->error == "isFatherOfThis") $mesg = '<div class="error">'.$langs->trans("ErrorAssociationIsFatherOfThis").'</div>';
				else $mesg=$object->error;
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
				$mesg=$product->error;
			}
		}
	}
	if (! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
}

if ($cancel == $langs->trans("Cancel"))
{
	$action = '';
	Header("Location: fiche.php?id=".$_POST["id"]);
	exit;
}

// build product on each store
if ($action == 'buildit')
{
	
	// Loop on each store
	$sql = "SELECT rowid, lieu, cp";
	$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
	$sql.= " WHERE statut = 1";
	$sql.= " ORDER BY cp ASC";
	
	dol_syslog("product/composition.fiche.php::Buildit composed product sql=".$sql);

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			$nbTotBuilded=0;
			// loop on each store
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$fk_entrepot=$obj->rowid;
				$nbToBuild= GETPOST('nbToBuild'.$fk_entrepot, 'int');
				
				// number to build on this store
				if ($nbToBuild > 0)
				{
					// How number of product is buildable on this store
					$nbmaxfabricable=$object->getNbProductBuildable($fk_entrepot, $id);
					// verify if the have enough component on the store
					if ($nbToBuild > $nbmaxfabricable)
					{
						//we can't build the quantity needed
						$error++;
						$action = 'build';
						$mesg='<div class="error">'.$obj->lieu." (".$obj->cp.") :".$langs->trans("ErrorNotEnoughtComponentToBuild").'</div>';
					}
					else
					{
						//print "==".$nbToBuild;
						// extract from store the component needed
						$prodsfather = $object->getFather(); //Parent Products
						$object->get_sousproduits_arbo();
						$prods_arbo = $object->get_arbo_each_prod();
						if (count($prods_arbo) > 0)
						{
							// Loop on products to extract to the store 
							foreach($prods_arbo as $value)
							{
								// only product, not the services
								if ($value['type']==0)
								{
									$productstatic = new Product($db);
									$productstatic->id=$value['id'];
									$productstatic->fetch($value['id']);
									
									$nbToUse=$value['nb']*$nbToBuild;
									// Extract product of the stock
									// 1 = build extract to store 
									$productstatic->correct_stock($user, $fk_entrepot, $nbToUse, 1, $langs->trans("ProductUsedForBuild"), $productstatic->price);
								}
							}
						}
						// At the end we build : add the new products to the store
						// need to be on the good product
						$result = $object->fetch($id,$ref);
						// 0 = build add to store
						$object->correct_stock($user, $fk_entrepot, $nbToBuild, 0, $langs->trans("ProductBuilded"), $object->price);
						$nbTotBuilded+=$nbToBuild;
					}
				}
				$i++;
			}
			// Little message to inform of the number of builded product
			$mesg='<div class="ok">'.$nbTotBuilded.' '.$langs->trans("ProductBuilded").'</div>';
		}
		// return on the product screen
		$result = $object->fetch($id,$ref);
		$productid=$object->id;
		$action ="";	
	}
}


/*
 * View
 */

// search products by keyword and/or categorie
if ($action == 'search')
{
	$current_lang = $langs->getDefaultLang();

	$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.fk_product_type as type';
	if ($conf->global->MAIN_MULTILANGS) $sql.= ', pl.label as labelm, pl.description as descriptionm';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON p.rowid = cp.fk_product';
	if ($conf->global->MAIN_MULTILANGS) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND lang='".($current_lang)."'";
	$sql.= ' WHERE p.entity IN ('.getEntity("product", 1).')';
	if ($key != "")
	{
		if ($conf->global->MAIN_MULTILANGS)
		{
			$sql.= " AND (p.ref LIKE '%".$key."%'";
			$sql.= " OR pl.label LIKE '%".$key."%')";
		}
		else
		{
			$sql.= " AND (p.ref LIKE '%".$key."%'";
			$sql.= " OR p.label LIKE '%".$key."%')";
		}
	}
	if ($conf->categorie->enabled && $parent != -1 and $parent)
	{
		$sql.= " AND cp.fk_categorie ='".$db->escape($parent)."'";
	}
	$sql.= " ORDER BY p.ref ASC";

	$resql = $db->query($sql);
}
//print $sql;

$productstatic = new Product($db);
$form = new Form($db);

llxHeader("","",$langs->trans("CardProduct".$product->type));

dol_htmloutput_mesg($mesg);


$head=product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==1?'service':'product');
dol_fiche_head($head, 'subproduct', $titre, 0, $picto);

if ($id || $ref)
{
	if ($result)
	{
		print '<table class="border" width="100%">';
		print "<tr>";

		$bproduit = ($object->isproduct()); 

		// Reference
		print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
		print $form->showrefnav($object,'ref','',1,'ref');
		print '</td></tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->libelle.'</td>';
		print '</tr>';

		// MultiPrix
		if ($conf->global->PRODUIT_MULTIPRICES)
		{
			if ($socid)
			{
				$soc = new Societe($db);
				$soc->id = $socid;
				$soc->fetch($socid);

				print '<tr><td>'.$langs->trans("SellingPrice").'</td>';

				if ($object->multiprices_base_type["$soc->price_level"] == 'TTC')
				{
					print '<td>'.price($object->multiprices_ttc["$soc->price_level"]);
				}
				else
				{
					print '<td>'.price($object->multiprices["$soc->price_level"]);
				}

				if ($object->multiprices_base_type["$soc->price_level"])
				{
					print ' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				}
				else
				{
					print ' '.$langs->trans($object->price_base_type);
				}
				print '</td></tr>';

				// Prix mini
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($object->multiprices_base_type["$soc->price_level"] == 'TTC')
				{
					print price($object->multiprices_min_ttc["$soc->price_level"]).' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				}
				else
				{
					print price($object->multiprices_min["$soc->price_level"]).' '.$langs->trans($object->multiprices_base_type["$soc->price_level"]);
				}
				print '</td></tr>';

				// TVA
				print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->multiprices_tva_tx["$soc->price_level"],true).'</td></tr>';
			}
			else
			{
				for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
				{
					// TVA
					if ($i == 1) // We show only price for level 1
					{
					     print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->multiprices_tva_tx[1],true).'</td></tr>';
					}
					
					print '<tr><td>'.$langs->trans("SellingPrice").' '.$i.'</td>';
		
					if ($object->multiprices_base_type["$i"] == 'TTC')
					{
						print '<td>'.price($object->multiprices_ttc["$i"]);
					}
					else
					{
						print '<td>'.price($object->multiprices["$i"]);
					}
		
					if ($object->multiprices_base_type["$i"])
					{
						print ' '.$langs->trans($object->multiprices_base_type["$i"]);
					}
					else
					{
						print ' '.$langs->trans($object->price_base_type);
					}
					print '</td></tr>';
		
					// Prix mini
					print '<tr><td>'.$langs->trans("MinPrice").' '.$i.'</td><td>';
					if ($object->multiprices_base_type["$i"] == 'TTC')
					{
						print price($object->multiprices_min_ttc["$i"]).' '.$langs->trans($object->multiprices_base_type["$i"]);
					}
					else
					{
						print price($object->multiprices_min["$i"]).' '.$langs->trans($object->multiprices_base_type["$i"]);
					}
					print '</td></tr>';
				}
			}
		}
		else
		{
			// TVA
			print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($object->tva_tx.($object->tva_npr?'*':''),true).'</td></tr>';
			
			// Price
			print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
			if ($object->price_base_type == 'TTC')
			{
				print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
				$sale="";
			}
			else
			{
				print price($object->price).' '.$langs->trans($object->price_base_type);
				$sale=$object->price;
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
				print price($object->price_min).' '.$langs->trans($object->price_base_type);
			}
			print '</td></tr>';
		}
		
		print '<tr><td>'.$langs->trans("PhysicalStock").'</td>';
		print '<td>'.$object->stock_reel.'</td></tr>';
		
		print '</table>';
		
		dol_fiche_end();


		$prodsfather = $object->getFather(); //Parent Products
		$object->get_sousproduits_arbo();
		// Number of subproducts
		print_fiche_titre($langs->trans("AssociatedProductsNumber").' : '.count($object->get_arbo_each_prod()),'','');

		// List of subproducts
		$prods_arbo = $object->get_arbo_each_prod();
		if (count($prods_arbo) > 0)
		{
			print '<b>'.$langs->trans("ProductAssociationList").'</b><BR>';
			print '<table class="border" >';
			print '<tr class="liste_titre">';
			print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
			print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
			print '<td class="liste_titre" width=50px align="center">'.$langs->trans("QtyNeed").'</td>';
			// on affiche la colonne stock même si cette fonction n'est pas active
			print '<td class="liste_titre" width=50px align="center">'.$langs->trans("Stock").'</td>'; 
			if ($conf->stock->enabled)
			{ 	// we display vwap titles
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPmp").'</td>';
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostPmpHT").'</td>';
			}
			else
			{ 	// we display price as latest purchasing unit price title
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitHA").'</td>';
				print '<td class="liste_titre" width=100px align="right">'.$langs->trans("CostHA").'</td>';
			}
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPriceHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("SellingPriceHT").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("ProfitAmount").'</td>';

			print '</tr>';
			$mntTot=0;
			$pmpTot=0;

			foreach($prods_arbo as $value)
			{
				$productstatic->id=$value['id'];
				$productstatic->fetch($value['id']);
				$productstatic->type=$value['type'];
				// verify if product have child then display it after the product name
				$tmpChildArbo=$productstatic->getChildsArbo($value['id']);
				$nbChildArbo="";
				if (count($tmpChildArbo) > 0) $nbChildArbo=" (".count($tmpChildArbo).")";

				print '<tr>';
				print '<td align="left">'.$productstatic->getNomUrl(1,'composition').$nbChildArbo.'</td>';
				print '<td align="left">'.$productstatic->label.'</td>';
				print '<td align="center">'.$value['nb'].'</td>';

				$price=$productstatic->price;
				if ($conf->stock->enabled)
				{	// we store vwap in variable pmp and display stock
					$pmp=$productstatic->pmp;
					if ($value['fk_product_type']==0)
					{ 	// if product
						$productstatic->load_stock();
						print '<td align=center>'.$productstatic->stock_reel.'</td>';
					}
					else
					{
						// no stock management for services
						print '<td></td>';
					}
				}
				else
				{ 	// sql query to fetch the most recent purchasing unit price
					$sql = "SELECT unitprice FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE fk_product = ".$value['id']." ORDER BY datec DESC";
					$resql=$db->query($sql);
					$tmp=$db->fetch_object($resql);
					$pmp=$tmp->unitprice; // we store data in pmp variable
				}		
				print '<td align="right">'.price($pmp).'</td>'; // display else vwap or else latest purchasing price
				print '<td align="right">'.price($pmp*$value['nb']).'</td>'; // display total line
				print '<td align="right">'.price($price).'</td>';
				print '<td align="right">'.price($price*$value['nb']).'</td>';
				print '<td align="right">'.price(($price-$pmp)*$value['nb']).'</td>'; 
				
				$mntTot=$mntTot+$productstatic->price*$value['nb'];
				$pmpTot=$pmpTot+$pmp*$value['nb']; // sub total calculation
				
				print '</tr>';

				//var_dump($value);
				//print '<pre>'.$productstatic->ref.'</pre>';
				//print $productstatic->getNomUrl(1).'<br>';
				//print $value[0];	// This contains a tr line.

			}
			print '<tr class="liste_total">';
			print '<td colspan=5 align=right >'.$langs->trans("Total").'</td>';
			print '<td align="right" >'.price($pmpTot).'</td>';
			print '<td ></td>';
			print '<td align="right" >'.price($mntTot).'</td>';
			print '<td align="right" >'.price($mntTot-$pmpTot).'</td>';
			print '</tr>';
			print '</table>';
		}
		print '<br>';
		
		// Number of parent products
		print_fiche_titre($langs->trans("ParentProductsNumber").' : '.count($prodsfather),'','');

		if (count($prodsfather) > 0)
		{
			print '<b>'.$langs->trans("ProductParentList").'</b><br>';
			print '<table class="border" >';
			print '<tr class="liste_titre">';
			print '<td class="liste_titre" width=100px align="left">'.$langs->trans("Ref").'</td>';
			print '<td class="liste_titre" width=200px align="left">'.$langs->trans("Label").'</td>';
			print '<td class="liste_titre" width=50px align="center">'.$langs->trans("Stock").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("UnitPmp").'</td>';
			print '<td class="liste_titre" width=100px align="right">'.$langs->trans("SellingPriceHT").'</td>';
			print '</tr>';
			foreach($prodsfather as $value)
			{
				$idprod= $value["id"];
				$productstatic->id=$idprod;// $value["id"];
				$productstatic->fetch($idprod);
				$productstatic->type=$value["fk_product_type"];
				
				print '<tr>';
				print '<td>'.$productstatic->getNomUrl(1,'composition').'</td>';;
				print '<td>'.$productstatic->label.'</td>';;
				
				if ($value['fk_product_type']==0)
				{
					if ($conf->stock->enabled) $productstatic->load_stock();
					if ($conf->stock->enabled) print '<td align=center>'.$productstatic->stock_reel.'</td>';
				}
				else
				{
					// no stock managment for the services
					print '<td></td>';
				}
				print '<td align="right">'.price($productstatic->pmp).'</td>';
				print '<td align="right">'.price($productstatic->price).'</td>';
				print '</tr>';
			}
			print '</table>';
		}


		$rowspan=1;
		if ($conf->categorie->enabled) $rowspan++;
		if ($action == 'edit' || $action == 'search' || $action == 're-edit' )
		{
			print '<br>';
			print_fiche_titre($langs->trans("ProductToAddSearch"),'','');
			print '<form action="'.DOL_URL_ROOT.'/product/composition/fiche.php?id='.$id.'" method="post">';
			print '<table class="border" width="50%"><tr><td>';
			print '<table class="nobordernopadding" width="100%">';
	
			print '<tr><td>';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print $langs->trans("KeywordFilter").' &nbsp; ';
			print '</td>';
			print '<td><input type="text" name="key" value="'.$key.'">';
			print '<input type="hidden" name="action" value="search">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '</td>';
			print '<td rowspan="'.$rowspan.'"  valign="bottom">';
			print '<input type="submit" class="button" value="'.$langs->trans("Search").'">';
			print '</td></tr>';
			if ($conf->categorie->enabled)
			{
				print '<tr><td>'.$langs->trans("CategoryFilter").' &nbsp; </td>';
				print '<td>'.$form->select_all_categories(0,$parent).'</td></tr>';
			}
	
			print '</table>';
			print '</td></tr></table>';
			print '</form>';
	
			if ($action == 'search')
			{
				print '<br>';
				print '<form action="'.DOL_URL_ROOT.'/product/composition/fiche.php?id='.$id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="add_prod">';
				print '<input type="hidden" name="id" value="'.$id.'">';
				print '<table class="nobordernopadding" width="100%">';
				print '<tr class="liste_titre">';
				print '<th class="liste_titre">'.$langs->trans("Ref").'</td>';
				print '<th class="liste_titre">'.$langs->trans("Label").'</td>';
				print '<th class="liste_titre" align="center">'.$langs->trans("AddDel").'</td>';
				print '<th class="liste_titre" align="right">'.$langs->trans("Quantity").'</td>';
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
							if($object->is_sousproduit($id, $objp->rowid))
							{
								$addchecked = ' checked="checked"';
								$qty=$object->is_sousproduit_qty;
							}
							else
							{
								$addchecked = '';
								$qty="1";
							}
							print '<td align="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
							print '<input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';
							print '<td align="right"><input type="text" size="3" name="prod_qty_'.$i.'" value="'.$qty.'"></td>';
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
		
		if ($action == 'build')
		{
			// Display the list of store with buildable product 
			print '<br>';
			print_fiche_titre($langs->trans("Building"),'','');
			print '<b>'.$langs->trans("BuildindListInfo").'</b><br>';
			print '<form action="'.DOL_URL_ROOT.'/product/composition/fiche.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="action" value="buildit">';
			print '<table class="border" width="25%">';
			
			// loop on the store
			$sql = "SELECT rowid, lieu, cp";
			$sql.= " FROM ".MAIN_DB_PREFIX."entrepot";
			$sql.= " WHERE statut = 1";
			$sql.= " ORDER BY cp ASC";
			
			dol_syslog("product/composition.fiche.php::Build composed product sql=".$sql);

			$resql=$db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;
				if ($num)
				{
					while ($i < $num)
					{
						$obj = $db->fetch_object($resql);

						// get the number of product buidable on the store
						$fabricable=$object->getNbProductBuildable($obj->rowid, $id);

						print "<tr><td>".$obj->lieu." (".$obj->cp.")</td>";
						print '<td align=right ><input style="text-align:right;" type="text" name="nbToBuild'.$obj->rowid.'" size=5 value="'.$fabricable.'">';
						print '</td></tr>';
						$i++;
					}
				}
			}
			print '<tr>';
			print '<td colspan=3 align=right><input type="submit" class="button" value="'.$langs->trans("BuildIt").'"></td>';
			print '</tr>';

			print '</table>';
			print '</form>';
		}
	}
}

/* Barre d'action				*/
print '<div class="tabsAction">';

if ($action == '' && $bproduit)
{
	if ($user->rights->produit->creer || $user->rights->service->creer)
	{
		if ($object->finished !=0)
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/composition/fiche.php?action=edit&amp;id='.$productid.'">'.$langs->trans("EditAssociate").'</a>';
		
		// we build only if we manage store and the product is manufactured
		if ($conf->stock->enabled && $object->finished ==1)
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/composition/fiche.php?action=build&amp;id='.$productid.'">'.$langs->trans("Build").'</a>';
	}
}

print '</div>';
llxFooter();
$db->close();

?>