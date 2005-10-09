<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
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
        \file       htdocs/product/stock/product.php
        \ingroup    product
        \brief      Page de la fiche produit
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");
$langs->load("bills");

$user->getrights('produit');
$mesg = '';

if (!$user->rights->produit->lire)
{
  accessforbidden();
}


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");


llxHeader("","",$langs->trans("ProductCard"));

if ($_POST["action"] == "create_stock")
{
  $product = new Product($db);
  $product->id = $_GET["id"];
  $product->create_stock($_POST["id_entrepot"], $_POST["nbpiece"]);
}

if ($_POST["action"] == "correct_stock" && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  if (is_numeric($_POST["nbpiece"]))
    {

      $product = new Product($db);
      $product->id = $_GET["id"];
      $product->correct_stock($user, 
			      $_POST["id_entrepot"], 
			      $_POST["nbpiece"],
			      $_POST["mouvement"]);
    }
}

if ($_POST["action"] == "transfert_stock" && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  if ($_POST["id_entrepot_source"] <> $_POST["id_entrepot_destination"])
    {
      if (is_numeric($_POST["nbpiece"]))
	{
	  
	  $product = new Product($db);
	  $product->id = $_GET["id"];

	  $product->correct_stock($user, 
				  $_POST["id_entrepot_source"], 
				  $_POST["nbpiece"],
				  1);

	  $product->correct_stock($user, 
				  $_POST["id_entrepot_destination"], 
				  $_POST["nbpiece"],
				  0);
	}
    }
}

/*
 * Fiche stock
 *
 */
if ($_GET["id"])
{

    $product = new Product($db);

    if ( $product->fetch($_GET["id"]))
    {
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

        if ($product->type == 0)
        {
            if ($conf->stock->enabled)
            {
                $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
                $head[$h][1] = $langs->trans("Stock");
                $hselected = $h;
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
        $head[$h][1] = $langs->trans('Referers');
        $h++;
	  
        $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
        $head[$h][1] = $langs->trans('Documents');
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

        print($mesg);

        print '<table class="border" width="100%">';

        // Reference
        print '<tr>';
        print '<td width="15%">'.$langs->trans("Ref").'</td><td>'.$product->ref.'</td>';
        print '</tr>';

        // Libellé
        print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
        print '</tr>';

        // Prix
        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td>';
        print '</tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td>';
        if ($product->envente) print $langs->trans("OnSell");
        else print $langs->trans("NotOnSell");
        print '</td></tr>';

        // TVA
        $langs->load("bills");
        print '<tr><td>'.$langs->trans("VATRate").'</td><td>'.$product->tva_tx.' %</td></tr>';

        // Stock
        if ($product->type == 0 && $conf->stock->enabled)
        {
            print '<tr><td><a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.$product->id.'">'.$langs->trans("Stock").'</a></td>';
            if ($product->no_stock)
            {
                print "<td>Pas de définition de stock pour ce produit";
            }
            else
            {
                if ($product->stock_reel <= $product->seuil_stock_alerte)
                {
                    print '<td class="alerte">'.$product->stock_reel.' Seuil : '.$product->seuil_stock_alerte;
                }
                else
                {
                    print "<td>".$product->stock_reel;
                }
            }
            print '</td></tr>';
        }
        
        print "</table>";

        /*
        * Contenu des stocks
        *
        */
        print '<br><table class="noborder" width="100%">';
        print '<tr class="liste_titre"><td width="40%">'.$langs->trans("Warehouse").'</td><td width="60%">Valeur du stock</td></tr>';
        $sql = "SELECT e.rowid, e.label, ps.reel FROM ".MAIN_DB_PREFIX."entrepot as e, ".MAIN_DB_PREFIX."product_stock as ps";
        $sql .= " WHERE ps.fk_entrepot = e.rowid AND ps.fk_product = ".$product->id;
        $sql .= " ORDER BY lower(e.label)";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0; $total = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                print '<tr><td width="40%">'.$obj->label.'</td><td>'.$obj->reel.'</td></tr>'; ;
                $total = $total + $obj->reel;
                $i++;
            }
        }
        print '<tr class="liste_total"><td align="right" class="liste_total">'.$langs->trans("Total").':</td><td class="liste_total">'.$total."</td></tr></table>";

    }
    print '</div>';

    /*
    * Correction du stock
    *
    */
    if ($_GET["action"] == "correction")
    {
        print_titre ("Correction du stock");
        print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
        print '<input type="hidden" name="action" value="correct_stock">';
        print '<table class="border" width="100%"><tr>';
        print '<td width="20%">'.$langs->trans("Warehouse").'</td><td width="20%"><select name="id_entrepot">';

        $sql  = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e";
        $sql .= " WHERE statut = 1";
        $sql .= " ORDER BY lower(e.label)";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                print '<option value="'.$obj->rowid.'">'.$obj->label ;
                $i++;
            }
        }
        print '</select></td>';
        print '<td width="20%"><select name="mouvement">';
        print '<option value="0">'.$langs->trans("Add").'</option>';
        print '<option value="1">'.$langs->trans("Delete").'</option>';
        print '</select></td>';
        print '<td width="20%">Nb de pièce</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
        print '<tr><td colspan="5" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
        print '</table>';
        print '</form>';

    }
    /*
    * Transfert de pièces
    *
    */
    if ($_GET["action"] == "transfert")
    {
        print_titre ("Transfert de stock");
        print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
        print '<input type="hidden" name="action" value="transfert_stock">';
        print '<table class="border" width="100%"><tr>';
        print '<td width="20%">'.$langs->trans("Source").'</td><td width="20%"><select name="id_entrepot_source">';

        $sql  = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e";
        $sql .= " WHERE statut = 1";
        $sql .= " ORDER BY lower(e.label)";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                print '<option value="'.$obj->rowid.'">'.$obj->label ;
                $i++;
            }
        }
        print '</select></td>';

        print '<td width="20%">'.$langs->trans("Target").'</td><td width="20%"><select name="id_entrepot_destination">';

        $sql  = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e";
        $sql .= " WHERE statut = 1";
        $sql .= " ORDER BY lower(e.label)";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                print '<option value="'.$obj->rowid.'">'.$obj->label ;
                $i++;
            }
        }
        print '</select></td>';
        print '<td width="20%">Nb de pièce</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
        print '<tr><td colspan="6" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
        print '</table>';
        print '</form>';

    }
    /*
    *
    *
    */
    if ($_GET["action"] == "definir")
    {
        print_titre ("Créer un stock");
        print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
        print '<input type="hidden" name="action" value="create_stock">';
        print '<table class="border" width="100%"><tr>';
        print '<td width="20%">'.$langs->trans("Warehouse").'</td><td width="40%"><select name="id_entrepot">';

        $sql = "SELECT e.rowid, e.label FROM ".MAIN_DB_PREFIX."entrepot as e";
        $sql .= " ORDER BY lower(e.label)";

        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                print '<option value="'.$obj->rowid.'">'.$obj->label ;
                $i++;
            }
        }
        print '</select></td><td width="20%">Nb de pièce</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
        print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td></tr>';
        print '</table>';
        print '</form>';
    }
}
else
{
    dolibarr_print_error();
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "<div class=\"tabsAction\">\n";

if ($_GET["action"] == '' )
{
  print '<a class="tabAction" href="product.php?id='.$product->id.'&amp;action=transfert">Transfert</a>';
  print '<a class="tabAction" href="product.php?id='.$product->id.'&amp;action=correction">Correction stock</a>';
}
print '</div>';


$db->close();


llxFooter('$Date$ - $Revision$');
?>
