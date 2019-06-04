<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013   	Peter Fontaine          <contact@peterfontaine.fr>
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
 * 		\file		htdocs/societe/class/companybankaccount.class.php
 *		\ingroup    societe
 *		\brief      File of class to manage bank accounts description of third parties
 */

require_once DOL_DOCUMENT_ROOT .'/compta/bank/class/account.class.php';


/**
 * 	Class to manage bank accounts description of third parties
 */
class CompanyBankAccount extends Account
{
	public $socid;

	public $default_rib;
	public $frstrecur;
	public $rum;
	public $date_rum;

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
		$this->default_rib = 0;
	}


	/**
	 * Create bank information record
	 *
	 * @param   User   $user		User
	 * @param   int    $notrigger   1=Disable triggers
	 * @return	int					<0 if KO, >= 0 if OK
	 */
    public function create(User $user = null, $notrigger = 0)
	{
		$now	= dol_now();
		$error	= 0;
		// Correct default_rib to be sure to have always one default
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_rib where fk_soc = ".$this->socid." AND default_rib = 1 AND type = 'ban'";
   		$result = $this->db->query($sql);
		if ($result)
		{
			$numrows=$this->db->num_rows($result);
			if ($this->default_rib && $numrows > 0) $this->default_rib = 0;
			if (empty($this->default_rib) && $numrows == 0) $this->default_rib = 1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_rib (fk_soc, type, datec)";
		$sql.= " VALUES (".$this->socid.", 'ban', '".$this->db->idate($now)."')";
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->affected_rows($resql))
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe_rib");

				if (! $notrigger)
				{
				   	// Call trigger
					$result=$this->call_trigger('COMPANY_RIB_CREATE', $user);
					if ($result < 0) $error++;
					// End call triggers

					if (! $error)
					{
						return 1;
					}
					else
					{
						return 0;
					}
				}
				else
				{
					return 1;
				}
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
	 *	@param	User	$user	     Object user
	 *  @param  int     $notrigger   1=Disable triggers
	 *	@return	int				     <=0 if KO, >0 if OK
	 */
    public function update(User $user = null, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		if (! $this->id) return -1;

		if (dol_strlen($this->domiciliation) > 255) $this->domiciliation = dol_trunc($this->domiciliation, 254, 'right', 'UTF-8', 1);
		if (dol_strlen($this->owner_address) > 255) $this->owner_address = dol_trunc($this->owner_address, 254, 'right', 'UTF-8', 1);

		$sql = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET";
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
		$sql.= ",default_rib = ".$this->default_rib;
		if ($conf->prelevement->enabled)
		{
			$sql.= ",frstrecur = '".$this->db->escape($this->frstrecur)."'";
			$sql.= ",rum = '".$this->db->escape($this->rum)."'";
			$sql.= ",date_rum = ".($this->date_rum ? "'".$this->db->idate($this->date_rum)."'" : "null");
		}
		if (trim($this->label) != '')
			$sql.= ",label = '".$this->db->escape($this->label)."'";
		else
			$sql.= ",label = NULL";
		$sql.= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{


		if (! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('COMPANY_RIB_MODIFY', $user);
			if ($result < 0) $error++;
			// End call triggers
			if(! $error )
			{
				return 1;
			}
			else
			{
				return -1;
			}
		}
		else
		{
			return 1;
		}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Load record from database
	 *
	 *	@param	int		$id			Id of record
	 * 	@param	int		$socid		Id of company. If this is filled, function will return the first entry found (matching $default and $type)
	 *  @param	int		$default	If id of company filled, we say if we want first record among all (-1), default record (1) or non default record (0)
	 *  @param	int		$type		If id of company filled, we say if we want record of this type only
	 * 	@return	int					<0 if KO, >0 if OK
	 */
    public function fetch($id, $socid = 0, $default = 1, $type = 'ban')
	{
		if (empty($id) && empty($socid)) return -1;

		$sql = "SELECT rowid, type, fk_soc, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation, proprio,";
		$sql.= " owner_address, default_rib, label, datec, tms as datem, rum, frstrecur";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_rib";
		if ($id)    $sql.= " WHERE rowid = ".$id;
		if ($socid)
		{
			$sql.= " WHERE fk_soc  = ".$socid;
			if ($default > -1) $sql.=" AND default_rib = ".$this->db->escape($default);
			if ($type) $sql.= " AND type ='".$this->db->escape($type)."'";
		}

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->ref             = $obj->fk_soc.'-'.$obj->label;      // Generate an artificial ref

				$this->id			   = $obj->rowid;
				$this->type			   = $obj->type;
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
				$this->default_rib     = $obj->default_rib;
				$this->datec           = $this->db->jdate($obj->datec);
				$this->datem           = $this->db->jdate($obj->datem);
				$this->rum             = $obj->rum;
				$this->frstrecur       = $obj->frstrecur;
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
	 *  Delete a rib from database
	 *
	 *	@param		User	$user		User deleting
	 *	@param  	int		$notrigger	1=Disable triggers
	 *  @return		int		            <0 if KO, >0 if OK
	 */
    public function delete(User $user = null, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		dol_syslog(get_class($this) . "::delete ".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('COMPANY_RIB_DELETE', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (! $error)
		{
			$sql = "DELETE FROM " . MAIN_DB_PREFIX . "societe_rib";
			$sql .= " WHERE rowid  = " . $this->id;

			if (! $this->db->query($sql))
			{
				$error++;
				$this->errors[]=$this->db->lasterror();
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1*$error;
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

		if ($this->code_banque || $this->code_guichet || $this->number || $this->cle_rib || $this->iban || $this->bic ) {

			if ($this->label && $displayriblabel) {
				$rib = $this->label." : ";
			}

			$rib .= (string) $this;
		}

		return $rib;
	}

	/**
	 * Set a BAN as Default
	 *
	 * @param   int     $rib    RIB id
	 * @return  int             0 if KO, 1 if OK
	 */
    public function setAsDefault($rib = 0)
	{
		$sql1 = "SELECT rowid as id, fk_soc  FROM ".MAIN_DB_PREFIX."societe_rib";
		$sql1.= " WHERE rowid = ".($rib?$rib:$this->id);

		dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
		$result1 = $this->db->query($sql1);
		if ($result1)
		{
			if ($this->db->num_rows($result1) == 0)
			{
				return 0;
			}
			else
			{
				$obj = $this->db->fetch_object($result1);

				$this->db->begin();

				$sql2 = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET default_rib = 0";
				$sql2.= " WHERE type = 'ban' AND fk_soc = ".$obj->fk_soc;
				dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
				$result2 = $this->db->query($sql2);

				$sql3 = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET default_rib = 1";
				$sql3.= " WHERE rowid = ".$obj->id;
				dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
				$result3 = $this->db->query($sql3);

				if (!$result2 || !$result3)
				{
					dol_print_error($this->db);
					$this->db->rollback();
					return -1;
				}
				else
				{
					$this->db->commit();
					return 1;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
    public function initAsSpecimen()
	{
		$this->specimen        = 1;
		$this->ref             = 'CBA';
		$this->label           = 'CustomerCorp Bank account';
		$this->bank            = 'CustomerCorp Bank';
		$this->courant         = Account::TYPE_CURRENT;
		$this->clos            = Account::STATUS_OPEN;
		$this->code_banque     = '123';
		$this->code_guichet    = '456';
		$this->number          = 'CUST12345';
		$this->cle_rib         = 50;
		$this->bic             = 'CC12';
		$this->iban            = 'FR999999999';
		$this->domiciliation   = 'Bank address of customer corp';
		$this->proprio         = 'Owner';
		$this->owner_address   = 'Owner address';
		$this->country_id      = 1;

		$this->rum             = 'UMR-CU1212-0007-5-1475405262';
		$this->date_rum        = dol_now() - 10000;
		$this->frstrecur       = 'FRST';

		$this->socid = 0;
	}
}
