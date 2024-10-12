<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
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
 *  \file       htdocs/user/document.php
 *  \brief      Tab for documents linked to user
 *  \ingroup    user
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'other'));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$id = (GETPOSTINT('userid') ? GETPOSTINT('userid') : GETPOSTINT('id'));
$ref = GETPOST('ref', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'userdoc'; // To manage different context of search

if (!isset($id) || empty($id)) {
	accessforbidden();
}

// Define value to know what current user can do on users
$permissiontoadd = (!empty($user->admin) || $user->hasRight("user", "user", "write"));
$permissiontoread = (!empty($user->admin) || $user->hasRight("user", "user", "read"));
$permissiontoedit = (!empty($user->admin) || $user->hasRight("user", "user", "write"));
$permissiontodisable = (!empty($user->admin) || $user->hasRight("user", "user", "delete"));
$permissiontoreadgroup = $permissiontoread;
$permissiontoeditgroup = $permissiontoedit;
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$permissiontoreadgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "read"));
	$permissiontoeditgroup = (!empty($user->admin) || $user->hasRight("user", "group_advance", "write"));
}
// Define value to know what current user can do on properties of edited user
if ($id) {
	// $user est le user qui edite, $id est l'id de l'utilisateur edite
	$permissiontoedit = ((($user->id == $id) && $user->hasRight("user", "self", "write")) || (($user->id != $id) && $user->hasRight("user", "user", "write")));
	$permissiontoeditpassword = ((($user->id == $id) && $user->hasRight("user", "self", "password")) || (($user->id != $id) && $user->hasRight("user", "user", "password")));
}

$permissiontoadd = $permissiontoedit;	// Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles
$permtoedit = $permissiontoedit;

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = 'user';

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('usercard', 'userdoc', 'globalcard'));

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

if ($user->id != $id && !$permissiontoread) {
	accessforbidden();
}

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "position_name";
}

$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->loadRights();
	//$upload_dir = $conf->user->multidir_output[$object->entity] . "/" . $object->id ;
	// For users, the upload_dir is always $conf->user->entity for the moment
	$upload_dir = $conf->user->dir_output."/".$object->id;
}

/*
 * Actions
 */

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}


/*
 * View
 */

$form = new Form($db);


$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('Documents');
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-card_document');

if ($object->id) {
	/*
	 * Affichage onglets
	 */
	if (isModEnabled('notification')) {
		$langs->load("mails");
	}
	$head = user_prepare_head($object);

	print dol_get_fiche_head($head, 'document', $langs->trans("User"), -1, 'user');

	$linkback = '';
	if ($user->hasRight("user", "user", "read") || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
	$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

	dol_banner_tab($object, 'id', $linkback, $user->hasRight("user", "user", "read") || $user->admin, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	print '<table class="border tableforfield centpercent">';

	// Login
	print '<tr><td class="titlefield">'.$langs->trans("Login").'</td>';
	if (!empty($object->ldap_sid) && $object->statut == 0) {
		print '<td class="error">';
		print $langs->trans("LoginAccountDisableInDolibarr");
		print '</td>';
	} else {
		print '<td>';
		$addadmin = '';
		if (property_exists($object, 'admin')) {
			if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
				$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
			} elseif (!empty($object->admin)) {
				$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
			}
		}
		print showValueWithClipboardCPButton($object->login).$addadmin;
		print '</td>';
	}
	print '</tr>';

	// Number of files
	print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.dol_print_size($totalsize, 1, 1).'</td></tr>';

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'user';
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
