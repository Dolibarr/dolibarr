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
     *     @return	int						0
     */
    public function doActions(&$action = '')
    {
        global $conf, $user, $langs, $mysoc;

        $this->getInstanceDao();

        /*
         * Add file in email form
         */
        if (GETPOST('addfile')) {
            // altairis : allow files from public interface
            if (GETPOST('track_id')) {
                $res = $this->dao->fetch('', GETPOST('track_id'));
            }

            ////if($res > 0)
            ////{
            include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            // Set tmp directory TODO Use a dedicated directory for temp mails files
            $vardir = $conf->ticketsup->dir_output . (!empty($this->dao->track_id) ?  '/' . dol_sanitizeFileName($this->dao->track_id) : '');
            $upload_dir_tmp = $vardir . '/temp';
            if (!dol_is_dir($upload_dir_tmp)) {
                dol_mkdir($upload_dir_tmp);
            }
            dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', dol_print_date(dol_now(), '%Y%m%d%H%M%S') . '-__file__');
            $action = !empty($this->dao->track_id) ? 'add_message' : 'create_ticket';
            ////}
        }

        /*
         * Remove file in email form
         */
        if (GETPOST('removedfile')) {
            // altairis : allow files from public interface
            if (GETPOST('track_id')) {
                $res = $this->dao->fetch('', GETPOST('track_id'));
            }

            ////if($res > 0)
            ////{
            include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            // Set tmp directory
            $vardir = $conf->ticketsup->dir_output . (!empty($this->dao->track_id) ?  '/' . dol_sanitizeFileName($this->dao->track_id) : '');
            $upload_dir_tmp = $vardir . '/temp';

            // TODO Delete only files that was uploaded from email form
            dol_remove_file_process($_POST['removedfile'], 0);
            $action = !empty($this->dao->track_id) ? 'add_message' : 'create_ticket';
            ////}
        }

        if (GETPOST('add_ticket') && $user->rights->ticketsup->create) {
            $error = 0;

            if (!GETPOST("subject")) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")));
                $action = 'create_ticket';
            } elseif (!GETPOST("message")) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("message")));
                $action = 'create_ticket';
            }

            if (!$error) {
                $this->db->begin();

                $this->dao->track_id = generate_random_id(16);

                $this->dao->ref = GETPOST("ref", 'alpha');
                $this->dao->fk_soc = GETPOST("socid", 'int');
                $this->dao->subject = GETPOST("subject", 'alpha');
                $this->dao->message = GETPOST("message");

                $this->dao->type_code = GETPOST("type_code", 'alpha');
                $this->dao->category_code = GETPOST("category_code", 'alpha');
                $this->dao->severity_code = GETPOST("severity_code", 'alpha');
                $notNotifyTiers = GETPOST("not_notify_tiers_at_create", 'alpha');
                $this->dao->notify_tiers_at_create = empty($notNotifyTiers) ? 1 : 0;

                $extrafields = new ExtraFields($this->db);
                $extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element);
                $ret = $extrafields->setOptionalsFromPost($extralabels, $this->dao);

                $id = $this->dao->create($user);
                if ($id <= 0) {
                    $error++;
                    $errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
                    array_push($this->errors, $this->dao->error ? array($this->dao->error) : $this->dao->errors);
                    $action = 'create_ticket';
                }

                if (!$error && $id > 0) {
                    $this->db->commit();

                    // File transfer
                    $this->copyFilesForTicket();

                    // Add contact
                    $contactid = GETPOST('contactid', 'int');
                    $type_contact = GETPOST("type", 'alpha');

                    if ($contactid > 0 && $type_contact) {
                        $result = $this->dao->add_contact($contactid, GETPOST("type"), 'external');
                    }

                    // altairis: link ticket to project
                    if (GETPOST('projectid')) {
                        $this->dao->setProject(GETPOST('projectid'));
                    }

                    // Auto assign user
                    if ($conf->global->TICKETS_AUTO_ASSIGN_USER_CREATE) {
                        $result = $this->dao->assignUser($user, $user->id, 1);
                        $this->dao->add_contact($user->id, "SUPPORTTEC", 'internal');
                    }

                    // Auto assign contrat
                    $contractid = 0;
                    if ($conf->global->TICKETS_AUTO_ASSIGN_CONTRACT_CREATE) {
                        $contrat = new Contrat($this->db);
                        $contrat->socid = $this->dao->fk_soc;
                        $list = $contrat->getListOfContracts();

                        if (is_array($list) && !empty($list)) {
                            if (count($list) == 1) {
                                $contractid = $list[0]->id;
                                $this->dao->setContract($contractid);
                            } else {
                            }
                        }
                    }

                    // Auto create fiche intervention
                    if ($conf->global->TICKETS_AUTO_CREATE_FICHINTER_CREATE) {
                        $fichinter = new Fichinter($this->db);
                        $fichinter->socid = $this->dao->fk_soc;
                        $fichinter->fk_project = GETPOST('projectid', 'int');
                        $fichinter->fk_contrat = $contractid;
                        $fichinter->author = $user->id;
                        $fichinter->modelpdf = 'soleil';
                        $fichinter->origin = $this->dao->element;
                        $fichinter->origin_id = $this->dao->id;

                        // Extrafields
                        $extrafields = new ExtraFields($this->db);
                        $extralabels = $extrafields->fetch_name_optionals_label($fichinter->table_element);
                        $array_options = $extrafields->getOptionalsFromPost($extralabels);
                        $fichinter->array_options = $array_options;

                        $id = $fichinter->create($user);
                        if ($id <= 0) {
                            setEventMessage($fichinter->error, 'errors');
                        }
                    }

                    if (!empty($backtopage)) {
                        $url = $backtopage;
                    } else {
                        $url = 'card.php?track_id=' . $this->dao->track_id;
                    }

                    header("Location: " . $url);
                    exit;
                } else {
                    $this->db->rollback();
                    setEventMessage($this->errors, 'errors');
                }
            } else {
                setEventMessage($this->errors, 'errors');
            }
        }

        if ($action == 'edit' && $user->rights->ticketsup->write) {
            $error = 0;

            if ($this->dao->fetch(GETPOST('id')) < 0) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
                $_GET["action"] = $_POST["action"] = '';
            }
        }

        if (GETPOST('update') && GETPOST('id') && $user->rights->ticketsup->write) {
            $error = 0;

            $ret = $this->dao->fetch(GETPOST('id'));
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

                $this->dao->label = GETPOST("label");
                $this->dao->description = GETPOST("description");

                //...
                $ret = $this->dao->update(GETPOST('id'), $user);
                if ($ret <= 0) {
                    $error++;
                    $errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
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
            $this->dao->fetch('', GETPOST("track_id"));

            if ($this->dao->markAsRead($user) > 0) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogMesgReadBy', $user->getFullName($langs));
                $ret = $this->dao->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessage($langs->trans('TicketMarkedAsRead'));
                } else {
                    setEventMessage($langs->trans('TicketMarkedAsReadButLogActionNotSaved'), 'errors');
                }
                header("Location: card.php?track_id=" . $this->dao->track_id . "&action=view");
                exit;
            } else {
                array_push($this->errors, $this->dao->error);
            }
            $action = 'view';
        }

        if ($action == "assign_user" && GETPOST('btn_assign_user') && $user->rights->ticketsup->write) {
            $this->dao->fetch('', GETPOST("track_id"));

            $useroriginassign = $this->dao->fk_user_assign;
            $usertoassign = GETPOST('fk_user_assign');
            if (!$usertoassign) {
                $error++;
                array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("UserAssignedTo")));
                $action = 'view';
            }

            if (!$error) {
                $ret = $this->dao->assignUser($user, $usertoassign);

                if ($ret) {
                    // Si déjà un user assigné on le supprime des contacts
                    if ($useroriginassign > 0) {
                        $internal_contacts = $this->dao->listeContact(-1, 'internal');

                        foreach ($internal_contacts as $key => $contact) {
                            if ($contact['code'] == "SUPPORTTEC" && $contact['id'] == $useroriginassign) {
                            }
                            {
                                //print "user à effacer : ".$useroriginassign;
                                $this->dao->delete_contact($contact['rowid']);
                            }
                        }
                    }
                    $this->dao->add_contact($usertoassign, "SUPPORTTEC", 'internal', $notrigger = 0);
                }

                // Log action in ticket logs table
                $this->dao->fetch_user($usertoassign);
                $log_action = $langs->trans('TicketLogAssignedTo', $this->dao->user->getFullName($langs));
                $ret = $this->dao->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessage($langs->trans('TicketAssigned'));
                } else {
                    setEventMessage($langs->trans('TicketAssignedButLogActionNotSaved'), 'errors');
                }
                header("Location: card.php?track_id=" . $this->dao->track_id . "&action=view");
                exit;
            } else {
                array_push($this->errors, $this->dao->error);
            }
            $action = 'view';
        }

        if ($action == "change_property" && GETPOST('btn_update_ticket_prop') && $user->rights->ticketsup->write) {
            $this->fetch('', GETPOST('track_id'));

            $fieldtomodify = GETPOST('property') . '_code';
            $fieldtomodify_label = GETPOST('property') . '_label';

            $oldvalue_code = $this->dao->$fieldtomodify;
            $newvalue_code = $this->dao->getValueFrom('c_ticketsup_' . GETPOST('property'), GETPOST('update_value'), 'code');

            $oldvalue_label = $this->dao->$fieldtomodify_label;
            $newvalue_label = $this->dao->getValueFrom('c_ticketsup_' . GETPOST('property'), GETPOST('update_value'), 'label');

            $this->dao->$fieldtomodify = $newvalue_code;

            $ret = $this->dao->update($user);
            if ($ret > 0) {
                $log_action = $langs->trans('TicketLogPropertyChanged', $oldvalue_label, $newvalue_label);
                $ret = $this->dao->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessage($langs->trans('TicketUpdated'));
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
                    $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
                }

                header("Location: " . $url);
                exit;
            } else {
                setEventMessage($this->dao->error, 'errors');
                $action = 'add_message';
            }
        }

        if ($action == "new_public_message" && GETPOST('btn_add_message')) {
            $this->newMessagePublic($user, $action);
        }

        if ($action == "confirm_close" && GETPOST('confirm', 'alpha') == 'yes' && $user->rights->ticketsup->write) {
            $this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha'));
            if ($this->dao->close()) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogClosedBy', $user->getFullName($langs));
                $ret = $this->dao->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessage('<div class="confirm">' . $langs->trans('TicketMarkedAsClosed') . '</div>');
                } else {
                    setEventMessage($langs->trans('TicketMarkedAsClosedButLogActionNotSaved'), 'warnings');
                }
                $url = 'card.php?action=view&track_id=' . GETPOST('track_id', 'alpha');
                header("Location: " . $url);
            } else {
                $action = '';
                setEventMessage($this->error, 'errors');
            }
        }

        if ($action == "confirm_public_close" && GETPOST('confirm', 'alpha') == 'yes') {
            $this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha'));
            if (($_SESSION['email_customer'] == $this->dao->origin_email || $_SESSION['email_customer'] == $this->dao->thirdparty->email) && $this->dao->close()) {
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogClosedBy', $_SESSION['email_customer']);
                $ret = $this->dao->createTicketLog($user, $log_action);
                if ($ret > 0) {
                    setEventMessage('<div class="confirm">' . $langs->trans('TicketMarkedAsClosed') . '</div>');
                } else {
                    setEventMessage($langs->trans('TicketMarkedAsClosedButLogActionNotSaved'), 'warnings');
                }
                $url = 'view.php?action=view_ticket&track_id=' . GETPOST('track_id', 'alpha');
                header("Location: " . $url);
            } else {
                setEventMessage($this->error, 'errors');
                $action = '';
            }
        }

        if ($action == 'confirm_delete_ticket' && GETPOST('confirm', 'alpha') == "yes" && $user->rights->ticketsup->delete) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                if ($this->dao->delete($user) > 0) {
                    setEventMessage('<div class="confirm">' . $langs->trans('TicketDeletedSuccess') . '</div>');
                    Header("Location: index.php");
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
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                $result = $this->dao->setCustomer(GETPOST('editcustomer', 'int'));
                $url = 'card.php?action=view&track_id=' . GETPOST('track_id', 'alpha');
                header("Location: " . $url);
                exit();
            }
        }

        if ($action == 'set_progression' && $user->rights->ticketsup->write) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                $result = $this->dao->setProgression(GETPOST('progress'));
                // Log action in ticket logs table
                $log_action = $langs->trans('TicketLogProgressSetTo', GETPOST('progress'));
                $ret = $this->dao->createTicketLog($user, $log_action);
                $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
                header("Location: " . $url);
                exit();
            }
        }

        if ($action == 'setsubject') {
            if ($this->fetch(GETPOST('id', 'int'))) {
                if ($action == 'setsubject') {
                    $this->dao->subject = trim(GETPOST('subject', 'alpha'));
                }

                if ($action == 'setsubject' && empty($this->dao->subject)) {
                    $mesg .= ($mesg ? '<br>' : '') . $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject"));
                }

                if (!$mesg) {
                    if ($this->dao->update($user) >= 0) {
                        header("Location: " . $_SERVER['PHP_SELF'] . "?track_id=" . $this->dao->track_id);
                        exit;
                    }
                    $mesg = $this->dao->error;
                }
            }
        }

        if ($action == "set_extrafields" && GETPOST('btn_edit_extrafields') && $user->rights->ticketsup->write && !GETPOST('cancel')) {
            $res = $this->fetch('', GETPOST('track_id'));

            $extrafields = new ExtraFields($this->db);
            $extralabels = $extrafields->fetch_name_optionals_label($this->dao->table_element);
            $ret = $extrafields->setOptionalsFromPost($extralabels, $this->dao);

            $ret = $this->dao->update($user);
            if ($ret > 0) {
                setEventMessage($langs->trans('TicketUpdated'));
                $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
                header("Location: " . $url);
                exit();
            }

            $action = 'view';
        } // Reopen ticket
        elseif ($action == 'confirm_reopen' && $user->rights->ticketsup->manage && !GETPOST('cancel')) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                // prevent browser refresh from reopening ticket several times
                if ($this->dao->fk_statut == 8) {
                    $res = $this->dao->setStatut(4);
                    if ($res) {
                        // Log action in ticket logs table
                        $log_action = $langs->trans('TicketLogReopen');
                        $ret = $this->dao->createTicketLog($user, $log_action);
                        $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
                        header("Location: " . $url);
                        exit();
                    }
                }
            }
        } // Categorisation dans projet
        elseif ($action == 'classin' && $user->rights->ticketsup->write) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                $this->dao->setProject(GETPOST('projectid'));
                $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
                header("Location: " . $url);
                exit();
            }
        } // Categorisation dans contrat
        elseif ($action == 'setcontract' && $user->rights->ticketsup->write) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                $this->dao->setContract(GETPOST('contractid'));
                $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
                header("Location: " . $url);
                exit();
            }
        } elseif ($action == "set_message" && $user->rights->ticketsup->manage) {
            // altairis: manage cancel button
            if (!GETPOST('cancel')) {
                $this->fetch('', GETPOST('track_id'));
                $oldvalue_message = $this->dao->message;
                $fieldtomodify = GETPOST('message_initial');

                $this->dao->message = $fieldtomodify;
                $ret = $this->dao->update($user);
                if ($ret > 0) {
                    $log_action = $langs->trans('TicketInitialMessageModified') . " \n";
                    // include the Diff class
                    dol_include_once('/ticketsup/class/utils_diff.class.php');
                    // output the result of comparing two files as plain text
                    $log_action .= Diff::toString(Diff::compare(strip_tags($oldvalue_message), strip_tags($this->dao->message)));

                    $ret = $this->dao->createTicketLog($user, $log_action);
                    if ($ret > 0) {
                        setEventMessage($langs->trans('TicketMessageSuccesfullyUpdated'));
                    }
                }
            }

            $action = 'view';
        } // Reopen ticket
        elseif ($action == 'confirm_set_status' && $user->rights->ticketsup->write && !GETPOST('cancel')) {
            if ($this->fetch(GETPOST('id', 'int'), GETPOST('track_id', 'alpha')) >= 0) {
                $new_status = GETPOST('new_status', 'int');
                $old_status = $this->dao->fk_statut;
                $res = $this->dao->setStatut($new_status);
                if ($res) {
                    // Log action in ticket logs table
                    $log_action = $langs->trans('TicketLogStatusChanged', $langs->transnoentities($this->dao->statuts_short[$old_status]), $langs->transnoentities($this->dao->statuts_short[$new_status]));
                    $ret = $this->dao->createTicketLog($user, $log_action);
                    $url = 'card.php?action=view&track_id=' . $this->dao->track_id;
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
     * @param User $user
     * @param string $action
     */
    private function newMessage($user, &$action)
    {
        global $mysoc, $conf, $langs;

        if (!class_exists('Contact')) {
            include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        }

        $contactstatic = new Contact($this->db);

        $error = 0;
        $ret = $this->dao->fetch('', GETPOST('track_id'));
        $this->dao->socid = $this->dao->fk_soc;
        $this->dao->fetch_thirdparty();
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
            $this->dao->message = GETPOST("message");
            $this->dao->private = GETPOST("private_message");
            $send_email = GETPOST('send_email', 'int');

            $id = $this->dao->createTicketMessage($user);
            if ($id <= 0) {
                $error++;
                $errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
                array_push($this->errors, $this->dao->error ? array($this->dao->error) : $this->dao->errors);
                $action = 'add_message';
            }

            if (!$error && $id > 0) {
                setEventMessage($langs->trans('TicketMessageSuccessfullyAdded'));

                /*
                 * Send email to linked contacts
                 */
                if ($send_email > 0) {
                    // Retrieve internal contact datas
                    $internal_contacts = $this->dao->getInfosTicketInternalContact();
                    $sendto = array();
                    if (is_array($internal_contacts) && count($internal_contacts) > 0) {
                        // altairis: set default subject
                        $label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
                        $subject = GETPOST('subject') ? GETPOST('subject') : '[' . $label_title . '- ticket #' . $this->dao->track_id . '] ' . $langs->trans('TicketNewMessage');

                        $message_intro = $langs->trans('TicketNotificationEmailBody', "#" . $this->dao->id);
                        $message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKETS_MESSAGE_MAIL_SIGNATURE;

                        $message = $langs->trans('TicketMessageMailIntroText');
                        $message .= "\n\n";
                        $message .= GETPOST('message');

                        //  Coordonnées client
                        $message .= "\n\n";
                        $message .= "==============================================\n";
                        $message .= !empty($this->dao->thirdparty->name) ? $langs->trans('Thirdparty') . " : " . $this->dao->thirdparty->name : '';
                        $message .= !empty($this->dao->thirdparty->town) ? "\n" . $langs->trans('Town') . " : " . $this->dao->thirdparty->town : '';
                        $message .= !empty($this->dao->thirdparty->phone) ? "\n" . $langs->trans('Phone') . " : " . $this->dao->thirdparty->phone : '';

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
                        $url_internal_ticket = dol_buildpath('/ticketsup/card.php', 2) . '?track_id=' . $this->dao->track_id;

                        // altairis: make html link on url
                        $message .= "\n" . $langs->trans('TicketNotificationEmailBodyInfosTrackUrlinternal') . ' : ' . '<a href="' . $url_internal_ticket . '">' . $this->dao->track_id . '</a>' . "\n";

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
                    if (empty($this->dao->private)) {
                        // Retrieve email of all contacts (external)
                        $external_contacts = $this->dao->getInfosTicketExternalContact();

                        // If no contact, get email from thirdparty
                        if (is_array($external_contacts) && count($external_contacts) === 0) {
                            if (!empty($this->dao->fk_soc)) {
                                $this->dao->fetch_thirdparty($this->dao->fk_soc);
                                $array_company = array(array('firstname' => '', 'lastname' => $this->dao->thirdparty->name, 'email' => $this->dao->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $this->dao->thirdparty->id));
                                $external_contacts = array_merge($external_contacts, $array_company);
                            } elseif (empty($this->dao->fk_soc) && !empty($this->dao->origin_email)) {
                                $array_external = array(array('firstname' => '', 'lastname' => $this->dao->origin_email, 'email' => $this->dao->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $this->dao->thirdparty->id));
                                $external_contacts = array_merge($external_contacts, $array_external);
                            }
                        }

                        $sendto = array();
                        if (is_array($external_contacts) && count($external_contacts) > 0) {
                            // altairis: get default subject for email to external contacts
                            $label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
                            $subject = GETPOST('subject') ? GETPOST('subject') : '[' . $label_title . '- ticket #' . $this->dao->track_id . '] ' . $langs->trans('TicketNewMessage');

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

                                if ($info_sendto['email'] != '' && $info_sendto['email'] != $this->dao->origin_email) {
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
                            	) . '?track_id=' . $this->dao->track_id;
                            $message .= "\n" . $langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer') . ' : ' . '<a href="' . $url_public_ticket . '">' . $this->dao->track_id . '</a>' . "\n";

                            // Build final message
                            $message = $message_intro . $message;

                            // Add signature
                            $message .= '<br />' . $message_signature;

                            if (!empty($this->dao->origin_email)) {
                                $sendto[] = $this->dao->origin_email;
                            }
                            
                            if ($this->dao->fk_soc > 0 && ! in_array($this->dao->origin_email, $sendto)) {
	                            $this->dao->socid = $this->dao->fk_soc;
	                            $this->dao->fetch_thirdparty();
                                if(!empty($this->dao->thirdparty->email)) $sendto[] = $this->dao->thirdparty->email;
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
                if ($this->dao->fk_statut < 3 && !$user->societe_id) {
                    $this->dao->setStatut(3);
                }

                return 1;
            } else {
                return -1;
                setEventMessage($this->dao->error, 'errors');
            }
        } else {
            return -1;
            setEventMessage($this->errors, 'errors');
        }
    }

    /**
     * Add new message on a ticket (public area)
     *
     * @param User $user
     * @param string $action
     */
    private function newMessagePublic($user, &$action)
    {

        global $mysoc, $conf, $langs;

        $error = 0;
        $ret = $this->dao->fetch('', GETPOST('track_id'));
        $this->dao->socid = $this->dao->fk_soc;
        $this->dao->fetch_thirdparty();
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
            $this->dao->message = GETPOST("message");
            $id = $this->dao->createTicketMessage($user);
            if ($id <= 0) {
                $error++;
                $errors = ($this->dao->error ? array($this->dao->error) : $this->dao->errors);
                array_push($this->errors, $this->dao->error ? array($this->dao->error) : $this->dao->errors);
                $action = 'add_message';
            }

            if (!$error && $id > 0) {
                setEventMessage($langs->trans('TicketMessageSuccessfullyAdded'));

                // Retrieve internal contact datas
                $internal_contacts = $this->dao->getInfosTicketInternalContact();
                $sendto = array();
                if (is_array($internal_contacts) && count($internal_contacts) > 0) {
                    $subject = '[' . $mysoc->name . '- ticket #' . $this->dao->track_id . '] ' . $langs->trans('TicketNewMessage');

                    $message = $langs->trans('TicketMessageMailIntroAutoNewPublicMessage', $this->dao->subject);
                    $message .= "\n";
                    $message .= GETPOST('message');
                    $message .= "\n";

                    //  Coordonnées client
                    if ($this->dao->thirdparty->id > 0) {
                        $message .= "\n\n";
                        $message .= "==============================================\n";
                        $message .= $langs->trans('Thirparty') . " : " . $this->dao->thirdparty->name;
                        $message .= !empty($this->dao->thirdparty->town) ? $langs->trans('Town') . " : " . $this->dao->thirdparty->town : '';
                        $message .= "\n";
                        $message .= !empty($this->dao->thirdparty->phone) ? $langs->trans('Phone') . " : " . $this->dao->thirdparty->phone : '';
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
                    $url_internal_ticket = dol_buildpath('/ticketsup/card.php', 2) . '?track_id=' . $this->dao->track_id;
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
                $external_contacts = $this->dao->getInfosTicketExternalContact();
                $sendto = array();
                if (is_array($external_contacts) && count($external_contacts) > 0) {
                    $subject = '[' . $mysoc->name . '- ticket #' . $this->dao->track_id . '] ' . $langs->trans('TicketNewMessage');

                    $message = $langs->trans('TicketMessageMailIntroAutoNewPublicMessage', $this->dao->subject);
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

                    $url_public_ticket = ($conf->global->TICKETS_URL_PUBLIC_INTERFACE ? $conf->global->TICKETS_URL_PUBLIC_INTERFACE . '/view.php' : dol_buildpath('/ticketsup/public/view.php', 2)) . '?track_id=' . $this->dao->track_id;
                    $message .= "\n\n" . $langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer') . ' : ' . $url_public_ticket . "\n";

                    // Add signature
                    $message .= '\n\n' . $message_signature;

                    if (!empty($this->dao->origin_email) && !in_array($this->dao->origin_email, $sendto)) {
                        $sendto[] = $this->dao->origin_email;
                    }
                    if ($this->dao->fk_soc > 0 && !in_array($this->dao->origin_email, $sendto)) {
                        $sendto[] = $this->dao->thirdparty->email;
                    }
                    $this->sendTicketMessageByEmail($subject, $message, '', $sendto);
                }

                $this->copyFilesForTicket();

                $url = 'view.php?action=view_ticket&track_id=' . $this->dao->track_id;
                header("Location: " . $url);
                exit;
            } else {
                setEventMessage($this->dao->error, 'errors');
            }
        } else {
            setEventMessage($this->errors, 'errors');
        }
    }

    /**
     * Fetch object
     * 
     * @param string $id
     * @param string $track_id
     * @param string $ref
     * @return void
     */
    public function fetch($id = 0, $track_id = 0, $ref = '')
    {
        $this->getInstanceDao();
        return $this->dao->fetch($id, $track_id, $ref);
    }

    /**
     * print statut
     * @param int $mode Display mode
     * @return void
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
        $this->dao->fetch($id, $track_id);

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
     * @param boolean $show_user Show user who make action
     */
    public function viewTimelineTicketLogs($show_user = true)
    {
    	global $conf, $langs, $bc;
    
    	// Load logs in cache
    	$ret = $this->dao->loadCacheLogsTicket();
    
    	if (is_array($this->dao->cache_logs_ticket) && count($this->dao->cache_logs_ticket) > 0) {
    		print '<section id="cd-timeline">';

    		foreach ($this->dao->cache_logs_ticket as $id => $arraylogs) {
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
    						print '<br /><small>'.$userstat->getNomUrl(1).'</small>';
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
     * @param User $user	$user wich display
     * @param string $action    Action mode
     */
    public function viewTicketOriginalMessage($user, $action = '')
    {
    	global $langs;
    	if (!empty($user->rights->ticketsup->manage) && $action == 'edit_message_init') {
    		// MESSAGE
    	
    		print '<form action="' . $_SERVER['PHP_SELF'] . '" method="post">';
    		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    		print '<input type="hidden" name="track_id" value="' . $this->dao->track_id . '">';
    		print '<input type="hidden" name="action" value="set_message">';
    	}
 
    	// Initial message
    	print '<table class="border" width="100%">';
    	print '<tr class="liste_titre"><td class="nowrap" colspan="2">';
    	print '<strong>' . $langs->trans("InitialMessage") . '</strong> ';
    	if ($user->rights->ticketsup->manage) {
    		print '<a  href="' . $_SERVER['PHP_SELF'] . '?action=edit_message_init&amp;track_id=' . $this->dao->track_id . '">' . img_edit($langs->trans('Modify')) . ' ' . $langs->trans('Modify') . '</a>';
    	}
    	print '</td></tr>';
    	
    	print '<tr>';
    	
    	print '<td>';
    	if (!empty($user->rights->ticketsup->manage) && $action == 'edit_message_init') {
    		// MESSAGE
    		$msg = GETPOST('message_initial', 'alpha') ? GETPOST('message_initial', 'alpha') : $this->dao->message;
    		include_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
    		$uselocalbrowser = true;
    		$doleditor = new DolEditor('message_initial', $msg, '100%', 250, 'dolibarr_details', 'In', true, $uselocalbrowser);
    		$doleditor->Create();
    	} else {
    		// Deal with format differences (text / HTML)
    		if (dol_textishtml($this->dao->message)) {
    			print $this->dao->message;
    		} else {
    			print dol_nl2br($this->dao->message);
    		}
    		
    		//print '<div>' . $this->dao->message . '</div>';
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

        // Load logs in cache
        $ret = $this->dao->loadCacheMsgsTicket();
        $action = GETPOST('action');

        $this->viewTicketOriginalMessage($user, $action);
        
        if (is_array($this->dao->cache_msgs_ticket) && count($this->dao->cache_msgs_ticket) > 0) {
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

            foreach ($this->dao->cache_msgs_ticket as $id => $arraymsgs) {
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
     * @param boolean $show_private Show private messages
     * @param boolean $show_user    Show user who make action
     */
    public function viewTicketTimelineMessages($show_private, $show_user = true)
    {
    	global $conf, $langs, $user, $bc;
    
    	// Load logs in cache
    	$ret = $this->dao->loadCacheMsgsTicket();
    	$action = GETPOST('action');
    
    	if (is_array($this->dao->cache_msgs_ticket) && count($this->dao->cache_msgs_ticket) > 0) {
    		print '<section id="cd-timeline">';

    		foreach ($this->dao->cache_msgs_ticket as $id => $arraymsgs) {
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
                                print '<br />';
                                print $userstat->getNomUrl(1);
                            }
                        } else {
                            print '<br />';
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
    public function load_previous_next_ref($filter, $fieldid)
    {
        $this->getInstanceDao();
        return $this->dao->load_previous_next_ref($filter, $fieldid);
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
            $array_receiver = $this->dao->getInfosTicketInternalContact();
            $array_receiver = array_merge($array_receiver, $this->dao->getInfosTicketExternalContact());
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
                    setEventMessage($mailfile->error, 'errors');
                } else {
                    $result = $mailfile->sendfile();
                    if ($result) {
                        setEventMessage($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($receiver, 2)), 'mesgs');
                    } else {
                        $langs->load("other");
                        if ($mailfile->error) {
                            setEventMessage($langs->trans('ErrorFailedToSendMail', $from, $receiver), 'errors');
                            dol_syslog($langs->trans('ErrorFailedToSendMail', $from, $receiver) . ' : ' . $mailfile->error);
                        } else {
                            setEventMessage('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', 'errors');
                        }
                    }
                }
                if (!empty($conf->global->TICKETS_DISABLE_MAIL_AUTOCOPY_TO)) {
                    $conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
                }
            }
        } else {
            $langs->load("other");
            setEventMessage($langs->trans('ErrorMailRecipientIsEmptyForSendTicketMessage') . '!', 'warnings');
        }
    }

    /**
     * Copy files into ticket directory
     *
     * Used for files linked into messages
     */
    public function copyFilesForTicket()
    {

        global $conf;

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
        $destdir = $conf->ticketsup->dir_output . '/' . $this->dao->track_id;

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
     * @global type $langs
     */
    public function viewStatusActions()
    {
        global $langs;

        print '<div class="tagtable noborder ">';
        print '<div class="tagtr liste_titre">';
        print '<div class="tagtd">';
        print '<strong>' . $langs->trans('TicketChangeStatus') . '</strong>';
        print '</div>';
        // Exclude status which requires specific method
        $exclude_status = array(4, 9, 8);
        // Exclude actual status
        $exclude_status = array_merge($exclude_status, array(intval($this->dao->fk_statut)));

        // If status is new, don't show link which allow mark ticket as read
        // Specific method exists to mark a ticket as read
        if ($this->dao->fk_statut === '0') {
            $exclude_status = array_merge($exclude_status, array(1));
        }

        // Sort results to be similar to status object list
        sort($exclude_status);

        //print '<br /><div>';
        foreach ($this->dao->statuts_short as $status => $statut_label) {
            if (!in_array($status, $exclude_status)) {
                print '<div class="tagtd">';
                print '<a class="button" href="' . $_SERVER['PHP_SELF'] . '?track_id=' . $this->dao->track_id . '&action=set_status&new_status=' . $status . '">';
                print img_picto($langs->trans($this->dao->statuts_short[$status]), 'statut' . $status . '.png@ticketsup') . ' ' . $langs->trans($this->dao->statuts_short[$status]);
                print '</a>';
                print '</div>';
            }
        }
        print '</div></div><br />';
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
