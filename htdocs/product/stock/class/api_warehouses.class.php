<?php
/* Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
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

 require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
 require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

/**
 * API class for warehouses
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Warehouses extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'label',
	);

	/**
	 * @var Entrepot $warehouse {@type Entrepot}
	 */
	public $warehouse;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->warehouse = new Entrepot($this->db);
	}

	/**
	 * Get properties of a warehouse object
	 *
	 * Return an array with warehouse information
	 *
	 * @param	int		$id				ID of warehouse
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->warehouse->fetch($id);
		if (!$result) {
			throw new RestException(404, 'warehouse not found');
		}

		if (!DolibarrApi::_checkAccessToResource('stock', $this->warehouse->id, 'entrepot')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->warehouse);
	}

	/**
	 * List warehouses
	 *
	 * Get a list of warehouses
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param  int    $category   Use this param to filter list by category
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'WH-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return array                Array of warehouse objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $category = 0, $sqlfilters = '', $properties = '')
	{
		global $db, $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->hasRight('stock', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot AS t LEFT JOIN ".MAIN_DB_PREFIX."entrepot_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		if ($category > 0) {
			$sql .= ", ".$this->db->prefix()."categorie_warehouse as c";
		}
		$sql .= ' WHERE t.entity IN ('.getEntity('stock').')';
		// Select warehouses of given category
		if ($category > 0) {
			$sql .= " AND c.fk_categorie = ".((int) $category);
			$sql .= " AND c.fk_warehouse = t.rowid ";
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

		$result = $this->db->query($sql);
		if ($result) {
			$i = 0;
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$warehouse_static = new Entrepot($this->db);
				if ($warehouse_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($warehouse_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve warehouse list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}


	/**
	 * Create warehouse object
	 *
	 * @param array $request_data   Request data
	 * @return int  ID of warehouse
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'creer')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->warehouse->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->warehouse->$field = $this->_checkValForAPI($field, $value, $this->warehouse);
		}
		if ($this->warehouse->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating warehouse", array_merge(array($this->warehouse->error), $this->warehouse->errors));
		}
		return $this->warehouse->id;
	}

	/**
	 * Update warehouse
	 *
	 * @param 	int   	$id             	Id of warehouse to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->warehouse->fetch($id);
		if (!$result) {
			throw new RestException(404, 'warehouse not found');
		}

		if (!DolibarrApi::_checkAccessToResource('stock', $this->warehouse->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->warehouse->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->warehouse->$field = $this->_checkValForAPI($field, $value, $this->warehouse);
		}

		if ($this->warehouse->update($id, DolibarrApiAccess::$user)) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->warehouse->error);
		}
	}

	/**
	 * Delete warehouse
	 *
	 * @param int $id   Warehouse ID
	 * @return array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->warehouse->fetch($id);
		if (!$result) {
			throw new RestException(404, 'warehouse not found');
		}

		if (!DolibarrApi::_checkAccessToResource('stock', $this->warehouse->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->warehouse->delete(DolibarrApiAccess::$user)) {
			throw new RestException(403, 'error when delete warehouse');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Warehouse deleted'
			)
		);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Entrepot  $object   Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Remove the subscriptions because they are handled as a subresource.
		//unset($object->subscriptions);

		return $object;
	}


	/**
	 * Validate fields before create or update object
	 *
	 * @param array|null    $data    Data to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$warehouse = array();
		foreach (Warehouses::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$warehouse[$field] = $data[$field];
		}
		return $warehouse;
	}
}
