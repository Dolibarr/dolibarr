<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

class FactureFourn
{
  var $id;
  var $db;
  var $socid;
  var $number;
  var $author;
  var $libelle;
  var $date;
  var $ref;
  var $amount;
  var $remise;
  var $tva;
  var $total_ht;
  var $total_tva;
  var $total_ttc;
  var $note;
  var $db_table;
  var $propalid;
  var $lignes;

  /*
   * Initialisation
   *
   */
  Function FactureFourn($DB, $soc_idp="", $facid="")
  {
    $this->db = $DB ;
    $this->socidp = $soc_idp;
    $this->products = array();
    $this->db_table = MAIN_DB_PREFIX."facture";
    $this->amount = 0;
    $this->remise = 0;
    $this->tva = 0;
    $this->total = 0;
    $this->propalid = 0;
    $this->id = $facid;

    $this->lignes = array();
  }
  /*
   *
   *
   *
   */
  Function add_ligne($label, $amount, $tauxtva, $qty=1, $write=0)
  {
    $i = sizeof($this->lignes);

    $this->lignes[$i][0] = $label;
    $this->lignes[$i][1] = $amount;
    $this->lignes[$i][2] = $tauxtva;
    $this->lignes[$i][3] = $qty;

    if ($write)
      {

	for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
	  {	 

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn_det (fk_facture_fourn)";
	    $sql .= " VALUES ($this->id);";
	    if ($this->db->query($sql) ) 
	      {
		$idligne = $this->db->last_insert_id();

		$this->update_ligne($idligne,
				    $this->lignes[$i][0],
				    $this->lignes[$i][1],
				    $this->lignes[$i][2],
				    $this->lignes[$i][3]);
	      }
	    else
	      {
		print $this->db->error();
	      }
	  }
	/*
	 * Mise à jour prix
	 */

	$this->updateprice($this->id);
      }
  }
  /*
   *
   */
  Function update_ligne($id, $label, $puht, $tauxtva, $qty=1)
  {

    $puht = ereg_replace(",",".",$puht);

    $totalht  = $puht * $qty;
    $tva      = tva($totalht, $tauxtva);
    $totalttc = $totalht + $tva;


    $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn_det ";
    $sql .= "SET description ='".$label."'";
    $sql .= ", pu_ht = " . $puht;
    $sql .= ", qty =".$qty;
    $sql .= ", total_ht=".$totalht;
    $sql .= ", tva=".$tva;
    $sql .= ", tva_taux=".$tauxtva;
    $sql .= ", total_ttc=".$totalttc;

    $sql .= " WHERE rowid = $id";

    if (! $this->db->query($sql) ) 
      {
	print $this->db->error() . '<b><br>'.$sql;
      }
  }
  /*
   *
   */
  Function delete_ligne($id)
  {

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_det ";
    $sql .= " WHERE rowid = $id";

    if (! $this->db->query($sql) ) 
      {
	print $this->db->error() . '<b><br>'.$sql;
      }
    $this->updateprice($this->id);
    return 1;
  }

  /*
   * Création d'une facture fournisseur
   *
   */
  Function create($user)
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

    $totalht = ($amount - $remise);
    $tva = tva($totalht);
    $total = $totalht + $tva;
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn (facnumber, libelle, fk_soc, datec, datef, note, fk_user_author) ";
    $sql .= " VALUES ('".$this->number."','".$this->libelle."',". $this->socid.", now(),".$this->date.",'".$this->note."', ".$user->id.");";

    if ( $this->db->query($sql) )
      {
	$this->id = $this->db->last_insert_id();

	for ($i = 0 ; $i < sizeof($this->lignes) ; $i++)
	  {	 

	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn_det (fk_facture_fourn)";
	    $sql .= " VALUES ($this->id);";
	    if ($this->db->query($sql) ) 
	      {
		$idligne = $this->db->last_insert_id();

		$this->update_ligne($idligne,
				    $this->lignes[$i][0],
				    $this->lignes[$i][1],
				    $this->lignes[$i][2],
				    $this->lignes[$i][3]);
	      }
	  }
	/*
	 * Mise à jour prix
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

  /*
   *
   *
   *
   */
  Function fetch($rowid)
    {
      $sql = "SELECT fk_soc,libelle,facnumber,amount,remise,".$this->db->pdate(datef)."as df";
      $sql .= ", total_ht, total_tva, total_ttc, fk_user_author";
      $sql .= ", fk_statut, paye";
      $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f WHERE f.rowid=$rowid;";
      
      if ($this->db->query($sql) )
	{
	  if ($this->db->num_rows())
	    {
	      $obj = $this->db->fetch_object(0);
	      
	      $this->id      = $rowid;
	      $this->datep   = $obj->dp;
	      $this->ref     = $obj->facnumber;
	      $this->libelle = $obj->libelle;

	      $this->remise = $obj->remise;
	      $this->socidp = $obj->fk_soc;

	      $this->total_ht  = $obj->total_ht;
	      $this->total_tva = $obj->total_tva;
	      $this->total_ttc = $obj->total_ttc;

	      $this->author    = $obj->fk_user_author;

	      $this->statut = $obj->fk_statut;
	      $this->paye   = $obj->paye;

	      $this->db->free();

	      /* 
	       * Lignes
	       */
	      $sql = "SELECT rowid,description, pu_ht, qty, tva_taux, tva, total_ht, total_ttc FROM ".MAIN_DB_PREFIX."facture_fourn_det WHERE fk_facture_fourn=".$this->id;
      
	      if ($this->db->query($sql) )
		{
		  $num = $this->db->num_rows();
		  $i = 0;
		  if ($num)
		    {
		      while ($i < $num)
			{
			  $obj = $this->db->fetch_object($i);
			  $this->lignes[$i][0] = stripslashes($obj->description);
			  $this->lignes[$i][1] = $obj->pu_ht;
			  $this->lignes[$i][2] = $obj->tva_taux;
			  $this->lignes[$i][3] = $obj->qty;
			  $this->lignes[$i][4] = $obj->total_ht;
			  $this->lignes[$i][5] = $obj->tva;
			  $this->lignes[$i][6] = $obj->total_ttc;
			  $this->lignes[$i][7] = $obj->rowid;
			  $i++;
			}
		    }
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
   * Suppression de la facture
   *
   */
  Function delete($rowid)
    {

      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn WHERE rowid = $rowid AND fk_statut = 0";

      if ( $this->db->query( $sql) )
	{
	  if ( $this->db->affected_rows() )
	    {
	      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_det WHERE fk_facture_fourn = $rowid;";

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

  /*
   * Passe une facture fournisseur a l'état validé
   *
   */
  Function set_valid($userid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn set fk_statut = 1, fk_user_valid = $userid WHERE rowid = ".$this->id;
      $result = $this->db->query( $sql);
      if (! $result) {
        print "Erreur : $sql : ".$this->db->error(); 
      }
    }

  /*
   * Passe une facture fournisseur a l'état payé
   *
   */
  Function set_payed($userid)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn set paye = 1 WHERE rowid = ".$this->id;
      $result = $this->db->query( $sql);
      if (! $result) {
        print "Erreur : $sql : ".$this->db->error(); 
      }
    }

  /*
   *
   *
   */
  Function addline($facid, $desc, $pu, $qty)
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn_det (fk_facture,description,price,qty) VALUES ($facid, '$desc', $pu, $qty) ;";
      $result = $this->db->query( $sql);

      $this->updateprice($facid);
    }
  /*
   *
   *
   */
  Function updateline($rowid, $desc, $pu, $qty)
    {
      $sql = "UPDATE ".MAIN_DB_PREFIX."facture-fourn_det set description='$desc',price=$pu,qty=$qty WHERE rowid = $rowid ;";
      $result = $this->db->query( $sql);

      $this->updateprice($this->id);
    }
  /*
   *
   *
   */
  Function deleteline($rowid)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_det WHERE rowid = $rowid;";
      $result = $this->db->query( $sql);

      $this->updateprice($this->id);
    }
  /*
   *
   *
   */
  Function updateprice($facid)
    {

      $sql = "SELECT sum(total_ht), sum(tva), sum(total_ttc) FROM ".MAIN_DB_PREFIX."facture_fourn_det";
      $sql .= " WHERE fk_facture_fourn = $facid;";
  
      $result = $this->db->query($sql);

      if ($result)
	{
	  if ($this->db->num_rows() )
	    {
	      $row = $this->db->fetch_row();
	      $total_ht  = $row[0];
	      $total_tva = $row[1];
	      $total_ttc = $row[2];
	    }
	  
	  $sql = "UPDATE ".MAIN_DB_PREFIX."facture_fourn SET total_ht = $total_ht, total_tva = $total_tva, total_ttc = $total_ttc";
	  $sql .= " WHERE rowid = $facid ;";
	  
	  $result = $this->db->query($sql);
	  
	}
      else 
	{
	  print $this->db->error();
	}
    }

  /*
   *
   * Génération du PDF
   *
   */
  Function pdf()
    {

    }
  
}
?>
