<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
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
		\file       htdocs/product/categorie.php
		\ingroup    product
		\brief      Page de l'onglet categories de produits
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("categories");

$mesg = '';

if (!$user->rights->produit->lire) accessforbidden();

$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

/*
* Creation de l'objet produit correspondant à l'id
*/
if ($_GET["id"] || $_GET["ref"])
{
	$product = new Product($db);
	if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);
}
$html = new Form($db);


llxHeader("","",$langs->trans("CardProduct0"));

/*
 * Fiche produit
 */
if ($_GET["id"] || $_GET["ref"])
{
	//on veut supprimer une catégorie
	if ($_REQUEST["cat"])
	{
		$cat = new Categorie($db,$_REQUEST["cat"]);
		$cat->del_product($product);
	}

	//on veut ajouter une catégorie
	if (isset($_REQUEST["catMere"]) && $_REQUEST["catMere"]>=0)
	{
		$cat = new Categorie($db,$_REQUEST["catMere"]);
		$cat->add_product($product);
	}

	if ( $result )
	{

		/*
	 	 *  En mode visu
		 */

		$h=0;

		$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Card");
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Price");
		$h++;

		//affichage onglet catégorie
		if ($conf->categorie->enabled)
		{
			$head[$h][0] = DOL_URL_ROOT."/product/categorie.php?id=".$product->id;
			$head[$h][1] = $langs->trans('Categories');
			$hselected = $h;
			$h++;
		}

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

		if($product->type == 0)
		{
			if ($conf->stock->enabled)
			{
				$head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
				$head[$h][1] = $langs->trans("Stock");
				$h++;
			}
		}
		
		// Multilangs
    if($conf->global->MAIN_MULTILANGS)
    {
	    $head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$product->id;
	    $head[$h][1] = $langs->trans("Translation");
	    $h++;
    }

		if ($conf->fournisseur->enabled)
		{
			$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
			$head[$h][1] = $langs->trans("Suppliers");
			$h++;
		}

		$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
		$head[$h][1] = $langs->trans('Statistics');
		$h++;
		
		// sousproduits
		if($conf->global->PRODUIT_SOUSPRODUITS == 1)
		{
				$head[$h][0] = DOL_URL_ROOT."/product/sousproduits/fiche.php?id=".$product->id;
				$head[$h][1] = $langs->trans('AssociatedProducts');
				$h++;
		}
		
		$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
		$head[$h][1] = $langs->trans('Referers');
		$h++;

		$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
		$head[$h][1] = $langs->trans('Documents');
		$h++;

		$titre=$langs->trans("CardProduct".$product->type);
		dolibarr_fiche_head($head, $hselected, $titre);

		print($mesg);
		print '<table class="border" width="100%">';
		print "<tr>";
		// Reference
		print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
		$product->load_previous_next_ref();
		$previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
		$next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
		if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
		print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
		if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
		print '</td>';
		print '</tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
		print '</tr>';

        // Prix
        print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">'.price($product->price).'</td></tr>';

        // Statut
        print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
		print $product->getLibStatut(2);
        print '</td></tr>';

		print '</table><br/>';

		// Formulaire ajout dans une categorie
		print '<form method="post" action="'.DOL_URL_ROOT.'/product/categorie.php?id='.$product->id.'">';
		print '<table class="border" width="100%">';
		print '<tr><td>';
		print '<input type="submit" class="button" value="'.$langs->trans("ClassifyInCategory").'">'. $html->select_all_categories($categorie->id_mere);
		print '</td>';
		
    if ($user->rights->categorie->creer)
    {
        print '<td><a class="butAction" href="'.DOL_URL_ROOT.'/categories/fiche.php?action=create&amp;origin='.$product->id.'">'.$langs->trans("NewCat").'</a></td>';
    }
    
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br/>';


		$c = new Categorie($db);

		if ($_GET["id"])
		{
			$cats = $c->containing($_REQUEST["id"]);
		}

		if ($_GET["ref"])
		{
			$cats = $c->containing_ref($_REQUEST["ref"]);
		}

		if (sizeof($cats) > 0)
		{
			print $langs->trans("ProductIsInCategories");
			print '<br/>';
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Categories").'</td></tr>';

			foreach ($cats as $cat)
			{

				$ways = $cat->print_all_ways ();
				foreach ($ways as $way)
				{
					$i = !$i;
					print "<tr ".$bc[$i]."><td>".$way."</td>";
					print "<td>".img_delete($langs->trans("DeleteFromCat"))." <a href= '".DOL_URL_ROOT."/product/categorie.php?id=".$product->id."&amp;cat=".$cat->id."'>".$langs->trans("DeleteFromCat")."</a></td></tr>\n";

				}

			}
			print "</table><br/>\n";
		}
		else if($cats < 0)
		{
			print $langs->trans("ErrorUnknown");
		}

		else
		{
			print $langs->trans("NoCat")."<br/>";
		}

	}

}
$db->close();


llxFooter('$Date$ - $Revision$');
?>
