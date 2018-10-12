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
 * \file    datapolicies/class/datapolicies.class.php
 * \ingroup datapolicies
 * \brief   Class to manage feature of Data Policies module.
 */
include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
include_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
include_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';


/**
 * Class DataPolicies
 */
Class DataPolicies extends Contact
{
	/**
	 * getAllContactNotInformed
	 *
	 * @return number
	 */
    function getAllContactNotInformed()
    {
        global $langs, $conf, $db, $user;

        $langs->load("companies");

        $sql = "SELECT c.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as c";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON c.fk_soc = s.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople_extrafields as spe ON spe.fk_object = c.rowid";
        $sql .= " WHERE (c.statut=1 AND c.no_email=0 AND (spe.datapolicies_consentement=0 OR spe.datapolicies_consentement IS NULL) AND (spe.datapolicies_opposition_traitement=0 OR spe.datapolicies_opposition_traitement IS NULL) AND (spe.datapolicies_opposition_prospection=0 OR spe.datapolicies_opposition_prospection IS NULL))";
        $sql .= " AND spe.datapolicies_send IS NULL";
        $sql .= " AND c.entity=" . $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $contact = new Contact($db);
                $contact->fetch($obj->rowid);

                DataPolicies::sendMailDataPoliciesContact($contact);
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
    function getAllCompaniesNotInformed()
    {
        global $langs, $conf, $db, $user;

        $langs->load("companies");

        $sql = "SELECT s.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_extrafields as se ON se.fk_object = s.rowid";
        $sql .= " WHERE s.statut=0 AND (se.datapolicies_consentement=0 OR se.datapolicies_consentement IS NULL) AND (se.datapolicies_opposition_traitement=0 OR se.datapolicies_opposition_traitement IS NULL) AND (se.datapolicies_opposition_prospection=0 OR se.datapolicies_opposition_prospection IS NULL)";
        $sql .= " AND se.datapolicies_send IS NULL";
        $sql .= " AND s.entity=" . $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $societe = new Societe($db);
                $societe->fetch($obj->rowid);

                DataPolicies::sendMailDataPoliciesCompany($societe);
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
    function getAllAdherentsNotInformed()
    {
        global $langs, $conf, $db, $user;

        $langs->load("adherent");

        $sql = "SELECT a.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "adherent as a";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "adherent_extrafields as ae ON ae.fk_object = a.rowid";
        $sql .= " WHERE a.statut=0 AND (ae.datapolicies_consentement=0 OR ae.datapolicies_consentement IS NULL) AND (ae.datapolicies_opposition_traitement=0 OR ae.datapolicies_opposition_traitement IS NULL) AND (ae.datapolicies_opposition_prospection=0 OR ae.datapolicies_opposition_prospection IS NULL)";
        $sql .= " AND ae.datapolicies_send IS NULL";
        $sql .= " AND a.entity=" . $conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);
                $adherent = new Adherent($db);
                $adherent->fetch($obj->rowid);

                DataPolicies::sendMailDataPoliciesAdherent($adherent);
                $i++;
            }
        } else {
            $this->error = $this->db->error();
            return -1;
        }
    }

    /**
     * sendMailDataPoliciesContact
     *
     * @param 	mixed		$contact		Contact
     * @return	void
     */
    function sendMailDataPoliciesContact($contact)
    {
     	global $langs, $conf, $db, $user;

     	$error = 0;

     	$from = $user->getFullName($langs) . ' <' . $user->email . '>';
     	$replyto = $from;
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
     	'__LINKACCEPT__' => '<a href="'.dol_buildpath('/datapolicies/public/index.php?action=1&c='.$contact->id.'&l='.$l.'&key='.$code,3).'" target="_blank">'.$linka.'</a>',
     	'__LINKREFUSED__' => '<a href="'.dol_buildpath('/datapolicies/public/index.php?action=2&c='.$contact->id.'&l='.$l.'&key='.$code,3).'" target="_blank">'.$linkr.'</a>',
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
     			$contact->array_options['options_datapolicies_send'] = date('Y-m-d', time());
     			$contact->update($contact->id);

     		} else {
     			dol_print_error($db);
     		}
     	}
     	setEventMessage($resultmasssend);
    }

    /**
     * sendMailDataPoliciesCompany
     *
     * @param Societe	$societe	Object societe
     * @return	void
     */
    function sendMailDataPoliciesCompany($societe)
    {
     	global $langs, $conf, $db, $user;

     	$error = 0;

     	$from = $user->getFullName($langs) . ' <' . $user->email . '>';
     	$replyto = $from;
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
            '__LINKACCEPT__' => '<a href="'.dol_buildpath('/datapolicies/public/index.php?action=1&s='.$societe->id.'&l='.$l.'&key='.$code,3).'" target="_blank">'.$linka.'</a>',
            '__LINKREFUSED__' => '<a href="'.dol_buildpath('/datapolicies/public/index.php?action=2&s='.$societe->id.'&l='.$l.'&key='.$code,3).'" target="_blank">'.$linkr.'</a>',
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
     			$societe->array_options['options_datapolicies_send'] = date('Y-m-d', time());
     			$societe->update($societe->id);
     		} else {
     			dol_print_error($db);
     		}
     	}
     	setEventMessage($resultmasssend);
    }

    /**
     * sendMailDataPoliciesAdherent
     *
     * @param Adherent	$adherent		Member
     * @return void
     */
    function sendMailDataPoliciesAdherent($adherent)
    {
    	global $langs, $conf, $db, $user;

    	$error = 0;

    	$from = $user->getFullName($langs) . ' <' . $user->email . '>';
    	$replyto = $from;
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
            '__LINKACCEPT__' => '<a href="'.dol_buildpath('/datapolicies/public/index.php?action=1&a='.$adherent->id.'&l='.$l.'&key='.$code,3).'" target="_blank">'.$linka.'</a>',
            '__LINKREFUSED__' => '<a href="'.dol_buildpath('/datapolicies/public/index.php?action=2&a='.$adherent->id.'&l='.$l.'&key='.$code,3).'" target="_blank">'.$linkr.'</a>',
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
    			$adherent->array_options['options_datapolicies_send'] = date('Y-m-d', time());
    			$adherent->update($user);

    		} else {
    			dol_print_error($db);
    		}
    	}
    	setEventMessage($resultmasssend);
    }
}
