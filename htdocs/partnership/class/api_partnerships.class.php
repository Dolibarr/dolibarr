<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

dol_include_once('/partnership/class/partnership.class.php');



/**
 * \file    partnership/class/api_partnerships.class.php
 * \ingroup partnership
 * \brief   File for API management of partnership.
 */

/**
 * API class for partnership partnership
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Partnerships extends DolibarrApi
{
	/**
	 * @var Partnership $partnership {@type Partnership}
	 */
	public $partnership;

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
		$this->partnership = new Partnership($this->db);
	}

	/**
	 * Get properties of a partnership object
	 *
	 * Return an array with partnership information
	 *
	 * @param	int		$id				ID of partnership
	 * @return  Object					Object with cleaned properties
	 *
	 * @url	GET partnerships/{id}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('partnership', 'read')) {
			throw new RestException(403);
		}

		$result = $this->partnership->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Partnership not found');
		}

		if (!DolibarrApi::_checkAccessToResource('partnership', $this->partnership->id, 'partnership')) {
			throw new RestException(403, 'Access to instance id='.$this->partnership->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->partnership);
	}


	/**
	 * List partnerships
	 *
	 * Get a list of partnerships
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 *
	 * @url	GET /partnerships/
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		$obj_ret = array();
		$tmpobject = new Partnership($this->db);

		if (!DolibarrApiAccess::$user->hasRight('partnership', 'read')) {
			throw new RestException(403);
		}

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : 0;

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
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
				$tmp_object = new Partnership($this->db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($tmp_object), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving partnership list: '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create partnership object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of partnership
	 *
	 * @throws RestException
	 *
	 * @url	POST partnerships/
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('partnership', 'write')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->partnership->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->partnership->$field = $this->_checkValForAPI($field, $value, $this->partnership);
		}

		// Clean data
		// $this->partnership->abc = sanitizeVal($this->partnership->abc, 'alphanohtml');

		if ($this->partnership->create(DolibarrApiAccess::$user)<0) {
			throw new RestException(500, "Error creating Partnership", array_merge(array($this->partnership->error), $this->partnership->errors));
		}
		return $this->partnership->id;
	}

	/**
	 * Update partnership
	 *
	 * @param 	int   	$id             	Id of partnership to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 *
	 * @throws RestException
	 *
	 * @url	PUT partnerships/{id}
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('partnership', 'write')) {
			throw new RestException(403);
		}

		$result = $this->partnership->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Partnership not found');
		}

		if (!DolibarrApi::_checkAccessToResource('partnership', $this->partnership->id, 'partnership')) {
			throw new RestException(403, 'Access to instance id='.$this->partnership->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->partnership->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->partnership->$field = $this->_checkValForAPI($field, $value, $this->partnership);
		}

		// Clean data
		// $this->partnership->abc = sanitizeVal($this->partnership->abc, 'alphanohtml');

		if ($this->partnership->update(DolibarrApiAccess::$user, false) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->partnership->error);
		}
	}

	/**
	 * Delete partnership
	 *
	 * @param   int     $id   Partnership ID
	 * @return  array
	 *
	 * @throws RestException
	 *
	 * @url	DELETE partnerships/{id}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('partnership', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->partnership->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Partnership not found');
		}

		if (!DolibarrApi::_checkAccessToResource('partnership', $this->partnership->id, 'partnership')) {
			throw new RestException(403, 'Access to instance id='.$this->partnership->id.' of object not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->partnership->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting Partnership : '.$this->partnership->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Partnership deleted'
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

		unset($object->rowid);
		unset($object->canvas);

		/*unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		*/

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

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array		$data   Array of data to validate
	 * @return	array
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		$partnership = array();
		foreach ($this->partnership->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) {
				continue; // Not a mandatory field
			}
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$partnership[$field] = $data[$field];
		}
		return $partnership;
	}
}
