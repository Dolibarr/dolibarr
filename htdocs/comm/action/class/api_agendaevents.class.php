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

require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';


/**
 * API class for Agenda Events
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class AgendaEvents extends DolibarrApi
{

	/**
	 * @var array   $FIELDS     Mandatory fields, checked when create and update object
	 */
	static $FIELDS = array(
	);

	/**
	 * @var ActionComm $actioncomm {@type ActionComm}
	 */
	public $actioncomm;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->actioncomm = new ActionComm($this->db);
	}

	/**
	 * Get properties of a Agenda Events object
	 *
	 * Return an array with Agenda Events informations
	 *
	 * @param       int         $id         ID of Agenda Events
	 * @return 	    array|mixed             Data without useless information
	 *
	 * @throws 	RestException
	 */
	public function get($id)
	{
		if (!DolibarrApiAccess::$user->rights->agenda->myactions->read) {
			throw new RestException(401, "Insufficient rights to read an event");
		}
		if ($id === 0) {
			$result = $this->actioncomm->initAsSpecimen();
		} else {
			$result = $this->actioncomm->fetch($id);
			if ($result) {
				$this->actioncomm->fetch_optionals();
				$this->actioncomm->fetchObjectLinked();
			}
		}
		if (!$result) {
			throw new RestException(404, 'Agenda Events not found');
		}

		if (!DolibarrApiAccess::$user->rights->agenda->allactions->read && $this->actioncomm->userownerid != DolibarrApiAccess::$user->id) {
			throw new RestException(401, "Insufficient rights to read event for owner id ".$request_data['userownerid'].' Your id is '.DolibarrApiAccess::$user->id);
		}

		if (!DolibarrApi::_checkAccessToResource('agenda', $this->actioncomm->id, 'actioncomm', '', 'fk_soc', 'id')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		return $this->_cleanObjectDatas($this->actioncomm);
	}

	/**
	 * List Agenda Events
	 *
	 * Get a list of Agenda Events
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string   	$user_ids   User ids filter field (owners of event). Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
	 * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'%dol%') and (t.datec:<:'20160101')"
	 * @return  array               Array of Agenda Events objects
	 */
	public function index($sortfield = "t.id", $sortorder = 'ASC', $limit = 100, $page = 0, $user_ids = 0, $sqlfilters = '')
	{
		global $db, $conf;

		$obj_ret = array();

		if (!DolibarrApiAccess::$user->rights->agenda->myactions->read) {
			throw new RestException(401, "Insufficient rights to read events");
		}

		// case of external user
		$socid = 0;
		if (!empty(DolibarrApiAccess::$user->socid)) {
			$socid = DolibarrApiAccess::$user->socid;
		}

		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) {
			$search_sale = DolibarrApiAccess::$user->id;
		}
		if (empty($conf->societe->enabled)) {
			$search_sale = 0; // If module thirdparty not enabled, sale representative is something that does not exists
		}

		$sql = "SELECT t.id as rowid";
		if (!empty($conf->societe->enabled)) {
			if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
				$sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
			}
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as t";
		if (!empty($conf->societe->enabled)) {
			if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
			}
		}
		$sql .= ' WHERE t.entity IN ('.getEntity('agenda').')';
		if (!empty($conf->societe->enabled)) {
			if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socid) || $search_sale > 0) {
				$sql .= " AND t.fk_soc = sc.fk_soc";
			}
		}
		if ($user_ids) {
			$sql .= " AND t.fk_user_action IN (".$this->db->sanitize($user_ids).")";
		}
		if ($socid > 0) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}
		// Insert sale filter
		if ($search_sale > 0) {
			$sql .= " AND sc.fk_user = ".((int) $search_sale);
		}
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
				$actioncomm_static = new ActionComm($this->db);
				if ($actioncomm_static->fetch($obj->rowid)) {
					$obj_ret[] = $this->_cleanObjectDatas($actioncomm_static);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve Agenda Event list : '.$this->db->lasterror());
		}
		if (!count($obj_ret)) {
			throw new RestException(404, 'No Agenda Event found');
		}
		return $obj_ret;
	}

	/**
	 * Create Agenda Event object
	 *
	 * @param   array   $request_data   Request data
	 * @return  int                     ID of Agenda Event
	 */
	public function post($request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->agenda->myactions->create) {
			throw new RestException(401, "Insufficient rights to create your Agenda Event");
		}
		if (!DolibarrApiAccess::$user->rights->agenda->allactions->create && DolibarrApiAccess::$user->id != $request_data['userownerid']) {
			throw new RestException(401, "Insufficient rights to create an Agenda Event for owner id ".$request_data['userownerid'].' Your id is '.DolibarrApiAccess::$user->id);
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			$this->actioncomm->$field = $this->_checkValForAPI($field, $value, $this->actioncomm);
		}
		/*if (isset($request_data["lines"])) {
		  $lines = array();
		  foreach ($request_data["lines"] as $line) {
			array_push($lines, (object) $line);
		  }
		  $this->expensereport->lines = $lines;
		}*/

		if ($this->actioncomm->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating event", array_merge(array($this->actioncomm->error), $this->actioncomm->errors));
		}

		return $this->actioncomm->id;
	}


	/**
	 * Update Agenda Event general fields
	 *
	 * @param int   $id             Id of Agenda Event to update
	 * @param array $request_data   Datas
	 *
	 * @return int
	 */
	public function put($id, $request_data = null)
	{
		if (!DolibarrApiAccess::$user->rights->agenda->myactions->create) {
			throw new RestException(401, "Insufficient rights to create your Agenda Event");
		}
		if (!DolibarrApiAccess::$user->rights->agenda->allactions->create && DolibarrApiAccess::$user->id != $request_data['userownerid']) {
			throw new RestException(401, "Insufficient rights to create an Agenda Event for owner id ".$request_data['userownerid'].' Your id is '.DolibarrApiAccess::$user->id);
		}

		$result = $this->actioncomm->fetch($id);
		if ($result) {
			$this->actioncomm->fetch_optionals();
			$this->actioncomm->fetch_userassigned();
			$this->actioncomm->oldcopy = clone $this->actioncomm;
		}
		if (!$result) {
			throw new RestException(404, 'actioncomm not found');
		}

		if (!DolibarrApi::_checkAccessToResource('actioncomm', $this->actioncomm->id, 'actioncomm', '', 'fk_soc', 'id')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}

			$this->actioncomm->$field = $this->_checkValForAPI($field, $value, $this->actioncomm);
		}

		if ($this->actioncomm->update(DolibarrApiAccess::$user, 1) > 0) {
			return $this->get($id);
		}

		return false;
	}

	/**
	 * Delete Agenda Event
	 *
	 * @param   int     $id         Agenda Event ID
	 *
	 * @return  array
	 */
	public function delete($id)
	{
		if (!DolibarrApiAccess::$user->rights->agenda->myactions->delete) {
			throw new RestException(401, "Insufficient rights to delete your Agenda Event");
		}

		$result = $this->actioncomm->fetch($id);
		if ($result) {
			$this->actioncomm->fetch_optionals();
			$this->actioncomm->fetch_userassigned();
			$this->actioncomm->oldcopy = clone $this->actioncomm;
		}

		if (!DolibarrApiAccess::$user->rights->agenda->allactions->delete && DolibarrApiAccess::$user->id != $this->actioncomm->userownerid) {
			throw new RestException(401, "Insufficient rights to delete an Agenda Event of owner id ".$this->actioncomm->userownerid.' Your id is '.DolibarrApiAccess::$user->id);
		}

		if (!$result) {
			throw new RestException(404, 'Agenda Event not found');
		}

		if (!DolibarrApi::_checkAccessToResource('actioncomm', $this->actioncomm->id, 'actioncomm', '', 'fk_soc', 'id')) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if (!$this->actioncomm->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete Agenda Event : '.$this->actioncomm->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'Agenda Event deleted'
			)
		);
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
		$event = array();
		foreach (AgendaEvents::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, "$field field missing");
			}
			$event[$field] = $data[$field];
		}
		return $event;
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

		unset($object->note); // alreaydy into note_private
		unset($object->usermod);
		unset($object->libelle);
		unset($object->context);
		unset($object->canvas);
		unset($object->contact);
		unset($object->contact_id);
		unset($object->thirdparty);
		unset($object->user);
		unset($object->origin);
		unset($object->origin_id);
		unset($object->ref_ext);
		unset($object->statut);
		unset($object->state_code);
		unset($object->state_id);
		unset($object->state);
		unset($object->region);
		unset($object->region_code);
		unset($object->country);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->barcode_type);
		unset($object->barcode_type_code);
		unset($object->barcode_type_label);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->cond_reglement);
		unset($object->fk_delivery_address);
		unset($object->shipping_method_id);
		unset($object->fk_account);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->fk_incoterms);
		unset($object->label_incoterms);
		unset($object->location_incoterms);
		unset($object->name);
		unset($object->lastname);
		unset($object->firstname);
		unset($object->civility_id);
		unset($object->contact);
		unset($object->societe);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->region_id);
		unset($object->actions);
		unset($object->lines);
		unset($object->modelpdf);

		return $object;
	}
}
