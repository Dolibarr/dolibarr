<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2008 Régis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 */

// Code identique a /expedition/commande.php

/**
        \file       htdocs/expedition/fiche.php
        \ingroup    expedition
        \brief      Fiche descriptive d'une expedition
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/expedition/mods/pdf/ModelePdfExpedition.class.php");
if ($conf->produit->enabled) require_once(DOL_DOCUMENT_ROOT."/product.class.php");
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
if ($conf->stock->enabled) require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load('deliveries');
$langs->load('orders');
$langs->load('stocks');
$langs->load('other');

$user->getrights('expedition');
if (!$user->rights->expedition->lire)
  accessforbidden();


// Sécurité accés client
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$origin     = $_GET["origin"]?$_GET["origin"]:$_POST["origin"];
$origin_id  = $_GET["origin_id"]?$_GET["origin_id"]:$_POST["origin_id"];

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

  // On boucle sur chaque ligne du document d'origine pour compléter objet expedition
  // avec qté à livrer
  $class = ucfirst($expedition->origin);
  $object = new $class($db);
  $object->fetch($expedition->origin_id);
  //$object->fetch_lines();
  
  $expedition->socid  = $object->socid;
  
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
 * Génère un bon de livraison
 */
if ($_GET["action"] == 'create_delivery' && $conf->livraison->enabled && $user->rights->expedition->livraison->creer)
{
  $expedition = new Expedition($db);
  $expedition->fetch($_GET["id"]);
  $result = $expedition->create_delivery($user);
  Header("Location: ".DOL_URL_ROOT.'/livraison/fiche.php?id='.$result);
}

if ($_POST["action"] == 'confirm_valid' && $_POST["confirm"] == 'yes' && $user->rights->expedition->valider)
{
  $expedition = new Expedition($db);
  $expedition->fetch($_GET["id"]);
  $result = $expedition->valid($user);
  //$expedition->PdfWrite();
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == 'yes')
{
  if ($user->rights->expedition->supprimer )
    {
      $expedition = new Expedition($db);
      $expedition->fetch($_GET["id"]);
      $expedition->delete();
      Header("Location: liste.php");
    }
}

/*
 * Générer ou regénérer le PDF
 */
if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
	// Sauvegarde le dernier modèle choisi pour générer un document
	$expedition = new Expedition($db, 0, $_REQUEST['id']);
	$expedition->fetch($_REQUEST['id']);

	if ($_REQUEST['model'])
	{
		$expedition->set_pdf_model($user, $_REQUEST['model']);
	}

	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=expedition_pdf_create($db,$expedition->id,$expedition->modelpdf,$outputlangs);
    if ($result <= 0)
    {
    	dolibarr_print_error($db,$result);
        exit;
    }
}


/*
 *
 */

llxHeader('',$langs->trans('Sending'),'Expedition');

$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET["action"] == 'create')
{

	print_titre($langs->trans("CreateASending"));

	if ($mesg)
	{
		print $mesg.'<br>';
	}

  $class            = ucfirst($origin);

	$object = new $class($db);
	$object->loadExpeditions();

	if ( $object->fetch($origin_id))
	{
		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$author = new User($db);
		$author->id = $object->user_author_id;
		$author->fetch();

		$entrepot = new Entrepot($db);

		/*
		 *   Document source
		 */
		print '<form action="fiche.php" method="post">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
		if ($_GET["entrepot_id"])
		{
			print '<input type="hidden" name="entrepot_id" value="'.$_GET["entrepot_id"].'">';
		}

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td>';
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
		print '<td colspan="3">'.dolibarr_print_date($object->date,"day")."</td></tr>\n";

		// Entrepot (si forcé)
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

		print "</table>";

		/*
		* Lignes de commandes
		*
		*/
		print '<br><table class="noborder" width="100%">';

		//$lignes = $object->fetch_lines(1);
		$numAsked = sizeof($object->lignes);

		/* Lecture des expeditions déjà effectuées */
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
			$ligne = $object->lignes[$indiceAsked];
			$var=!$var;
			print "<tr $bc[$var]>\n";
			if ($ligne->fk_product > 0)
			{
				$product = new Product($db);
				$product->fetch($ligne->fk_product);

				print '<td>';
				print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$ligne->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
				if ($ligne->description) print nl2br($ligne->description);
				print '</td>';
			}
			else
			{
				print "<td>".nl2br($ligne->description)."</td>\n";
			}

			print '<td align="center">'.$ligne->qty.'</td>';

			print '<td align="center">';
			$quantityDelivered = $object->expeditions[$ligne->fk_product];
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
					$stock+=0;  // Convertit en numérique
					$defaultqty=min($quantityToBeDelivered, $stock);
				}

				// Quantité à livrer
				print '<td align="center">';
				print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$ligne->id.'">';
				print '<input name="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$defaultqty.'">';
				print '</td>';

				// Stock
				if ($_GET["entrepot_id"])
				{
					print '<td align="right">'.$stock;
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
				// Quantité à livrer
				print '<td align="center">';
				print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$ligne->id.'">';
				print '<input name="qtyl'.$indiceAsked.'" type="text" size="6" value="'.$quantityToBeDelivered.'">';
				print '</td>';
			}

			print "</tr>\n";

			$indiceAsked++;
			$var=!$var;
		}

		/*
		*
		*/

		print '<tr><td align="center" colspan="5"><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
		print "</table>";
		print '</form>';
	}
	else
	{
		dolibarr_print_error($db);
	}
}
else
/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
{
	if ($_GET["id"] > 0)
  {
  	$expedition = New Expedition($db);
    $result = $expedition->fetch($_GET["id"]);
    $lignes = $expedition->lignes;
    $num_prod = sizeof($lignes);

    if ($expedition->id > 0)
    {
    	$object = New Commande($db);
      $object->fetch($expedition->origin_id);

      $soc = new Societe($db);
      $soc->fetch($expedition->socid);

      $h=0;
      $head[$h][0] = DOL_URL_ROOT."/expedition/fiche.php?id=".$expedition->id;
      $head[$h][1] = $langs->trans("SendingCard");
      $hselected = $h;
      $h++;

      if ($conf->livraison->enabled && $expedition->livraison_id)
      {
      	$head[$h][0] = DOL_URL_ROOT."/livraison/fiche.php?id=".$expedition->livraison_id;
        $head[$h][1] = $langs->trans("DeliveryCard");
        $h++;
      }

      dolibarr_fiche_head($head, $hselected, $langs->trans("Sending"));

            /*
            * Confirmation de la suppression
            *
            */
            if ($_GET["action"] == 'delete')
            {
                $html->form_confirm("fiche.php?id=$expedition->id",$langs->trans("DeleteSending"),"Etes-vous sûr de vouloir supprimer cette expedition ?","confirm_delete");
                print '<br>';
            }

            /*
            * Confirmation de la validation
            *
            */
            if ($_GET["action"] == 'valid')
            {
                $html->form_confirm("fiche.php?id=$expedition->id",$langs->trans("ValidateSending"),"Etes-vous sûr de vouloir valider cette expédition ?","confirm_valid");
                print '<br>';
            }
            /*
            * Confirmation de l'annulation
            *
            */
            if ($_GET["action"] == 'annuler')
            {
                $html->form_confirm("fiche.php?id=$expedition->id",$langs->trans("CancelSending"),"Etes-vous sûr de vouloir annuler cette commande ?","confirm_cancel");
                print '<br>';
            }
            
            // calcul du poids total et du volume total des produits
            //TODO: ajouter conversion pour le poids et le volume et selection de l'unité de mesure la plus utilisée
            $totalWeight = '';
            $totalVolume = '';
            for ($i = 0 ; $i < $num_prod ; $i++)
            {
            	$totalWeight += $lignes[$i]->weight*$lignes[$i]->qty_shipped;
            	$weightUnit = $lignes[$i]->weight_units;
            	$totalVolume += $lignes[$i]->volume*$lignes[$i]->qty_shipped;
            	$volumeUnit = $lignes[$i]->volume_units;
            }

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
            print '<td colspan="3">'.$expedition->ref.'</td></tr>';

            // Client
            print '<tr><td width="20%">'.$langs->trans("Customer").'</td>';
            print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
            print "</tr>";

            // Document liée
            print '<tr><td>';
            if ($conf->commande->enabled)
            {
            	print $langs->trans("RefOrder").'</td>';
            	print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$object->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$object->ref;
            }
            else
            {
            	print $langs->trans("RefProposal").'</td>';
            	print '<td colspan="3"><a href="'.DOL_URL_ROOT.'/expedition/propal.php?propalid='.$object->id.'">'.img_object($langs->trans("ShowProposal"),'propal').' '.$object->ref;
            }
            print "</a></td>\n";
            print '</tr>';

            // Ref client
            print '<tr><td>'.$langs->trans("RefCustomer").'</td>';
            print '<td colspan="3">'.$object->ref_client."</a></td>\n";
            print '</tr>';

            // Date
            print '<tr><td>'.$langs->trans("Date").'</td>';
            print '<td colspan="3">'.dolibarr_print_date($expedition->date,"day")."</td>\n";
   			    print '</tr>';
   			    
   			    // Poids Total
            print '<tr><td>'.$langs->trans("TotalWeight").'</td>';
            print '<td colspan="3">'.$totalWeight.' '.measuring_units_string($weightUnit,"weight")."</td>\n";
   			    print '</tr>';
   			    
   			    // Volume Total
            print '<tr><td>'.$langs->trans("TotalVolume").'</td>';
            print '<td colspan="3">'.$totalVolume.' '.measuring_units_string($volumeUnit,"volume")."</td>\n";
   			    print '</tr>';

            // Statut
            print '<tr><td>'.$langs->trans("Status").'</td>';
            print '<td colspan="3">'.$expedition->getLibStatut(4)."</td>\n";
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

            $var=true;
            
            for ($i = 0 ; $i < $num_prod ; $i++)
            {
              print "<tr $bc[$var]>";
              if ($lignes[$i]->fk_product > 0)
              {
              	print '<td>';
                print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$lignes[$i]->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$lignes[$i]->ref.'</a> - '.$lignes[$i]->libelle;
                if ($lignes[$i]->description) print '<br>'.nl2br($lignes[$i]->description);
                print '</td>';
              }
              else
              {
                print "<td>".nl2br($lignes[$i]->description)."</td>\n";
              }
                
              // Qte commandé
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

                if (! eregi('^(valid|delete)',$_REQUEST["action"]))
                {
	                if ($expedition->statut == 0 && $user->rights->expedition->valider && $num_prod > 0)
	                {
	                    print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
	                }
	
	                if ($conf->livraison->enabled && $expedition->statut == 1 && $user->rights->expedition->livraison->creer && !$expedition->livraison_id)
	                {
	                    print '<a class="butAction" href="fiche.php?id='.$expedition->id.'&amp;action=create_delivery">'.$langs->trans("DeliveryOrder").'</a>';
	                }
	
	                if ($expedition->brouillon && $user->rights->expedition->supprimer)
	                {
	                    print '<a class="butActionDelete" href="fiche.php?id='.$expedition->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
	                }
				}
				
                print '</div>';
            }
			print "\n";

            print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";

            /*
             * Documents générés
             */

            $expeditionref = sanitize_string($expedition->ref);
            $filedir = $conf->expedition->dir_output . "/" .$expeditionref;

            $urlsource = $_SERVER["PHP_SELF"]."?id=".$expedition->id;

            $genallowed=$user->rights->expedition->lire && ($expedition->statut > 0);
            $delallowed=$user->rights->expedition->supprimer;
            //$genallowed=1;
            //$delallowed=0;

            $somethingshown=$html->show_documents('expedition',$expeditionref,$filedir,$urlsource,$genallowed,$delallowed,$expedition->modelpdf);
			if ($genallowed && ! $somethingshown) $somethingshown=1;
			
            /*
             * Autres expeditions
             */
            $sql = "SELECT obj.fk_product, obj.description, obj.rowid, obj.qty as qty_asking";
            $sql.= ", ed.qty as qty_shipped, ed.fk_expedition as expedition_id";
            $sql.= ", e.ref, ".$db->pdate("e.date_expedition")." as date_expedition";
           	$sql.= " FROM ".MAIN_DB_PREFIX.$expedition->origin."det as obj";
            $sql.= " , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
            $sql.= " WHERE obj.fk_".$expedition->origin." = ".$expedition->origin_id;
            $sql.= " AND e.rowid <> ".$expedition->id;
            $sql.= " AND obj.rowid = ed.fk_origin_line";
            $sql.= " AND ed.fk_expedition = e.rowid";
            $sql.= " ORDER BY obj.fk_product";

            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $i = 0;

                if ($num)
                {
                    if ($somethingshown) print '<br>';

                    print_titre($langs->trans("OtherSendingsForSameOrder"));
                    print '<table class="liste" width="100%">';
                    print '<tr class="liste_titre">';
                    print '<td align="left">'.$langs->trans("Ref").'</td>';
                    print '<td>'.$langs->trans("Description").'</td>';
                    print '<td align="center">'.$langs->trans("Qty").'</td>';
                    print '<td align="center">'.$langs->trans("Date").'</td>';
                    print "</tr>\n";

                    $var=True;
                    while ($i < $num)
                    {
                        $var=!$var;
                        $objp = $db->fetch_object($resql);
                        print "<tr $bc[$var]>";
                        print '<td align="left" nowrap="nowrap"><a href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';
                        if ($objp->fk_product > 0)
                        {
                            $product = new Product($db);
                            $product->fetch($objp->fk_product);

                            print '<td>';
                            print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.dolibarr_trunc($product->libelle,20);
                            if ($objp->description) print nl2br(dolibarr_trunc($objp->description,24));
                            print '</td>';
                        }
                        else
                        {
                            print "<td>".nl2br(dolibarr_trunc($objp->description,24))."</td>\n";
                        }
                        print '<td align="center">'.$objp->qty_shipped.'</td>';
                        print '<td align="center" nowrap="nowrap">'.dolibarr_print_date($objp->date_expedition).'</td>';
                        print '</tr>';
                        $i++;
                    }

                    print '</table>';
                }
                $db->free($resql);
            }
            else
            {
              dolibarr_print_error($db);
            }


            print '</td><td valign="top" width="50%">';

			// Rien a droite

            print '</td></tr></table>';

    }
    else
    {
        print "Expedition inexistante ou accés refusé";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
