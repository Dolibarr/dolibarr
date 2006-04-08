<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/product/stats/propal.php
        \ingroup    product, service, propal
		\brief      Page des stats des propals pour un produit
		\version    $Revision$
*/


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$mesg = '';

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.datec";


// Securite
$socidp = 0;
if ($user->societe_id > 0)
{
  $action = '';
  $socidp = $user->societe_id;
}
else
{
  $socidp = 0;
}


/*
 * Affiche fiche
 *
 */

llxHeader();

if ($_GET["id"] || $_GET["ref"])
{
    $product = new Product($db);
    if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
    if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

    if ( $result > 0)
    {
        /*
         *  En mode visu
         */
        
        $h=0;
        
        $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Card");
        $h++;
        
        $head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Price");
        $h++;
        
        //affichage onglet catégorie
        if ($conf->categorie->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/categorie.php?id=".$product->id;
            $head[$h][1] = $langs->trans('Categories');
            $h++;
        }
        
        if($product->type == 0)
        {
            if ($user->rights->barcode->lire)
            {
                if ($conf->barcode->enabled)
                {
                    $head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$product->id;
                    $head[$h][1] = $langs->trans("BarCode");
                    $h++;
                }
            }
        }
        
        
        $head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Photos");
        $h++;
        
        if($product->type == 0)
        {
            if ($conf->stock->enabled)
            {
                $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
                $head[$h][1] = $langs->trans("Stock");
                $h++;
            }
        }
        
        if ($conf->fournisseur->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
            $head[$h][1] = $langs->trans("Suppliers");
            $h++;
        }
        
        $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Statistics');
        $h++;
        
        //erics: pour créer des produits composés de x 'sous' produits
        /*
        $head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Packs');
        $h++;
        */
        
        $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Referers');
        $hselected=$h;
        $h++;
        
        $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
        $head[$h][1] = $langs->trans('Documents');
        $h++;
        
        $titre=$langs->trans("CardProduct".$product->type);
        dolibarr_fiche_head($head, $hselected, $titre);


        print '<table class="border" width="100%">';

        // Reference
        print '<tr>';
        print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="3">';
        $product->load_previous_next_ref();
        $previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
        $next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
        if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
        if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
        print '</td>';
        print '</tr>';

		// Libelle
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td>';
        print '</tr>';
        
        // Prix
        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="3">'.price($product->price).'</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
		print $product->getLibStatut(2);
        print '</td></tr>';

        print '<tr><td valign="top" width="25%">'.$langs->trans("Referers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("NbOfCustomers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("NbOfReferers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
        print '</tr>';

        // Propals
        if ($conf->propal->enabled)
        {
            $ret=$product->load_stats_propale($socidp);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("propal");
            print '<tr><td>';
            print '<a href="propal.php?id='.$product->id.'">'.img_object('','propal').' '.$langs->trans("Proposals").'</a>';
            print '</td><td align="right">';
            print $product->stats_propale['customers'];
            print '</td><td align="right">';
            print $product->stats_propale['nb'];
            print '</td><td align="right">';
            print $product->stats_propale['qty'];
            print '</td>';
            print '</tr>';
        }
        // Commandes
        if ($conf->commande->enabled)
        {
            $ret=$product->load_stats_commande($socidp);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("orders");
            print '<tr><td>';
            print '<a href="commande.php?id='.$product->id.'">'.img_object('','order').' '.$langs->trans("Orders").'</a>';
            print '</td><td align="right">';
            print $product->stats_commande['customers'];
            print '</td><td align="right">';
            print $product->stats_commande['nb'];
            print '</td><td align="right">';
            print $product->stats_commande['qty'];
            print '</td>';
            print '</tr>';
        }
        // Contrats
        if ($conf->contrat->enabled)
        {
            $ret=$product->load_stats_contrat($socidp);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("contracts");
            print '<tr><td>';
            print '<a href="contrat.php?id='.$product->id.'">'.img_object('','contract').' '.$langs->trans("Contracts").'</a>';
            print '</td><td align="right">';
            print $product->stats_contrat['customers'];
            print '</td><td align="right">';
            print $product->stats_contrat['nb'];
            print '</td><td align="right">';
            print $product->stats_contrat['qty'];
            print '</td>';
            print '</tr>';
        }
        // Factures
        if ($conf->facture->enabled)
        {
            $ret=$product->load_stats_facture($socidp);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("bills");
            print '<tr><td>';
            print '<a href="facture.php?id='.$product->id.'">'.img_object('','bill').' '.$langs->trans("Bills").'</a>';
            print '</td><td align="right">';
            print $product->stats_facture['customers'];
            print '</td><td align="right">';
            print $product->stats_facture['nb'];
            print '</td><td align="right">';
            print $product->stats_facture['qty'];
            print '</td>';
            print '</tr>';
        }
        
        print "</table>";

        print '</div>';


        $sql = "SELECT distinct(s.nom), s.idp, p.rowid as propalid, p.ref, p.total as amount,";
				$sql.= $db->pdate("p.datec")." as date, p.fk_statut as statut";
				if (!$user->rights->commercial->client->voir && !$socidp) $sql .= ", sc.fk_soc, sc.fk_user ";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."propaldet as d";
        if (!$user->rights->commercial->client->voir && !$socidp) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
				$sql.= " WHERE p.fk_soc = s.idp";
        $sql.= " AND d.fk_propal = p.rowid AND d.fk_product =".$product->id;
        if (!$user->rights->commercial->client->voir && !$socidp) $sql .= " AND s.idp = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socidp)
        {
            $sql .= " AND p.fk_soc = $socidp";
        }
        $sql .= " ORDER BY $sortfield $sortorder ";
        $sql.= $db->plimit($conf->liste_limit +1, $offset);

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);

            print_barre_liste($langs->trans("Proposals"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num);

            $i = 0;
            print "<table class=\"noborder\" width=\"100%\">";
            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.rowid","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"p.datec","","&amp;id=".$_GET["id"],'align="center"',$sortfield);
            print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"p.total","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
            print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"p.fk_statut","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
            print "</tr>\n";

            $propalstatic=new Propal($db);

            if ($num > 0)
            {
                $var=True;
                while ($i < $num && $i < $conf->liste_limit)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;

                    print "<tr $bc[$var]>";
                    print '<td><a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' ';
                    print $objp->ref;
                    print "</a></td>\n";
                    print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44).'</a></td>';
                    print "<td align=\"center\">";
                    print dolibarr_print_date($objp->date)."</td>";
                    print "<td align=\"right\">".price($objp->amount)."</td>\n";
                    print '<td align="right">'.$propalstatic->LibStatut($objp->statut,5).'</td>';
                    print "</tr>\n";
                    $i++;
                }
            }
        }
        else
        {
            dolibarr_print_error($db);
        }
        print "</table>";
        print '<br>';
        $db->free($result);
    }
}
else
{
    dolibarr_print_error();
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
