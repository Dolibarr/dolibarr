<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2007 Régis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Auguria SARL         <info@auguria.org>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/product/fiche.php
 *  \ingroup    product
 *  \brief      Page de la fiche produit
 *  \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");

$langs->load("bills");
$langs->load("other");
$langs->load("stocks");

$mesg = '';

if (!$user->rights->produit->lire) accessforbidden();

/*
 *
 */
if ($_GET["action"] == 'fastappro')
{
	$product = new Product($db);
	$product->fetch($_GET["id"]);
	$result = $product->fastappro($user);
	Header("Location: fiche.php?id=".$_GET["id"]);
	exit;
}


// Action ajout d'un produit ou service
if ($_POST["action"] == 'add' && $user->rights->produit->creer)
{
	$error=0;

	if (empty($_POST["libelle"]))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Label')).'</div>';
		$_GET["action"] = "create";
		$_GET["canvas"] = $product->canvas;
		$_GET["type"] = $_POST["type"];
		$error++;
	}

	if (! $error)
	{
		if ($_POST["canvas"] <> '' && file_exists('canvas/product.'.$_POST["canvas"].'.class.php') )
		{
			$class = 'Product'.ucfirst($_POST["canvas"]);
			include_once('canvas/product.'.$_POST["canvas"].'.class.php');
			$product = new $class($db);
		}
		else
		{
			$product = new Product($db);
		}

		$product->ref                = $_POST["ref"];
		$product->libelle            = $_POST["libelle"];
		$product->price_base_type    = $_POST["price_base_type"];
		if ($product->price_base_type == 'TTC') $product->price_ttc = $_POST["price"];
		else $product->price = $_POST["price"];
		if ($product->price_base_type == 'TTC') $product->price_min_ttc = $_POST["price_min"];
		else $product->price_min = $_POST["price_min"];
		$product->tva_tx             = $_POST["tva_tx"];
		$product->type               = $_POST["type"];
		$product->status             = $_POST["statut"];
		$product->description        = $_POST["desc"];
		$product->note               = $_POST["note"];
		$product->duration_value     = $_POST["duration_value"];
		$product->duration_unit      = $_POST["duration_unit"];
		$product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
		$product->canvas             = $_POST["canvas"];
		$product->weight             = $_POST["weight"];
		$product->weight_units       = $_POST["weight_units"];
		$product->volume             = $_POST["volume"];
		$product->volume_units       = $_POST["volume_units"];
		$product->finished           = $_POST["finished"];
		// MultiPrix
		if($conf->global->PRODUIT_MULTIPRICES)
		{
			for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
			{
				if($_POST["price_".$i])
				{
					$price = price2num($_POST["price_".$i]);
					$product->multiprices["$i"] = $price;
					$product->multiprices_base_type["$i"] = $_POST["multiprices_base_type_".$i];
				}
				else
				{
					$product->multiprices["$i"] = "";
				}
			}
		}

		if ( $value != $current_lang ) $e_product = $product;

		// Produit spécifique
		// $_POST n'est pas utilise dans la classe Product
		// mais dans des classes qui hérite de Product
		$id = $product->create($user, $_POST);

		if ($id > 0)
		{
			Header("Location: fiche.php?id=".$id);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$langs->trans($product->error).'</div>';
			$_GET["action"] = "create";
			$_GET["canvas"] = $product->canvas;
			$_GET["type"] = $_POST["type"];
		}
	}
}

// Action mise a jour d'un produit ou service
if ($_POST["action"] == 'update' &&
$_POST["cancel"] <> $langs->trans("Cancel") &&
$user->rights->produit->creer)
{
	$product = new Product($db);
	if ($product->fetch($_POST["id"]))
	{
		$product->ref                = $_POST["ref"];
		$product->libelle            = $_POST["libelle"];
		$product->description        = $_POST["desc"];
		$product->note               = $_POST["note"];
		$product->status             = $_POST["statut"];
		$product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
		$product->stock_loc          = $_POST["stock_loc"];
		$product->duration_value     = $_POST["duration_value"];
		$product->duration_unit      = $_POST["duration_unit"];
		$product->canvas             = $_POST["canvas"];
		$product->weight             = $_POST["weight"];
		$product->weight_units       = $_POST["weight_units"];
		$product->volume             = $_POST["volume"];
		$product->volume_units       = $_POST["volume_units"];
		$product->finished           = $_POST["finished"];

		if ($product->check())
		{
			if ($product->update($product->id, $user) > 0)
			{
				$_GET["action"] = '';
				$_GET["id"] = $_POST["id"];
			}
			else
			{
				$_GET["action"] = 'edit';
				$_GET["id"] = $_POST["id"];
				$mesg = $product->error;
			}
		}
		else
		{
			$_GET["action"] = 'edit';
			$_GET["id"] = $_POST["id"];
			$mesg = $langs->trans("ErrorProductBadRefOrLabel");
		}

		// Produit spécifique
		if ($product->canvas <> '' && file_exists('canvas/product.'.$product->canvas.'.class.php') )
		{
			$class = 'Product'.ucfirst($product->canvas);
			include_once('canvas/product.'.$product->canvas.'.class.php');

			$product = new $class($db);
			if ($product->FetchCanvas($_POST["id"]))
			{
				$product->UpdateCanvas($_POST);
			}
		}
	}
}

// clone d'un produit
if ($_GET["action"] == 'clone' && $user->rights->produit->creer)
{
	$db->begin();

	$product = new Product($db);
	$originalId = $_GET["id"];
	if ($product->fetch($_GET["id"]) > 0)
	{
		$product->ref = "Clone ".$product->ref;
		$product->status = 0;
		$product->finished = 1;
		$product->id = null;

		if ($product->check())
		{
			$id = $product->create($user);
			if ($id > 0)
			{
				// $product->clone_fournisseurs($originalId, $id);

				$db->commit();
				$db->close();

				Header("Location: fiche.php?id=$id");
				exit;
			}
			else
			{
				if ($product->error == 'ErrorProductAlreadyExists')
				{
					$db->rollback();

					$_error = 1;
					$_GET["action"] = "";
						
					$mesg='<div class="error">'.$langs->trans("ErrorProductAlreadyExists",$product->ref).'</div>';
					//dolibarr_print_error($product->db);
				}
				else
				{
					$db->rollback();
					dolibarr_print_error($product->db);
				}
			}
		}
	}
	else
	{
		$db->rollback();
		dolibarr_print_error($product->db);
	}
}

/*
 * Suppression d'un produit/service pas encore affect
 */
if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes' && $user->rights->produit->supprimer)
{
	$product = new Product($db);
	$product->fetch($_GET['id']);
	$result = $product->delete($_GET['id']);

	if ($result == 0)
	{
		Header('Location: '.DOL_URL_ROOT.'/product/liste.php?delprod='.$product->ref);
		exit;
	}
	else
	{
		$reload = 0;
		$_GET['action']='';
	}
}


/*
 * Ajout du produit dans une propal
 */
if ($_POST["action"] == 'addinpropal')
{
	$propal = New Propal($db);
	$result=$propal->fetch($_POST["propalid"]);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$propal->error);
		exit;
	}

	$soc = new Societe($db);
	$result=$soc->fetch($propal->socid,$user);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$soc->error);
		exit;
	}

	$prod = new Product($db);
	$result=$prod->fetch($_GET['id']);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$prod->error);
		exit;
	}

	$desc = $prod->description;
	$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

	$price_base_type = 'HT';

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		$pu_ht = $prod->multiprices[$soc->price_level];
		$pu_ttc = $prod->multiprices_ttc[$soc->price_level];
		$price_base_type = $prod->multiprices_base_type[$soc->price_level];
	}
	else
	{
		$pu_ht = $prod->price;
		$pu_ttc = $prod->price_ttc;
		$price_base_type = $prod->price_base_type;
	}

	// On reevalue prix selon taux tva car taux tva transaction peut etre different
	// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
	if ($tva_tx != $prod->tva_tx)
	{
		if ($price_base_type != 'HT')
		{
			$pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
		}
		else
		{
			$pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
		}
	}

	$result = $propal->addline($propal->id,
	$desc,
	$pu_ht,
	$_POST["qty"],
	$tva_tx,
	$prod->id,
	$_POST["remise_percent"],
	$price_base_type,
	$pu_ttc
	);
	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT."/comm/propal.php?propalid=".$propal->id);
		return;
	}

	$mesg = $langs->trans("ErrorUnknown").": $result";
}

/*
 * Ajout du produit dans une commande
 */
if ($_POST["action"] == 'addincommande')
{
	$commande = new Commande($db);
	$result=$commande->fetch($_POST["commandeid"]);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$commande->error);
		exit;
	}

	$soc = new Societe($db);
	$result=$soc->fetch($commande->socid,$user);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$soc->error);
		exit;
	}

	$prod = new Product($db);
	$result=$prod->fetch($_GET['id']);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$prod->error);
		exit;
	}

	$desc = $prod->description;
	$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		$pu_ht = $prod->multiprices[$soc->price_level];
		$pu_ttc = $prod->multiprices_ttc[$soc->price_level];
		$price_base_type = $prod->multiprices_base_type[$soc->price_level];
	}
	else
	{
		$pu_ht = $prod->price;
		$pu_ttc = $prod->price_ttc;
		$price_base_type = $prod->price_base_type;
	}

	// On reevalue prix selon taux tva car taux tva transaction peut etre different
	// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
	if ($tva_tx != $prod->tva_tx)
	{
		if ($price_base_type != 'HT')
		{
			$pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
		}
		else
		{
			$pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
		}
	}

	$result =  $commande->addline($commande->id,
	$desc,
	$pu_ht,
	$_POST["qty"],
	$tva_tx,
	$prod->id,
	$_POST["remise_percent"],
				'',
				'', //Todo: voir si fk_remise_except est encore valable car n'apparait plus dans les propales
	$price_base_type,
	$pu_ttc
	);

	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT."/commande/fiche.php?id=".$commande->id);
		exit;
	}
}

/*
 * Ajout du produit dans une facture
 */
if ($_POST["action"] == 'addinfacture' && $user->rights->facture->creer)
{
	$facture = New Facture($db);
	$result=$facture->fetch($_POST["factureid"]);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$facture->error);
		exit;
	}

	$soc = new Societe($db);
	$soc->fetch($facture->socid,$user);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$soc->error);
		exit;
	}

	$prod = new Product($db);
	$result = $prod->fetch($_GET["id"]);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$prod->error);
		exit;
	}

	$desc = $prod->description;
	$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES)
	{
		$pu_ht = $prod->multiprices[$soc->price_level];
		$pu_ttc = $prod->multiprices_ttc[$soc->price_level];
		$price_base_type = $prod->multiprices_base_type[$soc->price_level];
	}
	else
	{
		$pu_ht = $prod->price;
		$pu_ttc = $prod->price_ttc;
		$price_base_type = $prod->price_base_type;
	}

	// On reevalue prix selon taux tva car taux tva transaction peut etre different
	// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
	if ($tva_tx != $prod->tva_tx)
	{
		if ($price_base_type != 'HT')
		{
			$pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
		}
		else
		{
			$pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
		}
	}

	$result = $facture->addline($facture->id,
	$desc,
	$pu_ht,
	$_POST["qty"],
	$tva_tx,
	$prod->id,
	$_POST["remise_percent"],
		    '',
		    '',
		    '',
		    '',
		    '',
	$price_base_type,
	$pu_ttc
	);

	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT."/compta/facture.php?facid=".$facture->id);
		exit;
	}
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
	$action = '';
	Header("Location: fiche.php?id=".$_POST["id"]);
	exit;
}


/*
 * View
 */

$html = new Form($db);
$formproduct = new FormProduct($db);


/*
 * Fiche création du produit
 */
if ($_GET["action"] == 'create' && $user->rights->produit->creer)
{
	if ($conf->global->PRODUCT_CANVAS_ABILITY)
	{
		if (! isset($product))
		{
			$filecanvas=DOL_DOCUMENT_ROOT.'/product/canvas/product.'.$_GET["canvas"].'.class.php';
			if ($_GET["canvas"] && file_exists($filecanvas) )
			{
				$class = 'Product'.ucfirst($_GET["canvas"]);
				include_once($filecanvas);

				$product = new $class($db,0,$user);
			}
			else
			{
				$product = new Product($db);
			}
		}

		$product->assign_smarty_values($smarty, 'create');

		if ($_error == 1)
		{
			$product = $e_product;
		}
	}

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ($mesg) print $mesg."\n";

	if (! $conf->global->PRODUCT_CANVAS_ABILITY || !$_GET["canvas"])
	{
		print '<form action="fiche.php" method="post">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";

		if ($_GET["type"]==1) $title=$langs->trans("NewService");
		else $title=$langs->trans("NewProduct");
		print_fiche_titre($title);

		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td width="20%">'.$langs->trans("Ref").'</td><td><input name="ref" size="40" maxlength="32" value="'.$product->ref.'">';
		if ($_error == 1)
		{
			print $langs->trans("RefAlreadyExists");
		}
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td><td>';
		$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
		$html->select_array('statut',$statutarray,$_POST["statut"]);
		print '</td></tr>';

		// Stock min level
		if ($_GET["type"] != 1 && $conf->stock->enabled)
		{
			print '<tr><td>'.$langs->trans("StockLimit").'</td><td>';
			print '<input name="seuil_stock_alerte" size="4" value="0">';
			print '</td></tr>';
		}
		else
		{
			print '<input name="seuil_stock_alerte" type="hidden" value="0">';
		}

		// Description (utilisé dans facture, propale...)
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';

		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('desc','',160,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="desc" rows="4" cols="90">';
			print '</textarea>';
		}

		print "</td></tr>";

		// Nature
		if ($_GET["type"] != 1)
		{
			print '<tr><td>'.$langs->trans("Nature").'</td><td>';
			$statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
			$html->select_array('finished',$statutarray,$_POST["finished"]);
			print '</td></tr>';
		}
		
		//Duration
		if ($_GET["type"] == 1)
		{
			print '<tr><td>'.$langs->trans("Duration").'</td><td><input name="duration_value" size="6" maxlength="5" value="'.$product->duree.'"> &nbsp;';
			print '<input name="duration_unit" type="radio" value="h">'.$langs->trans("Hour").'&nbsp;';
			print '<input name="duration_unit" type="radio" value="d">'.$langs->trans("Day").'&nbsp;';
			print '<input name="duration_unit" type="radio" value="w">'.$langs->trans("Week").'&nbsp;';
			print '<input name="duration_unit" type="radio" value="m">'.$langs->trans("Month").'&nbsp;';
			print '<input name="duration_unit" type="radio" value="y">'.$langs->trans("Year").'&nbsp;';
			print '</td></tr>';
		}

		// Weight - Volume
		if ($_GET["type"] != 1)
		{
			// Le poids et le volume ne concerne que les produits et pas les services
			print '<tr><td>'.$langs->trans("Weight").'</td><td>';
			print '<input name="weight" size="4" value="">';
			print $formproduct->select_measuring_units("weight_units","weight");
			print '</td></tr>';
			print '<tr><td>'.$langs->trans("Volume").'</td><td>';
			print '<input name="volume" size="4" value="">';
			print $formproduct->select_measuring_units("volume_units","volume");
			print '</td></tr>';
		}

		// Note (invisible sur facture, propales...)
		print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td>';
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('note','',180,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="note" rows="8" cols="70">';
			print '</textarea>';
		}
		print "</td></tr>";
		print '</table>';

		print '<br>';

		print '<table class="border" width="100%">';
		if($conf->global->PRODUIT_MULTIPRICES)
		{
			print '<tr><td>'.$langs->trans("SellingPrice").' 1</td>';
			print '<td><input name="price" size="10" value="'.$product->price.'">';
			print $html->select_PriceBaseType($product->price_base_type, "price_base_type");
			print '</td></tr>';
			for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
			{
				print '<tr><td>'.$langs->trans("SellingPrice").' '.$i.'</td>';
				print '<td><input name="price_'.$i.'" size="10" value="'.$product->multiprices["$i"].'">';
				print $html->select_PriceBaseType($product->multiprices_base_type["$i"], "multiprices_base_type_".$i);
				print '</td></tr>';
			}
		}
		// PRIX
		else
		{
			print '<tr><td>'.$langs->trans("SellingPrice").'</td>';
			print '<td><input name="price" size="10" value="'.$product->price.'">';
			print $html->select_PriceBaseType($product->price_base_type, "price_base_type");
			print '</td></tr>';
		}

		// MIN PRICE
		print '<tr><td>'.$langs->trans("MinPrice").'</td>';
		print '<td><input name="price_min" size="10" value="'.$product->price_min.'">';
		print '</td></tr>';

		// VAT
		print '<tr><td width="20%">'.$langs->trans("VATRate").'</td><td>';
		print $html->select_tva("tva_tx",$conf->defaulttx,$mysoc,'');
		print '</td></tr>';

		print '</table>';

		print '<br>';

		print '<table class="border" width="100%">';
		print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
		print '</table>';

		print '</form>';
	}
	else
	{
		//RODO
		// On assigne les valeurs meme en creation car elles sont definies si
		// on revient en erreur
		//
		$smarty->template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$_GET["canvas"].'/';
		$tvaarray = load_tva($db,"tva_tx",$conf->defaulttx,$mysoc,'');
		$smarty->assign('tva_taux_value', $tvaarray['value']);
		$smarty->assign('tva_taux_libelle', $tvaarray['label']);
		$smarty->display($_GET["canvas"].'-create.tpl');
	}
}

/**
 *
 * Fiche produit
 *
 */
if ($_GET["id"] || $_GET["ref"])
{
	$product = new Product($db);

	if ($_GET["ref"])
	{
		$result = $product->fetch('',$_GET["ref"]);
		$_GET["id"] = $product->id;
	}
	elseif ($_GET["id"])
	{
		$result = $product->fetch($_GET["id"]);
	}

	// Gestion des produits specifiques
	$product->canvas = '';
	if ($conf->global->PRODUCT_CANVAS_ABILITY)
	{
		if ($product->canvas <> '' && file_exists('canvas/product.'.$product->canvas.'.class.php') )
		{
			$class = 'Product'.ucfirst($product->canvas);
			include_once('canvas/product.'.$product->canvas.'.class.php');
			$product = new $class($db);

			$result = $product->FetchCanvas($_GET["id"],'',$_GET["action"]);

			$smarty->template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$product->canvas.'/';

			$product->assign_smarty_values($smarty,$_GET["action"]);
		}
	}
	// END TODO RODO FINISH THIS PART

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ( $result )
	{
		if ($_GET["action"] <> 'edit')
		{
			$head=product_prepare_head($product, $user);
			$titre=$langs->trans("CardProduct".$product->type);
			dolibarr_fiche_head($head, 'card', $titre);
			print "\n<!-- CUT HERE -->\n";
			// Confirmation de la suppression de la facture
			if ($_GET["action"] == 'delete')
			{
				$html = new Form($db);
				$html->form_confirm("fiche.php?id=".$product->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete");
				print "<br />\n";
			}

			print($mesg);
		}
		if ($_GET["action"] <> 'edit' && $product->canvas <> '')
		{
			/*
			 *  Smarty en mode visu
			 */
			$smarty->assign('fiche_cursor_prev',$previous_ref);
			$smarty->assign('fiche_cursor_next',$next_ref);

			// Photo
			//$nbphoto=$product->show_photos($conf->produit->dir_output,1,1,0);

			$smarty->display($product->canvas.'-view.tpl');

			print "</div>\n<!-- CUT HERE -->\n";
		}

		if ($_GET["action"] <> 'edit' && $product->canvas == '')
		{
			// En mode visu
			print '<table class="border" width="100%"><tr>';

			// Reference
			print '<td width="15%">'.$langs->trans("Ref").'</td><td width="85%">';
			print $html->showrefnav($product,'ref','',1,'ref');
			print '</td>';

			$nblignes=6;
			if ($product->isproduct() && $conf->stock->enabled) $nblignes++;
			if ($product->isservice()) $nblignes++;
			if ($product->is_photo_available($conf->produit->dir_output))
			{
				// Photo
				print '<td valign="middle" align="center" rowspan="'.$nblignes.'">';
				$nbphoto=$product->show_photos($conf->produit->dir_output,1,1,0);
				print '</td>';
			}
			print '</tr>';

			// Libelle
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td></tr>';

			// MultiPrix
			if($conf->global->PRODUIT_MULTIPRICES)
			{
				for ($i=1; $i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++)
				{
					print '<tr><td>'.$langs->trans("SellingPrice").' '.$i.'</td>';

					if ($product->multiprices_base_type["$i"] == 'TTC')
					{
						print '<td>'.price($product->multiprices_ttc["$i"]);
					}
					else
					{
						print '<td>'.price($product->multiprices["$i"]);
					}

					if ($product->multiprices_base_type["$i"])
					{
						print ' '.$langs->trans($product->multiprices_base_type["$i"]);
					}
					else
					{
						print ' '.$langs->trans($product->price_base_type);
					}
					print '</td></tr>';
					
					// Prix mini
					print '<tr><td>'.$langs->trans("MinPrice").' '.$i.'</td><td>';
					if ($product->multiprices_base_type["$i"] == 'TTC')
					{
						print price($product->multiprices_min_ttc["$i"]).' '.$langs->trans($product->multiprices_base_type["$i"]);
					}
					else
					{
						print price($product->multiprices_min["$i"]).' '.$langs->trans($product->multiprices_base_type["$i"]);
					}
					print '</td></tr>';					
				}
			}
			else
			{
				// Prix
				print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
				if ($product->price_base_type == 'TTC')
				{
					print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
				}
				else
				{
					print price($product->price).' '.$langs->trans($product->price_base_type);
				}
				print '</td></tr>';
				
				// Prix mini
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($product->price_base_type == 'TTC')
				{
					print price($product->price_min_ttc).' '.$langs->trans($product->price_base_type);
				}
				else
				{
					print price($product->price_min).' '.$langs->trans($product->price_base_type);
				}
				print '</td></tr>';
			}

			// TVA
			print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.vatrate($product->tva_tx,true).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td><td>';
			print $product->getLibStatut(2);
			print '</td></tr>';
				
			// Description
			print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($product->description).'</td></tr>';

			// Nature
			if($product->type!=1)
			{
				print '<tr><td>'.$langs->trans("Nature").'</td><td>';
				print $product->getLibFinished();
				print '</td></tr>';
			}
				
			if ($product->isservice())
			{
				// Duration
				print '<tr><td>'.$langs->trans("Duration").'</td><td>'.$product->duration_value.'&nbsp;';
				if ($product->duration_value > 1)
				{
					$dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
				}
				else if ($product->duration_value > 0)
				{
					$dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
				}
				print $langs->trans($dur[$product->duration_unit])."&nbsp;";

				print '</td></tr>';
			}
			else
			{
				// Weight / Volume
				print '<tr><td>'.$langs->trans("Weight").'</td><td>';
				if ($product->weight != '')
				{
					print $product->weight." ".measuring_units_string($product->weight_units,"weight");
				}
				else
				{
					print '&nbsp;';
				}
				print "</td></tr>\n";

				print '<tr><td>'.$langs->trans("Volume").'</td><td>';
				if ($product->volume != '')
				{
					print $product->volume." ".measuring_units_string($product->volume_units,"volume");
				}
				else
				{
					print '&nbsp;';
				}
				print "</td></tr>\n";
			}
				
			// Note
			print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>'.nl2br($product->note).'</td></tr>';

			print "</table>\n";
			print "</div>\n<!-- CUT HERE -->\n";
		}
	}

	/*
	 * Fiche en mode edition
	 */
	if ($_GET["action"] == 'edit' && $user->rights->produit->creer)
	{
		if ($product->isservice()) {
			print_fiche_titre($langs->trans('Modify').' '.$langs->trans('Service').' : '.$product->ref, "");
		} else {
			print_fiche_titre($langs->trans('Modify').' '.$langs->trans('Product').' : '.$product->ref, "");
		}

		if ($mesg) {
			print '<br><div class="error">'.$mesg.'</div><br>';
		}

		if ( $product->canvas == '')
		{
	  print "<!-- CUT HERE -->\n";
	  print "<form action=\"fiche.php\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  print '<input type="hidden" name="id" value="'.$product->id.'">';
	  print '<input type="hidden" name="canvas" value="'.$product->canvas.'">';
	  print '<table class="border" width="100%">';
	  print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="40" maxlength="32" value="'.$product->ref.'"></td></tr>';
	  print '<tr><td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

	  // Status
	  print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
	  print '<select class="flat" name="statut">';
	  if ($product->status)
	  {
	  	print '<option value="1" selected="true">'.$langs->trans("OnSell").'</option>';
	  	print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
	  }
	  else
	  {
	  	print '<option value="1">'.$langs->trans("OnSell").'</option>';
	  	print '<option value="0" selected="true">'.$langs->trans("NotOnSell").'</option>';
	  }
	  print '</select>';
	  print '</td></tr>';
	   
	  // Description (utilisé dans facture, propale...)
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">';
	  print "\n";
	  if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
	  {
	  	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	  	$doleditor=new DolEditor('desc',$product->description,160,'dolibarr_notes','',false);
	  	$doleditor->Create();
	  }
	  else
	  {
	  	print '<textarea name="desc" rows="4" cols="90">';
	  	print dol_htmlentitiesbr_decode($product->description);
	  	print "</textarea>";
	  }
	  print "</td></tr>";
	  print "\n";

	  // Nature
	  if($product->type!=1)
	  {
	  	print '<tr><td>'.$langs->trans("Nature").'</td><td>';
	  	$statutarray=array('-1'=>'&nbsp;', '1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
	  	$html->select_array('finished',$statutarray,$product->finished);
	  	print '</td></tr>';
	  }

	  if ($product->isproduct() && $conf->stock->enabled)
	  {
	  	print "<tr>".'<td>'.$langs->trans("StockLimit").'</td><td colspan="2">';
	  	print '<input name="seuil_stock_alerte" size="4" value="'.$product->seuil_stock_alerte.'">';
	  	print '</td></tr>';
	  }
	  else
	  {
	  	print '<input name="seuil_stock_alerte" type="hidden" value="0">';
	  }

	  if ($product->isservice())
	  {
	  	// Duration
	  	print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="2"><input name="duration_value" size="3" maxlength="5" value="'.$product->duration_value.'">';
	  	print '&nbsp; ';
	  	print '<input name="duration_unit" type="radio" value="h"'.($product->duration_unit=='h'?' checked':'').'>'.$langs->trans("Hour");
	  	print '&nbsp; ';
	  	print '<input name="duration_unit" type="radio" value="d"'.($product->duration_unit=='d'?' checked':'').'>'.$langs->trans("Day");
	  	print '&nbsp; ';
	  	print '<input name="duration_unit" type="radio" value="w"'.($product->duration_unit=='w'?' checked':'').'>'.$langs->trans("Week");
	  	print '&nbsp; ';
	  	print '<input name="duration_unit" type="radio" value="m"'.($product->duration_unit=='m'?' checked':'').'>'.$langs->trans("Month");
	  	print '&nbsp; ';
	  	print '<input name="duration_unit" type="radio" value="y"'.($product->duration_unit=='y'?' checked':'').'>'.$langs->trans("Year");

	  	print '</td></tr>';
	  }
	  else
	  {
	  	// Weight / Volume
	  	print '<tr><td>'.$langs->trans("Weight").'</td><td>';
	  	print '<input name="weight" size="5" value="'.$product->weight.'"> ';
	  	print $formproduct->select_measuring_units("weight_units", "weight", $product->weight_units);
	  	print '</td></tr>';
	  	print '<tr><td>'.$langs->trans("Volume").'</td><td>';
	  	print '<input name="volume" size="5" value="'.$product->volume.'"> ';
	  	print $formproduct->select_measuring_units("volume_units", "volume", $product->volume_units);
	  	print '</td></tr>';
	  }

	  // Note
	  print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td colspan="2">';
	  if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
	  {
	  	require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
	  	$doleditor=new DolEditor('note',$product->note,200,'dolibarr_notes','',false);
	  	$doleditor->Create();
	  }
	  else
	  {
	  	print '<textarea name="note" rows="8" cols="70">';
	  	print dol_htmlentitiesbr_decode($product->note);
	  	print "</textarea>";
	  }
	  print "</td></tr>";

	  print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
	  print "<!-- CUT HERE -->\n";
		}
		else
		{
	  $tvaarray = load_tva($db,"tva_tx",$conf->defaulttx,$mysoc,'','');
	  $smarty->assign('tva_taux_value', $tvaarray['value']);
	  $smarty->assign('tva_taux_libelle', $tvaarray['label']);
	  $smarty->display($product->canvas.'-edit.tpl');
		}
	}
}
else if (!$_GET["action"] == 'create')
{
	Header("Location: index.php");
	exit;
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
	if ( $user->rights->produit->creer)
	{
		if ($product->no_button_edit <> 1)
		print '<a class="butAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Modify").'</a>';

		if ($product->no_button_copy <> 1)
		print '<a class="butAction" href="fiche.php?action=clone&amp;id='.$product->id.'">'.$langs->trans("CreateCopy").'</a>';
	}

	/*
	 if ($product->isproduct() && $user->rights->commande->creer)
	 {
	 $langs->load('orders');
	 print '<a class="butAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">';
	 print $langs->trans("CreateCustomerOrder").'</a>';
	 }

	 if ($product->isproduct() && $user->rights->fournisseur->commande->creer)
	 {
	 $langs->load('orders');
	 print '<a class="butAction" href="fiche.php?action=fastappro&amp;id='.$product->id.'">';
	 print $langs->trans("CreateSupplierOrder").'</a>';
	 }
	 */

	$product_is_used = $product->verif_prod_use($product->id);
	if ($user->rights->produit->supprimer)
	{
		if (! $product_is_used && $product->no_button_delete <> 1)
		{
			print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$product->id.'">'.$langs->trans("Delete").'</a>';
		}
		else
		{
			print '<a class="butActionRefused" href="#" title="'.$langs->trans("ProductIsUsed").'">'.$langs->trans("Delete").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Delete").'</a>';
	}
}

print "\n</div><br>\n";


if ($_GET["id"] && $_GET["action"] == '' && $product->status)
{
	$propal = New Propal($db);

	print '<table width="100%" class="noborder">';

	// Propals
	if($conf->propal->enabled && $user->rights->propale->creer)
	{
		$langs->load("propal");

		print '<tr class="liste_titre"><td width="50%" valign="top" class="liste_titre">';
		print $langs->trans("AddToMyProposals") . '</td>';

		if ($user->rights->societe->client->voir)
		{
	  print '<td width="50%" valign="top" class="liste_titre">';
	  print $langs->trans("AddToOtherProposals").'</td>';
		}
		else
		{
	  print '<td width="50%" valign="top" class="liste_titre">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de "Mes propals"
		print '<tr><td width="50%" valign="top">';

		$sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.ref,".$db->pdate("p.datep")." as dp";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
		$sql .=" WHERE p.fk_soc = s.rowid AND p.fk_statut = 0 AND p.fk_user_author = ".$user->id;
		$sql .= " ORDER BY p.datec DESC, tms DESC";

		$result=$db->query($sql);
		if ($result)
		{
	  $var=true;
	  $num = $db->num_rows($result);
	  print '<table class="noborder" width="100%">';
	  if ($num)
	  {
	  	$i = 0;
	  	while ($i < $num)
	  	{
	  		$objp = $db->fetch_object($result);
	  		$var=!$var;
	  		print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	  		print "<tr $bc[$var]>";
	  		print "<td nowrap>";
	  		print '<input type="hidden" name="action" value="addinpropal">';
	  		print "<a href=\"../comm/propal.php?propalid=".$objp->propalid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a></td>\n";
	  		print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dolibarr_trunc($objp->nom,18)."</a></td>\n";
	  		print "<td nowrap=\"nowrap\">".dolibarr_print_date($objp->dp,"%d %b")."</td>\n";
	  		print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
	  		print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
	  		print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
	  		print " ".$product->stock_proposition;
	  		print '</td><td align="right">';
	  		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	  		print '</td>';
	  		print '</tr>';
	  		print '</form>';
	  		$i++;
	  	}
	  }
	  else {
	  	print "<tr ".$bc[!$var]."><td>";
	  	print $langs->trans("NoOpenedPropals");
	  	print "</td></tr>";
	  }
	  print "</table>";
	  $db->free($result);
		}

		print '</td>';

		if ($user->rights->societe->client->voir)
		{
	  // Liste de "Other propals"
	  print '<td width="50%" valign="top">';

	  $var=true;
	  $otherprop = $propal->liste_array(1, ' <> '.$user->id);
	  print '<table class="noborder" width="100%">';

	  if (is_array($otherprop) && sizeof($otherprop))
	  {
	  	$var=!$var;
	  	print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	  	print '<tr '.$bc[$var].'><td colspan="3">';
	  	print '<input type="hidden" name="action" value="addinpropal">';
	  	print $langs->trans("OtherPropals").'</td><td>';
	  	$html->select_array("propalid", $otherprop);
	  	print '</td></tr>';
	  	print '<tr '.$bc[$var].'><td nowrap="nowrap" colspan="2">'.$langs->trans("Qty");
	  	print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
	  	print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
	  	print '</td><td align="right">';
	  	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	  	print '</td></tr>';
	  	print '</form>';
	  }
	  else
	  {
	  	print "<tr ".$bc[!$var]."><td>";
	  	print $langs->trans("NoOtherOpenedPropals");
	  	print '</td></tr>';
	  }
	  print '</table>';

	  print '</td>';
		}

		print '</tr>';
	}

	$commande = New Commande($db);


	// Commande
	if($conf->commande->enabled && $user->rights->commande->creer)
	{
		$langs->load("orders");

		print '<tr class="liste_titre"><td width="50%" valign="top" class="liste_titre">';
		print $langs->trans("AddToMyOrders").'</td>';

		if ($user->rights->societe->client->voir)
		{
	  print '<td width="50%" valign="top" class="liste_titre">';
	  print $langs->trans("AddToOtherOrders").'</td>';
		}
		else
		{
	  print '<td width="50%" valign="top" class="liste_titre">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de "Mes commandes"
		print '<tr><td width="50%" valign="top">';
		$sql = "SELECT s.nom, s.rowid as socid, c.rowid as commandeid, c.ref,".$db->pdate("c.date_commande")." as dc";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql .=" WHERE c.fk_soc = s.rowid AND c.fk_statut = 0 AND c.fk_user_author = ".$user->id;
		$sql .= " ORDER BY c.date_creation DESC";

		$result=$db->query($sql);
		if ($result)
		{
	  $num = $db->num_rows($result);
	  $var=true;
	  print '<table class="noborder" width="100%">';
	  if ($num)
	  {
	  	$i = 0;
	  	while ($i < $num)
	  	{
	  		$objc = $db->fetch_object($result);
	  		$var=!$var;
	  		print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	  		print "<tr $bc[$var]>";
	  		print "<td nowrap>";
	  		print '<input type="hidden" name="action" value="addincommande">';
	  		print "<a href=\"../commande/fiche.php?id=".$objc->commandeid."\">".img_object($langs->trans("ShowOrder"),"order")." ".$objc->ref."</a></td>\n";
	  		print "<td><a href=\"../comm/fiche.php?socid=".$objc->socid."\">".dolibarr_trunc($objc->nom,18)."</a></td>\n";
	  		print "<td nowrap=\"nowrap\">".dolibarr_print_date($objc->dc,"%d %b")."</td>\n";
	  		print '<td><input type="hidden" name="commandeid" value="'.$objc->commandeid.'">';
	  		print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
	  		print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
	  		print " ".$product->stock_proposition;
	  		print '</td><td align="right">';
	  		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	  		print '</td>';
	  		print '</tr>';
	  		print '</form>';
	  		$i++;
	  	}
	  }
	  else
	  {
	  	print "<tr ".$bc[!$var]."><td>";
	  	print $langs->trans("NoOpenedOrders");
	  	print '</td></tr>';
	  }
	  print "</table>";
	  $db->free($result);
		}

		print '</td>';

		if ($user->rights->societe->client->voir)
		{
	  // Liste de "Other orders"
	  print '<td width="50%" valign="top">';

	  $var=true;
	  $othercom = $commande->liste_array(1, ' <> '.$user->id);
	  print '<table class="noborder" width="100%">';
	  if (is_array($othercom) && sizeof($othercom))
	  {
	  	$var=!$var;
	  	print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	  	print '<tr '.$bc[$var].'><td colspan="3">';
	  	print '<input type="hidden" name="action" value="addincommande">';
	  	print $langs->trans("OtherOrders").'</td><td>';
	  	$html->select_array("commandeid", $othercom);
	  	print '</td></tr>';
	  	print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("Qty");
	  	print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
	  	print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
	  	print '</td><td align="right">';
	  	print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	  	print '</td></tr>';
	  	print '</form>';
	  }
	  else
	  {
	  	print "<tr ".$bc[!$var]."><td>";
	  	print $langs->trans("NoOtherOpenedOrders");
	  	print '</td></tr>';
	  }
	  print '</table>';
		}
		print '</td>';

		print '</tr>';
	}

	// Factures
	if($conf->facture->enabled && $user->rights->facture->creer)
	{


		print '<tr class="liste_titre"><td width="50%" valign="top" class="liste_titre">';
		print $langs->trans("AddToMyBills").'</td>';

		if ($user->rights->societe->client->voir)
		{
	  print '<td width="50%" valign="top" class="liste_titre">';
	  print $langs->trans("AddToOtherBills").'</td>';
		}
		else
		{
	  print '<td width="50%" valign="top" class="liste_titre">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de Mes factures
		print '<tr><td width="50%" valign="top">';
		$sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
		$sql .=" WHERE f.fk_soc = s.rowid AND f.fk_statut = 0 AND f.fk_user_author = ".$user->id;
		$sql .= " ORDER BY f.datec DESC, f.rowid DESC";

		$result=$db->query($sql);
		if ($result)
		{
	  $num = $db->num_rows($result);
	  $var=true;
	  print '<table class="noborder" width="100%">';
	  if ($num)
	  {
	  	$i = 0;
	  	while ($i < $num)
	  	{
	  		$objp = $db->fetch_object($result);
	  		$var=!$var;
	  		print '<form method="POST" action="fiche.php?id='.$product->id.'">';
	  		print "<tr $bc[$var]>";
	  		print "<td nowrap>";
	  		print '<input type="hidden" name="action" value="addinfacture">';
	  		print "<a href=\"../compta/facture.php?facid=".$objp->factureid."\">".img_object($langs->trans("ShowBills"),"bill")." ".$objp->facnumber."</a></td>\n";
	  		print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dolibarr_trunc($objp->nom,18)."</a></td>\n";
	  		print "<td nowrap=\"nowrap\">".dolibarr_print_date($objp->df,"%d %b")."</td>\n";
	  		print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
	  		print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
	  		print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
	  		print '</td><td align="right">';
	  		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	  		print '</td>';
	  		print '</tr>';
	  		print '</form>';
	  		$i++;
	  	}
	  }
	  else {
	  	print "<tr ".$bc[!$var]."><td>";
	  	print $langs->trans("NoDraftBills");
	  	print '</td></tr>';
	  }
	  print "</table>";
	  $db->free($result);
		}
		else
		{
	  dolibarr_print_error($db);
		}

		print '</td>';

		if ($user->rights->societe->client->voir)
		{
	  print '<td width="50%" valign="top">';

	  // Liste de Autres factures
	  $var=true;

	  $sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
	  $sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
	  $sql .=" WHERE f.fk_soc = s.rowid AND f.fk_statut = 0 AND f.fk_user_author <> ".$user->id;
	  $sql .= " ORDER BY f.datec DESC, f.rowid DESC";

	  $result=$db->query($sql);
	  if ($result)
	  {
	  	$num = $db->num_rows($result);
	  	$var=true;
	  	print '<table class="noborder" width="100%">';
	  	if ($num) {
	  		$i = 0;
	  		while ($i < $num)
	  		{
		    $objp = $db->fetch_object($result);

		    $var=!$var;
		    print '<form method="POST" action="fiche.php?id='.$product->id.'">';
		    print "<tr $bc[$var]>";
		    print "<td><a href=\"../compta/facture.php?facid=".$objp->factureid."\">$objp->facnumber</a></td>\n";
		    print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dolibarr_trunc($objp->nom,24)."</a></td>\n";
		    print "<td colspan=\"2\">".$langs->trans("Qty");
		    print '<input type="hidden" name="action" value="addinfacture">';
		    print "</td>";
		    print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
		    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
		    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
		    print '</td><td align="right">';
		    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
		    print '</td>';
		    print '</tr>';
		    print '</form>';
		    $i++;
	  		}
	  	}
	  	else {
	  		print "<tr ".$bc[!$var]."><td>";
	  		print $langs->trans("NoOtherDraftBills");
	  		print '</td></tr>';
	  	}
	  	print "</table>";
	  	$db->free($result);
	  }
	  else
	  {
	  	dolibarr_print_error($db);
	  }
		}

		print '</td></tr>';
	}

	print '</table>';

	print '<br>';
}


$db->close();

llxFooter('$Date$ - $Revision$');


/**
 *		\brief		Load tva_taux_value and tva_taux_libelle array
 *		\remarks	Ne sert que pour smarty
 */
function load_tva($db,$name='tauxtva', $defaulttx='', $societe_vendeuse='', $societe_acheteuse='', $taux_produit='')
{
	global $langs,$conf,$mysoc;

	$retarray=array();

	if (is_object($societe_vendeuse->pays_code))
	{
		$code_pays=$societe_vendeuse->pays_code;
	}
	else
	{
		$code_pays=$mysoc->pays_code;	// Pour compatibilite ascendente
	}

	// Recherche liste des codes TVA du pays vendeur
	$sql  = "SELECT t.taux,t.recuperableonly";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p";
	$sql .= " WHERE t.fk_pays = p.rowid AND p.code = '".$code_pays."'";
	$sql .= " AND t.active = 1";
	$sql .= " ORDER BY t.taux ASC, t.recuperableonly ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		for ($i = 0; $i < $num; $i++)
		{
			$obj = $db->fetch_object($resql);
			$txtva[ $i ] = $obj->taux;
			$libtva[ $i ] = $obj->taux.'%'.($obj->recuperableonly ? ' *':'');
		}
	}

	// Définition du taux à pré-sélectionner
	if ($defaulttx == '') $defaulttx=get_default_tva($societe_vendeuse,$societe_acheteuse,$taux_produit);
	// Si taux par defaut n'a pu etre trouvé, on prend dernier.
	// Comme ils sont triés par ordre croissant, dernier = plus élevé = taux courant
	if ($defaulttx == '') $defaulttx = $txtva[sizeof($txtva)-1];

	$nbdetaux = sizeof($txtva);

	for ($i = 0 ; $i < $nbdetaux ; $i++)
	{
		$retarray['value'][$i] = $txtva[$i];
		$retarray['label'][$i] = $libtva[$i];
	}

	return $retarray;
}
?>
