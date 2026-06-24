<?php
/* Copyright (C) 2026       Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 *      \file       htdocs/core/class/cincoterm.class.php
 *      \ingroup    core
 *      \brief      This file is a CRUD class file (Create/Read/Update/Delete) for c_incoterms dictionary
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commondict.class.php';


/**
 *  Class to manage dictionary Incoterms (used by imports)
 */
class Cincoterm extends CommonDict
{
	/**
	 * @var string      The code of the incoterm
	 *                  (ex: FOB, CIF, CPT, etc.)
	 */
	public $code;

	/**
	 * @var ?string      The name of the incoterm
	 */
	public $label = '';

	/**
	 * @var ?string      The description of the incoterm
	 */
	public $description = '';

	/**
	 * @var ?string
	 * @deprecated
	 * @see $description
	 */
	public $libelle = '';

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
	 *  Load object in memory from database
	 *
	 *  @param      int		$id    	Incoterm ID
	 *  @param		string	$code	Incoterm code
	 *  @return     int          	Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $code = '')
	{
		$sql = "SELECT";
		$sql .= " i.rowid,";
		$sql .= " i.code,";
		$sql .= " i.label,";
		$sql .= " i.libelle as description";
		$sql .= " FROM ".$this->db->prefix()."c_incoterms as i";
		if ($id) {
			$sql .= " WHERE i.rowid = ".((int) $id);
		} elseif ($code) {
			$sql .= " WHERE i.code = '".$this->db->escape($code)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->code = $obj->code;
				$this->label = $obj->label;
				$this->libelle = $obj->description; // deprecated
				$this->description = $obj->description;
				$this->active = $obj->active;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}
}
