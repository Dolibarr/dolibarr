<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021 Greg Rastklan <greg.rastklan@atm-consulting.fr>
 * Copyright (C) 2021 Jean-Pascal BOUDET <jean-pascal.boudet@atm-consulting.fr>
 * Copyright (C) 2021 Grégory BLEMAND <gregory.blemand@atm-consulting.fr>
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
 *    \file       htdocs/hrm/job_agenda.php
 *    \ingroup    hrm
 *    \brief      Tab of events on Job
 */


// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm_job.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('hrm', 'other'));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

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

// Initialize a technical objects
$object = new Job($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('jobagenda', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->hrm->multidir_output[!empty($object->entity) ? $object->entity : $conf->entity]."/".$object->id;
}

// Permissions
$permissiontoread = $user->hasRight('hrm', 'all', 'read');
$permissiontoadd  = $user->hasRight('hrm', 'all', 'write'); // Used by the include of actions_addupdatedelete.inc.php

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
if (empty($conf->hrm->enabled)) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}


/*
 *  Actions
 */

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
 *	View
 */

$form = new Form($db);

if ($object->id > 0) {
	$title = $langs->trans("Agenda");
	$help_url = 'EN:Module_Agenda_En|DE:Modul_Terminplanung';
	llxHeader('', $title, $help_url);

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}
	$head = jobPrepareHead($object);


	print dol_get_fiche_head($head, 'agenda', $langs->trans("Agenda"), -1, $object->picto);

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/hrm/job_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refid">';
	$morehtmlref .= $object->label;
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'rowid', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();



	// Actions buttons

	$objthirdparty = $object;
	$objcon = new stdClass();

	$out = '&origin='.urlencode((string) ($object->element.'@'.$object->module)).'&originid='.urlencode((string) ($object->id));
	$urlbacktopage = $_SERVER['PHP_SELF'].'?id='.$object->id;
	$out .= '&backtopage='.urlencode($urlbacktopage);
	$permok = $user->hasRight('agenda', 'myactions', 'create');
	if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok) {
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') {
			$out .= '&socid='.urlencode((string) ($objthirdparty->id));
		}
		$out .= (!empty($objcon->id) ? '&contactid='.urlencode($objcon->id) : '');
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}


	if (isModEnabled('agenda')) {
		if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
			$newcardbutton = '';
			$newcardbutton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
		}
	}

	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$param = '&id='.$object->id.'&socid='.(!empty($socid) ? '&socid='.$socid : '');
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}


		print load_fiche_titre($langs->trans("ActionsOnJob"), $newcardbutton, '');

		// List of all actions
		$filters = array();
		$filters['search_agenda_label'] = $search_agenda_label;
		$filters['search_rowid'] = $search_rowid;

		// TODO Replace this with same code than into list.php
		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
	}
}

// End of page
llxFooter();
$db->close();
