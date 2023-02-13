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
		global $db, $conf;
		$this->db = $db;
	}

	/**
	 * Get a list of currencies
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.product_id:=:1) and (t.date_creation:<:'20160101')"
	 * @return array                Array of warehouse objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $sqlfilters = '')
	{
		global $db, $conf;

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
					$obj_ret[] = $this->_cleanObjectDatas($multicurrency_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve currencies list : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No currencies found');
		}

		return $obj_ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   MultiCurrency $object     Object to clean
	 * @return  Object                     Object with cleaned properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Clear all fields out of interrest
		foreach ($object as $key => $value) {
			if ($key == "rate") $object->$key = $this->_cleanObjectDatas($object->$key);
			if ($key == "id" || $key == "code" || $key == "rate" || $key == "date_sync")
				continue;
			unset($object->$key);
		}

		return $object;
	}
}
