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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/bookmarks/bookmark.class.php
        \ingroup    bookmark
        \brief      Fichier de la classe des bookmark
        \version    $Id$
*/


/**
        \class      Bookmark
        \brief      Classe permettant la gestion des bookmarks
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
    var $favicon;


    /**
     *    \brief      Constructeur
     *    \param      db          Handler d'acc�s base de donn�es
     *    \param      id          Id du bookmark
     */
    function Bookmark($db, $id=-1)
    {
        $this->db = $db;
        $this->id = $id;
    }

    /**
     *    \brief      Charge le bookmark
     *    \param      id          Id du bookmark � charger
     */
    function fetch($id)
    {
        $sql = "SELECT rowid, fk_user, dateb as datec, url, target,";
        $sql.= " title, favicon";
        $sql.= " FROM ".MAIN_DB_PREFIX."bookmark";
        $sql.= " WHERE rowid = ".$id;

		dol_syslog("Bookmark::fetch sql=".$sql, LOG_DEBUG);
        $resql  = $this->db->query ($sql);
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
     *      \brief      Insere bookmark en base
     *      \return     int     <0 si ko, rowid du bookmark cr�� si ok
     */
    function create()
    {
    	// Clean parameters
    	$this->url=trim($this->url);
    	$this->title=trim($this->title);

    	$this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_user,dateb,url,target";
        $sql.= " ,title,favicon";
        if ($this->fk_soc) $sql.=",fk_soc";
        $sql.= ") VALUES (";
        $sql.= ($this->fk_user > 0?"'".$this->fk_user."'":"0").",";
        $sql.= " ".$this->db->idate(gmmktime()).",";
        $sql.= " '".$this->url."', '".$this->target."',";
        $sql.= " '".addslashes($this->title)."', '".$this->favicon."'";
        if ($this->fk_soc) $sql.=",".$this->fk_soc;
        $sql.= ")";

        dol_syslog("Bookmark::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query ($sql);
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
     *      \brief      Mise � jour du bookmark
     *      \return     int         <0 si ko, >0 si ok
     */
    function update()
    {
    	// Clean parameters
    	$this->url=trim($this->url);
    	$this->title=trim($this->title);

    	$sql = "UPDATE ".MAIN_DB_PREFIX."bookmark";
        $sql.= " SET fk_user = ".($this->fk_user > 0?"'".$this->fk_user."'":"0");
        $sql.= " ,dateb = '".$this->db->idate($this->datec)."'";
        $sql.= " ,url = '".addslashes($this->url)."'";
        $sql.= " ,target = '".$this->target."'";
        $sql.= " ,title = '".addslashes($this->title)."'";
        $sql.= " ,favicon = '".$this->favicon."'";
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog("Bookmark::update sql=".$sql, LOG_DEBUG);
        if ($this->db->query ($sql))
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
     *      \brief      Supprime le bookmark
     *      \param      id          Id bookmark � supprimer
     *      \return     int         <0 si ko, >0 si ok
     */
    function remove($id)
    {
        $sql  = "DELETE FROM ".MAIN_DB_PREFIX."bookmark";
        $sql .= " WHERE rowid = ".$id;

        dol_syslog("Bookmark::remove sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query ($sql);
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
