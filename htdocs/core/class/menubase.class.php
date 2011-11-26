<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2010 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/class/menubase.class.php
 *  \ingroup    core
 *  \brief      File of class to manage dynamic menu entries
 */


/**
 *  \class      Menubase
 *  \brief      Class to manage menu entries
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
    var $fk_mainmenu;
    var $fk_leftmenu;
    var $position;
    var $url;
    var $target;
    var $titre;
    var $langs;
    var $level;
    var $leftmenu;		//<! 0=Left menu in pre.inc.php files must not be overwrite by database menu, 1=Must be
    var $perms;
    var $enabled;
    var $user;
    var $tms;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB 		    Database handler
     *  @param     	string		$menu_handler	Menu handler
     *  @param     	string		$type			Type
     */
    function Menubase($DB,$menu_handler='',$type='')
    {
        $this->db = $DB;
        $this->menu_handler = $menu_handler;
        $this->type = $type;
        return 1;
    }


    /**
     *      Create menu entry into database
     *
     *      @param      User	$user       User that create
     *      @return     int      			<0 if KO, Id of record if OK
     */
    function create($user=0)
    {
        global $conf, $langs;

        // Clean parameters
        $this->menu_handler=trim($this->menu_handler);
        $this->module=trim($this->module);
        $this->type=trim($this->type);
        $this->mainmenu=trim($this->mainmenu);
        $this->leftmenu=trim($this->leftmenu);
        $this->fk_menu=trim($this->fk_menu);
        $this->fk_mainmenu=trim($this->fk_mainmenu);
        $this->fk_leftmenu=trim($this->fk_leftmenu);
        $this->position=trim($this->position);
        $this->url=trim($this->url);
        $this->target=trim($this->target);
        $this->titre=trim($this->titre);
        $this->langs=trim($this->langs);
        $this->perms=trim($this->perms);
        $this->enabled=trim($this->enabled);
        $this->user=trim($this->user);
        if (! $this->level) $this->level=0;

        // Check parameters
        // Put here code to add control on parameters values

        // FIXME
        // Get the max rowid in llx_menu and use it as rowid in insert because postgresql
        // may use an already used value because its internal cursor does not increase when we do
        // an insert with a forced id.
        // Two solution: Disable menu handler using database when database is postgresql or update counter when
        // enabling such menus.

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."menu(";
        $sql.= "menu_handler,";
        $sql.= "entity,";
        $sql.= "module,";
        $sql.= "type,";
        $sql.= "mainmenu,";
        $sql.= "leftmenu,";
        $sql.= "fk_menu,";
        $sql.= "fk_mainmenu,";
        $sql.= "fk_leftmenu,";
        $sql.= "position,";
        $sql.= "url,";
        $sql.= "target,";
        $sql.= "titre,";
        $sql.= "langs,";
        $sql.= "perms,";
        $sql.= "enabled,";
        $sql.= "usertype";
        $sql.= ") VALUES (";
        $sql.= " '".$this->menu_handler."',";
        $sql.= " '".$conf->entity."',";
        $sql.= " '".$this->module."',";
        $sql.= " '".$this->type."',";
        $sql.= " '".$this->mainmenu."',";
        $sql.= " '".$this->leftmenu."',";
        $sql.= " '".$this->fk_menu."',";
        $sql.= " ".($this->fk_mainmenu?"'".$this->fk_mainmenu."'":"null").",";
        $sql.= " ".($this->fk_leftmenu?"'".$this->fk_leftmenu."'":"null").",";
        $sql.= " '".$this->position."',";
        $sql.= " '".$this->url."',";
        $sql.= " '".$this->target."',";
        $sql.= " '".$this->titre."',";
        $sql.= " '".$this->langs."',";
        $sql.= " '".$this->perms."',";
        $sql.= " '".$this->enabled."',";
        $sql.= " '".$this->user."'";
        $sql.= ")";

        dol_syslog("Menubase::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."menu");
            dol_syslog("Menubase::create record added has rowid=".$this->id, LOG_DEBUG);

            return $this->id;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog("Menubase::create ".$this->error, LOG_ERR);
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
    function update($user=0, $notrigger=0)
    {
        global $conf, $langs;

        // Clean parameters
        $this->rowid=trim($this->rowid);
        $this->menu_handler=trim($this->menu_handler);
        $this->module=trim($this->module);
        $this->type=trim($this->type);
        $this->mainmenu=trim($this->mainmenu);
        $this->leftmenu=trim($this->leftmenu);
        $this->fk_menu=trim($this->fk_menu);
        $this->fk_mainmenu=trim($this->fk_mainmenu);
        $this->fk_leftmenu=trim($this->fk_leftmenu);
        $this->position=trim($this->position);
        $this->url=trim($this->url);
        $this->target=trim($this->target);
        $this->titre=trim($this->titre);
        $this->langs=trim($this->langs);
        $this->perms=trim($this->perms);
        $this->enabled=trim($this->enabled);
        $this->user=trim($this->user);

        // Check parameters
        // Put here code to add control on parameters values

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."menu SET";
        $sql.= " menu_handler='".$this->db->escape($this->menu_handler)."',";
        $sql.= " module='".$this->db->escape($this->module)."',";
        $sql.= " type='".$this->type."',";
        $sql.= " mainmenu='".$this->db->escape($this->mainmenu)."',";
        $sql.= " leftmenu='".$this->db->escape($this->leftmenu)."',";
        $sql.= " fk_menu='".$this->fk_menu."',";
        $sql.= " fk_mainmenu=".($this->fk_mainmenu?"'".$this->fk_mainmenu."'":"null").",";
        $sql.= " fk_leftmenu=".($this->fk_leftmenu?"'".$this->fk_leftmenu."'":"null").",";
        $sql.= " position='".$this->position."',";
        $sql.= " url='".$this->db->escape($this->url)."',";
        $sql.= " target='".$this->db->escape($this->target)."',";
        $sql.= " titre='".$this->db->escape($this->titre)."',";
        $sql.= " langs='".$this->db->escape($this->langs)."',";
        $sql.= " perms='".$this->db->escape($this->perms)."',";
        $sql.= " enabled='".$this->db->escape($this->enabled)."',";
        $sql.= " usertype='".$this->user."'";
        $sql.= " WHERE rowid=".$this->id;

        dol_syslog("Menubase::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog("Menubase::update ".$this->error, LOG_ERR);
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
        $sql.= " t.entity,";
        $sql.= " t.module,";
        $sql.= " t.type,";
        $sql.= " t.mainmenu,";
        $sql.= " t.leftmenu,";
        $sql.= " t.fk_menu,";
        $sql.= " t.position,";
        $sql.= " t.url,";
        $sql.= " t.target,";
        $sql.= " t.titre,";
        $sql.= " t.langs,";
        $sql.= " t.perms,";
        $sql.= " t.enabled,";
        $sql.= " t.usertype as user,";
        $sql.= " t.tms";
        $sql.= " FROM ".MAIN_DB_PREFIX."menu as t";
        $sql.= " WHERE t.rowid = ".$id;

        dol_syslog("Menubase::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;

                $this->menu_handler = $obj->menu_handler;
                $this->entity = $obj->entity;
                $this->module = $obj->module;
                $this->type = $obj->type;
                $this->mainmenu = $obj->mainmenu;
                $this->leftmenu = $obj->leftmenu;
                $this->fk_menu = $obj->fk_menu;
                $this->position = $obj->position;
                $this->url = $obj->url;
                $this->target = $obj->target;
                $this->titre = $obj->titre;
                $this->langs = $obj->langs;
                $this->perms = $obj->perms;
                $this->enabled = str_replace("\"","'",$obj->enabled);
                $this->user = $obj->user;
                $this->tms = $this->db->jdate($obj->tms);
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog("Menubase::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *  Delete object in database
     *
     *	@param	User	$user       User that delete
     *	@return	int					<0 if KO, >0 if OK
     */
    function delete($user)
    {
        global $conf, $langs;

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."menu";
        $sql.= " WHERE rowid=".$this->id;

        dol_syslog("Menubase::delete sql=".$sql);
        $resql = $this->db->query($sql);
        if (! $resql)
        {
            $this->error="Error ".$this->db->lasterror();
            dol_syslog("Menubase::delete ".$this->error, LOG_ERR);
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
        $this->enabled='';
        $this->user='';
        $this->tms='';
    }


    /**
     *  Complete this->newmenu with menu entry found in $tab
     *
     *  @param  array	$tab			Tab array
     *  @param  int		$pere			Id of parent
     *  @param  int		$rang			Rang
     *  @param  string	$myleftmenu     Value for left that defined leftmenu
     *  @return	void
     */
    function recur($tab, $pere, $rang, $myleftmenu)
    {
        global $leftmenu;	// To be exported in dol_eval function

        //print "xx".$pere;
        $leftmenu = $myleftmenu;

        //ballayage du tableau
        $num = count($tab);
        for ($x = 0; $x < $num; $x++)
        {
            //si un element a pour pere : $pere
            if ($tab[$x][1] == $pere)
            {
                if ($tab[$x][7])
                {
                    $leftmenuConstraint = true;
                    if ($tab[$x][6])
                    {
                        $leftmenuConstraint = verifCond($tab[$x][6]);
                    }

                    if ($leftmenuConstraint)
                    {
                        //print 'name='.$tab[$x][3].' pere='.$pere." ".$tab[$x][6];

                        $this->newmenu->add((! preg_match("/^(http:\/\/|https:\/\/)/i",$tab[$x][2])) ? $tab[$x][2] : $tab[$x][2], $tab[$x][3], $rang -1, $tab[$x][4], $tab[$x][5], $tab[$x][8]);
                        $this->recur($tab, $tab[$x][0], $rang +1, $lelfmenu);
                    }
                }
            }
        }
    }

    /**
     *	Load tabMenu array
     *
     * 	@param	string	$mainmenu		Value for mainmenu that defined top menu
     * 	@param	string	$myleftmenu		Left menu name
     * 	@param	int		$type_user		0=Internal,1=External,2=All
     * 	@param	string	$menu_handler	Name of menu_handler used (auguria, eldy...)
     * 	@param  array	&$tabMenu        If array with menu entries already loaded, we put this array here (in most cases, it's empty)
     * 	@return	array					Return array with menu entries for top menu
     */
    function menuTopCharger($mainmenu, $myleftmenu, $type_user, $menu_handler, &$tabMenu)
    {
        global $langs, $user, $conf;
        global $leftmenu,$rights;	// To export to dol_eval function

        $leftmenu=$myleftmenu;  // To export to dol_eval function

        // Load datas into tabMenu
        if (count($tabMenu) == 0)
        {
            $this->menuLoad($leftmenu, $type_user, $menu_handler, $tabMenu);
        }

        $newTabMenu=array();
        $i=0;
        if (is_array($tabMenu))
        {
            foreach($tabMenu as $val)
            {
                if ($val[9]=='top')
                {

                    $newTabMenu[$i]['rowid']=$val[0];
                    $newTabMenu[$i]['fk_menu']=$val[1];
                    $newTabMenu[$i]['url']=$val[2];
                    $newTabMenu[$i]['titre']=$val[3];
                    $newTabMenu[$i]['right']=$val[4];
                    $newTabMenu[$i]['atarget']=$val[5];
                    $newTabMenu[$i]['leftmenu']=$val[6];
                    $newTabMenu[$i]['enabled']=$val[7];
                    $newTabMenu[$i]['mainmenu']=$val[8];
                    $newTabMenu[$i]['type']=$val[9];
                    $newTabMenu[$i]['lang']=$val[10];
                    $i++;
                }
            }
        }

        return $newTabMenu;
    }

    /**
     * 	Load entries found in database in a menu array.
     *
     * 	@param	array	$newmenu        Menu array to complete
     * 	@param	string	$mainmenu       Value for mainmenu that defined top menu of left menu
     * 	@param 	string	$myleftmenu     Value that defined leftmenu
     * 	@param  int		$type_user		0=Internal,1=External,2=All
     * 	@param  string	$menu_handler   Name of menu_handler used (auguria, eldy...)
     * 	@param  array	&$tabMenu       If array with menu entries already loaded, we put this array here (in most cases, it's empty)
     * 	@return array    		       	Menu array for particular mainmenu value or full tabArray
     */
    function menuLeftCharger($newmenu, $mainmenu, $myleftmenu, $type_user, $menu_handler, &$tabMenu)
    {
        global $langs, $user, $conf; // To export to dol_eval function
        global $leftmenu,$rights; // To export to dol_eval function

        $leftmenu=$myleftmenu;  // To export to dol_eval function

        $this->newmenu = $newmenu;

        // Load datas into tabMenu
        if (count($tabMenu) == 0)
        {
            $this->menuLoad($leftmenu, $type_user, $menu_handler, $tabMenu);
        }
        //var_dump($tabMenu);

        // Define menutopid
        $menutopid='';
        if (is_array($tabMenu))
        {
            foreach($tabMenu as $val)
            {
                if ($val[9] == 'top' && $val[8] == $mainmenu)
                {
                    $menutopid=$val[0];
                    break;
                }
            }
        }

        // Now edit this->newmenu->list to add entries found into tabMenu that are in childs of mainmenu claimed
        $this->recur($tabMenu, $menutopid, 1, $leftmenu);

        return $this->newmenu;
    }


    /**
     *  Load entries found in database in a menu array.
     *
     *  @param	string	$myleftmenu     Value for left that defined leftmenu
     *  @param  int		$type_user      0=Internal,1=External,2=All
     *  @param  string	$menu_handler   Name of menu_handler used (auguria, eldy...)
     *  @param  array	&$tabMenu       If array with menu entries already load, we put this array here (in most cases, it's empty)
     *  @return int     		        >0 if OK, <0 if KO
     */
    function menuLoad($myleftmenu, $type_user, $menu_handler, &$tabMenu)
    {
        global $langs, $user, $conf; // To export to dol_eval function
        global $leftmenu, $rights; // To export to dol_eval function

        $menutopid=0;
        $leftmenu=$myleftmenu;  // To export to dol_eval function

        $sql = "SELECT m.rowid, m.type, m.fk_menu, m.url, m.titre, m.langs, m.perms, m.enabled, m.target, m.mainmenu, m.leftmenu";
        $sql.= " FROM ".MAIN_DB_PREFIX."menu as m";
        $sql.= " WHERE m.entity = ".$conf->entity;
        $sql.= " AND m.menu_handler in('".$menu_handler."','all')";
        if ($type_user == 0) $sql.= " AND m.usertype in (0,2)";
        if ($type_user == 1) $sql.= " AND m.usertype in (1,2)";
        // If type_user == 2, no test required
        $sql.= " ORDER BY m.position, m.rowid";

        dol_syslog("Menubase::menuLeftCharger sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $numa = $this->db->num_rows($resql);

            $a = 0;
            $b = 0;
            $oldrowid=0;
            while ($a < $numa)
            {
                //$objm = $this->db->fetch_object($resql);
                $menu = $this->db->fetch_array($resql);

                // Define $chaine
                $chaine="";
                $title = $langs->trans($menu['titre']);
                if ($title == $menu['titre'])   // Translation not found
                {
                    if (! empty($menu['langs']))    // If there is a dedicated translation file
                    {
                        $langs->load($menu['langs']);
                    }

                    if (preg_match("/\//",$menu['titre'])) // To manage translation when title is string1/string2
                    {
                        $tab_titre = explode("/",$menu['titre']);
                        $chaine = $langs->trans($tab_titre[0])."/".$langs->trans($tab_titre[1]);
                    }
                    else
                    {
                        $chaine = $langs->trans($menu['titre']);
                    }
                }
                else
                {
                    $chaine = $title;
                }

                // Define $right
                $perms = true;
                if ($menu['perms'])
                {
                    $perms = verifCond($menu['perms']);
                    //print "verifCond rowid=".$menu['rowid']." ".$menu['right'].":".$perms."<br>\n";
                }

                // Define $enabled
                $enabled = true;
                if ($menu['enabled'])
                {
                    $enabled = verifCond($menu['enabled']);
                    if ($conf->use_javascript_ajax && $conf->global->MAIN_MENU_USE_JQUERY_ACCORDION && preg_match('/^\$leftmenu/',$menu['enabled'])) $enabled=1;
                    //print "verifCond chaine=".$chaine." rowid=".$menu['rowid']." ".$menu['enabled'].":".$enabled."<br>\n";
                }

                // 0=rowid, 1=fk_menu, 2=url, 3=text, 4=perms, 5=target, 8=mainmenu
                $tabMenu[$b][0] = $menu['rowid'];
                $tabMenu[$b][1] = $menu['fk_menu'];
                $tabMenu[$b][2] = $menu['url'];
                if (! preg_match("/^(http:\/\/|https:\/\/)/i",$tabMenu[$b][2]))
                {
                    if (preg_match('/\?/',$tabMenu[$b][2])) $tabMenu[$b][2].='&amp;idmenu='.$menu['rowid'];
                    else $tabMenu[$b][2].='?idmenu='.$menu['rowid'];
                }
                $tabMenu[$b][3] = $chaine;
                $tabMenu[$b][5] = $menu['target'];
                $tabMenu[$b][6] = $menu['leftmenu'];
                if (! isset($tabMenu[$b][4])) $tabMenu[$b][4] = $perms;
                else $tabMenu[$b][4] = ($tabMenu[$b][4] && $perms);
                if (! isset($tabMenu[$b][7])) $tabMenu[$b][7] = $enabled;
                else $tabMenu[$b][7] = ($tabMenu[$b][7] && $enabled);
                $tabMenu[$b][8] = $menu['mainmenu'];
                $tabMenu[$b][9] = $menu['type'];
                $tabMenu[$b][10] = $menu['langs'];

                $b++;
                $a++;
            }
            $this->db->free($resql);
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

}

?>
