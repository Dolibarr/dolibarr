<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Vinicius Nogueira       <viniciusvgn@gmail.com>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/fourn/facture/impayees.php
 *		\ingroup    facture
 *		\brief      Page to list all unpaid invoices
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

if (!$user->rights->fournisseur->facture->lire) accessforbidden();

$langs->loadLangs(array("companies", "bills"));

$socid = GETPOST('socid', 'int');
$option = GETPOST('option');

// Security check
if ($user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');

$search_ref = GETPOST('search_ref', 'alpha');
$search_ref_supplier = GETPOST('search_ref_supplier', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
$search_amount_no_tax = GETPOST('search_amount_no_tax', 'alpha');
$search_amount_all_tax = GETPOST('search_amount_all_tax', 'alpha');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "f.date_lim_reglement";
if (!$sortorder) $sortorder = "ASC";

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
{
	$search_ref = "";
	$search_ref_supplier = "";
	$search_company = "";
	$search_amount_no_tax = "";
	$search_amount_all_tax = "";
}

/*
 * View
 */

$now = dol_now();

llxHeader('', $langs->trans("BillsSuppliersUnpaid"));

$title = $langs->trans("BillsSuppliersUnpaid");

$facturestatic = new FactureFournisseur($db);
$companystatic = new Societe($db);

if ($user->rights->fournisseur->facture->lire)
{
	$sql = "SELECT s.rowid as socid, s.nom as name,";
	$sql .= " f.rowid, f.ref, f.ref_supplier, f.total_ht, f.total_ttc,";
	$sql .= " f.datef as df, f.date_lim_reglement as datelimite, ";
	$sql .= " f.paye as paye, f.rowid as facid, f.fk_statut";
	$sql .= " ,sum(pf.amount) as am";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= ",".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf ON f.rowid=pf.fk_facturefourn ";
	$sql .= " WHERE f.entity = ".$conf->entity;
	$sql .= " AND f.fk_soc = s.rowid";
	$sql .= " AND f.paye = 0 AND f.fk_statut = 1";
	if ($option == 'late') $sql .= " AND f.date_lim_reglement < '".$db->idate(dol_now() - $conf->facture->fournisseur->warning_delay)."'";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;

	if (GETPOST('filtre'))
	{
		$filtrearr = explode(",", GETPOST('filtre'));
		foreach ($filtrearr as $fil)
		{
			$filt = explode(":", $fil);
			$sql .= " AND ".$filt[0]." = ".$filt[1];
		}
	}

	if ($search_ref)
	{
		$sql .= " AND f.ref LIKE '%".$search_ref."%'";
	}
	if ($search_ref_supplier)
	{
		$sql .= " AND f.ref_supplier LIKE '%".$search_ref_supplier."%'";
	}

	if ($search_company)
	{
		$sql .= " AND s.nom LIKE '%".$search_company."%'";
	}

	if ($search_amount_no_tax)
	{
		$sql .= " AND f.total_ht = '".$search_amount_no_tax."'";
	}

	if ($search_amount_all_tax)
	{
		$sql .= " AND f.total_ttc = '".$search_amount_all_tax."'";
	}

	if (dol_strlen(GETPOST('sf_re')) > 0)
	{
		$sql .= " AND f.ref_supplier LIKE '%".$db->escape(GETPOST('sf_re'))."%'";
	}

	$sql .= " GROUP BY s.rowid, s.nom, f.rowid, f.ref, f.ref_supplier, f.total_ht, f.total_ttc, f.datef, f.date_lim_reglement, f.paye, f.fk_statut";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
	$sql .= $db->order($sortfield, $sortorder);
	if (!in_array("f.ref_supplier", explode(',', $sortfield))) $sql .= ", f.ref_supplier DESC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($socid)
		{
			$soc = new Societe($db);
			$soc->fetch($socid);
		}

		$param = '';
		if ($socid) $param .= "&socid=".$socid;

		if ($search_ref)         	$param .= '&amp;search_ref='.urlencode($search_ref);
		if ($search_ref_supplier)	$param .= '&amp;search_ref_supplier='.urlencode($search_ref_supplier);
		if ($search_company)     	$param .= '&amp;search_company='.urlencode($search_company);
		if ($search_amount_no_tax)	$param .= '&amp;search_amount_no_tax='.urlencode($search_amount_no_tax);
		if ($search_amount_all_tax) $param .= '&amp;search_amount_all_tax='.urlencode($search_amount_all_tax);

		$param .= ($option ? "&option=".$option : "");
		if (!empty($late)) $param .= '&late='.urlencode($late);
		$urlsource = str_replace('&amp;', '&', $param);

		$titre = ($socid ? $langs->trans("BillsSuppliersUnpaidForCompany", $soc->name) : $langs->trans("BillsSuppliersUnpaid"));

		if ($option == 'late') $titre .= ' ('.$langs->trans("Late").')';
	    else $titre .= ' ('.$langs->trans("All").')';

		$link = '';
		if (empty($option)) $link = '<a href="'.$_SERVER["PHP_SELF"].'?option=late'.($socid ? '&socid='.$socid : '').'">'.$langs->trans("ShowUnpaidLateOnly").'</a>';
		elseif ($option == 'late') $link = '<a href="'.$_SERVER["PHP_SELF"].'?'.($socid ? '&socid='.$socid : '').'">'.$langs->trans("ShowUnpaidAll").'</a>';
		print load_fiche_titre($titre, $link);

		print_barre_liste('', '', $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', 0); // We don't want pagination on this page
		$i = 0;
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

		print '<table class="liste centpercent">';
		print '<tr class="liste_titre">';
		print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "f.rowid", "", $param, "", $sortfield, $sortorder);
		print_liste_field_titre("RefSupplier", $_SERVER["PHP_SELF"], "f.ref_supplier", "", $param, "", $sortfield, $sortorder);
		print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "f.datef", "", $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("DateDue", $_SERVER["PHP_SELF"], "f.date_lim_reglement", "", $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
		print_liste_field_titre("AmountHT", $_SERVER["PHP_SELF"], "f.total_ht", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("AmountTTC", $_SERVER["PHP_SELF"], "f.total_ttc", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("AlreadyPaid", $_SERVER["PHP_SELF"], "am", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "fk_statut,paye,am", "", $param, '', $sortfield, $sortorder, 'right ');
		print "</tr>\n";

		// Lines with filter fields
		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		print '<input class="flat" size="8" type="text" name="search_ref" value="'.$search_ref.'"></td>';
		print '<td class="liste_titre">';
		print '<input class="flat" size="8" type="text" name="search_ref_supplier" value="'.$search_ref_supplier.'"></td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="6" name="search_company" value="'.$search_company.'">';
		print '</td><td class="liste_titre right">';
		print '<input class="flat" type="text" size="8" name="search_amount_no_tax" value="'.$search_amount_no_tax.'">';
		print '</td><td class="liste_titre right">';
		print '<input class="flat" type="text" size="8" name="search_amount_all_tax" value="'.$search_amount_all_tax.'">';
		print '</td>';
        print '<td class="liste_titre maxwidthsearch">';
        $searchpicto = $form->showFilterAndCheckAddButtons(0);
        print $searchpicto;
        print '</td>';
		print "</tr>\n";

		if ($num > 0)
		{
			$total_ht = 0;
			$total_ttc = 0;
			$total_paid = 0;

			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$facturestatic->statut = $objp->fk_statut;
				$facturestatic->date_echeance = $db->jdate($objp->datelimite);



				print '<tr class="oddeven">';
				$classname = "impayee";

				print '<td class="nowrap">';
				$facturestatic->id = $objp->facid;
				$facturestatic->ref = $objp->ref;
				print $facturestatic->getNomUrl(1);
				print "</td>\n";

				print '<td class="nowrap">'.dol_trunc($objp->ref_supplier, 12).'</td>';

				print '<td class="nowrap center">'.dol_print_date($db->jdate($objp->df), 'day')."</td>\n";
				print '<td class="nowrap center">'.dol_print_date($db->jdate($objp->datelimite), 'day');
				if ($facturestatic->hasDelay()) {
					print img_warning($langs->trans("Late"));
				}
				print "</td>\n";

				print '<td>';
				$companystatic->id = $objp->socid;
				$companystatic->name = $objp->name;
				print $companystatic->getNomUrl(1, 'supplier', 32);
				print '</td>';

				print "<td class=\"right\">".price($objp->total_ht)."</td>";
				print "<td class=\"right\">".price($objp->total_ttc)."</td>";
				print "<td class=\"right\">".price($objp->am)."</td>";

				// Show invoice status
				print '<td class="right nowrap">';
				print $facturestatic->LibStatut($objp->paye, $objp->fk_statut, 5, $objp->am);
				print '</td>';

				print "</tr>\n";
				$total_ht += $objp->total_ht;
				$total_ttc += $objp->total_ttc;
				$total_paid += $objp->am;

				$i++;
			}

			print '<tr class="liste_total">';
			print "<td colspan=\"5\" class=\"left\">".$langs->trans("Total").": </td>";
			print "<td class=\"right\"><b>".price($total_ht)."</b></td>";
			print "<td class=\"right\"><b>".price($total_ttc)."</b></td>";
			print "<td class=\"right\"><b>".price($total_paid)."</b></td>";
			print '<td class="center">&nbsp;</td>';
			print "</tr>\n";
		}

		print "</table>";

		print '</form>';

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

// End of page
llxFooter();
$db->close();
