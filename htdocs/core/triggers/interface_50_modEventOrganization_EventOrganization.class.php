<?php
/* Copyright (C) 2005-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2021		Florian Henry		<florian.henry@scopen.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/triggers/interface_50_modEventOrganization_EventOrganization.class.php
 *  \ingroup    eventorganization
 *  \brief      Trigger file for Event Organization module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for agenda module
 */
class InterfaceEventOrganization extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "eventorganization";
		$this->description = "Triggers of this module to manage event organization triggers action";
		$this->version = self::VERSIONS['prod'];
		$this->picto = 'action';
	}

	/**
	 * Function called when a Dolibarr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact or array of ids of contacts)
	 *      $object->socid (id of thirdparty)
	 *      $object->fk_project
	 *      $object->fk_element	(ID of object to link action event to)
	 *      $object->elementtype (->element of object to link action to)
	 *      $object->module (if defined, elementtype in llx_actioncomm will be elementtype@module)
	 *
	 * @param string		$action		Event action code ('CONTRACT_MODIFY', 'RECRUITMENTCANDIDATURE_MODIFIY', ...)
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->eventorganization) || empty($conf->eventorganization->enabled)) {
			return 0; // Module not active, we do nothing
		}

		$error = 0;

		// Actions
		if ($action == 'PROJECT_VALIDATE') {
			if (getDolGlobalString('EVENTORGANIZATION_TASK_LABEL') && !empty($object->usage_organize_event)) {
				$taskToDo = explode("\n", getDolGlobalString('EVENTORGANIZATION_TASK_LABEL'));
				if (is_array($taskToDo) && count($taskToDo) > 0) {
					// Load translation files required by the page
					$langs->loadLangs(array("eventorganization"));

					$this->db->begin();
					foreach ($taskToDo as $taskLabel) {
						$task = new Task($this->db);
						$task->label = $taskLabel;
						$task->fk_project = $object->id;
						$defaultref = '';
						$classnamemodtask = getDolGlobalString('PROJECT_TASK_ADDON', 'mod_task_simple');
						if (getDolGlobalString('PROJECT_TASK_ADDON') && is_readable(DOL_DOCUMENT_ROOT . "/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON') . ".php")) {
							require_once DOL_DOCUMENT_ROOT . "/core/modules/project/task/" . getDolGlobalString('PROJECT_TASK_ADDON') . '.php';
							$modTask = new $classnamemodtask();
							'@phan-var-force ModeleNumRefTask $modTask';
							$defaultref = $modTask->getNextValue($object->thirdparty, $task);
						}
						if (is_numeric($defaultref) && $defaultref <= 0) {
							$defaultref = '';
						}
						$task->ref = $defaultref;

						// TODO Can set offset for start date or endline from setup of task to create when creating event
						$task->date_start = null;
						$task->date_end = null;

						$result = $task->create($user);
						if ($result < 0) {
							$this->errors = array_merge($this->errors, $task->errors);
							$error++;
						}
					}

					if (empty($error)) {
						$this->db->commit();
						return 1;
					} else {
						dol_syslog("InterfaceEventOrganization.class.php: ".implode(',', $this->errors), LOG_ERR);
						$this->db->rollback();
						return -1;
					}
				}
			}
		}
		return 0;
	}
}
