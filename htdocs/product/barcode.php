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
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

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
if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
if ($_GET["id"]) $result = $product->fetch($_GET["id"]);


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

//erics: pour créer des produits composés de x 'sous' produits
/*
$head[$h][0] = DOL_URL_ROOT."/product/pack.php?id=".$product->id;
$head[$h][1] = $langs->trans('Packs');
$h++;
*/

$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
$head[$h][1] = $langs->trans("Referers");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
$head[$h][1] = $langs->trans('Documents');
$h++;

$titre=$langs->trans("CardProduct".$product->type);
dolibarr_fiche_head($head, $hselected, $titre);

print '<table class="border" width="100%">';

// Reference
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
$product->load_previous_next_ref();
$previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
$next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
print '</td>';
print '</tr>';

// Libelle
print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';
print '</tr>';

// Prix
print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">'.price($product->price).'</td></tr>';

// Statut
print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
if ($product->envente) print $langs->trans("OnSell");
else print $langs->trans("NotOnSell");
print '</td></tr>';

print "</table>\n";
      
print "</div>\n";


/*
 * Affiche code barre
 */




$db->close();

llxFooter('$Date$ - $Revision$');
?>
