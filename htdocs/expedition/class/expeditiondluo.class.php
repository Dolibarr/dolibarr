<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       productdluo/core/class/productdluo.class.php
 *  \ingroup    productdluo
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2013-12-30 15:20
 */

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");

class ExpeditionLigneDluo extends CommonObject
{
	var $db;
	var $error;							//!< To return error code (or message)
    
	var $id;
	var $dluo='';
	var $dlc='';
	var $lot='';
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
	
	function fetchFromStock($id_stockdluo) {
        $sql = "SELECT";
		$sql.= " t.dluo,";
		$sql.= " t.dlc,";
		$sql.= " t.lot,";
		$sql.= " e.fk_entrepot";
		
        $sql.= " FROM ".MAIN_DB_PREFIX."product_dluo as t inner join ";
        $sql.= MAIN_DB_PREFIX."product_stock as e on t.fk_product_stock=e.rowid ";
        $sql.= " WHERE t.rowid = ".(int)$id_stockdluo;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

				$this->dluo = $this->db->jdate($obj->dluo);
				$this->dlc = $this->db->jdate($obj->dlc);
				$this->lot = $obj->lot;
				$this->entrepot_id= $obj->fk_entrepot;
				$this->fk_origin_stock=(int)$id_stockdluo;
                
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }

	function create($id_line_expdet) {
		
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."expeditiondet_dluo (";
		$sql.= "fk_expeditiondet";
		$sql.= ", dluo";
		$sql.= ", dlc";
		$sql.= ", lot";
		$sql.= ", qty";
		$sql.= ", fk_origin_stock";
		$sql.= ") VALUES (";
		$sql.= $id_line_expdet.",";
		$sql.= " ".(! isset($this->dluo) || dol_strlen($this->dluo)==0?'NULL':$this->db->idate($this->dluo)).",";
		$sql.= " ".(! isset($this->dlc) || dol_strlen($this->dlc)==0?'NULL':$this->db->idate($this->dlc)).",";
		$sql.= " ".(! isset($this->lot)?'NULL':"'".$this->db->escape($this->lot)."'").",";
		$sql.= " ".(! isset($this->dluo_qty)?'NULL':$this->dluo_qty).",";
		$sql.= " ".(! isset($this->fk_origin_stock)?'NULL':$this->fk_origin_stock);
		$sql.= ")";

		dol_syslog(get_class($this)."::create_line sql=".$sql, LOG_DEBUG);
		$resql=$this->db->query($sql);

		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error){
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_dluo");
			$this->fk_expeditiondet=$id_line_expdet;
			return $this->id;
		} else {
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
	}

	static function deletefromexp($db,$id_expedition) {
		$sql="DELETE FROM ".MAIN_DB_PREFIX."expeditiondet_dluo ";
		$sql.=" WHERE fk_expeditiondet in (SELECT rowid FROM ".MAIN_DB_PREFIX."expeditiondet WHERE fk_expedition=".$id_expedition.")";
		
		if ( $db->query($sql) )
		{
			return 1;
		} else {
			return -1;
		}
	}

	static function FetchAll($db,$id_line_expdet) {
		$sql="SELECT rowid,";
		$sql.= "fk_expeditiondet";
		$sql.= ", dluo";
		$sql.= ", dlc";
		$sql.= ", lot";
		$sql.= ", qty";
		$sql.= ", fk_origin_stock";
		$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet_dluo ";
		$sql.= " WHERE fk_expeditiondet=".(int)$id_line_expdet;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
			$num=$db->num_rows($resql);
            $i=0;
			while ($i<$num) {
				$tmp=new ExpeditionLigneDluo($db);

				$obj = $db->fetch_object($resql);

				$tmp->dluo = $db->jdate($obj->dluo);
				$tmp->dlc = $db->jdate($obj->dlc);
				$tmp->lot = $obj->lot;
				$tmp->id = $obj->rowid;
				$tmp->fk_origin_stock = $obj->fk_origin_stock;
				$tmp->fk_expeditiondet = $obj->fk_expeditiondet;
				$tmp->dluo_qty = $obj->qty;

				$ret[]=$tmp;
				$i++;
			}
			$db->free($resql);
			return $ret;
		} else {
			return -1;
		}
	}

}
?>
