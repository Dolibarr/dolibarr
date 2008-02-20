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
        \file       htdocs/lib/menubase.class.php
        \ingroup    core
		\version	$Id$
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
	var $module;
	var $type;
	var $mainmenu;
	var $fk_menu;
	var $position;
	var $url;
	var $target;
	var $titre;
	var $langs;
	var $level;
	var $leftmenu;
	var $perms;
	var $user;
	var $tms;

	
    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Menubase($DB,$menu_handler='',$type='')
    {
        $this->db = $DB;
		$this->menu_handler = $menu_handler;
		$this->type = $type;
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
		$this->menu_handler=trim($this->menu_handler);
		$this->module=trim($this->module);
		$this->type=trim($this->type);
		$this->mainmenu=trim($this->mainmenu);
		$this->fk_menu=trim($this->fk_menu);
		$this->position=trim($this->position);
		$this->url=trim($this->url);
		$this->target=trim($this->target);
		$this->titre=trim($this->titre);
		$this->langs=trim($this->langs);
		$this->level=trim($this->level);
		$this->leftmenu=trim($this->leftmenu);
		$this->perms=trim($this->perms);
		$this->user=trim($this->user);

		// Check parameters
		// Put here code to add control on parameters values
		
        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(";
		$sql.= "menu_handler,";
		$sql.= "module,";
		$sql.= "type,";
		$sql.= "mainmenu,";
		$sql.= "fk_menu,";
		$sql.= "position,";
		$sql.= "url,";
		$sql.= "target,";
		$sql.= "titre,";
		$sql.= "langs,";
		$sql.= "level,";
		$sql.= "leftmenu,";
		$sql.= "perms,";
		$sql.= "user";
        $sql.= ") VALUES (";
		$sql.= " '".$this->menu_handler."',";
		$sql.= " '".$this->module."',";
		$sql.= " '".$this->type."',";
		$sql.= " '".$this->mainmenu."',";
		$sql.= " '".$this->fk_menu."',";
		$sql.= " '".$this->position."',";
		$sql.= " '".$this->url."',";
		$sql.= " '".$this->target."',";
		$sql.= " '".$this->titre."',";
		$sql.= " '".$this->langs."',";
		$sql.= " '".$this->level."',";
		$sql.= " '".$this->leftmenu."',";
		$sql.= " '".$this->perms."',";
		$sql.= " '".$this->user."'";
		$sql.= ")";

	   	dolibarr_syslog("Menu::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."menu");
    
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
		$this->module=trim($this->module);
		$this->type=trim($this->type);
		$this->mainmenu=trim($this->mainmenu);
		$this->fk_menu=trim($this->fk_menu);
		$this->position=trim($this->position);
		$this->url=trim($this->url);
		$this->target=trim($this->target);
		$this->titre=trim($this->titre);
		$this->langs=trim($this->langs);
		$this->level=trim($this->level);
		$this->leftmenu=trim($this->leftmenu);
		$this->perms=trim($this->perms);
		$this->user=trim($this->user);

		// Check parameters
		// Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
		$sql.= " menu_handler='".addslashes($this->menu_handler)."',";
		$sql.= " module='".addslashes($this->module)."',";
		$sql.= " type='".$this->type."',";
		$sql.= " mainmenu='".addslashes($this->mainmenu)."',";
		$sql.= " fk_menu='".$this->fk_menu."',";
		$sql.= " position='".$this->position."',";
		$sql.= " url='".addslashes($this->url)."',";
		$sql.= " target='".addslashes($this->target)."',";
		$sql.= " titre='".addslashes($this->titre)."',";
		$sql.= " langs='".addslashes($this->langs)."',";
		$sql.= " level='".$this->level."',";
		$sql.= " leftmenu='".addslashes($this->leftmenu)."',";
		$sql.= " perms='".addslashes($this->perms)."',";
		$sql.= " user='".$this->user."'";
        $sql.= " WHERE rowid=".$this->id;

        dolibarr_syslog("Menu::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dolibarr_syslog("Menu::update ".$this->error, LOG_ERR);
            return -1;
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
		$sql.= " t.module,";
		$sql.= " t.type,";
		$sql.= " t.mainmenu,";
		$sql.= " t.fk_menu,";
		$sql.= " t.position,";
		$sql.= " t.url,";
		$sql.= " t.target,";
		$sql.= " t.titre,";
		$sql.= " t.langs,";
		$sql.= " t.level,";
		$sql.= " t.leftmenu,";
		$sql.= " t.perms,";
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
				$this->module = $obj->module;
				$this->type = $obj->type;
				$this->mainmenu = $obj->mainmenu;
				$this->fk_menu = $obj->fk_menu;
				$this->position = $obj->position;
				$this->url = $obj->url;
				$this->target = $obj->target;
				$this->titre = $obj->titre;
				$this->langs = $obj->langs;
				$this->level = $obj->level;
				$this->leftmenu = $obj->leftmenu;
				$this->perms = $obj->perms;
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
	
		return 1;
	}

  
	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		
		$this->menu_handler='all';
		$this->module='specimen';
		$this->type='top';
		$this->mainmenu='';
		$this->fk_menu='0';
		$this->position='';
		$this->url='http://dummy';
		$this->target='';
		$this->titre='Specimen menu';
		$this->langs='';
		$this->level='';
		$this->leftmenu='';
		$this->perms='';
		$this->user='';
		$this->tms='';
	}

	
	function menuCharger($mainmenu, $newmenu, $type_user, $leftmenu) 
	{
		global $langs, $user, $conf;

		$this->mainmenu = $mainmenu;
		$this->newmenu = $newmenu;
		$this->leftmenu = $leftmenu;

		$sql = "SELECT m.rowid, m.titre, m.type";
		$sql.= " FROM " . MAIN_DB_PREFIX . "menu as m";
		$sql.= " WHERE m.mainmenu = '".$this->mainmenu."'";
		$sql.= " AND m.menu_handler= '".$this->menu_handler."'";
		$result = $this->db->query($sql);
		$menuTop = $this->db->fetch_object($result);

		$data[] = array ($menutop->rowid,-1,$this->mainmenu);

		$sql = "SELECT m.rowid, m.fk_menu, m.url, m.titre, m.langs, m.perms, m.target, m.mainmenu, m.leftmenu";
		$sql.= " FROM " . MAIN_DB_PREFIX . "menu as m";
		$sql.= " WHERE m.menu_handler= '".$this->menu_handler."'";
		if($type_user == 0) $sql.= " AND m.user <> 1";
		else $sql.= " AND m.user > 0";
		$sql.= " ORDER BY m.position, m.rowid";

		$res = $this->db->query($sql);
		if ($res)
		{
			$num = $this->db->num_rows($res);

			$i = 1;
			while ($menu = $this->db->fetch_array($res))
			{
				if (! empty($menu['langs'])) $langs->load($menu['langs']);
				$titre = $langs->trans($menu['titre']);
				$rights = $this->verifRights($menu['right']);
				$data[] = array (
					$menu['rowid'],
					$menu['fk_menu'],
					$menu['url'],
					$titre,
					$rights,
					$menu['target'],
					$menu['leftmenu']
				);
				$i++;

			}

		}
		else
		{
			dolibarr_print_error($this->db);
		}

		$this->recur($data, $menuTop->rowid, 1);

		return $this->newmenu;

	}

	function recur($tab, $pere, $rang) {
		$leftmenu = $this->leftmenu;
		//ballayage du tableau
		for ($x = 0; $x < count($tab); $x++) {

			//si un element a pour pere : $pere
			if ($tab[$x][1] == $pere) {

				//on affiche le menu

				if ($this->verifConstraint($tab[$x][0], $tab[$x][6], $tab[$x][7]) != 0) {

					if ($tab[$x][6]) {

						$leftmenuConstraint = false;
						$str = "if(" . $tab[$x][6] . ") \$leftmenuConstraint = true;";

						eval ($str);
						if ($leftmenuConstraint == true) {
							$this->newmenu->add_submenu(DOL_URL_ROOT . $tab[$x][2], $tab[$x][3], $rang -1, $tab[$x][4], $tab[$x][5]);
							$this->recur($tab, $tab[$x][0], $rang +1);
						}
					} else {
						$this->newmenu->add_submenu(DOL_URL_ROOT . $tab[$x][2], $tab[$x][3], $rang -1, $tab[$x][4], $tab[$x][5]);
						$this->recur($tab, $tab[$x][0], $rang +1);
					}

				}
			}
		}
	}

	function verifConstraint($rowid, $mainmenu = "", $leftmenu = "") 
	{
		global $user, $conf, $user;

		include_once(DOL_DOCUMENT_ROOT.'/lib/admin.lib.php');	// Because later some eval try to run dynamic call to dolibarr_get_const
		$constraint = true;

		$sql = "SELECT c.rowid, c.action, mc.user";
		$sql.= " FROM " . MAIN_DB_PREFIX . "menu_constraint as c, " . MAIN_DB_PREFIX . "menu_const as mc";
		$sql.= " WHERE mc.fk_constraint = c.rowid AND (mc.user = 0 OR mc.user = 2) AND mc.fk_menu = '" . $rowid . "'";

		$result = $this->db->query($sql);
		if ($result) 
		{
			//echo $sql;
			$num = $this->db->num_rows($result);
			$i = 0;
			while (($i < $num) && $constraint == true) 
			{
				$obj = $this->db->fetch_object($result);
				$strconstraint = "if(!(" . $obj->action . ")) { \$constraint = false; }";

				eval ($strconstraint);
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}

		return $constraint;
	}

	function verifRights($strRights) {

		global $user,$conf,$user;

		if ($strRights != "") {
			$rights = true;

			$tab_rights = explode(" || ", $strRights);
			$i = 0;
			while (($i < count($tab_rights)) && ($rights == true)) {
				$str = "if(!(" . $strRights . ")) { \$rights = false;}";
				eval ($str);
				$i++;
			}
		} else
			$rights = true;

		return $rights;
	}

	function listeMainmenu()
	{
		$sql = "SELECT DISTINCT m.mainmenu";
		$sql.= " FROM " . MAIN_DB_PREFIX . "menu as m";
		$sql.= " WHERE m.menu_handler= '".$this->menu_handler."'";

		$res = $this->db->query($sql);
		if ($res) {
			$i = 0;
			while ($menu = $this->db->fetch_array($res)) {
				$overwritemenufor[$i] = $menu['mainmenu'];
				$i++;
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}
		
		return $overwritemenufor;
	}
	
	/**
	*		brief		type_user		0=Internal,1=External,2=All
	*/
	function menutopCharger($type_user, $mainmenu, $menu_handler)
	{
		global $langs, $user, $conf;
		
		$sql = "SELECT m.rowid, m.mainmenu, m.titre, m.url, m.langs, m.perms";
		$sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
		$sql.= " WHERE m.type = 'top'"; 
		$sql.= " AND m.menu_handler in('".$menu_handler."','all')";
		if ($type_user == 0) $sql.= " AND m.user in (0,2)";
		if ($type_user == 1) $sql.= " AND m.user in (1,2)";
		$sql.= " ORDER BY m.position";

		//print "x".$sql;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$numa = $this->db->num_rows($resql);

			$a = 0;
			$b = 0;
			while ($a < $numa)
			{
				// Init tabMenu array
				$objm = $this->db->fetch_object($resql);
				
				if ($this->verifConstraint($objm->rowid))
		        {
					// Define class
		            $class="";
		            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == $objm->mainmenu)
		            {
		                $class='id="sel"';
		            }
		            $chaine="";

		            // Define $right
		        	$right = true;
		        	if ($objm->perms)
		        	{
		        		$str = "if(!(".$objm->perms.")) \$right = false;";
		        		eval($str);
		        	}
		        	
					// Define $chaine
					$title=$objm->titre;
					if (! eregi('\(dotnoloadlang\)$',$title))
					{
						if (! empty($objm->langs)) $langs->load($objm->langs);
					}
					else
					{
						$title=eregi_replace('\(dotnoloadlang\)$','',$title);
					}

 		        	if (eregi("/",$title))
		        	{
		        		$tab_titre = explode("/",$title);
		        		$chaine = $langs->trans($tab_titre[0])."/".$langs->trans($tab_titre[1]);
		        	}
		        	else
		        	{
		        		$chaine = $langs->trans($title);
		        	} 
						
		        	$tabMenu[$b]['rowid'] = $objm->rowid;
					$tabMenu[$b]['mainmenu'] = $objm->mainmenu;
					$tabMenu[$b]['titre'] = $chaine;	// Title
		        	$tabMenu[$b]['url'] = $objm->url;
		        	$tabMenu[$b]['atarget'] = $this->atarget;
		        	$tabMenu[$b]['class'] = $class;
		        	$tabMenu[$b]['right'] = $right;
		        	
					$b++;
					
		        }	
		        			
				$a++;	
			}
		}
		else
		{
			dolibarr_print_error($this->db);
		}
		
		return $tabMenu;
		
	}

}
?>
