<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *      \file       htdocs/projet/class/task.class.php
 *      \ingroup    project
 *      \brief      This file is a CRUD class file for Task (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * 	Class to manage tasks
 *	Initialy built by build_class_from_table on 2008-09-10 12:41
 */
class Task extends CommonObject
{
    public $element='project_task';		//!< Id that identify managed objects
    public $table_element='projet_task';	//!< Name of table without prefix where object is stored

    var $id;

	var $ref;

    var $fk_project;
    var $fk_task_parent;
    var $label;
    var $description;
    var $duration_effective;
    var $planned_workload;
    var $date_c;
    var $date_start;
    var $date_end;
    var $progress;
    var $priority;
    var $fk_user_creat;
    var $fk_user_valid;
    var $statut;
    var $note_private;
    var $note_public;
	var $rang;

    var $timespent_id;
    var $timespent_duration;
    var $timespent_old_duration;
    var $timespent_date;
    var $timespent_fk_user;
    var $timespent_note;


    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }


    /**
     *  Create into database
     *
     *  @param	User	$user        	User that create
     *  @param 	int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int 		        	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf, $langs;

        $error=0;

        // Clean parameters
        $this->label = trim($this->label);
        $this->description = trim($this->description);

        // Check parameters
        // Put here code to add control on parameters values

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task (";
        $sql.= "fk_projet";
		$sql.= ", ref";
        $sql.= ", fk_task_parent";
        $sql.= ", label";
        $sql.= ", description";
        $sql.= ", datec";
        $sql.= ", fk_user_creat";
        $sql.= ", dateo";
        $sql.= ", datee";
        $sql.= ", planned_workload";
        $sql.= ", progress";
        $sql.= ") VALUES (";
        $sql.= $this->fk_project;
		$sql.= ", ".(!empty($this->ref)?"'".$this->db->escape($this->ref)."'":'null');
        $sql.= ", ".$this->fk_task_parent;
        $sql.= ", '".$this->db->escape($this->label)."'";
        $sql.= ", '".$this->db->escape($this->description)."'";
        $sql.= ", '".$this->db->idate($this->date_c)."'";
        $sql.= ", ".$user->id;
        $sql.= ", ".($this->date_start!=''?"'".$this->db->idate($this->date_start)."'":'null');
        $sql.= ", ".($this->date_end!=''?"'".$this->db->idate($this->date_end)."'":'null');
        $sql.= ", ".($this->planned_workload!=''?$this->planned_workload:0);
        $sql.= ", ".($this->progress!=''?$this->progress:0);
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
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('TASK_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
            }
        }
        
        //Update extrafield
        if (!$error) {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
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
     *  @param	int		$id			Id object
     *  @param	int		$ref		ref object
     *  @return int 		        <0 if KO, >0 if OK
     */
    function fetch($id,$ref='')
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " t.rowid,";
		$sql.= " t.ref,";
        $sql.= " t.fk_projet,";
        $sql.= " t.fk_task_parent,";
        $sql.= " t.label,";
        $sql.= " t.description,";
        $sql.= " t.duration_effective,";
        $sql.= " t.planned_workload,";
        $sql.= " t.datec,";
        $sql.= " t.dateo,";
        $sql.= " t.datee,";
        $sql.= " t.fk_user_creat,";
        $sql.= " t.fk_user_valid,";
        $sql.= " t.fk_statut,";
        $sql.= " t.progress,";
        $sql.= " t.priority,";
        $sql.= " t.note_private,";
		$sql.= " t.note_public,";
		$sql.= " t.rang";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
        $sql.= " WHERE ";
        if (!empty($ref)) {
        	$sql.="t.ref = '".$ref."'";
        }else {
        	$sql.="t.rowid = ".$id;
        }

        dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id					= $obj->rowid;
				$this->ref					= $obj->ref;
                $this->fk_project			= $obj->fk_projet;
                $this->fk_task_parent		= $obj->fk_task_parent;
                $this->label				= $obj->label;
                $this->description			= $obj->description;
                $this->duration_effective	= $obj->duration_effective;
                $this->planned_workload		= $obj->planned_workload;
                $this->date_c				= $this->db->jdate($obj->datec);
                $this->date_start			= $this->db->jdate($obj->dateo);
                $this->date_end				= $this->db->jdate($obj->datee);
                $this->fk_user_creat		= $obj->fk_user_creat;
                $this->fk_user_valid		= $obj->fk_user_valid;
                $this->fk_statut			= $obj->fk_statut;
                $this->progress				= $obj->progress;
                $this->priority				= $obj->priority;
                $this->note_private			= $obj->note_private;
                $this->note_public			= $obj->note_public;
				$this->rang					= $obj->rang;
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
     *  Update database
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return int			         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
        global $conf, $langs;
        $error=0;

        // Clean parameters
        if (isset($this->fk_project)) $this->fk_project=trim($this->fk_project);
		if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->fk_task_parent)) $this->fk_task_parent=trim($this->fk_task_parent);
        if (isset($this->label)) $this->label=trim($this->label);
        if (isset($this->description)) $this->description=trim($this->description);
        if (isset($this->duration_effective)) $this->duration_effective=trim($this->duration_effective);
        if (isset($this->planned_workload)) $this->planned_workload=trim($this->planned_workload);

        // Check parameters
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
        $sql.= " fk_projet=".(isset($this->fk_project)?$this->fk_project:"null").",";
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"'".$this->id."'").",";
        $sql.= " fk_task_parent=".(isset($this->fk_task_parent)?$this->fk_task_parent:"null").",";
        $sql.= " label=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
        $sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
        $sql.= " duration_effective=".(isset($this->duration_effective)?$this->duration_effective:"null").",";
        $sql.= " planned_workload=".(isset($this->planned_workload)?$this->planned_workload:"0").",";
        $sql.= " dateo=".($this->date_start!=''?$this->db->idate($this->date_start):'null').",";
        $sql.= " datee=".($this->date_end!=''?$this->db->idate($this->date_end):'null').",";
        $sql.= " progress=".$this->progress.",";
        $sql.= " rang=".((!empty($this->rang))?$this->rang:"0");
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
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('TASK_MODIFY',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
            }
        }
        
        //Update extrafield
        if (!$error) {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
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
     *	Delete object in database
     *
     *	@param	User	$user        	User that delete
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *	@return	int						<0 if KO, >0 if OK
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

        if (! $error)
        {
            // Delete linked contacts
            $res = $this->delete_linked_contact();
            if ($res < 0)
            {
                $this->error='ErrorFailToDeleteLinkedContact';
                //$error++;
                $this->db->rollback();
                return 0;
            }
        }

        // Delete rang of line
        //$this->delRangOfLine($this->id, $this->element);

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
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
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
			//Delete associated link file
	        if ($conf->projet->dir_output)
	        {
	        	$projectstatic=new Project($this->db);
	        	$projectstatic->fetch($this->fk_project);

	            $dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($projectstatic->ref) . '/' . dol_sanitizeFileName($this->id);
	            dol_syslog(get_class($this)."::delete dir=".$dir, LOG_DEBUG);
	            if (file_exists($dir))
	            {
	            	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	                $res = @dol_delete_dir_recursive($dir);
	                if (!$res)
	                {
	                    $this->error = 'ErrorFailToDeleteDir';
	                    $this->db->rollback();
	                    return 0;
	                }
	            }
	        }

            $this->db->commit();

	        return 1;
        }
    }

    /**
     *	Return nb of children
     *
     *	@return	int		<0 if KO, 0 if no children, >0 if OK
     */
    function hasChildren()
    {
    	$error=0;
        $ret=0;

        $sql = "SELECT COUNT(*) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task";
        $sql.= " WHERE fk_task_parent=".$this->id;

        dol_syslog(get_class($this)."::hasChildren sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
        else
        {
            $obj=$this->db->fetch_object($resql);
            if ($obj) $ret=$obj->nb;
            $this->db->free($resql);
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
     *	Renvoie nom clicable (avec eventuellement le picto)
     *
     *	@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *	@param	int		$option			Sur quoi pointe le lien
     *	@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0,$option='')
    {
        global $langs;

        $result='';

        $lien = '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$this->id.($option=='withproject'?'&withproject=1':'').'">';
        $lienfin='</a>';

        $picto='projecttask';

        $label=$langs->trans("ShowTask").': '.$this->ref.($this->label?' - '.$this->label:'');

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
        return $result;
    }

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        $this->id=0;

        $this->fk_projet='';
		$this->ref='';
        $this->fk_task_parent='';
        $this->title='';
        $this->duration_effective='';
        $this->fk_user_creat='';
        $this->statut='';
        $this->note='';
    }

    /**
     * Return list of tasks for all projects or for one particular project
     * Sort order is on project, then on position of task, and last on start date of first level task
     *
     * @param	User	$usert				Object user to limit tasks affected to a particular user
     * @param	User	$userp				Object user to limit projects of a particular user and public projects
     * @param	int		$projectid			Project id
     * @param	int		$socid				Third party id
     * @param	int		$mode				0=Return list of tasks and their projects, 1=Return projects and tasks if exists
     * @param	string	$filteronprojref	Filter on project ref
     * @return 	array						Array of tasks
     */
    function getTasksArray($usert=0, $userp=0, $projectid=0, $socid=0, $mode=0, $filteronprojref='')
    {
        global $conf;

        $tasks = array();

        //print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

        // List of tasks (does not care about permissions. Filtering will be done later)
        $sql = "SELECT p.rowid as projectid, p.ref, p.title as plabel, p.public,";
        $sql.= " t.rowid as taskid, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress,";
        $sql.= " t.dateo as date_start, t.datee as date_end, t.planned_workload, t.ref as ref_task,t.rang";
        if ($mode == 0)
        {
            $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
            $sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
            $sql.= " WHERE t.fk_projet = p.rowid";
            $sql.= " AND p.entity = ".$conf->entity;
            if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
            if ($projectid) $sql.= " AND p.rowid in (".$projectid.")";
        }
        if ($mode == 1)
        {
            $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
            $sql.= " WHERE p.entity = ".$conf->entity;
            if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
            if ($projectid) $sql.= " AND p.rowid in (".$projectid.")";
        }
        if ($filteronprojref) $sql.= " AND p.ref LIKE '%".$filteronprojref."%'";
        $sql.= " ORDER BY p.ref, t.rang, t.dateo";

        //print $sql;
        dol_syslog(get_class($this)."::getTasksArray sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            // Loop on each record found, so each couple (project id, task id)
            while ($i < $num)
            {
                $error=0;

                $obj = $this->db->fetch_object($resql);

                if ((! $obj->public) && (is_object($userp)))	// If not public project and we ask a filter on project owned by a user
                {
                    if (! $this->getUserRolesForProjectsOrTasks($userp, 0, $obj->projectid, 0))
                    {
                        $error++;
                    }
                }
                if (is_object($usert))							// If we ask a filter on a user affected to a task
                {
                    if (! $this->getUserRolesForProjectsOrTasks(0, $usert, $obj->projectid, $obj->taskid))
                    {
                        $error++;
                    }
                }

                if (! $error)
                {
					$tasks[$i] = new Task($db);
                    $tasks[$i]->id				= $obj->taskid;
					$tasks[$i]->ref				= $obj->ref_task;
                    $tasks[$i]->fk_project		= $obj->projectid;
                    $tasks[$i]->projectref		= $obj->ref;
                    $tasks[$i]->projectlabel	= $obj->plabel;
                    $tasks[$i]->label			= $obj->label;
                    $tasks[$i]->description		= $obj->description;
                    $tasks[$i]->fk_parent		= $obj->fk_task_parent;
                    $tasks[$i]->duration		= $obj->duration_effective;
                    $tasks[$i]->planned_workload= $obj->planned_workload;
                    $tasks[$i]->progress		= $obj->progress;
                    $tasks[$i]->public			= $obj->public;
                    $tasks[$i]->date_start		= $this->db->jdate($obj->date_start);
                    $tasks[$i]->date_end		= $this->db->jdate($obj->date_end);
                    $tasks[$i]->rang	   		= $obj->rang;
                }

                $i++;
            }
            $this->db->free($resql);
        }
        else
        {
            dol_print_error($this->db);
        }

        return $tasks;
    }

    /**
     * Return list of roles for a user for each projects or each tasks (or a particular project or task).
     *
     * @param	User	$userp			Return roles on project for this internal user (task id can't be defined)
     * @param	User	$usert			Return roles on task for this internal user
     * @param 	int		$projectid		Project id list separated with , to filter on project
     * @param 	int		$taskid			Task id to filter on a task
     * @return 	array					Array (projectid => 'list of roles for project' or taskid => 'list of roles for task')
     */
    function getUserRolesForProjectsOrTasks($userp,$usert,$projectid='',$taskid=0)
    {
        $arrayroles = array();

        dol_syslog(get_class($this)."::getUserRolesForProjectsOrTasks userp=".is_object($userp)." usert=".is_object($usert)." projectid=".$projectid." taskid=".$taskid);

        // We want role of user for a projet or role of user for a task. Both are not possible.
        if (empty($userp) && empty($usert))
        {
            $this->error="CallWithWrongParameters";
            return -1;
        }
        if (! empty($userp) && ! empty($usert))
        {
            $this->error="CallWithWrongParameters";
            return -1;
        }

        /* Liste des taches et role sur les projets ou taches */
        $sql = "SELECT pt.rowid as pid, ec.element_id, ctc.code, ctc.source";
        if ($userp) $sql.= " FROM ".MAIN_DB_PREFIX."projet as pt";
        if ($usert) $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt";
        $sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
        $sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
        $sql.= " WHERE pt.rowid = ec.element_id";
        if ($userp) $sql.= " AND ctc.element = 'project'";
        if ($usert) $sql.= " AND ctc.element = 'project_task'";
        $sql.= " AND ctc.rowid = ec.fk_c_type_contact";
        if ($userp) $sql.= " AND ec.fk_socpeople = ".$userp->id;
        if ($usert) $sql.= " AND ec.fk_socpeople = ".$usert->id;
        $sql.= " AND ec.statut = 4";
        $sql.= " AND ctc.source = 'internal'";
        if ($projectid)
        {
            if ($userp) $sql.= " AND pt.rowid in (".$projectid.")";
            if ($usert) $sql.= " AND pt.fk_projet in (".$projectid.")";
        }
        if ($taskid)
        {
            if ($userp) $sql.= " ERROR SHOULD NOT HAPPENS";
            if ($usert) $sql.= " AND pt.rowid = ".$taskid;
        }
        //print $sql;

        dol_syslog(get_class($this)."::getUserRolesForProjectsOrTasks sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                if (empty($arrayroles[$obj->pid])) $arrayroles[$obj->pid] = $obj->code;
                else $arrayroles[$obj->pid].=','.$obj->code;
                $i++;
            }
            $this->db->free($resql);
        }
        else
        {
            dol_print_error($this->db);
        }

        return $arrayroles;
    }


    /**
     * 	Return list of id of contacts of task
     *
     *	@param	string	$source		Source
     *  @return array				Array of id of contacts
     */
    function getListContactId($source='internal')
    {
        $contactAlreadySelected = array();
        $tab = $this->liste_contact(-1,$source);
        //var_dump($tab);
        $num=count($tab);
        $i = 0;
        while ($i < $num)
        {
            if ($source == 'thirdparty') $contactAlreadySelected[$i] = $tab[$i]['socid'];
            else  $contactAlreadySelected[$i] = $tab[$i]['id'];
            $i++;
        }
        return $contactAlreadySelected;
    }


    /**
     *  Add time spent
     *
     *  @param	User	$user           user id
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return	void
     */
    function addTimeSpent($user, $notrigger=0)
    {
        global $conf,$langs;

		$error=0;
        $ret = 0;

        // Clean parameters
        if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time (";
        $sql.= "fk_task";
        $sql.= ", task_date";
        $sql.= ", task_duration";
        $sql.= ", fk_user";
        $sql.= ", note";
        $sql.= ") VALUES (";
        $sql.= $this->id;
        $sql.= ", '".$this->db->idate($this->timespent_date)."'";
        $sql.= ", ".$this->timespent_duration;
        $sql.= ", ".$this->timespent_fk_user;
        $sql.= ", ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
        $sql.= ")";

        dol_syslog(get_class($this)."::addTimeSpent sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql) )
        {
            $task_id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task_time");
            $ret = $task_id;

            if (! $notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('TASK_TIMESPENT_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::addTimeSpent error -1 ".$this->error,LOG_ERR);
            $ret = -1;
        }

        if ($ret >= 0)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
            $sql.= " SET duration_effective = duration_effective + '".price2num($this->timespent_duration)."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::addTimeSpent sql=".$sql, LOG_DEBUG);
            if (! $this->db->query($sql) )
            {
                $this->error=$this->db->lasterror();
                dol_syslog(get_class($this)."::addTimeSpent error -2 ".$this->error, LOG_ERR);
                $ret = -2;
            }
        }

        return $ret;
    }

    /**
     *  Load object in memory from database
     *
     *  @param	int		$id 	Id object
     *  @return int		        <0 if KO, >0 if OK
     */
    function fetchTimeSpent($id)
    {
        global $langs;

        $sql = "SELECT";
        $sql.= " t.rowid,";
        $sql.= " t.fk_task,";
        $sql.= " t.task_date,";
        $sql.= " t.task_duration,";
        $sql.= " t.fk_user,";
        $sql.= " t.note";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
        $sql.= " WHERE t.rowid = ".$id;

        dol_syslog(get_class($this)."::fetchTimeSpent sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->timespent_id			= $obj->rowid;
                $this->id					= $obj->fk_task;
                $this->timespent_date		= $obj->task_date;
                $this->timespent_duration	= $obj->task_duration;
                $this->timespent_user		= $obj->fk_user;
                $this->timespent_note		= $obj->note;
            }

            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetchTimeSpent ".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Update time spent
     *
     *  @param	User	$user           User id
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return	int						<0 if KO, >0 if OK
     */
    function updateTimeSpent($user, $notrigger=0)
    {
    	global $conf,$langs;

    	$error=0;
        $ret = 0;

        // Clean parameters
        if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

        $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task_time SET";
        $sql.= " task_date = '".$this->db->idate($this->timespent_date)."',";
        $sql.= " task_duration = ".$this->timespent_duration.",";
        $sql.= " fk_user = ".$this->timespent_fk_user.",";
        $sql.= " note = ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
        $sql.= " WHERE rowid = ".$this->timespent_id;

        dol_syslog(get_class($this)."::updateTimeSpent sql=".$sql, LOG_DEBUG);
        if ($this->db->query($sql) )
        {
            if (! $notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('TASK_TIMESPENT_MODIFY',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
            }
            $ret = 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::updateTimeSpent error -1 ".$this->error,LOG_ERR);
            $ret = -1;
        }

        if ($ret == 1 && ($this->timespent_old_duration != $this->timespent_duration))
        {
            $newDuration = $this->timespent_duration - $this->timespent_old_duration;

            $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
            $sql.= " SET duration_effective = duration_effective + '".$newDuration."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::updateTimeSpent sql=".$sql, LOG_DEBUG);
            if (! $this->db->query($sql) )
            {
                $this->error=$this->db->lasterror();
                dol_syslog(get_class($this)."::addTimeSpent error -2 ".$this->error, LOG_ERR);
                $ret = -2;
            }
        }

        return $ret;
    }

    /**
     *  Delete time spent
     *
     *  @param	User	$user        	User that delete
     *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
     *  @return	int						<0 if KO, >0 if OK
     */
    function delTimeSpent($user, $notrigger=0)
    {
        global $conf, $langs;

        $error=0;

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_time";
        $sql.= " WHERE rowid = ".$this->timespent_id;

        dol_syslog(get_class($this)."::delTimeSpent sql=".$sql);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error)
        {
            if (! $notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('TASK_TIMESPENT_DELETE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
            }
        }

        if (! $error)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
            $sql.= " SET duration_effective = duration_effective - '".$this->timespent_duration."'";
            $sql.= " WHERE rowid = ".$this->id;

            dol_syslog(get_class($this)."::delTimeSpent sql=".$sql, LOG_DEBUG);
            if ($this->db->query($sql) )
            {
                $result = 0;
            }
            else
            {
                $this->error=$this->db->lasterror();
                dol_syslog(get_class($this)."::addTimeSpent error -3 ".$this->error, LOG_ERR);
                $result = -2;
            }
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(get_class($this)."::delTimeSpent ".$errmsg, LOG_ERR);
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

     /**	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     			Id of object to clone
	 *  @param	int		$project_id				Id of project to attach clone task
	 *  @param	int		$parent_task_id			Id of task to attach clone task
	 *  @param	bool	$clone_change_dt		recalculate date of task regarding new project start date
	 *	@param	bool	$clone_affectation		clone affectation of project
	 *	@param	bool	$clone_time				clone time of project
	 *	@param	bool	$clone_file				clone file of project
	 *  @param	bool	$clone_note				clone note of project
	 *	@param	bool	$clone_prog				clone progress of project
	 * 	@return	int								New id of clone
	 */
	function createFromClone($fromid,$project_id,$parent_task_id,$clone_change_dt=false,$clone_affectation=false,$clone_time=false,$clone_file=false,$clone_note=false,$clone_prog=false)
	{
		global $user,$langs,$conf;

		$error=0;

		$now=dol_now();

		$datec = $now;

		$clone_task=new Task($this->db);
		$origin_task=new Task($this->db);

		$this->db->begin();

		// Load source object
		$clone_task->fetch($fromid);
		$origin_task->fetch($fromid);
		
		$defaultref='';
		$obj = empty($conf->global->PROJECT_TASK_ADDON)?'mod_task_simple':$conf->global->PROJECT_TASK_ADDON;
		if (! empty($conf->global->PROJECT_TASK_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.".php"))
		{
			require_once DOL_DOCUMENT_ROOT ."/core/modules/project/task/".$conf->global->PROJECT_TASK_ADDON.'.php';
			$modTask = new $obj;
			$defaultref = $modTask->getNextValue(0,$clone_task);
		}

		$ori_project_id					= $clone_task->fk_project;

		$clone_task->id					= 0;
		$clone_task->ref				= $defaultref;
        $clone_task->fk_project			= $project_id;
        $clone_task->fk_task_parent		= $parent_task_id;
        $clone_task->date_c				= $datec;
        $clone_task->planned_workload	= $origin_task->planned_workload;
		$clone_task->rang				= $origin_task->rang;

        //Manage Task Date
        if ($clone_change_dt)
        {
        	$projectstatic=new Project($this->db);
        	$projectstatic->fetch($ori_project_id);

        	//Origin project strat date
	    	$orign_project_dt_start = $projectstatic->date_start;

	    	//Calcultate new task start date with difference between origin proj start date and origin task start date
	    	if (!empty($clone_task->date_start))
	    	{
				$clone_task->date_start			= $now + $clone_task->date_start - $orign_project_dt_start;
	    	}

	    	//Calcultate new task end date with difference between origin proj end date and origin task end date
	    	if (!empty($clone_task->date_end))
	    	{
				$clone_task->date_end			= $now + $clone_task->date_end - $orign_project_dt_start;
	    	}

        }

		if (!$clone_prog)
        {
        	    $clone_task->progress=0;
        }

		// Create clone
		$result=$clone_task->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$clone_task->error;
			$error++;
		}

		// End
		if (! $error)
		{
			$this->db->commit();

			$clone_task_id=$clone_task->id;
			$clone_task_ref = $clone_task->ref;

       		//Note Update
			if (!$clone_note)
       		{
        	    $clone_task->note_private='';
    			$clone_task->note_public='';
        	}
        	else
        	{
        		$this->db->begin();
				$res=$clone_task->update_note(dol_html_entity_decode($clone_task->note_public, ENT_QUOTES),'_public');
				if ($res < 0)
				{
					$this->error.=$clone_task->error;
					$error++;
					$this->db->rollback();
				}
				else
				{
					$this->db->commit();
				}

				$this->db->begin();
				$res=$clone_task->update_note(dol_html_entity_decode($clone_task->note_private, ENT_QUOTES), '_private');
				if ($res < 0)
				{
					$this->error.=$clone_task->error;
					$error++;
					$this->db->rollback();
				}
				else
				{
					$this->db->commit();
				}
        	}

			//Duplicate file
			if ($clone_file)
			{
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				//retreive project origin ref to know folder to copy
				$projectstatic=new Project($this->db);
	        	$projectstatic->fetch($ori_project_id);
	        	$ori_project_ref=$projectstatic->ref;

	        	if ($ori_project_id!=$project_id)
	        	{
	        		$projectstatic->fetch($project_id);
	        		$clone_project_ref=$projectstatic->ref;
	        	}
	        	else
	        	{
	        		$clone_project_ref=$ori_project_ref;
	        	}

				$clone_task_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($clone_project_ref). "/" . dol_sanitizeFileName($clone_task_ref);
				$ori_task_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($ori_project_ref). "/" . dol_sanitizeFileName($fromid);

				$filearray=dol_dir_list($ori_task_dir,"files",0,'','\.meta$','',SORT_ASC,1);
				foreach($filearray as $key => $file)
				{
					if (!file_exists($clone_task_dir))
					{
						if (dol_mkdir($clone_task_dir) < 0)
						{
							$this->error.=$langs->trans('ErrorInternalErrorDetected').':dol_mkdir';
							$error++;
						}
					}

					$rescopy = dol_copy($ori_task_dir . '/' . $file['name'], $clone_task_dir . '/' . $file['name'],0,1);
					if (is_numeric($rescopy) && $rescopy < 0)
					{
						$this->error.=$langs->trans("ErrorFailToCopyFile",$ori_task_dir . '/' . $file['name'],$clone_task_dir . '/' . $file['name']);
						$error++;
					}
				}
			}

			// clone affectation
			if ($clone_affectation)
			{
				$origin_task = new Task($this->db);
				$origin_task->fetch($fromid);

				foreach(array('internal','external') as $source)
				{
					$tab = $origin_task->liste_contact(-1,$source);
					$num=count($tab);
					$i = 0;
					while ($i < $num)
					{
						$clone_task->add_contact($tab[$i]['id'], $tab[$i]['code'], $tab[$i]['source']);
						if ($clone_task->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
						{
							$langs->load("errors");
							$this->error.=$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
							$error++;
						}
						else
						{
							if ($clone_task->error!='')
							{
								$this->error.=$clone_task->error;
								$error++;
							}
						}
						$i++;
					}
				}
			}

			if($clone_time)
			{
				//TODO clone time of affectation
			}

			if (! $error)
			{
				return $clone_task_id;
			}
			else
			{
				dol_syslog(get_class($this)."::createFromClone nbError: ".$error." error : " . $this->error, LOG_ERR);
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

}
?>
