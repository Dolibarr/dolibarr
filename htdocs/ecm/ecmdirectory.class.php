<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       dev/skeletons/ecm_directories.class.php
        \ingroup    mymodule othermodule1 othermodule2
        \brief      This file is an example for a class file
		\version    $Id$
		\author		Put author name here
		\remarks	Initialy built by build_class_from_table on 2008-02-24 19:24
*/

// Put here all includes required by your class file
//require_once(DOL_DOCUMENT_ROOT."/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
        \class      EcmDirectory
        \brief      Class to manage ECM directories
		\remarks	Initialy built by build_class_from_table on 2008-02-24 19:24
*/
class EcmDirectory // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='ecm_directories';			//!< Id that identify managed objects
	//var $table_element='ecm_directories';	//!< Name of table without prefix where object is stored
    
    var $id;
    
	var $label;
	var $fk_parent;
	var $description;
	var $tms;

    

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function EcmDirectory($DB) 
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
    	
		$now=time();
		
		// Clean parameters
        $this->label=sanitize_string($this->label);
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);
		if (! $this->cachenbofdoc) $this->cachenbofdoc=0;
		$this->date_c=$now;
		$this->fk_user_c=$user->id;
        

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."ecm_directories(";
		$sql.= "label,";
		$sql.= "fk_parent,";
		$sql.= "description,";
		$sql.= "cachenbofdoc,";
		$sql.= "date_c,";
		$sql.= "fk_user_c";
        $sql.= ") VALUES (";
		$sql.= " '".$this->label."',";
		$sql.= " '".$this->fk_parent."',";
		$sql.= " '".$this->description."',";
		$sql.= " ".($this->cachenbofdoc).",";
		$sql.= " ".$this->db->idate($this->date_c).",";
		$sql.= " '".$this->fk_user_c."'";
		$sql.= ")";

	   	dolibarr_syslog("Ecm_directories::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ecm_directories");
    
			$result=create_exdir($conf->ecm->dir_output.'/'.$this->label);
			
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::create ".$this->error, LOG_ERR);
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
        
		$this->label=trim($this->label);
		$this->fk_parent=trim($this->fk_parent);
		$this->description=trim($this->description);

        

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories SET";
        
		$sql.= " label='".addslashes($this->label)."',";
		$sql.= " fk_parent='".$this->fk_parent."',";
		$sql.= " description='".addslashes($this->description)."',";
		$sql.= " tms=".$this->db->idate($this->tms)."";

        
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Ecm_directories::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::update ".$this->error, LOG_ERR);
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
        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.label,";
		$sql.= " t.fk_parent,";
		$sql.= " t.description,";
		$sql.= " t.cachenbofdoc,";
		$sql.= " t.fk_user_c,";
		$sql.= " t.fk_user_m,";
		$sql.= " ".$this->db->pdate('t.date_c').",";
		$sql.= " ".$this->db->pdate('t.date_m')."";
        $sql.= " FROM ".MAIN_DB_PREFIX."ecm_directories as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog("Ecm_directories::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                $this->ref   = $obj->rowid;
                
				$this->label = $obj->label;
				$this->fk_parent = $obj->fk_parent;
				$this->description = $obj->description;
				$this->cachenbofdoc = $obj->cachenbofdoc;
				$this->fk_user_m = $obj->fk_user_m;
				$this->fk_user_c = $obj->fk_user_c;
				$this->date_c = $obj->date_c;
				$this->date_m = $obj->date_m;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::fetch ".$this->error, LOG_ERR);
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
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."ecm_directories";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dolibarr_syslog("Ecm_directories::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Ecm_directories::delete ".$this->error, LOG_ERR);
			return -1;
		}
	
		$file = $conf->ecm->dir_output . "/" . $this->label;
		$result=dol_delete_dir($file);
		
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
		
		$this->label='MyDirectory';
		$this->fk_parent='0';
		$this->description='This is a directory';
	}

	
  /**
     \brief      Renvoie nom clicable (avec eventuellement le picto)
     \param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     \param		option			Sur quoi pointe le lien
     \return		string			Chaine avec URL
   */
  function getNomUrl($withpicto=0,$option='')
  {
    global $langs;
		
    $result='';
		
    $lien = '<a href="'.DOL_URL_ROOT.'/ecm/docmine.php?section='.$this->id.'">';
    $lienfin='</a>';
		
    $picto='dir';

    $label=$langs->trans("ShowECMSection").': '.$this->ref;
		
    if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
    if ($withpicto && $withpicto != 2) $result.=' ';
    if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
    return $result;
  }

}
?>
