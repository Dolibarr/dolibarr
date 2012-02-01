<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/compta/facture/fiche-rec.php
 *	\ingroup    facture
 *	\brief      Page to show predefined invoice
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture-rec.class.php");
require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$langs->load('bills');

// Security check
$facid=GETPOST("facid");
$action=GETPOST("action");
if ($user->societe_id) $socid=$user->societe_id;
$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") $objecttype = '';
$result = restrictedArea($user, 'facture', $facid, $objecttype);

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


// Create predefined invoice
if ($_POST["action"] == 'add')
{
	$facturerec = new FactureRec($db);
	$facturerec->titre = $_POST["titre"];
	$facturerec->note  = $_POST["comment"];

	if ($facturerec->create($user,$facid) > 0)
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
	$facrec = new FactureRec($db);
	$facrec->fetch(GETPOST("facid"));
	$facrec->delete();
	$facid = 0 ;
}



/*
 *	View
 */

llxHeader('',$langs->trans("RepeatableInvoices"),'ch-facture.html#s-fac-facture-rec');

$form = new Form($db);

/*
 * Create mode
 */
if ($_GET["action"] == 'create')
{
	print_fiche_titre($langs->trans("CreateRepeatableInvoice"));

	if ($mesg) print $mesg.'<br>';

	$facture = new Facture($db);   // Source invoice
	$product_static=new Product($db);

	if ($facture->fetch($_GET["facid"]) > 0)
	{
		print '<form action="fiche-rec.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$facture->id.'">';

		print '<table class="border" width="100%">';

		$facture->fetch_thirdparty();

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

		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->getFullName($langs)."</td></tr>";

		print "<tr><td>".$langs->trans("PaymentConditions")."</td><td>";
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$facture->id,$facture->cond_reglement_id,'none');
		print "</td></tr>";

		print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$facture->id,$facture->mode_reglement_id,'none');
		print "</td></tr>";

		if ($conf->projet->enabled)
		{
			print "<tr><td>".$langs->trans("Project")."</td><td>";
			if ($facture->fk_project > 0)
			{
				$project = new Project($db);
				$project->fetch($facture->fk_project);
				print $project->title;
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
		 * Invoice lines
		 */
		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr><td colspan="3">';

		$sql = 'SELECT l.fk_product, l.product_type, l.description, l.qty, l.rowid, l.tva_tx,';
		$sql.= ' l.fk_remise_except,';
		$sql.= ' l.remise_percent, l.subprice, l.info_bits,';
		$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
		$sql.= ' l.date_start,';
		$sql.= ' l.date_end,';
		$sql.= ' l.product_type,';
		$sql.= ' p.ref, p.fk_product_type, p.label as product_label,';
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
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($db->jdate($objp->date_start),$db->jdate($objp->date_end));

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
					print_date_range($db->jdate($objp->date_start),$db->jdate($objp->date_end));

					print "</td>\n";
				}


				print '<TD align="center">'.$objp->tva_tx.' %</TD>';
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
{
	/*
	 * View mode
	 */

	if ($facid > 0)
	{
		$fac = new FactureRec($db);

		if ($fac->fetch($facid, $user->societe_id) > 0)
		{
			$soc = new Societe($db);
			$soc->fetch($fac->socid);
			$author = new User($db);
			$author->fetch($fac->user_author);


			dol_fiche_head($head, 'compta', $langs->trans("PredefinedInvoices"),0,'company');	// Add a div

			print '<table class="border" width="100%">';

			print '<tr><td>'.$langs->trans("Ref").'</td>';
			print '<td colspan="4">'.$fac->titre.'</td>';

			print '<tr><td>'.$langs->trans("Customer").'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print "<td>". $langs->trans("PaymentConditions") ." : ";
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->cond_reglement_id,'none');
			print "</td></tr>";

			print "<tr><td>".$langs->trans("Author")."</td><td colspan=\"3\">".$author->getFullName($langs)."</td>";

			if ($fac->remise_percent > 0)
			{
				print '<td rowspan="5" valign="top">';
			}
			else
			{
				print '<td rowspan="4" valign="top">';
			}

			print $langs->trans("PaymentMode") ." : ";
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$fac->id,$fac->mode_reglement_id,'none');
			print "</td></tr>";

			print '<tr><td>'.$langs->trans("AmountHT").'</td>';
			print '<td align="right" colspan="2"><b>'.price($fac->total_ht).'</b></td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

			print '<tr><td>'.$langs->trans("AmountVAT").'</td><td align="right" colspan="2">'.price($fac->total_tva).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
			print '<tr><td>'.$langs->trans("AmountTTC").'</td><td align="right" colspan="2">'.price($fac->total_ttc).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
			if ($fac->note)
			{
				print '<tr><td colspan="5">'.$langs->trans("Note").' : '.nl2br($fac->note)."</td></tr>";
			}

			print "</table>";

			print '</div>';

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

			$num = count($fac->lines);
			$i = 0;
			$var=True;
			while ($i < $num)
			{
				$var=!$var;

				$product_static=new Product($db);

				// Show product and description
				$type=$fac->lines[$i]->product_type?$fac->lines[$i]->product_type:$fac->lines[$i]->fk_product_type;
				// Try to enhance type detection using date_start and date_end for free lines when type
				// was not saved.
				if (! empty($objp->date_start)) $type=1;
				if (! empty($objp->date_end)) $type=1;

				// Show line
				print "<tr $bc[$var]>";
				if ($fac->lines[$i]->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$fac->lines[$i]->id.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->type=$fac->lines[$i]->fk_product_type;
					$product_static->id=$fac->lines[$i]->fk_product;
					$product_static->ref=$fac->lines[$i]->product_ref;
					$product_static->libelle=$fac->lines[$i]->libelle;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$fac->lines[$i]->libelle;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($fac->lines[$i]->desc));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($fac->lines[$i]->date_start,$fac->lines[$i]->date_end);

					// Add description in form
					if ($conf->global->PRODUIT_DESC_IN_FORM) print ($fac->lines[$i]->desc && $fac->lines[$i]->desc!=$fac->lines[$i]->libelle)?'<br>'.dol_htmlentitiesbr($fac->lines[$i]->desc):'';

					print '</td>';
				}
				else
				{
					print '<td>';

					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');
					print $text.' '.nl2br($fac->lines[$i]->desc);

					// Show range
					print_date_range($fac->lines[$i]->date_start,$fac->lines[$i]->date_end);

					print '</td>';
				}
				print "<td align=\"right\">".price($fac->lines[$i]->price)."</td>";
				print '<td align="center">'.$fac->lines[$i]->remise_percent.' %</td>';
				print "<td align=\"center\">".$fac->lines[$i]->qty."</td></tr>\n";
				$i++;
			}
			print '</table>';



			/**
			 * Barre d'actions
			 */
			print '<div class="tabsAction">';

			if ($fac->statut == 0 && $user->rights->facture->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&facid='.$fac->id.'">'.$langs->trans('Delete').'</a>';
			}

			print '</div>';
		}
		else
		{
			print $langs->trans("ErrorRecordNotFound");
		}
	}
	else
	{
		/*
		 *  List mode
		 */

		if ($user->rights->facture->lire)
		{

			$sql = "SELECT s.nom, s.rowid as socid, f.titre, f.total, f.rowid as facid";
			$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
			$sql.= " WHERE f.fk_soc = s.rowid";
			$sql.= " AND f.entity = ".$conf->entity;
			if ($socid)	$sql .= " AND s.rowid = ".$socid;

			//$sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
			//	$sql .= $db->plimit($limit + 1,$offset);

			$result = $db->query($sql);
		}
		if ($result)
		{
			$num = $db->num_rows($result);
			print_barre_liste($langs->trans("RepeatableInvoices"),$page,$_SERVER['PHP_SELF'],"&socid=$socid",$sortfield,$sortorder,'',$num);

			$i = 0;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Ref").'</td>';
			print_liste_field_titre($langs->trans("Company"),$_SERVER['PHP_SELF'],"s.nom","","&socid=$socid","",$sortfiled,$sortorder);
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

					print '<td><a href="'.$_SERVER['PHP_SELF'].'?facid='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->titre;
					print "</a></td>\n";
					print '<td><a href="../fiche.php?socid='.$objp->socid.'">'.$objp->nom.'</a></td>';

					print '<td align="right">'.price($objp->total).'</td>'."\n";

					if (! $objp->paye)
					{
						if ($objp->fk_statut == 0)
						{
							print '<td align="right">'.$langs->trans("Draft").'</td>';
						}
						else
						{
							print '<td align="right"><a href="facture.php?filtre=paye:0,fk_statut:1">'.$langs->trans("Validated").'</a></td>';
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

llxFooter();

$db->close();

?>
