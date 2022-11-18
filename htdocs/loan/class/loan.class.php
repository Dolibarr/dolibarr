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
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *  Loan
 */
class Loan extends CommonObject
{
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

	public $datestart;
	public $dateend;

	/**
	 * @var string Loan label
	 */
	public $label;

	public $capital;
	public $nbterm;
	public $rate;
	public $paid;
	public $account_capital;
	public $account_insurance;
	public $account_interest;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * @var integer|string date_modification
	 */
	public $date_modification;

	/**
	 * @var integer|string date_validation
	 */
	public $date_validation;

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


	const STATUS_UNPAID = 0;
	const STATUS_PAID = 1;
	const STATUS_STARTED = 2;


	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Load object in memory from database
	 *
	 *  @param	int		$id		 id object
	 *  @return int				 <0 error , >=0 no error
	 */
	public function fetch($id)
	{
		$sql = "SELECT l.rowid, l.label, l.capital, l.datestart, l.dateend, l.nbterm, l.rate, l.note_private, l.note_public, l.insurance_amount,";
		$sql .= " l.paid, l.fk_bank, l.accountancy_account_capital, l.accountancy_account_insurance, l.accountancy_account_interest, l.fk_projet as fk_project";
		$sql .= " FROM ".MAIN_DB_PREFIX."loan as l";
		$sql .= " WHERE l.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->datestart = $this->db->jdate($obj->datestart);
				$this->dateend				= $this->db->jdate($obj->dateend);
				$this->label				= $obj->label;
				$this->capital				= $obj->capital;
				$this->nbterm = $obj->nbterm;
				$this->rate					= $obj->rate;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->insurance_amount = $obj->insurance_amount;
				$this->paid = $obj->paid;
				$this->fk_bank = $obj->fk_bank;

				$this->account_capital = $obj->accountancy_account_capital;
				$this->account_insurance	= $obj->accountancy_account_insurance;
				$this->account_interest		= $obj->accountancy_account_interest;
				$this->fk_project = $obj->fk_project;

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

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."loan (label, fk_bank, capital, datestart, dateend, nbterm, rate, note_private, note_public,";
		$sql .= " accountancy_account_capital, accountancy_account_insurance, accountancy_account_interest, entity,";
		$sql .= " datec, fk_projet, fk_user_author, insurance_amount)";
		$sql .= " VALUES ('".$this->db->escape($this->label)."',";
		$sql .= " '".$this->db->escape($this->fk_bank)."',";
		$sql .= " '".price2num($newcapital)."',";
		$sql .= " '".$this->db->idate($this->datestart)."',";
		$sql .= " '".$this->db->idate($this->dateend)."',";
		$sql .= " '".$this->db->escape($this->nbterm)."',";
		$sql .= " '".$this->db->escape($this->rate)."',";
		$sql .= " '".$this->db->escape($this->note_private)."',";
		$sql .= " '".$this->db->escape($this->note_public)."',";
		$sql .= " '".$this->db->escape($this->account_capital)."',";
		$sql .= " '".$this->db->escape($this->account_insurance)."',";
		$sql .= " '".$this->db->escape($this->account_interest)."',";
		$sql .= " ".$conf->entity.",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " ".(empty($this->fk_project) ? 'NULL' : $this->fk_project).",";
		$sql .= " ".$user->id.",";
		$sql .= " '".price2num($newinsuranceamount)."'";
		$sql .= ")";

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
	 *  Update loan
	 *
	 *  @param	User	$user	User who modified
	 *  @return int				<0 if error, >0 if ok
	 */
	public function update($user)
	{
		$this->db->begin();

		if (!is_numeric($this->nbterm)) {
			$this->error = 'BadValueForParameterForNbTerm';
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."loan";
		$sql .= " SET label='".$this->db->escape($this->label)."',";
		$sql .= " capital='".price2num($this->db->escape($this->capital))."',";
		$sql .= " datestart='".$this->db->idate($this->datestart)."',";
		$sql .= " dateend='".$this->db->idate($this->dateend)."',";
		$sql .= " nbterm=".((float) $this->nbterm).",";
		$sql .= " rate=".((float) $this->rate).",";
		$sql .= " accountancy_account_capital = '".$this->db->escape($this->account_capital)."',";
		$sql .= " accountancy_account_insurance = '".$this->db->escape($this->account_insurance)."',";
		$sql .= " accountancy_account_interest = '".$this->db->escape($this->account_interest)."',";
		$sql .= " fk_projet=".(empty($this->fk_project) ? 'NULL' : ((int) $this->fk_project)).",";
		$sql .= " fk_user_modif = ".$user->id.",";
		$sql .= " insurance_amount = '".price2num($this->db->escape($this->insurance_amount))."'";
		$sql .= " WHERE rowid=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
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
	 *  Tag loan as payement as unpaid
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
	 *  Tag loan as payement as unpaid
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
		global $user, $langs, $conf;

		$now = dol_now();

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
		$this->nbterm = 48;
		$this->rate = 4.3;
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
}
