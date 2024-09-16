<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020  Lenin Rivas		   <lenin@leninrivas.com>
 * Copyright (C) 2023-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 * \file        mrp/class/moline.class.php
 * \ingroup     mrp
 * \brief       This file is a CRUD class file for Mo lines (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

/**
 * Class MoLine. You can also remove this and generate a CRUD class for lines objects.
 */
class MoLine extends CommonObjectLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'mrp_production';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'mrp_production';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'mo';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_mo';

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

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'ID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'fk_mo' => array('type' => 'integer', 'label' => 'Mo', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 15),
		'origin_id' => array('type' => 'integer', 'label' => 'Origin', 'enabled' => 1, 'visible' => -1, 'notnull' => 0, 'position' => 17),
		'origin_type' => array('type' => 'varchar(10)', 'label' => 'Origin type', 'enabled' => 1, 'visible' => -1, 'notnull' => 0, 'position' => 18),
		'position' => array('type' => 'integer', 'label' => 'Position', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 20),
		'fk_product' => array('type' => 'integer', 'label' => 'Product', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 25),
		'fk_warehouse' => array('type' => 'integer', 'label' => 'Warehouse', 'enabled' => 1, 'visible' => -1, 'position' => 30),
		'qty' => array('type' => 'real', 'label' => 'Qty', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 35),
		'qty_frozen' => array('type' => 'smallint', 'label' => 'QuantityFrozen', 'enabled' => 1, 'visible' => 1, 'default' => '0', 'notnull' => 1, 'position' => 105, 'css' => 'maxwidth50imp', 'help' => 'QuantityConsumedInvariable'),
		'disable_stock_change' => array('type' => 'smallint', 'label' => 'DisableStockChange', 'enabled' => 1, 'visible' => 1, 'default' => '0', 'notnull' => 1, 'position' => 108, 'css' => 'maxwidth50imp', 'help' => 'DisableStockChangeHelp'),
		'batch' => array('type' => 'varchar(30)', 'label' => 'Batch', 'enabled' => 1, 'visible' => -1, 'position' => 140),
		'role' => array('type' => 'varchar(10)', 'label' => 'Role', 'enabled' => 1, 'visible' => -1, 'position' => 145),
		'fk_mrp_production' => array('type' => 'integer', 'label' => 'Fk mrp production', 'enabled' => 1, 'visible' => -1, 'position' => 150),
		'fk_stock_movement' => array('type' => 'integer', 'label' => 'StockMovement', 'enabled' => 1, 'visible' => -1, 'position' => 155),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 160),
		'tms' => array('type' => 'timestamp', 'label' => 'Tms', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 165),
		'fk_user_creat' => array('type' => 'integer', 'label' => 'UserCreation', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 170),
		'fk_user_modif' => array('type' => 'integer', 'label' => 'UserModification', 'enabled' => 1, 'visible' => -1, 'position' => 175),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -1, 'position' => 180),
		'fk_default_workstation' => array('type' => 'integer', 'label' => 'DefaultWorkstation', 'enabled' => 1, 'visible' => 1, 'notnull' => 0, 'position' => 185),
		'fk_unit' => array('type' => 'int', 'label' => 'Unit', 'enabled' => 1, 'visible' => 1, 'notnull' => 0, 'position' => 186)
	);

	/**
	 * @var int
	 */
	public $rowid;
	/**
	 * @var int
	 */
	public $fk_mo;
	/**
	 * @var int
	 */
	public $origin_id;
	/**
	 * @var string
	 */
	public $origin_type;
	/**
	 * @var int
	 */
	public $position;
	/**
	 * @var int
	 */
	public $fk_product;
	/**
	 * @var int
	 */
	public $fk_warehouse;

	/**
	 * @var float Quantity
	 */
	public $qty;

	/**
	 * @var float Quantity frozen
	 */
	public $qty_frozen;
	/**
	 * @var int<0,1>
	 */
	public $disable_stock_change;
	/**
	 * @var float|int
	 */
	public $efficiency;

	/**
	 * @var string batch reference
	 */
	public $batch;
	/**
	 * @var string
	 */
	public $role;
	/**
	 * @var int
	 */
	public $fk_mrp_production;
	/**
	 * @var int
	 */
	public $fk_stock_movement;
	/**
	 * @var string
	 */
	public $import_key;
	/**
	 * @var int
	 */
	public $fk_parent_line;
	/**
	 * @var ?int
	 */
	public $fk_unit;

	/**
	 * @var int Service Workstation
	 */
	public $fk_default_workstation;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		$this->ismultientitymanaged = 0;
		$this->isextrafieldmanaged = 1;

		if (!getDolGlobalString('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
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
	 * @param  User		$user		User that creates
	 * @param  int<0,1> $notrigger	0=launch triggers after, 1=disable triggers
	 * @return int<-1,1>			Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		if (empty($this->qty)) {
			$this->error = 'ErrorEmptyValueForQty';
			return -1;
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int		$id		Id object
	 * @param string	$ref	Ref
	 * @return int<-1,1>		Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      	$sortorder    	Sort Order
	 * @param  string      	$sortfield    	Sort field
	 * @param  int         	$limit        	limit
	 * @param  int         	$offset       	Offset
	 * @param  string|array $filter       	Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param  string      	$filtermode   	Filter mode (AND or OR)
	 * @return array|int                 	int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList();
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN ('.getEntity($this->element).')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}

		// Deprecated.
		if (is_array($filter)) {
			$sqlwhere = array();
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if ($key == 't.rowid') {
						$sqlwhere[] = $key." = ".((int) $value);
					} elseif (strpos($key, 'date') !== false) {
						$sqlwhere[] = $key." = '".$this->db->idate($value)."'";
					} else {
						$sqlwhere[] = $key." LIKE '%".$this->db->escape($this->db->escapeforlike($value))."%'";
					}
				}
			}
			if (count($sqlwhere) > 0) {
				$sql .= ' AND ('.implode(' '.$this->db->escape($filtermode).' ', $sqlwhere).')';
			}

			$filter = '';
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
	 * @param  User		$user		User that modifies
	 * @param  int<0,1> $notrigger	0=launch triggers after, 1=disable triggers
	 * @return int<-1,1>			Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User		$user       User that deletes
	 * @param int<0,1> 	$notrigger  0=launch triggers after, 1=disable triggers
	 * @return int<-1,1>			Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}
}
