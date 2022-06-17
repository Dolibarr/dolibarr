<?php
/* Copyright (C) 2007-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013-2014 Cedric GROSS         <c.gross@kreiz-it.fr>
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
 *  \file       htdocs/expedition/class/expeditionlinebatch.class.php
 *  \ingroup    productbatch
 *  \brief      This file implements CRUD method for managing shipment batch lines
 *				with batch record
 */

/**
 *	CRUD class for batch number management within shipment
 */
class ExpeditionLineBatch extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expeditionlignebatch';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'expeditiondet_batch';

	public $sellby;
	public $eatby;
	public $batch;
	public $qty;
	public $dluo_qty; // deprecated, use qty
	public $entrepot_id;
	public $fk_origin_stock;
	public $fk_expeditiondet;


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
	 * Fill object based on a product-warehouse-batch's record
	 *
	 * @param	int		$id_stockdluo	Rowid in product_batch table
	 * @return	int      		   	 -1 if KO, 1 if OK
	 */
	public function fetchFromStock($id_stockdluo)
	{
		$sql = "SELECT";
		$sql .= " pb.batch,";
		$sql .= " pl.sellby,";
		$sql .= " pl.eatby,";
		$sql .= " ps.fk_entrepot";

		$sql .= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
		$sql .= " JOIN ".MAIN_DB_PREFIX."product_stock as ps on pb.fk_product_stock=ps.rowid";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX."product_lot as pl on pl.batch = pb.batch AND pl.fk_product = ps.fk_product";
		$sql .= " WHERE pb.rowid = ".(int) $id_stockdluo;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->sellby = $this->db->jdate($obj->sellby);
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->batch = $obj->batch;
				$this->entrepot_id = $obj->fk_entrepot;
				$this->fk_origin_stock = (int) $id_stockdluo;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Create an expeditiondet_batch DB record link to an expedtiondet record
	 *
	 * @param	int		$id_line_expdet		rowid of expedtiondet record
	 * @return	int							<0 if KO, Id of record (>0) if OK
	 */
	public function create($id_line_expdet)
	{
		$error = 0;

		$id_line_expdet = (int) $id_line_expdet;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "fk_expeditiondet";
		$sql .= ", sellby";
		$sql .= ", eatby";
		$sql .= ", batch";
		$sql .= ", qty";
		$sql .= ", fk_origin_stock";
		$sql .= ") VALUES (";
		$sql .= $id_line_expdet.",";
		$sql .= " ".(!isset($this->sellby) || dol_strlen($this->sellby) == 0 ? 'NULL' : ("'".$this->db->idate($this->sellby))."'").",";
		$sql .= " ".(!isset($this->eatby) || dol_strlen($this->eatby) == 0 ? 'NULL' : ("'".$this->db->idate($this->eatby))."'").",";
		$sql .= " ".(!isset($this->batch) ? 'NULL' : ("'".$this->db->escape($this->batch)."'")).",";
		$sql .= " ".(!isset($this->qty) ? ((!isset($this->dluo_qty)) ? 'NULL' : $this->dluo_qty) : $this->qty).","; // dluo_qty deprecated, use qty
		$sql .= " ".(!isset($this->fk_origin_stock) ? 'NULL' : $this->fk_origin_stock);
		$sql .= ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++; $this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			$this->fk_expeditiondet = $id_line_expdet;
			return $this->id;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * Delete batch record attach to a shipment
	 *
	 * @param	int		$id_expedition	rowid of shipment
	 * @return 	int						-1 if KO, 1 if OK
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

	/**
	 * Retrieve all batch number detailed information of a shipment line
	 *
	 * @param	int			$id_line_expdet		id of shipment line
	 * @param	int			$fk_product			If provided, load also detailed information of lot
	 * @return	int|array						-1 if KO, array of ExpeditionLineBatch if OK
	 */
	public function fetchAll($id_line_expdet, $fk_product = 0)
	{
		$sql = "SELECT";
		$sql .= " eb.rowid,";
		$sql .= " eb.fk_expeditiondet,";
		$sql .= " eb.sellby as oldsellby,"; // deprecated
		$sql .= " eb.eatby as oldeatby,"; // deprecated
		$sql .= " eb.batch,";
		$sql .= " eb.qty,";
		$sql .= " eb.fk_origin_stock";
		if ($fk_product > 0) {
			$sql .= ", pl.sellby";
			$sql .= ", pl.eatby";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as eb";
		if ($fk_product > 0) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON pl.batch = eb.batch AND pl.fk_product = ".((int) $fk_product);
		}
		$sql .= " WHERE fk_expeditiondet=".(int) $id_line_expdet;

		dol_syslog(__METHOD__."", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$ret = array();
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tmp = new self($this->db);
				$tmp->sellby = $this->db->jdate($obj->sellby ? $obj->sellby : $obj->oldsellby);
				$tmp->eatby = $this->db->jdate($obj->eatby ? $obj->eatby : $obj->oldeatby);
				$tmp->batch = $obj->batch;
				$tmp->id = $obj->rowid;
				$tmp->fk_origin_stock = $obj->fk_origin_stock;
				$tmp->fk_expeditiondet = $obj->fk_expeditiondet;
				$tmp->dluo_qty = $obj->qty; // dluo_qty deprecated, use qty
				$tmp->qty = $obj->qty;

				$ret[] = $tmp;
				$i++;
			}

			$this->db->free($resql);

			return $ret;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}
}
