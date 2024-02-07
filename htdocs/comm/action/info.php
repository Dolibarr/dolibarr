<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/comm/action/info.php
 *      \ingroup    agenda
 *		\brief      Page des informations d'une action
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->load("commercial");

$id = GETPOST('id', 'int');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('actioncard', 'globalcard'));

// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$result = restrictedArea($user, 'agenda', $id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}

$usercancreate = $user->hasRight('agenda', 'allactions', 'create') || (($object->authorid == $user->id || $object->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'));


/*
 * View
 */

$form = new Form($db);

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("Agenda"), $help_url);

$object = new ActionComm($db);
$object->fetch($id);
$object->info($object->id);

$head = actions_prepare_head($object);
print dol_get_fiche_head($head, 'info', $langs->trans("Action"), -1, 'action');

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

print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
