<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016   Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2020-2024  Frédéric France		<frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';


/**
 * API class for Expense Reports
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class ExpenseReports extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_user_author'
	);

	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDSPAYMENT = array(
		"fk_typepayment",
		'datepaid',
		'amounts',
	);

	/**
	 * @var ExpenseReport $expensereport {@type ExpenseReport}
	 */
	public $expensereport;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->expensereport = new ExpenseReport($this->db);
	}

	/**
	 * Get properties of an Expense Report
	 *
	 * Return an array with Expense Report information
	 *
	 * @param   int         $id         ID of Expense Report
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->expensereport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Expense report not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expensereport', $this->expensereport->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->expensereport->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->expensereport);
	}

	/**
	 * List Expense Reports
	 *
	 * Get a list of Expense Reports
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string	$user_ids   User ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array               Array of Expense Report objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = '', $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'lire')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		// case of external user, $societe param is ignored and replaced by user's socid
		//$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $societe;

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport AS t LEFT JOIN ".MAIN_DB_PREFIX."expensereport_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('expensereport').')';
		if ($user_ids) {
			$sql .= " AND t.fk_user_author IN (".$this->db->sanitize($user_ids).")";
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
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$expensereport_static = new ExpenseReport($this->db);
				if ($expensereport_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($expensereport_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve Expense Report list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create Expense Report object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int                     ID of Expense Report
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->expensereport->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->expensereport->$field = $this->_checkValForAPI($field, $value, $this->expensereport);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->expensereport->lines = $lines;
		}*/
		if ($this->expensereport->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating expensereport", array_merge(array($this->expensereport->error), $this->expensereport->errors));
		}

		return $this->expensereport->id;
	}

	/**
	 * Get lines of an Expense Report
	 *
	 * @param int   $id             Id of Expense Report
	 *
	 * @url	GET {id}/lines
	 *
	 * @return int
	 */
	/*
	public function getLines($id)
	{
		if(! DolibarrApiAccess::$user->hasRight('expensereport', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->expensereport->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'expensereport not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->expensereport->getLinesArray();
		$result = array();
		foreach ($this->expensereport->lines as $line) {
			array_push($result,$this->_cleanObjectDatas($line));
		}
		return $result;
	}
	*/

	/**
	 * Add a line to given Expense Report
	 *
	 * @param int   $id             Id of Expense Report to update
	 * @param array $request_data   Expense Report data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int
	 */
	/*
	public function postLine($id, $request_data = null)
	{
	  if(! DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			  throw new RestException(403);
		  }

	  $result = $this->expensereport->fetch($id);
	  if( ! $result ) {
		 throw new RestException(404, 'expensereport not found');
	  }

		  if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			  throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
	  }

	  $request_data = (object) $request_data;

	  $request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
	  $request_data->label = sanitizeVal($request_data->label);

	  $updateRes = $this->expensereport->addline(
						$request_data->desc,
						$request_data->subprice,
						$request_data->qty,
						$request_data->tva_tx,
						$request_data->localtax1_tx,
						$request_data->localtax2_tx,
						$request_data->fk_product,
						$request_data->remise_percent,
						$request_data->info_bits,
						$request_data->fk_remise_except,
						'HT',
						0,
						$request_data->date_start,
						$request_data->date_end,
						$request_data->product_type,
						$request_data->rang,
						$request_data->special_code,
						$fk_parent_line,
						$request_data->fk_fournprice,
						$request_data->pa_ht,
						$request_data->label,
						$request_data->array_options,
						$request_data->fk_unit,
						$this->element,
						$request_data->id
	  );

	  if ($updateRes > 0) {
		return $updateRes;

	  }
	  return false;
	}
	*/

	/**
	 * Update a line to given Expense Report
	 *
	 * @param int   $id             Id of Expense Report to update
	 * @param int   $lineid         Id of line to update
	 * @param array $request_data   Expense Report data
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return object
	 */
	/*
	public function putLine($id, $lineid, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			  throw new RestException(403);
		}

		$result = $this->expensereport->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'expensereport not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->expensereport->updateline(
						$lineid,
						$request_data->desc,
						$request_data->subprice,
						$request_data->qty,
						$request_data->remise_percent,
						$request_data->tva_tx,
						$request_data->localtax1_tx,
						$request_data->localtax2_tx,
						'HT',
						$request_data->info_bits,
						$request_data->date_start,
						$request_data->date_end,
						$request_data->product_type,
						$request_data->fk_parent_line,
						0,
						$request_data->fk_fournprice,
						$request_data->pa_ht,
						$request_data->label,
						$request_data->special_code,
						$request_data->array_options,
						$request_data->fk_unit
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}
		return false;
	}
	*/

	/**
	 * Delete a line of given Expense Report
	 *
	 * @param int   $id             Id of Expense Report to update
	 * @param int   $lineid         Id of line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return int
	 */
	/*
	public function deleteLine($id, $lineid)
	{
	  if(! DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			  throw new RestException(403);
		  }

	  $result = $this->expensereport->fetch($id);
	  if( ! $result ) {
		 throw new RestException(404, 'expensereport not found');
	  }

		  if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			  throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
	  }

	  // TODO Check the lineid $lineid is a line of object

	  $updateRes = $this->expensereport->deleteLine($lineid);
	  if ($updateRes == 1) {
		return $this->get($id);
	  }
	  return false;
	}
	*/

	/**
	 * Update Expense Report general fields (won't touch lines of expensereport)
	 *
	 * @param 	int   	$id             	Id of Expense Report to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 *
	 * @throws	RestException	401		Not allowed
	 * @throws  RestException	404		Expense report not found
	 * @throws	RestException	500		System error
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->expensereport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'expensereport not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expensereport', $this->expensereport->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->expensereport->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->expensereport->$field = $this->_checkValForAPI($field, $value, $this->expensereport);
		}

		if ($this->expensereport->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->expensereport->error);
		}
	}

	/**
	 * Delete Expense Report
	 *
	 * @param   int     $id         Expense Report ID
	 *
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'supprimer')) {
			throw new RestException(403);
		}

		$result = $this->expensereport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Expense Report not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expensereport', $this->expensereport->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->expensereport->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Expense Report : '.$this->expensereport->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Expense Report deleted'
			)
		);
	}

	/**
	 * Validate an Expense Report
	 *
	 * @param   int $id             Expense Report ID
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "idwarehouse": 0
	 * }
	 */
	/*
	public function validate($id, $idwarehouse=0)
	{
		if(! DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->expensereport->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'expensereport not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('expensereport',$this->expensereport->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if( ! $this->expensereport->valid(DolibarrApiAccess::$user, $idwarehouse)) {
			throw new RestException(500, 'Error when validate expensereport');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'expensereport validated'
			)
		);
	}*/



	/**
	 * Get the list of payments of expensereport.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Limit for list
	 * @param int       $page       Page number
	 * @return array                List of paymentExpenseReport objects
	 *
	 * @url     GET /payments
	 *
	 * @throws RestException
	 */
	public function getAllPayments($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'lire')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid FROM " . MAIN_DB_PREFIX . "payment_expensereport as t, ".MAIN_DB_PREFIX."expensereport as e";
		$sql .= " WHERE e.rowid = t.fk_expensereport";
		$sql .= ' AND e.entity IN ('.getEntity('expensereport').')';

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

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			for ($i = 0; $i < $min; $i++) {
				$obj = $this->db->fetch_object($result);
				$paymentExpenseReport = new PaymentExpenseReport($this->db);
				if ($paymentExpenseReport->fetch($obj->rowid) > 0) {
					$list[] = $this->_cleanObjectDatas($paymentExpenseReport);
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of paymentexpensereport: ' . $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get a given payment.
	 *
	 * @param	int		$pid	Payment ID
	 * @return 	object 			PaymentExpenseReport object
	 *
	 * @url     GET /payments/{pid}
	 *
	 * @throws RestException
	 */
	public function getPayments($pid)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'lire')) {
			throw new RestException(403);
		}

		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		$result = $paymentExpenseReport->fetch($pid);
		if (!$result) {
			throw new RestException(404, 'paymentExpenseReport not found');
		}

		return $this->_cleanObjectDatas($paymentExpenseReport);
	}

	/**
	 * Create payment of ExpenseReport
	 *
	 * @param 	int 	$id   							ID of expense report
	 * @param 	array 	$request_data   {@from body}  	Request data
	 * @return 	int 									ID of paymentExpenseReport
	 *
	 * @url     POST {id}/payments
	 */
	public function addPayment($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validatepayment($request_data);

		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		$paymentExpenseReport->fk_expensereport = $id;
		foreach ($request_data as $field => $value) {
			$paymentExpenseReport->$field = $this->_checkValForAPI($field, $value, $paymentExpenseReport);
		}

		if ($paymentExpenseReport->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating paymentExpenseReport', array_merge(array($paymentExpenseReport->error), $paymentExpenseReport->errors));
		}
		if (isModEnabled("bank")) {
			$paymentExpenseReport->addPaymentToBank(
				DolibarrApiAccess::$user,
				'payment_expensereport',
				'(ExpenseReportPayment)',
				(int) $request_data['accountid'],
				'',
				''
			);
		}

		return $paymentExpenseReport->id;
	}

	/**
	 * Update a payment of ExpenseReport
	 *
	 * @param   int     $id              ID of paymentExpenseReport
	 * @param   array   $request_data    data
	 * @return  object
	 *
	 * @url     PUT {id}/payments
	 */
	public function updatePayment($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('expensereport', 'creer')) {
			throw new RestException(403);
		}

		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		$result = $paymentExpenseReport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'payment of expense report not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$paymentExpenseReport->$field = $this->_checkValForAPI($field, $value, $paymentExpenseReport);
		}

		if ($paymentExpenseReport->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $paymentExpenseReport->error);
		}
	}

	/**
	 * Delete paymentExpenseReport
	 *
	 * @param 	int    $id    ID of payment ExpenseReport
	 * @return 	array
	 *
	 * @url     DELETE {id}/payments
	 */
	/*public function delete($id)
	 {
	 if (!DolibarrApiAccess::$user->hasRight('expensereport', 'creer') {
	 throw new RestException(403);
	 }
	 $paymentExpenseReport = new PaymentExpenseReport($this->db);
	 $result = $paymentExpenseReport->fetch($id);
	 if (!$result) {
	 throw new RestException(404, 'paymentExpenseReport not found');
	 }

	 if ($paymentExpenseReport->delete(DolibarrApiAccess::$user) < 0) {
	 throw new RestException(403, 'error when deleting paymentExpenseReport');
	 }

	 return array(
	 'success' => array(
	 'code' => 200,
	 'message' => 'paymentExpenseReport deleted'
	 )
	 );
	 }*/



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

		unset($object->fk_statut);
		unset($object->statut);
		unset($object->user);
		unset($object->thirdparty);

		unset($object->cond_reglement);
		unset($object->shipping_method_id);

		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		unset($object->code_paiement);
		unset($object->code_statut);
		unset($object->fk_c_paiement);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->cond_reglement_id);
		unset($object->contact);
		unset($object->contact_id);

		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);

		unset($object->note); // We already use note_public and note_pricate

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		$expensereport = array();
		foreach (ExpenseReports::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$expensereport[$field] = $data[$field];
		}
		return $expensereport;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 * @throws  RestException
	 */
	private function _validatepayment($data)
	{
		$expensereport = array();
		foreach (ExpenseReports::$FIELDSPAYMENT as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$expensereport[$field] = $data[$field];
		}
		return $expensereport;
	}
}
