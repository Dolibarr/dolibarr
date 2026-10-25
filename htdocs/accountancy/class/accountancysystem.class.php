<?php
/* Copyright (C) 2013-2014  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
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
 * \file		htdocs/accountancy/class/accountancysystem.class.php
 * \ingroup		Accountancy (Double entries)
 * \brief		File of class to manage accountancy systems
 */

/**
 * Class to manage accountancy systems
 */
class AccountancySystem extends CommonObject
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
	 * @var string[] Array of Errors code (or messages)
	 */
	public $errors = array();

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int ID
	 * @deprecated
	 * @see $id
	 */
	public $rowid;

	/**
	 * @var string 		Accountancy system code
	 */
	public $pcg_version;

	/**
	 * @var string 		Ref of accountancy system. Duplicate property with ->pcg_version.
	 * @see $pcg_version
	 */
	public $ref;

	/**
	 * @var int active
	 */
	public $active;

	/**
	 * @var string Accountancy System label
	 */
	public $label;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Load record in memory
	 *
	 * @param 	int 	$rowid 				   Id
	 * @param 	string 	$ref             	   ref
	 * @return 	int                            Return integer <0 if KO, Id of record if OK and found
	 */
	public function fetch($rowid = 0, $ref = '')
	{
		global $conf;

		if ($rowid > 0 || $ref) {
			$sql  = "SELECT a.rowid, a.pcg_version, a.label, a.active";
			$sql .= " FROM ".MAIN_DB_PREFIX."accounting_system as a";
			$sql .= " WHERE";
			if ($rowid) {
				$sql .= " a.rowid = ".((int) $rowid);
			} elseif ($ref) {
				$sql .= " a.pcg_version = '".$this->db->escape($ref)."'";
			}

			dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);

				if ($obj) {
					$this->id = $obj->rowid;
					$this->rowid = $obj->rowid;
					$this->pcg_version = $obj->pcg_version;
					$this->ref = $obj->pcg_version;
					$this->label = $obj->label;
					$this->active = $obj->active;

					return $this->id;
				} else {
					return 0;
				}
			} else {
				$this->error = "Error ".$this->db->lasterror();
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}
		return -1;
	}


	/**
	 * Insert accountancy system name into database
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
	public function create($user)
	{
		$now = dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_system";
		$sql .= " (date_creation, fk_user_author, label, pcg_version, active)";
		$sql .= " VALUES ('"
			. $this->db->idate($now)                    ."',"
			. ((int) $user->id)                         .",'"
			. $this->db->escape($this->label)           ."','"
			. $this->db->escape($this->pcg_version)     ."',"
			. ((int) $this->active)                      .")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."accounting_system");

			if ($id > 0) {
				$this->id = $id;
				$this->rowid = $id;
				$result = $this->rowid;
			} else {
				$result = - 2;
				$this->error = "AccountancySystem::Create Error $result: " . $this->db->lasterror();
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
			$this->error = "AccountancySystem::Create Error $result: " . $this->db->lasterror();
			dol_syslog($this->error, LOG_ERR);
		}

		return $result;
	}
}
