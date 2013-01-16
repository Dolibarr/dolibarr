<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       compta/facture/class/paymentterm.class.php
 *      \ingroup    facture
 *      \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *		\author		Put author name here
 */


/**
 *      \class      PaymentTerm
 *      \brief      Class to manage payment terms records in dictionnary
 *		\remarks	Initialy built by build_class_from_table on 2010-09-06 00:33
 */
class PaymentTerm // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//public  $element='c_payment_term';			//!< Id that identify managed objects
	//public  $table_element='c_payment_term';	//!< Name of table without prefix where object is stored

    var $id;

	var $code;
	var $sortorder;
	var $active;
	var $libelle;
	var $libelle_facture;
	var $fdm;
	var $nbjour;
	var $decalage;




    /**
     * 	Constructor
     *
	 * 	@param	DoliDB		$db			Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }


    /**
     *      Create in database
     *
     *      @param      User	$user        	User that create
     *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
     *      @return     int       			  	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->sortorder)) $this->sortorder=trim($this->sortorder);
		if (isset($this->active)) $this->active=trim($this->active);
		if (isset($this->libelle)) $this->libelle=trim($this->libelle);
		if (isset($this->libelle_facture)) $this->libelle_facture=trim($this->libelle_facture);
		if (isset($this->fdm)) $this->fdm=trim($this->fdm);
		if (isset($this->nbjour)) $this->nbjour=trim($this->nbjour);
		if (isset($this->decalage)) $this->decalage=trim($this->decalage);



		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."c_payment_term(";

		$sql.= "rowid,";
		$sql.= "code,";
		$sql.= "sortorder,";
		$sql.= "active,";
		$sql.= "libelle,";
		$sql.= "libelle_facture,";
		$sql.= "fdm,";
		$sql.= "nbjour,";
		$sql.= "decalage";


        $sql.= ") VALUES (";

		$sql.= " ".(! isset($this->rowid)?'NULL':"'".$this->rowid."'").",";
		$sql.= " ".(! isset($this->code)?'NULL':"'".$this->db->escape($this->code)."'").",";
		$sql.= " ".(! isset($this->sortorder)?'NULL':"'".$this->sortorder."'").",";
		$sql.= " ".(! isset($this->active)?'NULL':"'".$this->active."'").",";
		$sql.= " ".(! isset($this->libelle)?'NULL':"'".$this->db->escape($this->libelle)."'").",";
		$sql.= " ".(! isset($this->libelle_facture)?'NULL':"'".$this->db->escape($this->libelle_facture)."'").",";
		$sql.= " ".(! isset($this->fdm)?'NULL':"'".$this->fdm."'").",";
		$sql.= " ".(! isset($this->nbjour)?'NULL':"'".$this->nbjour."'").",";
		$sql.= " ".(! isset($this->decalage)?'NULL':"'".$this->decalage."'")."";


		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."c_payment_term");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

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
     *    Load object in memory from database
     *
     *    @param      int		$id     Id object
     *    @return     int         		<0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
        $sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.code,";
		$sql.= " t.sortorder,";
		$sql.= " t.active,";
		$sql.= " t.libelle,";
		$sql.= " t.libelle_facture,";
		$sql.= " t.fdm,";
		$sql.= " t.nbjour,";
		$sql.= " t.decalage";


        $sql.= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

				$this->code = $obj->code;
				$this->sortorder = $obj->sortorder;
				$this->active = $obj->active;
				$this->libelle = $obj->libelle;
				$this->libelle_facture = $obj->libelle_facture;
				$this->fdm = $obj->fdm;
				$this->nbjour = $obj->nbjour;
				$this->decalage = $obj->decalage;


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
     *    Return id of default payment term
     *
     *    @return     int         <0 if KO, >0 if OK
     */
    function getDefaultId()
    {
    	global $langs;

        $ret=0;

    	$sql = "SELECT";
		$sql.= " t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
        $sql.= " WHERE t.code = 'RECEP'";

    	dol_syslog(get_class($this)."::getDefaultId sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
                if ($obj) $ret=$obj->rowid;
            }
            $this->db->free($resql);
            return $ret;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::getDefaultId ".$this->error, LOG_ERR);
            return -1;
        }
    }


	/**
     *      Update database
     *
     *      @param      User	$user        	User that modify
     *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
     *      @return     int       			  	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters

		if (isset($this->code)) $this->code=trim($this->code);
		if (isset($this->sortorder)) $this->sortorder=trim($this->sortorder);
		if (isset($this->active)) $this->active=trim($this->active);
		if (isset($this->libelle)) $this->libelle=trim($this->libelle);
		if (isset($this->libelle_facture)) $this->libelle_facture=trim($this->libelle_facture);
		if (isset($this->fdm)) $this->fdm=trim($this->fdm);
		if (isset($this->nbjour)) $this->nbjour=trim($this->nbjour);
		if (isset($this->decalage)) $this->decalage=trim($this->decalage);



		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."c_payment_term SET";

		$sql.= " code=".(isset($this->code)?"'".$this->db->escape($this->code)."'":"null").",";
		$sql.= " sortorder=".(isset($this->sortorder)?$this->sortorder:"null").",";
		$sql.= " active=".(isset($this->active)?$this->active:"null").",";
		$sql.= " libelle=".(isset($this->libelle)?"'".$this->db->escape($this->libelle)."'":"null").",";
		$sql.= " libelle_facture=".(isset($this->libelle_facture)?"'".$this->db->escape($this->libelle_facture)."'":"null").",";
		$sql.= " fdm=".(isset($this->fdm)?$this->fdm:"null").",";
		$sql.= " nbjour=".(isset($this->nbjour)?$this->nbjour:"null").",";
		$sql.= " decalage=".(isset($this->decalage)?$this->decalage:"null")."";


        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action call a trigger.

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
     *	@param      User	$user  		User that delete
     *  @param      int		$notrigger	0=launch triggers after, 1=disable triggers
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;
		$error=0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."c_payment_term";
		$sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action call a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
			}
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
	 *		Load an object from its id and create a new one in database
	 *
	 *		@param      int		$fromid     Id of object to clone
	 * 	 	@return		int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new PaymentTerm($this->db);

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
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->code='';
		$this->sortorder='';
		$this->active='';
		$this->libelle='';
		$this->libelle_facture='';
		$this->fdm='';
		$this->nbjour='';
		$this->decalage='';
	}

}
?>
