<?php
/* Copyright (C) 2022   J-F Bouculat     <jfbouculat@gmail.com>
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

//require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/multicurrency.lib.php';

/**
 * API class for MultiCurrency
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class MultiCurrencies extends DolibarrApi
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
	}

	/**
	 * List Currencies
	 *
	 * Get a list of Currencies
	 *
	 * @param string	$sortfield		Sort field
	 * @param string	$sortorder		Sort order
	 * @param int		$limit			Limit for list
	 * @param int	    $page			Page number
	 * @param string    $sqlfilters		Other criteria to filter answers separated by a comma. Syntax example "(t.product_id:=:1) and (t.date_creation:<:'20160101')"
	 * @param string    $properties		Restrict the data returned to theses properties. Ignored if empty. Comma separated list of properties names
	 * @return array					Array of warehouse objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		global $db;

		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->read) {
			throw new RestException(401, "Insufficient rights to read currency");
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".$this->db->prefix()."multicurrency as t";
		$sql .= ' WHERE 1 = 1';
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			if (!DolibarrApi::_checkFilters($sqlfilters, $errormessage)) {
				throw new RestException(503, 'Error when validating parameter sqlfilters -> '.$errormessage);
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
				$multicurrency_static = new MultiCurrency($this->db);
				if ($multicurrency_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($multicurrency_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve currencies list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Get properties of a Currency object
	 *
	 * Return an array with Currency informations
	 *
	 * @param	int			$id		ID of Currency
	 * @return  Object              Object with cleaned properties
	 *
	 * @throws RestException
	 */
	public function get($id)
	{
		$multicurrency = new MultiCurrency($this->db);
		if (!$multicurrency->fetch($id)) {
			throw new RestException(404, 'Currency not found');
		}

		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->read) {
			throw new RestException(401, "Insufficient rights to read currency");
		}

		return $this->_cleanObjectDatas($multicurrency);
	}

	/**
	 * Get properties of a Currency object by code
	 *
	 * Return an array with Currency informations
	 * @url GET /bycode/{code}
	 *
	 * @param	string		$code	Code of Currency (ex: EUR)
	 * @return	array|mixed			Data without useless information
	 *
	 * @throws RestException
	 */
	public function getByCode($code)
	{
		$multicurrency = new MultiCurrency($this->db);
		if (!$multicurrency->fetch('', $code)) {
			throw new RestException(404, 'Currency not found');
		}

		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->read) {
			throw new RestException(401, "Insufficient rights to read currency");
		}

		return $this->_cleanObjectDatas($multicurrency);
	}

	/**
	 * List Currency rates
	 *
	 * Get a list of Currency rates
	 *
	 * @url GET {id}/rates
	 * @param	int		$id		ID of Currency
	 * @return	array|mixed		Data without useless information
	 *
	 * @throws RestException
	 */
	public function getRates($id)
	{
		$multicurrency = new MultiCurrency($this->db);
		if (!$multicurrency->fetch($id)) {
			throw new RestException(404, 'Currency not found');
		}

		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->read) {
			throw new RestException(401, "Insufficient rights to read currency rates");
		}

		if ($multicurrency->fetchAllCurrencyRate() < 0) {
			throw new RestException(500, "Error when fetching currency rates");
		}

		// Clean object datas
		foreach ($multicurrency->rates as $key => $obj) {
			$multicurrency->rates[$key] = $this->_cleanObjectDatasRate($obj);
		}

		return $multicurrency->rates;
	}

	/**
	 * Create Currency object
	 *
	 * @param array $request_data	Request data
	 * @return int					ID of Currency
	 *
	 * @throws RestException
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->write) {
			throw new RestException(401, "Insufficient rights to create currency");
		}

		// Check parameters
		if (!isset($request_data['code'])) {
			throw new RestException(400, "code field missing");
		}
		if (!isset($request_data['name'])) {
			throw new RestException(400, "name field missing");
		}

		$multicurrency = new MultiCurrency($this->db);
		$multicurrency->code = $request_data['code'];
		$multicurrency->name = $request_data['name'];

		// Create Currency
		if ($multicurrency->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating currency", array_merge(array($multicurrency->error), $multicurrency->errors));
		}

		// Add default rate if defined
		if (isset($request_data['rate']) && $request_data['rate'] > 0) {
			if ($multicurrency->addRate($request_data['rate']) < 0) {
				throw new RestException(500, "Error adding currency rate", array_merge(array($multicurrency->error), $multicurrency->errors));
			}

			return $multicurrency->id;
		}

		return $multicurrency->id;
	}

	/**
	 * Update Currency
	 *
	 * @param int   $id             Id of Currency to update
	 * @param array $request_data   Datas
	 * @return array				The updated Currency
	 *
	 * @throws RestException
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->write) {
			throw new RestException(401, "Insufficient rights to update currency");
		}

		$multicurrency = new MultiCurrency($this->db);
		if (!$multicurrency->fetch($id)) {
			throw new RestException(404, 'Currency not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again whith the caller
				$multicurrency->context['caller'] = $request_data['caller'];
				continue;
			}

			$multicurrency->$field = $value;
		}

		if ($multicurrency->update(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error updating currency", array_merge(array($multicurrency->error), $multicurrency->errors));
		}

		return $this->get($id);
	}

	/**
	 * Delete Currency
	 *
	 * @param   int     $id	Currency ID
	 * @return  array
	 *
	 * @throws RestException
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->delete) {
			throw new RestException(401, "Insufficient rights to delete currency");
		}

		$multicurrency = new MultiCurrency($this->db);
		if (!$multicurrency->fetch($id)) {
			throw new RestException(404, 'Currency not found');
		}

		if (!$multicurrency->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, "Error deleting currency", array_merge(array($multicurrency->error), $multicurrency->errors));
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Currency deleted'
			)
		);
	}


	/**
	 * Update Currency rate
	 * @url PUT {id}/rates
	 *
	 * @param	int		$id				Currency ID
	 * @param	array	$request_data	Request data
	 * @return	Object|false			Object with cleaned properties
	 *
	 * @throws RestException
	 */
	public function updateRate($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->multicurrency->currency->write) {
			throw new RestException(401, "Insufficient rights to update currency rate");
		}

		// Check parameters
		if (!isset($request_data['rate'])) {
			throw new RestException(400, "rate field missing");
		}

		$multicurrency = new MultiCurrency($this->db);
		if (!$multicurrency->fetch($id)) {
			throw new RestException(404, 'Currency not found');
		}

		// Add rate
		if ($multicurrency->addRate($request_data['rate']) < 0) {
			throw new RestException(500, "Error updating currency rate", array_merge(array($multicurrency->error), $multicurrency->errors));
		}

		return $this->_cleanObjectDatas($multicurrency);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   MultiCurrency	$object		Object to clean
	 * @return  Object						Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Clear all fields out of interrest
		foreach ($object as $key => $value) {
			if ($key == "rate") {
				$object->$key = $this->_cleanObjectDatasRate($object->$key);
			}
			if ($key == "id" || $key == "code" || $key == "rate" || $key == "name") {
				continue;
			}
			unset($object->$key);
		}

		return $object;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible MultiCurrencyRate object datas
	 *
	 * @param   MultiCurrency	$object     Object to clean
	 * @return  Object						Object with cleaned properties
	 */
	protected function _cleanObjectDatasRate($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Clear all fields out of interrest
		foreach ($object as $key => $value) {
			if ($key == "id" || $key == "rate" || $key == "date_sync") {
				continue;
			}
			unset($object->$key);
		}

		return $object;
	}
}
