<?php
/* Copyright (C) 2013-2016 Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
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
 * 	\file       htdocs/ticket/new.php
 *  \ingroup	ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket'));

// Get parameters
$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
$contactid = GETPOST('contactid', 'int');
$msg_id = GETPOST('msg_id', 'int');
$notifyTiers = GETPOST("notify_tiers_at_create", 'alpha');

$action = GETPOST('action', 'alpha', 3);

// Protection if external user
if (!$user->rights->ticket->read || !$user->rights->ticket->write) {
    accessforbidden();
}

$object = new Ticket($db);
$actionobject = new ActionsTicket($db);


/*
 * Actions
 */

$actionobject->doActions($action, $object);



/*
 * View
 */

$form = new Form($db);

$help_url = 'FR:DocumentationModuleTicket';
$page_title = $actionobject->getTitle($action);
llxHeader('', $page_title, $help_url);


if ($action == 'create_ticket') {
    $formticket = new FormTicket($db);

    print load_fiche_titre($langs->trans('NewTicket'), '', 'title_ticket');

    $formticket->withfromsocid = $socid ? $socid : $user->societe_id;
    $formticket->withfromcontactid = $contactid ? $contactid : '';
    $formticket->withtitletopic = 1;
    $formticket->withnotifytiersatcreate = ($notifyTiers?1:0);
    $formticket->withusercreate = 1;
    $formticket->withref = 1;
    $formticket->fk_user_create = $user->id;
    $formticket->withfile = 2;
    $formticket->withextrafields = 1;
    $formticket->param = array('origin' => GETPOST('origin'), 'originid' => GETPOST('originid'));
    if (empty($defaultref)) {
        $defaultref = '';
    }

    $formticket->showForm(1);
}

//$somethingshown=$object->showLinkedObjectBlock();

// End of page
llxFooter('');
$db->close();
