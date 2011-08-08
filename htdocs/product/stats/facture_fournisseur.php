<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/product/stats/facture_fournisseur.php
 *       \ingroup    product service facture
 *       \brief      Page des stats des factures fournisseurs pour un produit
 *       \version    $Id: facture_fournisseur.php,v 1.33 2011/08/08 16:07:47 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load("companies");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$id,'product','','',$fieldid);

$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datef";


/*
 * View
 */

$supplierinvoicestatic=new FactureFournisseur($db);

$html = new Form($db);

if ($_GET["id"] || $_GET["ref"])
{
    $product = new Product($db);
    if ($_GET["ref"])
    {
    	$result = $product->fetch('',$_GET["ref"]);
    	$_GET["id"]=$product->id;
    }
    if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

    llxHeader("","",$langs->trans("CardProduct".$product->type));

    if ($result > 0)
    {
        /*
         *  En mode visu
         */
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'referers', $titre, 0, $picto);


        print '<table class="border" width="100%">';

        // Reference
        print '<tr>';
        print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $html->showrefnav($product,'ref','',1,'ref');
        print '</td>';
        print '</tr>';

        // Libelle
        print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td>';
        print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')'.'</td><td colspan="3">';
		print $product->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')'.'</td><td colspan="3">';
		print $product->getLibStatut(2,1);
		print '</td></tr>';

		show_stats_for_company($product,$socid);

        print "</table>";

        print '</div>';


        $sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client, f.facnumber, f.total_ht as total_ht,";
        $sql.= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid";
        if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= ", ".MAIN_DB_PREFIX."facture_fourn as f";
        $sql.= ", ".MAIN_DB_PREFIX."facture_fourn_det as d";
        if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
        $sql.= " WHERE f.fk_soc = s.rowid";
        $sql.= " AND s.entity = ".$conf->entity;
        $sql.= " AND d.fk_facture_fourn = f.rowid";
        $sql.= " AND d.fk_product =".$product->id;
        if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
        if ($socid) $sql.= " AND f.fk_soc = ".$socid;
        $sql.= " ORDER BY $sortfield $sortorder ";
        $sql.= $db->plimit($conf->liste_limit +1, $offset);

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);

            print_barre_liste($langs->trans("SuppliersInvoices"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num,0,'');

            $i = 0;
            print "<table class=\"noborder\" width=\"100%\">";

            print '<tr class="liste_titre">';
            print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"s.rowid","","&amp;id=".$_GET["id"],'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$_GET["id"],'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("SupplierCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$_GET["id"],'',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","&amp;id=".$_GET["id"],'align="center"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total_ht","","&amp;id=".$_GET["id"],'align="right"',$sortfield,$sortorder);
            print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.paye,f.fk_statut","","&amp;id=".$_GET["id"],'align="right"',$sortfield,$sortorder);
            print "</tr>\n";

            if ($num > 0)
            {
                $var=True;
                while ($i < $num && $conf->liste_limit)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;

                    print "<tr $bc[$var]>";
                    print '<td>';
                    $supplierinvoicestatic->id=$objp->facid;
                    $supplierinvoicestatic->ref=$objp->facnumber;
					print $supplierinvoicestatic->getNomUrl(1);
                    print "</td>\n";
                    print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,44).'</a></td>';
                    print "<td>".$objp->code_client."</td>\n";
                    print "<td align=\"center\">";
                    print dol_print_date($db->jdate($objp->datef))."</td>";
                    print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
                    $fac=new Facture($db);
                    print '<td align="right">'.$fac->LibStatut($objp->paye,$objp->statut,5).'</td>';
                    print "</tr>\n";
                    $i++;
                }
            }
        }
        else
        {
            dol_print_error($db);
        }
        print "</table>";
        print '<br>';
        $db->free($result);
    }
}
else
{
    dol_print_error();
}

$db->close();

llxFooter('$Date: 2011/08/08 16:07:47 $ - $Revision: 1.33 $');
?>
