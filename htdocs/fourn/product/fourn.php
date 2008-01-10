<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  \file       htdocs/fourn/product/fourn.php
  \ingroup    product
  \brief      Page de la fiche produit fournisseur
  \version    $Revision$
*/

require("./pre.inc.php");

require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.product.class.php";

if (!$user->rights->produit->lire) accessforbidden();

if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{

  $product = new ProductFournisseur($db);
  $result = $product->fetch($_GET["id"], $_GET["id_fourn"]);

  if( $result == 0 )
    {
      $product->update($_POST["fourn_ref"], '1', $_POST["price"], $user);
    }

  Header('Location :fourn.php?id='.$product->id.'&id_fourn='.$_GET["id_fourn"]);
}



llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"])
{
  if ($_GET["action"] <> 're-edit')
    {
      $product = new ProductFournisseur($db);
      $result = $product->fetch($_GET["id"], $_GET["id_fourn"]);
      $product->get_buyprice($_GET["id_fourn"],1);
    }
  
  if ( $result == 0)
    { 
      
      
      /*
       *  En mode visu
       */
      
      $h=0;
      
      $head[$h][0] = DOL_URL_ROOT."/fourn/product/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans("ProductCard");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT."/fourn/product/fourn.php?id=".$product->id.'&amp;id_fourn='.$_GET["id_fourn"];
      $head[$h][1] = $langs->trans("SupplierCard");
      $hselected = $h;
      $h++;
	  
      dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);
      
      print '<table class="border" width="100%">';
      
      if ($conf->categorie->enabled)
	{		  
	  print '<tr id="ways">';
	  print '<td colspan="3">';
	  $cat = new Categorie ($db);
	  $way = $cat->print_primary_way($product->id," &gt; ",'fourn/product/liste.php');
	  if ($way == "")
	    {
	      print "Ce produit n'appartient à aucune catégorie";
	    }
	  else
	    {
		  print $langs->trans("Categorie")." : ";
		  print $way;	
		}
	  print '</td></tr>';
	}

      print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';
	  
      print '<tr><td width="20%">'.$langs->trans("InternalRef").'</td><td width="40%">'.$product->ref.'</td>';

      print '<td class="photo" valign="top" rowspan="6">';
      $product->show_photos($conf->produit->dir_output,1,1,0);
      print '</td></tr>';

      print "<tr>";
      print '<td width="20%">'.$langs->trans("Supplier").'</td><td width="40%">'.$product->fourn->getNomUrl(1).'</td>';
      print '</tr><tr>';
      print '<td width="20%">'.$langs->trans("SupplierRef").'</td><td width="40%">'.$product->fourn_ref.'</td>';
      print '</tr><tr>';
      print '<td width="20%">'.$langs->trans("BuiingPrice").'</td><td width="40%">'.price($product->buyprice).'</td>';
      print '</tr>';	  
            
      print '<tr><td colspan="2">'.$langs->trans("Description").'</td></tr>';
      print '<tr><td valign="top" colspan="2">'.nl2br($product->description).'&nbsp;</td></tr>';
      
      print "</table><br>\n";
      
      print '<table class="border" width="100%">';
      print '<tr class="liste_titre"><td>';
      print $langs->trans("Date").'</td>';
      print '<td align="right">'.$langs->trans("Price").'</td>';
      print '<td align="center">'.$langs->trans("Quantity").'</td>';
      print '</tr>';
      
      /*
       * Prix
       */
      
      $sql = "SELECT p.price, p.quantity,".$db->pdate("tms") ." as date_releve";
      $sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as p";
      $sql .=" WHERE p.fk_soc = ".$product->fourn->id;
      $sql .= " AND p.fk_product = ".$product->id;
      $sql .= " ORDER BY p.quantity ASC";
      $resql= $db->query($sql) ;
      if ($resql)
	{
	  $num_fournisseur = $db->num_rows($resql);
	  $i = 0;
	  $var=True;      
	  while ($i < $num_fournisseur)
	    {
	      $objp = $db->fetch_object($resql);
	      $var=!$var;
	      print "<tr $bc[$var]>";
	      print '<td>'.dolibarr_print_date($objp->date_releve).'</td>';
	      print '<td align="right">'.price($objp->price).'</td>';
	      print '<td align="center">'.$objp->quantity.'</td></tr>';
	      
	      $i++;
	    }
	  $db->free($resql);
	}
      print '</table>';
           
      /*
       *
       * Fiche en mode edition
       *
       */
      if (($_GET["action"] == 'edit' || $_GET["action"] == 're-edit') && $user->rights->produit->creer)
	{

	  $action = 'fourn.php?id='.$product->id.'&amp;id_fourn='.$product->fourn->id;

	  print '<form action="'.$action.'" method="post">';
	  print '<input type="hidden" name="action" value="update">';
	  print '<br /><table class="border" width="100%">';
	  print '<tr><td width="20%">'.$langs->trans("Price").'</td>';
	  print '<td><input name="price" size="20" value="'.$product->buyprice.'"></td></tr>';

	  print '<tr><td>'.$langs->trans("SupplierRef").'</td>';
	  print '<td><input name="fourn_ref" size="40" value="'.$product->fourn_ref.'"></td></tr>';

	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $product->description;
	  print "</textarea></td></tr>";

	  print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form>';
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
	  
	  if ( $user->rights->produit->creer)
	    {
	      print '<a class="butAction" href="fourn.php?action=edit&amp;id='.$product->id.'&amp;id_fourn='.$product->fourn->id.'">'.$langs->trans("Modify").'</a>';
	    }
	}
      
      print "\n</div>\n";
      
    }  
}
else
{
  print $langs->trans("ErrorUnknown");
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
    
