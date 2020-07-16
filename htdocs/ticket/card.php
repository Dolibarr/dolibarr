<?php
/* Copyright (C) 2013-2016 Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2018      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *    \file     htdocs/ticket/card.php
 *    \ingroup 	ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
if (!empty($conf->projet->enabled)) {
    include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
    include_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
}
if (!empty($conf->contrat->enabled)) {
    include_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
    include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formcontract.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket"));

// Get parameters
$id        = GETPOST('id', 'int');
$socid     = GETPOST('socid', 'int');
$track_id  = GETPOST('track_id', 'alpha', 3);
$ref       = GETPOST('ref', 'alpha');
$projectid = GETPOST('projectid', 'int');
$cancel    = GETPOST('cancel', 'alpha');
$action    = GETPOST('action', 'aZ09');

$notifyTiers = GETPOST("notify_tiers_at_create", 'alpha');

// Initialize technical object to manage hooks of ticket. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('ticketcard', 'globalcard'));

$object = new Ticket($db);
$extrafields = new ExtraFields($db);

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key, 'alpha')) $search[$key] = GETPOST('search_'.$key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

//Select mail models is same action as add_message
if (GETPOST('modelselected', 'alpha')) {
	$action = 'presend';
}

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id || $track_id || $ref) {
	$res = $object->fetch($id, $ref, $track_id);
	if ($res >= 0)
	{
		$id = $object->id;
		$track_id = $object->track_id;
	}
}

// Store current page url
$url_page_current = DOL_URL_ROOT.'/ticket/card.php';

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$result = restrictedArea($user, 'ticket', $object->id);

$triggermodname = 'TICKET_MODIFY';
$permissiontoadd = $user->rights->ticket->write;

$actionobject = new ActionsTicket($db);

$now = dol_now();


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($cancel)
{
	if (!empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
	$action = 'view';
}

if (GETPOST('add', 'alpha') && $user->rights->ticket->write) {
    $error = 0;

    if (!GETPOST("subject", 'alphanohtml')) {
        $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")), null, 'errors');
        $action = 'create';
    } elseif (!GETPOST("message", 'restricthtml')) {
        $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")), null, 'errors');
        $action = 'create';
    }

    if (!$error) {
        $db->begin();

        $object->ref = GETPOST("ref", 'alphanohtml');
        $object->fk_soc = GETPOST("socid", 'int') > 0 ? GETPOST("socid", 'int') : 0;
        $object->subject = GETPOST("subject", 'alphanohtml');
        $object->message = GETPOST("message", 'restricthtml');

        $object->type_code = GETPOST("type_code", 'alpha');
        $object->category_code = GETPOST("category_code", 'alpha');
        $object->severity_code = GETPOST("severity_code", 'alpha');
        $notifyTiers = GETPOST("notify_tiers_at_create", 'alpha');
        $object->notify_tiers_at_create = empty($notifyTiers) ? 0 : 1;

        $object->fk_project = GETPOST('projectid', 'int');

        $ret = $extrafields->setOptionalsFromPost(null, $object);

        $id = $object->create($user);
        if ($id <= 0) {
            $error++;
            setEventMessage($object->error, $object->errors, 'errors');
            $action = 'create';
        }

        if (!$error)
        {
            // Add contact
            $contactid = GETPOST('contactid', 'int');
            $type_contact = GETPOST("type", 'alpha');

            if ($contactid > 0 && $type_contact) {
                $result = $object->add_contact($contactid, GETPOST("type"), 'external');
            }

            // altairis: link ticket to project
            if (GETPOST('projectid') > 0) {
                $object->setProject(GETPOST('projectid'));
            }

            // Auto assign user
            if ($conf->global->TICKET_AUTO_ASSIGN_USER_CREATE) {
                $result = $object->assignUser($user, $user->id, 1);
                $object->add_contact($user->id, "SUPPORTTEC", 'internal');
            }

            // Auto assign contrat
            $contractid = 0;
            if ($conf->global->TICKET_AUTO_ASSIGN_CONTRACT_CREATE) {
                $contrat = new Contrat($db);
                $contrat->socid = $object->fk_soc;
                $list = $contrat->getListOfContracts();

                if (is_array($list) && !empty($list)) {
                    if (count($list) == 1) {
                        $contractid = $list[0]->id;
                        $object->setContract($contractid);
                    } else {
                    }
                }
            }

            // Auto create fiche intervention
            if ($conf->global->TICKET_AUTO_CREATE_FICHINTER_CREATE)
            {
                $fichinter = new Fichinter($db);
                $fichinter->socid = $object->fk_soc;
                $fichinter->fk_project = GETPOST('projectid', 'int');
                $fichinter->fk_contrat = $contractid;
                $fichinter->author = $user->id;
                $fichinter->modelpdf = 'soleil';
                $fichinter->origin = $object->element;
                $fichinter->origin_id = $object->id;

                // Extrafields
                $extrafields->fetch_name_optionals_label($fichinter->table_element);
                $array_options = $extrafields->getOptionalsFromPost($fichinter->table_element);
                $fichinter->array_options = $array_options;

                $id = $fichinter->create($user);
                if ($id <= 0) {
                    setEventMessages($fichinter->error, null, 'errors');
                }
            }
        }

        if (!$error)
        {
            // File transfer
            $object->copyFilesForTicket();
        }

        if (!$error)
        {
            $db->commit();

            if (!empty($backtopage)) {
                $url = $backtopage;
            } else {
                $url = 'card.php?track_id='.$object->track_id;
            }

            header("Location: ".$url);
            exit;
        } else {
            $db->rollback();
            setEventMessages($object->error, $object->errors, 'errors');
        }
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

if ($action == 'edit' && $user->rights->ticket->write) {
    $error = 0;

    if ($object->fetch(GETPOST('id', 'int')) < 0) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorTicketIsNotValid"));
        $_GET["action"] = $_POST["action"] = '';
    }
}

if (GETPOST('update', 'alpha') && GETPOST('id', 'int') && $user->rights->ticket->write) {
    $error = 0;

    $ret = $object->fetch(GETPOST('id', 'int'));
    if ($ret < 0) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorTicketIsNotValid"));
        $action = '';
    } elseif (!GETPOST("label")) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")));
        $action = 'edit';
    } elseif (!GETPOST("subject", 'alphanohtml')) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")));
        $action = 'edit';
    }

    if (!$error) {
        $db->begin();

        $object->label = GETPOST("label", 'alphanohtml');
        $object->description = GETPOST("description", 'restricthtml');

        //...
        $ret = $object->update($user);
        if ($ret <= 0) {
            $error++;
            setEventMessage($object->error, $object->errors, 'errors');
            $action = 'edit';
        }

        if (!$error && $ret > 0) {
            $db->commit();
        } else {
            $db->rollback();
        }
    }
}

// Mark as Read
if ($action == "mark_ticket_read" && $user->rights->ticket->write) {
    $object->fetch('', '', GETPOST("track_id", 'alpha'));

    if ($object->markAsRead($user) > 0)
    {
        setEventMessages($langs->trans('TicketMarkedAsRead'), null, 'mesgs');

        header("Location: card.php?track_id=".$object->track_id."&action=view");
        exit;
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
    }
    $action = 'view';
}

// Assign to someone
if ($action == "assign_user" && GETPOST('btn_assign_user', 'alpha') && $user->rights->ticket->write) {
    $object->fetch('', '', GETPOST("track_id", 'alpha'));
    $useroriginassign = $object->fk_user_assign;
    $usertoassign = GETPOST('fk_user_assign', 'int');

    /*if (! ($usertoassign > 0)) {
     $error++;
     array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("AssignedTo")));
     $action = 'view';
     }*/

    if (!$error)
    {
        $ret = $object->assignUser($user, $usertoassign);
        if ($ret < 0) $error++;
    }

    if (!$error)	// Update list of contacts
    {
        // Si déjà un user assigné on le supprime des contacts
        if ($useroriginassign > 0) {
            $internal_contacts = $object->listeContact(-1, 'internal');

            foreach ($internal_contacts as $key => $contact) {
                if ($contact['code'] == "SUPPORTTEC" && $contact['id'] == $useroriginassign) {
                }
                {
                    //print "user à effacer : ".$useroriginassign;
                    $object->delete_contact($contact['rowid']);
                }
            }
        }

        if ($usertoassign > 0) $object->add_contact($usertoassign, "SUPPORTTEC", 'internal', $notrigger = 0);
    }

    if (!$error)
    {
        // Log action in ticket logs table
        $object->fetch_user($usertoassign);
        $log_action = $langs->trans('TicketLogAssignedTo', $object->user->getFullName($langs));

        setEventMessages($langs->trans('TicketAssigned'), null, 'mesgs');

        header("Location: card.php?track_id=".$object->track_id."&action=view");
        exit;
    } else {
        array_push($object->errors, $object->error);
    }
    $action = 'view';
}

if ($action == 'add_message' && GETPOSTISSET('btn_add_message') && $user->rights->ticket->read) {
    $ret = $object->newMessage($user, $action, (GETPOST('private_message', 'alpha') == "on" ? 1 : 0));

    if ($ret > 0) {
        if (!empty($backtopage)) {
            $url = $backtopage;
        } else {
            $url = 'card.php?action=view&track_id='.$object->track_id;
        }

        header("Location: ".$url);
        exit;
    } else {
        setEventMessages($object->error, null, 'errors');
        $action = 'presend';
    }
}

if ($action == "confirm_close" && GETPOST('confirm', 'alpha') == 'yes' && $user->rights->ticket->write)
{
    $object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha'));

    if ($object->close($user)) {
        setEventMessages($langs->trans('TicketMarkedAsClosed'), null, 'mesgs');

        $url = 'card.php?action=view&track_id='.GETPOST('track_id', 'alpha');
        header("Location: ".$url);
    } else {
        $action = '';
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

if ($action == "confirm_public_close" && GETPOST('confirm', 'alpha') == 'yes') {
    $object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha'));
    if ($_SESSION['email_customer'] == $object->origin_email || $_SESSION['email_customer'] == $object->thirdparty->email) {
    	$object->close($user);

        // Log action in ticket logs table
        $log_action = $langs->trans('TicketLogClosedBy', $_SESSION['email_customer']);

        setEventMessages('<div class="confirm">'.$langs->trans('TicketMarkedAsClosed').'</div>', null, 'mesgs');

        $url = 'card.php?action=view_ticket&track_id='.GETPOST('track_id', 'alpha');
        header("Location: ".$url);
    } else {
        setEventMessages($object->error, $object->errors, 'errors');
        $action = '';
    }
}

if ($action == 'confirm_delete_ticket' && GETPOST('confirm', 'alpha') == "yes" && $user->rights->ticket->delete) {
    if ($object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
        if ($object->delete($user) > 0) {
            setEventMessages('<div class="confirm">'.$langs->trans('TicketDeletedSuccess').'</div>', null, 'mesgs');
            Header("Location: ".DOL_URL_ROOT."/ticket/list.php");
            exit;
        } else {
            $langs->load("errors");
            $mesg = '<div class="error">'.$langs->trans($object->error).'</div>';
            $action = '';
        }
    }
}

// Set parent company
if ($action == 'set_thirdparty' && $user->rights->societe->creer) {
    if ($object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
        $result = $object->setCustomer(GETPOST('editcustomer', 'int'));
        $url = 'card.php?action=view&track_id='.GETPOST('track_id', 'alpha');
        header("Location: ".$url);
        exit();
    }
}

if ($action == 'set_progression' && $user->rights->ticket->write) {
    if ($object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
        $result = $object->setProgression(GETPOST('progress', 'alpha'));

        $url = 'card.php?action=view&track_id='.$object->track_id;
        header("Location: ".$url);
        exit();
    }
}

if ($action == 'setsubject') {
    if ($object->fetch(GETPOST('id', 'int'))) {
        if ($action == 'setsubject') {
            $object->subject = trim(GETPOST('subject', 'alphanohtml'));
        }

        if ($action == 'setsubject' && empty($object->subject)) {
            $mesg .= ($mesg ? '<br>' : '').$langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject"));
        }

        if (!$mesg) {
            if ($object->update($user) >= 0) {
                header("Location: ".$_SERVER['PHP_SELF']."?track_id=".$object->track_id);
                exit;
            }
            $mesg = $object->error;
        }
    }
}

if ($action == 'confirm_reopen' && $user->rights->ticket->manage && !GETPOST('cancel')) {
    if ($object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
        // prevent browser refresh from reopening ticket several times
        if ($object->fk_statut == Ticket::STATUS_CLOSED) {
            $res = $object->setStatut(Ticket::STATUS_ASSIGNED);
            if ($res) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogReopen');

                $url = 'card.php?action=view&track_id='.$object->track_id;
                header("Location: ".$url);
                exit();
            }
        }
    }
} // Categorisation dans projet
elseif ($action == 'classin' && $user->rights->ticket->write) {
    if ($object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
        $object->setProject(GETPOST('projectid', 'int'));
        $url = 'card.php?action=view&track_id='.$object->track_id;
        header("Location: ".$url);
        exit();
    }
} // Categorisation dans contrat
elseif ($action == 'setcontract' && $user->rights->ticket->write) {
    if ($object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
        $object->setContract(GETPOST('contractid', 'int'));
        $url = 'card.php?action=view&track_id='.$object->track_id;
        header("Location: ".$url);
        exit();
    }
} elseif ($action == "set_message" && $user->rights->ticket->manage) {
    // altairis: manage cancel button
    if (!GETPOST('cancel')) {
        $object->fetch('', '', GETPOST('track_id', 'alpha'));
        $oldvalue_message = $object->message;
        $fieldtomodify = GETPOST('message_initial', 'restricthtml');

        $object->message = $fieldtomodify;
        $ret = $object->update($user);
        if ($ret > 0) {
            $log_action = $langs->trans('TicketInitialMessageModified')." \n";
            // include the Diff class
            dol_include_once('/ticket/class/utils_diff.class.php');
            // output the result of comparing two files as plain text
            $log_action .= Diff::toString(Diff::compare(strip_tags($oldvalue_message), strip_tags($object->message)));

            setEventMessages($langs->trans('TicketMessageSuccesfullyUpdated'), null, 'mesgs');
        }
    }

    $action = 'view';
} // Reopen ticket
elseif ($action == 'confirm_set_status' && $user->rights->ticket->write && !GETPOST('cancel')) {
    if ($object->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
        $new_status = GETPOST('new_status', 'int');
        $old_status = $object->fk_statut;
        $res = $object->setStatut($new_status);
        if ($res) {
            // Log action in ticket logs table
            $log_action = $langs->trans('TicketLogStatusChanged', $langs->transnoentities($object->statuts_short[$old_status]), $langs->transnoentities($object->statuts_short[$new_status]));

            $url = 'card.php?action=view&track_id='.$object->track_id;
            header("Location: ".$url);
            exit();
        }
    }
}

// Action to update one extrafield
if ($action == "update_extras" && !empty($permissiontoadd))
{
	$object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha'));
	$attributekey = GETPOST('attribute', 'alpha');
	$attributekeylong = 'options_'.$attributekey;
	$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong, ' alpha');

	$result = $object->insertExtraFields(empty($triggermodname) ? '' : $triggermodname, $user);
	if ($result > 0)
	{
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = 'view';
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'edit_extras';
	}
}

if ($action == "change_property" && GETPOST('btn_update_ticket_prop', 'alpha') && $user->rights->ticket->write)
{
	$object->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha'));

	$object->type_code = GETPOST('update_value_type', 'aZ09');
	$object->severity_code = GETPOST('update_value_severity', 'aZ09');
	$object->category_code = GETPOST('update_value_category', 'aZ09');

	$ret = $object->update($user);
	if ($ret > 0) {
		$log_action = $langs->trans('TicketLogPropertyChanged', $oldvalue_label, $newvalue_label);

		setEventMessages($langs->trans('TicketUpdated'), null, 'mesgs');
	}
	$action = 'view';
}


$permissiondellink = $user->rights->ticket->write;
include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

// Actions to build doc
$upload_dir = $conf->ticket->dir_output;
$permissiontoadd = $user->rights->ticket->write;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

// Actions to send emails
$triggersendname = 'TICKET_SENTBYMAIL';
$paramname = 'id';
$autocopy = 'MAIN_MAIL_AUTOCOPY_TICKET_TO'; // used to know the automatic BCC to add
$trackid = 'tic'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

// Set $action to correct value for the case we used presend action to add a message
if (GETPOSTISSET('actionbis') && $action == 'presend') $action = 'presend_addmessage';


/*
 * View
 */

$userstat = new User($db);
$form = new Form($db);
$formticket = new FormTicket($db);
if (!empty($conf->projet->enabled)) $formproject = new FormProjets($db);

$help_url = 'FR:DocumentationModuleTicket';
$page_title = $actionobject->getTitle($action);

llxHeader('', $page_title, $help_url);

if ($action == 'create' || $action == 'presend')
{
    $formticket = new FormTicket($db);

    print load_fiche_titre($langs->trans('NewTicket'), '', 'ticket');

    $formticket->withfromsocid = $socid ? $socid : $user->socid;
    $formticket->withfromcontactid = $contactid ? $contactid : '';
    $formticket->withtitletopic = 1;
    $formticket->withnotifytiersatcreate = ($notifyTiers ? 1 : 0);
    $formticket->withusercreate = 1;
    $formticket->withref = 1;
    $formticket->fk_user_create = $user->id;
    $formticket->withfile = 2;
    $formticket->withextrafields = 1;
    $formticket->param = array('origin' => GETPOST('origin'), 'originid' => GETPOST('originid'));
    if (empty($defaultref)) {
        $defaultref = '';
    }

    $formticket->showForm(1, 'create');
}

if (empty($action) || $action == 'view' || $action == 'addlink' || $action == 'dellink' || $action == 'presend' || $action == 'presend_addmessage' || $action == 'close' || $action == 'delete' || $action == 'editcustomer' || $action == 'progression' || $action == 'reopen'
	|| $action == 'editsubject' || $action == 'edit_extras' || $action == 'update_extras' || $action == 'edit_extrafields' || $action == 'set_extrafields' || $action == 'classify' || $action == 'sel_contract' || $action == 'edit_message_init' || $action == 'set_status' || $action == 'dellink')
{
    if ($res > 0)
    {
        // or for unauthorized internals users
        if (!$user->socid && ($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY && $object->fk_user_assign != $user->id) && !$user->rights->ticket->manage) {
            accessforbidden('', 0, 1);
        }

        // Confirmation close
        if ($action == 'close') {
            print $form->formconfirm($url_page_current."?track_id=".$object->track_id, $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_close", '', '', 1);
            if ($ret == 'html') {
                print '<br>';
            }
        }
        // Confirmation delete
        if ($action == 'delete') {
        	print $form->formconfirm($url_page_current."?track_id=".$object->track_id, $langs->trans("Delete"), $langs->trans("ConfirmDeleteTicket"), "confirm_delete_ticket", '', '', 1);
        }
        // Confirm reopen
        if ($action == 'reopen') {
        	print $form->formconfirm($url_page_current.'?track_id='.$object->track_id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenTicket'), 'confirm_reopen', '', '', 1);
        }
        // Confirmation status change
        if ($action == 'set_status') {
            $new_status = GETPOST('new_status');
            //var_dump($url_page_current . "?track_id=" . $object->track_id);
            print $form->formconfirm($url_page_current."?track_id=".$object->track_id."&new_status=".GETPOST('new_status'), $langs->trans("TicketChangeStatus"), $langs->trans("TicketConfirmChangeStatus", $langs->transnoentities($object->statuts_short[$new_status])), "confirm_set_status", '', '', 1);
        }

        // project info
        if ($projectid) {
            $projectstat = new Project($db);
            if ($projectstat->fetch($projectid) > 0) {
                $projectstat->fetch_thirdparty();

                // To verify role of users
                //$userAccess = $object->restrictedProjectArea($user,'read');
                $userWrite = $projectstat->restrictedProjectArea($user, 'write');
                //$userDelete = $object->restrictedProjectArea($user,'delete');
                //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

                $head = project_prepare_head($projectstat);
                dol_fiche_head($head, 'ticket', $langs->trans("Project"), 0, ($projectstat->public ? 'projectpub' : 'project'));

                /*
                 *   Projet synthese pour rappel
                 */
                print '<table class="border centpercent">';

                $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

                // Ref
                print '<tr><td width="30%">'.$langs->trans('Ref').'</td><td colspan="3">';
                // Define a complementary filter for search of next/prev ref.
                if (!$user->rights->projet->all->lire) {
                    $objectsListId = $projectstat->getProjectsAuthorizedForUser($user, $mine, 0);
                    $projectstat->next_prev_filter = " rowid in (".(count($objectsListId) ? join(',', array_keys($objectsListId)) : '0').")";
                }
                print $form->showrefnav($projectstat, 'ref', $linkback, 1, 'ref', 'ref', '');
                print '</td></tr>';

                // Label
                print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projectstat->title.'</td></tr>';

                // Customer
                print "<tr><td>".$langs->trans("ThirdParty")."</td>";
                print '<td colspan="3">';
                if ($projectstat->thirdparty->id > 0) {
                    print $projectstat->thirdparty->getNomUrl(1);
                } else {
                    print '&nbsp;';
                }

                print '</td></tr>';

                // Visibility
                print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
                if ($projectstat->public) {
                    print $langs->trans('SharedProject');
                } else {
                    print $langs->trans('PrivateProject');
                }

                print '</td></tr>';

                // Statut
                print '<tr><td>'.$langs->trans("Status").'</td><td>'.$projectstat->getLibStatut(4).'</td></tr>';

                print "</table>";

                print '</div>';
            } else {
                print "ErrorRecordNotFound";
            }
        } elseif ($socid > 0) {
            $object->fetch_thirdparty();
            $head = societe_prepare_head($object->thirdparty);

            dol_fiche_head($head, 'ticket', $langs->trans("ThirdParty"), 0, 'company');
            dol_banner_tab($object->thirdparty, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');
            dol_fiche_end();
        }

        if (!$user->socid && $conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) {
            $object->next_prev_filter = "te.fk_user_assign = '".$user->id."'";
        } elseif ($user->socid > 0) {
            $object->next_prev_filter = "te.fk_soc = '".$user->socid."'";
        }

        $head = ticket_prepare_head($object);

        dol_fiche_head($head, 'tabTicket', $langs->trans("Ticket"), -1, 'ticket');

        $morehtmlref = '<div class="refidno">';
        $morehtmlref .= $object->subject;
        // Author
        if ($object->fk_user_create > 0) {
        	$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';

            $langs->load("users");
            $fuser = new User($db);
            $fuser->fetch($object->fk_user_create);
            $morehtmlref .= $fuser->getNomUrl(0);
        }
        if (!empty($object->origin_email)) {
        	$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';
        	$morehtmlref .= dol_escape_htmltag($object->origin_email).' <small>('.$langs->trans("TicketEmailOriginIssuer").')</small>';
        }

        // Thirdparty
        if (!empty($conf->societe->enabled))
        {
	        $morehtmlref .= '<br>'.$langs->trans('ThirdParty').' ';
	        if ($action != 'editcustomer' && $object->fk_statut < 8 && !$user->socid && $user->rights->ticket->write) {
	        	$morehtmlref .= '<a class="editfielda" href="'.$url_page_current.'?action=editcustomer&track_id='.$object->track_id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 0).'</a> : ';
	        }
	        if ($action == 'editcustomer') {
	        	$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, 'editcustomer', '', 1, 0, 0, array(), 1);
	        } else {
	            $morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, 'none', '', 1, 0, 0, array(), 1);
	        }
        }

        // Project
        if (!empty($conf->projet->enabled))
        {
        	$langs->load("projects");
        	$morehtmlref .= '<br>'.$langs->trans('Project');
        	if ($user->rights->ticket->write)
        	{
        		if ($action != 'classify')
        			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a>';
       			$morehtmlref .= ' : ';
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

        $linkback = '<a href="'.DOL_URL_ROOT.'/ticket/list.php?restore_lastsearch_values=1"><strong>'.$langs->trans("BackToList").'</strong></a> ';

        dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref);

        print '<div class="fichecenter">';
        print '<div class="fichehalfleft">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border tableforfield centpercent">';

        // Track ID
        print '<tr><td class="titlefield">'.$langs->trans("TicketTrackId").'</td><td>';
        if (!empty($object->track_id)) {
            if (empty($object->ref)) {
                $object->ref = $object->id;
                print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'track_id');
            } else {
                print $object->track_id;
            }
        } else {
            print $langs->trans('None');
        }
        print '</td></tr>';

        // Subject
        print '<tr><td>';
        print $form->editfieldkey("Subject", 'subject', $object->subject, $object, $user->rights->ticket->write && !$user->socid, 'string');
        print '</td><td>';
        print $form->editfieldval("Subject", 'subject', $object->subject, $object, $user->rights->ticket->write && !$user->socid, 'string');
        print '</td></tr>';

        // Creation date
        print '<tr><td>'.$langs->trans("DateCreation").'</td><td>';
        print dol_print_date($object->datec, 'dayhour');
        print '<span class="opacitymedium"> - '.$langs->trans("TimeElapsedSince").': '.'<i>'.convertSecondToTime(roundUpToNextMultiple($now - $object->datec, 60)).'</i></span>';
        print '</td></tr>';

        // Read date
        print '<tr><td>'.$langs->trans("TicketReadOn").'</td><td>';
        if (!empty($object->date_read)) {
        	print dol_print_date($object->date_read, 'dayhour');
        	print '<span class="opacitymedium"> - '.$langs->trans("TicketTimeToRead").': <i>'.convertSecondToTime(roundUpToNextMultiple($object->date_read - $object->datec, 60)).'</i>';
        	print ' - '.$langs->trans("TimeElapsedSince").': '.'<i>'.convertSecondToTime(roundUpToNextMultiple($now - $object->date_read, 60)).'</i></span>';
        }
        print '</td></tr>';

        // Close date
        print '<tr><td>'.$langs->trans("TicketCloseOn").'</td><td>';
        if (!empty($object->date_close)) {
        	print dol_print_date($object->date_close, 'dayhour');
        }
        print '</td></tr>';

        // User assigned
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
        print $langs->trans("AssignedTo");
        if ($object->fk_statut < 8 && GETPOST('set', 'alpha') != "assign_ticket" && $user->rights->ticket->manage) {
        	print '<td class="right"><a class="editfielda" href="'.$url_page_current.'?track_id='.$object->track_id.'&action=view&set=assign_ticket">'.img_edit($langs->trans('Modify'), '').'</a></td>';
        }
        print '</tr></table>';
        print '</td><td>';
        if ($object->fk_user_assign > 0) {
            $userstat->fetch($object->fk_user_assign);
            print $userstat->getNomUrl(1);
        } else {
            print $langs->trans('None');
        }

        // Show user list to assignate one if status is "read"
        if (GETPOST('set', 'alpha') == "assign_ticket" && $object->fk_statut < 8 && !$user->socid && $user->rights->ticket->write) {
            print '<form method="post" name="ticket" enctype="multipart/form-data" action="'.$url_page_current.'">';
            print '<input type="hidden" name="token" value="'.newToken().'">';
            print '<input type="hidden" name="action" value="assign_user">';
            print '<input type="hidden" name="track_id" value="'.$object->track_id.'">';
            print '<label for="fk_user_assign">'.$langs->trans("AssignUser").'</label> ';
            print $form->select_dolusers($user->id, 'fk_user_assign', 1);
            print ' <input class="button" type="submit" name="btn_assign_user" value="'.$langs->trans("Validate").'" />';
            print '</form>';
        }
        print '</td></tr>';

        // Progression
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
        print $langs->trans('Progression').'</td><td class="left">';
        print '</td>';
        if ($action != 'progression' && $object->fk_statut < 8 && !$user->socid) {
            print '<td class="right"><a class="editfielda" href="'.$url_page_current.'?action=progression&amp;track_id='.$object->track_id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
        }
        print '</tr></table>';
        print '</td><td colspan="5">';
        if ($user->rights->ticket->write && $action == 'progression') {
            print '<form action="'.$url_page_current.'" method="post">';
            print '<input type="hidden" name="token" value="'.newToken().'">';
            print '<input type="hidden" name="track_id" value="'.$track_id.'">';
            print '<input type="hidden" name="action" value="set_progression">';
            print '<input type="text" class="flat" size="20" name="progress" value="'.$object->progress.'">';
            print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
            print '</form>';
        } else {
            print($object->progress > 0 ? $object->progress : '0').'%';
        }
        print '</td>';
        print '</tr>';

        // Timing (Duration sum of linked fichinter)
        if ($conf->fichinter->enabled)
        {
	        $object->fetchObjectLinked();
	        $num = count($object->linkedObjects);
	        $timing = 0;
	        if ($num) {
	            foreach ($object->linkedObjects as $objecttype => $objects) {
	                if ($objecttype = "fichinter") {
	                    foreach ($objects as $fichinter) {
	                        $timing += $fichinter->duration;
	                    }
	                }
	            }
	        }
	        print '<tr><td valign="top">';

	        print $form->textwithpicto($langs->trans("TicketDurationAuto"), $langs->trans("TicketDurationAutoInfos"), 1);
	        print '</td><td>';
	        print convertSecondToTime($timing, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
	        print '</td></tr>';
        }

        // Other attributes
        include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

        print '</table>';


        // Fin colonne gauche et début colonne droite
        print '</div><div class="fichehalfright"><div class="ficheaddleft">';


        // View Original message
        $actionobject->viewTicketOriginalMessage($user, $action, $object);

        // Classification of ticket
        print '<form method="post" name="formticketproperties" action="'.$url_page_current.'">';
        print '<input type="hidden" name="token" value="'.newToken().'">';
        print '<input type="hidden" name="action" value="change_property">';
        print '<input type="hidden" name="property" value="'.$property['dict'].'">';
        print '<input type="hidden" name="track_id" value="'.$track_id.'">';

        print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
        print '<table class="noborder centpercent margintable">';
        print '<tr class="liste_titre">';
        print '<td>';
        print $langs->trans('Properties');
        print '</td>';
        print '<td>';
        if (GETPOST('set', 'alpha') == 'properties' && $user->rights->ticket->write) {
        	print '<input class="button" type="submit" name="btn_update_ticket_prop" value="'.$langs->trans("Modify").'" />';
        }
        else {
        	//    Button to edit Properties
        	if ($object->fk_statut < 5 && $user->rights->ticket->write) {
        		print '<a class="editfielda" href="card.php?track_id='.$object->track_id.'&action=view&set=properties">'.img_edit($langs->trans('Modify')).'</a>';
        	}
        }
        print '</td>';
        print '</tr>';

        if (GETPOST('set', 'alpha') == 'properties' && $user->rights->ticket->write) {
            print '<tr>';
            // Type
            print '<td class="titlefield">';
            print $langs->trans('TicketChangeType');
            print '</td><td>';
            $formticket->selectTypesTickets($object->type_code, 'update_value_type', '', 2);
            print '</td>';
            print '</tr>';
            // Group
            print '<tr>';
            print '<td>';
            print $langs->trans('TicketChangeCategory');
            print '</td><td>';
            $formticket->selectGroupTickets($object->category_code, 'update_value_category', '', 2);
            print '</td>';
            print '</tr>';
            // Severity
            print '<tr>';
            print '<td>';
            print $langs->trans('TicketChangeSeverity');
            print '</td><td>';
            $formticket->selectSeveritiesTickets($object->severity_code, 'update_value_severity', '', 2);
            print '</td>';
            print '</tr>';
        } else {
            // Type
            print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td>';
            print $langs->getLabelFromKey($db, $object->type_code, 'c_ticket_type', 'code', 'label');
            print '</td></tr>';
            // Group
            print '<tr><td>'.$langs->trans("TicketGroup").'</td><td>';
            print $langs->getLabelFromKey($db, $object->category_code, 'c_ticket_category', 'code', 'label');
            print '</td></tr>';
            // Severity
            print '<tr><td>'.$langs->trans("TicketSeverity").'</td><td>';
            print $langs->getLabelFromKey($db, $object->severity_code, 'c_ticket_severity', 'code', 'label');
            print '</td></tr>';
        }
        print '</table>'; // End table actions
        print '</div>';

        print '</form>';

        // Display navbar with links to change ticket status
        print '<!-- navbar with status -->';
        if (!$user->socid && $user->rights->ticket->write && $object->fk_statut < 8 && GETPOST('set') !== 'properties') {
        	$actionobject->viewStatusActions($object);
        }


        if (!empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
        {
	        print load_fiche_titre($langs->trans('Contacts'), '', 'title_companies.png');

	        print '<div class="div-table-responsive-no-min">';
	        print '<div class="tagtable centpercent noborder allwidth">';

	        print '<div class="tagtr liste_titre">';
	        print '<div class="tagtd">'.$langs->trans("Source").'</div>
			<div class="tagtd">' . $langs->trans("Company").'</div>
			<div class="tagtd">' . $langs->trans("Contacts").'</div>
			<div class="tagtd">' . $langs->trans("ContactType").'</div>
			<div class="tagtd">' . $langs->trans("Phone").'</div>
			<div class="tagtd center">' . $langs->trans("Status").'</div>';
	        print '</div><!-- tagtr -->';

	        // Contact list
	        $companystatic = new Societe($db);
	        $contactstatic = new Contact($db);
	        $userstatic = new User($db);
	        foreach (array('internal', 'external') as $source) {
	            $tmpobject = $object;
	            $tab = $tmpobject->listeContact(-1, $source);
	            $num = count($tab);
	            $i = 0;
	            while ($i < $num) {
	                $var = !$var;
	                print '<div class="tagtr '.($var ? 'pair' : 'impair').'">';

	                print '<div class="tagtd left">';
	                if ($tab[$i]['source'] == 'internal') {
	                    echo $langs->trans("User");
	                }

	                if ($tab[$i]['source'] == 'external') {
	                    echo $langs->trans("ThirdPartyContact");
	                }

	                print '</div>';
	                print '<div class="tagtd left">';

	                if ($tab[$i]['socid'] > 0) {
	                    $companystatic->fetch($tab[$i]['socid']);
	                    echo $companystatic->getNomUrl(1);
	                }
	                if ($tab[$i]['socid'] < 0) {
	                    echo $conf->global->MAIN_INFO_SOCIETE_NOM;
	                }
	                if (!$tab[$i]['socid']) {
	                    echo '&nbsp;';
	                }
	                print '</div>';

	                print '<div class="tagtd">';
	                if ($tab[$i]['source'] == 'internal') {
	                	if ($userstatic->fetch($tab[$i]['id'])) {
		                    print $userstatic->getNomUrl(1);
	                	}
	                }
	                if ($tab[$i]['source'] == 'external') {
	                	if ($contactstatic->fetch($tab[$i]['id'])) {
		                    print $contactstatic->getNomUrl(1);
	                	}
	                }
	                print ' </div>
					<div class="tagtd">' . $tab[$i]['libelle'].'</div>';

	                print '<div class="tagtd">';

	                print dol_print_phone($tab[$i]['phone'], '', '', '', 'AC_TEL').'<br>';

	                if (!empty($tab[$i]['phone_perso'])) {
	                    //print img_picto($langs->trans('PhonePerso'),'object_phoning.png','',0,0,0).' ';
	                    print '<br>'.dol_print_phone($tab[$i]['phone_perso'], '', '', '', 'AC_TEL').'<br>';
	                }
	                if (!empty($tab[$i]['phone_mobile'])) {
	                    //print img_picto($langs->trans('PhoneMobile'),'object_phoning.png','',0,0,0).' ';
	                    print dol_print_phone($tab[$i]['phone_mobile'], '', '', '', 'AC_TEL').'<br>';
	                }
	                print '</div>';

	                print '<div class="tagtd center">';
	                if ($object->statut >= 0) {
	                    echo '<a href="contact.php?track_id='.$object->track_id.'&amp;action=swapstatut&amp;ligne='.$tab[$i]['rowid'].'">';
	                }

	                if ($tab[$i]['source'] == 'internal') {
	                    $userstatic->id = $tab[$i]['id'];
	                    $userstatic->lastname = $tab[$i]['lastname'];
	                    $userstatic->firstname = $tab[$i]['firstname'];
	                    echo $userstatic->LibStatut($tab[$i]['statuscontact'], 3);
	                }
	                if ($tab[$i]['source'] == 'external') {
	                    $contactstatic->id = $tab[$i]['id'];
	                    $contactstatic->lastname = $tab[$i]['lastname'];
	                    $contactstatic->firstname = $tab[$i]['firstname'];
	                    echo $contactstatic->LibStatut($tab[$i]['statuscontact'], 3);
	                }
	                if ($object->statut >= 0) {
	                    echo '</a>';
	                }

	                print '</div>';

	                print '</div><!-- tagtr -->';

	                $i++;
	            }
	        }

	        print '</div><!-- contact list -->';
			print '</div>';
        }

        print '</div></div></div>';
        print '<div style="clear:both"></div>';

		dol_fiche_end();


		// Buttons for actions
		if ($action != 'presend' && $action != 'presend_addmessage' && $action != 'editline') {
			print '<div class="tabsAction">'."\n";
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

			if (empty($reshook))
			{
				// Show link to add a message (if read and not closed)
				if ($object->fk_statut < Ticket::STATUS_CLOSED && $action != "presend" && $action != "presend_addmessage") {
		            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id='.$object->track_id.'&action=presend_addmessage&mode=init">'.$langs->trans('TicketAddMessage').'</a></div>';
		        }

		        // Link to create an intervention
		        // socid is needed otherwise fichinter ask it and forgot origin after form submit :\
		        if (!$object->fk_soc && $user->rights->ficheinter->creer) {
		            print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans('UnableToCreateInterIfNoSocid').'">'.$langs->trans('TicketAddIntervention').'</a></div>';
		        }
		        if ($object->fk_soc > 0 && $object->fk_statut < Ticket::STATUS_CLOSED && $user->rights->ficheinter->creer) {
		            print '<div class="inline-block divButAction"><a class="butAction" href="'.dol_buildpath('/fichinter/card.php', 1).'?action=create&socid='.$object->fk_soc.'&origin=ticket_ticket&originid='.$object->id.'">'.$langs->trans('TicketAddIntervention').'</a></div>';
		        }

		        // Close ticket if statut is read
		        if ($object->fk_statut > 0 && $object->fk_statut < Ticket::STATUS_CLOSED && $user->rights->ticket->write) {
		            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id='.$object->track_id.'&action=close">'.$langs->trans('CloseTicket').'</a></div>';
		        }

		        // Re-open ticket
		        if (!$user->socid && $object->fk_statut == Ticket::STATUS_CLOSED && !$user->socid) {
		            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id='.$object->track_id.'&action=reopen">'.$langs->trans('ReOpen').'</a></div>';
		        }

		        // Delete ticket
		        if ($user->rights->ticket->delete && !$user->socid) {
		            print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?track_id='.$object->track_id.'&action=delete">'.$langs->trans('Delete').'</a></div>';
		        }
			}
	        print '</div>'."\n";
		}
		else
		{
			print '<br>';
		}

		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}
		// Set $action to correct value for the case we used presend action to add a message
		if (GETPOSTISSET('actionbis') && $action == 'presend') $action = 'presend_addmessage';

		if ($action != 'presend' && $action != 'presend_addmessage')
		{
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, array('ticket'));
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			// Show direct link to public interface
			print '<br><!-- Link to public interface -->'."\n";
			print showDirectPublicLink($object).'<br>';

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// List of actions on element
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'ticket', $socid, 1);

			print '</div></div></div>';
		}
		else
		{
			$action = 'add_message'; // action to use to post the message
			$modelmail = 'ticket_send';

			// Substitution array
			$morehtmlright = '';
		    $help = "";
		    $substitutionarray = array();
		    if ($object->fk_soc > 0) {
		        $object->fetch_thirdparty();
		        $substitutionarray['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
		    }
		    $substitutionarray['__SIGNATURE__'] = $user->signature;
		    $substitutionarray['__TICKET_TRACKID__'] = $object->track_id;
		    $substitutionarray['__TICKET_REF__'] = $object->ref;
		    $substitutionarray['__TICKET_SUBJECT__'] = $object->subject;
		    $substitutionarray['__TICKET_TYPE__'] = $object->type_code;
		    $substitutionarray['__TICKET_SEVERITY__'] = $object->severity_code;
		    $substitutionarray['__TICKET_CATEGORY__'] = $object->category_code; // For backward compatibility
		    $substitutionarray['__TICKET_ANALYTIC_CODE__'] = $object->category_code;
		    $substitutionarray['__TICKET_MESSAGE__'] = $object->message;
		    $substitutionarray['__TICKET_PROGRESSION__'] = $object->progress;
		    if ($object->fk_user_assign > 0) {
		        $userstat->fetch($object->fk_user_assign);
		        $substitutionarray['__TICKET_USER_ASSIGN__'] = dolGetFirstLastname($userstat->firstname, $userstat->lastname);
		    }

		    if ($object->fk_user_create > 0) {
		        $userstat->fetch($object->fk_user_create);
		        $substitutionarray['__TICKET_USER_CREATE__'] = dolGetFirstLastname($userstat->firstname, $userstat->lastname);
		    }
		    foreach ($substitutionarray as $key => $val) {
		        $help .= $key.' -> '.$langs->trans($val).'<br>';
		    }
		    $morehtmlright .= $form->textwithpicto('<span class="opacitymedium">'.$langs->trans("TicketMessageSubstitutionReplacedByGenericValues").'</span>', $help, 1, 'helpclickable', '', 0, 3, 'helpsubstitution');

			print '<div>';
			print load_fiche_titre($langs->trans('TicketAddMessage'), $morehtmlright, 'messages@ticket');

			print '<hr>';

			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && !empty($_REQUEST['lang_id'])) {
				$newlang = $_REQUEST['lang_id'];
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
				$newlang = $object->default_lang;
			}

			$formticket = new FormTicket($db);

			$formticket->action = $action;
			$formticket->track_id = $object->track_id;
			$formticket->ref = $object->ref;
			$formticket->id = $object->id;

			$formticket->withfile = 2;
			$formticket->withcancel = 1;
			$formticket->param = array('fk_user_create' => $user->id);
			$formticket->param['langsmodels'] = (empty($newlang) ? $langs->defaultlang : $newlang);

			// Tableau des parametres complementaires du post
			$formticket->param['models'] = $modelmail;
			$formticket->param['models_id'] = GETPOST('modelmailselected', 'int');
			//$formticket->param['socid']=$object->fk_soc;
			$formticket->param['returnurl'] = $_SERVER["PHP_SELF"].'?track_id='.$object->track_id;

			$formticket->withsubstit = 1;
			$formticket->substit = $substitutionarray;
			$formticket->showMessageForm('100%');
			print '</div>';
	    }
	}
}

// End of page
llxFooter();
$db->close();
