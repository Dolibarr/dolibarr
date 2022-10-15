<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003  Jean-Louis Bergamo      <jlb@j1b.org>
 * Copyright (C) 2004-2022  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2016  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2021		Frédéric France			<frederic.france@netlogic.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$langs->loadLangs(array("members", "companies"));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'memberslist'; // To manage different context of search

$search = GETPOST("search", 'alpha');
$search_ref = GETPOST("search_ref", 'alpha');
$search_lastname = GETPOST("search_lastname", 'alpha');
$search_firstname = GETPOST("search_firstname", 'alpha');
$search_gender = GETPOST("search_gender", 'alpha');
$search_civility = GETPOST("search_civility", 'alpha');
$search_company = GETPOST('search_company', 'alphanohtml');
$search_login = GETPOST("search_login", 'alpha');
$search_address = GETPOST("search_address", 'alpha');
$search_zip = GETPOST("search_zip", 'alpha');
$search_town = GETPOST("search_town", 'alpha');
$search_state = GETPOST("search_state", 'alpha');
$search_country = GETPOST("search_country", 'alpha');
$search_phone = GETPOST("search_phone", 'alpha');
$search_phone_perso = GETPOST("search_phone_perso", 'alpha');
$search_phone_mobile = GETPOST("search_phone_mobile", 'alpha');
$search_type = GETPOST("search_type", 'alpha');
$search_email = GETPOST("search_email", 'alpha');
$search_categ = GETPOST("search_categ", 'int');
$search_filter = GETPOST("search_filter", 'alpha');
$search_status = GETPOST("search_status", 'intcomma');
$search_import_key  = trim(GETPOST("search_import_key", "alpha"));
$catid        = GETPOST("catid", 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$socid = GETPOST('socid', 'int');

$filter = GETPOST("filter", 'alpha');
if ($filter) {
	$search_filter = $filter; // For backward compatibility
}
$statut = GETPOST("statut", 'alpha');
if ($statut != '') {
	$search_status = $statut; // For backward compatibility
}

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

if ($search_status < -2) {
	$search_status = '';
}

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
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

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('memberlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'd.ref'=>'Ref',
	'd.login'=>'Login',
	'd.lastname'=>'Lastname',
	'd.firstname'=>'Firstname',
	'd.login'=>'Login',
	'd.societe'=>"Company",
	'd.email'=>'EMail',
	'd.address'=>'Address',
	'd.zip'=>'Zip',
	'd.town'=>'Town',
	'd.phone'=>"Phone",
	'd.phone_perso'=>"PhonePerso",
	'd.phone_mobile'=>"PhoneMobile",
	'd.note_public'=>'NotePublic',
	'd.note_private'=>'NotePrivate',
);
if ($db->type == 'pgsql') {
	unset($fieldstosearchall['d.rowid']);
}
$arrayfields = array(
	'd.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'd.civility'=>array('label'=>$langs->trans("Civility"), 'checked'=>0),
	'd.lastname'=>array('label'=>$langs->trans("Lastname"), 'checked'=>1),
	'd.firstname'=>array('label'=>$langs->trans("Firstname"), 'checked'=>1),
	'd.gender'=>array('label'=>$langs->trans("Gender"), 'checked'=>0),
	'd.company'=>array('label'=>$langs->trans("Company"), 'checked'=>1),
	'd.login'=>array('label'=>$langs->trans("Login"), 'checked'=>1),
	'd.morphy'=>array('label'=>$langs->trans("MemberNature"), 'checked'=>1),
	't.libelle'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
	'd.email'=>array('label'=>$langs->trans("Email"), 'checked'=>1),
	'd.address'=>array('label'=>$langs->trans("Address"), 'checked'=>0),
	'd.zip'=>array('label'=>$langs->trans("Zip"), 'checked'=>0),
	'd.town'=>array('label'=>$langs->trans("Town"), 'checked'=>0),
	'd.phone'=>array('label'=>$langs->trans("Phone"), 'checked'=>0),
	'd.phone_perso'=>array('label'=>$langs->trans("PhonePerso"), 'checked'=>0),
	'd.phone_mobile'=>array('label'=>$langs->trans("PhoneMobile"), 'checked'=>0),
	'state.nom'=>array('label'=>$langs->trans("State"), 'checked'=>0),
	'country.code_iso'=>array('label'=>$langs->trans("Country"), 'checked'=>0),
	/*'d.note_public'=>array('label'=>$langs->trans("NotePublic"), 'checked'=>0),
	'd.note_private'=>array('label'=>$langs->trans("NotePrivate"), 'checked'=>0),*/
	'd.datefin'=>array('label'=>$langs->trans("EndSubscription"), 'checked'=>1, 'position'=>500),
	'd.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'd.birth'=>array('label'=>$langs->trans("Birthday"), 'checked'=>0, 'position'=>500),
	'd.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'd.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	'd.import_key'=>array('label'=>"ImportId", 'checked'=>0, 'position'=>1100),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

// Security check
$result = restrictedArea($user, 'adherent');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('socid'=>isset($socid) ? $socid : null);
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
		$sall = "";
		$toselect = array();
		$search_array_options = array();
	}

	// Close
	if ($massaction == 'close' && $user->rights->adherent->creer) {
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
	if ($massaction == 'createexternaluser' && $user->rights->adherent->creer && $user->rights->user->user->creer) {
		$tmpmember = new Adherent($db);
		$error = 0;
		$nbcreated = 0;

		$db->begin();

		foreach ($toselect as $idtoclose) {
			$tmpmember->fetch($idtoclose);

			if (!empty($tmpmember->fk_soc)) {
				$nuser = new User($db);
				$tmpuser = dol_clone($tmpmember);

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

	// Mass actions
	$objectclass = 'Adherent';
	$objectlabel = 'Members';
	$permissiontoread = $user->rights->adherent->lire;
	$permissiontodelete = $user->rights->adherent->supprimer;
	$permissiontoadd = $user->rights->adherent->creer;
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

$title = $langs->trans("Members");

$now = dol_now();

if ((!empty($search_categ) && $search_categ > 0) || !empty($catid)) {
	$sql = "SELECT DISTINCT";
} else {
	$sql = "SELECT";
}
$sql .= " d.rowid, d.ref, d.login, d.lastname, d.firstname, d.gender, d.societe as company, d.fk_soc,";
$sql .= " d.civility, d.datefin, d.address, d.zip, d.town, d.state_id, d.country,";
$sql .= " d.email, d.phone, d.phone_perso, d.phone_mobile, d.birth, d.public, d.photo,";
$sql .= " d.fk_adherent_type as type_id, d.morphy, d.statut, d.datec as date_creation, d.tms as date_update,";
$sql .= " d.note_private, d.note_public, d.import_key,";
$sql .= " s.nom,";
$sql .= " ".$db->ifsql("d.societe IS NULL", "s.nom", "d.societe")." as companyname,";
$sql .= " t.libelle as type, t.subscription,";
$sql .= " state.code_departement as state_code, state.nom as state_name,";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? "ef.".$key." as options_".$key.', ' : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d";
if (!empty($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (d.rowid = ef.fk_object)";
}
if ((!empty($search_categ) && ($search_categ > 0 || $search_categ == -2)) || !empty($catid)) {
	// We need this table joined to the select in order to filter by categ
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_member as cm ON d.rowid = cm.fk_member";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on (country.rowid = d.country)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as state on (state.rowid = d.state_id)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on (s.rowid = d.fk_soc)";
$sql .= ", ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid";
if ($catid > 0) {
	$sql .= " AND cm.fk_categorie = ".((int) $catid);
}
if ($catid == -2) {
	$sql .= " AND cm.fk_categorie IS NULL";
}
if ($search_categ > 0) {
	$sql .= " AND cm.fk_categorie = ".((int) $search_categ);
}
if ($search_categ == -2) {
	$sql .= " AND cm.fk_categorie IS NULL";
}
$sql .= " AND d.entity IN (".getEntity('adherent').")";
if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if ($search_type > 0) {
	$sql .= " AND t.rowid=".((int) $search_type);
}
if ($search_filter == 'withoutsubscription') {
	$sql .= " AND (datefin IS NULL)";
}
if ($search_filter == 'uptodate') {
	$sql .= " AND (datefin >= '".$db->idate($now)."' OR t.subscription = '0')";
}
if ($search_filter == 'outofdate') {
	$sql .= " AND (datefin < '".$db->idate($now)."' AND t.subscription = '1')";
}
if ($search_status != '') {
	// Peut valoir un nombre ou liste de nombre separes par virgules
	$sql .= " AND d.statut in (".$db->sanitize($db->escape($search_status)).")";
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

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records with no order and no limits
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	if ($resql) {
		$nbtotalofrecords = $db->num_rows($resql);
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

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


$arrayofselected = is_array($toselect) ? $toselect : array();

if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/adherents/card.php?id='.$id);
	exit;
}

$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
llxHeader('', $title, $help_url);

if (GETPOSTISSET("search_status")) {
	if ($search_status == '-1,1') { // TODO : check this test as -1 == Adherent::STATUS_DRAFT and -2 == Adherent::STATUS_EXLCUDED
		$title = $langs->trans("MembersListQualified");
	}
	if ($search_status == Adherent::STATUS_DRAFT) {
		$title = $langs->trans("MembersListToValid");
	}
	if ($search_status == Adherent::STATUS_VALIDATED && $filter == '') {
		$title = $langs->trans("MenuMembersValidated");
	}
	if ($search_status == Adherent::STATUS_VALIDATED && $filter == 'withoutsubscription') {
		$title = $langs->trans("MembersWithSubscriptionToReceive");
	}
	if ($search_status == Adherent::STATUS_VALIDATED && $filter == 'uptodate') {
		$title = $langs->trans("MembersListUpToDate");
	}
	if ($search_status == Adherent::STATUS_VALIDATED && $filter == 'outofdate') {
		$title = $langs->trans("MembersListNotUpToDate");
	}
	if ((string) $search_status == (string) Adherent::STATUS_RESILIATED) {	// The cast to string is required to have test false when search_status is ''
		$title = $langs->trans("MembersListResiliated");
	}
	if ($search_status == Adherent::STATUS_EXCLUDED) {
		$title = $langs->trans("MembersListExcluded");
	}
} elseif ($action == 'search') {
	$title = $langs->trans("MembersListQualified");
}

if ($search_type > 0) {
	$membertype = new AdherentType($db);
	$result = $membertype->fetch($search_type);
	$title .= " (".$membertype->label.")";
}

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($sall != "") {
	$param .= "&sall=".urlencode($sall);
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
	$param .= "&search_categ=".urlencode($search_categ);
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
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
if ($user->rights->adherent->creer) {
	$arrayofmassactions['close'] = img_picto('', 'close_title', 'class="pictofixedwidth"').$langs->trans("Resiliate");
}
if ($user->rights->adherent->supprimer) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (isModEnabled('category') && $user->rights->adherent->creer) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if ($user->rights->adherent->creer && $user->rights->user->user->creer) {
	$arrayofmassactions['createexternaluser'] = img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("CreateExternalUser");
}
if (GETPOST('nomassaction', 'int') || in_array($massaction, array('presend', 'predelete', 'preaffecttag'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = '';
if ($user->rights->adherent->creer) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewMember'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/adherents/card.php?action=create');
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "Information";
$modelmail = "member";
$objecttmp = new Adherent($db);
$trackid = 'mem'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($sall) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
}

// Filter on categories
$moreforfilter = '';
if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= img_picto($langs->trans('Categories'), 'category', 'class="pictofixedlength"').$formother->select_categories(Categorie::TYPE_MEMBER, $search_categ, 'search_categ', 1);
	$moreforfilter .= '</div>';
}
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
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

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Line for filters fields
print '<tr class="liste_titre_filter">';

// Line numbering
if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
	print '<td class="liste_titre">&nbsp;</td>';
}

// Ref
if (!empty($arrayfields['d.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75imp" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';
}
if (!empty($arrayfields['d.civility']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50imp" type="text" name="search_civility" value="'.dol_escape_htmltag($search_civility).'"></td>';
}
if (!empty($arrayfields['d.firstname']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'"></td>';
}
if (!empty($arrayfields['d.lastname']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'"></td>';
}
if (!empty($arrayfields['d.gender']['checked'])) {
	print '<td class="liste_titre">';
	$arraygender = array('man'=>$langs->trans("Genderman"), 'woman'=>$langs->trans("Genderwoman"), 'other'=>$langs->trans("Genderother"));
	print $form->selectarray('search_gender', $arraygender, $search_gender, 1);
	print '</td>';
}
if (!empty($arrayfields['d.company']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_company" value="'.dol_escape_htmltag($search_company).'"></td>';
}
if (!empty($arrayfields['d.login']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'"></td>';
}
if (!empty($arrayfields['d.morphy']['checked'])) {
	print '<td class="liste_titre left">';
	print '</td>';
}
if (!empty($arrayfields['t.libelle']['checked'])) {
	print '<td class="liste_titre">';
	$listetype = $membertypestatic->liste_array();
	print $form->selectarray("search_type", $listetype, $search_type, 1, 0, 0, '', 0, 32);
	print '</td>';
}

if (!empty($arrayfields['d.address']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_address" value="'.dol_escape_htmltag($search_address).'"></td>';
}

if (!empty($arrayfields['d.zip']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth50imp" type="text" name="search_zip" value="'.dol_escape_htmltag($search_zip).'"></td>';
}
if (!empty($arrayfields['d.town']['checked'])) {
	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth75imp" type="text" name="search_town" value="'.dol_escape_htmltag($search_town).'"></td>';
}
// State
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
	print '<input class="flat maxwidth50" type="text" name="search_phone_perso" value="'.dol_escape_htmltag($search_phone_perso).'"></td>';
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
	$selectarray = array('-1'=>'', 'withoutsubscription'=>$langs->trans("WithoutSubscription"), 'uptodate'=>$langs->trans("UpToDate"), 'outofdate'=>$langs->trans("OutOfDate"));
	print $form->selectarray('search_filter', $selectarray, $search_filter);
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['d.datec']['checked'])) {
	print '<td class="liste_titre">';
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
	print '</td>';
}
// Status
if (!empty($arrayfields['d.statut']['checked'])) {
	print '<td class="liste_titre right maxwidthonsmartphone">';
	$liststatus = array(
		Adherent::STATUS_DRAFT => $langs->trans("Draft"),
		Adherent::STATUS_VALIDATED => $langs->trans("Validated"),
		Adherent::STATUS_RESILIATED => $langs->trans("MemberStatusResiliatedShort"),
		Adherent::STATUS_EXCLUDED =>$langs->trans("MemberStatusExcludedShort")
	);
	print $form->selectarray('search_status', $liststatus, $search_status, -3);
	print '</td>';
}
if (!empty($arrayfields['d.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring maxwidth50" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	// Action column
	print '<td class="liste_titre middle">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

print '<tr class="liste_titre">';
if (!empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
}
if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
	print_liste_field_titre("ID", $_SERVER["PHP_SELF"], '', '', $param, 'align="center"', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.ref']['checked'])) {
	print_liste_field_titre($arrayfields['d.ref']['label'], $_SERVER["PHP_SELF"], 'd.ref', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.civility']['checked'])) {
	print_liste_field_titre($arrayfields['d.civility']['label'], $_SERVER["PHP_SELF"], 'd.civility', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.firstname']['checked'])) {
	print_liste_field_titre($arrayfields['d.firstname']['label'], $_SERVER["PHP_SELF"], 'd.firstname', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.lastname']['checked'])) {
	print_liste_field_titre($arrayfields['d.lastname']['label'], $_SERVER["PHP_SELF"], 'd.lastname', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.gender']['checked'])) {
	print_liste_field_titre($arrayfields['d.gender']['label'], $_SERVER['PHP_SELF'], 'd.gender', $param, "", "", $sortfield, $sortorder);
}
if (!empty($arrayfields['d.company']['checked'])) {
	print_liste_field_titre($arrayfields['d.company']['label'], $_SERVER["PHP_SELF"], 'companyname', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.login']['checked'])) {
	print_liste_field_titre($arrayfields['d.login']['label'], $_SERVER["PHP_SELF"], 'd.login', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.morphy']['checked'])) {
	print_liste_field_titre($arrayfields['d.morphy']['label'], $_SERVER["PHP_SELF"], 'd.morphy', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['t.libelle']['checked'])) {
	print_liste_field_titre($arrayfields['t.libelle']['label'], $_SERVER["PHP_SELF"], 't.libelle', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.address']['checked'])) {
	print_liste_field_titre($arrayfields['d.address']['label'], $_SERVER["PHP_SELF"], 'd.address', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.zip']['checked'])) {
	print_liste_field_titre($arrayfields['d.zip']['label'], $_SERVER["PHP_SELF"], 'd.zip', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.town']['checked'])) {
	print_liste_field_titre($arrayfields['d.town']['label'], $_SERVER["PHP_SELF"], 'd.town', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['state.nom']['checked'])) {
	print_liste_field_titre($arrayfields['state.nom']['label'], $_SERVER["PHP_SELF"], "state.nom", "", $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "country.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['d.phone']['checked'])) {
	print_liste_field_titre($arrayfields['d.phone']['label'], $_SERVER["PHP_SELF"], 'd.phone', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.phone_perso']['checked'])) {
	print_liste_field_titre($arrayfields['d.phone_perso']['label'], $_SERVER["PHP_SELF"], 'd.phone_perso', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.phone_mobile']['checked'])) {
	print_liste_field_titre($arrayfields['d.phone_mobile']['label'], $_SERVER["PHP_SELF"], 'd.phone_mobile', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.email']['checked'])) {
	print_liste_field_titre($arrayfields['d.email']['label'], $_SERVER["PHP_SELF"], 'd.email', '', $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.datefin']['checked'])) {
	print_liste_field_titre($arrayfields['d.datefin']['label'], $_SERVER["PHP_SELF"], 'd.datefin', '', $param, '', $sortfield, $sortorder, 'center ');
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['d.datec']['checked'])) {
	print_liste_field_titre($arrayfields['d.datec']['label'], $_SERVER["PHP_SELF"], "d.datec", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.birth']['checked'])) {
	print_liste_field_titre($arrayfields['d.birth']['label'], $_SERVER["PHP_SELF"], "d.birth", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.tms']['checked'])) {
	print_liste_field_titre($arrayfields['d.tms']['label'], $_SERVER["PHP_SELF"], "d.tms", "", $param, 'align="center" class="nowrap"', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.statut']['checked'])) {
	print_liste_field_titre($arrayfields['d.statut']['label'], $_SERVER["PHP_SELF"], "d.statut", "", $param, 'class="right"', $sortfield, $sortorder);
}
if (!empty($arrayfields['d.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['d.import_key']['label'], $_SERVER["PHP_SELF"], "d.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
}
print "</tr>\n";

$i = 0;
$totalarray = array();
$totalarray['nbfield'] = 0;
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$datefin = $db->jdate($obj->datefin);
	$memberstatic->id = $obj->rowid;
	$memberstatic->ref = $obj->ref;
	$memberstatic->civility_id = $obj->civility;
	$memberstatic->login = $obj->login;
	$memberstatic->lastname = $obj->lastname;
	$memberstatic->firstname = $obj->firstname;
	$memberstatic->gender = $obj->gender;
	$memberstatic->statut = $obj->statut;
	$memberstatic->datefin = $datefin;
	$memberstatic->socid = $obj->fk_soc;
	$memberstatic->photo = $obj->photo;
	$memberstatic->email = $obj->email;
	$memberstatic->morphy = $obj->morphy;
	$memberstatic->note_public = $obj->note_public;
	$memberstatic->note_private = $obj->note_private;

	if (!empty($obj->fk_soc)) {
		$memberstatic->fetch_thirdparty();
		if ($memberstatic->thirdparty->id > 0) {
			$companyname = $memberstatic->thirdparty->name;
			$companynametoshow = $memberstatic->thirdparty->getNomUrl(1);
		}
	} else {
		$companyname = $obj->company;
		$companynametoshow = $obj->company;
	}
	$memberstatic->company = $companyname;

	print '<tr class="oddeven">';

	if (!empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
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
	// Civility
	if (!empty($arrayfields['d.civility']['checked'])) {
		print "<td>";
		print $obj->civility;
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Firstname
	if (!empty($arrayfields['d.firstname']['checked'])) {
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->firstname).'">';
		print $memberstatic->getNomUrl(0, 0, 'card', 'firstname');
		//print $obj->firstname;
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Lastname
	if (!empty($arrayfields['d.lastname']['checked'])) {
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->lastname).'">';
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
		$s = '';
		if ($obj->morphy == 'phy') {
			$s .= '<span class="customer-back" title="'.$langs->trans("Physical").'">'.dol_substr($langs->trans("Physical"), 0, 1).'</span>';
		}
		if ($obj->morphy == 'mor') {
			$s .= '<span class="vendor-back" title="'.$langs->trans("Moral").'">'.dol_substr($langs->trans("Moral"), 0, 1).'</span>';
		}
		print $s;
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
		print $obj->address;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Zip
	if (!empty($arrayfields['d.zip']['checked'])) {
		print '<td class="nocellnopadd">';
		print $obj->zip;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Town
	if (!empty($arrayfields['d.town']['checked'])) {
		print '<td class="nocellnopadd">';
		print $obj->town;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// State
	if (!empty($arrayfields['state.nom']['checked'])) {
		print "<td>".$obj->state_name."</td>\n";
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
		print $obj->phone;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Phone perso
	if (!empty($arrayfields['d.phone_perso']['checked'])) {
		print '<td class="nocellnopadd">';
		print $obj->phone_perso;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Phone mobile
	if (!empty($arrayfields['d.phone_mobile']['checked'])) {
		print '<td class="nocellnopadd">';
		print $obj->phone_mobile;
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
	}
	// End of subscription date
	$datefin = $db->jdate($obj->datefin);
	if (!empty($arrayfields['d.datefin']['checked'])) {
		print '<td class="nowrap center">';
		if ($datefin) {
			print dol_print_date($datefin, 'day');
			if ($memberstatic->hasDelay()) {
				$textlate = ' ('.$langs->trans("DateReference").' > '.$langs->trans("DateToday").' '.(ceil($conf->adherent->subscription->warning_delay / 60 / 60 / 24) >= 0 ? '+' : '').ceil($conf->adherent->subscription->warning_delay / 60 / 60 / 24).' '.$langs->trans("days").')';
				print " ".img_warning($langs->trans("SubscriptionLate").$textlate);
			}
		} else {
			if (!empty($obj->subscription)) {
				print $langs->trans("SubscriptionNotReceived");
				if ($obj->statut > 0) {
					print " ".img_warning();
				}
			} else {
				print '&nbsp;';
			}
		}
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
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
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Status
	if (!empty($arrayfields['d.statut']['checked'])) {
		print '<td class="nowrap right">';
		print $memberstatic->LibStatut($obj->statut, $obj->subscription, $datefin, 5);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['d.import_key']['checked'])) {
		print '<td class="tdoverflowmax100 center" title="'.dol_escape_htmltag($obj->import_key).'">';
		print dol_escape_htmltag($obj->import_key);
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Action column
	if (empty($conf->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
		print '<td class="center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
	}
	if (!$i) {
		$totalarray['nbfield']++;
	}

	print '</tr>'."\n";
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
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

$db->free($resql);

$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print "</div>";
print '</form>';

// End of page
llxFooter();
$db->close();
