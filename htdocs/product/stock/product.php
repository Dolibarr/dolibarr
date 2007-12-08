<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER         <simon@kornog-computing.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
        \brief      Page de la fiche stock d'un produit
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");
$langs->load("orders");
$langs->load("bills");

$user->getrights('produit');
$mesg = '';

if (! $user->rights->produit->lire || ! $product->type == 0 || ! $conf->stock->enabled)
{
	accessforbidden();
}


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
if ($_GET["id"] || $_GET["ref"])
{
    $product = new Product($db);
    if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
    if ($_GET["id"]) $result = $product->fetch($_GET["id"]);
    
    llxHeader("","",$langs->trans("CardProduct".$product->type));

    if ($result > 0)
    {
      $head=product_prepare_head($product, $user);
        $titre=$langs->trans("CardProduct".$product->type);
        dolibarr_fiche_head($head, 'stock', $titre);
        
        $html = new Form($db);
        
        print($mesg);

        print '<table class="border" width="100%">';

        // Reference
        print '<tr>';
        print '<td width="25%">'.$langs->trans("Ref").'</td><td>';
        print $html->showrefnav($product,'ref','',1,'ref');
        print '</td>';
        print '</tr>';
        
        // Libellé
        print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
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
        print '<tr><td>'.$langs->trans("Status").'</td><td>';
        print $product->getLibStatut(2);
        print '</td></tr>';

        // Stock
        print '<tr><td>'.$langs->trans("TotalStock").'</td>';
		if ($product->stock_reel < $product->seuil_stock_alerte)
		{
			print '<td>'.$product->stock_reel.' '.img_warning().' (Seuil: '.$product->seuil_stock_alerte.')</td>';
		}
		else
		{
			print "<td>".$product->stock_reel.'</td>';
		}
        print '</tr>';
        

        // Nbre de commande clients en cours
	if ($conf->commande->enabled)
	  {
	    $result=$product->load_stats_commande(0,'2');
	    if ($result < 0) dolibarr_print_error($db,$product->error);
	    print '<tr><td>'.$langs->trans("CustomersOrdersRunning").'</td>';
	    print '<td>';
	    print $product->stats_commande['qty'];
	    $result=$product->load_stats_commande(0,'0,1');
	    if ($result < 0) dolibarr_print_error($db,$product->error);
	    print ' ('.$langs->trans("DraftOrWaitingShipped").': '.$product->stats_commande['qty'].')';
	    print '</td></tr>';
	  }
		        
        // Nbre de commande fournisseurs en cours
	if ($conf->fournisseur->enabled)
	  {
	    $result=$product->load_stats_commande_fournisseur(0,'3');
	    if ($result < 0) dolibarr_print_error($db,$product->error);
	    print '<tr><td>'.$langs->trans("SuppliersOrdersRunning").'</td>';
	    print '<td>';
	    print $product->stats_commande_fournisseur['qty'];
	    $result=$product->load_stats_commande_fournisseur(0,'0,1,2');
	    if ($result < 0) dolibarr_print_error($db,$product->error);
	    print ' ('.$langs->trans("DraftOrWaitingApproved").': '.$product->stats_commande_fournisseur['qty'].')';
	    print '</td></tr>';
	  }
                
        print "</table>";

    }
    print '</div>';

    /*
     * Correction du stock
     */
    if ($_GET["action"] == "correction")
    {
        print_titre($langs->trans("StockCorrection"));
        print "<form action=\"product.php?id=$product->id\" method=\"post\">\n";
        print '<input type="hidden" name="action" value="correct_stock">';
        print '<table class="border" width="100%"><tr>';
        print '<td width="20%">'.$langs->trans("Warehouse").'</td>';
        
        // Entrepot
        print '<td width="20%"><select class="flat" name="id_entrepot">';
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
        print '<td width="20%">';
        print '<select name="mouvement" class="flat">';
        print '<option value="0">'.$langs->trans("Add").'</option>';
        print '<option value="1">'.$langs->trans("Delete").'</option>';
        print '</select></td>';
        print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input class="flat" name="nbpiece" size="10" value=""></td>';

	print '</tr>';
        print '<tr><td colspan="5" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
        print '</table>';
        print '</form>';

    }
    
    /*
     * Transfert de pièces
     */
    if ($_GET["action"] == "transfert")
    {
        print_titre($langs->trans("Transfer"));
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
        print '<td width="20%">'.$langs->trans("NumberOfUnit").'</td><td width="20%"><input name="nbpiece" size="10" value=""></td></tr>';
        print '<tr><td colspan="6" align="center"><input type="submit" class="button" value="'.$langs->trans('Save').'">&nbsp;';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
        print '</table>';
        print '</form>';

    }
    
    /*
     *
     */
    if ($_GET["action"] == "definir")
    {
        print_titre($langs->trans("SetStock"));
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
	if ($user->rights->stock->mouvement->creer)
	{
		print '<a class="butAction" href="product.php?id='.$product->id.'&amp;action=transfert">'.$langs->trans("StockMovement").'</a>';
	}
	
	if ($user->rights->stock->creer)
	{
		print '<a class="butAction" href="product.php?id='.$product->id.'&amp;action=correction">'.$langs->trans("StockCorrection").'</a>';
	}
}
print '</div>';




/*
 * Contenu des stocks
 */
print '<br><table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="40%">'.$langs->trans("Warehouse").'</td>';
print '<td align="right">'.$langs->trans("NumberOfUnit").'</td></tr>';

$sql = "SELECT e.rowid, e.label, ps.reel FROM ".MAIN_DB_PREFIX."entrepot as e, ".MAIN_DB_PREFIX."product_stock as ps";
$sql .= " WHERE ps.fk_entrepot = e.rowid AND ps.fk_product = ".$product->id;
$sql .= " ORDER BY lower(e.label)";

$entrepotstatic=new Entrepot($db);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i=0; $total=0; $var=false;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);
        $entrepotstatic->id=$obj->rowid;
        $entrepotstatic->libelle=$obj->label;
        print '<tr '.$bc[$var].'>';
        print '<td>'.$entrepotstatic->getNomUrl(1).'</td>';
        print '<td align="right">'.$obj->reel.'</td>';
        print '</tr>'; ;
        $total = $total + $obj->reel;
        $i++;
        $var=!$var;
    }
}
print '<tr class="liste_total"><td align="right" class="liste_total">'.$langs->trans("Total").':</td><td class="liste_total" align="right">'.$total."</td></tr>";
print "</table>";



$db->close();


llxFooter('$Date$ - $Revision$');
?>
