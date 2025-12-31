<?php
/* Copyright (C) 2015   	Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   	Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2020-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		William Mead			<william@m34d.com>
 * Copyright (C) 2025		Charlene Benke			<charlene@patas-monkey.com>
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
 * API class for Leaves
 *
 * @since	23.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Holidays extends DolibarrApi
{
	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_user',
		'date_debut',
		'date_fin',
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
	 * Get a leave
	 *
	 * Return an array with leave information
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id		ID of Leave
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
			throw new RestException(404, 'Leave not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->holiday->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->holiday);
	}

	/**
	 * List leaves
	 *
	 * Get a list of Leaves
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	string		$sortfield			Sort field
	 * @param	string		$sortorder			Sort order
	 * @param	int			$limit				List limit
	 * @param	int			$page				Page number
	 * @param	string		$user_ids   		User ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param	string		$sqlfilters 		Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param	string		$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return	array<string,mixed>				Array of order objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		// TODO Check on permission holiday->read only if all ID are inside the childids of user

		if (!DolibarrApiAccess::$user->hasRight('holiday', 'readall')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $societe param is ignored and replaced by user's socid
		//$socid = DolibarrApiAccess::$user->socid ?: $societe;

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday AS t LEFT JOIN ".MAIN_DB_PREFIX."holiday_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('holiday').')';
		if ($user_ids) {
			$sql .= " AND t.fk_user IN (".$this->db->sanitize($user_ids).")";
		}

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total orders with the filters given
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
			throw new RestException(503, 'Error when retrieve Leave list : '.$this->db->lasterror());
		}

		//if $pagination_data is true the response will contain element data with all values and element pagination with pagination data(total,page,limit)
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
	 * Create a leave
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int						ID of Leave
	 *
	 * @throws RestException
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403, "Insufficiant rights");
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
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->holiday->lines = $lines;
		}*/
		if ($this->holiday->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating holiday", array_merge(array($this->holiday->error), $this->holiday->errors));
		}

		return $this->holiday->id;
	}


	/**
	 * Update expense report general fields
	 *
	 * Does not touch lines of the expense report
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id					Leave ID to update
	 * @param	array	$request_data		Expense report data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object						Updated object
	 *
	 * @throws	RestException	401		Not allowed
	 * @throws  RestException	404		Expense report not found
	 * @throws	RestException	500		System error
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('holiday', 'write')) {
			throw new RestException(403);
		}

		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'holiday not found');
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
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id		Leave Report ID
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
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
			throw new RestException(404, 'Leave not found');
		}

		if (!DolibarrApi::_checkAccessToResource('holiday', $this->holiday->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->holiday->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting Leave : '.$this->holiday->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Leave deleted'
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
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id				Leave report ID
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
			throw new RestException(403, "Insufficiant rights");
		}
		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Leave not found');
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
			throw new RestException(500, 'Error when validating leave: '.$this->holiday->error);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}


	/**
	 * Approve a leave
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param	int		$id				Leave ID
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
			throw new RestException(403, "Insufficiant rights");
		}
		$result = $this->holiday->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Leave not found');
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
			throw new RestException(500, 'Error when approving expense report: '.$this->holiday->error);
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
	 * @since	23.0.0	Initial implementation
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
	 * @since	23.0.0	Initial implementation
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
	 * Reopen a canceled holiday
	 *
	 * This method allows to reopen a holiday that was previously canceled
	 * and set its status back to VALIDATED
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "notrigger": 0
	 * }
	 *
	 * @since   23.0.0   New endpoint
	 *
	 * @param   int     $id             Holiday ID
	 * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url     POST    {id}/reopen
	 *
	 * @return  Object
	 *
	 * @throws RestException
	 */
	public function reopen($id, $notrigger = 0)
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

		// Check if the holiday is actually canceled
		if ($this->holiday->statut != Holiday::STATUS_CANCELED) {
			throw new RestException(400, 'Holiday is not canceled. Only canceled holidays can be reopened.');
		}
		$this->holiday->status = Holiday::STATUS_VALIDATED;
		$result = $this->holiday->validate(DolibarrApiAccess::$user, $notrigger);
		if ($result < 0) {
			throw new RestException(500, 'Error when canceling holiday: '.$this->holiday->error);
		}

		return $this->_cleanObjectDatas($this->holiday);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param   Holiday  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);
		/**
		 * @var Holiday $object
		 */
		unset($object->statut);
		unset($object->user);
		unset($object->thirdparty);

		unset($object->cond_reglement);
		unset($object->shipping_method_id);

		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->cond_reglement_id);
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
		unset($object->holiday);
		unset($object->canvas);
		unset($object->lines);

		unset($object->totalpaid);
		unset($object->totalpaid_multicurrency);

		unset($object->note); // We already use note_public and note_pricate

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
		$expensereport = array();
		foreach (self::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$expensereport[$field] = $data[$field];
		}
		return $expensereport;
	}
}
