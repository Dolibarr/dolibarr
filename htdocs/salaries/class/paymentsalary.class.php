<?php
/* Copyright (C) 2011-2022  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2021       Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
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
 *  \file       htdocs/salaries/class/paymentsalary.class.php
 *  \ingroup    salaries
 *  \brief      File of class to manage payment of salaries
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';


/**
 *	Class to manage payments of salaries
 */
class PaymentSalary extends CommonObject
{
	/**
	 * @var string 			ID to identify managed object
	 */
	public $element = 'payment_salary';

	/**
	 * @var string 			Name of table without prefix where object is stored
	 */
	public $table_element = 'payment_salary';

	/**
	 * @var string 			String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	/**
	 * @var int chid
	 * @deprecated
	 * @see $fk_salary
	 */
	public $chid;

	/**
	 * @var int 			ID of the salary linked to the payment
	 */
	public $fk_salary;

	/**
	 * @var int|string				Payment creation date
	 */
	public $datec = '';

	/**
	 * @var int|string		Date of payment
	 * @deprecated
	 * @see $datep
	 */
	public $datepaye = '';

	/**
	 * @var int|string		Date of payment
	 */
	public $datep = '';

	/**
	 * @deprecated
	 * @see $amount
	 */
	public $total;

	/**
	 * @var float			Total amount of payment
	 */
	public $amount;

	/**
	 * @var array			Array of amounts
	 */
	public $amounts = array();

	/**
	 * @var int 			Payment type ID
	 */
	public $fk_typepayment;

	/**
	 * @var string
	 * @deprecated
	 */
	public $num_paiement;

	/**
	 * @var string      Payment reference
	 *                  (Cheque or bank transfer reference. Can be "ABC123")
	 */
	public $num_payment;

	/**
	 * @inheritdoc
	 */
	public $fk_bank;

	/**
	 * @var int				ID of bank_line
	 */
	public $bank_line;

	/**
	 * @var int				ID of the user who created the payment
	 */
	public $fk_user_author;

	/**
	 * @var int				ID of the user who modified the payment
	 */
	public $fk_user_modif;

	/**
	 * @var int				Payment type
	 */
	public $type_code;

	/**
	 * @var int				Payment label
	 */
	public $type_label;

	/**
	 * @var int				Bank account description
	 */
	public $bank_account;

	/**
	 * @var int|string		Payment validation date
	 */
	public $datev = '';

	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id'),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'enabled' => 1, 'position' => 10, 'visible' => 1, 'index' => 1, 'comment' => "Reference of object"),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 20),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => 0, 'position' => 30),
		'datep' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'visible' => 0, 'position' => 40, 'comment' => 'Date'),
		'datev' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'visible' => 0,  'position' => 50, 'comment' => 'Date'),
		'fk_user' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Employee', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 1, 'picto' => 'user'),
		'salary' => array('type' => 'double(24,8)', 'label' => 'salary', 'enabled' => 1, 'visible' => 0, 'position' => 70),
		'amount' => array('type' => 'double(24,8)', 'label' => 'Amount', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 80),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => "isModEnabled('project')", 'visible' => 0, 'position' => 90),
		'fk_typepayment' => array('type' => 'typepayment', 'label' => 'DefaultPaymentMode', 'enabled' => 1, 'visible' => 1, 'position' => 100, 'comment' => 'Payment type'),
		'num_payment' => array('type' => 'string', 'label' => 'Reference', 'enabled' => 1, 'visible' => 0, 'position' => 110, 'comment' => 'Reference'),
		'label' => array('type' => 'varchar(255)', 'label' => 'Label', 'enabled' => 1, 'position' => 120, 'notnull' => 0, 'visible' => 1),
		'datesp' => array('type' => 'date', 'label' => 'DateStart', 'enabled' => 1, 'visible' => 1, 'position' => 130, 'comment' => 'Date'),
		'dateep' => array('type' => 'date', 'label' => 'DateEnd', 'enabled' => 1, 'visible' => 1, 'position' => 140, 'comment' => 'Date'),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => 0, 'position' => 150, 'index' => 1),
		'note' => array('type' => 'text', 'label' => 'Note', 'enabled' => 1, 'position' => 160, 'visible' => 0,),
		'fk_bank' => array('type' => 'integer', 'label' => 'BankId', 'enabled' => 1, 'visible' => 0, 'position' => 170),
		'paye' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 180),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => 0, 'position' => 190),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'position' => 200, 'visible' => 0,),
		'ref_ext' => array('type' => 'varchar(128)', 'label' => 'Ref ext', 'enabled' => 1, 'visible' => 0, 'position' => 210),
		'note_public' => array('type' => 'text', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 220),
	);

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
	 *  Create payment of salary into database.
	 *  Use this->amounts to have list of lines for the payment
	 *
	 *  @param      User	$user   				User making payment
	 *	@param		int		$closepaidcontrib   	1=Also close paid contributions to paid, 0=Do nothing more
	 *  @return     int     						Return integer <0 if KO, id of payment if OK
	 */
	public function create($user, $closepaidcontrib = 0)
	{
		global $conf;

		$error = 0;

		$now = dol_now();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);

		//deprecatd
		if (!empty($this->datepaye) && empty($this->datep)) {
			dol_syslog(__METHOD__.": using datepaye is deprecated, please use datep instead", LOG_WARNING);
			$this->datep = $this->datepaye;
		}

		// Validate parameters
		if (empty($this->datep)) {
			$this->error = 'ErrorBadValueForParameterCreatePaymentSalary';
			return -1;
		}

		// Clean parameters
		if (isset($this->fk_salary)) {
			$this->fk_salary = (int) $this->fk_salary;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->fk_typepayment)) {
			$this->fk_typepayment = (int) $this->fk_typepayment;
		}
		if (isset($this->num_paiement)) {
			$this->num_paiement = trim($this->num_paiement);
		} // deprecated
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note)) {
			$this->note = trim($this->note);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = (int) $this->fk_bank;
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = (int) $this->fk_user_author;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}

		$totalamount = 0;
		foreach ($this->amounts as $key => $value) {  // How payment is dispatch
			$newvalue = (float) price2num($value, 'MT');
			$this->amounts[$key] = $newvalue;
			$totalamount += $newvalue;
		}

		// Check parameters
		$totalamount = (float) price2num($totalamount, 'MT'); // this is to ensure the following test is no biaised by a potential float equal to 0.0000000000001
		if ($totalamount == 0) {
			return -1;
		} // On accepte les montants negatifs pour les rejets de prelevement mais pas null


		$this->db->begin();

		if ($totalamount != 0) {
			// Insert array of amounts
			foreach ($this->amounts as $key => $amount) {
				$salary_id = $key;
				if (is_numeric($amount) && !empty($amount)) {
					// We can have n payments for 1 salary but we can't have 1 payments for n salaries (for invoices link is n-n, not for salaries).
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."payment_salary (entity, fk_salary, datec, datep, amount,";
					$sql .= " fk_typepayment, num_payment, note, fk_user_author, fk_bank)";
					$sql .= " VALUES (".((int) $conf->entity).", ".((int) $salary_id).", '".$this->db->idate($now)."',";
					$sql .= " '".$this->db->idate($this->datep)."',";
					$sql .= " ".((float) $amount).",";
					$sql .= " ".((int) $this->fk_typepayment).", '".$this->db->escape($this->num_payment)."', '".$this->db->escape($this->note)."', ".((int) $user->id).",";
					$sql .= " 0)";

					$resql = $this->db->query($sql);
					if ($resql) {
						$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."payment_salary");
					}

					// If we want to closed paid invoices
					if ($closepaidcontrib) {
						$tmpsalary = new Salary($this->db);
						$tmpsalary->fetch($salary_id);
						$paiement = $tmpsalary->getSommePaiement();
						//$creditnotes=$tmpsalary->getSumCreditNotesUsed();
						$creditnotes = 0;
						//$deposits=$tmpsalary->getSumDepositsUsed();
						$deposits = 0;
						$alreadypayed = price2num($paiement + $creditnotes + $deposits, 'MT');
						$remaintopay = price2num($tmpsalary->amount - $paiement - $creditnotes - $deposits, 'MT');
						if ($remaintopay == 0) {
							$result = $tmpsalary->setPaid($user);
						} else {
							dol_syslog("Remain to pay for salary id=".$salary_id." not null. We do nothing.");
						}
					}
				}
			}
		}

		$result = $this->call_trigger('PAYMENTSALARY_CREATE', $user);
		if ($result < 0) {
			$error++;
		}

		if ($totalamount != 0 && !$error) {
			$this->amount = $totalamount;
			$this->total = $totalamount; // deprecated
			$this->db->commit();
			return $this->id;	// id of the last payment inserted
		} else {
			$this->error = $this->db->error();
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
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.fk_salary,";
		$sql .= " t.datec,";
		$sql .= " t.tms,";
		$sql .= " t.datep,";
		$sql .= " t.amount,";
		$sql .= " t.fk_typepayment,";
		$sql .= " t.num_payment as num_payment,";
		$sql .= " t.note,";
		$sql .= " t.fk_bank,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.fk_user_modif,";
		$sql .= " pt.code as type_code, pt.libelle as type_label,";
		$sql .= ' b.fk_account';
		$sql .= " FROM ".MAIN_DB_PREFIX."payment_salary as t LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pt ON t.fk_typepayment = pt.id";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON t.fk_bank = b.rowid';
		$sql .= " WHERE t.rowid = ".((int) $id);
		// TODO link on entity of tax;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = $obj->rowid;

				$this->fk_salary = $obj->fk_salary;
				$this->datec = $this->db->jdate($obj->datec);
				$this->tms = $this->db->jdate($obj->tms);
				$this->datepaye = $this->db->jdate($obj->datep);
				$this->amount = $obj->amount;
				$this->fk_typepayment = $obj->fk_typepayment;
				$this->num_paiement = $obj->num_payment;
				$this->num_payment = $obj->num_payment;
				$this->note = $obj->note;
				$this->note_private = $obj->note;
				$this->fk_bank = $obj->fk_bank;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_modif = $obj->fk_user_modif;

				$this->type_code = $obj->type_code;
				$this->type_label = $obj->type_label;

				$this->bank_account   = $obj->fk_account;
				$this->bank_line      = $obj->fk_bank;
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
		global $conf, $langs;
		$error = 0;

		// Clean parameters

		if (isset($this->fk_salary)) {
			$this->fk_salary = (int) $this->fk_salary;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->fk_typepayment)) {
			$this->fk_typepayment = (int) $this->fk_typepayment;
		}
		if (isset($this->num_paiement)) {
			$this->num_paiement = trim($this->num_paiement);
		} // deprecated
		if (isset($this->num_payment)) {
			$this->num_payment = trim($this->num_payment);
		}
		if (isset($this->note)) {
			$this->note = trim($this->note);
		}
		if (isset($this->fk_bank)) {
			$this->fk_bank = (int) $this->fk_bank;
		}
		if (isset($this->fk_user_author)) {
			$this->fk_user_author = (int) $this->fk_user_author;
		}
		if (isset($this->fk_user_modif)) {
			$this->fk_user_modif = (int) $this->fk_user_modif;
		}

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_salary SET";
		$sql .= " fk_salary=".(isset($this->fk_salary) ? $this->fk_salary : "null").",";
		$sql .= " datec=".(dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " tms=".(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
		$sql .= " datep=".(dol_strlen($this->datepaye) != 0 ? "'".$this->db->idate($this->datepaye)."'" : 'null').",";
		$sql .= " amount=".(isset($this->amount) ? $this->amount : "null").",";
		$sql .= " fk_typepayment=".(isset($this->fk_typepayment) ? $this->fk_typepayment : "null").",";
		$sql .= " num_payment=".(isset($this->num_payment) ? "'".$this->db->escape($this->num_payment)."'" : "null").",";
		$sql .= " note=".(isset($this->note) ? "'".$this->db->escape($this->note)."'" : "null").",";
		$sql .= " fk_bank=".(isset($this->fk_bank) ? ((int) $this->fk_bank) : "null").",";
		$sql .= " fk_user_author=".(isset($this->fk_user_author) ? ((int) $this->fk_user_author) : "null").",";
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

		dol_syslog(get_class($this)."::delete");

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
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_salary";
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

		$object = new PaymentSalary($this->db);

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
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->fk_salary = 0;
		$this->datec = '';
		$this->tms = dol_now();
		$this->datepaye = dol_now();
		$this->amount = 0.0;
		$this->fk_typepayment = 0;
		$this->num_payment = '';
		$this->note_private = '';
		$this->note_public = '';
		$this->fk_bank = 0;
		$this->fk_user_author = 0;
		$this->fk_user_modif = 0;

		return 1;
	}


	/**
	 *      Add record into bank for payment with links between this bank record and invoices of payment.
	 *      All payment properties must have been set first like after a call to create().
	 *
	 *      @param	User	$user               Object of user making payment
	 *      @param  string	$mode               'payment_salary'
	 *      @param  string	$label              Label to use in bank record
	 *      @param  int		$accountid          Id of bank account to do link with
	 *      @param  string	$emetteur_nom       Name of transmitter
	 *      @param  string	$emetteur_banque    Name of bank
	 *      @return int                 		Return integer <0 if KO, >0 if OK
	 */
	public function addPaymentToBank($user, $mode, $label, $accountid, $emetteur_nom, $emetteur_banque)
	{
		global $langs;

		// Clean data
		$this->num_payment = trim($this->num_payment ? $this->num_payment : $this->num_paiement);

		$error = 0;

		if (isModEnabled("bank")) {
			include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$acc = new Account($this->db);
			$acc->fetch($accountid);

			$total = $this->amount;

			// Insert payment into llx_bank
			$bank_line_id = $acc->addline(
				$this->datep,
				$this->fk_typepayment, // Payment mode id or code ("CHQ or VIR for example")
				$label,
				-$total,
				$this->num_payment,
				'',
				$user,
				$emetteur_nom,
				$emetteur_banque,
				'',
				$this->datev
			);

			// Update fk_bank into llx_paiement_salary.
			// so we know the payment that was used to generated the bank entry.
			if ($bank_line_id > 0) {
				$result = $this->update_fk_bank($bank_line_id);
				if ($result <= 0) {
					$error++;
					dol_print_error($this->db);
				}

				// Add link 'payment_salary' in bank_url between payment and bank transaction
				$url = '';
				if ($mode == 'payment_salary') {
					$url = DOL_URL_ROOT.'/salaries/payment_salary/card.php?id=';
				}

				if ($url) {
					$result = $acc->add_url_line($bank_line_id, $this->id, $url, '(paiement)', $mode);
					if ($result <= 0) {
						$error++;
						dol_print_error($this->db);
					}
				}

				// Add link 'user' in bank_url between user and bank transaction
				foreach ($this->amounts as $key => $value) {
					if (!$error) {
						if ($mode == 'payment_salary') {
							$salary = new Salary($this->db);
							$salary->fetch($key);
							$salary->fetch_user($salary->fk_user);

							$fuser = $salary->user;

							if ($fuser->id > 0) {
								$result = $acc->add_url_line(
									$bank_line_id,
									$fuser->id,
									DOL_URL_ROOT.'/user/card.php?id=',
									$fuser->getFullName($langs),
									'user'
								);
							}
							if ($result <= 0) {
								$this->error = $this->db->lasterror();
								dol_syslog(get_class($this) . '::addPaymentToBank ' . $this->error);
								$error++;
							}
						}
					}
				}
			} else {
				$this->error = $acc->error;
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
	 *  Mise a jour du lien entre le paiement de  salaire et la ligne dans llx_bank generee
	 *
	 *  @param	int		$id_bank         Id if bank
	 *  @return	int			             >0 if OK, <=0 if KO
	 */
	public function update_fk_bank($id_bank)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX."payment_salary SET fk_bank = ".((int) $id_bank)." WHERE rowid = ".((int) $this->id);

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
	 *	Updates the payment date.
	 *  Old name of function is update_date()
	 *
	 *  @param	int	$date   		New date
	 *  @return int					Return integer <0 if KO, 0 if OK
	 */
	public function updatePaymentDate($date)
	{
		$error = 0;

		if (!empty($date)) {
			$this->db->begin();

			dol_syslog(get_class($this)."::updatePaymentDate with date = ".$date, LOG_DEBUG);

			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET datep = '".$this->db->idate($date)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->error = 'Error -1 '.$this->db->error();
			}

			$type = $this->element;

			$sql = "UPDATE ".MAIN_DB_PREFIX.'bank';
			$sql .= " SET dateo = '".$this->db->idate($date)."', datev = '".$this->db->idate($date)."'";
			$sql .= " WHERE rowid IN (SELECT fk_bank FROM ".MAIN_DB_PREFIX."bank_url WHERE type = '".$this->db->escape($type)."' AND url_id = ".((int) $this->id).")";
			$sql .= " AND rappro = 0";

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->error = 'Error -1 '.$this->db->error();
			}

			if (!$error) {
			}

			if (!$error) {
				$this->datepaye = $date;

				$this->db->commit();
				return 0;
			} else {
				$this->db->rollback();
				return -2;
			}
		}
		return -1; //no date given or already validated
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
	 *  Return the status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs; // TODO Renvoyer le libelle anglais et faire traduction a affichage

		$langs->load('compta');
		/*if ($mode == 0)
			{
			if ($status == 0) return $langs->trans('ToValidate');
			if ($status == 1) return $langs->trans('Validated');
			}
			if ($mode == 1)
			{
			if ($status == 0) return $langs->trans('ToValidate');
			if ($status == 1) return $langs->trans('Validated');
			}
			if ($mode == 2)
			{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
			}
			if ($mode == 3)
			{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4');
			}
			if ($mode == 4)
			{
			if ($status == 0) return img_picto($langs->trans('ToValidate'),'statut1').' '.$langs->trans('ToValidate');
			if ($status == 1) return img_picto($langs->trans('Validated'),'statut4').' '.$langs->trans('Validated');
			}
			if ($mode == 5)
			{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
			}
			if ($mode == 6)
			{
			if ($status == 0) return $langs->trans('ToValidate').' '.img_picto($langs->trans('ToValidate'),'statut1');
			if ($status == 1) return $langs->trans('Validated').' '.img_picto($langs->trans('Validated'),'statut4');
			}*/
		return '';
	}

	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 * 	@param	int		$maxlen						Longueur max libelle
	 *  @param  int     $notooltip      			1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlen = 0, $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		$option = '';

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element.($this->module ? '@'.$this->module : ''),
			//'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		$url = DOL_URL_ROOT.'/salaries/payment_salary/card.php?id='.$this->id;

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($url && $add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("SalaryPayment");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		if ($option == 'nolink' || empty($url)) {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="'.$url.'"';
		}
		$linkstart .= $linkclose.'>';
		if ($option == 'nolink' || empty($url)) {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array($this->element.'dao'));
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
	 * getTooltipContentArray
	 *
	 * @param array $params params to construct tooltip data
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$langs->load('salaries');
		$datas = [];

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("SalaryPayment")];
		}

		if ($user->hasRight('salaries', 'read')) {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("SalaryPayment").'</u>';
			if (isset($this->status)) {
				$datas['status'] = ' '.$this->getLibStatut(5);
			}
			$datas['Ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
			if (!empty($this->total_ttc)) {
				$datas['AmountTTC'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->datep)) {
				$datas['Date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->datep, 'dayhour', 'tzuserrel');
			} elseif (!empty($this->datepaye)) {
				$datas['Date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->datepaye, 'dayhour', 'tzuserrel');
			}
		}

		return $datas;
	}

	/**
	 *	Return clicable link of object (with eventually picto)
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
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'fk_bank') && is_numeric($this->fk_bank)) {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$account = new AccountLine($this->db);
			$account->fetch($this->fk_bank);
			$return .= ' |  <span class="info-box-label">'.$account->getNomUrl(1).'</span>';
		}
		if (property_exists($this, 'fk_user_author') && is_numeric($this->fk_user_author)) {
			$userstatic = new User($this->db);
			$userstatic->fetch($this->fk_user_author);
			$return .= '<br><span class="info-box-status">'.$userstatic->getNomUrl(1).'</span>';
		}

		if (property_exists($this, 'fk_typepayment')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("PaymentMode").'</span> : <span class="info-box-label">'.$this->fk_typepayment.'</span>';
		}
		if (property_exists($this, 'amount')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Amount").'</span> : <span class="info-box-label amount">'.price($this->amount).'</span>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
