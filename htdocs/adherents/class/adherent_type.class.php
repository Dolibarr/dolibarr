<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2016      Charlie Benke        <charlie@patas-monkey.com>
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
 *	\file       htdocs/adherents/class/adherent_type.class.php
 *	\ingroup    member
 *	\brief      File of class to manage members types
 *	\author     Rodolphe Quiedeville
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class AdherentType extends CommonObject
{
	public $table_element = 'adherent_type';
	public $element = 'adherent_type';
	public $picto = 'group';
	
	/** @var string Label */
	public $label;
	/**
	 * @var bool
	 * @deprecated Use subscription
	 * @see subscription
	 */
	public $cotisation;
	/**
	 * @var int Subsription required (0 or 1)
	 * @since 5.0
	 */
	public $subscription;
	/** @var string Public note */
	public $note;
	/** @var bool Can vote*/
	public $vote;
	/** @var bool Email sent during validation */
	public $mail_valid;


    /**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->statut = 1;
    }


    /**
     *  Fonction qui permet de creer le status de l'adherent
     *
     *  @param      User		$user		User making creation
     *  @return     int						>0 if OK, < 0 if KO
     */
    function create($user)
    {
        global $conf;

        $this->statut=(int) $this->statut;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent_type (";
        $sql.= "libelle";
        $sql.= ", entity";
        $sql.= ") VALUES (";
        $sql.= "'".$this->db->escape($this->libelle)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog("Adherent_type::create", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent_type");
            return $this->update($user);
        }
        else
        {
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }
    }


    /**
     *  Met a jour en base donnees du type
     *
     *	@param		User	$user	Object user making change
     *  @return		int				>0 if OK, < 0 if KO
     */
    function update($user)
    {
    	global $hookmanager,$conf;

    	$error=0;

        $this->libelle=trim($this->libelle);

        $sql = "UPDATE ".MAIN_DB_PREFIX."adherent_type ";
        $sql.= "SET ";
        $sql.= "statut = ".$this->statut.",";
        $sql.= "libelle = '".$this->db->escape($this->libelle) ."',";
        $sql.= "subscription = '".$this->subscription."',";
        $sql.= "note = '".$this->db->escape($this->note)."',";
        $sql.= "vote = '".$this->vote."',";
        $sql.= "mail_valid = '".$this->db->escape($this->mail_valid)."'";
        $sql .= " WHERE rowid =".$this->id;

        $result = $this->db->query($sql);
        if ($result)
        {
        	$action='update';

        	// Actions on extra fields (by external module or standard code)
        	$hookmanager->initHooks(array('membertypedao'));
        	$parameters=array('membertype'=>$this->id);
        	$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
        	if (empty($reshook))
        	{
        		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        		{
        			$result=$this->insertExtraFields();
        			if ($result < 0)
        			{
        				$error++;
        			}
        		}
        	}
        	else if ($reshook < 0) $error++;


            return 1;
        }
        else
        {
            $this->error=$this->db->error().' sql='.$sql;
            return -1;
        }
    }

    /**
     *	Fonction qui permet de supprimer le status de l'adherent
     *
     *	@param      int		$rowid		Id of member type to delete
     *  @return		int					>0 if OK, 0 if not found, < 0 if KO
     */
    function delete($rowid='')
    {
    	if (empty($rowid)) $rowid=$this->id;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_type WHERE rowid = ".$rowid;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
            {
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            print "Err : ".$this->db->error();
            return 0;
        }
    }

    /**
     *  Fonction qui permet de recuperer le status de l'adherent
     *
     *  @param 		int		$rowid		Id of member type to load
     *  @return		int					<0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql = "SELECT d.rowid, d.libelle as label, d.statut, d.subscription, d.mail_valid, d.note, d.vote";
        $sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as d";
        $sql .= " WHERE d.rowid = ".$rowid;

        dol_syslog("Adherent_type::fetch", LOG_DEBUG);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->label          = $obj->label;
                $this->libelle        = $obj->label;	// For backward compatibility
                $this->statut         = $obj->statut;
                $this->subscription   = $obj->subscription;
                $this->mail_valid     = $obj->mail_valid;
                $this->note           = $obj->note;
                $this->vote           = $obj->vote;
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Return list of members' type
     *
     *  @return 	array	List of types of members
     */
    function liste_array()
    {
        global $conf,$langs;

        $adherenttypes = array();

        $sql = "SELECT rowid, libelle";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type";
        $sql.= " WHERE entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);

                    $adherenttypes[$obj->rowid] = $langs->trans($obj->libelle);
                    $i++;
                }
            }
        }
        else
        {
            print $this->db->error();
        }
        return $adherenttypes;
    }


    /**
     *    	Return clicable name (with picto eventually)
     *
     *		@param		int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     *		@param		int		$maxlen			length max libelle
     *		@return		string					String with URL
     */
    function getNomUrl($withpicto=0,$maxlen=0)
    {
        global $langs;

        $result='';
        $label=$langs->trans("ShowTypeCard",$this->libelle);

        $link = '<a href="'.DOL_URL_ROOT.'/adherents/type.php?rowid='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend='</a>';

        $picto='group';

        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$link.($maxlen?dol_trunc($this->libelle,$maxlen):$this->libelle).$linkend;
        return $result;
    }


    /**
     *     getLibStatut
     *
     *     @return string     Return status of a type of member
     */
    function getLibStatut()
    {
    	return '';
    }
    
    /**
     *     getMailOnValid
     *
     *     @return string     Return mail model
     */
    function getMailOnValid()
    {
        global $conf;

        if (! empty($this->mail_valid) && trim(dol_htmlentitiesbr_decode($this->mail_valid)))
        {
            return $this->mail_valid;
        }
        else
        {
            return $conf->global->ADHERENT_MAIL_VALID;
        }
    }

    /**
     *     getMailOnSubscription
     *
     *     @return string     Return mail model
     */
    function getMailOnSubscription()
    {
        global $conf;
	// mail_subscription not  defined so never used
        if (! empty($this->mail_subscription) && trim(dol_htmlentitiesbr_decode($this->mail_subscription)))  // Property not yet defined
        {
            return $this->mail_subscription;
        }
        else
        {
            return $conf->global->ADHERENT_MAIL_COTIS;
        }
    }

    /**
     *     getMailOnResiliate
     *
     *     @return string     Return mail model
     */
    function getMailOnResiliate()
    {
        global $conf;
	// NOTE mail_resiliate not defined so never used
        if (! empty($this->mail_resiliate) && trim(dol_htmlentitiesbr_decode($this->mail_resiliate)))  // Property not yet defined
        {
            return $this->mail_resiliate;
        }
        else
        {
            return $conf->global->ADHERENT_MAIL_RESIL;
        }
    }
}
