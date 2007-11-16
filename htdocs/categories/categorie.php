<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		  <patrick.raguin@gmail.com>
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

/**
		\file       htdocs/categories/categorie.php
		\ingroup    category
		\brief      Page de l'onglet categories
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("categories");

$user->getrights();

$mesg=isset($_GET["mesg"])?'<div class="ok">'.$_GET["mesg"].'</div>':'';

if ($_REQUEST["socid"])
{
	if ($_REQUEST["typeid"] == 1) $type = 'fournisseur';
	if ($_REQUEST["typeid"] == 2) $type = 'societe';
	$objectid = isset($_REQUEST["socid"])?$_REQUEST["socid"]:'';
}
else if ($_REQUEST["id"] || $_REQUEST["ref"])
{
	$type = 'produit';
}
else
{
	accessforbidden();
}

// Sécurité d'accès client et commerciaux
$objectid = restrictedArea($user, $type, $objectid);



/*
*	Actions
*/

//Suppression d'un objet d'une catégorie
if ($_REQUEST["removecat"])
{
	if ($_REQUEST["socid"] && $user->rights->societe->creer)
	{
		$object = new Societe($db);
		$result = $object->fetch($_REQUEST["socid"]);
	}
	else if (($_REQUEST["id"] || $_REQUEST["ref"]) && $user->rights->produit->creer)
	{
		$object = new Product($db);
		if ($_REQUEST["ref"]) $result = $object->fetch('',$_REQUEST["ref"]);
		if ($_REQUEST["id"])  $result = $object->fetch($_REQUEST["id"]);
		$type = 'product';
	}
	$cat = new Categorie($db,$_REQUEST["removecat"]);
	$result=$cat->del_type($object,$type);
}

//Ajoute d'un objet dans une catégorie
if (isset($_REQUEST["catMere"]) && $_REQUEST["catMere"]>=0)
{
	if ($_REQUEST["socid"] && $user->rights->societe->creer)
	{
		$object = new Societe($db);
		$result = $object->fetch($_REQUEST["socid"]);
	}
	else if (($_REQUEST["id"] || $_REQUEST["ref"]) && $user->rights->produit->creer)
	{
		$object = new Product($db);
		if ($_REQUEST["ref"]) $result = $object->fetch('',$_REQUEST["ref"]);
		if ($_REQUEST["id"])  $result = $object->fetch($_REQUEST["id"]);
		$type = 'product';
	}
	
	$cat = new Categorie($db,$_REQUEST["catMere"]);
	$result=$cat->add_type($object,$type);
	if ($result >= 0)
	{
		$mesg='<div class="ok">'.$langs->trans("WasAddedSuccessfully").'</div>';	
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans("Error").' '.$cat->error.'</div>';	
	}
	
}

$html = new Form($db);

/*
 * Fiche categorie de client et/ou fournisseur
 */
if ($_GET["socid"])
{
	require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
	
	$langs->load("companies");
	
	/*
	 * Creation de l'objet client/fournisseur correspondant au socid
	 */
	 $soc = new Societe($db);
	 $result = $soc->fetch($_GET["socid"]);
	 llxHeader("","",$langs->trans("CardCompany".$soc->type));
	
	
	/*
	* Affichage onglets
	*/
	$head = societe_prepare_head($soc);

	dolibarr_fiche_head($head, 'category', $soc->nom);

	print '<table class="border" width="100%">';
	
	print '<tr><td width="30%">'.$langs->trans("Name").'</td><td width="70%" colspan="3">';
	print $soc->nom;
	print '</td></tr>';

	print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$soc->prefix_comm.'</td></tr>';

	if ($soc->client)
	{
		print '<tr><td>';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print $soc->code_client;
		if ($soc->check_codeclient() <> 0) print ' '.$langs->trans("WrongCustomerCode");
		print '</td></tr>';
	}
	
	if ($soc->fournisseur)
	{
		print '<tr><td>';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print $soc->code_fournisseur;
		if ($soc->check_codefournisseur() <> 0) print ' '.$langs->trans("WrongSupplierCode");
		print '</td></tr>';
	}

	print "<tr><td valign=\"top\">".$langs->trans('Address')."</td><td colspan=\"3\">".nl2br($soc->adresse)."</td></tr>";

	print '<tr><td>'.$langs->trans('Zip').'</td><td>'.$soc->cp."</td>";
	print '<td>'.$langs->trans('Town').'</td><td>'.$soc->ville."</td></tr>";
	if ($soc->pays) {
		print '<tr><td>'.$langs->trans('Country').'</td><td colspan="3">'.$soc->pays.'</td></tr>';
	}

	print '<tr><td>'.$langs->trans('Phone').'</td><td>'.dolibarr_print_phone($soc->tel,$soc->pays_code).'</td>';
	print '<td>'.$langs->trans('Fax').'</td><td>'.dolibarr_print_phone($soc->fax,$soc->pays_code).'</td></tr>';

	print '<tr><td>'.$langs->trans("Web")."</td><td colspan=\"3\"><a href=\"http://$soc->url\" target=\"_blank\">".$soc->url."</a>&nbsp;</td></tr>";

	// Assujeti à TVA ou pas
	print '<tr>';
	print '<td nowrap="nowrap">'.$langs->trans('VATIsUsed').'</td><td colspan="3">';
	print yn($soc->tva_assuj);
	print '</td>';
	print '</tr>';

	print '</table>';

	print '</div>';
	
	if ($mesg) print($mesg);
	
	if ($soc->client) formCategory($db,$soc,'societe',2);
	
	if ($soc->client && $soc->fournisseur) print '<br><br>';
	
	if ($soc->fournisseur) formCategory($db,$soc,'fournisseur',1);
}
else if ($_GET["id"] || $_GET["ref"])
{
	/*
	* Fiche categorie de produit
	*/
	require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
	require_once(DOL_DOCUMENT_ROOT."/product.class.php");
  
	// Produit
	$product = new Product($db);
	if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
	if ($_GET["id"]) $result = $product->fetch($_GET["id"]);
	
	llxHeader("","",$langs->trans("CardProduct".$product->type));


	$head=product_prepare_head($product, $user);
	$titre=$langs->trans("CardProduct".$product->type);
	dolibarr_fiche_head($head, 'category', $titre);
  

	print '<table class="border" width="100%">';
	print "<tr>";
	// Reference
	print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
	print $html->showrefnav($product,'ref','',1,'ref');
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
	
	formCategory($db,$product,'product',0);
}

	
/*
* Fonction Barre d'actions
*/
function formCategory($db,$object,$type,$typeid)
{
	global $user,$langs,$html,$bc;
	
	if ($typeid == 0) $title = $langs->trans("ProductsCategoriesShort");
	if ($typeid == 1) $title = $langs->trans("SuppliersCategoriesShort");
	if ($typeid == 2) $title = $langs->trans("CustomersCategoriesShort");
	if ($type == 'societe' || $type == 'fournisseur')
	{
		$nameId = 'socid';
	}
	else if ($type == 'product')
	{
		$nameId = 'id';
	}
	
	// Formulaire ajout dans une categorie
	if ($user->rights->societe->creer)
	{
		print '<br>';
		print_fiche_titre($title);
		print '<form method="post" action="'.DOL_URL_ROOT.'/categories/categorie.php?'.$nameId.'='.$object->id.'">';
		print '<input type="hidden" name="typeid" value="'.$typeid.'">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>';
		print $langs->trans("ClassifyInCategory").' ';
		print $html->select_all_categories($typeid).' <input type="submit" class="button" value="'.$langs->trans("Classify").'"></td>';
		if ($user->rights->categorie->creer)
		{
			print '<td align="right">';
			print '<a class="butAction" href="'.DOL_URL_ROOT.'/categories/fiche.php?action=create&amp;origin='.$object->id.'&type='.$typeid.'">'.$langs->trans("NewCat").'</a>';
			print '</td>';
		}
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br/>';
	}

	$c = new Categorie($db);
	$cats = $c->containing($object->id,$type,$typeid);

	if (sizeof($cats) > 0)
	{
		if ($typeid == 0) $title=$langs->trans("ProductIsInCategories");
		if ($typeid == 1) $title=$langs->trans("CompanyIsInSuppliersCategories");
		if ($typeid == 2) $title=$langs->trans("CompanyIsInCustomersCategories");
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2">'.$title.':</td></tr>';

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
				$module = ($type=='fournisseur'?'societe':$type);
				if ($user->rights->$module->creer)
				{
					print "<a href= '".DOL_URL_ROOT."/categories/categorie.php?".$nameId."=".$object->id."&amp;typeid=".$typeid."&amp;removecat=".$cat->id."'>";
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
		print "</table>\n";
	}
	else if($cats < 0)
	{
		print $langs->trans("ErrorUnknown");
	}
	else
	{
		print $langs->trans("CompanyHasNoCategory")."<br/>";
	}
}	

$db->close();

llxFooter('$Date$ - $Revision$');
?>
