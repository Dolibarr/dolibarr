<?php
/* Copyright (C) 2016   Jean-François Ferry     <hello@librethic.io>
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

require 'ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';


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
     * Return an array with ticket informations
     *
     * @param	int 			$id 		ID of ticket
     * @return 	array|mixed 				Data without useless information
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    public function get($id)
    {
        return $this->getCommon($id, '', '');
    }

    /**
     * Get properties of a Ticket object from track id
     *
     * Return an array with ticket informations
     *
     * @param	string  		$track_id 	Tracking ID of ticket
     * @return 	array|mixed 				Data without useless information
     *
     * @url GET track_id/{track_id}
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    public function getByTrackId($track_id)
    {
        return $this->getCommon(0, $track_id, '');
    }

    /**
     * Get properties of a Ticket object from ref
     *
     * Return an array with ticket informations
     *
     * @param	string  		$ref    	Reference for ticket
     * @return 	array|mixed 				Data without useless information
     *
     * @url GET ref/{ref}
     *
     * @throws 	401
     * @throws 	403
     * @throws 	404
     */
    public function getByRef($ref)
    {
        try {
            return $this->getCommon(0, '', $ref);
        }
        catch(Exception $e)
        {
               throw $e;
        }
    }

    /**
     * Get properties of a Ticket object
     * Return an array with ticket informations
     *
     * @param	int 			$id 		ID of ticket
     * @param	string  		$track_id 	Tracking ID of ticket
     * @param	string  		$ref    	Reference for ticket
     * @return 	array|mixed 				Data without useless information
     */
    private function getCommon($id = 0, $track_id = '', $ref = '')
    {
        if (! DolibarrApiAccess::$user->rights->ticket->read) {
            throw new RestException(403);
        }

        // Check parameters
        if (!$id && !$track_id && !$ref) {
            throw new RestException(401, 'Wrong parameters');
        }

        $result = $this->ticket->fetch($id, $ref, $track_id);
        if (! $result) {
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

        // History
        $history = array();
        $this->ticket->loadCacheLogsTicket();
        if (is_array($this->ticket->cache_logs_ticket) && count($this->ticket->cache_logs_ticket) > 0) {
            $num = count($this->ticket->cache_logs_ticket);
            $i = 0;
            while ($i < $num) {
                if ($this->ticket->cache_logs_ticket[$i]['fk_user_create'] > 0) {
                    $user_action = new User($this->db);
                    $user_action->fetch($this->ticket->cache_logs_ticket[$i]['fk_user_create']);
                }

                // Now define messages
                $history[] = array(
                'id' => $this->ticket->cache_logs_ticket[$i]['id'],
                'fk_user_author' => $this->ticket->cache_msgs_ticket[$i]['fk_user_author'],
                'fk_user_action' => $this->ticket->cache_logs_ticket[$i]['fk_user_create'],
                'fk_user_action_string' => dolGetFirstLastname($user_action->firstname, $user_action->lastname),
                'message' => $this->ticket->cache_logs_ticket[$i]['message'],
                'datec' => $this->ticket->cache_logs_ticket[$i]['datec'],
                );
                $i++;
            }
            $this->ticket->history = $history;
        }


        if (! DolibarrApi::_checkAccessToResource('ticket', $this->ticket->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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
     *
     * @return array Array of ticket objects
     *
     */
    public function index($socid = 0, $sortfield = "t.rowid", $sortorder = "ASC", $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if (!$socid && DolibarrApiAccess::$user->socid) {
            $socid = DolibarrApiAccess::$user->socid;
        }

        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
            $search_sale = DolibarrApiAccess::$user->id;
        }

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
            $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        }
        $sql.= " FROM ".MAIN_DB_PREFIX."ticket as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
            $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        }

        $sql.= ' WHERE t.entity IN ('.getEntity('ticket', 1).')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
            $sql.= " AND t.fk_soc = sc.fk_soc";
        }
        if ($socid > 0) {
            $sql.= " AND t.fk_soc = ".$socid;
        }
        if ($search_sale > 0) {
            $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        }

        // Insert sale filter
        if ($search_sale > 0) {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        // Add sql filters
        if ($sqlfilters) {
            if (! DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $i = 0;
            while ($i < $num) {
                $obj = $db->fetch_object($result);
                $ticket_static = new Ticket($db);
                if ($ticket_static->fetch($obj->rowid)) {
                    if ($ticket_static->fk_user_assign > 0) {
                        $userStatic = new User($this->db);
                        $userStatic->fetch($ticket_static->fk_user_assign);
                        $ticket_static->fk_user_assign_string = $userStatic->firstname.' '.$userStatic->lastname;
                    }
                    $obj_ret[] = $this->_cleanObjectDatas($ticket_static);
                }
                $i++;
            }
        } else {
            throw new RestException(503, 'Error when retrieve ticket list');
        }
        if (! count($obj_ret)) {
            throw new RestException(404, 'No ticket found');
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
        if (! DolibarrApiAccess::$user->rights->ticket->write) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach ($request_data as $field => $value) {
            $this->ticket->$field = $value;
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
     * Create ticket object
     *
     * @param array $request_data   Request datas
     * @return int  ID of ticket
     *
     */
    public function postNewMessage($request_data = null)
    {
        $ticketstatic = new Ticket($this->db);
        if (! DolibarrApiAccess::$user->rights->ticket->write) {
            throw new RestException(401);
        }
        // Check mandatory fields
        $result = $this->_validateMessage($request_data);

        foreach ($request_data as $field => $value) {
            $this->ticket->$field = $value;
        }
        $ticketMessageText = $this->ticket->message;
        $result = $this->ticket->fetch('', '', $this->ticket->track_id);
        if (! $result) {
            throw new RestException(404, 'Ticket not found');
        }
        $this->ticket->message = $ticketMessageText;
        if (! $this->ticket->createTicketMessage(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->ticket->id;
    }

    /**
     * Update ticket
     *
     * @param int   $id             Id of ticket to update
     * @param array $request_data   Datas
     * @return int
     *
     */
    public function put($id, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->ticket->write) {
            throw new RestException(401);
        }

        $result = $this->ticket->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Ticket not found');
        }

        if (! DolibarrApi::_checkAccessToResource('ticket', $this->ticket->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($request_data as $field => $value) {
            $this->ticket->$field = $value;
        }

        if ($this->ticket->update($id, DolibarrApiAccess::$user)) {
            return $this->get($id);
        }

        return false;
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
        if (! DolibarrApiAccess::$user->rights->ticket->delete) {
            throw new RestException(401);
        }
        $result = $this->ticket->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Ticket not found');
        }

        if (! DolibarrApi::_checkAccessToResource('ticket', $this->ticket->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if (!$this->ticket->delete($id)) {
            throw new RestException(500);
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
     * @param   object  $object	Object to clean
     * @return	array	Array of cleaned object properties
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
            "statuts_short",
            "statuts"
        );
        foreach ($attr2clean as $toclean) {
            unset($object->$toclean);
        }

        // If object has lines, remove $db property
        if (isset($object->lines) && count($object->lines) > 0) {
            $nboflines = count($object->lines);
            for ($i=0; $i < $nboflines; $i++) {
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
