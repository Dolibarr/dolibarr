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

require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';

/**
 * API class for paymentexpensereport
 *
 * @property DoliDB db
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class PaymentExpenseReports extends DolibarrApi
{
	/**
	 * array $FIELDS Mandatory fields, checked when creating an object
	 */
	static $FIELDS = array(
		"fk_expensereport",
		"fk_typepayment",
		'datepaid',
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
	 * Get the list of paymentexpensereport.
	 *
	 * @param string    $sortfield  Sort field
	 * @param string    $sortorder  Sort order
	 * @param int       $limit      Limit for list
	 * @param int       $page       Page number
	 * @return array                List of paymentExpenseReport objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0)
	{
		$list = array();

		if (!DolibarrApiAccess::$user->rights->banque->lire) {
			throw new RestException(401);
		}

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "payment_expensereport as t";
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
	 * Get paymentExpenseReport by ID.
	 *
	 * @param int    $id    ID of paymentExpenseReport
	 * @return array PaymentExpenseReport object
	 *
	 * @throws RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->banque->lire) {
			throw new RestException(401);
		}

		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		$result = $paymentExpenseReport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'paymentExpenseReport not found');
		}

		return $this->_cleanObjectDatas($paymentExpenseReport);
	}

	/**
	 * Create paymentExpenseReport object
	 *
	 * @param array $request_data    Request data
	 * @return int ID of paymentExpenseReport
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->banque->configurer) {
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		foreach ($request_data as $field => $value) {
			$paymentExpenseReport->$field = $this->_checkValForAPI($field, $value, $paymentExpenseReport);
		}

		if ($paymentExpenseReport->create(DolibarrApiAccess::$user, 1) < 0) {
			throw new RestException(500, 'Error creating paymentExpenseReport', array_merge(array($paymentExpenseReport->error), $paymentExpenseReport->errors));
		}
		if (isModEnabled("banque")) $paymentExpenseReport->addPaymentToBank(
			DolibarrApiAccess::$user,
			'payment_expensereport',
			'(ExpenseReportPayment)',
			(int) $request_data['accountid'],
			'',
			''
		);
		return $paymentExpenseReport->id;
	}

	/**
	 * Update paymentExpenseReport
	 *
	 * @param int    $id              ID of paymentExpenseReport
	 * @param array  $request_data    data
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		/** @todo ->rights->paymentexpensereport ? */
		if (!DolibarrApiAccess::$user->rights->banque->creer) {
			throw new RestException(401);
		}

		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		$result = $paymentExpenseReport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'paymentExpenseReport not found');
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
	 * @param int    $id    ID of paymentExpenseReport
	 * @return array
	 */
	/*public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->banque->configurer) {
			throw new RestException(401);
		}
		$paymentExpenseReport = new PaymentExpenseReport($this->db);
		$result = $paymentExpenseReport->fetch($id);
		if (!$result) {
			throw new RestException(404, 'paymentExpenseReport not found');
		}

		if ($paymentExpenseReport->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(401, 'error when deleting paymentExpenseReport');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'paymentExpenseReport deleted'
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
		$paymentExpenseReport = array();
		$fields = PaymentExpenseReports::$FIELDS;
		if (isModEnabled("banque")) array_push($fields, "accountid");
		foreach ($fields as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$paymentExpenseReport[$field] = $data[$field];
		}
		return $paymentExpenseReport;
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
