<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
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
        \file       htdocs/product/price.php
        \ingroup    product
        \brief      Page de la fiche produit
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

if ($_POST["action"] == 'update_price' && 
    $_POST["cancel"] <> $langs->trans("Cancel") && $user->rights->produit->creer)
{
  $product = new Product($db);

  $result = $product->fetch($_GET["id"]);

  $product->price = ereg_replace(" ","",$_POST["price"]);
	// MultiPrix
	if($conf->global->PRODUIT_MULTIPRICES == 1)
	{
		for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
		{
				if($_POST["price_".$i])
					$product->multiprices["$i"]=ereg_replace(" ","",$_POST["price_".$i]);
				else
					$product->multiprices["$i"] = "";
		}
	}

  if ( $product->update_price($product->id, $user) > 0 )
    {
      $_GET["action"] = '';
      $mesg = 'Fiche mise à jour';
    }
  else
    {
      $_GET["action"] = 'edit_price';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $product->mesg_error;
    }
}



/*
 * Affiche historique prix
 */

llxHeader("","",$langs->trans("Price"));

$product = new Product($db);
if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
if ($_GET["id"]) $result = $product->fetch($_GET["id"]);


$h=0;

$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
$head[$h][1] = $langs->trans("Card");
$h++;

$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
$head[$h][1] = $langs->trans("Price");
$hselected=$h;
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

$titre=$langs->trans("CardProduct".$product->type);
dolibarr_fiche_head($head, $hselected, $titre);

print '<table class="border" width="100%">';

// Reference
print '<tr>';
print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
$product->load_previous_next_ref();
$previous_ref = $product->ref_previous?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
$next_ref     = $product->ref_next?'<a href="'.$_SERVER["PHP_SELF"].'?ref='.$product->ref_next.'">'.img_next().'</a>':'';
if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
print '</td>';
print '</tr>';

// Libelle
print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td>';
print '</tr>';


// MultiPrix
if($conf->global->PRODUIT_MULTIPRICES == 1)
{
	print '<tr><td>'.$langs->trans("SellingPrice").' 1</td><td colspan="2">'.price($product->price).'</td></tr>';
	for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
	{
			print '<tr><td>'.$langs->trans("SellingPrice").' '.$i.'</td><td>'.price($product->multiprices["$i"]).'</td>';
            print '</tr>';
	}
}
// Prix
else
	print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">'.price($product->price).'</td></tr>';
// Statut
print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
print $product->getLibStatut(2);
print '</td></tr>';

print "</table>\n";
      
print "</div>\n";



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($_GET["action"] == '')
{
    if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
        print '<a class="tabAction" href="'.DOL_URL_ROOT.'/product/price.php?action=edit_price&amp;id='.$product->id.'">'.$langs->trans("UpdatePrice").'</a>';
    }
}

print "\n</div>\n";


/*
 * Edition du prix
 */
if ($_GET["action"] == 'edit_price' && $user->rights->produit->creer)
{
  print_fiche_titre($langs->trans("NewPrice"));

  print '<form action="price.php?id='.$product->id.'" method="post">';
  print '<input type="hidden" name="action" value="update_price">';
  print '<input type="hidden" name="id" value="'.$product->id.'">';
  print '<table class="border" width="100%">';
  if($conf->global->PRODUIT_MULTIPRICES == 1)
  print '<tr><td width="15%">'.$langs->trans('SellingPrice').' 1</td><td><input name="price" size="10" value="'.price($product->price).'"></td></tr>';
  else
  print '<tr><td width="15%">'.$langs->trans('SellingPrice').'</td><td><input name="price" size="10" value="'.price($product->price).'"></td></tr>';
  print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
  print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
  print '</table>';
  print '</form>';
  // MultiPrix
	if($conf->global->PRODUIT_MULTIPRICES == 1)
	{
		for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
		{
				print '<form action="price.php?id='.$product->id.'" method="post">';
				print '<input type="hidden" name="action" value="update_price">';
				print '<input type="hidden" name="id" value="'.$product->id.'">';
				print '<table class="border" width="100%">';
				print '<tr><td width="15%">'.$langs->trans("SellingPrice").' '.$i.'</td><td><input name="price_'.$i.'" size="10" value="'.price($product->multiprices["$i"]).'"></td>';
				print '</tr>';
				print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
  				print '</table>';
  				print '</form>';
		}
	}
}


// Liste des evolutions du prix
$sql = "SELECT p.rowid, p.price, ";
if($conf->global->PRODUIT_MULTIPRICES == 1)
{
	$sql .= "p.price_level, ";
	$sql .= $db->pdate("p.date_price")." as dp, u.rowid as user_id, u.login";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_price as p, llx_user as u";
	$sql .= " WHERE fk_product = ".$product->id;
	$sql .= " AND p.fk_user_author = u.rowid ";
	$sql .= " ORDER BY p.price_level ASC ";
	$sql .= ",p.date_price DESC ";
}
else
{
	$sql .= $db->pdate("p.date_price")." as dp, u.rowid as user_id, u.login";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_price as p, llx_user as u";
	$sql .= " WHERE fk_product = ".$product->id;
	$sql .= " AND p.fk_user_author = u.rowid ";
	$sql .= " ORDER BY p.date_price DESC ";
}

	
	
	
	
$sql .= $db->plimit();
$result = $db->query($sql) ;

if ($result)
{
    $num = $db->num_rows($result);

    if (! $num)
    {
        $db->free($result) ;

        // Il doit au moins y avoir la ligne de prix initial.
        // On l'ajoute donc pour remettre à niveau (pb vieilles versions)
        $product->update_price($product->id, $user);

        $result = $db->query($sql) ;
        $num = $db->num_rows($result);
    }

    if ($num > 0)
    {
        print '<br>';

        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("AppliedPricesFrom").'</td>';
		if($conf->global->PRODUIT_MULTIPRICES == 1)
			print '<td>'.$langs->trans("MultiPriceLevelsName").'</td>';
        print '<td>'.$langs->trans("Price").'</td>';
        print '<td>'.$langs->trans("ChangedBy").'</td>';
        print '</tr>';

        $var=True;
        $i = 0;
        while ($i < $num)
        {
            $objp = $db->fetch_object($result);
            $var=!$var;
            print "<tr $bc[$var]>";
        
            // Date
            print "<td>".dolibarr_print_date($objp->dp,"%d %b %Y %H:%M:%S")."</td>";
            
			// catégorie de Prix
			if($conf->global->PRODUIT_MULTIPRICES == 1)
				print "<td>".$objp->price_level."</td>";
			
            // Prix
            print "<td>".price($objp->price)."</td>";

            // User
            print '<td><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$objp->user_id.'">'.img_object($langs->trans("ShowUser"),'user').' '.$objp->login.'</a></td>';
            print "</tr>\n";
            $i++;
        }
        $db->free($result);
        print "</table>";
        print "<br>";
    }
}
else
{
    dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
