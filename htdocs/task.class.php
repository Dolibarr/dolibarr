<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       task.class.php
        \ingroup    project
        \brief      This file is a CRUD class file for Taks (Create/Read/Update/Delete)
		\version    $Id$
		\remarks	Initialy built by build_class_from_table on 2008-09-10 12:41
*/

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
        \class      Projet_task
        \brief      Put here description of your class
		\remarks	Initialy built by build_class_from_table on 2008-09-10 12:41
*/
class Task // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='projet_task';			//!< Id that identify managed objects
	//var $table_element='projet_task';	//!< Name of table without prefix where object is stored
    
    var $id;
    
	var $fk_projet;
	var $fk_task_parent;
	var $title;
	var $duration_effective;
	var $fk_user_creat;
	var $statut;
	var $note;

    

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Task($DB) 
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
        
		if (isset($this->fk_projet)) $this->fk_projet=trim($this->fk_projet);
		if (isset($this->fk_task_parent)) $this->fk_task_parent=trim($this->fk_task_parent);
		if (isset($this->title)) $this->title=trim($this->title);
		if (isset($this->duration_effective)) $this->duration_effective=trim($this->duration_effective);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->statut)) $this->statut=trim($this->statut);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task(";
		
		$sql.= "fk_projet,";
		$sql.= "fk_task_parent,";
		$sql.= "title,";
		$sql.= "duration_effective,";
		$sql.= "fk_user_creat,";
		$sql.= "statut,";
		$sql.= "note";

		
        $sql.= ") VALUES (";
        
		$sql.= " ".(! isset($this->fk_projet)?'NULL':"'".$this->fk_projet."'").",";
		$sql.= " ".(! isset($this->fk_task_parent)?'NULL':"'".$this->fk_task_parent."'").",";
		$sql.= " ".(! isset($this->title)?'NULL':"'".$this->title."'").",";
		$sql.= " ".(! isset($this->duration_effective)?'NULL':"'".$this->duration_effective."'").",";
		$sql.= " ".(! isset($this->fk_user_creat)?'NULL':"'".$this->fk_user_creat."'").",";
		$sql.= " ".(! isset($this->statut)?'NULL':"'".$this->statut."'").",";
		$sql.= " ".(! isset($this->note)?'NULL':"'".$this->note."'")."";

        
		$sql.= ")";

		$this->db->begin();
		
	   	dolibarr_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        
		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");
    
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
	            dolibarr_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
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
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.fk_projet,";
		$sql.= " t.fk_task_parent,";
		$sql.= " t.title,";
		$sql.= " t.duration_effective,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.statut,";
		$sql.= " t.note";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                
				$this->fk_projet = $obj->fk_projet;
				$this->fk_task_parent = $obj->fk_task_parent;
				$this->title = $obj->title;
				$this->duration_effective = $obj->duration_effective;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->statut = $obj->statut;
				$this->note = $obj->note;

                
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
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
        
		if (isset($this->fk_projet)) $this->fk_projet=trim($this->fk_projet);
		if (isset($this->fk_task_parent)) $this->fk_task_parent=trim($this->fk_task_parent);
		if (isset($this->title)) $this->title=trim($this->title);
		if (isset($this->duration_effective)) $this->duration_effective=trim($this->duration_effective);
		if (isset($this->fk_user_creat)) $this->fk_user_creat=trim($this->fk_user_creat);
		if (isset($this->statut)) $this->statut=trim($this->statut);
		if (isset($this->note)) $this->note=trim($this->note);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
        
		$sql.= " fk_projet=".(isset($this->fk_projet)?$this->fk_projet:"null").",";
		$sql.= " fk_task_parent=".(isset($this->fk_task_parent)?$this->fk_task_parent:"null").",";
		$sql.= " title=".(isset($this->title)?"'".addslashes($this->title)."'":"null").",";
		$sql.= " duration_effective=".(isset($this->duration_effective)?$this->duration_effective:"null").",";
		$sql.= " fk_user_creat=".(isset($this->fk_user_creat)?$this->fk_user_creat:"null").",";
		$sql.= " statut=".(isset($this->statut)?$this->statut:"null").",";
		$sql.= " note=".(isset($this->note)?"'".addslashes($this->note)."'":"null")."";

        
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();
        
		dolibarr_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
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
	            dolibarr_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
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
		
		$this->db->begin();
		
		if ($this->hasChildren() > 0)
		{
			dolibarr_syslog(get_class($this)."::delete Can't delete record as it has some child", LOG_WARNING);
			$this->error='ErrorRecordHasChildren';
			$this->db->rollback();
			return 0;
		}
		
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE rowid=".$this->id;
		
		dolibarr_syslog(get_class($this)."::delete sql=".$sql);
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
	            dolibarr_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
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
	 *		\brief		Return nb of children
	 *		\return 	<0 if KO, 0 if no children, >0 if OK
	 */
	function hasChildren()
	{
		$ret=0;
		
		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE fk_task_parent=".$this->id;
		
		dolibarr_syslog(get_class($this)."::hasChildren sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		else
		{
			$obj=$this->db->fetch_object($resql);
			if ($obj) $ret=$obj->nb;
		}
		
		if (! $error)
		{
			return $ret;			
		}
		else
		{
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
		
		$this->fk_projet='';
		$this->fk_task_parent='';
		$this->title='';
		$this->duration_effective='';
		$this->fk_user_creat='';
		$this->statut='';
		$this->note='';
	}

}
?>