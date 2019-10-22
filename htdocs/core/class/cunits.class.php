<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/core/class/cunits.class.php
 *      \ingroup    core
 *      \brief      This file is CRUD class file (Create/Read/Update/Delete) for c_units dictionary
 */


/**
 *	Class of dictionary type of thirdparty (used by imports)
 */
class CUnits // extends CommonObject
{
	/**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error='';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();
	public $records = array();

	//var $element='ctypent';			//!< Id that identify managed objects
	//var $table_element='ctypent';	//!< Name of table without prefix where object is stored

    /**
	 * @var int ID
	 */
	public $id;

	public $code;
	public $label;
	public $short_label;
	public $unit_type;
	public $scale;
	public $active;




    /**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
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
     *  @return     int      		   	 <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->id)) $this->id = (int) $this->id;
		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->label)) $this->libelle=trim($this->label);
		if (isset($this->short_label)) $this->libelle=trim($this->short_label);
		if (isset($this->unit_type)) $this->active=trim($this->unit_type);
		if (isset($this->active)) $this->active=trim($this->active);

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_units(";
		$sql.= "rowid,";
		$sql.= "code,";
		$sql.= "label,";
		$sql.= "short_label,";
		$sql.= "unit_type";
        $sql.= ") VALUES (";
		$sql.= " ".(! isset($this->id)?'NULL':"'".$this->db->escape($this->id)."'").",";
		$sql.= " ".(! isset($this->code)?'NULL':"'".$this->db->escape($this->code)."'").",";
		$sql.= " ".(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'").",";
		$sql.= " ".(! isset($this->short_label)?'NULL':"'".$this->db->escape($this->short_label)."'").",";
		$sql.= " ".(! isset($this->unit_type)?'NULL':"'".$this->db->escape($this->unit_type)."'");
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."c_units");

			//if (! $notrigger)
			//{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
	            //if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            //// End call triggers
			//}
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
     *  Load object in memory from database
     *
     *  @param      int		$id    	Id object
     *  @param		string	$code	Code
     *  @param		string	$short_label	Short Label
     *  @param		string	$unit_type	unit type
     *  @return     int		<0 if KO, >0 if OK
     */
    public function fetch($id, $code = '', $short_label = '', $unit_type = '')
    {
    	global $langs;

        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.code,";
		$sql.= " t.label,";
		$sql.= " t.short_label,";
		$sql.= " t.scale,";
		$sql.= " t.unit_type,";
		$sql.= " t.active";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_units as t";
        $sql_where=array();
        if ($id)   $sql_where[]= " t.id = ".$id;
        if ($unit_type)   $sql_where[]= " t.unit_type = '".$this->db->escape($unit_type)."'";
        if ($code) $sql_where[]= " t.code = '".$this->db->escape($code)."'";
        if ($short_label) $sql_where[]= " t.short_label = '".$this->db->escape($short_label)."'";
        if (count($sql_where)>0) {
        	$sql.=' WHERE '. implode(' AND ', $sql_where);
        }

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
				$this->code = $obj->code;
				$this->label = $obj->label;
				$this->short_label = $obj->short_label;
				$this->scale = $obj->scale;
				$this->unit_type = $obj->unit_type;
				$this->active = $obj->active;
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
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return array|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
    	global $conf;

    	dol_syslog(__METHOD__, LOG_DEBUG);

    	$records=array();

    	$sql = 'SELECT';
    	$sql.= " t.rowid,";
    	$sql.= " t.code,";
    	$sql.= " t.label,";
    	$sql.= " t.short_label,";
    	$sql.= " t.unit_type,";
    	$sql.= " t.scale,";
    	$sql.= " t.active";
    	$sql .= ' FROM ' . MAIN_DB_PREFIX . 'c_units as t';
    	// Manage filter
    	$sqlwhere = array();
    	if (count($filter) > 0) {
    		foreach ($filter as $key => $value) {
    			if ($key=='t.rowid' || $key=='t.active' || $key=='t.scale') {
    				$sqlwhere[] = $key . '='. (int) $value;
    			}
    			elseif (strpos($key, 'date') !== false) {
    				$sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
    			}
    			elseif ($key=='t.unit_type' || $key=='t.code' || $key=='t.short_label') {
    				$sqlwhere[] =  $key.' = \''.$this->db->escape($value).'\'';
    			}
    			else {
    				$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
    			}
    		}
    	}
    	if (count($sqlwhere) > 0) {
    		$sql .= ' WHERE (' . implode(' '.$filtermode.' ', $sqlwhere).')';
    	}

    	if (!empty($sortfield)) {
    		$sql .= $this->db->order($sortfield, $sortorder);
    	}
    	if (!empty($limit)) {
    		$sql .=  ' ' . $this->db->plimit($limit, $offset);
    	}
    	$resql = $this->db->query($sql);
    	if ($resql) {
    		$this->records=array();
    		$num = $this->db->num_rows($resql);
    		if ($num>0) {
	    		while ($obj = $this->db->fetch_object($resql))
	    		{
	    			$record = new self($this->db);

	    			$record->id    = $obj->rowid;
	    			$record->code = $obj->code;
	    			$record->label = $obj->label;
	    			$record->short_label = $obj->short_label;
	    			$record->unit_type = $obj->unit_type;
	    			$record->scale = $obj->scale;
	    			$record->active = $obj->active;
	    			$this->records[$record->id] = $record;
	    		}
    		}
    		$this->db->free($resql);

    		return $this->records;
    	} else {
    		$this->errors[] = 'Error ' . $this->db->lasterror();
    		dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

    		return -1;
    	}
    }


    /**
     *  Update object into database
     *
     *  @param      User	$user        User that modify
     *  @param      int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return     int     		   	 <0 if KO, >0 if OK
     */
    public function update($user = null, $notrigger = 0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->label)) $this->libelle=trim($this->label);
		if (isset($this->short_label)) $this->libelle=trim($this->short_label);
		if (isset($this->unit_type)) $this->libelle=trim($this->unit_type);
		if (isset($this->active)) $this->active=trim($this->active);

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_units SET";
		$sql.= " code=".(isset($this->code)?"'".$this->db->escape($this->code)."'":"null").",";
		$sql.= " label=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
		$sql.= " short_label=".(isset($this->short_label)?"'".$this->db->escape($this->short_label)."'":"null").",";
		$sql.= " unit_type=".(isset($this->unit_type)?"'".$this->db->escape($this->unit_type)."'":"null").",";
		$sql.= " active=".(isset($this->active)?$this->active:"null");
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		//if (! $error)
		//{
		//	if (! $notrigger)
		//	{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

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
     *	@param  User	$user        User that delete
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."c_units";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		//if (! $error)
		//{
		//	if (! $notrigger)
		//	{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action call a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
		//	}
		//}

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
}
