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
    $sql = "SELECT rowid, datec, fk_user_author,fk_bank_account,amount,number,statut";
    $sql.= ",".$this->db->pdate("date_bordereau"). " as date_bordereau";
    $sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque ";
    $sql.= " WHERE rowid = $id;";

    $resql = $this->db->query($sql);

    if ($resql)
      {
	if ($obj = $this->db->fetch_object($resql) )
	  {
	    $this->id             = $obj->rowid;
	    $this->number         = $obj->number;
	    $this->amount         = $obj->amount;
	    $this->date_bordereau = $obj->date_bordereau;
	    $this->account_id     = $obj->fk_account_id;
	    $this->author_id      = $obj->fk_user_author;
	    $this->statut         = $obj->statut;
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
  function create($user, $account_id)
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
	    $this->errno = -2;
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
		$this->errno = -19;
		dolibarr_syslog("RemiseCheque::Create ERREUR UPDATE ($this->errno)");
	      }	    
	  }

	if ($this->id > 0 && $this->errno === 0)
	  {
	    $lines = array();
	    $sql = "SELECT b.rowid";
	    $sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	    $sql.= " WHERE b.fk_type = 'CHQ'";
	    $sql.= " AND b.fk_bordereau = 0";

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
		$this->errno = -17;
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
	    $this->errno = -20;
	    dolibarr_syslog("RemiseCheque::Create ERREUR UPDATE ($this->errno)");
	  }	    
      }
    return 0;
  }
}
?>
