<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Commande 
{
  var $db ;
  var $id ;
  var $brouillon;

  Function Commande($DB)
    {
      $this->db = $DB;
    }
  /**
   * Créé la facture depuis une propale existante
   *
   *
   */
  Function create_from_propale($user, $propale_id)
    {
      $this->propale_id = $propale_id;
      return $this->create($user);
    }
  /**
   * Créé la facture
   *
   *
   */
  Function create($user)
    {
      /* On positionne en mode brouillon la facture */
      $this->brouillon = 1;

      if (! $remise)
	{
	  $remise = 0 ;
	}

      if (! $this->projetid)
	{
	  $this->projetid = 0;
	}
      
      $sql = "INSERT INTO llx_commande (fk_soc, date_creation, fk_user_author,fk_projet) ";
      $sql .= " VALUES ($socid, now(), $user->id, $this->projetid)";      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  $sql = "UPDATE llx_commande SET ref='(PROV".$this->id.")' WHERE rowid=".$this->id;
	  $this->db->query($sql);

	  if ($this->id && $this->propale_id)
	    {
	      $sql = "INSERT INTO llx_co_pr (fk_commande, fk_propale) VALUES (".$this->id.",".$this->propale_id.")";
	      $this->db->query($sql);
	    }
	  /*
	   * Produits
	   *
	   */
	  for ($i = 0 ; $i < sizeof($this->products) ; $i++)
	    {
	      $prod = new Product($this->db, $this->products[$i]);
	      $prod->fetch($this->products[$i]);

	      $result_insert = $this->addline($this->id, 
					      $prod->libelle,
					      $prod->price,
					      $this->products_qty[$i], 
					      $prod->tva_tx, 
					      $this->products[$i], 
					      $this->products_remise_percent[$i]);


	      if ( $result_insert < 0)
		{
		  print $sql . '<br>' . $this->db->error() .'<br>';
		}
	    }

	  /*
	   *
	   *
	   */
	  $this->updateprice($this->id);	  
	  return $this->id;
	}
      else
	{
	  print $this->db->error() . '<b><br>'.$sql;
	  return 0;
	}
    }
  /** 
   *
   * Lit une commande
   *
   */
  Function fetch ($id)
    {
      $sql = "SELECT c.rowid, c.date, c.fk_user_author FROM commande as c";
      $sql .= " WHERE c.rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_object();

	  $this->id          = $obj->rowid;

	  $this->db->free();
	  
	  return 1;

	}
      else
	{
	  return -1;
	}
    }
  /*
   *
   *
   */
}

class CommandeLigne
{
  var $pu;
}

?>
