<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2016       Florian Henry       <florian.henry@atm-consulting.fr>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * \file    htdocs/core/class/ctyperesource.class.php
 * \ingroup resource
 */

/**
 * Class Ctyperesource
 *
 * Put here description of your class
 *
 * @see CommonObject
 */
class Ctyperesource
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'ctyperesource';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'c_type_resource';

	/**
	 * @var CtyperesourceLine[] Lines
	 */
	public $lines = array();

	public $code;

	/**
     * @var string Type resource label
     */
    public $label;

	public $active;


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters

		if (isset($this->code)) {
			 $this->code = trim($this->code);
		}
		if (isset($this->label)) {
			 $this->label = trim($this->label);
		}
		if (isset($this->active)) {
			 $this->active = trim($this->active);
		}



		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';

		$sql .= 'code,';
		$sql .= 'label';
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
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			// Uncomment this and change MYOBJECT to your own tag if you
			// want this action to call a trigger.
			//if (!$notrigger) {

			//  // Call triggers
			//  $result=$this->call_trigger('MYOBJECT_CREATE',$user);
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
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $code = '', $label = '')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';

		$sql .= " t.code,";
		$sql .= " t.label,";
		$sql .= " t.active";


		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if ($id)   $sql .= " WHERE t.id = ".$id;
		elseif ($code) $sql .= " WHERE t.code = '".$this->db->escape($code)."'";
		elseif ($label) $sql .= " WHERE t.label = '".$this->db->escape($label)."'";


		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

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
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';

		$sql .= " t.code,";
		$sql .= " t.label,";
		$sql .= " t.active";


		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				$sqlwhere [] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
			}
		}

		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE '.implode(' '.$filtermode.' ', $sqlwhere);
		}
		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
		    $sql .= ' '.$this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id = $obj->rowid;

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
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters

		if (isset($this->code)) {
			 $this->code = trim($this->code);
		}
		if (isset($this->label)) {
			 $this->label = trim($this->label);
		}
		if (isset($this->active)) {
			 $this->active = trim($this->active);
		}

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';

		$sql .= ' code = '.(isset($this->code) ? "'".$this->db->escape($this->code)."'" : "null").',';
		$sql .= ' label = '.(isset($this->label) ? "'".$this->db->escape($this->label)."'" : "null").',';
		$sql .= ' active = '.(isset($this->active) ? $this->active : "null");


		$sql .= ' WHERE rowid='.$this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		// Uncomment this and change MYOBJECT to your own tag if you
		// want this action calls a trigger.
		//if (!$error && !$notrigger) {

		//  // Call triggers
		//  $result=$this->call_trigger('MYOBJECT_MODIFY',$user);
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
	 * @param User $user      User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		// Uncomment this and change MYOBJECT to your own tag if you
		// want this action calls a trigger.
		//if (!$error && !$notrigger) {

		//  // Call triggers
		//  $result=$this->call_trigger('MYOBJECT_DELETE',$user);
		//  if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
		//  // End call triggers
		//}

		// If you need to delete child tables to, you can insert them here

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' WHERE rowid='.$this->id;

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
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->code = '';
		$this->label = '';
		$this->active = '';
	}
}

/**
 * Class CtyperesourceLine
 */
class CtyperesourceLine
{
	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var mixed Sample line property 1
	 */
	public $code;

	/**
     * @var string Type resource line label
     */
    public $label;

	public $active;

	/**
	 * @var mixed Sample line property 2
	 */
}
