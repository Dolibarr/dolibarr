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

  Function Propal($DB, $soc_idp="", $propalid=0)
    {
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->id = $propalid;
      $this->products = array();
      $this->remise = 0;
    }
  /*
   *
   *
   *
   */
  Function add_product($idproduct, $qty, $remise_percent=0)
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
	  $this->products_remise_percent[$i] = $remise_percent;
	}
    }
  /*
   *
   *
   */
  Function insert_product($idproduct, $qty, $remise_percent=0)
    {
      if ($this->statut == 0)
	{
	  $prod = new Product($this->db, $idproduct);
	  if ($prod->fetch($idproduct) > 0)
	    {
	      $price = $prod->price;
	      $subprice = $prod->price;
	      if ($remise_percent > 0)
		{
		  $remise = round(($prod->price * $remise_percent / 100), 2);
		  $price = $prod->price - $remise;
		}
	  
	      $sql = "INSERT INTO llx_propaldet (fk_propal, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
	      $sql .= " (".$this->id.",". $idproduct.",". $qty.",". $price.",".$prod->tva_tx.",'".addslashes($prod->label)."',$remise_percent, $subprice)";
	  
	      if ($this->db->query($sql) )
		{		  
		  $this->update_price();
		  
		  return 1;
		}
	      else
		{
		  return -1;
		}
	    }
	  else
	    {
	      return -2;
	    }
	}
    }
  /*
   *
   *
   */
  Function insert_product_generic($p_desc, $p_price, $p_qty, $p_tva_tx=19.6, $remise_percent=0)
    {
      if ($this->statut == 0)
	{
	  if (strlen(trim($p_qty)) == 0)
	    {
	      $p_qty = 1;
	    }

	  $p_price = ereg_replace(",",".",$p_price);

	  $price = $p_price;
	  $subprice = $p_price;
	  if ($remise_percent > 0)
	    {
	      $remise = round(($p_price * $remise_percent / 100), 2);
	      $price = $p_price - $remise;
	    }

	  $sql = "INSERT INTO llx_propaldet (fk_propal, fk_product, qty, price, tva_tx, description, remise_percent, subprice) VALUES ";
	  $sql .= " (".$this->id.", 0,". $p_qty.",". $price.",".$p_tva_tx.",'".$p_desc."',$remise_percent, $subprice) ; ";
	  
	  if ($this->db->query($sql) )
	    {
	      
	      if ($this->update_price() > 0)
		{	      
		  return 1;
		}
	      else
		{
		  return -1;
		}
	    }
	  else
	    {
	      print $this->db->error();
	      print "<br>".$sql;
	      return -2;
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
	      $this->update_price();
	      
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
		    
		      $this->insert_product($this->products[$i], 
					    $this->products_qty[$i], 
					    $this->products_remise_percent[$i]);
		    }
		  /*
		   *
		   */
		  $this->update_price();
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
  Function update_price()
    {
      include_once DOL_DOCUMENT_ROOT . "/lib/price.lib.php";

      /*
       *  Liste des produits a ajouter
       */
      $sql = "SELECT price, qty, tva_tx FROM llx_propaldet WHERE fk_propal = $this->id";
      if ( $this->db->query($sql) )
	{
	  $num = $this->db->num_rows();
	  $i = 0;
	  
	  while ($i < $num)
	    {
	      $obj = $this->db->fetch_object($i);
	      $products[$i][0] = $obj->price;
	      $products[$i][1] = $obj->qty;
	      $products[$i][2] = $obj->tva_tx;
	      $i++;
	    }
	}
      $calculs = calcul_price($products, $this->remise_percent);

      $totalht = $calculs[0];
      $totaltva = $calculs[1];
      $totalttc = $calculs[2];
      $total_remise = $calculs[3];

      $this->remise         = $total_remise;
      $this->total_ht       = $totalht;
      $this->total_tva      = $totaltva;
      $this->total_ttc      = $totalttc;
      /*
       *
       */
      $sql = "UPDATE llx_propal set price=$totalht, tva=$totaltva, total=$totalttc, remise=$total_remise WHERE rowid = $this->id";
      if ( $this->db->query($sql) )
	{
	  return 1;
	}
      else
	{
	  print "Erreur mise à jour du prix<p>".$sql;
	  return -1;
	}
    }
  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {

      $sql = "SELECT ref,total,price,remise,tva,fk_soc,fk_soc_contact,".$this->db->pdate(datep)."as dp, model_pdf, note, fk_statut, remise_percent";
      $sql .= " FROM llx_propal WHERE rowid=$rowid;";

      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	  
	      $this->id             = $rowid;
	      $this->datep          = $obj->dp;
	      $this->date           = $obj->dp;
	      $this->ref            = $obj->ref;
	      $this->price          = $obj->price;
	      $this->remise         = $obj->remise;
	      $this->remise_percent = $obj->remise_percent;
	      $this->total          = $obj->total;
	      $this->total_ht       = $obj->price;
	      $this->total_tva      = $obj->tva;
	      $this->total_ttc      = $obj->total;
	      $this->socidp         = $obj->fk_soc;
	      $this->contactid      = $obj->fk_soc_contact;
	      $this->modelpdf       = $obj->model_pdf;
	      $this->note           = $obj->note;

	      if ($obj->fk_statut == 0)
		{
		  $this->brouillon = 1;
		}

	      $this->lignes = array();
	      $this->db->free();

	      $this->ref_url = '<a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$this->id.'">'.$this->ref.'</a>';

	      /*
	       * Lignes produits
	       */

	      $sql = "SELECT p.rowid, p.label, p.description, p.ref, d.price, d.tva_tx, d.qty, d.remise_percent, d.subprice";
	      $sql .= " FROM llx_propaldet as d, llx_product as p";
	      $sql .= " WHERE d.fk_propal = ".$this->id ." AND d.fk_product = p.rowid ORDER by d.rowid ASC";
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $i = 0; 
		  
		  while ($i < $num)
		    {
		      $objp                  = $this->db->fetch_object($i);

		      $ligne                 = new PropaleLigne();
		      $ligne->libelle        = stripslashes($objp->label);
		      $ligne->desc           = stripslashes($objp->description);
		      $ligne->qty            = $objp->qty;
		      $ligne->ref            = $objp->ref;
		      $ligne->tva_tx         = $objp->tva_tx;
		      $ligne->subprice       = $objp->subprice;
		      $ligne->remise_percent = $objp->remise_percent;
		      $ligne->price          = $objp->price;
		      $ligne->product_id     = $objp->rowid;

		      $this->lignes[$i]      = $ligne;
		      $i++;
		    }
		  $this->db->free();
		} 
	      else
		{
		  print $this->db->error();
		}

	      /*
	       * Lignes génériques
	       */
	      $sql = "SELECT d.qty, d.description, d.price, d.tva_tx, d.rowid, d.remise_percent";
	      $sql .= " FROM llx_propaldet as d";
	      $sql .= " WHERE d.fk_propal = ".$this->id ." AND d.fk_product = 0";
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $j = 0; 
		  
		  while ($j < $num)
		    {
		      $objp                  = $this->db->fetch_object($i);
		      $ligne                 = new PropaleLigne();
		      $ligne->libelle        = stripslashes($objp->description);
		      $ligne->desc           = stripslashes($objp->description);
		      $ligne->qty            = $objp->qty;
		      $ligne->ref            = $objp->ref;
		      $ligne->tva_tx         = $objp->tva_tx;
		      $ligne->subprice       = $objp->subprice;
		      $ligne->remise_percent = $objp->remise_percent;
		      $ligne->price          = $objp->price;
		      $ligne->product_id     = 0;

		      $this->lignes[$i]      = $ligne;
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
  Function set_remise($user, $remise)
    {
      if ($user->rights->propale->creer)
	{

	  $remise = ereg_replace(",",".",$remise);

	  $sql = "UPDATE llx_propal SET remise_percent = ".$remise;
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      $this->remise_percent = $remise;
	      $this->update_price();
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
  Function set_pdf_model($user, $modelpdf)
    {
      if ($user->rights->propale->creer)
	{

	  $sql = "UPDATE llx_propal SET model_pdf = '$modelpdf'";
	  $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
	  
	  if ($this->db->query($sql) )
	    {
	      return 1;
	    }
	  else
	    {
	      print $this->db->error() . ' in ' . $sql;
	      return 0;
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
  /*
   *
   *
   */
  Function delete()
  {
    $sql = "DELETE FROM llx_propaldet WHERE fk_propal = $this->id ;";
    if ( $this->db->query($sql) ) 
      {
	$sql = "DELETE FROM llx_propal WHERE rowid = $this->id;";
	if ( $this->db->query($sql) ) 
	  {
	    return 1;
	  }
	else
	  {
	    return -2;
	  }
      }
    else
      {
	return -1;
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
