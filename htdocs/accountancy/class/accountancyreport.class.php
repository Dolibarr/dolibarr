<?php
/* Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 * \file	htdocs/accountancy/class/accountancyreport.class.php
 * \ingroup Accountancy (Double entries)
 * \brief	File of class to manage reports for accounting categories
 */

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

/**
 * Class to manage reports for accounting categories
 */
class AccountancyReport // extends CommonObject
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string        Error string
	 */
	public $error;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'c_accounting_report';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'c_accounting_report';

	/**
	 * @var int ID
	 * @deprecated
	 */
	public $rowid;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string Accountancy code
	 */
	public $code;

	/**
	 * @var string Accountancy Category label
	 */
	public $label;

	/**
	 * @var int country id
	 */
	public $fk_country;

	/**
	 * @var int Is active
	 */
	public $active;

	/**
	 *  Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create object into database
	 *
	 * @param User $user User that create
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return     int                 Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->code)) {
			$this->code = trim($this->code);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->fk_country)) {
			$this->fk_country = (int) $this->fk_country;
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Insert request
		$sql = "INSERT INTO " . $this->db->prefix() . $this->table_element . " (";
		if ($this->rowid > 0) {
			$sql .= "rowid, ";
		}
		$sql .= "code, ";
		$sql .= "label, ";
		$sql .= "fk_country, ";
		$sql .= "active, ";
		$sql .= "entity";
		$sql .= ") VALUES (";
		if ($this->rowid > 0) {
			$sql .= " " . ((int) $this->rowid) . ",";
		}
		$sql .= " " . (!isset($this->code) ? "NULL" : "'" . $this->db->escape($this->code) . "'") . ",";
		$sql .= " " . (!isset($this->label) ? 'NULL' : "'" . $this->db->escape($this->label) . "'") . ",";
		$sql .= " " . (!isset($this->fk_country) ? 'NULL' : ((int) $this->fk_country)) . ",";
		$sql .= " " . (!isset($this->active) ? 'NULL' : ((int) $this->active));
		$sql .= ", " . ((int) $conf->entity);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
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
	 * @param int $id Id object
	 * @param string $code Code
	 * @param string $label Label
	 * @return     int            Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $code = '', $label = '')
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.code,";
		$sql .= " t.label,";
		$sql .= " t.fk_country,";
		$sql .= " t.active";
		$sql .= " FROM " . $this->db->prefix() . $this->table_element . " as t";
		if ($id) {
			$sql .= " WHERE t.rowid = " . ((int) $id);
		} else {
			$sql .= " WHERE t.entity IN (" . getEntity('c_accounting_report') . ")"; // Don't use entity if you use rowid
			if ($code) {
				$sql .= " AND t.code = '" . $this->db->escape($code) . "'";
			} elseif ($label) {
				$sql .= " AND t.label = '" . $this->db->escape($label) . "'";
			}
		}

		dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->code = $obj->code;
				$this->label = $obj->label;
				$this->fk_country = $obj->fk_country;
				$this->active = $obj->active;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update object into database
	 *
	 * @param User $user User that modify
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return     int                 Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->code)) {
			$this->code = trim($this->code);
		}
		if (isset($this->label)) {
			$this->label = trim($this->label);
		}
		if (isset($this->fk_country)) {
			$this->fk_country = (int) $this->fk_country;
		}
		if (isset($this->active)) {
			$this->active = (int) $this->active;
		}


		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE " . $this->db->prefix() . $this->table_element . " SET";
		$sql .= " code=" . (isset($this->code) ? "'" . $this->db->escape($this->code) . "'" : "null") . ",";
		$sql .= " label=" . (isset($this->label) ? "'" . $this->db->escape($this->label) . "'" : "null") . ",";
		$sql .= " fk_country=" . (isset($this->fk_country) ? ((int) $this->fk_country) : "null") . ",";
		$sql .= " active=" . (isset($this->active) ? ((int) $this->active) : "null");
		$sql .= " WHERE rowid=" . ((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this) . "::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
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
	 * @param User $user User that delete
	 * @param int $notrigger 0=launch triggers after, 1=disable triggers
	 * @return    int                     Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM " . $this->db->prefix() . $this->table_element;
		$sql .= " WHERE rowid=" . ((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error " . $this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}
}
