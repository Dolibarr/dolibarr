<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016      Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2020      Pierre Ardoin     	<mapiolca@me.com>
 * Copyright (C) 2020		Tobias Sekan		<tobias.sekan@startmail.com>
 * Copyright (C) 2021      Gauthier VERDOL     	<gauthier.verdol@atm-consulting.fr>
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
 *	\file		htdocs/compta/sociales/list.php
 *	\ingroup	tax
 *	\brief		Page to list all social contributions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta', 'banks', 'bills', 'hrm', 'projects'));

$action				= GETPOST('action', 'aZ09');
$massaction			= GETPOST('massaction', 'alpha');
$confirm			= GETPOST('confirm', 'alpha');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage		= GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sclist';

$search_ref			= GETPOST('search_ref', 'int');
$search_label = GETPOST('search_label', 'alpha');
$search_amount		= GETPOST('search_amount', 'alpha');
$search_status		= GETPOST('search_status', 'int');
$search_day_lim		= GETPOST('search_day_lim', 'int');
$search_month_lim = GETPOST('search_month_lim', 'int');
$search_year_lim	= GETPOST('search_year_lim', 'int');
$search_project_ref = GETPOST('search_project_ref', 'alpha');
$search_project = GETPOST('search_project', 'alpha');
$search_users = GETPOST('search_users');
$search_type = GETPOST('search_type', 'int');
$search_account				= GETPOST('search_account', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield			= GETPOST("sortfield", 'alpha');
$sortorder			= GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page == -1) {
	$page = 0; // If $page is not defined, or '' or -1
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
	$sortfield = "cs.date_ech";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$year = GETPOST("year", 'int');
$filtre = GETPOST("filtre", 'int');

if (!GETPOSTISSET('search_typeid')) {
	$newfiltre = str_replace('filtre=', '', $filtre);
	$filterarray = explode('-', $newfiltre);
	foreach ($filterarray as $val) {
		$part = explode(':', $val);
		if ($part[0] == 'cs.fk_type') {
			$search_typeid = $part[1];
		}
	}
} else {
	$search_typeid = GETPOST('search_typeid', 'int');
}

$arrayfields = array(
	'cs.rowid'		=>array('label'=>"Ref", 'checked'=>1, 'position'=>10),
	'cs.libelle'	=>array('label'=>"Label", 'checked'=>1, 'position'=>20),
	'cs.fk_type'	=>array('label'=>"Type", 'checked'=>1, 'position'=>30),
	'cs.date_ech'	=>array('label'=>"Date", 'checked'=>1, 'position'=>40),
	'cs.periode'	=>array('label'=>"PeriodEndDate", 'checked'=>1, 'position'=>50),
	'p.ref'			=>array('label'=>"ProjectRef", 'checked'=>1, 'position'=>60, 'enable'=>(!empty($conf->projet->enabled))),
	'cs.fk_user'	=>array('label'=>"Employee", 'checked'=>1, 'position'=>70),
	'cs.fk_mode_reglement'	=>array('checked'=>-1, 'position'=>80, 'label'=>"DefaultPaymentMode"),
	'cs.amount'		=>array('label'=>"Amount", 'checked'=>1, 'position'=>100),
	'cs.paye'		=>array('label'=>"Status", 'checked'=>1, 'position'=>110),
);

if (!empty($conf->banque->enabled)) {
	$arrayfields['cs.fk_account'] = array('checked'=>-1, 'position'=>90, 'label'=>"DefaultBankAccount");
}

$arrayfields = dol_sort_array($arrayfields, 'position');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('sclist'));
$object = new ChargeSociales($db);

// Security check
$socid = GETPOST("socid", 'int');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'chargesociales', 'charges');


/*
 * Actions
 */

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// All tests are required to be compatible with all browsers
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
		$search_ref = '';
		$search_label = '';
		$search_amount = '';
		$search_status = '';
		$search_typeid = '';
		$year = '';
		$search_day_lim = '';
		$search_year_lim = '';
		$search_month_lim = '';
		$search_project_ref = '';
		$search_project = '';
		$search_users = '';
		$search_type = '';
		$search_account = '';
		$search_array_options = array();
	}
}

/*
 *	View
 */

$form = new Form($db);
$formother = new FormOther($db);
$bankstatic = new Account($db);
$formsocialcontrib = new FormSocialContrib($db);
$chargesociale_static = new ChargeSociales($db);
if (!empty($conf->projet->enabled)) {
	$projectstatic = new Project($db);
}

llxHeader('', $langs->trans("SocialContributions"));

$sql = "SELECT cs.rowid, cs.fk_type as type, cs.fk_user,";
$sql .= " cs.amount, cs.date_ech, cs.libelle as label, cs.paye, cs.periode,";
if (!empty($conf->projet->enabled)) {
	$sql .= " p.rowid as project_id, p.ref as project_ref, p.title as project_label,";
}
$sql .= " c.libelle as type_label, cs.fk_account,";
$sql .= " ba.label as blabel, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.iban_prefix as iban, ba.bic, ba.currency_code, ba.clos,";
$sql .= " SUM(pc.amount) as alreadypayed, pay.code as payment_code";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c,";
$sql .= " ".MAIN_DB_PREFIX."chargesociales as cs";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON (cs.fk_account = ba.rowid)";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as pay ON (cs.fk_mode_reglement = pay.id)';
if (!empty($conf->projet->enabled)) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = cs.fk_projet";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON pc.fk_charge = cs.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON (cs.fk_user = u.rowid)";
$sql .= " WHERE cs.fk_type = c.id";
$sql .= " AND cs.entity = ".$conf->entity;
// Search criteria
if ($search_ref) {
	$sql .= " AND cs.ref = '".$db->escape($search_ref)."'";
}
if ($search_label) {
	$sql .= natural_search("cs.libelle", $search_label);
}
if (!empty($conf->projet->enabled)) {
	if ($search_project_ref != '') {
		$sql .= natural_search("p.ref", $search_project_ref);
	}
}
if (!empty($search_users)) {
	$sql .= ' AND cs.fk_user IN('.implode(', ', $search_users).')';
}
if (!empty($search_type) && $search_type > 0) {
	$sql .= ' AND cs.fk_mode_reglement='.$search_type;
}
if (!empty($search_account) && $search_account > 0) {
	$sql .= ' AND cs.fk_account='.$search_account;
}
if ($search_amount) {
	$sql .= natural_search("cs.amount", $search_amount, 1);
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND cs.paye = ".((int) $search_status);
}
$sql .= dolSqlDateFilter("cs.periode", $search_day_lim, $search_month_lim, $search_year_lim);
//$sql.= dolSqlDateFilter("cs.periode", 0, 0, $year);
if ($year > 0) {
	$sql .= " AND (";
	// Si period renseignee on l'utilise comme critere de date, sinon on prend date echeance,
	// ceci afin d'etre compatible avec les cas ou la periode n'etait pas obligatoire
	$sql .= "   (cs.periode IS NOT NULL AND date_format(cs.periode, '%Y') = '".$db->escape($year)."') ";
	$sql .= "OR (cs.periode IS NULL AND date_format(cs.date_ech, '%Y') = '".$db->escape($year)."')";
	$sql .= ")";
}
if ($search_typeid > 0) {
	$sql .= " AND cs.fk_type = ".((int) $search_typeid);
}
$sql .= " GROUP BY cs.rowid, cs.fk_type, cs.fk_user, cs.amount, cs.date_ech, cs.libelle, cs.paye, cs.periode, c.libelle, cs.fk_account, ba.label, ba.ref, ba.number, ba.account_number, ba.iban_prefix, ba.bic, ba.currency_code, ba.clos, pay.code, u.lastname";
if (!empty($conf->projet->enabled)) {
	$sql .= ", p.rowid, p.ref, p.title";
}
$sql .= $db->order($sortfield, $sortorder);

$totalnboflines = 0;
$result = $db->query($sql);
if ($result) {
	$totalnboflines = $db->num_rows($result);
}
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	llxFooter();
	$db->close();
	exit;
}

$num = $db->num_rows($resql);
$i = 0;

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($search_ref) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_label) {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_project_ref >= 0) {
	$param .= "&search_project_ref=".urlencode($search_project_ref);
}
if ($search_amount) {
	$param .= '&search_amount='.urlencode($search_amount);
}
if ($search_typeid) {
	$param .= '&search_typeid='.urlencode($search_typeid);
}
if ($search_users) {
	foreach ($search_users as $id_user) {
		$param .= '&search_users[]='.urlencode($id_user);
	}
}
if ($search_type) {
	$param .= '&search_type='.urlencode($search_type);
}
if ($search_account) {
	$param .= '&search_account='.$search_account;
}
if ($search_status != '' && $search_status != '-1') {
	$param .= '&search_status='.urlencode($search_status);
}
if ($year) {
	$param .= '&year='.urlencode($year);
}

$newcardbutton = '';
if ($user->rights->tax->charges->creer) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('MenuNewSocialContribution'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/sociales/card.php?action=create');
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="search_status" value="'.$search_status.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

$center = '';
if ($year) {
	$center = '<a href="list.php?year='.($year - 1).'">'.img_previous().'</a>';
	$center .= ' '.$langs->trans("Year").' '.$year;
	$center .= ' <a href="list.php?year='.($year + 1).'">'.img_next().'</a>';
}

print_barre_liste($langs->trans("SocialContributions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $totalnboflines, 'bill', 0, $newcardbutton, '', $limit, 0, 0, 1);

if (empty($mysoc->country_id) && empty($mysoc->country_code)) {
	print '<div class="error">';
	$langs->load("errors");
	$countrynotdefined = $langs->trans("ErrorSetACountryFirst");
	print $countrynotdefined;
	print '</div>';

	print '</form>';
	llxFooter();
	$db->close();
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : '').'">'."\n";

print '<tr class="liste_titre_filter">';

// Filters: Line number (placeholder)
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Filter: Ref
if (!empty($arrayfields['cs.rowid']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Filter: Label
if (!empty($arrayfields['cs.rowid']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth100" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}

// Filter: Type
if (!empty($arrayfields['cs.fk_type']['checked'])) {
	print '<td class="liste_titre">';
	$formsocialcontrib->select_type_socialcontrib($search_typeid, 'search_typeid', 1, 0, 0, 'maxwidth150', 1);
	print '</td>';
}

// Filter: Date (placeholder)
if (!empty($arrayfields['cs.date_ech']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Filter: Period end date
if (!empty($arrayfields['cs.periode']['checked'])) {
	print '<td class="liste_titre center">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
		print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day_lim" value="'.dol_escape_htmltag($search_day_lim).'">';
	}
	print '<input class="flat valignmiddle width25" type="text" size="1" maxlength="2" name="search_month_lim" value="'.dol_escape_htmltag($search_month_lim).'">';
	$formother->select_year($search_year_lim ? $search_year_lim : -1, 'search_year_lim', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
	print '</td>';
}

// Filter: Project ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="6" name="search_project_ref" value="'.$search_project_ref.'">';
	print '</td>';
}

if (!empty($arrayfields['cs.fk_user']['checked'])) {
	// Employee
	print '<td class="liste_titre">';
	print $form->select_dolusers($search_users, 'search_users', 1, null, 0, '', '', '0', '0', 0, '', 0, '', '', 0, 0, true);
}

// Filter: Type
if (!empty($arrayfields['cs.fk_mode_reglement']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_types_paiements($search_type, 'search_type', '', 0, 1, 1, 0, 1, 'maxwidth150');
	print '</td>';
}

// Filter: Bank Account
if (!empty($arrayfields['cs.fk_account']['checked'])) {
	print '<td class="liste_titre">';
	$form->select_comptes($search_account, 'search_account', 0, '', 1, '', 0, 'maxwidth150');
	print '</td>';
}

// Filter: Amount
if (!empty($arrayfields['cs.amount']['checked'])) {
	print '<td class="liste_titre right">';
	print '<input class="flat maxwidth75" type="text" name="search_amount" value="'.dol_escape_htmltag($search_amount).'">';
	print '</td>';
}

// Filter: Status
if (!empty($arrayfields['cs.paye']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone right">';
	$liststatus = array('0'=>$langs->trans("Unpaid"), '1'=>$langs->trans("Paid"));
	print $form->selectarray('search_status', $liststatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100', 1);
	print '</td>';
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Filter: Buttons
print '<td class="liste_titre maxwidthsearch">';
print $form->showFilterAndCheckAddButtons(0);
print '</td>';

print '</tr>';

print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
	print_liste_field_titre('#', $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['cs.rowid']['label'], $_SERVER["PHP_SELF"], "cs.rowid", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['cs.libelle']['label'], $_SERVER["PHP_SELF"], "cs.libelle", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.fk_type']['checked'])) {
	print_liste_field_titre($arrayfields['cs.fk_type']['label'], $_SERVER["PHP_SELF"], "cs.fk_type", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.date_ech']['checked'])) {
	print_liste_field_titre($arrayfields['cs.date_ech']['label'], $_SERVER["PHP_SELF"], "cs.date_ech", '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['cs.periode']['checked'])) {
	print_liste_field_titre($arrayfields['cs.periode']['label'], $_SERVER["PHP_SELF"], "cs.periode", '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.fk_user']['checked'])) {
	print_liste_field_titre("Employee", $_SERVER["PHP_SELF"], "u.lastname", "", $param, 'class="left"', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.fk_mode_reglement']['checked'])) {
	print_liste_field_titre($arrayfields['cs.fk_mode_reglement']['label'], $_SERVER["PHP_SELF"], "cs.fk_mode_reglement", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.fk_account']['checked'])) {
	print_liste_field_titre($arrayfields['cs.fk_account']['label'], $_SERVER["PHP_SELF"], "cs.fk_account", '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.amount']['checked'])) {
	print_liste_field_titre($arrayfields['cs.amount']['label'], $_SERVER["PHP_SELF"], "cs.amount", '', $param, 'class="right"', $sortfield, $sortorder);
}
if (!empty($arrayfields['cs.paye']['checked'])) {
	print_liste_field_titre($arrayfields['cs.paye']['label'], $_SERVER["PHP_SELF"], "cs.paye", '', $param, 'class="right"', $sortfield, $sortorder);
}

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
print '</tr>';

$i = 0;
		$totalarray = $TLoadedUsers = array();
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$chargesociale_static->id = $obj->rowid;
	$chargesociale_static->ref = $obj->rowid;
	$chargesociale_static->label = $obj->label;
	$chargesociale_static->type_label = $obj->type_label;
	if (!empty($conf->projet->enabled)) {
		$projectstatic->id = $obj->project_id;
		$projectstatic->ref = $obj->project_ref;
		$projectstatic->title = $obj->project_label;
	}

	print '<tr class="oddeven">';

	// Line number
	if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER_IN_LIST)) {
		print '<td>'.(($offset * $limit) + $i).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Ref
	if (!empty($arrayfields['cs.rowid']['checked'])) {
		print '<td>'.$chargesociale_static->getNomUrl(1, '20').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Label
	if (!empty($arrayfields['cs.libelle']['checked'])) {
		print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->label).'">'.dol_escape_htmltag($obj->label).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Type
	if (!empty($arrayfields['cs.fk_type']['checked'])) {
		print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->type_label).'">'.dol_escape_htmltag($obj->type_label).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date
	if (!empty($arrayfields['cs.date_ech']['checked'])) {
		print '<td class="center">'.dol_print_date($db->jdate($obj->date_ech), 'day').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date end period
	if (!empty($arrayfields['cs.periode']['checked'])) {
		print '<td class="center">';
		if ($obj->periode) {
			print '<a href="list.php?search_year_lim='.dol_print_date($db->jdate($obj->periode), "%Y").'">';
			print dol_print_date($db->jdate($obj->periode), 'day');
			print '</a>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Project ref
	if (!empty($arrayfields['p.ref']['checked'])) {
		print '<td class="nowrap">';
		if ($obj->project_id > 0) {
			print $projectstatic->getNomUrl(1);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['cs.fk_user']['checked'])) {
		// Employee
		print "<td>";
		if (!empty($obj->fk_user)) {
			if (!empty($TLoadedUsers[$obj->fk_user])) {
				$ustatic = $TLoadedUsers[$obj->fk_user];
			} else {
				$ustatic = new User($db);
				$ustatic->fetch($obj->fk_user);
				$TLoadedUsers[$obj->fk_user] = $ustatic;
			}
			print $ustatic->getNomUrl(-1);
		}
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Type
	if (!empty($arrayfields['cs.fk_mode_reglement']['checked'])) {
		print '<td>';
		if (!empty($obj->payment_code)) print $langs->trans("PaymentTypeShort".$obj->payment_code);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Account
	if (!empty($arrayfields['cs.fk_account']['checked'])) {
		print '<td>';
		if ($obj->fk_account > 0) {
			$bankstatic->id = $obj->fk_account;
			$bankstatic->ref = $obj->bref;
			$bankstatic->number = $obj->bnumber;
			$bankstatic->iban = $obj->iban;
			$bankstatic->bic = $obj->bic;
			$bankstatic->currency_code = $langs->trans("Currency".$obj->currency_code);
			$bankstatic->account_number = $obj->account_number;
			$bankstatic->clos = $obj->clos;

			//$accountingjournal->fetch($obj->fk_accountancy_journal);
			//$bankstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);

			$bankstatic->label = $obj->blabel;
			print $bankstatic->getNomUrl(1);
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
	}

	// Amount
	if (!empty($arrayfields['cs.amount']['checked'])) {
		print '<td class="nowrap amount right">'.price($obj->amount).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
		if (!$i) {
			$totalarray['pos'][$totalarray['nbfield']] = 'totalttcfield';
		}
		$totalarray['val']['totalttcfield'] += $obj->amount;
	}

	// Status
	if (!empty($arrayfields['cs.paye']['checked'])) {
		print '<td class="nowrap right">'.$chargesociale_static->LibStatut($obj->paye, 5, $obj->alreadypayed).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Buttons
	print '<td></td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	print '</tr>';
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

print '</table>';
print '</div>';
print '</form>';

// End of page
llxFooter();
$db->close();
