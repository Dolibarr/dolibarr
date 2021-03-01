<?php
/* Copyright (C) 2011 		Dimitri Mouillard   	<dmouillard@teclib.com>
 * Copyright (C) 2015 		Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2015 		Alexandre Spangaro  	<aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (c) 2018       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2016-2020 	Ferran Marcet       	<fmarcet@2byte.es>
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
 *       \file       htdocs/expensereport/class/expensereport.class.php
 *       \ingroup    expensereport
 *       \brief      File to manage Expense Reports
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_rule.class.php';

/**
 * Class to manage Trips and Expenses
 */
class ExpenseReport extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'expensereport';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'expensereport';

	/**
	 * @var string table element line name
	 */
	public $table_element_line = 'expensereport_det';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_expensereport';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'trip';

	public $lines = array();

	public $date_debut;

	public $date_fin;

	/**
	 * 0=draft, 2=validated (attente approb), 4=canceled, 5=approved, 6=paid, 99=denied
	 *
	 * @var int		Status
	 */
	public $status;

	/**
	 * 0=draft, 2=validated (attente approb), 4=canceled, 5=approved, 6=paid, 99=denied
	 *
	 * @var int		Status
	 * @deprecated
	 */
	public $fk_statut;

	public $vat_src_code;

	public $fk_c_paiement;
	public $paid;

	public $user_author_infos;
	public $user_validator_infos;

	public $rule_warning_message;

	// ACTIONS

	// Create
	public $date_create;
	public $fk_user_author; // Note fk_user_author is not the 'author' but the guy the expense report is for.

	// Update
	public $date_modif;
	public $fk_user_modif;

	// Refus
	public $date_refuse;
	public $detail_refuse;
	public $fk_user_refuse;

	// Annulation
	public $date_cancel;
	public $detail_cancel;
	public $fk_user_cancel;

	public $fk_user_validator; // User that is defined to approve

	// Validation
	public $date_valid; // User making validation
	public $fk_user_valid;
	public $user_valid_infos;

	// Approve
	public $date_approve;
	public $fk_user_approve; // User that has approved

	// Paiement
	public $user_paid_infos;


	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated (need to be paid)
	 */
	const STATUS_VALIDATED = 2;

	/**
	 * Classified canceled
	 */
	const STATUS_CANCELED = 4;

	/**
	 * Classified approved
	 */
	const STATUS_APPROVED = 5;

	/**
	 * Classified refused
	 */
	const STATUS_REFUSED = 99;

	/**
	 * Classified paid.
	 */
	const STATUS_CLOSED = 6;


	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'ID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'ref' =>array('type'=>'varchar(50)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'showoncombobox'=>1, 'position'=>15),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>20),
		'ref_number_int' =>array('type'=>'integer', 'label'=>'Ref number int', 'enabled'=>1, 'visible'=>-1, 'position'=>25),
		'ref_ext' =>array('type'=>'integer', 'label'=>'Ref ext', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'total_ht' =>array('type'=>'double(24,8)', 'label'=>'Total ht', 'enabled'=>1, 'visible'=>-1, 'position'=>35),
		'total_tva' =>array('type'=>'double(24,8)', 'label'=>'Total tva', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		'localtax1' =>array('type'=>'double(24,8)', 'label'=>'Localtax1', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'localtax2' =>array('type'=>'double(24,8)', 'label'=>'Localtax2', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		'total_ttc' =>array('type'=>'double(24,8)', 'label'=>'Total ttc', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'date_debut' =>array('type'=>'date', 'label'=>'Date debut', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>60),
		'date_fin' =>array('type'=>'date', 'label'=>'Date fin', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>65),
		'date_valid' =>array('type'=>'datetime', 'label'=>'Date valid', 'enabled'=>1, 'visible'=>-1, 'position'=>75),
		'date_approve' =>array('type'=>'datetime', 'label'=>'Date approve', 'enabled'=>1, 'visible'=>-1, 'position'=>80),
		'date_refuse' =>array('type'=>'datetime', 'label'=>'Date refuse', 'enabled'=>1, 'visible'=>-1, 'position'=>85),
		'date_cancel' =>array('type'=>'datetime', 'label'=>'Date cancel', 'enabled'=>1, 'visible'=>-1, 'position'=>90),
		'fk_user_author' =>array('type'=>'integer', 'label'=>'Fk user author', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>100),
		'fk_user_modif' =>array('type'=>'integer', 'label'=>'Fk user modif', 'enabled'=>1, 'visible'=>-1, 'position'=>105),
		'fk_user_valid' =>array('type'=>'integer', 'label'=>'Fk user valid', 'enabled'=>1, 'visible'=>-1, 'position'=>110),
		'fk_user_validator' =>array('type'=>'integer', 'label'=>'Fk user validator', 'enabled'=>1, 'visible'=>-1, 'position'=>115),
		'fk_user_approve' =>array('type'=>'integer', 'label'=>'Fk user approve', 'enabled'=>1, 'visible'=>-1, 'position'=>120),
		'fk_user_refuse' =>array('type'=>'integer', 'label'=>'Fk user refuse', 'enabled'=>1, 'visible'=>-1, 'position'=>125),
		'fk_user_cancel' =>array('type'=>'integer', 'label'=>'Fk user cancel', 'enabled'=>1, 'visible'=>-1, 'position'=>130),
		'fk_c_paiement' =>array('type'=>'integer', 'label'=>'Fk c paiement', 'enabled'=>1, 'visible'=>-1, 'position'=>140),
		'paid' =>array('type'=>'integer', 'label'=>'Paid', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>145),
		'note_public' =>array('type'=>'text', 'label'=>'Note public', 'enabled'=>1, 'visible'=>0, 'position'=>150),
		'note_private' =>array('type'=>'text', 'label'=>'Note private', 'enabled'=>1, 'visible'=>0, 'position'=>155),
		'detail_refuse' =>array('type'=>'varchar(255)', 'label'=>'Detail refuse', 'enabled'=>1, 'visible'=>-1, 'position'=>160),
		'detail_cancel' =>array('type'=>'varchar(255)', 'label'=>'Detail cancel', 'enabled'=>1, 'visible'=>-1, 'position'=>165),
		'integration_compta' =>array('type'=>'integer', 'label'=>'Integration compta', 'enabled'=>1, 'visible'=>-1, 'position'=>170),
		'fk_bank_account' =>array('type'=>'integer', 'label'=>'Fk bank account', 'enabled'=>1, 'visible'=>-1, 'position'=>175),
		'fk_multicurrency' =>array('type'=>'integer', 'label'=>'Fk multicurrency', 'enabled'=>1, 'visible'=>-1, 'position'=>185),
		'multicurrency_code' =>array('type'=>'varchar(255)', 'label'=>'Multicurrency code', 'enabled'=>1, 'visible'=>-1, 'position'=>190),
		'multicurrency_tx' =>array('type'=>'double(24,8)', 'label'=>'Multicurrency tx', 'enabled'=>1, 'visible'=>-1, 'position'=>195),
		'multicurrency_total_ht' =>array('type'=>'double(24,8)', 'label'=>'Multicurrency total ht', 'enabled'=>1, 'visible'=>-1, 'position'=>200),
		'multicurrency_total_tva' =>array('type'=>'double(24,8)', 'label'=>'Multicurrency total tva', 'enabled'=>1, 'visible'=>-1, 'position'=>205),
		'multicurrency_total_ttc' =>array('type'=>'double(24,8)', 'label'=>'Multicurrency total ttc', 'enabled'=>1, 'visible'=>-1, 'position'=>210),
		'extraparams' =>array('type'=>'varchar(255)', 'label'=>'Extraparams', 'enabled'=>1, 'visible'=>-1, 'position'=>220),
		'date_create' =>array('type'=>'datetime', 'label'=>'Date create', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>300),
		'tms' =>array('type'=>'timestamp', 'label'=>'Tms', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>305),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-1, 'position'=>1000),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>1010),
		'fk_statut' =>array('type'=>'integer', 'label'=>'Fk statut', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>500),
	);

	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db     Handler acces base de donnees
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->total_ht = 0;
		$this->total_ttc = 0;
		$this->total_tva = 0;
		$this->modepaymentid = 0;

		// List of language codes for status
		$this->statuts_short = array(0 => 'Draft', 2 => 'Validated', 4 => 'Canceled', 5 => 'Approved', 6 => 'Paid', 99 => 'Refused');
		$this->statuts = array(0 => 'Draft', 2 => 'ValidatedWaitingApproval', 4 => 'Canceled', 5 => 'Approved', 6 => 'Paid', 99 => 'Refused');
		$this->statuts_logo = array(0 => 'status0', 2 => 'status1', 4 => 'status6', 5 => 'status4', 6 => 'status6', 99 => 'status5');
	}

	/**
	 * Create object in database
	 *
	 * @param   User    $user   User that create
	 * @param   int     $notrigger   Disable triggers
	 * @return  int             <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$now = dol_now();

		$error = 0;

		// Check parameters
		if (empty($this->date_debut) || empty($this->date_fin))
		{
			$this->error = $langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Date'));
			return -1;
		}

		$fuserid = $this->fk_user_author; // Note fk_user_author is not the 'author' but the guy the expense report is for.
		if (empty($fuserid)) $fuserid = $user->id;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql .= "ref";
		$sql .= ",total_ht";
		$sql .= ",total_ttc";
		$sql .= ",total_tva";
		$sql .= ",date_debut";
		$sql .= ",date_fin";
		$sql .= ",date_create";
		$sql .= ",fk_user_creat";
		$sql .= ",fk_user_author";
		$sql .= ",fk_user_validator";
		$sql .= ",fk_user_approve";
		$sql .= ",fk_user_modif";
		$sql .= ",fk_statut";
		$sql .= ",fk_c_paiement";
		$sql .= ",paid";
		$sql .= ",note_public";
		$sql .= ",note_private";
		$sql .= ",entity";
		$sql .= ") VALUES(";
		$sql .= "'(PROV)'";
		$sql .= ", ".$this->total_ht;
		$sql .= ", ".$this->total_ttc;
		$sql .= ", ".$this->total_tva;
		$sql .= ", '".$this->db->idate($this->date_debut)."'";
		$sql .= ", '".$this->db->idate($this->date_fin)."'";
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".$user->id;
		$sql .= ", ".$fuserid;
		$sql .= ", ".($this->fk_user_validator > 0 ? $this->fk_user_validator : "null");
		$sql .= ", ".($this->fk_user_approve > 0 ? $this->fk_user_approve : "null");
		$sql .= ", ".($this->fk_user_modif > 0 ? $this->fk_user_modif : "null");
		$sql .= ", ".($this->fk_statut > 1 ? $this->fk_statut : 0);
		$sql .= ", ".($this->modepaymentid ? $this->modepaymentid : "null");
		$sql .= ", 0";
		$sql .= ", ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".$conf->entity;
		$sql .= ")";

		$result = $this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$this->ref = '(PROV'.$this->id.')';

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element." SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".$this->id;
			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error)
			{
				if (is_array($this->lines) && count($this->lines) > 0)
				{
					foreach ($this->lines as $line)
					{
						// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
						//if (! is_object($line)) $line=json_decode(json_encode($line), false);  // convert recursively array into object.
						if (!is_object($line)) {
							$line = (object) $line;
							$newndfline = new ExpenseReportLine($this->db);
							$newndfline->fk_expensereport = $line->fk_expensereport;
							$newndfline->fk_c_type_fees = $line->fk_c_type_fees;
							$newndfline->fk_project = $line->fk_project;
							$newndfline->vatrate = $line->vatrate;
							$newndfline->vat_src_code = $line->vat_src_code;
							$newndfline->comments = $line->comments;
							$newndfline->qty = $line->qty;
							$newndfline->value_unit = $line->value_unit;
							$newndfline->total_ht = $line->total_ht;
							$newndfline->total_ttc = $line->total_ttc;
							$newndfline->total_tva = $line->total_tva;
							$newndfline->date = $line->date;
							$newndfline->rule_warning_message = $line->rule_warning_message;
							$newndfline->fk_c_exp_tax_cat = $line->fk_c_exp_tax_cat;
							$newndfline->fk_ecm_files = $line->fk_ecm_files;
						} else {
							$newndfline = $line;
						}
						//$newndfline=new ExpenseReportLine($this->db);
						$newndfline->fk_expensereport = $this->id;
						$result = $newndfline->insert();
						if ($result < 0)
						{
							$this->error = $newndfline->error;
							$this->errors = $newndfline->errors;
							$error++;
							break;
						}
					}
				}
			}

			if (!$error)
			{
				$result = $this->insertExtraFields();
		   		if ($result < 0) $error++;
			}

			if (!$error)
			{
				$result = $this->update_price();
				if ($result > 0)
				{
					if (!$notrigger)
					{
						// Call trigger
						$result = $this->call_trigger('EXPENSE_REPORT_CREATE', $user);

						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}

					if (empty($error))
					{
						$this->db->commit();
						return $this->id;
					} else {
						$this->db->rollback();
						return -4;
					}
				} else {
					$this->db->rollback();
					return -3;
				}
			} else {
				dol_syslog(get_class($this)."::create error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror()." sql=".$sql;
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	    User	$user		        User making the clone
	 *	@param		int     $fk_user_author		Id of new user
	 *	@return		int							New id of clone
	 */
	public function createFromClone(User $user, $fk_user_author)
	{
		global $hookmanager;

		$error = 0;

		if (empty($fk_user_author)) $fk_user_author = $user->id;

		$this->db->begin();

		// get extrafields so they will be clone
		//foreach($this->lines as $line)
			//$line->fetch_optionals();

		// Load source object
		$objFrom = clone $this;

		$this->id = 0;
		$this->ref = '';
		$this->status = 0;
		$this->fk_statut = 0; // deprecated

		// Clear fields
		$this->fk_user_creat      = $user->id;
		$this->fk_user_author     = $fk_user_author; // Note fk_user_author is not the 'author' but the guy the expense report is for.
		$this->fk_user_valid      = '';
		$this->date_create = '';
		$this->date_creation      = '';
		$this->date_validation    = '';

		// Remove link on lines to a joined file
		if (is_array($this->lines) && count($this->lines) > 0)
		{
			foreach ($this->lines as $key => $line)
			{
				$this->lines[$key]->fk_ecm_files = 0;
			}
		}

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $this->create($user);
		if ($result < 0) $error++;

		if (!$error)
		{
			// Hook of thirdparty module
			if (is_object($hookmanager))
			{
				$parameters = array('objFrom'=>$objFrom);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) $error++;
			}
		}

		unset($this->context['createfromclone']);

		// End
		if (!$error)
		{
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 * update
	 *
	 * @param   User    $user                   User making change
	 * @param   int     $notrigger              Disable triggers
	 * @param   User    $userofexpensereport    New user we want to have the expense report on.
	 * @return  int                             <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0, $userofexpensereport = null)
	{
		global $langs;

		$error = 0;
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " total_ht = ".$this->total_ht;
		$sql .= " , total_ttc = ".$this->total_ttc;
		$sql .= " , total_tva = ".$this->total_tva;
		$sql .= " , date_debut = '".$this->db->idate($this->date_debut)."'";
		$sql .= " , date_fin = '".$this->db->idate($this->date_fin)."'";
		if ($userofexpensereport && is_object($userofexpensereport))
		{
			$sql .= " , fk_user_author = ".($userofexpensereport->id > 0 ? $userofexpensereport->id : "null"); // Note fk_user_author is not the 'author' but the guy the expense report is for.
		}
		$sql .= " , fk_user_validator = ".($this->fk_user_validator > 0 ? $this->fk_user_validator : "null");
		$sql .= " , fk_user_valid = ".($this->fk_user_valid > 0 ? $this->fk_user_valid : "null");
		$sql .= " , fk_user_approve = ".($this->fk_user_approve > 0 ? $this->fk_user_approve : "null");
		$sql .= " , fk_user_modif = ".$user->id;
		$sql .= " , fk_statut = ".($this->fk_statut >= 0 ? $this->fk_statut : '0');
		$sql .= " , fk_c_paiement = ".($this->fk_c_paiement > 0 ? $this->fk_c_paiement : "null");
		$sql .= " , note_public = ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "''");
		$sql .= " , note_private = ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "''");
		$sql .= " , detail_refuse = ".(!empty($this->detail_refuse) ? "'".$this->db->escape($this->detail_refuse)."'" : "''");
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if (!$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('EXPENSE_REPORT_UPDATE', $user);

				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (empty($error))
			{
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -2;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Load an object from database
	 *
	 *  @param  int     $id     Id                      {@min 1}
	 *  @param  string  $ref    Ref                     {@name ref}
	 *  @return int             <0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		global $conf;

		$sql = "SELECT d.rowid, d.entity, d.ref, d.note_public, d.note_private,"; // DEFAULT
		$sql .= " d.detail_refuse, d.detail_cancel, d.fk_user_refuse, d.fk_user_cancel,"; // ACTIONS
		$sql .= " d.date_refuse, d.date_cancel,"; // ACTIONS
		$sql .= " d.total_ht, d.total_ttc, d.total_tva,"; // TOTAUX (int)
		$sql .= " d.date_debut, d.date_fin, d.date_create, d.tms as date_modif, d.date_valid, d.date_approve,"; // DATES (datetime)
		$sql .= " d.fk_user_creat, d.fk_user_author, d.fk_user_modif, d.fk_user_validator,";
		$sql .= " d.fk_user_valid, d.fk_user_approve,";
		$sql .= " d.fk_statut as status, d.fk_c_paiement, d.paid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as d";
		if ($ref) $sql .= " WHERE d.ref = '".$this->db->escape($ref)."'";
		else $sql .= " WHERE d.rowid = ".$id;
		//$sql.= $restrict;

		dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->id           = $obj->rowid;
				$this->ref          = $obj->ref;

				$this->entity       = $obj->entity;

				$this->total_ht     = $obj->total_ht;
				$this->total_tva    = $obj->total_tva;
				$this->total_ttc    = $obj->total_ttc;
				$this->note_public  = $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->detail_refuse = $obj->detail_refuse;
				$this->detail_cancel = $obj->detail_cancel;

				$this->date_debut       = $this->db->jdate($obj->date_debut);
				$this->date_fin         = $this->db->jdate($obj->date_fin);
				$this->date_valid       = $this->db->jdate($obj->date_valid);
				$this->date_approve     = $this->db->jdate($obj->date_approve);
				$this->date_create      = $this->db->jdate($obj->date_create);
				$this->date_modif       = $this->db->jdate($obj->date_modif);
				$this->date_refuse      = $this->db->jdate($obj->date_refuse);
				$this->date_cancel      = $this->db->jdate($obj->date_cancel);

				$this->fk_user_creat            = $obj->fk_user_creat;
				$this->fk_user_author           = $obj->fk_user_author; // Note fk_user_author is not the 'author' but the guy the expense report is for.
				$this->fk_user_modif            = $obj->fk_user_modif;
				$this->fk_user_validator        = $obj->fk_user_validator;
				$this->fk_user_valid            = $obj->fk_user_valid;
				$this->fk_user_refuse           = $obj->fk_user_refuse;
				$this->fk_user_cancel           = $obj->fk_user_cancel;
				$this->fk_user_approve          = $obj->fk_user_approve;

				$user_author = new User($this->db);
				if ($this->fk_user_author > 0) $user_author->fetch($this->fk_user_author);

				$this->user_author_infos = dolGetFirstLastname($user_author->firstname, $user_author->lastname);

				$user_approver = new User($this->db);
				if ($this->fk_user_approve > 0) $user_approver->fetch($this->fk_user_approve);
				elseif ($this->fk_user_validator > 0) $user_approver->fetch($this->fk_user_validator); // For backward compatibility
				$this->user_validator_infos = dolGetFirstLastname($user_approver->firstname, $user_approver->lastname);

				$this->fk_statut                = $obj->status; // deprecated
				$this->status                   = $obj->status;
				$this->fk_c_paiement            = $obj->fk_c_paiement;
				$this->paid                     = $obj->paid;

				if ($this->status == self::STATUS_APPROVED || $this->status == self::STATUS_CLOSED)
				{
					$user_valid = new User($this->db);
					if ($this->fk_user_valid > 0) $user_valid->fetch($this->fk_user_valid);
					$this->user_valid_infos = dolGetFirstLastname($user_valid->firstname, $user_valid->lastname);
				}

				$this->fetch_optionals();

				$result = $this->fetch_lines();

				return $result;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Classify the expense report as paid
	 *
	 *    @param    int     $id                 Id of expense report
	 *    @param    user    $fuser              User making change
	 *    @param    int     $notrigger          Disable triggers
	 *    @return   int                         <0 if KO, >0 if OK
	 */
	public function set_paid($id, $fuser, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."expensereport";
		$sql .= " SET fk_statut = ".self::STATUS_CLOSED.", paid=1";
		$sql .= " WHERE rowid = ".$id." AND fk_statut = ".self::STATUS_APPROVED;

		dol_syslog(get_class($this)."::set_paid sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->affected_rows($resql))
			{
				if (!$notrigger)
				{
					// Call trigger
					$result = $this->call_trigger('EXPENSE_REPORT_PAID', $fuser);

					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (empty($error))
				{
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = $this->db->error();
					return -2;
				}
			} else {
				$this->db->commit();
				return 0;
			}
		} else {
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Returns the label status
	 *
	 *  @param      int     $mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return     string              Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Returns the label of a status
	 *
	 *  @param      int     $status     ID status
	 *  @param      int     $mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return     string              Label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		$labelStatus = $langs->transnoentitiesnoconv($this->statuts[$status]);
		$labelStatusShort = $langs->transnoentitiesnoconv($this->statuts_short[$status]);

		$statusType = $this->statuts_logo[$status];

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}


	/**
	 *  Load information on object
	 *
	 *  @param  int     $id      Id of object
	 *  @return void
	 */
	public function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid,";
		$sql .= " f.date_create as datec,";
		$sql .= " f.tms as date_modification,";
		$sql .= " f.date_valid as datev,";
		$sql .= " f.date_approve as datea,";
		$sql .= " f.fk_user_creat as fk_user_creation,";
		$sql .= " f.fk_user_modif as fk_user_modification,";
		$sql .= " f.fk_user_valid,";
		$sql .= " f.fk_user_approve";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as f";
		$sql .= " WHERE f.rowid = ".$id;
		$sql .= " AND f.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->date_validation = $this->db->jdate($obj->datev);
				$this->date_approbation = $this->db->jdate($obj->datea);

				$cuser = new User($this->db);
				$cuser->fetch($obj->fk_user_author);
				$this->user_creation = $cuser;

				if ($obj->fk_user_creation)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creation);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}
				if ($obj->fk_user_modification)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modification);
					$this->user_modification = $muser;
				}
				if ($obj->fk_user_approve)
				{
					$auser = new User($this->db);
					$auser->fetch($obj->fk_user_approve);
					$this->user_approve = $auser;
				}
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
	}



	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *  id must be 0 if object instance is a specimen.
	 *
	 *  @return void
	 */
	public function initAsSpecimen()
	{
		global $user, $langs, $conf;

		$now = dol_now();

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->entity = 1;
		$this->date_create = $now;
		$this->date_debut = $now;
		$this->date_fin = $now;
		$this->date_valid = $now;
		$this->date_approve = $now;

		$type_fees_id = 2; // TF_TRIP

		$this->status = 5;
		$this->fk_statut = 5;

		$this->fk_user_author = $user->id;
		$this->fk_user_validator = $user->id;
		$this->fk_user_valid = $user->id;
		$this->fk_user_approve = $user->id;

		$this->note_private = 'Private note';
		$this->note_public = 'SPECIMEN';
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new ExpenseReportLine($this->db);
			$line->comments = $langs->trans("Comment")." ".$xnbp;
			$line->date = ($now - 3600 * (1 + $xnbp));
			$line->total_ht = 100;
			$line->total_tva = 20;
			$line->total_ttc = 120;
			$line->qty = 1;
			$line->vatrate = 20;
			$line->value_unit = 120;
			$line->fk_expensereport = 0;
			$line->type_fees_code = 'TRA';
			$line->fk_c_type_fees = $type_fees_id;

			$line->projet_ref = 'ABC';

			$this->lines[$xnbp] = $line;
			$xnbp++;

			$this->total_ht += $line->total_ht;
			$this->total_tva += $line->total_tva;
			$this->total_ttc += $line->total_ttc;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * fetch_line_by_project
	 *
	 * @param   int     $projectid      Project id
	 * @param   User    $user           User
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function fetch_line_by_project($projectid, $user = '')
	{
		// phpcs:enable
		global $conf, $db, $langs;

		$langs->load('trips');

		if ($user->rights->expensereport->lire) {
			$sql = "SELECT de.fk_expensereport, de.date, de.comments, de.total_ht, de.total_ttc";
			$sql .= " FROM ".MAIN_DB_PREFIX."expensereport_det as de";
			$sql .= " WHERE de.fk_projet = ".$projectid;

			dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				$total_HT = 0;
				$total_TTC = 0;

				while ($i < $num)
				{
					$objp = $this->db->fetch_object($result);

					$sql2 = "SELECT d.rowid, d.fk_user_author, d.ref, d.fk_statut as status";
					$sql2 .= " FROM ".MAIN_DB_PREFIX."expensereport as d";
					$sql2 .= " WHERE d.rowid = ".((int) $objp->fk_expensereport);

					$result2 = $this->db->query($sql2);
					$obj = $this->db->fetch_object($result2);

					$objp->fk_user_author = $obj->fk_user_author;
					$objp->ref = $obj->ref;
					$objp->fk_c_expensereport_status = $obj->status;
					$objp->rowid = $obj->rowid;

					$total_HT = $total_HT + $objp->total_ht;
					$total_TTC = $total_TTC + $objp->total_ttc;
					$author = new User($this->db);
					$author->fetch($objp->fk_user_author);

					print '<tr>';
					print '<td><a href="'.DOL_URL_ROOT.'/expensereport/card.php?id='.$objp->rowid.'">'.$objp->ref_num.'</a></td>';
					print '<td class="center">'.dol_print_date($objp->date, 'day').'</td>';
					print '<td>'.$author->getNomUrl(1).'</td>';
					print '<td>'.$objp->comments.'</td>';
					print '<td class="right">'.price($objp->total_ht).'</td>';
					print '<td class="right">'.price($objp->total_ttc).'</td>';
					print '<td class="right">';

					switch ($objp->fk_c_expensereport_status) {
						case 4:
							print img_picto($langs->trans('StatusOrderCanceled'), 'statut5');
							break;
						case 1:
							print $langs->trans('Draft').' '.img_picto($langs->trans('Draft'), 'statut0');
							break;
						case 2:
							print $langs->trans('TripForValid').' '.img_picto($langs->trans('TripForValid'), 'statut3');
							break;
						case 5:
							print $langs->trans('TripForPaid').' '.img_picto($langs->trans('TripForPaid'), 'statut3');
							break;
						case 6:
							print $langs->trans('TripPaid').' '.img_picto($langs->trans('TripPaid'), 'statut4');
							break;
					}
					/*
                     if ($status==4) return img_picto($langs->trans('StatusOrderCanceled'),'statut5');
                    if ($status==1) return img_picto($langs->trans('StatusOrderDraft'),'statut0');
                    if ($status==2) return img_picto($langs->trans('StatusOrderValidated'),'statut1');
                    if ($status==2) return img_picto($langs->trans('StatusOrderOnProcess'),'statut3');
                    if ($status==5) return img_picto($langs->trans('StatusOrderToBill'),'statut4');
                    if ($status==6) return img_picto($langs->trans('StatusOrderOnProcess'),'statut6');
                    */
					print '</td>';
					print '</tr>';

					$i++;
				}

				print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Number").': '.$i.'</td>';
				print '<td class="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_HT).'</td>';
				print '<td class="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_TTC).'</td>';
				print '<td>&nbsp;</td>';
				print '</tr>';
			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		}
	}

	/**
	 * recalculer
	 * TODO Replace this with call to update_price if not already done
	 *
	 * @param   int         $id     Id of expense report
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function recalculer($id)
	{
		$sql = 'SELECT tt.total_ht, tt.total_ttc, tt.total_tva';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as tt';
		$sql .= ' WHERE tt.'.$this->fk_element.' = '.$id;

		$total_ht = 0; $total_tva = 0; $total_ttc = 0;

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num):
				$objp = $this->db->fetch_object($result);
				$total_ht += $objp->total_ht;
				$total_tva += $objp->total_tva;
				$i++;
			endwhile;

			$total_ttc = $total_ht + $total_tva;
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
			$sql .= " total_ht = ".$total_ht;
			$sql .= " , total_ttc = ".$total_ttc;
			$sql .= " , total_tva = ".$total_tva;
			$sql .= " WHERE rowid = ".$id;
			$result = $this->db->query($sql);
			if ($result):
				$this->db->free($result);
				return 1;
			else :
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::recalculer: Error ".$this->error, LOG_ERR);
				return -3;
			endif;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::recalculer: Error ".$this->error, LOG_ERR);
			return -3;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * fetch_lines
	 *
	 * @return  int     <0 if OK, >0 if KO
	 */
	public function fetch_lines()
	{
		// phpcs:enable
		global $conf;

		$this->lines = array();

		$sql = ' SELECT de.rowid, de.comments, de.qty, de.value_unit, de.date, de.rang,';
		$sql .= ' de.'.$this->fk_element.', de.fk_c_type_fees, de.fk_c_exp_tax_cat, de.fk_projet as fk_project, de.tva_tx, de.fk_ecm_files,';
		$sql .= ' de.total_ht, de.total_tva, de.total_ttc,';
		$sql .= ' ctf.code as code_type_fees, ctf.label as libelle_type_fees,';
		$sql .= ' p.ref as ref_projet, p.title as title_projet';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as de';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as ctf ON de.fk_c_type_fees = ctf.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as p ON de.fk_projet = p.rowid';
		$sql .= ' WHERE de.'.$this->fk_element.' = '.$this->id;
		if (!empty($conf->global->EXPENSEREPORT_LINES_SORTED_BY_ROWID))
		{
			$sql .= ' ORDER BY de.rang ASC, de.rowid ASC';
		} else {
			$sql .= ' ORDER BY de.rang ASC, de.date ASC';
		}

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$objp = $this->db->fetch_object($resql);

				$deplig = new ExpenseReportLine($this->db);

				$deplig->rowid          = $objp->rowid;
				$deplig->id             = $objp->rowid;
				$deplig->comments       = $objp->comments;
				$deplig->qty            = $objp->qty;
				$deplig->value_unit     = $objp->value_unit;
				$deplig->date           = $objp->date;
				$deplig->dates          = $this->db->jdate($objp->date);

				$deplig->fk_expensereport = $objp->fk_expensereport;
				$deplig->fk_c_type_fees   = $objp->fk_c_type_fees;
				$deplig->fk_c_exp_tax_cat = $objp->fk_c_exp_tax_cat;
				$deplig->fk_projet        = $objp->fk_project; // deprecated
				$deplig->fk_project       = $objp->fk_project;
				$deplig->fk_ecm_files     = $objp->fk_ecm_files;

				$deplig->total_ht         = $objp->total_ht;
				$deplig->total_tva        = $objp->total_tva;
				$deplig->total_ttc        = $objp->total_ttc;

				$deplig->type_fees_code     = empty($objp->code_type_fees) ? 'TF_OTHER' : $objp->code_type_fees;
				$deplig->type_fees_libelle  = $objp->libelle_type_fees;
				$deplig->tva_tx = $objp->tva_tx;
				$deplig->vatrate            = $objp->tva_tx;
				$deplig->projet_ref         = $objp->ref_projet;
				$deplig->projet_title       = $objp->title_projet;

				$deplig->rang               = $objp->rang;

				$this->lines[$i] = $deplig;

				$i++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_lines: Error ".$this->error, LOG_ERR);
			return -3;
		}
	}


	/**
	 * Delete object in database
	 *
	 * @param   User    $user       User that delete
	 * @param 	bool 	$notrigger  false=launch triggers after, true=disable triggers
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function delete(User $user = null, $notrigger = false)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('EXPENSEREPORT_DELETE', $user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		// Delete extrafields of lines and lines
		if (!$error && !empty($this->table_element_line)) {
			$tabletodelete = $this->table_element_line;
			//$sqlef = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete."_extrafields WHERE fk_object IN (SELECT rowid FROM ".MAIN_DB_PREFIX.$tabletodelete." WHERE ".$this->fk_element." = ".$this->id.")";
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete." WHERE ".$this->fk_element." = ".$this->id;
			if (!$this->db->query($sql)) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) $error++;
		}

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) $error++;
		}

		// Removed extrafields of object
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		// Delete main record
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".$this->id;
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		// Delete record into ECM index and physically
		if (!$error) {
			$res = $this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
			if (!$res) {
				$error++;
			}
		}

		if (!$error) {
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if ($conf->expensereport->multidir_output[$this->entity] && !empty($this->ref)) {
				$dir = $conf->expensereport->multidir_output[$this->entity]."/".$ref;
				$file = $dir."/".$ref.".pdf";
				if (file_exists($file)) {
					dol_delete_preview($this);

					if (!dol_delete_file($file, 0, 0, 0, $this)) {
						$this->error = 'ErrorFailToDeleteFile';
						$this->errors[] = $this->error;
						$this->db->rollback();
						return 0;
					}
				}
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);
					if (!$res) {
						$this->error = 'ErrorFailToDeleteDir';
						$this->errors[] = $this->error;
						$this->db->rollback();
						return 0;
					}
				}
			}
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Set to status validate
	 *
	 * @param   User    $fuser      User
	 * @param   int     $notrigger  Disable triggers
	 * @return  int                 <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function setValidate($fuser, $notrigger = 0)
	{
		global $conf, $langs, $user;

		$error = 0;
		$now = dol_now();

		// Protection
		if ($this->status == self::STATUS_VALIDATED)
		{
			dol_syslog(get_class($this)."::valid action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		$this->date_valid = $now; // Required for the getNextNum later.

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
		{
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		if (empty($num) || $num < 0) return -1;

		$this->newref = dol_sanitizeFileName($num);

		$this->db->begin();

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " fk_statut = ".self::STATUS_VALIDATED.",";
		$sql .= " date_valid='".$this->db->idate($this->date_valid)."',";
		$sql .= " fk_user_valid = ".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (!$error && !$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('EXPENSE_REPORT_VALIDATE', $fuser);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error)
			{
				$this->oldref = $this->ref;

				// Rename directory if dir was a temporary ref
				if (preg_match('/^[\(]?PROV/i', $this->ref))
				{
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

					// Now we rename also files into index
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'expensereport/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'expensereport/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) { $error++; $this->error = $this->db->lasterror(); }

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->expensereport->dir_output.'/'.$oldref;
					$dirdest = $conf->expensereport->dir_output.'/'.$newref;
					if (!$error && file_exists($dirsource))
					{
						dol_syslog(get_class($this)."::setValidate() rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest))
						{
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $newref
							$listoffiles = dol_dir_list($conf->expensereport->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
							foreach ($listoffiles as $fileentry)
							{
								$dirsource = $fileentry['name'];
								$dirdest = preg_replace('/^'.preg_quote($oldref, '/').'/', $newref, $dirsource);
								$dirsource = $fileentry['path'].'/'.$dirsource;
								$dirdest = $fileentry['path'].'/'.$dirdest;
								@rename($dirsource, $dirdest);
							}
						}
					}
				}
			}

			// Set new ref and current status
			if (!$error)
			{
				$this->ref = $num;
				$this->status = self::STATUS_VALIDATED;
			}

			if (empty($error))
			{
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -2;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * set_save_from_refuse
	 *
	 * @param   User    $fuser      User
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function set_save_from_refuse($fuser)
	{
		// phpcs:enable
		global $conf, $langs;

		// Sélection de la date de début de la NDF
		$sql = 'SELECT date_debut';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
		$sql .= ' WHERE rowid = '.$this->id;

		$result = $this->db->query($sql);

		$objp = $this->db->fetch_object($result);

		$this->date_debut = $this->db->jdate($objp->date_debut);

		if ($this->status != self::STATUS_VALIDATED)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET fk_statut = ".self::STATUS_VALIDATED;
			$sql .= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_save_from_refuse sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql))
			{
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::set_save_from_refuse expensereport already with save status", LOG_WARNING);
		}
	}

	/**
	 * Set status to approved
	 *
	 * @param   User    $fuser      User
	 * @param   int     $notrigger  Disable triggers
	 * @return  int                 <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function setApproved($fuser, $notrigger = 0)
	{
		$now = dol_now();
		$error = 0;

		// date approval
		$this->date_approve = $now;
		if ($this->status != self::STATUS_APPROVED)
		{
			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($this->ref)."', fk_statut = ".self::STATUS_APPROVED.", fk_user_approve = ".$fuser->id.",";
			$sql .= " date_approve='".$this->db->idate($this->date_approve)."'";
			$sql .= ' WHERE rowid = '.$this->id;
			if ($this->db->query($sql))
			{
				if (!$notrigger)
				{
					// Call trigger
					$result = $this->call_trigger('EXPENSE_REPORT_APPROVE', $fuser);

					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (empty($error))
				{
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = $this->db->error();
					return -2;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::setApproved expensereport already with approve status", LOG_WARNING);
		}

		return 0;
	}

	/**
	 * setDeny
	 *
	 * @param User      $fuser      User
	 * @param string    $details    Details
	 * @param int       $notrigger  Disable triggers
	 * @return int
	 */
	public function setDeny($fuser, $details, $notrigger = 0)
	{
		$now = dol_now();
		$error = 0;

		// date de refus
		if ($this->status != self::STATUS_REFUSED)
		{
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ref = '".$this->db->escape($this->ref)."', fk_statut = ".self::STATUS_REFUSED.", fk_user_refuse = ".$fuser->id.",";
			$sql .= " date_refuse='".$this->db->idate($now)."',";
			$sql .= " detail_refuse='".$this->db->escape($details)."',";
			$sql .= " fk_user_approve = NULL";
			$sql .= ' WHERE rowid = '.$this->id;
			if ($this->db->query($sql))
			{
				$this->fk_statut = 99; // deprecated
				$this->status = 99;
				$this->fk_user_refuse = $fuser->id;
				$this->detail_refuse = $details;
				$this->date_refuse = $now;

				if (!$notrigger)
				{
					// Call trigger
					$result = $this->call_trigger('EXPENSE_REPORT_DENY', $fuser);

					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (empty($error))
				{
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = $this->db->error();
					return -2;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::setDeny expensereport already with refuse status", LOG_WARNING);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * set_unpaid
	 *
	 * @param   User    $fuser      User
	 * @param   int     $notrigger  Disable triggers
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function set_unpaid($fuser, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;

		if ($this->paid)
		{
			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET paid = 0, fk_statut = ".self::STATUS_APPROVED;
			$sql .= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_unpaid sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql))
			{
				if (!$notrigger)
				{
					// Call trigger
					$result = $this->call_trigger('EXPENSE_REPORT_UNPAID', $fuser);

					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (empty($error))
				{
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = $this->db->error();
					return -2;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::set_unpaid expensereport already with unpaid status", LOG_WARNING);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * set_cancel
	 *
	 * @param   User    $fuser      User
	 * @param   string  $detail     Detail
	 * @param   int     $notrigger  Disable triggers
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function set_cancel($fuser, $detail, $notrigger = 0)
	{
		// phpcs:enable
		$error = 0;
		$this->date_cancel = $this->db->idate(dol_now());
		if ($this->status != self::STATUS_CANCELED)
		{
			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET fk_statut = ".self::STATUS_CANCELED.", fk_user_cancel = ".$fuser->id;
			$sql .= ", date_cancel='".$this->db->idate($this->date_cancel)."'";
			$sql .= " ,detail_cancel='".$this->db->escape($detail)."'";
			$sql .= ' WHERE rowid = '.$this->id;

			dol_syslog(get_class($this)."::set_cancel sql=".$sql, LOG_DEBUG);

			if ($this->db->query($sql))
			{
				if (!$notrigger)
				{
					// Call trigger
					$result = $this->call_trigger('EXPENSE_REPORT_CANCEL', $fuser);

					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (empty($error))
				{
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = $this->db->error();
					return -2;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::set_cancel expensereport already with cancel status", LOG_WARNING);
		}
	}

	/**
	 * Return next reference of expense report not already used
	 *
	 * @return    string            free ref
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("trips");

		if (!empty($conf->global->EXPENSEREPORT_ADDON))
		{
			$mybool = false;

			$file = $conf->global->EXPENSEREPORT_ADDON.".php";
			$classname = $conf->global->EXPENSEREPORT_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir)
			{
				$dir = dol_buildpath($reldir."core/modules/expensereport/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir.$file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($this);

			if ($numref != "")
			{
				return $numref;
			} else {
				$this->error = $obj->error;
				$this->errors = $obj->errors;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return -1;
			}
		} else {
			$this->error = "Error_EXPENSEREPORT_ADDON_NotDefined";
			return -2;
		}
	}

	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		int		$max						Max length of shown ref
	 *	@param		int		$short						1=Return just URL
	 *	@param		string	$moretitle					Add more text to title tooltip
	 *	@param		int		$notooltip					1=Disable tooltip
	 *  @param  	int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return		string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $langs, $conf;

		$result = '';

		$url = DOL_URL_ROOT.'/expensereport/card.php?id='.$this->id;

		if ($short) return $url;

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ExpenseReport").'</u>';
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}
		if (!empty($this->ref))
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (!empty($this->total_ht))
			$label .= '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
		if (!empty($this->total_tva))
			$label .= '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
		if (!empty($this->total_ttc))
			$label .= '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
		if ($moretitle) $label .= ' - '.$moretitle;

		//if ($option != 'nolink')
		//{
		// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		//}

		$ref = $this->ref;
		if (empty($ref)) $ref = $this->id;

		$linkclose = '';
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label = $langs->trans("ShowExpenseReport");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= ($max ?dol_trunc($ref, $max) : $ref);
		$result .= $linkend;

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update total of an expense report when you add a line.
	 *
	 *  @param    string    $ligne_total_ht    Amount without taxes
	 *  @param    string    $ligne_total_tva    Amount of all taxes
	 *  @return    void
	 */
	public function update_totaux_add($ligne_total_ht, $ligne_total_tva)
	{
		// phpcs:enable
		$this->total_ht = $this->total_ht + $ligne_total_ht;
		$this->total_tva = $this->total_tva + $ligne_total_tva;
		$this->total_ttc = $this->total_ht + $this->total_tva;

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " total_ht = ".$this->total_ht;
		$sql .= " , total_ttc = ".$this->total_ttc;
		$sql .= " , total_tva = ".$this->total_tva;
		$sql .= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result):
			return 1;
		else :
			$this->error = $this->db->error();
			return -1;
		endif;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update total of an expense report when you delete a line.
	 *
	 *  @param    string    $ligne_total_ht    Amount without taxes
	 *  @param    string    $ligne_total_tva    Amount of all taxes
	 *  @return    void
	 */
	public function update_totaux_del($ligne_total_ht, $ligne_total_tva)
	{
		// phpcs:enable
		$this->total_ht = $this->total_ht - $ligne_total_ht;
		$this->total_tva = $this->total_tva - $ligne_total_tva;
		$this->total_ttc = $this->total_ht + $this->total_tva;

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " total_ht = ".$this->total_ht;
		$sql .= " , total_ttc = ".$this->total_ttc;
		$sql .= " , total_tva = ".$this->total_tva;
		$sql .= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result):
			return 1;
		else :
			$this->error = $this->db->error();
			return -1;
		endif;
	}

	/**
	 * addline
	 *
	 * @param    float       $qty                      Qty
	 * @param    double      $up                       Value init
	 * @param    int         $fk_c_type_fees           Type payment
	 * @param    string      $vatrate                  Vat rate (Can be '10' or '10 (ABC)')
	 * @param    string      $date                     Date
	 * @param    string      $comments                 Description
	 * @param    int         $fk_project               Project id
	 * @param    int         $fk_c_exp_tax_cat         Car category id
	 * @param    int         $type                     Type line
	 * @param    int         $fk_ecm_files             Id of ECM file to link to this expensereport line
	 * @return   int                                   <0 if KO, >0 if OK
	 */
	public function addline($qty = 0, $up = 0, $fk_c_type_fees = 0, $vatrate = 0, $date = '', $comments = '', $fk_project = 0, $fk_c_exp_tax_cat = 0, $type = 0, $fk_ecm_files = 0)
	{
		global $conf, $langs, $mysoc;

		dol_syslog(get_class($this)."::addline qty=$qty, up=$up, fk_c_type_fees=$fk_c_type_fees, vatrate=$vatrate, date=$date, fk_project=$fk_project, type=$type, comments=$comments", LOG_DEBUG);

		if ($this->status == self::STATUS_DRAFT)
		{
			if (empty($qty)) $qty = 0;
			if (empty($fk_c_type_fees) || $fk_c_type_fees < 0) $fk_c_type_fees = 0;
			if (empty($fk_c_exp_tax_cat) || $fk_c_exp_tax_cat < 0) $fk_c_exp_tax_cat = 0;
			if (empty($vatrate) || $vatrate < 0) $vatrate = 0;
			if (empty($date)) $date = '';
			if (empty($fk_project)) $fk_project = 0;

			$qty = price2num($qty);
			if (!preg_match('/\s*\((.*)\)/', $vatrate)) {
				$vatrate = price2num($vatrate); // $txtva can have format '5.0 (XXX)' or '5'
			}
			$up = price2num($up);

			$this->db->begin();

			$this->line = new ExpenseReportLine($this->db);

			$localtaxes_type = getLocalTaxesFromRate($vatrate, 0, $mysoc, $this->thirdparty);

			$vat_src_code = '';
			$reg = array();
			if (preg_match('/\s*\((.*)\)/', $vatrate, $reg))
			{
				$vat_src_code = $reg[1];
				$vatrate = preg_replace('/\s*\(.*\)/', '', $vatrate); // Remove code into vatrate.
			}
			$vatrate = preg_replace('/\*/', '', $vatrate);

			$seller = ''; // seller is unknown

			$tmp = calcul_price_total($qty, $up, 0, $vatrate, 0, 0, 0, 'TTC', 0, $type, $seller, $localtaxes_type);

			$this->line->value_unit = $up;
			$this->line->vat_src_code = $vat_src_code;
			$this->line->vatrate = price2num($vatrate);
			$this->line->total_ttc = $tmp[2];
			$this->line->total_ht = $tmp[0];
			$this->line->total_tva = $tmp[1];

			$this->line->fk_expensereport = $this->id;
			$this->line->qty = $qty;
			$this->line->date = $date;
			$this->line->fk_c_type_fees = $fk_c_type_fees;
			$this->line->fk_c_exp_tax_cat = $fk_c_exp_tax_cat;
			$this->line->comments = $comments;
			$this->line->fk_projet = $fk_project; // deprecated
			$this->line->fk_project = $fk_project;

			$this->line->fk_ecm_files = $fk_ecm_files;

			$this->applyOffset();
			$this->checkRules($type, $seller);

			$result = $this->line->insert(0, true);
			if ($result > 0)
			{
				$result = $this->update_price(); // This method is designed to add line from user input so total calculation must be done using 'auto' mode.
				if ($result > 0)
				{
					$this->db->commit();
					return $this->line->id;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->line->error;
				dol_syslog(get_class($this)."::addline error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			dol_syslog(get_class($this)."::addline status of expense report must be Draft to allow use of ->addline()", LOG_ERR);
			$this->error = 'ErrorExpenseNotDraft';
			return -3;
		}
	}

	/**
	 * Check constraint of rules and update price if needed
	 *
	 * @param	int		$type		type of line
	 * @param	string	$seller		seller, but actually he is unknown
	 * @return true or false
	 */
	public function checkRules($type = 0, $seller = '')
	{
		global $user, $conf, $db, $langs;

		$langs->load('trips');

		if (empty($conf->global->MAIN_USE_EXPENSE_RULE)) return true; // if don't use rules

		$rulestocheck = ExpenseReportRule::getAllRule($this->line->fk_c_type_fees, $this->line->date, $this->fk_user_author);

		$violation = 0;
		$rule_warning_message_tab = array();

		$current_total_ttc = $this->line->total_ttc;
		$new_current_total_ttc = $this->line->total_ttc;

		// check if one is violated
		foreach ($rulestocheck as $rule)
		{
			if (in_array($rule->code_expense_rules_type, array('EX_DAY', 'EX_MON', 'EX_YEA'))) $amount_to_test = $this->line->getExpAmount($rule, $this->fk_user_author, $rule->code_expense_rules_type);
			else $amount_to_test = $current_total_ttc; // EX_EXP

			$amount_to_test = $amount_to_test - $current_total_ttc + $new_current_total_ttc; // if amount as been modified by a previous rule

			if ($amount_to_test > $rule->amount)
			{
				$violation++;

				if ($rule->restrictive)
				{
					$this->error = 'ExpenseReportConstraintViolationError';
					$this->errors[] = $this->error;

					$new_current_total_ttc -= $amount_to_test - $rule->amount; // ex, entered 16€, limit 12€, subtracts 4€;
					$rule_warning_message_tab[] = $langs->trans('ExpenseReportConstraintViolationError', $rule->id, price($amount_to_test, 0, $langs, 1, -1, -1, $conf->currency), price($rule->amount, 0, $langs, 1, -1, -1, $conf->currency), $langs->trans('by'.$rule->code_expense_rules_type, price($new_current_total_ttc, 0, $langs, 1, -1, -1, $conf->currency)));
				} else {
					$this->error = 'ExpenseReportConstraintViolationWarning';
					$this->errors[] = $this->error;

					$rule_warning_message_tab[] = $langs->trans('ExpenseReportConstraintViolationWarning', $rule->id, price($amount_to_test, 0, $langs, 1, -1, -1, $conf->currency), price($rule->amount, 0, $langs, 1, -1, -1, $conf->currency), $langs->trans('nolimitby'.$rule->code_expense_rules_type));
				}

				// No break, we sould test if another rule is violated
			}
		}

		$this->line->rule_warning_message = implode('\n', $rule_warning_message_tab);

		if ($violation > 0)
		{
			$tmp = calcul_price_total($this->line->qty, $new_current_total_ttc / $this->line->qty, 0, $this->line->vatrate, 0, 0, 0, 'TTC', 0, $type, $seller);

			$this->line->value_unit = $tmp[5];
			$this->line->total_ttc = $tmp[2];
			$this->line->total_ht = $tmp[0];
			$this->line->total_tva = $tmp[1];

			return false;
		} else return true;
	}

	/**
	 * Method to apply the offset if needed
	 *
	 * @return boolean		true=applied, false=not applied
	 */
	public function applyOffset()
	{
		global $conf;

		if (empty($conf->global->MAIN_USE_EXPENSE_IK)) return false;

		$userauthor = new User($this->db);
		if ($userauthor->fetch($this->fk_user_author) <= 0)
		{
			$this->error = 'ErrorCantFetchUser';
			$this->errors[] = 'ErrorCantFetchUser';
			return false;
		}

		$range = ExpenseReportIk::getRangeByUser($userauthor, $this->line->fk_c_exp_tax_cat);

		if (empty($range))
		{
			$this->error = 'ErrorNoRangeAvailable';
			$this->errors[] = 'ErrorNoRangeAvailable';
			return false;
		}

		if (!empty($conf->global->MAIN_EXPENSE_APPLY_ENTIRE_OFFSET)) $ikoffset = $range->ikoffset;
		else $ikoffset = $range->ikoffset / 12; // The amount of offset is a global value for the year

		// Test if ikoffset has been applied for the current month
		if (!$this->offsetAlreadyGiven())
		{
			$new_up = $range->coef + ($ikoffset / $this->line->qty);
			$tmp = calcul_price_total($this->line->qty, $new_up, 0, $this->line->vatrate, 0, 0, 0, 'TTC', 0, $type, $seller);

			$this->line->value_unit = $tmp[5];
			$this->line->total_ttc = $tmp[2];
			$this->line->total_ht = $tmp[0];
			$this->line->total_tva = $tmp[1];

			return true;
		}

		return false;
	}

	/**
	 * If the sql find any rows then the ikoffset is already given (ikoffset is applied at the first expense report line)
	 *
	 * @return bool
	 */
	public function offsetAlreadyGiven()
	{
		$sql = 'SELECT e.rowid FROM '.MAIN_DB_PREFIX.'expensereport e';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'expensereport_det d ON (e.rowid = d.fk_expensereport)';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'c_type_fees f ON (d.fk_c_type_fees = f.id AND f.code = "EX_KME")';
		$sql .= ' WHERE e.fk_user_author = '.(int) $this->fk_user_author;
		$sql .= ' AND YEAR(d.date) = "'.dol_print_date($this->line->date, '%Y').'" AND MONTH(d.date) = "'.dol_print_date($this->line->date, '%m').'"';
		if (!empty($this->line->id)) $sql .= ' AND d.rowid <> '.$this->line->id;

		dol_syslog(get_class($this)."::offsetAlreadyGiven sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num > 0) return true;
		} else {
			dol_print_error($this->db);
		}

		return false;
	}

	/**
	 * Update an expense report line
	 *
	 * @param   int         $rowid                  Line to edit
	 * @param   int         $type_fees_id           Type payment
	 * @param   int         $projet_id              Project id
	 * @param   double      $vatrate                Vat rate. Can be '8.5' or '8.5* (8.5NPROM...)'
	 * @param   string      $comments               Description
	 * @param   float       $qty                    Qty
	 * @param   double      $value_unit             Value init
	 * @param   int         $date                   Date
	 * @param   int         $expensereport_id       Expense report id
	 * @param   int         $fk_c_exp_tax_cat       Id of category of car
	 * @param   int         $fk_ecm_files           Id of ECM file to link to this expensereport line
	 * @return  int                                 <0 if KO, >0 if OK
	 */
	public function updateline($rowid, $type_fees_id, $projet_id, $vatrate, $comments, $qty, $value_unit, $date, $expensereport_id, $fk_c_exp_tax_cat = 0, $fk_ecm_files = 0)
	{
		global $user, $mysoc;

		if ($this->status == self::STATUS_DRAFT || $this->status == self::STATUS_REFUSED)
		{
			$this->db->begin();

			$type = 0; // TODO What if type is service ?

			// We don't know seller and buyer for expense reports
			$seller = $mysoc;
			$buyer = new Societe($this->db);

			$localtaxes_type = getLocalTaxesFromRate($vatrate, 0, $buyer, $seller);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $vatrate, $reg))
			{
				$vat_src_code = $reg[1];
				$vatrate = preg_replace('/\s*\(.*\)/', '', $vatrate); // Remove code into vatrate.
			}
			$vatrate = preg_replace('/\*/', '', $vatrate);

			$tmp = calcul_price_total($qty, $value_unit, 0, $vatrate, 0, 0, 0, 'TTC', 0, $type, $seller, $localtaxes_type);

			// calcul total of line
			//$total_ttc  = price2num($qty*$value_unit, 'MT');

			$tx_tva = $vatrate / 100;
			$tx_tva = $tx_tva + 1;

			$this->line = new ExpenseReportLine($this->db);
			$this->line->comments        = $comments;
			$this->line->qty             = $qty;
			$this->line->value_unit      = $value_unit;
			$this->line->date            = $date;

			$this->line->fk_expensereport = $expensereport_id;
			$this->line->fk_c_type_fees  = $type_fees_id;
			$this->line->fk_c_exp_tax_cat = $fk_c_exp_tax_cat;
			$this->line->fk_projet       = $projet_id; // deprecated
			$this->line->fk_project      = $projet_id;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->vatrate = price2num($vatrate);
			$this->line->total_ttc = $tmp[2];
			$this->line->total_ht = $tmp[0];
			$this->line->total_tva = $tmp[1];
			$this->line->localtax1_tx = $localtaxes_type[1];
			$this->line->localtax2_tx = $localtaxes_type[3];
			$this->line->localtax1_type = $localtaxes_type[0];
			$this->line->localtax2_type = $localtaxes_type[2];

			$this->line->fk_ecm_files = $fk_ecm_files;

			$this->line->id = $rowid;

			// Select des infos sur le type fees
			$sql = "SELECT c.code as code_type_fees, c.label as libelle_type_fees";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_type_fees as c";
			$sql .= " WHERE c.id = ".$type_fees_id;
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$objp_fees = $this->db->fetch_object($resql);
				$this->line->type_fees_code      = $objp_fees->code_type_fees;
				$this->line->type_fees_libelle   = $objp_fees->libelle_type_fees;
				$this->db->free($resql);
			}

			// Select des informations du projet
			$sql = "SELECT p.ref as ref_projet, p.title as title_projet";
			$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
			$sql .= " WHERE p.rowid = ".$projet_id;
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp_projet = $this->db->fetch_object($resql);
				$this->line->projet_ref          = $objp_projet->ref_projet;
				$this->line->projet_title        = $objp_projet->title_projet;
				$this->db->free($resql);
			}

			$this->applyOffset();
			$this->checkRules();

			$result = $this->line->update($user);
			if ($result > 0)
			{
				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;
				$this->db->rollback();
				return -2;
			}
		}
	}

	/**
	 * deleteline
	 *
	 * @param   int     $rowid      Row id
	 * @param   User    $fuser      User
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function deleteline($rowid, $fuser = '')
	{
		$this->db->begin();

		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element_line;
		$sql .= ' WHERE rowid = '.$rowid;

		dol_syslog(get_class($this)."::deleteline sql=".$sql);
		$result = $this->db->query($sql);
		if (!$result)
		{
			$this->error = $this->db->error();
			dol_syslog(get_class($this)."::deleteline  Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * periode_existe
	 *
	 * @param   User       $fuser          User
	 * @param   integer    $date_debut     Start date
	 * @param   integer    $date_fin       End date
	 * @return  int                        <0 if KO, >0 if OK
	 */
	public function periode_existe($fuser, $date_debut, $date_fin)
	{
		// phpcs:enable
		$sql = "SELECT rowid, date_debut, date_fin";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE fk_user_author = '{$fuser->id}'";

		dol_syslog(get_class($this)."::periode_existe sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$num_rows = $this->db->num_rows($result); $i = 0;

			if ($num_rows > 0)
			{
				$date_d_form = $date_debut;
				$date_f_form = $date_fin;

				$existe = false;

				while ($i < $num_rows)
				{
					$objp = $this->db->fetch_object($result);

					$date_d_req = $this->db->jdate($objp->date_debut); // 3
					$date_f_req = $this->db->jdate($objp->date_fin); // 4

					if (!($date_f_form < $date_d_req || $date_d_form > $date_f_req)) $existe = true;

					$i++;
				}

				if ($existe) return 1;
				else return 0;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::periode_existe  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of people with permission to validate expense reports.
	 * Search for permission "approve expense report"
	 *
	 * @return  array       Array of user ids
	 */
	public function fetch_users_approver_expensereport()
	{
		// phpcs:enable
		$users_validator = array();

		$sql = "SELECT DISTINCT ur.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ur.fk_id = rd.id and rd.module = 'expensereport' AND rd.perms = 'approve'"; // Permission 'Approve';
		$sql .= "UNION";
		$sql .= " SELECT DISTINCT ugu.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_user as ugu, ".MAIN_DB_PREFIX."usergroup_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ugu.fk_usergroup = ur.fk_usergroup AND ur.fk_id = rd.id and rd.module = 'expensereport' AND rd.perms = 'approve'"; // Permission 'Approve';
		//print $sql;

		dol_syslog(get_class($this)."::fetch_users_approver_expensereport sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num_rows = $this->db->num_rows($result); $i = 0;
			while ($i < $num_rows)
			{
				$objp = $this->db->fetch_object($result);
				array_push($users_validator, $objp->fk_user);
				$i++;
			}
			return $users_validator;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_users_approver_expensereport  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Create a document onto disk accordign to template module.
	 *
	 *  @param      string      $modele         Force le mnodele a utiliser ('' to not force)
	 *  @param      Translate   $outputlangs    objet lang a utiliser pour traduction
	 *  @param      int         $hidedetails    Hide details of lines
	 *  @param      int         $hidedesc       Hide description
	 *  @param      int         $hideref        Hide ref
	 *  @param   null|array  $moreparams     Array to provide more information
	 *  @return     int                         0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf;

		$outputlangs->load("trips");

		if (!dol_strlen($modele)) {
			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($this->modelpdf)) {	// deprecated
				$modele = $this->modelpdf;
			} elseif (!empty($conf->global->EXPENSEREPORT_ADDON_PDF)) {
				$modele = $conf->global->EXPENSEREPORT_ADDON_PDF;
			}
		}

		if (!empty($modele)) {
			$modelpath = "core/modules/expensereport/doc/";

			return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		} else {
			return 0;
		}
	}

	/**
	 * List of types
	 *
	 * @param   int     $active     Active or not
	 * @return  array
	 */
	public function listOfTypes($active = 1)
	{
		global $langs;
		$ret = array();
		$sql = "SELECT id, code, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_fees";
		$sql .= " WHERE active = ".$active;
		dol_syslog(get_class($this)."::listOfTypes", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				$ret[$obj->code] = (($langs->transnoentitiesnoconv($obj->code) != $obj->code) ? $langs->transnoentitiesnoconv($obj->code) : $obj->label);
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}
		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Charge indicateurs this->nb pour le tableau de bord
	 *
	 *      @return     int         <0 if KO, >0 if OK
	 */
	public function load_state_board()
	{
		// phpcs:enable
		global $conf, $user;

		$this->nb = array();

		$sql = "SELECT count(ex.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as ex";
		$sql .= " WHERE ex.fk_statut > 0";
		$sql .= " AND ex.entity IN (".getEntity('expensereport').")";
		if (empty($user->rights->expensereport->readall))
		{
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (ex.fk_user_author IN (".join(',', $userchildids).")";
			$sql .= " OR ex.fk_user_validator IN (".join(',', $userchildids)."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["expensereports"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Objet user
	 *      @param  string  $option         'topay' or 'toapprove'
	 *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $option = 'topay')
	{
		// phpcs:enable
		global $conf, $langs;

		if ($user->socid) return -1; // protection pour eviter appel par utilisateur externe

		$now = dol_now();

		$sql = "SELECT ex.rowid, ex.date_valid";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as ex";
		if ($option == 'toapprove') $sql .= " WHERE ex.fk_statut = ".self::STATUS_VALIDATED;
		else $sql .= " WHERE ex.fk_statut = ".self::STATUS_APPROVED;
		$sql .= " AND ex.entity IN (".getEntity('expensereport').")";
		if (empty($user->rights->expensereport->readall))
		{
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (ex.fk_user_author IN (".join(',', $userchildids).")";
			$sql .= " OR ex.fk_user_validator IN (".join(',', $userchildids)."))";
		}

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$langs->load("trips");

			$response = new WorkboardResponse();
			if ($option == 'toapprove')
			{
				$response->warning_delay = $conf->expensereport->approve->warning_delay / 60 / 60 / 24;
				$response->label = $langs->trans("ExpenseReportsToApprove");
				$response->labelShort = $langs->trans("ToApprove");
				$response->url = DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm&amp;statut='.self::STATUS_VALIDATED;
			} else {
				$response->warning_delay = $conf->expensereport->payment->warning_delay / 60 / 60 / 24;
				$response->label = $langs->trans("ExpenseReportsToPay");
				$response->labelShort = $langs->trans("StatusToPay");
				$response->url = DOL_URL_ROOT.'/expensereport/list.php?mainmenu=hrm&amp;statut='.self::STATUS_APPROVED;
			}
			$response->img = img_object('', "trip");

			while ($obj = $this->db->fetch_object($resql))
			{
				$response->nbtodo++;

				if ($option == 'toapprove')
				{
					if ($this->db->jdate($obj->date_valid) < ($now - $conf->expensereport->approve->warning_delay)) {
						$response->nbtodolate++;
					}
				} else {
					if ($this->db->jdate($obj->date_valid) < ($now - $conf->expensereport->payment->warning_delay)) {
						$response->nbtodolate++;
					}
				}
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 * Return if an expense report is late or not
	 *
	 * @param  string  $option          'topay' or 'toapprove'
	 * @return boolean                  True if late, False if not late
	 */
	public function hasDelay($option)
	{
		global $conf;

		// Only valid expenses reports
		if ($option == 'toapprove' && $this->status != 2) return false;
		if ($option == 'topay' && $this->status != 5) return false;

		$now = dol_now();
		if ($option == 'toapprove') {
			return ($this->datevalid ? $this->datevalid : $this->date_valid) < ($now - $conf->expensereport->approve->warning_delay);
		} else {
			return ($this->datevalid ? $this->datevalid : $this->date_valid) < ($now - $conf->expensereport->payment->warning_delay);
		}
	}

	/**
	 *	Return if object was dispatched into bookkeeping
	 *
	 *	@return     int         <0 if KO, 0=no, 1=yes
	 */
	public function getVentilExportCompta()
	{
		$alreadydispatched = 0;

		$type = 'expense_report';

		$sql = " SELECT COUNT(ab.rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab WHERE ab.doc_type='".$this->db->escape($type)."' AND ab.fk_doc = ".$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$alreadydispatched = $obj->nb;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		if ($alreadydispatched)
		{
			return 1;
		}
		return 0;
	}

	/**
	 * 	Return amount of payments already done
	 *
	 *  @return		int						Amount of payment already done, <0 if KO
	 */
	public function getSumPayments()
	{
		$table = 'payment_expensereport';
		$field = 'fk_expensereport';

		$sql = 'SELECT sum(amount) as amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$table;
		$sql .= ' WHERE '.$field.' = '.$this->id;

		dol_syslog(get_class($this)."::getSumPayments", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return (empty($obj->amount) ? 0 : $obj->amount);
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
}


/**
 * Class of expense report details lines
 */
class ExpenseReportLine
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int ID
	 */
	public $rowid;

	public $comments;
	public $qty;
	public $value_unit;
	public $date;

	/**
	 * @var int ID
	 */
	public $fk_c_type_fees;

	/**
	 * @var int ID
	 */
	public $fk_c_exp_tax_cat;

	/**
	 * @var int ID
	 */
	public $fk_projet;

	/**
	 * @var int ID
	 */
	public $fk_expensereport;

	public $type_fees_code;
	public $type_fees_libelle;

	public $projet_ref;
	public $projet_title;

	public $vatrate;
	public $total_ht;
	public $total_tva;
	public $total_ttc;

	/**
	 * @var int ID into llx_ecm_files table to link line to attached file
	 */
	public $fk_ecm_files;


	/**
	 * Constructor
	 *
	 * @param DoliDB    $db     Handlet database
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Fetch record for expense report detailed line
	 *
	 * @param   int     $rowid      Id of object to load
	 * @return  int                 <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT fde.rowid, fde.fk_expensereport, fde.fk_c_type_fees, fde.fk_c_exp_tax_cat, fde.fk_projet as fk_project, fde.date,';
		$sql .= ' fde.tva_tx as vatrate, fde.vat_src_code, fde.comments, fde.qty, fde.value_unit, fde.total_ht, fde.total_tva, fde.total_ttc, fde.fk_ecm_files,';
		$sql .= ' ctf.code as type_fees_code, ctf.label as type_fees_libelle,';
		$sql .= ' pjt.rowid as projet_id, pjt.title as projet_title, pjt.ref as projet_ref';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expensereport_det as fde';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as ctf ON fde.fk_c_type_fees=ctf.id'; // Sometimes type of expense report has been removed, so we use a left join here.
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pjt ON fde.fk_projet=pjt.rowid';
		$sql .= ' WHERE fde.rowid = '.$rowid;

		$result = $this->db->query($sql);

		if ($result)
		{
			$objp = $this->db->fetch_object($result);

			$this->rowid = $objp->rowid;
			$this->id = $objp->rowid;
			$this->ref = $objp->ref;
			$this->fk_expensereport = $objp->fk_expensereport;
			$this->comments = $objp->comments;
			$this->qty = $objp->qty;
			$this->date = $objp->date;
			$this->dates = $this->db->jdate($objp->date);
			$this->value_unit = $objp->value_unit;
			$this->fk_c_type_fees = $objp->fk_c_type_fees;
			$this->fk_c_exp_tax_cat = $objp->fk_c_exp_tax_cat;
			$this->fk_projet = $objp->fk_project; // deprecated
			$this->fk_project = $objp->fk_project;
			$this->type_fees_code = $objp->type_fees_code;
			$this->type_fees_libelle = $objp->type_fees_libelle;
			$this->projet_ref = $objp->projet_ref;
			$this->projet_title = $objp->projet_title;
			$this->vatrate = $objp->vatrate;
			$this->vat_src_code = $objp->vat_src_code;
			$this->total_ht = $objp->total_ht;
			$this->total_tva = $objp->total_tva;
			$this->total_ttc = $objp->total_ttc;
			$this->fk_ecm_files = $objp->fk_ecm_files;

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Insert a line of expense report
	 *
	 * @param   int     $notrigger      1=No trigger
	 * @param   bool    $fromaddline    false=keep default behavior, true=exclude the update_price() of parent object
	 * @return  int                     <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0, $fromaddline = false)
	{
		global $langs, $user, $conf;

		$error = 0;

		dol_syslog("ExpenseReportLine::Insert", LOG_DEBUG);

		// Clean parameters
		$this->comments = trim($this->comments);
		if (empty($this->value_unit)) $this->value_unit = 0;
		$this->qty = price2num($this->qty);
		$this->vatrate = price2num($this->vatrate);
		if (empty($this->fk_c_exp_tax_cat)) $this->fk_c_exp_tax_cat = 0;

		$this->db->begin();

		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'expensereport_det';
		$sql .= ' (fk_expensereport, fk_c_type_fees, fk_projet,';
		$sql .= ' tva_tx, vat_src_code, comments, qty, value_unit, total_ht, total_tva, total_ttc, date, rule_warning_message, fk_c_exp_tax_cat, fk_ecm_files)';
		$sql .= " VALUES (".$this->db->escape($this->fk_expensereport).",";
		$sql .= " ".$this->db->escape($this->fk_c_type_fees).",";
		$sql .= " ".$this->db->escape((!empty($this->fk_project) && $this->fk_project > 0) ? $this->fk_project : ((!empty($this->fk_projet) && $this->fk_projet > 0) ? $this->fk_projet : 'null')).",";
		$sql .= " ".$this->db->escape($this->vatrate).",";
		$sql .= " '".$this->db->escape(empty($this->vat_src_code) ? '' : $this->vat_src_code)."',";
		$sql .= " '".$this->db->escape($this->comments)."',";
		$sql .= " ".$this->db->escape($this->qty).",";
		$sql .= " ".$this->db->escape($this->value_unit).",";
		$sql .= " ".$this->db->escape($this->total_ht).",";
		$sql .= " ".$this->db->escape($this->total_tva).",";
		$sql .= " ".$this->db->escape($this->total_ttc).",";
		$sql .= " '".$this->db->idate($this->date)."',";
		$sql .= " ".(empty($this->rule_warning_message) ? 'null' : "'".$this->db->escape($this->rule_warning_message)."'").",";
		$sql .= " ".$this->db->escape($this->fk_c_exp_tax_cat).",";
		$sql .= " ".($this->fk_ecm_files > 0 ? $this->fk_ecm_files : 'null');
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'expensereport_det');

			if (!$fromaddline)
			{
				$tmpparent = new ExpenseReport($this->db);
				$tmpparent->fetch($this->fk_expensereport);
				$result = $tmpparent->update_price();
				if ($result < 0)
				{
					$error++;
					$this->error = $tmpparent->error;
					$this->errors = $tmpparent->errors;
				}
			}
		} else {
			$error++;
		}

		if (!$error)
		{
			$this->db->commit();
			return $this->id;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("ExpenseReportLine::insert Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 * Function to get total amount in expense reports for a same rule
	 *
	 * @param  ExpenseReportRule $rule		object rule to check
	 * @param  int				 $fk_user	user author id
	 * @param  string			 $mode		day|EX_DAY / month|EX_MON / year|EX_YEA to get amount
	 * @return float                        Amount
	 */
	public function getExpAmount(ExpenseReportRule $rule, $fk_user, $mode = 'day')
	{
		$amount = 0;

		$sql = 'SELECT SUM(d.total_ttc) as total_amount';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expensereport_det d';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'expensereport e ON (d.fk_expensereport = e.rowid)';
		$sql .= ' WHERE e.fk_user_author = '.$fk_user;
		if (!empty($this->id)) $sql .= ' AND d.rowid <> '.$this->id;
		$sql .= ' AND d.fk_c_type_fees = '.$rule->fk_c_type_fees;
		if ($mode == 'day' || $mode == 'EX_DAY') $sql .= ' AND d.date = \''.dol_print_date($this->date, '%Y-%m-%d').'\'';
		elseif ($mode == 'mon' || $mode == 'EX_MON') $sql .= ' AND DATE_FORMAT(d.date, \'%Y-%m\') = \''.dol_print_date($this->date, '%Y-%m').'\''; // @todo DATE_FORMAT is forbidden
		elseif ($mode == 'year' || $mode == 'EX_YEA') $sql .= ' AND DATE_FORMAT(d.date, \'%Y\') = \''.dol_print_date($this->date, '%Y').'\''; // @todo DATE_FORMAT is forbidden

		dol_syslog('ExpenseReportLine::getExpAmount');

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			if ($num > 0)
			{
				$obj = $this->db->fetch_object($resql);
				$amount = (double) $obj->total_amount;
			}
		} else {
			dol_print_error($this->db);
		}

		return $amount + $this->total_ttc;
	}

	/**
	 * Update line
	 *
	 * @param   User    $user      User
	 * @return  int                <0 if KO, >0 if OK
	 */
	public function update(User $user)
	{
		global $langs, $conf;

		$error = 0;

		// Clean parameters
		$this->comments = trim($this->comments);
		$this->vatrate = price2num($this->vatrate);
		$this->value_unit = price2num($this->value_unit);
		if (empty($this->fk_c_exp_tax_cat)) $this->fk_c_exp_tax_cat = 0;

		$this->db->begin();

		// Update line in database
		$sql = "UPDATE ".MAIN_DB_PREFIX."expensereport_det SET";
		$sql .= " comments='".$this->db->escape($this->comments)."'";
		$sql .= ",value_unit=".$this->db->escape($this->value_unit);
		$sql .= ",qty=".$this->db->escape($this->qty);
		$sql .= ",date='".$this->db->idate($this->date)."'";
		$sql .= ",total_ht=".$this->db->escape($this->total_ht)."";
		$sql .= ",total_tva=".$this->db->escape($this->total_tva)."";
		$sql .= ",total_ttc=".$this->db->escape($this->total_ttc)."";
		$sql .= ",tva_tx=".$this->db->escape($this->vatrate);
		$sql .= ",vat_src_code='".$this->db->escape($this->vat_src_code)."'";
		$sql .= ",rule_warning_message='".$this->db->escape($this->rule_warning_message)."'";
		$sql .= ",fk_c_exp_tax_cat=".$this->db->escape($this->fk_c_exp_tax_cat);
		$sql .= ",fk_ecm_files=".($this->fk_ecm_files > 0 ? $this->fk_ecm_files : 'null');
		if ($this->fk_c_type_fees) $sql .= ",fk_c_type_fees=".$this->db->escape($this->fk_c_type_fees);
		else $sql .= ",fk_c_type_fees=null";
		if ($this->fk_project > 0) $sql .= ",fk_projet=".$this->db->escape($this->fk_project);
		else $sql .= ",fk_projet=null";
		$sql .= " WHERE rowid = ".$this->db->escape($this->rowid ? $this->rowid : $this->id);

		dol_syslog("ExpenseReportLine::update sql=".$sql);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$tmpparent = new ExpenseReport($this->db);
			$result = $tmpparent->fetch($this->fk_expensereport);
			if ($result > 0)
			{
				$result = $tmpparent->update_price();
				if ($result < 0)
				{
					$error++;
					$this->error = $tmpparent->error;
					$this->errors = $tmpparent->errors;
				}
			} else {
				$error++;
				$this->error = $tmpparent->error;
				$this->errors = $tmpparent->errors;
			}
		} else {
			$error++;
			dol_print_error($this->db);
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("ExpenseReportLine::update Error ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -2;
		}
	}
}
