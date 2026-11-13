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
 *	    \file       htdocs/contact/stats/byproperties.php
 *      \ingroup    societe
 *		\brief      Page with statistics on contacts by nature of their third party (prospect, customer, vendor)
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

// Security check (statistics are on all the contacts, so they are not available to external users)
if ($user->socid > 0) {
	accessforbidden();
}
restrictedArea($user, 'contact', 0, '');

// Load translation files required by the page
$langs->loadLangs(array("companies", "commercial"));


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

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-societe page-contact-stats_byproperties');

$param = '';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/contact/list.php', ['mode' => 'common']), '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/contact/list.php', ['mode' => 'kanban']), '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('Statistics'), '', 'fa fa-chart-bar imgforviewmode', dolBuildUrl(DOL_URL_ROOT.'/contact/stats/geo.php', ['mode' => 'contactbycountry']), '', 2, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewContactAddress'), '', 'fa fa-plus-circle', dolBuildUrl(DOL_URL_ROOT.'/contact/card.php', ['action' => 'create']), '', $user->hasRight('societe', 'contact', 'creer'));

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], $param, '', '', '', 0, $langs->trans("Statistics"), $contactstatic->picto, 0, $newcardbutton, '', 0, 0, 0, 1);

// List of natures with their SQL condition, same as the filters of the list of contacts.
// A contact can be counted on several lines (its third party can be prospect and/or customer and/or vendor).
$arrayofnatures = array();
if (!getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
	$arrayofnatures['prospect'] = array('label' => 'ThirdPartyProspects', 'sql' => 's.client IN (2, 3)');
}
if (!getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
	$arrayofnatures['customer'] = array('label' => 'ThirdPartyCustomers', 'sql' => 's.client IN (1, 3)');
}
if (isModEnabled('fournisseur') && $user->hasRight('fournisseur', 'lire')) {
	$arrayofnatures['supplier'] = array('label' => 'ThirdPartySuppliers', 'sql' => 's.fournisseur = 1');
}
$arrayofnatures['none'] = array('label' => 'NorProspectNorCustomer', 'sql' => 'p.fk_soc IS NOT NULL AND s.client = 0');
$arrayofnatures['nothirdparty'] = array('label' => 'OthersNotLinkedToThirdParty', 'sql' => 'p.fk_soc IS NULL');

// Count all natures with one request
$sql = "SELECT";
$i = 0;
foreach ($arrayofnatures as $key => $nature) {
	$sql .= ($i ? "," : "")." SUM(CASE WHEN ".$nature['sql']." THEN 1 ELSE 0 END) as nb_".$key;
	$sql .= ", SUM(CASE WHEN ".$nature['sql']." AND p.statut = 1 THEN 1 ELSE 0 END) as nbactive_".$key;
	$sql .= ", MAX(CASE WHEN ".$nature['sql']." THEN p.datec ELSE NULL END) as lastdate_".$key;
	$i++;
}
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql .= " WHERE p.entity IN (".getEntity('contact').")";
$sql .= " AND (p.priv = 0 OR (p.priv = 1 AND p.fk_user_creat = ".((int) $user->id)."))";	// Exclude the private contacts of other users
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND (p.fk_soc IS NULL OR EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $user->id)."))";
}

// Define $data array
$data = array();
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	if ($obj) {
		foreach ($arrayofnatures as $key => $nature) {
			$data[$key] = array(
				'label' => $langs->trans($nature['label']),
				'nb' => (int) $obj->{'nb_'.$key},
				'nbactive' => (int) $obj->{'nbactive_'.$key},
				'lastdate' => $db->jdate($obj->{'lastdate_'.$key})
			);
		}
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}


$head = contact_stats_prepare_head($contactstatic);

print dol_get_fiche_head($head, 'statsbyproperties', '', -1, '');


// Print title
print '<div class="info">'.$langs->trans("ContactsByNatureDesc").'</div>';
print '<br>';

// Print array
print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="liste centpercent noborder">';
print '<tr class="liste_titre">';
print '<th>'.$langs->trans("NatureOfContact").'</th>';
print '<th class="right">'.$langs->trans("NbOfContacts").' <span class="opacitymedium">('.$langs->trans("AllTime").')</span></th>';
print '<th class="right">'.$langs->trans("NbOfActiveContacts").'</th>';
print '<th class="center">'.$langs->trans("LastContactDate").'</th>';
print '</tr>';

foreach ($data as $val) {
	print '<tr class="oddeven">';
	print '<td>'.$val['label'].'</td>';
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
