<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
/* Copyright (C) 2015      Ion Agorria          <ion@agorria.com>
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
 *	\file       htdocs/product/dynamic_price/class/price_expression.class.php
 *	\ingroup    product
 *  \brief      Class for accessing price expression table
 */


/**
 *	Class for accesing price expression table
 */
class PriceExpression
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
    var $id;
    var $title;
	var $expression;
    public $table_element = "c_price_expression";

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
        $error=0;

		// Clean parameters
		if (isset($this->title)) $this->title=trim($this->title);
		if (isset($this->expression)) $this->expression=trim($this->expression);

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql.= "title, expression";
		$sql.= ") VALUES (";
		$sql.= " ".(isset($this->title)?"'".$this->db->escape($this->title)."'":"''").",";
		$sql.= " ".(isset($this->expression)?"'".$this->db->escape($this->expression)."'":"''");
		$sql.= ")";

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_CREATE',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			}
        }

        // Commit or rollback
        if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
     *  @param		int		$id    	Id object
     *  @return		int			    < 0 if KO, 0 if OK but not found, > 0 if OK
     */
    function fetch($id)
    {
        // Check parameters
        if (empty($id))
        {
            $this->error='ErrorWrongParameters';
            return -1;
        }
        
        $sql = "SELECT title, expression";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid = ".$id;

    	dol_syslog(__METHOD__);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                $this->id			= $id;
                $this->title		= $obj->title;
                $this->expression	= $obj->expression;
                return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }

    /**
     *    List all price expressions
     *
     *    @return	array				Array of price expressions
     */
    function list_price_expression()
    {
        $sql = "SELECT rowid, title, expression";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " ORDER BY title";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $retarray = array();

            while ($record = $this->db->fetch_array($resql))
            {
                $price_expression_obj = new PriceExpression($this->db);
                $price_expression_obj->id			= $record["rowid"];
                $price_expression_obj->title		= $record["title"];
				$price_expression_obj->expression	= $record["expression"];
                $retarray[]=$price_expression_obj;
            }

            $this->db->free($resql);
            return $retarray;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *  Returns any existing rowid with specified title
     *
     *  @param		String	$title  Title of expression
     *  @return		int			    < 0 if KO, 0 if OK but not found, > 0 rowid
     */
    function find_title($title)
    {
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE title = '".$this->db->escape($title)."'";

    	dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
            	return (int) $obj->rowid;
            }
            else
            {
                return 0;
            }
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
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
		$error=0;

		// Clean parameters
		if (isset($this->title)) $this->title=trim($this->title);
		if (isset($this->expression)) $this->expression=trim($this->expression);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql.= " title = ".(isset($this->title)?"'".$this->db->escape($this->title)."'":"''").",";
		$sql.= " expression = ".(isset($this->expression)?"'".$this->db->escape($this->expression)."'":"''")."";
        $sql.= " WHERE rowid = ".$this->id;

		$this->db->begin();

    	dol_syslog(__METHOD__);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			 }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	function delete(User $user, $notrigger=0)
	{
		$error=0;

		$rowid = $this->id;
		
		$this->db->begin();

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

	            //// Call triggers
	            //$result=$this->call_trigger('MYOBJECT_DELETE',$user);
	            //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
	            //// End call triggers
			}
		}

		if (! $error)
		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
    		$sql.= " WHERE rowid = ".$rowid;

	        dol_syslog(__METHOD__);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	        	dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		$this->expression='';
	}
}
