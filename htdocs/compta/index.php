<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2020 Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2020      Tobias Sekan         <tobias.sekan@startmail.com>
 * Copyright (C) 2020      Josep Lluís Amador   <joseplluis@lliuretic.cat>
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
 *	\file       htdocs/compta/index.php
 *	\ingroup    compta
 *	\brief      Main page of accountancy area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';

// L'espace compta/treso doit toujours etre actif car c'est un espace partage
// par de nombreux modules (banque, facture, commande a facturer, etc...) independamment
// de l'utilisation de la compta ou non. C'est au sein de cet espace que chaque sous fonction
// est protegee par le droit qui va bien du module concerne.
//if (!$user->rights->compta->general->lire)
//  accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));
if (!empty($conf->commande->enabled))
	$langs->load("orders");

$action = GETPOST('action', 'aZ09');
$bid = GETPOST('bid', 'int');

// Security check
$socid = '';
if ($user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('invoiceindex'));

/*
 * Actions
 */


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formfile = new FormFile($db);
$thirdpartystatic = new Societe($db);

llxHeader("", $langs->trans("AccountancyTreasuryArea"));

print load_fiche_titre($langs->trans("AccountancyTreasuryArea"), '', 'bill');


print '<div class="fichecenter"><div class="fichethirdleft">';


if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
	// Search customer invoices
	if (!empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		$listofsearchfields['search_invoice'] = array('text'=>'CustomerInvoice');
	}
	// Search supplier invoices
	if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) && $user->rights->fournisseur->lire)
	{
		$listofsearchfields['search_supplier_invoice'] = array('text'=>'SupplierInvoice');
	}
	if (!empty($conf->don->enabled) && $user->rights->don->lire)
	{
		$langs->load("donations");
		$listofsearchfields['search_donation'] = array('text'=>'Donation');
	}

	if (count($listofsearchfields))
	{
		print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';
		$i = 0;
		foreach ($listofsearchfields as $key => $value)
		{
			if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
			print '<tr '.$bc[false].'>';
			print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'"></td>';
			if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
			print '</tr>';
			$i++;
		}
		print '</table>';
		print '</div>';
		print '</form>';
		print '<br>';
	}
}


/**
 * Draft customers invoices
 */
if (!empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$tmpinvoice = new Facture($db);

	$sql = "SELECT f.rowid, f.ref, f.datef as date, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.ref_client";
	$sql .= ", f.type, f.fk_statut as status, f.paye";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid, s.email";
	$sql .= ", s.code_client, s.code_compta, s.code_fournisseur, s.code_compta_fournisseur";
	$sql .= ", cc.rowid as country_id, cc.code as country_code";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.rowid = f.fk_soc AND f.fk_statut = ".Facture::STATUS_DRAFT;
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;

	if ($socid)
	{
		$sql .= " AND f.fk_soc = $socid";
	}
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerDraft', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY f.rowid, f.ref, f.datef, f.total, f.tva, f.total_ttc, f.ref_client, f.type, f.fk_statut, f.paye,";
	$sql .= " s.nom, s.rowid, s.email, s.code_client, s.code_compta, s.code_fournisseur, s.code_compta_fournisseur,";
	$sql .= " cc.rowid, cc.code";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user";

	// Add Group from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListGroupByCustomerDraft', $parameters);
	$sql .= $hookmanager->resPrint;

	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<th colspan="3">';
		print $langs->trans("CustomersDraftInvoices").' ';
		print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status='.Facture::STATUS_DRAFT.'">';
		print '<span class="badge marginleftonlyshort">'.$num.'</span>';
		print '</a>';
		print '</th>';
		print '</tr>';

		if ($num)
		{
			$companystatic = new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$tmpinvoice->id = $obj->rowid;
				$tmpinvoice->ref = $obj->ref;
				$tmpinvoice->date = $db->jdate($obj->date);
				$tmpinvoice->type = $obj->type;
				$tmpinvoice->total_ht = $obj->total_ht;
				$tmpinvoice->total_tva = $obj->total_tva;
				$tmpinvoice->total_ttc = $obj->total_ttc;
				$tmpinvoice->ref_client = $obj->ref_client;
				$tmpinvoice->statut = $obj->status;
				$tmpinvoice->paye = $obj->paye;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->email = $obj->email;
				$companystatic->country_id = $obj->country_id;
				$companystatic->country_code = $obj->country_code;
				$companystatic->client = 1;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td class="nowrap tdoverflowmax100">';
				print $tmpinvoice->getNomUrl(1, '');
				print '</td>';
				print '<td class="nowrap tdoverflowmax100">';
				print $companystatic->getNomUrl(1, 'customer');
				print '</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc += $obj->total_ttc;
				$i++;
			}

			print '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table></div><br>";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

/**
 * Draft suppliers invoices
 */
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_invoice->enabled)) && $user->rights->fournisseur->facture->lire)
{
	$facturesupplierstatic = new FactureFournisseur($db);

	$sql = "SELECT f.ref, f.rowid, f.total_ht, f.total_tva, f.total_ttc, f.type, f.ref_supplier, f.fk_statut as status, f.paye";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid, s.email";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur";
	$sql .= ", cc.rowid as country_id, cc.code as country_code";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.rowid = f.fk_soc AND f.fk_statut = ".FactureFournisseur::STATUS_DRAFT;
	$sql .= " AND f.entity IN (".getEntity('invoice').')';
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid)	$sql .= " AND f.fk_soc = ".$socid;
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereSupplierDraft', $parameters);
	$sql .= $hookmanager->resPrint;
	$resql = $db->query($sql);

	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<th colspan="3">';
		print $langs->trans("SuppliersDraftInvoices").' ';
		print '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?search_status='.FactureFournisseur::STATUS_DRAFT.'">';
		print '<span class="badge marginleftonlyshort">'.$num.'</span>';
		print '</a>';
		print '</th>';
		print '</tr>';

		if ($num)
		{
			$companystatic = new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$facturesupplierstatic->ref = $obj->ref;
				$facturesupplierstatic->id = $obj->rowid;
				$facturesupplierstatic->total_ht = $obj->total_ht;
				$facturesupplierstatic->total_tva = $obj->total_tva;
				$facturesupplierstatic->total_ttc = $obj->total_ttc;
				$facturesupplierstatic->ref_supplier = $obj->ref_supplier;
				$facturesupplierstatic->type = $obj->type;
				$facturesupplierstatic->statut = $obj->status;
				$facturesupplierstatic->paye = $obj->paye;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->email = $obj->email;
				$companystatic->country_id = $obj->country_id;
				$companystatic->country_code = $obj->country_code;
				$companystatic->fournisseur = 1;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td class="nowrap tdoverflowmax100">';
				print $facturesupplierstatic->getNomUrl(1, '');
				print '</td>';
				print '<td class="nowrap tdoverflowmax100">';
				print $companystatic->getNomUrl(1, 'supplier');
				print '</td>';
				print '<td class="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc += $obj->total_ttc;
				$i++;
			}

			print '<tr class="liste_total"><td class="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" class="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table></div><br>";
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Latest modified customer invoices
if (!empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$langs->load("boxes");
	$tmpinvoice = new Facture($db);

	$sql = "SELECT f.rowid, f.ref, f.fk_statut as status, f.type, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.paye, f.tms";
	$sql .= ", f.date_lim_reglement as datelimite";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid";
	$sql .= ", s.code_client, s.code_compta, s.email";
	$sql .= ", cc.rowid as country_id, cc.code as country_code";
	$sql .= ", sum(pf.amount) as am";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays, ".MAIN_DB_PREFIX."facture as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.rowid = f.fk_soc";
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND f.fk_soc = ".$socid;
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerLastModified', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY f.rowid, f.ref, f.fk_statut, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
	$sql .= " s.nom, s.rowid, s.code_client, s.code_compta, s.email,";
	$sql .= " cc.rowid, cc.code";
	$sql .= " ORDER BY f.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		$othernb = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastCustomerBills", $max).'</th>';
		if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$tmpinvoice->ref = $obj->ref;
				$tmpinvoice->id = $obj->rowid;
				$tmpinvoice->total_ht = $obj->total_ht;
				$tmpinvoice->total_tva = $obj->total_tva;
				$tmpinvoice->total_ttc = $obj->total_ttc;
				$tmpinvoice->statut = $obj->status;
				$tmpinvoice->paye = $obj->paye;
				$tmpinvoice->date_lim_reglement = $db->jdate($obj->datelimite);
				$tmpinvoice->type = $obj->type;

				$thirdpartystatic->id = $obj->socid;
				$thirdpartystatic->name = $obj->name;
				$thirdpartystatic->email = $obj->email;
				$thirdpartystatic->country_id = $obj->country_id;
				$thirdpartystatic->country_code = $obj->country_code;
				$thirdpartystatic->email = $obj->email;
				$thirdpartystatic->client = 1;
				$thirdpartystatic->code_client = $obj->code_client;
				//$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
				$thirdpartystatic->code_compta = $obj->code_compta;
				//$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowraponall">';
				print $tmpinvoice->getNomUrl(1, '');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($tmpinvoice->hasDelay()) {
					print img_warning($langs->trans("Late"));
				}
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($tmpinvoice->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td class="left">';
				print $thirdpartystatic->getNomUrl(1, 'customer', 44);
				print '</td>';
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="nowrap right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->tms), 'day').'</td>';
				print '<td>'.$tmpinvoice->getLibStatut(3, $obj->am).'</td>';
				print '</tr>';

				$total_ttc += $obj->total_ttc;
				$total += $obj->total_ht;
				$totalam += $obj->am;

				$i++;
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}
		} else {
			$colspan = 5;
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}



// Last modified supplier invoices
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_invoice->enabled)) && $user->rights->fournisseur->facture->lire)
{
	$langs->load("boxes");
	$facstatic = new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut as status, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.tms, ff.paye";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.email";
	$sql .= ", SUM(pf.amount) as am";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.rowid = ff.fk_soc";
	$sql .= " AND ff.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND ff.fk_soc = ".$socid;
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereSupplierLastModified', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.tms, ff.paye,";
	$sql .= " s.nom, s.rowid, s.code_fournisseur, s.code_compta_fournisseur, s.email";
	$sql .= " ORDER BY ff.tms DESC ";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastSupplierBills", $max).'</th>';
		if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print "</tr>\n";
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			$othernb = 0;

			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$facstatic->ref = $obj->ref;
				$facstatic->id = $obj->rowid;
				$facstatic->total_ht = $obj->total_ht;
				$facstatic->total_tva = $obj->total_tva;
				$facstatic->total_ttc = $obj->total_ttc;
				$facstatic->statut = $obj->status;
				$facstatic->paye = $obj->paye;

				$thirdpartystatic->id = $obj->socid;
				$thirdpartystatic->name = $obj->name;
				$thirdpartystatic->email = $obj->email;
				$thirdpartystatic->country_id = 0;
				$thirdpartystatic->country_code = '';
				$thirdpartystatic->client = 0;
				$thirdpartystatic->fournisseur = 1;
				$thirdpartystatic->code_client = '';
				$thirdpartystatic->code_fournisseur = $obj->code_fournisseur;
				$thirdpartystatic->code_compta = '';
				$thirdpartystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven nowraponall tdoverflowmax100"><td>';
				print $facstatic->getNomUrl(1, '');
				print '</td>';
				print '<td class="nowrap tdoverflowmax100">';
				print $thirdpartystatic->getNomUrl(1, 'supplier');
				print '</td>';
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->tms), 'day').'</td>';
				print '<td>'.$facstatic->getLibStatut(3).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc += $obj->total_ttc;
				$totalam += $obj->am;
				$i++;
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}
		} else {
			$colspan = 5;
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
	} else {
		dol_print_error($db);
	}
}



// Latest donations
if (!empty($conf->don->enabled) && $user->rights->don->lire)
{
	include_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

	$langs->load("boxes");
	$donationstatic = new Don($db);

	$sql = "SELECT d.rowid, d.lastname, d.firstname, d.societe, d.datedon as date, d.tms as dm, d.amount, d.fk_statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."don as d";
	$sql .= " WHERE d.entity IN (".getEntity('donation').")";
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereLastDonations', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= $db->order("d.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);

		$i = 0;
		$othernb = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th>'.$langs->trans("BoxTitleLastModifiedDonations", $max).'</th>';
		print '<th></th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;

			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$donationstatic->id = $objp->rowid;
				$donationstatic->ref = $objp->rowid;
				$donationstatic->lastname = $objp->lastname;
				$donationstatic->firstname = $objp->firstname;
				$donationstatic->date = $objp->date;
				$donationstatic->statut = $objp->status;
				$donationstatic->status = $objp->status;

				$label = $donationstatic->getFullName($langs);
				if ($objp->societe) $label .= ($label ? ' - ' : '').$objp->societe;

				print '<tr class="oddeven tdoverflowmax100">';
				print '<td>'.$donationstatic->getNomUrl(1).'</td>';
				print '<td>'.$label.'</td>';
				print '<td class="nowrap right">'.price($objp->amount).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($objp->dm), 'day').'</td>';
				print '<td>'.$donationstatic->getLibStatut(3).'</td>';
				print '</tr>';

				$i++;
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}
		} else {
			print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print '</table></div><br>';
	} else dol_print_error($db);
}

/**
 * Social contributions to pay
 */
if (!empty($conf->tax->enabled) && $user->rights->tax->charges->lire)
{
	if (!$socid)
	{
		$chargestatic = new ChargeSociales($db);

		$sql = "SELECT c.rowid, c.amount, c.date_ech, c.paye,";
		$sql .= " cc.libelle as label,";
		$sql .= " SUM(pc.amount) as sumpaid";
		$sql .= " FROM (".MAIN_DB_PREFIX."c_chargesociales as cc, ".MAIN_DB_PREFIX."chargesociales as c)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = c.rowid";
		$sql .= " WHERE c.fk_type = cc.id";
		$sql .= " AND c.entity IN (".getEntity('tax').')';
		$sql .= " AND c.paye = 0";
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhereSocialContributions', $parameters);
		$sql .= $hookmanager->resPrint;

		$sql .= " GROUP BY c.rowid, c.amount, c.date_ech, c.paye, cc.libelle";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th>'.$langs->trans("ContributionsToPay").($num ? ' <a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?status=0"><span class="badge">'.$num.'</span></a>' : '').'</th>';
			print '<th align="center">'.$langs->trans("DateDue").'</th>';
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("Paid").'</th>';
			print '<th align="center" width="16">&nbsp;</th>';
			print '</tr>';
			if ($num)
			{
				$i = 0;
				$tot_ttc = 0;
				$othernb = 0;

				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);

					if ($i >= $max) {
						$othernb += 1;
						$i++;
						$total += $obj->total_ht;
						$total_ttc += $obj->total_ttc;
						continue;
					}

					$chargestatic->id = $obj->rowid;
					$chargestatic->ref = $obj->rowid;
					$chargestatic->label = $obj->label;
					$chargestatic->paye = $obj->paye;
					$chargestatic->status = $obj->paye;

					print '<tr class="oddeven">';
					print '<td class="nowraponall">'.$chargestatic->getNomUrl(1).'</td>';
					print '<td class="center">'.dol_print_date($db->jdate($obj->date_ech), 'day').'</td>';
					print '<td class="nowrap right">'.price($obj->amount).'</td>';
					print '<td class="nowrap right">'.price($obj->sumpaid).'</td>';
					print '<td class="center">'.$chargestatic->getLibStatut(3).'</td>';
					print '</tr>';

					$tot_ttc += $obj->amount;
					$i++;
				}

				if ($othernb) {
					print '<tr class="oddeven">';
					print '<td class="nowrap" colspan="5">';
					print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
					print '</td>';
					print "</tr>\n";
				}

				print '<tr class="liste_total"><td class="left" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td class="nowrap right">'.price($tot_ttc).'</td>';
				print '<td class="right"></td>';
				print '<td class="right">&nbsp;</td>';
				print '</tr>';
			} else {
				print '<tr class="oddeven"><td colspan="5" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
			}
			print "</table></div><br>";
			$db->free($resql);
		} else {
			dol_print_error($db);
		}
	}
}

/*
 * Customers orders to be billed
 */
if (!empty($conf->facture->enabled) && !empty($conf->commande->enabled) && $user->rights->commande->lire && empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER))
{
	$commandestatic = new Commande($db);
	$langs->load("orders");

	$sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc";
	$sql .= ", s.nom as name, s.email";
	$sql .= ", s.rowid as socid";
	$sql .= ", s.code_client, s.code_compta";
	$sql .= ", c.rowid, c.ref, c.facture, c.fk_statut as status, c.total_ht, c.tva as total_tva, c.total_ttc,";
	$sql .= " cc.rowid as country_id, cc.code as country_code";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= ", ".MAIN_DB_PREFIX."commande as c";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_source = c.rowid AND el.sourcetype = 'commande'";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON el.fk_target = f.rowid AND el.targettype = 'facture'";
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid)	$sql .= " AND c.fk_soc = ".$socid;
	$sql .= " AND c.fk_statut = ".Commande::STATUS_CLOSED;
	$sql .= " AND c.facture = 0";
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerOrderToBill', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY s.nom, s.email, s.rowid, s.code_client, s.code_compta, c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.tva, c.total_ttc, cc.rowid, cc.code";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		if ($num)
		{
			$i = 0;
			$othernb = 0;

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			print "<tr class=\"liste_titre\">";
			print '<th colspan="2">';
			print $langs->trans("OrdersDeliveredToBill").' ';
			print '<a href="'.DOL_URL_ROOT.'/commande/list.php?search_status='.Commande::STATUS_CLOSED.'&amp;billed=0">';
			print '<span class="badge">'.$num.'</span>';
			print '</a>';
			print '</th>';

			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("ToBill").'</th>';
			print '<th align="center" width="16">&nbsp;</th>';
			print '</tr>';

			$tot_ht = $tot_ttc = $tot_tobill = 0;
			$societestatic = new Societe($db);
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$societestatic->id = $obj->socid;
				$societestatic->name = $obj->name;
				$societestatic->email = $obj->email;
				$societestatic->country_id = $obj->country_id;
				$societestatic->country_code = $obj->country_code;
				$societestatic->client = 1;
				$societestatic->code_client = $obj->code_client;
				//$societestatic->code_fournisseur = $obj->code_fournisseur;
				$societestatic->code_compta = $obj->code_compta;
				//$societestatic->code_fournisseur = $obj->code_fournisseur;

				$commandestatic->id = $obj->rowid;
				$commandestatic->ref = $obj->ref;
				$commandestatic->statut = $obj->status;
				$commandestatic->billed = $obj->facture;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td class="nowrap tdoverflowmax100">';
				print $societestatic->getNomUrl(1, 'customer');
				print '</td>';
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc - $obj->tot_fttc).'</td>';
				print '<td>'.$commandestatic->getLibStatut(3).'</td>';
				print '</tr>';
				$tot_ht += $obj->total_ht;
				$tot_ttc += $obj->total_ttc;
				//print "x".$tot_ttc."z".$obj->tot_fttc;
				$tot_tobill += ($obj->total_ttc - $obj->tot_fttc);
				$i++;
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($tot_ht).'</td>';
			print '<td class="nowrap right">'.price($tot_ttc).'</td>';
			print '<td class="nowrap right">'.price($tot_tobill).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			print '</table></div><br>';
		}
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

/*
 * Unpaid customers invoices
 */
if (!empty($conf->facture->enabled) && $user->rights->facture->lire)
{
	$tmpinvoice = new Facture($db);

	$sql = "SELECT f.rowid, f.ref, f.fk_statut as status, f.datef, f.type, f.total as total_ht, f.tva as total_tva, f.total_ttc, f.paye, f.tms";
	$sql .= ", f.date_lim_reglement as datelimite";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid, s.email";
	$sql .= ", s.code_client, s.code_compta";
	$sql .= ", cc.rowid as country_id, cc.code as country_code";
	$sql .= ", sum(pf.amount) as am";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays,".MAIN_DB_PREFIX."facture as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = ".Facture::STATUS_VALIDATED;
	$sql .= " AND f.entity IN (".getEntity('invoice').')';
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND f.fk_soc = ".$socid;
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerUnpaid', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY f.rowid, f.ref, f.fk_statut, f.datef, f.type, f.total, f.tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
	$sql .= " s.nom, s.rowid, s.email, s.code_client, s.code_compta, cc.rowid, cc.code";
	$sql .= " ORDER BY f.datef ASC, f.ref ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		$othernb = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BillsCustomersUnpaid", $num).' ';
		print '<a href="'.DOL_URL_ROOT.'/compta/facture/list.php?search_status='.Facture::STATUS_VALIDATED.'">';
		print '<span class="badge">'.$num.'</span>';
		print '</a>';
		print '</th>';

		print '<th class="right">'.$langs->trans("DateDue").'</th>';
		if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("Received").'</th>';
		print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num)
		{
			$societestatic = new Societe($db);
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$tmpinvoice->ref = $obj->ref;
				$tmpinvoice->id = $obj->rowid;
				$tmpinvoice->total_ht = $obj->total_ht;
				$tmpinvoice->total_tva = $obj->total_tva;
				$tmpinvoice->total_ttc = $obj->total_ttc;
				$tmpinvoice->type = $obj->type;
				$tmpinvoice->statut = $obj->status;
				$tmpinvoice->paye = $obj->paye;
				$tmpinvoice->date_lim_reglement = $db->jdate($obj->datelimite);

				$societestatic->id = $obj->socid;
				$societestatic->name = $obj->name;
				$societestatic->email = $obj->email;
				$societestatic->country_id = $obj->country_id;
				$societestatic->country_code = $obj->country_code;
				$societestatic->client = 1;
				$societestatic->code_client = $obj->code_client;
				$societestatic->code_fournisseur = $obj->code_fournisseur;
				$societestatic->code_compta = $obj->code_compta;
				$societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="110" class="nobordernopadding nowrap">';
				print $tmpinvoice->getNomUrl(1, '');
				print '</td>';
				print '<td width="20" class="nobordernopadding nowrap">';
				if ($tmpinvoice->hasDelay()) {
					print img_warning($langs->trans("Late"));
				}
				print '</td>';
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($tmpinvoice->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				print '<td class="nowrap tdoverflowmax100">';
				print $societestatic->getNomUrl(1, 'customer');
				print '</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->datelimite), 'day').'</td>';
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="nowrap right">'.price($obj->am).'</td>';
				print '<td>'.$tmpinvoice->getLibStatut(3, $obj->am).'</td>';
				print '</tr>';

				$total_ttc += $obj->total_ttc;
				$total += $obj->total_ht;
				$totalam += $obj->am;

				$i++;
			}

			if ($othernb) {
				$colspan = 6;
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="'.$colspan.'">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc - $totalam).')</font> </td>';
			print '<td>&nbsp;</td>';
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($total).'</td>';
			print '<td class="nowrap right">'.price($total_ttc).'</td>';
			print '<td class="nowrap right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		} else {
			$colspan = 6;
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

/*
 * Unpaid supplier invoices
 */
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_invoice->enabled)) && $user->rights->fournisseur->facture->lire)
{
	$facstatic = new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut as status, ff.type, ff.libelle as label, ff.total_ht, ff.total_tva, ff.total_ttc, ff.paye";
	$sql .= ", ff.date_lim_reglement";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid, s.email";
	$sql .= ", s.code_client, s.code_compta";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur";
	$sql .= ", sum(pf.amount) as am";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.rowid = ff.fk_soc";
	$sql .= " AND ff.entity = ".$conf->entity;
	$sql .= " AND ff.paye = 0";
	$sql .= " AND ff.fk_statut = ".FactureFournisseur::STATUS_VALIDATED;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND ff.fk_soc = ".$socid;
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereSupplierUnpaid', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.type, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.paye, ff.date_lim_reglement,";
	$sql .= " s.nom, s.rowid, s.email, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
	$sql .= " ORDER BY ff.date_lim_reglement ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$othernb = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BillsSuppliersUnpaid", $num).' ';
		print '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?search_status='.FactureFournisseur::STATUS_VALIDATED.'">';
		// TODO: "impayees.php" looks very outdatetd and should be set to deprecated or directly remove in the next version
		// <a href="'.DOL_URL_ROOT.'/fourn/facture/impayees.php">
		print '<span class="badge">'.$num.'</span>';
		print '</a>';
		print '</th>';

		print '<th class="right">'.$langs->trans("DateDue").'</th>';
		if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("Paid").'</th>';
		print '<th width="16">&nbsp;</th>';
		print "</tr>\n";
		$societestatic = new Societe($db);
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$facstatic->ref = $obj->ref;
				$facstatic->id = $obj->rowid;
				$facstatic->type = $obj->type;
				$facstatic->total_ht = $obj->total_ht;
				$facstatic->total_tva = $obj->total_tva;
				$facstatic->total_ttc = $obj->total_ttc;
				$facstatic->statut = $obj->status;
				$facstatic->paye = $obj->paye;

				$societestatic->id = $obj->socid;
				$societestatic->name = $obj->name;
				$societestatic->email = $obj->email;
				$societestatic->client = 0;
				$societestatic->fournisseur = 1;
				$societestatic->code_client = $obj->code_client;
				$societestatic->code_fournisseur = $obj->code_fournisseur;
				$societestatic->code_compta = $obj->code_compta;
				$societestatic->code_compta_fournisseur = $obj->code_compta_fournisseur;

				print '<tr class="oddeven"><td class="nowrap tdoverflowmax100">';
				print $facstatic->getNomUrl(1, '');
				print '</td>';
				print '<td class="nowrap tdoverflowmax100">'.$societestatic->getNomUrl(1, 'supplier').'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->date_lim_reglement), 'day').'</td>';
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($obj->total_ht).'</td>';
				print '<td class="nowrap right">'.price($obj->total_ttc).'</td>';
				print '<td class="nowrap right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->getLibStatut(3, $obj->am).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc += $obj->total_ttc;
				$totalam += $obj->am;
				$i++;
			}

			if ($othernb) {
				$colspan = 6;
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="'.$colspan.'">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc - $totalam).')</font> </td>';
			print '<td>&nbsp;</td>';
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) print '<td class="right">'.price($total).'</td>';
			print '<td class="nowrap right">'.price($total_ttc).'</td>';
			print '<td class="nowrap right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		} else {
			$colspan = 6;
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) $colspan++;
			print '<tr class="oddeven"><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table></div><br>';
	} else {
		dol_print_error($db);
	}
}



// TODO Mettre ici recup des actions en rapport avec la compta
$resql = 0;
if ($resql)
{
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><thcolspan="2">'.$langs->trans("TasksToDo").'</th>';
	print "</tr>\n";
	$i = 0;
	while ($i < $db->num_rows($resql))
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven"><td>'.dol_print_date($db->jdate($obj->da), "day").'</td>';
		print '<td><a href="action/card.php">'.$obj->label.'</a></td></tr>';
		$i++;
	}
	$db->free($resql);
	print "</table></div><br>";
}


print '</div></div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardAccountancy', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
