<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class Propal
{
  var $id;
  var $db;
  var $socidp;
  var $contactid;
  var $projetidp;
  var $author;
  var $ref;
  var $datep;
  var $remise;
  var $products;
  var $products_qty;
  var $note;

  var $price;

  Function Propal($DB, $soc_idp="")
    {
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->products = array();
      $this->remise = 0;
    }
  /*
   *
   *
   *
   */
  Function add_product($idproduct, $qty)
    {
      if ($idproduct > 0)
	{
	  $i = sizeof($this->products);
	  $this->products[$i] = $idproduct;
	  if (!$qty)
	    {
	      $qty = 1 ;
	    }
	  $this->products_qty[$i] = $qty;
	}
    }
  /*
   *
   *
   */
  Function insert_product($idproduct, $qty)
    {
      if ($this->statut == 0)
	{
	  $prod = new Product($this->db, $idproduct);
	  $prod->fetch($idproduct);
	  
	  $sql = "INSERT INTO llx_propaldet (fk_propal, fk_product, qty, price, tva_tx, description) VALUES ";
	  $sql .= " (".$this->id.",". $idproduct.",". $qty.",". $prod->price.",".$prod->tva_tx.",'".$prod->label."') ; ";
	  
	  if ($this->db->query($sql) )
	    {
	      
	      $this->update_price($this->id);
	      
	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	}
    }
  /*
   *
   *
   */
  Function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx=19.6)
    {
      if ($this->statut == 0)
	{
	  $p_price = ereg_replace(",",".",$p_price);
	  $sql = "INSERT INTO llx_propaldet (fk_propal, fk_product, qty, price, tva_tx, description) VALUES ";
	  $sql .= " (".$this->id.", 0,". $p_qty.",". $p_price.",".$p_tva_tx.",'".$p_desc."') ; ";
	  
	  if ($this->db->query($sql) )
	    {
	      
	      $this->update_price($this->id);
	      
	      return 1;
	    }
	  else
	    {
	      print $this->db->error();
	      print "<br>".$sql;
	      return 0;
	    }
	}
    }
  /*
   *
   *
   */
  Function fetch_client()
    {
      $client = new Societe($this->db);
      $client->fetch($this->socidp);
      $this->client = $client;
	
    }
  /*
   *
   *
   */
  Function delete_product($idligne)
    {
      if ($this->statut == 0)
	{
	  $sql = "DELETE FROM llx_propaldet WHERE rowid = $idligne";
	  
	  if ($this->db->query($sql) )
	    {
	      
	      $this->update_price($this->id);
	      
	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
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
      $sql = "INSERT INTO llx_propal (fk_soc, fk_soc_contact, price, remise, tva, total, datep, datec, ref, fk_user_author, note, model_pdf) ";
      $sql .= " VALUES ($this->socidp, $this->contactid, 0, $this->remise, 0,0, $this->datep, now(), '$this->ref', $this->author, '$this->note','$this->modelpdf')";
      $sqlok = 0;
      
      if ( $this->db->query($sql) )
	{

	  $this->id = $this->db->last_insert_id();
	  
	  $sql = "SELECT rowid FROM llx_propal WHERE ref='$this->ref';";
	  if ( $this->db->query($sql) ) 
	    { 
	      /*
	       *  Insertion du detail des produits dans la base
	       */
	      if ( $this->db->num_rows() )
		{
		  $propalid = $this->db->result( 0, 0);
		  $this->db->free();
		  
		  for ($i = 0 ; $i < sizeof($this->products) ; $i++)
		    {
		      $prod = new Product($this->db, $this->products[$i]);
		      $prod->fetch($this->products[$i]);
		    
		      $sql = "INSERT INTO llx_propaldet (fk_propal, fk_product, qty, price, tva_tx) VALUES ";
		      $sql .= " ($propalid,".$this->products[$i].",".$this->products_qty[$i].",$prod->price,$prod->tva_tx);";
		    
		      if (! $this->db->query($sql) )
			{
			  print $sql . '<br>' . $this->db->error() .'<br>';
			}
		    }
		  /*
		   *
		   */
		  $this->update_price($this->id);
		  /*
		   *  Affectation au projet
		   */
		  if ($this->projetidp)
		    {
		      $sql = "UPDATE llx_propal SET fk_projet=$this->projetidp WHERE ref='$this->ref';";
		      $this->db->query($sql);
		    }
		}	  
	    }
	  else
	    {
	      print $this->db->error() . '<b><br>'.$sql;
	    }
	}
      else
	{
	  print $this->db->error() . '<b><br>'.$sql;
	}
      return $this->id;
    }
  /*
   *
   *
   */
  Function update_price($rowid)
    {
      $totalht=0;
      $totaltva=0;
      $totalttc=0;

      /*
       *  Remise
       */
      $sql = "SELECT remise FROM llx_propal WHERE rowid = $rowid";
      if ( $this->db->query($sql) )
	{
	  $remise = $this->db->result(0, 0);
	  $this->db->free();
	  
      
	  /*
	   *  Total des produits a ajouter
	   */
	  $sql = "SELECT price, qty, tva_tx FROM llx_propaldet WHERE fk_propal = $rowid";
	  if ( $this->db->query($sql) )
	    {
	      $num = $this->db->num_rows();
	      $i = 0;

	      while ($i < $num)
		{
		  $obj = $this->db->fetch_object($i);

		  $totalht = $totalht + ($obj->qty * $obj->price);
		  $totaltva = $totaltva + (tva(($obj->qty * $obj->price), $obj->tva_tx));
		  $i++;
		}

	      $this->db->free();
	      
	      /*
	       *  Calcul TVA, Remise
	       */
	      $totalht = $totalht - $this->remise;
	      $totalttc = $totalht + $totaltva;
	      /*
	       *
	       */
	      $sql = "UPDATE llx_propal set price=$totalht, tva=$totaltva, total=$totalttc WHERE rowid = $rowid";
	      if (! $this->db->query($sql) )
		{
		  print "Erreur mise à jour du prix<p>".$sql;
		}
	    }
	}
      
    }

  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {

      $sql = "SELECT ref,total,price,remise,tva,fk_soc,".$this->db->pdate(datep)."as dp, model_pdf ";
      $sql .= " FROM llx_propal WHERE rowid=$rowid;";

      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	  
	      $this->id        = $rowid;
	      $this->datep     = $obj->dp;
	      $this->date      = $obj->dp;
	      $this->ref       = $obj->ref;
	      $this->price     = $obj->price;
	      $this->remise    = $obj->remise;
	      $this->total     = $obj->total;
	      $this->total_ht  = $obj->price;
	      $this->total_tva = $obj->tva;
	      $this->total_ttc = $obj->total;
	      $this->socidp    = $obj->fk_soc;
	      $this->modelpdf  = $obj->model_pdf;
	      $this->lignes = array();
	      $this->db->free();

	      $this->ref_url = '<a href="'.DOL_URL_ROOT.'/comm/propal.php3?propalid='.$this->id.'">'.$this->ref.'</a>';

	      /*
	       * Lignes
	       */

	      $sql = "SELECT d.qty, p.description, p.ref, p.price, d.tva_tx, p.rowid";
	      $sql .= " FROM llx_propaldet as d, llx_product as p";
	      $sql .= " WHERE d.fk_propal = ".$this->id ." AND d.fk_product = p.rowid";
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $i = 0; 
		  
		  while ($i < $num)
		    {
		      $objp              = $this->db->fetch_object($i);
		      $ligne             = new PropaleLigne();
		      $ligne->desc       = stripslashes($objp->description);
		      $ligne->qty        = $objp->qty;
		      $ligne->ref        = $objp->ref;
		      $ligne->tva_tx     = $objp->tva_tx;
		      $ligne->price      = $objp->price;
		      $ligne->product_id = $objp->rowid;
		      $this->lignes[$i]  = $ligne;
		      $i++;
		    }
	    
		  $this->db->free();
		} 
	      else
		{
		  print $this->db->error();
		}

	      /*
	       * Lignes
	       */

	      $sql = "SELECT d.qty, d.description, d.price, d.tva_tx, d.rowid";
	      $sql .= " FROM llx_propaldet as d";
	      $sql .= " WHERE d.fk_propal = ".$this->id ." AND d.fk_product = 0";
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $j = 0; 
		  
		  while ($j < $num)
		    {
		      $objp              = $this->db->fetch_object($i);
		      $ligne             = new PropaleLigne();
		      $ligne->desc       = stripslashes($objp->description);
		      $ligne->qty        = $objp->qty;
		      $ligne->ref        = $objp->ref;
		      $ligne->tva_tx     = $objp->tva_tx;
		      $ligne->price      = $objp->price;
		      $ligne->product_id = $objp->rowid;
		      $this->lignes[$i]  = $ligne;
		      $i++;
		      $j++;
		    }
	    
		  $this->db->free();
		} 
	      else
		{
		  print $this->db->error();
		}


	    }
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  return 0;
	}    
    }
  /*
   *
   *
   *
   */
  Function valid($user)
    {

      if ($user->rights->propale->valider)
	{

	  $sql = "UPDATE llx_propal SET fk_statut = 1, date_valid=now(), fk_user_valid=$user->id";
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      return 1;
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	    }
	}
  }
  /*
   *
   *
   *
   */
  Function cloture($userid, $statut, $note)
    {
      $sql = "UPDATE llx_propal SET fk_statut = $statut, note = '$note', date_cloture=now(), fk_user_cloture=$userid";
      
      $sql .= " WHERE rowid = $this->id;";
      
      if ($this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}
    }
  /*
   *
   *
   *
   */
  Function reopen($userid)
    {
      $sql = "UPDATE llx_propal SET fk_statut = 0";
      
      $sql .= " WHERE rowid = $this->id;";
      
      if ($this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print $this->db->error() . ' in ' . $sql;
	}
    }
  /*
   *
   *
   */
  Function liste_array ($brouillon=0, $user='')
    {
      $ga = array();

      $sql = "SELECT rowid, ref FROM llx_propal";
      if ($brouillon = 1)
	{
	  $sql .= " WHERE fk_statut = 0";
	  if ($user)
	    {
	      $sql .= " AND fk_user_author".$user;
	    }
	}
      else
	{
	  if ($user)
	    {
	      $sql .= " WHERE fk_user_author".$user;
	    }
	}
      
      $sql .= " ORDER BY datep DESC";
      
      if ($this->db->query($sql) )
	{
	  $nump = $this->db->num_rows();
	  
	  if ($nump)
	    {
	      $i = 0;
	      while ($i < $nump)
		{
		  $obj = $this->db->fetch_object($i);
		  
		  $ga[$obj->rowid] = $obj->ref;
		  $i++;
		}
	    }
	  return $ga;
	}
      else
	{
	  print $this->db->error();
	}      
    }
}  

class PropaleLigne  
{
  Function PropaleLigne()
    {
    }
}
?>
