<?php
/* Copyright (C) 2017       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024  Frédéric France          <frederic.france@free.fr>
 * Copyright (C) 2024		MDW                      <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024 Maxime Kohlhaas <maxime@atm-consulting.fr>
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
 * \file        class/holiday.class.php
 * \ingroup     holiday
 * \brief       This file is a CRUD class file for Holiday (Create/Read/Update/Delete)
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for Holiday
 */
class Holiday extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'holiday';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'holiday';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'holiday';

	/**
	 * @var string 	If permission must be checkec with hasRight('holiday', 'read') and not hasright('mymodyle', 'holiday', 'read'), you can uncomment this line
	 */
	public $element_for_permission = 'holiday';

	/**
	 * @var string String with name of icon for holiday. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'holiday@holiday' if picto is file 'img/object_holiday.png'.
	 */
	public $picto = 'holiday';

	const STATUS_DRAFT = 1;
	const STATUS_VALIDATED = 2;
	const STATUS_APPROVED = 3;
	const STATUS_CANCELED = 4;
	const STATUS_REFUSED = 5;

	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'. for integer list of values are in 'arrayofkeyval'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:SortField]]]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price', 'stock',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'alias' the alias used into some old hard coded SQL requests
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'				=> array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'noteditable' => 1, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id', 'css' => 'left'),
		'ref'				=> array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'noteditable' => 0, 'default' => '', 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1, 'comment' => 'Reference of object', 'validate' => 1),
		'fk_user'			=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Employee', 'enabled' => 1, 'position' => 30, 'notnull' => 1, 'visible' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax125',),
		'fk_validator'		=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'ValidatorCP', 'enabled' => 1, 'position' => 31, 'notnull' => 1, 'visible' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax125',),
		'fk_user_create'	=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'position' => 35, 'notnull' => 0, 'visible' => -1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150',),
		'fk_user_modif'		=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 40, 'notnull' => -1, 'visible' => -2, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150',),
		'fk_type'			=> array('type' => 'integer', 'label' => 'Type', 'enabled' => 1, 'position' => 45, 'notnull' => 1, 'visible' => 1, 'css' => 'maxwidth500 widthcentpercentminusxx',),
		'date_create'		=> array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 50),
		'description'		=> array('type' => 'text', 'label' => 'Description', 'enabled' => 1, 'visible' => 3, 'position' => 55, 'validate' => 1),
		'date_debut'		=> array('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 1,),
		'date_fin'			=> array('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'position' => 65, 'notnull' => 1, 'visible' => 1,),
		'halfday'			=> array('type' => 'integer', 'label' => 'Halfday', 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => 0,),
		'nb_open_day'		=> array('type' => 'double(24,8)', 'label' => 'NbUseDaysCPShort', 'enabled' => 1, 'position' => 75, 'notnull' => 0, 'visible' => 1, 'isameasure' => 1,),
		'statut'			=> array('type' => 'integer', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'default' => 0, 'index' => 1, 'position' => 2000, 'csslist' => 'center', 'arrayofkeyval' => array(1 => 'DraftCP', 2 => 'ToReviewCP', 3 => 'ApprovedCP', 4 => 'CancelCP', 5 => 'RefuseCP'), 'validate' => 1),
		'date_valid'		=> array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'position' => 85, 'notnull' => 0, 'visible' => 1,),
		'fk_user_valid'		=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'position' => 86, 'notnull' => 0, 'visible' => -1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150',),
		'date_approval'		=> array('type' => 'datetime', 'label' => 'DateApprove', 'enabled' => 1, 'position' => 90, 'notnull' => 0, 'visible' => 1,),
		'fk_user_approve'	=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fkuserapprove', 'enabled' => 1, 'position' => 91, 'notnull' => 0, 'visible' => -1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150',),
		'date_refuse'		=> array('type' => 'datetime', 'label' => 'Daterefuse', 'enabled' => 1, 'position' => 95, 'notnull' => 0, 'visible' => -1,),
		'fk_user_refuse'	=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fkuserrefuse', 'enabled' => 1, 'position' => 96, 'notnull' => 0, 'visible' => -1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150',),
		'date_cancel'		=> array('type' => 'datetime', 'label' => 'Datecancel', 'enabled' => 1, 'position' => 105, 'notnull' => 0, 'visible' => -1,),
		'fk_user_cancel'	=> array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Fkusercancel', 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => -1, 'css' => 'maxwidth500 widthcentpercentminusxx', 'csslist' => 'tdoverflowmax150',),
		'detail_refuse'		=> array('type' => 'varchar(250)', 'label' => 'DetailRefuse', 'enabled' => 1, 'position' => 115, 'notnull' => 0, 'visible' => -1,),
		'note_public'		=> array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 56, 'validate' => 1, 'cssview' => 'wordbreak'),
		'note_private'		=> array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 57, 'validate' => 1, 'cssview' => 'wordbreak'),
		'tms'				=> array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'notnull' => 0, 'position' => 51),
		'import_key'		=> array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'index' => 0, 'position' => 1000),
	);
	public $rowid;
	public $ref;
	public $fk_user;
	public $fk_user_create;
	public $fk_user_modif;
	public $fk_type;
	public $date_create;
	public $description;
	public $date_debut;
	public $date_fin;
	public $halfday;
	public $statut;
	public $fk_validator;
	public $date_valid;
	public $fk_user_valid;
	public $date_refuse;
	public $fk_user_refuse;
	public $date_cancel;
	public $fk_user_cancel;
	public $detail_refuse;
	public $note_private;
	public $note_public;
	public $tms;
	public $import_key;
	public $date_approval;
	public $fk_user_approve;
	public $nb_open_day;
	// END MODULEBUILDER PROPERTIES

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;
		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

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

		$this->fields['fk_type']['arrayofkeyval'] = array_column($this->getTypes(1, -1), 'label');
		//var_dump($this->fields['fk_type']['arrayofkeyval']);
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
		$resultcreate = $this->createCommon($user, $notrigger);

		return $resultcreate;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param  	User 	$user      	User that creates
	 * @param  	int 	$fromid     Id of object to clone
	 * @return 	mixed 				New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_create);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_".$object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf")." ".$object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'statut')) {
			$object->statut = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key);
					//var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->setErrorsFromObject($object);
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	int    	$id   			Id object
	 * @param 	string 	$ref  			Ref
	 * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int		$nolines		0=Default to load extrafields, 1=No extrafields
	 * @return 	int     				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$res = $this->fetchCommon($id, $ref, '', $noextrafields);
		if ($res > 0) {
			// Needed to ensure right calculation of nb_open_days
			$this->date_debut_gmt = $this->db->jdate($this->db->idate($this->date_debut), 1);
			$this->date_fin_gmt = $this->db->jdate($this->db->idate($this->date_fin), 1);

			$this->status = $this->statut;
		}
		return $res;
	}


	/**
	 * Load list of objects in memory from the database.
	 * Using a fetchAll() with limit = 0 is a very bad practice. Instead try to forge yourself an optimized SQL request with
	 * your own loop with start and stop pagination.
	 *
	 * @param  string      	$sortorder    	Sort Order
	 * @param  string      	$sortfield    	Sort field
	 * @param  int         	$limit        	Limit the number of lines returned
	 * @param  int         	$offset       	Offset
	 * @param  string		$filter       	Filter as an Universal Search string.
	 * 										Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  string      	$filtermode   	No more used
	 * @return array|int                 	int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 1000, $offset = 0, string $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (isset($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				if (!empty($record->isextrafieldmanaged)) {
					$record->fetch_optionals();
				}

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
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
		$checkBalance = getDictionaryValue('c_holiday_types', 'block_if_negative', $this->fk_type);

		if ($checkBalance > 0 && $this->status != self::STATUS_DRAFT) {
			$balance = $this->getCPforUser($this->fk_user, $this->fk_type);

			if ($balance < 0) {
				$this->error = 'LeaveRequestCreationBlockedBecauseBalanceIsNegative';
				return -1;
			}
		}

		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user       	User that deletes
	 * @param int 	$notrigger  0=launch triggers, 1=disable triggers
	 * @return int             	Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}


	/**
	 *	Validate leave request
	 *
	 *  @param	User	$user        	User that validate
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function validate($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$error = 0;

		$checkBalance = getDictionaryValue('c_holiday_types', 'block_if_negative', $this->fk_type);

		if ($checkBalance > 0) {
			$balance = $this->getCPforUser($this->fk_user, $this->fk_type);

			if ($balance < 0) {
				$this->error = 'LeaveRequestCreationBlockedBecauseBalanceIsNegative';
				return -1;
			}
		}

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref) || $this->ref == $this->id)) {
			$num = $this->getNextNumRef(null);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		// Update status
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";
		$sql .= " fk_user_valid = ".((int) $user->id).",";
		$sql .= " date_valid = '".$this->db->idate(dol_now())."',";
		if (!empty($this->status) && is_numeric($this->status)) {
			$sql .= " statut = ".((int) $this->status).",";
		} else {
			$this->error = 'Property status must be a numeric value';
			$error++;
		}
		$sql .= " ref = '".$this->db->escape($num)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAY_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'holiday/" . $this->db->escape($this->newref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'holiday/" . $this->db->escape($this->ref) . "' and entity = " . ((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'holiday/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'holiday/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->holiday->multidir_output[$this->entity] . '/' . $oldref;
				$dirdest = $conf->holiday->multidir_output[$this->entity] . '/' . $newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate rename dir " . $dirsource . " into " . $dirdest);
					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($dirdest, 'files', 1, '^' . preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
							$dirsource = $fileentry['path'] . '/' . $dirsource;
							$dirdest = $fileentry['path'] . '/' . $dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}


		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::validate ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Approve leave request
	 *
	 *  @param	User	$user        	User that approve
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function approve($user = null, $notrigger = 0)
	{
		$error = 0;

		$checkBalance = getDictionaryValue('c_holiday_types', 'block_if_negative', $this->fk_type);

		if ($checkBalance > 0) {
			$balance = $this->getCPforUser($this->fk_user, $this->fk_type);

			if ($balance < 0) {
				$this->error = 'LeaveRequestCreationBlockedBecauseBalanceIsNegative';
				return -1;
			}
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";
		$sql .= " description= '".$this->db->escape($this->description)."',";
		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".((int) $this->halfday).",";
		if (!empty($this->status) && is_numeric($this->status)) {
			$sql .= " statut = ".((int) $this->status).",";
		} else {
			$error++;
		}
		if (!empty($this->fk_validator)) {
			$sql .= " fk_validator = ".((int) $this->fk_validator).",";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = ".((int) $this->fk_user_valid).",";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_approval)) {
			$sql .= " date_approval = '".$this->db->idate($this->date_approval)."',";
		} else {
			$sql .= " date_approval = NULL,";
		}
		if (!empty($this->fk_user_approve)) {
			$sql .= " fk_user_approve = ".((int) $this->fk_user_approve).",";
		} else {
			$sql .= " fk_user_approve = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = ".((int) $this->fk_user_refuse).",";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = ".((int) $this->fk_user_cancel).",";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::approve", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAY_APPROVE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::approve ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->statut <= self::STATUS_DRAFT) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'HOLIDAY_UNVALIDATE');
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->statut != self::STATUS_VALIDATED) {
			return 0;
		}

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'HOLIDAY_CANCEL');
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param 	array 	$params 	Params to construct tooltip data
	 * @since 	v18
	 * @return 	array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowHoliday")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Holiday").'</u>';
		if (isset($this->statut)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		if (property_exists($this, 'ref')) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (isset($this->halfday) && !empty($this->date_debut) && !empty($this->date_fin)) {
			$listhalfday = array(
				'morning' => "Morning",
				"afternoon" => "Afternoon"
			);
			$starthalfday = ($this->halfday == -1 || $this->halfday == 2) ? 'afternoon' : 'morning';
			$endhalfday = ($this->halfday == 1 || $this->halfday == 2) ? 'morning' : 'afternoon';
			$datas['date_start'] = '<br><b>'.$langs->trans('DateDebCP') . '</b>: '. dol_print_date($this->date_debut, 'day') . '&nbsp;&nbsp;<span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
			$datas['date_end'] = '<br><b>'.$langs->trans('DateFinCP') . '</b>: '. dol_print_date($this->date_fin, 'day') . '&nbsp;&nbsp;<span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
		}

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = dol_buildpath('/holiday/card.php', 1).'?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowHoliday");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				[$class, $module] = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity]."/$class/".dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class.'/'.$this->ref.'/thumbs/'.substr($filename, 0, $pospoint).'_mini'.substr($filename, $pospoint);
					if (!getDolGlobalString(strtoupper($module.'_'.$class).'_FORMATLISTPHOTOSASUSERS')) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo'.$module.'" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$module.'&entity='.$conf->entity.'&file='.urlencode($pathtophoto).'"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array($this->element.'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP');
			$this->labelStatus[self::STATUS_APPROVED] = $langs->transnoentitiesnoconv('ApprovedCP');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
			$this->labelStatus[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP');
			$this->labelStatusShort[self::STATUS_APPROVED] = $langs->transnoentitiesnoconv('ApprovedCP');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
			$this->labelStatusShort[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');
		}

		$statusType = 'status6';
		if (!empty($startdate) && $startdate >= dol_now()) {		// If not yet passed, we use a green "in live" color
			$statusType = 'status4';
			$params = array('tooltip' => $this->labelStatus[$status].' - '.$langs->trans("Forthcoming"));
		}
		if ($status == self::STATUS_DRAFT) {
			$statusType = 'status0';
		}
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status1';
		}
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
		}
		if ($status == self::STATUS_REFUSED) {
			$statusType = 'status9';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param  int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid, f.statut as status,";
		$sql .= " f.date_create as datec,";
		$sql .= " f.tms as date_modification,";
		$sql .= " f.date_valid as datev,";
		$sql .= " f.date_approval as datea,";
		$sql .= " f.date_refuse as dater,";
		$sql .= " f.fk_user_create as fk_user_creation,";
		$sql .= " f.fk_user_modif as fk_user_modification,";
		$sql .= " f.fk_user_valid as fk_user_validation,";
		$sql .= " f.fk_user_approve as fk_user_approval_done,";
		$sql .= " f.fk_validator as fk_user_approval_expected,";
		$sql .= " f.fk_user_refuse as fk_user_refuse";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as f";
		$sql .= " WHERE f.rowid = ".((int) $id);
		$sql .= " AND f.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->date_validation = $this->db->jdate($obj->datev);
				$this->date_approval = $this->db->jdate($obj->datea);

				$this->user_creation_id = $obj->fk_user_creation;
				$this->user_validation_id = $obj->fk_user_validation;
				$this->user_modification_id = $obj->fk_user_modification;

				if ($obj->status == Holiday::STATUS_APPROVED || $obj->status == Holiday::STATUS_CANCELED) {
					if ($obj->fk_user_approval_done) {
						$this->user_approve_id = $obj->fk_user_approval_done;
					}
				}
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		global $user;

		$this->fk_user = $user->id;
		$this->date_debut = dol_now();
		$this->date_fin = dol_now() + (24 * 3600);
		$this->date_valid = dol_now();
		$this->fk_validator = $user->id;
		$this->halfday = 0;
		$this->fk_type = 1;
		$this->status = Holiday::STATUS_VALIDATED;

		return $this->initAsSpecimenCommon();
	}

	/**
	 *  Returns the reference to the following non used Order depending on the active numbering module
	 *  defined into HOLIDAY_ADDON
	 *
	 *	@param	Societe		$objsoc     third party object
	 *  @return string      			Holiday free reference
	 */
	public function getNextNumRef($objsoc)
	{
		global $langs, $conf;
		$langs->load("order");

		if (!getDolGlobalString('HOLIDAY_ADDON')) {
			$conf->global->HOLIDAY_ADDON = 'mod_holiday_madonna';
		}

		if (getDolGlobalString('HOLIDAY_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('HOLIDAY_ADDON') . ".php";
			$classname = getDolGlobalString('HOLIDAY_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/holiday/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if ($mybool === false) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($objsoc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_HOLIDAY_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * Return validation test result for a field.
	 * Need MAIN_ACTIVATE_VALIDATION_RESULT to be called.
	 *
	 * @param  array   $fields	       		Array of properties of field to show
	 * @param  string  $fieldKey            Key of attribute
	 * @param  string  $fieldValue          value of attribute
	 * @return bool 						Return false if fail, true on success, set $this->error for error message
	 */
	public function validateField($fields, $fieldKey, $fieldValue)
	{
		// Add your own validation rules here.
		// ...

		return parent::validateField($fields, $fieldKey, $fieldValue);
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		//global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlogfile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__." start", LOG_INFO);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		dol_syslog(__METHOD__." end", LOG_INFO);

		return $error;
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$val	Array of properties of field to show
	 * @param  string  	$key            	Key of attribute
	 * @param  string  	$value          	Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  	$moreparam      	To add more parameters on html tag
	 * @param  string  	$keysuffix      	Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  	$keyprefix      	Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   	$morecss        	Value for CSS to use (Old usage: May also be a numeric to define a size).
	 * @return string
	 */
	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs, $form;

		if ($key == 'date_debut') {
			$listhalfday = array('morning' => $langs->trans("Morning"), "afternoon" => $langs->trans("Afternoon"));
			$starthalfday = ($this->halfday == -1 || $this->halfday == 2) ? 'afternoon' : 'morning';
			$moreOut = ' <span class="opacitymedium nowraponall">('.$langs->trans($listhalfday[$starthalfday]).')</span>';
			return parent::showOutputField($val, $key, $value, $moreparam, $keyprefix, $morecss) . $moreOut;
		}

		if ($key == 'date_fin') {
			$listhalfday = array('morning' => "Morning", "afternoon" => "Afternoon");
			$endhalfday = ($this->halfday == 1 || $this->halfday == 2) ? 'morning' : 'afternoon';
			$moreOut = ' <span class="opacitymedium nowraponall">('.$langs->trans($listhalfday[$endhalfday]).')</span>';
			return parent::showOutputField($val, $key, $value, $moreparam, $keyprefix, $morecss) . $moreOut;
		}

		if ($key == 'date_valid') {
			$val['type'] = 'date'; // Force to show only date instead of datetime
		}

		if ($key == 'date_approval') {
			$val['type'] = 'date'; // Force to show only date instead of datetime
		}

		if ($key == 'nb_open_day') {
			$value = $this->nb_open_day = num_open_day($this->date_debut, $this->date_fin, 0, 1, $this->halfday);
		}

		return parent::showOutputField($val, $key, $value, $moreparam, $keyprefix, $morecss);
	}

	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param ?array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$val	Array of properties for field to show (used only if ->fields not defined)
	 *                                                                                                                                                                                                                                                                                                                                          Array of properties of field to show
	 * @param  string  		$key           Key of attribute
	 * @param  string|string[]	$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @param  int<0,1>		$nonewbutton   Force to not show the new button on field that are links to object
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;
		if ($key == 'fk_type') {
			return $this->getTypes(1, -1);
		}
	}

	/**
	 * Update balance of vacations and check table of users for holidays is complete. If not complete.
	 *
	 * @return	int			Return integer <0 if KO, >0 if OK
	 */
	public function updateBalance()
	{
		$this->db->begin();

		// Update sold of vocations
		$result = $this->updateSoldeCP();

		// Check nb of users into table llx_holiday_users and update with empty lines
		//if ($result > 0) $result = $this->verifNbUsers($this->countActiveUsersWithoutCP(), $this->getConfCP('nbUser'));

		if ($result >= 0) {
			$this->db->commit();
			return 0; // for cronjob use (0 is OK, any other value is an error code)
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	List holidays for a particular user or list of users
	 *
	 *  @param		int|string		$user_id    ID of user to list, or comma separated list of IDs of users to list
	 *  @param      string			$order      Sort order
	 *  @param      string			$filter     SQL Filter
	 *  @return     int      					-1 if KO, 1 if OK, 2 if no result
	 */
	public function fetchByUser($user_id, $order = '', $filter = '')
	{
		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";

		$sql .= " cp.fk_user,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.date_create,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut as status,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_approval,";
		$sql .= " cp.fk_user_approve,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";

		$sql .= " uu.lastname as user_lastname,";
		$sql .= " uu.firstname as user_firstname,";
		$sql .= " uu.login as user_login,";
		$sql .= " uu.statut as user_status,";
		$sql .= " uu.photo as user_photo,";

		$sql .= " ua.lastname as validator_lastname,";
		$sql .= " ua.firstname as validator_firstname,";
		$sql .= " ua.login as validator_login,";
		$sql .= " ua.statut as validator_status,";
		$sql .= " ua.photo as validator_photo";

		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu, ".MAIN_DB_PREFIX."user as ua";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = uu.rowid AND cp.fk_validator = ua.rowid"; // Hack pour la recherche sur le tableau
		$sql .= " AND cp.fk_user IN (".$this->db->sanitize($user_id).")";

		// Selection filter
		if (!empty($filter)) {
			$sql .= $filter;
		}

		// Order of display of the result
		if (!empty($order)) {
			$sql .= $order;
		}

		dol_syslog(get_class($this)."::fetchByUser", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// If no SQL error
		if ($resql) {
			$i = 0;
			$tab_result = $this->holiday;
			$num = $this->db->num_rows($resql);

			// If no registration
			if (!$num) {
				return 2;
			}

			// List the records and add them to the table
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['id'] = $obj->rowid;
				$tab_result[$i]['ref'] = ($obj->ref ? $obj->ref : $obj->rowid);

				$tab_result[$i]['fk_user'] = $obj->fk_user;
				$tab_result[$i]['fk_type'] = $obj->fk_type;
				$tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
				$tab_result[$i]['description'] = $obj->description;
				$tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
				$tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
				$tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
				$tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
				$tab_result[$i]['halfday'] = $obj->halfday;
				$tab_result[$i]['statut'] = $obj->status;
				$tab_result[$i]['status'] = $obj->status;
				$tab_result[$i]['fk_validator'] = $obj->fk_validator;
				$tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
				$tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
				$tab_result[$i]['date_approval'] = $this->db->jdate($obj->date_approval);
				$tab_result[$i]['fk_user_approve'] = $obj->fk_user_approve;
				$tab_result[$i]['date_refuse'] = $this->db->jdate($obj->date_refuse);
				$tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
				$tab_result[$i]['date_cancel'] = $this->db->jdate($obj->date_cancel);
				$tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
				$tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

				$tab_result[$i]['user_firstname'] = $obj->user_firstname;
				$tab_result[$i]['user_lastname'] = $obj->user_lastname;
				$tab_result[$i]['user_login'] = $obj->user_login;
				$tab_result[$i]['user_statut'] = $obj->user_status;
				$tab_result[$i]['user_status'] = $obj->user_status;
				$tab_result[$i]['user_photo'] = $obj->user_photo;

				$tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
				$tab_result[$i]['validator_lastname'] = $obj->validator_lastname;
				$tab_result[$i]['validator_login'] = $obj->validator_login;
				$tab_result[$i]['validator_statut'] = $obj->validator_status;
				$tab_result[$i]['validator_status'] = $obj->validator_status;
				$tab_result[$i]['validator_photo'] = $obj->validator_photo;

				$i++;
			}

			// Returns 1 with the filled array
			$this->holiday = $tab_result;
			return 1;
		} else {
			// SQL Error
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Check if a user is on holiday (partially or completely) into a period.
	 *  This function can be used to avoid to have 2 leave requests on same period for example.
	 *  Warning: It consumes a lot of memory because it load in ->holiday all holiday of a dedicated user at each call.
	 *
	 *  @param 	int		$fk_user		Id user
	 *  @param 	integer	$dateStart		Start date of period to check
	 *  @param 	integer	$dateEnd		End date of period to check
	 *  @param  int     $halfday        Tag to define how start and end the period to check:
	 *                                  0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning
	 * 	@return boolean					False = New range overlap an existing holiday, True = no overlapping (is never on holiday during checked period).
	 *  @see verifDateHolidayForTimestamp()
	 */
	public function verifDateHolidayCP($fk_user, $dateStart, $dateEnd, $halfday = 0)
	{
		$this->fetchByUser($fk_user, '', '');

		foreach ($this->holiday as $infos_CP) {
			if ($infos_CP['statut'] == Holiday::STATUS_CANCELED) {
				continue; // ignore not validated holidays
			}
			if ($infos_CP['statut'] == Holiday::STATUS_REFUSED) {
				continue; // ignore refused holidays
			}
			//var_dump("--");
			//var_dump("old: ".dol_print_date($infos_CP['date_debut'],'dayhour').' '.dol_print_date($infos_CP['date_fin'],'dayhour').' '.$infos_CP['halfday']);
			//var_dump("new: ".dol_print_date($dateStart,'dayhour').' '.dol_print_date($dateEnd,'dayhour').' '.$halfday);

			if ($halfday == 0) {
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					return false;
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					return false;
				}
			} elseif ($halfday == -1) {
				// new start afternoon, new end afternoon
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateStart < $dateEnd) {
						return false;
					}
					if ($dateEnd < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
			} elseif ($halfday == 1) {
				// new start morning, new end morning
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $dateEnd) {
						return false;
					}
					if ($dateStart > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateEnd > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
			} elseif ($halfday == 2) {
				// new start afternoon, new end morning
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateEnd > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
			} else {
				dol_print_error(null, 'Bad value of parameter halfday when calling function verifDateHolidayCP');
			}
		}

		return true;
	}

	/**
	 *	Check that a user is not on holiday for a particular timestamp. Can check approved leave requests and not into public holidays of company.
	 *
	 * 	@param 	int			$fk_user				Id user
	 *  @param	integer	    $timestamp				Time stamp date for a day (YYYY-MM-DD) without hours  (= 12:00AM in english and not 12:00PM that is 12:00)
	 *  @param	string		$status					Filter on holiday status. '-1' = no filter.
	 * 	@return array								array('morning'=> ,'afternoon'=> ), Boolean is true if user is available for day timestamp.
	 *  @see verifDateHolidayCP()
	 */
	public function verifDateHolidayForTimestamp($fk_user, $timestamp, $status = '-1')
	{
		$isavailablemorning = true;
		$isavailableafternoon = true;

		// Check into leave requests
		$sql = "SELECT cp.rowid, cp.date_debut as date_start, cp.date_fin as date_end, cp.halfday, cp.statut as status";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = ".(int) $fk_user;
		$sql .= " AND cp.date_debut <= '".$this->db->idate($timestamp)."' AND cp.date_fin >= '".$this->db->idate($timestamp)."'";
		if ($status != '-1') {
			$sql .= " AND cp.statut IN (".$this->db->sanitize($status).")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql); // Note, we can have 2 records if on is morning and the other one is afternoon
			if ($num_rows > 0) {
				$arrayofrecord = array();
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);

					// Note: $obj->halfday is  0:Full days, 2:Start afternoon end morning, -1:Start afternoon, 1:End morning
					$arrayofrecord[$obj->rowid] = array('date_start' => $this->db->jdate($obj->date_start), 'date_end' => $this->db->jdate($obj->date_end), 'halfday' => $obj->halfday, 'status' => $obj->status);
					$i++;
				}

				// We found a record, user is on holiday by default, so is not available is true.
				$isavailablemorning = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_start'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_start'] && $record['halfday'] == -1) {
						continue;
					}
					$isavailablemorning = false;
					break;
				}
				$isavailableafternoon = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_end'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_end'] && $record['halfday'] == 1) {
						continue;
					}
					$isavailableafternoon = false;
					break;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		$result = array('morning' => $isavailablemorning, 'afternoon' => $isavailableafternoon);
		if (!$isavailablemorning) {
			$result['morning_reason'] = 'leave_request';
		}
		if (!$isavailableafternoon) {
			$result['afternoon_reason'] = 'leave_request';
		}
		return $result;
	}

	/**
	 *   Show select with list of leave status
	 *
	 *   @param 	int		$selected   	Id of preselected status
	 *   @param		string	$htmlname		Name of HTML select field
	 *   @param		string	$morecss		More CSS on select component
	 *   @return    string					Show select of status
	 */
	public function selectStatutCP($selected = 0, $htmlname = 'select_statut', $morecss = 'minwidth125')
	{
		global $langs;

		// List of status label
		$name = array('DraftCP', 'ToReviewCP', 'ApprovedCP', 'CancelCP', 'RefuseCP');
		$nb = count($name) + 1;

		// Select HTML
		$out = '<select name="'.$htmlname.'" id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'">'."\n";
		$out .= '<option value="-1">&nbsp;</option>'."\n";

		// Loop on status
		for ($i = 1; $i < $nb; $i++) {
			if ($i == $selected) {
				$out .= '<option value="'.$i.'" selected>'.$langs->trans($name[$i - 1]).'</option>'."\n";
			} else {
				$out .= '<option value="'.$i.'">'.$langs->trans($name[$i - 1]).'</option>'."\n";
			}
		}

		$out .= "</select>\n";

		$showempty = 0;
		$out .= ajax_combobox($htmlname, array(), 0, 0, 'resolve', ($showempty < 0 ? (string) $showempty : '-1'), $morecss);

		return $out;
	}

	/**
	 *  Met à jour une option du module Holiday Payés
	 *
	 *  @param	string	$name       name du paramètre de configuration
	 *  @param	string	$value      vrai si mise à jour OK sinon faux
	 *  @return boolean				ok or ko
	 */
	public function updateConfCP($name, $value)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
		$sql .= " value = '".$this->db->escape($value)."'";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		dol_syslog(get_class($this).'::updateConfCP name='.$name, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return true;
		}

		return false;
	}

	/**
	 *  Return value of a conf parameter for leave module
	 *  TODO Move this into llx_const table
	 *
	 *  @param	string	$name                 Name of parameter
	 *  @param  string  $createifnotfound     'stringvalue'=Create entry with string value if not found. For example 'YYYYMMDDHHMMSS'.
	 *  @return string|int<min,0>             Value of parameter. Example: 'YYYYMMDDHHMMSS' or < 0 if error
	 */
	public function getConfCP($name, $createifnotfound = '')
	{
		$sql = "SELECT value";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_config";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		dol_syslog(get_class($this).'::getConfCP name='.$name.' createifnotfound='.$createifnotfound, LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			$obj = $this->db->fetch_object($result);
			// Return value
			if (empty($obj)) {
				if ($createifnotfound) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_config(name, value)";
					$sql .= " VALUES('".$this->db->escape($name)."', '".$this->db->escape($createifnotfound)."')";
					$result = $this->db->query($sql);
					if ($result) {
						return $createifnotfound;
					} else {
						$this->error = $this->db->lasterror();
						return -2;
					}
				} else {
					return '';
				}
			} else {
				return $obj->value;
			}
		} else {
			// Erreur SQL
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Met à jour le timestamp de la dernière mise à jour du solde des CP
	 *
	 *	@param		int		$userID		Id of user
	 *	@param		float	$nbHoliday	Nb of days
	 *  @param		int		$fk_type	Type of vacation
	 *  @return     int					0=Nothing done, 1=OK, -1=KO
	 */
	public function updateSoldeCP($userID = 0, $nbHoliday = 0, $fk_type = 0)
	{
		global $user, $langs;

		$error = 0;

		if (empty($userID) && empty($nbHoliday) && empty($fk_type)) {
			$langs->load("holiday");

			// Si mise à jour pour tout le monde en début de mois
			$now = dol_now();

			$month = date('m', $now);
			$newdateforlastupdate = dol_print_date($now, '%Y%m%d%H%M%S');

			// Get month of last update
			$lastUpdate = $this->getConfCP('lastUpdate', $newdateforlastupdate);
			$monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
			//print 'month: '.$month.' lastUpdate:'.$lastUpdate.' monthLastUpdate:'.$monthLastUpdate;exit;

			// If month date is not same than the one of last update (the one we saved in database), then we update the timestamp and balance of each open user.
			if ($month != $monthLastUpdate) {
				$this->db->begin();

				$users = $this->fetchUsers(false, false, ' AND u.statut > 0');
				$nbUser = count($users);

				$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
				$sql .= " value = '".$this->db->escape($newdateforlastupdate)."'";
				$sql .= " WHERE name = 'lastUpdate'";
				$result = $this->db->query($sql);

				$typeleaves = $this->getTypes(1, 1);

				// Update each user counter
				foreach ($users as $userCounter) {
					$nbDaysToAdd = (isset($typeleaves[$userCounter['type']]['newbymonth']) ? $typeleaves[$userCounter['type']]['newbymonth'] : 0);
					if (empty($nbDaysToAdd)) {
						continue;
					}

					dol_syslog("We update leave type id ".$userCounter['type']." for user id ".$userCounter['rowid'], LOG_DEBUG);

					$nowHoliday = $userCounter['nb_holiday'];
					$newSolde = $nowHoliday + $nbDaysToAdd;

					// We add a log for each user
					$this->addLogCP($user->id, $userCounter['rowid'], $langs->trans('HolidaysMonthlyUpdate'), $newSolde, $userCounter['type']);

					$result = $this->updateSoldeCP($userCounter['rowid'], $newSolde, $userCounter['type']);

					if ($result < 0) {
						$error++;
						break;
					}
				}

				if (!$error) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			}

			return 0;
		} else {
			// Mise à jour pour un utilisateur
			$nbHoliday = price2num($nbHoliday, 5);

			$sql = "SELECT nb_holiday FROM ".MAIN_DB_PREFIX."holiday_users";
			$sql .= " WHERE fk_user = ".(int) $userID." AND fk_type = ".(int) $fk_type;
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				if ($num > 0) {
					// Update for user
					$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_users SET";
					$sql .= " nb_holiday = ".((float) $nbHoliday);
					$sql .= " WHERE fk_user = ".(int) $userID." AND fk_type = ".(int) $fk_type;
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				} else {
					// Insert for user
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users(nb_holiday, fk_user, fk_type) VALUES (";
					$sql .= ((float) $nbHoliday);
					$sql .= ", ".(int) $userID.", ".(int) $fk_type.")";
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}
			} else {
				$this->errors[] = $this->db->lasterror();
				$error++;
			}

			if (!$error) {
				return 1;
			} else {
				return -1;
			}
		}
	}

	/**
	 *  Return the balance of annual leave of a user
	 *
	 *  @param	int		$user_id    User ID
	 *  @param	int		$fk_type	Filter on type
	 *  @return float|null     		Balance of annual leave if OK, null if KO.
	 */
	public function getCPforUser($user_id, $fk_type = 0)
	{
		$sql = "SELECT nb_holiday";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users";
		$sql .= " WHERE fk_user = ".(int) $user_id;
		if ($fk_type > 0) {
			$sql .= " AND fk_type = ".(int) $fk_type;
		}

		dol_syslog(get_class($this).'::getCPforUser user_id='.$user_id.' type_id='.$fk_type, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			//return number_format($obj->nb_holiday,2);
			if ($obj) {
				return $obj->nb_holiday;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 *    Get list of Users or list of vacation balance.
	 *
	 *    @param      boolean			$stringlist	    If true return a string list of id. If false, return an array with detail.
	 *    @param      boolean   		$type			If true, read Dolibarr user list, if false, return vacation balance list.
	 *    @param      string            $filters        Filters. Warning: This must not contains data from user input.
	 *    @return     array|string|int      			Return an array
	 */
	public function fetchUsers($stringlist = true, $type = true, $filters = '')
	{
		global $conf;

		dol_syslog(get_class($this)."::fetchUsers", LOG_DEBUG);

		if ($stringlist) {
			if ($type) {
				// If user of Dolibarr
				$sql = "SELECT";
				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= " DISTINCT";
				}
				$sql .= " u.rowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = u.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR u.entity = 0)"; // Show always superadmin
				} else {
					$sql .= " WHERE u.entity IN (".getEntity('user').")";
				}
				$sql .= " AND u.statut > 0";
				$sql .= " AND u.employee = 1"; // We only want employee users for holidays
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$num = $this->db->num_rows($resql);
					$stringlist = '';

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($i == 0) {
							$stringlist .= $obj->rowid;
						} else {
							$stringlist .= ', '.$obj->rowid;
						}

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $stringlist;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			} else {
				// We want only list of vacation balance for user ids
				$sql = "SELECT DISTINCT cpu.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE cpu.fk_user = u.rowid";
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$num = $this->db->num_rows($resql);
					$stringlist = '';

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($i == 0) {
							$stringlist .= $obj->fk_user;
						} else {
							$stringlist .= ', '.$obj->fk_user;
						}

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $stringlist;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			}
		} else {
			// Si faux donc return array
			// List for Dolibarr users
			if ($type) {
				// If we need users of Dolibarr
				$sql = "SELECT";
				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= " DISTINCT";
				}
				$sql .= " u.rowid, u.lastname, u.firstname, u.gender, u.photo, u.employee, u.statut as status, u.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = u.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR u.entity = 0)"; // Show always superadmin
				} else {
					$sql .= " WHERE u.entity IN (".getEntity('user').")";
				}

				$sql .= " AND u.statut > 0";
				$sql .= " AND u.employee = 1"; // We only want employee users for holidays
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$tab_result = $this->holiday;
					$num = $this->db->num_rows($resql);

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						$tab_result[$i]['rowid'] = $obj->rowid; // rowid of user
						$tab_result[$i]['id'] = $obj->rowid; // id of user
						$tab_result[$i]['name'] = $obj->lastname; // deprecated
						$tab_result[$i]['lastname'] = $obj->lastname;
						$tab_result[$i]['firstname'] = $obj->firstname;
						$tab_result[$i]['gender'] = $obj->gender;
						$tab_result[$i]['status'] = $obj->status;
						$tab_result[$i]['employee'] = $obj->employee;
						$tab_result[$i]['photo'] = $obj->photo;
						$tab_result[$i]['fk_user'] = $obj->fk_user; // rowid of manager
						//$tab_result[$i]['type'] = $obj->type;
						//$tab_result[$i]['nb_holiday'] = $obj->nb_holiday;

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $tab_result;
				} else {
					// Erreur SQL
					$this->errors[] = "Error ".$this->db->lasterror();
					return -1;
				}
			} else {
				// List of vacation balance users
				$sql = "SELECT cpu.fk_type, cpu.nb_holiday, u.rowid, u.lastname, u.firstname, u.gender, u.photo, u.employee, u.statut as status, u.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE cpu.fk_user = u.rowid";
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$tab_result = $this->holiday;
					$num = $this->db->num_rows($resql);

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						$tab_result[$i]['rowid'] = $obj->rowid; // rowid of user
						$tab_result[$i]['id'] = $obj->rowid; // id of user
						$tab_result[$i]['name'] = $obj->lastname; // deprecated
						$tab_result[$i]['lastname'] = $obj->lastname;
						$tab_result[$i]['firstname'] = $obj->firstname;
						$tab_result[$i]['gender'] = $obj->gender;
						$tab_result[$i]['status'] = $obj->status;
						$tab_result[$i]['employee'] = $obj->employee;
						$tab_result[$i]['photo'] = $obj->photo;
						$tab_result[$i]['fk_user'] = $obj->fk_user; // rowid of manager

						$tab_result[$i]['type'] = $obj->fk_type;
						$tab_result[$i]['nb_holiday'] = $obj->nb_holiday;

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $tab_result;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			}
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of people with permission to validate leave requests.
	 * Search for permission "approve leave requests"
	 *
	 * @return  array|int       Array of user ids or -1 if error
	 */
	public function fetch_users_approver_holiday()
	{
		// phpcs:enable
		$users_validator = array();

		$sql = "SELECT DISTINCT ur.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ur.fk_id = rd.id and rd.module = 'holiday' AND rd.perms = 'approve'"; // Permission 'Approve';
		$sql .= "UNION";
		$sql .= " SELECT DISTINCT ugu.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_user as ugu, ".MAIN_DB_PREFIX."usergroup_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ugu.fk_usergroup = ur.fk_usergroup AND ur.fk_id = rd.id and rd.module = 'holiday' AND rd.perms = 'approve'"; // Permission 'Approve';
		//print $sql;

		dol_syslog(get_class($this)."::fetch_users_approver_holiday sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$num_rows = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num_rows) {
				$objp = $this->db->fetch_object($result);
				array_push($users_validator, $objp->fk_user);
				$i++;
			}
			return $users_validator;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_users_approver_holiday  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * addLogCP
	 *
	 * @param 	int		$fk_user_action		Id user creation
	 * @param 	int		$fk_user_update		Id user update
	 * @param 	string	$label				Label (Example: 'Leave', 'Manual update', 'Leave request cancelation'...)
	 * @param 	int		$new_solde			New value
	 * @param	int		$fk_type			Type of vacation
	 * @return 	int							Id of record added, 0 if nothing done, < 0 if KO
	 */
	public function addLogCP($fk_user_action, $fk_user_update, $label, $new_solde, $fk_type)
	{
		global $conf, $langs;

		$error = 0;

		$prev_solde = price2num($this->getCPforUser($fk_user_update, $fk_type), 5);
		$new_solde = price2num($new_solde, 5);
		//print "$prev_solde == $new_solde";

		if ($prev_solde == $new_solde) {
			return 0;
		}

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_logs (";
		$sql .= "date_action,";
		$sql .= "fk_user_action,";
		$sql .= "fk_user_update,";
		$sql .= "type_action,";
		$sql .= "prev_solde,";
		$sql .= "new_solde,";
		$sql .= "fk_type";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->idate(dol_now())."',";
		$sql .= " ".((int) $fk_user_action).",";
		$sql .= " ".((int) $fk_user_update).",";
		$sql .= " '".$this->db->escape($label)."',";
		$sql .= " ".((float) $prev_solde).",";
		$sql .= " ".((float) $new_solde).",";
		$sql .= " ".((int) $fk_type);
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday_logs");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::addLogCP ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->optRowid;
		}
	}

	/**
	 *  Liste le log des congés payés
	 *
	 *  @param	string	$order      Filtrage par ordre
	 *  @param  string	$filter     Filtre de séléction
	 *  @return int         		-1 si erreur, 1 si OK et 2 si pas de résultat
	 */
	public function fetchLog($order, $filter)
	{
		$sql = "SELECT";
		$sql .= " cpl.rowid,";
		$sql .= " cpl.date_action,";
		$sql .= " cpl.fk_user_action,";
		$sql .= " cpl.fk_user_update,";
		$sql .= " cpl.type_action,";
		$sql .= " cpl.prev_solde,";
		$sql .= " cpl.new_solde,";
		$sql .= " cpl.fk_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_logs as cpl";
		$sql .= " WHERE cpl.rowid > 0"; // To avoid error with other search and criteria

		// Filtrage de séléction
		if (!empty($filter)) {
			$sql .= " ".$filter;
		}

		// Ordre d'affichage
		if (!empty($order)) {
			$sql .= " ".$order;
		}

		dol_syslog(get_class($this)."::fetchLog", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// Si pas d'erreur SQL
		if ($resql) {
			$i = 0;
			$tab_result = $this->logs;
			$num = $this->db->num_rows($resql);

			// Si pas d'enregistrement
			if (!$num) {
				return 2;
			}

			// On liste les résultats et on les ajoutent dans le tableau
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['id'] = $obj->rowid;
				$tab_result[$i]['date_action'] = $obj->date_action;
				$tab_result[$i]['fk_user_action'] = $obj->fk_user_action;
				$tab_result[$i]['fk_user_update'] = $obj->fk_user_update;
				$tab_result[$i]['type_action'] = $obj->type_action;
				$tab_result[$i]['prev_solde'] = $obj->prev_solde;
				$tab_result[$i]['new_solde'] = $obj->new_solde;
				$tab_result[$i]['fk_type'] = $obj->fk_type;

				$i++;
			}
			// Retourne 1 et ajoute le tableau à la variable
			$this->logs = $tab_result;
			return 1;
		} else {
			// Erreur SQL
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Return array with list of types
	 *
	 *  @param		int		$active		Status of type. -1 = Both
	 *  @param		int		$affect		Filter on affect (a request will change sold or not). -1 = Both
	 *  @return     array	    		Return array with list of types
	 */
	public function getTypes($active = -1, $affect = -1)
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		if ($active >= 0) {
			$sql .= " AND active = ".((int) $active);
		}
		if ($affect >= 0) {
			$sql .= " AND affect = ".((int) $affect);
		}
		$sql .= " ORDER BY sortorder";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				$types = array();
				while ($obj = $this->db->fetch_object($result)) {
					$types[$obj->rowid] = array('id' => $obj->rowid, 'rowid' => $obj->rowid, 'code' => $obj->code, 'label' => $obj->label, 'affect' => $obj->affect, 'delay' => $obj->delay, 'newbymonth' => $obj->newbymonth);
				}

				return $types;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *      Load this->nb for dashboard
	 *
	 *      @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $user;

		$this->nb = array();

		$sql = "SELECT count(h.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.statut > 1";
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		if (!$user->hasRight('expensereport', 'readall')) {
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (h.fk_user IN (".$this->db->sanitize(implode(',', $userchildids)).")";
			$sql .= " OR h.fk_validator IN (".$this->db->sanitize(implode(',', $userchildids))."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["holidays"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Object user
	 *      @return WorkboardResponse|int 	Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT h.rowid, h.date_debut";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.statut = 2";
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		if (!$user->hasRight('expensereport', 'read_all')) {
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (h.fk_user IN (".$this->db->sanitize(implode(',', $userchildids)).")";
			$sql .= " OR h.fk_validator IN (".$this->db->sanitize(implode(',', $userchildids))."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("members");

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->holiday->approve->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("HolidaysToApprove");
			$response->labelShort = $langs->trans("ToApprove");
			$response->url = DOL_URL_ROOT.'/holiday/list.php?search_status=2&amp;mainmenu=hrm&amp;leftmenu=holiday';
			$response->img = img_object('', "holiday");

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				if ($this->db->jdate($obj->date_debut) < ($now - $conf->holiday->approve->warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}
	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Label of holiday type (if known)
	 *  @return		string		HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.$arraydata['user']->getNomUrl(-1).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'fk_type')) {
			$return .= '<br>';
			//$return .= '<span class="opacitymedium">'.$langs->trans("Type").'</span> : ';
			$return .= '<div class="info_box-label tdoverflowmax100" title="'.dol_escape_htmltag($arraydata['labeltype']).'">'.dol_escape_htmltag($arraydata['labeltype']).'</div>';
		}
		if (property_exists($this, 'date_debut') && property_exists($this, 'date_fin')) {
			$return .= '<span class="info-box-label small">'.dol_print_date($this->date_debut, 'day').'</span>';
			$return .= ' <span class="opacitymedium small">'.$langs->trans("To").'</span> ';
			$return .= '<span class="info-box-label small">'.dol_print_date($this->date_fin, 'day').'</span>';
			if (!empty($arraydata['nbopenedday'])) {
				$return .= ' ('.$arraydata['nbopenedday'].')';
			}
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
