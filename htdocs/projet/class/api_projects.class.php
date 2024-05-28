<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

 require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
 require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

/**
 * API class for projects
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Projects extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'ref',
		'title'
	);

	/**
	 * @var Project $project {@type Project}
	 */
	public $project;

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
		$this->project = new Project($this->db);
		$this->task = new Task($this->db);
	}

	/**
	 * Get properties of a project object
	 *
	 * Return an array with project information
	 *
	 * @param   int         $id         ID of project
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Project with supplied id not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->project->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->project);
	}

	/**
	 * Get properties of a project object
	 *
	 * Return an array with project information
	 *
	 * @param	string	$ref			Ref of project
	 * @return  Object					Object with cleaned properties
	 *
	 * @url GET ref/{ref}
	 *
	 * @throws	RestException
	 */
	public function getByRef($ref)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch('', $ref);
		if (!$result) {
			throw new RestException(404, 'Project with supplied ref not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->project->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->project);
	}

	/**
	 * Get properties of a project object
	 *
	 * Return an array with project information
	 *
	 * @param	string	$ref_ext			Ref_Ext of project
	 * @return  Object					Object with cleaned properties
	 *
	 * @url GET ref_ext/{ref_ext}
	 *
	 * @throws	RestException
	 */
	public function getByRefExt($ref_ext)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch('', '', $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Project with supplied ref_ext not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->project->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->project);
	}

	/**
	 * Get properties of a project object
	 *
	 * Return an array with project information
	 *
	 * @param	string	$email_msgid	Email msgid of project
	 * @return  Object					Object with cleaned properties
	 *
	 * @url GET email_msgid/{email_msgid}
	 *
	 * @throws	RestException
	 */
	public function getByMsgId($email_msgid)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch('', '', '', $email_msgid);
		if (!$result) {
			throw new RestException(404, 'Project with supplied email_msgid not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->project->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->project);
	}

	/**
	 * List projects
	 *
	 * Get a list of projects
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string		   $thirdparty_ids		Thirdparty ids to filter projects of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param  int    $category   Use this param to filter list by category
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of project objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $category = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields AS ef ON ef.fk_object = t.rowid";	// So we will be able to filter on extrafields
		if ($category > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."categorie_project as c";
		}
		$sql .= ' WHERE t.entity IN ('.getEntity('project').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Select projects of given category
		if ($category > 0) {
			$sql .= " AND c.fk_categorie = ".((int) $category)." AND c.fk_project = t.rowid ";
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
				$project_static = new Project($this->db);
				if ($project_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($project_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve project list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create project object
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
				$this->project->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->project->$field = $this->_checkValForAPI($field, $value, $this->project);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->project->lines = $lines;
		}*/
		if ($this->project->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating project", array_merge(array($this->project->error), $this->project->errors));
		}

		return $this->project->id;
	}

	/**
	 * Get tasks of a project.
	 * See also API /tasks
	 *
	 * @param int   $id                     Id of project
	 * @param int   $includetimespent       0=Return only list of tasks. 1=Include a summary of time spent, 2=Include details of time spent lines
	 * @return array
	 *
	 * @url	GET {id}/tasks
	 */
	public function getLines($id, $includetimespent = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Project not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->project->getLinesArray(DolibarrApiAccess::$user);
		$result = array();
		foreach ($this->project->lines as $line) {      // $line is a task
			if ($includetimespent == 1) {
				$timespent = $line->getSummaryOfTimeSpent(0);
			}
			if ($includetimespent == 2) {
				$timespent = $line->fetchTimeSpentOnTask();
			}
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}


	/**
	 * Get roles a user is assigned to a project with
	 *
	 * @param   int   $id             Id of project
	 * @param   int   $userid         Id of user (0 = connected user)
	 * @return array
	 *
	 * @url	GET {id}/roles
	 */
	public function getRoles($id, $userid = 0)
	{
		global $db;

		if (!DolibarrApiAccess::$user->hasRight('projet', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Project not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		$taskstatic = new Task($this->db);
		$userp = DolibarrApiAccess::$user;
		if ($userid > 0) {
			$userp = new User($this->db);
			$userp->fetch($userid);
		}
		$this->project->roles = $taskstatic->getUserRolesForProjectsOrTasks($userp, null, $id, 0);
		$result = array();
		foreach ($this->project->roles as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}

		return $result;
	}


	/**
	 * Add a task to given project
	 *
	 * @param int   $id             Id of project to update
	 * @param array $request_data   Projectline data
	 *
	 * @url	POST {id}/tasks
	 *
	 * @return int
	 */
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

	/**
	 * Update a task to given project
	 *
	 * @param int   $id             Id of project to update
	 * @param int   $taskid         Id of task to update
	 * @param array $request_data   Projectline data
	 *
	 * @url	PUT {id}/tasks/{taskid}
	 *
	 * @return object
	 */
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
	 * Update project general fields (won't touch lines of project)
	 *
	 * @param 	int   	$id             	Id of project to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->project->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'Project not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->project->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->project->array_options[$index] = $this->_checkValForAPI($field, $val, $this->project);
				}
				continue;
			}

			$this->project->$field = $this->_checkValForAPI($field, $value, $this->project);
		}

		if ($this->project->update(DolibarrApiAccess::$user) >= 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->project->error);
		}
	}

	/**
	 * Delete project
	 *
	 * @param   int     $id         Project ID
	 *
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->project->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Project not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->project->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete project : '.$this->project->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Project deleted'
			)
		);
	}

	/**
	 * Validate a project.
	 * You can test this API with the following input message
	 * { "notrigger": 0 }
	 *
	 * @param   int $id             Project ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "notrigger": 0
	 * }
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('projet', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->project->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Project not found');
		}

		if (!DolibarrApi::_checkAccessToResource('project', $this->project->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->project->setValid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Project: '.$this->project->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Project validated'
			)
		);
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

		unset($object->datec);
		unset($object->datem);
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


	// TODO
	// getSummaryOfTimeSpent
}
