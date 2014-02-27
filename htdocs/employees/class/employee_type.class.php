<?php
/* Copyright (C) 2002      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com> 
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
 *	\file       htdocs/employees/class/employee_type.class.php
 *	\ingroup    employee
 *	\brief      File of class to manage employees types
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class EmployeeType extends CommonObject
{
    public $table_element = 'employee_type';
    public $element = 'employee_type';

    var $id;
    var $label;
    var $statut;
    var $note;
    
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
     *  Fonction qui permet de creer le status du salarié
     *
     *  @param      User		$user		User making creation
     *  @return     						>0 if OK, < 0 if KO
     */
    function create($user)
    {
        global $conf;

        $this->statut=trim($this->statut);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."employee_type (";
        $sql.= "label";
        $sql.= ", note";
        $sql.= ", entity";
        $sql.= ") VALUES (";
        $sql.= "'".$this->db->escape($this->label)."'";
        $sql.= ", '".$this->db->escape($this->note)."'";
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog("Employee_type::create sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."employee_type");
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
    	global $hookmanager;
    	
        $this->label=trim($this->label);

        $sql = "UPDATE ".MAIN_DB_PREFIX."employee_type ";
        $sql.= "SET ";
        $sql.= "statut = ".$this->statut.",";
        $sql.= "label = '".$this->db->escape($this->label) ."',";
        $sql.= "note = '".$this->db->escape($this->note)."'";
        $sql.= " WHERE rowid = $this->id";

        $result = $this->db->query($sql);
        if ($result)
        {
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
     *	Fonction qui permet de supprimer le status du salarié
     *
     *	@param      int		$rowid		Id of member type to delete
     *  @return		int					>0 if OK, < 0 if KO
     */
    function delete($rowid)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."employee_type WHERE rowid = $rowid";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ( $this->db->affected_rows($resql) )
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
     *  Fonction qui permet de recuperer le status du salarié
     *
     *  @param 		int		$rowid		Id of member type to load
     *  @return		int					<0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql = "SELECT d.rowid, d.label, d.statut, d.note";
        $sql .= " FROM ".MAIN_DB_PREFIX."employee_type as d";
        $sql .= " WHERE d.rowid = ".$rowid;

        dol_syslog("Employee_type::fetch sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id             = $obj->rowid;
                $this->ref            = $obj->rowid;
                $this->label          = $obj->label;
                $this->statut         = $obj->statut;
                $this->note           = $obj->note;
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog("Employee_type::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *  Return list of employee's type
     *
     *  @return 	array	List of types of employees
     */
    function liste_array()
    {
        global $conf,$langs;

        $projets = array();

        $sql = "SELECT rowid, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."employee_type";
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

                    $projets[$obj->rowid] = $langs->trans($obj->label);
                    $i++;
                }
            }
            return $projets;
        }
        else
        {
            print $this->db->error();
        }

    }


    /**
     *    	Renvoie nom clicable (avec eventuellement le picto)
     *
     *		@param		int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *		@param		int		$maxlen			length max label
     *		@return		string					String with URL
     */
    function getNomUrl($withpicto=0,$maxlen=0)
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/employees/type.php?rowid='.$this->id.'">';
        $lienfin='</a>';

        $picto='group';
        $label=$langs->trans("ShowTypeCard",$this->label);

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.($maxlen?dol_trunc($this->label,$maxlen):$this->label).$lienfin;
        return $result;
    }
    
    /**
     *     getMailOnValid
     *
     *     @return     Return mail model
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
            return $conf->global->EMPLOYEE_MAIL_VALID;
        }
    }

    /**
     *     getMailOnResiliate
     *
     *     @return     Return mail model
     */
    function getMailOnResiliate()
    {
        global $conf;

        if (! empty($this->mail_resiliate) && trim(dol_htmlentitiesbr_decode($this->mail_resiliate)))  // Property not yet defined
        {
            return $this->mail_resiliate;
        }
        else
        {
            return $conf->global->EMPLOYEE_MAIL_RESIL;
        }
    }
}
?>
