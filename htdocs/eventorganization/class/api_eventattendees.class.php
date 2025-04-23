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
