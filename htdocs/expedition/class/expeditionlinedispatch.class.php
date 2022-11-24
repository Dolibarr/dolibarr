<?php
/* Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Juanjo Menent	      <jmenent@2byte.es>
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
 *  \file       htdocs/expedition/class/expeditionlinedispatch.class.php
 *  \ingroup    expedition
 *  \brief      Class to manage shipment line dispatch
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";


/**
 *  Class to manage table expeditiondet_dispatch
 */
class ExpeditionLineDispatch
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expeditiondetdispatch';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expeditiondet_dispatch'; //!< Name of table without prefix where object is stored

	/**
	 * @var array Lines
	 */
	public $lines = array();

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var int ID
	 */
	public $fk_expeditiondet;

	/**
	 * @var int ID
	 */
	public $fk_product;

	/**
	 * @var int ID
	 */
	public $fk_product_parent;

	/**
	 * @var int ID
	 */
	public $fk_entrepot;

	/**
	 * @var float Qty
	 */
	public $qty;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create object into database
	 *
	 *  @param	User	$user        User that creates
	 *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
	 *  @return int      		   	 <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		$error = 0;

		// Clean parameters
		if (isset($this->fk_commande)) {
			$this->fk_expeditiondet = trim($this->fk_expeditiondet);
		}
		if (isset($this->fk_product)) {
			$this->fk_product = trim($this->fk_product);
		}
		if (isset($this->fk_product_parent)) {
			$this->fk_product_parent = trim($this->fk_product_parent);
		}
		if (isset($this->fk_entrepot)) {
			$this->fk_entrepot = trim($this->fk_entrepot);
		}
		if (isset($this->qty)) {
			$this->qty = trim($this->qty);
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		$sql .= "fk_expeditiondet";
		$sql .= ", fk_product";
		$sql .= ", fk_product_parent";
		$sql .= ", fk_entrepot";
		$sql .= ", qty";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->fk_expeditiondet) ? 'NULL' : "'".$this->db->escape($this->fk_expeditiondet)."'");
		$sql .= ", ".(!isset($this->fk_product) ? 'NULL' : "'".$this->db->escape($this->fk_product)."'");
		$sql .= ", ".(!isset($this->fk_product_parent) ? 'NULL' : "'".$this->db->escape($this->fk_product_parent)."'");
		$sql .= ", ".(!isset($this->fk_entrepot) ? 'NULL' : "'".$this->db->escape($this->fk_entrepot)."'");
		$sql .= ", ".(!isset($this->qty) ? 'NULL' : "'".$this->db->escape($this->qty)."'");
		$sql .= ")";

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	 *  Load object in memory from the database
	 *
	 *  @param	int		$id    				Id object
	 *  @return int		<0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT";
		$sql .= " t.rowid";
		$sql .= ", t.fk_expeditiondet";
		$sql .= ", t.fk_product";
		$sql .= ", t.fk_product_parent";
		$sql .= ", t.fk_entrepot";
		$sql .= ", t.qty";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(__METHOD__);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->fk_expeditiondet = $obj->fk_expeditiondet;
				$this->fk_product = $obj->fk_product;
				$this->fk_product_parent = $obj->fk_product_parent;
				$this->fk_entrepot = $obj->fk_entrepot;
				$this->qty = $obj->qty;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	string 	$sortorder		Sort Order
	 * @param	string 	$sortfield		Sort field
	 * @param	int    	$limit			Limit
	 * @param	int    	$offset 		Offset
	 * @param	array  	$filter			Filter array(key => value)
	 * @param	string	$filtermode		Filter mode (AND or OR)
	 * @param	int		$mvt_type		[=0] To increment qty (or no movement), -1 to decrement qty for movement
	 *
	 * @return	int	<0 if KO, >=0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $mvt_type = 0)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT";
		$sql .= " t.rowid";
		$sql .= ", t.fk_expeditiondet";
		$sql .= ", t.fk_product";
		$sql .= ", t.fk_product_parent";
		$sql .= ", t.fk_entrepot";
		$sql .= ", t.qty";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 'qty') {
					$sqlwhere [] = $key." = ".((float) $value);
				} else {
					$sqlwhere [] = $key." = ".((int) $value);
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE '.implode(' '.$this->db->escape($filtermode).' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit, $offset);
		}
		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id = $obj->rowid;
				$line->fk_expeditiondet = $obj->fk_expeditiondet;
				$line->fk_entrepot = $obj->fk_entrepot;
				$line->fk_product = $obj->fk_product;
				$line->fk_product_parent = $obj->fk_product_parent;
				if ($mvt_type == -1) {
					$line->qty = -$obj->qty;
				} else {
					$line->qty = $obj->qty;
				}
				$this->lines[$line->id] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


	/**
	 *  Update object into database
	 *
	 *  @param	User	$user        User that modifies
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return int     		   	 <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		$error = 0;

		// Clean parameters
		if (isset($this->fk_expeditiondet)) {
			$this->fk_expeditiondet = trim($this->fk_expeditiondet);
		}
		if (isset($this->fk_product)) {
			$this->fk_product = trim($this->fk_product);
		}
		if (isset($this->fk_product_parent)) {
			$this->fk_product_parent = trim($this->fk_product_parent);
		}
		if (isset($this->fk_entrepot)) {
			$this->fk_entrepot = trim($this->fk_entrepot);
		}
		if (isset($this->qty)) {
			$this->qty = trim($this->qty);
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " fk_expeditiondet=".(isset($this->fk_expeditiondet) ? $this->fk_expeditiondet : "null");
		$sql .= ", fk_product=".(isset($this->fk_product) ? $this->fk_product : "null");
		$sql .= ", fk_product_parent=".(isset($this->fk_product_parent) ? $this->fk_product_parent : "null");
		$sql .= ", fk_entrepot=".(isset($this->fk_entrepot) ? $this->fk_entrepot : "null");
		$sql .= ", qty=".(isset($this->qty) ? $this->qty : "null");
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(__METHOD__);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (empty($this->id) && !empty($this->rowid)) {
				$this->id = $this->rowid;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	 *	@param  User	$user        User that deletes
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(__METHOD__);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	 * Delete dispatch record attach to a shipment
	 *
	 * @param	int		$id_expedition	id of shipment
	 * @return 	int						<0 if KO, >0 if OK
	 */
	public function deleteFromShipment($id_expedition)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_expeditiondet in (SELECT rowid FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition=".((int) $id_expedition).")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			return -1;
		}
	}
}
