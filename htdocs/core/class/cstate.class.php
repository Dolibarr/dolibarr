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

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commondict.class.php';


/**
 *  Class to manage dictionary States (used by imports)
 */
class Cstate extends CommonDict
{
	/**
	 * @var int         The ID of the state
	 */
	public $rowid;

	/**
	 * @var string      The code of the state
	 *                  (ex: LU0011, MA12, 07, 0801, etc.)
	 */
	public $code_departement;

	/**
	 * @var string      The name of the state
	 */
	public $name = '';

	/**
	 * @var string
	 * @deprecated
	 * @see $name
	 */
	public $nom = '';


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
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
	 *  @return     int      		   	 Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		$error = 0;

		// Clean parameters
		if (isset($this->code_departement)) {
			$this->code_departement = trim($this->code_departement);
		}
		if (isset($this->nom)) {
			$this->nom = trim($this->nom);
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO ".$this->db->prefix()."c_departements(";
		$sql .= "rowid,";
		$sql .= "code_departement,";
		$sql .= "nom,";
		$sql .= "active";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->rowid) ? 'NULL' : "'".$this->db->escape($this->rowid)."'").",";
		$sql .= " ".(!isset($this->code_departement) ? 'NULL' : "'".$this->db->escape($this->code_departement)."'").",";
		$sql .= " ".(!isset($this->nom) ? 'NULL' : "'".$this->db->escape($this->nom)."'").",";
		$sql .= " ".(!isset($this->active) ? 'NULL' : "'".$this->db->escape($this->active)."'");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."c_departements");
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
	 *  @param      int		$id    	State ID
	 *  @param		string	$code	State code
	 *  @return     int          	Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $code = '')
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.code_departement,";
		$sql .= " t.nom,";
		$sql .= " t.active";
		$sql .= " FROM ".$this->db->prefix()."c_departements as t";
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
	 *  @param      User	$user        User who updates
	 *  @param      int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return     int     		   	 Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		$error = 0;

		// Clean parameters
		if (isset($this->code_departement)) {
			$this->code_departement = trim($this->code_departement);
		}
		if (isset($this->name)) {
			$this->name = trim($this->name);
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}

		// Check parameters
		if (empty($this->name) && !empty($this->nom)) {
			$this->name = $this->nom;
		}

		// Update request
		$sql = "UPDATE ".$this->db->prefix()."c_departements SET";
		$sql .= " code_departement=".(isset($this->code_departement) ? "'".$this->db->escape($this->code_departement)."'" : "null").",";
		$sql .= " nom=".(isset($this->name) ? "'".$this->db->escape($this->name)."'" : "null").",";
		$sql .= " active=".(isset($this->active) ? ((int) $this->active) : "null");
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
	 *  @return	int					 Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		$sql = "DELETE FROM ".$this->db->prefix()."c_departements";
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

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs;
		return $langs->trans($this->name);
	}
}
