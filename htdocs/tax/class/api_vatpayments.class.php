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

require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
// PaymentVAT::delete() instantiates AccountLine without requiring it; load it here
// so deleting a bank-linked payment via the API does not throw a fatal error.
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * API class for VAT payments (declarations of VAT to pay/collect) and their payments
 *
 * @property DoliDB $db
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class VatPayments extends DolibarrApi
{
	/**
	 * @var string[] Mandatory fields, checked when creating a VAT declaration
	 */
	public static $FIELDS = array(
		'datep',
		'datev',
		'amount',
		'label',
	);

	/**
	 * @var string[] Mandatory fields, checked when creating a VAT payment
	 */
	public static $PAYMENT_FIELDS = array(
		'datepaye',
		'amount',
		'paiementtype',
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
	 * Get properties of a VAT payment (declaration) object.
	 *
	 * @param	int		$id		ID of the VAT declaration
	 * @return  Object			Object with cleaned properties
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 VAT payment not found
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'lire')) {
			throw new RestException(403);
		}

		$vat = new Tva($this->db);
		$result = $vat->fetch($id);
		// Tva::fetch() returns 1 even when the record does not exist,
		// so also check that the id was actually loaded.
		if ($result <= 0 || empty($vat->id)) {
			throw new RestException(404, 'VAT payment not found');
		}

		return $this->_cleanObjectDatas($vat);
	}

	/**
	 * List VAT payments (declarations).
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.datep:>=:'20240101') and (t.amount:<:100)"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0
	 * @return array						Array of VAT payment objects
	 * @phan-return Tva[]|array{data:Tva[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return Tva[]|array{data:Tva[],pagination:array{total:int,page:int,page_count:int,limit:int}}
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
		$sql .= " FROM ".MAIN_DB_PREFIX."tva AS t";
		$sql .= " WHERE t.entity IN (".getEntity('tva').")";

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
			throw new RestException(503, 'Error when retrieving list of VAT payments: '.$this->db->lasterror());
		}

		$num = $this->db->num_rows($result);
		$min = min($num, ($limit <= 0 ? $num : $limit));
		for ($i = 0; $i < $min; $i++) {
			$obj = $this->db->fetch_object($result);
			$vat = new Tva($this->db);
			if ($vat->fetch($obj->rowid) > 0) {
				$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($vat), $properties);
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
	 * Create a VAT payment (declaration).
	 *
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int						ID of the created VAT payment
	 *
	 * @throws RestException 400 Missing mandatory field
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 Error when creating the VAT payment
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'creer')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$this->_validate($request_data);

		$vat = new Tva($this->db);
		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$vat->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$vat->$field = $this->_checkValForAPI($field, $value, $vat);
		}

		if ($vat->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when creating VAT payment: '.$vat->error);
		}

		return $vat->id;
	}

	/**
	 * Update a VAT payment (declaration).
	 *
	 * @param	int		$id				ID of the VAT declaration
	 * @param	array	$request_data	Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return  Object					Updated object with cleaned properties
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 VAT payment not found
	 * @throws RestException 500 Error when updating the VAT payment
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'creer')) {
			throw new RestException(403);
		}

		$vat = new Tva($this->db);
		$result = $vat->fetch($id);
		// Tva::fetch() returns 1 even when the record does not exist,
		// so also check that the id was actually loaded.
		if ($result <= 0 || empty($vat->id)) {
			throw new RestException(404, 'VAT payment not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$vat->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$vat->array_options[$index] = $this->_checkValExtrafieldsForAPI($index, $val, $vat);
				}
				continue;
			}

			$vat->$field = $this->_checkValForAPI($field, $value, $vat);
		}

		if ($vat->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, 'Error when updating VAT payment: '.$vat->error);
		}
	}

	/**
	 * Delete a VAT payment (declaration).
	 *
	 * @param	int		$id		ID of the VAT declaration
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 VAT payment not found
	 * @throws RestException 500 Error when deleting the VAT payment
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'supprimer')) {
			throw new RestException(403);
		}

		$vat = new Tva($this->db);
		$result = $vat->fetch($id);
		// Tva::fetch() returns 1 even when the record does not exist,
		// so also check that the id was actually loaded.
		if ($result <= 0 || empty($vat->id)) {
			throw new RestException(404, 'VAT payment not found');
		}

		if ($vat->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting VAT payment: '.$vat->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'VAT payment deleted'
			)
		);
	}

	/**
	 * List all VAT payments.
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @return  array			List of PaymentVAT objects
	 * @phan-return PaymentVAT[]
	 * @phpstan-return PaymentVAT[]
	 *
	 * @url     GET /payments
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 503 Error when retrieving list
	 */
	public function getAllPayments($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'lire')) {
			throw new RestException(403);
		}

		$list = array();

		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."payment_vat AS t, ".MAIN_DB_PREFIX."tva AS s";
		$sql .= " WHERE t.fk_tva = s.rowid AND s.entity IN (".getEntity('tva').")";
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		$result = $this->db->query($sql);
		if (!$result) {
			throw new RestException(503, 'Error when retrieving list of VAT payments: '.$this->db->lasterror());
		}

		$num = $this->db->num_rows($result);
		$min = min($num, ($limit <= 0 ? $num : $limit));
		for ($i = 0; $i < $min; $i++) {
			$obj = $this->db->fetch_object($result);
			$payment = new PaymentVAT($this->db);
			if ($payment->fetch($obj->rowid) > 0) {
				$list[] = $this->_cleanObjectDatas($payment);
			}
		}

		return $list;
	}

	/**
	 * Get the list of payments of a given VAT declaration.
	 *
	 * @param	int		$id		ID of the VAT declaration
	 * @return  array			List of PaymentVAT objects
	 * @phan-return PaymentVAT[]
	 * @phpstan-return PaymentVAT[]
	 *
	 * @url     GET {id}/payments
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 503 Error when retrieving list
	 */
	public function getPayments($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'lire')) {
			throw new RestException(403);
		}

		$list = array();

		$sql = "SELECT t.rowid FROM ".MAIN_DB_PREFIX."payment_vat AS t";
		$sql .= " WHERE t.fk_tva = ".((int) $id);
		$sql .= $this->db->order('t.rowid', 'ASC');

		$result = $this->db->query($sql);
		if (!$result) {
			throw new RestException(503, 'Error when retrieving list of VAT payments: '.$this->db->lasterror());
		}

		$num = $this->db->num_rows($result);
		for ($i = 0; $i < $num; $i++) {
			$obj = $this->db->fetch_object($result);
			$payment = new PaymentVAT($this->db);
			if ($payment->fetch($obj->rowid) > 0) {
				$list[] = $this->_cleanObjectDatas($payment);
			}
		}

		return $list;
	}

	/**
	 * Get a given VAT payment.
	 *
	 * @param	int		$pid	ID of the payment
	 * @return  Object			PaymentVAT object with cleaned properties
	 *
	 * @url     GET payments/{pid}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Payment not found
	 */
	public function getPayment($pid)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'lire')) {
			throw new RestException(403);
		}

		$payment = new PaymentVAT($this->db);
		$result = $payment->fetch($pid);
		if ($result <= 0 || empty($payment->id)) {
			throw new RestException(404, 'VAT payment not found');
		}

		return $this->_cleanObjectDatas($payment);
	}

	/**
	 * Add a payment to a VAT declaration.
	 *
	 * @param	int		$id				ID of the VAT declaration
	 * @param	array	$request_data	Request data (datepaye, amount, paiementtype, [num_payment], [accountid])
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return	int						ID of the created payment
	 *
	 * @url     POST {id}/payments
	 *
	 * @throws RestException 400 Missing mandatory field
	 * @throws RestException 403 Access denied
	 * @throws RestException 500 Error when creating the payment
	 */
	public function addPayment($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'creer')) {
			throw new RestException(403);
		}

		// Check mandatory fields
		$this->_validatePayment($request_data);

		$payment = new PaymentVAT($this->db);
		$payment->chid = $id;
		$payment->datepaye = $request_data['datepaye'];
		$payment->amounts = array($id => (float) $request_data['amount']);
		$payment->paiementtype = $request_data['paiementtype'];
		if (isset($request_data['num_payment'])) {
			$payment->num_payment = $request_data['num_payment'];
		}
		if (isset($request_data['note'])) {
			$payment->note = $request_data['note'];
		}

		if ($payment->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when creating VAT payment', array_merge(array($payment->error), $payment->errors));
		}

		if (isModEnabled("bank") && !empty($request_data['accountid'])) {
			$payment->addPaymentToBank(DolibarrApiAccess::$user, 'payment_vat', '(VATPayment)', (int) $request_data['accountid'], '', '');
		}

		return $payment->id;
	}

	/**
	 * Delete a VAT payment.
	 *
	 * @param	int		$pid	ID of the payment
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @url     DELETE payments/{pid}
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 404 Payment not found
	 * @throws RestException 500 Error when deleting the payment
	 */
	public function deletePayment($pid)
	{
		if (!DolibarrApiAccess::$user->hasRight('tax', 'charges', 'supprimer')) {
			throw new RestException(403);
		}

		$payment = new PaymentVAT($this->db);
		$result = $payment->fetch($pid);
		if ($result <= 0 || empty($payment->id)) {
			throw new RestException(404, 'VAT payment not found');
		}

		if ($payment->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting VAT payment: '.$payment->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'VAT payment deleted'
			)
		);
	}

	/**
	 * Validate fields before creating a VAT declaration
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
		$vat = array();
		foreach (VatPayments::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$vat[$field] = $data[$field];
		}
		return $vat;
	}

	/**
	 * Validate fields before creating a VAT payment
	 *
	 * @param	?array<string,string>	$data	Data to validate
	 * @return	array<string,string>
	 *
	 * @throws RestException 400 Missing mandatory field
	 */
	private function _validatePayment($data)
	{
		if ($data === null) {
			$data = array();
		}
		$payment = array();
		foreach (VatPayments::$PAYMENT_FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$payment[$field] = $data[$field];
		}
		return $payment;
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
