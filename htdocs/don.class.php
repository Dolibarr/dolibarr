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
  var $date;
  var $amount;
  var $prenom;
  var $nom;
  var $societe;
  var $adresse;
  var $cp;
  var $ville;
  var $pays;
  var $email;
  var $public;
  var $projetid;
  var $modepaiement;
  var $modepaiementid;
  var $commentaire;
  var $statut;

  var $projet;
  var $errorstr;
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
      $this->modepaiementid = 0;
    }
  /*
   *
   *
   *
   */
  Function print_error_list()
  {
    $num = sizeof($this->errorstr);
    for ($i = 0 ; $i < $num ; $i++)
      {
	print "<li>" . $this->errorstr[$i];
      }
  }

  Function check() 
    {
      $err = 0;

      if (strlen(trim($this->societe)) == 0)
	{
	  if ((strlen(trim($this->nom)) + strlen(trim($this->prenom))) == 0)
	    {
	      $error_string[$err] = "Vous devez saisir vos nom et prénom ou le nom de votre société.";
	      $err++;
	    }
	}

      if (strlen(trim($this->adresse)) == 0)
	{
	  $error_string[$err] = "L'adresse saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->cp)) == 0)
	{
	  $error_string[$err] = "Le code postal saisi est invalide";
	  $err++;
	}

      if (strlen(trim($this->ville)) == 0)
	{
	  $error_string[$err] = "La ville saisie est invalide";
	  $err++;
	}

      if (strlen(trim($this->email)) == 0)
	{
	  $error_string[$err] = "L'email saisi est invalide";
	  $err++;
	}

      $this->amount = trim($this->amount);

      $map = range(0,9);
      for ($i = 0; $i < strlen($this->amount) ; $i++)
	{
	  if (!isset($map[substr($this->amount, $i, 1)] ))
	    {
	      $error_string[$err] = "Le montant du don contient un/des caractère(s) invalide(s)";
	      $err++;
	      break;
	    } 	      
	}

      if ($this->amount == 0)
	{
	  $error_string[$err] = "Le montant du don est null";
	  $err++;
	}

      if ($err)
	{
	  $this->errorstr = $error_string;
	  return 0;
	}
      else
	{
	  return 1;
	}

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

      $this->date = $this->db->idate($this->date);

      $sql = "INSERT INTO llx_don (datec, amount, fk_paiement,prenom, nom, societe,adresse, cp, ville, pays, public, fk_don_projet, note, fk_user_author, datedon, email)";
      $sql .= " VALUES (now(), $this->amount, $this->modepaiementid,'$this->prenom','$this->nom','$this->societe','$this->adresse', '$this->cp','$this->ville','$this->pays',$this->public, $this->projetid, '$this->commentaire', $userid, '$this->date','$this->email')";
      
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
    $sql = "SELECT d.rowid, ".$this->db->pdate("d.datedon")." as datedon, d.prenom, d.nom, d.societe, d.amount, p.libelle as projet, d.fk_statut, d.adresse, d.cp, d.ville, d.pays, d.public, d.amount, d.fk_paiement, d.note, cp.libelle, d.email";
    $sql .= " FROM llx_don as d, llx_don_projet as p, c_paiement as cp";
    $sql .= " WHERE p.rowid = d.fk_don_projet AND cp.id = d.fk_paiement AND d.rowid = $rowid";

    if ( $this->db->query( $sql) )
      {
	if ($this->db->num_rows())
	  {

	    $obj = $this->db->fetch_object(0);

	    $this->date           = $obj->datedon;
	    $this->prenom         = stripslashes($obj->prenom);
	    $this->nom            = stripslashes($obj->nom);
	    $this->societe        = stripslashes($obj->societe);
	    $this->statut         = $obj->fk_statut;
	    $this->adresse        = stripslashes($obj->adresse);
	    $this->cp             = stripslashes($obj->cp);
	    $this->ville          = stripslashes($obj->ville);
	    $this->email          = stripslashes($obj->email);
	    $this->pays           = stripslashes($obj->pays);
	    $this->projet         = $obj->projet;
	    $this->public         = $obj->public;
	    $this->modepaiementid = $obj->fk_paiement;
	    $this->modepaiement   = $obj->libelle;
	    $this->amount         = $obj->amount;
	    $this->commentaire    = stripslashes($obj->note);
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
  Function set_paye($rowid, $modepaiement='')
  {
    $sql = "UPDATE llx_don SET fk_statut = 2";

    if ($modepaiement)
      {
	$sql .= ", fk_paiement=$modepaiement";
      }
    $sql .=  " WHERE rowid = $rowid AND fk_statut = 1;";

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
  /*
   * Somme des dons encaissés
   */
  Function sum_actual()
  {
    $sql = "SELECT sum(amount)";
    $sql .= " FROM llx_don";
    $sql .= " WHERE fk_statut = 3";

    if ( $this->db->query( $sql) )
      {
	$row = $this->db->fetch_row(0);

	return $row[0];

      }
  }
  /* Paiement recu en attente d'encaissement
   * 
   *
   */
  Function sum_pending()
  {
    $sql = "SELECT sum(amount)";
    $sql .= " FROM llx_don";
    $sql .= " WHERE fk_statut = 2";

    if ( $this->db->query( $sql) )
      {
	$row = $this->db->fetch_row(0);

	return $row[0];

      }
  }
  /*
   * Somme des promesses de dons validées
   *
   */
  Function sum_intent()
  {
    $sql = "SELECT sum(amount)";
    $sql .= " FROM llx_don";
    $sql .= " WHERE fk_statut = 1";

    if ( $this->db->query( $sql) )
      {
	$row = $this->db->fetch_row(0);

	return $row[0];

      }
  }
}
?>
