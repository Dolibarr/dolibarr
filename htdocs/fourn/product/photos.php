<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
   \file       htdocs/product/fiche.php
   \ingroup    product
   \brief      Page de la fiche produit
   \version    $Revision$
*/

require("./pre.inc.php");
;



if (!$user->rights->produit->lire) accessforbidden();


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

/*
 *
 */

if ( $_POST["sendit"] && defined('MAIN_UPLOAD_DOC') && MAIN_UPLOAD_DOC == 1)
{
  if ($_GET["id"])
    {           
      $product = new Product($db);
      $result = $product->fetch($_GET["id"]);

      $product->add_photo($conf->produit->dir_output, $_FILES['photofile']);
    }
}
/*
 *
 */
llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"])
{
  $product = new Product($db);
  $result = $product->fetch($_GET["id"]);

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
      
      $head[$h][0] = DOL_URL_ROOT."/fourn/product/photos.php?id=".$product->id;
      $head[$h][1] = $langs->trans("Photos");
      $hselected = $h;	      
      $h++;
      	      
      dolibarr_fiche_head($head, $hselected, $langs->trans("CardProduct".$product->type).' : '.$product->ref);

      print($mesg);
      print '<table class="border" width="100%">';
      print "<tr>";
      print '<td>'.$langs->trans("Ref").'</td><td>'.$product->ref.'</td>';
      print '<td colspan="2">';
      if ($product->envente)
	{
	  print $langs->trans("OnSell");
	}
      else
	{
	  print $langs->trans("NotOnSell");
	}
      print '</td></tr>';
      print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
      print '<td>'.$langs->trans("SellingPrice").'</td><td>'.price($product->price).'</td></tr>';

      print '<tr><td>'.$langs->trans("Description").'</td><td colspan="3">'.nl2br($product->description).'</td></tr>';

      print "</table><br>\n";


      /*
       * Ajouter une photo
       *
       */
      if ($_GET["action"] == 'ajout_photo' && $user->rights->produit->creer  && $product->isproduct)
	{
	  print_titre($langs->trans("AddPhoto"));
	  
	  print '<form name="userfile" action="photos.php?id='.$product->id.'" enctype="multipart/form-data" METHOD="POST">';      
	  print '<input type="hidden" name="max_file_size" value="2000000">';
	  
	  print '<table class="border" width="100%"><tr>';
	  print '<td>'.$langs->trans("File").'</td>';
	  print '<td><input type="file" name="photofile"></td></tr>';
	  
	  print '<tr><td colspan="4" align="center">';
	  print '<input type="submit" name="sendit" value="'.$langs->trans("Save").'">&nbsp;';
	  
	  
	  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	  print '</table>';
	  print '</form><br />';
	}


      // Affiche photos
      $nbphoto=$product->show_photos($conf->produit->dir_output,1);
      if ($nbphoto < 1) print $langs->trans("NoPhotoYet")."<br><br>";
      print "</div>\n";

    }

  print "\n<div class=\"tabsAction\">\n";
  
  if ($_GET["action"] == '')
    {            
      if ( $user->rights->produit->creer && $product->isproduct)
	{
	  print '<a class="tabAction" href="photos.php?action=ajout_photo&amp;id='.$product->id.'">';
	  print $langs->trans("AddPhoto").'</a>';
	}      
}

print "\n</div>\n";



}
else
{
  print $langs->trans("ErrorUnknown");
}







$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
