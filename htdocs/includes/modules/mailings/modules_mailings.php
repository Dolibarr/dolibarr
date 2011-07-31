<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/includes/modules/mailings/modules_mailings.php
 *		\ingroup    mailing
 *		\brief      File with parent class of emailing target selectors modules
 *		\version    $Id: modules_mailings.php,v 1.21 2011/07/31 23:28:15 eldy Exp $
 */
require_once(DOL_DOCUMENT_ROOT.'/lib/functions.lib.php');


/**
 *	    \class      MailingTargets
 *		\brief      Parent class of emailing target selectors modules
 */
class MailingTargets
{
    var $db='';
    var $error='';

    function MailingTargets($DB)
    {
        $this->db=$DB;
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Retourne la traduction de la cle MailingModuleDescXXX ou XXX nom du module, ou $this->desc si non trouve
     */
    function getDesc()
    {
        global $langs;
        $langs->load("mails");
        $transstring="MailingModuleDesc".$this->name;
        if ($langs->trans($transstring) != $transstring) return $langs->trans($transstring);
        else return $this->desc;
    }

    /**     \brief      Renvoi un exemple de numerotation
     *      \return     string      Example
     */
    function getNbOfRecords()
    {
        return 0;
    }

    /**
     *    \brief      Retourne nombre de destinataires
     *    \param      sql         Requete sql de comptage
     *    \return     int         Nb de destinataires si ok, < 0 si erreur
     */
    function getNbOfRecipients($sql)
    {
        $result=$this->db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            return $obj->nb;
        }
        else
        {
        	$this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *      \brief      Affiche formulaire de filtre qui apparait dans page de selection
     *                  des destinataires de mailings
     *      \return     string      Retourne zone select
     */
    function formFilter()
    {
        return '';
    }

    /**
     *      \brief      Met a jour nombre de destinataires
     *      \param      mailing_id          Id du mailing concern�
     *      \return     int                 < 0 si erreur, nb destinataires si ok
     */
    function update_nb($mailing_id)
    {
        // Mise a jour nombre de destinataire dans table des mailings
        $sql = "SELECT COUNT(*) nb FROM ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " WHERE fk_mailing = ".$mailing_id;
        $result=$this->db->query($sql);
        if ($result)
        {
            $obj=$this->db->fetch_object($result);
            $nb=$obj->nb;

            $sql = "UPDATE ".MAIN_DB_PREFIX."mailing";
            $sql .= " SET nbemail = ".$nb." WHERE rowid = ".$mailing_id;
            if (!$this->db->query($sql))
            {
                dol_syslog($this->db->error());
                $this->error=$this->db->error();
                return -1;
            }
        }
        else {
            return -1;
        }
        return $nb;
    }

    /**
     *    \brief      Ajoute destinataires dans table des cibles
     *    \param      mailing_id    Id of emailing
     *    \param      cibles        Array with targets
     *    \return     int           < 0 si erreur, nb ajout si ok
     */
    function add_to_target($mailing_id, $cibles)
    {
    	$this->db->begin();

        // Insert emailing targest from array into database
        $j = 0;
        $num = sizeof($cibles);
        for ($i = 0 ; $i < $num ; $i++)
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_cibles";
            $sql .= " (fk_mailing,";
            $sql .= " fk_contact,";
            $sql .= " nom, prenom, email, other, source_url, source_id, source_type)";
            $sql .= " VALUES (".$mailing_id.",";
            $sql .= (empty($cibles[$i]['fk_contact']) ? '0' : "'".$cibles[$i]['fk_contact']."'") .",";
            $sql .= "'".$this->db->escape($cibles[$i]['name'])."',";
            $sql .= "'".$this->db->escape($cibles[$i]['firstname'])."',";
            $sql .= "'".$this->db->escape($cibles[$i]['email'])."',";
            $sql .= "'".$this->db->escape($cibles[$i]['other'])."',";
            $sql .= "'".$this->db->escape($cibles[$i]['source_url'])."',";
            $sql .= "'".$this->db->escape($cibles[$i]['source_id'])."',";
            $sql .= "'".$this->db->escape($cibles[$i]['source_type'])."')";
            $result=$this->db->query($sql);
            if ($result)
            {
                $j++;
            }
            else
            {
                if ($this->db->errno() != 'DB_ERROR_RECORD_ALREADY_EXISTS')
                {
                    // Si erreur autre que doublon
                    dol_syslog($this->db->error());
                    $this->error=$this->db->error();
                    $this->db->rollback();
                    return -1;
                }
            }
        }

        dol_syslog("MailingTargets::add_to_target: mailing ".$j." targets added");

        $this->update_nb($mailing_id);

        $this->db->commit();
        return $j;
    }

    /**
     *    \brief      Supprime tous les destinataires de la table des cibles
     *    \param      mailing_id        Id of emailing
     */
    function clear_target($mailing_id)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " WHERE fk_mailing = ".$mailing_id;

        if (! $this->db->query($sql))
        {
            dol_syslog($this->db->error());
        }

        $this->update_nb($mailing_id);
    }

}

?>
