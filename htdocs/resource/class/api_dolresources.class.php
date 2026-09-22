<?php
/* Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2026		Dolicraft				<contact@dolicraft.com>
 * Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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

		$this->resource->oldcopy = dol_clone($this->resource, 2);  // @phan-suppress-current-line PhanTypeMismatchProperty

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

	/**
	 * Get the resources linked to an element
	 *
	 * Return the links stored in llx_element_resources for an element, so the endpoint works for
	 * any element type that can carry resources (agenda events, interventions, ...) and not only
	 * for one module.
	 *
	 * @param	string	$element_type	Type of the element, as stored in element_type (for example "action" for an agenda event)
	 * @param	int		$element_id		ID of the element
	 * @return	array					Array of links
	 * @phan-return array<int,array{id:int,resource_id:int,resource_type:string,busy:int,mandatory:int,resource:array<string,mixed>|null}>
	 * @phpstan-return array<int,array{id:int,resource_id:int,resource_type:string,busy:int,mandatory:int,resource:array<string,mixed>|null}>
	 *
	 * @url	GET {element_type}/{element_id}/resources
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Element not found
	 */
	public function getElementResources($element_type, $element_id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(403);
		}

		$element = $this->_fetchElement($element_type, $element_id);

		$result = array();
		foreach ($this->resource->getElementResources($element->element, $element->id) as $link) {
			$result[] = $this->_formatLink($link);
		}

		return $result;
	}

	/**
	 * Link a resource to an element
	 *
	 * @param	string	$element_type	Type of the element, as stored in element_type (for example "action" for an agenda event)
	 * @param	int		$element_id		ID of the element
	 * @param	array	$request_data	Request data. Mandatory: resource_id. Optional: busy, mandatory
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	array					The created link
	 * @phan-return array{id:int,resource_id:int,resource_type:string,busy:int,mandatory:int,resource:array<string,mixed>|null}
	 * @phpstan-return array{id:int,resource_id:int,resource_type:string,busy:int,mandatory:int,resource:array<string,mixed>|null}
	 *
	 * @url	POST {element_type}/{element_id}/resources
	 *
	 * @throws RestException 400 Mandatory field missing
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Element or resource not found
	 * @throws RestException 409 Resource already linked to this element, or already busy on the period
	 * @throws RestException 500 Error when creating the link
	 */
	public function postElementResources($element_type, $element_id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'write')) {
			throw new RestException(403);
		}

		if ($request_data === null) {
			$request_data = array();
		}
		if (!isset($request_data['resource_id'])) {
			throw new RestException(400, "resource_id field missing");
		}

		$element = $this->_fetchElement($element_type, $element_id);

		$resource_id = (int) $request_data['resource_id'];
		if ($this->resource->fetch($resource_id) <= 0) {
			throw new RestException(404, 'Resource not found');
		}

		$busy = empty($request_data['busy']) ? 0 : 1;
		$mandatory = empty($request_data['mandatory']) ? 0 : 1;

		// The unique index of llx_element_resources rejects a duplicate, so the case is detected
		// here to answer 409 instead of letting the insert fail with a 500.
		foreach ($this->resource->getElementResources($element->element, $element->id) as $existing) {
			if ((int) $existing['resource_id'] == $resource_id && $existing['resource_type'] == $this->resource->element) {
				throw new RestException(409, 'Resource already linked to this element');
			}
		}

		// A busy link books the resource, so the same double booking check as the interface is
		// applied. getBookingConflicts() returns -1 on SQL error, an array otherwise.
		if ($busy) {
			$conflicts = $this->_getBookingConflicts($element, $resource_id);
			if (!empty($conflicts)) {
				throw new RestException(409, 'Resource already used on this period by: '.$this->_describeConflicts($conflicts));
			}
		}

		if ($element->add_element_resource($resource_id, $this->resource->element, $busy, $mandatory) <= 0) {
			throw new RestException(500, 'Error when linking resource: '.$element->error);
		}

		foreach ($this->resource->getElementResources($element->element, $element->id) as $link) {
			if ((int) $link['resource_id'] == $resource_id && $link['resource_type'] == $this->resource->element) {
				return $this->_formatLink($link);
			}
		}

		throw new RestException(500, 'Link created but not found back');
	}

	/**
	 * Unlink a resource from an element
	 *
	 * @param	string	$element_type	Type of the element, as stored in element_type (for example "action" for an agenda event)
	 * @param	int		$element_id		ID of the element
	 * @param	int		$id				ID of the link to delete (the id returned by the GET, not the id of the resource)
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url	DELETE {element_type}/{element_id}/resources/{id}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Element or link not found
	 * @throws RestException 500 Error when deleting the link
	 */
	public function deleteElementResources($element_type, $element_id, $id)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'delete')) {
			throw new RestException(403);
		}

		$element = $this->_fetchElement($element_type, $element_id);

		// delete_resource() deletes by rowid without checking the row belongs to the element, so
		// the link is looked up on the element first to not allow deleting the link of another one.
		$found = false;
		foreach ($this->resource->getElementResources($element->element, $element->id) as $link) {
			if ((int) $link['rowid'] == (int) $id) {
				$found = true;
				break;
			}
		}
		if (!$found) {
			throw new RestException(404, 'Link not found for this element');
		}

		if ($element->delete_resource($id, $element->element) <= 0) {
			throw new RestException(500, 'Error when unlinking resource: '.$element->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Resource unlinked'
			)
		);
	}

	/**
	 * Get the bookings that conflict with a period for a resource
	 *
	 * Answers the availability question without having to create a link first. Only the links
	 * flagged busy are considered, as in the interface.
	 *
	 * @param	int		$id				ID of resource
	 * @param	string	$date_start		Start of the period, format YYYY-MM-DD HH:MM:SS or a Unix timestamp
	 * @param	string	$date_end		End of the period, format YYYY-MM-DD HH:MM:SS or a Unix timestamp
	 * @return	array					Conflicting bookings, empty if the resource is free
	 * @phan-return array<int,array{element_type:string,element_id:int,ref:string}>
	 * @phpstan-return array<int,array{element_type:string,element_id:int,ref:string}>
	 *
	 * @url	GET {id}/bookingconflicts
	 *
	 * @throws RestException 400 Bad date
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Resource not found
	 * @throws RestException 500 Error when searching the conflicts
	 */
	public function getBookingConflicts($id, $date_start, $date_end)
	{
		if (!DolibarrApiAccess::$user->hasRight('resource', 'read')) {
			throw new RestException(403);
		}

		if ($this->resource->fetch($id) <= 0) {
			throw new RestException(404, 'Resource not found');
		}

		$start = $this->_toTimestamp($date_start, 'date_start');
		$end = $this->_toTimestamp($date_end, 'date_end');
		if ($end < $start) {
			throw new RestException(400, 'date_end is before date_start');
		}

		$conflicts = $this->resource->getBookingConflicts($this->resource->id, $this->resource->element, $start, $end);
		if (!is_array($conflicts)) {
			throw new RestException(500, 'Error when searching the conflicts: '.$this->resource->error);
		}

		return $conflicts;
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
	 * Load the element carrying the resources and check the access to it
	 *
	 * @param	string	$element_type	Type of the element, as stored in element_type
	 * @param	int		$element_id		ID of the element
	 * @return	CommonObject			The loaded element
	 *
	 * @throws RestException 400 Bad element type
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Element not found
	 */
	private function _fetchElement($element_type, $element_id)
	{
		$element_type = (string) $element_type;
		if ($element_type === '' || !preg_match('/^[a-z0-9_]+$/i', $element_type)) {
			throw new RestException(400, 'Bad value for parameter element_type');
		}
		if ((int) $element_id <= 0) {
			throw new RestException(400, 'Bad value for parameter element_id');
		}

		$element = fetchObjectByElement((int) $element_id, $element_type);
		if (!is_object($element) || empty($element->id)) {
			throw new RestException(404, 'Element '.$element_type.' not found');
		}

		// Linking a resource writes on the element, so the access to the element itself is checked
		// and not only the permissions on the resources.
		if (!DolibarrApi::_checkAccessToResource($element_type, $element->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $element;
	}

	/**
	 * Format a link of llx_element_resources for the answer
	 *
	 * @param	array<string,mixed>	$link	Link as returned by getElementResources()
	 * @return	array<string,mixed>			Formatted link
	 */
	private function _formatLink($link)
	{
		$resource = null;
		$resource_static = new Dolresource($this->db);
		if ($resource_static->fetch((int) $link['resource_id']) > 0) {
			$resource = $this->_cleanObjectDatas($resource_static);
		}

		return array(
			'id' => (int) $link['rowid'],
			'resource_id' => (int) $link['resource_id'],
			'resource_type' => $link['resource_type'],
			'busy' => (int) $link['busy'],
			'mandatory' => (int) $link['mandatory'],
			'resource' => $resource
		);
	}

	/**
	 * Return the bookings conflicting with the period of an element for a resource
	 *
	 * @param	CommonObject	$element		Element being linked
	 * @param	int				$resource_id	ID of resource
	 * @return	array<int,array{element_type:string,element_id:int,ref:string}>	Conflicting bookings, empty if none or if the element has no usable period
	 *
	 * @throws RestException 500 Error when searching the conflicts
	 */
	private function _getBookingConflicts($element, $resource_id)
	{
		$date_start = 0;
		$date_end = 0;

		// Only the agenda event carries a usable period on the object itself. The other elements
		// are linked without a booking check, as the interface does.
		if ($element->element == 'action') {
			/** @var ActionComm $element */
			'@phan-var-force ActionComm $element';
			$date_start = empty($element->datep) ? 0 : $element->datep;
			$date_end = empty($element->datef) ? $date_start : $element->datef;
			if ($date_start && !empty($element->fulldayevent)) {
				$parts = dol_getdate((int) $date_start);
				$date_start = dol_mktime(0, 0, 0, $parts['mon'], $parts['mday'], $parts['year']);
				$date_end = dol_mktime(23, 59, 59, $parts['mon'], $parts['mday'], $parts['year']);
			}
		}

		if (empty($date_start)) {
			return array();
		}

		$conflicts = $this->resource->getBookingConflicts($resource_id, $this->resource->element, $date_start, $date_end, $element->element, $element->id);
		if (!is_array($conflicts)) {
			throw new RestException(500, 'Error when searching the conflicts: '.$this->resource->error);
		}

		return $conflicts;
	}

	/**
	 * Describe conflicting bookings for an error message
	 *
	 * @param	array<int,array{element_type:string,element_id:int,ref:string}>	$conflicts	Conflicting bookings
	 * @return	string															Description
	 */
	private function _describeConflicts($conflicts)
	{
		$out = array();
		foreach ($conflicts as $conflict) {
			$out[] = $conflict['element_type'].' '.$conflict['element_id'].(empty($conflict['ref']) ? '' : ' ('.$conflict['ref'].')');
		}

		return implode(', ', $out);
	}

	/**
	 * Convert a date given to the API into a timestamp
	 *
	 * @param	string	$value	Date, format YYYY-MM-DD HH:MM:SS or a Unix timestamp
	 * @param	string	$name	Name of the parameter, for the error message
	 * @return	int				Timestamp
	 *
	 * @throws RestException 400 Bad date
	 */
	private function _toTimestamp($value, $name)
	{
		if (is_numeric($value)) {
			return (int) $value;
		}

		$timestamp = dol_stringtotime((string) $value);
		if (empty($timestamp)) {
			throw new RestException(400, 'Bad value for parameter '.$name);
		}

		return (int) $timestamp;
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
