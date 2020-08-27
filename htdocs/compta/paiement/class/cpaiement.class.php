<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
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
 * \file    htdocs/compta/paiement/class/cpaiement.class.php
 * \ingroup facture
 * \brief   This file is to manage CRUD function of type of payments
 */


/**
 * Class Cpaiement
 */
class Cpaiement
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'cpaiement';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'c_paiement';

	public $code;

	/**
	 * @deprecated
	 * @see $label
	 */
	public $libelle;
	public $label;

	public $type;
	public $active;
	public $accountancy_code;
	public $module;


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
		if (isset($this->libelle)) {
			 $this->libelle = trim($this->libelle);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->type)) {
			 $this->type = trim($this->type);
		}
		if (isset($this->active)) {
			 $this->active = trim($this->active);
		}
		if (isset($this->accountancy_code)) {
			 $this->accountancy_code = trim($this->accountancy_code);
		}
		if (isset($this->module)) {
			 $this->module = trim($this->module);
		}



		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'(';
		$sql .= 'entity,';
		$sql .= 'code,';
		$sql .= 'libelle,';
		$sql .= 'type,';
		$sql .= 'active,';
		$sql .= 'accountancy_code,';
		$sql .= 'module';
		$sql .= ') VALUES (';
		$sql .= ' '.(!isset($this->entity) ?getEntity('c_paiement') : $this->entity).',';
		$sql .= ' '.(!isset($this->code) ? 'NULL' : "'".$this->db->escape($this->code)."'").',';
		$sql .= ' '.(!isset($this->libelle) ? 'NULL' : "'".$this->db->escape($this->libelle)."'").',';
		$sql .= ' '.(!isset($this->type) ? 'NULL' : $this->type).',';
		$sql .= ' '.(!isset($this->active) ? 'NULL' : $this->active).',';
		$sql .= ' '.(!isset($this->accountancy_code) ? 'NULL' : "'".$this->db->escape($this->accountancy_code)."'").',';
		$sql .= ' '.(!isset($this->module) ? 'NULL' : "'".$this->db->escape($this->module)."'");
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
	 * @param string $ref Ref
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.id,';
		$sql .= " t.code,";
		$sql .= " t.libelle as label,";
		$sql .= " t.type,";
		$sql .= " t.active,";
		$sql .= " t.accountancy_code,";
		$sql .= " t.module";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		if (null !== $ref) {
			$sql .= ' WHERE t.entity IN ('.getEntity('c_paiement').')';
			$sql .= " AND t.code = '".$this->db->escape($ref)."'";
		} else {
			$sql .= ' WHERE t.id = '.$id;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->id;

				$this->code = $obj->code;
				$this->libelle = $obj->label;
				$this->label = $obj->label;
				$this->type = $obj->type;
				$this->active = $obj->active;
				$this->accountancy_code = $obj->accountancy_code;
				$this->module = $obj->module;
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
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
		if (isset($this->libelle)) {
			 $this->libelle = trim($this->libelle);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->type)) {
			 $this->type = trim($this->type);
		}
		if (isset($this->active)) {
			 $this->active = trim($this->active);
		}
		if (isset($this->accountancy_code)) {
			 $this->accountancy_code = trim($this->accountancy_code);
		}
		if (isset($this->module)) {
			 $this->module = trim($this->module);
		}



		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.' SET';
		$sql .= ' id = '.(isset($this->id) ? $this->id : "null").',';
		$sql .= ' code = '.(isset($this->code) ? "'".$this->db->escape($this->code)."'" : "null").',';
		$sql .= ' libelle = '.(isset($this->libelle) ? "'".$this->db->escape($this->libelle)."'" : "null").',';
		$sql .= ' type = '.(isset($this->type) ? $this->type : "null").',';
		$sql .= ' active = '.(isset($this->active) ? $this->active : "null").',';
		$sql .= ' accountancy_code = '.(isset($this->accountancy_code) ? "'".$this->db->escape($this->accountancy_code)."'" : "null").',';
		$sql .= ' module = '.(isset($this->module) ? "'".$this->db->escape($this->module)."'" : "null");
		$sql .= ' WHERE id='.$this->id;

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' WHERE id='.$this->id;

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
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
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->code = '';
		$this->libelle = '';
		$this->label = '';
		$this->type = '';
		$this->active = '';
		$this->accountancy_code = '';
		$this->module = '';
	}
}
