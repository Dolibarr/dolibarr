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

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

/**
 * API class for supplier invoices
 *
 * @property DoliDB db
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierInvoices extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
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
	 * @param 	int 	$id ID of supplier invoice
	 * @return 	array|mixed data without useless information
	 *
	 * @throws 	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->lire) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->invoice->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * List invoices
	 *
	 * Get a list of supplier invoices
	 *
	 * @param string	$sortfield	      Sort field
	 * @param string	$sortorder	      Sort order
	 * @param int		$limit		      Limit for list
	 * @param int		$page		      Page number
	 * @param string   	$thirdparty_ids	  Thirdparty ids to filter invoices of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$status		      Filter by invoice status : draft | unpaid | paid | cancelled
	 * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @return array                      Array of invoice objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $status = '', $sqlfilters = '')
	{
		global $db;

		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->lire) {
			throw new RestException(401);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->rights->societe->client->voir) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		// We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		if (!DolibarrApiAccess::$user->rights->societe->client->voir || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as t";

		// We need this table joined to the select in order to filter by sale
		if (!DolibarrApiAccess::$user->rights->societe->client->voir || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}

		$sql .= ' WHERE t.entity IN ('.getEntity('supplier_invoice').')';
		if (!DolibarrApiAccess::$user->rights->societe->client->voir || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
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
		if ($status == 'unpaid') {
			$sql .= " AND t.fk_statut IN (1)";
		}
		if ($status == 'paid') {
			$sql .= " AND t.fk_statut IN (2)";
		}
		if ($status == 'cancelled') {
			$sql .= " AND t.fk_statut IN (3)";
		}
		// Insert sale filter
		if ($search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
		// Add sql filters
		if ($sqlfilters) {
			if (!DolibarrApi::_checkFilters($sqlfilters)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
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
				$invoice_static = new FactureFournisseur($this->db);
				if ($invoice_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($invoice_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve supplier invoice list : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No supplier invoice found');
		}
		return $obj_ret;
	}

	/**
	 * Create supplier invoice object
	 *
	 * @param array $request_data Request datas
	 *
	 * @return int  ID of supplier invoice
	 *
	 * @throws RestException 401
	 * @throws RestException 500
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->invoice->$field = $value;
		}
		if (!array_key_exists('date', $request_data)) {
			$this->invoice->date = dol_now();
		}

		if ($this->invoice->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating order", array_merge(array($this->invoice->error), $this->invoice->errors));
		}
		return $this->invoice->id;
	}

	/**
	 * Update supplier invoice
	 *
	 * @param int   $id             Id of supplier invoice to update
	 * @param array $request_data   Datas
	 *
	 * @return int
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$this->invoice->$field = $value;
		}

		if ($this->invoice->update($id, DolibarrApiAccess::$user)) {
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
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->supprimer) {
			throw new RestException(401);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($this->invoice->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500);
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
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 * @throws RestException 500
	 */
	public function validate($id, $idwarehouse = 0, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->validate(DolibarrApiAccess::$user, '', $idwarehouse, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. The invoice is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Invoice: '.$this->invoice->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Invoice validated (Ref='.$this->invoice->ref.')'
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
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function getPayments($id)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->lire) {
			throw new RestException(401);
		}
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		$result = $this->invoice->getListOfPayments();
		if ($result < 0) {
			throw new RestException(405, $this->invoice->error);
		}

		return $result;
	}


	/**
	 * Add payment line to a specific supplier invoice with the remain to pay as amount.
	 *
	 * @param int     $id                               Id of invoice
	 * @param string  $datepaye           {@from body}  Payment date        {@type timestamp}
	 * @param int     $payment_mode_id    {@from body}  Payment mode ID (look it up via REST GET to /setup/dictionary/payment_types) {@min 1}
	 * @param string  $closepaidinvoices  {@from body}  Close paid invoices {@choice yes,no}
	 * @param int     $accountid          {@from body}  Bank account ID (look it up via REST GET to /bankaccounts) {@min 1}
	 * @param string  $num_payment        {@from body}  Payment number (optional)
	 * @param string  $comment            {@from body}  Note (optional)
	 * @param string  $chqemetteur        {@from body}  Payment issuer (mandatory if payment_mode_id corresponds to 'CHQ'-payment type)
	 * @param string  $chqbank            {@from body}  Issuer bank name (optional)
	 *
	 * @url     POST {id}/payments
	 *
	 * @return int  Payment ID
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function addPayment($id, $datepaye, $payment_mode_id, $closepaidinvoices, $accountid, $num_payment = '', $comment = '', $chqemetteur = '', $chqbank = '')
	{
		global $conf;

		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!empty($conf->banque->enabled)) {
			if (empty($accountid)) {
				throw new RestException(400, 'Bank account ID is mandatory');
			}
		}

		if (empty($payment_mode_id)) {
			throw new RestException(400, 'Payment mode ID is mandatory');
		}


		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		// Calculate amount to pay
		$totalpaye = $this->invoice->getSommePaiement();
		$totaldeposits = $this->invoice->getSumDepositsUsed();
		$resteapayer = price2num($this->invoice->total_ttc - $totalpaye - $totaldeposits, 'MT');

		$this->db->begin();

		$amounts = array();
		$multicurrency_amounts = array();

		$resteapayer = price2num($resteapayer, 'MT');
		$amounts[$id] = $resteapayer;

		// Multicurrency
		$newvalue = price2num($this->invoice->multicurrency_total_ttc, 'MT');
		$multicurrency_amounts[$id] = $newvalue;

		// Creation of payment line
		$paiement = new PaiementFourn($this->db);
		$paiement->datepaye     = $datepaye;
		$paiement->amounts      = $amounts; // Array with all payments dispatching with invoice id
		$paiement->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
		$paiement->paiementid = $payment_mode_id;
		$paiement->paiementcode = dol_getIdFromCode($this->db, $payment_mode_id, 'c_paiement', 'id', 'code', 1);
		$paiement->oper = $paiement->paiementcode; // For backward compatibility
		$paiement->num_payment = $num_payment;
		$paiement->note_public = $comment;

		$paiement_id = $paiement->create(DolibarrApiAccess::$user, ($closepaidinvoices == 'yes' ? 1 : 0)); // This include closing invoices
		if ($paiement_id < 0) {
			$this->db->rollback();
			throw new RestException(400, 'Payment error : '.$paiement->error);
		}

		if (!empty($conf->banque->enabled)) {
			$result = $paiement->addPaymentToBank(DolibarrApiAccess::$user, 'payment_supplier', '(SupplierInvoicePayment)', $accountid, $chqemetteur, $chqbank);
			if ($result < 0) {
				$this->db->rollback();
				throw new RestException(400, 'Add payment to bank error : '.$paiement->error);
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
	 */
	public function getLines($id)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
	 * @param int   $id             Id of supplier invoice to update
	 * @param array $request_data   supplier invoice line data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int|bool
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->description = checkVal($request_data->description, 'restricthtml');
		$request_data->ref_supplier = checkVal($request_data->ref_supplier);

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
			$request_data->ventil,
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
			throw new RestException(400, 'Unable to insert the new line. Check your inputs. '.$this->invoice->error);
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
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 304 Error
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->description = checkVal($request_data->description, 'restricthtml');
		$request_data->ref_supplier = checkVal($request_data->ref_supplier);

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
			$request_data->ref_supplier
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
	 * @param int   $lineid 		Id of the line to delete
	 *
	 * @url     DELETE {id}/lines/{lineid}
	 *
	 * @return array
	 *
	 * @throws RestException 400 Bad parameters
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 * @throws RestException 405 Error
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->rights->fournisseur->facture->creer) {
			throw new RestException(401);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier invoice not found');
		}

		if (empty($lineid)) {
			throw new RestException(400, 'Line ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('fournisseur', $this->invoice->id, 'facture_fourn', 'facture')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// TODO Check the lineid $lineid is a line of ojbect

		$updateRes = $this->invoice->deleteline($lineid);
		if ($updateRes > 0) {
			return $this->get($id);
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
