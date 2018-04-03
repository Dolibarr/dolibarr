<?php
/* Copyright (C) 2013-2016 Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * 	\file       htdocs/ticketsup/new.php
 *  \ingroup	ticketsup
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ticketsup/class/actions_ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticketsup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticketsup.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("ticketsup");

// Get parameters
$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
$contactid = GETPOST('contactid', 'int');
$msg_id = GETPOST('msg_id', 'int');
$notifyTiers = GETPOST("notify_tiers_at_create", 'alpha');

$action = GETPOST('action', 'alpha', 3);

// Protection if external user
if (!$user->rights->ticketsup->read || !$user->rights->ticketsup->write) {
    accessforbidden();
}

$object = new Ticketsup($db);
$actionobject = new ActionsTicketsup($db);


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
    $formticket = new FormTicketsup($db);

    print load_fiche_titre($langs->trans('NewTicket'), '', 'title_ticketsup');

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

    $formticket->showForm();
}

//$somethingshown=$object->showLinkedObjectBlock();

// End of page
llxFooter('');
$db->close();
