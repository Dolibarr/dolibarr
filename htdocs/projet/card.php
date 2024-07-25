<?php
/* Copyright (C) 2001-2005  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2023       Charlene Benke          <charlene@patas_monkey.com>
 * Copyright (C) 2023       Christian Foellmann     <christian@foellmann.de>
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 *	\file       htdocs/projet/card.php
 *	\ingroup    projet
 *	\brief      Project card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langsLoad = array('projects', 'companies');
if (isModEnabled('eventorganization')) {
	$langsLoad[] = 'eventorganization';
}

$langs->loadLangs($langsLoad);

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$backtopagejsfields = GETPOST('backtopagejsfields', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'aZ09');

$dol_openinpopup = 0;
if (!empty($backtopagejsfields)) {
	$tmpbacktopagejsfields = explode(':', $backtopagejsfields);
	$dol_openinpopup = $tmpbacktopagejsfields[0];
}

$status = GETPOSTINT('status');
$opp_status = GETPOSTINT('opp_status');
$opp_percent = price2num(GETPOST('opp_percent', 'alphanohtml'));
$objcanvas = GETPOST("objcanvas", "alphanohtml");
$comefromclone = GETPOST("comefromclone", "alphanohtml");
$date_start = dol_mktime(0, 0, 0, GETPOSTINT('projectstartmonth'), GETPOSTINT('projectstartday'), GETPOSTINT('projectstartyear'));
$date_end = dol_mktime(0, 0, 0, GETPOSTINT('projectendmonth'), GETPOSTINT('projectendday'), GETPOSTINT('projectendyear'));
$date_start_event = dol_mktime(GETPOSTINT('date_start_eventhour'), GETPOSTINT('date_start_eventmin'), GETPOSTINT('date_start_eventsec'), GETPOSTINT('date_start_eventmonth'), GETPOSTINT('date_start_eventday'), GETPOSTINT('date_start_eventyear'), 'tzuserrel');
$date_end_event = dol_mktime(GETPOSTINT('date_end_eventhour'), GETPOSTINT('date_end_eventmin'), GETPOSTINT('date_end_eventsec'), GETPOSTINT('date_end_eventmonth'), GETPOSTINT('date_end_eventday'), GETPOSTINT('date_end_eventyear'), 'tzuserrel');
$location = GETPOST('location', 'alphanohtml');
$fk_project = GETPOSTINT('fk_project');


$mine = GETPOST('mode') == 'mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('projectcard', 'globalcard'));

$object = new Project($db);
$extrafields = new ExtraFields($db);

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Can't use generic include because when creating a project, ref is defined and we don't want error if fetch fails from ref.
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref); // If we create project, ref may be defined into POST but record does not yet exists into database
	if ($ret > 0) {
		$object->fetch_thirdparty();
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$id = $object->id;
	}
}

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Security check
$socid = GETPOSTINT('socid');
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
restrictedArea($user, 'projet', $object->id, 'projet&project');

if ($id == '' && $ref == '' && ($action != "create" && $action != "add" && $action != "update" && !GETPOST("cancel"))) {
	accessforbidden();
}

$permissiontoadd = $user->hasRight('projet', 'creer');
$permissiontodelete = $user->hasRight('projet', 'supprimer');
$permissiondellink = $user->hasRight('projet', 'creer');	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

$parameters = array('id' => $socid, 'objcanvas' => $objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/projet/list.php';

	// Cancel
	if ($cancel) {
		if (GETPOST("comefromclone") == 1) {
			$result = $object->delete($user);
			if ($result > 0) {
				header("Location: index.php");
				exit;
			} else {
				dol_syslog($object->error, LOG_DEBUG);
				setEventMessages($langs->trans("CantRemoveProject", $langs->transnoentitiesnoconv("ProjectOverview")), null, 'errors');
			}
		}
	}

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/projet/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be 'include', not 'include_once'

	// Action setdraft object
	if ($action == 'confirm_setdraft' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setStatut($object::STATUS_DRAFT, null, '', 'PROJECT_MODIFY');
		if ($result >= 0) {
			// Nothing else done
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
		$action = '';

		// For backward compatibility
		$object->statut = $object::STATUS_DRAFT;	// this already set for $object->status by $object->setStatut()
	}

	// Action add
	if ($action == 'add' && $permissiontoadd) {
		$error = 0;
		if (!GETPOST('ref')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
			$error++;
		}
		if (!GETPOST('title')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ProjectLabel")), null, 'errors');
			$error++;
		}

		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			if (GETPOST('usage_opportunity') != '' && !(GETPOST('opp_status') > 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorOppStatusRequiredIfUsage"), null, 'errors');
			}
			if (GETPOST('opp_amount') != '' && !(GETPOST('opp_status') > 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorOppStatusRequiredIfAmount"), null, 'errors');
			}
		}

		// Create with status validated immediately
		if (getDolGlobalString('PROJECT_CREATE_NO_DRAFT') && !$error) {
			$status = Project::STATUS_VALIDATED;
		}

		if (!$error) {
			$error = 0;

			$db->begin();

			$object->ref                  = GETPOST('ref', 'alphanohtml');
			$object->fk_project           = GETPOSTINT('fk_project');
			$object->title                = GETPOST('title', 'alphanohtml');
			$object->socid                = GETPOSTINT('socid');
			$object->description          = GETPOST('description', 'restricthtml'); // Do not use 'alpha' here, we want field as it is
			$object->public               = GETPOST('public', 'alphanohtml');
			$object->opp_amount           = GETPOSTFLOAT('opp_amount');
			$object->budget_amount        = GETPOSTFLOAT('budget_amount');
			$object->date_c               = dol_now();
			$object->date_start           = $date_start;
			$object->date_end             = $date_end;
			$object->date_start_event     = $date_start_event;
			$object->date_end_event       = $date_end_event;
			$object->location             = $location;
			$object->status               = $status;
			$object->opp_status           = $opp_status;
			$object->opp_percent          = $opp_percent;
			$object->usage_opportunity    = (GETPOST('usage_opportunity', 'alpha') == 'on' ? 1 : 0);
			$object->usage_task           = (GETPOST('usage_task', 'alpha') == 'on' ? 1 : 0);
			$object->usage_bill_time      = (GETPOST('usage_bill_time', 'alpha') == 'on' ? 1 : 0);
			$object->usage_organize_event = (GETPOST('usage_organize_event', 'alpha') == 'on' ? 1 : 0);

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			$result = $object->create($user);
			if (!$error && $result > 0) {
				// Add myself as Owner Contact Selected in the form
				$typeofcontact = GETPOST('typeofcontact');
				$result = $object->add_contact($user->id, $typeofcontact, 'internal');

				// -3 means type not found (renamed, de-activated or deleted), so don't prevent creation if it has been the case
				if ($result == -3) {
					setEventMessage('ErrorPROJECTLEADERRoleMissingRestoreIt', 'errors');
					$error++;
				} elseif ($result < 0) {
					$langs->load("errors");
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			} else {
				$langs->load("errors");
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
			if (!$error && !empty($object->id) > 0) {
				// Category association
				$categories = GETPOST('categories', 'array');
				$result = $object->setCategories($categories);
				if ($result < 0) {
					$langs->load("errors");
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();

				if (!empty($backtopage)) {
					$backtopage = preg_replace('/--IDFORBACKTOPAGE--|__ID__/', (string) $object->id, $backtopage); // New method to autoselect project after a New on another form object creation
					$backtopage = $backtopage.'&projectid='.$object->id; // Old method
					header("Location: ".$backtopage);
					exit;
				} else {
					header("Location:card.php?id=".$object->id);
					exit;
				}
			} else {
				$db->rollback();
				unset($_POST["ref"]);
				$action = 'create';
			}
		} else {
			$action = 'create';
		}
	}

	if ($action == 'update' && empty(GETPOST('cancel')) && $permissiontoadd) {
		$error = 0;

		if (empty($ref)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
		}
		if (!GETPOST("title")) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ProjectLabel")), null, 'errors');
		}

		$db->begin();

		if (!$error) {
			$object->oldcopy = clone $object;

			$old_start_date = $object->date_start;

			$object->ref          = GETPOST('ref', 'alpha');
			$object->fk_project   = GETPOSTINT('fk_project');
			$object->title        = GETPOST('title', 'alphanohtml'); // Do not use 'alpha' here, we want field as it is
			$object->status       = GETPOSTINT('status');
			$object->socid        = GETPOSTINT('socid');
			$object->description  = GETPOST('description', 'restricthtml'); // Do not use 'alpha' here, we want field as it is
			$object->public       = GETPOST('public', 'alpha');
			$object->date_start   = (!GETPOST('projectstart')) ? '' : $date_start;
			$object->date_end     = (!GETPOST('projectend')) ? '' : $date_end;
			$object->date_start_event = (!GETPOST('date_start_event')) ? '' : $date_start_event;
			$object->date_end_event   = (!GETPOST('date_end_event')) ? '' : $date_end_event;
			$object->location     = $location;
			if (GETPOSTISSET('opp_amount')) {
				$object->opp_amount   = price2num(GETPOST('opp_amount', 'alpha'));
			}
			if (GETPOSTISSET('budget_amount')) {
				$object->budget_amount = price2num(GETPOST('budget_amount', 'alpha'));
			}
			if (GETPOSTISSET('opp_status')) {
				$object->opp_status   = $opp_status;
			}
			if (GETPOSTISSET('opp_percent')) {
				$object->opp_percent  = $opp_percent;
			}
			$object->usage_opportunity    = (GETPOST('usage_opportunity', 'alpha') == 'on' ? 1 : 0);
			$object->usage_task           = (GETPOST('usage_task', 'alpha') == 'on' ? 1 : 0);
			$object->usage_bill_time      = (GETPOST('usage_bill_time', 'alpha') == 'on' ? 1 : 0);
			$object->usage_organize_event = (GETPOST('usage_organize_event', 'alpha') == 'on' ? 1 : 0);

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			if ($ret < 0) {
				$error++;
			}
		}

		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			if ($object->opp_amount && ($object->opp_status <= 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorOppStatusRequiredIfAmount"), null, 'errors');
			}
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result < 0) {
				$error++;
				if ($result == -4) {
					setEventMessages($langs->trans("ErrorRefAlreadyExists"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			} else {
				// Category association
				$categories = GETPOST('categories', 'array');
				$result = $object->setCategories($categories);
				if ($result < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if (!$error) {
			if (GETPOST("reportdate") && ($object->date_start != $old_start_date)) {
				$result = $object->shiftTaskDate($old_start_date);
				if ($result < 0) {
					$error++;
					setEventMessages($langs->trans("ErrorShiftTaskDate").':'.$object->error, $object->errors, 'errors');
				}
			}
		}

		// Check if we must change status
		if (GETPOST('closeproject')) {
			$resclose = $object->setClose($user);
			if ($resclose < 0) {
				$error++;
				setEventMessages($langs->trans("FailedToCloseProject").':'.$object->error, $object->errors, 'errors');
			}
		}


		if ($error) {
			$db->rollback();
			$action = 'edit';
		} else {
			$db->commit();

			if (GETPOSTINT('socid') > 0) {
				$object->fetch_thirdparty(GETPOSTINT('socid'));
			} else {
				unset($object->thirdparty);
			}
		}
	}

	if ($action == 'set_opp_status' && $user->hasRight('projet', 'creer')) {
		$error = 0;
		if (GETPOSTISSET('opp_status')) {
			$object->opp_status   = $opp_status;
		}
		if (GETPOSTISSET('opp_percent')) {
			$object->opp_percent  = $opp_percent;
		}

		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			if ($object->opp_amount && ($object->opp_status <= 0)) {
				$error++;
				setEventMessages($langs->trans("ErrorOppStatusRequiredIfAmount"), null, 'errors');
			}
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result < 0) {
				$error++;
				if ($result == -4) {
					setEventMessages($langs->trans("ErrorRefAlreadyExists"), null, 'errors');
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if ($error) {
			$db->rollback();
			$action = 'edit';
		} else {
			$db->commit();
		}
	}

	// Build doc
	if ($action == 'builddoc' && $permissiontoadd) {
		// Save last template used to generate document
		if (GETPOST('model')) {
			$object->setDocModel($user, GETPOST('model', 'alpha'));
		}

		$outputlangs = $langs;
		if (GETPOST('lang_id', 'aZ09')) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
		}
		$result = $object->generateDocument($object->model_pdf, $outputlangs);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $permissiontoadd) {
		if ($object->id > 0) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

			$langs->load("other");
			$upload_dir = $conf->project->multidir_output[$object->entity];
			$file = $upload_dir.'/'.GETPOST('file');
			$ret = dol_delete_file($file, 0, 0, 0, $object);
			if ($ret) {
				setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
			}
			$action = '';
		}
	}


	if ($action == 'confirm_validate' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setValid($user);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_close' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setClose($user);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_reopen' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setValid($user);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
		$object->fetch($id);
		$result = $object->delete($user);
		if ($result > 0) {
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');

			if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
				$tmpurl = $_SESSION['pageforbacktolist']['project'];
				$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
				$urlback = $tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1';
			} else {
				$urlback = DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1';
			}

			header("Location: ".$urlback);
			exit;
		} else {
			dol_syslog($object->error, LOG_DEBUG);
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_clone' && $permissiontoadd && $confirm == 'yes') {
		$clone_contacts = GETPOST('clone_contacts') ? 1 : 0;
		$clone_tasks = GETPOST('clone_tasks') ? 1 : 0;
		$clone_project_files = GETPOST('clone_project_files') ? 1 : 0;
		$clone_task_files = GETPOST('clone_task_files') ? 1 : 0;
		$clone_notes = GETPOST('clone_notes') ? 1 : 0;
		$move_date = GETPOST('move_date') ? 1 : 0;
		$clone_thirdparty = GETPOSTINT('socid') ? GETPOSTINT('socid') : 0;

		$result = $object->createFromClone($user, $object->id, $clone_contacts, $clone_tasks, $clone_project_files, $clone_task_files, $clone_notes, $move_date, 0, $clone_thirdparty);
		if ($result <= 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			// Load new object
			$newobject = new Project($db);
			$newobject->fetch($result);
			$newobject->fetch_optionals();
			$newobject->fetch_thirdparty(); // Load new object
			$object = $newobject;
			$action = 'view';
			$comefromclone = true;

			setEventMessages($langs->trans("ProjectCreatedInDolibarr", $newobject->ref), "", 'mesgs');
			//var_dump($newobject); exit;
		}
	}

	// Actions to send emails
	$triggersendname = 'PROJECT_SENTBYMAIL';
	$paramname = 'id';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_PROJECT_TO'; // used to know the automatic BCC to add
	$trackid = 'proj'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);

$title = $langs->trans("Project").' - '.$object->ref.(!empty($object->thirdparty->name) ? ' - '.$object->thirdparty->name : '').(!empty($object->title) ? ' - '.$object->title : '');
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/projectnameonly/', getDolGlobalString('MAIN_HTML_TITLE'))) {
	$title = $object->ref.(!empty($object->thirdparty->name) ? ' - '.$object->thirdparty->name : '').(!empty($object->title) ? ' - '.$object->title : '');
}

$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos|DE:Modul_Projekte";

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-project page-card');

$titleboth = $langs->trans("LeadsOrProjects");
$titlenew = $langs->trans("NewLeadOrProject"); // Leads and opportunities by default
if (!getDolGlobalInt('PROJECT_USE_OPPORTUNITIES')) {
	$titleboth = $langs->trans("Projects");
	$titlenew = $langs->trans("NewProject");
}
if (getDolGlobalInt('PROJECT_USE_OPPORTUNITIES') == 2) { // 2 = leads only
	$titleboth = $langs->trans("Leads");
	$titlenew = $langs->trans("NewLead");
}

if ($action == 'create' && $user->hasRight('projet', 'creer')) {
	/*
	 * Create
	 */

	$thirdparty = new Societe($db);
	if ($socid > 0) {
		$thirdparty->fetch($socid);
	}

	print load_fiche_titre($titlenew, '', 'project');

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	print '<input type="hidden" name="backtopagejsfields" value="'.$backtopagejsfields.'">';
	print '<input type="hidden" name="dol_openinpopup" value="'.$dol_openinpopup.'">';

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldcreate">';

	$defaultref = '';
	$modele = !getDolGlobalString('PROJECT_ADDON') ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;

	// Search template files
	$file = '';
	$classname = '';
	$filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/project/".$modele.'.php', 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = $modele;
			break;
		}
	}

	if ($filefound) {
		$result = dol_include_once($reldir."core/modules/project/".$modele.'.php');
		$modProject = new $classname();

		$defaultref = $modProject->getNextValue($thirdparty, $object);
	}

	if (is_numeric($defaultref) && $defaultref <= 0) {
		$defaultref = '';
	}

	// Ref
	$suggestedref = (GETPOST("ref") ? GETPOST("ref") : $defaultref);
	print '<tr><td class="titlefieldcreate"><span class="fieldrequired">'.$langs->trans("Ref").'</span></td><td class><input class="maxwidth150onsmartphone" type="text" name="ref" value="'.dol_escape_htmltag($suggestedref).'">';
	if ($suggestedref) {
		print ' '.$form->textwithpicto('', $langs->trans("YouCanCompleteRef", $suggestedref));
	}
	print '</td></tr>';

	// Label
	print '<tr><td><span class="fieldrequired">'.$langs->trans("Label").'</span></td><td><input class="width500 maxwidth150onsmartphone" type="text" name="title" value="'.dol_escape_htmltag(GETPOST("title", 'alphanohtml')).'" autofocus></td></tr>';

	// Parent
	if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
		print '<tr><td>'.$langs->trans("Parent").'</td><td class="maxwidthonsmartphone">';
		print img_picto('', 'project', 'class="pictofixedwidth"');
		$formproject->select_projects(-1, '', 'fk_project', 64, 0, 1, 1, 0, 0, 0, '', 0, 0, '', '', '');
		print '</td></tr>';
	}

	// Usage (opp, task, bill time, ...)
	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
		print '<tr><td class="tdtop">';
		print $langs->trans("Usage");
		print '</td>';
		print '<td>';
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			print '<input type="checkbox" id="usage_opportunity" name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') ? ' checked="checked"' : '') : ' checked="checked"').'"> ';
			$htmltext = $langs->trans("ProjectFollowOpportunity");
			print '<label for="usage_opportunity">'.$form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext).'</label>';
			print '<script>';
			print '$( document ).ready(function() {
					jQuery("#usage_opportunity").change(function() {
						if (jQuery("#usage_opportunity").prop("checked")) {
							console.log("Show opportunities fields");
							jQuery(".classuseopportunity").show();
						} else {
							console.log("Hide opportunities fields "+jQuery("#usage_opportunity").prop("checked"));
							jQuery(".classuseopportunity").hide();
						}
					});
					';
			if (GETPOSTISSET('usage_opportunity') && !GETPOST('usage_opportunity')) {
				print 'jQuery(".classuseopportunity").hide();';
			}
			print '});';
			print '</script>';
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			print '<input type="checkbox" id="usage_task" name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') ? ' checked="checked"' : '') : ' checked="checked"').'"> ';
			$htmltext = $langs->trans("ProjectFollowTasks");
			print '<label for="usage_task">'.$form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext).'</label>';
			print '<script>';
			print '$( document ).ready(function() {
					jQuery("#usage_task").change(function() {
						if (jQuery("#usage_task").prop("checked")) {
							console.log("Show task fields");
							jQuery(".classusetask").show();
						} else {
							console.log("Hide tasks fields "+jQuery("#usage_task").prop("checked"));
							jQuery(".classusetask").hide();
						}
					});
					';
			if (GETPOSTISSET('usage_task') && !GETPOST('usage_task')) {
				print 'jQuery(".classusetask").hide();';
			}
			print '});';
			print '</script>';
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
			print '<input type="checkbox" id="usage_bill_time" name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') ? ' checked="checked"' : '') : '').'"> ';
			$htmltext = $langs->trans("ProjectBillTimeDescription");
			print '<label for="usage_bill_time">'.$form->textwithpicto($langs->trans("BillTime"), $htmltext).'</label>';
			print '<script>';
			print '$( document ).ready(function() {
					jQuery("#usage_bill_time").change(function() {
						if (jQuery("#usage_bill_time").prop("checked")) {
							console.log("Show bill time fields");
							jQuery(".classusebilltime").show();
						} else {
							console.log("Hide bill time fields "+jQuery("#usage_bill_time").prop("checked"));
							jQuery(".classusebilltime").hide();
						}
					});
					';
			if (GETPOSTISSET('usage_bill_time') && !GETPOST('usage_bill_time')) {
				print 'jQuery(".classusebilltime").hide();';
			}
			print '});';
			print '</script>';
			print '<br>';
		}
		if (isModEnabled('eventorganization')) {
			print '<input type="checkbox" id="usage_organize_event" name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') ? ' checked="checked"' : '') : '').'"> ';
			$htmltext = $langs->trans("EventOrganizationDescriptionLong");
			print '<label for="usage_organize_event">'.$form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext).'</label>';
			print '<script>';
			print '$( document ).ready(function() {
					jQuery("#usage_organize_event").change(function() {
						if (jQuery("#usage_organize_event").prop("checked")) {
							console.log("Show organize event fields");
							jQuery(".classuseorganizeevent").show();
						} else {
							console.log("Hide organize event fields "+jQuery("#usage_organize_event").prop("checked"));
							jQuery(".classuseorganizeevent").hide();
						}
					});
					';
			if (!GETPOST('usage_organize_event')) {
				print 'jQuery(".classuseorganizeevent").hide();';
			}
			print '});';
			print '</script>';
		}
		print '</td>';
		print '</tr>';
	}

	// Thirdparty
	if (isModEnabled('societe')) {
		print '<tr><td>';
		print(!getDolGlobalString('PROJECT_THIRDPARTY_REQUIRED') ? '' : '<span class="fieldrequired">');
		print $langs->trans("ThirdParty");
		print(!getDolGlobalString('PROJECT_THIRDPARTY_REQUIRED') ? '' : '</span>');
		print '</td><td class="maxwidthonsmartphone">';
		$filter = '';
		if (getDolGlobalString('PROJECT_FILTER_FOR_THIRDPARTY_LIST')) {
			$filter = getDolGlobalString('PROJECT_FILTER_FOR_THIRDPARTY_LIST');
		}
		$text = img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company(GETPOSTINT('socid'), 'socid', $filter, 'SelectThirdParty', 1, 0, array(), 0, 'minwidth300 widthcentpercentminusxx maxwidth500');
		if (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') && empty($conf->dol_use_jmobile)) {
			$texthelp = $langs->trans("IfNeedToUseOtherObjectKeepEmpty");
			print $form->textwithtooltip($text.' '.img_help(), $texthelp, 1);
		} else {
			print $text;
		}
		if (!GETPOSTISSET('backtopage')) {
			$url = '/societe/card.php?action=create&client=3&fournisseur=0&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create');
			$newbutton = '<span class="fa fa-plus-circle valignmiddle paddingleft" title="'.$langs->trans("AddThirdParty").'"></span>';
			// TODO @LDR Implement this
			if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
				$tmpbacktopagejsfields = 'addthirdparty:socid,search_socid';
				print dolButtonToOpenUrlInDialogPopup('addthirdparty', $langs->transnoentitiesnoconv('AddThirdParty'), $newbutton, $url, '', '', '', $tmpbacktopagejsfields);
			} else {
				print ' <a href="'.DOL_URL_ROOT.$url.'">'.$newbutton.'</a>';
			}
		}
		print '</td></tr>';
	}

	// Status
	if ($status != '') {
		print '<tr><td>'.$langs->trans("Status").'</td><td>';
		print '<input type="hidden" name="status" value="'.$status.'">';
		print $object->LibStatut($status, 4);
		print '</td></tr>';
	}

	// Visibility
	print '<tr><td>'.$langs->trans("Visibility").'</td><td class="maxwidthonsmartphone">';
	$array = array();
	if (!getDolGlobalString('PROJECT_DISABLE_PRIVATE_PROJECT')) {
		$array[0] = $langs->trans("PrivateProject");
	}
	if (!getDolGlobalString('PROJECT_DISABLE_PUBLIC_PROJECT')) {
		$array[1] = $langs->trans("SharedProject");
	}

	if (count($array) > 0) {
		print $form->selectarray('public', $array, GETPOST('public'), 0, 0, 0, '', 0, 0, 0, '', '', 1);
	} else {
		print '<input type="hidden" name="public" id="public" value="'.GETPOST('public').'">';

		if (GETPOST('public') == 0) {
			print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
			print $langs->trans("PrivateProject");
		} else {
			print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
			print $langs->trans("SharedProject");
		}
	}
	print '</td></tr>';

	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		// Opportunity status
		print '<tr class="classuseopportunity"><td><span class="fieldrequired">'.$langs->trans("OpportunityStatus").'</span></td>';
		print '<td class="maxwidthonsmartphone">';
		print $formproject->selectOpportunityStatus('opp_status', GETPOSTISSET('opp_status') ? GETPOST('opp_status') : $object->opp_status, 1, 0, 0, 0, '', 0, 1);

		// Opportunity probability
		print ' <input class="width50 right" type="text" id="opp_percent" name="opp_percent" title="'.dol_escape_htmltag($langs->trans("OpportunityProbability")).'" value="'.dol_escape_htmltag(GETPOSTISSET('opp_percent') ? GETPOST('opp_percent') : '').'"><span class="hideonsmartphone"> %</span>';
		print '<input type="hidden" name="opp_percent_not_set" id="opp_percent_not_set" value="'.dol_escape_htmltag(GETPOSTISSET('opp_percent') ? '0' : '1').'">';
		print '</td>';
		print '</tr>';

		// Opportunity amount
		print '<tr class="classuseopportunity"><td>'.$langs->trans("OpportunityAmount").'</td>';
		print '<td><input class="width75 right" type="text" name="opp_amount" value="'.dol_escape_htmltag(GETPOSTISSET('opp_amount') ? GETPOST('opp_amount') : '').'">';
		print ' '.$langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print '</tr>';
	}

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td>';
	print '<td><input class="width75 right" type="text" name="budget_amount" value="'.dol_escape_htmltag(GETPOSTISSET('budget_amount') ? GETPOST('budget_amount') : '').'">';
	print ' '.$langs->getCurrencySymbol($conf->currency);
	print '</td>';
	print '</tr>';

	// Date project
	print '<tr><td>'.$langs->trans("Date").(isModEnabled('eventorganization') ? ' <span class="classuseorganizeevent">('.$langs->trans("Project").')</span>' : '').'</td><td>';
	print $form->selectDate(($date_start ? $date_start : ''), 'projectstart', 0, 0, 0, '', 1, 0);
	print ' <span class="opacitymedium"> '.$langs->trans("to").' </span> ';
	print $form->selectDate(($date_end ? $date_end : -1), 'projectend', 0, 0, 0, '', 1, 0);
	print '</td></tr>';

	if (isModEnabled('eventorganization')) {
		// Date event
		print '<tr class="classuseorganizeevent"><td>'.$langs->trans("Date").' ('.$langs->trans("Event").')</td><td>';
		print $form->selectDate(($date_start_event ? $date_start_event : -1), 'date_start_event', 1, 1, 1, '', 1, 0);
		print ' <span class="opacitymedium"> '.$langs->trans("to").' </span> ';
		print $form->selectDate(($date_end_event ? $date_end_event : -1), 'date_end_event', 1, 1, 1, '', 1, 0);
		print '</td></tr>';

		// Location
		print '<tr class="classuseorganizeevent"><td>'.$langs->trans("Location").'</td>';
		print '<td><input class="minwidth300 maxwidth500" type="text" name="location" value="'.dol_escape_htmltag($location).'"></td>';
		print '</tr>';
	}

	// Description
	print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
	print '<td>';
	$doleditor = new DolEditor('description', GETPOST("description", 'restricthtml'), '', 90, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor') && getDolGlobalString('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
	$doleditor->Create();
	print '</td></tr>';

	if (isModEnabled('category')) {
		// Categories
		print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 3);
		$arrayselected = GETPOST('categories', 'array');
		print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}

	// Selection of Owner contact type
	print '<tr><td class="tdtop">'.$langs->trans("ProjectContactTypeManager").'</td>';
	print '<td>';
	$contactList = $object->liste_type_contact('internal', 'position', 1);
	$typeofcontact = GETPOST('typeofcontact') ? GETPOST('typeofcontact') : 'PROJECTLEADER';
	print $form->selectarray('typeofcontact', $contactList, $typeofcontact, 0, 0, 0, '', 0, 0, 0, '', '', 1);
	print '</td></tr>';

	// Other options
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create');
	}

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel('CreateDraft');

	print '</form>';

	// Change probability from status or role of project
	// Set also dependencies between use task and bill time
	print '<script type="text/javascript">
        jQuery(document).ready(function() {
        	function change_percent()
        	{
                var element = jQuery("#opp_status option:selected");
                var defaultpercent = element.attr("defaultpercent");
                /*if (jQuery("#opp_percent_not_set").val() == "") */
                jQuery("#opp_percent").val(defaultpercent);
        	}

			/*init_myfunc();*/
        	jQuery("#opp_status").change(function() {
        		change_percent();
        	});

        	jQuery("#usage_task").change(function() {
        		console.log("We click on usage task "+jQuery("#usage_task").is(":checked"));
                if (! jQuery("#usage_task").is(":checked")) {
                    jQuery("#usage_bill_time").prop("checked", false);
                }
        	});

        	jQuery("#usage_bill_time").change(function() {
        		console.log("We click on usage to bill time");
                if (jQuery("#usage_bill_time").is(":checked")) {
                    jQuery("#usage_task").prop("checked", true);
                }
        	});
        });
        </script>';
} elseif ($object->id > 0) {
	/*
	 * Show or edit
	 */

	$res = $object->fetch_optionals();

	// To verify role of users
	$userAccess = $object->restrictedProjectArea($user, 'read');
	$userWrite  = $object->restrictedProjectArea($user, 'write');
	$userDelete = $object->restrictedProjectArea($user, 'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


	// Confirmation validation
	if ($action == 'validate') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProject'), $langs->trans('ConfirmValidateProject'), 'confirm_validate', '', 0, 1);
	}
	// Confirmation close
	if ($action == 'close') {
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("CloseAProject"), $langs->trans("ConfirmCloseAProject"), "confirm_close", '', '', 1);
	}
	// Confirmation reopen
	if ($action == 'reopen') {
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("ReOpenAProject"), $langs->trans("ConfirmReOpenAProject"), "confirm_reopen", '', '', 1);
	}
	// Confirmation delete
	if ($action == 'delete') {
		$text = $langs->trans("ConfirmDeleteAProject");
		$task = new Task($db);
		$taskarray = $task->getTasksArray(0, 0, $object->id, 0, 0);
		$nboftask = count($taskarray);
		if ($nboftask) {
			$text .= '<br>'.img_warning().' '.$langs->trans("ThisWillAlsoRemoveTasks", $nboftask);
		}
		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("DeleteAProject"), $text, "confirm_delete", '', '', 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		$formquestion = array(
			'text' => $langs->trans("ConfirmClone"),
			0 => array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOSTINT('socid') > 0 ? GETPOSTINT('socid') : $object->socid, 'socid', '', "None", 0, 0, null, 0, 'minwidth200 maxwidth250')),
			1 => array('type' => 'checkbox', 'name' => 'clone_contacts', 'label' => $langs->trans("CloneContacts"), 'value' => true),
			2 => array('type' => 'checkbox', 'name' => 'clone_tasks', 'label' => $langs->trans("CloneTasks"), 'value' => true),
			3 => array('type' => 'checkbox', 'name' => 'move_date', 'label' => $langs->trans("CloneMoveDate"), 'value' => true),
			4 => array('type' => 'checkbox', 'name' => 'clone_notes', 'label' => $langs->trans("CloneNotes"), 'value' => true),
			5 => array('type' => 'checkbox', 'name' => 'clone_project_files', 'label' => $langs->trans("CloneProjectFiles"), 'value' => false),
			6 => array('type' => 'checkbox', 'name' => 'clone_task_files', 'label' => $langs->trans("CloneTaskFiles"), 'value' => false)
		);

		print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("ToClone"), $langs->trans("ConfirmCloneProject"), "confirm_clone", $formquestion, '', 1, 400, 590);
	}


	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<input type="hidden" name="comefromclone" value="'.$comefromclone.'">';

	$head = project_prepare_head($object);

	if ($action == 'edit' && $userWrite > 0) {
		print dol_get_fiche_head($head, 'project', $langs->trans("Project"), 0, ($object->public ? 'projectpub' : 'project'));

		print '<table class="border centpercent">';

		// Ref
		$suggestedref = $object->ref;
		print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Ref").'</td>';
		print '<td><input class="width200" name="ref" value="'.$suggestedref.'">';
		print ' '.$form->textwithpicto('', $langs->trans("YouCanCompleteRef", $suggestedref));
		print '</td></tr>';

		// Label
		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
		print '<td><input class="quatrevingtpercent" name="title" value="'.dol_escape_htmltag($object->title).'"></td></tr>';

		// Status
		print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td><td>';
		print '<select class="flat" name="status" id="status">';
		$statuses = $object->labelStatusShort;
		if (getDolGlobalString('MAIN_DISABLEDRAFTSTATUS') || getDolGlobalString('MAIN_DISABLEDRAFTSTATUS_PROJECT')) {
			unset($statuses[$object::STATUS_DRAFT]);
		}
		foreach ($statuses as $key => $val) {
			print '<option value="'.$key.'"'.((GETPOSTISSET('status') ? GETPOST('status') : $object->status) == $key ? ' selected="selected"' : '').'>'.$langs->trans($val).'</option>';
		}
		print '</select>';
		print ajax_combobox('status');
		print '</td></tr>';

		// Parent
		if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
			print '<tr><td>'.$langs->trans("Parent").'</td><td class="maxwidthonsmartphone">';
			print img_picto('', 'project', 'class="pictofixedwidth"');
			$formproject->select_projects(-1, $object->fk_project, 'fk_project', 64, 0, 1, 1, 0, 0, 0, '', 0, 0, '', '', '');
			print '</td></tr>';
		}

		// Usage
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
			print '<tr><td class="tdtop">';
			print $langs->trans("Usage");
			print '</td>';
			print '<td>';
			if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
				print '<input type="checkbox" id="usage_opportunity" name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print '<label for="usage_opportunity">'.$form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext).'</label>';
				print '<script>';
				print '$( document ).ready(function() {
					jQuery("#usage_opportunity").change(function() {
						set_usage_opportunity();
					});

					set_usage_opportunity();

					function set_usage_opportunity() {
						console.log("set_usage_opportunity");
						if (jQuery("#usage_opportunity").prop("checked")) {
							console.log("Show opportunities fields");
							jQuery(".classuseopportunity").show();
						} else {
							console.log("Hide opportunities fields "+jQuery("#usage_opportunity").prop("checked"));
							jQuery(".classuseopportunity").hide();
						}
					}
				});';
				print '</script>';
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
				print '<input type="checkbox" id="usage_task" name="usage_task"' . (GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')) . '> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print '<label for="usage_task">'.$form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext).'</label>';
				print '<script>';
				print '$( document ).ready(function() {
					jQuery("#usage_task").change(function() {
						set_usage_task();
					});

					set_usage_task();

					function set_usage_task() {
						console.log("set_usage_task");
						if (jQuery("#usage_task").prop("checked")) {
							console.log("Show task fields");
							jQuery(".classusetask").show();
						} else {
							console.log("Hide task fields "+jQuery("#usage_task").prop("checked"));
							jQuery(".classusetask").hide();
						}
					}
				});';
				print '</script>';
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
				print '<input type="checkbox" id="usage_bill_time" name="usage_bill_time"' . (GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')) . '> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print '<label for="usage_bill_time">'.$form->textwithpicto($langs->trans("BillTime"), $htmltext).'</label>';
				print '<script>';
				print '$( document ).ready(function() {
					jQuery("#usage_bill_time").change(function() {
						set_usage_bill_time();
					});

					set_usage_bill_time();

					function set_usage_bill_time() {
						console.log("set_usage_bill_time");
						if (jQuery("#usage_bill_time").prop("checked")) {
							console.log("Show bill time fields");
							jQuery(".classusebilltime").show();
						} else {
							console.log("Hide bill time fields "+jQuery("#usage_bill_time").prop("checked"));
							jQuery(".classusebilltime").hide();
						}
					}
				});';
				print '</script>';
				print '<br>';
			}
			if (isModEnabled('eventorganization')) {
				print '<input type="checkbox" id="usage_organize_event" name="usage_organize_event"'. (GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')) . '> ';
				$htmltext = $langs->trans("EventOrganizationDescriptionLong");
				print '<label for="usage_organize_event">'.$form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext).'</label>';
				print '<script>';
				print '$( document ).ready(function() {
					jQuery("#usage_organize_event").change(function() {
						set_usage_event();
					});

					set_usage_event();

					function set_usage_event() {
						console.log("set_usage_event");
						if (jQuery("#usage_organize_event").prop("checked")) {
							console.log("Show organize event fields");
							jQuery(".classuseorganizeevent").show();
						} else {
							console.log("Hide organize event fields "+jQuery("#usage_organize_event").prop("checked"));
							jQuery(".classuseorganizeevent").hide();
						}
					}
				});';
				print '</script>';
			}
			print '</td></tr>';
		}
		print '</td></tr>';

		// Thirdparty
		if (isModEnabled('societe')) {
			print '<tr><td>';
			print(!getDolGlobalString('PROJECT_THIRDPARTY_REQUIRED') ? '' : '<span class="fieldrequired">');
			print $langs->trans("ThirdParty");
			print(!getDolGlobalString('PROJECT_THIRDPARTY_REQUIRED') ? '' : '</span>');
			print '</td><td>';
			$filter = '';
			if (getDolGlobalString('PROJECT_FILTER_FOR_THIRDPARTY_LIST')) {
				$filter = getDolGlobalString('PROJECT_FILTER_FOR_THIRDPARTY_LIST');
			}
			$text = img_picto('', 'company', 'class="pictofixedwidth"');
			$text .= $form->select_company(!empty($object->thirdparty->id) ? $object->thirdparty->id : "", 'socid', $filter, 'None', 1, 0, array(), 0, 'minwidth300');
			if (!getDolGlobalString('PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS') && empty($conf->dol_use_jmobile)) {
				$texthelp = $langs->trans("IfNeedToUseOtherObjectKeepEmpty");
				print $form->textwithtooltip($text.' '.img_help(), $texthelp, 1, 0, '', '', 2);
			} else {
				print $text;
			}
			print '</td></tr>';
		}

		// Visibility
		print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
		$array = array();
		if (!getDolGlobalString('PROJECT_DISABLE_PRIVATE_PROJECT')) {
			$array[0] = $langs->trans("PrivateProject");
		}
		if (!getDolGlobalString('PROJECT_DISABLE_PUBLIC_PROJECT')) {
			$array[1] = $langs->trans("SharedProject");
		}

		if (count($array) > 0) {
			print $form->selectarray('public', $array, $object->public, 0, 0, 0, '', 0, 0, 0, '', '', 1);
		} else {
			print '<input type="hidden" id="public" name="public" value="'.$object->public.'">';

			if ($object->public == 0) {
				print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
				print $langs->trans("PrivateProject");
			} else {
				print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
				print $langs->trans("SharedProject");
			}
		}
		print '</td></tr>';

		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			$classfortr = ($object->usage_opportunity ? '' : ' hideobject');
			// Opportunity status
			print '<tr class="classuseopportunity'.$classfortr.'"><td>'.$langs->trans("OpportunityStatus").'</td>';
			print '<td>';
			print '<div>';
			print $formproject->selectOpportunityStatus('opp_status', $object->opp_status, 1, 0, 0, 0, 'minwidth150 inline-block valignmiddle', 1, 1);

			// Opportunity probability
			print ' <input class="width50 right" type="text" id="opp_percent" name="opp_percent" title="'.dol_escape_htmltag($langs->trans("OpportunityProbability")).'" value="'.(GETPOSTISSET('opp_percent') ? GETPOST('opp_percent') : (strcmp($object->opp_percent, '') ? vatrate($object->opp_percent) : '')).'"> %';
			print '<span id="oldopppercent" class="opacitymedium"></span>';
			print '</div>';

			print '<div id="divtocloseproject" class="inline-block valign clearboth paddingtop" style="display: none;">';
			print '<input type="checkbox" id="inputcloseproject" name="closeproject" />';
			print '<label for="inputcloseproject">';
			print $form->textwithpicto($langs->trans("AlsoCloseAProject"), $langs->trans("AlsoCloseAProjectTooltip")).'</label>';
			print ' </div>';

			print '</td>';
			print '</tr>';

			// Opportunity amount
			print '<tr class="classuseopportunity'.$classfortr.'"><td>'.$langs->trans("OpportunityAmount").'</td>';
			print '<td><input class="width75 right" type="text" name="opp_amount" value="'.(GETPOSTISSET('opp_amount') ? GETPOST('opp_amount') : (strcmp($object->opp_amount, '') ? price2num($object->opp_amount) : '')).'">';
			print $langs->getCurrencySymbol($conf->currency);
			print '</td>';
			print '</tr>';
		}

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td>';
		print '<td><input class="width75 right" type="text" name="budget_amount" value="'.(GETPOSTISSET('budget_amount') ? GETPOST('budget_amount') : (strcmp($object->budget_amount, '') ? price2num($object->budget_amount) : '')).'">';
		print $langs->getCurrencySymbol($conf->currency);
		print '</td>';
		print '</tr>';

		// Date project
		print '<tr><td>'.$langs->trans("Date").(isModEnabled('eventorganization') ? ' <span class="classuseorganizeevent">('.$langs->trans("Project").')</span>' : '').'</td><td>';
		print $form->selectDate($object->date_start ? $object->date_start : -1, 'projectstart', 0, 0, 0, '', 1, 0);
		print ' <span class="opacitymedium"> '.$langs->trans("to").' </span> ';
		print $form->selectDate($object->date_end ? $object->date_end : -1, 'projectend', 0, 0, 0, '', 1, 0);
		$object->getLinesArray(null, 0);
		if (!empty($object->usage_task) && !empty($object->lines)) {
			print ' <span id="divreportdate" class="hidden">&nbsp; &nbsp; <input type="checkbox" class="valignmiddle" id="reportdate" name="reportdate" value="yes" ';
			if ($comefromclone) {
				print 'checked ';
			}
			print '/><label for="reportdate" class="valignmiddle opacitymedium">'.$langs->trans("ProjectReportDate").'</label></span>';
		}
		print '</td></tr>';

		if (isModEnabled('eventorganization')) {
			// Date event
			print '<tr class="classuseorganizeevent"><td>'.$langs->trans("Date").' ('.$langs->trans("Event").')</td><td>';
			print $form->selectDate(($date_start_event ? $date_start_event : ($object->date_start_event ? $object->date_start_event : -1)), 'date_start_event', 1, 1, 1, '', 1, 0);
			print ' <span class="opacitymedium"> '.$langs->trans("to").' </span> ';
			print $form->selectDate(($date_end_event ? $date_end_event : ($object->date_end_event ? $object->date_end_event : -1)), 'date_end_event', 1, 1, 1, '', 1, 0);
			print '</td></tr>';

			// Location
			print '<tr class="classuseorganizeevent"><td>'.$langs->trans("Location").'</td>';
			print '<td><input class="minwidth300 maxwidth500" type="text" name="location" value="'.dol_escape_htmltag(GETPOSTISSET('location') ? GETPOST('location') : $object->location).'"></td>';
			print '</tr>';
		}

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
		print '<td>';
		$doleditor = new DolEditor('description', $object->description, '', 90, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor') && getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_3, '90%');
		$doleditor->Create();
		print '</td></tr>';

		// Tags-Categories
		if (isModEnabled('category')) {
			$arrayselected = array();
			print '<tr><td>'.$langs->trans("Categories").'</td><td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_PROJECT, '', 'parent', 64, 0, 3);
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_PROJECT);
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
			print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $cate_arbo, $arrayselected, 0, 0, 'quatrevingtpercent widthcentpercentminusx', 0, '0');
			print "</td></tr>";
		}

		// Other options
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'edit');
		}

		print '</table>';
	} else {
		print dol_get_fiche_head($head, 'project', $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'), 0, '', '', 0, '', 1);

		// Project card

		if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
			$tmpurl = $_SESSION['pageforbacktolist']['project'];
			$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
			$linkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		} else {
			$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
		}

		$morehtmlref = '<div class="refidno">';
		// Title
		$morehtmlref .= dol_escape_htmltag($object->title);
		$morehtmlref .= '<br>';
		// Thirdparty
		if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
			$morehtmlref .= $object->thirdparty->getNomUrl(1, 'project');
		}
		// Parent
		if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
			if (!empty($object->fk_project) && $object->fk_project) {
				$parent = new Project($db);
				$parent->fetch($object->fk_project);
				$morehtmlref .= $langs->trans("Child of").' '.$parent->getNomUrl(1, 'project').' '.$parent->title;
			}
		}
		$morehtmlref .= '</div>';

		// Define a complementary filter for search of next/prev ref.
		if (!$user->hasRight('projet', 'all', 'lire')) {
			$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
			$object->next_prev_filter = "rowid IN (".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0').")";
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

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
				print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'> ';
				$htmltext = $langs->trans("ProjectFollowOpportunity");
				print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
				print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'> ';
				$htmltext = $langs->trans("ProjectFollowTasks");
				print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
				print '<br>';
			}
			if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
				print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'> ';
				$htmltext = $langs->trans("ProjectBillTimeDescription");
				print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
				print '<br>';
			}

			if (isModEnabled('eventorganization')) {
				print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')).'> ';
				$htmltext = $langs->trans("EventOrganizationDescriptionLong");
				print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
			}
			print '</td></tr>';
		}

		// Visibility
		print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
		if ($object->public) {
			print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
			print $langs->trans('SharedProject');
		} else {
			print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
			print $langs->trans('PrivateProject');
		}
		print '</td></tr>';

		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') && !empty($object->usage_opportunity)) {
			// Opportunity status
			print '<tr><td>'.$langs->trans("OpportunityStatus");
			if ($action != 'edit_opp_status' && $user->hasRight('projet', 'creer')) {
				print '<a class="editfielda paddingtop" href="'.$_SERVER["PHP_SELF"].'?action=edit_opp_status&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a>';
			}
			print '</td><td>';
			$html_name_status 	= ($action == 'edit_opp_status') ? 'opp_status' : 'none';
			$html_name_percent 	= ($action == 'edit_opp_status') ? 'opp_percent' : 'none';
			$percent_value = (GETPOSTISSET('opp_percent') ? GETPOST('opp_percent') : (strcmp($object->opp_percent, '') ? vatrate($object->opp_percent) : ''));
			$formproject->formOpportunityStatus($_SERVER['PHP_SELF'].'?socid='.$object->id, $object->opp_status, $percent_value, $html_name_status, $html_name_percent);
			print '</td></tr>';

			// Opportunity Amount
			print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
			if (strcmp($object->opp_amount, '')) {
				print '<span class="amount">'.price($object->opp_amount, 0, $langs, 1, 0, -1, $conf->currency).'</span>';
				if (strcmp($object->opp_percent, '')) {
					print ' &nbsp; &nbsp; &nbsp; <span title="'.dol_escape_htmltag($langs->trans('OpportunityWeightedAmount')).'"><span class="opacitymedium">'.$langs->trans("OpportunityWeightedAmountShort").'</span>: <span class="amount">'.price($object->opp_amount * $object->opp_percent / 100, 0, $langs, 1, 0, -1, $conf->currency).'</span></span>';
				}
			}
			print '</td></tr>';
		}

		// Budget
		print '<tr><td>'.$langs->trans("Budget").'</td><td>';
		if (!is_null($object->budget_amount) && strcmp($object->budget_amount, '')) {
			print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
		}
		print '</td></tr>';

		// Date start - end project
		print '<tr><td>'.$langs->trans("Dates").'</td><td>';
		$start = dol_print_date($object->date_start, 'day');
		print($start ? $start : '?');
		$end = dol_print_date($object->date_end, 'day');
		print ' <span class="opacitymedium">-</span> ';
		print($end ? $end : '?');
		if ($object->hasDelay()) {
			print img_warning("Late");
		}
		print '</td></tr>';

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Description
		print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
		print '<div class="longmessagecut">';
		print dolPrintHTML($object->description);
		print '</div>';
		print '</td></tr>';

		// Categories
		if (isModEnabled('category')) {
			print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
			print "</td></tr>";
		}

		print '</table>';

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';
	}

	print dol_get_fiche_end();

	if ($action == 'edit' && $userWrite > 0) {
		print $form->buttonsSaveCancel();
	}

	print '</form>';

	// Set also dependencies between use task and bill time
	print '<script type="text/javascript">
        jQuery(document).ready(function() {
        	jQuery("#usage_task").change(function() {
        		console.log("We click on usage task "+jQuery("#usage_task").is(":checked"));
                if (! jQuery("#usage_task").is(":checked")) {
                    jQuery("#usage_bill_time").prop("checked", false);
                }
        	});

        	jQuery("#usage_bill_time").change(function() {
        		console.log("We click on usage to bill time");
                if (jQuery("#usage_bill_time").is(":checked")) {
                    jQuery("#usage_task").prop("checked", true);
                }
        	});

			jQuery("#projectstart").change(function() {
				console.log("We modify the start date");
				jQuery("#divreportdate").show();
			});
        });
        </script>';

	// Change probability from status
	if (!empty($conf->use_javascript_ajax) && getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		// Default value to close or not when we set opp to 'WON'.
		$defaultcheckedwhenoppclose = 1;
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			$defaultcheckedwhenoppclose = 0;
		}

		print '<!-- Javascript to manage opportunity status change -->';
		print '<script type="text/javascript">
            jQuery(document).ready(function() {
            	function change_percent()
            	{
                    var element = jQuery("#opp_status option:selected");
                    var defaultpercent = element.attr("defaultpercent");
                    var defaultcloseproject = '.((int) $defaultcheckedwhenoppclose).';
                    var elemcode = element.attr("elemcode");
                    var oldpercent = \''.dol_escape_js($object->opp_percent).'\';

                    console.log("We select "+elemcode);

                    /* Define if checkbox to close is checked or not */
                    var closeproject = 0;
                    if (elemcode == \'LOST\') closeproject = 1;
                    if (elemcode == \'WON\') closeproject = defaultcloseproject;
                    if (closeproject) jQuery("#inputcloseproject").prop("checked", true);
                    else jQuery("#inputcloseproject").prop("checked", false);

                    /* Make the close project checkbox visible or not */
                    console.log("closeproject="+closeproject);
                    if (elemcode == \'WON\' || elemcode == \'LOST\')
                    {
                        jQuery("#divtocloseproject").show();
                    }
                    else
                    {
                        jQuery("#divtocloseproject").hide();
                    }

                    /* Change percent with default percent (defaultpercent) if new status (defaultpercent) is higher than current (jQuery("#opp_percent").val()) */
                    if (oldpercent != \'\' && (parseFloat(defaultpercent) < parseFloat(oldpercent)))
                    {
	                    console.log("oldpercent="+oldpercent+" defaultpercent="+defaultpercent+" def < old");
                        if (jQuery("#opp_percent").val() != \'\' && oldpercent != \'\') {
							jQuery("#oldopppercent").text(\' - '.dol_escape_js($langs->transnoentities("PreviousValue")).': \'+price2numjs(oldpercent)+\' %\');
						}

						if (parseFloat(oldpercent) != 100 && elemcode != \'LOST\') { jQuery("#opp_percent").val(oldpercent); }
                        else { jQuery("#opp_percent").val(price2numjs(defaultpercent)); }
                    } else {
	                    console.log("oldpercent="+oldpercent+" defaultpercent="+defaultpercent);
                    	if (jQuery("#opp_percent").val() == \'\' || (parseFloat(jQuery("#opp_percent").val()) < parseFloat(defaultpercent))) {
                        	if (jQuery("#opp_percent").val() != \'\' && oldpercent != \'\') {
								jQuery("#oldopppercent").text(\' - '.dol_escape_js($langs->transnoentities("PreviousValue")).': \'+price2numjs(oldpercent)+\' %\');
							}
                        	jQuery("#opp_percent").val(price2numjs(defaultpercent));
                    	}
                    }
            	}

            	jQuery("#opp_status").change(function() {
            		change_percent();
            	});
        });
        </script>';
	}


	/*
	 * Actions Buttons
	 */

	print '<div class="tabsAction">';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
	// modified by hook
	if (empty($reshook)) {
		if ($action != "edit" && $action != 'presend') {
			// Create event
			/*if (isModEnabled('agenda') && !empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 				// Add hidden condition because this is not a
				// "workflow" action so should appears somewhere else on
				// page.
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '&amp;projectid=' . $object->id . '">' . $langs->trans("AddAction") . '</a>';
			}*/

			// Send
			if (empty($user->socid)) {
				if ($object->status != Project::STATUS_CLOSED) {
					print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?action=presend&token='.newToken().'&id='.$object->id.'&mode=init#formmailbeforetitle', '');
				}
			}

			// Accounting Report
			/*
			$accouting_module_activated = isModEnabled('comptabilite') || isModEnabled('accounting');
			if ($accouting_module_activated && $object->status != Project::STATUS_DRAFT) {
				$start = dol_getdate((int) $object->date_start);
				$end = dol_getdate((int) $object->date_end);
				$url = DOL_URL_ROOT.'/compta/accounting-files.php?projectid='.$object->id;
				if (!empty($object->date_start)) $url .= '&amp;date_startday='.$start['mday'].'&amp;date_startmonth='.$start['mon'].'&amp;date_startyear='.$start['year'];
				if (!empty($object->date_end)) $url .= '&amp;date_stopday='.$end['mday'].'&amp;date_stopmonth='.$end['mon'].'&amp;date_stopyear='.$end['year'];
				print dolGetButtonAction('', $langs->trans('ExportAccountingReportButtonLabel'), 'default', $url, '');
			}
			*/

			// Back to draft
			if (!getDolGlobalString('MAIN_DISABLEDRAFTSTATUS') && !getDolGlobalString('MAIN_DISABLEDRAFTSTATUS_PROJECT')) {
				if ($object->status != Project::STATUS_DRAFT && $user->hasRight('projet', 'creer')) {
					if ($userWrite > 0) {
						print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?action=confirm_setdraft&amp;confirm=yes&amp;token='.newToken().'&amp;id='.$object->id, '');
					} else {
						print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('SetToDraft'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
					}
				}
			}

			// Modify
			if ($object->status != Project::STATUS_CLOSED && $user->hasRight('projet', 'creer')) {
				if ($userWrite > 0) {
					print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id, '');
				} else {
					print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('Modify'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}

			// Validate
			if ($object->status == Project::STATUS_DRAFT && $user->hasRight('projet', 'creer')) {
				if ($userWrite > 0) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?action=validate&amp;token='.newToken().'&amp;id='.$object->id, '');
				} else {
					print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}

			// Close
			if ($object->status == Project::STATUS_VALIDATED && $user->hasRight('projet', 'creer')) {
				if ($userWrite > 0) {
					print dolGetButtonAction('', $langs->trans('Close'), 'default', $_SERVER["PHP_SELF"].'?action=close&amp;token='.newToken().'&amp;id='.$object->id, '');
				} else {
					print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('Close'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}

			// Reopen
			if ($object->status == Project::STATUS_CLOSED && $user->hasRight('projet', 'creer')) {
				if ($userWrite > 0) {
					print dolGetButtonAction('', $langs->trans('ReOpen'), 'default', $_SERVER["PHP_SELF"].'?action=reopen&amp;token='.newToken().'&amp;id='.$object->id, '');
				} else {
					print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('ReOpen'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}

			// Buttons Create
			if (!getDolGlobalString('PROJECT_HIDE_CREATE_OBJECT_BUTTON')) {
				$arrayforbutaction = array(
					10 => array('lang' => 'propal', 'enabled' => isModEnabled("propal"), 'perm' => $user->hasRight('propal', 'creer'), 'label' => 'AddProp', 'url' => '/comm/propal/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					20 => array('lang' => 'orders', 'enabled' => isModEnabled("order"), 'perm' => $user->hasRight('commande', 'creer'), 'label' => 'CreateOrder', 'url' => '/commande/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					30 => array('lang' => 'bills', 'enabled' => isModEnabled("invoice"), 'perm' => $user->hasRight('facture', 'creer'), 'label' => 'CreateBill', 'url' => '/compta/facture/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					40 => array('lang' => 'supplier_proposal', 'enabled' => isModEnabled("supplier_proposal"), 'perm' => $user->hasRight('supplier_proposal', 'creer'), 'label' => 'AddSupplierProposal', 'url' => '/supplier_proposal/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					50 => array('lang' => 'suppliers', 'enabled' => isModEnabled("supplier_order"), 'perm' => $user->hasRight('fournisseur', 'commande', 'creer'), 'label' => 'AddSupplierOrder', 'url' => '/fourn/commande/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					60 => array('lang' => 'suppliers', 'enabled' => isModEnabled("supplier_invoice"), 'perm' => $user->hasRight('fournisseur', 'facture', 'creer'), 'label' => 'AddSupplierInvoice', 'url' => '/fourn/facture/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					70 => array('lang' => 'interventions', 'enabled' => isModEnabled("intervention"), 'perm' => $user->hasRight('fichinter', 'creer'), 'label' => 'AddIntervention', 'url' => '/fichinter/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					80 => array('lang' => 'contracts', 'enabled' => isModEnabled("contract"), 'perm' => $user->hasRight('contrat', 'creer'), 'label' => 'AddContract', 'url' => '/contrat/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
					90 => array('lang' => 'trips', 'enabled' => isModEnabled("expensereport"), 'perm' => $user->hasRight('expensereport', 'creer'), 'label' => 'AddTrip', 'url' => '/expensereport/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
				   100 => array('lang' => 'donations', 'enabled' => isModEnabled("don"), 'perm' => $user->hasRight('don', 'creer'), 'label' => 'AddDonation', 'url' => '/don/card.php?action=create&amp;projectid='.$object->id.'&amp;socid='.$object->socid),
				);

				$params = array('backtopage' => $_SERVER["PHP_SELF"].'?id='.$object->id);

				print dolGetButtonAction('', $langs->trans("Create"), 'default', $arrayforbutaction, '', 1, $params);
			}

			// Clone
			if ($user->hasRight('projet', 'creer')) {
				if ($userWrite > 0) {
					print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER["PHP_SELF"].'?action=clone&token='.newToken().'&id='.((int) $object->id), '');
				} else {
					print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}

			// Delete
			if ($user->hasRight('projet', 'supprimer') || ($object->status == Project::STATUS_DRAFT && $user->hasRight('projet', 'creer'))) {
				if ($userDelete > 0 || ($object->status == Project::STATUS_DRAFT && $user->hasRight('projet', 'creer'))) {
					print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id, '');
				} else {
					print dolGetButtonAction($langs->trans('NotOwnerOfProject'), $langs->trans('Delete'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}
		}
	}

	print "</div>";

	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
			/*
			 * Sub-projects (children)
			 */
			$children = $object->getChildren();
			if ($children) {
				print '<table class="centpercent notopnoleftnoright table-fiche-title">';
				print '<tr class="titre"><td class="nobordernopadding valignmiddle col-title">';
				print '<div class="titre inline-block">'.$langs->trans('Sub-projects').'</div>';
				print '</td></tr></table>';

				print '<div class="div-table-responsive-no-min">';
				print '<table class="centpercent noborder'.($morecss ? ' '.$morecss : '').'">';
				print '<tr class="liste_titre">';
				print getTitleFieldOfList('Ref', 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', '', 1);
				print getTitleFieldOfList('Title', 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', '', 1);
				print getTitleFieldOfList('Status', 0, $_SERVER["PHP_SELF"], '', '', '', '', '', '', '', 1);
				print '</tr>';
				print "\n";

				$subproject = new Project($db);
				foreach ($children as $child) {
					$subproject->fetch($child->rowid);
					print '<tr class="oddeven">';
					print '<td class="nowraponall">'.$subproject->getNomUrl(1, 'project').'</td>';
					print '<td class="nowraponall tdoverflowmax125">'.$child->title.'</td>';
					print '<td class="nowraponall">'.$subproject->getLibStatut(5).'</td>';
					print '</tr>';
				}

				print '</table>';
				print '</div>';
			}
		}

		/*
		 * Generated documents
		 */
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->project->multidir_output[$object->entity]."/".dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed = ($user->hasRight('projet', 'lire') && $userAccess > 0);
		$delallowed = ($user->hasRight('projet', 'creer') && $userWrite > 0);

		print $formfile->showdocuments('project', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 0, 0, '', '', '', '', '', $object);

		print '</div><div class="fichehalfright">';

		$MAXEVENT = 10;

		$morehtmlcenter = '<div class="nowraponall">';
		$morehtmlcenter .= dolGetButtonTitle($langs->trans('FullConversation'), '', 'fa fa-comments imgforviewmode', DOL_URL_ROOT.'/projet/messaging.php?id='.$object->id);
		$morehtmlcenter .= dolGetButtonTitle($langs->trans('FullList'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/projet/agenda.php?id='.$object->id);
		$morehtmlcenter .= '</div>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'project', 0, 1, '', $MAXEVENT, '', $morehtmlcenter);

		print '</div></div>';
	}

	// Presend form
	$modelmail = 'project';
	$defaulttopic = 'SendProjectRef';
	$defaulttopiclang = 'projects';
	$diroutput = $conf->project->multidir_output[$object->entity];
	$autocopy = 'MAIN_MAIL_AUTOCOPY_PROJECT_TO'; // used to know the automatic BCC to add
	$trackid = 'proj'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';

	// Hook to add more things on page
	$parameters = array();
	$reshook = $hookmanager->executeHooks('mainCardTabAddMore', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
} else {
	print $langs->trans("RecordNotFound");
}

// End of page
llxFooter();
$db->close();
