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
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");

class PaiementFourn
{
  var $id;
  var $db;
  var $facid;
  var $facnumber;
  var $datepaye;
  var $amount;
  var $accountid;
  var $author;
  var $paiementid;		// Cette variable contient le type de paiement, 7 pour CHQ, etc... (nom pas tres bien choisi)
  var $num_paiement;
  var $note;
  var $societe;
  /*
   *
   *
   *
   */
  function PaiementFourn($DB) 
  {
    $this->db = $DB ;
  }
  /*
   *
   *
   *
   */
  function create($user) 
  {
    /*
     *  Insertion dans la base
     */

    $this->amount = ereg_replace(",",".",$this->amount);
    
    $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiementfourn (fk_facture_fourn, datec, datep, amount, fk_user_author, fk_paiement, num_paiement, note)";
    $sql .= " VALUES ('$this->facid', now(), '$this->datepaye', '$this->amount', '$user->id', '$this->paiementid', '$this->num_paiement', '$this->note')";
    
    $result = $this->db->query($sql);

    if (isset($result))
      {
	$this->id = $this->db->last_insert_id();
	
	$label = "Règlement facture $this->facnumber - $this->societe";
	
	$account = new Account($this->db, $this->accountid);

	$result = $account->addline($this->datepaye, 
				    $this->paiementid, 
				    $label,
				    -$this->amount,
				    $this->num_paiement);

	
	// Mise a jour fk_bank dans llx_paiement_fourn
	if ($result)
	  {   
	    $this->bankid = $this->db->last_insert_id();
	  
	    $sql = "UPDATE ".MAIN_DB_PREFIX."paiementfourn SET fk_bank=$this->bankid WHERE rowid=$this->id";
	    $result = $this->db->query($sql);
	  }	
      }
    else
      {
	print "$sql";
      }  
    
    return 1;
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
  function delete($id) 
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."paiementfourn WHERE rowid = $id";
    
    return $this->db->query($sql);
  }

}
?>
