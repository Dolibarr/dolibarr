<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
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
   \file       htdocs/product/fournisseurs.php
   \ingroup    product
   \brief      Page de l'onglet fournisseur de produits
   \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

$langs->load("products");
$langs->load("suppliers");
$langs->load("bills");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');
$mesg = '';

if (! $user->rights->produit->lire) accessforbidden();

/*
 * Actions
 */
 
if ($_GET["action"] == 'remove_fourn')
{
    $product = new Product($db);
    if( $product->fetch($_GET["id"]) )
    {
        if ($_GET["qty"]) { // On supprime une quantité
            if ($product->remove_price($user, $_GET["id_fourn"], $_GET["qty"]) > 0)
            {
                $_GET["action"] = '';
                $mesg = '<div class="ok">'.$langs->trans("PriceRemoved").'.</div>';
            }
            else
            {
                $_GET["action"] = '';
            }
        }
        else {              // On supprime un fournisseur
            if ($product->remove_fournisseur($user, $_GET["id_fourn"]) > 0)
            {
                $_GET["action"] = '';
                $mesg = '<div class="ok">'.$langs->trans("SupplierRemoved").'.</div>';
            }
            else
            {
                $_GET["action"] = '';
            }
        }
    }
}

if ($_POST["action"] == 'updateprice' && $_POST["cancel"] <> $langs->trans("Cancel"))
{

  $product = new Product($db);
  if( $product->fetch($_GET["id"]) )
    {
      $db->begin();
      
      $error=0;
      
      if ($_POST["ref_fourn"])
        {
	  $ret=$product->add_fournisseur($user, $_POST["id_fourn"], $_POST["ref_fourn"]);
	  if ($ret < 0)
	    {
	      $error++;
	      $mesg='<div class="error">'.$product->error.'</div>';
	    }
        }
      else
        {
	  $error++;
	  $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")).'</div>';
        }
      
      if ($_POST["qty"])
        {
	  if ($_POST["price"] >= 0)
	    {
	      $ret=$product->update_buyprice($_POST["id_fourn"], $_POST["qty"], $_POST["price"], $user);
	      if ($ret < 0)
		{
		  $error++;
		  $mesg='<div class="error">'.$product->error.'</div>';
		  if ($ret == -2)
		    {
		      $mesg='<div class="error">'.$langs->trans("ProductHasAlreadyReferenceInThisSupplier").'</div>';
		    }
		}
	    }
          else
	    {
	      $error++;
	      $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Price")).'</div>';
	    }
        }
      else
        {
	  $error++;
	  $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Qty")).'</div>';
        }
      
      if (! $error)
        {
	  $db->commit();
        }
      else {
	$db->rollback();
      }
    }
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
    $action = '';
    Header("Location: fournisseurs.php?id=".$_GET["id"]);
    exit;
}



/*
 * Affichage fiche
 */
 
if ($_GET["id"] || $_GET["ref"])
{
	if ($_GET["action"] <> 're-edit')
	{
		$product = new Product($db);
		if ($_GET["ref"]) $result = $product->fetch('',$_GET["ref"]);
		if ($_GET["id"]) $result = $product->fetch($_GET["id"]);
		llxHeader("","",$langs->trans("CardProduct".$product->type));
	}

	if ( $result )
	{ 
		
		if ($_GET["action"] <> 'edit' && $_GET["action"] <> 're-edit')
		{
			/*
			*  En mode visu
			*/

			$head=product_prepare_head($product, $user);
			$titre=$langs->trans("CardProduct".$product->type);
			dolibarr_fiche_head($head, 'suppliers', $titre);


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
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';
			
			// Prix
			print '<tr><td>'.$langs->trans("SellingPrice").'</td><td colspan="2">'.price($product->price).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans("Status").'</td><td colspan="2">';
			print $product->getLibStatut(2);
			print '</td></tr>';

			print '</table>';
			
			print "</div>\n";

			if ($mesg) print($mesg);


			// Formulaire ajout prix
			if ($_GET["action"] == 'add_price' && $user->rights->produit->creer)
			{
				$langs->load("suppliers");
				
				if ($_GET["id_fourn"]) {
					print_fiche_titre($langs->trans("ChangeSupplierPrice"));
				} else {
					print_fiche_titre($langs->trans("AddSupplierPrice"));
				}		  
				print '<table class="border" width="100%">';
				print '<form action="fournisseurs.php?id='.$product->id.'" method="post">';
				print '<input type="hidden" name="action" value="updateprice">';
				
				if ($_GET["id_fourn"]) {
					print '<input type="hidden" name="id_fourn" value="'.$_GET["id_fourn"].'">';
					$product->fetch_fourn_data($_GET["id_fourn"]);
					print '<input type="hidden" name="ref_fourn" value="'.$product->ref_fourn.'">';
				} else {
					print '<tr><td>'.$langs->trans("Supplier").'</td><td colspan="5">';
					$html=new Form($db);
					$html->select_societes('','id_fourn','fournisseur=1');
					print '</td></tr>';
				}
				
				print '<tr><td>'.$langs->trans("Ref").'</td><td>';
				print '<input class="flat" name="ref_fourn" size="12" value="">';
				print '</td>';
				print '<td>'.$langs->trans("QtyMin").'</td>';
				$quantity = $_GET["qty"] ? $_GET["qty"] : "1";
				print '<td><input class="flat" name="qty" size="5" value="'.$quantity.'"></td>';
				print '<td>'.$langs->trans("PriceHTQty").'</td>';
				print '<td><input class="flat" name="price" size="8" value="'.price($_GET["price"]).'"></td></tr>';
				
				print '<tr><td colspan="6" align="center"><input class="button" type="submit" value="'.$langs->trans("Save").'">';
				print '&nbsp; &nbsp;';
				print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
				print '</form>';
				print '</table>';
			}    
			
			/* ************************************************************************** */
			/*                                                                            */ 
			/* Barre d'action                                                             */ 
			/*                                                                            */ 
			/* ************************************************************************** */
			
			print "\n<div class=\"tabsAction\">\n";
			
			if ($_GET["action"] != 'add_price') {

				if ($user->rights->produit->creer)
				{
					print '<a class="tabAction" href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$product->id.'&amp;action=add_price">';
					print $langs->trans("AddSupplier").'</a>';
				}

			}
			
			print "\n</div>\n";
			print '<br>';


			if ($user->rights->fournisseur->lire) {
				// Titre liste des fournisseurs
				print '<table class="noborder" width="100%">';
				if ($product->isproduct()) $nblignefour=4;
				else $nblignefour=4;
				print '<tr class="liste_titre"><td valign="top">';
				print $langs->trans("Suppliers").'</td>';
				print '<td>'.$langs->trans("Ref").'</td>';
				print '<td align="center">'.$langs->trans("QtyMin").'</td>';
				print '<td align="right">'.$langs->trans("PriceHTQty").'</td>';
				print '<td align="right">'.$langs->trans("UnitPrice").'</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';

				// Liste des fournisseurs
				$sql = "SELECT s.nom, s.idp, pf.ref_fourn, pfp.price, pfp.quantity";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur as pf";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
				$sql.= " ON pf.fk_soc = pfp.fk_soc AND pf.fk_product = pfp.fk_product";
				$sql.= " WHERE pf.fk_soc = s.idp AND pf.fk_product = ".$product->id;
				$sql.= " ORDER BY lower(s.nom), pfp.quantity";

				$resql="";
				$resql=$db->query($sql);
				if ($resql)
				{
					$num = $db->num_rows($resql);
					$i = 0;

					$var=True;      
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						$var=!$var;
						
						print "<tr $bc[$var]>";
						print '<td><a href="../fourn/fiche.php?socid='.$objp->idp.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$objp->nom.'</a></td>';

						// Fournisseur
						print '<td align="left">'.$objp->ref_fourn.'</td>';

						// Quantité
						print '<td align="center">';
						print $objp->quantity;
						print '</td>';

						// Prix quantité
						print '<td align="right">';
						print $objp->price?price($objp->price):"";
						print '</td>';
						
						// Prix unitaire
						print '<td align="right">';
						print $objp->quantity?price($objp->price / $objp->quantity):"&nbsp;";
						print '</td>';
						
						// Modifier-Supprimer
						print '<td align="center">';
						if ($user->rights->produit->creer) {
							print '<a href="fournisseurs.php?id='.$product->id.'&amp;action=add_price&amp;id_fourn='.$objp->idp.'&amp;qty='.$objp->quantity.'&amp;price='.$objp->price.'">'.img_edit()."</a>";
							print '<a href="fournisseurs.php?id='.$product->id.'&amp;action=remove_fourn&amp;id_fourn='.$objp->idp.'&amp;qty='.$objp->quantity.'">';
							print img_disable($langs->trans("Remove")).'</a>';
						}

						print '</td>';
						
						print '</tr>';
						
						$i++;
					}
					
					$db->free($resql);
				}
				else {
					dolibarr_print_error($db);
				}
				
				print '</table>';
			}
		}
	}
}
else
{
	print $langs->trans("ErrorUnknown");
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
