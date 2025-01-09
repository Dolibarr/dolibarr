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
	 * @param	int		$id ID of supplier order
	 * @return	array|mixed data without useless information
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "lire")) {
			throw new RestException(403);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->order->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->order);
	}

	/**
	 * List orders
	 *
	 * Get a list of supplier orders
	 *
	 * @param string	$sortfield		  Sort field
	 * @param string	$sortorder		  Sort order
	 * @param int		$limit			  Limit for list
	 * @param int		$page			  Page number
	 * @param string	$thirdparty_ids	  Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$product_ids	  Product ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$status			  Filter by order status : draft | validated | approved | running | received_start | received_end | cancelled | refused
	 * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @param string    $sqlfilterlines   Other criteria to filter answers separated by a comma. Syntax example "(tl.fk_product:=:'17') and (tl.price:<:'250')"
	 * @param string    $properties		  Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data  If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return array                      Array of order objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $product_ids = '', $status = '', $sqlfilters = '', $sqlfilterlines = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "lire")) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight("societe", "client", "voir") && !empty($socids)) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		if (!empty($product_ids)) {
			$sql .= ", ".MAIN_DB_PREFIX."commande_fournisseurdet as cd"; // We need this table joined to the select in order to filter by product
		}
		$sql .= ' WHERE t.entity IN ('.getEntity('supplier_order').')';
		if (!empty($product_ids)) {
			$sql .= " AND cd.fk_commande = t.rowid AND cd.fk_product IN (".$this->db->sanitize($product_ids).")";
		}
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
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
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}
		// Add sql filters for lines
		if ($sqlfilterlines) {
			$errormessage = '';
			$sql .= " AND EXISTS (SELECT tl.rowid FROM ".MAIN_DB_PREFIX."commande_fournisseurdet AS tl WHERE tl.fk_commande = t.rowid";
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilterlines, $errormessage);
			$sql .=	")";
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilterlines -> '.$errormessage);
			}
		}

		//this query will return total supplier orders with the filters given
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
				$order_static = new CommandeFournisseur($this->db);
				if ($order_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($order_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve supplier order list : '.$this->db->lasterror());
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
	 * Create supplier order object
	 *
	 * Example: {"ref": "auto", "ref_supplier": "1234", "socid": "1", "multicurrency_code": "SEK", "multicurrency_tx": 1, "tva_tx": 25, "note": "Imported via the REST API"}
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of supplier order
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->order->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->order->$field = $this->_checkValForAPI($field, $value, $this->order);
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
	 * @param 	int   	$id             	Id of supplier order to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object|false				Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->order->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->order->array_options[$index] = $this->_checkValForAPI($field, $val, $this->order);
				}
				continue;
			}
			$this->order->$field = $this->_checkValForAPI($field, $value, $this->order);
		}

		if ($this->order->update(DolibarrApiAccess::$user)) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Get contacts of given supplier order
	 *
	 * Return an array with contact information
	 *
	 * @param	int		$id			ID of supplier order
	 * @param	string	$source		Source of the contact (internal, external, all).
	 * @param	string	$type		Type of the contact (BILLING, SHIPPING, CUSTOMER, SALESREPFOLL, ...)
	 * @return	Object				Object with cleaned properties
	 *
	 * @url	GET {id}/contacts
	 *
	 * @throws	RestException
	 */
	public function getContacts($id, $source, $type = '')
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "lire")) {
			throw new RestException(403);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$contacts = array();

		if ($source == 'all' || $source == 'external') {
			$tmpContacts = $this->order->liste_contact(-1, 'external', 0, $type);
			$contacts =	array_merge($contacts, $tmpContacts);
		}

		if ($source == 'all' || $source == 'internal') {
			$tmpContacts = $this->order->liste_contact(-1, 'internal', 0, $type);
			$contacts = array_merge($contacts, $tmpContacts);
		}

		return $this->_cleanObjectDatas($contacts);
	}

	/**
	 * Add a contact type of given supplier order
	 *
	 * @param int		$id				Id of supplier order to update
	 * @param int		$contactid		Id of contact/user to add
	 * @param string	$type			Type of the contact (BILLING, SHIPPING, CUSTOMER, SALESREPFOLL, ...)
	 * @param string	$source			Source of the contact (external, internal)
	 * @return array
	 *
	 * @url	POST {id}/contact/{contactid}/{type}/{source}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function postContact($id, $contactid, $type, $source)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer")) {
			throw new RestException(403);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->order->add_contact($contactid, $type, $source);

		if ($result < 0) {
			throw new RestException(500, 'Error when added the contact');
		}

		if ($result == 0) {
			throw new RestException(304, 'contact already added');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact linked to the order'
			)
		);
	}

	/**
	 * Unlink a contact type of given supplier order
	 *
	 * @param	int		$id             Id of supplier order to update
	 * @param	int		$contactid      Id of contact/user to add
	 * @param	string	$type           Type of the contact (BILLING, SHIPPING, CUSTOMER, SALESREPFOLL, ...).
	 * @param	string	$source			Source of the contact (internal, external).
	 *
	 * @url	DELETE {id}/contact/{contactid}/{type}/{source}
	 *
	 * @return array
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function deleteContact($id, $contactid, $type, $source)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer")) {
			throw new RestException(403);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$contacts = $this->order->liste_contact(-1, $source, 0, $type);

		$contactToUnlink = 0;
		foreach ($contacts as $contact) {
			if ($contact['id'] == $contactid && $contact['code'] == $type) {
				$contactToUnlink = $contact['rowid'];
				break;
			}
		}

		if ($contactToUnlink == 0) {
			throw new RestException(404, 'Linked contact not found');
		}

		$result = $this->order->delete_contact($contact['rowid']);

		if (!$result) {
			throw new RestException(500, 'Error when deleted the contact');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact unlinked from supplier order'
			)
		);
	}

	/**
	 * Delete supplier order
	 *
	 * @param int		$id		Supplier order ID
	 * @return array			Array of result
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "supprimer")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($lines as $line) {
			$lineObj =(object) $line;

			$result=$this->order->dispatchProduct(
				DolibarrApiAccess::$user,
				$lineObj->fk_product,
				$lineObj->qty,
				$lineObj->warehouse,
				$lineObj->price,
				$lineObj->comment,
				$lineObj->eatby,
				$lineObj->sellby,
				$lineObj->batch,
				$lineObj->id,
				$lineObj->notrigger
			);

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
