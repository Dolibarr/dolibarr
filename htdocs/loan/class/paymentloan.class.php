<?php
/* Copyright (C) 2014-2025	Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2015-2024  Frédéric France      		<frederic.france@free.fr>
 * Copyright (C) 2020       Maxime DEMAREST      		<maxime@indelog.fr>
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
 *  \file       htdocs/loan/class/paymentloan.class.php
 *  \ingroup    loan
 *  \brief      File of class to manage payment of loans
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 * Class to manage payments of loans
 */
class PaymentLoan extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'payment_loan';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'payment_loan';

	/**
	 * @var string String with name of icon for PaymentLoan
	 */
	public $picto = 'money-bill-alt';

	/**
	 * @var int Loan ID
	 */
	public $fk_loan;

	/**
	 * @var string Create date
	 */
	public $datec = '';

	/**
	 * @var string Payment date
	 */
	public $datep = '';

	/**
	 * @var array<float|int> Array of amounts
	 */
	public $amounts = array();

	/**
	 * @var float|int  Total amount of payment
	 */
	public $amount_capital;

	/**
	 * @var float|int
	 */
	public $amount_insurance;

	/**
	 * @var null|float|int
	 */
	public $amount_interest;

	/**
	 * @var int Payment mode ID
	 */
	public $fk_typepayment;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @var int Bank ID
	 */
	public $fk_bank;

	/**
	 * @var int User ID
	 */
	public $fk_user_creat;

	/**
	 * @var int user ID
	 */
	public $fk_user_modif;

	/**
	 * @var string
	 */
	public $type_code;
	/**
	 * @var string
	 */
	public $type_label;
	/**
	 * @var int
	 */
	public $chid;
	/**
	 * @var string
	 */
	public $label;

	/**
	 * @var int
	 */
	public $paymenttype;

	/**
	 * @var int
	 */
	public $bank_account;

	/**
	 * @var int
	 */
	public $bank_line;


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
	 *  Create payment of loan into database.
	 *  Use this->amounts to have list of lines for the payment
	 *
	 *  @param      User		$user   User making payment
	 *  @return     int     			Return integer <0 if KO, id of payment if OK
	 */
	public function create($user)
	{
		$error = 0;

		$now = dol_now();

		// Validate parameters
		if (!$this->datep) {
			$this->error = 'ErrorBadValueForParameter';
			return -1;
		}

		// Clean parameters
		if (isset($this->fk_loan)) {
			$this->fk_loan = (int) $this->fk_loan;
		}
		if (isset($this->amount_capital)) {
			$this->amount_capital = (float) price2num($this->amount_capital ? $this->amount_capital : 0);
		}
		if (isset($this->amount_insurance)) {
			$this->amount_insurance = (float) price2num($this->amount_insurance ? $this->amount_insurance : 0);
		}
		if (isset($this->amount_interest)) {
			$this->amount_interest = (float) price2num($this->amount_interest ? $this->amount_interest : 0);
		}
		if (isset($this->fk_typepayment)) {
			$this->fk_typepayment = (int) $this->fk_typepayment;
		}
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = (int) $this->fk_bank;
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = (int) $this->fk_user_creat;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}

		$totalamount = $this->amount_capital + $this->amount_insurance + $this->amount_interest;
		$totalamount = (float) price2num($totalamount);

		// Check parameters
		if ($totalamount == 0) {
			return -1; // Negative amounts are accepted for reject prelevement but not null
		}


		$this->db->begin();

		if ($totalamount != 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_loan (fk_loan, datec, datep, amount_capital, amount_insurance, amount_interest,";
			$sql .= " fk_typepayment, num_payment, note_private, note_public, fk_user_creat, fk_bank)";
			$sql .= " VALUES (".$this->chid.", '".$this->db->idate($now)."',";
			$sql .= " '".$this->db->idate($this->datep)."',";
			$sql .= " ".price2num($this->amount_capital).",";
			$sql .= " ".price2num($this->amount_insurance).",";
			$sql .= " ".price2num($this->amount_interest).",";
			$sql .= " ".((int) $this->paymenttype).", '".$this->db->escape($this->num_payment)."', '".$this->db->escape($this->note_private)."', '".$this->db->escape($this->note_public)."', ".$user->id.",";
			$sql .= " 0)";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_loan");
			} else {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if ($totalamount != 0 && !$error) {
			$this->amount_capital = $totalamount;
			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_loan,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.datep,";
		$sql .= " t.amount_capital,";
		$sql .= " t.amount_insurance,";
		$sql .= " t.amount_interest,";
		$sql .= " t.fk_typepayment,";
		$sql .= " t.num_payment,";
		$sql .= " t.note_private,";
		$sql .= " t.note_public,";
		$sql .= " t.fk_bank,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " pt.code as type_code, pt.libelle as type_label,";
		$sql .= ' b.fk_account';
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_loan as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pt ON t.fk_typepayment = pt.id";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;

				$this->fk_loan = $obj->fk_loan;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->datep = $this->db->jdate($obj->datep);
				$this->amount_capital = $obj->amount_capital;
				$this->amount_insurance = $obj->amount_insurance;
				$this->amount_interest = $obj->amount_interest;
				$this->fk_typepayment = $obj->fk_typepayment;
				$this->num_payment = $obj->num_payment;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat = $obj->fk_user_creat;
				$this->fk_user_modif = $obj->fk_user_modif;

				$this->type_code = $obj->type_code;
				$this->type_label = $obj->type_label;

				$this->bank_account = $obj->fk_account;
				$this->bank_line = $obj->fk_bank;
			}
			$this->db->free($resql);

			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		$error = 0;

		// Clean parameters
		if (isset($this->fk_loan)) {
			$this->fk_loan = (int) $this->fk_loan;
		}
		if (isset($this->amount_capital)) {
			$this->amount_capital = (float) $this->amount_capital;
		}
		if (isset($this->amount_insurance)) {
			$this->amount_insurance = (float) $this->amount_insurance;
		}
		if (isset($this->amount_interest)) {
			$this->amount_interest = (float) $this->amount_interest;
		}
		if (isset($this->fk_typepayment)) {
			$this->fk_typepayment = (int) $this->fk_typepayment;
		}
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note_private)) {
			$this->note = trim($this->note_private);
		}
		if (isset($this->note_public)) {
			$this->note = trim($this->note_public);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = (int) $this->fk_bank;
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = (int) $this->fk_user_creat;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}

		// Check parameters

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_loan SET";
		$sql .= " fk_loan=".(isset($this->fk_loan) ? $this->fk_loan : "null").",";
		$sql .= " datec=".(dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " tms=".(dol_strlen((string) $this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql .= " datep=".(dol_strlen($this->datep) != 0 ? "'".$this->db->idate($this->datep)."'" : 'null').",";
		$sql .= " amount_capital=".(isset($this->amount_capital) ? $this->amount_capital : "null").",";
		$sql .= " amount_insurance=".(isset($this->amount_insurance) ? $this->amount_insurance : "null").",";
		$sql .= " amount_interest=".(isset($this->amount_interest) ? $this->amount_interest : "null").",";
		$sql .= " fk_typepayment=".(isset($this->fk_typepayment) ? $this->fk_typepayment : "null").",";
		$sql .= " num_payment=".(isset($this->num_payment) ? "'".$this->db->escape($this->num_payment)."'" : "null").",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " fk_bank=".(isset($this->fk_bank) ? ((int) $this->fk_bank) : "null").",";
		$sql .= " fk_user_creat=".(isset($this->fk_user_creat) ? ((int) $this->fk_user_creat) : "null").",";
		$sql .= " fk_user_modif=".(isset($this->fk_user_modif) ? ((int) $this->fk_user_modif) : "null");
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Delete object in database
	 *
	 *  @param	User	$user        	User that delete
	 *  @param  int		$notrigger		0=launch triggers after, 1=disable triggers
	 *  @return int						Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		if ($this->bank_line > 0) {
			$accline = new AccountLine($this->db);
			$accline->fetch($this->bank_line);
			$result = $accline->delete($user);
			if ($result < 0) {
				$this->errors[] = $accline->error;
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_loan";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		// Set loan unpaid if loan has no other payment
		if (!$error) {
			require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
			$loan = new Loan($this->db);
			$loan->fetch($this->fk_loan);
			$sum_payment = $loan->getSumPayment();
			if ($sum_payment == 0) {
				dol_syslog(get_class($this)."::delete : set loan to unpaid", LOG_DEBUG);
				if ($loan->setUnpaid($user) < 1) {
					$error++;
					dol_print_error($this->db);
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
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
		return '';
	}

	/**
	 *      Add record into bank for payment with links between this bank record and invoices of payment.
	 *      All payment properties must have been set first like after a call to create().
	 *
	 *      @param	User	$user               Object of user making payment
	 *      @param  int		$fk_loan            Id of fk_loan to do link with this payment
	 *      @param  string	$mode               'payment_loan'
	 *      @param  string	$label              Label to use in bank record
	 *      @param  int		$accountid          Id of bank account to do link with
	 *      @param  string	$emetteur_nom       Name of transmitter
	 *      @param  string	$emetteur_banque    Name of bank
	 *      @return int                 		Return integer <0 if KO, >0 if OK
	 */
	public function addPaymentToBank($user, $fk_loan, $mode, $label, $accountid, $emetteur_nom, $emetteur_banque)
	{
		$error = 0;
		$this->db->begin();

		if (isModEnabled("bank")) {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$acc = new Account($this->db);
			$acc->fetch($accountid);

			$total = $this->amount_capital;
			if ($mode == 'payment_loan') {
				$total = -$total;
			}

			// Insert payment into llx_bank
			$bank_line_id = $acc->addline(
				$this->datep,
				$this->paymenttype, // Payment mode ID or code ("CHQ or VIR for example") it's integer in db
				$label,
				$total,
				$this->num_payment,
				0,
				$user,
				$emetteur_nom,
				$emetteur_banque
			);

			// Update fk_bank into llx_paiement.
			// We know the payment who generated the account write
			if ($bank_line_id > 0) {
				$result = $this->update_fk_bank($bank_line_id);
				if ($result <= 0) {
					$error++;
					dol_print_error($this->db);
				}

				// Add link 'payment_loan' in bank_url between payment and bank transaction
				$url = '';
				if ($mode == 'payment_loan') {
					$url = DOL_URL_ROOT.'/loan/payment/card.php?id=';
				}
				if ($url) {
					$result = $acc->add_url_line($bank_line_id, $this->id, $url, '(payment)', $mode);
					if ($result <= 0) {
						$error++;
						dol_print_error($this->db);
					}
				}


				// Add link 'loan' in bank_url between invoice and bank transaction (for each invoice concerned by payment)
				if ($mode == 'payment_loan') {
					$result = $acc->add_url_line($bank_line_id, $fk_loan, DOL_URL_ROOT.'/loan/card.php?id=', ($this->label ? $this->label : ''), 'loan');
					if ($result <= 0) {
						dol_print_error($this->db);
					}
				}
			} else {
				$this->error = $acc->error;
				$error++;
			}
		}


		// Set loan payment started if no set
		if (!$error) {
			require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
			$loan = new Loan($this->db);
			$loan->fetch($fk_loan);
			if ($loan->paid == $loan::STATUS_UNPAID) {
				dol_syslog(get_class($this)."::addPaymentToBank : set loan payment to started", LOG_DEBUG);
				if ($loan->setStarted($user) < 1) {
					$error++;
					dol_print_error($this->db);
				}
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


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update link between loan's payment and the line generate in llx_bank
	 *
	 *  @param	int		$id_bank         Id if bank
	 *  @return	int			             >0 if OK, <=0 if KO
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_loan SET fk_bank = ".((int) $id_bank)." WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_fk_bank", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->fk_bank = ((int) $id_bank);
			return 1;
		} else {
			$this->error = $this->db->error();
			return 0;
		}
	}

	/**
	 *  Return clickable name (with eventually a picto)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=No picto
	 * 	@param	int		$maxlen						Max length label
	 *	@param	int  	$notooltip					1=Disable tooltip
	 *	@param	string	$moretitle					Add more text to title tooltip
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $maxlen = 0, $notooltip = 0, $moretitle = '', $save_lastsearch_value = -1)
	{
		global $langs, $conf, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$label = '<u>'.$langs->trans("Loan").'</u>';
		if (!empty($this->id)) {
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->id;
		}
		if ($moretitle) {
			$label .= ' - '.$moretitle;
		}

		$url = DOL_URL_ROOT.'/loan/payment/card.php?id='.$this->id;

		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}

		$linkstart = '<a href="'.$url.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}
}
