<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Fichinter {
  var $id;
  var $db;
  var $socidp;
  var $author;
  var $ref;
  var $date;
  var $duree;
  var $note;
  var $projet_id;

  Function Fichinter($DB, $soc_idp="")
    {
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->products = array();
    }

  Function add_product($idproduct)
    {
      if ($idproduct > 0)
	{
	  $i = sizeof($this->products);
	  $this->products[$i] = $idproduct;
	}
    }
  /*
   *
   *
   *
   */
  Function create()
    {
      /*
       *  Insertion dans la base
       */
    if (!strlen($this->duree)) { $this->duree = 0; }

    $sql = "INSERT INTO llx_fichinter (fk_soc, datei, datec, ref, fk_user_author, note, duree, fk_projet) ";
    $sql .= " VALUES ($this->socidp, $this->date, now(), '$this->ref', $this->author, '$this->note', $this->duree, $this->projet_id)";
    $sqlok = 0;
      
    if (! $this->db->query($sql) )
      {
	print $this->db->error() . '<b><br>'.$sql;
      }
    return $this->db->last_insert_id();
    }
  /*
   *
   *
   *
   */
  Function update($id)
    {
      /*
       *  Insertion dans la base
       */
      $sql = "UPDATE llx_fichinter SET ";
      $sql .= " datei = $this->date,";
      $sql .= " note  = '$this->note',";
      $sql .= " duree = $this->duree";
      $sql .= ", fk_projet = $this->projet_id";
      $sql .= " WHERE rowid = $id";
      
      if (! $this->db->query($sql) )
	{
	
	  print $this->db->error() . '<b><br>'.$sql;
	}
      return 1;
    }
  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {
      
      $sql = "SELECT ref,note,fk_soc,fk_statut,duree,".$this->db->pdate(datei)."as di, fk_projet FROM llx_fichinter WHERE rowid=$rowid;";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id         = $rowid;
	      $this->date       = $obj->di;
	      $this->duree      = $obj->duree;
	      $this->ref        = $obj->ref;
	      $this->note       = $obj->note;
	      $this->societe_id = $obj->fk_soc;
	      $this->projet_id  = $obj->fk_projet;
	      $this->statut     = $obj->fk_statut;
	      
	      $this->db->free();
	    }
	}
      else
	{
	  print $this->db->error();
	}    
    }
  /*
   *
   *
   *
   */
  Function valid($userid, $outputdir) {

    $this->fetch($this->id);

    $sql = "UPDATE llx_fichinter SET fk_statut = 1, date_valid=now(), fk_user_valid=$userid";
    $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
    
    if ($this->db->query($sql) ) {

      /*
       * Set generates files readonly
       *
       */
      $file = $outputdir . "/$this->ref/$this->ref.tex";
      print $file;
      if (is_writeable($file)) {
	chmod($file, 0444);
      }
      $file = $outputdir . "/$this->ref/$this->ref.ps";
      if (is_writeable($file)) {
	chmod($file, 0444);
      }
      $file = $outputdir . "/$this->ref/$this->ref.pdf";
      if (is_writeable($file)) {
	chmod($file, 0444);
      }
      return 1;
    } else {
      print $this->db->error() . ' in ' . $sql;
    }

  }
  /*
   *
   *
   *
   */

}    
?>
    
