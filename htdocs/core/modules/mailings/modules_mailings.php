<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/modules/mailings/modules_mailings.php
 *		\ingroup    mailing
 *		\brief      File with parent class of emailing target selectors modules
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 *	    \class      MailingTargets
 *		\brief      Parent class of emailing target selectors modules
 */
class MailingTargets    // This can't be abstract as it is used for some method
{
    var $db;
    var $error;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
        $this->db = $db;
	}

    /**
     * Return description of email selector
     *
     * @return     string      Retourne la traduction de la cle MailingModuleDescXXX ou XXX nom du module, ou $this->desc si non trouve
     */
    function getDesc()
    {
        global $langs;
        $langs->load("mails");
        $transstring="MailingModuleDesc".$this->name;
        if ($langs->trans($transstring) != $transstring) return $langs->trans($transstring);
        else return $this->desc;
    }

    /**
	 *	Return number of records for email selector
     *
     *  @return     string      Example
     */
    function getNbOfRecords()
    {
        return 0;
    }

    /**
     * Retourne nombre de destinataires
     *
     * @param      string	$sql        Requete sql de comptage
     * @return     int       			Nb de destinataires si ok, < 0 si erreur
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
     * Affiche formulaire de filtre qui apparait dans page de selection
     * des destinataires de mailings
     *
     * @return     string      Retourne zone select
     */
    function formFilter()
    {
        return '';
    }

    /**
     * Met a jour nombre de destinataires
     *
     * @param	int		$mailing_id          Id of emailing
     * @return  int			                 < 0 si erreur, nb destinataires si ok
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
     * Ajoute destinataires dans table des cibles
     *
     * @param	int		$mailing_id    Id of emailing
     * @param   array	$cibles        Array with targets
     * @return  int      			   < 0 si erreur, nb ajout si ok
     */
    function add_to_target($mailing_id, $cibles)
    {
    	global $conf;

    	$this->db->begin();

        // Insert emailing targest from array into database
        $j = 0;
        $num = count($cibles);
        for ($i = 0 ; $i < $num ; $i++)
        {
        	if (! empty($cibles[$i]['email'])) // avoid empty email address
        	{
        		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_cibles";
        		$sql .= " (fk_mailing,";
        		$sql .= " fk_contact,";
        		$sql .= " nom, prenom, email, other, source_url, source_id,";
        		if (! empty($conf->global->MAILING_EMAIL_UNSUBSCRIBE)) {
        			$sql .= " tag,";
        		}
        		$sql.= " source_type)";
        		$sql .= " VALUES (".$mailing_id.",";
        		$sql .= (empty($cibles[$i]['fk_contact']) ? '0' : "'".$cibles[$i]['fk_contact']."'") .",";
        		$sql .= "'".$this->db->escape($cibles[$i]['name'])."',";
        		$sql .= "'".$this->db->escape($cibles[$i]['firstname'])."',";
        		$sql .= "'".$this->db->escape($cibles[$i]['email'])."',";
        		$sql .= "'".$this->db->escape($cibles[$i]['other'])."',";
        		$sql .= "'".$this->db->escape($cibles[$i]['source_url'])."',";
        		$sql .= "".(empty($cibles[$i]['source_id']) ? 'null' : "'".$this->db->escape($cibles[$i]['source_id'])."'").",";
        		if (! empty($conf->global->MAILING_EMAIL_UNSUBSCRIBE)) {
        			$sql .= "'".$this->db->escape(md5($cibles[$i]['email'].';'.$cibles[$i]['name'].';'.$mailing_id.';'.$conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY))."',";
        		}
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
        }

        dol_syslog(get_class($this)."::add_to_target: sql ".$sql,LOG_DEBUG);
        dol_syslog(get_class($this)."::add_to_target: mailing ".$j." targets added");

        //Update the status to show thirdparty mail that don't want to be contacted anymore'
        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " SET statut=3";
        $sql .= " WHERE fk_mailing=".$mailing_id." AND email in (SELECT email FROM ".MAIN_DB_PREFIX."societe where fk_stcomm=-1)";
        $sql .= " AND source_type='thirdparty'";
        $result=$this->db->query($sql);

        dol_syslog(get_class($this)."::add_to_target: mailing update status to display thirdparty mail that do not want to be contacted sql:".$sql);

        //Update the status to show contact mail that don't want to be contacted anymore'
        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " SET statut=3";
        $sql .= " WHERE fk_mailing=".$mailing_id." AND email in (SELECT sc.email FROM ".MAIN_DB_PREFIX."socpeople AS sc ";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.fk_stcomm=-1 AND s.rowid=sc.fk_soc)";
        $sql .= " AND source_type='contact'";
        $result=$this->db->query($sql);

        dol_syslog(get_class($this)."::add_to_target: mailing update status to display contact mail that do not want to be contacted sql:".$sql);


        $this->update_nb($mailing_id);

        $this->db->commit();
        return $j;
    }

    /**
     *  Supprime tous les destinataires de la table des cibles
     *
     *	@param	int		$mailing_id        Id of emailing
     *	@return	void
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
