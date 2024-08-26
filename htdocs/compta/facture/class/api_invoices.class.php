<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2020   Thibault FOUCART		<support@ptibogxiv.net>
 * Copyright (C) 2023	Joachim Kueter			<git-jk@bloxera.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';


/**
 * API class for invoices
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Invoices extends DolibarrApi
{
	/**
	 *
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
	);

	/**
	 * @var Facture $invoice {@type Facture}
	 */
	private $invoice;

	/**
	 * @var FactureRec $templte_invoice {@type FactureRec}
	 */
	private $template_invoice;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->invoice = new Facture($this->db);
		$this->template_invoice = new FactureRec($this->db);
	}

	/**
	 * Get properties of a invoice object
	 *
	 * Return an array with invoice information
	 *
	 * @param	int		$id				ID of invoice
	 * @param   int     $contact_list	0:Return array contains all properties, 1:Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id, $contact_list = 1)
	{
		return $this->_fetch($id, '', '', $contact_list);
	}

	/**
	 * Get properties of an invoice object by ref
	 *
	 * Return an array with invoice information
	 *
	 * @param   string		$ref			Ref of object
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object						Object with cleaned properties
	 *
	 * @url GET    ref/{ref}
	 *
	 * @throws	RestException
	 */
	public function getByRef($ref, $contact_list = 1)
	{
		return $this->_fetch('', $ref, '', $contact_list);
	}

	/**
	 * Get properties of an invoice object by ref_ext
	 *
	 * Return an array with invoice information
	 *
	 * @param   string		$ref_ext		External reference of object
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object						Object with cleaned properties
	 *
	 * @url GET    ref_ext/{ref_ext}
	 *
	 * @throws	RestException
	 */
	public function getByRefExt($ref_ext, $contact_list = 1)
	{
		return $this->_fetch('', '', $ref_ext, $contact_list);
	}

	/**
	 * Get properties of an invoice object
	 *
	 * Return an array with invoice information
	 *
	 * @param   int         $id				ID of order
	 * @param	string		$ref			Ref of object
	 * @param	string		$ref_ext		External reference of object
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetch($id, $ref = '', $ref_ext = '', $contact_list = 1)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id, $ref, $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		// Get payment details
		$this->invoice->totalpaid = $this->invoice->getSommePaiement();
		$this->invoice->totalcreditnotes = $this->invoice->getSumCreditNotesUsed();
		$this->invoice->totaldeposits = $this->invoice->getSumDepositsUsed();
		$this->invoice->remaintopay = price2num($this->invoice->total_ttc - $this->invoice->totalpaid - $this->invoice->totalcreditnotes - $this->invoice->totaldeposits, 'MT');

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Add external contacts ids
		if ($contact_list > -1) {
			$tmparray = $this->invoice->liste_contact(-1, 'external', $contact_list);
			if (is_array($tmparray)) {
				$this->invoice->contacts_ids = $tmparray;
			}
		}

		$this->invoice->fetchObjectLinked();

		// Add online_payment_url, copied from order
		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		$this->invoice->online_payment_url = getOnlinePaymentUrl(0, 'invoice', $this->invoice->ref);

		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * List invoices
	 *
	 * Get a list of invoices
	 *
	 * @param string	$sortfield		  Sort field
	 * @param string	$sortorder		  Sort order
	 * @param int		$limit			  Limit for list
	 * @param int		$page			  Page number
	 * @param string	$thirdparty_ids	  Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string	$status			  Filter by invoice status : STATUS_DRAFT | STATUS_VALIDATED | STATUS_CLOSED | STATUS_ABANDONED
	 * @param string    $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	      Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data  If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0
	 * @return array                      Array of invoice objects
	 *
	 * @throws RestException 404 Not found
	 * @throws RestException 503 Error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $status = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('invoice').')';
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}

		// Filter by status
		if (in_array($status, array('STATUS_DRAFT', 'STATUS_VALIDATED', 'STATUS_CLOSED', 'STATUS_ABANDONED'))) {
			$const_status = (int) constant('Facture::'.$status); // To pass phpunit test
			$sql .=  " AND t.fk_statut IN (".$this->db->sanitize($const_status).")";
		} elseif (!empty($status)) {
			throw new RestException(400, 'Invalid invoice status');
		}

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total invoices with the filters given
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
				$invoice_static = new Facture($this->db);
				if ($invoice_static->fetch($obj->rowid)) {
					// Get payment details
					$invoice_static->totalpaid = $invoice_static->getSommePaiement();
					$invoice_static->totalcreditnotes = $invoice_static->getSumCreditNotesUsed();
					$invoice_static->totaldeposits = $invoice_static->getSumDepositsUsed();
					$invoice_static->remaintopay = price2num($invoice_static->total_ttc - $invoice_static->totalpaid - $invoice_static->totalcreditnotes - $invoice_static->totaldeposits, 'MT');

					// Add external contacts ids
					$tmparray = $invoice_static->liste_contact(-1, 'external', 1);
					if (is_array($tmparray)) {
						$invoice_static->contacts_ids = $tmparray;
					}
					// Add online_payment_url, copied from order
					require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
					$invoice_static->online_payment_url = getOnlinePaymentUrl(0, 'invoice', $invoice_static->ref);

					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($invoice_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve invoice list : '.$this->db->lasterror());
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
	 * Create invoice object
	 *
	 * @param array $request_data   Request datas
	 * @return int                  ID of invoice
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
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
		/* We keep lines as an array
		 if (isset($request_data["lines"])) {
			$lines = array();
			foreach ($request_data["lines"] as $line) {
				array_push($lines, (object) $line);
			}
			$this->invoice->lines = $lines;
		}*/

		if ($this->invoice->create(DolibarrApiAccess::$user, 0, (empty($request_data["date_lim_reglement"]) ? 0 : $request_data["date_lim_reglement"])) < 0) {
			throw new RestException(500, "Error creating invoice", array_merge(array($this->invoice->error), $this->invoice->errors));
		}
		return ((int) $this->invoice->id);
	}

	/**
	 * Create an invoice using an existing order.
	 *
	 * @param int   $orderid       Id of the order
	 * @return	Object				Object with cleaned properties
	 *
	 * @url     POST /createfromorder/{orderid}
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function createInvoiceFromOrder($orderid)
	{
		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

		if (!DolibarrApiAccess::$user->hasRight('commande', 'lire')) {
			throw new RestException(403);
		}
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($orderid)) {
			throw new RestException(400, 'Order ID is mandatory');
		}

		$order = new Commande($this->db);
		$result = $order->fetch($orderid);
		if (!$result) {
			throw new RestException(404, 'Order not found');
		}

		$result = $this->invoice->createFromOrder($order, DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(405, $this->invoice->error);
		}
		$this->invoice->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	* Create an invoice using a contract.
	*
	* @param int   $contractid       Id of the contract
	* @return     Object                          Object with cleaned properties
	*
	* @url     POST /createfromcontract/{contractid}
	*
	* @throws RestException 400
	* @throws RestException 401
	* @throws RestException 404
	* @throws RestException 405
	*/
	public function createInvoiceFromContract($contractid)
	{
		require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

		if (!DolibarrApiAccess::$user->hasRight('contrat', 'lire')) {
			throw new RestException(403);
		}
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($contractid)) {
			throw new RestException(400, 'Contract ID is mandatory');
		}

		$contract = new Contrat($this->db);
		$result = $contract->fetch($contractid);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		$result = $this->invoice->createFromContract($contract, DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(405, $this->invoice->error);
		}
		$this->invoice->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * Get lines of an invoice
	 *
	 * @param	int   $id				Id of invoice
	 * @return	array					Array of lines
	 *
	 * @url	GET {id}/lines
	 */
	public function getLines($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->invoice->getLinesArray();
		$result = array();
		foreach ($this->invoice->lines as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Update a line to a given invoice
	 *
	 * @param	int   $id             Id of invoice to update
	 * @param	int   $lineid         Id of line to update
	 * @param	array $request_data   InvoiceLine data
	 * @return	Object				  Object with cleaned properties
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404 Invoice not found
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->invoice->updateline(
			$lineid,
			$request_data->desc,
			$request_data->subprice,
			$request_data->qty,
			$request_data->remise_percent,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->info_bits,
			$request_data->product_type,
			$request_data->fk_parent_line,
			0,
			$request_data->fk_fournprice,
			$request_data->pa_ht,
			$request_data->label,
			$request_data->special_code,
			$request_data->array_options,
			$request_data->situation_percent,
			$request_data->fk_unit,
			$request_data->multicurrency_subprice,
			0,
			$request_data->ref_ext,
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
	 * Add a contact type of given invoice
	 *
	 * @param	int    $id             Id of invoice to update
	 * @param	int    $contactid      Id of contact to add
	 * @param	string $type           Type of the contact (BILLING, SHIPPING, CUSTOMER)
	 * @return	array
	 *
	 * @url	POST {id}/contact/{contactid}/{type}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function postContact($id, $contactid, $type)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);

		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!in_array($type, array('BILLING', 'SHIPPING', 'CUSTOMER'), true)) {
			throw new RestException(500, 'Availables types: BILLING, SHIPPING OR CUSTOMER');
		}

		if (!DolibarrApi::_checkAccessToResource('invoice', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->add_contact($contactid, $type, 'external');

		if (!$result) {
			throw new RestException(500, 'Error when added the contact');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contact linked to the invoice'
			)
		);
	}

	/**
	 * Delete a contact type of given invoice
	 *
	 * @param	int    $id             Id of invoice to update
	 * @param	int    $contactid      Row key of the contact in the array contact_ids.
	 * @param	string $type           Type of the contact (BILLING, SHIPPING, CUSTOMER).
	 * @return	Object				   Object with cleaned properties
	 *
	 * @url	DELETE {id}/contact/{contactid}/{type}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function deleteContact($id, $contactid, $type)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);

		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('invoice', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$contacts = $this->invoice->liste_contact();

		foreach ($contacts as $contact) {
			if ($contact['id'] == $contactid && $contact['code'] == $type) {
				$result = $this->invoice->delete_contact($contact['rowid']);

				if (!$result) {
					throw new RestException(500, 'Error when deleted the contact');
				}
			}
		}

		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * Deletes a line of a given invoice
	 *
	 * @param	int   $id				Id of invoice
	 * @param	int   $lineid			Id of the line to delete
	 * @return	Object					Object with cleaned properties
	 *
	 * @url     DELETE {id}/lines/{lineid}
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($lineid)) {
			throw new RestException(400, 'Line ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		$updateRes = $this->invoice->deleteLine($lineid, $id);
		if ($updateRes > 0) {
			return $this->get($id);
		} else {
			throw new RestException(405, $this->invoice->error);
		}
	}

	/**
	 * Update invoice
	 *
	 * @param	int				$id             Id of invoice to update
	 * @param	array			$request_data   Datas
	 * @return	Object|false					Object with cleaned properties
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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

			// If cond reglement => update date lim reglement
			if ($field == 'cond_reglement_id') {
				$this->invoice->date_lim_reglement = $this->invoice->calculate_date_lim_reglement();
			}
		}

		// update bank account
		if (!empty($this->invoice->fk_account)) {
			if ($this->invoice->setBankAccount($this->invoice->fk_account) == 0) {
				throw new RestException(400, $this->invoice->error);
			}
		}

		if ($this->invoice->update(DolibarrApiAccess::$user)) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete invoice
	 *
	 * @param	int		$id		Invoice ID
	 * @return	array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->delete(DolibarrApiAccess::$user);
		if ($result < 0) {
			throw new RestException(500, 'Error when deleting invoice');
		} elseif ($result == 0) {
			throw new RestException(403, 'Invoice not erasable');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Invoice deleted'
			)
		);
	}

	/**
	 * Add a line to a given invoice
	 *
	 * Example of POST query :
	 * {
	 *     "desc": "Desc", "subprice": "1.00000000", "qty": "1", "tva_tx": "20.000", "localtax1_tx": "0.000", "localtax2_tx": "0.000",
	 *     "fk_product": "1", "remise_percent": "0", "date_start": "", "date_end": "", "fk_code_ventilation": 0,  "info_bits": "0",
	 *     "fk_remise_except": null,  "product_type": "1", "rang": "-1", "special_code": "0", "fk_parent_line": null, "fk_fournprice": null,
	 *     "pa_ht": "0.00000000", "label": "", "array_options": [], "situation_percent": "100", "fk_prev_id": null, "fk_unit": null
	 * }
	 *
	 * @param int   $id             Id of invoice
	 * @param array $request_data   InvoiceLine data
	 *
	 * @url     POST {id}/lines
	 *
	 * @return int
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 400
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		// Reset fk_parent_line for no child products and special product
		if (($request_data->product_type != 9 && empty($request_data->fk_parent_line)) || $request_data->product_type == 9) {
			$request_data->fk_parent_line = 0;
		}

		// calculate pa_ht
		$marginInfos = getMarginInfos($request_data->subprice, $request_data->remise_percent, $request_data->tva_tx, $request_data->localtax1_tx, $request_data->localtax2_tx, $request_data->fk_fournprice, $request_data->pa_ht);
		$pa_ht = $marginInfos[0];

		$updateRes = $this->invoice->addline(
			$request_data->desc,
			$request_data->subprice,
			$request_data->qty,
			$request_data->tva_tx,
			$request_data->localtax1_tx,
			$request_data->localtax2_tx,
			$request_data->fk_product,
			$request_data->remise_percent,
			$request_data->date_start,
			$request_data->date_end,
			$request_data->fk_code_ventilation,
			$request_data->info_bits,
			$request_data->fk_remise_except,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->subprice,
			$request_data->product_type,
			$request_data->rang,
			$request_data->special_code,
			$request_data->origin,
			$request_data->origin_id,
			$request_data->fk_parent_line,
			empty($request_data->fk_fournprice) ? null : $request_data->fk_fournprice,
			$pa_ht,
			$request_data->label,
			$request_data->array_options,
			$request_data->situation_percent,
			$request_data->fk_prev_id,
			$request_data->fk_unit,
			0,
			$request_data->ref_ext
		);

		if ($updateRes < 0) {
			throw new RestException(400, 'Unable to insert the new line. Check your inputs. '.$this->invoice->error);
		}

		return $updateRes;
	}

	/**
	 * Adds a contact to an invoice
	 *
	 * @param   int		$id					Order ID
	 * @param   int		$fk_socpeople			Id of thirdparty contact (if source = 'external') or id of user (if source = 'internal') to link
	 * @param   string	$type_contact           Type of contact (code). Must a code found into table llx_c_type_contact. For example: BILLING
	 * @param   string  $source					external=Contact extern (llx_socpeople), internal=Contact intern (llx_user)
	 * @param   int     $notrigger              Disable all triggers
	 *
	 * @url POST    {id}/contacts
	 *
	 * @return  object
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 *
	 */
	public function addContact($id, $fk_socpeople, $type_contact, $source, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->add_contact($fk_socpeople, $type_contact, $source, $notrigger);
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->invoice->error);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->invoice);
	}



	/**
	 * Sets an invoice as draft
	 *
	 * @param   int $id             Order ID
	 * @param   int $idwarehouse    Warehouse ID
	 * @return	Object				Object with cleaned properties
	 *
	 * @url POST    {id}/settodraft
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 *
	 */
	public function settodraft($id, $idwarehouse = -1)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->setDraft(DolibarrApiAccess::$user, $idwarehouse);
		if ($result == 0) {
			throw new RestException(304, 'Nothing done.');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->invoice->error);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->invoice);
	}


	/**
	 * Validate an invoice
	 *
	 * If you get a bad value for param notrigger check that ou provide this in body
	 * {
	 *   "idwarehouse": 0,
	 *   "notrigger": 0
	 * }
	 *
	 * @param   int $id             	Invoice ID
	 * @param   string $force_number   	force ref invoice
	 * @param   int $idwarehouse    	Warehouse ID
	 * @param   int $notrigger      	1=Does not execute triggers, 0= execute triggers
	 * @return	Object|false			Object with cleaned properties
	 *
	 * @url POST    {id}/validate
	 */
	public function validate($id, $force_number = '', $idwarehouse = 0, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->validate(DolibarrApiAccess::$user, $force_number, $idwarehouse, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Invoice: '.$this->invoice->error);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// copy from order
		require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
		$this->invoice->online_payment_url = getOnlinePaymentUrl(0, 'invoice', $this->invoice->ref);

		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * Sets an invoice as paid
	 *
	 * @param   int		$id            Order ID
	 * @param   string	$close_code    Code filled if we classify to 'Paid completely' when payment is not complete (for escompte for example)
	 * @param   string	$close_note    Comment defined if we classify to 'Paid' when payment is not complete (for escompte for example)
	 * @return	Object				   Object with cleaned properties
	 *
	 * @url POST    {id}/settopaid
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function settopaid($id, $close_code = '', $close_note = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->setPaid(DolibarrApiAccess::$user, $close_code, $close_note);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->invoice->error);
		}


		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->invoice);
	}


	/**
	 * Sets an invoice as unpaid
	 *
	 * @param   int     $id				Order ID
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/settounpaid
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function settounpaid($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->setUnpaid(DolibarrApiAccess::$user);
		if ($result == 0) {
			throw new RestException(304, 'Nothing done');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error : '.$this->invoice->error);
		}


		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * Get discount from invoice
	 *
	 * @param int   $id             Id of invoice
	 * @return	Object				Object with cleaned properties
	 *
	 * @url	GET {id}/discount
	 */
	public function getDiscount($id)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$discountcheck = new DiscountAbsolute($this->db);
		$result = $discountcheck->fetch(0, $this->invoice->id);

		if ($result == 0) {
			throw new RestException(404, 'Discount not found');
		}
		if ($result < 0) {
			throw new RestException(500, $discountcheck->error);
		}

		return parent::_cleanObjectDatas($discountcheck);
	}

	/**
	 * Create a discount (credit available) for a credit note or a deposit.
	 *
	 * @param   int		$id				Invoice ID
	 * @return	Object					Object with cleaned properties
	 *
	 * @url POST    {id}/markAsCreditAvailable
	 *
	 * @throws RestException 304
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 500 System error
	 */
	public function markAsCreditAvailable($id)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $this->invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($this->invoice->paye) {
			throw new RestException(500, 'Alreay paid');
		}

		$this->invoice->fetch($id);
		$this->invoice->fetch_thirdparty();

		// Check if there is already a discount (protection to avoid duplicate creation when resubmit post)
		$discountcheck = new DiscountAbsolute($this->db);
		$result = $discountcheck->fetch(0, $this->invoice->id);

		$canconvert = 0;
		if ($this->invoice->type == Facture::TYPE_DEPOSIT && empty($discountcheck->id)) {
			$canconvert = 1; // we can convert deposit into discount if deposit is paid (completely, partially or not at all) and not already converted (see real condition into condition used to show button converttoreduc)
		}
		if (($this->invoice->type == Facture::TYPE_CREDIT_NOTE || $this->invoice->type == Facture::TYPE_STANDARD) && $this->invoice->paye == 0 && empty($discountcheck->id)) {
			$canconvert = 1; // we can convert credit note into discount if credit note is not paid back and not already converted and amount of payment is 0 (see real condition into condition used to show button converttoreduc)
		}
		if ($canconvert) {
			$this->db->begin();

			$amount_ht = $amount_tva = $amount_ttc = array();
			$multicurrency_amount_ht = $multicurrency_amount_tva = $multicurrency_amount_ttc = array();

			// Loop on each vat rate
			$i = 0;
			foreach ($this->invoice->lines as $line) {
				if ($line->product_type < 9 && $line->total_ht != 0) { // Remove lines with product_type greater than or equal to 9
					// no need to create discount if amount is null
					$amount_ht[$line->tva_tx] += $line->total_ht;
					$amount_tva[$line->tva_tx] += $line->total_tva;
					$amount_ttc[$line->tva_tx] += $line->total_ttc;
					$multicurrency_amount_ht[$line->tva_tx] += $line->multicurrency_total_ht;
					$multicurrency_amount_tva[$line->tva_tx] += $line->multicurrency_total_tva;
					$multicurrency_amount_ttc[$line->tva_tx] += $line->multicurrency_total_ttc;
					$i++;
				}
			}

			// Insert one discount by VAT rate category
			$discount = new DiscountAbsolute($this->db);
			if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
				$discount->description = '(CREDIT_NOTE)';
			} elseif ($this->invoice->type == Facture::TYPE_DEPOSIT) {
				$discount->description = '(DEPOSIT)';
			} elseif ($this->invoice->type == Facture::TYPE_STANDARD || $this->invoice->type == Facture::TYPE_REPLACEMENT || $this->invoice->type == Facture::TYPE_SITUATION) {
				$discount->description = '(EXCESS RECEIVED)';
			} else {
				throw new RestException(500, 'Cant convert to reduc an Invoice of this type');
			}

			$discount->fk_soc = $this->invoice->socid;
			$discount->socid = $this->invoice->socid;
			$discount->fk_facture_source = $this->invoice->id;

			$error = 0;

			if ($this->invoice->type == Facture::TYPE_STANDARD || $this->invoice->type == Facture::TYPE_REPLACEMENT || $this->invoice->type == Facture::TYPE_SITUATION) {
				// If we're on a standard invoice, we have to get excess received to create a discount in TTC without VAT

				// Total payments
				$sql = 'SELECT SUM(pf.amount) as total_payments';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'paiement_facture as pf, '.MAIN_DB_PREFIX.'paiement as p';
				$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
				$sql .= ' WHERE pf.fk_facture = '.((int) $this->invoice->id);
				$sql .= ' AND pf.fk_paiement = p.rowid';
				$sql .= ' AND p.entity IN ('.getEntity('invoice').')';
				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_print_error($this->db);
				}

				$res = $this->db->fetch_object($resql);
				$total_payments = $res->total_payments;

				// Total credit note and deposit
				$total_creditnote_and_deposit = 0;
				$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
				$sql .= " re.description, re.fk_facture_source";
				$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re";
				$sql .= " WHERE fk_facture = ".((int) $this->invoice->id);
				$resql = $this->db->query($sql);
				if (!empty($resql)) {
					while ($obj = $this->db->fetch_object($resql)) {
						$total_creditnote_and_deposit += $obj->amount_ttc;
					}
				} else {
					dol_print_error($this->db);
				}

				$discount->amount_ht = $discount->amount_ttc = $total_payments + $total_creditnote_and_deposit - $this->invoice->total_ttc;
				$discount->amount_tva = 0;
				$discount->tva_tx = 0;

				$result = $discount->create(DolibarrApiAccess::$user);
				if ($result < 0) {
					$error++;
				}
			}
			if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE || $this->invoice->type == Facture::TYPE_DEPOSIT) {
				foreach ($amount_ht as $tva_tx => $xxx) {
					$discount->amount_ht = abs($amount_ht[$tva_tx]);
					$discount->amount_tva = abs($amount_tva[$tva_tx]);
					$discount->amount_ttc = abs($amount_ttc[$tva_tx]);
					$discount->multicurrency_amount_ht = abs($multicurrency_amount_ht[$tva_tx]);
					$discount->multicurrency_amount_tva = abs($multicurrency_amount_tva[$tva_tx]);
					$discount->multicurrency_amount_ttc = abs($multicurrency_amount_ttc[$tva_tx]);
					$discount->tva_tx = abs($tva_tx);

					$result = $discount->create(DolibarrApiAccess::$user);
					if ($result < 0) {
						$error++;
						break;
					}
				}
			}

			if (empty($error)) {
				if ($this->invoice->type != Facture::TYPE_DEPOSIT) {
					// Set the invoice as paid
					$result = $this->invoice->setPaid(DolibarrApiAccess::$user);
					if ($result >= 0) {
						$this->db->commit();
					} else {
						$this->db->rollback();
						throw new RestException(500, 'Could not set paid');
					}
				} else {
					$this->db->commit();
				}
			} else {
				$this->db->rollback();
				throw new RestException(500, 'Discount creation error');
			}
		}

		return $this->_cleanObjectDatas($this->invoice);
	}

	/**
	 * Add a discount line into an invoice (as an invoice line) using an existing absolute discount
	 *
	 * Note that this consume the discount.
	 *
	 * @param int   $id             Id of invoice
	 * @param int   $discountid     Id of discount
	 * @return int
	 *
	 * @url     POST {id}/usediscount/{discountid}
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function useDiscount($id, $discountid)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}
		if (empty($discountid)) {
			throw new RestException(400, 'Discount ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		$result = $this->invoice->insert_discount($discountid);
		if ($result < 0) {
			throw new RestException(405, $this->invoice->error);
		}

		return $result;
	}

	/**
	 * Add an available credit note discount to payments of an existing invoice.
	 *
	 *  Note that this consume the credit note.
	 *
	 * @param int   $id            Id of invoice
	 * @param int   $discountid    Id of a discount coming from a credit note
	 * @return	int
	 *
	 * @url     POST {id}/usecreditnote/{discountid}
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function useCreditNote($id, $discountid)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}
		if (empty($discountid)) {
			throw new RestException(400, 'Credit ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$discount = new DiscountAbsolute($this->db);
		$result = $discount->fetch($discountid);
		if (!$result) {
			throw new RestException(404, 'Credit not found');
		}

		$result = $discount->link_to_invoice(0, $id);
		if ($result < 0) {
			throw new RestException(405, $discount->error);
		}

		return $result;
	}

	/**
	 * Get list of payments of a given invoice
	 *
	 * @param	int   $id             Id of invoice
	 * @return	array
	 *
	 * @url     GET {id}/payments
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 * @throws RestException 405
	 */
	public function getPayments($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
	 * Add payment line to a specific invoice with the remain to pay as amount.
	 *
	 * @param int     $id                               Id of invoice
	 * @param string  $datepaye           {@from body}  Payment date        {@type timestamp}
	 * @param int     $paymentid          {@from body}  Payment mode Id {@min 1}
	 * @param string  $closepaidinvoices  {@from body}  Close paid invoices {@choice yes,no}
	 * @param int     $accountid          {@from body}  Account Id {@min 1}
	 * @param string  $num_payment        {@from body}  Payment number (optional)
	 * @param string  $comment            {@from body}  Note private (optional)
	 * @param string  $chqemetteur        {@from body}  Payment issuer (mandatory if paymentcode = 'CHQ')
	 * @param string  $chqbank            {@from body}  Issuer bank name (optional)
	 *
	 * @url     POST {id}/payments
	 *
	 * @return int  Payment ID
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function addPayment($id, $datepaye, $paymentid, $closepaidinvoices, $accountid, $num_payment = '', $comment = '', $chqemetteur = '', $chqbank = '')
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Invoice ID is mandatory');
		}

		if (!DolibarrApi::_checkAccessToResource('facture', $id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (isModEnabled("bank")) {
			if (empty($accountid)) {
				throw new RestException(400, 'Account ID is mandatory');
			}
		}

		if (empty($paymentid)) {
			throw new RestException(400, 'Payment ID or Payment Code is mandatory');
		}


		$result = $this->invoice->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Invoice not found');
		}

		// Calculate amount to pay
		$totalpaid = $this->invoice->getSommePaiement();
		$totalcreditnotes = $this->invoice->getSumCreditNotesUsed();
		$totaldeposits = $this->invoice->getSumDepositsUsed();
		$resteapayer = price2num($this->invoice->total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');

		$this->db->begin();

		$amounts = array();
		$multicurrency_amounts = array();

		// Clean parameters amount if payment is for a credit note
		if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
			$resteapayer = price2num($resteapayer, 'MT');
			$amounts[$id] = (float) price2num(-1 * (float) $resteapayer, 'MT');
			// Multicurrency
			$newvalue = price2num($this->invoice->multicurrency_total_ttc, 'MT');
			$multicurrency_amounts[$id] = (float) price2num(-1 * (float) $newvalue, 'MT');
		} else {
			$resteapayer = price2num($resteapayer, 'MT');
			$amounts[$id] = (float) $resteapayer;
			// Multicurrency
			$newvalue = price2num($this->invoice->multicurrency_total_ttc, 'MT');
			$multicurrency_amounts[$id] = (float) $newvalue;
		}

		// Creation of payment line
		$paymentobj = new Paiement($this->db);
		$paymentobj->datepaye     = dol_stringtotime($datepaye);
		$paymentobj->amounts      = $amounts; // Array with all payments dispatching with invoice id
		$paymentobj->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
		$paymentobj->paiementid = $paymentid;
		$paymentobj->paiementcode = (string) dol_getIdFromCode($this->db, $paymentid, 'c_paiement', 'id', 'code', 1);
		$paymentobj->num_payment = $num_payment;
		$paymentobj->note_private = $comment;

		$payment_id = $paymentobj->create(DolibarrApiAccess::$user, ($closepaidinvoices == 'yes' ? 1 : 0)); // This include closing invoices
		if ($payment_id < 0) {
			$this->db->rollback();
			throw new RestException(400, 'Payment error : '.$paymentobj->error);
		}

		if (isModEnabled("bank")) {
			$label = '(CustomerInvoicePayment)';

			if ($paymentobj->paiementcode == 'CHQ' && empty($chqemetteur)) {
				throw new RestException(400, 'Emetteur is mandatory when payment code is '.$paymentobj->paiementcode);
			}
			if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
				$label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
			}
			$result = $paymentobj->addPaymentToBank(DolibarrApiAccess::$user, 'payment', $label, $accountid, $chqemetteur, $chqbank);
			if ($result < 0) {
				$this->db->rollback();
				throw new RestException(400, 'Add payment to bank error : '.$paymentobj->error);
			}
		}

		$this->db->commit();

		return $payment_id;
	}

	/**
	 * Add a payment to pay partially or completely one or several invoices.
	 * Warning: Take care that all invoices are owned by the same customer.
	 * Example of value for parameter arrayofamounts: {"1": {"amount": "99.99", "multicurrency_amount": ""}, "2": {"amount": "", "multicurrency_amount": "10"}}
	 *
	 * @param array   $arrayofamounts     {@from body}  Array with id of invoices with amount to pay for each invoice
	 * @param string  $datepaye           {@from body}  Payment date        {@type timestamp}
	 * @param int     $paymentid           {@from body}  Payment mode Id {@min 1}
	 * @param string  $closepaidinvoices   {@from body}  Close paid invoices {@choice yes,no}
	 * @param int     $accountid           {@from body}  Account Id {@min 1}
	 * @param string  $num_payment         {@from body}  Payment number (optional)
	 * @param string  $comment             {@from body}  Note private (optional)
	 * @param string  $chqemetteur         {@from body}  Payment issuer (mandatory if paiementcode = 'CHQ')
	 * @param string  $chqbank             {@from body}  Issuer bank name (optional)
	 * @param string  $ref_ext             {@from body}  External reference (optional)
	 * @param bool    $accepthigherpayment {@from body}  Accept higher payments that it remains to be paid (optional)
	 *
	 * @url     POST /paymentsdistributed
	 *
	 * @return int  Payment ID
	 *
	 * @throws RestException 400
	 * @throws RestException 401
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function addPaymentDistributed($arrayofamounts, $datepaye, $paymentid, $closepaidinvoices, $accountid, $num_payment = '', $comment = '', $chqemetteur = '', $chqbank = '', $ref_ext = '', $accepthigherpayment = false)
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		foreach ($arrayofamounts as $id => $amount) {
			if (empty($id)) {
				throw new RestException(400, 'Invoice ID is mandatory. Fill the invoice id and amount into arrayofamounts parameter. For example: {"1": "99.99", "2": "10"}');
			}
			if (!DolibarrApi::_checkAccessToResource('facture', $id)) {
				throw new RestException(403, 'Access not allowed on invoice ID '.$id.' for login '.DolibarrApiAccess::$user->login);
			}
		}

		if (isModEnabled("bank")) {
			if (empty($accountid)) {
				throw new RestException(400, 'Account ID is mandatory');
			}
		}
		if (empty($paymentid)) {
			throw new RestException(400, 'Payment ID or Payment Code is mandatory');
		}

		$this->db->begin();

		$amounts = array();
		$multicurrency_amounts = array();

		// Loop on each invoice to pay
		foreach ($arrayofamounts as $id => $amountarray) {
			$result = $this->invoice->fetch($id);
			if (!$result) {
				$this->db->rollback();
				throw new RestException(404, 'Invoice ID '.$id.' not found');
			}

			if (($amountarray["amount"] == "remain" || $amountarray["amount"] > 0) && ($amountarray["multicurrency_amount"] == "remain" || $amountarray["multicurrency_amount"] > 0)) {
				$this->db->rollback();
				throw new RestException(400, 'Payment in both currency '.$id.' ( amount: '.$amountarray["amount"].', multicurrency_amount: '.$amountarray["multicurrency_amount"].')');
			}

			$is_multicurrency = 0;
			$total_ttc = $this->invoice->total_ttc;

			if ($amountarray["multicurrency_amount"] > 0 || $amountarray["multicurrency_amount"] == "remain") {
				$is_multicurrency = 1;
				$total_ttc = $this->invoice->multicurrency_total_ttc;
			}

			// Calculate amount to pay
			$totalpaid = $this->invoice->getSommePaiement($is_multicurrency);
			$totalcreditnotes = $this->invoice->getSumCreditNotesUsed($is_multicurrency);
			$totaldeposits = $this->invoice->getSumDepositsUsed($is_multicurrency);
			$remainstopay = $amount = price2num($total_ttc - $totalpaid - $totalcreditnotes - $totaldeposits, 'MT');

			if (!$is_multicurrency && $amountarray["amount"] != 'remain') {
				$amount = price2num($amountarray["amount"], 'MT');
			}

			if ($is_multicurrency && $amountarray["multicurrency_amount"] != 'remain') {
				$amount = price2num($amountarray["multicurrency_amount"], 'MT');
			}

			if ($amount > $remainstopay && !$accepthigherpayment) {
				$this->db->rollback();
				throw new RestException(400, 'Payment amount on invoice ID '.$id.' ('.$amount.') is higher than remain to pay ('.$remainstopay.')');
			}

			if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
				$amount = price2num(-1 * (float) $amount, 'MT');
			}

			if ($is_multicurrency) {
				$amounts[$id] = null;
				// Multicurrency
				$multicurrency_amounts[$id] = (float) $amount;
			} else {
				$amounts[$id] = (float) $amount;
				// Multicurrency
				$multicurrency_amounts[$id] = null;
			}
		}

		// Creation of payment line
		$paymentobj = new Paiement($this->db);
		$paymentobj->datepaye     = $datepaye;
		$paymentobj->amounts      = $amounts; // Array with all payments dispatching with invoice id
		$paymentobj->multicurrency_amounts = $multicurrency_amounts; // Array with all payments dispatching
		$paymentobj->paiementid   = $paymentid;
		$paymentobj->paiementcode = (string) dol_getIdFromCode($this->db, $paymentid, 'c_paiement', 'id', 'code', 1);
		$paymentobj->num_payment  = $num_payment;
		$paymentobj->note_private = $comment;
		$paymentobj->ref_ext      = $ref_ext;
		$payment_id = $paymentobj->create(DolibarrApiAccess::$user, ($closepaidinvoices == 'yes' ? 1 : 0)); // This include closing invoices
		if ($payment_id < 0) {
			$this->db->rollback();
			throw new RestException(400, 'Payment error : '.$paymentobj->error);
		}
		if (isModEnabled("bank")) {
			$label = '(CustomerInvoicePayment)';
			if ($paymentobj->paiementcode == 'CHQ' && empty($chqemetteur)) {
				throw new RestException(400, 'Emetteur is mandatory when payment code is '.$paymentobj->paiementcode);
			}
			if ($this->invoice->type == Facture::TYPE_CREDIT_NOTE) {
				$label = '(CustomerInvoicePaymentBack)'; // Refund of a credit note
			}
			$result = $paymentobj->addPaymentToBank(DolibarrApiAccess::$user, 'payment', $label, $accountid, $chqemetteur, $chqbank);
			if ($result < 0) {
				$this->db->rollback();
				throw new RestException(400, 'Add payment to bank error : '.$paymentobj->error);
			}
		}

		$this->db->commit();

		return $payment_id;
	}

	/**
	 * Update a payment
	 *
	 * @param int       $id             Id of payment
	 * @param string    $num_payment    Payment number
	 *
	 * @url     PUT payments/{id}
	 *
	 * @return array
	 *
	 * @throws RestException 400 Bad parameters
	 * @throws RestException 401 Not allowed
	 * @throws RestException 404 Not found
	 */
	public function putPayment($id, $num_payment = '')
	{
		require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';

		if (!DolibarrApiAccess::$user->hasRight('facture', 'creer')) {
			throw new RestException(403);
		}
		if (empty($id)) {
			throw new RestException(400, 'Payment ID is mandatory');
		}

		$paymentobj = new Paiement($this->db);
		$result = $paymentobj->fetch($id);

		if (!$result) {
			throw new RestException(404, 'Payment not found');
		}

		if (!empty($num_payment)) {
			$result = $paymentobj->update_num($num_payment);
			if ($result < 0) {
				throw new RestException(500, 'Error when updating the payment num');
			}
		}

		return [
			'success' => [
				'code' => 200,
				'message' => 'Payment updated'
			]
		];
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

		unset($object->note);
		unset($object->address);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->canvas);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array|null    $data       Datas to validate
	 * @return	array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$invoice = array();
		foreach (Invoices::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$invoice[$field] = $data[$field];
		}
		return $invoice;
	}


	/**
	 * Get properties of a template invoice object
	 *
	 * Return an array with invoice information
	 *
	 * @param	int		$id				ID of template invoice
	 * @param   int     $contact_list	0:Return array contains all properties, 1:Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object					Object with cleaned properties
	 *
	 * @url GET    templates/{id}
	 *
	 * @throws	RestException
	 */
	public function getTemplateInvoice($id, $contact_list = 1)
	{
		return $this->_fetchTemplateInvoice($id, '', '', $contact_list);
	}

	/**
	 * Get properties of an invoice object
	 *
	 * Return an array with invoice information
	 *
	 * @param   int         $id				ID of order
	 * @param	string		$ref			Ref of object
	 * @param	string		$ref_ext		External reference of object
	 * @param   int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id, -1: Do not return contacts/adddesses
	 * @return	Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetchTemplateInvoice($id, $ref = '', $ref_ext = '', $contact_list = 1)
	{
		if (!DolibarrApiAccess::$user->hasRight('facture', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->template_invoice->fetch($id, $ref, $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Template invoice not found');
		}

		if (!DolibarrApi::_checkAccessToResource('facturerec', $this->template_invoice->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// Add external contacts ids
		if ($contact_list > -1) {
			$tmparray = $this->template_invoice->liste_contact(-1, 'external', $contact_list);
			if (is_array($tmparray)) {
				$this->template_invoice->contacts_ids = $tmparray;
			}
		}

		$this->template_invoice->fetchObjectLinked();
		return $this->_cleanTemplateObjectDatas($this->template_invoice);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	protected function _cleanTemplateObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->note);
		unset($object->address);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->canvas);

		return $object;
	}
}
