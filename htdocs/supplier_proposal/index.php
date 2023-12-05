<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
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

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('suppliersproposalsindex'));

// Load translation files required by the page
$langs->loadLangs(array('supplier_proposal', 'companies'));

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
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
$help_url = "EN:Module_Ask_Price_Supplier|FR:Module_Demande_de_prix_fournisseur";

llxHeader("", $langs->trans("SupplierProposalArea"), $help_url);

print load_fiche_titre($langs->trans("SupplierProposalArea"), '', 'supplier_proposal');

print '<div class="fichecenter"><div class="fichethirdleft">';

// Statistics

$sql = "SELECT count(p.rowid), p.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."supplier_proposal as p";
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE p.fk_soc = s.rowid";
$sql .= " AND p.entity IN (".getEntity('supplier_proposal').")";
if ($user->socid) {
	$sql .= ' AND p.fk_soc = '.((int) $user->socid);
}
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " AND p.fk_statut IN (0,1,2,3,4)";
$sql .= " GROUP BY p.fk_statut";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$total = 0;
	$totalinprocess = 0;
	$dataseries = array();
	$colorseries = array();
	$vals = array();
	// -1=Canceled, 0=Draft, 1=Validated, (2=Accepted/On process not managed for sales orders), 3=Closed (Sent/Received, billed or not)
	while ($i < $num) {
		$row = $db->fetch_row($resql);
		if ($row) {
			//if ($row[1]!=-1 && ($row[1]!=3 || $row[2]!=1))
			{
				$vals[$row[1]] = $row[0];
				$totalinprocess += $row[0];
			}
			$total += $row[0];
		}
		$i++;
	}
	$db->free($resql);

	include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("CommRequests").'</th></tr>'."\n";
	$listofstatus = array(0, 1, 2, 3, 4);
	foreach ($listofstatus as $status) {
		$dataseries[] = array($supplier_proposalstatic->LibStatut($status, 1), (isset($vals[$status]) ? (int) $vals[$status] : 0));
		if ($status == SupplierProposal::STATUS_DRAFT) {
			$colorseries[$status] = '-'.$badgeStatus0;
		}
		if ($status == SupplierProposal::STATUS_VALIDATED) {
			$colorseries[$status] = $badgeStatus1;
		}
		if ($status == SupplierProposal::STATUS_SIGNED) {
			$colorseries[$status] = $badgeStatus4;
		}
		if ($status == SupplierProposal::STATUS_NOTSIGNED) {
			$colorseries[$status] = $badgeStatus9;
		}
		if ($status == SupplierProposal::STATUS_CLOSE) {
			$colorseries[$status] = $badgeStatus6;
		}

		if (empty($conf->use_javascript_ajax)) {
			print '<tr class="oddeven">';
			print '<td>'.$supplier_proposalstatic->LibStatut($status, 0).'</td>';
			print '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
			print "</tr>\n";
		}
	}
	if ($conf->use_javascript_ajax) {
		print '<tr><td class="center" colspan="2">';

		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->SetDataColor(array_values($colorseries));
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent(1);
		$dolgraph->SetType(array('pie'));
		$dolgraph->setHeight('200');
		$dolgraph->draw('idgraphstatus');
		print $dolgraph->show($total ? 0 : 1);

		print '</td></tr>';
	}

	print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">'.$total.'</td></tr>';
	print "</table></div><br>";
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
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE c.fk_soc = s.rowid";
	$sql .= " AND c.entity = ".$conf->entity;
	$sql .= " AND c.fk_statut = 0";
	if ($socid) {
		$sql .= " AND c.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
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
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity = ".$conf->entity;
//$sql.= " AND c.fk_statut > 2";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
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
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.fk_soc = s.rowid";
	$sql .= " AND p.entity IN (".getEntity('supplier_proposal').")";
	$sql .= " AND p.fk_statut = 1";
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
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
			print '<tr class="liste_titre"><th colspan="5">'.$langs->trans("RequestsOpened").' <a href="'.DOL_URL_ROOT.'/supplier_proposal/list.php?search_status=1"><span class="badge">'.$num.'</span></a></th></tr>';

			$nbofloop = min($num, (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
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
				if ($db->jdate($obj->dfv) < ($now - $conf->supplier_proposal->cloture->warning_delay)) {
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
