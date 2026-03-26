<?php
/* Copyright (C) 2024       AWeerWolf
 * Copyright (C) 2026       Alexandre Spangaro		<alexandre@inovea-conseil.com>
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
 * \file    accountancy/class/bookkeepingtemplateline.class.php
 * \ingroup accountancy
 * \brief   This file is a CRUD class file for BookkeepingTemplateLine (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for BookkeepingTemplateLine
 */
class BookkeepingTemplateLine extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'accountancy';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'accounting_transaction_template_det';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'accounting_transaction_template_det';

	/**
	 * @var int<0,1>|string     0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int<0,1>            Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for bookkeepingtemplateline. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'bookkeepingtemplateline@accountancy' if picto is file 'img/object_bookkeepingtemplateline.png'.
	 */
	public $picto = 'fa-file';

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'langfile' the key of the language file for translation.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwritten by the Setup of Default Values if the field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,langfile?:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,cssview?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>|string,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>,searchmulti?:int<0,1>}>    Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type"=>"integer", "label"=>"TechnicalID", "enabled"=>1, 'position'=>1, 'notnull'=>1, "visible"=>0, "noteditable"=>1, "index"=>1, "css"=>"left", "comment"=>"Id"),
		"fk_transaction_template" => array("type"=>"integer", "label"=>"Template", "enabled"=>1, 'position'=>5, 'notnull'=>1, "visible"=>0, "noteditable"=>1, "foreignkey"=>"accounting_transaction_template.rowid"),
		"general_account" => array("type"=>"varchar(32)", "label"=>"AccountNumber", "enabled"=>1, 'position'=>10, 'notnull'=>1, "visible"=>1, "css"=>"minwidth100"),
		"general_label" => array("type"=>"varchar(255)", "label"=>"AccountLabel", "enabled"=>1, 'position'=>20, 'notnull'=>1, "visible"=>1, "css"=>"minwidth200"),
		"subledger_account" => array("type"=>"varchar(32)", "label"=>"SubledgerAccount", "enabled"=>1, 'position'=>30, 'notnull'=>0, "visible"=>1, "css"=>"minwidth100"),
		"subledger_label" => array("type"=>"varchar(255)", "label"=>"SubledgerLabel", "enabled"=>1, 'position'=>40, 'notnull'=>0, "visible"=>1, "css"=>"minwidth200"),
		"operation_label" => array("type"=>"varchar(255)", "label"=>"OperationLabel", "enabled"=>1, 'position'=>50, 'notnull'=>0, "visible"=>1, "css"=>"minwidth200"),
		"debit" => array("type"=>"double(24,8)", "label"=>"Debit", "enabled"=>1, 'position'=>60, 'notnull'=>0, "visible"=>1, "css"=>"maxwidth75 right"),
		"credit" => array("type"=>"double(24,8)", "label"=>"Credit", "enabled"=>1, 'position'=>70, 'notnull'=>0, "visible"=>1, "css"=>"maxwidth75 right"),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int Foreign key to accounting transaction template
	 */
	public $fk_transaction_template;

	/**
	 * @var string General account number
	 */
	public $general_account;

	/**
	 * @var string|null General account label/description
	 */
	public $general_label;

	/**
	 * @var string|null Subledger account number (auxiliary account)
	 */
	public $subledger_account;

	/**
	 * @var string|null Subledger account label/description
	 */
	public $subledger_label;

	/**
	 * @var ?string label operation
	 */
	public $operation_label;

	/**
	 * @var string Debit amount (stored as string for precision)
	 */
	public $debit;

	/**
	 * @var string Credit amount (stored as string for precision)
	 */
	public $credit;

	/**
	 * @var string|null Sense/direction (D for debit, C for credit)
	 */
	public $sens;

	/**
	 * @var int|null Line position/order
	 */
	public $position;

	/**
	 * @var integer|''|null		Creation date
	 */
	public $date_creation;

	/**
	 * @var int
	 */
	public $tms;

	/**
	 * @var int|null User ID who created this line
	 */
	public $fk_user_creat;

	/**
	 * @var int|null User ID who last modified this line
	 */
	public $fk_user_modif;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
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
	 * @param   User    $user       User that creates
	 * @param   int     $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                 Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		$resultcreate = $this->createCommon($user, $notrigger);
		return $resultcreate;
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param  int    $id             Id object
	 * @param  int    $noextrafields  0=Default to load extrafields, 1=No extrafields
	 * @return int                    Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $noextrafields = 0)
	{
		$result = $this->fetchCommon($id, '', '', $noextrafields);
		return $result;
	}

	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param  string                      $sortorder  Sort Order
	 * @param  string                      $sortfield  Sort field
	 * @param  int                         $limit      limit
	 * @param  int                         $offset     Offset
	 * @param  array<string,mixed>         $filter     Filter array. Example array('mystringfield'=>'value', 'myintfield'=>4, 'customsql'=>...)
	 * @param  string                      $filtermode Filter mode (AND or OR)
	 * @return BookkeepingTemplateLine[]|int           Array of BookkeepingTemplateLine objects if OK, <0 if KO
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = "SELECT ";
		$sql .= $this->getFieldList('t');
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if (isset($this->isextrafieldmanaged) && $this->isextrafieldmanaged == 1) {
			$sql .= " LEFT JOIN ".$this->db->prefix().$this->table_element."_extrafields as te ON te.fk_object = t.rowid";
		}
		$sql .= " WHERE 1 = 1";

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key === 'customsql') {
					// Never use 'customsql' with a value from user input since it is injected as is. The value must be hard coded.
					$sqlwhere[] = $value;
					continue;
				}

				$columnName = preg_replace('/^t\./', '', $key);

				if (isset($this->fields[$columnName])) {
					$type = $this->fields[$columnName]['type'];
					if (preg_match('/^integer/', $type)) {
						if (is_int($value)) {
							// single value
							$sqlwhere[] = $key . " = " . intval($value);
						} elseif (is_array($value)) {
							if (empty($value)) {
								continue;
							}
							$sqlwhere[] = $key . ' IN (' . $this->db->sanitize(implode(',', array_map('intval', $value))) . ')';
						}
						continue;
					} elseif (in_array($type, array('date', 'datetime', 'timestamp'))) {
						$sqlwhere[] = $key . " = '" . $this->db->idate($value) . "'";
						continue;
					}
				}

				// when the $key doesn't fall into the previously handled categories, we do as if the column were a varchar/text
				if (is_array($value) && count($value)) {
					$escapedValues = array();
					foreach ($value as $v) {
						$escapedValues[] = $this->db->escape($v);
					}
					$value = implode(',', $escapedValues);
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($value, 1) . ')';
				} elseif (is_scalar($value)) {
					if (strpos($value, '%') === false) {
						$sqlwhere[] = $key . " = '" . $this->db->sanitize($this->db->escape($value)) . "'";
					} else {
						$sqlwhere[] = $key . " LIKE '%" . $this->db->escape($this->db->escapeforlike($value)) . "%'";
					}
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= " AND (".implode(" ".$filtermode." ", $sqlwhere).")";
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
	 * @param   User    $user       User that modifies
	 * @param   int     $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                 Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param   User    $user       User that deletes
	 * @param   int     $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                 Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		return $this->deleteCommon($user, $notrigger);
	}
}
