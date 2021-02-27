<?php
/* Copyright (C) 2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2021 florian.henry@scopen.fr  <florian.henry@scopen.fr>
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
 *	\file       htdocs/projet/event.php
 *	\ingroup    project
 *	\brief      Tab event organization
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->load('projects', 'eventorganization');

$action = GETPOST('action', 'aZ09');
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$mine = $_REQUEST['mode'] == 'mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
}
$result = restrictedArea($user, 'eventorganization', $id);

$permissiontoread = $user->rights->eventorganization->read;
$permissiontoadd = $user->rights->eventorganization->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->eventorganization->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

/*
 * Actions
 */

if ($action == 'update' && empty(GETPOST('cancel')) && $permissiontoadd) {
	$error = 0;
	$object->oldcopy = clone $object;

	$object->accept_conference_suggestions=(GETPOST('accept_conference_suggestions', 'alpha') == 'on' ? 1 : 0);
	$object->accept_booth_suggestions=(GETPOST('accept_booth_suggestions', 'alpha') == 'on' ? 1 : 0);
	$object->price_registration=price2num(GETPOST('price_registration', 'alphanohtml'));;
	$object->price_booth=price2num(GETPOST('price_booth', 'alphanohtml'));;

	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
		$db->rollback();
		$action = 'edit';
	} else {
		$db->commit();
		if (GETPOST('socid', 'int') > 0) {
			$object->fetch_thirdparty(GETPOST('socid', 'int'));
		} else {
			unset($object->thirdparty);
		}
		$action='';
	}
}



/*
 * View
 */

$title = $langs->trans("Project").' - '.$langs->trans("ConferenceOrBoothTab").' - '.$object->ref.' '.$object->name;
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->ref.' '.$object->name.' - '.$langs->trans("Note");
}
//TODO Make wiki docs
$help_url = '';
llxHeader("", $title, $help_url);

$form = new Form($db);
$userstatic = new User($db);

$now = dol_now();

if ($id > 0 || !empty($ref)) {
	$head = project_prepare_head($object);
	print dol_get_fiche_head($head, 'eventorganisation', $langs->trans('ConferenceOrBoothTab'), -1);

	// Project card

	$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Title
	$morehtmlref .= $object->title;
	// Thirdparty
	if ($object->thirdparty->id > 0) {
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref .= '</div>';

	// Define a complementary filter for search of next/prev ref.
	if (!$user->rights->projet->all->lire) {
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$object->next_prev_filter = " rowid in (".(count($objectsListId) ?join(',', array_keys($objectsListId)) : '0').")";
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield" width="100%">';

	// Usage
	print '<tr><td class="tdtop">';
	print $langs->trans("Usage");
	print '</td>';
	print '<td>';
	if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
		print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("ProjectFollowOpportunity");
		print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
		print '<br>';
	}
	if (empty($conf->global->PROJECT_HIDE_TASKS)) {
		print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("ProjectFollowTasks");
		print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
		print '<br>';
	}
	if (!empty($conf->global->PROJECT_BILL_TIME_SPENT)) {
		print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("ProjectBillTimeDescription");
		print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
		print '<br>';
	}

	if (!empty($conf->eventorganization->enabled)) {
		print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')).'"> ';
		$htmltext = $langs->trans("EventOrganizationDescriptionLong");
		print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
	}
	print '</td></tr>';

	// Visibility
	print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) {
		print $langs->trans('SharedProject');
	} else {
		print $langs->trans('PrivateProject');
	}
	print '</td></tr>';

	if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES) && !empty($object->usage_opportunity)) {
		// Opportunity status
		print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
		$code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
		if ($code) {
			print $langs->trans("OppStatus".$code);
		}
		print '</td></tr>';

		// Opportunity percent
		print '<tr><td>'.$langs->trans("OpportunityProbability").'</td><td>';
		if (strcmp($object->opp_percent, '')) {
			print price($object->opp_percent, 0, $langs, 1, 0).' %';
		}
		print '</td></tr>';

		// Opportunity Amount
		print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
		/*if ($object->opp_status)
		{
		   print price($obj->opp_amount, 1, $langs, 1, 0, -1, $conf->currency);
		}*/
		if (strcmp($object->opp_amount, '')) {
			print price($object->opp_amount, 0, $langs, 1, 0, -1, $conf->currency);
		}
		print '</td></tr>';

		// Opportunity Weighted Amount
		print '<tr><td>'.$langs->trans('OpportunityWeightedAmount').'</td><td>';
		if (strcmp($object->opp_amount, '') && strcmp($object->opp_percent, '')) {
			print price($object->opp_amount * $object->opp_percent / 100, 0, $langs, 1, 0, -1, $conf->currency);
		}
		print '</td></tr>';
	}

	// Date start - end
	print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
	$start = dol_print_date($object->date_start, 'day');
	print ($start ? $start : '?');
	$end = dol_print_date($object->date_end, 'day');
	print ' - ';
	print ($end ? $end : '?');
	if ($object->hasDelay()) {
		print img_warning("Late");
	}
	print '</td></tr>';

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (strcmp($object->budget_amount, '')) {
		print price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
	}
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';

	if ($action == 'edit') {
		print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';
	}

	print '<table class="border tableforfield" width="100%">';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	// Categories
	if ($conf->categorie->enabled) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
		print "</td></tr>";
	}

	if ($action == 'edit' && $permissiontoadd) {
		//Allow unknown people to suggest conferences
		print '<tr><td class="valignmiddle">' . $langs->trans("AllowUnknownPeopleSuggestConf") . $form->textwithpicto('', $langs->trans("AllowUnknownPeopleSuggestConfHelp")) . '</td><td>';
		print '<input type="checkbox" name="accept_conference_suggestions"' . (GETPOSTISSET('accept_conference_suggestions') ? (GETPOST('accept_conference_suggestions', 'alpha') != '' ? ' checked="checked"' : '') : ($object->accept_conference_suggestions ? ' checked="checked"' : '')) . '"> ';
		print "</td></tr>";

		//Allow unknown people to suggest booth
		print '<tr><td class="valignmiddle">' . $langs->trans("AllowUnknownPeopleSuggestBooth") . $form->textwithpicto('', $langs->trans("AllowUnknownPeopleSuggestBoothHelp")) . '</td><td>';
		print '<input type="checkbox" name="accept_booth_suggestions"' . (GETPOSTISSET('accept_booth_suggestions') ? (GETPOST('accept_booth_suggestions', 'alpha') != '' ? ' checked="checked"' : '') : ($object->accept_booth_suggestions ? ' checked="checked"' : '')) . '"> ';
		print "</td></tr>";

		//Price of registration
		print '<tr><td class="valignmiddle">' . $langs->trans("PriceOfRegistration") . '</td><td>';
		print '<input size="5" type="text" name="price_registration" value="'.(GETPOSTISSET('price_registration') ? GETPOST('price_registration') : (strcmp($object->price_registration, '') ? price2num($object->price_registration) : '')).'">';
		print "</td></tr>";

		//Price of registration
		print '<tr><td class="valignmiddle">' . $langs->trans("PriceOfBooth") . '</td><td>';
		print '<input size="5" type="text" name="price_booth" value="'.(GETPOSTISSET('price_booth') ? GETPOST('price_booth') : (strcmp($object->price_booth, '') ? price2num($object->price_booth) : '')).'">';
		print "</td></tr>";
	} else {
		//Allow unknown people to suggest conferences
		print '<tr><td class="valignmiddle">' . $langs->trans("AllowUnknownPeopleSuggestConf") . $form->textwithpicto('', $langs->trans("AllowUnknownPeopleSuggestConfHelp")) . '</td><td>';
		print '<input type="checkbox" disabled name="accept_conference_suggestions"' . (GETPOSTISSET('accept_conference_suggestions') ? (GETPOST('accept_conference_suggestions', 'alpha') != '' ? ' checked="checked"' : '') : ($object->accept_conference_suggestions ? ' checked="checked"' : '')) . '"> ';
		print "</td></tr>";

		//Allow unknown people to suggest booth
		print '<tr><td class="valignmiddle">' . $langs->trans("AllowUnknownPeopleSuggestBooth") . $form->textwithpicto('', $langs->trans("AllowUnknownPeopleSuggestBoothHelp")) . '</td><td>';
		print '<input type="checkbox" disabled name="accept_booth_suggestions"' . (GETPOSTISSET('accept_booth_suggestions') ? (GETPOST('accept_booth_suggestions', 'alpha') != '' ? ' checked="checked"' : '') : ($object->accept_booth_suggestions ? ' checked="checked"' : '')) . '"> ';
		print "</td></tr>";

		//Price of registration
		print '<tr><td class="valignmiddle">' . $langs->trans("PriceOfRegistration") . '</td><td>';
		if (strcmp($object->price_registration, '')) {
			print price($object->price_registration, 0, $langs, 1, 0, 0, $conf->currency);
		}
		print "</td></tr>";

		//Price of registration
		print '<tr><td class="valignmiddle">' . $langs->trans("PriceOfBooth") . '</td><td>';
		if (strcmp($object->price_booth, '')) {
			print price($object->price_booth, 0, $langs, 1, 0, 0, $conf->currency);
		}
		print "</td></tr>";
	}

	//ICS Link
	print '<tr><td class="valignmiddle">'.$langs->trans("EventOrganizationICSLink").'</td><td>';
	//TODO ICS Link
	//print '<a href="ICSLink">ICS</a>';
	print "</td></tr>";

	print '</table>';

	print '</table>';

	print '</div>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';
}

print dol_get_fiche_end();

if ($action == 'edit' && $permissiontoadd > 0) {
	print '<div class="center">';
	print '<input name="update" class="button" type="submit" value="'.$langs->trans("Save").'">&nbsp; &nbsp; &nbsp;';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}


/*
	 * Actions Buttons
	 */
print '<div class="tabsAction">';
$parameters = array();
$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
// modified by hook
if (empty($reshook)) {
	// Modify
	if ($object->statut != Project::STATUS_CLOSED && $action=='') {
		if ($permissiontoadd > 0) {
			print '<a class="butAction" href="event.php?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('Modify').'</a>';
		}
	}
}

// End of page
llxFooter();
$db->close();
