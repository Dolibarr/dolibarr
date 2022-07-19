<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       job_contact.php
 *  \ingroup    hrm
 *  \brief      Tab for contacts linked to Job
 */

// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT . '/hrm/lib/hrm_job.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("hrm", "companies", "other", "mails"));

$id     = (GETPOST('id') ?GETPOST('id', 'int') : GETPOST('facid', 'int')); // For backward compatibility
$ref    = GETPOST('ref', 'alpha');
$lineid = GETPOST('lineid', 'int');
$socid  = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

// Initialize technical objects
$object = new Job($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->hrm->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('jobcontact', 'globalcard')); // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

$permission = $user->rights->hrm->job->write;

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, $object->table_element, '', 'fk_soc', 'rowid', $isdraft);
//if (empty($conf->hrm->enabled)) accessforbidden();
//if (!$permissiontoread) accessforbidden();


/*
 * Add a new contact
 */

if ($action == 'addcontact' && $permission) {
	$contactid = (GETPOST('userid') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
	$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
	$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
} elseif ($action == 'swapstatut' && $permission) {
	// Toggle the status of a contact
	$result = $object->swapContactStatus(GETPOST('ligne', 'int'));
} elseif ($action == 'deletecontact' && $permission) {
	// Deletes a contact
	$result = $object->delete_contact($lineid);

	if ($result >= 0) {
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$title = $langs->trans('Job')." - ".$langs->trans('ContactsAddresses');
$help_url = '';
//$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);


/* *************************************************************************** */
/*                                                                             */
/* View and edit mode                                                         */
/*                                                                             */
/* *************************************************************************** */

if ($object->id) {
	/*
	 * Show tabs
	 */
	$head = jobPrepareHead($object);

	print dol_get_fiche_head($head, 'contact', '', -1, $object->picto);

	$linkback = '<a href="'.dol_buildpath('/hrm/job_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	 // Ref customer
	 $morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
	 $morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
	 // Thirdparty
	 $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	 // Project
	 if (! empty($conf->project->enabled))
	 {
	 $langs->load("projects");
	 $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	 if ($permissiontoadd)
	 {
	 if ($action != 'classify')
	 //$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	 $morehtmlref.=' : ';
	 if ($action == 'classify') {
	 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	 $morehtmlref.='<input type="hidden" name="action" value="classin">';
	 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
	 $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	 $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	 $morehtmlref.='</form>';
	 } else {
	 $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	 }
	 } else {
	 if (! empty($object->fk_project)) {
	 $proj = new Project($db);
	 $proj->fetch($object->fk_project);
	 $morehtmlref .= ': '.$proj->getNomUrl();
	 } else {
	 $morehtmlref .= '';
	 }
	 }
	 }*/
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

	print dol_get_fiche_end();

	print '<br>';

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir) {
		$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
		if ($res) {
			break;
		}
	}
}

// End of page
llxFooter();
$db->close();
