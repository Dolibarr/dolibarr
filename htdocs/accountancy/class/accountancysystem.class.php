<?php
/* Copyright (C) 2006-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
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
 *    \file       htdocs/accountancy/class/accountancysystem.class.php
 *    \ingroup    accounting
 *    \brief      File of class to manage accountancy systems
 */


/**    \class        AccountancySystem
 *    \brief        Classe to manage accountancy systems
 */
class AccountancySystem
{
	/**
	 * Database handler
	 * @var DoliDB
	 */
	public $db;
	public $error;
	public $rowid;
	public $fk_pcg_version;
	public $pcg_type;w
	public $pcg_subtype;
	public $label;
	public $account_number;
	public $account_parent;
	public $intitule;
	public $numero;
	public $id;

	/**
	 *    Constructor
	 *
	 * @param        DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 *  Insert accountancy system name into database
	 *
	 * @param    User $user User making insert
	 * @return        int                <0 if KO, Id of line if OK
	 */
	public function create(User $user)
	{
		$now = dol_now();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."accounting_system";
		$sql .= " (date_creation, fk_user_author, numero,intitule)";
		$sql .= " VALUES ('".$this->db->idate($now)."',".$user->id.",'".$this->numero."','".$this->intitule."')";

		$resql = $this->db->query($sql);
		if ($resql) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."accounting_system");

			if ($id > 0) {
				$this->id = $id;
				$result = $this->id;
			} else {
				$result = -2;
				$this->error = "AccountancySystem::Create Erreur $result";
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = -1;
			$this->error = "AccountancySystem::Create Erreur $result";
			dol_syslog($this->error, LOG_ERR);
		}

		return $result;
	}
}
