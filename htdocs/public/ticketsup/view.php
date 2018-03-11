<?php
/*  Copyright (C) - 2013-2016    Jean-François FERRY    <hello@librethic.io>
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
 *       \file       ticketsup/public/index.php
 *       \ingroup    ticketsup
 *       \brief      Public file to add and manage ticket
 */

if (!defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
    define("NOLOGIN", '1');
}
// If this page is public (can be called outside logged session)

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/class/actions_ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/class/html.formticketsup.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/lib/ticketsup.lib.php';

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("ticketsup");

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action = GETPOST('action', 'alpha', 3);
$email = GETPOST('email', 'alpha');

if (GETPOST('btn_view_ticket')) {
    unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
    $email = $_SESSION['email_customer'];
}

$object = new ActionsTicketsup($db);

if ($action == "view_ticket" || $action == "add_message" || $action == "close" || $action == "confirm_public_close" || $action == "new_public_message") {
    $error = 0;
    $display_ticket = false;
    if (!strlen($track_id)) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("TicketTrackId")));
        $action = '';
    }

    if (!strlen($email)) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
        $action = '';
    } else {
        if (!isValidEmail($email)) {
            $error++;
            array_push($object->errors, $langs->trans("ErrorEmailInvalid"));
            $action = '';
        }
    }

    if (!$error) {
        $ret = $object->fetch('', $track_id);
        if ($ret && $object->dao->id > 0) {
            // vérifie si l'adresse email est bien dans les contacts du ticket
            $contacts = $object->dao->liste_contact(-1, 'external');
            foreach ($contacts as $contact) {
                if ($contact['email'] == $email) {
                    $display_ticket = true;
                    $_SESSION['email_customer'] = $email;
                    break;
                } else {
                    $display_ticket = false;
                }
            }

            if ($object->dao->fk_soc > 0) {
                $object->dao->fetch_thirdparty();
            }

            if ($email == $object->dao->origin_email || $email == $object->dao->thirdparty->email) {
                $display_ticket = true;
                $_SESSION['email_customer'] = $email;
            }
        } else {
            $error++;
            array_push($object->errors, $langs->trans("ErrorTicketNotFound", $track_id));
            $action = '';
        }
    }

    if ($error) {
        setEventMessage($object->errors, 'errors');
        $action = '';
    }
}
$object->doActions($action);

/***************************************************
 * VIEW
 *
 ****************************************************/

$form = new Form($db);
$formticket = new FormTicketsup($db);

$arrayofjs = array();
$arrayofcss = array('/ticketsup/css/styles.css', '/ticketsup/css/bg.css.php');
llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);

if (!$conf->global->TICKETS_ENABLE_PUBLIC_INTERFACE) {
    print '<div class="error">' . $langs->trans('TicketPublicInterfaceForbidden') . '</div>';
    $db->close();
    exit();
}

print '<div style="margin: 0 auto; width:60%">';

if ($action == "view_ticket" || $action == "add_message" || $action == "close" || $action == "confirm_public_close") {
    if ($display_ticket) {
        // Confirmation close
        if ($action == 'close') {
            $ret = $form->form_confirm($_SERVER["PHP_SELF"] . "?track_id=" . $track_id, $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_public_close", '', '', 1);
            if ($ret == 'html') {
                print '<br>';
            }
        }

        print '<div id="form_view_ticket">';

        print '<table class="border" style="width:100%">';

        // Ref
        print '<tr><td style="width:40%">' . $langs->trans("Ref") . '</td><td>';
        print $object->dao->ref;
        print '</td></tr>';

        // Tracking ID
        print '<tr><td style="width:40%">' . $langs->trans("TicketTrackId") . '</td><td>';
        print $object->dao->track_id;
        print '</td></tr>';

        // Subject
        print '<tr><td><strong>' . $langs->trans("Subject") . '</strong></td><td>';
        print $object->dao->subject;
        print '</td></tr>';

        // Statut
        print '<tr><td>' . $langs->trans("Status") . '</td><td>';
        print $object->dao->getLibStatut(2);
        print '</td></tr>';

        // Type
        print '<tr><td>' . $langs->trans("Type") . '</td><td>';
        print $object->dao->type_label;
        print '</td></tr>';

        // Category
        print '<tr><td>' . $langs->trans("Category") . '</td><td>';
        print $object->dao->category_label;
        print '</td></tr>';

        // Severity
        print '<tr><td>' . $langs->trans("Severity") . '</td><td>';
        print $object->dao->severity_label;
        print '</td></tr>';

        // Creation date
        print '<tr><td>' . $langs->trans("DateCreation") . '</td><td>';
        print dol_print_date($object->dao->datec, 'dayhour');
        print '</td></tr>';

        // Author
        print '<tr><td>' . $langs->trans("Author") . '</td><td>';
        if ($object->dao->fk_user_create > 0) {
            $langs->load("users");
            $fuser = new User($db);
            $fuser->fetch($object->dao->fk_user_create);
            print $fuser->getFullName($langs);
        } else {
            print $object->dao->origin_email;
        }

        print '</td></tr>';

        // Read date
        if (!empty($object->dao->date_read)) {
            print '<tr><td>' . $langs->trans("TicketReadOn") . '</td><td>';
            print dol_print_date($object->dao->date_read, 'dayhour');
            print '</td></tr>';
        }

        // Close date
        if (!empty($object->dao->date_close)) {
            print '<tr><td>' . $langs->trans("TicketCloseOn") . '</td><td>';
            print dol_print_date($object->dao->date_close, 'dayhour');
            print '</td></tr>';
        }

        // User assigned
        print '<tr><td>' . $langs->trans("UserAssignedTo") . '</td><td>';
        if ($object->dao->fk_user_assign > 0) {
            $fuser = new User($db);
            $fuser->fetch($object->dao->fk_user_assign);
            print $fuser->getFullName($langs, 1);
        } else {
            print $langs->trans('None');
        }
        print '</td></tr>';

        // Progression
        print '<tr><td>' . $langs->trans("Progression") . '</td><td>';
        print ($object->dao->progress > 0 ? $object->dao->progress : '0') . '%';
        print '</td></tr>';

        print '</table>';

        print '</div>';

        print '<div style="clear: both; margin-top: 1.5em;"></div>';

        if ($action == 'add_message') {
            print load_fiche_titre($langs->trans('TicketAddMessage'), '', 'messages@ticketsup');

            $formticket = new FormTicketsup($db);

            $formticket->action = "new_public_message";
            $formticket->track_id = $object->dao->track_id;
            $formticket->id = $object->dao->id;

            $formticket->param = array('fk_user_create' => '-1');

            $formticket->withfile = 2;
            $formticket->showMessageForm('100%');
        } else {
            print '<form method="post" id="form_view_ticket_list" name="form_view_ticket_list" enctype="multipart/form-data" action="' . dol_buildpath('/ticketsup/public/list.php', 1) . '">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<input type="hidden" name="action" value="view_ticketlist">';
            print '<input type="hidden" name="track_id" value="'.$object->dao->track_id.'">';
            print '<input type="hidden" name="email" value="'.$_SESSION['email_customer'].'">';
            print '<input type="hidden" name="search_fk_status" value="non_closed">';
            print "</form>\n";

            print '<div class="tabsAction">';
            // List ticket
            print '<div class="inline-block divButAction"><a  class="butAction" href="javascript:$(\'#form_view_ticket_list\').submit();">' . $langs->trans('ViewMyTicketList') . '</a></div>';

            if ($object->dao->fk_statut < 8) {
                // New message
                print '<div class="inline-block divButAction"><a  class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=add_message&track_id=' . $object->dao->track_id . '">' . $langs->trans('AddMessage') . '</a></div>';

                // Close ticket
                if ($object->dao->fk_statut > 0 && $object->dao->fk_statut < 8) {
                    print '<div class="inline-block divButAction"><a  class="butAction" href="' . $_SERVER['PHP_SELF'] . '?action=close&track_id=' . $object->dao->track_id . '">' . $langs->trans('CloseTicket') . '</a></div>';
                }
            }

            print '</div>';
        }

        // Message list
        print load_fiche_titre($langs->trans('TicketMessagesList'), '', 'messages@ticketsup');
        $object->viewTicketMessages(false);

        print '<br>';

        // Logs list
        print load_fiche_titre($langs->trans('TicketHistory'), '', 'history@ticketsup');
        $object->viewTicketLogs(false);
    } else {
        print '<div class="error">Not Allowed<br><a href="' . $_SERVER['PHP_SELF'] . '?track_id=' . $object->dao->track_id . '">' . $langs->trans('Back') . '</a></div>';
    }
} else {
    print '<p style="text-align: center">' . $langs->trans("TicketPublicMsgViewLogIn") . '</p>';

    print '<div id="form_view_ticket">';
    print '<form method="post" name="form_view_ticket"  enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="view_ticket">';

    print '<p><label for="track_id" style="display: inline-block; width: 30%; "><span class="fieldrequired">' . $langs->trans("TicketTrackId") . '</span></label>';
    print '<input size="30" id="track_id" name="track_id" value="' . (GETPOST('track_id', 'alpha') ? GETPOST('track_id', 'alpha') : '') . '" />';
    print '</p>';

    print '<p><label for="email" style="display: inline-block; width: 30%; "><span class="fieldrequired">' . $langs->trans('Email') . '</span></label>';
    print '<input size="30" id="email" name="email" value="' . (GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $_SESSION['customer_email']) . '" />';
    print '</p>';

    print '<p style="text-align: center; margin-top: 1.5em;">';
    print '<input class="button" type="submit" name="btn_view_ticket" value="' . $langs->trans('ViewTicket') . '" />';
    print "</p>\n";

    print "</form>\n";
    print "</div>\n";
}

// End of page
llxFooter();
$db->close();
