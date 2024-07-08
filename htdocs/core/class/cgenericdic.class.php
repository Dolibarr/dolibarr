<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2016       Florian Henry       <florian.henry@atm-consulting.fr>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file    htdocs/core/class/cgenericdic.class.php
 * \ingroup core
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commondict.class.php';

/**
 * Class CGenericDic
 */
class CGenericDic extends CommonDict
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'undefined';	// Will be defined into constructor

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'undefined';	// Will be defined into constructor

	/**
	 * @var CtyperesourceLine[] Lines
	 */
	public $lines = array();

	public $code;

	/**
	 * @var string Label
	 */
	public $label;

	public $active;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		// Don't forget to set this->element and this->table_element after the construction
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      	User that creates
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$fieldlabel = 'label';
		if ($this->table_element == 'c_stcomm') {
			$fieldlabel = 'libelle';
		} elseif ($this->table_element == 'c_type_fees') {
			$fieldrowid = 'id';
		}

		$error = 0;

		// Clean parameters

		if (isset($this->code)) {
			$this->code = trim($this->code);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}

		// Insert request
		$sql = 'INSERT INTO '.$this->db->prefix().$this->table_element.'(';
		$sql .= 'code,';
		$sql .= $fieldlabel;
		$sql .= 'active';
		$sql .= ') VALUES (';
		$sql .= ' '.(!isset($this->code) ? 'NULL' : "'".$this->db->escape($this->code)."'").',';
		$sql .= ' '.(!isset($this->label) ? 'NULL' : "'".$this->db->escape($this->label)."'").',';
		$sql .= ' '.(!isset($this->active) ? 'NULL' : $this->active);
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);

			// Uncomment this and change CTYPERESOURCE to your own tag if you
			// want this action to call a trigger.
			//if (!$notrigger) {

			//  // Call triggers
			//  $result=$this->call_trigger('CTYPERESOURCE_CREATE',$user);
			//  if ($result < 0) $error++;
			//  // End call triggers
			//}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 * @param string $code code
	 * @param string $label Label
	 *
	 * @return int Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $code = '', $label = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$fieldrowid = 'rowid';
		$fieldlabel = 'label';
		if ($this->table_element == 'c_stcomm') {
			$fieldrowid = 'id';
			$fieldlabel = 'libelle';
		} elseif ($this->table_element == 'c_type_fees') {
			$fieldrowid = 'id';
		}

		$sql = "SELECT";
		$sql .= " t.".$fieldrowid.",";
		$sql .= " t.code,";
		$sql .= " t.".$fieldlabel." as label,";
		$sql .= " t.active";
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		if ($id) {
			$sql .= " WHERE t.".$fieldrowid." = ".((int) $id);
		} elseif ($code) {
			$sql .= " WHERE t.code = '".$this->db->escape($code)."'";
		} elseif ($label) {
			$sql .= " WHERE t.label = '".$this->db->escape($label)."'";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->$fieldrowid;

				$this->code = $obj->code;
				$this->label = $obj->label;
				$this->active = $obj->active;
			}

			// Retrieve all extrafields for invoice
			// fetch optionals attributes and labels
			// $this->fetch_optionals();

			// $this->fetch_lines();

			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string 		$sortorder 		Sort Order
	 * @param string 		$sortfield 		Sort field
	 * @param int    		$limit     		Limit
	 * @param int    		$offset    		offset limit
	 * @param  array		$filter       	Filter as an Universal Search string.
	 * 										Example: $filter['uss'] =
	 * @param string 		$filtermode 	filter mode (AND or OR)
	 * @return int 							Return integer <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		if (isset($filter['customsql'])) {
			trigger_error(__CLASS__ .'::'.__FUNCTION__.' customsql in filter is now forbidden, please use $filter["uss"]="xx:yy:zz" with Universal Search String instead', E_USER_ERROR);
		}
		$filter = $filter['uss'] ?? "";

		$fieldrowid = 'rowid';
		$fieldlabel = 'label';
		if ($this->table_element == 'c_stcomm') {
			$fieldrowid = 'id';
			$fieldlabel = 'libelle';
		} elseif ($this->table_element == 'c_type_fees') {
			$fieldrowid = 'id';
		}

		$sql = "SELECT";
		$sql .= " t.".$this->db->sanitize($fieldrowid).",";
		$sql .= " t.code,";
		$sql .= " t.".$this->db->sanitize($fieldlabel)." as label,";
		$sql .= " t.active";
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";

		// Manage filter
		if (is_array($filter)) {
			$sqlwhere = array();
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					$sqlwhere[] = $this->db->sanitize($key)." LIKE '%".$this->db->escape($value)."%'";
				}
			}
			if (count($sqlwhere) > 0) {
				$sql .= " WHERE ".implode(' '.$this->db->escape($filtermode).' ', $sqlwhere);
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

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id = $obj->$fieldrowid;

				$line->code = $obj->code;
				$line->label = $obj->label;
				$line->active = $obj->active;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      	User that modifies
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$fieldrowid = 'rowid';
		$fieldlabel = 'label';
		if ($this->table_element == 'c_stcomm') {
			$fieldrowid = 'id';
			$fieldlabel = 'libelle';
		} elseif ($this->table_element == 'c_type_fees') {
			$fieldrowid = 'id';
		}

		// Clean parameters

		if (isset($this->code)) {
			$this->code = trim($this->code);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = "UPDATE ".$this->db->prefix().$this->table_element.' SET';
		$sql .= " code = ".(isset($this->code) ? "'".$this->db->escape($this->code)."'" : "null").',';
		$sql .= " ".$fieldlabel." = ".(isset($this->label) ? "'".$this->db->escape($this->label)."'" : "null").',';
		$sql .= " active = ".(isset($this->active) ? $this->active : "null");
		$sql .= " WHERE ".$fieldrowid." = ".((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		// Uncomment this and change CTYPERESOURCE to your own tag if you
		// want this action calls a trigger.
		//if (!$error && !$notrigger) {

		//  // Call triggers
		//  $result=$this->call_trigger('CTYPERESOURCE_MODIFY',$user);
		//  if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
		//  // End call triggers
		//}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User 	$user      	User that deletes
	 * @param int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$fieldrowid = 'rowid';

		$error = 0;

		$this->db->begin();

		// Uncomment this and change CTYPERESOURCE to your own tag if you
		// want this action calls a trigger.
		//if (!$error && !$notrigger) {

		//  // Call triggers
		//  $result=$this->call_trigger('CTYPERESOURCE_DELETE',$user);
		//  if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
		//  // End call triggers
		//}

		// If you need to delete child tables to, you can insert them here

		if (!$error) {
			$sql = "DELETE FROM ".$this->db->prefix().$this->table_element;
			$sql .= " WHERE ".$fieldrowid." = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user		User making the clone
	 * @param   int     $fromid     Id of object to clone
	 * @return  int                 New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$object = new Ctyperesource($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();

			return $object->id;
		} else {
			$this->db->rollback();

			return -1;
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
		$this->id = 0;

		$this->code = 'CODE';
		$this->label = 'Label';
		$this->active = 1;

		return 1;
	}
}
