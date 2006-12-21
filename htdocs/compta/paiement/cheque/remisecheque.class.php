<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/**
   \file       htdocs/compta/paiement/cheque/resimecheque.class.php
   \ingroup    compta
   \brief      Fichier de la classe des bordereau de remise de cheque
   \version    $Revision$
*/

/**
   \class RemiseCheque
   \brief Classe permettant la gestion des remises de cheque
*/

class RemiseCheque
{
  var $db ;
  var $id ;
  var $num;
  var $intitule;
  //! Numero d'erreur Plage 1024-1279
  var $errno;

  /**
   *    \brief  Constructeur de la classe
   *    \param  DB          handler accès base de données
   *    \param  id          id compte (0 par defaut)
   */
	 
  function RemiseCheque($DB)
    {
      $this->db = $DB;
    }  

  /**
     \brief Lecture
     \param id identifiant de ligne
  */
  function Fetch($id)
  {
    $sql = "SELECT bc.rowid, bc.datec, bc.fk_user_author,bc.fk_bank_account,bc.amount,bc.number,bc.statut";
    $sql.= ",".$this->db->pdate("date_bordereau"). " as date_bordereau";
    $sql.=",ba.label as account_label";
    $sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc";
    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON bc.fk_bank_account = ba.rowid";
    $sql.= " WHERE bc.rowid = $id;";

    $resql = $this->db->query($sql);

    if ($resql)
      {
	if ($obj = $this->db->fetch_object($resql) )
	  {
	    $this->id             = $obj->rowid;
	    $this->amount         = $obj->amount;
	    $this->date_bordereau = $obj->date_bordereau;
	    $this->account_id     = $obj->fk_bank_account;
	    $this->account_label  = $obj->account_label;
	    $this->author_id      = $obj->fk_user_author;
	    $this->statut         = $obj->statut;

	    if ($this->statut == 0)
	      {
		$this->number         = "(PROV".$this->id.")";
	      }
	    else
	      {
		$this->number         = $obj->number;
	      }

	  }
	$this->db->free($resql);

	return 0;
      }
    else
      {
	return -1;
      }


  }
  /**
     \brief  Insère la remise en base
     \param  user utilisateur qui effectue l'operation
     \param  account_id Compte bancaire concerne
   */	 
  function Create($user, $account_id)
  {
    $this->errno = 0;
    $this->db->begin();
    $this->id = 0;

    $sql = "INSERT INTO ".MAIN_DB_PREFIX."bordereau_cheque (datec, date_bordereau,fk_user_author,fk_bank_account)";
    $sql .= " VALUES (now(),now(),".$user->id.",".$account_id.")";
		
    $resql = $this->db->query($sql);
    if ( $resql )
      {
	$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bordereau_cheque");
	
	if ($this->id == 0)
	  {
	    $this->errno = -1024;
	    dolibarr_syslog("Remisecheque::Create Erreur Lecture ID ($this->errno)");
	  }

	if ($this->id > 0 && $this->errno === 0)
	  {
	    $sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
	    $sql.= " SET number='(PROV".$this->id.")'";
	    $sql.= " WHERE rowid='".$this->id."';";
	    $resql = $this->db->query($sql);	    
	    if (!$resql)
	      {		
		$this->errno = -1025;
		dolibarr_syslog("RemiseCheque::Create ERREUR UPDATE ($this->errno)");
	      }	    
	  }

	if ($this->id > 0 && $this->errno === 0)
	  {
	    $lines = array();
	    $sql = "SELECT b.rowid";
	    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	    $sql.= " WHERE b.fk_type = 'CHQ'";
	    $sql.= " AND b.fk_bordereau = 0 AND b.fk_account='$account_id';";

	    $resql = $this->db->query($sql);

	    if ($resql)
	      {
		while ( $row = $this->db->fetch_row($resql) )
		  {
		    array_push($lines, $row[0]);
		  }
		$this->db->free($resql);
	      }
	    else
	      {
		$this->errno = -1026;
		dolibarr_syslog("RemiseCheque::Create ERREUR SELECT ($this->errno)");
	      }
	  }

	if ($this->id > 0 && $this->errno === 0)
	  {
	    foreach ($lines as $lineid)
	      {
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank as b";
		$sql.= " SET fk_bordereau = ".$this->id;
		$sql.= " WHERE b.rowid = $lineid;";
		
		$resql = $this->db->query($sql);
		if (!$resql)
		  {		
		    $this->errno = -18;
		    dolibarr_syslog("RemiseCheque::Create ERREUR UPDATE ($this->errno)");
		  }
	      }
	  }

	if ($this->id > 0 && $this->errno === 0)
	  {
	    if ($this->UpdateAmount() <> 0)
	      {		
		$this->errno = -1027;
		dolibarr_syslog("RemiseCheque::Create ERREUR ($this->errno)");
	      }
	  }
      }
    else
      {
	$result = -1;
	dolibarr_syslog("RemiseCheque::Create Erreur $result INSERT Mysql");
      }
    

    if ($this->errno === 0)
      {
	$this->db->commit();
      }
    else
      {
	$this->db->rollback();
	dolibarr_syslog("RemiseCheque::Create ROLLBACK ($this->errno)");
      }
    
    return $this->errno;
  }

  /**
     \brief  Supprime la remise en base
     \param  user utilisateur qui effectue l'operation
     \param  account_id Compte bancaire concerne
   */	 
  function Delete()
  {
    $this->errno = 0;
    $this->db->begin();

    $sql = "DELETE FROM ".MAIN_DB_PREFIX."bordereau_cheque";
    $sql .= " WHERE rowid = $this->id;";
		
    $resql = $this->db->query($sql);
    if ( $resql )
      {
	$num = $this->db->affected_rows($resql);
	
	if ($num <> 1)
	  {
	    $this->errno = -2;
	    dolibarr_syslog("Remisecheque::Delete Erreur Lecture ID ($this->errno)");
	  }

	if ( $this->errno === 0)
	  {
	    $sql = "UPDATE ".MAIN_DB_PREFIX."bank";
	    $sql.= " SET fk_bordereau=0";
	    $sql.= " WHERE fk_bordereau='".$this->id."';";
	    $resql = $this->db->query($sql);	    
	    if (!$resql)
	      {		
		$this->errno = -1028;
		dolibarr_syslog("RemiseCheque::Delete ERREUR UPDATE ($this->errno)");
	      }	    
	  }
      }

    if ($this->errno === 0)
      {
	$this->db->commit();
      }
    else
      {
	$this->db->rollback();
	dolibarr_syslog("RemiseCheque::Delete ROLLBACK ($this->errno)");
      }
    
    return $this->errno;
  }
  /**
     \brief  Supprime la remise en base
     \param  user utilisateur qui effectue l'operation
     \param  account_id Compte bancaire concerne
   */	 
  function Validate($user)
  {
    $this->errno = 0;
    $this->db->begin();

    $sql = "SELECT MAX(number) FROM ".MAIN_DB_PREFIX."bordereau_cheque;";

    $resql = $this->db->query($sql);
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);
	$num = $row[0];
	$this->db->free($resql);
      }
    else
      {
	$this->errno = -1034;
	dolibarr_syslog("Remisecheque::Validate Erreur SELECT ($this->errno)");
      }

    if ($this->errno === 0)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
	$sql.= " SET statut=1, number='".($num+1)."'";
	$sql .= " WHERE rowid = $this->id;";
	
	$resql = $this->db->query($sql);
	if ( $resql )
	  {
	    $num = $this->db->affected_rows($resql);
	    
	    if ($num <> 1)
	      {
		$this->errno = -1029;
		dolibarr_syslog("Remisecheque::Validate Erreur UPDATE ($this->errno)");
	      }
	  }
	else
	  {
	    $this->errno = -1033;
	    dolibarr_syslog("Remisecheque::Validate Erreur UPDATE ($this->errno)");
	  }
      }

    if ($this->errno === 0)
      {
	require_once(DOL_DOCUMENT_ROOT ."/compta/paiement/cheque/pdf/pdf_blochet.class.php");
	$pdf = new BordereauChequeBlochet($db);
	$pdf->write_pdf_file(DOL_DATA_ROOT.'/compta/bordereau', ($num+1) );
      }

    if ($this->errno === 0)
      {
	$this->db->commit();
      }
    else
      {
	$this->db->rollback();
	dolibarr_syslog("RemiseCheque::Validate ROLLBACK ($this->errno)");
      }
    
    return $this->errno;
  }

  /**
     \brief  Mets a jour le montant total
     \return int, 0 en cas de succes
   */	 
  function UpdateAmount()
  {
    $this->errno = 0;
    $this->db->begin();

    $sql = "SELECT sum(amount) ";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank";
    $sql.= " WHERE fk_bordereau = $this->id;";
		
    $resql = $this->db->query($sql);
    if ( $resql )
      {
	$row = $this->db->fetch_row($resql);
	$total = $row[0];


	$sql = "UPDATE ".MAIN_DB_PREFIX."bordereau_cheque";
	$sql.= " SET amount='$total'";
	$sql.= " WHERE rowid='".$this->id."';";
	$resql = $this->db->query($sql);	    
	if (!$resql)
	  {		
	    $this->errno = -1030;
	    dolibarr_syslog("RemiseCheque::UpdateAmount ERREUR UPDATE ($this->errno)");
	  }	    
      }
    else
      {		
	$this->errno = -1031;
	dolibarr_syslog("RemiseCheque::UpdateAmount ERREUR SELECT ($this->errno)");
      }	    

    if ($this->errno === 0)
      {
	$this->db->commit();
      }
    else
      {
	$this->db->rollback();
	dolibarr_syslog("RemiseCheque::UpdateAmount ROLLBACK ($this->errno)");
      }
    
    return $this->errno;
  }

  /**
     \brief  Insère la remise en base
     \param  user utilisateur qui effectue l'operation
     \param  account_id Compte bancaire concerne
   */	 
  function RemoveCheck($account_id)
  {
    $this->errno = 0;

    if ($this->id > 0)
      {
	$sql = "UPDATE ".MAIN_DB_PREFIX."bank";
	$sql.= " SET fk_bordereau = 0 ";
	$sql.= " WHERE rowid = '".$account_id."' AND fk_bordereau='".$this->id."';";
	$resql = $this->db->query($sql);	    
	if (!$resql)
	  {		
	    $this->errno = -1032;
	    dolibarr_syslog("RemiseCheque::RemoveCheck ERREUR UPDATE ($this->errno)");
	  }	    
      }
    return 0;
  }
}
?>
