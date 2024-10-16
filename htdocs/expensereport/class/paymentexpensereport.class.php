<?php
/* Copyright (C) 2015-2017  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Nicolas ZABOURI  <info@inovea-conseil.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *  \file       htdocs/expensereport/class/paymentexpensereport.class.php
 *  \ingroup    Expense Report
 *  \brief      File of class to manage payment of expense report
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage payments of expense report
 */
class PaymentExpenseReport extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'payment_expensereport';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'payment_expensereport';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int ID
	 */
	public $fk_expensereport;

	/**
	 * @var int|string
	 */
	public $datec = '';

	/**
	 * @var int|string
	 */
	public $datep = '';
	/**
	 * @var float|int
	 */
	public $amount; // Total amount of payment
	/**
	 * @var array<float|int>
	 */
	public $amounts = array(); // Array of amounts

	/**
	 * @var int ID
	 */
	public $fk_typepayment;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @var int ID
	 */
	public $fk_bank;

	/**
	 * @var int ID
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
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
	public $bank_account;

	/**
	 * @var int
	 */
	public $bank_line;

	/**
	 * @var string
	 */
	public $label;


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
	 *  Create payment of expense report into database.
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
			$this->error = 'ErrorBadValueForParameterCreatePaymentExpenseReport';
			return -1;
		}

		// Clean parameters
		if (isset($this->fk_expensereport)) {
			$this->fk_expensereport = (int) $this->fk_expensereport;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->fk_typepayment)) {
			$this->fk_typepayment = (int) $this->fk_typepayment;
		}
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note)) {
			$this->note = trim($this->note);
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = ((int) $this->fk_bank);
		}
		if (isset($this->fk_user_creat)) {
			$this->fk_user_creat = ((int) $this->fk_user_creat);
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = ((int) $this->fk_user_modif);
		}

		$totalamount = 0;
		foreach ($this->amounts as $key => $value) {  // How payment is dispatch
			$newvalue = (float) price2num($value, 'MT');
			$this->amounts[$key] = $newvalue;
			$totalamount += $newvalue;
		}
		// $totalamount = (float) price2num($totalamount);

		// Check parameters
		if ($totalamount == 0) {
			return -1; // On accepte les montants negatifs pour les rejets de prelevement mais pas null
		}


		$this->db->begin();

		if ($totalamount != 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_expensereport (fk_expensereport, datec, datep, amount,";
			$sql .= " fk_typepayment, num_payment, note, fk_user_creat, fk_bank)";
			$sql .= " VALUES ($this->fk_expensereport, '".$this->db->idate($now)."',";
			$sql .= " '".$this->db->idate($this->datep)."',";
			$sql .= " ".price2num($totalamount).",";
			$sql .= " ".((int) $this->fk_typepayment).", '".$this->db->escape($this->num_payment)."', '".$this->db->escape($this->note_public)."', ".((int) $user->id).",";
			$sql .= " 0)";	// fk_bank is ID of transaction into ll_bank

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_expensereport");
			} else {
				$error++;
			}
		}

		if ($totalamount != 0 && !$error) {
			$this->amount = $totalamount;
			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param  int		$id         Id object
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_expensereport,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.datep,";
		$sql .= " t.amount,";
		$sql .= " t.fk_typepayment,";
		$sql .= " t.num_payment,";
		$sql .= " t.note as note_public,";
		$sql .= " t.fk_bank,";
		$sql .= " t.fk_user_creat,";
		$sql .= " t.fk_user_modif,";
		$sql .= " pt.code as type_code, pt.libelle as type_label,";
		$sql .= ' b.fk_account';
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_expensereport as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pt ON t.fk_typepayment = pt.id";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->fk_expensereport = $obj->fk_expensereport;
				$this->datec			= $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->datep			= $this->db->jdate($obj->datep);
				$this->amount = $obj->amount;
				$this->fk_typepayment = $obj->fk_typepayment;
				$this->num_payment		= $obj->num_payment;
				$this->note_public		= $obj->note_public;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_creat	= $obj->fk_user_creat;
				$this->fk_user_modif	= $obj->fk_user_modif;

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

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 *  Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;

		// Clean parameters

		if (isset($this->fk_expensereport)) {
			$this->fk_expensereport = (int) $this->fk_expensereport;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->fk_typepayment)) {
			$this->fk_typepayment = (int) $this->fk_typepayment;
		}
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note)) {
			$this->note = trim($this->note);
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

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_expensereport SET";
		$sql .= " fk_expensereport=".(isset($this->fk_expensereport) ? $this->fk_expensereport : "null").",";
		$sql .= " datec=".(dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " tms=".(dol_strlen((string) $this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql .= " datep=".(dol_strlen($this->datep) != 0 ? "'".$this->db->idate($this->datep)."'" : 'null').",";
		$sql .= " amount=".(isset($this->amount) ? $this->amount : "null").",";
		$sql .= " fk_typepayment=".(isset($this->fk_typepayment) ? $this->fk_typepayment : "null").",";
		$sql .= " num_payment=".(isset($this->num_payment) ? "'".$this->db->escape($this->num_payment)."'" : "null").",";
		$sql .= " note=".(isset($this->note) ? "'".$this->db->escape($this->note)."'" : "null").",";
		$sql .= " fk_bank=".(isset($this->fk_bank) ? $this->fk_bank : "null").",";
		$sql .= " fk_user_creat=".(isset($this->fk_user_creat) ? $this->fk_user_creat : "null").",";
		$sql .= " fk_user_modif=".(isset($this->fk_user_modif) ? $this->fk_user_modif : "null");
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

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 *  Delete object in database
	 *
	 *  @param  User	$user        	User that delete
	 *  @param  int		$notrigger		0=launch triggers after, 1=disable triggers
	 *  @return int						Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;

		$this->db->begin();

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url";
			$sql .= " WHERE type='payment_expensereport' AND url_id=".((int) $this->id);

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_expensereport";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
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
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		    User making the clone
	 *	@param	int		$fromid     	Id of object to clone
	 * 	@return	int						New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error = 0;

		$object = new PaymentExpenseReport($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error++;
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
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
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		//global $langs;

		return '';
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;

		$this->fk_expensereport = 0;
		$this->datec = dol_now();
		$this->tms = dol_now();
		$this->datep = dol_now();
		$this->amount = 100;
		$this->fk_typepayment = 0;
		$this->num_payment = '123456';
		$this->note_public = 'Public note';
		$this->note_private = 'Private note';
		$this->fk_bank = 0;
		$this->fk_user_creat = 0;
		$this->fk_user_modif = 0;

		return 1;
	}


	/**
	 *      Add record into bank for payment with links between this bank record and invoices of payment.
	 *      All payment properties must have been set first like after a call to create().
	 *
	 *      @param	User	$user               Object of user making payment
	 *      @param  string	$mode               'payment_expensereport'
	 *      @param  string	$label              Label to use in bank record
	 *      @param  int		$accountid          Id of bank account to do link with
	 *      @param  string	$emetteur_nom       Name of transmitter
	 *      @param  string	$emetteur_banque    Name of bank
	 *      @return int                 		Return integer <0 if KO, >0 if OK
	 */
	public function addPaymentToBank($user, $mode, $label, $accountid, $emetteur_nom, $emetteur_banque)
	{
		global $langs;

		$error = 0;

		if (isModEnabled("bank")) {
			include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$acc = new Account($this->db);
			$acc->fetch($accountid);

			//Fix me field
			$total = $this->amount;

			if ($mode == 'payment_expensereport') {
				$amount = $total;
			}

			// Insert payment into llx_bank
			$bank_line_id = $acc->addline(
				$this->datep,
				$this->fk_typepayment, // Payment mode id or code ("CHQ or VIR for example")
				$label,
				-$amount,
				$this->num_payment,
				'',
				$user,
				$emetteur_nom,
				$emetteur_banque
			);

			// Update fk_bank in llx_paiement.
			// So we will know the payment that has generated the bank transaction
			if ($bank_line_id > 0) {
				$result = $this->update_fk_bank($bank_line_id);
				if ($result <= 0) {
					$error++;
					dol_print_error($this->db);
				}

				// Add link 'payment', 'payment_supplier', 'payment_expensereport' in bank_url between payment and bank transaction
				$url = '';
				if ($mode == 'payment_expensereport') {
					$url = DOL_URL_ROOT.'/expensereport/payment/card.php?rowid=';
				}
				if ($url) {
					$result = $acc->add_url_line($bank_line_id, $this->id, $url, '(paiement)', $mode);
					if ($result <= 0) {
						$error++;
						dol_print_error($this->db);
					}
				}

				// Add link 'user' in bank_url between user and bank transaction
				if (!$error) {
					foreach ($this->amounts as $key => $value) {  // We should have always same user but we loop in case of.
						if ($mode == 'payment_expensereport') {
							$fuser = new User($this->db);
							$fuser->fetch($key);

							$result = $acc->add_url_line(
								$bank_line_id,
								$fuser->id,
								DOL_URL_ROOT.'/user/card.php?id=',
								$fuser->getFullName($langs),
								'user'
							);
							if ($result <= 0) {
								$this->error = $this->db->lasterror();
								dol_syslog(get_class($this).'::addPaymentToBank '.$this->error);
								$error++;
							}
						}
					}
				}
			} else {
				$this->error = $acc->error;
				$this->errors = $acc->errors;
				$error++;
			}
		}

		if (!$error) {
			return 1;
		} else {
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update link between the expense report payment and the generated line in llx_bank
	 *
	 *  @param	int		$id_bank         Id if bank
	 *  @return	int			             >0 if OK, <=0 if KO
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_expensereport SET fk_bank = ".((int) $id_bank)." WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_fk_bank", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return 1;
		} else {
			$this->error = $this->db->error();
			return 0;
		}
	}

	/**
	 *  Return clickable name (with picto eventually)
	 *
	 *  @param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param	int		$maxlen			Longueur max libelle
	 *  @return	string					Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlen = 0)
	{
		global $langs, $hookmanager;

		$result = '';

		if (empty($this->ref)) {
			$this->ref = $this->label;
		}
		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Payment").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		if (!empty($this->ref)) {
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		}
		if (!empty($this->datep)) {
			$label .= '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->datep, 'dayhour');
		}

		if (!empty($this->id)) {
			$link = '<a href="'.DOL_URL_ROOT.'/expensereport/payment/card.php?id='.$this->id.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend = '</a>';

			if ($withpicto) {
				$result .= ($link.img_object($label, 'payment', 'class="classfortooltip"').$linkend.' ');
			}
			if ($withpicto && $withpicto != 2) {
				$result .= ' ';
			}
			if ($withpicto != 2) {
				$result .= $link.($maxlen ? dol_trunc($this->ref, $maxlen) : $this->ref).$linkend;
			}
		}
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

	/**
	 *    Tab information on object
	 *
	 *    @param   int     $id      Payment id
	 *    @return  void
	 */
	public function info($id)
	{
		$sql = 'SELECT e.rowid, e.datec, e.fk_user_creat, e.fk_user_modif, e.tms';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'payment_expensereport as e';
		$sql .= ' WHERE e.rowid = '.((int) $id);

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array{string,mixed}		$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'datep')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Date").'</span> : <span class="info-box-label">'.dol_print_date($this->db->jdate($this->datep), 'dayhour').'</span>';
		}
		if (property_exists($this, 'fk_typepayment')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Type").'</span> : <span class="info-box-label">'.$this->fk_typepayment.'</span>';
		}
		if (property_exists($this, 'fk_bank') && !is_null($this->fk_bank)) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("BankAccount").'</span> : <span class="info-box-label">'.$this->fk_bank.'</span>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Amount").'</span> : <span class="info-box-label amount">'.price($this->amount).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
