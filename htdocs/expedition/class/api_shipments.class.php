<?php
/* Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2025		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Charlene Benke  		<charlene@patas-monkey.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

/**
 * API class for shipments
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Shipments extends DolibarrApi
{
	/**
	 * @var string[]	Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'socid',
		'origin_id',
		'origin_type',
	);

	/**
	 * @var Expedition {@type Expedition}
	 */
	public $shipment;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->shipment = new Expedition($this->db);
	}

	/**
	 * Get properties of a shipment object
	 *
	 * Return an array with shipment information
	 *
	 * @param   int         $id         ID of shipment
	 * @return  Object					Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->shipment->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->shipment);
	}



	/**
	 * List shipments
	 *
	 * Get a list of shipments
	 *
	 * @param string		   $sortfield			Sort field
	 * @param string		   $sortorder			Sort order
	 * @param int			   $limit				Limit for list
	 * @param int			   $page				Page number
	 * @param string		   $thirdparty_ids		Thirdparty ids to filter shipments of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}
	 * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:>:'20160101')"
	 * @param string		   $properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array                               Array of shipment objects
	 * @phan-return Expedition[]
	 * @phpstan-return Expedition[]
	 *
	 * @throws RestException
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'lire')) {
			throw new RestException(403);
		}

		global $hookmanager;

		$obj_ret = array();

		// case of external user, $thirdparty_ids param is ignored and replaced by user's socid
		$socids = DolibarrApiAccess::$user->socid ?: $thirdparty_ids;

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socids) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."expedition AS t";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe AS s ON (s.rowid = t.fk_soc)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expedition_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('expedition').')';
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
			$parameters = array('sqlfilters' => $sqlfilters, 'apiroute' => 'shipments', 'apimethod' => 'index');
			$object = new stdClass();
			$action = 'list';
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook > 0) {
				$sql = $hookmanager->resPrint;
			} elseif ($reshook == 0) {
				$sql .= $hookmanager->resPrint;
			}
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		//this query will return total shipments with the filters given
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
				$shipment_static = new Expedition($this->db);
				if ($shipment_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($shipment_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve commande list : '.$this->db->lasterror());
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
	 * Create shipment object
	 *
	 * @param   array   $request_data   Request data
	 * @phan-param ?array<string,string|array<string,string|array<string,string>>> $request_data
	 * @phpstan-param ?array<string,string|array<string,string|array<string,string>>> $request_data
	 * @return  int     				ID of shipment created
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403, "Insufficiant rights");
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->shipment->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->shipment->$field = $this->_checkValForAPI($field, $value, $this->shipment);
		}
		if (isset($request_data["lines"])) {
			$lines = array();
			foreach ($request_data["lines"] as $line) {
				$shipmentline = new ExpeditionLigne($this->db);

				$shipmentline->entrepot_id = (int) $line['entrepot_id'];
				$shipmentline->fk_element = (int) ($line['fk_element'] ?? $line['origin_id']);				// example: order id.  this->origin is 'commande'
				$shipmentline->origin_line_id = (int) ($line['fk_elementdet'] ?? $line['origin_line_id']);	// example: order id
				$shipmentline->fk_elementdet = (int) ($line['fk_elementdet'] ?? $line['origin_line_id']);	// example: order line id
				$shipmentline->origin_type = $line['element_type'] ?? $line['origin_type'];			// example 'commande' or 'order'
				$shipmentline->element_type = $line['element_type'] ?? $line['origin_type'];		// example 'commande' or 'order'
				$shipmentline->qty = (float) $line['qty'];
				$shipmentline->rang = (int) $line['rang'];
				$array_options = $line['array_options'];
				if (is_array($array_options)) {
					$shipmentline->array_options = $array_options;
				}
				$detail_batch = $line['detail_batch'];
				if (is_array($detail_batch) || is_object($detail_batch)) {
					$shipmentline->detail_batch = $detail_batch;
				}
				$lines[] = $shipmentline;
			}
			$this->shipment->lines = $lines;
		}

		if ($this->shipment->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating shipment", array_merge(array($this->shipment->error), $this->shipment->errors));
		}

		return $this->shipment->id;
	}

	//  * Get lines of an shipment
	//  *
	//  * @param int   $id             Id of shipment
	//  *
	//  * @url	GET {id}/lines
	//  *
	//  * @return int
	//  */
	public function getLines($id)
	{
		if(! DolibarrApiAccess::$user->hasRight('expedition', 'lire')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if( ! $result ) {
			throw new RestException(404, 'Shipment not found');
		}

		if( ! DolibarrApi::_checkAccessToResource('expedition',$this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		$this->shipment->getLinesArray();
		$result = array();
		foreach ($this->shipment->lines as $line) {
			array_push($result,$this->_cleanObjectDatas($line));
		}
		return $result;
	}

	/**
	 * Add a line to a given shipment
	 *
	 * A shipment line always references the order line it ships (fk_origin_line):
	 * the shipped quantity relates to that order line. A line can only be added
	 * while the shipment is still a draft, before any stock movement.
	 * Lines managed by lot/serial (batch) are not supported here (the quantity is
	 * split by lot in expeditiondet_batch) - use the dedicated dispatch flow instead.
	 *
	 * @param int   $id                 Id of shipment to update
	 * @param int   $fk_origin_line     Id of the order line shipped by this line			{@from body}{@required true}
	 * @param float $qty                Quantity to ship for this line						{@from body}{@required true}{@min 1}
	 * @param int   $warehouse_id       Source warehouse id (may be optional depending on STOCK_WAREHOUSE_NOT_REQUIRED_FOR_SHIPMENTS)	{@from body}{@required false}
	 * @param int   $fk_product         Product id (optional, defaults to the order line product)	{@from body}{@required false}
	 *
	 * @url	POST {id}/lines
	 *
	 * @return int							Id of the created shipment line
	 *
	 * @throws RestException 400
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 405
	 * @throws RestException 500
	 */
	public function postLine($id, $fk_origin_line = 0, $qty = 0, $warehouse_id = 0, $fk_product = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// A line can only be added while the shipment is a draft (no stock movement yet).
		if ((int) $this->shipment->status != Expedition::STATUS_DRAFT) {
			throw new RestException(405, 'Lines can only be added to a draft shipment');
		}

		$fk_origin_line = (int) $fk_origin_line;
		if ($fk_origin_line <= 0) {
			throw new RestException(400, 'Field fk_origin_line is mandatory');
		}

		$qty = (float) $qty;
		if ($qty <= 0) {
			throw new RestException(400, 'Field qty is mandatory and must be greater than 0');
		}

		require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
		$orderline = new OrderLine($this->db);
		$resorderline = $orderline->fetch($fk_origin_line);
		if ($resorderline <= 0) {
			throw new RestException(404, 'Origin order line '.$fk_origin_line.' not found');
		}
		if (isModEnabled('productbatch') && !empty($orderline->product_tobatch)) {
			throw new RestException(405, 'Adding a batch/lot managed shipment line is not supported through this endpoint. Use the dedicated dispatch flow instead.');
		}

		// addline() stacks the line in memory and runs native validation
		// (warehouse requirement, stock availability, batch rejection).
		$addRes = $this->shipment->addline((int) $warehouse_id, $fk_origin_line, $qty, array(), (int) $fk_product);
		if ($addRes <= 0) {
			$msg = $this->shipment->error ? $this->shipment->error : 'Error while adding shipment line';
			// -1 missing warehouse, -3 not enough stock, -4 batch product: bad request from the caller.
			if (in_array((int) $addRes, array(-1, -3, -4), true)) {
				throw new RestException(400, $msg);
			}
			throw new RestException(500, $msg);
		}

		// addline() only fills $this->shipment->lines; persist the line just stacked.
		$line = end($this->shipment->lines);
		$line->fk_expedition = $this->shipment->id;

		$insertRes = $line->insert(DolibarrApiAccess::$user);
		if ($insertRes > 0) {
			return $insertRes;
		}

		throw new RestException(500, $line->error ? $line->error : implode(', ', $line->errors));
	}

	/**
	 * Update a line of a given shipment
	 *
	 * Only the quantity (and optionally the source warehouse) of the line can be
	 * changed, and only while the shipment is still a draft: once validated the
	 * stock has already been moved, so editing the line would desync the stock.
	 * Lines managed by lot/serial (batch) are not supported here (the quantity is
	 * split by lot in expeditiondet_batch) - delete the line and create it again.
	 *
	 * @param int   $id             Id of shipment to update
	 * @param int   $lineid         Id of line to update
	 * @param float $qty            New quantity to ship for this line			{@from body}{@required true}{@min 1}
	 * @param int   $warehouse_id   Source warehouse id for this line (optional, 0 to keep the current one)	{@from body}{@required false}
	 *
	 * @url	PUT {id}/lines/{lineid}
	 *
	 * @return Object					Updated shipment
	 *
	 * @throws RestException 400
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 405
	 * @throws RestException 500
	 */
	public function putLine($id, $lineid, $qty = 0, $warehouse_id = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// A line can only be edited while the shipment is a draft (no stock movement yet).
		if ((int) $this->shipment->status != Expedition::STATUS_DRAFT) {
			throw new RestException(405, 'Only draft shipment lines can be updated');
		}

		$qty = (float) $qty;
		if ($qty <= 0) {
			throw new RestException(400, 'Field qty is mandatory and must be greater than 0');
		}

		$line = new ExpeditionLigne($this->db);
		$resline = $line->fetch($lineid);
		if ($resline <= 0) {
			throw new RestException(404, 'Shipment line not found');
		}
		if ((int) $line->fk_expedition != (int) $this->shipment->id) {
			throw new RestException(404, 'Line '.((int) $lineid).' is not a line of shipment '.((int) $id));
		}

		require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionlinebatch.class.php';
		$linebatch = new ExpeditionLineBatch($this->db);
		$lots = $linebatch->fetchAll($line->id);
		if (is_array($lots) && count($lots) > 0) {
			throw new RestException(405, 'Update of a batch/lot managed shipment line is not supported. Delete and recreate the line instead.');
		}

		$line->qty = $qty;
		if ($warehouse_id > 0) {
			$line->entrepot_id = (int) $warehouse_id;
		}

		$updateRes = $line->update(DolibarrApiAccess::$user);
		if ($updateRes > 0) {
			return $this->get($id);
		}

		throw new RestException(500, $line->error ? $line->error : $this->shipment->error);
	}

	/**
	 * Delete a line to given shipment
	 *
	 *
	 * @param int   $id             Id of shipment to update
	 * @param int   $lineid         Id of line to delete
	 *
	 * @url	DELETE {id}/lines/{lineid}
	 *
	 * @return array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 *
	 * @throws RestException 401
	 * @throws RestException 404
	 */
	public function deleteLine($id, $lineid)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		// TODO Check the lineid $lineid is a line of object

		$updateRes = $this->shipment->deleteLine(DolibarrApiAccess::$user, $lineid);
		if ($updateRes > 0) {
			return array(
			'success' => array(
				'code' => 200,
				'message' => 'line ' .$lineid. ' deleted'
			)
			);
		} else {
			throw new RestException(405, $this->shipment->error);
		}
	}

	/**
	 * Update shipment general fields (won't touch lines of shipment)
	 *
	 * @param 	int   	$id             	Id of shipment to update
	 * @param 	array 	$request_data   	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->shipment->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			if ($field == 'array_options' && is_array($value)) {
				foreach ($value as $index => $val) {
					$this->shipment->array_options[$index] = $this->_checkValExtrafieldsForAPI($index, $val, $this->shipment);
				}
				continue;
			}
			$this->shipment->$field = $this->_checkValForAPI($field, $value, $this->shipment);
		}

		if ($this->shipment->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->shipment->error);
		}
	}

	/**
	 * Delete shipment
	 *
	 * @param   int     $id         Shipment ID
	 *
	 * @return  array
	 * @phan-return array{success:array{code:int,message:string}}
	 * @phpstan-return array{success:array{code:int,message:string}}
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'supprimer')) {
			throw new RestException(403);
		}
		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->shipment->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting shipment : '.$this->shipment->error);
		}

		return array(
		'success' => array(
			'code' => 200,
			'message' => 'Shipment deleted'
		)
		);
	}

	/**
	 * Validate a shipment
	 *
	 * This may record stock movements if module stock is enabled and option to
	 * decrease stock on shipment is on.
	 *
	 * @param   int $id             Shipment ID
	 * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
	 *
	 * @url POST    {id}/validate
	 *
	 * @return  object
	 * \todo An error 403 is returned if the request has an empty body.
	 * Error message: "Forbidden: Content type `text/plain` is not supported."
	 * Workaround: send this in the body
	 * {
	 *   "notrigger": 0
	 * }
	 */
	public function validate($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}
		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->shipment->valid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when validating Shipment: '.$this->shipment->error);
		}

		// Reload shipment
		$result = $this->shipment->fetch($id);

		$this->shipment->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->shipment);
	}


	// /**
	//  *  Classify the shipment as invoiced
	//  *
	//  * @param int   $id           Id of the shipment
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

	if(! DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
	}
	if(empty($id)) {
			throw new RestException(400, 'Shipment ID is mandatory');
	}
	$result = $this->shipment->fetch($id);
	if( ! $result ) {
			throw new RestException(404, 'Shipment not found');
	}

	$result = $this->shipment->classifyBilled(DolibarrApiAccess::$user);
	if( $result < 0) {
			throw new RestException(400, $this->shipment->error);
	}
	return $result;
	}
	*/


	//  /**
	//  * Create a shipment using an existing order.
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

	if(! DolibarrApiAccess::$user->hasRight('expedition', 'lire')) {
			throw new RestException(403);
	}
	if(! DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
	}
	if(empty($proposalid)) {
			throw new RestException(400, 'Order ID is mandatory');
	}

	$order = new Commande($this->db);
	$result = $order->fetch($proposalid);
	if( ! $result ) {
			throw new RestException(404, 'Order not found');
	}

	$result = $this->shipment->createFromOrder($order, DolibarrApiAccess::$user);
	if( $result < 0) {
			throw new RestException(405, $this->shipment->error);
	}
	$this->shipment->fetchObjectLinked();
	return $this->_cleanObjectDatas($this->shipment);
	}
	*/

	/**
	 * Close a shipment (Classify it as "Delivered")
	 *
	 * @param   int     $id             Expedition ID
	 * @param   int     $notrigger      Disabled triggers
	 *
	 * @url POST    {id}/close
	 *
	 * @return  object
	 */
	public function close($id, $notrigger = 0)
	{
		if (!DolibarrApiAccess::$user->hasRight('expedition', 'creer')) {
			throw new RestException(403);
		}

		$result = $this->shipment->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Shipment not found');
		}

		if (!DolibarrApi::_checkAccessToResource('expedition', $this->shipment->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$result = $this->shipment->setClosed();
		if ($result == 0) {
			throw new RestException(304, 'Error nothing done. May be object is already closed');
		}
		if ($result < 0) {
			throw new RestException(500, 'Error when closing Order: '.$this->shipment->error);
		}

		// Reload shipment
		$result = $this->shipment->fetch($id);

		$this->shipment->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->shipment);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 * @phpstan-template T
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 * @phpstan-param T $object
	 * @phpstan-return T
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->canvas);

		unset($object->thirdparty); // id already returned

		unset($object->note);
		unset($object->address);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);

		if (!empty($object->lines) && is_array($object->lines)) {
			foreach ($object->lines as $line) {
				if (is_array($line->detail_batch)) {
					foreach ($line->detail_batch as $keytmp2 => $valtmp2) {
						unset($line->detail_batch[$keytmp2]->db);
					}
				}
				unset($line->canvas);

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
	 * @param ?array<string,string> $data   Array with data to verify
	 * @return array<string,string>
	 * @throws  RestException
	 */
	private function _validate($data)
	{
		if ($data === null) {
			$data = array();
		}
		$shipment = array();
		foreach (Shipments::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$shipment[$field] = $data[$field];
		}
		return $shipment;
	}
}
