<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
 *  \file       htdocs/product/fournisseurs.php
 *  \ingroup    product
 *  \brief      Page de l'onglet fournisseur de produits
 *  \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once DOL_DOCUMENT_ROOT."/fourn/fournisseur.product.class.php";

$langs->load("products");
$langs->load("suppliers");
$langs->load("bills");

// If socid provided by ajax company selector
if (! empty($_REQUEST['id_fourn_id']))
{
	$_GET['id_fourn'] = $_GET['id_fourn_id'];
	$_POST['id_fourn'] = $_POST['id_fourn_id'];
	$_REQUEST['id_fourn'] = $_REQUEST['id_fourn_id'];
}

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$id,'product','','',$fieldid);

$mesg = '';


/*
 * Actions
 */

if ($_GET["action"] == 'remove_pf')
{
	$product = new ProductFournisseur($db);
	if ($product->fetch($_GET["id"]) > 0)
	{
		if ($_GET["rowid"])
		{
			$result=$product->remove_product_fournisseur_price($_GET["rowid"]);
			$_GET["action"] = '';
			$mesg = '<div class="ok">'.$langs->trans("PriceRemoved").'.</div>';
		}
		else
		{
			// Deprecated. Should not occurs
			if ($product->remove_fournisseur($_GET["socid"]) > 0)
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
	$product = new ProductFournisseur($db);
	$result=$product->fetch($_REQUEST["id"]);
	if ($result > 0)
	{
		$db->begin();

		$error=0;
		if (! $_POST["ref_fourn"])
		{
			$error++;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")).'</div>';
		}
		if ($_POST["id_fourn"] <= 0)
		{
			//print "eee".$_POST["id_fourn"];
			$error++;
			$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Supplier")).'</div>';
		}

		if (! $error)
		{
			$ret=$product->add_fournisseur($user, $_POST["id_fourn"], $_POST["ref_fourn"]);
			if ($ret < 0)
			{
				$error++;
				$mesg='<div class="error">'.$product->error.'</div>';
			}

			if ($_POST["qty"])
			{
				if ($_POST["price"] >= 0)
				{
					$supplier=new Fournisseur($db);
					$result=$supplier->fetch($_POST["id_fourn"]);

					$ret=$product->update_buyprice($_POST["qty"], $_POST["price"], $user, $_POST["price_base_type"], $supplier);
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
		}

		if (! $error)
		{
			$db->commit();
			$_POST['action']='';
		}
		else
		{
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
$html = new Form($db);

if ($_GET["id"] || $_GET["ref"])
{
	if ($_GET["action"] <> 're-edit')
	{
		$product = new ProductFournisseur($db);
		$result = $product->fetch($_GET["id"],$_GET["ref"]);
		$result = $product->fetch_fourn_data($_REQUEST["id_fourn"]);
		//print 'eeeee'.$_GET["socid"];exit;
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
			$picto=($product->type==1?'service':'product');
			dol_fiche_head($head, 'suppliers', $titre, 0, $picto);


			print '<table class="border" width="100%">';

			// Reference
			print '<tr>';
			print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="2">';
			print $html->showrefnav($product,'ref','',1,'ref');
			print '</td>';
			print '</tr>';

			// Libelle
			print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$product->libelle.'</td></tr>';

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

			print "</div>\n";

			if ($mesg) print($mesg);


			// Form to add or update a price
			if (($_GET["action"] == 'add_price' || $_POST["action"] == 'updateprice' ) && ($user->rights->produit->creer || $user->rights->service->creer))
			{
				$langs->load("suppliers");

				if ($_GET["rowid"]) {
					$product->fetch_product_fournisseur_price($_GET["rowid"]);
					print_fiche_titre($langs->trans("ChangeSupplierPrice"));
				} else {
					print_fiche_titre($langs->trans("AddSupplierPrice"));
				}
				print '<table class="border" width="100%">';
				print '<form action="fournisseurs.php?id='.$product->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="updateprice">';

				print '<tr><td>'.$langs->trans("Supplier").'</td><td colspan="3">';
				if ($_GET["rowid"])
				{
					$supplier=new Fournisseur($db);
					$supplier->fetch($_GET["socid"]);
					print $supplier->getNomUrl(1);
					print '<input type="hidden" name="id_fourn" value="'.$_GET["socid"].'">';
					print '<input type="hidden" name="ref_fourn" value="'.$product->fourn_ref.'">';
					print '<input type="hidden" name="ref_fourn_price_id" value="'.$_GET["rowid"].'">';
				}
				else
				{
					$html=new Form($db);
					$html->select_societes($_POST["id_fourn"],'id_fourn','fournisseur=1',1);
				}
				print '</td></tr>';

				print '<tr><td>'.$langs->trans("SupplierRef").'</td><td colspan="3">';
				if ($_GET["rowid"])
				{
					print $product->fourn_ref;
				}
				else
				{
					print '<input class="flat" name="ref_fourn" size="12" value="'.($_POST["ref_fourn"]?$_POST["ref_fourn"]:$product->ref_fourn).'">';
				}
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>'.$langs->trans("QtyMin").'</td>';
				print '<td>';
				$quantity = $_REQUEST["qty"] ? $_REQUEST["qty"] : "1";
				if ($_GET["rowid"])
				{
					print '<input type="hidden" name="qty" value="'.$product->fourn_qty.'">';
					print $product->fourn_qty;
				}
				else
				{
					print '<input class="flat" name="qty" size="5" value="'.$quantity.'">';
				}
				print '</td>';
				print '<td>'.$langs->trans("PriceQtyMin").'</td>';
				print '<td><input class="flat" name="price" size="8" value="'.($_POST["price"]?$_POST["price"]:price($product->fourn_price)).'">';
				print '&nbsp;';
				print $html->select_PriceBaseType(($_POST["price_base_type"]?$_POST["price_base_type"]:$product->price_base_type), "price_base_type");
				print '</td>';
				print '</tr>';

				print '<tr><td colspan="4" align="center"><input class="button" type="submit" value="'.$langs->trans("Save").'">';
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

				if ($user->rights->produit->creer || $user->rights->service->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$product->id.'&amp;action=add_price">';
					print $langs->trans("AddSupplierPrice").'</a>';
				}

			}

			print "\n</div>\n";
			print '<br>';


			if ($user->rights->fournisseur->lire)
			{
				// Titre liste des fournisseurs
				print '<table class="noborder" width="100%">';
				if ($product->isproduct()) $nblignefour=4;
				else $nblignefour=4;
				print '<tr class="liste_titre"><td valign="top">';
				print $langs->trans("Suppliers").'</td>';
				print '<td>'.$langs->trans("SupplierRef").'</td>';
				print '<td align="center">'.$langs->trans("QtyMin").'</td>';
				print '<td align="right">'.$langs->trans("PriceQtyMinHT").'</td>';
				print '<td align="right">'.$langs->trans("UnitPriceHT").'</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';

				// Liste des fournisseurs
				$sql = "SELECT s.nom, s.rowid as socid,";
				$sql.= " pf.ref_fourn,";
				$sql.= " pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
				$sql.= ", ".MAIN_DB_PREFIX."product_fournisseur as pf";
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
				$sql.= " ON pf.rowid = pfp.fk_product_fournisseur";
				$sql.= " WHERE pf.fk_soc = s.rowid";
				$sql.= " AND s.entity = ".$conf->entity;
				$sql.= " AND pf.fk_product = ".$product->id;
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
						print '<td><a href="../fourn/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),'company').' '.$objp->nom.'</a></td>';

						// Fournisseur
						print '<td align="left">'.$objp->ref_fourn.'</td>';

						// Quantit�
						print '<td align="center">';
						print $objp->quantity;
						print '</td>';

						// Prix quantit�
						print '<td align="right">';
						print $objp->price?price($objp->price):"";
						print '</td>';

						// Prix unitaire
						print '<td align="right">';
						print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
						print '</td>';

						// Modifier-Supprimer
						print '<td align="center">';
						if ($user->rights->produit->creer || $user->rights->service->creer)
						{
							print '<a href="fournisseurs.php?id='.$product->id.'&amp;socid='.$objp->socid.'&amp;action=add_price&amp;rowid='.$objp->rowid.'">'.img_edit()."</a>";
							print '<a href="fournisseurs.php?id='.$product->id.'&amp;socid='.$objp->socid.'&amp;action=remove_pf&amp;rowid='.$objp->rowid.'">'.img_picto($langs->trans("Remove"),'disable.png').'</a>';
						}

						print '</td>';

						print '</tr>';

						$i++;
					}

					$db->free($resql);
				}
				else {
					dol_print_error($db);
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
