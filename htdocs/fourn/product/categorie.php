<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
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
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("categories");

$mesg = '';

if (!$user->rights->produit->lire) accessforbidden();

/*
 * Creation de l'objet produit correspondant à l'id
 */  
if ($_GET["id"])
{           
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);      
}

llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"])
{
  //on veut supprimer une catégorie
  if ($_REQUEST["cat"])
    {
      $cat = new Categorie($db,$_REQUEST["cat"]);
      $cat->del_product($product);
    }

  //on veut ajouter une catégorie
  if (isset($_REQUEST["add_cat"]) && $_REQUEST["add_cat"]>=0)
    {
      $cat = new Categorie($db,$_REQUEST["add_cat"]);
      $cat->add_product($product);
    }
  
  if ( $result )
    { 

      /*
       *  En mode visu
       */
	  
      $h=0;
          
      $head[$h][0] = DOL_URL_ROOT."/fourn/product/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Card");
      $h++;
	  
  
      if ($conf->stock->enabled)
	{
	  $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
	  $head[$h][1] = $langs->trans("Stock");
	  $h++;
	}
      
      if ($conf->fournisseur->enabled)
	{
	  $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
	  $head[$h][1] = $langs->trans("Suppliers");
	  $h++;
	}
      
      $head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Photos");
      $h++;
      	      
      $head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
      $head[$h][1] = $langs->trans('Statistics');
      $h++;
	
      //affichage onglet catégorie
      if ($conf->categorie->enabled){
	$head[$h][0] = DOL_URL_ROOT."/fourn/product/categorie.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Categories');
	$hselected = $h;	      
	$h++;
      }


      dol_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

      print($mesg);
      print '<table class="border" width="100%">';
      print "<tr>";
      print '<td>'.$langs->trans("Ref").'</td><td>'.$product->ref.'</td>';
            
      print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
      print "</table><br>\n";

      $c = new Categorie($db);
      $cats = $c->containing($_REQUEST['id'],"product");
      
      if (sizeof($cats) > 0)
	{
	  print "Vous avez stocké le produit dans les catégorie suivantes:<br/><br/>";
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("AllWays").'</td></tr>';
	
	
	  foreach ($cats as $cat)
	    {
	    
	      $ways = $cat->print_all_ways ();
	      foreach ($ways as $way)
		{
		  $i = !$i;
		  print "<tr ".$bc[$i]."><td>".$way."</td>";
		  print "<td><a href= '".DOL_URL_ROOT."/fourn/product/categorie.php?id=".$product->id."&amp;cat=".$cat->id."'>".$langs->trans("DeleteFromCat")."</a></td></tr>\n";
		
		}
	    
	    }
	  print "</table><br/><br/>\n";
	}      
      else if($cats < 0)
	{
	  print $langs->trans("ErrorUnknown");
	}
      
      else
	{
	  print $langs->trans("NoCat")."<br/><br/>";
	}
      
    }
  
  print $langs->trans("AddProductToCat")."<br/><br/>";
  print '<table class="border" width="100%">';
  print '<form method="POST" action="'.DOL_URL_ROOT.'/fourn/product/categorie.php?id='.$product->id.'">';
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print "<tr><td><select name='add_cat'><option value='-1'>".$langs->trans("Choose")."</option>";
  $cat = new Categorie($db);
  foreach ($cat->get_all_categories() as $categorie)
    {
      print "<option value='".$categorie->id."'>".$categorie->label."</option>\n";
    }
  print "</select></td><td><input type='submit' value='".$langs->trans("Select")."'></td></tr>";
  print "</form></table><br/>";
  
}
$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>

