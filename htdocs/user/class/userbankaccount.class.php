<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013   	Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2015	    Alexandre Spangaro	    <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * 		\file		htdocs/user/class/userbankaccount.class.php
 *		\ingroup    user
 *		\brief      File of class to manage bank accounts description of users
 */

require_once DOL_DOCUMENT_ROOT .'/compta/bank/class/account.class.php';


/**
 * 	Class to manage bank accounts description of third parties
 */
class UserBankAccount extends Account
{
    var $socid;

    var $datec;
    var $datem;


    /**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;

        $this->socid = 0;
        $this->solde = 0;
        $this->error_number = 0;
    }


    /**
     * Create bank information record
     *
     * @param	User	$user		User
     * @param	int		$notrigger	1=Disable triggers
     * @return	int					<0 if KO, >= 0 if OK
     */
    function create(User $user=null, $notrigger=0)
    {
        $now=dol_now();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rib (fk_user, datec)";
        $sql.= " VALUES (".$this->userid.", '".$this->db->idate($now)."')";
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->affected_rows($resql))
            {
                $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."user_rib");

                return $this->update($user);
            }
        }
        else
        {
            print $this->db->error();
            return 0;
        }
    }

    /**
     *	Update bank account
     *
     *	@param	User	$user		Object user
     *	@param	int		$notrigger	1=Disable triggers
     *	@return	int					<=0 if KO, >0 if OK
     */
    function update(User $user=null, $notrigger=0)
    {
    	global $conf;

        if (! $this->id)
        {
            $this->create();
        }

        $sql = "UPDATE ".MAIN_DB_PREFIX."user_rib SET";
        $sql.= " bank = '" .$this->db->escape($this->bank)."'";
        $sql.= ",code_banque='".$this->db->escape($this->code_banque)."'";
        $sql.= ",code_guichet='".$this->db->escape($this->code_guichet)."'";
        $sql.= ",number='".$this->db->escape($this->number)."'";
        $sql.= ",cle_rib='".$this->db->escape($this->cle_rib)."'";
        $sql.= ",bic='".$this->db->escape($this->bic)."'";
        $sql.= ",iban_prefix = '".$this->db->escape($this->iban)."'";
        $sql.= ",domiciliation='".$this->db->escape($this->domiciliation)."'";
        $sql.= ",proprio = '".$this->db->escape($this->proprio)."'";
        $sql.= ",owner_address = '".$this->db->escape($this->owner_address)."'";

	    if (trim($this->label) != '')
            $sql.= ",label = '".$this->db->escape($this->label)."'";
        else
            $sql.= ",label = NULL";
        $sql.= " WHERE rowid = ".$this->id;

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
     *
     *	@param	int		$id			Id of record
     *	@param	string	$ref		Ref of record
     *  @param  int     $userid     User id
     * 	@return	int					<0 if KO, >0 if OK
     */
    function fetch($id, $ref='', $userid=0)
    {
        if (empty($id) && empty($ref) && empty($userid)) return -1;

        $sql = "SELECT rowid, fk_user, entity, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation, proprio,";
        $sql.= " owner_address, label, datec, tms as datem";
        $sql.= " FROM ".MAIN_DB_PREFIX."user_rib";
        if ($id) $sql.= " WHERE rowid = ".$id;
        if ($ref) $sql.= " WHERE label = '".$this->db->escape($ref)."'";
        if ($userid) $sql.= " WHERE fk_user = '".$userid."'";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id			   = $obj->rowid;
                $this->socid           = $obj->fk_soc;
                $this->bank            = $obj->bank;
                $this->code_banque     = $obj->code_banque;
                $this->code_guichet    = $obj->code_guichet;
                $this->number          = $obj->number;
                $this->cle_rib         = $obj->cle_rib;
                $this->bic             = $obj->bic;
                $this->iban		       = $obj->iban;
                $this->domiciliation   = $obj->domiciliation;
                $this->proprio         = $obj->proprio;
                $this->owner_address   = $obj->owner_address;
                $this->label           = $obj->label;
                $this->datec           = $this->db->jdate($obj->datec);
                $this->datem           = $this->db->jdate($obj->datem);
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

	/**
	 * Return RIB
	 *
	 * @param   boolean     $displayriblabel     Prepend or Hide Label
	 * @return	string		RIB
	 */
	public function getRibLabel($displayriblabel = true)
	{
		$rib = '';

		if ($this->code_banque || $this->code_guichet || $this->number || $this->cle_rib) {

			if ($this->label && $displayriblabel) {
				$rib = $this->label." : ";
			}

			$rib .= (string) $this;
		}

		return $rib;
	}
}

