<?php
/* Copyright (C) Richard Rondu  <rondu.richard@lainwir3d.net>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/class/cregion.class.php
 *      \ingroup    core
 *      \brief      This file is a CRUD class file (Create/Read/Update/Delete) for c_regions dictionary
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commondict.class.php';


/**
 * 	Class to manage dictionary Regions
 */
class Cregion extends CommonDict
{
	//public $element = 'cregion'; //!< Id that identify managed objects
	//public $table_element = 'c_regions'; //!< Name of table without prefix where object is stored

	/**
	 * @var int         The code of the region
	 */
	public $code_region;

	/**
	 * @var int         The ID of the country of the region
	 */
	public $fk_pays;

	/**
	 * @var string      The name of the region
	 */
	public $name;

	/**
	 * @var string      The reference of the "chef-lieu" of the region
	 *                  A.k.a. the administrative headquarter of the region
	 *                  (examples: HU33, PT9, 97601)
	 */
	public $cheflieu;


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
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->code_region)) {
			$this->code_region = (int) $this->code_region;
		}
		if (isset($this->fk_pays)) {
			$this->fk_pays = (int) $this->fk_pays;
		}
		if (isset($this->name)) {
			$this->name = trim($this->name);
		}
		if (isset($this->cheflieu)) {
			$this->cheflieu = trim($this->cheflieu);
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO ".$this->db->prefix()."c_regions(";
		$sql .= "rowid,";
		$sql .= "code_region,";
		$sql .= "fk_pays,";
		$sql .= "nom,";
		$sql .= "cheflieu,";
		$sql .= "active";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->id) ? 'NULL' : (int) $this->id).",";
		$sql .= " ".(!isset($this->code_region) ? 'NULL' : (int) $this->code_region).",";
		$sql .= " ".(!isset($this->fk_pays) ? 'NULL' : (int) $this->fk_pays).",";
		$sql .= " ".(!isset($this->name) ? 'NULL' : "'".$this->db->escape($this->name)."'").",";
		$sql .= " ".(!isset($this->cheflieu) ? 'NULL' : "'".$this->db->escape($this->cheflieu)."'").",";
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
			$this->id = $this->db->last_insert_id($this->db->prefix()."c_regions");
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
	 *  @param      int		        $id           Id object
	 *  @param      int		        $code_region  Code
	 *  @param      int	            $fk_pays      Country Id
	 *  @return     int          	>0 if OK, 0 if not found, <0 if KO
	 */
	public function fetch($id, $code_region = 0, $fk_pays = 0)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.code_region,";
		$sql .= " t.fk_pays,";
		$sql .= " t.nom,";
		$sql .= " t.cheflieu,";
		$sql .= " t.active";
		$sql .= " FROM ".$this->db->prefix()."c_regions as t";
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		} elseif ($code_region) {
			$sql .= " WHERE t.code_region = ".((int) $code_region);
		} elseif ($fk_pays) {
			$sql .= " WHERE t.fk_pays = ".((int) $fk_pays);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				if ($obj) {
					$this->id = $obj->rowid;
					$this->code_region = (int) $obj->code_region;
					$this->fk_pays = (int) $obj->fk_pays;
					$this->name = $obj->nom;
					$this->cheflieu = $obj->cheflieu;
					$this->active = $obj->active;
				}

				$this->db->free($resql);
				return 1;
			} else {
				return 0;
			}
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
	 *  @return     int     		   	 Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->code_region)) {
			$this->code_region = (int) $this->code_region;
		}
		if (isset($this->fk_pays)) {
			$this->fk_pays = (int) $this->fk_pays;
		}
		if (isset($this->name)) {
			$this->name = trim($this->name);
		}
		if (isset($this->cheflieu)) {
			$this->cheflieu = trim($this->cheflieu);
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}


		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".$this->db->prefix()."c_regions SET";
		$sql .= " code_region=".(isset($this->code_region) ? ((int) $this->code_region) : "null").",";
		$sql .= " fk_pays=".(isset($this->fk_pays) ? ((int) $this->fk_pays) : "null").",";
		$sql .= " nom=".(isset($this->name) ? "'".$this->db->escape($this->name)."'" : "null").",";
		$sql .= " cheflieu=".(isset($this->cheflieu) ? "'".$this->db->escape($this->cheflieu)."'" : "null").",";
		$sql .= " active=".(isset($this->active) ? $this->active : "null");
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
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM ".$this->db->prefix()."c_regions";
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
