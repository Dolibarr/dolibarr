<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/supplier_proposal/index.php
 *	\ingroup    supplier_proposal
 *	\brief      Home page of vendor proposal area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/dashboard.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('suppliersproposalsindex'));

// Load translation files required by the page
$langs->loadLangs(array('supplier_proposal', 'companies'));

// Security check
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$result = restrictedArea($user, 'supplier_proposal');


/*
 * View
 */
$now = dol_now();
$supplier_proposalstatic = new SupplierProposal($db);
$companystatic = new Societe($db);
$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("SupplierProposalArea");
$help_url = "EN:Module_Ask_Price_Supplier|FR:Module_Demande_de_prix_fournisseur";

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-supplierproposal page-index');

print load_fiche_titre($langs->trans("SupplierProposalArea"), '', 'supplier_proposal');

print '<div class="fichecenter"><div class="fichethirdleft">';

// Statistics

$sql = "SELECT count(p.rowid), p.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."supplier_proposal as p";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE p.fk_soc = s.rowid";
$sql .= " AND p.entity IN (".getEntity('supplier_proposal').")";
if ($user->socid) {
	$sql .= ' AND p.fk_soc = '.((int) $user->socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " AND p.fk_statut IN (0,1,2,3,4)";
$sql .= " GROUP BY p.fk_statut";
$resql = $db->query($sql);
if ($resql) {
	$vals = array();
	while ($row = $db->fetch_row($resql)) {
		$vals[$row[1]] = $row[0];
	}
	$db->free($resql);

	$colors = getThemeBadgeStatusColors();
	$colorofstatus = array(
		SupplierProposal::STATUS_DRAFT => '-'.$colors[0],
		SupplierProposal::STATUS_VALIDATED => $colors[1],
		SupplierProposal::STATUS_SIGNED => $colors[4],
		SupplierProposal::STATUS_NOTSIGNED => $colors[9],
		SupplierProposal::STATUS_CLOSE => $colors[6],
	);
	$series = array();
	foreach (array(0, 1, 2, 3, 4) as $status) {
		$series[] = array(
			'label' => $supplier_proposalstatic->LibStatut($status, 1),
			'labelnojs' => $supplier_proposalstatic->LibStatut($status, 0),
			'nb' => (isset($vals[$status]) ? (int) $vals[$status] : 0),
			'color' => $colorofstatus[$status],
			'url' => 'list.php?search_status='.$status,
		);
	}
	print getStatusPieChart($langs->trans("Statistics").' - '.$langs->trans("CommRequests"), $series);
} else {
	dol_print_error($db);
}


/*
 * Draft askprice
 */
if (isModEnabled('supplier_proposal')) {
	$sql = "SELECT c.rowid, c.ref, s.nom as socname, s.rowid as socid, s.canvas, s.client";
	$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as c";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity = ".((int) $conf->entity);
	$sql .= " AND c.fk_statut = 0";
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("DraftRequests").'</th></tr>';
		$langs->load("supplier_proposal");
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';
				$supplier_proposalstatic->id = $obj->rowid;
				$supplier_proposalstatic->ref = $obj->ref;
				print '<td class="nowrap">'.$supplier_proposalstatic->getNomUrl(1).'</td>';

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->socname;
				$companystatic->client = $obj->client;
				$companystatic->canvas = $obj->canvas;
				print '<td>'.$companystatic->getNomUrl(1, 'customer', 24).'</td>';

				print '</tr>';
				$i++;
			}
		}
		print "</table></div><br>";
	}
}

print '</div><div class="fichetwothirdright">';


$max = 5;

/*
 * Last modified askprice
 */

$sql = "SELECT c.rowid, c.ref, c.fk_statut, s.nom as socname, s.rowid as socid, s.canvas, s.client,";
$sql .= " date_cloture as datec";
$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity = ".((int) $conf->entity);
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
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LastModifiedRequests", $max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="20%" class="nowrap">';

			$supplier_proposalstatic->id = $obj->rowid;
			$supplier_proposalstatic->ref = $obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">';
			print $supplier_proposalstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->supplier_proposal->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($supplier_proposalstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->socname;
			$companystatic->client = $obj->client;
			$companystatic->canvas = $obj->canvas;
			print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';

			print '<td>'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';
			print '<td class="right">'.$supplier_proposalstatic->LibStatut($obj->fk_statut, 3).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table></div><br>";
} else {
	dol_print_error($db);
}


/*
 * Opened askprice
 */
if (isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire')) {
	$langs->load("supplier_proposal");

	$now = dol_now();

	$sql = "SELECT s.nom as socname, s.rowid as socid, s.canvas, s.client, p.rowid as supplier_proposalid, p.total_ttc, p.total_tva, p.total_ht, p.ref, p.fk_statut, p.datec as dp";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."supplier_proposal as p";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.fk_soc = s.rowid";
	$sql .= " AND p.entity IN (".getEntity('supplier_proposal').")";
	$sql .= " AND p.fk_statut = 1";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY p.rowid DESC";

	$result = $db->query($sql);
	if ($result) {
		$total = 0;
		$num = $db->num_rows($result);
		$i = 0;
		if ($num > 0) {
			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre"><th colspan="5">'.$langs->trans("RequestsOpened");
			print ' <a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?search_status=1" alt="'.$langs->trans("GoOnList").'"><span class="badge">'.$num.'</span></a>';
			print '</th></tr>';

			$nbofloop = min($num, getDolGlobalString('MAIN_MAXLIST_OVERLOAD', 500));
			while ($i < $nbofloop) {
				$obj = $db->fetch_object($result);

				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowrap" width="140">';

				$supplier_proposalstatic->id = $obj->supplier_proposalid;
				$supplier_proposalstatic->ref = $obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">';
				print $supplier_proposalstatic->getNomUrl(1);
				print '</td>';
				print '<td width="18" class="nobordernopadding nowrap">';
				if (isset($obj->dfv) && $db->jdate($obj->dfv) < ($now - $conf->supplier_proposal->cloture->warning_delay)) {
					print img_warning($langs->trans("Late"));
				}
				print '</td>';
				print '<td width="16" class="center nobordernopadding">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->supplier_proposal->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->supplier_proposalid;
				print $formfile->getDocumentsLink($supplier_proposalstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print "</td>";

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->socname;
				$companystatic->client = $obj->client;
				$companystatic->canvas = $obj->canvas;
				print '<td class="left">'.$companystatic->getNomUrl(1, 'customer', 44).'</td>'."\n";

				print '<td class="right">';
				print dol_print_date($db->jdate($obj->dp), 'day').'</td>'."\n";
				print '<td class="right">'.price($obj->total_ttc).'</td>';
				print '<td class="center" width="14">'.$supplier_proposalstatic->LibStatut($obj->fk_statut, 3).'</td>'."\n";
				print '</tr>'."\n";
				$i++;
				$total += $obj->total_ttc;
			}
			if ($num > $nbofloop) {
				print '<tr class="liste_total"><td colspan="5">'.$langs->trans("XMoreLines", ($num - $nbofloop))."</td></tr>";
			} elseif ($total > 0) {
				print '<tr class="liste_total"><td colspan="3">'.$langs->trans("Total").'</td><td class="right">'.price($total)."</td><td>&nbsp;</td></tr>";
			}
			print "</table></div><br>";
		}
	} else {
		dol_print_error($db);
	}
}

print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardSupplierProposal', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
