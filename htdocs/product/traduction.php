<?php
/* Copyright (C) 2005-2006 Regis Houssin  <regis.houssin@cap-networks.com>
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
* or see http://www.gnu.org/
*
* $Id$
* $Source$
*/

/**
        \file       htdocs/product/traduction.php
        \ingroup    product
        \brief      Page de traduction des produits
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");
$langs->load("bills");

$user->getrights('produit');

if (!$user->rights->produit->lire)
accessforbidden();


$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");


/*
 * Actions
 */

// retour a l'affichage des traduction si annulation
if ($_POST["cancel"] == $langs->trans("Cancel"))
{
    $_GET["action"] = '';
}

// Validation de l'ajout
if ($_POST["action"] == 'vadd' && 
    $_POST["cancel"] != $langs->trans("Cancel") && 
	$user->rights->produit->creer)
{
	$product = new Product($db);
	$product->fetch($_POST["id"]);
	$current_lang = $langs->getDefaultLang();

	// update de l'objet
	if ( $_POST["lang"] == $current_lang )
	{
		$product->libelle		= $_POST["libelle"];
		$product->description	= $_POST["desc"];
		$product->note			= $_POST["note"];
	}
	else
	{
		$product->multilangs[$_POST["lang"]]["libelle"]		= $_POST["libelle"];
		$product->multilangs[$_POST["lang"]]["description"]	= $_POST["desc"];
		$product->multilangs[$_POST["lang"]]["note"]		= $_POST["note"];
	}
	
	// sauvegarde en base
	if ( $product->update($product->id, $user) > 0 )
	{
		$_GET["action"] = '';
		$mesg = 'Fiche mise à jour';
	}
	else
	{
		$_GET["action"] = 'add';
		$mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
	}
}

// Validation de l'edition
if ($_POST["action"] == 'vedit' && 
    $_POST["cancel"] != $langs->trans("Cancel") && 
	$user->rights->produit->creer)
{
	$product = new Product($db);
	$product->fetch($_POST["id"]);
	$current_lang = $langs->getDefaultLang();
	
	foreach ( $product->multilangs as $key => $value ) // enregistrement des nouvelles valeurs dans l'objet
	{
		if ( $key == $current_lang )
		{
			$product->libelle		= $_POST["libelle-".$key];
			$product->description	= $_POST["desc-".$key];
			$product->note			= $_POST["note-".$key];
		}
		else
		{
			$value["libelle"]		= $_POST["libelle-".$key];
			$value["description"]	= $_POST["desc-".$key];
			$value["note"]			= $_POST["note-".$key];
		}
	}
	
	if ( $product->update($product->id, $user) > 0 )
	{
		$_GET["action"] = '';
		$mesg = 'Fiche mise à jour';
	}
	else
	{
		$_GET["action"] = 'edit';
		$mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
	}
}

$product = new Product($db);
if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
if ($_GET["id"]) $result = $product->fetch($_GET["id"]);

/*
 * Définition des onglets
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
if($conf->global->PRODUIT_MULTILANGS == 1)
{
	$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Translation");
	$hselected=$h;
	$h++;
}

if ($conf->fournisseur->enabled) {
    $head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
    $head[$h][1] = $langs->trans("Suppliers");
    $h++;
}

$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
$head[$h][1] = $langs->trans("Statistics");
$h++;

// sousproduits
if($conf->global->PRODUIT_SOUSPRODUITS == 1)
{
	$head[$h][0] = DOL_URL_ROOT."/product/sousproduits/fiche.php?id=".$product->id;
	$head[$h][1] = $langs->trans('AssociatedProducts');
	$h++;
}

$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
$head[$h][1] = $langs->trans("Referers");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
$head[$h][1] = $langs->trans('Documents');
$h++;

/*
 * Affichage
 */

llxHeader("","",$langs->trans("Translation"));

$titre=$langs->trans("CardProduct".$product->type);

if ( $_GET["action"] != 'edit') dolibarr_fiche_head($head, $hselected, $titre);

print '<table class="border" width="100%">';

// Reference
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
if ($_GET["action"] != 'edit')
{
	$product->load_previous_next_ref();
	$previous_ref = $product->ref_previous?'<a href="?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
	$next_ref     = $product->ref_next?'<a href="?ref='.$product->ref_next.'">'.img_next().'</a>':'';
	if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
	print '<a href="?id='.$product->id.'">'.$product->ref.'</a>';
	if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
}
else
{
	print '<a href="?id='.$product->id.'">'.$product->ref.'</a>';
}
print '</td>';
print '</tr>';
print '</table>';

if ($_GET["action"] == 'edit')
{
	print '<form action="" method="post">';
	print '<input type="hidden" name="action" value="vedit">';
	print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

	foreach ( $product->multilangs as $key => $value)
	{
		print "<br /><b><u>$key :</u></b><br />";
		print '<table class="border" width="100%">';
		print '<tr><td valign="top" width="15%">'.$langs->trans('Label').'</td><td><input name="libelle-'.$key.'" size="40" value="'.$product->multilangs[$key]["libelle"].'"></td></tr>';
		print '<tr><td valign="top" width="15%">'.$langs->trans('Description').'</td><td><textarea name="desc-'.$key.'" rows="3" cols="80">'.$product->multilangs[$key]["description"].'</textarea></td></tr>';
		print '<tr><td valign="top" width="15%">'.$langs->trans('Note').'</td><td><textarea name="note-'.$key.'" rows="3" cols="80">'.$product->multilangs[$key]["note"].'</textarea></td></tr>';
		print '</tr>';
		print '</table>';
	}

	print '<br /><table class="noborder" width="100%">';
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	print '</table>';
	print '</form>';

}
else
{
	foreach ( $product->multilangs as $key => $value)
	{
		print "<br /><b><u>$key :</u></b><br />";
		print '<table class="border" width="100%">';
		print '<tr><td width="15%">'.$langs->trans('Label').'</td><td>'.$product->multilangs[$key]["libelle"].'</td></tr>';
		print '<tr><td width="15%">'.$langs->trans('Description').'</td><td>'.$product->multilangs[$key]["description"].'</td></tr>';
		print '<tr><td width="15%">'.$langs->trans('Note').'</td><td>'.$product->multilangs[$key]["note"].'</td></tr>';
		print '</tr>';
		print '</table>';
	}
}

print "</div>\n";

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
    if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
        print '<a class="tabAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=edit&id='.$product->id.'">'.$langs->trans("Update").'</a>';
        print '<a class="tabAction" href="'.DOL_URL_ROOT.'/product/traduction.php?action=add&id='.$product->id.'">'.$langs->trans("Add").'</a>';
    }

print "\n</div>\n";


/*
 * Formulaire d'ajout de traduction
 */
if ($_GET["action"] == 'add' || $user->rights->produit->modifier)
{
	$langs_available = $langs->get_available_languages();
	$current_lang = $langs->getDefaultLang();
	
	// on construit la liste des traduction qui n'existe pas déjà
	$select = '<select class="flat" name="lang">';
	foreach ($langs_available as $value)
		if ( !array_key_exists($value, $product->multilangs) ) // si la traduction n'existe pas
			$select.= "<option value='$value'>$value</option>";
	$select.='</select>';

	print '<form action="" method="post">';
	print '<input type="hidden" name="action" value="vadd">';
	print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
	print '<table class="border" width="100%">';
	print '<tr><td valign="top" width="15%">'.$langs->trans('Translation').'</td><td>'.$select.'</td></tr>';;
	//print '<tr><td valign="top" width="15%">'.$langs->trans('Label').'</td><td><input name="libelle" size="40" value="'.$product->multilangs[$key]["libelle"].'"></td></tr>';
	//print '<tr><td valign="top" width="15%">'.$langs->trans('Description').'</td><td><textarea name="desc" rows="3" cols="80">'.$product->multilangs[$key]["description"].'</textarea></td></tr>';
	//print '<tr><td valign="top" width="15%">'.$langs->trans('Note').'</td><td><textarea name="note" rows="3" cols="80">'.$product->multilangs[$key]["note"].'</textarea></td></tr>';
	print '<tr><td valign="top" width="15%">'.$langs->trans('Label').'</td><td><input name="libelle" size="40"></td></tr>';
	print '<tr><td valign="top" width="15%">'.$langs->trans('Description').'</td><td><textarea name="desc" rows="3" cols="80"></textarea></td></tr>';
	print '<tr><td valign="top" width="15%">'.$langs->trans('Note').'</td><td><textarea name="note" rows="3" cols="80"></textarea></td></tr>';
	print '</tr>';
	print '</table>';
	print '<br /><table class="noborder" width="100%">';
	print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
	print '</table>';
	print '</form>';
	

}
llxFooter('$Date$ - $Revision$');
?>
