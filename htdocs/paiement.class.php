<?php
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

/*!
  \file       htdocs/paiement.class.php
  \ingroup    facture
  \brief      Fichier de la classe des paiement de factures clients
  \version    $Revision$
*/


/*! \class Paiement
  	\brief Classe permettant la gestion des paiements des factures clients
*/

class Paiement 
{
  var $id;
  var $db;
  var $facid;
  var $datepaye;
  var $amount;
  var $author;
  var $paiementid; 		// Type de paiement. Stocké dans fk_paiement 
                                // de llx_paiement qui est lié aux types de 
                                //paiement de llx_c_paiement
  var $num_paiement;	        // Numéro du CHQ, VIR, etc...
  var $note;
  // fk_paiement dans llx_paiement est l'id du type de paiement (7 pour CHQ, ...)
  // fk_paiement dans llx_paiement_facture est le rowid du paiement


  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  soc_idp     id societe ("" par defaut)
   */
	 
  function Paiement($DB, $soc_idp="") 
  {
    $this->db = $DB ;
  }

  /**
   *    \brief      Recupére l'objet paiement
   *    \param      id       id du paiement a récupérer
   */
	 
  function fetch($id) 
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
	      $obj = $this->db->fetch_object();

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
	  dolibarr_print_error($this->db);
	  return 0;
	}
    }

  /**
   *    \brief      Création du paiement en base
   *    \param      user       object utilisateur qui crée
   *
   */
	 
  function create($user)
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
	    $amount = ereg_replace(",",".",round($value, 2));

	    if (is_numeric($amount))
	      {
		$total += $amount;
	      }
	  }
	
	$total = ereg_replace(",",".",$total);

	if ($total > 0)
	  {
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement (datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)";
	    $sql .= " VALUES (now(), $this->datepaye, '$total', $this->paiementid, '$this->num_paiement', '$this->note', $user->id)";

	    if ( $this->db->query($sql) )
	      {

		$this->id = $this->db->last_insert_id();

		foreach ($this->amounts as $key => $value)
		  {
		    $facid = $key;
		    $value = trim($value);
		    $amount = ereg_replace(",",".",round($value, 2));
		    
		    if (is_numeric($amount) && $amount > 0)
		      {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
			$sql .= " VALUES ('".$facid."','". $this->id."','". $amount."')";

			if (! $this->db->query($sql) )
			  {
			    dolibarr_print_error($this->db);
		    
			    $sql_err++;
			  }
		      }
		  }
	      
	      }
	    else
	      {
		dolibarr_print_error($this->db);
		$sql_err++;
	      }
	  }

	if ( $total > 0 && $sql_err == 0 )
	  {
	    $this->db->commit();
	    return $this->id;
	  }
	else
	  {
	    $this->db->rollback();
	    return -1;
	  }
	
      }
  }

  /*
   *
   *
   *
   */
	 
  function select($name, $filtre='', $id='')
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
	 
  function delete()
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
	dolibarr_print_error($this->db);
	return 0;
      }    
  }
  
  /*
   * Mise a jour du lien entre le paiement et la ligne générée dans llx_bank
   *
   */
	 
  function update_fk_bank($id_bank)
    {
    $sql = "UPDATE llx_paiement set fk_bank = ".$id_bank." where rowid = ".$this->id;
    $result = $this->db->query($sql);
    if ($result) 
      {	    
	return 1;
      }
    else
      {
	dolibarr_print_error($this->db);
    	return 0;
      }
    }

  /*
   *    \brief      Information sur l'objet
   *    \param      id      id du paiement dont il faut afficher les infos
   */
	 
  function info($id) 
    {
      $sql = "SELECT c.rowid, ".$this->db->pdate("datec")." as datec, fk_user_creat, fk_user_modif";
      $sql .= ", ".$this->db->pdate("tms")." as tms";
      $sql .= " FROM ".MAIN_DB_PREFIX."paiement as c";
      $sql .= " WHERE c.rowid = $id";
      
      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object();

	      $this->id                = $obj->idp;

	      if ($obj->fk_user_creat) {
	      	$cuser = new User($this->db, $obj->fk_user_creat);
	      	$cuser->fetch();
	      	$this->user_creation     = $cuser;
	      }

		  if ($obj->fk_user_modif) {
	        $muser = new User($this->db, $obj->fk_user_modif);
	        $muser->fetch();
  	        $this->user_modification = $muser;
	      }

	      $this->date_creation     = $obj->datec;
	      $this->date_modification = $obj->tms;

	    }
	  $this->db->free();

	}
      else
	{
	  dolibarr_print_error($this->db);
	}
    }
}
?>
