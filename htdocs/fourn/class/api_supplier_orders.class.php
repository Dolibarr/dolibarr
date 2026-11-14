<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2025-2026	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		William Mead			<william@m34d.com>
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

/**
 * API class for supplier orders
 *
 * @since	6.0.0	Initial implementation
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierOrders extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid'
	);

	/**
	 * @var CommandeFournisseur {@type CommandeFournisseur}
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
	 * @since	6.0.0	Initial implementation
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
	 * Prepare a plain-text supplier order email for review, without sending.
	 *
	 * Body fields: subject and body (optional overrides); template_id (0 for default);
	 * to, cc, bcc (lists of bare email
	 * addresses); from_type (user, company, robot); attachments (existing PDF
	 * filenames); reply_to_event_id (an accessible sent email of this order).
	 * Omitted to defaults to the supplier email; omitted attachments means none.
	 * Returns effective recipients including automatic copies and an expected_hash for comparison.
	 * No draft is stored. Templates are rendered as plain text. Attachment suggestions
	 * never attach files automatically. PDF generation is a separate explicit document action.
	 *
	 * @param int $id Supplier order ID
	 * @param array<string,mixed> $request_data Message fields
	 * @return array<string,mixed> Message preview and comparison fingerprint
	 * @url POST {id}/emails/prepare
	 * @throws RestException
	 */
	public function prepareEmail($id, $request_data = null)
	{
		$this->checkEmailOrder($id);
		if (!is_array($request_data)) {
			throw new RestException(400, 'An email object is required');
		}
		return $this->emailService()->prepare($this->order, DolibarrApiAccess::$user, $request_data);
	}

	/**
	 * Send a reviewed supplier order email. Each successful call sends a new email.
	 *
	 * The body contains message (the original preparation input) and expected_hash.
	 * The hash detects changes, not human consent. This endpoint is not idempotent;
	 * clients must not automatically retry failed, interrupted or successful calls.
	 * Transport acceptance is not proof of delivery. No order status is changed.
	 *
	 * @param int $id Supplier order ID
	 * @param array<string,mixed> $request_data Object containing message and expected_hash
	 * @return array<string,mixed> Transport result
	 * @url POST {id}/emails/send
	 * @throws RestException
	 */
	public function sendEmail($id, $request_data = null)
	{
		$this->checkEmailOrder($id);
		if (!is_array($request_data) || count($request_data) !== 2
			|| !isset($request_data['message'], $request_data['expected_hash'])
			|| !is_array($request_data['message']) || !is_string($request_data['expected_hash'])) {
			throw new RestException(400, 'Only message and expected_hash are accepted');
		}
		return $this->emailService()->send($this->order, DolibarrApiAccess::$user, $request_data['message'], $request_data['expected_hash']);
	}

	/**
	 * Recheck native send rights, object access and permitted status on both requests.
	 *
	 * @param int $id Supplier order ID
	 * @return void
	 */
	protected function checkEmailOrder($id)
	{
		$actor = DolibarrApiAccess::$user;
		if (empty($actor->id) || !empty($actor->socid) || !isModEnabled('supplier_order')
			|| !$actor->hasRight('fournisseur', 'commande', 'lire') || !$actor->hasRight('fournisseur', 'commande', 'commander')) {
			throw new RestException(403, 'Internal supplier order read and order permissions are required');
		}
		if ((!is_int($id) && (!is_string($id) || !ctype_digit($id))) || filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 1) {
			throw new RestException(400, 'Invalid supplier order ID');
		}
		$result = $this->order->fetch((int) $id);
		if ($result < 0) {
			throw new RestException(503, 'Unable to fetch supplier order');
		}
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}
		if (!static::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Supplier order access denied');
		}
		if (!in_array((int) $this->order->status, array(2, 3, 4, 5), true) && !getDolGlobalString('SUPPLIER_ORDER_SENDBYEMAIL_FOR_ALL_STATUS')) {
			throw new RestException(409, 'Supplier order is not in a state that allows email');
		}
	}

	/** @return SupplierOrderMail Email preparation service */
	protected function emailService()
	{
		return new SupplierOrderMail($this->db);
	}

	/**
	 * List orders
	 *
	 * Get a list of supplier orders
	 *
	 * @since	6.0.0	Initial implementation
	 * @since	12.0.0	Added product_ids parameter
	 * @since	19.0.0	Added sqlfilterlines and properties parameters
	 * @since	21.0.0	Added pagination_data parameter
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
	 * @phan-return CommandeFournisseur[]|array{data:CommandeFournisseur[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return CommandeFournisseur[]|array{data:CommandeFournisseur[],pagination:array{total:int,page:int,page_count:int,limit:int}}
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
		$socids = DolibarrApiAccess::$user->socid ?: $thirdparty_ids;

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
		// An unknown value used to be ignored, so the whole list came back as if it were the filtered one
		if ((string) $status !== '' && !in_array($status, array('draft', 'validated', 'approved', 'running', 'received_start', 'received_end', 'cancelled', 'refused'), true)) {
			throw new RestException(400, "Bad value for parameter status. Expected one of: draft, validated, approved, running, received_start, received_end, cancelled, refused");
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
				$sql .= " AND ".getSalesRepresentativeSqlFilter('t.fk_soc', 0, 1);
			} elseif ($search_sale > 0) {
				$sql .= " AND ".getSalesRepresentativeSqlFilter('t.fk_soc', (int) $search_sale);
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
				'page_count' => (int) ceil((int) $total / $limit),
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
	 * @since	6.0.0	Initial implementation
	 *
	 * @param array $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return int  ID of supplier order
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "creer") && !DolibarrApiAccess::$user->hasRight("supplier_order", "creer")) {
			throw new RestException(403, "Insufficiant rights");
		}

		if (!is_array($request_data)) {
			$request_data = array();
		}

		// Check mandatory fields (not using output, only possible exception is important)
		$this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->order->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->order->$field = $this->_checkValForAPI($field, $value, $this->order);
		}
		if (!array_key_exists('date', $request_data)) {
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
	 * @since	6.0.0	Initial implementation
	 *
	 * @param 	int   	$id             	Id of supplier order to update
	 * @param 	array 	$request_data   	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return 	Object						Updated object
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500 System error
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

		if (!is_array($request_data)) {
			$request_data = array();
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
					$this->order->array_options[$index] = $this->_checkValExtrafieldsForAPI($index, $val, $this->order);
				}
				continue;
			}

			$this->order->$field = $this->_checkValForAPI($field, $value, $this->order);
		}

		if ($this->order->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->order->errorsToString());
		}
	}

	/**
	 * Add a line to a given supplier order
	 *
	 * @since	23.0.0	Initial implementation
	 *
	 * @param int   $id             Id of order to update
	 * @param array $request_data   OrderLine data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('fournisseur', 'commande', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');

		$updateRes = $this->order->addline(
			$request_data->desc,
			$request_data->subprice,
			$request_data->qty,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->fk_product,
			$request_data->fk_prod_fourn_price,
			$request_data->ref_fourn,
			$request_data->remise_percent,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->pu_ttc,
			$request_data->product_type,
			$request_data->info_bits,
			$request_data->notrigger,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->array_options,
			$request_data->fk_unit,
			$request_data->multicurrency_subprice,
			$request_data->origin,
			$request_data->origin_id,
			// -1 is the "append at the end" value of addline(). An absent rang is null, which empty() turns into 0,
			// so without this every line added through the API is stored with rang 0 instead of being appended.
			isset($request_data->rang) ? $request_data->rang : -1,
			$request_data->special_code
		);

		if ($updateRes > 0) {
			return $updateRes;
		} else {
			throw new RestException(400, $this->order->errorsToString());
		}
	}

	/**
	 * Get one supplier order line after checking access to its parent.
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param int $id Supplier order ID
	 * @param int $lineid Line ID
	 * @return CommandeFournisseurLigne Order line
	 * @url GET {id}/lines/{lineid}
	 */
	public function getLine($id, $lineid)
	{
		$line = clone $this->loadLineForOperation($id, $lineid);
		$this->_cleanObjectDatas($line);
		return $line;
	}

	/**
	 * Update selected fields of a draft supplier order line.
	 *
	 * Omitted fields keep their current value. Dates are Unix timestamps or null.
	 * Give only one price, in company currency (HT or TTC) or order currency (HT).
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param int $id Supplier order ID
	 * @param int $lineid Line ID
	 * @param array $request_data Fields to change
	 * @phan-param ?array<string,mixed> $request_data
	 * @phpstan-param ?array<string,mixed> $request_data
	 * @return CommandeFournisseurLigne Updated line
	 * @url PUT {id}/lines/{lineid}
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('fournisseur', 'commande', 'creer')) {
			throw new RestException(403, 'Write permission is required');
		}
		$line = $this->loadLineForOperation($id, $lineid);
		if ((int) $this->order->status !== CommandeFournisseur::STATUS_DRAFT) {
			throw new RestException(409, 'Only draft supplier orders accept line changes');
		}
		$line = $this->prepareLineUpdate($line, $request_data);
		$includingtax = isset($request_data['subprice_ttc']) || (!isset($request_data['subprice']) && $line->wasEnteredIncludingTax());
		if (isset($request_data['multicurrency_subprice'])) {
			$line->subprice = 0;
			$includingtax = false;
		} elseif ($includingtax || isset($request_data['subprice'])) {
			$line->multicurrency_subprice = 0;
		}
		$vatrate = $line->tva_tx;
		if (!isset($request_data['tva_tx']) && !empty($line->vat_src_code)) {
			$vatrate .= ' ('.$line->vat_src_code.')';
		}
		if ($this->order->fetch_thirdparty() <= 0) {
			throw new RestException(500, 'Unable to load supplier for tax calculations');
		}
		$result = $this->order->updateline(
			$lineid, $line->desc, $includingtax ? $line->subprice_ttc : $line->subprice,
			$line->qty, $line->remise_percent, $vatrate, $line->localtax1_tx, $line->localtax2_tx,
			$includingtax ? 'TTC' : 'HT', $line->info_bits, $line->product_type, 0,
			(int) $line->date_start, (int) $line->date_end, $line->array_options, $line->fk_unit,
			$line->multicurrency_subprice, $line->ref_supplier
		);
		if ($result <= 0) {
			throw new RestException(400, $this->order->errorsToString());
		}

		$line = new CommandeFournisseurLigne($this->db);
		if ($line->fetch($lineid) <= 0) {
			throw new RestException(500, 'Failed to reload the updated line');
		}

		$this->_cleanObjectDatas($line);
		return $line;
	}

	/**
	 * Deletes a line of a given draft supplier order
	 *
	 * A line that has already been received cannot be deleted.
	 *
	 * @since	25.0.0	Initial implementation
	 * @param	int		$id			Id of supplier order
	 * @param	int		$lineid		Id of the line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return	array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 400 Bad parameters
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Error
	 */
	public function deleteLine($id, $lineid)
	{
		if (empty($lineid)) {
			throw new RestException(400, 'Line ID is mandatory');
		}

		if (!DolibarrApiAccess::$user->hasRight('fournisseur', 'commande', 'creer')) {
			throw new RestException(403);
		}

		// Checks read permission and access to the order, and that the line belongs to it
		$this->loadLineForOperation($id, $lineid);

		if ((int) $this->order->status !== CommandeFournisseur::STATUS_DRAFT) {
			throw new RestException(405, 'Supplier order must be in draft status to delete lines');
		}

		$updateRes = $this->order->deleteLine((int) $lineid);
		if ($updateRes > 0) {
			return array(
				'success' => array(
					'code' => 200,
					'message' => 'line '.$lineid.' deleted'
				)
			);
		} else {
			throw new RestException(405, $this->order->errorsToString());
		}
	}

	/**
	 * Prepare a partial update without changing the original line or writing data.
	 *
	 * @param CommandeFournisseurLigne $line Current line
	 * @param array<string,mixed>|null $request_data Fields to change
	 * @return CommandeFournisseurLigne Prepared line
	 */
	protected function prepareLineUpdate($line, $request_data)
	{
		if (!is_array($request_data) || empty($request_data)) {
			throw new RestException(400, 'A non-empty object of line changes is required');
		}
		$pricefields = array_intersect(array_keys($request_data), array('subprice', 'subprice_ttc', 'multicurrency_subprice'));
		if (count($pricefields) > 1) {
			throw new RestException(400, 'Give only one unit price: subprice, subprice_ttc or multicurrency_subprice');
		}
		$line = clone $line;
		$allowed = array('desc', 'subprice', 'subprice_ttc', 'qty', 'remise_percent', 'tva_tx', 'localtax1_tx', 'localtax2_tx', 'date_start', 'date_end', 'array_options', 'fk_unit', 'multicurrency_subprice', 'ref_supplier');
		foreach ($request_data as $field => $value) {
			if (!in_array($field, $allowed, true)) {
				throw new RestException(400, 'Unsupported line field: '.$field);
			}
			if ($field === 'array_options') {
				if (!is_array($value)) {
					throw new RestException(400, 'array_options must be an object');
				}
				foreach ($value as $key => $optional) {
					$line->array_options[$key] = $this->_checkValExtrafieldsForAPI($key, $optional, $line);
				}
			} elseif (in_array($field, array('date_start', 'date_end', 'fk_unit'), true)) {
				if ($value !== null && (!is_numeric($value) || (int) $value != $value || $value < ($field === 'fk_unit' ? 1 : 0))) {
					throw new RestException(400, 'Expected a valid integer or null for '.$field);
				}
				$line->$field = $value === null ? null : (int) $value;
			} elseif (in_array($field, array('desc', 'ref_supplier'), true)) {
				if (!is_string($value)) {
					throw new RestException(400, 'Expected a string for '.$field);
				}
				$line->$field = $this->_checkValForAPI($field, $value, $line);
			} elseif (!is_numeric($value) || !is_finite((float) $value)) {
				throw new RestException(400, 'Expected a finite numeric value for '.$field);
			} else {
				$line->$field = $value;
			}
		}
		if ((float) $line->qty == 0) {
			throw new RestException(400, 'Quantity must not be zero');
		}
		if ($line->date_start && $line->date_end && $line->date_start > $line->date_end) {
			throw new RestException(400, 'Start date must not be later than end date');
		}
		return $line;
	}

	/**
	 * Load an authorised order without sanitising its live database handle.
	 *
	 * @param int $id Supplier order ID
	 * @return void
	 */
	protected function loadOrderForOperation($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('fournisseur', 'commande', 'lire')) {
			throw new RestException(403, 'Read permission is required');
		}
		if ($this->order->fetch($id) <= 0) {
			throw new RestException(404, 'Supplier order not found');
		}
		if (!static::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access denied to supplier order');
		}
	}

	/**
	 * Load the complete line after checking its parent and ownership.
	 *
	 * @param int $id Supplier order ID
	 * @param int $lineid Line ID
	 * @return CommandeFournisseurLigne Order line
	 */
	protected function loadLineForOperation($id, $lineid)
	{
		$this->loadOrderForOperation($id);
		foreach ($this->order->lines as $line) {
			if ((int) $line->id === (int) $lineid) {
				return $line;
			}
		}
		throw new RestException(404, 'Line not found in this supplier order');
	}

	/**
	 * Get contacts of given supplier order
	 *
	 * Return an array with contact information
	 *
	 * @since	18.0.0	Initial implementation
	 *
	 * @param	int					$id			ID of supplier order
	 * @param	string				$source		Source of the contact (internal, external, all).
	 * @param	string				$type		Type of the contact (BILLING, SHIPPING, CUSTOMER, SALESREPFOLL, ...)
	 * @return	array<int,mixed>				Array of contacts
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

		return $contacts;
	}

	/**
	 * Add a contact type of given supplier order
	 *
	 * @since	18.0.0	Initial implementation
	 *
	 * @param int		$id				Id of supplier order to update
	 * @param int		$contactid		Id of contact/user to add
	 * @param string	$type			Type of the contact (BILLING, SHIPPING, CUSTOMER, SALESREPFOLL, ...)
	 * @param string	$source			Source of the contact (external, internal)
	 * @return array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
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
	 * @since	18.0.0	Initial implementation
	 *
	 * @param	int		$id             Id of supplier order to update
	 * @param	int		$contactid      Id of contact/user to add
	 * @param	string	$type           Type of the contact (BILLING, SHIPPING, CUSTOMER, SALESREPFOLL, ...).
	 * @param	string	$source			Source of the contact (internal, external).
	 *
	 * @url	DELETE {id}/contact/{contactid}/{type}/{source}
	 *
	 * @return array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
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

		$result = $this->order->delete_contact($contactToUnlink);

		if ($result < 0) {
			throw new RestException(500, 'Error when deleting the contact: '.$this->order->errorsToString());
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
	 * @since	6.0.0	Initial implementation
	 *
	 * @param int		$id		Supplier order ID
	 * @return array			Array of result
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
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
	 * @since	6.0.0	Initial implementation
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
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
			throw new RestException(500, 'Error when validating Order: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order validated (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Set a validated or approved order back to draft
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param   int		$id			Order ID
	 *
	 * @url POST    {id}/settodraft
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Order is not validated or approved
	 * @throws RestException 500 Error
	 */
	public function settodraft($id)
	{
		// Same right as checked by the supplier order card before calling CommandeFournisseur::setReopen()
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

		// Other statuses are reopened one step back by CommandeFournisseur::setReopen(), not set to draft
		$status = (int) $this->order->status;
		if ($status !== CommandeFournisseur::STATUS_VALIDATED && $status !== CommandeFournisseur::STATUS_ACCEPTED) {
			throw new RestException(405, 'Only validated or approved supplier orders can be set back to draft');
		}

		// Same rights as the ones showing the "Modify" (validated) and "Disapprove" (approved) buttons on the supplier order card
		if ($status === CommandeFournisseur::STATUS_VALIDATED) {
			$allowed = DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "commander");
		} else {
			$allowed = (DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "approuver")
					&& (!getDolGlobalString('SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY') || DolibarrApiAccess::$user->id == $this->order->user_approve_id))
				|| (DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "approve2") && getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED')
					&& (!getDolGlobalString('SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY') || DolibarrApiAccess::$user->id == $this->order->user_approve_id2));
		}
		if (!$allowed) {
			throw new RestException(403, 'Not allowed to set this supplier order back to draft');
		}

		$result = $this->order->setReopen(DolibarrApiAccess::$user);
		if ($result <= 0) {
			throw new RestException(500, 'Error when setting Order to draft: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order set to draft (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Approve an order
	 *
	 * @since	15.0.0	Initial implementation
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $secondlevel      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/approve
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
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
			throw new RestException(500, 'Error when approve Order: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order approved (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Refuse a validated order that is waiting for approval
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param   int		$id			Order ID
	 * @param   string	$comment	Reason of the refusal, added to the agenda event {@required false}
	 *
	 * @url POST    {id}/refuse
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Order is not waiting for approval
	 * @throws RestException 500 Error
	 *
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "comment": ""
	 * }
	 */
	public function refuse($id, $comment = '')
	{
		// Same right as checked by CommandeFournisseur::refuse(), which otherwise does nothing and returns 0
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "approuver")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ((int) $this->order->status !== CommandeFournisseur::STATUS_VALIDATED) {
			throw new RestException(405, 'Only validated supplier orders waiting for approval can be refused');
		}

		if ((string) $comment !== '') {
			$this->order->refuse_note = $comment;
		}

		$result = $this->order->refuse(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, 'Error when refusing Order: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order refused (Ref='.$this->order->ref.')'
			)
		);
	}


	/**
	 * Sends an order to the vendor
	 *
	 * @since	15.0.0	Initial implementation
	 *
	 * @param   int		$id             Order ID
	 * @param   integer	$date		Date (unix timestamp in sec)
	 * @param   int		$method		Method
	 * @param  string	$comment	Comment
	 *
	 * @url POST    {id}/makeorder
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 405 Order is not approved
	 *
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

		// Same status as the one allowing the make order button on the supplier order card
		if ((int) $this->order->status !== CommandeFournisseur::STATUS_ACCEPTED) {
			throw new RestException(405, 'Only approved supplier orders can be sent to the vendor');
		}

		$result = $this->order->commande(DolibarrApiAccess::$user, $date, $method, $comment);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already sent');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when sending Order: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order sent (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Cancel an approved order that has not been sent to the vendor yet
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param   int		$id			Order ID
	 * @param   string	$comment	Reason of the cancellation, added to the agenda event {@required false}
	 *
	 * @url POST    {id}/cancel
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Order is not approved
	 * @throws RestException 500 Error
	 *
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "comment": ""
	 * }
	 */
	public function cancel($id, $comment = '')
	{
		// Same right as checked by CommandeFournisseur::cancel(), which otherwise returns -1 without error message
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "commande", "commander")) {
			throw new RestException(403);
		}
		$result = $this->order->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->order->id, 'commande_fournisseur', 'commande')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Same status as the one allowing the cancel button on the supplier order card
		if ((int) $this->order->status !== CommandeFournisseur::STATUS_ACCEPTED) {
			throw new RestException(405, 'Only approved supplier orders can be canceled');
		}

		if ((string) $comment !== '') {
			$this->order->cancel_note = $comment;
		}

		$result = $this->order->cancel(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, 'Error when canceling Order: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Order canceled (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Classify an order as billed. Could be also called setbilled
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param   int		$id			Order ID
	 *
	 * @url POST    {id}/setinvoiced
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Order status does not allow it, or a supplier invoice is required
	 * @throws RestException 500 Error
	 */
	public function setinvoiced($id)
	{
		// Same right as checked by the supplier order card before calling CommandeFournisseur::classifyBilled()
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

		// Same statuses as the ones showing the "Classify billed" button on the supplier order card
		$status = (int) $this->order->status;
		if ($status < CommandeFournisseur::STATUS_ACCEPTED || $status === CommandeFournisseur::STATUS_CANCELED_AFTER_ORDER) {
			throw new RestException(405, 'Only approved supplier orders, or later statuses except canceled after order, can be classified as billed');
		}

		// Same rule as the supplier order card, which then requires at least one linked supplier invoice
		if (isModEnabled('invoice') && getDolGlobalInt('SUPPLIER_ORDER_DISABLE_CLASSIFY_BILLED_FROM_SUPPLIER_ORDER')) {
			$this->order->fetchObjectLinked();
			if (empty($this->order->linkedObjectsIds['invoice_supplier'])) {
				throw new RestException(405, 'At least one supplier invoice linked to the order is required to classify it as billed');
			}
		}

		// CommandeFournisseur::classifyBilled() returns 0 when the order is already billed
		$result = $this->order->classifyBilled(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, 'Error when classifying Order as billed: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => ($result == 0 ? 'Order already classified as billed' : 'Order classified as billed').' (Ref='.$this->order->ref.')'
			)
		);
	}

	/**
	 * Classify an order as unbilled. Could be also called setunbilled
	 *
	 * @since	25.0.0	Initial implementation
	 *
	 * @param   int		$id			Order ID
	 *
	 * @url POST    {id}/setuninvoiced
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Order status does not allow it
	 * @throws RestException 500 Error
	 */
	public function setuninvoiced($id)
	{
		// Same right as checked by the supplier order card before calling CommandeFournisseur::classifyUnBilled()
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

		// Same statuses as the ones showing the "Classify unbilled" button on the supplier order card
		if ((int) $this->order->status <= CommandeFournisseur::STATUS_DRAFT) {
			throw new RestException(405, 'A draft supplier order can not be classified as unbilled');
		}

		// CommandeFournisseur::classifyUnBilled() returns 0 when the order is not billed
		$result = $this->order->classifyUnBilled(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, 'Error when classifying Order as unbilled: '.$this->order->errorsToString());
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => ($result == 0 ? 'Order already classified as unbilled' : 'Order classified as unbilled').' (Ref='.$this->order->ref.')'
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
	 * @since	15.0.0	Initial implementation
	 *
	 * @param   int		$id             Order ID
	 * @param   integer	$closeopenorder	Close order if everything is received {@required false}
	 * @param   string	$comment	Comment {@required false}
	 * @param   array	$lines		Array of product dispatches
	 * @phan-param array<array{fk_product:string,qty:string,warehouse:string,price:string,comment:string,eatby:string,sellby:string,batch:string,id:string,notrigger:string}> $lines
	 * @phpstan-param array<array{fk_product:string,qty:string,warehouse:string,price:string,comment:string,eatby:string,sellby:string,batch:string,id:string,notrigger:string}> $lines
	 *
	 * @url POST    {id}/receive
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
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
			$lineObj = (object) $line;

			$result = $this->order->dispatchProduct(
				DolibarrApiAccess::$user,
				$lineObj->fk_product,
				$lineObj->qty,
				$lineObj->warehouse,
				$lineObj->price,
				$lineObj->comment,
				$lineObj->eatby,
				$lineObj->sellby,
				$lineObj->batch,
				(int) $lineObj->id,
				$lineObj->notrigger
			);

			if ($result < 0) {
				throw new RestException(500, 'Error dispatch order line '.$lineObj->id.': '.$this->order->errorsToString());
			}
		}

		$result = $this->order->calcAndSetStatusDispatch(DolibarrApiAccess::$user, $closeopenorder, $comment);

		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already dispatched');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when receivce order: '.$this->order->errorsToString());
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
	 * @param ?array<string,string> $data   Data to validate
	 * @return array<string,string>
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
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

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

/** Prepare and compare effective messages without storing drafts or temporary files. */
class SupplierOrderMail
{
	/** @var DoliDB Database handler */
	private $db;
	/** Maximum combined size of existing PDF attachments. */
	const MAX_BYTES = 10485760;

	/**
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Prepare a plain-text message without sending it or generating documents.
	 *
	 * @param CommandeFournisseur $order Authorized order
	 * @param User $actor Acting user
	 * @param array<string,mixed> $input Message fields
	 * @return array<string,mixed> Effective message preview and comparison fingerprint
	 */
	public function prepare($order, $actor, array $input)
	{
		$prepared = $this->compile($order, $actor, $input);
		return array_intersect_key($prepared, array_flip(array("preview", "expected_hash")));
	}

	/**
	 * @param CommandeFournisseur $order Authorized order
	 * @param User $actor Acting user
	 * @param array<string,mixed> $input Message fields
	 * @return array<string,mixed> Effective message and native transport
	 */
	private function compile($order, $actor, array $input)
	{
		global $conf;
		$allowed = array('subject', 'body', 'to', 'cc', 'bcc', 'from_type', 'attachments', 'reply_to_event_id', 'template_id');
		if (array_diff(array_keys($input), $allowed)) {
			throw new RestException(400, 'Unknown email field');
		}
		$template = array();
		if (isset($input['template_id']) || !isset($input['subject'], $input['body'])) {
			$template = $this->template($order, $actor, $input['template_id'] ?? 0);
			$input += $template['fields'];
		}
		foreach (array('subject', 'body') as $field) {
			if (!isset($input[$field]) || !is_string($input[$field]) || trim($input[$field]) === '' || strpos($input[$field], "\0") !== false) {
				throw new RestException(400, 'A non-empty '.$field.' is required');
			}
		}
		if (dol_strlen($input['subject']) > 255 || preg_match('/[\r\n]/', $input['subject']) || strlen($input['body']) > 100000) {
			throw new RestException(400, 'Invalid subject or message too large');
		}
		if (getDolGlobalString('MAIN_MAIL_FORCE_CONTENT_TYPE_TO_HTML')) {
			throw new RestException(409, 'This endpoint requires plain-text email; forced HTML is enabled');
		}
		$fromType = $input['from_type'] ?? 'user';
		if (!is_string($fromType) || !in_array($fromType, array('user', 'company', 'robot'), true)) {
			throw new RestException(400, 'from_type must be user, company or robot');
		}
		if (!array_key_exists('to', $input)) {
			if ($order->fetch_thirdparty() <= 0) {
				throw new RestException(409, 'Cannot read the supplier email address');
			}
			$input['to'] = array($order->thirdparty->email);
		}
		$message = array(
			'subject' => $input['subject'], 'body' => $input['body'], 'from_type' => $fromType, 'from' => isset($input['from_type']) || empty($template['sender']) ? $this->sender($actor, $fromType) : $template['sender'],
			'to' => $this->addresses($input['to']), 'cc' => $this->addresses($input['cc'] ?? array()),
			'bcc' => $this->addresses($input['bcc'] ?? array()), 'in_reply_to' => ''
		);
		if ($message['to'] === '') {
			throw new RestException(400, 'At least one recipient is required');
		}
		if (isset($input['reply_to_event_id'])) {
			$message['in_reply_to'] = $this->replyMessageId($order, $input['reply_to_event_id']);
			$message['reply_to_event_id'] = (int) $input['reply_to_event_id'];
		}
		$attachments = $input['attachments'] ?? array();
		if (!is_array($attachments) || count($attachments) > 10 || array_values($attachments) !== $attachments) {
			throw new RestException(400, 'attachments must be a list of at most ten PDF filenames');
		}
		$files = $this->readAttachments($order, $attachments);
		$manifest = array();
		foreach ($files as $file) {
			$manifest[] = array('name' => $file['name'], 'size' => strlen($file['content']), 'sha256' => hash('sha256', $file['content']));
		}
		$mail = $this->buildMail($order, $actor, $message, array_column($files, 'path'), array_column($files, 'name'));
		$preview = $this->preview($order, $message, $mail, $manifest);
		$preview['template'] = $template['preview'] ?? null;
		// Detect document replacement while the native transport was being constructed.
		if ($files !== $this->readAttachments($order, $attachments)) {
			throw new RestException(409, 'An attachment changed; prepare the email again');
		}
		return array('preview' => $preview, 'message' => $message, 'mail' => $mail,
			'expected_hash' => hash('sha256', $this->encode(array((int) $conf->entity, (int) $actor->id, $this->orderHash($order), $preview))));
	}

	/**
	 * Rebuild and compare the reviewed email, then attempt native transport once.
	 * This stateless endpoint is not idempotent: clients must not automatically retry.
	 *
	 * @param CommandeFournisseur $order Freshly authorized order
	 * @param User $actor Acting user
	 * @param array<string,mixed> $input Original preparation input
	 * @param string $expectedHash Reviewed preview fingerprint, not an authorization token
	 * @return array<string,mixed> Transport result, not a delivery receipt
	 */
	public function send($order, $actor, array $input, $expectedHash)
	{
		if (!is_string($expectedHash) || !preg_match('/^[a-f0-9]{64}$/D', $expectedHash)) {
			throw new RestException(400, 'A preparation expected_hash is required');
		}
		if (getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
			throw new RestException(409, 'Email sending is disabled');
		}
		$prepared = $this->compile($order, $actor, $input);
		if (!hash_equals($prepared['expected_hash'], $expectedHash)) {
			throw new RestException(409, 'The effective email or order changed; prepare and review it again');
		}
		$mail = $prepared['mail'];
		try {
			$accepted = $this->transmit($mail, $actor);
		} catch (Throwable $e) {
			dol_syslog(__METHOD__.': mail transport outcome unknown', LOG_ERR);
			throw new RestException(502, 'Mail transport outcome is unknown. Do not retry automatically');
		}
		if (!$accepted) {
			throw new RestException(502, 'Mail transport did not report success; delivery may be unknown. Do not retry automatically');
		}
		return array('status' => 'sent', 'message_id' => $mail->msgid, 'delivery_status' => 'unknown',
			'history_recorded' => $this->recordSent($order, $actor, $prepared['message'], $mail));
	}

	/**
	 * Render an accessible native template as plain text before review.
	 *
	 * @param CommandeFournisseur $order Order
	 * @param User $actor Acting user
	 * @param mixed $id Zero selects the default template
	 * @return array<string,mixed> Rendered defaults and template metadata
	 */
	protected function template($order, $actor, $id)
	{
		global $langs, $user;
		if ((!is_int($id) && (!is_string($id) || !ctype_digit($id)))
			|| filter_var($id, FILTER_VALIDATE_INT) === false || (int) $id < 0) {
			throw new RestException(400, 'Invalid template_id');
		}
		if ($order->fetch_thirdparty() <= 0) {
			throw new RestException(409, 'Cannot read the supplier for template substitutions');
		}
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$outputlangs = clone $langs;
		if (getDolGlobalInt('MAIN_MULTILANGS') && !empty($order->thirdparty->default_lang)) {
			$outputlangs->setDefaultLang($order->thirdparty->default_lang);
		}
		$outputlangs->loadLangs(array('main', 'other', 'commercial', 'orders', 'companies'));
		$formmail = $this->mailForm();
		$template = $formmail->getEMailTemplate($this->db, 'order_supplier_send', $actor, $outputlangs, (int) $id, 1, '', $id > 0 ? -1 : 1);
		if (!is_object($template) || ($id > 0 && (int) $template->id !== (int) $id)) {
			throw new RestException(404, 'Accessible active supplier order email template not found');
		}
		$previousUser = $user;
		$user = $actor;
		try {
			$formmail->setSubstitFromObject($order, $outputlangs);
			$formmail->substit['__SENDEREMAIL_SIGNATURE__'] = $actor->signature;
			$formmail->substit['__EMAIL__'] = $order->thirdparty->email;
			$formmail->substit['__CHECK_READ__'] = '';
			$lines = '';
			foreach ($formmail->substit_lines as $line) {
				$lines .= make_substitutions($template->content_lines, $line, $outputlangs)."\n";
			}
			$formmail->substit['__LINES__'] = $lines;
			$rendered = array();
			foreach (array('subject' => $template->topic ?: $outputlangs->transnoentitiesnoconv('SendSupplierOrderRef', $order->ref),
				'body' => $template->content, 'sender' => $template->email_from,
				'cc' => $template->email_tocc, 'bcc' => $template->email_tobcc) as $field => $value) {
				$rendered[$field] = dol_string_nohtmltag(make_substitutions((string) $value, $formmail->substit, $outputlangs), 0, 'UTF-8', 1);
			}
			$fields = array('subject' => $rendered['subject'], 'body' => $rendered['body']);
			foreach (array('cc', 'bcc') as $field) {
				if (trim($rendered[$field]) !== '') {
					$fields[$field] = array_map('trim', explode(',', $rendered[$field]));
				}
			}
			return array('fields' => $fields, 'sender' => trim($rendered['sender']) === '' ? '' : $this->addresses(array(trim($rendered['sender']))),
				'preview' => array('id' => (int) $template->id, 'label' => $template->label,
					'attachment_suggested' => !empty($template->joinfiles)));
		} finally {
			$user = $previousUser;
		}
	}

	/** @return FormMail Native template reader and substitution builder */
	protected function mailForm()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		return new FormMail($this->db);
	}

	/**
	 * @param User $actor Acting user
	 * @param string $type Sender source
	 * @return string Configured sender
	 */
	private function sender($actor, $type)
	{
		$address = $type === 'user' ? $actor->email : getDolGlobalString($type === 'company' ? 'MAIN_INFO_SOCIETE_MAIL' : 'MAIN_MAIL_EMAIL_FROM');
		return $this->addresses(array($address));
	}

	/**
	 * @param mixed $addresses List of bare email addresses
	 * @return string Validated recipient list
	 */
	private function addresses($addresses)
	{
		if (!is_array($addresses) || array_values($addresses) !== $addresses || count($addresses) > 20) {
			throw new RestException(400, 'Recipients must be a list of at most twenty email addresses');
		}
		foreach ($addresses as $address) {
			if (!is_string($address) || preg_match('/[\r\n\x00,;<>]/', $address) || !isValidEmail($address)) {
				throw new RestException(400, 'Invalid email address');
			}
		}
		return implode(', ', array_unique($addresses));
	}

	/**
	 * @param CommandeFournisseur $order Order
	 * @param mixed[] $names Existing PDF filenames
	 * @return array<array{name:string,content:string,path:string}> Exact attachment bytes
	 */
	protected function readAttachments($order, array $names)
	{
		$files = array();
		$total = 0;
		$root = realpath($this->attachmentDirectory($order));
		foreach ($names as $name) {
			if (!is_string($name) || $name === '' || dol_sanitizeFileName($name) !== $name
				|| preg_match('/[\/\\\\\x00-\x1f]/', $name) || dol_strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'pdf') {
				throw new RestException(400, 'Only PDF filenames directly attached to this order are allowed');
			}
			$path = $root ? realpath($root.'/'.$name) : false;
			if (!$path || dirname($path) !== $root || !dol_is_file($path) || !is_readable($path)) {
				throw new RestException(404, 'Order attachment not found');
			}
			$content = file_get_contents($path, false, null, 0, self::MAX_BYTES + 1);
			if ($content === false || strncmp($content, '%PDF-', 5) !== 0 || ($total += strlen($content)) > self::MAX_BYTES) {
				throw new RestException(400, 'Attachments must be PDFs totalling at most 10 MiB');
			}
			$files[] = array('name' => $name, 'content' => $content, 'path' => $path);
		}
		return $files;
	}

	/**
	 * @param CommandeFournisseur $order Order
	 * @return string Order document directory
	 */
	protected function attachmentDirectory($order)
	{
		return (string) getMultidirOutput($order, '', 1);
	}

	/**
	 * Build the native transport without sending. Its effective recipients include all configured overrides.
	 *
	 * @param CommandeFournisseur $order Order
	 * @param User $actor Acting user
	 * @param array<string,mixed> $message Message
	 * @param string[] $paths Existing order PDF paths
	 * @param string[] $names Attachment names
	 * @return CMailFile Mail transport
	 */
	protected function buildMail($order, $actor, array $message, array $paths, array $names)
	{
		global $user;
		$previousUser = $user;
		$user = $actor;
		$swiftCache = null;

		try {
			$mode = getDolGlobalString('MAIN_MAIL_SENDMODE_STANDARD');
			if ($mode === '' || $mode === 'default') {
				$mode = getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail');
			}
			if ($mode === 'swiftmailer') {
				require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lexer/lib/Doctrine/Common/Lexer/AbstractLexer.php';
				require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/autoload.php';
				require_once DOL_DOCUMENT_ROOT.'/includes/swiftmailer/lib/swift_required.php';
				// Bound attachment size allows an in-memory native cache, with no disk spool.
				$container = Swift_DependencyContainer::getInstance();
				$swiftCache = $container->lookup('cache');
				$container->register('cache')->asValue($container->lookup('cache.array'));
			}

			$bcc = $message['bcc'];
			$auto = getDolGlobalString('MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO');
			if ($auto !== '') {
				$bcc .= ($bcc !== '' ? ', ' : '').$auto;
			}
			$mail = new CMailFile($message['subject'], $message['to'], $message['from'], $message['body'], $paths,
				array_fill(0, count($paths), 'application/pdf'), $names, $message['cc'], $bcc, 0, 0, '', '',
				'sord'.$order->id, '', 'standard', '', '', $message['in_reply_to'], $message['in_reply_to']);
			if ($mail->error || $mail->errors || !$mail->addr_to) {
				throw new RestException(400, 'Cannot construct the email with the current mail configuration');
			}
			foreach (array($mail->addr_from, $mail->addr_to, $mail->addr_cc, $mail->addr_bcc) as $addresses) {
				if (dol_strlen($addresses) > 255) {
					throw new RestException(400, 'An effective address list exceeds the agenda storage limit of 255 characters');
				}
			}
			// Match the native transport's recipient-count checks before issuing a preparation.
			$totalRecipients = 0;
			foreach (array('TO' => $mail->addr_to, 'CC' => $mail->addr_cc, 'BCC' => $mail->addr_bcc) as $kind => $list) {
				$count = count(explode(',', $list));
				$totalRecipients += $count;
				if ($count > (getDolGlobalInt('MAIL_MAX_NB_OF_RECIPIENTS_'.$kind.'_IN_SAME_EMAIL') ?: 10)) {
					throw new RestException(400, 'Effective recipient count exceeds the configured mail limit');
				}
			}
			if ($totalRecipients > (getDolGlobalInt('MAIL_MAX_NB_OF_RECIPIENTS_IN_SAME_EMAIL') ?: 10)) {
				throw new RestException(400, 'Effective recipient count exceeds the configured total mail limit');
			}
			if ($mail->sendmode === 'swiftmailer' && is_object($mail->message)) {
				foreach ($mail->message->getChildren() as $attachment) {
					$attachment->setBody($attachment->getBody());
				}
			}
			return $mail;
		} finally {
			if ($swiftCache !== null) {
				Swift_DependencyContainer::getInstance()->register('cache')->asValue($swiftCache);
			}

			$user = $previousUser;
		}
	}

	/**
	 * @param CommandeFournisseur $order Order
	 * @param array<string,mixed> $message Message
	 * @param CMailFile $mail Native transport
	 * @param array<array{name:string,size:int,sha256:string}> $manifest Attachment fingerprints
	 * @return array<string,mixed> Effective preview
	 */
	private function preview($order, array $message, $mail, array $manifest)
	{
		return array('order_id' => (int) $order->id, 'order_ref' => $order->ref,
			'from' => $mail->addr_from, 'reply_to' => $mail->reply_to, 'to' => $mail->addr_to, 'cc' => $mail->addr_cc, 'bcc' => $mail->addr_bcc,
			'subject' => $message['subject'], 'body' => $message['body'], 'format' => 'text',
			'in_reply_to' => $message['in_reply_to'], 'attachments' => $manifest);
	}

	/**
	 * @param CommandeFournisseur $order Order
	 * @return string Fingerprint of the business content used to prepare the email
	 */
	private function orderHash($order)
	{
		$headerFields = array('id', 'entity', 'ref', 'socid', 'status', 'ref_supplier', 'total_ht', 'total_tva', 'total_ttc',
			'delivery_date', 'date_commande', 'cond_reglement_id', 'mode_reglement_id', 'note_public', 'note_private',
			'fk_project', 'multicurrency_code', 'multicurrency_tx', 'multicurrency_total_ttc', 'array_options',
			'fk_incoterms', 'location_incoterms', 'fk_warehouse', 'deposit_percent', 'remise_percent', 'fk_account',
			'total_localtax1', 'total_localtax2', 'multicurrency_total_ht', 'multicurrency_total_tva');
		$header = array_intersect_key(get_object_vars($order), array_flip($headerFields));
		$lines = array();
		$lineFields = array('id', 'rowid', 'fk_product', 'desc', 'description', 'qty', 'subprice', 'pu_ht', 'tva_tx',
			'remise_percent', 'total_ht', 'total_tva', 'total_ttc', 'product_type', 'date_start', 'date_end', 'array_options',
			'localtax1_tx', 'localtax2_tx', 'localtax1_type', 'localtax2_type', 'multicurrency_subprice',
			'multicurrency_total_ht', 'multicurrency_total_tva', 'multicurrency_total_ttc', 'ref_supplier', 'fk_unit', 'rang');
		foreach ($order->lines as $line) {
			$lines[] = array_intersect_key(get_object_vars($line), array_flip($lineFields));
		}
		return hash('sha256', $this->encode(array($header, $lines)));
	}

	/**
	 * @param array<mixed> $data State or fingerprint values
	 * @return string JSON without silently dropping invalid text
	 */
	private function encode(array $data)
	{
		$json = json_encode($data);
		if ($json === false) {
			throw new RestException(400, 'Email data must contain valid UTF-8 text');
		}
		return $json;
	}

	/**
	 * @param CommandeFournisseur $order Order
	 * @param mixed $eventId Previous outgoing email event
	 * @return string Validated Message-ID
	 */
	protected function replyMessageId($order, $eventId)
	{
		if ((!is_int($eventId) && (!is_string($eventId) || !ctype_digit($eventId)))
			|| filter_var($eventId, FILTER_VALIDATE_INT) === false || (int) $eventId <= 0) {
			throw new RestException(400, 'Invalid reply_to_event_id');
		}
		$event = $this->replyEvent((int) $eventId);
		if ((int) $event->elementid !== (int) $order->id || $event->elementtype !== 'order_supplier'
			|| $event->code !== 'AC_ORDER_SUPPLIER_SENTBYMAIL' || empty($event->email_msgid)
			|| preg_match('/[\r\n\x00]/', $event->email_msgid)) {
			throw new RestException(400, 'The reply event must be an accessible sent email of this order with a Message-ID');
		}
		$messageId = trim($event->email_msgid, '<>');
		if (!preg_match('/^[^<>\s]+@[^<>\s]+$/D', $messageId)) {
			throw new RestException(400, 'The previous email has an invalid Message-ID');
		}
		return '<'.$messageId.'>';
	}

	/**
	 * @param int $eventId Agenda event ID
	 * @return object Accessible agenda event
	 */
	protected function replyEvent($eventId)
	{
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/api_agendaevents.class.php';
		$agenda = new AgendaEvents();
		return $agenda->get($eventId);
	}

	/**
	 * @param CMailFile $mail Native mail transport
	 * @param User $actor Acting user
	 * @return bool Transport acceptance
	 */
	protected function transmit($mail, $actor)
	{
		global $user;
		$previousUser = $user;
		$user = $actor;
		try {
			return (bool) $mail->sendfile();
		} finally {
			$user = $previousUser;
		}
	}

	/**
	 * Record the native sent-email trigger after transport success, never retrying the transport on log failure.
	 *
	 * @param CommandeFournisseur $order Order
	 * @param User $actor Acting user
	 * @param array<string,mixed> $message Message
	 * @param CMailFile $mail Native mail transport
	 * @return bool Whether the trigger succeeded with agenda tracking enabled
	 */
	protected function recordSent($order, $actor, array $message, $mail)
	{
		$order->actiontypecode = 'AC_EMAIL';
		$order->actionmsg = dol_nl2br(dol_escape_htmltag($message['body']));
		$order->actionmsg2 = $message['subject'];
		$order->sendtoid = array();
		$previousContext = $order->context;
		$fields = array('email_msgid' => $mail->msgid, 'email_subject' => $message['subject'],
			'email_from' => $mail->addr_from, 'email_reply_to' => $mail->reply_to, 'email_to' => $mail->addr_to, 'email_tocc' => $mail->addr_cc, 'email_tobcc' => $mail->addr_bcc);
		foreach ($fields as $key => $value) {
			$order->context[$key] = $value;
		}
		$this->db->begin();
		try {
			if ($order->call_trigger('ORDER_SUPPLIER_SENTBYMAIL', $actor) < 0) {
				$this->db->rollback();
				return false;
			}
			if (!$this->db->commit()) {
				return false;
			}
			return isModEnabled('agenda') && getDolGlobalString('MAIN_AGENDA_ACTIONAUTO_ORDER_SUPPLIER_SENTBYMAIL') !== '';
		} catch (Throwable $e) {
			$this->db->rollback();
			dol_syslog(__METHOD__.': sent mail could not be recorded', LOG_ERR);
			return false;
		} finally {
			$order->context = $previousContext;
		}
	}
}
