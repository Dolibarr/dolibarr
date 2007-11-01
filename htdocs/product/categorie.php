<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
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
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("categories");

if (!$user->rights->produit->lire) accessforbidden();

$mesg = '';


/*
*	Actions
*/

//on veut supprimer une catégorie
if ($_REQUEST["removecat"] && $user->rights->produit->creer)
{
	$product = new Product($db);
	if ($_REQUEST["ref"]) $result = $product->fetch('',$_REQUEST["ref"]);
	if ($_REQUEST["id"])  $result = $product->fetch($_REQUEST["id"]);

	$cat = new Categorie($db,$_REQUEST["removecat"]);
	$result=$cat->del_type($product,"product");
}

//on veut ajouter une catégorie
if (isset($_REQUEST["catMere"]) && $_REQUEST["catMere"]>=0  && $user->rights->produit->creer)
{
	$product = new Product($db);
	if ($_REQUEST["ref"]) $result = $product->fetch('',$_REQUEST["ref"]);
	if ($_REQUEST["id"])  $result = $product->fetch($_REQUEST["id"]);

	$cat = new Categorie($db,$_REQUEST["catMere"]);
	$result=$cat->add_type($product,"product");
	if ($result >= 0)
	{
		$mesg='<div class="ok">'.$langs->trans("Added").'</div>';	
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("Error").' '.$cat->error.'</div>';	
	}
	
}



/*
* Creation de l'objet produit correspondant à l'id
*/
if ($_GET["id"] || $_GET["ref"])
{
	$product = new Product($db);
	if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);
	
	llxHeader("","",$langs->trans("CardProduct".$product->type));
}

$html = new Form($db);

/*
 * Fiche produit
 */
if ($_GET["id"] || $_GET["ref"])
{
  $head=product_prepare_head($product, $user);
  $titre=$langs->trans("CardProduct".$product->type);
  dolibarr_fiche_head($head, 'category', $titre);
  

	print '<table class="border" width="100%">';
	print "<tr>";
	// Reference
	print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
	print $html->showrefnav($product,'ref');
	print '</td>';
	print '</tr>';

	// Libelle
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
    print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
	print $product->getLibStatut(2);
    print '</td></tr>';

	print '</table>';

	print '</div>';
	

	if ($mesg) print($mesg);

	
    /*
     * Barre d'actions
     *
     */
    print '<div class="tabsAction">';
    if ($user->rights->categorie->creer)
    {
	    print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/fiche.php?action=create&amp;origin='.$product->id.'&type=0">'.$langs->trans("NewCat").'</a>';
    }
	print '</div>';


	// Formulaire ajout dans une categorie
	if ($user->rights->produit->creer)
	{
	  print '<br/>';
	  print '<form method="post" action="'.DOL_URL_ROOT.'/product/categorie.php?id='.$product->id.'">';
	  print '<table class="noborder" width="100%">';
	  print '<tr class="liste_titre"><td>';
	  print $langs->trans("ClassifyInCategory").' ';
	  print $html->select_all_categories(0,$categorie->id_mere).' <input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
	  print '</tr>';
	  print '</table>';
	  print '</form>';
	  print '<br/>';
	}


	$c = new Categorie($db);

	if ($_GET["id"])
	{
		$cats = $c->containing($_REQUEST["id"],"product");
	}

	if ($_GET["ref"])
	{
		$cats = $c->containing_ref($_REQUEST["ref"],"product");
	}

	if (sizeof($cats) > 0)
	{
		print_fiche_titre($langs->trans("ProductIsInCategories"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Categories").'</td></tr>';

		$var = true;
		foreach ($cats as $cat)
		{
			$ways = $cat->print_all_ways ();
			foreach ($ways as $way)
			{
				$var = ! $var;
				print "<tr ".$bc[$var].">";
				
				// Categorie
				print "<td>".$way."</td>";

				// Lien supprimer
				print '<td align="right">';
				if ($user->rights->produit->creer)
				{
					print "<a href= '".DOL_URL_ROOT."/product/categorie.php?id=".$product->id."&amp;removecat=".$cat->id."'>";
					print img_delete($langs->trans("DeleteFromCat")).' ';
					print $langs->trans("DeleteFromCat")."</a>";
				}
				else
				{
					print '&nbsp;';
				}
				print "</td>";

				print "</tr>\n";
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
		print $langs->trans("ProductHasNoCategory")."<br/>";
	}
	
}
$db->close();


llxFooter('$Date$ - $Revision$');
?>
