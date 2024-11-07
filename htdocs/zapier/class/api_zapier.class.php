<?php
/* Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2019-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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

/**
 * \file    htdocs/zapier/class/api_zapier.class.php
 * \ingroup zapier
 * \brief   File for API management of Zapier hooks.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/zapier/class/hook.class.php';


/**
 * API class for zapier hook
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Zapier extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'url',
	);


	/**
	 * @var Hook $hook {@type Hook}
	 */
	public $hook;

	/**
	 * Constructor
	 *
	 * @url     GET /
	 *
	 */
	public function __construct()
	{
		global $db;

		$this->db = $db;
		$this->hook = new Hook($this->db);
	}

	/**
	 * Get properties of a hook object
	 *
	 * Return an array with hook information
	 *
	 * @param   int             $id		ID of hook
	 * @return  Object					Object with cleaned properties
	 *
	 * @url GET /hooks/{id}
	 * @throws  RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('zapier', 'read')) {
			throw new RestException(403);
		}

		$result = $this->hook->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Hook not found');
		}

		if (!DolibarrApi::_checkAccessToResource('hook', $this->hook->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($this->hook);
	}

	/**
	 * Get list of possibles choices for module
	 *
	 * Return an array with hook information
	 *
	 * @return  array     data
	 *
	 * @url GET /getmoduleschoices/
	 * @throws  RestException
	 */
	public function getModulesChoices()
	{
		if (!DolibarrApiAccess::$user->hasRight('zapier', 'read')) {
			throw new RestException(403);
		}

		$arraychoices = array(
			'invoices' => 'Invoices',
			'orders' => 'Orders',
			'thirdparties' => 'Thirparties',
			'contacts' => 'Contacts',
			'users' => 'Users',
		);
		// $result = $this->hook->fetch($id);
		// if (! $result ) {
		//     throw new RestException(404, 'Hook not found');
		// }

		// if (! DolibarrApi::_checkAccessToResource('hook', $this->hook->id)) {
		//     throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		// }

		return $arraychoices;
	}

	/**
	 * List hooks
	 *
	 * Get a list of hooks
	 *
	 * @param string           $sortfield           Sort field
	 * @param string           $sortorder           Sort order
	 * @param int              $limit               Limit for list
	 * @param int              $page                Page number
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException
	 *
	 * @url GET /hooks/
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('zapier', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : 0;

		// Set to 1 if there is a field socid in table of object
		$restrictonsocid = 0;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if ($restrictonsocid && !DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."hook_mytable as t";
		$sql .= " WHERE 1 = 1";
		$tmpobject = new Hook($this->db);
		if ($tmpobject->ismultientitymanaged) {
			$sql .= ' AND t.entity IN ('.getEntity('hook').')';
		}
		if ($restrictonsocid && $socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
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
		$i = 0;
		if ($result) {
			$num = $this->db->num_rows($result);
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$hook_static = new Hook($this->db);
				if ($hook_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($hook_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve hook list');
		}

		return $obj_ret;
	}

	/**
	 * Create hook object
	 *
	 * @param array $request_data   Request datas
	 * @return array  ID of hook
	 *
	 * @url	POST /hook/
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('zapier', 'write')) {
			throw new RestException(403);
		}

		dol_syslog("API Zapier create hook receive : ".print_r($request_data, true), LOG_DEBUG);

		// Check mandatory fields
		$fields = array(
			'url',
		);
		$result = $this->validate($request_data, $fields);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->hook->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->hook->$field = $this->_checkValForAPI($field, $value, $this->hook);
		}

		$this->hook->fk_user = DolibarrApiAccess::$user->id;
		// we create the hook into database
		if (!$this->hook->create(DolibarrApiAccess::$user)) {
			throw new RestException(500, "Error creating Hook", array_merge(array($this->hook->error), $this->hook->errors));
		}
		return array(
			'id' => $this->hook->id,
		);
	}

	/**
	 * Delete hook
	 *
	 * @param   int     $id   Hook ID
	 * @return  array
	 *
	 * @url DELETE /hook/{id}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('zapier', 'delete')) {
			throw new RestException(403);
		}

		$result = $this->hook->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Hook not found');
		}

		if (!DolibarrApi::_checkAccessToResource('hook', $this->hook->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->hook->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting Hook : '.$this->hook->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Hook deleted'
			)
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 */
	public function _cleanObjectDatas($object)
	{
		// phpcs:disable
		$object = parent::_cleanObjectDatas($object);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param   array<string,mixed>	$data       Array of data to validate
	 * @param   string[]			$fields     Array of fields needed
	 * @return  array<string,mixed>
	 *
	 * @throws  RestException
	 */
	private function validate($data, $fields)
	{
		$hook = array();
		foreach ($fields as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$hook[$field] = $data[$field];
		}
		return $hook;
	}
}
