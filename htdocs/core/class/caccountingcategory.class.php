<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *      \file       htdocs/core/class/caccountingcategory.class.php
 *      \ingroup    core
 *      \brief      This file is a CRUD class file (Create/Read/Update/Delete) for c_accounting_category dictionary
 */


/**
 * 	Class to manage dictionary Accounting Category (used by imports)
 */
class Caccountingcategory // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='c_accounting_category';			//!< Id that identify managed objects
	//var $table_element='c_accounting_category';	//!< Name of table without prefix where object is stored

    var $id;
    var $code;
   	var $label;
   	var $range_account;
   	var $sens;
    var $category_type;
   	var $formula;
    var $position;
    var $fk_country;
   	var $active;




    /**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *  Create object into database
     *
     *  @param      User	$user        User that create
     *  @param      int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return     int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        if (isset($this->code)) $this->code=trim($this->code);
        if (isset($this->label)) $this->label=trim($this->label);
        if (isset($this->range_account)) $this->range_account=trim($this->range_account);
        if (isset($this->sens)) $this->sens=trim($this->sens);
        if (isset($this->category_type)) $this->category_type=trim($this->category_type);
        if (isset($this->formula)) $this->formula=trim($this->formula);
        if (isset($this->position)) $this->position=trim($this->position);
        if (isset($this->fk_country)) $this->fk_country=trim($this->fk_country);
		if (isset($this->active)) $this->active=trim($this->active);

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_accounting_category(";
		$sql.= "rowid,";
        $sql.= "code,";
        $sql.= "label,";
        $sql.= "range_account,";
        $sql.= "sens,";
        $sql.= "category_type,";
        $sql.= "formula,";
        $sql.= "position,";
        $sql.= "fk_country,";
		$sql.= "active";
        $sql.= ") VALUES (";
        $sql.= " ".(! isset($this->rowid)?'NULL':"'".$this->rowid."'").",";
        $sql.= " ".(! isset($this->code)?'NULL':"'".$this->db->escape($this->code)."'").",";
        $sql.= " ".(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'").",";
        $sql.= " ".(! isset($this->range_account)?'NULL':"'".$this->db->escape($this->range_account)."'").",";
        $sql.= " ".(! isset($this->sens)?'NULL':"'".$this->sens."'").",";
        $sql.= " ".(! isset($this->category_type)?'NULL':"'".$this->category_type."'").",";
        $sql.= " ".(! isset($this->formula)?'NULL':"'".$this->db->escape($this->formula)."'").",";
        $sql.= " ".(! isset($this->position)?'NULL':"'".$this->position."'").",";
        $sql.= " ".(! isset($this->fk_country)?'NULL':"'".$this->fk_country."'").",";
		$sql.= " ".(! isset($this->active)?'NULL':"'".$this->active."'")."";
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."c_accounting_category");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *  Load object in memory from database
     *
     *  @param      int		$id    	Id object
     *  @param		string	$code	Code
     *  @param		string	$label	Label
     *  @return     int          	<0 if KO, >0 if OK
     */
    function fetch($id,$code='',$label='')
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.code,";
		$sql.= " t.label,";
        $sql.= " t.range_account,";
        $sql.= " t.sens,";
        $sql.= " t.category_type,";
        $sql.= " t.formula,";
        $sql.= " t.position,";
        $sql.= " t.fk_country,";
		$sql.= " t.active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_accounting_category as t";
        if ($id)   $sql.= " WHERE t.rowid = ".$id;
        elseif ($code) $sql.= " WHERE t.code = '".$this->db->escape($code)."'";
        elseif ($label) $sql.= " WHERE t.label = '".$this->db->escape($label)."'";

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id            = $obj->rowid;
                $this->code          = $obj->code;
                $this->label         = $obj->label;
                $this->range_account = $obj->range_account;
                $this->sens          = $obj->sens;
                $this->category_type = $obj->category_type;
                $this->formula       = $obj->formula;
                $this->position      = $obj->position;
				$this->fk_country    = $obj->fk_country;
				$this->active        = $obj->active;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Update object into database
     *
     *  @param      User	$user        User that modify
     *  @param      int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return     int     		   	 <0 if KO, >0 if OK
     */
    function update($user=null, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
        if (isset($this->code)) $this->code=trim($this->code);
        if (isset($this->label)) $this->label=trim($this->label);
        if (isset($this->range_account)) $this->range_account=trim($this->range_account);
        if (isset($this->sens)) $this->sens=trim($this->sens);
        if (isset($this->category_type)) $this->category_type=trim($this->category_type);
        if (isset($this->formula)) $this->formula=trim($this->formula);
        if (isset($this->position)) $this->position=trim($this->position);
        if (isset($this->fk_country)) $this->fk_country=trim($this->fk_country);
		if (isset($this->active)) $this->active=trim($this->active);


		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_accounting_category SET";
        $sql.= " code=".(isset($this->code)?"'".$this->db->escape($this->code)."'":"null").",";
        $sql.= " label=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " range_account=".(isset($this->range_account)?"'".$this->db->escape($this->range_account)."'":"null").",";
        $sql.= " sens=".(isset($this->sens)?$this->sens:"null").",";
        $sql.= " category_type=".(isset($this->category_type)?$this->category_type:"null").",";
        $sql.= " formula=".(isset($this->formula)?"'".$this->db->escape($this->formula)."'":"null").",";
        $sql.= " position=".(isset($this->position)?$this->position:"null").",";
        $sql.= " fk_country=".(isset($this->fk_country)?$this->fk_country:"null").",";
		$sql.= " active=".(isset($this->active)?$this->active:"null")."";
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


 	/**
 	 *  Delete object in database
	 *
     *	@param  User	$user        User that delete
     *  @param	int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."c_accounting_category";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action call a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

}
