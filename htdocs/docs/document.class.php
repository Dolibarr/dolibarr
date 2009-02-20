<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
   \file       htdocs/docs/document.class.php
   \ingroup    editeurs
   \brief      Classe de generation des courriers pour les editeurs
*/


class Document
{
  /**
     \brief      Constructeur
     \param	 db	     Handler accès base de donnée
  */
  function Document ($db)
  {
    $this->db = $db;
  }



  /**
     \brief Génère le document
     \return int 0= ok, <> 0 = ko
  */
  function Generate ($id)
  {
    $errno = 0;

    dol_syslog("Document::Generate id=$id", LOG_DEBUG );
    $this->id = $id;
    $class = $id;
    $classfile = 'docs/class/'.$class.'.class.php';

    require DOL_DOCUMENT_ROOT.'/'.$classfile;
    $obj = new $class($this->db);

    $this->db->begin();

    $sql = "DELETE FROM  ".MAIN_DB_PREFIX."document";
    $sql.= " WHERE name='".$obj->name."';";

    $resql=$this->db->query($sql);

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document";
    $sql.= " (name,file_name,file_extension,date_generation) VALUES";
    $sql.= " ('".$obj->name."','".$obj->file."','".$obj->extension."',".$this->db->idate(mktime()).")";

    $resql=$this->db->query($sql);

    $id = $this->db->last_insert_id(MAIN_DB_PREFIX."document");

    $err = $obj->Generate($id);

    if ($err === 0)
      {
	$this->db->commit();
	dol_syslog("Document::Generate COMMIT", LOG_DEBUG );
      }
    else
      {
	$this->db->rollback();
	dol_syslog("Document::Generate ROLLBACK", LOG_ERR );
      }

    return $errno;
  }

}

?>
