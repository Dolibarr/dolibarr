<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2019	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2019-2026  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2025		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/mrp/index.php
 *       \ingroup    bom, mrp
 *       \brief      Home page for BOM and MRP modules
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/dashboard.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('mrpindex'));

// Load translation files required by the page
$langs->loadLangs(array("companies", "mrp"));

// Security check
$result = restrictedArea($user, 'bom|mrp');

$max = getDolUserInt('MAIN_SIZE_SHORTLIST_LIMIT', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5));


/*
 * Actions
 */

if (GETPOST('addbox')) {
	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOSTINT('areacode');
	$userid = GETPOSTINT('userid');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');
	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) {
		setEventMessages($langs->trans("BoxAdded"), null);
	}
}


/*
 * View
 */

$staticbom = new BOM($db);
$staticmo = new Mo($db);

$title = $langs->trans('MRP');
$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag';

// Load $resultboxes
$resultboxes = FormOther::getBoxesArea($user, "6");

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-mrp page-index');

print load_fiche_titre($langs->trans("MRPArea"), $resultboxes['selectboxlist'], 'mrp');


print '<div class="fichecenter">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';


/*
 * Statistics
 */

if (isModEnabled('mrp')) {
	$sql = "SELECT COUNT(t.rowid) as nb, status";
	$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as t";
	$sql .= " WHERE t.entity IN (".getEntity('mo').")";
	$sql .= " GROUP BY t.status";
	$sql .= " ORDER BY t.status ASC";
	$resql = $db->query($sql);

	if ($resql) {
		$vals = array();
		while ($obj = $db->fetch_object($resql)) {
			$vals[$obj->status] = $obj->nb;
		}
		$db->free($resql);

		$colors = getThemeBadgeStatusColors();
		$colorofstatus = array(
			Mo::STATUS_DRAFT => '-'.$colors[0],
			Mo::STATUS_VALIDATED => $colors[1],
			Mo::STATUS_INPROGRESS => $colors[4],
			Mo::STATUS_PRODUCED => $colors[6],
			Mo::STATUS_CANCELED => $colors[9],
		);
		$series = array();
		foreach (array(0, 1, 2, 3, 9) as $status) {
			$series[] = array(
				'label' => $staticmo->LibStatut($status, 1),
				'labelnojs' => $staticmo->LibStatut($status, 0),
				'nb' => (isset($vals[$status]) ? (int) $vals[$status] : 0),
				'color' => $colorofstatus[$status],
				'url' => 'mo_list.php?search_status='.$status,
			);
		}
		print getStatusPieChart($langs->trans("Statistics").' - '.$langs->trans("ManufacturingOrder"), $series, array('total' => false));
	} else {
		dol_print_error($db);
	}
}

print '<br>';


print $resultboxes['boxlista'];

print '</div><div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';


/*
 * Last modified BOM
 */

if (isModEnabled('bom')) {
	$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem, a.status, a.fk_product";
	$sql .= " FROM ".MAIN_DB_PREFIX."bom_bom as a";
	$sql .= " WHERE a.entity IN (".getEntity('bom').")";
	$sql .= $db->order("a.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("LatestBOMModified", $max);
		$lastmodified = '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?sortfield=t.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		$lastmodified .= '<span class="badge marginleftonlyshort">...</span>';
		$lastmodified .= '</a>';
		print $lastmodified;
		print '</th>';
		print '<th class="right">';
		//print '<a href="'.DOL_URL_ROOT.'/bom/bom_list.php?sortfield=t.tms&sortorder=DESC">'.img_picto($langs->trans("FullList"), 'bom');
		print '</th>';
		print '</tr>';

		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$staticbom->id = $obj->rowid;
				$staticbom->ref = $obj->ref;
				$staticbom->fk_product = $obj->fk_product;
				$staticbom->date_modification = $obj->datem;
				$staticbom->status = $obj->status;

				print '<tr class="oddeven">';
				print '<td>'.$staticbom->getNomUrl(1, '32').'</td>';
				print '<td>'.dol_print_date($db->jdate($obj->datem), 'dayhour').'</td>';
				print '<td class="right">'.$staticbom->getLibStatut(3).'</td>';
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}


/*
 * Last modified MOs
 */

if (isModEnabled('mrp')) {
	$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem, a.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as a";
	$sql .= " WHERE a.entity IN (".getEntity('mo').")";
	$sql .= $db->order("a.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$sql = "SELECT a.rowid, a.status, a.ref, a.tms as datem, a.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as a";
	$sql .= " WHERE a.entity IN (".getEntity('mo').")";
	$sql .= $db->order("a.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("LatestMOModified", $max);
		$lastmodified = '<a href="'.DOL_URL_ROOT.'/mrp/mo_list.php?sortfield=t.tms&sortorder=DESC" title="'.$langs->trans("FullList").'">';
		$lastmodified .= '<span class="badge marginleftonlyshort">...</span>';
		$lastmodified .= '</a>';
		print $lastmodified;
		print '</th>';
		print '<th class="right">';
		//print '<a href="'.DOL_URL_ROOT.'/mrp/mo_list.php?sortfield=t.tms&sortorder=DESC">'.img_picto($langs->trans("FullList"), 'mrp');
		print '</th>';
		print '</tr>';

		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				$staticmo->id = $obj->rowid;
				$staticmo->ref = $obj->ref;
				$staticmo->date_modification = $obj->datem;
				$staticmo->status = $obj->status;

				print '<tr class="oddeven">';
				print '<td>'.$staticmo->getNomUrl(1, '32').'</td>';
				print '<td>'.dol_print_date($db->jdate($obj->datem), 'dayhour').'</td>';
				print '<td class="right">'.$staticmo->getLibStatut(3).'</td>';
				print '</tr>';
				$i++;
			}
		} else {
			print '<tr class="oddeven">';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';
		print '<br>';
	} else {
		dol_print_error($db);
	}
}

print $resultboxes['boxlistb'];

print '</div></div></div>';

$object = new stdClass();
$parameters = array(
	//'type' => $type,
	'user' => $user,
);
$reshook = $hookmanager->executeHooks('dashboardMRP', $parameters, $object);

// End of page
llxFooter();
$db->close();
