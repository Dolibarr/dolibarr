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
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Put here description of your class
 */
class Productdluo extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	var $element='productdluo';			//!< Id that identify managed objects
	var $table_element='productdluo';		//!< Name of table without prefix where object is stored

    var $id;
    
	var $tms='';
	var $fk_product_stock;
	var $dluo='';
	var $dlc='';
	var $lot='';
	var $qty;
	var $import_key;

    


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
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->clean_param();

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_dluo(";
		
		$sql.= "fk_product_stock,";
		$sql.= "dluo,";
		$sql.= "dlc,";
		$sql.= "lot,";
		$sql.= "qty,";
		$sql.= "import_key";

		
        $sql.= ") VALUES (";
        
		$sql.= " ".(! isset($this->fk_product_stock)?'NULL':$this->fk_product_stock).",";
		$sql.= " ".(! isset($this->dluo) || dol_strlen($this->dluo)==0?'NULL':$this->db->idate($this->dluo)).",";
		$sql.= " ".(! isset($this->dlc) || dol_strlen($this->dlc)==0?'NULL':$this->db->idate($this->dlc)).",";
		$sql.= " ".(! isset($this->lot)?'NULL':"'".$this->db->escape($this->lot)."'").",";
		$sql.= " ".(! isset($this->qty)?'NULL':$this->qty).",";
		$sql.= " ".(! isset($this->import_key)?'NULL':"'".$this->db->escape($this->import_key)."'")."";

        
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_dluo");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
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
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.tms,";
		$sql.= " t.fk_product_stock,";
		$sql.= " t.dluo,";
		$sql.= " t.dlc,";
		$sql.= " t.lot,";
		$sql.= " t.qty,";
		$sql.= " t.import_key";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."product_dluo as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_product_stock = $obj->fk_product_stock;
				$this->dluo = $this->db->jdate($obj->dluo);
				$this->dlc = $this->db->jdate($obj->dlc);
				$this->lot = $obj->lot;
				$this->qty = $obj->qty;
				$this->import_key = $obj->import_key;

                
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


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		$this->clean_param();


		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."product_dluo SET";
        
		$sql.= " fk_product_stock=".(isset($this->fk_product_stock)?$this->fk_product_stock:"null").",";
		$sql.= " dluo=".(dol_strlen($this->dluo)!=0 ? "'".$this->db->idate($this->dluo)."'" : 'null').",";
		$sql.= " dlc=".(dol_strlen($this->dlc)!=0 ? "'".$this->db->idate($this->dlc)."'" : 'null').",";
		$sql.= " lot=".(isset($this->lot)?"'".$this->db->escape($this->lot)."'":"null").",";
		$sql.= " qty=".(isset($this->qty)?$this->qty:"null").",";
		$sql.= " import_key=".(isset($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null")."";

        
        $sql.= " WHERE rowid=".$this->id." AND tms='".$this->db->idate($this->tms)."'";

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
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
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_dluo";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete sql=".$sql);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
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
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Productdluo($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		// End
		if (! $error)
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
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->tms='';
		$this->fk_product_stock='';
		$this->dluo='';
		$this->dlc='';
		$this->lot='';
		$this->import_key='';

		
	}

	private function clean_param() {
		if (isset($this->fk_product_stock)) $this->fk_product_stock=(int)trim($this->fk_product_stock);
		if (isset($this->lot)) $this->lot=trim($this->lot);
		if (isset($this->qty)) $this->qty=(float)trim($this->qty);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);
	}

    /**
     *  Load object in memory from the database
     *
     *  @param	int			$fk_product_stck    id product_stock for objet
     *  @param	date		$dlc    eat-by date for objet
     *  @param	date		$dluo   sell-by date for objet
     *  @param	string		$lot   	lot number for objet
     *  @return int          	<0 if KO, >0 if OK
     */
    function find($fk_product_stock=0, $dlc='',$dluo='',$lot_number='')
    {
    	global $langs;
		$where = array();
        $sql = "SELECT";
		$sql.= " t.rowid,";
		
		$sql.= " t.tms,";
		$sql.= " t.fk_product_stock,";
		$sql.= " t.dluo,";
		$sql.= " t.dlc,";
		$sql.= " t.lot,";
		$sql.= " t.qty,";
		$sql.= " t.import_key";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."product_dluo as t";
		$sql.= " WHERE fk_product_stock=".$fk_product_stock;
        if (! empty($dlc)) array_push($where," dlc = '".$this->db->idate($dlc)."'");
		if (! empty($dluo)) array_push($where," dluo = '".$this->db->idate($dluo)."'");
		if (! empty($lot_number)) array_push($where," lot = '".$this->db->escape($lot_number)."'");

		if (! empty($where)) $sql.= " AND (".implode(" OR ",$where).")";
		
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                
				$this->tms = $this->db->jdate($obj->tms);
				$this->fk_product_stock = $obj->fk_product_stock;
				$this->dluo = $this->db->jdate($obj->dluo);
				$this->dlc = $this->db->jdate($obj->dlc);
				$this->lot = $obj->lot;
				$this->qty = $obj->qty;
				$this->import_key = $obj->import_key;
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
    /**
     *  Load object in memory from the database
     *
     *  @param	obj			$db    database object
     *  @param	int			$fk_product_stck    id product_stock for objet
     *  @param	int			$with_qty    doesn't return line with 0 quantity
	 *  @return int          	<0 if KO, >0 if OK
     */
    public static function findAll($db,$fk_product_stock,$with_qty=0)
    {
    	global $langs;
		$ret = array();
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.tms,";
		$sql.= " t.fk_product_stock,";
		$sql.= " t.dluo,";
		$sql.= " t.dlc,";
		$sql.= " t.lot,";
		$sql.= " t.qty,";
		$sql.= " t.import_key";

		
        $sql.= " FROM ".MAIN_DB_PREFIX."product_dluo as t";
		$sql.= " WHERE fk_product_stock=".$fk_product_stock;
		
		if ($with_qty) $sql.= " AND qty<>0";
    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$db->query($sql);
        if ($resql)
        {
			$num = $db->num_rows($resql);
			$i=0;
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
				$tmp=new ProductDluo($db);
				
                $tmp->id    = $obj->rowid;
                
				$tmp->tms = $db->jdate($obj->tms);
				$tmp->fk_product_stock = $obj->fk_product_stock;
				$tmp->dluo = $db->jdate($obj->dluo);
				$tmp->dlc = $db->jdate($obj->dlc);
				$tmp->lot = $obj->lot;
				$tmp->qty = $obj->qty;
				$tmp->import_key = $obj->import_key;

				array_push($ret,$tmp);
				$i++;
            }
            $db->free($resql);

            return $ret;
        }
        else
        {
      	    $error="Error ".$db->lasterror();
            dol_syslog("ProductDluo::find_all ".$error, LOG_ERR);
            return -1;
        }
    }

}
?>
