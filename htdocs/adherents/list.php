<?php
/* Copyright (C) 2001-2003	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2022	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015	Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2016	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2018-2024	Alexandre Spangaro			<aspangaro@open-dsi.fr>
 * Copyright (C) 2021-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Benjamin Falière			<benjamin.faliere@altairis.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *  \file       htdocs/adherents/list.php
 *  \ingroup    member
 *  \brief      Page to list all members of foundation
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


// Load translation files required by the page
$langs->loadLangs(array("members", "companies", "categories"));


// Get parameters
$action 	= GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm 	= GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'alpha');
$toselect 	= GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'memberslist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$optioncss 	= GETPOST('optioncss', 'aZ');
$mode 		= GETPOST('mode', 'alpha');
$groupby = GETPOST('groupby', 'aZ09');	// Example: $groupby = 'p.fk_opp_status' or $groupby = 'p.fk_statut'

// Search fields
$search 			= GETPOST("search", 'alpha');
$search_id = GETPOST('search_id', 'int');
$search_ref 		= GETPOST("search_ref", 'alpha');
$search_lastname 	= GETPOST("search_lastname", 'alpha');
$search_firstname 	= GETPOST("search_firstname", 'alpha');
$search_gender 		= GETPOST("search_gender", 'alpha');
$search_civility 	= GETPOST("search_civility", 'alpha');
$search_company 	= GETPOST('search_company', 'alphanohtml');
$search_login 		= GETPOST("search_login", 'alpha');
$search_address 	= GETPOST("search_address", 'alpha');
$search_zip 		= GETPOST("search_zip", 'alpha');
$search_town 		= GETPOST("search_town", 'alpha');
$search_state 		= GETPOST("search_state", 'alpha');  // county / departement / federal state
$search_country 	= GETPOST("search_country", 'alpha');
$search_phone 		= GETPOST("search_phone", 'alpha');
$search_phone_perso = GETPOST("search_phone_perso", 'alpha');
$search_phone_mobile = GETPOST("search_phone_mobile", 'alpha');
$search_type 		= GETPOST("search_type", 'alpha');
$search_email 		= GETPOST("search_email", 'alpha');
$search_categ 		= GETPOST("search_categ", 'intcomma');
$search_morphy 		= GETPOST("search_morphy", 'alpha');
$search_import_key  = trim(GETPOST("search_import_key", 'alpha'));

$socid 		= GETPOSTINT('socid');
if (GETPOSTINT('catid') && empty($search_categ)) {
	$search_categ = GETPOSTINT('catid');
}

$search_filter 		= GETPOST("search_filter", 'alpha');
$search_status 		= GETPOST("search_status", 'intcomma');  // status
$search_datec_start = dol_mktime(0, 0, 0, GETPOSTINT('search_datec_start_month'), GETPOSTINT('search_datec_start_day'), GETPOSTINT('search_datec_start_year'));
$search_datec_end = dol_mktime(23, 59, 59, GETPOSTINT('search_datec_end_month'), GETPOSTINT('search_datec_end_day'), GETPOSTINT('search_datec_end_year'));
$search_datem_start = dol_mktime(0, 0, 0, GETPOSTINT('search_datem_start_month'), GETPOSTINT('search_datem_start_day'), GETPOSTINT('search_datem_start_year'));
$search_datem_end = dol_mktime(23, 59, 59, GETPOSTINT('search_datem_end_month'), GETPOSTINT('search_datem_end_day'), GETPOSTINT('search_datem_end_year'));

$filter = GETPOST("filter", 'alpha');
if ($filter) {
	$search_filter = $filter; // For backward compatibility
}

$statut = GETPOST("statut", 'alpha');
if ($statut != '') {
	$search_status = $statut; // For backward compatibility
}

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

if ($search_status < -2) {
	$search_status = '';
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = ($filter == 'outofdate' ? "DESC" : "ASC");
}
if (!$sortfield) {
	$sortfield = ($filter == 'outofdate' ? "d.datefin" : "d.lastname");
}

$object = new Adherent($db);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('memberlist'));
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'd.ref' => 'Ref',
	'd.login' => 'Login',
	'd.lastname' => 'Lastname',
	'd.firstname' => 'Firstname',
	'd.societe' => "Company",
	'd.email' => 'EMail',
	'd.address' => 'Address',
	'd.zip' => 'Zip',
	'd.town' => 'Town',
	'd.phone' => "Phone",
	'd.phone_perso' => "PhonePerso",
	'd.phone_mobile' => "PhoneMobile",
	'd.note_public' => 'NotePublic',
	'd.note_private' => 'NotePrivate',
);

$arrayfields = array(
	'd.rowid' => array('label' => 'ID', 'checked' => 1, 'enabled' => getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID'), 'position' => 1),
	'd.ref' => array('label' => "Ref", 'checked' => 1),
	'd.civility' => array('label' => "Civility", 'checked' => 0),
	'd.lastname' => array('label' => "Lastname", 'checked' => 1),
	'd.firstname' => array('label' => "Firstname", 'checked' => 1),
	'd.gender' => array('label' => "Gender", 'checked' => 0),
	'd.company' => array('label' => "Company", 'checked' => 1, 'position' => 70),
	'd.login' => array('label' => "Login", 'checked' => 1),
	'd.morphy' => array('label' => "MemberNature", 'checked' => 1),
	't.libelle' => array('label' => "Type", 'checked' => 1, 'position' => 55),
	'd.address' => array('label' => "Address", 'checked' => 0),
	'd.zip' => array('label' => "Zip", 'checked' => 0),
	'd.town' => array('label' => "Town", 'checked' => 0),
	'd.phone' => array('label' => "Phone", 'checked' => 0),
	'd.phone_perso' => array('label' => "PhonePerso", 'checked' => 0),
	'd.phone_mobile' => array('label' => "PhoneMobile", 'checked' => 0),
	'd.email' => array('label' => "Email", 'checked' => 1),
	'state.nom' => array('label' => "State", 'checked' => 0, 'position' => 90),
	'country.code_iso' => array('label' => "Country", 'checked' => 0, 'position' => 95),
	/*'d.note_public'=>array('label'=>"NotePublic", 'checked'=>0),
	'd.note_private'=>array('label'=>"NotePrivate", 'checked'=>0),*/
	'd.datefin' => array('label' => "EndSubscription"),
	'd.datec' => array('label' => "DateCreation"),
	'd.birth' => array('label' => "Birthday"),
	'd.tms' => array('label' => "DateModificationShort"),
	'd.statut' => array('label' => "Status"),
	'd.import_key' => array('label' => "ImportId"),
);

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');

// Complete array of fields for columns
$tableprefix = 'd';
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval((string) $val['visible'], 1);
		$arrayfields[$tableprefix.'.'.$key] = array(
			'label' => $val['label'],
			'checked' => (($visible < 0) ? 0 : 1),
			'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
			'position' => $val['position'],
			'help' => isset($val['help']) ? $val['help'] : ''
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

// Security check
$result = restrictedArea($user, 'adherent');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$permissiontoread = 0;
$permissiontodelete = 0;
$permissiontoadd = 0;

$parameters = array('socid' => isset($socid) ? $socid : null, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$statut = '';
		$filter = '';

		$search = "";
		$search_id = '';
		$search_ref = "";
		$search_lastname = "";
		$search_firstname = "";
		$search_gender = "";
		$search_civility = "";
		$search_login = "";
		$search_company = "";
		$search_type = "";
		$search_email = "";
		$search_address = "";
		$search_zip = "";
		$search_town = "";
		$search_state = "";
		$search_country = '';
		$search_phone = '';
		$search_phone_perso = '';
		$search_phone_mobile = '';
		$search_morphy = "";
		$search_categ = "";
		$search_filter = "";
		$search_status = "";
		$search_import_key = '';
		$catid = "";
		$search_all = "";
		$toselect = array();
		$search_datec_start = '';
		$search_datec_end = '';
		$search_datem_start = '';
		$search_datem_end = '';
		$search_array_options = array();
	}

	// Close
	if ($massaction == 'close' && $user->hasRight('adherent', 'creer')) {
		$tmpmember = new Adherent($db);
		$error = 0;
		$nbclose = 0;

		$db->begin();

		foreach ($toselect as $idtoclose) {
			$tmpmember->fetch($idtoclose);
			$result = $tmpmember->resiliate($user);

			if ($result < 0 && !count($tmpmember->errors)) {
				setEventMessages($tmpmember->error, $tmpmember->errors, 'errors');
			} else {
				if ($result > 0) {
					$nbclose++;
				}
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("XMembersClosed", $nbclose), null, 'mesgs');

			$db->commit();
		} else {
			$db->rollback();
		}
	}

	// Create external user
	if ($massaction == 'createexternaluser' && $user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer')) {
		$tmpmember = new Adherent($db);
		$error = 0;
		$nbcreated = 0;

		$db->begin();

		foreach ($toselect as $idtoclose) {
			$tmpmember->fetch($idtoclose);

			if (!empty($tmpmember->socid)) {
				$nuser = new User($db);
				$tmpuser = dol_clone($tmpmember, 2);

				$result = $nuser->create_from_member($tmpuser, $tmpmember->login);

				if ($result < 0 && !count($tmpmember->errors)) {
					setEventMessages($tmpmember->error, $tmpmember->errors, 'errors');
				} else {
					if ($result > 0) {
						$nbcreated++;
					}
				}
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("XExternalUserCreated", $nbcreated), null, 'mesgs');

			$db->commit();
		} else {
			$db->rollback();
		}
	}

	// Create external user
	if ($action == 'createsubscription_confirm' && $confirm == "yes" && $user->hasRight('adherent', 'creer')) {
		$tmpmember = new Adherent($db);
		$adht = new AdherentType($db);
		$error = 0;
		$nbcreated = 0;
		$now = dol_now();
		$amount = price2num(GETPOST('amount', 'alpha'));
		$db->begin();
		foreach ($toselect as $id) {
			$res = $tmpmember->fetch($id);
			if ($res > 0) {
				$result = $tmpmember->subscription($now, $amount);
				if ($result < 0) {
					$error++;
				} else {
					$nbcreated++;
				}
			} else {
				$error++;
			}
		}

		if (!$error) {
			setEventMessages($langs->trans("XSubsriptionCreated", $nbcreated), null, 'mesgs');
			$db->commit();
		} else {
			setEventMessages($langs->trans("XSubsriptionError", $error), null, 'mesgs');
			$db->rollback();
		}
	}

	// Mass actions
	$objectclass = 'Adherent';
	$objectlabel = 'Members';
	$permissiontoread = $user->hasRight('adherent', 'lire');
	$permissiontodelete = $user->hasRight('adherent', 'supprimer');
	$permissiontoadd = $user->hasRight('adherent', 'creer');
	$uploaddir = $conf->adherent->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$membertypestatic = new AdherentType($db);
$memberstatic = new Adherent($db);

$now = dol_now();

// Page Header
$title = $langs->trans("Members")." - ".$langs->trans("List");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';
$morejs = array();
$morecss = array();


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT";
$sql .= " d.rowid, d.ref, d.login, d.lastname, d.firstname, d.gender, d.societe as company, d.fk_soc,";
$sql .= " d.civility, d.datefin, d.address, d.zip, d.town, d.state_id, d.country,";
$sql .= " d.email, d.phone, d.phone_perso, d.phone_mobile, d.birth, d.public, d.photo,";
$sql .= " d.fk_adherent_type as type_id, d.morphy, d.statut as status, d.datec as date_creation, d.tms as date_modification,";
$sql .= " d.note_private, d.note_public, d.import_key,";
$sql .= " s.nom,";
$sql .= " ".$db->ifsql("d.societe IS NULL", "s.nom", "d.societe")." as companyname,";
$sql .= " t.libelle as type, t.subscription,";
$sql .= " state.code_departement as state_code, state.nom as state_name";

// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : "");
	}
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

// SQL Alias adherent
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
if (!empty($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (d.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = d.country)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = d.state_id)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on (s.rowid = d.fk_soc)";

// SQL Alias adherent_type
$sql .= ", ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid";

$searchCategoryContactList = $search_categ ? array($search_categ) : array();
$searchCategoryContactOperator = 0;
// Search for tag/category ($searchCategoryContactList is an array of ID)
if (!empty($searchCategoryContactList)) {
	$searchCategoryContactSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryContactList as $searchCategoryContact) {
		if (intval($searchCategoryContact) == -2) {
			$searchCategoryContactSqlList[] = "NOT EXISTS (SELECT ck.fk_categorie FROM ".MAIN_DB_PREFIX."categorie_member as ck WHERE d.rowid = ck.fk_member)";
		} elseif (intval($searchCategoryContact) > 0) {
			if ($searchCategoryContactOperator == 0) {
				$searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_categorie FROM ".MAIN_DB_PREFIX."categorie_member as ck WHERE d.rowid = ck.fk_member AND ck.fk_categorie = ".((int) $searchCategoryContact).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryContact);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_categorie FROM ".MAIN_DB_PREFIX."categorie_member as ck WHERE d.rowid = ck.fk_member AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryContactOperator == 1) {
		if (!empty($searchCategoryContactSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryContactSqlList).")";
		}
	} else {
		if (!empty($searchCategoryContactSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryContactSqlList).")";
		}
	}
}

$sql .= " AND d.entity IN (".getEntity('adherent').")";
if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($search_type > 0) {
	$sql .= " AND t.rowid=".((int) $search_type);
}
if ($search_filter == 'withoutsubscription') {
	$sql .= " AND (datefin IS NULL)";
}
if ($search_filter == 'waitingsubscription') {
	$sql .= " AND (datefin IS NULL AND t.subscription = '1')";
}
if ($search_filter == 'uptodate') {
	//$sql .= " AND (datefin >= '".$db->idate($now)."')";
	// Up to date subscription OR no subscription required
	$sql .= " AND (datefin >= '".$db->idate($now)."' OR (datefin IS NULL AND t.subscription = '0'))";
}
if ($search_filter == 'outofdate') {
	$sql .= " AND (datefin < '".$db->idate($now)."')";
}
if ($search_status != '') {
	// Peut valoir un nombre ou liste de nombre separates par virgules
	$sql .= " AND d.statut in (".$db->sanitize($db->escape($search_status)).")";
}
if ($search_morphy != '' && $search_morphy != '-1') {
	$sql .= natural_search("d.morphy", $search_morphy);
}
if ($search_id) {
	$sql .= natural_search("d.rowid", $search_id);
}
if ($search_ref) {
	$sql .= natural_search("d.ref", $search_ref);
}
if ($search_civility) {
	$sql .= natural_search("d.civility", $search_civility);
}
if ($search_firstname) {
	$sql .= natural_search("d.firstname", $search_firstname);
}
if ($search_lastname) {
	$sql .= natural_search(array("d.firstname", "d.lastname", "d.societe"), $search_lastname);
}
if ($search_gender != '' && $search_gender != '-1') {
	$sql .= natural_search("d.gender", $search_gender);
}
if ($search_login) {
	$sql .= natural_search("d.login", $search_login);
}
if ($search_company) {
	$sql .= natural_search("s.nom", $search_company);
}
if ($search_email) {
	$sql .= natural_search("d.email", $search_email);
}
if ($search_address) {
	$sql .= natural_search("d.address", $search_address);
}
if ($search_town) {
	$sql .= natural_search("d.town", $search_town);
}
if ($search_zip) {
	$sql .= natural_search("d.zip", $search_zip);
}
if ($search_state) {
	$sql .= natural_search("state.nom", $search_state);
}
if ($search_phone) {
	$sql .= natural_search("d.phone", $search_phone);
}
if ($search_phone_perso) {
	$sql .= natural_search("d.phone_perso", $search_phone_perso);
}
if ($search_phone_mobile) {
	$sql .= natural_search("d.phone_mobile", $search_phone_mobile);
}
if ($search_country) {
	$sql .= " AND d.country IN (".$db->sanitize($search_country).')';
}
if ($search_import_key) {
	$sql .= natural_search("d.import_key", $search_import_key);
}
if ($search_datec_start) {
	$sql .= " AND d.datec >= '".$db->idate($search_datec_start)."'";
}
if ($search_datec_end) {
	$sql .= " AND d.datec <= '".$db->idate($search_datec_end)."'";
}
if ($search_datem_start) {
	$sql .= " AND d.tms >= '".$db->idate($search_datem_start)."'";
}
if ($search_datem_end) {
	$sql .= " AND d.tms <= '".$db->idate($search_datem_end)."'";
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}
//print $sql;

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);


// Direct jump if only one record found
if ($num == 1 && getDolGlobalInt('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/adherents/card.php?id='.$id);
	exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-member page-list bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

$arrayofselected = is_array($toselect) ? $toselect : array();


if ($search_type > 0) {
	$membertype = new AdherentType($db);
	$result = $membertype->fetch($search_type);
	$title .= " (".$membertype->label.")";
}

// $parameters
$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($groupby != '') {
	$param .= '&groupby='.urlencode($groupby);
}
if ($search_all != "") {
	$param .= "&search_all=".urlencode($search_all);
}
if ($search_ref) {
	$param .= "&search_ref=".urlencode($search_ref);
}
if ($search_civility) {
	$param .= "&search_civility=".urlencode($search_civility);
}
if ($search_firstname) {
	$param .= "&search_firstname=".urlencode($search_firstname);
}
if ($search_lastname) {
	$param .= "&search_lastname=".urlencode($search_lastname);
}
if ($search_gender) {
	$param .= "&search_gender=".urlencode($search_gender);
}
if ($search_login) {
	$param .= "&search_login=".urlencode($search_login);
}
if ($search_email) {
	$param .= "&search_email=".urlencode($search_email);
}
if ($search_categ > 0 || $search_categ == -2) {
	$param .= "&search_categ=".urlencode((string) ($search_categ));
}
if ($search_company) {
	$param .= "&search_company=".urlencode($search_company);
}
if ($search_address != '') {
	$param .= "&search_address=".urlencode($search_address);
}
if ($search_town != '') {
	$param .= "&search_town=".urlencode($search_town);
}
if ($search_zip != '') {
	$param .= "&search_zip=".urlencode($search_zip);
}
if ($search_state != '') {
	$param .= "&search_state=".urlencode($search_state);
}
if ($search_country != '') {
	$param .= "&search_country=".urlencode($search_country);
}
if ($search_phone != '') {
	$param .= "&search_phone=".urlencode($search_phone);
}
if ($search_phone_perso != '') {
	$param .= "&search_phone_perso=".urlencode($search_phone_perso);
}
if ($search_phone_mobile != '') {
	$param .= "&search_phone_mobile=".urlencode($search_phone_mobile);
}
if ($search_filter && $search_filter != '-1') {
	$param .= "&search_filter=".urlencode($search_filter);
}
if ($search_status != "" && $search_status != -3) {
	$param .= "&search_status=".urlencode($search_status);
}
if ($search_import_key != '') {
	$param .= '&search_import_key='.urlencode($search_import_key);
}
if ($search_type > 0) {
	$param .= "&search_type=".urlencode($search_type);
}
if ($search_datec_start) {
	$param .= '&search_datec_start_day='.dol_print_date($search_datec_start, '%d').'&search_datec_start_month='.dol_print_date($search_datec_start, '%m').'&search_datec_start_year='.dol_print_date($search_datec_start, '%Y');
}
if ($search_datem_end) {
	$param .= '&search_datem_end_day='.dol_print_date($search_datem_end, '%d').'&search_datem_end_month='.dol_print_date($search_datem_end, '%m').'&search_datem_end_year='.dol_print_date($search_datem_end, '%Y');
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
if ($user->hasRight('adherent', 'creer')) {
	$arrayofmassactions['close'] = img_picto('', 'close_title', 'class="pictofixedwidth"').$langs->trans("Resiliate");
}
if ($user->hasRight('adherent', 'supprimer')) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (isModEnabled('category') && $user->hasRight('adherent', 'creer')) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if ($user->hasRight('adherent', 'creer') && $user->hasRight('user', 'user', 'creer')) {
	$arrayofmassactions['createexternaluser'] = img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("CreateExternalUser");
}
if ($user->hasRight('adherent', 'creer')) {
	$arrayofmassactions['createsubscription'] = img_picto('', 'payment', 'class="pictofixedwidth"').$langs->trans("CreateSubscription");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete', 'preaffecttag'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewMember'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/adherents/card.php?action=create', '', $user->hasRight('adherent', 'creer'));

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "Information";
$modelmail = "member";
$objecttmp = new Adherent($db);
$trackid = 'mem'.$object->id;
if ($massaction == 'createsubscription') {
	$tmpmember = new Adherent($db);
	$adht = new AdherentType($db);
	$amount = 0;
	foreach ($toselect as $id) {
		$now = dol_now();
		$tmpmember->fetch($id);
		$res = $adht->fetch($tmpmember->typeid);
		if ($res > 0) {
			$amounttmp = $adht->amount;
			if (!empty($tmpmember->last_subscription_amount) && !GETPOSTISSET('newamount') && is_numeric($amounttmp)) {
				$amounttmp = max($tmpmember->last_subscription_amount, $amount);
			}
			$amount = max(0, $amounttmp, $amount);
		} else {
			$error++;
		}
	}

	$date = dol_print_date(dol_now(), "%d/%m/%Y");
	$formquestion = array(
		array('label' => $langs->trans("DateSubscription"), 'type' => 'other', 'value' => $date),
		array('label' => $langs->trans("Amount"), 'type' => 'text', 'value' => price($amount, 0, '', 0), 'name' => 'amount'),
		array('type' => 'separator'),
		array('label' => $langs->trans("MoreActions"), 'type' => 'other', 'value' => $langs->trans("None").' '.img_warning($langs->trans("WarningNoComplementaryActionDone"))),
	);
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ConfirmMassSubsriptionCreation"), $langs->trans("ConfirmMassSubsriptionCreationQuestion", count($toselect)), "createsubscription_confirm", $formquestion, '', 0, 200, 500, 1);
}
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if MYOBJECT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>'."\n";
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$moreforfilter = '';
// Filter on categories
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedwidth"').$formother->select_categories(Categorie::TYPE_MEMBER, $search_categ, 'search_categ', 1, $langs->trans("MembersCategoriesShort"));
	$moreforfilter .= '</div>';
}
$parameters = array(
	'arrayfields' => &$arrayfields,
);
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}
if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

// Line numbering
if (!empty($arrayfields['d.rowid']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" size="6" type="text" name="search_id" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}

// Ref
if (!empty($arrayfields['d.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth75imp" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}

// Civility
if (!empty($arrayfields['d.civility']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50imp" type="text" name="search_civility" value="'.dol_escape_htmltag($search_civility).'"></td>';
}

// First Name
if (!empty($arrayfields['d.firstname']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'"></td>';
}

// Last Name
if (!empty($arrayfields['d.lastname']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'"></td>';
}

// Gender
if (!empty($arrayfields['d.gender']['checked'])) {
	print '<td class="liste_titre">';
	$arraygender = array('man' => $langs->trans("Genderman"), 'woman' => $langs->trans("Genderwoman"), 'other' => $langs->trans("Genderother"));
	print $form->selectarray('search_gender', $arraygender, $search_gender, 1);
	print '</td>';
}

// Company
if (!empty($arrayfields['d.company']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'"></td>';
}

// Login
if (!empty($arrayfields['d.login']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'"></td>';
}

// Nature
if (!empty($arrayfields['d.morphy']['checked'])) {
	print '<td class="liste_titre center">';
	$arraymorphy = array('mor' => $langs->trans("Moral"), 'phy' => $langs->trans("Physical"));
	print $form->selectarray('search_morphy', $arraymorphy, $search_morphy, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
	print '</td>';
}

// Member Type
if (!empty($arrayfields['t.libelle']['checked'])) {
	print '</td>';
}
if (!empty($arrayfields['t.libelle']['checked'])) {
	print '<td class="liste_titre">';
	$listetype = $membertypestatic->liste_array();
	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print $form->selectarray("search_type", $listetype, $search_type, 1, 0, 0, '', 0, 32);
	print '</td>';
}

// Address - Street
if (!empty($arrayfields['d.address']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_address" value="'.dol_escape_htmltag($search_address).'"></td>';
}

// ZIP
if (!empty($arrayfields['d.zip']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
}

// Town/City
if (!empty($arrayfields['d.town']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
}

// State / County / Departement
if (!empty($arrayfields['state.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring maxwidth75imp" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
	print '</td>';
}

// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}

// Phone pro
if (!empty($arrayfields['d.phone']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_phone" value="'.dol_escape_htmltag($search_phone).'"></td>';
}

// Phone perso
if (!empty($arrayfields['d.phone_perso']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_phone_perso" value="'.dol_escape_htmltag($search_phone_perso).'"></td>';
}

// Phone mobile
if (!empty($arrayfields['d.phone_mobile']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_phone_mobile" value="'.dol_escape_htmltag($search_phone_mobile).'"></td>';
}

// Email
if (!empty($arrayfields['d.email']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'"></td>';
}

// End of subscription date
if (!empty($arrayfields['d.datefin']['checked'])) {
	print '<td class="liste_titre center">';
	//$selectarray = array('-1'=>'', 'withoutsubscription'=>$langs->trans("WithoutSubscription"), 'uptodate'=>$langs->trans("UpToDate"), 'outofdate'=>$langs->trans("OutOfDate"));
	$selectarray = array('-1' => '', 'waitingsubscription' => $langs->trans("WaitingSubscription"), 'uptodate' => $langs->trans("UpToDate"), 'outofdate' => $langs->trans("OutOfDate"));
	print $form->selectarray('search_filter', $selectarray, $search_filter);
	print '</td>';
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

// Date creation
if (!empty($arrayfields['d.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datec_start ? $search_datec_start : -1, 'search_datec_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datec_end ? $search_datec_end : -1, 'search_datec_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Birthday
if (!empty($arrayfields['d.birth']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}

// Date modification
if (!empty($arrayfields['d.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datem_start ? $search_datem_start : -1, 'search_datem_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datem_end ? $search_datem_end : -1, 'search_datem_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Import Key
if (!empty($arrayfields['d.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}

// Status
if (!empty($arrayfields['d.statut']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	$liststatus = array(
		Adherent::STATUS_DRAFT => $langs->trans("Draft"),
		Adherent::STATUS_VALIDATED => $langs->trans("Validated"),
		Adherent::STATUS_RESILIATED => $langs->trans("MemberStatusResiliatedShort"),
		Adherent::STATUS_EXCLUDED => $langs->trans("MemberStatusExcludedShort")
	);
	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print $form->selectarray('search_status', $liststatus, $search_status, -3, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage');
	print '</td>';
}

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['d.rowid']['label'], $_SERVER["PHP_SELF"], 'd.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.ref']['checked'])) {
	print_liste_field_titre($arrayfields['d.ref']['label'], $_SERVER["PHP_SELF"], 'd.ref', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.civility']['checked'])) {
	print_liste_field_titre($arrayfields['d.civility']['label'], $_SERVER["PHP_SELF"], 'd.civility', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.firstname']['checked'])) {
	print_liste_field_titre($arrayfields['d.firstname']['label'], $_SERVER["PHP_SELF"], 'd.firstname', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.lastname']['checked'])) {
	print_liste_field_titre($arrayfields['d.lastname']['label'], $_SERVER["PHP_SELF"], 'd.lastname', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.gender']['checked'])) {
	print_liste_field_titre($arrayfields['d.gender']['label'], $_SERVER['PHP_SELF'], 'd.gender', $param, "", "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.company']['checked'])) {
	print_liste_field_titre($arrayfields['d.company']['label'], $_SERVER["PHP_SELF"], 'companyname', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.login']['checked'])) {
	print_liste_field_titre($arrayfields['d.login']['label'], $_SERVER["PHP_SELF"], 'd.login', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.morphy']['checked'])) {
	print_liste_field_titre($arrayfields['d.morphy']['label'], $_SERVER["PHP_SELF"], 'd.morphy', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['t.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['t.libelle']['label'], $_SERVER["PHP_SELF"], 't.libelle', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.address']['checked'])) {
	print_liste_field_titre($arrayfields['d.address']['label'], $_SERVER["PHP_SELF"], 'd.address', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.zip']['checked'])) {
	print_liste_field_titre($arrayfields['d.zip']['label'], $_SERVER["PHP_SELF"], 'd.zip', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.town']['checked'])) {
	print_liste_field_titre($arrayfields['d.town']['label'], $_SERVER["PHP_SELF"], 'd.town', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.phone']['checked'])) {
	print_liste_field_titre($arrayfields['d.phone']['label'], $_SERVER["PHP_SELF"], 'd.phone', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.phone_perso']['checked'])) {
	print_liste_field_titre($arrayfields['d.phone_perso']['label'], $_SERVER["PHP_SELF"], 'd.phone_perso', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.phone_mobile']['checked'])) {
	print_liste_field_titre($arrayfields['d.phone_mobile']['label'], $_SERVER["PHP_SELF"], 'd.phone_mobile', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.email']['checked'])) {
	print_liste_field_titre($arrayfields['d.email']['label'], $_SERVER["PHP_SELF"], 'd.email', '', $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.datefin']['checked'])) {
	print_liste_field_titre($arrayfields['d.datefin']['label'], $_SERVER["PHP_SELF"], 'd.datefin,t.subscription', '', $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'totalarray' => &$totalarray, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

if (!empty($arrayfields['d.datec']['checked'])) {
	print_liste_field_titre($arrayfields['d.datec']['label'], $_SERVER["PHP_SELF"], "d.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.birth']['checked'])) {
	print_liste_field_titre($arrayfields['d.birth']['label'], $_SERVER["PHP_SELF"], "d.birth", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.tms']['checked'])) {
	print_liste_field_titre($arrayfields['d.tms']['label'], $_SERVER["PHP_SELF"], "d.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['d.import_key']['label'], $_SERVER["PHP_SELF"], "d.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['d.statut']['checked'])) {
	print_liste_field_titre($arrayfields['d.statut']['label'], $_SERVER["PHP_SELF"], "d.statut,t.subscription,d.datefin", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
	$totalarray['nbfield']++;
}
print "</tr>\n";

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$datefin = $db->jdate($obj->datefin);

	$memberstatic->id = $obj->rowid;
	$memberstatic->ref = $obj->ref;
	$memberstatic->civility_code = $obj->civility;
	$memberstatic->login = $obj->login;
	$memberstatic->lastname = $obj->lastname;
	$memberstatic->firstname = $obj->firstname;
	$memberstatic->gender = $obj->gender;
	$memberstatic->status = $obj->status;
	$memberstatic->datefin = $datefin;
	$memberstatic->socid = $obj->fk_soc;
	$memberstatic->photo = $obj->photo;
	$memberstatic->email = $obj->email;
	$memberstatic->morphy = $obj->morphy;
	$memberstatic->note_public = $obj->note_public;
	$memberstatic->note_private = $obj->note_private;
	$memberstatic->need_subscription = $obj->subscription;

	if (!empty($obj->fk_soc)) {
		$memberstatic->fetch_thirdparty();
		if ($memberstatic->thirdparty->id > 0) {
			$companyname = $memberstatic->thirdparty->name;
			$companynametoshow = $memberstatic->thirdparty->getNomUrl(1);
		} else {
			$companyname = null;
			$companynametoshow = null;
		}
	} else {
		$companyname = $obj->company;
		$companynametoshow = $obj->company;
	}
	$memberstatic->company = (string) $companyname;

	$object = $memberstatic;

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		$membertypestatic->id = $obj->type_id;
		$membertypestatic->label = $obj->type;

		$memberstatic->type = $membertypestatic->label;
		$memberstatic->photo = $obj->photo;

		// Output Kanban
		print $memberstatic->getKanbanView('', array('selected' => in_array($object->id, $arrayofselected)));
		if ($i == (min($num, $limit) - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Technical ID
		if (!empty($arrayfields['d.rowid']['checked'])) {
			print '<td class="center" data-key="id">'.$obj->rowid.'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Ref
		if (!empty($arrayfields['d.ref']['checked'])) {
			print "<td>";
			print $memberstatic->getNomUrl(-1, 0, 'card', 'ref', '', -1, 0, 1);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Title/Civility
		if (!empty($arrayfields['d.civility']['checked'])) {
			print "<td>";
			print dol_escape_htmltag($obj->civility);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Firstname
		if (!empty($arrayfields['d.firstname']['checked'])) {
			print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($obj->firstname).'">';
			print $memberstatic->getNomUrl(0, 0, 'card', 'firstname');
			//print $obj->firstname;
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Lastname
		if (!empty($arrayfields['d.lastname']['checked'])) {
			print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($obj->lastname).'">';
			print $memberstatic->getNomUrl(0, 0, 'card', 'lastname');
			//print $obj->lastname;
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Gender
		if (!empty($arrayfields['d.gender']['checked'])) {
			print '<td>';
			if ($obj->gender) {
				print $langs->trans("Gender".$obj->gender);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Company
		if (!empty($arrayfields['d.company']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($companyname).'">';
			print $companynametoshow;
			print "</td>\n";
		}
		// Login
		if (!empty($arrayfields['d.login']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->login).'">'.$obj->login."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Nature (Moral/Physical)
		if (!empty($arrayfields['d.morphy']['checked'])) {
			print '<td class="center">';
			print $memberstatic->getmorphylib('', 2);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Type label
		if (!empty($arrayfields['t.libelle']['checked'])) {
			$membertypestatic->id = $obj->type_id;
			$membertypestatic->label = $obj->type;
			print '<td class="nowraponall">';
			print $membertypestatic->getNomUrl(1, 32);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Address
		if (!empty($arrayfields['d.address']['checked'])) {
			print '<td class="nocellnopadd tdoverflowmax200" title="'.dol_escape_htmltag($obj->address).'">';
			print dol_escape_htmltag($obj->address);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Zip
		if (!empty($arrayfields['d.zip']['checked'])) {
			print '<td class="nocellnopadd">';
			print dol_escape_htmltag($obj->zip);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Town
		if (!empty($arrayfields['d.town']['checked'])) {
			print '<td class="nocellnopadd">';
			print dol_escape_htmltag($obj->town);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// State / County / Departement
		if (!empty($arrayfields['state.nom']['checked'])) {
			print "<td>";
			print dol_escape_htmltag($obj->state_name);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			$tmparray = getCountry($obj->country, 'all');
			print '<td class="center tdoverflowmax100" title="'.dol_escape_htmltag($tmparray['label']).'">';
			print dol_escape_htmltag($tmparray['label']);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Phone pro
		if (!empty($arrayfields['d.phone']['checked'])) {
			print '<td class="nocellnopadd">';
			print dol_print_phone($obj->phone);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Phone perso
		if (!empty($arrayfields['d.phone_perso']['checked'])) {
			print '<td class="nocellnopadd">';
			print dol_print_phone($obj->phone_perso);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Phone mobile
		if (!empty($arrayfields['d.phone_mobile']['checked'])) {
			print '<td class="nocellnopadd">';
			print dol_print_phone($obj->phone_mobile);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// EMail
		if (!empty($arrayfields['d.email']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->email).'">';
			print dol_print_email($obj->email, 0, 0, 1, 64, 1, 1);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// End of subscription date
		$datefin = $db->jdate($obj->datefin);
		if (!empty($arrayfields['d.datefin']['checked'])) {
			$s = '';
			if ($datefin) {
				$s .= dol_print_date($datefin, 'day');
				if ($memberstatic->hasDelay()) {
					$textlate = ' ('.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($conf->adherent->subscription->warning_delay / 60 / 60 / 24) >= 0 ? '+' : '').ceil($conf->adherent->subscription->warning_delay / 60 / 60 / 24).' '.$langs->trans("days").')';
					$s .= " ".img_warning($langs->trans("SubscriptionLate").$textlate);
				}
			} else {
				if (!empty($obj->subscription)) {
					$s .= '<span class="opacitymedium">'.$langs->trans("SubscriptionNotReceived").'</span>';
					if ($obj->status > 0) {
						$s .= " ".img_warning();
					}
				} else {
					$s .= '<span class="opacitymedium">'.$langs->trans("SubscriptionNotNeeded").'</span>';
				}
			}
			print '<td class="nowraponall center tdoverflowmax150" title="'.dolPrintHTMLForAttribute(dol_string_nohtmltag($s)).'">';
			print $s;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['d.datec']['checked'])) {
			print '<td class="nowrap center">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Birth
		if (!empty($arrayfields['d.birth']['checked'])) {
			print '<td class="nowrap center">';
			print dol_print_date($db->jdate($obj->birth), 'day', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['d.tms']['checked'])) {
			print '<td class="nowrap center">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Import key
		if (!empty($arrayfields['d.import_key']['checked'])) {
			print '<td class="tdoverflowmax100 center" title="'.dol_escape_htmltag($obj->import_key).'">';
			print dol_escape_htmltag($obj->import_key);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['d.statut']['checked'])) {
			print '<td class="nowrap center">';
			print $memberstatic->LibStatut($obj->status, $obj->subscription, $datefin, 5);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';


// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_'.$object->module, '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
