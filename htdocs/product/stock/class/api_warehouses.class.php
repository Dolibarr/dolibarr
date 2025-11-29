<?php
/* Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2025		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025	William Mead			<william@m34d.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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
require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';

/**
 * API class for warehouses
 *
 * @since	5.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Warehouses extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'label',
	);

	/**
	 * @var Entrepot {@type Entrepot}
	 */
	public $warehouse;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->warehouse = new Entrepot($this->db);
	}

	/**
	 * Get properties of a warehouse object
	 *
	 * Return an array with warehouse information
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int		$id				ID of warehouse
	 * @return	Object					Object with cleaned properties
	 *
	 * @url	GET {id}
	 *
	 * @throws RestException 400 Bad Request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'lire')) {
			throw new RestException(403);
		}
		if ($id == 0) {
			throw new RestException(400, 'No warehouse with id=0 can exist');
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
	 * @since	4.0.0	Initial implementation
	 * @since	23.0.0	Data pagination
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param int		$category			Use this param to filter list by category
	 * @param string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'WH-%') and (t.date_creation:<:'20160101')"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return array						Array of warehouse objects
	 * @phan-return Entrepot[]
	 * @phpstan-return Entrepot[]
	 *
	 * @url	GET
	 *
	 * @throws RestException 400 Bad Request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 500 Internal Server Error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $category = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
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

		//this query will return total warehouses with the filters given
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
	 * Create a warehouse
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return int  ID of warehouse
	 *
	 * @url	POST
	 *
	 * @throws RestException 400 Bad Request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 500 Internal Server Error
	 *
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

			if ($field == 'id' || $field == 'warehouse_id') {
				throw new RestException(400, 'Creating with id field is forbidden');
			}
			if ($field == 'entity' && $value != $this->warehouse->entity) {
				throw new RestException(403, 'Changing entity of a user using the APIs is not possible');
			}

			$this->warehouse->$field = $this->_checkValForAPI($field, $value, $this->warehouse);
		}
		if ($this->warehouse->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating warehouse", array_merge(array($this->warehouse->error), $this->warehouse->errors));
		}
		return $this->warehouse->id;
	}

	/**
	 * Update a warehouse
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int 	$id					ID of warehouse to update
	 * @param	array	$request_data		Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object						Updated object
	 *
	 * @url	PUT {id}
	 *
	 * @throws RestException 400 Bad Request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 Internal Server Error
	 *
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'creer')) {
			throw new RestException(403);
		}
		if ($id == 0) {
			throw new RestException(400, 'No warehouse with id=0 can exist');
		}
		$result = $this->warehouse->fetch($id);
		if (!$result) {
			throw new RestException(404, 'warehouse not found');
		}

		if (!DolibarrApi::_checkAccessToResource('stock', $this->warehouse->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id' || $field == 'warehouse_id') {
				throw new RestException(400, 'Updating with id field is forbidden');
			}
			if ($field == 'entity' && $value != $this->warehouse->entity) {
				throw new RestException(403, 'Changing entity of a user using the APIs is not possible');
			}
			if ($field == 'ref') {
				throw new RestException(400, 'Deprecated, use label, not ref');
			}

			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->warehouse->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->warehouse->array_options[$index] = $this->_checkValForAPI($field, $val, $this->warehouse);
				}
				continue;
			}

			$this->warehouse->$field = $this->_checkValForAPI($field, $value, $this->warehouse);
		}

		$updateresult = $this->warehouse->update($id, DolibarrApiAccess::$user);
		if ($updateresult > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->warehouse->error);
		}
	}

	/**
	 * Delete a warehouse
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int		$id		Warehouse ID
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url	DELETE {id}
	 *
	 * @throws RestException 400 Bad Request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 Internal Server Error
	 *
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'supprimer')) {
			throw new RestException(403);
		}
		if ($id == 0) {
			throw new RestException(400, 'No warehouse with id=0 can exist');
		}
		$result = $this->warehouse->fetch($id);
		if (!$result) {
			throw new RestException(404, 'warehouse not found');
		}

		if (!DolibarrApi::_checkAccessToResource('stock', $this->warehouse->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->warehouse->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'error when delete warehouse');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Warehouse deleted'
			)
		);
	}

	/**
	 * List products in a warehouse
	 *
	 * Get a list of products for a warehouse
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param 	int		$id					warehouse ID {@min 1} {@from body} {@required true}
	 * @param	string	$sortfield			Sort field
	 * @param	string	$sortorder			Sort order
	 * @param	int		$limit				Limit for list
	 * @param	int		$page				Page number
	 * @param	int		$includestockdata	1=Load also information about stock (slower), 0=No stock data (faster) (default)
	 * @param	bool	$includesubproducts	Load information about subproducts
	 * @param	bool	$includeparentid	Load also ID of parent product (if product is a variant of a parent product)
	 * @param	bool	$includetrans		Load also the translations of product label and description
	 * @param	string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool	$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0
	 * @return	array	 					Array of product in warehouse
	 *
	 * @phan-return Product[]
	 * @phpstan-return Product[]
	 *
	 * @url GET /{id}/products
	 *
	 * @throws RestException 400 Bad Request
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 500 Internal Server Error
	 *
	*/
	public function listProducts($id = 0, $sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $includestockdata = 0, $includesubproducts = false, $includeparentid = false, $includetrans = false, $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('stock', 'lire')) {
			throw new RestException(403);
		}
		if ((int) $id == 0) {
			throw new RestException(400, 'No warehouse with id=0 can exist');
		}
		$existsresult = $this->warehouse->fetch($id);
		if (!$existsresult) {
			throw new RestException(404, 'warehouse not found');
		}
		$obj_ret = array();

		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."product_stock as ps";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."product as t ON ps.fk_product = t.rowid";
		$sql.= " WHERE ps.fk_entrepot =".((int) $id);
		$sql.= " AND t.entity IN (".getEntity('stock').")";

		//this query will return total warehouses with the filters given
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
			$i = 0;
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$api_products_static = new Products();
				if ($api_products_static->get($obj->rowid, $includestockdata, $includesubproducts, $includeparentid, $includetrans)) {
					$obj_ret[] = $this->_filterObjectProperties($api_products_static->product, $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(500, 'Error when retrieve warehouse product list : '.$this->db->lasterror());
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param   Entrepot  $object   Object to clean
	 * @return  Object              Object with cleaned properties
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->actiontypecode);
		unset($object->canvas);
		unset($object->civility_code);
		unset($object->civility_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement_supplier_id);
		unset($object->contact_id);
		unset($object->contacts_ids);
		unset($object->contacts_ids_internal);
		unset($object->country_code);
		unset($object->date_cloture);
		unset($object->date_validation);
		unset($object->demand_reason_id);
		unset($object->deposit_percent);
		unset($object->extraparams);
		unset($object->firstname);
		unset($object->fk_account);
		unset($object->fk_multicurrency);
		unset($object->fk_user_creat);
		unset($object->fk_user_modif);
		unset($object->last_main_doc);
		unset($object->lastname);
		unset($object->libelle);
		unset($object->lines);
		unset($object->linkedObjectsIds);
		unset($object->mode_reglement_id);
		unset($object->module);
		unset($object->multicurrency_code);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_tx);
		unset($object->name);
		unset($object->nb_rights);
		unset($object->note_private);
		unset($object->note_public);
		unset($object->origin_id);
		unset($object->origin_type);
		unset($object->product);
		unset($object->ref_ext);
		unset($object->region_id);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->shipping_method);
		unset($object->shipping_method_id);
		unset($object->specimen);
		unset($object->state_id);
		unset($object->tms);
		unset($object->total_ht);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->total_tva);
		unset($object->totalpaid);
		unset($object->totalpaid_multicurrency);
		unset($object->transport_mode_id);
		unset($object->TRIGGER_PREFIX);
		unset($object->user);
		unset($object->user_closing_id);
		unset($object->user_modification_id);
		unset($object->user_validation_id);

		return $object;
	}


	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,string> $data   Data to validate
	 * @return array<string,string>
	 *
	 * @throws RestException 400 Bad Request
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
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
