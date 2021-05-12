<?php
/* Copyright (C) 2013-2014 Olivier Geffroy       <jeff@jeffinfo.com>
<<<<<<< HEAD
 * Copyright (C) 2013-2014 Alexandre Spangaro    <aspangaro@zendsi.com>
=======
 * Copyright (C) 2013-2014 Alexandre Spangaro    <aspangaro@open-dsi.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * \file		htdocs/accountancy/class/accountancysystem.class.php
<<<<<<< HEAD
 * \ingroup		Advanced accountancy
=======
 * \ingroup		Accountancy (Double entries)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * \brief		File of class to manage accountancy systems
 */

/**
 * Class to manage accountancy systems
 */
class AccountancySystem
{
<<<<<<< HEAD
	var $db;
	var $error;
	var $rowid;
	var $fk_pcg_version;
	var $pcg_type;
	var $pcg_subtype;
	var $label;
	var $account_number;
	var $account_parent;
=======
    /**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
     * @var int ID
     */
	public $fk_pcg_version;

	public $pcg_type;
	public $pcg_subtype;

    /**
     * @var string Accountancy System label
     */
    public $label;

	public $account_number;
	public $account_parent;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
<<<<<<< HEAD
	function __construct($db) {
		$this->db = $db;
	}
=======
    public function __construct($db)
    {
		$this->db = $db;
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	/**
	 * Load record in memory
	 *
	 * @param 	int 	$rowid 				   Id
	 * @param 	string 	$ref             	   ref
	 * @return 	int                            <0 if KO, Id of record if OK and found
	 */
<<<<<<< HEAD
	function fetch($rowid = 0, $ref = '')
=======
	public function fetch($rowid = 0, $ref = '')
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
	    global $conf;

	    if ($rowid > 0 || $ref)
	    {
<<<<<<< HEAD
	        $sql  = "SELECT a.pcg_version, a.label, a.active";
=======
	        $sql  = "SELECT a.rowid, a.pcg_version, a.label, a.active";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	        $sql .= " FROM " . MAIN_DB_PREFIX . "accounting_system as a";
	        $sql .= " WHERE";
	        if ($rowid) {
	            $sql .= " a.rowid = '" . $rowid . "'";
	        } elseif ($ref) {
<<<<<<< HEAD
	            $sql .= " a.pcg_version = '" . $ref . "'";
=======
	            $sql .= " a.pcg_version = '" . $this->db->escape($ref) . "'";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	        }

	        dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
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
	            $this->error = "Error " . $this->db->lasterror();
	            $this->errors[] = "Error " . $this->db->lasterror();
	        }
	    }
	    return - 1;
	}


	/**
	 * Insert accountancy system name into database
	 *
	 * @param User $user making insert
	 * @return int if KO, Id of line if OK
	 */
<<<<<<< HEAD
	function create($user) {
=======
    public function create($user)
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$now = dol_now();

		$sql = "INSERT INTO " . MAIN_DB_PREFIX . "accounting_system";
		$sql .= " (date_creation, fk_user_author, numero, label)";
		$sql .= " VALUES ('" . $this->db->idate($now) . "'," . $user->id . ",'" . $this->db->escape($this->numero) . "','" . $this->db->escape($this->label) . "')";

		dol_syslog(get_class($this) . "::create sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX . "accounting_system");

			if ($id > 0) {
				$this->rowid = $id;
				$result = $this->rowid;
			} else {
				$result = - 2;
<<<<<<< HEAD
				$this->error = "AccountancySystem::Create Erreur $result";
=======
				$this->error = "AccountancySystem::Create Error $result";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				dol_syslog($this->error, LOG_ERR);
			}
		} else {
			$result = - 1;
<<<<<<< HEAD
			$this->error = "AccountancySystem::Create Erreur $result";
			dol_syslog($this->error, LOG_ERR);
		}

		return $result;
	}
}
=======
			$this->error = "AccountancySystem::Create Error $result";
			dol_syslog($this->error, LOG_ERR);
		}

        return $result;
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
