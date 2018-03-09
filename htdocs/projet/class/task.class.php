<?php
/* Copyright (C) 2008-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
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
 */
class Task extends CommonObject
{
	public $element='project_task';		//!< Id that identify managed objects
	public $table_element='projet_task';	//!< Name of table without prefix where object is stored
	public $fk_element='fk_task';
	public $picto = 'task';
	protected $childtables=array('projet_task_time');    // To test if we can delete object

	var $fk_task_parent;
	var $label;
	var $description;
	var $duration_effective;		// total of time spent on this task
	var $planned_workload;
	var $date_c;
	var $date_start;
	var $date_end;
	var $progress;
	var $fk_statut;
	var $priority;
	var $fk_user_creat;
	var $fk_user_valid;
	var $rang;

	var $timespent_min_date;
	var $timespent_max_date;
	var $timespent_total_duration;
	var $timespent_total_amount;
	var $timespent_nblinesnull;
	var $timespent_nblines;
	// For detail of lines of timespent record, there is the property ->lines in common

	// Var used to call method addTimeSpent(). Bad practice.
	var $timespent_id;
	var $timespent_duration;
	var $timespent_old_duration;
	var $timespent_date;
	var $timespent_datehour;		// More accurate start date (same than timespent_date but includes hours, minutes and seconds)
	var $timespent_withhour;		// 1 = we entered also start hours for timesheet line
	var $timespent_fk_user;
	var $timespent_note;

	var $comments = array();

	public $oldcopy;


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
		$sql.= ", ".(($this->planned_workload!='' && $this->planned_workload >= 0)?$this->planned_workload:'null');
		$sql.= ", ".(($this->progress!='' && $this->progress >= 0)?$this->progress:'null');
		$sql.= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");

			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('TASK_CREATE',$user);
				if ($result < 0) { $error++; }
				// End call triggers
			}
		}

		// Update extrafield
		if (! $error)
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
	 *  @param	int		$id					Id object
	 *  @param	int		$ref				ref object
	 *  @param	int		$loadparentdata		Also load parent data
	 *  @return int 		        		<0 if KO, 0 if not found, >0 if OK
	 */
	function fetch($id, $ref='', $loadparentdata=0)
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
		if (! empty($loadparentdata))
		{
			$sql.=", t2.ref as task_parent_ref";
			$sql.=", t2.rang as task_parent_position";
		}
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";
		if (! empty($loadparentdata)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t2 ON t.fk_task_parent = t2.rowid";
		$sql.= " WHERE ";
		if (!empty($ref)) {
			$sql.="t.ref = '".$this->db->escape($ref)."'";
		}else {
			$sql.="t.rowid = ".$id;
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num_rows = $this->db->num_rows($resql);

			if ($num_rows)
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

				if (! empty($loadparentdata))
				{
					$this->task_parent_ref      = $obj->task_parent_ref;
					$this->task_parent_position = $obj->task_parent_position;
				}

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();
			}

			$this->db->free($resql);

			if ($num_rows)
			{
				return 1;
			}else {
				return 0;
			}
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int			         	<=0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
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
		$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"'".$this->db->escape($this->id)."'").",";
		$sql.= " fk_task_parent=".(isset($this->fk_task_parent)?$this->fk_task_parent:"null").",";
		$sql.= " label=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
		$sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
		$sql.= " duration_effective=".(isset($this->duration_effective)?$this->duration_effective:"null").",";
		$sql.= " planned_workload=".((isset($this->planned_workload) && $this->planned_workload != '')?$this->planned_workload:"null").",";
		$sql.= " dateo=".($this->date_start!=''?"'".$this->db->idate($this->date_start)."'":'null').",";
		$sql.= " datee=".($this->date_end!=''?"'".$this->db->idate($this->date_end)."'":'null').",";
		$sql.= " progress=".(($this->progress!='' && $this->progress >= 0)?$this->progress:'null').",";
		$sql.= " rang=".((!empty($this->rang))?$this->rang:"0");
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('TASK_MODIFY',$user);
				if ($result < 0) { $error++; }
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

		if (! $error && (is_object($this->oldcopy) && $this->oldcopy->ref !== $this->ref))
		{
			// We remove directory
			if ($conf->projet->dir_output)
			{
				$project = new Project($this->db);
				$project->fetch($this->fk_project);

				$olddir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($this->oldcopy->ref);
				$newdir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($this->ref);
				if (file_exists($olddir))
				{
					include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
					$res=dol_move($olddir, $newdir);
					if (! $res)
					{
						$langs->load("errors");
						$this->error=$langs->trans('ErrorFailToRenameDir',$olddir,$newdir);
						$error++;
					}
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
	 *	Delete task from database
	 *
	 *	@param	User	$user        	User that delete
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{

		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error=0;

		$this->db->begin();

		if ($this->hasChildren() > 0)
		{
			dol_syslog(get_class($this)."::delete Can't delete record as it has some sub tasks", LOG_WARNING);
			$this->error='ErrorRecordHasSubTasks';
			$this->db->rollback();
			return 0;
		}

		$objectisused = $this->isObjectUsed($this->id);
		if (! empty($objectisused))
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

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_time";
			$sql.= " WHERE fk_task=".$this->id;

			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_extrafields";
			$sql.= " WHERE fk_object=".$this->id;

			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task";
			$sql.= " WHERE rowid=".$this->id;

			$resql = $this->db->query($sql);
			if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

		if (! $error)
		{
			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('TASK_DELETE',$user);
				if ($result < 0) { $error++; }
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

		dol_syslog(get_class($this)."::hasChildren", LOG_DEBUG);
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
	 *	Return nb of time spent
	 *
	 *	@return	int		<0 if KO, 0 if no children, >0 if OK
	 */
	function hasTimeSpent()
	{
		$error=0;
		$ret=0;

		$sql = "SELECT COUNT(*) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time";
		$sql.= " WHERE fk_task=".$this->id;

		dol_syslog(get_class($this)."::hasTimeSpent", LOG_DEBUG);
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
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			'withproject' or ''
	 *  @param	string	$mode			Mode 'task', 'time', 'contact', 'note', document' define page to link to.
	 * 	@param	int		$addlabel		0=Default, 1=Add label into string, >1=Add first chars into string
	 *  @param	string	$sep			Separator between ref and label if option addlabel is set
	 *  @param	int   	$notooltip		1=Disable tooltip
	 *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string					Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='',$mode='task', $addlabel=0, $sep=' - ', $notooltip=0, $save_lastsearch_value=-1)
	{
		global $conf, $langs, $user;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$result='';
		$label = '<u>' . $langs->trans("ShowTask") . '</u>';
		if (! empty($this->ref))
			$label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if (! empty($this->label))
			$label .= '<br><b>' . $langs->trans('LabelTask') . ':</b> ' . $this->label;
		if ($this->date_start || $this->date_end)
		{
			$label .= "<br>".get_date_range($this->date_start,$this->date_end,'',$langs,0);
		}

		$url = DOL_URL_ROOT.'/projet/tasks/'.$mode.'.php?id='.$this->id.($option=='withproject'?'&withproject=1':'');
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
		if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';

		$linkclose = '';
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowTask");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		$picto='projecttask';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), $picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= $this->ref;
		$result .= $linkend;
		if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

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
		$this->ref='TK01';
		$this->fk_task_parent='';
		$this->label='Specimen task TK01';
		$this->duration_effective='';
		$this->fk_user_creat='';
		$this->progress='25';
		$this->fk_statut='';
		$this->note='This is a specimen task not';
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
	 * @param	string	$filteronproj    	Filter on project ref or label
	 * @param	string	$filteronprojstatus	Filter on project status
	 * @param	string	$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 * @param	string	$filteronprojuser	Filter on user that is a contact of project
	 * @param	string	$filterontaskuser	Filter on user assigned to task
	 * @return 	array						Array of tasks
	 */
	function getTasksArray($usert=null, $userp=null, $projectid=0, $socid=0, $mode=0, $filteronproj='', $filteronprojstatus=-1, $morewherefilter='',$filteronprojuser=0,$filterontaskuser=0)
	{
		global $conf;

		$tasks = array();

		//print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT ";
		if ($filteronprojuser > 0 || $filterontaskuser > 0) $sql.= " DISTINCT";		// We may get several time the same record if user has several roles on same project/task
		$sql.= " p.rowid as projectid, p.ref, p.title as plabel, p.public, p.fk_statut as projectstatus,";
		$sql.= " t.rowid as taskid, t.ref as taskref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut as status,";
		$sql.= " t.dateo as date_start, t.datee as date_end, t.planned_workload, t.rang,";
		$sql.= " s.rowid as thirdparty_id, s.nom as thirdparty_name, s.email as thirdparty_email";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		if ($mode == 0)
		{
			if ($filteronprojuser > 0)
			{
				$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
				$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
			}
			$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
			if ($filterontaskuser > 0)
			{
				$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec2";
				$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
			}
			$sql.= " WHERE p.entity IN (".getEntity('project').")";
			$sql.= " AND t.fk_projet = p.rowid";
		}
		elseif ($mode == 1)
		{
			if ($filteronprojuser > 0)
			{
				$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
				$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
			}
			if ($filterontaskuser > 0)
			{
				$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
				$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec2";
				$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
			}
			else
			{
				$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
			}
			$sql.= " WHERE p.entity IN (".getEntity('project').")";
		}
		else return 'BadValueForParameterMode';

		if ($filteronprojuser > 0)
		{
			$sql.= " AND p.rowid = ec.element_id";
			$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
			$sql.= " AND ctc.element = 'project'";
			$sql.= " AND ec.fk_socpeople = ".$filteronprojuser;
			$sql.= " AND ec.statut = 4";
			$sql.= " AND ctc.source = 'internal'";
		}
		if ($filterontaskuser > 0)
		{
			$sql.= " AND t.fk_projet = p.rowid";
			$sql.= " AND p.rowid = ec2.element_id";
			$sql.= " AND ctc2.rowid = ec2.fk_c_type_contact";
			$sql.= " AND ctc2.element = 'project_task'";
			$sql.= " AND ec2.fk_socpeople = ".$filterontaskuser;
			$sql.= " AND ec2.statut = 4";
			$sql.= " AND ctc2.source = 'internal'";
		}
		if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
		if ($projectid) $sql.= " AND p.rowid in (".$projectid.")";
		if ($filteronproj) $sql.= natural_search(array("p.ref", "p.title"), $filteronproj);
		if ($filteronprojstatus > -1) $sql.= " AND p.fk_statut IN (".$filteronprojstatus.")";
		if ($morewherefilter) $sql.=$morewherefilter;
		$sql.= " ORDER BY p.ref, t.rang, t.dateo";

		//print $sql;exit;
		dol_syslog(get_class($this)."::getTasksArray", LOG_DEBUG);
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
					$tasks[$i] = new Task($this->db);
					$tasks[$i]->id				= $obj->taskid;
					$tasks[$i]->ref				= $obj->taskref;
					$tasks[$i]->fk_project		= $obj->projectid;
					$tasks[$i]->projectref		= $obj->ref;
					$tasks[$i]->projectlabel	= $obj->plabel;
					$tasks[$i]->projectstatus	= $obj->projectstatus;
					$tasks[$i]->label			= $obj->label;
					$tasks[$i]->description		= $obj->description;
					$tasks[$i]->fk_parent		= $obj->fk_task_parent;      // deprecated
					$tasks[$i]->fk_task_parent	= $obj->fk_task_parent;
					$tasks[$i]->duration		= $obj->duration_effective;
					$tasks[$i]->planned_workload= $obj->planned_workload;
					$tasks[$i]->progress		= $obj->progress;
					$tasks[$i]->fk_statut		= $obj->status;
					$tasks[$i]->public			= $obj->public;
					$tasks[$i]->date_start		= $this->db->jdate($obj->date_start);
					$tasks[$i]->date_end		= $this->db->jdate($obj->date_end);
					$tasks[$i]->rang	   		= $obj->rang;

					$tasks[$i]->socid           = $obj->thirdparty_id;	// For backward compatibility
					$tasks[$i]->thirdparty_id	= $obj->thirdparty_id;
					$tasks[$i]->thirdparty_name	= $obj->thirdparty_name;
					$tasks[$i]->thirdparty_email= $obj->thirdparty_email;
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
	 * Return list of roles for a user for each projects or each tasks (or a particular project or a particular task).
	 *
	 * @param	User	$userp			      Return roles on project for this internal user. If set, usert and taskid must not be defined.
	 * @param	User	$usert			      Return roles on task for this internal user. If set userp must NOT be defined. -1 means no filter.
	 * @param 	int		$projectid		      Project id list separated with , to filter on project
	 * @param 	int		$taskid			      Task id to filter on a task
	 * @param	integer	$filteronprojstatus	  Filter on project status if userp is set. Not used if userp not defined.
	 * @return 	array					      Array (projectid => 'list of roles for project' or taskid => 'list of roles for task')
	 */
	function getUserRolesForProjectsOrTasks($userp, $usert, $projectid='', $taskid=0, $filteronprojstatus=-1)
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
		if ($usert && $filteronprojstatus > -1) $sql.= " FROM ".MAIN_DB_PREFIX."projet as p, ".MAIN_DB_PREFIX."projet_task as pt";
		if ($usert && $filteronprojstatus <= -1) $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as pt";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql.= " WHERE pt.rowid = ec.element_id";
		if ($userp && $filteronprojstatus > -1) $sql.= " AND pt.fk_statut = ".$filteronprojstatus;
		if ($usert && $filteronprojstatus > -1) $sql.= " AND pt.fk_projet = p.rowid AND p.fk_statut = ".$filteronprojstatus;
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

		dol_syslog(get_class($this)."::getUserRolesForProjectsOrTasks execute request", LOG_DEBUG);
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
	 *  @param	User	$user           User object
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return	int                     <=0 if KO, >0 if OK
	 */
	function addTimeSpent($user, $notrigger=0)
	{
		global $conf,$langs;

		dol_syslog(get_class($this)."::addTimeSpent", LOG_DEBUG);

		$ret = 0;

		// Check parameters
		if (! is_object($user))
		{
			dol_print_error('',"Method addTimeSpent was called with wrong parameter user");
			return -1;
		}

		// Clean parameters
		if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);
		if (empty($this->timespent_datehour)) $this->timespent_datehour = $this->timespent_date;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time (";
		$sql.= "fk_task";
		$sql.= ", task_date";
		$sql.= ", task_datehour";
		$sql.= ", task_date_withhour";
		$sql.= ", task_duration";
		$sql.= ", fk_user";
		$sql.= ", note";
		$sql.= ") VALUES (";
		$sql.= $this->id;
		$sql.= ", '".$this->db->idate($this->timespent_date)."'";
		$sql.= ", '".$this->db->idate($this->timespent_datehour)."'";
		$sql.= ", ".(empty($this->timespent_withhour)?0:1);
		$sql.= ", ".$this->timespent_duration;
		$sql.= ", ".$this->timespent_fk_user;
		$sql.= ", ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
		$sql.= ")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$tasktime_id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task_time");
			$ret = $tasktime_id;
			$this->timespent_id = $ret;

			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('TASK_TIMESPENT_CREATE',$user);
				if ($result < 0) { $ret=-1; }
				// End call triggers
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$ret = -1;
		}

		if ($ret > 0)
		{
			// Recalculate amount of time spent for task and update denormalized field
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = (SELECT SUM(task_duration) FROM ".MAIN_DB_PREFIX."projet_task_time as ptt where ptt.fk_task = ".$this->id.")";
			if (isset($this->progress)) $sql.= ", progress = " . $this->progress;	// Do not overwrite value if not provided
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::addTimeSpent", LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				$ret = -2;
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task_time";
			$sql.= " SET thm = (SELECT thm FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".$this->timespent_fk_user.")";	// set average hour rate of user
			$sql.= " WHERE rowid = ".$tasktime_id;

			dol_syslog(get_class($this)."::addTimeSpent", LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				$ret = -2;
			}
		}

		if ($ret >0)
		{
			$this->db->commit();
		}
		else
		{
			$this->db->rollback();
		}
		return $ret;
	}

	/**
	 *  Calculate total of time spent for task
	 *
	 *  @param  User|int	$userobj			Filter on user. null or 0=No filter
	 *  @param	string		$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 *  @return array		 					Array of info for task array('min_date', 'max_date', 'total_duration', 'total_amount', 'nblines', 'nblinesnull')
	 */
	function getSummaryOfTimeSpent($userobj=null, $morewherefilter='')
	{
		global $langs;

		if (is_object($userobj)) $userid=$userobj->id;
		else $userid=$userobj;	// old method

		$id=$this->id;
		if (empty($id) && empty($userid))
		{
			dol_syslog("getSummaryOfTimeSpent called on a not loaded task without user param defined", LOG_ERR);
			return -1;
		}

		$result=array();

		$sql = "SELECT";
		$sql.= " MIN(t.task_datehour) as min_date,";
		$sql.= " MAX(t.task_datehour) as max_date,";
		$sql.= " SUM(t.task_duration) as total_duration,";
		$sql.= " SUM(t.task_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm").") as total_amount,";
		$sql.= " COUNT(t.rowid) as nblines,";
		$sql.= " SUM(".$this->db->ifsql("t.thm IS NULL", 1, 0).") as nblinesnull";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql.= " WHERE 1 = 1";
		if ($morewherefilter) $sql.=$morewherefilter;
		if ($id > 0) $sql.= " AND t.fk_task = ".$id;
		if ($userid > 0) $sql.=" AND t.fk_user = ".$userid;

		dol_syslog(get_class($this)."::getSummaryOfTimeSpent", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);

			$result['min_date'] = $obj->min_date;               // deprecated. use the ->timespent_xxx instead
			$result['max_date'] = $obj->max_date;               // deprecated. use the ->timespent_xxx instead
			$result['total_duration'] = $obj->total_duration;   // deprecated. use the ->timespent_xxx instead

			$this->timespent_min_date=$this->db->jdate($obj->min_date);
			$this->timespent_max_date=$this->db->jdate($obj->max_date);
			$this->timespent_total_duration=$obj->total_duration;
			$this->timespent_total_amount=$obj->total_amount;
			$this->timespent_nblinesnull=($obj->nblinesnull?$obj->nblinesnull:0);
			$this->timespent_nblines=($obj->nblines?$obj->nblines:0);

			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}
		return $result;
	}

	/**
	 *  Calculate quantity and value of time consumed using the thm (hourly amount value of work for user entering time)
	 *
	 *	@param		User		$fuser		Filter on a dedicated user
	 *  @param		string		$dates		Start date (ex 00:00:00)
	 *  @param		string		$datee		End date (ex 23:59:59)
	 *  @return 	array	        		Array of info for task array('amount','nbseconds','nblinesnull')
	 */
	function getSumOfAmount($fuser='', $dates='', $datee='')
	{
		global $langs;

		if (empty($id)) $id=$this->id;

		$result=array();

		$sql = "SELECT";
		$sql.= " SUM(t.task_duration) as nbseconds,";
		$sql.= " SUM(t.task_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm").") as amount, SUM(".$this->db->ifsql("t.thm IS NULL", 1, 0).") as nblinesnull";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql.= " WHERE t.fk_task = ".$id;
		if (is_object($fuser) && $fuser->id > 0)
		{
			$sql.=" AND fk_user = ".$fuser->id;
		}
		if ($dates > 0)
		{
			$datefieldname="task_datehour";
			$sql.=" AND (".$datefieldname." >= '".$this->db->idate($dates)."' OR ".$datefieldname." IS NULL)";
		}
		if ($datee > 0)
		{
			$datefieldname="task_datehour";
			$sql.=" AND (".$datefieldname." <= '".$this->db->idate($datee)."' OR ".$datefieldname." IS NULL)";
		}
		//print $sql;

		dol_syslog(get_class($this)."::getSumOfAmount", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);

			$result['amount'] = $obj->amount;
			$result['nbseconds'] = $obj->nbseconds;
			$result['nblinesnull'] = $obj->nblinesnull;

			$this->db->free($resql);
			return $result;
		}
		else
		{
			dol_print_error($this->db);
			return $result;
		}
	}

	/**
	 *  Load one record of time spent
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
		$sql.= " t.task_datehour,";
		$sql.= " t.task_date_withhour,";
		$sql.= " t.task_duration,";
		$sql.= " t.fk_user,";
		$sql.= " t.note";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql.= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetchTimeSpent", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->timespent_id			= $obj->rowid;
				$this->id					= $obj->fk_task;
				$this->timespent_date		= $this->db->jdate($obj->task_date);
				$this->timespent_datehour   = $this->db->jdate($obj->task_datehour);
				$this->timespent_withhour   = $obj->task_date_withhour;
				$this->timespent_duration	= $obj->task_duration;
				$this->timespent_fk_user	= $obj->fk_user;
				$this->timespent_note		= $obj->note;
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
	 *  Load all records of time spent
	 *
	 *  @param	User	$userobj			User object
	 *  @param	string	$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 *  @return int							<0 if KO, array of time spent if OK
	 */
	function fetchAllTimeSpent(User $userobj, $morewherefilter='')
	{
		global $langs;

		$arrayres=array();

		$sql = "SELECT";
		$sql.= " s.rowid as socid,";
		$sql.= " s.nom as thirdparty_name,";
		$sql.= " s.email as thirdparty_email,";
		$sql.= " ptt.rowid,";
		$sql.= " ptt.fk_task,";
		$sql.= " ptt.task_date,";
		$sql.= " ptt.task_datehour,";
		$sql.= " ptt.task_date_withhour,";
		$sql.= " ptt.task_duration,";
		$sql.= " ptt.fk_user,";
		$sql.= " ptt.note,";
		$sql.= " pt.rowid as task_id,";
		$sql.= " pt.ref as task_ref,";
		$sql.= " pt.label as task_label,";
		$sql.= " p.rowid as project_id,";
		$sql.= " p.ref as project_ref,";
		$sql.= " p.title as project_label,";
		$sql.= " p.public as public";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as ptt, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		$sql.= " WHERE ptt.fk_task = pt.rowid AND pt.fk_projet = p.rowid";
		$sql.= " AND ptt.fk_user = ".$userobj->id;
		$sql.= " AND pt.entity IN (".getEntity('project').")";
		if ($morewherefilter) $sql.=$morewherefilter;

		dol_syslog(get_class($this)."::fetchAllTimeSpent", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);

			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$newobj = new stdClass();

				$newobj->socid              = $obj->socid;
				$newobj->thirdparty_name    = $obj->thirdparty_name;
				$newobj->thirdparty_email   = $obj->thirdparty_email;

				$newobj->fk_project			= $obj->project_id;
				$newobj->project_ref		= $obj->project_ref;
				$newobj->project_label		= $obj->project_label;
				$newobj->public				= $obj->project_public;

				$newobj->fk_task			= $obj->task_id;
				$newobj->task_ref			= $obj->task_ref;
				$newobj->task_label			= $obj->task_label;

				$newobj->timespent_id		= $obj->rowid;
				$newobj->timespent_date		= $this->db->jdate($obj->task_date);
				$newobj->timespent_datehour	= $this->db->jdate($obj->task_datehour);
				$newobj->timespent_withhour = $obj->task_date_withhour;
				$newobj->timespent_duration = $obj->task_duration;
				$newobj->timespent_fk_user	= $obj->fk_user;
				$newobj->timespent_note		= $obj->note;

				$arrayres[] = $newobj;

				$i++;
			}

			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		return $arrayres;
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

		$ret = 0;

		// Clean parameters
		if (empty($this->timespent_datehour)) $this->timespent_datehour = $this->timespent_date;
		if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task_time SET";
		$sql.= " task_date = '".$this->db->idate($this->timespent_date)."',";
		$sql.= " task_datehour = '".$this->db->idate($this->timespent_datehour)."',";
		$sql.= " task_date_withhour = ".(empty($this->timespent_withhour)?0:1).",";
		$sql.= " task_duration = ".$this->timespent_duration.",";
		$sql.= " fk_user = ".$this->timespent_fk_user.",";
		$sql.= " note = ".(isset($this->timespent_note)?"'".$this->db->escape($this->timespent_note)."'":"null");
		$sql.= " WHERE rowid = ".$this->timespent_id;

		dol_syslog(get_class($this)."::updateTimeSpent", LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('TASK_TIMESPENT_MODIFY',$user);
				if ($result < 0)
				{
					$this->db->rollback();
					$ret = -1;
				}
				else $ret = 1;
				// End call triggers
			}
			else $ret = 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			$ret = -1;
		}

		if ($ret == 1 && ($this->timespent_old_duration != $this->timespent_duration))
		{
			$newDuration = $this->timespent_duration - $this->timespent_old_duration;

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = (SELECT SUM(task_duration) FROM ".MAIN_DB_PREFIX."projet_task_time as ptt where ptt.fk_task = ".$this->db->escape($this->id).")";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::updateTimeSpent", LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				$this->db->rollback();
				$ret = -2;
			}
		}

		if ($ret >= 0) $this->db->commit();
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

		dol_syslog(get_class($this)."::delTimeSpent", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('TASK_TIMESPENT_DELETE',$user);
				if ($result < 0) { $error++; }
				// End call triggers
			}
		}

		if (! $error)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective - ".$this->db->escape($this->timespent_duration?$this->timespent_duration:0);
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::delTimeSpent", LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				$result = 0;
			}
			else
			{
				$this->error=$this->db->lasterror();
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

		//Use 00:00 of today if time is use on task.
		$now=dol_mktime(0,0,0,dol_print_date(dol_now(),'%m'),dol_print_date(dol_now(),'%d'),dol_print_date(dol_now(),'%Y'));

		$datec = $now;

		$clone_task=new Task($this->db);
		$origin_task=new Task($this->db);

		$clone_task->context['createfromclone']='createfromclone';

		$this->db->begin();

		// Load source object
		$clone_task->fetch($fromid);
		$clone_task->fetch_optionals();
		//var_dump($clone_task->array_options);exit;

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

				$filearray=dol_dir_list($ori_task_dir,"files",0,'','(\.meta|_preview.*\.png)$','',SORT_ASC,1);
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
		}

		unset($clone_task->context['createfromclone']);

		if (! $error)
		{
			$this->db->commit();
			return $clone_task_id;
		}
		else
		{
			$this->db->rollback();
			dol_syslog(get_class($this)."::createFromClone nbError: ".$error." error : " . $this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Return status label of object
	 *
	 *	@param	integer	$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	@return	string	  			Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->fk_statut, $mode);
	}

	/**
	 *	Return status label for an object
	 *
	 *	@param	int			$statut	  	Id statut
	 *	@param	integer		$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	@return	string	  				Label
	 */
	function LibStatut($statut, $mode=0)
	{
		// list of Statut of the task
		$this->statuts[0]='Draft';
		$this->statuts[1]='ToDo';
		$this->statuts[2]='Running';
		$this->statuts[3]='Finish';
		$this->statuts[4]='Transfered';
		$this->statuts_short[0]='Draft';
		$this->statuts_short[1]='ToDo';
		$this->statuts_short[2]='Running';
		$this->statuts_short[3]='Completed';
		$this->statuts_short[4]='Transfered';

		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut1').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut3').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==3) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==4) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==5) return img_picto($langs->trans($this->statuts_short[$statut]),'statut5').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut1');
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut3');
			if ($statut==3) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
			if ($statut==4) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
			if ($statut==5) return img_picto($langs->trans($this->statuts_short[$statut]),'statut5');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut1').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut3').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==3) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==4) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==5) return img_picto($langs->trans($this->statuts_short[$statut]),'statut5').' '.$langs->trans($this->statuts[$statut]);
		}
		if ($mode == 5)
		{
			/*if ($statut==0) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut1');
			if ($statut==2) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut3');
			if ($statut==3) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
			if ($statut==4) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
			if ($statut==5) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut5');
			*/
			//return $this->progress.' %';
			return '&nbsp;';
		}
		if ($mode == 6)
		{
			/*if ($statut==0) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut1');
			if ($statut==2) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut3');
			if ($statut==3) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
			if ($statut==4) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
			if ($statut==5) return $langs->trans($this->statuts[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut5');
			*/
			//return $this->progress.' %';
			return '&nbsp;';
		}
	}

	/**
	 *  Create an intervention document on disk using template defined into PROJECT_TASK_ADDON_PDF
	 *
	 *  @param	string		$modele			force le modele a utiliser ('' par defaut)
	 *  @param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @return int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("projects");

		if (! dol_strlen($modele)) {

			$modele = 'nodefault';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->PROJECT_TASK_ADDON_PDF)) {
				$modele = $conf->global->PROJECT_TASK_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/project/task/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}


	/**
	 * Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 * @param	User	$user   Objet user
	 * @return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
	 */
	function load_board($user)
	{
		global $conf, $langs;

		$mine=0; $socid=$user->societe_id;

		$projectstatic = new Project($this->db);
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1,$socid);

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT p.rowid as projectid, p.fk_statut as projectstatus,";
		$sql.= " t.rowid as taskid, t.progress as progress, t.fk_statut as status,";
		$sql.= " t.dateo as date_start, t.datee as datee";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
		$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
		$sql.= " WHERE p.entity IN (".getEntity('project', 0).')';
		$sql.= " AND p.fk_statut = 1";
		$sql.= " AND t.fk_projet = p.rowid";
		$sql.= " AND t.progress < 100";         // tasks to do
		if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
		// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
		//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
		if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
		if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
		//print $sql;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$task_static = new Task($this->db);

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->projet->task->warning_delay/60/60/24;
			$response->label = $langs->trans("OpenedTasks");
			if ($user->rights->projet->all->lire) $response->url = DOL_URL_ROOT.'/projet/tasks/list.php?mainmenu=project';
			else $response->url = DOL_URL_ROOT.'/projet/tasks/list.php?mode=mine&amp;mainmenu=project';
			$response->img = img_object('',"task");

			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj=$this->db->fetch_object($resql))
			{
				$response->nbtodo++;

				$task_static->projectstatus = $obj->projectstatus;
				$task_static->progress = $obj->progress;
				$task_static->fk_statut = $obj->status;
				$task_static->date_end = $this->db->jdate($obj->datee);

				if ($task_static->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *      Charge indicateurs this->nb de tableau de bord
	 *
	 *      @return     int         <0 if ko, >0 if ok
	 */
	function load_state_board()
	{
		global $user;

		$mine=0; $socid=$user->societe_id;

		$projectstatic = new Project($this->db);
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,$mine,1,$socid);

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT count(p.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
		if (! $user->rights->societe->client->voir && ! $socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
		$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
		$sql.= " WHERE p.entity IN (".getEntity('project', 0).')';
		$sql.= " AND t.fk_projet = p.rowid";         // tasks to do
		if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
		// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
		//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
		if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
		if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";

		$resql=$this->db->query($sql);
		if ($resql)
		{

			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["tasks"]=$obj->nb;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 * Is the task delayed?
	 *
	 * @return bool
	 */
	public function hasDelay()
	{
		global $conf;

		if (! ($this->progress >= 0 && $this->progress < 100)) {
			return false;
		}

		$now = dol_now();

		$datetouse = ($this->date_end > 0) ? $this->date_end : ($this->datee > 0 ? $this->datee : 0);

		return ($datetouse > 0 && ($datetouse < ($now - $conf->projet->task->warning_delay)));
	}
}