<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */

class ActionComm
{
  var $id;
  var $db;

  var $date;
  var $type;
  var $priority;
  var $user;
  var $author;
  var $societe;
  var $contact;
  var $note;
  var $percent;


  Function ActionComm($db) 
    {
      $this->db = $db;
      $this->societe = new Societe($db);
      $this->author = new User($db);
      $this->contact = new Contact($db);
      
    }
  /*
   *
   *
   *
   */
  Function add($author)
    {
      if (!strlen($this->contact))
	{
	  $this->contact = 0;
	}
      $sql = "INSERT INTO llx_actioncomm (datea, fk_action, fk_soc, fk_user_author, fk_user_action, fk_contact, percent, note,priority) ";
      $sql .= " VALUES ('$this->date', $this->type, $this->societe, $author->id,";
      $sql .= $this->user->id . ", $this->contact, $this->percent, '$this->note', $this->priority);";
      
      if ($this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print $this->db->error() . "<br>" . $sql;
	}
    }
  /*
   *
   *
   *
   */
  Function fetch($id)
    {      
      $sql = "SELECT ".$this->db->pdate("a.datea")." as da, a.note, c.libelle, fk_soc, fk_user_author, fk_contact, fk_facture, a.percent ";
      $sql .= "FROM llx_actioncomm as a, c_actioncomm as c WHERE a.id=$id AND a.fk_action=c.id;";

      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id = $id;
	      $this->type = $obj->libelle;
	      $this->date = $obj->da;
	      $this->note =$obj->note;
	      $this->percent =$obj->percent;
	      
	      $this->societe->id = $obj->fk_soc;
	      
	      $this->author->id = $obj->fk_user_author;
	      
	      $this->contact->id = $obj->fk_contact;

	      $this->fk_facture = $obj->fk_facture;

	      if ($this->fk_facture)
		{
		  $this->objet_url = '<a href="'. DOL_URL_ROOT . '/compta/facture.php3?facid='.$this->fk_facture.'">Facture</a>';
		}
	      
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
  Function delete($id)
    {
      
      $sql = "DELETE FROM llx_actioncomm WHERE id=$id;";
      
      if ($this->db->query($sql) ) {
	
      }
    }
  /*
   *
   *
   */
}    
?>
