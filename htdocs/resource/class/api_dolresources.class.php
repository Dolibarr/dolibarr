<?php
/* Copyright (C) 2026   Jessica Kowal		<jessicakowal69@gmail.com>
 * Copyright (C) 2026   Charlene Benke		<charlene@patas-monkey.com>
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
w
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';


/**
 * API class for Resources
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class Dolresources extends DolibarrApi
{
	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'ref',
		'fk_code_type_resource',
	);

	/**
	 * @var Dolresource $resource {@type Dolresource}
	 */
	public $resource;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->resource = new Dolresource($this->db);
	}


	/**
	 * Get properties of a resource object
	 *
	 * Return an array with resource information
	 *
	 * @param   int $id ID of resource
	 * @return  array|mixed Data without useless information
	 *
	 * @url GET {id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(401, "Insufficient rights to read resource");
		}

		$result = $this->resource->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Resource not found');
		}

		if ($result < 0) {
			throw new RestException(500, $this->resource->error);
		}

		return $this->_cleanObjectDatas($this->resource);
	}

	/**
	 * List resources
	 *
	 * Get a list of resources
	 *
	 * @param string   $sortfield  Sort field
	 * @param string   $sortorder  Sort order
	 * @param int	  $limit	  Limit for list
	 * @param int	  $page	   Page number
	 * @param string   $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return array			   Array of resource objects
	 *
	 * @url GET /
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 500 System error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(401, "Insufficient rights to read resources");
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->resource->table_element." as t";
		$sql .= " WHERE t.entity IN (".getEntity('resource').")";

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
			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < min($num, $limit)) {
				$obj = $this->db->fetch_object($result);
				$resource_static = new Dolresource($this->db);
				if ($resource_static->fetch($obj->rowid) > 0) {
					$obj_ret[] = $this->_cleanObjectDatas($resource_static);
				}
				$i++;
			}
		} else {
			throw new RestException(500, $this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create resource object
	 *
	 * @param array $request_data Request data
	 * @return int  ID of resource
	 *
	 * @url POST /
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 500 System error
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(401, "Insufficient rights to create resource");
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->resource->$field = $this->_checkValForAPI($field, $value, $this->resource);
		}

		if ($this->resource->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating resource", array_merge(array($this->resource->error), $this->resource->errors));
		}

		return $this->resource->id;
	}

	/**
	 * Update resource
	 *
	 * @param int   $id		   Id of resource to update
	 * @param array $request_data Datas
	 * @return array
	 *
	 * @url PUT {id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(401, "Insufficient rights to update resource");
		}

		$result = $this->resource->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Resource not found');
		}

		if ($result < 0) {
			throw new RestException(500, $this->resource->error);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->resource->$field = $this->_checkValForAPI($field, $value, $this->resource);
		}

		if ($this->resource->update(DolibarrApiAccess::$user, 1) < 0) {
			throw new RestException(500, "Error updating resource", array_merge(array($this->resource->error), $this->resource->errors));
		}

		return $this->_cleanObjectDatas($this->resource);
	}

	/**
	 * Delete resource
	 *
	 * @param int $id   Resource ID
	 * @return array
	 *
	 * @url DELETE {id}
	 *
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'delete')) {
			throw new RestException(401, "Insufficient rights to delete resource");
		}

		$result = $this->resource->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Resource not found');
		}

		if ($result < 0) {
			throw new RestException(500, $this->resource->error);
		}

		if ($this->resource->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error deleting resource", array_merge(array($this->resource->error), $this->resource->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Resource deleted'
			)
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Dolresource  $object	 Object to clean
	 * @return  Object			  Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->statut);
		unset($object->user);
		unset($object->thirdparty);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->logs);
		unset($object->events);
		unset($object->canvas);
		unset($object->lines);
		unset($object->note);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Array with data to verify
	 * @return array<string,string>
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$resource = array();
		foreach (self::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$resource[$field] = $data[$field];
		}
		return $resource;
	}
}
