<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Code identique a /expedition/shipment.php

/**
 *	\file       htdocs/expedition/fiche.php
 *	\ingroup    expedition
 *	\brief      Fiche descriptive d'une expedition
 *	\version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/sendings.lib.php");
if ($conf->product->enabled || $conf->service->enabled)  require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
if ($conf->stock->enabled)    require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");

$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
$langs->load('stocks');
$langs->load('other');
$langs->load('propal');

$origin = "expedition";
$origin_id = isset($_REQUEST["id"])?$_REQUEST["id"]:'';
$id = $origin_id;

$origin     = $_GET["origin"]?$_GET["origin"]:$_POST["origin"];				// Example: commande, propal
$origin_id  = $_GET["origin_id"]?$_GET["origin_id"]:$_POST["origin_id"];	// Id of order or propal


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,$origin,$origin_id,'');


/*
 * Actions
 */

if ($_POST["action"] == 'add')
{
	$db->begin();

	// Creation de l'objet expedition
	$expedition = new Expedition($db);

	$expedition->note				= $_POST["note"];
	$expedition->origin				= $origin;
	$expedition->origin_id			= $origin_id;
	$expedition->weight				= $_POST["weight"]==""?"NULL":$_POST["weight"];
	$expedition->sizeH				= $_POST["sizeH"]==""?"NULL":$_POST["sizeH"];
	$expedition->sizeW				= $_POST["sizeW"]==""?"NULL":$_POST["sizeW"];
	$expedition->sizeS				= $_POST["sizeS"]==""?"NULL":$_POST["sizeS"];
	$expedition->size_units			= $_POST["size_units"];
	$expedition->weight_units		= $_POST["weight_units"];

	// On va boucler sur chaque ligne du document d'origine pour completer objet expedition
	// avec info diverses + qte a livrer
	$classname = ucfirst($expedition->origin);
	$object = new $classname($db);
	$object->fetch($expedition->origin_id);
	//$object->fetch_lines();

	$expedition->socid					= $object->socid;
	$expedition->ref_customer			= $object->ref_client;
	$expedition->date_delivery			= $object->date_livraison;	// Date delivery planed
	$expedition->fk_delivery_address	= $object->fk_delivery_address;
	$expedition->expedition_method_id	= $_POST["expedition_method_id"];
	$expedition->tracking_number		= $_POST["tracking_number"];

	for ($i = 0 ; $i < sizeof($object->lignes) ; $i++)
	{
		$ent = "entl".$i;
		$idl = "idl".$i;
		$qty = "qtyl".$i;
		$entrepot_id = $_POST[$ent]?$_POST[$ent]:$_POST["entrepot_id"];
		if ($_POST[$qty] > 0)
		{
			$expedition->addline($entrepot_id,$_POST[$idl],$_POST[$qty]);
		}
	}

	$ret=$expedition->create($user);
	if ($ret > 0)
	{
		$db->commit();
		Header("Location: fiche.php?id=".$expedition->id);
		exit;
	}
	else
	{
		$db->rollback();
		$mesg='<div class="error">'.$expedition->error.'</div>';
		$_GET["commande_id"]=$_POST["commande_id"];
		$_GET["action"]='create';
	}
}

/*
 * Genere un bon de livraison
 */
if ($_GET["action"] == 'create_delivery' && $conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)
{
	$expedition = new Expedition($db);
	$expedition->fetch($_GET["id"]);
	$result = $expedition->create_delivery($user);
	if ($result > 0)
	{
		Header("Location: ".DOL_URL_ROOT.'/livraison/fiche.php?id='.$result);
		exit;
	}
	else
	{
		$mesg=$expedition->error;
	}
}

if ($_REQUEST["action"] == 'confirm_valid' && $_REQUEST["confirm"] == 'yes' && $user->rights->expedition->valider)
{
	$expedition = new Expedition($db);
	$expedition->fetch($_GET["id"]);
	$expedition->fetch_client();

	$result = $expedition->valid($user);

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$expedition->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=expedition_pdf_create($db,$expedition->id,$expedition->modelpdf,$outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}

if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == 'yes')
{
	if ($user->rights->expedition->supprimer )
	{
		$expedition = new Expedition($db);
		$expedition->fetch($_GET["id"]);
		$result = $expedition->delete();
		if ($result > 0)
		{
			Header("Location: liste.php");
			exit;
		}
		else
		{
			$mesg = $expedition->error;
		}
	}
}

if ($_REQUEST["action"] == 'open')
{
	if ($user->rights->expedition->valider )
	{
		$expedition = new Expedition($db);
		$expedition->fetch($_GET["id"]);
		$result = $expedition->setStatut(0);
		if ($result < 0)
		{
			$mesg = $expedition->error;
		}
	}
}

if ($_POST['action'] == 'setdate_livraison' && $user->rights->expedition->creer)
{
	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	$datelivraison=dol_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

	$shipping = new Expedition($db);
	$shipping->fetch($_GET['id']);
	$result=$shipping->set_date_livraison($user,$datelivraison);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$shipping->error.'</div>';
	}
}

// Action update description of emailing
if ($_REQUEST["action"] == 'settrackingnumber' || $_REQUEST["action"] == 'settrackingurl'
|| $_REQUEST["action"] == 'settrueWeight'
|| $_REQUEST["action"] == 'settrueWidth'
|| $_REQUEST["action"] == 'settrueHeight'
|| $_REQUEST["action"] == 'settrueDepth'
|| $_REQUEST["action"] == 'setexpedition_method_id')
{
	$error=0;

	$shipping = new Expedition($db);
	$result=$shipping->fetch($_REQUEST['id']);
	if ($result < 0) dol_print_error($db,$shipping->error);

	if ($_REQUEST["action"] == 'settrackingnumber')  $shipping->tracking_number = trim($_REQUEST["trackingnumber"]);
	if ($_REQUEST["action"] == 'settrackingurl')     $shipping->tracking_url = trim($_REQUEST["trackingurl"]);
	if ($_REQUEST["action"] == 'settrueWeight')      $shipping->trueWeight = trim($_REQUEST["trueWeight"]);
	if ($_REQUEST["action"] == 'settrueWidth')       $shipping->trueWidth = trim($_REQUEST["trueWidth"]);
	if ($_REQUEST["action"] == 'settrueHeight')      $shipping->trueHeight = trim($_REQUEST["trueHeight"]);
	if ($_REQUEST["action"] == 'settrueDepth')       $shipping->trueDepth = trim($_REQUEST["trueDepth"]);
	if ($_REQUEST["action"] == 'setexpedition_method_id')       $shipping->expedition_method_id = trim($_REQUEST["expedition_method_id"]);

	if (! $error)
	{
		if ($shipping->update($user) >= 0)
		{
			Header("Location: fiche.php?id=".$shipping->id);
			exit;
		}
		$mesg=$shipping->error;
	}

	$mesg='<div class="error">'.$mesg.'</div>';
	$_GET["action"]="";
	$_GET["id"]=$_REQUEST["id"];
}


/*
 * Build doc
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	require_once(DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php");

	// Sauvegarde le dernier modele choisi pour generer un document
	$shipment = new Expedition($db, 0, $_REQUEST['id']);
	$shipment->fetch($_REQUEST['id']);
	$shipment->fetch_client();

	if ($_REQUEST['model'])
	{
		$shipment->setDocModel($user, $_REQUEST['model']);
	}

	// Define output language
	$outputlangs = $langs;
	$newlang='';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$shipment->client->default_lang;
	if (! empty($newlang))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($newlang);
	}
	$result=expedition_pdf_create($db,$_REQUEST['id'],$_REQUEST['model'],$outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
}


/*
 * View
 */

llxHeader('',$langs->trans('Sending'),'Expedition');

$html = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);

/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET["action"] == 'create')
{
	$expe = new Expedition($db);

	print_fiche_titre($langs->trans("CreateASending"));
	if (! $origin)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorBadParameters").'</div>';
	}

	if ($mesg)
	{
		print $mesg.'<br>';
	}

	if ($origin)
	{
		$classname = ucfirst($origin);

		$object = new $classname($db);

		if ($object->fetch($origin_id))
		{
			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$author = new User($db);
			$author->fetch($object->user_author_id);

			if ($conf->stock->enabled) $entrepot = new Entrepot($db);

			/*
			 *   Document source
			 */
			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
			if ($_GET["entrepot_id"])
			{
				print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
			}

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">';
			if ($origin == 'commande' && $conf->commande->enabled)
			{
				print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$object->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$object->ref;
			}
			if ($origin == 'propal' && $conf->propal->enabled)
			{
				print $langs->trans("RefProposal").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/comm/fiche.php?propalid='.$object->id.'">'.img_object($langs->trans("ShowProposal"),'propal').' '.$object->ref;
			}
			print '</a></td>';
			print "</tr>\n";

			// Ref client
			print '<tr><td>';
			print $langs->trans('RefCustomer').'</td><td colspan="3">';
			print $object->ref_client;
			print '</td>';
			print '</tr>';

			// Tiers
			print '<tr><td>'.$langs->trans('Company').'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print '</tr>';

			// Date delivery planned
			print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
			print '<td colspan="3">';
			print dol_print_date($object->date_livraison,"day");
			//$html->select_date($object->date_livraison,'date_delivery');
			print "</td>\n";
			print '</tr>';

			// Delivery address
			if (($origin == 'commande' && $conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
				|| ($origin == 'propal' && $conf->global->PROPAL_ADD_DELIVERY_ADDRESS))
			{
				print '<tr><td>'.$langs->trans('DeliveryAddress').'</td>';
				print '<td colspan="3">';
				if (!empty($object->fk_delivery_address))
				{
					$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$_GET['socid'],'none','commande',$object->id);
				}
				print '</td></tr>'."\n";
			}

			// Warehouse (id forced)
			if ($conf->stock->enabled && $_GET["entrepot_id"])
			{
				print '<tr><td>'.$langs->trans("Warehouse").'</td>';
				print '<td colspan="3">';
				$ents = $entrepot->list_array();
				print '<a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?id='.$_GET["entrepot_id"].'">'.img_object($langs->trans("ShowWarehouse"),'stock').' '.$ents[$_GET["entrepot_id"]].'</a>';
				print '</td></tr>';
			}

			if ($object->note && ! $user->societe_id)
			{
				print '<tr><td colspan="3">'.$langs->trans("NotePrivate").': '.nl2br($object->note)."</td></tr>";
			}

			print '<tr><td>';
			print $langs->trans("Weight");
			print '</td><td><input name="weight" size="4" value="'.$_POST["weight"].'"></td><td>';
			print $formproduct->select_measuring_units("weight_units","weight",$_POST["weight_units"]);
			print '</td></tr><tr><td>';
			print $langs->trans("Width");
			print ' </td><td><input name="sizeW" size="4" value="'.$_POST["sizeW"].'"></td><td rowspan="3">';
			print $formproduct->select_measuring_units("size_units","size");
			print '</td></tr><tr><td>';
			print $langs->trans("Height");
			print '</td><td><input name="sizeH" size="4" value="'.$_POST["sizeH"].'"></td>';
			print '</tr><tr><td>';
			print $langs->trans("Depth");
			print '</td><td><input name="sizeS" size="4" value="'.$_POST["sizeS"].'"></td>';
			print '</tr>';

			// Delivery method
			print "<tr><td>".$langs->trans("DeliveryMethod")."</td>";
			print '<td colspan="3">';
			$expe->fetch_delivery_methods();
			$html->select_array("expedition_method_id",$expe->meths,$_POST["expedition_method_id"],1,0,0,0,"",1);
			print "</td></tr>\n";

			// Tracking number
			print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
			print '<td colspan="3">';
			print '<input name="tracking_number" size="20" value="'.$_POST["tracking_number"].'">';
			print "</td></tr>\n";

			print "</table>";

			/*
			 * Lignes de commandes
			 *
			 */
			print '<br><table class="nobordernopadding" width="100%">';

			//$lignes = $object->fetch_lines(1);
			$numAsked = sizeof($object->lignes);

			/* Lecture des expeditions deja effectuees */
			$object->loadExpeditions();

			if ($numAsked)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
				print '<td align="center">'.$langs->trans("QtyToShip").'</td>';
				if ($conf->stock->enabled)
				{
					if ($_GET["entrepot_id"])
					{
						print '<td align="right">'.$langs->trans("Stock").'</td>';
					}
					else
					{
						print '<td align="left">'.$langs->trans("Warehouse").'</td>';
					}
				}
				print "</tr>\n";
			}

			$var=true;
			$indiceAsked = 0;
			while ($indiceAsked < $numAsked)
			{
				$product = new Product($db);

				$line = $object->lines[$indiceAsked];
				$var=!$var;
				print "<tr ".$bc[$var].">\n";

				// Desc
				if ($line->fk_product > 0)
				{
					$product->fetch($line->fk_product);

					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product.'">';
					if ($line->product_type == 1)
					{
						print img_object($langs->trans("ShowService"),"service");
					}
					else
					{
						print img_object($langs->trans("ShowProduct"),"product");
					}
					print ' '.$product->ref.'</a> - '.$product->libelle;
					if ($line->desc) print '<br>'.dol_nl2br(dol_htmlcleanlastbr($line->desc),1);
					print '</td>';
				}
				else
				{	//var_dump($ligne);
					print "<td>".nl2br($line->desc)."</td>\n";
				}

				// Qty
				print '<td align="center">'.$line->qty.'</td>';
				$qtyProdCom=$line->qty;

				// Sendings
				print '<td align="center">';
				$quantityDelivered = $object->expeditions[$line->id];
				print $quantityDelivered;
				print '</td>';

				$quantityAsked = $line->qty;
				$quantityToBeDelivered = $quantityAsked - $quantityDelivered;

				if ($conf->stock->enabled && $line->product_type == 0)
				{
					$defaultqty=0;
					if ($_GET["entrepot_id"])
					{
						$stock = $product->stock_entrepot[$_GET["entrepot_id"]];
						$stock+=0;  // Convertit en numerique
						$defaultqty=min($quantityToBeDelivered, $stock);
						if ($defaultqty < 0) $defaultqty=0;
					}

					// Quantity
					print '<td align="center">';
					print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
					print '<input name="qtyl'.$indiceAsked.'" type="text" size="6" value="'.$defaultqty.'">';
					print '</td>';

					// Stock
					if ($_GET["entrepot_id"])
					{
						print '<td align="right">';
						print $stock;
						if ($stock < $quantityToBeDelivered)
						{
							print ' '.img_warning($langs->trans("StockTooLow"));
						}
						print '</td>';
					}
					else
					{
						$array=array();

						$sql = "SELECT e.rowid, e.label, ps.reel";
						$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
						$sql.= ", ".MAIN_DB_PREFIX."entrepot as e";
						$sql.= " WHERE ps.fk_entrepot = e.rowid";
						$sql.= " AND fk_product = '".$product->id."'";

						$result = $db->query($sql) ;
						if ($result)
						{
							$num = $db->num_rows($result);
							$i=0;
							if ($num > 0)
							{
								while ($i < $num)
								{
									$obj = $db->fetch_object($result);
									$array[$obj->rowid] = $obj->label.' ('.$obj->reel.')';
									$i++;
								}
							}
							$db->free($result);
						}
						else
						{
							$this->error=$db->error();
							return -1;
						}

						print '<td align="left">';
						$html->select_array('entl'.$i,$array,'',1,0,0);
						print '</td>';
					}

				}
				else
				{
					// Quantity
					print '<td align="center" '.$colspan.'>';
					print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
					print '<input name="qtyl'.$indiceAsked.'" type="text" size="6" value="'.$quantityToBeDelivered.'">';
					print '</td>';
					if ($line->product_type == 1) print '<td>&nbsp;</td>';
				}

				print "</tr>\n";

				// Show subproducts of product
				if (! empty($conf->global->PRODUIT_SOUSPRODUITS) && $line->fk_product > 0)
				{
					$product->get_sousproduits_arbo ();
					$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
					if(sizeof($prods_arbo) > 0)
					{
						foreach($prods_arbo as $key => $value)
						{
							//print $value[0];
							$img='';
							if ($value['stock'] < $value['stock_alert'])
							{
								$img=img_warning($langs->trans("StockTooLow"));
							}
							print "<tr><td>&nbsp; &nbsp; &nbsp; ->
                                <a href=\"".DOL_URL_ROOT."/product/fiche.php?id=".$value['id']."\">".$value['fullpath']."
                                </a> (".$value['nb'].")</td><td align=\"center\"> ".$value['nb_total']."</td><td>&nbsp</td><td>&nbsp</td>
                                <td align=\"center\">".$value['stock']." ".$img."</td></tr>";
						}
					}
				}

				$indiceAsked++;
			}

			print '<tr><td align="center" colspan="5"><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
			print "</table>";
			print '</form>';
		}
		else
		{
			dol_print_error($db);
		}
	}
}
else
/* *************************************************************************** */
/*                                                                             */
/* Edit and view mode                                                          */
/*                                                                             */
/* *************************************************************************** */
{
	if (! empty($_REQUEST["id"]) || ! empty($_REQUEST["ref"]))
	{
		$expedition = new Expedition($db);
		$result = $expedition->fetch($_REQUEST["id"],$_REQUEST["ref"]);
		if ($result < 0)
		{
			dol_print_error($db,$expedition->error);
			exit -1;
		}
		$lignes = $expedition->lignes;
		$num_prod = sizeof($lignes);

		if ($expedition->id > 0)
		{
			if ($mesg)
			{
				print '<div class="error">'.$mesg.'</div>';
			}

			if (!empty($expedition->origin))
			{
				$typeobject = $expedition->origin;
				$origin = $expedition->origin;
				$expedition->fetch_object();
			}

			if (strlen($expedition->tracking_number))
			{
				$expedition->GetUrlTrackingStatus();
			}

			$soc = new Societe($db);
			$soc->fetch($expedition->socid);

			// delivery link
			$expedition->load_object_linked($expedition->id,$expedition->element,-1,-1);

			$head=shipping_prepare_head($expedition);
			dol_fiche_head($head, 'shipping', $langs->trans("Sending"), 0, 'sending');

			if ($mesg) print $mesg;

			/*
			 * Confirmation de la suppression
			 *
			 */
			if ($_GET["action"] == 'delete')
			{
				$ret=$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$expedition->id,$langs->trans('DeleteSending'),$langs->trans("ConfirmDeleteSending",$expedition->ref),'confirm_delete','',0,1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la validation
			 *
			 */
			if ($_GET["action"] == 'valid')
			{
				$ret=$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$expedition->id,$langs->trans('ValidateSending'),$langs->trans("ConfirmValidateSending",$expedition->ref),'confirm_valid','',0,1);
				if ($ret == 'html') print '<br>';
			}
			/*
			 * Confirmation de l'annulation
			 *
			 */
			if ($_GET["action"] == 'annuler')
			{
				$ret=$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$expedition->id,$langs->trans('CancelSending'),$langs->trans("ConfirmCancelSending",$expedition->ref),'confirm_cancel','',0,1);
				if ($ret == 'html') print '<br>';
			}

			// Calcul du poids total et du volume total des produits
			$totalWeight = '';
			$totalVolume = '';
			for ($i = 0 ; $i < $num_prod ; $i++)
			{
				$weightUnit=0;
				$volumeUnit=0;
				if (! empty($lignes[$i]->weight_units)) $weightUnit = $lignes[$i]->weight_units;
				$trueWeightUnit=pow(10,$weightUnit);
				$totalWeight += $lignes[$i]->weight*$lignes[$i]->qty_shipped*$trueWeightUnit;
				if (! empty($lignes[$i]->volume_units)) $volumeUnit = $lignes[$i]->volume_units;
				$trueVolumeUnit=pow(10,$volumeUnit);
				$totalVolume += $lignes[$i]->volume*$lignes[$i]->qty_shipped*$trueVolumeUnit;
			}
			$totalVolume=$totalVolume;

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">'.$expedition->ref.'</td></tr>';

			// Customer
			print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print "</tr>";

			// Linked documents
			if ($typeobject == 'commande' && $expedition->$typeobject->id && $conf->commande->enabled)
			{
				print '<tr><td>';
				$object=new Commande($db);
				$object->fetch($expedition->$typeobject->id);
				print $langs->trans("RefOrder").'</td>';
				print '<td colspan="3">';
				print $object->getNomUrl(1,'commande');
				print "</td>\n";
				print '</tr>';
			}
			if ($typeobject == 'propal' && $expedition->$typeobject->id && $conf->propal->enabled)
			{
				print '<tr><td>';
				$object=new Propal($db);
				$object->fetch($expedition->$typeobject->id);
				print $langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $object->getNomUrl(1,'expedition');
				print "</td>\n";
				print '</tr>';
			}

			// Ref customer
			print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
			print '<td colspan="3">'.$expedition->ref_customer."</a></td>\n";
			print '</tr>';

			// Date creation
			print '<tr><td>'.$langs->trans("DateCreation").'</td>';
			print '<td colspan="3">'.dol_print_date($expedition->date_creation,"daytext")."</td>\n";
			print '</tr>';

			// Delivery date planed
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateDeliveryPlanned');
			print '</td>';

			if ($_GET['action'] != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$expedition->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editdate_livraison')
			{
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$expedition->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$html->select_date($expedition->date_delivery?$expedition->date_delivery:-1,'liv_','','','',"setdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $expedition->date_delivery ? dol_print_date($expedition->date_delivery,'daytext') : '&nbsp;';
			}
			print '</td>';
			print '</tr>';

			// Delivery address
			if (($origin == 'commande' && $conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
				|| ($origin == 'propal' && $conf->global->PROPAL_ADD_DELIVERY_ADDRESS))
			{
				print '<tr><td>'.$langs->trans('DeliveryAddress').'</td>';
				print '<td colspan="3">';
				if (!empty($expedition->fk_delivery_address))
				{
					$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$expedition->id,$expedition->fk_delivery_address,$expedition->deliveryaddress->socid,'none','shipment',$expedition->id);
				}
				print '</td></tr>'."\n";
			}

			// Weight
			print '<tr><td>'.$html->editfieldkey("TotalWeight",'trueWeight',$expedition->trueWeight,'id',$expedition->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $html->editfieldval("TotalWeight",'trueWeight',$expedition->trueWeight,'id',$expedition->id,$user->rights->expedition->creer);
			print $expedition->weight_units?measuring_units_string($expedition->weight_units,"weight"):'';
			print '</td></tr>';

			// Volume Total
			print '<tr><td>'.$langs->trans("TotalVolume").'</td>';
			print '<td colspan="3">';
			if ($expedition->trueVolume)
			{
				// If sending volume defined
				print $expedition->trueVolume.' '.measuring_units_string($expedition->volumeUnit,"volume");
			}
			else
			{
				// If sending volume not defined we use sum of products
				// TODO Show in best unit
				if ($totalVolume > 0) print $totalVolume.' '.measuring_units_string(0,"volume");
				else print '&nbsp;';
			}
			print "</td>\n";
			print '</tr>';

			// Width
			print '<tr><td>'.$html->editfieldkey("Width",'trueWidth',$expedition->trueWidth,'id',$expedition->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $html->editfieldval("Width",'trueWidth',$expedition->trueWidth,'id',$expedition->id,$user->rights->expedition->creer);
			print $expedition->trueWidth?measuring_units_string($expedition->width_units,"size"):'';
			print '</td></tr>';

			// Height
			print '<tr><td>'.$html->editfieldkey("Height",'trueHeight',$expedition->trueHeight,'id',$expedition->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $html->editfieldval("Height",'trueHeight',$expedition->trueHeight,'id',$expedition->id,$user->rights->expedition->creer);
			print $expedition->trueHeight?measuring_units_string($expedition->height_units,"size"):'';
			print '</td></tr>';

			// Depth
			print '<tr><td>'.$html->editfieldkey("Depth",'trueDepth',$expedition->trueDepth,'id',$expedition->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $html->editfieldval("Depth",'trueDepth',$expedition->trueDepth,'id',$expedition->id,$user->rights->expedition->creer);
			print $expedition->trueDepth?measuring_units_string($expedition->depth_units,"size"):'';
			print '</td></tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$expedition->getLibStatut(4)."</td>\n";
			print '</tr>';

			// Sending method
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('SendingMethod');
			print '</td>';

			if ($_GET['action'] != 'editexpedition_method_id') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editexpedition_method_id&amp;id='.$expedition->id.'">'.img_edit($langs->trans('SetSendingMethod'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editexpedition_method_id')
			{
				print '<form name="setexpedition_method_id" action="'.$_SERVER["PHP_SELF"].'?id='.$expedition->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setexpedition_method_id">';
				$expedition->fetch_delivery_methods();
				$html->select_array("expedition_method_id",$expedition->meths,$expedition->expedition_method_id,1,0,0,0,"",1);
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				if ($expedition->expedition_method_id > 0)
				{
					// Get code using getLabelFromKey
					$code=$langs->getLabelFromKey($db,$expedition->expedition_method_id,'expedition_methode','rowid','code');
					print $langs->trans("SendingMethod".strtoupper($code));
				}
			}
			print '</td>';
			print '</tr>';

			// Tracking Number
			print '<tr><td>'.$html->editfieldkey("TrackingNumber",'trackingnumber',$expedition->tracking_number,'id',$expedition->id,$user->rights->expedition->creer).'</td><td colspan="3">';
			print $html->editfieldval("TrackingNumber",'trackingnumber',$expedition->tracking_number,'id',$expedition->id,$user->rights->expedition->creer);
			print '</td></tr>';

			if ($expedition->tracking_url)
			{
				print '<tr><td>'.$html->editfieldkey("TrackingUrl",'trackingurl',$expedition->tracking_url,'id',$expedition->id,$user->rights->expedition->creer).'</td><td colspan="3">';
				print $html->editfieldval("TrackingUrl",'trackingurl',$expedition->tracking_url,'id',$expedition->id,$user->rights->expedition->creer);
				print '</td></tr>';
			}

			print "</table>\n";

			/*
			 * Lignes produits
			 */
			print '<br><table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Products").'</td>';
			print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
			if ($expedition->fk_statut <= 1)
			{
				print '<td align="center">'.$langs->trans("QtyToShip").'</td>';
			}
			else
			{
				print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			}

			print '<td align="center">'.$langs->trans("Weight").'</td>';
			print '<td align="center">'.$langs->trans("Volume").'</td>';

			if ($conf->stock->enabled)
			{
				print '<td align="left">'.$langs->trans("WarehouseSource").'</td>';
			}
			print "</tr>\n";

			$var=false;

			for ($i = 0 ; $i < $num_prod ; $i++)
			{
				print "<tr ".$bc[$var].">";

				// Product
				if ($lignes[$i]->fk_product > 0)
				{
					print '<td>';

					// Affiche ligne produit
					$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$lignes[$i]->fk_product.'">';
					if ($lignes[$i]->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$lignes[$i]->ref.'</a>';
					$text.= ' - '.$lignes[$i]->label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($lignes[$i]->description));
					//print $description;
					print $html->textwithtooltip($text,$description,3,'','',$i);
					print_date_range($lignes[$i]->date_start,$lignes[$i]->date_end);
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print ($lignes[$i]->description && $lignes[$i]->description!=$lignes[$i]->product)?'<br>'.dol_htmlentitiesbr($lignes[$i]->description):'';
					}
				}
				else
				{
					print "<td>";
					if ($lignes[$i]->fk_product_type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($lignes[$i]->description);
					print_date_range($lignes[$i]->date_start,$lignes[$i]->date_end);
					print "</td>\n";
				}

				// Qte commande
				print '<td align="center">'.$lignes[$i]->qty_asked.'</td>';

				// Qte a expedier ou expedier
				print '<td align="center">'.$lignes[$i]->qty_shipped.'</td>';

				// Poids
				print '<td align="center">'.$lignes[$i]->weight*$lignes[$i]->qty_shipped.' '.measuring_units_string($lignes[$i]->weight_units,"weight").'</td>';

				// Volume
				print '<td align="center">'.$lignes[$i]->volume*$lignes[$i]->qty_shipped.' '.measuring_units_string($lignes[$i]->volume_units,"volume").'</td>';

				// Entrepot source
				if ($conf->stock->enabled)
				{
					print '<td align="left">';
					if ($lignes[$i]->entrepot_id > 0)
					{
						$entrepot = new Entrepot($db);
						$entrepot->fetch($lignes[$i]->entrepot_id);
						print $entrepot->getNomUrl(1);
					}
					print '</td>';
				}


				print "</tr>";

				$var=!$var;
			}
		}

		print "</table>\n";

		print "\n</div>\n";


		/*
		 *    Boutons actions
		 */

		if ($user->societe_id == 0)
		{
			print '<div class="tabsAction">';

			/*if ($expedition->statut > 0 && $user->rights->expedition->valider)
			{
				print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=open">'.$langs->trans("Modify").'</a>';
			}*/

			if ($expedition->statut == 0 && $num_prod > 0)
			{
				if ($user->rights->expedition->valider)
				{
					print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
				}
				else
				{
					print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Validate").'</a>';
				}
			}

			if ($conf->livraison_bon->enabled && $expedition->statut == 1 && $user->rights->expedition->livraison->creer && empty($expedition->linked_object))
			{
				print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=create_delivery">'.$langs->trans("DeliveryOrder").'</a>';
			}

			if ($user->rights->expedition->supprimer)
			{
				print '<a class="butActionDelete" href="fiche.php?id='.$expedition->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
			}

			print '</div>';
		}
		print "\n";

		print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";


		/*
		 * Documents generated
		 */
		if ($conf->expedition_bon->enabled)
		{
			$expeditionref = dol_sanitizeFileName($expedition->ref);
			$filedir = $conf->expedition->dir_output . "/sending/" .$expeditionref;

			$urlsource = $_SERVER["PHP_SELF"]."?id=".$expedition->id;

			$genallowed=$user->rights->expedition->lire;
			$delallowed=$user->rights->expedition->supprimer;
			//$genallowed=1;
			//$delallowed=0;

			$somethingshown=$formfile->show_documents('expedition',$expeditionref,$filedir,$urlsource,$genallowed,$delallowed,$expedition->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);
			if ($genallowed && ! $somethingshown) $somethingshown=1;
		}

		print '</td><td valign="top" width="50%">';

		// Rien a droite

		print '</td></tr></table>';

		if (!empty($origin) && $expedition->$origin->id)
		{
			print '<br>';
			//show_list_sending_receive($expedition->origin,$expedition->origin_id," AND e.rowid <> ".$expedition->id);
			show_list_sending_receive($expedition->origin,$expedition->origin_id);
		}

	}
	else
	{
		print "Expedition inexistante ou acces refuse";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
