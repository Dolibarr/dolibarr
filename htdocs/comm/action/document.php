<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *       \file       htdocs/comm/action/document.php
 *       \ingroup    agenda
 *       \brief      Page of documents linked to actions
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'commercial', 'other', 'bills'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) {
	$socid = $user->socid;
}
if ($user->socid > 0) {
	unset($_GET["action"]);
	$action = '';
}

$object = new ActionComm($db);

if ($id > 0) {
	$ret = $object->fetch($id);
	$object->fetch_thirdparty();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('actioncard', 'globalcard'));

// Get parameters
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
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart = 'actions';

$result = restrictedArea($user, 'agenda', $id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}

$usercancreate = $user->hasRight('agenda', 'allactions', 'create') || (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'));
$permissiontoadd = $usercancreate;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda|DE:Modul_Terminplanung';

llxHeader('', $langs->trans("Agenda"), $help_url);

$now = dol_now();
$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

if ($object->id > 0) {
	$result1 = $object->fetch($id);
	$result2 = $object->fetch_thirdparty();
	$result3 = $object->fetch_contact();
	$result4 = $object->fetch_userassigned();
	$result5 = $object->fetch_optionals();

	if ($result1 < 0 || $result2 < 0 || $result3 < 0 || $result4 < 0 || $result5 < 0) {
		dol_print_error($db, $object->error);
		exit;
	}

	if ($object->authorid > 0) {
		$tmpuser = new User($db);
		$res = $tmpuser->fetch($object->authorid);
		$object->author = $tmpuser;
	}
	if ($object->usermodid > 0) {
		$tmpuser = new User($db);
		$res = $tmpuser->fetch($object->usermodid);
		$object->usermod = $tmpuser;
	}

	$author = new User($db);
	$author->fetch($object->author->id);
	$object->author = $author;


	$head = actions_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans("Action"), -1, 'action');

	// Link to other agenda views
	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&restore_lastsearch_values=1">';
	$linkback .= img_picto($langs->trans("BackToList"), 'object_calendarlist', 'class="pictoactionview pictofixedwidth"');
	$linkback .= '<span class="hideonsmartphone">'.$langs->trans("BackToList").'</span>';
	$linkback .= '</a>';
	$linkback .= '</li>';
	$linkback .= '<li class="noborder litext">';
	$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_month&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
	$linkback .= img_picto($langs->trans("ViewCal"), 'object_calendar', 'class="pictoactionview pictofixedwidth"');
	$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewCal").'</span>';
	$linkback .= '</a>';
	$linkback .= '</li>';
	$linkback .= '<li class="noborder litext">';
	$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_week&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
	$linkback .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictoactionview pictofixedwidth"');
	$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewWeek").'</span>';
	$linkback .= '</a>';
	$linkback .= '</li>';
	$linkback .= '<li class="noborder litext">';
	$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
	$linkback .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictoactionview pictofixedwidth"');
	$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewDay").'</span>';
	$linkback .= '</a>';
	$linkback .= '</li>';
	$linkback .= '<li class="noborder litext">';
	$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/peruser.php?mode=show_peruser&year='.dol_print_date($object->datep, '%Y').'&month='.dol_print_date($object->datep, '%m').'&day='.dol_print_date($object->datep, '%d').'">';
	$linkback .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictoactionview pictofixedwidth"');
	$linkback .= '<span class="hideonsmartphone">'.$langs->trans("ViewPerUser").'</span>';
	$linkback .= '</a>';

	// Add more views from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addCalendarView', $parameters, $object, $action);
	if (empty($reshook)) {
		$linkback .= $hookmanager->resPrint;
	} elseif ($reshook > 1) {
		$linkback = $hookmanager->resPrint;
	}

	$morehtmlref = '<div class="refidno">';
	// Thirdparty
	//$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		//$morehtmlref .= '<br>';
		if (0) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, ($user->socid ? 0 : 1), 'id', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	// Affichage fiche action en mode visu
	print '<table class="border tableforfield centpercent">';

	// Type of event
	if (getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td colspan="3">';
		print $object->getTypePicto();
		print $langs->trans("Action".$object->type_code);
		print '</td></tr>';
	}

	// Full day event
	print '<tr><td class="titlefield">'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($object->fulldayevent ? 1 : 0, 3).'</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateActionStart").'</td><td colspan="3">';
	if (empty($object->fulldayevent)) {
		print dol_print_date($object->datep, 'dayhour', 'tzuser');
	} else {
		$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
		print dol_print_date($object->datep, 'day', ($tzforfullday ? $tzforfullday : 'tzuser'));
	}
	if ($object->percentage == 0 && $object->datep && $object->datep < ($now - $delay_warning)) {
		print img_warning($langs->trans("Late"));
	}
	print '</td>';
	print '</tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
	if (empty($object->fulldayevent)) {
		print dol_print_date($object->datef, 'dayhour', 'tzuser');
	} else {
		$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
		print dol_print_date($object->datef, 'day', ($tzforfullday ? $tzforfullday : 'tzuser'));
	}
	if ($object->percentage > 0 && $object->percentage < 100 && $object->datef && $object->datef < ($now - $delay_warning)) {
		print img_warning($langs->trans("Late"));
	}
	print '</td></tr>';

	// Location
	if (!getDolGlobalString('AGENDA_DISABLE_LOCATION')) {
		print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$object->location.'</td></tr>';
	}

	// Assigned to
	print '<tr><td class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
	$listofuserid = array();
	if (empty($donotclearsession)) {
		if ($object->userownerid > 0) {
			$listofuserid[$object->userownerid] = array('id'=>$object->userownerid, 'transparency'=>$object->transparency); // Owner first
		}
		if (!empty($object->userassigned)) {	// Now concat assigned users
			// Restore array with key with same value than param 'id'
			$tmplist1 = $object->userassigned;
			foreach ($tmplist1 as $key => $val) {
				if ($val['id'] && $val['id'] != $object->userownerid) {
					$listofuserid[$val['id']] = $val;
				}
			}
		}
		$_SESSION['assignedtouser'] = json_encode($listofuserid);
	} else {
		if (!empty($_SESSION['assignedtouser'])) {
			$listofuserid = json_decode($_SESSION['assignedtouser'], true);
		}
	}
	$listofcontactid = array(); // not used yet
	$listofotherid = array(); // not used yet
	print '<div class="assignedtouser">';
	print $form->select_dolusers_forevent('view', 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, '', ($object->datep != $object->datef) ? 1 : 0, $listofuserid, $listofcontactid, $listofotherid);
	print '</div>';
	/*if (in_array($user->id,array_keys($listofuserid)))
	{
		print '<div class="myavailability">';
		print $langs->trans("MyAvailability").': '.(($object->userassigned[$user->id]['transparency'] > 0)?$langs->trans("Busy"):$langs->trans("Available"));	// We show nothing if event is assigned to nobody
		print '</div>';
	}*/
	print '	</td></tr>';

	print '</table>';

	print '<table class="border tableforfield centpercent">';

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	print '<tr><td class="titlefield" class="nowrap">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();


	$modulepart = 'actions';
	$permissiontoadd = $user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create');
	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
