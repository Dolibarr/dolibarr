<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2019-2021	Christophe Battarel		<christophe@altairis.fr>
 * Copyright (C) 2023		Gauthier VERDOL			<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Vincent de Grandpré		<vincent@de-grandpre.quebec>
 * Copyright (C) 2024		Solution Libre SAS		<contact@solution-libre.fr>
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
 *    \file        htdocs/projet/tasks/time.php
 *    \ingroup    project
 *    \brief        Page to add new time spent on a task
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formintervention.class.php';

// Load translation files required by the page
$langsLoad = array('projects', 'bills', 'orders', 'companies');
if (isModEnabled('eventorganization')) {
	$langsLoad[] = 'eventorganization';
}

$langs->loadLangs($langsLoad);

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'timespentlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');

$id = GETPOSTINT('id');
$projectid = GETPOSTINT('projectid');
$ref = GETPOST('ref', 'alpha');
$withproject = GETPOSTINT('withproject');
$project_ref = GETPOST('project_ref', 'alpha');
$tab = GETPOST('tab', 'aZ09');

$search_day = GETPOSTINT('search_day');
$search_month = GETPOSTINT('search_month');
$search_year = GETPOSTINT('search_year');
$search_date_startday = GETPOSTINT('search_date_startday');
$search_date_startmonth = GETPOSTINT('search_date_startmonth');
$search_date_startyear = GETPOSTINT('search_date_startyear');
$search_date_endday = GETPOSTINT('search_date_endday');
$search_date_endmonth = GETPOSTINT('search_date_endmonth');
$search_date_endyear = GETPOSTINT('search_date_endyear');
$search_date_start = dol_mktime(0, 0, 0, $search_date_startmonth, $search_date_startday, $search_date_startyear); // Use tzserver
$search_date_end = dol_mktime(23, 59, 59, $search_date_endmonth, $search_date_endday, $search_date_endyear);
$search_note = GETPOST('search_note', 'alpha');
$search_duration = GETPOST('search_duration', 'alpha');
$search_task_ref = GETPOST('search_task_ref', 'alpha');
$search_task_label = GETPOST('search_task_label', 'alpha');
$search_user = GETPOST('search_user', 'intcomma');
$search_valuebilled = GETPOST('search_valuebilled', 'intcomma');
$search_product_ref = GETPOST('search_product_ref', 'alpha');
$search_company = GETPOST('$search_company', 'alpha');
$search_company_alias = GETPOST('$search_company_alias', 'alpha');
$search_project_ref = GETPOST('$search_project_ref', 'alpha');
$search_project_label = GETPOST('$search_project_label', 'alpha');
$search_timespent_starthour = GETPOSTINT("search_timespent_duration_starthour");
$search_timespent_startmin = GETPOSTINT("search_timespent_duration_startmin");
$search_timespent_endhour = GETPOSTINT("search_timespent_duration_endhour");
$search_timespent_endmin = GETPOSTINT("search_timespent_duration_endmin");

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}        // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 't.element_date,t.element_datehour,t.rowid';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC,DESC';
}

$childids = $user->getAllChildIds(1);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
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
//if ($user->socid > 0) $socid = $user->socid;	  // For external user, no check is done on company because readability is managed by public status of project and assignment.
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}

if ($object->fk_project > 0) {
	restrictedArea($user, 'projet', $object->fk_project, 'projet&project');
} else {
	restrictedArea($user, 'projet', 0, 'projet&project');
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

$parameters = array('socid' => $socid, 'projectid' => $projectid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_day = '';
	$search_month = '';
	$search_year = '';
	$search_note = '';
	$search_duration = '';
	$search_value = '';
	$search_date_startday = '';
	$search_date_startmonth = '';
	$search_date_startyear = '';
	$search_date_endday = '';
	$search_date_endmonth = '';
	$search_date_endyear = '';
	$search_date_start = '';
	$search_date_end = '';
	$search_task_ref = '';
	$search_company = '';
	$search_company_alias = '';
	$search_project_ref = '';
	$search_project_label = '';
	$search_task_label = '';
	$search_user = -1;
	$search_valuebilled = '';
	$search_product_ref = '';
	$toselect = array();
	$search_array_options = array();
	$search_timespent_starthour = '';
	$search_timespent_startmin = '';
	$search_timespent_endhour = '';
	$search_timespent_endmin = '';
	$action = '';
}

if ($action == 'addtimespent' && $user->hasRight('projet', 'time')) {
	$error = 0;

	$timespent_durationhour = GETPOSTINT('timespent_durationhour');
	$timespent_durationmin = GETPOSTINT('timespent_durationmin');
	if (empty($timespent_durationhour) && empty($timespent_durationmin)) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}
	if (!GETPOSTINT("userid")) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorUserNotAssignedToTask'), null, 'errors');
		$error++;
	}

	if (!$error) {
		if ($id || $ref) {
			$object->fetch($id, $ref);
		} else {
			if (!GETPOSTINT('taskid') || GETPOSTINT('taskid') < 0) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Task")), null, 'errors');
				$action = 'createtime';
				$error++;
			} else {
				$object->fetch(GETPOSTINT('taskid'));
			}
		}

		if (!$error) {
			$object->fetch_projet();

			if (empty($object->project->status)) {
				setEventMessages($langs->trans("ProjectMustBeValidatedFirst"), null, 'errors');
				$action = 'createtime';
				$error++;
			} else {
				$object->timespent_note = GETPOST("timespent_note", 'alpha');
				if (GETPOSTINT('progress') > 0) {
					$object->progress = GETPOSTINT('progress'); // If progress is -1 (not defined), we do not change value
				}
				$object->timespent_duration = GETPOSTINT("timespent_durationhour") * 60 * 60; // We store duration in seconds
				$object->timespent_duration += (GETPOSTINT('timespent_durationmin') ? GETPOSTINT('timespent_durationmin') : 0) * 60; // We store duration in seconds
				if (GETPOST("timehour") != '' && GETPOST("timehour") >= 0) {    // If hour was entered
					$object->timespent_date = dol_mktime(GETPOSTINT("timehour"), GETPOSTINT("timemin"), 0, GETPOSTINT("timemonth"), GETPOSTINT("timeday"), GETPOSTINT("timeyear"));
					$object->timespent_withhour = 1;
				} else {
					$object->timespent_date = dol_mktime(12, 0, 0, GETPOSTINT("timemonth"), GETPOSTINT("timeday"), GETPOSTINT("timeyear"));
				}
				$object->timespent_fk_user = GETPOSTINT("userid");
				$object->timespent_fk_product = GETPOSTINT("fk_product");

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
		$action = 'createtime';
	}
}

if (($action == 'updateline' || $action == 'updatesplitline') && !$cancel && $user->hasRight('projet', 'lire')) {
	$error = 0;

	if (!GETPOST("new_durationhour") && !GETPOST("new_durationmin")) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Duration")), null, 'errors');
		$error++;
	}

	if (!$error) {
		if (GETPOSTINT('taskid') != $id) {        // GETPOST('taskid') is id of new task
			$id_temp = GETPOSTINT('taskid'); // should not overwrite $id


			$object->fetchTimeSpent(GETPOSTINT('lineid'));

			$result = 0;
			if (in_array($object->timespent_fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
				$result = $object->delTimeSpent($user);
			}

			$object->fetch($id_temp, $ref);

			$object->timespent_note = GETPOST("timespent_note_line", "alphanohtml");
			$object->timespent_old_duration = GETPOSTINT("old_duration");
			$object->timespent_duration = GETPOSTINT("new_durationhour") * 60 * 60; // We store duration in seconds
			$object->timespent_duration += (GETPOSTINT("new_durationmin") ? GETPOSTINT('new_durationmin') : 0) * 60; // We store duration in seconds
			if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0) {    // If hour was entered
				$object->timespent_date = dol_mktime(GETPOST("timelinehour"), GETPOST("timelinemin"), 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
				$object->timespent_withhour = 1;
			} else {
				$object->timespent_date = dol_mktime(12, 0, 0, GETPOST("timelinemonth"), GETPOST("timelineday"), GETPOST("timelineyear"));
			}
			$object->timespent_fk_user = GETPOSTINT("userid_line");
			$object->timespent_fk_product = GETPOSTINT("fk_product");
			$object->timespent_invoiceid = GETPOSTINT("invoiceid");
			$object->timespent_invoicelineid = GETPOSTINT("invoicelineid");

			$result = 0;
			if (in_array($object->timespent_fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
				$result = $object->addTimeSpent($user);
				if ($result >= 0) {
					setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				} else {
					setEventMessages($langs->trans($object->error), null, 'errors');
					$error++;
				}
			}
		} else {
			$object->fetch($id, $ref);

			$object->timespent_id = GETPOSTINT("lineid");
			$object->timespent_note = GETPOST("timespent_note_line", "alphanohtml");
			$object->timespent_old_duration = GETPOSTINT("old_duration");
			$object->timespent_duration = GETPOSTINT("new_durationhour") * 60 * 60; // We store duration in seconds
			$object->timespent_duration += (GETPOSTINT("new_durationmin") ? GETPOSTINT('new_durationmin') : 0) * 60; // We store duration in seconds
			if (GETPOST("timelinehour") != '' && GETPOST("timelinehour") >= 0) {    // If hour was entered
				$object->timespent_date = dol_mktime(GETPOSTINT("timelinehour"), GETPOSTINT("timelinemin"), 0, GETPOSTINT("timelinemonth"), GETPOSTINT("timelineday"), GETPOSTINT("timelineyear"));
				$object->timespent_withhour = 1;
			} else {
				$object->timespent_date = dol_mktime(12, 0, 0, GETPOSTINT("timelinemonth"), GETPOSTINT("timelineday"), GETPOSTINT("timelineyear"));
			}
			$object->timespent_fk_user = GETPOSTINT("userid_line");
			$object->timespent_fk_product = GETPOSTINT("fk_product");
			$object->timespent_invoiceid = GETPOSTINT("invoiceid");
			$object->timespent_invoicelineid = GETPOSTINT("invoicelineid");
			$result = 0;

			if (in_array($object->timespent_fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
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

if ($action == 'confirm_deleteline' && $confirm == "yes" && ($user->hasRight('projet', 'time') || $user->hasRight('projet', 'all', 'creer'))) {
	$object->fetchTimeSpent(GETPOSTINT('lineid'));    // load properties like $object->timespent_xxx

	if (in_array($object->timespent_fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
		$result = $object->delTimeSpent($user);    // delete line with $object->timespent_id

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
			header("Location: " . DOL_URL_ROOT . '/projet/tasks.php?id=' . $projectstatic->id . ($withproject ? '&withproject=1' : '') . (empty($mode) ? '' : '&mode=' . $mode));
			exit;
		}
	}
}

// To show all time lines for project
$projectidforalltimes = 0;
if (GETPOSTINT('projectid') > 0) {
	$projectidforalltimes = GETPOSTINT('projectid');

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
		include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

		$tmpinvoice = new Facture($db);
		$tmptimespent = new Task($db);
		$tmpproduct = new Product($db);
		$fuser = new User($db);
		$remiseproject = price2num(GETPOST('remiseprojet', 'alphanohtml'));
		$condidproject = GETPOSTINT('condidproject');
		$db->begin();
		$idprod = GETPOSTINT('productid');
		$generateinvoicemode = GETPOST('generateinvoicemode', 'alphanohtml');
		$invoiceToUse = GETPOSTINT('invoiceid');

		$prodDurationHoursBase = 1.0;
		$product_data_cache = array();
		if ($idprod > 0) {
			$tmpproduct->fetch($idprod);
			if ($result < 0) {
				$error++;
				setEventMessages($tmpproduct->error, $tmpproduct->errors, 'errors');
			}

			$prodDurationHoursBase = $tmpproduct->getProductDurationHours();
			if ($prodDurationHoursBase < 0) {
				$error++;
				$langs->load("errors");
				setEventMessages(null, $tmpproduct->errors, 'errors');
			}

			$dataforprice = $tmpproduct->getSellPrice($mysoc, $projectstatic->thirdparty, 0);

			$pu_ht = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
			$txtva = $dataforprice['tva_tx'];
			$localtax1 = $dataforprice['localtax1'];
			$localtax2 = $dataforprice['localtax2'];
		} else {
			$prodDurationHoursBase = 1;

			$pu_ht = 0;
			$txtva = get_default_tva($mysoc, $projectstatic->thirdparty);
			$localtax1 = get_default_localtax($mysoc, $projectstatic->thirdparty, 1);
			$localtax2 = get_default_localtax($mysoc, $projectstatic->thirdparty, 2);
		}

		$tmpinvoice->socid = $projectstatic->thirdparty->id;
		$tmpinvoice->date = dol_mktime(GETPOSTINT('rehour'), GETPOSTINT('remin'), GETPOSTINT('resec'), GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
		$tmpinvoice->fk_project = $projectstatic->id;
		$tmpinvoice->cond_reglement_id = $condidproject;
		$tmpinvoice->mode_reglement_id = $projectstatic->thirdparty->mode_reglement_id;
		$tmpinvoice->fk_account = $projectstatic->thirdparty->fk_account;

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
			if ($generateinvoicemode == 'onelineperuser') {        // 1 line per user (and per product)
				$arrayoftasks = array();
				foreach ($toselect as $key => $value) {
					// Get userid, timepent
					$object->fetchTimeSpent($value);    // $value is ID of 1 line in timespent table
					$arrayoftasks[$object->timespent_fk_user][(int) $object->timespent_fk_product]['timespent'] += $object->timespent_duration;
					$arrayoftasks[$object->timespent_fk_user][(int) $object->timespent_fk_product]['totalvaluetodivideby3600'] += ($object->timespent_duration * $object->timespent_thm);
				}

				foreach ($arrayoftasks as $userid => $data) {
					$fuser->fetch($userid);
					$username = $fuser->getFullName($langs);

					foreach ($data as $fk_product => $timespent_data) {
						// Define qty per hour
						$qtyhour = $timespent_data['timespent'] / 3600;
						$qtyhourtext = convertSecondToTime($timespent_data['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

						// Set the unit price we want to sell the time, for this user
						if (getDolGlobalInt('PROJECT_USE_REAL_COST_FOR_TIME_INVOICING')) {
							// We set unit price to 0 to force the use of the rate saved during recording
							$pu_ht = 0;
						} elseif ($idprod <= 0) {
							// We want to sell all the time spent with the last hourly rate of user
							// -> but what about choice user selected ? add idprod test
							$pu_ht = $fuser->thm;
						}

						// If no unit price known for user, we use the price recorded when recording timespent.
						if (empty($pu_ht)) {
							if ($timespent_data['timespent']) {
								$pu_ht = price2num(($timespent_data['totalvaluetodivideby3600'] / $timespent_data['timespent']), 'MU');
							}
						}

						// Add lines
						$prodDurationHours = $prodDurationHoursBase;
						$idprodline = $idprod;
						$pu_htline = $pu_ht;
						$txtvaline = $txtva;
						$localtax1line = $localtax1;
						$localtax2line = $localtax2;

						// If a particular product/service was defined for the task
						if (!empty($fk_product) && ($fk_product > 0) && ($fk_product !== $idprod)) {
							if (!array_key_exists($fk_product, $product_data_cache)) {
								$result = $tmpproduct->fetch($fk_product);
								if ($result < 0) {
									$error++;
									setEventMessages($tmpproduct->error, $tmpproduct->errors, 'errors');
								}
								$prodDurationHours = $tmpproduct->getProductDurationHours();
								if ($prodDurationHours < 0) {
									$error++;
									$langs->load("errors");
									setEventMessages(null, $tmpproduct->errors, 'errors');
								}

								$dataforprice = $tmpproduct->getSellPrice($mysoc, $projectstatic->thirdparty, 0);

								$pu_htline = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
								$txtvaline = $dataforprice['tva_tx'];
								$localtax1line = $dataforprice['localtax1'];
								$localtax2line = $dataforprice['localtax2'];

								$product_data_cache[$fk_product] = array('duration' => $prodDurationHours, 'dataforprice' => $dataforprice);
							} else {
								$prodDurationHours = $product_data_cache[$fk_product]['duration'];
								$pu_htline = empty($product_data_cache[$fk_product]['dataforprice']['pu_ht']) ? 0 : $product_data_cache[$fk_product]['dataforprice']['pu_ht'];
								$txtvaline = $product_data_cache[$fk_product]['dataforprice']['tva_tx'];
								$localtax1line = $product_data_cache[$fk_product]['dataforprice']['localtax1'];
								$localtax2line = $product_data_cache[$fk_product]['dataforprice']['localtax2'];
							}
							$idprodline = $fk_product;
						}

						// Add lines
						$lineid = $tmpinvoice->addline($langs->trans("TimeSpentForInvoice", $username) . ' : ' . $qtyhourtext, $pu_htline, round($qtyhour / $prodDurationHours, 2), $txtvaline, $localtax1line, $localtax2line, ($idprodline > 0 ? $idprodline : 0), $remiseproject);
						if ($lineid < 0) {
							$error++;
							setEventMessages(null, $tmpinvoice->errors, 'errors');
						}

						// Update lineid into line of timespent
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'element_time SET invoice_line_id = '.((int) $lineid).', invoice_id = '.((int) $tmpinvoice->id);
						$sql .= ' WHERE rowid IN ('.$db->sanitize(implode(',', $toselect)).') AND fk_user = '.((int) $userid);
						$result = $db->query($sql);
						if (!$result) {
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
							break;
						}
					}
				}
			} elseif ($generateinvoicemode == 'onelineperperiod') {    // One line for each time spent line
				$arrayoftasks = array();

				$withdetail = GETPOST('detail_time_duration', 'alpha');
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
					$arrayoftasks[$object->timespent_id]['note'] = $ftask->ref . ' - ' . $ftask->label . ' - ' . $username;
					$arrayoftasks[$object->timespent_id]['note'] = dol_concatdesc($arrayoftasks[$object->timespent_id]['note'], $object->timespent_note);

					if (!empty($withdetail)) {
						if (!empty($object->timespent_withhour)) {
							$arrayoftasks[$object->timespent_id]['note'] = dol_concatdesc($arrayoftasks[$object->timespent_id]['note'], $langs->trans("Date") . ': ' . dol_print_date($object->timespent_datehour));
						} else {
							$arrayoftasks[$object->timespent_id]['note'] = dol_concatdesc($arrayoftasks[$object->timespent_id]['note'], $langs->trans("Date") . ': ' . dol_print_date($object->timespent_date));
						}
						$arrayoftasks[$object->timespent_id]['note'] = dol_concatdesc($arrayoftasks[$object->timespent_id]['note'], $langs->trans("Duration") . ': ' . convertSecondToTime($object->timespent_duration, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY));
					}
					$arrayoftasks[$object->timespent_id]['user'] = $object->timespent_fk_user;
					$arrayoftasks[$object->timespent_id]['fk_product'] = $object->timespent_fk_product;
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
					$prodDurationHours = $prodDurationHoursBase;
					$idprodline = $idprod;
					$pu_htline = $pu_ht;
					$txtvaline = $txtva;
					$localtax1line = $localtax1;
					$localtax2line = $localtax2;

					if (!empty($value['fk_product']) && $value['fk_product'] !== $idprod) {
						if (!array_key_exists($value['fk_product'], $product_data_cache)) {
							$result = $tmpproduct->fetch($value['fk_product']);
							if ($result < 0) {
								$error++;
								setEventMessages($tmpproduct->error, $tmpproduct->errors, 'errors');
							}
							$prodDurationHours = $tmpproduct->getProductDurationHours();
							if ($prodDurationHours < 0) {
								$error++;
								$langs->load("errors");
								setEventMessages(null, $tmpproduct->errors, 'errors');
							}

							$dataforprice = $tmpproduct->getSellPrice($mysoc, $projectstatic->thirdparty, 0);

							$pu_htline = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
							$txtvaline = $dataforprice['tva_tx'];
							$localtax1line = $dataforprice['localtax1'];
							$localtax2line = $dataforprice['localtax2'];

							$product_data_cache[$value['fk_product']] = array('duration' => $prodDurationHours, 'dataforprice' => $dataforprice);
						} else {
							$prodDurationHours = $product_data_cache[$value['fk_product']]['duration'];
							$pu_htline = empty($product_data_cache[$value['fk_product']]['dataforprice']['pu_ht']) ? 0 : $product_data_cache[$value['fk_product']]['dataforprice']['pu_ht'];
							$txtvaline = $product_data_cache[$value['fk_product']]['dataforprice']['tva_tx'];
							$localtax1line = $product_data_cache[$value['fk_product']]['dataforprice']['localtax1'];
							$localtax2line = $product_data_cache[$value['fk_product']]['dataforprice']['localtax2'];
						}
						$idprodline = $value['fk_product'];
					}
					$lineid = $tmpinvoice->addline($value['note'], $pu_htline, round($qtyhour / $prodDurationHours, 2), $txtvaline, $localtax1line, $localtax2line, ($idprodline > 0 ? $idprodline : 0), $remiseproject);
					if ($lineid < 0) {
						$error++;
						setEventMessages(null, $tmpinvoice->errors, 'errors');
					}
					//var_dump($lineid);exit;

					// Update lineid into line of timespent
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'element_time SET invoice_line_id = '.((int) $lineid).', invoice_id = '.((int) $tmpinvoice->id);
					$sql .= ' WHERE rowid IN ('.$db->sanitize(implode(',', $toselect)).') AND fk_user = '.((int) $userid);
					$result = $db->query($sql);
					if (!$result) {
						$error++;
						setEventMessages($db->lasterror(), null, 'errors');
						break;
					}
				}
			} elseif ($generateinvoicemode == 'onelinepertask') {    // One line for each different task
				$arrayoftasks = array();
				foreach ($toselect as $key => $value) {
					// Get userid, timepent
					$object->fetchTimeSpent($value);        // Call method to get list of timespent for a timespent line id (We use the utiliy method found into Task object)
					// $object->id is now the task id
					$arrayoftasks[$object->id][(int) $object->timespent_fk_product]['timespent'] += $object->timespent_duration;
					$arrayoftasks[$object->id][(int) $object->timespent_fk_product]['totalvaluetodivideby3600'] += ($object->timespent_duration * $object->timespent_thm);
				}

				foreach ($arrayoftasks as $task_id => $data) {
					$ftask = new Task($db);
					$ftask->fetch($task_id);

					foreach ($data as $fk_product => $timespent_data) {
						$qtyhour = $timespent_data['timespent'] / 3600;
						$qtyhourtext = convertSecondToTime($timespent_data['timespent'], 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);

						// Add lines
						$prodDurationHours = $prodDurationHoursBase;
						$idprodline = $idprod;
						$pu_htline = $pu_ht;
						$txtvaline = $txtva;
						$localtax1line = $localtax1;
						$localtax2line = $localtax2;

						if (!empty($fk_product) && $fk_product !== $idprod) {
							if (!array_key_exists($fk_product, $product_data_cache)) {
								$result = $tmpproduct->fetch($fk_product);
								if ($result < 0) {
									$error++;
									setEventMessages($tmpproduct->error, $tmpproduct->errors, 'errors');
								}
								$prodDurationHours = $tmpproduct->getProductDurationHours();
								if ($prodDurationHours < 0) {
									$error++;
									$langs->load("errors");
									setEventMessages(null, $tmpproduct->errors, 'errors');
								}

								$dataforprice = $tmpproduct->getSellPrice($mysoc, $projectstatic->thirdparty, 0);

								$pu_htline = empty($dataforprice['pu_ht']) ? 0 : $dataforprice['pu_ht'];
								$txtvaline = $dataforprice['tva_tx'];
								$localtax1line = $dataforprice['localtax1'];
								$localtax2line = $dataforprice['localtax2'];

								$product_data_cache[$fk_product] = array('duration' => $prodDurationHours, 'dataforprice' => $dataforprice);
							} else {
								$prodDurationHours = $product_data_cache[$fk_product]['duration'];
								$pu_htline = empty($product_data_cache[$fk_product]['dataforprice']['pu_ht']) ? 0 : $product_data_cache[$fk_product]['dataforprice']['pu_ht'];
								$txtvaline = $product_data_cache[$fk_product]['dataforprice']['tva_tx'];
								$localtax1line = $product_data_cache[$fk_product]['dataforprice']['localtax1'];
								$localtax2line = $product_data_cache[$fk_product]['dataforprice']['localtax2'];
							}
							$idprodline = $fk_product;
						}


						if ($idprodline > 0) {
							// If a product is defined, we msut use the $prodDurationHours and $pu_ht of product (already set previously).
							$pu_ht_for_task = $pu_htline;
							// If we want to reuse the value of timespent (so use same price than cost price)
							if (getDolGlobalString('PROJECT_TIME_SPENT_INTO_INVOICE_USE_VALUE')) {
								$pu_ht_for_task = price2num($timespent_data['totalvaluetodivideby3600'] / $timespent_data['timespent'], 'MU') * $prodDurationHours;
							}
							$pa_ht = price2num($timespent_data['totalvaluetodivideby3600'] / $timespent_data['timespent'], 'MU') * $prodDurationHours;
						} else {
							// If not product used, we use the hour unit for duration and unit price.
							$pu_ht_for_task = 0;
							// If we want to reuse the value of timespent (so use same price than cost price)
							if (getDolGlobalString('PROJECT_TIME_SPENT_INTO_INVOICE_USE_VALUE')) {
								$pu_ht_for_task = price2num($timespent_data['totalvaluetodivideby3600'] / $timespent_data['timespent'], 'MU');
							}
							$pa_ht = price2num($timespent_data['totalvaluetodivideby3600'] / $timespent_data['timespent'], 'MU');
						}

						// Add lines
						$date_start = '';
						$date_end = '';
						$lineName = $ftask->ref . ' - ' . $ftask->label;
						$lineid = $tmpinvoice->addline($lineName, $pu_ht_for_task, price2num($qtyhour / $prodDurationHours, 'MS'), $txtvaline, $localtax1line, $localtax2line, ($idprodline > 0 ? $idprodline : 0), $remiseproject, $date_start, $date_end, 0, 0, '', 'HT', 0, 1, -1, 0, '', 0, 0, null, $pa_ht);
						if ($lineid < 0) {
							$error++;
							setEventMessages($tmpinvoice->error, $tmpinvoice->errors, 'errors');
							break;
						}

						if (!$error) {
							// Update lineid into line of timespent
							$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'element_time SET invoice_line_id = ' . ((int) $lineid) . ', invoice_id = ' . ((int) $tmpinvoice->id);
							$sql .= ' WHERE rowid IN (' . $db->sanitize(implode(',', $toselect)) . ')';
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

	if (!empty($projectstatic->socid)) {
		$projectstatic->fetch_thirdparty();
	}

	if (!($projectstatic->thirdparty->id > 0)) {
		setEventMessages($langs->trans("ThirdPartyRequiredToGenerateIntervention"), null, 'errors');
	} else {
		include_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
		include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';


		require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
		$tmpinter = new Fichinter($db);
		$tmptimespent = new Task($db);
		$fuser = new User($db);

		$db->begin();
		$interToUse = GETPOSTINT('interid');


		$tmpinter->socid = $projectstatic->thirdparty->id;
		$tmpinter->date = dol_mktime(GETPOSTINT('rehour'), GETPOSTINT('remin'), GETPOSTINT('resec'), GETPOSTINT('remonth'), GETPOSTINT('reday'), GETPOSTINT('reyear'));
		$tmpinter->fk_project = $projectstatic->id;
		$tmpinter->description = $projectstatic->title . (!empty($projectstatic->description) ? '-' . $projectstatic->label : '');

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
				$lineid = $tmpinter->addline($user, $tmpinter->id, $ftask->label . (!empty($value['note']) ? ' - ' . $value['note'] : ''), $value['date'], $value['timespent']);
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

$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);
//$result = $projectstatic->fetch($object->fk_project);
$arrayofselected = is_array($toselect) ? $toselect : array();

$title = $object->ref . ' - ' . $langs->trans("TimeSpent");
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': ' . $projectstatic->ref : '');
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-project project-tasks page-task_time');

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
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_TASK') && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$result = $projectstatic->fetch($object->fk_project);
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
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
				$param .= '&search_user=' . ((int) $search_user);
			}
			if ($search_month) {
				$param .= '&search_month=' . ((int) $search_month);
			}
			if ($search_year) {
				$param .= '&search_year=' . ((int) $search_year);
			}

			// Project card

			$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Title
			$morehtmlref .= $projectstatic->title;
			// Thirdparty
			if (!empty($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
				$morehtmlref .= '<br>' . $projectstatic->thirdparty->getNomUrl(1, 'project');
			}
			$morehtmlref .= '</div>';

			// Define a complementary filter for search of next/prev ref.
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
				$projectstatic->next_prev_filter = "rowid IN (" . $db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0') . ")";
			}

			dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Usage
			if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
				print '<tr><td class="tdtop">';
				print $langs->trans("Usage");
				print '</td>';
				print '<td>';
				if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
					print '<input type="checkbox" disabled name="usage_opportunity"' . (GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')) . '"> ';
					$htmltext = $langs->trans("ProjectFollowOpportunity");
					print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
					print '<br>';
				}
				if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
					print '<input type="checkbox" disabled name="usage_task"' . (GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')) . '"> ';
					$htmltext = $langs->trans("ProjectFollowTasks");
					print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
					print '<br>';
				}
				if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
					print '<input type="checkbox" disabled name="usage_bill_time"' . (GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')) . '"> ';
					$htmltext = $langs->trans("ProjectBillTimeDescription");
					print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
					print '<br>';
				}
				if (isModEnabled('eventorganization')) {
					print '<input type="checkbox" disabled name="usage_organize_event"' . (GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')) . '"> ';
					$htmltext = $langs->trans("EventOrganizationDescriptionLong");
					print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
				}
				print '</td></tr>';
			}

			// Visibility
			print '<tr><td class="titlefield">' . $langs->trans("Visibility") . '</td><td>';
			if ($projectstatic->public) {
				print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
				print $langs->trans('SharedProject');
			} else {
				print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
				print $langs->trans('PrivateProject');
			}
			print '</td></tr>';

			// Budget
			print '<tr><td>' . $langs->trans("Budget") . '</td><td>';
			if (!is_null($projectstatic->budget_amount) && strcmp($projectstatic->budget_amount, '')) {
				print '<span class="amount">' . price($projectstatic->budget_amount, 0, $langs, 1, 0, 0, $conf->currency) . '</span>';
			}
			print '</td></tr>';

			// Date start - end project
			print '<tr><td>' . $langs->trans("Dates") . '</td><td>';
			$start = dol_print_date($projectstatic->date_start, 'day');
			print($start ? $start : '?');
			$end = dol_print_date($projectstatic->date_end, 'day');
			print ' - ';
			print($end ? $end : '?');
			if ($projectstatic->hasDelay()) {
				print img_warning("Late");
			}
			print '</td></tr>';

			// Other attributes
			$cols = 2;
			$savobject = $object;
			$object = $projectstatic;
			include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
			$object = $savobject;

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Description
			print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
			print dol_htmlentitiesbr($projectstatic->description);
			print '</td></tr>';

			// Categories
			if (isModEnabled('category')) {
				print '<tr><td class="valignmiddle">' . $langs->trans("Categories") . '</td><td>';
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

		$param = '';

		// Link to create time
		$linktocreatetimeBtnStatus = 0;
		$linktocreatetimeUrl = '';
		$linktocreatetimeHelpText = '';
		if ($user->hasRight('projet', 'time')) {
			if ($projectstatic->public || $userRead > 0) {
				$linktocreatetimeBtnStatus = 1;

				if (!empty($projectidforalltimes)) {
					// We are on tab 'Time Spent' of project
					$backtourl = $_SERVER['PHP_SELF'] . '?projectid=' . $projectstatic->id . ($withproject ? '&withproject=1' : '');
					$linktocreatetimeUrl = $_SERVER['PHP_SELF'] . '?' . ($withproject ? 'withproject=1' : '') . '&projectid=' . $projectstatic->id . '&action=createtime&token=' . newToken() . $param . '&backtopage=' . urlencode($backtourl);
				} else {
					// We are on tab 'Time Spent' of task
					$backtourl = $_SERVER['PHP_SELF'] . '?id=' . $object->id . ($withproject ? '&withproject=1' : '');
					$linktocreatetimeUrl = $_SERVER['PHP_SELF'] . '?' . ($withproject ? 'withproject=1' : '') . ($object->id > 0 ? '&id=' . $object->id : '&projectid=' . $projectstatic->id) . '&action=createtime&token=' . newToken() . $param . '&backtopage=' . urlencode($backtourl);
				}
			} else {
				$linktocreatetimeBtnStatus = -2;
				$linktocreatetimeHelpText = $langs->trans("NotOwnerOfProject");
			}
		} else {
			$linktocreatetimeBtnStatus = -2;
			$linktocreatetimeHelpText = $langs->trans("NotEnoughPermissions");
		}

		$paramsbutton = array('morecss' => 'reposition');
		$linktocreatetime = dolGetButtonTitle($langs->trans('AddTimeSpent'), $linktocreatetimeHelpText, 'fa fa-plus-circle', $linktocreatetimeUrl, '', $linktocreatetimeBtnStatus, $paramsbutton);
	}

	$massactionbutton = '';
	$arrayofmassactions = array();

	if ($projectstatic->id > 0) {
		// If we are on a given project.
		if ($projectstatic->usage_bill_time) {
			$arrayofmassactions = array(
				'generateinvoice' => $langs->trans("GenerateBill"),
				//'builddoc'=>$langs->trans("PDFMerge"),
			);
		}
		if (isModEnabled('intervention') && $user->hasRight('ficheinter', 'creer')) {
			$langs->load("interventions");
			$arrayofmassactions['generateinter'] = $langs->trans("GenerateInter");
		}
	}
	//if ($user->rights->projet->creer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete', 'generateinvoice', 'generateinter'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Task

	// Show section with information of task. If id of task is not defined and project id defined, then $projectidforalltimes is not empty.
	if (empty($projectidforalltimes) && empty($allprojectforuser)) {
		$head = task_prepare_head($object);
		print dol_get_fiche_head($head, 'task_time', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');

		if ($action == 'deleteline') {
			$urlafterconfirm = $_SERVER["PHP_SELF"] . "?" . ($object->id > 0 ? "id=" . $object->id : 'projectid=' . $projectstatic->id) . '&lineid=' . GETPOSTINT("lineid") . ($withproject ? '&withproject=1' : '');
			print $form->formconfirm($urlafterconfirm, $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_deleteline", '', '', 1);
		}

		$param = ($withproject ? '&withproject=1' : '');
		$param .= ($param ? '&' : '') . 'id=' . $object->id;        // ID of task
		$linkback = $withproject ? '<a href="' . DOL_URL_ROOT . '/projet/tasks.php?id=' . $projectstatic->id . '">' . $langs->trans("BackToList") . '</a>' : '';

		if (!GETPOST('withproject') || empty($projectstatic->id)) {
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			$object->next_prev_filter = "fk_projet IN (" . $db->sanitize($projectsListId) . ")";
		} else {
			$object->next_prev_filter = "fk_projet = " . ((int) $projectstatic->id);
		}

		$morehtmlref = '';

		// Project
		if (empty($withproject)) {
			$morehtmlref .= '<div class="refidno">';
			$morehtmlref .= $langs->trans("Project") . ': ';
			$morehtmlref .= $projectstatic->getNomUrl(1);
			$morehtmlref .= '<br>';

			// Third party
			$morehtmlref .= $langs->trans("ThirdParty") . ': ';
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
		print '<tr><td>' . $langs->trans("ChildOfTask") . '</td><td>';
		if ($object->fk_task_parent > 0) {
			$tasktmp = new Task($db);
			$tasktmp->fetch($object->fk_task_parent);
			print $tasktmp->getNomUrl(1);
		}
		print '</td></tr>';

		// Date start - Date end task
		print '<tr><td class="titlefield">' . $langs->trans("DateStart") . ' - ' . $langs->trans("Deadline") . '</td><td>';
		$start = dol_print_date($object->date_start, 'dayhour');
		print($start ? $start : '?');
		$end = dol_print_date($object->date_end, 'dayhour');
		print ' - ';
		print($end ? $end : '?');
		if ($object->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Planned workload
		print '<tr><td>' . $langs->trans("PlannedWorkload") . '</td><td>';
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
		print '<tr><td class="titlefield">' . $langs->trans("ProgressDeclared") . '</td><td>';
		print $object->progress != '' ? $object->progress . ' %' : '';
		print '</td></tr>';

		// Progress calculated
		print '<tr><td>' . $langs->trans("ProgressCalculated") . '</td><td>';
		if ($object->planned_workload) {
			$tmparray = $object->getSummaryOfTimeSpent();
			if ($tmparray['total_duration'] > 0) {
				print round($tmparray['total_duration'] / $object->planned_workload * 100, 2) . ' %';
			} else {
				print '0 %';
			}
		} else {
			print '<span class="opacitymedium">' . $langs->trans("WorkloadNotDefined") . '</span>';
		}
		print '</td>';

		print '</tr>';

		print '</table>';

		print '</div>';

		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();
	} else {
		if ($action == 'deleteline') {
			$urlafterconfirm = $_SERVER["PHP_SELF"] . "?" . ($object->id > 0 ? "id=" . $object->id : 'projectid=' . $projectstatic->id) . '&lineid=' . GETPOSTINT("lineid") . ($withproject ? '&withproject=1' : '');
			print $form->formconfirm($urlafterconfirm, $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_deleteline", '', '', 1);
		}
	}


	if ($projectstatic->id > 0 || $allprojectforuser > 0) {
		// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
		$hookmanager->initHooks(array('tasktimelist'));

		$formconfirm = '';

		if ($action == 'deleteline' && !empty($projectidforalltimes)) {
			// We must use projectidprojectid if on list of timespent of project and id=taskid if on list of timespent of a task
			$urlafterconfirm = $_SERVER["PHP_SELF"] . "?" . ($projectstatic->id > 0 ? 'projectid=' . $projectstatic->id : ($object->id > 0 ? "id=" . $object->id : '')) . '&lineid=' . GETPOSTINT('lineid') . ($withproject ? '&withproject=1' : '') . "&contextpage=" . urlencode($contextpage);
			$formconfirm = $form->formconfirm($urlafterconfirm, $langs->trans("DeleteATimeSpent"), $langs->trans("ConfirmDeleteATimeSpent"), "confirm_deleteline", '', '', 1);
		}

		// Call Hook formConfirm
		$parameters = array('formConfirm' => $formconfirm, "projectstatic" => $projectstatic, "withproject" => $withproject);
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
		$arrayfields['t.element_date'] = array('label' => $langs->trans("Date"), 'checked' => 1);
		$arrayfields['p.fk_soc'] = array('label' => $langs->trans("ThirdParty"), 'type' => 'integer:Societe:/societe/class/societe.class.php:1','checked' => 1);
		$arrayfields['s.name_alias'] = array('label' => $langs->trans("AliasNameShort"), 'type' => 'integer:Societe:/societe/class/societe.class.php:1');
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
			if (! empty($allprojectforuser)) {
				$arrayfields['p.project_ref'] = ['label' => $langs->trans('RefProject'), 'checked' => 1];
				$arrayfields['p.project_label'] = ['label' => $langs->trans('ProjectLabel'), 'checked' => 1];
			}
			$arrayfields['t.element_ref'] = array('label' => $langs->trans("RefTask"), 'checked' => 1);
			$arrayfields['t.element_label'] = array('label' => $langs->trans("LabelTask"), 'checked' => 1);
		}
		$arrayfields['author'] = array('label' => $langs->trans("By"), 'checked' => 1);
		$arrayfields['t.note'] = array('label' => $langs->trans("Note"), 'checked' => 1);
		if (isModEnabled('service') && !empty($projectstatic->thirdparty) && $projectstatic->thirdparty->id > 0 && $projectstatic->usage_bill_time) {
			$arrayfields['t.fk_product'] = array('label' => $langs->trans("Product"), 'checked' => 1);
		}
		$arrayfields['t.element_duration'] = array('label' => $langs->trans("Duration"), 'checked' => 1);
		$arrayfields['value'] = array('label' => $langs->trans("Value"), 'checked' => 1, 'enabled' => isModEnabled("salaries"));
		$arrayfields['valuebilled'] = array('label' => $langs->trans("Billed"), 'checked' => 1, 'enabled' => (((getDolGlobalInt('PROJECT_HIDE_TASKS') || !getDolGlobalInt('PROJECT_BILL_TIME_SPENT')) ? 0 : 1) && $projectstatic->usage_bill_time));
		// Extra fields
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_list_array_fields.tpl.php';

		$arrayfields = dol_sort_array($arrayfields, 'position');
		'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

		$param = '';
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage=' . urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		if ($search_month > 0) {
			$param .= '&search_month=' . urlencode((string) ($search_month));
		}
		if ($search_year > 0) {
			$param .= '&search_year=' . urlencode((string) ($search_year));
		}
		if (!empty($search_user)) { 	// We keep param if -1 because default value is forced to user id if not set
			$param .= '&search_user='.urlencode($search_user);
		}
		if ($search_task_ref != '') {
			$param .= '&search_task_ref=' . urlencode($search_task_ref);
		}
		if ($search_company != '') {
			$param .= '&amp;$search_company=' . urlencode($search_company);
		}
		if ($search_company_alias != '') {
			$param .= '&amp;$search_company_alias=' . urlencode($search_company_alias);
		}
		if ($search_project_ref != '') {
			$param .= '&amp;$search_project_ref=' . urlencode($search_project_ref);
		}
		if ($search_project_label != '') {
			$param .= '&amp;$search_project_label=' . urlencode($search_project_label);
		}
		if ($search_task_label != '') {
			$param .= '&search_task_label=' . urlencode($search_task_label);
		}
		if ($search_note != '') {
			$param .= '&search_note=' . urlencode($search_note);
		}
		if ($search_duration != '') {
			$param .= '&amp;search_field2=' . urlencode((string) ($search_duration));
		}
		if ($optioncss != '') {
			$param .= '&optioncss=' . urlencode($optioncss);
		}
		if ($search_date_startday) {
			$param .= '&search_date_startday=' . urlencode((string) ($search_date_startday));
		}
		if ($search_date_startmonth) {
			$param .= '&search_date_startmonth=' . urlencode((string) ($search_date_startmonth));
		}
		if ($search_date_startyear) {
			$param .= '&search_date_startyear=' . urlencode((string) ($search_date_startyear));
		}
		if ($search_date_endday) {
			$param .= '&search_date_endday=' . urlencode((string) ($search_date_endday));
		}
		if ($search_date_endmonth) {
			$param .= '&search_date_endmonth=' . urlencode((string) ($search_date_endmonth));
		}
		if ($search_date_endyear) {
			$param .= '&search_date_endyear=' . urlencode((string) ($search_date_endyear));
		}
		if ($search_timespent_starthour) {
			$param .= '&search_timespent_duration_starthour=' . urlencode((string) ($search_timespent_starthour));
		}
		if ($search_timespent_startmin) {
			$param .= '&search_timespent_duration_startmin=' . urlencode((string) ($search_timespent_startmin));
		}
		if ($search_timespent_endhour) {
			$param .= '&search_timespent_duration_endhour=' . urlencode((string) ($search_timespent_endhour));
		}
		if ($search_timespent_endmin) {
			$param .= '&search_timespent_duration_endmin=' . urlencode((string) ($search_timespent_endmin));
		}

		/*
		 // Add $param from extra fields
		 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
		 */
		if ($id) {
			$param .= '&id=' . urlencode((string) ($id));
		}
		if ($projectid) {
			$param .= '&projectid=' . urlencode((string) ($projectid));
		}
		if ($withproject) {
			$param .= '&withproject=' . urlencode((string) ($withproject));
		}
		// Add $param from hooks
		$parameters = array('param' => &$param);
		$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$param .= $hookmanager->resPrint;

		print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
		if ($optioncss != '') {
			print '<input type="hidden" name="optioncss" value="' . $optioncss . '">';
		}
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
		if ($action == 'editline') {
			print '<input type="hidden" name="action" value="updateline">';
		} elseif ($action == 'splitline') {
			print '<input type="hidden" name="action" value="updatesplitline">';
		} elseif ($action == 'createtime' && $user->hasRight('projet', 'time')) {
			print '<input type="hidden" name="action" value="addtimespent">';
		} elseif ($massaction == 'generateinvoice' && $user->hasRight('facture', 'creer')) {
			print '<input type="hidden" name="action" value="confirm_generateinvoice">';
		} elseif ($massaction == 'generateinter' && $user->hasRight('ficheinter', 'creer')) {
			print '<input type="hidden" name="action" value="confirm_generateinter">';
		} else {
			print '<input type="hidden" name="action" value="list">';
		}
		print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
		print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';

		print '<input type="hidden" name="id" value="' . $id . '">';
		print '<input type="hidden" name="projectid" value="' . $projectidforalltimes . '">';
		print '<input type="hidden" name="withproject" value="' . $withproject . '">';
		print '<input type="hidden" name="tab" value="' . $tab . '">';
		print '<input type="hidden" name="page_y" value="">';

		// Form to convert time spent into invoice
		if ($massaction == 'generateinvoice') {
			if (!empty($projectstatic->thirdparty) && $projectstatic->thirdparty->id > 0) {
				print '<table class="noborder centerpercent">';
				print '<tr>';
				print '<td class="titlefield">';
				print $langs->trans('DateInvoice');
				print '</td>';
				print '<td>';
				print $form->selectDate('', '', 0, 0, 0, '', 1, 1);
				print '</td>';
				print '</tr>';

				print '<tr>';
				print '<td>';
				print $langs->trans('Mode');
				print '</td>';
				print '<td>';
				$tmparray = array(
					'onelineperuser' => 'OneLinePerUser',
					'onelinepertask' => 'OneLinePerTask',
					'onelineperperiod' => 'OneLinePerTimeSpentLine',
				);
				print $form->selectarray('generateinvoicemode', $tmparray, 'onelineperuser', 0, 0, 0, '', 1);
				print "\n" . '<script type="text/javascript">';
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
				print '</script>' . "\n";
				print '<span style="display:none" id="detail_time_duration"><input type="checkbox" value="detail" name="detail_time_duration"/>' . $langs->trans('AddDetailDateAndDuration') . '</span>';
				print '</td>';
				print '</tr>';

				if (isModEnabled("service")) {
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
				print $form->selectInvoice($projectstatic->thirdparty->id, '', 'invoiceid', 24, 0, $langs->trans('NewInvoice'), 1, 0, 0, 'maxwidth500', '', 'all');
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td class="titlefield">';
				print $langs->trans("CustomerRelativeDiscount");
				print '</td>';
				print '<td>';
				print '<input type="text" size="5" name="remiseproject" value="'.$projectstatic->thirdparty->remise_percent.'">%';
				print '</td>';
				print '</tr>';
				print '<tr class="newinvoicedetail">';
				print '<td class="titlefield">';
				print $langs->trans("PaymentConditions");
				print '</td>';
				print '<td>';
				print $form->getSelectConditionsPaiements($projectstatic->thirdparty->cond_reglement_id, 'condidproject');
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
				print '<input type="submit" class="button" id="createbills" name="createbills" value="' . $langs->trans('GenerateBill') . '">  ';
				print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
				print '</div>';
				print '<br>';
			} else {
				print '<div class="warning">' . $langs->trans("ThirdPartyRequiredToGenerateInvoice") . '</div>';
				print '<div class="center">';
				print '<input type="submit" class="button button-cancel" id="cancel" name="cancel" value="' . $langs->trans("Cancel") . '">';
				print '</div>';
				$massaction = '';
			}
		} elseif ($massaction == 'generateinter') {
			// Form to convert time spent into invoice
			print '<input type="hidden" name="massaction" value="confirm_createinter">';

			if (!empty($projectstatic->thirdparty) && $projectstatic->thirdparty->id > 0) {
				print '<br>';
				print '<table class="noborder centpercent">';
				print '<tr>';
				print '<td class="titlefield">';
				print img_picto('', 'intervention', 'class="pictofixedwidth"') . $langs->trans('InterToUse');
				print '</td>';
				print '<td>';
				$forminter = new FormIntervention($db);
				print $forminter->select_interventions($projectstatic->thirdparty->id, '', 'interid', 24, $langs->trans('NewInter'), true);
				print '</td>';
				print '</tr>';
				print '</table>';

				print '<div class="center">';
				print '<input type="submit" class="button" id="createinter" name="createinter" value="' . $langs->trans('GenerateInter') . '">  ';
				print '<input type="submit" class="button" id="cancel" name="cancel" value="' . $langs->trans('Cancel') . '">';
				print '</div>';
				print '<br>';
			} else {
				print '<div class="warning">' . $langs->trans("ThirdPartyRequiredToGenerateIntervention") . '</div>';
				print '<div class="center">';
				print '<input type="submit" class="button" id="cancel" name="cancel" value="' . $langs->trans('Cancel') . '">';
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

		$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields

		$sql = "SELECT t.rowid, t.fk_element, t.element_date, t.element_datehour, t.element_date_withhour, t.element_duration, t.fk_user, t.note, t.thm,";
		$sql .= " t.fk_product,";
		$sql .= " pt.ref, pt.label, pt.fk_projet,";
		$sql .= " u.lastname, u.firstname, u.login, u.photo, u.gender, u.statut as user_status,";
		$sql .= " il.fk_facture as invoice_id, inv.fk_statut,";
		$sql .= " p.fk_soc,s.name_alias,";
		$sql .= " t.invoice_line_id,";
		$sql .= " pt.billable";
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql = preg_replace('/,\s*$/', '', $sql);

		$sqlfields = $sql; // $sql fields to remove for count total

		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facturedet as il ON il.rowid = t.invoice_line_id";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as inv ON inv.rowid = il.fk_facture";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as prod ON prod.rowid = t.fk_product";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."projet_task as pt ON pt.rowid = t.fk_element";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."projet as p ON p.rowid = pt.fk_projet";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."user as u ON t.fk_user = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";

		// Add table from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= " WHERE elementtype = 'task'";
		$sql .= " AND p.entity IN (".getEntity('project').")";
		if (!$user->hasRight('projet', 'all', 'lire')) {
			// Get list of project id allowed to user (in a string list separated by comma)
			// TODO This may generate performance trouble when list of project is very large. Solution can be to complete $filterproj with filters on project.
			$filterproj = '';
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $user->socid > 0 ? $user->socid : 0, $filterproj);
			$sql .= " AND p.rowid IN (".$db->sanitize($projectsListId).")"; // public and assigned to, or restricted to company for external users
		}
		if (empty($projectidforalltimes) && empty($allprojectforuser)) {
			// Limit on one task
			$sql .= " AND t.fk_element =".((int) $object->id);
		} elseif (!empty($projectidforalltimes)) {
			// Limit on one project
			$sql .= " AND pt.fk_projet IN (" . $db->sanitize($projectidforalltimes) . ")";
		} elseif (!empty($allprojectforuser)) {
			// Limit on on user
			if (empty($search_user)) {
				$search_user = $user->id;
			}
			if ($search_user > 0) {
				$sql .= " AND t.fk_user = " . ((int) $search_user);
			}
		}

		if ($search_note) {
			$sql .= natural_search('t.note', $search_note);
		}
		if ($search_task_ref) {
			$sql .= natural_search('pt.ref', $search_task_ref);
		}
		if (empty($arrayfields['s.name_alias']['checked']) && $search_company) {
			$sql .= natural_search(array("s.nom", "s.name_alias"), $search_company);
		} else {
			if ($search_company) {
				$sql .= natural_search('s.nom', $search_company);
			}
			if ($search_company_alias) {
				$sql .= natural_search('s.name_alias', $search_company_alias);
			}
		}
		if ($search_project_ref) {
			$sql .= natural_search('p.ref', $search_project_ref);
		}
		if ($search_project_label) {
			$sql .= natural_search('p.title', $search_project_label);
		}
		if ($search_task_label) {
			$sql .= natural_search('pt.label', $search_task_label);
		}
		if ($search_user > 0) {
			$sql .= natural_search('t.fk_user', $search_user, 2);
		}
		if (!empty($search_product_ref)) {
			$sql .= natural_search('prod.ref', $search_product_ref);
		}
		if ($search_valuebilled == '1') {
			$sql .= ' AND t.invoice_id > 0';
		}
		if ($search_valuebilled == '0') {
			$sql .= ' AND (t.invoice_id = 0 OR t.invoice_id IS NULL)';
		}

		if ($search_date_start) {
			$sql .= " AND t.element_date >= '".$db->idate($search_date_start)."'";
		}
		if ($search_date_end) {
			$sql .= " AND t.element_date <= '".$db->idate($search_date_end)."'";
		}

		if (!empty($arrayfields['t.element_duration']['checked'])) {
			if ($search_timespent_starthour || $search_timespent_startmin) {
				$timespent_duration_start = $search_timespent_starthour * 60 * 60; // We store duration in seconds
				$timespent_duration_start += ($search_timespent_startmin ? $search_timespent_startmin : 0) * 60; // We store duration in seconds
				$sql .= " AND t.element_duration >= " . $timespent_duration_start;
			}

			if ($search_timespent_endhour || $search_timespent_endmin) {
				$timespent_duration_end = $search_timespent_endhour * 60 * 60; // We store duration in seconds
				$timespent_duration_end += ($search_timespent_endmin ? $search_timespent_endmin : 0) * 60; // We store duration in seconds
				$sql .= " AND t.element_duration <= " . $timespent_duration_end;
			}
		}

		$sql .= dolSqlDateFilter('t.element_datehour', $search_day, $search_month, $search_year);

		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;

		// Count total nb of records
		$nbtotalofrecords = '';
		if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
			/* The fast and low memory method to get and count full list converts the sql into a sql count */
			$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
			$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
			$resql = $db->query($sqlforcount);
			if ($resql) {
				$objforcount = $db->fetch_object($resql);
				$nbtotalofrecords = $objforcount->nbtotalofrecords;
			} else {
				dol_print_error($db);
			}

			if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
				$page = 0;
				$offset = 0;
			}
			$db->free($resql);
		}

		// Complete request and execute it with limit
		$sql .= $db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $db->plimit($limit + 1, $offset);
		}

		$resql = $db->query($sql);
		if (!$resql) {
			dol_print_error($db);
			exit;
		}

		$num = $db->num_rows($resql);

		if ($num >= 0) {
			if (!empty($projectidforalltimes)) {
				print '<!-- List of time spent for project -->' . "\n";

				$title = $langs->trans("ListTaskTimeUserProject");

				print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'clock', 0, $linktocreatetime, '', $limit, 0, 0, 1);
			} else {
				print '<!-- List of time spent -->' . "\n";

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
		if ($action == 'createtime' && $user->hasRight('projet', 'time')) {
			print '<!-- table to add time spent -->' . "\n";
			if (!empty($id)) {
				print '<input type="hidden" name="taskid" value="' . $id . '">';
			}

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table class="noborder nohover centpercent">';

			print '<tr class="liste_titre">';
			print '<td>' . $langs->trans("Date") . '</td>';
			if (!empty($allprojectforuser)) {
				print '<td>' . $langs->trans("Project") . '</td>';
			}
			if (empty($id)) {
				print '<td>' . $langs->trans("Task") . '</td>';
			}
			print '<td>' . $langs->trans("By") . '</td>';
			print '<td>' . $langs->trans("Note") . '</td>';
			print '<td>' . $langs->trans("NewTimeSpent") . '</td>';
			print '<td>' . $langs->trans("ProgressDeclared") . '</td>';
			if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
				print '<td></td>';

				if (isModEnabled("service") && !empty($projectstatic->thirdparty) && $projectstatic->thirdparty->id > 0 && $projectstatic->usage_bill_time) {
					print '<td>'.$langs->trans("Product").'</td>';
				}
			}
			// Hook fields
			$parameters = array('mode' => 'create');
			$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
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
				$nboftasks = $formproject->selectTasks(-1, GETPOSTINT('taskid'), 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth300', $projectstatic->id, 'progress');
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
				print $form->select_dolusers((GETPOSTINT('userid') ? GETPOSTINT('userid') : $userid), 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 0, $langs->trans("ResourceNotAssignedToProject"), 'minwidth150imp maxwidth200');
			} else {
				if ($nboftasks) {
					print img_error($langs->trans('FirstAddRessourceToAllocateTime')) . ' ' . $langs->trans('FirstAddRessourceToAllocateTime');
				}
			}
			print '</td>';

			// Note
			print '<td>';
			print '<textarea name="timespent_note" class="maxwidth100onsmartphone" rows="' . ROWS_2 . '">' . (GETPOST('timespent_note') ? GETPOST('timespent_note') : '') . '</textarea>';
			print '</td>';

			// Duration - Time spent
			print '<td class="nowraponall">';
			$durationtouse = (GETPOST('timespent_duration') ? GETPOST('timespent_duration') : '');
			if (GETPOSTISSET('timespent_durationhour') || GETPOSTISSET('timespent_durationmin')) {
				$durationtouse = ((int) GETPOST('timespent_durationhour') * 3600 + (int) GETPOST('timespent_durationmin') * 60);
			}
			print $form->select_duration('timespent_duration', $durationtouse, 0, 'text');
			print '</td>';

			// Progress declared
			print '<td class="nowrap">';
			print $formother->select_percent(GETPOST('progress') ? GETPOST('progress') : $object->progress, 'progress', 0, 5, 0, 100, 1);
			print '</td>';

			// Invoiced
			if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
				print '<td>';
				print '</td>';

				if (isModEnabled("service") && !empty($projectstatic->thirdparty) && $projectstatic->thirdparty->id > 0 && $projectstatic->usage_bill_time) {
					print '<td class="nowraponall">';
					print img_picto('', 'service');
					print $form->select_produits((GETPOSTISSET('fk_product') ? GETPOSTINT("fk_product") : ''), 'fk_product', '1', 0, $projectstatic->thirdparty->price_level, 1, 2, '', 1, array(), $projectstatic->thirdparty->id, 'None', 0, 'maxwidth150', 0, '', null, 1);
					print '</td>';
				}
			}

			// Fields from hook
			$parameters = array('mode' => 'create');
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			print '<td class="center">';
			$form->buttonsSaveCancel();
			print '<input type="submit" name="save" class="button buttongen smallpaddingimp marginleftonly margintoponlyshort marginbottomonlyshort button-add reposition" value="'.$langs->trans("Add").'">';
			print '<input type="submit" name="cancel" class="button buttongen smallpaddingimp marginleftonly margintoponlyshort marginbottomonlyshort button-cancel" value="'.$langs->trans("Cancel").'">';
			print '</td></tr>';

			print '</table>';
			print '</div>';

			print '<br>';
		}

		$moreforfilter = '';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
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
		$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
		$selectedfields .= (is_array($arrayofmassactions) && count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

		print '<div class="div-table-responsive">';
		print '<table class="tagtable nobottomiftotal liste' . ($moreforfilter ? " listwithfilterbefore" : "") . '">' . "\n";

		// Fields title search
		// --------------------------------------------------------------------
		print '<tr class="liste_titre_filter">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre center">';
			$searchpicto = $form->showFilterButtons('left');
			print $searchpicto;
			print '</td>';
		}
		// Date
		if (!empty($arrayfields['t.element_date']['checked'])) {
			print '<td class="liste_titre left">';
			print '<div class="nowrapfordate">';
			print $form->selectDate($search_date_start ? $search_date_start : -1, 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
			print '</div>';
			print '<div class="nowrapfordate">';
			print $form->selectDate($search_date_end ? $search_date_end : -1, 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
			print '</div>';
			print '</td>';
		}
		// Thirdparty
		if (!empty($arrayfields['p.fk_soc']['checked'])) {
			print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="$search_company" value="' . dol_escape_htmltag($search_company) . '"></td>';
		}

		// Thirdparty alias
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="$search_company_alias" value="' . dol_escape_htmltag($search_company_alias) . '"></td>';
		}

		if (!empty($allprojectforuser)) {
			if (!empty($arrayfields['p.project_ref']['checked'])) {
				print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="$search_project_ref" value="' . dol_escape_htmltag($search_project_ref) . '"></td>';
			}
			if (!empty($arrayfields['p.project_label']['checked'])) {
				print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="$search_project_label" value="' . dol_escape_htmltag($search_project_label) . '"></td>';
			}
		}
		// Task
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
			if (!empty($arrayfields['t.element_ref']['checked'])) {
				print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'"></td>';
			}
			if (!empty($arrayfields['t.element_label']['checked'])) {
				print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'"></td>';
			}
		}
		// Author
		if (!empty($arrayfields['author']['checked'])) {
			print '<td class="liste_titre">'.$form->select_dolusers(($search_user > 0 ? $search_user : -1), 'search_user', 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth125').'</td>';
		}
		// Note
		if (!empty($arrayfields['t.note']['checked'])) {
			print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_note" value="' . dol_escape_htmltag($search_note) . '"></td>';
		}
		// Duration
		if (!empty($arrayfields['t.element_duration']['checked'])) {
			// Duration - Time spent
			print '<td class="liste_titre right">';

			$durationtouse_start = '';
			if ($search_timespent_starthour || $search_timespent_startmin) {
				$durationtouse_start = ($search_timespent_starthour * 3600 + $search_timespent_startmin * 60);
			}
			print '<div class="nowraponall">' . $langs->trans('from') . ' ';
			print $form->select_duration('search_timespent_duration_start', $durationtouse_start, 0, 'text', 0, 1);
			print '</div>';

			$durationtouse_end = '';
			if ($search_timespent_endhour || $search_timespent_endmin) {
				$durationtouse_end = ($search_timespent_endhour * 3600 + $search_timespent_endmin * 60);
			}
			print '<div class="nowraponall">' . $langs->trans('to') . ' ';
			print $form->select_duration('search_timespent_duration_end', $durationtouse_end, 0, 'text', 0, 1);
			print '</div>';

			print '</td>';
		}
		// Product
		if (!empty($arrayfields['t.fk_product']['checked'])) {
			print '<td class="liste_titre right"></td>';
		}
		// Value in main currency
		if (!empty($arrayfields['value']['checked'])) {
			print '<td class="liste_titre"></td>';
		}
		// Value billed
		if (!empty($arrayfields['valuebilled']['checked'])) {
			print '<td class="liste_titre center">' . $form->selectyesno('search_valuebilled', $search_valuebilled, 1, false, 1) . '</td>';
		}

		/*
		 // Extra fields
		 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
		 */
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields);
		$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre center">';
			$searchpicto = $form->showFilterButtons();
			print $searchpicto;
			print '</td>';
		}
		print '</tr>' . "\n";


		$totalarray = array();
		$totalarray['nbfield'] = 0;

		// Fields title label
		// --------------------------------------------------------------------
		print '<tr class="liste_titre">';
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['t.element_date']['checked'])) {
			print_liste_field_titre($arrayfields['t.element_date']['label'], $_SERVER['PHP_SELF'], 't.element_date,t.element_datehour,t.rowid', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['p.fk_soc']['checked'])) {
			print_liste_field_titre($arrayfields['p.fk_soc']['label'], $_SERVER['PHP_SELF'], 't.element_date,t.element_datehour,t.rowid', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER['PHP_SELF'], 's.name_alias', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		if (!empty($allprojectforuser)) {
			if (!empty($arrayfields['p.project_ref']['checked'])) {
				print_liste_field_titre("Project", $_SERVER['PHP_SELF'], 'p.ref', '', $param, '', $sortfield, $sortorder);
				$totalarray['nbfield']++;
			}
			if (!empty($arrayfields['p.project_label']['checked'])) {
				print_liste_field_titre("ProjectLabel", $_SERVER['PHP_SELF'], 'p.title', '', $param, '', $sortfield, $sortorder);
				$totalarray['nbfield']++;
			}
		}
		if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
			if (!empty($arrayfields['t.element_ref']['checked'])) {
				print_liste_field_titre($arrayfields['t.element_ref']['label'], $_SERVER['PHP_SELF'], 'pt.ref', '', $param, '', $sortfield, $sortorder);
				$totalarray['nbfield']++;
			}
			if (!empty($arrayfields['t.element_label']['checked'])) {
				print_liste_field_titre($arrayfields['t.element_label']['label'], $_SERVER['PHP_SELF'], 'pt.label', '', $param, '', $sortfield, $sortorder);
				$totalarray['nbfield']++;
			}
		}
		if (!empty($arrayfields['author']['checked'])) {
			print_liste_field_titre($arrayfields['author']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['t.note']['checked'])) {
			print_liste_field_titre($arrayfields['t.note']['label'], $_SERVER['PHP_SELF'], 't.note', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['t.element_duration']['checked'])) {
			print_liste_field_titre($arrayfields['t.element_duration']['label'], $_SERVER['PHP_SELF'], 't.element_duration', '', $param, '', $sortfield, $sortorder, 'right ');
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['t.fk_product']['checked'])) {
			print_liste_field_titre($arrayfields['t.fk_product']['label'], $_SERVER['PHP_SELF'], 't.fk_product', '', $param, '', $sortfield, $sortorder);
			$totalarray['nbfield']++;
		}

		if (!empty($arrayfields['value']['checked'])) {
			print_liste_field_titre($arrayfields['value']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'right ');
			$totalarray['nbfield']++;
		}
		if (!empty($arrayfields['valuebilled']['checked'])) {
			print_liste_field_titre($arrayfields['valuebilled']['label'], $_SERVER['PHP_SELF'], 'il.total_ht', '', $param, '', $sortfield, $sortorder, 'center ', $langs->trans("SelectLinesOfTimeSpentToInvoice"));
			$totalarray['nbfield']++;
		}
		/*
		 // Extra fields
		 include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
		 */
		// Hook fields
		$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'width="80"', $sortfield, $sortorder, 'center maxwidthsearch ');
			$totalarray['nbfield']++;
		}
		print "</tr>\n";

		$tasktmp = new Task($db);
		$tmpinvoice = new Facture($db);

		if ($page) {
			$param .= '&page='.((int) $page);
		}
		$param .= '&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder);

		$i = 0;

		$savnbfield = $totalarray['nbfield'];
		$totalarray = array();
		$totalarray['nbfield'] = 0;
		//$imaxinloop = ($limit ? min($num, $limit) : $num);
		foreach ($tasks as $task_time) {
			if ($i >= $limit) {
				break;
			}

			$date1 = $db->jdate($task_time->element_date);
			$date2 = $db->jdate($task_time->element_datehour);

			// Show here line of result
			$j = 0;
			print '<tr data-rowid="'.$task_time->rowid.'" class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center nowraponall">';
				if (($action == 'editline' || $action == 'splitline') && GETPOSTINT('lineid') == $task_time->rowid) {
					print '<input type="hidden" name="lineid" value="' . GETPOSTINT('lineid') . '">';
					print '<input type="submit" class="button buttongen smallpaddingimp margintoponlyshort marginbottomonlyshort button-save" name="save" value="'.$langs->trans("Save").'">';
					print '<br>';
					print '<input type="submit" class="button buttongen smallpaddingimp margintoponlyshort marginbottomonlyshort button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				} elseif ($user->hasRight('projet', 'time') || $user->hasRight('projet', 'all', 'creer')) {	 // Read project and enter time consumed on assigned tasks
					if (in_array($task_time->fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
						print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?'.($withproject ? 'id='.$task_time->fk_element : '').'&action=editline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
						print img_edit('default', 0, 'class="pictofixedwidth paddingleft"');
						print '</a>';

						if (getDolGlobalString('PROJECT_ALLOW_SPLIT_TIMESPENT')) {
							print '<a class="reposition editfielda paddingleft" href="' . $_SERVER["PHP_SELF"] . '?action=splitline&token=' . newToken() . '&lineid=' . $task_time->rowid . $param . ((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '') . '">';
							print img_split('', 'class="pictofixedwidth paddingleft"');
							print '</a>';
						}

						print '<a class="reposition paddingleft" href="'.$_SERVER["PHP_SELF"].'?'.($withproject ? 'id='.$task_time->fk_element : '').'&action=deleteline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
						print img_delete('default', 'class="pictodelete paddingleft"');
						print '</a>';

						if ($massactionbutton || $massaction) {    // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
							$selected = 0;
							if (in_array($task_time->rowid, $arrayofselected)) {
								$selected = 1;
							}
							print '&nbsp;';
							print '<input id="cb' . $task_time->rowid . '" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="' . $task_time->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
						}
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Date
			if (!empty($arrayfields['t.element_date']['checked'])) {
				print '<td class="nowrap">';
				if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
					if (empty($task_time->element_date_withhour)) {
						print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 4, 3, 2, "timespent_date", 1, 0);
					} else {
						print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 2, 1, 2, "timespent_date", 1, 0);
					}
				} else {
					print dol_print_date(($date2 ? $date2 : $date1), ($task_time->element_date_withhour ? 'dayhour' : 'day'));
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Thirdparty
			if (!empty($arrayfields['p.fk_soc']['checked'])) {
				print '<td class="tdoverflowmax125">';
				if ($task_time->fk_soc > 0) {
					if (empty($conf->cache['thridparty'][$task_time->fk_soc])) {
						$tmpsociete = new Societe($db);
						$tmpsociete->fetch($task_time->fk_soc);
						$conf->cache['thridparty'][$task_time->fk_soc] = $tmpsociete;
					} else {
						$tmpsociete = $conf->cache['thridparty'][$task_time->fk_soc];
					}
					print $tmpsociete->getNomUrl(1, '', 100, 0, 1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Thirdparty alias
			if (!empty($arrayfields['s.name_alias']['checked'])) {
				if ($task_time->fk_soc > 0) {
					if (empty($conf->cache['thridparty'][$task_time->fk_soc])) {
						$tmpsociete = new Societe($db);
						$tmpsociete->fetch($task_time->fk_soc);
						$conf->cache['thridparty'][$task_time->fk_soc] = $tmpsociete;
					} else {
						$tmpsociete = $conf->cache['thridparty'][$task_time->fk_soc];
					}
					$valtoshow = $tmpsociete->name_alias;
				}
				print '<td class="nowrap tdoverflowmax150" title="'.dol_escape_htmltag($valtoshow).'">';
				print $valtoshow;
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Project ref & label
			if (!empty($allprojectforuser)) {
				if (!empty($arrayfields['p.project_ref']['checked'])) {
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
				if (!empty($arrayfields['p.project_label']['checked'])) {
					if (empty($conf->cache['project'][$task_time->fk_projet])) {
						$tmpproject = new Project($db);
						$tmpproject->fetch($task_time->fk_projet);
						$conf->cache['project'][$task_time->fk_projet] = $tmpproject;
					} else {
						$tmpproject = $conf->cache['project'][$task_time->fk_projet];
					}
					print '<td class="tdoverflowmax250" title="'.dol_escape_htmltag($tmpproject->title).'">';
					print dol_escape_htmltag($tmpproject->title);
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			}

			// Task ref
			if (!empty($arrayfields['t.element_ref']['checked'])) {
				if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {   // Not a dedicated task
					print '<td class="nowrap">';
					if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
						$formproject->selectTasks(-1, GETPOSTINT('taskid') ? GETPOSTINT('taskid') : $task_time->fk_element, 'taskid', 0, 0, 1, 1, 0, 0, 'maxwidth250', $projectstatic->id, '');
					} else {
						$tasktmp->id = $task_time->fk_element;
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
				print '<input type="hidden" name="taskid" value="' . $id . '">';
			}

			// Task label
			if (!empty($arrayfields['t.element_label']['checked'])) {
				if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
					print '<td class="tdoverflowmax250" title="'.dol_escape_htmltag($task_time->label).'">';
					print dol_escape_htmltag($task_time->label);
					print '</td>';
					if (!$i) {
						$totalarray['nbfield']++;
					}
				}
			}

			// By User
			if (!empty($arrayfields['author']['checked'])) {
				print '<td class="minwidth100 tdoverflowmax125">';
				if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
					// Here $object is task. TODO Add a cache
					if (empty($object->id)) {
						$object->fetch($task_time->fk_element);
					}
					$contactsoftask = $object->getListContactId('internal');
					if (!in_array($task_time->fk_user, $contactsoftask)) {
						$contactsoftask[] = $task_time->fk_user;
					}
					if (count($contactsoftask) > 0) {
						print img_object('', 'user', 'class="pictofixedwidth hideonsmartphone"');
						print $form->select_dolusers($task_time->fk_user, 'userid_line', 0, null, 0, '', $contactsoftask, '0', 0, 0, '', 0, '', 'minwidth100 maxwidth100');	// maxwidth must be lowed than minwidth of the td
					} else {
						print img_error($langs->trans('FirstAddRessourceToAllocateTime')) . $langs->trans('FirstAddRessourceToAllocateTime');
					}
				} else {
					$userstatic->id = $task_time->fk_user;
					$userstatic->lastname = $task_time->lastname;
					$userstatic->firstname = $task_time->firstname;
					$userstatic->photo = $task_time->photo;
					$userstatic->gender = $task_time->gender;
					$userstatic->status = $task_time->user_status;

					print $userstatic->getNomUrl(-1);
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Note
			if (!empty($arrayfields['t.note']['checked'])) {
				if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
					print '<td class="small">';
					print '<textarea name="timespent_note_line" width="95%" rows="' . ROWS_1 . '">' . dol_escape_htmltag($task_time->note, 0, 1) . '</textarea>';
					print '</td>';
				} else {
					print '<td class="small tdoverflowmax150 classfortooltip" title="'.dol_string_onlythesehtmltags(dol_htmlentitiesbr($task_time->note)).'">';
					print dolGetFirstLineOfText($task_time->note);
					print '</td>';
				}
				if (!$i) {
					$totalarray['nbfield']++;
				}
			} elseif ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
				print '<input type="hidden" name="timespent_note_line" value="' . dol_escape_htmltag($task_time->note, 0, 1) . '">';
			}

			// Time spent
			if (!empty($arrayfields['t.element_duration']['checked'])) {
				print '<td class="right nowraponall">';
				if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
					print '<input type="hidden" name="old_duration" value="'.$task_time->element_duration.'">';
					print $form->select_duration('new_duration', $task_time->element_duration, 0, 'text');
				} else {
					print convertSecondToTime($task_time->element_duration, 'allhourmin');
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 't.element_duration';
				}
				if (empty($totalarray['val']['t.element_duration'])) {
					$totalarray['val']['t.element_duration'] = $task_time->element_duration;
				} else {
					$totalarray['val']['t.element_duration'] += $task_time->element_duration;
				}
				if (!$i) {
					$totalarray['totaldurationfield'] = $totalarray['nbfield'];
				}
				if (empty($totalarray['totalduration'])) {
					$totalarray['totalduration'] = $task_time->element_duration;
				} else {
					$totalarray['totalduration'] += $task_time->element_duration;
				}
			}

			// Product
			if (!empty($arrayfields['t.fk_product']['checked'])) {
				print '<td class="nowraponall">';
				if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
					print img_picto('', 'service');
					print $form->select_produits($task_time->fk_product, 'fk_product', '1', 0, $projectstatic->thirdparty->price_level, 1, 2, '', 1, array(), $projectstatic->thirdparty->id, 'None', 0, 'maxwidth500', 0, '', null, 1);
				} elseif (!empty($task_time->fk_product)) {
					$product = new Product($db);
					$resultFetch = $product->fetch($task_time->fk_product);
					if ($resultFetch < 0) {
						setEventMessages($product->error, $product->errors, 'errors');
					} else {
						print $product->getNomUrl(1);
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			// Value spent
			if (!empty($arrayfields['value']['checked'])) {
				$langs->load("salaries");
				$value = price2num($task_time->thm * $task_time->element_duration / 3600, 'MT', 1);

				print '<td class="nowraponall right">';
				print '<span class="amount" title="' . $langs->trans("THM") . ': ' . price($task_time->thm) . '">';
				print price($value, 1, $langs, 1, -1, -1, $conf->currency);
				print '</span>';
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
				if (!$i) {
					$totalarray['pos'][$totalarray['nbfield']] = 'value';
				}
				if (empty($totalarray['val']['value'])) {
					$totalarray['val']['value'] = $value;
				} else {
					$totalarray['val']['value'] += $value;
				}
				if (!$i) {
					$totalarray['totalvaluefield'] = $totalarray['nbfield'];
				}
				if (empty($totalarray['totalvalue'])) {
					$totalarray['totalvalue'] = $value;
				} else {
					$totalarray['totalvalue'] += $value;
				}
			}

			// Invoiced
			$invoiced = false;
			if (!empty($arrayfields['valuebilled']['checked'])) {
				print '<td class="center">'; // invoice_id and invoice_line_id
				if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
					if ($projectstatic->usage_bill_time) {
						if ($task_time->invoice_id) {
							$result = $tmpinvoice->fetch($task_time->invoice_id);
							if ($result > 0) {
								if ($action == 'editline' && GETPOSTINT('lineid') == $task_time->rowid) {
									print $formproject->selectInvoiceAndLine($task_time->invoice_id, $task_time->invoice_line_id, 'invoiceid', 'invoicelineid', 'maxwidth500', array('p.rowid' => $projectstatic->id));
								} else {
									print $tmpinvoice->getNomUrl(1);
									if (!empty($task_time->invoice_line_id)) {
										$invoiceLine = new FactureLigne($db);
										$invoiceLine->fetch($task_time->invoice_line_id);
										if (!empty($invoiceLine->id)) {
											print '<br>'.$langs->trans('Qty').':'.$invoiceLine->qty;
											print ' '.$langs->trans('TotalHT').':'.price($invoiceLine->total_ht);
										}
									}
								}
							}
							$invoiced = true;
						} else {
							if ( intval($task_time->billable) == 1) {
								print $langs->trans("No");
							} else {
								print $langs->trans("Disabled");
							}
						}
					} else {
						print '<span class="opacitymedium">' . $langs->trans("NA") . '</span>';
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
			$parameters = array('arrayfields' => $arrayfields, 'obj' => $task_time, 'i' => $i, 'totalarray' => &$totalarray);
			$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="center nowraponall">';
				if (($action == 'editline' || $action == 'splitline') && GETPOSTINT('lineid') == $task_time->rowid) {
					print '<input type="hidden" name="lineid" value="'.GETPOSTINT('lineid').'">';
					print '<input type="submit" class="button buttongen smallpaddingimp margintoponlyshort marginbottomonlyshort button-save" name="save" value="'.$langs->trans("Save").'">';
					print '<br>';
					print '<input type="submit" class="button buttongen smallpaddingimp margintoponlyshort marginbottomonlyshort button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				} elseif ($user->hasRight('projet', 'time') || $user->hasRight('projet', 'all', 'creer')) {	 // Read project and enter time consumed on assigned tasks
					if (in_array($task_time->fk_user, $childids) || $user->hasRight('projet', 'all', 'creer')) {
						print '<a class="reposition editfielda" href="'.$_SERVER["PHP_SELF"].'?'.($withproject ? 'id='.$task_time->fk_element : '').'&action=editline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
						print img_edit('default', 0, 'class="pictofixedwidth paddingleft"');
						print '</a>';

						if (getDolGlobalString('PROJECT_ALLOW_SPLIT_TIMESPENT')) {
							print '<a class="reposition editfielda paddingleft" href="' . $_SERVER["PHP_SELF"] . '?action=splitline&token=' . newToken() . '&lineid=' . $task_time->rowid . $param . ((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '') . '">';
							print img_split('', 'class="pictofixedwidth paddingleft"');
							print '</a>';
						}

						print '<a class="reposition paddingleft" href="'.$_SERVER["PHP_SELF"].'?'.($withproject ? 'id='.$task_time->fk_element : '').'&action=deleteline&token='.newToken().'&lineid='.$task_time->rowid.$param.((empty($id) || $tab == 'timespent') ? '&tab=timespent' : '').'">';
						print img_delete('default', 'class="pictodelete paddingleft"');
						print '</a>';

						if ($massactionbutton || $massaction) {    // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
							$selected = 0;
							if (in_array($task_time->rowid, $arrayofselected)) {
								$selected = 1;
							}
							print '&nbsp;';
							// Disable select if task not billable or already invoiced
							$disabled = (intval($task_time->billable) !=1 || $invoiced);
							$ctrl = '<input '.($disabled?'disabled':'').' id="cb' . $task_time->rowid . '" class="flat checkforselect marginleftonly" type="checkbox" name="toselect[]" value="' . $task_time->rowid . '"' . ($selected ? ' checked="checked"' : '') . '>';
							if ($disabled) {
								// If disabled, a dbl-click very close outside the control
								// will re-enable it, so that user is not blocked if needed.
								print '<span id="cbsp'. $task_time->rowid . '">'.$ctrl.'</span>';
								print '<script>$("#cbsp' . $task_time->rowid . '").dblclick(()=>{ $("#cb' . $task_time->rowid . '").removeAttr("disabled") })</script>';
							} else {
								print $ctrl;
							}
						}
					}
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}

			print "</tr>\n";


			// Add the lines for the split feature

			if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
				print '<!-- first line -->';
				print '<tr class="oddeven">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}

				// Date
				if (!empty($arrayfields['t.element_date']['checked'])) {
					print '<td class="nowrap">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
						if (empty($task_time->element_date_withhour)) {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 3, 3, 2, "timespent_date", 1, 0);
						} else {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline', 1, 1, 2, "timespent_date", 1, 0);
						}
					} else {
						print dol_print_date(($date2 ? $date2 : $date1), ($task_time->element_date_withhour ? 'dayhour' : 'day'));
					}
					print '</td>';
				}

				// Thirdparty
				if (!empty($arrayfields['p.fk_soc']['checked'])) {
					print '<td class="nowrap">';
					print '</td>';
				}

				// Thirdparty alias
				if (!empty($arrayfields['s.name_alias']['checked'])) {
					print '<td class="nowrap">';
					print '</td>';
				}

				// Project ref
				if (!empty($allprojectforuser)) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {    // Not a dedicated task
						print '<td class="nowrap">';
						print '</td>';
					}
				}

				// Task ref
				if (!empty($arrayfields['t.element_ref']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						$tasktmp->id = $task_time->fk_element;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
						print '</td>';
					}
				}

				// Task label
				if (!empty($arrayfields['t.element_label']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($task_time->label).'">';
						print dol_escape_htmltag($task_time->label);
						print '</td>';
					}
				}

				// User
				if (!empty($arrayfields['author']['checked'])) {
					print '<td class="nowraponall">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
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
							print img_error($langs->trans('FirstAddRessourceToAllocateTime')) . $langs->trans('FirstAddRessourceToAllocateTime');
						}
					} else {
						$userstatic->id = $task_time->fk_user;
						$userstatic->lastname = $task_time->lastname;
						$userstatic->firstname = $task_time->firstname;
						$userstatic->photo = $task_time->photo;
						$userstatic->status = $task_time->user_status;
						print $userstatic->getNomUrl(-1);
					}
					print '</td>';
				}

				// Note
				if (!empty($arrayfields['t.note']['checked'])) {
					print '<td class="tdoverflowmax300">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
						print '<textarea name="timespent_note_line" width="95%" rows="' . ROWS_1 . '">' . dol_escape_htmltag($task_time->note, 0, 1) . '</textarea>';
					} else {
						print dol_nl2br($task_time->note);
					}
					print '</td>';
				} elseif ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
					print '<input type="hidden" name="timespent_note_line" rows="' . ROWS_1 . '" value="' . dol_escape_htmltag($task_time->note, 0, 1) . '">';
				}

				// Time spent
				if (!empty($arrayfields['t.element_duration']['checked'])) {
					print '<td class="right">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
						print '<input type="hidden" name="old_duration" value="'.$task_time->element_duration.'">';
						print $form->select_duration('new_duration', $task_time->element_duration, 0, 'text');
					} else {
						print convertSecondToTime($task_time->element_duration, 'allhourmin');
					}
					print '</td>';
				}

				// Product
				if (!empty($arrayfields['t.fk_product']['checked'])) {
					print '<td class="nowraponall tdoverflowmax125">';
					print '</td>';
				}

				// Value spent
				if (!empty($arrayfields['value']['checked'])) {
					print '<td class="right">';
					print '<span class="amount">';
					$value = price2num($task_time->thm * $task_time->element_duration / 3600, 'MT', 1);
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
				$parameters = array('arrayfields' => $arrayfields, 'obj' => $task_time, 'mode' => 'split1');
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center nowraponall">';
					print '</td>';
				}

				print "</tr>\n";


				// Line for second dispatching

				print '<!-- second line --><tr class="oddeven">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center nowraponall">';
					print '</td>';
				}

				// Date
				if (!empty($arrayfields['t.element_date']['checked'])) {
					print '<td class="nowrap">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
						if (empty($task_time->element_date_withhour)) {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline_2', 3, 3, 2, "timespent_date", 1, 0);
						} else {
							print $form->selectDate(($date2 ? $date2 : $date1), 'timeline_2', 1, 1, 2, "timespent_date", 1, 0);
						}
					} else {
						print dol_print_date(($date2 ? $date2 : $date1), ($task_time->element_date_withhour ? 'dayhour' : 'day'));
					}
					print '</td>';
				}

				// Thirdparty
				if (!empty($arrayfields['p.fk_soc']['checked'])) {
					print '<td class="nowrap">';
					print '</td>';
				}

				// Thirdparty alias
				if (!empty($arrayfields['s.name_alias']['checked'])) {
					print '<td class="nowrap">';
					print '</td>';
				}

				// Project ref
				if (!empty($allprojectforuser)) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {    // Not a dedicated task
						print '<td class="nowrap">';
						print '</td>';
					}
				}

				// Task ref
				if (!empty($arrayfields['t.element_ref']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						$tasktmp->id = $task_time->fk_element;
						$tasktmp->ref = $task_time->ref;
						$tasktmp->label = $task_time->label;
						print $tasktmp->getNomUrl(1, 'withproject', 'time');
						print '</td>';
					}
				}

				// Task label
				if (!empty($arrayfields['t.element_label']['checked'])) {
					if ((empty($id) && empty($ref)) || !empty($projectidforalltimes)) {	// Not a dedicated task
						print '<td class="nowrap">';
						print dol_escape_htmltag($task_time->label);
						print '</td>';
					}
				}

				// User
				if (!empty($arrayfields['author']['checked'])) {
					print '<td class="nowraponall tdoverflowmax100">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
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
							print img_error($langs->trans('FirstAddRessourceToAllocateTime')) . $langs->trans('FirstAddRessourceToAllocateTime');
						}
					} else {
						$userstatic->id = $task_time->fk_user;
						$userstatic->lastname = $task_time->lastname;
						$userstatic->firstname = $task_time->firstname;
						$userstatic->photo = $task_time->photo;
						$userstatic->status = $task_time->user_status;
						print $userstatic->getNomUrl(-1);
					}
					print '</td>';
				}

				// Note
				if (!empty($arrayfields['t.note']['checked'])) {
					print '<td class="small tdoverflowmax300"">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
						print '<textarea name="timespent_note_line_2" width="95%" rows="' . ROWS_1 . '">' . dol_escape_htmltag($task_time->note, 0, 1) . '</textarea>';
					} else {
						print dol_nl2br($task_time->note);
					}
					print '</td>';
				} elseif ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
					print '<input type="hidden" name="timespent_note_line_2" value="' . dol_escape_htmltag($task_time->note, 0, 1) . '">';
				}

				// Time spent
				if (!empty($arrayfields['t.element_duration']['checked'])) {
					print '<td class="right">';
					if ($action == 'splitline' && GETPOSTINT('lineid') == $task_time->rowid) {
						print '<input type="hidden" name="old_duration_2" value="0">';
						print $form->select_duration('new_duration_2', 0, 0, 'text');
					} else {
						print convertSecondToTime($task_time->element_duration, 'allhourmin');
					}
					print '</td>';
				}

				// Product
				if (!empty($arrayfields['t.fk_product']['checked'])) {
					print '<td class="nowraponall tdoverflowmax125">';
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
				$parameters = array('arrayfields' => $arrayfields, 'obj' => $task_time, 'mode' => 'split2');
				$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center nowraponall">';
					print '</td>';
				}

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
						print '<td class="left">' . $langs->trans("Total") . '</td>';
					} else {
						print '<td class="left">'.$form->textwithpicto($langs->trans("Total"), $langs->trans("Totalforthispage")).'</td>';
					}
				} elseif (isset($totalarray['totaldurationfield']) && $totalarray['totaldurationfield'] == $i) {
					print '<td class="right">' . convertSecondToTime($totalarray['totalduration'], 'allhourmin') . '</td>';
				} elseif (isset($totalarray['totalvaluefield']) && $totalarray['totalvaluefield'] == $i) {
					print '<td class="right">' . price($totalarray['totalvalue']) . '</td>';
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
				if (!empty($value['checked'])) {
					$totalnboffields++;
				}
			}
			print '<tr class="oddeven"><td colspan="' . $totalnboffields . '">';
			print '<span class="opacitymedium">' . $langs->trans("None") . '</span>';
			print '</td></tr>';
		}

		$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
		$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print "</table>";
		print '</div>';
		print "</form>";
	}
}

// End of page
llxFooter();
$db->close();
