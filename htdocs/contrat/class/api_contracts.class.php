<?php
/* Copyright (C) 2015		Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016		Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2018-2020  Frédéric France         <frederic.france@netlogic.fr>
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

 require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

/**
 * API class for contracts
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Contracts extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
		'date_contrat',
		'commercial_signature_id',
		'commercial_suivi_id'
	);

	/**
	 * @var Contrat $contract {@type Contrat}
	 */
	public $contract;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->contract = new Contrat($this->db);
	}

	/**
	 * Get properties of a contract object
	 *
	 * Return an array with contract information
	 *
	 * @param   int         $id         ID of contract
	 * @return  Object					Object with cleaned properties
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->contract->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->contract);
	}

	/**
	 * List contracts
	 *
	 * Get a list of contracts
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string		   $thirdparty_ids		Thirdparty ids to filter contracts of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                               Array of order objects
	 *
	 * @throws RestException 404 Not found
	 * @throws RestException 503 Error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->hasRight('contrat', 'lire')) {
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
		$sql .= " FROM ".MAIN_DB_PREFIX."contrat AS t LEFT JOIN ".MAIN_DB_PREFIX."contrat_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('contrat').')';
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

		//this query will return total orders with the filters given
		$sqlTotals = str_replace('SELECT t.rowid', 'SELECT count(t.rowid) as total', $sql);

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
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$contrat_static = new Contrat($this->db);
				if ($contrat_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($contrat_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve contrat list : '.$this->db->lasterror());
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
	 * Create contract object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of contrat
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403, "Insufficient rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->contract->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->contract->$field = $this->_checkValForAPI($field, $value, $this->contract);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->contract->lines = $lines;
		}*/
		if ($this->contract->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating contract", array_merge(array($this->contract->error), $this->contract->errors));
		}

		return $this->contract->id;
	}

	/**
	 * Get lines of a contract
	 *
	 * @param int   $id             Id of contract
	 *
	 * @url	GET {id}/lines
	 *
	 * @return array
	 */
	public function getLines($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->contract->getLinesArray();
		$result = array();
		foreach ($this->contract->lines as $line) {
			array_push($result, $this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Add a line to given contract
	 *
	 * @param int   $id             Id of contrat to update
	 * @param array $request_data   Contractline data
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int|bool
	 */
	public function postLine($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->price_base_type = sanitizeVal($request_data->price_base_type);

		$updateRes = $this->contract->addline(
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
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->subprice_excl_tax,
			$request_data->info_bits,
			$request_data->fk_fournprice,
			$request_data->pa_ht,
			$request_data->array_options,
			$request_data->fk_unit,
			$request_data->rang
		);

		if ($updateRes > 0) {
			return $updateRes;
		}
		return false;
	}

	/**
	 * Update a line to given contract
	 *
	 * @param int   $id             Id of contrat to update
	 * @param int   $lineid         Id of line to update
	 * @param array $request_data   Contractline data
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return Object|bool
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contrat not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->price_base_type = sanitizeVal($request_data->price_base_type);

		$updateRes = $this->contract->updateline(
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
			$request_data->date_start_real,
			$request_data->date_end_real,
			$request_data->price_base_type ? $request_data->price_base_type : 'HT',
			$request_data->info_bits,
			$request_data->fk_fourn_price,
			$request_data->pa_ht,
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

	/**
	 * Activate a service line of a given contract
	 *
	 * @param int		$id             Id of contract to activate
	 * @param int		$lineid         Id of line to activate
	 * @param string	$datestart		{@from body}  Date start        {@type timestamp}
	 * @param string    $dateend		{@from body}  Date end          {@type timestamp}
	 * @param string    $comment		{@from body}  Comment
	 *
	 * @url	PUT {id}/lines/{lineid}/activate
	 *
	 * @return Object|bool
	 */
	public function activateLine($id, $lineid, $datestart, $dateend = null, $comment = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contrat not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$updateRes = $this->contract->active_line(DolibarrApiAccess::$user, $lineid, $datestart, $dateend, $comment);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}

		return false;
	}

	/**
	 * Unactivate a service line of a given contract
	 *
	 * @param int		$id             Id of contract to activate
	 * @param int		$lineid         Id of line to activate
	 * @param string	$datestart		{@from body}  Date start        {@type timestamp}
	 * @param string    $comment		{@from body}  Comment
	 *
	 * @url	PUT {id}/lines/{lineid}/unactivate
	 *
	 * @return Object|bool
	 */
	public function unactivateLine($id, $lineid, $datestart, $comment = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contrat not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$updateRes = $this->contract->close_line(DolibarrApiAccess::$user, $lineid, $datestart, $comment);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}

		return false;
	}

	/**
	 * Delete a line to given contract
	 *
	 *
	 * @param int   $id             Id of contract to update
	 * @param int   $lineid         Id of line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return array|mixed
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contrat not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// TODO Check the lineid $lineid is a line of object

		$updateRes = $this->contract->deleteLine($lineid, DolibarrApiAccess::$user);
		if ($updateRes > 0) {
			return $this->get($id);
		} else {
			throw new RestException(405, $this->contract->error);
		}
	}

	/**
	 * Update contract general fields (won't touch lines of contract)
	 *
	 * @param 	int   	$id             	Id of contract to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contrat not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->contract->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}
			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->contract->array_options[$index] = $this->_checkValForAPI($field, $val, $this->contract);;
				}
				continue;
			}

			$this->contract->$field = $this->_checkValForAPI($field, $value, $this->contract);
		}

		if ($this->contract->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->contract->error);
		}
	}

	/**
	 * Delete contract
	 *
	 * @param   int     $id         Contract ID
	 *
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->contract->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete contract : '.$this->contract->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contract deleted'
			)
		);
	}

	/**
	 * Validate a contract
	 *
	 * @param   int $id             Contract ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "notrigger": 0
	 * }
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->contract->validate(DolibarrApiAccess::$user, '', $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Contract: '.$this->contract->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contract validated (Ref='.$this->contract->ref.')'
			)
		);
	}

	/**
	 * Close all services of a contract
	 *
	 * @param   int $id             Contract ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/close
	 *
	 * @return  array
	 * FIXME An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "notrigger": 0
	 * }
	 */
	public function close($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('contrat', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->contract->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Contract not found');
		}

		if (!DolibarrApi::_checkAccessToResource('contrat', $this->contract->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->contract->closeAll(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already close');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Contract: '.$this->contract->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Contract closed (Ref='.$this->contract->ref.'). All services were closed.'
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
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->address);
		unset($object->civility_id);

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
		$contrat = array();
		foreach (Contracts::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$contrat[$field] = $data[$field];
		}
		return $contrat;
	}
}
