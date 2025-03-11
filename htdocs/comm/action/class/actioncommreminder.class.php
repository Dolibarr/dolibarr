<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file        htdocs/comm/action/class/actioncommreminder.class.php
 * \ingroup     agenda
 * \brief       This file is a CRUD class file for ActionCommReminder (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class for ActionCommReminder
 */
class ActionCommReminder extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'actioncomm_reminder';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'actioncomm_reminder';

	/**
	 * @var string String with name of icon for actioncommreminder. Must be the part after the 'object_' into object_actioncommreminder.png
	 */
	public $picto = 'generic';

	const STATUS_TODO = 0;
	const STATUS_DONE = 1;
	const STATUS_ERROR = -1;


	/**
	 *  'type' if the field format.
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only. Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'position' is the sort order of field.
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'help' is a string visible as a tooltip on field
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *  'default' is a default value for creation (can still be replaced by the global setup of default values)
	 *  'showoncombobox' if field must be shown into the label of combobox
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-5,5>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'visible' => -1, 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'index' => 1, 'comment' => "Id",),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'visible' => 0, 'enabled' => 1, 'position' => 20, 'notnull' => 1, 'index' => 1,),
		'dateremind' => array('type' => 'datetime', 'label' => 'DateRemind', 'visible' => 1, 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'index' => 1,),
		'typeremind' => array('type' => 'varchar(32)', 'label' => 'TypeRemind', 'visible' => -1, 'enabled' => 1, 'position' => 55, 'notnull' => 1, 'comment' => "email, browser, sms",),
		'fk_user' => array('type' => 'integer', 'label' => 'User', 'visible' => -1, 'enabled' => 1, 'position' => 65, 'notnull' => 1, 'index' => 1,),
		'offsetvalue' => array('type' => 'integer', 'label' => 'OffsetValue', 'visible' => 1, 'enabled' => 1, 'position' => 56, 'notnull' => 1,),
		'offsetunit' => array('type' => 'varchar(1)', 'label' => 'OffsetUnit', 'visible' => 1, 'enabled' => 1, 'position' => 57, 'notnull' => 1, 'comment' => "y, m, d, w, h, i",),
		'status' => array('type' => 'integer', 'label' => 'Status', 'visible' => 1, 'enabled' => 1, 'position' => 58, 'notnull' => 1, 'default' => '0', 'index' => 0, 'arrayofkeyval' => array('0' => 'ToDo', '1' => 'Done')),
		'lasterror' => array('type' => 'varchar(128)', 'label' => 'LastError', 'visible' => -1, 'enabled' => 1, 'position' => 59, 'index' => 0),
		'fk_actioncomm' => array('type' => 'integer', 'label' => 'Project', 'visible' => 1, 'enabled' => 1, 'position' => 70, 'notnull' => 1, 'index' => 1,),
		'fk_email_template' => array('type' => 'integer', 'label' => 'EmailTemplate', 'visible' => 1, 'enabled' => 1, 'position' => 80, 'notnull' => 0),
	);

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var int date remind
	 */
	public $dateremind;

	/**
	 * @var string reminder type email, browser, sms
	 */
	public $typeremind;

	/**
	 * @var int User ID
	 */
	public $fk_user;

	/**
	 * @var int offset value
	 */
	public $offsetvalue;

	/**
	 * @var string y, m, d, w, h, i
	 */
	public $offsetunit;

	/**
	 * @var int Status
	 */
	public $status;

	/**
	 * @var string Last error message
	 */
	public $lasterror;

	/**
	 * @var int Project
	 */
	public $fk_actioncomm;

	/**
	 * @var int Template Mail
	 */
	public $fk_email_template;
	// END MODULEBUILDER PROPERTIES


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 0;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID')) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany')) {
			$this->fields['entity']['enabled'] = 0;
		}
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		return $this->createCommon($user, $notrigger);
	}


	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id   Id object
	 * @param string $ref  Ref
	 * @return int         Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		return $result;
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  int 	$notrigger 0=launch triggers after, 1=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user       User that deletes
	 * @param int 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}

	/**
	 *  Return label of the status of a reminder
	 *
	 *  @param  int     $mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param  int     $status         Id status
	 *  @param  int     $mode           0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string                  Label of status
	 */
	public static function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		$labelStatus = $langs->transnoentitiesnoconv('ToDo');
		if ($status == 1) {
			$labelStatus = $langs->transnoentitiesnoconv('Done');
		} elseif ($status == -1) {
			$labelStatus = $langs->transnoentitiesnoconv('Error');
		}

		$labelStatusShort = $langs->transnoentitiesnoconv('ToDo');
		if ($status == 1) {
			$labelStatus = $langs->transnoentitiesnoconv('Done');
		} elseif ($status == -1) {
			$labelStatus = $langs->transnoentitiesnoconv('Error');
		}

		$statusType = 'status5';
		if ($status == 1) {
			$statusType = 'status4';
		} elseif ($status == -1) {
			$statusType = 'status8';
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		return $this->initAsSpecimenCommon();
	}
}
