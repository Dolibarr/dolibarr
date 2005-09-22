<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 
/**
       \file       htdocs/compta/fiche.php
       \ingroup    commande
       \brief      Fiche commande
       \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("orders");
$langs->load("sendings");
$langs->load("companies");
$langs->load("bills");
$langs->load("propal");

$user->getrights('facture');

if (! $user->rights->commande->lire) accessforbidden();

require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}
/*
 *
 */	

if ($_GET["action"] == 'facturee') 
{
  $commande = new Commande($db);
  $commande->fetch($_GET["id"]);
  $commande->classer_facturee();
}


llxHeader('',$langs->trans("OrderCard"),"Commande");



$html = new Form($db);

/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */
  
if ($_GET["id"] > 0)
{
    $commande = new Commande($db);
    if ( $commande->fetch($_GET["id"]) > 0)
    {
        $soc = new Societe($db);
        $soc->fetch($commande->soc_id);
        $author = new User($db);
        $author->id = $commande->user_author_id;
        $author->fetch();

        $h=0;

        if ($conf->commande->enabled && $user->rights->commande->lire)
        {
            $head[$h][0] = DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;
            $head[$h][1] = $langs->trans("OrderCard");
            $h++;
        }

        if ($conf->expedition->enabled && $user->rights->expedition->lire)
        {
            $head[$h][0] = DOL_URL_ROOT.'/expedition/commande.php?id='.$commande->id;
            $head[$h][1] = $langs->trans("SendingCard");
            $h++;
        }

        if ($conf->compta->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT.'/compta/commande/fiche.php?id='.$commande->id;
            $head[$h][1] = $langs->trans("ComptaCard");
            $hselected = $h;
            $h++;
        }

        $head[$h][0] = DOL_URL_ROOT.'/commande/info.php?id='.$commande->id;
        $head[$h][1] = $langs->trans("Info");
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("Order").": $commande->ref");

        /*
         *   Commande
         */
        print '<table class="border" width="100%">';

        print '<tr><td width="15%">'.$langs->trans("Ref")."</td>";
        print '<td colspan="2">'.$commande->ref.'</td>';
        print '<td width="50%">'.$langs->trans("Source").' : ' . $commande->sources[$commande->source] ;
        if ($commande->source == 0)
        {
            /* Propale */
            $propal = new Propal($db);
            $propal->fetch($commande->propale_id);
            print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
        }
        print "</td></tr>";

        // Client
        print "<tr><td>".$langs->trans("Customer")."</td>";
        print '<td colspan="3">';
        print '<a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></td>';
        print '</tr>';

        $nbrow=7;
        if ($conf->projet->enabled) $nbrow++;

        // Ref cde client
			print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
			print $langs->trans('RefCdeClient').'</td><td align="left">';
            print '</td>';
            print '</tr></table>';
            print '</td><td colspan="2">';
			print $commande->ref_client;
			print '</td>';
			print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('Note').' :<br>';
            if ($commande->brouillon == 1 && $user->rights->commande->creer)
            {
                print '<form action="fiche.php?id='.$id.'" method="post">';
                print '<input type="hidden" name="action" value="setnote">';
                print '<textarea name="note" rows="4" style="width:95%;">'.$commande->note.'</textarea><br>';
                print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"></center>';
                print '</form>';
            }
            else
            {
                print nl2br($commande->note);
            }
			print '</td>';
			print '</tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td>';
        print "<td colspan=\"2\">".$commande->statuts[$commande->statut]."</td>\n";
        print '</tr>';

        // Date
        print '<tr><td>'.$langs->trans("Date").'</td>';
        print "<td colspan=\"2\">".dolibarr_print_date($commande->date,"%A %d %B %Y")."</td>\n";
        print '</tr>';

        // Projet
        print '<tr>';
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
            print '<td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans("Project");
            print '</td>';
            //if ($_GET["action"] != "classer") print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans("SetProject")).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="2">';
            if ($_GET["action"] == "classer")
            {
                $html->form_project($_SERVER["PHP_SELF"]."?id=$commande->id",$commande->fk_soc,$commande->projetid,"projetid");
            }
            else
            {
                $html->form_project($_SERVER["PHP_SELF"]."?id=$commande->id",$commande->fk_soc,$commande->projetid,"none");
            }
            print "</td>";
        }
        else
        {
            print '<td>&nbsp;</td><td colspan="2">&nbsp;</td>';
        }
        print '</tr>';
        
        // Lignes de 3 colonnes
        
        // Discount
        print '<tr><td>'.$langs->trans("GlobalDiscount").'</td><td align="right">';
        print $commande->remise_percent.'%</td><td>&nbsp;';
        print '</td></tr>';

        // Total HT
        print '<tr><td>'.$langs->trans("TotalHT").'</td>';
        print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
        print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

        // Total VAT
        print '<tr><td>'.$langs->trans("TotalVAT").'</td><td align="right">'.price($commande->total_tva).'</td>';
        print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
        
        // Total TTC
        print '<tr><td>'.$langs->trans("TotalTTC").'</td><td align="right">'.price($commande->total_ttc).'</td>';
        print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

        print '</table>';

        /*
         * Lignes de commandes
         *
         */
        $sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice,';
        $sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid';
        $sql.= ' FROM '.MAIN_DB_PREFIX."commandedet as l";
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product=p.rowid';
        $sql.= " WHERE l.fk_commande = ".$commande->id;
        $sql.= " ORDER BY l.rowid";

        $resql = $db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0; $total = 0;

            if ($num) print '<br>';
            print '<table class="noborder" width="100%">';
            if ($num)
            {
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans('Description').'</td>';
                print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
                print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
                print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
                print '<td align="right">'.$langs->trans('Discount').'</td>';
                print '<td align="right">'.$langs->trans('AmountHT').'</td>';
                print '<td>&nbsp;</td><td>&nbsp;</td>';
                print "</tr>\n";
            }

            $var=true;
            while ($i < $num)
            {
                $objp = $db->fetch_object($resql);

                $var=!$var;
                print '<tr '.$bc[$var].'>';
                if ($objp->fk_product > 0)
                {
                    print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
                    if ($objp->fk_product_type) print img_object($langs->trans('ShowService'),'service');
                    else print img_object($langs->trans('ShowProduct'),'product');
                    print ' '.$objp->ref.'</a> - '.stripslashes(nl2br($objp->product));
                    print ($objp->description && $objp->description!=$objp->product)?'<br>'.$objp->description:'';
                    print '</td>';
                }
                else
                {
                    print '<td>'.stripslashes(nl2br($objp->description));
                    print "</td>\n";
                }
                print '<td align="right">'.$objp->tva_tx.'%</td>';

                print '<td align="right">'.price($objp->subprice)."</td>\n";

                print '<td align="right">'.$objp->qty.'</td>';

                if ($objp->remise_percent > 0)
                {
                    print '<td align="right">'.$objp->remise_percent."%</td>\n";
                }
                else
                {
                    print '<td>&nbsp;</td>';
                }

                print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100)."</td>\n";

                print '<td>&nbsp;</td><td>&nbsp;</td>';
                print '</tr>';

                $i++;
            }
            $db->free($resql);
        }
        else
        {
            dolibarr_print_error($db);
        }
        print '</table>';

        print '</div>';


        /*
        * Boutons actions
        */

        if (! $user->societe_id && ! $commande->facturee)
        {
            print "<div class=\"tabsAction\">\n";

            if ($commande->statut > 0 && $user->rights->facture->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;commandeid='.$commande->id.'&amp;socidp='.$commande->soc_id.'">'.$langs->trans("CreateBill").'</a>';
            }

            if ($user->rights->commande->creer)
            {
                print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/commande/fiche.php?action=facturee&amp;id='.$commande->id.'">'.$langs->trans("ClassifyBilled").'</a>';
            }
            print '</div>';
        }


        print "<table width=\"100%\"><tr><td width=\"50%\" valign=\"top\">";


        /*
        * Documents générés
        *
        */
        $file = $conf->facture->dir_output . "/" . $commande->ref . "/" . $commande->ref . ".pdf";
        $relativepath = $commande->ref."/".$commande->ref.".pdf";

        $var=true;

        if (file_exists($file))
        {
            print_titre($langs->trans("Documents"));
            print '<table width="100%" class="border">';

            print "<tr $bc[$var]><td>".$langs->trans("Order")." PDF</td>";
            print '<td><a href="'.DOL_URL_ROOT.'/document.php?modulepart=commande&file='.urlencode($relativepath).'">'.$commande->ref.'.pdf</a></td>';
            print '<td align="right">'.filesize($file). ' bytes</td>';
            print '<td align="right">'.strftime("%d %b %Y %H:%M:%S",filemtime($file)).'</td>';
            print '</tr>';

            print "</table>\n";

        }

        /*
        * Liste des factures
        */
        $sql = "SELECT f.rowid,f.facnumber, f.total_ttc, ".$db->pdate("f.datef")." as df";
        $sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."co_fa as cf";
        $sql .= " WHERE f.rowid = cf.fk_facture AND cf.fk_commande = ". $commande->id;

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            if ($num)
            {
                print '<br>';
                print_titre($langs->trans("RelatedBills"));
                $i = 0; $total = 0;
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre"><td>'.$langs->trans("Ref")."</td>";
                print '<td align="center">'.$langs->trans("Date").'</td>';
                print '<td align="right">'.$langs->trans("Price").'</td>';
                print "</tr>\n";

                $var=True;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    print "<tr $bc[$var]>";
                    print '<td><a href="../facture.php?facid='.$objp->rowid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->facnumber.'</a></td>';
                    print '<td align="center">'.dolibarr_print_date($objp->df).'</td>';
                    print '<td align="right">'.$objp->total_ttc.'</td></tr>';
                    $i++;
                }
                print "</table>";
            }
        }
        else
        {
            dolibarr_print_error($db);
        }

        print '</td><td valign="top" width="50%">';

        /*
        * Liste des expéditions
        */
        $sql = "SELECT e.rowid,e.ref,".$db->pdate("e.date_expedition")." as de";
        $sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
        $sql .= " WHERE e.fk_commande = ". $commande->id;

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            if ($num)
            {
                print_titre($langs->trans("Sendings"));
                $i = 0; $total = 0;
                print '<table class="border" width="100%">';
                print "<tr $bc[$var]><td>".$langs->trans("Sendings")."</td><td>".$langs->trans("Date")."</td></tr>\n";

                $var=True;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    print "<tr $bc[$var]>";
                    print '<td><a href="../../expedition/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowSending"),"sending").' '.$objp->ref.'</a></td>';
                    print "<td>".dolibarr_print_date($objp->de)."</td></tr>\n";
                    $i++;
                }
                print "</table>";
            }
        }
        else
        {
            dolibarr_print_error($db);
        }

        print "</td></tr></table>";

    }
    else
    {
        // Commande non trouvée
        print "Commande inexistante";
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
