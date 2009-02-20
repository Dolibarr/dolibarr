<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 \file       htdocs/compta/facture/fiche-rec.php
 \ingroup    facture
 \brief      Page d'affichage d'une facture récurrent
 \version    $Id$
 */

require("./pre.inc.php");
require_once("./facture-rec.class.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

if (!$user->rights->facture->lire)
accessforbidden();

$facid=isset($_GET["facid"])?$_GET["facid"]:$_POST["facid"];
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];

if ($page == -1)
{
	$page = 0 ;
}
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if ($sortorder == "")
$sortorder="DESC";

if ($sortfield == "")
$sortfield="f.datef";


// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * Actions
 */

// Ajout
if ($_POST["action"] == 'add')
{
	$facturerec = new FactureRec($db, $facid);
	$facturerec->titre = $_POST["titre"];

	if ($facturerec->create($user) > 0)
	{
		$facid = $facturerec->id;
		$action = '';
	}
	else
	{
		$_GET["action"] = "create";
		$_GET["facid"] = $_POST["facid"];
		$mesg = '<div class="error">'.$facturerec->error.'</div>';
	}
}

// Suppression
if ($_REQUEST["action"] == 'delete' && $user->rights->facture->supprimer)
{
	$fac = new FactureRec($db);
	$fac->delete($_REQUEST["facid"]);
	$facid = 0 ;
}



/*
 *	View
 */

llxHeader('',$langs->trans("RepeatableInvoices"),'ch-facture.html#s-fac-facture-rec');

$html = new Form($db);

/*********************************************************************
 *
 * Mode creation
 *
 ************************************************************************/
if ($_GET["action"] == 'create')
{
	print_titre($langs->trans("CreateRepeatableInvoice"));

	if ($mesg) print $mesg.'<br>';

	$facture = new Facture($db);

	if ($facture->fetch($_GET["facid"]) > 0)
	{
		print '<form action="fiche-rec.php" method="post">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$facture->id.'">';

		print '<table class="border" width="100%">';

		$facture->fetch_client();

		print '<tr><td>'.$langs->trans("Customer").' :</td><td>'.$facture->client->nom.'</td>';
		print '<td>'.$langs->trans("Comment").'</td></tr>';

		print '<tr><td>'.$langs->trans("Title").' :</td><td><input class="flat" type="text" name="titre" size="16"></td>';

		print '<td rowspan="4" valign="top">';
		print '<textarea class="flat" name="note" wrap="soft" cols="60" rows="'.ROWS_4.'"></textarea></td></tr>';

		print "<tr><td>".$langs->trans("Author")." :</td><td>".$user->fullname."</td></tr>";

		print "<tr><td>".$langs->trans("PaymentConditions")." :</td><td>";
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$facture->id,$facture->cond_reglement_id,'none');
		print "</td></tr>";

		print "<tr><td>".$langs->trans("PaymentMode")." :</td><td>";
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$facture->id,$facture->mode_reglement_id,'none');
		print "</td></tr>";

		print "<tr><td>".$langs->trans("Project")." :</td><td>";
		if ($facture->projetid > 0)
		{
			$proj = new Project($db);
			$proj->fetch($facture->projetid);
			print $proj->title;
		}
		print "</td></tr></table>";



		print '<br>';
		if ($conf->service->enabled) {
			print_titre($langs->trans("ProductsAndServices"));
		} else {
			print_titre($langs->trans("Products"));
		}

		/*
		 * Lignes de factures
		 *
		 */
		print '<table class="noborder" width="100%">';
		print '<tr><td colspan="3">';

		$sql = "SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_taux, l.remise_percent, l.subprice";
		$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l WHERE l.fk_facture = $facture->id ORDER BY l.rowid";

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0; $total = 0;

			echo '<table class="notopnoleftnoright" width="100%">';
			if ($num)
			{
				print "<tr class=\"liste_titre\">";
				print '<td width="54%">'.$langs->trans("Description").'</td>';
				print '<td width="8%" align="center">'.$langs->trans("VAT").'</td>';
				print '<td width="8%" align="center">'.$langs->trans("Qty").'</td>';
				print '<td width="8%" align="right">'.$langs->trans("ReductionShort").'</td>';
				print '<td width="12%" align="right">'.$langs->trans("PriceU").'</td>';
				print '<td width="12%" align="right">N.P.</td>';
				print "</tr>\n";
			}
			$var=True;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				if ($objp->fk_product > 0)
				{
					$product = New Product($db);
					$product->fetch($objp->fk_product);
				}

				$var=!$var;
				print "<TR $bc[$var]>";
				if ($objp->fk_product)
				{
					print '<td><a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">'.stripslashes(nl2br($objp->description)).'</a></td>';
				}
				else
				{
					print "<td>".nl2br($objp->description)."</TD>\n";
				}
				print '<TD align="center">'.$objp->tva_taux.' %</TD>';
				print '<TD align="center">'.$objp->qty.'</TD>';
				if ($objp->remise_percent > 0)
				{
					print '<td align="right">'.$objp->remise_percent." %</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}

				print '<TD align="right">'.price($objp->subprice)."</td>\n";

				if ($objp->fk_product > 0 && $objp->subprice <> $product->price)
				{
					print '<td align="right">'.price($product->price)."</td>\n";
					$flag_different_price++;
				}
				else
				{
					print '<td>&nbsp;</td>';
				}

				print "</tr>";

				$i++;
			}

			$db->free($result);

		}
		else
		{
			print $db->error();
		}
		print "</table>";

		print '</td></tr>';
		if ($flag_different_price)
		{
			print '<tr><td colspan="3" align="left">';
			print '<select name="deal_price">';
			if ($flag_different_price>1)
			{
				print '<option value="new">Prendre en compte les nouveaux prix</option>';
				print '<option value="old">Utiliser les anciens prix</option>';
			}
			else
			{
				print '<option value="new">Prendre en compte le nouveau prix</option>';
				print '<option value="old">Utiliser l\'ancien prix</option>';
			}
			print '</select>';
			print '</td></tr>';
		}
		print '<tr><td colspan="3" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
		print "</form>\n";
		print "</table>\n";

	}
	else
	{
		print "Erreur facture $facture->id inexistante";
	}
}
else
/* *************************************************************************** */
/*                                                                             */
/*                                                                             */
/*                                                                             */
/* *************************************************************************** */
{

	if ($facid > 0)
	{
		$fac = New FactureRec($db,0);

		if ( $fac->fetch($facid, $user->societe_id) > 0)
		{
			$soc = new Societe($db, $fac->socid);
			$soc->fetch($fac->socid);
			$author = new User($db);
			$author->id = $fac->user_author;
			$author->fetch();

			print_titre($langs->trans("PredefinedInvoices").': '.$fac->titre);
			print '<br>';

			/*
			 *   Facture
			 */
			print '<table class="border" width="100%">';
			print '<tr><td>'.$langs->trans("Customer").'</td>';
			print "<td colspan=\"3\">";
			print '<b><a href="../fiche.php?socid='.$soc->id.'">'.$soc->nom.'</a></b></td>';

			print "<td>". $langs->trans("PaymentConditions") ." : ";
			$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'none');
			print "</td></tr>";

			print "<tr><td>".$langs->trans("Author")."</td><td colspan=\"3\">$author->fullname</td>";

			if ($fac->remise_percent > 0)
			{
				print '<td rowspan="5" valign="top">';
			}
			else
			{
				print '<td rowspan="4" valign="top">';
			}

			print $langs->trans("PaymentMode") ." : ";
			$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'none');
			print "</td></tr>";

			print '<tr><td>'.$langs->trans("AmountHT").'</td>';
			print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
			print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

			print '<tr><td>'.$langs->trans("AmountVAT").'</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
			print '<tr><td>'.$langs->trans("AmountTTC").'</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
			if ($fac->note)
			{
				print '<tr><td colspan="5">'.$langs->trans("Note").' : '.nl2br($fac->note)."</td></tr>";
			}

			print "</table><br>";
			/*
			 * Lignes
			 *
			 */
			print_titre($langs->trans("Products"));

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td colspan="2">'.$langs->trans("Description").'</td>';
			print '<td align="right">'.$langs->trans("Price").'</td>';
			print '<td align="center">'.$langs->trans("ReductionShort").'</td>';
			print '<td align="center">'.$langs->trans("Qty").'</td></tr>';

			$num = sizeof($fac->lignes);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$var=!$var;
				if ($fac->lignes[$i]->produit_id > 0)
				{
					$prod = New Product($db);
					$prod->fetch($fac->lignes[$i]->produit_id);
					print "<tr $bc[$var]><td>";
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$prod->id.'">';
					print img_object($langs->trans("ShowProduct"),"product").' '.$prod->ref;
					print '</a>';
					print '</td>';
					print '<td>'.$fac->lignes[$i]->desc.'</td>';
				}
				else
				{
					print "<tr $bc[$var]><td>&nbsp;</td>";
					print '<td>'.$fac->lignes[$i]->desc.'</td>';
				}
				print "<td align=\"right\">".price($fac->lignes[$i]->price)."</TD>";
				print '<td align="center">'.$fac->lignes[$i]->remise_percent.' %</td>';
				print "<td align=\"center\">".$fac->lignes[$i]->qty."</td></tr>\n";
				$i++;
			}
			print '</table>';



			/**
			 * Barre d'actions
			 */
			print '<div class="tabsAction">';

			if ($fac->statut == 0 && $user->rights->facture->supprimer)
			{
				print '<a class="butActionDelete" href="fiche-rec.php?action=delete&facid='.$fac->id.'">'.$langs->trans('Delete').'</a>';
			}

			print '</div>';
		}
		else
		{
			print $langs->trans("ErrorRecordNotFound");
		}
	} else {
		/***************************************************************************
			*                                                                         *
			*                      Mode Liste                                         *
			*                                                                         *
			*                                                                         *
			***************************************************************************/

		if ($user->rights->facture->lire)
		{

			$sql = "SELECT s.nom, s.rowid as socid, f.titre, f.total, f.rowid as facid";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
			$sql.= " WHERE f.fk_soc = s.rowid";

			if ($socid)
			$sql .= " AND s.rowid = ".$socid;

			//$sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
			//	$sql .= $db->plimit($limit + 1,$offset);

			$result = $db->query($sql);
		}
		if ($result)
		{
			$num = $db->num_rows($result);
			print_barre_liste($langs->trans("RepeatableInvoices"),$page,"fiche-rec.php","&socid=$socid",$sortfield,$sortorder,'',$num);

			$i = 0;
			print "<table class=\"noborder\" width=\"100%\">";
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Ref").'</td>';
			print_liste_field_titre($langs->trans("Company"),"fiche-rec.php","s.nom","","&socid=$socid","",$sortfiled,$sortorder);
			print '</td><td align="right">'.$langs->trans("Amount").'</td>';
			print '<td>&nbsp;</td>';
			print "</td>\n";

			if ($num > 0)
			{
				$var=True;
				while ($i < min($num,$limit))
				{
					$objp = $db->fetch_object($result);
					$var=!$var;

					print "<tr $bc[$var]>";

					print '<td><a href="fiche-rec.php?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->titre;
					print "</a></td>\n";
					print '<td><a href="../fiche.php?socid='.$objp->socid.'">'.$objp->nom.'</a></td>';

					print "<td align=\"right\">".price($objp->total)."</td>\n";

					if (! $objp->paye)
					{
						if ($objp->fk_statut == 0)
						{
							print '<td align="center">brouillon</td>';
						}
						else
						{
							print '<td align="center"><a href="facture.php?filtre=paye:0,fk_statut:1">impayée</a></td>';
						}
					}
					else
					{
						print '<td>&nbsp;</td>';
					}

					print "</tr>\n";
					$i++;
				}
			}

			print "</table>";
			$db->free();
		}
		else
		{
			dol_print_error($db);
		}
	}

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
