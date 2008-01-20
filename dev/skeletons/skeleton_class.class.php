<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
        \file       dev/skeletons/skeleton_class.class.php
        \ingroup    unknown
        \brief      This file is an example for a class file
		\version    $Id$
		\author		Put author name here
		\remarks	Put here some comments
*/

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
        \class      Skeleton_class
        \brief      Put here description of your class
		\remarks	Put here some comments
*/
class Skeleton_class // extends CommonObject
{
	var $db;							// To store db handler
	var $error;							// To return error code (or message)
	var $errors=array();				// To return several error codes (or messages)
	//var $element='skeleton';			// Id that identify managed objects
	//var $table_element='skeleton';		// Name of table without prefix where object is stored
    
    var $id;
    var $prop1;
    var $prop2;
	//...

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Skeleton_class($DB) 
    {
        $this->db = $DB;
        return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        User that create
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        $this->prop1=trim($this->prop1);
        $this->prop2=trim($this->prop2);
		//...

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."mytable(";
		$sql.= " field1,";
		$sql.= " field2";
		//...
        $sql.= ") VALUES (";
        $sql.= " '".$this->prop1."',";
        $sql.= " '".$this->prop2."'";
		//...
		$sql.= ")";

	   	dolibarr_syslog("Skeleton_class::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."mytable");
    
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
			if ($result < 0) $this->errors=$interface->errors;
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Skeleton_class::create ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /*
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=no, 1=yes (no update trigger)
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        $this->prop1=trim($this->prop1);
        $this->prop2=trim($this->prop2);
		//...

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."mytable SET";
        $sql.= " field1='".addslashes($this->field1)."',";
        $sql.= " field2='".addslashes($this->field2)."'";
		//...
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Skeleton_class::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Skeleton_class::update ".$this->error, LOG_ERR);
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) $this->errors=$interface->errors;
            // Fin appel triggers
    	}

        return 1;
    }
  
  
    /*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id, $user=0)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.field1,";
		$sql.= " t.field2";
		//...
        $sql.= " FROM ".MAIN_DB_PREFIX."mytable as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog("Skeleton_class::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->prop1 = $obj->field1;
                $this->prop2 = $obj->field2;
				//...
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Skeleton_class::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }
    
    
 	/*
	*   \brief      Delete object in database
    *	\param      user        User that delete
	*	\return		int			<0 if KO, >0 if OK
	*/
	function delete($user)
	{
		global $conf, $langs;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."mytable";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dolibarr_syslog("Skeleton_class::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Skeleton_class::delete ".$this->error, LOG_ERR);
			return -1;
		}
	
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		if ($result < 0) $this->errors=$interface->errors;
        // Fin appel triggers

		return 1;
	}

  
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		$this->prop1='prop1';
		$this->prop2='prop2';
	}

}
?>
