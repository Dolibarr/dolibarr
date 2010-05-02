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
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

$langs->load("bills");
$langs->load("other");
$langs->load("stocks");
$langs->load("products");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
$socid=$user->societe_id?$user->societe_id:0;
$result=restrictedArea($user,'produit|service',$id,'product','','',$fieldid);

if (empty($_GET["canvas"]))
{
	$_GET["canvas"] = 'default';
	if ($_GET["type"] == 1) $_GET["canvas"] = 'service';
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

	if (!empty($_POST["canvas"]) && file_exists('canvas/'.$_POST["canvas"].'/product.'.$_POST["canvas"].'.class.php') )
	{
		$classname = 'Product'.ucfirst($_POST["canvas"]);
		include_once('canvas/'.$_POST["canvas"].'/product.'.$_POST["canvas"].'.class.php');
		$product = new $classname($db);
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans('ErrorCanvasNotDefined').'</div>';
		$_GET["action"] = "create";
		$_GET["canvas"] = $_POST["canvas"];
		$_GET["type"] 	= $_POST["type"];
		$error++;
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

		if ( $value != $current_lang ) $e_product = $product;

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
		if (!empty($_POST["canvas"]) && file_exists('canvas/'.$product->canvas.'/product.'.$product->canvas.'.class.php'))
		{
			$classname = 'Product'.ucfirst($product->canvas);
			include_once('canvas/'.$product->canvas.'/product.'.$product->canvas.'.class.php');
			$product = new $classname($db);
		}

		if ($product->fetch($_POST["id"]))
		{
			$product->ref                = $_POST["ref"];
			$product->libelle            = $_POST["libelle"];
			$product->description        = dol_htmlcleanlastbr($_POST["desc"]);
			$product->note               = dol_htmlcleanlastbr($_POST["note"]);
			$product->status             = $_POST["statut"];
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
	$propal = New Propal($db);
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
	$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

	$price_base_type = 'HT';

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
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
	$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
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
	$tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);

	// multiprix
	if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
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
 * Fiche creation du produit
 */
if ($_GET["action"] == 'create' && ($user->rights->produit->creer || $user->rights->service->creer))
{
	if (!empty($_GET["canvas"]) && file_exists(DOL_DOCUMENT_ROOT.'/product/canvas/'.$_GET["canvas"].'/product.'.$_GET["canvas"].'.class.php'))
	{
		$helpurl='';
		if (isset($_GET["type"]) && $_GET["type"] == 0) $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
		if (isset($_GET["type"]) && $_GET["type"] == 1)	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
		
		llxHeader("",$helpurl,$langs->trans("CardProduct".$product->type));
		
		if (! isset($product))
		{
			$filecanvas = DOL_DOCUMENT_ROOT.'/product/canvas/'.$_GET["canvas"].'/product.'.$_GET["canvas"].'.class.php';
			$classname = 'Product'.ucfirst($_GET["canvas"]);

			include_once($filecanvas);
			$product = new $classname($db,0,$user);
			
			$template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$_GET["canvas"].'/tpl/';
			
			if ($product->smarty)
			{
				$product->assign_smarty_values($smarty, 'create');
				$smarty->template_dir = $template_dir;
				
				//$tvaarray = load_tva($db,"tva_tx",$conf->defaulttx,$mysoc,'');
				//$smarty->assign('tva_taux_value', $tvaarray['value']);
				//$smarty->assign('tva_taux_libelle', $tvaarray['label']);
				
				$smarty->display($_GET["canvas"].'-create.tpl');
			}
			else
			{
				$product->assign_values('create');
				include($template_dir.'create.tpl.php');
			}
		}

		if ($_error == 1)
		{
			$product = $e_product;
		}
	}
}

/**
 *
 * Fiche produit
 *
 */
if ($_GET["id"] || $_GET["ref"])
{
	$productstatic = new Product($db);
	$result = $productstatic->getCanvas($_GET["id"],$_GET["ref"]);

	// Gestion des produits specifiques
	if (!empty($productstatic->canvas) && file_exists(DOL_DOCUMENT_ROOT.'/product/canvas/'.$productstatic->canvas.'/product.'.$productstatic->canvas.'.class.php') )
	{
		$classname = 'Product'.ucfirst($productstatic->canvas);
		include_once(DOL_DOCUMENT_ROOT.'/product/canvas/'.$productstatic->canvas.'/product.'.$productstatic->canvas.'.class.php');
		$product = new $classname($db);

		$result = $product->fetch($productstatic->id,'',$_GET["action"]);

		$template_dir = DOL_DOCUMENT_ROOT.'/product/canvas/'.$product->canvas.'/tpl/';
	}

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ( $result )
	{
		if ($_GET["action"] <> 'edit')
		{
			$head=product_prepare_head($product, $user);
			$titre=$langs->trans("CardProduct".$product->type);
			$picto=($product->type==1?'service':'product');
			dol_fiche_head($head, 'card', $titre, 0, $picto);
			print "\n<!-- CUT HERE -->\n";

			// Confirmation de la suppression de la facture
			if ($_GET["action"] == 'delete')
			{
				$ret=$html->form_confirm("fiche.php?id=".$product->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete",'',0,2);
				if ($ret == 'html') print '<br>';
			}

			print($mesg);

			$product->assign_values('view');

			include($template_dir.'view.tpl.php');
		}
	}

	/*
	 * Fiche en mode edition
	 */
	if ($_GET["action"] == 'edit' && ($user->rights->produit->creer || $user->rights->service->creer))
	{
		if ($mesg) {
			print '<br><div class="error">'.$mesg.'</div><br>';
		}

		$product->assign_values('edit');

		include($template_dir.'edit.tpl.php');
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
		print '<a class="butAction" href="fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Modify").'</a>';

		if ($product->no_button_copy <> 1)
		print '<a class="butAction" href="fiche.php?action=clone&amp;id='.$product->id.'">'.$langs->trans("ToClone").'</a>';
	}

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


/*
 * All the "Add to" areas
 */

if ($product->id && $_GET["action"] == '' && $product->status)
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
					print '<form method="POST" action="fiche.php?id='.$product->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="addinpropal">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print "<a href=\"../comm/propal.php?propalid=".$objp->propalid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a></td>\n";
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
			$otherprop = $propal->liste_array(1, ' <> '.$user->id);
			print '<form method="POST" action="fiche.php?id='.$product->id.'">';
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
					print '<form method="POST" action="fiche.php?id='.$product->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="addincommande">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print "<a href=\"../commande/fiche.php?id=".$objc->commandeid."\">".img_object($langs->trans("ShowOrder"),"order")." ".$objc->ref."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=".$objc->socid."\">".dol_trunc($objc->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dol_print_date($objc->dc,"%d %b")."</td>\n";
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
			print '<form method="POST" action="fiche.php?id='.$product->id.'">';
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
					print '<form method="POST" action="fiche.php?id='.$product->id.'">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="action" value="addinfacture">';
					print "<tr $bc[$var]>";
					print "<td nowrap>";
					print "<a href=\"../compta/facture.php?facid=".$objp->factureid."\">".img_object($langs->trans("ShowBills"),"bill")." ".$objp->facnumber."</a></td>\n";
					print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,18)."</a></td>\n";
					print "<td nowrap=\"nowrap\">".dol_print_date($objp->df,"%d %b")."</td>\n";
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
			print '<td width="50%" valign="top">';

			// Liste de Autres factures
			$var=true;

			$sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber,".$db->pdate("f.datef")." as df";
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
				if ($num) {
					$i = 0;
					while ($i < $num)
					{
						$objp = $db->fetch_object($result);

						$var=!$var;
						print '<form method="POST" action="fiche.php?id='.$product->id.'">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="action" value="addinfacture">';
						print "<tr $bc[$var]>";
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
				dol_print_error($db);
			}
		}

		print '</td></tr>';
	}

	print '</table>';

	print '<br>';
}



$db->close();

llxFooter('$Date$ - $Revision$');

?>
