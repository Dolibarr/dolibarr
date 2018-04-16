<?php
/* Copyright (C) 2013-2015 Jean-François FERRY <hello@librethic.io>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *    \file       ticketsup/class/actions_ticketsup.class.php
 *    \ingroup    ticketsup
 *    \brief      File Class ticketsup
 */

require_once "ticketsup.class.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';


/**
 *  Class Actions of the module ticketsup
 */
class ActionsTicketsup
{
    public $db;
    public $dao;

    public $mesg;
    public $error;
    public $errors = array();
    //! Numero de l'erreur
    public $errno = 0;

    public $template_dir;
    public $template;

    public $label;
    public $description;

    public $fk_statut;
    public $fk_soc;

    /**
     *    Constructor
     *
     *    @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Instantiation of DAO class
     *
     * @return void
     */
    public function getInstanceDao()
    {
        if (!is_object($this->dao)) {
            $this->dao = new Ticketsup($this->db);
        }
    }

    /**
     *     doActions
     *
     *     @param 	string 		$action 	Action type
     *     @param	Ticketsup	$object		Object Ticketsup
     *     @return	int						0
     */
    public function doActions(&$action = '', Ticketsup $object=null)
    {
        global $conf, $user, $langs, $mysoc;

        /*
         * Add file in email form
         */
        if (GETPOST('addfile')) {
            // altairis : allow files from public interface
            if (GETPOST('track_id')) {
            	$res = $object->fetch('', '', GETPOST('track_id','alpha'));
            }

            ////if($res > 0)
            ////{
            include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            // Set tmp directory TODO Use a dedicated directory for temp mails files
            $vardir = $conf->ticketsup->dir_output . (!empty($object->track_id) ?  '/' . dol_sanitizeFileName($object->track_id) : '');
            $upload_dir_tmp = $vardir . '/temp';
            if (!dol_is_dir($upload_dir_tmp)) {
                dol_mkdir($upload_dir_tmp);
            }
            dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', dol_print_date(dol_now(), '%Y%m%d%H%M%S') . '-__file__');
            $action = !empty($object->track_id) ? 'add_message' : 'create_ticket';
            ////}
        }

        /*
         * Remove file in email form
         */
        if (GETPOST('removedfile')) {
            // altairis : allow files from public interface
            if (GETPOST('track_id')) {
                $res = $object->fetch('', '', GETPOST('track_id','alpha'));
            }

            ////if($res > 0)
            ////{
            include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            // Set tmp directory
            $vardir = $conf->ticketsup->dir_output . (!empty($object->track_id) ?  '/' . dol_sanitizeFileName($object->track_id) : '');
            $upload_dir_tmp = $vardir . '/temp';

            // TODO Delete only files that was uploaded from email form
            dol_remove_file_process($_POST['removedfile'], 0);
            $action = !empty($object->track_id) ? 'add_message' : 'create_ticket';
            ////}
        }

        if (GETPOST('add_ticket') && $user->rights->ticketsup->write) {
            $error = 0;

            if (!GETPOST("subject")) {
                $error++;
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject"));
                $action = 'create_ticket';
            } elseif (!GETPOST("message")) {
                $error++;
                $this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("message"));
                $action = 'create_ticket';
            }

            if (!$error) {
                $this->db->begin();

                $object->track_id = generate_random_id(16);

                $object->ref = GETPOST("ref", 'alpha');
                $object->fk_soc = GETPOST("socid", 'int') > 0 ? GETPOST("socid", 'int') : 0;
                $object->subject = GETPOST("subject", 'alpha');
                $object->message = GETPOST("message");

                $object->type_code = GETPOST("type_code", 'alpha');
                $object->category_code = GETPOST("category_code", 'alpha');
                $object->severity_code = GETPOST("severity_code", 'alpha');
                $notifyTiers = GETPOST("notify_tiers_at_create", 'alpha');
                $object->notify_tiers_at_create = empty($notifyTiers) ? 0 : 1;

                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
                $ret = $extrafields->setOptionalsFromPost($extralabels, $object);

                $id = $object->create($user);
                if ($id <= 0) {
                    $error++;
                    $this->error = $object->error;
                    $this->errors = $object->errors;
                    $action = 'create_ticket';
                }

                if (!$error && $id > 0)
                {
                    $this->db->commit();

                    // File transfer
                    $this->copyFilesForTicket();

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
                    if ($conf->global->TICKETS_AUTO_ASSIGN_USER_CREATE) {
                        $result = $object->assignUser($user, $user->id, 1);
                        $object->add_contact($user->id, "SUPPORTTEC", 'internal');
                    }

                    // Auto assign contrat
                    $contractid = 0;
                    if ($conf->global->TICKETS_AUTO_ASSIGN_CONTRACT_CREATE) {
                        $contrat = new Contrat($this->db);
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
                    if ($conf->global->TICKETS_AUTO_CREATE_FICHINTER_CREATE)
                    {
                        $fichinter = new Fichinter($this->db);
                        $fichinter->socid = $object->fk_soc;
                        $fichinter->fk_project = GETPOST('projectid', 'int');
                        $fichinter->fk_contrat = $contractid;
                        $fichinter->author = $user->id;
                        $fichinter->modelpdf = 'soleil';
                        $fichinter->origin = $object->element;
                        $fichinter->origin_id = $object->id;

                        // Extrafields
                        $extrafields = new ExtraFields($this->db);
                        $extralabels = $extrafields->fetch_name_optionals_label($fichinter->table_element);
                        $array_options = $extrafields->getOptionalsFromPost($extralabels);
                        $fichinter->array_options = $array_options;

                        $id = $fichinter->create($user);
                        if ($id <= 0) {
                            setEventMessages($fichinter->error, null, 'errors');
                        }
                    }

                    if (!empty($backtopage)) {
                        $url = $backtopage;
                    } else {
                        $url = 'card.php?track_id=' . $object->track_id;
                    }

                    header("Location: " . $url);
                    exit;
                } else {
                    $this->db->rollback();
                    setEventMessages($this->error, $this->errors, 'errors');
                }
            } else {
                setEventMessages($this->error, $this->errors, 'errors');
            }
        }

        if ($action == 'edit' && $user->rights->ticketsup->write) {
            $error = 0;

            if ($object->fetch(GETPOST('id')) < 0) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
                $_GET["action"] = $_POST["action"] = '';
            }
        }

        if (GETPOST('update') && GETPOST('id') && $user->rights->ticketsup->write) {
            $error = 0;

            $ret = $object->fetch(GETPOST('id'));
            if ($ret < 0) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
                $action = '';
            } elseif (!GETPOST("label")) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label")));
                $action = 'edit';
            } elseif (!GETPOST("subject")) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")));
                $action = 'edit';
            }

            if (!$error) {
                $this->db->begin();

                $object->label = GETPOST("label");
                $object->description = GETPOST("description");

                //...
                $ret = $object->update($user);
                if ($ret <= 0) {
                    $error++;
                    $this->errors = $object->error;
                    $this->errors = $object->errors;
                    $action = 'edit';
                }

                if (!$error && $ret > 0) {
                    $this->db->commit();
                } else {
                    $this->db->rollback();
                }
            }
        }

        if ($action == "mark_ticket_read" && $user->rights->ticketsup->write) {
            $object->fetch('', '', GETPOST("track_id",'alpha'));

            if ($object->markAsRead($user) > 0) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogMesgReadBy', $user->getFullName($langs));
                $ret = $object->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessages($langs->trans('TicketMarkedAsRead'), null, 'mesgs');
                } else {
                    setEventMessages($langs->trans('TicketMarkedAsReadButLogActionNotSaved'), null, 'errors');
                }
                header("Location: card.php?track_id=" . $object->track_id . "&action=view");
                exit;
            } else {
                array_push($this->errors, $object->error);
            }
            $action = 'view';
        }

        if ($action == "assign_user" && GETPOST('btn_assign_user','aplha') && $user->rights->ticketsup->write) {
            $object->fetch('', '', GETPOST("track_id",'alpha'));
            $useroriginassign = $object->fk_user_assign;
            $usertoassign = GETPOST('fk_user_assign','int');

            /*if (! ($usertoassign > 0)) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("AssignedTo")));
                $action = 'view';
            }*/

            if (!$error)
            {
                $ret = $object->assignUser($user, $usertoassign);
                if ($ret < 0) $error++;
            }

            if (! $error)	// Update list of contacts
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

            if (! $error)
            {
                // Log action in ticket logs table
                $object->fetch_user($usertoassign);
                $log_action = $langs->trans('TicketLogAssignedTo', $object->user->getFullName($langs));
                $ret = $object->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessages($langs->trans('TicketAssigned'), null, 'mesgs');
                } else {
                    setEventMessages($langs->trans('TicketAssignedButLogActionNotSaved'), null, 'errors');
                }
                header("Location: card.php?track_id=" . $object->track_id . "&action=view");
                exit;
            } else {
                array_push($this->errors, $object->error);
            }
            $action = 'view';
        }

        if ($action == "change_property" && GETPOST('btn_update_ticket_prop') && $user->rights->ticketsup->write) {
            $this->fetch('', '', GETPOST('track_id','alpha'));

            $fieldtomodify = GETPOST('property') . '_code';
            $fieldtomodify_label = GETPOST('property') . '_label';

            $oldvalue_code = $object->$fieldtomodify;
            $newvalue_code = $object->getValueFrom('c_ticketsup_' . GETPOST('property'), GETPOST('update_value'), 'code');

            $oldvalue_label = $object->$fieldtomodify_label;
            $newvalue_label = $object->getValueFrom('c_ticketsup_' . GETPOST('property'), GETPOST('update_value'), 'label');

            $object->$fieldtomodify = $newvalue_code;

            $ret = $object->update($user);
            if ($ret > 0) {
                $log_action = $langs->trans('TicketLogPropertyChanged', $oldvalue_label, $newvalue_label);
                $ret = $object->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessages($langs->trans('TicketUpdated'), null, 'mesgs');
                }
            }
            $action = 'view';
        }

        if ($action == "new_message" && GETPOST('btn_add_message') && $user->rights->ticketsup->read) {
            $ret = $this->newMessage($user, $action);
            if ($ret) {
                if (!empty($backtopage)) {
                    $url = $backtopage;
                } else {
                    $url = 'card.php?action=view&track_id=' . $object->track_id;
                }

                header("Location: " . $url);
                exit;
            } else {
                setEventMessages($object->error, null, 'errors');
                $action = 'add_message';
            }
        }

        if ($action == "new_public_message" && GETPOST('btn_add_message')) {
            $this->newMessagePublic($user, $action);
        }

        if ($action == "confirm_close" && GETPOST('confirm', 'alpha') == 'yes' && $user->rights->ticketsup->write) {
            $this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha'));
            if ($object->close()) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogClosedBy', $user->getFullName($langs));
                $ret = $object->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessages($langs->trans('TicketMarkedAsClosed'), null, 'mesgs');
                } else {
                    setEventMessages($langs->trans('TicketMarkedAsClosedButLogActionNotSaved'), null, 'warnings');
                }
                $url = 'card.php?action=view&track_id=' . GETPOST('track_id', 'alpha');
                header("Location: " . $url);
            } else {
                $action = '';
                setEventMessages($this->error, $this->errors, 'errors');
            }
        }

        if ($action == "confirm_public_close" && GETPOST('confirm', 'alpha') == 'yes') {
            $this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha'));
            if (($_SESSION['email_customer'] == $object->origin_email || $_SESSION['email_customer'] == $object->thirdparty->email) && $object->close()) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogClosedBy', $_SESSION['email_customer']);
                $ret = $object->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessages('<div class="confirm">' . $langs->trans('TicketMarkedAsClosed') . '</div>', null, 'mesgs');
                } else {
                    setEventMessages($langs->trans('TicketMarkedAsClosedButLogActionNotSaved'), null, 'warnings');
                }
                $url = 'view.php?action=view_ticket&track_id=' . GETPOST('track_id', 'alpha');
                header("Location: " . $url);
            } else {
                setEventMessages($this->error, $this->errors, 'errors');
                $action = '';
            }
        }

        if ($action == 'confirm_delete_ticket' && GETPOST('confirm', 'alpha') == "yes" && $user->rights->ticketsup->delete) {
            if ($this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
                if ($object->delete($user) > 0) {
                    setEventMessages('<div class="confirm">' . $langs->trans('TicketDeletedSuccess') . '</div>', null, 'mesgs');
                    Header("Location: ".DOL_URL_ROOT."/ticketsup/list.php");
                    exit;
                } else {
                    $langs->load("errors");
                    $mesg = '<div class="error">' . $langs->trans($this->error) . '</div>';
                    $action = '';
                }
            }
        }

        // Set parent company
        if ($action == 'set_thirdparty' && $user->rights->societe->creer) {
            if ($this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
                $result = $object->setCustomer(GETPOST('editcustomer', 'int'));
                $url = 'card.php?action=view&track_id=' . GETPOST('track_id', 'alpha');
                header("Location: " . $url);
                exit();
            }
        }

        if ($action == 'set_progression' && $user->rights->ticketsup->write) {
            if ($this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
                $result = $object->setProgression(GETPOST('progress'));
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogProgressSetTo', GETPOST('progress'));
                $ret = $object->createTicketLog($user, $log_action);
                $url = 'card.php?action=view&track_id=' . $object->track_id;
                header("Location: " . $url);
                exit();
            }
        }

        if ($action == 'setsubject') {
            if ($this->fetch(GETPOST('id', 'int'))) {
                if ($action == 'setsubject') {
                    $object->subject = trim(GETPOST('subject', 'alpha'));
                }

                if ($action == 'setsubject' && empty($object->subject)) {
                    $mesg .= ($mesg ? '<br>' : '') . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject"));
                }

                if (!$mesg) {
                    if ($object->update($user) >= 0) {
                        header("Location: " . $_SERVER['PHP_SELF'] . "?track_id=" . $object->track_id);
                        exit;
                    }
                    $mesg = $object->error;
                }
            }
        }


        if ($action == 'confirm_reopen' && $user->rights->ticketsup->manage && !GETPOST('cancel')) {
            if ($this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
                // prevent browser refresh from reopening ticket several times
                if ($object->fk_statut == 8) {
                    $res = $object->setStatut(4);
                    if ($res) {
                        // Log action in ticket logs table
                        $log_action = $langs->trans('TicketLogReopen');
                        $ret = $object->createTicketLog($user, $log_action);
                        $url = 'card.php?action=view&track_id=' . $object->track_id;
                        header("Location: " . $url);
                        exit();
                    }
                }
            }
        } // Categorisation dans projet
        elseif ($action == 'classin' && $user->rights->ticketsup->write) {
            if ($this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
                $object->setProject(GETPOST('projectid'));
                $url = 'card.php?action=view&track_id=' . $object->track_id;
                header("Location: " . $url);
                exit();
            }
        } // Categorisation dans contrat
        elseif ($action == 'setcontract' && $user->rights->ticketsup->write) {
            if ($this->fetch(GETPOST('id', 'int'), '', GETPOST('track_id', 'alpha')) >= 0) {
                $object->setContract(GETPOST('contractid'));
                $url = 'card.php?action=view&track_id=' . $object->track_id;
                header("Location: " . $url);
                exit();
            }
        } elseif ($action == "set_message" && $user->rights->ticketsup->manage) {
            // altairis: manage cancel button
            if (!GETPOST('cancel')) {
                $this->fetch('', '', GETPOST('track_id','alpha'));
                $oldvalue_message = $object->message;
                $fieldtomodify = GETPOST('message_initial');

                $object->message = $fieldtomodify;
                $ret = $object->update($user);
                if ($ret > 0) {
                    $log_action = $langs->trans('TicketInitialMessageModified') . " \n";
                    // include the Diff class
                    dol_include_once('/ticketsup/class/utils_diff.class.php');
                    // output the result of comparing two files as plain text
                    $log_action .= Diff::toString(Diff::compare(strip_tags($oldvalue_message), strip_tags($object->message)));

                    $ret = $object->createTicketLog($user, $log_action);
                    if ($ret > 0) {
                        setEventMessages($langs->trans('TicketMessageSuccesfullyUpdated'), null, 'mesgs');
                    }
                }
            }

            $action = 'view';
        } // Reopen ticket
        elseif ($action == 'confirm_set_status' && $user->rights->ticketsup->write && !GETPOST('cancel')) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                $new_status = GETPOST('new_status', 'int');
                $old_status = $object->fk_statut;
                $res = $object->setStatut($new_status);
                if ($res) {
                    // Log action in ticket logs table
                    $log_action = $langs->trans('TicketLogStatusChanged', $langs->transnoentities($object->statuts_short[$old_status]), $langs->transnoentities($object->statuts_short[$new_status]));
                    $ret = $object->createTicketLog($user, $log_action);
                    $url = 'card.php?action=view&track_id=' . $object->track_id;
                    header("Location: " . $url);
                    exit();
                }
            }
        }

        return 0;
    }

    /**
     * Add new message on a ticket (private area)
     *
     * @param User $user        User for action
     * @param string $action    Action string
     */
    private function newMessage($user, &$action)
    {
        global $mysoc, $conf, $langs;

        if (!class_exists('Contact')) {
            include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        }

        $contactstatic = new Contact($this->db);

        $error = 0;

        $object = new Ticketsup($this->db);
        $ret = $object->fetch('', '', GETPOST('track_id','alpha'));
        $object->socid = $object->fk_soc;
        $object->fetch_thirdparty();
        if ($ret < 0) {
            $error++;
            array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
            $action = '';
        }

        if (!GETPOST("message")) {
            $error++;
            array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("message")));
            $action = 'add_message';
        }

        if (!$error) {
            $object->message = GETPOST("message");
            $object->private = GETPOST("private_message");
            $send_email = GETPOST('send_email', 'int');

            $id = $object->createTicketMessage($user);
            if ($id <= 0) {
                $error++;
                $this->errors = $object->error;
                $this->errors = $object->errors;
                $action = 'add_message';
            }

            if (!$error && $id > 0) {
                setEventMessages($langs->trans('TicketMessageSuccessfullyAdded'), null, 'mesgs');

                /*
                 * Send email to linked contacts
                 */
                if ($send_email > 0) {
                    // Retrieve internal contact datas
                    $internal_contacts = $object->getInfosTicketInternalContact();
                    $sendto = array();
                    if (is_array($internal_contacts) && count($internal_contacts) > 0) {
                        // altairis: set default subject
                        $label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
                        $subject = GETPOST('subject') ? GETPOST('subject') : '[' . $label_title . '- ticket #' . $object->track_id . '] ' . $langs->trans('TicketNewMessage');

                        $message_intro = $langs->trans('TicketNotificationEmailBody', "#" . $object->id);
                        $message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE;

                        $message = $langs->trans('TicketMessageMailIntroText');
                        $message .= "\n\n";
                        $message .= GETPOST('message');

                        //  Coordonnées client
                        $message .= "\n\n";
                        $message .= "==============================================\n";
                        $message .= !empty($object->thirdparty->name) ? $langs->trans('Thirdparty') . " : " . $object->thirdparty->name : '';
                        $message .= !empty($object->thirdparty->town) ? "\n" . $langs->trans('Town') . " : " . $object->thirdparty->town : '';
                        $message .= !empty($object->thirdparty->phone) ? "\n" . $langs->trans('Phone') . " : " . $object->thirdparty->phone : '';

                        // Build array to display recipient list
                        foreach ($internal_contacts as $key => $info_sendto) {
                            // altairis: avoid duplicate notifications
                            if ($info_sendto['id'] == $user->id) {
                                continue;
                            }

                            if ($info_sendto['email'] != '') {
                                if(!empty($info_sendto['email'])) $sendto[] = trim($info_sendto['firstname'] . " " . $info_sendto['lastname']) . " <" . $info_sendto['email'] . ">";

                                //Contact type
                                $recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1') . ' (' . strtolower($info_sendto['libelle']) . ')';
                                $message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient') . ' : ' . $recipient . "\n" : '');
                            }
                        }
                        $message .= "\n";
                        // URL ticket
                        $url_internal_ticket = dol_buildpath('/ticketsup/card.php', 2) . '?track_id=' . $object->track_id;

                        // altairis: make html link on url
                        $message .= "\n" . $langs->trans('TicketNotificationEmailBodyInfosTrackUrlinternal') . ' : ' . '<a href="' . $url_internal_ticket . '">' . $object->track_id . '</a>' . "\n";

                        // Add global email address recipient
                        // altairis: use new TICKETS_NOTIFICATION_EMAIL_TO configuration variable
                        if ($conf->global->TICKETS_NOTIFICATION_ALSO_MAIN_ADDRESS && !in_array($conf->global->TICKETS_NOTIFICATION_EMAIL_TO, $sendto)) {
                            if(!empty($conf->global->TICKETS_NOTIFICATION_EMAIL_TO)) $sendto[] = $conf->global->TICKETS_NOTIFICATION_EMAIL_TO;
                        }

                        // altairis: dont try to send email if no recipient
                        if (!empty($sendto)) {
                            $this->sendTicketMessageByEmail($subject, $message, '', $sendto);
                        }
                    }

                    /*
                     * Email for externals users if not private
                     */
                    if (empty($object->private)) {
                        // Retrieve email of all contacts (external)
                        $external_contacts = $object->getInfosTicketExternalContact();

                        // If no contact, get email from thirdparty
                        if (is_array($external_contacts) && count($external_contacts) === 0) {
                            if (!empty($object->fk_soc)) {
                                $object->fetch_thirdparty($object->fk_soc);
                                $array_company = array(array('firstname' => '', 'lastname' => $object->thirdparty->name, 'email' => $object->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $object->thirdparty->id));
                                $external_contacts = array_merge($external_contacts, $array_company);
                            } elseif (empty($object->fk_soc) && !empty($object->origin_email)) {
                                $array_external = array(array('firstname' => '', 'lastname' => $object->origin_email, 'email' => $object->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $object->thirdparty->id));
                                $external_contacts = array_merge($external_contacts, $array_external);
                            }
                        }

                        $sendto = array();
                        if (is_array($external_contacts) && count($external_contacts) > 0) {
                            // altairis: get default subject for email to external contacts
                            $label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
                            $subject = GETPOST('subject') ? GETPOST('subject') : '[' . $label_title . '- ticket #' . $object->track_id . '] ' . $langs->trans('TicketNewMessage');

                            $message_intro = GETPOST('mail_intro') ? GETPOST('mail_intro') : $conf->global->TICKETS_MESSAGE_MAIL_INTRO;
                            $message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE;

                            // We put intro after
                            $message = GETPOST('message');
                            $message .= "\n\n";

                            foreach ($external_contacts as $key => $info_sendto) {
                                // altairis: avoid duplicate emails to external contacts
                                if ($info_sendto['id'] == $user->contactid) {
                                    continue;
                                }

                                if ($info_sendto['email'] != '' && $info_sendto['email'] != $object->origin_email) {
                                    if(!empty($info_sendto['email'])) $sendto[] = trim($info_sendto['firstname'] . " " . $info_sendto['lastname']) . " <" . $info_sendto['email'] . ">";

                                    $recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1') . ' (' . strtolower($info_sendto['libelle']) . ')';
                                    $message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient') . ' : ' . $recipient . "\n" : '');
                                }
                            }

                            // If public interface is not enable, use link to internal page into mail
                            $url_public_ticket = (!empty($conf->global->TICKETS_ENABLE_PUBLIC_INTERFACE) ?
                            		(!empty($conf->global->TICKETS_URL_PUBLIC_INTERFACE) ?
                            			$conf->global->TICKETS_URL_PUBLIC_INTERFACE . '/view.php' :
                            			dol_buildpath('/ticketsup/public/view.php', 2)
                            		) :
                            		dol_buildpath('/ticketsup/card.php', 2)
                            	) . '?track_id=' . $object->track_id;
                            $message .= "\n" . $langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer') . ' : ' . '<a href="' . $url_public_ticket . '">' . $object->track_id . '</a>' . "\n";

                            // Build final message
                            $message = $message_intro . $message;

                            // Add signature
                            $message .= '<br>' . $message_signature;

                            if (!empty($object->origin_email)) {
                                $sendto[] = $object->origin_email;
                            }

                            if ($object->fk_soc > 0 && ! in_array($object->origin_email, $sendto)) {
	                            $object->socid = $object->fk_soc;
	                            $object->fetch_thirdparty();
                                if(!empty($object->thirdparty->email)) $sendto[] = $object->thirdparty->email;
                            }

                            // altairis: Add global email address reciepient
                            if ($conf->global->TICKETS_NOTIFICATION_ALSO_MAIN_ADDRESS && !in_array($conf->global->TICKETS_NOTIFICATION_EMAIL_TO, $sendto)) {
                                if(!empty($conf->global->TICKETS_NOTIFICATION_EMAIL_TO)) $sendto[] = $conf->global->TICKETS_NOTIFICATION_EMAIL_TO;
                            }

                            // altairis: dont try to send email when no recipient
                            if (!empty($sendto)) {
                                $this->sendTicketMessageByEmail($subject, $message, '', $sendto);
                            }
                        }
                    }
                }

                $this->copyFilesForTicket();

                // Set status to "answered" if not set yet, only for internal users
                if ($object->fk_statut < 3 && !$user->societe_id) {
                    $object->setStatut(3);
                }

                return 1;
            } else {
                setEventMessages($object->error, $object->errors, 'errors');
                return -1;
            }
        } else {
            setEventMessages($this->error, $this->errors, 'errors');
            return -1;
        }
    }

    /**
     * Add new message on a ticket (public area)
     *
     * @param User $user        User for action
     * @param string $action    Action string
     */
    private function newMessagePublic($user, &$action)
    {

        global $mysoc, $conf, $langs;

        $error = 0;
        $ret = $object->fetch('', '', GETPOST('track_id','alpha'));
        $object->socid = $object->fk_soc;
        $object->fetch_thirdparty();
        if ($ret < 0) {
            $error++;
            array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
            $action = '';
        }

        if (!GETPOST("message")) {
            $error++;
            array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("message")));
            $action = 'add_message';
        }

        if (!$error) {
            $object->message = GETPOST("message");
            $id = $object->createTicketMessage($user);
            if ($id <= 0) {
                $error++;
                $this->errors = $object->error;
                $this->errors = $object->errors;
                $action = 'add_message';
            }

            if (!$error && $id > 0) {
                setEventMessages($langs->trans('TicketMessageSuccessfullyAdded'), null, 'mesgs');

                // Retrieve internal contact datas
                $internal_contacts = $object->getInfosTicketInternalContact();
                $sendto = array();
                if (is_array($internal_contacts) && count($internal_contacts) > 0) {
                    $subject = '[' . $mysoc->name . '- ticket #' . $object->track_id . '] ' . $langs->trans('TicketNewMessage');

                    $message = $langs->trans('TicketMessageMailIntroAutoNewPublicMessage', $object->subject);
                    $message .= "\n";
                    $message .= GETPOST('message');
                    $message .= "\n";

                    //  Coordonnées client
                    if ($object->thirdparty->id > 0) {
                        $message .= "\n\n";
                        $message .= "==============================================\n";
                        $message .= $langs->trans('Thirparty') . " : " . $object->thirdparty->name;
                        $message .= !empty($object->thirdparty->town) ? $langs->trans('Town') . " : " . $object->thirdparty->town : '';
                        $message .= "\n";
                        $message .= !empty($object->thirdparty->phone) ? $langs->trans('Phone') . " : " . $object->thirdparty->phone : '';
                        $message .= "\n";
                    }

                    // Build array to display recipient list
                    foreach ($internal_contacts as $key => $info_sendto) {
                        if ($info_sendto['email'] != '') {
                            $sendto[] = trim($info_sendto['firstname'] . " " . $info_sendto['lastname']) . " <" . $info_sendto['email'] . ">";
                        }

                        // Contact type
                        $recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1') . ' (' . strtolower($info_sendto['libelle']) . ')';
                        $message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient') . ' : ' . $recipient . "\n" : '');
                        $message .= "\n";
                    }

                    // URL ticket
                    $url_internal_ticket = dol_buildpath('/ticketsup/card.php', 2) . '?track_id=' . $object->track_id;
                    $message .= "\n" . $langs->trans('TicketNotificationEmailBodyInfosTrackUrlinternal') . ' : ' . $url_internal_ticket . "\n";

                    $message .= "\n\n";

                    $message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE;

                    // Add global email address reciepient
                    if ($conf->global->TICKETS_NOTIFICATION_ALSO_MAIN_ADDRESS && !in_array($conf->global->TICKETS_NOTIFICATION_EMAIL_FROM, $sendto)) {
                        $sendto[] = $conf->global->TICKETS_NOTIFICATION_EMAIL_FROM;
                    }

                    $this->sendTicketMessageByEmail($subject, $message, '', $sendto);
                }

                /*
                 * Email for externals users if not private
                 */

                // Retrieve email of all contacts external
                $external_contacts = $object->getInfosTicketExternalContact();
                $sendto = array();
                if (is_array($external_contacts) && count($external_contacts) > 0) {
                    $subject = '[' . $mysoc->name . '- ticket #' . $object->track_id . '] ' . $langs->trans('TicketNewMessage');

                    $message = $langs->trans('TicketMessageMailIntroAutoNewPublicMessage', $object->subject);
                    $message .= "\n";

                    $message .= GETPOST('message');
                    $message .= "\n\n";

                    $message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE;
                    foreach ($external_contacts as $key => $info_sendto) {
                        if ($info_sendto['email'] != '') {
                            $sendto[] = trim($info_sendto['firstname'] . " " . $info_sendto['lastname']) . " <" . $info_sendto['email'] . ">";
                        }
                        $recipient = '';
                        $recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1') . ' (' . strtolower($info_sendto['libelle']) . ')';
                        $message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient') . ' : ' . $recipient . "\n" : '');
                    }

                    $url_public_ticket = ($conf->global->TICKETS_URL_PUBLIC_INTERFACE ? $conf->global->TICKETS_URL_PUBLIC_INTERFACE . '/view.php' : dol_buildpath('/ticketsup/public/view.php', 2)) . '?track_id=' . $object->track_id;
                    $message .= "\n\n" . $langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer') . ' : ' . $url_public_ticket . "\n";

                    // Add signature
                    $message .= '\n\n' . $message_signature;

                    if (!empty($object->origin_email) && !in_array($object->origin_email, $sendto)) {
                        $sendto[] = $object->origin_email;
                    }
                    if ($object->fk_soc > 0 && !in_array($object->origin_email, $sendto)) {
                        $sendto[] = $object->thirdparty->email;
                    }
                    $this->sendTicketMessageByEmail($subject, $message, '', $sendto);
                }

                $this->copyFilesForTicket();

                $url = 'view.php?action=view_ticket&track_id=' . $object->track_id;
                header("Location: " . $url);
                exit;
            } else {
            	setEventMessages($object->error, $object->errors, 'errors');
            }
        } else {
            setEventMessages($this->error, $this->errors, 'errors');
        }
    }

    /**
     * Fetch object
     *
     * @param	int		$id				ID of ticket
     * @param	string	$ref			Reference of ticket
     * @param	string	$track_id		Track ID of ticket (for public area)
     * @return 	void
     */
    public function fetch($id = 0, $ref = '', $track_id = '')
    {
        $this->getInstanceDao();
        return $this->dao->fetch($id, $ref, $track_id);
    }

    /**
     * Print statut
     *
     * @param		int		$mode		Display mode
     * @return 		void
     */
    public function getLibStatut($mode = 0)
    {
        $this->getInstanceDao();
        $this->dao->fk_statut = $this->fk_statut;
        return $this->dao->getLibStatut($mode);
    }

    /**
     * Get ticket info
     *
     * @param  int $id    Object id
     */
    public function getInfo($id)
    {
        $this->getInstanceDao();
        $this->dao->fetch($id, '', $track_id);

        $this->label = $this->dao->label;
        $this->description = $this->dao->description;
    }

    /**
     * Get action title
     *
     * @param string $action    Type of action
     */
    public function getTitle($action = '')
    {
        global $langs;

        if ($action == 'create_ticket') {
            return $langs->trans("CreateTicket");
        } elseif ($action == 'edit') {
            return $langs->trans("EditTicket");
        } elseif ($action == 'view') {
            return $langs->trans("TicketCard");
        } elseif ($action == 'add_message') {
            return $langs->trans("AddMessage");
        } else {
            return $langs->trans("TicketsManagement");
        }
    }

    /**
     * View html list of logs
     *
     * @param boolean $show_user Show user who make action
     */
    public function viewTicketLogs($show_user = true)
    {
        global $conf, $langs, $bc;

        // Load logs in cache
        $ret = $this->dao->loadCacheLogsTicket();

        if (is_array($this->dao->cache_logs_ticket) && count($this->dao->cache_logs_ticket) > 0) {
            print '<table class="border" style="width:100%;">';

            print '<tr class="liste_titre">';

            print '<th>';
            print $langs->trans('DateCreation');
            print '</th>';

            if ($show_user) {
                print '<th>';
                print $langs->trans('User');
                print '</th>';
            }

            $var = true;

            foreach ($this->dao->cache_logs_ticket as $id => $arraylogs) {
                $var = !$var;
                print "<tr " . $bc[$var] . ">";
                print '<td><strong>';
                print dol_print_date($arraylogs['datec'], 'dayhour');
                print '</strong></td>';

                if ($show_user) {
                    print '<td>';
                    if ($arraylogs['fk_user_create'] > 0) {
                        $userstat = new User($this->db);
                        $res = $userstat->fetch($arraylogs['fk_user_create']);
                        if ($res) {
                            print $userstat->getNomUrl(1);
                        }
                    }
                    print '</td>';
                }
                print '</tr>';
                print "<tr " . $bc[$var] . ">";
                print '<td colspan="2">';
                print dol_nl2br($arraylogs['message']);

                print '</td>';
                print '</tr>';
            }

            print '</table>';
        } else {
            print '<div class="info">' . $langs->trans('NoLogForThisTicket') . '</div>';
        }
    }

    /**
     * View list of logs with timeline view
     *
     * @param 	boolean 	$show_user 	Show user who make action
     * @param	Ticketsup	$object		Object
     */
    public function viewTimelineTicketLogs($show_user = true, $object = true)
    {
    	global $conf, $langs, $bc;

    	// Load logs in cache
    	$ret = $object->loadCacheLogsTicket();

    	if (is_array($object->cache_logs_ticket) && count($object->cache_logs_ticket) > 0) {
    		print '<section id="cd-timeline">';

    		foreach ($object->cache_logs_ticket as $id => $arraylogs) {
    			print '<div class="cd-timeline-block">';
    			print '<div class="cd-timeline-img">';
    			//print '<img src="img/history.png" alt="">';
    			print '</div> <!-- cd-timeline-img -->';

    			print '<div class="cd-timeline-content">';
    			print dol_nl2br($arraylogs['message']);

    			print '<span class="cd-date">';
    			print dol_print_date($arraylogs['datec'], 'dayhour');

    			if ($show_user) {
    				if ($arraylogs['fk_user_create'] > 0) {
    					$userstat = new User($this->db);
    					$res = $userstat->fetch($arraylogs['fk_user_create']);
    					if ($res) {
    						print '<br><small>'.$userstat->getNomUrl(1).'</small>';
    					}
    				}
    			}
    			print '</span>';
    			print '</div> <!-- cd-timeline-content -->';
    			print '</div> <!-- cd-timeline-block -->';
    		}
    		print '</section>';
    	} else {
    		print '<div class="info">' . $langs->trans('NoLogForThisTicket') . '</div>';
    	}
    }

    /**
     * Show ticket original message
     *
     * @param 	User		$user		User wich display
     * @param 	string 		$action    	Action mode
     * @param	TicketSup	$object		Object ticket
     * @return	void
     */
    public function viewTicketOriginalMessage($user, $action, $object)
    {
    	global $langs;
    	if (!empty($user->rights->ticketsup->manage) && $action == 'edit_message_init') {
    		// MESSAGE

    		print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
    		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    		print '<input type="hidden" name="track_id" value="' . $object->track_id . '">';
    		print '<input type="hidden" name="action" value="set_message">';
    	}

    	// Initial message
    	print '<div class="underbanner clearboth"></div>';
    	print '<div class="div-table-responsive-no-min">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
    	print '<table class="border centpercent margintable">';
    	print '<tr class="liste_titre"><td class="nowrap" colspan="2">';
    	print '<strong>' . $langs->trans("InitialMessage") . '</strong> ';
    	if ($user->rights->ticketsup->manage) {
    		print '<a  href="' . $_SERVER['PHP_SELF'] . '?action=edit_message_init&amp;track_id=' . $object->track_id . '">' . img_edit($langs->trans('Modify')) . ' ' . $langs->trans('Modify') . '</a>';
    	}
    	print '</td></tr>';

    	print '<tr>';

    	print '<td>';
    	if (!empty($user->rights->ticketsup->manage) && $action == 'edit_message_init') {
    		// MESSAGE
    		$msg = GETPOST('message_initial', 'alpha') ? GETPOST('message_initial', 'alpha') : $object->message;
    		include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
    		$uselocalbrowser = true;
    		$doleditor = new DolEditor('message_initial', $msg, '100%', 250, 'dolibarr_details', 'In', true, $uselocalbrowser);
    		$doleditor->Create();
    	} else {
    		// Deal with format differences (text / HTML)
    		if (dol_textishtml($object->message)) {
    			print $object->message;
    		} else {
    			print dol_nl2br($object->message);
    		}

    		//print '<div>' . $object->message . '</div>';
    	}
    	print '</td>';
    	print '</tr>';
    	print '</table>';
    	if ($user->rights->ticketsup->manage && $action == 'edit_message_init') {
    		print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
    		print ' <input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '">';
    		print '</form>';
    	}
    }
    /**
     * View html list of message for ticket
     *
     * @param boolean $show_private Show private messages
     * @param boolean $show_user    Show user who make action
     */
    public function viewTicketMessages($show_private, $show_user = true)
    {
        global $conf, $langs, $user, $bc;
		global $object;

        // Load logs in cache
        $ret = $object->loadCacheMsgsTicket();
        $action = GETPOST('action');

        $this->viewTicketOriginalMessage($user, $action);

        if (is_array($object->cache_msgs_ticket) && count($object->cache_msgs_ticket) > 0) {
            print_titre($langs->trans('TicketMailExchanges'));

            print '<table class="border" style="width:100%;">';

            print '<tr class="liste_titre">';

            print '<td>';
            print $langs->trans('DateCreation');
            print '</td>';

            if ($show_user) {
                print '<td>';
                print $langs->trans('User');
                print '</td>';
            }

            foreach ($object->cache_msgs_ticket as $id => $arraymsgs) {
                if (!$arraymsgs['private']
                    || ($arraymsgs['private'] == "1" && $show_private)
                ) {
                    //print '<tr>';
                    $var = !$var;
                    print "<tr " . $bc[$var] . ">";
                    print '<td><strong>';
                    print dol_print_date($arraymsgs['datec'], 'dayhour');
                    print '<strong></td>';
                    if ($show_user) {
                        print '<td>';
                        if ($arraymsgs['fk_user_action'] > 0) {
                            $userstat = new User($this->db);
                            $res = $userstat->fetch($arraymsgs['fk_user_action']);
                            if ($res) {
                                print $userstat->getNomUrl(0);
                            }
                        } else {
                            print $langs->trans('Customer');
                        }
                        print '</td>';
                    }
                    print '</td>';
                    print "<tr " . $bc[$var] . ">";
                    print '<td colspan="2">';
                    print $arraymsgs['message'];
                    print '</td>';
                    print '</tr>';
                }
            }

            print '</table>';
        } else {
            print '<div class="info">' . $langs->trans('NoMsgForThisTicket') . '</div>';
        }
    }

    /**
     * View list of message for ticket with timeline display
     *
     * @param 	boolean 	$show_private Show private messages
     * @param 	boolean 	$show_user    Show user who make action
     * @param	Ticketsup	$object		 Object ticketsup
     */
    public function viewTicketTimelineMessages($show_private, $show_user, Ticketsup $object)
    {
    	global $conf, $langs, $user, $bc;

    	// Load logs in cache
    	$ret = $object->loadCacheMsgsTicket();
    	$action = GETPOST('action');

    	if (is_array($object->cache_msgs_ticket) && count($object->cache_msgs_ticket) > 0) {
    		print '<section id="cd-timeline">';

    		foreach ($object->cache_msgs_ticket as $id => $arraymsgs) {
    			if (!$arraymsgs['private']
    			|| ($arraymsgs['private'] == "1" && $show_private)
    			) {
    				print '<div class="cd-timeline-block">';
    				print '<div class="cd-timeline-img">';
    				print '<img src="img/messages.png" alt="">';
    				print '</div> <!-- cd-timeline-img -->';

    				print '<div class="cd-timeline-content">';
    				print $arraymsgs['message'];

    				print '<span class="cd-date">';
    				print dol_print_date($arraymsgs['datec'], 'dayhour');

                    if ($show_user) {
                        if ($arraymsgs['fk_user_action'] > 0) {
                            $userstat = new User($this->db);
                            $res = $userstat->fetch($arraymsgs['fk_user_action']);
                            if ($res) {
                                print '<br>';
                                print $userstat->getNomUrl(1);
                            }
                        } else {
                            print '<br>';
                            print $langs->trans('Customer');
                        }
                    }
    				print '</span>';
    				print '</div> <!-- cd-timeline-content -->';
    				print '</div> <!-- cd-timeline-block -->';
                }
    		}
    		print '</section>';
    	} else {
    		print '<div class="info">' . $langs->trans('NoMsgForThisTicket') . '</div>';
    	}
    }

    /**
     * load_previous_next_ref
     *
     * @param string		$filter			Filter
     * @param int			$fieldid		Id
     * @return int			0
     */
    function load_previous_next_ref($filter, $fieldid)
    {
        $this->getInstanceDao();
        return $object->load_previous_next_ref($filter, $fieldid);
    }

    /**
     * Send ticket by email to linked contacts
     *
     * @param string $subject          Email subject
     * @param string $message          Email message
     * @param int    $send_internal_cc Receive a copy on internal email ($conf->global->TICKETS_NOTIFICATION_EMAIL_FROM)
     * @param array  $array_receiver   Array of receiver. exemple array('name' => 'John Doe', 'email' => 'john@doe.com', etc...)
     */
    public function sendTicketMessageByEmail($subject, $message, $send_internal_cc = 0, $array_receiver = array())
    {
        global $conf, $langs;

        if ($conf->global->TICKETS_DISABLE_ALL_MAILS) {
            dol_syslog(get_class($this) . '::sendTicketMessageByEmail: Emails are disable into ticketsup setup by option TICKETSUP_DISABLE_ALL_MAILS', LOG_WARNING);
            return '';
        }

        $langs->load("mails");

        if (!class_exists('Contact')) {
            include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        }

        $contactstatic = new Contact($this->db);

        // If no receiver defined, load all ticket linked contacts
        if (!is_array($array_receiver) || !count($array_receiver) > 0) {
            $array_receiver = $object->getInfosTicketInternalContact();
            $array_receiver = array_merge($array_receiver, $object->getInfosTicketExternalContact());
        }

        if ($send_internal_cc) {
            $sendtocc = $conf->global->TICKETS_NOTIFICATION_EMAIL_FROM;
        }

        $from = $conf->global->TICKETS_NOTIFICATION_EMAIL_FROM;
        if (is_array($array_receiver) && count($array_receiver) > 0) {
            foreach ($array_receiver as $key => $receiver) {
                // Create form object
                include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
                $formmail = new FormMail($this->db);

                $attachedfiles = $formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                $message_to_send = dol_nl2br($message);

                // Envoi du mail
                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
                }
                include_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
                $mailfile = new CMailFile($subject, $receiver, $from, $message_to_send, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1);
                if ($mailfile->error) {
                    setEventMessages($mailfile->error, null, 'errors');
                } else {
                    $result = $mailfile->sendfile();
                    if ($result) {
                        setEventMessages($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($receiver, 2)), null, 'mesgs');
                    } else {
                        $langs->load("other");
                        if ($mailfile->error) {
                            setEventMessages($langs->trans('ErrorFailedToSendMail', $from, $receiver), null, 'errors');
                            dol_syslog($langs->trans('ErrorFailedToSendMail', $from, $receiver) . ' : ' . $mailfile->error);
                        } else {
                            setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'errors');
                        }
                    }
                }
                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                }
            }
        } else {
            $langs->load("other");
            setEventMessages($langs->trans('ErrorMailRecipientIsEmptyForSendTicketMessage'), null, 'warnings');
        }
    }

    /**
     * Copy files into ticket directory
     * Used for files linked into messages
     *
     * @return	void
     */
    public function copyFilesForTicket()
    {
        global $conf, $object;

        // Create form object
        include_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
        include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        include_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';

        $maxwidthsmall = 270;
        $maxheightsmall = 150;
        $maxwidthmini = 128;
        $maxheightmini = 72;

        $formmail = new FormMail($this->db);

        $attachedfiles = $formmail->get_attached_files();
        $filepath = $attachedfiles['paths'];
        $filename = $attachedfiles['names'];
        $mimetype = $attachedfiles['mimes'];

        // Copy files into ticket directory
        $destdir = $conf->ticketsup->dir_output . '/' . $object->track_id;

        if (!dol_is_dir($destdir)) {
            dol_mkdir($destdir);
        }
        foreach ($filename as $i => $val) {
            $res = dol_move($filepath[$i], $destdir . '/' . $filename[$i]);
            if (image_format_supported($destdir . '/' . $filename[$i]) == 1) {
                // Create small thumbs for image (Ratio is near 16/9)
                // Used on logon for example
                $imgThumbSmall = vignette($destdir . '/' . $filename[$i], $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
                // Create mini thumbs for image (Ratio is near 16/9)
                // Used on menu or for setup page for example
                $imgThumbMini = vignette($destdir . '/' . $filename[$i], $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
            }
            $formmail->remove_attached_files($i);
        }
    }

    /**
     * Print html navbar with link to set ticket status
     *
     * @param	Ticketsup	$object		Ticket sup
     * @return	void
     */
    public function viewStatusActions(Ticketsup $object)
    {
        global $langs;

        print '<div class="div-table-responsive-no-min">';
        print '<div class="tagtable noborder ">';
        print '<div class="tagtr liste_titre">';
        print '<div class="tagtd">';
        print '<strong>' . $langs->trans('TicketChangeStatus') . '</strong>';
        print '</div>';
        // Exclude status which requires specific method
        $exclude_status = array(Ticketsup::STATUS_CLOSED, Ticketsup::STATUS_CANCELED);
        // Exclude actual status
        $exclude_status = array_merge($exclude_status, array(intval($object->fk_statut)));

        // Sort results to be similar to status object list
        //sort($exclude_status);

        //print '<br><div>';
        foreach ($object->statuts_short as $status => $statut_label) {
            if (!in_array($status, $exclude_status)) {
                print '<div class="tagtd">';

                if ($status == 1)
                	$urlforbutton = $_SERVER['PHP_SELF'] . '?track_id=' . $object->track_id . '&action=mark_ticket_read';	// To set as read, we use a dedicated action
               	else
               		$urlforbutton = $_SERVER['PHP_SELF'] . '?track_id=' . $object->track_id . '&action=set_status&new_status=' . $status;

                print '<a class="button" href="' . $urlforbutton . '">';
                print img_picto($langs->trans($object->statuts_short[$status]), 'statut' . $status . '.png@ticketsup') . ' ' . $langs->trans($object->statuts_short[$status]);
                print '</a>';
                print '</div>';
            }
        }
        print '</div></div></div><br>';
    }


  	/**
  	 * deleteObjectLinked
  	 *
  	 * @return number
  	 */
    public function deleteObjectLinked()
    {
    	return $this->dao->deleteObjectLinked();
    }

    /**
     * Hook to add email element template
     *
     * @param array 		$parameters   Parameters
     * @param Ticketsup		$object       Object for action
     * @param string 		$action       Action string
     * @param HookManager 	$hookmanager  Hookmanager object
     * @return int
     */
    public function emailElementlist($parameters, &$object, &$action, $hookmanager)
    {
    	global $langs;

    	$error = 0;

    	if (in_array('admin', explode(':', $parameters['context']))) {
            $this->results = array('ticketsup_send' => $langs->trans('MailToSendTicketsupMessage'));
    	}

    	if (! $error) {
            return 0; // or return 1 to replace standard code
    	} else {
    		$this->errors[] = 'Error message';
    		return -1;
    	}
    }
}
