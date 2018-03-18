<?php
/* Copyright (C) 2017       Alexandre Spangaro  <aspangaro@zendsi.com>
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
 *  \file		htdocs/compta/bank/class/paymentvarious.class.php
 *  \ingroup	bank
 *  \brief		Class for various payment
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *  Class to manage various payments
 */
class PaymentVarious extends CommonObject
{
	public $element='variouspayment';		//!< Id that identify managed objects
	public $table_element='payment_various';	//!< Name of table without prefix where object is stored
	public $picto = 'bill';

	var $id;
	var $ref;
	var $tms;
	var $datep;
	var $datev;
	var $sens;
	var $amount;
	var $type_payment;
	var $num_payment;
	var $label;
	var $accountancy_code;
	var $fk_project;
	var $fk_bank;
	var $fk_user_author;
	var $fk_user_modif;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->element = 'payment_various';
		$this->table_element = 'payment_various';
		return 1;
	}

	/**
	 * Update database
	 *
	 * @param   User	$user        	User that modify
	 * @param	int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			<0 if KO, >0 if OK
	 */
	function update($user=null, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		// Clean parameters
		$this->amount=trim($this->amount);
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_author=trim($this->fk_user_author);
		$this->fk_user_modif=trim($this->fk_user_modif);

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_various SET";
		if ($this->tms) $sql.= " tms='".$this->db->idate($this->tms)."',";
		$sql.= " datep='".$this->db->idate($this->datep)."',";
		$sql.= " datev='".$this->db->idate($this->datev)."',";
		$sql.= " sens=".$this->sens.",";
		$sql.= " amount=".price2num($this->amount).",";
		$sql.= " fk_typepayment=".$this->fk_typepayment."',";
		$sql.= " num_payment='".$this->db->escape($this->num_payment)."',";
		$sql.= " label='".$this->db->escape($this->label)."',";
		$sql.= " note='".$this->db->escape($this->note)."',";
		$sql.= " accountancy_code='".$this->db->escape($this->accountancy_code)."',";
		$sql.= " fk_projet='".$this->db->escape($this->fk_project)."',";
		$sql.= " fk_bank=".($this->fk_bank > 0 ? $this->fk_bank:"null").",";
		$sql.= " fk_user_author=".$this->fk_user_author.",";
		$sql.= " fk_user_modif=".$this->fk_user_modif;
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		if (! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('PAYMENT_VARIOUS_MODIFY',$user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         id object
	 *  @param  User	$user       User that load
	 *  @return int         		<0 if KO, >0 if OK
	 */
	function fetch($id, $user=null)
	{
		global $langs;
		$sql = "SELECT";
		$sql.= " v.rowid,";
		$sql.= " v.tms,";
		$sql.= " v.datep,";
		$sql.= " v.datev,";
		$sql.= " v.sens,";
		$sql.= " v.amount,";
		$sql.= " v.fk_typepayment,";
		$sql.= " v.num_payment,";
		$sql.= " v.label,";
		$sql.= " v.note,";
		$sql.= " v.accountancy_code,";
		$sql.= " v.fk_projet as fk_project,";
		$sql.= " v.fk_bank,";
		$sql.= " v.fk_user_author,";
		$sql.= " v.fk_user_modif,";
		$sql.= " b.fk_account,";
		$sql.= " b.fk_type,";
		$sql.= " b.rappro";
		$sql.= " FROM ".MAIN_DB_PREFIX."payment_various as v";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON v.fk_bank = b.rowid";
		$sql.= " WHERE v.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->ref				= $obj->rowid;
				$this->tms				= $this->db->jdate($obj->tms);
				$this->datep			= $this->db->jdate($obj->datep);
				$this->datev			= $this->db->jdate($obj->datev);
				$this->sens				= $obj->sens;
				$this->amount			= $obj->amount;
				$this->type_payment		= $obj->fk_typepayment;
				$this->num_payment		= $obj->num_payment;
				$this->label			= $obj->label;
				$this->note				= $obj->note;
				$this->accountancy_code	= $obj->accountancy_code;
				$this->fk_project		= $obj->fk_project;
				$this->fk_bank			= $obj->fk_bank;
				$this->fk_user_author	= $obj->fk_user_author;
				$this->fk_user_modif	= $obj->fk_user_modif;
				$this->fk_account		= $obj->fk_account;
				$this->fk_type			= $obj->fk_type;
				$this->rappro			= $obj->rappro;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *	@param	User	$user       User that delete
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function delete($user)
	{
		global $conf, $langs;

		$error=0;

		// Call trigger
		$result=$this->call_trigger('PAYMENT_VARIOUS_DELETE',$user);
		if ($result < 0) return -1;
		// End call triggers


		$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_various";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}

		return 1;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->tms='';
		$this->datep='';
		$this->datev='';
		$this->sens='';
		$this->amount='';
		$this->label='';
		$this->accountancy_code='';
		$this->note='';
		$this->fk_bank='';
		$this->fk_user_author='';
		$this->fk_user_modif='';
	}

	/**
	 *  Create in database
	 *
	 *  @param   User   $user   User that create
	 *  @return  int            <0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf,$langs;

		$error=0;
		$now=dol_now();

		// Clean parameters
		$this->amount=price2num(trim($this->amount));
		$this->label=trim($this->label);
		$this->note=trim($this->note);
		$this->fk_bank=trim($this->fk_bank);
		$this->fk_user_author=trim($this->fk_user_author);
		$this->fk_user_modif=trim($this->fk_user_modif);

		// Check parameters
		if (! $this->label)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Label"));
			return -3;
		}
		if ($this->amount < 0 || $this->amount == '')
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Amount"));
			return -5;
		}
		if (! empty($conf->banque->enabled) && (empty($this->accountid) || $this->accountid <= 0))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Account"));
			return -6;
		}
		if (! empty($conf->banque->enabled) && (empty($this->type_payment) || $this->type_payment <= 0))
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->transnoentities("PaymentMode"));
			return -7;
		}

		$this->db->begin();

		// Insert into llx_payment_various
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_various (";
		$sql.= " datep";
		$sql.= ", datev";
		$sql.= ", sens";
		$sql.= ", amount";
		$sql.= ", fk_typepayment";
		$sql.= ", num_payment";
		if ($this->note) $sql.= ", note";
		$sql.= ", label";
		$sql.= ", accountancy_code";
		$sql.= ", fk_projet";
		$sql.= ", fk_user_author";
		$sql.= ", datec";
		$sql.= ", fk_bank";
		$sql.= ", entity";
		$sql.= ")";
		$sql.= " VALUES (";
		$sql.= "'".$this->db->idate($this->datep)."'";
		$sql.= ", '".$this->db->idate($this->datev)."'";
		$sql.= ", '".$this->db->escape($this->sens)."'";
		$sql.= ", ".$this->amount;
		$sql.= ", '".$this->db->escape($this->type_payment)."'";
		$sql.= ", '".$this->db->escape($this->num_payment)."'";
		if ($this->note) $sql.= ", '".$this->db->escape($this->note)."'";
		$sql.= ", '".$this->db->escape($this->label)."'";
		$sql.= ", '".$this->db->escape($this->accountancy_code)."'";
		$sql.= ", ".($this->fk_project > 0? $this->fk_project : 0);
		$sql.= ", ".$user->id;
		$sql.= ", '".$this->db->idate($now)."'";
		$sql.= ", NULL";
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_various");
			$this->ref = $this->id;

			if ($this->id > 0)
			{
				if (! empty($conf->banque->enabled) && ! empty($this->amount))
				{
					// Insert into llx_bank
					require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

					$acc = new Account($this->db);
					$result=$acc->fetch($this->accountid);
					if ($result <= 0) dol_print_error($this->db);

					// Insert payment into llx_bank
					// Add link 'payment_various' in bank_url between payment and bank transaction
					if ($this->sens == '0') $sign='-';

					$bank_line_id = $acc->addline(
						$this->datep,
						$this->type_payment,
						$this->label,
						$sign.abs($this->amount),
						$this->num_payment,
						'',
						$user
					);

					// Update fk_bank into llx_paiement.
					// So we know the payment which has generate the banking ecriture
					if ($bank_line_id > 0)
					{
						$this->update_fk_bank($bank_line_id);
					}
					else
					{
						$this->error=$acc->error;
						$error++;
					}

					if (! $error)
					{
						// Add link 'payment_various' in bank_url between payment and bank transaction
						$url=DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id=';

						$result=$acc->add_url_line($bank_line_id, $this->id, $url, "(VariousPayment)", "payment_various");
						if ($result <= 0)
						{
							$this->error=$acc->error;
							$error++;
						}
					}

					if ($result <= 0)
					{
						$this->error=$acc->error;
						$error++;
					}
				}

				// Call trigger
				$result=$this->call_trigger('PAYMENT_VARIOUS_CREATE',$user);
				if ($result < 0) $error++;
				// End call triggers

			}
			else $error++;

			if (! $error)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Update link between payment various and line generate into llx_bank
	 *
	 *  @param  int     $id_bank    Id bank account
	 *	@return int                 <0 if KO, >0 if OK
	 */
	function update_fk_bank($id_bank)
	{
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'payment_various SET fk_bank = '.$id_bank;
		$sql.= ' WHERE rowid = '.$this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 * Retourne le libelle du statut
	 *
	 * @param	int		$mode   	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * @return  string   		   	Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$statut     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string      		Libelle
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 3)
		{
			if ($statut==0 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			if ($statut==2 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
		}
		if ($mode == 4)
		{
			if ($statut==0 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==1 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts[$statut]);
			if ($statut==2 && ! empty($this->statuts_short[$statut])) return img_picto($langs->trans($this->statuts_short[$statut]),'statut6').' '.$langs->trans($this->statuts[$statut]);
		}
		if ($mode == 5)
		{
			if ($statut==0 && ! empty($this->statuts_short[$statut])) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1 && ! empty($this->statuts_short[$statut])) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
			if ($statut==2 && ! empty($this->statuts_short[$statut])) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut6');
		}
	}


	/**
	 *	Send name clicable (with possibly the picto)
	 *
	 *	@param  int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param  string	$option			link option
	 *	@return string					Chaine with URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';
		$label=$langs->trans("ShowVariousPayment").': '.$this->ref;

		$linkstart = '<a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend='</a>';

		$result .= $linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.= ($maxlen?dol_trunc($this->ref,$maxlen):$this->ref);
		$result .= $linkend;

		return $result;
	}

	/**
	 * Information on record
	 *
	 * @param  int      $id      Id of record
	 * @return void
	 */
	function info($id)
	{
		$sql = 'SELECT v.rowid, v.datec, v.fk_user_author';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'payment_various as v';
		$sql.= ' WHERE v.rowid = '.$id;

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				$this->date_creation = $this->db->jdate($obj->datec);
				if ($obj->fk_user_modif)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modif = $muser;
				}
				$this->date_modif = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

}
