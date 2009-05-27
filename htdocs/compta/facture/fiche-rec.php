<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/compta/facture/fiche-rec.php
 *	\ingroup    facture
 *	\brief      Page d'affichage d'une facture r�current
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once("./facture-rec.class.php");
require_once(DOL_DOCUMENT_ROOT."/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");

if (!$user->rights->facture->lire)
accessforbidden();

// Security check
$facid=isset($_GET["facid"])?$_GET["facid"]:$_POST["facid"];
$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'facture', $facid,'facture_rec');

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


/*
 * Actions
 */

// Ajout
if ($_POST["action"] == 'add')
{
	$facturerec = new FactureRec($db, $facid);
	$facturerec->titre = $_POST["titre"];
	$facturerec->note  = $_POST["comment"];

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
	print_fiche_titre($langs->trans("CreateRepeatableInvoice"));

	if ($mesg) print $mesg.'<br>';

	$facture = new Facture($db);
	$product_static=new Product($db);

	if ($facture->fetch($_GET["facid"]) > 0)
	{
		print '<form action="fiche-rec.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$facture->id.'">';

		print '<table class="border" width="100%">';

		$facture->fetch_client();

		print '<tr><td>'.$langs->trans("Customer").'</td><td>'.$facture->client->getNomUrl(1).'</td>';
		print '<td>';
		//print $langs->trans("NotePrivate");
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("Title").'</td><td>';
		print '<input class="flat" type="text" name="titre" size="16" value="'.$_POST["titre"].'">';
		print '</td>';

		print '<td rowspan="4" valign="top">';
		print '<textarea class="flat" name="note" wrap="soft" cols="60" rows="'.ROWS_4.'"></textarea>';
		print '</td></tr>';

		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->fullname."</td></tr>";

		print "<tr><td>".$langs->trans("PaymentConditions")."</td><td>";
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$facture->id,$facture->cond_reglement_id,'none');
		print "</td></tr>";

		print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$facture->id,$facture->mode_reglement_id,'none');
		print "</td></tr>";

		if ($conf->projet->enabled)
		{
			print "<tr><td>".$langs->trans("Project")."</td><td>";
			if ($facture->projetid > 0)
			{
				$proj = new Project($db);
				$proj->fetch($facture->projetid);
				print $proj->title;
			}
			print "</td></tr>";
		}

		print "</table>";



		print '<br>';
		if ($conf->service->enabled) {
			print_titre($langs->trans("ProductsAndServices"));
		} else {
			print_titre($langs->trans("Products"));
		}

		/*
		 * Lines de factures
		 */
		print '<table class="noborder" width="100%">';
		print '<tr><td colspan="3">';

		$sql = 'SELECT l.fk_product, l.product_type, l.description, l.qty, l.rowid, l.tva_taux,';
		$sql.= ' l.fk_remise_except,';
		$sql.= ' l.remise_percent, l.subprice, l.info_bits,';
		$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
		$sql.= ' '.$db->pdate('l.date_start').' as date_start,';
		$sql.= ' '.$db->pdate('l.date_end').' as date_end,';
		$sql.= ' l.product_type,';
		$sql.= ' p.ref, p.fk_product_type, p.label as product,';
		$sql.= ' p.description as product_desc';
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as l";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON l.fk_product = p.rowid";
		$sql.= " WHERE l.fk_facture = ".$facture->id;
		$sql.= " ORDER BY l.rowid";

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
				print "<tr $bc[$var]>";

				// Show product and description
				$type=$objp->product_type?$objp->product_type:$objp->fk_product_type;

				if ($objp->fk_product)
				{
					print '<td>';

					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->type=$objp->fk_product_type;
					$product_static->id=$objp->fk_product;
					$product_static->ref=$objp->ref;
					$product_static->libelle=$objp->product_label;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$objp->product_label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
					print $html->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM) print ($objp->description && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';

					print '</td>';
				}
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($objp->description);

					// Show range
					print_date_range($objp->date_start,$objp->date_end);

					print "</td>\n";
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
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';

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
			 * Lines
			 */
			if ($conf->service->enabled) {
				print_titre($langs->trans("ProductsAndServices"));
			} else {
				print_titre($langs->trans("Products"));
			}

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Description").'</td>';
			print '<td align="right">'.$langs->trans("Price").'</td>';
			print '<td align="center">'.$langs->trans("ReductionShort").'</td>';
			print '<td align="center">'.$langs->trans("Qty").'</td></tr>';

			$num = sizeof($fac->lignes);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$var=!$var;

				$product_static=new Product($db);

				// Show product and description
				$type=$fac->lignes[$i]->product_type?$fac->lignes[$i]->product_type:$fac->lignes[$i]->fk_product_type;
				// Try to enhance type detection using date_start and date_end for free lines when type
				// was not saved.
				if (! empty($objp->date_start)) $type=1;
				if (! empty($objp->date_end)) $type=1;

				// Show line
				print "<tr $bc[$var]>";
				if ($fac->lignes[$i]->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$fac->lignes[$i]->id.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->type=$fac->lignes[$i]->fk_product_type;
					$product_static->id=$fac->lignes[$i]->fk_product;
					$product_static->ref=$fac->lignes[$i]->product_ref;
					$product_static->libelle=$fac->lignes[$i]->libelle;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$fac->lignes[$i]->libelle;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($fac->lignes[$i]->desc));
					print $html->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($fac->lignes[$i]->date_start,$fac->lignes[$i]->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM) print ($fac->lignes[$i]->desc && $fac->lignes[$i]->desc!=$fac->lignes[$i]->libelle)?'<br>'.dol_htmlentitiesbr($fac->lignes[$i]->desc):'';

					print '</td>';
				}
				else
				{
					print '<td>';

					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($fac->lignes[$i]->desc);

					// Show range
					print_date_range($fac->lignes[$i]->date_start,$fac->lignes[$i]->date_end);

					print '</td>';
				}
				print "<td align=\"right\">".price($fac->lignes[$i]->price)."</td>";
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
			$sql.= " AND s.entity = ".$conf->entity;

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
							print '<td align="center"><a href="facture.php?filtre=paye:0,fk_statut:1">impay�e</a></td>';
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
