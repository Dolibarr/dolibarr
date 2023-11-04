<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Eric Seigne             <erics@rycks.com>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018       Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2020       Open-Dsi      			<support@open-dsi.fr>
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
 *	    \file       htdocs/contact/list.php
 *      \ingroup    societe
 *		\brief      Page to list all contacts
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "suppliers", "categories"));

$socialnetworks = getArrayOfSocialNetworks();

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'contactlist';

// Security check
$id = GETPOST('id', 'int');
$contactid = GETPOST('id', 'int');
$ref = ''; // There is no ref for contacts
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contact', $contactid, '');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_cti = preg_replace('/^0+/', '', preg_replace('/[^0-9]/', '', GETPOST('search_cti', 'alphanohtml'))); // Phone number without any special chars
$search_phone = GETPOST("search_phone", 'alpha');

$search_id = GETPOST("search_id", "int");
$search_firstlast_only = GETPOST("search_firstlast_only", 'alpha');
$search_lastname = GETPOST("search_lastname", 'alpha');
$search_firstname = GETPOST("search_firstname", 'alpha');
$search_societe = GETPOST("search_societe", 'alpha');
$search_poste = GETPOST("search_poste", 'alpha');
$search_phone_perso = GETPOST("search_phone_perso", 'alpha');
$search_phone_pro = GETPOST("search_phone_pro", 'alpha');
$search_phone_mobile = GETPOST("search_phone_mobile", 'alpha');
$search_fax = GETPOST("search_fax", 'alpha');
$search_email = GETPOST("search_email", 'alpha');
if (!empty($conf->mailing->enabled)) {
	$search_no_email = GETPOSTISSET("search_no_email") ? GETPOST("search_no_email", 'int') : -1;
} else {
	$search_no_email = -1;
}
if (!empty($conf->socialnetworks->enabled)) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active']) {
			$search_[$key] = GETPOST("search_".$key, 'alpha');
		}
	}
}
$search_priv = GETPOST("search_priv", 'alpha');
$search_categ = GETPOST("search_categ", 'int');
$search_categ_thirdparty = GETPOST("search_categ_thirdparty", 'int');
$search_categ_supplier = GETPOST("search_categ_supplier", 'int');
$search_status = GETPOST("search_status", 'int');
$search_type = GETPOST('search_type', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_import_key = GETPOST("search_import_key", "alpha");
$search_country = GETPOST("search_country", 'intcomma');
$search_roles = GETPOST("search_roles", 'array');
$search_level = GETPOST("search_level", "array");
$search_stcomm = GETPOST('search_stcomm', 'int');

if ($search_status === '') {
	$search_status = 1; // always display active customer first
}
if ($search_no_email === '') {
	$search_no_email = -1;
}

$optioncss = GETPOST('optioncss', 'alpha');


$type = GETPOST("type", 'aZ');
$view = GETPOST("view", 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$userid = GETPOST('userid', 'int');
$begin = GETPOST('begin');
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "p.lastname";
}
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;

$titre = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("ListOfContacts") : $langs->trans("ListOfContactsAddresses"));
if ($type == "p") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactprospectlist';
	}
	$titre .= '  ('.$langs->trans("ThirdPartyProspects").')';
	$urlfiche = "card.php";
}
if ($type == "c") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactcustomerlist';
	}
	$titre .= '  ('.$langs->trans("ThirdPartyCustomers").')';
	$urlfiche = "card.php";
} elseif ($type == "f") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactsupplierlist';
	}
	$titre .= ' ('.$langs->trans("ThirdPartySuppliers").')';
	$urlfiche = "card.php";
} elseif ($type == "o") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactotherlist';
	}
	$titre .= ' ('.$langs->trans("OthersNotLinkedToThirdParty").')';
	$urlfiche = "";
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Contact($db);
$hookmanager->initHooks(array('contactlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	// don't allow search in private notes for external users when doing "search in all"
	if (!empty($user->socid) && $key == "note_private") {
		continue;
	}

	if (empty($val['searchall'])) {
		continue;
	}

	$fieldstosearchall['p.'.$key] = $val['label'];
}

// Add none object fields for "search in all"
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
	$fieldstosearchall['s.nom'] = "ThirdParty";
}

// Definition of fields for list
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (empty($val['visible'])) {
		continue;
	}

	$arrayfields['p.'.$key] = array(
		'label'=>$val['label'],
		'checked'=>(($val['visible'] < 0) ? 0 : 1),
		'enabled'=>($val['enabled'] && ($val['visible'] != 3)),
		'position'=>$val['position']);
}

// Add none object fields to fields for list
$arrayfields['country.code_iso'] = array('label'=>"Country", 'position'=>22, 'checked'=>0);
if (empty($conf->global->SOCIETE_DISABLE_CONTACTS)) {
	$arrayfields['s.nom'] = array('label'=>"ThirdParty", 'position'=>25, 'checked'=>1);
}

$arrayfields['unsubscribed'] = array(
		'label'=>'No_Email',
		'checked'=>0,
		'enabled'=>(!empty($conf->mailing->enabled)),
		'position'=>41);

if (!empty($conf->socialnetworks->enabled)) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active']) {
			$arrayfields['p.'.$key] = array(
				'label' => $value['label'],
				'checked' => 0,
				'position' => 300
			);
		}
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


if (($id > 0 || !empty($ref)) && $action != 'add') {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		dol_print_error($db);
	}
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Did we click on purge search criteria ?
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {	// All tests are required to be compatible with all browsers
		$sall = "";
		$search_id = '';
		$search_firstlast_only = "";
		$search_lastname = "";
		$search_firstname = "";
		$search_societe = "";
		$search_town = "";
		$search_zip = "";
		$search_country = "";
		$search_poste = "";
		$search_phone = "";
		$search_phone_perso = "";
		$search_phone_pro = "";
		$search_phone_mobile = "";
		$search_fax = "";
		$search_email = "";
		$search_no_email = -1;
		if (!empty($conf->socialnetworks->enabled)) {
			foreach ($socialnetworks as $key => $value) {
				if ($value['active']) {
					$search_[$key] = "";
				}
			}
		}
		$search_priv = "";
		$search_stcomm = '';
		$search_level = '';
		$search_status = -1;
		$search_categ = '';
		$search_categ_thirdparty = '';
		$search_categ_supplier = '';
		$search_import_key = '';
		$toselect = array();
		$search_array_options = array();
		$search_roles = array();
	}

	// Mass actions
	$objectclass = 'Contact';
	$objectlabel = 'Contact';
	$permissiontoread = $user->rights->societe->lire;
	$permissiontodelete = $user->rights->societe->supprimer;
	$permissiontoadd = $user->rights->societe->creer;
	$uploaddir = $conf->societe->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	if ($action == 'setstcomm') {
		$object = new Contact($db);
		$result = $object->fetch(GETPOST('stcommcontactid'));
		$object->stcomm_id = dol_getIdFromCode($db, GETPOST('stcomm', 'alpha'), 'c_stcommcontact');
		$result = $object->update($object->id, $user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action = '';
	}
}

if ($search_priv < 0) {
	$search_priv = '';
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);

if (!empty($conf->global->THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES)) {
	$contactstatic->loadCacheOfProspStatus();
}

$title = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));

// Select every potentiels, and note each potentiels which fit in search parameters
$tab_level = array();
$sql = "SELECT code, label, sortorder";
$sql .= " FROM ".MAIN_DB_PREFIX."c_prospectcontactlevel";
$sql .= " WHERE active > 0";
$sql .= " ORDER BY sortorder";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		// Compute level text
		$level = $langs->trans($obj->code);
		if ($level == $obj->code) {
			$level = $langs->trans($obj->label);
		}
		$tab_level[$obj->code] = $level;
	}
} else {
	dol_print_error($db);
}

$sql = "SELECT s.rowid as socid, s.nom as name,";
$sql .= " p.rowid, p.lastname as lastname, p.statut, p.firstname, p.zip, p.town, p.poste, p.email,";
$sql .= " p.socialnetworks, p.photo,";
$sql .= " p.phone as phone_pro, p.phone_mobile, p.phone_perso, p.fax, p.fk_pays, p.priv, p.datec as date_creation, p.tms as date_update,";
$sql .= " st.libelle as stcomm, st.picto as stcomm_picto, p.fk_stcommcontact as stcomm_id, p.fk_prospectcontactlevel,";
$sql .= " co.label as country, co.code as country_code";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
	}
}
if (!empty($conf->mailing->enabled)) {
	$sql .= ", (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = p.email) as unsubscribed";
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (p.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON co.rowid = p.fk_pays";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_stcommcontact as st ON st.id = p.fk_stcommcontact";
if (!empty($search_categ) && $search_categ != '-1') {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_contact as cc ON p.rowid = cc.fk_socpeople"; // We need this table joined to the select in order to filter by categ
}
if (!empty($search_categ_thirdparty) && $search_categ_thirdparty != '-1') {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_societe as cs ON s.rowid = cs.fk_soc"; // We need this table joined to the select in order to filter by categ
}
if (!empty($search_categ_supplier) && $search_categ_supplier != '-1') {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_fournisseur as cs2 ON s.rowid = cs2.fk_soc"; // We need this table joined to the select in order to filter by categ
}
if (!$user->rights->societe->client->voir && !$socid) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
}
$sql .= ' WHERE p.entity IN ('.getEntity('socpeople').')';
if (!$user->rights->societe->client->voir && !$socid) { //restriction
	$sql .= " AND (sc.fk_user = ".((int) $user->id)." OR p.fk_soc IS NULL)";
}
if (!empty($userid)) {    // propre au commercial
	$sql .= " AND p.fk_user_creat=".((int) $userid);
}
if ($search_level) {
	$sql .= natural_search("p.fk_prospectcontactlevel", join(',', $search_level), 3);
}
if ($search_stcomm != '' && $search_stcomm != -2) {
	$sql .= natural_search("p.fk_stcommcontact", $search_stcomm, 2);
}

// Filter to exclude not owned private contacts
if ($search_priv != '0' && $search_priv != '1') {
	$sql .= " AND (p.priv='0' OR (p.priv='1' AND p.fk_user_creat=".((int) $user->id)."))";
} else {
	if ($search_priv == '0') {
		$sql .= " AND p.priv='0'";
	}
	if ($search_priv == '1') {
		$sql .= " AND (p.priv='1' AND p.fk_user_creat=".((int) $user->id).")";
	}
}

if ($search_categ > 0) {
	$sql .= " AND cc.fk_categorie = ".((int) $search_categ);
}
if ($search_categ == -2) {
	$sql .= " AND cc.fk_categorie IS NULL";
}
if ($search_categ_thirdparty > 0) {
	$sql .= " AND cs.fk_categorie = ".((int) $search_categ_thirdparty);
}
if ($search_categ_thirdparty == -2) {
	$sql .= " AND cs.fk_categorie IS NULL";
}
if ($search_categ_supplier > 0) {
	$sql .= " AND cs2.fk_categorie = ".((int) $search_categ_supplier);
}
if ($search_categ_supplier == -2) {
	$sql .= " AND cs2.fk_categorie IS NULL";
}

if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
if (strlen($search_phone)) {
	$sql .= natural_search(array('p.phone', 'p.phone_perso', 'p.phone_mobile'), $search_phone);
}
if (strlen($search_cti)) {
	$sql .= natural_search(array('p.phone', 'p.phone_perso', 'p.phone_mobile'), $search_cti);
}
if (strlen($search_firstlast_only)) {
	$sql .= natural_search(array('p.lastname', 'p.firstname'), $search_firstlast_only);
}

if ($search_id > 0) {
	$sql .= natural_search('p.rowid', $search_id, 1);
}
if ($search_lastname) {
	$sql .= natural_search('p.lastname', $search_lastname);
}
if ($search_firstname) {
	$sql .= natural_search('p.firstname', $search_firstname);
}
if ($search_societe) {
	$sql .= natural_search(empty($conf->global->SOCIETE_DISABLE_CONTACTS) ? 's.nom' : 'p.fk_soc', $search_societe);
}
if ($search_country) {
	$sql .= " AND p.fk_pays IN (".$db->sanitize($search_country).')';
}
if (strlen($search_poste)) {
	$sql .= natural_search('p.poste', $search_poste);
}
if (strlen($search_phone_perso)) {
	$sql .= natural_search('p.phone_perso', $search_phone_perso);
}
if (strlen($search_phone_pro)) {
	$sql .= natural_search('p.phone', $search_phone_pro);
}
if (strlen($search_phone_mobile)) {
	$sql .= natural_search('p.phone_mobile', $search_phone_mobile);
}
if (strlen($search_fax)) {
	$sql .= natural_search('p.fax', $search_fax);
}
if (!empty($conf->socialnetworks->enabled)) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active'] && strlen($search_[$key])) {
			$sql .= ' AND p.socialnetworks LIKE \'%"'.$key.'":"'.$search_[$key].'%\'';
		}
	}
}
if (strlen($search_email)) {
	$sql .= natural_search('p.email', $search_email);
}
if (strlen($search_zip)) {
	$sql .= natural_search("p.zip", $search_zip);
}
if (strlen($search_town)) {
	$sql .= natural_search("p.town", $search_town);
}
if (count($search_roles) > 0) {
	$sql .= " AND p.rowid IN (SELECT sc.fk_socpeople FROM ".MAIN_DB_PREFIX."societe_contacts as sc WHERE sc.fk_c_type_contact IN (".$db->sanitize(implode(',', $search_roles))."))";
}
if ($search_no_email != -1 && $search_no_email > 0) {
	$sql .= " AND (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = p.email) > 0";
}
if ($search_no_email != -1 && $search_no_email == 0) {
	$sql .= " AND (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = p.email) = 0 AND p.email IS NOT NULL  AND p.email <> ''";
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND p.statut = ".((int) $search_status);
}
if ($search_import_key) {
	$sql .= natural_search("p.import_key", $search_import_key);
}
if ($type == "o") {        // filtre sur type
	$sql .= " AND p.fk_soc IS NULL";
} elseif ($type == "f") {        // filtre sur type
	$sql .= " AND s.fournisseur = 1";
} elseif ($type == "c") {        // filtre sur type
	$sql .= " AND s.client IN (1, 3)";
} elseif ($type == "p") {        // filtre sur type
	$sql .= " AND s.client IN (2, 3)";
}
if (!empty($socid)) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
// Add order
if ($view == "recent") {
	$sql .= $db->order("p.datec", "DESC");
} else {
	$sql .= $db->order($sortfield, $sortorder);
}

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected = is_array($toselect) ? $toselect : array();

if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && ($sall != '' || $search_cti != '')) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/contact/card.php?id='.$id);
	exit;
}

$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas';
llxHeader('', $title, $help_url);

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&amp;contextpage='.$contextpage;
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&amp;limit='.$limit;
}
$param .= '&amp;begin='.urlencode($begin).'&amp;userid='.urlencode($userid).'&amp;contactname='.urlencode($sall);
$param .= '&amp;type='.urlencode($type).'&amp;view='.urlencode($view);
if (!empty($search_categ) && $search_categ != '-1') {
	$param .= '&amp;search_categ='.urlencode($search_categ);
}
if (!empty($search_categ_thirdparty) && $search_categ_thirdparty != '-1') {
	$param .= '&amp;search_categ_thirdparty='.urlencode($search_categ_thirdparty);
}
if (!empty($search_categ_supplier) && $search_categ_supplier != '-1') {
	$param .= '&amp;search_categ_supplier='.urlencode($search_categ_supplier);
}
if ($sall != '') {
	$param .= '&amp;sall='.urlencode($sall);
}
if ($search_id > 0) {
	$param .= "&amp;search_id=".urlencode($search_id);
}
if ($search_lastname != '') {
	$param .= '&amp;search_lastname='.urlencode($search_lastname);
}
if ($search_firstname != '') {
	$param .= '&amp;search_firstname='.urlencode($search_firstname);
}
if ($search_societe != '') {
	$param .= '&amp;search_societe='.urlencode($search_societe);
}
if ($search_zip != '') {
	$param .= '&amp;search_zip='.urlencode($search_zip);
}
if ($search_town != '') {
	$param .= '&amp;search_town='.urlencode($search_town);
}
if ($search_country != '') {
	$param .= "&search_country=".urlencode($search_country);
}
if ($search_poste != '') {
	$param .= '&amp;search_poste='.urlencode($search_poste);
}
if ($search_phone_pro != '') {
	$param .= '&amp;search_phone_pro='.urlencode($search_phone_pro);
}
if ($search_phone_perso != '') {
	$param .= '&amp;search_phone_perso='.urlencode($search_phone_perso);
}
if ($search_phone_mobile != '') {
	$param .= '&amp;search_phone_mobile='.urlencode($search_phone_mobile);
}
if ($search_fax != '') {
	$param .= '&amp;search_fax='.urlencode($search_fax);
}
if ($search_email != '') {
	$param .= '&amp;search_email='.urlencode($search_email);
}
if ($search_no_email != '') {
	$param .= '&amp;search_no_email='.urlencode($search_no_email);
}
if ($search_status != '') {
	$param .= '&amp;search_status='.urlencode($search_status);
}
if ($search_priv == '0' || $search_priv == '1') {
	$param .= "&amp;search_priv=".urlencode($search_priv);
}
if ($search_stcomm != '') {
	$param .= '&search_stcomm='.urlencode($search_stcomm);
}
if (is_array($search_level) && count($search_level)) {
	foreach ($search_level as $slevel) {
		$param .= '&search_level[]='.urlencode($slevel);
	}
}
if ($search_import_key != '') {
	$param .= '&amp;search_import_key='.urlencode($search_import_key);
}
if ($optioncss != '') {
	$param .= '&amp;optioncss='.urlencode($optioncss);
}
if (count($search_roles) > 0) {
	$param .= implode('&search_roles[]=', $search_roles);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
//    'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
//    'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if ($user->rights->societe->supprimer) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if ($user->rights->societe->creer) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if (in_array($massaction, array('presend', 'predelete','preaffecttag'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = dolGetButtonTitle($langs->trans('NewContactAddress'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/contact/card.php?action=create', '', $user->rights->societe->contact->creer);

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';

print_barre_liste($titre, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'address', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "Information";
$modelmail = "contact";
$objecttmp = new Contact($db);
$trackid = 'ctc'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($sall) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
}
if ($search_firstlast_only) {
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_firstlast_only).$langs->trans("Lastname").", ".$langs->trans("Firstname").'</div>';
}

$moreforfilter = '';
if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ContactCategoriesShort');
	$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"');
	$moreforfilter .= $formother->select_categories(Categorie::TYPE_CONTACT, $search_categ, 'search_categ', 1, $tmptitle);
	$moreforfilter .= '</div>';
	if (empty($type) || $type == 'c' || $type == 'p') {
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = '';
		if ($type == 'c') {
			$tmptitle .= $langs->trans('CustomersCategoriesShort');
		} elseif ($type == 'p') {
			$tmptitle .= $langs->trans('ProspectsCategoriesShort');
		} else {
			$tmptitle .= $langs->trans('CustomersProspectsCategoriesShort');
		}
		$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"');
		$moreforfilter .= $formother->select_categories(Categorie::TYPE_CUSTOMER, $search_categ_thirdparty, 'search_categ_thirdparty', 1, $tmptitle);
		$moreforfilter .= '</div>';
	}

	if (!empty($conf->fournisseur->enabled) && (empty($type) || $type == 'f')) {
		$moreforfilter .= '<div class="divsearchfield">';
		$tmptitle = $langs->trans('SuppliersCategoriesShort');
		$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"');
		$moreforfilter .= $formother->select_categories(Categorie::TYPE_SUPPLIER, $search_categ_supplier, 'search_categ_supplier', 1, $tmptitle);
		$moreforfilter .= '</div>';
	}
}

$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('Roles').': ';
$moreforfilter .= $formcompany->showRoles("search_roles", $objecttmp, 'edit', $search_roles);
$moreforfilter .= '</div>';

print '<div class="liste_titre liste_titre_bydiv centpercent">';
print $moreforfilter;
$parameters = array('type'=>$type);
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print '</div>';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
if ($massactionbutton) {
	$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
}

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Lines for filter fields
print '<tr class="liste_titre_filter">';
if (!empty($arrayfields['p.rowid']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat searchstring" type="text" name="search_id" size="1" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}
if (!empty($arrayfields['p.lastname']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_lastname" size="6" value="'.dol_escape_htmltag($search_lastname).'">';
	print '</td>';
}
if (!empty($arrayfields['p.firstname']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_firstname" size="6" value="'.dol_escape_htmltag($search_firstname).'">';
	print '</td>';
}
if (!empty($arrayfields['p.poste']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_poste" size="5" value="'.dol_escape_htmltag($search_poste).'">';
	print '</td>';
}
if (!empty($arrayfields['p.zip']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_zip" size="3" value="'.dol_escape_htmltag($search_zip).'">';
	print '</td>';
}
if (!empty($arrayfields['p.town']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_town" size="5" value="'.dol_escape_htmltag($search_town).'">';
	print '</td>';
}
// State
/*if (! empty($arrayfields['state.nom']['checked']))
 {
 print '<td class="liste_titre">';
 print '<input class="flat searchstring" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
 print '</td>';
 }
 // Region
 if (! empty($arrayfields['region.nom']['checked']))
 {
 print '<td class="liste_titre">';
 print '<input class="flat searchstring" size="4" type="text" name="search_region" value="'.dol_escape_htmltag($search_region).'">';
 print '</td>';
 }*/
// Country
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->select_country($search_country, 'search_country', '', 0, 'minwidth100imp maxwidth100');
	print '</td>';
}
if (!empty($arrayfields['p.phone']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_phone_pro" size="6" value="'.dol_escape_htmltag($search_phone_pro).'">';
	print '</td>';
}
if (!empty($arrayfields['p.phone_perso']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_phone_perso" size="6" value="'.dol_escape_htmltag($search_phone_perso).'">';
	print '</td>';
}
if (!empty($arrayfields['p.phone_mobile']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_phone_mobile" size="6" value="'.dol_escape_htmltag($search_phone_mobile).'">';
	print '</td>';
}
if (!empty($arrayfields['p.fax']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_fax" size="6" value="'.dol_escape_htmltag($search_fax).'">';
	print '</td>';
}
if (!empty($arrayfields['p.email']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_email" size="6" value="'.dol_escape_htmltag($search_email).'">';
	print '</td>';
}
if (!empty($arrayfields['unsubscribed']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->selectarray('search_no_email', array('-1'=>'', '0'=>$langs->trans('No'), '1'=>$langs->trans('Yes')), $search_no_email);
	print '</td>';
}
if (!empty($conf->socialnetworks->enabled)) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active']) {
			if (!empty($arrayfields['p.'.$key]['checked'])) {
				print '<td class="liste_titre">';
				print '<input class="flat" type="text" name="search_'.$key.'" size="6" value="'.dol_escape_htmltag($search_[$key]).'">';
				print '</td>';
			}
		}
	}
}
if (!empty($arrayfields['p.fk_soc']['checked']) || !empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_societe" size="8" value="'.dol_escape_htmltag($search_societe).'">';
	print '</td>';
}
if (!empty($arrayfields['p.priv']['checked'])) {
	print '<td class="liste_titre center">';
	$selectarray = array('0'=>$langs->trans("ContactPublic"), '1'=>$langs->trans("ContactPrivate"));
	print $form->selectarray('search_priv', $selectarray, $search_priv, 1);
	print '</td>';
}
// Prospect level
if (!empty($arrayfields['p.fk_prospectcontactlevel']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->multiselectarray('search_level', $tab_level, $search_level, 0, 0, 'width75', 0, 0, '', '', '', 2);
	print '</td>';
}
// Prospect status
if (!empty($arrayfields['p.fk_stcommcontact']['checked'])) {
	print '<td class="liste_titre maxwidthonsmartphone center">';
	$arraystcomm = array();
	foreach ($contactstatic->cacheprospectstatus as $key => $val) {
		$arraystcomm[$val['id']] = ($langs->trans("StatusProspect".$val['id']) != "StatusProspect".$val['id'] ? $langs->trans("StatusProspect".$val['id']) : $val['label']);
	}
	print $form->selectarray('search_stcomm', $arraystcomm, $search_stcomm, -2, 0, 0, '', 0, 0, 0, '', 'nowrap ');
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['p.datec']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Date modification
if (!empty($arrayfields['p.tms']['checked'])) {
	print '<td class="liste_titre">';
	print '</td>';
}
// Status
if (!empty($arrayfields['p.statut']['checked'])) {
	print '<td class="liste_titre center">';
	print $form->selectarray('search_status', array('-1'=>'', '0'=>$langs->trans('ActivityCeased'), '1'=>$langs->trans('InActivity')), $search_status);
	print '</td>';
}
if (!empty($arrayfields['p.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring" type="text" name="search_import_key" size="3" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';

print '</tr>';

// Ligne des titres
print '<tr class="liste_titre">';
if (!empty($arrayfields['p.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['p.rowid']['label'], $_SERVER["PHP_SELF"], "p.rowid", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['p.lastname']['checked'])) {
	print_liste_field_titre($arrayfields['p.lastname']['label'], $_SERVER["PHP_SELF"], "p.lastname", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.firstname']['checked'])) {
	print_liste_field_titre($arrayfields['p.firstname']['label'], $_SERVER["PHP_SELF"], "p.firstname", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.poste']['checked'])) {
	print_liste_field_titre($arrayfields['p.poste']['label'], $_SERVER["PHP_SELF"], "p.poste", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.zip']['checked'])) {
	print_liste_field_titre($arrayfields['p.zip']['label'], $_SERVER["PHP_SELF"], "p.zip", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.town']['checked'])) {
	print_liste_field_titre($arrayfields['p.town']['label'], $_SERVER["PHP_SELF"], "p.town", $begin, $param, '', $sortfield, $sortorder);
}
//if (! empty($arrayfields['state.nom']['checked']))           print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
//if (! empty($arrayfields['region.nom']['checked']))          print_liste_field_titre($arrayfields['region.nom']['label'],$_SERVER["PHP_SELF"],"region.nom","",$param,'',$sortfield,$sortorder);
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "co.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['p.phone']['checked'])) {
	print_liste_field_titre($arrayfields['p.phone']['label'], $_SERVER["PHP_SELF"], "p.phone", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.phone_perso']['checked'])) {
	print_liste_field_titre($arrayfields['p.phone_perso']['label'], $_SERVER["PHP_SELF"], "p.phone_perso", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.phone_mobile']['checked'])) {
	print_liste_field_titre($arrayfields['p.phone_mobile']['label'], $_SERVER["PHP_SELF"], "p.phone_mobile", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.fax']['checked'])) {
	print_liste_field_titre($arrayfields['p.fax']['label'], $_SERVER["PHP_SELF"], "p.fax", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.email']['checked'])) {
	print_liste_field_titre($arrayfields['p.email']['label'], $_SERVER["PHP_SELF"], "p.email", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['unsubscribed']['checked'])) {
	print_liste_field_titre($arrayfields['unsubscribed']['label'], $_SERVER["PHP_SELF"], "unsubscribed", $begin, $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($conf->socialnetworks->enabled)) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active'] && !empty($arrayfields['p.'.$key]['checked'])) {
			print_liste_field_titre($arrayfields['p.'.$key]['label'], $_SERVER["PHP_SELF"], "p.".$key, $begin, $param, '', $sortfield, $sortorder);
		}
	}
}
if (!empty($arrayfields['p.fk_soc']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_soc']['label'], $_SERVER["PHP_SELF"], "p.fk_soc", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", $begin, $param, '', $sortfield, $sortorder);
}
if (!empty($arrayfields['p.priv']['checked'])) {
	print_liste_field_titre($arrayfields['p.priv']['label'], $_SERVER["PHP_SELF"], "p.priv", $begin, $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['p.fk_prospectcontactlevel']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_prospectcontactlevel']['label'], $_SERVER["PHP_SELF"], "p.fk_prospectcontactlevel", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['p.fk_stcommcontact']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_stcommcontact']['label'], $_SERVER["PHP_SELF"], "p.fk_stcommcontact", "", $param, '', $sortfield, $sortorder, 'center ');
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array(
	'arrayfields'=>$arrayfields,
	'param'=>$param,
	'sortfield'=>$sortfield,
	'sortorder'=>$sortorder,
);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['p.datec']['checked'])) {
	print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['p.tms']['checked'])) {
	print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
}
if (!empty($arrayfields['p.statut']['checked'])) {
	print_liste_field_titre($arrayfields['p.statut']['label'], $_SERVER["PHP_SELF"], "p.statut", "", $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['p.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['p.import_key']['label'], $_SERVER["PHP_SELF"], "p.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
}
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";


$i = 0;
$totalarray = array();
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$arraysocialnetworks = (array) json_decode($obj->socialnetworks, true);
	$contactstatic->lastname = $obj->lastname;
	$contactstatic->firstname = '';
	$contactstatic->id = $obj->rowid;
	$contactstatic->statut = $obj->statut;
	$contactstatic->poste = $obj->poste;
	$contactstatic->email = $obj->email;
	$contactstatic->phone_pro = $obj->phone_pro;
	$contactstatic->phone_perso = $obj->phone_perso;
	$contactstatic->phone_mobile = $obj->phone_mobile;
	$contactstatic->zip = $obj->zip;
	$contactstatic->town = $obj->town;
	$contactstatic->socialnetworks = $arraysocialnetworks;
	$contactstatic->country = $obj->country;
	$contactstatic->country_code = $obj->country_code;
	$contactstatic->photo = $obj->photo;

	$contactstatic->fk_prospectlevel = $obj->fk_prospectcontactlevel;

	print '<tr class="oddeven">';

	// ID
	if (!empty($arrayfields['p.rowid']['checked'])) {
		print '<td class="tdoverflowmax50">';
		print $obj->rowid;
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Name
	if (!empty($arrayfields['p.lastname']['checked'])) {
		print '<td class="middle tdoverflowmax200">';
		print $contactstatic->getNomUrl(1);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Firstname
	if (!empty($arrayfields['p.firstname']['checked'])) {
		print '<td class="tdoverflowmax200">'.$obj->firstname.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Job position
	if (!empty($arrayfields['p.poste']['checked'])) {
		print '<td class="tdoverflowmax100">'.$obj->poste.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Zip
	if (!empty($arrayfields['p.zip']['checked'])) {
		print '<td>'.$obj->zip.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Town
	if (!empty($arrayfields['p.town']['checked'])) {
		print '<td>'.$obj->town.'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// State
	/*if (! empty($arrayfields['state.nom']['checked']))
	{
		print "<td>".$obj->state_name."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}
	// Region
	if (! empty($arrayfields['region.nom']['checked']))
	{
		print "<td>".$obj->region_name."</td>\n";
		if (! $i) $totalarray['nbfield']++;
	}*/
	// Country
	if (!empty($arrayfields['country.code_iso']['checked'])) {
		print '<td class="center">';
		$tmparray = getCountry($obj->fk_pays, 'all');
		print $tmparray['label'];
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Phone
	if (!empty($arrayfields['p.phone']['checked'])) {
		print '<td class="nowraponall tdoverflowmax150">'.dol_print_phone($obj->phone_pro, $obj->country_code, $obj->rowid, $obj->socid, 'AC_TEL', ' ', 'phone').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Phone perso
	if (!empty($arrayfields['p.phone_perso']['checked'])) {
		print '<td class="nowraponall tdoverflowmax150">'.dol_print_phone($obj->phone_perso, $obj->country_code, $obj->rowid, $obj->socid, 'AC_TEL', ' ', 'phone').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Phone mobile
	if (!empty($arrayfields['p.phone_mobile']['checked'])) {
		print '<td class="nowraponall tdoverflowmax150">'.dol_print_phone($obj->phone_mobile, $obj->country_code, $obj->rowid, $obj->socid, 'AC_TEL', ' ', 'mobile').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Fax
	if (!empty($arrayfields['p.fax']['checked'])) {
		print '<td class="nowraponall tdoverflowmax150">'.dol_print_phone($obj->fax, $obj->country_code, $obj->rowid, $obj->socid, 'AC_TEL', ' ', 'fax').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// EMail
	if (!empty($arrayfields['p.email']['checked'])) {
		print '<td class="nowraponall tdmaxoverflow300">'.dol_print_email($obj->email, $obj->rowid, $obj->socid, 'AC_EMAIL', 18, 0, 1).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// No EMail
	if (!empty($arrayfields['unsubscribed']['checked'])) {
		print '<td class="center">';
		if (empty($obj->email)) {
			//print '<span class="opacitymedium">'.$langs->trans("NoEmail").'</span>';
		} else {
			print yn(($obj->unsubscribed > 0) ? 1 : 0);
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($conf->socialnetworks->enabled)) {
		foreach ($socialnetworks as $key => $value) {
			if ($value['active'] && !empty($arrayfields['p.'.$key]['checked'])) {
				print '<td>'.dol_print_socialnetworks($arraysocialnetworks[$key], $obj->rowid, $obj->socid, $key, $socialnetworks).'</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
		}
	}
	// Company
	if (!empty($arrayfields['p.fk_soc']['checked']) || !empty($arrayfields['s.nom']['checked'])) {
		print '<td class="tdoverflowmax200">';
		if ($obj->socid) {
			$objsoc = new Societe($db);
			$objsoc->fetch($obj->socid);
			print $objsoc->getNomUrl(1);
		} else {
			print '&nbsp;';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Private/Public
	if (!empty($arrayfields['p.priv']['checked'])) {
		print '<td class="center">'.$contactstatic->LibPubPriv($obj->priv).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['p.fk_prospectcontactlevel']['checked'])) {
		// Prospect level
		print '<td class="center">';
		print $contactstatic->getLibProspLevel();
		print "</td>";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['p.fk_stcommcontact']['checked'])) {
		// Prospect status
		print '<td class="center nowrap"><div class="nowrap">';
		print '<div class="inline-block">'.$contactstatic->libProspCommStatut($obj->stcomm_id, 2, $contactstatic->cacheprospectstatus[$obj->stcomm_id]['label'], $obj->stcomm_picto);
		print '</div> - <div class="inline-block">';
		foreach ($contactstatic->cacheprospectstatus as $key => $val) {
			$titlealt = 'default';
			if (!empty($val['code']) && !in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) {
				$titlealt = $val['label'];
			}
			if ($obj->stcomm_id != $val['id']) {
				print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?stcommcontactid='.$obj->rowid.'&stcomm='.$val['code'].'&action=setstcomm&token='.newToken().$param.($page ? '&page='.urlencode($page) : '').'">'.img_action($titlealt, $val['code'], $val['picto']).'</a>';
			}
		}
		print '</div></div></td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['p.datec']['checked'])) {
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Date modification
	if (!empty($arrayfields['p.tms']['checked'])) {
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Status
	if (!empty($arrayfields['p.statut']['checked'])) {
		print '<td class="center">'.$contactstatic->getLibStatut(5).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['p.import_key']['checked'])) {
		print '<td class="tdoverflowmax100">';
		print $obj->import_key;
		print "</td>\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Action column
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

	print "</tr>\n";
	$i++;
}

$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print "</div>";

//if ($num > $limit || $page) print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_companies.png', 0, '', '', $limit, 1);

print '</form>';


llxFooter();
$db->close();
