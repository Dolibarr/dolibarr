<?php
/* Copyright (C) 2007-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2023-2024	William Mead		<william.mead@manchenumerique.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       cron/class/cronjob.class.php
 *  \ingroup    cron
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";


/**
 *	Cron Job class
 */
class Cronjob extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'cronjob';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'cronjob';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'cron';

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string Job type
	 */
	public $jobtype;

	/**
	 * @var string|int     Date for cron job create
	 */
	public $datec = '';

	/**
	 * @var string Cron Job label
	 */
	public $label;

	/**
	 * @var string Job command
	 */
	public $command;
	public $classesname;
	public $objectname;
	public $methodename;
	public $params;
	public $md5params;
	public $module_name;
	public $priority;

	/**
	 * @var string|int|null		Date for last job execution
	 */
	public $datelastrun = '';

	/**
	 * @var string|int			Date for next job execution
	 */
	public $datenextrun = '';

	/**
	 * @var string|int			Date for end job execution
	 */
	public $dateend = '';

	/**
	 * @var string|int			Date for first start job execution
	 */
	public $datestart = '';

	/**
	 * @var string|int|null		Date for last result job execution
	 */
	public $datelastresult = '';

	/**
	 * @var string			Last result from end job execution
	 */
	public $lastresult;

	/**
	 * @var string 			Last output from end job execution
	 */
	public $lastoutput;

	/**
	 * @var string 			Unit frequency of job execution ('60', '86400', 'd', 'm', ...)
	 */
	public $unitfrequency;

	/**
	 * @var int 			Frequency of job execution
	 */
	public $frequency;

	/**
	 * @var int 			Status
	 */
	public $status;

	/**
	 * @var int 			Is job running ?
	 */
	public $processing;

	/**
	 * @var int|null		The job current PID
	 */
	public $pid;

	/**
	 * @var string 			Email when an error occurs
	 */
	public $email_alert;

	/**
	 * @var int 			User ID of creation
	 */
	public $fk_user_author;

	/**
	 * @var int 			User ID of last modification
	 */
	public $fk_user_mod;

	/**
	 * @var int 			Number of run job execution
	 */
	public $nbrun;

	/**
	 * @var int 			Maximum run job execution
	 */
	public $maxrun;

	/**
	 * @var string 			Libname
	 */
	public $libname;

	/**
	 * @var string 			A test condition to know if job is visible/qualified
	 */
	public $test;

	/**
	 * @var string 			Autodelete
	 */
	public $autodelete;

	/**
	 * @var CommonObjectLine[] 			Cronjob
	 */
	public $lines;


	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;
	const STATUS_ARCHIVED = 2;
	const MAXIMUM_LENGTH_FOR_LASTOUTPUT_FIELD = 65535;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 * Create object into database
	 *
	 * @param	User	$user		User that creates
	 * @param  int		$notrigger	0=launch triggers after, 1=disable triggers
	 * @return int					if KO: <0 || if OK: Id of created object
	 */
	public function create(User $user, int $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->jobtype)) {
			$this->jobtype = trim($this->jobtype);
		}
		if (isset($this->command)) {
			$this->command = trim($this->command);
		}
		if (isset($this->classesname)) {
			$this->classesname = trim($this->classesname);
		}
		if (isset($this->objectname)) {
			$this->objectname = trim($this->objectname);
		}
		if (isset($this->methodename)) {
			$this->methodename = trim($this->methodename);
		}
		if (isset($this->params)) {
			$this->params = trim($this->params);
		}
		if (isset($this->md5params)) {
			$this->md5params = trim($this->md5params);
		}
		if (isset($this->module_name)) {
			$this->module_name = trim($this->module_name);
		}
		if (isset($this->priority)) {
			$this->priority = trim($this->priority);
		}
		if (isset($this->lastoutput)) {
			$this->lastoutput = trim($this->lastoutput);
		}
		if (isset($this->lastresult)) {
			$this->lastresult = trim($this->lastresult);
		}
		if (isset($this->unitfrequency)) {
			$this->unitfrequency = trim($this->unitfrequency);
		}
		if (isset($this->frequency)) {
			$this->frequency = (int) $this->frequency;
		}
		if (isset($this->status)) {
			$this->status = (int) $this->status;
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->nbrun)) {
			$this->nbrun = (int) $this->nbrun;
		}
		if (isset($this->maxrun)) {
			$this->maxrun = (int) $this->maxrun;
		}
		if (isset($this->libname)) {
			$this->libname = trim($this->libname);
		}
		if (isset($this->test)) {
			$this->test = trim($this->test);
		}

		// Check parameters
		// Put here code to add a control on parameters values
		if (dol_strlen($this->datenextrun) == 0) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronDtNextLaunch'));
			$error++;
		}
		if (empty($this->label)) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronLabel'));
			$error++;
		}
		if ((dol_strlen($this->datestart) != 0) && (dol_strlen($this->dateend) != 0) && ($this->dateend < $this->datestart)) {
			$this->errors[] = $langs->trans('CronErrEndDateStartDt');
			$error++;
		}
		if (empty($this->unitfrequency)) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronFrequency'));
			$error++;
		}
		if (($this->jobtype == 'command') && (empty($this->command))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronCommand'));
			$error++;
		}
		if (($this->jobtype == 'method') && (empty($this->classesname))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronClass'));
			$error++;
		}
		if (($this->jobtype == 'method' || $this->jobtype == 'function') && (empty($this->methodename))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronMethod'));
			$error++;
		}
		if (($this->jobtype == 'method') && (empty($this->objectname))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronObject'));
			$error++;
		}
		if (($this->jobtype == 'function') && (empty($this->libname))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronLib'));
			$error++;
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cronjob(";
		$sql .= "entity,";
		$sql .= "datec,";
		$sql .= "jobtype,";
		$sql .= "label,";
		$sql .= "command,";
		$sql .= "classesname,";
		$sql .= "objectname,";
		$sql .= "methodename,";
		$sql .= "params,";
		$sql .= "md5params,";
		$sql .= "module_name,";
		$sql .= "priority,";
		$sql .= "datelastrun,";
		$sql .= "datenextrun,";
		$sql .= "dateend,";
		$sql .= "datestart,";
		$sql .= "lastresult,";
		$sql .= "datelastresult,";
		$sql .= "lastoutput,";
		$sql .= "unitfrequency,";
		$sql .= "frequency,";
		$sql .= "status,";
		$sql .= "fk_user_author,";
		$sql .= "fk_user_mod,";
		$sql .= "note,";
		$sql .= "nbrun,";
		$sql .= "maxrun,";
		$sql .= "libname,";
		$sql .= "test";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->entity) ? $conf->entity : $this->db->escape($this->entity)).",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " ".(!isset($this->jobtype) ? 'NULL' : "'".$this->db->escape($this->jobtype)."'").",";
		$sql .= " ".(!isset($this->label) ? 'NULL' : "'".$this->db->escape($this->label)."'").",";
		$sql .= " ".(!isset($this->command) ? 'NULL' : "'".$this->db->escape($this->command)."'").",";
		$sql .= " ".(!isset($this->classesname) ? 'NULL' : "'".$this->db->escape($this->classesname)."'").",";
		$sql .= " ".(!isset($this->objectname) ? 'NULL' : "'".$this->db->escape($this->objectname)."'").",";
		$sql .= " ".(!isset($this->methodename) ? 'NULL' : "'".$this->db->escape($this->methodename)."'").",";
		$sql .= " ".(!isset($this->params) ? 'NULL' : "'".$this->db->escape($this->params)."'").",";
		$sql .= " ".(!isset($this->md5params) ? 'NULL' : "'".$this->db->escape($this->md5params)."'").",";
		$sql .= " ".(!isset($this->module_name) ? 'NULL' : "'".$this->db->escape($this->module_name)."'").",";
		$sql .= " ".(!isset($this->priority) ? '0' : $this->priority).",";
		$sql .= " ".(!isset($this->datelastrun) || dol_strlen($this->datelastrun) == 0 ? 'NULL' : "'".$this->db->idate($this->datelastrun)."'").",";
		$sql .= " ".(!isset($this->datenextrun) || dol_strlen($this->datenextrun) == 0 ? 'NULL' : "'".$this->db->idate($this->datenextrun)."'").",";
		$sql .= " ".(!isset($this->dateend) || dol_strlen($this->dateend) == 0 ? 'NULL' : "'".$this->db->idate($this->dateend)."'").",";
		$sql .= " ".(!isset($this->datestart) || dol_strlen($this->datestart) == 0 ? 'NULL' : "'".$this->db->idate($this->datestart)."'").",";
		$sql .= " ".(!isset($this->lastresult) ? 'NULL' : "'".$this->db->escape($this->lastresult)."'").",";
		$sql .= " ".(!isset($this->datelastresult) || dol_strlen($this->datelastresult) == 0 ? 'NULL' : "'".$this->db->idate($this->datelastresult)."'").",";
		$sql .= " ".(!isset($this->lastoutput) ? 'NULL' : "'".$this->db->escape($this->lastoutput)."'").",";
		$sql .= " ".(!isset($this->unitfrequency) ? 'NULL' : "'".$this->db->escape($this->unitfrequency)."'").",";
		$sql .= " ".(!isset($this->frequency) ? '0' : ((int) $this->frequency)).",";
		$sql .= " ".(!isset($this->status) ? '0' : ((int) $this->status)).",";
		$sql .= " ".($user->id ? (int) $user->id : "NULL").",";
		$sql .= " ".($user->id ? (int) $user->id : "NULL").",";
		$sql .= " ".(!isset($this->note_private) ? 'NULL' : "'".$this->db->escape($this->note_private)."'").",";
		$sql .= " ".(!isset($this->nbrun) ? '0' : ((int) $this->nbrun)).",";
		$sql .= " ".(empty($this->maxrun) ? '0' : ((int) $this->maxrun)).",";
		$sql .= " ".(!isset($this->libname) ? 'NULL' : "'".$this->db->escape($this->libname)."'").",";
		$sql .= " ".(!isset($this->test) ? 'NULL' : "'".$this->db->escape($this->test)."'");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."cronjob");
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param	int			$id				Id object
	 * @param	string		$objectname		Object name
	 * @param	string		$methodname		Method name
	 * @return	int							if KO: <0 || if OK: >0
	 */
	public function fetch(int $id, string $objectname = '', string $methodname = '')
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.tms,";
		$sql .= " t.datec,";
		$sql .= " t.jobtype,";
		$sql .= " t.label,";
		$sql .= " t.command,";
		$sql .= " t.classesname,";
		$sql .= " t.objectname,";
		$sql .= " t.methodename,";
		$sql .= " t.params,";
		$sql .= " t.md5params,";
		$sql .= " t.module_name,";
		$sql .= " t.priority,";
		$sql .= " t.datelastrun,";
		$sql .= " t.datenextrun,";
		$sql .= " t.dateend,";
		$sql .= " t.datestart,";
		$sql .= " t.lastresult,";
		$sql .= " t.datelastresult,";
		$sql .= " t.lastoutput,";
		$sql .= " t.unitfrequency,";
		$sql .= " t.frequency,";
		$sql .= " t.status,";
		$sql .= " t.processing,";
		$sql .= " t.pid,";
		$sql .= " t.email_alert,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.note as note_private,";
		$sql .= " t.nbrun,";
		$sql .= " t.maxrun,";
		$sql .= " t.libname,";
		$sql .= " t.test";
		$sql .= " FROM ".MAIN_DB_PREFIX."cronjob as t";
		if ($id > 0) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE t.entity IN(0, ".getEntity('cron').")";
			$sql .= " AND t.objectname = '".$this->db->escape($objectname)."'";
			$sql .= " AND t.methodename = '".$this->db->escape($methodname)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->entity = $obj->entity;
				$this->tms = $this->db->jdate($obj->tms);
				$this->datec = $this->db->jdate($obj->datec);
				$this->label = $obj->label;
				$this->jobtype = $obj->jobtype;
				$this->command = $obj->command;
				$this->classesname = $obj->classesname;
				$this->objectname = $obj->objectname;
				$this->methodename = $obj->methodename;
				$this->params = $obj->params;
				$this->md5params = $obj->md5params;
				$this->module_name = $obj->module_name;
				$this->priority = $obj->priority;
				$this->datelastrun = $this->db->jdate($obj->datelastrun);
				$this->datenextrun = $this->db->jdate($obj->datenextrun);
				$this->dateend = $this->db->jdate($obj->dateend);
				$this->datestart = $this->db->jdate($obj->datestart);
				$this->lastresult = (string) $obj->lastresult;
				$this->lastoutput = $obj->lastoutput;
				$this->datelastresult = $this->db->jdate($obj->datelastresult);
				$this->unitfrequency = $obj->unitfrequency;
				$this->frequency = $obj->frequency;
				$this->status = $obj->status;
				$this->processing = $obj->processing;
				$this->pid = $obj->pid;
				$this->email_alert = $obj->email_alert;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->note_private = $obj->note_private;
				$this->nbrun = $obj->nbrun;
				$this->maxrun = $obj->maxrun;
				$this->libname = $obj->libname;
				$this->test = $obj->test;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Load list of cron jobs in a memory array from the database
	 *
	 * @param	string			$sortorder		Sort order
	 * @param	string			$sortfield		Sort field
	 * @param	int				$limit			Limit page
	 * @param	int				$offset			Offset ppage
	 * @param	int				$status			Display active or not (-1=no filter, 0=not active, 1=active, 2=archived)
	 * @param	string|array	$filter			Filter USF.
	 * @param	int				$processing		Processing or not (-1=all, 0=not in progress, 1=in progress)
	 * @return	int								if KO: <0 || if OK: >0
	 */
	public function fetchAll(string $sortorder = 'DESC', string $sortfield = 't.rowid', int $limit = 0, int $offset = 0, int $status = 1, $filter = '', int $processing = -1)
	{
		$this->lines = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.tms,";
		$sql .= " t.datec,";
		$sql .= " t.jobtype,";
		$sql .= " t.label,";
		$sql .= " t.command,";
		$sql .= " t.classesname,";
		$sql .= " t.objectname,";
		$sql .= " t.methodename,";
		$sql .= " t.params,";
		$sql .= " t.md5params,";
		$sql .= " t.module_name,";
		$sql .= " t.priority,";
		$sql .= " t.datelastrun,";
		$sql .= " t.datenextrun,";
		$sql .= " t.dateend,";
		$sql .= " t.datestart,";
		$sql .= " t.lastresult,";
		$sql .= " t.datelastresult,";
		$sql .= " t.lastoutput,";
		$sql .= " t.unitfrequency,";
		$sql .= " t.frequency,";
		$sql .= " t.status,";
		$sql .= " t.processing,";
		$sql .= " t.pid,";
		$sql .= " t.email_alert,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_mod,";
		$sql .= " t.note as note_private,";
		$sql .= " t.nbrun,";
		$sql .= " t.maxrun,";
		$sql .= " t.libname,";
		$sql .= " t.test";
		$sql .= " FROM ".MAIN_DB_PREFIX."cronjob as t";
		$sql .= " WHERE 1 = 1";
		if ($processing >= 0) {
			$sql .= " AND t.processing = ".(empty($processing) ? '0' : '1');
		}
		if ($status >= 0 && $status < 2) {
			$sql .= " AND t.status = ".(empty($status) ? '0' : '1');
		} elseif ($status == 2) {
			$sql .= " AND t.status = 2";
		}

		// Manage filter
		if (is_array($filter)) {
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if ($key == 't.rowid') {
						$sql .= " AND ".$this->db->sanitize($key)." = ".((int) $value);
					} else {
						$sql .= " AND ".$this->db->sanitize($key)." LIKE '%".$this->db->escape($this->db->escapeforlike($value))."%'";
					}
				}
			}

			$filter = '';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if (!empty($limit) && !empty($offset)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$cronjob_obj = new Cronjob($this->db);

					$cronjob_obj->id = $obj->rowid;
					$cronjob_obj->ref = $obj->rowid;
					$cronjob_obj->entity = $obj->entity;
					$cronjob_obj->tms = $this->db->jdate($obj->tms);
					$cronjob_obj->datec = $this->db->jdate($obj->datec);
					$cronjob_obj->label = $obj->label;
					$cronjob_obj->jobtype = $obj->jobtype;
					$cronjob_obj->command = $obj->command;
					$cronjob_obj->classesname = $obj->classesname;
					$cronjob_obj->objectname = $obj->objectname;
					$cronjob_obj->methodename = $obj->methodename;
					$cronjob_obj->params = $obj->params;
					$cronjob_obj->md5params = $obj->md5params;
					$cronjob_obj->module_name = $obj->module_name;
					$cronjob_obj->priority = $obj->priority;
					$cronjob_obj->datelastrun = $this->db->jdate($obj->datelastrun);
					$cronjob_obj->datenextrun = $this->db->jdate($obj->datenextrun);
					$cronjob_obj->dateend = $this->db->jdate($obj->dateend);
					$cronjob_obj->datestart = $this->db->jdate($obj->datestart);
					$cronjob_obj->lastresult = $obj->lastresult;
					$cronjob_obj->lastoutput = $obj->lastoutput;
					$cronjob_obj->datelastresult = $this->db->jdate($obj->datelastresult);
					$cronjob_obj->unitfrequency = $obj->unitfrequency;
					$cronjob_obj->frequency = $obj->frequency;
					$cronjob_obj->status = $obj->status;
					$cronjob_obj->processing = $obj->processing;
					$cronjob_obj->pid = $obj->pid;
					$cronjob_obj->email_alert = $obj->email_alert;
					$cronjob_obj->fk_user_author = $obj->fk_user_author;
					$cronjob_obj->fk_user_mod = $obj->fk_user_mod;
					$cronjob_obj->note_private = $obj->note_private;
					$cronjob_obj->nbrun = $obj->nbrun;
					$cronjob_obj->maxrun = $obj->maxrun;
					$cronjob_obj->libname = $obj->libname;
					$cronjob_obj->test = $obj->test;

					$this->lines[] = $cronjob_obj;

					$i++;
				}
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 * Update object into database
	 *
	 * @param	User|null	$user		User that modifies
	 * @param	int			$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int						if KO: <0 || if OK: >0
	 */
	public function update(User $user = null, int $notrigger = 0)
	{
		global $conf, $langs;

		$langs->load('cron');

		$error = 0;

		// Clean parameters
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->jobtype)) {
			$this->jobtype = trim($this->jobtype);
		}
		if (isset($this->command)) {
			$this->command = trim($this->command);
		}
		if (isset($this->classesname)) {
			$this->classesname = trim($this->classesname);
		}
		if (isset($this->objectname)) {
			$this->objectname = trim($this->objectname);
		}
		if (isset($this->methodename)) {
			$this->methodename = trim($this->methodename);
		}
		if (isset($this->params)) {
			$this->params = trim($this->params);
		}
		if (isset($this->md5params)) {
			$this->md5params = trim($this->md5params);
		}
		if (isset($this->module_name)) {
			$this->module_name = trim($this->module_name);
		}
		if (isset($this->priority)) {
			$this->priority = trim($this->priority);
		}
		if (isset($this->lastoutput)) {
			$this->lastoutput = trim($this->lastoutput);
		}
		if (isset($this->lastresult)) {
			$this->lastresult = trim($this->lastresult);
		}
		if (isset($this->unitfrequency)) {
			$this->unitfrequency = trim($this->unitfrequency);
		}
		if (isset($this->frequency)) {
			$this->frequency = (int) $this->frequency;
		}
		if (isset($this->status)) {
			$this->status = (int) $this->status;
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->nbrun)) {
			$this->nbrun = (is_numeric($this->nbrun)) ? (int) trim((string) $this->nbrun) : 0;
		}
		if (isset($this->libname)) {
			$this->libname = trim($this->libname);
		}
		if (isset($this->test)) {
			$this->test = trim($this->test);
		}

		if (empty($this->maxrun)) {
			$this->maxrun = 0;
		}
		if (empty($this->processing)) {
			$this->processing = 0;
		}
		if (empty($this->pid)) {
			$this->pid = null;
		}
		if (empty($this->email_alert)) {
			$this->email_alert = '';
		}
		if (empty($this->datenextrun)) {
			$this->datenextrun = dol_now();
		}

		// Check parameters
		// Put here code to add a control on parameters values
		if (dol_strlen($this->datenextrun) == 0 && $this->status == self::STATUS_ENABLED) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronDtNextLaunch'));
			$error++;
		}
		if ((dol_strlen($this->datestart) != 0) && (dol_strlen($this->dateend) != 0) && ($this->dateend < $this->datestart)) {
			$this->errors[] = $langs->trans('CronErrEndDateStartDt');
			$error++;
		}
		if (empty($this->label)) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronLabel'));
			$error++;
		}
		if (empty($this->unitfrequency)) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronFrequency'));
			$error++;
		}
		if (($this->jobtype == 'command') && (empty($this->command))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronCommand'));
			$error++;
		}
		if (($this->jobtype == 'method') && (empty($this->classesname))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronClass'));
			$error++;
		}
		if (($this->jobtype == 'method' || $this->jobtype == 'function') && (empty($this->methodename))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronMethod'));
			$error++;
		}
		if (($this->jobtype == 'method') && (empty($this->objectname))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronObject'));
			$error++;
		}

		if (($this->jobtype == 'function') && (empty($this->libname))) {
			$this->errors[] = $langs->trans('CronFieldMandatory', $langs->transnoentitiesnoconv('CronLib'));
			$error++;
		}


		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."cronjob SET";
		$sql .= " entity=".(isset($this->entity) ? ((int) $this->entity) : $conf->entity).",";
		$sql .= " label=".(isset($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " jobtype=".(isset($this->jobtype) ? "'".$this->db->escape($this->jobtype)."'" : "null").",";
		$sql .= " command=".(isset($this->command) ? "'".$this->db->escape($this->command)."'" : "null").",";
		$sql .= " classesname=".(isset($this->classesname) ? "'".$this->db->escape($this->classesname)."'" : "null").",";
		$sql .= " objectname=".(isset($this->objectname) ? "'".$this->db->escape($this->objectname)."'" : "null").",";
		$sql .= " methodename=".(isset($this->methodename) ? "'".$this->db->escape($this->methodename)."'" : "null").",";
		$sql .= " params=".(isset($this->params) ? "'".$this->db->escape($this->params)."'" : "null").",";
		$sql .= " md5params=".(isset($this->md5params) ? "'".$this->db->escape($this->md5params)."'" : "null").",";
		$sql .= " module_name=".(isset($this->module_name) ? "'".$this->db->escape($this->module_name)."'" : "null").",";
		$sql .= " priority=".(isset($this->priority) ? ((int) $this->priority) : "null").",";
		$sql .= " datelastrun=".(dol_strlen($this->datelastrun) != 0 ? "'".$this->db->idate($this->datelastrun)."'" : 'null').",";
		$sql .= " datenextrun=".(dol_strlen($this->datenextrun) != 0 ? "'".$this->db->idate($this->datenextrun)."'" : 'null').",";
		$sql .= " dateend=".(dol_strlen($this->dateend) != 0 ? "'".$this->db->idate($this->dateend)."'" : 'null').",";
		$sql .= " datestart=".(dol_strlen($this->datestart) != 0 ? "'".$this->db->idate($this->datestart)."'" : 'null').",";
		$sql .= " datelastresult=".(dol_strlen($this->datelastresult) != 0 ? "'".$this->db->idate($this->datelastresult)."'" : 'null').",";
		$sql .= " lastresult=".(isset($this->lastresult) ? "'".$this->db->escape($this->lastresult)."'" : "null").",";
		$sql .= " lastoutput=".(isset($this->lastoutput) ? "'".$this->db->escape($this->lastoutput)."'" : "null").",";
		$sql .= " unitfrequency=".(isset($this->unitfrequency) ? "'".$this->db->escape($this->unitfrequency)."'" : "null").",";
		$sql .= " frequency=".(isset($this->frequency) ? ((int) $this->frequency) : "null").",";
		$sql .= " status=".(isset($this->status) ? ((int) $this->status) : "null").",";
		$sql .= " processing=".((isset($this->processing) && $this->processing > 0) ? $this->processing : "0").",";
		$sql .= " pid=".(isset($this->pid) ? ((int) $this->pid) : "null").",";
		$sql .= " email_alert = ".(isset($this->email_alert) ? "'".$this->db->escape($this->email_alert)."'" : "null").",";
		$sql .= " fk_user_mod = ".((int) $user->id).",";
		$sql .= " note=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " nbrun=".((isset($this->nbrun) && $this->nbrun > 0) ? $this->nbrun : "null").",";
		$sql .= " maxrun=".((isset($this->maxrun) && $this->maxrun > 0) ? $this->maxrun : "0").",";
		$sql .= " libname=".(isset($this->libname) ? "'".$this->db->escape($this->libname)."'" : "null").",";
		$sql .= " test=".(isset($this->test) ? "'".$this->db->escape($this->test)."'" : "null");
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
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
	 * Delete object in database
	 *
	 * @param	User	$user		User that deletes
	 * @param	int		$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int					if KO: <0 || if OK: >0
	 */
	public function delete(User $user, int $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."cronjob";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
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
			$this->db->commit();
			return 1;
		}
	}



	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user		User making the clone
	 * @param	int		$fromid		Id of object to clone
	 * @return	int					New id of clone
	 */
	public function createFromClone(User $user, int $fromid)
	{
		global $langs;

		$error = 0;

		$object = new Cronjob($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;

		// Clear fields
		$object->status = self::STATUS_DISABLED;
		$object->label = $langs->trans("CopyOf").' '.$langs->trans($object->label);
		$object->datelastrun = null;
		$object->lastresult = '';
		$object->datelastresult = null;
		$object->lastoutput = '';
		$object->nbrun = 0;

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$this->errors = $object->errors;
			$error++;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->ref = '';
		$this->entity = 0;
		$this->date_modification = dol_now();
		$this->datec = '';
		$this->label = '';
		$this->jobtype = '';
		$this->command = '';
		$this->classesname = '';
		$this->objectname = '';
		$this->methodename = '';
		$this->params = '';
		$this->md5params = '';
		$this->module_name = '';
		$this->priority = '';
		$this->datelastrun = '';
		$this->datenextrun = '';
		$this->dateend = '';
		$this->datestart = '';
		$this->datelastresult = '';
		$this->lastoutput = '';
		$this->lastresult = '';
		$this->unitfrequency = '86400';
		$this->frequency = 0;
		$this->status = 0;
		$this->processing = 0;
		$this->pid = null;
		$this->email_alert = '';
		$this->fk_user_author = 0;
		$this->fk_user_mod = 0;
		$this->note_private = '';
		$this->nbrun = 0;
		$this->maxrun = 100;
		$this->libname = '';

		return 1;
	}


	/**
	 * getTooltipContentArray
	 *
	 * @param	array		$params		params to construct tooltip data
	 * @since v18
	 * @return	array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->load('cron');
		$datas = [];

		$datas['picto'] = img_picto('', 'object_'.$this->picto).' <u>'.$langs->trans("CronTask").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.dol_escape_htmltag($this->ref);
		$datas['label'] = '<br><b>'.$langs->trans('Title').':</b> '.$langs->trans($this->label);
		if ($this->label != $langs->trans($this->label)) {
			$datas['label']  .= ' <span class="opacitymedium">('.$this->label.')</span>';
		}
		if (!empty($this->params)) {
			$datas['params'] = '<br><b>'.$langs->trans('Parameters').':</b> '.dol_escape_htmltag($this->params);
		}
		$datas['space'] = '<br>';

		if (!empty($this->datestart) && $this->datestart >= dol_now()) {
			$datas['crondtstart'] = '<br><b>'.$langs->trans('CronDtStart').':</b> '.dol_print_date($this->datestart, 'dayhour', 'tzuserrel');
		}
		if (!empty($this->dateend)) {
			$datas['crondtend'] = '<br><b>'.$langs->trans('CronDtEnd').':</b> '.dol_print_date($this->dateend, 'dayhour', 'tzuserrel');
		}
		if (!empty($this->datelastrun)) {
			$datas['cronlastlaunch'] = '<br><b>'.$langs->trans('CronDtLastLaunch').':</b> '.dol_print_date($this->datelastrun, 'dayhour', 'tzuserrel');
		}
		if (!empty($this->datenextrun)) {
			$datas['crondtnextlaunch'] = '<br><b>'.$langs->trans('CronDtNextLaunch').':</b> '.dol_print_date($this->datenextrun, 'dayhour', 'tzuserrel');
		}

		return $datas;
	}

	/**
	 * Return a link to the object card (with optionally the picto)
	 *
	 * @param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param	string	$option						On what the link point to ('nolink', ...)
	 * @param	int		$notooltip					1=Disable tooltip
	 * @param	string	$morecss					Add more css on link
	 * @param	int		$save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return	string								String with URL
	 */
	public function getNomUrl(int $withpicto = 0, string $option = '', int $notooltip = 0, string $morecss = '', int $save_lastsearch_value = -1)
	{
		global $conf, $langs;

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

		$url = DOL_URL_ROOT.'/cron/card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowCronJob");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ?: 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}


	/**
	 * Load object information
	 *
	 * @param	int		$id		ID
	 * @return	int				if KO: <0 || if OK: >0
	 */
	public function info(int $id)
	{
		$sql = "SELECT";
		$sql .= " f.rowid, f.datec, f.tms, f.fk_user_mod, f.fk_user_author";
		$sql .= " FROM ".MAIN_DB_PREFIX."cronjob as f";
		$sql .= " WHERE f.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->user_modification_id = $obj->fk_user_mod;
				$this->user_creation_id = $obj->fk_user_author;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Run a job.
	 * Once job is finished, status and nb of run is updated.
	 * This function does not plan the next run. This is done by function ->reprogram_jobs
	 *
	 * @param	string		$userlogin		User login
	 * @return	int					 		if KO: <0 || if OK: >0
	 */
	public function run_jobs(string $userlogin)
	{
		// phpcs:enable
		global $langs, $conf, $hookmanager;

		$hookmanager->initHooks(array('cron'));

		$now = dol_now();
		$error = 0;

		$langs->load('cron');

		if (empty($userlogin)) {
			$this->error = "User login is mandatory";
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			return -1;
		}

		// Force the environment of running to the environment declared for job, so jobs launched from command line will run into correct environment
		// When job is ran from GUI, the environment should already be same, except if job has entity 0 (visible into all environments)
		if ($conf->entity != $this->entity && $this->entity > 0) {
			dol_syslog("We try to run a job in entity ".$this->entity." when we are in entity ".$conf->entity, LOG_WARNING);
		}
		$savcurrententity = $conf->entity;
		$conf->setEntityValues($this->db, $this->entity);
		dol_syslog(get_class($this)."::run_jobs entity for running job is ".$conf->entity);

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user = new User($this->db);
		$result = $user->fetch(0, $userlogin);
		if ($result < 0) {
			$this->error = "User Error:".$user->error;
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			$conf->setEntityValues($this->db, $savcurrententity);
			return -1;
		} else {
			if (empty($user->id)) {
				$this->error = "User login: ".$userlogin." does not exist";
				dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
				$conf->setEntityValues($this->db, $savcurrententity);
				return -1;
			}
		}

		dol_syslog(get_class($this)."::run_jobs jobtype=".$this->jobtype." userlogin=".$userlogin, LOG_DEBUG);

		// Increase limit of time. Works only if we are not in safe mode
		$ExecTimeLimit = 600;
		if (!empty($ExecTimeLimit)) {
			$err = error_reporting();
			error_reporting(0); // Disable all errors
			//error_reporting(E_ALL);
			@set_time_limit($ExecTimeLimit); // Need more than 240 on Windows 7/64
			error_reporting($err);
		}
		$MemoryLimit = 0;
		if (!empty($MemoryLimit)) {
			@ini_set('memory_limit', $MemoryLimit);
		}

		// Update last run date start (to track running jobs)
		$this->datelastrun = $now;
		$this->datelastresult = null;
		$this->lastoutput = '';
		$this->lastresult = '';
		$this->processing = 1; // To know job was started
		$this->pid = function_exists('getmypid') ? getmypid() : null; // Avoid dol_getmypid to get null if the function is not available
		$this->nbrun += 1;
		$result = $this->update($user); // This include begin/commit
		if ($result < 0) {
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			$conf->setEntityValues($this->db, $savcurrententity);
			return -1;
		}

		// Run a method
		if ($this->jobtype == 'method') {
			// Deny to launch a method from a deactivated module
			if (!empty($this->entity) && !empty($this->module_name) && !isModEnabled(strtolower($this->module_name))) {
				$this->error = $langs->transnoentitiesnoconv('CronModuleNotEnabledInThisEntity', $this->methodename, $this->objectname);
				dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
				$this->lastoutput = $this->error;
				$this->lastresult = '-1';
				$error++;
			}

			// load classes
			if (!$error) {
				$ret = dol_include_once($this->classesname);
				if ($ret === false || (!class_exists($this->objectname))) {
					if ($ret === false) {
						$this->error = $langs->transnoentitiesnoconv('CronCannotLoadClass', $this->classesname, $this->objectname);
					} else {
						$this->error = $langs->transnoentitiesnoconv('CronCannotLoadObject', $this->classesname, $this->objectname);
					}
					dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
					$this->lastoutput = $this->error;
					$this->lastresult = '-1';
					$error++;
				}
			}

			// test if method exists
			if (!$error) {
				if (!method_exists($this->objectname, $this->methodename)) {
					$this->error = $langs->transnoentitiesnoconv('CronMethodDoesNotExists', $this->objectname, $this->methodename);
					dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
					$this->lastoutput = $this->error;
					$this->lastresult = '-1';
					$error++;
				}
				if (in_array(strtolower(trim($this->methodename)), array('executecli'))) {
					$this->error = $langs->transnoentitiesnoconv('CronMethodNotAllowed', $this->methodename, $this->objectname);
					dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
					$this->lastoutput = $this->error;
					$this->lastresult = '-1';
					$error++;
				}
			}

			// Load langs
			if (!$error) {
				$result = $langs->load($this->module_name);
				$result = $langs->load($this->module_name.'@'.$this->module_name, 0, 0, '', 0, 1);

				if ($result < 0) {	// If technical error
					dol_syslog(get_class($this)."::run_jobs Cannot load module lang file - ".$langs->error, LOG_ERR);
					$this->error = $langs->error;
					$this->lastoutput = $this->error;
					$this->lastresult = '-1';
					$error++;
				}
			}

			if (!$error) {
				dol_syslog(get_class($this)."::run_jobs START ".$this->objectname."->".$this->methodename."(".$this->params."); (Note: Log for cron jobs may be into a different log file)", LOG_DEBUG);

				// Create Object for the called module
				$nameofclass = (string) $this->objectname;
				$object = new $nameofclass($this->db);
				if ($this->entity > 0) {
					$object->entity = $this->entity; // We work on a dedicated entity
				}

				$params_arr = array();
				if (!empty($this->params) || $this->params === '0') {
					$params_arr = array_map('trim', explode(",", $this->params));
				}

				if (!is_array($params_arr)) {
					$result = call_user_func(array($object, $this->methodename), $this->params);
				} else {
					$result = call_user_func_array(array($object, $this->methodename), $params_arr);
				}
				$errmsg = '';
				if ($result === false || (!is_bool($result) && $result != 0)) {
					$langs->load("errors");

					if (!is_array($object->errors) || !in_array($object->error, $object->errors)) {
						$errmsg .= $object->error;
					}
					if (is_array($object->errors) && count($object->errors)) {
						$errmsg .= (($errmsg ? ', ' : '').implode(', ', $object->errors));
					}
					if (empty($errmsg)) {
						$errmsg = $langs->trans('ErrorUnknown');
					}

					dol_syslog(get_class($this)."::run_jobs END result=".$result." error=".$errmsg, LOG_ERR);

					$this->error = $errmsg;
					$this->lastoutput = dol_substr((empty($object->output) ? "" : $object->output."\n").$errmsg, 0, $this::MAXIMUM_LENGTH_FOR_LASTOUTPUT_FIELD, 'UTF-8', 1);
					$this->lastresult = is_numeric($result) ? var_export($result, true) : '-1';
					$error++;
				} else {
					dol_syslog(get_class($this)."::run_jobs END");
					$this->lastoutput = dol_substr((empty($object->output) ? "" : $object->output."\n"), 0, $this::MAXIMUM_LENGTH_FOR_LASTOUTPUT_FIELD, 'UTF-8', 1);
					$this->lastresult = var_export($result, true);
				}
			}
		}

		if ($this->jobtype == 'function') {
			//load lib
			$libpath = '/'.strtolower($this->module_name).'/lib/'.$this->libname;
			$ret = dol_include_once($libpath);
			if ($ret === false) {
				$this->error = $langs->trans('CronCannotLoadLib').': '.$libpath;
				dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
				$conf->setEntityValues($this->db, $savcurrententity);
				return -1;
			}

			// Load langs
			$result = $langs->load($this->module_name);
			$result = $langs->load($this->module_name.'@'.$this->module_name); // If this->module_name was an existing language file, this will make nothing
			if ($result < 0) {	// If technical error
				dol_syslog(get_class($this)."::run_jobs Cannot load module langs".$langs->error, LOG_ERR);
				$conf->setEntityValues($this->db, $savcurrententity);
				return -1;
			}

			dol_syslog(get_class($this)."::run_jobs ".$this->libname."::".$this->methodename."(".$this->params.");", LOG_DEBUG);
			$params_arr = explode(", ", $this->params);
			if (!is_array($params_arr)) {
				$result = call_user_func($this->methodename, $this->params);
			} else {
				$result = call_user_func_array($this->methodename, $params_arr);
			}

			if ($result === false || (!is_bool($result) && $result != 0)) {
				$langs->load("errors");
				dol_syslog(get_class($this)."::run_jobs result=".$result, LOG_ERR);
				$this->error = $langs->trans('ErrorUnknown');
				$this->lastoutput = $this->error;
				$this->lastresult = is_numeric($result) ? var_export($result, true) : '-1';
				$error++;
			} else {
				$this->lastoutput = var_export($result, true);
				$this->lastresult = var_export($result, true); // Return code
			}
		}

		// Run a command line
		if ($this->jobtype == 'command') {
			global $dolibarr_cron_allow_cli;

			if (empty($dolibarr_cron_allow_cli)) {
				$langs->load("errors");
				$this->error      = $langs->trans("FailedToExecutCommandJob");
				$this->lastoutput = '';
				$this->lastresult = $langs->trans("ErrorParameterMustBeEnabledToAllwoThisFeature", 'dolibarr_cron_allow_cli');
			} else {
				$outputdir = $conf->cron->dir_temp;
				if (empty($outputdir)) {
					$outputdir = $conf->cronjob->dir_temp;
				}

				if (!empty($outputdir)) {
					dol_mkdir($outputdir);
					$outputfile = $outputdir.'/cronjob.'.$userlogin.'.out'; // File used with popen method

					// Execute a CLI
					include_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
					$utils = new Utils($this->db);
					$arrayresult = $utils->executeCLI($this->command, $outputfile);

					$this->error      = $arrayresult['error'];
					$this->lastoutput = $arrayresult['output'];
					$this->lastresult = $arrayresult['result'];
				}
			}
		}

		dol_syslog(get_class($this)."::run_jobs now we update job to track it is finished (with success or error)");

		$this->datelastresult = dol_now();
		$this->processing = 0;
		$this->pid = null;
		$result = $this->update($user); // This include begin/commit
		if ($result < 0) {
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			$conf->setEntityValues($this->db, $savcurrententity);
			return -1;
		}

		$conf->setEntityValues($this->db, $savcurrententity);

		if ($error && !empty($this->email_alert)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$subject = $langs->trans("ErrorInBatch", $this->label);
			$msg = $langs->trans("ErrorInBatch", $this->label);
			$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
			$cmailfile = new CMailFile($subject, $this->email_alert, $from, $msg);
			$result = $cmailfile->sendfile();	// Do not test result
		}

		return $error ? -1 : 1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Reprogram a job
	 *
	 * @param	string		$userlogin		User login
	 * @param	integer		$now			Date returned by dol_now()
	 * @return	int							if KO: <0 || if OK: >0
	 */
	public function reprogram_jobs(string $userlogin, int $now)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::reprogram_jobs userlogin:$userlogin", LOG_DEBUG);

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user = new User($this->db);
		$result = $user->fetch(0, $userlogin);
		if ($result < 0) {
			$this->error = "User Error : ".$user->error;
			dol_syslog(get_class($this)."::reprogram_jobs ".$this->error, LOG_ERR);
			return -1;
		} else {
			if (empty($user->id)) {
				$this->error = " User user login:".$userlogin." do not exists";
				dol_syslog(get_class($this)."::reprogram_jobs ".$this->error, LOG_ERR);
				return -1;
			}
		}

		dol_syslog(get_class($this)."::reprogram_jobs datenextrun=".$this->datenextrun." ".dol_print_date($this->datenextrun, 'dayhourrfc')." frequency=".$this->frequency." unitfrequency=".$this->unitfrequency, LOG_DEBUG);

		if (empty($this->datenextrun)) {
			if (empty($this->datestart)) {
				if (!is_numeric($this->frequency) || (int) $this->unitfrequency == 2678400) {
					$this->datenextrun = dol_time_plus_duree($now, $this->frequency, 'm');
				} else {
					$this->datenextrun = $now + ($this->frequency * (int) $this->unitfrequency);
				}
			} else {
				if (!is_numeric($this->frequency) || (int) $this->unitfrequency == 2678400) {
					$this->datenextrun = dol_time_plus_duree($this->datestart, $this->frequency, 'm');
				} else {
					$this->datenextrun = $this->datestart + ($this->frequency * (int) $this->unitfrequency);
				}
			}
		}

		if ($this->datenextrun < $now && $this->frequency > 0 && !empty($this->unitfrequency)) {
			// Loop until date is after future
			while ($this->datenextrun < $now) {
				if (!is_numeric($this->unitfrequency) || (int) $this->unitfrequency == 2678400 || (int) $this->unitfrequency <= 0) {
					$this->datenextrun = dol_time_plus_duree($this->datenextrun, $this->frequency, 'm');
				} else {
					$this->datenextrun += ($this->frequency * (int) $this->unitfrequency);
				}
			}
		} else {
			dol_syslog(get_class($this)."::reprogram_jobs datenextrun is already in future, we do not change it");
		}


		// Archive job
		if ($this->autodelete == 2) {
			if (($this->maxrun > 0 && ($this->nbrun >= $this->maxrun))
				|| ($this->dateend && ($this->datenextrun > $this->dateend))) {
				$this->status = self::STATUS_ARCHIVED;
				dol_syslog(get_class($this)."::reprogram_jobs Job will be set to archived", LOG_ERR);
			}
		}

		$result = $this->update($user);
		if ($result < 0) {
			dol_syslog(get_class($this)."::reprogram_jobs ".$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Return label of status of user (active, inactive)
	 *
	 * @param	int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return	string					Label of status
	 */
	public function getLibStatut(int $mode = 0)
	{
		return $this->LibStatut($this->status, $mode, $this->processing, $this->lastresult);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return label of a giver status
	 *
	 * @param	int		$status				Id status
	 * @param	int		$mode				0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @param	int		$processing			0=Not running, 1=Running
	 * @param	string	$lastResult			Value of last result (''=no error, error otherwise)
	 * @return	string						Label of status
	 */
	public function LibStatut(int $status, int $mode = 0, int $processing = 0, string $lastResult = '')
	{
		// phpcs:enable
		$this->labelStatus = array(); // Force reset o array because label depends on other fields
		$this->labelStatusShort = array();

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load('users');

			$moreText = '';
			if ($processing) {
				$moreText = ' ('.$langs->trans("Running").')';
			} elseif ($lastResult) {
				$moreText .= ' ('.$langs->trans("Error").')';
			}

			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled').$moreText;
			$this->labelStatus[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Scheduled').$moreText;
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Scheduled');
		}

		$statusType = 'status4';
		if ($status == 1 && $processing) {
			$statusType = 'status1';
		}
		if ($status == 0) {
			$statusType = 'status5';
		}
		if ($this->lastresult) {
			$statusType = 'status8';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}
}
