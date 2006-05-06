<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
 * $Source$
 */

// Code identique a /expedition/fiche.php

/**
        \file       htdocs/expedition/commande.php
        \ingroup    expedition
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/stock/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");

$langs->load("bills");

$user->getrights('commande');
$user->getrights('expedition');
if (!$user->rights->commande->lire)
	accessforbidden();


// Sécurité accés client
if ($user->societe_id > 0)
{
    $action = '';
    $socidp = $user->societe_id;
}


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
        $commande->livraison_array(1);

        $soc = new Societe($db);
        $soc->fetch($commande->soc_id);
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
            if ($_GET['action'] != 'refcdeclient') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refcdeclient&amp;id='.$commande->id.'">'.img_edit($langs->trans('Edit')).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
			if ($user->rights->commande->creer && $_GET['action'] == 'refcdeclient')
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
			print '<td colspan="2">';
			print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
			print '</tr>';

			print '<tr><td>'.$langs->trans('Date').'</td>';
			print '<td colspan="2">'.dolibarr_print_date($commande->date,'%A %d %B %Y').'</td>';
			print '<td width="50%">'.$langs->trans('Source').' : ' . $commande->sources[$commande->source] ;
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
			print $langs->trans('DateDelivery');
			print '</td>';
					
			if ($_GET['action'] != 'editdate_livraison' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDateDelivery'),1).'</a></td>';
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
				print dolibarr_print_date($commande->date_livraison,'%A %d %B %Y');
			}
			print '</td>';
			print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
			if ($commande->brouillon == 1 && $user->rights->commande->creer)
			{
				print '<form action="fiche.php?id='.$id.'" method="post">';
				print '<input type="hidden" name="action" value="setnote">';
				print '<textarea name="note" rows="4" style="width:95%;">'.$commande->note.'</textarea><br>';
				print '<center><input type="submit" class="button" value="'.$langs->trans('Save').'"></center>';
				print '</form>';
			}
			else
			{
				print nl2br($commande->note);
			}
			
			print '</td>';
			print '</tr>';
			
			
			// Adresse de livraison
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DeliveryAddress');
			print '</td>';
					
			if ($_GET['action'] != 'editdelivery_adress' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$commande->soc_id.'&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
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
			print $langs->trans('PaymentConditions');
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
                if ($_GET['action'] != 'classer') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="2">';
                if ($_GET['action'] == 'classer')
                {
                    $html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->soc_id, $commande->projet_id, 'projetid');
                }
                else
                {
                    $html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->soc_id, $commande->projet_id, 'none');
                }
                print '</td></tr>';
            }

			// Lignes de 3 colonnes

            // Total HT
			print '<tr><td>'.$langs->trans('TotalHT').'</td>';
			print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Total TVA
			print '<tr><td>'.$langs->trans('TotalVAT').'</td><td align="right">'.price($commande->total_tva).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';
			
			// Total TTC
			print '<tr><td>'.$langs->trans('TotalTTC').'</td><td align="right">'.price($commande->total_ttc).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans('Status').'</td>';
			print '<td colspan="2">'.$commande->getLibStatut(4).'</td>';
			print '</tr>';
			
			print '</table><br>';
			

        /**
         *  Lignes de commandes avec quantité livrées et reste à livrer
         *
         */
        echo '<table class="liste" width="100%">';

        $sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice";
        $sql.= " FROM ".MAIN_DB_PREFIX."commandedet as l ";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
        $sql.= " WHERE l.fk_commande = ".$commande->id;
        $sql.= " AND p.fk_product_type <> 1";
        $sql.= " ORDER BY l.rowid";

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
                    print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
                }

                print '<td align="center">'.$objp->qty.'</td>';

                print '<td align="center">';
                $quantite_livree = $commande->livraisons[$objp->fk_product];
                print $quantite_livree;
                print '</td>';

                $reste_a_livrer[$objp->fk_product] = $objp->qty - $quantite_livree;
                $reste_a_livrer_x = $objp->qty - $quantite_livree;
                $reste_a_livrer_total = $reste_a_livrer_total + $reste_a_livrer_x;
                print '<td align="center">';
                print $reste_a_livrer[$objp->fk_product];
                print '</td>';

                if ($conf->stock->enabled)
                {
                    if ($product->stock_reel < $reste_a_livrer_x)
                    {
                        print '<td align="center" class="alerte">'.$product->stock_reel.'</td>';
                    }
                    else
                    {
                        print '<td align="center">'.$product->stock_reel.'</td>';
                    }
                }
                else
                {
                	print '<td>&nbsp;</td>';
                }
                print "</tr>";

                $i++;
                $var=!$var;
            }
            $db->free();
            
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
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/fiche.php?action=create&amp;commande_id='.$_GET["id"].'">'.$langs->trans("NewSending").'</a>';
            }

            print "</div>";

        }

		// Bouton expedier avec gestion des stocks
        if ($conf->stock->enabled && $reste_a_livrer_total > 0 && ! $commande->brouillon && $user->rights->expedition->creer)
        {

            print '<form method="GET" action="'.DOL_URL_ROOT.'/expedition/fiche.php">';
            print '<input type="hidden" name="action" value="create">';
            print '<input type="hidden" name="id" value="'.$commande->id.'">';
            print '<input type="hidden" name="commande_id" value="'.$commande->id.'">';
            print '<table class="border" width="100%">';

            $entrepot = new Entrepot($db);
            $langs->load("stocks");

            print '<tr>';
            print '<td>'.$langs->trans("Warehouse").'</td>';
            print '<td>';
            $html->select_array("entrepot_id",$entrepot->list_array());
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

            print "</table><br>";
            print "</form>\n";
        }


        /*
         * Alerte de seuil
         */
        if ($reste_a_livrer_total > 0 && $conf->stock->enabled)
        {
            print '<br><table class="liste" width="100%">';
            foreach ($reste_a_livrer as $key => $value)
            {
                if ($value > 0)
                {
                    $sql = "SELECT e.label as entrepot, ps.reel, p.label ";
                    $sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e, ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."product as p";
                    $sql .= " WHERE e.rowid = ps.fk_entrepot AND ps.fk_product = p.rowid AND ps.fk_product = $key";
                    $sql .= " AND e.statut = 1 AND reel < $value";

                    $resql = $db->query($sql);
                    if ($resql)
                    {
                        $num = $db->num_rows($resql);
                        $i = 0;

                        $var=True;
                        while ($i < $num)
                        {
                            $obja = $db->fetch_object($resql);
                            print "<tr $bc[$var]>";
                            print '<td width="54%">'.$obja->label.'</td><td>'.$obja->entrepot.'</td><td><b>Stock : '.$obja->reel.'</b></td>';
                            print "</tr>\n";
                            $i++;
                        }
                        $db->free($resql);
                    }
                    else {
                        dolibarr_print_error($db);
                    }

                }
            }
            print "</table>";
        }

        /*
         * Déjà livré
         */
        $sql = "SELECT cd.fk_product, cd.description, cd.rowid, cd.qty as qty_commande";
        $sql .= " , ed.qty as qty_livre, e.ref, ed.fk_expedition as expedition_id";
        $sql .= ",".$db->pdate("e.date_expedition")." as date_expedition";
        $sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
        $sql .= " , ".MAIN_DB_PREFIX."expeditiondet as ed, ".MAIN_DB_PREFIX."expedition as e";
        $sql .= " WHERE cd.fk_commande = ".$commande->id;
        $sql .= " AND cd.rowid = ed.fk_commande_ligne";
        $sql .= " AND ed.fk_expedition = e.rowid";
        $sql .= " AND e.fk_statut > 0";
        $sql .= " ORDER BY cd.fk_product";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
	        $i = 0;

            if ($num)
            {
                print '<br>';
                
                print_titre($langs->trans("OtherSendingsForSameOrder"));
                print '<table class="liste" width="100%">';
                print '<tr class="liste_titre">';
                print '<td align="left">'.$langs->trans("Sending").'</td>';
                print '<td>'.$langs->trans("Description").'</td>';
                print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
                print '<td align="center">'.$langs->trans("Date").'</td>';
                print "</tr>\n";

                $var=True;
                while ($i < $num)
                {
                    $var=!$var;
					$objp = $db->fetch_object($resql);
                    print "<tr $bc[$var]>";
                    print '<td align="left"><a href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$objp->expedition_id.'">'.img_object($langs->trans("ShowSending"),'sending').' '.$objp->ref.'<a></td>';
                    if ($objp->fk_product > 0)
                    {
            	      $product = new Product($db);
            	      $product->fetch($objp->fk_product);
            	      
            	      print '<td>';
            	      print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),"product").' '.$product->ref.'</a> - '.$product->libelle;
            	      if ($objp->description) print nl2br($objp->description);
            	      print '</td>';
                    }
                    else
                    {
                        print "<td>".stripslashes(nl2br($objp->description))."</td>\n";
                    }
                    print '<td align="center">'.$objp->qty_livre.'</td>';
                    print '<td align="center">'.dolibarr_print_date($objp->date_expedition).'</td>';
                    $i++;
                }

                print '</table>';
            }
	      $db->free($resql);
        }
        else {
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
