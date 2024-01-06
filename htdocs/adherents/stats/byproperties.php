<?php
/* Copyright (c) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/adherents/stats/byproperties.php
 *      \ingroup    member
 *		\brief      Page with statistics on members
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$graphwidth = 700;
$mapratio = 0.5;
$graphheight = round($graphwidth * $mapratio);

$mode = GETPOST('mode') ? GETPOST('mode') : '';


// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$result = restrictedArea($user, 'adherent', '', '', 'cotisation');

$year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array("companies", "members"));


/*
 * View
 */

$memberstatic = new Adherent($db);

llxHeader('', $langs->trans("MembersStatisticsByProperties"), '', '', 0, 0, array('https://www.google.com/jsapi'));

$title = $langs->trans("MembersStatisticsByProperties");

print load_fiche_titre($title, '', $memberstatic->picto);

//dol_mkdir($dir);

$data = array();

$sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions,";
$sql .= " MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate,";
$sql .= " d.morphy as code";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
$sql .= " AND d.statut <> ".Adherent::STATUS_DRAFT;
$sql .= " GROUP BY d.morphy";
$foundphy = $foundmor = 0;

// Define $data array
dol_syslog("Count member", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		if ($obj->code == 'phy') {
			$foundphy++;
		}
		if ($obj->code == 'mor') {
			$foundmor++;
		}

		$data[$obj->code] = array('label'=>$obj->code, 'nb'=>$obj->nb, 'nbsubscriptions'=>$obj->nbsubscriptions, 'lastdate'=>$db->jdate($obj->lastdate), 'lastsubscriptiondate'=>$db->jdate($obj->lastsubscriptiondate));

		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}

$sql = "SELECT COUNT(DISTINCT d.rowid) as nb, COUNT(s.rowid) as nbsubscriptions,";
$sql .= " MAX(d.datevalid) as lastdate, MAX(s.dateadh) as lastsubscriptiondate,";
$sql .= " d.morphy as code";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."subscription as s ON s.fk_adherent = d.rowid";
$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
$sql .= " AND d.statut >= 1"; // Active (not excluded=-2, not draft=-1, not resiliated=0)
$sql .= " GROUP BY d.morphy";
$foundphy = $foundmor = 0;

// Define $data array
dol_syslog("Count member still active", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		if ($obj->code == 'phy') {
			$foundphy++;
		}
		if ($obj->code == 'mor') {
			$foundmor++;
		}

		$data[$obj->code]['nbactive'] = $obj->nb;

		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}


$head = member_stats_prepare_head($memberstatic);

print dol_get_fiche_head($head, 'statsbyproperties', '', -1, '');


// Print title
if (!count($data)) {
	print '<span class="opacitymedium">'.$langs->trans("NoValidatedMemberYet").'</span><br>';
	print '<br>';
} else {
	print '<span class="opacitymedium">'.$langs->trans("MembersByNature").'</span><br>';
	print '<br>';
}

// Print array
print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="liste centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("MemberNature").'</td>';
print '<td class="right">'.$langs->trans("NbOfMembers").' <span class="opacitymedium">('.$langs->trans("AllTime").')</span></td>';
print '<td class="right">'.$langs->trans("NbOfActiveMembers").'</td>';
print '<td class="center">'.$langs->trans("LastMemberDate").'</td>';
print '<td class="right">'.$langs->trans("NbOfSubscriptions").'</td>';
print '<td class="center">'.$langs->trans("LatestSubscriptionDate").'</td>';
print '</tr>';

if (!$foundphy) {
	$data[] = array('label'=>'phy', 'nb'=>'0', 'nbactive'=>'0', 'lastdate'=>'', 'lastsubscriptiondate'=>'');
}
if (!$foundmor) {
	$data[] = array('label'=>'mor', 'nb'=>'0', 'nbactive'=>'0', 'lastdate'=>'', 'lastsubscriptiondate'=>'');
}

foreach ($data as $val) {
	$nb = $val['nb'];
	$nbsubscriptions = isset($val['nbsubscriptions']) ? $val['nbsubscriptions'] : 0;
	$nbactive = $val['nbactive'];

	print '<tr class="oddeven">';
	print '<td>'.$memberstatic->getmorphylib($val['label']).'</td>';
	print '<td class="right">'.$nb.'</td>';
	print '<td class="right">'.$nbactive.'</td>';
	print '<td class="center">'.dol_print_date($val['lastdate'], 'dayhour').'</td>';
	print '<td class="right">'.$nbsubscriptions.'</td>';
	print '<td class="center">'.dol_print_date($val['lastsubscriptiondate'], 'dayhour').'</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
