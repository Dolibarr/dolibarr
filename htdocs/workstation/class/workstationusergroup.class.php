<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

/**
 * \file        htdocs/workstation/class/workstationusergroup.class.php
 * \ingroup     workstation
 * \brief       This file is a CRUD class file for WorkstationUserGroup (Create/Read/Update/Delete)
 */

/**
 * Class to link User groups and Workstations
 */
class WorkstationUserGroup extends CommonObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'workstation_workstation_usergroup';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'workstationusergroup';

	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'fk_workstation' => array('type' => 'integer', 'label' => 'Workstation', 'enabled' => 1, 'position' => 10, 'visible' => 1),
		'fk_usergroup' => array('type' => 'integer', 'label' => 'UserGroup', 'enabled' => 1, 'position' => 20, 'visible' => 1),
	);

	/**
	 * @var int ID of workstation
	 */
	public $fk_workstation;

	/**
	 * @var int ID of user group
	 */
	public $fk_usergroup;


	/**
	 * WorkstationUserGroup constructor.
	 *
	 * @param DoliDB    $db    Database connector
	 */
	public function __construct($db)
	{
		global $langs;

		$this->db = $db;

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Function used to get an array with all usergroups linked to a workstation
	 *
	 * @param	int		$fk_workstation		id of workstation we need to get linked usergroups
	 * @return 	array						Array of record
	 */
	public static function getAllGroupsOfWorkstation($fk_workstation)
	{
		global $db;

		$obj = new self($db);
		return parent::getAllItemsLinkedByObjectID($fk_workstation, 'fk_usergroup', 'fk_workstation', $obj->table_element);
	}

	/**
	 * Function used to remove all usergroups linked to a workstation
	 *
	 * @param	int		$fk_workstation		Id of workstation we need to remove linked usergroups
	 * @return 	int							Return integer <0 if KO, 0 if nothing done, >0 if OK and something done
	 */
	public static function deleteAllGroupsOfWorkstation($fk_workstation)
	{
		global $db;

		$obj = new self($db);
		return parent::deleteAllItemsLinkedByObjectID($fk_workstation, 'fk_workstation', $obj->table_element);
	}
}
