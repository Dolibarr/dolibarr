<?php
/* Copyright (C) 2002-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

	/**
	 * @deprecated
	 * @see $amount
	 */
	public $total;

	public $datep;
	public $datev;
	public $amount;
	public $type_payment;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @var int     Creation date
	 */
	public $datec;

	/**
	 * @var int ID
	 */
	public $fk_type;

	/**
	 * @var int
	 */
	public $paye;

	/**
	 * @var int
	 */
	public $rappro;

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
	 * @var int|string paiementtype
	 */
	public $paiementtype;


	const STATUS_UNPAID = 0;
	const STATUS_PAID = 1;

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
	 *  @return     int      			Return integer <0 if KO, >0 if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error = 0;
		$now = dol_now();

		// Clean parameters
		$this->amount = trim($this->amount);
		$this->label = trim($this->label);
		$this->type_payment = (int) $this->type_payment;
		$this->note = trim($this->note);
		$this->fk_account = (int) $this->fk_account;
		$this->fk_user_creat = (int) $this->fk_user_creat;
		$this->fk_user_modif = (int) $this->fk_user_modif;

		// Check parameters
		// Put here code to add control on parameters values

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."tva(";
		$sql .= "entity,";
		$sql .= "datec,";
		$sql .= "datep,";
		$sql .= "datev,";
		$sql .= "amount,";
		$sql .= "label,";
		$sql .= "note,";
		$sql .= "fk_account,";
		$sql .= "fk_typepayment,";
		$sql .= "fk_user_creat,";
		$sql .= "fk_user_modif";
		$sql .= ") VALUES (";
		$sql .= " ".((int) $conf->entity).", ";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " '".$this->db->idate($this->datep)."',";
		$sql .= " '".$this->db->idate($this->datev)."',";
		$sql .= " '".$this->db->escape($this->amount)."',";
		$sql .= " '".$this->db->escape($this->label)."',";
		$sql .= " '".$this->db->escape($this->note)."',";
		$sql .= " '".$this->db->escape($this->fk_account)."',";
		$sql .= " '".$this->db->escape($this->type_payment)."',";
		$sql .= " ".($this->fk_user_creat > 0 ? (int) $this->fk_user_creat : (int) $user->id).",";
		$sql .= " ".($this->fk_user_modif > 0 ? (int) $this->fk_user_modif : (int) $user->id);
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva");

			// Call trigger
			$result = $this->call_trigger('TVA_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers

			if (!$error) {
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
	 * @return  int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		// Clean parameters
		$this->amount = trim($this->amount);
		$this->label = trim($this->label);
		$this->note = trim($this->note);
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
		$sql .= " fk_user_creat=".((int) $this->fk_user_creat).",";
		$sql .= " fk_user_modif=".((int) ($this->fk_user_modif > 0 ? $this->fk_user_modif : $user->id));
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			$error++;
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('TVA_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
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
	 *    Tag TVA as paid completely
	 *
	 *    @param    User    $user       Object user making change
	 *    @return   int					Return integer <0 if KO, >0 if OK
	 */
	public function setPaid($user)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."tva SET";
		$sql .= " paye = 1";
		$sql .= " WHERE rowid = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			return -1;
		}
	}

	/**
	 *    Remove tag paid on TVA
	 *
	 *    @param	User	$user       Object user making change
	 *    @return	int					Return integer <0 if KO, >0 if OK
	 */
	public function setUnpaid($user)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."tva SET";
		$sql .= " paye = 0";
		$sql .= " WHERE rowid = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			return 1;
		} else {
			return -1;
		}
	}


	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         id object
	 *  @param  string	$ref        Ref of VAT (not used yet)
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
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
		$sql .= " t.paye,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " t.fk_account";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
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
				$this->paye  = $obj->paye;
				$this->note  = $obj->note;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;
				$this->fk_account = $obj->fk_account;
				$this->fk_type = empty($obj->fk_type) ? "" : $obj->fk_type;
				$this->rappro  = empty($obj->rappro) ? "" : $obj->rappro;
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
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		global $conf, $langs;

		$error = 0;

		// Call trigger
		$result = $this->call_trigger('TVA_DELETE', $user);
		if ($result < 0) {
			return -1;
		}
		// End call triggers

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."tva";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
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
	 *  @return	int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->tms = dol_now();
		$this->datep = '';
		$this->datev = '';
		$this->amount = '';
		$this->label = '';
		$this->note = '';
		$this->fk_bank = 0;
		$this->fk_user_creat = 0;
		$this->fk_user_modif = 0;

		return 1;
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

		$sql = "SELECT sum(f.total_tva) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f WHERE f.paye = 1";
		if ($year) {
			$sql .= " AND f.datef >= '".$this->db->escape($year)."-01-01' AND f.datef <= '".$this->db->escape($year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
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
	 * 	VAT paid
	 *
	 *	@param	int		$year		Year
	 *	@return	double				Amount
	 */
	public function tva_sum_payee($year = 0)
	{
		// phpcs:enable

		$sql = "SELECT sum(f.total_tva) as total_tva";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		if ($year) {
			$sql .= " WHERE f.datef >= '".$this->db->escape($year)."-01-01' AND f.datef <= '".$this->db->escape($year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
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
	 * 	Total of the VAT paid
	 *
	 *	@param	int		$year		Year
	 *	@return	double				Amount
	 */
	public function tva_sum_reglee($year = 0)
	{
		// phpcs:enable

		$sql = "SELECT sum(f.amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as f";

		if ($year) {
			$sql .= " WHERE f.datev >= '".$this->db->escape($year)."-01-01' AND f.datev <= '".$this->db->escape($year)."-12-31' ";
		}

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
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
	 *	@return	int					Return integer <0 if KO, rowid in tva table if OK
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
		if (empty($this->datec)) {
			$this->datec = dol_now();
		}

		// Check parameters
		if (!$this->label) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Label"));
			return -3;
		}
		if ($this->amount == '') {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("Amount"));
			return -4;
		}
		if (isModEnabled("bank") && (empty($this->accountid) || $this->accountid <= 0)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentities("BankAccount"));
			return -5;
		}
		if (isModEnabled("bank") && (empty($this->type_payment) || $this->type_payment <= 0)) {
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
		if ($this->note) {
			$sql .= ", note";
		}
		if ($this->label) {
			$sql .= ", label";
		}
		$sql .= ", fk_user_creat";
		$sql .= ", fk_bank";
		$sql .= ", entity";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= " '".$this->db->idate($this->datec)."'";
		$sql .= ", '".$this->db->idate($this->datep)."'";
		$sql .= ", '".$this->db->idate($this->datev)."'";
		$sql .= ", ".((float) $this->amount);
		$sql .= ", '".$this->db->escape($this->type_payment)."'";
		$sql .= ", '".$this->db->escape($this->num_payment)."'";
		if ($this->note) {
			$sql .= ", '".$this->db->escape($this->note)."'";
		}
		if ($this->label) {
			$sql .= ", '".$this->db->escape($this->label)."'";
		}
		$sql .= ", '".$this->db->escape($user->id)."'";
		$sql .= ", NULL";
		$sql .= ", ".((int) $conf->entity);
		$sql .= ")";

		dol_syslog(get_class($this)."::addPayment", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."tva"); // TODO should be called 'payment_vat'

			// Call trigger
			//XXX: Should be done just before commit no ?
			$result = $this->call_trigger('TVA_ADDPAYMENT', $user);
			if ($result < 0) {
				$this->id = 0;
				$ok = 0;
			}
			// End call triggers

			if ($this->id > 0) {
				$ok = 1;
				if (isModEnabled("bank") && !empty($this->amount)) {
					// Insert into llx_bank
					require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

					$acc = new Account($this->db);
					$result = $acc->fetch($this->accountid);
					if ($result <= 0) {
						dol_print_error($this->db);
					}

					if ($this->amount > 0) {
						$bank_line_id = $acc->addline($this->datep, $this->type_payment, $this->label, -abs((float) $this->amount), $this->num_payment, '', $user);
					} else {
						$bank_line_id = $acc->addline($this->datep, $this->type_payment, $this->label, abs((float) $this->amount), $this->num_payment, '', $user);
					}

					// Update fk_bank into llx_tva. So we know vat line used to generate bank transaction
					if ($bank_line_id > 0) {
						$this->update_fk_bank($bank_line_id);
					} else {
						$this->error = $acc->error;
						$ok = 0;
					}

					// Update links
					$result = $acc->add_url_line($bank_line_id, $this->id, DOL_URL_ROOT.'/compta/tva/card.php?id=', "(VATPayment)", "payment_vat");
					if ($result < 0) {
						$this->error = $acc->error;
						$ok = 0;
					}
				}

				if ($ok) {
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
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'tva SET fk_bank = '.(int) $id_bank;
		$sql .= ' WHERE rowid = '.(int) $this->id;
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Send name clickable (with possibly the picto)
	 *
	 *	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param	string	$option			link option
	 *  @param	int  	$notooltip		1=Disable tooltip
	 *  @param	string	$morecss		More CSS
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string					Chaine with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $conf;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = '<u>'.$langs->trans("ShowVatPayment").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (!empty($this->label)) {
			$label .= '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		}

		$url = DOL_URL_ROOT.'/compta/tva/card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$picto = 'payment';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
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
		$table = 'payment_vat';
		$field = 'fk_tva';

		$sql = 'SELECT sum(amount) as amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql .= " WHERE ".$field." = ".((int) $this->id);

		dol_syslog(get_class($this)."::getSommePaiement", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$amount = 0;

			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$amount = $obj->amount ? $obj->amount : 0;
			}

			$this->db->free($resql);
			return $amount;
		} else {
			return -1;
		}
	}

	/**
	 *	Information of vat payment object
	 *
	 *	@param	int		$id     Id of vat payment
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = "SELECT t.rowid, t.tms, t.fk_user_modif, t.datec, t.fk_user_creat";
		$sql .= " FROM ".MAIN_DB_PREFIX."tva as t";
		$sql .= " WHERE t.rowid = ".(int) $id;

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return the label of the VAT status f object
	 *
	 *  @param	int		$mode       	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
	 *  @return	string        			Label
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paye, $mode, $alreadypaid);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given VAT status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @param  double	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommend to put here amount paid if you have it, 1 otherwise)
	 *  @return string        			Label
	 */
	public function LibStatut($status, $mode = 0, $alreadypaid = -1)
	{
		// phpcs:enable
		global $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("customers", "bills"));

		// We reinit status array to force to redefine them because label may change according to properties values.
		$this->labelStatus = array();
		$this->labelStatusShort = array();

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('BillStatusNotPaid');
			$this->labelStatus[self::STATUS_PAID] = $langs->transnoentitiesnoconv('BillStatusPaid');
			if ($status == self::STATUS_UNPAID && $alreadypaid != 0) {
				$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
			$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('BillStatusNotPaid');
			$this->labelStatusShort[self::STATUS_PAID] = $langs->transnoentitiesnoconv('BillStatusPaid');
			if ($status == self::STATUS_UNPAID && $alreadypaid != 0) {
				$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
		}

		$statusType = 'status1';
		if ($status == 0 && $alreadypaid != 0) {
			$statusType = 'status3';
		}
		if ($status == 1) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		//$return .= '<i class="fa fa-dol-action"></i>'; // Can be image
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'amount')) {
			$return .= ' | <span class="opacitymedium">'.$langs->trans("Amount").'</span> : <span class="info-box-label amount">'.price($this->amount).'</span>';
		}
		if (property_exists($this, 'type_payment')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Payement").'</span> : <span class="info-box-label">'.$this->type_payment.'</span>';
		}
		if (property_exists($this, 'datev')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("DateEnd").'</span> : <span class="info-box-label" >'.dol_print_date($this->datev).'</span>';
		}
		if (method_exists($this, 'LibStatut')) {
			$return .= '<br><div class="info-box-status margintoponly">'.$this->getLibStatut(3, $this->alreadypaid).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
