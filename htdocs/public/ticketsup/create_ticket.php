<?php
/*
 * Copyright (C) - 2013-2016    Jean-François FERRY    <hello@librethic.io>
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
 *     Display public form to add new ticket
 *
 *    @package ticketsup
 */
if (!defined('NOREQUIREUSER')) {
    define('NOREQUIREUSER', '1');
}

//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (!defined('NOTOKENRENEWAL')) {
    define('NOTOKENRENEWAL', '1');
}

if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
// If there is no menu to show
if (!defined('NOREQUIREHTML')) {
    define('NOREQUIREHTML', '1');
}
// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
define("NOLOGIN", 1); // This means this output page does not require to be logged.
define("NOCSRFCHECK", 1); // We accept to go on this page from external web site.

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/class/actions_ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/class/html.formticketsup.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/lib/ticketsup.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("mails");
$langs->load("ticketsup");

// Get parameters
$id = GETPOST('id', 'int');
$msg_id = GETPOST('msg_id', 'int');

$action = GETPOST('action', 'alpha');

$object = new Ticketsup($db);

/*
 * Add file in email form
 */
if (GETPOST('addfile') && !GETPOST('add_ticket')) {
    ////$res = $object->fetch('',GETPOST('track_id'));
    ////if($res > 0)
    ////{
    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    // Set tmp directory TODO Use a dedicated directory for temp mails files
    $vardir = $conf->ticketsup->dir_output;
    $upload_dir_tmp = $vardir . '/temp';
    if (!dol_is_dir($upload_dir_tmp)) {
        dol_mkdir($upload_dir_tmp);
    }
    dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile');
    $action = 'create_ticket';
    ////}
}

/*
 * Remove file in email form
 */
if (GETPOST('removedfile') && !GETPOST('add_ticket')) {
    ////$res = $object->fetch('',GETPOST('track_id'));
    ////if($res > 0)
    ////{
    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

    // Set tmp directory
    $vardir = $conf->ticketsup->dir_output . '/';
    $upload_dir_tmp = $vardir . '/temp';

    // TODO Delete only files that was uploaded from email form
    dol_remove_file_process($_POST['removedfile'], 0);
    $action = 'create_ticket';
    ////}
}
if ($action == 'create_ticket' && GETPOST('add_ticket')) {
    $error = 0;
    $origin_email = GETPOST('email', 'alpha');
    if (empty($origin_email)) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
        $action = '';
    } else {
        // Search company saved with email
        $searched_companies = $object->searchSocidByEmail($origin_email, '0');

        // Chercher un contact existant avec cette adresse email
        // Le premier contact trouvé est utilisé pour déterminer le contact suivi
        $contacts = $object->searchContactByEmail($origin_email);

        // Option to require email exists to create ticket
        if (!empty($conf->global->TICKETS_EMAIL_MUST_EXISTS) && !$contacts[0]->socid) {
            $error++;
            array_push($object->errors, $langs->trans("ErrorEmailMustExistToCreateTicket"));
            $action = '';
        }
    }

    if (!GETPOST("subject")) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")));
        $action = '';
    } elseif (!GETPOST("message")) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("message")));
        $action = '';
    }

    // Check email address
    if (!isValidEmail($origin_email)) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorBadEmailAddress", $langs->transnoentities("email")));
        $action = '';
    }

    if (!$error) {
        $object->db->begin();

        $object->track_id = generate_random_id(16);

        $object->subject = GETPOST("subject");
        $object->message = GETPOST("message");
        $object->origin_email = $origin_email;

        $object->type_code = GETPOST("type_code");
        $object->category_code = GETPOST("category_code");
        $object->severity_code = GETPOST("severity_code");
        if (is_array($searched_companies)) {
            $object->fk_soc = $searched_companies[0]->id;
        }

        if (is_array($contacts) and count($contacts) > 0) {
            $object->fk_soc = $contacts[0]->socid;
            $usertoassign = $contacts[0]->id;
        }

        if (!empty($conf->global->TICKETS_EXTRAFIELDS_PUBLIC) && $conf->global->TICKETS_EXTRAFIELDS_PUBLIC == "1") {
            $extrafields = new ExtraFields($db);
            $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
            $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
        }

        // Generate new ref
        $object->ref = $object->getDefaultRef();
        if (!is_object($user)) {
            $user = new User($db);
        }
        $id = $object->create($user, 1); // Disable trigger for email (send by this page)
        if ($id <= 0) {
            $error++;
            $errors = ($object->error ? array($object->error) : $object->errors);
            array_push($object->errors, $object->error ? array($object->error) : $object->errors);
            $action = 'create_ticket';
        }

        if (!$error && $id > 0) {
            if ($usertoassign > 0) {
                $object->add_contact($usertoassign, "SUPPORTCLI", 'external', $notrigger = 0);
            }

            $object->db->commit();

            $res = $object->fetch($id);
            if ($res) {
                // Create form object
                include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
                $formmail = new FormMail($db);

                // Init to avoid errors
                $filepath = array();
                $filename = array();
                $mimetype = array();

                $attachedfiles = $formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send email to customer
                $subject = '[' . $conf->global->MAIN_INFO_SOCIETE_NOM . '] ' . $langs->transnoentities('TicketNewEmailSubject');
                $message .= ($conf->global->TICKETS_MESSAGE_MAIL_NEW ? $conf->global->TICKETS_MESSAGE_MAIL_NEW : $langs->transnoentities('TicketNewEmailBody')) . "\n\n";
                $message .= $langs->transnoentities('TicketNewEmailBodyInfosTicket') . "\n";

                $url_public_ticket = ($conf->global->TICKETS_URL_PUBLIC_INTERFACE ? $conf->global->TICKETS_URL_PUBLIC_INTERFACE . '/' : dol_buildpath('/ticketsup/public/view.php', 2)) . '?track_id=' . $object->track_id;
                $infos_new_ticket = $langs->transnoentities('TicketNewEmailBodyInfosTrackId', '<a href="' . $url_public_ticket . '">' . $object->track_id . '</a>') . "\n";
                $infos_new_ticket .= $langs->transnoentities('TicketNewEmailBodyInfosTrackUrl') . "\n\n";

                $message .= dol_nl2br($infos_new_ticket);
                $message .= $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE ? $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE : $langs->transnoentities('TicketMessageMailSignatureText');

                $sendto = GETPOST('email');

                $from = $conf->global->MAIN_INFO_SOCIETE_NOM . '<' . $conf->global->TICKETS_NOTIFICATION_EMAIL_FROM . '>';
                $replyto = $from;

                $message = dol_nl2br($message);

                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                }
                include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1);
                if ($mailfile->error) {
                    setEventMessage($mailfile->error, 'errors');
                } else {
                    $result = $mailfile->sendfile();
                }
                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                }

                /* Send email to admin */
                $sendto = $conf->global->TICKETS_NOTIFICATION_EMAIL_TO;
                $subject = '[' . $conf->global->MAIN_INFO_SOCIETE_NOM . '] ' . $langs->transnoentities('TicketNewEmailSubjectAdmin');
                $message_admin = $langs->transnoentities('TicketNewEmailBodyAdmin', $object->track_id) . "\n\n";
                $message_admin .= '<ul><li>' . $langs->trans('Title') . ' : ' . $object->subject . '</li>';
                $message_admin .= '<li>' . $langs->trans('Type') . ' : ' . $object->type_label . '</li>';
                $message_admin .= '<li>' . $langs->trans('Category') . ' : ' . $object->category_label . '</li>';
                $message_admin .= '<li>' . $langs->trans('Severity') . ' : ' . $object->severity_label . '</li>';
                $message_admin .= '<li>' . $langs->trans('From') . ' : ' . $object->origin_email . '</li>';
                if (is_array($object->array_options) && count($object->array_options) > 0) {
                    foreach ($object->array_options as $key => $value) {
                        $message_admin .= '<li>' . $langs->trans($key) . ' : ' . $value . '</li>';
                    }
                }
                $message_admin .= '</ul>';
                $message_admin .= '<p>' . $langs->trans('Message') . ' : <br>' . $object->message . '</p>';
                $message_admin .= '<p><a href="' . dol_buildpath('/ticketsup/card.php', 2) . '?track_id=' . $object->track_id . '">' . $langs->trans('SeeThisTicketIntomanagementInterface') . '</a></p>';

                $from = $conf->global->MAIN_INFO_SOCIETE_NOM . '<' . $conf->global->TICKETS_NOTIFICATION_EMAIL_FROM . '>';
                $replyto = $from;

                $message_admin = dol_nl2br($message_admin);

                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                }
                include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject, $sendto, $from, $message_admin, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1);
                if ($mailfile->error) {
                    setEventMessage($mailfile->error, 'errors');
                } else {
                    $result = $mailfile->sendfile();
                }
                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                }

                // Copy files into ticket directory
                $destdir = $conf->ticketsup->dir_output . '/' . $object->track_id;
                if (!dol_is_dir($destdir)) {
                    dol_mkdir($destdir);
                }
                foreach ($filename as $i => $val) {
                    dol_move($filepath[$i], $destdir . '/' . $filename[$i], 0, 1);
                    $formmail->remove_attached_files($i);
                }
            }

            setEventMessage($langs->trans('YourTicketSuccessfullySaved'));
            $action = "infos_success";
        } else {
            $object->db->rollback();
            setEventMessage($object->errors, 'errors');
            $action = 'create_ticket';
        }
    } else {
        setEventMessage($object->errors, 'errors');
    }
}

/***************************************************
 * PAGE
 *
 ****************************************************/

$arrayofjs = array();
$arrayofcss = array('/opensurvey/css/style.css', '/ticketsup/css/styles.css', '/ticketsup/css/bg.css.php');
llxHeaderTicket($langs->trans("CreateTicket"), "", 0, 0, $arrayofjs, $arrayofcss);

$form = new Form($db);
$formticket = new FormTicketsup($db);

if (!$conf->global->TICKETS_ENABLE_PUBLIC_INTERFACE) {
    print '<div class="error">' . $langs->trans('TicketPublicInterfaceForbidden') . '</div>';
    $db->close();
    exit();
}

print '<div style="width:60%; margin: 0 auto;">';

if ($action != "infos_success") {
    $formticket->withfromsocid = isset($socid) ? $socid : $user->societe_id;
    $formticket->withtitletopic = 1;
    $formticket->withcompany = 0;
    $formticket->withusercreate = 1;
    $formticket->fk_user_create = 0;
    $formticket->withemail = 1;
    $formticket->ispublic = 1;
    $formticket->withfile = 2;
    if (!empty($conf->global->TICKETS_EXTRAFIELDS_PUBLIC)) {
        $formticket->withextrafields = $conf->global->TICKETS_EXTRAFIELDS_PUBLIC;
    }
    $formticket->action = 'create_ticket';

    $formticket->param = array('returnurl' => $_SERVER['PHP_SELF']);

    if (empty($defaultref)) {
        $defaultref = '';
    }

    print load_fiche_titre($langs->trans('NewTicket'), '', 'ticketsup-32@ticketsup', 0);

    print '<div class="info">' . $langs->trans('TicketPublicInfoCreateTicket') . '</div>';
    $formticket->showForm();
} else {
    print '<div class="ok">' . $langs->trans('MesgInfosPublicTicketCreatedWithTrackId', '<strong>' . $object->track_id . '</strong>');
    print '<br>';
    print $langs->trans('PleaseRememberThisId');
}
print '</div>';

/***************************************************
 * LINKED OBJECT BLOCK
 *
 * Put here code to view linked object
 ****************************************************/
//$somethingshown=$object->showLinkedObjectBlock();

// End of page
$db->close();
llxFooter('');
