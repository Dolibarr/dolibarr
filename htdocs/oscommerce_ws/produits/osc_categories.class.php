<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007 Jean Heimburger      <jean@tiaris.info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**
        \file       dev/skeletons/Osc_Categorie.class.class.php
        \ingroup    core
        \brief      Example for class
        \version    $Revision$
*/

// Put here all includes required by your script
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");



/**
        \class      Osc_Categorie.class
        \brief      Class description
*/

class Osc_Categorie
{
    var $db;
    var $error='';
    var $errors=array();
    
    var $id;
    var $dolicatid;
    var $osccatid;

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Osc_Categorie($DB) 
    {
        $this->db = $DB;
        return 1;
    }

	
    /**
     *      \brief      Create in database
     *      \param      user        User that create
     *      \return     int         <0 si ko, >0 si ok
     */
    function create($user)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        $this->dolicatid=trim($this->dolicatid);
        $this->ocscatid=trim($this->ocscatid);

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."osc_categories";
		$sql.= "( dolicatid, osccatid)";
        $sql.= " VALUES (";
        $sql.= " '".$this->dolicatid."',";
        $sql.= " '".$this->osccatid."'";
		$sql.= ")";
	   	dol_syslog("Osc_Categorie.class::create sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."osc_categories");
    
/*            $resql=$this->update($user, 1);
            if ($resql < 0)
            {
                $this->error=$this->db->lasterror();
                return -2;
            }

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

			$this->id = $newid;
			*/
            return $this->id;
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->error .= "erreur ".$sql;
            dol_syslog("Osc_Categorie.class::create ".$this->error);
            return -1;
        }
    }

    /*
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=no, 1=yes (no update trigger)
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
    	
		// Clean parameters
        $this->dolicatid=trim($this->dolicatid);
        $this->ocscatid=trim($this->ocscatid);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."osc_categories SET";
        $sql.= " dolicatid='".addslashes($this->dolicatid)."',";
        $sql.= " osccatid='".addslashes($this->osccatid)."'";
        $sql.= " WHERE rowid=".$this->id;
        dol_syslog("Osc_Categorie.class::update sql=".$sql,LOG_DEBUG);
    
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error=$this->db->lasterror().' sql='.$sql;
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers
    	}

        return 1;
    }
  
  
    /*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id, $user=0)
    {
    	global $langs;
        $sql = "SELECT t.rowid, t.dolicatid, t.osccatid";
        $sql.= " FROM ".MAIN_DB_PREFIX."osc_categories as t";
        $sql.= " WHERE c.rowid = ".$id;
    
    	dol_syslog("Osc_Categorie.class::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->dolicatid = $obj->dolicatid;
                $this->ocscatid = $obj->osccatid;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog("Osc_Categorie.class::fetch ".$this->error);
            return -1;
        }
    }
    
     /*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch_osccat($oscid, $user=0)
    {
    	global $langs;
        $sql = "SELECT t.rowid, t.dolicatid, t.osccatid";
        $sql.= " FROM ".MAIN_DB_PREFIX."osc_categories as t";
        $sql.= " WHERE t.osccatid = ".$oscid;
    
    	dol_syslog("Osc_Categorie.class::fetch_osccat sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->dolicatid = $obj->dolicatid;
                $this->osccatid = $obj->osccatid;
            }
            else 
            	$this->initAsSpecimen();
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog("Osc_Categorie.class::fetch_osccat ".$this->error);
            return -1;
        }
    } 
    
        /*
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \param      user        User that load
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch_dolicat($doliid, $user=0)
    {
    	global $langs;
        $sql = "SELECT t.rowid, t.dolicatid, t.osccatid";
        $sql.= " FROM ".MAIN_DB_PREFIX."osc_categories as t";
        $sql.= " WHERE t.dolicatid = ".$doliid;
    
    	dol_syslog("Osc_Categorie.class::fetch_dolicat sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->dolicatid = $obj->dolicatid;
                $this->ocscatid = $obj->osccatid;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog("Osc_Categorie.class::fetch_dolicat ".$this->error);
            return -1;
        }
    }
      
 	/*
	*   \brief      Delete object in database
    *	\param      user        User that delete
	*	\return		int			<0 if KO, >0 if OK
	*/
	function delete($user)
	{
		global $conf, $langs;
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."osc_categories";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dol_syslog("Osc_Categorie.class::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error=$this->db->lasterror().' sql='.$sql;
			return -1;
		}
	
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
        if ($result < 0) { $error++; $this->errors=$interface->errors; }
        // Fin appel triggers

		return 1;
	}

  
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		$this->dolicatid=0;
		$this->osccatid=0;
	}
	
}
?>
