<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003-2005 Éric Seigne <eric.seigne@ryxeo.com>
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
require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once("../includes/configure.php");

llxHeader();

if ($action == '' && !$cancel) {

 if ($_GET['id'])
 {
  $osc_prod = new Osc_product();
  $result = $osc_prod->fetch($_GET['id']);

  if ( !$result)
    { 
      print '<div class="titre">Fiche article OSC : '.$osc_prod->osc_name.'</div><br>';

      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
      print '<tr></tr><td width="20%">Descrption</td><td width="80%">'.$osc_prod->osc_desc.'</td></tr>';
      print '<tr></tr><td width="20%">ref</td><td width="80%">'.$osc_prod->osc_ref.'</td></tr>';
      print '<tr></tr><td width="20%">Id</td><td width="80%">'.$osc_prod->osc_id.'</td></tr>';
      print '<tr></tr><td width="20%">Prix</td><td width="80%">'.$osc_prod->osc_price.'</td></tr>';
      print '<tr></tr><td width="20%">Four_id</td><td width="80%">'.$osc_prod->osc_four.'</td></tr>';
      print "</table>";

	/* ************************************************************************** */
	/*                                                                            */ 
	/* Barre d'action                                                             */ 
	/*                                                                            */ 
	/* ************************************************************************** */
	print "\n<div class=\"tabsAction\">\n";

	  if ( $user->rights->produit->creer) {
        print '<a class="tabAction" href="fiche.php?action=import&amp;id='.$osc_prod->osc_id.'">'.$langs->trans("Import").'</a>';
    	}
	print "\n</div><br>\n";
// seule action importer
     
	}
      else
	{
	  print "<p>ERROR 1</p>\n";
	  dolibarr_print_error("erreur webservice ".$osc_prod->error);
	}
 }
 else
 {
   print "<p>ERROR 1</p>\n";
   print "Error";
 }
}
/* action Import création de l'objet product de dolibarr 
*
*/
 if (($_GET["action"] == 'import' ) && ( $_GET["id"] != '' ) && $user->rights->produit->creer)
    {
		  $osc_prod = new Osc_product();
  		  $result = $osc_prod->fetch($_GET['id']);
  
	  if ( !$result )
	  {
			$product = new Product($db);
	    	if ($_error == 1)
	    	{
	       		print '<br>erreur 1</br>';
				exit;
	    	}
	    	/* initialisation */
	    	$product->ref = $osc_prod->osc_ref;
	    	$product->libelle = $osc_prod->osc_name;
	    	$product->description = $osc_prod->osc_desc;
	    	$product->price = $osc_prod->osc_price;
	    	$product->tva_tx = $osc_prod->osc_tva;
	    	$product->type = 0;
	    	$product->seuil_stock_alerte = 0; /* on force */
	/* on force */
			$product->catid = 0; /* à voir avec la gestion des catégories */
			$product->status = 1; /* en vente */
		 } 

			$id = $product->create($user);
	       
		    if ($id > 0)
		    {
		       	print '<br>création réussie produit '.$id.' référence : '.$product->ref.'</br>';
				$id_entrepot = OSC_ENTREPOT;
				$id = $product->create_stock($id_entrepot,$osc_prod->osc_stock);
				if ($id > 0)  exit;
		    }
		    else
		    {
		        if ($id == -3)
		        {
		            $_error = 1;
		            $_GET["action"] = "create";
		            $_GET["type"] = $_POST["type"];
		        }
				if ($id == -2)
				{
				/* la référence existe on fait un update */
				 $product_control = new Product($db);
				 if ($_error == 1)
		    	 {
		       		print '<br>erreur 1</br>';
					exit;
		    	 }
			     $id = $product_control->fetch($ref = $osc_prod->osc_ref);
					
					if ($id > 0) 
					{ 
						$id = $product->update($id, $user);
						if ($id > 0) {
							$id_entrepot = 1;
							$id = $product->correct_stock($id_entrepot,$osc_prod->osc_stock);
						}
						else print '<br>Erreur update '.$id.'</br>';
					}
					else print '<br>update impossible $id : '.$id.' </br>';
				}
		    }
 
    }

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
