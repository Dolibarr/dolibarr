<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under the	terms of the GNU General Public	License	as published by
 * the Free Software Foundation; either	version	2 of the License, or
 * (at your option) any later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59	Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file      htdocs/fourn/commande/dispatch.php
 *	\ingroup   commande
 *	\brief     Fiche de ventilation des commandes fournisseurs
 *	\version   $Id$
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/supplier_order/modules_commandefournisseur.php');
require_once(DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/fourn.lib.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.commande.class.php");
if ($conf->projet->enabled)	require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');

// Security check
$id = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $id,'');

if (empty($conf->stock->enabled))
{
	accessforbidden();
}

// Recuperation	de l'id	de projet
$projectid =	0;
if ($_GET["projectid"]) $projectid = $_GET["projectid"];

$mesg='';


/*
 * Actions
 */
if ($_POST["action"] ==	'dispatch' && $user->rights->fournisseur->commande->receptionner)
{
	$commande = new CommandeFournisseur($db);
	$commande->fetch($_GET["id"]);

	foreach($_POST as $key => $value)
	{
		if ( preg_match('/^product_([0-9]+)$/i', $key, $reg) )
		{
			$prod = "product_".$reg[1];
			$qty = "qty_".$reg[1];
			$ent = "entrepot_".$reg[1];
			$pu = "pu_".$reg[1];
			if ($_POST[$ent] > 0)
			{
				$result = $commande->DispatchProduct($user, $_POST[$prod], $_POST[$qty], $_POST[$ent], $_POST[$pu], $_POST["comment"]);
			}
			else
			{
				dol_syslog('No dispatch for line '.$key.' as no warehouse choosed');
			}
		}
	}

	if ($result > 0)
	{
		Header("Location: dispatch.php?id=".$_GET["id"]);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$langs->trans($commande->error).'</div>';
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$html =	new Form($db);
$warehouse_static = new Entrepot($db);

$now=dol_now();

$id = $_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	//if ($mesg) print $mesg.'<br>';

	$commande = new CommandeFournisseur($db);

	$result=$commande->fetch($_GET['id'],$_GET['ref']);
	if ($result >= 0)
	{
		$soc = new Societe($db);
		$soc->fetch($commande->socid);

		$author = new User($db);
		$author->fetch($commande->user_author_id);

		$head = ordersupplier_prepare_head($commande);

		$title=$langs->trans("SupplierOrder");
		dol_fiche_head($head, 'dispatch', $title, 0, 'order');

		/*
		 *	Commande
		 */
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="2">';
		print $html->showrefnav($commande,'ref','',1,'ref','ref');
		print '</td>';
		print '</tr>';

		// Fournisseur
		print '<tr><td>'.$langs->trans("Supplier")."</td>";
		print '<td colspan="2">'.$soc->getNomUrl(1,'supplier').'</td>';
		print '</tr>';

		// Statut
		print '<tr>';
		print '<td>'.$langs->trans("Status").'</td>';
		print '<td colspan="2">';
		print $commande->getLibStatut(4);
		print "</td></tr>";

		// Date
		if ($commande->methode_commande_id > 0)
		{
			print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
			if ($commande->date_commande)
			{
				print dol_print_date($commande->date_commande,"dayhourtext")."\n";
			}
			print "</td></tr>";

			if ($commande->methode_commande)
			{
				print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$commande->methode_commande.'</td></tr>';
			}
		}

		// Auteur
		print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
		print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
		print '</tr>';

		print "</table>";

		if ($mesg) print $mesg;
		else print '<br>';


		$disabled=1;
		if ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) $disabled=0;

		/*
		 * Lignes de commandes
		 */
		if ($commande->statut <= 2 || $commande->statut >= 6)
		{
			print $langs->trans("OrderStatusNotReadyToDispatch");
		}

		if ($commande->statut == 3 || $commande->statut == 4 || $commande->statut == 5)
		{
			$entrepot = new Entrepot($db);
			$listwarehouses=$entrepot->list_array(1);

			print '<form method="POST" action="dispatch.php?id='.$commande->id.'">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="dispatch">';
			print '<table class="noborder" width="100%">';

			$sql = "SELECT cfd.fk_product, sum(cfd.qty) as qty";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
			$sql.= " WHERE cfd.fk_commande = ".$commande->id;
			$sql.= " GROUP BY cfd.fk_product";

			$resql = $db->query($sql);
			if ($resql)
			{
				while ( $row = $db->fetch_row($resql) )
				{
					$products_dispatched[$row[0]] = $row[1];
				}
				$db->free($resql);
			}

			$sql = "SELECT l.ref,l.fk_product,l.description, l.subprice, sum(l.qty) as qty";
			$sql.= ", l.rowid";
			$sql.= ", p.label";
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as l";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product=p.rowid";
			$sql.= " WHERE l.fk_commande = ".$commande->id;
			$sql.= " GROUP BY l.fk_product";
			$sql.= " ORDER BY l.rowid";

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num)
				{
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans("Description").'</td>';

					print '<td align="right">'.$langs->trans("QtyOrdered").'</td>';
					print '<td align="right">'.$langs->trans("QtyDispatched").'</td>';
					print '<td align="right">'.$langs->trans("QtyDelivered").'</td>';
					print '<td align="right">'.$langs->trans("Warehouse").'</td>';
					print "</tr>\n";
				}

				$nbfreeproduct=0;
				$nbproduct=0;

				$var=true;
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);

					// On n'affiche pas les produits personnalises
					if (! $objp->fk_product > 0)
					{
						$nbfreeproduct++;
					}
					else
					{
						$nbproduct++;

						$remaintodispatch=($objp->qty - $products_dispatched[$objp->fk_product]);
						if ($remaintodispatch < 0) $remaintodispatch=0;

						$var=!$var;
						print "<tr ".$bc[$var].">";
						print '<td>';
						print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref.'</a>';
						print ' - '.$objp->label;
						if ($objp->description) print '<br>'.nl2br($objp->description);
						print '<input name="product_'.$i.'" type="hidden" value="'.$objp->fk_product.'">';
						print '<input name="pu_'.$i.'" type="hidden" value="'.$objp->subprice.'">';
						print "</td>\n";

						print '<td align="right">'.$objp->qty.'</td>';
						print '<td align="right">'.$products_dispatched[$objp->fk_product].'</td>';

						// Dispatch
						print '<td align="right"><input name="qty_'.$i.'" type="text" size="8" value="'.($remaintodispatch).'"></td>';

						// Warehouse
						print '<td align="right">';
						if (sizeof($listwarehouses))
						{
							print $html->selectarray("entrepot_".$i, $listwarehouses, '', $disabled, 0, 0, '', 0, 0, $disabled);
						}
						else
						{
							print $langs->trans("NoWarehouseDefined");
						}
						print "</td>\n";
						print "</tr>\n";
					}
					$i++;
				}
				$db->free($resql);
			}
			else
			{
				dol_print_error($db);
			}

			print "</table>\n";
			print "<br/>\n";

			if ($nbproduct)
			{
				print $langs->trans("Comment").' : ';
				print '<input type="text" size="60" maxlength="128" name="comment" value="';
				print $_POST["comment"]?$_POST["comment"]:$langs->trans("DispatchSupplierOrder",$commande->ref);
				// print ' / '.$commande->ref_supplier;	// Not yet available
				print '" class="flat"><br><br>';

				print '<center><input type="submit" class="button" value="'.$langs->trans("DispatchVerb").'"';
				if (sizeof($listwarehouses) <= 0) print ' disabled="true"';
				print '></center>';
			}
			if (! $nbproduct && $nbfreeproduct)
			{
				print $langs->trans("NoPredefinedProductToDispatch");
			}

			print '</form>';
		}

		// List of already dispatching
		$sql = "SELECT p.ref, p.label,";
		$sql.= " e.rowid as warehouse_id, e.label as entrepot,";
		$sql.= " cfd.fk_product, cfd.qty, cfd.rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."product as p,";
		$sql.= " ".MAIN_DB_PREFIX."commande_fournisseur_dispatch as cfd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as e ON cfd.fk_entrepot = e.rowid";
		$sql.= " WHERE cfd.fk_commande = ".$commande->id;
		$sql.= " AND cfd.fk_product = p.rowid";
		$sql.= " ORDER BY cfd.rowid ASC";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num > 0)
			{
				print "<br/>\n";

				print '<table class="noborder" width="100%">';

				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td align="right">'.$langs->trans("QtyDispatched").'</td>';
				print '<td align="right">'.$langs->trans("Warehouse").'</td>';
				print "</tr>\n";

				$var=false;

				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					print "<tr $bc[$var]>";
					print '<td>';
					print '<a href="'.DOL_URL_ROOT.'/product/fournisseurs.php?id='.$objp->fk_product.'">'.img_object($langs->trans("ShowProduct"),'product').' '.$objp->ref.'</a>';
					print ' - '.$objp->label;
					print "</td>\n";

					print '<td align="right">'.$objp->qty.'</td>';
					print '<td align="right">';
					$warehouse_static->id=$objp->warehouse_id;
					$warehouse_static->libelle=$objp->entrepot;
					print $warehouse_static->getNomUrl(1);
					print '</td>';
					print "</tr>\n";

					$i++;
					$var=!$var;
				}
				$db->free($resql);

				print "</table>\n";
			}
		}
		else
		{
			dol_print_error($db);
		}

		dol_fiche_end();


		/**
		 * Boutons actions
		 */
		if ($user->societe_id == 0 && $commande->statut	< 3	&& ($_GET["action"]	<> 'valid' || $_GET['action'] == 'builddoc'))
		{
			//print '<div	class="tabsAction">';

			//print "</div>";
		}
	}
	else
	{
		// Commande	non	trouvee
		dol_print_error($db);
	}
}

$db->close();

llxFooter('$Date$	- $Revision$');
?>
