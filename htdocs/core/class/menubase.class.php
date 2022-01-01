<?php
/* Copyright (C) 2007-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2018-2019  Frédéric France     <frederic.france@netlogic.fr>
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
 *  \file       htdocs/core/class/menubase.class.php
 *  \ingroup    core
 *  \brief      File of class to manage dynamic menu entries
 */


/**
 *  Class to manage menu entries
 */
class Menubase
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string Error code (or message)
     */
    public $error;

    /**
     * @var string[] Error codes (or messages)
     */
    public $errors = array();

    /**
     * @var int ID
     */
    public $id;

    /**
     * @var string Menu handler
     */
    public $menu_handler;

    /**
     * @var string Module name if record is added by a module
     */
    public $module;

    /**
     * @var string Menu top or left
     */
    public $type;

    /**
     * @var string Name family/module for top menu (home, companies, ...)
     */
    public $mainmenu;

    /**
     * @var int 0 or Id of mother menu line, or -1 if we use fk_mainmenu and fk_leftmenu
     */
    public $fk_menu;

    /**
     * @var string fk_mainmenu
     */
    public $fk_mainmenu;

    /**
     * @var string fk_leftmenu
     */
    public $fk_leftmenu;

    /**
     * @var int Sort order of entry
     */
    public $position;

    /**
     * @var string Relative (or absolute) url to go
     */
    public $url;

    /**
     * @var string Target of Url link
     */
    public $target;

    /**
     * @var string Key for menu translation
     * @deprecated
     * @see $title
     */
    public $titre;

    /**
     * @var string Key for menu translation
     */
    public $title;

    /**
     * @var string Lang file to load for translation
     */
    public $langs;

    /**
     * @var string Not used
     * @deprecated
     */
    public $level;

    /**
     * @var string Name family/module for left menu (setup, info, ...)
     */
    public $leftmenu;

    /**
     * @var string Condition to show enabled or disabled
     */
    public $perms;

    /**
     * @var string Condition to show or hide
     */
    public $enabled;

    /**
     * @var int 0 if menu for all users, 1 for external only, 2 for internal only
     */
    public $user;

    /**
     * @var int timestamp
     */
    public $tms;


    /**
	 *  Constructor
	 *
	 *  @param		DoliDB		$db 		    Database handler
     *  @param     	string		$menu_handler	Menu handler
     */
    public function __construct($db, $menu_handler = '')
    {
        $this->db = $db;
        $this->menu_handler = $menu_handler;
        return 1;
    }


    /**
     *  Create menu entry into database
     *
     *  @param      User	$user       User that create
     *  @return     int      			<0 if KO, Id of record if OK
     */
    public function create($user = null)
    {
        global $conf, $langs;

        // Clean parameters
        $this->menu_handler = trim($this->menu_handler);
        $this->module = trim($this->module);
        $this->type = trim($this->type);
        $this->mainmenu = trim($this->mainmenu);
        $this->leftmenu = trim($this->leftmenu);
        $this->fk_menu = (int) $this->fk_menu; // If -1, fk_mainmenu and fk_leftmenu must be defined
        $this->fk_mainmenu = trim($this->fk_mainmenu);
        $this->fk_leftmenu = trim($this->fk_leftmenu);
        $this->position = (int) $this->position;
        $this->url = trim($this->url);
        $this->target = trim($this->target);
        $this->titre = trim($this->titre);
        $this->langs = trim($this->langs);
        $this->perms = trim($this->perms);
        $this->enabled = trim($this->enabled);
        $this->user = (int) $this->user;
        if (empty($this->position)) $this->position = 0;
        if (!$this->level) $this->level = 0;

        // Check parameters
        if (empty($this->menu_handler)) return -1;

        // For PGSQL, we must first found the max rowid and use it as rowid in insert because postgresql
        // may use an already used value because its internal cursor does not increase when we do
        // an insert with a forced id.
        if (in_array($this->db->type, array('pgsql')))
        {
            $sql = "SELECT MAX(rowid) as maxrowid FROM ".MAIN_DB_PREFIX."menu";
            $resqlrowid = $this->db->query($sql);
            if ($resqlrowid) {
                $obj = $this->db->fetch_object($resqlrowid);
                $maxrowid = $obj->maxrowid;

                // Max rowid can be empty if there is no record yet
                if (empty($maxrowid)) $maxrowid = 1;

                $sql = "SELECT setval('".MAIN_DB_PREFIX."menu_rowid_seq', ".($maxrowid).")";
                //print $sql; exit;
                $resqlrowidset = $this->db->query($sql);
                if (!$resqlrowidset) dol_print_error($this->db);
            }
            else dol_print_error($this->db);
        }

        // Check that entry does not exists yet on key menu_handler-fk_menu-position-url-entity, to avoid errors with postgresql
        $sql = "SELECT count(*)";
        $sql .= " FROM ".MAIN_DB_PREFIX."menu";
        $sql .= " WHERE menu_handler = '".$this->db->escape($this->menu_handler)."'";
        $sql .= " AND fk_menu = ".((int) $this->fk_menu);
        $sql .= " AND position = ".((int) $this->position);
        $sql .= " AND url = '".$this->db->escape($this->url)."'";
        $sql .= " AND entity = ".$conf->entity;

        $result = $this->db->query($sql);
        if ($result)
        {
        	$row = $this->db->fetch_row($result);

        	if ($row[0] == 0)   // If not found
        	{
		        // Insert request
		        $sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(";
		        $sql .= "menu_handler,";
		        $sql .= "entity,";
		        $sql .= "module,";
		        $sql .= "type,";
		        $sql .= "mainmenu,";
		        $sql .= "leftmenu,";
		        $sql .= "fk_menu,";
		        $sql .= "fk_mainmenu,";
		        $sql .= "fk_leftmenu,";
		        $sql .= "position,";
		        $sql .= "url,";
		        $sql .= "target,";
		        $sql .= "titre,";
		        $sql .= "langs,";
		        $sql .= "perms,";
		        $sql .= "enabled,";
		        $sql .= "usertype";
		        $sql .= ") VALUES (";
		        $sql .= " '".$this->db->escape($this->menu_handler)."',";
		        $sql .= " '".$this->db->escape($conf->entity)."',";
		        $sql .= " '".$this->db->escape($this->module)."',";
		        $sql .= " '".$this->db->escape($this->type)."',";
		        $sql .= " ".($this->mainmenu ? "'".$this->db->escape($this->mainmenu)."'" : "''").","; // Can't be null
		        $sql .= " ".($this->leftmenu ? "'".$this->db->escape($this->leftmenu)."'" : "null").",";
		        $sql .= " ".((int) $this->fk_menu).",";
		        $sql .= " ".($this->fk_mainmenu ? "'".$this->db->escape($this->fk_mainmenu)."'" : "null").",";
		        $sql .= " ".($this->fk_leftmenu ? "'".$this->db->escape($this->fk_leftmenu)."'" : "null").",";
		        $sql .= " ".((int) $this->position).",";
		        $sql .= " '".$this->db->escape($this->url)."',";
		        $sql .= " '".$this->db->escape($this->target)."',";
		        $sql .= " '".$this->db->escape($this->titre)."',";
		        $sql .= " '".$this->db->escape($this->langs)."',";
		        $sql .= " '".$this->db->escape($this->perms)."',";
		        $sql .= " '".$this->db->escape($this->enabled)."',";
		        $sql .= " '".$this->db->escape($this->user)."'";
		        $sql .= ")";

		        dol_syslog(get_class($this)."::create", LOG_DEBUG);
		        $resql = $this->db->query($sql);
		        if ($resql)
		        {
		            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."menu");
		            dol_syslog(get_class($this)."::create record added has rowid=".$this->id, LOG_DEBUG);

		            return $this->id;
		        }
		        else
		        {
		            $this->error = "Error ".$this->db->lasterror();
		            return -1;
		        }
        	}
        	else
        	{
        		dol_syslog(get_class($this)."::create menu entry already exists", LOG_WARNING);
        		$this->error = 'Error Menu entry already exists';
        		return 0;
        	}
        }
        else
        {
        	return -1;
        }
    }

    /**
     *  Update menu entry into database.
     *
     *  @param	User	$user        	User that modify
     *  @param  int		$notrigger	    0=no, 1=yes (no update trigger)
     *  @return int 		        	<0 if KO, >0 if OK
     */
    public function update($user = null, $notrigger = 0)
    {
        //global $conf, $langs;

        // Clean parameters
        $this->rowid = trim($this->rowid);
        $this->menu_handler = trim($this->menu_handler);
        $this->module = trim($this->module);
        $this->type = trim($this->type);
        $this->mainmenu = trim($this->mainmenu);
        $this->leftmenu = trim($this->leftmenu);
        $this->fk_menu = (int) $this->fk_menu;
        $this->fk_mainmenu = trim($this->fk_mainmenu);
        $this->fk_leftmenu = trim($this->fk_leftmenu);
        $this->position = (int) $this->position;
        $this->url = trim($this->url);
        $this->target = trim($this->target);
        $this->titre = trim($this->titre);
        $this->langs = trim($this->langs);
        $this->perms = trim($this->perms);
        $this->enabled = trim($this->enabled);
        $this->user = (int) $this->user;

        // Check parameters
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
        $sql .= " menu_handler='".$this->db->escape($this->menu_handler)."',";
        $sql .= " module='".$this->db->escape($this->module)."',";
        $sql .= " type='".$this->db->escape($this->type)."',";
        $sql .= " mainmenu='".$this->db->escape($this->mainmenu)."',";
        $sql .= " leftmenu='".$this->db->escape($this->leftmenu)."',";
        $sql .= " fk_menu=".$this->fk_menu.",";
        $sql .= " fk_mainmenu=".($this->fk_mainmenu ? "'".$this->db->escape($this->fk_mainmenu)."'" : "null").",";
        $sql .= " fk_leftmenu=".($this->fk_leftmenu ? "'".$this->db->escape($this->fk_leftmenu)."'" : "null").",";
        $sql .= " position=".($this->position > 0 ? $this->position : 0).",";
        $sql .= " url='".$this->db->escape($this->url)."',";
        $sql .= " target='".$this->db->escape($this->target)."',";
        $sql .= " titre='".$this->db->escape($this->titre)."',";
        $sql .= " langs='".$this->db->escape($this->langs)."',";
        $sql .= " perms='".$this->db->escape($this->perms)."',";
        $sql .= " enabled='".$this->db->escape($this->enabled)."',";
        $sql .= " usertype='".$this->db->escape($this->user)."'";
        $sql .= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }

        return 1;
    }


    /**
     *   Load object in memory from database
     *
     *   @param		int		$id         Id object
     *   @param		User    $user       User that load
     *   @return	int         		<0 if KO, >0 if OK
     */
    public function fetch($id, $user = null)
    {
        //global $langs;

        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.menu_handler,";
        $sql .= " t.entity,";
        $sql .= " t.module,";
        $sql .= " t.type,";
        $sql .= " t.mainmenu,";
        $sql .= " t.leftmenu,";
        $sql .= " t.fk_menu,";
        $sql .= " t.fk_mainmenu,";
        $sql .= " t.fk_leftmenu,";
        $sql .= " t.position,";
        $sql .= " t.url,";
        $sql .= " t.target,";
        $sql .= " t.titre,";
        $sql .= " t.langs,";
        $sql .= " t.perms,";
        $sql .= " t.enabled,";
        $sql .= " t.usertype as user,";
        $sql .= " t.tms";
        $sql .= " FROM ".MAIN_DB_PREFIX."menu as t";
        $sql .= " WHERE t.rowid = ".$id;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->menu_handler = $obj->menu_handler;
                $this->entity = $obj->entity;
                $this->module = $obj->module;
                $this->type = $obj->type;
                $this->mainmenu = $obj->mainmenu;
                $this->leftmenu = $obj->leftmenu;
                $this->fk_menu = $obj->fk_menu;
                $this->fk_mainmenu = $obj->fk_mainmenu;
                $this->fk_leftmenu = $obj->fk_leftmenu;
                $this->position = $obj->position;
                $this->url = $obj->url;
                $this->target = $obj->target;
                $this->titre = $obj->titre;
                $this->langs = $obj->langs;
                $this->perms = $obj->perms;
                $this->enabled = str_replace("\"", "'", $obj->enabled);
                $this->user = $obj->user;
                $this->tms = $this->db->jdate($obj->tms);
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Delete object in database
     *
     *	@param	User	$user       User that delete
     *	@return	int					<0 if KO, >0 if OK
     */
    public function delete($user)
    {
        //global $conf, $langs;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
        $sql .= " WHERE rowid=".$this->id;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (!$resql)
        {
            $this->error = "Error ".$this->db->lasterror();
            return -1;
        }

        return 1;
    }


    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    public function initAsSpecimen()
    {
        $this->id = 0;

        $this->menu_handler = 'all';
        $this->module = 'specimen';
        $this->type = 'top';
        $this->mainmenu = '';
        $this->fk_menu = '0';
        $this->position = '';
        $this->url = 'http://dummy';
        $this->target = '';
        $this->titre = 'Specimen menu'; // deprecated
        $this->title = 'Specimen menu';
        $this->langs = '';
        $this->level = '';
        $this->leftmenu = '';
        $this->perms = '';
        $this->enabled = '';
        $this->user = '';
        $this->tms = '';
    }


    /**
     *	Load tabMenu array with top menu entries found into database.
     *
     * 	@param	string	$mymainmenu		Value for mainmenu to filter menu to load (always '')
     * 	@param	string	$myleftmenu		Value for leftmenu to filter menu to load (always '')
     * 	@param	int		$type_user		0=Menu for backoffice, 1=Menu for front office
     * 	@param	string	$menu_handler	Filter on name of menu_handler used (auguria, eldy...)
     * 	@param  array	$tabMenu       If array with menu entries already loaded, we put this array here (in most cases, it's empty)
     * 	@return	array					Return array with menu entries for top menu
     */
    public function menuTopCharger($mymainmenu, $myleftmenu, $type_user, $menu_handler, &$tabMenu)
    {
        global $langs, $user, $conf; // To export to dol_eval function
        global $mainmenu, $leftmenu; // To export to dol_eval function

        $mainmenu = $mymainmenu; // To export to dol_eval function
        $leftmenu = $myleftmenu; // To export to dol_eval function

        $newTabMenu = array();
        foreach ($tabMenu as $val)
        {
            if ($val['type'] == 'top') $newTabMenu[] = $val;
        }

        return $newTabMenu;
    }

    /**
     * 	Load entries found from database (and stored into $tabMenu) in $this->newmenu array.
     *  Warning: Entries in $tabMenu must have child after parent
     *
     * 	@param	Menu	$newmenu        Menu array to complete (in most cases, it's empty, may be already initialized with some menu manager like eldy)
     * 	@param	string	$mymainmenu		Value for mainmenu to filter menu to load (often $_SESSION["mainmenu"])
     * 	@param	string	$myleftmenu		Value for leftmenu to filter menu to load (always '')
     * 	@param	int		$type_user		0=Menu for backoffice, 1=Menu for front office
     * 	@param	string	$menu_handler	Filter on name of menu_handler used (auguria, eldy...)
     * 	@param  array	$tabMenu       Array with menu entries already loaded
     * 	@return Menu    		       	Menu array for particular mainmenu value or full tabArray
     */
    public function menuLeftCharger($newmenu, $mymainmenu, $myleftmenu, $type_user, $menu_handler, &$tabMenu)
    {
        global $langs, $user, $conf; // To export to dol_eval function
        global $mainmenu, $leftmenu; // To export to dol_eval function

        $mainmenu = $mymainmenu; // To export to dol_eval function
        $leftmenu = $myleftmenu; // To export to dol_eval function

        // Detect what is top mainmenu id
        $menutopid = '';
        foreach ($tabMenu as $key => $val)
        {
            // Define menutopid of mainmenu
            if (empty($menutopid) && $val['type'] == 'top' && $val['mainmenu'] == $mainmenu)
            {
                $menutopid = $val['rowid'];
                break;
            }
        }

        // We initialize newmenu with first already found menu entries
        $this->newmenu = $newmenu;

        // Now complete $this->newmenu->list to add entries found into $tabMenu that are childs of mainmenu=$menutopid, using the fk_menu link that is int (old method)
        $this->recur($tabMenu, $menutopid, 1);

        // Now complete $this->newmenu->list when fk_menu value is -1 (left menu added by modules with no top menu)
        foreach ($tabMenu as $key => $val)
        {
        	//var_dump($tabMenu);
        	if ($val['fk_menu'] == -1 && $val['fk_mainmenu'] == $mainmenu)    // We found a menu entry not linked to parent with good mainmenu
        	{
        		//print 'Try to add menu (current is mainmenu='.$mainmenu.' leftmenu='.$leftmenu.') for '.join(',',$val).' fk_mainmenu='.$val['fk_mainmenu'].' fk_leftmenu='.$val['fk_leftmenu'].'<br>';
        		//var_dump($this->newmenu->liste);exit;

        		if (empty($val['fk_leftmenu']))
        		{
        			$this->newmenu->add($val['url'], $val['titre'], 0, $val['perms'], $val['target'], $val['mainmenu'], $val['leftmenu'], $val['position']);
        			//var_dump($this->newmenu->liste);
        		}
        		else
        		{
        			// Search first menu with this couple (mainmenu,leftmenu)=(fk_mainmenu,fk_leftmenu)
        			$searchlastsub = 0; $lastid = 0; $nextid = 0; $found = 0;
        			foreach ($this->newmenu->liste as $keyparent => $valparent)
        			{
        				//var_dump($valparent);
        				if ($searchlastsub)    // If we started to search for last submenu
        				{
        					if ($valparent['level'] >= $searchlastsub) $lastid = $keyparent;
        					if ($valparent['level'] < $searchlastsub)
        					{
        						$nextid = $keyparent;
        						break;
        					}
        				}
        				if ($valparent['mainmenu'] == $val['fk_mainmenu'] && $valparent['leftmenu'] == $val['fk_leftmenu'])
        				{
        					//print "We found parent: keyparent='.$keyparent.' - level=".$valparent['level'].' - '.join(',',$valparent).'<br>';
        					// Now we look to find last subelement of this parent (we add at end)
        					$searchlastsub = ($valparent['level'] + 1);
        					$lastid = $keyparent;
        					$found = 1;
        				}
        			}
        			//print 'We must insert menu entry between entry '.$lastid.' and '.$nextid.'<br>';
        			if ($found) $this->newmenu->insert($lastid, $val['url'], $val['titre'], $searchlastsub, $val['perms'], $val['target'], $val['mainmenu'], $val['leftmenu'], $val['position']);
        			else {
        			    dol_syslog("Error. Modules ".$val['module']." has defined a menu entry with a parent='fk_mainmenu=".$val['fk_leftmenu'].",fk_leftmenu=".$val['fk_leftmenu']."' and position=".$val['position'].'. The parent was not found. May be you forget it into your definition of menu, or may be the parent has a "position" that is after the child (fix field "position" of parent or child in this case).', LOG_WARNING);
        			    //print "Parent menu not found !!<br>";
        			}
        		}
        	}
        }

        return $this->newmenu;
    }


    /**
     *  Load entries found in database into variable $tabMenu. Note that only "database menu entries" are loaded here, hardcoded will not be present into output.
     *
     *  @param	string	$mymainmenu     Value for mainmenu that defined mainmenu
     *  @param	string	$myleftmenu     Value for left that defined leftmenu
     *  @param  int		$type_user      Looks for menu entry for 0=Internal users, 1=External users
     *  @param  string	$menu_handler   Name of menu_handler used ('auguria', 'eldy'...)
     *  @param  array	$tabMenu        Array to store new entries found (in most cases, it's empty, but may be alreay filled)
     *  @return int     		        >0 if OK, <0 if KO
     */
    public function menuLoad($mymainmenu, $myleftmenu, $type_user, $menu_handler, &$tabMenu)
    {
        global $langs, $user, $conf; // To export to dol_eval function
        global $mainmenu, $leftmenu; // To export to dol_eval function

        $mainmenu = $mymainmenu; // To export to dol_eval function
        $leftmenu = $myleftmenu; // To export to dol_eval function

        $sql = "SELECT m.rowid, m.type, m.module, m.fk_menu, m.fk_mainmenu, m.fk_leftmenu, m.url, m.titre, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu, m.position";
        $sql .= " FROM ".MAIN_DB_PREFIX."menu as m";
        $sql .= " WHERE m.entity IN (0,".$conf->entity.")";
        $sql .= " AND m.menu_handler IN ('".$menu_handler."','all')";
        if ($type_user == 0) $sql .= " AND m.usertype IN (0,2)";
        if ($type_user == 1) $sql .= " AND m.usertype IN (1,2)";
        $sql .= " ORDER BY m.position, m.rowid";
        //print $sql;

        //dol_syslog(get_class($this)."::menuLoad mymainmenu=".$mymainmenu." myleftmenu=".$myleftmenu." type_user=".$type_user." menu_handler=".$menu_handler." tabMenu size=".count($tabMenu)."", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $numa = $this->db->num_rows($resql);

            $a = 0;
            $b = 0;
            while ($a < $numa)
            {
                //$objm = $this->db->fetch_object($resql);
                $menu = $this->db->fetch_array($resql);

                // Define $right
                $perms = true;
                if (isset($menu['perms']))
                {
                	$tmpcond = $menu['perms'];
                	if ($leftmenu == 'all') $tmpcond = preg_replace('/\$leftmenu\s*==\s*["\'a-zA-Z_]+/', '1==1', $tmpcond); // Force part of condition to true
                	$perms = verifCond($tmpcond);
                    //print "verifCond rowid=".$menu['rowid']." ".$tmpcond.":".$perms."<br>\n";
                }

                // Define $enabled
                $enabled = true;
                if (isset($menu['enabled']))
                {
                	$tmpcond = $menu['enabled'];
                	if ($leftmenu == 'all') $tmpcond = preg_replace('/\$leftmenu\s*==\s*["\'a-zA-Z_]+/', '1==1', $tmpcond); // Force part of condition to true
                    $enabled = verifCond($tmpcond);
                }

                // Define $title
                if ($enabled)
                {
                	$title = $langs->trans($menu['titre']); // If $menu['titre'] start with $, a dol_eval is done.
                	//var_dump($title.'-'.$menu['titre']);
                    if ($title == $menu['titre'])   // Translation not found
                    {
                        if (!empty($menu['langs']))    // If there is a dedicated translation file
                        {
                        	//print 'Load file '.$menu['langs'].'<br>';
                            $langs->load($menu['langs']);
                        }

                        $substitarray = array('__LOGIN__' => $user->login, '__USER_ID__' => $user->id, '__USER_SUPERVISOR_ID__' => $user->fk_user);
                        $menu['titre'] = make_substitutions($menu['titre'], $substitarray);

                        if (preg_match("/\//", $menu['titre'])) // To manage translation when title is string1/string2
                        {
                            $tab_titre = explode("/", $menu['titre']);
                            $title = $langs->trans($tab_titre[0])."/".$langs->trans($tab_titre[1]);
                        }
                        elseif (preg_match('/\|\|/', $menu['titre']))
                        {
                            // To manage different translation (Title||AltTitle@ConditionForAltTitle)
                        	$tab_title = explode("||", $menu['titre']);
                        	$alt_title = explode("@", $tab_title[1]);
                        	$title_enabled = verifCond($alt_title[1]);
                        	$title = ($title_enabled ? $langs->trans($alt_title[0]) : $langs->trans($tab_title[0]));
                        }
                        else
                        {
                            $title = $langs->trans($menu['titre']);
                        }
                    }
                    //$tmp4=microtime(true);
                    //print '>>> 3 '.($tmp4 - $tmp3).'<br>';

                    // We complete tabMenu
                    $tabMenu[$b]['rowid']       = $menu['rowid'];
                    $tabMenu[$b]['module']      = $menu['module'];
                    $tabMenu[$b]['fk_menu']     = $menu['fk_menu'];
                    $tabMenu[$b]['url']         = $menu['url'];
                    if (!preg_match("/^(http:\/\/|https:\/\/)/i", $tabMenu[$b]['url']))
                    {
                        if (preg_match('/\?/', $tabMenu[$b]['url'])) $tabMenu[$b]['url'] .= '&amp;idmenu='.$menu['rowid'];
                        else $tabMenu[$b]['url'] .= '?idmenu='.$menu['rowid'];
                    }
                    $tabMenu[$b]['titre']       = $title;
                    $tabMenu[$b]['target']      = $menu['target'];
                    $tabMenu[$b]['mainmenu']    = $menu['mainmenu'];
                    $tabMenu[$b]['leftmenu']    = $menu['leftmenu'];
                    $tabMenu[$b]['perms']       = $perms;
                    $tabMenu[$b]['langs']       = $menu['langs'];	// Note that this should not be used, lang file should be already loaded.
                    $tabMenu[$b]['enabled']     = $enabled;
                    $tabMenu[$b]['type']        = $menu['type'];
                    $tabMenu[$b]['fk_mainmenu'] = $menu['fk_mainmenu'];
                    $tabMenu[$b]['fk_leftmenu'] = $menu['fk_leftmenu'];
                    $tabMenu[$b]['position']    = (int) $menu['position'];

                    $b++;
                }

                $a++;
            }
            $this->db->free($resql);

            // Currently $tabMenu is sorted on position.
            // If a child have a position lower that its parent, we can make a loop to fix this here, but we prefer to show a warning
            // into the leftMenuCharger later to avoid useless operations.

            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Complete this->newmenu with menu entry found in $tab
     *
     *  @param  array	$tab			Tab array with all menu entries
     *  @param  int		$pere			Id of parent
     *  @param  int		$level			Level
     *  @return	void
     */
    private function recur($tab, $pere, $level)
    {
        // Loop on tab array
        $num = count($tab);
        for ($x = 0; $x < $num; $x++)
        {
            //si un element a pour pere : $pere
            if ((($tab[$x]['fk_menu'] >= 0 && $tab[$x]['fk_menu'] == $pere)) && $tab[$x]['enabled'])
            {
                $this->newmenu->add($tab[$x]['url'], $tab[$x]['titre'], ($level - 1), $tab[$x]['perms'], $tab[$x]['target'], $tab[$x]['mainmenu'], $tab[$x]['leftmenu']);
                $this->recur($tab, $tab[$x]['rowid'], ($level + 1));
            }
        }
    }
}
