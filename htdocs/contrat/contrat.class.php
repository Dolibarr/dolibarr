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

class Contrat
{
  var $id;
  var $db;

  /*
   * Initialisation
   *
   */

  Function Contrat($DB)
  {
    include_once("../societe.class.php3");
    $this->db = $DB ;
    $this->product = new Product($DB);
    $this->societe = new Societe($DB);
    $this->user_service = new User($DB);
    $this->user_cloture = new User($DB);
  }
  /*
   *
   *
   *
   */
  Function mise_en_service($user)
  {
    $sql = "UPDATE llx_contrat SET enservice = 1";
    $sql .= " , mise_en_service = now(), fk_user_mise_en_service = ".$user->id;

    $sql .= " WHERE rowid = ".$this->id;

    $result = $this->db->query($sql) ;
  }
  /*
   *
   *
   */
  Function cloture($user)
  {
    $sql = "UPDATE llx_contrat SET enservice = 2";
    $sql .= " , date_cloture = now(), fk_user_cloture = ".$user->id;
    $sql .= " WHERE rowid = ".$this->id;

    $result = $this->db->query($sql) ;
  }
  /*
   *
   *
   */ 
  Function fetch ($id)
  {    
      $sql = "SELECT rowid, enservice, fk_soc, fk_product, ".$this->db->pdate("mise_en_service")." as datemise";
      $sql .= ", fk_user_mise_en_service, ".$this->db->pdate("date_cloture")." as datecloture";
      $sql .= ", ".$this->db->pdate("fin_validite")." as datefin";
      $sql .= ", fk_user_cloture, fk_facture";
      $sql .= " FROM llx_contrat WHERE rowid = $id";

      $result = $this->db->query($sql) ;

      if ( $result )
	{
	  $result = $this->db->fetch_array();

	  $this->id                = $result["rowid"];
	  $this->enservice         = $result["enservice"];
	  $this->factureid         = $result["fk_facture"];
	  $this->mise_en_service   = $result["datemise"];
	  $this->date_cloture      = $result["datecloture"];
	  $this->date_fin_validite = $result["datefin"];

	  $this->user_service->id = $result["fk_user_mise_en_service"];
	  $this->user_cloture->id = $result["fk_user_cloture"];

	  $this->product->fetch($result["fk_product"]);
	  $this->societe->fetch($result["fk_soc"]);

	  $this->db->free();
	}
      else
	{
	  print $this->db->error();
	}

      return $result;
  }
  /*
   *
   *
   */
  Function create_from_facture($factureid, $user, $socid)
    {
      $sql = "SELECT p.rowid FROM llx_product as p, llx_facturedet as f";
      $sql .= " WHERE p.rowid = f.fk_product AND p.fk_product_type = 1 AND f.fk_facture = ".$factureid;

      if ($this->db->query($sql))
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $objp = $this->db->fetch_object($i);
	      $contrats[$i] = $objp->rowid;
	      $i++;
	    }
	  
	  $this->db->free();

	  while (list($key, $value) = each ($contrats))
	    {
	      $sql = "INSERT INTO llx_contrat (fk_product, fk_facture, fk_soc, fk_user_author)";
	      $sql .= " VALUES ($value, $factureid, $socid, $user->id)";
	      if (! $this->db->query($sql))
		{
		  print $this->db->error();
		}
	    }
	}
      else
	{
	  print $this->db->error();
	}
      
      return $result;
    }
  /*
   *
   *
   */
  
}
?>
