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

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (isModEnabled('project')) {
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
}

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket", "mails"));

$id       = GETPOSTINT('id');
$socid = GETPOSTINT('socid');
$ref      = GETPOST('ref', 'alpha');
$track_id = GETPOST('track_id', 'alpha');
$action   = GETPOST('action', 'alpha');
$confirm  = GETPOST('confirm', 'alpha');

// Store current page url
$url_page_current = DOL_URL_ROOT.'/ticket/document.php';

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
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

$hookmanager->initHooks(array('documentticketcard', 'globalcard'));
$object = new Ticket($db);
$result = $object->fetch($id, $ref, $track_id);

if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
} else {
	$upload_dir = $conf->ticket->dir_output."/".dol_sanitizeFileName($object->ref);
}

// Security check - Protection if external user
$result = restrictedArea($user, 'ticket', $object->id);

// restrict access for externals users
if ($user->socid > 0 && ($object->fk_soc != $user->socid)) {
	accessforbidden();
}
// or for unauthorized internals users
if (!$user->socid && getDolGlobalString('TICKET_LIMIT_VIEW_ASSIGNED_ONLY') && $object->fk_user_assign != $user->id && !$user->hasRight('ticket', 'manage')) {
	accessforbidden();
}

$permissiontoadd = $user->hasRight('ticket', 'write');	// Used by the include of actions_addupdatedelete.inc.php and actions_linkedfiles


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Set parent company
if ($action == 'set_thirdparty' && $user->hasRight('ticket', 'write')) {
	if ($object->fetch(GETPOSTINT('id'), '', GETPOST('track_id', 'alpha')) >= 0) {
		$result = $object->setCustomer(GETPOSTINT('editcustomer'));
		$url = $_SERVER["PHP_SELF"].'?track_id='.GETPOST('track_id', 'alpha');
		header("Location: ".$url);
		exit();
	}
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans("TicketDocumentsLinked").' - '.$langs->trans("Files"), $help_url, '', 0, 0, '', '', '', 'mod-ticket page-card_documents');

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

	if (!$user->socid && getDolGlobalString('TICKET_LIMIT_VIEW_ASSIGNED_ONLY')) {
		$object->next_prev_filter = "te.fk_user_assign = ".((int) $user->id);
	} elseif ($user->socid > 0) {
		$object->next_prev_filter = "te.fk_soc = ".((int) $user->socid);
	}

	$head = ticket_prepare_head($object);

	print dol_get_fiche_head($head, 'tabTicketDocument', $langs->trans("Ticket"), 0, 'ticket');

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= $object->subject;
	// Author
	if ($object->fk_user_create > 0) {
		$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';

		$fuser = new User($db);
		$fuser->fetch($object->fk_user_create);
		$morehtmlref .= $fuser->getNomUrl(-1);
	} elseif (!empty($object->email_msgid)) {
		$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';
		$morehtmlref .= img_picto('', 'email', 'class="paddingrightonly"');
		$morehtmlref .= dol_escape_htmltag($object->origin_email).' <small class="hideonsmartphone opacitymedium">('.$form->textwithpicto($langs->trans("CreatedByEmailCollector"), $langs->trans("EmailMsgID").': '.$object->email_msgid).')</small>';
	} elseif (!empty($object->origin_email)) {
		$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';
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
		$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, $action == 'editcustomer' ? 'editcustomer' : 'none', '', 1, 0, 0, array(), 1);
	}

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		if (0) {
			$morehtmlref .= '<br>';
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
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

	$linkback = '<a href="'.dol_buildpath('/ticket/list.php', 1).'"><strong>'.$langs->trans("BackToList").'</strong></a> ';

	dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

	print dol_get_fiche_end();

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	// same as above for every messages
	$sql = 'SELECT id FROM '.MAIN_DB_PREFIX.'actioncomm';
	$sql .= " WHERE fk_element = ".(int) $object->id." AND elementtype = 'ticket'";
	$resql = $db->query($sql);
	if ($resql) {
		$file_msg_array = array();
		$numrows = $db->num_rows($resql);
		for ($i=0; $i < $numrows; $i++) {
			$upload_msg_dir = $conf->agenda->dir_output.'/'.$db->fetch_row($resql)[0];
			$file_msg = dol_dir_list($upload_msg_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
			if (count($file_msg)) {
				$file_msg_array = array_merge($file_msg, $file_msg_array);
			}
		}
		if (count($file_msg_array)) {
			$filearray = array_merge($filearray, $file_msg_array);
		}
	}

	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	//$object->ref = $object->track_id;	// For compatibility we use track ID for directory
	$modulepart = 'ticket';
	$permissiontoadd = $user->hasRight('ticket', 'write');
	$permtoedit = $user->hasRight('ticket', 'write');
	$param = '&id='.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
