<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021		Florian Henry			<florian.henry@scopen.fr>
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
 *  \file       conferenceorbooth_document.php
 *  \ingroup    eventorganization
 *  \brief      Tab for documents linked to ConferenceOrBooth
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorbooth.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/lib/eventorganization_conferenceorbooth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array("eventorganization", "projects", "companies", "other", "mails"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$cancel = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'conferenceorboothcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$withproject = GETPOST('withproject', 'int');
$project_ref = GETPOST('project_ref', 'alpha');


// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}
//if (! $sortfield) $sortfield="position_name";

// Initialize technical objects
$object = new ConferenceOrBooth($db);
$extrafields = new ExtraFields($db);
$projectstatic = new Project($db);
$diroutputmassaction = $conf->eventorganization->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('conferenceorboothdocument', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->eventorganization->multidir_output[$object->entity ? $object->entity : $conf->entity]."/conferenceorbooth/".get_exdir(0, 0, 0, 1, $object);
}

$permissiontoread = $user->rights->eventorganization->read;
$permissiontoadd = $user->rights->eventorganization->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->eventorganization->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->eventorganization->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->eventorganization->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->eventorganization->multidir_output[isset($object->entity) ? $object->entity : 1];

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
$isdraft = (($object->status== $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'eventorganization', $object->id, '', '', 'fk_soc', 'rowid', $isdraft);

if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ConferenceOrBooth").' - '.$langs->trans("Files");
$help_url = '';
llxHeader('', $title, $help_url);

$result = $projectstatic->fetch($object->fk_project);
if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
	$projectstatic->fetchComments();
}
if (!empty($projectstatic->socid)) {
	$projectstatic->fetch_thirdparty();
}

$withProjectUrl='';
$object->project = clone $projectstatic;

if (!empty($withproject)) {
	// Tabs for project
	$tab = 'eventorganisation';
	$withProjectUrl = "&withproject=1";
	$head = project_prepare_head($projectstatic);
	print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'), 0, '', '');

	// Project card

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Title
	$morehtmlref .= $projectstatic->title;
	// Thirdparty
	if (isset($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$projectstatic->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref .= '</div>';

	// Define a complementary filter for search of next/prev ref.
	if (empty($user->rights->project->all->lire)) {
		$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
		$projectstatic->next_prev_filter = " rowid IN (".$db->sanitize(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
	}

	dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Usage
	if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) || empty($conf->global->PROJECT_HIDE_TASKS) || !empty($conf->eventorganization->enabled)) {
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
		if (!empty($conf->eventorganization->enabled)) {
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
		print price($projectstatic->budget_amount, '', $langs, 1, 0, 0, $conf->currency);
	}
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	$objectconf = $object;
	$object = $projectstatic;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';
	$object = $objectconf;

	print '</table>';

	print '</div>';

	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Description
	print '<td class="tdtop">'.$langs->trans("Description").'</td><td>';
	print nl2br($projectstatic->description);
	print '</td></tr>';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($projectstatic->id, Categorie::TYPE_PROJECT, 1);
		print "</td></tr>";
	}

	print '<tr><td>';
	$typeofdata = 'checkbox:'.($projectstatic->accept_conference_suggestions ? ' checked="checked"' : '');
	$htmltext = $langs->trans("AllowUnknownPeopleSuggestConfHelp");
	print $form->editfieldkey('AllowUnknownPeopleSuggestConf', 'accept_conference_suggestions', '', $projectstatic, 0, $typeofdata, '', 0, 0, 'projectid', $htmltext);
	print '</td><td>';
	print $form->editfieldval('AllowUnknownPeopleSuggestConf', 'accept_conference_suggestions', '1', $projectstatic, 0, $typeofdata, '', 0, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td>';
	$typeofdata = 'checkbox:'.($projectstatic->accept_booth_suggestions ? ' checked="checked"' : '');
	$htmltext = $langs->trans("AllowUnknownPeopleSuggestBoothHelp");
	print $form->editfieldkey('AllowUnknownPeopleSuggestBooth', 'accept_booth_suggestions', '', $projectstatic, 0, $typeofdata, '', 0, 0, 'projectid', $htmltext);
	print '</td><td>';
	print $form->editfieldval('AllowUnknownPeopleSuggestBooth', 'accept_booth_suggestions', '1', $projectstatic, 0, $typeofdata, '', 0, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans('PriceOfBooth'), $langs->trans("PriceOfBoothHelp")), 'price_booth', '', $projectstatic, 0, 'amount', '', 0, 0, 'projectid');
	print '</td><td>';
	print $form->editfieldval($form->textwithpicto($langs->trans('PriceOfBooth'), $langs->trans("PriceOfBoothHelp")), 'price_booth', $projectstatic->price_booth, $projectstatic, 0, 'amount', '', 0, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td>';
	print $form->editfieldkey($form->textwithpicto($langs->trans('PriceOfRegistration'), $langs->trans("PriceOfRegistrationHelp")), 'price_registration', '', $projectstatic, 0, 'amount', '', 0, 0, 'projectid');
	print '</td><td>';
	print $form->editfieldval($form->textwithpicto($langs->trans('PriceOfRegistration'), $langs->trans("PriceOfRegistrationHelp")), 'price_registration', $projectstatic->price_registration, $projectstatic, 0, 'amount', '', 0, 0, '', 0, '', 'projectid');
	print "</td></tr>";

	print '<tr><td valign="middle">'.$langs->trans("EventOrganizationICSLink").'</td><td>';
	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;

	// Show message
	$message = '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/agenda/agendaexport.php?format=ical'.($conf->entity > 1 ? "&entity=".$conf->entity : "");
	$message .= '&exportkey='.urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY', '...'));
	$message .= "&project=".$projectstatic->id.'&module='.urlencode('@eventorganization').'&status='.ConferenceOrBooth::STATUS_CONFIRMED.'">'.$langs->trans('DownloadICSLink').img_picto('', 'download', 'class="paddingleft"').'</a>';
	print $message;
	print "</td></tr>";

	// Link to the submit vote/register page
	print '<tr><td>';
	//print '<span class="opacitymedium">';
	print $form->textwithpicto($langs->trans("SuggestOrVoteForConfOrBooth"), $langs->trans("EvntOrgRegistrationHelpMessage"));
	//print '</span>';
	print '</td><td>';
	$linksuggest = $dolibarr_main_url_root.'/public/project/index.php?id='.((int) $projectstatic->id);
	$encodedsecurekey = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY').'conferenceorbooth'.((int) $projectstatic->id), 'md5');
	$linksuggest .= '&securekey='.urlencode($encodedsecurekey);
	//print '<div class="urllink">';
	//print '<input type="text" value="'.$linksuggest.'" id="linkregister" class="quatrevingtpercent paddingrightonly">';
	print '<div class="tdoverflowmax200 inline-block valignmiddle"><a target="_blank" href="'.$linksuggest.'" class="quatrevingtpercent">'.$linksuggest.'</a></div>';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$linksuggest.'">'.img_picto('', 'globe').'</a>';
	//print '</div>';
	//print ajax_autoselect("linkregister");
	print '</td></tr>';

	// Link to the subscribe
	print '<tr><td>';
	//print '<span class="opacitymedium">';
	print $langs->trans("PublicAttendeeSubscriptionGlobalPage");
	//print '</span>';
	print '</td><td>';
	$link_subscription = $dolibarr_main_url_root.'/public/eventorganization/attendee_new.php?id='.((int) $projectstatic->id).'&type=global';
	$encodedsecurekey = dol_hash(getDolGlobalString('EVENTORGANIZATION_SECUREKEY').'conferenceorbooth'.((int) $projectstatic->id), 'md5');
	$link_subscription .= '&securekey='.urlencode($encodedsecurekey);
	//print '<div class="urllink">';
	//print '<input type="text" value="'.$linkregister.'" id="linkregister" class="quatrevingtpercent paddingrightonly">';
	print '<div class="tdoverflowmax200 inline-block valignmiddle"><a target="_blank" href="'.$link_subscription.'" class="quatrevingtpercent">'.$link_subscription.'</a></div>';
	print '<a target="_blank" rel="noopener noreferrer" rel="noopener noreferrer" href="'.$link_subscription.'">'.img_picto('', 'globe').'</a>';
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

if ($object->id) {
	/*
	 * Show tabs
	 */
	$head = conferenceorboothPrepareHead($object, $withproject);

	print dol_get_fiche_head($head, 'document', $langs->trans("ConferenceOrBooth"), -1, $object->picto);


	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	// Object card
	//-----------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/eventorganization/conferenceorbooth_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'eventorganization';
	//$permission = $user->rights->eventorganization->conferenceorbooth->write;
	$permission = 1;
	//$permtoedit = $user->rights->eventorganization->conferenceorbooth->write;
	$permtoedit = 1;
	$param = '&id='.$object->id;
	//$param = '';
	if ($withproject) {
		$param .= '&withproject=1';
	}
	//$relativepathwithnofile='conferenceorbooth/' . dol_sanitizeFileName($object->id).'/';
	$relativepathwithnofile = 'conferenceorbooth/'.dol_sanitizeFileName($object->ref).'/';

	include_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
