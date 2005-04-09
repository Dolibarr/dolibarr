<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
        \file       htdocs/product/price.php
        \ingroup    product
        \brief      Page de la fiche produit
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");

$user->getrights('produit');

if (!$user->rights->produit->lire)
accessforbidden();


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");




/*
 *
 *
 */

llxHeader("","",$langs->trans("Price"));

$product = new Product($db);
$result = $product->fetch($_GET["id"]);


$h=0;

$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
$head[$h][1] = $langs->trans("Card");
$h++;

$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
$head[$h][1] = $langs->trans("Price");
$hselected=$h;
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

if ($conf->fournisseur->enabled) {
    $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
    $head[$h][1] = $langs->trans("Suppliers");
    $h++;
}

$head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
$head[$h][1] = $langs->trans("Photos");
$h++;

$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
$head[$h][1] = $langs->trans("Statistics");
$h++;


dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


$sql = "SELECT p.rowid, p.price, ".$db->pdate("p.date_price")." as dp, u.login";
$sql .= " FROM ".MAIN_DB_PREFIX."product_price as p, llx_user as u";
$sql .= " WHERE fk_product = ".$product->id;
$sql .= " AND p.fk_user_author = u.rowid ";
$sql .= " ORDER BY p.date_price DESC ";
$sql .= $db->plimit();
$result = $db->query($sql) ;

if ($result)
{
    $num = $db->num_rows($result);

    if (! $num)
    {
        $result = $db->free($result) ;

        // Il doit au moins y avoir la ligne de prix initial.
        // On l'ajoute donc pour remettre à niveau pb vieilles versions
        $product->update_price($product->id, $user);

        $result = $db->query($sql) ;
        $num = $db->num_rows($result);
    }

    if ($num > 0)
    {
        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("AppliedPricesFrom").'</td>';
        print '<td>'.$langs->trans("Price").'</td>';
        print '<td>'.$langs->trans("ChangedBy").'</td>';
        print '</tr>';

        $var=True;
        $i = 0;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            print "<tr $bc[$var]>";
            print "<td>".dolibarr_print_date($objp->dp,"%d %B %Y %H:%M:%S")."</td>";
            print "<td>".price($objp->price)."</td>";
            print "<td>".$objp->login."</td>";

            print "</tr>\n";
            $i++;
        }
        $db->free($result);
        print "</table>";
        print "<br>";
    }
}
else
{
    dolibarr_print_error($db);
}

print "</div>\n";



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
    if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
        print '<a class="tabAction" href="fiche.php?action=edit_price&amp;id='.$product->id.'">'.$langs->trans("UpdatePrice").'</a>';
    }
}

print "\n</div>\n";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
