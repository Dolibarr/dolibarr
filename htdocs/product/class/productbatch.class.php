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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       product/class/productbatch.class.php
 *  \ingroup    productbatch
 *  \brief      Manage record and specific data for batch number management
 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";


/**
 *	Manage record for batch number management
 */
class Productbatch extends CommonObject
{
	/**
	 * Batches rules
	 */
	const BATCH_RULE_SELLBY_EATBY_DATES_FIRST = 1;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'productbatch';

	private static $_table_element = 'product_batch'; //!< Name of table without prefix where object is stored

	public $tms = '';
	public $fk_product_stock;
	public $sellby = '';
	public $eatby = '';
	public $batch = '';
	public $qty;
	public $warehouseid;

	/**
     * @var int ID
     */
	public $fk_product;



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
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->cleanParam();

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_batch (";
		$sql .= "fk_product_stock,";
		$sql .= "sellby,";
		$sql .= "eatby,";
		$sql .= "batch,";
		$sql .= "qty,";
		$sql .= "import_key";
		$sql .= ") VALUES (";
		$sql .= " ".(!isset($this->fk_product_stock) ? 'NULL' : $this->fk_product_stock).",";
		$sql .= " ".(!isset($this->sellby) || dol_strlen($this->sellby) == 0 ? 'NULL' : "'".$this->db->idate($this->sellby)."'").",";
		$sql .= " ".(!isset($this->eatby) || dol_strlen($this->eatby) == 0 ? 'NULL' : "'".$this->db->idate($this->eatby)."'").",";
		$sql .= " ".(!isset($this->batch) ? 'NULL' : "'".$this->db->escape($this->batch)."'").",";
		$sql .= " ".(!isset($this->qty) ? 'NULL' : $this->qty).",";
		$sql .= " ".(!isset($this->import_key) ? 'NULL' : "'".$this->db->escape($this->import_key)."'")."";

		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
		if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }
		if (!$error)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.self::$_table_element);
		}

        // Commit or rollback
		if ($error)
		{
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
            return $this->id;
		}
    }


    /**
     *  Load object in memory from the database
     *
     *  @param	int		$id		Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    public function fetch($id)
    {
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.tms,";
		$sql .= " t.fk_product_stock,";
		$sql .= " t.sellby as oldsellby,";
		$sql .= " t.eatby as oldeatby,";
		$sql .= " t.batch,";
		$sql .= " t.qty,";
		$sql .= " t.import_key,";
		$sql .= " w.fk_entrepot,";
		$sql .= " w.fk_product,";
		$sql .= " pl.eatby,";
		$sql .= " pl.sellby";

        $sql .= " FROM ".MAIN_DB_PREFIX."product_batch as t INNER JOIN ".MAIN_DB_PREFIX."product_stock w on t.fk_product_stock = w.rowid";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl on pl.fk_product = w.fk_product and pl.batch = t.batch";
        $sql .= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_product_stock = $obj->fk_product_stock;
				$this->sellby = $this->db->jdate($obj->sellby ? $obj->sellby : $obj->oldsellby);
				$this->eatby = $this->db->jdate($obj->eatby ? $obj->eatby : $obj->oldeatby);
				$this->batch = $obj->batch;
				$this->qty = $obj->qty;
				$this->import_key = $obj->import_key;
				$this->warehouseid = $obj->fk_entrepot;
				$this->fk_product = $obj->fk_product;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error = "Error ".$this->db->lasterror();
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
    public function update($user = null, $notrigger = 0)
    {
    	global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->cleanParam();

		// TODO Check qty is ok for stock move. Negative may not be allowed.
		if ($this->qty < 0)
		{
		}

        // Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.self::$_table_element." SET";
		$sql .= " fk_product_stock=".(isset($this->fk_product_stock) ? $this->fk_product_stock : "null").",";
		$sql .= " sellby=".(dol_strlen($this->sellby) != 0 ? "'".$this->db->idate($this->sellby)."'" : 'null').",";
		$sql .= " eatby=".(dol_strlen($this->eatby) != 0 ? "'".$this->db->idate($this->eatby)."'" : 'null').",";
		$sql .= " batch=".(isset($this->batch) ? "'".$this->db->escape($this->batch)."'" : "null").",";
		$sql .= " qty=".(isset($this->qty) ? $this->qty : "null").",";
		$sql .= " import_key=".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null")."";
        $sql .= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

        // Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }

    /**
     *  Delete object in database
     *
     *  @param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return	int					 <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (!$error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX.self::$_table_element."";
    		$sql .= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
    		$resql = $this->db->query($sql);
        	if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}



	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		User making the clone
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error = 0;

		$object = new Productbatch($this->db);

 		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error = $object->error;
			$this->errors = array_merge($this->errors, $object->errors);
			$error++;
		}

		if (!$error)
		{
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->tms = '';
		$this->fk_product_stock = '';
		$this->sellby = '';
		$this->eatby = '';
		$this->batch = '';
		$this->import_key = '';
	}

	/**
	 *  Clean fields (triming)
	 *
	 *  @return	void
	 */
	private function cleanParam()
	{
		if (isset($this->fk_product_stock)) $this->fk_product_stock = (int) trim($this->fk_product_stock);
		if (isset($this->batch)) $this->batch = trim($this->batch);
		if (isset($this->qty)) $this->qty = (float) trim($this->qty);
		if (isset($this->import_key)) $this->import_key = trim($this->import_key);
	}

    /**
     *  Find first detail record that match eather eat-by or sell-by or batch within given warehouse
     *
     *  @param	int			$fk_product_stock   id product_stock for objet
     *  @param	integer		$eatby    			eat-by date for object - deprecated: a search must be done on batch number
     *  @param	integer		$sellby   			sell-by date for object - deprecated: a search must be done on batch number
     *  @param	string		$batch_number   	batch number for object
     *  @return int          					<0 if KO, >0 if OK
     */
    public function find($fk_product_stock = 0, $eatby = '', $sellby = '', $batch_number = '')
    {
    	global $langs;

		$where = array();
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.fk_product_stock,";
		$sql .= " t.sellby,"; // deprecated
		$sql .= " t.eatby,"; // deprecated
		$sql .= " t.batch,";
		$sql .= " t.qty,";
		$sql .= " t.import_key";
		$sql .= " FROM ".MAIN_DB_PREFIX.self::$_table_element." as t";
		$sql .= " WHERE fk_product_stock=".$fk_product_stock;

		if (!empty($eatby)) array_push($where, " eatby = '".$this->db->idate($eatby)."'"); // deprecated
		if (!empty($sellby)) array_push($where, " sellby = '".$this->db->idate($sellby)."'"); // deprecated

		if (!empty($batch_number)) $sql .= " AND batch = '".$this->db->escape($batch_number)."'";

		if (!empty($where)) $sql .= " AND (".implode(" OR ", $where).")";

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_product_stock = $obj->fk_product_stock;
				$this->sellby = $this->db->jdate($obj->sellby);
				$this->eatby = $this->db->jdate($obj->eatby);
				$this->batch = $obj->batch;
				$this->qty = $obj->qty;
				$this->import_key = $obj->import_key;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error = "Error ".$this->db->lasterror();
            return -1;
        }
    }
    /**
     * Return all batch detail records for a given product and warehouse
     *
     *  @param	DoliDB		$db    				database object
     *  @param	int			$fk_product_stock	id product_stock for objet
     *  @param	int			$with_qty    		1 = doesn't return line with 0 quantity
     *  @param  int         $fk_product         If set to a product id, get eatby and sellby from table llx_product_lot
     *  @return array         					<0 if KO, array of batch
     */
    public static function findAll($db, $fk_product_stock, $with_qty = 0, $fk_product = 0)
    {
    	global $langs;
		$ret = array();

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.tms,";
		$sql .= " t.fk_product_stock,";
		$sql .= " t.sellby as oldsellby,"; // deprecated but may not be migrated into new table
		$sql .= " t.eatby as oldeatby,"; // deprecated but may not be migrated into new table
		$sql .= " t.batch,";
		$sql .= " t.qty,";
		$sql .= " t.import_key";
		if ($fk_product > 0)
		{
		    $sql .= ", pl.rowid as lotid, pl.eatby as eatby, pl.sellby as sellby";
		    // TODO May add extrafields to ?
		}
        $sql .= " FROM ".MAIN_DB_PREFIX."product_batch as t";
        if ($fk_product > 0)
        {
            $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON pl.fk_product = ".$fk_product." AND pl.batch = t.batch";
            // TODO May add extrafields to ?
        }
		$sql .= " WHERE fk_product_stock=".$fk_product_stock;
		if ($with_qty) $sql .= " AND t.qty <> 0";

		dol_syslog("productbatch::findAll", LOG_DEBUG);
		$resql = $db->query($sql);
        if ($resql) {
            $num = $db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);

				$tmp = new Productbatch($db);
				$tmp->id    = $obj->rowid;
				$tmp->lotid = $obj->lotid;
				$tmp->tms = $db->jdate($obj->tms);
				$tmp->fk_product_stock = $obj->fk_product_stock;
				$tmp->sellby = $db->jdate($obj->sellby ? $obj->sellby : $obj->oldsellby);
				$tmp->eatby = $db->jdate($obj->eatby ? $obj->eatby : $obj->oldeatby);
				$tmp->batch = $obj->batch;
				$tmp->qty = $obj->qty;
				$tmp->import_key = $obj->import_key;

				$ret[$tmp->batch] = $tmp; // $ret is for a $fk_product_stock and unique key is on $fk_product_stock+batch
				$i++;
            }
            $db->free($resql);

            return $ret;
        }
        else
        {
            $error = "Error ".$db->lasterror();
            return -1;
        }
    }

	/**
	 * Return all batch for a product and a warehouse
	 *
	 * @param	DoliDB		$db    				Database object
	 * @param	int			$fk_product         Id of product
	 * @param	int			$fk_warehouse       Id of warehouse
	 * @param	int			$qty_min            [=NULL] Minimum quantity
	 * @param	string		$sortfield		    [=NULL] List of sort fields, separated by comma. Example: 't1.fielda,t2.fieldb'
	 * @param	string		$sortorder		    [=NULL] Sort order, separated by comma. Example: 'ASC,DESC';
	 * @return  int|array   <0 if KO, array of batch
	 *
	 * @throws  Exception
	 */
	public static function findAllForProduct($db, $fk_product, $fk_warehouse = 0, $qty_min = null, $sortfield = null, $sortorder = null)
	{
		$productBatchList = array();

		dol_syslog(__METHOD__.' fk_product='.$fk_product.', fk_warehouse='.$fk_warehouse.', qty_min='.$qty_min.', sortfield='.$sortfield.', sortorder='.$sortorder, LOG_DEBUG);

		$sql  = "SELECT";
		$sql .= " pl.rowid";
		$sql .= ", pl.fk_product";
		$sql .= ", pl.batch";
		$sql .= ", pl.sellby";
		$sql .= ", pl.eatby";
		$sql .= ", pb.qty";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_lot as pl";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = pl.fk_product";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_batch AS pb ON pl.batch = pb.batch";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock AS ps ON ps.rowid = pb.fk_product_stock";
		$sql .= " WHERE p.entity IN (".getEntity('product').")";
		$sql .= " AND pl.fk_product = ".$fk_product;
		if ($fk_warehouse > 0) {
			$sql .= " AND ps.fk_entrepot = ".$fk_warehouse;
		}
		if ($qty_min !== null) {
			$sql .= " AND pb.qty > ".$qty_min;
		}
		$sql .= $db->order($sortfield, $sortorder);

		$resql = $db->query($sql);
		if ($resql) {
			while ($obj = $db->fetch_object($resql)) {
				$productBatch             = new self($db);
				$productBatch->id         = $obj->rowid;
				$productBatch->fk_product = $obj->fk_product;
				$productBatch->batch      = $obj->batch;
				$productBatch->eatby      = $db->jdate($obj->eatby);
				$productBatch->sellby     = $db->jdate($obj->sellby);
				$productBatch->qty        = $obj->qty;
				$productBatchList[]       = $productBatch;
			}
			$db->free($resql);

			return $productBatchList;
		} else {
			dol_syslog(__METHOD__.' Error: '.$db->lasterror(), LOG_ERR);
			return -1;
		}
	}
}
