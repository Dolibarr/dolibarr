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

class Don
{
  var $id;
  var $db;
  var $amount;
  var $nom;
  var $adresse;
  var $cp;
  var $ville;
  var $date;
  var $pays;
  var $public;
  var $projetid;
  var $modepaiement;
  var $note;
  var $statut;

  var $projet;
  /*
   * Statut du don
   * 0 : promesse non validée
   * 1 : promesse validée
   * 2 : don validé
   * 3 : don payé
   *
   *
   */
  Function Don($DB, $soc_idp="") 
    {
      $this->db = $DB ;
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

      $sql = "INSERT INTO llx_don (datec, amount, fk_paiement, nom, adresse, cp, ville, pays, public, fk_don_projet, note, fk_user_author, datedon)";
      $sql .= " VALUES (now(), $this->amount, $this->modepaiement,'$this->nom','$this->adresse', '$this->cp','$this->ville','$this->pays',$this->public, $this->projetid, '$this->note', $userid, '$this->date')";
      
      $result = $this->db->query($sql);
      
      if ($result) 
	{
	  return 1;
	}
      else
	{
	  print $this->db->error();
	  print "<h2><br>$sql<br></h2>";
	  return 0;
	}  
    }

  /*
   * Suppression du don
   *
   */
  Function delete($rowid)
  {

    $sql = "DELETE FROM llx_don WHERE rowid = $rowid AND fk_statut = 0;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
	print "Err : ".$this->db->error();
	return 0;
      }    
  }
  /*
   * Fetch
   *
   *
   */
  Function fetch($rowid)
  {
    $sql = "SELECT d.rowid, ".$this->db->pdate("d.datedon")." as datedon, d.nom, d.amount, p.libelle as projet, d.fk_statut, d.adresse, d.cp, d.ville, d.public, d.amount, d.fk_paiement";
    $sql .= " FROM llx_don as d, llx_don_projet as p";
    $sql .= " WHERE p.rowid = d.fk_don_projet AND d.rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ($this->db->num_rows())
	  {

	    $obj = $this->db->fetch_object(0);

	    $this->date       = $obj->datedon;
	    $this->nom        = $obj->nom;
	    $this->statut     = $obj->fk_statut;
	    $this->adresse    = $obj->adresse;
	    $this->cp         = $obj->cp;
	    $this->ville      = $obj->ville;
	    $this->projet     = $obj->projet;
	    $this->public     = $obj->public;
	    $this->modepaiement = $obj->modepaiement;
	    $this->amount     = $obj->amount;
	  }
      }
    else
      {
	print $this->db->error();
      }
    
  }
  /*
   * Suppression du don
   *
   */
  Function valid_promesse($rowid, $userid)
  {

    $sql = "UPDATE llx_don SET fk_statut = 1, fk_user_valid = $userid WHERE rowid = $rowid AND fk_statut = 0;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
	print "Err : ".$this->db->error();
	return 0;
      }    
  }
  /*
   * Classé comme payé, le don a été recu
   *
   */
  Function set_paye($rowid)
  {

    $sql = "UPDATE llx_don SET fk_statut = 2 WHERE rowid = $rowid AND fk_statut = 1;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
	    return 0;
	  }
      }
    else
      {
	print "Err : ".$this->db->error();
	return 0;
      }    
  }
  /*
   * Classé comme encaissé
   *
   */
  Function set_encaisse($rowid)
  {

    $sql = "UPDATE llx_don SET fk_statut = 3 WHERE rowid = $rowid AND fk_statut = 2;";

    if ( $this->db->query( $sql) )
      {
	if ( $this->db->affected_rows() )
	  {
	    return 1;
	  }
	else
	  {
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
?>
