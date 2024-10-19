<?php
/* Copyright (C) 2024 Johnson
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modPreopportunity_PreopportunityTriggers.class.php
 * \ingroup preopportunity
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modPreopportunity_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for Preopportunity module
 */
class InterfacePreopportunityTriggers extends DolibarrTriggers
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
		$this->family = "demo";
		$this->description = "Preopportunity triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'preopportunity@preopportunity';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (!isModEnabled('preopportunity')) {
			return 0; // If module is not enabled, we do nothing
		}

		// Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action
		$permissiontoleadconversion = $user->hasRight('preopportunity', 'preopportunity', 'leadconversion');

		if($action == 'PROJECT_CREATE' && $permissiontoleadconversion) {
			dol_syslog("Trigger '".$this->name."' for action '".$action."' launched by ".__FILE__.". id=".$object->id);
			$backtopageforcancel = GETPOST('backtopageforcancel');
			if($backtopageforcancel) {
				$parsed_url = parse_url($backtopageforcancel);
				parse_str($parsed_url['query'], $params);

				if (isset($params['id']) && $params['id'] > 0) {
					$path = $parsed_url['path'];
					$filename = basename($path);
					if ($filename == 'preopportunity_card.php') {
						dol_include_once('/preopportunity/class/preopportunity.class.php');
						$objectpreopportunity = new PreOpportunity($this->db);

						$objectpreopportunity->fetch($params['id']);

						$objectpreopportunity->id = $params['id'];
						$objectpreopportunity->fk_project = $object->id;
						$objectpreopportunity->status = 1;

						if($objectpreopportunity->update($user) < 0) {
							return -1;
						}
					}
				}
			}
		}

		return 0;
	}
}
