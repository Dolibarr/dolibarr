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

class FactureLigne
{
  Function FactureLigne()
    {
    }
}

class Facture
{
  var $id;
  var $db;
  var $socidp;
  var $number;
  var $author;
  var $date;
  var $ref;
  var $amount;
  var $remise;
  var $tva;
  var $total;
  var $note;
  var $db_table;
  var $propalid;
  var $projetid;
  /*
   * Initialisation
   *
   */

  Function Facture($DB, $soc_idp="", $facid="")
    {
      $this->db = $DB ;
      $this->socidp = $soc_idp;
      $this->products = array();
      $this->db_table = "llx_facture";
      $this->amount = 0;
      $this->remise = 0;
      $this->tva = 0;
      $this->total = 0;
      $this->propalid = 0;
      $this->projetid = 0;
      $this->id = $facid;
  }
  /*
   *
   *
   *
   */

  Function create($userid)
    {
      /*
       *  Insertion dans la base
       */
      $socid = $this->socidp;
      $number = $this->number;
      $amount = $this->amount;
      $remise = $this->remise;
      
      if (! $remise)
	{
	  $remise = 0 ;
	}

      if (! $this->projetid)
	{
	  $this->projetid = "NULL";
	}
      
      $totalht = ($amount - $remise);
      $tva = tva($totalht);
      $total = $totalht + $tva;
      
      $sql = "INSERT INTO $this->db_table (facnumber, fk_soc, datec, amount, remise, tva, total, datef, note, fk_user_author,fk_projet) ";
      $sql .= " VALUES ('$number', $socid, now(), $totalht, $remise, $tva, $total, $this->date,'$note',$userid, $this->projetid);";
      
      if ( $this->db->query($sql) )
	{
	  $this->id = $this->db->last_insert_id();

	  if ($this->id && $this->propalid)
	    {
	      $sql = "INSERT INTO llx_fa_pr (fk_facture, fk_propal) VALUES (".$this->id.",".$this->propalid.")";
	      $this->db->query($sql);
	    }
	  return $this->id;
	}
      else
	{
	  print $this->db->error() . '<b><br>'.$sql;
	  return 0;
	}
      
    }

  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {

      $sql = "SELECT fk_soc,facnumber,amount,tva,total,remise,".$this->db->pdate(datef)."as df,fk_projet";
      $sql .= " FROM llx_facture WHERE rowid=$rowid;";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id        = $rowid;
	      $this->datep     = $obj->dp;
	      $this->date      = $obj->df;
	      $this->ref       = $obj->facnumber;
	      $this->total_ht  = $obj->amount;
	      $this->total_tva = $obj->tva;
	      $this->total_ttc = $obj->total;
	      $this->remise    = $obj->remise;
	      $this->socidp    = $obj->fk_soc;
	      $this->projetid  = $obj->fk_projet;
	      $this->lignes    = array();
	      $this->db->free();

	      /*
	       * Lignes
	       */

	      $sql = "SELECT l.description, l.price, l.qty, l.rowid, l.tva_taux";
	      $sql .= " FROM llx_facturedet as l WHERE l.fk_facture = ".$this->id;
	
	      $result = $this->db->query($sql);
	      if ($result)
		{
		  $num = $this->db->num_rows();
		  $i = 0; $total = 0;
		  
		  while ($i < $num)
		    {
		      $objp = $this->db->fetch_object($i);
		      $faclig = new FactureLigne();
		      $faclig->desc = stripslashes($objp->description);
		      $faclig->qty  = $objp->qty;
		      $faclig->price = $objp->price;
		      $faclig->tva_taux = $objp->tva_taux;
		      $this->lignes[$i] = $faclig;
		      $i++;
		    }
	    
		  $this->db->free();
		} 
	      else
		{
		  print $this->db->error();
		}
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
  Function fetch_client()
    {
      $client = new Societe($this->db);
      $client->fetch($this->socidp);
      $this->client = $client;
	
    }
  /*
   *
   *
   *
   */
  Function valid($userid, $dir)
    {
      $sql = "UPDATE llx_facture SET fk_statut = 1, date_valid=now(), fk_user_valid=$userid";
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

  /*
   * Suppression de la facture
   *
   */
  Function delete($rowid)
    {

      $sql = "DELETE FROM llx_facture WHERE rowid = $rowid AND fk_statut = 0;";

      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->affected_rows() )
	    {
	      $sql = "DELETE FROM llx_fa_pr WHERE fk_facture = $rowid;";

	      if ($this->db->query( $sql) )
		{

		  $sql = "DELETE FROM llx_facturedet WHERE fk_facture = $rowid;";

		  if ($this->db->query( $sql) )
		    {
		      return 1;
		    }
		  else
		    {
		      print "Err : ".$this->db->error();
		      return 0;
		    }
		}
	      else
		{
		  print "Err : ".$this->db->error();
		  return 0;
		}
	    }
	}
      else
	{
	  print "Err : ".$this->db->error();
	  return 0;
	}


    }
  /*
   *
   *
   *
   */
  Function set_payed($rowid)
    {
      $sql = "UPDATE llx_facture set paye = 1 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }

  Function set_valid($rowid, $userid)
    {
      global $conf;

      $sql = "UPDATE llx_facture set fk_statut = 1, fk_user_valid = $userid WHERE rowid = $rowid ;";
      $result = $this->db->query( $sql);

      return $result;
    }
  /*
   *
   *
   */
  Function addline($facid, $desc, $pu, $qty, $txtva, $fk_product='NULL')
    {
      $sql = "INSERT INTO llx_facturedet (fk_facture,description,price,qty,tva_taux, fk_product)";
      $sql .= " VALUES ($facid, '$desc', $pu, $qty, $txtva, $fk_product) ;";

      if ( $this->db->query( $sql) )
	{
	  $this->updateprice($facid);
	}
    }
  /*
   *
   *
   */
  Function updateline($rowid, $desc, $pu, $qty)
    {
      $sql = "UPDATE llx_facturedet set description='$desc',price=$pu,qty=$qty WHERE rowid = $rowid ;";
      $result = $this->db->query( $sql);

      $this->updateprice($this->id);
    }
  /*
   *
   *
   */
  Function deleteline($rowid)
    {
      $sql = "DELETE FROM llx_facturedet WHERE rowid = $rowid;";
      $result = $this->db->query( $sql);

      $this->updateprice($this->id);
    }
  /*
   *
   *
   */
  Function updateprice($facid)
    {

      $sql = "SELECT sum(price*qty) FROM llx_facturedet WHERE fk_facture = $facid;";
  
      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows() )
	    {
	      $row = $this->db->fetch_row();
	      $totalht = $row[0];
	    }

	  $tva = tva($totalht);
	  $total = $totalht + $tva;
	  
	  $sql = "UPDATE llx_facture SET amount = $totalht, tva=$tva, total=$total";
	  $sql .= " WHERE rowid = $facid ;";
	  
	  $result = $this->db->query($sql);
	  
	}
    }

  /*
   *
   * Génération du PDF
   *
   */
  Function pdf()
    {

      
      print "<hr><b>Génération du PDF</b><p>";
      
      $command = "export DBI_DSN=\"".$GLOBALS["DBI"]."\" ";
      $command .= " ; ../../scripts/facture-tex.pl --facture=$facid --pdf --ps"  ;
      
      $output = system($command);
      print "<p>command : $command<br>";
    }
  
}
?>
