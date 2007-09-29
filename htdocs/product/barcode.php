<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/product/barcode.php
        \ingroup    product
        \brief      Page du code barre
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");
$langs->load("bills");

$user->getrights('barcode');

if (!$user->rights->barcode->lire)
accessforbidden();

/*
 * Affiche historique prix
 */

llxHeader("","",$langs->trans("BarCode"));

$html = new Form($db);

$product = new Product($db);
if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
if ($_GET["id"]) $result = $product->fetch($_GET["id"]);


$head=product_prepare_head($product, $user);
$titre=$langs->trans("CardProduct".$product->type);
dolibarr_fiche_head($head, 'barcode', $titre);


print '<table class="border" width="100%">';

// Reference
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
print $html->showrefnav($product,'ref');
print '</td>';
print '</tr>';

// Libelle
print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';
print '</tr>';

 // Prix
    print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">';
	if ($product->price_base_type == 'TTC')
	{
	  print price($product->price_ttc).' '.$langs->trans($product->price_base_type);
	}
	else
	{
	  print price($product->price).' '.$langs->trans($product->price_base_type);
	}
	print '</td></tr>';

// Statut
print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
print $product->getLibStatut(2);
print '</td></tr>';

// Barcode type	 
print '<tr><td>'.$langs->trans("BarcodeType").'</td><td colspan="2">';
print $product->barcode_type_label;
print '</td></tr>';

print "</table>\n";
      
print "</div>\n";


/*
 * Affiche code barre
 */




$db->close();

llxFooter('$Date$ - $Revision$');
?>
