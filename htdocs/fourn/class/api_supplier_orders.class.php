<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
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

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

/**
 * API class for supplier orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierOrders extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid'
	);

	/**
	 * @var CommandeFournisseur $order {@type CommandeFournisseur}
	 */
	public $order;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->order = new CommandeFournisseur($this->db);
	}

	/**
	 * Get properties of a supplier order object
	 *
	 * Return an array with supplier order information
	 *
	 * @param 	int 	$id ID of supplier order
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->commande->lire) {
			throw new RestException(401);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->order->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->order);
	}

	/**
	 * List orders
	 *
	 * Get a list of supplier orders
	 *
	 * @param string	$sortfield	      Sort field
	 * @param string	$sortorder	      Sort order
	 * @param int		$limit		      Limit for list
	 * @param int		$page		      Page number
	 * @param string   	$thirdparty_ids	  Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string   	$product_ids	  Product ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$status		      Filter by order status : draft | validated | approved | running | received_start | received_end | cancelled | refused
	 * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @return array                      Array of order objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $product_ids = '', $status = '', $sqlfilters = '')
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->rights->fournisseur->commande->lire) {
			throw new RestException(401);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as t";

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		}

		if (!empty($product_ids)) {
			$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseurdet as cd"; // We need this table joined to the select in order to filter by product
		}

		$sql .= ' WHERE t.entity IN ('.getEntity('supplier_order').')';
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if (!empty($product_ids)) {
			$sql .= " AND cd.fk_commande = t.rowid AND cd.fk_product IN (".$this->db->sanitize($product_ids).")";
		}
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		if ($search_sale > 0) {
			$sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}

		// Filter by status
		if ($status == 'draft') {
			$sql .= " AND t.fk_statut IN (0)";
		}
		if ($status == 'validated') {
			$sql .= " AND t.fk_statut IN (1)";
		}
		if ($status == 'approved') {
			$sql .= " AND t.fk_statut IN (2)";
		}
		if ($status == 'running') {
			$sql .= " AND t.fk_statut IN (3)";
		}
		if ($status == 'received_start') {
			$sql .= " AND t.fk_statut IN (4)";
		}
		if ($status == 'received_end') {
			$sql .= " AND t.fk_statut IN (5)";
		}
		if ($status == 'cancelled') {
			$sql .= " AND t.fk_statut IN (6,7)";
		}
		if ($status == 'refused') {
			$sql .= " AND t.fk_statut IN (9)";
		}
		// Insert sale filter
		if ($search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			if (!DolibarrApi::_checkFilters($sqlfilters, $errormessage)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
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
				$order_static = new CommandeFournisseur($this->db);
				if ($order_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($order_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve supplier order list : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No supplier order found');
		}
		return $obj_ret;
	}

	/**
	 * Create supplier order object
	 *
	 * Example: {"ref": "auto", "ref_supplier": "1234", "socid": "1", "multicurrency_code": "SEK", "multicurrency_tx": 1, "tva_tx": 25, "note": "Imported via the REST API"}
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of supplier order
	 */
	public function post($request_data = null)
	{
		if (empty(DolibarrApiAccess::$user->rights->fournisseur->commande->creer) && empty(DolibarrApiAccess::$user->rights->supplier_order->creer)) {
			throw new RestException(401, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->order->$field = $value;
		}
		if (!array_keys($request_data, 'date')) {
			$this->order->date = dol_now();
		}
		/* We keep lines as an array
		 if (isset($request_data["lines"])) {
			$lines = array();
			foreach ($request_data["lines"] as $line) {
				array_push($lines, (object) $line);
			}
			$this->order->lines = $lines;
		}*/

		if ($this->order->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating order", array_merge(array($this->order->error), $this->order->errors));
		}
		return $this->order->id;
	}

	/**
	 * Update supplier order
	 *
	 * @param int   $id             Id of supplier order to update
	 * @param array $request_data   Datas
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		if (empty(DolibarrApiAccess::$user->rights->fournisseur->commande->creer) && empty(DolibarrApiAccess::$user->rights->supplier_order->creer)) {
			throw new RestException(401);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->order->$field = $value;
		}

		if ($this->order->update(DolibarrApiAccess::$user)) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete supplier order
	 *
	 * @param int   	$id 	Supplier order ID
	 * @return array			Array of result
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->commande->supprimer) {
			throw new RestException(401);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($this->order->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting order');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Supplier order deleted'
			)
		);
	}


	/**
	 * Validate an order
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "idwarehouse": 0,
	 *   "notrigger": 0
	 * }
	 */
	public function validate($id, $idwarehouse = 0, $notrigger = 0)
	{
		if (empty(DolibarrApiAccess::$user->rights->fournisseur->commande->creer) && empty(DolibarrApiAccess::$user->rights->supplier_order->creer)) {
			throw new RestException(401);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->order->valid(DolibarrApiAccess::$user, $idwarehouse, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Order: '.$this->order->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order validated (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Approve an order
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $secondlevel      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/approve
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "idwarehouse": 0,
	 *   "secondlevel": 0
	 * }
	 */
	public function approve($id, $idwarehouse = 0, $secondlevel = 0)
	{
		if (empty(DolibarrApiAccess::$user->rights->fournisseur->commande->creer) && empty(DolibarrApiAccess::$user->rights->supplier_order->creer)) {
			throw new RestException(401);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->order->approve(DolibarrApiAccess::$user, $idwarehouse, $secondlevel);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already approved');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when approve Order: '.$this->order->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order approved (Ref='.$this->order->ref.')'
			)
		);
	}


	/**
	 * Sends an order to the vendor
	 *
	 * @param   int		$id             Order ID
	 * @param   integer	$date		Date (unix timestamp in sec)
	 * @param   int		$method		Method
	 * @param  string	$comment	Comment
	 *
	 * @url POST    {id}/makeorder
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "date": 0,
	 *   "method": 0,
	 *   "comment": ""
	 * }
	 */
	public function makeOrder($id, $date, $method, $comment = '')
	{
		if (empty(DolibarrApiAccess::$user->rights->fournisseur->commande->creer) && empty(DolibarrApiAccess::$user->rights->supplier_order->creer)) {
			throw new RestException(401);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->order->commande(DolibarrApiAccess::$user, $date, $method, $comment);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already sent');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when sending Order: '.$this->order->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order sent (Ref='.$this->order->ref.')'
			)
		);
	}

		/**
	 * Receives the order, dispatches products.
		 *
	 * Example:
	 * <code> {
	 *   "closeopenorder": 1,
	 *   "comment": "",
		 *   "lines": [{
		 *      "id": 14,
		 *      "fk_product": 112,
		 *      "qty": 18,
		 *      "warehouse": 1,
		 *      "price": 114,
		 *      "comment": "",
		 *      "eatby": 0,
		 *      "sellby": 0,
		 *      "batch": 0,
		 *      "notrigger": 0
		 *   }]
	 * }</code>
		 *
	 * @param   int		$id             Order ID
	 * @param   integer	$closeopenorder	Close order if everything is received {@required false}
	 * @param   string	$comment	Comment {@required false}
	 * @param   array	$lines		Array of product dispatches
	 *
	 * @url POST    {id}/receive
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 *
	 */
	public function receiveOrder($id, $closeopenorder, $comment, $lines)
	{
		if (empty(DolibarrApiAccess::$user->rights->fournisseur->commande->creer) && empty(DolibarrApiAccess::$user->rights->supplier_order->creer)) {
			throw new RestException(401);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($lines as $line) {
			$lineObj =(object) $line;

			$result=$this->order->dispatchProduct(DolibarrApiAccess::$user,
				  $lineObj->fk_product,
				  $lineObj->qty,
				  $lineObj->warehouse,
				  $lineObj->price,
				  $lineObj->comment,
				  $lineObj->eatby,
				  $lineObj->sellby,
				  $lineObj->batch,
				  $lineObj->id,
				  $lineObj->notrigger);

			if ($result < 0) {
				throw new RestException(500, 'Error dispatch order line '.$line->id.': '.$this->order->error);
			}
		}

		$result = $this->order->calcAndSetStatusDispatch(DolibarrApiAccess::$user, $closeopenorder, $comment);

		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already dispatched');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when receivce order: '.$this->order->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order received (Ref='.$this->order->ref.')'
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
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param array $data   Datas to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$order = array();
		foreach (SupplierOrders::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$order[$field] = $data[$field];
		}
		return $order;
	}
}
