<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Account {
  var $rowid;

  var $bank;
  var $label;

  var $courant;

  var $code_banque;
  var $code_guichet;
  var $number;
  var $cle_rib;
  var $bic;

  Function Account($DB, $rowid=0) {
    global $config;

    $this->db = $DB;
    $this->rowid = $rowid;
    
    return 1;
  }
  /*
   *
   *
   */
  /*
   *
   *
   *
   */
  Function create()
    {
      $sql = "INSERT INTO llx_bank_account (datec, label) values (now(),'$this->label');";
      if ($this->db->query($sql))
	{
	  if ($this->db->affected_rows()) 
	    {
	      $this->id = $this->db->last_insert_id();
	      $this->update();
	      return $this->id;      
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
   */

  Function update()
    {      
      $sql = "UPDATE llx_bank_account SET ";

      $sql .= " bank = '" .$this->bank ."'";
      $sql .= ",label = '".$this->label ."'";

      $sql .= ",code_banque='".$this->code_banque."'";
      $sql .= ",code_guichet='".$this->code_guichet."'";
      $sql .= ",number='".$this->number."'";
      $sql .= ",cle_rib='".$this->cle_rib."'";
      $sql .= ",bic='".$this->bic."'";
      $sql .= ",courant = ".$this->courant;

      $sql .= " WHERE rowid = $this->id";
      
      $result = $this->db->query($sql);
	      
      if ($result) 
	{
	  if ($this->db->affected_rows()) 
	    {
	      return 1;		      
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
   */
  Function fetch($id)
  {
    $this->id = $id; 
    $sql = "SELECT rowid, label, bank, number, courant, code_banque,code_guichet,cle_rib FROM llx_bank_account";
    $sql .= " WHERE rowid  = ".$id;

    $result = $this->db->query($sql);

    if ($result)
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object($result , 0);
	    
	    $this->bank    = $obj->bank;
	    $this->label   = $obj->label;
	    $this->courant = $obj->courant;

	    $this->code_banque  = $obj->code_banque;
	    $this->code_guichet = $obj->code_guichet;
	    $this->number       = $obj->number;
	    $this->cle_rib      = $obj->cle_rib;
	  }
	$this->db->free();
      }
  }
  /*
   *
   *
   */
  Function solde()
  {
    $sql = "SELECT sum(amount) FROM llx_bank WHERE fk_account=$this->id AND dateo <=" . $this->db->idate(time() );

    $result = $this->db->query($sql);

    if ($result)
      {
	if ($this->db->num_rows())
	  {
	    $solde = $this->db->result(0,0);

	    return $solde;
	  }
	$this->db->free();
      }
  }


}

?>
