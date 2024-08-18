<?php
/* Copyright (C) 2019       Thibault FOUCART        <support@ptibogxiv.net>
 * Copyright (C) 2019       Laurent Destailleur     <eldy@users.sourceforge.net>
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

require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

/**
 * API class for donations
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Donations extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'amount'
	);

	/**
	 * @var Don $don {@type Don}
	 */
	public $don;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->don = new Don($this->db);
	}

	/**
	 * Get properties of an donation object
	 *
	 * Return an array with donation information
	 *
	 * @param   int         $id         ID of order
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('don', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->don->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Donation not found');
		}

		if (!DolibarrApi::_checkAccessToResource('don', $this->don->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Add external contacts ids
		//$this->don->contacts_ids = $this->don->liste_contact(-1,'external',1);
		//$this->don->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->don);
	}



	/**
	 * List donations
	 *
	 * Get a list of donations
	 *
	 * @param string    $sortfield          Sort field
	 * @param string    $sortorder          Sort order
	 * @param int       $limit              Limit for list
	 * @param int       $page               Page number
	 * @param string    $thirdparty_ids     Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                       Array of order objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('don', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		$sql = "SELECT t.rowid";
		if ((!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids)) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."don AS t LEFT JOIN ".MAIN_DB_PREFIX."don_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields

		$sql .= ' WHERE t.entity IN ('.getEntity('don').')';
		if ((!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids)) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($thirdparty_ids) {
			$sql .= " AND t.fk_soc = ".((int) $thirdparty_ids)." ";
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

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$don_static = new Don($this->db);
				if ($don_static->fetch($obj->rowid)) {
					// Add external contacts ids
					//$don_static->contacts_ids = $don_static->liste_contact(-1, 'external', 1);
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($don_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve donation list : '.$this->db->lasterror());
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
	 * Create donation object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of order
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('don', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->don->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->don->$field = $this->_checkValForAPI($field, $value, $this->don);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->don->lines = $lines;
		}*/

		if ($this->don->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating donation", array_merge(array($this->don->error), $this->don->errors));
		}

		return $this->don->id;
	}

	/**
	 * Update order general fields (won't touch lines of order)
	 *
	 * @param 	int   	$id             	Id of order to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('don', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->don->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Donation not found');
		}

		if (!DolibarrApi::_checkAccessToResource('donation', $this->don->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->don->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->don->$field = $this->_checkValForAPI($field, $value, $this->don);
		}

		if ($this->don->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->don->error);
		}
	}

	/**
	 * Delete donation
	 *
	 * @param   int     $id         Order ID
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('don', 'supprimer')) {
			throw new RestException(403);
		}

		$result = $this->don->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Donation not found');
		}

		if (!DolibarrApi::_checkAccessToResource('donation', $this->don->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->don->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete donation : '.$this->don->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Donation deleted'
			)
		);
	}

	/**
	 * Validate an donation
	 *
	 * If you get a bad value for param notrigger check, provide this in body
	 * {
	 *   "idwarehouse": 0,
	 *   "notrigger": 0
	 * }
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @throws RestException 304
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 *
	 * @return  object
	 */
	public function validate($id, $idwarehouse = 0, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('don', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->don->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Donation not found');
		}

		if (!DolibarrApi::_checkAccessToResource('don', $this->don->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$result = $this->don->valid_promesse($id, DolibarrApiAccess::$user->id, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Order: '.$this->don->error);
		}
		$result = $this->don->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('don', $this->don->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->don->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->don);
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

		unset($object->note);
		unset($object->address);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

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
		$don = array();
		foreach (Donations::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$don[$field] = $data[$field];
		}
		return $don;
	}
}
