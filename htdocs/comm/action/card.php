<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2013  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2014       Cedric GROSS            <c.gross@kreiz-it.fr>
 * Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2019	      Ferran Marcet	          <fmarcet@2byte.es>
 * Copyright (C) 2024		    MDW						          <mdeweerd@users.noreply.github.com>
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
 *    \file       htdocs/comm/action/card.php
 *    \ingroup    agenda
 *    \brief      Page for event card
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncommreminder.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';


// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "commercial", "bills", "orders", "agenda", "mails"));

// Get Parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$socpeopleassigned = GETPOST('socpeopleassigned', 'array');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOSTINT('originid');
$confirm = GETPOST('confirm', 'alpha');

$fulldayevent = GETPOST('fullday', 'alpha');

$aphour = GETPOSTINT('aphour');
$apmin = GETPOSTINT('apmin');
$p2hour = GETPOSTINT('p2hour');
$p2min = GETPOSTINT('p2min');

$addreminder = GETPOST('addreminder', 'alpha');
$offsetvalue = GETPOSTINT('offsetvalue');
$offsetunit = GETPOST('offsetunittype_duration', 'aZ09');
$remindertype = GETPOST('selectremindertype', 'aZ09');
$modelmail = GETPOSTINT('actioncommsendmodel_mail');
$complete = GETPOST('complete', 'alpha');	// 'na' must be allowed
$private = GETPOST('private', 'alphanohtml');
if ($complete == 'na' || $complete == -2) {
	$complete = -1;
}

$tzforfullday = null;
if ($fulldayevent) {
	$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
	// For "full day" events, we must store date in GMT (It must be viewed as same moment everywhere)
	$datep = dol_mktime(0, 0, 0, GETPOSTINT("apmonth"), GETPOSTINT("apday"), GETPOSTINT("apyear"), $tzforfullday ? $tzforfullday : 'tzuserrel');
	$datef = dol_mktime(23, 59, 59, GETPOSTINT("p2month"), GETPOSTINT("p2day"), GETPOSTINT("p2year"), $tzforfullday ? $tzforfullday : 'tzuserrel');
	//print $db->idate($datep); exit;
} else {
	$datep = dol_mktime($aphour, $apmin, 0, GETPOSTINT("apmonth"), GETPOSTINT("apday"), GETPOSTINT("apyear"), 'tzuserrel');
	$datef = dol_mktime($p2hour, $p2min, 59, GETPOSTINT("p2month"), GETPOSTINT("p2day"), GETPOSTINT("p2year"), 'tzuserrel');
}
$reg = array();
if (GETPOST('datep')) {
	if (GETPOST('datep') == 'now') {
		$datep = dol_now();
	} elseif (preg_match('/^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$/', GETPOST("datep"), $reg)) {		// Try to not use this. Use instead '&datep=now'
		$datep = dol_mktime(0, 0, 0, (int) $reg[2], (int) $reg[3], (int) $reg[1], 'tzuserrel');
	}
}

// Security check
$socid = GETPOSTINT('socid');
$id = GETPOSTINT('id');
if ($user->socid && ($socid != $user->socid)) {
	accessforbidden();
}

$error = GETPOST("error");
$donotclearsession = GETPOST('donotclearsession') ? GETPOST('donotclearsession') : 0;

// Initialize Objects
$object = new ActionComm($db);
$cactioncomm = new CActionComm($db);
$contact = new Contact($db);
$extrafields = new ExtraFields($db);
$formfile = new FormFile($db);

$form = new Form($db);
$formfile = new FormFile($db);
$formactions = new FormActions($db);

// Load object
if ($id > 0 && $action != 'add') {
	$ret = $object->fetch($id);
	$ret1 = 0;
	if ($ret > 0) {
		$ret = $object->fetch_optionals();
		$ret1 = $object->fetch_userassigned();
	}
	if ($ret < 0 || $ret1 < 0) {
		dol_print_error(null, $object->error);
	}
}

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('actioncard', 'globalcard'));

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$TRemindTypes = array();
if (getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
	$TRemindTypes['browser'] = array('label' => $langs->trans('BrowserPush'), 'disabled' => (!getDolGlobalString('AGENDA_REMINDER_BROWSER') ? 1 : 0));
}
if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
	$TRemindTypes['email'] = array('label' => $langs->trans('EMail'), 'disabled' => (!getDolGlobalString('AGENDA_REMINDER_EMAIL') ? 1 : 0));
}

$TDurationTypes = array('y' => $langs->trans('Years'), 'm' => $langs->trans('Month'), 'w' => $langs->trans('Weeks'), 'd' => $langs->trans('Days'), 'h' => $langs->trans('Hours'), 'i' => $langs->trans('Minutes'));

$result = restrictedArea($user, 'agenda', $object, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');

$usercancreate = $user->hasRight('agenda', 'allactions', 'create') || ((empty($object->id) || $object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'));


/*
 * Actions
 */

$listUserAssignedUpdated = false;
$listResourceAssignedUpdated = false;
$assignedtouser = array();

// Remove user to assigned list
if (empty($reshook) && (GETPOST('removedassigned') || GETPOST('removedassigned') == '0')) {
	$idtoremove = GETPOST('removedassigned');

	if (!empty($_SESSION['assignedtouser'])) {
		$tmpassigneduserids = json_decode($_SESSION['assignedtouser'], true);
	} else {
		$tmpassigneduserids = array();
	}

	foreach ($tmpassigneduserids as $key => $val) {
		if ($val['id'] == $idtoremove || $val['id'] == -1) {
			unset($tmpassigneduserids[$key]);
		}
	}

	$_SESSION['assignedtouser'] = json_encode($tmpassigneduserids);
	$donotclearsession = 1;
	if ($action == 'add') {		// Test on permission not required here
		$action = 'create';
	}
	if ($action == 'update') {	// Test on permission not required here
		$action = 'edit';
	}

	$listUserAssignedUpdated = true;
}
// Remove resource to assigned list
if (empty($reshook) && (GETPOST('removedassignedresource') || GETPOST('removedassignedresource') == '0')) {
	$idtoremove = GETPOST('removedassignedresource');

	if (!empty($_SESSION['assignedtoresource'])) {
		$tmpassignedresourceids = json_decode($_SESSION['assignedtoresource'], true);
	} else {
		$tmpassignedresourceids = array();
	}

	foreach ($tmpassignedresourceids as $key => $val) {
		if ($val['id'] == $idtoremove || $val['id'] == -1) {
			unset($tmpassignedresourceids[$key]);
		}
	}

	$_SESSION['assignedtoresource'] = json_encode($tmpassignedresourceids);
	$donotclearsessionresource = 1;
	if ($action == 'add' && $usercancreate) {
		$action = 'create';
	}
	if ($action == 'update' && $usercancreate) {
		$action = 'edit';
	}

	$listResourceAssignedUpdated = true;
}

// Add user to assigned list
if (empty($reshook) && (GETPOST('addassignedtouser') || GETPOST('updateassignedtouser'))) {
	// Add a new user
	if (GETPOST('assignedtouser') > 0) {
		$assignedtouser = array();
		if (!empty($_SESSION['assignedtouser'])) {
			$assignedtouser = json_decode($_SESSION['assignedtouser'], true);
		}
		$assignedtouser[GETPOST('assignedtouser')] = array('id' => GETPOSTINT('assignedtouser'), 'transparency' => GETPOST('transparency'), 'mandatory' => 1);
		$_SESSION['assignedtouser'] = json_encode($assignedtouser);
	}
	$donotclearsession = 1;
	if ($action == 'add' && $usercancreate) {
		$action = 'create';
	}
	if ($action == 'update' && $usercancreate) {
		$action = 'edit';
	}

	$listUserAssignedUpdated = true;
}

// Add resource to assigned list
if (empty($reshook) && (GETPOST('addassignedtoresource') || GETPOST('updateassignedtoresource'))) {
	// Add a new user
	if (GETPOST('assignedtoresource') > 0) {
		$assignedtoresource = array();
		if (!empty($_SESSION['assignedtoresource'])) {
			$assignedtoresource = json_decode($_SESSION['assignedtoresource'], true);
		}
		$assignedtoresource[GETPOST('assignedtoresource')] = array('id' => GETPOSTINT('assignedtoresource'), 'transparency' => GETPOST('transparency'), 'mandatory' => 1);
		$_SESSION['assignedtoresource'] = json_encode($assignedtoresource);
	}
	$donotclearsession = 1;
	if ($action == 'add' && $usercancreate) {
		$action = 'create';
	}
	if ($action == 'update' && $usercancreate) {
		$action = 'edit';
	}

	$listResourceAssignedUpdated = true;
}

// Link to a project
if (empty($reshook) && $action == 'classin' && ($user->hasRight('agenda', 'allactions', 'create') ||
	(($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create')))) {
	//$object->fetch($id);
	$object->setProject(GETPOSTINT('projectid'));
}

// Action clone object
if (empty($reshook) && $action == 'confirm_clone' && $confirm == 'yes' && $usercancreate) {
	if (1 == 0 && !GETPOST('clone_content') && !GETPOST('clone_receivers')) {
		setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
	} else {
		if ($id > 0) {
			//$object->fetch($id);
			if (!empty($object->socpeopleassigned)) {
				reset($object->socpeopleassigned);
				$object->contact_id = key($object->socpeopleassigned);
			}
			$result = $object->createFromClone($user, GETPOSTINT('socid'));
			if ($result > 0) {
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
				exit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = '';
			}
		}
	}
}

// Add event
if (empty($reshook) && $action == 'add' && $usercancreate) {
	$error = 0;

	if (empty($backtopage)) {
		if ($socid > 0) {
			$backtopage = DOL_URL_ROOT.'/societe/agenda.php?socid='.$socid;
		} else {
			$backtopage = DOL_URL_ROOT.'/comm/action/index.php';
		}
	}

	if (!empty($socpeopleassigned[0])) {
		$result = $contact->fetch($socpeopleassigned[0]);
	}

	if ($cancel) {
		header("Location: ".$backtopage);
		exit;
	}

	$percentage = in_array(GETPOST('status'), array(-1, 100)) ? GETPOST('status') : (in_array($complete, array(-1, 100)) ? $complete : GETPOSTINT("percentage")); // If status is -1 or 100, percentage is not defined and we must use status

	// Clean parameters
	if ($fulldayevent) {
		$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
		// For "full day" events, we must store date in GMT (It must be viewed as same moment everywhere)
		$datep = dol_mktime(0, 0, 0, GETPOSTINT("apmonth"), GETPOSTINT("apday"), GETPOSTINT("apyear"), $tzforfullday ? $tzforfullday : 'tzuserrel');
		$datef = dol_mktime(23, 59, 59, GETPOSTINT("p2month"), GETPOSTINT("p2day"), GETPOSTINT("p2year"), $tzforfullday ? $tzforfullday : 'tzuserrel');
	} else {
		$datep = dol_mktime(GETPOSTINT("aphour"), GETPOSTINT("apmin"), GETPOSTINT("apsec"), GETPOSTINT("apmonth"), GETPOSTINT("apday"), GETPOSTINT("apyear"), 'tzuserrel');
		$datef = dol_mktime(GETPOSTINT("p2hour"), GETPOSTINT("p2min"), GETPOSTINT("apsec"), GETPOSTINT("p2month"), GETPOSTINT("p2day"), GETPOSTINT("p2year"), 'tzuserrel');
	}
	//set end date to now if percentage is set to 100 and end date not set
	$datef = (!$datef && $percentage == 100)?dol_now():$datef;

	// Check parameters
	if (!$datef && $percentage == 100) {
		$error++;
		$donotclearsession = 1;
		$action = 'create';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
	}

	if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && !GETPOST('label')) {
		$error++;
		$donotclearsession = 1;
		$action = 'create';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Title")), null, 'errors');
	}

	// Initialisation object cactioncomm
	if (GETPOSTISSET('actioncode') && !GETPOST('actioncode', 'aZ09')) {	// actioncode is '0'
		$error++;
		$donotclearsession = 1;
		$action = 'create';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
	} else {
		$object->type_code = GETPOST('actioncode', 'aZ09');
	}

	if (!$error) {
		// Initialisation object actioncomm
		$object->priority = GETPOSTISSET("priority") ? GETPOSTINT("priority") : 0;
		$object->fulldayevent = ($fulldayevent ? 1 : 0);
		$object->location = GETPOST("location", 'alphanohtml');
		$object->label = GETPOST('label', 'alphanohtml');

		if (GETPOST("elementtype", 'alpha')) {
			$elProp = getElementProperties(GETPOST("elementtype", 'alpha'));
			$modulecodetouseforpermissioncheck = $elProp['module'];

			$hasPermissionOnLinkedObject = 0;
			if ($user->hasRight($modulecodetouseforpermissioncheck, 'read')) {
				$hasPermissionOnLinkedObject = 1;
			}
			if ($hasPermissionOnLinkedObject) {
				$object->fk_element = GETPOSTINT("fk_element");
				$object->elementtype = GETPOST("elementtype", 'alpha');
			}
		}

		if (!GETPOST('label')) {
			if (GETPOST('actioncode', 'aZ09') == 'AC_RDV' && $contact->getFullName($langs)) {
				$object->label = $langs->transnoentitiesnoconv("TaskRDVWith", $contact->getFullName($langs));
			} else {
				if ($langs->trans("Action".$object->type_code) != "Action".$object->type_code) {
					$object->label = $langs->transnoentitiesnoconv("Action".$object->type_code)."\n";
				} else {
					$cactioncomm->fetch($object->type_code);
					$object->label = $cactioncomm->label;
				}
			}
		}
		$object->fk_project = GETPOSTISSET("projectid") ? GETPOSTINT("projectid") : 0;

		$taskid = GETPOSTINT('taskid');
		if (!empty($taskid)) {
			$taskProject = new Task($db);
			if ($taskProject->fetch($taskid) > 0) {
				$object->fk_project = $taskProject->fk_project;
			}

			$object->fk_element = $taskid;
			$object->elementtype = 'task';
		}

		$object->datep = $datep;
		$object->datef = $datef;
		$object->percentage = $percentage;
		$object->duree = (((int) GETPOST('dureehour') * 60) + (int) GETPOST('dureemin')) * 60;

		$transparency = (GETPOST("transparency") == 'on' ? 1 : 0);

		$listofuserid = array();
		if (!empty($_SESSION['assignedtouser'])) {
			$listofuserid = json_decode($_SESSION['assignedtouser'], true);
		}
		$i = 0;
		foreach ($listofuserid as $key => $value) {
			if ($i == 0) {	// First entry
				if ($value['id'] > 0) {
					$object->userownerid = $value['id'];
				}
				$object->transparency = $transparency;
			}

			$object->userassigned[$value['id']] = array('id' => $value['id'], 'transparency' => $transparency);

			$i++;
		}
	}

	$object->note_private = trim(GETPOST("note", "restricthtml"));

	if (GETPOSTISSET("contactid")) {
		$object->contact = $contact;
	}

	if (GETPOSTINT('socid') > 0) {
		$object->socid = GETPOSTINT('socid');
		$object->fetch_thirdparty();

		$object->societe = $object->thirdparty; // For backward compatibility
	}

	// Check parameters
	if (empty($object->userownerid) && empty($_SESSION['assignedtouser'])) {
		$error++;
		$donotclearsession = 1;
		$action = 'create';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ActionsOwnedBy")), null, 'errors');
	}
	if ($object->type_code == 'AC_RDV' && ($datep == '' || ($datef == '' && empty($fulldayevent)))) {
		$error++;
		$donotclearsession = 1;
		$action = 'create';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateEnd")), null, 'errors');
	}

	if (!GETPOST('apyear') && !GETPOST('adyear')) {
		$error++;
		$donotclearsession = 1;
		$action = 'create';
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
	}

	foreach ($socpeopleassigned as $cid) {
		$object->socpeopleassigned[$cid] = array('id' => $cid);
	}
	if (!empty($object->socpeopleassigned)) {
		reset($object->socpeopleassigned);
		$object->contact_id = key($object->socpeopleassigned);
	}

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost(null, $object);
	if ($ret < 0) {
		$error++;
		$donotclearsession = 1;
		$action = 'create';
	}



	if (!$error) {
		$db->begin();

		$selectedrecurrulefreq = 'no';
		$selectedrecurrulebymonthday = '';
		$selectedrecurrulebyday = '';
		$object->recurrule = GETPOSTISSET('recurrulefreq') ? "FREQ=".GETPOST('recurrulefreq', 'alpha') : "";
		$object->recurrule .= (GETPOST('recurrulefreq', 'alpha') == 'MONTHLY' && GETPOSTISSET('BYMONTHDAY')) ? "_BYMONTHDAY".GETPOST('BYMONTHDAY', 'alpha') : "";
		$object->recurrule .= (GETPOST('recurrulefreq', 'alpha') == 'WEEKLY' && GETPOSTISSET('BYDAY')) ? "_BYDAY".GETPOST('BYDAY', 'alpha') : "";

		$reg1 = array();
		$reg2 = array();
		$reg3 = array();
		if ($object->recurrule && preg_match('/FREQ=([A-Z]+)/i', $object->recurrule, $reg1)) {
			$selectedrecurrulefreq = $reg1[1];
		}
		if ($object->recurrule && preg_match('/FREQ=MONTHLY.*BYMONTHDAY(\d+)/i', $object->recurrule, $reg2)) {
			$selectedrecurrulebymonthday = (int) $reg2[1];
		}
		if ($object->recurrule && preg_match('/FREQ=WEEKLY.*BYDAY(\d+)/i', $object->recurrule, $reg3)) {
			$selectedrecurrulebyday = (int) $reg3[1];
		}

		// Is event recurrent ?
		$eventisrecurring = 0;
		$userepeatevent = (getDolGlobalInt('MAIN_FEATURES_LEVEL') == 2 ? 1 : 0);
		if ($userepeatevent && !empty($selectedrecurrulefreq) && $selectedrecurrulefreq != 'no') {
			$eventisrecurring = 1;
			$object->recurid = dol_print_date(dol_now('gmt'), 'dayhourlog', 'gmt');
			$object->recurdateend = dol_mktime(0, 0, 0, GETPOSTINT('limitmonth'), GETPOSTINT('limitday'), GETPOSTINT('limityear'));
		} else {
			unset($object->recurid);
			unset($object->recurrule);
			unset($object->recurdateend);
		}

		// Creation of action/event
		$idaction = $object->create($user);
		$moreparam = '';

		if ($idaction > 0) {
			if (!$object->error) {
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);

				unset($_SESSION['assignedtouser']);

				if ($user->id != $object->userownerid) {
					$moreparam = "filtert=-1"; // We force to remove filter so created record is visible when going back to per user view.
				}

				// Create reminders
				if ($addreminder == 'on') {
					$actionCommReminder = new ActionCommReminder($db);

					$dateremind = dol_time_plus_duree($datep, -1 * $offsetvalue, $offsetunit);

					$actionCommReminder->dateremind = $dateremind;
					$actionCommReminder->typeremind = $remindertype;
					$actionCommReminder->offsetunit = $offsetunit;
					$actionCommReminder->offsetvalue = $offsetvalue;
					$actionCommReminder->status = $actionCommReminder::STATUS_TODO;
					$actionCommReminder->fk_actioncomm = $object->id;
					if ($remindertype == 'email') {
						$actionCommReminder->fk_email_template = $modelmail;
					}

					// the notification must be created for every user assigned to the event
					foreach ($object->userassigned as $userassigned) {
						$actionCommReminder->fk_user = $userassigned['id'];
						$res = $actionCommReminder->create($user);

						if ($res <= 0) {
							// If error
							$db->rollback();
							$langs->load("errors");
							$error = $langs->trans('ErrorReminderActionCommCreation');
							setEventMessages($error, null, 'errors');
							$action = 'create';
							$donotclearsession = 1;
							break;
						}
					}
				}

				// Modify $moreparam so we are sure to see the event we have just created, whatever are the default value of filter on next page.
				/*$moreparam .= ($moreparam ? '&' : '').'search_actioncode=0';
				 $moreparam .= ($moreparam ? '&' : '').'search_status=-1';
				 $moreparam .= ($moreparam ? '&' : '').'search_filtert='.$object->userownerid;
				 */
				$moreparam .= ($moreparam ? '&' : '').'disabledefaultvalues=1';

				if ($error) {
					$db->rollback();
				} else {
					$db->commit();
				}

				// if (!empty($backtopage)) {
				// 	dol_syslog("Back to ".$backtopage.($moreparam ? (preg_match('/\?/', $backtopage) ? '&'.$moreparam : '?'.$moreparam) : ''));
				// 	header("Location: ".$backtopage.($moreparam ? (preg_match('/\?/', $backtopage) ? '&'.$moreparam : '?'.$moreparam) : ''));
				// } elseif ($idaction) {
				// 	header("Location: ".DOL_URL_ROOT.'/comm/action/card.php?id='.$idaction.($moreparam ? '&'.$moreparam : ''));
				// } else {
				// 	header("Location: ".DOL_URL_ROOT.'/comm/action/index.php'.($moreparam ? '?'.$moreparam : ''));
				// }
				// exit;
			} else {
				// If error
				$db->rollback();
				$langs->load("errors");
				$error = $langs->trans($object->error);
				setEventMessages($error, null, 'errors');
				$action = 'create';
				$donotclearsession = 1;
			}
		} else {
			$db->rollback();
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'create';
			$donotclearsession = 1;
		}

		if ($eventisrecurring) {
			$dayoffset = 0;
			$monthoffset = 0;
			// We set first date of recurrence and offsets
			if ($selectedrecurrulefreq == 'WEEKLY' && !empty($selectedrecurrulebyday)) {
				$firstdatearray = dol_get_first_day_week(GETPOSTINT("apday"), GETPOSTINT("apmonth"), GETPOSTINT("apyear"));
				$datep = dol_mktime($fulldayevent ? '00' : GETPOSTINT("aphour"), $fulldayevent ? '00' : GETPOSTINT("apmin"), $fulldayevent ? '00' : GETPOSTINT("apsec"), $firstdatearray['month'], $firstdatearray['first_day'], $firstdatearray['year'], $tzforfullday ? $tzforfullday : 'tzuserrel');
				$datep = dol_time_plus_duree($datep, $selectedrecurrulebyday + 6, 'd');//We begin the week after
				$dayoffset = 7;
				$monthoffset = 0;
			} elseif ($selectedrecurrulefreq == 'MONTHLY' && !empty($selectedrecurrulebymonthday)) {
				$firstday = $selectedrecurrulebymonthday;
				$firstmonth = GETPOST("apday") > $selectedrecurrulebymonthday ? GETPOSTINT("apmonth") + 1 : GETPOSTINT("apmonth");//We begin the week after
				$datep = dol_mktime($fulldayevent ? '00' : GETPOSTINT("aphour"), $fulldayevent ? '00' : GETPOSTINT("apmin"), $fulldayevent ? '00' : GETPOSTINT("apsec"), $firstmonth, $firstday, GETPOSTINT("apyear"), $tzforfullday ? $tzforfullday : 'tzuserrel');
				$dayoffset = 0;
				$monthoffset = 1;
			} else {
				$error++;
			}
			// End date
			$repeateventlimitdate = dol_mktime(23, 59, 59, GETPOSTISSET("limitmonth") ? GETPOSTINT("limitmonth") : 1, GETPOSTISSET("limitday") ? GETPOSTINT("limitday") : 1, GETPOSTISSET("limityear") && GETPOSTINT("limityear") < 2100 ? GETPOSTINT("limityear") : 2100, $tzforfullday ? $tzforfullday : 'tzuserrel');
			// Set date of end of event
			$deltatime = num_between_day($object->datep, $datep);
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$datef = dol_time_plus_duree($datef, $deltatime, 'd');

			while ($datep <= $repeateventlimitdate && !$error) {
				$finalobject = clone $object;


				$finalobject->datep = $datep;
				$finalobject->datef = $datef;
				// Creation of action/event
				$idaction = $finalobject->create($user);

				if ($idaction > 0) {
					if (!$finalobject->error) {
						// Category association
						$categories = GETPOST('categories', 'array');
						$finalobject->setCategories($categories);

						unset($_SESSION['assignedtouser']);

						$moreparam = '';
						if ($user->id != $finalobject->userownerid) {
							$moreparam = "filtert=-1"; // We force to remove filter so created record is visible when going back to per user view.
						}

						// Create reminders
						if ($addreminder == 'on') {
							$actionCommReminder = new ActionCommReminder($db);

							$dateremind = dol_time_plus_duree($datep, -1 * $offsetvalue, $offsetunit);

							$actionCommReminder->dateremind = $dateremind;
							$actionCommReminder->typeremind = $remindertype;
							$actionCommReminder->offsetunit = $offsetunit;
							$actionCommReminder->offsetvalue = $offsetvalue;
							$actionCommReminder->status = $actionCommReminder::STATUS_TODO;
							$actionCommReminder->fk_actioncomm = $finalobject->id;
							if ($remindertype == 'email') {
								$actionCommReminder->fk_email_template = $modelmail;
							}

							// the notification must be created for every user assigned to the event
							foreach ($finalobject->userassigned as $userassigned) {
								$actionCommReminder->fk_user = $userassigned['id'];
								$res = $actionCommReminder->create($user);

								if ($res <= 0) {
									// If error
									$db->rollback();
									$langs->load("errors");
									$error = $langs->trans('ErrorReminderActionCommCreation');
									setEventMessages($error, null, 'errors');
									$action = 'create';
									$donotclearsession = 1;
									break;
								}
							}
						}

						// Modify $moreparam so we are sure to see the event we have just created, whatever are the default value of filter on next page.
						/*$moreparam .= ($moreparam ? '&' : '').'search_actioncode=0';
						 $moreparam .= ($moreparam ? '&' : '').'search_status=-1';
						 $moreparam .= ($moreparam ? '&' : '').'search_filtert='.$object->userownerid;
						 */
						$moreparam .= ($moreparam ? '&' : '').'disabledefaultvalues=1';

						if ($error) {
							$db->rollback();
						} else {
							$db->commit();
						}
					} else {
						// If error
						$db->rollback();
						$langs->load("errors");
						$error = $langs->trans($finalobject->error);
						setEventMessages($error, null, 'errors');
						$action = 'create';
						$donotclearsession = 1;
					}
				} else {
					$db->rollback();
					setEventMessages($finalobject->error, $finalobject->errors, 'errors');
					$action = 'create';
					$donotclearsession = 1;
				}

				// If event is not recurrent, we stop here
				if (!($userepeatevent && GETPOSTISSET('recurrulefreq') && GETPOST('recurrulefreq') != 'no' && GETPOSTISSET("limityear") && GETPOSTISSET("limitmonth") && GETPOSTISSET("limitday"))) {
					break;
				}

				// increment date for recurrent events
				$datep = dol_time_plus_duree($datep, $dayoffset, 'd');
				$datep = dol_time_plus_duree($datep, $monthoffset, 'm');  // @phan-suppress-current-line PhanPluginSuspiciousParamOrder
				$datef = dol_time_plus_duree($datef, $dayoffset, 'd');
				$datef = dol_time_plus_duree($datef, $monthoffset, 'm');  // @phan-suppress-current-line PhanPluginSuspiciousParamOrder
			}
		}
		if (!empty($backtopage) && !$error) {
			dol_syslog("Back to ".$backtopage.($moreparam ? (preg_match('/\?/', $backtopage) ? '&'.$moreparam : '?'.$moreparam) : ''));
			header("Location: ".$backtopage.($moreparam ? (preg_match('/\?/', $backtopage) ? '&'.$moreparam : '?'.$moreparam) : ''));
		} elseif ($idaction) {
			header("Location: ".DOL_URL_ROOT.'/comm/action/card.php?id='.$idaction.($moreparam ? '&'.$moreparam : ''));
		} else {
			header("Location: ".DOL_URL_ROOT.'/comm/action/index.php'.($moreparam ? '?'.$moreparam : ''));
		}
		exit;
	}
}

// Action update event
if (empty($reshook) && $action == 'update' && $usercancreate) {
	if (empty($cancel)) {
		$fulldayevent = GETPOST('fullday');
		$aphour = GETPOSTINT('aphour');
		$apmin = GETPOSTINT('apmin');
		$p2hour = GETPOSTINT('p2hour');
		$p2min = GETPOSTINT('p2min');
		$percentage = in_array(GETPOST('status'), array(-1, 100)) ? GETPOST('status') : (in_array($complete, array(-1, 100)) ? $complete : GETPOSTINT("percentage")); // If status is -1 or 100, percentage is not defined and we must use status

		// Clean parameters
		if ($aphour == -1) {
			$aphour = '0';
		}
		if ($apmin == -1) {
			$apmin = '0';
		}
		if ($p2hour == -1) {
			$p2hour = '0';
		}
		if ($p2min == -1) {
			$p2min = '0';
		}

		$object->fetch($id);
		$object->fetch_optionals();
		$object->fetch_userassigned();
		$object->oldcopy = dol_clone($object, 2);

		// Clean parameters
		if ($fulldayevent) {
			$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
			// For "full day" events, we must store date in GMT (It must be viewed as same moment everywhere)
			$datep = dol_mktime(0, 0, 0, GETPOST("apmonth", 'int'), GETPOST("apday", 'int'), GETPOST("apyear", 'int'), $tzforfullday ? $tzforfullday : 'tzuserrel');
			$datef = dol_mktime(23, 59, 59, GETPOST("p2month", 'int'), GETPOST("p2day", 'int'), GETPOST("p2year", 'int'), $tzforfullday ? $tzforfullday : 'tzuserrel');
		} else {
			$datep = dol_mktime(GETPOST("aphour", 'int'), GETPOST("apmin", 'int'), GETPOST("apsec", 'int'), GETPOST("apmonth", 'int'), GETPOST("apday", 'int'), GETPOST("apyear", 'int'), 'tzuserrel');
			$datef = dol_mktime(GETPOST("p2hour", 'int'), GETPOST("p2min", 'int'), GETPOST("apsec", 'int'), GETPOST("p2month", 'int'), GETPOST("p2day", 'int'), GETPOST("p2year", 'int'), 'tzuserrel');
		}
		//set end date to now if percentage is set to 100 and end date not set
		$datef = (!$datef && $percentage == 100) ? dol_now() : $datef;

		if ($object->elementtype == 'ticket') {	// code should be TICKET_MSG, TICKET_MSG_PRIVATE, TICKET_MSG_SENTBYMAIL, TICKET_MSG_PRIVATE_SENTBYMAIL
			if ($private) {
				if ($object->code == 'TICKET_MSG') {
					$object->code = 'TICKET_MSG_PRIVATE';
				}
				if ($object->code == 'TICKET_MSG_SENTBYMAIL') {
					$object->code = 'TICKET_MSG_PRIVATE_SENTBYMAIL';
				}
			} else {
				if ($object->code == 'TICKET_MSG_PRIVATE') {
					$object->code = 'TICKET_MSG';
				}
				if ($object->code == 'TICKET_MSG_PRIVATE_SENTBYMAIL') {
					$object->code = 'TICKET_MSG_SENTBYMAIL';
				}
			}
			// type_id and type_code is not modified
		} else {
			$object->type_id = dol_getIdFromCode($db, GETPOST("actioncode", 'aZ09'), 'c_actioncomm');
			$object->type_code = GETPOST("actioncode", 'aZ09');
		}

		$object->label       = GETPOST("label", "alphanohtml");
		$object->datep       = $datep;
		$object->datef       = $datef;
		$object->percentage  = $percentage;
		$object->priority    = GETPOSTINT("priority");
		$object->fulldayevent = GETPOST("fullday") ? 1 : 0;
		$object->location    = GETPOST('location', "alphanohtml");
		$object->socid       = GETPOSTINT("socid");
		$socpeopleassigned   = GETPOST("socpeopleassigned", 'array');
		$object->socpeopleassigned = array();
		foreach ($socpeopleassigned as $cid) {
			$object->socpeopleassigned[$cid] = array('id' => $cid);
		}
		$object->contact_id = GETPOSTINT("contactid");
		if (empty($object->contact_id) && !empty($object->socpeopleassigned)) {
			reset($object->socpeopleassigned);
			$object->contact_id = key($object->socpeopleassigned);
		}
		$object->fk_project  = GETPOSTINT("projectid");
		$object->note_private = trim(GETPOST("note", "restricthtml"));

		if (GETPOST("elementtype", 'alpha')) {
			$elProp = getElementProperties(GETPOST("elementtype", 'alpha'));
			$modulecodetouseforpermissioncheck = $elProp['module'];

			$hasPermissionOnLinkedObject = 0;
			if ($user->hasRight($modulecodetouseforpermissioncheck, 'read')) {
				$hasPermissionOnLinkedObject = 1;
			}
			if ($hasPermissionOnLinkedObject) {
				$object->fk_element = GETPOSTINT("fk_element");
				$object->elementtype = GETPOST("elementtype", 'alpha');
			}
		}


		$transparency = (GETPOST("transparency") == 'on' ? 1 : 0);

		// Users
		$listofuserid = array();
		if (!empty($_SESSION['assignedtouser'])) {	// Now concat assigned users
			// Restore array with key with same value than param 'id'
			$tmplist1 = json_decode($_SESSION['assignedtouser'], true);
			foreach ($tmplist1 as $key => $val) {
				if ($val['id'] > 0 && $val['id'] != $assignedtouser) {
					$listofuserid[$val['id']] = $val;
				}
			}
		} else {
			$assignedtouser = (!empty($object->userownerid) && $object->userownerid > 0 ? $object->userownerid : 0);
			if ($assignedtouser) {
				$listofuserid[$assignedtouser] = array('id' => $assignedtouser, 'mandatory' => 0, 'transparency' => ($user->id == $assignedtouser ? $transparency : '')); // Owner first
			}
		}
		$object->userassigned = array();
		$object->userownerid = 0; // Clear old content
		$i = 0;
		foreach ($listofuserid as $key => $val) {
			if ($i == 0) {
				$object->userownerid = $val['id'];
			}
			$object->userassigned[$val['id']] = array('id' => $val['id'], 'mandatory' => 0, 'transparency' => ($user->id == $val['id'] ? $transparency : ''));
			$i++;
		}

		$object->transparency = $transparency; // We set transparency on event (even if we can also store it on each user, standard says this property is for event)
		// TODO store also transparency on owner user

		// Check parameters
		if (GETPOSTISSET('actioncode') && !GETPOST('actioncode', 'aZ09')) {	// actioncode is '0'
			$error++;
			$donotclearsession = 1;
			$action = 'edit';
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
		} else {
			$result = $cactioncomm->fetch(GETPOST('actioncode', 'aZ09'));
		}
		if (empty($object->userownerid)) {
			$error++;
			$donotclearsession = 1;
			$action = 'edit';
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ActionsOwnedBy")), null, 'errors');
		}

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// check if an event resource is already in use
			if (getDolGlobalString('RESOURCE_USED_IN_EVENT_CHECK') && $object->element == 'action') {
				$eventDateStart = $object->datep;
				$eventDateEnd = $object->datef;

				$sql  = "SELECT er.rowid, r.ref as r_ref, ac.id as ac_id, ac.label as ac_label";
				$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as er";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."resource as r ON r.rowid = er.resource_id AND er.resource_type = 'dolresource'";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm as ac ON ac.id = er.element_id AND er.element_type = '".$db->escape($object->element)."'";
				$sql .= " WHERE ac.id <> ".((int) $object->id);
				$sql .= " AND er.resource_id IN (";
				$sql .= " SELECT resource_id FROM ".MAIN_DB_PREFIX."element_resources";
				$sql .= " WHERE element_id = ".((int) $object->id);
				$sql .= " AND element_type = '".$db->escape($object->element)."'";
				$sql .= " AND busy = 1";
				$sql .= ")";
				$sql .= " AND er.busy = 1";
				$sql .= " AND (";

				// event date start between ac.datep and ac.datep2 (if datep2 is null we consider there is no end)
				$sql .= " (ac.datep <= '".$db->idate($eventDateStart)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($eventDateStart)."'))";
				// event date end between ac.datep and ac.datep2
				if (!empty($eventDateEnd)) {
					$sql .= " OR (ac.datep <= '".$db->idate($eventDateEnd)."' AND (ac.datep2 >= '".$db->idate($eventDateEnd)."'))";
				}
				// event date start before ac.datep and event date end after ac.datep2
				$sql .= " OR (";
				$sql .= "ac.datep >= '".$db->idate($eventDateStart)."'";
				if (!empty($eventDateEnd)) {
					$sql .= " AND (ac.datep2 IS NOT NULL AND ac.datep2 <= '".$db->idate($eventDateEnd)."')";
				}
				$sql .= ")";

				$sql .= ")";
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
					$object->error = $db->lasterror();
					$object->errors[] = $object->error;
				} else {
					if ($db->num_rows($resql) > 0) {
						// Resource already in use
						$error++;
						$object->error = $langs->trans('ErrorResourcesAlreadyInUse').' : ';
						while ($obj = $db->fetch_object($resql)) {
							$object->error .= '<br> - '.$langs->trans('ErrorResourceUseInEvent', $obj->r_ref, $obj->ac_label.' ['.$obj->ac_id.']');
						}
						$object->errors[] = $object->error;
					}
					$db->free($resql);
				}

				if ($error) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if (!$error) {
			$db->begin();

			$result = $object->update($user);

			if ($result > 0) {
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);

				$object->loadReminders($remindertype, 0, false);

				// If there is reminders, we remove them
				if (!empty($object->reminders)) {
					foreach ($object->reminders as $reminder) {
						if ($reminder->status < 1) {	// If already sent, we never remove it
							$reminder->delete($user);
						}
					}
					$object->reminders = array();
				}

				// Create reminders for every assigned user if reminder is on
				if ($addreminder == 'on') {
					$actionCommReminder = new ActionCommReminder($db);

					$dateremind = dol_time_plus_duree($datep, -1 * $offsetvalue, $offsetunit);

					$actionCommReminder->dateremind = $dateremind;
					$actionCommReminder->typeremind = $remindertype;
					$actionCommReminder->offsetunit = $offsetunit;
					$actionCommReminder->offsetvalue = $offsetvalue;
					$actionCommReminder->status = $actionCommReminder::STATUS_TODO;
					$actionCommReminder->fk_actioncomm = $object->id;
					if ($remindertype == 'email') {
						$actionCommReminder->fk_email_template = $modelmail;
					}

					// the notification must be created for every user assigned to the event
					foreach ($object->userassigned as $userassigned) {
						$actionCommReminder->fk_user = $userassigned['id'];

						// We update the event, so we recreate the notification event.
						// First we delete all reminders for the user and the type of reminding (all offset dates).
						$sqldelete = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_reminder";
						$sqldelete .= " WHERE fk_user = ".((int) $actionCommReminder->fk_user)." AND fk_actioncomm = ".((int) $object->id)." AND typeremind = '".$db->escape($remindertype)."'";
						$resqldelete = $db->query($sqldelete);

						$res = $actionCommReminder->create($user);

						if ($res <= 0) {
							// If error
							$langs->load("errors");
							$error = $langs->trans('ErrorReminderActionCommCreation');
							setEventMessages($error, null, 'errors');
							$action = 'create';
							$donotclearsession = 1;
							break;
						}
					}
				}

				if (!$error) {
					unset($_SESSION['assignedtouser']);
					unset($_SESSION['assignedtoresource']);

					$db->commit();
				} else {
					$db->rollback();
					$action = 'edit';
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
			}
		}
	}

	if (!$error) {
		if (!empty($backtopage)) {
			unset($_SESSION['assignedtouser']);
			header("Location: ".$backtopage);
			exit;
		}
	}
}

// Delete event
if (empty($reshook) && $action == 'confirm_delete' && GETPOST("confirm") == 'yes') {
	$object->fetch($id);
	$object->fetch_optionals();
	$object->fetch_userassigned();
	$object->oldcopy = dol_clone($object, 2);

	if ($user->hasRight('agenda', 'myactions', 'delete')
		|| $user->hasRight('agenda', 'allactions', 'delete')) {
		$result = $object->delete($user);

		if ($result >= 0) {
			header("Location: index.php");
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

/*
 * Action move update, used when user move an event in calendar by drag'n drop
 * TODO Move this into page comm/action/index that trigger this call by the drag and drop of event.
 */
if (empty($reshook) && GETPOST('actionmove', 'alpha') == 'mupdate') {
	$error = 0;

	$shour = (int) dol_print_date($object->datep, "%H", 'tzuserrel');		// We take the date visible by user $newdate is also date visible by user.
	$smin = (int) dol_print_date($object->datep, "%M", 'tzuserrel');

	$newdate = GETPOST('newdate', 'alpha');
	if (empty($newdate) || strpos($newdate, 'dayevent_') != 0) {
		header("Location: ".$backtopage, true, 307);
		exit;
	}

	$datep = dol_mktime($shour, $smin, 0, (int) substr($newdate, 13, 2), (int) substr($newdate, 15, 2), (int) substr($newdate, 9, 4), 'tzuserrel');
	//print dol_print_date($datep, 'dayhour');exit;

	if ($datep != $object->datep) {
		if (!empty($object->datef)) {
			$object->datef += $datep - $object->datep;
		}
		$object->datep = $datep;

		if (!$error) {
			// check if an event resource is already in use
			if (getDolGlobalString('RESOURCE_USED_IN_EVENT_CHECK') && $object->element == 'action') {
				$eventDateStart = $object->datep;
				$eventDateEnd = $object->datef;

				$sql  = "SELECT er.rowid, r.ref as r_ref, ac.id as ac_id, ac.label as ac_label";
				$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as er";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."resource as r ON r.rowid = er.resource_id AND er.resource_type = 'dolresource'";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm as ac ON ac.id = er.element_id AND er.element_type = '".$db->escape($object->element)."'";
				$sql .= " WHERE ac.id <> ".((int) $object->id);
				$sql .= " AND er.resource_id IN (";
				$sql .= " SELECT resource_id FROM ".MAIN_DB_PREFIX."element_resources";
				$sql .= " WHERE element_id = ".((int) $object->id);
				$sql .= " AND element_type = '".$db->escape($object->element)."'";
				$sql .= " AND busy = 1";
				$sql .= ")";
				$sql .= " AND er.busy = 1";
				$sql .= " AND (";

				// event date start between ac.datep and ac.datep2 (if datep2 is null we consider there is no end)
				$sql .= " (ac.datep <= '".$db->idate($eventDateStart)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($eventDateStart)."'))";
				// event date end between ac.datep and ac.datep2
				if (!empty($eventDateEnd)) {
					$sql .= " OR (ac.datep <= '".$db->idate($eventDateEnd)."' AND (ac.datep2 >= '".$db->idate($eventDateEnd)."'))";
				}
				// event date start before ac.datep and event date end after ac.datep2
				$sql .= " OR (";
				$sql .= "ac.datep >= '".$db->idate($eventDateStart)."'";
				if (!empty($eventDateEnd)) {
					$sql .= " AND (ac.datep2 IS NOT NULL AND ac.datep2 <= '".$db->idate($eventDateEnd)."')";
				}
				$sql .= ")";

				$sql .= ")";
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
					$object->error = $db->lasterror();
					$object->errors[] = $object->error;
				} else {
					if ($db->num_rows($resql) > 0) {
						// Resource already in use
						$error++;
						$object->error = $langs->trans('ErrorResourcesAlreadyInUse').' : ';
						while ($obj = $db->fetch_object($resql)) {
							$object->error .= '<br> - '.$langs->trans('ErrorResourceUseInEvent', $obj->r_ref, $obj->ac_label.' ['.$obj->ac_id.']');
						}
						$object->errors[] = $object->error;
					}
					$db->free($resql);
				}

				if ($error) {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if (!$error) {
			$db->begin();
			$result = $object->update($user);
			if ($result < 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
			} else {
				$db->commit();
			}
		}
	}
	if (!empty($backtopage)) {
		header("Location: ".$backtopage, true, 307);
		exit;
	} else {
		$action = '';
	}
}

// Actions to delete doc
$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($object->ref);
$permissiontoadd = ($user->hasRight('agenda', 'allactions', 'create') || (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'read')));
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);

$arrayrecurrulefreq = array(
	'no' => $langs->trans("OnceOnly"),
	'MONTHLY' => $langs->trans("EveryMonth"),
	'WEEKLY' => $langs->trans("EveryWeek")
	// 'DAILY'=>$langs->trans("EveryDay")
);


$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("Agenda"), $help_url);

if ($action == 'create') {
	$contact = new Contact($db);

	$socpeopleassigned = GETPOST("socpeopleassigned", 'array');
	if (!empty($socpeopleassigned[0])) {
		$result = $contact->fetch($socpeopleassigned[0]);
		if ($result < 0) {
			dol_print_error($db, $contact->error);
		}
	}

	dol_set_focus("#label");

	if (!empty($conf->use_javascript_ajax)) {
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
        			function setdatefields()
	            	{
	            		if ($("#fullday:checked").val() == null) {
	            			$(".fulldaystarthour").removeAttr("disabled");
	            			$(".fulldaystartmin").removeAttr("disabled");
	            			$(".fulldayendhour").removeAttr("disabled");
	            			$(".fulldayendmin").removeAttr("disabled");
	            			$("#p2").removeAttr("disabled");
	            		} else {
							$(".fulldaystarthour").prop("disabled", true).val("00");
							$(".fulldaystartmin").prop("disabled", true).val("00");
							$(".fulldayendhour").prop("disabled", true).val("23");
							$(".fulldayendmin").prop("disabled", true).val("59");
							$("#p2").removeAttr("disabled");
	            		}
	            	}
                    $("#fullday").change(function() {
						console.log("setdatefields");
                        setdatefields();
                    });

                    $("#actioncode").change(function() {
                        if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
                        else $("#dateend").removeClass("fieldrequired");
                    });
					$("#aphour,#apmin").change(function() {
						if ($("#actioncode").val() == \'AC_RDV\') {
							console.log("Start date was changed, we modify end date "+(parseInt($("#aphour").val()))+" "+$("#apmin").val()+" -> "+("00" + (parseInt($("#aphour").val()) + 1)).substr(-2,2));
							$("#p2hour").val(("00" + (parseInt($("#aphour").val()) + 1)).substr(-2,2));
							$("#p2min").val($("#apmin").val());
							$("#p2day").val($("#apday").val());
							$("#p2month").val($("#apmonth").val());
							$("#p2year").val($("#apyear").val());
							$("#p2").val($("#ap").val());
						}
					});
                    if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
                    else $("#dateend").removeClass("fieldrequired");
                    setdatefields();
               })';
		print '</script>'."\n";
	}

	print '<form name="formaction" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="donotclearsession" value="1">';
	print '<input type="hidden" name="page_y" value="">';
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : '').'">';
	}
	if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		print '<input type="hidden" name="actioncode" value="'.dol_getIdFromCode($db, 'AC_OTH', 'c_actioncomm').'">';
	}

	if (GETPOST("actioncode", 'aZ09') == 'AC_RDV') {
		print load_fiche_titre($langs->trans("AddActionRendezVous"), '', 'title_agenda');
	} else {
		print load_fiche_titre($langs->trans("AddAnAction"), '', 'title_agenda');
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent">';

	// Type of event
	if (getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Type").'</span></b></td><td>';
		$default = getDolGlobalString('AGENDA_USE_EVENT_TYPE_DEFAULT', 'AC_RDV');
		print img_picto($langs->trans("ActionType"), 'square', 'class="fawidth30 inline-block" style="color: #ddd;"');
		$selectedvalue = GETPOSTISSET("actioncode") ? GETPOST("actioncode", 'aZ09') : ($object->type_code ? $object->type_code : $default);
		print $formactions->select_type_actions($selectedvalue, "actioncode", "systemauto", 0, -1, 0, 1);	// TODO Replace 0 with -2 in onlyautoornot
		print '</td></tr>';
	}

	// Title
	print '<tr><td'.(!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? ' class="fieldrequired titlefieldcreate"' : '').'>'.$langs->trans("Title").'</td><td><input type="text" id="label" name="label" class="soixantepercent" value="'.GETPOST('label').'"></td></tr>';

	// Full day
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td>';
	print '<td class="valignmiddle height30"><input class="valignmiddle" type="checkbox" id="fullday" name="fullday" '.(GETPOST('fullday') ? ' checked' : '').'><label for="fullday" class="valignmiddle small">'.$langs->trans("EventOnFullDay").'</label>';

	// Recurring event
	$userepeatevent = (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 1 ? 1 : 0);
	if ($userepeatevent) {
		// Repeat
		print ' &nbsp; &nbsp; &nbsp; &nbsp; <div class="opacitymedium inline-block small">';
		print img_picto($langs->trans("Recurrence"), 'recurring', 'style="margin-left: 6px" class="paddingright2"');
		print '<input type="hidden" name="recurid" value="'.(empty($object->recurid) ? '' : $object->recurid).'">';

		$selectedrecurrulefreq = 'no';
		$selectedrecurrulebymonthday = '';
		$selectedrecurrulebyday = '';
		$object->recurrule = GETPOSTISSET('recurrulefreq') ? "FREQ=".GETPOST('recurrulefreq', 'alpha') : "";
		$object->recurrule .= GETPOSTISSET('BYMONTHDAY') ? "_BYMONTHDAY".GETPOST('BYMONTHDAY', 'alpha') : "";
		$object->recurrule .= GETPOSTISSET('BYDAY') ? "_BYDAY".GETPOST('BYDAY', 'alpha') : "";


		$reg = array();
		if ($object->recurrule && preg_match('/FREQ=([A-Z]+)/i', $object->recurrule, $reg)) {
			$selectedrecurrulefreq = $reg[1];
		}
		if ($object->recurrule && preg_match('/FREQ=MONTHLY.*BYMONTHDAY(\d+)/i', $object->recurrule, $reg)) {
			$selectedrecurrulebymonthday = (int) $reg[1];
		}
		if ($object->recurrule && preg_match('/FREQ=WEEKLY.*BYDAY(\d+)/i', $object->recurrule, $reg)) {
			$selectedrecurrulebyday = (int) $reg[1];
		}

		print $form->selectarray('recurrulefreq', $arrayrecurrulefreq, $selectedrecurrulefreq, 0, 0, 0, '', 0, 0, 0, '', 'marginrightonly');
		// print '<script>console.log("recurrule: " +'.$object->recurrule.')</script>';
		// For recursive event


		// If recurrulefreq is MONTHLY
		print '<div class="hidden marginrightonly inline-block repeateventBYMONTHDAY">';
		print $langs->trans("DayOfMonth").': <input type="input" size="2" name="BYMONTHDAY" value="'.$selectedrecurrulebymonthday.'">';
		print '</div>';
		// If recurrulefreq is WEEKLY
		print '<div class="hidden marginrightonly inline-block repeateventBYDAY">';
		print $langs->trans("DayOfWeek").': <input type="input" size="4" name="BYDAY" value="'.$selectedrecurrulebyday.'">';
		print '</div>';
		// limit date
		$repeateventlimitdate = !empty($repeateventlimitdate) ? $repeateventlimitdate : '';
		print '<div class="hidden marginrightonly inline-block repeateventlimitdate">';
		print $langs->trans("Until")." ";
		print $form->selectDate($repeateventlimitdate, 'limit', 0, 0, 0, "action", 1, 0, 0, '', '', '', '', 1, '', '', 'tzuserrel');
		print '</div>';

		print '<script type="text/javascript">
			jQuery(document).ready(function() {
				function init_repeat()
				{
					console.log("recurrule: " + "'.$object->recurrule.'");
					console.log("reg1: " + "'.$selectedrecurrulefreq.'");
					console.log("reg2: " + "'.$selectedrecurrulebymonthday.'");
					console.log("reg3: " + "'.$selectedrecurrulebyday.'");
					console.log("selectedrulefreq: " + "'.$selectedrecurrulefreq.'");
					if (jQuery("#recurrulefreq").val() == \'MONTHLY\')
					{
						jQuery(".repeateventBYMONTHDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
						jQuery(".repeateventlimitdate").css("display", "inline-block");
						jQuery(".repeateventBYDAY").hide();
					}
					else if (jQuery("#recurrulefreq").val() == \'WEEKLY\')
					{
						jQuery(".repeateventBYMONTHDAY").hide();
						jQuery(".repeateventBYDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
						jQuery(".repeateventlimitdate").css("display", "inline-block");
					}
					else
					{
						jQuery(".repeateventBYMONTHDAY").hide();
						jQuery(".repeateventBYDAY").hide();
						jQuery(".repeateventlimitdate").hide();
					}
				}
				init_repeat();
				jQuery("#recurrulefreq").change(function() {
					init_repeat();
				});
			});
			</script>';
		print '</div>';
		//print '</td></tr>';
	}

	print '</td></tr>';

	$datep = ($datep ? $datep : (is_null($object->datep) ? '' : $object->datep));
	if (GETPOSTINT('datep', 1)) {
		$datep = dol_stringtotime((string) GETPOSTINT('datep', 1), 'tzuserrel');
	}
	$datef = ($datef ? $datef : $object->datef);
	if (GETPOSTINT('datef', 1)) {
		$datef = dol_stringtotime((string) GETPOSTINT('datef', 1), 'tzuserrel');
	}
	if (empty($datef) && !empty($datep)) {
		if (GETPOST("actioncode", 'aZ09') == 'AC_RDV' || !getDolGlobalString('AGENDA_USE_EVENT_TYPE_DEFAULT')) {
			$datef = dol_time_plus_duree($datep, getDolGlobalInt('AGENDA_AUTOSET_END_DATE_WITH_DELTA_HOURS', 1), 'h');
		}
	}

	// Date start
	print '<tr><td class="nowrap">';
	print '</td><td>';
	if (GETPOST("afaire") == 1) {
		print $form->selectDate($datep, 'ap', 1, 1, 0, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel'); // Empty value not allowed for start date and hours if "todo"
	} else {
		print $form->selectDate($datep, 'ap', 1, 1, 1, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', 'tzuserrel');
	}
	print ' <span class="hideonsmartphone">&nbsp; &nbsp; - &nbsp; &nbsp;</span><br class="showonsmartphone"> ';
	print $form->selectDate($datef, 'p2', 1, 1, 1, "action", 1, 2, 0, 'fulldayend', '', '', '', 1, '', '', 'tzuserrel');
	print '</td></tr>';

	print '<tr><td class="">&nbsp;</td><td></td></tr>';

	// Assigned to user
	print '<tr><td class="tdtop nowrap"><span class="fieldrequired">'.$langs->trans("ActionAffectedTo").'</span></td><td>';
	$listofuserid = array();
	$listofcontactid = array();
	$listofotherid = array();

	if (empty($donotclearsession)) {
		$assignedtouser = GETPOST("assignedtouser") ? GETPOST("assignedtouser") : (!empty($object->userownerid) && $object->userownerid > 0 ? $object->userownerid : $user->id);
		if ($assignedtouser) {
			$listofuserid[$assignedtouser] = array('id' => $assignedtouser, 'mandatory' => 0); // Owner first
		}
		//$listofuserid[$user->id] = array('id'=>$user->id, 'mandatory'=>0, 'transparency'=>(GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 1)); // 1 by default at first init
		$listofuserid[$assignedtouser]['transparency'] = (GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 1); // 1 by default at first init
		$_SESSION['assignedtouser'] = json_encode($listofuserid);
	} else {
		if (!empty($_SESSION['assignedtouser'])) {
			$listofuserid = json_decode($_SESSION['assignedtouser'], true);
		}
		$firstelem = reset($listofuserid);
		if (isset($listofuserid[$firstelem['id']])) {
			$listofuserid[$firstelem['id']]['transparency'] = (GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 0); // 0 by default when refreshing
		}
	}
	print '<div class="assignedtouser">';
	print $form->select_dolusers_forevent(($action == 'create' ? 'add' : 'update'), 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, 'AND u.statut != 0', 1, $listofuserid, $listofcontactid, $listofotherid);
	print '</div>';
	print '</td></tr>';

	// Location
	if (!getDolGlobalString('AGENDA_DISABLE_LOCATION')) {
		print '<tr><td>'.$langs->trans("Location").'</td><td><input type="text" name="location" class="minwidth300 maxwidth150onsmartphone" value="'.(GETPOST('location') ? GETPOST('location') : $object->location).'"></td></tr>';
	}

	if (isModEnabled('category')) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACTIONCOMM, '', 'parent', 64, 0, 3);
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, GETPOST('categories', 'array'), '', 0, 'minwidth300 quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}

	if (isModEnabled('resource')) {
		// Categories
		print '<tr><td class="tdtop nowrap">'.$langs->trans("Resource").'</td><td>';

		$listofresourceid = array();
		if (empty($donotclearsession)) {
			$assignedtoresource = GETPOST("assignedtoresource");
			if ($assignedtoresource) {
				$listofresourceid[$assignedtoresource] = array('id' => $assignedtoresource, 'mandatory' => 0); // Owner first
			}
			$_SESSION['assignedtoresource'] = json_encode($listofresourceid);
		} else {
			if (!empty($_SESSION['assignedtoresource'])) {
				$listofresourceid = json_decode($_SESSION['assignedtoresource'], true);
			}
			$firstelem = reset($listofresourceid);
			if (isset($listofresourceid[$firstelem['id']])) {
				$listofresourceid[$firstelem['id']]['transparency'] = (GETPOSTISSET('transparency') ? GETPOST('transparency', 'alpha') : 0); // 0 by default when refreshing
			}
		}
		print '<div class="assignedtoresource">';
		print $form->select_dolresources_forevent(($action == 'create' ? 'add' : 'update'), 'assignedtoresource', 1, '', 0, '', '', 0, 0, 0, 'AND u.statut != 0', 1, $listofresourceid);
		print '</div>';
		print '</td></tr>';
	}

	// Status
	print '<tr><td>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td>';
	print '<td>';
	$percent = $complete !== '' ? $complete : -1;
	if (GETPOSTISSET('status')) {
		$percent = GETPOST('status');
	} elseif (GETPOSTISSET('percentage')) {
		$percent = GETPOSTINT('percentage');
	} else {
		if ($complete == '0' || GETPOST("afaire") == 1) {
			$percent = '0';
		} elseif ($complete == 100 || GETPOST("afaire") == 2) {
			$percent = 100;
		}
	}
	$formactions->form_select_status_action('formaction', $percent, 1, 'complete', 0, 0, 'maxwidth200');
	print '</td></tr>';

	print '</table>';


	print '<br><hr><br>';


	print '<table class="border centpercent">';

	if (isModEnabled("societe")) {
		// Related company
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ActionOnCompany").'</td><td>';
		if (GETPOSTINT('socid') > 0) {
			$societe = new Societe($db);
			$societe->fetch(GETPOSTINT('socid'));
			print $societe->getNomUrl(1);
			print '<input type="hidden" id="socid" name="socid" value="'.GETPOSTINT('socid').'">';
		} else {
			$events = array();
			$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1&token='.currentToken(), 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
			//For external user force the company to user company
			if (!empty($user->socid)) {
				print img_picto('', 'company', 'class="paddingrightonly"').$form->select_company($user->socid, 'socid', '', 1, 1, 0, $events, 0, 'minwidth300 widthcentpercentminusxx maxwidth500');
			} else {
				print img_picto('', 'company', 'class="paddingrightonly"').$form->select_company('', 'socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300 widthcentpercentminusxx maxwidth500');
			}
		}
		print '</td></tr>';

		// Related contact
		print '<tr><td class="nowrap">'.$langs->trans("ActionOnContact").'</td><td>';
		$preselectedids = GETPOST('socpeopleassigned', 'array:int');
		if (GETPOSTINT('contactid')) {
			$preselectedids[GETPOSTINT('contactid')] = GETPOSTINT('contactid');
		}
		if ($origin == 'contact') {
			$preselectedids[GETPOSTINT('originid')] = GETPOSTINT('originid');
		}
		// select "all" or "none" contact by default
		if (getDolGlobalInt('MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT')) {
			// Warning: MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT will hangs on large databases
			$select_contact_default = 0; // select "all" contacts by default : avoid to use it if there is a lot of contacts
		} else {
			$select_contact_default = -1; // select "none" by default
		}
		print img_picto('', 'contact', 'class="paddingrightonly"');

		if (getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT') && $conf->use_javascript_ajax) {
			// FIXME Use a select without the "multiple" (not supported when CONTACT_USE_SEARCH_TO_SELECT is on) or allow use only when $object->socid is set...
			/*
			 $selected = array_keys($object->socpeopleassigned);
			 print $form->select_contact(getDolGlobalString('MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT') ? 0 : $object->socid, $selected, 'socpeopleassigned', 1, '', '', 1, 'minwidth300 widthcentpercentminusx', false, 0, 0, array());
			 */
			$sav = getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT');
			$conf->global->CONTACT_USE_SEARCH_TO_SELECT = 0;
			print $form->selectcontacts(GETPOSTISSET('socid') ? GETPOSTINT('socid') : $select_contact_default, $preselectedids, 'socpeopleassigned[]', 1, '', '', 0, 'minwidth300 widthcentpercentminusxx maxwidth500', 0, 0, array(), false, 'multiple', 'contactid');
			$conf->global->CONTACT_USE_SEARCH_TO_SELECT = $sav;
		} else {
			print $form->selectcontacts(GETPOSTISSET('socid') ? GETPOSTINT('socid') : $select_contact_default, $preselectedids, 'socpeopleassigned[]', 1, '', '', 0, 'minwidth300 widthcentpercentminusxx maxwidth500', 0, 0, array(), false, 'multiple', 'contactid');
		}

		print '</td></tr>';
	}

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");

		$projectid = GETPOSTINT('projectid');

		print '<tr><td class="titlefieldcreate">'.$langs->trans("Project").'</td><td id="project-input-container">';
		print img_picto('', 'project', 'class="pictofixedwidth"');
		print $formproject->select_projects(($object->socid > 0 ? $object->socid : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500 widthcentpercentminusxx maxwidth500');

		print '&nbsp;<a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.(empty($societe->id) ? '' : $societe->id).'&action=create&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create').'">';
		print '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
		$urloption = '?action=create&donotclearsession=1';
		$url = dol_buildpath('comm/action/card.php', 2).$urloption;

		// update task list
		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
	               $("#projectid").change(function () {
                        var url = "'.DOL_URL_ROOT.'/projet/ajax/projects.php?mode=gettasks&socid="+$("#search_socid").val()+"&projectid="+$("#projectid").val();
						console.log("Call url to get new list of tasks: "+url);
                        $.get(url, function(data) {
                            console.log(data);
                            if (data) $("#taskid").html(data).select2();
                        })
                  });
               })';
		print '</script>'."\n";

		print '</td></tr>';

		// Task
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Task").'</td><td id="project-task-input-container" >';
		print img_picto('', 'projecttask', 'class="paddingrightonly"');
		$projectsListId = false;
		if (!empty($projectid)) {
			$projectsListId = $projectid;
		}

		$tid = GETPOSTISSET("projecttaskid") ? GETPOSTINT("projecttaskid") : (GETPOSTISSET("taskid") ? GETPOSTINT("taskid") : '');

		$formproject->selectTasks((!empty($societe->id) ? $societe->id : -1), $tid, 'taskid', 24, 0, '1', 1, 0, 0, 'maxwidth500 widthcentpercentminusxx', $projectsListId);
		print '</td></tr>';
	}

	// Object linked
	if (!empty($origin) && !empty($originid)) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$hasPermissionOnLinkedObject = 0;

		$elProp = getElementProperties($origin);
		if ($user->hasRight($elProp['module'], 'read') || $user->hasRight($elProp['module'], $elProp['element'], 'read')) {
			$hasPermissionOnLinkedObject = 1;
		}
		//var_dump('origin='.$origin.' originid='.$originid.' hasPermissionOnLinkedObject='.$hasPermissionOnLinkedObject);

		if (! in_array($origin, array('societe', 'project', 'task', 'user'))) {
			// We do not use link for object that already contains a hard coded field to make links with agenda events
			print '<tr><td class="titlefieldcreate">'.$langs->trans("LinkedObject").'</td>';
			print '<td colspan="3">';
			if ($hasPermissionOnLinkedObject) {
				print dolGetElementUrl($originid, $origin, 1);
				print '<input type="hidden" name="fk_element" value="'.$originid.'">';
				print '<input type="hidden" name="elementtype" value="'.$origin.'">';
				print '<input type="hidden" name="originid" value="'.$originid.'">';
				print '<input type="hidden" name="origin" value="'.$origin.'">';
			} else {
				print '<!-- no permission on object to link '.$origin.' id '.$originid.' -->';
			}
			print '</td></tr>';
		}
	}

	// Priority
	if (getDolGlobalString('AGENDA_SUPPORT_PRIORITY_IN_EVENTS')) {
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("Priority").'</td><td colspan="3">';
		print '<input type="text" name="priority" value="'.(GETPOSTISSET('priority') ? GETPOSTINT('priority') : ($object->priority ? $object->priority : '')).'" size="5">';
		print '</td></tr>';
	}

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('note', (GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : $object->note_private), '', 120, 'dolibarr_notes', 'In', true, true, isModEnabled('fckeditor'), ROWS_4, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}

	print '</table>';


	if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
		//checkbox create reminder
		print '<hr>';
		print '<br>';
		print '<label for="addreminder">'.img_picto('', 'bell', 'class="pictofixedwidth"').$langs->trans("AddReminder").'</label> <input type="checkbox" id="addreminder" name="addreminder"><br><br>';

		print '<div class="reminderparameters" style="display: none;">';

		print '<table class="border centpercent">';

		//Reminder
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderTime").'</td><td colspan="3">';
		print '<input class="width50" type="number" name="offsetvalue" value="'.(GETPOSTISSET('offsetvalue') ? GETPOSTINT('offsetvalue') : getDolGlobalInt('AGENDA_REMINDER_DEFAULT_OFFSET', 30)).'"> ';
		print $form->selectTypeDuration('offsetunit', 'i', array('y', 'm'));
		print '</td></tr>';

		//Reminder Type
		print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderType").'</td><td colspan="3">';
		print $form->selectarray('selectremindertype', $TRemindTypes, '', 0, 0, 0, '', 0, 0, 0, '', 'minwidth200 maxwidth500', 1);
		print '</td></tr>';

		//Mail Model
		if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
			print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("EMailTemplates").'</td><td colspan="3">';
			print $form->selectModelMail('actioncommsend', 'actioncomm_send', 1, 1);
			print '</td></tr>';
		}

		print '</table>';
		print '</div>';

		print "\n".'<script type="text/javascript">';
		print '$(document).ready(function () {
	            		$("#addreminder").click(function(){
							console.log("Click on addreminder");
	            		    if (this.checked) {
	            		    	$(".reminderparameters").show();
                            } else {
                            	$(".reminderparameters").hide();
                            }
							$("#selectremindertype").select2("destroy");
							$("#selectremindertype").select2();
							$("#select_offsetunittype_duration").select2("destroy");
							$("#select_offsetunittype_duration").select2();
							selectremindertype();
	            		 });

	            		$("#selectremindertype").change(function(){
							selectremindertype();
	            		});

						function selectremindertype() {
							console.log("Call selectremindertype");
	            	        var selected_option = $("#selectremindertype option:selected").val();
	            		    if(selected_option == "email") {
	            		        $("#select_actioncommsendmodel_mail").closest("tr").show();
	            		    } else {
	            			    $("#select_actioncommsendmodel_mail").closest("tr").hide();
	            		    }
						}

                   })';
		print '</script>'."\n";
	}

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Add");

	print "</form>";
}

// View or edit
if ($id > 0) {
	$result1 = $object->fetch($id);
	if ($result1 <= 0) {
		$langs->load("errors");
		print $langs->trans("ErrorRecordNotFound");

		llxFooter();
		exit;
	}

	$result2 = $object->fetch_thirdparty();
	$result2 = $object->fetchProject();
	$result3 = $object->fetch_contact();
	$result4 = $object->fetch_userassigned();
	$result5 = $object->fetch_optionals();

	if ($listUserAssignedUpdated || $donotclearsession) {
		$percentage = in_array(GETPOST('status'), array(-1, 100)) ? GETPOST('status') : (in_array($complete, array(-1, 100)) ? $complete : GETPOSTINT("percentage")); // If status is -1 or 100, percentage is not defined and we must use status

		$datep = dol_mktime($fulldayevent ? 0 : $aphour, $fulldayevent ? 0 : $apmin, 0, GETPOSTINT("apmonth"), GETPOSTINT("apday"), GETPOSTINT("apyear"), 'tzuserrel');
		$datef = dol_mktime($fulldayevent ? 23 : $p2hour, $fulldayevent ? 59 : $p2min, $fulldayevent ? 59 : 0, GETPOSTINT("p2month"), GETPOSTINT("p2day"), GETPOSTINT("p2year"), 'tzuserrel');

		$object->type_id     = dol_getIdFromCode($db, GETPOST("actioncode", 'aZ09'), 'c_actioncomm');
		$object->label       = GETPOST("label", "alphanohtml");
		$object->datep       = $datep;
		$object->datef       = $datef;
		$object->percentage  = $percentage;
		$object->priority    = GETPOST("priority", "alphanohtml");
		$object->fulldayevent = GETPOST("fullday") ? 1 : 0;
		$object->location    = GETPOST('location', "alphanohtml");
		$object->socid       = GETPOSTINT("socid");
		$socpeopleassigned   = GETPOST("socpeopleassigned", 'array');
		foreach ($socpeopleassigned as $tmpid) {
			$object->socpeopleassigned[$id] = array('id' => $tmpid);
		}
		$object->contact_id   = GETPOSTINT("contactid");
		$object->fk_project  = GETPOSTINT("projectid");

		$object->note_private = GETPOST("note", 'restricthtml');
	}

	if ($result2 < 0 || $result3 < 0 || $result4 < 0 || $result5 < 0) {
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


	/*
	 * Show tabs
	 */

	$head = actions_prepare_head($object);

	$now = dol_now();
	$delay_warning = getDolGlobalInt('MAIN_DELAY_ACTIONS_TODO') * 24 * 60 * 60;


	// Confirmation suppression action
	if ($action == 'delete') {
		print $form->formconfirm("card.php?id=".urlencode((string) ($id)), $langs->trans("DeleteAction"), $langs->trans("ConfirmDeleteAction"), "confirm_delete", '', '', 1);
	}

	if ($action == 'edit') {
		if (!empty($conf->use_javascript_ajax)) {
			print "\n".'<script type="text/javascript">';
			print '$(document).ready(function () {
	            		function setdatefields()
	            		{
	            			if ($("#fullday:checked").val() == null) {
	            				$(".fulldaystarthour").removeAttr("disabled");
	            				$(".fulldaystartmin").removeAttr("disabled");
	            				$(".fulldayendhour").removeAttr("disabled");
	            				$(".fulldayendmin").removeAttr("disabled");
	            			} else {
								$(".fulldaystarthour").prop("disabled", true).val("00");
								$(".fulldaystartmin").prop("disabled", true).val("00");
								$(".fulldayendhour").prop("disabled", true).val("23");
								$(".fulldayendmin").prop("disabled", true).val("59");
	            			}
	            		}
	            		setdatefields();
	            		$("#fullday").change(function() {
	            			setdatefields();
	            		});
	            		$("#actioncode").change(function() {
                        	if ($("#actioncode").val() == \'AC_RDV\') $("#dateend").addClass("fieldrequired");
                        	else $("#dateend").removeClass("fieldrequired");
                    	});
                   })';
			print '</script>'."\n";
		}

		print '<form name="formaction" action="'.$_SERVER['PHP_SELF'].'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="ref_ext" value="'.$object->ref_ext.'">';
		print '<input type="hidden" name="page_y" value="">';
		if ($backtopage) {
			print '<input type="hidden" name="backtopage" value="'.($backtopage != '1' ? $backtopage : '').'">';
		}
		if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE') && ! preg_match('/^TICKET_MSG_PRIVATE/', $object->code)) {
			print '<input type="hidden" name="actioncode" value="'.$object->type_code.'">';
		}

		print dol_get_fiche_head($head, 'card', $langs->trans("Action"), 0, 'action');

		print '<table class="border tableforfield" width="100%">';

		// Ref
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td colspan="3">'.$object->id.'</td></tr>';

		// Type of event
		if (getDolGlobalString('AGENDA_USE_EVENT_TYPE') && $object->elementtype != "ticket") {
			print '<tr><td class="fieldrequired">'.$langs->trans("Type").'</td><td colspan="3">';
			if ($object->type_code != 'AC_OTH_AUTO') {
				print img_picto($langs->trans("ActionType"), 'square', 'class="fawidth30 inline-block" style="color: #ddd;"');
				print $formactions->select_type_actions(GETPOST("actioncode", 'aZ09') ? GETPOST("actioncode", 'aZ09') : $object->type_code, "actioncode", "systemauto", 0, 0, 0, 1);
			} else {
				print '<input type="hidden" name="actioncode" value="'.$object->type_code.'">';
				print $object->getTypePicto();
				print $langs->trans("Action".$object->type_code);
			}
			print '</td></tr>';
		}

		// Private
		if ($object->elementtype == 'ticket') {
			print '<tr><td>'.$langs->trans("MarkMessageAsPrivate");
			print ' '.$form->textwithpicto('', $langs->trans("TicketMessagePrivateHelp"), 1, 'help');
			print '</td><td colspan="3"><input type="checkbox" id="private" name="private" '.(preg_match('/^TICKET_MSG_PRIVATE/', $object->code) ? ' checked' : '').'></td></tr>';
		}

		// Title
		print '<tr><td'.(!getDolGlobalString('AGENDA_USE_EVENT_TYPE') ? ' class="fieldrequired titlefieldcreate"' : '').'>'.$langs->trans("Title").'</td><td colspan="3"><input type="text" name="label" class="soixantepercent" value="'.$object->label.'"></td></tr>';

		// Full day event
		print '<tr><td><span class="fieldrequired">'.$langs->trans("Date").'</span></td><td colspan="3" class="valignmiddle height30 small"><input type="checkbox" id="fullday" name="fullday" '.($object->fulldayevent ? ' checked' : '').'>';
		print '<label for="fullday">'.$langs->trans("EventOnFullDay").'</label>';

		// // Recurring event
		// $userepeatevent = (getDolGlobalInt('MAIN_FEATURES_LEVEL') == 2 ? 1 : 0);
		// if ($userepeatevent) {
		// 	// Repeat
		// 	//print '<tr><td></td><td colspan="3">';
		// 	print ' &nbsp; &nbsp; &nbsp; &nbsp; <div class="opacitymedium inline-block">';
		// 	print img_picto($langs->trans("Recurrence"), 'recurring', 'class="paddingright2"');
		// 	print '<input type="hidden" name="recurid" value="'.$object->recurid.'">';
		// 	$selectedrecurrulefreq = 'no';
		// 	$selectedrecurrulebymonthday = '';
		// 	$selectedrecurrulebyday = '';
		// 	if ($object->recurrule && preg_match('/FREQ=([A-Z]+)/i', $object->recurrule, $reg)) {
		// 		$selectedrecurrulefreq = $reg[1];
		// 	}
		// 	if ($object->recurrule && preg_match('/FREQ=MONTHLY.*BYMONTHDAY=(\d+)/i', $object->recurrule, $reg)) {
		// 		$selectedrecurrulebymonthday = $reg[1];
		// 	}
		// 	if ($object->recurrule && preg_match('/FREQ=WEEKLY.*BYDAY(\d+)/i', $object->recurrule, $reg)) {
		// 		$selectedrecurrulebyday = $reg[1];
		// 	}
		// 	print $form->selectarray('recurrulefreq', $arrayrecurrulefreq, $selectedrecurrulefreq, 0, 0, 0, '', 0, 0, 0, '', 'marginrightonly');
		// 	// If recurrulefreq is MONTHLY
		// 	print '<div class="hidden marginrightonly inline-block repeateventBYMONTHDAY">';
		// 	print $langs->trans("DayOfMonth").': <input type="input" size="2" name="BYMONTHDAY" value="'.$selectedrecurrulebymonthday.'">';
		// 	print '</div>';
		// 	// If recurrulefreq is WEEKLY
		// 	print '<div class="hidden marginrightonly inline-block repeateventBYDAY">';
		// 	print $langs->trans("DayOfWeek").': <input type="input" size="4" name="BYDAY" value="'.$selectedrecurrulebyday.'">';
		// 	print '</div>';
		// 	print '<script type="text/javascript">
		// 		jQuery(document).ready(function() {
		// 			function init_repeat()
		// 			{
		// 				if (jQuery("#recurrulefreq").val() == \'MONTHLY\')
		// 				{
		// 					jQuery(".repeateventBYMONTHDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
		// 					jQuery(".repeateventBYDAY").hide();
		// 				}
		// 				else if (jQuery("#recurrulefreq").val() == \'WEEKLY\')
		// 				{
		// 					jQuery(".repeateventBYMONTHDAY").hide();
		// 					jQuery(".repeateventBYDAY").css("display", "inline-block");		/* use this instead of show because we want inline-block and not block */
		// 				}
		// 				else
		// 				{
		// 					jQuery(".repeateventBYMONTHDAY").hide();
		// 					jQuery(".repeateventBYDAY").hide();
		// 				}
		// 			}
		// 			init_repeat();
		// 			jQuery("#recurrulefreq").change(function() {
		// 				init_repeat();
		// 			});
		// 		});
		// 		</script>';
		// 	print '</div>';
		// 	//print '</td></tr>';
		// }
		print '</td></tr>';

		// Date start - end
		print '<tr><td class="nowrap">';
		/*print '<span class="fieldrequired">'.$langs->trans("DateActionStart").'</span>';
		print ' - ';
		print '<span id="dateend"'.($object->type_code == 'AC_RDV' ? ' class="fieldrequired"' : '').'>'.$langs->trans("DateActionEnd").'</span>';
		*/
		print '</td><td td colspan="3">';
		$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
		print $form->selectDate($datep ? $datep : $object->datep, 'ap', 1, 1, 0, "action", 1, 2, 0, 'fulldaystart', '', '', '', 1, '', '', $object->fulldayevent ? ($tzforfullday ? $tzforfullday : 'tzuserrel') : 'tzuserrel');
		print ' <span class="hideonsmartphone">&nbsp; &nbsp; - &nbsp; &nbsp;</span> ';
		print $form->selectDate($datef ? $datef : $object->datef, 'p2', 1, 1, 1, "action", 1, 2, 0, 'fulldayend', '', '', '', 1, '', '', $object->fulldayevent ? ($tzforfullday ? $tzforfullday : 'tzuserrel') : 'tzuserrel');
		print '</td></tr>';

		print '<tr><td class="">&nbsp;</td><td></td></tr>';

		// Assigned to
		$listofuserid = array(); // User assigned
		if (empty($donotclearsession)) {
			if ($object->userownerid > 0) {
				$listofuserid[$object->userownerid] = array(
					'id' => $object->userownerid,
					'type' => 'user',
					//'transparency'=>$object->userassigned[$user->id]['transparency'],
					'transparency' => $object->transparency, // Force transparency on ownerfrom event
					'answer_status' => $object->userassigned[$object->userownerid]['answer_status'],
					'mandatory' => $object->userassigned[$object->userownerid]['mandatory']
				);
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

		$listofcontactid = $object->socpeopleassigned; // Contact assigned
		$listofotherid = $object->otherassigned; // Other undefined email (not used yet)

		print '<tr><td class="tdtop nowrap fieldrequired">'.$langs->trans("ActionAssignedTo").'</td><td colspan="3">';
		print '<div class="assignedtouser">';
		print $form->select_dolusers_forevent(($action == 'create' ? 'add' : 'update'), 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, 'AND u.statut != 0', 1, $listofuserid, $listofcontactid, $listofotherid);
		print '</div>';
		/*if (in_array($user->id,array_keys($listofuserid)))
		{
			print '<div class="myavailability">';
			print $langs->trans("MyAvailability").':  <input id="transparency" type="checkbox" name="transparency"'.($listofuserid[$user->id]['transparency']?' checked':'').'>'.$langs->trans("Busy");
			print '</div>';
		}*/
		print '</td></tr>';

		// Location
		if (!getDolGlobalString('AGENDA_DISABLE_LOCATION')) {
			print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3"><input type="text" name="location" class="minwidth300 maxwidth150onsmartphone" value="'.$object->location.'"></td></tr>';
		}

		// Status
		print '<tr><td class="nowrap">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="3">';
		$percent = GETPOSTISSET("percentage") ? GETPOSTINT("percentage") : $object->percentage;
		$formactions->form_select_status_action('formaction', $percent, 1, 'complete', 0, 0, 'maxwidth200');
		print '</td></tr>';

		// Tags-Categories
		if (isModEnabled('category')) {
			print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACTIONCOMM, '', 'parent', 64, 0, 3);
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_ACTIONCOMM);
			$arrayselected = array();
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
			print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";
		}

		print '</table>';


		print '<br><hr><br>';


		print '<table class="border tableforfield centpercent">';

		if (isModEnabled("societe")) {
			// Related company
			print '<tr><td class="titlefieldcreate">'.$langs->trans("ActionOnCompany").'</td>';
			print '<td>';
			print '<div>';
			$events = array(); // 'method'=parameter action of url, 'url'=url to call that return new list of contacts
			$events[] = array('method' => 'getContacts', 'url' => dol_buildpath('/core/ajax/contacts.php?showempty=1&token='.currentToken(), 1), 'htmlname' => 'contactid', 'params' => array('add-customer-contact' => 'disabled'));
			// TODO Refresh also list of project if conf PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY not defined with list linked to socid ?
			// FIXME If we change company, we may get a project that does not match
			print img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company($object->socid, 'socid', '', 'SelectThirdParty', 1, 0, $events, 0, 'minwidth300');
			print '</div>';
			print '</td></tr>';

			// Related contact
			print '<tr><td>'.$langs->trans("ActionOnContact").'</td><td>';
			print '<div class="maxwidth200onsmartphone">';
			print img_picto('', 'contact', 'class="paddingrightonly"');
			if (getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT') && $conf->use_javascript_ajax) {
				// FIXME Use the select_contact supporting the "multiple"
				/*
				$selected = array_keys($object->socpeopleassigned);
				print $form->select_contact(getDolGlobalString('MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT') ? 0 : $object->socid, $selected, 'socpeopleassigned', 1, '', '', 1, 'minwidth300 widthcentpercentminusx', false, 0, 0, array());
				*/
				$sav = getDolGlobalString('CONTACT_USE_SEARCH_TO_SELECT');
				$conf->global->CONTACT_USE_SEARCH_TO_SELECT = 0;
				print $form->selectcontacts(getDolGlobalString('MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT') ? 0 : ($object->socid > 0 ? $object->socid : -1), array_keys($object->socpeopleassigned), 'socpeopleassigned[]', 1, '', '', 1, 'minwidth300 widthcentpercentminusx', 0, 0, 0, array(), 'multiple', 'contactid');
				$conf->global->CONTACT_USE_SEARCH_TO_SELECT = $sav;
			} else {
				// Warning: MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT will hangs on large databases
				print $form->selectcontacts(getDolGlobalString('MAIN_ACTIONCOM_CAN_ADD_ANY_CONTACT') ? 0 : $object->socid, array_keys($object->socpeopleassigned), 'socpeopleassigned[]', 1, '', '', 1, 'minwidth300 widthcentpercentminusx', 0, 0, 0, array(), 'multiple', 'contactid');
			}
			print '</div>';
			print '</td>';
			print '</tr>';
		}

		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");

			print '<tr><td class="titlefieldcreate">'.$langs->trans("Project").'</td><td>';
			print img_picto('', 'project', 'class="pictofixedwidth"');
			$numprojet = $formproject->select_projects(($object->socid > 0 ? $object->socid : -1), $object->fk_project, 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, 'maxwidth500 widthcentpercentminusxx');
			if ($numprojet == 0) {
				print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$object->socid.'&action=create&token='.newToken().'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id.'&action=edit').'"><span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddProject").'"></span></a>';
			}
			print '</td></tr>';
		}

		// Priority
		if (getDolGlobalString('AGENDA_SUPPORT_PRIORITY_IN_EVENTS')) {
			print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("Priority").'</td><td>';
			print '<input type="text" name="priority" value="'.($object->priority ? $object->priority : '').'" size="5">';
			print '</td></tr>';
		}

		// Object linked
		if (!empty($object->fk_element) && !empty($object->elementtype)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print '<tr>';
			print '<td>'.$langs->trans("LinkedObject").'</td>';

			if ($object->elementtype == 'task' && isModEnabled('project')) {
				print '<td id="project-task-input-container" >';

				$urloption = '?action=create&donotclearsession=1'; // we use create not edit for more flexibility
				$url = DOL_URL_ROOT.'/comm/action/card.php'.$urloption;

				// update task list
				print "\n".'<script type="text/javascript" >';
				print '$(document).ready(function () {
	              $("#projectid").change(function () {
                        var url = "'.$url.'&projectid="+$("#projectid").val();
                        $.get(url, function(data) {
                            console.log($( data ).find("#fk_element").html());
                            if (data) $("#fk_element").html( $( data ).find("#taskid").html() ).select2();
                        })
                  });
                })';
				print '</script>'."\n";

				$formproject->selectTasks((!empty($societe->id) ? $societe->id : -1), $object->fk_element, 'fk_element', 24, 0, 0, 1, 0, 0, 'maxwidth500', $object->fk_project);
				print '<input type="hidden" name="elementtype" value="'.$object->elementtype.'">';

				print '</td>';
			} else {
				print '<td>';
				print dolGetElementUrl($object->fk_element, $object->elementtype, 1);
				print '<input type="hidden" name="fk_element" value="'.$object->fk_element.'">';
				print '<input type="hidden" name="elementtype" value="'.$object->elementtype.'">';
				print '</td>';
			}

			print '</tr>';
		}

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
		// Editeur wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('note', $object->note_private, '', 120, 'dolibarr_notes', 'In', true, true, isModEnabled('fckeditor'), ROWS_4, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'edit', $parameters);
		}

		print '</table>';

		// Reminders
		if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
			$filteruserid = $user->id;
			if ($user->hasRight('agenda', 'allactions', 'read')) {
				$filteruserid = 0;
			}
			$object->loadReminders('', $filteruserid, false);

			print '<hr>';

			if (count($object->reminders) > 0) {
				$checked = 'checked';
				$keys = array_keys($object->reminders);
				$firstreminderId = array_shift($keys);

				$actionCommReminder = $object->reminders[$firstreminderId];
			} else {
				$checked = '';
				$actionCommReminder = new ActionCommReminder($db);
				$actionCommReminder->offsetvalue = getDolGlobalInt('AGENDA_REMINDER_DEFAULT_OFFSET', 30);
				$actionCommReminder->offsetunit = 'i';
				$actionCommReminder->typeremind = 'email';
			}
			$disabled = '';
			if ($object->datep < dol_now()) {
				//$disabled = 'disabled title="'.dol_escape_htmltag($langs->trans("EventExpired")).'"';
			}

			print '<label for="addreminder">'.img_picto('', 'bell', 'class="pictofixedwidth"').$langs->trans("AddReminder").'</label> <input type="checkbox" id="addreminder" name="addreminder"'.($checked ? ' '.$checked : '').($disabled ? ' '.$disabled : '').'><br>';

			print '<div class="reminderparameters" '.(empty($checked) ? 'style="display: none;"' : '').'>';

			print '<br>';

			print '<table class="border centpercent">';

			// Reminder
			print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderTime").'</td><td colspan="3">';
			print '<input type="number" name="offsetvalue" class="width50" value="'.$actionCommReminder->offsetvalue.'"> ';
			print $form->selectTypeDuration('offsetunit', $actionCommReminder->offsetunit, array('y', 'm'));
			print '</td></tr>';

			// Reminder Type
			print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("ReminderType").'</td><td colspan="3">';
			print $form->selectarray('selectremindertype', $TRemindTypes, $actionCommReminder->typeremind, 0, 0, 0, '', 0, 0, 0, '', 'minwidth200', 1);
			print '</td></tr>';

			$hide = '';
			if ($actionCommReminder->typeremind == 'browser') {
				$hide = 'style="display:none;"';
			}

			// Mail Model
			if (getDolGlobalString('AGENDA_REMINDER_EMAIL')) {
				print '<tr '.$hide.'><td class="titlefieldcreate nowrap">'.$langs->trans("EMailTemplates").'</td><td colspan="3">';
				print $form->selectModelMail('actioncommsend', 'actioncomm_send', 1, 1);
				print '</td></tr>';
			}

			print '</table>';

			print "\n".'<script type="text/javascript">';
			print '$(document).ready(function () {
	            		$("#addreminder").click(function(){
	            		    if (this.checked) {
	            		      $(".reminderparameters").show();
                            } else {
                            $(".reminderparameters").hide();
                            }
	            		 });

	            		$("#selectremindertype").change(function(){
	            	        var selected_option = $("#selectremindertype option:selected").val();
	            		    if(selected_option == "email") {
	            		        $("#select_actioncommsendmodel_mail").closest("tr").show();
	            		    } else {
	            			    $("#select_actioncommsendmodel_mail").closest("tr").hide();
	            		    }
	            		});

                   })';
			print '</script>'."\n";

			print '</div>';		// End of div for reminderparameters
		}

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel();

		print '</form>';
	} else {
		print dol_get_fiche_head($head, 'card', $langs->trans("Action"), -1, 'action');

		$formconfirm = '';

		// Clone event
		if ($action == 'clone') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.GETPOST('id'), $langs->trans('ToClone'), $langs->trans('ConfirmCloneEvent', $object->label), 'confirm_clone', array(), 'yes', 1);
		}

		// Call Hook formConfirm
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;

		$linkback = '';
		// Link to other agenda views
		$linkback .= '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&restore_lastsearch_values=1">';
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

		//$linkback.=$out;

		$morehtmlref = '<div class="refidno">';
		// Thirdparty
		//$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
		// Project
		if (isModEnabled('project')) {
			$langs->load("projects");
			//$morehtmlref .= '<br>';
			if ($usercancreate) {
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
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';

		// Show event in view mode
		print '<table class="border tableforfield centpercent">';

		// Type
		if (getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
			print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td>';
			$labeltoshow = $langs->trans("Action".$object->type_code);
			if ($object->code) {
				$labeltoshow .= ' ('.$object->code.')';
			}
			print $object->getTypePicto('pictofixedwidth paddingright', $labeltoshow);
			print $langs->trans("Action".$object->type_code);
			print '</td></tr>';
		}

		// Full day event
		print '<tr><td class="titlefield">'.$langs->trans("EventOnFullDay").'</td><td>'.yn($object->fulldayevent ? 1 : 0, 3).'</td></tr>';

		// Event into a series
		if ($object->recurid) {
			print '<tr><td class="titlefield">'.$langs->trans("EventIntoASerie").'</td><td>'.dol_escape_htmltag($object->recurid).'</td></tr>';
		}

		$rowspan = 4;
		if (!getDolGlobalString('AGENDA_DISABLE_LOCATION')) {
			$rowspan++;
		}

		// Date start
		print '<tr><td>'.$langs->trans("DateActionStart").'</td><td>';
		// Test a date before the 27 march and one after
		//print dol_print_date($object->datep, 'dayhour', 'gmt');
		//print dol_print_date($object->datep, 'dayhour', 'tzuser');
		//print dol_print_date($object->datep, 'dayhour', 'tzuserrel');
		if (empty($object->fulldayevent)) {
			print dol_print_date($object->datep, 'dayhour', 'tzuserrel');
		} else {
			$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
			print dol_print_date($object->datep, 'day', ($tzforfullday ? $tzforfullday : 'tzuserrel'));
		}
		if ($object->percentage == 0 && $object->datep && $object->datep < ($now - $delay_warning)) {
			print img_warning($langs->trans("Late"));
		}
		print '</td>';
		print '</tr>';

		// Date end
		print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td>';
		if (empty($object->fulldayevent)) {
			print dol_print_date($object->datef, 'dayhour', 'tzuserrel');
		} else {
			$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
			print dol_print_date($object->datef, 'day', ($tzforfullday ? $tzforfullday : 'tzuserrel'));
		}
		if ($object->percentage > 0 && $object->percentage < 100 && $object->datef && $object->datef < ($now - $delay_warning)) {
			print img_warning($langs->trans("Late"));
		}
		print '</td></tr>';

		// Location
		if (!getDolGlobalString('AGENDA_DISABLE_LOCATION')) {
			print '<tr><td>'.$langs->trans("Location").'</td><td>'.$object->location.'</td></tr>';
		}

		// Assigned to user
		print '<tr><td class="nowrap">'.$langs->trans("ActionAssignedTo").'</td><td>';
		$listofuserid = array();
		if (empty($donotclearsession)) {
			if ($object->userownerid > 0) {
				$listofuserid[$object->userownerid] = array(
					'id' => $object->userownerid,
					'transparency' => $object->transparency, // Force transparency on owner from property of event
					'answer_status' => $object->userassigned[$object->userownerid]['answer_status'],
					'mandatory' => $object->userassigned[$object->userownerid]['mandatory']
				);
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
		/*
		if ($object->datep != $object->datef && in_array($user->id,array_keys($listofuserid)))
		{
			print '<div class="myavailability">';
			print $langs->trans("MyAvailability").': '.(($object->userassigned[$user->id]['transparency'] > 0)?$langs->trans("Busy"):$langs->trans("Available"));	// We show nothing if event is assigned to nobody
			print '</div>';
		}
		*/
		print '	</td></tr>';

		// Categories
		if (isModEnabled('category')) {
			print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($object->id, Categorie::TYPE_ACTIONCOMM, 1);
			print "</td></tr>";
		}

		print '</table>';

		print '</div>';

		print '<div class="fichehalfright">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		if (isModEnabled("societe")) {
			// Related company
			print '<tr><td class="titlefield">'.$langs->trans("ActionOnCompany").'</td>';
			print '<td>'.(is_object($object->thirdparty) && $object->thirdparty->id ? $object->thirdparty->getNomUrl(1) : ('<span class="opacitymedium">'.$langs->trans("None").'</span>'));
			if (is_object($object->thirdparty) && $object->thirdparty->id > 0 && $object->type_code == 'AC_TEL') {
				if ($object->thirdparty->fetch($object->thirdparty->id)) {
					print "<br>".dol_print_phone($object->thirdparty->phone);
				}
			}
			print '</td></tr>';

			// Related contact
			print '<tr><td>'.$langs->trans("ActionOnContact").'</td>';
			print '<td>';

			if (!empty($object->socpeopleassigned)) {
				foreach ($object->socpeopleassigned as $cid => $Tab) {
					$contact = new Contact($db);
					$result = $contact->fetch($cid);

					if ($result < 0) {
						dol_print_error($db, $contact->error);
					}

					if ($result > 0) {
						print $contact->getNomUrl(1);
						if ($object->type_code == 'AC_TEL') {
							if (!empty($contact->phone_pro)) {
								print '('.dol_print_phone($contact->phone_pro).')';
							}
						}
						print '<div class="paddingright"></div>';
					}
				}
			} else {
				print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
			}
			print '</td></tr>';
		}

		// Priority
		print '<tr><td class="nowrap" class="titlefield">'.$langs->trans("Priority").'</td><td>';
		print($object->priority ? $object->priority : '');
		print '</td></tr>';

		// Object linked (if link is for thirdparty, contact, project it is a recording error. We should not have links in link table
		// for such objects because there is already a dedicated field into table llx_actioncomm.
		if (!empty($object->fk_element) && !empty($object->elementtype) && !in_array($object->elementtype, array('societe', 'contact', 'project'))) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			print '<tr><td>'.$langs->trans("LinkedObject").'</td>';
			$link = dolGetElementUrl($object->fk_element, $object->elementtype, 1);
			print '<td>';
			if (empty($link)) {
				print '<span class="opacitymedium">'.$langs->trans("ObjectDeleted").'</span>';
			} else {
				print $link;
			}
			print '</td></tr>';
		}

		//mail information
		if (!empty($object->email_msgid)) {
			print '<tr><td>'.$langs->trans('MailTopic').'</td>';
			print '<td>'.dol_escape_htmltag($object->email_subject).'</td></tr>';
			print '<tr><td>'.$langs->trans('MailFrom').'</td>';
			print '<td>'.dol_escape_htmltag($object->email_from).'</td></tr>';
			print '<tr><td>'.$langs->trans('MailTo').'</td>';
			print '<td>'.dol_escape_htmltag($object->email_to).'</td></tr>';
			if (!empty($object->email_tocc)) {
				print '<tr><td>'.$langs->trans('MailCC').'</td>';
				print '<td>'.dol_escape_htmltag($object->email_tocc).'</td></tr>';
			}
		}

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td class="wordbreak sensiblehtmlcontent">';
		print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note_private));
		print '</td></tr>';

		// Other attributes
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		// Reminders
		if (getDolGlobalString('AGENDA_REMINDER_EMAIL') || getDolGlobalString('AGENDA_REMINDER_BROWSER')) {
			$filteruserid = $user->id;
			if ($user->hasRight('agenda', 'allactions', 'read')) {
				$filteruserid = 0;
			}
			$object->loadReminders('', $filteruserid, false);

			print '<tr><td class="titlefieldcreate nowrap">'.$langs->trans("Reminders").'</td><td>';

			if (count($object->reminders) > 0) {
				$tmpuserstatic = new User($db);

				foreach ($object->reminders as $actioncommreminderid => $actioncommreminder) {
					print $TRemindTypes[$actioncommreminder->typeremind]['label'];
					if ($actioncommreminder->fk_user > 0) {
						$tmpuserstatic->fetch($actioncommreminder->fk_user);
						print ' ('.$tmpuserstatic->getNomUrl(0, '', 0, 0, 16).')';
					}
					print ' - '.$actioncommreminder->offsetvalue.' '.$TDurationTypes[$actioncommreminder->offsetunit];

					if ($actioncommreminder->status == $actioncommreminder::STATUS_TODO) {
						print ' - <span class="opacitymedium">';
						print $langs->trans("NotSent");
						print ' </span>';
					} elseif ($actioncommreminder->status == $actioncommreminder::STATUS_DONE) {
						print ' - <span class="opacitymedium">';
						print $langs->trans("Done");
						print ' </span>';
					} elseif ($actioncommreminder->status == $actioncommreminder::STATUS_ERROR) {
						print ' - <span class="opacitymedium">';
						print $form->textwithpicto($langs->trans("Error"), $actioncommreminder->lasterror);
						print ' </span>';
					}
					print '<br>';
				}
			}

			print '</td></tr>';
		}

		print '</table>';

		print '</div>';
		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();
	}


	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		if ($action != 'edit') {
			if ($user->hasRight('agenda', 'allactions', 'create') ||
			   (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {
				print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Modify").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Modify").'</a></div>';
			}

			if ($user->hasRight('agenda', 'allactions', 'create') ||
			   (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {
				print '<div class="inline-block divButAction"><a class="butAction" href="card.php?action=clone&object='.$object->element.'&id='.$object->id.'">'.$langs->trans("ToClone").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("ToClone").'</a></div>';
			}

			if ($user->hasRight('agenda', 'allactions', 'delete') ||
			   (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'delete'))) {
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?action=delete&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Delete").'</a></div>';
			} else {
				print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Delete").'</a></div>';
			}
		}
	}

	print '</div>';

	if ($action != 'edit') {
		if (!getDolGlobalString('AGENDA_DISABLE_BUILDDOC')) {
			print '<div class="clearboth"></div><div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			/*
			 * Generated documents
			 */

			$filedir = $conf->agenda->multidir_output[$conf->entity].'/'.$object->id;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

			$genallowed = $user->hasRight('agenda', 'myactions', 'read');
			$delallowed = $user->hasRight('agenda', 'myactions', 'create');


			print $formfile->showdocuments('actions', $object->id, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 0, 0, '', '', '', $langs->getDefaultLang());

			print '</div><div class="fichehalfright">';


			print '</div></div>';
		}
	}
}

// End of page
llxFooter();
$db->close();
