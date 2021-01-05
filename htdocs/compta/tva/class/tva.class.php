<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *      \file       htdocs/compta/tva/class/tva.class.php
 *      \ingroup    tax
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *  Put here description of your class
 */
class Tva extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'tva';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'tva';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	public $tms;
	public $datep;
	public $datev;
	public $amount;
	public $type_payment;
	public $num_payment;

	/**
	 * @var string label
	 */
	public $label;

	/**
	 * @var int ID
	 */
	public $fk_bank;

	/**
	 * @var int accountid
	 */
	public $accountid;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 */
	public $fk_user_modif;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Create in database
	 *
	 *  @param      User	$user       User that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error = 0;
		$now = dol_now();

		// Clean parameters
		$this->amount = trim($this->amount);
		$this->label = trim($this->label);
		$this->note = trim($this->note);
		$this->fk_bank = (int) $this->fk_bank;
		$this->fk_user_creat = (int) $this->fk_user_creat;
		$this->fk_user_modif = (int) $this->fk_user_modif;

		// Check parameters
		// Put here code to add control on parameters values

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."tva(";
		$sql .= "datec,";
		$sql .= "datep,";
		$sql .= "datev,";
		$sql .= "amount,";
		$sql .= "label,";
		$sql .= "note,";
		$sql .= "fk_bank,";
		$sql .= "fk_user_creat,";
		$sql .= "fk_user_modif";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " '".$this->db->idate($this->datep)."',";
		$sql .= " '".$this->db->idate($this->datev)."',";
		$sql .= " '".$this->db->escape($this->amount)."',";
		$sql .= " '".$this->db->escape($this->label)."',";
		$sql .= " '".$this->db->escape($this->note)."',";
		$sql .= " ".($this->fk_bank <= 0 ? "NULL" : "'".$this->db->escape($this->fk_bank)."'").",";
		$sql .= " '".$this->db->escape($this->fk_user_creat)."',";
		$sql .= " '".$this->db->escape($this->fk_user_modif)."'";
		$sql .= ")";

	   	dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva");

			// Call trigger
			$result = $this->call_trigger('TVA_CREATE', $user);
			if ($result < 0) $error++;
			// End call triggers

			if (!$error)
			{
				$this->db->commit();
				return $this->id;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Update database
	 *
	 * @param   User	$user        	User that modify
	 * @param	int		$notrigger	    0=no, 1=yes (no update trigger)
	 * @return  int         			<0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		// Clean parameters
		$this->amount = trim($this->amount);
		$this->label = trim($this->label);
		$this->note = trim($this->note);
		$this->fk_bank = (int) $this->fk_bank;
		$this->fk_user_creat = (int) $this->fk_user_creat;
		$this->fk_user_modif = (int) $this->fk_user_modif;

		// Check parameters
		// Put here code to add control on parameters values

		$this->db->begin();

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."tva SET";
		$sql .= " tms='".$this->db->idate($this->tms)."',";
		$sql .= " datep='".$this->db->idate($this->datep)."',";
		$sql .= " datev='".$this->db->idate($this->datev)."',";
		$sql .= " amount=".price2num($this->amount).",";
		$sql .= " label='".$this->db->escape($this->label)."',";
		$sql .= " note='".$this->db->escape($this->note)."',";
		$sql .= " fk_bank=".$this->fk_bank.",";
		$sql .= " fk_user_creat=".$this->fk_user_creat.",";
		$sql .= " fk_user_modif=".($this->fk_user_modif > 0 ? $this->fk_user_modif : $user->id)."";
		$sql .= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql)
		{
			$this->error = "Error ".$this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger)
		{
			// Call trigger
			$result = $this->call_trigger('TVA_MODIFY', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		} else {
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
	public function fetch($id, $user = null)
	{
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";

		$sql .= " t.tms,";
		$sql .= " t.datep,";
		$sql .= " t.datev,";
		$sql .= " t.amount,";
		$sql .= " t.fk_typepayment,";
		$sql .= " t.num_payment,";
		$sql .= " t.label,";
		$sql .= " t.note,";
		$sql .= " t.fk_bank,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " b.fk_account,";
		$sql .= " b.fk_type,";
		$sql .= " b.rappro";

		$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON t.fk_bank = b.rowid";
		$sql .= " WHERE t.rowid = ".$id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;
				$this->tms   = $this->db->jdate($obj->tms);
				$this->datep = $this->db->jdate($obj->datep);
				$this->datev = $this->db->jdate($obj->datev);
				$this->amount = $obj->amount;
				$this->type_payment = $obj->fk_typepayment;
				$this->num_payment = $obj->num_payment;
				$this->label = $obj->label;
				$this->note  = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->fk_type = $obj->fk_type;
				$this->rappro  = $obj->rappro;
			}
			$this->db->free($resql);

			return 1;
		} else {
	  		$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


 	/**
 	 *  Delete object in database
 	 *
 	 *	@param	User	$user       User that delete
 	 *	@return	int					<0 if KO, >0 if OK
 	 */
	public function delete($user)
	{
		global $conf, $langs;

		$error = 0;

		// Call trigger
		$result = $this->call_trigger('TVA_DELETE', $user);
		if ($result < 0) return -1;
		// End call triggers

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."tva";
		$sql .= " WHERE rowid=".$this->id;

	   	dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql)
		{
			$this->error = "Error ".$this->db->lasterror();
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
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->tms = '';
		$this->datep = '';
		$this->datev = '';
		$this->amount = '';
		$this->label = '';
		$this->note = '';
		$this->fk_bank = '';
		$this->fk_user_creat = '';
		$this->fk_user_modif = '';
	}


	/**
	 *  Balance of VAT
	 *
	 *	@param	int		$year		Year
	 *	@return	double				Amount
	 */
	public function solde($year = 0)
	{

		$reglee = $this->tva_sum_reglee($year);

		$payee = $this->tva_sum_payee($year);
		$collectee = $this->tva_sum_collectee($year);

		$solde = $reglee - ($collectee - $payee);

		return $solde;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Total of the VAT from invoices emitted by the thirdparty.
	 *
	 *	@param	int		$year		Year
	 *  @return	double				Amount
	 */
	public function tva_sum_collectee($year = 0)
	{
		// phpcs:enable

		$sql = "SELECT sum(f.tva) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1";
		if ($year)
		{
			$sql .= " AND f.datef >= '".$this->db->escape($year)."-01-01' AND f.datef <= '".$this->db->escape($year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$ret = $obj->amount;
				$this->db->free($result);
				return $ret;
			} else {
				$this->db->free($result);
				return 0;
			}
		} else {
			print $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	VAT payed
	 *
	 *	@param	int		$year		Year
	 *	@return	double				Amount
	 */
	public function tva_sum_payee($year = 0)
	{
		// phpcs:enable

		$sql = "SELECT sum(f.total_tva) as total_tva";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		if ($year)
		{
			$sql .= " WHERE f.datef >= '".$this->db->escape($year)."-01-01' AND f.datef <= '".$this->db->escape($year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$ret = $obj->total_tva;
				$this->db->free($result);
				return $ret;
			} else {
				$this->db->free($result);
				return 0;
			}
		} else {
			print $this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Total of the VAT payed
	 *
	 *	@param	int		$year		Year
	 *	@return	double				Amount
	 */
	public function tva_sum_reglee($year = 0)
	{
		// phpcs:enable

		$sql = "SELECT sum(f.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as f";

		if ($year)
		{
			$sql .= " WHERE f.datev >= '".$this->db->escape($year)."-01-01' AND f.datev <= '".$this->db->escape($year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$ret = $obj->amount;
				$this->db->free($result);
				return $ret;
			} else {
				$this->db->free($result);
				return 0;
			}
		} else {
			print $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Create in database
	 *
	 *	@param	User	$user		Object user that insert
	 *	@return	int					<0 if KO, rowid in tva table if OK
	 */
	public function addPayment($user)
	{
		global $conf, $langs;

		$this->db->begin();

		// Clean parameters
		$this->amount = price2num(trim($this->amount));
		$this->label = trim($this->label);
		$this->note = trim($this->note);
		$this->num_payment = trim($this->num_payment);
		$this->fk_bank = (int) $this->fk_bank;
		$this->fk_user_creat = (int) $this->fk_user_creat;
		$this->fk_user_modif = (int) $this->fk_user_modif;
		if (empty($this->datec)) $this->datec = dol_now();

		// Check parameters
		if (!$this->label)
		{
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
			return -3;
		}
		if ($this->amount == '')
		{
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
			return -4;
		}
		if (!empty($conf->banque->enabled) && (empty($this->accountid) || $this->accountid <= 0))
		{
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Account"));
			return -5;
		}
		if (!empty($conf->banque->enabled) && (empty($this->type_payment) || $this->type_payment <= 0))
		{
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("PaymentMode"));
			return -5;
		}

		// Insert into llx_tva
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."tva (";
		$sql .= "datec";
		$sql .= ", datep";
		$sql .= ", datev";
		$sql .= ", amount";
		$sql .= ", fk_typepayment";
		$sql .= ", num_payment";
		if ($this->note)  $sql .= ", note";
		if ($this->label) $sql .= ", label";
		$sql .= ", fk_user_creat";
		$sql .= ", fk_bank";
		$sql .= ", entity";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= " '".$this->db->idate($this->datec)."'";
		$sql .= ", '".$this->db->idate($this->datep)."'";
		$sql .= ", '".$this->db->idate($this->datev)."'";
		$sql .= ", ".$this->amount;
		$sql .= ", '".$this->db->escape($this->type_payment)."'";
		$sql .= ", '".$this->db->escape($this->num_payment)."'";
		if ($this->note)  $sql .= ", '".$this->db->escape($this->note)."'";
		if ($this->label) $sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", '".$this->db->escape($user->id)."'";
		$sql .= ", NULL";
		$sql .= ", ".$conf->entity;
		$sql .= ")";

		dol_syslog(get_class($this)."::addPayment", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva"); // TODO should be called 'payment_vat'

			// Call trigger
			//XXX: Should be done just befor commit no ?
			$result = $this->call_trigger('TVA_ADDPAYMENT', $user);
			if ($result < 0)
			{
				$this->id = 0;
				$ok = 0;
			}
			// End call triggers

			if ($this->id > 0)
			{
				$ok = 1;
				if (!empty($conf->banque->enabled) && !empty($this->amount))
				{
					// Insert into llx_bank
					require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

					$acc = new Account($this->db);
					$result = $acc->fetch($this->accountid);
					if ($result <= 0) dol_print_error($this->db);

					if ($this->amount > 0) {
						$bank_line_id = $acc->addline($this->datep, $this->type_payment, $this->label, -abs($this->amount), $this->num_payment, '', $user);
					} else {
						$bank_line_id = $acc->addline($this->datep, $this->type_payment, $this->label, abs($this->amount), $this->num_payment, '', $user);
					}

					// Update fk_bank into llx_tva. So we know vat line used to generate bank transaction
					if ($bank_line_id > 0)
					{
						$this->update_fk_bank($bank_line_id);
					} else {
						$this->error = $acc->error;
						$ok = 0;
					}

					// Update links
					$result = $acc->add_url_line($bank_line_id, $this->id, DOL_URL_ROOT.'/compta/tva/card.php?id=', "(VATPayment)", "payment_vat");
					if ($result < 0)
					{
						$this->error = $acc->error;
						$ok = 0;
					}
				}

				if ($ok)
				{
					$this->db->commit();
					return $this->id;
				} else {
					$this->db->rollback();
					return -3;
				}
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update link between payment tva and line generate into llx_bank
	 *
	 *  @param	int		$id_bank    Id bank account
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'tva SET fk_bank = '.(int) $id_bank;
		$sql .= ' WHERE rowid = '.(int) $this->id;
		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Send name clicable (with possibly the picto)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			link option
	 *  @param	int  	$notooltip		1=Disable tooltip
	 *  @param	string	$morecss			More CSS
	 *	@return	string					Chaine with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '')
	{
		global $langs, $conf;

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = '<u>'.$langs->trans("ShowVatPayment").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$url = DOL_URL_ROOT.'/compta/tva/card.php?id='.$this->id;

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$picto = 'payment';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref;
		$result .= $linkend;

		return $result;
	}

	/**
	 * 	Return amount of payments already done
	 *
	 *	@return		int		Amount of payment already done, <0 if KO
	 */
	public function getSommePaiement()
	{
		$table = 'paiementcharge';
		$field = 'fk_charge';

		$sql = 'SELECT sum(amount) as amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql .= ' WHERE '.$field.' = '.$this->id;

		dol_syslog(get_class($this)."::getSommePaiement", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$amount = 0;

			$obj = $this->db->fetch_object($resql);
			if ($obj) $amount = $obj->amount ? $obj->amount : 0;

			$this->db->free($resql);
			return $amount;
		} else {
			return -1;
		}
	}

	/**
	 *	Informations of vat payment object
	 *
	 *	@param	int		$id     Id of vat payment
	 *	@return	int				<0 if KO, >0 if OK
	 */
	public function info($id)
	{
		$sql = "SELECT t.rowid, t.tms, t.fk_user_modif, t.datec, t.fk_user_creat";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql .= " WHERE t.rowid = ".(int) $id;

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->import_key        = $obj->import_key;
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Retourne le libelle du statut d'une facture (brouillon, validee, abandonnee, payee)
	 *
	 * @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return  string				Libelle
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Renvoi le libelle d'un statut donne
	 *
	 * @param   int		$status     Statut
	 * @param   int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * @return	string  		    Libelle du statut
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs; // TODO Renvoyer le libelle anglais et faire traduction a affichage

		return '';
	}
}
