<?php
/* Copyright (C) 2016   Jean-François Ferry     <hello@librethic.io>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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

require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';


/**
 * API class for ticket object
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Tickets extends DolibarrApi
{
	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'subject',
		'message'
	);

	/**
	 * @var array   $FIELDS_MESSAGES     Mandatory fields, checked when create and update object
	 */
	public static $FIELDS_MESSAGES = array(
		'track_id',
		'message'
	);

	/**
	 * @var Ticket $ticket {@type Ticket}
	 */
	public $ticket;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->ticket = new Ticket($this->db);
	}

	/**
	 * Get properties of a Ticket object.
	 *
	 * Return an array with ticket information
	 *
	 * @param	int				$id			ID of ticket
	 * @return  Object						Object with cleaned properties
	 *
	 * @throws RestException 401
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function get($id)
	{
		return $this->getCommon($id, '', '');
	}

	/**
	 * Get properties of a Ticket object from track id
	 *
	 * Return an array with ticket information
	 *
	 * @param	string			$track_id	Tracking ID of ticket
	 * @return	array|mixed					Data without useless information
	 *
	 * @url GET track_id/{track_id}
	 *
	 * @throws RestException	401
	 * @throws RestException	403
	 * @throws RestException	404
	 */
	public function getByTrackId($track_id)
	{
		return $this->getCommon(0, $track_id, '');
	}

	/**
	 * Get properties of a Ticket object from ref
	 *
	 * Return an array with ticket information
	 *
	 * @param	string			$ref		Reference for ticket
	 * @return	array|mixed					Data without useless information
	 *
	 * @url GET ref/{ref}
	 *
	 * @throws RestException 401
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getByRef($ref)
	{
		return $this->getCommon(0, '', $ref);
	}

	/**
	 * Get properties of a Ticket object
	 * Return an array with ticket information
	 *
	 * @param	int				$id			ID of ticket
	 * @param	string			$track_id	Tracking ID of ticket
	 * @param	string			$ref		Reference for ticket
	 * @return	array|mixed					Data without useless information
	 */
	private function getCommon($id = 0, $track_id = '', $ref = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('ticket', 'read')) {
			throw new RestException(403);
		}

		// Check parameters
		if (($id < 0) && !$track_id && !$ref) {
			throw new RestException(400, 'Wrong parameters');
		}
		if (empty($id) && empty($ref) && empty($track_id)) {
			$result = $this->ticket->initAsSpecimen();
		} else {
			$result = $this->ticket->fetch($id, $ref, $track_id);
		}
		if (!$result) {
			throw new RestException(404, 'Ticket not found');
		}

		// String for user assigned
		if ($this->ticket->fk_user_assign > 0) {
			$userStatic = new User($this->db);
			$userStatic->fetch($this->ticket->fk_user_assign);
			$this->ticket->fk_user_assign_string = $userStatic->firstname.' '.$userStatic->lastname;
		}

		// Messages of ticket
		$messages = array();
		$this->ticket->loadCacheMsgsTicket();
		if (is_array($this->ticket->cache_msgs_ticket) && count($this->ticket->cache_msgs_ticket) > 0) {
			$num = count($this->ticket->cache_msgs_ticket);
			$i = 0;
			while ($i < $num) {
				if ($this->ticket->cache_msgs_ticket[$i]['fk_user_author'] > 0) {
					$user_action = new User($this->db);
					$user_action->fetch($this->ticket->cache_msgs_ticket[$i]['fk_user_author']);
				}

				// Now define messages
				$messages[] = array(
				'id' => $this->ticket->cache_msgs_ticket[$i]['id'],
				'fk_user_action' => $this->ticket->cache_msgs_ticket[$i]['fk_user_author'],
				'fk_user_action_socid' =>  $user_action->socid,
				'fk_user_action_string' => dolGetFirstLastname($user_action->firstname, $user_action->lastname),
				'message' => $this->ticket->cache_msgs_ticket[$i]['message'],
				'datec' => $this->ticket->cache_msgs_ticket[$i]['datec'],
				'private' => $this->ticket->cache_msgs_ticket[$i]['private']
				);
				$i++;
			}
			$this->ticket->messages = $messages;
		}

		if (!DolibarrApi::_checkAccessToResource('ticket', $this->ticket->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		return $this->_cleanObjectDatas($this->ticket);
	}

	/**
	 * List tickets
	 *
	 * Get a list of tickets
	 *
	 * @param int       $socid      Filter list with thirdparty ID
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string	$sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101') and (t.fk_statut:=:1)"
	 * @param string    $properties	Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool             $pagination_data     If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 *
	 * @return array Array of ticket objects
	 *
	 */
	public function index($socid = 0, $sortfield = "t.rowid", $sortorder = "ASC", $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		if (!DolibarrApiAccess::$user->hasRight('ticket', 'read')) {
			throw new RestException(403);
		}

		$obj_ret = array();

		$socid = DolibarrApiAccess::$user->socid ? DolibarrApiAccess::$user->socid : $socid;

		$search_sale = null;
		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."ticket_extrafields AS ef ON (ef.fk_object = t.rowid)"; // Modification VMR Global Solutions to include extrafields as search parameters in the API GET call, so we will be able to filter on extrafields
		$sql .= ' WHERE t.entity IN ('.getEntity('ticket', 1).')';
		if ($socid > 0) {
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

			$sql .= $this->db->plimit($limit, $offset);
		}

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($result);
				$ticket_static = new Ticket($this->db);
				if ($ticket_static->fetch($obj->rowid)) {
					if ($ticket_static->fk_user_assign > 0) {
						$userStatic = new User($this->db);
						$userStatic->fetch($ticket_static->fk_user_assign);
						$ticket_static->fk_user_assign_string = $userStatic->firstname.' '.$userStatic->lastname;
					}
					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($ticket_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve ticket list');
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
	 * Create ticket object
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of ticket
	 */
	public function post($request_data = null)
	{
		$ticketstatic = new Ticket($this->db);
		if (!DolibarrApiAccess::$user->hasRight('ticket', 'write')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->ticket->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->ticket->$field = $this->_checkValForAPI($field, $value, $this->ticket);
		}
		if (empty($this->ticket->ref)) {
			$this->ticket->ref = $ticketstatic->getDefaultRef();
		}
		if (empty($this->ticket->track_id)) {
			$this->ticket->track_id = generate_random_id(16);
		}

		if ($this->ticket->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating ticket", array_merge(array($this->ticket->error), $this->ticket->errors));
		}

		return $this->ticket->id;
	}

	/**
	 * Add a new message to an existing ticket identified by property ->track_id into request.
	 *
	 * @param array $request_data   Request datas
	 * @return int  ID of ticket
	 *
	 */
	public function postNewMessage($request_data = null)
	{
		$ticketstatic = new Ticket($this->db);
		if (!DolibarrApiAccess::$user->hasRight('ticket', 'write')) {
			throw new RestException(403);
		}
		// Check mandatory fields
		$result = $this->_validateMessage($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->ticket->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->ticket->$field = $this->_checkValForAPI($field, $value, $this->ticket);
		}
		$ticketMessageText = $this->ticket->message;
		$result = $this->ticket->fetch('', '', $this->ticket->track_id);
		if (!$result) {
			throw new RestException(404, 'Ticket not found');
		}
		$this->ticket->message = $ticketMessageText;
		if (!$this->ticket->createTicketMessage(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when creating ticket');
		}
		return $this->ticket->id;
	}

	/**
	 * Update ticket
	 *
	 * @param 	int   	$id             	Id of ticket to update
	 * @param 	array 	$request_data   	Datas
	 * @return 	Object						Updated object
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->hasRight('ticket', 'write')) {
			throw new RestException(403);
		}

		$result = $this->ticket->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Ticket not found');
		}

		if (!DolibarrApi::_checkAccessToResource('ticket', $this->ticket->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->ticket->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->ticket->$field = $this->_checkValForAPI($field, $value, $this->ticket);
		}

		if ($this->ticket->update(DolibarrApiAccess::$user) > 0) {
			return $this->get($id);
		} else {
			throw new RestException(500, $this->ticket->error);
		}
	}

	/**
	 * Delete ticket
	 *
	 * @param   int     $id   Ticket ID
	 * @return  array
	 *
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->hasRight('ticket', 'delete')) {
			throw new RestException(403);
		}
		$result = $this->ticket->fetch($id);
		if (!$result) {
			throw new RestException(404, 'Ticket not found');
		}

		if (!DolibarrApi::_checkAccessToResource('ticket', $this->ticket->id)) {
			throw new RestException(403, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->ticket->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when deleting ticket');
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Ticket deleted'
			)
		);
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param array $data   Data to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validate($data)
	{
		$ticket = array();
		foreach (Tickets::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$ticket[$field] = $data[$field];
		}
		return $ticket;
	}

	/**
	 * Validate fields before create or update object message
	 *
	 * @param array $data   Data to validate
	 * @return array
	 *
	 * @throws RestException
	 */
	private function _validateMessage($data)
	{
		$ticket = array();
		foreach (Tickets::$FIELDS_MESSAGES as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$ticket[$field] = $data[$field];
		}
		return $ticket;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     Object to clean
	 * @return  Object              Object with cleaned properties
	 *
	 * @todo use an array for properties to clean
	 *
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		// Other attributes to clean
		$attr2clean = array(
			"contact",
			"contact_id",
			"ref_previous",
			"ref_next",
			"ref_ext",
			"table_element_line",
			"statut",
			"country",
			"country_id",
			"country_code",
			"barcode_type",
			"barcode_type_code",
			"barcode_type_label",
			"barcode_type_coder",
			"mode_reglement_id",
			"cond_reglement_id",
			"cond_reglement",
			"fk_delivery_address",
			"shipping_method_id",
			"modelpdf",
			"fk_account",
			"note_public",
			"note_private",
			"note",
			"total_ht",
			"total_tva",
			"total_localtax1",
			"total_localtax2",
			"total_ttc",
			"fk_incoterms",
			"label_incoterms",
			"location_incoterms",
			"name",
			"lastname",
			"firstname",
			"civility_id",
			"canvas",
			"cache_msgs_ticket",
			"cache_logs_ticket",
			"cache_types_tickets",
			"cache_category_tickets",
			"regeximgext",
			"labelStatus",
			"labelStatusShort",
			"multicurrency_code",
			"multicurrency_tx",
			"multicurrency_total_ht",
			"multicurrency_total_ttc",
			"multicurrency_total_tva",
			"multicurrency_total_localtax1",
			"multicurrency_total_localtax2"
		);
		foreach ($attr2clean as $toclean) {
			unset($object->$toclean);
		}

		// If object has lines, remove $db property
		if (isset($object->lines) && count($object->lines) > 0) {
			$nboflines = count($object->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				$this->_cleanObjectDatas($object->lines[$i]);
			}
		}

		// If object has linked objects, remove $db property
		if (isset($object->linkedObjects) && count($object->linkedObjects) > 0) {
			foreach ($object->linkedObjects as $type_object => $linked_object) {
				foreach ($linked_object as $object2clean) {
					$this->_cleanObjectDatas($object2clean);
				}
			}
		}
		return $object;
	}
}
