<?php
/* Copyright (C) 2017 Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2021 NextGestion 			<contact@nextgestion.com>
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
 *   	\file       partnership_card.php
 *		\ingroup    partnership
 *		\brief      Page to create/edit/view partnership
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/lib/partnership.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","members","partnership", "other"));

// Get parameters
$id = GETPOST('rowid', 'int') ? GETPOST('rowid', 'int') : GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'partnershipcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

$object = new Adherent($db);
if ($id > 0) {
	$object->fetch($id);
}

// Initialize technical objects
$object 		= new Partnership($db);
$extrafields 	= new ExtraFields($db);
$adht 			= new AdherentType($db);
$diroutputmassaction = $conf->partnership->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('partnershipthirdparty', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();

foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

$permissiontoread = $user->hasRight('partnership', 'read');
$permissiontoadd = $user->hasRight('partnership', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('partnership', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->hasRight('partnership', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('partnership', 'write'); // Used by the include of actions_dellink.inc.php
$usercanclose = $user->hasRight('partnership', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$upload_dir = $conf->partnership->multidir_output[isset($object->entity) ? $object->entity : 1];


if (getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') != 'member') {
	accessforbidden('Partnership module is not activated for members');
}
if (!isModEnabled('partnership')) {
	accessforbidden();
}
if (empty($permissiontoread)) {
	accessforbidden();
}
if ($action == 'edit' && empty($permissiontoadd)) {
	accessforbidden();
}
if (($action == 'update' || $action == 'edit') && $object->status != $object::STATUS_DRAFT) {
	accessforbidden();
}


// Security check
$result = restrictedArea($user, 'adherent', $id, '', '', 'socid', 'rowid', 0);


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$date_start = dol_mktime(0, 0, 0, GETPOST('date_partnership_startmonth', 'int'), GETPOST('date_partnership_startday', 'int'), GETPOST('date_partnership_startyear', 'int'));
$date_end = dol_mktime(0, 0, 0, GETPOST('date_partnership_endmonth', 'int'), GETPOST('date_partnership_endday', 'int'), GETPOST('date_partnership_endyear', 'int'));

if (empty($reshook)) {
	$error = 0;

	$backtopage = dol_buildpath('/partnership/partnership.php', 1).'?rowid='.($id > 0 ? $id : '__ID__');

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';
}

$object->fields['fk_member']['visible'] = 0;
if ($object->id > 0 && $object->status == $object::STATUS_REFUSED && empty($action)) {
	$object->fields['reason_decline_or_cancel']['visible'] = 1;
}
$object->fields['note_public']['visible'] = 1;


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title = $langs->trans("Partnership");
llxHeader('', $title);

$form = new Form($db);

if ($id > 0) {
	$langs->load("members");

	$object = new Adherent($db);
	$result = $object->fetch($id);

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}

	$adht->fetch($object->typeid);

	$head = member_prepare_head($object);

	print dol_get_fiche_head($head, 'partnership', $langs->trans("ThirdParty"), -1, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'rowid', $linkback);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Login
	if (!getDolGlobalString('ADHERENT_LOGIN_NOT_REQUIRED')) {
		print '<tr><td class="titlefield">'.$langs->trans("Login").' / '.$langs->trans("Id").'</td><td class="valeur">'.$object->login.'&nbsp;</td></tr>';
	}

	// Type
	print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td class="valeur">'.$adht->getNomUrl(1)."</td></tr>\n";

	// Morphy
	print '<tr><td>'.$langs->trans("MemberNature").'</td><td class="valeur" >'.$object->getmorphylib().'</td>';
	print '</tr>';

	// Company
	print '<tr><td>'.$langs->trans("Company").'</td><td class="valeur">'.$object->company.'</td></tr>';

	// Civility
	print '<tr><td>'.$langs->trans("UserTitle").'</td><td class="valeur">'.$object->getCivilityLabel().'&nbsp;</td>';
	print '</tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();
} else {
	dol_print_error('', 'Parameter rowid not defined');
}


// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	// Buttons for actions

	if ($action != 'presend') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Show
			if ($permissiontoadd) {
				print dolGetButtonAction($langs->trans('AddPartnership'), '', 'default', DOL_URL_ROOT.'/partnership/partnership_card.php?action=create&fk_member='.$object->id.'&backtopage='.urlencode(DOL_URL_ROOT.'/adherents/partnership.php?id='.$object->id), '', $permissiontoadd);
			}
		}
		print '</div>'."\n";
	}


	//$morehtmlright = 'partnership/partnership_card.php?action=create&backtopage=%2Fdolibarr%2Fhtdocs%2Fpartnership%2Fpartnership_list.php';
	$morehtmlright = '';

	print load_fiche_titre($langs->trans("PartnershipDedicatedToThisMember", $langs->transnoentitiesnoconv("Partnership")), $morehtmlright, '');

	$memberid = $object->id;


	// TODO Replace this card with the list of all partnerships.

	$object = new Partnership($db);
	$partnershipid = $object->fetch(0, "", $memberid);

	if ($partnershipid > 0) {
		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">'."\n";

		// Common attributes
		//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
		//unset($object->fields['fk_project']);				// Hide field already shown in banner
		//unset($object->fields['fk_member']);					// Hide field already shown in banner
		include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

		// End of subscription date
		$fadherent = new Adherent($db);
		$fadherent->fetch($object->fk_member);
		print '<tr><td>'.$langs->trans("SubscriptionEndDate").'</td><td class="valeur">';
		if ($fadherent->datefin) {
			print dol_print_date($fadherent->datefin, 'day');
			if ($fadherent->hasDelay()) {
				print " ".img_warning($langs->trans("Late"));
			}
		} else {
			if (!$adht->subscription) {
				print $langs->trans("SubscriptionNotRecorded");
				if ($fadherent->statut > 0) {
					print " ".img_warning($langs->trans("Late")); // Display a delay picto only if it is not a draft and is not canceled
				}
			} else {
				print $langs->trans("SubscriptionNotReceived");
				if ($fadherent->statut > 0) {
					print " ".img_warning($langs->trans("Late")); // Display a delay picto only if it is not a draft and is not canceled
				}
			}
		}
		print '</td></tr>';

		print '</table>';
		print '</div>';
	}
}

// End of page
llxFooter();
$db->close();
