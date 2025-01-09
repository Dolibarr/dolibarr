<?php
/* Copyright (C) 2017		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *    \file       htdocs/eventorganization/conferenceorboothattendee_card.php
 *    \ingroup    eventorganization
 *    \brief      Page to create/edit/view conferenceorboothattendee
 */


// Load Dolibarr environment
require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/lib/eventorganization_conferenceorbooth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Load translation files required by the page
$langs->loadLangs(array("eventorganization", "other", "projects", "companies"));

// Get parameters
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'conferenceorboothattendeecard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
$lineid   = GETPOSTINT('lineid');
$mode = GETPOST('mode', 'alpha');

$conf_or_booth_id = GETPOSTINT('conforboothid');
$fk_project = GETPOSTINT('fk_project');
$withproject = 1;

// Initialize a technical objects
$object = new ConferenceOrBoothAttendee($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);
$diroutputmassaction = $conf->eventorganization->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('conferenceorboothattendeecard', 'globalcard')); // Note that conf->hooks_modules contains array


if ($conf_or_booth_id > 0) {
	$confOrBooth = new ConferenceOrBooth($db);
	$result = $confOrBooth->fetch($id > 0 ? $id : $conf_or_booth_id);
	if ($result < 0) {
		setEventMessages(null, $confOrBooth->errors, 'errors');
	} else {
		$object->fk_actioncomm = $confOrBooth->id;
		$object->fk_project = $confOrBooth->fk_project;
		$fk_project = $object->fk_project;
	}
}

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = GETPOST('search_all', 'alphanohtml');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha') !== '') {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
	if (preg_match('/^(date|timestamp|datetime)/', $val['type'])) {
		$search[$key.'_dtstart'] = dol_mktime(0, 0, 0, GETPOSTINT('search_'.$key.'_dtstartmonth'), GETPOSTINT('search_'.$key.'_dtstartday'), GETPOSTINT('search_'.$key.'_dtstartyear'));
		$search[$key.'_dtend'] = dol_mktime(23, 59, 59, GETPOSTINT('search_'.$key.'_dtendmonth'), GETPOSTINT('search_'.$key.'_dtendday'), GETPOSTINT('search_'.$key.'_dtendyear'));
	}
}

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (!empty($val['searchall'])) {
		$fieldstosearchall['t.'.$key] = $val['label'];
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// Now we have loaded the attendee, we can force the project (in case value provided as parameter is wrong or value not provided)
if ($object->fk_project > 0) {
	$fk_project = $object->fk_project;
}

// Permissions
$permissiontoread = $user->hasRight('eventorganization', 'read');
$permissiontoadd = $user->hasRight('eventorganization', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('eventorganization', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->hasRight('eventorganization', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('eventorganization', 'write'); // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->eventorganization->multidir_output[isset($object->entity) ? $object->entity : 1];


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	if (!empty($withproject)) {
		$backurlforlist = DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_list.php?withproject=1&fk_project='.((int) $fk_project);
	} else {
		$backurlforlist = DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_list.php';
	}

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_card.php?fk_project='.((int) $fk_project).'&id='.($id > 0 ? $id : '__ID__');
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

	$triggermodname = 'EVENTORGANIZATION_CONFERENCEORBOOTHATTENDEE_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', null, 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	// Actions to send emails
	$triggersendname = 'EVENTORGANIZATION_CONFERENCEORBOOTHATTENDEE_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_CONFERENCEORBOOTHATTENDEE_TO';
	$trackid = 'conferenceorboothattendee'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("ConferenceOrBoothAttendee");
$help_url = 'EN:Module_Event_Organization';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-eventorganization page-attendee-card');

$result = $projectstatic->fetch(empty($confOrBooth->fk_project) ? $fk_project : $confOrBooth->fk_project);
if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
	$projectstatic->fetchComments();
}
if (!empty($projectstatic->socid)) {
	$projectstatic->fetch_thirdparty();
}


$object->project = clone $projectstatic;

if (!empty($withproject)) {
	// Tabs for project
	$tab = 'eventorganisation';
	$head = project_prepare_head($projectstatic);
	print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'), 0, '', '');

	$param = ($mode == 'mine' ? '&mode=mine' : '');

	// Project card

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Title
	$morehtmlref .= $projectstatic->title;
	// Thirdparty
	if (!empty($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
		$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref .= '</div>';

	// Define a complementary filter for search of next/prev ref.
	if (!$user->hasRight('projet', 'all', 'lire')) {
		$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
		$projectstatic->next_prev_filter = "rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
	}

	dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

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
			print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowOpportunity");
			print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowTasks");
			print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
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
		print $langs->trans('SharedProject');
	} else {
		print $langs->trans('PrivateProject');
	}
	print '</td></tr>';

	// Date start - end
	print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
	$start = dol_print_date($projectstatic->date_start, 'day');
	print($start ? $start : '?');
	$end = dol_print_date($projectstatic->date_end, 'day');
	print ' - ';
	print($end ? $end : '?');
	if ($projectstatic->hasDelay()) {
		print img_warning("Late");
	}
	print '</td></tr>';

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (strcmp($projectstatic->budget_amount, '')) {
		print price($projectstatic->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
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
	if (isModEnabled('category')) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($projectstatic->id, 'project', 1);
		print "</td></tr>";
	}

	print '<tr><td class="nowrap">';
	$typeofdata = 'checkbox:'.($projectstatic->accept_conference_suggestions ? ' checked="checked"' : '');
	$htmltext = $langs->trans("AllowUnknownPeopleSuggestConfHelp");
	print $form->editfieldkey('AllowUnknownPeopleSuggestConf', 'accept_conference_suggestions', '', $projectstatic, 0, $typeofdata, '', 0, 0, 'projectid', $htmltext);
	print '</td><td>';
	print $form->editfieldval('AllowUnknownPeopleSuggestConf', 'accept_conference_suggestions', '1', $projectstatic, 0, $typeofdata, '', null, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td>';
	$typeofdata = 'checkbox:'.($projectstatic->accept_booth_suggestions ? ' checked="checked"' : '');
	$htmltext = $langs->trans("AllowUnknownPeopleSuggestBoothHelp");
	print $form->editfieldkey('AllowUnknownPeopleSuggestBooth', 'accept_booth_suggestions', '', $projectstatic, 0, $typeofdata, '', 0, 0, 'projectid', $htmltext);
	print '</td><td>';
	print $form->editfieldval('AllowUnknownPeopleSuggestBooth', 'accept_booth_suggestions', '1', $projectstatic, 0, $typeofdata, '', null, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans('PriceOfBooth'), $langs->trans("PriceOfBoothHelp")), 'price_booth', '', $projectstatic, 0, 'amount', '', 0, 0, 'projectid');
	print '</td><td>';
	print $form->editfieldval($form->textwithpicto($langs->trans('PriceOfBooth'), $langs->trans("PriceOfBoothHelp")), 'price_booth', $projectstatic->price_booth, $projectstatic, 0, 'amount', '', null, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans('PriceOfRegistration'), $langs->trans("PriceOfRegistrationHelp")), 'price_registration', '', $projectstatic, 0, 'amount', '', 0, 0, 'projectid');
	print '</td><td>';
	print $form->editfieldval($form->textwithpicto($langs->trans('PriceOfRegistration'), $langs->trans("PriceOfRegistrationHelp")), 'price_registration', $projectstatic->price_registration, $projectstatic, 0, 'amount', '', null, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td valign="middle">'.$langs->trans("EventOrganizationICSLink").'</td><td>';
	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;

	// Show message
	$message = '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical'.($conf->entity > 1 ? "&entity=".$conf->entity : "");
	$message .= '&exportkey='.($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY ? urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) : '...');
	$message .= "&project=".$projectstatic->id.'&module='.urlencode('@eventorganization').'&status='.ConferenceOrBooth::STATUS_CONFIRMED.'">'.$langs->trans('DownloadICSLink').img_picto('', 'download', 'class="paddingleft"').'</a>';
	print $message;
	print "</td></tr>";

	// Link to the submit vote/register page
	print '<tr><td>';
	//print '<span class="opacitymedium">';
	print $form->textwithpicto($langs->trans("SuggestOrVoteForConfOrBooth"), $langs->trans("EvntOrgRegistrationHelpMessage"));
	//print '</span>';
	print '</td><td>';
	$linksuggest = $dolibarr_main_url_root.'/public/project/index.php?id='.$projectstatic->id;
	$encodedsecurekey = dol_hash(getDolGlobalString("EVENTORGANIZATION_SECUREKEY").'conferenceorbooth'.$projectstatic->id, 'md5');
	$linksuggest .= '&securekey='.urlencode($encodedsecurekey);
	//print '<div class="urllink">';
	//print '<input type="text" value="'.$linksuggest.'" id="linkregister" class="quatrevingtpercent paddingrightonly">';
	print '<div class="tdoverflowmax200 inline-block valignmiddle"><a target="_blank" href="'.$linksuggest.'" class="quatrevingtpercent">'.$linksuggest.'</a></div>';
	print '<a target="_blank" rel="noopener noreferrer" rel="noopener noreferrer" href="'.$linksuggest.'">'.img_picto('', 'globe').'</a>';
	//print '</div>';
	//print ajax_autoselect("linkregister");
	print '</td></tr>';

	// Link to the subscribe
	print '<tr><td>';
	//print '<span class="opacitymedium">';
	print $langs->trans("PublicAttendeeSubscriptionGlobalPage");
	//print '</span>';
	print '</td><td>';
	$link_subscription = $dolibarr_main_url_root.'/public/eventorganization/attendee_new.php?id='.$projectstatic->id.'&type=global';
	$encodedsecurekey = dol_hash(getDolGlobalString("EVENTORGANIZATION_SECUREKEY").'conferenceorbooth'.$projectstatic->id, 'md5');
	$link_subscription .= '&securekey='.urlencode($encodedsecurekey);
	//print '<div class="urllink">';
	//print '<input type="text" value="'.$linkregister.'" id="linkregister" class="quatrevingtpercent paddingrightonly">';
	print '<div class="tdoverflowmax200 inline-block valignmiddle"><a target="_blank" href="'.$link_subscription.'" class="quatrevingtpercent">'.$link_subscription.'</a></div>';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$link_subscription.'">'.img_picto('', 'globe').'</a>';
	//print '</div>';
	//print ajax_autoselect("linkregister");
	print '</td></tr>';

	print '</table>';

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	print '<br>';
}

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("ConferenceOrBoothAttendee")), '', 'object_'.$object->picto);


	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].(empty($withproject) ? '' : '?withproject=1').'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	if ($confOrBooth->id > 0) {
		print '<input type="hidden" name="conforboothid" value="'.$confOrBooth->id.'">';
	}
	if ($projectstatic->id > 0) {
		print '<input type="hidden" name="fk_project" value="'.$projectstatic->id.'">';
		print '<input type="hidden" name="projectid" value="'.$projectstatic->id.'">';
	}

	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}

	print dol_get_fiche_head(array(), '');

	print '<table class="border centpercent tableforfieldcreate">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create");

	print '</form>';

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("ConferenceOrBoothAttendee"), '', 'object_'.$object->picto);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	if ($confOrBooth->id > 0) {
		print '<input type="hidden" name="conforboothid" value="'.$confOrBooth->id.'">';
	}
	if ($object->fk_actioncomm > 0) {
		print '<input type="hidden" name="fk_actioncomm" value="'.$object->fk_actioncomm.'">';
	}
	if ($backtopage) {
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	}
	if ($backtopageforcancel) {
		print '<input type="hidden" name="backtopageforcancel" value="'.$backtopageforcancel.'">';
	}
	if ($projectstatic->id > 0) {
		print '<input type="hidden" name="fk_project" value="'.$projectstatic->id.'">';
	}

	print dol_get_fiche_head();

	print '<table class="border centpercent tableforfieldedit">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$object->fetch_optionals();

	$moreparam = '';
	if ($withproject) {
		$moreparam .= '&withproject=1';
	}
	if ($fk_project) {
		$moreparam .= '&fk_project='.((int) $fk_project);
	}

	$head = conferenceorboothAttendeePrepareHead($object);
	print dol_get_fiche_head($head, 'card', $langs->trans("ConferenceOrBoothAttendee"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteConferenceOrBoothAttendee'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx') {
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/eventorganization/conferenceorboothattendee_list.php', 1).'?restore_lastsearch_values=1'.(!empty($confOrBooth->id) && $confOrBooth->id > 0 ? '&conforboothid='.((int) $confOrBooth->id) : '').$moreparam.'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $moreparam);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	$keyforbreak = 'note_public';

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">'."\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			// Send
			if (empty($user->socid)) {
				print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($confOrBooth->id) ? '&conforboothid='.$confOrBooth->id : '').(!empty($projectstatic->id) ? '&fk_project='.$projectstatic->id : '').'&action=presend&token='.newToken().'&mode=init#formmailbeforetitle');
			}
			print dolGetButtonAction('', $langs->trans('Modify'), 'default', $_SERVER["PHP_SELF"].'?id='.$object->id.(!empty($confOrBooth->id) ? '&conforboothid='.$confOrBooth->id : '').(!empty($projectstatic->id) ? '&fk_project='.$projectstatic->id : '').'&action=edit&token='.newToken(), '', $permissiontoadd);

			// Clone
			print dolGetButtonAction('', $langs->trans('ToClone'), 'default', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clone&token='.newToken().(!empty($projectstatic->id) ? '&fk_project='.$projectstatic->id : ''), '', $permissiontoadd);

			// Delete (need delete permission, or if draft, just need create/modify permission)
			print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER['PHP_SELF'].'?id='.$object->id.'&action=delete&token='.newToken().(!empty($projectstatic->id) ? '&fk_project='.$projectstatic->id : ''), '', $permissiontodelete || ($object->status == $object::STATUS_DRAFT && $permissiontoadd));
		}
		print '</div>'."\n";
	}


	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 0;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$relativepath = $objref.'/'.$objref.'.pdf';
			$filedir = $conf->eventorganization->dir_output.'/'.$object->element.'/'.$objref;
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->hasRight('eventorganization', 'conferenceorboothattendee', 'read'); // If you can read, you can build the PDF to read content
			$delallowed = $user->hasRight('eventorganization', 'conferenceorboothattendee', 'write'); // If you can create/edit, you can remove a file on card
			print $formfile->showdocuments('eventorganization:ConferenceOrBoothAttendee', $object->element.'/'.$objref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
		}

		// Show links to link elements
		$tmparray = $form->showLinkToObjectBlock($object, array(), array('conferenceorboothattendee'), 1);
		$linktoelem = $tmparray['linktoelem'];
		$htmltoenteralink = $tmparray['htmltoenteralink'];
		print $htmltoenteralink;

		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


		print '</div><div class="fichehalfright">';

		print '</div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'conferenceorboothattendee';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->eventorganization->dir_output;
	$trackid = 'conferenceorboothattendee'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
