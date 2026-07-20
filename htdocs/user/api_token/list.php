<?php
/* Copyright (C) 2005-2017  Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2015  Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2013	    Florian Henry               <florian.henry@open-concept.pro.com>
 * Copyright (C) 2018       Ferran Marcet               <fmarcet@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/user/api_token/list.php
 *       \brief      Page to show user list of token
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

// Load translation files required by page
$langs->loadLangs(array('admin', 'users'));
$error = 0;

// Security check
$id = GETPOSTINT('id');

if (!isset($id) || empty($id)) {
	accessforbidden();
}

$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight("user", "self", "write")) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// $user is current user, $id is id of edited user
$canreaduser = ($user->admin || ($user->id == $id));
$canedittoken = ($user->admin || (($user->id == $id) && $user->hasRight("user", "self", "write")));

if (!$canreaduser) {
	accessforbidden();
}

// Retrieve needed GETPOSTS for this file
// Action / Massaction
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

// List filters
$search_entity = GETPOST('search_entity', 'alpha');
$search_datec_startday = GETPOSTINT('search_datec_startday');
$search_datec_startmonth = GETPOSTINT('search_datec_startmonth');
$search_datec_startyear = GETPOSTINT('search_datec_startyear');
$search_datec_endday = GETPOSTINT('search_datec_endday');
$search_datec_endmonth = GETPOSTINT('search_datec_endmonth');
$search_datec_endyear = GETPOSTINT('search_datec_endyear');
$search_datec_start = dol_mktime(0, 0, 0, $search_datec_startmonth, $search_datec_startday, $search_datec_startyear);
$search_datec_end = dol_mktime(23, 59, 59, $search_datec_endmonth, $search_datec_endday, $search_datec_endyear);
$search_tms_startday = GETPOSTINT('search_tms_startday');
$search_tms_startmonth = GETPOSTINT('search_tms_startmonth');
$search_tms_startyear = GETPOSTINT('search_tms_startyear');
$search_tms_endday = GETPOSTINT('search_tms_endday');
$search_tms_endmonth = GETPOSTINT('search_tms_endmonth');
$search_tms_endyear = GETPOSTINT('search_tms_endyear');
$search_tms_start = dol_mktime(0, 0, 0, $search_tms_startmonth, $search_tms_startday, $search_tms_startyear);
$search_tms_end = dol_mktime(23, 59, 59, $search_tms_endmonth, $search_tms_endday, $search_tms_endyear);

// Pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
	$sortfield = 'oat.tms';
}
if (!$sortorder) {
	$sortorder = 'DESC';
}

$arrayfields = array(
	'oat.datec' => array('label' => "DateCreation", 'checked' => '1'),
	'oat.tms' => array('label' => "DateModification", 'checked' => '1'),
);

$object = new User($db);
$object->fetch($id, '', '', 1);
$object->loadRights();

// Deny access if user not using api
if (empty($object->api_key)) {
	accessforbidden();
}

$form = new Form($db);


/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_entity = '';
		$search_datec_start = '';
		$search_datec_end = '';
		$search_tms_start = '';
		$search_tms_end = '';

		$toselect = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	if (($action == 'delete' && $confirm == 'yes') && $canedittoken) {
		$db->begin();

		$nbok = 0;

		// $toselect could contain duplicate entries, cf https://github.com/Dolibarr/dolibarr/issues/26244
		$unique_arr = array_unique($toselect);
		foreach ($unique_arr as $toselectid) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."oauth_token";
			$sql .= " WHERE rowid = ".((int) $toselectid);
			$sql .= " AND service = 'dolibarr_rest_api'";
			$sql .= " AND entity = ".((int) $conf->entity);

			$result = $db->query($sql);

			if ($result > 0) {
				$nbok++;
			} else {
				setEventMessages($db->error(), null, 'errors');
				$error++;
				break;
			}
		}

		if (empty($error)) {
			// Message for elements well deleted
			if ($nbok > 1) {
				setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
			} elseif ($nbok > 0) {
				setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
			}
			$db->commit();
		} else {
			$db->rollback();
		}
	}
}


/*
 * View
 */

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('ApiTokens');
$help_url = '';

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = 'SELECT COUNT(*) as nbtotalofrecords';
	$sqlforcount .= " FROM ".MAIN_DB_PREFIX."oauth_token as oat";
	$sqlforcount .= " WHERE entity =".((int) $conf->entity);
	$sqlforcount .= " AND fk_user = ".((int) $id);
	$sqlforcount .= " AND service = 'dolibarr_rest_api'";
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

$sql = "SELECT oat.rowid, oat.token, oat.entity, oat.state as rights, oat.datec as date_creation, oat.tms as date_modification";
if (isModEnabled('multicompany')) {
	$sql .= ", e.label as entity_name";
}
$sql .= " FROM ".MAIN_DB_PREFIX."oauth_token as oat";
if (isModEnabled('multicompany')) {
	$sql .= " JOIN ".$db->prefix()."entity as e ON oat.entity = e.rowid";
}
$sql .= " WHERE oat.fk_user = ".((int) $object->id);
$sql .= " AND service = 'dolibarr_rest_api'";
if ($search_entity) {
	$sql .= natural_search('oat.entity', $search_entity);
}
if ($search_datec_start) {
	$sql .= " AND oat.datec >= '".$db->idate($search_datec_start)."'";
}
if ($search_datec_end) {
	$sql .= " AND oat.datec <= '".$db->idate($search_datec_end)."'";
}
if ($search_tms_start) {
	$sql .= " AND oat.tms >= '".$db->idate($search_tms_start)."'";
}
if ($search_tms_end) {
	$sql .= " AND oat.tms <= '".$db->idate($search_tms_end)."'";
}
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);

$num = $db->num_rows($resql);

$param = '&id='.$id; // We always need the id of the user
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_datec_startday) {
	$param .= '&search_date_startday='.urlencode((string) ($search_datec_startday));
}
if ($search_datec_startmonth) {
	$param .= '&search_date_startmonth='.urlencode((string) ($search_datec_startmonth));
}
if ($search_datec_startyear) {
	$param .= '&search_date_startyear='.urlencode((string) ($search_datec_startyear));
}
if ($search_datec_endday) {
	$param .= '&search_date_endday='.urlencode((string) ($search_datec_endday));
}
if ($search_datec_endmonth) {
	$param .= '&search_date_endmonth='.urlencode((string) ($search_datec_endmonth));
}
if ($search_datec_endyear) {
	$param .= '&search_date_endyear='.urlencode((string) ($search_datec_endyear));
}
if ($search_tms_startday) {
	$param .= '&search_date_startday='.urlencode((string) ($search_tms_startday));
}
if ($search_tms_startmonth) {
	$param .= '&search_date_startmonth='.urlencode((string) ($search_tms_startmonth));
}
if ($search_tms_startyear) {
	$param .= '&search_date_startyear='.urlencode((string) ($search_tms_startyear));
}
if ($search_tms_endday) {
	$param .= '&search_date_endday='.urlencode((string) ($search_tms_endday));
}
if ($search_tms_endmonth) {
	$param .= '&search_date_endmonth='.urlencode((string) ($search_tms_endmonth));
}
if ($search_tms_endyear) {
	$param .= '&search_date_endyear='.urlencode((string) ($search_tms_endyear));
}

$arrayofselected = is_array($toselect) ? $toselect : array();

$head = user_prepare_head($object);

$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-card_param_ihm');


print '<!-- Token section -->'."\n";

$arrayofmassactions = array(
	'predelete' => img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete")
);

if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$tmpurlforbutton = DOL_URL_ROOT.'/user/api_token/card.php?id='.$id.'&action=create';
$morehtmlright = dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $tmpurlforbutton);

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
print_barre_liste($langs->trans("ListOfTokensForUser"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'fa-at', 0, $morehtmlright, '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$colspan = 5; // Base colspan for empty list

include DOL_DOCUMENT_ROOT.'/core/tpl/apitoken_list.tpl.php';


print '</form>';

// End of page
llxFooter();
$db->close();
