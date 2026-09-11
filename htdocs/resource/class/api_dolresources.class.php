<?php
/* Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2026		Dolicraft				<contact@dolicraft.com>
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

require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

/**
 * \file    htdocs/resource/class/api_dolresources.class.php
 * \ingroup resource
 * \brief   File for API management of resources.
 */

/**
 * API class for resources
 *
 * The class is named Dolresources and not Resources because "resources" is used by the
 * API explorer itself (Luracast\Restler\Resources), so a class named Resources would
 * conflict with it and the endpoint would not be listed.
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Dolresources extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create object
	 */
	public static $FIELDS = array(
		'ref'
	);

	/**
	 * @var Dolresource {@type Dolresource}
	 */
	public $resource;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->resource = new Dolresource($this->db);
	}

	/**
	 * Get properties of a resource object by id
	 *
	 * Return an array with resource information.
	 *
	 * @param	int		$id				ID of resource
	 * @return	array|mixed				Data without useless information
	 *
	 * @url	GET {id}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Resource not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(403);
		}

		$result = $this->resource->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'Resource not found');
		}

		if (!DolibarrApi::_checkAccessToResource('resource', $this->resource->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->resource);
	}

	/**
	 * List resources
	 *
	 * Get a list of resources
	 *
	 * @param	string	$sortfield			Sort field
	 * @param	string	$sortorder			Sort order
	 * @param	int		$limit				Limit for list
	 * @param	int		$page				Page number
	 * @param	string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'A%')"
	 * @param	string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return	array						Array of resource objects
	 * @phan-return Dolresource[]
	 * @phpstan-return Dolresource[]
	 *
	 * @throws RestException 400 Error on the sqlfilters
	 * @throws RestException 403 Access denied
	 * @throws RestException 503 Error when retrieving the list
	 */
	public function index($sortfield = "t.ref", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".$this->db->prefix()."resource as t";
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

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if (!$result) {
			throw new RestException(503, 'Error when retrieving resource list: '.$this->db->lasterror());
		}

		$num = $this->db->num_rows($result);
		$min = min($num, ($limit <= 0 ? $num : $limit));
		$i = 0;
		while ($i < $min) {
			$obj = $this->db->fetch_object($result);
			$resource_static = new Dolresource($this->db);
			if ($resource_static->fetch($obj->rowid) > 0) {
				$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($resource_static), $properties);
			}
			$i++;
		}

		return $obj_ret;
	}

	/**
	 * Create resource object
	 *
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int						ID of resource
	 *
	 * @throws RestException 400 Mandatory field missing
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 Error when creating the resource
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->resource->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->resource->$field = $this->_checkValForAPI($field, $value, $this->resource);
		}

		// Note: create() returns the id of the new resource on success, and a positive
		// count of errors on failure, so the result must be compared to the id and not to 0.
		$result = $this->resource->create(DolibarrApiAccess::$user);
		if ($result <= 0 || $result != $this->resource->id) {
			throw new RestException(500, "Error creating resource", array_merge(array($this->resource->error), $this->resource->errors));
		}

		return $this->resource->id;
	}

	/**
	 * Update resource
	 *
	 * @param	int		$id				ID of resource to update
	 * @param	array	$request_data	Datas
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	array|mixed				Data without useless information
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Resource not found
	 * @throws RestException 500 Error when updating the resource
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(403);
		}

		$result = $this->resource->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'Resource not found');
		}

		if (!DolibarrApi::_checkAccessToResource('resource', $this->resource->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
					$this->resource->array_options[$index] = $this->_checkValForAPI($field, $val, $this->resource);
				}
				continue;
			}

			$this->resource->$field = $this->_checkValForAPI($field, $value, $this->resource);
		}

		if ($this->resource->update(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, $this->resource->error);
		}

		return $this->get($id);
	}

	/**
	 * Delete resource
	 *
	 * @param	int		$id		Resource ID
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Resource not found
	 * @throws RestException 500 Error when deleting the resource
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'delete')) {
			throw new RestException(403);
		}

		$result = $this->resource->fetch($id);
		if ($result <= 0) {
			throw new RestException(404, 'Resource not found');
		}

		if (!DolibarrApi::_checkAccessToResource('resource', $this->resource->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($this->resource->delete(DolibarrApiAccess::$user) <= 0) {
			throw new RestException(500, 'Error when deleting resource: '.$this->resource->error);
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
	 * @param	Object	$object		Object to clean
	 * @return	Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->cache_code_type_resource);
		unset($object->objelement);
		unset($object->type_label);

		// Properties of the link between an element and a resource, only filled by
		// fetchElementResource() and not by fetch()
		unset($object->resource_id);
		unset($object->resource_type);
		unset($object->element_id);
		unset($object->element_type);
		unset($object->busy);
		unset($object->mandatory);
		unset($object->fulldayevent);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	?array<string,string>	$data	Datas to validate
	 * @return	array<string,string>
	 *
	 * @throws RestException 400 Mandatory field missing
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$resource = array();
		foreach (Dolresources::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$resource[$field] = $data[$field];
		}

		return $resource;
	}
}
