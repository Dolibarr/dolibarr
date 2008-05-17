<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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

// Code identique a /expedition/fiche.php

/**
   \file       htdocs/expedition/commande.php
   \ingroup    expedition
   \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");

$langs->load('orders');
$langs->load("companies");
$langs->load("bills");
$langs->load('propal');
$langs->load('deliveries');
$langs->load('stocks');

if (!$user->rights->commande->lire)
	accessforbidden();

// Sécurité accés client
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

// Chargement des permissions
$error = $user->load_entrepots();

/*
 * Actions
 */
if ($_POST["action"] == 'confirm_cloture' && $_POST["confirm"] == 'yes')
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $result = $commande->cloture($user);
}

$html = new Form($db);
$formfile = new FormFile($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

llxHeader('',$langs->trans("OrderCard"));

if ($_GET["id"] > 0)
{
	$commande = New Commande($db);
  if ( $commande->fetch($_GET["id"]) > 0)
  {
  	$commande->loadExpeditions(1);

    $soc = new Societe($db);
    $soc->fetch($commande->socid);

    $author = new User($db);
    $author->id = $commande->user_author_id;
    $author->fetch();

		$head = commande_prepare_head($commande);
    dolibarr_fiche_head($head, 'shipping', $langs->trans("CustomerOrder"));

    /*
     * Confirmation de la validation
     *
     */
    if ($_GET["action"] == 'cloture')
    {
    	$html->form_confirm("commande.php?id=".$_GET["id"],"Clôturer la commande","Etes-vous sûr de vouloir clôturer cette commande ?","confirm_cloture");
      print "<br />";
    }
    
    // Onglet commande
    $nbrow=8;
    if ($conf->projet->enabled) $nbrow++;
    
    print '<table class="border" width="100%">';
    
    // Ref
    print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
    print '<td colspan="3">'.$commande->ref.'</td>';
    print '</tr>';
    
    // Ref commande client
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
    print $langs->trans('RefCustomer').'</td><td align="left">';
    print '</td>';
    if ($_GET['action'] != 'RefCustomerOrder' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=RefCustomerOrder&amp;id='.$commande->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
    print '</tr></table>';
    print '</td><td colspan="3">';
    if ($user->rights->commande->creer && $_GET['action'] == 'RefCustomerOrder')
	  {
	  	print '<form action="fiche.php?id='.$id.'" method="post">';
	    print '<input type="hidden" name="action" value="set_ref_client">';
	    print '<input type="text" class="flat" size="20" name="ref_client" value="'.$commande->ref_client.'">';
	    print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
	    print '</form>';
	  }
	  else
	  {
	    print $commande->ref_client;
	  }
	  print '</td>';
	  print '</tr>';
	  
	  // Société
	  print '<tr><td>'.$langs->trans('Company').'</td>';
	  print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
	  print '</tr>';
	  
	  // Date
	  print '<tr><td>'.$langs->trans('Date').'</td>';
	  print '<td colspan="2">'.dolibarr_print_date($commande->date,'daytext').'</td>';
	  print '<td width="50%">'.$langs->trans('Source').' : '.$commande->getLabelSource();
	  if ($commande->source == 0)
	  {
	    // Si source = propal
	    $propal = new Propal($db);
	    $propal->fetch($commande->propale_id);
	    print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
	  }
	  print '</td>';
	  print '</tr>';
	  
	  // Date de livraison
	  print '<tr><td height="10">';
	  print '<table class="nobordernopadding" width="100%"><tr><td>';
	  print $langs->trans('DeliveryDate');
	  print '</td>';
	  
	  if ($_GET['action'] != 'editdate_livraison' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
	  print '</tr></table>';
	  print '</td><td colspan="2">';
	  if ($_GET['action'] == 'editdate_livraison')
	  {
	    print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'" method="post">';
	    print '<input type="hidden" name="action" value="setdate_livraison">';
	    $html->select_date($commande->date_livraison,'liv_','','','',"setdate_livraison");
	    print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
	    print '</form>';
	  }
	  else
	  {
	    print dolibarr_print_date($commande->date_livraison,'daytext');
	  }
	  print '</td>';
	  print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
	  print nl2br($commande->note_public);			
	  print '</td>';
	  print '</tr>';
	  
	  // Adresse de livraison
	  print '<tr><td height="10">';
	  print '<table class="nobordernopadding" width="100%"><tr><td>';
	  print $langs->trans('DeliveryAddress');
	  print '</td>';
	  
	  if ($_GET['action'] != 'editdelivery_adress' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$commande->socid.'&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
	  print '</tr></table>';
	  print '</td><td colspan="2">';
	  
	  if ($_GET['action'] == 'editdelivery_adress')
	  {
	    $html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'adresse_livraison_id','commande',$commande->id);
	  }
	  else
	  {
	    $html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'none','commande',$commande->id);
	  }
	  print '</td></tr>';
	  
	  // Conditions et modes de réglement
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';
					
		if ($_GET['action'] != 'editconditions' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($_GET['action'] == 'editconditions')
		{
			$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'cond_reglement_id');
		}
		else
		{
			$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'none');
		}
		print '</td></tr>';
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($_GET['action'] != 'editmode' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($_GET['action'] == 'editmode')
		{
			$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'mode_reglement_id');
		}
		else
		{
			$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'none');
		}
		print '</td></tr>';
		
		// Projet
		if ($conf->projet->enabled)
		{
			$langs->load('projects');
      print '<tr><td height="10">';
      print '<table class="nobordernopadding" width="100%"><tr><td>';
      print $langs->trans('Project');
      print '</td>';
      if ($_GET['action'] != 'classer' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
      print '</tr></table>';
      print '</td><td colspan="2">';
      if ($_GET['action'] == 'classer')
      {
      	$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'projetid');
      }
      else
      {
      	$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'none');
      }
      print '</td></tr>';
    }
    
		// Lignes de 3 colonnes

    // Total HT
		print '<tr><td>'.$langs->trans('AmountHT').'</td>';
		print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

		// Total TVA
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($commande->total_tva).'</td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
		
		// Total TTC
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($commande->total_ttc).'</td>';
		print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans('Status').'</td>';
		print '<td colspan="2">'.$commande->getLibStatut(4).'</td>';
		print '</tr>';
			
		print '</table><br>';
			

    /**
     *  Lignes de commandes avec quantité livrées et reste à livrer
     *  Les quantités livrées sont stockées dans $commande->expeditions[fk_product]
     */
    print '<table class="liste" width="100%">';

    $sql = "SELECT cd.fk_product, cd.description, cd.price, cd.qty, cd.rowid, cd.tva_tx, cd.subprice";
    $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd ";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
    $sql.= " WHERE cd.fk_commande = ".$commande->id;
    $sql.= " AND p.fk_product_type <> 1";
    $sql.= " ORDER BY cd.rowid";

    $resql = $db->query($sql);
    if ($resql)
    {
    	$num = $db->num_rows($resql);
      $i = 0;

      print '<tr class="liste_titre">';
      print '<td>'.$langs->trans("Description").'</td>';
      print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
      print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
      print '<td align="center">'.$langs->trans("KeepToShip").'</td>';
      if ($conf->stock->enabled)
      {
      	print '<td align="center">'.$langs->trans("Stock").'</td>';
      }
      else
      {
      	print '<td>&nbsp;</td>';	
      }
      print "</tr>\n";
      
      $var=true;
      $reste_a_livrer = array();
      while ($i < $num)
      {
      	$objp = $db->fetch_object($resql);
      	
      	$var=!$var;
      	print "<tr $bc[$var]>";
        if ($objp->fk_product > 0)
        {
        	$product = new Product($db);
          $product->fetch($objp->fk_product);
          print '<td>';
          print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
          print img_object($langs->trans("Product"),"product").' '.$product->ref.'</a>';
          print $product->libelle?' - '.$product->libelle:'';
          print '</td>';
        }
        else
        {
        	print "<td>".nl2br($objp->description)."</td>\n";
        }
        
        print '<td align="center">'.$objp->qty.'</td>';

        print '<td align="center">';
        $quantite_livree = $commande->expeditions[$objp->fk_product];
        print $quantite_livree;
        print '</td>';

        $reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
        $reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer[$objp->fk_product];
        print '<td align="center">';
        print $reste_a_livrer[$objp->fk_product];
        print '</td>';

        if ($conf->stock->enabled)
        {
        	print '<td align="center">';
          print $product->stock_reel;
          if ($product->stock_reel < $reste_a_livrer[$objp->fk_product])
          {
          	print ' '.img_warning($langs->trans("StockTooLow"));
          }
          print '</td>';
        }
        else
        {
        	print '<td>&nbsp;</td>';
        }
        print "</tr>";
        
        $i++;
        $var=!$var;
      }
      $db->free($resql);
      
      if (! $num)
      {
      	print '<tr '.$bc[false].'><td colspan="5">'.$langs->trans("NoArticleOfTypeProduct").'<br>';
      }
      
      print "</table>";
    }
    else
    {
    	dolibarr_print_error($db);
    }
    
    print '</div>';
        
        
    /*
     * Boutons Actions
     */
    
    if ($user->societe_id == 0)
    {
    	print '<div class="tabsAction">';
        	
      // Bouton expedier sans gestion des stocks
      if (! $conf->stock->enabled && $reste_a_livrer_total > 0 && ! $commande->brouillon && $user->rights->expedition->creer)
      {
      	print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/fiche.php?action=create&amp;origin=commande&amp;object_id='.$_GET["id"].'">'.$langs->trans("NewSending").'</a>';
      } 
      print "</div>";
    }
    
    
    print '<table width="100%"><tr><td width="50%" valign="top">';
    
    /*
     * Documents générés
     *
     */
    $comref = sanitize_string($commande->ref);
    $file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
    $relativepath = $comref.'/'.$comref.'.pdf';
    $filedir = $conf->commande->dir_output . '/' . $comref;
    $urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
    $genallowed=0;
    $delallowed=0;
    
    $somethingshown=$formfile->show_documents('commande',$comref,$filedir,$urlsource,$genallowed,$delallowed,$commande->modelpdf);
    
    print '</td><td valign="top" width="50%">';
    
    // Bouton expedier avec gestion des stocks
    if ($conf->stock->enabled && $reste_a_livrer_total > 0 && $commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer)
    {
    	print_titre($langs->trans("NewSending"));
            
    print '<form method="GET" action="'.DOL_URL_ROOT.'/expedition/fiche.php">';
    print '<input type="hidden" name="action" value="create">';
    print '<input type="hidden" name="id" value="'.$commande->id.'">';
    print '<input type="hidden" name="origin" value="commande">';
    print '<input type="hidden" name="object_id" value="'.$commande->id.'">';
    print '<table class="border" width="100%">';

    $entrepot = new Entrepot($db);
    $langs->load("stocks");
    
    print '<tr>';
    print '<td>'.$langs->trans("Warehouse").'</td>';
    print '<td>';
    
    if (sizeof($user->entrepots) === 1)
	  {
	  	$uentrepot = array();
	  	$uentrepot[$user->entrepots[0]['id']] = $user->entrepots[0]['label'];
	  	$html->select_array("entrepot_id",$uentrepot);
	  }
	  else
	  {
	  	$html->select_array("entrepot_id",$entrepot->list_array());
	  }
	  
	  if (sizeof($entrepot->list_array()) <= 0) 
    {
    	print ' &nbsp; Aucun entrepôt définit, <a href="'.DOL_URL_ROOT.'/product/stock/fiche.php?action=create">definissez en un</a>';
    }
    print '</td></tr>';
    /*
    print '<tr><td width="20%">Mode d\'expédition</td>';
    print '<td>';
    $html->select_array("entrepot_id",$entrepot->list_array());
    print '</td></tr>';
    */

    print '<tr><td align="center" colspan="2">';
    print '<input type="submit" class="button" named="save" value="'.$langs->trans("NewSending").'">';
    print '</td></tr>';

    print "</table>";
    print "</form>\n";
            
    $somethingshown=1;
  }
  
  print "</td></tr></table>";


        /*
         * 	Liste des expéditions
         */
        $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande";
        $sql .= " , ed.qty as qty_livre, e.ref, ed.fk_expedition as expedition_id";
        $sql .= ",".$db->pdate("e.date_expedition")." as date_expedition";
        if ($conf->livraison->enabled) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
        $sql .= " , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
        if ($conf->livraison->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid";
        $sql .= " WHERE cd.fk_commande = ".$commande->id;
        $sql .= " AND cd.rowid = ed.fk_origin_line";
        $sql .= " AND ed.fk_expedition = e.rowid";
        $sql .= " ORDER BY cd.fk_product";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
	        $i = 0;

            if ($num)
            {
                if ($somethingshown) print '<br>';
                
                print_titre($langs->trans("SendingsAndReceivingForSameOrder"));
                print '<table class="liste" width="100%">';
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Product").'</td>';
                print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
                print '<td align="center">'.$langs->trans("DateSending").'</td>';
                if ($conf->expedition->enabled)
                {
                	print '<td>'.$langs->trans("SendingSheet").'</td>';
                }
                if ($conf->livraison->enabled)
                {
                	print '<td>'.$langs->trans("DeliveryOrder").'</td>';
                }
                print "</tr>\n";

                $var=True;
                while ($i < $num)
                {
                    $var=!$var;
                    $objp = $db->fetch_object($resql);
                    print "<tr $bc[$var]>";
                    
                    if ($objp->fk_product > 0)
                    {
            	      $product = new Product($db);
            	      $product->fetch($objp->fk_product);
            	      
            	      print '<td>';
            	      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
            	      if ($objp->description) print '<br>'.nl2br($objp->description);
            	      print '</td>';
                    }
                    else
                    {
                        print "<td>".nl2br($objp->description)."</td>\n";
                    }
                    print '<td align="center">'.$objp->qty_livre.'</td>';
                    print '<td align="center">'.dolibarr_print_date($objp->date_expedition).'</td>';
                    if ($conf->expedition->enabled)
                    {
	                    print '<td align="left"><a href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';
                    }
                    if ($conf->livraison->enabled)
                    {
                    	if ($objp->livraison_id)
                    	{
                    		print '<td><a href="'.DOL_URL_ROOT.'/livraison/fiche.php?id='.$objp->livraison_id.'">'.img_object($langs->trans("ShowSending"),'generic').' '.$objp->livraison_ref.'<a></td>';
                    	}
                    	else
                    	{
                    		print '<td>&nbsp;</td>';
                    	}
                    }
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
    }
    else
    {
        /* Commande non trouvée */
        print "Commande inexistante";
    }
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
