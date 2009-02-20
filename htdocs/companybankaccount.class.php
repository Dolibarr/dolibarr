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
 */

/*
 * 	\version	$Id$
 */

/**
 * 	\brief	Class
 *
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
	var $iban;
	var $iban_prefix;		// deprecated
	var $proprio;
	var $adresse_proprio;

	/**
	 * 	Constructor
	 */
	function CompanyBankAccount($DB)
	{
		$this->db = $DB;

		$this->socid = 0;
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

		$sql = "SELECT fk_soc FROM ".MAIN_DB_PREFIX."societe_rib";
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
			dol_print_error($this->db);
			return 0;
		}
	}

	/*
	 *
	 *
	 */
	function fetch()
	{

		$sql = "SELECT rowid, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation, proprio, adresse_proprio FROM ".MAIN_DB_PREFIX."societe_rib";
		$sql.= " WHERE fk_soc  = ".$this->socid;

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
				$this->iban		       = $obj->iban;
				$this->iban_prefix     = $obj->iban;	// deprecated
				$this->domiciliation   = $obj->domiciliation;
				$this->proprio         = $obj->proprio;
				$this->adresse_proprio = $obj->adresse_proprio;
			}
			$this->db->free();
		}
		else
		{
			dol_print_error($this->db);
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

	
	/**
	 *
	 *
	 */
	function verif()
	{
		require_once DOL_DOCUMENT_ROOT . '/lib/bank.lib.php';

		// Call function to check BAN
		if (! checkBanForAccount($this))
		{
			$this->error_number = 12;
			$this->error_message = 'RIBControlError';
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
	
	/**
	 * 	\brief		Return account country code
	 *	\return		String		country code
	 */
	function getCountryCode()
	{
		if (! empty($this->iban))
		{
			// If IBAN defined, we can know country of account from it
			if (eregi("^([a-zA-Z][a-zA-Z])",$this->iban,$reg)) return $reg[1];
		}
		
		// We return country code
		$company=new Societe($this->db);
		$result=$company->fetch($this->socid);
		if (! empty($company->pays_code)) return $company->pays_code;

		return '';
	}

	/**
	 * 	\brief		Return if a bank account is defined with detailed information (bank code, desk code, number and key)
	 * 	\return		boolean		true or false
	 */
	function useDetailedBBAN()
	{
		if ($this->getCountryCode() == 'FR') return true;
		if ($this->getCountryCode() == 'ES') return true;
		return false;
	}	
	
}

?>
