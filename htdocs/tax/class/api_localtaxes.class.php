<?php
/*
 * Copyright (C) 2026 Xabier Trigo Losada <xabi@galicloud.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

/**
 * API class for local taxes (RE / IRPF)
 *
 * @property DoliDB $db
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class LocalTaxes extends DolibarrApi
{
	/**
	 * @var string[] Mandatory fields, checked when creating an object
	 */
	public static $FIELDS = array(
		'localtaxtype',
		'datep',
		'datev',
		'amount',
		'label',
	);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
	}

	/**
	 * Get properties of a local tax object.
	 *
	 * @param	int		$id		ID of the local tax
	 * @return  Object			Object with cleaned properties
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Local tax not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'lire')) {
			throw new RestException(403);
		}

		$localtax = new Localtax($this->db);
		$result = $localtax->fetch($id);
		// Localtax::fetch() returns 1 even when the record does not exist,
		// so also check that the id was actually loaded.
		if ($result <= 0 || empty($localtax->id)) {
			throw new RestException(404, 'Local tax not found');
		}

		return $this->_cleanObjectDatas($localtax);
	}

	/**
	 * List local taxes (RE / IRPF).
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.datep:>=:'20240101') and (t.amount:<:100)"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0
	 * @return array						Array of local tax objects
	 * @phan-return Localtax[]|array{data:Localtax[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return Localtax[]|array{data:Localtax[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @throws RestException 400 Bad value for sqlfilters
	 * @throws RestException 403 Access denied
	 * @throws RestException 503 Error when retrieving list
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."localtax AS t";
		$sql .= " WHERE t.entity IN (".getEntity('localtax').")";

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		// This query will count total records matching the filters (before pagination)
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

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

		if (!$result) {
			throw new RestException(503, 'Error when retrieving list of local taxes: '.$this->db->lasterror());
		}

		$num = $this->db->num_rows($result);
		$min = min($num, ($limit <= 0 ? $num : $limit));
		for ($i = 0; $i < $min; $i++) {
			$obj = $this->db->fetch_object($result);
			$localtax = new Localtax($this->db);
			if ($localtax->fetch($obj->rowid) > 0) {
				$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($localtax), $properties);
			}
		}

		// If $pagination_data is true, the response will contain the element data with all values
		// and the element pagination with pagination data (total, page, page_count, limit)
		if ($pagination_data) {
			$totalsResult = $this->db->query($sqlTotals);
			$total = $this->db->fetch_object($totalsResult)->total;

			$tmp = $obj_ret;
			$obj_ret = array();

			$obj_ret['data'] = $tmp;
			$obj_ret['pagination'] = array(
				'total' => (int) $total,
				'page' => $page, // count starts from 0
				'page_count' => ($limit > 0 ? (int) ceil((int) $total / $limit) : 1),
				'limit' => $limit
			);
		}

		return $obj_ret;
	}

	/**
	 * Create a local tax.
	 *
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int						ID of the created local tax
	 *
	 * @throws RestException 400 Missing mandatory field
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 Error when creating the local tax
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'creer')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$this->_validate($request_data);

		$localtax = new Localtax($this->db);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$localtax->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			// The business class stores the local tax type in property "ltt" (int)
			if ($field == 'localtaxtype') {
				$localtax->ltt = (int) $value;
				continue;
			}

			$localtax->$field = $this->_checkValForAPI($field, $value, $localtax);
		}

		$localtax->fk_user_creat = DolibarrApiAccess::$user->id;
		if (empty($localtax->tms)) {
			$localtax->tms = dol_now();
		}

		if ($localtax->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when creating local tax: '.$localtax->error);
		}

		return $localtax->id;
	}

	/**
	 * Update a local tax.
	 *
	 * @param	int		$id				ID of the local tax
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return  Object					Updated object with cleaned properties
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Local tax not found
	 * @throws RestException 500 Error when updating the local tax
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'creer')) {
			throw new RestException(403);
		}

		$localtax = new Localtax($this->db);
		$result = $localtax->fetch($id);
		// Localtax::fetch() returns 1 even when the record does not exist,
		// so also check that the id was actually loaded.
		if ($result <= 0 || empty($localtax->id)) {
			throw new RestException(404, 'Local tax not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$localtax->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			// The business class stores the local tax type in property "ltt" (int)
			if ($field == 'localtaxtype') {
				$localtax->ltt = (int) $value;
				continue;
			}

			$localtax->$field = $this->_checkValForAPI($field, $value, $localtax);
		}

		$localtax->fk_user_modif = DolibarrApiAccess::$user->id;

		if ($localtax->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, 'Error when updating local tax: '.$localtax->error);
		}
	}

	/**
	 * Delete a local tax.
	 *
	 * @param	int		$id		ID of the local tax
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Local tax not found
	 * @throws RestException 500 Error when deleting the local tax
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'supprimer')) {
			throw new RestException(403);
		}

		$localtax = new Localtax($this->db);
		$result = $localtax->fetch($id);
		// Localtax::fetch() returns 1 even when the record does not exist,
		// so also check that the id was actually loaded.
		if ($result <= 0 || empty($localtax->id)) {
			throw new RestException(404, 'Local tax not found');
		}

		if ($localtax->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting local tax: '.$localtax->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Local tax deleted'
			)
		);
	}

	/**
	 * Validate fields before creating an object
	 *
	 * @param	?array<string,string>	$data	Data to validate
	 * @return	array<string,string>
	 *
	 * @throws RestException 400 Missing mandatory field
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$localtax = array();
		foreach (LocalTaxes::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$localtax[$field] = $data[$field];
		}
		return $localtax;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensitive object data
	 *
	 * @param	Object	$object		Object to clean
	 * @return	Object				Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->statut);
		unset($object->labelStatus);
		unset($object->labelStatusShort);

		return $object;
	}
}
