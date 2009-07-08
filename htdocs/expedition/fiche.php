<?php
/* Copyright (C) 2003-2008 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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

// Code identique a /expedition/commande.php

/**
 *	\file       htdocs/expedition/fiche.php
 *	\ingroup    expedition
 *	\brief      Fiche descriptive d'une expedition
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/sendings.lib.php");
if ($conf->produit->enabled || $conf->service->enabled)  require_once(DOL_DOCUMENT_ROOT."/product.class.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
if ($conf->stock->enabled)    require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
$langs->load('stocks');
$langs->load('other');
$langs->load('propal');

$origin = "expedition";
$origin_id = isset($_GET["id"])?$_GET["id"]:'';
$id = $origin_id;

$origin     = $_GET["origin"]?$_GET["origin"]:$_POST["origin"];				// Example: commande, propal
$origin_id  = $_GET["object_id"]?$_GET["object_id"]:$_POST["object_id"];	// Id of order or propal


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

	$expedition->date_expedition  = time();
	$expedition->note             = $_POST["note"];
	$expedition->origin           = $origin;
	$expedition->origin_id        = $origin_id;
	$expedition->weight			= $_POST["weight"]==""?"NULL":$_POST["weight"];
	$expedition->sizeH			= $_POST["sizeH"]==""?"NULL":$_POST["sizeH"];
	$expedition->sizeW			= $_POST["sizeW"]==""?"NULL":$_POST["sizeW"];
	$expedition->sizeS			= $_POST["sizeS"]==""?"NULL":$_POST["sizeS"];
	$expedition->size_units		= $_POST["size_units"];
	$expedition->weight_units	= $_POST["weight_units"];

	// On boucle sur chaque ligne du document d'origine pour completer objet expedition
	// avec qte a livrer
	$class = ucfirst($expedition->origin);
	$object = new $class($db);
	$object->fetch($expedition->origin_id);
	//$object->fetch_lines();

	$expedition->socid  = $object->socid;
	$expedition->fk_delivery_address = $object->fk_delivery_address;
	$expedition->expedition_method_id = $_POST["expedition_method_id"];
	$expedition->tracking_number = $_POST["tracking_number"];

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
	$result = $expedition->valid($user);
	//$expedition->PdfWrite();
}

if ($_REQUEST["action"] == 'confirm_delete' && $_REQUEST["confirm"] == 'yes')
{
	if ($user->rights->expedition->supprimer )
	{
		$expedition = new Expedition($db);
		$expedition->fetch($_GET["id"]);
		$expedition->delete();
		Header("Location: liste.php");
		exit;
	}
}

/*
 * Build doc
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	require_once(DOL_DOCUMENT_ROOT."/includes/modules/expedition/pdf/ModelePdfExpedition.class.php");

	// Sauvegarde le dernier modele choisi pour generer un document
	$expedition = new Expedition($db, 0, $_REQUEST['id']);
	$expedition->fetch($_REQUEST['id']);

	if ($_REQUEST['model'])
	{
		$expedition->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=expedition_pdf_create($db,$expedition->id,$expedition->modelpdf,$outputlangs);
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
		$class = ucfirst($origin);

		$object = new $class($db);

		if ($object->fetch($origin_id))
		{
			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$author = new User($db);
			$author->id = $object->user_author_id;
			$author->fetch();

			if ($conf->stock->enabled) $entrepot = new Entrepot($db);

			/*
			 *   Document source
			 */
			print '<form action="fiche.php" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="object_id" value="'.$object->id.'">';
			if ($_GET["entrepot_id"])
			{
				print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
			}

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="30%">';
			if ($conf->commande->enabled)
			{
				print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$object->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$object->ref;
			}
			else
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

			// Date
			print "<tr><td>".$langs->trans("Date")."</td>";
			print '<td colspan="3">'.dol_print_date($object->date,"day")."</td></tr>\n";

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
			print '</td><td><input name="weight" size="4" value=""></td><td>';
			print $formproduct->select_measuring_units("weight_units","weight");
			print '</td></tr><tr><td>';
			print $langs->trans("Width");
			print ' </td><td><input name="sizeW" size="4" value=""></td>';
			print '<td>&nbsp;</td></tr><tr><td>';
			print $langs->trans("Height");
			print '</td><td><input name="sizeH" size="4" value=""></td><td>';
			print $formproduct->select_measuring_units("size_units","size");
			print '</td></tr><tr><td>';
			print $langs->trans("Depth");
			print '</td><td><input name="sizeS" size="4" value=""></td>';
			print '<td>&nbsp;</td></tr>';

			// Delivery method
			print "<tr><td>".$langs->trans("DeliveryMethod")."</td>";
			print '<td colspan="3">';
			$expe->fetch_delivery_methods();
			$html->select_array("expedition_method_id",$expe->meths,'',1,0,0,0,"",1);
			print "</td></tr>\n";

			// Tracking number
			print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
			print '<td colspan="3">';
			print '<input name="tracking_number" size="20">';
			print "</td></tr>\n";

			print "</table>";

			/*
			 * Lignes de commandes
			 *
			 */
			print '<br><table class="noborder" width="100%">';

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

				$ligne = $object->lignes[$indiceAsked];
				$var=!$var;
				print "<tr ".$bc[$var].">\n";

				// Desc
				if ($ligne->fk_product > 0)
				{
					$product->fetch($ligne->fk_product);

					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$ligne->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
					if ($ligne->desc) print '<br>'.dol_nl2br(dol_htmlcleanlastbr($ligne->desc),1);
					print '</td>';
				}
				else
				{	//var_dump($ligne);
					print "<td>".nl2br($ligne->desc)."</td>\n";
				}

				// Qty
				print '<td align="center">'.$ligne->qty.'</td>';
				$qtyProdCom=$ligne->qty;

				// Sendings
				print '<td align="center">';
				$quantityDelivered = $object->expeditions[$ligne->id];
				print $quantityDelivered;
				print '</td>';

				$quantityAsked = $ligne->qty;
				$quantityToBeDelivered = $quantityAsked - $quantityDelivered;

				if ($conf->stock->enabled)
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
					print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$ligne->id.'">';
					print '<input name="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$defaultqty.'">';
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
						$sql.= " FROM ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."entrepot as e";
						$sql.= " WHERE ps.fk_entrepot = e.rowid AND fk_product = '".$product->id."'";
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
					print '<td align="center">';
					print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$ligne->id.'">';
					print '<input name="qtyl'.$indiceAsked.'" type="text" size="6" value="'.$quantityToBeDelivered.'">';
					print '</td>';
				}

				print "</tr>\n";

				// Show subproducts of product
				if (! empty($conf->global->PRODUIT_SOUSPRODUITS) && $ligne->fk_product > 0)
				{
					$product->get_sousproduits_arbo ();
					$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
					if(sizeof($prods_arbo) > 0)
					{
						foreach($prods_arbo as $key => $value)
						{
							print $value[0];
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
	if ($_GET["id"] > 0)
	{
		$expedition = new Expedition($db);
		$result = $expedition->fetch($_GET["id"]);
		if ($result < 0)
		{
			dol_print_error($db,$expedition->error);
			exit -1;
		}
		$lignes = $expedition->lignes;
		$num_prod = sizeof($lignes);

		if ($expedition->id > 0)
		{
			$typeobject = $expedition->origin;
			$expedition->fetch_object();

			if (strlen($expedition->tracking_number))
			{
				$expedition->GetUrlTrackingStatus();
			}

			$soc = new Societe($db);
			$soc->fetch($expedition->socid);

			$h=0;
			$head[$h][0] = DOL_URL_ROOT."/expedition/fiche.php?id=".$expedition->id;
			$head[$h][1] = $langs->trans("SendingCard");
			$hselected = $h;
			$h++;

			if ($conf->livraison_bon->enabled && $expedition->livraison_id)
			{
				$head[$h][0] = DOL_URL_ROOT."/livraison/fiche.php?id=".$expedition->livraison_id;
				$head[$h][1] = $langs->trans("DeliveryCard");
				$h++;
			}

			dol_fiche_head($head, $hselected, $langs->trans("Sending"));

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
			print '<tr><td>';
			if ($conf->commande->enabled)
			{
				$order=new Commande($db);
				$order->fetch($expedition->$typeobject->id);
				print $langs->trans("RefOrder").'</td>';
				print '<td colspan="3">';
				print $order->getNomUrl(1,'commande');
				print "</td>\n";
			}
			else
			{
				$propal=new Propal($db);
				$propal->fetch($livraison->origin_id);
				print $langs->trans("RefProposal").'</td>';
				print '<td colspan="3">';
				print $propal->getNomUrl(1,'expedition');
				print "</td>\n";
			}
			print '</tr>';

			// Ref customer
			print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
			print '<td colspan="3">'.$object->ref_client."</a></td>\n";
			print '</tr>';

			// Date
			print '<tr><td>'.$langs->trans("Date").'</td>';
			print '<td colspan="3">'.dol_print_date($expedition->date,"daytext")."</td>\n";
			print '</tr>';

			// Weight
			print '<tr><td>'.$langs->trans("TotalWeight").'</td>';
			print '<td colspan="3">';
			if ($expedition->trueWeight)
			{
				// If sending weigth defined
				print $expedition->trueWeight.' '.measuring_units_string($expedition->weight_units,"weight");
			}
			else
			{
				// If sending Weight not defined we use sum of products
				// TODO Show in best unit
				if ($totalWeight > 0) print $totalWeight.' '.measuring_units_string(0,"weight");
				else print '&nbsp;';
			}
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

			// Taille
			print '<tr><td>'.$langs->trans("Size").'</td>';
			print '<td colspan="3">';
			if ($expedition->trueWidth || $expedition->trueHeight || $expedition->trueDepth)
			{
				// If sending size defined
				print $expedition->trueSize.' '.measuring_units_string($expedition->size_units,"size");
			}
			else print '&nbsp;';
			print "</td>\n";
			print '</tr>';

			// Status
			print '<tr><td>'.$langs->trans("Status").'</td>';
			print '<td colspan="3">'.$expedition->getLibStatut(4)."</td>\n";
			print '</tr>';

			// Sending method
			print '<tr><td>'.$langs->trans("SendingMethod").'</td>';
			print '<td colspan="3">';
			if ($expedition->expedition_method_id > 0)
			{
				// Get code using getLabelFromKey
				$code=$langs->getLabelFromKey($db,$expedition->expedition_method_id,'expedition_methode','rowid','code');
				print $langs->trans("SendingMethod".strtoupper($code));
			}
			else print '&nbsp;';
			print '</td>';
			print '</tr>';

			// Tracking Number
			print '<tr><td>'.$langs->trans("TrackingNumber").'</td>';
			print '<td>'.$expedition->tracking_number.'</td>';
			if ($expedition->tracking_url)
			{
				print '<td colspan="2">'.$expedition->tracking_url."</td>\n";
			}
			print '</tr>';

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
					$entrepot = new Entrepot($db);
					$entrepot->fetch($lignes[$i]->entrepot_id);
					print '<td align="left">'.$entrepot->getNomUrl(1).'</td>';
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

			if ($conf->livraison_bon->enabled && $expedition->statut == 1 && $user->rights->expedition->livraison->creer && !$expedition->livraison_id)
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

			$somethingshown=$formfile->show_documents('expedition',$expeditionref,$filedir,$urlsource,$genallowed,$delallowed,$expedition->modelpdf);
			if ($genallowed && ! $somethingshown) $somethingshown=1;
		}

		print '</td><td valign="top" width="50%">';

		// Rien a droite

		print '</td></tr></table>';

		print '<br>';
		//show_list_sending_receive($expedition->origin,$expedition->origin_id," AND e.rowid <> ".$expedition->id);
		show_list_sending_receive($expedition->origin,$expedition->origin_id);

	}
	else
	{
		print "Expedition inexistante ou acces refuse";
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
