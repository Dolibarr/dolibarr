<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/events.class.php
 *      \ingroup    core
 *		\brief      Events class file.
 *		\version    $Id$
 *		\author		Laurent Destailleur
 *		\remarks	An event is when status of an object change.
 */

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
        \class      Events
        \brief      Events class
		\remarks	Initialy built by build_class_from_table on 2008-02-28 17:25
*/
class Events // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='events';				//!< Id that identify managed objects
	var $table_element='events';		//!< Name of table without prefix where object is stored
    
    var $id;
    
	var $tms;
	var $type;
	var $dateevent;
	var $description;

    

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Events($DB) 
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
		$this->id=trim($this->id);
		$this->fk_action=trim($this->fk_action);
		$this->description=trim($this->description);

		// Check parameters
		if (! $this->description) { $this->error='ErrorBadValueForParameter'; return -1; }
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."events(";
		
		$sql.= "type,";
		$sql.= "ip,";
		$sql.= "dateevent,";
		$sql.= "fk_user,";
		$sql.= "description";

        $sql.= ") VALUES (";
       
		$sql.= " '".$this->type."',";
		$sql.= " '".$_SERVER['REMOTE_ADDR']."',";
		$sql.= " ".$this->db->idate($this->dateevent).",";
		$sql.= " ".($user->id?"'".$user->id."'":'NULL').",";
		$sql.= " '".addslashes($this->description)."'";

		$sql.= ")";

	   	dolibarr_syslog("Events::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."events");
    
            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Events::create ".$this->error, LOG_ERR);
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
        
		$this->id=trim($this->id);
		$this->type=trim($this->type);
		$this->description=trim($this->description);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."events SET";
        
		$sql.= " type='".$this->type."',";
		$sql.= " dateevent=".$this->db->idate($this->dateevent).",";
		$sql.= " description='".addslashes($this->description)."'";
        
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Events::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Events::update ".$this->error, LOG_ERR);
            return -1;
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
		
		$sql.= " ".$this->db->pdate('t.tms').",";
		$sql.= " t.type,";
		$sql.= " ".$this->db->pdate('t.dateevent').",";
		$sql.= " t.description";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."events as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog("Events::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                
				$this->tms = $obj->tms;
				$this->type = $obj->type;
				$this->dateevent = $obj->dateevent;
				$this->description = $obj->description;

                
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Events::fetch ".$this->error, LOG_ERR);
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
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."events";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dolibarr_syslog("Events::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Events::delete ".$this->error, LOG_ERR);
			return -1;
		}
	
		return 1;
	}

  
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->tms=time();
		$this->type='';
		$this->dateevent=time();
		$this->description='This is a specimen event';
	}

}
?>
