<?php
/* Copyright (C) 2015	Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023	Joachim Kueter			<git-jk@bloxera.com>
 * Copyright (C) 2024	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024	Frédéric France			<frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';

/**
 * API class for supplier invoices
 *
 * @property DoliDB $db
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierInvoices extends DolibarrApi
{
	/**
	 *
	 * @var string[]   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
	);

	/**
	 * @var FactureFournisseur $invoice {@type FactureFournisseur}
	 */
	public $invoice;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->invoice = new FactureFournisseur($this->db);
	}

	/**
	 * Get properties of a supplier invoice object
	 *
	 * Return an array with supplier invoice information
	 *
	 * @param	int		$id				ID of supplier invoice
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws 	RestException 403
	 * @throws 	RestException 404
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "lire")) {
			throw new RestException(403);
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		// Retrieve credit note ids
		$this->invoice->getListIdAvoirFromInvoice();

		$this->invoice->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * List invoices
	 *
	 * Get a list of supplier invoices
	 *
	 * @param string	$sortfield		  Sort field
	 * @param string	$sortorder		  Sort order
	 * @param int		$limit			  Limit for list
	 * @param int		$page			  Page number
	 * @param string	$thirdparty_ids	  Thirdparty ids to filter invoices of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$status			  Filter by invoice status : draft | unpaid | paid | cancelled
	 * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @param string    $properties		  Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data  If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return array                      Array of invoice objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $status = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "lire")) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight("societe", "client", "voir")) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn AS t";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_fourn_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN (' . getEntity('supplier_invoice') . ')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (" . $this->db->sanitize($socids) . ")";
		}
		// Filter by status
		if ($status == 'draft') {
			$sql .= " AND t.fk_statut IN (0)";
		}
		if ($status == 'unpaid') {
			$sql .= " AND t.fk_statut IN (1)";
		}
		if ($status == 'paid') {
			$sql .= " AND t.fk_statut IN (2)";
		}
		if ($status == 'cancelled') {
			$sql .= " AND t.fk_statut IN (3)";
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
				throw new RestException(400, 'Error when validating parameter sqlfilters -> ' . $errormessage);
			}
		}

		//this query will return total supplier invoices with the filters given
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
				$invoice_static = new FactureFournisseur($this->db);
				if ($invoice_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($invoice_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve supplier invoice list : ' . $this->db->lasterror());
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
	 * Create supplier invoice object
	 *
	 * Note: soc_id = dolibarr_order_id
	 *
	 * Example: {'ref': 'auto', 'ref_supplier': '7985630', 'socid': 1, 'note': 'Inserted with Python', 'order_supplier': 1, 'date': '2021-07-28'}
	 *
	 * @param array $request_data Request datas
	 *
	 * @return int  ID of supplier invoice
	 *
	 * @throws RestException 403
	 * @throws RestException 500	System error
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->invoice->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->invoice->$field = $this->_checkValForAPI($field, $value, $this->invoice);
		}
		if (!array_key_exists('date', $request_data)) {
			$this->invoice->date = dol_now();
		}

		if ($this->invoice->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating invoice ", array_merge(array($this->invoice->error), $this->invoice->errors));
		}
		return $this->invoice->id;
	}

	/**
	 * Update supplier invoice
	 *
	 * @param 	int   	$id             	Id of supplier invoice to update
	 * @param 	array 	$request_data  		Datas
	 * @return 	Object|false				Updated object
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->invoice->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->invoice->array_options[$index] = $this->_checkValForAPI($field, $val, $this->invoice);
				}
				continue;
			}
			$this->invoice->$field = $this->_checkValForAPI($field, $value, $this->invoice);
		}

		if ($this->invoice->update(DolibarrApiAccess::$user)) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete supplier invoice
	 *
	 * @param int   $id Supplier invoice ID
	 *
	 * @return array
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500	System error
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "supprimer")) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if ($this->invoice->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error when deleting invoice');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Supplier invoice deleted'
			)
		);
	}

	/**
	 * Validate an invoice
	 *
	 * @param   int $id             Invoice ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  array
	 *
	 * @throws RestException 304
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 405
	 * @throws RestException 500	System error
	 */
	public function validate($id, $idwarehouse = 0, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		$result = $this->invoice->validate(DolibarrApiAccess::$user, '', $idwarehouse, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. The invoice is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Invoice: ' . $this->invoice->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Invoice validated (Ref=' . $this->invoice->ref . ')'
			)
		);
	}

	/**
	 * Get list of payments of a given supplier invoice
	 *
	 * @param int   $id             Id of SupplierInvoice
	 *
	 * @url     GET {id}/payments
	 *
	 * @return array
	 * @throws RestException 400
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function getPayments($id)
	{
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}

		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "lire")) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		$result = $this->invoice->getListOfPayments();
		if ($this->invoice->error !== '') {
			throw new RestException(405, $this->invoice->error);
		}

		return $result;
	}


	/**
	 * Add payment line to a specific supplier invoice with the remain to pay as amount.
	 *
	 * @param int     $id                               Id of invoice
	 * @param int     $datepaye           {@from body}  Payment date        {@type timestamp}
	 * @param int     $payment_mode_id    {@from body}  Payment mode ID (look it up via REST GET to /setup/dictionary/payment_types) {@min 1}
	 * @param string  $closepaidinvoices  {@from body}  Close paid invoices {@choice yes,no}
	 * @param int     $accountid          {@from body}  Bank account ID (look it up via REST GET to /bankaccounts) {@min 1}
	 * @param string  $num_payment        {@from body}  Payment number (optional)
	 * @param string  $comment            {@from body}  Note (optional)
	 * @param string  $chqemetteur        {@from body}  Payment issuer (mandatory if payment_mode_id corresponds to 'CHQ'-payment type)
	 * @param string  $chqbank            {@from body}  Issuer bank name (optional)
	 * @param float   $amount			  {@from body}  Amount of payment if we don't want to use the remain to pay
	 *
	 * @url     POST {id}/payments
	 *
	 * @return int  Payment ID
	 *
	 * @throws RestException 400
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function addPayment($id, $datepaye, $payment_mode_id, $closepaidinvoices, $accountid, $num_payment = '', $comment = '', $chqemetteur = '', $chqbank = '', $amount = null)
	{
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}

		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (isModEnabled("bank")) {
			if (empty($accountid)) {
				throw new RestException(400, 'Bank account ID is mandatory');
			}
		}

		if (empty($payment_mode_id)) {
			throw new RestException(400, 'Payment mode ID is mandatory');
		}

		if (null !== $amount && $amount > 0) {
			// We use the amount given in parameter
			$paymentamount = $amount;
		} else {
			// We calculate the remain to pay, and use it as amount
			$totalpaid = $this->invoice->getSommePaiement();
			$totaldeposits = $this->invoice->getSumDepositsUsed();
			$paymentamount = price2num($this->invoice->total_ttc - $totalpaid - $totaldeposits, 'MT');
		}

		$this->db->begin();

		$amounts = array();
		$multicurrency_amounts = array();

		$paymentamount = (float) price2num($paymentamount, 'MT');

		$amounts[$id] = $paymentamount;

		// Multicurrency
		$newvalue = (float) price2num($this->invoice->multicurrency_total_ttc, 'MT');
		$multicurrency_amounts[$id] = $newvalue;

		// Creation of payment line
		$paiement = new PaiementFourn($this->db);
		$paiement->datepaye     = $datepaye;
		$paiement->amounts      = $amounts; // Array with all payments dispatching with invoice id
		$paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
		$paiement->paiementid = $payment_mode_id;
		$paiement->paiementcode = (string) dol_getIdFromCode($this->db, (string) $payment_mode_id, 'c_paiement', 'id', 'code', 1);
		$paiement->num_payment = $num_payment;
		$paiement->note_public = $comment;

		$paiement_id = $paiement->create(DolibarrApiAccess::$user, ($closepaidinvoices == 'yes' ? 1 : 0)); // This include closing invoices
		if ($paiement_id < 0) {
			$this->db->rollback();
			throw new RestException(400, 'Payment error : ' . $paiement->error);
		}

		if (isModEnabled("bank")) {
			$result = $paiement->addPaymentToBank(DolibarrApiAccess::$user, 'payment_supplier', '(SupplierInvoicePayment)', $accountid, $chqemetteur, $chqbank);
			if ($result < 0) {
				$this->db->rollback();
				throw new RestException(400, 'Add payment to bank error : ' . $paiement->error);
			}
		}

		$this->db->commit();

		return $paiement_id;
	}

	/**
	 * Get lines of a supplier invoice
	 *
	 * @param int   $id             Id of supplier invoice
	 *
	 * @url	GET {id}/lines
	 *
	 * @return array
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getLines($id)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		$this->invoice->fetch_lines();
		$result = array();
		foreach ($this->invoice->lines as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Add a line to given supplier invoice
	 *
	 * Note: socid = dolibarr_order_id, pu_ht = net price, remise = discount
	 *
	 * Example: {'socid': 1, 'qty': 1, 'pu_ht': 21.0, 'tva_tx': 25.0, 'fk_product': '1189', 'product_type': 0, 'remise_percent': 1.0, 'vat_src_code': None}
	 *
	 * @param int   $id             Id of supplier invoice to update
	 * @param array $request_data   supplier invoice line data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int|bool
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		$request_data = (object) $request_data;

		$request_data->description = sanitizeVal($request_data->description, 'restricthtml');
		$request_data->ref_supplier = sanitizeVal($request_data->ref_supplier);

		$updateRes = $this->invoice->addline(
			$request_data->description,
			$request_data->pu_ht,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->qty,
			$request_data->fk_product,
			$request_data->remise_percent,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->fk_code_ventilation,
			$request_data->info_bits,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->product_type,
			$request_data->rang,
			false,
			$request_data->array_options,
			$request_data->fk_unit,
			$request_data->origin_id,
			$request_data->multicurrency_subprice,
			$request_data->ref_supplier,
			$request_data->special_code
		);

		if ($updateRes < 0) {
			throw new RestException(400, 'Unable to insert the new line. Check your inputs. ' . $this->invoice->error);
		}

		return $updateRes;
	}

	/**
	 * Update a line to a given supplier invoice
	 *
	 * @param int   $id             Id of supplier invoice to update
	 * @param int   $lineid         Id of line to update
	 * @param array $request_data   InvoiceLine data
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return object
	 *
	 * @throws RestException 403 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 304 Error
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		$request_data = (object) $request_data;

		$request_data->description = sanitizeVal($request_data->description, 'restricthtml');
		$request_data->ref_supplier = sanitizeVal($request_data->ref_supplier);

		$updateRes = $this->invoice->updateline(
			$lineid,
			$request_data->description,
			$request_data->pu_ht,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->qty,
			$request_data->fk_product,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->info_bits,
			$request_data->product_type,
			$request_data->remise_percent,
			false,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->array_options,
			$request_data->fk_unit,
			$request_data->multicurrency_subprice,
			$request_data->ref_supplier,
			$request_data->rang
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		} else {
			throw new RestException(304, $this->invoice->error);
		}
	}

	/**
	 * Deletes a line of a given supplier invoice
	 *
	 * @param int   $id             Id of supplier invoice
	 * @param int   $lineid			Id of the line to delete
	 *
	 * @url     DELETE {id}/lines/{lineid}
	 *
	 * @return array
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

		if (!DolibarrApiAccess::$user->hasRight("fournisseur", "facture", "creer")) {
			throw new RestException(403);
		}
		if (!DolibarrApi::_checkAccessToResource('fournisseur', $id, 'facture_fourn', 'facture')) {
			throw new RestException(403, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		// TODO Check the lineid $lineid is a line of object

		$updateRes = $this->invoice->deleteLine($lineid);
		if ($updateRes > 0) {
			return array(
				'success' => array(
					'code' => 200,
					'message' => 'line '.$lineid.' deleted'
				)
			);
		} else {
			throw new RestException(405, $this->invoice->error);
		}
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
		$invoice = array();
		foreach (SupplierInvoices::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$invoice[$field] = $data[$field];
		}
		return $invoice;
	}
}
