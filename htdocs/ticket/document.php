<?php
/* Copyright (C) 2002-2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013-2016      Jean-François Ferry  <hello@librethic.io>
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
 *  \file       htdocs/ticket/document.php
 *  \ingroup    ticket
 *  \brief      files linked to a ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket", "mails"));

$id       = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
$ref      = GETPOST('ref', 'alpha');
$track_id = GETPOST('track_id', 'alpha');
$action   = GETPOST('action', 'alpha');
$confirm  = GETPOST('confirm', 'alpha');

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "position_name";
}

$object = new Ticket($db);
$result = $object->fetch($id, $ref, $track_id);

if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
} else {
	$upload_dir = $conf->ticket->dir_output."/".dol_sanitizeFileName($object->ref);
}

$permissiontoadd = $user->rights->ticket->write;	// Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles

// Security check - Protection if external user
$result = restrictedArea($user, 'ticket', $object->id);

// restrict access for externals users
if ($user->socid > 0 && ($object->fk_soc != $user->socid)) {
	accessforbidden();
}
// or for unauthorized internals users
if (!$user->socid && !empty($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) && $object->fk_user_assign != $user->id && empty($user->rights->ticket->manage)) {
	accessforbidden();
}



/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';



/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans("TicketDocumentsLinked").' - '.$langs->trans("Files"), $help_url);

if ($object->id) {
	/*
	 * Show tabs
	 */
	if ($socid > 0) {
		$object->fetch_thirdparty();
		$head = societe_prepare_head($object->thirdparty);
		print dol_get_fiche_head($head, 'ticket', $langs->trans("ThirdParty"), 0, 'company');
		dol_banner_tab($object->thirdparty, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');
		print dol_get_fiche_end();
	}

	if (!$user->socid && !empty($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY)) {
		$object->next_prev_filter = "te.fk_user_assign = '".$user->id."'";
	} elseif ($user->socid > 0) {
		$object->next_prev_filter = "te.fk_soc = '".$user->socid."'";
	}

	$head = ticket_prepare_head($object);

	print dol_get_fiche_head($head, 'tabTicketDocument', $langs->trans("Ticket"), 0, 'ticket');

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $object->subject;
	// Author
	if ($object->fk_user_create > 0) {
		$morehtmlref .= '<br>'.$langs->trans("CreatedBy").'  : ';

		$langs->load("users");
		$fuser = new User($db);
		$fuser->fetch($object->fk_user_create);
		$morehtmlref .= $fuser->getNomUrl(-1);
	}
	if (!empty($object->origin_email)) {
		$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';
		$morehtmlref .= $object->origin_email.' <small>('.$langs->trans("TicketEmailOriginIssuer").')</small>';
	}

	// Thirdparty
	if (!empty($conf->societe->enabled)) {
		$morehtmlref .= '<br>'.$langs->trans('ThirdParty');
		/*if ($action != 'editcustomer' && $object->fk_statut < 8 && !$user->socid && $user->rights->ticket->write) {
			$morehtmlref.='<a class="editfielda" href="' . $url_page_current . '?action=editcustomer&token='.newToken().'&track_id=' . $object->track_id . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a>';
		}*/
		$morehtmlref .= ' : ';
		if ($action == 'editcustomer') {
			$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, 'editcustomer', '', 1, 0, 0, array(), 1);
		} else {
			$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, 'none', '', 1, 0, 0, array(), 1);
		}
	}

	// Project
	if (!empty($conf->projet->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($user->rights->ticket->write) {
			if ($action != 'classify') {
				//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
				$morehtmlref .= ' : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
			} else {
				$morehtmlref .= '';
			}
		}
	}

	$morehtmlref .= '</div>';

	$linkback = '<a href="'.dol_buildpath('/ticket/list.php', 1).'"><strong>'.$langs->trans("BackToList").'</strong></a> ';

	dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

	print dol_get_fiche_end();

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	//$object->ref = $object->track_id;	// For compatibility we use track ID for directory
	$modulepart = 'ticket';
	$permissiontoadd = $user->rights->ticket->write;
	$permtoedit = $user->rights->ticket->write;
	$param = '&id='.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
