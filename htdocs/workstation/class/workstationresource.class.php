<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020 Gauthier VERDOL <gauthier.verdol@atm-consulting.fr>
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
 * \file        class/workstationresource.class.php
 * \ingroup     workstation
 * \brief       This file is a CRUD class file for WorkstationResource (Create/Read/Update/Delete)
 */


/**
 * Class to link resource with Workstations
 */
class WorkstationResource extends CommonObject
{
	/** @var string $table_element Table name in SQL */
	public $table_element = 'workstation_workstation_resource';

	/** @var string $element Name of the element (tip for better integration in Dolibarr: this value should be the reflection of the class name with ucfirst() function) */
	public $element = 'workstationresource';

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'fk_workstation' => array('type' => 'integer'),
		'fk_resource' => array('type' => 'integer')
	);

	/**
	 * @var int ID of workstation
	 */
	public $fk_workstation;

	/**
	 * @var int ID of dolresource
	 */
	public $fk_resource;


	/**
	 * WorkstationResource constructor.
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
	 * Function used to get an array with all resources linked to a workstation
	 *
	 * @param	int		$fk_workstation		Id of workstation we need to get linked resources
	 * @return 	array						Array of record
	 */
	public static function getAllResourcesOfWorkstation($fk_workstation)
	{
		global $db;
		$obj = new self($db);
		return parent::getAllItemsLinkedByObjectID($fk_workstation, 'fk_resource', 'fk_workstation', $obj->table_element);
	}

	/**
	 * Function used to remove all resources linked to a workstation
	 *
	 * @param	int		$fk_workstation		Id of workstation we need to remove linked resources
	 * @return 	int							Return integer <0 if KO, 0 if nothing done, >0 if OK and something done
	 */
	public static function deleteAllResourcesOfWorkstation($fk_workstation)
	{
		global $db;
		$obj = new self($db);
		return parent::deleteAllItemsLinkedByObjectID($fk_workstation, 'fk_workstation', $obj->table_element);
	}
}
