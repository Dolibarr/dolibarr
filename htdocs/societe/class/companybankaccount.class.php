<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 		\file		htdocs/societe/class/companybankaccount.class.php
 *		\ingroup    societe
 *		\brief      File of class to manage bank accounts description of third parties
 */

require_once(DOL_DOCUMENT_ROOT ."/compta/bank/class/account.class.php");


/**
 * 		\brief	Class to manage bank accounts description of third parties
 */
class CompanyBankAccount extends Account
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
	 *  Constructor
	 *
	 *  @param      DoliDB		$DB      Database handler
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


    /**
     * Create bank information record
     */
    function create()
    {
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_rib (fk_soc, datec) values ($this->socid, ".$this->db->idate(mktime()).")";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
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

    /**
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
            if ($this->db->num_rows($result) == 0)
            {
                $this->create();
            }
        }
        else
        {
            dol_print_error($this->db);
            return 0;
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET ";
        $sql .= " bank = '" .$this->db->escape($this->bank)."'";
        $sql .= ",code_banque='".$this->code_banque."'";
        $sql .= ",code_guichet='".$this->code_guichet."'";
        $sql .= ",number='".$this->number."'";
        $sql .= ",cle_rib='".$this->cle_rib."'";
        $sql .= ",bic='".$this->bic."'";
        $sql .= ",iban_prefix = '".$this->iban_prefix."'";
        $sql .= ",domiciliation='".$this->db->escape($this->domiciliation)."'";
        $sql .= ",proprio = '".$this->db->escape($this->proprio)."'";
        $sql .= ",adresse_proprio = '".$this->db->escape($this->adresse_proprio)."'";
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

    /**
     * 	Load record from database
     *	@param		id			Id of record
     * 	@param		socid		Id of company
     */
    function fetch($id,$socid=0)
    {
        if (empty($id) && empty($socid)) return -1;

        $sql = "SELECT rowid, fk_soc, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation, proprio, adresse_proprio";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe_rib";
        if ($id)    $sql.= " WHERE rowid = ".$id;
        if ($socid) $sql.= " WHERE fk_soc  = ".$socid;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id			   = $obj->rowid;
                $this->socid           = $obj->fk_soc;
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
            $this->db->free($resql);

            return 1;
        }
        else
        {
            dol_print_error($this->db);
            return -1;
        }
    }

}

?>
