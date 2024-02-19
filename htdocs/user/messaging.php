<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/societe/messaging.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'bills', 'companies', 'orders', 'propal'));

$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'useragenda';

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}

$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Initialize technical objects
$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->getrights();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('agendathirdparty', 'globalcard'));

// Security check
$userid = GETPOSTINT('userid') ? GETPOSTINT('userid') : GETPOSTINT('id');
if ($user->id) {
	$userId = $user->id;
}

$result = $object->fetch($userid);
if ($result <= 0) {
	accessforbidden('User not found');
}

$result = restrictedArea($user, 'user', $userId, '&user');



/*
 *	Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode = '';
		$search_agenda_label = '';
	}
}


/*
 * View
 */

$form = new Form($db);

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('Info');
$help_url = '';
llxHeader('', $title, $help_url);

$head = user_prepare_head($object);

$title = $langs->trans("User");
print dol_get_fiche_head($head, 'info', $title, -1, 'user');


$linkback = '';

if ($user->hasRight('user', 'user', 'lire') || $user->admin) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin, 'rowid', 'ref', $morehtmlref);


$object->info($userid);


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

dol_print_object_info($object);

print '</div>';


print dol_get_fiche_end();

$objUser = $object;
$objcon = new stdClass();

$out = '';
$permok = $user->hasRight('agenda', 'myactions', 'create');
if ((!empty($objUser->id) || !empty($objcon->id)) && $permok) {
	if (is_object($objUser) && get_class($objUser) == 'User') {
		$out .= '&amp;originid='.$objUser->id.($objUser->id > 0 ? '&amp;userid='.$objUser->id : '').'&amp;backtopage='.urlencode($_SERVER['PHP_SELF'].($objUser->id > 0 ? '?userid='.$objUser->id : ''));
	}
	$out .= (!empty($objcon->id) ? '&amp;contactid='.$objcon->id : '');
	$out .= '&amp;datep='.dol_print_date(dol_now(), 'dayhourlog', 'tzuserrel');
}

$morehtmlright = '';

$messagingUrl = DOL_URL_ROOT.'/user/messaging.php?userid='.$object->id;
$morehtmlright .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 2);
$messagingUrl = DOL_URL_ROOT.'/user/agenda.php?id='.$object->id;
$morehtmlright .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 1);

if (isModEnabled('agenda')) {
	if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
		$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
	}
}

if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allaactions', 'read'))) {
	print '<br>';
	$param = '&userid='.urlencode($userid);
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}


	// Try to know count of actioncomm from cache
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_events_user_'.$object->id;
	//$nbEvent = dol_getcache($cachekey);	// TODO Add nb into badge in menu so we can get it from cache also here

	$titlelist = $langs->trans("ActionsOnCompany").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
	if (!empty($conf->dol_optimize_smallscreen)) {
		$titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
	}

	print_barre_liste($titlelist, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', -1, '', '', 0, $morehtmlright, '', 0, 1, 0);

	// List of all actions
	$filters = array();
	$filters['search_agenda_label'] = $search_agenda_label;
	$filters['search_rowid'] = $search_rowid;

	show_actions_messaging($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
}

//End of page
llxFooter();
$db->close();
