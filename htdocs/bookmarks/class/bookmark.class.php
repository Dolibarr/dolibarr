<?php
/* Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Marcos García       <marcosgdf@gmail.com>
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
 *      \file       htdocs/bookmarks/class/bookmark.class.php
 *      \ingroup    bookmark
 *      \brief      File of class to manage bookmarks
 */


/**
 *		Class to manage bookmarks
 */
class Bookmark extends CommonObject
{
    /**
	 * @var string ID to identify managed object
	 */
	public $element='bookmark';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element='bookmark';

    /**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
    public $ismultientitymanaged = 1;

    /**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'bookmark';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var int ID
     */
    public $id;

    /**
	 * @var int User ID
	 */
	public $fk_user;

    /**
     * Date creation record (datec)
     *
     * @var integer
     */
    public $datec;

    public $url;

    public $target;	// 0=replace, 1=new window

    public $title;

    public $position;

    public $favicon;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *    Directs the bookmark
     *
     *    @param    int		$id		Bookmark Id Loader
     *    @return	int				<0 if KO, >0 if OK
     */
    public function fetch($id)
    {
        global $conf;

        $sql = "SELECT rowid, fk_user, dateb as datec, url, target,";
        $sql.= " title, position, favicon";
        $sql.= " FROM ".MAIN_DB_PREFIX."bookmark";
        $sql.= " WHERE rowid = ".$id;
        $sql.= " AND entity = ".$conf->entity;

		dol_syslog("Bookmark::fetch", LOG_DEBUG);
        $resql  = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);

            $this->id	   = $obj->rowid;
            $this->ref	   = $obj->rowid;

            $this->fk_user = $obj->fk_user;
            $this->datec   = $this->db->jdate($obj->datec);
            $this->url     = $obj->url;
            $this->target  = $obj->target;
            $this->title   = $obj->title;
            $this->position= $obj->position;
            $this->favicon = $obj->favicon;

            $this->db->free($resql);
            return $this->id;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *      Insert bookmark into database
     *
     *      @return     int     <0 si ko, rowid du bookmark cree si ok
     */
    public function create()
    {
        global $conf;

    	// Clean parameters
    	$this->url=trim($this->url);
    	$this->title=trim($this->title);
		if (empty($this->position)) $this->position=0;

		$now=dol_now();

    	$this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_user,dateb,url,target";
        $sql.= ",title,favicon,position";
        $sql.= ",entity";
        $sql.= ") VALUES (";
        $sql.= ($this->fk_user > 0 ? $this->fk_user:"0").",";
        $sql.= " '".$this->db->idate($now)."',";
        $sql.= " '".$this->db->escape($this->url)."', '".$this->db->escape($this->target)."',";
        $sql.= " '".$this->db->escape($this->title)."', '".$this->db->escape($this->favicon)."', '".$this->db->escape($this->position)."'";
        $sql.= ", ".$this->db->escape($conf->entity);
        $sql.= ")";

        dol_syslog("Bookmark::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX."bookmark");
            if ($id > 0)
            {
                $this->id = $id;
                $this->db->commit();
                return $id;
            }
            else
            {
                $this->error=$this->db->lasterror();
                $this->errno=$this->db->lasterrno();
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $this->errno=$this->db->lasterrno();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *      Update bookmark record
     *
     *      @return     int         <0 if KO, > if OK
     */
    public function update()
    {
    	// Clean parameters
    	$this->url=trim($this->url);
    	$this->title=trim($this->title);
		if (empty($this->position)) $this->position=0;

    	$sql = "UPDATE ".MAIN_DB_PREFIX."bookmark";
        $sql.= " SET fk_user = ".($this->fk_user > 0 ? $this->fk_user :"0");
        $sql.= " ,dateb = '".$this->db->idate($this->datec)."'";
        $sql.= " ,url = '".$this->db->escape($this->url)."'";
        $sql.= " ,target = '".$this->db->escape($this->target)."'";
        $sql.= " ,title = '".$this->db->escape($this->title)."'";
        $sql.= " ,favicon = '".$this->db->escape($this->favicon)."'";
        $sql.= " ,position = '".$this->db->escape($this->position)."'";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("Bookmark::update", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *      Removes the bookmark
     *
     *      @param      int		$id     Id removed bookmark
     *      @return     int         	<0 si ko, >0 si ok
     */
    public function remove($id)
    {
        $sql  = "DELETE FROM ".MAIN_DB_PREFIX."bookmark";
        $sql .= " WHERE rowid = ".$id;

        dol_syslog("Bookmark::remove", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'bookmark'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * 	@return 	string					Label of contact status
	 */
	public function getLibStatut($mode)
	{
	    return '';
	}
}
