<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2019       Christophe Battarel		<christophe@altairis.fr>
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

// Load translation files required by the page
$langs->loadLangs(array('projects', 'bills', 'orders'));

$action		= GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm	= GETPOST('confirm', 'alpha');
$cancel		= GETPOST('cancel', 'alpha');
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'myobjectlist'; // To manage different context of search
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

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;	  // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) accessforbidden();

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }		// If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 't.task_date,t.task_datehour,t.rowid';
if (!$sortorder) $sortorder = 'DESC,DESC,DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
//$object = new TaskTime($db);
$hookmanager->initHooks(array('projecttasktime', 'globalcard'));

$object = new Task($db);
$projectstatic = new Project($db);
$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($projectstatic->table_element);
$extrafields->fetch_name_optionals_label($object->table_element);


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_generateinvoice') { $massaction = ''; }

$parameters = array('socid'=>$socid, 'projectid'=>$projectid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
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
	$toselect = '';
	$search_array_options = array();
	$action = '';
}

if ($action == 'addtimespent' && $user->rights->projet->lire)
{
	$error = 0;

	$timespent_durationhour = GETPOST('timespent_durationhour', 'int');
	$timespent_durationmin = GETPOST('timespent_durationmin', 'int');
	if (empty($timespent_durationhour) && empty($timespent_durationmin))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}
	if (empty($_POST["userid"]))
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorUserNotAssignedToTask'), null, 'errors');
		$error++;
	}

	if (!$error)
	{
		if ($id || $ref)
		{
			$object->fetch($id, $ref);
		}
		else
		{
			if (!GETPOST('taskid', 'int') || GETPOST('taskid', 'int') < 0)
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), null, 'errors');
				$action = 'createtime';
				$error++;
			}
			else
			{
				$object->fetch(GETPOST('taskid', 'int'));
			}
		}

		if (!$error)
		{
			$object->fetch_projet();

			if (empty($object->project->statut))
			{
				setEventMessages($langs->trans("ProjectMustBeValidatedFirst"), null, 'errors');
				$action = 'createtime';
				$error++;
			}
			else
			{
				$object->timespent_note = $_POST["timespent_note"];
				if (GETPOST('progress', 'int') > 0) $object->progress = GETPOST('progress', 'int'); // If progress is -1 (not defined), we do not change value
				$object->timespent_duration = $_POST["timespent_durationhour"] * 60 * 60; // We store duration in seconds
				$object->timespent_duration += ($_POST["timespent_durationmin"] ? $_POST["timespent_durationmin"] : 0) * 60; // We store duration in seconds
				if (GETPOST("timehour") != '' && GETPOST("timehour") >= 0)	// If hour was entered
				{
					$object->timespent_date = dol_mktime(GETPOST("timehour"), GETPOST("timemin"), 0, GETPOST("timemonth"), GETPOST("timeday"), GETPOST("timeyear"));
					$object->timespent_withhour = 1;
				}
				else
				{
					$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timemonth"), GETPOST("timeday"), GETPOST("timeyear"));
				}
				$object->timespent_fk_user = $_POST["userid"];
				$result = $object->addTimeSpent($user);
				if ($result >= 0)
				{
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				}
				else
				{
					setEventMessages($langs->trans($object->error), null, 'errors');
					$error++;
				}
			}
		}
	}
	else
	{
		if (empty($id)) $action = 'createtime';
		else $action = 'createtime';
	}
}

if (($action == 'updateline' || $action == 'updatesplitline') && !$cancel && $user->rights->projet->lire)
{
	$error = 0;

	if (!GETPOST("new_durationhour") && !GETPOST("new_durationmin"))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}

	if (!$error)
	{
		if (GETPOST('taskid', 'int') != $id)		// GETPOST('taskid') is id of new task
		{
			$id = GETPOST('taskid', 'int');

			$object->fetchTimeSpent(GETPOST('lineid', 'int'));
			// TODO Check that ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids))
			$result = $object->delTimeSpent($user);

			$object->fetch($id, $ref);
			$object->timespent_note = $_POST["timespent_note_line"];
			$object->timespent_old_duration = $_POST["old_duration"];
			$object->timespent_duration = $_POST["new_durationhour"] * 60 * 60; // We store duration in seconds
			$object->timespent_duration += ($_POST["new_durationmin"] ? $_POST["new_durationmin"] : 0) * 60; // We store duration in seconds
			if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0)	// If hour was entered
			{
				$object->timespent_date = dol_mktime(GETPOST("timelinehour"), GETPOST("timelinemin"), 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
				$object->timespent_withhour = 1;
			}
			else
			{
				$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
			}
			$object->timespent_fk_user = $_POST["userid_line"];
			$result = $object->addTimeSpent($user);
			if ($result >= 0)
			{
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			}
			else
			{
				setEventMessages($langs->trans($object->error), null, 'errors');
				$error++;
			}
		}
		else
		{
			$object->fetch($id, $ref);
			// TODO Check that ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids))

			$object->timespent_id = $_POST["lineid"];
			$object->timespent_note = $_POST["timespent_note_line"];
			$object->timespent_old_duration = $_POST["old_duration"];
			$object->timespent_duration = $_POST["new_durationhour"] * 60 * 60; // We store duration in seconds
			$object->timespent_duration += ($_POST["new_durationmin"] ? $_POST["new_durationmin"] : 0) * 60; // We store duration in seconds
			if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0)	// If hour was entered
			{
				$object->timespent_date = dol_mktime(GETPOST("timelinehour"), GETPOST("timelinemin"), 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
				$object->timespent_withhour = 1;
			}
			else
			{
				$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
			}
			$object->timespent_fk_user = $_POST["userid_line"];

			$result = $object->updateTimeSpent($user);
			if ($result >= 0)
			{
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			}
			else
			{
				setEventMessages($langs->trans($object->error), null, 'errors');
				$error++;
			}
		}
	}
	else
	{
		$action = '';
	}
}

if ($action == 'confirm_delete' && $confirm == "yes" && $user->rights->projet->lire)
{
	$object->fetchTimeSpent(GETPOST('lineid', 'int'));
	// TODO Check that ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids))
	$result = $object->delTimeSpent($user);

	if ($result < 0)
	{
		$langs->load("errors");
		setEventMessages($langs->trans($object->error), null, 'errors');
		$error++;
		$action = '';
	}
	else
	{
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
	}
}

// Retreive First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject))
{
	if ($projectstatic->fetch(0, $project_ref) > 0)
	{
		$tasksarray = $object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0)
		{
			$id = $tasksarray[0]->id;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject ? '&withproject=1' : '').(empty($mode) ? '' : '&mode='.$mode));
			exit;
		}
	}
}

// To show all time lines for project
$projectidforalltimes = 0;
if (GETPOST('projectid', 'int') > 0)
{
	$projectidforalltimes = GETPOST('projectid', 'int');

	$result = $projectstatic->fetch($projectidforalltimes);
	if (!empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();
	$res = $projectstatic->fetch_optionals();
}
elseif (GETPOST('project_ref', 'alpha'))
{
	$projectstatic->fetch(0, GETPOST('project_ref', 'alpha'));
	$projectidforalltimes = $projectstatic->id;
	$withproject = 1;
}
elseif ($id > 0)
{
	$object->fetch($id);
	$result = $projectstatic->fetch($object->fk_project);
}

if ($action == 'confirm_generateinvoice')
{
	if (!empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();

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
		if ($idprod > 0)
		{
			$tmpproduct->fetch($idprod);
            if ($tmpproduct->duration_unit == 'i')
                $prodDurationHours = 1. / 60;
            if ($tmpproduct->duration_unit == 'h')
                $prodDurationHours = 1.;
            if ($tmpproduct->duration_unit == 'd')
                $prodDurationHours = 24.;
            if ($tmpproduct->duration_unit == 'w')
                $prodDurationHours = 24. * 7;
            if ($tmpproduct->duration_unit == 'm')
                $prodDurationHours = 24. * 30;
            if ($tmpproduct->duration_unit == 'y')
                $prodDurationHours = 24. * 365;
            $prodDurationHours *= $tmpproduct->duration_value;

			$dataforprice = $tmpproduct->getSellPrice($mysoc, $projectstatic->thirdparty, 0);

			$pu_ht = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
			$txtva = $dataforprice['tva_tx'];
			$localtax1 = $dataforprice['localtax1'];
			$localtax2 = $dataforprice['localtax2'];
		}
		else
		{
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
        }
        else {
		    $result = $tmpinvoice->create($user);
		    if ($result <= 0)
		    {
		    	$error++;
		    	setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
		    }
        }

		if (!$error)
		{
			if ($generateinvoicemode == 'onelineperuser') {
					$arrayoftasks = array();
				foreach ($toselect as $key => $value)
					{
					// Get userid, timepent
					$object->fetchTimeSpent($value);
					$arrayoftasks[$object->timespent_fk_user]['timespent'] += $object->timespent_duration;
					$arrayoftasks[$object->timespent_fk_user]['totalvaluetodivideby3600'] += ($object->timespent_duration * $object->timespent_thm);
				}

				foreach ($arrayoftasks as $userid => $value)
				{
					$fuser->fetch($userid);
					//$pu_ht = $value['timespent'] * $fuser->thm;
					$username = $fuser->getFullName($langs);

					// Define qty per hour
					$qtyhour = $value['timespent'] / 3600;
					$qtyhourtext = convertSecondToTime($value['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

					// If no unit price known
					if (empty($pu_ht))
					{
						$pu_ht = price2num($value['totalvaluetodivideby3600'] / 3600, 'MU');
					}

					// Add lines
					$lineid = $tmpinvoice->addline($langs->trans("TimeSpentForInvoice", $username).' : '.$qtyhourtext, $pu_ht, round($qtyhour / $prodDurationHours, 2), $txtva, $localtax1, $localtax2, ($idprod > 0 ? $idprod : 0));

					// Update lineid into line of timespent
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET invoice_line_id = '.$lineid.', invoice_id = '.$tmpinvoice->id;
					$sql .= ' WHERE rowid in ('.join(',', $toselect).') AND fk_user = '.$userid;
					$result = $db->query($sql);
					if (!$result)
					{
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
				}
			}
			elseif ($generateinvoicemode == 'onelineperperiod') {
					$arrayoftasks = array();
				foreach ($toselect as $key => $value)
					{
					// Get userid, timepent
					$object->fetchTimeSpent($value);
					$arrayoftasks[$object->timespent_id]['timespent'] = $object->timespent_duration;
					$arrayoftasks[$object->timespent_id]['totalvaluetodivideby3600'] = $object->timespent_duration * $object->timespent_thm;
					$arrayoftasks[$object->timespent_id]['note'] = $object->timespent_note;
					$arrayoftasks[$object->timespent_id]['user'] = $object->timespent_fk_user;
				}

				foreach ($arrayoftasks as $timespent_id => $value)
				{
					$userid = $value['user'];
					$fuser->fetch($userid);
					//$pu_ht = $value['timespent'] * $fuser->thm;
					$username = $fuser->getFullName($langs);

					// Define qty per hour
					$qtyhour = $value['timespent'] / 3600;
					$qtyhourtext = convertSecondToTime($value['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

					// If no unit price known
					if (empty($pu_ht))
					{
						$pu_ht = price2num($value['totalvaluetodivideby3600'] / 3600, 'MU');
					}

					// Add lines
					$lineid = $tmpinvoice->addline($value['note'], $pu_ht, round($qtyhour / $prodDurationHours, 2), $txtva, $localtax1, $localtax2, ($idprod > 0 ? $idprod : 0));

					// Update lineid into line of timespent
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET invoice_line_id = '.$lineid.', invoice_id = '.$tmpinvoice->id;
					$sql .= ' WHERE rowid in ('.join(',', $toselect).') AND fk_user = '.$userid;
					$result = $db->query($sql);
					if (!$result)
					{
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
				}
			}
			elseif ($generateinvoicemode == 'onelinepertask') {
					$arrayoftasks = array();
				foreach ($toselect as $key => $value)
					{
					// Get userid, timepent
					$object->fetchTimeSpent($value);
					// $object->id is the task id
					$arrayoftasks[$object->id]['timespent'] += $object->timespent_duration;
					$arrayoftasks[$object->id]['totalvaluetodivideby3600'] += $object->timespent_duration * $object->timespent_thm;
				}

				foreach ($arrayoftasks as $task_id => $value)
				{
					$ftask = new Task($db);
					$ftask->fetch($task_id);
					// Define qty per hour
					$qtyhour = $value['timespent'] / 3600;
					$qtyhourtext = convertSecondToTime($value['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

					// If no unit price known
					if (empty($pu_ht))
					{
						$pu_ht = price2num($value['totalvaluetodivideby3600'] / 3600, 'MU');
					}

					// Add lines
					$lineName = $ftask->ref.' - '.$ftask->label;
					$lineid = $tmpinvoice->addline($lineName, $pu_ht, round($qtyhour / $prodDurationHours, 2), $txtva, $localtax1, $localtax2, ($idprod > 0 ? $idprod : 0));

					// Update lineid into line of timespent
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task_time SET invoice_line_id = '.$lineid.', invoice_id = '.$tmpinvoice->id;
					$sql .= ' WHERE rowid in ('.join(',', $toselect).')';
					$result = $db->query($sql);
					if (!$result)
					{
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
				}
			}
		}

		if (!$error)
		{
			$urltoinvoice = $tmpinvoice->getNomUrl(0);
			setEventMessages($langs->trans("InvoiceGeneratedFromTimeSpent", $urltoinvoice), null, 'mesgs');
			//var_dump($tmpinvoice);

			$db->commit();
		}
		else
		{
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

if (($id > 0 || !empty($ref)) || $projectidforalltimes > 0)
{
	/*
	 * Fiche projet en mode visu
	 */
	if ($projectidforalltimes > 0)
	{
		$result = $projectstatic->fetch($projectidforalltimes);
		if (!empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();
		$res = $projectstatic->fetch_optionals();
	}
	elseif ($object->fetch($id, $ref) >= 0)
	{
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();
		$result = $projectstatic->fetch($object->fk_project);
		if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) $projectstatic->fetchComments();
		if (!empty($projectstatic->socid)) $projectstatic->fetch_thirdparty();
		$res = $projectstatic->fetch_optionals();

		$object->project = clone $projectstatic;
	}

	$userRead = $projectstatic->restrictedProjectArea($user, 'read');
	$linktocreatetime = '';

	if ($projectstatic->id > 0)
	{
		if ($withproject)
		{
			// Tabs for project
			if (empty($id) || $tab == 'timespent') $tab = 'timespent';
			else $tab = 'tasks';

			$head = project_prepare_head($projectstatic);
			dol_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

			$param = ($mode == 'mine' ? '&mode=mine' : '');

			// Project card

			$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Title
			$morehtmlref .= $projectstatic->title;
			// Thirdparty
			if ($projectstatic->thirdparty->id > 0)
			{
				$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$projectstatic->thirdparty->getNomUrl(1, 'project');
			}
			$morehtmlref .= '</div>';

			// Define a complementary filter for search of next/prev ref.
			if (!$user->rights->projet->all->lire)
			{
				$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
				$projectstatic->next_prev_filter = " rowid in (".(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
			}

			dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Usage
			print '<tr><td class="tdtop">';
			print $langs->trans("Usage");
			print '</td>';
			print '<td>';
			if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES))
			{
				print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
				print '<br>';
			}
			if (empty($conf->global->PROJECT_HIDE_TASKS))
			{
				print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
				print '<br>';
			}
			if (!empty($conf->global->PROJECT_BILL_TIME_SPENT))
			{
				print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
				print '<br>';
			}
			print '</td></tr>';

			// Visibility
			print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
			if ($projectstatic->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td></tr>';

			// Date start - end
			print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
			$start = dol_print_date($projectstatic->date_start, 'day');
			print ($start ? $start : '?');
			$end = dol_print_date($projectstatic->date_end, 'day');
			print ' - ';
			print ($end ? $end : '?');
			if ($projectstatic->hasDelay()) print img_warning("Late");
			print '</td></tr>';

			// Budget
			print '<tr><td>'.$langs->trans("Budget").'</td><td>';
			if (strcmp($projectstatic->budget_amount, '')) print price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
			print '</td></tr>';

			// Other attributes
			$cols = 2;
			//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="ficheaddleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield" width="100%">';

			// Description
			print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
			print nl2br($projectstatic->description);
			print '</td></tr>';

			// Categories
			if ($conf->categorie->enabled) {
				print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
				print $form->showCategories($projectstatic->id, 'project', 1);
				print "</td></tr>";
			}

			print '</table>';

			print '</div>';
			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			dol_fiche_end();

			print '<br>';
		}

		// Link to create time
		$linktocreatetimeBtnStatus = 0;
		$linktocreatetimeUrl = '';
		$linktocreatetimeHelpText = '';
		if ($user->rights->projet->all->lire || $user->rights->projet->lire)	// To enter time, read permission is enough
		{
			if ($projectstatic->public || $userRead > 0)
			{
				$linktocreatetimeBtnStatus = 1;

				if (!empty($projectidforalltimes))		// We are on tab 'Time Spent' of project
				{
					$backtourl = $_SERVER['PHP_SELF'].'?projectid='.$projectstatic->id.($withproject ? '&withproject=1' : '');
					$linktocreatetimeUrl = $_SERVER['PHP_SELF'].'?'.($withproject ? 'withproject=1' : '').'&projectid='.$projectstatic->id.'&action=createtime'.$param.'&backtopage='.urlencode($backtourl);
				}
				else									// We are on tab 'Time Spent' of task
				{
					$backtourl = $_SERVER['PHP_SELF'].'?id='.$object->id.($withproject ? '&withproject=1' : '');
					$linktocreatetimeUrl = $_SERVER['PHP_SELF'].'?'.($withproject ? 'withproject=1' : '').($object->id > 0 ? '&id='.$object->id : '&projectid='.$projectstatic->id).'&action=createtime'.$param.'&backtopage='.urlencode($backtourl);
				}
			}
			else
			{
				$linktocreatetimeBtnStatus = -2;
				$linktocreatetimeHelpText = $langs->trans("NotOwnerOfProject");
			}
		}

		$linktocreatetime = dolGetButtonTitle($langs->trans('AddTimeSpent'), $linktocreatetimeHelpText, 'fa fa-plus-circle', $linktocreatetimeUrl, '', $linktocreatetimeBtnStatus);
	}

	$massactionbutton = '';
	if ($projectstatic->usage_bill_time)
	{
		$arrayofmassactions = array(
			'generateinvoice'=>$langs->trans("GenerateBill"),
			//'builddoc'=>$langs->trans("PDFMerge"),
		);
		//if ($user->rights->projet->creer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
		if (in_array($massaction, array('presend', 'predelete', 'generateinvoice'))) $arrayofmassactions = array();
		$massactionbutton = $form->selectMassAction('', $arrayofmassactions);
	}

	// Show section with information of task. If id of task is not defined and project id defined, then $projectidforalltimes is not empty.
	if (empty($projectidforalltimes))
	{
		$head = task_prepare_head($object);
		dol_fiche_head($head, 'task_time', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

		if ($action == 'deleteline')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id > 0 ? "id=".$object->id : 'projectid='.$projectstatic->id).'&lineid='.GETPOST("lineid", 'int').($withproject ? '&withproject=1' : ''), $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_delete", '', '', 1);
		}

		$param = ($withproject ? '&withproject=1' : '');
		$linkback = $withproject ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

		if (!GETPOST('withproject') || empty($projectstatic->id))
		{
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			$object->next_prev_filter = " fk_projet in (".$projectsListId.")";
		}
		else $object->next_prev_filter = " fk_projet = ".$projectstatic->id;

		$morehtmlref = '';

		// Project
		if (empty($withproject))
		{
			$morehtmlref .= '<div class="refidno">';
			$morehtmlref .= $langs->trans("Project").': ';
			$morehtmlref .= $projectstatic->getNomUrl(1);
			$morehtmlref .= '<br>';

			// Third party
			$morehtmlref .= $langs->trans("ThirdParty").': ';
			if (is_object($projectstatic->thirdparty)) {
				$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
			}
			$morehtmlref .= '</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Date start - Date end
		print '<tr><td class="titlefield">'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
		$start = dol_print_date($object->date_start, 'dayhour');
		print ($start ? $start : '?');
		$end = dol_print_date($object->date_end, 'dayhour');
		print ' - ';
		print ($end ? $end : '?');
		if ($object->hasDelay()) print img_warning("Late");
		print '</td></tr>';

		// Planned workload
		print '<tr><td>'.$langs->trans("PlannedWorkload").'</td><td>';
		if ($object->planned_workload)
		{
			print convertSecondToTime($object->planned_workload, 'allhourmin');
		}
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<div class="fichehalfright"><div class="ficheaddleft">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Progress declared
		print '<tr><td class="titlefield">'.$langs->trans("ProgressDeclared").'</td><td>';
		print $object->progress != '' ? $object->progress.' %' : '';
		print '</td></tr>';

		// Progress calculated
		print '<tr><td>'.$langs->trans("ProgressCalculated").'</td><td>';
		if ($object->planned_workload)
		{
			$tmparray = $object->getSummaryOfTimeSpent();
			if ($tmparray['total_duration'] > 0) print round($tmparray['total_duration'] / $object->planned_workload * 100, 2).' %';
			else print '0 %';
		}
		else print '<span class="opacitymedium">'.$langs->trans("WorkloadNotDefined").'</span>';
		print '</td>';

		print '</tr>';

		print '</table>';

		print '</div>';
		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';

		dol_fiche_end();
	}


	if ($projectstatic->id > 0)
	{
		if ($action == 'deleteline' && !empty($projectidforalltimes))
		{
			print $form->formconfirm($_SERVER["PHP_SELF"]."?".($object->id > 0 ? "id=".$object->id : 'projectid='.$projectstatic->id).'&lineid='.GETPOST('lineid', 'int').($withproject ? '&withproject=1' : ''), $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_delete", '', '', 1);
		}

		// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
		$hookmanager->initHooks(array('tasktimelist'));

		// Definition of fields for list
		$arrayfields = array();
		$arrayfields['t.task_date'] = array('label'=>$langs->trans("Date"), 'checked'=>1);
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
		{
			$arrayfields['t.task_ref'] = array('label'=>$langs->trans("RefTask"), 'checked'=>1);
			$arrayfields['t.task_label'] = array('label'=>$langs->trans("LabelTask"), 'checked'=>1);
		}
		$arrayfields['author'] = array('label'=>$langs->trans("By"), 'checked'=>1);
		$arrayfields['t.note'] = array('label'=>$langs->trans("Note"), 'checked'=>1);
		$arrayfields['t.task_duration'] = array('label'=>$langs->trans("Duration"), 'checked'=>1);
		$arrayfields['value'] = array('label'=>$langs->trans("Value"), 'checked'=>1, 'enabled'=>(empty($conf->salaries->enabled) ? 0 : 1));
		$arrayfields['valuebilled'] = array('label'=>$langs->trans("Billed"), 'checked'=>1, 'enabled'=>(((!empty($conf->global->PROJECT_HIDE_TASKS) || empty($conf->global->PROJECT_BILL_TIME_SPENT)) ? 0 : 1) && $projectstatic->usage_bill_time));
		// Extra fields
		if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
		{
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
			{
				if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
					$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
			}
		}
		$arrayfields = dol_sort_array($arrayfields, 'position');

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
		if ($search_month > 0) $param .= '&search_month='.urlencode($search_month);
		if ($search_year > 0) $param .= '&search_year='.urlencode($search_year);
		if ($search_user > 0) $param .= '&search_user='.urlencode($search_user);
		if ($search_task_ref != '') $param .= '&search_task_ref='.urlencode($search_task_ref);
		if ($search_task_label != '') $param .= '&search_task_label='.urlencode($search_task_label);
		if ($search_note != '') $param .= '&search_note='.urlencode($search_note);
		if ($search_duration != '') $param .= '&amp;search_field2='.urlencode($search_duration);
		if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);
		/*
		 // Add $param from extra fields
		 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
		 */
		if ($id) $param .= '&id='.urlencode($id);
		if ($projectid) $param .= '&projectid='.urlencode($projectid);
		if ($withproject) $param .= '&withproject='.urlencode($withproject);

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		if ($action == 'editline') print '<input type="hidden" name="action" value="updateline">';
		elseif ($action == 'splitline') print '<input type="hidden" name="action" value="updatesplitline">';
		elseif ($action == 'createtime' && $user->rights->projet->lire) print '<input type="hidden" name="action" value="addtimespent">';
		elseif ($massaction == 'generateinvoice' && $user->rights->facture->lire) print '<input type="hidden" name="action" value="confirm_generateinvoice">';
		else print '<input type="hidden" name="action" value="list">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="page" value="'.$page.'">';

		print '<input type="hidden" name="id" value="'.$id.'">';
		print '<input type="hidden" name="projectid" value="'.$projectidforalltimes.'">';
		print '<input type="hidden" name="withproject" value="'.$withproject.'">';
		print '<input type="hidden" name="tab" value="'.$tab.'">';

		// Form to convert time spent into invoice
		if ($massaction == 'generateinvoice')
		{
			print '<input type="hidden" name="massaction" value="confirm_createbills">';

			if ($projectstatic->thirdparty->id > 0) {
				print '<table class="noborder" width="100%" >';
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
					'onelineperperiod'=>'OneLinePerPeriod',
				);
				print $form->selectarray('generateinvoicemode', $tmparray, 'onelineperuser', 0, 0, 0, '', 1);
				print '</td>';
				print '</tr>';

				if ($conf->service->enabled)
				{
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
	            $form->selectInvoice('invoice', '', 'invoiceid', 24, 0, $langs->trans('NewInvoice'),
				1, 0, 0, 'maxwidth500', '', 'all');
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
				print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
				print '</div>';
				print '<br>';
			} else {
				print '<div class="warning">'.$langs->trans("ThirdPartyRequiredToGenerateInvoice").'</div>';
				print '<div class="center">';
				print '<input type="submit" class="button" id="cancel" name="cancel" value="'.$langs->trans('Cancel').'">';
				print '</div>';
				$massaction = '';
			}
		}

		/*
		 *	List of time spent
		 */
		$tasks = array();

		$sql = "SELECT t.rowid, t.fk_task, t.task_date, t.task_datehour, t.task_date_withhour, t.task_duration, t.fk_user, t.note, t.thm,";
		$sql .= " pt.ref, pt.label,";
		$sql .= " u.lastname, u.firstname, u.login, u.photo, u.statut as user_status,";
		$sql .= " il.fk_facture as invoice_id, inv.fk_statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as il ON il.rowid = t.invoice_line_id";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as inv ON inv.rowid = il.fk_facture,";
		$sql .= " ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
		if (empty($projectidforalltimes)) $sql .= " AND t.fk_task =".$object->id;
		else $sql .= " AND pt.fk_projet IN (".$projectidforalltimes.")";
		if ($search_ref) $sql .= natural_search('c.ref', $search_ref);
		if ($search_note) $sql .= natural_search('t.note', $search_note);
		if ($search_task_ref) $sql .= natural_search('pt.ref', $search_task_ref);
		if ($search_task_label) $sql .= natural_search('pt.label', $search_task_label);
		if ($search_user > 0) $sql .= natural_search('t.fk_user', $search_user);
		if ($search_valuebilled == '1') $sql .= ' AND t.invoice_id > 0';
		if ($search_valuebilled == '0') $sql .= ' AND (t.invoice_id = 0 OR t.invoice_id IS NULL)';
		$sql .= dolSqlDateFilter('t.task_datehour', $search_day, $search_month, $search_year);
		$sql .= $db->order($sortfield, $sortorder);

		// Count total nb of records
		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$resql = $db->query($sql);
			$nbtotalofrecords = $db->num_rows($resql);
			if (($page * $limit) > $nbtotalofrecords)	// if total of record found is smaller than page * limit, goto and load page 0
			{
				$page = 0;
				$offset = 0;
			}
		}
		// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
		if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords)
		{
			$num = $nbtotalofrecords;
		}
		else
		{
			$sql .= $db->plimit($limit + 1, $offset);

			$resql = $db->query($sql);
			if (!$resql)
			{
				dol_print_error($db);
				exit;
			}

			$num = $db->num_rows($resql);
		}

		if ($num >= 0)
		{
			if (!empty($projectidforalltimes))
			{
				print '<!-- List of time spent for project -->'."\n";

				$title = $langs->trans("ListTaskTimeUserProject");

				print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, $linktocreatetime, '', $limit);
			}
			else
			{
				print '<!-- List of time spent for project -->'."\n";

				$title = $langs->trans("ListTaskTimeForTask");

				print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, $linktocreatetime, '', $limit);
			}

			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_object($resql);
				$tasks[$i] = $row;
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		/*
		 * Form to add a new line of time spent
		 */
		if ($action == 'createtime' && $user->rights->projet->lire)
		{
			print '<!-- table to add time spent -->'."\n";
			if (!empty($id)) print '<input type="hidden" name="taskid" value="'.$id.'">';

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
			print '<table class="noborder nohover centpercent">';

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Date").'</td>';
			if (empty($id)) print '<td>'.$langs->trans("Task").'</td>';
			print '<td>'.$langs->trans("By").'</td>';
			print '<td>'.$langs->trans("Note").'</td>';
			print '<td>'.$langs->trans("NewTimeSpent").'</td>';
			print '<td>'.$langs->trans("ProgressDeclared").'</td>';
			if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT))
			{
				print '<td></td>';
			}
			print '<td></td>';
			print "</tr>\n";

			print '<tr class="oddeven nohover">';

			// Date
			print '<td class="maxwidthonsmartphone">';
			//$newdate=dol_mktime(12,0,0,$_POST["timemonth"],$_POST["timeday"],$_POST["timeyear"]);
			$newdate = '';
			print $form->selectDate($newdate, 'time', ($conf->browser->layout == 'phone' ? 2 : 1), 1, 2, "timespent_date", 1, 0);
			print '</td>';

			// Task
			$nboftasks = 0;
			if (empty($id))
			{
				print '<td class="maxwidthonsmartphone">';
				$nboftasks = $formproject->selectTasks(-1, GETPOST('taskid', 'int'), 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth300', $projectstatic->id, '');
				print '</td>';
			}

			// Contributor
			print '<td class="maxwidthonsmartphone nowraponall">';
			$contactsofproject = $projectstatic->getListContactId('internal');
			if (count($contactsofproject) > 0)
			{
				print img_object('', 'user', 'class="hideonsmartphone"');
				if (in_array($user->id, $contactsofproject)) $userid = $user->id;
				else $userid = $contactsofproject[0];

				if ($projectstatic->public) $contactsofproject = array();
				print $form->select_dolusers((GETPOST('userid', 'int') ? GETPOST('userid', 'int') : $userid), 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToProject"), 'maxwidth200');
			}
			else
			{
				if ($nboftasks) {
					print img_error($langs->trans('FirstAddRessourceToAllocateTime')).' '.$langs->trans('FirstAddRessourceToAllocateTime');
				}
			}
			print '</td>';

			// Note
			print '<td>';
			print '<textarea name="timespent_note" class="maxwidth100onsmartphone" rows="'.ROWS_2.'">'.($_POST['timespent_note'] ? $_POST['timespent_note'] : '').'</textarea>';
			print '</td>';

			// Duration - Time spent
			print '<td>';
			$durationtouse = ($_POST['timespent_duration'] ? $_POST['timespent_duration'] : '');
			if (GETPOSTISSET('timespent_durationhour') || GETPOSTISSET('timespent_durationmin'))
			{
				$durationtouse = (GETPOST('timespent_durationhour') * 3600 + GETPOST('timespent_durationmin') * 60);
			}
			print $form->select_duration('timespent_duration', $durationtouse, 0, 'text');
			print '</td>';

			// Progress declared
			print '<td class="nowrap">';
			print $formother->select_percent(GETPOST('progress') ?GETPOST('progress') : $object->progress, 'progress', 0, 5, 0, 100, 1);
			print '</td>';

			// Invoiced
			if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT))
			{
				print '<td>';
				print '</td>';
			}

			print '<td class="center">';
			print '<input type="submit" name="save" class="button buttongen marginleftonly margintoponlyshort marginbottomonlyshort" value="'.$langs->trans("Add").'">';
			print '<input type="submit" name="cancel" class="button buttongen marginleftonly margintoponlyshort marginbottomonlyshort" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';

			print '</table>';
			print '</div>';

			print '<br>';
		}

		$moreforfilter = '';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
		else $moreforfilter = $hookmanager->resPrint;

		if (!empty($moreforfilter))
		{
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
		if (!empty($arrayfields['t.task_date']['checked']))
		{
			print '<td class="liste_titre">';
			if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_day" value="'.$search_day.'">';
			print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_month" value="'.$search_month.'">';
			$formother->select_year($search_year, 'search_year', 1, 20, 5);
			print '</td>';
		}
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
		{
			if (!empty($arrayfields['t.task_ref']['checked'])) print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'"></td>';
			if (!empty($arrayfields['t.task_label']['checked'])) print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
		}
		// Author
		if (!empty($arrayfields['author']['checked'])) print '<td class="liste_titre">'.$form->select_dolusers(($search_user > 0 ? $search_user : -1), 'search_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth200').'</td>';
		// Note
		if (!empty($arrayfields['t.note']['checked'])) print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_note" value="'.dol_escape_htmltag($search_note).'"></td>';
		// Duration
		if (!empty($arrayfields['t.task_duration']['checked'])) print '<td class="liste_titre right"></td>';
		// Value in main currency
		if (!empty($arrayfields['value']['checked'])) print '<td class="liste_titre"></td>';
		// Value billed
		if (!empty($arrayfields['valuebilled']['checked'])) print '<td class="liste_titre center">'.$form->selectyesno('search_valuebilled', $search_valuebilled, 1, false, 1).'</td>';

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
		if (!empty($arrayfields['t.task_date']['checked']))		 print_liste_field_titre($arrayfields['t.task_date']['label'], $_SERVER['PHP_SELF'], 't.task_date,t.task_datehour,t.rowid', '', $param, '', $sortfield, $sortorder);
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
		{
			if (!empty($arrayfields['t.task_ref']['checked']))	 print_liste_field_titre($arrayfields['t.task_ref']['label'], $_SERVER['PHP_SELF'], 'pt.ref', '', $param, '', $sortfield, $sortorder);
			if (!empty($arrayfields['t.task_label']['checked'])) print_liste_field_titre($arrayfields['t.task_label']['label'], $_SERVER['PHP_SELF'], 'pt.label', '', $param, '', $sortfield, $sortorder);
		}
		if (!empty($arrayfields['author']['checked']))			 print_liste_field_titre($arrayfields['author']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		if (!empty($arrayfields['t.note']['checked']))			 print_liste_field_titre($arrayfields['t.note']['label'], $_SERVER['PHP_SELF'], 't.note', '', $param, '', $sortfield, $sortorder);
		if (!empty($arrayfields['t.task_duration']['checked']))  print_liste_field_titre($arrayfields['t.task_duration']['label'], $_SERVER['PHP_SELF'], 't.task_duration', '', $param, '', $sortfield, $sortorder, 'right ');
		if (!empty($arrayfields['value']['checked']))			 print_liste_field_titre($arrayfields['value']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
		if (!empty($arrayfields['valuebilled']['checked']))		 print_liste_field_titre($arrayfields['valuebilled']['label'], $_SERVER['PHP_SELF'], 'il.total_ht', '', $param, '', $sortfield, $sortorder, 'center ');
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

		$childids = $user->getAllChildIds();

		$total = 0;
		$totalvalue = 0;
		$totalarray = array();
		foreach ($tasks as $task_time)
		{
			if ($i >= $limit) break;

			print '<tr class="oddeven">';

			$date1 = $db->jdate($task_time->task_date);
			$date2 = $db->jdate($task_time->task_datehour);

			// Date
			if (!empty($arrayfields['t.task_date']['checked']))
			{
				print '<td class="nowrap">';
				if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
				{
					if (empty($task_time->task_date_withhour))
					{
						print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 3, 3, 2, "timespent_date", 1, 0);
					}
					else print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 1, 1, 2, "timespent_date", 1, 0);
				}
				else
				{
					print dol_print_date(($date2 ? $date2 : $date1), ($task_time->task_date_withhour ? 'dayhour' : 'day'));
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Task ref
            if (!empty($arrayfields['t.task_ref']['checked']))
            {
        		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))   // Not a dedicated task
    			{
        			print '<td class="nowrap">';
					if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
					{
						$formproject->selectTasks(-1, GETPOST('taskid', 'int') ?GETPOST('taskid', 'int') : $task_time->fk_task, 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth300', $projectstatic->id, '');
					}
					else
					{
						$tasktmp->id = $task_time->fk_task;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
					}
        			print '</td>';
        			if (!$i) $totalarray['nbfield']++;
    			}
            } else {
            	print '<input type="hidden" name="taskid" value="'.$id.'">';
            }

			// Task label
			if (!empty($arrayfields['t.task_label']['checked']))
			{
				if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
				{
					print '<td class="nowrap">';
					print $task_time->label;
					print '</td>';
					if (!$i) $totalarray['nbfield']++;
				}
			}

			// By User
			if (!empty($arrayfields['author']['checked']))
			{
				print '<td class="nowrap">';
				if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
				{
					if (empty($object->id)) $object->fetch($id);
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
				}
				else
				{
					$userstatic->id = $task_time->fk_user;
					$userstatic->lastname = $task_time->lastname;
					$userstatic->firstname = $task_time->firstname;
					$userstatic->photo = $task_time->photo;
					$userstatic->statut = $task_time->user_status;
					print $userstatic->getNomUrl(-1);
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}

			// Note
			if (!empty($arrayfields['t.note']['checked']))
			{
				print '<td class="left">';
				if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
				{
					print '<textarea name="timespent_note_line" width="95%" rows="'.ROWS_2.'">'.$task_time->note.'</textarea>';
				}
				else
				{
					print dol_nl2br($task_time->note);
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
			}
			elseif ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
			{
				print '<input type="hidden" name="timespent_note_line" value="'.$task_time->note.'">';
			}

			// Time spent
			if (!empty($arrayfields['t.task_duration']['checked']))
			{
				print '<td class="right">';
				if ($action == 'editline' && $_GET['lineid'] == $task_time->rowid)
				{
					print '<input type="hidden" name="old_duration" value="'.$task_time->task_duration.'">';
					print $form->select_duration('new_duration', $task_time->task_duration, 0, 'text');
				}
				else
				{
					print convertSecondToTime($task_time->task_duration, 'allhourmin');
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 't.task_duration';
				$totalarray['val']['t.task_duration'] += $task_time->task_duration;
				if (!$i) $totalarray['totaldurationfield'] = $totalarray['nbfield'];
				$totalarray['totalduration'] += $task_time->task_duration;
			}

			// Value spent
			if (!empty($arrayfields['value']['checked']))
			{
				print '<td class="nowraponall right">';
				$value = price2num($task_time->thm * $task_time->task_duration / 3600, 'MT', 1);
				print price($value, 1, $langs, 1, -1, -1, $conf->currency);
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'value';
				$totalarray['val']['value'] += $value;
				if (!$i) $totalarray['totalvaluefield'] = $totalarray['nbfield'];
				$totalarray['totalvalue'] += $value;
			}

			// Invoiced
			if (!empty($arrayfields['valuebilled']['checked']))
			{
				print '<td class="center">'; // invoice_id and invoice_line_id
				if (empty($conf->global->PROJECT_HIDE_TASKS) && !empty($conf->global->PROJECT_BILL_TIME_SPENT))
				{
					if ($projectstatic->usage_bill_time)
					{
						if ($task_time->invoice_id)
						{
							$result = $tmpinvoice->fetch($task_time->invoice_id);
							if ($result > 0)
							{
								print $tmpinvoice->getNomUrl(1);
							}
						}
						else
						{
							print $langs->trans("No");
						}
					}
					else
					{
						print '<span class="opacitymedium">'.$langs->trans("NA").'</span>';
					}
				}
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
				if (!$i) $totalarray['totalvaluebilledfield'] = $totalarray['nbfield'];
				$totalarray['totalvaluebilled'] += $valuebilled;
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
			if (($action == 'editline' || $action == 'splitline') && $_GET['lineid'] == $task_time->rowid)
			{
				print '<input type="hidden" name="lineid" value="'.$_GET['lineid'].'">';
				print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort" name="save" value="'.$langs->trans("Save").'">';
				print '<br>';
				print '<input type="submit" class="button buttongen margintoponlyshort marginbottomonlyshort" name="cancel" value="'.$langs->trans('Cancel').'">';
			}
			elseif ($user->rights->projet->lire || $user->rights->projet->all->creer)	 // Read project and enter time consumed on assigned tasks
			{
				if ($task_time->fk_user == $user->id || in_array($task_time->fk_user, $childids) || $user->rights->projet->all->creer)
				{
					if ($conf->MAIN_FEATURES_LEVEL >= 2)
					{
						print '&nbsp;';
						print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&amp;action=splitline&amp;lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
						print img_split();
						print '</a>';
					}

					print '&nbsp;';
					print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&amp;action=editline&amp;lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
					print img_edit();
					print '</a>';

					print '&nbsp;';
					print '<a class="reposition paddingleft" href="'.$_SERVER["PHP_SELF"].'?id='.$task_time->fk_task.'&amp;action=deleteline&amp;lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
					print img_delete('default', 'class="pictodelete paddingleft"');
					print '</a>';

					if ($massactionbutton || $massaction)	// If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
					{
						$selected = 0;
						if (in_array($task_time->rowid, $arrayofselected)) $selected = 1;
						print '&nbsp;';
						print '<input id="cb'.$task_time->rowid.'" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="'.$task_time->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
					}
				}
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;

			print "</tr>\n";


			// Add line to split

			if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
			{
				print '<tr class="oddeven">';

				// Date
				if (!empty($arrayfields['t.task_date']['checked']))
				{
					print '<td class="nowrap">';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						if (empty($task_time->task_date_withhour))
						{
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 3, 3, 2, "timespent_date", 1, 0);
						}
						else print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 1, 1, 2, "timespent_date", 1, 0);
					}
					else
					{
						print dol_print_date(($date2 ? $date2 : $date1), ($task_time->task_date_withhour ? 'dayhour' : 'day'));
					}
					print '</td>';
				}

				// Task ref
				if (!empty($arrayfields['t.task_ref']['checked']))
				{
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
					{
						print '<td class="nowrap">';
						$tasktmp->id = $task_time->fk_task;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
						print '</td>';
					}
				}

				// Task label
				if (!empty($arrayfields['t.task_label']['checked']))
				{
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
					{
						print '<td class="nowrap">';
						print $task_time->label;
						print '</td>';
					}
				}

				// User
				if (!empty($arrayfields['author']['checked']))
				{
					print '<td>';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						if (empty($object->id)) $object->fetch($id);
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
					}
					else
					{
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
				if (!empty($arrayfields['t.note']['checked']))
				{
					print '<td class="left">';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						print '<textarea name="timespent_note_line" width="95%" rows="'.ROWS_2.'">'.$task_time->note.'</textarea>';
					}
					else
					{
						print dol_nl2br($task_time->note);
					}
					print '</td>';
				}
				elseif ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
				{
					print '<input type="hidden" name="timespent_note_line" value="'.$task_time->note.'">';
				}

				// Time spent
				if (!empty($arrayfields['t.task_duration']['checked']))
				{
					print '<td class="right">';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						print '<input type="hidden" name="old_duration" value="'.$task_time->task_duration.'">';
						print $form->select_duration('new_duration', $task_time->task_duration, 0, 'text');
					}
					else
					{
						print convertSecondToTime($task_time->task_duration, 'allhourmin');
					}
					print '</td>';
				}

				// Value spent
				if (!empty($arrayfields['value']['checked']))
				{
					print '<td class="right">';
					$value = price2num($task_time->thm * $task_time->task_duration / 3600, 'MT', 1);
					print price($value, 1, $langs, 1, -1, -1, $conf->currency);
					print '</td>';
				}

				// Value billed
				if (!empty($arrayfields['valuebilled']['checked']))
				{
					print '<td class="right">';
					$valuebilled = price2num($task_time->total_ht, '', 1);
					if (isset($task_time->total_ht)) print price($valuebilled, 1, $langs, 1, -1, -1, $conf->currency);
					print '</td>';
				}

				/*
				 // Extra fields
				 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
				 */

				// Fields from hook
				$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$task_time);
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Action column
				print '<td class="center nowraponall">';
				print '</td>';

				print "</tr>\n";


				// Line for second dispatching

				print '<tr class="oddeven">';

				// Date
				if (!empty($arrayfields['t.task_date']['checked']))
				{
					print '<td class="nowrap">';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						if (empty($task_time->task_date_withhour))
						{
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline_2', 3, 3, 2, "timespent_date", 1, 0);
						}
						else print $form->selectDate(($date2 ? $date2 : $date1), 'timeline_2', 1, 1, 2, "timespent_date", 1, 0);
					}
					else
					{
						print dol_print_date(($date2 ? $date2 : $date1), ($task_time->task_date_withhour ? 'dayhour' : 'day'));
					}
					print '</td>';
				}

				// Task ref
				if (!empty($arrayfields['t.task_ref']['checked']))
				{
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
					{
						print '<td class="nowrap">';
						$tasktmp->id = $task_time->fk_task;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
						print '</td>';
					}
				}

				// Task label
				if (!empty($arrayfields['t.task_label']['checked']))
				{
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes))	// Not a dedicated task
					{
						print '<td class="nowrap">';
						print $task_time->label;
						print '</td>';
					}
				}

				// User
				if (!empty($arrayfields['author']['checked']))
				{
					print '<td>';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						if (empty($object->id)) $object->fetch($id);
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
					}
					else
					{
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
				if (!empty($arrayfields['t.note']['checked']))
				{
					print '<td class="left">';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						print '<textarea name="timespent_note_line_2" width="95%" rows="'.ROWS_2.'">'.$task_time->note.'</textarea>';
					}
					else
					{
						print dol_nl2br($task_time->note);
					}
					print '</td>';
				}
				elseif ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
				{
					print '<input type="hidden" name="timespent_note_line_2" value="'.$task_time->note.'">';
				}

				// Time spent
				if (!empty($arrayfields['t.task_duration']['checked']))
				{
					print '<td class="right">';
					if ($action == 'splitline' && $_GET['lineid'] == $task_time->rowid)
					{
						print '<input type="hidden" name="old_duration_2" value="0">';
						print $form->select_duration('new_duration_2', 0, 0, 'text');
					}
					else
					{
						print convertSecondToTime($task_time->task_duration, 'allhourmin');
					}
					print '</td>';
				}

				// Value spent
				if (!empty($arrayfields['value']['checked']))
				{
					print '<td class="right">';
					$value = 0;
					print price($value, 1, $langs, 1, -1, -1, $conf->currency);
					print '</td>';
				}

				// Value billed
				if (!empty($arrayfields['valuebilled']['checked']))
				{
					print '<td class="right">';
					$valuebilled = price2num($task_time->total_ht, '', 1);
					if (isset($task_time->total_ht)) print price($valuebilled, 1, $langs, 1, -1, -1, $conf->currency);
					print '</td>';
				}

				/*
				 // Extra fields
				 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
				 */

				// Fields from hook
				$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$task_time);
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
		if (isset($totalarray['totaldurationfield']) || isset($totalarray['totalvaluefield']))
		{
			print '<tr class="liste_total">';
			$i = 0;
			while ($i < $totalarray['nbfield'])
			{
				$i++;
				if ($i == 1)
				{
					if ($num < $limit && empty($offset)) print '<td class="left">'.$langs->trans("Total").'</td>';
					else print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
				}
				elseif ($totalarray['totaldurationfield'] == $i) print '<td class="right">'.convertSecondToTime($totalarray['totalduration'], 'allhourmin').'</td>';
				elseif ($totalarray['totalvaluefield'] == $i) print '<td class="right">'.price($totalarray['totalvalue']).'</td>';
				//elseif ($totalarray['totalvaluebilledfield'] == $i) print '<td class="center">'.price($totalarray['totalvaluebilled']).'</td>';
				else print '<td></td>';
			}
			print '</tr>';
		}

		if (!count($tasks))
		{
			$totalnboffields = 1;
			foreach ($arrayfields as $value)
			{
				if ($value['checked']) $totalnboffields++;
			}
			print '<tr class="oddeven"><td colspan="'.$totalnboffields.'">';
			print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
			print '</td></tr>';
		}


		print "</table>";
		print '</div>';
		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
