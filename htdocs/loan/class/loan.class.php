<?php
/* Copyright (C) 2014-2018  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2018  Frédéric France      <frederic.france@netlogic.fr>
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
 *  \file       htdocs/loan/class/loan.class.php
 *  \ingroup    loan
 *  \brief      Class for loan module
 */
require_once DOL_DOCUMENT_ROOT.'/loan/class/loanschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';


/**
 *  Loan
 */
class Loan extends CommonObject implements \JsonSerializable
{
	const IN_ARREAR = 0;
	const IN_ADVANCE = 1;
	const CALC_MODES = array(
		self::IN_ARREAR => 'CalcInArrear',
		self::IN_ADVANCE => 'CalcInAdvance',
	);

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'loan';

	public $table = 'loan';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'loan';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'money-bill-alt';

	/**
	 * @var int ID
	 */
	public $rowid;

	/** @var int $datestart  timestamp */
	public $dateend;

	/** @var int $dateend  timestamp */
	public $datestart;

	/**
	 * @var string Loan label
	 */
	public $label;

	/** @var int $fk_periodicity  Points to llx_c_payment_periodicity which links Period types to their duration in months */
	public $fk_periodicity;

	/** @var int $periodicity  Duration of a Period in months */
	public $periodicity;

	/** @var int $periodicity_label  Translation key for the name of the Period duration (month, quarter, semester, year…) */
	public $periodicity_label;

	/** @var double $capital */
	public $capital;

	/** @var int $nbPeriods  Number of Periods */
	public $nbPeriods;

	/**
	 * @var double $rate  (Proportional) annual interest rate of the loan
	 *                    (= 12 * monthly rate if periodicity = 1 month)
	 *                    Expressed as a percentage.
	 *                    $myLoan->rate == 5.0;  // the annual rate is 0.05
	 */
	public $rate;

	/**
	 * @var int $calc_mode  Whether the payments are made in advance or in arrear.
	 *                         Typical loans are in advance (the borrower pays at the start of each period).
	 *                         in advance = terme à échoir
	 *                         in arrear = terme échu
	 */
	public $calc_mode;
	/**
	 * @var double $future_value  Value of the loan after the last installment of its lifecycle is paid. (usually 0)
	 * TODO: add on card + list to enable user to choose a non-zero future value + modify it
	 */
	public $future_value = 0;

	/** @var double $paid */
	public $paid;

	/** @var double $account_capital */
	public $account_capital;

	/** @var double $account_insurance */
	public $account_insurance;

	/** @var double $account_interest */
	public $account_interest;

	/** @var int $date_creation */
	public $date_creation;

	/** @var int $date_modification */
	public $date_modification;

	/** @var int $date_validation */
	public $date_validation;

	/** @var double $insurance_amount */
	public $insurance_amount;

	/**
	 * @var int Bank ID
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
	 * @var int ID
	 */
	public $fk_project;

	/**
	 * @var int totalpaid
	 */
	public $totalpaid;

	const STATUS_UNPAID = 0;
	const STATUS_PAID = 1;
	const STATUS_STARTED = 2;

	/**
	 * @var int $currency  TODO
	 */
	public $currency;
	/**
	 * @var string[] Names of fields to include when encoding the object as JSON
	 */
	protected $jsonEncodableFields = array(
		'id',
		'element',
		'dateend',
		'datestart',
		'label',
		'periodicity',
		'periodicity_label',
		'capital',
		'nbPeriods',
		'rate',
		'calc_mode',
		'future_value',
		'paid',
		'account_capital',
		'account_insurance',
		'account_interest',
		'insurance_amount',
		'currency',
	);

	// TODO: complete this fields definition to make the module compatible with the helpers shipped with ModuleBuilder
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'visible' => 2, 'index' => 1, 'css' => 'left', 'comment' => 'Id'),
		'entity' => array(),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'label' => array('type' => 'text', 'label' => 'Label', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 3,),
		'fk_bank' => array('type' => 'integer:Account:compta/bank/class/account.class.php', 'label' => 'Account', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 3,),
		'capital' => array('type' => 'price', 'label' => 'Capital', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'insurance_amount' => array('type' => 'price', 'label' => 'Insurance', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'nbPeriods' => array('type' => 'integer', 'label' => 'nbPeriods', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'rate' => array('type' => 'double', 'label' => 'rate', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'note_private' => array('type' => 'double', 'label' => 'NotePrivate', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'note_public' => array('type' => 'double', 'label' => 'NotePublic', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),

		'capital_position' => array('type' => 'double', 'label' => 'CapitalPosition', 'enabled' => 0, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'date_position' => array('type' => 'datetime', 'label' => 'DatePosition', 'enabled' => 0, 'position' => 60, 'notnull' => 1, 'visible' => 3,),

		'accountancy_account_capital' => array('type' => 'double', 'label' => 'accountancy_account_capital', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'accountancy_account_insurance' => array('type' => 'double', 'label' => 'accountancy_account_insurance', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'accountancy_account_interest' => array('type' => 'double', 'label' => 'accountancy_account_interest', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),

		'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Account', 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => 3,),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2, 'foreignkey' => 'user.rowid',),

		'fk_periodicity' => array('type' => 'integer', 'label' => 'Periodicity', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'calc_mode' => array('type' => 'integer', 'label' => 'CalcMode', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
		'future_value' => array('type' => 'price', 'label' => 'FutureValue', 'enabled' => 1, 'position' => 60, 'notnull' => 1, 'visible' => 3,),
	);


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;
		$langs->load('errors');
		$this->currency = $conf->currency; // TODO: handle multi-currency
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id		 id object
	 *  @return int				 <0 error , >=0 no error
	 */
	public function fetch($id)
	{
		$sql = /* @lang SQL */
			'SELECT l.rowid,'
			. ' l.label,'
			. ' l.capital,'
			. ' l.datestart,'
			. ' l.dateend,'
			. ' l.nbterm AS nbPeriods,'
			. ' l.rate,'
			. ' l.note_private,'
			. ' l.note_public,'
			. ' l.insurance_amount,'
			. ' l.paid,'
			. ' l.fk_bank,'
			. ' l.accountancy_account_capital,'
			. ' l.accountancy_account_insurance,'
			. ' l.accountancy_account_interest,'
			. ' l.fk_projet AS fk_project, '
			. ' l.calc_mode,'
			. ' l.fk_periodicity,'
			. ' l.future_value,'
			. ' d.value AS periodicity,'
			. ' d.label AS periodicity_label'
			. ' FROM ' . MAIN_DB_PREFIX . 'loan AS l'
			. ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_periodicity AS d'
			. '    ON d.rowid = l.fk_periodicity AND d.entity IN (' . getEntity('loan') . ')'
			. ' WHERE l.rowid = ' . intval($id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id                  = (int) $obj->rowid;
				$this->ref                 = $obj->rowid;
				$this->datestart           = (int) $this->db->jdate($obj->datestart);
				$this->dateend             = (int) $this->db->jdate($obj->dateend);
				$this->label               = $obj->label;
				$this->capital             = (double) $obj->capital;
				$this->nbPeriods           = (int) $obj->nbPeriods;
				$this->rate                = (double) $obj->rate;
				$this->note_private        = $obj->note_private;
				$this->note_public         = $obj->note_public;
				$this->insurance_amount    = (double) $obj->insurance_amount;
				$this->paid                = (bool) $obj->paid;
				$this->fk_bank             = $obj->fk_bank;
				$this->account_capital     = $obj->accountancy_account_capital;
				$this->account_insurance   = $obj->accountancy_account_insurance;
				$this->account_interest    = $obj->accountancy_account_interest;
				$this->fk_project          = (int) $obj->fk_project;
				$this->calc_mode           = (int) $obj->calc_mode;
				$this->fk_periodicity      = (int) $obj->fk_periodicity;
				$this->future_value        = (double) $obj->future_value;
				$this->periodicity         = $obj->periodicity === null ? 1 : (int) $obj->periodicity;
				$this->periodicity_label   = $obj->periodicity_label === null ? 'Monthly' : $obj->periodicity_label;

				$this->db->free($resql);
				return 1;
			} else {
				$this->db->free($resql);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Create a loan into database
	 *
	 *  @param	User	$user	User making creation
	 *  @return int				<0 if KO, id if OK
	 */
	public function create($user)
	{
		global $conf, $langs;

		$error = 0;

		$now = dol_now();

		// clean parameters
		$newcapital = price2num($this->capital, 'MT');
		if (empty($this->insurance_amount)) {
			$this->insurance_amount = 0;
		}
		$newinsuranceamount = price2num($this->insurance_amount, 'MT');
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->account_capital)) {
			$this->account_capital = trim($this->account_capital);
		}
		if (isset($this->account_insurance)) {
			$this->account_insurance = trim($this->account_insurance);
		}
		if (isset($this->account_interest)) {
			$this->account_interest = trim($this->account_interest);
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
		if (isset($this->fk_project)) {
			$this->fk_project = (int) $this->fk_project;
		}

		// Check parameters
		if (!($newcapital > 0) || empty($this->datestart) || empty($this->dateend)) {
			$this->error = "ErrorBadParameter";
			return -2;
		}
		if (isModEnabled('accounting') && empty($this->account_capital)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("LoanAccountancyCapitalCode"));
			return -2;
		}
		if (isModEnabled('accounting') && empty($this->account_insurance)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("LoanAccountancyInsuranceCode"));
			return -2;
		}
		if (isModEnabled('accounting') && empty($this->account_interest)) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("LoanAccountancyInterestCode"));
			return -2;
		}
		if (! in_array($this->calc_mode, array_keys(self::CALC_MODES))) {
			$this->error = $langs->trans('ErrorBadValueForParameter', $this->calc_mode, $langs->transnoentitiesnoconv('CalcMode'));

			return -2;
		}

		$this->db->begin();

		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'loan ('
			. ' label,'
			. ' fk_bank,'
			. ' capital,'
			. ' datestart,'
			. ' dateend,'
			. ' nbterm,'
			. ' rate,'
			. ' note_private,'
			. ' note_public,'
			. ' accountancy_account_capital,'
			. ' accountancy_account_insurance,'
			. ' accountancy_account_interest,'
			. ' entity,'
			. ' datec,'
			. ' fk_projet,'
			. ' fk_periodicity,'
			. ' future_value,'
			. ' fk_user_author,'
			. ' insurance_amount,'
			. ' calc_mode'
			. ')'
			. ' VALUES ('
			. " '" . $this->db->escape($this->label) . "',"
			. " '" . $this->db->escape($this->fk_bank) . "',"
			. " '" . price2num($newcapital) . "',"
			. " '" . $this->db->idate($this->datestart) . "',"
			. " '" . $this->db->idate($this->dateend) . "',"
			. " '" . $this->db->escape($this->nbPeriods) . "',"
			. " '" . $this->db->escape($this->rate) . "',"
			. " '" . $this->db->escape($this->note_private) . "',"
			. " '" . $this->db->escape($this->note_public) . "',"
			. " '" . $this->db->escape($this->account_capital) . "',"
			. " '" . $this->db->escape($this->account_insurance) . "',"
			. " '" . $this->db->escape($this->account_interest) . "',"
			. ' ' . (int) $conf->entity . ','
			. " '" . $this->db->idate($now) . "',"
			. ' ' . (empty($this->fk_project) ? 'NULL' : $this->fk_project) . ','
			. ' ' . (int) $this->fk_periodicity . ','
			. ' ' . (double) $this->future_value . ','
			. ' ' . (int) $user->id . ','
			. " '" . price2num($newinsuranceamount) . "',"
			. ' ' . (int) $this->calc_mode . ''
			. ')';

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."loan");

			//dol_syslog("Loans::create this->id=".$this->id);
			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Delete a loan
	 *
	 *  @param	User	$user	Object user making delete
	 *  @return int 			<0 if KO, >0 if OK
	 */
	public function delete($user)
	{
		$error = 0;

		$this->db->begin();

		// suppression des liens vers ou depuis cet emprunt
		if ($this->deleteObjectLinked() < 0) {
			$error++;
		}

		// Get bank transaction lines for this loan
		include_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
		$account = new Account($this->db);
		$lines_url = $account->get_url('', $this->id, 'loan');

		// Delete bank urls
		foreach ($lines_url as $line_url) {
			if (!$error) {
				$accountline = new AccountLine($this->db);
				$accountline->fetch($line_url['fk_bank']);
				$result = $accountline->delete_urls($user);
				if ($result < 0) {
					$error++;
				}
			}
		}

		// Delete payments
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."payment_loan where fk_loan=".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."loan where rowid=".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->error = $this->db->lasterror();
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
	 * Deletes all loan schedule elements associated with this loan
	 * @return int
	 */
	public function deleteSchedule() {
		$loanScheduleStatic = new LoanSchedule($this->db);
		$this->db->begin();
		$error = 0;
		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $loanScheduleStatic->table_element
			. ' WHERE fk_loan = ' . (int) $this->id;
		$resql = $this->db->query($sql);
		if (! $resql) {
			$error++;
			$this->error = __FILE__ . ':' . __LINE__ . ":\n" . $this->db->lasterror();
			$this->errors[] = $this->error;
		}
		if ($error) {
			$this->db->rollback();
			return -$error;
		}
		$this->db->commit();
		return 1;
	}

	/**
	 *  Update loan
	 *
	 *  @param	User	$user	User who modified
	 *  @return int				<0 if error, >0 if ok
	 */
	public function update($user)
	{
		$this->db->begin();

		if (! is_numeric($this->nbPeriods)) {
			$this->errors[] = 'BadValueForParameterForNbTerm';

			return -1;
		}
		if (! in_array($this->calc_mode, array_keys(self::CALC_MODES))) {
			$this->errors[] = $langs->trans('ErrorBadValueForParameter', $this->calc_mode, $langs->transnoentitiesnoconv('CalcMode'));

			return -1;
		}

		$sql = /* @lang SQL */
			'UPDATE ' . MAIN_DB_PREFIX . 'loan'
			. ' SET'
			. " label                         = '" . $this->db->escape($this->label) . "',"
			. " capital                       = '" . price2num($this->db->escape($this->capital)) . "',"
			. " datestart                     = '" . $this->db->idate($this->datestart) . "',"
			. " dateend                       = '" . $this->db->idate($this->dateend) . "',"
			. ' nbterm                        = ' . $this->nbPeriods . ','
			. ' rate                          = ' . $this->db->escape($this->rate) . ','
			. ' fk_periodicity                = ' . (int) $this->fk_periodicity . ','
			. ' future_value                  = ' . (double) $this->future_value . ','
			. ' calc_mode                     = ' . (int) $this->calc_mode . ','
			. " accountancy_account_capital   = '" . $this->db->escape($this->account_capital) . "',"
			. " accountancy_account_insurance = '" . $this->db->escape($this->account_insurance) . "',"
			. " accountancy_account_interest  = '" . $this->db->escape($this->account_interest) . "',"
			. ' fk_projet                     = ' . (empty($this->fk_project) ? 'NULL' : $this->fk_project) . ','
			. ' fk_user_modif                 = ' . $user->id . ','
			. " insurance_amount              = '" . price2num($this->db->escape($this->insurance_amount)) . "'"
			. ' WHERE rowid                   = ' . (int) $this->id;

		dol_syslog(get_class($this) . '::update', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();

			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();

			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tag loan as paid completely
	 *
	 *	@deprecated
	 *  @see setPaid()
	 *  @param	User	$user	Object user making change
	 *  @return	int				<0 if KO, >0 if OK
	 */
	public function set_paid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_paid is deprecated, use setPaid instead", LOG_NOTICE);
		return $this->setPaid($user);
	}

	/**
	 *  Tag loan as paid completely
	 *
	 *  @param	User	$user	Object user making change
	 *  @return	int				<0 if KO, >0 if OK
	 */
	public function setPaid($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."loan SET";
		$sql .= " paid = ".$this::STATUS_PAID;
		$sql .= " WHERE rowid = ".((int) $this->id);
		$return = $this->db->query($sql);
		if ($return) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tag loan as payment started
	 *
	 *	@deprecated
	 *  @see setStarted()
	 *  @param	User	$user	Object user making change
	 *  @return	int				<0 if KO, >0 if OK
	 */
	public function set_started($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_started is deprecated, use setStarted instead", LOG_NOTICE);
		return $this->setStarted($user);
	}

	/**
	 *  Tag loan as payment started
	 *
	 *  @param	User	$user	Object user making change
	 *  @return	int				<0 if KO, >0 if OK
	 */
	public function setStarted($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."loan SET";
		$sql .= " paid = ".$this::STATUS_STARTED;
		$sql .= " WHERE rowid = ".((int) $this->id);
		$return = $this->db->query($sql);
		if ($return) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tag loan as payment as unpaid
	 *	@deprecated
	 *  @see setUnpaid()
	 *  @param	User	$user	Object user making change
	 *  @return	int				<0 if KO, >0 if OK
	 */
	public function set_unpaid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_unpaid is deprecated, use setUnpaid instead", LOG_NOTICE);
		return $this->setUnpaid($user);
	}

	/**
	 *  Tag loan as payment as unpaid
	 *
	 *  @param	User	$user	Object user making change
	 *  @return	int				<0 if KO, >0 if OK
	 */
	public function setUnpaid($user)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."loan SET";
		$sql .= " paid = ".$this::STATUS_UNPAID;
		$sql .= " WHERE rowid = ".((int) $this->id);
		$return = $this->db->query($sql);
		if ($return) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Return label of loan status (unpaid, paid)
	 *
	 *  @param  int		$mode			0=label, 1=short label, 2=Picto + Short label, 3=Picto, 4=Picto + Label
	 *  @param  integer	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount paid if you have it, 1 otherwise)
	 *  @return string					Label
	 */
	public function getLibStatut($mode = 0, $alreadypaid = -1)
	{
		return $this->LibStatut($this->paid, $mode, $alreadypaid);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label for given status
	 *
	 *  @param  int		$status			Id status
	 *  @param  int		$mode			0=Label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Label, 5=Short label + Picto
	 *  @param  integer	$alreadypaid	0=No payment already done, >0=Some payments were already done (we recommand to put here amount paid if you have it, 1 otherwise)
	 *  @return string					Label
	 */
	public function LibStatut($status, $mode = 0, $alreadypaid = -1)
	{
		// phpcs:enable
		global $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("customers", "bills"));

		unset($this->labelStatus); // Force to reset the array of status label, because label can change depending on parameters
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('Unpaid');
			$this->labelStatus[self::STATUS_PAID] = $langs->transnoentitiesnoconv('Paid');
			$this->labelStatus[self::STATUS_STARTED] = $langs->transnoentitiesnoconv("BillStatusStarted");
			if ($status == 0 && $alreadypaid > 0) {
				$this->labelStatus[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
			$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv('Unpaid');
			$this->labelStatusShort[self::STATUS_PAID] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_STARTED] = $langs->transnoentitiesnoconv("BillStatusStarted");
			if ($status == 0 && $alreadypaid > 0) {
				$this->labelStatusShort[self::STATUS_UNPAID] = $langs->transnoentitiesnoconv("BillStatusStarted");
			}
		}

		$statusType = 'status1';
		if (($status == 0 && $alreadypaid > 0) || $status == self::STATUS_STARTED) {
			$statusType = 'status3';
		}
		if ($status == 1) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}


	/**
	 *  Return clicable name (with eventually the picto)
	 *
	 *  @param	int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *  @param	int		$maxlen						Label max length
	 *  @param  string  $option        				On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @return	string								Chaine with URL
	 */
	public function getNomUrl($withpicto = 0, $maxlen = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		$result = '';

		$label = '<u>'.$langs->trans("ShowLoan").'</u>';
		if (!empty($this->ref)) {
			$label .= '<br><strong>'.$langs->trans('Ref').':</strong> '.$this->ref;
		}
		if (!empty($this->label)) {
			$label .= '<br><strong>'.$langs->trans('Label').':</strong> '.$this->label;
		}

		$url = DOL_URL_ROOT.'/loan/card.php?id='.$this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
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

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= ($maxlen ?dol_trunc($this->ref, $maxlen) : $this->ref);
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id'=>$this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 * 	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	public function initAsSpecimen()
	{
	    $now=dol_now();
	    // Initialise parameters
	    $this->id = 0;
	    $this->fk_bank = 1;
	    $this->label = 'SPECIMEN';
	    $this->specimen = 1;
	    $this->socid = 1;
	    $this->account_capital = 16;
	    $this->account_insurance = 616;
	    $this->account_interest = 518;
	    $this->datestart = $now;
	    $this->dateend = $now + (3600 * 24 * 365);
	    $this->note_public = 'SPECIMEN';
	    $this->capital = 20000;
	    $this->nbPeriods = 48;
	    $this->rate = 4.3;
		$this->fk_periodicity = null;
		$this->future_value = 0;
		$this->calc_mode = 1;
	}

	/**
	 *  Return amount of payments already done
	 *
	 *  @return		int		Amount of payment already done, <0 if KO
	 */
	public function getSumPayment()
	{
		$table = 'payment_loan';
		$field = 'fk_loan';

		$sql = 'SELECT sum(amount_capital) as amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql .= " WHERE ".$field." = ".((int) $this->id);

		dol_syslog(get_class($this)."::getSumPayment", LOG_DEBUG);
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
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Information on record
	 *
	 *  @param	int			$id		Id of record
	 *  @return	integer|null
	 */
	public function info($id)
	{
		$sql = 'SELECT l.rowid, l.datec, l.fk_user_author, l.fk_user_modif,';
		$sql .= ' l.tms as datem';
		$sql .= ' WHERE l.rowid = '.((int) $id);

		dol_syslog(get_class($this).'::info', LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);

				$this->db->free($result);
				return 1;
			} else {
				$this->db->free($result);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
    }

	/**
	 * @param int $periodN  Period of the installment for which the date is to be computed
	 * @return int  Computed date of the installment for period $periodN
	 */
	public function getDateOfPeriod($periodN) {
		// TODO: change calculation method if periodicity is not an integer (e.g. weekly payments)
		return dol_time_plus_duree($this->datestart, $this->periodicity * $periodN, 'm');
	}

	/**
	 * @return bool
	 */
	public function hasEcheancier() {
		static $hasEcheancier = null;
		if (! $this->id) return false; // no ID => no schedule

		// not null => already computed, use cached value
		if ($hasEcheancier !== null) return $hasEcheancier;

		// else => sql required
		$loanScheduleStatic = new LoanSchedule($this->db);
		$sql = 'SELECT COUNT(rowid) > 0 as has_echeancier FROM ' . MAIN_DB_PREFIX . $loanScheduleStatic->table_element
			. ' WHERE fk_loan = ' . (int) $this->id;
		$resql = $this->db->query($sql);
		if (!$resql) {
			// TODO: handle error
		}
		$hasEcheancier = (bool) $this->db->fetch_object($resql)->has_echeancier;
		return $hasEcheancier;
	}

	/**
	 * Secure calls to json_encode($myLoan) by encoding only business-relevant values
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		$arrayForJSON = array();
		foreach ($this->jsonEncodableFields as $attrName) {
			$arrayForJSON[$attrName] = $this->{$attrName};
		}
		return $arrayForJSON;
	}

	/**
	 * Returns an object with 'entity', 'code', 'label', 'value' representing a payment frequency (periodicity).
	 * @param int|string  $fk_periodicity
	 * @param $invalidateCache
	 * @return ?stdClass|int|array   -1 = SQL Error; null = not found; Object representing a periodicity
	 */
	public static function getPeriodicity($fk_periodicity = 1)
	{
		global $db;
		$periodicityDict = self::getAllPeriodicities();
		if (isset($periodicityDict[$fk_periodicity])) {
			return $periodicityDict[$fk_periodicity];
		}
		if (is_null($fk_periodicity)) {
			return (object) array(
				'rowid' => null,
				'entity' => 0,
				'code' => 'MONTH',
				'label' => 'Monthly',
				'value' => 1
			);
		}
		return null;
	}

	/**
	 * @param ?int $entity
	 * @param bool $onlyActive
	 * @param bool $invalidateCache
	 * @return array|int  -1 = SQL error, else array of objects with 'entity', 'code', 'label', 'value' representing a payment frequency (periodicity).
	 */
	public static function getAllPeriodicities($entity = null, $onlyActive = false, $invalidateCache = false)
	{
		global $db;
		static $periodicityDict = null;
		if ($entity !== null) $entity = intval($entity);
		if ($invalidateCache) $periodicityDict = null; // reset to force re-fetching by SQL
		if (is_null($periodicityDict)) {
			$periodicityDict = array();
			$sql = /* @lang SQL */
				'SELECT rowid, entity, code, label, value'
				. ' FROM ' . MAIN_DB_PREFIX . 'c_periodicity';

			if ($onlyActive) $sql .= ' WHERE active = 1';

			$resql = $db->query($sql);
			if (!$resql) return -1;
			while ($obj = $db->fetch_object($resql)) {
				$periodicityDict[intval($obj->rowid)] = (object) array(
					'rowid' => intval($obj->rowid),
					'entity' => intval($obj->entity),
					'code' => $obj->rowid,
					'label' => $obj->label,
					'value' => intval($obj->value),
				);
			}
		}
		if ($entity === null) return $periodicityDict;
		$filteredDict = array();
		foreach ($periodicityDict as $rowid => $obj) {
			if ($obj->entity === $entity) {
				$filteredDict[$rowid] = $obj;
			}
		}
		return $filteredDict;
	}

	/**
	 * Returns a <select> element with options to choose a calculation mode for the loan (in advance or in arrear).
	 * @param $selected
	 * @return string
	 */
	public static function getCalcModeSelector($selected)
	{
		global $langs;
		return Form::selectarray(
			'calc_mode',
			self::CALC_MODES,
			$selected,
			1,
			0,
			0,
			'',
			1,
			0,
			0,
			'',
			'',
			0,
			'',
			0,
			0
		) . img_help(
			1,
			$langs->trans('CalcInArrear') . ': '
			. $langs->trans('CalcInArrearHelp')
			. ' — '
			. $langs->trans('CalcInAdvance') . ': '
			. $langs->trans('CalcInAdvanceHelp')
		);
	}
}
