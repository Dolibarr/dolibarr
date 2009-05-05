<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       compta/bank/bankcateg.class.php
 *      \ingroup    banque
 *      \brief      This file is CRUD class file (Create/Read/Update/Delete) for bank categories
 *		\version    $Id$
 *		\author		Laurent Destailleur
 *		\remarks	Initialy built by build_class_from_table on 2009-01-02 15:26
 */

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
 *      \class      Bank_categ
 *      \brief      Put here description of your class
 *		\remarks	Initialy built by build_class_from_table on 2009-01-02 15:26
 */
class BankCateg // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='bank_categ';			//!< Id that identify managed objects
	//var $table_element='bank_categ';	//!< Name of table without prefix where object is stored
    
  var $id;
    
	var $label;

  	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function BankCateg($DB) 
    {
    	$this->db = $DB;
      return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
    	$error=0;
    	
    	// Clean parameters
    	if (isset($this->label)) $this->label=trim($this->label);
    	
    	// Check parameters
    	// Put here code to add control on parameters values
    	
    	// Insert request
    	$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_categ (";
    	$sql.= "label";
    	$sql.= ", entity";
    	$sql.= ") VALUES (";
    	$sql.= " ".(! isset($this->label)?'NULL':"'".addslashes($this->label)."'")."";
    	$sql.= ", ".$conf->entity;
    	$sql.= ")";
    	
    	$this->db->begin();
		
	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
      $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
    	
    	if (! $error)
      {
      	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_categ");
    
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.
	            
	            //// Call triggers
	            //include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
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
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs,$conf;
    	
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
    	$sql.= " t.label";
    	$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ as t";
    	$sql.= " WHERE t.rowid = ".$id;
    	$sql.= " AND t.entity = ".$conf->entity;
    
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
      $resql=$this->db->query($sql);
      if ($resql)
      {
      	if ($this->db->num_rows($resql))
        {
        	$obj = $this->db->fetch_object($resql);
        	
        	$this->id    = $obj->rowid;
        	$this->label = $obj->label;
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
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
    	$error=0;
    	
    	// Clean parameters
    	if (isset($this->label)) $this->label=trim($this->label);

      // Check parameters
      // Put here code to add control on parameters values
      
      // Update request
      $sql = "UPDATE ".MAIN_DB_PREFIX."bank_categ SET";
      $sql.= " label=".(isset($this->label)?"'".addslashes($this->label)."'":"null")."";
      $sql.= " WHERE rowid=".$this->id;
      $sql.= " AND entity = ".$conf->entity;
      
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
	            //include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
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
	 *   \brief      Delete object in database
     *	\param      user        	User that delete
     *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *	\return		int				<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_categ";
		$sql.= " WHERE rowid=".$this->id;
		$sql.= " AND entity = ".$conf->entity;
	
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
		        //include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
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


	
	/**
	 *		\brief      Load an object from its id and create a new one in database
	 *		\param      fromid     		Id of object to clone
	 * 	 	\return		int				New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;
		
		$error=0;
		
		$object=new Bank_categ($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...
				
		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0) 
		{
			$this->error=$object->error;
			$error++;
		}
		
		if (! $error)
		{
			
			
			
		}
		
		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->label='';

		
	}

}
?>
