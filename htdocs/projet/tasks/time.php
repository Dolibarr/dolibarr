<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2021	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019-2021  Christophe Battarel		<christophe@altairis.fr>
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
 *	\file		htdocs/projet/tasks/time.php
 *	\ingroup	project
 *	\brief		Page to add new time spent on a task
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formintervention.class.php';

// Load translation files required by the page
$langsLoad=array('projects', 'bills', 'orders');
if (!empty($conf->eventorganization->enabled)) {
	$langsLoad[]='eventorganization';
}

$langs->loadLangs($langsLoad);

$action		= GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm	= GETPOST('confirm', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'timespentlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss	= GETPOST('optioncss', 'alpha');

$id			= GETPOST('id', 'int');
$projectid	= GETPOST('projectid', 'int');
$ref		= GETPOST('ref', 'alpha');
$withproject = GETPOST('withproject', 'int');
$project_ref = GETPOST('project_ref', 'alpha');
$tab        = GETPOST('tab', 'aZ09');

$search_day = GETPOST('search_day', 'int');
$search_month = GETPOST('search_month', 'int');
$search_year = GETPOST('search_year', 'int');
$search_datehour = '';
$search_datewithhour = '';
$search_note = GETPOST('search_note', 'alpha');
$search_duration = GETPOST('search_duration', 'int');
$search_value = GETPOST('search_value', 'int');
$search_task_ref = GETPOST('search_task_ref', 'alpha');
$search_task_label = GETPOST('search_task_label', 'alpha');
$search_user = GETPOST('search_user', 'int');
$search_valuebilled = GETPOST('search_valuebilled', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}		// If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 't.task_date,t.task_datehour,t.rowid';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC,DESC';
}

$childids = $user->getAllChildIds(1);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
//$object = new TaskTime($db);
$hookmanager->initHooks(array('projecttasktime', 'globalcard'));

$object = new Task($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($projectstatic->table_element);
$extrafields->fetch_name_optionals_label($object->table_element);

// Load task
if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}


// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;	  // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) {
	accessforbidden();
}

if ($object->fk_project > 0) {
	restrictedArea($user, 'projet', $object->fk_project, 'projet&project');
} else {
	restrictedArea($user, 'projet', null, 'projet&project');
	// We check user has permission to see all tasks of all users
	if (empty($projectid) && !$user->hasRight('projet', 'all', 'lire')) {
		$search_user = $user->id;
	}
}



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_generateinvoice' && $massaction != 'confirm_generateinter') {
	$massaction = '';
}

$parameters = array('socid'=>$socid, 'projectid'=>$projectid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_day = '';
	$search_month = '';
	$search_year = '';
	$search_date = '';
	$search_datehour = '';
	$search_datewithhour = '';
	$search_note = '';
	$search_duration = '';
	$search_value = '';
	$search_date_creation = '';
	$search_date_update = '';
	$search_task_ref = '';
	$search_task_label = '';
	$search_user = 0;
	$search_valuebilled = '';
	$toselect = array();
	$search_array_options = array();
	$action = '';
}

if ($action == 'addtimespent' && $user->rights->projet->time) {
	$error = 0;

	$timespent_durationhour = GETPOST('timespent_durationhour', 'int');
	$timespent_durationmin = GETPOST('timespent_durationmin', 'int');
	if (empty($timespent_durationhour) && empty($timespent_durationmin)) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}
	if (!GETPOST("userid", 'int')) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorUserNotAssignedToTask'), null, 'errors');
		$error++;
	}

	if (!$error) {
		if ($id || $ref) {
			$object->fetch($id, $ref);
		} else {
			if (!GETPOST('taskid', 'int') || GETPOST('taskid', 'int') < 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), null, 'errors');
				$action = 'createtime';
				$error++;
			} else {
				$object->fetch(GETPOST('taskid', 'int'));
			}
		}

		if (!$error) {
			$object->fetch_projet();

			if (empty($object->project->statut)) {
				setEventMessages($langs->trans("ProjectMustBeValidatedFirst"), null, 'errors');
				$action = 'createtime';
				$error++;
			} else {
				$object->timespent_note = GETPOST("timespent_note", 'alpha');
				if (GETPOST('progress', 'int') > 0) {
					$object->progress = GETPOST('progress', 'int'); // If progress is -1 (not defined), we do not change value
				}
				$object->timespent_duration = GETPOSTINT("timespent_durationhour") * 60 * 60; // We store duration in seconds
				$object->timespent_duration += (GETPOSTINT('timespent_durationmin') ? GETPOSTINT('timespent_durationmin') : 0) * 60; // We store duration in seconds
				if (GETPOST("timehour") != '' && GETPOST("timehour") >= 0) {	// If hour was entered
					$object->timespent_date = dol_mktime(GETPOST("timehour", 'int'), GETPOST("timemin", 'int'), 0, GETPOST("timemonth", 'int'), GETPOST("timeday", 'int'), GETPOST("timeyear", 'int'));
					$object->timespent_withhour = 1;
				} else {
					$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timemonth", 'int'), GETPOST("timeday", 'int'), GETPOST("timeyear", 'int'));
				}
				$object->timespent_fk_user = GETPOST("userid", 'int');
				$result = $object->addTimeSpent($user);
				if ($result >= 0) {
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				} else {
					setEventMessages($langs->trans($object->error), null, 'errors');
					$error++;
				}
			}
		}
	} else {
		if (empty($id)) {
			$action = 'createtime';
		} else {
			$action = 'createtime';
		}
	}
}

if (($action == 'updateline' || $action == 'updatesplitline') && !$cancel && $user->rights->projet->lire) {
	$error = 0;

	if (!GETPOST("new_durationhour") && !GETPOST("new_durationmin")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}

	if (!$error) {
		if (GETPOST('taskid', 'int') != $id) {		// GETPOST('taskid') is id of new task
			$id_temp = GETPOST('taskid', 'int'); // should not overwrite $id


			$object->fetchTimeSpent(GETPOST('lineid', 'int'));

			$result = 0;
			if (in_array($object->timespent_fk_user, $childids) || $user->rights->projet->all->creer) {
				$result = $object->delTimeSpent($user);
			}

			$object->fetch($id_temp, $ref);

			$object->timespent_note = GETPOST("timespent_note_line", "alphanohtml");
			$object->timespent_old_duration = GETPOST("old_duration", "int");
			$object->timespent_duration = GETPOSTINT("new_durationhour") * 60 * 60; // We store duration in seconds
			$object->timespent_duration += (GETPOSTINT("new_durationmin") ? GETPOSTINT('new_durationmin') : 0) * 60; // We store duration in seconds
			if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0) {	// If hour was entered
				$object->timespent_date = dol_mktime(GETPOST("timelinehour"), GETPOST("timelinemin"), 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
				$object->timespent_withhour = 1;
			} else {
				$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
			}
			$object->timespent_fk_user = GETPOST("userid_line", 'int');

			$result = 0;
			if (in_array($object->timespent_fk_user, $childids) || $user->rights->projet->all->creer) {
				$result = $object->addTimeSpent($user);
			}

			if ($result >= 0) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans($object->error), null, 'errors');
				$error++;
			}
		} else {
			$object->fetch($id, $ref);

			$object->timespent_id = GETPOST("lineid", 'int');
			$object->timespent_note = GETPOST("timespent_note_line", "alphanohtml");
			$object->timespent_old_duration = GETPOST("old_duration", "int");
			$object->timespent_duration = GETPOSTINT("new_durationhour") * 60 * 60; // We store duration in seconds
			$object->timespent_duration += (GETPOSTINT("new_durationmin") ? GETPOSTINT('new_durationmin') : 0) * 60; // We store duration in seconds
			if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0) {	// If hour was entered
				$object->timespent_date = dol_mktime(GETPOST("timelinehour", 'int'), GETPOST("timelinemin", 'int'), 0, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));
				$object->timespent_withhour = 1;
			} else {
				$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timelinemonth", 'int'), GETPOST("timelineday", 'int'), GETPOST("timelineyear", 'int'));
			}
			$object->timespent_fk_user = GETPOST("userid_line", 'int');

			$result = 0;
			if (in_array($object->timespent_fk_user, $childids) || $user->rights->projet->all->creer) {
				$result = $object->updateTimeSpent($user);

				if ($result >= 0) {
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				} else {
					setEventMessages($langs->trans($object->error), null, 'errors');
					$error++;
				}
			}
		}
	} else {
		$action = '';
	}
}

if ($action == 'confirm_deleteline' && $confirm == "yes" && $user->rights->projet->supprimer) {
	$object->fetchTimeSpent(GETPOST('lineid', 'int'));	// load properties like $object->timespent_id

	if (in_array($object->timespent_fk_user, $childids) || $user->rights->projet->all->creer) {
		$result = $object->delTimeSpent($user);	// delete line with $object->timespent_id

		if ($result < 0) {
			$langs->load("errors");
			setEventMessages($langs->trans($object->error), null, 'errors');
			$error++;
			$action = '';
		} else {
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		}
	}
}

// Retrieve First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject)) {
	if ($projectstatic->fetch(0, $project_ref) > 0) {
		$tasksarray = $object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0) {
			$id = $tasksarray[0]->id;
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject ? '&withproject=1' : '').(empty($mode) ? '' : '&mode='.$mode));
			exit;
		}
	}
}

// To show all time lines for project
$projectidforalltimes = 0;
if (GETPOST('projectid', 'int') > 0) {
	$projectidforalltimes = GETPOST('projectid', 'int');

	$result = $projectstatic->fetch($projectidforalltimes);
	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}
	$res = $projectstatic->fetch_optionals();
} elseif (GETPOST('project_ref', 'alpha')) {
	$projectstatic->fetch(0, GETPOST('project_ref', 'alpha'));
	$projectidforalltimes = $projectstatic->id;
	$withproject = 1;
} elseif ($id > 0) {
	$object->fetch($id);
	$result = $projectstatic->fetch($object->fk_project);
}
// If not task selected and no project selected
if ($id <= 0 && $projectidforalltimes == 0) {
	$allprojectforuser = $user->id;
}

if ($action == 'confirm_generateinvoice') {
	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}

	if (!($projectstatic->thirdparty->id > 0)) {
		setEventMessages($langs->trans("ThirdPartyRequiredToGenerateInvoice"), null, 'errors');
	} else {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$tmpinvoice = new Facture($db);
		$tmptimespent = new Task($db);
		$tmpproduct = new Product($db);
		$fuser = new User($db);

		$db->begin();
		$idprod = GETPOST('productid', 'int');
		$generateinvoicemode = GETPOST('generateinvoicemode', 'string');
		$invoiceToUse = GETPOST('invoiceid', 'int');

		$prodDurationHours = 1.0;
		if ($idprod > 0) {
			$tmpproduct->fetch($idprod);

			if (empty($tmpproduct->duration_value)) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorDurationForServiceNotDefinedCantCalculateHourlyPrice"), null, 'errors');
			}

			if ($tmpproduct->duration_unit == 'i') {
				$prodDurationHours = 1. / 60;
			}
			if ($tmpproduct->duration_unit == 'h') {
				$prodDurationHours = 1.;
			}
			if ($tmpproduct->duration_unit == 'd') {
				$prodDurationHours = 24.;
			}
			if ($tmpproduct->duration_unit == 'w') {
				$prodDurationHours = 24. * 7;
			}
			if ($tmpproduct->duration_unit == 'm') {
				$prodDurationHours = 24. * 30;
			}
			if ($tmpproduct->duration_unit == 'y') {
				$prodDurationHours = 24. * 365;
			}
			$prodDurationHours *= $tmpproduct->duration_value;

			$dataforprice = $tmpproduct->getSellPrice($mysoc, $projectstatic->thirdparty, 0);

			$pu_ht = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
			$txtva = $dataforprice['tva_tx'];
			$localtax1 = $dataforprice['localtax1'];
			$localtax2 = $dataforprice['localtax2'];
		} else {
			$prodDurationHours = 1;

			$pu_ht = 0;
			$txtva = get_default_tva($mysoc, $projectstatic->thirdparty);
			$localtax1 = get_default_localtax($mysoc, $projectstatic->thirdparty, 1);
			$localtax2 = get_default_localtax($mysoc, $projectstatic->thirdparty, 2);
		}

		$tmpinvoice->socid = $projectstatic->thirdparty->id;
		$tmpinvoice->date = dol_mktime(GETPOST('rehour', 'int'), GETPOST('remin', 'int'), GETPOST('resec', 'int'), GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
		$tmpinvoice->fk_project = $projectstatic->id;

		if ($invoiceToUse) {
			$tmpinvoice->fetch($invoiceToUse);
		} else {
			$result = $tmpinvoice->create($user);
			if ($result <= 0) {
				$error++;
				setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
			}
		}

		if (!$error) {
			if ($generateinvoicemode == 'onelineperuser') {
				$arrayoftasks = array();
				foreach ($toselect as $key => $value) {
					// Get userid, timepent
					$object->fetchTimeSpent($value);
					$arrayoftasks[$object->timespent_fk_user]['timespent'] += $object->timespent_duration;
					$arrayoftasks[$object->timespent_fk_user]['totalvaluetodivideby3600'] += ($object->timespent_duration * $object->timespent_thm);
				}

				foreach ($arrayoftasks as $userid => $value) {
					$fuser->fetch($userid);
					//$pu_ht = $value['timespent'] * $fuser->thm;
					$username = $fuser->getFullName($langs);

					// Define qty per hour
					$qtyhour = $value['timespent'] / 3600;
					$qtyhourtext = convertSecondToTime($value['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

					// If no unit price known
					if (empty($pu_ht)) {
						$pu_ht = price2num($value['totalvaluetodivideby3600'] / 3600, 'MU');
					}

					// Add lines
					$lineid = $tmpinvoice->addline($langs->trans("TimeSpentForInvoice", $username).' : '.$qtyhourtext, $pu_ht, round($qtyhour / $prodDurationHours, 2), $txtva, $localtax1, $localtax2, ($idprod > 0 ? $idprod : 0));

					// Update lineid into line of timespent
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET invoice_line_id = '.((int) $lineid).', invoice_id = '.((int) $tmpinvoice->id);
					$sql .= ' WHERE rowid IN ('.$db->sanitize(join(',', $toselect)).') AND fk_user = '.((int) $userid);
					$result = $db->query($sql);
					if (!$result) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
				}
			} elseif ($generateinvoicemode == 'onelineperperiod') {	// One line for each time spent line
				$arrayoftasks = array();
				$withdetail=GETPOST('detail_time_duration', 'alpha');
				foreach ($toselect as $key => $value) {
					// Get userid, timepent
					$object->fetchTimeSpent($value);
					// $object->id is the task id
					$ftask = new Task($db);
					$ftask->fetch($object->id);

					$fuser->fetch($object->timespent_fk_user);
					$username = $fuser->getFullName($langs);

					$arrayoftasks[$object->timespent_id]['timespent'] = $object->timespent_duration;
					$arrayoftasks[$object->timespent_id]['totalvaluetodivideby3600'] = $object->timespent_duration * $object->timespent_thm;
					$arrayoftasks[$object->timespent_id]['note'] = $ftask->ref.' - '.$ftask->label.' - '.$username.($object->timespent_note ? ' - '.$object->timespent_note : '');		// TODO Add user name in note
					if (!empty($withdetail)) {
						if (!empty($conf->fckeditor->enabled) && !empty($conf->global->FCKEDITOR_ENABLE_DETAILS)) {
							$arrayoftasks[$object->timespent_id]['note'] .= "<br/>";
						} else {
							$arrayoftasks[$object->timespent_id]['note'] .= "\n";
						}

						if (!empty($object->timespent_withhour)) {
							$arrayoftasks[$object->timespent_id]['note'] .= $langs->trans("Date") . ': ' . dol_print_date($object->timespent_datehour);
						} else {
							$arrayoftasks[$object->timespent_id]['note'] .= $langs->trans("Date") . ': ' . dol_print_date($object->timespent_date);
						}
						$arrayoftasks[$object->timespent_id]['note'] .= ' - '.$langs->trans("Duration").': '.convertSecondToTime($object->timespent_duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
					}
					$arrayoftasks[$object->timespent_id]['user'] = $object->timespent_fk_user;
				}

				foreach ($arrayoftasks as $timespent_id => $value) {
					$userid = $value['user'];
					//$pu_ht = $value['timespent'] * $fuser->thm;

					// Define qty per hour
					$qtyhour = $value['timespent'] / 3600;

					// If no unit price known
					if (empty($pu_ht)) {
						$pu_ht = price2num($value['totalvaluetodivideby3600'] / 3600, 'MU');
					}

					// Add lines
					$lineid = $tmpinvoice->addline($value['note'], $pu_ht, round($qtyhour / $prodDurationHours, 2), $txtva, $localtax1, $localtax2, ($idprod > 0 ? $idprod : 0));
					//var_dump($lineid);exit;

					// Update lineid into line of timespent
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET invoice_line_id = '.((int) $lineid).', invoice_id = '.((int) $tmpinvoice->id);
					$sql .= ' WHERE rowid IN ('.$db->sanitize(join(',', $toselect)).') AND fk_user = '.((int) $userid);
					$result = $db->query($sql);
					if (!$result) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
				}
			} elseif ($generateinvoicemode == 'onelinepertask') {	// One line for each different task
				$arrayoftasks = array();
				foreach ($toselect as $key => $value) {
					// Get userid, timepent
					$object->fetchTimeSpent($value);		// Call method to get list of timespent for a timespent line id (We use the utiliy method found into Task object)
					// $object->id is now the task id
					$arrayoftasks[$object->id]['timespent'] += $object->timespent_duration;
					$arrayoftasks[$object->id]['totalvaluetodivideby3600'] += ($object->timespent_duration * $object->timespent_thm);
				}

				foreach ($arrayoftasks as $task_id => $value) {
					$ftask = new Task($db);
					$ftask->fetch($task_id);
					// Define qty per hour
					$qtyhour = $value['timespent'] / 3600;
					$qtyhourtext = convertSecondToTime($value['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

					if ($idprod > 0) {
						// If a product is defined, we msut use the $prodDurationHours and $pu_ht of product (already set previously).
						$pu_ht_for_task = $pu_ht;
						// If we want to reuse the value of timespent (so use same price than cost price)
						if (!empty($conf->global->PROJECT_TIME_SPENT_INTO_INVOICE_USE_VALUE)) {
							$pu_ht_for_task = price2num($value['totalvaluetodivideby3600'] / $value['timespent'], 'MU') * $prodDurationHours;
						}
						$pa_ht = price2num($value['totalvaluetodivideby3600'] / $value['timespent'], 'MU') * $prodDurationHours;
					} else {
						// If not product used, we use the hour unit for duration and unit price.
						$pu_ht_for_task = 0;
						// If we want to reuse the value of timespent (so use same price than cost price)
						if (!empty($conf->global->PROJECT_TIME_SPENT_INTO_INVOICE_USE_VALUE)) {
							$pu_ht_for_task = price2num($value['totalvaluetodivideby3600'] / $value['timespent'], 'MU');
						}
						$pa_ht = price2num($value['totalvaluetodivideby3600'] / $value['timespent'], 'MU');
					}

					// Add lines
					$date_start = '';
					$date_end = '';
					$lineName = $ftask->ref.' - '.$ftask->label;
					$lineid = $tmpinvoice->addline($lineName, $pu_ht_for_task, price2num($qtyhour / $prodDurationHours, 'MS'), $txtva, $localtax1, $localtax2, ($idprod > 0 ? $idprod : 0), 0, $date_start, $date_end, 0, 0, '', 'HT', 0, 1, -1, 0, '', 0, 0, null, $pa_ht);
					if ($lineid < 0) {
						$error++;
						setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
						break;
					}

					if (!$error) {
						// Update lineid into line of timespent
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET invoice_line_id = '.((int) $lineid).', invoice_id = '.((int) $tmpinvoice->id);
						$sql .= ' WHERE rowid IN ('.$db->sanitize(join(',', $toselect)).')';
						$result = $db->query($sql);
						if (!$result) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
							break;
						}
					}
				}
			}
		}

		if (!$error) {
			$urltoinvoice = $tmpinvoice->getNomUrl(0);
			$mesg = $langs->trans("InvoiceGeneratedFromTimeSpent", '{s1}');
			$mesg = str_replace('{s1}', $urltoinvoice, $mesg);
			setEventMessages($mesg, null, 'mesgs');

			//var_dump($tmpinvoice);

			$db->commit();
		} else {
			$db->rollback();
		}
	}
}

if ($action == 'confirm_generateinter') {
	$langs->load('interventions');

	if (!empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();

	if (!($projectstatic->thirdparty->id > 0)) {
		setEventMessages($langs->trans("ThirdPartyRequiredToGenerateIntervention"), null, 'errors');
	} else {
		include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


		require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
		$tmpinter = new Fichinter($db);
		$tmptimespent = new Task($db);
		$fuser = new User($db);

		$db->begin();
		$interToUse = GETPOST('interid', 'int');


		$tmpinter->socid = $projectstatic->thirdparty->id;
		$tmpinter->date = dol_mktime(GETPOST('rehour', 'int'), GETPOST('remin', 'int'), GETPOST('resec', 'int'), GETPOST('remonth', 'int'), GETPOST('reday', 'int'), GETPOST('reyear', 'int'));
		$tmpinter->fk_project = $projectstatic->id;
		$tmpinter->description = $projectstatic->title . ( ! empty($projectstatic->description) ? '-' . $projectstatic->label : '' );

		if ($interToUse) {
			$tmpinter->fetch($interToUse);
		} else {
			$result = $tmpinter->create($user);
			if ($result <= 0) {
				$error++;
				setEventMessages($tmpinter->error, $tmpinter->errors, 'errors');
			}
		}

		if (!$error) {
			$arrayoftasks = array();
			foreach ($toselect as $key => $value) {
				// Get userid, timespent
				$object->fetchTimeSpent($value);
				// $object->id is the task id
				$arrayoftasks[$object->timespent_id]['id'] = $object->id;
				$arrayoftasks[$object->timespent_id]['timespent'] = $object->timespent_duration;
				$arrayoftasks[$object->timespent_id]['totalvaluetodivideby3600'] = $object->timespent_duration * $object->timespent_thm;
				$arrayoftasks[$object->timespent_id]['note'] = $object->timespent_note;
				$arrayoftasks[$object->timespent_id]['date'] = date('Y-m-d H:i:s', $object->timespent_datehour);
			}

			foreach ($arrayoftasks as $timespent_id => $value) {
				$ftask = new Task($db);
				$ftask->fetch($value['id']);
				// Define qty per hour
				$qtyhour = $value['timespent'] / 3600;
				$qtyhourtext = convertSecondToTime($value['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

				// Add lines
				$lineid = $tmpinter->addline($user, $tmpinter->id, $ftask->label . ( ! empty($value['note']) ? ' - ' . $value['note'] : '' ), $value['date'], $value['timespent']);
			}
		}

		if (!$error) {
			$urltointer = $tmpinter->getNomUrl(0);
			$mesg = $langs->trans("InterventionGeneratedFromTimeSpent", '{s1}');
			$mesg = str_replace('{s1}', $urltointer, $mesg);
			setEventMessages($mesg, null, 'mesgs');

			//var_dump($tmpinvoice);

			$db->commit();
		} else {
			$db->rollback();
		}
	}
}

/*
 * View
 */

$arrayofselected = is_array($toselect) ? $toselect : array();

llxHeader("", $langs->trans("Task"));

$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);

if (($id > 0 || !empty($ref)) || $projectidforalltimes > 0 || $allprojectforuser > 0) {
	/*
	 * Fiche projet en mode visu
	 */
	if ($projectidforalltimes > 0) {
		$result = $projectstatic->fetch($projectidforalltimes);
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}
		$res = $projectstatic->fetch_optionals();
	} elseif ($object->fetch($id, $ref) >= 0) {
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$result = $projectstatic->fetch($object->fk_project);
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
			$projectstatic->fetchComments();
		}
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}
		$res = $projectstatic->fetch_optionals();

		$object->project = clone $projectstatic;
	}

	$userRead = $projectstatic->restrictedProjectArea($user, 'read');
	$linktocreatetime = '';

	if ($projectstatic->id > 0) {
		if ($withproject) {
			// Tabs for project
			if (empty($id) || $tab == 'timespent') {
				$tab = 'timespent';
			} else {
				$tab = 'tasks';
			}

			$head = project_prepare_head($projectstatic);
			print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

			$param = ((!empty($mode) && $mode == 'mine') ? '&mode=mine' : '');
			if ($search_user) {
				$param .= '&search_user='.((int) $search_user);
			}
			if ($search_month) {
				$param .= '&search_month='.((int) $search_month);
			}
			if ($search_year) {
				$param .= '&search_year='.((int) $search_year);
			}

			// Project card

			$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Title
			$morehtmlref .= $projectstatic->title;
			// Thirdparty
			if (!empty($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
				$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$projectstatic->thirdparty->getNomUrl(1, 'project');
			}
			$morehtmlref .= '</div>';

			// Define a complementary filter for search of next/prev ref.
			if (empty($user->rights->projet->all->lire)) {
				$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
				$projectstatic->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
			}

			dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Usage
			if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) || empty($conf->global->PROJECT_HIDE_TASKS) || isModEnabled('eventorganization')) {
				print '<tr><td class="tdtop">';
				print $langs->trans("Usage");
				print '</td>';
				print '<td>';
				if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
					print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("ProjectFollowOpportunity");
					print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
					print '<br>';
				}
				if (empty($conf->global->PROJECT_HIDE_TASKS)) {
					print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("ProjectFollowTasks");
					print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
					print '<br>';
				}
				if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
					print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("ProjectBillTimeDescription");
					print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
					print '<br>';
				}
				if (isModEnabled('eventorganization')) {
					print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("EventOrganizationDescriptionLong");
					print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
				}
				print '</td></tr>';
			}

			// Visibility
			print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
			if ($projectstatic->public) {
				print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
				print $langs->trans('SharedProject');
			} else {
				print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
				print $langs->trans('PrivateProject');
			}
			print '</td></tr>';

			// Date start - end
			print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
			$start = dol_print_date($projectstatic->date_start, 'day');
			print ($start ? $start : '?');
			$end = dol_print_date($projectstatic->date_end, 'day');
			print ' - ';
			print ($end ? $end : '?');
			if ($projectstatic->hasDelay()) {
				print img_warning("Late");
			}
			print '</td></tr>';

			// Budget
			print '<tr><td>'.$langs->trans("Budget").'</td><td>';
			if (strcmp($projectstatic->budget_amount, '')) {
				print '<span class="amount">'.price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency).'</span>';
			}
			print '</td></tr>';

			// Other attributes
			$cols = 2;
			//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Description
			print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
			print nl2br($projectstatic->description);
			print '</td></tr>';

			// Categories
			if (isModEnabled('categorie')) {
				print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
				print $form->showCategories($projectstatic->id, 'project', 1);
				print "</td></tr>";
			}

			print '</table>';

			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();

			print '<br>';
		}

		// Link to create time
		$linktocreatetimeBtnStatus = 0;
		$linktocreatetimeUrl = '';
		$linktocreatetimeHelpText = '';
		if (!empty($user->rights->projet->time)) {
			if ($projectstatic->public || $userRead > 0) {
				$linktocreatetimeBtnStatus = 1;

				if (!empty($projectidforalltimes)) {
					// We are on tab 'Time Spent' of project
					$backtourl = $_SERVER['PHP_SELF'].'?projectid='.$projectstatic->id.($withproject ? '&withproject=1' : '');
					$linktocreatetimeUrl = $_SERVER['PHP_SELF'].'?'.($withproject ? 'withproject=1' : '').'&projectid='.$projectstatic->id.'&action=createtime&token='.newToken().$param.'&backtopage='.urlencode($backtourl);
				} else {
					// We are on tab 'Time Spent' of task
					$backtourl = $_SERVER['PHP_SELF'].'?id='.$object->id.($withproject ? '&withproject=1' : '');
					$linktocreatetimeUrl = $_SERVER['PHP_SELF'].'?'.($withproject ? 'withproject=1' : '').($object->id > 0 ? '&id='.$object->id : '&projectid='.$projectstatic->id).'&action=createtime&token='.newToken().$param.'&backtopage='.urlencode($backtourl);
				}
			} else {
				$linktocreatetimeBtnStatus = -2;
				$linktocreatetimeHelpText = $langs->trans("NotOwnerOfProject");
			}
		} else {
			$linktocreatetimeBtnStatus = -2;
			$linktocreatetimeHelpText = $langs->trans("NotEnoughPermissions");
		}

		$paramsbutton = array('morecss'=>'reposition');
		$linktocreatetime = dolGetButtonTitle($langs->trans('AddTimeSpent'), $linktocreatetimeHelpText, 'fa fa-plus-circle', $linktocreatetimeUrl, '', $linktocreatetimeBtnStatus, $paramsbutton);
	}

	$massactionbutton = '';
	$arrayofmassactions = array();

	if ($projectstatic->id > 0) {
		// If we are on a given project.
		if ($projectstatic->usage_bill_time) {
			$arrayofmassactions = array(
				'generateinvoice'=>$langs->trans("GenerateBill"),
				//'builddoc'=>$langs->trans("PDFMerge"),
			);
		}
		if ( ! empty($conf->ficheinter->enabled) && $user->rights->ficheinter->creer) {
			$langs->load("interventions");
			$arrayofmassactions['generateinter'] = $langs->trans("GenerateInter");
		}
	}
	//if ($user->rights->projet->creer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete', 'generateinvoice', 'generateinter'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Show section with information of task. If id of task is not defined and project id defined, then $projectidforalltimes is not empty.
	if (empty($projectidforalltimes) && empty($allprojectforuser)) {
		$head = task_prepare_head($object);
		print dol_get_fiche_head($head, 'task_time', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

		if ($action == 'deleteline') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id > 0 ? "id=".$object->id : 'projectid='.$projectstatic->id).'&lineid='.GETPOST("lineid", 'int').($withproject ? '&withproject=1' : ''), $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_deleteline", '', '', 1);
		}

		$param = ($withproject ? '&withproject=1' : '');
		$linkback = $withproject ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

		if (!GETPOST('withproject') || empty($projectstatic->id)) {
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			$object->next_prev_filter = " fk_projet IN (".$db->sanitize($projectsListId).")";
		} else {
			$object->next_prev_filter = " fk_projet = ".$projectstatic->id;
		}

		$morehtmlref = '';

		// Project
		if (empty($withproject)) {
			$morehtmlref .= '<div class="refidno">';
			$morehtmlref .= $langs->trans("Project").': ';
			$morehtmlref .= $projectstatic->getNomUrl(1);
			$morehtmlref .= '<br>';

			// Third party
			$morehtmlref .= $langs->trans("ThirdParty").': ';
			if (!empty($projectstatic->thirdparty) && is_object($projectstatic->thirdparty)) {
				$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
			}
			$morehtmlref .= '</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		// Task parent
		print '<tr><td>'.$langs->trans("ChildOfTask").'</td><td>';
		if ($object->fk_task_parent > 0) {
			$tasktmp = new Task($db);
			$tasktmp->fetch($object->fk_task_parent);
			print $tasktmp->getNomUrl(1);
		}
		print '</td></tr>';

		// Date start - Date end
		print '<tr><td class="titlefield">'.$langs->trans("DateStart").' - '.$langs->trans("Deadline").'</td><td>';
		$start = dol_print_date($object->date_start, 'dayhour');
		print ($start ? $start : '?');
		$end = dol_print_date($object->date_end, 'dayhour');
		print ' - ';
		print ($end ? $end : '?');
		if ($object->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Planned workload
		print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
		if ($object->planned_workload) {
			print convertSecondToTime($object->planned_workload, 'allhourmin');
		}
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Progress declared
		print '<tr><td class="titlefield">'.$langs->trans("ProgressDeclared").'</td><td>';
		print $object->progress != '' ? $object->progress.' %' : '';
		print '</td></tr>';

		// Progress calculated
		print '<tr><td>'.$langs->trans("ProgressCalculated").'</td><td>';
		if ($object->planned_workload) {
			$tmparray = $object->getSummaryOfTimeSpent();
			if ($tmparray['total_duration'] > 0) {
				print round($tmparray['total_duration'] / $object->planned_workload * 100, 2).' %';
			} else {
				print '0 %';
			}
		} else {
			print '<span class="opacitymedium">'.$langs->trans("WorkloadNotDefined").'</span>';
		}
		print '</td>';

		print '</tr>';

		print '</table>';

		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();
	}


	if ($projectstatic->id > 0 || $allprojectforuser > 0) {
		if ($action == 'deleteline' && !empty($projectidforalltimes)) {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id > 0 ? "id=".$object->id : 'projectid='.$projectstatic->id).'&lineid='.GETPOST('lineid', 'int').($withproject ? '&withproject=1' : ''), $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_deleteline", '', '', 1);
		}

		// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
		$hookmanager->initHooks(array('tasktimelist'));

		$formconfirm = '';

		if ($action == 'deleteline' && !empty($projectidforalltimes)) {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id > 0 ? "id=".$object->id : 'projectid='.$projectstatic->id).'&lineid='.GETPOST('lineid', 'int').($withproject ? '&withproject=1' : ''), $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_deleteline", '', '', 1);
		}

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid, "projectstatic" => $projectstatic, "withproject" => $withproject);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$formconfirm .= $hookmanager->resPrint;
		} elseif ($reshook > 0) {
			$formconfirm = $hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;

		// Definition of fields for list
		$arrayfields = array();
		$arrayfields['t.task_date'] = array('label'=>$langs->trans("Date"), 'checked'=>1);
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
			$arrayfields['t.task_ref'] = array('label'=>$langs->trans("RefTask"), 'checked'=>1);
			$arrayfields['t.task_label'] = array('label'=>$langs->trans("LabelTask"), 'checked'=>1);
		}
		$arrayfields['author'] = array('label'=>$langs->trans("By"), 'checked'=>1);
		$arrayfields['t.note'] = array('label'=>$langs->trans("Note"), 'checked'=>1);
		$arrayfields['t.task_duration'] = array('label'=>$langs->trans("Duration"), 'checked'=>1);
		$arrayfields['value'] = array('label'=>$langs->trans("Value"), 'checked'=>1, 'enabled'=>(empty($conf->salaries->enabled) ? 0 : 1));
		$arrayfields['valuebilled'] = array('label'=>$langs->trans("Billed"), 'checked'=>1, 'enabled'=>(((!empty($conf->global->PROJECT_HIDE_TASKS) || empty($conf->global->PROJECT_BILL_TIME_SPENT)) ? 0 : 1) && $projectstatic->usage_bill_time));
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

		$arrayfields = dol_sort_array($arrayfields, 'position');

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.urlencode($limit);
		}
		if ($search_month > 0) {
			$param .= '&search_month='.urlencode($search_month);
		}
		if ($search_year > 0) {
			$param .= '&search_year='.urlencode($search_year);
		}
		if ($search_user > 0) {
			$param .= '&search_user='.urlencode($search_user);
		}
		if ($search_task_ref != '') {
			$param .= '&search_task_ref='.urlencode($search_task_ref);
		}
		if ($search_task_label != '') {
			$param .= '&search_task_label='.urlencode($search_task_label);
		}
		if ($search_note != '') {
			$param .= '&search_note='.urlencode($search_note);
		}
		if ($search_duration != '') {
			$param .= '&amp;search_field2='.urlencode($search_duration);
		}
		if ($optioncss != '') {
			$param .= '&optioncss='.urlencode($optioncss);
		}
		/*
		 // Add $param from extra fields
		 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
		 */
		if ($id) {
			$param .= '&id='.urlencode($id);
		}
		if ($projectid) {
			$param .= '&projectid='.urlencode($projectid);
		}
		if ($withproject) {
			$param .= '&withproject='.urlencode($withproject);
		}
		// Add $param from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
		$param .= $hookmanager->resPrint;

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') {
			print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		}
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		if ($action == 'editline') {
			print '<input type="hidden" name="action" value="updateline">';
		} elseif ($action == 'splitline') {
			print '<input type="hidden" name="action" value="updatesplitline">';
		} elseif ($action == 'createtime' && $user->rights->projet->time) {
			print '<input type="hidden" name="action" value="addtimespent">';
		} elseif ($massaction == 'generateinvoice' && $user->rights->facture->lire) {
			print '<input type="hidden" name="action" value="confirm_generateinvoice">';
		} elseif ($massaction == 'generateinter' && $user->rights->ficheinter->lire) {
			print '<input type="hidden" name="action" value="confirm_generateinter">';
		} else {
			print '<input type="hidden" name="action" value="list">';
		}
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="projectid" value="'.$projectidforalltimes.'">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';
		print '<input type="hidden" name="tab" value="'.$tab.'">';
		print '<input type="hidden" name="page_y" value="">';

		// Form to convert time spent into invoice
		if ($massaction == 'generateinvoice') {
			if ($projectstatic->thirdparty->id > 0) {
				print '<table class="noborder centerpercent">';
				print '<tr>';
				print '<td class="titlefield">';
				print $langs->trans('DateInvoice');
				print '</td>';
				print '<td>';
				print $form->selectDate('', '', '', '', '', '', 1, 1);
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>';
				print $langs->trans('Mode');
				print '</td>';
				print '<td>';
				$tmparray = array(
					'onelineperuser'=>'OneLinePerUser',
					'onelinepertask'=>'OneLinePerTask',
					'onelineperperiod'=>'OneLinePerTimeSpentLine',
				);
				print $form->selectarray('generateinvoicemode', $tmparray, 'onelineperuser', 0, 0, 0, '', 1);
				print "\n".'<script type="text/javascript">';
				print '
				$(document).ready(function () {
					setDetailVisibility();
					$("#generateinvoicemode").change(function() {
            			setDetailVisibility();
            		});
            		function setDetailVisibility() {
            			generateinvoicemode = $("#generateinvoicemode option:selected").val();
            			if (generateinvoicemode=="onelineperperiod") {
            				$("#detail_time_duration").show();
            			} else {
            				$("#detail_time_duration").hide();
            			}
            		}
            	});
            			';
				print '</script>'."\n";
				print '<span style="display:none" id="detail_time_duration"><input type="checkbox" value="detail" name="detail_time_duration"/>'.$langs->trans('AddDetailDateAndDuration').'</span>';
				print '</td>';
				print '</tr>';

				if ($conf->service->enabled) {
					print '<tr>';
					print '<td>';
					print $langs->trans('ServiceToUseOnLines');
					print '</td>';
					print '<td>';
					$form->select_produits('', 'productid', '1', 0, $projectstatic->thirdparty->price_level, 1, 2, '', 0, array(), $projectstatic->thirdparty->id, 'None', 0, 'maxwidth500');
					print '</td>';
					print '</tr>';
				}

				print '<tr>';
				print '<td class="titlefield">';
				print $langs->trans('InvoiceToUse');
				print '</td>';
				print '<td>';
				$form->selectInvoice('invoice', '', 'invoiceid', 24, 0, $langs->trans('NewInvoice'), 1, 0, 0, 'maxwidth500', '', 'all');
				print '</td>';
				print '</tr>';
				/*print '<tr>';
				print '<td>';
				print $langs->trans('ValidateInvoices');
				print '</td>';
				print '<td>';
				print $form->selectyesno('validate_invoices', 0, 1);
				print '</td>';
				print '</tr>';*/
				print '</table>';

				print '<br>';
				print '<div class="center">';
				print '<input type="submit" class="button" id="createbills" name="createbills" value="'.$langs->trans('GenerateBill').'">  ';
				print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
				print '<br>';
			} else {
				print '<div class="warning">'.$langs->trans("ThirdPartyRequiredToGenerateInvoice").'</div>';
				print '<div class="center">';
				print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
				$massaction = '';
			}
		} elseif ($massaction == 'generateinter') {
			// Form to convert time spent into invoice
			print '<input type="hidden" name="massaction" value="confirm_createinter">';

			if ($projectstatic->thirdparty->id > 0) {
				print '<br>';
				print '<table class="noborder centpercent">';
				print '<tr>';
				print '<td class="titlefield">';
				print img_picto('', 'intervention', 'class="pictofixedwidth"').$langs->trans('InterToUse');
				print '</td>';
				print '<td>';
				$forminter = new FormIntervention($db);
				print $forminter->select_interventions($projectstatic->thirdparty->id, '', 'interid', 24, $langs->trans('NewInter'), true);
				print '</td>';
				print '</tr>';
				print '</table>';

				print '<div class="center">';
				print '<input type="submit" class="button" id="createinter" name="createinter" value="'.$langs->trans('GenerateInter').'">  ';
				print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
				print '</div>';
				print '<br>';
			} else {
				print '<div class="warning">'.$langs->trans("ThirdPartyRequiredToGenerateIntervention").'</div>';
				print '<div class="center">';
				print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
				print '</div>';
				$massaction = '';
			}
		}

		// Allow Pre-Mass-Action hook (eg for confirmation dialog)
		$parameters = array(
			'toselect' => $toselect,
			'uploaddir' => isset($uploaddir) ? $uploaddir : null
		);

		$reshook = $hookmanager->executeHooks('doPreMassActions', $parameters, $object, $action);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} else {
			print $hookmanager->resPrint;
		}

		/*
		 *	List of time spent
		 */
		$tasks = array();

		$sql = "SELECT t.rowid, t.fk_task, t.task_date, t.task_datehour, t.task_date_withhour, t.task_duration, t.fk_user, t.note, t.thm,";
		$sql .= " pt.ref, pt.label, pt.fk_projet,";
		$sql .= " u.lastname, u.firstname, u.login, u.photo, u.statut as user_status,";
		$sql .= " il.fk_facture as invoice_id, inv.fk_statut,";
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
		$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
		$sql = preg_replace('/,\s*$/', '', $sql);
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as il ON il.rowid = t.invoice_line_id";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as inv ON inv.rowid = il.fk_facture";
		// Add table from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= ", ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";

		if (empty($projectidforalltimes) && empty($allprojectforuser)) {
			// Limit on one task
			$sql .= " AND t.fk_task =".((int) $object->id);
		} elseif (!empty($projectidforalltimes)) {
			// Limit on one project
			$sql .= " AND pt.fk_projet IN (".$db->sanitize($projectidforalltimes).")";
		} elseif (!empty($allprojectforuser)) {
			// Limit on on user
			if (empty($search_user)) {
				$search_user = $user->id;
			}
			$sql .= " AND t.fk_user = ".((int) $search_user);
		}

		if ($search_note) {
			$sql .= natural_search('t.note', $search_note);
		}
		if ($search_task_ref) {
			$sql .= natural_search('pt.ref', $search_task_ref);
		}
		if ($search_task_label) {
			$sql .= natural_search('pt.label', $search_task_label);
		}
		if ($search_user > 0) {
			$sql .= natural_search('t.fk_user', $search_user, 2);
		}
		if ($search_valuebilled == '1') {
			$sql .= ' AND t.invoice_id > 0';
		}
		if ($search_valuebilled == '0') {
			$sql .= ' AND (t.invoice_id = 0 OR t.invoice_id IS NULL)';
		}

		$sql .= dolSqlDateFilter('t.task_datehour', $search_day, $search_month, $search_year);

		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= $db->order($sortfield, $sortorder);

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
			$resql = $db->query($sql);

			if (! $resql) {
				dol_print_error($db);
				exit;
			}

			$nbtotalofrecords = $db->num_rows($resql);
			if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
				$page = 0;
				$offset = 0;
			}
		}
		// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
		if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords) {
			$num = $nbtotalofrecords;
		} else {
			$sql .= $db->plimit($limit + 1, $offset);

			$resql = $db->query($sql);
			if (!$resql) {
				dol_print_error($db);
				exit;
			}

			$num = $db->num_rows($resql);
		}

		if ($num >= 0) {
			if (!empty($projectidforalltimes)) {
				print '<!-- List of time spent for project -->'."\n";

				$title = $langs->trans("ListTaskTimeUserProject");

				print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'clock', 0, $linktocreatetime, '', $limit, 0, 0, 1);
			} else {
				print '<!-- List of time spent -->'."\n";

				$title = $langs->trans("ListTaskTimeForTask");

				print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'clock', 0, $linktocreatetime, '', $limit, 0, 0, 1);
			}

			$i = 0;
			while ($i < $num) {
				$row = $db->fetch_object($resql);
				$tasks[$i] = $row;
				$i++;
			}
			$db->free($resql);
		} else {
			dol_print_error($db);
		}

		/*
		 * Form to add a new line of time spent
		 */
		if ($action == 'createtime' && $user->rights->projet->time) {
			print '<!-- table to add time spent -->'."\n";
			if (!empty($id)) {
				print '<input type="hidden" name="taskid" value="'.$id.'">';
			}

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
			print '<table class="noborder nohover centpercent">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Date").'</td>';
			if (!empty($allprojectforuser)) {
				print '<td>'.$langs->trans("Project").'</td>';
			}
			if (empty($id)) {
				print '<td>'.$langs->trans("Task").'</td>';
			}
			print '<td>'.$langs->trans("By").'</td>';
			print '<td>'.$langs->trans("Note").'</td>';
			print '<td>'.$langs->trans("NewTimeSpent").'</td>';
			print '<td>'.$langs->trans("ProgressDeclared").'</td>';
			if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
				print '<td></td>';
			}
			// Hook fields
			$parameters = array('mode' => 'create');
			$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			print '<td></td>';
			print "</tr>\n";

			print '<tr class="oddeven nohover">';

			// Date
			print '<td class="maxwidthonsmartphone">';
			$newdate = '';
			print $form->selectDate($newdate, 'time', ($conf->browser->layout == 'phone' ? 2 : 1), 1, 2, "timespent_date", 1, 0);
			print '</td>';

			if (!empty($allprojectforuser)) {
				print '<td>';
				// Add project selector
				print '</td>';
			}

			// Task
			$nboftasks = 0;
			if (empty($id)) {
				print '<td class="maxwidthonsmartphone">';
				$nboftasks = $formproject->selectTasks(-1, GETPOST('taskid', 'int'), 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth300', $projectstatic->id, 'progress');
				print '</td>';
			}

			// Contributor
			print '<td class="maxwidthonsmartphone nowraponall">';
			$contactsofproject = $projectstatic->getListContactId('internal');
			if (count($contactsofproject) > 0) {
				print img_object('', 'user', 'class="hideonsmartphone"');
				if (in_array($user->id, $contactsofproject)) {
					$userid = $user->id;
				} else {
					$userid = $contactsofproject[0];
				}

				if ($projectstatic->public) {
					$contactsofproject = array();
				}
				print $form->select_dolusers((GETPOST('userid', 'int') ? GETPOST('userid', 'int') : $userid), 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToProject"), 'maxwidth250');
			} else {
				if ($nboftasks) {
					print img_error($langs->trans('FirstAddRessourceToAllocateTime')).' '.$langs->trans('FirstAddRessourceToAllocateTime');
				}
			}
			print '</td>';

			// Note
			print '<td>';
			print '<textarea name="timespent_note" class="maxwidth100onsmartphone" rows="'.ROWS_2.'">'.(GETPOST('timespent_note') ? GETPOST('timespent_note') : '').'</textarea>';
			print '</td>';

			// Duration - Time spent
			print '<td class="nowraponall">';
			$durationtouse = (GETPOST('timespent_duration') ? GETPOST('timespent_duration') : '');
			if (GETPOSTISSET('timespent_durationhour') || GETPOSTISSET('timespent_durationmin')) {
				$durationtouse = (GETPOST('timespent_durationhour') * 3600 + GETPOST('timespent_durationmin') * 60);
			}
			print $form->select_duration('timespent_duration', $durationtouse, 0, 'text');
			print '</td>';

			// Progress declared
			print '<td class="nowrap">';
			print $formother->select_percent(GETPOST('progress') ?GETPOST('progress') : $object->progress, 'progress', 0, 5, 0, 100, 1);
			print '</td>';

			// Invoiced
			if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
				print '<td>';
				print '</td>';
			}

			// Fields from hook
			$parameters = array('mode' => 'create');
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			print '<td class="center">';
			$form->buttonsSaveCancel();
			print '<input type="submit" name="save" class="button buttongen marginleftonly margintoponlyshort marginbottomonlyshort button-add" value="'.$langs->trans("Add").'">';
			print '<input type="submit" name="cancel" class="button buttongen marginleftonly margintoponlyshort marginbottomonlyshort button-cancel" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';

			print '</table>';
			print '</div>';

			print '<br>';
		}

		$moreforfilter = '';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$moreforfilter .= $hookmanager->resPrint;
		} else {
			$moreforfilter = $hookmanager->resPrint;
		}

		if (!empty($moreforfilter)) {
			print '<div class="liste_titre liste_titre_bydiv centpercent">';
			print $moreforfilter;
			print '</div>';
		}

		$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
		$selectedfields .= (is_array($arrayofmassactions) && count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

		print '<div class="div-table-responsive">';
		print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

		// Fields title search
		print '<tr class="liste_titre_filter">';
		// Date
		if (!empty($arrayfields['t.task_date']['checked'])) {
			print '<td class="liste_titre">';
			if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
				print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
			}
			print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
			print $formother->selectyear($search_year, 'search_year', 1, 20, 5);
			print '</td>';
		}
		if (!empty($allprojectforuser)) {
			print '<td class="liste_titre"></td>';
		}
		// Task
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
			if (!empty($arrayfields['t.task_ref']['checked'])) {
				print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'"></td>';
			}
			if (!empty($arrayfields['t.task_label']['checked'])) {
				print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
			}
		}
		// Author
		if (!empty($arrayfields['author']['checked'])) {
			print '<td class="liste_titre">'.$form->select_dolusers(($search_user > 0 ? $search_user : -1), 'search_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth250').'</td>';
		}
		// Note
		if (!empty($arrayfields['t.note']['checked'])) {
			print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_note" value="'.dol_escape_htmltag($search_note).'"></td>';
		}
		// Duration
		if (!empty($arrayfields['t.task_duration']['checked'])) {
			print '<td class="liste_titre right"></td>';
		}
		// Value in main currency
		if (!empty($arrayfields['value']['checked'])) {
			print '<td class="liste_titre"></td>';
		}
		// Value billed
		if (!empty($arrayfields['valuebilled']['checked'])) {
			print '<td class="liste_titre center">'.$form->selectyesno('search_valuebilled', $search_valuebilled, 1, false, 1).'</td>';
		}

		/*
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
		*/
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields);
		$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Action column
		print '<td class="liste_titre center">';
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
		print '</tr>'."\n";

		print '<tr class="liste_titre">';
		if (!empty($arrayfields['t.task_date']['checked'])) {
			print_liste_field_titre($arrayfields['t.task_date']['label'], $_SERVER['PHP_SELF'], 't.task_date,t.task_datehour,t.rowid', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($allprojectforuser)) {
			print_liste_field_titre("Project", $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		}
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
			if (!empty($arrayfields['t.task_ref']['checked'])) {
				print_liste_field_titre($arrayfields['t.task_ref']['label'], $_SERVER['PHP_SELF'], 'pt.ref', '', $param, '', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['t.task_label']['checked'])) {
				print_liste_field_titre($arrayfields['t.task_label']['label'], $_SERVER['PHP_SELF'], 'pt.label', '', $param, '', $sortfield, $sortorder);
			}
		}
		if (!empty($arrayfields['author']['checked'])) {
			print_liste_field_titre($arrayfields['author']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['t.note']['checked'])) {
			print_liste_field_titre($arrayfields['t.note']['label'], $_SERVER['PHP_SELF'], 't.note', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['t.task_duration']['checked'])) {
			print_liste_field_titre($arrayfields['t.task_duration']['label'], $_SERVER['PHP_SELF'], 't.task_duration', '', $param, '', $sortfield, $sortorder, 'right ');
		}
		if (!empty($arrayfields['value']['checked'])) {
			print_liste_field_titre($arrayfields['value']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
		}
		if (!empty($arrayfields['valuebilled']['checked'])) {
			print_liste_field_titre($arrayfields['valuebilled']['label'], $_SERVER['PHP_SELF'], 'il.total_ht', '', $param, '', $sortfield, $sortorder, 'center ', $langs->trans("SelectLinesOfTimeSpentToInvoice"));
		}
		/*
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
		*/
		// Hook fields
		$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'width="80"', $sortfield, $sortorder, 'center maxwidthsearch ');
		print "</tr>\n";

		$tasktmp = new Task($db);
		$tmpinvoice = new Facture($db);

		$i = 0;

		$total = 0;
		$totalvalue = 0;
		$totalarray = array();
		foreach ($tasks as $task_time) {
			if ($i >= $limit) {
				break;
			}

			$date1 = $db->jdate($task_time->task_date);
			$date2 = $db->jdate($task_time->task_datehour);

			print '<tr class="oddeven">';

			// Date
			if (!empty($arrayfields['t.task_date']['checked'])) {
				print '<td class="nowrap">';
				if ($action == 'editline' && GETPOST('lineid', 'int') == $task_time->rowid) {
					if (empty($task_time->task_date_withhour)) {
						print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 3, 3, 2, "timespent_date", 1, 0);
					} else {
						print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 1, 1, 2, "timespent_date", 1, 0);
					}
				} else {
					print dol_print_date(($date2 ? $date2 : $date1), ($task_time->task_date_withhour ? 'dayhour' : 'day'));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Project ref
			if (!empty($allprojectforuser)) {
				print '<td class="nowraponall">';
				if (empty($conf->cache['project'][$task_time->fk_projet])) {
					$tmpproject = new Project($db);
					$tmpproject->fetch($task_time->fk_projet);
					$conf->cache['project'][$task_time->fk_projet] = $tmpproject;
				} else {
					$tmpproject = $conf->cache['project'][$task_time->fk_projet];
				}
				print $tmpproject->getNomUrl(1);
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Task ref
			if (!empty($arrayfields['t.task_ref']['checked'])) {
				if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {   // Not a dedicated task
					print '<td class="nowrap">';
					if ($action == 'editline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						$formproject->selectTasks(-1, GETPOST('taskid', 'int') ? GETPOST('taskid', 'int') : $task_time->fk_task, 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth300', $projectstatic->id, '');
					} else {
						$tasktmp->id = $task_time->fk_task;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
					}
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			} elseif ($action !== 'createtime') {
				print '<input type="hidden" name="taskid" value="'.$id.'">';
			}

			// Task label
			if (!empty($arrayfields['t.task_label']['checked'])) {
				if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
					print '<td class="nowrap tdoverflowmax300" title="'.dol_escape_htmltag($task_time->label).'">';
					print dol_escape_htmltag($task_time->label);
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			}

			// By User
			if (!empty($arrayfields['author']['checked'])) {
				print '<td class="tdoverflowmax100">';
				if ($action == 'editline' && GETPOST('lineid', 'int') == $task_time->rowid) {
					if (empty($object->id)) {
						$object->fetch($id);
					}
					$contactsoftask = $object->getListContactId('internal');
					if (!in_array($task_time->fk_user, $contactsoftask)) {
						$contactsoftask[] = $task_time->fk_user;
					}
					if (count($contactsoftask) > 0) {
						print img_object('', 'user', 'class="hideonsmartphone"');
						print $form->select_dolusers($task_time->fk_user, 'userid_line', 0, '', 0, '', $contactsoftask, '0', 0, 0, '', 0, '', 'maxwidth200');
					} else {
						print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
					}
				} else {
					$userstatic->id = $task_time->fk_user;
					$userstatic->lastname = $task_time->lastname;
					$userstatic->firstname = $task_time->firstname;
					$userstatic->photo = $task_time->photo;
					$userstatic->statut = $task_time->user_status;
					print $userstatic->getNomUrl(-1);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Note
			if (!empty($arrayfields['t.note']['checked'])) {
				print '<td class="small">';
				if ($action == 'editline' && GETPOST('lineid', 'int') == $task_time->rowid) {
					print '<textarea name="timespent_note_line" width="95%" rows="'.ROWS_1.'">'.dol_escape_htmltag($task_time->note, 0, 1).'</textarea>';
				} else {
					print dol_nl2br($task_time->note);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			} elseif ($action == 'editline' && GETPOST('lineid', 'int') == $task_time->rowid) {
				print '<input type="hidden" name="timespent_note_line" value="'.dol_escape_htmltag($task_time->note, 0, 1).'">';
			}

			// Time spent
			if (!empty($arrayfields['t.task_duration']['checked'])) {
				print '<td class="right nowraponall">';
				if ($action == 'editline' && GETPOST('lineid', 'int') == $task_time->rowid) {
					print '<input type="hidden" name="old_duration" value="'.$task_time->task_duration.'">';
					print $form->select_duration('new_duration', $task_time->task_duration, 0, 'text');
				} else {
					print convertSecondToTime($task_time->task_duration, 'allhourmin');
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.task_duration';
				}
				$totalarray['val']['t.task_duration'] += $task_time->task_duration;
				if (!$i) {
					$totalarray['totaldurationfield'] = $totalarray['nbfield'];
				}
				$totalarray['totalduration'] += $task_time->task_duration;
			}

			// Value spent
			if (!empty($arrayfields['value']['checked'])) {
				$langs->load("salaries");
				$value = price2num($task_time->thm * $task_time->task_duration / 3600, 'MT', 1);

				print '<td class="nowraponall right">';
				print '<span class="amount" title="'.$langs->trans("THM").': '.price($task_time->thm).'">';
				print price($value, 1, $langs, 1, -1, -1, $conf->currency);
				print '</span>';
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'value';
				}
				$totalarray['val']['value'] += $value;
				if (!$i) {
					$totalarray['totalvaluefield'] = $totalarray['nbfield'];
				}
				$totalarray['totalvalue'] += $value;
			}

			// Invoiced
			if (!empty($arrayfields['valuebilled']['checked'])) {
				print '<td class="center">'; // invoice_id and invoice_line_id
				if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
					if ($projectstatic->usage_bill_time) {
						if ($task_time->invoice_id) {
							$result = $tmpinvoice->fetch($task_time->invoice_id);
							if ($result > 0) {
								print $tmpinvoice->getNomUrl(1);
							}
						} else {
							print $langs->trans("No");
						}
					} else {
						print '<span class="opacitymedium">'.$langs->trans("NA").'</span>';
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			/*
			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
			*/

			// Fields from hook
			$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$task_time, 'i'=>$i, 'totalarray'=>&$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			// Action column
			print '<td class="center nowraponall">';
			if (($action == 'editline' || $action == 'splitline') && GETPOST('lineid', 'int') == $task_time->rowid) {
				print '<input type="hidden" name="lineid" value="'.GETPOST('lineid', 'int').'">';
				print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-save" name="save" value="'.$langs->trans("Save").'">';
				print ' ';
				print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			} elseif ($user->hasRight('projet', 'time') || $user->hasRight('projet', 'all', 'creer')) {	 // Read project and enter time consumed on assigned tasks
				if ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
					if (getDolGlobalString('MAIN_FEATURES_LEVEL') >= 2) {
						print '&nbsp;';
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=splitline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
						print img_split('', 'class="pictofixedwidth"');
						print '</a>';
					}

					print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&action=editline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
					print img_edit('default', 0, 'class="pictofixedwidth paddingleft"');
					print '</a>';

					print '<a class="reposition paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&action=deleteline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
					print img_delete('default', 'class="pictodelete paddingleft"');
					print '</a>';

					if ($massactionbutton || $massaction) {	// If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
						$selected = 0;
						if (in_array($task_time->rowid, $arrayofselected)) {
							$selected = 1;
						}
						print '&nbsp;';
						print '<input id="cb'.$task_time->rowid.'" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="'.$task_time->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
					}
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}

			print "</tr>\n";


			// Add line to split

			if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
				print '<tr class="oddeven">';

				// Date
				if (!empty($arrayfields['t.task_date']['checked'])) {
					print '<td class="nowrap">';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						if (empty($task_time->task_date_withhour)) {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 3, 3, 2, "timespent_date", 1, 0);
						} else {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 1, 1, 2, "timespent_date", 1, 0);
						}
					} else {
						print dol_print_date(($date2 ? $date2 : $date1), ($task_time->task_date_withhour ? 'dayhour' : 'day'));
					}
					print '</td>';
				}

				// Project ref
				if (!empty($allprojectforuser)) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						print '</td>';
					}
				}

				// Task ref
				if (!empty($arrayfields['t.task_ref']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						$tasktmp->id = $task_time->fk_task;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
						print '</td>';
					}
				}

				// Task label
				if (!empty($arrayfields['t.task_label']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($task_time->label).'">';
						print dol_escape_htmltag($task_time->label);
						print '</td>';
					}
				}

				// User
				if (!empty($arrayfields['author']['checked'])) {
					print '<td>';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						if (empty($object->id)) {
							$object->fetch($id);
						}
						$contactsoftask = $object->getListContactId('internal');
						if (!in_array($task_time->fk_user, $contactsoftask)) {
							$contactsoftask[] = $task_time->fk_user;
						}
						if (count($contactsoftask) > 0) {
							print img_object('', 'user', 'class="hideonsmartphone"');
							print $form->select_dolusers($task_time->fk_user, 'userid_line', 0, '', 0, '', $contactsoftask);
						} else {
							print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
						}
					} else {
						$userstatic->id = $task_time->fk_user;
						$userstatic->lastname = $task_time->lastname;
						$userstatic->firstname = $task_time->firstname;
						$userstatic->photo = $task_time->photo;
						$userstatic->statut = $task_time->user_status;
						print $userstatic->getNomUrl(-1);
					}
					print '</td>';
				}

				// Note
				if (!empty($arrayfields['t.note']['checked'])) {
					print '<td class="tdoverflowmax300">';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						print '<textarea name="timespent_note_line" width="95%" rows="'.ROWS_1.'">'.dol_escape_htmltag($task_time->note, 0, 1).'</textarea>';
					} else {
						print dol_nl2br($task_time->note);
					}
					print '</td>';
				} elseif ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
					print '<input type="hidden" name="timespent_note_line" rows="'.ROWS_1.'" value="'.dol_escape_htmltag($task_time->note, 0, 1).'">';
				}

				// Time spent
				if (!empty($arrayfields['t.task_duration']['checked'])) {
					print '<td class="right">';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						print '<input type="hidden" name="old_duration" value="'.$task_time->task_duration.'">';
						print $form->select_duration('new_duration', $task_time->task_duration, 0, 'text');
					} else {
						print convertSecondToTime($task_time->task_duration, 'allhourmin');
					}
					print '</td>';
				}

				// Value spent
				if (!empty($arrayfields['value']['checked'])) {
					print '<td class="right">';
					print '<span class="amount">';
					$value = price2num($task_time->thm * $task_time->task_duration / 3600, 'MT', 1);
					print price($value, 1, $langs, 1, -1, -1, $conf->currency);
					print '</span>';
					print '</td>';
				}

				// Value billed
				if (!empty($arrayfields['valuebilled']['checked'])) {
					print '<td class="right">';
					$valuebilled = price2num($task_time->total_ht, '', 1);
					if (isset($task_time->total_ht)) {
						print price($valuebilled, 1, $langs, 1, -1, -1, $conf->currency);
					}
					print '</td>';
				}

				/*
				 // Extra fields
				 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
				 */

				// Fields from hook
				$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$task_time, 'mode' => 'split1');
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Action column
				print '<td class="center nowraponall">';
				print '</td>';

				print "</tr>\n";


				// Line for second dispatching

				print '<tr class="oddeven">';

				// Date
				if (!empty($arrayfields['t.task_date']['checked'])) {
					print '<td class="nowrap">';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						if (empty($task_time->task_date_withhour)) {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline_2', 3, 3, 2, "timespent_date", 1, 0);
						} else {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline_2', 1, 1, 2, "timespent_date", 1, 0);
						}
					} else {
						print dol_print_date(($date2 ? $date2 : $date1), ($task_time->task_date_withhour ? 'dayhour' : 'day'));
					}
					print '</td>';
				}

				// Project ref
				if (!empty($allprojectforuser)) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						print '</td>';
					}
				}

				// Task ref
				if (!empty($arrayfields['t.task_ref']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						$tasktmp->id = $task_time->fk_task;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
						print '</td>';
					}
				}

				// Task label
				if (!empty($arrayfields['t.task_label']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						print $task_time->label;
						print '</td>';
					}
				}

				// User
				if (!empty($arrayfields['author']['checked'])) {
					print '<td>';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						if (empty($object->id)) {
							$object->fetch($id);
						}
						$contactsoftask = $object->getListContactId('internal');
						if (!in_array($task_time->fk_user, $contactsoftask)) {
							$contactsoftask[] = $task_time->fk_user;
						}
						if (count($contactsoftask) > 0) {
							print img_object('', 'user', 'class="hideonsmartphone"');
							print $form->select_dolusers($task_time->fk_user, 'userid_line_2', 0, '', 0, '', $contactsoftask);
						} else {
							print img_error($langs->trans('FirstAddRessourceToAllocateTime')).$langs->trans('FirstAddRessourceToAllocateTime');
						}
					} else {
						$userstatic->id = $task_time->fk_user;
						$userstatic->lastname = $task_time->lastname;
						$userstatic->firstname = $task_time->firstname;
						$userstatic->photo = $task_time->photo;
						$userstatic->statut = $task_time->user_status;
						print $userstatic->getNomUrl(-1);
					}
					print '</td>';
				}

				// Note
				if (!empty($arrayfields['t.note']['checked'])) {
					print '<td class="small tdoverflowmax300"">';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						print '<textarea name="timespent_note_line_2" width="95%" rows="'.ROWS_1.'">'.dol_escape_htmltag($task_time->note, 0, 1).'</textarea>';
					} else {
						print dol_nl2br($task_time->note);
					}
					print '</td>';
				} elseif ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
					print '<input type="hidden" name="timespent_note_line_2" value="'.dol_escape_htmltag($task_time->note, 0, 1).'">';
				}

				// Time spent
				if (!empty($arrayfields['t.task_duration']['checked'])) {
					print '<td class="right">';
					if ($action == 'splitline' && GETPOST('lineid', 'int') == $task_time->rowid) {
						print '<input type="hidden" name="old_duration_2" value="0">';
						print $form->select_duration('new_duration_2', 0, 0, 'text');
					} else {
						print convertSecondToTime($task_time->task_duration, 'allhourmin');
					}
					print '</td>';
				}

				// Value spent
				if (!empty($arrayfields['value']['checked'])) {
					print '<td class="right">';
					print '<span class="amount">';
					$value = 0;
					print price($value, 1, $langs, 1, -1, -1, $conf->currency);
					print '</span>';
					print '</td>';
				}

				// Value billed
				if (!empty($arrayfields['valuebilled']['checked'])) {
					print '<td class="right">';
					$valuebilled = price2num($task_time->total_ht, '', 1);
					if (isset($task_time->total_ht)) {
						print '<span class="amount">';
						print price($valuebilled, 1, $langs, 1, -1, -1, $conf->currency);
						print '</span>';
					}
					print '</td>';
				}

				/*
				 // Extra fields
				 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
				 */

				// Fields from hook
				$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$task_time, 'mode' => 'split2');
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Action column
				print '<td class="center nowraponall">';
				print '</td>';

				print "</tr>\n";
			}

			$i++;
		}

		// Show total line
		//include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
		if (isset($totalarray['totaldurationfield']) || isset($totalarray['totalvaluefield'])) {
			print '<tr class="liste_total">';
			$i = 0;
			while ($i < $totalarray['nbfield']) {
				$i++;
				if ($i == 1) {
					if ($num < $limit && empty($offset)) {
						print '<td class="left">'.$langs->trans("Total").'</td>';
					} else {
						print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
					}
				} elseif ($totalarray['totaldurationfield'] == $i) {
					print '<td class="right">'.convertSecondToTime($totalarray['totalduration'], 'allhourmin').'</td>';
				} elseif ($totalarray['totalvaluefield'] == $i) {
					print '<td class="right">'.price($totalarray['totalvalue']).'</td>';
					//} elseif ($totalarray['totalvaluebilledfield'] == $i) { print '<td class="center">'.price($totalarray['totalvaluebilled']).'</td>';
				} else {
					print '<td></td>';
				}
			}
			print '</tr>';
		}

		if (!count($tasks)) {
			$totalnboffields = 1;
			foreach ($arrayfields as $value) {
				if ($value['checked']) {
					$totalnboffields++;
				}
			}
			print '<tr class="oddeven"><td colspan="'.$totalnboffields.'">';
			print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
			print '</td></tr>';
		}

		$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
		$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print "</table>";
		print '</div>';
		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
