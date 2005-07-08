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
        \file       htdocs/product/stats/facture.php
        \ingroup    product, service
        \brief      Page des stats des factures pour un produit
        \version    $Revision$
*/


require("./pre.inc.php");
require_once("../../facture.class.php");

$langs->load("bills");

$mesg = '';

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datef";
if ($page == -1) $page = 0;
$limit = $conf->liste_limit;
$offset = $limit * $page ;


if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

llxHeader();


/*
 * Affiche fiche
 *
 */

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

            $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
            $head[$h][1] = $langs->trans('Bills');
            $hselected=$h;
            $h++;


            dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


        print '<table class="border" width="100%">';

        print '<tr>';
        print '<td width="10%">'.$langs->trans("Ref").'</td><td colspan="2" width="40%">'.$product->ref.'</td>';
        print '</tr>';
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';
        print '</tr>';
        
        // Prix
        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">'.price($product->price).'</td></tr>';
        
        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
        if ($product->envente) print $langs->trans("OnSell");
        else print $langs->trans("NotOnSell");
        print '</td></tr>';

        print "</table>";

        print "<br>";
        print '</div>';
        
        print_barre_liste($langs->trans("Bills"),$page,"facture.php","&amp;id=$product->id",$sortfield,$sortorder);

        $sql = "SELECT s.nom, s.idp, s.code_client, f.facnumber, f.amount,";
        $sql.= " ".$db->pdate("f.datef")." as df, f.paye, f.fk_statut as statut, f.rowid as facid";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."facturedet as d WHERE f.fk_soc = s.idp";
        $sql.= " AND d.fk_facture = f.rowid AND d.fk_product =".$product->id;
        if ($socid)
        {
            $sql .= " AND f.fk_soc = $socid";
        }
        $sql .= " ORDER BY $sortfield $sortorder ";
        $sql .= $db->plimit( $limit ,$offset);

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);

            $i = 0;
            print "<table class=\"noborder\" width=\"100%\">";

            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"s.idp","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$_GET["id"],'',$sortfield);
            print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
            print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"f.datef","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
            print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"f.amount","","&amp;id=".$_GET["id"],'align="right"',$sortfield);
            print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.paye,f.fk_statut","","&amp;id=".$_GET["id"],'align="center"',$sortfield);
            print "</tr>\n";

            if ($num > 0)
            {
                $var=True;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;

                    print "<tr $bc[$var]>";
                    print '<td><a href="'.DOL_URL_ROOT.'/compta/facture.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' ';
                    print $objp->facnumber;
                    print "</a></td>\n";
                    print '<td><a href="'.DOL_URL_ROOT.'/compta/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($objp->nom,44).'</a></td>';
                    print "<td>".$objp->code_client."</td>\n";
                    print "<td align=\"right\">";
                    print dolibarr_print_date($objp->df)."</td>";
                    print "<td align=\"right\">".price($objp->amount)."</td>\n";
                    $fac=new Facture($db);
                    print '<td align="center">'.$fac->LibStatut($objp->paye,$objp->statut).'</td>';
                    print "</tr>\n";
                    $i++;
                }
            }
        }
        else {
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
