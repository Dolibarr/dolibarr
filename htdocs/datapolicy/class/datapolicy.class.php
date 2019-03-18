<?php
/* Copyright (C) 2018 Nicolas ZABOURI <info@inovea-conseil.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    datapolicy/class/datapolicy.class.php
 * \ingroup datapolicy
 * \brief   Class to manage feature of Data Policy module.
 */
include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
include_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';


/**
 * Class DataPolicy
 */
class DataPolicy
{
    /**
     * getAllContactNotInformed
     *
     * @return number
     */
    public function getAllContactNotInformed()
    {
        global $langs, $conf, $db, $user;

        $langs->load("companies");

        $sql = "SELECT c.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople_extrafields as spe ON spe.fk_object = c.rowid";
        $sql .= " WHERE (c.statut=1 AND c.no_email=0 AND (spe.datapolicy_consentement=0 OR spe.datapolicy_consentement IS NULL) AND (spe.datapolicy_opposition_traitement=0 OR spe.datapolicy_opposition_traitement IS NULL) AND (spe.datapolicy_opposition_prospection=0 OR spe.datapolicy_opposition_prospection IS NULL))";
        $sql .= " AND spe.datapolicy_send IS NULL";
        $sql .= " AND c.entity=" . $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $contact = new Contact($db);
                $contact->fetch($obj->rowid);

                DataPolicy::sendMailDataPolicyContact($contact);
                $i++;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     * getAllCompaniesNotInformed
     *
     * @return number
     */
    public function getAllCompaniesNotInformed()
    {
        global $langs, $conf, $db, $user;

        $langs->load("companies");

        $sql = "SELECT s.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields as se ON se.fk_object = s.rowid";
        $sql .= " WHERE s.statut=0 AND (se.datapolicy_consentement=0 OR se.datapolicy_consentement IS NULL) AND (se.datapolicy_opposition_traitement=0 OR se.datapolicy_opposition_traitement IS NULL) AND (se.datapolicy_opposition_prospection=0 OR se.datapolicy_opposition_prospection IS NULL)";
        $sql .= " AND se.datapolicy_send IS NULL";
        $sql .= " AND s.entity=" . $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $societe = new Societe($db);
                $societe->fetch($obj->rowid);

                DataPolicy::sendMailDataPolicyCompany($societe);
                $i++;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     * getAllAdherentsNotInformed
     *
     * @return number
     */
    public function getAllAdherentsNotInformed()
    {
        global $langs, $conf, $db, $user;

        $langs->load("adherent");

        $sql = "SELECT a.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as a";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "adherent_extrafields as ae ON ae.fk_object = a.rowid";
        $sql .= " WHERE a.statut=0 AND (ae.datapolicy_consentement=0 OR ae.datapolicy_consentement IS NULL) AND (ae.datapolicy_opposition_traitement=0 OR ae.datapolicy_opposition_traitement IS NULL) AND (ae.datapolicy_opposition_prospection=0 OR ae.datapolicy_opposition_prospection IS NULL)";
        $sql .= " AND ae.datapolicy_send IS NULL";
        $sql .= " AND a.entity=" . $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $adherent = new Adherent($db);
                $adherent->fetch($obj->rowid);

                DataPolicy::sendMailDataPolicyAdherent($adherent);
                $i++;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     * sendMailDataPolicyContact
     *
     * @param 	mixed		$contact		Contact
     * @return	void
     */
    public function sendMailDataPolicyContact($contact)
    {
        global $langs, $conf, $db, $user;

        $error = 0;

        $from = $user->getFullName($langs) . ' <' . $user->email . '>';

        $sendto = $contact->email;
        $code= md5($contact->email);
        if (!empty($contact->default_lang)) {
            $l = $contact->default_lang;
        } else {
            $l = $langs->defaultlang;
        }
        $s = "DATAPOLICIESSUBJECT_" . $l;
        $ma = "DATAPOLICIESCONTENT_" . $l;
        $la = 'TXTLINKDATAPOLICIESACCEPT_' . $l;
        $lr = 'TXTLINKDATAPOLICIESREFUSE_' . $l;

        $subject = $conf->global->$s;
        $message = $conf->global->$ma;
        $linka = $conf->global->$la;
        $linkr = $conf->global->$lr;
        $sendtocc = $sendtobcc = '';
        $filepath = $mimetype = $filename = array();
        $deliveryreceipt = 0;

        $substitutionarray = array(
            '__LINKACCEPT__' => '<a href="'.dol_buildpath('/datapolicy/public/index.php?action=1&c='.$contact->id.'&l='.$l.'&key='.$code, 3).'" target="_blank">'.$linka.'</a>',
            '__LINKREFUSED__' => '<a href="'.dol_buildpath('/datapolicy/public/index.php?action=2&c='.$contact->id.'&l='.$l.'&key='.$code, 3).'" target="_blank">'.$linkr.'</a>',
            '__FIRSTNAME__' => $contact->firstname,
            '__NAME__' => $contact->lastname,
            '__CIVILITY__' => $contact->civility,
        );
        $subject = make_substitutions($subject, $substitutionarray);
        $message = make_substitutions($message, $substitutionarray);

        $actiontypecode = 'AC_EMAIL';
        $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto;
        if ($message) {
            if ($sendtocc)
                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
                $actionmsg = dol_concatdesc($actionmsg, $message);
        }


        // Send mail
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1);

        if ($mailfile->error) {
            $resultmasssend .= '<div class="error">' . $mailfile->error . '</div>';
        } else {
            $result4 = $mailfile->sendfile();
            if (!$error) {

                $resultmasssend .= $langs->trans("MailSent") . ': ' . $sendto . "<br>";
                $contact->array_options['options_datapolicy_send'] = date('Y-m-d', time());
                $contact->update($contact->id);
            } else {
                dol_print_error($db);
            }
        }
        setEventMessage($resultmasssend);
    }

    /**
     * sendMailDataPolicyCompany
     *
     * @param Societe	$societe	Object societe
     * @return	void
     */
    public function sendMailDataPolicyCompany($societe)
    {
        global $langs, $conf, $db, $user;

        $error = 0;

        $from = $user->getFullName($langs) . ' <' . $user->email . '>';

        $sendto = $societe->email;

        $code= md5($societe->email);
        if (!empty($societe->default_lang)) {
            $l = $societe->default_lang;
        } else {
            $l = $langs->defaultlang;
        }
        $s = "DATAPOLICIESSUBJECT_" . $l;
        $ma = "DATAPOLICIESCONTENT_" . $l;
        $la = 'TXTLINKDATAPOLICIESACCEPT_' . $l;
        $lr = 'TXTLINKDATAPOLICIESREFUSE_' . $l;

        $subject = $conf->global->$s;
        $message = $conf->global->$ma;
        $linka = $conf->global->$la;
        $linkr = $conf->global->$lr;
        $sendtocc = $sendtobcc = '';
        $filepath = $mimetype = $filename = array();
        $deliveryreceipt = 0;

        $substitutionarray = array(
            '__LINKACCEPT__' => '<a href="'.dol_buildpath('/datapolicy/public/index.php?action=1&s='.$societe->id.'&l='.$l.'&key='.$code, 3).'" target="_blank">'.$linka.'</a>',
            '__LINKREFUSED__' => '<a href="'.dol_buildpath('/datapolicy/public/index.php?action=2&s='.$societe->id.'&l='.$l.'&key='.$code, 3).'" target="_blank">'.$linkr.'</a>',
        );
        $subject = make_substitutions($subject, $substitutionarray);
        $message = make_substitutions($message, $substitutionarray);

        $actiontypecode = 'AC_EMAIL';
        $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto;
        if ($message)
        {
            if ($sendtocc)
            {
                 $actionmsg .= dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
            }
            $actionmsg .= dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
            $actionmsg .= dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
            $actionmsg .= dol_concatdesc($actionmsg, $message);
        }

        // Send mail
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1);
        if ($mailfile->error) {
            $resultmasssend .= '<div class="error">' . $mailfile->error . '</div>';
        } else {
            $result4 = $mailfile->sendfile();

            if (!$error) {
                $resultmasssend .= $langs->trans("MailSent") . ': ' . $sendto . "<br>";
                $societe->array_options['options_datapolicy_send'] = date('Y-m-d', time());
                $societe->update($societe->id);
            } else {
                dol_print_error($db);
            }
        }
        setEventMessage($resultmasssend);
    }

    /**
     * sendMailDataPolicyAdherent
     *
     * @param Adherent	$adherent		Member
     * @return void
     */
    public function sendMailDataPolicyAdherent($adherent)
    {
        global $langs, $conf, $db, $user;

        $error = 0;

        $from = $user->getFullName($langs) . ' <' . $user->email . '>';

        $sendto = $adherent->email;

        $code= md5($adherent->email);
        if (!empty($adherent->default_lang)) {
            $l = $adherent->default_lang;
        } else {
            $l = $langs->defaultlang;
        }
        $la = 'TXTLINKDATAPOLICIESACCEPT_' . $l;
        $lr = 'TXTLINKDATAPOLICIESREFUSE_' . $l;

        $subject = $conf->global->$s;
        $message = $conf->global->$ma;
        $linka = $conf->global->$la;
        $linkr = $conf->global->$lr;
        $sendtocc = $sendtobcc = '';
        $filepath = $mimetype = $filename = array();
        $deliveryreceipt = 0;

        $substitutionarray = array(
            '__LINKACCEPT__' => '<a href="'.dol_buildpath('/datapolicy/public/index.php?action=1&a='.$adherent->id.'&l='.$l.'&key='.$code, 3).'" target="_blank">'.$linka.'</a>',
            '__LINKREFUSED__' => '<a href="'.dol_buildpath('/datapolicy/public/index.php?action=2&a='.$adherent->id.'&l='.$l.'&key='.$code, 3).'" target="_blank">'.$linkr.'</a>',
        );
        $subject = make_substitutions($subject, $substitutionarray);
        $message = make_substitutions($message, $substitutionarray);

        $actiontypecode = 'AC_EMAIL';
        $actionmsg = $langs->transnoentities('MailSentBy') . ' ' . $from . ' ' . $langs->transnoentities('To') . ' ' . $sendto;
        if ($message) {
            if ($sendtocc) {
                $actionmsg .= dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
            }
            $actionmsg .= dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
            $actionmsg .= dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
            $actionmsg .= dol_concatdesc($actionmsg, $message);
        }


        // Send mail
        require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
        $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1);
        if ($mailfile->error) {
            $resultmasssend .= '<div class="error">' . $mailfile->error . '</div>';
        } else {
            $result4 = $mailfile->sendfile();

            if (!$error) {
                $resultmasssend .= $langs->trans("MailSent") . ': ' . $sendto . "<br>";
                $adherent->array_options['options_datapolicy_send'] = date('Y-m-d', time());
                $adherent->update($user);
            } else {
                dol_print_error($db);
            }
        }
        setEventMessage($resultmasssend);
    }
}
