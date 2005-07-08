<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
        \file       htdocs/product/barcode.php
        \ingroup    product
        \brief      Page du code barre
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$langs->load("bills");

$user->getrights('barcode');

if (!$user->rights->barcode->lire)
accessforbidden();

$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

/*
 * Affiche historique prix
 */

llxHeader("","",$langs->trans("BarCode"));

$product = new Product($db);
$result = $product->fetch($_GET["id"]);


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
                        $hselected=$h;
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

    if ($conf->fournisseur->enabled) {
        $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Suppliers");
        $h++;
    }

        $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Statistics");
        $h++;

        $head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
        $head[$h][1] = $langs->trans("Bills");
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

print "</table><br>\n";
      
print "</div>\n";


/*
 * Affiche code barre
 */




$db->close();

llxFooter('$Date$ - $Revision$');
?>
