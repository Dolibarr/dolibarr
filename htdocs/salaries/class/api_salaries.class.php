<?php
/*
 * Copyright (C) 2023 Marc Chenebaux <marc.chenebaux@maj44.com>
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

require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';


/**
 * API class for salaries
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class Salaries extends DolibarrApi
{
	/**
	 * @var array $FIELDS Mandatory fields, checked when creating an object
	 */
	static $FIELDS = array(
		'fk_user',
		'label',
		'amount',
	);

	/**
	 * array $FIELDS Mandatory fields, checked when creating an object
	 */
	static $FIELDSPAYMENT = array(
		"paiementtype",
		'datepaye',
		'chid',
		'amounts',
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
	 * Get the list of salaries.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Limit for list
	 * @param int       $page       Page number
	 * @return array                List of salary objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('salaries', 'read')) {
			throw new RestException(403);
		}

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "salary as t";
		//$sql .= ' WHERE t.entity IN ('.getEntity('bank_account').')';

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
				$salary = new Salary($this->db);
				if ($salary->fetch($obj->rowid) > 0) {
					$list[] = $this->_cleanObjectDatas($salary);
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of salaries: ' . $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get salary by ID.
	 *
	 * @param 	int    $id    	ID of salary
	 * @return 	Object			Salary object
	 *
	 * @throws RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'read')) {
			throw new RestException(403);
		}

		$salary = new Salary($this->db);
		$result = $salary->fetch($id);
		if (!$result) {
			throw new RestException(404, 'salary not found');
		}

		return $this->_cleanObjectDatas($salary);
	}

	/**
	 * Create salary object
	 *
	 * @param 	array $request_data    	Request data
	 * @return 	int 					ID of salary
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'write')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		$salary = new Salary($this->db);
		foreach ($request_data as $field => $value) {
			$salary->$field = $this->_checkValForAPI($field, $value, $salary);
		}

		if ($salary->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'Error creating salary', array_merge(array($salary->error), $salary->errors));
		}
		return $salary->id;
	}

	/**
	 * Update salary
	 *
	 * @param 	int    	$id              	ID of salary
	 * @param 	array  	$request_data    	Data
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'write')) {
			throw new RestException(403);
		}

		$salary = new Salary($this->db);
		$result = $salary->fetch($id);
		if (!$result) {
			throw new RestException(404, 'salary not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$salary->$field = $this->_checkValForAPI($field, $value, $salary);
		}

		if ($salary->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $salary->error);
		}
	}

	/**
	 * Delete salary
	 *
	 * @param int    $id    ID of salary
	 * @return array
	 */
	/*public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'delete')) {
			throw new RestException(403);
		}
		$salary = new Salary($this->db);
		$result = $salary->fetch($id);
		if (!$result) {
			throw new RestException(404, 'salary not found');
		}

		if ($salary->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, 'error when deleting salary');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'salary deleted'
			)
		);
	}*/


	/**
	 * Get the list of payment of salaries.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Limit for list
	 * @param int       $page       Page number
	 * @return array                List of paymentsalary objects
	 *
	 * @url     GET /payments
	 *
	 * @throws RestException
	 */
	public function getAllPayments($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		$list = array();

		if (!DolibarrApiAccess::$user->hasRight('salaries', 'read')) {
			throw new RestException(403);
		}

		$sql = "SELECT t.rowid FROM " . MAIN_DB_PREFIX . "payment_salary as t, ".MAIN_DB_PREFIX."salary as s";
		$sql .= ' WHERE s.rowid = t.fk_salary AND t.entity IN ('.getEntity('salary').')';

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
				$paymentsalary = new PaymentSalary($this->db);
				if ($paymentsalary->fetch($obj->rowid) > 0) {
					$list[] = $this->_cleanObjectDatas($paymentsalary);
				}
			}
		} else {
			throw new RestException(503, 'Error when retrieving list of paymentsalaries: ' . $this->db->lasterror());
		}

		return $list;
	}

	/**
	 * Get a given payment.
	 *
	 * @param 	int    $pid    	ID of payment salary
	 * @return 	Object 			PaymentSalary object
	 *
	 * @url     GET /payments/{pid}
	 *
	 * @throws RestException
	 */
	public function getPayments($pid)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'read')) {
			throw new RestException(403);
		}

		$paymentsalary = new PaymentSalary($this->db);
		$result = $paymentsalary->fetch($pid);
		if (!$result) {
			throw new RestException(404, 'paymentsalary not found');
		}

		return $this->_cleanObjectDatas($paymentsalary);
	}

	/**
	 * Create payment salary on a salary
	 *
	 * @param 	int		$id					Id of salary
	 * @param 	array 	$request_data    	Request data
	 * @return 	int 						ID of paymentsalary
	 *
	 * @url     POST {id}/payments
	 *
	 * @throws RestException
	 */
	public function addPayment($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'write')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validatepayments($request_data);

		$paymentsalary = new PaymentSalary($this->db);
		$paymentsalary->fk_salary = $id;
		foreach ($request_data as $field => $value) {
			$paymentsalary->$field = $this->_checkValForAPI($field, $value, $paymentsalary);
		}

		if ($paymentsalary->create(DolibarrApiAccess::$user, 1) < 0) {
			throw new RestException(500, 'Error creating paymentsalary', array_merge(array($paymentsalary->error), $paymentsalary->errors));
		}
		if (isModEnabled("bank")) {
			$paymentsalary->addPaymentToBank(
				DolibarrApiAccess::$user,
				'payment_salary',
				'(SalaryPayment)',
				(int) $request_data['accountid'],
				'',
				''
			);
		}
		return $paymentsalary->id;
	}

	/**
	 * Update paymentsalary
	 *
	 * @param 	int    $id              ID of paymentsalary
	 * @param 	array  $request_data    data
	 * @return 	Object					PaymentSalary object
	 *
	 * @url     POST {id}/payments
	 *
	 * @throws RestException
	 */
	public function updatePayment($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('salaries', 'write')) {
			throw new RestException(403);
		}

		$paymentsalary = new PaymentSalary($this->db);
		$result = $paymentsalary->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Payment salary not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			$paymentsalary->$field = $this->_checkValForAPI($field, $value, $paymentsalary);
		}

		if ($paymentsalary->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $paymentsalary->error);
		}
	}

	/**
	 * Delete a payment salary
	 *
	 * @param int    $id    ID of payment salary
	 * @return array
	 *
	 * @url     DELETE {id}/payments
	 */
	/*public function delete($id)
	 {
	 if (!DolibarrApiAccess::$user->hasRight('salaries', 'delete')) {
	 throw new RestException(403);
	 }
	 $paymentsalary = new PaymentSalary($this->db);
	 $result = $paymentsalary->fetch($id);
	 if (!$result) {
	 throw new RestException(404, 'paymentsalary not found');
	 }

	 if ($paymentsalary->delete(DolibarrApiAccess::$user) < 0) {
	 throw new RestException(500, 'error when deleting paymentsalary');
	 }

	 return array(
	 'success' => array(
	 'code' => 200,
	 'message' => 'paymentsalary deleted'
	 )
	 );
	 }*/


	/**
	 * Validate fields before creating an object
	 *
	 * @param array|null    $data    Data to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$salary = array();
		foreach (Salaries::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$salary[$field] = $data[$field];
		}
		return $salary;
	}

	/**
	 * Validate fields before creating an object
	 *
	 * @param array|null    $data    Data to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validatepayments($data)
	{
		$paymentsalary = array();
		$fields = Salaries::$FIELDSPAYMENT;
		if (isModEnabled("bank")) array_push($fields, "accountid");
		foreach ($fields as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$paymentsalary[$field] = $data[$field];
		}
		return $paymentsalary;
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

		return $object;
	}
}
