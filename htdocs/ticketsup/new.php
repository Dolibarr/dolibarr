<?php
/* Copyright (C) - 2013-2016    Jean-François FERRY    <hello@librethic.io>
 *                    2016            Christophe Battarel <christophe@altairis.fr>
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
 * Display form to add new ticket
 *
 * @package ticketsup
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . 'class/actions_ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticketsup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticketsup.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("ticketsup@ticketsup");

// Get parameters
$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');
$contactid = GETPOST('contactid', 'int');
$msg_id = GETPOST('msg_id', 'int');
$notNotifyTiers = GETPOST("not_notify_tiers_at_create", 'alpha');
$notnotifytiersatcreate = !empty($notNotifyTiers);

$action = GETPOST('action', 'alpha', 3);

// Protection if external user
if (!$user->rights->ticketsup->read || !$user->rights->ticketsup->write) {
    accessforbidden();
}

$object = new ActionsTicketsup($db);

$object->doActions($action);

/***************************************************
 * PAGE
 *
 * Put here all code to build page
 ****************************************************/
$help_url = 'FR:DocumentationModuleTicket';
$page_title = $object->getTitle($action);
llxHeader('', $page_title, $help_url);

$form = new Form($db);

if ($action == 'create_ticket') {
    $formticket = new FormTicketsup($db);

    print load_fiche_titre($langs->trans('NewTicket'), '', 'img/ticketsup-32.png', 1);

    $formticket->withfromsocid = $socid ? $socid : $user->societe_id;
    $formticket->withfromcontactid = $contactid ? $contactid : '';
    $formticket->withtitletopic = 1;
    $formticket->withnotnotifytiersatcreate = $notnotifytiersatcreate;
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

/***************************************************
 * LINKED OBJECT BLOCK
 *
 * Put here code to view linked object
 ****************************************************/
//$somethingshown=$object->showLinkedObjectBlock();

// End of page
llxFooter('');
$db->close();
