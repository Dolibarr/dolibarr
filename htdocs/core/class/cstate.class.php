<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/class/cstate.class.php
 *      \ingroup    core
 *      \brief      This file is a CRUD class file (Create/Read/Update/Delete) for c_departements dictionary
 */

/**
 *  Class to manage dictionary States (used by imports)
 */
class Cstate // extends CommonObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	//var $element='cstate';			//!< Id that identify managed objects
	//var $table_element='cstate';	    //!< Name of table without prefix where object is stored

	/**
	 * @var int ID
	 */
	public $id;

	public $code_departement;
	public $code;
	/**
	 * @var string
	 * @deprecated
	 * @see $name
	 */
	public $nom = '';

	/**
	 * @var string name
	 */
	public $name = '';

	public $active;




	/**
	 *  Constructor
	 *
	 *  @param      DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create object into database
	 *
	 *  @param      User	$user        User that create
	 *  @param      int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return     int      		   	 <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->code_departement)) {
			$this->code_departement = trim($this->code_departement);
		}
		if (isset($this->nom)) {
			$this->nom = trim($this->nom);
		}
		if (isset($this->active)) {
			$this->active = trim($this->active);
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_departements(";
		$sql .= "rowid,";
		$sql .= "code_departement,";
		$sql .= "nom,";
		$sql .= "active";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->rowid) ? 'NULL' : "'".$this->db->escape($this->rowid)."'").",";
		$sql .= " ".(!isset($this->code_departement) ? 'NULL' : "'".$this->db->escape($this->code_departement)."'").",";
		$sql .= " ".(!isset($this->nom) ? 'NULL' : "'".$this->db->escape($this->nom)."'").",";
		$sql .= " ".(!isset($this->active) ? 'NULL' : "'".$this->db->escape($this->active)."'")."";
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."c_departements");
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
	 *  Load object in memory from database
	 *
	 *  @param      int		$id    	Id object
	 *  @param		string	$code	Code
	 *  @return     int          	<0 if KO, >0 if OK
	 */
	public function fetch($id, $code = '')
	{
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.code_departement,";
		$sql .= " t.nom,";
		$sql .= " t.active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_departements as t";
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		} elseif ($code) {
			$sql .= " WHERE t.code_departement = '".$this->db->escape($code)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->code_departement = $obj->code_departement; //deprecated
				$this->code = $obj->code_departement;
				$this->nom = $obj->nom; //deprecated
				$this->name = $obj->nom;
				$this->active = $obj->active;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update object into database
	 *
	 *  @param      User	$user        User that modify
	 *  @param      int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return     int     		   	 <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->code_departement)) {
			$this->code_departement = trim($this->code_departement);
		}
		if (isset($this->nom)) {
			$this->nom = trim($this->nom);
		}
		if (isset($this->active)) {
			$this->active = trim($this->active);
		}


		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."c_departements SET";
		$sql .= " code_departement=".(isset($this->code_departement) ? "'".$this->db->escape($this->code_departement)."'" : "null").",";
		$sql .= " nom=".(isset($this->nom) ? "'".$this->db->escape($this->nom)."'" : "null").",";
		$sql .= " active=".(isset($this->active) ? $this->active : "null")."";
		$sql .= " WHERE rowid=".((int) $this->id);

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
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that delete
	 *  @param	int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."c_departements";
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
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
}
