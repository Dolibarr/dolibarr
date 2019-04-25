<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016 Marcos García        <marcosgdf@gmail.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       compta/bank/class/bankcateg.class.php
 * \ingroup    bank
 * \brief      This file is CRUD class file (Create/Read/Update/Delete) for bank categories
 */

/**
 *    Class to manage bank categories
 */
class BankCateg // extends CommonObject
{
	//public $element='bank_categ';			//!< Id that identify managed objects
	//public $table_element='bank_categ';	//!< Name of table without prefix where object is stored
    /**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto='generic';

	/**
     * @var int ID
     */
    public $id;

	/**
     * @var string bank categories label
     */
    public $label;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 *  Create in database
	 *
	 * @param  User $user User that create
	 * @param  int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean parameters
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_categ (";
		$sql .= "label";
		$sql .= ", entity";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->label) ? 'NULL' : "'".$this->db->escape($this->label)."'")."";
		$sql .= ", ".$conf->entity;
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_categ");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 * Load object in memory from database
	 *
	 * @param  int $id Id object
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		global $conf;

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.label";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_categ as t";
		$sql .= " WHERE t.rowid = ".$id;
		$sql .= " AND t.entity = ".$conf->entity;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->label = $obj->label;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Update database
	 *
	 * @param  User $user User that modify
	 * @param  int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return int                    <0 if KO, >0 if OK
	 */
	public function update(User $user = null, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		// Clean parameters
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank_categ SET";
		$sql .= " label=".(isset($this->label) ? "'".$this->db->escape($this->label)."'" : "null")."";
		$sql .= " WHERE rowid=".$this->id;
		$sql .= " AND entity = ".$conf->entity;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
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
	 * Delete object in database
	 *
	 * @param  User    $user       User that delete
	 * @param  int     $notrigger  0=launch triggers after, 1=disable triggers
	 * @return int                 <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		$this->db->begin();

		// Delete link between tag and bank account
		if (! $error)
		{
		    $sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_account";
    		$sql.= " WHERE fk_categorie = ".$this->id;

    		$resql = $this->db->query($sql);
    		if (!$resql)
    		{
    		    $error++;
    		    $this->errors[] = "Error ".$this->db->lasterror();
    		}
		}

		// Delete link between tag and bank lines
		if (! $error)
		{
		    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class";
		    $sql.= " WHERE fk_categ = ".$this->id;

		    $resql = $this->db->query($sql);
		    if (!$resql)
		    {
		        $error++;
		        $this->errors[] = "Error ".$this->db->lasterror();
		    }
		}

		// Delete bank categ
		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_categ";
    		$sql .= " WHERE rowid=".$this->id;

    		$resql = $this->db->query($sql);
    		if (!$resql)
    		{
    			$error++;
    			$this->errors[] = "Error ".$this->db->lasterror();
    		}
		}

    	// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
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
	 * Load an object from its id and create a new one in database
	 *
	 * @param  int $fromid Id of object to clone
	 * @return int New id of clone
	 */
	public function createFromClone($fromid)
	{
		global $user;

		$error = 0;

		$object = new BankCateg($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error++;
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
	 * Returns all bank categories
	 *
	 * @return BankCateg[]
	 */
	public function fetchAll()
	{
		global $conf;

		$return = array();

		$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ WHERE entity = ".$conf->entity." ORDER BY label";
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$tmp = new BankCateg($this->db);
				$tmp->id = $obj->rowid;
				$tmp->label = $obj->label;

				$return[] = $tmp;
			}
		}

		return $return;
	}

	/**
	 * Initialise an instance with random values.
	 * Used to build previews or test instances.
	 * id must be 0 if object instance is a specimen.
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->label = '';
	}
}
