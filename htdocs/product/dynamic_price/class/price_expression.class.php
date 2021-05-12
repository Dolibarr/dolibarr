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
<<<<<<< HEAD
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
    var $id;
    var $title;
	var $expression;
=======
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

    /**
     * @var int ID
     */
    public $id;

    public $title;
    public $expression;

    /**
     * @var string Name of table without prefix where object is stored
     */
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    public $table_element = "c_price_expression";

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
<<<<<<< HEAD
    function __construct($db)
    {
        $this->db = $db;
        return 1;
=======
    public function __construct($db)
    {
        $this->db = $db;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
<<<<<<< HEAD
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
=======
    public function create($user, $notrigger = 0)
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

            //if (! $notrigger)
            //{
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //$result=$this->call_trigger('MYOBJECT_CREATE',$user);
                //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
                //// End call triggers
            //}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

        // Commit or rollback
        if ($error)
<<<<<<< HEAD
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
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }


    /**
     *  Load object in memory from the database
     *
     *  @param		int		$id    	Id object
     *  @return		int			    < 0 if KO, 0 if OK but not found, > 0 if OK
     */
<<<<<<< HEAD
    function fetch($id)
=======
    public function fetch($id)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        // Check parameters
        if (empty($id))
        {
            $this->error='ErrorWrongParameters';
            return -1;
        }
<<<<<<< HEAD
        
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $sql = "SELECT title, expression";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid = ".$id;

<<<<<<< HEAD
    	dol_syslog(__METHOD__);
=======
        dol_syslog(__METHOD__);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
      	    $this->error="Error ".$this->db->lasterror();
=======
              $this->error="Error ".$this->db->lasterror();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            return -1;
        }
    }

<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     *    List all price expressions
     *
     *    @return	array				Array of price expressions
     */
<<<<<<< HEAD
    function list_price_expression()
    {
=======
    public function list_price_expression()
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
				$price_expression_obj->expression	= $record["expression"];
=======
                $price_expression_obj->expression	= $record["expression"];
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     *  Returns any existing rowid with specified title
     *
     *  @param		String	$title  Title of expression
     *  @return		int			    < 0 if KO, 0 if OK but not found, > 0 rowid
     */
<<<<<<< HEAD
    function find_title($title)
    {
=======
    public function find_title($title)
    {
        // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $sql = "SELECT rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE title = '".$this->db->escape($title)."'";

<<<<<<< HEAD
    	dol_syslog(__METHOD__, LOG_DEBUG);
=======
        dol_syslog(__METHOD__, LOG_DEBUG);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
<<<<<<< HEAD
            	return (int) $obj->rowid;
=======
                return (int) $obj->rowid;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            }
            else
            {
                return 0;
            }
        }
        else
        {
<<<<<<< HEAD
      	    $this->error="Error ".$this->db->lasterror();
=======
              $this->error="Error ".$this->db->lasterror();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
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
=======
    public function update($user = 0, $notrigger = 0)
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

        // if (! $error)
        // {
        //     if (! $notrigger)
        //     {
        //         // Uncomment this and change MYOBJECT to your own tag if you
        //         // want this action calls a trigger.

        //         //// Call triggers
        //         //$result=$this->call_trigger('MYOBJECT_MODIFY',$user);
        //         //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
        //         //// End call triggers
        //     }
        // }

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
    public function delete(User $user, $notrigger = 0)
    {
        $error=0;

        $rowid = $this->id;

        $this->db->begin();

        //if (! $error)
        //{
        //    if (! $notrigger)
        //    {
                // Uncomment this and change MYOBJECT to your own tag if you
                // want this action calls a trigger.

                //// Call triggers
                //$result=$this->call_trigger('MYOBJECT_DELETE',$user);
                //if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
                //// End call triggers
        //    }
        //}

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
     *  Initialise object with example values
     *  Id must be 0 if object instance is a specimen
     *
     *  @return	void
     */
    public function initAsSpecimen()
    {
        $this->id=0;
        $this->expression='';
    }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
