<?php
/* Copyright (C) 2005 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *      \file       htdocs/bookmarks/class/bookmark.class.php
 *      \ingroup    bookmark
 *      \brief      File of class to manage bookmarks
 */


/**
 *		\class      Bookmark
 *		\brief      Class to manage bookmarks
 */
class Bookmark
{
    var $db;

    var $id;
    var $fk_user;
    var $datec;
    var $url;
    var $target;	// 0=replace, 1=new window
    var $title;
    var $position;
    var $favicon;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function Bookmark($db)
    {
        $this->db = $db;
    }

    /**
     *    Directs the bookmark
     *
     *    @param    int		$id		Bookmark Id Loader
     *    @return	int				<0 if KO, >0 if OK
     */
    function fetch($id)
    {
        $sql = "SELECT rowid, fk_user, dateb as datec, url, target,";
        $sql.= " title, position, favicon";
        $sql.= " FROM ".MAIN_DB_PREFIX."bookmark";
        $sql.= " WHERE rowid = ".$id;

		dol_syslog("Bookmark::fetch sql=".$sql, LOG_DEBUG);
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
    function create()
    {
    	// Clean parameters
    	$this->url=trim($this->url);
    	$this->title=trim($this->title);
		if (empty($this->position)) $this->position=0;
		
		$now=dol_now();

    	$this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_user,dateb,url,target";
        $sql.= " ,title,favicon,position";
        if ($this->fk_soc) $sql.=",fk_soc";
        $sql.= ") VALUES (";
        $sql.= ($this->fk_user > 0?"'".$this->fk_user."'":"0").",";
        $sql.= " ".$this->db->idate($now).",";
        $sql.= " '".$this->url."', '".$this->target."',";
        $sql.= " '".$this->db->escape($this->title)."', '".$this->favicon."', '".$this->position."'";
        if ($this->fk_soc) $sql.=",".$this->fk_soc;
        $sql.= ")";

        dol_syslog("Bookmark::update sql=".$sql, LOG_DEBUG);
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
    function update()
    {
    	// Clean parameters
    	$this->url=trim($this->url);
    	$this->title=trim($this->title);
		if (empty($this->position)) $this->position=0;

    	$sql = "UPDATE ".MAIN_DB_PREFIX."bookmark";
        $sql.= " SET fk_user = ".($this->fk_user > 0?"'".$this->fk_user."'":"0");
        $sql.= " ,dateb = '".$this->db->idate($this->datec)."'";
        $sql.= " ,url = '".$this->db->escape($this->url)."'";
        $sql.= " ,target = '".$this->target."'";
        $sql.= " ,title = '".$this->db->escape($this->title)."'";
        $sql.= " ,favicon = '".$this->favicon."'";
        $sql.= " ,position = '".$this->position."'";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("Bookmark::update sql=".$sql, LOG_DEBUG);
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
    function remove($id)
    {
        $sql  = "DELETE FROM ".MAIN_DB_PREFIX."bookmark";
        $sql .= " WHERE rowid = ".$id;

        dol_syslog("Bookmark::remove sql=".$sql, LOG_DEBUG);
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

}
?>
