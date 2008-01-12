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
 *
 * $Id$
 */

/**
        \file       htdocs/lib/menubase.class.php
        \ingroup    core
        \brief      File of class to manage menu entries
		\remarks	Initialy built by build_class_from_table on 2008-01-12 14:19
*/


/**
        \class      Menubase
        \brief      Class to manage menu entries
*/
class Menubase
{
	var $db;							// To store db handler
	var $error;							// To return error code (or message)
	var $errors=array();				// To return several error codes (or messages)
    
    var $id;
    
	var $menu_handler;
	var $type;
	var $mainmenu;
	var $fk_menu;
	var $order;
	var $url;
	var $target;
	var $titre;
	var $langs;
	var $level;
	var $leftmenu;
	var $right;
	var $user;
	var $tms;

    

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Menubase($DB) 
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
		$this->rowid=trim($this->rowid);
		$this->menu_handler=trim($this->menu_handler);
		$this->type=trim($this->type);
		$this->mainmenu=trim($this->mainmenu);
		$this->fk_menu=trim($this->fk_menu);
		$this->order=trim($this->order);
		$this->url=trim($this->url);
		$this->target=trim($this->target);
		$this->titre=trim($this->titre);
		$this->langs=trim($this->langs);
		$this->level=trim($this->level);
		$this->leftmenu=trim($this->leftmenu);
		$this->right=trim($this->right);
		$this->user=trim($this->user);

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(";
		$sql.= "menu_handler,";
		$sql.= "type,";
		$sql.= "mainmenu,";
		$sql.= "fk_menu,";
		$sql.= "order,";
		$sql.= "url,";
		$sql.= "target,";
		$sql.= "titre,";
		$sql.= "langs,";
		$sql.= "level,";
		$sql.= "leftmenu,";
		$sql.= "right,";
		$sql.= "user,";
		$sql.= "tms";
        $sql.= ") VALUES (";
		$sql.= " '".$this->menu_handler."',";
		$sql.= " '".$this->type."',";
		$sql.= " '".$this->mainmenu."',";
		$sql.= " '".$this->fk_menu."',";
		$sql.= " '".$this->order."',";
		$sql.= " '".$this->url."',";
		$sql.= " '".$this->target."',";
		$sql.= " '".$this->titre."',";
		$sql.= " '".$this->langs."',";
		$sql.= " '".$this->level."',";
		$sql.= " '".$this->leftmenu."',";
		$sql.= " '".$this->right."',";
		$sql.= " '".$this->user."',";
		$sql.= " ".$this->db->idate($this->tms)."";
		$sql.= ")";

	   	dolibarr_syslog("Menu::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."menu");
    
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
			if ($result < 0) $this->errors=$interface->errors;
            // Fin appel triggers

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Menu::create ".$this->error, LOG_ERR);
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
		$this->rowid=trim($this->rowid);
		$this->menu_handler=trim($this->menu_handler);
		$this->type=trim($this->type);
		$this->mainmenu=trim($this->mainmenu);
		$this->fk_menu=trim($this->fk_menu);
		$this->order=trim($this->order);
		$this->url=trim($this->url);
		$this->target=trim($this->target);
		$this->titre=trim($this->titre);
		$this->langs=trim($this->langs);
		$this->level=trim($this->level);
		$this->leftmenu=trim($this->leftmenu);
		$this->right=trim($this->right);
		$this->user=trim($this->user);

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
		$sql.= " menu_handler='".addslashes($this->menu_handler)."',";
		$sql.= " type='".$this->type."',";
		$sql.= " mainmenu='".addslashes($this->mainmenu)."',";
		$sql.= " fk_menu='".$this->fk_menu."',";
		$sql.= " order='".$this->order."',";
		$sql.= " url='".addslashes($this->url)."',";
		$sql.= " target='".addslashes($this->target)."',";
		$sql.= " titre='".addslashes($this->titre)."',";
		$sql.= " langs='".addslashes($this->langs)."',";
		$sql.= " level='".$this->level."',";
		$sql.= " leftmenu='".addslashes($this->leftmenu)."',";
		$sql.= " right='".addslashes($this->right)."',";
		$sql.= " user='".$this->user."',";
		$sql.= " tms=".$this->db->idate($this->tms)."";
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Menu::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Menu::update ".$this->error, LOG_ERR);
            return -1;
        }

		if (! $notrigger)
		{
            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
			if ($result < 0) $this->errors=$interface->errors;
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
		$sql.= " t.menu_handler,";
		$sql.= " t.type,";
		$sql.= " t.mainmenu,";
		$sql.= " t.fk_menu,";
		$sql.= " t.order,";
		$sql.= " t.url,";
		$sql.= " t.target,";
		$sql.= " t.titre,";
		$sql.= " t.langs,";
		$sql.= " t.level,";
		$sql.= " t.leftmenu,";
		$sql.= " t.right,";
		$sql.= " t.user,";
		$sql.= " ".$this->db->pdate('t.tms')."";
        $sql.= " FROM ".MAIN_DB_PREFIX."menu as t";
        $sql.= " WHERE t.rowid = ".$id;
    
    	dolibarr_syslog("Menu::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);
    
                $this->id    = $obj->rowid;
                
				$this->menu_handler = $obj->menu_handler;
				$this->type = $obj->type;
				$this->mainmenu = $obj->mainmenu;
				$this->fk_menu = $obj->fk_menu;
				$this->order = $obj->order;
				$this->url = $obj->url;
				$this->target = $obj->target;
				$this->titre = $obj->titre;
				$this->langs = $obj->langs;
				$this->level = $obj->level;
				$this->leftmenu = $obj->leftmenu;
				$this->right = $obj->right;
				$this->user = $obj->user;
				$this->tms = $obj->tms;
            }
            $this->db->free($resql);
            
            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Menu::fetch ".$this->error, LOG_ERR);
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
	
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
		$sql.= " WHERE rowid=".$this->id;
	
	   	dolibarr_syslog("Menu::delete sql=".$sql);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Menu::delete ".$this->error, LOG_ERR);
			return -1;
		}
	
        // Appel des triggers
        include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
        $interface=new Interfaces($this->db);
        $result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		if ($result < 0) $this->errors=$interface->errors;
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
		
		$this->menu_handler='';
		$this->type='';
		$this->mainmenu='';
		$this->fk_menu='';
		$this->order='';
		$this->url='';
		$this->target='';
		$this->titre='';
		$this->langs='';
		$this->level='';
		$this->leftmenu='';
		$this->right='';
		$this->user='';
		$this->tms='';
	}

}
?>
