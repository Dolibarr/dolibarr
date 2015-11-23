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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       expedition/class/expeditionbatch.class.php
 *  \ingroup    productbatch
 *  \brief      This file implements CRUD method for managing shipment batch lines
 *				with batch record
 */

/**
 *	CRUD class for batch number management within shipment
 */
class ExpeditionLineBatch extends CommonObject
{
	var $element='expeditionlignebatch';			//!< Id that identify managed objects
	private static $_table_element='expeditiondet_batch';		//!< Name of table without prefix where object is stored

	var $sellby;
	var $eatby;
	var $batch;
	var $dluo_qty;
	var $entrepot_id;
	var $fk_origin_stock;
	var $fk_expeditiondet;

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

	/**
	 * Fill object based on a product-warehouse-batch's record
	 *
	 * @param	int		$id_stockdluo	Rowid in product_batch table
	 * @return	int      		   	 -1 if KO, 1 if OK
	 */
	function fetchFromStock($id_stockdluo)
	{
        $sql = "SELECT";
		$sql.= " t.sellby,";
		$sql.= " t.eatby,";
		$sql.= " t.batch,";
		$sql.= " e.fk_entrepot";

        $sql.= " FROM ".MAIN_DB_PREFIX."product_batch as t inner join ";
        $sql.= MAIN_DB_PREFIX."product_stock as e on t.fk_product_stock=e.rowid ";
        $sql.= " WHERE t.rowid = ".(int) $id_stockdluo;

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

				$this->sellby = $this->db->jdate($obj->sellby);
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->batch = $obj->batch;
				$this->entrepot_id= $obj->fk_entrepot;
				$this->fk_origin_stock=(int) $id_stockdluo;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }

	/**
	 * Create an expeditiondet_batch DB record link to an expedtiondet record
	 *
	 * @param	int		$id_line_expdet		rowid of expedtiondet record
	 * @return	int							<0 if KO, Id of record (>0) if OK
	 */
	function create($id_line_expdet)
	{
		$error = 0;

		$id_line_expdet = (int) $id_line_expdet;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.self::$_table_element." (";
		$sql.= "fk_expeditiondet";
		$sql.= ", sellby";
		$sql.= ", eatby";
		$sql.= ", batch";
		$sql.= ", qty";
		$sql.= ", fk_origin_stock";
		$sql.= ") VALUES (";
		$sql.= $id_line_expdet.",";
		$sql.= " ".(! isset($this->sellby) || dol_strlen($this->sellby)==0?'NULL':("'".$this->db->idate($this->sellby))."'").",";
		$sql.= " ".(! isset($this->eatby) || dol_strlen($this->eatby)==0?'NULL':("'".$this->db->idate($this->eatby))."'").",";
		$sql.= " ".(! isset($this->batch)?'NULL':("'".$this->db->escape($this->batch)."'")).",";
		$sql.= " ".(! isset($this->dluo_qty)?'NULL':$this->dluo_qty).",";
		$sql.= " ".(! isset($this->fk_origin_stock)?'NULL':$this->fk_origin_stock);
		$sql.= ")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.self::$_table_element);
			$this->fk_expeditiondet=$id_line_expdet;
			return $this->id;
		}
		else
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
	}

	/**
	 * Delete batch record attach to a shipment
	 *
	 * @param	DoliDB	$db				Database object
	 * @param	int		$id_expedition	rowid of shipment
	 * @return 	int						-1 if KO, 1 if OK
	 */
	static function deletefromexp($db,$id_expedition)
	{
		$id_expedition = (int) $id_expedition;

		$sql="DELETE FROM ".MAIN_DB_PREFIX.self::$_table_element;
		$sql.=" WHERE fk_expeditiondet in (SELECT rowid FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition=".$id_expedition.")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		if ($db->query($sql))
		{
			return 1;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Retrieve all batch number details link to a shipment line
	 *
	 * @param	DoliDB	$db					Database object
	 * @param	int		$id_line_expdet		id of shipment line
	 * @return	variant						-1 if KO, array of ExpeditionLineBatch if OK
	 */
	static function fetchAll($db,$id_line_expdet)
	{
		$sql="SELECT rowid,";
		$sql.= "fk_expeditiondet";
		$sql.= ", sellby";
		$sql.= ", eatby";
		$sql.= ", batch";
		$sql.= ", qty";
		$sql.= ", fk_origin_stock";
		$sql.= " FROM ".MAIN_DB_PREFIX.self::$_table_element;
		$sql.= " WHERE fk_expeditiondet=".(int) $id_line_expdet;

		dol_syslog(__METHOD__ ."", LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;
			$ret = array();
			while ($i<$num)
			{
				$tmp=new self($db);

				$obj = $db->fetch_object($resql);

				$tmp->sellby = $db->jdate($obj->sellby);
				$tmp->eatby = $db->jdate($obj->eatby);
				$tmp->batch = $obj->batch;
				$tmp->id = $obj->rowid;
				$tmp->fk_origin_stock = $obj->fk_origin_stock;
				$tmp->fk_expeditiondet = $obj->fk_expeditiondet;
				$tmp->dluo_qty = $obj->qty;

				$ret[]=$tmp;
				$i++;
			}
			$db->free($resql);
			return $ret;
		}
		else
		{
			return -1;
		}
	}

}
