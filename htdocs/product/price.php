<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
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
 *	\file       htdocs/product/price.php
 *	\ingroup    product
 *	\brief      Page to show product prices
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("products");
$langs->load("bills");

$id		= GETPOST('id', 'int');
$ref	= GETPOST('ref', 'alpha');
$action	= GETPOST('action', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$object = new Product($db);

/*
 * Actions
 */

if ($action == 'update_price' && ! $_POST["cancel"] && ($user->rights->produit->creer || $user->rights->service->creer))
{
	$result = $object->fetch($id);
	
	// MultiPrix
	if (! empty($conf->global->PRODUIT_MULTIPRICES))
	{
		$newprice='';
		$newprice_min='';
		$newpricebase='';
		$newvat='';

		for($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
		{
			if (isset($_POST["price_".$i]))
			{
				$level=$i;
				$newprice=price2num($_POST["price_".$i],'MU');
				$newprice_min=price2num($_POST["price_min_".$i],'MU');
				$newpricebase=$_POST["multiprices_base_type_".$i];
				$newnpr=(preg_match('/\*/',$_POST["tva_tx_".$i]) ? 1 : 0);
				$newvat=str_replace('*','',$_POST["tva_tx_".$i]);
				$newpsq=GETPOST('psqflag');
				$newpsq = empty($newpsq) ? 0 : $newpsq;
				break;	// We found submited price
			}
		}
	}
	else
	{
		$level=0;
		$newprice=price2num($_POST["price"],'MU');
		$newprice_min=price2num($_POST["price_min"],'MU');
		$newpricebase=$_POST["price_base_type"];
		$newnpr=(preg_match('/\*/',$_POST["tva_tx"]) ? 1 : 0);
		$newvat=str_replace('*','',$_POST["tva_tx"]);
		$newpsq=GETPOST('psqflag');
		$newpsq = empty($newpsq) ? 0 : $newpsq;
	}

	if ($object->updatePrice($object->id, $newprice, $newpricebase, $user, $newvat, $newprice_min, $level, $newnpr, $newpsq) > 0)
	{
		$action = '';
		$mesg = '<div class="ok">'.$langs->trans("RecordSaved").'</div>';
	}
	else
	{
		$action = 'edit_price';
		$mesg = '<div class="error">'.$object->error.'</div>';
	}
}
else if ($action == 'delete' && $user->rights->produit->supprimer)
{
	$result=$object->log_price_delete($user,$_GET["lineid"]);
	if ($result < 0) $mesg='<div class="error">'.$object->error.'</div>';
}

/*****************************************************
 * Price by quantity
 *****************************************************/
$error=0;
if ($action == 'activate_price_by_qty') { // Activating product price by quantity add a new price, specified as by quantity
	$result = $object->fetch($id);
	$level=GETPOST('level');
	
	$object->updatePrice($object->id, 0, $object->price_base_type, $user, $object->tva_tx, 0, $level, $object->tva_npr, 1);
}

if ($action == 'edit_price_by_qty') { // Edition d'un prix par quantité
	$rowid = GETPOST('rowid');
}

if ($action == 'update_price_by_qty') { // Ajout / Mise à jour d'un prix par quantité
	$result = $object->fetch($id);

	// Récupération des variables
	$rowid = GETPOST('rowid');
	$priceid=GETPOST('priceid');
	$newprice=price2num(GETPOST("price"),'MU');
	//$newminprice=price2num(GETPOST("price_min"),'MU'); // TODO : Add min price management
	$quantity=GETPOST('quantity');
	$remise_percent=price2num(GETPOST('remise_percent'));
	$remise=0; // TODO : allow dicsount by amount when available on documents
	
	if (empty($quantity))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Qty")).'</div>';
	}
	if (empty($newprice))
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Price")).'</div>';
	}
	if(!$error) {
		// Calcul du prix HT et du prix unitaire
		if ($object->price_base_type == 'TTC')
		{
			$price = price2num($newprice) / (1 + ($object->tva_tx / 100));
		}
		
		$price = price2num($newprice,'MU');
		$unitPrice = price2num($price/$quantity,'MU');
	
		// Ajout / mise à jour
		if($rowid > 0) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."product_price_by_qty SET";
			$sql.= " price='".$price."',";
			$sql.= " unitprice=".$unitPrice.",";
			$sql.= " quantity=".$quantity.",";
			$sql.= " remise_percent=".$remise_percent.",";
			$sql.= " remise=".$remise;
			$sql.= " WHERE rowid = ".GETPOST('rowid');
			
			$result = $db->query($sql);
		} else {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_price_by_qty (fk_product_price,price,unitprice,quantity,remise_percent,remise) values (";
			$sql.= $priceid.','.$price.','.$unitPrice.','.$quantity.','.$remise_percent.','.$remise.')';
	
			$result = $db->query($sql);
		}
	}
}

if ($action == 'delete_price_by_qty') {
	$rowid = GETPOST('rowid');
	
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price_by_qty";
	$sql.= " WHERE rowid = ".GETPOST('rowid');
	
	$result = $db->query($sql);
}

if ($action == 'delete_all_price_by_qty') {
	$priceid=GETPOST('priceid');
	
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_price_by_qty";
	$sql.= " WHERE fk_product_price = ".$priceid;
	
	$result = $db->query($sql);
}



/*
 * View
 */

$form = new Form($db);

if (! empty($id) || ! empty($ref)) $result = $object->fetch($id, $ref);

llxHeader("","",$langs->trans("CardProduct".$object->type));

$head=product_prepare_head($object, $user);
$titre=$langs->trans("CardProduct".$object->type);
$picto=($object->type==1?'service':'product');
dol_fiche_head($head, 'price', $titre, 0, $picto);


print '<table class="border" width="100%">';

// Ref
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
print $form->showrefnav($object,'ref','',1,'ref');
print '</td>';
print '</tr>';

// Label
print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->libelle.'</td>';

$isphoto=$object->is_photo_available($conf->product->multidir_output[$object->entity]);

$nblignes=5;
if ($isphoto)
{
	// Photo
	print '<td valign="middle" align="center" width="30%" rowspan="'.$nblignes.'">';
	print $object->show_photos($conf->product->multidir_output[$object->entity],1,1,0,0,0,80);
	print '</td>';
}

print '</tr>';

// MultiPrix
if (! empty($conf->global->PRODUIT_MULTIPRICES))
{
	if (! empty($socid))
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

			// Price by quantity
			if($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) {
				print '<tr><td>'.$langs->trans("PriceByQuantity").' '.$i;
				print '</td><td>';
				
				if($object->prices_by_qty[$i] == 1) {
					print '<table width="50%" class="noborder">';
		
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans("PriceByQuantityRange").' '.$i.'</td>';
					print '<td align="right">'.$langs->trans("HT").'</td>';
					print '<td align="right">'.$langs->trans("UnitPrice").'</td>';
					print '<td align="right">'.$langs->trans("Discount").'</td>';
					print '<td>&nbsp;</td>';
					print '</tr>';
					foreach ($object->prices_by_qty_list[$i] as $ii=> $prices) {
						if($action == 'edit_price_by_qty' && $rowid == $prices['rowid'] && ($user->rights->produit->creer || $user->rights->service->creer)) {
							print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
							print '<input type="hidden" name="action" value="update_price_by_qty">';
							print '<input type="hidden" name="priceid" value="'.$object->prices_by_qty_id[$i].'">';
							print '<input type="hidden" value="'.$prices['rowid'].'" name="rowid">';
							print '<tr class="'.($ii % 2 == 0 ? 'pair':'impair').'">';
							print '<td><input size="5" type="text" value="'.$prices['quantity'].'" name="quantity"></td>';
							print '<td align="right" colspan="2"><input size="10" type="text" value="'.$prices['price'].'" name="price">&nbsp;'.$object->price_base_type.'</td>';
							//print '<td align="right">&nbsp;</td>';
							print '<td align="right"><input size="5" type="text" value="'.$prices['remise_percent'].'" name="remise_percent">&nbsp;%</td>';
							print '<td align="center"><input type="submit" value="'.$langs->trans("Modify").'" class="button"></td>';
							print '</tr>';
							print '</form>';
						} else {
							print '<tr class="'.($ii % 2 == 0 ? 'pair':'impair').'">';
							print '<td>'.$prices['quantity'].'</td>';
							print '<td align="right">'.price($prices['price']).'</td>';
							print '<td align="right">'.price($prices['unitprice']).'</td>';
							print '<td align="right">'.price($prices['remise_percent']).' %</td>';
							print '<td align="center">';
							if(($user->rights->produit->creer || $user->rights->service->creer)) {
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit_price_by_qty&amp;rowid='.$prices["rowid"].'">';
								print img_edit().'</a>';
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete_price_by_qty&amp;rowid='.$prices["rowid"].'">';
								print img_delete().'</a>';
							} else {
								print '&nbsp;';
							}
							print '</td>';
							print '</tr>';
						}
					}
					if($action != 'edit_price_by_qty' && ($user->rights->produit->creer || $user->rights->service->creer)) {
						print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
						print '<input type="hidden" name="action" value="update_price_by_qty">';
						print '<input type="hidden" name="priceid" value="'.$object->prices_by_qty_id[$i].'">';
						print '<input type="hidden" value="0" name="rowid">';
						print '<tr class="'.($ii % 2 == 0 ? 'pair':'impair').'">';
						print '<td><input size="5" type="text" value="1" name="quantity"></td>';
						print '<td align="right" colspan="2"><input size="10" type="text" value="0" name="price">&nbsp;'.$object->price_base_type.'</td>';
						//print '<td align="right">&nbsp;</td>';
						print '<td align="right"><input size="5" type="text" value="0" name="remise_percent">&nbsp;%</td>';
						print '<td align="center"><input type="submit" value="'.$langs->trans("Add").'" class="button"></td>';
						print '</tr>';
						print '</form>';
					}
		
					print '</table>';
				} else {
					print $langs->trans("No");
					print '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=activate_price_by_qty&level='.$i.'">('.$langs->trans("Activate").')</a>';
				}
				print '</td></tr>';
			}
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
	}
	else
	{
		print price($object->price).' '.$langs->trans($object->price_base_type);
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
	
	// Price by quantity
	if($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY) {
		print '<tr><td>'.$langs->trans("PriceByQuantity");
		if($object->prices_by_qty[0] == 0) {
			print '&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=activate_price_by_qty&level=1">'.$langs->trans("Activate");
		}
		print '</td><td>';
		
		if($object->prices_by_qty[0] == 1) {
			print '<table width="50%" class="noborder">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("PriceByQuantityRange").'</td>';
			print '<td align="right">'.$langs->trans("HT").'</td>';
			print '<td align="right">'.$langs->trans("UnitPrice").'</td>';
			print '<td align="right">'.$langs->trans("Discount").'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			foreach ($object->prices_by_qty_list[0] as $ii=> $prices) {
				if($action == 'edit_price_by_qty' && $rowid == $prices['rowid'] && ($user->rights->produit->creer || $user->rights->service->creer)) {
					print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
					print '<input type="hidden" name="action" value="update_price_by_qty">';
					print '<input type="hidden" name="priceid" value="'.$object->prices_by_qty_id[0].'">';
					print '<input type="hidden" value="'.$prices['rowid'].'" name="rowid">';
					print '<tr class="'.($ii % 2 == 0 ? 'pair':'impair').'">';
					print '<td><input size="5" type="text" value="'.$prices['quantity'].'" name="quantity"></td>';
					print '<td align="right" colspan="2"><input size="10" type="text" value="'.$prices['price'].'" name="price">&nbsp;'.$object->price_base_type.'</td>';
					//print '<td align="right">&nbsp;</td>';
					print '<td align="right"><input size="5" type="text" value="'.$prices['remise_percent'].'" name="remise_percent">&nbsp;%</td>';
					print '<td align="center"><input type="submit" value="'.$langs->trans("Modify").'" class="button"></td>';
					print '</tr>';
					print '</form>';
				} else {
					print '<tr class="'.($ii % 2 == 0 ? 'pair':'impair').'">';
					print '<td>'.$prices['quantity'].'</td>';
					print '<td align="right">'.price($prices['price']).'</td>';
					print '<td align="right">'.price($prices['unitprice']).'</td>';
					print '<td align="right">'.price($prices['remise_percent']).' %</td>';
					print '<td align="center">';
					if(($user->rights->produit->creer || $user->rights->service->creer)) {
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit_price_by_qty&amp;rowid='.$prices["rowid"].'">';
						print img_edit().'</a>';
						print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete_price_by_qty&amp;rowid='.$prices["rowid"].'">';
						print img_delete().'</a>';
					} else {
						print '&nbsp;';
					}
					print '</td>';
					print '</tr>';
				}
			}
			if($action != 'edit_price_by_qty') {
				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
				print '<input type="hidden" name="action" value="update_price_by_qty">';
				print '<input type="hidden" name="priceid" value="'.$object->prices_by_qty_id[0].'">';
				print '<input type="hidden" value="0" name="rowid">';
				print '<tr class="'.($ii % 2 == 0 ? 'pair':'impair').'">';
				print '<td><input size="5" type="text" value="1" name="quantity"></td>';
				print '<td align="right" colspan="2"><input size="10" type="text" value="0" name="price">&nbsp;'.$object->price_base_type.'</td>';
				//print '<td align="right">&nbsp;</td>';
				print '<td align="right"><input size="5" type="text" value="0" name="remise_percent">&nbsp;%</td>';
				print '<td align="center"><input type="submit" value="'.$langs->trans("Add").'" class="button"></td>';
				print '</tr>';
				print '</form>';
			}

			print '</table>';
		} else {
			print $langs->trans("No");
		}
		print '</td></tr>';
	}
}

// Status (to sell)
print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
print $object->getLibStatut(2,0);
print '</td></tr>';

print "</table>\n";

print "</div>\n";

if (! empty($mesg)) {
	dol_htmloutput_mesg($mesg);
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

if (! $action || $action == 'delete')
{
	print "\n".'<div class="tabsAction">'."\n";

	if ($user->rights->produit->creer || $user->rights->service->creer)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit_price&amp;id='.$object->id.'">'.$langs->trans("UpdatePrice").'</a>';
	}

	print "\n</div>\n";
}



/*
 * Edition du prix
 */
if ($action == 'edit_price' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	print_fiche_titre($langs->trans("NewPrice"),'','');

	if (empty($conf->global->PRODUIT_MULTIPRICES))
	{
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update_price">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<table class="border" width="100%">';

        // VAT
        print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
        print $form->load_tva("tva_tx",$object->tva_tx,$mysoc,'',$object->id,$object->tva_npr);
        print '</td></tr>';

		// Price base
		print '<tr><td width="15%">';
		print $langs->trans('PriceBase');
		print '</td>';
		print '<td>';
		print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
		print '</td>';
		print '</tr>';

		// Price
		print '<tr><td width="20%">';
		$text=$langs->trans('SellingPrice');
		print $form->textwithpicto($text,$langs->trans("PrecisionUnitIsLimitedToXDecimals",$conf->global->MAIN_MAX_DECIMALS_UNIT),1,1);
		print '</td><td>';
		if ($object->price_base_type == 'TTC')
		{
			print '<input name="price" size="10" value="'.price($object->price_ttc).'">';
		}
		else
		{
			print '<input name="price" size="10" value="'.price($object->price).'">';
		}
		print '</td></tr>';

		// Price minimum
		print '<tr><td>' ;
		$text=$langs->trans('MinPrice');
		print $form->textwithpicto($text,$langs->trans("PrecisionUnitIsLimitedToXDecimals",$conf->global->MAIN_MAX_DECIMALS_UNIT),1,1);
		if ($object->price_base_type == 'TTC')
		{
			print '<td><input name="price_min" size="10" value="'.price($object->price_min_ttc).'">';
		}
		else
		{
			print '<td><input name="price_min" size="10" value="'.price($object->price_min).'">';
		}
		print '</td></tr>';

		print '</table>';

		print '<center><br><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
		print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

		print '<br></form>';
	}
	else
	{
		for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
		{
			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update_price">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<table class="border" width="100%">';

            // VAT
            if ($i == 1)
            {
                print '<tr><td>'.$langs->trans("VATRate").'</td><td>';
                print $form->load_tva("tva_tx_".$i,$object->multiprices_tva_tx["$i"],$mysoc,'',$object->id);
                print '</td></tr>';
            }
            else
            {    // We always use the vat rate of price level 1 (A vat rate does not depends on customer)
                print '<input type="hidden" name="tva_tx_'.$i.'" value="'.$object->multiprices_tva_tx[1].'">';
            }

			// Selling price
			print '<tr><td width="20%">';
			$text=$langs->trans('SellingPrice').' '.$i;
			print $form->textwithpicto($text,$langs->trans("PrecisionUnitIsLimitedToXDecimals",$conf->global->MAIN_MAX_DECIMALS_UNIT),1,1);
			print '</td><td>';
			if ($object->multiprices_base_type["$i"] == 'TTC')
			{
				print '<input name="price_'.$i.'" size="10" value="'.price($object->multiprices_ttc["$i"]).'">';
			}
			else
			{
				print '<input name="price_'.$i.'" size="10" value="'.price($object->multiprices["$i"]).'">';
			}
			print $form->select_PriceBaseType($object->multiprices_base_type["$i"], "multiprices_base_type_".$i);
			print '</td></tr>';

            // Min price
			print '<tr><td>';
			$text=$langs->trans('MinPrice').' '.$i;
			print $form->textwithpicto($text,$langs->trans("PrecisionUnitIsLimitedToXDecimals",$conf->global->MAIN_MAX_DECIMALS_UNIT),1,1);
			if ($object->multiprices_base_type["$i"] == 'TTC')
			{
				print '<td><input name="price_min_'.$i.'" size="10" value="'.price($object->multiprices_min_ttc["$i"]).'">';
			}
			else
			{
				print '<td><input name="price_min_'.$i.'" size="10" value="'.price($object->multiprices_min["$i"]).'">';
			}
			print '</td></tr>';

			print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
			print '</table>';
			print '</form>';
		}

	}
}


// Liste des evolutions du prix
$sql = "SELECT p.rowid, p.price, p.price_ttc, p.price_base_type, p.tva_tx, p.recuperableonly,";
$sql.= " p.price_level, p.price_min, p.price_min_ttc,p.price_by_qty,";
$sql.= " p.date_price as dp, u.rowid as user_id, u.login";
$sql.= " FROM ".MAIN_DB_PREFIX."product_price as p,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE fk_product = ".$object->id;
$sql.= " AND p.entity IN (".getEntity('productprice', 1).")";
$sql.= " AND p.fk_user_author = u.rowid";
if (! empty($socid) && ! empty($conf->global->PRODUIT_MULTIPRICES)) $sql.= " AND p.price_level = ".$soc->price_level;
$sql.= " ORDER BY p.date_price DESC, p.price_level ASC";
//$sql .= $db->plimit();

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);

	if (! $num)
	{
		$db->free($result);

		// Il doit au moins y avoir la ligne de prix initial.
		// On l'ajoute donc pour remettre a niveau (pb vieilles versions)
		$object->updatePrice($object->id, $object->price, $object->price_base_type, $user, $newprice_min);

		$result = $db->query($sql);
		$num = $db->num_rows($result);
	}

	if ($num > 0)
	{
		print '<br>';

		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("AppliedPricesFrom").'</td>';

		if (! empty($conf->global->PRODUIT_MULTIPRICES))
		{
			print '<td align="center">'.$langs->trans("MultiPriceLevelsName").'</td>';
		}
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
		{
			print '<td align="center">'.$langs->trans("Type").'</td>';
		}

		print '<td align="center">'.$langs->trans("PriceBase").'</td>';
		print '<td align="right">'.$langs->trans("VAT").'</td>';
		print '<td align="right">'.$langs->trans("HT").'</td>';
		print '<td align="right">'.$langs->trans("TTC").'</td>';
		print '<td align="right">'.$langs->trans("MinPrice").' '.$langs->trans("HT").'</td>';
		print '<td align="right">'.$langs->trans("MinPrice").' '.$langs->trans("TTC").'</td>';
		print '<td align="right">'.$langs->trans("ChangedBy").'</td>';
		if ($user->rights->produit->supprimer) print '<td align="right">&nbsp;</td>';
		print '</tr>';

		$var=True;
		$i = 0;
		while ($i < $num)
		{
			$objp = $db->fetch_object($result);
			$var=!$var;
			print "<tr $bc[$var]>";
			// Date
			print "<td>".dol_print_date($db->jdate($objp->dp),"dayhour")."</td>";

			// Price level
			if (! empty($conf->global->PRODUIT_MULTIPRICES))
			{
				print '<td align="center">'.$objp->price_level."</td>";
			}
			// Price by quantity
			if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))
			{
				$type = ($objp->price_by_qty == 1) ? 'PriceByQuantity' : 'Standard';
				print '<td align="center">'.$langs->trans($type)."</td>";
			}

			print '<td align="center">'.$langs->trans($objp->price_base_type)."</td>";
			print '<td align="right">'.vatrate($objp->tva_tx,true,$objp->recuperableonly)."</td>";
			print '<td align="right">'.price($objp->price)."</td>";
			print '<td align="right">'.price($objp->price_ttc)."</td>";
			print '<td align="right">'.price($objp->price_min).'</td>';
			print '<td align="right">'.price($objp->price_min_ttc).'</td>';

			// User
			print '<td align="right"><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$objp->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$objp->login.'</a></td>';

			// Action
			if ($user->rights->produit->supprimer)
			{
				print '<td align="right">';
				if ($i > 0)
				{
					print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$object->id.'&amp;lineid='.$objp->rowid.'">';
					print img_delete();
					print '</a>';
				}
				else print '&nbsp;';	// Can not delete last price (it's current price)
				print '</td>';
			}

			print "</tr>\n";
			$i++;
		}
		$db->free($result);
		print "</table>";
		print "<br>";
	}
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
?>
