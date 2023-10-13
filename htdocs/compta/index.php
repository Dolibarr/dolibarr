<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2020 Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2020      Tobias Sekan         <tobias.sekan@startmail.com>
 * Copyright (C) 2020      Josep Lluís Amador   <joseplluis@lliuretic.cat>
 * Copyright (C) 2021-2023 Frédéric France		<frederic.france@netlogic.fr>
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


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/invoice.lib.php';

// L'espace compta/treso doit toujours etre actif car c'est un espace partage
// par de nombreux modules (banque, facture, commande a facturer, etc...) independamment
// de l'utilisation de la compta ou non. C'est au sein de cet espace que chaque sous fonction
// est protegee par le droit qui va bien du module concerne.
//if (!$user->rights->compta->general->lire)
//  accessforbidden();

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));
if (isModEnabled('commande')) {
	$langs->load("orders");
}

// Get parameters
$action = GETPOST('action', 'aZ09');
$bid = GETPOST('bid', 'int');

// Security check
$socid = '';
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;

// Maximum elements of the tables
$maxDraftCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;
$maxLatestEditCount = 5;
$maxOpenCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('invoiceindex'));


$maxofloop = (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD);


/*
 * Actions
 */

// None


/*
 * View
 */

$now = dol_now();

$form = new Form($db);
$formfile = new FormFile($db);
$thirdpartystatic = new Societe($db);

llxHeader("", $langs->trans("InvoicesArea"));

print load_fiche_titre($langs->trans("InvoicesArea"), '', 'bill');


print '<div class="fichecenter"><div class="fichethirdleft">';

if (isModEnabled('facture')) {
	print getNumberInvoicesPieChart('customers');
	print '<br>';
}

if (isModEnabled('fournisseur') || isModEnabled('supplier_invoice')) {
	print getNumberInvoicesPieChart('suppliers');
	print '<br>';
}

if (isModEnabled('facture')) {
	print getCustomerInvoiceDraftTable($max, $socid);
	print '<br>';
}

if (isModEnabled('fournisseur') || isModEnabled('supplier_invoice')) {
	print getDraftSupplierTable($max, $socid);
	print '<br>';
}

print '</div><div class="fichetwothirdright">';


// Latest modified customer invoices
if (isModEnabled('facture') && $user->hasRight('facture', 'lire')) {
	$langs->load("boxes");
	$tmpinvoice = new Facture($db);

	$sql = "SELECT f.rowid, f.ref, f.fk_statut as status, f.type, f.total_ht, f.total_tva, f.total_ttc, f.paye, f.tms";
	$sql .= ", f.date_lim_reglement as datelimite";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid";
	$sql .= ", s.code_client, s.code_compta, s.email";
	$sql .= ", cc.rowid as country_id, cc.code as country_code";
	$sql .= ", sum(pf.amount) as am";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays, ".MAIN_DB_PREFIX."facture as f";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.rowid = f.fk_soc";
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerLastModified', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY f.rowid, f.ref, f.fk_statut, f.type, f.total_ht, f.total_tva, f.total_ttc, f.paye, f.tms, f.date_lim_reglement,";
	$sql .= " s.nom, s.rowid, s.code_client, s.code_compta, s.email,";
	$sql .= " cc.rowid, cc.code";
	$sql .= " ORDER BY f.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$othernb = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastCustomerBills", $max).'</th>';
		if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
			print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		}
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print '</tr>';
		if ($num) {
			$total_ttc = $totalam = $total_ht = 0;
			while ($i < $num && $i < $conf->liste_limit) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total_ht += $obj->total_ht;
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
				if ($tmpinvoice->hasDelay()) {
					print '<td width="20" class="nobordernopadding nowrap">';
					print img_warning($langs->trans("Late"));
					print '</td>';
				}
				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				print $formfile->getDocumentsLink($tmpinvoice->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td class="tdoverflowmax150">';
				print $thirdpartystatic->getNomUrl(1, 'customer', 44);
				print '</td>';
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
					print '<td class="nowrap right"><span class="amount">'.price($obj->total_ht).'</span></td>';
				}
				print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc).'</span></td>';

				print '<td class="right" title="'.dol_escape_htmltag($langs->trans("DateModificationShort").' : '.dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuserrel')).'">'.dol_print_date($db->jdate($obj->tms), 'day', 'tzuserrel').'</td>';

				print '<td>'.$tmpinvoice->getLibStatut(3, $obj->am).'</td>';

				print '</tr>';

				$total_ttc += $obj->total_ttc;
				$total_ht += $obj->total_ht;
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
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
				$colspan++;
			}
			print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoInvoice").'</span></td></tr>';
		}
		print '</table></div><br>';
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


// Last modified supplier invoices
if ((isModEnabled('fournisseur') && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) && $user->hasRight("fournisseur", "facture", "lire")) || (isModEnabled('supplier_invoice') && $user->hasRight("supplier_invoice", "lire"))) {
	$langs->load("boxes");
	$facstatic = new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.ref, ff.fk_statut as status, ff.type, ff.libelle, ff.total_ht, ff.total_tva, ff.total_ttc, ff.tms, ff.paye, ff.ref_supplier";
	$sql .= ", s.nom as name";
	$sql .= ", s.rowid as socid";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.email";
	$sql .= ", SUM(pf.amount) as am";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.rowid = ff.fk_soc";
	$sql .= " AND ff.entity = ".$conf->entity;
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND ff.fk_soc = ".((int) $socid);
	}
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereSupplierLastModified', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY ff.rowid, ff.ref, ff.fk_statut, ff.type, ff.libelle, ff.total_ht, ff.tva, ff.total_tva, ff.total_ttc, ff.tms, ff.paye, ff.ref_supplier,";
	$sql .= " s.nom, s.rowid, s.code_fournisseur, s.code_compta_fournisseur, s.email";
	$sql .= " ORDER BY ff.tms DESC ";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("BoxTitleLastSupplierBills", $max).'</th>';
		if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
			print '<th class="right">'.$langs->trans("AmountHT").'</th>';
		}
		print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '<th width="16">&nbsp;</th>';
		print "</tr>\n";
		if ($num) {
			$i = 0;
			$total_ht = $total_ttc = $totalam = 0;
			$othernb = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total_ht += $obj->total_ht;
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
				$facstatic->type = $obj->type;
				$facstatic->ref_supplier = $obj->ref_supplier;

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
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
					print '<td class="right"><span class="amount">'.price($obj->total_ht).'</span></td>';
				}
				print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc).'</span></td>';
				print '<td class="right" title="'.dol_escape_htmltag($langs->trans("DateModificationShort").' : '.dol_print_date($db->jdate($obj->tms), 'dayhour', 'tzuserrel')).'">'.dol_print_date($db->jdate($obj->tms), 'day', 'tzuserrel').'</td>';
				$alreadypaid = $facstatic->getSommePaiement();
				print '<td>'.$facstatic->getLibStatut(3, $alreadypaid).'</td>';
				print '</tr>';
				$total_ht += $obj->total_ht;
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
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
				$colspan++;
			}
			print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoInvoice").'</span></td></tr>';
		}
		print '</table></div><br>';
	} else {
		dol_print_error($db);
	}
}



// Latest donations
if (isModEnabled('don') && $user->hasRight('don', 'lire')) {
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
	if ($result) {
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

		if ($num) {
			$total_ttc = $totalam = $total_ht = 0;

			while ($i < $num && $i < $max) {
				$obj = $db->fetch_object($result);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total_ht += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$donationstatic->id = $obj->rowid;
				$donationstatic->ref = $obj->rowid;
				$donationstatic->lastname = $obj->lastname;
				$donationstatic->firstname = $obj->firstname;
				$donationstatic->date = $db->jdate($obj->date);
				$donationstatic->statut = $obj->status;
				$donationstatic->status = $obj->status;

				$label = $donationstatic->getFullName($langs);
				if ($obj->societe) {
					$label .= ($label ? ' - ' : '').$obj->societe;
				}

				print '<tr class="oddeven tdoverflowmax100">';
				print '<td>'.$donationstatic->getNomUrl(1).'</td>';
				print '<td>'.$label.'</td>';
				print '<td class="nowrap right"><span class="amount">'.price($obj->amount).'</span></td>';
				print '<td class="right" title="'.dol_escape_htmltag($langs->trans("DateModificationShort").' : '.dol_print_date($db->jdate($obj->dm), 'dayhour', 'tzuserrel')).'">'.dol_print_date($db->jdate($obj->dm), 'day', 'tzuserrel').'</td>';
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
			print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print '</table></div><br>';
	} else {
		dol_print_error($db);
	}
}

/**
 * Social contributions to pay
 */
if (isModEnabled('tax') && !empty($user->rights->tax->charges->lire)) {
	if (!$socid) {
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
		if ($resql) {
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
			if ($num) {
				$i = 0;
				$tot_ttc = 0;
				$othernb = 0;

				while ($i < $num) {
					$obj = $db->fetch_object($resql);

					if ($i >= $max) {
						$othernb += 1;
						$tot_ttc += $obj->amount;
						$i++;
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
					print '<td class="nowrap right"><span class="amount">'.price($obj->amount).'</span></td>';
					print '<td class="nowrap right"><span class="amount">'.price($obj->sumpaid).'</span></td>';
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
				print '<tr class="oddeven"><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
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
if (isModEnabled('facture') && isModEnabled('commande') && $user->hasRight("commande", "lire") && empty($conf->global->WORKFLOW_DISABLE_CREATE_INVOICE_FROM_ORDER)) {
	$commandestatic = new Commande($db);
	$langs->load("orders");

	$sql = "SELECT sum(f.total_ht) as tot_fht, sum(f.total_ttc) as tot_fttc";
	$sql .= ", s.nom as name, s.email";
	$sql .= ", s.rowid as socid";
	$sql .= ", s.code_client, s.code_compta";
	$sql .= ", c.rowid, c.ref, c.facture, c.fk_statut as status, c.total_ht, c.total_tva, c.total_ttc,";
	$sql .= " cc.rowid as country_id, cc.code as country_code";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s LEFT JOIN ".MAIN_DB_PREFIX."c_country as cc ON cc.rowid = s.fk_pays";
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= ", ".MAIN_DB_PREFIX."commande as c";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON el.fk_source = c.rowid AND el.sourcetype = 'commande'";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON el.fk_target = f.rowid AND el.targettype = 'facture'";
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity = ".$conf->entity;
	if (empty($user->rights->societe->client->voir) && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	$sql .= " AND c.fk_statut = ".Commande::STATUS_CLOSED;
	$sql .= " AND c.facture = 0";
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhereCustomerOrderToBill', $parameters);
	$sql .= $hookmanager->resPrint;

	$sql .= " GROUP BY s.nom, s.email, s.rowid, s.code_client, s.code_compta, c.rowid, c.ref, c.facture, c.fk_statut, c.total_ht, c.total_tva, c.total_ttc, cc.rowid, cc.code";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		if ($num) {
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

			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
				print '<th class="right">'.$langs->trans("AmountHT").'</th>';
			}
			print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
			print '<th class="right">'.$langs->trans("ToBill").'</th>';
			print '<th align="center" width="16">&nbsp;</th>';
			print '</tr>';

			$tot_ht = $tot_ttc = $tot_tobill = 0;
			$societestatic = new Societe($db);
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total_ht += $obj->total_ht;
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
				print '<td class="nobordernopadding nowrap">';
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
				if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
					print '<td class="right"><span class="amount">'.price($obj->total_ht).'</span></td>';
				}
				print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc).'</span></td>';
				print '<td class="nowrap right"><span class="amount">'.price($obj->total_ttc - $obj->tot_fttc).'</span></td>';
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

			print '<tr class="liste_total"><td colspan="2">'.$langs->trans("Total").' &nbsp; <span style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</span> </td>';
			if (!empty($conf->global->MAIN_SHOW_HT_ON_SUMMARY)) {
				print '<td class="right">'.price($tot_ht).'</td>';
			}
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


// TODO Mettre ici recup des actions en rapport avec la compta
$sql = '';
if ($sql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><thcolspan="2">'.$langs->trans("TasksToDo").'</th>';
	print "</tr>\n";
	$i = 0;
	$resql = $db->query($sql);
	if ($resql) {
		$num_rows = $db->num_rows($resql);
		while ($i < $num_rows) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>'.dol_print_date($db->jdate($obj->da), "day").'</td>';
			print '<td><a href="action/card.php">'.$obj->label.'</a></td></tr>';
			$i++;
		}
		$db->free($resql);
	}
	print "</table></div><br>";
}


print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardAccountancy', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
