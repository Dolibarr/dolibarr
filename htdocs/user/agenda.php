<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       htdocs/user/agenda.php
 *      \ingroup    core
 *		\brief      Page for user events
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->load("users");

// Security check
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

if (!isset($id) || empty($id)) {
	accessforbidden();
}

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

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
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

$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->loadRights();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight('user', 'self', 'creer')) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// If user is not user that read and no permission to read other users, we stop
if (($object->id != $user->id) && !$user->hasRight('user', 'user', 'lire')) {
	accessforbidden();
}

$parameters = array('id' => $id);
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
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-card_agenda');

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


$object->info($id); // This overwrite ->ref with login instead of id


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
$morehtmlright .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 1);
$messagingUrl = DOL_URL_ROOT.'/user/agenda.php?id='.$object->id;
$morehtmlright .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 2);

if (isModEnabled('agenda')) {
	if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
		$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
	}
}

if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allaactions', 'read'))) {
	print '<br>';
	$param = '&id='.urlencode((string) ($id));

	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}


	// Try to know count of actioncomm from cache
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_events_user_'.$object->id;
	$nbEvent = dol_getcache($cachekey);

	$titlelist = $langs->trans("ActionsOnUser").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
	if (!empty($conf->dol_optimize_smallscreen)) {
		$titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
	}

	print_barre_liste($titlelist, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', -1, '', '', 0, $morehtmlright, '', 0, 1, 0);

	// List of all actions
	$filters = array();
	$filters['search_agenda_label'] = $search_agenda_label;
	$filters['search_rowid'] = $search_rowid;

	// TODO Replace this with same code than into list.php
	show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
}

// End of page
llxFooter();
$db->close();
