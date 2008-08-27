<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

class CompanyBankAccount
{
	var $rowid;
	var $socid;
		
	var $bank;
	var $courant;
	var $clos;
	var $code_banque;
	var $code_guichet;
	var $number;
	var $cle_rib;
	var $bic;
	var $iban_prefix;
	var $proprio;
	var $adresse_proprio;
	
	function CompanyBankAccount($DB, $socid)
	{
    global $config;
    
    $this->db = $DB;
    $this->socid = $socid;

    $this->clos = 0;
    $this->solde = 0;
    $this->error_number = 0;
    return 1;
  }


  /*
   * Creation du compte bancaire
   *
   */
  function create()
    {
      $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_rib (fk_soc, datec) values ($this->socid, ".$this->db->idate(mktime()).")";
      if ($this->db->query($sql))
	{
	  if ($this->db->affected_rows()) 
	    {
	      return 1;
	    }
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
  function update($user='')
  {      
    
    $sql = "SELECT fk_soc FROM ".MAIN_DB_PREFIX."societe_rib ";
    $sql .= " WHERE fk_soc = ".$this->socid;

    $result = $this->db->query($sql);

    if ($result)
      {
	if ($this->db->num_rows() == 0)
	  {
	    $this->create();
	  }
      }
    else
      {
	print $this->db->error();
	return 0;	
      }

    if (strlen(trim($this->iban_prefix)) == 0)
      {
	// Indispensable de la positionner pour vérifier le RIB
	$this->iban_prefix = 'FR';
      }


    $sql = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET ";
    
    $sql .= " bank = '" .addslashes($this->bank)."'";    
    $sql .= ",code_banque='".$this->code_banque."'";
    $sql .= ",code_guichet='".$this->code_guichet."'";
    $sql .= ",number='".$this->number."'";
    $sql .= ",cle_rib='".$this->cle_rib."'";
    $sql .= ",bic='".$this->bic."'";
    $sql .= ",iban_prefix = '".$this->iban_prefix."'";
    $sql .= ",domiciliation='".addslashes($this->domiciliation)."'";
    $sql .= ",proprio = '".addslashes($this->proprio)."'";
    $sql .= ",adresse_proprio = '".addslashes($this->adresse_proprio)."'";
    
    $sql .= " WHERE fk_soc = ".$this->socid;
    
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
   *
   *
   */
  function fetch()
  {

    $sql = "SELECT rowid, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix, domiciliation, proprio, adresse_proprio FROM ".MAIN_DB_PREFIX."societe_rib";
    $sql .= " WHERE fk_soc  = ".$this->socid;

    $result = $this->db->query($sql);

    if ($result)
      {
	if ($this->db->num_rows())
	  {
	    $obj = $this->db->fetch_object($result);
	    
	    $this->bank            = $obj->bank;
	    $this->courant         = $obj->courant;
	    $this->clos            = $obj->clos;
	    $this->code_banque     = $obj->code_banque;
	    $this->code_guichet    = $obj->code_guichet;
	    $this->number          = $obj->number;
	    $this->cle_rib         = $obj->cle_rib;
	    $this->bic             = $obj->bic;
	    $this->iban_prefix     = $obj->iban_prefix;
	    $this->domiciliation   = $obj->domiciliation;
	    $this->proprio         = $obj->proprio;
	    $this->adresse_proprio = $obj->adresse_proprio;
	  }
	$this->db->free();
      }
    else
      {
	dolibarr_print_error($this->db);
      }
  }

  /*
   *
   *
   */
  function error()
    {      
      return $this->error;
    }

  /*
   *
   *
   */
  function verif()
  {
    require_once DOL_DOCUMENT_ROOT . '/lib/bank.lib.php';


    if (strlen(trim($this->code_banque)) == 0)
      {
	$this->error_number = 32;
	$this->error_message = "Le code banque n'est pas renseigné";
      }

    if (strlen(trim($this->code_guichet)) == 0)
      {
	$this->error_number = 33;
	$this->error_message = "Le code guichet n'est pas renseigné";
      }


    if (strlen(trim($this->number)) == 0)
      {
	$this->error_number = 34;
	$this->error_message = "Le numéro de compte n'est pas renseigné";
      }

    if (strlen(trim($this->cle_rib)) == 0)
      {
	$this->error_number = 35;
	$this->error_message = "La clé n'est pas renseignée";
      }

    if (strlen(trim($this->iban_prefix)) == 0)
      {
	$this->error_number = 36;
	$this->error_message = "La cle IBAN n'est pas renseignée";
      }


    if (! verif_rib($this->code_banque, $this->code_guichet, $this->number, $this->cle_rib, $this->iban_prefix))
      {
	$this->error_number = 12;
	$this->error_message = "Le RIB n'est pas valide";
      }
    
    if ($this->error_number == 0)
      {
	return 1;
      }
    else
      {
	return 0;
      }
  }
}

?>
