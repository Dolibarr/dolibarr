<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
	    \file       htdocs/product/fiche.php
        \ingroup    product
		\brief      Page de la fiche produit
		\version    $Revision$
*/

require("./pre.inc.php");
require("../propal.class.php");
require("../facture.class.php");

$langs->load("products");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');
$mesg = '';

if (!$user->rights->produit->lire)
{
  accessforbidden();
}

$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

if ($_POST["action"] == 'updateprice' && $_POST["cancel"] <> $langs->trans("Cancel"))
{

  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      $product->update_buyprice($_POST["id_fourn"], $_POST["qty"], $_POST["price"], $user);
    }
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
  $action = '';
  Header("Location: fournisseurs.php?id=".$_POST["id"]);
}




llxHeader("","",$langs->trans("CardProduct".$product->type));




  /*
   * Fiche produit
   */
  if ($_GET["id"])
    {

      if ($_GET["action"] <> 're-edit')
	{
	  $product = new Product($db);
	  $result = $product->fetch($_GET["id"]);
	}

      if ( $result )
	{ 

	  if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
	    {
	      /*
	       *  En mode visu
	       */
	      
	      // Zone recherche
	      print '<div class="formsearch">';
	      print '<form action="liste.php" method="post">';
	      print '<input type="hidden" name="type" value="'.$product->type.'">';
	      print $langs->trans("Ref").': <input class="flat" type="text" size="10" name="sref">&nbsp;<input class="flat" type="submit" value="'.$langs->trans("Go").'"> &nbsp;';
	      print $langs->trans("Label").': <input class="flat" type="text" size="20" name="snom">&nbsp;<input class="flat" type="submit" value="'.$langs->trans("Go").'">';
	      print '</form></div>';
	      
	      $h=0;
	      
	      $head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("Card");
	      $h++;
	      
	      $head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
	      $head[$h][1] = $langs->trans("Price");
	      $h++;
	      
	      if($product->type == 0)
		{
		  if ($conf->stock->enabled)
		    {
		      $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
		      $head[$h][1] = 'Stock';
		      $h++;
		    }
		  $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
		  $head[$h][1] = 'Fournisseurs';
		  $hselected = $h;
		  
		  $h++;
		  
		}
	      
	      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
	      $head[$h][1] = $langs->trans('Statistics');
	      $h++;


	      dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);


	      print($mesg);
	      print '<table class="border" width="100%">';
	      print "<tr>";
	      print '<td width="20%">'.$langs->trans("Ref").'</td><td width="30%">'.$product->ref.'</td>';
	      print '<td width="20%">';
	      if ($product->envente)
		{
		  print $langs->trans("OnSell");
		}
	      else
		{
		  print $langs->trans("NotOnSell");
		}
	      print '</td></tr>';
	      print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td></tr>';
	      print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="3">'.price($product->price).'</td></tr>';
	      if ($product->type == 0)
		{
		  $nblignefour=4;
		}
	      else
		{
		  $nblignefour=4;
		} 
		
	      print '<tr class="liste_titre"><td valign="top">';
	      print $langs->trans("Suppliers").'</td>';
	      print '<td>'.$langs->trans("Ref").'</td>';
	      print '<td align="center">'.$langs->trans("Qty").'</td>';
	      print '<td align="right">Prix d\'achat</td>';
	      print '</tr>';
	      
	      $sql = "SELECT s.nom, s.idp, pf.ref_fourn, pfp.price, pfp.quantity";
	      $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	      $sql .= "  , ".MAIN_DB_PREFIX."product_fournisseur as pf";
	      $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
	      $sql .=" ON pf.fk_soc = pfp.fk_soc AND pf.fk_product = pfp.fk_product";
	      $sql .=" WHERE pf.fk_soc = s.idp AND pf.fk_product = ".$product->id;
	      $sql .= " ORDER BY lower(s.nom)";

	      if ( $db->query($sql) )
		{
		  $num = $db->num_rows();
		  $i = 0;

		  $var=True;      
		  while ($i < $num)
		    {
		      $objp = $db->fetch_object();
		      $var=!$var;
		      print "<tr $bc[$var]>";
		      print '<td><a href="../fourn/fiche.php?socid='.$objp->idp.'">'.$objp->nom.'</a></td>';
		      print '<td align="left">'.$objp->ref_fourn.'</td>';
		      print '<td align="center">';
		      print $objp->quantity;
		      print '</td>';

		      print '<td align="right">';
		      print price($objp->price);
		      print '<a href="fournisseurs.php?id='.$product->id.'&amp;action=add_price&amp;id_fourn='.$objp->idp.'&amp;qty='.$objp->quantity.'">';
		      print img_edit(). "</a></td>";
		      print '</tr>';

		      if ($_GET["action"] == 'add_price' 
			  && $user->rights->produit->creer 
			  && $_GET["qty"] == $objp->quantity
			  && $_GET["id_fourn"] == $objp->idp)
			{
			  $langs->load("suppliers");
			  
			  
			  print '<form action="fournisseurs.php?id='.$product->id.'" method="post">';
			  print '<input type="hidden" name="action" value="updateprice">';
			  print '<input type="hidden" name="id_fourn" value="'.$objp->idp.'">';
			  
			  print '<tr><td colspan="2" align="right">Modifier le prix</td>';
			  
			  print '<td align="center"><input name="qty" size="5" value="'.$objp->quantity.'"></td>';
			  print '<td><input name="price" size="8" value="'.price($objp->price).'"></td></tr>';
			  print '<tr><td colspan="2">&nbsp;</td><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
			  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
			  print '</form>';
			}    
		      $i++;
		    }

		  $db->free();
		}

	      print '</td></tr>';
	      print "</table><br>\n";      
	      print "</div>\n";
	    }
	}
    }
  else
    {
      print $langs->trans("ErrorUnknown");
    }


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

print "\n</div>\n";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
