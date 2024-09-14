<?php
/* Copyright (C) 2007-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2020       Florian HENRY           <florian.henry@scopen.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *      \file       htdocs/core/class/cproductnature.class.php
 *      \ingroup    core
 *      \brief      This file is CRUD class file (Create/Read/Update/Delete) for c_units dictionary
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commondict.class.php';


/**
 *	Class of dictionary of nature of product (used by imports)
 */
class CProductNature extends CommonDict
{
	/**
	 * @var array record
	 */
	public $records = array();

	/**
	 * @var string element
	 */
	public $element = 'cproductnbature';

	/**
	 * @var string table element
	 */
	public $table_element = 'c_product_nature';


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

		// Insert request
		$sql = "INSERT INTO ".$this->db->prefix().$this->table_element."(";
		$sql .= "rowid,";
		$sql .= "code,";
		$sql .= "label,";
		$sql .= "active";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->id) ? 'NULL' : ((int) $this->id)).",";
		$sql .= " ".(!isset($this->code) ? 'NULL' : ((int) $this->code)).",";
		$sql .= " ".(!isset($this->label) ? 'NULL' : "'".$this->db->escape(trim($this->label))."'").",";
		$sql .= " ".(!isset($this->active) ? 'NULL' : ((int) $this->active)).",";
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		// Commit or rollback
		if (!$resql) {
			dol_syslog(get_class($this)."::create ".$this->db->lasterror(), LOG_ERR);
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		} else {
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param      int		$id    			Id of CUnit object to fetch (rowid)
	 *  @param		string	$code			Code
	 *  @return     int						Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $code = '')
	{
		global $langs;

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.code,";
		$sql .= " t.label,";
		$sql .= " t.active";
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		$sql_where = array();
		if ($id) {
			$sql_where[] = " t.rowid = ".((int) $id);
		}
		if ($code >= 0) {
			$sql_where[] = " t.code = ".((int) $code);
		}
		if (count($sql_where) > 0) {
			$sql .= ' WHERE '.implode(' AND ', $sql_where);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->code = $obj->code;
				$this->label = $obj->label;
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
	 * Load list of objects in memory from the database.
	 *
	 * @param  string      $sortorder    Sort Order
	 * @param  string      $sortfield    Sort field
	 * @param  int         $limit        Limit
	 * @param  int         $offset       Offset
	 * @param  string      $filter       Filter USF
	 * @param  string      $filtermode   Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.code,";
		$sql .= " t.label,";
		$sql .= " t.active";
		$sql .= " FROM ".$this->db->prefix().$this->table_element." as t";
		$sql .= " WHERE 1 = 1";

		// Manage filter
		if (is_array($filter)) {
			$sqlwhere = array();
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if ($key == 't.rowid' || $key == 't.active' || $key == 't.code') {
						$sqlwhere[] = $this->db->sanitize($key)." = ".((int) $value);
					} elseif (strpos($key, 'date') !== false) {
						$sqlwhere[] = $this->db->sanitize($key)." = '".$this->db->idate($value)."'";
					} elseif ($key == 't.label') {
						$sqlwhere[] = $this->db->sanitize($key)." = '".$this->db->escape($value)."'";
					} else {
						$sqlwhere[] = $this->db->sanitize($key)." LIKE '%".$this->db->escape($value)."%'";
					}
				}
			}
			if (count($sqlwhere) > 0) {
				$sql .= " AND ".implode(' '.$this->db->escape($filtermode).' ', $sqlwhere);
			}

			$filter = '';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->records = array();
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				while ($obj = $this->db->fetch_object($resql)) {
					$record = new self($this->db);

					$record->id    = $obj->rowid;
					$record->code = $obj->code;
					$record->label = $obj->label;
					$this->records[$record->id] = $record;
				}
			}
			$this->db->free($resql);

			return $this->records;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

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

		// Update request
		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
		$sql .= " code=".(isset($this->code) ? ((int) $this->code) : "null").",";
		$sql .= " label=".(isset($this->label) ? "'".$this->db->escape(trim($this->label))."'" : "null").",";
		$sql .= " active=".(isset($this->active) ? ((int) $this->active) : "null");
		$sql .= " WHERE rowid=".(int) $this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		// Commit or rollback
		if (!$resql) {
			dol_syslog(get_class($this)."::update Error ".$this->db->lasterror(), LOG_ERR);
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that delete
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE rowid=".(int) $this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		// Commit or rollback
		if (!$resql) {
			dol_syslog(get_class($this)."::delete Error ".$this->db->lasterror(), LOG_ERR);
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 * Get unit from code
	 * @param string $code code of unit
	 * @param string $mode 0= id , short_label=Use short label as value, code=use code
	 * @return int            Return integer <0 if KO, Id of code if OK
	 */
	public function getProductNatureFromCode($code, $mode = 'code')
	{
		if ($mode == 'label') {
			return dol_getIdFromCode($this->db, $code, $this->table_element, 'label', 'code');
		} elseif ($mode == 'code') {
			return dol_getIdFromCode($this->db, $code, $this->table_element, 'code', 'code');
		}

		return $code;
	}
}
