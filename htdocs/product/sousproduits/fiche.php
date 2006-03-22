<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2006 Régis Houssin        <regis.houssin@cap-networks.com>
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
        \file       htdocs/product/fiche.php
        \ingroup    product
        \brief      Page de la fiche produit
        \version    $Revision$
*/

require("./pre.inc.php");

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
// if(MAIN_MODULE_CATEGORIE)
	require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");
	


$langs->load("bills");
$langs->load("product");


$mesg = '';
$ref=isset($_GET["ref"])?$_GET["ref"]:$_POST["ref"];
$key=isset($_GET["key"])?$_GET["key"]:$_POST["key"];
$catMere=isset($_GET["catMere"])?$_GET["catMere"]:$_POST["catMere"];
$id=isset($_GET["id"])?$_GET["id"]:$_POST["id"];
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$cancel=isset($_GET["cancel"])?$_GET["cancel"]:$_POST["cancel"];

if ($action <> 're-edit')
    {
        $product = new Product($db);
        if ($id) $result = $product->fetch($id);
		    if ($ref) $result = $product->fetch($ref);
		    if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
        if ($_GET["id"])  $result = $product->fetch($_GET["id"]);
    }

	
if (!$user->rights->produit->lire) accessforbidden();

$html = new Form($db);
$types[0] = $langs->trans("Product");
$types[1] = $langs->trans("Service");

// Action association d'un sousproduit
if ($action == 'add_prod' && 
    $cancel <> $langs->trans("Cancel") && 
    $user->rights->produit->creer)
{
  
		for($i=0;$i<$_POST["max_prod"];$i++)
		{
				// print "<br> : ".$_POST["prod_id_chk".$i];
				if($_POST["prod_id_chk".$i] != "")
				{
					if($product->add_sousproduit($id, $_POST["prod_id_".$i],$_POST["prod_qty_".$i]) > 0)
					{
						$action = 'edit';
					}
					else
					{
						$action = 're-edit';
						if($product->error == "isFatherOfThis")
							$mesg = $langs->trans("ErrorAssociationIsFatherOfThis");
					}
				}
				else
				{
					if($product->del_sousproduit($id, $_POST["prod_id_".$i]))
					{
						$action = 'edit';
					}
					else
					{
						$action = 're-edit';
					}
				
				
				}
     }
}
// action recherche des produits par mot-clé et/ou par catégorie
if($action == 'search' )
{
	$sql = 'SELECT p.rowid, p.ref, p.label, p.price, p.fk_product_type';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
	if($conf->categorie->enabled && $catMere != -1)
	{
		$sql .= ', '.MAIN_DB_PREFIX.'categorie_product as cp';
	}
	$sql .= " WHERE 1=1";
	if($key != "")
	{
		$sql .= " AND (p.ref like '%".$key."%'";
		$sql .= " OR p.label like '%".$key."%')";
	}
	if($conf->categorie->enabled && $catMere != -1)
	{
		$sql .= " AND p.rowid=cp.fk_product AND cp.fk_categorie ='".$catMere."'";
	}
	$sql .= " ORDER BY p.ref ASC ";
	// $sql .= $db->plimit($limit + 1 ,$offset);
	$resql = $db->query($sql) ;
}

if ($cancel == $langs->trans("Cancel"))
{
    $action = '';
    Header("Location: fiche.php?id=".$_POST["id"]);
    exit;
}

llxHeader("","",$langs->trans("ProductServiceCard"));
$html = new Form($db);


/*
 * Fiche produit
 */
if ($id || $ref)
{

    if ( $result )
    {

        if ($action <> 'edit' &&$action <> 'search' && $action <> 're-edit')
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
				      $hselected = $h;
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

            $nblignes=6;
            if ($product->type == 0 && $conf->stock->enabled) $nblignes++;
            if ($product->type == 1) $nblignes++;

            // Reference
            print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
            
            // Suivant/précédent
            $product->load_previous_next_ref();
	          $previous_ref = $product->ref_previous?'<a href="?ref='.$product->ref_previous.'">'.img_previous().'</a>':'';
	          $next_ref     = $product->ref_next?'<a href="?ref='.$product->ref_next.'">'.img_next().'</a>':'';
	          if ($previous_ref || $next_ref) print '<table class="nobordernopadding" width="100%"><tr class="nobordernopadding"><td class="nobordernopadding">';
	          print '<a href="?id='.$product->id.'">'.$product->ref.'</a>';
	          if ($previous_ref || $next_ref) print '</td><td class="nobordernopadding" align="center" width="20">'.$previous_ref.'</td><td class="nobordernopadding" align="center" width="20">'.$next_ref.'</td></tr></table>';
            
            print '</td>';
            
            if ($product->is_photo_available($conf->produit->dir_output))
            {
                // Photo
                print '<td valign="middle" align="center" rowspan="'.$nblignes.'">';
                $nbphoto=$product->show_photo($conf->produit->dir_output,1);
                print '</td>';
            }
            
            print '</tr>';

            // Libelle
            print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
            print '</tr>';
			
			$product->get_sousproduits_arbo ();
            print '<tr><td>'.$langs->trans("AssociatedProductsNumber").'</td><td>'.sizeof($product->get_arbo_each_prod()).'</td>';
            
          // associations sousproduits
		$prods_arbo = $product->get_arbo_each_prod();
		if(sizeof($prods_arbo) > 0)
		{
			print '<tr><td colspan="2">';
			print '<b>'.$langs->trans("ProductAssociationList").'</b><br>';
			foreach($prods_arbo as $key => $value)
			{
				// print $value[1].' :: '.$value[0].'<br>';
				print $value[0].'<br>';
			}
					 
		
            print '</td></tr>';
            }

            print "</table>\n";

            print "</div>\n";
        }
    }

    /*
     * Fiche en mode edition
     */
    if (($action == 'edit' || $action == 'search' || $action == 're-edit') && $user->rights->produit->creer)
    {

        print_fiche_titre($langs->trans('EditAssociate').' '.$types[$product->type].' : '.$product->ref, "");

        if ($mesg) {
            print '<br><div class="error">'.$mesg.'</div><br>';
        }

        print '<table class="border" width="100%">';

            print "<tr>";

            $nblignes=6;
            if ($product->type == 0 && $conf->stock->enabled) $nblignes++;
            if ($product->type == 1) $nblignes++;

            // Reference
            print '<td width="15%">'.$langs->trans("Ref").'</td><td>';
            print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$product->id.'">'.$product->ref.'</a>';
            print '</td>';
            
            if ($product->is_photo_available($conf->produit->dir_output))
            {
                // Photo
                print '<td valign="middle" align="center" rowspan="'.$nblignes.'">';
                $nbphoto=$product->show_photo($conf->produit->dir_output,1);
                print '</td>';
            }
            
            print '</tr>';

            // Libelle
            print '<tr><td>'.$langs->trans("Label").'</td><td>'.$product->libelle.'</td>';
            print '</tr>';
			// Nombre de sousproduits associés
			$product->get_sousproduits_arbo ();
            print '<tr><td>'.$langs->trans("AssociatedProductsNumber").'</td><td>'.sizeof($product->get_arbo_each_prod()).'</td>';
            print '</tr>';
			print '<tr><td colspan="2"><b>'.$langs->trans("ProductToAddSearch").'</b>';
			print '<table class="noborder">';
			print '<tr><td><form action="./fiche.php" method="post">';
			print $langs->trans("KeywordFilter");
			print '</td><td><input type="text" name="key" value="'.$key.'">';
			print '<input type="hidden" name="action" value="search">';
			print '<input type="hidden" name="id" value="'.$id.'">';
      print '</td></tr>';
      
      if($conf->categorie->enabled)
			{
			  print '<tr><td>'.$langs->trans("CategoryFilter");
			  print '</td><td>'.$html->select_all_categories($catMere).'</td></tr>';
			}
			
			print '<tr><td colspan="2"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
			print '</form>';
			print '<tr><td colspan="2"><br>';
			
			if($action == 'search')
			{
				print '<table class="border">';
				print '<tr>';
				print '<td><b>'.$langs->trans("Ref").'</b></td><td><b>'.$langs->trans("Label").'</b></td><td><b>'.$langs->trans("AddDel").'</b></td><td><b>'.$langs->trans("Quantity").'</b></td>';
				print '<form action="./fiche.php" method="post"';
				print '<input type="hidden" name="action" value="add_prod"';
				print '<input type="hidden" name="id" value="'.$id.'"';
				if ($resql)
				{
					$num = $db->num_rows($resql);
					$i=0;
					if($num == 0)
					print '<tr><td colspan="4">'.$langs->trans("NoMatchFound").'</td></tr>';
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						if($objp->rowid != $id)
						{
							print "\n<tr>";
							print '<td>'.$objp->ref.'</td>';
							print '<td>'.$objp->label.'</td>';
							if($product->is_sousproduit($id, $objp->rowid))
							{
								$addchecked = ' checked="true"';
								$qty=$product->is_sousproduit_qty;
							}
							else
							{
								$addchecked = '';
								$qty="1";
							}
							print '<td align="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'"><input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';
							print '<td align="center"><input type="text" size="3" name="prod_qty_'.$i.'" value="'.$qty.'"></td>';
							print '</td>';
							print '</tr>';
						}
						$i++;
					}	
					
				}
				else
				{
					dolibarr_print_error($db);
				}
				print '<input type="hidden" name="max_prod" value="'.$i.'">';
				if($num > 0)
				print '<tr><td colspan="2"><input type="submit" class="button" value="'.$langs->trans("Update").'"></td></tr>';
        		print '</table>';
			}
			
         print '</form></td></tr>';
         print '</table>';
		 print '</td></tr>';
         print '</table>';
    }
}


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print "\n<div class=\"tabsAction\">\n";

if ($action == '')
{
   
    if ( $user->rights->produit->creer)
    {
        print '<a class="tabAction" href="./fiche.php?action=edit&amp;id='.$product->id.'">'.$langs->trans("Edit").'</a>';

    }

}

print "\n</div><br>\n";



$db->close();

llxFooter('$Date$ - $Revision$');
?>
