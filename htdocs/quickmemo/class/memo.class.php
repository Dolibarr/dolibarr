<?php
/* Copyright (C) 2017       Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023-2026  Frédéric France          <frederic.france@free.fr>
 * Copyright (C) 2026		John BOTELLA
 * Copyright (C) 2026		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file        class/memo.class.php
 * \ingroup     quickmemo
 * \brief       This file is a CRUD class file for Memo (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Memo
 */
class Memo extends CommonObject
{
	/**
	 * @var string 		ID of module.
	 */
	public $module = 'quickmemo';

	/**
	 * @var string 		ID to identify managed object.
	 */
	public $element = 'memo';

	/**
	 * @var string		Prefix to check for any trigger code of any business class to prevent bad value for trigger code.
	 * @see CommonTrigger::call_trigger()
	 */
	public $TRIGGER_PREFIX = 'QUICKMEMO_MEMO';	// Will be used to build trgiger keys 'QUICKMEMO_MEMO_MODIFY', ...

	/**
	 * @var string 		Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'quickmemo_memo';

	/**
	 * @var string 		If permission must be checked with hasRight('quickmemo', 'read') and not hasright('quickmemo', 'memo', 'read'), you can uncomment this line
	 */
	//public $element_for_permission = 'quickmemo';

	/**
	 * @var string 		String with name of icon for memo. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'memo@quickmemo' if picto is file 'img/object_memo.png'.
	 */
	public $picto = 'fa-file';

	/**
	 * @var int<0,1>	Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var int<0,1>|string		Does this object support multicompany module ?
	 * 							0=No test on entity, 1=Test with field entity in local table, 'field@table'=Test entity into the field@table (example 'fk_soc@societe')
	 */
	public $ismultientitymanaged = 0;



	const STATUS_TPL = 2;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9; // fall back in case of
	const STATUS_ARCHIVED = 9;

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
	 *  	'email', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:>:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'length' the length of field. Example: 255, '24,8'
	 *  'label' the translation key.
	 *  'langfile' the key of the language file for translation.
	 *  'alias' the alias used into some old hard coded SQL requests
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalInt("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form (not create). 5=Visible on list and view form (not create/not update). 6=visible on list and update/view form (not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwritten by the Setup of Default Values if the field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'placeholder' to set the placeholder of a varchar field.
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code like the constructor of the class.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if you need to validate the field with $this->validateField(). Need MAIN_ACTIVATE_VALIDATION_RESULT.
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @inheritdoc
	 * Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type" => "integer", "label" => "TechnicalID", 'enabled' => 1, 'position' => 1, 'notnull' => 1, "visible" => 0, 'noteditable' => 1, 'index' => 1, "css" => "left", "comment" => "Id"),
		"quick_note" => array("type" => "text", "label" => "Note", 'enabled' => 1, 'position' => 61, 'notnull' => 0, "visible" => -1, "cssview" => "wordbreak", "validate" => 1,),
		"date_creation" => array("type" => "datetime", "label" => "DateCreation", 'enabled' => 1, 'position' => 500, 'notnull' => 1, "visible" => -2,),
		"tms" => array("type" => "timestamp", "label" => "DateModification", 'enabled' => 1, 'position' => 501, 'notnull' => 0, "visible" => -2,),
		"date_archived" => array("type" => "timestamp", "label" => "DateArchived", 'enabled' => 1, 'position' => 502, 'notnull' => 0, "visible" => -2,),
		"fk_user_creat" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserAuthor", "picto" => "user", 'enabled' => 1, 'position' => 510, 'notnull' => 1, "visible" => -2, "csslist" => "tdoverflowmax150",),
		"fk_user_modif" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserModif", "picto" => "user", 'enabled' => 1, 'position' => 511, 'notnull' => -1, "visible" => -2, "csslist" => "tdoverflowmax150",),
		"fk_user_archived" => array("type" => "integer:User:user/class/user.class.php", "label" => "ArchivedBy", "picto" => "user", 'enabled' => 1, 'position' => 511, 'notnull' => -1, "visible" => -2, "csslist" => "tdoverflowmax150",),
		"fk_element" => array('type' => 'integer','label' => 'MemoLinkedTo','help' => 'MemoLinkedToHelp','enabled' => 1,'visible' => 5,'notnull' => 0,'default' => 0,'index' => 1,'position' => 0),
		"element_type" => array('type' => 'varchar(64)','label' => 'QuickMemoElementType','enabled' => 1,'visible' => 5,'position' => 10,'required' => 0),
		"pos_z" => array("type" => "integer", "label" => "PosZ", 'enabled' => 1, 'position' => 1000, 'notnull' => 1, "visible" => 0, "default" => 0, "validate" => 1),
		"pos_y" => array("type" => "integer", "label" => "PosY", 'enabled' => 1, 'position' => 1000, 'notnull' => 1, "visible" => 0, "default" => 0, "validate" => 1),
		"pos_x" => array("type" => "integer", "label" => "PosX", 'enabled' => 1, 'position' => 1000, 'notnull' => 1, "visible" => 0, "default" => 0, "validate" => 1),
		"pos_w" => array("type" => "integer", "label" => "PosW", 'enabled' => 1, 'position' => 1000, 'notnull' => 1, "visible" => 0, "default" => 0, "validate" => 1),
		"pos_h" => array("type" => "integer", "label" => "PosH", 'enabled' => 1, 'position' => 1000, 'notnull' => 1, "visible" => 0, "default" => 0, "validate" => 1),
		"color" => array('type' => 'varchar(10)', 'label' => 'Color','enabled' => 1,'visible' => 1,'position' => 10,'required' => 0),
		"context_tab" => array('type' => 'varchar(64)', 'label' => 'ContextTab','enabled' => 1,'visible' => 1,'position' => 10,'required' => 0),
		"private" => array("type" => "integer", "label" => "Private", 'enabled' => 1, 'position' => 1990, 'notnull' => 1, "visible" => 1, 'index' => 1, "arrayofkeyval" => array(0 => "No", 1 => "Yes"),'default' => 1, "validate" => 1,),
		"private_tpl" => array("type" => "integer", "label" => "PrivateTemplate", 'enabled' => 1, 'position' => 1990, 'notnull' => 0, "visible" => 1, 'index' => 1, "arrayofkeyval" => array(0 => "No", 1 => "Yes"),'default' => 0, "validate" => 1,),
		"rank_tpl" => array("type" => "integer", "label" => "TemplateRank", 'enabled' => 1, 'position' => 1990, 'notnull' => 0, "visible" => 0, 'index' => 1,'default' => 0, "validate" => 1,),
		"name_tpl" => array('type' => 'varchar(256)','label' => 'QuickMemoTemplateName','enabled' => 1,'visible' => -1,'position' => 1,'required' => 0),
		"shared_on_element" => array("type" => "integer", "label" => "SharedBetweenElement", 'enabled' => 1, 'position' => 1991, 'notnull' => 1, "visible" => 1, 'index' => 1, "arrayofkeyval" => array(0 => "No", 1 => "Yes"), "validate" => 1,),
		"import_key" => array("type" => "varchar(14)", "label" => "ImportId", 'enabled' => 1, 'position' => 1000, 'notnull' => -1, "visible" => -2,),
		"status" => array("type" => "integer", "label" => "Status", 'enabled' => 1, 'position' => 2000, 'notnull' => 1, "visible" => 1, 'index' => 1, "arrayofkeyval" => array(1 => "Active",2 => "Template",  9 => "Archived"), "validate" => 1,),
	);

	/** @var int|null */
	public $rowid;

	/** @var int|null */
	public $date_archived;

	/** @var int|null */
	public $fk_user_archived;

	/** @var string|null */
	public $quick_note;

	/** @var int|string|null */
	public $date_creat;

	/**
	 * @var string|int	Field with ID of parent key if this field has a parent (a string). For example 'fk_product'.
	 *					ID of parent key itself (an int). For example in few classes like 'Comment', 'ActionComm' or 'AdvanceTargetingMailing'.
	 */
	public $fk_element;

	/** @var string|null */
	public $element_type;

	/** @var int|string|null */
	public $pos_z;

	/** @var int|string|null */
	public $pos_y;

	/** @var int|string|null */
	public $pos_x;

	/** @var int|string|null */
	public $pos_w;

	/** @var int|string|null */
	public $pos_h;

	/** @var int|string|null */
	public $color;

	/** @var string|null */
	public $context_tab;

	/** @var string|null */
	public $import_key;

	/**
	 * @var null|int|array<int, string>   The object's status (an int).
	 *                 						Or an array listing all the potential status of the object:
	 *                                    	array: int of the status => translated label of the status
	 *                                    	In some classes status must be able to be null.
	 *                                    	See for example the Account class.
	 * @see setStatut()
	 */
	public $status;

	/** @var int|string|null */
	public $private;

	/** @var int|string|null */
	public $private_tpl;

	/** @var string|null */
	public $name_tpl;

	/** @var int|string|null */
	public $shared_on_element;

	/**
	 * Constructor
	 *
	 * @param	DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

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
	}

	/**
	 * Create object into database
	 *
	 * @param	User		$user		User that creates
	 * @param	int<0,1> 	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int<-1,max>				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		$result = $this->createCommon($user, $notrigger);

		return $result;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param	User 	$user		User that creates
	 * @param	int 	$fromid		Id of object to clone
	 * @return	self|int<-1,-1>		New object created, <0 if KO
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
		unset($object->fk_user_creat);
		unset($object->import_key);


		$object->status = self::STATUS_VALIDATED;

		$object->date_creation = dol_now();

		$object->date_modification = null;

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
	 * @param	int    		$id   			Id object
	 * @param	string 		$ref  			Ref
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int<0,1>	$nolines		0=Default to load lines, 1=No lines
	 * @return	int<-1,1>					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $ref, '', $noextrafields);
		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->fetchLines($noextrafields);
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @return 	int<-1,1>					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines($noextrafields = 0)
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon('', $noextrafields);
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 * Using a fetchAll() with limit = 0 is a very bad practice. Instead try to forge yourself an optimized SQL request with
	 * your own loop with start and stop pagination.
	 *
	 * @param	string		$sortorder	Sort Order
	 * @param	string		$sortfield	Sort field
	 * @param	int<0,max>	$limit		Limit the number of lines returned
	 * @param	int<0,max>	$offset		Offset
	 * @param	string		$filter		Filter as an Universal Search string.
	 *                                  Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param	string		$filtermode	No longer used
	 * @return	array<int,self>|int<-1,-1>	 <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 1000, $offset = 0, string $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		if (!empty($this->ismultientitymanaged) && (int) $this->ismultientitymanaged == 1) {
			$sql .= " WHERE t.entity IN (".getEntity($this->element).")";
		} elseif (preg_match('/^\w+@\w+$/', (string) $this->ismultientitymanaged)) {
			$tmparray = explode('@', (string) $this->ismultientitymanaged);
			$sql .= " LEFT JOIN ".$this->db->prefix().$tmparray[1]." as pt ON t.".$this->db->sanitize($tmparray[0])." = pt.rowid";
			$sql .= " WHERE pt.entity IN (".getEntity($this->element).")";
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
	 * @param	User		$user		User that modifies
	 * @param	int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Update position of a memo for a specific user
	 *
	 * @param User $user Dolibarr user
	 * @param int  $x    Position X
	 * @param int  $y    Position Y
	 * @param int  $w    Width
	 * @param int  $h    Height
	 * @param int  $z    Z-index (rank)
	 *
	 * @return bool True if success, false if error
	 */
	public function updatePosition(User $user, int $x, int $y, int $w, int $h, int $z)
	{
		if (empty($this->id)) {
			return false;
		}

		$x = (int) $x;
		$y = (int) $y;
		$w = (int) $w;
		$h = (int) $h;
		$z = (int) $z;

		// CREATEUR
		if ((int) $this->fk_user_creat === (int) $user->id) {
			$this->pos_x = $x;
			$this->pos_y = $y;
			$this->pos_w = $w;
			$this->pos_h = $h;
			$this->pos_z = $z;

			if ($this->update($user) <= 0) {
				return false;
			}

			return true;
		}

		// USER NOT CREATOR

		$sql = 'SELECT rowid';
		$sql .= ' FROM '.$this->db->prefix().'quickmemo_memo_user';
		$sql .= ' WHERE fk_memo = '.((int) $this->id);
		$sql .= ' AND fk_user = '.((int) $user->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			return false;
		}

		if ($this->db->num_rows($resql) > 0) {
			// UPDATE
			$sql = 'UPDATE '.$this->db->prefix().'quickmemo_memo_user SET';
			$sql .= ' pos_x = '. (int) $x.',';
			$sql .= ' pos_y = '. (int) $y.',';
			$sql .= ' pos_w = '. (int) $w.',';
			$sql .= ' pos_h = '. (int) $h.',';
			$sql .= ' pos_z = '. (int) $z;
			$sql .= ' WHERE fk_memo = '.((int) $this->id);
			$sql .= ' AND fk_user = '.((int) $user->id);

			if (!$this->db->query($sql)) {
				return false;
			}
		} else {
			// INSERT
			$sql = 'INSERT INTO '.$this->db->prefix().'quickmemo_memo_user (';
			$sql .= 'fk_memo,';
			$sql .= 'fk_user,';
			$sql .= 'date_creation,';
			$sql .= 'pos_x,';
			$sql .= 'pos_y,';
			$sql .= 'pos_w,';
			$sql .= 'pos_h,';
			$sql .= 'pos_z';
			$sql .= ') VALUES (';
			$sql .= ((int) $this->id).',';
			$sql .= ((int) $user->id).',';
			$sql .= '\''. $this->db->idate(dol_now()) .'\',';
			$sql .= (int) $x.',';
			$sql .= (int) $y.',';
			$sql .= (int) $w.',';
			$sql .= (int) $h.',';
			$sql .= (int) $z;
			$sql .= ')';

			if (!$this->db->query($sql)) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Delete object in database
	 *
	 * @param	User		$user		User that deletes
	 * @param	int<0,1> 	$notrigger	0=launch triggers, 1=disable triggers
	 * @return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 * @param	User		$user		User that deletes
	 *  @param	int			$idline		Id of line to delete
	 *  @param	int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 *  @return	int<-2,1>				>0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = 0)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *	Validate object
	 *
	 *	@param	User		$user		User making status change
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int<-1,1>				Return integer <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo', 'memo', 'write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo', 'memo_advance', 'validate')))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = (string) $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE ".$this->db->prefix().$this->table_element;
			$sql .= " SET ";
			if (!empty($this->fields['ref'])) {
				$sql .= " ref = '".$this->db->escape($num)."',";
			}
			$sql .= " status = ".self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '".$this->db->idate($now)."'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = ".((int) $user->id);
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('MEMO_VALIDATE', $user);
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
				$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'memo/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'memo/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filepath = 'memo/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'memo/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->quickmemo->dir_output.'/memo/'.$oldref;
				$dirdest = $conf->quickmemo->dir_output.'/memo/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::validate() rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->quickmemo->dir_output.'/memo/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
							$dirsource = $fileentry['path'].'/'.$dirsource;
							$dirdest = $fileentry['path'].'/'.$dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set draft status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<0,1>				Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_VALIDATED) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo','quickmemo_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'QUICKMEMO_MEMO_UNVALIDATE');
	}


	/**
	 *	Set archived status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<0,1>				Return integer <0 if KO, >0 if OK
	 */
	public function setArchived($user, $notrigger = 0)
	{
		$this->date_archived = dol_now();
		$this->fk_user_archived = $user->id;
		$this->status = self::STATUS_ARCHIVED;
		return $this->update($user, $notrigger);
	}


	/**
	 *	Set unarchived status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<0,1>				Return integer <0 if KO, >0 if OK
	 */
	public function setUnArchived($user, $notrigger = 0)
	{
		$this->date_archived = null;
		$this->fk_user_archived = null;
		$this->status = self::STATUS_VALIDATED;
		return $this->update($user, $notrigger);
	}

	/**
	 *	Set cancel status
	 *
	 *	@param	User		$user		Object user that modify
	 *  @param	int<0,1>	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>				Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/* if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo','quickmemo_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'QUICKMEMO_MEMO_CANCEL');
	}

	/**
	 *	Set back to validated status
	 *
	 *	@param	User		$user			Object user that modify
	 *  @param	int<0,1>	$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *	@return	int<-1,1>					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((!getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo','write'))
		 || (getDolGlobalInt('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('quickmemo','quickmemo_advance','validate'))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'QUICKMEMO_MEMO_REOPEN');
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param	array<string,string> 	$params 	Params to construct tooltip data
	 * @since 	v18
	 * @return	array{optimize?:string,picto?:string,ref?:string}
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$datas = [];

		if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowMemo")];
		}
		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Memo").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *  @param	int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param	string  $option                     On what the link point to ('nolink', ...)
	 *  @param	int     $notooltip                  1=Disable tooltip
	 *  @param	string  $morecss                    Add more css on link
	 *  @param	int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
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
			'id' => (string) $this->id,
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

		$baseurl = dol_buildpath('/quickmemo/memo_card.php', 1);
		$query = ['id' => $this->id];
		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$query = array_merge($query, ['save_lastsearch_values' => 1]);
			}
		}
		$url = dolBuildUrl($baseurl, $query);

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMemo");
				$linkclose .= ' alt="'.dolPrintHTMLForAttribute($label).'"';
			}
			$linkclose .= ($label ? ' title="'.dolPrintHTMLForAttribute($label).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink') {
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
	 *	Return a thumb for kanban views
	 *
	 *	@param	string	    			$option		Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	?array<string,mixed>	$arraydata	Array of data
	 *  @return	string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $conf, $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$isDarkMode = !empty($this->color) && !colorIsLight($this->color) ? '--memo-is-dark' : '';
		$style = !empty($this->color) && Memo::checkColor($this->color) ? ' style="--memo-color : '.$this->color.'" ' : '';
		$return .= '<div class="info-box-quickmemo '.$isDarkMode.'" '.$style.' >';
		$return .= '<div class="info-box-content-quickmemo">';

		$return .= '<span class="info-box-status">'.$this->getLibStatut(4).'</span>';

		//      $return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}



		$return .= ' <div class="info-box-content-quicknote">';
		$return .= nl2br(htmlentities($this->quick_note));
		$return .= '</div>';

		$return .= '<div class="quickmemo-info" >';
		$return .= '	<div class="quickmemo-info__create" >';
		$return .= '		<span class="quickmemo-info__user-create-name">'.$this->showOutputField($this->fields['fk_user_creat'], 'fk_user_creat', $this->user_creation_id).'</span>';
		$return .= ' 		<span class="quickmemo-info__date_create">'.dol_print_date($this->date_creation, '%d/%m/%Y %H:%M', 'tzuserrel').'</span>';
		$return .= '	</div>';


		if (!empty($this->tms) && $this->tms != $this->date_creation) {
			$return .= '	<div class="quickmemo-info__update" >';

			$return .= ' 		<span class="quickmemo-info__date_update">'.$langs->trans('QuickMemoModified') . ' ' .dol_print_date($this->date_modification, '%d/%m/%Y %H:%M', 'tzuserrel').'</span>';
			if ($this->user_modification_id != $this->user_creation_id) {
				$return .= '		<span class="quickmemo-info__user-update-name">'.$langs->trans('QuickMemoBy') . ' ' .$this->showOutputField($this->fields['fk_user_modif'], 'fk_user_modif', $this->user_modification_id).'</span>';
			}

			$return .= '	</div>';
		}

		if (!empty($this->date_archived) && $this->status == self::STATUS_ARCHIVED) {
			$return .= '	<div class="quickmemo-info__update" >';

			$return .= ' 		<span class="quickmemo-info__date_update">'.$langs->trans('QuickMemoArchived') . ' ' .dol_print_date($this->date_archived, '%d/%m/%Y %H:%M', 'tzuserrel').'</span>';
			if ($this->fk_user_modif != $this->fk_user_archived) {
				$return .= '		<span class="quickmemo-info__user-update-name">'.$langs->trans('QuickMemoBy') . ' ' .$this->showOutputField($this->fields['fk_user_archived'], 'fk_user_archived', $this->fk_user_archived).'</span>';
			}

			$return .= '	</div>';
		}

		$this->date_archived = dol_now();
		$this->fk_user_archived = (int) $this->user->id;


		$return .= '</div>';




		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param	int<0,6>	$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param	int<0,6>	$mode	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string				Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int			$status		Id status
	 *  @param	int<0,6>	$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string					Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (is_null($status)) {
			return '';
		}

		$paramsBadge = array('badgeParams' => array('attr' => array(
			'data-status-element' => $this->element,
			'data-status' => (int) $status
		)));


		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("quickmemo");
			$this->labelStatus[self::STATUS_TPL] = $langs->transnoentitiesnoconv('Template');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_ARCHIVED] = $langs->transnoentitiesnoconv('Archived');
			$this->labelStatusShort[self::STATUS_TPL] = $langs->transnoentitiesnoconv('Template');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_ARCHIVED] = $langs->transnoentitiesnoconv('Archived');
		}

		$statusType = 'status'.$status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_ARCHIVED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode, '', $paramsBadge);
	}

	/**
	 *	Load the info information in the object
	 *
	 *	@param	int		$id       Id of object
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT t.rowid, t.date_creation as datec";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= ", GREATEST(t.tms, te.tms) as datem";
		} else {
			$sql .= ", t.tms as datem";
		}
		if (!empty($this->fields['date_validation'])) {
			$sql .= ", t.date_validation as datev";
		}
		if (!empty($this->fields['fk_user_creat'])) {
			$sql .= ", t.fk_user_creat";
		}
		if (!empty($this->fields['fk_user_modif'])) {
			$sql .= ", t.fk_user_modif";
		}
		if (!empty($this->fields['fk_user_valid'])) {
			$sql .= ", t.fk_user_valid";
		}
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (!empty($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		$sql .= " WHERE t.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if (!empty($this->fields['fk_user_creat'])) {
					$this->user_creation_id = $obj->fk_user_creat;
				}
				if (!empty($this->fields['fk_user_modif'])) {
					$this->user_modification_id = $obj->fk_user_modif;
				}
				if (!empty($this->fields['fk_user_valid'])) {
					$this->user_validation_id = $obj->fk_user_valid;
				}
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
				if (!empty($obj->datev)) {
					$this->date_validation = empty($obj->datev) ? '' : $this->db->jdate($obj->datev);
				}
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialize object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return	int
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		return $this->initAsSpecimenCommon();
	}


	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 *  @return	string      		Object free reference
	 */
	public function getNextNumRef()
	{
		return '';
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	string		$modele			Force template to use ('' to not force)
	 *  @param	Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param	int<0,1>	$hidedetails    Hide details of lines
	 *  @param	int<0,1>	$hidedesc       Hide description
	 *  @param	int<0,1>	$hideref        Hide ref
	 *  @param	?array<string,string>  $moreparams     Array to provide more information
	 *  @return	int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		return 0;
	}

	/**
	 * Vérifie si une couleur est un code hex valide
	 *
	 * @param mixed $color the color to check
	 * @return bool
	 */
	public static function checkColor($color)
	{
		if (!is_string($color)) {
			return false;
		}

		// Remove spaces at the beginning/end
		$color = trim($color);

		// Vérifie #fff ou #ffffff
		return preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/', $color) === 1;
	}

	/**
	 * Get color preset
	 *
	 * @return string[]
	 */
	public static function getColorPreset()
	{

		$colorsConf = getDolGlobalString('QUICKMEMO_COLORS_PRESET');
		$colors = explode(',', $colorsConf);
		if (!empty($colors)) {
			foreach ($colors as $iColor => $color) {
				if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
					unset($colors[$iColor]);
				}
			}
		}

		if (empty($colors)) {
			$colors = ['#fff8a6', '#ffd6d6', '#d6ffd9', '#d6e6ff', '#f3d6ff', '#ffffff', '#f5f5f5'];
		}

		return array_values($colors);
	}

	/**
	 * Get memo context
	 *
	 * @param string|array<string> $context Context
	 * @param CommonObject|null    $object the common Dolibarr object
	 *
	 * @return mixed|string
	 */
	public static function getMemoContext($context, $object = null)
	{
		global $db,$action, $hookmanager;

		if (!isModEnabled('quickmemo')) {
			return '';
		}

		if (!is_array($context)) {
			$context = explode(':', $context);
		}

		$contextMapping = self::getAvailableMemoContextMapping();

		$memoContext = '';
		foreach ($contextMapping as $key => $values) {
			$values = (array) $values;

			if (array_intersect($context, $values)) {
				$memoContext = $key;
				break;
			}
		}

		$staticMemo = new self($db);

		$hookmanager->initHooks(array($staticMemo->element.'dao'));
		$parameters = array(
			'memoContext' => & $memoContext
		);

		$reshook = $hookmanager->executeHooks('getMemoContext', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		return $memoContext;
	}

	/**
	 * @param array<string, array<string>> $contextTabMapping memo context list and Dolibarr context associated
	 * @param string $tabContext memo context
	 * @param string $dolibarrContext Dolibarr context
	 *
	 * @return void
	 */
	public static function completeMemoContextMapping(array &$contextTabMapping, string $tabContext, string $dolibarrContext = '')
	{
		$dolibarrContext = trim($dolibarrContext) ?: $tabContext;

		if (!isset($contextTabMapping[$tabContext])) {
			$contextTabMapping[$tabContext] = [$dolibarrContext];
			return;
		}

		if (!in_array($dolibarrContext, $contextTabMapping[$tabContext], true)) {
			$contextTabMapping[$tabContext][] = $dolibarrContext;
		}
	}

	/**
	 * Get available memo context
	 *
	 * @param null $object the common Dolibarr object
	 * @param bool $onlyActiveModules on true return only
	 *
	 * @return array<string, array<string>>
	 */
	public static function getAvailableMemoContextMapping($object = null, $onlyActiveModules = true)
	{
		global $db,$action, $hookmanager;

		$contextTabMapping = [];

		// Start Correction of no standard Dolibarr context and special context
		if (isModEnabled('propal') || !$onlyActiveModules) {
			self::completeMemoContextMapping($contextTabMapping, 'contactcard', 'proposalcontactcard');
		}

		if (isModEnabled('supplier_order') || !$onlyActiveModules) {
			self::completeMemoContextMapping($contextTabMapping, 'contactcard', 'ordersuppliercontactcard');
			self::completeMemoContextMapping($contextTabMapping, 'document', 'ordersuppliercarddocument');
			self::completeMemoContextMapping($contextTabMapping, 'agenda', 'ordersuppliercardinfo');
			self::completeMemoContextMapping($contextTabMapping, 'ordersupplierdispatch');
		}

		if (isModEnabled('contract') || !$onlyActiveModules) {
			self::completeMemoContextMapping($contextTabMapping, 'agenda', 'agendacontract');
		}

		if (isModEnabled('order') || !$onlyActiveModules) {
			self::completeMemoContextMapping($contextTabMapping, 'ordershipmentcard');
		}

		if (isModEnabled('shipping')) {
			self::completeMemoContextMapping($contextTabMapping, 'contactcard', 'shipmentcontactcard');
		}

		if (isModEnabled('product')) {
			self::completeMemoContextMapping($contextTabMapping, 'productpricecard');
			self::completeMemoContextMapping($contextTabMapping, 'pricesuppliercard');
			self::completeMemoContextMapping($contextTabMapping, 'producttranslationcard');
			self::completeMemoContextMapping($contextTabMapping, 'productcompositioncard');
			self::completeMemoContextMapping($contextTabMapping, 'stockproductcard');
			self::completeMemoContextMapping($contextTabMapping, 'productstatsinvoice');
			self::completeMemoContextMapping($contextTabMapping, 'productstatscard');
			self::completeMemoContextMapping($contextTabMapping, 'tabproductmarginlist');
		}

		// End of corrections

		// Generate standard context
		// TODO : Common contexts such as document, agenda, contact card, and stat, which are used across most Dolibarr objects, are not defined as standard global contexts.
		//  Instead of having dedicated contexts like global_document, global_agenda, global_contactcard, or global_stat (similar to global_card), pages currently mix global_card with object-specific contexts (e.g. product_document, propal_agenda, etc.).
		//  This creates inconsistent context handling. These contexts should include both their object-specific context and a proper global context (e.g. global_document or global_agenda) depending on the active tab.
		$commonCardContext = ['document', 'agenda', 'contactcard', 'stats']; // for common object
		$modules = ['order', 'propal', 'invoice', 'supplier_proposal', 'supplier_order', 'supplier_invoice', 'contract', 'product', 'shipping'];
		foreach ($modules as $module) {
			if (!isModEnabled($module) && $onlyActiveModules) {
				continue;
			}

			$moduleClean = str_replace('_', '', $module);

			foreach ($commonCardContext as $context) {
				self::completeMemoContextMapping($contextTabMapping, $context, $moduleClean.$context);
			}
		}

		if (!empty($object) && !empty($object->element)) {
			foreach ($commonCardContext as $context) {
				self::completeMemoContextMapping($contextTabMapping, $context, $object->element.$context);
			}
		}

		// Need to be at end of tests
		self::completeMemoContextMapping($contextTabMapping, 'index');
		self::completeMemoContextMapping($contextTabMapping, 'card', 'globalcard');

		$staticMemo = new self($db);
		$hookmanager->initHooks(array($staticMemo->element.'dao'));
		$parameters = array(
			'contextTabMapping' => & $contextTabMapping,
			'onlyActiveModules' => $onlyActiveModules
		);

		$reshook = $hookmanager->executeHooks('getAvailableMemoContextMapping', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			return $hookmanager->resArray;
		}

		return $contextTabMapping;
	}


	/**
	 * Get available memo context
	 *
	 * @return string[]
	 */
	public static function getAvailableMemoContext()
	{
		global $db,$action, $hookmanager;

		$contextMapping = self::getAvailableMemoContextMapping();
		$list = array_keys($contextMapping);

		$staticMemo = new self($db);
		$hookmanager->initHooks(array($staticMemo->element.'dao'));
		$parameters = array(
			'list' => & $list
		);
		$object = null;  // @phan-suppress-next-line PhanPluginConstantVariableNull
		$reshook = $hookmanager->executeHooks('getAvailableMemoContext', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$list = $hookmanager->resArray;
		}

		// Security check
		$result = [];
		foreach ($list as $k => $v) {
			if (!is_string($v)) {
				continue;
			}
			if (strlen($v) > 64) {
				continue;
			}
			if (preg_match('/^[a-zA-Z0-9_]+$/', $k) !== 1) {
				continue;
			}
			$result[] = $v;
		}

		return $result;
	}


	/**
	 * Count archived memo query
	 *
	 * @param     string $element_type  Type of element
	 * @param     int    $element_id    Id of element
	 * @param     string $context       Context
	 * @param     int    $id            Id
	 *
	 * @return int|false
	 */
	public function countArchivedMemoQuery($element_type, $element_id, $context, $id = 0)
	{
		global $user;

		$sql = 'SELECT COUNT(m.rowid) nb
			FROM '.MAIN_DB_PREFIX.$this->table_element.' m
			WHERE m.element_type = \''.$this->db->escape($element_type).'\'
			  AND ( m.fk_element = '.(int) $element_id.' OR m.shared_on_element = 1 )
			  AND m.context_tab = \''.$this->db->escape($context).'\'
			  AND m.status = '.Memo::STATUS_ARCHIVED.'
			  AND (m.private = 0 OR m.fk_user_creat = '.(int) $user->id.')
       ';

		if ((int) $id > 0) {
			$sql .= ' AND m.rowid = ' . (int) $id;
		}

		$obj = $this->db->getRow($sql);
		if (!$obj) {
			return false;
		}

		return (int) $obj->nb;
	}

	/**
	 * Get memos query
	 *
	 * @param     string $element_type  Type of element
	 * @param     int    $element_id    Id of element
	 * @param     string $context       Context
	 * @param     int    $id            Id
	 *
	 * @return string
	 */
	public function getMemosQuery($element_type, $element_id, $context, $id = 0)
	{
		global $user;

		$sql = 'SELECT m.rowid, m.quick_note, m.pos_x, m.pos_y, m.pos_w, m.pos_h, m.pos_z, m.color, m.fk_user_creat, m.fk_user_modif, m.shared_on_element, m.date_creation, m.tms, m.private,
               mu.pos_x as user_pos_x, mu.pos_y as user_pos_y, mu.pos_w as user_pos_w, mu.pos_h as user_pos_h, mu.pos_z as user_pos_z
        FROM '.MAIN_DB_PREFIX.$this->table_element.' m
        LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element.'_user mu
            ON mu.fk_memo = m.rowid
            AND mu.fk_user = '.(int) $user->id.'
        WHERE m.element_type = \''.$this->db->escape($element_type).'\'
          AND ( m.fk_element = '.(int) $element_id.' OR m.shared_on_element = 1 )
          AND m.context_tab = \''.$this->db->escape($context).'\'
          AND m.status = '.self::STATUS_VALIDATED.'
          AND (COALESCE(m.private, 0) = 0 OR m.fk_user_creat = '.(int) $user->id.')
       ';

		if ((int) $id > 0) {
			$sql .= ' AND m.rowid = ' . (int) $id;
		}

		$sql .= ' ORDER BY COALESCE(mu.pos_z, m.pos_z) ASC';
		return $sql;
	}


	/**
	 * Get template memos query
	 *
	 * @param     string $element_type  Type of element
	 * @param     string $context_tab       Context tab
	 * @param     int    $id            Id
	 *
	 * @return string
	 */
	public function getTemplateMemosQuery($element_type, $context_tab = '', $id = 0)
	{
		global $user;

		$sql = 'SELECT m.rowid, m.quick_note, m.pos_x, m.pos_y, m.pos_w, m.pos_h, m.color, m.pos_z,
       			m.fk_user_creat, m.fk_user_modif, m.shared_on_element, m.date_creation, m.tms, m.private, m.name_tpl,m.rank_tpl,
               	mu.pos_x as user_pos_x, mu.pos_y as user_pos_y, mu.pos_w as user_pos_w, mu.pos_h as user_pos_h, mu.pos_z as user_pos_z
        FROM '.MAIN_DB_PREFIX.$this->table_element.' m
        LEFT JOIN '.MAIN_DB_PREFIX.$this->table_element.'_user mu
            ON mu.fk_memo = m.rowid
            AND mu.fk_user = '.(int) $user->id.'
        WHERE m.element_type IN (\''.$this->db->escape($element_type).'\', \'\')
          AND m.status = '.self::STATUS_TPL.'
          AND (COALESCE(m.private_tpl, 0) = 0 OR m.fk_user_creat = '.(int) $user->id.')
       ';

		if (!empty($context_tab)) {
			//$sql.= ' AND (m.context_tab = \''.$this->db->escape($context_tab).'\' OR m.element_type = \'\' ) ';
		}

		if ((int) $id > 0) {
			$sql .= ' AND m.rowid = ' . (int) $id;
		}

		$sql .= " ORDER BY m.rank_tpl DESC, m.rowid ASC  "; // Due to multiple type of sort panel  rank_tpl is reversed higher is first and  rowid ASC to keep last add is last

		return $sql;
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$val	Array of properties of field to show
	 * @param  string  			$key            	Key of attribute
	 * @param  string|int|null  	$value          	Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  			$moreparam      	To add more parameters on html tag
	 * @param  string  			$keysuffix      	Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  			$keyprefix      	Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   			$morecss        	Value for CSS to use (Old usage: May also be a numeric to define a size).
	 * @return string
	 */
	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs, $form;

		if ($key == 'color' && self::checkColor($value)) {
			$badgeBase = colorIsLight($value) ? 'badge-light' : 'badge-dark';
			return '<span class="badge badge-pill '.$badgeBase.'" style="background-color: '.$value.'" >'.$value.'</span>';
		}

		if ($key == 'quick_note') {
			return nl2br(htmlentities($value));
		}

		return parent::showOutputField($val, $key, $value, $moreparam, $keysuffix, $keyprefix, $morecss);
	}

	/**
	 * Load the Javascript interface for QuickMemo
	 *
	 * @param  array<string|mixed> $jsConfVars  Configuration variables
	 * @return bool
	 */
	public static function loadQuickMemoJsInterface($jsConfVars)
	{
		global $user;

		if (!isModEnabled('quickmemo')) {
			return false;
		}

		// DO NOT LOAD TWICE
		if (defined('QUICKMEMOJS')) {
			return false;
		}

		if (defined('NOREQUIRETRAN') || empty($user) || empty($user->id) || (int) $user->socid > 0) {
			return false;
		}

		if (!$user->hasRight('quickmemo', 'memo', 'read')) {
			return false;
		}

		$defaultJsConfVars = [
			'interfaceUrl' => dol_buildpath('quickmemo/interface.php', 1),
			'archivesUrl' => dol_buildpath('quickmemo/memo_list.php', 1) . '?mode=kanban&search_status='.Memo::STATUS_ARCHIVED . ($jsConfVars['archivesUrlParams'] ?? ''),
			'elementId' => 0,
			'elementType' => '',
			'context' => null,
			'token' => newToken(),
			'colors' => Memo::getColorPreset(),
			'userReadRight' => $user->hasRight('quickmemo', 'memo', 'read'),
			'userWriteRight' => $user->hasRight('quickmemo', 'memo', 'write'),
			'userDeleteRight' => $user->hasRight('quickmemo', 'memo', 'delete')
		];
		$jsConfVars = array_merge($defaultJsConfVars, $jsConfVars);

		if (!empty($jsConfVars['elementType'])) {
			$jsConfVars['shareBtnStatus'] = 1;
		}

		// LOAD Memo class
		print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('quickmemo/css/memo.css', 1) . '">'."\n";
		print '<link rel="stylesheet" type="text/css" href="'.dol_buildpath('quickmemo/css/memo-dialog.css', 1) . '">'."\n";
		print '<script nonce="'.getNonce().'" src="'.dol_buildpath('quickmemo/js/QuickMemo.js', 1).'" ></script>'."\n";

		print '<script nonce="'.getNonce().'">
		document.addEventListener(\'Dolibarr:Ready\', function() {
			if(!Dolibarr.checkToolExist(\'quickMemo\')) {
				Dolibarr.defineTool(\'quickMemo\', new QuickMemo('.json_encode($jsConfVars).') );
			}
		});
		</script>'."\n";

		define('QUICKMEMOJS', true);
		return true;
	}

	/**
	 * Get JS memo
	 *
	 * @param User $currentUser Current user
	 *
	 * @return stdClass
	 */
	public function getJsMemo(User $currentUser)
	{
		global $langs;

		$memo = self::getJsMemoDefault();
		$memo->id = $this->id;
		$memo->color = $this->color;
		$memo->note = $this->quick_note;

		$memo->pos_x = (int) $this->pos_x;
		$memo->pos_y = (int) $this->pos_y;
		$memo->pos_w = (int) $this->pos_w;
		$memo->pos_h = (int) $this->pos_h;

		if ($currentUser->id != $memo->fk_user_creat) {
			// TODO update positions
		}

		$memo->shared_on_element = $this->shared_on_element;
		$memo->private = (int) $this->private;
		$memo->date_creation = dol_print_date($this->date_creation, '%d/%m/%Y %H:%M', 'tzuserrel');
		$memo->date_change =  '';
		if (!empty($this->tms) && ((int) $this->date_creation !== (int) $this->tms || (int) $this->fk_user_modif > 0)) {
			$memo->date_change = dol_print_date($this->tms, '%d/%m/%Y %H:%M', 'tzuserrel');
		}

		$memo->fk_user_creat = $this->fk_user_creat;
		$memo->user_name = '';
		if ((int) $this->fk_user_creat > 0) {
			$userCreate = new User($this->db);
			if ($userCreate->fetch((int) $this->fk_user_creat) > 0) {
				$memo->user_name = $userCreate->getFullName($langs);
			}
		}

		$memo->fk_user_modif = $this->fk_user_modif;
		$memo->user_change_name = '';
		if ((int) $this->fk_user_modif > 0) {
			$userMod = new User($this->db);
			if ($userMod->fetch((int) $this->fk_user_modif) > 0) {
				$memo->user_change_name = $userMod->getFullName($langs);
			}
		}

		return $memo;
	}

	/**
	 * Get JS memo default
	 *
	 * @return stdClass
	 */
	public static function getJsMemoDefault()
	{
		$memo = new stdClass();
		$memo->id = null;
		$memo->color = null;
		$memo->note = null;
		$memo->pos_x = 0;
		$memo->pos_y = 0;
		$memo->pos_w = 0;
		$memo->pos_h = 0;
		$memo->shared_on_element = 0;
		$memo->private = 0;
		$memo->date_creation = '';
		$memo->date_change =  '';
		$memo->fk_user_creat = null;
		$memo->user_name = '';
		$memo->fk_user_modif = null;
		$memo->user_change_name = '';
		return $memo;
	}
}
