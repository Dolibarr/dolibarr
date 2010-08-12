<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Auguria SARL         <info@auguria.org>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/canvas.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled) require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

$langs->load("products");
$langs->load("other");
if ($conf->stock->enabled) $langs->load("stocks");
if ($conf->facture->enabled) $langs->load("bills");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
$socid=$user->societe_id?$user->societe_id:0;
$result=restrictedArea($user,'produit|service',$id,'product','','',$fieldid);

// For canvas usage
if (empty($_GET["canvas"]))
{
	$_GET["canvas"] = 'default@product';
	if ($_GET["type"] == 1) $_GET["canvas"] = 'service@product';
}

$mesg = '';


/*
 * Actions
 */

if ($_POST['action'] == 'setproductaccountancycodebuy')
{
	$product = new Product($db);
	$result=$product->fetch($_POST['id']);
	$product->accountancy_code_buy=$_POST["productaccountancycodebuy"];
	$result=$product->update($product->id,$user,1,0,1);
	if ($result < 0)
	{
		$mesg=join(',',$product->errors);
	}
	$POST["action"]="";
	$id=$_POST["id"];
	$_GET["id"]=$_POST["id"];
}

if ($_POST['action'] == 'setproductaccountancycodesell')
{
	$product = new Product($db);
	$result=$product->fetch($_POST['id']);
	$product->accountancy_code_sell=$_POST["productaccountancycodesell"];
	$result=$product->update($product->id,$user,1,0,1);
	if ($result < 0)
	{
		$mesg=join(',',$product->errors);
	}
	$POST["action"]="";
	$id=$_POST["id"];
	$_GET["id"]=$_POST["id"];
}

if ($_GET["action"] == 'fastappro')
{
	$product = new Product($db);
	$product->fetch($_GET["id"]);
	$result = $product->fastappro($user);
	Header("Location: fiche.php?id=".$_GET["id"]);
	exit;
}


// Add a product or service
if ($_POST["action"] == 'add' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	$error=0;

	if (empty($_POST["libelle"]))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Label')).'</div>';
		$_GET["action"] = "create";
		$_GET["canvas"] = $_POST["canvas"];
		$_GET["type"] 	= $_POST["type"];
		$error++;
	}
	if (empty($_POST["ref"]))
	{
		$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Ref')).'</div>';
		$_GET["action"] = "create";
		$_GET["canvas"] = $_POST["canvas"];
		$_GET["type"] 	= $_POST["type"];
		$error++;
	}

	$product=new Product($db);

	$usecanvas=$_POST["canvas"];
	if (empty($conf->global->MAIN_USE_CANVAS)) $usecanvas=0;

	if (! empty($usecanvas))	// Overwrite product here
	{
		$canvas = new Canvas($db,$user);
		$product = $canvas->load_canvas('product',$_POST["canvas"]);
	}

	if (! $error)
	{
		$product->ref                = $_POST["ref"];
		$product->libelle            = $_POST["libelle"];
		$product->price_base_type    = $_POST["price_base_type"];
		if ($product->price_base_type == 'TTC') $product->price_ttc = $_POST["price"];
		else $product->price = $_POST["price"];
		if ($product->price_base_type == 'TTC') $product->price_min_ttc = $_POST["price_min"];
		else $product->price_min = $_POST["price_min"];
		$product->tva_tx             = $_POST["tva_tx"];

		// local taxes.
		$product->localtax1_tx 			= get_localtax($product->tva_tx,1);
		$product->localtax2_tx 			= get_localtax($product->tva_tx,2);

		$product->type               	= $_POST["type"];
		$product->status             	= $_POST["statut"];
		$product->status_buy           	= $_POST["statut_buy"];
		$product->description        	= dol_htmlcleanlastbr($_POST["desc"]);
		$product->note               	= dol_htmlcleanlastbr($_POST["note"]);
		$product->duration_value     	= $_POST["duration_value"];
		$product->duration_unit      	= $_POST["duration_unit"];
		$product->seuil_stock_alerte 	= $_POST["seuil_stock_alerte"]?$_POST["seuil_stock_alerte"]:0;
		$product->canvas             	= $_POST["canvas"];
		$product->weight             	= $_POST["weight"];
		$product->weight_units       	= $_POST["weight_units"];
		$product->length             	= $_POST["size"];
		$product->length_units       	= $_POST["size_units"];
		$product->surface            	= $_POST["surface"];
		$product->surface_units      	= $_POST["surface_units"];
		$product->volume             	= $_POST["volume"];
		$product->volume_units       	= $_POST["volume_units"];
		$product->finished           	= $_POST["finished"];
		$product->hidden             	= $_POST["hidden"]=='yes'?1:0;

		// MultiPrix
		if($conf->global->PRODUIT_MULTIPRICES)
		{
			for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
			{
				if($_POST["price_".$i])
				{
					$product->multiprices["$i"] = price2num($_POST["price_".$i],'MU');
					$product->multiprices_base_type["$i"] = $_POST["multiprices_base_type_".$i];
				}
				else
				{
					$product->multiprices["$i"] = "";
				}
			}
		}

		$id = $product->create($user);

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
// Update a product or service
if ($_POST["action"] == 'update' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	if (! empty($_POST["cancel"]))
	{
		$_GET["action"] = '';
		$_GET["id"] = $_POST["id"];
	}
	else
	{
		$product=new Product($db);

		$usecanvas=$_POST["canvas"];
		if (empty($conf->global->MAIN_USE_CANVAS)) $usecanvas=0;

		if (! empty($usecanvas))	// Overwrite product here
		{
			$canvas = new Canvas($db,$user);
			$product = $canvas->load_canvas('product',$_POST["canvas"]);
		}

		if ($product->fetch($_POST["id"]))
		{
			$product->ref                = $_POST["ref"];
			$product->libelle            = $_POST["libelle"];
			$product->description        = dol_htmlcleanlastbr($_POST["desc"]);
			$product->note               = dol_htmlcleanlastbr($_POST["note"]);
			$product->status             = $_POST["statut"];
			$product->status_buy         = $_POST["statut_buy"];
			$product->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
			$product->duration_value     = $_POST["duration_value"];
			$product->duration_unit      = $_POST["duration_unit"];
			$product->canvas             = $_POST["canvas"];
			$product->weight             = $_POST["weight"];
			$product->weight_units       = $_POST["weight_units"];
			$product->length             = $_POST["size"];
			$product->length_units       = $_POST["size_units"];
			$product->surface            = $_POST["surface"];
			$product->surface_units      = $_POST["surface_units"];
			$product->volume             = $_POST["volume"];
			$product->volume_units       = $_POST["volume_units"];
			$product->finished           = $_POST["finished"];
			$product->hidden             = $_POST["hidden"]=='yes'?1:0;

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
		}
	}
}

// Action clone object
if ($_POST["action"] == 'confirm_clone' && $_POST['confirm'] == 'yes' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	if (empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_prices"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$db->begin();

		$product = new Product($db);
		$originalId = $_GET["id"];
		if ($product->fetch($_GET["id"]) > 0)
		{
			$product->ref = $_REQUEST["clone_ref"];
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

						$mesg='<div class="error">'.$langs->trans("ErrorProductAlreadyExists",$product->ref);
						$mesg.=' <a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref.'">'.$langs->trans("ShowCardHere").'</a>.';
						$mesg.='</div>';
						//dol_print_error($product->db);
					}
					else
					{
						$db->rollback();
						dol_print_error($product->db);
					}
				}
			}
		}
		else
		{
			$db->rollback();
			dol_print_error($product->db);
		}
	}
}

/*
 * Suppression d'un produit/service pas encore affect
 */
if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes' && $user->rights->produit->supprimer)
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
	$propal = new Propal($db);
	$result=$propal->fetch($_POST["propalid"]);
	if ($result <= 0)
	{
		dol_print_error($db,$propal->error);
		exit;
	}

	$soc = new Societe($db);
	$result=$soc->fetch($propal->socid);
	if ($result <= 0)
	{
		dol_print_error($db,$soc->error);
		exit;
	}

	$prod = new Product($db);
	$result=$prod->fetch($_GET['id']);
	if ($result <= 0)
	{
		dol_print_error($db,$prod->error);
		exit;
	}

	$desc = $prod->description;

	$tva_tx = get_default_tva($mysoc, $soc, $prod->id);
	$localtax1_tx= get_localtax($tva_tx, 1, $soc);
	$localtax2_tx= get_localtax($tva_tx, 2, $soc);

    $pu_ht = $prod->price;
    $pu_ttc = $prod->price_ttc;
    $price_base_type = $prod->price_base_type;

	// If multiprice
	if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
	{
		$pu_ht = $prod->multiprices[$soc->price_level];
		$pu_ttc = $prod->multiprices_ttc[$soc->price_level];
		$price_base_type = $prod->multiprices_base_type[$soc->price_level];
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
	$localtax1_tx, // localtax1
	$localtax2_tx, // localtax2
	$prod->id,
	$_POST["remise_percent"],
	$price_base_type,
	$pu_ttc
	);
	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT."/comm/propal.php?id=".$propal->id);
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
		dol_print_error($db,$commande->error);
		exit;
	}

	$soc = new Societe($db);
	$result=$soc->fetch($commande->socid);
	if ($result <= 0)
	{
		dol_print_error($db,$soc->error);
		exit;
	}

	$prod = new Product($db);
	$result=$prod->fetch($_GET['id']);
	if ($result <= 0)
	{
		dol_print_error($db,$prod->error);
		exit;
	}

	$desc = $prod->description;

	$tva_tx = get_default_tva($mysoc, $soc, $prod->id);
	$localtax1_tx= get_localtax($tva_tx, 1, $soc);
	$localtax2_tx= get_localtax($tva_tx, 2, $soc);


    $pu_ht = $prod->price;
    $pu_ttc = $prod->price_ttc;
    $price_base_type = $prod->price_base_type;

    // If multiprice
    if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
    {
        $pu_ht = $prod->multiprices[$soc->price_level];
        $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
        $price_base_type = $prod->multiprices_base_type[$soc->price_level];
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
	$localtax1_tx, // localtax1
	$localtax2_tx, // localtax2
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
		dol_print_error($db,$facture->error);
		exit;
	}

	$soc = new Societe($db);
	$soc->fetch($facture->socid);
	if ($result <= 0)
	{
		dol_print_error($db,$soc->error);
		exit;
	}

	$prod = new Product($db);
	$result = $prod->fetch($_GET["id"]);
	if ($result <= 0)
	{
		dol_print_error($db,$prod->error);
		exit;
	}

	$desc = $prod->description;

	$tva_tx = get_default_tva($mysoc, $soc, $prod->id);
	$localtax1_tx= get_localtax($tva_tx, 1, $soc);
	$localtax2_tx= get_localtax($tva_tx, 2, $soc);

    $pu_ht = $prod->price;
    $pu_ttc = $prod->price_ttc;
    $price_base_type = $prod->price_base_type;

    // If multiprice
    if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
    {
        $pu_ht = $prod->multiprices[$soc->price_level];
        $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
        $price_base_type = $prod->multiprices_base_type[$soc->price_level];
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
	$localtax1_tx,
	$localtax2_tx,
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
	Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_POST["id"]);
	exit;
}


/*
 * View
 */

$html = new Form($db);
$formproduct = new FormProduct($db);


/*
 * Fiche creation du produit
 */
if ($_GET["action"] == 'create' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	$helpurl='';
	if (isset($_GET["type"]) && $_GET["type"] == 0) $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	if (isset($_GET["type"]) && $_GET["type"] == 1)	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';

	llxHeader('',$langs->trans("CardProduct".$_GET["type"]),$helpurl);

	$usecanvas=$_GET["canvas"];
	if (empty($conf->global->MAIN_USE_CANVAS)) $usecanvas=0;

	if (empty($usecanvas))
	{
		print '<form action="fiche.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";

		if ($_GET["type"]==1) $title=$langs->trans("NewService");
		else $title=$langs->trans("NewProduct");
		print_fiche_titre($title);

		if ($mesg) print $mesg."\n";

		print '<table class="border" width="100%">';
		print '<tr>';
		print '<td class="fieldrequired" width="20%">'.$langs->trans("Ref").'</td><td><input name="ref" size="40" maxlength="32" value="'.$_POST["ref"].'">';
		if ($_error == 1)
		{
			print $langs->trans("RefAlreadyExists");
		}
		print '</td></tr>';

		// Label
		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$_POST["libelle"].'"></td></tr>';

		// On sell
		print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td>';
		$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
		$html->select_array('statut',$statutarray,$_POST["statut"]);
		print '</td></tr>';
		
		// To buy
		print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td>';
		$statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
		$html->select_array('statut_buy',$statutarray,$_POST["statut_buy"]);
		print '</td></tr>';

		// Stock min level
		if ($_GET["type"] != 1 && $conf->stock->enabled)
		{
			print '<tr><td>'.$langs->trans("StockLimit").'</td><td>';
			print '<input name="seuil_stock_alerte" size="4" value="'.$_POST["seuil_stock_alerte"].'">';
			print '</td></tr>';
		}
		else
		{
			print '<input name="seuil_stock_alerte" type="hidden" value="0">';
		}

		// Description (used in invoice, propal...)
		print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';

		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('desc',$_POST["desc"],160,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="desc" rows="4" cols="90">';
			print $_POST["desc"];
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

		if ($_GET["type"] != 1)	// Le poids et le volume ne concerne que les produits et pas les services
		{
			// Weight
			print '<tr><td>'.$langs->trans("Weight").'</td><td>';
			print '<input name="weight" size="4" value="'.$_POST["weight"].'">';
			print $formproduct->select_measuring_units("weight_units","weight");
			print '</td></tr>';
			// Length
			print '<tr><td>'.$langs->trans("Length").'</td><td>';
			print '<input name="size" size="4" value="'.$_POST["size"].'">';
			print $formproduct->select_measuring_units("size_units","size");
			print '</td></tr>';
			// Surface
			print '<tr><td>'.$langs->trans("Surface").'</td><td>';
			print '<input name="surface" size="4" value="'.$_POST["surface"].'">';
			print $formproduct->select_measuring_units("surface_units","surface");
			print '</td></tr>';
			// Volume
			print '<tr><td>'.$langs->trans("Volume").'</td><td>';
			print '<input name="volume" size="4" value="'.$_POST["volume"].'">';
			print $formproduct->select_measuring_units("volume_units","volume");
			print '</td></tr>';
		}

		// Hidden
		if (($_GET["type"] != 1 && $user->rights->produit->hidden)
		|| ($_GET["type"] == 1 && $user->rights->service->hidden))
		{
			print '<tr><td>'.$langs->trans("Hidden").'</td><td>';
			print $html->selectyesno('hidden',$product->hidden);
			print '</td></tr>';
		}
		else
		{
			print '<tr><td>'.$langs->trans("Hidden").'</td><td>';
			print yn("No");
			print '</td></tr>';
		}

		// Note (invisible sur facture, propales...)
		print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td>';
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('note',$_POST["note"],180,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea name="note" rows="8" cols="70">';
			print $_POST["note"];
			print '</textarea>';
		}
		print "</td></tr>";
		print '</table>';

		print '<br>';

		if ($conf->global->PRODUIT_MULTIPRICES)
		{
			// We do no show price array on create when multiprices enabled.
			// We must set them on prices tab.
		}
		else
		{
			print '<table class="border" width="100%">';

			// PRIX
			print '<tr><td>'.$langs->trans("SellingPrice").'</td>';
			print '<td><input name="price" size="10" value="'.$product->price.'">';
			print $html->select_PriceBaseType($product->price_base_type, "price_base_type");
			print '</td></tr>';

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
		}

		print '<center><input type="submit" class="button" value="'.$langs->trans("Create").'"></center>';

		print '</form>';
	}
	else
	{
		$canvas = new Canvas($db,$user);
		$product = $canvas->load_canvas('product',$_GET["canvas"]);

		$canvas->assign_values('create');
		$canvas->display_canvas();
	}
}

/**
 * Product card
 */

if ($_GET["id"] || $_GET["ref"])
{
	$product=new Product($db);

	// TODO en attendant d'inclure le nom du canvas dans les liens
	$productstatic = new Product($db);
	$result = $productstatic->getCanvas($_GET["id"],$_GET["ref"]);
	$usecanvas=$productstatic->canvas;
	if (empty($conf->global->MAIN_USE_CANVAS)) $usecanvas=0;

	if (empty($usecanvas))
	{
		$product->fetch($_GET["id"],$_GET["ref"]);
	}
	else 	// Gestion des produits specifiques
	{
		$canvas = new Canvas($db,$user);

		$product = $canvas->load_canvas('product',$productstatic->canvas);
		if (! $product) dol_print_error('','Faled to load canvas product-'.$productstatic->canvas);

		$canvas->fetch($productstatic->id,'',$_GET["action"]);
	}

	llxHeader('',$langs->trans("CardProduct".$product->type));

	/*
	 * Fiche en mode edition
	 */
	if ($_GET["action"] == 'edit' && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		if (empty($usecanvas))
		{
			if ($product->isservice()) {
				print_fiche_titre($langs->trans('Modify').' '.$langs->trans('Service').' : '.$product->ref, "");
			} else {
				print_fiche_titre($langs->trans('Modify').' '.$langs->trans('Product').' : '.$product->ref, "");
			}

			if ($mesg) {
				print '<br><div class="error">'.$mesg.'</div><br>';
			}

			// Main official, simple, and not duplicated code
			print "<form action=\"fiche.php\" method=\"post\">\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$product->id.'">';
			print '<input type="hidden" name="canvas" value="'.$product->canvas.'">';
			print '<table class="border" width="100%">';
			print '<tr><td width="15%">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="40" maxlength="32" value="'.$product->ref.'"></td></tr>';
			print '<tr><td>'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$product->libelle.'"></td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td colspan="2">';
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
			
			// To Buy
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td colspan="2">';
			print '<select class="flat" name="statut_buy">';
			if ($product->status_buy)
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

			// Description (used in invoice, propal...)
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
				print '<input name="seuil_stock_alerte" type="hidden" value="'.$product->seuil_stock_alerte.'">';
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
				// Weight
				print '<tr><td>'.$langs->trans("Weight").'</td><td>';
				print '<input name="weight" size="5" value="'.$product->weight.'"> ';
				print $formproduct->select_measuring_units("weight_units", "weight", $product->weight_units);
				print '</td></tr>';
				// Length
				print '<tr><td>'.$langs->trans("Length").'</td><td>';
				print '<input name="size" size="5" value="'.$product->length.'"> ';
				print $formproduct->select_measuring_units("size_units", "size", $product->length_units);
				print '</td></tr>';
				// Surface
				print '<tr><td>'.$langs->trans("Surface").'</td><td>';
				print '<input name="surface" size="5" value="'.$product->surface.'"> ';
				print $formproduct->select_measuring_units("surface_units", "surface", $product->surface_units);
				print '</td></tr>';
				// Volume
				print '<tr><td>'.$langs->trans("Volume").'</td><td>';
				print '<input name="volume" size="5" value="'.$product->volume.'"> ';
				print $formproduct->select_measuring_units("volume_units", "volume", $product->volume_units);
				print '</td></tr>';
			}

			// Hidden
			if ((! $product->isservice() && $user->rights->produit->hidden)
			|| ($product->isservice() && $user->rights->service->hidden))
			{
				print '<tr><td>'.$langs->trans("Hidden").'</td><td>';
				print $html->selectyesno('hidden',$product->hidden);
				print '</td></tr>';
			}
			else
			{
				print '<tr><td>'.$langs->trans("Hidden").'</td><td>';
				print yn("No");
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
			print '</table>';

			print '<br>';

			print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

			print '</form>';
		}
		else
		{
			$canvas->assign_values('edit');
			$canvas->display_canvas();
		}
	}
	/*
	 * Fiche en mode visu
	 */
	else
	{
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'card', $titre, 0, $picto);

		// Confirmation de la suppression de la facture
		if ($_GET["action"] == 'delete')
		{
			$ret=$html->form_confirm("fiche.php?id=".$product->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete",'',0,2);
			if ($ret == 'html') print '<br>';
		}

		if (empty($usecanvas))
		{
			// En mode visu
			print '<table class="border" width="100%"><tr>';

			// Ref
			print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
			print $html->showrefnav($product,'ref','',1,'ref');
			print '</td>';

			print '</tr>';

			// Label
			print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';

			$nblignes=4;
			if ($product->is_photo_available($conf->product->dir_output))
			{
				// Photo
				print '<td valign="middle" align="center" width="30%" rowspan="'.$nblignes.'">';
				print $product->show_photos($conf->product->dir_output,1,1,0,0,0,80);
				print '</td>';
			}

			print '</tr>';

			// Accountancy buy code
			print '<tr><td>'.$html->editfieldkey("ProductAccountancyBuyCode",'productaccountancycodesell',$product->accountancy_code_sell,'id',$product->id,$user->rights->produit->creer).'</td><td>';
			print $html->editfieldval("ProductAccountancyBuyCode",'productaccountancycodesell',$product->accountancy_code_sell,'id',$product->id,$user->rights->produit->creer);
			print '</td></tr>';

			// Accountancy sell code
			print '<tr><td>'.$html->editfieldkey("ProductAccountancySellCode",'productaccountancycodebuy',$product->accountancy_code_buy,'id',$product->id,$user->rights->produit->creer).'</td><td>';
			print $html->editfieldval("ProductAccountancySellCode",'productaccountancycodebuy',$product->accountancy_code_buy,'id',$product->id,$user->rights->produit->creer);
			print '</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td>';
			print $product->getLibStatut(2,0);
			print '</td></tr>';
			
			// To buy
			print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td>';
			print $product->getLibStatut(2,1);
			print '</td></tr>';

			// Description
			print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">'.nl2br($product->description).'</td></tr>';

			// Nature
			if($product->type!=1)
			{
				print '<tr><td>'.$langs->trans("Nature").'</td><td colspan="2">';
				print $product->getLibFinished();
				print '</td></tr>';
			}

			if ($product->isservice())
			{
				// Duration
				print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="2">'.$product->duration_value.'&nbsp;';
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
				// Weight
				print '<tr><td>'.$langs->trans("Weight").'</td><td colspan="2">';
				if ($product->weight != '')
				{
					print $product->weight." ".measuring_units_string($product->weight_units,"weight");
				}
				else
				{
					print '&nbsp;';
				}
				print "</td></tr>\n";
				// Length
				print '<tr><td>'.$langs->trans("Length").'</td><td colspan="2">';
				if ($product->length != '')
				{
					print $product->length." ".measuring_units_string($product->length_units,"size");
				}
				else
				{
					print '&nbsp;';
				}
				print "</td></tr>\n";
				// Surface
				print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="2">';
				if ($product->surface != '')
				{
					print $product->surface." ".measuring_units_string($product->surface_units,"surface");
				}
				else
				{
					print '&nbsp;';
				}
				print "</td></tr>\n";
				// Volume
				print '<tr><td>'.$langs->trans("Volume").'</td><td colspan="2">';
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

			// Hidden
			if ((! $product->isservice() && $user->rights->produit->hidden)
			|| ($product->isservice() && $user->rights->service->hidden))
			{
				print '<tr><td>'.$langs->trans("Hidden").'</td><td colspan="2">';
				print yn($product->hidden);
				print "</td></tr>\n";
			}
			else
			{
				print '<tr><td>'.$langs->trans("Hidden").'</td><td>';
				print yn("No");
				print '</td></tr>';
			}

			// Note
			print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="2">'.nl2br($product->note).'</td></tr>';

			print "</table>\n";
		}
		else
		{
			$canvas->assign_values('view');
			$canvas->display_canvas();
		}

		dol_fiche_end();
	}

}
else if (!$_GET["action"] == 'create')
{
	Header("Location: index.php");
	exit;
}



// Clone confirmation
if ($_GET["action"] == 'clone')
{
	// Create an array for form
	$formquestion=array(
		'text' => $langs->trans("ConfirmClone"),
	array('type' => 'text', 'name' => 'clone_ref','label' => $langs->trans("NewRefForClone"), 'value' => $langs->trans("CopyOf").' '.$product->ref, 'size'=>24),
	array('type' => 'checkbox', 'name' => 'clone_content','label' => $langs->trans("CloneContentProduct"), 'value' => 1),
	array('type' => 'checkbox', 'name' => 'clone_prices', 'label' => $langs->trans("ClonePricesProduct").' ('.$langs->trans("FeatureNotYetAvailable").')', 'value' => 0, 'disabled' => true)
	);
	// Paiement incomplet. On demande si motif = escompte ou autre
	$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$product->id,$langs->trans('CloneProduct'),$langs->trans('ConfirmCloneProduct',$product->ref),'confirm_clone',$formquestion,'yes');
}


/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
	if ($user->rights->produit->creer || $user->rights->service->creer)
	{
		if ($product->no_button_edit <> 1)
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$product->id.'">'.$langs->trans("Modify").'</a>';

		if ($product->no_button_copy <> 1)
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=clone&amp;id='.$product->id.'">'.$langs->trans("ToClone").'</a>';
	}

	$product_is_used = $product->verif_prod_use($product->id);
	if ($user->rights->produit->supprimer)
	{
		if (! $product_is_used && $product->no_button_delete <> 1)
		{
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$product->id.'">'.$langs->trans("Delete").'</a>';
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


/*
 * All the "Add to" areas
 */

if ($product->id && $_GET["action"] == '' && $product->status)
{
	print '<table width="100%" class="noborder">';

	// Propals
	if($conf->propal->enabled && $user->rights->propale->creer)
	{
		$propal = new Propal($db);

		$langs->load("propal");

		print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
		print $langs->trans("AddToMyProposals") . '</td>';

		if ($user->rights->societe->client->voir)
		{
			print '<td width="50%" class="liste_titre">';
			print $langs->trans("AddToOtherProposals").'</td>';
		}
		else
		{
			print '<td width="50%" class="liste_titre">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de "Mes propals"
		print '<tr><td'.($user->rights->societe->client->voir?' width="50%"':'').' valign="top">';

		$sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.ref, p.datep as dp";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
		$sql.= " WHERE p.fk_soc = s.rowid";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND p.fk_statut = 0";
		$sql.= " AND p.fk_user_author = ".$user->id;
		$sql.= " ORDER BY p.datec DESC, tms DESC";

		$result=$db->query($sql);
		if ($result)
		{
			$var=true;
			$num = $db->num_rows($result);
			print '<table class="nobordernopadding" width="100%">';
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="addinpropal">';
					print "<tr ".$bc[$var].">";
					print '<td nowrap="nowrap">';
					print "<a href=\"../comm/propal.php?id=".$objp->propalid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dol_print_date($objp->dp,"%d %b")."</td>\n";
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
			$otherprop = $propal->liste_array(1,1,1);
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" width="100%">';
			if (is_array($otherprop) && sizeof($otherprop))
			{
				$var=!$var;
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
			}
			else
			{
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoOtherOpenedPropals");
				print '</td></tr>';
			}
			print '</table>';
			print '</form>';

			print '</td>';
		}

		print '</tr>';
	}

	// Commande
	if($conf->commande->enabled && $user->rights->commande->creer)
	{
		$commande = new Commande($db);

		$langs->load("orders");

		print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
		print $langs->trans("AddToMyOrders").'</td>';

		if ($user->rights->societe->client->voir)
		{
			print '<td width="50%" class="liste_titre">';
			print $langs->trans("AddToOtherOrders").'</td>';
		}
		else
		{
			print '<td width="50%" class="liste_titre">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de "Mes commandes"
		print '<tr><td'.($user->rights->societe->client->voir?' width="50%"':'').' valign="top">';

		$sql = "SELECT s.nom, s.rowid as socid, c.rowid as commandeid, c.ref, c.date_commande as dc";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		$sql.= " WHERE c.fk_soc = s.rowid";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND c.fk_statut = 0";
		$sql.= " AND c.fk_user_author = ".$user->id;
		$sql.= " ORDER BY c.date_creation DESC";

		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$var=true;
			print '<table class="nobordernopadding" width="100%">';
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$objc = $db->fetch_object($result);
					$var=!$var;
					print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="addincommande">';
					print "<tr ".$bc[$var].">";
					print '<td nowrap="nowrap">';
					print "<a href=\"../commande/fiche.php?id=".$objc->commandeid."\">".img_object($langs->trans("ShowOrder"),"order")." ".$objc->ref."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=".$objc->socid."\">".dol_trunc($objc->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dol_print_date($db->jdate($objc->dc),"%d %b")."</td>\n";
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
			$othercom = $commande->liste_array(1, $user);
			print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" width="100%">';
			if (is_array($othercom) && sizeof($othercom))
			{
				$var=!$var;
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
			}
			else
			{
				print "<tr ".$bc[!$var]."><td>";
				print $langs->trans("NoOtherOpenedOrders");
				print '</td></tr>';
			}
			print '</table>';
			print '</form>';

			print '</td>';
		}

		print '</tr>';
	}

	// Factures
	if ($conf->facture->enabled && $user->rights->facture->creer)
	{
		print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
		print $langs->trans("AddToMyBills").'</td>';

		if ($user->rights->societe->client->voir)
		{
			print '<td width="50%" class="liste_titre">';
			print $langs->trans("AddToOtherBills").'</td>';
		}
		else
		{
			print '<td width="50%" class="liste_titre">&nbsp;</td>';
		}

		print '</tr>';

		// Liste de Mes factures
		print '<tr><td'.($user->rights->societe->client->voir?' width="50%"':'').' valign="top">';

		$sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber, f.datef as df";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND f.fk_statut = 0";
		$sql.= " AND f.fk_user_author = ".$user->id;
		$sql.= " ORDER BY f.datec DESC, f.rowid DESC";

		$result=$db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$var=true;
			print '<table class="nobordernopadding" width="100%">';
			if ($num)
			{
				$i = 0;
				while ($i < $num)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;
					print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="addinfacture">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print "<a href=\"../compta/facture.php?facid=".$objp->factureid."\">".img_object($langs->trans("ShowBills"),"bill")." ".$objp->facnumber."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dol_print_date($db->jdate($objp->df),"%d %b")."</td>\n";
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
			dol_print_error($db);
		}

		print '</td>';

		if ($user->rights->societe->client->voir)
		{
            $facture = new Facture($db);

			print '<td width="50%" valign="top">';

            // Liste de Autres factures
			$var=true;

			$sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber, f.datef as df";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
			$sql.= " WHERE f.fk_soc = s.rowid";
			$sql.= " AND f.entity = ".$conf->entity;
			$sql.= " AND f.fk_statut = 0";
			$sql.= " AND f.fk_user_author <> ".$user->id;
			$sql.= " ORDER BY f.datec DESC, f.rowid DESC";

			$result=$db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				$var=true;
				print '<table class="nobordernopadding" width="100%">';
				if ($num)
				{
					$i = 0;
					while ($i < $num)
					{
						$objp = $db->fetch_object($result);

						$var=!$var;
						print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="action" value="addinfacture">';
						print "<tr ".$bc[$var].">";
						print "<td><a href=\"../compta/facture.php?facid=".$objp->factureid."\">$objp->facnumber</a></td>\n";
						print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,24)."</a></td>\n";
						print "<td colspan=\"2\">".$langs->trans("Qty");
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
				else
				{
					print "<tr ".$bc[!$var]."><td>";
					print $langs->trans("NoOtherDraftBills");
					print '</td></tr>';
				}
				print "</table>";
				$db->free($result);
			}
			else
			{
				dol_print_error($db);
			}

			print '</td>';
		}

		print '</tr>';
	}

	print '</table>';

	print '<br>';
}



$db->close();

llxFooter('$Date$ - $Revision$');

?>
