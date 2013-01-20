<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/class/cpays.class.php
 *      \ingroup    core
 *      \brief      This file is a CRUD class file (Create/Read/Update/Delete) for c_pays dictionnary
 *					Initialy built by build_class_from_table on 2012-01-17 22:03
 */

// Put here all includes required by your class file
//require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 *      \class      Cpays
 *      \brief      Class to manage dictionnary Countries (used by imports)
 */
class Cpays // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='cpays';			//!< Id that identify managed objects
	//var $table_element='cpays';	//!< Name of table without prefix where object is stored

    var $id;
	var $code;
	var $code_iso;
	var $libelle;
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
		if (isset($this->code_iso)) $this->code_iso=trim($this->code_iso);
		if (isset($this->libelle)) $this->libelle=trim($this->libelle);
		if (isset($this->active)) $this->active=trim($this->active);

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_pays(";
		$sql.= "rowid,";
		$sql.= "code,";
		$sql.= "code_iso,";
		$sql.= "libelle,";
		$sql.= "active";
        $sql.= ") VALUES (";
		$sql.= " ".(! isset($this->rowid)?'NULL':"'".$this->rowid."'").",";
		$sql.= " ".(! isset($this->code)?'NULL':"'".$this->db->escape($this->code)."'").",";
		$sql.= " ".(! isset($this->code_iso)?'NULL':"'".$this->db->escape($this->code_iso)."'").",";
		$sql.= " ".(! isset($this->libelle)?'NULL':"'".$this->db->escape($this->libelle)."'").",";
		$sql.= " ".(! isset($this->active)?'NULL':"'".$this->active."'")."";
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."c_pays");

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
     *  @return     int          	<0 if KO, >0 if OK
     */
    function fetch($id,$code='')
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.code,";
		$sql.= " t.code_iso,";
		$sql.= " t.libelle,";
		$sql.= " t.active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_pays as t";
        if ($id)   $sql.= " WHERE t.rowid = ".$id;
        elseif ($code) $sql.= " WHERE t.code = '".$this->db->escape($code)."'";

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
				$this->code = $obj->code;
				$this->code_iso = $obj->code_iso;
				$this->libelle = $obj->libelle;
				$this->active = $obj->active;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
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
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->code_iso)) $this->code_iso=trim($this->code_iso);
		if (isset($this->libelle)) $this->libelle=trim($this->libelle);
		if (isset($this->active)) $this->active=trim($this->active);


		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_pays SET";
		$sql.= " code=".(isset($this->code)?"'".$this->db->escape($this->code)."'":"null").",";
		$sql.= " code_iso=".(isset($this->code_iso)?"'".$this->db->escape($this->code_iso)."'":"null").",";
		$sql.= " libelle=".(isset($this->libelle)?"'".$this->db->escape($this->libelle)."'":"null").",";
		$sql.= " active=".(isset($this->active)?$this->active:"null")."";
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
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
     *	@param     User	$user        User that delete
     *  @param     int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."c_pays";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete sql=".$sql);
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
?>
