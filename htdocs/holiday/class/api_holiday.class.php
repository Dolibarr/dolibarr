<?php
/* Copyright (C) 2025   MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025   Jessica Kowal		<jessicakowal69@gmail.com>
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

require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';


/**
 * API class for Holidays
 *
 * @since	19.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Holidayapi extends DolibarrApi
{
	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_user',
		'date_debut',
		'date_fin',
		'fk_type'
	);

	/**
	 * @var Holiday {@type Holiday}
	 */
	public $holiday;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->holiday = new Holiday($this->db);
	}

	/**
	 * Get a holiday
	 *
	 * Return an array with Holiday information
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id		ID of Holiday
	 * @return	Object			Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'read')) {
			throw new RestException(403);
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->holiday->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->holiday);
	}

	/**
	 * List holidays
	 *
	 * Get a list of Holidays
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	string		$sortfield			Sort field
	 * @param	string		$sortorder			Sort order
	 * @param	int			$limit				List limit
	 * @param	int			$page				Page number
	 * @param	string		$user_ids   		User ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param	string		$status				Status filter field. Example: '1' or '1,2,3'
	 * @param	string		$sqlfilters 		Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'HO-%') and (t.date_creation:<:'20160101')"
	 * @param	string		$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0
	 * @return	array							Array of holiday objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = '', $status = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday AS t LEFT JOIN ".MAIN_DB_PREFIX."holiday_extrafields AS ef ON (ef.fk_object = t.rowid)";
		$sql .= ' WHERE t.entity IN ('.getEntity('holiday').')';

		if ($user_ids) {
			$sql .= " AND t.fk_user IN (".$this->db->sanitize($user_ids).")";
		}

		if ($status) {
			$sql .= " AND t.statut IN (".$this->db->sanitize($status).")";
		}

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		// This query will return total holidays with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$holiday_static = new Holiday($this->db);
				if ($holiday_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($holiday_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve Holiday list : '.$this->db->lasterror());
		}

		// If $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
		if ($pagination_data) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = [];

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = [
				'total' => (int) $total,
				'page' => $page, //count starts from 0
				'page_count' => ceil((int) $total / $limit),
				'limit' => $limit
			];
		}

		return $obj_ret;
	}

	/**
	 * Create a holiday
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	array	$request_data	Request data
	 * @return	int						ID of Holiday
	 *
	 * @throws RestException
	 */
	public function post($request_data = null)
	{
		dol_syslog("POST /holidays called with data: " . json_encode($request_data), LOG_DEBUG);
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403, "Insufficient rights");
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->holiday->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->holiday->$field = $this->_checkValForAPI($field, $value, $this->holiday);
		}

		if ($this->holiday->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating holiday", array_merge(array($this->holiday->error), $this->holiday->errors));
		}

		return $this->holiday->id;
	}

	/**
	 * Update holiday general fields
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id					ID of Holiday to update
	 * @param	array	$request_data		Holiday data
	 * @return	Object						Updated object
	 *
	 * @throws	RestException	401		Not allowed
	 * @throws  RestException	404		Holiday not found
	 * @throws	RestException	500		System error
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403);
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->holiday->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->holiday->array_options[$index] = $this->_checkValForAPI($field, $val, $this->holiday);
				}
				continue;
			}

			$this->holiday->$field = $this->_checkValForAPI($field, $value, $this->holiday);
		}

		if ($this->holiday->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->holiday->error);
		}
	}

	/**
	 * Delete holiday
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id		Holiday ID
	 * @return	array
	 *
	 * @throws RestException
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'delete')) {
			throw new RestException(403);
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->holiday->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Holiday : '.$this->holiday->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Holiday deleted'
			)
		);
	}

	/**
	 * Validate a holiday
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id				Holiday ID
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *
	 * @url		POST	{id}/validate
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403, "Insufficient rights");
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->holiday->status = Holiday::STATUS_VALIDATED;
		$result = $this->holiday->validate(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating holiday: '.$this->holiday->error);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}

	/**
	 * Approve a holiday
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id				Holiday ID
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *
	 * @url		POST	{id}/approve
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function approve($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'approve')) {
			throw new RestException(403, "Insufficient rights");
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->holiday->status = Holiday::STATUS_APPROVED;
		$result = $this->holiday->approve(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already approved');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when approving holiday: '.$this->holiday->error);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}

	/**
	 * Cancel a holiday
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id				Holiday ID
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *
	 * @url		POST	{id}/cancel
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function cancel($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403, "Insufficient rights");
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->holiday->status = Holiday::STATUS_CANCELED;
		$result = $this->holiday->update(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already canceled');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when canceling holiday: '.$this->holiday->error);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}

	/**
	 * Refuse a holiday
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$id				Holiday ID
	 * @param	string	$detail_refuse	Comments for refusal
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *
	 * @url		POST	{id}/refuse
	 *
	 * @return	Object
	 *
	 * @throws RestException
	 */
	public function refuse($id, $detail_refuse, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403, "Insufficient rights");
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Holiday not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->holiday->status = Holiday::STATUS_REFUSED;
		$this->holiday->detail_refuse = $detail_refuse;
		$result = $this->holiday->update(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already refused');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when refusing holiday: '.$this->holiday->error);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}

	/**
	 * Get holiday types
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$active		Filter active types (1=active, 0=inactive, -1=all)
	 * @return	array				Array of holiday types
	 *
	 * @url     GET /types
	 *
	 * @throws RestException
	 */
	public function getTypes($active = 1)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'read')) {
			throw new RestException(403);
		}

		$holiday = new Holiday($this->db);
		$types = $holiday->getTypes($active, -1);

		return $types;
	}

	/**
	 * Get user's holiday balance
	 *
	 * @since	19.0.0	Initial implementation
	 *
	 * @param	int		$user_id	User ID (optional, current user if not provided)
	 * @param	int		$fk_type	Type ID (optional)
	 * @return	array				Balance information
	 *
	 * @url     GET /balance
	 *
	 * @throws RestException
	 */
	public function getBalance($user_id = 0, $fk_type = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'read')) {
			throw new RestException(403);
		}

		if (empty($user_id)) {
			$user_id = DolibarrApiAccess::$user->id;
		}

		$holiday = new Holiday($this->db);
		$balance = $holiday->getCPforUser($user_id, $fk_type);

		return array(
			'user_id' => (int) $user_id,
			'fk_type' => (int) $fk_type,
			'balance' => $balance
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

		unset($object->fk_statut);
		unset($object->statut);
		unset($object->user);
		unset($object->thirdparty);

		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);

		unset($object->note); // We already use note_public and note_private

		// Remove sensitive database information
		unset($object->db);
		unset($object->error);
		unset($object->errors);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array	$data	Array with data to verify
	 * @return	array
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		$holiday = array();
		foreach (Holidayapi::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$holiday[$field] = $data[$field];
		}
		return $holiday;
	}
}
