<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 * API class for projects
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Tasks extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'ref',
		'label',
		'fk_project'
	);

	/**
	 * @var Task $task {@type Task}
	 */
	public $task;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->task = new Task($this->db);
	}

	/**
	 * Get properties of a task object
	 *
	 * Return an array with task information
	 *
	 * @param   int         $id                     ID of task
	 * @param   int         $includetimespent       0=Return only task. 1=Include a summary of time spent, 2=Include details of time spent lines
	 * @return	array|mixed                         data without useless information
	 *
	 * @throws	RestException
	 */
	public function get($id, $includetimespent = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->task->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Task not found');
		}

		if (!DolibarrApi::_checkAccessToResource('task', $this->task->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includetimespent == 1) {
			$timespent = $this->task->getSummaryOfTimeSpent(0);
		}
		if ($includetimespent == 2) {
			$timespent = $this->task->fetchTimeSpentOnTask();
		}

		return $this->_cleanObjectDatas($this->task);
	}



	/**
	 * List tasks
	 *
	 * Get a list of tasks
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of project objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : 0;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."projet AS p ON p.rowid = t.fk_projet";
		$sql .= ' WHERE t.entity IN ('.getEntity('project').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$task_static = new Task($this->db);
				if ($task_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($task_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve task list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create task object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of project
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->task->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->task->$field = $this->_checkValForAPI($field, $value, $this->task);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->project->lines = $lines;
		}*/
		if ($this->task->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating task", array_merge(array($this->task->error), $this->task->errors));
		}

		return $this->task->id;
	}

	// /**
	//  * Get time spent of a task
	//  *
	//  * @param int   $id                     Id of task
	//  * @return int
	//  *
	//  * @url	GET {id}/tasks
	//  */
	/*
	public function getLines($id, $includetimespent=0)
	{
		if(! DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Project not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->project->getLinesArray(DolibarrApiAccess::$user);
		$result = array();
		foreach ($this->project->lines as $line)      // $line is a task
		{
			if ($includetimespent == 1)
			{
				$timespent = $line->getSummaryOfTimeSpent(0);
			}
			if ($includetimespent == 1)
			{
				// TODO
				// Add class for timespent records and loop and fill $line->lines with records of timespent
			}
			array_push($result,$this->_cleanObjectDatas($line));
		}
		return $result;
	}
	*/

	/**
	 * Get roles a user is assigned to a task with
	 *
	 * @param   int   $id           Id of task
	 * @param   int   $userid       Id of user (0 = connected user)
	 * @return	array				Array of roles
	 *
	 * @url	GET {id}/roles
	 *
	 */
	public function getRoles($id, $userid = 0)
	{
		global $db;

		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->task->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Task not found');
		}

		if (!DolibarrApi::_checkAccessToResource('tasks', $this->task->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$usert = DolibarrApiAccess::$user;
		if ($userid > 0) {
			$usert = new User($this->db);
			$usert->fetch($userid);
		}
		$this->task->roles = $this->task->getUserRolesForProjectsOrTasks(null, $usert, 0, $id);
		$result = array();
		foreach ($this->task->roles as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}

		return $result;
	}


	// /**
	//  * Add a task to given project
	//  *
	//  * @param int   $id             Id of project to update
	//  * @param array $request_data   Projectline data
	//  *
	//  * @url	POST {id}/tasks
	//  *
	//  * @return int
	//  */
	/*
	public function postLine($id, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Project not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');

		$updateRes = $this->project->addline(
						$request_data->desc,
						$request_data->subprice,
						$request_data->qty,
						$request_data->tva_tx,
						$request_data->localtax1_tx,
						$request_data->localtax2_tx,
						$request_data->fk_product,
						$request_data->remise_percent,
						$request_data->info_bits,
						$request_data->fk_remise_except,
						'HT',
						0,
						$request_data->date_start,
						$request_data->date_end,
						$request_data->product_type,
						$request_data->rang,
						$request_data->special_code,
						$fk_parent_line,
						$request_data->fk_fournprice,
						$request_data->pa_ht,
						$request_data->label,
						$request_data->array_options,
						$request_data->fk_unit,
						$this->element,
						$request_data->id
		);

		if ($updateRes > 0) {
			return $updateRes;

		}
		return false;
	}
	*/

	// /**
	//  * Update a task of a given project
	//  *
	//  * @param int   $id             Id of project to update
	//  * @param int   $taskid         Id of task to update
	//  * @param array $request_data   Projectline data
	//  *
	//  * @url	PUT {id}/tasks/{taskid}
	//  *
	//  * @return object
	//  */
	/*
	public function putLine($id, $lineid, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Project not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');

		$updateRes = $this->project->updateline(
						$lineid,
						$request_data->desc,
						$request_data->subprice,
						$request_data->qty,
						$request_data->remise_percent,
						$request_data->tva_tx,
						$request_data->localtax1_tx,
						$request_data->localtax2_tx,
						'HT',
						$request_data->info_bits,
						$request_data->date_start,
						$request_data->date_end,
						$request_data->product_type,
						$request_data->fk_parent_line,
						0,
						$request_data->fk_fournprice,
						$request_data->pa_ht,
						$request_data->label,
						$request_data->special_code,
						$request_data->array_options,
						$request_data->fk_unit
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}
		return false;
	}*/


	/**
	 * Update task general fields (won't touch time spent of task)
	 *
	 * @param 	int   	$id             	Id of task to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->task->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Task not found');
		}

		if (!DolibarrApi::_checkAccessToResource('task', $this->task->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->task->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->task->array_options[$index] = $this->_checkValForAPI($field, $val, $this->task);
				}
				continue;
			}

			$this->task->$field = $this->_checkValForAPI($field, $value, $this->task);
		}

		if ($this->task->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->task->error);
		}
	}

	/**
	 * Delete task
	 *
	 * @param   int     $id         Task ID
	 *
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->task->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Task not found');
		}

		if (!DolibarrApi::_checkAccessToResource('task', $this->task->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->task->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete task : '.$this->task->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Task deleted'
			)
		);
	}


	/**
	 * Add time spent to a task of a project.
	 * You can test this API with the following input message
	 * { "date": "2016-12-31 23:15:00", "duration": 1800, "user_id": 1, "note": "My time test" }
	 *
	 * @param   int         $id                 Task ID
	 * @param   datetime    $date               Date (YYYY-MM-DD HH:MI:SS in GMT)
	 * @param   int         $duration           Duration in seconds (3600 = 1h)
	 * @param   int         $user_id            User (Use 0 for connected user)
	 * @param   string      $note               Note
	 *
	 * @url POST    {id}/addtimespent
	 *      NOTE: Should be "POST {id}/timespent", since POST already implies "add"
	 *
	 * @return  array
	 */
	public function addTimeSpent($id, $date, $duration, $user_id = 0, $note = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->task->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'Task not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->task->fk_project)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$uid = $user_id;
		if (empty($uid)) {
			$uid = DolibarrApiAccess::$user->id;
		}

		$newdate = dol_stringtotime($date, 1);
		$this->task->timespent_date = $newdate;
		$this->task->timespent_datehour = $newdate;
		$this->task->timespent_withhour = 1;
		$this->task->timespent_duration = $duration;
		$this->task->timespent_fk_user  = $uid;
		$this->task->timespent_note     = $note;

		$result = $this->task->addTimeSpent(DolibarrApiAccess::$user, 0);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when adding time: '.$this->task->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Time spent added'
			)
		);
	}

	/**
	 * Update time spent for a task of a project.
	 * You can test this API with the following input message
	 * { "date": "2016-12-31 23:15:00", "duration": 1800, "user_id": 1, "note": "My time test" }
	 *
	 * @param   int         $id                 Task ID
	 * @param   int         $timespent_id       Time spent ID (llx_element_time.rowid)
	 * @param   datetime    $date               Date (YYYY-MM-DD HH:MI:SS in GMT)
	 * @param   int         $duration           Duration in seconds (3600 = 1h)
	 * @param   int         $user_id            User (Use 0 for connected user)
	 * @param   string      $note               Note
	 *
	 * @url PUT    {id}/timespent/{timespent_id}
	 *
	 * @return  array
	 */
	public function putTimeSpent($id, $timespent_id, $date, $duration, $user_id = 0, $note = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}
		$this->timespentRecordChecks($id, $timespent_id);

		if (!DolibarrApi::_checkAccessToResource('task', $this->task->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$newdate = dol_stringtotime($date, 1);
		$this->task->timespent_date = $newdate;
		$this->task->timespent_datehour = $newdate;
		$this->task->timespent_withhour = 1;
		$this->task->timespent_duration = $duration;
		$this->task->timespent_fk_user  = $user_id ?? DolibarrApiAccess::$user->id;
		$this->task->timespent_note     = $note;

		$result = $this->task->updateTimeSpent(DolibarrApiAccess::$user, 0);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done.');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when updating time spent: '.$this->task->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Time spent updated'
			)
		);
	}

	/**
	 * Delete time spent for a task of a project.
	 *
	 * @param   int         $id                 Task ID
	 * @param   int         $timespent_id       Time spent ID (llx_element_time.rowid)
	 *
	 * @url DELETE    {id}/timespent/{timespent_id}
	 *
	 * @return  array
	 */
	public function deleteTimeSpent($id, $timespent_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'supprimer')) {
			throw new RestException(403);
		}
		$this->timespentRecordChecks($id, $timespent_id);

		if (!DolibarrApi::_checkAccessToResource('task', $this->task->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($this->task->delTimeSpent(DolibarrApiAccess::$user, 0) < 0) {
			throw new RestException(500, 'Error when deleting time spent: '.$this->task->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Time spent deleted'
			)
		);
	}

	/**
	 * Validate task & timespent IDs for timespent API methods.
	 * Loads the selected task & timespent records.
	 *
	 * @param   int         $id                 Task ID
	 * @param   int         $timespent_id       Time spent ID (llx_element_time.rowid)
	 *
	 * @return void
	 */
	protected function timespentRecordChecks($id, $timespent_id)
	{
		if ($this->task->fetch($id) <= 0) {
			throw new RestException(404, 'Task not found');
		}
		if ($this->task->fetchTimeSpent($timespent_id) <= 0) {
			throw new RestException(404, 'Timespent not found');
		} elseif ($this->task->id != $id) {
			throw new RestException(404, 'Timespent not found in selected task');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->fk_delivery_address);
		unset($object->shipping_method_id);
		unset($object->fk_account);
		unset($object->note);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->mode_reglement_id);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);

		unset($object->weekWorkLoad);
		unset($object->weekWorkLoad);

		//unset($object->lines);            // for task we use timespent_lines, but for project we use lines

		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);

		unset($object->comments);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		$object = array();
		foreach (self::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$object[$field] = $data[$field];
		}
		return $object;
	}


	// \todo
	// getSummaryOfTimeSpent
}
