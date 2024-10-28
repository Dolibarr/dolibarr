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

require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';


/**
 * API class for supplier proposal
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SupplierProposals extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid'
	);

	/**
	 * @var SupplierProposal $supplier_proposal {@type SupplierProposal}
	 */
	public $supplier_proposal;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->supplier_proposal = new SupplierProposal($this->db);
	}

	/**
	 * Delete commercial proposal
	 *
	 * @param   int     $id         Supplier proposal ID
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('supplier_proposal', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->supplier_proposal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('supplier_proposal', $this->supplier_proposal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->supplier_proposal->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Supplier Proposal : '.$this->supplier_proposal->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Supplier Proposal deleted'
			)
		);
	}

	/**
	 * Get properties of a supplier proposal (price request) object
	 *
	 * Return an array with supplier proposal information
	 *
	 * @param       int         $id         ID of supplier proposal
	 * @return		Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('supplier_proposal', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->supplier_proposal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier Proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('supplier_proposal', $this->supplier_proposal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->supplier_proposal->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->supplier_proposal);
	}

	/**
	 * Create supplier proposal (price request) object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of supplier proposal
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('supplier_proposal', 'creer')) {
			throw new RestException(403, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->supplier_proposal->context['caller'] = $request_data['caller'];
				continue;
			}

			$this->supplier_proposal->$field = $value;
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->propal->lines = $lines;
		}*/
		if ($this->supplier_proposal->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating supplier proposal", array_merge(array($this->supplier_proposal->error), $this->supplier_proposal->errors));
		}

		return $this->supplier_proposal->id;
	}

	/**
	 * Update supplier proposal general fields (won't touch lines of supplier proposal)
	 *
	 * @param	int		$id             Id of supplier proposal to update
	 * @param	array	$request_data   Datas
	 * @return	Object					Object with cleaned properties
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('supplier_proposal', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->supplier_proposal->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Supplier proposal not found');
		}

		if (!DolibarrApi::_checkAccessToResource('supplier_proposal', $this->supplier_proposal->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->supplier_proposal->context['caller'] = $request_data['caller'];
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->supplier_proposal->array_options[$index] = $val;
				}
				continue;
			}
			$this->supplier_proposal->$field = $value;
		}

		// update end of validity date
		if (empty($this->supplier_proposal->fin_validite) && !empty($this->supplier_proposal->duree_validite) && !empty($this->supplier_proposal->date_creation)) {
			$this->supplier_proposal->fin_validite = $this->supplier_proposal->date_creation + ($this->supplier_proposal->duree_validite * 24 * 3600);
		}
		if (!empty($this->supplier_proposal->fin_validite)) {
			if ($this->supplier_proposal->set_echeance(DolibarrApiAccess::$user, $this->supplier_proposal->fin_validite) < 0) {
				throw new RestException(500, $this->supplier_proposal->error);
			}
		}

		if ($this->supplier_proposal->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->supplier_proposal->error);
		}
	}

	/**
	 * List supplier proposals
	 *
	 * Get a list of supplier proposals
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string	$thirdparty_ids		Thirdparty ids to filter supplier proposals (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
	 * @param string    $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool      $pagination_data    If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                       Array of order objects
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('supplier_proposal', 'lire')) {
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
		$sql .= " FROM ".MAIN_DB_PREFIX."supplier_proposal AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."supplier_proposal_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('propal').')';
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
		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total supplier proposals with the filters given
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
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$propal_static = new SupplierProposal($this->db);
				if ($propal_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($propal_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieving supplier proposal list : '.$this->db->lasterror());
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
	 * Validate fields before create or update object
	 *
	 * @param   array           $data   Array with data to verify
	 * @return  array
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		$propal = array();
		foreach (SupplierProposals::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$propal[$field] = $data[$field];
		}
		return $propal;
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

		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->address);
		unset($object->datec);
		unset($object->datev);

		return $object;
	}
}
