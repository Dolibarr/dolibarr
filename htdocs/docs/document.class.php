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
   \file       docs/class/courrier-droit-editeur.class.php
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
    dolibarr_syslog("Document::Generate id=$id", LOG_DEBUG );	
    $this->id = $id;
    
    // On récupère données du mail
    $sql = "SELECT classfile,class";
    $sql .= " FROM ".MAIN_DB_PREFIX."document_generator";
    $sql .= " WHERE rowid = '".$this->id."';";
    
    $resql=$this->db->query($sql);
    if ($resql) 
      {
	while ($obj = $this->db->fetch_object($resql) )
	  {
	    $class = $obj->class;
	    $classfile = $obj->classfile;
	  }   
	$this->db->free($resql);
      }
    else
      {
	print $this->db->error();	    
	print "$sql\n";
      }
    
    
    require DOL_DOCUMENT_ROOT.'/'.$classfile;
    $obj = new $class($this->db);

    $err = $obj->Generate();

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."document";
    $sql.= " (name,date_generation) VALUES";
    $sql.= " ('".$obj->name."',now());";

    $resql=$this->db->query($sql);
  }

}

?>
