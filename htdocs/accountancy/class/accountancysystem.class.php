<?php
/* Copyright (C) 2013-2014 Olivier Geffroy       <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Alexandre Spangaro    <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
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
 * \file htdocs/accountancy/class/accountancysystem.class.php
 * \ingroup Accounting Expert
 * \brief File of class to manage accountancy systems
 */

/**
 * Class to manage accountancy systems
 */
class AccountancySystem
{
	var $db;
	var $error;
	var $rowid;
	var $fk_pcg_version;
	var $pcg_type;
	var $pcg_subtype;
	var $label;
	var $account_number;
	var $account_parent;
	
	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {
		$this->db = $db;
	}
	
	/**
	 * Insert accountancy system name into database
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
	function create($user) {
		$now = dol_now();
		
		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "accounting_system";
		$sql .= " (date_creation, fk_user_author, numero, label)";
		$sql .= " VALUES (" . $this->db->idate($now) . "," . $user->id . ",'" . $this->numero . "','" . $this->label . "')";
		
		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX . "accounting_system");
			
			if ($id > 0) {
				$this->rowid = $id;
				$result = $this->rowid;
			} else {
				$result = - 2;
				$this->error = "AccountancySystem::Create Erreur $result";
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
			$this->error = "AccountancySystem::Create Erreur $result";
			dol_syslog($this->error, LOG_ERR);
		}
		
		return $result;
	}
}