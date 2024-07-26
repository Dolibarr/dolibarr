<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013   	Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2015	    Alexandre Spangaro	    <aspangaro@open-dsi.fr>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 		\file		htdocs/user/class/userbankaccount.class.php
 *		\ingroup    user
 *		\brief      File of class to manage bank accounts description of users
 */

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


/**
 * 	Class to manage bank accounts description of users
 */
class UserBankAccount extends Account
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'user_bank_account';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'user_rib';


	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * Date modification record (tms)
	 *
	 * @var integer
	 */
	public $datem;

	/**
	 * User id of bank account
	 *
	 * @var integer
	 */
	public $userid;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->userid = 0;
		$this->solde = 0;
		$this->balance = 0;
	}


	/**
	 * Create bank information record
	 *
	 * @param	User|null	$user		User
	 * @param	int			$notrigger	1=Disable triggers
	 * @return	int						Return integer <0 if KO, >= 0 if OK
	 */
	public function create(User $user = null, $notrigger = 0)
	{
		$now = dol_now();

		$sql = "INSERT INTO ".$this->db->prefix()."user_rib (fk_user, datec)";
		$sql .= " VALUES (".$this->userid.", '".$this->db->idate($now)."')";
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql)) {
				$this->id = $this->db->last_insert_id($this->db->prefix()."user_rib");

				return $this->update($user);
			} else {
				return 0;
			}
		} else {
			print $this->db->error();
			return -1;
		}
	}

	/**
	 *	Update bank account
	 *
	 *	@param	User|null	$user		Object user
	 *	@param	int			$notrigger	1=Disable triggers
	 *	@return	int						Return integer <=0 if KO, >0 if OK
	 */
	public function update(User $user = null, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		if (!$this->id) {
			$this->create();
		}

		$sql = "UPDATE ".$this->db->prefix()."user_rib SET";
		$sql .= " bank = '".$this->db->escape($this->bank)."'";
		$sql .= ",code_banque='".$this->db->escape($this->code_banque)."'";
		$sql .= ",code_guichet='".$this->db->escape($this->code_guichet)."'";
		$sql .= ",number='".$this->db->escape($this->number)."'";
		$sql .= ",cle_rib='".$this->db->escape($this->cle_rib)."'";
		$sql .= ",bic='".$this->db->escape($this->bic)."'";
		$sql .= ",iban_prefix = '".$this->db->escape($this->iban)."'";
		$sql .= ",domiciliation='".$this->db->escape($this->address ? $this->address :$this->domiciliation)."'";
		$sql .= ",proprio = '".$this->db->escape($this->proprio)."'";
		$sql .= ",owner_address = '".$this->db->escape($this->owner_address)."'";
		$sql .= ",currency_code = '".$this->db->escape($this->currency_code)."'";
		$sql .= ",state_id = ".($this->state_id > 0 ? ((int) $this->state_id) : "null");
		$sql .= ",fk_country = ".($this->country_id > 0 ? ((int) $this->country_id) : "null");

		if (trim($this->label) != '') {
			$sql .= ",label = '".$this->db->escape($this->label)."'";
		} else {
			$sql .= ",label = NULL";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if (!$result) {
			$error++;
			$this->errors[] = $this->db->lasterror();
		}

		// Triggers
		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $user);
			if ($result < 0) {
				$error++;
			} //Do also here what you must do to rollback action if trigger fail
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * 	Load record from database
	 *
	 *	@param	int		$id			Id of record
	 *	@param	string	$ref		Ref of record
	 *  @param  int     $userid     User id
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '', $userid = 0)
	{
		if (empty($id) && empty($ref) && empty($userid)) {
			return -1;
		}

		$sql = "SELECT ur.rowid, ur.fk_user, ur.entity, ur.bank, ur.number, ur.code_banque, ur.code_guichet, ur.cle_rib, ur.bic, ur.iban_prefix as iban, ur.domiciliation as address";
		$sql .= ", ur.proprio as owner_name, ur.owner_address, ur.label, ur.datec, ur.tms as datem";
		$sql .= ', ur.currency_code, ur.state_id, ur.fk_country as country_id';
		$sql .= ', c.code as country_code, c.label as country';
		$sql .= ', d.code_departement as state_code, d.nom as state';
		$sql .= " FROM ".$this->db->prefix()."user_rib as ur";
		$sql .= ' LEFT JOIN '.$this->db->prefix().'c_country as c ON ur.fk_country=c.rowid';
		$sql .= ' LEFT JOIN '.$this->db->prefix().'c_departements as d ON ur.state_id=d.rowid';

		if ($id) {
			$sql .= " WHERE ur.rowid = ".((int) $id);
		}
		if ($ref) {
			$sql .= " WHERE ur.label = '".$this->db->escape($ref)."'";
		}
		if ($userid) {
			$sql .= " WHERE ur.fk_user = ".((int) $userid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->userid = $obj->fk_user;
				$this->bank = $obj->bank;
				$this->code_banque = $obj->code_banque;
				$this->code_guichet = $obj->code_guichet;
				$this->number = $obj->number;
				$this->cle_rib = $obj->cle_rib;
				$this->bic = $obj->bic;
				$this->iban = $obj->iban;
				$this->courant = self::TYPE_CURRENT;
				$this->type = self::TYPE_CURRENT;

				$this->domiciliation = $obj->address;
				$this->address = $obj->address;

				$this->proprio = $obj->owner_name;
				$this->owner_name = $obj->owner_name;
				$this->owner_address = $obj->owner_address;
				$this->label = $obj->label;
				$this->datec = $this->db->jdate($obj->datec);
				$this->datem = $this->db->jdate($obj->datem);
				$this->currency_code = $obj->currency_code;

				$this->state_id = $obj->state_id;
				$this->state_code = $obj->state_code;
				$this->state = $obj->state;

				$this->country_id = $obj->country_id;
				$this->country_code = $obj->country_code;
				$this->country = $obj->country;
			}
			$this->db->free($resql);

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Delete user bank account from database
	 *
	 *  @param	User|null	$user		User deleting
	 *	@param  int			$notrigger	1=Disable triggers
	 *  @return int      	       		Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user = null, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		// Delete link between tag and bank account
		/*
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_account";
			$sql .= " WHERE fk_account = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = "Error ".$this->db->lasterror();
			}
		}
		*/

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				// Remove extrafields
				/*
				if (!$error) {
					$result = $this->deleteExtraFields();
					if ($result < 0) {
						$error++;
						dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
					}
				}*/
			} else {
				$error++;
				$this->error = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Return RIB
	 *
	 * @param   boolean     $displayriblabel     Prepend or Hide Label
	 * @return  string      RIB
	 */
	public function getRibLabel($displayriblabel = true)
	{
		$rib = '';

		if ($this->code_banque || $this->code_guichet || $this->number || $this->cle_rib) {
			if ($this->label && $displayriblabel) {
				$rib = $this->label." : ";
			}

			$rib .= $this->iban;
		}

		return $rib;
	}

	/**
	 * Return if a country of userBank is inside the EEC (European Economic Community)
	 * @return     boolean    true = country inside EEC, false = country outside EEC
	 */
	public function checkCountryBankAccount()
	{
		if (!empty($this->country_code)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			$country_code_in_EEC = getCountriesInEEC();
			return in_array($this->country_code, $country_code_in_EEC);
		} else {
			return false;
		}
	}
}
