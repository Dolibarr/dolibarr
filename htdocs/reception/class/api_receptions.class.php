<?php
/* Copyright (C) 2022       Quatadah Nasdami     <quatadah.nasdami@gmail.com>
 * Copyright (C) 2022       Laurent Destailleur     <eldy@users.sourceforge.net>
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

 require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';

/**
 * API class for receptions
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Receptions extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
		'origin_id',
		'origin_type',
	);

	/**
	 * @var Reception $reception {@type Reception}
	 */
	public $reception;

	/**
		* Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->reception = new Reception($this->db);
	}

	/**
	 * Get properties of a reception object
	 *
	 * Return an array with reception informations
	 *
	 * @param       int         $id         ID of reception
	 * @return		Object					Object with cleaned properties
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->reception->lire) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Reception not found');
		}

		if (!DolibarrApi::_checkAccessToResource('reception', $this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->reception->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->reception);
	}



	/**
	 * List receptions
	 *
	 * Get a list of receptions
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string		   $thirdparty_ids		Thirdparty ids to filter receptions of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @param string    $properties	Restrict the data returned to theses properties. Ignored if empty. Comma separated list of properties names
	 * @return  array                               Array of reception objects
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '')
	{
		global $db, $conf;

		if (!DolibarrApiAccess::$user->rights->reception->lire) {
			throw new RestException(401);
		}

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."reception AS t LEFT JOIN ".MAIN_DB_PREFIX."reception_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields

		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
		}

		$sql .= ' WHERE t.entity IN ('.getEntity('reception').')';
		if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) {
			$sql .= " AND t.fk_soc = sc.fk_soc";
		}
		if ($socids) {
			$sql .= " AND t.fk_soc IN (".$this->db->sanitize($socids).")";
		}
		if ($search_sale > 0) {
			$sql .= " AND t.rowid = sc.fk_soc"; // Join for the needed table to filter by sale
		}
		// Insert sale filter
		if ($search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
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

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$reception_static = new Reception($this->db);
				if ($reception_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($reception_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve commande list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}

	/**
	 * Create reception object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int     ID of reception
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401, "Insuffisant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again whith the caller
				$this->reception->context['caller'] = $request_data['caller'];
				continue;
			}

			$this->reception->$field = $value;
		}
		if (isset($request_data["lines"])) {
			$lines = array();
			foreach ($request_data["lines"] as $line) {
				array_push($lines, (object) $line);
			}
			$this->reception->lines = $lines;
		}

		if ($this->reception->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating reception", array_merge(array($this->reception->error), $this->reception->errors));
		}

		return $this->reception->id;
	}

	// /**
	//  * Get lines of an reception
	//  *
	//  * @param int   $id             Id of reception
	//  *
	//  * @url	GET {id}/lines
	//  *
	//  * @return int
	//  */
	/*
	public function getLines($id)
	{
		if(! DolibarrApiAccess::$user->rights->reception->lire) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Reception not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('reception',$this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->reception->getLinesArray();
		$result = array();
		foreach ($this->reception->lines as $line) {
			array_push($result,$this->_cleanObjectDatas($line));
		}
		return $result;
	}
	*/

	// /**
	//  * Add a line to given reception
	//  *
	//  * @param int   $id             Id of reception to update
	//  * @param array $request_data   ShipmentLine data
	//  *
	//  * @url	POST {id}/lines
	//  *
	//  * @return int
	//  */
	/*
	public function postLine($id, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if ( ! $result ) {
			throw new RestException(404, 'Reception not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('reception',$this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->reception->addline(
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
						$request_data->origin,
						$request_data->origin_id,
						$request_data->multicurrency_subprice
		);

		if ($updateRes > 0) {
			return $updateRes;

		}
		return false;
	}*/

	// /**
	//  * Update a line to given reception
	//  *
	//  * @param int   $id             Id of reception to update
	//  * @param int   $lineid         Id of line to update
	//  * @param array $request_data   ShipmentLine data
	//  *
	//  * @url	PUT {id}/lines/{lineid}
	//  *
	//  * @return object
	//  */
	/*
	public function putLine($id, $lineid, $request_data = null)
	{
		if (! DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if ( ! $result ) {
			throw new RestException(404, 'Reception not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('reception',$this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$request_data = (object) $request_data;

		$request_data->desc = sanitizeVal($request_data->desc, 'restricthtml');
		$request_data->label = sanitizeVal($request_data->label);

		$updateRes = $this->reception->updateline(
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
						$request_data->fk_unit,
						$request_data->multicurrency_subprice
		);

		if ($updateRes > 0) {
			$result = $this->get($id);
			unset($result->line);
			return $this->_cleanObjectDatas($result);
		}
		return false;
	}*/

	/**
	 * Delete a line to given reception
	 *
	 * @param int   $id             Id of reception to update
	 * @param int   $lineid         Id of line to delete
	 * @return array
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Reception not found');
		}

		if (!DolibarrApi::_checkAccessToResource('reception', $this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// TODO Check the lineid $lineid is a line of object

		$updateRes = $this->reception->deleteline(DolibarrApiAccess::$user, $lineid);
		if ($updateRes < 0) {
			throw new RestException(405, $this->reception->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Line deleted'
			)
		);
	}

	/**
	 * Update reception general fields (won't touch lines of reception)
	 *
	 * @param int   $id						Id of reception to update
	 * @param array $request_data			Datas
	 * @return		Object					Object with cleaned properties
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Reception not found');
		}

		if (!DolibarrApi::_checkAccessToResource('reception', $this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again whith the caller
				$this->reception->context['caller'] = $request_data['caller'];
				continue;
			}

			$this->reception->$field = $value;
		}

		if ($this->reception->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->reception->error);
		}
	}

	/**
	 * Delete reception
	 *
	 * @param   int     $id         Reception ID
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->reception->supprimer) {
			throw new RestException(401);
		}
		$result = $this->reception->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Reception not found');
		}

		if (!DolibarrApi::_checkAccessToResource('reception', $this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->reception->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting reception : '.$this->reception->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Reception deleted'
			)
		);
	}

	/**
	 * Validate a reception
	 *
	 * This may record stock movements if module stock is enabled and option to
	 * decrease stock on reception is on.
	 *
	 * @param   int		$id             Reception ID
	 * @param   int		$notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  Object
	 * \todo An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "notrigger": 0
	 * }
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401);
		}
		$result = $this->reception->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Reception not found');
		}

		if (!DolibarrApi::_checkAccessToResource('reception', $this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->reception->valid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Reception: '.$this->reception->error);
		}

		// Reload reception
		$result = $this->reception->fetch($id);

		$this->reception->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->reception);
	}


	// /**
	//  *  Classify the reception as invoiced
	//  *
	//  * @param int   $id           Id of the reception
	//  *
	//  * @url     POST {id}/setinvoiced
	//  *
	//  * @return int
	//  *
	//  * @throws RestException 400
	//  * @throws RestException 401
	//  * @throws RestException 404
	//  * @throws RestException 405
	//  */
	/*
	public function setinvoiced($id)
	{

		if(! DolibarrApiAccess::$user->rights->reception->creer) {
				throw new RestException(401);
		}
		if(empty($id)) {
				throw new RestException(400, 'Reception ID is mandatory');
		}
		$result = $this->reception->fetch($id);
		if( ! $result ) {
				throw new RestException(404, 'Reception not found');
		}

		$result = $this->reception->classifyBilled(DolibarrApiAccess::$user);
		if( $result < 0) {
				throw new RestException(400, $this->reception->error);
		}
		return $result;
	}
	*/


	//  /**
	//  * Create a reception using an existing order.
	//  *
	//  * @param int   $orderid       Id of the order
	//  *
	//  * @url     POST /createfromorder/{orderid}
	//  *
	//  * @return int
	//  * @throws RestException 400
	//  * @throws RestException 401
	//  * @throws RestException 404
	//  * @throws RestException 405
	//  */
	/*
	public function createShipmentFromOrder($orderid)
	{

		require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

		if(! DolibarrApiAccess::$user->rights->reception->lire) {
				throw new RestException(401);
		}
		if(! DolibarrApiAccess::$user->rights->reception->creer) {
				throw new RestException(401);
		}
		if(empty($proposalid)) {
				throw new RestException(400, 'Order ID is mandatory');
		}

		$order = new Commande($this->db);
		$result = $order->fetch($proposalid);
		if( ! $result ) {
				throw new RestException(404, 'Order not found');
		}

		$result = $this->reception->createFromOrder($order, DolibarrApiAccess::$user);
		if( $result < 0) {
				throw new RestException(405, $this->reception->error);
		}
		$this->reception->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->reception);
	}
	*/

	/**
	* Close a reception (Classify it as "Delivered")
	*
	* @param	int     $id             Reception ID
	* @param	int     $notrigger      Disabled triggers
	*
	* @url POST    {id}/close
	*
	* @return  Object
	*/
	public function close($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->rights->reception->creer) {
			throw new RestException(401);
		}

		$result = $this->reception->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Reception not found');
		}

		if (!DolibarrApi::_checkAccessToResource('reception', $this->reception->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->reception->setClosed();
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Reception: '.$this->reception->error);
		}

		// Reload reception
		$result = $this->reception->fetch($id);

		$this->reception->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->reception);
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

		unset($object->thirdparty); // id already returned

		unset($object->note);
		unset($object->address);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		if (!empty($object->lines) && is_array($object->lines)) {
			foreach ($object->lines as $line) {
				unset($line->tva_tx);
				unset($line->vat_src_code);
				unset($line->total_ht);
				unset($line->total_ttc);
				unset($line->total_tva);
				unset($line->total_localtax1);
				unset($line->total_localtax2);
				unset($line->remise_percent);
			}
		}

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
		$reception = array();
		foreach (Receptions::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$reception[$field] = $data[$field];
		}
		return $reception;
	}
}
