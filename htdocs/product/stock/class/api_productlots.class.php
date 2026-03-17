<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
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

require_once DOL_DOCUMENT_ROOT . '/product/stock/class/productlot.class.php';

/**
 * API class for Product lots
 *
 * @since	5.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Productlots extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_product',
		'batch'
	);

	/**
	 * @var Productlot {@type Productlot}
	 */
	public $productlot;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->productlot = new Productlot($this->db);
	}

	/**
	 * Get all product lot
	 *
	 * Return an array with product lot
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int			$id			ID of Product lot to get
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('product', 'lire')) {
			throw new RestException(403, "Insufficient rights to read an event");
		}
		if ($id === 0) {
			$result = $this->productlot->initAsSpecimen();
		} else {
			$result = $this->productlot->fetch($id);
			if ($result) {
				$this->productlot->fetch_optionals();
				$this->productlot->fetchObjectLinked();
			}
		}
		if (!$result) {
			throw new RestException(404, 'Product lots not found');
		}

		return $this->_cleanObjectDatas($this->productlot);
	}

	/**
	 * List of product lot
	 *
	 * Get a list of product lot
	 *
	 * @since	5.0.0	Initial implementation
	 * @since	21.0.0	Added data pagination
	 *
	 * @param	string	$sortfield			Sort field
	 * @param	string	$sortorder			Sort order
	 * @param	int		$limit				Limit for list
	 * @param	int		$page				Page number
	 * @param	string	$user_ids			User ids filter field (owners of event). Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param	string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(pl.label:like:'%dol%') and (pl.datec:<:'20160101')"
	 * @param	string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param	bool	$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return	array						Array of order objects
	 * @phan-return productlot[]|array{data:productlot[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return productlot[]|array{data:productlot[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @url GET
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "pl.batch", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		global $conf;

		$obj_ret = [];

		if (!DolibarrApiAccess::$user->hasRight('produit', 'lire')) {
			throw new RestException(403, "Insufficient rights to view your products lots");
		}

		$sortorder = (strtoupper($sortorder) === 'DESC') ? 'DESC' : 'ASC';

		$sql  = "SELECT pl.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_lot AS pl";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot_extrafields AS ple ON ple.fk_object = pl.rowid";
		$sql .= " WHERE 1=1";

		// Filtres universels
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		// Clone pour total (avant ORDER/LIMIT)
		$sqlTotals = preg_replace('/^\s*SELECT\s+pl\.rowid/i', 'SELECT COUNT(pl.rowid) AS total', $sql);

		// ORDER BY
		$sql .= $this->db->order($sortfield, $sortorder);

		// LIMIT/OFFSET
		if ($limit) {
			$page   = max(0, (int) $page);
			$offset = $limit * $page;
			$sql   .= $this->db->plimit($limit + 1, $offset);
		}

		$res = $this->db->query($sql);
		if (!$res) {
			throw new RestException(503, 'Error when retrieving product lot list: '.$this->db->lasterror());
		}

		$i   = 0;
		$num = $this->db->num_rows($res);
		$max = ($limit > 0) ? min($num, $limit) : $num;

		while ($i < $max) {
			$obj = $this->db->fetch_object($res);
			if (!$obj) break;

			$pl = new Productlot($this->db);
			if ($pl->fetch((int) $obj->rowid) > 0) {
				$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($pl), $properties);
			}
			$i++;
		}

		// Pagination enrichie
		if ($pagination_data) {
			$total = 0;
			$totRes = $this->db->query($sqlTotals);
			if ($totRes) {
				$row = $this->db->fetch_object($totRes);
				if ($row && isset($row->total)) $total = (int) $row->total;
			}

			// Evite division par zéro
			$safeLimit  = ($limit > 0) ? (int) $limit : max(1, count($obj_ret));
			$pageCount  = (int) ceil($total / $safeLimit);

			$data = $obj_ret;
			$obj_ret = [
				'data' => $data,
				'pagination' => [
					'total'      => $total,
					'page'       => (int) $page,
					'page_count' => $pageCount,
					'limit'      => $safeLimit
				]
			];
		}

		return $obj_ret;
	}


	/**
	 * Create an product lot
	 *
	 * @param array $request_data Example: {"fk_product":123,"batch":"LOT-2025-0001"}
	 * @phan-param ?array<string, string> $request_data
	 * @phpstan-param ?array<string, string> $request_data
	 *
	 * @return    int                        ID of Product lot
	 *
	 * @throws RestException
	 *@since	5.0.0	Initial implementation
	 *
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('produit', 'creer')) {
			throw new RestException(403, "Insufficient rights to create your product lot");
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->productlot->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->productlot->$field = $this->_checkValForAPI($field, $value, $this->productlot);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->expensereport->lines = $lines;
		}*/

		if ($this->productlot->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating event", array_merge(array($this->productlot->error), $this->productlot->errors));
		}

		return $this->productlot->id;
	}


	/**
	 * Update an Product lot
	 *
	 * @since	11.0.0	Initial implementation
	 *
	 * @param	int			$id				ID of Product lot to update
	 * @param	array		$request_data	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	Object|false				Object with cleaned properties
	 *
	 * @throws RestException
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('produit', 'creer')) {
			throw new RestException(403, "Insufficient rights to create your Product lot");
		}

		$result = $this->productlot->fetch($id);
		if ($result) {
			$this->productlot->fetch_optionals();
			$this->productlot->oldcopy = clone $this->productlot;  // @phan-suppress-current-line PhanTypeMismatchProperty
		}
		if (!$result) {
			throw new RestException(404, 'productlot not found');
		}

		if (!DolibarrApi::_checkAccessToResource('productlot', $this->productlot->id, 'product_lot', '', 'fk_soc', 'rowid')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->productlot->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->productlot->array_options[$index] = $this->_checkValForAPI($field, $val, $this->productlot);
				}
				continue;
			}
			$this->productlot->$field = $this->_checkValForAPI($field, $value, $this->productlot);
		}

		if ($this->productlot->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete an product lot
	 *
	 * @since	5.0.0	Initial implementation
	 *
	 * @param	int		$id		ID of product lot to delete
	 *
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('produit', 'supprimer')) {
			throw new RestException(403, "Insufficient rights to delete your product lot");
		}

		$result = $this->productlot->fetch($id);
		if ($result) {
			$this->productlot->fetch_optionals();
			$this->productlot->oldcopy = clone $this->productlot;  // @phan-suppress-current-line PhanTypeMismatchProperty
		}

		if (!$result) {
			throw new RestException(404, 'Product lot not found');
		}

		if (!DolibarrApi::_checkAccessToResource('productlot', $this->productlot->id, 'product_lot', '', 'fk_soc', 'rowid')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($this->productlot->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when delete Product lot : '.implode(',', $this->productlot->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Product lot deleted'
			)
		);
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

		$lot = array();

		foreach (self::$FIELDS as $field) {
			if (!isset($data[$field]) || $data[$field] === '') {
				throw new RestException(400, "$field field missing");
			}
			$lot[$field] = $data[$field];
		}

		return $lot;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->note); // already in note_private or note_public
		unset($object->usermod);
		unset($object->libelle);
		unset($object->context);
		unset($object->canvas);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->thirdparty);
		unset($object->user);
		unset($object->origin);
		unset($object->origin_id);
		unset($object->ref_ext);
		unset($object->statut);
		unset($object->state_code);
		unset($object->state_id);
		unset($object->state);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->fk_delivery_address);
		unset($object->shipping_method_id);
		unset($object->fk_account);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->contact);
		unset($object->societe);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->region_id);
		unset($object->actions);
		unset($object->lines);

		return $object;
	}
}
