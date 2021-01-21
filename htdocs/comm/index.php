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

require '../main.inc.php';

if (!$user->rights->societe->lire) accessforbidden();

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('commercialindex'));

// Load translation files required by the page
$langs->loadLangs(array("boxes", "commercial", "contracts", "orders", "propal", "supplier_proposal"));

$action = GETPOST('action', 'aZ09');
$bid = GETPOST('bid', 'int');

// Securite acces client
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = $conf->global->MAIN_SIZE_SHORTLIST_LIMIT;
$now = dol_now();

/*
 * Actions
 */


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$companystatic = new Societe($db);
if (!empty($conf->propal->enabled)) $propalstatic = new Propal($db);
if (!empty($conf->supplier_proposal->enabled)) $supplierproposalstatic = new SupplierProposal($db);
if (!empty($conf->commande->enabled)) $orderstatic = new Commande($db);
if (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled)) $supplierorderstatic = new CommandeFournisseur($db);

llxHeader("", $langs->trans("CommercialArea"));

print load_fiche_titre($langs->trans("CommercialArea"), '', 'commercial');

print '<div class="fichecenter"><div class="fichethirdleft">';

// This is useless due to the global search combo
if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS)) {
	// Search proposal
	if (!empty($conf->propal->enabled) && $user->rights->propal->lire) {
		$listofsearchfields['search_proposal'] = array('text'=>'Proposal');
	}
	// Search customer order
	if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
		$listofsearchfields['search_customer_order'] = array('text'=>'CustomerOrder');
	}
	// Search supplier proposal
	if (!empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire) {
		$listofsearchfields['search_supplier_proposal'] = array('text'=>'SupplierProposalShort');
	}
	// Search supplier order
	if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled)) && $user->rights->fournisseur->commande->lire) {
		$listofsearchfields['search_supplier_order'] = array('text'=>'SupplierOrder');
	}
	// Search intervention
	if (!empty($conf->ficheinter->enabled) && $user->rights->ficheinter->lire) {
		$listofsearchfields['search_intervention'] = array('text'=>'Intervention');
	}
	// Search contract
	if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire) {
		$listofsearchfields['search_contract'] = array('text'=>'Contract');
	}

	if (count($listofsearchfields)) {
		print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';
		$i = 0;
		foreach ($listofsearchfields as $key => $value) {
			if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
			print '<tr '.$bc[false].'>';
			print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
			if ($i == 0) print '<td class="noborderbottom" rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button "></td>';
			print '</tr>';
			$i++;
		}
		print '</table>';
		print '</div>';
		print '</form>';
		print '<br>';
	}
}


/*
 * Draft customer proposals
 */
if (!empty($conf->propal->enabled) && $user->rights->propal->lire) {
	$sql = "SELECT p.rowid, p.ref, p.ref_client, p.total_ht, p.tva as total_tva, p.total as total_ttc, p.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_soc = s.rowid";
	$sql .= " AND p.fk_statut = ".Propal::STATUS_DRAFT;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid)	$sql .= " AND s.rowid = ".$socid;

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("ProposalsDraft", "comm/propal/list.php", "search_status=".Propal::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

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
				print '<td class="nowrap tdoverflowmax100">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="nowrap right tdamount">'.price((!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc)).'</td>';
				print '</tr>';

				$i++;
				$total += (!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc);
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
if (!empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire) {
	$sql = "SELECT p.rowid, p.ref, p.total_ht, p.tva as total_tva, p.total as total_ttc, p.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as p,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE p.entity IN (".getEntity($supplierproposalstatic->element).")";
	$sql .= " AND p.fk_statut = ".SupplierProposal::STATUS_DRAFT;
	$sql .= " AND p.fk_soc = s.rowid";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("SupplierProposalsDraft", "supplier_proposal/list.php", "search_status=".SupplierProposal::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

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
				print '<td class="nowrap tdoverflowmax100">'.$supplierproposalstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
				print '<td class="nowrap right tdamount">'.price(!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc);
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
 * Draft customer orders
 */
if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.tva as total_tva, c.total_ttc, c.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE c.entity IN (".getEntity($orderstatic->element).")";
	$sql .= " AND c.fk_statut = ".Commande::STATUS_DRAFT;
	$sql .= " AND c.fk_soc = s.rowid";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND c.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("DraftOrders", "commande/list.php", "search_status=".Commande::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

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
				print '<td class="nowrap tdoverflowmax100">'.$orderstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="nowrap right tdamount">'.price(!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc);
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
 * Draft purchase orders
 */
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled)) && $user->rights->fournisseur->commande->lire) {
	$sql = "SELECT cf.rowid, cf.ref, cf.ref_supplier, cf.total_ttc, cf.fk_statut as status";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf,";
	$sql .= " ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE cf.entity IN (".getEntity($supplierorderstatic->element).")";
	$sql .= " AND cf.fk_statut = ".CommandeFournisseur::STATUS_DRAFT;
	$sql .= " AND cf.fk_soc = s.rowid";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND cf.fk_soc = ".$socid;

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("DraftSuppliersOrders", "fourn/commande/list.php", "search_status=".CommandeFournisseur::STATUS_DRAFT, 2, $num);

		if ($num > 0) {
			$i = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				$supplierorderstatic->id = $obj->rowid;
				$supplierorderstatic->ref = $obj->ref;
				$supplierorderstatic->ref_supplier = $obj->ref_suppliert;
				$supplierorderstatic->total_ht = $obj->total_ht;
				$supplierorderstatic->total_tva = $obj->total_tva;
				$supplierorderstatic->total_ttc = $obj->total_ttc;
				$supplierorderstatic->statut = $obj->status;

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
				print '<td class="nowrap tdoverflowmax100">'.$supplierorderstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
				print '<td class="nowrap right tdamount">'.price(!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc);
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoProposal");
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div><div class="fichetwothirdright">';
print '<div class="ficheaddleft">';

/*
 * Last modified customers or prospects
 */
if (!empty($conf->societe->enabled) && $user->rights->societe->lire) {
	$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= ", s.datec, s.tms";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.entity IN (".getEntity($companystatic->element).")";
	$sql .= " AND s.client IN (".Societe::CUSTOMER.", ".Societe::PROSPECT.", ".Societe::CUSTOMER_AND_PROSPECT.")";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = $socid";
	$sql .= " ORDER BY s.tms DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) {
			$header = "BoxTitleLastCustomersOrProspects";
		}
		elseif (!empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) {
			$header = "BoxTitleLastModifiedProspects";
		}
		else {
			$header = "BoxTitleLastModifiedCustomers";
		}

		$num = $db->num_rows($resql);
		startSimpleTable($langs->trans($header, min($max, $num)), "societe/list.php", "type=p,c", 1);

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
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="nowrap">';
				//print $companystatic->getLibCustProspStatut();

				$obj = $companystatic;
				$s = '';
				if (($obj->client == 2 || $obj->client == 3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
				{
					$s .= '<a class="customer-back opacitymedium" title="'.$langs->trans("Prospect").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Prospect"), 0, 1).'</a>';
				}
				if (($obj->client == 1 || $obj->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
				{
					$s .= '<a class="customer-back" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Customer"), 0, 1).'</a>';
				}
				/*
				if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) && $obj->fournisseur)
				{
					$s .= '<a class="vendor-back" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Supplier"), 0, 1).'</a>';
				}*/
				print $s;

				print '</td>';
				print '<td class="right nowrap tddate">'.dol_print_date($db->jdate($objp->tms), 'day').'</td>';
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
 * Last suppliers
 */
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) && $user->rights->societe->lire) {
	$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= ", s.datec as dc, s.tms as dm";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$user->socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE s.entity IN (".getEntity($companystatic->element).")";
	$sql .= " AND s.fournisseur = ".Societe::SUPPLIER;
	if (!$user->rights->societe->client->voir && !$user->socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
	$sql .= " ORDER BY s.datec DESC";
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		startSimpleTable($langs->trans("BoxTitleLastModifiedSuppliers", min($max, $num)), "societe/list.php", "type=f", 1);

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
				print '<td class="nowrap tdoverflowmax100">'.$companystatic->getNomUrl(1, 'supplier').'</td>';
				print '<td>';

				$obj = $companystatic;
				$s = '';
				/*if (($obj->client == 2 || $obj->client == 3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
				{
					$s .= '<a class="customer-back opacitymedium" title="'.$langs->trans("Prospect").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Prospect"), 0, 1).'</a>';
				}
				if (($obj->client == 1 || $obj->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS))
				{
					$s .= '<a class="customer-back" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Customer"), 0, 1).'</a>';
				}*/
				if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled)) && $obj->fournisseur)
				{
					$s .= '<a class="vendor-back" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$companystatic->id.'">'.dol_substr($langs->trans("Supplier"), 0, 1).'</a>';
				}
				print $s;

				print '</td>';
				print '<td class="right tddate">'.dol_print_date($db->jdate($objp->dm), 'day').'</td>';
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
if ($user->rights->agenda->myactions->read) {
	show_array_last_actions_done($max);
}


/*
 * Actions to do
 */
if ($user->rights->agenda->myactions->read) {
	show_array_actions_to_do($max);
}


/*
 * Latest contracts
 */
if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire && 0) { // TODO A REFAIRE DEPUIS NOUVEAU CONTRAT
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
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE c.entity IN (".getEntity($staticcontrat->element).")";
	$sql .= " AND c.fk_soc = s.rowid";
	$sql .= " AND c.fk_product = p.rowid";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
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
				print '<td>'.$staticcontrat->getNomUrl(1).'</td>';
				print '<td>'.$companystatic->getNomUrl(1, 'customer', 44).'</td>';
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
if (!empty($conf->propal->enabled) && $user->rights->propal->lire) {
	$sql = "SELECT p.rowid as propalid, p.entity, p.total as total_ttc, p.total_ht, p.tva as total_tva, p.ref, p.ref_client, p.fk_statut, p.datep as dp, p.fin_validite as dfv";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_soc = s.rowid";
	$sql .= " AND p.fk_statut = ".Propal::STATUS_VALIDATED;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
	$sql .= " ORDER BY p.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = $total_ttc = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
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
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				$warning = ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) ? img_warning($langs->trans("Late")) : '';

				print '<tr class="oddeven">';

				print '<td class="nowrap" width="140">';
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td width="18" class="nobordernopadding nowrap">'.$warning.'</td>';
				print '<td width="16" align="center" class="nobordernopadding">'.$formfile->getDocumentsLink($propalstatic->element, $filename, $filedir).'</td>';
				print '</tr>';
				print '</table>';
				print '</td>';

				print '<td class="nowrap">'.$companystatic->getNomUrl(1, 'customer', 44).'</td>';
				print '<td class="right tddate">'.dol_print_date($db->jdate($obj->dp), 'day').'</td>';
				print '<td class="right tdamount">'.price(!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc).'</td>';
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

		addSummaryTableLine(5, $num, $nbofloop, empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $total_ttc : $total, "NoProposal", true);
		finishSimpleTable(true);

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}


/*
 * Opened (validated) order
 */
if (!empty($conf->commande->enabled) && $user->rights->commande->lire) {
	$sql = "SELECT c.rowid as commandeid, c.total_ttc, c.total_ht, c.tva as total_tva, c.ref, c.ref_client, c.fk_statut, c.date_valid as dv, c.facture as billed";
	$sql .= ", s.rowid as socid, s.nom as name, s.name_alias";
	$sql .= ", s.code_client, s.code_compta, s.client";
	$sql .= ", s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur";
	$sql .= ", s.logo, s.email, s.entity";
	$sql .= ", s.canvas";
	$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE c.entity IN (".getEntity($orderstatic->element).")";
	$sql .= " AND c.fk_soc = s.rowid";
	$sql .= " AND c.fk_statut IN (".Commande::STATUS_VALIDATED.", ".Commande::STATUS_SHIPMENTONPROCESS.")";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
	$sql .= " ORDER BY c.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = $total_ttc = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
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
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->propalid;
				//$warning = ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) ? img_warning($langs->trans("Late")) : '';

				print '<tr class="oddeven">';

				print '<td class="nowrap" width="140">';
				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">'.$orderstatic->getNomUrl(1).'</td>';
				print '<td width="18" class="nobordernopadding nowrap"></td>';
				print '<td width="16" align="center" class="nobordernopadding">'.$formfile->getDocumentsLink($orderstatic->element, $filename, $filedir).'</td>';
				print '</tr>';
				print '</table>';
				print '</td>';

				print '<td class="nowrap">'.$companystatic->getNomUrl(1, 'customer', 44).'</td>';
				print '<td class="right tddate">'.dol_print_date($db->jdate($obj->dp), 'day').'</td>';
				print '<td class="right tdamount">'.price(!empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $obj->total_ht : $obj->total_ttc).'</td>';
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

		addSummaryTableLine(5, $num, $nbofloop, empty($conf->global->MAIN_DASHBOARD_USE_TOTAL_HT) ? $total_ttc : $total, "None", true);
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
