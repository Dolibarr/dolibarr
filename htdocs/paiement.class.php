<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

class Paiement 
{
  var $id;
  var $db;
  var $facid;
  var $datepaye;
  var $amount;
  var $author;
  var $paiementid; // numero du paiement dans le cas ou une facture paye +ieur fois
  var $num_paiement;
  var $note;
  /*
   *
   *
   *
   */
  Function Paiement($DB, $soc_idp="") 
  {
    $this->db = $DB ;
  }
  /*
   *
   *
   */
  Function fetch($id) 
    {
      $sql = "SELECT p.rowid,".$this->db->pdate("p.datep")." as dp, p.amount";
      $sql .=", c.libelle as paiement_type, p.num_paiement";
      $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as c";
      $sql .= " WHERE p.fk_paiement = c.id";
      $sql .=" AND p.rowid = ".$id;      

      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object($result , 0);

	      $this->id             = $obj->rowid;
	      $this->date           = $obj->dp;
	      $this->numero         = $obj->num_paiement;
	      $this->montant        = $obj->amount;
	      $this->note           = $obj->note;
	      $this->type_libelle   = $obj->paiement_type;

	      return 1;
	    }
	  else
	    {
	      return 0;
	    }
	  $this->db->free();
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
   */
  Function create($user)
  {
    $sql_err = 0;
    /*
     *  Insertion dans la base
     */
    if ($this->db->begin())
      {
	$total = 0;
	foreach ($this->amounts as $key => $value)
	  {
	    $facid = $key;
	    $value = trim($value);
	    $amount = round(ereg_replace(",",".",$value), 2);

	    if (is_numeric($amount))
	      {
		$total += $amount;
	      }
	  }

	if ($total > 0)
	  {
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement (datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)";
	    $sql .= " VALUES (now(), $this->datepaye, $total, $this->paiementid, '$this->num_paiement', '$this->note', $user->id)";

	    if ( $this->db->query($sql) )
	      {

		$this->id = $this->db->last_insert_id();

		foreach ($this->amounts as $key => $value)
		  {
		    $facid = $key;
		    $value = trim($value);
		    $amount = round(ereg_replace(",",".",$value), 2);
		    
		    if (is_numeric($amount))
		      {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
			$sql .= " VALUES (".$facid.",". $this->id.",". $amount.")";
			if (! $this->db->query($sql) )
			  {
			    $sql_err++;
			  }
		      }
		  }
	      }
	    else
	      {
		$sql_err++;
	      }
		

	    if ( $sql_err == 0 )
	      {
		$this->db->commit();
		return $this->id;
	      }
	    else
	      {
		$this->rollback();
		return -1;
	      }

	  }
      }
  }
  /*
   *
   *
   *
   */
  Function select($name, $filtre='', $id='')
  {
    $form = new Form($this->db);

    if ($filtre == 'crédit')
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE type IN (0,2) ORDER BY libelle";
      }
    elseif ($filtre == 'débit')
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE type IN (1,2) ORDER BY libelle";
      }
    else
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement ORDER BY libelle";
      }
    $form->select($name, $sql, $id);
  }

  /*
   *
   *
   *
   */
  Function delete()
  {
    $sql = "DELETE FROM llx_paiement_facture WHERE fk_paiement = ".$this->id;
    
    $result = $this->db->query($sql);
	
    if ($result) 
      {	    
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."paiement WHERE rowid = ".$this->id;
	
	$result = $this->db->query($sql);
	
	return 1;
      }
    else
      {
	print $this->db->error() ."<br>".$sql;
	return 0;
      }    
  }
  /*
   * Information sur l'objet
   *
   */
  Function info($id) 
    {
      $sql = "SELECT c.rowid, ".$this->db->pdate("datec")." as datec, fk_user_creat, fk_user_modif";
      $sql .= ", ".$this->db->pdate("tms")." as tms";
      $sql .= " FROM ".MAIN_DB_PREFIX."paiement as c";
      $sql .= " WHERE c.rowid = $id";
      
      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object($result , 0);

	      $this->id                = $obj->idp;

	      $cuser = new User($this->db, $obj->fk_user_creat);
	      $cuser->fetch();

	      $this->user_creation     = $cuser;

	      $muser = new User($this->db, $obj->fk_user_modif);
	      $muser->fetch();

	      $this->user_modification = $muser;

	      $this->date_creation     = $obj->datec;
	      $this->date_modification = $obj->tms;

	    }
	  $this->db->free();

	}
      else
	{
	  print $this->db->error();
	}
    }
}
?>
