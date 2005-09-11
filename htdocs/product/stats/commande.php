<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/product/stats/commande.php
        \ingroup    product, service, commande
        \brief      Page des stats des commandes pour un produit
        \version    $Revision$
*/


require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("orders");

$mesg = '';

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="c.date_creation";


if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}
else
{
  $socid = 0;
}


/*
 * Affiche fiche
 *
 */

llxHeader();


if ($_GET["id"])
{
    $product = new Product($db);
    $result = $product->fetch($_GET["id"]);

    if ($result > 0)
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
	    $head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
	    $head[$h][1] = $langs->trans('Packs');
	    $h++;

        $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
        $head[$h][1] = $langs->trans('Referers');
        $hselected=$h;
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
        $head[$h][1] = $langs->trans('Documents');
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


        print '<table class="border" width="100%">';

        print '<tr>';
        print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="3">'.$product->ref.'</td>';
        print '</tr>';
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td>';
        print '</tr>';
        
        // Prix
        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="3">'.price($product->price).'</td></tr>';
        
        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="3">';
        if ($product->envente) print $langs->trans("OnSell");
        else print $langs->trans("NotOnSell");
        print '</td></tr>';

        print '<tr><td valign="top" width="25%">'.$langs->trans("Referers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("NbOfCustomers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("NbOfReferers").'</td>';
        print '<td align="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
        print '</tr>';

        // Propals
        if ($conf->propal->enabled)
        {
            $ret=$product->load_stats_propale($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("propal");
            print '<tr><td>';
            print '<a href="propal.php?id='.$product->id.'">'.$langs->trans("Proposals").'</a>';
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
            $ret=$product->load_stats_commande($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("orders");
            print '<tr><td>';
            print '<a href="commande.php?id='.$product->id.'">'.$langs->trans("Orders").'</a>';
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
            $ret=$product->load_stats_contrat($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("contracts");
            print '<tr><td>';
            print '<a href="contrat.php?id='.$product->id.'">'.$langs->trans("Contracts").'</a>';
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
            $ret=$product->load_stats_facture($socid);
            if ($ret < 0) dolibarr_print_error($db);
            $langs->load("bills");
            print '<tr><td>';
            print '<a href="facture.php?id='.$product->id.'">'.$langs->trans("Bills").'</a>';
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
        

        $sql = "SELECT distinct(s.nom), s.idp, s.code_client, c.rowid, c.total_ht as amount,";
        $sql.= " ".$db->pdate("c.date_creation")." as date, c.fk_statut as statut, c.rowid as commandeid";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."commandedet as d";
		$sql.= " WHERE c.fk_soc = s.idp";
        $sql.= " AND d.fk_commande = c.rowid AND d.fk_product =".$product->id;
        if ($socid)
        {
            $sql .= " AND f.fk_soc = $socid";
        }
        $sql.= " ORDER BY $sortfield $sortorder ";
        $sql.= $db->plimit($conf->liste_limit +1, $offset);

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);

            print_barre_liste($langs->trans("Orders"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num);

            $i = 0;
            print "<table class=\"noborder\" width=\"100%\">";

            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"c.rowid","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"c.date_creation","","&amp;id=".$_GET["id"],'align="center"',$sortfield);
            print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"c.amount_ht","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
            print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"c.fk_statut","","&amp;id=".$_GET["id"],'align="center"',$sortfield);
            print "</tr>\n";

            if ($num > 0)
            {
                $var=True;
                while ($i < $num && $i < $conf->liste_limit)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;

                    print "<tr $bc[$var]>";
                    print '<td><a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$objp->commandeid.'">'.img_object($langs->trans("ShowOrder"),"order").' ';
                    print $objp->rowid;
                    print "</a></td>\n";
                    print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44).'</a></td>';
                    print "<td>".$objp->code_client."</td>\n";
                    print "<td align=\"center\">";
                    print dolibarr_print_date($objp->date)."</td>";
                    print "<td align=\"right\">".price($objp->amount)."</td>\n";
                    $fac=new Commande($db);
                    print '<td align="center">'.$fac->LibStatut($objp->statut,1).'</td>';
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
