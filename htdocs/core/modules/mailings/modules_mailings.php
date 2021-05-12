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
 *		Parent class of emailing target selectors modules
 */
<<<<<<< HEAD
class MailingTargets    // This can't be abstract as it is used for some method
{
    var $db;
    var $error;
    var $tooltip='';
    
=======
class MailingTargets // This can't be abstract as it is used for some method
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
	 * @var string Error code (or message)
	 */
	public $error='';

    public $tooltip='';

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
<<<<<<< HEAD
	function __construct($db)
=======
    public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
        $this->db = $db;
	}

    /**
     * Return description of email selector
     *
     * @return     string      Return translation of module label. Try translation of $this->name then translation of 'MailingModuleDesc'.$this->name, or $this->desc if not found
     */
<<<<<<< HEAD
    function getDesc()
    {
        global $langs, $form;
        
=======
    public function getDesc()
    {
        global $langs, $form;

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $langs->load("mails");
        $transstring="MailingModuleDesc".$this->name;
        $s='';

        if ($langs->trans($this->name) != $this->name) $s=$langs->trans($this->name);
        elseif ($langs->trans($transstring) != $transstring) $s=$langs->trans($transstring);
        else $s=$this->desc;

        if ($this->tooltip && is_object($form)) $s .= ' '.$form->textwithpicto('', $langs->trans($this->tooltip), 1, 1);
        return $s;
    }

    /**
	 *	Return number of records for email selector
     *
     *  @return     integer      Example
     */
<<<<<<< HEAD
    function getNbOfRecords()
=======
    public function getNbOfRecords()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        return 0;
    }

    /**
     * Retourne nombre de destinataires
     *
     * @param      string	$sql        Sql request to count
     * @return     int       			Nb of recipient, or <0 if error
     */
<<<<<<< HEAD
    function getNbOfRecipients($sql)
=======
    public function getNbOfRecipients($sql)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $result=$this->db->query($sql);
        if ($result)
        {
            $obj = $this->db->fetch_object($result);
            return $obj->nb;
        }
        else
        {
        	$this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     * Affiche formulaire de filtre qui apparait dans page de selection
     * des destinataires de mailings
     *
     * @return     string      Retourne zone select
     */
<<<<<<< HEAD
    function formFilter()
=======
    public function formFilter()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        return '';
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Met a jour nombre de destinataires
     *
     * @param	int		$mailing_id          Id of emailing
     * @return  int			                 < 0 si erreur, nb destinataires si ok
     */
<<<<<<< HEAD
    function update_nb($mailing_id)
    {
=======
    public function update_nb($mailing_id)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Ajoute destinataires dans table des cibles
     *
     * @param	int		$mailing_id    Id of emailing
     * @param   array	$cibles        Array with targets
     * @return  int      			   < 0 si erreur, nb ajout si ok
     */
<<<<<<< HEAD
    function add_to_target($mailing_id, $cibles)
    {
=======
    public function add_to_target($mailing_id, $cibles)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    	global $conf;

    	$this->db->begin();

<<<<<<< HEAD
        // Insert emailing targest from array into database
=======
        // Insert emailing targets from array into database
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $j = 0;
        $num = count($cibles);
        foreach ($cibles as $targetarray)
        {
        	if (! empty($targetarray['email'])) // avoid empty email address
        	{
        		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_cibles";
        		$sql.= " (fk_mailing,";
        		$sql.= " fk_contact,";
        		$sql.= " lastname, firstname, email, other, source_url, source_id,";
       			$sql.= " tag,";
        		$sql.= " source_type)";
        		$sql.= " VALUES (".$mailing_id.",";
        		$sql.= (empty($targetarray['fk_contact']) ? '0' : "'".$targetarray['fk_contact']."'") .",";
        		$sql.= "'".$this->db->escape($targetarray['lastname'])."',";
        		$sql.= "'".$this->db->escape($targetarray['firstname'])."',";
        		$sql.= "'".$this->db->escape($targetarray['email'])."',";
        		$sql.= "'".$this->db->escape($targetarray['other'])."',";
        		$sql.= "'".$this->db->escape($targetarray['source_url'])."',";
        		$sql.= (empty($targetarray['source_id']) ? 'null' : "'".$this->db->escape($targetarray['source_id'])."'").",";
       			$sql .= "'".$this->db->escape(dol_hash($targetarray['email'].';'.$targetarray['lastname'].';'.$mailing_id.';'.$conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY))."',";
        		$sql .= "'".$this->db->escape($targetarray['source_type'])."')";
<<<<<<< HEAD
        		dol_syslog(get_class($this)."::".__METHOD__,LOG_DEBUG);
=======
        		dol_syslog(get_class($this)."::".__METHOD__, LOG_DEBUG);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
        				dol_syslog($this->db->error());
        				$this->error=$this->db->error();
=======
        				dol_syslog($this->db->error().' : '.$targetarray['email']);
        				$this->error=$this->db->error().' : '.$targetarray['email'];
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        				$this->db->rollback();
        				return -1;
        			}
        		}
        	}
        }

        dol_syslog(get_class($this)."::".__METHOD__.": mailing ".$j." targets added");

<<<<<<< HEAD
=======
        /*
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        //Update the status to show thirdparty mail that don't want to be contacted anymore'
        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " SET statut=3";
        $sql .= " WHERE fk_mailing=".$mailing_id." AND email in (SELECT email FROM ".MAIN_DB_PREFIX."societe where fk_stcomm=-1)";
        $sql .= " AND source_type='thirdparty'";
        dol_syslog(get_class($this)."::".__METHOD__.": mailing update status to display thirdparty mail that do not want to be contacted");
        $result=$this->db->query($sql);

<<<<<<< HEAD


=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        //Update the status to show contact mail that don't want to be contacted anymore'
        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " SET statut=3";
        $sql .= " WHERE fk_mailing=".$mailing_id." AND source_type='contact' AND (email in (SELECT sc.email FROM ".MAIN_DB_PREFIX."socpeople AS sc ";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON s.rowid=sc.fk_soc WHERE s.fk_stcomm=-1 OR no_email=1))";
        dol_syslog(get_class($this)."::".__METHOD__.": mailing update status to display contact mail that do not want to be contacted",LOG_DEBUG);
        $result=$this->db->query($sql);
<<<<<<< HEAD

=======
		*/

        $sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " SET statut=3";
        $sql .= " WHERE fk_mailing=".$mailing_id." AND email IN (SELECT mu.email FROM ".MAIN_DB_PREFIX."mailing_unsubscribe AS mu WHERE mu.entity IN ('".getEntity('mailing')."'))";

        dol_syslog(get_class($this)."::".__METHOD__.":mailing update status to display emails that do not want to be contacted anymore", LOG_DEBUG);
        $result=$this->db->query($sql);
        if (! $result)
        {
        	dol_print_error($this->db);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $this->update_nb($mailing_id);

        $this->db->commit();
<<<<<<< HEAD
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
=======

        return $j;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Supprime tous les destinataires de la table des cibles
     *
     *  @param  int		$mailing_id        Id of emailing
     *  @return	void
     */
    public function clear_target($mailing_id)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles";
        $sql .= " WHERE fk_mailing = ".$mailing_id;

        if (! $this->db->query($sql))
        {
            dol_syslog($this->db->error());
        }

        $this->update_nb($mailing_id);
    }
<<<<<<< HEAD

}

=======
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
