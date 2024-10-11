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
 * Copyright (C) 2019       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2019       Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2020       Open-Dsi      			<support@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Benjamin Falière		<benjamin.faliere@altairis.fr>
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


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "suppliers", "categories"));

$socialnetworks = getArrayOfSocialNetworks();

// Get parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'contactlist';
$mode = GETPOST('mode', 'alpha');

if ($contextpage == 'poslist') {
	$optioncss = 'print';
}

// Security check
$id = GETPOSTINT('id');
$contactid = GETPOSTINT('id');
$ref = ''; // There is no ref for contacts
if ($user->socid > 0) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'contact', $contactid, '');

$search_all = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_cti = preg_replace('/^0+/', '', preg_replace('/[^0-9]/', '', GETPOST('search_cti', 'alphanohtml'))); // Phone number without any special chars
$search_phone = GETPOST("search_phone", 'alpha');

$search_id = GETPOST("search_id", "intcomma");
$search_firstlast_only = GETPOST("search_firstlast_only", 'alpha');
$search_lastname = GETPOST("search_lastname", 'alpha');
$search_firstname = GETPOST("search_firstname", 'alpha');
$search_societe = GETPOST("search_societe", 'alpha');
$search_societe_alias = GETPOST("search_societe_alias", 'alpha');
$search_poste = GETPOST("search_poste", 'alpha');
$search_phone_perso = GETPOST("search_phone_perso", 'alpha');
$search_phone_pro = GETPOST("search_phone_pro", 'alpha');
$search_phone_mobile = GETPOST("search_phone_mobile", 'alpha');
$search_fax = GETPOST("search_fax", 'alpha');
$search_email = GETPOST("search_email", 'alpha');
if (isModEnabled('mailing')) {
	$search_no_email = GETPOSTISSET("search_no_email") ? GETPOSTINT("search_no_email") : -1;
} else {
	$search_no_email = -1;
}

$search_ = array();

if (isModEnabled('socialnetworks')) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active']) {
			$search_[$key] = GETPOST("search_".$key, 'alpha');
		}
	}
}
$search_priv = GETPOST("search_priv", 'alpha');
$search_sale = GETPOST('search_sale', 'intcomma');
$search_categ = GETPOST("search_categ", 'intcomma');
$search_categ_thirdparty = GETPOST("search_categ_thirdparty", 'intcomma');
$search_categ_supplier = GETPOST("search_categ_supplier", 'intcomma');
$search_status = GETPOST("search_status", "intcomma");
$search_type = GETPOST('search_type', 'alpha');
$search_address = GETPOST('search_address', 'alpha');
$search_zip = GETPOST('search_zip', 'alpha');
$search_town = GETPOST('search_town', 'alpha');
$search_import_key = GETPOST("search_import_key", 'alpha');
$search_country = GETPOST("search_country", 'aZ09');
$search_roles = GETPOST("search_roles", 'array');
$search_level = GETPOST("search_level", 'array');
$search_stcomm = GETPOST('search_stcomm', 'intcomma');
$search_birthday_start = dol_mktime(0, 0, 0, GETPOSTINT('search_birthday_startmonth'), GETPOSTINT('search_birthday_startday'), GETPOSTINT('search_birthday_startyear'));
$search_birthday_end = dol_mktime(23, 59, 59, GETPOSTINT('search_birthday_endmonth'), GETPOSTINT('search_birthday_endday'), GETPOSTINT('search_birthday_endyear'));

if ($search_status === '') {
	$search_status = 1; // always display active customer first
}
if ($search_no_email === '') {
	$search_no_email = -1;
}

$optioncss = GETPOST('optioncss', 'alpha');

$place = GETPOST('place', 'aZ09') ? GETPOST('place', 'aZ09') : '0'; // $place is string id of table for Bar or Restaurant

$type = GETPOST("type", 'aZ');
$view = GETPOST("view", 'alpha');

$userid = GETPOSTINT('userid');
$begin = GETPOST('begin');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "p.lastname";
}
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$title = (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses"));
if ($type == "p") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactprospectlist';
	}
	$title .= '  ('.$langs->trans("ThirdPartyProspects").')';
	$urlfiche = "card.php";
}
if ($type == "c") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactcustomerlist';
	}
	$title .= '  ('.$langs->trans("ThirdPartyCustomers").')';
	$urlfiche = "card.php";
} elseif ($type == "f") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactsupplierlist';
	}
	$title .= ' ('.$langs->trans("ThirdPartySuppliers").')';
	$urlfiche = "card.php";
} elseif ($type == "o") {
	if (empty($contextpage) || $contextpage == 'contactlist') {
		$contextpage = 'contactotherlist';
	}
	$title .= ' ('.$langs->trans("OthersNotLinkedToThirdParty").')';
	$urlfiche = "";
}

// Initialize technical object
$object = new Contact($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array($contextpage));

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
if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
	$fieldstosearchall['s.nom'] = "ThirdParty";
	$fieldstosearchall['s.name_alias'] = "AliasNames";
}

$parameters = array('fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('completeFieldsToSearchAll', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook > 0) {
	$fieldstosearchall = empty($hookmanager->resArray['fieldstosearchall']) ? array() : $hookmanager->resArray['fieldstosearchall'];
} elseif ($reshook == 0) {
	$fieldstosearchall = array_merge($fieldstosearchall, empty($hookmanager->resArray['fieldstosearchall']) ? array() : $hookmanager->resArray['fieldstosearchall']);
}

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1);
		$arrayfields['p.'.$key] = array(
			'label' => $val['label'],
			'checked' => (($visible < 0) ? 0 : 1),
			'enabled' => (abs($visible) != 3 && (bool) dol_eval($val['enabled'], 1)),
			'position' => $val['position'],
			'help' => isset($val['help']) ? $val['help'] : ''
		);
	}
}

// Add none object fields to fields for list
$arrayfields['country.code_iso'] = array('label' => "Country", 'position' => 66, 'checked' => 0);
if (!getDolGlobalString('SOCIETE_DISABLE_CONTACTS')) {
	$arrayfields['s.nom'] = array('label' => "ThirdParty", 'position' => 113, 'checked' => 1);
	$arrayfields['s.name_alias'] = array('label' => "AliasNameShort", 'position' => 114, 'checked' => 1);
}

$arrayfields['unsubscribed'] = array(
		'label' => 'No_Email',
		'checked' => 0,
		'enabled' => (isModEnabled('mailing')),
		'position' => 111);

if (isModEnabled('socialnetworks')) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active']) {
			$arrayfields['p.'.$key] = array(
				'label' => $value['label'],
				'checked' => 0,
				'position' => 299
			);
		}
	}
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
//$arrayfields['anotherfield'] = array('type'=>'integer', 'label'=>'AnotherField', 'checked'=>1, 'enabled'=>1, 'position'=>90, 'csslist'=>'right');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan


if (($id > 0 || !empty($ref)) && $action != 'add') {
	$result = $object->fetch($id, $ref);
	if ($result < 0) {
		dol_print_error($db);
	}
}

$permissiontoread = $user->hasRight('societe', 'contact', 'lire');
$permissiontodelete = $user->hasRight('societe', 'contact', 'supprimer');
$permissiontoadd = $user->hasRight('societe', 'contact', 'creer');

if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == "change" && $user->hasRight('takepos', 'run')) {	// Change customer for TakePOS
	$idcustomer = GETPOSTINT('idcustomer');
	$idcontact = GETPOSTINT('idcontact');

	// Check if draft invoice already exists, if not create it
	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' AND entity IN (".getEntity('invoice').")";
	$result = $db->query($sql);
	$num_lines = $db->num_rows($result);
	if ($num_lines == 0) {
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$invoice = new Facture($db);
		$constforthirdpartyid = 'CASHDESK_ID_THIRDPARTY'.$_SESSION["takeposterminal"];
		$invoice->socid = getDolGlobalInt($constforthirdpartyid);
		$invoice->date = dol_now();
		$invoice->module_source = 'takepos';
		$invoice->pos_source = $_SESSION["takeposterminal"];
		$placeid = $invoice->create($user);
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture set ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")' where rowid = ".((int) $placeid);
		$db->query($sql);
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."facture set fk_soc=".((int) $idcustomer)." where ref='(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")'";
	$resql = $db->query($sql);

	// set contact on invoice
	if (!isset($invoice)) {
		require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		$invoice = new Facture($db);
		$invoice->fetch(null, "(PROV-POS".$_SESSION["takeposterminal"]."-".$place.")");
		$invoice->delete_linked_contact('external', 'BILLING');
	}
	$invoice->add_contact($idcontact, 'BILLING'); ?>
		<script>
		console.log("Reload page invoice.php with place=<?php print $place; ?>");
		parent.$("#poslines").load("invoice.php?place=<?php print $place; ?>", function() {
			//parent.$("#poslines").scrollTop(parent.$("#poslines")[0].scrollHeight);
			<?php if (!$resql) { ?>
				alert('Error failed to update customer on draft invoice.');
			<?php } ?>
			parent.$("#idcustomer").val(<?php echo $idcustomer; ?>);
			parent.$.colorbox.close(); /* Close the popup */
		});
		</script>
	<?php
	exit;
}

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {	// All tests are required to be compatible with all browsers
		$search_all = "";
		$search_id = '';
		$search_firstlast_only = "";
		$search_lastname = "";
		$search_firstname = "";
		$search_societe = "";
		$search_societe_alias = "";
		$search_town = "";
		$search_address = "";
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
		if (isModEnabled('socialnetworks')) {
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
		$search_sale = '';
		$search_categ = '';
		$search_categ_thirdparty = '';
		$search_categ_supplier = '';
		$search_import_key = '';
		$toselect = array();
		$search_array_options = array();
		$search_roles = array();
		$search_birthday_start = '';
		$search_birthday_end = '';
	}

	// Mass actions
	$objectclass = 'Contact';
	$objectlabel = 'Contact';
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

// The user has no rights to see other third-party than their own
if (!$user->hasRight('societe', 'client', 'voir')) {
	$socid = $user->socid;
}


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);

$now = dol_now();

$title = $langs->trans("Contacts")." - ".$langs->trans("List");
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Empresas';
$morejs = array();
$morecss = array();

if (getDolGlobalString('THIRDPARTY_ENABLE_PROSPECTION_ON_ALTERNATIVE_ADRESSES')) {
	$contactstatic->loadCacheOfProspStatus();
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

// Select every potentials, and note each potentials which fit in search parameters
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

// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT s.rowid as socid, s.nom as name, s.name_alias as alias,";
$sql .= " p.rowid, p.lastname as lastname, p.statut, p.firstname, p.address, p.zip, p.town, p.poste, p.email, p.birthday,";
$sql .= " p.socialnetworks, p.photo,";
$sql .= " p.phone as phone_pro, p.phone_mobile, p.phone_perso, p.fax, p.fk_pays, p.priv, p.datec as date_creation, p.tms as date_modification,";
$sql .= " p.import_key, p.fk_stcommcontact as stcomm_id, p.fk_prospectlevel,";
$sql .= " st.libelle as stcomm, st.picto as stcomm_picto,";
$sql .= " co.label as country, co.code as country_code";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
if (isModEnabled('mailing')) {
	$sql .= ", (SELECT count(*) FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = p.email) as unsubscribed";
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (p.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON co.rowid = p.fk_pays";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_stcommcontact as st ON st.id = p.fk_stcommcontact";

// Add fields from hooks - ListFrom
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' WHERE p.entity IN ('.getEntity('contact').')';
if (!empty($userid)) {    // propre au commercial
	$sql .= " AND p.fk_user_creat=".((int) $userid);
}
if ($search_level) {
	$sql .= natural_search("p.fk_prospectlevel", implode(',', $search_level), 3);
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
// Search on sale representative
if (!empty($search_sale) && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".$db->prefix()."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".$db->prefix()."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}

// Search Contact Categories
$searchCategoryContactList = $search_categ ? array($search_categ) : array();
$searchCategoryContactOperator = 0;
// Search for tag/category ($searchCategoryContactList is an array of ID)
if (!empty($searchCategoryContactList)) {
	$searchCategoryContactSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryContactList as $searchCategoryContact) {
		if (intval($searchCategoryContact) == -2) {
			$searchCategoryContactSqlList[] = "NOT EXISTS (SELECT ck.fk_socpeople FROM ".MAIN_DB_PREFIX."categorie_contact as ck WHERE p.rowid = ck.fk_socpeople)";
		} elseif (intval($searchCategoryContact) > 0) {
			if ($searchCategoryContactOperator == 0) {
				$searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_socpeople FROM ".MAIN_DB_PREFIX."categorie_contact as ck WHERE p.rowid = ck.fk_socpeople AND ck.fk_categorie = ".((int) $searchCategoryContact).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryContact);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryContactSqlList[] = " EXISTS (SELECT ck.fk_socpeople FROM ".MAIN_DB_PREFIX."categorie_contact as ck WHERE p.rowid = ck.fk_socpeople AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
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

// Search Customer Categories
$searchCategoryCustomerList = $search_categ_thirdparty ? array($search_categ_thirdparty) : array();
$searchCategoryCustomerOperator = 0;
// Search for tag/category ($searchCategoryCustomerList is an array of ID)
if (!empty($searchCategoryCustomerList)) {
	$searchCategoryCustomerSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$searchCategoryCustomerSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategoryCustomer) > 0) {
			if ($searchCategoryCustomerOperator == 0) {
				$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategoryCustomer).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryCustomer);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryCustomerSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_societe as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryCustomerOperator == 1) {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
		}
	} else {
		if (!empty($searchCategoryCustomerSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
		}
	}
}

// Search Supplier Categories
$searchCategorySupplierList = $search_categ_supplier ? array($search_categ_supplier) : array();
$searchCategorySupplierOperator = 0;
// Search for tag/category ($searchCategorySupplierList is an array of ID)
if (!empty($searchCategorySupplierList)) {
	$searchCategorySupplierSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategorySupplierList as $searchCategorySupplier) {
		if (intval($searchCategorySupplier) == -2) {
			$searchCategorySupplierSqlList[] = "NOT EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc)";
		} elseif (intval($searchCategorySupplier) > 0) {
			if ($searchCategorySupplierOperator == 0) {
				$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie = ".((int) $searchCategorySupplier).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategorySupplier);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategorySupplierSqlList[] = " EXISTS (SELECT ck.fk_soc FROM ".MAIN_DB_PREFIX."categorie_fournisseur as ck WHERE s.rowid = ck.fk_soc AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategorySupplierOperator == 1) {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategorySupplierSqlList).")";
		}
	} else {
		if (!empty($searchCategorySupplierSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategorySupplierSqlList).")";
		}
	}
}

if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
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
if (empty($arrayfields['s.name_alias']['checked']) && $search_societe) {
	$sql .= natural_search(array("s.nom", "s.name_alias"), $search_societe);
} else {
	if ($search_societe) {
		$sql .= natural_search('s.nom', $search_societe);
	}
	if ($search_societe_alias) {
		$sql .= natural_search('s.name_alias', $search_societe_alias);
	}
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
if (isModEnabled('socialnetworks')) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active'] && strlen($search_[$key])) {
			$searchkeyinjsonformat = preg_replace('/"$/', '', preg_replace('/^"/', '', json_encode($search_[$key])));
			if (in_array($db->type, array('mysql', 'mysqli'))) {
				$sql .= " AND p.socialnetworks REGEXP '\"".$db->escape($db->escapeforlike($key))."\":\"[^\"]*".$db->escape($db->escapeforlike($searchkeyinjsonformat))."'";
			} elseif ($db->type == 'pgsql') {
				$sql .= " AND p.socialnetworks ~ '\"".$db->escape($db->escapeforlike($key))."\":\"[^\"]*".$db->escape($db->escapeforlike($searchkeyinjsonformat))."'";
			} else {
				// Works with all database but not reliable because search only for social network code starting with earched value
				$sql .= " AND p.socialnetworks LIKE '%\"".$db->escape($db->escapeforlike($key))."\":\"".$db->escape($db->escapeforlike($searchkeyinjsonformat))."%'";
			}
		}
	}
}
//print $sql;

if (strlen($search_email)) {
	$sql .= natural_search('p.email', $search_email);
}
if (strlen($search_address)) {
	$sql .= natural_search("p.address", $search_address);
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
if ($type == "o") {        // filter on type
	$sql .= " AND p.fk_soc IS NULL";
} elseif ($type == "f") {        // filter on type
	$sql .= " AND s.fournisseur = 1";
} elseif ($type == "c") {        // filter on type
	$sql .= " AND s.client IN (1, 3)";
} elseif ($type == "p") {        // filter on type
	$sql .= " AND s.client IN (2, 3)";
}
if (!empty($socid)) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
if ($search_birthday_start) {
	$sql .= " AND p.birthday >= '".$db->idate($search_birthday_start)."'";
}
if ($search_birthday_end) {
	$sql .= " AND p.birthday <= '".$db->idate($search_birthday_end)."'";
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
//print $sql;

// Add GroupBy from hooks
$parameters = array('fieldstosearchall' => $fieldstosearchall);
$reshook = $hookmanager->executeHooks('printFieldListGroupBy', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
//print $sql;

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

// Complete request and execute it with limit
if ($view == "recent") {
	$sql .= $db->order("p.datec", "DESC");
} else {
	$sql .= $db->order($sortfield, $sortorder);
}
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
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && ($search_all != '' || $search_cti != '') && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".DOL_URL_ROOT.'/contact/card.php?id='.$id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');

$arrayofselected = is_array($toselect) ? $toselect : array();

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
$param .= '&begin='.urlencode((string) ($begin)).'&userid='.urlencode((string) ($userid)).'&contactname='.urlencode((string) ($search_all));
$param .= '&type='.urlencode($type).'&view='.urlencode($view);
if (!empty($search_sale) && $search_sale != '-1') {
	$param .= '&search_sale='.urlencode($search_sale);
}
if (!empty($search_categ) && $search_categ != '-1') {
	$param .= '&search_categ='.urlencode((string) ($search_categ));
}
if (!empty($search_categ_thirdparty) && $search_categ_thirdparty != '-1') {
	$param .= '&search_categ_thirdparty='.urlencode((string) ($search_categ_thirdparty));
}
if (!empty($search_categ_supplier) && $search_categ_supplier != '-1') {
	$param .= '&search_categ_supplier='.urlencode((string) ($search_categ_supplier));
}
if ($search_all != '') {
	$param .= '&search_all='.urlencode($search_all);
}
if ($search_id > 0) {
	$param .= "&search_id=".urlencode((string) ($search_id));
}
if ($search_lastname != '') {
	$param .= '&search_lastname='.urlencode($search_lastname);
}
if ($search_firstname != '') {
	$param .= '&search_firstname='.urlencode($search_firstname);
}
if ($search_societe != '') {
	$param .= '&search_societe='.urlencode($search_societe);
}
if ($search_societe_alias != '') {
	$param .= '&search_societe_alias='.urlencode($search_societe_alias);
}
if ($search_address != '') {
	$param .= '&search_address='.urlencode($search_address);
}
if ($search_zip != '') {
	$param .= '&search_zip='.urlencode($search_zip);
}
if ($search_town != '') {
	$param .= '&search_town='.urlencode($search_town);
}
if ($search_country != '') {
	$param .= "&search_country=".urlencode($search_country);
}
if ($search_poste != '') {
	$param .= '&search_poste='.urlencode($search_poste);
}
if ($search_phone_pro != '') {
	$param .= '&search_phone_pro='.urlencode($search_phone_pro);
}
if ($search_phone_perso != '') {
	$param .= '&search_phone_perso='.urlencode($search_phone_perso);
}
if ($search_phone_mobile != '') {
	$param .= '&search_phone_mobile='.urlencode($search_phone_mobile);
}
if ($search_fax != '') {
	$param .= '&search_fax='.urlencode($search_fax);
}
if ($search_email != '') {
	$param .= '&search_email='.urlencode($search_email);
}
if ($search_no_email != '') {
	$param .= '&search_no_email='.urlencode((string) ($search_no_email));
}
if ($search_status != '') {
	$param .= '&search_status='.urlencode((string) ($search_status));
}
if ($search_priv == '0' || $search_priv == '1') {
	$param .= "&search_priv=".urlencode($search_priv);
}
if ($search_stcomm != '') {
	$param .= '&search_stcomm='.urlencode((string) ($search_stcomm));
}
if (is_array($search_level) && count($search_level)) {
	foreach ($search_level as $slevel) {
		$param .= '&search_level[]='.urlencode($slevel);
	}
}
if ($search_import_key != '') {
	$param .= '&search_import_key='.urlencode($search_import_key);
}
if (count($search_roles) > 0) {
	$param .= implode('&search_roles[]=', $search_roles);
}
if ($search_birthday_start) {
	$param .= '&search_birthday_start='.urlencode(dol_print_date($search_birthday_start, '%d')).'&search_birthday_startmonth='.urlencode(dol_print_date($search_birthday_start, '%m')).'&search_birthday_startyear='.urlencode(dol_print_date($search_birthday_start, '%Y'));
}
if ($search_birthday_end) {
	$param .= '&search_birthday_end='.urlencode(dol_print_date($search_birthday_end, '%d')).'&search_birthday_endmonth='.urlencode(dol_print_date($search_birthday_end, '%m')).'&search_birthday_endyear='.urlencode(dol_print_date($search_birthday_end, '%Y'));
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	'presend' => img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
//    'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (isModEnabled('category') && $user->hasRight('societe', 'creer')) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete','preaffecttag'))) {
	$arrayofmassactions = array();
}

$massactionbutton = '';
if ($contextpage != 'poslist') {
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'" name="formfilter">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
//print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$newcardbutton  = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
if ($contextpage != 'poslist') {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewContactAddress'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/contact/card.php?action=create', '', $permissiontoadd);
} elseif ($user->hasRight('societe', 'contact', 'creer')) {
	$url = DOL_URL_ROOT . '/contact/card.php?action=create&type=t&contextpage=poslist&optioncss=print&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?token=' . newToken() . 'type=t&contextpage=poslist&nomassaction=1&optioncss=print&place='.$place);
	$label = 'MenuNewCustomer';
	$newcardbutton .= dolGetButtonTitle($langs->trans($label), '', 'fa fa-plus-circle', $url);
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'address', 0, $newcardbutton, '', $limit, 0, 0, 1);

$topicmail = "Information";
$modelmail = "contact";
$objecttmp = new Contact($db);
$trackid = 'ctc'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if CONTACT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}
if ($search_firstlast_only) {
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_firstlast_only).$langs->trans("Lastname").", ".$langs->trans("Firstname").'</div>';
}

$moreforfilter = '';

// If the user can view third-party other than their own
if ($user->hasRight('societe', 'client', 'voir')) {
	$langs->load('commercial');
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth250 widthcentpercentminusx', 1);
	$moreforfilter .= '</div>';
}

if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
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

	if (isModEnabled("fournisseur") && (empty($type) || $type == 'f')) {
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
$parameters = array('type' => $type);
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print '</div>';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$selectedfields .= ((count($arrayofmassactions) && $contextpage != 'poslist') ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch actioncolumn">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
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
if (!empty($arrayfields['p.address']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" name="search_address" size="6" value="'.dol_escape_htmltag($search_address).'">';
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

/*
// State
 if (!empty($arrayfields['state.nom']['checked']))
 {
 print '<td class="liste_titre">';
 print '<input class="flat searchstring" size="4" type="text" name="search_state" value="'.dol_escape_htmltag($search_state).'">';
 print '</td>';
 }

 // Region
 if (!empty($arrayfields['region.nom']['checked']))
 {
 print '<td class="liste_titre">';
 print '<input class="flat searchstring" size="4" type="text" name="search_region" value="'.dol_escape_htmltag($search_region).'">';
 print '</td>';
 }
*/

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
	print $form->selectarray('search_no_email', array('-1' => '', '0' => $langs->trans('No'), '1' => $langs->trans('Yes')), $search_no_email);
	print '</td>';
}
if (isModEnabled('socialnetworks')) {
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
// Alias of ThirdParty
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre" align="left">';
	print '<input class="flat maxwidth100" type="text" name="search_societe_alias" value="'.dol_escape_htmltag($search_societe_alias).'">';
	print '</td>';
}
if (!empty($arrayfields['p.priv']['checked'])) {
	print '<td class="liste_titre center">';
	$selectarray = array('0' => $langs->trans("ContactPublic"), '1' => $langs->trans("ContactPrivate"));
	print $form->selectarray('search_priv', $selectarray, $search_priv, 1);
	print '</td>';
}
// Prospect level
if (!empty($arrayfields['p.fk_prospectlevel']['checked'])) {
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
if (!empty($arrayfields['p.birthday']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_birthday_start ? $search_birthday_start : -1, 'search_birthday_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_birthday_end ? $search_birthday_end : -1, 'search_birthday_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
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
	print '<td class="liste_titre center parentonrightofpage">';
	print $form->selectarray('search_status', array('-1' => '', '0' => $langs->trans('ActivityCeased'), '1' => $langs->trans('InActivity')), $search_status, 0, 0, 0, '', 0, 0, 0, '', 'search_status width100 onrightofpage');
	print '</td>';
}
if (!empty($arrayfields['p.import_key']['checked'])) {
	print '<td class="liste_titre center">';
	print '<input class="flat searchstring" type="text" name="search_import_key" size="3" value="'.dol_escape_htmltag($search_import_key).'">';
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
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.rowid']['checked'])) {
	print_liste_field_titre($arrayfields['p.rowid']['label'], $_SERVER["PHP_SELF"], "p.rowid", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.lastname']['checked'])) {
	print_liste_field_titre($arrayfields['p.lastname']['label'], $_SERVER["PHP_SELF"], "p.lastname", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.firstname']['checked'])) {
	print_liste_field_titre($arrayfields['p.firstname']['label'], $_SERVER["PHP_SELF"], "p.firstname", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.poste']['checked'])) {
	print_liste_field_titre($arrayfields['p.poste']['label'], $_SERVER["PHP_SELF"], "p.poste", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.address']['checked'])) {
	print_liste_field_titre($arrayfields['p.address']['label'], $_SERVER["PHP_SELF"], "p.address", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.zip']['checked'])) {
	print_liste_field_titre($arrayfields['p.zip']['label'], $_SERVER["PHP_SELF"], "p.zip", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.town']['checked'])) {
	print_liste_field_titre($arrayfields['p.town']['label'], $_SERVER["PHP_SELF"], "p.town", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
//if (!empty($arrayfields['state.nom']['checked']))           print_liste_field_titre($arrayfields['state.nom']['label'],$_SERVER["PHP_SELF"],"state.nom","",$param,'',$sortfield,$sortorder);
//if (!empty($arrayfields['region.nom']['checked']))          print_liste_field_titre($arrayfields['region.nom']['label'],$_SERVER["PHP_SELF"],"region.nom","",$param,'',$sortfield,$sortorder);
if (!empty($arrayfields['country.code_iso']['checked'])) {
	print_liste_field_titre($arrayfields['country.code_iso']['label'], $_SERVER["PHP_SELF"], "co.code_iso", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.phone']['checked'])) {
	print_liste_field_titre($arrayfields['p.phone']['label'], $_SERVER["PHP_SELF"], "p.phone", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.phone_perso']['checked'])) {
	print_liste_field_titre($arrayfields['p.phone_perso']['label'], $_SERVER["PHP_SELF"], "p.phone_perso", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.phone_mobile']['checked'])) {
	print_liste_field_titre($arrayfields['p.phone_mobile']['label'], $_SERVER["PHP_SELF"], "p.phone_mobile", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fax']['checked'])) {
	print_liste_field_titre($arrayfields['p.fax']['label'], $_SERVER["PHP_SELF"], "p.fax", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.email']['checked'])) {
	print_liste_field_titre($arrayfields['p.email']['label'], $_SERVER["PHP_SELF"], "p.email", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['unsubscribed']['checked'])) {
	print_liste_field_titre($arrayfields['unsubscribed']['label'], $_SERVER["PHP_SELF"], "unsubscribed", $begin, $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (isModEnabled('socialnetworks')) {
	foreach ($socialnetworks as $key => $value) {
		if ($value['active'] && !empty($arrayfields['p.'.$key]['checked'])) {
			print_liste_field_titre($arrayfields['p.'.$key]['label'], $_SERVER["PHP_SELF"], "p.".$key, $begin, $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
	}
}
if (!empty($arrayfields['p.fk_soc']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_soc']['label'], $_SERVER["PHP_SELF"], "p.fk_soc", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], "s.name_alias", $begin, $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.priv']['checked'])) {
	print_liste_field_titre($arrayfields['p.priv']['label'], $_SERVER["PHP_SELF"], "p.priv", $begin, $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_prospectlevel']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_prospectlevel']['label'], $_SERVER["PHP_SELF"], "p.fk_prospectlevel", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_stcommcontact']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_stcommcontact']['label'], $_SERVER["PHP_SELF"], "p.fk_stcommcontact", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.birthday']['checked'])) {
	print_liste_field_titre($arrayfields['p.birthday']['label'], $_SERVER["PHP_SELF"], "p.birthday", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['p.datec']['checked'])) {
	print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.tms']['checked'])) {
	print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.statut']['checked'])) {
	print_liste_field_titre($arrayfields['p.statut']['label'], $_SERVER["PHP_SELF"], "p.statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['p.import_key']['label'], $_SERVER["PHP_SELF"], "p.import_key", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
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

	$arraysocialnetworks = (array) json_decode($obj->socialnetworks, true);
	$contactstatic->lastname = $obj->lastname;
	$contactstatic->firstname = $obj->firstname;
	$contactstatic->id = $obj->rowid;
	$contactstatic->statut = $obj->statut;
	$contactstatic->poste = $obj->poste;
	$contactstatic->email = $obj->email;
	$contactstatic->phone_pro = $obj->phone_pro;
	$contactstatic->phone_perso = $obj->phone_perso;
	$contactstatic->phone_mobile = $obj->phone_mobile;
	$contactstatic->address = $obj->address;
	$contactstatic->zip = $obj->zip;
	$contactstatic->town = $obj->town;
	$contactstatic->socialnetworks = $arraysocialnetworks;
	$contactstatic->country = $obj->country;
	$contactstatic->country_code = $obj->country_code;
	$contactstatic->photo = $obj->photo;
	$contactstatic->import_key = $obj->import_key;
	$contactstatic->photo = $obj->photo;
	$contactstatic->fk_prospectlevel = $obj->fk_prospectlevel;

	$object = $contactstatic;

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		if ($obj->socid > 0) {
			$contactstatic->fetch_thirdparty($obj->socid);
		}
		print $contactstatic->getKanbanView('', array('selected' => in_array($contactstatic->id, $arrayofselected)));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show here line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven"';
		if ($contextpage == 'poslist') {
			print ' onclick="location.href=\'list.php?action=change&contextpage=poslist&idcustomer='.$obj->socid.'&idcontact='.$obj->rowid.'&place='.urlencode($place).'\'"';
		}
		print '>';

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

		// ID
		if (!empty($arrayfields['p.rowid']['checked'])) {
			print '<td class="tdoverflowmax50">';
			print dol_escape_htmltag($obj->rowid);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// (Last) Name
		if (!empty($arrayfields['p.lastname']['checked'])) {
			print '<td class="middle tdoverflowmax150">';
			if ($contextpage == 'poslist') {
				print $contactstatic->lastname;
			} else {
				print $contactstatic->getNomUrl(1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Firstname
		if (!empty($arrayfields['p.firstname']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->firstname).'">'.dol_escape_htmltag($obj->firstname).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Job position
		if (!empty($arrayfields['p.poste']['checked'])) {
			print '<td class="tdoverflowmax100">'.dol_escape_htmltag($obj->poste).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Address
		if (!empty($arrayfields['p.address']['checked'])) {
			print '<td>'.dol_escape_htmltag($obj->address).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Zip
		if (!empty($arrayfields['p.zip']['checked'])) {
			print '<td>'.dol_escape_htmltag($obj->zip).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Town
		if (!empty($arrayfields['p.town']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->town).'">'.dol_escape_htmltag($obj->town).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		/*
		// State
		if (!empty($arrayfields['state.nom']['checked']))
		{
			print "<td>".$obj->state_name."</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}

		// Region
		if (!empty($arrayfields['region.nom']['checked']))
		{
			print "<td>".$obj->region_name."</td>\n";
			if (! $i) $totalarray['nbfield']++;
		}*/

		// Country
		if (!empty($arrayfields['country.code_iso']['checked'])) {
			print '<td class="center">';
			$tmparray = getCountry($obj->fk_pays, 'all');
			print dol_escape_htmltag($tmparray['label']);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Phone pro
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
			print '<td class="nowraponall tdoverflowmax300">';
			if ($contextpage == 'poslist') {
				print $obj->email;
			} else {
				print dol_print_email($obj->email, $obj->rowid, $obj->socid, 1, 18, 0, 1);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// No EMail Subscription
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

		// Social Networks
		if (isModEnabled('socialnetworks')) {
			foreach ($socialnetworks as $key => $value) {
				if ($value['active'] && !empty($arrayfields['p.'.$key]['checked'])) {
					print '<td class="tdoverflowmax100">'.(empty($arraysocialnetworks[$key]) ? '' : dol_print_socialnetworks($arraysocialnetworks[$key], $obj->rowid, $obj->socid, $key, $socialnetworks)).'</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			}
		}

		// Company / Third Party
		if (!empty($arrayfields['p.fk_soc']['checked']) || !empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax150">';
			if ($obj->socid) {
				$objsoc = new Societe($db);
				$objsoc->fetch($obj->socid);
				$option_link = 'customer';
				if ($objsoc->client == 0 && $objsoc->fournisseur > 0) {
					$option_link = 'supplier';
				} elseif ($objsoc->client == 0 && $objsoc->fournisseur == 0) {
					$option_link = '';
				}
				if ($contextpage == 'poslist') {
					print $objsoc->name;
				} else {
					print $objsoc->getNomUrl(1, $option_link, 100, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
				}
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Alias name
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="tdoverflowmax100" title="'.dol_escape_htmltag($obj->alias).'">';
			print dol_escape_htmltag($obj->alias);
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

		// Prospect Level
		if (!empty($arrayfields['p.fk_prospectlevel']['checked'])) {
			print '<td class="center">';
			print $contactstatic->getLibProspLevel();
			print "</td>";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Prospect status
		if (!empty($arrayfields['p.fk_stcommcontact']['checked'])) {
			print '<td class="center nowrap"><div class="nowrap">';
			print '<div class="inline-block">'.$contactstatic->libProspCommStatut($obj->stcomm_id, 2, $contactstatic->cacheprospectstatus[$obj->stcomm_id]['label'], $obj->stcomm_picto);
			print '</div> - <div class="inline-block">';
			foreach ($contactstatic->cacheprospectstatus as $key => $val) {
				$titlealt = 'default';
				if (!empty($val['code']) && !in_array($val['code'], array('ST_NO', 'ST_NEVER', 'ST_TODO', 'ST_PEND', 'ST_DONE'))) {
					$titlealt = $val['label'];
				}
				if ($obj->stcomm_id != $val['id']) {
					print '<a class="pictosubstatus" href="'.$_SERVER["PHP_SELF"].'?stcommcontactid='.$obj->rowid.'&stcomm='.urlencode((string) ($val['code'])).'&action=setstcomm&token='.newToken().$param.($page ? '&page='.urlencode((string) ($page)) : '').'">'.img_action($titlealt, $val['code'], $val['picto']).'</a>';
				}
			}
			print '</div></div></td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Birthday
		if (!empty($arrayfields['p.birthday']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->birthday), 'day', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date modification
		if (!empty($arrayfields['p.tms']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
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

		// Import key
		if (!empty($arrayfields['p.import_key']['checked'])) {
			print '<td class="tdoverflowmax100">';
			print dol_escape_htmltag($obj->import_key);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
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

// End of page
llxFooter();
$db->close();
