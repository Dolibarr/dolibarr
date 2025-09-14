<?php
/* Copyright (C) 2005-2012	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012	Juanjo Menent	<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry	<florian.henry@open-concept.pro>
 * Copyright (C) 2017       Ferran Marcet   <fmarcet@2byte.es>
 * Copyright (C) 2024       Frédéric France <frederic.france@free.fr>
 * Copyright (C) 2025		MDW				<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Charlene Benke	<charlene@patas-monkey.com>
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
 *	\file       htdocs/ticket/note.php
 *	\ingroup    ticket
 *	\brief      Fiche d'information sur une ticket
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'ticket'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$hookmanager->initHooks(array('ticketnote'));
$result = restrictedArea($user, 'ticket', $id, 'ticket');

$object = new Ticket($db);
$object->fetch($id, $ref);

$permissiontoadd = $user->hasRight('ticket', 'write');
$permissionnote = $user->hasRight('ticket', 'write'); // Used by the include of actions_setnotes.inc.php

// Store current page url
$url_page_current = DOL_URL_ROOT.'/ticket/document.php';


/*
 * Actions
 */

$reshook = $hookmanager->executeHooks('doActions', array(), $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
if (empty($reshook)) {
	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be 'include', not 'include_once'
}


/*
 * View
 */

llxHeader('', $langs->trans("Ticket"), '', '', 0, 0, '', '', '', 'mod-ticket page-card_note');

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$object->fetch_thirdparty();

	$head = ticket_prepare_head($object);
	print dol_get_fiche_head($head, 'note', $langs->trans('TicketCard'), -1, 'ticket');

	// Ticket card
	$linkback = '<a href="'.DOL_URL_ROOT.'/ticket/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $object->subject;
	// Author
	if ($object->fk_user_create > 0) {
		$morehtmlref .= '<br>';
		//$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("CreatedBy").'</span> ';

		$fuser = new User($db);
		$fuser->fetch($object->fk_user_create);
		$morehtmlref .= $fuser->getNomUrl(-1);
	} elseif (!empty($object->email_msgid)) {
		$morehtmlref .= '<br>';
		//$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("CreatedBy").'</span> ';
		$morehtmlref .= img_picto('', 'email', 'class="paddingrightonly"');
		$morehtmlref .= dol_escape_htmltag($object->origin_email).' <small class="hideonsmartphone opacitymedium">('.$form->textwithpicto($langs->trans("CreatedByEmailCollector"), $langs->trans("EmailMsgID").': '.$object->email_msgid).')</small>';
	} elseif (!empty($object->origin_email)) {
		$morehtmlref .= '<br>';
		//$morehtmlref .= '<span class="opacitymedium">'.$langs->trans("CreatedBy").'</span> ';
		$morehtmlref .= img_picto('', 'email', 'class="paddingrightonly"');
		$morehtmlref .= dol_escape_htmltag($object->origin_email).' <small class="hideonsmartphone opacitymedium">('.$langs->trans("CreatedByPublicPortal").')</small>';
	}

	// Thirdparty
	if (isModEnabled("societe")) {
		$morehtmlref .= '<br>';
		$morehtmlref .= img_picto($langs->trans("ThirdParty"), 'company', 'class="pictofixedwidth"');
		if ($action != 'editcustomer' && $permissiontoadd) {
			$morehtmlref .= '<a class="editfielda" href="'.$url_page_current.'?action=editcustomer&token='.newToken().'&track_id='.$object->track_id.'">'.img_edit($langs->transnoentitiesnoconv('SetThirdParty'), 0).'</a> ';
		}
		$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, (string) $object->socid, $action == 'editcustomer' ? 'editcustomer' : 'none', '', 1, 0, 0, array(), 1);
	}

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		if (0) {	// @phpstan-ignore-line
			$morehtmlref .= '<br>';
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, (string) $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$morehtmlref .= '<br>';
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

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	$cssclass = "titlefield";
	include DOL_DOCUMENT_ROOT.'/core/tpl/notes.tpl.php';

	print '</div>';

	print dol_get_fiche_end();
}

llxFooter();
$db->close();
