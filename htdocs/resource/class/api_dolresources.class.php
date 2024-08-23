<?php
/* Copyright (C) 2015		Jean-François Ferry			<jfefe@aternatik.fr>
 * Copyright (C) ---Replace with your own copyright and developer email---
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

require_once __DIR__ . '/dolresource.class.php';

/**
 * API class for mymodule myobject
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class DolResources extends DolibarrApi
{
	/**
	 * @var DolResource $resource {@type DolResource}
	 */
	/*
	 * @var mixed TODO: set type
	 */
	public DolResource $resource;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->resource = new DolResource($this->db);
	}


	/**
	 * Get properties of a resource object
	 *
	 * Return an array with resource information
	 *
	 * @param	int		$id				ID of resource
	 * @return  Object					Object with cleaned properties
	 * @phan-return	DolResource			Object with cleaned properties
	 * @phpstan-return	DolResource			Object with cleaned properties
	 *
	 * @phan-return  DolResource
	 *
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('resource', $id)) {
			throw new RestException(403, 'Access to instance id='.$id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->resource->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Resource not found');
		}

		return $this->_cleanObjectDatas($this->resource);
	}


	/**
	 * List resources
	 *
	 * Get a list of resources
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of Resource objects
	 * @phan-return array<int,DolResource>
	 * @phpstan-return array<int,DolResource>
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 503 System error
	 *
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		$obj_ret = array();
		$tmpobject = new DolResource($this->db);

		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(403);
		}

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : 0;

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}
		if (!isModEnabled('societe')) {
			$search_sale = 0; // If module thirdparty not enabled, sale representative is something that does not exists
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$tmpobject->table_element."_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= " WHERE 1 = 1";
		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
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

		$result = $this->db->query($sql);
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$tmp_object = new DolResource($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving resource list: '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create resource object
	 *
	 * @param array $request_data   Request data
	 * @phan-param array{string,mixed} $request_data
	 * @phpstan-param array{string,mixed} $request_data
	 * @return int  				ID of resource
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 500 System error
	 *
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$result = $this->_validateResource($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->resource->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->resource->array_options[$index] = $this->_checkValForAPI('extrafields', $val, $this->resource);
				}
				continue;
			}

			$this->resource->$field = $this->_checkValForAPI($field, $value, $this->resource);
		}

		// Clean data
		// $this->resource->abc = sanitizeVal($this->resource->abc, 'alphanohtml');

		if ($this->resource->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating Resource", array_merge(array($this->resource->error), $this->resource->errors));
		}
		return $this->resource->id;
	}

	/**
	 * Update resource
	 *
	 * @param 	int   		$id             Id of resource to update
	 * @param 	array 		$request_data   Data
	 * @phan-param mixed[]	$request_data
	 * @phpstan-param mixed[]	$request_data
	 * @return 	Object						Object after update
	 * @phan-return DolResource
	 * @phpstan-return DolResource
	 *
	 * @phan-return  DolResource
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 System error
	 *
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('resource', $id)) {
			throw new RestException(403, 'Access to instance id='.$this->resource->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->resource->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Resource not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->resource->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->resource->array_options[$index] = $this->_checkValForAPI('extrafields', $val, $this->resource);
				}
				continue;
			}

			$this->resource->$field = $this->_checkValForAPI($field, $value, $this->resource);
		}

		// Clean data
		// $this->resource->abc = sanitizeVal($this->resource->abc, 'alphanohtml');

		if ($this->resource->update(DolibarrApiAccess::$user, false) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->resource->error);
		}
	}

	/**
	 * Delete resource
	 *
	 * @param   int     $id   Resource ID
	 * @return  array
	 * @phan-return array<string,array{code:int,message:string}>
	 * @phpstan-return array<string,array{code:int,message:string}>
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 409 Nothing to do
	 * @throws RestException 500 System error
	 *
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'delete')) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('resource', $id)) {
			throw new RestException(403, 'Access to instance id='.$this->resource->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->resource->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Resource not found');
		}

		if ($this->resource->delete(DolibarrApiAccess::$user) == 0) {
			throw new RestException(409, 'Error when deleting Resource : '.$this->resource->error);
		} elseif ($this->resource->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting Resource : '.$this->resource->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Resource deleted'
			)
		);
	}


	/**
	 * Validate fields before creating or updating object
	 *
	 * @param	array		$data   Array of data to validate
	 * @phan-param array<string,null|int|float|string> $data
	 * @phpstan-param	array<string,null|int|float|string> $data
	 * @return	array
	 * @phan-return array<string,null|int|float|string>|array{}
	 * @phpstan-return array<string,null|int|float|string>|array{}
	 *
	 * @throws	RestException
	 */
	private function _validateResource($data)
	{
		$resource = array();
		foreach ($this->resource->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$resource[$field] = $data[$field];
		}
		return $resource;
	}



	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensitive object data fields
	 * @phpstan-template T of Object
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 *
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}
}
