<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \file       htdocs/paiement.class.php
        \ingroup    facture
        \brief      Fichier de la classe des paiement de factures clients
        \version    $Revision$
*/


/**     \class      Paiement
  	    \brief      Classe permettant la gestion des paiements des factures clients
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
  var $num_paiement;	// Numéro du CHQ, VIR, etc...
  var $bank_account;    // Id compte bancaire du paiement
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
   *    \brief      Récupère l'objet paiement
   *    \param      id       id du paiement a récupérer
   */
	 
  function fetch($id) 
    {
      $sql = "SELECT p.rowid,".$this->db->pdate("p.datep")." as dp, p.amount, p.statut";
      $sql .=", c.libelle as paiement_type";
      $sql .= ", p.num_paiement, p.note, b.fk_account";
      $sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as c ";
      $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid ";
      $sql .= " WHERE p.fk_paiement = c.id";
      $sql .= " AND p.rowid = ".$id;

      if ($this->db->query($sql)) 
	{
	  if ($this->db->num_rows()) 
	    {
	      $obj = $this->db->fetch_object();

	      $this->id             = $obj->rowid;
	      $this->date           = $obj->dp;
	      $this->numero         = $obj->num_paiement;
	      $this->bank_account   = $obj->fk_account;

	      $this->montant        = $obj->amount;
	      $this->note           = $obj->note;
	      $this->type_libelle   = $obj->paiement_type;
	      $this->statut         = $obj->statut;

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
   *    \param      no_commit  le begin et le commit sont fait par l'appelant
   *
   */
	 
  function create($user, $no_commit = 0)
  {
    $sql_err = 0;
    /*
     *  Insertion dans la base
     */
    if ($no_commit == 0)
      {
	$result = $this->db->begin();
      }
    else
      {
	$result = 1;
      }

    if ($result)
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

	if ($total <> 0) /* On accepte les montants négatifs pour les rejets de prélèvement */
	  {
	    $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement (datec, datep, amount, fk_paiement, num_paiement, note, fk_user_creat)";
	    $sql .= " VALUES (now(), $this->datepaye, '$total', $this->paiementid, '$this->num_paiement', '$this->note', $user->id)";

	    if ( $this->db->query($sql) )
	      {

		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."paiement");

		foreach ($this->amounts as $key => $value)
		  {
		    $facid = $key;
		    $value = trim($value);
		    $amount = ereg_replace(",",".",round($value, 2));
		    
		    if (is_numeric($amount) && $amount <> 0)
		      {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, fk_paiement, amount)";
			$sql .= " VALUES ('".$facid."','". $this->id."','". $amount."')";

			if (! $this->db->query($sql) )
			  {
			    dolibarr_syslog("Paiement::Create Erreur INSERT dans paiement_facture ".$facid);
		    
			    $sql_err++;
			  }
		      }
		    else
		      {
			dolibarr_syslog("Paiement::Create Montant non numérique");
		      }
		  }
	      
	      }
	    else
	      {
		dolibarr_syslog("Paiement::Create Erreur INSERT dans paiement");
		$sql_err++;
	      }
	  }

	if ( $total <> 0 && $sql_err == 0 ) // On accepte les montants négatifs
	  {
	    if ($no_commit == 0)
	      {
		$this->db->commit();
	      }
	    dolibarr_syslog("Paiement::Create Ok Total = $total");
	    return $this->id;
	  }
	else
	  {
	    if ($no_commit == 0)
	      {
		$this->db->rollback();
	      }
	    dolibarr_syslog("Paiement::Create Erreur");
	    return -1;
	  }
	
      }
  }

  /**
   *    \brief      Affiche la liste des modes de paiement possible
   *    \param      name        nom du champ select
   *    \param      filtre      filtre sur un sens de paiement particulier, norme ISO (CRDT=Mode propre à un crédit, DBIT=mode propre à un débit)
   *    \param      id          ???
   */
  function select($name, $filtre='', $id='')
  {
    $form = new Form($this->db);

    if ($filtre == 'CRDT')
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE type IN (0,2) ORDER BY libelle";
      }
    elseif ($filtre == 'DBIT')
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement WHERE type IN (1,2) ORDER BY libelle";
      }
    else
      {
	$sql = "SELECT id, libelle FROM ".MAIN_DB_PREFIX."c_paiement ORDER BY libelle";
      }
    $form->select($name, $sql, $id);
  }

  /**
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

  /**
   *    \brief      Valide le paiement
   */
  function valide()
    {
    $sql = "UPDATE ".MAIN_DB_PREFIX."paiement SET statut = 1 WHERE rowid = ".$this->id;
    $result = $this->db->query($sql);

    if ($result) 
      {	    
	return 0;
      }
    else
      {
	dolibarr_syslog("Paiement::Valide Error -1");
    	return -1;
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
