<?php
/* Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2018-2024 Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2022      Charlene Benke       <charlene@patas-monkey.com>
 * Copyright (C) 2023      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Vincent de Grandpré	<vincent@de-grandpre.quebec>
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
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/lib/project.lib.php
 * \brief      Functions used by project module
 * \ingroup    project
 */
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


/**
 * Prepare array with list of tabs
 *
 * @param	Project	$project	Object related to tabs
 * @param	string	$moreparam	More param on url
 * @return	array				Array of tabs to show
 */
function project_prepare_head(Project $project, $moreparam = '')
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/card.php?id='.((int) $project->id).($moreparam ? '&'.$moreparam : '');
	$head[$h][1] = $langs->trans("Project");
	$head[$h][2] = 'project';
	$h++;
	$nbContacts = 0;
	// Enable caching of project count Contacts
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_contacts_project_'.$project->id;
	$dataretrieved = dol_getcache($cachekey);

	if (!is_null($dataretrieved)) {
		$nbContacts = $dataretrieved;
	} else {
		$nbContacts = count($project->liste_contact(-1, 'internal')) + count($project->liste_contact(-1, 'external'));
		dol_setcache($cachekey, $nbContacts, 120);	// If setting cache fails, this is not a problem, so we do not test result.
	}
	$head[$h][0] = DOL_URL_ROOT.'/projet/contact.php?id='.((int) $project->id).($moreparam ? '&'.$moreparam : '');
	$head[$h][1] = $langs->trans("ProjectContact");
	if ($nbContacts > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContacts.'</span>';
	}
	$head[$h][2] = 'contact';
	$h++;

	if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
		// Then tab for sub level of projet, i mean tasks
		$nbTasks = 0;
		// Enable caching of project count Tasks
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_tasks_project_'.$project->id;
		$dataretrieved = dol_getcache($cachekey);

		if (!is_null($dataretrieved)) {
			$nbTasks = $dataretrieved;
		} else {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
			$taskstatic = new Task($db);
			$nbTasks = count($taskstatic->getTasksArray(0, 0, $project->id, 0, 0));
			dol_setcache($cachekey, $nbTasks, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks.php?id='.((int) $project->id).($moreparam ? '&'.$moreparam : '');
		$head[$h][1] = $langs->trans("Tasks");
		if ($nbTasks > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbTasks).'</span>';
		}
		$head[$h][2] = 'tasks';
		$h++;

		$nbTimeSpent = 0;
		// Enable caching of project count Timespent
		$cachekey = 'count_timespent_project_'.$project->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbTimeSpent = $dataretrieved;
		} else {
			$sql = "SELECT t.rowid";
			//$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
			//$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t, ".MAIN_DB_PREFIX."projet_task as pt";
			$sql .= " WHERE t.fk_element = pt.rowid";
			$sql .= " AND t.elementtype = 'task'";
			$sql .= " AND pt.fk_projet =".((int) $project->id);
			$resql = $db->query($sql);
			if ($resql) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$nbTimeSpent = 1;
					dol_setcache($cachekey, $nbTimeSpent, 120);	// If setting cache fails, this is not a problem, so we do not test result.
				}
			} else {
				dol_print_error($db);
			}
		}

		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?withproject=1&projectid='.((int) $project->id).($moreparam ? '&'.$moreparam : '');
		$head[$h][1] = $langs->trans("TimeSpent");
		if ($nbTimeSpent > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">...</span>';
		}
		$head[$h][2] = 'timespent';
		$h++;
	}

	if (isModEnabled("supplier_proposal") || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")
		|| isModEnabled("propal") || isModEnabled('order')
		|| isModEnabled('invoice') || isModEnabled('contract')
		|| isModEnabled('intervention') || isModEnabled('agenda') || isModEnabled('deplacement') || isModEnabled('stock')) {
		$nbElements = 0;
		// Enable caching of thirdrparty count Contacts
		$cachekey = 'count_elements_project_'.$project->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbElements = $dataretrieved;
		} else {
			if (isModEnabled('stock')) {
				$nbElements += $project->getElementCount('stock', 'entrepot', 'fk_project');
			}
			if (isModEnabled("propal")) {
				$nbElements += $project->getElementCount('propal', 'propal');
			}
			if (isModEnabled('order')) {
				$nbElements += $project->getElementCount('order', 'commande');
			}
			if (isModEnabled('invoice')) {
				$nbElements += $project->getElementCount('invoice', 'facture');
			}
			if (isModEnabled('invoice')) {
				$nbElements += $project->getElementCount('invoice_predefined', 'facture_rec');
			}
			if (isModEnabled('supplier_proposal')) {
				$nbElements += $project->getElementCount('proposal_supplier', 'supplier_proposal');
			}
			if (isModEnabled("supplier_order")) {
				$nbElements += $project->getElementCount('order_supplier', 'commande_fournisseur');
			}
			if (isModEnabled("supplier_invoice")) {
				$nbElements += $project->getElementCount('invoice_supplier', 'facture_fourn');
			}
			if (isModEnabled('contract')) {
				$nbElements += $project->getElementCount('contract', 'contrat');
			}
			if (isModEnabled('intervention')) {
				$nbElements += $project->getElementCount('intervention', 'fichinter');
			}
			if (isModEnabled("shipping")) {
				$nbElements += $project->getElementCount('shipping', 'expedition');
			}
			if (isModEnabled('mrp')) {
				$nbElements += $project->getElementCount('mrp', 'mrp_mo', 'fk_project');
			}
			if (isModEnabled('deplacement')) {
				$nbElements += $project->getElementCount('trip', 'deplacement');
			}
			if (isModEnabled('expensereport')) {
				$nbElements += $project->getElementCount('expensereport', 'expensereport');
			}
			if (isModEnabled('don')) {
				$nbElements += $project->getElementCount('donation', 'don');
			}
			if (isModEnabled('loan')) {
				$nbElements += $project->getElementCount('loan', 'loan');
			}
			if (isModEnabled('tax')) {
				$nbElements += $project->getElementCount('chargesociales', 'chargesociales');
			}
			if (isModEnabled('project')) {
				$nbElements += $project->getElementCount('project_task', 'projet_task');
			}
			if (isModEnabled('stock')) {
				$nbElements += $project->getElementCount('stock_mouvement', 'stock');
			}
			if (isModEnabled('salaries')) {
				$nbElements += $project->getElementCount('salaries', 'payment_salary');
			}
			if (isModEnabled("bank")) {
				$nbElements += $project->getElementCount('variouspayment', 'payment_various');
			}
			dol_setcache($cachekey, $nbElements, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$project->id;
		$head[$h][1] = $langs->trans("ProjectOverview");
		if ($nbElements > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbElements.'</span>';
		}
		$head[$h][2] = 'element';
		$h++;
	}

	if (isModEnabled('ticket') && $user->hasRight('ticket', 'read')) {
		require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
		$Tickettatic = new Ticket($db);
		$nbTicket = $Tickettatic->getCountOfItemsLinkedByObjectID($project->id, 'fk_project', 'ticket');
		$head[$h][0] = DOL_URL_ROOT.'/ticket/list.php?projectid='.((int) $project->id);
		$head[$h][1] = $langs->trans("Ticket");
		if ($nbTicket > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbTicket).'</span>';
		}
		$head[$h][2] = 'ticket';
		$h++;
	}

	if (isModEnabled('eventorganization') && !empty($project->usage_organize_event)) {
		$langs->load('eventorganization');
		$head[$h][0] = DOL_URL_ROOT . '/eventorganization/conferenceorbooth_list.php?projectid=' . $project->id;
		$head[$h][1] = $langs->trans("EventOrganization");

		// Enable caching of conf or booth count
		$nbConfOrBooth = 0;
		$nbAttendees = 0;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_conferenceorbooth_'.$project->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbConfOrBooth = $dataretrieved;
		} else {
			require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
			$conforbooth = new ConferenceOrBooth($db);
			$result = $conforbooth->fetchAll('', '', 0, 0, '(t.fk_project:=:'.((int) $project->id).")");
			//,
			if (!is_array($result) && $result < 0) {
				setEventMessages($conforbooth->error, $conforbooth->errors, 'errors');
			} else {
				$nbConfOrBooth = count($result);
			}
			dol_setcache($cachekey, $nbConfOrBooth, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		$cachekey = 'count_attendees_'.$project->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbAttendees = $dataretrieved;
		} else {
			require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
			$conforboothattendee = new ConferenceOrBoothAttendee($db);
			$result = $conforboothattendee->fetchAll('', '', 0, 0, '(t.fk_project:=:'.((int) $project->id).')');

			if (!is_array($result) && $result < 0) {
				setEventMessages($conforboothattendee->error, $conforboothattendee->errors, 'errors');
			} else {
				$nbAttendees = count($result);
			}
			dol_setcache($cachekey, $nbAttendees, 120);	// If setting cache fails, this is not a problem, so we do not test result.
		}
		if ($nbConfOrBooth > 0 || $nbAttendees > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">';
			$head[$h][1] .= '<span title="'.dol_escape_htmltag($langs->trans("ConferenceOrBooth")).'">'.$nbConfOrBooth.'</span>';
			$head[$h][1] .= ' + ';
			$head[$h][1] .= '<span title="'.dol_escape_htmltag($langs->trans("Attendees")).'">'.$nbAttendees.'</span>';
			$head[$h][1] .= '</span>';
		}
		$head[$h][2] = 'eventorganisation';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $project, $head, $h, 'project', 'add', 'core');


	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($project->note_private)) {
			$nbNote++;
		}
		if (!empty($project->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/projet/note.php?id='.$project->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'notes';
		$h++;
	}

	// Attached files and Links
	$totalAttached = 0;
	// Enable caching of thirdrparty count attached files and links
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_attached_project_'.$project->id;
	$dataretrieved = dol_getcache($cachekey);
	if (!is_null($dataretrieved)) {
		$totalAttached = $dataretrieved;
	} else {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$upload_dir = $conf->project->multidir_output[empty($project->entity) ? 1 : $project->entity]."/".dol_sanitizeFileName($project->ref);
		$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
		$nbLinks = Link::count($db, $project->element, $project->id);
		$totalAttached = $nbFiles + $nbLinks;
		dol_setcache($cachekey, $totalAttached, 120);		// If setting cache fails, this is not a problem, so we do not test result.
	}
	$head[$h][0] = DOL_URL_ROOT.'/projet/document.php?id='.$project->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($totalAttached) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($totalAttached).'</span>';
	}
	$head[$h][2] = 'document';
	$h++;

	// Manage discussion
	if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT')) {
		$nbComments = 0;
		// Enable caching of thirdrparty count attached files and links
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_attached_project_'.$project->id;
		$dataretrieved = dol_getcache($cachekey);
		if (!is_null($dataretrieved)) {
			$nbComments = $dataretrieved;
		} else {
			$nbComments = $project->getNbComments();
			dol_setcache($cachekey, $nbComments, 120);		// If setting cache fails, this is not a problem, so we do not test result.
		}
		$head[$h][0] = DOL_URL_ROOT.'/projet/comment.php?id='.$project->id;
		$head[$h][1] = $langs->trans("CommentLink");
		if ($nbComments > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbComments.'</span>';
		}
		$head[$h][2] = 'project_comment';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/projet/messaging.php?id='.$project->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $project, $head, $h, 'project', 'add', 'external');

	complete_head_from_modules($conf, $langs, $project, $head, $h, 'project', 'remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function task_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/task.php?id='.$object->id.(GETPOST('withproject') ? '&withproject=1' : '');
	$head[$h][1] = $langs->trans("Task");
	$head[$h][2] = 'task_task';
	$h++;

	$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$object->id.(GETPOST('withproject') ? '&withproject=1' : '');
	$head[$h][1] = $langs->trans("TaskRessourceLinks");
	if ($nbContact > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbContact.'</span>';
	}
	$head[$h][2] = 'task_contact';
	$h++;

	// Is there timespent ?
	$nbTimeSpent = 0;
	$sql = "SELECT t.rowid";
	//$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
	//$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
	$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
	$sql .= " WHERE t.elementtype='task' AND t.fk_element = ".((int) $object->id);
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$nbTimeSpent = 1;
		}
	} else {
		dol_print_error($db);
	}

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?id='.urlencode($object->id).(GETPOST('withproject') ? '&withproject=1' : '');
	$head[$h][1] = $langs->trans("TimeSpent");
	if ($nbTimeSpent > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">...</span>';
	}
	$head[$h][2] = 'task_time';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'task', 'add', 'core');

	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/note.php?id='.urlencode($object->id).(GETPOST('withproject') ? '&withproject=1' : '');
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'task_notes';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/document.php?id='.$object->id.(GETPOST('withproject') ? '&withproject=1' : '');
	$filesdir = $conf->project->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->project->ref).'/'.dol_sanitizeFileName($object->ref);
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$nbFiles = count(dol_dir_list($filesdir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'task_document';
	$h++;

	// Manage discussion
	if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_TASK')) {
		$nbComments = $object->getNbComments();
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/comment.php?id='.$object->id.(GETPOST('withproject') ? '&withproject=1' : '');
		$head[$h][1] = $langs->trans("CommentLink");
		if ($nbComments > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbComments.'</span>';
		}
		$head[$h][2] = 'task_comment';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'task', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'task', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param	string	$mode		Mode
 * @param   string  $fuser      Filter on user
 * @return  array				Array of tabs to show
 */
function project_timesheet_prepare_head($mode, $fuser = null)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$param = '';
	$param .= ($mode ? '&mode='.$mode : '');
	if (is_object($fuser) && $fuser->id > 0 && $fuser->id != $user->id) {
		$param .= '&search_usertoprocessid='.$fuser->id;
	}

	if (!getDolGlobalString('PROJECT_DISABLE_TIMESHEET_PERMONTH')) {
		$head[$h][0] = DOL_URL_ROOT."/projet/activity/permonth.php".($param ? '?'.$param : '');
		$head[$h][1] = $langs->trans("InputPerMonth");
		$head[$h][2] = 'inputpermonth';
		$h++;
	}

	if (!getDolGlobalString('PROJECT_DISABLE_TIMESHEET_PERWEEK')) {
		$head[$h][0] = DOL_URL_ROOT."/projet/activity/perweek.php".($param ? '?'.$param : '');
		$head[$h][1] = $langs->trans("InputPerWeek");
		$head[$h][2] = 'inputperweek';
		$h++;
	}

	if (!getDolGlobalString('PROJECT_DISABLE_TIMESHEET_PERTIME')) {
		$head[$h][0] = DOL_URL_ROOT."/projet/activity/perday.php".($param ? '?'.$param : '');
		$head[$h][1] = $langs->trans("InputPerDay");
		$head[$h][2] = 'inputperday';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'project_timesheet');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'project_timesheet', 'remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function project_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('projet');
	$extrafields->fetch_name_optionals_label('projet_task');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/projet/admin/project.php";
	$head[$h][1] = $langs->trans("Projects");
	$head[$h][2] = 'project';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'project_admin');

	$head[$h][0] = DOL_URL_ROOT."/projet/admin/project_extrafields.php";
	$head[$h][1] = $langs->trans("ExtraFieldsProject");
	$nbExtrafields = $extrafields->attributes['projet']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	if (empty($conf->global->PROJECT_HIDE_TASKS)) {
		$head[$h][0] = DOL_URL_ROOT . '/projet/admin/project_task_extrafields.php';
		$head[$h][1] = $langs->trans("ExtraFieldsProjectTask");
		$nbExtrafields = $extrafields->attributes['projet_task']['count'];
		if ($nbExtrafields > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
		}
		$head[$h][2] = 'attributes_task';
		$h++;
	}

	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		$langs->load("members");

		$head[$h][0] = DOL_URL_ROOT.'/projet/admin/website.php';
		$head[$h][1] = $langs->trans("BlankSubscriptionForm");
		$head[$h][2] = 'website';
		$h++;
	}

	complete_head_from_modules($conf, $langs, null, $head, $h, 'project_admin', 'remove');

	return $head;
}


/**
 * Show task lines with a particular parent
 *
 * @param	string	   	$inc				    Line number (start to 0, then increased by recursive call)
 * @param   int 		$parent				    Id of parent task to show (0 to show all)
 * @param   Task[]		$lines				    Array of lines
 * @param   int			$level				    Level (start to 0, then increased/decrease by recursive call), or -1 to show all level in order of $lines without the recursive groupment feature.
 * @param 	string		$var				    Color
 * @param 	int			$showproject		    Show project columns
 * @param	int			$taskrole			    Array of roles of user for each tasks
 * @param	string		$projectsListId		    List of id of project allowed to user (string separated with comma)
 * @param	int			$addordertick		    Add a tick to move task
 * @param   int         $projectidfortotallink  0 or Id of project to use on total line (link to see all time consumed for project)
 * @param   string      $dummy					Not used.
 * @param   int         $showbilltime           Add the column 'TimeToBill' and 'TimeBilled'
 * @param   array       $arrayfields            Array with displayed column information
 * @param   array       $arrayofselected        Array with selected fields
 * @return	int									Nb of tasks shown
 */
function projectLinesa(&$inc, $parent, &$lines, &$level, $var, $showproject, &$taskrole, $projectsListId = '', $addordertick = 0, $projectidfortotallink = 0, $dummy = '', $showbilltime = 0, $arrayfields = array(), $arrayofselected = array())
{
	global $user, $langs, $conf, $db, $hookmanager;
	global $projectstatic, $taskstatic, $extrafields;

	$lastprojectid = 0;

	$projectsArrayId = explode(',', $projectsListId);

	$numlines = count($lines);

	// We declare counter as global because we want to edit them into recursive call
	global $total_projectlinesa_spent, $total_projectlinesa_planned, $total_projectlinesa_spent_if_planned, $total_projectlinesa_declared_if_planned, $total_projectlinesa_tobill, $total_projectlinesa_billed, $total_budget_amount;
	global $totalarray;

	if ($level == 0) {
		$total_projectlinesa_spent = 0;
		$total_projectlinesa_planned = 0;
		$total_projectlinesa_spent_if_planned = 0;
		$total_projectlinesa_declared_if_planned = 0;
		$total_projectlinesa_tobill = 0;
		$total_projectlinesa_billed = 0;
		$total_budget_amount = 0;
		$totalarray = array();
	}

	for ($i = 0; $i < $numlines; $i++) {
		if ($parent == 0 && $level >= 0) {
			$level = 0; // if $level = -1, we don't use sublevel recursion, we show all lines
		}

		// Process line
		// print "i:".$i."-".$lines[$i]->fk_project.'<br>';
		if ($lines[$i]->fk_task_parent == $parent || $level < 0) {       // if $level = -1, we don't use sublevel recursion, we show all lines
			// Show task line.
			$showline = 1;
			$showlineingray = 0;

			// If there is filters to use
			if (is_array($taskrole)) {
				// If task not legitimate to show, search if a legitimate task exists later in tree
				if (!isset($taskrole[$lines[$i]->id]) && $lines[$i]->id != $lines[$i]->fk_task_parent) {
					// So search if task has a subtask legitimate to show
					$foundtaskforuserdeeper = 0;
					searchTaskInChild($foundtaskforuserdeeper, $lines[$i]->id, $lines, $taskrole);
					//print '$foundtaskforuserpeeper='.$foundtaskforuserdeeper.'<br>';
					if ($foundtaskforuserdeeper > 0) {
						$showlineingray = 1; // We will show line but in gray
					} else {
						$showline = 0; // No reason to show line
					}
				}
			} else {
				// Caller did not ask to filter on tasks of a specific user (this probably means he want also tasks of all users, into public project
				// or into all other projects if user has permission to).
				if (!$user->hasRight('projet', 'all', 'lire')) {
					// User is not allowed on this project and project is not public, so we hide line
					if (!in_array($lines[$i]->fk_project, $projectsArrayId)) {
						// Note that having a user assigned to a task into a project user has no permission on, should not be possible
						// because assignment on task can be done only on contact of project.
						// If assignment was done and after, was removed from contact of project, then we can hide the line.
						$showline = 0;
					}
				}
			}

			if ($showline) {
				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
					$var = !$var;
					$lastprojectid = $lines[$i]->fk_project;
				}

				print '<tr class="oddeven" id="row-'.$lines[$i]->id.'">'."\n";

				$projectstatic->id = $lines[$i]->fk_project;
				$projectstatic->ref = $lines[$i]->projectref;
				$projectstatic->public = $lines[$i]->public;
				$projectstatic->title = $lines[$i]->projectlabel;
				$projectstatic->usage_bill_time = $lines[$i]->usage_bill_time;
				$projectstatic->status = $lines[$i]->projectstatus;

				$taskstatic->id = $lines[$i]->id;
				$taskstatic->ref = $lines[$i]->ref;
				$taskstatic->label = (!empty($taskrole[$lines[$i]->id]) ? $langs->trans("YourRole").': '.$taskrole[$lines[$i]->id] : '');
				$taskstatic->projectstatus = $lines[$i]->projectstatus;
				$taskstatic->progress = $lines[$i]->progress;
				$taskstatic->fk_statut = $lines[$i]->status;	// deprecated
				$taskstatic->status = $lines[$i]->status;
				$taskstatic->date_start = $lines[$i]->date_start;
				$taskstatic->date_end = $lines[$i]->date_end;
				$taskstatic->datee = $lines[$i]->date_end; // deprecated
				$taskstatic->planned_workload = $lines[$i]->planned_workload;
				$taskstatic->duration_effective = $lines[$i]->duration_effective;
				$taskstatic->budget_amount = $lines[$i]->budget_amount;
				$taskstatic->billable = $lines[$i]->billable;

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					$selected = 0;
					if (in_array($lines[$i]->id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb' . $lines[$i]->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $lines[$i]->id . '"' . ($selected ? ' checked="checked"' : '') . '>';
					print '</td>';
				}

				if ($showproject) {
					// Project ref
					print '<td class="nowraponall">';
					//if ($showlineingray) print '<i>';
					if ($lines[$i]->public || in_array($lines[$i]->fk_project, $projectsArrayId) || $user->hasRight('projet', 'all', 'lire')) {
						print $projectstatic->getNomUrl(1);
					} else {
						print $projectstatic->getNomUrl(1, 'nolink');
					}
					//if ($showlineingray) print '</i>';
					print "</td>";

					// Project status
					print '<td>';
					$projectstatic->statut = $lines[$i]->projectstatus;
					print $projectstatic->getLibStatut(2);
					print "</td>";
				}

				// Ref of task
				if (count($arrayfields) > 0 && !empty($arrayfields['t.ref']['checked'])) {
					print '<td class="nowraponall">';
					if ($showlineingray) {
						print '<i>'.img_object('', 'projecttask').' '.$lines[$i]->ref.'</i>';
					} else {
						print $taskstatic->getNomUrl(1, 'withproject');
					}
					print '</td>';
				}

				// Title of task
				if (count($arrayfields) > 0 && !empty($arrayfields['t.label']['checked'])) {
					$labeltoshow = '';
					if ($showlineingray) {
						$labeltoshow .= '<i>';
					}
					//else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$lines[$i]->id.'&withproject=1">';
					for ($k = 0; $k < $level; $k++) {
						$labeltoshow .= '<div class="marginleftonly">';
					}
					$labeltoshow .= dol_escape_htmltag($lines[$i]->label);
					for ($k = 0; $k < $level; $k++) {
						$labeltoshow .= '</div>';
					}
					if ($showlineingray) {
						$labeltoshow .= '</i>';
					}
					print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($labeltoshow).'">';
					print $labeltoshow;
					print "</td>\n";
				}

				if (count($arrayfields) > 0 && !empty($arrayfields['t.description']['checked'])) {
					print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($lines[$i]->description).'">';
					print $lines[$i]->description;
					print "</td>\n";
				}

				// Date start
				if (count($arrayfields) > 0 && !empty($arrayfields['t.dateo']['checked'])) {
					print '<td class="center nowraponall">';
					print dol_print_date($lines[$i]->date_start, 'dayhour');
					print '</td>';
				}

				// Date end
				if (count($arrayfields) > 0 && !empty($arrayfields['t.datee']['checked'])) {
					print '<td class="center nowraponall">';
					print dol_print_date($lines[$i]->date_end, 'dayhour');
					if ($taskstatic->hasDelay()) {
						print img_warning($langs->trans("Late"));
					}
					print '</td>';
				}

				$plannedworkloadoutputformat = 'allhourmin';
				$timespentoutputformat = 'allhourmin';
				if (getDolGlobalString('PROJECT_PLANNED_WORKLOAD_FORMAT')) {
					$plannedworkloadoutputformat = getDolGlobalString('PROJECT_PLANNED_WORKLOAD_FORMAT');
				}
				if (getDolGlobalString('PROJECT_TIMES_SPENT_FORMAT')) {
					$timespentoutputformat = getDolGlobalString('PROJECT_TIME_SPENT_FORMAT');
				}

				// Planned Workload (in working hours)
				if (count($arrayfields) > 0 && !empty($arrayfields['t.planned_workload']['checked'])) {
					print '<td class="right">';
					$fullhour = convertSecondToTime($lines[$i]->planned_workload, $plannedworkloadoutputformat);
					$workingdelay = convertSecondToTime($lines[$i]->planned_workload, 'all', 86400, 7); // TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
					if ($lines[$i]->planned_workload != '') {
						print $fullhour;
						// TODO Add delay taking account of working hours per day and working day per week
						//if ($workingdelay != $fullhour) print '<br>('.$workingdelay.')';
					}
					//else print '--:--';
					print '</td>';
				}

				// Time spent
				if (count($arrayfields) > 0 && !empty($arrayfields['t.duration_effective']['checked'])) {
					print '<td class="right">';
					if ($showlineingray) {
						print '<i>';
					} else {
						print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.($showproject ? '' : '&withproject=1').'">';
					}
					if ($lines[$i]->duration_effective) {
						print convertSecondToTime($lines[$i]->duration_effective, $timespentoutputformat);
					} else {
						print '--:--';
					}
					if ($showlineingray) {
						print '</i>';
					} else {
						print '</a>';
					}
					print '</td>';
				}

				// Progress calculated (Note: ->duration_effective is time spent)
				if (count($arrayfields) > 0 && !empty($arrayfields['t.progress_calculated']['checked'])) {
					$s = '';
					$shtml = '';
					if ($lines[$i]->planned_workload || $lines[$i]->duration_effective) {
						if ($lines[$i]->planned_workload) {
							$s = round(100 * (float) $lines[$i]->duration_effective / (float) $lines[$i]->planned_workload, 2).' %';
							$shtml = $s;
						} else {
							$s = $langs->trans('WorkloadNotDefined');
							$shtml = '<span class="opacitymedium">'.$s.'</span>';
						}
					}
					print '<td class="right tdoverflowmax100" title="'.dol_escape_htmltag($s).'">';
					print $shtml;
					print '</td>';
				}

				// Progress declared
				if (count($arrayfields) > 0 && !empty($arrayfields['t.progress']['checked'])) {
					print '<td class="right">';
					if ($lines[$i]->progress != '') {
						print getTaskProgressBadge($taskstatic);
					}
					print '</td>';
				}

				// resume
				if (count($arrayfields) > 0 && !empty($arrayfields['t.progress_summary']['checked'])) {
					print '<td class="right">';
					if ($lines[$i]->progress != '' && $lines[$i]->duration_effective) {
						print getTaskProgressView($taskstatic, false, false);
					}
					print '</td>';
				}

				if ($showbilltime) {
					// Time not billed
					if (count($arrayfields) > 0 && !empty($arrayfields['t.tobill']['checked'])) {
						print '<td class="right">';
						if ($lines[$i]->usage_bill_time) {
							print convertSecondToTime($lines[$i]->tobill, 'allhourmin');
							$total_projectlinesa_tobill += $lines[$i]->tobill;
						} else {
							print '<span class="opacitymedium">'.$langs->trans("NA").'</span>';
						}
						print '</td>';
					}

					// Time billed
					if (count($arrayfields) > 0 && !empty($arrayfields['t.billed']['checked'])) {
						print '<td class="right">';
						if ($lines[$i]->usage_bill_time) {
							print convertSecondToTime($lines[$i]->billed, 'allhourmin');
							$total_projectlinesa_billed += $lines[$i]->billed;
						} else {
							print '<span class="opacitymedium">'.$langs->trans("NA").'</span>';
						}
						print '</td>';
					}
				}

				// Budget task
				if (count($arrayfields) > 0 && !empty($arrayfields['t.budget_amount']['checked'])) {
					print '<td class="center">';
					if ($lines[$i]->budget_amount) {
						print '<span class="amount">'.price($lines[$i]->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
						$total_budget_amount += $lines[$i]->budget_amount;
					}
					print '</td>';
				}

				// Contacts of task
				if (count($arrayfields) > 0 && !empty($arrayfields['c.assigned']['checked'])) {
					print '<td class="center">';
					$ifisrt = 1;
					foreach (array('internal', 'external') as $source) {
						//$tab = $lines[$i]->liste_contact(-1, $source);
						$tab = $lines[$i]->liste_contact(-1, $source, 0, '', 1);

						$numcontact = count($tab);
						if (!empty($numcontact)) {
							foreach ($tab as $contacttask) {
								//var_dump($contacttask);
								if ($source == 'internal') {
									$c = new User($db);
								} else {
									$c = new Contact($db);
								}
								$c->fetch($contacttask['id']);
								if (!empty($c->photo)) {
									if (get_class($c) == 'User') {
										print $c->getNomUrl(-2, '', 0, 0, 24, 1, '', ($ifisrt ? '' : 'notfirst'));
									} else {
										print $c->getNomUrl(-2, '', 0, '', -1, 0, ($ifisrt ? '' : 'notfirst'));
									}
								} else {
									if (get_class($c) == 'User') {
										print $c->getNomUrl(2, '', 0, 0, 24, 1, '', ($ifisrt ? '' : 'notfirst'));
									} else {
										print $c->getNomUrl(2, '', 0, '', -1, 0, ($ifisrt ? '' : 'notfirst'));
									}
								}
								$ifisrt = 0;
							}
						}
					}
					print '</td>';
				}

				// Billable
				if (count($arrayfields) > 0 && !empty($arrayfields['t.billable']['checked'])) {
					print '<td class="center">';
					if ($lines[$i]->billable) {
						print '<span>'.$langs->trans('Yes').'</span>';
					} else {
						print '<span>'.$langs->trans('No').'</span>';
					}
					print '</td>';
				}

				// Extra fields
				$extrafieldsobjectkey = $taskstatic->table_element;
				$extrafieldsobjectprefix = 'efpt.';
				$obj = $lines[$i];
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
				// Fields from hook
				$parameters = array('arrayfields' => $arrayfields, 'obj' => $lines[$i]);
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Tick to drag and drop
				print '<td class="tdlineupdown center"></td>';

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					$selected = 0;
					if (in_array($lines[$i]->id, $arrayofselected)) {
						$selected = 1;
					}
					print '<input id="cb' . $lines[$i]->id . '" class="flat checkforselect" type="checkbox" name="toselect[]" value="' . $lines[$i]->id . '"' . ($selected ? ' checked="checked"' : '') . '>';

					print '</td>';
				}

				print "</tr>\n";

				if (!$showlineingray) {
					$inc++;
				}

				if ($level >= 0) {    // Call sublevels
					$level++;
					if ($lines[$i]->id) {
						projectLinesa($inc, $lines[$i]->id, $lines, $level, $var, $showproject, $taskrole, $projectsListId, $addordertick, $projectidfortotallink, '', $showbilltime, $arrayfields);
					}
					$level--;
				}

				$total_projectlinesa_spent += $lines[$i]->duration_effective;
				$total_projectlinesa_planned += $lines[$i]->planned_workload;
				if ($lines[$i]->planned_workload) {
					$total_projectlinesa_spent_if_planned += $lines[$i]->duration_effective;
				}
				if ($lines[$i]->planned_workload) {
					$total_projectlinesa_declared_if_planned += (float) $lines[$i]->planned_workload * $lines[$i]->progress / 100;
				}
			}
		} else {
			//$level--;
		}
	}

	// Total line
	if (($total_projectlinesa_planned > 0 || $total_projectlinesa_spent > 0 || $total_projectlinesa_tobill > 0 || $total_projectlinesa_billed > 0 || $total_budget_amount > 0)
		&& $level <= 0) {
		print '<tr class="liste_total nodrag nodrop">';

		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_total"></td>';
		}

		print '<td class="liste_total">'.$langs->trans("Total").'</td>';
		if ($showproject) {
			print '<td></td><td></td>';
		}
		if (count($arrayfields) > 0 && !empty($arrayfields['t.label']['checked'])) {
			print '<td></td>';
		}
		if (count($arrayfields) > 0 && !empty($arrayfields['t.description']['checked'])) {
			print '<td></td>';
		}
		if (count($arrayfields) > 0 && !empty($arrayfields['t.dateo']['checked'])) {
			print '<td></td>';
		}
		if (count($arrayfields) > 0 && !empty($arrayfields['t.datee']['checked'])) {
			print '<td></td>';
		}
		if (count($arrayfields) > 0 && !empty($arrayfields['t.planned_workload']['checked'])) {
			print '<td class="nowrap liste_total right">';
			print convertSecondToTime($total_projectlinesa_planned, 'allhourmin');
			print '</td>';
		}
		if (count($arrayfields) > 0 && !empty($arrayfields['t.duration_effective']['checked'])) {
			print '<td class="nowrap liste_total right">';
			if ($projectidfortotallink > 0) {
				print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?projectid='.$projectidfortotallink.($showproject ? '' : '&withproject=1').'">';
			}
			print convertSecondToTime($total_projectlinesa_spent, 'allhourmin');
			if ($projectidfortotallink > 0) {
				print '</a>';
			}
			print '</td>';
		}

		if ($total_projectlinesa_planned) {
			$totalAverageDeclaredProgress = round(100 * $total_projectlinesa_declared_if_planned / $total_projectlinesa_planned, 2);
			$totalCalculatedProgress = round(100 * $total_projectlinesa_spent / $total_projectlinesa_planned, 2);

			// this conf is actually hidden, by default we use 10% for "be careful or warning"
			$warningRatio = getDolGlobalString('PROJECT_TIME_SPEND_WARNING_PERCENT') ? (1 + $conf->global->PROJECT_TIME_SPEND_WARNING_PERCENT / 100) : 1.10;

			// define progress color according to time spend vs workload
			$progressBarClass = 'progress-bar-info';
			$badgeClass = 'badge ';

			if ($totalCalculatedProgress > $totalAverageDeclaredProgress) {
				$progressBarClass = 'progress-bar-danger';
				$badgeClass .= 'badge-danger';
			} elseif ($totalCalculatedProgress * $warningRatio >= $totalAverageDeclaredProgress) { // warning if close at 1%
				$progressBarClass = 'progress-bar-warning';
				$badgeClass .= 'badge-warning';
			} else {
				$progressBarClass = 'progress-bar-success';
				$badgeClass .= 'badge-success';
			}
		}

		// Computed progress
		if (count($arrayfields) > 0 && !empty($arrayfields['t.progress_calculated']['checked'])) {
			print '<td class="nowrap liste_total right">';
			if ($total_projectlinesa_planned) {
				print $totalCalculatedProgress.' %';
			}
			print '</td>';
		}

		// Declared progress
		if (count($arrayfields) > 0 && !empty($arrayfields['t.progress']['checked'])) {
			print '<td class="nowrap liste_total right">';
			if ($total_projectlinesa_planned) {
				print '<span class="'.$badgeClass.'" >'.$totalAverageDeclaredProgress.' %</span>';
			}
			print '</td>';
		}


		// Progress
		if (count($arrayfields) > 0 && !empty($arrayfields['t.progress_summary']['checked'])) {
			print '<td class="right">';
			if ($total_projectlinesa_planned) {
				print '</span>';
				print '    <div class="progress sm" title="'.$totalAverageDeclaredProgress.'%" >';
				print '        <div class="progress-bar '.$progressBarClass.'" style="width: '.$totalAverageDeclaredProgress.'%"></div>';
				print '    </div>';
				print '</div>';
			}
			print '</td>';
		}

		if ($showbilltime) {
			if (count($arrayfields) > 0 && !empty($arrayfields['t.tobill']['checked'])) {
				print '<td class="nowrap liste_total right">';
				print convertSecondToTime($total_projectlinesa_tobill, 'allhourmin');
				print '</td>';
			}
			if (count($arrayfields) > 0 && !empty($arrayfields['t.billed']['checked'])) {
				print '<td class="nowrap liste_total right">';
				print convertSecondToTime($total_projectlinesa_billed, 'allhourmin');
				print '</td>';
			}
		}

		// Budget task
		if (count($arrayfields) > 0 && !empty($arrayfields['t.budget_amount']['checked'])) {
			print '<td class="nowrap liste_total center">';
			if (strcmp((string) $total_budget_amount, '')) {
				print price($total_budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
			}
			print '</td>';
		}

		// Contacts of task for backward compatibility,
		if (getDolGlobalString('PROJECT_SHOW_CONTACTS_IN_LIST')) {
			print '<td></td>';
		}
		// Contacts of task
		if (count($arrayfields) > 0 && !empty($arrayfields['c.assigned']['checked'])) {
			print '<td></td>';
		}

		// Check if Extrafields is totalizable
		if (!empty($extrafields->attributes['projet_task']['totalizable'])) {
			foreach ($extrafields->attributes['projet_task']['totalizable'] as $key => $value) {
				if (!empty($arrayfields['efpt.'.$key]['checked']) && $arrayfields['efpt.'.$key]['checked'] == 1) {
					print '<td class="right">';
					if ($value == 1) {
						print empty($totalarray['totalizable'][$key]['total']) ? '' : $totalarray['totalizable'][$key]['total'];
					}
					print '</td>';
				}
			}
		}

		// Column for the drag and drop
		print '<td class="liste_total"></td>';

		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_total"></td>';
		}

		print '</tr>';
	}

	return $inc;
}


/**
 * Output a task line into a pertime input mode
 *
 * @param	string	   	$inc					Line number (start to 0, then increased by recursive call)
 * @param   int 		$parent					Id of parent task to show (0 to show all)
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   Task[]		$lines					Array of lines
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	string		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is a task i am affected to
 * @param	int			$preselectedday			Preselected day
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and subtasks
 */
function projectLinesPerAction(&$inc, $parent, $fuser, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, $preselectedday, &$isavailable, $oldprojectforbreak = 0)
{
	global $conf, $db, $user, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$lastprojectid = 0;
	$totalforeachline = array();
	$workloadforid = array();
	$lineswithoutlevel0 = array();

	$numlines = count($lines);

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) { // Always and only if at first level
		for ($i = 0; $i < $numlines; $i++) {
			if ($lines[$i]->fk_task_parent) {
				$lineswithoutlevel0[] = $lines[$i];
			}
		}
	}

	if (empty($oldprojectforbreak)) {
		$oldprojectforbreak = (!getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT') ? 0 : -1); // 0 to start break , -1 no break
	}

	//dol_syslog('projectLinesPerDay inc='.$inc.' preselectedday='.$preselectedday.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));
	for ($i = 0; $i < $numlines; $i++) {
		if ($parent == 0) {
			$level = 0;
		}

		//if ($lines[$i]->fk_task_parent == $parent)
		//{
		// If we want all or we have a role on task, we show it
		if (empty($mine) || !empty($tasksrole[$lines[$i]->id])) {
			//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

			// Break on a new project
			if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
				$lastprojectid = $lines[$i]->fk_project;
				if ($preselectedday) {
					$projectstatic->id = $lines[$i]->fk_project;
				}
			}

			if (empty($workloadforid[$projectstatic->id])) {
				if ($preselectedday) {
					$projectstatic->loadTimeSpent($preselectedday, 0, $fuser->id); // Load time spent from table element_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
					$workloadforid[$projectstatic->id] = 1;
				}
			}

			$projectstatic->id = $lines[$i]->fk_project;
			$projectstatic->ref = $lines[$i]->project_ref;
			$projectstatic->title = $lines[$i]->project_label;
			$projectstatic->public = $lines[$i]->public;
			$projectstatic->status = $lines[$i]->project->status;

			$taskstatic->id = $lines[$i]->fk_statut;
			$taskstatic->ref = ($lines[$i]->task_ref ? $lines[$i]->task_ref : $lines[$i]->task_id);
			$taskstatic->label = $lines[$i]->task_label;
			$taskstatic->date_start = $lines[$i]->date_start;
			$taskstatic->date_end = $lines[$i]->date_end;

			$thirdpartystatic->id = $lines[$i]->socid;
			$thirdpartystatic->name = $lines[$i]->thirdparty_name;
			$thirdpartystatic->email = $lines[$i]->thirdparty_email;

			if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id)) {
				print '<tr class="oddeven trforbreak nobold">'."\n";
				print '<td colspan="11">';
				print $projectstatic->getNomUrl(1, '', 0, $langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
				if ($projectstatic->title) {
					print ' - ';
					print $projectstatic->title;
				}
				print '</td>';
				print '</tr>';
			}

			if ($oldprojectforbreak != -1) {
				$oldprojectforbreak = $projectstatic->id;
			}

			print '<tr class="oddeven">'."\n";

			// User
			/*
			 print '<td class="nowrap">';
			 print $fuser->getNomUrl(1, 'withproject', 'time');
			 print '</td>';
			 */

			// Project
			print "<td>";
			if ($oldprojectforbreak == -1) {
				print $projectstatic->getNomUrl(1, '', 0, $langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
				print '<br>'.$projectstatic->title;
			}
			print "</td>";

			// Thirdparty
			print '<td class="tdoverflowmax100">';
			if ($thirdpartystatic->id > 0) {
				print $thirdpartystatic->getNomUrl(1, 'project', 10);
			}
			print '</td>';

			// Ref
			print '<td>';
			print '<!-- Task id = '.$lines[$i]->id.' (projectlinesperaction) -->';
			for ($k = 0; $k < $level; $k++) {
				print '<div class="marginleftonly">';
			}
			print $taskstatic->getNomUrl(1, 'withproject', 'time');
			// Label task
			print '<br>';
			print '<div class="opacitymedium tdoverflowmax500" title="'.dol_escape_htmltag($taskstatic->label).'">'.dol_escape_htmltag($taskstatic->label).'</div>';
			for ($k = 0; $k < $level; $k++) {
				print "</div>";
			}
			print "</td>\n";

			// Date
			print '<td class="center">';
			print dol_print_date($lines[$i]->timespent_datehour, 'day');
			print '</td>';

			$disabledproject = 1;
			$disabledtask = 1;
			//print "x".$lines[$i]->fk_project;
			//var_dump($lines[$i]);
			//var_dump($projectsrole[$lines[$i]->fk_project]);
			// If at least one role for project
			if ($lines[$i]->public || !empty($projectsrole[$lines[$i]->fk_project]) || $user->hasRight('projet', 'all', 'creer')) {
				$disabledproject = 0;
				$disabledtask = 0;
			}
			// If $restricteditformytask is on and I have no role on task, i disable edit
			if ($restricteditformytask && empty($tasksrole[$lines[$i]->id])) {
				$disabledtask = 1;
			}

			// Hour
			print '<td class="nowrap center">';
			print dol_print_date($lines[$i]->timespent_datehour, 'hour');
			print '</td>';

			$cssonholiday = '';
			if (!$isavailable[$preselectedday]['morning'] && !$isavailable[$preselectedday]['afternoon']) {
				$cssonholiday .= 'onholidayallday ';
			} elseif (!$isavailable[$preselectedday]['morning']) {
				$cssonholiday .= 'onholidaymorning ';
			} elseif (!$isavailable[$preselectedday]['afternoon']) {
				$cssonholiday .= 'onholidayafternoon ';
			}

			// Duration
			print '<td class="duration'.($cssonholiday ? ' '.$cssonholiday : '').' center">';

			$dayWorkLoad = $lines[$i]->timespent_duration;
			$totalforeachline[$preselectedday] += $lines[$i]->timespent_duration;

			$alreadyspent = '';
			if ($dayWorkLoad > 0) {
				$alreadyspent = convertSecondToTime($lines[$i]->timespent_duration, 'allhourmin');
			}

			print convertSecondToTime($lines[$i]->timespent_duration, 'allhourmin');

			print '</td>';

			// Note
			print '<td class="center">';
			print '<textarea name="'.$lines[$i]->id.'note" rows="'.ROWS_2.'" id="'.$lines[$i]->id.'note"'.($disabledtask ? ' disabled="disabled"' : '').'>';
			print $lines[$i]->timespent_note;
			print '</textarea>';
			print '</td>';

			// Warning
			print '<td class="right">';
			/*if ((! $lines[$i]->public) && $disabledproject) print $form->textwithpicto('',$langs->trans("UserIsNotContactOfProject"));
			elseif ($disabledtask)
			{
				$titleassigntask = $langs->trans("AssignTaskToMe");
				if ($fuser->id != $user->id) $titleassigntask = $langs->trans("AssignTaskToUser", '...');

				print $form->textwithpicto('',$langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
			}*/
			print '</td>';

			print "</tr>\n";
		}
		//}
		//else
		//{
		//$level--;
		//}
	}

	return $totalforeachline;
}


/**
 * Output a task line into a pertime input mode
 *
 * @param	string	   	$inc					Line number (start to 0, then increased by recursive call)
 * @param   int 		$parent					Id of parent task to show (0 to show all)
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   Task[]		$lines					Array of lines
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	int 		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param	int			$preselectedday			Preselected day
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @param	array		$arrayfields		    Array of additional column
 * @param	Extrafields	$extrafields		    Object extrafields
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and subtasks
 */
function projectLinesPerDay(&$inc, $parent, $fuser, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, $preselectedday, &$isavailable, $oldprojectforbreak = 0, $arrayfields = array(), $extrafields = null)
{
	global $conf, $db, $user, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$lastprojectid = 0;
	$totalforeachday = array();
	$workloadforid = array();
	$lineswithoutlevel0 = array();

	$numlines = count($lines);

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) { // Always and only if at first level
		for ($i = 0; $i < $numlines; $i++) {
			if ($lines[$i]->fk_task_parent) {
				$lineswithoutlevel0[] = $lines[$i];
			}
		}
	}

	if (empty($oldprojectforbreak)) {
		$oldprojectforbreak = (!getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT') ? 0 : -1); // 0 to start break , -1 no break
	}

	$restrictBefore = null;

	if (getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$restrictBefore = dol_time_plus_duree(dol_now(), -1 * getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'), 'm');
	}

	//dol_syslog('projectLinesPerDay inc='.$inc.' preselectedday='.$preselectedday.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));
	for ($i = 0; $i < $numlines; $i++) {
		if ($parent == 0) {
			$level = 0;
		}

		if ($lines[$i]->fk_task_parent == $parent) {
			$obj = &$lines[$i]; // To display extrafields

			// If we want all or we have a role on task, we show it
			if (empty($mine) || !empty($tasksrole[$lines[$i]->id])) {
				//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

				if ($restricteditformytask == 2 && empty($tasksrole[$lines[$i]->id])) {	// we have no role on task and we request to hide such cases
					continue;
				}

				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
					$lastprojectid = $lines[$i]->fk_project;
					if ($preselectedday) {
						$projectstatic->id = $lines[$i]->fk_project;
					}
				}

				if (empty($workloadforid[$projectstatic->id])) {
					if ($preselectedday) {
						$projectstatic->loadTimeSpent($preselectedday, 0, $fuser->id); // Load time spent from table element_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
						$workloadforid[$projectstatic->id] = 1;
					}
				}

				$projectstatic->id = $lines[$i]->fk_project;
				$projectstatic->ref = $lines[$i]->projectref;
				$projectstatic->title = $lines[$i]->projectlabel;
				$projectstatic->public = $lines[$i]->public;
				$projectstatic->status = $lines[$i]->projectstatus;

				$taskstatic->id = $lines[$i]->id;
				$taskstatic->ref = ($lines[$i]->ref ? $lines[$i]->ref : $lines[$i]->id);
				$taskstatic->label = $lines[$i]->label;
				$taskstatic->date_start = $lines[$i]->date_start;
				$taskstatic->date_end = $lines[$i]->date_end;

				$thirdpartystatic->id = $lines[$i]->socid;
				$thirdpartystatic->name = $lines[$i]->thirdparty_name;
				$thirdpartystatic->email = $lines[$i]->thirdparty_email;

				if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id)) {
					$addcolspan = 0;
					if (!empty($arrayfields['t.planned_workload']['checked'])) {
						$addcolspan++;
					}
					if (!empty($arrayfields['t.progress']['checked'])) {
						$addcolspan++;
					}
					foreach ($arrayfields as $key => $val) {
						if ($val['checked'] && substr($key, 0, 5) == 'efpt.') {
							$addcolspan++;
						}
					}

					print '<tr class="oddeven trforbreak nobold">'."\n";
					print '<td colspan="'.(7 + $addcolspan).'">';
					print $projectstatic->getNomUrl(1, '', 0, '<strong>'.$langs->transnoentitiesnoconv("YourRole").':</strong> '.$projectsrole[$lines[$i]->fk_project]);
					if ($thirdpartystatic->id > 0) {
						print ' - '.$thirdpartystatic->getNomUrl(1);
					}
					if ($projectstatic->title) {
						print ' - ';
						print '<span class="secondary">'.$projectstatic->title.'</span>';
					}
					/*
					$colspan=5+(empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)?0:2);
					print '<table class="">';

					print '<tr class="liste_titre">';

					// PROJECT fields
					if (!empty($arrayfields['p.fk_opp_status']['checked'])) print_liste_field_titre($arrayfields['p.fk_opp_status']['label'], $_SERVER["PHP_SELF"], 'p.fk_opp_status', "", $param, '', $sortfield, $sortorder, 'center ');
					if (!empty($arrayfields['p.opp_amount']['checked']))    print_liste_field_titre($arrayfields['p.opp_amount']['label'], $_SERVER["PHP_SELF"], 'p.opp_amount', "", $param, '', $sortfield, $sortorder, 'right ');
					if (!empty($arrayfields['p.opp_percent']['checked']))   print_liste_field_titre($arrayfields['p.opp_percent']['label'], $_SERVER["PHP_SELF"], 'p.opp_percent', "", $param, '', $sortfield, $sortorder, 'right ');
					if (!empty($arrayfields['p.budget_amount']['checked'])) print_liste_field_titre($arrayfields['p.budget_amount']['label'], $_SERVER["PHP_SELF"], 'p.budget_amount', "", $param, '', $sortfield, $sortorder, 'right ');
					if (!empty($arrayfields['p.usage_bill_time']['checked']))     print_liste_field_titre($arrayfields['p.usage_bill_time']['label'], $_SERVER["PHP_SELF"], 'p.usage_bill_time', "", $param, '', $sortfield, $sortorder, 'right ');

					$extrafieldsobjectkey='projet';
					$extrafieldsobjectprefix='efp.';
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

					print '</tr>';
					print '<tr>';

					// PROJECT fields
					if (!empty($arrayfields['p.fk_opp_status']['checked']))
					{
						print '<td class="nowrap">';
						$code = dol_getIdFromCode($db, $lines[$i]->fk_opp_status, 'c_lead_status', 'rowid', 'code');
						if ($code) print $langs->trans("OppStatus".$code);
						print "</td>\n";
					}
					if (!empty($arrayfields['p.opp_amount']['checked']))
					{
						print '<td class="nowrap">';
						print price($lines[$i]->opp_amount, 0, $langs, 1, 0, -1, $conf->currency);
						print "</td>\n";
					}
					if (!empty($arrayfields['p.opp_percent']['checked']))
					{
						print '<td class="nowrap">';
						print price($lines[$i]->opp_percent, 0, $langs, 1, 0).' %';
						print "</td>\n";
					}
					if (!empty($arrayfields['p.budget_amount']['checked']))
					{
						print '<td class="nowrap">';
						print price($lines[$i]->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
						print "</td>\n";
					}
					if (!empty($arrayfields['p.usage_bill_time']['checked']))
					{
						print '<td class="nowrap">';
						print yn($lines[$i]->usage_bill_time);
						print "</td>\n";
					}

					$extrafieldsobjectkey='projet';
					$extrafieldsobjectprefix='efp.';
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

					print '</tr>';
					print '</table>';

					*/
					print '</td>';
					print '</tr>';
				}

				if ($oldprojectforbreak != -1) {
					$oldprojectforbreak = $projectstatic->id;
				}

				print '<tr class="oddeven" data-taskid="'.$lines[$i]->id.'">'."\n";

				// User
				/*
				print '<td class="nowrap">';
				print $fuser->getNomUrl(1, 'withproject', 'time');
				print '</td>';
				*/

				// Project
				if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
					print "<td>";
					if ($oldprojectforbreak == -1) {
						print $projectstatic->getNomUrl(1, '', 0, $langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
					}
					print "</td>";
				}

				// Thirdparty
				if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
					print '<td class="tdoverflowmax100">';
					if ($thirdpartystatic->id > 0) {
						print $thirdpartystatic->getNomUrl(1, 'project', 10);
					}
					print '</td>';
				}

				// Ref
				print '<td>';
				print '<!-- Task id = '.$lines[$i]->id.' (projectlinesperday) -->';
				for ($k = 0; $k < $level; $k++) {
					print '<div class="marginleftonly">';
				}
				print $taskstatic->getNomUrl(1, 'withproject', 'time');
				// Label task
				print '<br>';
				print '<div class="opacitymedium tdoverflowmax500" title="'.dol_escape_htmltag($taskstatic->label).'">'.dol_escape_htmltag($taskstatic->label).'</div>';
				for ($k = 0; $k < $level; $k++) {
					print "</div>";
				}
				print "</td>\n";

				// TASK extrafields
				$extrafieldsobjectkey = 'projet_task';
				$extrafieldsobjectprefix = 'efpt.';
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

				// Planned Workload
				if (!empty($arrayfields['t.planned_workload']['checked'])) {
					print '<td class="leftborder plannedworkload right">';
					if ($lines[$i]->planned_workload) {
						print convertSecondToTime($lines[$i]->planned_workload, 'allhourmin');
					} else {
						print '--:--';
					}
					print '</td>';
				}

				// Progress declared %
				if (!empty($arrayfields['t.progress']['checked'])) {
					print '<td class="right">';
					print $formother->select_percent($lines[$i]->progress, $lines[$i]->id.'progress');
					print '</td>';
				}

				if (!empty($arrayfields['timeconsumed']['checked'])) {
					// Time spent by everybody
					print '<td class="right">';
					// $lines[$i]->duration_effective is a denormalised field = summ of time spent by everybody for task. What we need is time consumed by user
					if ($lines[$i]->duration_effective) {
						print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'">';
						print convertSecondToTime($lines[$i]->duration_effective, 'allhourmin');
						print '</a>';
					} else {
						print '--:--';
					}
					print "</td>\n";

					// Time spent by user
					print '<td class="right">';
					$tmptimespent = $taskstatic->getSummaryOfTimeSpent($fuser->id);
					if ($tmptimespent['total_duration']) {
						print convertSecondToTime($tmptimespent['total_duration'], 'allhourmin');
					} else {
						print '--:--';
					}
					print "</td>\n";
				}

				$disabledproject = 1;
				$disabledtask = 1;
				//print "x".$lines[$i]->fk_project;
				//var_dump($lines[$i]);
				//var_dump($projectsrole[$lines[$i]->fk_project]);
				// If at least one role for project
				if ($lines[$i]->public || !empty($projectsrole[$lines[$i]->fk_project]) || $user->hasRight('projet', 'all', 'creer')) {
					$disabledproject = 0;
					$disabledtask = 0;
				}
				// If $restricteditformytask is on and I have no role on task, i disable edit
				if ($restricteditformytask && empty($tasksrole[$lines[$i]->id])) {
					$disabledtask = 1;
				}

				if ($restrictBefore && $preselectedday < $restrictBefore) {
					$disabledtask = 1;
				}

				// Select hour
				print '<td class="nowraponall leftborder center minwidth150imp borderleft">';
				$tableCell = $form->selectDate($preselectedday, $lines[$i]->id, 1, 1, 2, "addtime", 0, 0, $disabledtask);
				print $tableCell;
				print '</td>';

				$cssonholiday = '';
				if (!$isavailable[$preselectedday]['morning'] && !$isavailable[$preselectedday]['afternoon']) {
					$cssonholiday .= 'onholidayallday ';
				} elseif (!$isavailable[$preselectedday]['morning']) {
					$cssonholiday .= 'onholidaymorning ';
				} elseif (!$isavailable[$preselectedday]['afternoon']) {
					$cssonholiday .= 'onholidayafternoon ';
				}

				global $daytoparse;
				$tmparray = dol_getdate($daytoparse, true); // detail of current day

				$idw = ($tmparray['wday'] - (!getDolGlobalString('MAIN_START_WEEK') ? 0 : 1));
				global $numstartworkingday, $numendworkingday;
				$cssweekend = '';
				if ((($idw + 1) < $numstartworkingday) || (($idw + 1) > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
					$cssweekend = 'weekend';
				}

				// Duration
				print '<td class="center duration'.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').'">';
				$dayWorkLoad = empty($projectstatic->weekWorkLoadPerTask[$preselectedday][$lines[$i]->id]) ? 0 : $projectstatic->weekWorkLoadPerTask[$preselectedday][$lines[$i]->id];
				if (!isset($totalforeachday[$preselectedday])) {
					$totalforeachday[$preselectedday] = 0;
				}
				$totalforeachday[$preselectedday] += $dayWorkLoad;

				$alreadyspent = '';
				if ($dayWorkLoad > 0) {
					$alreadyspent = convertSecondToTime($dayWorkLoad, 'allhourmin');
				}

				$idw = 0;

				$tableCell = '';
				$tableCell .= '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center width40" disabled id="timespent['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="'.$alreadyspent.'"></span>';
				$tableCell .= '<span class="hideonsmartphone"> + </span>';
				//$tableCell.='&nbsp;&nbsp;&nbsp;';
				$tableCell .= $form->select_duration($lines[$i]->id.'duration', '', $disabledtask, 'text', 0, 1);
				//$tableCell.='&nbsp;<input type="submit" class="button"'.($disabledtask?' disabled':'').' value="'.$langs->trans("Add").'">';
				print $tableCell;

				print '</td>';

				// Note
				print '<td class="center">';
				print '<textarea name="'.$lines[$i]->id.'note" rows="'.ROWS_2.'" id="'.$lines[$i]->id.'note"'.($disabledtask ? ' disabled="disabled"' : '').'>';
				print '</textarea>';
				print '</td>';

				// Warning
				print '<td class="right">';
				if ((!$lines[$i]->public) && $disabledproject) {
					print $form->textwithpicto('', $langs->trans("UserIsNotContactOfProject"));
				} elseif ($disabledtask) {
					$titleassigntask = $langs->trans("AssignTaskToMe");
					if ($fuser->id != $user->id) {
						$titleassigntask = $langs->trans("AssignTaskToUser", '...');
					}

					print $form->textwithpicto('', $langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
				}
				print '</td>';

				print "</tr>\n";
			}

			$inc++;
			$level++;
			if ($lines[$i]->id > 0) {
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level);
				//var_dump($totalforeachday);
				$ret = projectLinesPerDay($inc, $lines[$i]->id, $fuser, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $preselectedday, $isavailable, $oldprojectforbreak, $arrayfields, $extrafields);
				//var_dump('ret with parent='.$lines[$i]->id.' level='.$level);
				//var_dump($ret);
				foreach ($ret as $key => $val) {
					$totalforeachday[$key] += $val;
				}
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level.' + subtasks');
				//var_dump($totalforeachday);
			}
			$level--;
		} else {
			//$level--;
		}
	}

	return $totalforeachday;
}


/**
 * Output a task line into a perday input mode
 *
 * @param	string	   	$inc					Line output identificator (start to 0, then increased by recursive call)
 * @param	int			$firstdaytoshow			First day to show
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   int 		$parent					Id of parent task to show (0 to show all)
 * @param   Task[]		$lines					Array of lines (list of tasks but we will show only if we have a specific role on task)
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	int 		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @param	array		$arrayfields		    Array of additional column
 * @param	Extrafields	$extrafields		    Object extrafields
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and subtasks
 */
function projectLinesPerWeek(&$inc, $firstdaytoshow, $fuser, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, &$isavailable, $oldprojectforbreak = 0, $arrayfields = array(), $extrafields = null)
{
	global $conf, $db, $user, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$numlines = count($lines);

	$lastprojectid = 0;
	$workloadforid = array();
	$totalforeachday = array();
	$lineswithoutlevel0 = array();

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) { // Always and only if at first level
		for ($i = 0; $i < $numlines; $i++) {
			if ($lines[$i]->fk_task_parent) {
				$lineswithoutlevel0[] = $lines[$i];
			}
		}
	}

	//dol_syslog('projectLinesPerWeek inc='.$inc.' firstdaytoshow='.$firstdaytoshow.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));

	if (empty($oldprojectforbreak)) {
		$oldprojectforbreak = (!getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT') ? 0 : -1); // 0 = start break, -1 = never break
	}

	$restrictBefore = null;

	if (getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$restrictBefore = dol_time_plus_duree(dol_now(), -1 * getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'), 'm');
	}

	for ($i = 0; $i < $numlines; $i++) {
		if ($parent == 0) {
			$level = 0;
		}

		if ($lines[$i]->fk_task_parent == $parent) {
			$obj = &$lines[$i]; // To display extrafields

			// If we want all or we have a role on task, we show it
			if (empty($mine) || !empty($tasksrole[$lines[$i]->id])) {
				//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

				if ($restricteditformytask == 2 && empty($tasksrole[$lines[$i]->id])) {	// we have no role on task and we request to hide such cases
					continue;
				}

				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
					$lastprojectid = $lines[$i]->fk_project;
					$projectstatic->id = $lines[$i]->fk_project;
				}

				//var_dump('--- '.$level.' '.$firstdaytoshow.' '.$fuser->id.' '.$projectstatic->id.' '.$workloadforid[$projectstatic->id]);
				//var_dump($projectstatic->weekWorkLoadPerTask);
				if (empty($workloadforid[$projectstatic->id])) {
					$projectstatic->loadTimeSpent($firstdaytoshow, 0, $fuser->id); // Load time spent from table element_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
					$workloadforid[$projectstatic->id] = 1;
				}
				//var_dump($projectstatic->weekWorkLoadPerTask);
				//var_dump('--- '.$projectstatic->id.' '.$workloadforid[$projectstatic->id]);

				$projectstatic->id = $lines[$i]->fk_project;
				$projectstatic->ref = $lines[$i]->projectref;
				$projectstatic->title = $lines[$i]->projectlabel;
				$projectstatic->public = $lines[$i]->public;
				$projectstatic->thirdparty_name = $lines[$i]->thirdparty_name;
				$projectstatic->status = $lines[$i]->projectstatus;

				$taskstatic->id = $lines[$i]->id;
				$taskstatic->ref = ($lines[$i]->ref ? $lines[$i]->ref : $lines[$i]->id);
				$taskstatic->label = $lines[$i]->label;
				$taskstatic->date_start = $lines[$i]->date_start;
				$taskstatic->date_end = $lines[$i]->date_end;

				$thirdpartystatic->id = $lines[$i]->thirdparty_id;
				$thirdpartystatic->name = $lines[$i]->thirdparty_name;
				$thirdpartystatic->email = $lines[$i]->thirdparty_email;

				if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id)) {
					$addcolspan = 0;
					if (!empty($arrayfields['t.planned_workload']['checked'])) {
						$addcolspan++;
					}
					if (!empty($arrayfields['t.progress']['checked'])) {
						$addcolspan++;
					}
					foreach ($arrayfields as $key => $val) {
						if ($val['checked'] && substr($key, 0, 5) == 'efpt.') {
							$addcolspan++;
						}
					}

					print '<tr class="oddeven trforbreak nobold">'."\n";
					print '<td colspan="'.(11 + $addcolspan).'">';
					print $projectstatic->getNomUrl(1, '', 0, '<strong>'.$langs->transnoentitiesnoconv("YourRole").':</strong> '.$projectsrole[$lines[$i]->fk_project]);
					if ($thirdpartystatic->id > 0) {
						print ' - '.$thirdpartystatic->getNomUrl(1);
					}
					if ($projectstatic->title) {
						print ' - ';
						print '<span class="secondary">'.$projectstatic->title.'</span>';
					}

					/*$colspan=5+(empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)?0:2);
					print '<table class="">';

					print '<tr class="liste_titre">';

					// PROJECT fields
					if (!empty($arrayfields['p.fk_opp_status']['checked'])) print_liste_field_titre($arrayfields['p.fk_opp_status']['label'], $_SERVER["PHP_SELF"], 'p.fk_opp_status', "", $param, '', $sortfield, $sortorder, 'center ');
					if (!empty($arrayfields['p.opp_amount']['checked']))    print_liste_field_titre($arrayfields['p.opp_amount']['label'], $_SERVER["PHP_SELF"], 'p.opp_amount', "", $param, '', $sortfield, $sortorder, 'right ');
					if (!empty($arrayfields['p.opp_percent']['checked']))   print_liste_field_titre($arrayfields['p.opp_percent']['label'], $_SERVER["PHP_SELF"], 'p.opp_percent', "", $param, '', $sortfield, $sortorder, 'right ');
					if (!empty($arrayfields['p.budget_amount']['checked'])) print_liste_field_titre($arrayfields['p.budget_amount']['label'], $_SERVER["PHP_SELF"], 'p.budget_amount', "", $param, '', $sortfield, $sortorder, 'right ');
					if (!empty($arrayfields['p.usage_bill_time']['checked']))     print_liste_field_titre($arrayfields['p.usage_bill_time']['label'], $_SERVER["PHP_SELF"], 'p.usage_bill_time', "", $param, '', $sortfield, $sortorder, 'right ');

					$extrafieldsobjectkey='projet';
					$extrafieldsobjectprefix='efp.';
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

					print '</tr>';
					print '<tr>';

					// PROJECT fields
					if (!empty($arrayfields['p.fk_opp_status']['checked']))
					{
						print '<td class="nowrap">';
						$code = dol_getIdFromCode($db, $lines[$i]->fk_opp_status, 'c_lead_status', 'rowid', 'code');
						if ($code) print $langs->trans("OppStatus".$code);
						print "</td>\n";
					}
					if (!empty($arrayfields['p.opp_amount']['checked']))
					{
						print '<td class="nowrap">';
						print price($lines[$i]->opp_amount, 0, $langs, 1, 0, -1, $conf->currency);
						print "</td>\n";
					}
					if (!empty($arrayfields['p.opp_percent']['checked']))
					{
						print '<td class="nowrap">';
						print price($lines[$i]->opp_percent, 0, $langs, 1, 0).' %';
						print "</td>\n";
					}
					if (!empty($arrayfields['p.budget_amount']['checked']))
					{
						print '<td class="nowrap">';
						print price($lines[$i]->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
						print "</td>\n";
					}
					if (!empty($arrayfields['p.usage_bill_time']['checked']))
					{
						print '<td class="nowrap">';
						print yn($lines[$i]->usage_bill_time);
						print "</td>\n";
					}

					$extrafieldsobjectkey='projet';
					$extrafieldsobjectprefix='efp.';
					include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

					print '</tr>';
					print '</table>';
					*/

					print '</td>';
					print '</tr>';
				}

				if ($oldprojectforbreak != -1) {
					$oldprojectforbreak = $projectstatic->id;
				}

				print '<tr class="oddeven" data-taskid="'.$lines[$i]->id.'">'."\n";

				// User
				/*
				print '<td class="nowrap">';
				print $fuser->getNomUrl(1, 'withproject', 'time');
				print '</td>';
				*/

				// Project
				if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
					print '<td class="nowrap">';
					if ($oldprojectforbreak == -1) {
						print $projectstatic->getNomUrl(1, '', 0, $langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
					}
					print "</td>";
				}

				// Thirdparty
				if (getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT')) {
					print '<td class="tdoverflowmax100">';
					if ($thirdpartystatic->id > 0) {
						print $thirdpartystatic->getNomUrl(1, 'project');
					}
					print '</td>';
				}

				// Ref
				print '<td class="tdoverflowmax300">';
				print '<!-- Task id = '.$lines[$i]->id.' (projectlinesperweek) -->';
				for ($k = 0; $k < $level; $k++) {
					print '<div class="marginleftonly">';
				}
				print $taskstatic->getNomUrl(1, 'withproject', 'time');
				// Label task
				print '<br>';
				print '<div class="opacitymedium tdoverflowmax500" title="'.dol_escape_htmltag($taskstatic->label).'">'.dol_escape_htmltag($taskstatic->label).'</div>';
				for ($k = 0; $k < $level; $k++) {
					print "</div>";
				}
				print "</td>\n";

				// TASK extrafields
				$extrafieldsobjectkey = 'projet_task';
				$extrafieldsobjectprefix = 'efpt.';
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

				// Planned Workload
				if (!empty($arrayfields['t.planned_workload']['checked'])) {
					print '<td class="leftborder plannedworkload right">';
					if ($lines[$i]->planned_workload) {
						print convertSecondToTime($lines[$i]->planned_workload, 'allhourmin');
					} else {
						print '--:--';
					}
					print '</td>';
				}

				if (!empty($arrayfields['t.progress']['checked'])) {
					// Progress declared %
					print '<td class="right">';
					print $formother->select_percent($lines[$i]->progress, $lines[$i]->id.'progress');
					print '</td>';
				}

				if (!empty($arrayfields['timeconsumed']['checked'])) {
					// Time spent by everybody
					print '<td class="right">';
					// $lines[$i]->duration_effective is a denormalised field = summ of time spent by everybody for task. What we need is time consumed by user
					if ($lines[$i]->duration_effective) {
						print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.((int) $lines[$i]->id).'">';
						print convertSecondToTime($lines[$i]->duration_effective, 'allhourmin');
						print '</a>';
					} else {
						print '--:--';
					}
					print "</td>\n";

					// Time spent by user
					print '<td class="right">';
					$tmptimespent = $taskstatic->getSummaryOfTimeSpent($fuser->id);
					if ($tmptimespent['total_duration']) {
						print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.((int) $lines[$i]->id).'&search_user='.((int) $fuser->id).'">';
						print convertSecondToTime($tmptimespent['total_duration'], 'allhourmin');
						print '</a>';
					} else {
						print '--:--';
					}
					print "</td>\n";
				}

				$disabledproject = 1;
				$disabledtask = 1;
				//print "x".$lines[$i]->fk_project;
				//var_dump($lines[$i]);
				//var_dump($projectsrole[$lines[$i]->fk_project]);
				// If at least one role for project
				if ($lines[$i]->public || !empty($projectsrole[$lines[$i]->fk_project]) || $user->hasRight('projet', 'all', 'creer')) {
					$disabledproject = 0;
					$disabledtask = 0;
				}
				// If $restricteditformytask is on and I have no role on task, i disable edit
				if ($restricteditformytask && empty($tasksrole[$lines[$i]->id])) {
					$disabledtask = 1;
				}

				//var_dump($projectstatic->weekWorkLoadPerTask);

				// Fields to show current time
				$tableCell = '';
				$modeinput = 'hours';
				$j = 0;
				for ($idw = 0; $idw < 7; $idw++) {
					$j++;
					$tmpday = dol_time_plus_duree($firstdaytoshow, $idw, 'd');
					if (!isset($totalforeachday[$tmpday])) {
						$totalforeachday[$tmpday] = 0;
					}
					$cssonholiday = '';
					if (!$isavailable[$tmpday]['morning'] && !$isavailable[$tmpday]['afternoon']) {
						$cssonholiday .= 'onholidayallday ';
					} elseif (!$isavailable[$tmpday]['morning']) {
						$cssonholiday .= 'onholidaymorning ';
					} elseif (!$isavailable[$tmpday]['afternoon']) {
						$cssonholiday .= 'onholidayafternoon ';
					}

					$tmparray = dol_getdate($tmpday);
					$dayWorkLoad = (!empty($projectstatic->weekWorkLoadPerTask[$tmpday][$lines[$i]->id]) ? $projectstatic->weekWorkLoadPerTask[$tmpday][$lines[$i]->id] : 0);
					$totalforeachday[$tmpday] += $dayWorkLoad;

					$alreadyspent = '';
					if ($dayWorkLoad > 0) {
						$alreadyspent = convertSecondToTime($dayWorkLoad, 'allhourmin');
					}
					$alttitle = $langs->trans("AddHereTimeSpentForDay", !empty($tmparray['day']) ? $tmparray['day'] : 0, $tmparray['mon']);

					global $numstartworkingday, $numendworkingday;
					$cssweekend = '';
					if (($idw + 1 < $numstartworkingday) || ($idw + 1 > $numendworkingday)) {	// This is a day is not inside the setup of working days, so we use a week-end css.
						$cssweekend = 'weekend';
					}

					$disabledtaskday = $disabledtask;

					if (! $disabledtask && $restrictBefore && $tmpday < $restrictBefore) {
						$disabledtaskday = 1;
					}

					$tableCell = '<td class="center hide'.$idw.($cssonholiday ? ' '.$cssonholiday : '').($cssweekend ? ' '.$cssweekend : '').($j <= 1 ? ' borderleft' : '').'">';
					//$tableCell .= 'idw='.$idw.' '.$conf->global->MAIN_START_WEEK.' '.$numstartworkingday.'-'.$numendworkingday;
					$placeholder = '';
					if ($alreadyspent) {
						$tableCell .= '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd width40" disabled id="timespent['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="'.$alreadyspent.'"></span>';
						//$placeholder=' placeholder="00:00"';
						//$tableCell.='+';
					}
					$tableCell .= '<input type="text" alt="'.($disabledtaskday ? '' : $alttitle).'" title="'.($disabledtaskday ? '' : $alttitle).'" '.($disabledtaskday ? 'disabled' : $placeholder).' class="center smallpadd width40" id="timeadded['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="" cols="2"  maxlength="5"';
					$tableCell .= ' onkeypress="return regexEvent(this,event,\'timeChar\')"';
					$tableCell .= ' onkeyup="updateTotal('.$idw.',\''.$modeinput.'\')"';
					$tableCell .= ' onblur="regexEvent(this,event,\''.$modeinput.'\'); updateTotal('.$idw.',\''.$modeinput.'\')" />';
					$tableCell .= '</td>';
					print $tableCell;
				}

				// Warning
				print '<td class="right">';
				if ((!$lines[$i]->public) && $disabledproject) {
					print $form->textwithpicto('', $langs->trans("UserIsNotContactOfProject"));
				} elseif ($disabledtask) {
					$titleassigntask = $langs->trans("AssignTaskToMe");
					if ($fuser->id != $user->id) {
						$titleassigntask = $langs->trans("AssignTaskToUser", '...');
					}

					print $form->textwithpicto('', $langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
				}
				print '</td>';

				print "</tr>\n";
			}

			// Call to show task with a lower level (task under the current task)
			$inc++;
			$level++;
			if ($lines[$i]->id > 0) {
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level);
				//var_dump($totalforeachday);
				$ret = projectLinesPerWeek($inc, $firstdaytoshow, $fuser, $lines[$i]->id, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $isavailable, $oldprojectforbreak, $arrayfields, $extrafields);
				//var_dump('ret with parent='.$lines[$i]->id.' level='.$level);
				//var_dump($ret);
				foreach ($ret as $key => $val) {
					$totalforeachday[$key] += $val;
				}
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level.' + subtasks');
				//var_dump($totalforeachday);
			}
			$level--;
		} else {
			//$level--;
		}
	}

	return $totalforeachday;
}

/**
 * Output a task line into a perday input mode
 *
 * @param	string	   	$inc					Line output identificator (start to 0, then increased by recursive call)
 * @param	int			$firstdaytoshow			First day to show
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   int 		$parent					Id of parent task to show (0 to show all)
 * @param   Task[]		$lines					Array of lines (list of tasks but we will show only if we have a specific role on task)
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	int 		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is a task i am affected to
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @param	array		$TWeek					Array of week numbers
 * @param	array		$arrayfields		    Array of additional column
 * @param	Extrafields	$extrafields		    Object extrafields
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and subtasks
 */
function projectLinesPerMonth(&$inc, $firstdaytoshow, $fuser, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, &$isavailable, $oldprojectforbreak = 0, $TWeek = array(), $arrayfields = array(), $extrafields = null)
{
	global $conf, $db, $user, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$numlines = count($lines);

	$lastprojectid = 0;
	$workloadforid = array();
	$totalforeachweek = array();
	$lineswithoutlevel0 = array();

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) { // Always and only if at first level
		for ($i = 0; $i < $numlines; $i++) {
			if ($lines[$i]->fk_task_parent) {
				$lineswithoutlevel0[] = $lines[$i];
			}
		}
	}

	//dol_syslog('projectLinesPerWeek inc='.$inc.' firstdaytoshow='.$firstdaytoshow.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));

	if (empty($oldprojectforbreak)) {
		$oldprojectforbreak = (!getDolGlobalString('PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT') ? 0 : -1); // 0 = start break, -1 = never break
	}

	$restrictBefore = null;

	if (getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		$restrictBefore = dol_time_plus_duree(dol_now(), -1 * getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'), 'm');
	}

	for ($i = 0; $i < $numlines; $i++) {
		if ($parent == 0) {
			$level = 0;
		}

		if ($lines[$i]->fk_task_parent == $parent) {
			// If we want all or we have a role on task, we show it
			if (empty($mine) || !empty($tasksrole[$lines[$i]->id])) {
				//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

				if ($restricteditformytask == 2 && empty($tasksrole[$lines[$i]->id])) {	// we have no role on task and we request to hide such cases
					continue;
				}

				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid) {
					$lastprojectid = $lines[$i]->fk_project;
					$projectstatic->id = $lines[$i]->fk_project;
				}

				//var_dump('--- '.$level.' '.$firstdaytoshow.' '.$fuser->id.' '.$projectstatic->id.' '.$workloadforid[$projectstatic->id]);
				//var_dump($projectstatic->weekWorkLoadPerTask);
				if (empty($workloadforid[$projectstatic->id])) {
					$projectstatic->loadTimeSpentMonth($firstdaytoshow, 0, $fuser->id); // Load time spent from table element_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
					$workloadforid[$projectstatic->id] = 1;
				}
				//var_dump($projectstatic->weekWorkLoadPerTask);
				//var_dump('--- '.$projectstatic->id.' '.$workloadforid[$projectstatic->id]);

				$projectstatic->id = $lines[$i]->fk_project;
				$projectstatic->ref = $lines[$i]->projectref;
				$projectstatic->title = $lines[$i]->projectlabel;
				$projectstatic->public = $lines[$i]->public;
				$projectstatic->thirdparty_name = $lines[$i]->thirdparty_name;
				$projectstatic->status = $lines[$i]->projectstatus;

				$taskstatic->id = $lines[$i]->id;
				$taskstatic->ref = ($lines[$i]->ref ? $lines[$i]->ref : $lines[$i]->id);
				$taskstatic->label = $lines[$i]->label;
				$taskstatic->date_start = $lines[$i]->date_start;
				$taskstatic->date_end = $lines[$i]->date_end;

				$thirdpartystatic->id = $lines[$i]->thirdparty_id;
				$thirdpartystatic->name = $lines[$i]->thirdparty_name;
				$thirdpartystatic->email = $lines[$i]->thirdparty_email;

				if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id)) {
					print '<tr class="oddeven trforbreak nobold">'."\n";
					print '<td colspan="'.(6 + count($TWeek)).'">';
					print $projectstatic->getNomUrl(1, '', 0, '<strong>'.$langs->transnoentitiesnoconv("YourRole").':</strong> '.$projectsrole[$lines[$i]->fk_project]);
					if ($thirdpartystatic->id > 0) {
						print ' - '.$thirdpartystatic->getNomUrl(1);
					}
					if ($projectstatic->title) {
						print ' - ';
						print '<span class="secondary">'.$projectstatic->title.'</span>';
					}
					print '</td>';
					print '</tr>';
				}

				if ($oldprojectforbreak != -1) {
					$oldprojectforbreak = $projectstatic->id;
				}
				print '<tr class="oddeven" data-taskid="'.$lines[$i]->id.'">'."\n";

				// User
				/*
				print '<td class="nowrap">';
				print $fuser->getNomUrl(1, 'withproject', 'time');
				print '</td>';
				*/

				// Project
				/*print '<td class="nowrap">';
				if ($oldprojectforbreak == -1) print $projectstatic->getNomUrl(1,'',0,$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
				print "</td>";*/

				// Thirdparty
				/*print '<td class="tdoverflowmax100">';
				if ($thirdpartystatic->id > 0) print $thirdpartystatic->getNomUrl(1, 'project');
				print '</td>';*/

				// Ref
				print '<td class="nowrap">';
				print '<!-- Task id = '.$lines[$i]->id.' (projectlinespermonth)  -->';
				for ($k = 0; $k < $level; $k++) {
					print '<div class="marginleftonly">';
				}
				print $taskstatic->getNomUrl(1, 'withproject', 'time');
				// Label task
				print '<br>';
				print '<div class="opacitymedium tdoverflowmax500" title="'.dol_escape_htmltag($taskstatic->label).'">'.dol_escape_htmltag($taskstatic->label).'</div>';
				for ($k = 0; $k < $level; $k++) {
					print "</div>";
				}
				print "</td>\n";

				// Planned Workload
				if (!empty($arrayfields['t.planned_workload']['checked'])) {
					print '<td class="leftborder plannedworkload right">';
					if ($lines[$i]->planned_workload) {
						print convertSecondToTime($lines[$i]->planned_workload, 'allhourmin');
					} else {
						print '--:--';
					}
					print '</td>';
				}

				// Progress declared %
				if (!empty($arrayfields['t.progress']['checked'])) {
					print '<td class="right">';
					print $formother->select_percent($lines[$i]->progress, $lines[$i]->id.'progress');
					print '</td>';
				}

				// Time spent by everybody
				if (!empty($arrayfields['timeconsumed']['checked'])) {
					print '<td class="right">';
					// $lines[$i]->duration_effective is a denormalised field = summ of time spent by everybody for task. What we need is time consumed by user
					if ($lines[$i]->duration_effective) {
						print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'">';
						print convertSecondToTime($lines[$i]->duration_effective, 'allhourmin');
						print '</a>';
					} else {
						print '--:--';
					}
					print "</td>\n";

					// Time spent by user
					print '<td class="right">';
					$tmptimespent = $taskstatic->getSummaryOfTimeSpent($fuser->id);
					if ($tmptimespent['total_duration']) {
						print convertSecondToTime($tmptimespent['total_duration'], 'allhourmin');
					} else {
						print '--:--';
					}
					print "</td>\n";
				}

				$disabledproject = 1;
				$disabledtask = 1;
				//print "x".$lines[$i]->fk_project;
				//var_dump($lines[$i]);
				//var_dump($projectsrole[$lines[$i]->fk_project]);
				// If at least one role for project
				if ($lines[$i]->public || !empty($projectsrole[$lines[$i]->fk_project]) || $user->hasRight('projet', 'all', 'creer')) {
					$disabledproject = 0;
					$disabledtask = 0;
				}
				// If $restricteditformytask is on and I have no role on task, i disable edit
				if ($restricteditformytask && empty($tasksrole[$lines[$i]->id])) {
					$disabledtask = 1;
				}

				//var_dump($projectstatic->weekWorkLoadPerTask);
				//TODO
				// Fields to show current time
				$tableCell = '';
				$modeinput = 'hours';
				$TFirstDay = getFirstDayOfEachWeek($TWeek, (int) date('Y', $firstdaytoshow));
				$TFirstDay[reset($TWeek)] = 1;

				$firstdaytoshowarray = dol_getdate($firstdaytoshow);
				$year = $firstdaytoshowarray['year'];
				$month = $firstdaytoshowarray['mon'];
				$j = 0;
				foreach ($TWeek as $weekIndex => $weekNb) {
					$j++;
					$weekWorkLoad = !empty($projectstatic->monthWorkLoadPerTask[$weekNb][$lines[$i]->id]) ? $projectstatic->monthWorkLoadPerTask[$weekNb][$lines[$i]->id] : 0 ;
					if (!isset($totalforeachweek[$weekNb])) {
						$totalforeachweek[$weekNb] = 0;
					}
					$totalforeachweek[$weekNb] += $weekWorkLoad;

					$alreadyspent = '';
					if ($weekWorkLoad > 0) {
						$alreadyspent = convertSecondToTime($weekWorkLoad, 'allhourmin');
					}
					$alttitle = $langs->trans("AddHereTimeSpentForWeek", $weekNb);

					$disabledtaskweek = $disabledtask;
					$firstdayofweek = dol_mktime(0, 0, 0, $month, $TFirstDay[$weekIndex], $year);

					if (! $disabledtask && $restrictBefore && $firstdayofweek < $restrictBefore) {
						$disabledtaskweek = 1;
					}

					$tableCell = '<td class="center hide'.($j <= 1 ? ' borderleft' : '').'">';
					$placeholder = '';
					if ($alreadyspent) {
						$tableCell .= '<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd width40" disabled id="timespent['.$inc.']['.((int) $weekNb).']" name="task['.$lines[$i]->id.']['.$weekNb.']" value="'.$alreadyspent.'"></span>';
						//$placeholder=' placeholder="00:00"';
						//$tableCell.='+';
					}

					$tableCell .= '<input type="text" alt="'.($disabledtaskweek ? '' : $alttitle).'" title="'.($disabledtaskweek ? '' : $alttitle).'" '.($disabledtaskweek ? 'disabled' : $placeholder).' class="center smallpadd width40" id="timeadded['.$inc.']['.((int) $weekNb).']" name="task['.$lines[$i]->id.']['.($TFirstDay[$weekNb] - 1).']" value="" cols="2"  maxlength="5"';
					$tableCell .= ' onkeypress="return regexEvent(this,event,\'timeChar\')"';
					$tableCell .= ' onkeyup="updateTotal('.$weekNb.',\''.$modeinput.'\')"';
					$tableCell .= ' onblur="regexEvent(this,event,\''.$modeinput.'\'); updateTotal('.$weekNb.',\''.$modeinput.'\')" />';
					$tableCell .= '</td>';
					print $tableCell;
				}

				// Warning
				print '<td class="right">';
				if ((!$lines[$i]->public) && $disabledproject) {
					print $form->textwithpicto('', $langs->trans("UserIsNotContactOfProject"));
				} elseif ($disabledtask) {
					$titleassigntask = $langs->trans("AssignTaskToMe");
					if ($fuser->id != $user->id) {
						$titleassigntask = $langs->trans("AssignTaskToUser", '...');
					}

					print $form->textwithpicto('', $langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
				}
				print '</td>';

				print "</tr>\n";
			}

			// Call to show task with a lower level (task under the current task)
			$inc++;
			$level++;
			if ($lines[$i]->id > 0) {
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level);
				//var_dump($totalforeachday);
				$ret = projectLinesPerMonth($inc, $firstdaytoshow, $fuser, $lines[$i]->id, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $isavailable, $oldprojectforbreak, $TWeek);
				//var_dump('ret with parent='.$lines[$i]->id.' level='.$level);
				//var_dump($ret);
				foreach ($ret as $key => $val) {
					$totalforeachweek[$key] += $val;
				}
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level.' + subtasks');
				//var_dump($totalforeachday);
			}
			$level--;
		} else {
			//$level--;
		}
	}

	return $totalforeachweek;
}


/**
 * Search in task lines with a particular parent if there is a task for a particular user (in taskrole)
 *
 * @param 	int		$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	int		$parent				Id of parent task to start
 * @param 	Task[]	$lines				Array of all tasks
 * @param	string	$taskrole			Array of task filtered on a particular user
 * @return	int							1 if there is
 */
function searchTaskInChild(&$inc, $parent, &$lines, &$taskrole)
{
	//print 'Search in line with parent id = '.$parent.'<br>';
	$numlines = count($lines);
	for ($i = 0; $i < $numlines; $i++) {
		// Process line $lines[$i]
		if ($lines[$i]->fk_task_parent == $parent && $lines[$i]->id != $lines[$i]->fk_task_parent) {
			// If task is legitimate to show, no more need to search deeper
			if (isset($taskrole[$lines[$i]->id])) {
				//print 'Found a legitimate task id='.$lines[$i]->id.'<br>';
				$inc++;
				return $inc;
			}

			searchTaskInChild($inc, $lines[$i]->id, $lines, $taskrole);
			//print 'Found inc='.$inc.'<br>';

			if ($inc > 0) {
				return $inc;
			}
		}
	}

	return $inc;
}

/**
 * Return HTML table with list of projects and number of opened tasks
 *
 * @param	DoliDB	$db					Database handler
 * @param	Form	$form				Object form
 * @param   int		$socid				Id thirdparty
 * @param   int		$projectsListId     Id of project I have permission on
 * @param   int		$mytasks            Limited to task I am contact to
 * @param	int		$status				-1=No filter on statut, 0 or 1 = Filter on status
 * @param	array	$listofoppstatus	List of opportunity status
 * @param   array   $hiddenfields       List of info to not show ('projectlabel', 'declaredprogress', '...', )
 * @param	int		$max				Max nb of record to show in HTML list
 * @return	void
 */
function print_projecttasks_array($db, $form, $socid, $projectsListId, $mytasks = 0, $status = -1, $listofoppstatus = array(), $hiddenfields = array(), $max = 0)
{
	global $langs, $conf, $user;
	global $theme_datacolor;

	$maxofloop = (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD);

	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

	$listofstatus = array_keys($listofoppstatus);

	if (is_array($listofstatus) && getDolGlobalString('USE_COLOR_FOR_PROSPECTION_STATUS')) {
		// Define $themeColorId and array $statusOppList for each $listofstatus
		$themeColorId = 0;
		$statusOppList = array();
		foreach ($listofstatus as $oppStatus) {
			$oppStatusCode = dol_getIdFromCode($db, $oppStatus, 'c_lead_status', 'rowid', 'code');
			if ($oppStatusCode) {
				$statusOppList[$oppStatus]['code'] = $oppStatusCode;
				$statusOppList[$oppStatus]['color'] = isset($theme_datacolor[$themeColorId]) ? implode(', ', $theme_datacolor[$themeColorId]) : '';
			}
			$themeColorId++;
		}
	}

	$projectstatic = new Project($db);
	$thirdpartystatic = new Societe($db);

	$sortfield = '';
	$sortorder = '';
	$project_year_filter = 0;

	$title = $langs->trans("Projects");
	if (strcmp((string) $status, '') && $status >= 0) {
		$title = $langs->trans("Projects").' '.$langs->trans($projectstatic->labelStatus[$status]);
	}

	print '<!-- print_projecttasks_array -->';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	$sql = " FROM ".MAIN_DB_PREFIX."projet as p";
	if ($mytasks) {
		$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
		$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
	} else {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
	}
	$sql .= " WHERE p.entity IN (".getEntity('project').")";
	$sql .= " AND p.rowid IN (".$db->sanitize($projectsListId).")";
	if ($socid) {
		$sql .= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
	}
	if ($mytasks) {
		$sql .= " AND p.rowid = t.fk_projet";
		$sql .= " AND ec.element_id = t.rowid";
		$sql .= " AND ec.fk_socpeople = ".((int) $user->id);
		$sql .= " AND ec.fk_c_type_contact = ctc.rowid"; // Replace the 2 lines with ec.fk_c_type_contact in $arrayidtypeofcontact
		$sql .= " AND ctc.element = 'project_task'";
	}
	if ($status >= 0) {
		$sql .= " AND p.fk_statut = ".(int) $status;
	}
	if (getDolGlobalString('PROJECT_LIMIT_YEAR_RANGE')) {
		$project_year_filter = GETPOST("project_year_filter", 'alpha');	// '*' seems allowed
		//Check if empty or invalid year. Wildcard ignores the sql check
		if ($project_year_filter != "*") {
			if (empty($project_year_filter) || !is_numeric($project_year_filter)) {
				$project_year_filter = date("Y");
			}
			$sql .= " AND (p.dateo IS NULL OR p.dateo <= ".$db->idate(dol_get_last_day((int) $project_year_filter, 12, false)).")";
			$sql .= " AND (p.datee IS NULL OR p.datee >= ".$db->idate(dol_get_first_day((int) $project_year_filter, 1, false)).")";
		}
	}

	// Get id of project we must show tasks
	$arrayidofprojects = array();
	$sql1 = "SELECT p.rowid as projectid";
	$sql1 .= $sql;
	$resql = $db->query($sql1);
	if ($resql) {
		$i = 0;
		$num = $db->num_rows($resql);
		while ($i < $num) {
			$objp = $db->fetch_object($resql);
			$arrayidofprojects[$objp->projectid] = $objp->projectid;
			$i++;
		}
	} else {
		dol_print_error($db);
	}
	if (empty($arrayidofprojects)) {
		$arrayidofprojects[0] = -1;
	}

	// Get list of project with calculation on tasks
	$sql2 = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_soc,";
	$sql2 .= " s.rowid as socid, s.nom as socname, s.name_alias,";
	$sql2 .= " s.code_client, s.code_compta, s.client,";
	$sql2 .= " s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur,";
	$sql2 .= " s.logo, s.email, s.entity,";
	$sql2 .= " p.fk_user_creat, p.public, p.fk_statut as status, p.fk_opp_status as opp_status, p.opp_percent, p.opp_amount,";
	$sql2 .= " p.dateo, p.datee,";
	$sql2 .= " COUNT(t.rowid) as nb, SUM(t.planned_workload) as planned_workload, SUM(t.planned_workload * t.progress / 100) as declared_progess_workload";
	$sql2 .= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
	$sql2 .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
	$sql2 .= " WHERE p.rowid IN (".$db->sanitize(implode(',', $arrayidofprojects)).")";
	$sql2 .= " GROUP BY p.rowid, p.ref, p.title, p.fk_soc, s.rowid, s.nom, s.name_alias, s.code_client, s.code_compta, s.client, s.code_fournisseur, s.code_compta_fournisseur, s.fournisseur,";
	$sql2 .= " s.logo, s.email, s.entity, p.fk_user_creat, p.public, p.fk_statut, p.fk_opp_status, p.opp_percent, p.opp_amount, p.dateo, p.datee";
	$sql2 .= " ORDER BY p.title, p.ref";

	$resql = $db->query($sql2);
	if ($resql) {
		$othernb = 0;
		$total_task = 0;
		$total_opp_amount = 0;
		$ponderated_opp_amount = 0;
		$total_plannedworkload = 0;
		$total_declaredprogressworkload = 0;

		$num = $db->num_rows($resql);
		$nbofloop = min($num, (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		$i = 0;

		print '<tr class="liste_titre">';
		print_liste_field_titre($title.'<a href="'.DOL_URL_ROOT.'/projet/list.php?search_status='.((int) $status).'"><span class="badge marginleftonlyshort">'.$num.'</span></a>', $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
		print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "", "", "", "", $sortfield, $sortorder);
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			if (!in_array('prospectionstatus', $hiddenfields)) {
				print_liste_field_titre("OpportunityStatus", "", "", "", "", 'style="max-width: 100px"', $sortfield, $sortorder, 'center ');
			}
			print_liste_field_titre($form->textwithpicto($langs->trans("Amount"), $langs->trans("OpportunityAmount").' ('.$langs->trans("Tooltip").' = '.$langs->trans("OpportunityWeightedAmount").')'), "", "", "", "", 'style="max-width: 100px"', $sortfield, $sortorder, 'right ');
			//print_liste_field_titre('OpportunityWeightedAmount', '', '', '', '', 'align="right"', $sortfield, $sortorder);
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			print_liste_field_titre("Tasks", "", "", "", "", 'align="right"', $sortfield, $sortorder);
			if (!in_array('plannedworkload', $hiddenfields)) {
				print_liste_field_titre("PlannedWorkload", "", "", "", "", 'style="max-width: 100px"', $sortfield, $sortorder, 'right ');
			}
			if (!in_array('declaredprogress', $hiddenfields)) {
				print_liste_field_titre("%", "", "", "", "", '', $sortfield, $sortorder, 'right ', $langs->trans("ProgressDeclared"));
			}
		}
		if (!in_array('projectstatus', $hiddenfields)) {
			print_liste_field_titre("Status", "", "", "", "", '', $sortfield, $sortorder, 'right ');
		}
		print "</tr>\n";

		while ($i < $nbofloop) {
			$objp = $db->fetch_object($resql);

			if ($max && $i >= $max) {
				$othernb++;
				$i++;
				$total_task += $objp->nb;
				$total_opp_amount += $objp->opp_amount;
				$opp_weighted_amount = $objp->opp_percent * $objp->opp_amount / 100;
				$ponderated_opp_amount += price2num($opp_weighted_amount);
				$plannedworkload = $objp->planned_workload;
				$total_plannedworkload += $plannedworkload;
				$declaredprogressworkload = $objp->declared_progess_workload;
				$total_declaredprogressworkload += $declaredprogressworkload;
				continue;
			}

			$projectstatic->id = $objp->projectid;
			$projectstatic->user_author_id = $objp->fk_user_creat;
			$projectstatic->public = $objp->public;

			// Check is user has read permission on project
			$userAccess = $projectstatic->restrictedProjectArea($user);
			if ($userAccess >= 0) {
				$projectstatic->ref = $objp->ref;
				$projectstatic->status = $objp->status;
				$projectstatic->title = $objp->title;
				$projectstatic->date_end = $db->jdate($objp->datee);
				$projectstatic->date_start = $db->jdate($objp->dateo);

				print '<tr class="oddeven">';

				print '<td class="tdoverflowmax150">';
				print $projectstatic->getNomUrl(1, '', 0, '', '-', 0, -1, 'nowraponall');
				if (!in_array('projectlabel', $hiddenfields)) {
					print '<br><span class="opacitymedium small">'.dol_escape_htmltag($objp->title).'</span>';
				}
				print '</td>';

				print '<td class="nowraponall tdoverflowmax100">';
				if ($objp->fk_soc > 0) {
					$thirdpartystatic->id = $objp->socid;
					$thirdpartystatic->name = $objp->socname;
					//$thirdpartystatic->name_alias = $objp->name_alias;
					//$thirdpartystatic->code_client = $objp->code_client;
					$thirdpartystatic->code_compta = $objp->code_compta;
					$thirdpartystatic->code_compta_client = $objp->code_compta;
					$thirdpartystatic->client = $objp->client;
					//$thirdpartystatic->code_fournisseur = $objp->code_fournisseur;
					$thirdpartystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
					$thirdpartystatic->fournisseur = $objp->fournisseur;
					$thirdpartystatic->logo = $objp->logo;
					$thirdpartystatic->email = $objp->email;
					$thirdpartystatic->entity = $objp->entity;
					print $thirdpartystatic->getNomUrl(1);
				}
				print '</td>';

				if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
					if (!in_array('prospectionstatus', $hiddenfields)) {
						print '<td class="center tdoverflowmax75">';
						// Because color of prospection status has no meaning yet, it is used if hidden constant is set
						if (!getDolGlobalString('USE_COLOR_FOR_PROSPECTION_STATUS')) {
							$oppStatusCode = dol_getIdFromCode($db, $objp->opp_status, 'c_lead_status', 'rowid', 'code');
							if ($langs->trans("OppStatus".$oppStatusCode) != "OppStatus".$oppStatusCode) {
								print $langs->trans("OppStatus".$oppStatusCode);
							}
						} else {
							if (isset($statusOppList[$objp->opp_status])) {
								$oppStatusCode = $statusOppList[$objp->opp_status]['code'];
								$oppStatusColor = $statusOppList[$objp->opp_status]['color'];
							} else {
								$oppStatusCode = dol_getIdFromCode($db, $objp->opp_status, 'c_lead_status', 'rowid', 'code');
								$oppStatusColor = '';
							}
							if ($oppStatusCode) {
								if (!empty($oppStatusColor)) {
									print '<a href="'.dol_buildpath('/projet/list.php?search_opp_status='.$objp->opp_status, 1).'" style="display: inline-block; width: 4px; border: 5px solid rgb('.$oppStatusColor.'); border-radius: 2px;" title="'.$langs->trans("OppStatus".$oppStatusCode).'"></a>';
								} else {
									print '<a href="'.dol_buildpath('/projet/list.php?search_opp_status='.$objp->opp_status, 1).'" title="'.$langs->trans("OppStatus".$oppStatusCode).'">'.$oppStatusCode.'</a>';
								}
							}
						}
						print '</td>';
					}

					print '<td class="right">';
					if ($objp->opp_percent && $objp->opp_amount) {
						$opp_weighted_amount = $objp->opp_percent * $objp->opp_amount / 100;
						$alttext = $langs->trans("OpportunityWeightedAmount").' '.price($opp_weighted_amount, 0, '', 1, -1, 0, $conf->currency);
						$ponderated_opp_amount += price2num($opp_weighted_amount);
					}
					if ($objp->opp_amount) {
						print '<span class="amount" title="'.$alttext.'">'.$form->textwithpicto(price($objp->opp_amount, 0, '', 1, -1, 0), $alttext).'</span>';
					}
					print '</td>';
				}

				if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
					print '<td class="right">'.$objp->nb.'</td>';

					$plannedworkload = $objp->planned_workload;
					$total_plannedworkload += $plannedworkload;
					if (!in_array('plannedworkload', $hiddenfields)) {
						print '<td class="right nowraponall">'.($plannedworkload ? convertSecondToTime($plannedworkload) : '').'</td>';
					}
					if (!in_array('declaredprogress', $hiddenfields)) {
						$declaredprogressworkload = $objp->declared_progess_workload;
						$total_declaredprogressworkload += $declaredprogressworkload;
						print '<td class="right nowraponall">';
						//print $objp->planned_workload.'-'.$objp->declared_progess_workload."<br>";
						print($plannedworkload ? round(100 * $declaredprogressworkload / $plannedworkload, 0).'%' : '');
						print '</td>';
					}
				}

				if (!in_array('projectstatus', $hiddenfields)) {
					print '<td class="right">';
					print $projectstatic->getLibStatut(3);
					print '</td>';
				}

				print "</tr>\n";

				$total_task += $objp->nb;
				$total_opp_amount += $objp->opp_amount;
			}

			$i++;
		}

		if ($othernb) {
			print '<tr class="oddeven">';
			print '<td class="nowrap" colspan="5">';
			print '<span class="opacitymedium">'.$langs->trans("More").'...'.($othernb < $maxofloop ? ' ('.$othernb.')' : '').'</span>';
			print '</td>';
			print "</tr>\n";
		}

		print '<tr class="liste_total">';
		print '<td>'.$langs->trans("Total")."</td><td></td>";
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			if (!in_array('prospectionstatus', $hiddenfields)) {
				print '<td class="liste_total"></td>';
			}
			print '<td class="liste_total right">';
			//$form->textwithpicto(price($ponderated_opp_amount, 0, '', 1, -1, -1, $conf->currency), $langs->trans("OpportunityPonderatedAmountDesc"), 1);
			print $form->textwithpicto(price($total_opp_amount, 0, '', 1, -1, 0), $langs->trans("OpportunityPonderatedAmountDesc").' : '.price($ponderated_opp_amount, 0, '', 1, -1, 0, $conf->currency));
			print '</td>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			print '<td class="liste_total right">'.$total_task.'</td>';
			if (!in_array('plannedworkload', $hiddenfields)) {
				print '<td class="liste_total right">'.($total_plannedworkload ? convertSecondToTime($total_plannedworkload) : '').'</td>';
			}
			if (!in_array('declaredprogress', $hiddenfields)) {
				print '<td class="liste_total right">'.($total_plannedworkload ? round(100 * $total_declaredprogressworkload / $total_plannedworkload, 0).'%' : '').'</td>';
			}
		}
		if (!in_array('projectstatus', $hiddenfields)) {
			print '<td class="liste_total"></td>';
		}
		print '</tr>';

		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print "</table>";
	print '</div>';

	if (getDolGlobalString('PROJECT_LIMIT_YEAR_RANGE')) {
		//Add the year filter input
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
		print '<table width="100%">';
		print '<tr>';
		print '<td>'.$langs->trans("Year").'</td>';
		print '<td class="right"><input type="text" size="4" class="flat" name="project_year_filter" value="'.((int) $project_year_filter).'"/>';
		print "</tr>\n";
		print '</table></form>';
	}
}

/**
 * @param   Task        $task               the task object
 * @param   bool|string $label              true = auto, false = don't display, string = replace output
 * @param   bool|string $progressNumber     true = auto, false = don't display, string = replace output
 * @param   bool        $hideOnProgressNull hide if progress is null
 * @param   bool        $spaced             used to add space at bottom (made by css)
 * @return string
 * @see getTaskProgressBadge()
 */
function getTaskProgressView($task, $label = true, $progressNumber = true, $hideOnProgressNull = false, $spaced = false)
{
	global $langs, $conf;

	$out = '';

	$plannedworkloadoutputformat = 'allhourmin';
	$timespentoutputformat = 'allhourmin';
	if (getDolGlobalString('PROJECT_PLANNED_WORKLOAD_FORMAT')) {
		$plannedworkloadoutputformat = getDolGlobalString('PROJECT_PLANNED_WORKLOAD_FORMAT');
	}
	if (getDolGlobalString('PROJECT_TIMES_SPENT_FORMAT')) {
		$timespentoutputformat = getDolGlobalString('PROJECT_TIME_SPENT_FORMAT');
	}

	if (empty($task->progress) && !empty($hideOnProgressNull)) {
		return '';
	}

	$spaced = !empty($spaced) ? 'spaced' : '';

	$diff = '';

	// define progress color according to time spend vs workload
	$progressBarClass = 'progress-bar-info';
	$progressCalculated = 0;
	if ($task->planned_workload) {
		$progressCalculated = round(100 * (float) $task->duration_effective / (float) $task->planned_workload, 2);

		// this conf is actually hidden, by default we use 10% for "be careful or warning"
		$warningRatio = getDolGlobalString('PROJECT_TIME_SPEND_WARNING_PERCENT') ? (1 + $conf->global->PROJECT_TIME_SPEND_WARNING_PERCENT / 100) : 1.10;

		$diffTitle = '<br>'.$langs->trans('ProgressDeclared').' : '.$task->progress.(isset($task->progress) ? '%' : '');
		$diffTitle .= '<br>'.$langs->trans('ProgressCalculated').' : '.$progressCalculated.(isset($progressCalculated) ? '%' : '');

		//var_dump($progressCalculated.' '.$warningRatio.' '.$task->progress.' '.floatval($task->progress * $warningRatio));
		if ((float) $progressCalculated > (float) ($task->progress * $warningRatio)) {
			$progressBarClass = 'progress-bar-danger';
			$title = $langs->trans('TheReportedProgressIsLessThanTheCalculatedProgressionByX', abs($task->progress - $progressCalculated).' '.$langs->trans("point"));
			$diff = '<span class="text-danger classfortooltip paddingrightonly" title="'.dol_htmlentities($title.$diffTitle).'" ><i class="fa fa-caret-down"></i> '.($task->progress - $progressCalculated).'%</span>';
		} elseif ((float) $progressCalculated > (float) $task->progress) { // warning if close at 10%
			$progressBarClass = 'progress-bar-warning';
			$title = $langs->trans('TheReportedProgressIsLessThanTheCalculatedProgressionByX', abs($task->progress - $progressCalculated).' '.$langs->trans("point"));
			$diff = '<span class="text-warning classfortooltip paddingrightonly" title="'.dol_htmlentities($title.$diffTitle).'" ><i class="fa fa-caret-left"></i> '.($task->progress - $progressCalculated).'%</span>';
		} else {
			$progressBarClass = 'progress-bar-success';
			$title = $langs->trans('TheReportedProgressIsMoreThanTheCalculatedProgressionByX', ($task->progress - $progressCalculated).' '.$langs->trans("point"));
			$diff = '<span class="text-success classfortooltip paddingrightonly" title="'.dol_htmlentities($title.$diffTitle).'" ><i class="fa fa-caret-up"></i> '.($task->progress - $progressCalculated).'%</span>';
		}
	}

	$out .= '<div class="progress-group">';

	if ($label !== false) {
		$out .= '    <span class="progress-text">';

		if ($label !== true) {
			$out .= $label; // replace label by param
		} else {
			$out .= $task->getNomUrl(1).' '.dol_htmlentities($task->label);
		}
		$out .= '    </span>';
	}


	if ($progressNumber !== false) {
		$out .= '    <span class="progress-number">';
		if ($progressNumber !== true) {
			$out .= $progressNumber; // replace label by param
		} else {
			if ($task->hasDelay()) {
				$out .= img_warning($langs->trans("Late")).' ';
			}

			$url = DOL_URL_ROOT.'/projet/tasks/time.php?id='.$task->id;

			$out .= !empty($diff) ? $diff.' ' : '';
			$out .= '<a href="'.$url.'" >';
			$out .= '<b title="'.$langs->trans('TimeSpent').'" >';
			if ($task->duration_effective) {
				$out .= convertSecondToTime($task->duration_effective, $timespentoutputformat);
			} else {
				$out .= '--:--';
			}
			$out .= '</b>';
			$out .= '</a>';

			$out .= ' / ';

			$out .= '<a href="'.$url.'" >';
			$out .= '<span title="'.$langs->trans('PlannedWorkload').'" >';
			if ($task->planned_workload) {
				$out .= convertSecondToTime($task->planned_workload, $plannedworkloadoutputformat);
			} else {
				$out .= '--:--';
			}
			$out .= '</a>';
		}
		$out .= '    </span>';
	}


	$out .= '</span>';
	$out .= '    <div class="progress sm'.($spaced ? $spaced : '').'">';
	$diffval = (float) $task->progress - (float) $progressCalculated;
	if ($diffval >= 0) {
		// good
		$out .= '        <div class="progress-bar '.$progressBarClass.'" style="width: '.(float) $task->progress.'%" title="'.(float) $task->progress.'%">';
		if (!empty($task->progress)) {
			$out .= '        <div class="progress-bar progress-bar-consumed" style="width: '.(float) ($progressCalculated / ((float) $task->progress == 0 ? 1 : $task->progress) * 100).'%" title="'.(float) $progressCalculated.'%"></div>';
		}
		$out .= '        </div>';
	} else {
		// bad
		$out .= '        <div class="progress-bar progress-bar-consumed-late" style="width: '.(float) $progressCalculated.'%" title="'.(float) $progressCalculated.'%">';
		$out .= '        <div class="progress-bar '.$progressBarClass.'" style="width: '.($task->progress ? (float) ($task->progress / ((float) $progressCalculated == 0 ? 1 : $progressCalculated) * 100).'%' : '1px').'" title="'.(float) $task->progress.'%"></div>';
		$out .= '        </div>';
	}
	$out .= '    </div>';
	$out .= '</div>';



	return $out;
}
/**
 * @param   Task    $task       the task object
 * @param   string  $label      empty = auto (progress), string = replace output
 * @param   string  $tooltip    empty = auto , string = replace output
 * @return  string
 * @see getTaskProgressView()
 */
function getTaskProgressBadge($task, $label = '', $tooltip = '')
{
	global $conf, $langs;

	$out = '';
	$badgeClass = '';
	if ($task->progress != '') {
		// TODO : manage 100%

		// define color according to time spend vs workload
		$badgeClass = 'badge ';
		if ($task->planned_workload) {
			$progressCalculated = round(100 * (float) $task->duration_effective / (float) $task->planned_workload, 2);

			// this conf is actually hidden, by default we use 10% for "be careful or warning"
			$warningRatio = getDolGlobalString('PROJECT_TIME_SPEND_WARNING_PERCENT') ? (1 + $conf->global->PROJECT_TIME_SPEND_WARNING_PERCENT / 100) : 1.10;

			if ((float) $progressCalculated > (float) ($task->progress * $warningRatio)) {
				$badgeClass .= 'badge-danger';
				if (empty($tooltip)) {
					$tooltip = $task->progress.'% < '.$langs->trans("TimeConsumed").' '.$progressCalculated.'%';
				}
			} elseif ((float) $progressCalculated > (float) $task->progress) { // warning if close at 10%
				$badgeClass .= 'badge-warning';
				if (empty($tooltip)) {
					$tooltip = $task->progress.'% < '.$langs->trans("TimeConsumed").' '.$progressCalculated.'%';
				}
			} else {
				$badgeClass .= 'badge-success';
				if (empty($tooltip)) {
					$tooltip = $task->progress.'% >= '.$langs->trans("TimeConsumed").' '.$progressCalculated.'%';
				}
			}
		}
	}

	$title = '';
	if (!empty($tooltip)) {
		$badgeClass .= ' classfortooltip';
		$title = 'title="'.dol_htmlentities($tooltip).'"';
	}

	if (empty($label)) {
		$label = $task->progress.' %';
	}

	if (!empty($label)) {
		$out = '<span class="'.$badgeClass.'" '.$title.' >'.$label.'</span>';
	}

	return $out;
}
