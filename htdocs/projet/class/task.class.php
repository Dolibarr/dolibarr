<?php
/* Copyright (C) 2008-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2020       Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2022       Charlene Benke		<charlene@patas-monkey.com>
 * Copyright (C) 2023      	Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Vincent de Grandpré <vincent@de-grandpre.quebec>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/projet/class/task.class.php
 *      \ingroup    project
 *      \brief      This file is a CRUD class file for Task (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/timespent.class.php';


/**
 * 	Class to manage tasks
 */
class Task extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'project_task';

	/**
	 * @var string 	Name of table without prefix where object is stored
	 */
	public $table_element = 'projet_task';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_element';

	/**
	 * @var string String with name of icon for myobject.
	 */
	public $picto = 'projecttask';

	/**
	 * @var array<string, array<string>>	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array(
		'element_time' => array('name' => 'Task', 'parent' => 'projet_task', 'parentkey' => 'fk_element', 'parenttypefield' => 'elementtype', 'parenttypevalue' => 'task')
	);

	/**
	 * @var int ID parent task
	 */
	public $fk_task_parent = 0;

	/**
	 * @var string Label of task
	 */
	public $label;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var float|'' total of time spent on this task
	 */
	public $duration_effective;

	/**
	 * @var float|'' planned workload
	 */
	public $planned_workload;

	/**
	 * @var null|int|'' date creation
	 * @see isDolTms()
	 */
	public $date_c;

	/**
	 * @var int|'' progress
	 */
	public $progress;

	/**
	 * @var null|int|'' start date
	 * @see isDolTms()
	 * @deprecated Use date_start instead
	 */
	public $dateo;

	/**
	 * @var null|int|'' start date
	 * @see isDolTms()
	 */
	public $date_start;

	/**
	 * @var null|int|'' end date
	 * @see isDolTms()
	 * @deprecated Use date_end instead
	 */
	public $datee;

	/**
	 * @var null|int|'' end date
	 * @see isDolTms()
	 */
	public $date_end;

	/**
	 * @var int ID
	 * @deprecated use status instead
	 * @see $status
	 */
	public $fk_statut;

	/**
	 * @var int ID
	 */
	public $status;

	/**
	 * @var int priority
	 */
	public $priority;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_valid;

	/**
	 * @var int rank
	 */
	public $rang;

	public $timespent_min_date;
	public $timespent_max_date;
	public $timespent_total_duration;
	public $timespent_total_amount;
	public $timespent_nblinesnull;
	public $timespent_nblines;
	// For detail of lines of timespent record, there is the property ->lines in common

	// Var used to call method addTimeSpent(). Bad practice.
	public $timespent_id;
	public $timespent_duration;
	public $timespent_old_duration;
	public $timespent_date;
	public $timespent_datehour; // More accurate start date (same than timespent_date but includes hours, minutes and seconds)
	public $timespent_withhour; // 1 = we entered also start hours for timesheet line
	public $timespent_fk_user;
	public $timespent_thm;
	public $timespent_note;
	public $timespent_fk_product;
	public $timespent_invoiceid;
	public $timespent_invoicelineid;

	public $comments = array();

	// Properties calculated from sum of llx_element_time linked to task
	/**
	 * @var int is task to be billed
	 */
	public $tobill;

	/**
	 * @var int is task billed
	 */
	public $billed;

	// Properties to store project information
	/**
	 * @var string project ref
	 */
	public $projectref;

	/**
	 * @var int project status
	 */
	public $projectstatus;

	/**
	 * @var string project label
	 */
	public $projectlabel;

	/**
	 * @var float|'' opportunity amount
	 */
	public $opp_amount;

	/**
	 * @var float|'' opportunity percent
	 */
	public $opp_percent;

	/**
	 * @var int opportunity status
	 */
	public $fk_opp_status;

	public $usage_bill_time;

	/**
	 * @var int is project public
	 */
	public $public;

	public $array_options_project;

	// Properties to store thirdparty of project information

	/**
	 * @var int ID of thirdparty
	 * @deprecated
	 * @see $thirdparty_id
	 */
	public $socid;

	/**
	 * @var int ID of thirdparty
	 */
	public $thirdparty_id;

	/**
	 * @var string name of thirdparty
	 */
	public $thirdparty_name;

	/**
	 * @var string email of thirdparty
	 */
	public $thirdparty_email;

	// store parent ref and position
	/**
	 * @var string task parent ref
	 */
	public $task_parent_ref;

	/**
	 * @var int task parent rank
	 */
	public $task_parent_position;

	/**
	 * Status indicate whether the task is billable (time is meant to be added to invoice) '1' or not '0'
	 * @var int billable
	 */
	public $billable = 1;

	/**
	 * @var float budget_amount
	 */
	public $budget_amount;

	/**
	 * @var float project_budget_amount
	 */
	public $project_budget_amount;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status (To do). Note: We also have the field progress to know the progression from 0 to 100%.
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Finished status
	 */
	const STATUS_CLOSED = 3;

	/**
	 * Transferred status
	 */
	const STATUS_TRANSFERRED = 4;

	/**
	 * status canceled
	 */
	const STATUS_CANCELED = 9;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create into database
	 *
	 *  @param	User	$user        	User that create
	 *  @param 	int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int 		        	Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		//For the date
		$now = dol_now();

		$error = 0;

		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		$this->note_public = trim($this->note_public);
		$this->note_private = trim($this->note_private);

		if (!empty($this->date_start) && !empty($this->date_end) && $this->date_start > $this->date_end) {
			$this->errors[] = $langs->trans('StartDateCannotBeAfterEndDate');
			return -1;
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task (";
		$sql .= "entity";
		$sql .= ", fk_projet";
		$sql .= ", ref";
		$sql .= ", fk_task_parent";
		$sql .= ", label";
		$sql .= ", description";
		$sql .= ", note_public";
		$sql .= ", note_private";
		$sql .= ", datec";
		$sql .= ", fk_user_creat";
		$sql .= ", dateo";
		$sql .= ", datee";
		$sql .= ", planned_workload";
		$sql .= ", progress";
		$sql .= ", budget_amount";
		$sql .= ", priority";
		$sql .= ", billable";
		$sql .= ") VALUES (";
		$sql .= (!empty($this->entity) ? (int) $this->entity : (int) $conf->entity);
		$sql .= ", ".((int) $this->fk_project);
		$sql .= ", ".(!empty($this->ref) ? "'".$this->db->escape($this->ref)."'" : 'null');
		$sql .= ", ".((int) $this->fk_task_parent);
		$sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->escape($this->description)."'";
		$sql .= ", '".$this->db->escape($this->note_public)."'";
		$sql .= ", '".$this->db->escape($this->note_private)."'";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".(isDolTms($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : 'null');
		$sql .= ", ".(isDolTms($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : 'null');
		$sql .= ", ".(($this->planned_workload != '' && $this->planned_workload >= 0) ? ((int) $this->planned_workload) : 'null');
		$sql .= ", ".(($this->progress != '' && $this->progress >= 0) ? ((int) $this->progress) : 'null');
		$sql .= ", ".(($this->budget_amount != '' && $this->budget_amount >= 0) ? ((int) $this->budget_amount) : 'null');
		$sql .= ", ".(($this->priority != '' && $this->priority >= 0) ? (int) $this->priority : 'null');
		$sql .= ", ".((int) $this->billable);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task");
			// Update extrafield
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TASK_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id					Id object
	 *  @param	string	$ref				ref object
	 *  @param	int		$loadparentdata		Also load parent data
	 *  @return int 		        		Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '', $loadparentdata = 0)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.ref,";
		$sql .= " t.entity,";
		$sql .= " t.fk_projet as fk_project,";
		$sql .= " t.fk_task_parent,";
		$sql .= " t.label,";
		$sql .= " t.description,";
		$sql .= " t.duration_effective,";
		$sql .= " t.planned_workload,";
		$sql .= " t.datec,";
		$sql .= " t.dateo as date_start,";
		$sql .= " t.datee as date_end,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_valid,";
		$sql .= " t.fk_statut as status,";
		$sql .= " t.progress,";
		$sql .= " t.budget_amount,";
		$sql .= " t.priority,";
		$sql .= " t.note_private,";
		$sql .= " t.note_public,";
		$sql .= " t.rang,";
		$sql .= " t.billable";
		if (!empty($loadparentdata)) {
			$sql .= ", t2.ref as task_parent_ref";
			$sql .= ", t2.rang as task_parent_position";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t";
		if (!empty($loadparentdata)) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t2 ON t.fk_task_parent = t2.rowid";
		}
		$sql .= " WHERE ";
		if (!empty($ref)) {
			$sql .= "entity IN (".getEntity('project').")";
			$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= "t.rowid = ".((int) $id);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql);

			if ($num_rows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->ref;
				$this->entity = $obj->entity;
				$this->fk_project = $obj->fk_project;
				$this->fk_task_parent = $obj->fk_task_parent;
				$this->label = $obj->label;
				$this->description = $obj->description;
				$this->duration_effective = $obj->duration_effective;
				$this->planned_workload = $obj->planned_workload;
				$this->date_c = $this->db->jdate($obj->datec);
				$this->date_start = $this->db->jdate($obj->date_start);
				$this->date_end = $this->db->jdate($obj->date_end);
				$this->fk_user_creat		= $obj->fk_user_creat;
				$this->fk_user_valid		= $obj->fk_user_valid;
				$this->fk_statut		    = $obj->status;
				$this->status			    = $obj->status;
				$this->progress				= $obj->progress;
				$this->budget_amount		= $obj->budget_amount;
				$this->priority				= $obj->priority;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->rang = $obj->rang;

				if (!empty($loadparentdata)) {
					$this->task_parent_ref      = $obj->task_parent_ref;
					$this->task_parent_position = $obj->task_parent_position;
				}
				$this->billable = $obj->billable;

				// Retrieve all extrafield
				$this->fetch_optionals();
			}

			$this->db->free($resql);

			if ($num_rows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int			         	Return integer <=0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->fk_project)) {
			$this->fk_project = (int) $this->fk_project;
		}
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->fk_task_parent)) {
			$this->fk_task_parent = (int) $this->fk_task_parent;
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->description)) {
			$this->description = trim($this->description);
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->duration_effective)) {
			$this->duration_effective = trim($this->duration_effective);
		}
		if (isset($this->planned_workload)) {
			$this->planned_workload = trim($this->planned_workload);
		}
		if (isset($this->budget_amount)) {
			$this->budget_amount = (float) $this->budget_amount;
		}

		if (!empty($this->date_start) && !empty($this->date_end) && $this->date_start > $this->date_end) {
			$this->errors[] = $langs->trans('StartDateCannotBeAfterEndDate');
			return -1;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET";
		$sql .= " fk_projet=".(isset($this->fk_project) ? $this->fk_project : "null").",";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "'".$this->db->escape($this->id)."'").",";
		$sql .= " fk_task_parent=".(isset($this->fk_task_parent) ? $this->fk_task_parent : "null").",";
		$sql .= " label=".(isset($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " description=".(isset($this->description) ? "'".$this->db->escape($this->description)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " duration_effective=".(isset($this->duration_effective) ? $this->duration_effective : "null").",";
		$sql .= " planned_workload=".((isset($this->planned_workload) && $this->planned_workload != '') ? $this->planned_workload : "null").",";
		$sql .= " dateo=".(isDolTms($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : 'null').",";
		$sql .= " datee=".(isDolTms($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : 'null').",";
		$sql .= " progress=".(($this->progress != '' && $this->progress >= 0) ? $this->progress : 'null').",";
		$sql .= " budget_amount=".(($this->budget_amount != '' && $this->budget_amount >= 0) ? $this->budget_amount : 'null').",";
		$sql .= " rang=".((!empty($this->rang)) ? ((int) $this->rang) : "0").",";
		$sql .= " priority=".((!empty($this->priority)) ? ((int) $this->priority) : "0").",";
		$sql .= " billable=".((int) $this->billable);
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Update extrafield
		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && getDolGlobalString('PROJECT_CLASSIFY_CLOSED_WHEN_ALL_TASKS_DONE')) {
			// Close the parent project if it is open (validated) and its tasks are 100% completed
			$project = new Project($this->db);
			if ($project->fetch($this->fk_project) > 0) {
				if ($project->statut == Project::STATUS_VALIDATED) {
					$project->getLinesArray(null); // this method does not return <= 0 if fails
					$projectCompleted = array_reduce(
						$project->lines,
						/**
						 * @param bool $allTasksCompleted
						 * @param Task $task
						 * @return bool
						 */
						static function ($allTasksCompleted, $task) {
							return $allTasksCompleted && $task->progress >= 100;
						},
						1
					);
					if ($projectCompleted) {
						if ($project->setClose($user) <= 0) {
							$error++;
						}
					}
				}
			} else {
				$error++;
			}
			if ($error) {
				$this->errors[] = $project->error;
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TASK_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error && (is_object($this->oldcopy) && $this->oldcopy->ref !== $this->ref)) {
			// We remove directory
			if ($conf->project->dir_output) {
				$project = new Project($this->db);
				$project->fetch($this->fk_project);

				$olddir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($this->oldcopy->ref);
				$newdir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($this->ref);
				if (file_exists($olddir)) {
					include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					$res = dol_move_dir($olddir, $newdir);
					if (!$res) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToRenameDir', $olddir, $newdir);
						$error++;
					}
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Delete task from database
	 *
	 *	@param	User	$user        	User that delete
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if ($this->hasChildren() > 0) {
			dol_syslog(get_class($this)."::delete Can't delete record as it has some sub tasks", LOG_WARNING);
			$this->error = 'ErrorRecordHasSubTasks';
			$this->db->rollback();
			return 0;
		}

		$objectisused = $this->isObjectUsed($this->id);
		if (!empty($objectisused)) {
			dol_syslog(get_class($this)."::delete Can't delete record as it has some child", LOG_WARNING);
			$this->error = 'ErrorRecordHasChildren';
			$this->db->rollback();
			return 0;
		}

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				$this->error = 'ErrorFailToDeleteLinkedContact';
				//$error++;
				$this->db->rollback();
				return 0;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_time";
			$sql .= " WHERE fk_element = ".((int) $this->id)." AND elementtype = 'task'";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_extrafields";
			$sql .= " WHERE fk_object = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task";
			$sql .= " WHERE rowid=".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TASK_DELETE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			//Delete associated link file
			if ($conf->project->dir_output) {
				$projectstatic = new Project($this->db);
				$projectstatic->fetch($this->fk_project);

				$dir = $conf->project->dir_output."/".dol_sanitizeFileName($projectstatic->ref).'/'.dol_sanitizeFileName($this->id);
				dol_syslog(get_class($this)."::delete dir=".$dir, LOG_DEBUG);
				if (file_exists($dir)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					$res = @dol_delete_dir_recursive($dir);
					if (!$res) {
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
	 *	@return	int		Return integer <0 if KO, 0 if no children, >0 if OK
	 */
	public function hasChildren()
	{
		$error = 0;
		$ret = 0;

		$sql = "SELECT COUNT(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task";
		$sql .= " WHERE fk_task_parent = ".((int) $this->id);

		dol_syslog(get_class($this)."::hasChildren", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		} else {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$ret = $obj->nb;
			}
			$this->db->free($resql);
		}

		if (!$error) {
			return $ret;
		} else {
			return -1;
		}
	}

	/**
	 *	Return nb of time spent
	 *
	 *	@return	int		Return integer <0 if KO, 0 if no children, >0 if OK
	 */
	public function hasTimeSpent()
	{
		$error = 0;
		$ret = 0;

		$sql = "SELECT COUNT(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time";
		$sql .= " WHERE fk_element = ".((int) $this->id);
		$sql .= " AND elementtype = 'task'";

		dol_syslog(get_class($this)."::hasTimeSpent", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		} else {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$ret = $obj->nb;
			}
			$this->db->free($resql);
		}

		if (!$error) {
			return $ret;
		} else {
			return -1;
		}
	}


	/**
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->load('projects');

		$datas = [];
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Task").'</u>';
		if (!empty($this->ref)) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (!empty($this->label)) {
			$datas['label'] = '<br><b>'.$langs->trans('LabelTask').':</b> '.$this->label;
		}
		if ($this->date_start || $this->date_end) {
			$datas['range'] = "<br>".get_date_range($this->date_start, $this->date_end, '', $langs, 0);
		}

		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually)
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
	public function getNomUrl($withpicto = 0, $option = '', $mode = 'task', $addlabel = 0, $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $action, $conf, $hookmanager, $langs;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/projet/tasks/'.$mode.'.php?id='.$this->id.($option == 'withproject' ? '&withproject=1' : '');
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowTask");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.' nowraponall"';
		} else {
			$linkclose .= ' class="nowraponall"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$picto = 'projecttask';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $picto, 'class="paddingright"', 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		if ($withpicto != 2) {
			$result .= (($addlabel && $this->label) ? $sep.dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');
		}

		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		global $user;

		$this->id = 0;

		$this->fk_project = 0;
		$this->ref = 'TK01';
		$this->fk_task_parent = 0;
		$this->label = 'Specimen task TK01';
		$this->duration_effective = '';
		$this->fk_user_creat = $user->id;
		$this->progress = 25;
		$this->status = 0;
		$this->priority = 0;
		$this->note_private = 'This is a specimen private note';
		$this->note_public = 'This is a specimen public note';
		$this->billable = 1;

		return 1;
	}

	/**
	 * Return list of tasks for all projects or for one particular project
	 * Sort order is on project, then on position of task, and last on start date of first level task
	 *
	 * @param	User	$usert					Object user to limit tasks affected to a particular user
	 * @param	User	$userp					Object user to limit projects of a particular user and public projects
	 * @param	int		$projectid				Project id
	 * @param	int		$socid					Third party id
	 * @param	int		$mode					0=Return list of tasks and their projects, 1=Return projects and tasks if exists
	 * @param	string	$filteronproj    		Filter on project ref or label
	 * @param	string	$filteronprojstatus		Filter on project status ('-1'=no filter, '0,1'=Draft+Validated only)
	 * @param	string	$morewherefilter		Add more filter into where SQL request (must start with ' AND ...')
	 * @param	int		$filteronprojuser		Filter on user that is a contact of project
	 * @param	int		$filterontaskuser		Filter on user assigned to task
	 * @param	?Extrafields	$extrafields	Show additional column from project or task
	 * @param   int     $includebilltime    	Calculate also the time to bill and billed
	 * @param   array   $search_array_options 	Array of search filters. Not Used yet.
	 * @param   int     $loadextras         	Fetch all Extrafields on each project and task
	 * @param	int		$loadRoleMode			1= will test Roles on task;  0 used in delete project action
	 * @param	string	$sortfield				Sort field
	 * @param	string	$sortorder				Sort order
	 * @return 	array|string					Array of tasks
	 */
	public function getTasksArray($usert = null, $userp = null, $projectid = 0, $socid = 0, $mode = 0, $filteronproj = '', $filteronprojstatus = '-1', $morewherefilter = '', $filteronprojuser = 0, $filterontaskuser = 0, $extrafields = null, $includebilltime = 0, $search_array_options = array(), $loadextras = 0, $loadRoleMode = 1, $sortfield = '', $sortorder = '')
	{
		global $hookmanager;

		$tasks = array();

		//print $usert.'-'.$userp.'-'.$projectid.'-'.$socid.'-'.$mode.'<br>';

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT ";
		if ($filteronprojuser > 0 || $filterontaskuser > 0) {
			$sql .= " DISTINCT"; // We may get several time the same record if user has several roles on same project/task
		}
		$sql .= " p.rowid as projectid, p.ref, p.title as plabel, p.public, p.fk_statut as projectstatus, p.usage_bill_time,";
		$sql .= " t.rowid as taskid, t.ref as taskref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut as status,";
		$sql .= " t.dateo as date_start, t.datee as date_end, t.planned_workload, t.rang, t.priority,";
		$sql .= " t.budget_amount, t.billable,";
		$sql .= " t.note_public, t.note_private,";
		$sql .= " s.rowid as thirdparty_id, s.nom as thirdparty_name, s.email as thirdparty_email,";
		$sql .= " p.fk_opp_status, p.opp_amount, p.opp_percent, p.budget_amount as project_budget_amount";
		if ($loadextras) {	// TODO Replace this with a fetch_optionnal() on the project after the fetch_object of line.
			if (!empty($extrafields->attributes['projet']['label'])) {
				foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
					$sql .= ($extrafields->attributes['projet']['type'][$key] != 'separate' ? ",efp.".$key." as options_".$key : '');
				}
			}
			if (!empty($extrafields->attributes['projet_task']['label'])) {
				foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
					$sql .= ($extrafields->attributes['projet_task']['type'][$key] != 'separate' ? ",efpt.".$key." as options_".$key : '');
				}
			}
		}
		if ($includebilltime) {
			$sql .= ", SUM(tt.element_duration * ".$this->db->ifsql("invoice_id IS NULL", "1", "0").") as tobill, SUM(tt.element_duration * ".$this->db->ifsql("invoice_id IS NULL", "0", "1").") as billed";
		}

		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		if ($loadextras) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as efp ON (p.rowid = efp.fk_object)";
		}

		if ($mode == 0) {
			if ($filteronprojuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
			}
			$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
			if ($loadextras) {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as efpt ON (t.rowid = efpt.fk_object)";
			}
			if ($includebilltime) {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as tt ON (tt.fk_element = t.rowid AND tt.elementtype='task')";
			}
			if ($filterontaskuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
			}
			$sql .= " WHERE p.entity IN (".getEntity('project').")";
			$sql .= " AND t.fk_projet = p.rowid";
		} elseif ($mode == 1) {
			if ($filteronprojuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
			}
			if ($filterontaskuser > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
				if ($includebilltime) {
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as tt ON (tt.fk_element = t.rowid AND tt.elementtype='task')";
				}
				$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec2";
				$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc2";
			} else {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t on t.fk_projet = p.rowid";
				if ($includebilltime) {
					$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_time as tt ON (tt.fk_element = t.rowid AND tt.elementtype = 'task')";
				}
			}
			$sql .= " WHERE p.entity IN (".getEntity('project').")";
		} else {
			return 'BadValueForParameterMode';
		}

		if ($filteronprojuser > 0) {
			$sql .= " AND p.rowid = ec.element_id";
			$sql .= " AND ctc.rowid = ec.fk_c_type_contact";
			$sql .= " AND ctc.element = 'project'";
			$sql .= " AND ec.fk_socpeople = ".((int) $filteronprojuser);
			$sql .= " AND ec.statut = 4";
			$sql .= " AND ctc.source = 'internal'";
		}
		if ($filterontaskuser > 0) {
			$sql .= " AND t.fk_projet = p.rowid";
			$sql .= " AND p.rowid = ec2.element_id";
			$sql .= " AND ctc2.rowid = ec2.fk_c_type_contact";
			$sql .= " AND ctc2.element = 'project_task'";
			$sql .= " AND ec2.fk_socpeople = ".((int) $filterontaskuser);
			$sql .= " AND ec2.statut = 4";
			$sql .= " AND ctc2.source = 'internal'";
		}
		if ($socid) {
			$sql .= " AND p.fk_soc = ".((int) $socid);
		}
		if ($projectid) {
			$sql .= " AND p.rowid IN (".$this->db->sanitize($projectid).")";
		}
		if ($filteronproj) {
			$sql .= natural_search(array("p.ref", "p.title"), $filteronproj);
		}
		if ($filteronprojstatus && (int) $filteronprojstatus != '-1') {
			$sql .= " AND p.fk_statut IN (".$this->db->sanitize($filteronprojstatus).")";
		}
		if ($morewherefilter) {
			$sql .= $morewherefilter;
		}

		// Add where from extra fields
		$extrafieldsobjectkey = 'projet_task';
		$extrafieldsobjectprefix = 'efpt.';
		global $db, $conf; // needed for extrafields_list_search_sql.tpl
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		if ($includebilltime) {
			$sql .= " GROUP BY p.rowid, p.ref, p.title, p.public, p.fk_statut, p.usage_bill_time,";
			$sql .= " t.datec, t.dateo, t.datee, t.tms,";
			$sql .= " t.rowid, t.ref, t.label, t.description, t.fk_task_parent, t.duration_effective, t.progress, t.fk_statut,";
			$sql .= " t.dateo, t.datee, t.planned_workload, t.rang, t.priority,";
			$sql .= " t.budget_amount, t.billable,";
			$sql .= " t.note_public, t.note_private,";
			$sql .= " s.rowid, s.nom, s.email,";
			$sql .= " p.fk_opp_status, p.opp_amount, p.opp_percent, p.budget_amount";
			if ($loadextras) {
				if (!empty($extrafields->attributes['projet']['label'])) {
					foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
						$sql .= ($extrafields->attributes['projet']['type'][$key] != 'separate' ? ",efp.".$key : '');
					}
				}
				if (!empty($extrafields->attributes['projet_task']['label'])) {
					foreach ($extrafields->attributes['projet_task']['label'] as $key => $val) {
						$sql .= ($extrafields->attributes['projet_task']['type'][$key] != 'separate' ? ",efpt.".$key : '');
					}
				}
			}
		}

		if ($sortfield && $sortorder) {
			$sql .= $this->db->order($sortfield, $sortorder);
		} else {
			$sql .= " ORDER BY p.ref, t.rang, t.dateo";
		}

		//print $sql;exit;
		dol_syslog(get_class($this)."::getTasksArray", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num) {
				$error = 0;

				$obj = $this->db->fetch_object($resql);

				if ($loadRoleMode) {
					if ((!$obj->public) && (is_object($userp))) {    // If not public project and we ask a filter on project owned by a user
						if (!$this->getUserRolesForProjectsOrTasks($userp, null, $obj->projectid, 0)) {
							$error++;
						}
					}
					if (is_object($usert)) {                            // If we ask a filter on a user affected to a task
						if (!$this->getUserRolesForProjectsOrTasks(null, $usert, $obj->projectid, $obj->taskid)) {
							$error++;
						}
					}
				}

				if (!$error) {
					$tasks[$i] = new Task($this->db);
					$tasks[$i]->id = $obj->taskid;
					$tasks[$i]->ref = $obj->taskref;
					$tasks[$i]->fk_project = $obj->projectid;

					// Data from project
					$tasks[$i]->projectref = $obj->ref;
					$tasks[$i]->projectlabel = $obj->plabel;
					$tasks[$i]->projectstatus = $obj->projectstatus;
					$tasks[$i]->fk_opp_status = $obj->fk_opp_status;
					$tasks[$i]->opp_amount = $obj->opp_amount;
					$tasks[$i]->opp_percent = $obj->opp_percent;
					$tasks[$i]->budget_amount = $obj->budget_amount;
					$tasks[$i]->project_budget_amount = $obj->project_budget_amount;
					$tasks[$i]->usage_bill_time = $obj->usage_bill_time;

					$tasks[$i]->label = $obj->label;
					$tasks[$i]->description = $obj->description;

					$tasks[$i]->fk_task_parent = $obj->fk_task_parent;
					$tasks[$i]->note_public = $obj->note_public;
					$tasks[$i]->note_private = $obj->note_private;
					$tasks[$i]->duration_effective = $obj->duration_effective;
					$tasks[$i]->planned_workload = $obj->planned_workload;

					if ($includebilltime) {
						// Data summed from element_time linked to task
						$tasks[$i]->tobill = $obj->tobill;
						$tasks[$i]->billed = $obj->billed;
					}

					$tasks[$i]->progress		= $obj->progress;
					$tasks[$i]->fk_statut		= $obj->status;
					$tasks[$i]->status 		    = $obj->status;
					$tasks[$i]->public = $obj->public;
					$tasks[$i]->date_start = $this->db->jdate($obj->date_start);
					$tasks[$i]->date_end		= $this->db->jdate($obj->date_end);
					$tasks[$i]->rang	   		= $obj->rang;
					$tasks[$i]->priority   		= $obj->priority;

					$tasks[$i]->socid           = $obj->thirdparty_id; // For backward compatibility
					$tasks[$i]->thirdparty_id = $obj->thirdparty_id;
					$tasks[$i]->thirdparty_name	= $obj->thirdparty_name;
					$tasks[$i]->thirdparty_email = $obj->thirdparty_email;

					$tasks[$i]->billable = $obj->billable;

					if ($loadextras) {
						if (!empty($extrafields->attributes['projet']['label'])) {
							foreach ($extrafields->attributes['projet']['label'] as $key => $val) {
								if ($extrafields->attributes['projet']['type'][$key] != 'separate') {
									$tmpvar = 'options_'.$key;
									$tasks[$i]->array_options_project['options_'.$key] = $obj->$tmpvar;
								}
							}
						}
					}

					if ($loadextras) {
						$tasks[$i]->fetch_optionals();
					}
				}

				$i++;
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}

		return $tasks;
	}

	/**
	 * Return list of roles for a user for each projects or each tasks (or a particular project or a particular task).
	 *
	 * @param	User|null	$userp			      Return roles on project for this internal user. If set, usert and taskid must not be defined.
	 * @param	User|null	$usert			      Return roles on task for this internal user. If set userp must NOT be defined. -1 means no filter.
	 * @param 	string		$projectid		      Project id list separated with , to filter on project
	 * @param 	int			$taskid			      Task id to filter on a task
	 * @param	integer		$filteronprojstatus	  Filter on project status if userp is set. Not used if userp not defined.
	 * @return 	array|int					      Array (projectid => 'list of roles for project' or taskid => 'list of roles for task')
	 */
	public function getUserRolesForProjectsOrTasks($userp, $usert, $projectid = '', $taskid = 0, $filteronprojstatus = -1)
	{
		$arrayroles = array();

		dol_syslog(get_class($this)."::getUserRolesForProjectsOrTasks userp=".json_encode(is_object($userp))." usert=".json_encode(is_object($usert))." projectid=".$projectid." taskid=".$taskid);

		// We want role of user for a projet or role of user for a task. Both are not possible.
		if (empty($userp) && empty($usert)) {
			$this->error = "CallWithWrongParameters";
			return -1;
		}
		if (!empty($userp) && !empty($usert)) {
			$this->error = "CallWithWrongParameters";
			return -1;
		}

		/* Liste des taches et role sur les projects ou taches */
		$sql = "SELECT ";
		if ($userp) {
			$sql .= " p.rowid as pid,";
		} else {
			$sql .= " pt.rowid as pid,";
		}
		$sql .= " ec.element_id, ctc.code, ctc.source";
		if ($userp) {
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		}
		if ($usert && $filteronprojstatus > -1) {
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p, ".MAIN_DB_PREFIX."projet_task as pt";
		}
		if ($usert && $filteronprojstatus <= -1) {
			$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as pt";
		}
		$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql .= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		if ($userp) {
			$sql .= " WHERE p.rowid = ec.element_id";
		} else {
			$sql .= " WHERE pt.rowid = ec.element_id";
		}
		if ($userp && $filteronprojstatus > -1) {
			$sql .= " AND p.fk_statut = ".((int) $filteronprojstatus);
		}
		if ($usert && $filteronprojstatus > -1) {
			$sql .= " AND pt.fk_projet = p.rowid AND p.fk_statut = ".((int) $filteronprojstatus);
		}
		if ($userp) {
			$sql .= " AND ctc.element = 'project'";
		}
		if ($usert) {
			$sql .= " AND ctc.element = 'project_task'";
		}
		$sql .= " AND ctc.rowid = ec.fk_c_type_contact";
		if ($userp) {
			$sql .= " AND ec.fk_socpeople = ".((int) $userp->id);
		}
		if ($usert) {
			$sql .= " AND ec.fk_socpeople = ".((int) $usert->id);
		}
		$sql .= " AND ec.statut = 4";
		$sql .= " AND ctc.source = 'internal'";
		if ($projectid) {
			if ($userp) {
				$sql .= " AND p.rowid IN (".$this->db->sanitize($projectid).")";
			}
			if ($usert) {
				$sql .= " AND pt.fk_projet IN (".$this->db->sanitize($projectid).")";
			}
		}
		if ($taskid) {
			if ($userp) {
				$sql .= " ERROR SHOULD NOT HAPPENS";
			}
			if ($usert) {
				$sql .= " AND pt.rowid = ".((int) $taskid);
			}
		}
		//print $sql;

		dol_syslog(get_class($this)."::getUserRolesForProjectsOrTasks execute request", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if (empty($arrayroles[$obj->pid])) {
					$arrayroles[$obj->pid] = $obj->code;
				} else {
					$arrayroles[$obj->pid] .= ','.$obj->code;
				}
				$i++;
			}
			$this->db->free($resql);
		} else {
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
	public function getListContactId($source = 'internal')
	{
		$contactAlreadySelected = array();
		$tab = $this->liste_contact(-1, $source);
		//var_dump($tab);
		$num = count($tab);
		$i = 0;
		while ($i < $num) {
			if ($source == 'thirdparty') {
				$contactAlreadySelected[$i] = $tab[$i]['socid'];
			} else {
				$contactAlreadySelected[$i] = $tab[$i]['id'];
			}
			$i++;
		}
		return $contactAlreadySelected;
	}

	/**
	 * Merge contact of tasks
	 *
	 * @param 	int 	$origin_id 	Old task id
	 * @param 	int 	$dest_id 	New task id
	 * @return 	bool
	 */
	public function mergeContactTask($origin_id, $dest_id)
	{
		$error = 0;
		$origintask = new Task($this->db);
		$result = $origintask->fetch($origin_id);
		if ($result <= 0) {
			return false;
		}

		//Get list of origin contacts
		$arraycontactorigin = array_merge($origintask->liste_contact(-1, 'internal'), $origintask->liste_contact(-1, 'external'));
		if (is_array($arraycontactorigin)) {
			foreach ($arraycontactorigin as $key => $contact) {
				$result = $this->add_contact($contact["id"], $contact["fk_c_type_contact"], $contact["source"]);
				if ($result < 0) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * Merge time spent of tasks
	 *
	 * @param 	int 	$origin_id 	Old task id
	 * @param 	int 	$dest_id 	New task id
	 * @return 	bool
	 */
	public function mergeTimeSpentTask($origin_id, $dest_id)
	{
		$ret = true;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."element_time as et";
		$sql .= " SET et.fk_element = ".((int) $dest_id);
		$sql .= " WHERE et.elementtype = 'task'";
		$sql .= " AND et.fk_element = ".((int) $origin_id);

		dol_syslog(get_class($this)."::mergeTimeSpentTask", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$ret = false;
		}

		if ($ret) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql .= " SET duration_effective = (SELECT SUM(element_duration) FROM ".MAIN_DB_PREFIX."element_time as ptt where ptt.elementtype = 'task' AND ptt.fk_element = ".((int) $dest_id).")";
			$sql .= " WHERE rowid = ".((int) $dest_id);

			dol_syslog(get_class($this)."::mergeTimeSpentTask update project_task", LOG_DEBUG);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$ret = false;
			}
		}

		if ($ret == true) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}
		return $ret;
	}

	/**
	 *  Add time spent
	 *
	 *  @param	User	$user           User object
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return	int                     Return integer <=0 if KO, >0 if OK
	 */
	public function addTimeSpent($user, $notrigger = 0)
	{
		global $langs;

		dol_syslog(get_class($this)."::addTimeSpent", LOG_DEBUG);

		$ret = 0;
		$now = dol_now();

		// Check parameters
		if (!is_object($user)) {
			dol_print_error(null, "Method addTimeSpent was called with wrong parameter user");
			return -1;
		}

		// Clean parameters
		if (isset($this->timespent_note)) {
			$this->timespent_note = trim($this->timespent_note);
		}
		if (empty($this->timespent_datehour) || ($this->timespent_date != $this->timespent_datehour)) {
			$this->timespent_datehour = $this->timespent_date;
		}

		if (getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$restrictBefore = dol_time_plus_duree(dol_now(), - getDolGlobalInt('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'), 'm');

			if ($this->timespent_date < $restrictBefore) {
				$this->error = $langs->trans('TimeRecordingRestrictedToNMonthsBack', getDolGlobalString('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'));
				$this->errors[] = $this->error;
				return -1;
			}
		}

		$this->db->begin();

		$timespent = new TimeSpent($this->db);
		$timespent->fk_element = $this->id;
		$timespent->elementtype = 'task';
		$timespent->element_date = $this->timespent_date;
		$timespent->element_datehour = $this->timespent_datehour;
		$timespent->element_date_withhour = $this->timespent_withhour;
		$timespent->element_duration = $this->timespent_duration;
		$timespent->fk_user = $this->timespent_fk_user;
		$timespent->fk_product = $this->timespent_fk_product;
		$timespent->note = $this->timespent_note;
		$timespent->datec = $this->db->idate($now);

		$result = $timespent->create($user);

		if ($result > 0) {
			$ret = $result;
			$this->timespent_id = $result;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TASK_TIMESPENT_CREATE', $user);
				if ($result < 0) {
					$ret = -1;
				}
				// End call triggers
			}
		} else {
			$this->error = $this->db->lasterror();
			$ret = -1;
		}

		if ($ret > 0) {
			// Recalculate amount of time spent for task and update denormalized field
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql .= " SET duration_effective = (SELECT SUM(element_duration) FROM ".MAIN_DB_PREFIX."element_time as ptt where ptt.elementtype = 'task' AND ptt.fk_element = ".((int) $this->id).")";
			if (isset($this->progress)) {
				$sql .= ", progress = ".((float) $this->progress); // Do not overwrite value if not provided
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::addTimeSpent", LOG_DEBUG);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$ret = -2;
			}

			// Update hourly rate of this time spent entry
			$resql_thm_user = $this->db->query("SELECT thm FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . ((int) $timespent->fk_user));
			if (!empty($resql_thm_user)) {
				$obj_thm_user = $this->db->fetch_object($resql_thm_user);
				$timespent->thm = $obj_thm_user->thm;
			}
			$res_update = $timespent->update($user);

			dol_syslog(get_class($this)."::addTimeSpent", LOG_DEBUG);
			if ($res_update <= 0) {
				$this->error = $this->db->lasterror();
				$ret = -2;
			}
		}

		if ($ret > 0) {
			$this->db->commit();
		} else {
			$this->db->rollback();
		}
		return $ret;
	}

	/**
	 *  Fetch records of time spent of this task
	 *
	 *  @param	string	$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 *  @return int							Return integer <0 if KO, array of time spent if OK
	 */
	public function fetchTimeSpentOnTask($morewherefilter = '')
	{
		$arrayres = array();

		$sql = "SELECT";
		$sql .= " s.rowid as socid,";
		$sql .= " s.nom as thirdparty_name,";
		$sql .= " s.email as thirdparty_email,";
		$sql .= " ptt.rowid,";
		$sql .= " ptt.ref_ext,";
		$sql .= " ptt.fk_element as fk_task,";
		$sql .= " ptt.element_date as task_date,";
		$sql .= " ptt.element_datehour as task_datehour,";
		$sql .= " ptt.element_date_withhour as task_date_withhour,";
		$sql .= " ptt.element_duration as task_duration,";
		$sql .= " ptt.fk_user,";
		$sql .= " ptt.note,";
		$sql .= " ptt.thm,";
		$sql .= " pt.rowid as task_id,";
		$sql .= " pt.ref as task_ref,";
		$sql .= " pt.label as task_label,";
		$sql .= " p.rowid as project_id,";
		$sql .= " p.ref as project_ref,";
		$sql .= " p.title as project_label,";
		$sql .= " p.public as public";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as ptt, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		$sql .= " WHERE ptt.fk_element = pt.rowid AND pt.fk_projet = p.rowid";
		$sql .= " AND ptt.elementtype = 'task'";
		$sql .= " AND pt.rowid = ".((int) $this->id);
		$sql .= " AND pt.entity IN (".getEntity('project').")";
		if ($morewherefilter) {
			$sql .= $morewherefilter;
		}

		dol_syslog(get_class($this)."::fetchAllTimeSpent", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$newobj = new stdClass();

				$newobj->socid              = $obj->socid;
				$newobj->thirdparty_name    = $obj->thirdparty_name;
				$newobj->thirdparty_email   = $obj->thirdparty_email;

				$newobj->fk_project			= $obj->project_id;
				$newobj->project_ref		= $obj->project_ref;
				$newobj->project_label = $obj->project_label;
				$newobj->public				= $obj->project_public;

				$newobj->fk_task			= $obj->task_id;
				$newobj->task_ref = $obj->task_ref;
				$newobj->task_label = $obj->task_label;

				$newobj->timespent_line_id = $obj->rowid;
				$newobj->timespent_line_ref_ext = $obj->ref_ext;
				$newobj->timespent_line_date = $this->db->jdate($obj->task_date);
				$newobj->timespent_line_datehour	= $this->db->jdate($obj->task_datehour);
				$newobj->timespent_line_withhour = $obj->task_date_withhour;
				$newobj->timespent_line_duration = $obj->task_duration;
				$newobj->timespent_line_fk_user = $obj->fk_user;
				$newobj->timespent_line_thm = $obj->thm;	// hourly rate
				$newobj->timespent_line_note = $obj->note;

				$arrayres[] = $newobj;

				$i++;
			}

			$this->db->free($resql);

			$this->lines = $arrayres;
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Calculate total of time spent for task
	 *
	 *  @param  User|int	$userobj			Filter on user. null or 0=No filter
	 *  @param	string		$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 *  @return array|int	 					Array of info for task array('min_date', 'max_date', 'total_duration', 'total_amount', 'nblines', 'nblinesnull')
	 */
	public function getSummaryOfTimeSpent($userobj = null, $morewherefilter = '')
	{
		if (is_object($userobj)) {
			$userid = $userobj->id;
		} else {
			$userid = $userobj; // old method
		}

		$id = $this->id;
		if (empty($id) && empty($userid)) {
			dol_syslog("getSummaryOfTimeSpent called on a not loaded task without user param defined", LOG_ERR);
			return -1;
		}

		$result = array();

		$sql = "SELECT";
		$sql .= " MIN(t.element_datehour) as min_date,";
		$sql .= " MAX(t.element_datehour) as max_date,";
		$sql .= " SUM(t.element_duration) as total_duration,";
		$sql .= " SUM(t.element_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm").") as total_amount,";
		$sql .= " COUNT(t.rowid) as nblines,";
		$sql .= " SUM(".$this->db->ifsql("t.thm IS NULL", 1, 0).") as nblinesnull";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " WHERE t.elementtype='task'";
		if ($morewherefilter) {
			$sql .= $morewherefilter;
		}
		if ($id > 0) {
			$sql .= " AND t.fk_element = ".((int) $id);
		}
		if ($userid > 0) {
			$sql .= " AND t.fk_user = ".((int) $userid);
		}

		dol_syslog(get_class($this)."::getSummaryOfTimeSpent", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$result['min_date'] = $obj->min_date; // deprecated. use the ->timespent_xxx instead
			$result['max_date'] = $obj->max_date; // deprecated. use the ->timespent_xxx instead
			$result['total_duration'] = $obj->total_duration; // deprecated. use the ->timespent_xxx instead

			$this->timespent_min_date = $this->db->jdate($obj->min_date);
			$this->timespent_max_date = $this->db->jdate($obj->max_date);
			$this->timespent_total_duration = $obj->total_duration;
			$this->timespent_total_amount = $obj->total_amount;
			$this->timespent_nblinesnull = ($obj->nblinesnull ? $obj->nblinesnull : 0);
			$this->timespent_nblines = ($obj->nblines ? $obj->nblines : 0);

			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
		return $result;
	}

	/**
	 *  Calculate quantity and value of time consumed using the thm (hourly amount value of work for user entering time)
	 *
	 *	@param		User|string	$fuser		Filter on a dedicated user
	 *  @param		string		$dates		Start date (ex 00:00:00)
	 *  @param		string		$datee		End date (ex 23:59:59)
	 *  @return 	array	        		Array of info for task array('amount','nbseconds','nblinesnull')
	 */
	public function getSumOfAmount($fuser = '', $dates = '', $datee = '')
	{
		$id = $this->id;

		$result = array();

		$sql = "SELECT";
		$sql .= " SUM(t.element_duration) as nbseconds,";
		$sql .= " SUM(t.element_duration / 3600 * ".$this->db->ifsql("t.thm IS NULL", 0, "t.thm").") as amount, SUM(".$this->db->ifsql("t.thm IS NULL", 1, 0).") as nblinesnull";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
		$sql .= " WHERE t.elementtype='task' AND t.fk_element = ".((int) $id);
		if (is_object($fuser) && $fuser->id > 0) {
			$sql .= " AND fk_user = ".((int) $fuser->id);
		}
		if ($dates > 0) {
			$datefieldname = "element_datehour";
			$sql .= " AND (".$datefieldname." >= '".$this->db->idate($dates)."' OR ".$datefieldname." IS NULL)";
		}
		if ($datee > 0) {
			$datefieldname = "element_datehour";
			$sql .= " AND (".$datefieldname." <= '".$this->db->idate($datee)."' OR ".$datefieldname." IS NULL)";
		}
		//print $sql;

		dol_syslog(get_class($this)."::getSumOfAmount", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$result['amount'] = $obj->amount;
			$result['nbseconds'] = $obj->nbseconds;
			$result['nblinesnull'] = $obj->nblinesnull;

			$this->db->free($resql);
			return $result;
		} else {
			dol_print_error($this->db);
			return $result;
		}
	}

	/**
	 *  Load properties of timespent of a task from the time spent ID.
	 *
	 *  @param	int		$id 	Id in time spent table
	 *  @return int		        Return integer <0 if KO, >0 if OK
	 */
	public function fetchTimeSpent($id)
	{
		$timespent = new TimeSpent($this->db);
		$timespent->fetch($id);

		dol_syslog(get_class($this)."::fetchTimeSpent", LOG_DEBUG);

		if ($timespent->id > 0) {
			$this->timespent_id = $timespent->id;
			$this->id = $timespent->fk_element;
			$this->timespent_date = $timespent->element_date;
			$this->timespent_datehour   = $timespent->element_datehour;
			$this->timespent_withhour   = $timespent->element_date_withhour;
			$this->timespent_duration = $timespent->element_duration;
			$this->timespent_fk_user	= $timespent->fk_user;
			$this->timespent_fk_product	= $timespent->fk_product;
			$this->timespent_thm    	= $timespent->thm; // hourly rate
			$this->timespent_note = $timespent->note;

			return 1;
		}

		return 0;
	}

	/**
	 *  Load all records of time spent
	 *
	 *  @param	User		$userobj			User object
	 *  @param	string		$morewherefilter	Add more filter into where SQL request (must start with ' AND ...')
	 *  @return array|int						Return integer <0 if KO, array of time spent if OK
	 */
	public function fetchAllTimeSpent(User $userobj, $morewherefilter = '')
	{
		$arrayres = array();

		$sql = "SELECT";
		$sql .= " s.rowid as socid,";
		$sql .= " s.nom as thirdparty_name,";
		$sql .= " s.email as thirdparty_email,";
		$sql .= " ptt.rowid,";
		$sql .= " ptt.fk_element as fk_task,";
		$sql .= " ptt.element_date as task_date,";
		$sql .= " ptt.element_datehour as task_datehour,";
		$sql .= " ptt.element_date_withhour as task_date_withhour,";
		$sql .= " ptt.element_duration as task_duration,";
		$sql .= " ptt.fk_user,";
		$sql .= " ptt.note,";
		$sql .= " ptt.thm,";
		$sql .= " pt.rowid as task_id,";
		$sql .= " pt.ref as task_ref,";
		$sql .= " pt.label as task_label,";
		$sql .= " p.rowid as project_id,";
		$sql .= " p.ref as project_ref,";
		$sql .= " p.title as project_label,";
		$sql .= " p.public as public";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time as ptt, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		$sql .= " WHERE ptt.fk_element = pt.rowid AND pt.fk_projet = p.rowid";
		$sql .= " AND ptt.elementtype = 'task'";
		$sql .= " AND ptt.fk_user = ".((int) $userobj->id);
		$sql .= " AND pt.entity IN (".getEntity('project').")";
		if ($morewherefilter) {
			$sql .= $morewherefilter;
		}

		dol_syslog(get_class($this)."::fetchAllTimeSpent", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$newobj = new stdClass();

				$newobj->socid              = $obj->socid;
				$newobj->thirdparty_name    = $obj->thirdparty_name;
				$newobj->thirdparty_email   = $obj->thirdparty_email;

				$newobj->fk_project			= $obj->project_id;
				$newobj->project_ref		= $obj->project_ref;
				$newobj->project_label = $obj->project_label;
				$newobj->public				= $obj->project_public;

				$newobj->fk_task			= $obj->task_id;
				$newobj->task_ref = $obj->task_ref;
				$newobj->task_label = $obj->task_label;

				$newobj->timespent_id = $obj->rowid;
				$newobj->timespent_date = $this->db->jdate($obj->task_date);
				$newobj->timespent_datehour	= $this->db->jdate($obj->task_datehour);
				$newobj->timespent_withhour = $obj->task_date_withhour;
				$newobj->timespent_duration = $obj->task_duration;
				$newobj->timespent_fk_user = $obj->fk_user;
				$newobj->timespent_thm = $obj->thm;	// hourly rate
				$newobj->timespent_note = $obj->note;

				$arrayres[] = $newobj;

				$i++;
			}

			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}

		return $arrayres;
	}

	/**
	 *	Update time spent
	 *
	 *  @param	User	$user           User id
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function updateTimeSpent($user, $notrigger = 0)
	{
		global $conf, $langs;

		$ret = 0;

		// Check parameters
		if ($this->timespent_date == '') {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Date"));
			return -1;
		}
		if (!($this->timespent_fk_user > 0)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("User"));
			return -1;
		}

		// Clean parameters
		if (empty($this->timespent_datehour)) {
			$this->timespent_datehour = $this->timespent_date;
		}
		if (isset($this->timespent_note)) {
			$this->timespent_note = trim($this->timespent_note);
		}

		if (getDolGlobalString('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$restrictBefore = dol_time_plus_duree(dol_now(), - $conf->global->PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS, 'm');

			if ($this->timespent_date < $restrictBefore) {
				$this->error = $langs->trans('TimeRecordingRestrictedToNMonthsBack', getDolGlobalString('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'));
				$this->errors[] = $this->error;
				return -1;
			}
		}

		$this->db->begin();

		$timespent = new TimeSpent($this->db);
		$timespent->fetch($this->timespent_id);
		$timespent->element_date = $this->timespent_date;
		$timespent->element_datehour = $this->timespent_datehour;
		$timespent->element_date_withhour = $this->timespent_withhour;
		$timespent->element_duration = $this->timespent_duration;
		$timespent->fk_user = $this->timespent_fk_user;
		$timespent->fk_product = $this->timespent_fk_product;
		$timespent->note = $this->timespent_note;
		$timespent->invoice_id = $this->timespent_invoiceid;
		$timespent->invoice_line_id = $this->timespent_invoicelineid;

		dol_syslog(get_class($this)."::updateTimeSpent", LOG_DEBUG);
		if ($timespent->update($user) > 0) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TASK_TIMESPENT_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					$ret = -1;
				} else {
					$ret = 1;
				}
				// End call triggers
			} else {
				$ret = 1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			$ret = -1;
		}

		if ($ret == 1 && (($this->timespent_old_duration != $this->timespent_duration) || getDolGlobalString('TIMESPENT_ALWAYS_UPDATE_THM'))) {
			if ($this->timespent_old_duration != $this->timespent_duration) {
				// Recalculate amount of time spent for task and update denormalized field
				$sql = "UPDATE " . MAIN_DB_PREFIX . "projet_task";
				$sql .= " SET duration_effective = (SELECT SUM(element_duration) FROM " . MAIN_DB_PREFIX . "element_time as ptt where ptt.elementtype = 'task' AND ptt.fk_element = " . ((int) $this->id) . ")";
				if (isset($this->progress)) {
					$sql .= ", progress = " . ((float) $this->progress); // Do not overwrite value if not provided
				}
				$sql .= " WHERE rowid = " . ((int) $this->id);

				dol_syslog(get_class($this) . "::updateTimeSpent", LOG_DEBUG);
				if (!$this->db->query($sql)) {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					$ret = -2;
				}
			}

			// Update hourly rate of this time spent entry, but only if it was not set initially
			$res_update = 1;
			if (empty($timespent->thm) || getDolGlobalString('TIMESPENT_ALWAYS_UPDATE_THM')) {
				$resql_thm_user = $this->db->query("SELECT thm FROM " . MAIN_DB_PREFIX . "user WHERE rowid = " . ((int) $timespent->fk_user));
				if (!empty($resql_thm_user)) {
					$obj_thm_user = $this->db->fetch_object($resql_thm_user);
					$timespent->thm = $obj_thm_user->thm;
				}
				$res_update = $timespent->update($user);
			}

			dol_syslog(get_class($this)."::updateTimeSpent", LOG_DEBUG);
			if ($res_update <= 0) {
				$this->error = $this->db->lasterror();
				$ret = -2;
			}
		}

		if ($ret >= 0) {
			$this->db->commit();
		}
		return $ret;
	}

	/**
	 *  Delete time spent
	 *
	 *  @param	User	$user        	User that delete
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function delTimeSpent($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		if (getDolGlobalString('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS')) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			$restrictBefore = dol_time_plus_duree(dol_now(), - $conf->global->PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS, 'm');

			if ($this->timespent_date < $restrictBefore) {
				$this->error = $langs->trans('TimeRecordingRestrictedToNMonthsBack', getDolGlobalString('PROJECT_TIMESHEET_PREVENT_AFTER_MONTHS'));
				$this->errors[] = $this->error;
				return -1;
			}
		}

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('TASK_TIMESPENT_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$timespent = new TimeSpent($this->db);
			$timespent->fetch($this->timespent_id);

			$res_del = $timespent->delete($user);

			if ($res_del < 0) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql .= " SET duration_effective = duration_effective - ".$this->db->escape($this->timespent_duration ? $this->timespent_duration : 0);
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::delTimeSpent", LOG_DEBUG);
			if ($this->db->query($sql)) {
				$result = 0;
			} else {
				$this->error = $this->db->lasterror();
				$result = -2;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delTimeSpent ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**	Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		            User making the clone
	 *  @param	int		$fromid     			Id of object to clone
	 *  @param	int		$project_id				Id of project to attach clone task
	 *  @param	int		$parent_task_id			Id of task to attach clone task
	 *  @param	bool	$clone_change_dt		recalculate date of task regarding new project start date
	 *  @param	bool	$clone_affectation		clone affectation of project
	 *  @param	bool	$clone_time				clone time of project
	 *  @param	bool	$clone_file				clone file of project
	 *  @param	bool	$clone_note				clone note of project
	 *  @param	bool	$clone_prog				clone progress of project
	 *  @return	int								New id of clone
	 */
	public function createFromClone(User $user, $fromid, $project_id, $parent_task_id, $clone_change_dt = false, $clone_affectation = false, $clone_time = false, $clone_file = false, $clone_note = false, $clone_prog = false)
	{
		global $langs, $conf;

		$error = 0;

		//Use 00:00 of today if time is use on task.
		$now = dol_mktime(0, 0, 0, dol_print_date(dol_now(), '%m'), dol_print_date(dol_now(), '%d'), dol_print_date(dol_now(), '%Y'));

		$datec = $now;

		$clone_task = new Task($this->db);
		$origin_task = new Task($this->db);

		$clone_task->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

		// Load source object
		$clone_task->fetch($fromid);
		$clone_task->fetch_optionals();
		//var_dump($clone_task->array_options);exit;

		$origin_task->fetch($fromid);

		$defaultref = '';
		$obj = !getDolGlobalString('PROJECT_TASK_ADDON') ? 'mod_task_simple' : $conf->global->PROJECT_TASK_ADDON;
		if (getDolGlobalString('PROJECT_TASK_ADDON') && is_readable(DOL_DOCUMENT_ROOT."/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON').".php")) {
			require_once DOL_DOCUMENT_ROOT."/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON').'.php';
			$modTask = new $obj();
			$defaultref = $modTask->getNextValue(0, $clone_task);
		}

		$ori_project_id					= $clone_task->fk_project;

		$clone_task->id					= 0;
		$clone_task->ref				= $defaultref;
		$clone_task->fk_project = $project_id;
		$clone_task->fk_task_parent = $parent_task_id;
		$clone_task->date_c = $datec;
		$clone_task->planned_workload = $origin_task->planned_workload;
		$clone_task->rang = $origin_task->rang;
		$clone_task->priority = $origin_task->priority;

		//Manage Task Date
		if ($clone_change_dt) {
			$projectstatic = new Project($this->db);
			$projectstatic->fetch($ori_project_id);

			//Origin project start date
			$orign_project_dt_start = $projectstatic->date_start;

			//Calculate new task start date with difference between origin proj start date and origin task start date
			if (!empty($clone_task->date_start)) {
				$clone_task->date_start = $now + $clone_task->date_start - $orign_project_dt_start;
			}

			//Calculate new task end date with difference between origin proj end date and origin task end date
			if (!empty($clone_task->date_end)) {
				$clone_task->date_end = $now + $clone_task->date_end - $orign_project_dt_start;
			}
		}

		if (!$clone_prog) {
			$clone_task->progress = 0;
		}

		// Create clone
		$result = $clone_task->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $clone_task->error;
			$error++;
		}
		// End
		if ($error) {
			$clone_task_id = 0;  // For static tool check
		} else {
			$clone_task_id = $clone_task->id;
			$clone_task_ref = $clone_task->ref;

			//Note Update
			if (!$clone_note) {
				$clone_task->note_private = '';
				$clone_task->note_public = '';
			} else {
				$this->db->begin();
				$res = $clone_task->update_note(dol_html_entity_decode($clone_task->note_public, ENT_QUOTES | ENT_HTML5), '_public');
				if ($res < 0) {
					$this->error .= $clone_task->error;
					$error++;
					$this->db->rollback();
				} else {
					$this->db->commit();
				}

				$this->db->begin();
				$res = $clone_task->update_note(dol_html_entity_decode($clone_task->note_private, ENT_QUOTES | ENT_HTML5), '_private');
				if ($res < 0) {
					$this->error .= $clone_task->error;
					$error++;
					$this->db->rollback();
				} else {
					$this->db->commit();
				}
			}

			//Duplicate file
			if ($clone_file) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				//retrieve project origin ref to know folder to copy
				$projectstatic = new Project($this->db);
				$projectstatic->fetch($ori_project_id);
				$ori_project_ref = $projectstatic->ref;

				if ($ori_project_id != $project_id) {
					$projectstatic->fetch($project_id);
					$clone_project_ref = $projectstatic->ref;
				} else {
					$clone_project_ref = $ori_project_ref;
				}

				$clone_task_dir = $conf->project->dir_output."/".dol_sanitizeFileName($clone_project_ref)."/".dol_sanitizeFileName($clone_task_ref);
				$ori_task_dir = $conf->project->dir_output."/".dol_sanitizeFileName($ori_project_ref)."/".dol_sanitizeFileName($fromid);

				$filearray = dol_dir_list($ori_task_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', '', SORT_ASC, 1);
				foreach ($filearray as $key => $file) {
					if (!file_exists($clone_task_dir)) {
						if (dol_mkdir($clone_task_dir) < 0) {
							$this->error .= $langs->trans('ErrorInternalErrorDetected').':dol_mkdir';
							$error++;
						}
					}

					$rescopy = dol_copy($ori_task_dir.'/'.$file['name'], $clone_task_dir.'/'.$file['name'], 0, 1);
					if (is_numeric($rescopy) && $rescopy < 0) {
						$this->error .= $langs->trans("ErrorFailToCopyFile", $ori_task_dir.'/'.$file['name'], $clone_task_dir.'/'.$file['name']);
						$error++;
					}
				}
			}

			// clone affectation
			if ($clone_affectation) {
				$origin_task = new Task($this->db);
				$origin_task->fetch($fromid);

				foreach (array('internal', 'external') as $source) {
					$tab = $origin_task->liste_contact(-1, $source);
					$num = count($tab);
					$i = 0;
					while ($i < $num) {
						$clone_task->add_contact($tab[$i]['id'], $tab[$i]['code'], $tab[$i]['source']);
						if ($clone_task->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
							$langs->load("errors");
							$this->error .= $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
							$error++;
						} else {
							if ($clone_task->error != '') {
								$this->error .= $clone_task->error;
								$error++;
							}
						}
						$i++;
					}
				}
			}

			if ($clone_time) {
				//TODO clone time of affectation
			}
		}

		unset($clone_task->context['createfromclone']);

		if (!$error) {
			$this->db->commit();
			return $clone_task_id;
		} else {
			$this->db->rollback();
			dol_syslog(get_class($this)."::createFromClone nbError: ".$error." error : ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Return status label of object
	 *
	 *	@param	integer	$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	@return	string	  			Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return status label for an object
	 *
	 *  @param	int			$status	  	Id status
	 *  @param	integer		$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return	string	  				Label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		// list of Statut of the task
		$this->labelStatus[0] = 'Draft';
		$this->labelStatus[1] = 'ToDo';
		$this->labelStatus[2] = 'Running';
		$this->labelStatus[3] = 'Finish';
		$this->labelStatus[4] = 'Transfered';
		$this->labelStatusShort[0] = 'Draft';
		$this->labelStatusShort[1] = 'ToDo';
		$this->labelStatusShort[2] = 'Running';
		$this->labelStatusShort[3] = 'Completed';
		$this->labelStatusShort[4] = 'Transfered';

		if ($mode == 0) {
			return $langs->trans($this->labelStatus[$status]);
		} elseif ($mode == 1) {
			return $langs->trans($this->labelStatusShort[$status]);
		} elseif ($mode == 2) {
			if ($status == 0) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut0').' '.$langs->trans($this->labelStatusShort[$status]);
			} elseif ($status == 1) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut1').' '.$langs->trans($this->labelStatusShort[$status]);
			} elseif ($status == 2) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut3').' '.$langs->trans($this->labelStatusShort[$status]);
			} elseif ($status == 3) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut6').' '.$langs->trans($this->labelStatusShort[$status]);
			} elseif ($status == 4) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut6').' '.$langs->trans($this->labelStatusShort[$status]);
			} elseif ($status == 5) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut5').' '.$langs->trans($this->labelStatusShort[$status]);
			}
		} elseif ($mode == 3) {
			if ($status == 0) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut0');
			} elseif ($status == 1) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut1');
			} elseif ($status == 2) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut3');
			} elseif ($status == 3) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut6');
			} elseif ($status == 4) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut6');
			} elseif ($status == 5) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut5');
			}
		} elseif ($mode == 4) {
			if ($status == 0) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut0').' '.$langs->trans($this->labelStatus[$status]);
			} elseif ($status == 1) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut1').' '.$langs->trans($this->labelStatus[$status]);
			} elseif ($status == 2) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut3').' '.$langs->trans($this->labelStatus[$status]);
			} elseif ($status == 3) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut6').' '.$langs->trans($this->labelStatus[$status]);
			} elseif ($status == 4) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut6').' '.$langs->trans($this->labelStatus[$status]);
			} elseif ($status == 5) {
				return img_picto($langs->trans($this->labelStatusShort[$status]), 'statut5').' '.$langs->trans($this->labelStatus[$status]);
			}
		} elseif ($mode == 5) {
			/*if ($status==0) return $langs->trans($this->labelStatusShort[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut0');
			elseif ($status==1) return $langs->trans($this->labelStatusShort[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut1');
			elseif ($status==2) return $langs->trans($this->labelStatusShort[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut3');
			elseif ($status==3) return $langs->trans($this->labelStatusShort[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut6');
			elseif ($status==4) return $langs->trans($this->labelStatusShort[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut6');
			elseif ($status==5) return $langs->trans($this->labelStatusShort[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut5');
			*/
			//else return $this->progress.' %';
			return '&nbsp;';
		} elseif ($mode == 6) {
			/*if ($status==0) return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut0');
			elseif ($status==1) return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut1');
			elseif ($status==2) return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut3');
			elseif ($status==3) return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut6');
			elseif ($status==4) return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut6');
			elseif ($status==5) return $langs->trans($this->labelStatus[$status]).' '.img_picto($langs->trans($this->labelStatusShort[$status]),'statut5');
			*/
			//else return $this->progress.' %';
			return '&nbsp;';
		}
		return "";
	}

	/**
	 *  Create an intervention document on disk using template defined into PROJECT_TASK_ADDON_PDF
	 *
	 *  @param	string		$modele			force le modele a utiliser ('' par default)
	 *  @param	Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @return int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		$outputlangs->load("projects");

		if (!dol_strlen($modele)) {
			$modele = 'nodefault';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('PROJECT_TASK_ADDON_PDF')) {
				$modele = getDolGlobalString('PROJECT_TASK_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/project/task/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 * @param	User	$user   Object user
	 * @return WorkboardResponse|int Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		// For external user, no check is done on company because readability is managed by public status of project and assignment.
		//$socid = $user->socid;
		$socid = 0;

		$projectstatic = new Project($this->db);
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1, $socid);

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT p.rowid as projectid, p.fk_statut as projectstatus,";
		$sql .= " t.rowid as taskid, t.progress as progress, t.fk_statut as status,";
		$sql .= " t.dateo as date_start, t.datee as date_end";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		//$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
		//if (! $user->rights->societe->client->voir && ! $socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
		$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
		$sql .= " WHERE p.entity IN (".getEntity('project', 0).')';
		$sql .= " AND p.fk_statut = 1";
		$sql .= " AND t.fk_projet = p.rowid";
		$sql .= " AND (t.progress IS NULL OR t.progress < 100)"; // tasks to do
		if (!$user->hasRight('projet', 'all', 'lire')) {
			$sql .= " AND p.rowid IN (".$this->db->sanitize($projectsListId).")";
		}
		// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
		//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
		// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
		// if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id).") OR (s.rowid IS NULL))";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$task_static = new Task($this->db);

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->project->task->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("OpenedTasks");
			if ($user->hasRight("projet", "all", "lire")) {
				$response->url = DOL_URL_ROOT.'/projet/tasks/list.php?mainmenu=project';
			} else {
				$response->url = DOL_URL_ROOT.'/projet/tasks/list.php?mode=mine&amp;mainmenu=project';
			}
			$response->img = img_object('', "task");

			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				$task_static->projectstatus = $obj->projectstatus;
				$task_static->progress = $obj->progress;
				$task_static->fk_statut = $obj->status;
				$task_static->status = $obj->status;
				$task_static->date_start = $this->db->jdate($obj->date_start);
				$task_static->date_end = $this->db->jdate($obj->date_end);

				if ($task_static->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 *      Load indicators this->nb for state board
	 *
	 *      @return     int         Return integer <0 if ko, >0 if ok
	 */
	public function loadStateBoard()
	{
		global $user;

		$mine = 0;
		$socid = $user->socid;

		$projectstatic = new Project($this->db);
		$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, $mine, 1, $socid);

		// List of tasks (does not care about permissions. Filtering will be done later)
		$sql = "SELECT count(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
		}
		$sql .= ", ".MAIN_DB_PREFIX."projet_task as t";
		$sql .= " WHERE p.entity IN (".getEntity('project', 0).')';
		$sql .= " AND t.fk_projet = p.rowid"; // tasks to do
		if ($mine || !$user->hasRight('projet', 'all', 'lire')) {
			$sql .= " AND p.rowid IN (".$this->db->sanitize($projectsListId).")";
		}
		// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
		//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
		if ($socid) {
			$sql .= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
		}
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id).") OR (s.rowid IS NULL))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["tasks"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
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

		if (!($this->progress >= 0 && $this->progress < 100)) {
			return false;
		}

		$now = dol_now();

		$datetouse = ($this->date_end > 0) ? $this->date_end : ((isset($this->datee) && $this->datee > 0) ? $this->datee : 0);

		return ($datetouse > 0 && ($datetouse < ($now - $conf->project->task->warning_delay)));
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm info-box-kanban">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		//$return .= '<i class="fa fa-dol-action"></i>'; // Can be image
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['projectlink'])) {
			//$tmpproject = $arraydata['project'];
			//$return .= '<br><span class="info-box-status ">'.$tmpproject->getNomProject().'</span>';
			$return .= '<br><span class="info-box-status ">'.$arraydata['projectlink'].'</span>';
		}
		if (property_exists($this, 'budget_amount')) {
			//$return .= '<br><span class="info-box-label amount">'.$langs->trans("Budget").' : '.price($this->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
		}
		if (property_exists($this, 'duration_effective')) {
			$return .= '<br><div class="info-box-label progressinkanban paddingtop">'.getTaskProgressView($this, false, true).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *    Merge a task with another one, deleting the given task.
	 *    The task given in parameter will be removed.
	 *
	 *    @param	int     $task_origin_id		Task to merge the data from
	 *    @return	int							-1 if error
	 */
	public function mergeTask($task_origin_id)
	{
		global $langs, $hookmanager, $user, $action;

		$error = 0;
		$task_origin = new Task($this->db);		// The thirdparty that we will delete

		dol_syslog("mergeTask merge task id=".$task_origin_id." (will be deleted) into the task id=".$this->id);

		$langs->load('error');

		if (!$error && $task_origin->fetch($task_origin_id) < 1) {
			$this->error = $langs->trans('ErrorRecordNotFound');
			$error++;
		}

		if (!$error) {
			$this->db->begin();

			// Recopy some data
			$listofproperties = array(
				'label', 'description', 'duration_effective', 'planned_workload', 'datec', 'date_start',
				'date_end', 'fk_user_creat', 'fk_user_valid', 'fk_statut', 'progress', 'budget_amount',
				'priority', 'rang', 'fk_projet', 'fk_task_parent'
			);
			foreach ($listofproperties as $property) {
				if (empty($this->$property)) {
					$this->$property = $task_origin->$property;
				}
			}

			// Concat some data
			$listofproperties = array(
				'note_public', 'note_private'
			);
			foreach ($listofproperties as $property) {
				$this->$property = dol_concatdesc($this->$property, $task_origin->$property);
			}

			// Merge extrafields
			if (is_array($task_origin->array_options)) {
				foreach ($task_origin->array_options as $key => $val) {
					if (empty($this->array_options[$key])) {
						$this->array_options[$key] = $val;
					}
				}
			}

			// Update
			$result = $this->update($user);

			if ($result < 0) {
				$error++;
			}

			// Merge time spent
			if (!$error) {
				$result = $this->mergeTimeSpentTask($task_origin_id, $this->id);
				if ($result != true) {
					$error++;
				}
			}

			// Merge contacts
			if (!$error) {
				$result = $this->mergeContactTask($task_origin_id, $this->id);
				if ($result != true) {
					$error++;
				}
			}

			// External modules should update their ones too
			if (!$error) {
				$parameters = array('task_origin' => $task_origin->id, 'task_dest' => $this->id);
				$reshook = $hookmanager->executeHooks('replaceThirdparty', $parameters, $this, $action);

				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
					$error++;
				}
			}


			if (!$error) {
				$this->context = array('merge' => 1, 'mergefromid' => $task_origin->id, 'mergefromref' => $task_origin->ref);

				// Call trigger
				$result = $this->call_trigger('TASK_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				// We finally remove the old task
				if ($task_origin->delete($user) < 1) {
					$this->error = $task_origin->error;
					$this->errors = $task_origin->errors;
					$error++;
				}
			}

			if (!$error) {
				$this->db->commit();
				return 0;
			} else {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorsTaskMerge');
				$this->db->rollback();
				return -1;
			}
		}

		return -1;
	}
}
