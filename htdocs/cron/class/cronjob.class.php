<?php
/* Copyright (C) 2007-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
	 * @var string ID of module.
	 */
	public $module = 'cron';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'cronjob';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'cronjob';

	/**
	 * @var int  	Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'cron';

	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;
	const STATUS_ARCHIVED = 2;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		"rowid" => array("type"=>"integer", "label"=>"TechnicalID", "enabled"=>"1", 'position'=>10, 'notnull'=>1, "visible"=>"-1",),
		"tms" => array("type"=>"timestamp", "label"=>"DateModification", "enabled"=>"1", 'position'=>15, 'notnull'=>1, "visible"=>"-1",),
		"datec" => array("type"=>"datetime", "label"=>"DateCreation", "enabled"=>"1", 'position'=>20, 'notnull'=>0, "visible"=>"-1",),
		"jobtype" => array("type"=>"varchar(10)", "label"=>"Jobtype", "enabled"=>"1", 'position'=>25, 'notnull'=>1, "visible"=>"-1",),
		"label" => array("type"=>"varchar(255)", "label"=>"Label", "enabled"=>"1", 'position'=>30, 'notnull'=>1, "visible"=>"-1", "alwayseditable"=>"1", "css"=>"minwidth300", "cssview"=>"wordbreak", "csslist"=>"tdoverflowmax150",),
		"command" => array("type"=>"varchar(255)", "label"=>"Command", "enabled"=>"1", 'position'=>35, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"classesname" => array("type"=>"varchar(255)", "label"=>"Classesname", "enabled"=>"1", 'position'=>40, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"objectname" => array("type"=>"varchar(255)", "label"=>"Objectname", "enabled"=>"1", 'position'=>45, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"methodename" => array("type"=>"varchar(255)", "label"=>"Methodename", "enabled"=>"1", 'position'=>50, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"params" => array("type"=>"text", "label"=>"Params", "enabled"=>"1", 'position'=>55, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"md5params" => array("type"=>"varchar(32)", "label"=>"Md5params", "enabled"=>"1", 'position'=>60, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"module_name" => array("type"=>"varchar(255)", "label"=>"Modulename", "enabled"=>"1", 'position'=>65, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"priority" => array("type"=>"integer", "label"=>"Priority", "enabled"=>"1", 'position'=>70, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"datelastrun" => array("type"=>"datetime", "label"=>"Datelastrun", "enabled"=>"1", 'position'=>75, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"datenextrun" => array("type"=>"datetime", "label"=>"Datenextrun", "enabled"=>"1", 'position'=>80, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"datestart" => array("type"=>"datetime", "label"=>"Datestart", "enabled"=>"1", 'position'=>85, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"dateend" => array("type"=>"datetime", "label"=>"Dateend", "enabled"=>"1", 'position'=>90, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"datelastresult" => array("type"=>"datetime", "label"=>"Datelastresult", "enabled"=>"1", 'position'=>95, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"lastresult" => array("type"=>"text", "label"=>"Lastresult", "enabled"=>"1", 'position'=>100, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"lastoutput" => array("type"=>"text", "label"=>"Lastoutput", "enabled"=>"1", 'position'=>105, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"unitfrequency" => array("type"=>"varchar(255)", "label"=>"Unitfrequency", "enabled"=>"1", 'position'=>110, 'notnull'=>1, "visible"=>"-1", "alwayseditable"=>"1",),
		"frequency" => array("type"=>"integer", "label"=>"Frequency", "enabled"=>"1", 'position'=>115, 'notnull'=>1, "visible"=>"-1", "alwayseditable"=>"1",),
		"nbrun" => array("type"=>"integer", "label"=>"Nbrun", "enabled"=>"1", 'position'=>120, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"status" => array("type"=>"integer", "label"=>"Status", "enabled"=>"1", 'position'=>500, 'notnull'=>1, "visible"=>"-1", "alwayseditable"=>"1",),
		"fk_user_author" => array("type"=>"integer:User:user/class/user.class.php", "label"=>"Fkuserauthor", "enabled"=>"1", 'position'=>130, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1", "css"=>"maxwidth500 widthcentpercentminusxx", "csslist"=>"tdoverflowmax150",),
		"fk_user_mod" => array("type"=>"integer:User:user/class/user.class.php", "label"=>"Fkusermod", "enabled"=>"1", 'position'=>135, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1", "css"=>"maxwidth500 widthcentpercentminusxx", "csslist"=>"tdoverflowmax150",),
		"note" => array("type"=>"text", "label"=>"Note", "enabled"=>"1", 'position'=>140, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"libname" => array("type"=>"varchar(255)", "label"=>"Libname", "enabled"=>"1", 'position'=>145, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>3, 'notnull'=>1, 'position'=>30, 'index'=>1),
		"maxrun" => array("type"=>"integer", "label"=>"Maxrun", "enabled"=>"1", 'position'=>155, 'notnull'=>1, "visible"=>"-1", "alwayseditable"=>"1",),
		"autodelete" => array("type"=>"integer", "label"=>"Autodelete", "enabled"=>"1", 'position'=>160, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"fk_mailing" => array("type"=>"integer", "label"=>"Fkmailing", "enabled"=>"1", 'position'=>165, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1", "css"=>"maxwidth500 widthcentpercentminusxx",),
		"test" => array("type"=>"varchar(255)", "label"=>"Test", "enabled"=>"1", 'position'=>170, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"processing" => array("type"=>"integer", "label"=>"Processing", "enabled"=>"1", 'position'=>175, 'notnull'=>1, "visible"=>"-1", "alwayseditable"=>"1",),
		"email_alert" => array("type"=>"varchar(128)", "label"=>"Emailalert", "enabled"=>"1", 'position'=>180, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
		"pid" => array("type"=>"integer", "label"=>"Pid", "enabled"=>"1", 'position'=>185, 'notnull'=>0, "visible"=>"-1", "alwayseditable"=>"1",),
	);
	public $rowid;

	/**
	 * @var string|int     Date for last cron object update
	 */
	public $tms;

	/**
	 * @var string|int     Date for cron job create
	 */
	public $datec;

	/**
	 * @var string Job type
	 */
	public $jobtype;

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
	 * @var string|int     Date for last job execution
	 */
	public $datelastrun= '';

	/**
	 * @var string|int     Date for next job execution
	 */
	public $datenextrun='';

	/**
	 * @var string|int     Date for first start job execution
	 */
	public $datestart='';

	/**
	 * @var string|int     Date for end job execution
	 */
	public $dateend='';

	/**
	 * @var string|int     Date for last result job execution
	 */
	public $datelastresult='';

	/**
	 * @var string 			Last result from end job execution
	 */
	public $lastresult;

	/**
	 * @var string 			Last output from end job execution
	 */
	public $lastoutput;

	/**
	 * @var string 			Unit frequency of job execution
	 */
	public $unitfrequency;

	/**
	 * @var int 			Frequency of job execution
	 */
	public $frequency;

	/**
	 * @var int 			Number of run job execution
	 */
	public $nbrun;

	/**
	 * @var int 			Status
	 */
	public $status;


	/**
	 * @var int 			User ID of creation
	 */
	public $fk_user_author;

	/**
	 * @var int 			User ID of last modification
	 */
	public $fk_user_mod;

	public $note;

	/**
	 * @var string 			Libname
	 */
	public $libname;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int 			Maximum run job execution
	 */
	public $maxrun;

	public $autodelete;
	public $fk_mailing;

	/**
	 * @var string 			A test condition to know if job is visible/qualified
	 */
	public $test;

	/**
	 * @var int 			Is job running ?
	 */
	public $processing;

	/**
	 * @var string 			Email when an error occurs
	 */
	public $email_alert;

	/**
	* @var int 			The job current PID
	 */
	public $pid;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}


	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $langs;
		$error = 0;

		$this->status = 0;
		$this->datec = dol_now();

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


		// Commit or rollback
		if ($error) {
			return -1 * $error;
		} else {
			$resultcreate = $this->createCommon($user, $notrigger);
			return $resultcreate;
		}

	}


	/**
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    			Id object
	 *  @param	string	$objectname		Object name
	 *  @param	string	$methodname		Method name
	 *  @return int          			<0 if KO, >0 if OK
	 */
	public function fetch($id, $objectname = '', $methodname = '')
	{

		if ($id > 0) {
			$result = $this->fetchCommon($id);
		} else {
			$morewhere=  " AND t.objectname = '".$this->db->escape($objectname)."'";
			$morewhere .= " AND t.methodename = '".$this->db->escape($methodname)."'";
			$result = $this->fetchCommon($id, '', $morewhere);
		}

		if ($result > 0) {
			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Load list of cron jobs in a memory array from the database
	 *  @TODO Use object CronJob and not CronJobLine.
	 *
	 *  @param	string		$sortorder      sort order
	 *  @param	string		$sortfield      sort field
	 *  @param	int			$limit		    limit page
	 *  @param	int			$offset    	    page
	 *  @param	int			$status    	    display active or not
	 *  @param	array		$filter    	    filter output
	 *  @param  int         $processing     Processing or not
	 *  @return int          			    <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = 'DESC', $sortfield = 't.rowid', $limit = 0, $offset = 0, $status = 1, $filter = array(), $processing = -1)
	{
		$this->lines = array();

		$sql = "SELECT";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		$sql .= " WHERE 1 = 1";
		if ($processing >= 0) {
			$sql .= " AND t.processing = ".(empty($processing) ? '0' : '1');
		}
		if ($status >= 0 && $status < 2) {
			$sql .= " AND t.status = ".(empty($status) ? '0' : '1');
		} elseif ($status == 2) {
			$sql .= " AND t.status = 2";
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$columnName = preg_replace('/^t\./', '', $key);
				if ($key === 'customsql') {
					// Never use 'customsql' with a value from user input since it is injected as is. The value must be hard coded.
					$sqlwhere[] = $value;
					continue;
				} elseif (isset($this->fields[$columnName])) {
					$type = $this->fields[$columnName]['type'];
					if (preg_match('/^integer/', $type)) {
						if (is_int($value)) {
							// single value
							$sqlwhere[] = $key . " = " . intval($value);
						} elseif (is_array($value)) {
							if (empty($value)) continue;
							$sqlwhere[] = $key . ' IN (' . $this->db->sanitize(implode(',', array_map('intval', $value))) . ')';
						}
						continue;
					} elseif (in_array($type, array('date', 'datetime', 'timestamp'))) {
						$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
						continue;
					}
				}

				// when the $key doesn't fall into the previously handled categories, we do as if the column were a varchar/text
				if (is_array($value) && count($value)) {
					$value = implode(',', array_map(function ($v) {
						return "'" . $this->db->sanitize($this->db->escape($v)) . "'";
					}, $value));
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($value, true) . ')';
				} elseif (is_scalar($value)) {
					if (strpos($value, '%') === false) {
						$sqlwhere[] = $key . " = '" . $this->db->sanitize($this->db->escape($value)) . "'";
					} else {
						$sqlwhere[] = $key . " LIKE '%" . $this->db->escapeforlike($this->db->escape($value)) . "%'";
					}
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" AND ", $sqlwhere).")";
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	public function update(User $user = null, $notrigger = 0)
	{
		global $conf, $langs;

		$langs->load('cron');

		$error = 0;

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

		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			return -1 * $error;
		} else {
			return $this->updateCommon($user, $notrigger);
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		User making the clone
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	public function createFromClone(User $user, $fromid)
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
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	public function initAsSpecimen()
	{
		$this->initAsSpecimenCommon();
	}


	/**
	 * getTooltipContentArray
	 * @param array $params params to construct tooltip data
	 * @since v18
	 * @return array
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
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs;

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
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowCronJob");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' :  ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}


	/**
	 *	Load object information
	 *
	 *  @param	int		$id		ID
	 *	@return	int				<0 if KO, >0 if OK
	 */
	public function info($id)
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
	 * @param   string		$userlogin    	User login
	 * @return	int					 		<0 if KO, >0 if OK
	 */
	public function run_jobs($userlogin)
	{
		// phpcs:enable
		global $langs, $conf, $hookmanager;

		$hookmanager->initHooks(array('cron'));

		$now = dol_now();
		$error = 0;
		$retval = '';

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
		$result = $user->fetch('', $userlogin);
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
		$this->nbrun = $this->nbrun + 1;
		$result = $this->update($user); // This include begin/commit
		if ($result < 0) {
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			$conf->setEntityValues($this->db, $savcurrententity);
			return -1;
		}

		// Run a method
		if ($this->jobtype == 'method') {
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
					$this->lastresult = -1;
					$retval = $this->lastresult;
					$error++;
				}
			}

			// test if method exists
			if (!$error) {
				if (!method_exists($this->objectname, $this->methodename)) {
					$this->error = $langs->transnoentitiesnoconv('CronMethodDoesNotExists', $this->objectname, $this->methodename);
					dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
					$this->lastoutput = $this->error;
					$this->lastresult = -1;
					$retval = $this->lastresult;
					$error++;
				}
				if (in_array(strtolower(trim($this->methodename)), array('executecli'))) {
					$this->error = $langs->transnoentitiesnoconv('CronMethodNotAllowed', $this->methodename, $this->objectname);
					dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
					$this->lastoutput = $this->error;
					$this->lastresult = -1;
					$retval = $this->lastresult;
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
					$this->lastresult = -1;
					$retval = $this->lastresult;
					$error++;
				}
			}

			if (!$error) {
				dol_syslog(get_class($this)."::run_jobs START ".$this->objectname."->".$this->methodename."(".$this->params."); !!! Log for job may be into a different log file...", LOG_DEBUG);

				// Create Object for the called module
				$nameofclass = $this->objectname;
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

				if ($result === false || (!is_bool($result) && $result != 0)) {
					$langs->load("errors");

					$errmsg = '';
					if (!is_array($object->errors) || !in_array($object->error, $object->errors)) {
						$errmsg .= $object->error;
					}
					if (is_array($object->errors) && count($object->errors)) {
						$errmsg .= (($errmsg ? ', ' : '').join(', ', $object->errors));
					}
					if (empty($errmsg)) {
						$errmsg = $langs->trans('ErrorUnknown');
					}

					dol_syslog(get_class($this)."::run_jobs END result=".$result." error=".$errmsg, LOG_ERR);

					$this->error = $errmsg;
					$this->lastoutput = (!empty($object->output) ? $object->output."\n" : "").$errmsg;
					$this->lastresult = is_numeric($result) ? $result : -1;
					$retval = $this->lastresult;
					$error++;
				} else {
					dol_syslog(get_class($this)."::run_jobs END");
					$this->lastoutput = (!empty($object->output) ? $object->output : "");
					$this->lastresult = var_export($result, true);
					$retval = $this->lastresult;
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
				$this->lastresult = is_numeric($result) ? $result : -1;
				$retval = $this->lastresult;
				$error++;
			} else {
				$this->lastoutput = var_export($result, true);
				$this->lastresult = var_export($result, true); // Return code
				$retval = $this->lastresult;
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

					$retval = $arrayresult['result'];
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

		return $error ?-1 : 1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Reprogram a job
	 *
	 * @param  string		$userlogin      User login
	 * @param  integer      $now            Date returned by dol_now()
	 * @return int					        <0 if KO, >0 if OK
	 */
	public function reprogram_jobs($userlogin, $now)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::reprogram_jobs userlogin:$userlogin", LOG_DEBUG);

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user = new User($this->db);
		$result = $user->fetch('', $userlogin);
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
				if ($this->unitfrequency == 2678400) {
					$this->datenextrun = dol_time_plus_duree($now, $this->frequency, 'm');
				} else {
					$this->datenextrun = $now + ($this->frequency * $this->unitfrequency);
				}
			} else {
				if ($this->unitfrequency == 2678400) {
					$this->datenextrun = dol_time_plus_duree($this->datestart, $this->frequency, 'm');
				} else {
					$this->datenextrun = $this->datestart + ($this->frequency * $this->unitfrequency);
				}
			}
		}

		if ($this->datenextrun < $now && $this->frequency > 0 && $this->unitfrequency > 0) {
			// Loop until date is after future
			while ($this->datenextrun < $now) {
				if ($this->unitfrequency == 2678400) {
					$this->datenextrun = dol_time_plus_duree($this->datenextrun, $this->frequency, 'm');
				} else {
					$this->datenextrun += ($this->frequency * $this->unitfrequency);
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
	 *  Return label of status of user (active, inactive)
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode, $this->processing, $this->lastresult);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a giver status
	 *
	 *  @param	int		$status        	Id statut
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@param	int		$processing		0=Not running, 1=Running
	 *  @param	int		$lastresult		Value of last result (0=no error, error otherwise)
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0, $processing = 0, $lastresult = 0)
	{
		// phpcs:enable
		$this->labelStatus = array(); // Force reset o array because label depends on other fields
		$this->labelStatusShort = array();

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$langs->load('users');

			$moretext = '';
			if ($processing) {
				$moretext = ' ('.$langs->trans("Running").')';
			} elseif ($lastresult) {
				$moretext .= ' ('.$langs->trans("Error").')';
			}

			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled').$moretext;
			$this->labelStatus[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Scheduled').$moretext;
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


/**
 *	Crob Job line class
 */
class Cronjobline
{

	/**
	 * @var int ID
	 */
	public $id;

	public $entity;

	/**
	 * @var string Ref
	 */
	public $ref;

	public $tms = '';
	public $datec = '';

	/**
	 * @var string Cron Job Line label
	 */
	public $label;

	public $jobtype;
	public $command;
	public $classesname;
	public $objectname;
	public $methodename;
	public $params;
	public $md5params;
	public $module_name;
	public $priority;
	public $datelastrun = '';
	public $datenextrun = '';
	public $dateend = '';
	public $datestart = '';
	public $datelastresult = '';
	public $lastresult = '';
	public $lastoutput;
	public $unitfrequency;
	public $frequency;
	public $processing;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var int ID
	 */
	public $fk_user_author;

	/**
	 * @var int ID
	 */
	public $fk_user_mod;

	public $note;
	public $note_private;
	public $nbrun;
	public $libname;
	public $test;

	/**
	 *  Constructor
	 *
	 */
	public function __construct()
	{
	}
}
