<?php
/* Copyright (C) 2026		Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *	    \file       htdocs/societe/stats/geo.php
 *      \ingroup    societe
 *		\brief      Page with geographical statistics on third parties
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$mode = GETPOST('mode', 'aZ09');
if (empty($mode)) {
	$mode = 'thirdpartybycountry';
}
$tabfamily = GETPOST('tabfamily', 'aZ09');	// 'thirdpartystats' to show the tab with the custom reports tool

// Security check (statistics are on all the third parties, so they are not available to external users)
if ($user->socid > 0) {
	accessforbidden();
}
restrictedArea($user, 'societe', 0, '');

// Load translation files required by the page
$langs->loadLangs(array("companies", "commercial"));

$MAXLINES = 1000;


/*
 * Actions
 */

// None


/*
 * View
 */

$thirdpartystatic = new Societe($db);

$title = $langs->trans("ThirdParties");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-stats_geo');

$param = '';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/societe/list.php', ['mode' => 'common']), '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/societe/list.php', ['mode' => 'kanban']), '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('Statistics'), '', 'fa fa-chart-bar imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/societe/stats/geo.php', ['mode' => 'thirdpartybycountry']), '', 2, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('MenuNewThirdParty'), '', 'fa fa-plus-circle', dolBuildUrl(DOL_URL_ROOT.'/societe/card.php', ['action' => 'create']), '', $user->hasRight('societe', 'creer'));

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], $param, '', '', '', 0, $langs->trans("Statistics"), $thirdpartystatic->picto, 0, $newcardbutton, '', 0, 0, 0, 1);

// Show the tab with the custom reports tool (it does not filter on sales representatives, so it needs the permission to see all third parties)
if ($tabfamily == 'thirdpartystats' && $user->hasRight('societe', 'client', 'voir')) {
	$head = societe_stats_prepare_head($thirdpartystatic);

	print dol_get_fiche_head($head, 'customreports', '', -1, '');

	define('USE_CUSTOM_REPORT_AS_TAB', 1);
	$customreportobjecttype = 'thirdparty';
	include DOL_DOCUMENT_ROOT.'/core/customreports.php';

	print dol_get_fiche_end();

	llxFooter();
	$db->close();
	exit;
}

$data = array();
$tab = null;
$label = $langs->trans("Country");
$label2 = null;

// Define sql
$sql = "SELECT COUNT(s.rowid) as nb, SUM(CASE WHEN s.status = 1 THEN 1 ELSE 0 END) as nbactive, MAX(s.datec) as lastdate,";
$sql .= " co.code, co.label";
if ($mode == 'thirdpartybyregion') {
	$sql .= ", r.nom as label2";
} elseif ($mode == 'thirdpartybystate') {
	$sql .= ", st.nom as label2";
} elseif ($mode == 'thirdpartybytown') {
	$sql .= ", s.town as label2";
}
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON s.fk_pays = co.rowid";
if ($mode == 'thirdpartybyregion' || $mode == 'thirdpartybystate') {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as st ON s.fk_departement = st.rowid";
}
if ($mode == 'thirdpartybyregion') {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r ON st.fk_region = r.code_region";
}
$sql .= " WHERE s.entity IN (".getEntity('societe').")";
if (!$user->hasRight('fournisseur', 'lire')) {
	$sql .= " AND (s.fournisseur <> 1 OR s.client <> 0)";	// Same rule than the list of third parties
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = s.rowid AND sc.fk_user = ".((int) $user->id).")";
}
$sql .= " GROUP BY co.label, co.code";
if ($mode == 'thirdpartybyregion') {
	$tab = 'statsregion';
	$label2 = $langs->trans("Region");
	$sql .= ", r.nom";
} elseif ($mode == 'thirdpartybystate') {
	$tab = 'statsstate';
	$label2 = $langs->trans("State");
	$sql .= ", st.nom";
} elseif ($mode == 'thirdpartybytown') {
	$tab = 'statstown';
	$label2 = $langs->trans("Town");
	$sql .= ", s.town";
} else {
	$mode = 'thirdpartybycountry';
	$tab = 'statscountry';
}
$sql .= " ORDER BY nb DESC";
$sql .= $db->plimit($MAXLINES + 1);

// Define $data array
$num = 0;
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < min($num, $MAXLINES)) {
		$obj = $db->fetch_object($resql);

		$labelcountry = '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>';
		if ($obj->code && $langs->trans("Country".$obj->code) != "Country".$obj->code) {
			$labelcountry = picto_from_langcode($obj->code, 'class="saturatemedium paddingrightonly"', 1).' '.$langs->trans("Country".$obj->code);
		} elseif ($obj->label) {
			$labelcountry = dolPrintLabel($obj->label);
		}

		$data[] = array(
			'label' => $labelcountry,
			'label2' => (isset($obj->label2) && $obj->label2 != '' ? dolPrintLabel($obj->label2) : '<span class="opacitymedium">'.$langs->trans("Unknown").'</span>'),
			'nb' => (int) $obj->nb,
			'nbactive' => (int) $obj->nbactive,
			'lastdate' => $db->jdate($obj->lastdate)
		);

		$i++;
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}


$head = societe_stats_prepare_head($thirdpartystatic);

print dol_get_fiche_head($head, (string) $tab, '', -1, '');


// Print title
if (!count($data)) {
	print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span><br>';
} else {
	print '<div class="info">';
	if ($mode == 'thirdpartybyregion') {
		print $langs->trans("ThirdPartiesByRegionDesc");
	} elseif ($mode == 'thirdpartybystate') {
		print $langs->trans("ThirdPartiesByStateDesc");
	} elseif ($mode == 'thirdpartybytown') {
		print $langs->trans("ThirdPartiesByTownDesc");
	} else {
		print $langs->trans("ThirdPartiesByCountryDesc");
	}
	if ($num > $MAXLINES) {
		print '<br>'.$langs->trans("ThirdPartiesStatsLimitedTo", $MAXLINES);
	}
	print '</div>';
}
print '<br>';

// Print array
print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="liste centpercent noborder">';
print '<tr class="liste_titre">';
print '<th>'.$label.'</th>';
if (isset($label2)) {
	print '<th class="center">'.$label2.'</th>';
}
print '<th class="right">'.$langs->trans("NbOfThirdParties").' <span class="opacitymedium">('.$langs->trans("AllTime").')</span></th>';
print '<th class="right">'.$langs->trans("NbOfActiveThirdParties").'</th>';
print '<th class="center">'.$langs->trans("LastThirdPartyDate").'</th>';
print '</tr>';

foreach ($data as $val) {
	print '<tr class="oddeven">';
	print '<td>'.$val['label'].'</td>';
	if (isset($label2)) {
		print '<td class="center">'.$val['label2'].'</td>';
	}
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right">'.$val['nbactive'].'</td>';
	print '<td class="center">'.dol_print_date($val['lastdate'], 'dayhour', 'tzuserrel').'</td>';
	print '</tr>';
}

print '</table>';
print '</div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
