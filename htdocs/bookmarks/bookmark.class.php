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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/bookmarks/bookmark.class.php
        \ingroup    bookmark
        \brief      Fichier de la classe des bookmark
        \version    $Revision$
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
    var $target;
    var $title;
    var $favicon;

    /**
     *    \brief      Constructeur
     *    \param      db          Handler d'accès base de données
     *    \param      id          Id du bookmark
     */
    function Bookmark($db, $id=-1)
    {
        $this->db = $db;
        $this->id = $id;
    }

    /**
     *    \brief      Charge le bookmark
     *    \param      id          Id du bookmark à charger
     */
    function fetch($id)
    {
        $sql = "SELECT rowid, fk_user, ".$this->db->pdate("dateb").", url, target,";
        $sql.= " title, favicon";
        $sql.= " FROM ".MAIN_DB_PREFIX."bookmark";
        $sql.= " WHERE rowid = ".$id;

        $resql  = $this->db->query ($sql);

        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);

            $this->id	   = $obj->rowid;
            $this->fk_user = $obj->fk_user;
            $this->datec   = $obj->datec;
            $this->url     = $obj->url;
            $this->target  = $obj->target;
            $this->title   = stripslashes($obj->title);
            $this->favicon = $obj->favicon;

            $this->db->free($resql);
            return $this->id;
        }
        else
        {
            dolibarr_print_error ($this->db);
            return -1;
        }
    }

    /**
     *      \brief      Insere bookmark en base
     *      \return     int     <0 si ko, rowid du bookmark créé si ok
     */
    function create()
    {
    	$this->db->begin();
    	
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_user,dateb,url,target";
        $sql.= " ,title,favicon";
        if ($this->fk_soc) $sql.=",fk_soc";
        $sql.= ")";
        $sql.= " VALUES ('".$this->fk_user."', ".$this->db->idate(mktime()).",";
        $sql.= " '".$this->url."', '".$this->target."',";
        $sql.= " '".addslashes($this->title)."', '".$this->favicon."'";
        if ($this->fk_soc) $sql.=",".$this->fk_soc;
        $sql.= ")";
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
     *      \brief      Mise à jour du bookmark
     *      \return     int         <0 si ko, >0 si ok
     */
    function update()
    {
        $sql = "UPDATE ".MAIN_DB_PREFIX."bookmark";
        $sql.= " SET fk_user = '".$this->fk_user."'";
        $sql.= " ,dateb = '".$this->datec."'";
        $sql.= " ,url = '".$this->url."'";
        $sql.= " ,target = '".$this->target."'";
        $sql.= " ,title = '".$this->title."'";
        $sql.= " ,favicon = '".$this->favicon."'";
        $sql.= " WHERE rowid = ".$this->id;

        if ($this->db->query ($sql))
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *      \brief      Supprime le bookmark
     *      \param      id          Id bookmark à supprimer
     *      \return     int         <0 si ko, >0 si ok
     */
    function remove($id)
    {
        $sql  = "DELETE FROM ".MAIN_DB_PREFIX."bookmark";
        $sql .= " WHERE rowid = ".$id;
        
        $resql=$this->db->query ($sql);
        if ($resql)
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }

    }

}
?>
