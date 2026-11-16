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
 *	    \file       htdocs/contact/stats/geo.php
 *      \ingroup    societe
 *		\brief      Page with geographical statistics on contacts
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/contact.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$mode = GETPOST('mode', 'aZ09');
if (empty($mode)) {
	$mode = 'contactbycountry';
}
$tabfamily = GETPOST('tabfamily', 'aZ09');	// 'contactstats' to show the tab with the custom reports tool

// Security check (statistics are on all the contacts, so they are not available to external users)
if ($user->socid > 0) {
	accessforbidden();
}
restrictedArea($user, 'contact', 0, '');

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

$contactstatic = new Contact($db);

$title = (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-contact-stats_geo');

$param = '';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/contact/list.php', ['mode' => 'common']), '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/contact/list.php', ['mode' => 'kanban']), '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('Statistics'), '', 'fa fa-chart-bar imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/contact/stats/geo.php', ['mode' => 'contactbycountry']), '', 2, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewContactAddress'), '', 'fa fa-plus-circle', dolBuildUrl(DOL_URL_ROOT.'/contact/card.php', ['action' => 'create']), '', $user->hasRight('societe', 'contact', 'creer'));

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], $param, '', '', '', 0, $langs->trans("Statistics"), $contactstatic->picto, 0, $newcardbutton, '', 0, 0, 0, 1);

// Show the tab with the custom reports tool (it does not filter on sales representatives, so it needs the permission to see all third parties)
if ($tabfamily == 'contactstats' && $user->hasRight('societe', 'client', 'voir')) {
	$head = contact_stats_prepare_head($contactstatic);

	print dol_get_fiche_head($head, 'customreports', '', -1, '');

	define('USE_CUSTOM_REPORT_AS_TAB', 1);
	$customreportobjecttype = 'contact';
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
$sql = "SELECT COUNT(p.rowid) as nb, SUM(CASE WHEN p.statut = 1 THEN 1 ELSE 0 END) as nbactive, MAX(p.datec) as lastdate,";
$sql .= " co.code, co.label";
if ($mode == 'contactbyregion') {
	$sql .= ", r.nom as label2";
} elseif ($mode == 'contactbystate') {
	$sql .= ", st.nom as label2";
} elseif ($mode == 'contactbytown') {
	$sql .= ", p.town as label2";
}
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON p.fk_pays = co.rowid";
if ($mode == 'contactbyregion' || $mode == 'contactbystate') {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as st ON p.fk_departement = st.rowid";
}
if ($mode == 'contactbyregion') {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_regions as r ON st.fk_region = r.code_region";
}
$sql .= " WHERE p.entity IN (".getEntity('contact').")";
$sql .= " AND (p.priv = 0 OR (p.priv = 1 AND p.fk_user_creat = ".((int) $user->id)."))";	// Exclude the private contacts of other users
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND (p.fk_soc IS NULL OR EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $user->id)."))";
}
$sql .= " GROUP BY co.label, co.code";
if ($mode == 'contactbyregion') {
	$tab = 'statsregion';
	$label2 = $langs->trans("Region");
	$sql .= ", r.nom";
} elseif ($mode == 'contactbystate') {
	$tab = 'statsstate';
	$label2 = $langs->trans("State");
	$sql .= ", st.nom";
} elseif ($mode == 'contactbytown') {
	$tab = 'statstown';
	$label2 = $langs->trans("Town");
	$sql .= ", p.town";
} else {
	$mode = 'contactbycountry';
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


$head = contact_stats_prepare_head($contactstatic);

print dol_get_fiche_head($head, (string) $tab, '', -1, '');


// Print title
if (!count($data)) {
	print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span><br>';
} else {
	print '<div class="info">';
	if ($mode == 'contactbyregion') {
		print $langs->trans("ContactsByRegionDesc");
	} elseif ($mode == 'contactbystate') {
		print $langs->trans("ContactsByStateDesc");
	} elseif ($mode == 'contactbytown') {
		print $langs->trans("ContactsByTownDesc");
	} else {
		print $langs->trans("ContactsByCountryDesc");
	}
	if ($num > $MAXLINES) {
		print '<br>'.$langs->trans("ContactsStatsLimitedTo", $MAXLINES);
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
print '<th class="right">'.$langs->trans("NbOfContacts").' <span class="opacitymedium">('.$langs->trans("AllTime").')</span></th>';
print '<th class="right">'.$langs->trans("NbOfActiveContacts").'</th>';
print '<th class="center">'.$langs->trans("LastContactDate").'</th>';
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
