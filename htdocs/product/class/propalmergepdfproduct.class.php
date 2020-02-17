<?php
/* Copyright (C) 2004-2015 	Laurent Destailleur   	<eldy@users.sourceforge.net>
 * Copyright (C) 2015 		Florian HENRY 			<florian.henry@open-concept.pro>
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
 *  \file       htdocs/product/class/propalmergepdfproduct.class.php
 *  \ingroup    product
 *  \brief      This file is an CRUD class file (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";



/**
 *	Put here description of your class
 */
class Propalmergepdfproduct extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='propal_merge_pdf_product';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='propal_merge_pdf_product';

	public $fk_product;
	public $file_name;
	public $fk_user_author;
	public $fk_user_mod;
	public $datec='';
	public $tms='';
	public $lang;

	public $lines=array();




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
		$error=0;

		// Clean parameters

		if (isset($this->fk_product)) $this->fk_product=trim($this->fk_product);
		if (isset($this->file_name)) $this->file_name=trim($this->file_name);
		if (isset($this->fk_user_author)) $this->fk_user_author=trim($this->fk_user_author);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->lang)) $this->lang=trim($this->lang);
		if (isset($this->import_key)) $this->import_key=trim($this->import_key);



		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."propal_merge_pdf_product(";

		$sql.= "fk_product,";
		$sql.= "file_name,";
		if ($conf->global->MAIN_MULTILANGS) {
			$sql.= "lang,";
		}
		$sql.= "fk_user_author,";
		$sql.= "fk_user_mod,";
		$sql.= "datec";


        $sql.= ") VALUES (";

		$sql.= " ".(! isset($this->fk_product)?'NULL':"'".$this->db->escape($this->fk_product)."'").",";
		$sql.= " ".(! isset($this->file_name)?'NULL':"'".$this->db->escape($this->file_name)."'").",";
		if ($conf->global->MAIN_MULTILANGS) {
			$sql.= " ".(! isset($this->lang)?'NULL':"'".$this->db->escape($this->lang)."'").",";
		}
		$sql.= " ".$user->id.",";
		$sql.= " ".$user->id.",";
		$sql.= " '".$this->db->idate(dol_now())."'";


		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."propal_merge_pdf_product");

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
    public function fetch($id)
    {
    	global $langs,$conf;

        $sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.fk_product,";
		$sql.= " t.file_name,";
		$sql.= " t.lang,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.datec,";
		$sql.= " t.tms,";
		$sql.= " t.import_key";


        $sql.= " FROM ".MAIN_DB_PREFIX."propal_merge_pdf_product as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

				$this->fk_product = $obj->fk_product;
				$this->file_name = $obj->file_name;
				if ($conf->global->MAIN_MULTILANGS) {
					$this->lang = $obj->lang;
				}
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Load object in memory from the database
     *
     *  @param	int		$product_id    	Id object
     *  @param	string	$lang  			Lang string code
     *  @return int          	<0 if KO, >0 if OK
     */
    public function fetch_by_product($product_id, $lang = '')
    {
        // phpcs:enable
    	global $langs,$conf;

    	$sql = "SELECT";
    	$sql.= " t.rowid,";

    	$sql.= " t.fk_product,";
    	$sql.= " t.file_name,";
    	$sql.= " t.lang,";
    	$sql.= " t.fk_user_author,";
    	$sql.= " t.fk_user_mod,";
    	$sql.= " t.datec,";
    	$sql.= " t.tms,";
    	$sql.= " t.import_key";


    	$sql.= " FROM ".MAIN_DB_PREFIX."propal_merge_pdf_product as t";
    	$sql.= " WHERE t.fk_product = ".$product_id;
    	if ($conf->global->MAIN_MULTILANGS && !empty($lang)) {
    		$sql.= " AND t.lang = '".$lang."'";
    	}

    	dol_syslog(__METHOD__, LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			while($obj = $this->db->fetch_object($resql)) {
    				$line = new PropalmergepdfproductLine();

	    			$line->id    = $obj->rowid;

	    			$line->fk_product = $obj->fk_product;
	    			$line->file_name = $obj->file_name;
	    			if ($conf->global->MAIN_MULTILANGS) {
	    				$line->lang = $obj->lang;
	    			}
	    			$line->fk_user_author = $obj->fk_user_author;
	    			$line->fk_user_mod = $obj->fk_user_mod;
	    			$line->datec = $this->db->jdate($obj->datec);
	    			$line->tms = $this->db->jdate($obj->tms);
	    			$line->import_key = $obj->import_key;


	    			if ($conf->global->MAIN_MULTILANGS) {
	    				$this->lines[$obj->file_name.'_'.$obj->lang]=$line;
	    			}else {
	    				$this->lines[$obj->file_name]=$line;
	    			}
    			}
    		}
    		$this->db->free($resql);

    		return 1;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
    		dol_syslog(get_class($this)."::fetch_by_product ".$this->error, LOG_ERR);
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
    public function update($user = 0, $notrigger = 0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->fk_product)) $this->fk_product=trim($this->fk_product);
		if (isset($this->file_name)) $this->file_name=trim($this->file_name);
		if (isset($this->fk_user_mod)) $this->fk_user_mod=trim($this->fk_user_mod);
		if (isset($this->lang)) $this->lang=trim($this->lang);

		// Check parameters
		// Put here code to add a control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."propal_merge_pdf_product SET";

		$sql.= " fk_product=".(isset($this->fk_product)?$this->fk_product:"null").",";
		$sql.= " file_name=".(isset($this->file_name)?"'".$this->db->escape($this->file_name)."'":"null").",";
		if ($conf->global->MAIN_MULTILANGS) {
			$sql.= " lang=".(isset($this->lang)?"'".$this->db->escape($this->lang)."'":"null").",";
		}
		$sql.= " fk_user_mod=".$user->id;


        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		//if (! $error)
		//{
		//	if (! $notrigger)
		//	{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
	    //	}
		//}

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
	public function delete($user, $notrigger = 0)
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
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal_merge_pdf_product";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(__METHOD__, LOG_DEBUG);
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *	@param  int		$product_id	 product_id
	 *  @param  string	$lang_id	 language
	 *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete_by_product($user, $product_id, $lang_id = '', $notrigger = 0)
	{
        // phpcs:enable
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		//if (! $error)
		//{
		//	if (! $notrigger)
		//	{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
		//	}
		//}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal_merge_pdf_product";
			$sql.= " WHERE fk_product=".$product_id;

			if ($conf->global->MAIN_MULTILANGS && !empty($lang_id)) {
				$sql.= " AND lang='".$lang_id."'";
			}

			dol_syslog(__METHOD__, LOG_DEBUG);
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

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Delete object in database
	 *
	 *	@param  User	$user        User that deletes
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete_by_file($user)
	{
        // phpcs:enable
		global $conf, $langs;
		$error=0;

		$this->db->begin();

		//if (! $error)
		//{
		//	if (! $notrigger)
		//	{
				// Uncomment this and change MYOBJECT to your own tag if you
				// want this action calls a trigger.

				//// Call triggers
				//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
				//$interface=new Interfaces($this->db);
				//$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
				//if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
		//	}
		//}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."propal_merge_pdf_product";
			$sql.= " WHERE fk_product=".$this->fk_product." AND file_name='".$this->db->escape($this->file_name)."'";

			dol_syslog(__METHOD__, LOG_DEBUG);
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
	 *  @param	User	$user		User making the clone
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error=0;

		$object=new Propalmergepdfproduct($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone']='createfromclone';
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$this->errors=array_merge($this->errors, $object->errors);
			$error++;
		}

		if (! $error)
		{
		}

		unset($object->context['createfromclone']);

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
	public function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_product='';
		$this->file_name='';
		$this->fk_user_author='';
		$this->fk_user_mod='';
		$this->datec='';
		$this->tms='';
		$this->import_key='';
	}
}

/**
 * Class to manage propal merge of product line
 */
class PropalmergepdfproductLine
{
    /**
     * @var int ID
     */
    public $id;

    /**
     * @var int ID
     */
    public $fk_product;

	public $file_name;
	public $lang;

	/**
     * @var int ID
     */
	public $fk_user_author;

	/**
     * @var int ID
     */
	public $fk_user_mod;

	public $datec='';
	public $tms='';
	public $import_key;

    /**
     *  Constructor
     */
    public function __construct()
    {
        return 1;
    }
}
