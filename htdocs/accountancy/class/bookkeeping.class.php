<?php
/* Copyright (C) 2014-2017  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2015-2022  Alexandre Spangaro  <aspangaro@open-dsi.fr>
 * Copyright (C) 2015-2020  Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
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
 * \file        htdocs/accountancy/class/bookkeeping.class.php
 * \ingroup     Accountancy (Double entries)
 * \brief       File of class to manage Ledger (General Ledger and Subledger)
 */

// Class
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/fiscalyear.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';

/**
 * Class to manage Ledger (General Ledger and Subledger)
 */
class BookKeeping extends CommonObject
{
	/**
	 * @var string 	Id to identify managed objects
	 */
	public $element = 'accountingbookkeeping';

	/**
	 * @var string 	Name of table without prefix where object is stored
	 */
	public $table_element = 'accounting_bookkeeping';

	/**
	 * @var int 	Entity
	 */
	public $entity;

	/**
	 * @var BookKeepingLine[] Lines
	 */
	public $lines = array();

	/**
	 * @var int 	ID
	 */
	public $id;

	/**
	 * @var int		Date of source document, in db date NOT NULL
	 */
	public $doc_date;

	/**
	 * @var int 	Deadline for payment
	 */
	public $date_lim_reglement;

	/**
	 * @var string 	Doc type
	 */
	public $doc_type;

	/**
	 * @var string 	Doc ref
	 */
	public $doc_ref;

	/**
	 * @var int 	ID
	 */
	public $fk_doc;

	/**
	 * @var int 	ID
	 */
	public $fk_docdet;

	/**
	 * @var string 	Thirdparty code
	 */
	public $thirdparty_code;

	/**
	 * @var string|null 	Subledger account
	 */
	public $subledger_account;

	/**
	 * @var string|null 	Subledger label
	 */
	public $subledger_label;

	/**
	 * @var string  doc_type
	 */
	public $numero_compte;

	/**
	 * @var string label compte
	 */
	public $label_compte;

	/**
	 * @var string label operation
	 */
	public $label_operation;

	/**
	 * @var float FEC:Debit
	 */
	public $debit;

	/**
	 * @var float FEC:Credit
	 */
	public $credit;

	/**
	 * @var float FEC:Amount (Not necessary)
	 * @deprecated No more used (we have info into debit/credit and sens)
	 */
	public $montant;

	/**
	 * @var float FEC:Amount (Not necessary)
	 * @deprecated No more used (we have info into debit/credit and sens)
	 */
	public $amount;

	/**
	 * @var string FEC:Sens (Not necessary)
	 */
	public $sens;

	/**
	 * @var int ID
	 */
	public $fk_user_author;

	/**
	 * @var string key for import
	 */
	public $import_key;

	/**
	 * @var string code journal
	 */
	public $code_journal;

	/**
	 * @var string label journal
	 */
	public $journal_label;

	/**
	 * @var int accounting transaction id
	 */
	public $piece_num;

	/**
	 * @var BookKeepingLine[] Movement line array
	 */
	public $linesmvt = array();

	/**
	 * @var BookKeepingLine[] export line array
	 */
	public $linesexport = array();

	/**
	 * @var int|string date of movement who are noticed like exported
	 */
	public $date_export;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';

	/**
	 * @var string[]	SQL filter used for check if the bookkeeping record can be created/inserted/modified/deleted (cached)
	 */
	public static $can_modify_bookkeeping_sql_cached;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 * Create object into database
	 *
	 * @param  User	$user		User that creates
	 * @param  int	$notrigger	false=launch triggers after, true=disable triggers
	 * @return int				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters</center>
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = (int) $this->fk_doc;
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = (int) $this->fk_docdet;
		}
		if (isset($this->thirdparty_code)) {
			$this->thirdparty_code = trim($this->thirdparty_code);
		}
		if (isset($this->subledger_account)) {
			$this->subledger_account = trim($this->subledger_account);
		}
		if (isset($this->subledger_label)) {
			$this->subledger_label = trim($this->subledger_label);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->label_operation)) {
			$this->label_operation = trim($this->label_operation);
		}
		if (isset($this->debit)) {
			$this->debit = (float) $this->debit;
		}
		if (isset($this->credit)) {
			$this->credit = (float) $this->credit;
		}
		if (isset($this->montant)) {
			$this->montant = (float) $this->montant;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->journal_label)) {
			$this->journal_label = trim($this->journal_label);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = (int) $this->piece_num;
		}
		if (empty($this->debit)) {
			$this->debit = 0.0;
		}
		if (empty($this->credit)) {
			$this->credit = 0.0;
		}

		$result = $this->validBookkeepingDate($this->doc_date);
		if ($result < 0) {
			return -1;
		} elseif ($result == 0) {
			if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') == 'blockedonclosed') {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateIsOnAClosedFiscalPeriod');
			} else {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateNotOnActiveFiscalPeriod');
			}
			return -1;
		}

		// Check parameters
		if (($this->numero_compte == "") || $this->numero_compte == '-1' || $this->numero_compte == 'NotDefined') {
			$langs->loadLangs(array("errors"));
			if (in_array($this->doc_type, array('bank', 'expense_report'))) {
				$this->errors[] = $langs->trans('ErrorFieldAccountNotDefinedForBankLine', $this->fk_docdet, $this->doc_type);
			} else {
				//$this->errors[]=$langs->trans('ErrorFieldAccountNotDefinedForInvoiceLine', $this->doc_ref,  $this->label_compte);
				$mesg = $this->doc_ref.', '.$langs->trans("AccountAccounting").': '.($this->numero_compte != -1 ? $this->numero_compte : $langs->trans("Unknown"));
				if ($this->subledger_account && $this->subledger_account != $this->numero_compte) {
					$mesg .= ', '.$langs->trans("SubledgerAccount").': '.$this->subledger_account;
				}
				$this->errors[] = $langs->trans('ErrorFieldAccountNotDefinedForLine', $mesg);
			}

			return -1;
		}

		$this->db->begin();

		$this->piece_num = 0;

		// First check if line not yet already in bookkeeping.
		// Note that we must include 'doc_type - fk_doc - numero_compte - label' to be sure to have unicity of line (because we may have several lines
		// with same doc_type, fk_doc, numero_compte for 1 invoice line when using localtaxes with same account)
		// WARNING: This is not reliable, label may have been modified. This is just a small protection.
		// The page that make transfer make the test on couple (doc_type - fk_doc) only.
		$sql = "SELECT count(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE doc_type = '".$this->db->escape($this->doc_type)."'";
		$sql .= " AND fk_doc = ".((int) $this->fk_doc);
		if (getDolGlobalString('ACCOUNTANCY_ENABLE_FKDOCDET')) {
			// DO NOT USE THIS IN PRODUCTION. This will generate a lot of trouble into reports and will corrupt database (by generating duplicate entries.
			$sql .= " AND fk_docdet = ".((int) $this->fk_docdet); // This field can be 0 if record is for several lines
		}
		$sql .= " AND numero_compte = '".$this->db->escape($this->numero_compte)."'";
		$sql .= " AND label_operation = '".$this->db->escape($this->label_operation)."'";
		$sql .= " AND entity = ".$conf->entity; // Do not use getEntity for accounting features

		$resql = $this->db->query($sql);

		if ($resql) {
			$row = $this->db->fetch_object($resql);
			if ($row->nb == 0) {	// Not already into bookkeeping
				// Check to know if piece_num already exists for data we try to insert to reuse the same value
				$sqlnum = "SELECT piece_num";
				$sqlnum .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
				$sqlnum .= " WHERE doc_type = '".$this->db->escape($this->doc_type)."'"; // For example doc_type = 'bank'
				$sqlnum .= " AND fk_doc = ".((int) $this->fk_doc);
				if (getDolGlobalString('ACCOUNTANCY_ENABLE_FKDOCDET')) {
					// fk_docdet is rowid into llx_bank or llx_facturedet or llx_facturefourndet, or ...
					$sqlnum .= " AND fk_docdet = ".((int) $this->fk_docdet);
				}
				$sqlnum .= " AND doc_ref = '".$this->db->escape($this->doc_ref)."'"; // ref of source object
				$sqlnum .= " AND entity = ".$conf->entity; // Do not use getEntity for accounting features

				dol_syslog(get_class($this).":: create sqlnum=".$sqlnum, LOG_DEBUG);
				$resqlnum = $this->db->query($sqlnum);
				if ($resqlnum) {
					$objnum = $this->db->fetch_object($resqlnum);
					$this->piece_num = $objnum->piece_num;
				}

				dol_syslog(get_class($this)."::create this->piece_num=".$this->piece_num, LOG_DEBUG);
				if (empty($this->piece_num)) {
					$sqlnum = "SELECT MAX(piece_num)+1 as maxpiecenum";
					$sqlnum .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
					$sqlnum .= " WHERE entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

					$resqlnum = $this->db->query($sqlnum);
					if ($resqlnum) {
						$objnum = $this->db->fetch_object($resqlnum);
						$this->piece_num = $objnum->maxpiecenum;
					}
					dol_syslog(get_class($this).":: create now this->piece_num=".$this->piece_num, LOG_DEBUG);
				}
				if (empty($this->piece_num)) {
					$this->piece_num = 1;
				}

				$now = dol_now();

				$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
				$sql .= "doc_date";
				$sql .= ", date_lim_reglement";
				$sql .= ", doc_type";
				$sql .= ", doc_ref";
				$sql .= ", fk_doc";
				$sql .= ", fk_docdet";
				$sql .= ", thirdparty_code";
				$sql .= ", subledger_account";
				$sql .= ", subledger_label";
				$sql .= ", numero_compte";
				$sql .= ", label_compte";
				$sql .= ", label_operation";
				$sql .= ", debit";
				$sql .= ", credit";
				$sql .= ", montant";
				$sql .= ", sens";
				$sql .= ", fk_user_author";
				$sql .= ", date_creation";
				$sql .= ", code_journal";
				$sql .= ", journal_label";
				$sql .= ", piece_num";
				$sql .= ', entity';
				$sql .= ") VALUES (";
				$sql .= "'".$this->db->idate($this->doc_date)."'";
				$sql .= ", ".(isDolTms($this->date_lim_reglement) ? "'".$this->db->idate($this->date_lim_reglement)."'" : 'NULL');
				$sql .= ", '".$this->db->escape($this->doc_type)."'";
				$sql .= ", '".$this->db->escape($this->doc_ref)."'";
				$sql .= ", ".((int) $this->fk_doc);
				$sql .= ", ".((int) $this->fk_docdet);
				$sql .= ", ".(!empty($this->thirdparty_code) ? ("'".$this->db->escape($this->thirdparty_code)."'") : "NULL");
				$sql .= ", ".(!empty($this->subledger_account) ? ("'".$this->db->escape($this->subledger_account)."'") : "NULL");
				$sql .= ", ".(!empty($this->subledger_label) ? ("'".$this->db->escape($this->subledger_label)."'") : "NULL");
				$sql .= ", '".$this->db->escape($this->numero_compte)."'";
				$sql .= ", ".(!empty($this->label_compte) ? ("'".$this->db->escape($this->label_compte)."'") : "NULL");
				$sql .= ", '".$this->db->escape($this->label_operation)."'";
				$sql .= ", ".((float) $this->debit);
				$sql .= ", ".((float) $this->credit);
				$sql .= ", ".((float) $this->montant);
				$sql .= ", ".(!empty($this->sens) ? ("'".$this->db->escape($this->sens)."'") : "NULL");
				$sql .= ", '".$this->db->escape($this->fk_user_author)."'";
				$sql .= ", '".$this->db->idate($now)."'";
				$sql .= ", '".$this->db->escape($this->code_journal)."'";
				$sql .= ", ".(!empty($this->journal_label) ? ("'".$this->db->escape($this->journal_label)."'") : "NULL");
				$sql .= ", ".((int) $this->piece_num);
				$sql .= ", ".(!isset($this->entity) ? $conf->entity : $this->entity);
				$sql .= ")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

					if ($id > 0) {
						$this->id = $id;
						$result = 0;
					} else {
						$result = -2;
						$error++;
						$this->errors[] = 'Error Create Error '.$result.' lecture ID';
						dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
					}
				} else {
					$result = -1;
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				}
			} else {	// Already exists
				$result = -3;
				$error++;
				$this->error = 'BookkeepingRecordAlreadyExists';
				dol_syslog(__METHOD__.' '.$this->error, LOG_WARNING);
			}
		} else {
			$result = -5;
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		// Uncomment this and change MYOBJECT to your own tag if you
		// want this action to call a trigger.
		//if (! $error && ! $notrigger) {

		// // Call triggers
		// $result=$this->call_trigger('MYOBJECT_CREATE',$user);
		// if ($result < 0) $error++;
		// // End call triggers
		//}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $result;
		}
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$companylink = '';

		$label = '<u>'.$langs->trans("Transaction").'</u>';
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->piece_num;

		$url = DOL_URL_ROOT.'/accountancy/bookkeeping/card.php?piece_num='.$this->piece_num;

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
				$label = $langs->trans("ShowTransaction");
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
			$result .= $this->piece_num;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

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
	 * Create object into database
	 *
	 * @param  User		$user	   	User that creates
	 * @param  int		$notrigger  false=launch triggers after, true=disable triggers
	 * @param  string  	$mode 	   	Mode
	 * @return int				 	Return integer <0 if KO, Id of created object if OK
	 */
	public function createStd(User $user, $notrigger = 0, $mode = '')
	{
		global $conf, $langs;

		$langs->loadLangs(array("accountancy", "bills", "compta"));

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = (int) $this->fk_doc;
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = (int) $this->fk_docdet;
		}
		if (isset($this->thirdparty_code)) {
			$this->thirdparty_code = trim($this->thirdparty_code);
		}
		if (isset($this->subledger_account)) {
			$this->subledger_account = trim($this->subledger_account);
		}
		if (isset($this->subledger_label)) {
			$this->subledger_label = trim($this->subledger_label);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->label_operation)) {
			$this->label_operation = trim($this->label_operation);
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->journal_label)) {
			$this->journal_label = trim($this->journal_label);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = (int) $this->piece_num;
		}
		if (empty($this->debit)) {
			$this->debit = 0;
		}
		if (empty($this->credit)) {
			$this->credit = 0;
		}
		if (empty($this->montant)) {
			$this->montant = 0;
		}

		$result = $this->validBookkeepingDate($this->doc_date);
		if ($result < 0) {
			return -1;
		} elseif ($result == 0) {
			if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') == 'blockedonclosed') {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateIsOnAClosedFiscalPeriod');
			} else {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateNotOnActiveFiscalPeriod');
			}
			return -1;
		}

		$this->debit = (float) price2num($this->debit, 'MT');
		$this->credit = (float) price2num($this->credit, 'MT');
		$this->montant = (float) price2num($this->montant, 'MT');

		$now = dol_now();

		// Check parameters
		$this->journal_label = $langs->trans($this->journal_label);

		// Insert request
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.$mode.' (';
		$sql .= 'doc_date,';
		$sql .= 'date_lim_reglement,';
		$sql .= 'doc_type,';
		$sql .= 'doc_ref,';
		$sql .= 'fk_doc,';
		$sql .= 'fk_docdet,';
		$sql .= 'thirdparty_code,';
		$sql .= 'subledger_account,';
		$sql .= 'subledger_label,';
		$sql .= 'numero_compte,';
		$sql .= 'label_compte,';
		$sql .= 'label_operation,';
		$sql .= 'debit,';
		$sql .= 'credit,';
		$sql .= 'montant,';
		$sql .= 'sens,';
		$sql .= 'fk_user_author,';
		$sql .= 'date_creation,';
		$sql .= 'code_journal,';
		$sql .= 'journal_label,';
		$sql .= 'piece_num,';
		$sql .= 'entity';
		$sql .= ') VALUES (';
		$sql .= ' '.(isDolTms($this->doc_date) ? "'".$this->db->idate($this->doc_date)."'" : 'NULL').',';
		$sql .= ' '.(isDolTms($this->date_lim_reglement) ? "'".$this->db->idate($this->date_lim_reglement)."'" : 'NULL').',';
		$sql .= ' '.(!isset($this->doc_type) ? 'NULL' : "'".$this->db->escape($this->doc_type)."'").',';
		$sql .= ' '.(!isset($this->doc_ref) ? 'NULL' : "'".$this->db->escape($this->doc_ref)."'").',';
		$sql .= ' '.(empty($this->fk_doc) ? '0' : (int) $this->fk_doc).',';
		$sql .= ' '.(empty($this->fk_docdet) ? '0' : (int) $this->fk_docdet).',';
		$sql .= ' '.(!isset($this->thirdparty_code) ? 'NULL' : "'".$this->db->escape($this->thirdparty_code)."'").',';
		$sql .= ' '.(!isset($this->subledger_account) ? 'NULL' : "'".$this->db->escape($this->subledger_account)."'").',';
		$sql .= ' '.(!isset($this->subledger_label) ? 'NULL' : "'".$this->db->escape($this->subledger_label)."'").',';
		$sql .= ' '.(!isset($this->numero_compte) ? 'NULL' : "'".$this->db->escape($this->numero_compte)."'").',';
		$sql .= ' '.(!isset($this->label_compte) ? 'NULL' : "'".$this->db->escape($this->label_compte)."'").',';
		$sql .= ' '.(!isset($this->label_operation) ? 'NULL' : "'".$this->db->escape($this->label_operation)."'").',';
		$sql .= ' '.(!isset($this->debit) ? 'NULL' : $this->debit).',';
		$sql .= ' '.(!isset($this->credit) ? 'NULL' : $this->credit).',';
		$sql .= ' '.(!isset($this->montant) ? 'NULL' : $this->montant).',';
		$sql .= ' '.(!isset($this->sens) ? 'NULL' : "'".$this->db->escape($this->sens)."'").',';
		$sql .= ' '.((int) $user->id).',';
		$sql .= ' '."'".$this->db->idate($now)."',";
		$sql .= ' '.(empty($this->code_journal) ? 'NULL' : "'".$this->db->escape($this->code_journal)."'").',';
		$sql .= ' '.(empty($this->journal_label) ? 'NULL' : "'".$this->db->escape($this->journal_label)."'").',';
		$sql .= ' '.(empty($this->piece_num) ? 'NULL' : $this->db->escape($this->piece_num)).',';
		$sql .= ' '.(!isset($this->entity) ? $conf->entity : $this->entity);
		$sql .= ')';

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element.$mode);
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int 			$id 	Id object
	 * @param string|null	$ref 	Ref
	 * @param string 		$mode 	Mode ('' or 'tmp_')
	 * @return int 					Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null, $mode = '')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.doc_date,";
		$sql .= " t.date_lim_reglement,";
		$sql .= " t.doc_type,";
		$sql .= " t.doc_ref,";
		$sql .= " t.fk_doc,";
		$sql .= " t.fk_docdet,";
		$sql .= " t.thirdparty_code,";
		$sql .= " t.subledger_account,";
		$sql .= " t.subledger_label,";
		$sql .= " t.numero_compte,";
		$sql .= " t.label_compte,";
		$sql .= " t.label_operation,";
		$sql .= " t.debit,";
		$sql .= " t.credit,";
		$sql .= " t.montant as amount,";
		$sql .= " t.sens,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.import_key,";
		$sql .= " t.code_journal,";
		$sql .= " t.journal_label,";
		$sql .= " t.piece_num,";
		$sql .= " t.date_creation,";
		// In llx_accounting_bookkeeping_tmp, field date_export doesn't exist
		if ($mode != "_tmp") {
			$sql .= " t.date_export,";
		}
		$sql .= " t.date_validated as date_validation";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.$mode.' as t';
		$sql .= ' WHERE 1 = 1';
		$sql .= " AND entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features
		if (null !== $ref) {
			$sql .= " AND t.rowid = ".((int) $ref);
		} else {
			$sql .= " AND t.rowid = ".((int) $id);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->doc_date = $this->db->jdate($obj->doc_date);
				$this->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
				$this->doc_type = $obj->doc_type;
				$this->doc_ref = $obj->doc_ref;
				$this->fk_doc = $obj->fk_doc;
				$this->fk_docdet = $obj->fk_docdet;
				$this->thirdparty_code = $obj->thirdparty_code;
				$this->subledger_account = $obj->subledger_account;
				$this->subledger_label = $obj->subledger_label;
				$this->numero_compte = $obj->numero_compte;
				$this->label_compte = $obj->label_compte;
				$this->label_operation = $obj->label_operation;
				$this->debit = $obj->debit;
				$this->credit = $obj->credit;
				$this->montant = $obj->amount;
				$this->amount = $obj->amount;
				$this->sens = $obj->sens;
				$this->fk_user_author = $obj->fk_user_author;
				$this->import_key = $obj->import_key;
				$this->code_journal = $obj->code_journal;
				$this->journal_label = $obj->journal_label;
				$this->piece_num = $obj->piece_num;
				$this->date_creation = $this->db->jdate($obj->date_creation);
				$this->date_export = $this->db->jdate($obj->date_export);
				$this->date_validation = isset($obj->date_validation) ? $this->db->jdate($obj->date_validation) : '';
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}


	/**
	 * Load object in memory from the database in ->lines. Or just make a simple count if $countonly=1.
	 *
	 * @param 	string 	$sortorder 		Sort Order
	 * @param 	string 	$sortfield 		Sort field
	 * @param 	int 	$limit 			limit
	 * @param 	int 	$offset 		offset limit
	 * @param 	array<string,string> 	$filter 		filter array
	 * @param 	string 	$filtermode 	filter mode (AND or OR)
	 * @param 	int 	$option 		option (0: general account or 1: subaccount)
	 * @param	int		$countonly		Do not fill the $object->lines, return only the count.
	 * @return 	int 					Return integer <0 if KO, Number of lines if OK
	 */
	public function fetchAllByAccount($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $option = 0, $countonly = 0)
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->lines = array();
		$num = 0;

		$sql = 'SELECT';
		if ($countonly) {
			$sql .= ' COUNT(t.rowid) as nb';
		} else {
			$sql .= ' t.rowid,';
			$sql .= " t.doc_date,";
			$sql .= " t.doc_type,";
			$sql .= " t.doc_ref,";
			$sql .= " t.fk_doc,";
			$sql .= " t.fk_docdet,";
			$sql .= " t.thirdparty_code,";
			$sql .= " t.subledger_account,";
			$sql .= " t.subledger_label,";
			$sql .= " t.numero_compte,";
			$sql .= " t.label_compte,";
			$sql .= " t.label_operation,";
			$sql .= " t.debit,";
			$sql .= " t.credit,";
			$sql .= " t.montant as amount,";
			$sql .= " t.sens,";
			$sql .= " t.multicurrency_amount,";
			$sql .= " t.multicurrency_code,";
			$sql .= " t.lettering_code,";
			$sql .= " t.date_lettering,";
			$sql .= " t.fk_user_author,";
			$sql .= " t.import_key,";
			$sql .= " t.code_journal,";
			$sql .= " t.journal_label,";
			$sql .= " t.piece_num,";
			$sql .= " t.date_creation,";
			$sql .= " t.date_export,";
			$sql .= " t.date_validated as date_validation,";
			$sql .= " t.date_lim_reglement,";
			$sql .= " t.import_key";
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.doc_date>=') {
					$sqlwhere[] = "t.doc_date >= '".$this->db->idate($value)."'";
				} elseif ($key == 't.doc_date<=') {
					$sqlwhere[] = "t.doc_date <= '".$this->db->idate($value)."'";
				} elseif ($key == 't.doc_date>') {
					$sqlwhere[] = "t.doc_date > '".$this->db->idate($value)."'";
				} elseif ($key == 't.doc_date<') {
					$sqlwhere[] = "t.doc_date < '".$this->db->idate($value)."'";
				} elseif ($key == 't.numero_compte>=') {
					$sqlwhere[] = "t.numero_compte >= '".$this->db->escape($value)."'";
				} elseif ($key == 't.numero_compte<=') {
					$sqlwhere[] = "t.numero_compte <= '".$this->db->escape($value)."'";
				} elseif ($key == 't.subledger_account>=') {
					$sqlwhere[] = "t.subledger_account >= '".$this->db->escape($value)."'";
				} elseif ($key == 't.subledger_account<=') {
					$sqlwhere[] = "t.subledger_account <= '".$this->db->escape($value)."'";
				} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
					$sqlwhere[] = $this->db->sanitize($key).' = '.((int) $value);
				} elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
					$sqlwhere[] = $this->db->sanitize($key).' LIKE \''.$this->db->escape($this->db->escapeforlike($value)).'%\'';
				} elseif ($key == 't.date_creation>=') {
					$sqlwhere[] = 't.date_creation >= \''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_creation<=') {
					$sqlwhere[] = 't.date_creation <= \''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_export>=') {
					$sqlwhere[] = 't.date_export >= \''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_export<=') {
					$sqlwhere[] = 't.date_export <= \''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_validated>=') {
					$sqlwhere[] = 't.date_validated >= \''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_validated<=') {
					$sqlwhere[] = 't.date_validated <= \''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_lim_reglement>=') {
					$sqlwhere[] = 't.date_lim_reglement>=\''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_lim_reglement<=') {
					$sqlwhere[] = 't.date_lim_reglement<=\''.$this->db->idate($value).'\'';
				} elseif ($key == 't.credit' || $key == 't.debit') {
					$sqlwhere[] = natural_search($key, $value, 1, 1);
				} elseif ($key == 't.reconciled_option') {
					$sqlwhere[] = 't.lettering_code IS NULL';
				} elseif ($key == 't.code_journal' && !empty($value)) {
					if (is_array($value)) {
						$sqlwhere[] = natural_search("t.code_journal", implode(',', $value), 3, 1);
					} else {
						$sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
					}
				} elseif ($key == 't.search_accounting_code_in' && !empty($value)) {
					$sqlwhere[] = 't.numero_compte IN ('.$this->db->sanitize($value, 1).')';
				} else {
					$sqlwhere[] = natural_search($key, $value, 0, 1);
				}
			}
		}
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE entity = ' . ((int) $conf->entity); // Do not use getEntity for accounting features
		if (count($sqlwhere) > 0) {
			$sql .= " AND ".implode(" ".$this->db->sanitize($filtermode)." ", $sqlwhere);
		}
		// Filter by ledger account or subledger account
		if (!empty($option)) {
			$sql .= " AND t.subledger_account IS NOT NULL";
			$sql .= " AND t.subledger_account <> ''";
			$sortfield = 't.subledger_account'.($sortfield ? ','.$sortfield : '');
			$sortorder = 'ASC'.($sortfield ? ','.$sortfield : '');
		} else {
			$sortfield = 't.numero_compte'.($sortfield ? ','.$sortfield : '');
			$sortorder = 'ASC'.($sortorder ? ','.$sortorder : '');
		}

		if (!$countonly) {
			$sql .= $this->db->order($sortfield, $sortorder);
			if (!empty($limit)) {
				$sql .= $this->db->plimit($limit + 1, $offset);
			}
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($countonly) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$num = $obj->nb;
				}
			} else {
				$num = $this->db->num_rows($resql);

				$i = 0;
				while (($obj = $this->db->fetch_object($resql)) && (empty($limit) || $i < min($limit, $num))) {
					$line = new BookKeepingLine($this->db);

					$line->id = $obj->rowid;

					$line->doc_date = $this->db->jdate($obj->doc_date);
					$line->doc_type = $obj->doc_type;
					$line->doc_ref = $obj->doc_ref;
					$line->fk_doc = $obj->fk_doc;
					$line->fk_docdet = $obj->fk_docdet;
					$line->thirdparty_code = $obj->thirdparty_code;
					$line->subledger_account = $obj->subledger_account;
					$line->subledger_label = $obj->subledger_label;
					$line->numero_compte = $obj->numero_compte;
					$line->label_compte = $obj->label_compte;
					$line->label_operation = $obj->label_operation;
					$line->debit = $obj->debit;
					$line->credit = $obj->credit;
					$line->montant = $obj->amount; // deprecated
					$line->amount = $obj->amount;
					$line->sens = $obj->sens;
					$line->multicurrency_amount = $obj->multicurrency_amount;
					$line->multicurrency_code = $obj->multicurrency_code;
					$line->lettering_code = $obj->lettering_code;
					$line->date_lettering = $obj->date_lettering;
					$line->fk_user_author = $obj->fk_user_author;
					$line->import_key = $obj->import_key;
					$line->code_journal = $obj->code_journal;
					$line->journal_label = $obj->journal_label;
					$line->piece_num = $obj->piece_num;
					$line->date_creation = $this->db->jdate($obj->date_creation);
					$line->date_export = $this->db->jdate($obj->date_export);
					$line->date_validation = $this->db->jdate($obj->date_validation);
					// Due date
					$line->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
					$line->import_key = $obj->import_key;

					$this->lines[] = $line;

					$i++;
				}
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param string 		$sortorder                      Sort Order
	 * @param string 		$sortfield                      Sort field
	 * @param int 			$limit                          Limit
	 * @param int 			$offset                         Offset limit
	 * @param string|array<string,string> 	$filter			Filter array
	 * @param string 		$filtermode                     Filter mode (AND or OR)
	 * @param int           $showAlreadyExportMovements     Show movements when field 'date_export' is not empty (0:No / 1:Yes (Default))
	 * @return int                                          Return integer <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND', $showAlreadyExportMovements = 1)
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.doc_date,";
		$sql .= " t.doc_type,";
		$sql .= " t.doc_ref,";
		$sql .= " t.fk_doc,";
		$sql .= " t.fk_docdet,";
		$sql .= " t.thirdparty_code,";
		$sql .= " t.subledger_account,";
		$sql .= " t.subledger_label,";
		$sql .= " t.numero_compte,";
		$sql .= " t.label_compte,";
		$sql .= " t.label_operation,";
		$sql .= " t.debit,";
		$sql .= " t.credit,";
		$sql .= " t.lettering_code,";
		$sql .= " t.date_lettering,";
		$sql .= " t.montant as amount,";
		$sql .= " t.sens,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.import_key,";
		$sql .= " t.code_journal,";
		$sql .= " t.journal_label,";
		$sql .= " t.piece_num,";
		$sql .= " t.date_creation,";
		$sql .= " t.date_lim_reglement,";
		$sql .= " t.tms as date_modification,";
		$sql .= " t.date_export,";
		$sql .= " t.date_validated as date_validation";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';

		$sql .= ' WHERE t.entity = ' . ((int) $conf->entity); // Do not use getEntity for accounting features
		if ($showAlreadyExportMovements == 0) {
			$sql .= " AND t.date_export IS NULL";
		}

		// Manage filter
		if (is_array($filter)) {	// deprecated, use $filter = USF syntax
			dol_syslog("You are using a deprecated use of fetchAll. filter parameter mus be an USF string now.", LOG_WARNING);
			$sqlwhere = array();
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if ($key == 't.doc_date') {
						$sqlwhere[] = $this->db->sanitize($key).' = \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.doc_date>=') {
						$sqlwhere[] = "t.doc_date >= '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date<=') {
						$sqlwhere[] = "t.doc_date <= '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date>') {
						$sqlwhere[] = "t.doc_date > '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date<') {
						$sqlwhere[] = "t.doc_date < '".$this->db->idate($value)."'";
					} elseif ($key == 't.numero_compte>=') {
						$sqlwhere[] = "t.numero_compte >= '".$this->db->escape($value)."'";
					} elseif ($key == 't.numero_compte<=') {
						$sqlwhere[] = "t.numero_compte <= '".$this->db->escape($value)."'";
					} elseif ($key == 't.subledger_account>=') {
						$sqlwhere[] = "t.subledger_account >= '".$this->db->escape($value)."'";
					} elseif ($key == 't.subledger_account<=') {
						$sqlwhere[] = "t.subledger_account <= '".$this->db->escape($value)."'";
					} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
						$sqlwhere[] = $this->db->sanitize($key).' = '.((int) $value);
					} elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
						$sqlwhere[] = $this->db->sanitize($key).' LIKE \''.$this->db->escape($value).'%\'';
					} elseif ($key == 't.date_creation>=') {
						$sqlwhere[] = 't.date_creation >= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.date_creation<=') {
						$sqlwhere[] = 't.date_creation <= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.tms>=') {
						$sqlwhere[] = 't.tms >= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.tms<=') {
						$sqlwhere[] = 't.tms <= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.date_export>=') {
						$sqlwhere[] = 't.date_export >= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.date_export<=') {
						$sqlwhere[] = 't.date_export <= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.date_validated>=') {
						$sqlwhere[] = 't.date_validated >= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.date_validated<=') {
						$sqlwhere[] = 't.date_validated <= \''.$this->db->idate($value).'\'';
					} elseif ($key == 't.credit' || $key == 't.debit') {
						$sqlwhere[] = natural_search($key, $value, 1, 1);
					} elseif ($key == 't.code_journal' && !empty($value)) {
						if (is_array($value)) {
							$sqlwhere[] = natural_search("t.code_journal", implode(',', $value), 3, 1);
						} else {
							$sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
						}
					} else {
						$sqlwhere[] = natural_search($key, $value, 0, 1);
					}
				}
			}
			if (count($sqlwhere) > 0) {
				$sql .= ' AND '.implode(" ".$this->db->sanitize($filtermode)." ", $sqlwhere);
			}

			$filter = '';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while (($obj = $this->db->fetch_object($resql)) && (empty($limit) || $i < min($limit, $num))) {
				$line = new BookKeepingLine($this->db);

				$line->id = $obj->rowid;

				$line->doc_date = $this->db->jdate($obj->doc_date);
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->thirdparty_code = $obj->thirdparty_code;
				$line->subledger_account = $obj->subledger_account;
				$line->subledger_label = $obj->subledger_label;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->label_operation = $obj->label_operation;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->amount; // deprecated
				$line->amount = $obj->amount;
				$line->sens = $obj->sens;
				$line->lettering_code = $obj->lettering_code;
				$line->date_lettering = $obj->date_lettering;
				$line->fk_user_author = $obj->fk_user_author;
				$line->import_key = $obj->import_key;
				$line->code_journal = $obj->code_journal;
				$line->journal_label = $obj->journal_label;
				$line->piece_num = $obj->piece_num;
				$line->date_creation = $this->db->jdate($obj->date_creation);
				$line->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
				$line->date_modification = $this->db->jdate($obj->date_modification);
				$line->date_export = $this->db->jdate($obj->date_export);
				$line->date_validation = $this->db->jdate($obj->date_validation);

				$this->lines[] = $line;

				$i++;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	string 			$sortorder 		Sort Order
	 * @param 	string 			$sortfield 		Sort field
	 * @param 	int 			$limit 			Limit
	 * @param 	int 			$offset 		Offset limit
	 * @param 	string|array<string,string> $filter 	Filter
	 * @param 	string 			$filtermode 	Filter mode (AND or OR)
	 * @param 	int 			$option 		option (0: aggregate by general account or 1: aggreegate by subaccount)
	 * @return 	int 							Return integer <0 if KO, >0 if OK
	 */
	public function fetchAllBalance($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND', $option = 0)
	{
		global $conf;

		$this->lines = array();

		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = 'SELECT';
		$sql .= " t.numero_compte,";
		if (!empty($option)) {
			$sql .= " t.subledger_account,";
			$sql .= " t.subledger_label,";
		}
		$sql .= " SUM(t.debit) as debit,";
		$sql .= " SUM(t.credit) as credit";
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE entity = ' . ((int) $conf->entity); // Do not use getEntity for accounting features

		// Manage filter
		if (is_array($filter)) {
			$sqlwhere = array();
			if (count($filter) > 0) {
				foreach ($filter as $key => $value) {
					if ($key == 't.doc_date') {
						$sqlwhere[] = $this->db->sanitize($key)." = '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date>=') {
						$sqlwhere[] = "t.doc_date >= '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date<=') {
						$sqlwhere[] = "t.doc_date <= '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date>') {
						$sqlwhere[] = "t.doc_date > '".$this->db->idate($value)."'";
					} elseif ($key == 't.doc_date<') {
						$sqlwhere[] = "t.doc_date < '".$this->db->idate($value)."'";
					} elseif ($key == 't.numero_compte>=') {
						$sqlwhere[] = "t.numero_compte >= '".$this->db->escape($value)."'";
					} elseif ($key == 't.numero_compte<=') {
						$sqlwhere[] = "t.numero_compte <= '".$this->db->escape($value)."'";
					} elseif ($key == 't.subledger_account>=') {
						$sqlwhere[] = "t.subledger_account >= '".$this->db->escape($value)."'";
					} elseif ($key == 't.subledger_account<=') {
						$sqlwhere[] = "t.subledger_account <= '".$this->db->escape($value)."'";
					} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
						$sqlwhere[] = $this->db->sanitize($key)." = ".((int) $value);
					} elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
						$sqlwhere[] = $this->db->sanitize($key)." LIKE '".$this->db->escape($value)."%'";
					} elseif ($key == 't.subledger_label') {
						$sqlwhere[] = $this->db->sanitize($key)." LIKE '".$this->db->escape($value)."%'";
					} elseif ($key == 't.code_journal' && !empty($value)) {
						if (is_array($value)) {
							$sqlwhere[] = natural_search("t.code_journal", implode(',', $value), 3, 1);
						} else {
							$sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
						}
					} elseif ($key == 't.reconciled_option') {
						$sqlwhere[] = 't.lettering_code IS NULL';
					} else {
						$sqlwhere[] = $this->db->sanitize($key)." LIKE '%".$this->db->escape($this->db->escapeforlike($value))."%'";
					}
				}
			}
			if (count($sqlwhere) > 0) {
				$sql .= " AND ".implode(" ".$this->db->sanitize($filtermode)." ", $sqlwhere);
			}

			$filter = '';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		if (!empty($option)) {
			$sql .= " AND t.subledger_account IS NOT NULL";
			$sql .= " AND t.subledger_account <> ''";
			$sql .= " GROUP BY t.numero_compte, t.subledger_account, t.subledger_label";
			$sortfield = 't.subledger_account'.($sortfield ? ','.$sortfield : '');
			$sortorder = 'ASC'.($sortfield ? ','.$sortfield : '');
		} else {
			$sql .= ' GROUP BY t.numero_compte';
			$sortfield = 't.numero_compte'.($sortfield ? ','.$sortfield : '');
			$sortorder = 'ASC'.($sortorder ? ','.$sortorder : '');
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		//print $sql;
		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while (($obj = $this->db->fetch_object($resql)) && (empty($limit) || $i < min($limit, $num))) {
				$line = new BookKeepingLine($this->db);

				$line->numero_compte = $obj->numero_compte;
				//$line->label_compte = $obj->label_compte;
				if (!empty($option)) {
					$line->subledger_account = $obj->subledger_account;
					$line->subledger_label = $obj->subledger_label;
				}
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;

				$this->lines[] = $line;

				$i++;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User    	$user       User that modifies
	 * @param  int		$notrigger  false=launch triggers after, true=disable triggers
	 * @param  string  	$mode       Mode ('' or _tmp')
	 * @return int                 	Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0, $mode = '')
	{
		global $langs;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		// Clean parameters
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = (int) $this->fk_doc;
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = (int) $this->fk_docdet;
		}
		if (isset($this->thirdparty_code)) {
			$this->thirdparty_code = trim($this->thirdparty_code);
		}
		if (isset($this->subledger_account)) {
			$this->subledger_account = trim($this->subledger_account);
		}
		if (isset($this->subledger_label)) {
			$this->subledger_label = trim($this->subledger_label);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->label_operation)) {
			$this->label_operation = trim($this->label_operation);
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->journal_label)) {
			$this->journal_label = trim($this->journal_label);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = (int) $this->piece_num;
		}

		$result = $this->canModifyBookkeeping($this->id, $mode);
		if ($result < 0) {
			return -1;
		} elseif ($result == 0) {
			if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') == 'blockedonclosed') {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateIsOnAClosedFiscalPeriod');
			} else {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateNotOnActiveFiscalPeriod');
			}
			return -1;
		}

		$this->debit = (float) price2num($this->debit, 'MT');
		$this->credit = (float) price2num($this->credit, 'MT');
		$this->montant = (float) price2num($this->montant, 'MT');

		// Check parameters
		// Put here code to add a control on parameters values

		// Update request
		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element.$mode.' SET';
		$sql .= ' doc_date = '.(isDolTms($this->doc_date) ? "'".$this->db->idate($this->doc_date)."'" : 'null').',';
		$sql .= ' doc_type = '.(isset($this->doc_type) ? "'".$this->db->escape($this->doc_type)."'" : "null").',';
		$sql .= ' doc_ref = '.(isset($this->doc_ref) ? "'".$this->db->escape($this->doc_ref)."'" : "null").',';
		$sql .= ' fk_doc = '.(isset($this->fk_doc) ? $this->fk_doc : "null").',';
		$sql .= ' fk_docdet = '.(isset($this->fk_docdet) ? $this->fk_docdet : "null").',';
		$sql .= ' thirdparty_code = '.(isset($this->thirdparty_code) ? "'".$this->db->escape($this->thirdparty_code)."'" : "null").',';
		$sql .= ' subledger_account = '.(isset($this->subledger_account) ? "'".$this->db->escape($this->subledger_account)."'" : "null").',';
		$sql .= ' subledger_label = '.(isset($this->subledger_label) ? "'".$this->db->escape($this->subledger_label)."'" : "null").',';
		$sql .= ' numero_compte = '.(isset($this->numero_compte) ? "'".$this->db->escape($this->numero_compte)."'" : "null").',';
		$sql .= ' label_compte = '.(isset($this->label_compte) ? "'".$this->db->escape($this->label_compte)."'" : "null").',';
		$sql .= ' label_operation = '.(isset($this->label_operation) ? "'".$this->db->escape($this->label_operation)."'" : "null").',';
		$sql .= ' debit = '.(isset($this->debit) ? $this->debit : "null").',';
		$sql .= ' credit = '.(isset($this->credit) ? $this->credit : "null").',';
		$sql .= ' montant = '.(isset($this->montant) ? $this->montant : "null").',';
		$sql .= ' sens = '.(isset($this->sens) ? "'".$this->db->escape($this->sens)."'" : "null").',';
		$sql .= ' fk_user_author = '.(isset($this->fk_user_author) ? $this->fk_user_author : "null").',';
		$sql .= ' import_key = '.(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null").',';
		$sql .= ' code_journal = '.(isset($this->code_journal) ? "'".$this->db->escape($this->code_journal)."'" : "null").',';
		$sql .= ' journal_label = '.(isset($this->journal_label) ? "'".$this->db->escape($this->journal_label)."'" : "null").',';
		$sql .= ' piece_num = '.(isset($this->piece_num) ? $this->piece_num : "null");
		$sql .= ' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}

		// Uncomment this and change MYOBJECT to your own tag if you
		// want this action calls a trigger.
		//if (! $error && ! $notrigger) {

		// // Call triggers
		// $result=$this->call_trigger('MYOBJECT_MODIFY',$user);
		// if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
		// // End call triggers
		//}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Update accounting movement
	 *
	 * @param  string  $piece_num      Piece num
	 * @param  string  $field          Field
	 * @param  string  $value          Value
	 * @param  string  $mode           Mode ('' or _tmp')
	 * @return int                     Return integer <0 if KO, >0 if OK
	 */
	public function updateByMvt($piece_num = '', $field = '', $value = '', $mode = '')
	{
		$error = 0;

		$sql_filter = $this->getCanModifyBookkeepingSQL();
		if (!isset($sql_filter)) {
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " SET ".$this->db->sanitize($field)." = ".(is_numeric($value) ? ((float) $value) : "'".$this->db->escape($value)."'");
		$sql .= " WHERE piece_num = ".((int) $piece_num);
		$sql .= $sql_filter;

		$resql = $this->db->query($sql);

		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
		}
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param User 		$user 		User that deletes
	 * @param int 		$notrigger 	0=launch triggers after, 1=disable triggers
	 * @param string 	$mode 		Mode ('' or 'tmp_')
	 * @return int 					Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0, $mode = '')
	{
		global $langs;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$result = $this->canModifyBookkeeping($this->id, $mode);
		if ($result < 0) {
			return -1;
		} elseif ($result == 0) {
			if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') == 'blockedonclosed') {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateIsOnAClosedFiscalPeriod');
			} else {
				$this->errors[] = $langs->trans('ErrorBookkeepingDocDateNotOnActiveFiscalPeriod');
			}
			return -1;
		}

		$error = 0;

		$this->db->begin();

		// Uncomment this and change MYOBJECT to your own tag if you
		// want this action calls a trigger.
		//if (! $error && ! $notrigger) {

		// // Call triggers
		// $result=$this->call_trigger('MYOBJECT_DELETE',$user);
		// if ($result < 0) { $error++; //Do also what you must do to rollback action if trigger fail}
		// // End call triggers
		//}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.$mode;
			$sql .= ' WHERE rowid='.((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete bookkeeping by importkey
	 *
	 * @param  string		$importkey		Import key
	 * @param string $mode Mode
	 * @return int Result
	 */
	public function deleteByImportkey($importkey, $mode = '')
	{
		$this->db->begin();

		$sql_filter = $this->getCanModifyBookkeepingSQL();
		if (!isset($sql_filter)) {
			return -1;
		}

		// first check if line not yet in bookkeeping
		$sql = "DELETE";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " WHERE import_key = '".$this->db->escape($importkey)."'";
		$sql .= $sql_filter;

		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->errors[] = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::delete Error ".$this->db->lasterror(), LOG_ERR);
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Delete bookkeeping by year
	 *
	 * @param  int	  $delyear		Year to delete
	 * @param  string $journal		Journal to delete
	 * @param  string $mode 		Mode
	 * @param  int	  $delmonth     Month
	 * @return int					Return integer <0 if KO, >0 if OK
	 */
	public function deleteByYearAndJournal($delyear = 0, $journal = '', $mode = '', $delmonth = 0)
	{
		global $conf, $langs;

		if (empty($delyear) && empty($journal)) {
			$this->error = 'ErrorOneFieldRequired';
			return -1;
		}
		if (!empty($delmonth) && empty($delyear)) {
			$this->error = 'YearRequiredIfMonthDefined';
			return -2;
		}

		$sql_filter = $this->getCanModifyBookkeepingSQL();
		if (!isset($sql_filter)) {
			return -1;
		}

		$this->db->begin();

		// Delete record in bookkeeping
		$sql = "DELETE";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " WHERE 1 = 1";
		$sql .= dolSqlDateFilter('doc_date', 0, $delmonth, $delyear);
		if (!empty($journal)) {
			$sql .= " AND code_journal = '".$this->db->escape($journal)."'";
		}
		$sql .= " AND entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features
		// Exclusion of validated entries at the time of deletion
		$sql .= " AND date_validated IS NULL";
		$sql .= $sql_filter;

		// TODO: In a future we must forbid deletion if record is inside a closed fiscal period.

		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->errors[] = "Error ".$this->db->lasterror();
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Delete bookkeeping by piece number
	 *
	 * @param 	int 	$piecenum 	Piecenum to delete
	 * @param 	string 	$mode 		Mode ('' or '_tmp')
	 * @return 	int 				Result
	 */
	public function deleteMvtNum($piecenum, $mode = '')
	{
		global $conf;

		$sql_filter = $this->getCanModifyBookkeepingSQL();
		if (!isset($sql_filter)) {
			return -1;
		}

		$this->db->begin();

		// first check if line not yet in bookkeeping
		$sql = "DELETE";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " WHERE piece_num = ".(int) $piecenum;
		$sql .= " AND date_validated IS NULL";		// For security, exclusion of validated entries at the time of deletion
		$sql .= " AND entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features
		$sql .= $sql_filter;

		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->errors[] = "Error ".$this->db->lasterror();
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 * @param	User	$user		User making the clone
	 * @param   int     $fromid     Id of object to clone
	 * @return  int                 New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;
		$object = new BookKeeping($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		// Reset object
		$object->id = 0;

		// Clear fields
		// ...

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$error++;
			$this->errors = $object->errors;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
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
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimen()
	{
		global $user;

		$now = dol_now();

		$this->id = 0;
		$this->doc_date = $now;
		$this->doc_type = '';
		$this->doc_ref = '';
		$this->fk_doc = 0;
		$this->fk_docdet = 0;
		$this->thirdparty_code = 'CU001';
		$this->subledger_account = '41100001';
		$this->subledger_label = 'My customer company';
		$this->numero_compte = '411';
		$this->label_compte = 'Customer';
		$this->label_operation = 'Sales of pea';
		$this->debit = 99.9;
		$this->credit = 0.0;
		$this->amount = 0.0;
		$this->sens = 'D';
		$this->fk_user_author = $user->id;
		$this->import_key = '20201027';
		$this->code_journal = 'VT';
		$this->journal_label = 'Journal de vente';
		$this->piece_num = 1234;
		$this->date_creation = $now;

		return 1;
	}

	/**
	 * Load an accounting document into memory from database
	 *
	 * @param int $piecenum Accounting document to get
	 * @param string $mode Mode
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function fetchPerMvt($piecenum, $mode = '')
	{
		global $conf;

		$sql = "SELECT piece_num, doc_date, code_journal, journal_label, doc_ref, doc_type,";
		$sql .= " date_creation, tms as date_modification, date_validated as date_validation, date_lim_reglement, import_key";
		// In llx_accounting_bookkeeping_tmp, field date_export doesn't exist
		if ($mode != "_tmp") {
			$sql .= ", date_export";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " WHERE piece_num = ".((int) $piecenum);
		$sql .= " AND entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

		dol_syslog(__METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);

			$this->piece_num = $obj->piece_num;
			$this->code_journal = $obj->code_journal;
			$this->journal_label = $obj->journal_label;
			$this->doc_date = $this->db->jdate($obj->doc_date);
			$this->doc_ref = $obj->doc_ref;
			$this->doc_type = $obj->doc_type;
			$this->date_creation = $this->db->jdate($obj->date_creation);
			$this->date_modification = $this->db->jdate($obj->date_modification);
			if ($mode != "_tmp") {
				$this->date_export = $this->db->jdate($obj->date_export);
			}
			$this->date_validation = $this->db->jdate($obj->date_validation);
			$this->date_lim_reglement = $this->db->jdate($obj->date_lim_reglement);
			$this->import_key = $obj->import_key;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(__METHOD__.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	/**
	 * Return next movement number
	 *
	 * @param	string	$mode		Mode
	 * @return	int<1, max>|-1		Return next movement number or -1 if error
	 */
	public function getNextNumMvt($mode = '')
	{
		global $conf;

		$sql = "SELECT MAX(piece_num)+1 as max FROM ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " WHERE entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

		dol_syslog(get_class($this)."::getNextNumMvt", LOG_DEBUG);

		$result = $this->db->query($sql);

		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$result = $obj->max;
			}
			if (empty($result)) {
				$result = 1;
			}
			return $result;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::getNextNumMvt ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load all accounting lines related to a given transaction ID $piecenum
	 *
	 * @param  int     $piecenum   Id of line to get
	 * @param  string  $mode       Mode ('' or '_tmp')
	 * @return int                 Return integer <0 if KO, >0 if OK
	 */
	public function fetchAllPerMvt($piecenum, $mode = '')
	{
		global $conf;

		$sql = "SELECT rowid, doc_date, doc_type,";
		$sql .= " doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,";
		$sql .= " numero_compte, label_compte, label_operation, debit, credit,";
		$sql .= " montant as amount, sens, fk_user_author, import_key, code_journal, journal_label, piece_num,";
		$sql .= " date_creation, tms as date_modification, date_validated as date_validation";
		// In llx_accounting_bookkeeping_tmp, field date_export doesn't exist
		if ($mode != "_tmp") {
			$sql .= ", date_export";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element.$mode;
		$sql .= " WHERE piece_num = ".((int) $piecenum);
		$sql .= " AND entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

		dol_syslog(__METHOD__, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				$line = new BookKeepingLine($this->db);

				$line->id = $obj->rowid;

				$line->doc_date = $this->db->jdate($obj->doc_date);
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->thirdparty_code = $obj->thirdparty_code;
				$line->subledger_account = $obj->subledger_account;
				$line->subledger_label = $obj->subledger_label;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->label_operation = $obj->label_operation;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->amount;
				$line->amount = $obj->amount;
				$line->sens = $obj->sens;
				$line->code_journal = $obj->code_journal;
				$line->journal_label = $obj->journal_label;
				$line->piece_num = $obj->piece_num;
				$line->date_creation = $obj->date_creation;
				$line->date_modification = $obj->date_modification;
				if ($mode != "_tmp") {
					$line->date_export = $obj->date_export;
				}
				$line->date_validation = $obj->date_validation;

				$this->linesmvt[] = $line;
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(__METHOD__.$this->error, LOG_ERR);
			return -1;
		}

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Export bookkeeping
	 *
	 * @param	string	$model	Model
	 * @return	int				Result
	 */
	public function export_bookkeeping($model = 'ebp')
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT rowid, doc_date, doc_type,";
		$sql .= " doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,";
		$sql .= " numero_compte, label_compte, label_operation, debit, credit,";
		$sql .= " montant as amount, sens, fk_user_author, import_key, code_journal, piece_num,";
		$sql .= " date_validated as date_validation";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

		dol_syslog(get_class($this)."::export_bookkeeping", LOG_DEBUG);

		$resql = $this->db->query($sql);

		if ($resql) {
			$this->linesexport = array();

			$num = $this->db->num_rows($resql);
			while ($obj = $this->db->fetch_object($resql)) {
				$line = new BookKeepingLine($this->db);

				$line->id = $obj->rowid;

				$line->doc_date = $this->db->jdate($obj->doc_date);
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->thirdparty_code = $obj->thirdparty_code;
				$line->subledger_account = $obj->subledger_account;
				$line->subledger_label = $obj->subledger_label;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->label_operation = $obj->label_operation;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->amount;
				$line->amount = $obj->amount;
				$line->sens = $obj->sens;
				$line->code_journal = $obj->code_journal;
				$line->piece_num = $obj->piece_num;
				$line->date_validation = $obj->date_validation;

				$this->linesexport[] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::export_bookkeeping ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Transform transaction
	 *
	 * @param  int      $direction      If 0: tmp => real, if 1: real => tmp
	 * @param  string   $piece_num      Piece num = Transaction ref
	 * @return int                      int Return integer <0 if KO, >0 if OK
	 */
	public function transformTransaction($direction = 0, $piece_num = '')
	{
		global $conf;

		$error = 0;

		$sql_filter = $this->getCanModifyBookkeepingSQL();

		if (!isset($sql_filter)) {
			return -1;
		}

		$this->db->begin();

		if ($direction == 0) {
			$next_piecenum = $this->getNextNumMvt();
			$now = dol_now();

			if ($next_piecenum < 0) {
				$error++;
			}

			if (!$error) {
				// Delete if there is an empty line
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.'_tmp WHERE piece_num = '.((int) $piece_num).' AND entity = ' .((int) $conf->entity)." AND numero_compte IS NULL AND debit = 0 AND credit = 0";
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				}
			}

			if (!$error) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.' (doc_date, doc_type,';
				$sql .= ' doc_ref, fk_doc, fk_docdet, entity, thirdparty_code, subledger_account, subledger_label,';
				$sql .= ' numero_compte, label_compte, label_operation, debit, credit,';
				$sql .= ' montant, sens, fk_user_author, import_key, code_journal, journal_label, piece_num, date_creation)';
				$sql .= ' SELECT doc_date, doc_type,';
				$sql .= ' doc_ref, fk_doc, fk_docdet, entity, thirdparty_code, subledger_account, subledger_label,';
				$sql .= ' numero_compte, label_compte, label_operation, debit, credit,';
				$sql .= ' montant, sens, fk_user_author, import_key, code_journal, journal_label, '.((int) $next_piecenum).", '".$this->db->idate($now)."'";
				$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.'_tmp WHERE piece_num = '.((int) $piece_num).' AND numero_compte IS NOT NULL AND entity = ' .((int) $conf->entity);
				$sql .= $sql_filter;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				}
			}

			if (!$error) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.'_tmp WHERE piece_num = '.((int) $piece_num).' AND entity = ' .((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				}
			}
		} elseif ($direction == 1) {
			if (!$error) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.'_tmp WHERE piece_num = '.((int) $piece_num).' AND entity = ' .((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				}
			}

			if (!$error) {
				$sql = 'INSERT INTO '.MAIN_DB_PREFIX.$this->table_element.'_tmp (doc_date, doc_type,';
				$sql .= ' doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,';
				$sql .= ' numero_compte, label_compte, label_operation, debit, credit,';
				$sql .= ' montant, sens, fk_user_author, import_key, code_journal, journal_label, piece_num)';
				$sql .= ' SELECT doc_date, doc_type,';
				$sql .= ' doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label,';
				$sql .= ' numero_compte, label_compte, label_operation, debit, credit,';
				$sql .= ' montant, sens, fk_user_author, import_key, code_journal, journal_label, piece_num';
				$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' WHERE piece_num = '.((int) $piece_num).' AND entity = ' .((int) $conf->entity);
				$sql .= $sql_filter;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				}
			}

			if (!$error) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element.'_tmp WHERE piece_num = '.((int) $piece_num).' AND entity = ' .((int) $conf->entity);
				$sql .= $sql_filter;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
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
		/*
		$sql = "DELETE FROM ";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON aa.account_number = ab.numero_compte";
		$sql .= " AND aa.active = 1";
		$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = " . ((int) $pcgver);
		$sql .= " AND ab.entity IN (" . getEntity('accountancy') . ")";
		$sql .= " ORDER BY account_number ASC";
		*/
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of accounts with label by chart of accounts
	 *
	 * @param string     $selectid   Preselected chart of accounts
	 * @param string     $htmlname	Name of field in html form
	 * @param int<0,1>		$showempty	Add an empty field
	 * @param array<array{method:string,url:string,htmlname:string,params:array<string,string>}>	$event		Event options
	 * @param int		$select_in	Value is a aa.rowid (0 default) or aa.account_number (1)
	 * @param int		$select_out	Set value returned by select 0=rowid (default), 1=account_number
	 * @param string	$aabase		Set accounting_account base class to display empty=all or from 1 to 8 will display only account starting from this number
	 * @return string|int	String with HTML select or -1 if KO
	 */
	public function select_account($selectid, $htmlname = 'account', $showempty = 0, $event = array(), $select_in = 0, $select_out = 0, $aabase = '')
	{
		// phpcs:enable
		global $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

		$pcgver = getDolGlobalInt('CHARTOFACCOUNTS');

		$sql = "SELECT DISTINCT ab.numero_compte as account_number, aa.label as label, aa.rowid as rowid, aa.fk_pcg_version";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON aa.account_number = ab.numero_compte";
		$sql .= " AND aa.active = 1";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = ".((int) $pcgver);
		$sql .= " AND ab.entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features
		$sql .= " ORDER BY account_number ASC";

		dol_syslog(get_class($this)."::select_account", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::select_account ".$this->error, LOG_ERR);
			return "-1";
		}

		$out = ajax_combobox($htmlname, $event);

		$options = array();
		$selected = null;

		while ($obj = $this->db->fetch_object($resql)) {
			$label = length_accountg($obj->account_number).' - '.$obj->label;

			$select_value_in = $obj->rowid;
			$select_value_out = $obj->rowid;

			if ($select_in == 1) {
				$select_value_in = $obj->account_number;
			}
			if ($select_out == 1) {
				$select_value_out = $obj->account_number;
			}

			// Remember guy's we store in database llx_facturedet the rowid of accounting_account and not the account_number
			// Because same account_number can be share between different accounting_system and do have the same meaning
			if (($selectid != '') && $selectid == $select_value_in) {
				$selected = $select_value_out;
			}

			$options[$select_value_out] = $label;
		}

		$out .= Form::selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', 'maxwidth300');
		$this->db->free($resql);
		return $out;
	}

	/**
	 * Return id and description of a root accounting account.
	 * FIXME: This function takes the parent of parent to get the root account !
	 *
	 * @param 	string 	$account	Accounting account
	 * @return  array{id:int,account_number:string,label:string}|int<-1,-1>	Array with root account information (max 2 upper level), <0 if KO
	 */
	public function getRootAccount($account = null)
	{
		global $conf;
		$pcgver = getDolGlobalInt('CHARTOFACCOUNTS');

		$sql  = "SELECT root.rowid, root.account_number, root.label as label,";
		$sql .= " parent.rowid as parent_rowid, parent.account_number as parent_account_number, parent.label as parent_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_account as aa";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND asy.rowid = ".((int) $pcgver);
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as parent ON aa.account_parent = parent.rowid AND parent.active = 1";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as root ON parent.account_parent = root.rowid AND root.active = 1";
		$sql .= " WHERE aa.account_number = '".$this->db->escape($account)."'";
		$sql .= " AND aa.entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

		dol_syslog(get_class($this)."::select_account", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = '';
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
			}

			$result = array('id' => $obj->rowid, 'account_number' => $obj->account_number, 'label' => $obj->label);
			return $result;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(__METHOD__." ".$this->error, LOG_ERR);

			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Description of accounting account
	 *
	 * @param	string	$account	Accounting account
	 * @return	string|int				Account desc or -1 if KO
	 */
	public function get_compte_desc($account = null)
	{
		// phpcs:enable
		global $conf;

		$pcgver = getDolGlobalInt('CHARTOFACCOUNTS');
		$sql  = "SELECT aa.account_number, aa.label, aa.rowid, aa.fk_pcg_version, cat.label as category";
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_account as aa ";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."accounting_system as asy ON aa.fk_pcg_version = asy.pcg_version";
		$sql .= " AND aa.account_number = '".$this->db->escape($account)."'";
		$sql .= " AND asy.rowid = ".((int) $pcgver);
		$sql .= " AND aa.active = 1";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_accounting_category as cat ON aa.fk_accounting_category = cat.rowid";
		$sql .= " WHERE aa.entity = " . ((int) $conf->entity); // Do not use getEntity for accounting features

		dol_syslog(get_class($this)."::select_account", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = '';
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
			}
			if (empty($obj->category)) {
				return $obj->label;
			} else {
				return $obj->label.' ('.$obj->category.')';
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(__METHOD__." ".$this->error, LOG_ERR);
			return "-1";
		}
	}

	/**
	 * Get SQL string for check if the bookkeeping can be modified or deleted ? (cached)
	 *
	 * @param 	string		$alias		Bookkeeping alias table
	 * @param 	bool		$force		Force reload
	 * @return 	string|null				SQL filter or null if error
	 */
	public function getCanModifyBookkeepingSQL($alias = '', $force = false)
	{
		global $conf;

		$alias = trim($alias);
		$alias = !empty($alias) && strpos($alias, '.') < 0 ? $alias . "." : $alias;

		if (!isset(self::$can_modify_bookkeeping_sql_cached[$alias]) || $force) {
			$result = $this->loadFiscalPeriods($force, 'active');
			if ($result < 0) {
				return null;
			}

			$sql_list = array();
			if (!empty($conf->cache['active_fiscal_period_cached']) && is_array($conf->cache['active_fiscal_period_cached'])) {
				foreach ($conf->cache['active_fiscal_period_cached'] as $fiscal_period) {
					$sql_list[] = "('" . $this->db->idate($fiscal_period['date_start']) . "' <= ".$this->db->sanitize($alias)."doc_date AND ".$this->db->sanitize($alias)."doc_date <= '" . $this->db->idate($fiscal_period['date_end']) . "')";
				}
			}
			$sqlsanitized = implode(' OR ', $sql_list);
			self::$can_modify_bookkeeping_sql_cached[$alias] = !empty($sql_list) ? " AND (".$sqlsanitized.")" : "";
		}

		return self::$can_modify_bookkeeping_sql_cached[$alias];
	}

	/**
	 * Is the bookkeeping can be modified or deleted ?
	 *
	 * @param 	int		$id		Bookkeeping ID
	 * @param 	string 	$mode 	Mode ('' or 'tmp_')
	 * @return 	int				Return integer <0 if KO, == 0 if No, == 1 if Yes
	 */
	public function canModifyBookkeeping($id, $mode = '')
	{
		global $conf;

		if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') == 'blockedonclosed') {
			$result = $this->loadFiscalPeriods(false, 'closed');

			if ($result < 0) {
				return -1;
			}

			$bookkeeping = new BookKeeping($this->db);
			$result = $bookkeeping->fetch($id, null, $mode);
			if ($result <= 0) {
				return $result;
			}

			if (!empty($conf->cache['closed_fiscal_period_cached']) && is_array($conf->cache['closed_fiscal_period_cached'])) {
				foreach ($conf->cache['closed_fiscal_period_cached'] as $fiscal_period) {
					if ($fiscal_period['date_start'] <= $bookkeeping->doc_date && $bookkeeping->doc_date <= $fiscal_period['date_end']) {
						return 0;
					}
				}
			}

			return 1;
		} else {
			$result = $this->loadFiscalPeriods(false, 'active');
			if ($result < 0) {
				return -1;
			}

			$bookkeeping = new BookKeeping($this->db);
			$result = $bookkeeping->fetch($id, null, $mode);

			if ($result <= 0) {
				return $result;
			}
			if (!empty($conf->cache['active_fiscal_period_cached']) && is_array($conf->cache['active_fiscal_period_cached'])) {
				foreach ($conf->cache['active_fiscal_period_cached'] as $fiscal_period) {
					if (!empty($fiscal_period['date_start']) && $fiscal_period['date_start'] <= $bookkeeping->doc_date && (empty($fiscal_period['date_end']) || $bookkeeping->doc_date <= $fiscal_period['date_end'])) {
						return 1;
					}
				}
			}

			return 0;
		}
	}

	/**
	 * Is the bookkeeping date valid (on an open period or not on a closed period) ?
	 *
	 * @param 	int		$date		Bookkeeping date
	 * @return 	int					Return integer <0 if KO, == 0 if No, == 1 if date is valid for a transfer
	 */
	public function validBookkeepingDate($date)
	{
		global $conf;

		if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') == 'blockedonclosed') {
			$result = $this->loadFiscalPeriods(false, 'closed');

			if ($result < 0) {
				return -1;
			}

			if (!empty($conf->cache['closed_fiscal_period_cached']) && is_array($conf->cache['closed_fiscal_period_cached'])) {
				foreach ($conf->cache['closed_fiscal_period_cached'] as $fiscal_period) {
					if ($fiscal_period['date_start'] <= $date && $date <= $fiscal_period['date_end']) {
						return 0;
					}
				}
			}

			return 1;
		} else {
			$result = $this->loadFiscalPeriods(false, 'active');
			if ($result < 0) {
				return -1;
			}

			if (!empty($conf->cache['active_fiscal_period_cached']) && is_array($conf->cache['active_fiscal_period_cached'])) {
				foreach ($conf->cache['active_fiscal_period_cached'] as $fiscal_period) {
					if (!empty($fiscal_period['date_start']) && $fiscal_period['date_start'] <= $date && (empty($fiscal_period['date_end']) || $date <= $fiscal_period['date_end'])) {
						return 1;
					}
				}
			}

			return 0;
		}
	}

	/**
	 * Load list of active fiscal period
	 *
	 * @param 	bool	$force		Force reload
	 * @param	string	$mode		active or closed ?
	 * @return 	int					Return integer <0 if KO, >0 if OK
	 */
	public function loadFiscalPeriods($force = false, $mode = 'active')
	{
		global $conf;

		if ($mode == 'active') {
			if (!isset($conf->cache['active_fiscal_period_cached']) || $force) {
				$sql = "SELECT date_start, date_end";
				$sql .= " FROM " . $this->db->prefix() . "accounting_fiscalyear";
				$sql .= " WHERE entity = " . ((int) $conf->entity);
				$sql .= " AND statut = 0";

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $this->db->lasterror();
					return -1;
				}

				$list = array();
				while ($obj = $this->db->fetch_object($resql)) {
					$list[] = array(
						'date_start' => $this->db->jdate($obj->date_start),
						'date_end' => $this->db->jdate($obj->date_end),
					);
				}
				$conf->cache['active_fiscal_period_cached'] = $list;
			}
		}
		if ($mode == 'closed') {
			if (!isset($conf->cache['closed_fiscal_period_cached']) || $force) {
				$sql = "SELECT date_start, date_end";
				$sql .= " FROM " . $this->db->prefix() . "accounting_fiscalyear";
				$sql .= " WHERE entity = " . ((int) $conf->entity);
				$sql .= " AND statut = 1";

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = $this->db->lasterror();
					return -1;
				}

				$list = array();
				while ($obj = $this->db->fetch_object($resql)) {
					$list[] = array(
						'date_start' => $this->db->jdate($obj->date_start),
						'date_end' => $this->db->jdate($obj->date_end),
					);
				}
				$conf->cache['closed_fiscal_period_cached'] = $list;
			}
		}

		return 1;
	}

	/**
	 * Get list of fiscal period
	 *
	 * @param 	string	$filter		Filter
	 * @return 	array<array{id:int,label:string,date_start:string,date_end:string,status:int}>|int			Return integer <0 if KO, Fiscal periods : [[id, date_start, date_end, label], ...]
	 */
	public function getFiscalPeriods($filter = '')
	{
		global $conf;
		$list = array();

		$sql = "SELECT rowid, label, date_start, date_end, statut";
		$sql .= " FROM " . $this->db->prefix() . "accounting_fiscalyear";
		$sql .= " WHERE entity = " . ((int) $conf->entity);
		if (!empty($filter)) {
			$sql .= " AND (" . $this->db->sanitize($filter, 1, 1, 1) . ')';
		}
		$sql .= $this->db->order('date_start', 'ASC');

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$list[$obj->rowid] = array(
				'id' => (int) $obj->rowid,
				'label' => $obj->label,
				'date_start' => $this->db->jdate($obj->date_start),
				'date_end' => $this->db->jdate($obj->date_end),
				'status' => (int) $obj->statut,
			);
		}

		return $list;
	}

	/**
	 * Get list of count by month into the fiscal period
	 *
	 * @param 	int			$date_start		Date start
	 * @param 	int			$date_end		Date end
	 * @return	array{total:int,list:array<array{year:int,count:array<int<1,12>,int>,total:int}>}|int<-1,-1>		Return integer <0 if KO, Fiscal periods : [[id, date_start, date_end, label], ...]
	 */
	public function getCountByMonthForFiscalPeriod($date_start, $date_end)
	{
		$total = 0;
		$list = array();

		$sql = "SELECT YEAR(b.doc_date) as year";
		for ($i = 1; $i <= 12; $i++) {
			$sql .= ", SUM(".$this->db->ifsql("MONTH(b.doc_date) = ".((int) $i), "1", "0") . ") AS month".((int) $i);
		}
		$sql .= ", COUNT(b.rowid) as total";
		$sql .= " FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as b";
		$sql .= " WHERE b.doc_date >= '" . $this->db->idate($date_start) . "'";
		$sql .= " AND b.doc_date <= '" . $this->db->idate($date_end) . "'";
		$sql .= " AND b.entity IN (" . getEntity('bookkeeping', 0) . ")"; // We don't share object for accountancy

		// Get count for each month into the fiscal period
		if (getDolGlobalString("ACCOUNTANCY_DISABLE_CLOSURE_LINE_BY_LINE")) {
			// TODO Analyse is done by finding record not into a closed period
			// Loop on each closed period
			$sql .= " AND b.doc_date BETWEEN 0 AND 0";
		} else {
			// Analyse closed record using the unitary flag/date on each record
			$sql .= " AND date_validated IS NULL";
		}

		$sql .= " GROUP BY YEAR(b.doc_date)";
		$sql .= $this->db->order("year", 'ASC');

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$total += (int) $obj->total;
			$year_list = array(
				'year' => (int) $obj->year,
				'count' => array(),
				'total' => (int) $obj->total,
			);
			for ($i = 1; $i <= 12; $i++) {
				$year_list['count'][$i] = (int) $obj->{'month' . $i};
			}

			$list[] = $year_list;
		}

		$this->db->free($resql);

		return array(
			'total' => $total,
			'list' => $list,
		);
	}

	/**
	 *  Validate all movement between the specified dates
	 *
	 * @param 	int		$date_start		Date start
	 * @param 	int		$date_end		Date end
	 * @return	int						int Return integer <0 if KO, >0 if OK
	 */
	public function validateMovementForFiscalPeriod($date_start, $date_end)
	{
		global $conf;

		$now = dol_now();

		// Specify as export : update field date_validated on selected month/year
		$sql = " UPDATE " . MAIN_DB_PREFIX . "accounting_bookkeeping";
		$sql .= " SET date_validated = '" . $this->db->idate($now) . "'";
		$sql .= " WHERE entity = " . ((int) $conf->entity);
		$sql .= " AND DATE(doc_date) >= '" . $this->db->idate($date_start) . "'";
		$sql .= " AND DATE(doc_date) <= '" . $this->db->idate($date_end) . "'";
		$sql .= " AND date_validated IS NULL";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 *  Define accounting result
	 *
	 * @param	int		$date_start		Date start
	 * @param	int		$date_end		Date end
	 * @return	string					Accounting result
	 */
	public function accountingResult($date_start, $date_end)
	{
		global $conf;

		$this->db->begin();

		$income_statement_amount = 0;

		if (getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT')) {
			$accounting_groups_used_for_income_statement = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT'))), 'strlen');

			$pcg_type_filter = array();
			foreach ($accounting_groups_used_for_income_statement as $item) {
				$pcg_type_filter[] = "'" . $this->db->escape($item) . "'";
			}

			$sql = 'SELECT';
			$sql .= " t.numero_compte,";
			$sql .= " aa.pcg_type,";
			$sql .= " (SUM(t.credit) - SUM(t.debit)) as accounting_result";
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
			$sql .= ' LEFT JOIN  ' . MAIN_DB_PREFIX . 'accounting_account as aa ON aa.account_number = t.numero_compte';
			$sql .= ' WHERE t.entity = ' . ((int) $conf->entity); // Do not use getEntity for accounting features
			$sql .= " AND aa.entity = " . ((int) $conf->entity);
			$sql .= ' AND aa.fk_pcg_version IN (SELECT pcg_version FROM ' . MAIN_DB_PREFIX . 'accounting_system WHERE rowid = ' . ((int) getDolGlobalInt('CHARTOFACCOUNTS')) . ')';
			$sql .= ' AND aa.pcg_type IN (' . $this->db->sanitize(implode(',', $pcg_type_filter), 1) . ')';
			$sql .= " AND DATE(t.doc_date) >= '" . $this->db->idate($date_start) . "'";
			$sql .= " AND DATE(t.doc_date) <= '" . $this->db->idate($date_end) . "'";
			$sql .= ' GROUP BY t.numero_compte, aa.pcg_type';

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);
			} else {
				while ($obj = $this->db->fetch_object($resql)) {
					$income_statement_amount += $obj->accounting_result;
				}
			}
		}

		return (string) $income_statement_amount;
	}

	/**
	 *  Close fiscal period
	 *
	 * @param 	int		$fiscal_period_id				Fiscal year ID
	 * @param 	int		$new_fiscal_period_id			New fiscal year ID
	 * @param	bool	$separate_auxiliary_account		Separate auxiliary account
	 * @param 	bool	$generate_bookkeeping_records	Generate closure bookkeeping records
	 * @return	int										int Return integer <0 if KO, >0 if OK
	 */
	public function closeFiscalPeriod($fiscal_period_id, $new_fiscal_period_id, $separate_auxiliary_account = false, $generate_bookkeeping_records = true)
	{
		global $conf, $langs, $user;

		// Current fiscal period
		$fiscal_period_id = max(0, $fiscal_period_id);
		if (empty($fiscal_period_id)) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}
		$fiscal_period = new Fiscalyear($this->db);
		$result = $fiscal_period->fetch($fiscal_period_id);
		if ($result < 0) {
			$this->error = $fiscal_period->error;
			$this->errors = $fiscal_period->errors;
			return -1;
		} elseif (empty($fiscal_period->id)) {
			$langs->loadLangs(array('errors', 'compta'));
			$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('FiscalPeriod') . ' (' . $fiscal_period_id . ')';
			return -1;
		}

		// New fiscal period
		$new_fiscal_period_id = max(0, $new_fiscal_period_id);
		if (empty($new_fiscal_period_id)) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}
		$new_fiscal_period = new Fiscalyear($this->db);
		$result = $new_fiscal_period->fetch($new_fiscal_period_id);
		if ($result < 0) {
			$this->error = $new_fiscal_period->error;
			$this->errors = $new_fiscal_period->errors;
			return -1;
		} elseif (empty($new_fiscal_period->id)) {
			$langs->loadLangs(array('errors', 'compta'));
			$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('FiscalPeriod') . ' (' . $new_fiscal_period_id . ')';
			return -1;
		}

		$error = 0;
		$this->db->begin();

		$fiscal_period->statut = Fiscalyear::STATUS_CLOSED;
		$fiscal_period->status = Fiscalyear::STATUS_CLOSED; // Actually not used
		$result = $fiscal_period->update($user);
		if ($result < 0) {
			$this->error = $fiscal_period->error;
			$this->errors = $fiscal_period->errors;
			$error++;
		}

		if (!$error && !empty($generate_bookkeeping_records)) {
			$journal_id = max(0, getDolGlobalString('ACCOUNTING_CLOSURE_DEFAULT_JOURNAL'));
			if (empty($journal_id)) {
				$langs->loadLangs(array('errors', 'accountancy'));
				$this->errors[] = $langs->trans('ErrorBadParameters') . ' - ' . $langs->trans('Codejournal') . ' (' . $langs->trans('AccountingJournalType9') . ')';
				$error++;
			}

			// Fetch journal
			if (!$error) {
				$journal = new AccountingJournal($this->db);
				$result = $journal->fetch($journal_id);
				if ($result < 0) {
					$this->error = $journal->error;
					$this->errors = $journal->errors;
					$error++;
				} elseif ($result == 0) {
					$langs->loadLangs(array('errors', 'accountancy'));
					$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('Codejournal') . ' (' . $langs->trans('AccountingJournalType9') . ')';
					$error++;
				}
			} else {
				$journal = null;
			}

			if (!$error && is_object($journal)) {
				$accounting_groups_used_for_balance_sheet_account = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT'))), 'strlen');
				$accounting_groups_used_for_income_statement = array_filter(array_map('trim', explode(',', getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT'))), 'strlen');

				$pcg_type_filter = array();
				$tmp = array_merge($accounting_groups_used_for_balance_sheet_account, $accounting_groups_used_for_income_statement);
				foreach ($tmp as $item) {
					$pcg_type_filter[] = "'" . $this->db->escape($item) . "'";
				}

				$sql = 'SELECT';
				$sql .= " t.numero_compte,";
				$sql .= " t.label_compte,";
				if ($separate_auxiliary_account) {
					$sql .= " t.subledger_account,";
					$sql .= " t.subledger_label,";
				}
				$sql .= " aa.pcg_type,";
				$sql .= " (SUM(t.credit) - SUM(t.debit)) as opening_balance";
				$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
				$sql .= ' LEFT JOIN  ' . MAIN_DB_PREFIX . 'accounting_account as aa ON aa.account_number = t.numero_compte';
				$sql .= ' WHERE t.entity = ' . ((int) $conf->entity); // Do not use getEntity for accounting features
				$sql .= " AND aa.entity = ". ((int) $conf->entity);
				$sql .= ' AND aa.fk_pcg_version IN (SELECT pcg_version FROM '.MAIN_DB_PREFIX.'accounting_system WHERE rowid = '.((int) getDolGlobalInt('CHARTOFACCOUNTS')).')';
				$sql .= ' AND aa.pcg_type IN (' . $this->db->sanitize(implode(',', $pcg_type_filter), 1) . ')';
				$sql .= " AND DATE(t.doc_date) >= '" . $this->db->idate($fiscal_period->date_start) . "'";
				$sql .= " AND DATE(t.doc_date) <= '" . $this->db->idate($fiscal_period->date_end) . "'";
				$sql .= ' GROUP BY t.numero_compte, t.label_compte, aa.pcg_type';
				if ($separate_auxiliary_account) {
					$sql .= ' ,t.subledger_account, t.subledger_label';
				}
				$sql .= $this->db->order("t.numero_compte", "ASC");

				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->errors[] = 'Error ' . $this->db->lasterror();
					dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

					$error++;
				} else {
					$now = dol_now();
					$income_statement_amount = 0;
					while ($obj = $this->db->fetch_object($resql)) {
						if (in_array($obj->pcg_type, $accounting_groups_used_for_income_statement)) {
							$income_statement_amount += $obj->opening_balance;
						} else {
							// Insert bookkeeping record for balance sheet account
							$mt = $obj->opening_balance;

							$bookkeeping = new BookKeeping($this->db);
							$bookkeeping->doc_date = $new_fiscal_period->date_start;
							$bookkeeping->date_lim_reglement = 0;
							$bookkeeping->doc_ref = $new_fiscal_period->label;
							$bookkeeping->date_creation = $now;
							$bookkeeping->doc_type = 'closure';
							$bookkeeping->fk_doc = $new_fiscal_period->id;
							$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
							$bookkeeping->thirdparty_code = '';

							if ($separate_auxiliary_account) {
								$bookkeeping->subledger_account = $obj->subledger_account;
								$bookkeeping->subledger_label = $obj->subledger_label;
							} else {
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
							}

							$bookkeeping->numero_compte = $obj->numero_compte;
							$bookkeeping->label_compte = $obj->label_compte;

							$bookkeeping->label_operation = $new_fiscal_period->label;
							$bookkeeping->montant = $mt;
							$bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
							$bookkeeping->debit = ($mt < 0) ? -$mt : 0;
							$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
							$bookkeeping->code_journal = $journal->code;
							$bookkeeping->journal_label = $langs->transnoentities($journal->label);
							$bookkeeping->fk_user_author = $user->id;
							$bookkeeping->entity = $conf->entity;

							$result = $bookkeeping->create($user);
							if ($result < 0) {
								$this->error = $bookkeeping->error;
								$this->errors = $bookkeeping->errors;
								$error++;
								break;
							}
						}
					}

					// Insert bookkeeping record for income statement
					if (!$error && $income_statement_amount != 0) {
						$mt = $income_statement_amount;
						$accountingaccount = new AccountingAccount($this->db);
						$accountingaccount->fetch(0, getDolGlobalString($income_statement_amount < 0 ? 'ACCOUNTING_RESULT_LOSS' : 'ACCOUNTING_RESULT_PROFIT'), true);

						$bookkeeping = new BookKeeping($this->db);
						$bookkeeping->doc_date = $new_fiscal_period->date_start;
						$bookkeeping->date_lim_reglement = 0;
						$bookkeeping->doc_ref = $new_fiscal_period->label;
						$bookkeeping->date_creation = $now;
						$bookkeeping->doc_type = 'closure';
						$bookkeeping->fk_doc = $new_fiscal_period->id;
						$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add
						$bookkeeping->thirdparty_code = '';

						if ($separate_auxiliary_account) {
							$bookkeeping->subledger_label = '';
							$bookkeeping->subledger_account = $obj->subledger_account;
							$bookkeeping->subledger_label = $obj->subledger_label;
						} else {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
						}

						$bookkeeping->numero_compte = $accountingaccount->account_number;
						$bookkeeping->label_compte = $accountingaccount->label;

						$bookkeeping->label_operation = $new_fiscal_period->label;
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt >= 0) ? 'C' : 'D';
						$bookkeeping->debit = ($mt < 0) ? -$mt : 0;
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal->code;
						$bookkeeping->journal_label = $langs->transnoentities($journal->label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->entity = $conf->entity;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							$this->error = $bookkeeping->error;
							$this->errors = $bookkeeping->errors;
							$error++;
						}
					}
					$this->db->free($resql);
				}
			}
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Insert accounting reversal into the inventory journal of the new fiscal period
	 *
	 * @param 	int		$fiscal_period_id		Fiscal year ID
	 * @param 	int		$inventory_journal_id	Inventory journal ID
	 * @param 	int		$new_fiscal_period_id	New fiscal year ID
	 * @param 	int		$date_start				Date start
	 * @param 	int		$date_end				Date end
	 * @return	int								int Return integer <0 if KO, >0 if OK
	 */
	public function insertAccountingReversal($fiscal_period_id, $inventory_journal_id, $new_fiscal_period_id, $date_start, $date_end)
	{
		global $conf, $langs, $user;

		// Current fiscal period
		$fiscal_period_id = max(0, $fiscal_period_id);
		if (empty($fiscal_period_id)) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}
		$fiscal_period = new Fiscalyear($this->db);
		$result = $fiscal_period->fetch($fiscal_period_id);
		if ($result < 0) {
			$this->error = $fiscal_period->error;
			$this->errors = $fiscal_period->errors;
			return -1;
		} elseif (empty($fiscal_period->id)) {
			$langs->loadLangs(array('errors', 'compta'));
			$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('FiscalPeriod') . ' (' . $fiscal_period_id . ')';
			return -1;
		}

		// New fiscal period
		$new_fiscal_period_id = max(0, $new_fiscal_period_id);
		if (empty($new_fiscal_period_id)) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}
		$new_fiscal_period = new Fiscalyear($this->db);
		$result = $new_fiscal_period->fetch($new_fiscal_period_id);
		if ($result < 0) {
			$this->error = $new_fiscal_period->error;
			$this->errors = $new_fiscal_period->errors;
			return -1;
		} elseif (empty($new_fiscal_period->id)) {
			$langs->loadLangs(array('errors', 'compta'));
			$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('FiscalPeriod') . ' (' . $new_fiscal_period_id . ')';
			return -1;
		}

		// Inventory journal
		$inventory_journal_id = max(0, $inventory_journal_id);
		if (empty($inventory_journal_id)) {
			$langs->load('errors');
			$this->errors[] = $langs->trans('ErrorBadParameters');
			return -1;
		}
		// Fetch journal
		$inventory_journal = new AccountingJournal($this->db);
		$result = $inventory_journal->fetch($inventory_journal_id);
		if ($result < 0) {
			$this->error = $inventory_journal->error;
			$this->errors = $inventory_journal->errors;
			return -1;
		} elseif ($result == 0) {
			$langs->loadLangs(array('errors', 'accountancy'));
			$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('InventoryJournal');
			return -1;
		}

		$error = 0;
		$this->db->begin();

		$sql = 'SELECT t.rowid';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE t.entity = ' . ((int) $conf->entity); // Do not use getEntity for accounting features
		$sql .= " AND code_journal = '" . $this->db->escape($inventory_journal->code) . "'";
		$sql .= " AND DATE(t.doc_date) >= '" . $this->db->idate($date_start) . "'";
		$sql .= " AND DATE(t.doc_date) <= '" . $this->db->idate($date_end) . "'";
		$sql .= " AND DATE(t.doc_date) >= '" . $this->db->idate($fiscal_period->date_start) . "'";
		$sql .= " AND DATE(t.doc_date) <= '" . $this->db->idate($fiscal_period->date_end) . "'";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			$error++;
		} else {
			$now = dol_now();
			while ($obj = $this->db->fetch_object($resql)) {
				$bookkeeping = new BookKeeping($this->db);
				$result = $bookkeeping->fetch($obj->rowid);
				if ($result < 0) {
					$this->error = $inventory_journal->error;
					$this->errors = $inventory_journal->errors;
					$error++;
					break;
				} elseif ($result == 0) {
					$langs->loadLangs(array('errors', 'accountancy'));
					$this->errors[] = $langs->trans('ErrorRecordNotFound') . ' - ' . $langs->trans('LineId') . ': ' . $obj->rowid;
					$error++;
					break;
				}

				$bookkeeping->id = 0;
				$bookkeeping->doc_date = $new_fiscal_period->date_start;
				$bookkeeping->doc_ref = $new_fiscal_period->label;
				$bookkeeping->date_creation = $now;
				$bookkeeping->doc_type = 'accounting_reversal';
				$bookkeeping->fk_doc = $new_fiscal_period->id;
				$bookkeeping->fk_docdet = 0; // Useless, can be several lines that are source of this record to add

				$bookkeeping->montant = -$bookkeeping->montant;
				$bookkeeping->sens = ($bookkeeping->montant >= 0) ? 'C' : 'D';
				$old_debit = $bookkeeping->debit;
				$bookkeeping->debit = $bookkeeping->credit;
				$bookkeeping->credit = $old_debit;

				$bookkeeping->fk_user_author = $user->id;
				$bookkeeping->entity = $conf->entity;

				$result = $bookkeeping->create($user);
				if ($result < 0) {
					$this->error = $bookkeeping->error;
					$this->errors = $bookkeeping->errors;
					$error++;
					break;
				}
			}
			$this->db->free($resql);
		}

		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}
}

/**
 * Class BookKeepingLine
 */
class BookKeepingLine extends CommonObjectLine
{
	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var ?int	Date of source document
	 */
	public $doc_date = null;
	/**
	 * @var string 	Doc type
	 */
	public $doc_type;

	/**
	 * @var string 	Doc ref
	 */
	public $doc_ref;

	/**
	 * @var int ID
	 */
	public $fk_doc;

	/**
	 * @var int ID
	 */
	public $fk_docdet;

	/**
	 * @var string 	Thirdparty code
	 */
	public $thirdparty_code;

	/**
	 * @var string|null 	Subledger account
	 */
	public $subledger_account;

	/**
	 * @var string|null 	Subledger label
	 */
	public $subledger_label;

	/**
	 * @var string  doc_type
	 */
	public $numero_compte;

	/**
	 * @var string label compte
	 */
	public $label_compte;

	/**
	 * @var string label operation
	 */
	public $label_operation;

	/**
	 * @var float FEC:Debit
	 */
	public $debit;

	/**
	 * @var float FEC:Credit
	 */
	public $credit;

	/**
	 * @var float Amount
	 * @deprecated see $amount
	 */
	public $montant;

	/**
	 * @var float 	Amount
	 */
	public $amount;

	/**
	 * @var float 	Multicurrency amount
	 */
	public $multicurrency_amount;

	/**
	 * @var string 	Multicurrency code
	 */
	public $multicurrency_code;

	/**
	 * @var string Sens
	 */
	public $sens;

	/**
	 * @var ?string
	 */
	public $lettering_code;

	/**
	 * @var string
	 */
	public $date_lettering;

	/**
	 * @var int ID
	 */
	public $fk_user_author;


	/**
	 * @var string key for import
	 */
	public $import_key;

	/**
	 * @var string
	 */
	public $code_journal;

	/**
	 * @var string
	 */
	public $journal_label;
	/**
	 * @var int accounting transaction id
	 */
	public $piece_num;

	/**
	 * @var int|string $date_export;
	 */
	public $date_export;

	/**
	 * @var int|string $date_lim_reglement;
	 */
	public $date_lim_reglement;
}
