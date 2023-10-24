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
	private string 	$event_label;
	private string 	$event_desc;
	private int 	$event_date;

	/**
	 * Constructor
	 * @param	DoliDB	$db	Database handler
	 */
	public function __construct(DoliDB $db)
	{
		parent::__construct($db);

		$this->family 		= "core";
		$this->description  = "Triggers of this module allows to add security event records inside Dolibarr.";
		$this->version 		= self::VERSION_DOLIBARR;  // VERSION_ 'DEVELOPMENT' or 'EXPERIMENTAL' or 'DOLIBARR'
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
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf): int
	{
		if (!empty($conf->global->MAIN_LOGEVENTS_DISABLE_ALL)) {
			return 0; // Log events is disabled (hidden features)
		}

		$key = 'MAIN_LOGEVENTS_'.$action;
		if (empty($conf->global->$key)) {
			return 0; // Log events not enabled for this action
		}

		if (empty($conf->entity)) {
			$conf->entity = $entity; // forcing of the entity if it's not defined (ex: in login form)
		}

		// Actions
		switch ($action) {
			case 'USER_LOGIN':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("UserLogged", $object, $langs, true);
				break;
			case 'USER_LOGIN_FAILED':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("UserLoginFailed", $object, $langs, true);
				break;
			case 'USER_LOGOUT':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("UserLogoff", $object, $langs, true);
				break;
			case 'USER_CREATE':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("NewUserCreated", $object, $langs, true);
				break;
			case 'USER_MODIFY':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("EventUserModified", $object, $langs, true);
				break;
			case 'USER_NEW_PASSWORD':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("NewUserPassword", $object, $langs, true);
				break;
			case 'USER_ENABLEDISABLE': // TODO maybe divide this action into 2 separate actions for better traceability
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				if ($object->statut == 0) {
					$this->initEventData("UserEnabled", $object, $langs, true);
				}
				if ($object->statut == 1) {
					$this->initEventData("UserDisabled", $object, $langs, true);
				}
				break;
			case 'USER_DELETE':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("UserDeleted", $object, $langs, true);
				break;
			case 'USERGROUP_CREATE':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("NewGroupCreated", $object, $langs, false);
				break;
			case 'USERGROUP_MODIFY':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("GroupModified", $object, $langs, false);
				break;
			case 'USERGROUP_DELETE':
				dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				$this->initEventData("GroupDeleted", $object, $langs, false);
				break;
			default:
				dol_syslog("Unknown action. Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
				break;
		}

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
	 * @param	string		$key				Text lang string
	 * @param	Object		$object				Object
	 * @param	Translate	$langs				Object langs
	 * @param	bool		$user_else_group	Bool to define if localized string param is Object login or name
	 * @return	void
	 */
	private function initEventData(string $key, Object $object, Translate $langs, bool $user_else_group): void
	{
		$langs->load("users");
		$this->event_date = dol_now();
		if ($user_else_group) { // TODO maybe use enum instead of bool when Dolibarr minimum PHP version is 8.1
			$this->event_label = $langs->transnoentities($key, $object->login);
			$this->event_desc = $langs->transnoentities($key, $object->login);
		} else {
			$this->event_label = $langs->transnoentities($key, $object->name);
			$this->event_desc = $langs->transnoentities($key, $object->name);
		}
		// Add more information into event description from the context property
		if (!empty($object->context['audit'])) {
			$this->event_desc .= (empty($this->event_desc) ? '' : ' - ').$object->context['audit'];
		}
	}
}
