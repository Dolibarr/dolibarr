<?php
/*
/* Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
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

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';

/**
 * API for handling Object of table llx_eventorganization_conferenceorboothattendee
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class EventAttendees extends DolibarrApi
{
	/**
	 * @var string[]       Mandatory fields, checked when create and update object
	 */
	public static $FIELDS = array(
		'fk_project'
	);

	/**
	 * @var string[]       Mandatory fields which needs to be an integer, checked when create and update object
	 */
	public static $INTFIELDS = array(
		'fk_soc',
		'fk_actioncomm',
		'fk_project',
		'fk_invoice',
		'status'
	);

	/**
	 * @var ConferenceOrBoothAttendee {@type ConferenceOrBoothAttendee}
	 */
	public $event_attendees;

	/**
	 * @var string 	Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'eventorganization_conferenceorboothattendee';

	/**
	 * Constructor of the class
	 */
	public function __construct()
	{
		global $db;
		$this->db = $db;
		$this->event_attendees = new ConferenceOrBoothAttendee($this->db);
	}

	/**
	 * Delete an event attendee
	 *
	 * @param   int     $id         event attendee ID
	 * @return  array
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url	DELETE {id}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function deleteById($id)
	{
		$allowaccess = $this->_checkAccessRights('delete', 0);
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to Event attendees');
		}

		$result = $this->event_attendees->fetch($id, '');
		if (!$result) {
			throw new RestException(404, 'Event attendee with id '.$id.' not found');
		}

		if (!$this->event_attendees->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete event attendee : '.$this->event_attendees->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'event attendee deleted'
			)
		);
	}

	/**
	 * Delete an event attendee
	 *
	 * @param   string     $ref         event attendee ref
	 * @return  array
	 * @phan-return array<array<string,int|string>>
	 * @phpstan-return array<array<string,int|string>>
	 *
	 * @url	DELETE ref/{ref}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 * @throws RestException 500
	 */
	public function deleteByRef($ref)
	{
		$allowaccess = $this->_checkAccessRights('delete', 0);
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to Event attendees');
		}

		$result = $this->event_attendees->fetch(0, $ref);
		if (!$result) {
			throw new RestException(404, "Event attendee with ref ".$ref." not found");
		}

		if (!$this->event_attendees->delete(DolibarrApiAccess::$user)) {
			throw new RestException(500, 'Error when delete event attendee : '.$this->event_attendees->error);
		}

		return array(
			'success' => array(
				'code' => 200,
				'message' => 'event attendee deleted'
			)
		);
	}

	/**
	 * Get properties of a event attendee by id
	 *
	 * Return an array with event attendee information
	 *
	 * @param   int         $id		ID of event attendee
	 * @return  Object				Object with cleaned properties
	 * @phan-return		ConferenceOrBoothAttendee
	 * @phpstan-return	ConferenceOrBoothAttendee
	 *
	 * @url	GET {id}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getById($id)
	{
		return $this->_fetch($id, '');
	}

	/**
	 * Get properties of an event attendee by ref
	 *
	 * Return an array with order information
	 *
	 * @param       string		$ref		Ref of object
	 * @return      Object				    Object with cleaned properties
	 * @phan-return		ConferenceOrBoothAttendee
	 * @phpstan-return	ConferenceOrBoothAttendee
	 *
	 * @url GET    ref/{ref}
	 *
	 * @throws RestException 403
	 * @throws RestException 404
	 */
	public function getByRef($ref)
	{
		return $this->_fetch(0, $ref);
	}

	/**
	 * List Event attendees
	 *
	 * Get a list of Event attendees
	 *
	 * @param string	$sortfield			Sort field
	 * @param string	$sortorder			Sort order
	 * @param int		$limit				Limit for list
	 * @param int		$page				Page number
	 * @param string	$sqlfilters			Other criteria to filter answers separated by a comma. Syntax example "(t.status:=:1) and (t.email:=:'bad@example.com')"
	 * @param string	$properties			Restrict the data returned to these properties. Ignored if empty. Comma separated list of properties names
	 * @param bool		$pagination_data	If this parameter is set to true the response will include pagination data. Default value is false. Page starts from 0*
	 * @return  array						Array of order objects
	 * @phan-return ConferenceOrBoothAttendee[]|array{data:ConferenceOrBoothAttendee[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 * @phpstan-return ConferenceOrBoothAttendee[]|array{data:ConferenceOrBoothAttendee[],pagination:array{total:int,page:int,page_count:int,limit:int}}
	 *
	 * @url GET
	 *
	 * @throws RestException 403 Access denied
	 * @throws RestException 503 Error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '', $pagination_data = false)
	{
		// $allowaccess = $this->_checkAccessRights('read', 0);
		// if (!$allowaccess) {
		//		throw new RestException(403, 'denied read access to Event attendees');
		// }
		// access check delayed until we can do it for each row checking each fk_project
		// entity stolen from api_setup.class.php
		$entity = (int) DolibarrApiAccess::$user->entity;
		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."projet AS p ON t.fk_project = p.rowid";
		if (isModEnabled('multicompany')) {
			$sql .= ' WHERE p.entity = '.((int) $entity);
		} else {
			$sql .= ' WHERE 1 = 1';
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

		dol_syslog(get_class($this)."::index", LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			$onerowaccessgranted = false;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$event_attendees_static = new ConferenceOrBoothAttendee($this->db);
				if ($event_attendees_static->fetch($obj->rowid, '') > 0) {
					$rowallowaccess = $this->_checkAccessRights('read', $event_attendees_static->fk_project);
					if ($rowallowaccess) {
						$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($event_attendees_static), $properties);
						$onerowaccessgranted = $rowallowaccess;
					}
				}
				$i++;
			}
			if (($num > 0) && !$onerowaccessgranted) {
				throw new RestException(403, 'No access granted for even a single of the rows found');
			}
		} else {
			throw new RestException(503, 'Error when retrieve event attendee list : '.$this->db->lasterror());
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
	 * Create an event attendee
	 *
	 * Example: {"module":"adherent","type_template":"member","active": 1,"ref":"(SendingEmailOnAutoSubscription)","fk_user":0,"joinfiles": "0", ... }
	 * Required: {"ref":"myBestTemplate","topic":"myBestOffer","type_template":"propal_send"}
	 *
	 * @param   array   $request_data   Request data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url POST
	 *
	 * @return  int     ID of event attendee
	 *
	 * @throws	RestException 304
	 * @throws	RestException 403
	 * @throws	RestException 500
	 */
	public function post($request_data = null)
	{
		$allowaccess = $this->_checkAccessRights('write', 0);
		if (!$allowaccess) {
			throw new RestException(403, 'denied create access to Event attendees');
		}

		// Check mandatory fields
		$result = $this->_validate($request_data);

		foreach ($request_data as $field => $value) {
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->event_attendees->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->event_attendees->$field = $this->_checkValForAPI($field, $value, $this->event_attendees);
		}

		if ($this->event_attendees->create(DolibarrApiAccess::$user) < 0) {
			throw new RestException(500, "Error creating event attendee", array_merge(array($this->event_attendees->error), $this->event_attendees->errors));
		}

		return ((int) $this->event_attendees->id);
	}

	/**
	 * Update an event attendee
	 *
	 * Example: {"module":"adherent","type_template":"member","active": 1,"ref":"(SendingEmailOnAutoSubscription)","fk_user":0,"joinfiles": "0", ... }
	 * Required: {"ref":"myBestTemplate","topic":"myBestOffer","type_template":"propal_send"}
	 *
	 * @param	int		$id             Id of order to update
	 * @param	array	$request_data   Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url PUT {id}
	 *
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException 403
	 * @throws	RestException 404
	 * @throws	RestException 500
	 */
	public function putById($id, $request_data = null)
	{
		$allowaccess = $this->_checkAccessRights('write', 0);
		if (!$allowaccess) {
			throw new RestException(403, 'denied update access to Event attendees');
		}

		$result = $this->event_attendees->fetch($id, '');
		if (!$result) {
			throw new RestException(404, 'event attendee not found');
		}

		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->event_attendees->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->event_attendees->$field = $this->_checkValForAPI($field, $value, $this->event_attendees);
		}

		if ($this->event_attendees->update(DolibarrApiAccess::$user) > 0) {
			return $this->_fetch($id, '');
		} else {
			throw new RestException(500, end($this->event_attendees->errors));
		}
	}

	/**
	 * Update an event attendee
	 *
	 * Example: {"module":"adherent","type_template":"member","active": 1,"ref":"(SendingEmailOnAutoSubscription)","fk_user":0,"joinfiles": "0", ... }
	 * Required: {"ref":"myBestTemplate","topic":"myBestOffer","type_template":"propal_send"}
	 *
	 * @param	string	$ref			Ref of order to update
	 * @param	array	$request_data	Data
	 * @phan-param ?array<string,string> $request_data
	 * @phpstan-param ?array<string,string> $request_data
	 *
	 * @url PUT ref/{ref}
	 *
	 * @return	Object					Object with cleaned properties
	 *
	 * @throws	RestException 403
	 * @throws	RestException 404
	 * @throws	RestException 500
	 */
	public function putByRef($ref, $request_data = null)
	{
		$allowaccess = $this->_checkAccessRights('write', 0);
		if (!$allowaccess) {
			throw new RestException(403, 'denied update access to Event attendees');
		}

		$result = $this->event_attendees->fetch(0, $ref);
		if (!$result) {
			throw new RestException(404, 'event attendee not found');
		}

		$newref = $ref;
		foreach ($request_data as $field => $value) {
			if ($field == 'id') {
				continue;
			}
			if ($field == 'ref') {
				$newref = $this->_checkValForAPI($field, $value, $this->event_attendees);
			}
			if ($field === 'caller') {
				// Add a mention of caller so on trigger called after action, we can filter to avoid a loop if we try to sync back again with the caller
				$this->event_attendees->context['caller'] = sanitizeVal($request_data['caller'], 'aZ09');
				continue;
			}

			$this->event_attendees->$field = $this->_checkValForAPI($field, $value, $this->event_attendees);
		}

		if ($this->event_attendees->update(DolibarrApiAccess::$user) > 0) {
			return $this->_fetch(0, $newref);
		} else {
			throw new RestException(500, end($this->event_attendees->errors));
		}
	}

	/**
	 * Get properties of an event attendee
	 *
	 * Return an array with Event attendees
	 *
	 * @param   int         $id             ID of event_attendees
	 * @param	string		$ref			Ref of event_attendees
	 * @return  Object						Object with cleaned properties
	 * @phan-return		ConferenceOrBoothAttendee
	 * @phpstan-return	ConferenceOrBoothAttendee
	 *
	 * @throws	RestException 403
	 * @throws	RestException 404
	 */
	private function _fetch($id, $ref = '')
	{
		// we first need to fetch the object so we can get the fk_project id and then check for access
		$result = $this->event_attendees->fetch($id, $ref);
		if (!$result) {
			if ($id) {
				throw new RestException(404, 'Event attendee with id '.((string) $id).' not found');
			}
			if ($ref) {
				throw new RestException(404, 'Event attendee with ref '.$ref.' not found');
			}
			throw new RestException(404, 'Event attendee not found');
		}
		$project_id = $this->event_attendees->fk_project;
		$allowaccess = $this->_checkAccessRights('read', $project_id);
		if (!$allowaccess) {
			throw new RestException(403, 'denied read access to Event attendees');
		}

		return $this->_cleanObjectDatas($this->event_attendees);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Clean sensible object datas
	 *
	 * @param   Object  $object     	Object to clean
	 * @phan-param		ConferenceOrBoothAttendee	$object
	 * @phpstan-param	ConferenceOrBoothAttendee	$object
	 *
	 * @return  Object	Object with cleaned properties
	 * @phan-return		ConferenceOrBoothAttendee
	 * @phpstan-return	ConferenceOrBoothAttendee
	 */
	protected function _cleanObjectDatas($object)
	{
		// phpcs:enable
		$object = parent::_cleanObjectDatas($object);

		unset($object->array_languages);
		unset($object->contacts_ids);
		unset($object->canvas);
		unset($object->contact_id);
		unset($object->user);
		unset($object->origin_type);
		unset($object->origin_id);
		unset($object->ref_ext);
		unset($object->statut);
		unset($object->civility_code);
		unset($object->country_id);
		unset($object->country_code);
		unset($object->state_id);
		unset($object->region_id);
		unset($object->barcode_type);
		unset($object->barcode_type_coder);
		unset($object->mode_reglement_id);
		unset($object->cond_reglement_id);
		unset($object->demand_reason_id);
		unset($object->transport_mode_id);
		unset($object->shipping_method_id);
		unset($object->shipping_method);
		unset($object->fk_multicurrency);
		unset($object->multicurrency_code);
		unset($object->multicurrency_tx);
		unset($object->multicurrency_total_ht);
		unset($object->multicurrency_total_tva);
		unset($object->multicurrency_total_ttc);
		unset($object->multicurrency_total_localtax1);
		unset($object->multicurrency_total_localtax2);
		unset($object->fk_account);
		unset($object->total_ht);
		unset($object->total_tva);
		unset($object->total_localtax1);
		unset($object->total_localtax2);
		unset($object->total_ttc);
		unset($object->lines);
		unset($object->actiontypecode);
		unset($object->name);
		unset($object->civility_id);
		unset($object->user_author);
		unset($object->user_creation);
		unset($object->user_creation_id);
		unset($object->user_valid);
		unset($object->user_validation);
		unset($object->user_validation_id);
		unset($object->user_closing_id);
		unset($object->user_modification);
		unset($object->user_modification_id);
		unset($object->totalpaid);
		unset($object->product);
		unset($object->cond_reglement_supplier_id);
		unset($object->deposit_percent);
		unset($object->retained_warranty_fk_cond_reglement);
		unset($object->warehouse_id);
		unset($object->target);
		unset($object->extraparams);
		unset($object->specimen);
		unset($object->date_validation);
		unset($object->date_modification);
		unset($object->date_cloture);
		unset($object->rowid);
		unset($object->module);
		unset($object->entity);
		unset($object->paid);

		return $object;
	}

	/**
	 * Validate fields before create or update object
	 *
	 * @param ?array<string,null|int|string>	$data   Data to validate
	 * @return array<string,null|int|string>			Return array with validated mandatory fields and their value
	 * @phan-return array<string,?int|?string>			Return array with validated mandatory fields and their value
	 *
	 * @throws  RestException 400
	 */
	private function _validate($data)
	{
		$event_attendees = array();
		foreach (EventAttendees::$FIELDS as $field) {
			if (!isset($data[$field])) {
				throw new RestException(400, $field." field missing");
			}
			$event_attendees[$field] = $data[$field];
		}
		return $event_attendees;
	}

	/**
	 * function to check for access rights - should probably have 1. parameter which is read/write/delete/...
	 * Why a separate function? because we probably needs to check so many many different kinds of objects
	 *
	 * @param	string		$accesstype		accesstype: read, write, delete, ...
	 * @param	int			$project_id		which project do we need to check for access to, 0 means don't check
	 * @return 	bool     					Return true if access is granted else false
	 *
	 * @throws  RestException 403
	 * @throws  RestException 500
	 */
	private function _checkAccessRights($accesstype, $project_id = 0)
	{
		// what kind of access management do we need?
		$moduleaccess = false;
		if (isModEnabled("eventorganization") && DolibarrApiAccess::$user->hasRight('eventorganization', $accesstype)) {
			$moduleaccess = true;
		}
		$fullprojectaccess = false;
		if (DolibarrApiAccess::$user->hasRight('projet', 'all', $accesstype)) {
			$fullprojectaccess = true;
		}

		if ($moduleaccess && $fullprojectaccess) {
			return true;
		} else {
			$singleprojectaccess = false;
			if (0 < $project_id) {
				// we should also check project visibility and if set to assigned contacts it should be only those contacts.
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$event_project = new Project($this->db);
				$result = $event_project->fetch($project_id);
				if (0 < $result) {
					$public = $event_project->public;
					if ( 1 == $public) {
						$singleprojectaccess = true;
					} else {
						$userProjectAccessListId = $event_project->getProjectsAuthorizedForUser(DolibarrApiAccess::$user, 0, 0);
						$project_title = $event_project->title;
						if (in_array($project_title, $userProjectAccessListId)) {
							$singleprojectaccess = true;
						} else {
							dol_syslog("project_title ".$project_title." is NOT in array from getProjectsAuthorizedForUser()", LOG_DEBUG);
							return false;
						}
					}
				} elseif (0 == $result) {
					throw new RestException(500, 'Project id '.$project_id.' not found');
				} else {
					throw new RestException(500, 'Error during fetch project '.$project_id.': '.$this->db->lasterror());
				}
			} elseif ($moduleaccess && ($project_id == 0)) {
				return true;
				// because we assume that the caller will know to check for each fk_projekt
			}
			if ($moduleaccess && $singleprojectaccess) {
				return true;
			} elseif ($moduleaccess) {
				throw new RestException(403, 'Event attendees access granted, but denied access to the project');
			} elseif ($singleprojectaccess) {
				throw new RestException(403, 'project access granted, but denied access to Event attendees');
			} else {
				throw new RestException(403, 'denied access both Event attendees and the project');
			}
		}
	}
}
