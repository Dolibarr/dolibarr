<?php
/* Copyright (C) - 2013-2016 Jean-François FERRY    <hello@librethic.io>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/ticket/history.php
 *    	\ingroup	ticket
 *    	\brief		History of ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

if (!class_exists('Contact')) {
    include DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket'));

// Get parameters
$id = GETPOST('id', 'int');
$track_id = GETPOST('track_id', 'alpha', 3);
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha', 3);

// Security check
if (!$user->rights->ticket->read) {
    accessforbidden();
}

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

if (!$action) {
	$action = 'view';
}

$object = new Ticket($db);
$object->fetch($id, $ref, $track_id);


/*
 * Actions
 */

$actionobject = new ActionsTicket($db);

$actionobject->doActions($action, $object);



/*
 * View
 */

$help_url = 'FR:DocumentationModuleTicket';
$page_title = $actionobject->getTitle($action);
llxHeader('', $page_title, $help_url);

$userstat = new User($db);
$form = new Form($db);
$formticket = new FormTicket($db);

if ($action == 'view') {
	$res = $object->fetch($id, $ref, $track_id);

    if ($res > 0) {
        // restrict access for externals users
        if ($user->societe_id > 0 && ($object->fk_soc != $user->societe_id)
        ) {
            accessforbidden('', 0);
        }
        // or for unauthorized internals users
        if (!$user->societe_id && ($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY && $object->fk_user_assign != $user->id) && !$user->rights->ticket->manage) {
            accessforbidden('', 0);
        }

        if ($socid > 0) {
            $object->fetch_thirdparty();
            $head = societe_prepare_head($object->thirdparty);
            dol_fiche_head($head, 'ticket', $langs->trans("ThirdParty"), 0, 'company');
            dol_banner_tab($object->thirdparty, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
            dol_fiche_end();
        }

        if (!$user->societe_id && $conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) {
            $object->next_prev_filter = "te.fk_user_assign = '" . $user->id . "'";
        } elseif ($user->societe_id > 0) {
            $object->next_prev_filter = "te.fk_soc = '" . $user->societe_id . "'";
        }
        $head = ticket_prepare_head($object);

        dol_fiche_head($head, 'tabTicketLogs', $langs->trans("Ticket"), 0, 'ticket');

        $morehtmlref ='<div class="refidno">';
        $morehtmlref.= $object->subject;
        // Author
        if ($object->fk_user_create > 0) {
        	$morehtmlref .= '<br>' . $langs->trans("CreatedBy") . '  ';

        	$langs->load("users");
        	$fuser = new User($db);
        	$fuser->fetch($object->fk_user_create);
        	$morehtmlref .= $fuser->getNomUrl(0);
        }
        if (!empty($object->origin_email)) {
        	$morehtmlref .= '<br>' . $langs->trans("CreatedBy") . ' ';
        	$morehtmlref .= $object->origin_email . ' <small>(' . $langs->trans("TicketEmailOriginIssuer") . ')</small>';
        }
        $morehtmlref.='</div>';

        $linkback = '<a href="' . dol_buildpath('/ticket/list.php', 1) . '"><strong>' . $langs->trans("BackToList") . '</strong></a> ';

        dol_banner_tab($object, 'ref', $linkback, ($user->societe_id ? 0 : 1), 'ref', 'ref', $morehtmlref);

        dol_fiche_end();

        print '<div class="fichecenter">';
        // Logs list
        print load_fiche_titre($langs->trans('TicketHistory'), '', 'history@ticket');
        $actionobject->viewTimelineTicketLogs(true, $object);
        print '</div><!-- fichecenter -->';
        print '<br style="clear: both">';
    }
} // End action view

// End of page
llxFooter('');
$db->close();
