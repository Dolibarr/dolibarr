<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019 Maxime Kohlhaas <maxime@atm-consulting.fr>
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

require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';


/**
 * \file    mrp/class/api_mo.class.php
 * \ingroup mrp
 * \brief   File for API management of MO.
 */

/**
 * API class for MO
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Mos extends DolibarrApi
{
	/**
	 * @var Mo $mo {@type Mo}
	 */
	public $mo;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->mo = new Mo($this->db);
	}

	/**
	 * Get properties of a MO object
	 *
	 * Return an array with MO informations
	 *
	 * @param 	int 	$id ID of MO
	 * @return 	array|mixed data without useless information
	 *
	 * @url	GET {id}
	 * @throws 	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->read) {
			throw new RestException(401);
		}

		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if (!DolibarrApi::_checkAccessToResource('mrp', $this->mo->id, 'mrp_mo')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->mo);
	}


	/**
	 * List Mos
	 *
	 * Get a list of MOs
	 *
	 * @param string	       $sortfield	        Sort field
	 * @param string	       $sortorder	        Sort order
	 * @param int		       $limit		        Limit for list
	 * @param int		       $page		        Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();
		$tmpobject = new Mo($db);

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : '';

		$restrictonsocid = 0; // Set to 1 if there is a field socid in table of object

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) $search_sale = DolibarrApiAccess::$user->id;

		$sql = "SELECT t.rowid";
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		$sql .= " FROM ".MAIN_DB_PREFIX.$tmpobject->table_element." as t";

		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		$sql .= " WHERE 1 = 1";

		// Example of use $mode
		//if ($mode == 1) $sql.= " AND s.client IN (1, 3)";
		//if ($mode == 2) $sql.= " AND s.client IN (2, 3)";

		if ($tmpobject->ismultientitymanaged) $sql .= ' AND t.entity IN ('.getEntity($tmpobject->element).')';
		if ($restrictonsocid && (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";
		if ($restrictonsocid && $socid) $sql .= " AND t.fk_soc = ".$socid;
		if ($restrictonsocid && $search_sale > 0) $sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		// Insert sale filter
		if ($restrictonsocid && $search_sale > 0)
		{
			$sql .= " AND sc.fk_user = ".$search_sale;
		}
		if ($sqlfilters)
		{
			if (!DolibarrApi::_checkFilters($sqlfilters))
			{
				throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
			}
			$regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
			$sql .= " AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
		}

		$sql .= $db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0)
			{
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $db->plimit($limit + 1, $offset);
		}

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($result);
				$tmp_object = new Mo($db);
				if ($tmp_object->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($tmp_object);
				}
				$i++;
			}
		}
		else {
			throw new RestException(503, 'Error when retrieve MO list');
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No MO found');
		}
		return $obj_ret;
	}

	/**
	 * Create MO object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of MO
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->write) {
			throw new RestException(401);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->mo->$field = $value;
		}
		if (!$this->mo->create(DolibarrApiAccess::$user)) {
			throw new RestException(500, "Error creating MO", array_merge(array($this->mo->error), $this->mo->errors));
		}
		return $this->mo->id;
	}

	/**
	 * Update MO
	 *
	 * @param int   $id             Id of MO to update
	 * @param array $request_data   Datas
	 *
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->write) {
			throw new RestException(401);
		}

		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if (!DolibarrApi::_checkAccessToResource('mrp', $this->mo->id, 'mrp_mo')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') continue;
			$this->mo->$field = $value;
		}

		if ($this->mo->update($id, DolibarrApiAccess::$user) > 0)
		{
			return $this->get($id);
		}
		else
		{
			throw new RestException(500, $this->mo->error);
		}
	}

	/**
	 * Delete MO
	 *
	 * @param   int     $id   MO ID
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->mrp->delete) {
			throw new RestException(401);
		}
		$result = $this->mo->fetch($id);
		if (!$result) {
			throw new RestException(404, 'MO not found');
		}

		if (!DolibarrApi::_checkAccessToResource('mrp', $this->mo->id, 'mrp_mo')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->mo->delete(DolibarrApiAccess::$user))
		{
			throw new RestException(500, 'Error when deleting MO : '.$this->mo->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'MO deleted'
			)
		);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   object  $object     Object to clean
	 * @return  array               Array of cleaned object properties
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->rowid);
		unset($object->canvas);

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->statut);
		unset($object->state);
		unset($object->state_id);
		unset($object->state_code);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_account);
		unset($object->comments);
		unset($object->note);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->shipping_method_id);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);

		// If object has lines, remove $db property
		if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++)
			{
				$this->_cleanObjectDatas($object->lines[$i]);

				unset($object->lines[$i]->lines);
				unset($object->lines[$i]->note);
			}
		}

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param	array		$data   Array of data to validate
	 * @return	array
	 *
	 * @throws	RestException
	 */
	private function _validate($data)
	{
		$myobject = array();
		foreach ($this->mo->fields as $field => $propfield) {
			if (in_array($field, array('rowid', 'entity', 'date_creation', 'tms', 'fk_user_creat')) || $propfield['notnull'] != 1) continue; // Not a mandatory field
			if (!isset($data[$field]))
				throw new RestException(400, "$field field missing");
				$myobject[$field] = $data[$field];
		}
		return $myobject;
	}
}
