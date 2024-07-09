<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2019		Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2020		Pierre Ardoin			<mapiolca@me.com>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
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
 *	\file		htdocs/comm/index.php
 *	\ingroup	commercial
 *	\brief		Home page of commercial area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
if (isModEnabled('contract')) {
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
}
if (isModEnabled('intervention')) {
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('commercialindex'));

// Load translation files required by the page
$langs->loadLangs(array("boxes", "commercial", "contracts", "orders", "propal", "supplier_proposal"));

$action = GETPOST('action', 'aZ09');
$bid = GETPOSTINT('bid');

// Securite access client
$socid = GETPOSTINT('socid');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}


$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);
$maxofloop = (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD);
$now = dol_now();

//restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', 0);
if (!$user->hasRight('propal', 'read') && !$user->hasRight('supplier_proposal', 'read') && !$user->hasRight('commande', 'read') && !$user->hasRight('fournisseur', 'commande', 'read')
	&& !$user->hasRight('supplier_order', 'read') && !$user->hasRight('fichinter', 'read') && !$user->hasRight('contrat', 'read')) {
	accessforbidden();
}



/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);
if (isModEnabled("propal")) {
	$propalstatic = new Propal($db);
}
if (isModEnabled('supplier_proposal')) {
	$supplierproposalstatic = new SupplierProposal($db);
}
if (isModEnabled('order')) {
	$orderstatic = new Commande($db);
}
if (isModEnabled("supplier_order")) {
	$supplierorderstatic = new CommandeFournisseur($db);
}

if (isModEnabled('intervention')) {
	$fichinterstatic = new Fichinter($db);
}

llxHeader("", $langs->trans("CommercialArea"));

print load_fiche_titre($langs->trans("CommercialArea"), '', 'commercial');

print '<div class="fichecenter">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';


$tmp = getCustomerProposalPieChart($socid);
if ($tmp) {
	print $tmp;
}
$tmp = getCustomerOrderPieChart($socid);
if ($tmp) {
	print $tmp;
}

/*
 * Draft customer proposals
 */

if (isModEnabled("propal") && $user->hasRight("propal", "lire")) {
	$sql = "SELECT p.rowid, p.ref, p.ref_client, p.total_ht, p.total_tva, p.total_ttc, p.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_soc = s.rowid";
	$sql .= " AND p.fk_statut = ".Propal::STATUS_DRAFT;
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, $maxofloop);
		startSimpleTable("ProposalsDraft", "comm/propal/list.php", "search_status=".Propal::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;
			$othernb = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb++;
					$i++;
					$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
					continue;
				}

				$propalstatic->id = $obj->rowid;
				$propalstatic->ref = $obj->ref;
				$propalstatic->ref_client = $obj->ref_client;
				$propalstatic->total_ht = $obj->total_ht;
				$propalstatic->total_tva = $obj->total_tva;
				$propalstatic->total_ttc = $obj->total_ttc;
				$propalstatic->statut = $obj->status;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowraponall tdoverflowmax125">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="nowrap right tdamount amount">'.price((getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc)).'</td>';
				print '</tr>';

				$i++;
				$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
				print '</td>';
				print "</tr>\n";
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoProposal");
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Draft supplier proposals
 */

if (isModEnabled('supplier_proposal') && $user->hasRight("supplier_proposal", "lire")) {
	$sql = "SELECT p.rowid, p.ref, p.total_ht, p.total_tva, p.total_ttc, p.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.entity IN (".getEntity($supplierproposalstatic->element).")";
	$sql .= " AND p.fk_statut = ".SupplierProposal::STATUS_DRAFT;
	$sql .= " AND p.fk_soc = s.rowid";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, $maxofloop);
		startSimpleTable("SupplierProposalsDraft", "supplier_proposal/list.php", "search_status=".SupplierProposal::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;
			$othernb = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
					continue;
				}

				$supplierproposalstatic->id = $obj->rowid;
				$supplierproposalstatic->ref = $obj->ref;
				$supplierproposalstatic->total_ht = $obj->total_ht;
				$supplierproposalstatic->total_tva = $obj->total_tva;
				$supplierproposalstatic->total_ttc = $obj->total_ttc;
				$supplierproposalstatic->statut = $obj->status;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowraponall tdoverflowmax125">'.$supplierproposalstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
				print '<td class="nowrap right tdamount amount">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
				print '</td>';
				print "</tr>\n";
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoProposal");
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Draft sales orders
 */

if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.total_tva, c.total_ttc, c.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.entity IN (".getEntity($orderstatic->element).")";
	$sql .= " AND c.fk_statut = ".Commande::STATUS_DRAFT;
	$sql .= " AND c.fk_soc = s.rowid";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, $maxofloop);
		startSimpleTable("DraftOrders", "commande/list.php", "search_status=".Commande::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;
			$othernb = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
					continue;
				}

				$orderstatic->id = $obj->rowid;
				$orderstatic->ref = $obj->ref;
				$orderstatic->ref_client = $obj->ref_client;
				$orderstatic->total_ht = $obj->total_ht;
				$orderstatic->total_tva = $obj->total_tva;
				$orderstatic->total_ttc = $obj->total_ttc;
				$orderstatic->statut = $obj->status;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowraponall tdoverflowmax125">'.$orderstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="nowrap right tdamount amount">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
				print '</td>';
				print "</tr>\n";
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoOrder");
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Draft purchase orders
 */

if ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight("fournisseur", "commande", "lire")) || (isModEnabled("supplier_order") && $user->hasRight("supplier_order", "lire"))) {
	$supplierorderstatic = new CommandeFournisseur($db);

	$sql = "SELECT cf.rowid, cf.ref, cf.ref_supplier, cf.total_ht, cf.total_tva, cf.total_ttc, cf.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE cf.entity IN (".getEntity($supplierorderstatic->element).")";
	$sql .= " AND cf.fk_statut = ".CommandeFournisseur::STATUS_DRAFT;
	$sql .= " AND cf.fk_soc = s.rowid";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND cf.fk_soc = ".((int) $socid);
	}

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, $maxofloop);
		startSimpleTable("DraftSuppliersOrders", "fourn/commande/list.php", "search_status=".CommandeFournisseur::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;
			$othernb = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
					continue;
				}

				$supplierorderstatic->id = $obj->rowid;
				$supplierorderstatic->ref = $obj->ref;
				$supplierorderstatic->ref_supplier = $obj->ref_supplier;
				$supplierorderstatic->total_ht = $obj->total_ht;
				$supplierorderstatic->total_tva = $obj->total_tva;
				$supplierorderstatic->total_ttc = $obj->total_ttc;
				$supplierorderstatic->statut = $obj->status;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->code_compta_client = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowraponall tdoverflowmax125">'.$supplierorderstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
				print '<td class="nowrap right tdamount amount">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
				print '</td>';
				print "</tr>\n";
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoOrder");
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Draft interventions
 */
if (isModEnabled('intervention')) {
	$sql = "SELECT f.rowid, f.ref, s.nom as name, f.fk_statut, f.duree as duration";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
	$sql .= " AND f.fk_soc = s.rowid";
	$sql .= " AND f.fk_statut = 0";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}


	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$nbofloop = min($num, $maxofloop);
		startSimpleTable("DraftFichinter", "fichinter/list.php", "search_status=".Fichinter::STATUS_DRAFT, 2, $num);

		//print '<tr class="liste_titre">';
		//print '<th colspan="2">'.$langs->trans("DraftFichinter").'</th></tr>';

		if ($num > 0) {
			$i = 0;
			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				$fichinterstatic->id=$obj->rowid;
				$fichinterstatic->ref=$obj->ref;
				$fichinterstatic->statut=$obj->fk_statut;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				print '<tr class="oddeven">';
				print '<td class="tdoverflowmax125">';
				print $fichinterstatic->getNomUrl(1);
				print "</td>";
				print '<td class="tdoverflowmax100">';
				print $companystatic->getNomUrl(1, 'customer');
				print '</td>';
				print '<td class="nowraponall tdoverflowmax100 right">';
				print convertSecondToTime($obj->duration);
				print '</td>';
				print '</tr>';
				$i++;
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoIntervention");
		finishSimpleTable(true);

		$db->free($resql);
	}
}


print '</div><div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';


/*
 * Last modified customers or prospects
 */
if (isModEnabled("societe") && $user->hasRight('societe', 'lire')) {
	$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= ", s.datec, s.tms";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.entity IN (".getEntity($companystatic->element).")";
	$sql .= " AND s.client IN (".Societe::CUSTOMER.", ".Societe::PROSPECT.", ".Societe::CUSTOMER_AND_PROSPECT.")";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	// Add where from hooks
	$parameters = array('socid' => $socid);
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $companystatic); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		if ($socid > 0) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
	}
	$sql .= $hookmanager->resPrint;
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS') && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
			$header = "BoxTitleLastCustomersOrProspects";
		} elseif (getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
			$header = "BoxTitleLastModifiedProspects";
		} else {
			$header = "BoxTitleLastModifiedCustomers";
		}

		$num = $db->num_rows($resql);
		startSimpleTable($langs->trans($header, min($max, $num)), "societe/list.php", "type=p,c&sortfield=s.tms&sortorder=DESC", 1, -1, 'company');

		if ($num) {
			$i = 0;

			while ($i < $num && $i < $max) {
				$objp = $db->fetch_object($resql);

				$companystatic->id = $objp->socid;
				$companystatic->name = $objp->name;
				$companystatic->name_alias = $objp->name_alias;
				$companystatic->code_client = $objp->code_client;
				$companystatic->code_compta = $objp->code_compta;
				$companystatic->client = $objp->client;
				$companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
				$companystatic->fournisseur = $objp->fournisseur;
				$companystatic->logo = $objp->logo;
				$companystatic->email = $objp->email;
				$companystatic->entity = $objp->entity;
				$companystatic->canvas = $objp->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowraponall tdoverflowmax100">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="nowrap">';
				//print $companystatic->getLibCustProspStatut();

				$obj = $companystatic;
				$s = '';
				if (($obj->client == 2 || $obj->client == 3) && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
					$s .= '<a class="customer-back opacitymedium" title="'.$langs->trans("Prospect").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Prospect"), 0, 1).'</a>';
				}
				if (($obj->client == 1 || $obj->client == 3) && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
					$s .= '<a class="customer-back" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Customer"), 0, 1).'</a>';
				}
				/*
				if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && $obj->fournisseur)
				{
					$s .= '<a class="vendor-back" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Supplier"), 0, 1).'</a>';
				}*/
				print $s;

				print '</td>';

				$datem = $db->jdate($objp->tms);
				print '<td class="right nowrap tddate" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
				print dol_print_date($datem, 'day', 'tzuserrel');
				print '</td>';
				print '</tr>';

				$i++;
			}
		}

		addSummaryTableLine(3, $num);
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Last modified proposals
 */

if (isModEnabled('propal')) {
	$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut as status, date_cloture as datec, c.tms as datem,";
	$sql .= " s.nom as socname, s.rowid as socid, s.canvas, s.client, s.email, s.code_compta";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as c,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	$sql .= " WHERE c.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND c.fk_soc = s.rowid";
	// If the internal user must only see his customers, force searching by him
	$search_sale = 0;
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$search_sale = $user->id;
	}
	// Search on sale representative
	if ($search_sale && $search_sale != '-1') {
		if ($search_sale == -2) {
			$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc)";
		} elseif ($search_sale > 0) {
			$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
		}
	}
	// Search on socid
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	$sql .= " ORDER BY c.tms DESC";

	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		startSimpleTable($langs->trans("LastModifiedProposals", $max), "comm/propal/list.php", "sortfield=p.tms&sortorder=DESC", 2, -1, 'propal');

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$propalstatic->id = $obj->rowid;
				$propalstatic->ref = $obj->ref;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->socname;
				$companystatic->client = $obj->client;
				$companystatic->canvas = $obj->canvas;
				$companystatic->email = $obj->email;
				$companystatic->code_compta = $obj->code_compta;

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->propal->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;

				print '<tr class="oddeven">';

				print '<td class="nowrap">';
				print '<table class="nobordernopadding">';
				print '<tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td width="16" class="nobordernopadding nowrap"></td>';
				print '<td width="16" class="nobordernopadding right">'.$formfile->getDocumentsLink($propalstatic->element, $filename, $filedir).'</td>';
				print '</tr>';
				print '</table>';
				print '</td>';

				print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';

				$datem = $db->jdate($obj->datem);
				print '<td class="center" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
				print dol_print_date($datem, 'day', 'tzuserrel');
				print '</td>';

				print '<td class="right">'.$propalstatic->LibStatut($obj->status, 3).'</td>';

				print '</tr>';

				$i++;
			}
		}

		finishSimpleTable(true);
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Latest modified orders
 */

if (isModEnabled('order')) {
	$commandestatic = new Commande($db);

	$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut as status, c.facture, c.date_cloture as datec, c.tms as datem,";
	$sql .= " s.nom as name, s.rowid as socid";
	$sql .= ", s.client";
	$sql .= ", s.code_client";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity IN (".getEntity('commande').")";
	//$sql.= " AND c.fk_statut > 2";
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " ORDER BY c.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		startSimpleTable($langs->trans("LastModifiedOrders", $max), "commande/list.php", "sortfield=c.tms&sortorder=DESC", 2, -1, 'order');

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				print '<td width="20%" class="nowrap">';

				$commandestatic->id = $obj->rowid;
				$commandestatic->ref = $obj->ref;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = $obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->canvas = $obj->canvas;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" class="nobordernopadding hideonsmartphone right">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->commande->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($commandestatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td class="nowrap">';
				print $companystatic->getNomUrl(1, 'company', 16);
				print '</td>';

				$datem = $db->jdate($obj->datem);
				print '<td class="center" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
				print dol_print_date($datem, 'day', 'tzuserrel');
				print '</td>';

				print '<td class="right">'.$commandestatic->LibStatut($obj->status, $obj->facture, 3).'</td>';
				print '</tr>';
				$i++;
			}
		}
		finishSimpleTable(true);
	} else {
		dol_print_error($db);
	}
}


/*
 * Last suppliers
 */
if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && $user->hasRight('societe', 'lire')) {
	$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= ", s.datec as dc, s.tms as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.entity IN (".getEntity($companystatic->element).")";
	$sql .= " AND s.fournisseur = ".Societe::SUPPLIER;
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	// Add where from hooks
	$parameters = array('socid' => $socid);
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $companystatic); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		if ($socid > 0) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
	}
	$sql .= $hookmanager->resPrint;
	$sql .= " ORDER BY s.datec DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		startSimpleTable($langs->trans("BoxTitleLastModifiedSuppliers", min($max, $num)), "societe/list.php", "type=f", 1, -1, 'company');

		if ($num) {
			$i = 0;
			while ($i < $num && $i < $max) {
				$objp = $db->fetch_object($resql);

				$companystatic->id = $objp->socid;
				$companystatic->name = $objp->name;
				$companystatic->name_alias = $objp->name_alias;
				$companystatic->code_client = $objp->code_client;
				$companystatic->code_compta = $objp->code_compta;
				$companystatic->client = $objp->client;
				$companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
				$companystatic->fournisseur = $objp->fournisseur;
				$companystatic->logo = $objp->logo;
				$companystatic->email = $objp->email;
				$companystatic->entity = $objp->entity;
				$companystatic->canvas = $objp->canvas;

				print '<tr class="oddeven">';
				print '<td class="nowraponall tdoverflowmax100">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
				print '<td>';

				$obj = $companystatic;
				$s = '';
				/*if (($obj->client == 2 || $obj->client == 3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) {
					$s .= '<a class="customer-back opacitymedium" title="'.$langs->trans("Prospect").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Prospect"), 0, 1).'</a>';
				}
				if (($obj->client == 1 || $obj->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
				{
					$s .= '<a class="customer-back" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Customer"), 0, 1).'</a>';
				}*/
				if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && $obj->fournisseur) {
					$s .= '<a class="vendor-back" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Supplier"), 0, 1).'</a>';
				}
				print $s;

				print '</td>';

				$datem = $db->jdate($objp->dm);
				print '<td class="right tddate" title="'.dol_escape_htmltag($langs->trans("DateModification").': '.dol_print_date($datem, 'dayhour', 'tzuserrel')).'">';
				print dol_print_date($datem, 'day', 'tzuserrel');
				print '</td>';
				print '</tr>';

				$i++;
			}
		}

		addSummaryTableLine(3, $num);
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Last actions
 */
/*if ($user->hasRight('agenda', 'myactions', 'read')) {
	show_array_last_actions_done($max);
}*/


/*
 * Actions to do
 */
/*if ($user->hasRight('agenda', 'myactions', 'read')) {
	show_array_actions_to_do($max);
}*/


/*
 * Latest contracts
 */
if (isModEnabled('contract') && $user->hasRight("contrat", "lire") && 0) { // TODO A REFAIRE DEPUIS NOUVEAU CONTRAT
	$staticcontrat = new Contrat($db);

	$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= ", c.statut, c.rowid as contratid, p.ref, c.fin_validite as datefin, c.date_cloture as dateclo";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."contrat as c";
	$sql .= ", ".MAIN_DB_PREFIX."product as p";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.entity IN (".getEntity($staticcontrat->element).")";
	$sql .= " AND c.fk_soc = s.rowid";
	$sql .= " AND c.fk_product = p.rowid";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY c.tms DESC";
	$sql .= $db->plimit($max + 1, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		startSimpleTable($langs->trans("LastContracts", $max), "", "", 2);

		if ($num > 0) {
			$i = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				$staticcontrat->id = $obj->contratid;
				$staticcontrat->ref = $obj->ref;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">'.$staticcontrat->getNomUrl(1).'</td>';
				print '<td class="tdoverflowmax150">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="right">'.$staticcontrat->LibStatut($obj->statut, 3).'</td>';
				print '</tr>';

				$i++;
			}
		}

		addSummaryTableLine(2, $num);
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Opened (validated) proposals
 */
if (isModEnabled("propal") && $user->hasRight("propal", "lire")) {
	$sql = "SELECT p.rowid as propalid, p.entity, p.total_ttc, p.total_ht, p.total_tva, p.ref, p.ref_client, p.fk_statut, p.datep as dp, p.fin_validite as dfv";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_soc = s.rowid";
	$sql .= " AND p.fk_statut = ".Propal::STATUS_VALIDATED;
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY p.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = $total_ttc = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("ProposalsOpened", "comm/propal/list.php", "search_status=1", 4, $num);

		if ($num > 0) {
			$i = 0;
			$othernb = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$propalstatic->id = $obj->propalid;
				$propalstatic->ref = $obj->ref;
				$propalstatic->ref_client = $obj->ref_client;
				$propalstatic->total_ht = $obj->total_ht;
				$propalstatic->total_tva = $obj->total_tva;
				$propalstatic->total_ttc = $obj->total_ttc;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->propal->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				//$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				$warning = ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) ? img_warning($langs->trans("Late")) : '';

				print '<tr class="oddeven">';

				print '<td class="nowrap" width="140">';
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowraponall">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td width="18" class="nobordernopadding nowrap">'.$warning.'</td>';
				print '<td width="16" align="center" class="nobordernopadding">'.$formfile->getDocumentsLink($propalstatic->element, $filename, $filedir).'</td>';
				print '</tr>';
				print '</table>';
				print '</td>';

				print '<td class="tdoverflowmax150">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				$datem = $db->jdate($obj->dp);
				print '<td class="center tddate" title="'.dol_escape_htmltag($langs->trans("Date").': '.dol_print_date($datem, 'day', 'tzserver')).'">';
				print dol_print_date($datem, 'day', 'tzserver');
				print '</td>';
				print '<td class="right tdamount amount">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '<td align="center" width="14">'.$propalstatic->LibStatut($obj->fk_statut, 3).'</td>';

				print '</tr>';

				$i++;
				$total += $obj->total_ht;
				$total_ttc += $obj->total_ttc;
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}
		}

		addSummaryTableLine(5, $num, $nbofloop, !getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $total_ttc : $total, "NoProposal", true);
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Opened (validated) order
 */
if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
	$sql = "SELECT c.rowid as commandeid, c.total_ttc, c.total_ht, c.total_tva, c.ref, c.ref_client, c.fk_statut, c.date_valid as dv, c.facture as billed";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.entity IN (".getEntity($orderstatic->element).")";
	$sql .= " AND c.fk_soc = s.rowid";
	$sql .= " AND c.fk_statut IN (".Commande::STATUS_VALIDATED.", ".Commande::STATUS_SHIPMENTONPROCESS.")";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY c.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = $total_ttc = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("OrdersOpened", "commande/list.php", "search_status=".Commande::STATUS_VALIDATED, 4, $num);

		if ($num > 0) {
			$i = 0;
			$othernb = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				if ($i >= $max) {
					$othernb += 1;
					$i++;
					$total += $obj->total_ht;
					$total_ttc += $obj->total_ttc;
					continue;
				}

				$orderstatic->id = $obj->commandeid;
				$orderstatic->ref = $obj->ref;
				$orderstatic->ref_client = $obj->ref_client;
				$orderstatic->statut = $obj->fk_statut;
				$orderstatic->total_ht = $obj->total_ht;
				$orderstatic->total_tva = $obj->total_tva;
				$orderstatic->total_ttc = $obj->total_ttc;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->name_alias = $obj->name_alias;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_compta = $obj->code_compta;
				$companystatic->client = $obj->client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->code_compta_fournisseur = $obj->code_compta_fournisseur;
				$companystatic->fournisseur = $obj->fournisseur;
				$companystatic->logo = $obj->logo;
				$companystatic->email = $obj->email;
				$companystatic->entity = $obj->entity;
				$companystatic->canvas = $obj->canvas;

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->commande->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				//$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				//$warning = ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) ? img_warning($langs->trans("Late")) : '';

				print '<tr class="oddeven">';

				print '<td class="nowrap" width="140">';
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowraponall">'.$orderstatic->getNomUrl(1).'</td>';
				print '<td width="18" class="nobordernopadding nowrap"></td>';
				print '<td width="16" align="center" class="nobordernopadding">'.$formfile->getDocumentsLink($orderstatic->element, $filename, $filedir).'</td>';
				print '</tr>';
				print '</table>';
				print '</td>';

				print '<td class="tdoverflowmax150">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				$datem = $db->jdate($obj->dv);
				print '<td class="center tddate" title="'.dol_escape_htmltag($langs->trans("DateValue").': '.dol_print_date($datem, 'day', 'tzserver')).'">';
				print dol_print_date($datem, 'day', 'tzserver');
				print '</td>';

				print '<td class="right tdamount amount">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '<td align="center" width="14">'.$orderstatic->LibStatut($obj->fk_statut, $obj->billed, 3).'</td>';

				print '</tr>';

				$i++;
				$total += $obj->total_ht;
				$total_ttc += $obj->total_ttc;
			}

			if ($othernb) {
				print '<tr class="oddeven">';
				print '<td class="nowrap" colspan="5">';
				print '<span class="opacitymedium">'.$langs->trans("More").'... ('.$othernb.')</span>';
				print '</td>';
				print "</tr>\n";
			}
		}

		addSummaryTableLine(5, $num, $nbofloop, !getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $total_ttc : $total, "None", true);
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div>';
print '</div>';
print '</div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardCommercials', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
