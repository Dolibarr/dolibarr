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
	 * @var int Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * @var string String with name of icon for bookkeepingtemplateline. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'bookkeepingtemplateline@accountancy' if picto is file 'img/object_bookkeepingtemplateline.png'.
	 */
	public $picto = 'fa-file';

	/**
	 * @var array Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type"=>"integer", "label"=>"TechnicalID", "enabled"=>"1", 'position'=>1, 'notnull'=>1, "visible"=>"0", "noteditable"=>"1", "index"=>"1", "css"=>"left", "comment"=>"Id"),
		"fk_transaction_template" => array("type"=>"integer", "label"=>"Template", "enabled"=>"1", 'position'=>5, 'notnull'=>1, "visible"=>"0", "foreignkey"=>"accounting_transaction_template.rowid"),
		"general_account" => array("type"=>"varchar(32)", "label"=>"AccountNumber", "enabled"=>"1", 'position'=>10, 'notnull'=>1, "visible"=>"1", "css"=>"minwidth100"),
		"general_label" => array("type"=>"varchar(255)", "label"=>"AccountLabel", "enabled"=>"1", 'position'=>20, 'notnull'=>1, "visible"=>"1", "css"=>"minwidth200"),
		"subledger_account" => array("type"=>"varchar(32)", "label"=>"SubledgerAccount", "enabled"=>"1", 'position'=>30, 'notnull'=>0, "visible"=>"1", "css"=>"minwidth100"),
		"subledger_label" => array("type"=>"varchar(255)", "label"=>"SubledgerLabel", "enabled"=>"1", 'position'=>40, 'notnull'=>0, "visible"=>"1", "css"=>"minwidth200"),
		"operation_label" => array("type"=>"varchar(255)", "label"=>"OperationLabel", "enabled"=>"1", 'position'=>50, 'notnull'=>0, "visible"=>"1", "css"=>"minwidth200"),
		"debit" => array("type"=>"double(24,8)", "label"=>"Debit", "enabled"=>"1", 'position'=>60, 'notnull'=>0, "visible"=>"1", "css"=>"maxwidth75 right"),
		"credit" => array("type"=>"double(24,8)", "label"=>"Credit", "enabled"=>"1", 'position'=>70, 'notnull'=>0, "visible"=>"1", "css"=>"maxwidth75 right"),
	);

	public $rowid;
	public $fk_transaction_template;
	public $general_account;
	public $general_label;
	public $subledger_account;
	public $subledger_label;
	public $operation_label;
	public $debit;
	public $credit;


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
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
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
	 * @param  string $sortorder  Sort Order
	 * @param  string $sortfield  Sort field
	 * @param  int    $limit      limit
	 * @param  int    $offset     Offset
	 * @param  array  $filter     Filter array. Example array('mystringfield'=>'value', 'myintfield'=>4, 'customsql'=>...)
	 * @param  string $filtermode Filter mode (AND or OR)
	 * @return array|int          int <0 if KO, array of pages if OK
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
					$value = implode(',', array_map(function ($v) {
						return "'" . $this->db->sanitize($this->db->escape($v)) . "'";
					}, $value));
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($value, true) . ')';
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
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param  User $user      User that deletes
	 * @param  bool $notrigger false=launch triggers, true=disable triggers
	 * @return int             Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
	}
}

