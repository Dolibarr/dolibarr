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

require_once DOL_DOCUMENT_ROOT . '/salaries/class/salary.class.php';

/**
 * API class for salaries
 *
 * @property DoliDB db
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class Salaries extends DolibarrApi
{
	/**
	 * array $FIELDS Mandatory fields, checked when creating an object
	 */
	static $FIELDS = array(
		'fk_user',
		'label',
		'amount',
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

		if (!DolibarrApiAccess::$user->rights->banque->lire) {
			throw new RestException(401);
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
	 * @param int    $id    ID of salary
	 * @return array Salary object
	 *
	 * @throws RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->banque->lire) {
			throw new RestException(401);
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
	 * @param array $request_data    Request data
	 * @return int ID of salary
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->banque->configurer) {
			throw new RestException(401);
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
	 * @param int    $id              ID of salary
	 * @param array  $request_data    data
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		/** @todo ->rights->salaries ? */
		if (!DolibarrApiAccess::$user->rights->banque->creer) {
			throw new RestException(401);
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
		if (!DolibarrApiAccess::$user->rights->banque->configurer) {
			throw new RestException(401);
		}
		$salary = new Salary($this->db);
		$result = $salary->fetch($id);
		if (!$result) {
			throw new RestException(404, 'salary not found');
		}

		if ($salary->delete(DolibarrApiAccess::$user) < 0) {
			throw new RestException(401, 'error when deleting salary');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'salary deleted'
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
