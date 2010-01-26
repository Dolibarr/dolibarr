<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/projet/tasks/task.class.php
 *      \ingroup    project
 *      \brief      This file is a CRUD class file for Task (Create/Read/Update/Delete)
 *		\version    $Id$
 *		\remarks	Initialy built by build_class_from_table on 2008-09-10 12:41
 */


/**
 *      \class      Projet_task
 *      \brief      Put here description of your class
 *		\remarks	Initialy built by build_class_from_table on 2008-09-10 12:41
 */
class Task extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='project_task';			//!< Id that identify managed objects
	var $table_element='project_task';	//!< Name of table without prefix where object is stored

    var $id;

	var $fk_project;
	var $fk_task_parent;
	var $label;
	var $description;
	var $duration_effective;
	var $date_c;
	var $date_start;
	var $date_end;
	var $fk_user_creat;
	var $fk_user_valid;
	var $statut;
	var $note_private;
	var $note_public;


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
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		$this->note_private = trim($this->note_private);
		$this->note_public = trim($this->note_public);

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task (";
		$sql.= "fk_projet";
		$sql.= ", fk_task_parent";
		$sql.= ", label";
		$sql.= ", datec";
		$sql.= ", fk_user_creat";
        $sql.= ") VALUES (";
		$sql.= $this->fk_project;
		$sql.= ", ".$this->fk_task_parent;
		$sql.= ", '".addslashes($this->label)."'";
		$sql.= ", ".$this->db->idate($this->date_c);
		$sql.= ", ".$user->id;
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");

			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_CREATE',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
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
    	global $langs;
    	
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_projet,";
		$sql.= " t.fk_task_parent,";
		$sql.= " t.label,";
		$sql.= " t.description,";
		$sql.= " t.duration_effective,";
		$sql.= " t.dateo,";
		$sql.= " t.datee,";
		$sql.= " t.fk_user_creat,";
		$sql.= " t.fk_user_valid,";
		$sql.= " t.fk_statut,";
		$sql.= " t.note_private,";
		$sql.= " t.note_public";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id					= $obj->rowid;
                $this->ref					= $obj->rowid;
				$this->fk_project			= $obj->fk_projet;
				$this->fk_task_parent		= $obj->fk_task_parent;
				$this->label				= $obj->label;
				$this->description			= $obj->description;
				$this->duration_effective	= $obj->duration_effective;
				$this->date_c				= $this->db->jdate($obj->datec);
				$this->date_start			= $this->db->jdate($obj->dateo);
				$this->date_end				= $this->db->jdate($obj->datee);
				$this->fk_user_creat		= $obj->fk_user_creat;
				$this->fk_user_valid		= $obj->fk_user_valid;
				$this->fk_statut			= $obj->fk_statut;
				$this->note_private			= $obj->note_private;
				$this->note_public			= $obj->note_public;
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
		if (isset($this->fk_project)) $this->fk_project=trim($this->fk_project);
		if (isset($this->fk_task_parent)) $this->fk_task_parent=trim($this->fk_task_parent);
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->description)) $this->description=trim($this->description);
		if (isset($this->duration_effective)) $this->duration_effective=trim($this->duration_effective);

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
		$sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
		$sql.= " fk_task_parent=".(isset($this->fk_task_parent)?$this->fk_task_parent:"null").",";
		$sql.= " label=".(isset($this->label)?"'".addslashes($this->label)."'":"null").",";
		$sql.= " description=".(isset($this->description)?"'".addslashes($this->description)."'":"null").",";
		$sql.= " duration_effective=".(isset($this->duration_effective)?$this->duration_effective:"null").",";
		$sql.= " dateo=".($this->date_start!=''?$this->db->idate($this->date_start):'null').",";
		$sql.= " datee=".($this->date_end!=''?$this->db->idate($this->date_end):'null');
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
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

		$this->db->begin();

		if ($this->hasChildren() > 0)
		{
			dol_syslog(get_class($this)."::delete Can't delete record as it has some child", LOG_WARNING);
			$this->error='ErrorRecordHasChildren';
			$this->db->rollback();
			return 0;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
		        // Call triggers
		        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
		        $interface=new Interfaces($this->db);
		        $result=$interface->run_triggers('TASK_DELETE',$this,$user,$langs,$conf);
		        if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        // End call triggers
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
	 *		\brief		Return nb of children
	 *		\return 	<0 if KO, 0 if no children, >0 if OK
	 */
	function hasChildren()
	{
		$ret=0;

		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE fk_task_parent=".$this->id;

		dol_syslog(get_class($this)."::hasChildren sql=".$sql, LOG_DEBUG);
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
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='projecttask';

		$label=$langs->trans("ShowTask").': '.$this->ref;

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
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
	
	/**
	 * Return list of task for all projects or a particular project
	 * Sort order is on project, TODO then of position of task, and last on title of first level task
	 * @param	usert	Object user to limit task affected to a particular user
	 * @param	userp	Object user to limit projects of a particular user
	 * @param	mode	0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 * @return 	array	Array of tasks
	 */
	function getTasksArray($usert=0, $userp=0, $mode=0, $socid=0)
	{
		global $conf;

		$tasks = array();

		//print $usert.'-'.$userp.'<br>';

		// List of tasks
		$sql = "SELECT p.rowid as projectid, p.ref, p.title as plabel";
		$sql.= ", t.rowid, t.label, t.description, t.fk_task_parent, t.duration_effective";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
		$sql.= ", ".MAIN_DB_PREFIX."projet as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
		$sql.= " WHERE t.fk_projet = p.rowid";
		$sql.= " AND p.entity = ".$conf->entity;
		if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
		if ($this->id) $sql.= " AND t.fk_projet =".$this->id;
		$sql.= " ORDER BY p.ref, t.label";

		dol_syslog("Project::getTasksArray sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				$tasks[$i]->id           = $obj->rowid;
				$tasks[$i]->projectid    = $obj->projectid;
				$tasks[$i]->projectref   = $obj->ref;
				$tasks[$i]->projectlabel = $obj->plabel;
				$tasks[$i]->label        = $obj->label;
				$tasks[$i]->description  = $obj->description;
				$tasks[$i]->fk_parent    = $obj->fk_task_parent;
				$tasks[$i]->duration     = $obj->duration_effective;
				$tasks[$i]->name         = $obj->name;			// Name of project leader
				$tasks[$i]->firstname    = $obj->firstname;		// Firstname of project leader
				$i++;
			}
			$this->db->free();
		}
		else
		{
			dol_print_error($this->db);
		}

		return $tasks;
	}
	
	/**
	 * Return array of role of user for each projects
	 *
	 * @param unknown_type $user
	 * @return unknown
	 */
	function getTasksRoleForUser($user)
	{
		$tasksrole = array();

		/* Liste des taches et role sur la tache du user courant dans $tasksrole */
		$sql = "SELECT ec.element_id, ctc.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql.= " WHERE pt.rowid = ec.element_id";
		$sql.= " AND ctc.element = '".$this->element."'";
		$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
		$sql.= " AND ec.fk_socpeople = ".$user->id;
		if ($this->id) $sql.= " AND pt.fk_projet =".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$tasksrole[$row[0]] = $row[1];
				$i++;
			}
			$this->db->free();
		}
		else
		{
			dol_print_error($this->db);
		}

		return $tasksrole;
	}

}
?>