<?php
/* Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2023		Udo Tamm			<dev@dolibit.de>
 * Copyright (C) 2023		William Mead		<william.mead@manchenumerique.fr>
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
 *  \file       htdocs/core/triggers/interface_20_all_Logevents.class.php
 *  \ingroup    core
 *  \brief      Trigger file for log events
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

/**
 *  Class of triggers for security audit events
 */
class InterfaceLogevents extends DolibarrTriggers
{
	// List of translation key to use for the description of each event.
	// TODO reduce this list of of events to use keep USER_CREATE, USER_MODIFY & USER_DELETE and use $user->context['audit'] = 'text to add' to complete message of event.
	const EVENT_ACTION_DICT = array(
		'USER_LOGIN' => 'UserLogged',
		'USER_LOGIN_FAILED' => 'UserLoginFailed',
		'USER_LOGOUT' => 'UserLogoff',
		'USER_CREATE' => 'NewUserCreated',
		'USER_MODIFY' => 'EventUserModified',
		'USER_NEW_PASSWORD' => 'UserPasswordChange',
		'USER_ENABLEDISABLE' => 'UserEnabledDisabled',
		'USER_DELETE' => 'UserDeleted',
		'USERGROUP_CREATE' => 'NewGroupCreated',
		'USERGROUP_MODIFY' => 'GroupModified',
		'USERGROUP_DELETE' => 'GroupDeleted'
	);
	/**
	 * @var string	Label
	 */
	private $event_label;
	/**
	 * @var string	Description
	 */
	private $event_desc;
	/**
	 * @var int		Date
	 */
	private $event_date;


	/**
	 * Constructor
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);

		$this->family 		= "core";
		$this->description  = "Triggers of this module allows to add security event records inside Dolibarr.";
		$this->version 		= self::VERSIONS['prod'];
		$this->picto 		= 'technic';
		$this->event_label 	= '';
		$this->event_desc 	= '';
		$this->event_date 	= 0;
	}

	/**
	 * Function called when a Dolibarr security audit event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param	string		$action	Event action code
	 * @param	Object		$object	Object
	 * @param	User		$user	Object user
	 * @param	Translate	$langs	Object langs
	 * @param	conf		$conf	Object conf
	 * @return	int					if KO: <0, if no trigger ran: 0, if OK: >0
	 * @throws	Exception			dol_syslog can throw Exceptions
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (getDolGlobalString('MAIN_LOGEVENTS_DISABLE_ALL')) {
			return 0; // Log events is disabled (hidden features)
		}

		$key = 'MAIN_LOGEVENTS_'.$action;
		if (empty($conf->global->$key)) {
			return 0; // Log events not enabled for this action
		}

		if (empty($conf->entity)) {
			global $entity;
			$conf->entity = $entity; // forcing of the entity if it's not defined (ex: in login form)
		}

		// Actions
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

		// Set the label of event from the action code and the object properties
		// Take the message code into EVENT_ACTION_DICT and complete with $object properties like $object->context['audit']
		$this->initEventData(InterfaceLogevents::EVENT_ACTION_DICT[$action], $object);

		// Add entry in event table
		include_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

		$event = new Events($this->db);
		$event->type = $action;
		$event->dateevent = $this->event_date;
		$event->label = $this->event_label;
		$event->description = $this->event_desc;
		$event->user_agent = (empty($_SERVER["HTTP_USER_AGENT"]) ? '' : $_SERVER["HTTP_USER_AGENT"]);
		$event->authentication_method = (empty($object->context['authentication_method']) ? '' : $object->context['authentication_method']);

		$result = $event->create($user);
		if ($result > 0) {
			return 1;
		} else {
			$error = "Failed to insert security event: ".$event->error;
			$this->errors[] = $error;
			$this->error = $error;

			dol_syslog(get_class($this).": ".$error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Method called by runTrigger to initialize date, label & description data for event
	 *
	 * @param	string		$key_text			Action string
	 * @param	Object		$object				Object
	 * @return	void
	 */
	private function initEventData($key_text, $object)
	{
		$this->event_date = dol_now();

		$this->event_label = $this->event_desc = $key_text;
		if (property_exists($object, 'login')) {
			$this->event_label .= ' : ' . $object->login;
		}
		if ($key_text == InterfaceLogevents::EVENT_ACTION_DICT['USER_ENABLEDISABLE']) { // TODO should be refactored using an object property for event data.
			$object->statut ? $this->event_desc .= ' - disabled' : $this->event_desc .= ' - enabled';
		}
		// Add more information into event description from the context property
		if (!empty($object->context['audit'])) {
			$this->event_desc .= (empty($this->event_desc) ? '' : ' - ').$object->context['audit'];
		}
	}

	/**
	 * Check if text contains an event action key. Used for dynamic localization on frontend events list.
	 *
	 * @param	string	$event_text		Input event text
	 * @return	bool					True if event text is a coded structured string
	 */
	public static function isEventActionTextKey($event_text)
	{
		foreach (InterfaceLogevents::EVENT_ACTION_DICT as $value) {
			if (str_contains($event_text, $value)) {
				return true;
			}
		}
		return false;
	}
}
