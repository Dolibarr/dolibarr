<?php
/*  Copyright (C) 2006      Jean Heimburger     <jean@tiaris.info>
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
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once("../includes/configure.php");

llxHeader();

if ($action == '' && !$cancel) {

	if ($_GET['id'])
	{
		$osc_prod = new Osc_product($db, $_GET['id']);
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
	  	print '<a class="butAction" href="fiche.php?action=import&amp;id='.$osc_prod->osc_id.'">'.$langs->trans("Import").'</a>';
	  }

	  print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';
	  print "\n</div><br>\n";
	  // seule action importer

		}
		else
		{
	  print "<p>ERROR 1</p>\n";
	  dolibarr_print_error('',"erreur webservice ".$osc_prod->error);
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
	$osc_prod = new Osc_product($db, $_GET['id']);
	$result = $osc_prod->fetch($_GET['id']);

	if ( !$result )
	{
		$product = new Product($db);
		if ($_error == 1)
		{
			print '<br>erreur 1</br>';
			//	exit;
		}
		$product = $osc_prod->osc2dolibarr($_GET['id']);

	}
	else
	{
		print "<p>erreur $osc_prod->fetch</p>";
	}

	/* utilisation de la table de transco*/
	if ($osc_prod->get_productid($osc_prod->osc_id)>0)
	{
		print '<p>Ce produit existe déjà</p>';
	}
	else
	{

		$id = $product->create($user);

		if ($id > 0)
		{
			print "\n<div class=\"tabsAction\">\n";
			$prod = new Product($db);
			$res = $prod->fetch($id);

			$prod->add_photo_web($conf->produit->dir_output,$osc_prod->osc_image);
			print '<p>création réussie produit '.$id.' référence : '.$product->ref;
			$res = $osc_prod->transcode($osc_prod->osc_id,$product->id);

			print ' Id osc : '.$osc_prod->osc_id.'</p>';
			print '<a class="butAction" href="index.php">'.$langs->trans("Retour").'</a>';

			print "\n</div><br>\n";
			$id_entrepot = OSC_ENTREPOT;
			$id = $product->create_stock($user, $id_entrepot,$osc_prod->osc_stock);
			//				if ($id > 0)  exit;
		}
		else
		{
			print "<p>On a une erreur".$id."</p>";
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
					//	exit;
				}
				$id = $product_control->fetch($ref = $osc_prod->osc_ref);

				if ($id > 0)
				{
					$id = $product->update($id, $user);
					if ($id > 0)
					{
						$id_entrepot = 1;
						$id = $product->correct_stock($user, $id_entrepot,$osc_prod->osc_stock, 0);
					}
					else print '<br>Erreur update '.$product->error().'</br>';
				}
				else print '<br>update impossible $id : '.$product_control->error().' </br>';
			}
			if ($id == -1)
			{
				print '<p>erreur'.$product->error().'</p>';
			}
			print '<p><a class="butAction" href="index.php">'.$langs->trans("Retour").'</a></p>';
		}
	}
}

llxFooter('$Date$ - $Revision$');
?>
