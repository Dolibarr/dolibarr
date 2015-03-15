<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load('bills');
$langs->load('compta');

// Security check
$id=(GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int'));
$action=GETPOST('action', 'alpha');
if ($user->societe_id) $socid=$user->societe_id;
$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") $objecttype = '';
$result = restrictedArea($user, 'facture', $id, $objecttype);

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

$object = new FactureRec($db);


/*
 * Actions
 */


// Create predefined invoice
if ($action == 'add')
{
	if (! GETPOST('titre'))
	{
		setEventMessage($langs->transnoentities("ErrorFieldRequired",$langs->trans("Title")), 'errors');
		$action = "create";
		$error++;
	}

	if (! $error)
	{
		$object->titre = GETPOST('titre', 'alpha');
		$object->note_private  = GETPOST('note_private');
		$object->usenewprice = GETPOST('usenewprice');

		if ($object->create($user, $id) > 0)
		{
			$id = $object->id;
			$action = '';
		}
		else
		{
			setEventMessage($object->error, 'errors');
			$action = "create";
		}
	}
}

// Suppression
if ($action == 'delete' && $user->rights->facture->supprimer)
{
	$object->fetch($id);
	$object->delete();
	$id = 0 ;
}



/*
 *	View
 */

llxHeader('',$langs->trans("RepeatableInvoices"),'ch-facture.html#s-fac-facture-rec');

$form = new Form($db);
$companystatic = new Societe($db);

/*
 * Create mode
 */
if ($action == 'create')
{
	print_fiche_titre($langs->trans("CreateRepeatableInvoice"));

	$object = new Facture($db);   // Source invoice
	$product_static = new Product($db);

	if ($object->fetch($id) > 0)
	{
		print '<form action="fiche-rec.php" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$object->id.'">';

		$rowspan=4;
		if (! empty($conf->projet->enabled) && $object->fk_project > 0) $rowspan++;

		print '<table class="border" width="100%">';

		$object->fetch_thirdparty();

		// Third party
		print '<tr><td>'.$langs->trans("Customer").'</td><td>'.$object->client->getNomUrl(1,'customer').'</td>';
		print '<td>';
		//print $langs->trans("NotePrivate");
		print '</td></tr>';

		// Title
		print '<tr><td class="fieldrequired">'.$langs->trans("Title").'</td><td>';
		print '<input class="flat" type="text" name="titre" size="24" value="'.$_POST["titre"].'">';
		print '</td>';

		// Note
		print '<td rowspan="'.$rowspan.'" valign="top">';
		print '<textarea class="flat" name="note_private" wrap="soft" cols="60" rows="'.ROWS_4.'"></textarea>';
		print '</td></tr>';

		// Author
		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->getFullName($langs)."</td></tr>";

		// Payment term
		print "<tr><td>".$langs->trans("PaymentConditions")."</td><td>";
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none');
		print "</td></tr>";

		// Payment mode
		print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		print "</td></tr>";

		// Project
		if (! empty($conf->projet->enabled) && $object->fk_project > 0)
		{
			print "<tr><td>".$langs->trans("Project")."</td><td>";
			if ($object->fk_project > 0)
			{
				$project = new Project($db);
				$project->fetch($object->fk_project);
				print $project->title;
			}
			print "</td></tr>";
		}

		print "</table>";

		print '<br>';

		$title = $langs->trans("ProductsAndServices");
		if (empty($conf->service->enabled))
			$title = $langs->trans("Products");
		else if (empty($conf->product->enabled))
			$title = $langs->trans("Services");

		print_titre($title);

		/*
		 * Invoice lines
		 */
		print '<table class="notopnoleftnoright" width="100%">';
		print '<tr><td colspan="3">';

		$sql = 'SELECT l.fk_product, l.product_type, l.label as custom_label, l.description, l.qty, l.rowid, l.tva_tx,';
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
		$sql.= " WHERE l.fk_facture = ".$object->id;
		$sql.= " ORDER BY l.rowid";

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0; $total = 0;

			echo '<table class="notopnoleftnoright" width="100%">';
			if ($num)
			{
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td align="center">'.$langs->trans("VAT").'</td>';
				print '<td align="center">'.$langs->trans("Qty").'</td>';
				print '<td>'.$langs->trans("ReductionShort").'</td>';
				print '<td align="right">'.$langs->trans("TotalHT").'</td>';
				print '<td align="right">'.$langs->trans("TotalVAT").'</td>';
				print '<td align="right">'.$langs->trans("TotalTTC").'</td>';
				print '<td align="right">'.$langs->trans("PriceUHT").'</td>';
				if (empty($conf->global->PRODUIT_MULTIPRICES)) print '<td align="right">'.$langs->trans("CurrentProductPrice").'</td>';
				print "</tr>\n";
			}
			$var=true;
			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				if ($objp->fk_product > 0)
				{
					$product = New Product($db);
					$product->fetch($objp->fk_product);
				}

				$var=!$var;
				print "<tr ".$bc[$var].">";

				// Show product and description
				$type=(isset($objp->product_type)?$objp->product_type:$objp->fk_product_type);

				if ($objp->fk_product > 0)
				{
					print '<td>';

					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->fetch($objp->fk_product);	// We need all information later
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.(! empty($objp->custom_label)?$objp->custom_label:$objp->product_label);
					$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($objp->description));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($db->jdate($objp->date_start), $db->jdate($objp->date_end));

					// Add description in form
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
						print (! empty($objp->description) && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';

					print '</td>';
				}
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($objp->custom_label)) {

						$text.= ' <strong>'.$objp->custom_label.'</strong>';
						print $form->textwithtooltip($text,dol_htmlentitiesbr($objp->description),3,'','',$i);

					} else {

						print $text.' '.nl2br($objp->description);
					}

					// Show range
					print_date_range($db->jdate($objp->date_start), $db->jdate($objp->date_end));

					print "</td>\n";
				}

				// Vat rate
				print '<td align="center">'.vatrate($objp->tva_tx).'%</td>';

				// Qty
				print '<td align="center">'.$objp->qty.'</td>';

				// Percent
				if ($objp->remise_percent > 0)
				{
					print '<td align="right">'.$objp->remise_percent." %</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}

				// Total HT
				print '<td align="right">'.price($objp->total_ht)."</td>\n";

				// Total VAT
				print '<td align="right">'.price($objp->total_vat)."</td>\n";

				// Total TTC
				print '<td align="right">'.price($objp->total_ttc)."</td>\n";

				// Total Unit price
				print '<td align="right">'.price($objp->subprice)."</td>\n";

				// Current price of product
				if (empty($conf->global->PRODUIT_MULTIPRICES))
				{
					if ($objp->fk_product > 0)
					{
						$flag_price_may_change++;
						$prodprice=$product_static->price;	// price HT
						print '<td align="right">'.price($prodprice)."</td>\n";
					}
					else
					{
						print '<td>&nbsp;</td>';
					}
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

		if ($flag_price_may_change)
		{
			print '<tr><td colspan="3" align="left">';
			print '<select name="usenewprice" class="flat">';
			print '<option value="0">'.$langs->trans("AlwaysUseFixedPrice").'</option>';
			print '<option value="1" disabled="disabled">'.$langs->trans("AlwaysUseNewPrice").'</option>';
			print '</select>';
			print '</td></tr>';
		}
		print '<tr><td colspan="3" align="center"><br><input type="submit" class="button" value="'.$langs->trans("Create").'"></td></tr>';
		print "</form>\n";
		print "</table>\n";

	}
	else
	{
		dol_print_error('',"Error, no invoice ".$object->id);
	}
}
else
{
	/*
	 * View mode
	 */
	if ($id > 0)
	{
		if ($object->fetch($id) > 0)
		{
			$object->fetch_thirdparty();

			$author = new User($db);
			$author->fetch($object->user_author);

			$head=array();
			$h=0;
			$head[$h][0] = $_SERVER["PHP_SELF"].'?id='.$object->id;
			$head[$h][1] = $langs->trans("CardBill");
			$head[$h][2] = 'card';

			dol_fiche_head($head, 'card', $langs->trans("PredefinedInvoices"),0,'bill');	// Add a div

			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="4">'.$object->titre.'</td>';

			print '<tr><td>'.$langs->trans("Customer").'</td>';
			print '<td colspan="3">'.$object->thirdparty->getNomUrl(1,'customer').'</td></tr>';

			print "<tr><td>".$langs->trans("Author").'</td><td colspan="3">'.$author->getFullName($langs)."</td></tr>";

			print '<tr><td>'.$langs->trans("AmountHT").'</td>';
			print '<td colspan="3"><b>'.price($object->total_ht,'',$langs,1,-1,-1,$conf->currency).'</b></td>';
			print '</tr>';

			print '<tr><td>'.$langs->trans("AmountVAT").'</td><td colspan="3">'.price($object->total_tva,'',$langs,1,-1,-1,$conf->currency).'</td>';
			print '</tr>';
			print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="3">'.price($object->total_ttc,'',$langs,1,-1,-1,$conf->currency).'</td>';
			print '</tr>';

			// Payment term
			print '<tr><td>'.$langs->trans("PaymentConditions").'</td><td colspan="3">';
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id,'none');
			print "</td></tr>";

			// Payment mode
			print '<tr><td>'.$langs->trans("PaymentMode").'</td><td colspan="3">';
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id,'none');
			print "</td></tr>";

			print '<tr><td>'.$langs->trans("Note").'</td><td colspan="3">'.nl2br($object->note_private)."</td></tr>";

			print "</table>";

			print '</div>';

			/*
			 * Lines
			 */

			$title = $langs->trans("ProductsAndServices");
			if (empty($conf->service->enabled))
				$title = $langs->trans("Products");
			else if (empty($conf->product->enabled))
				$title = $langs->trans("Services");

			print_titre($title);

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Description").'</td>';
			print '<td align="right">'.$langs->trans("Price").'</td>';
			print '<td align="center">'.$langs->trans("ReductionShort").'</td>';
			print '<td align="center">'.$langs->trans("Qty").'</td></tr>';

			$num = count($object->lines);
			$i = 0;
			$var=true;
			while ($i < $num)
			{
				$var=!$var;

				$product_static=new Product($db);

				// Show product and description
				$type=(isset($object->lines[$i]->product_type)?$object->lines[$i]->product_type:$object->lines[$i]->fk_product_type);
				// Try to enhance type detection using date_start and date_end for free lines when type
				// was not saved.
				if (! empty($objp->date_start)) $type=1;
				if (! empty($objp->date_end)) $type=1;

				// Show line
				print "<tr ".$bc[$var].">";
				if ($object->lines[$i]->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$object->lines[$i]->id.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->type=$object->lines[$i]->fk_product_type;
					$product_static->id=$object->lines[$i]->fk_product;
					$product_static->ref=$object->lines[$i]->product_ref;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.(! empty($object->lines[$i]->label)?$object->lines[$i]->label:$object->lines[$i]->product_label);
					$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($object->lines[$i]->desc));
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($object->lines[$i]->date_start, $object->lines[$i]->date_end);

					// Add description in form
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
						print (! empty($object->lines[$i]->desc) && $object->lines[$i]->desc!=$fac->lines[$i]->product_label)?'<br>'.dol_htmlentitiesbr($object->lines[$i]->desc):'';

					print '</td>';
				}
				else
				{
					print '<td>';

					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($object->lines[$i]->label)) {

						$text.= ' <strong>'.$object->lines[$i]->label.'</strong>';
						print $form->textwithtooltip($text,dol_htmlentitiesbr($object->lines[$i]->desc),3,'','',$i);

					} else {

						print $text.' '.nl2br($object->lines[$i]->desc);
					}

					// Show range
					print_date_range($object->lines[$i]->date_start, $object->lines[$i]->date_end);

					print '</td>';
				}
				print '<td align="right">'.price($object->lines[$i]->price).'</td>';
				print '<td align="center">'.$object->lines[$i]->remise_percent.' %</td>';
				print '<td align="center">'.$object->lines[$i]->qty.'</td></tr>'."\n";
				$i++;
			}
			print '</table>';



			/**
			 * Barre d'actions
			 */
			print '<div class="tabsAction">';

			if ($object->statut == Facture::STATUS_DRAFT && $user->rights->facture->creer)
			{
			    	echo '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;socid='.$object->thirdparty->id.'&amp;fac_rec='.$object->id.'">'.$langs->trans("CreateBill").'</a></div>';
			}

			if ($object->statut == Facture::STATUS_DRAFT && $user->rights->facture->supprimer)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
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
		$sql = "SELECT s.nom as name, s.rowid as socid, f.titre, f.total, f.rowid as facid";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		if ($socid)	$sql .= " AND s.rowid = ".$socid;

		//$sql .= " ORDER BY $sortfield $sortorder, rowid DESC ";
		//	$sql .= $db->plimit($limit + 1,$offset);

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			print_barre_liste($langs->trans("RepeatableInvoices"),$page,$_SERVER['PHP_SELF'],"&socid=$socid",$sortfield,$sortorder,'',$num);

			print $langs->trans("ToCreateAPredefinedInvoice").'<br><br>';

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
				$var=true;
				while ($i < min($num,$limit))
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					print "<tr ".$bc[$var].">";

					print '<td><a href="'.$_SERVER['PHP_SELF'].'?id='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->titre;
					print "</a></td>\n";

					$companystatic->id=$objp->socid;
					$companystatic->name=$objp->name;
					print '<td>'.$companystatic->getNomUrl(1,'customer').'</td>';

					print '<td align="right">'.price($objp->total).'</td>'."\n";

					echo '<td align="center">';

					if ($user->rights->facture->creer)
					{
                        echo '<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;socid='.$objp->socid.'&amp;fac_rec='.$objp->facid.'">';
                        echo  $langs->trans("CreateBill"),'</a>';
					}
					else
					{
					    echo "&nbsp;";
					}
					echo "</td></tr>\n";
					$i++;
				}
			}
			else print '<tr><td>'.$langs->trans("NoneF").'</td></tr>';

			print "</table>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}

}

llxFooter();

$db->close();
