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

class Facture {
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

  /*
   * Initialisation
   *
   */

  Function Facture($DB, $soc_idp="") {
    $this->db = $DB ;
    $this->socidp = $soc_idp;
    $this->products = array();
    $this->db_table = "llx_facture";
    $this->amount = 0;
    $this->remise = 0;
    $this->tva = 0;
    $this->total = 0;
    $this->propalid = 0;
  }
  /*
   *
   *
   *
   */

  Function create($userid) {
    /*
     *  Insertion dans la base
     */
    $socid = $this->socidp;
    $number = $this->number;
    $amount = $this->amount;
    $remise = $this->remise;

    if (! $remise) {
      $remise = 0 ;
    }

    $totalht = ($amount - $remise);
    $tva = tva($totalht);
    $total = $totalht + $tva;
    
    $sql = "INSERT INTO $this->db_table (facnumber, fk_soc, datec, amount, remise, tva, total, datef, note, fk_user_author) ";
    $sql .= " VALUES ('$number', $socid, now(), $totalht, $remise, $tva, $total, $this->date,'$note',$userid);";
      
    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id();

	$sql = "INSERT INTO llx_fa_pr (fk_facture,fk_propal) VALUES ($this->id, $this->propalid);";
	if ( $this->db->query($sql) ) 
	  {
	    return $this->id;
	  }
	else
	  {
	    print $this->db->error() . '<b><br>'.$sql;
	    return $this->id;
	  }

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
  Function fetch($rowid) {

    $sql = "SELECT ref,price,remise,".$this->db->pdate(datep)."as dp FROM llx_facture WHERE rowid=$rowid;";

    if ($this->db->query($sql) ) {
      if ($this->db->num_rows()) {
	$obj = $this->db->fetch_object(0);

	$this->id = $rowid;
	$this->datep = $obj->dp;
	$this->ref = $obj->ref;
	$this->price = $obj->price;
	$this->remise = $obj->remise;
	
	$this->db->free();
      }
    } else {
      print $this->db->error();
    }    
  }
  /*
   *
   *
   *
   */
  Function valid($userid) {
    $sql = "UPDATE llx_facture SET fk_statut = 1, date_valid=now(), fk_user_valid=$userid";
    $sql .= " WHERE rowid = $this->id AND fk_statut = 0 ;";
    
    if ($this->db->query($sql) ) {
      return 1;
    } else {
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
		  return 1;
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

  Function set_payed($rowid)
    {
      $sql = "UPDATE llx_facture set paye = 1 WHERE rowid = $rowid ;";
      $return = $this->db->query( $sql);
    }

  Function set_valid($rowid, $userid)
    {
      $sql = "UPDATE llx_facture set fk_statut = 1, fk_user_valid = $userid WHERE rowid = $rowid ;";
      $result = $this->db->query( $sql);
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
