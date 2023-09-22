<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2010-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2016 Ferran Marcet       <fmarcet@2byte.es>
 * Copyright (C) 2018      Nicolas ZABOURI     <info@inovea-conseil.com>
 * Copyright (C) 2019		JC Prieto			<jcprieto@virtual20.com><prietojc@gmail.com>
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
 * \file       htdocs/compta/prelevement/class/bonprelevement.class.php
 * \ingroup    prelevement
 * \brief      File of withdrawal receipts class
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';


/**
 *	Class to manage withdrawal receipts
 */
class BonPrelevement extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'widthdraw';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'prelevement_bons';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'payment';

	public $date_echeance;
	public $raison_sociale;
	public $reference_remise;
	public $emetteur_code_guichet;
	public $emetteur_numero_compte;
	public $emetteur_code_banque;
	public $emetteur_number_key;
	public $sepa_xml_pti_in_ctti;

	public $emetteur_iban;
	public $emetteur_bic;
	public $emetteur_ics;

	public $user_trans;
	public $user_credit;

	public $total;
	public $fetched;
	public $labelStatus = array();

	public $factures = array();

	public $invoice_in_error = array();
	public $thirdparty_in_error = array();

	/**
	 * @var resource	Handler of the file for direct debit or credit transfer order
	 */
	public $file;

	/*
	 * @var string filename
	 */
	public $filename;

	const STATUS_DRAFT = 0;
	const STATUS_TRANSFERED = 1;
	const STATUS_CREDITED = 2;		// STATUS_CREDITED and STATUS_DEBITED is same. Difference is in ->type
	const STATUS_DEBITED = 2;		// STATUS_CREDITED and STATUS_DEBITED is same. Difference is in ->type


	/**
	 *  'type' field format:
	 *  	'integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]',
	 *  	'select' (list of values are in 'options'),
	 *  	'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]',
	 *  	'chkbxlst:...',
	 *  	'varchar(x)',
	 *  	'text', 'text:none', 'html',
	 *   	'double(24,8)', 'real', 'price',
	 *  	'date', 'datetime', 'timestamp', 'duration',
	 *  	'boolean', 'checkbox', 'radio', 'array',
	 *  	'mail', 'phone', 'url', 'password', 'ip'
	 *		Note: Filter must be a Dolibarr filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'alwayseditable' says if field can be modified also when status is not draft ('1' or '0')
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 or 2 if field can be used for measure. Field type must be summable like integer or double(24,8). Use 1 in most cases, or 2 if you don't want to see the column total into list (for example for percentage)
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *	'validate' is 1 if need to validate with $this->validateField()
	 *  'copytoclipboard' is 1 or 2 to allow to add a picto to copy value into clipboard (1=picto after label, 2=picto after value)
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>0,),
		'ref' => array('type'=>'varchar(12)', 'label'=>'Ref', 'enabled'=>'1', 'position'=>15, 'notnull'=>0, 'visible'=>-1, 'csslist'=>'tdoverflowmax150', 'showoncombobox'=>'1',),
		'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>25, 'notnull'=>0, 'visible'=>-1,),
		'amount' => array('type'=>'double(24,8)', 'label'=>'Amount', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
		'statut' => array('type'=>'smallint(6)', 'label'=>'Statut', 'enabled'=>'1', 'position'=>500, 'notnull'=>0, 'visible'=>-1, 'arrayofkeyval'=>array(0=>'Wait', 1=>'Transfered', 2=>'Credited')),
		'credite' => array('type'=>'smallint(6)', 'label'=>'Credite', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>-1,),
		'note' => array('type'=>'text', 'label'=>'Note', 'enabled'=>'1', 'position'=>45, 'notnull'=>0, 'visible'=>-1,),
		'date_trans' => array('type'=>'datetime', 'label'=>'Datetrans', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-1,),
		'method_trans' => array('type'=>'smallint(6)', 'label'=>'Methodtrans', 'enabled'=>'1', 'position'=>55, 'notnull'=>0, 'visible'=>-1,),
		'fk_user_trans' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkusertrans', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>-1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'tdoverflowmax150',),
		'date_credit' => array('type'=>'datetime', 'label'=>'Datecredit', 'enabled'=>'1', 'position'=>65, 'notnull'=>0, 'visible'=>-1,),
		'fk_user_credit' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkusercredit', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>-1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'tdoverflowmax150',),
		'type' => array('type'=>'varchar(16)', 'label'=>'Type', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>-1,),
		'fk_bank_account' => array('type'=>'integer', 'label'=>'Fkbankaccount', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>-1, 'css'=>'maxwidth500 widthcentpercentminusxx',),
	);
	public $rowid;
	public $ref;
	public $datec;
	public $amount;
	public $statut;
	public $credite;
	public $note;
	public $date_trans;
	public $method_trans;
	public $fk_user_trans;
	public $date_credit;
	public $fk_user_credit;
	public $type;
	public $fk_bank_account;
	// END MODULEBUILDER PROPERTIES



	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      	Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->filename = '';

		$this->date_echeance = dol_now();
		$this->raison_sociale = "";
		$this->reference_remise = "";

		$this->emetteur_code_guichet = "";
		$this->emetteur_numero_compte = "";
		$this->emetteur_code_banque = "";
		$this->emetteur_number_key = "";
		$this->sepa_xml_pti_in_ctti = false;

		$this->emetteur_iban = "";
		$this->emetteur_bic = "";
		$this->emetteur_ics = "";

		$this->factures = array();

		$this->methodes_trans = array(0 => 'Internet', 2 => 'Email', 3 => 'Api');

		$this->fetched = 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Add invoice to withdrawal
	 *
	 * @param	int		$invoice_id 	id invoice to add
	 * @param	int		$client_id  	id invoice customer
	 * @param	string	$client_nom 	customer name
	 * @param	int		$amount 		amount of invoice
	 * @param	string	$code_banque 	code of bank withdrawal
	 * @param	string	$code_guichet 	code of bank's office
	 * @param	string	$number bank 	account number
	 * @param	string	$number_key 	number key of account number
	 * @param	string	$type			'debit-order' or 'bank-transfer'
	 * @return	int						>0 if OK, <0 if KO
	 */
	public function AddFacture($invoice_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number, $number_key, $type = 'debit-order')
	{
		// phpcs:enable
		$result = 0;
		$line_id = 0;

		// Add lines
		$result = $this->addline($line_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number, $number_key);

		if ($result == 0) {
			if ($line_id > 0) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement (";
				if ($type != 'bank-transfer') {
					$sql .= "fk_facture";
				} else {
					$sql .= "fk_facture_fourn";
				}
				$sql .= ",fk_prelevement_lignes";
				$sql .= ") VALUES (";
				$sql .= ((int) $invoice_id);
				$sql .= ", ".((int) $line_id);
				$sql .= ")";

				if ($this->db->query($sql)) {
					$result = 0;
				} else {
					$result = -1;
					$this->errors[] = get_class($this)."::AddFacture ".$this->db->lasterror;
					dol_syslog(get_class($this)."::AddFacture Error $result");
				}
			} else {
				$result = -2;
				$this->errors[] = get_class($this)."::AddFacture linedid Empty";
				dol_syslog(get_class($this)."::AddFacture Error $result");
			}
		} else {
			$result = -3;
			dol_syslog(get_class($this)."::AddFacture Error $result");
		}

		return $result;
	}

	/**
	 *	Add line to withdrawal
	 *
	 *	@param	int		$line_id 		id line to add
	 *	@param	int		$client_id  	id invoice customer
	 *	@param	string	$client_nom 	customer name
	 *	@param	int		$amount 		amount of invoice
	 *	@param	string	$code_banque 	code of bank withdrawal
	 *	@param	string	$code_guichet 	code of bank's office
	 *	@param	string	$number 		bank account number
	 *	@param  string	$number_key 	number key of account number
	 *	@return	int						>0 if OK, <0 if KO
	 */
	public function addline(&$line_id, $client_id, $client_nom, $amount, $code_banque, $code_guichet, $number, $number_key)
	{
		$result = -1;
		$concat = 0;

		if ($concat == 1) {
			/*
			 * We aggregate the lines
			 */
			$sql = "SELECT rowid";
			$sql .= " FROM  ".MAIN_DB_PREFIX."prelevement_lignes";
			$sql .= " WHERE fk_prelevement_bons = ".((int) $this->id);
			$sql .= " AND fk_soc =".((int) $client_id);
			$sql .= " AND code_banque = '".$this->db->escape($code_banque)."'";
			$sql .= " AND code_guichet = '".$this->db->escape($code_guichet)."'";
			$sql .= " AND number = '".$this->db->escape($number)."'";

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
			} else {
				$result = -1;
			}
		} else {
			/*
			 * No aggregate
			 */
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_lignes (";
			$sql .= "fk_prelevement_bons";
			$sql .= ", fk_soc";
			$sql .= ", client_nom";
			$sql .= ", amount";
			$sql .= ", code_banque";
			$sql .= ", code_guichet";
			$sql .= ", number";
			$sql .= ", cle_rib";
			$sql .= ") VALUES (";
			$sql .= $this->id;
			$sql .= ", ".((int) $client_id);
			$sql .= ", '".$this->db->escape($client_nom)."'";
			$sql .= ", ".((float) price2num($amount));
			$sql .= ", '".$this->db->escape($code_banque)."'";
			$sql .= ", '".$this->db->escape($code_guichet)."'";
			$sql .= ", '".$this->db->escape($number)."'";
			$sql .= ", '".$this->db->escape($number_key)."'";
			$sql .= ")";

			if ($this->db->query($sql)) {
				$line_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_lignes");
				$result = 0;
			} else {
				$this->errors[] = get_class($this)."::addline Error -2 ".$this->db->lasterror;
				dol_syslog(get_class($this)."::addline Error -2");
				$result = -2;
			}
		}

		return $result;
	}

	/**
	 *	Return error string
	 *
	 *  @param	int		$error 		 Id of error
	 *	@return	string               Error string
	 */
	public function getErrorString($error)
	{
		global $langs;

		$errors = array();

		$errors[1027] = $langs->trans("DateInvalid");

		return $errors[abs($error)];
	}

	/**
	 *	Get object and lines from database
	 *
	 *	@param	int		$rowid		Id of object to load
	 *  @param	string	$ref		Ref of direct debit
	 *	@return	int					>0 if OK, <0 if KO
	 */
	public function fetch($rowid, $ref = '')
	{
		$sql = "SELECT p.rowid, p.ref, p.amount, p.note";
		$sql .= ", p.datec as dc";
		$sql .= ", p.date_trans as date_trans";
		$sql .= ", p.method_trans, p.fk_user_trans";
		$sql .= ", p.date_credit as date_credit";
		$sql .= ", p.fk_user_credit";
		$sql .= ", p.type";
		$sql .= ", p.fk_bank_account";
		$sql .= ", p.statut as status";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
		$sql .= " WHERE p.entity IN (".getEntity('invoice').")";
		if ($rowid > 0) {
			$sql .= " AND p.rowid = ".((int) $rowid);
		} else {
			$sql .= " AND p.ref = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id             = $obj->rowid;
				$this->ref            = $obj->ref;
				$this->amount         = $obj->amount;
				$this->note           = $obj->note;
				$this->datec          = $this->db->jdate($obj->dc);

				$this->date_trans     = $this->db->jdate($obj->date_trans);
				$this->method_trans   = $obj->method_trans;
				$this->user_trans     = $obj->fk_user_trans;

				$this->date_credit    = $this->db->jdate($obj->date_credit);
				$this->user_credit    = $obj->fk_user_credit;

				$this->type           = $obj->type;
				$this->fk_bank_account = $obj->fk_bank_account;

				$this->status         = $obj->status;
				$this->statut         = $obj->status; // For backward compatibility

				$this->fetched = 1;

				return 1;
			} else {
				dol_syslog(get_class($this)."::Fetch Erreur aucune ligne retournee");
				return -1;
			}
		} else {
			return -2;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set direct debit or credit transfer order to "paid" status.
	 *  Then create the payment for each invoice of the prelemevement_bon.
	 *
	 *	@param	User	$user			Id of user
	 *	@param 	int		$date			date of action
	 *	@return	int						>0 if OK, <0 if KO
	 */
	public function set_infocredit($user, $date)
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		if ($this->fetched == 1) {
			if ($date < $this->date_trans) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorDateOfMovementLowerThanDateOfFileTransmission');
				dol_syslog("bon-prelevment::set_infocredit 1027 ".$this->error);
				return -1027;
			}

			$this->db->begin();

			$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
			$sql .= " SET fk_user_credit = ".$user->id;
			$sql .= ", statut = ".self::STATUS_CREDITED;
			$sql .= ", date_credit = '".$this->db->idate($date)."'";
			$sql .= " WHERE rowid=".((int) $this->id);
			$sql .= " AND entity = ".((int) $conf->entity);
			$sql .= " AND statut = ".self::STATUS_TRANSFERED;

			$resql = $this->db->query($sql);
			if ($resql) {
				$langs->load('withdrawals');
				$subject = $langs->trans("InfoCreditSubject", $this->ref);
				$message = $langs->trans("InfoCreditMessage", $this->ref, dol_print_date($date, 'dayhour'));

				// Add payment of withdrawal into bank
				$fk_bank_account = $this->fk_bank_account;
				if (empty($fk_bank_account)) {
					$fk_bank_account = ($this->type == 'bank-transfer' ? $conf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT : $conf->global->PRELEVEMENT_ID_BANKACCOUNT);
				}

				$facs = array();
				$amounts = array();
				$amountsperthirdparty = array();

				$facs = $this->getListInvoices(1);

				// Loop on each invoice. $facs=array(0=>id, 1=>amount requested)
				$num = count($facs);
				for ($i = 0; $i < $num; $i++) {
					if ($this->type == 'bank-transfer') {
						$fac = new FactureFournisseur($this->db);
					} else {
						$fac = new Facture($this->db);
					}

					$result = $fac->fetch($facs[$i][0]);

					$amounts[$fac->id] = $facs[$i][1];
					$amountsperthirdparty[$fac->socid][$fac->id] = $facs[$i][1];

					$totalpaid = $fac->getSommePaiement();
					$totalcreditnotes = $fac->getSumCreditNotesUsed();
					$totaldeposits = $fac->getSumDepositsUsed();
					$alreadypayed = $totalpaid + $totalcreditnotes + $totaldeposits;

					// @TODO Move this after creation of payment
					if (price2num($alreadypayed + $facs[$i][1], 'MT') == $fac->total_ttc) {
						$result = $fac->setPaid($user);
						if ($result < 0) {
							$this->error = $fac->error;
							$this->errors = $fac->errors;
						}
					}
				}
				//var_dump($amountsperthirdparty);exit;

				// Make one payment per customer
				foreach ($amountsperthirdparty as $thirdpartyid => $cursoramounts) {
					if ($this->type == 'bank-transfer') {
						$paiement = new PaiementFourn($this->db);
					} else {
						$paiement = new Paiement($this->db);
					}
					$paiement->datepaye = $date;
					$paiement->amounts = $cursoramounts; // Array with detail of dispatching of payments for each invoice

					if ($this->type == 'bank-transfer') {
						$paiement->paiementid = 2;
						$paiement->paiementcode = 'VIR';
					} else {
						$paiement->paiementid = 3;
						$paiement->paiementcode = 'PRE';
					}

					$paiement->num_payment = $this->ref; // Set ref of direct debit note
					$paiement->id_prelevement = $this->id;

					$paiement_id = $paiement->create($user); // This use ->paiementid, that is ID of payment mode
					if ($paiement_id < 0) {
						$error++;
						$this->error = $paiement->error;
						$this->errors = $paiement->errors;
						dol_syslog(get_class($this)."::set_infocredit AddPayment Error ".$this->error);
					} else {
						if ($this->type == 'bank-transfer') {
							$modeforaddpayment = 'payment_supplier';
							$labelforaddpayment = '(SupplierInvoicePayment)';
							$addbankurl = 'credit-transfer';
						} else {
							$modeforaddpayment = 'payment';
							$labelforaddpayment = '(CustomerInvoicePayment)';
							$addbankurl = 'direct-debit';	// = 'directdebit'
						}

						$result = $paiement->addPaymentToBank($user, $modeforaddpayment, $labelforaddpayment, $fk_bank_account, '', '', 0, '', $addbankurl);
						if ($result < 0) {
							$error++;
							$this->error = $paiement->error;
							$this->errors = $paiement->errors;
							dol_syslog(get_class($this)."::set_infocredit AddPaymentToBank Error ".$this->error);
						}
					}
				}

				// Update withdrawal line
				// TODO: Translate to ligneprelevement.class.php
				if (!$error) {
					$sql = " UPDATE ".MAIN_DB_PREFIX."prelevement_lignes";
					$sql .= " SET statut = 2";
					$sql .= " WHERE fk_prelevement_bons = ".((int) $this->id);

					if (!$this->db->query($sql)) {
						dol_syslog(get_class($this)."::set_infocredit Update lines Error");
						$error++;
					}
				}
			} else {
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::set_infocredit Update Bons Error");
				$error++;
			}

			/*
			 * End of procedure
			 */
			if ($error == 0) {
				$this->date_credit = $date;
				$this->statut = self::STATUS_CREDITED;

				$this->db->commit();
				return 0;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			return -1026;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set withdrawal to transmited status
	 *
	 *	@param	User		$user		id of user
	 *	@param 	int	$date		date of action
	 *	@param	string		$method		method of transmision to bank (0=Internet, 1=Api...)
	 *	@return	int						>0 if OK, <0 if KO
	 */
	public function set_infotrans($user, $date, $method)
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		dol_syslog(get_class($this)."::set_infotrans Start", LOG_INFO);
		if ($this->db->begin()) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons ";
			$sql .= " SET fk_user_trans = ".$user->id;
			$sql .= " , date_trans = '".$this->db->idate($date)."'";
			$sql .= " , method_trans = ".((int) $method);
			$sql .= " , statut = ".self::STATUS_TRANSFERED;
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND entity = ".((int) $conf->entity);
			$sql .= " AND statut = 0";

			if ($this->db->query($sql)) {
				$this->method_trans = $method;
				$langs->load('withdrawals');
				$subject = $langs->trans("InfoTransSubject", $this->ref);
				$message = $langs->trans("InfoTransMessage", $this->ref, dolGetFirstLastname($user->firstname, $user->lastname));
				$message .= $langs->trans("InfoTransData", price($this->amount), $this->methodes_trans[$this->method_trans], dol_print_date($date, 'day'));

				// TODO Call trigger to create a notification using notification module
			} else {
				$error++;
			}

			if ($error == 0) {
				$this->date_trans = $date;
				$this->statut = 1;
				$this->user_trans = $user->id;
				$this->db->commit();

				return 0;
			} else {
				$this->db->rollback();
				dol_syslog(get_class($this)."::set_infotrans ROLLBACK", LOG_ERR);

				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::set_infotrans Ouverture transaction SQL impossible", LOG_CRIT);
			return -2;
		}
	}

	/**
	 *	Get invoice list
	 *
	 *  @param 	int		$amounts 	If you want to get the amount of the order for each invoice
	 *	@return	array 				Id of invoices
	 */
	private function getListInvoices($amounts = 0)
	{
		global $conf;

		$arr = array();

		/*
		 * Returns all invoices presented within same order
		 */
		$sql = "SELECT ";
		if ($this->type == 'bank-transfer') {
			$sql .= " pf.fk_facture_fourn";
		} else {
			$sql .= " pf.fk_facture";
		}
		if ($amounts) {
			$sql .= ", SUM(pl.amount)";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
		$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
		$sql .= " , ".MAIN_DB_PREFIX."prelevement as pf";
		$sql .= " WHERE pf.fk_prelevement_lignes = pl.rowid";
		$sql .= " AND pl.fk_prelevement_bons = p.rowid";
		$sql .= " AND p.rowid = ".((int) $this->id);
		$sql .= " AND p.entity = ".((int) $conf->entity);
		if ($amounts) {
			if ($this->type == 'bank-transfer') {
				$sql .= " GROUP BY fk_facture_fourn";
			} else {
				$sql .= " GROUP BY fk_facture";
			}
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if ($num) {
				$i = 0;
				while ($i < $num) {
					$row = $this->db->fetch_row($resql);
					if (!$amounts) {
						$arr[$i] = $row[0];
					} else {
						$arr[$i] = array(
							$row[0],
							$row[1]
						);
					}
					$i++;
				}
			}
			$this->db->free($resql);
		} else {
			dol_syslog(get_class($this)."::getListInvoices Erreur");
		}

		return $arr;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Returns amount waiting for direct debit payment or credit transfer payment
	 *
	 *	@param	string	$mode		'direct-debit' or 'bank-transfer'
	 *	@return	double	 			<O if KO, Total amount
	 */
	public function SommeAPrelever($mode = 'direct-debit')
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT sum(pd.amount) as nb";
		if ($mode != 'bank-transfer') {
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as f,";
		} else {
			$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f,";
		}
		$sql .= " ".MAIN_DB_PREFIX."prelevement_demande as pd";
		$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
		if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS)) {
			$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
		}
		if ($mode != 'bank-transfer') {
			$sql .= " AND f.rowid = pd.fk_facture";
		} else {
			$sql .= " AND f.rowid = pd.fk_facture_fourn";
		}
		$sql .= " AND f.paye = 0";
		$sql .= " AND pd.traite = 0";
		$sql .= " AND pd.ext_payment_id IS NULL";
		$sql .= " AND f.total_ttc > 0";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			return $obj->nb;
		} else {
			$error = 1;
			dol_syslog(get_class($this)."::SommeAPrelever Erreur -1");
			dol_syslog($this->db->error());

			return -1;
		}
	}

	/**
	 *	Get number of invoices waiting for payment
	 *
	 *	@param	string	$mode		'direct-debit' or 'bank-transfer'
	 *	@return	int					<O if KO, number of invoices if OK
	 */
	public function nbOfInvoiceToPay($mode = 'direct-debit')
	{
		return $this->NbFactureAPrelever($mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Get number of invoices to pay
	 *
	 *	@param	string	$type		'direct-debit' or 'bank-transfer'
	 *	@return	int					<O if KO, number of invoices if OK
	 */
	public function NbFactureAPrelever($type = 'direct-debit')
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT count(f.rowid) as nb";
		if ($type == 'bank-transfer') {
			$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		} else {
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		}
		$sql .= ", ".MAIN_DB_PREFIX."prelevement_demande as pd";
		$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
		if (empty($conf->global->WITHDRAWAL_ALLOW_ANY_INVOICE_STATUS)) {
			$sql .= " AND f.fk_statut = ".Facture::STATUS_VALIDATED;
		}
		if ($type == 'bank-transfer') {
			$sql .= " AND f.rowid = pd.fk_facture_fourn";
		} else {
			$sql .= " AND f.rowid = pd.fk_facture";
		}
		$sql .= " AND pd.traite = 0";
		$sql .= " AND pd.ext_payment_id IS NULL";
		$sql .= " AND f.total_ttc > 0";

		dol_syslog(get_class($this)."::NbFactureAPrelever");
		$resql = $this->db->query($sql);

		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$this->db->free($resql);

			return $obj->nb;
		} else {
			$this->error = get_class($this)."::NbFactureAPrelever Erreur -1 sql=".$this->db->error();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Create a BAN payment order:
	 *  - Select waiting requests from prelevement_demande (or use $did if provided)
	 *  - Check BAN values
	 *  - Then create a direct debit order or a credit transfer order
	 *  - Link the order with the prelevement_demande lines
	 *  TODO delete params banque and agence when not necessary
	 *
	 *	@param 	int		$banque				dolibarr mysoc bank
	 *	@param	int		$agence				dolibarr mysoc bank office (guichet)
	 *	@param	string	$mode				real=do action, simu=test only
	 *  @param	string	$format				FRST, RCUR or ALL
	 *  @param  string  $executiondate		Date to execute the transfer
	 *  @param	int	    $notrigger			Disable triggers
	 *  @param	string	$type				'direct-debit' or 'bank-transfer'
	 *  @param	int		$did				ID of an existing payment request. If $did is defined, we use the existing payment request.
	 *  @param	int		$fk_bank_account	Bank account ID the receipt is generated for. Will use the ID into the setup of module Direct Debit or Credit Transfer if 0.
	 *	@return	int							<0 if KO, No of invoice included into file if OK
	 */
	public function create($banque = 0, $agence = 0, $mode = 'real', $format = 'ALL', $executiondate = '', $notrigger = 0, $type = 'direct-debit', $did = 0, $fk_bank_account = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		dol_syslog(__METHOD__." Bank=".$banque." Office=".$agence." mode=".$mode." format=".$format, LOG_DEBUG);

		require_once DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php";
		require_once DOL_DOCUMENT_ROOT."/societe/class/societe.class.php";

		// Check params
		if ($type != 'bank-transfer') {
			if (empty($format)) {
				$this->error = 'ErrorBadParametersForDirectDebitFileCreate';
				return -1;
			}
		}

		// Clean params
		if (empty($fk_bank_account)) {
			$fk_bank_account = ($type == 'bank-transfer' ? $conf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT : $conf->global->PRELEVEMENT_ID_BANKACCOUNT);
		}

		$error = 0;

		$datetimeprev = dol_now('gmt');
		// Choice the date of the execution direct debit
		if (!empty($executiondate)) {
			$datetimeprev = $executiondate;
		}

		$month = dol_print_date($datetimeprev, "%m", 'gmt');
		$year = dol_print_date($datetimeprev, "%Y", 'gmt');

		$this->invoice_in_error = array();
		$this->thirdparty_in_error = array();

		// Read invoices
		$factures = array();
		$factures_prev = array();
		$factures_result = array();
		$factures_prev_id = array();
		$factures_errors = array();

		if (!$error) {
			dol_syslog(__METHOD__." Read invoices for did=".((int) $did), LOG_DEBUG);

			$sql = "SELECT f.rowid, pd.rowid as pfdrowid, f.fk_soc";
			$sql .= ", pd.code_banque, pd.code_guichet, pd.number, pd.cle_rib";
			$sql .= ", pd.amount";
			$sql .= ", s.nom as name";
			$sql .= ", f.ref, sr.bic, sr.iban_prefix, sr.frstrecur";
			if ($type != 'bank-transfer') {
				$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "prelevement_demande as pd ON f.rowid = pd.fk_facture";
			} else {
				$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "prelevement_demande as pd ON f.rowid = pd.fk_facture_fourn";
			}
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid = f.fk_soc";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_rib as sr ON s.rowid = sr.fk_soc AND sr.default_rib = 1";
			$sql .= " WHERE f.entity IN (".getEntity('invoice').')';
			$sql .= " AND f.fk_statut = 1"; // Invoice validated
			$sql .= " AND f.paye = 0";
			$sql .= " AND pd.traite = 0";
			$sql .= " AND f.total_ttc > 0";
			$sql .= " AND pd.ext_payment_id IS NULL";
			$sql .= " AND sr.type = 'ban' ";
			if ($did > 0) {
				$sql .= " AND pd.rowid = ".((int) $did);
			}

			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;

				while ($i < $num) {
					$row = $this->db->fetch_row($resql);
					$factures[$i] = $row; // All fields
					if ($row[7] == 0) {
						$error++;
						dol_syslog(__METHOD__." Read invoices error Found a null invoice", LOG_ERR);
						$this->invoice_in_error[$row[0]] = "Error for invoice id ".$row[0].", found a null amount";
						break;
					}
					$i++;
				}
				$this->db->free($resql);
				dol_syslog(__METHOD__." Read invoices, ".$i." invoices to withdraw", LOG_DEBUG);
			} else {
				$error++;
				$this->error = $this->db->lasterror();
				dol_syslog(__METHOD__." Read invoices error ".$this->db->lasterror(), LOG_ERR);
				return -1;
			}
		}

		if (!$error) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

			$soc = new Societe($this->db);

			// Check BAN
			$i = 0;
			dol_syslog(__METHOD__." Check BAN", LOG_DEBUG);

			if (count($factures) > 0) {
				foreach ($factures as $key => $fac) {
					/*
					if ($type != 'bank-transfer') {
						$tmpinvoice = new Facture($this->db);
					} else {
						$tmpinvoice = new FactureFournisseur($this->db);
					}
					$resfetch = $tmpinvoice->fetch($fac[0]);
					if ($resfetch >= 0) {		// Field 0 of $fac is rowid of invoice
					*/

					// Check if $fac[8] s.nom is null
					if ($fac[8] != null) {
						//$bac = new CompanyBankAccount($this->db);
						//$bac->fetch(0, $soc->id);

						if ($type != 'bank-transfer') {
							if ($format == 'FRST' && $fac[12] != 'FRST') {
								continue;
							}
							if ($format == 'RCUR' && $fac[12] != 'RCUR') {
								continue;
							}
						}

						$verif = checkSwiftForAccount(null, $fac[10]);
						if ($verif) {
							$verif = checkIbanForAccount(null, $fac[11]);
						}

						if ($verif) {
							$factures_prev[$i] = $fac;
							/* second array necessary for BonPrelevement */
							$factures_prev_id[$i] = $fac[0];
							$i++;
							//dol_syslog(__METHOD__."::RIB is ok", LOG_DEBUG);
						} else {
							if ($type != 'bank-transfer') {
								$invoice_url = "<a href='".DOL_URL_ROOT.'/compta/facture/card.php?facid='.$fac[0]."'>".$fac[9]."</a>";
							} else {
								$invoice_url = "<a href='".DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$fac[0]."'>".$fac[9]."</a>";
							}
							dol_syslog(__METHOD__ . " Check BAN Error on default bank number IBAN/BIC for thirdparty reported by verif() " . $fac->fk_soc . " " . $soc->name, LOG_WARNING);
							$this->invoice_in_error[$fac[0]] = "Error on default bank number IBAN/BIC for invoice " . $invoice_url . " for thirdparty " . $soc->getNomUrl(0);
							$this->thirdparty_in_error[$soc->id] = "Error on default bank number IBAN/BIC for invoice " . $invoice_url . " for thirdparty " . $soc->getNomUrl(0);
						}
					} else {
						dol_syslog(__METHOD__ . " Check BAN Failed to read company", LOG_WARNING);
					}
					/*
					} else {
						dol_syslog(__METHOD__." Check BAN Failed to read invoice", LOG_WARNING);
					}
					*/
				}
			} else {
				dol_syslog(__METHOD__." Check BAN No invoice to process", LOG_WARNING);
			}
		}

		$ok = 0;

		// Withdraw invoices in factures_prev array
		$out = count($factures_prev)." invoices will be included.";
		//print $out."\n";
		dol_syslog($out);

		// Return warning
		/*$i=0;
		 foreach ($this->thirdparty_in_error as $key => $val)
		 {
		 if ($i < 10) setEventMessages($val, null, 'warnings');
		 else setEventMessages('More error were discarded...', null, 'warnings');
		 $i++;
		 }*/

		if (count($factures_prev) > 0) {
			if ($mode == 'real') {
				$ok = 1;
			} else {
				print $langs->trans("ModeWarning"); // "Option for real mode was not set, we stop after this simulation\n";
			}
		}

		if ($ok) {
			/*
			 * We are in real mode.
			 * We create order and build file into disk
			 */
			$this->db->begin();

			$now = dol_now();
			$ref = '';

			/*
			 * Process order generation
			 */
			if (!$error) {
				$ref = substr($year, -2).$month;

				// Get next free nuber for the ref of bon
				$sql = "SELECT substring(ref from char_length(ref) - 1)";	// To extract "YYMMXX" from "TYYMMXX"
				$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons";
				$sql .= " WHERE ref LIKE '_".$this->db->escape($ref)."%'";
				$sql .= " AND entity = ".((int) $conf->entity);
				$sql .= " ORDER BY ref DESC LIMIT 1";

				dol_syslog(get_class($this)." get next free number", LOG_DEBUG);
				$resql = $this->db->query($sql);

				if ($resql) {
					$row = $this->db->fetch_row($resql);

					// Build the new ref
					$ref = "T".$ref.sprintf("%02d", (intval($row[0]) + 1));

					// $conf->abc->dir_output may be:
					// /home/ldestailleur/git/dolibarr_15.0/documents/abc/
					// or
					// /home/ldestailleur/git/dolibarr_15.0/documents/X/abc with X >= 2 with multicompany.
					if ($type != 'bank-transfer') {
						$dir = $conf->prelevement->dir_output.'/receipts';
					} else {
						$dir = $conf->paymentbybanktransfer->dir_output.'/receipts';
					}
					if (!is_dir($dir)) {
						dol_mkdir($dir);
					}

					if (isModEnabled('multicompany')) {
						$labelentity = $conf->entity;
						$this->filename = $dir.'/'.$ref.'-'.$labelentity.'.xml';
					} else {
						$this->filename = $dir.'/'.$ref.'.xml';
					}

					// Create withdraw order in database
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."prelevement_bons (";
					$sql .= "ref, entity, datec, type, fk_bank_account";
					$sql .= ") VALUES (";
					$sql .= "'".$this->db->escape($ref)."'";
					$sql .= ", ".((int) $conf->entity);
					$sql .= ", '".$this->db->idate($now)."'";
					$sql .= ", '".($type == 'bank-transfer' ? 'bank-transfer' : 'debit-order')."'";
					$sql .= ", ".((int) $fk_bank_account);
					$sql .= ")";

					$resql = $this->db->query($sql);
					if ($resql) {
						$prev_id = $this->db->last_insert_id(MAIN_DB_PREFIX."prelevement_bons");
						$this->id = $prev_id;
						$this->ref = $ref;
					} else {
						$error++;
						dol_syslog(__METHOD__." Create withdraw receipt ".$this->db->lasterror(), LOG_ERR);
					}
				} else {
					$error++;
					dol_syslog(__METHOD__." Get last withdraw receipt ".$this->db->lasterror(), LOG_ERR);
				}
			}

			if (!$error) {
				/*
				if ($type != 'bank-transfer') {
					$fact = new Facture($this->db);
				} else {
					$fact = new FactureFournisseur($this->db);
				}
				*/

				// Add lines for the bon
				if (count($factures_prev) > 0) {
					foreach ($factures_prev as $fac) {	// Add a link in database for each invoice
						// Fetch invoice
						/*
						$result = $fact->fetch($fac[0]);
						if ($result < 0) {
							$this->error = 'ERRORBONPRELEVEMENT Failed to load invoice with id '.$fac[0];
							break;
						}
						*/

						/*
						 * Add standing order. This add record into llx_prelevement_lignes and llx_prelevement
						 *
						 * $fac[0] : invoice_id
						 * $fac[1] : ???
						 * $fac[2] : third party id
						 * $fac[3] : banque
						 * $fac[4] : guichet
						 * $fac[5] : number
						 * $fac[6] : cle rib
						 * $fac[7] : amount
						 * $fac[8] : client nom
						 * $fac[9] : Invoice ref
						 * $fac[10] : BIC
						 * $fac[11] : IBAN
						 * $fac[12] : frstrcur
						 */
						$ri = $this->AddFacture($fac[0], $fac[2], $fac[8], $fac[7], $fac[3], $fac[4], $fac[5], $fac[6], $type);
						if ($ri <> 0) {
							$error++;
						}

						// Update invoice requests as done
						$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_demande";
						$sql .= " SET traite = 1";
						$sql .= ", date_traite = '".$this->db->idate($now)."'";
						$sql .= ", fk_prelevement_bons = ".((int) $this->id);
						$sql .= " WHERE rowid = ".((int) $fac[1]);

						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							$this->errors[] = $this->db->lasterror();
							dol_syslog(__METHOD__." Update Error=".$this->db->lasterror(), LOG_ERR);
						}
					}
				}
			}

			if (!$error) {
				/*
				 * Create file of type='direct-debit' for direct debit order or type='bank-transfer' for credit transfer into a XML file
				 */

				dol_syslog(__METHOD__." Init direct debit or credit transfer file for ".count($factures_prev)." invoices", LOG_DEBUG);

				if (count($factures_prev) > 0) {
					$this->date_echeance = $datetimeprev;
					$this->reference_remise = $ref;

					$account = new Account($this->db);
					if ($account->fetch($fk_bank_account) > 0) {
						$this->emetteur_code_banque        = $account->code_banque;
						$this->emetteur_code_guichet       = $account->code_guichet;
						$this->emetteur_numero_compte      = $account->number;
						$this->emetteur_number_key         = $account->cle_rib;
						$this->sepa_xml_pti_in_ctti        = (bool) $account->pti_in_ctti;
						$this->emetteur_iban               = $account->iban;
						$this->emetteur_bic                = $account->bic;

						$this->emetteur_ics = ($type == 'bank-transfer' ? $account->ics_transfer : $account->ics);

						$this->raison_sociale = $account->proprio;
					}

					$this->factures = $factures_prev_id;
					$this->context['factures_prev'] = $factures_prev;

					// Generation of direct debit or credit transfer file $this->filename (May be a SEPA file for european countries)
					// This also set the property $this->total with amount that is included into file
					$result = $this->generate($format, $executiondate, $type);
					if ($result < 0) {
						//var_dump($this->error);
						//var_dump($this->invoice_in_error);
						$error++;
					}
				}
				dol_syslog(__METHOD__." Bank order file has been generated under filename ".$this->filename, LOG_DEBUG);
			}
			//var_dump($this->total);exit;

			/*
			 * Update total defined after generation of file
			 */
			if (!$error) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_bons";
				$sql .= " SET amount = ".price2num($this->total);
				$sql .= " WHERE rowid = ".((int) $this->id);
				$sql .= " AND entity = ".((int) $conf->entity);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					dol_syslog(__METHOD__." Error update total: ".$this->db->error(), LOG_ERR);
				}
			}

			if (!$error && !$notrigger) {
				$triggername = 'DIRECT_DEBIT_ORDER_CREATE';
				if ($type != 'bank-transfer') {
					$triggername = 'CREDIT_TRANSFER_ORDER_CREATE';
				}

				// Call trigger
				$result = $this->call_trigger($triggername, $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return count($factures_prev);
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			return 0;
		}
	}


	/**
	 *  Get object and lines from database
	 *
	 *  @param	User	$user		Object user that delete
	 *  @param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return	int					>0 if OK, <0 if KO
	 */
	public function delete($user = null, $notrigger = 0)
	{
		$this->db->begin();

		$error = 0;
		$resql1 = $resql2 = $resql3 = $resql4 = 0;

		if (!$notrigger) {
			$triggername = 'DIRECT_DEBIT_ORDER_DELETE';
			if ($this->type == 'bank-transfer') {
				$triggername = 'PAYMENTBYBANKTRANFER_DELETE';
			}
			// Call trigger
			$result = $this->call_trigger($triggername, $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement WHERE fk_prelevement_lignes IN (SELECT rowid FROM ".MAIN_DB_PREFIX."prelevement_lignes WHERE fk_prelevement_bons = ".((int) $this->id).")";
			$resql1 = $this->db->query($sql);
			if (!$resql1) {
				dol_print_error($this->db);
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_lignes WHERE fk_prelevement_bons = ".((int) $this->id);
			$resql2 = $this->db->query($sql);
			if (!$resql2) {
				dol_print_error($this->db);
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."prelevement_bons WHERE rowid = ".((int) $this->id);
			$resql3 = $this->db->query($sql);
			if (!$resql3) {
				dol_print_error($this->db);
			}
		}

		if (!$error) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."prelevement_demande SET fk_prelevement_bons = NULL, traite = 0 WHERE fk_prelevement_bons = ".((int) $this->id);
			$resql4 = $this->db->query($sql);
			if (!$resql4) {
				dol_print_error($this->db);
			}
		}

		if ($resql1 && $resql2 && $resql3 && $resql4 && !$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Returns clickable name (with picto)
	 *
	 *  @param  int     $withpicto                  Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *  @param  string  $option                     On what the link point to ('nolink', ...)
	 *  @param  int     $notooltip                  1=Disable tooltip
	 *  @param  string  $morecss                    Add more css on link
	 *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								URL of target
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$labeltoshow = 'PaymentByDirectDebit';
		if (!empty($this->type) && $this->type == 'bank-transfer') {
			$labeltoshow = 'PaymentByBankTransfer';
		}

		$label = img_picto('', $this->picto).' <u>'.$langs->trans($labeltoshow).'</u> '.$this->getLibStatut(5);
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
		if (isset($this->amount)) {
			$label .= '<br><b>'.$langs->trans("Amount").":</b> ".price($this->amount);
		}
		if (isset($this->date_trans)) {
			$label .= '<br><b>'.$langs->trans("TransData").":</b> ".dol_print_date($this->date_trans, 'dayhour', 'tzuserrel');
		}
		/*if (isset($this->date_credit)) {
			$label .= '<br><b>'.$langs->trans("TransData").":</b> ".dol_print_date($this->date_credit, 'dayhour', 'tzuserrel');
		}*/

		$url = DOL_URL_ROOT.'/compta/prelevement/card.php?id='.$this->id;
		if (!empty($this->type) && $this->type == 'bank-transfer') {
			$url = DOL_URL_ROOT.'/compta/prelevement/card.php?id='.$this->id;
		}

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
			$result .= $this->ref;
		}
		$result .= $linkend;

		global $action, $hookmanager;
		$hookmanager->initHooks(array('banktransferdao'));
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
	 *	Delete a notification def by id
	 *
	 *	@param	int		$rowid		id of notification
	 *	@return	int					0 if OK, <0 if KO
	 */
	public function deleteNotificationById($rowid)
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
		$sql .= " WHERE rowid = ".((int) $rowid);

		if ($this->db->query($sql)) {
			return 0;
		} else {
			return -1;
		}
	}

	/**
	 *	Delete a notification
	 *
	 *	@param	int|User	$user		notification user
	 *	@param	string		$action		notification action
	 *	@return	int						>0 if OK, <0 if KO
	 */
	public function deleteNotification($user, $action)
	{
		if (is_object($user)) {
			$userid = $user->id;
		} else {	// If user is an id
			$userid = $user;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."notify_def";
		$sql .= " WHERE fk_user=".((int) $userid)." AND fk_action='".$this->db->escape($action)."'";

		if ($this->db->query($sql)) {
			return 0;
		} else {
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add a notification
	 *
	 *	@param	DoliDB		$db			database handler
	 *	@param	int|User	$user		notification user
	 *	@param	string		$action		notification action
	 *	@return	int						0 if OK, <0 if KO
	 */
	public function addNotification($db, $user, $action)
	{
		// phpcs:enable
		$result = 0;

		if (is_object($user)) {
			$userid = $user->id;
		} else {	// If user is an id
			$userid = $user;
		}

		if ($this->deleteNotification($user, $action) == 0) {
			$now = dol_now();

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify_def (datec,fk_user, fk_soc, fk_contact, fk_action)";
			$sql .= " VALUES ('".$this->db->idate($now)."', ".((int) $userid).", 'NULL', 'NULL', '".$this->db->escape($action)."')";

			dol_syslog("adnotiff: ".$sql);
			if ($this->db->query($sql)) {
				$result = 0;
			} else {
				$result = -1;
				dol_syslog(get_class($this)."::addNotification Error $result");
			}
		}

		return $result;
	}


	/**
	 * Generate a direct debit or credit transfer file.
	 * Generation Formats:
	 * - Europe: SEPA (France: CFONB no more supported, Spain: AEB19 if external module EsAEB is enabled)
	 * - Others countries: Warning message
	 * File is generated with name this->filename
	 *
	 * @param	string	$format				FRST, RCUR or ALL
	 * @param 	int 	$executiondate		Timestamp date to execute transfer
	 * @param	string	$type				'direct-debit' or 'bank-transfer'
	 * @param	int		$fk_bank_account	Bank account ID the receipt is generated for. Will use the ID into the setup of module Direct Debit or Credit Transfer if 0.
	 * @return	int							>=0 if OK, <0 if KO
	 */
	public function generate($format = 'ALL', $executiondate = 0, $type = 'direct-debit', $fk_bank_account = 0)
	{
		global $conf, $langs, $mysoc;

		//TODO: Optimize code to read lines in a single function

		// Clean params
		if (empty($fk_bank_account)) {
			$fk_bank_account = ($type == 'bank-transfer' ? $conf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT : $conf->global->PRELEVEMENT_ID_BANKACCOUNT);
		}

		$result = 0;

		dol_syslog(get_class($this)."::generate build file=".$this->filename." type=".$type);

		$this->file = fopen($this->filename, "w");
		if (empty($this->file)) {
			$this->error = $langs->trans('ErrorFailedToOpenFile', $this->filename);
			return -1;
		}

		$found = 0;
		$this->total = 0;

		// Build file for European countries
		if ($mysoc->isInEEC()) {
			$found++;

			if ($type != 'bank-transfer') {
				/**
				 * SECTION CREATION FICHIER SEPA - DIRECT DEBIT
				 */
				// SEPA Initialisation
				$CrLf = "\n";

				$now = dol_now();

				$dateTime_ECMA = dol_print_date($now, '%Y-%m-%dT%H:%M:%S');

				$date_actu = $now;
				if (!empty($executiondate)) {
					$date_actu = $executiondate;
				}

				$dateTime_YMD = dol_print_date($date_actu, '%Y%m%d');
				$dateTime_YMDHMS = dol_print_date($date_actu, '%Y%m%d%H%M%S');
				$fileDebiteurSection = '';
				$fileEmetteurSection = '';
				$i = 0;

				/*
				 * Section Debitor (sepa Debiteurs bloc lines)
				 */

				$sql = "SELECT soc.rowid as socid, soc.code_client as code, soc.address, soc.zip, soc.town, c.code as country_code,";
				$sql .= " pl.client_nom as nom, pl.code_banque as cb, pl.code_guichet as cg, pl.number as cc, pl.amount as somme,";
				$sql .= " f.ref as reffac, pf.fk_facture as idfac,";
				$sql .= " rib.rowid, rib.datec, rib.iban_prefix as iban, rib.bic as bic, rib.rowid as drum, rib.rum, rib.date_rum";
				$sql .= " FROM";
				$sql .= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
				$sql .= " ".MAIN_DB_PREFIX."facture as f,";
				$sql .= " ".MAIN_DB_PREFIX."prelevement as pf,";
				$sql .= " ".MAIN_DB_PREFIX."societe as soc,";
				$sql .= " ".MAIN_DB_PREFIX."c_country as c,";
				$sql .= " ".MAIN_DB_PREFIX."societe_rib as rib";
				$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $this->id);
				$sql .= " AND pl.rowid = pf.fk_prelevement_lignes";
				$sql .= " AND pf.fk_facture = f.rowid";
				$sql .= " AND f.fk_soc = soc.rowid";
				$sql .= " AND soc.fk_pays = c.rowid";
				$sql .= " AND rib.fk_soc = f.fk_soc";
				$sql .= " AND rib.default_rib = 1";
				$sql .= " AND rib.type = 'ban'";

				// Define $fileDebiteurSection. One section DrctDbtTxInf per invoice.
				$resql = $this->db->query($sql);
				if ($resql) {
					$cachearraytotestduplicate = array();

					$num = $this->db->num_rows($resql);
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if (!empty($cachearraytotestduplicate[$obj->idfac])) {
							$this->error = $langs->trans('ErrorCompanyHasDuplicateDefaultBAN', $obj->socid);
							$this->invoice_in_error[$obj->idfac] = $this->error;
							$result = -2;
							break;
						}
						$cachearraytotestduplicate[$obj->idfac] = $obj->rowid;

						$daterum = (!empty($obj->date_rum)) ? $this->db->jdate($obj->date_rum) : $this->db->jdate($obj->datec);

						$fileDebiteurSection .= $this->EnregDestinataireSEPA($obj->code, $obj->nom, $obj->address, $obj->zip, $obj->town, $obj->country_code, $obj->cb, $obj->cg, $obj->cc, $obj->somme, $obj->reffac, $obj->idfac, $obj->iban, $obj->bic, $daterum, $obj->drum, $obj->rum, $type);

						$this->total = $this->total + $obj->somme;
						$i++;
					}
					$nbtotalDrctDbtTxInf = $i;
				} else {
					$this->error = $this->db->lasterror();
					fputs($this->file, 'ERROR DEBITOR '.$sql.$CrLf); // DEBITOR = Customers
					$result = -2;
				}

				// Define $fileEmetteurSection. Start of bloc PmtInf. Will contains all $nbtotalDrctDbtTxInf
				if ($result != -2) {
					$fileEmetteurSection .= $this->EnregEmetteurSEPA($conf, $date_actu, $nbtotalDrctDbtTxInf, $this->total, $CrLf, $format, $type, $fk_bank_account);
				}

				/**
				 * SECTION CREATION SEPA FILE - ISO200022
				 */
				// SEPA File Header
				fputs($this->file, '<'.'?xml version="1.0" encoding="UTF-8" standalone="yes"?'.'>'.$CrLf);
				fputs($this->file, '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.008.001.02" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$CrLf);
				fputs($this->file, '	<CstmrDrctDbtInitn>'.$CrLf);
				// SEPA Group header
				fputs($this->file, '		<GrpHdr>'.$CrLf);
				fputs($this->file, '			<MsgId>'.('DD/'.$dateTime_YMD.'/REF'.$this->id).'</MsgId>'.$CrLf);
				fputs($this->file, '			<CreDtTm>'.$dateTime_ECMA.'</CreDtTm>'.$CrLf);
				fputs($this->file, '			<NbOfTxs>'.$i.'</NbOfTxs>'.$CrLf);
				fputs($this->file, '			<CtrlSum>'.$this->total.'</CtrlSum>'.$CrLf);
				fputs($this->file, '			<InitgPty>'.$CrLf);
				fputs($this->file, '				<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($this->raison_sociale), ' '))).'</Nm>'.$CrLf);
				fputs($this->file, '				<Id>'.$CrLf);
				fputs($this->file, '				    <PrvtId>'.$CrLf);
				fputs($this->file, '					<Othr>'.$CrLf);
				fputs($this->file, '						<Id>'.$this->emetteur_ics.'</Id>'.$CrLf);
				fputs($this->file, '					</Othr>'.$CrLf);
				fputs($this->file, '				    </PrvtId>'.$CrLf);
				fputs($this->file, '				</Id>'.$CrLf);
				fputs($this->file, '			</InitgPty>'.$CrLf);
				fputs($this->file, '		</GrpHdr>'.$CrLf);
				// SEPA File Emetteur
				if ($result != -2) {
					fputs($this-> file, $fileEmetteurSection);
				}
				// SEPA File Debiteurs
				if ($result != -2) {
					fputs($this-> file, $fileDebiteurSection);
				}
				// SEPA FILE FOOTER
				fputs($this->file, '		</PmtInf>'.$CrLf);
				fputs($this->file, '	</CstmrDrctDbtInitn>'.$CrLf);
				fputs($this->file, '</Document>'.$CrLf);
			} else {
				/**
				 * SECTION CREATION FICHIER SEPA - CREDIT TRANSFER
				 */
				// SEPA Initialisation
				$CrLf = "\n";

				$now = dol_now();

				$dateTime_ECMA = dol_print_date($now, '%Y-%m-%dT%H:%M:%S');

				$date_actu = $now;
				if (!empty($executiondate)) {
					$date_actu = $executiondate;
				}

				$dateTime_YMD = dol_print_date($date_actu, '%Y%m%d');
				$dateTime_YMDHMS = dol_print_date($date_actu, '%Y%m%d%H%M%S');
				$fileCrediteurSection = '';
				$fileEmetteurSection = '';
				$i = 0;

				/*
				 * Section Creditor (sepa Crediteurs bloc lines)
				 */

				$sql = "SELECT soc.rowid as socid, soc.code_client as code, soc.address, soc.zip, soc.town, c.code as country_code,";
				$sql .= " pl.client_nom as nom, pl.code_banque as cb, pl.code_guichet as cg, pl.number as cc, pl.amount as somme,";
				$sql .= " f.ref as reffac, pf.fk_facture_fourn as idfac, f.ref_supplier as fac_ref_supplier,";
				$sql .= " rib.rowid, rib.datec, rib.iban_prefix as iban, rib.bic as bic, rib.rowid as drum, rib.rum, rib.date_rum";
				$sql .= " FROM";
				$sql .= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
				$sql .= " ".MAIN_DB_PREFIX."facture_fourn as f,";
				$sql .= " ".MAIN_DB_PREFIX."prelevement as pf,";
				$sql .= " ".MAIN_DB_PREFIX."societe as soc,";
				$sql .= " ".MAIN_DB_PREFIX."c_country as c,";
				$sql .= " ".MAIN_DB_PREFIX."societe_rib as rib";
				$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $this->id);
				$sql .= " AND pl.rowid = pf.fk_prelevement_lignes";
				$sql .= " AND pf.fk_facture_fourn = f.rowid";
				$sql .= " AND f.fk_soc = soc.rowid";
				$sql .= " AND soc.fk_pays = c.rowid";
				$sql .= " AND rib.fk_soc = f.fk_soc";
				$sql .= " AND rib.default_rib = 1";
				$sql .= " AND rib.type = 'ban'";

				// Define $fileCrediteurSection. One section DrctDbtTxInf per invoice.
				$resql = $this->db->query($sql);
				if ($resql) {
					$cachearraytotestduplicate = array();

					$num = $this->db->num_rows($resql);
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if (!empty($cachearraytotestduplicate[$obj->idfac])) {
							$this->error = $langs->trans('ErrorCompanyHasDuplicateDefaultBAN', $obj->socid);
							$this->invoice_in_error[$obj->idfac] = $this->error;
							$result = -2;
							break;
						}
						$cachearraytotestduplicate[$obj->idfac] = $obj->rowid;

						$daterum = (!empty($obj->date_rum)) ? $this->db->jdate($obj->date_rum) : $this->db->jdate($obj->datec);

						$fileCrediteurSection .= $this->EnregDestinataireSEPA($obj->code, $obj->nom, $obj->address, $obj->zip, $obj->town, $obj->country_code, $obj->cb, $obj->cg, $obj->cc, $obj->somme, $obj->reffac, $obj->idfac, $obj->iban, $obj->bic, $daterum, $obj->drum, $obj->rum, $type, $obj->fac_ref_supplier);

						$this->total = $this->total + $obj->somme;
						$i++;
					}
					$nbtotalDrctDbtTxInf = $i;
				} else {
					$this->error = $this->db->lasterror();
					fputs($this->file, 'ERROR CREDITOR '.$sql.$CrLf); // CREDITORS = Suppliers
					$result = -2;
				}

				// Define $fileEmetteurSection. Start of bloc PmtInf. Will contains all $nbtotalDrctDbtTxInf
				if ($result != -2) {
					$fileEmetteurSection .= $this->EnregEmetteurSEPA($conf, $date_actu, $nbtotalDrctDbtTxInf, $this->total, $CrLf, $format, $type);
				}

				/**
				 * SECTION CREATION SEPA FILE - CREDIT TRANSFER - ISO200022
				 */
				// SEPA File Header
				fputs($this->file, '<'.'?xml version="1.0" encoding="UTF-8" standalone="yes"?'.'>'.$CrLf);
				fputs($this->file, '<Document xmlns="urn:iso:std:iso:20022:tech:xsd:pain.001.001.03" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$CrLf);
				fputs($this->file, '	<CstmrCdtTrfInitn>'.$CrLf);
				// SEPA Group header
				fputs($this->file, '		<GrpHdr>'.$CrLf);
				fputs($this->file, '			<MsgId>'.('TRF/'.$dateTime_YMD.'/REF'.$this->id).'</MsgId>'.$CrLf);
				fputs($this->file, '			<CreDtTm>'.$dateTime_ECMA.'</CreDtTm>'.$CrLf);
				fputs($this->file, '			<NbOfTxs>'.$i.'</NbOfTxs>'.$CrLf);
				fputs($this->file, '			<CtrlSum>'.$this->total.'</CtrlSum>'.$CrLf);
				fputs($this->file, '			<InitgPty>'.$CrLf);
				fputs($this->file, '				<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($this->raison_sociale), ' '))).'</Nm>'.$CrLf);
				fputs($this->file, '				<Id>'.$CrLf);
				fputs($this->file, '				    <PrvtId>'.$CrLf);
				fputs($this->file, '					<Othr>'.$CrLf);
				fputs($this->file, '						<Id>'.$this->emetteur_ics.'</Id>'.$CrLf);
				fputs($this->file, '					</Othr>'.$CrLf);
				fputs($this->file, '				    </PrvtId>'.$CrLf);
				fputs($this->file, '				</Id>'.$CrLf);
				fputs($this->file, '			</InitgPty>'.$CrLf);
				fputs($this->file, '		</GrpHdr>'.$CrLf);
				// SEPA File Emetteur (mycompany)
				if ($result != -2) {
					fputs($this-> file, $fileEmetteurSection);
				}
				// SEPA File Creditors
				if ($result != -2) {
					fputs($this-> file, $fileCrediteurSection);
				}
				// SEPA FILE FOOTER
				fputs($this->file, '		</PmtInf>'.$CrLf);
				fputs($this->file, '	</CstmrCdtTrfInitn>'.$CrLf);
				fputs($this->file, '</Document>'.$CrLf);
			}
		}

		// Build file for Other Countries with unknow format
		if (!$found) {
			if ($type != 'bank-transfer') {
				$sql = "SELECT pl.amount";
				$sql .= " FROM";
				$sql .= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
				$sql .= " ".MAIN_DB_PREFIX."facture as f,";
				$sql .= " ".MAIN_DB_PREFIX."prelevement as pf";
				$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $this->id);
				$sql .= " AND pl.rowid = pf.fk_prelevement_lignes";
				$sql .= " AND pf.fk_facture = f.rowid";

				// Lines
				$i = 0;
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);

					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						$this->total = $this->total + $obj->amount;

						// TODO Write record into file
						$i++;
					}
				} else {
					$result = -2;
				}
			} else {
				$sql = "SELECT pl.amount";
				$sql .= " FROM";
				$sql .= " ".MAIN_DB_PREFIX."prelevement_lignes as pl,";
				$sql .= " ".MAIN_DB_PREFIX."facture_fourn as f,";
				$sql .= " ".MAIN_DB_PREFIX."prelevement as pf";
				$sql .= " WHERE pl.fk_prelevement_bons = ".((int) $this->id);
				$sql .= " AND pl.rowid = pf.fk_prelevement_lignes";
				$sql .= " AND pf.fk_facture_fourn = f.rowid";

				// Lines
				$i = 0;
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);

					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						$this->total = $this->total + $obj->amount;

						// TODO Write record into file
						$i++;
					}
				} else {
					$result = -2;
				}
			}

			$langs->load('withdrawals');

			// TODO Add here code to generate a generic file
			fputs($this->file, $langs->transnoentitiesnoconv('WithdrawalFileNotCapable', $mysoc->country_code));
		}

		fclose($this->file);
		dolChmod($this->filename);

		return $result;
	}


	/**
	 * Generate dynamically a RUM number for a customer bank account
	 *
	 * @param	string		$row_code_client	Customer code (soc.code_client)
	 * @param	int			$row_datec			Creation date of bank account (rib.datec)
	 * @param	string		$row_drum			Id of customer bank account (rib.rowid)
	 * @return 	string		RUM number
	 */
	public static function buildRumNumber($row_code_client, $row_datec, $row_drum)
	{
		global $langs;

		$pre = substr(dol_string_nospecial(dol_string_unaccent($langs->transnoentitiesnoconv('RUM'))), 0, 3); // Must always be on 3 char ('RUM' or 'UMR'. This is a protection against bad translation)

		// 3 char + '-' + 12 + '-' + id + '-' + code 		Must be lower than 32.
		return $pre.'-'.dol_print_date($row_datec, 'dayhourlogsmall').'-'.dol_trunc($row_drum.($row_code_client ? '-'.$row_code_client : ''), 13, 'right', 'UTF-8', 1);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write recipient of request (customer)
	 *
	 *	@param	int		$rowid			id of line
	 *	@param	string	$client_nom		name of customer
	 *	@param	string	$rib_banque		code of bank
	 *	@param	string	$rib_guichet 	code of bank office
	 *	@param	string	$rib_number		bank account
	 *	@param	float	$amount			amount
	 *	@param	string	$ref		ref of invoice
	 *	@param	int		$facid			id of invoice
	 *  @param	string	$rib_dom		rib domiciliation
	 *  @param	string	$type			'direct-debit' or 'bank-transfer'
	 *	@return	void
	 *  @see EnregDestinataireSEPA()
	 */
	public function EnregDestinataire($rowid, $client_nom, $rib_banque, $rib_guichet, $rib_number, $amount, $ref, $facid, $rib_dom = '', $type = 'direct-debit')
	{
		// phpcs:enable
		fputs($this->file, "06");
		fputs($this->file, "08"); // Prelevement ordinaire

		fputs($this->file, "        "); // Zone Reservee B2

		fputs($this->file, $this->emetteur_ics); // ICS

		// Date d'echeance C1

		fputs($this->file, "       ");
		fputs($this->file, dol_print_date($this->date_echeance, "%d%m", 'gmt'));
		fputs($this->file, substr(dol_print_date($this->date_echeance, "%y", 'gmt'), 1));

		// Raison Sociale Destinataire C2

		fputs($this->file, substr(strtoupper($client_nom)."                         ", 0, 24));

		// Domiciliation facultative D1
		$domiciliation = strtr($rib_dom, array(" " => "-", CHR(13) => " ", CHR(10) => ""));
		fputs($this->file, substr($domiciliation."                         ", 0, 24));

		// Zone Reservee D2

		fputs($this->file, substr("                             ", 0, 8));

		// Code Guichet  D3

		fputs($this->file, $rib_guichet);

		// Numero de compte D4

		fputs($this->file, substr("000000000000000".$rib_number, -11));

		// Zone E Montant

		$montant = (round($amount, 2) * 100);

		fputs($this->file, substr("000000000000000".$montant, -16));

		// Label F

		fputs($this->file, substr("*_".$ref."_RDVnet".$rowid."                               ", 0, 31));

		// Code etablissement G1

		fputs($this->file, $rib_banque);

		// Zone Reservee G2

		fputs($this->file, substr("                                        ", 0, 5));

		fputs($this->file, "\n");
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write recipient (thirdparty concerned by request)
	 *
	 *	@param	string		$row_code_client	soc.code_client as code,
	 *	@param	string		$row_nom			pl.client_nom AS name,
	 *	@param	string		$row_address		soc.address AS adr,
	 *	@param	string		$row_zip			soc.zip
	 *  @param	string		$row_town			soc.town
	 *	@param	string		$row_country_code	c.code AS country,
	 *	@param	string		$row_cb				pl.code_banque AS cb,		Not used for SEPA
	 *	@param	string		$row_cg				pl.code_guichet AS cg,		Not used for SEPA
	 *	@param	string		$row_cc				pl.number AS cc,			Not used for SEPA
	 *	@param	string		$row_somme			pl.amount AS somme,
	 *	@param	string		$row_ref			Invoice ref (f.ref)
	 *	@param	string		$row_idfac			pf.fk_facture AS idfac,
	 *	@param	string		$row_iban			rib.iban_prefix AS iban,
	 *	@param	string		$row_bic			rib.bic AS bic,
	 *	@param	string		$row_datec			rib.datec,
	 *	@param	string		$row_drum			rib.rowid used to generate rum
	 * 	@param	string		$row_rum			rib.rum Rum defined on company bank account
	 *  @param	string		$type				'direct-debit' or 'bank-transfer'
	 *  @param  string      $row_comment		A free text string for the Unstructured data field
	 *	@return	string							Return string with SEPA part DrctDbtTxInf
	 *  @see EnregDestinataire()
	 */
	public function EnregDestinataireSEPA($row_code_client, $row_nom, $row_address, $row_zip, $row_town, $row_country_code, $row_cb, $row_cg, $row_cc, $row_somme, $row_ref, $row_idfac, $row_iban, $row_bic, $row_datec, $row_drum, $row_rum, $type = 'direct-debit', $row_comment = '')
	{
		// phpcs:enable
		global $conf;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$CrLf = "\n";
		$Rowing = sprintf("%010d", $row_idfac);

		// Define value for RUM
		// Example:  RUM-CustomerCode-CustomerBankAccountId-01424448606	(note: Date is the timestamp of the date of creation of CustomerBankAccountId)
		$Rum = (empty($row_rum) ? $this->buildRumNumber($row_code_client, $row_datec, $row_drum) : $row_rum);

		// Define date of RUM signature
		$DtOfSgntr = dol_print_date($row_datec, '%Y-%m-%d');

		if ($type != 'bank-transfer') {
			// SEPA Paiement Information of buyer for Direct Debit
			$XML_DEBITOR = '';
			$XML_DEBITOR .= '			<DrctDbtTxInf>'.$CrLf;
			$XML_DEBITOR .= '				<PmtId>'.$CrLf;
			// Add EndToEndId. Must be a unique ID for each payment (for example by including bank, buyer or seller, date, checksum)
			$XML_DEBITOR .= '					<EndToEndId>'.(($conf->global->PRELEVEMENT_END_TO_END != "") ? $conf->global->PRELEVEMENT_END_TO_END : ('DD-'.dol_trunc($row_idfac.'-'.$row_ref, 20, 'right', 'UTF-8', 1)).'-'.$Rowing).'</EndToEndId>'.$CrLf; // ISO20022 states that EndToEndId has a MaxLength of 35 characters
			$XML_DEBITOR .= '				</PmtId>'.$CrLf;
			$XML_DEBITOR .= '				<InstdAmt Ccy="EUR">'.round($row_somme, 2).'</InstdAmt>'.$CrLf;
			$XML_DEBITOR .= '				<DrctDbtTx>'.$CrLf;
			$XML_DEBITOR .= '					<MndtRltdInf>'.$CrLf;
			$XML_DEBITOR .= '						<MndtId>'.$Rum.'</MndtId>'.$CrLf;
			$XML_DEBITOR .= '						<DtOfSgntr>'.$DtOfSgntr.'</DtOfSgntr>'.$CrLf;
			$XML_DEBITOR .= '						<AmdmntInd>false</AmdmntInd>'.$CrLf;
			$XML_DEBITOR .= '					</MndtRltdInf>'.$CrLf;
			$XML_DEBITOR .= '				</DrctDbtTx>'.$CrLf;
			$XML_DEBITOR .= '				<DbtrAgt>'.$CrLf;
			$XML_DEBITOR .= '					<FinInstnId>'.$CrLf;
			$XML_DEBITOR .= '						<BIC>'.$row_bic.'</BIC>'.$CrLf;
			$XML_DEBITOR .= '					</FinInstnId>'.$CrLf;
			$XML_DEBITOR .= '				</DbtrAgt>'.$CrLf;
			$XML_DEBITOR .= '				<Dbtr>'.$CrLf;
			$XML_DEBITOR .= '					<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($row_nom), ' '))).'</Nm>'.$CrLf;
			$XML_DEBITOR .= '					<PstlAdr>'.$CrLf;
			$XML_DEBITOR .= '						<Ctry>'.$row_country_code.'</Ctry>'.$CrLf;
			$addressline1 = strtr($row_address, array(CHR(13) => ", ", CHR(10) => ""));
			$addressline2 = strtr($row_zip.(($row_zip && $row_town) ? ' ' : (string) $row_town), array(CHR(13) => ", ", CHR(10) => ""));
			if (trim($addressline1)) {
				$XML_DEBITOR .= '						<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline1), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
			}
			if (trim($addressline2)) {
				$XML_DEBITOR .= '						<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline2), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
			}
			$XML_DEBITOR .= '					</PstlAdr>'.$CrLf;
			$XML_DEBITOR .= '				</Dbtr>'.$CrLf;
			$XML_DEBITOR .= '				<DbtrAcct>'.$CrLf;
			$XML_DEBITOR .= '					<Id>'.$CrLf;
			$XML_DEBITOR .= '						<IBAN>'.preg_replace('/\s/', '', $row_iban).'</IBAN>'.$CrLf;
			$XML_DEBITOR .= '					</Id>'.$CrLf;
			$XML_DEBITOR .= '				</DbtrAcct>'.$CrLf;
			$XML_DEBITOR .= '				<RmtInf>'.$CrLf;
			// A string with some information on payment - 140 max
			$XML_DEBITOR .= '					<Ustrd>'.getDolGlobalString('PRELEVEMENT_USTRD', dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($row_ref.($row_comment ? ' - '.$row_comment : '')), '', '', '', 1), 135, 'right', 'UTF-8', 1))).'</Ustrd>'.$CrLf; // Free unstuctured data - 140 max
			$XML_DEBITOR .= '				</RmtInf>'.$CrLf;
			$XML_DEBITOR .= '			</DrctDbtTxInf>'.$CrLf;
			return $XML_DEBITOR;
		} else {
			// SEPA Paiement Information of seller for Credit Transfer
			$XML_CREDITOR = '';
			$XML_CREDITOR .= '			<CdtTrfTxInf>'.$CrLf;
			$XML_CREDITOR .= '				<PmtId>'.$CrLf;
			// Add EndToEndId. Must be a unique ID for each payment (for example by including bank, buyer or seller, date, checksum)
			$XML_CREDITOR .= '					<EndToEndId>'.(($conf->global->PRELEVEMENT_END_TO_END != "") ? $conf->global->PRELEVEMENT_END_TO_END : ('CT-'.dol_trunc($row_idfac.'-'.$row_ref, 20, 'right', 'UTF-8', 1)).'-'.$Rowing).'</EndToEndId>'.$CrLf; // ISO20022 states that EndToEndId has a MaxLength of 35 characters
			$XML_CREDITOR .= '				</PmtId>'.$CrLf;
			if (!empty($this->sepa_xml_pti_in_ctti)) {
				$XML_CREDITOR .= '				<PmtTpInf>' . $CrLf;

				// Can be 'NORM' for normal or 'HIGH' for high priority level
				if (!empty($conf->global->PAYMENTBYBANKTRANSFER_FORCE_HIGH_PRIORITY)) {
					$instrprty = 'HIGH';
				} else {
					$instrprty = 'NORM';
				}
				$XML_CREDITOR .= '					<InstrPrty>'.$instrprty.'</InstrPrty>' . $CrLf;
				$XML_CREDITOR .= '					<SvcLvl>' . $CrLf;
				$XML_CREDITOR .= '						<Cd>SEPA</Cd>' . $CrLf;
				$XML_CREDITOR .= '					</SvcLvl>' . $CrLf;
				$XML_CREDITOR .= '					<CtgyPurp>' . $CrLf;
				$XML_CREDITOR .= '						<Cd>CORE</Cd>' . $CrLf;
				$XML_CREDITOR .= '					</CtgyPurp>' . $CrLf;
				$XML_CREDITOR .= '				</PmtTpInf>' . $CrLf;
			}
			$XML_CREDITOR .= '				<Amt>'.$CrLf;
			$XML_CREDITOR .= '					<InstdAmt Ccy="EUR">'.round($row_somme, 2).'</InstdAmt>'.$CrLf;
			$XML_CREDITOR .= '				</Amt>'.$CrLf;
			/*
			 $XML_CREDITOR .= '				<DrctDbtTx>'.$CrLf;
			 $XML_CREDITOR .= '					<MndtRltdInf>'.$CrLf;
			 $XML_CREDITOR .= '						<MndtId>'.$Rum.'</MndtId>'.$CrLf;
			 $XML_CREDITOR .= '						<DtOfSgntr>'.$DtOfSgntr.'</DtOfSgntr>'.$CrLf;
			 $XML_CREDITOR .= '						<AmdmntInd>false</AmdmntInd>'.$CrLf;
			 $XML_CREDITOR .= '					</MndtRltdInf>'.$CrLf;
			 $XML_CREDITOR .= '				</DrctDbtTx>'.$CrLf;
			 */
			//$XML_CREDITOR .= '				<ChrgBr>SLEV</ChrgBr>'.$CrLf;
			$XML_CREDITOR .= '				<CdtrAgt>'.$CrLf;
			$XML_CREDITOR .= '					<FinInstnId>'.$CrLf;
			$XML_CREDITOR .= '						<BIC>'.$row_bic.'</BIC>'.$CrLf;
			$XML_CREDITOR .= '					</FinInstnId>'.$CrLf;
			$XML_CREDITOR .= '				</CdtrAgt>'.$CrLf;
			$XML_CREDITOR .= '				<Cdtr>'.$CrLf;
			$XML_CREDITOR .= '					<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($row_nom), ' '))).'</Nm>'.$CrLf;
			$XML_CREDITOR .= '					<PstlAdr>'.$CrLf;
			$XML_CREDITOR .= '						<Ctry>'.$row_country_code.'</Ctry>'.$CrLf;
			$addressline1 = strtr($row_address, array(CHR(13) => ", ", CHR(10) => ""));
			$addressline2 = strtr($row_zip.(($row_zip && $row_town) ? ' ' : (string) $row_town), array(CHR(13) => ", ", CHR(10) => ""));
			if (trim($addressline1)) {
				$XML_CREDITOR .= '						<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline1), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
			}
			if (trim($addressline2)) {
				$XML_CREDITOR .= '						<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline2), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
			}
			$XML_CREDITOR .= '					</PstlAdr>'.$CrLf;
			$XML_CREDITOR .= '				</Cdtr>'.$CrLf;
			$XML_CREDITOR .= '				<CdtrAcct>'.$CrLf;
			$XML_CREDITOR .= '					<Id>'.$CrLf;
			$XML_CREDITOR .= '						<IBAN>'.preg_replace('/\s/', '', $row_iban).'</IBAN>'.$CrLf;
			$XML_CREDITOR .= '					</Id>'.$CrLf;
			$XML_CREDITOR .= '				</CdtrAcct>'.$CrLf;
			$XML_CREDITOR .= '				<RmtInf>'.$CrLf;
			// A string with some information on payment - 140 max
			$XML_CREDITOR .= '					<Ustrd>'.getDolGlobalString('CREDITTRANSFER_USTRD', dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($row_ref.($row_comment ? ' - '.$row_comment : '')), '', '', '', 1), 135, 'right', 'UTF-8', 1))).'</Ustrd>'.$CrLf; // Free unstructured data - 140 max
			$XML_CREDITOR .= '				</RmtInf>'.$CrLf;
			$XML_CREDITOR .= '			</CdtTrfTxInf>'.$CrLf;
			return $XML_CREDITOR;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write sender of request (me).
	 *
	 *  @param	string		$type				'direct-debit' or 'bank-transfer'
	 *	@return	void
	 *  @see EnregEmetteurSEPA()
	 */
	public function EnregEmetteur($type = 'direct-debit')
	{
		// phpcs:enable
		fputs($this->file, "03");
		fputs($this->file, "08"); // Prelevement ordinaire

		fputs($this->file, "        "); // Zone Reservee B2

		fputs($this->file, $this->emetteur_ics); // ICS

		// Date d'echeance C1

		fputs($this->file, "       ");
		fputs($this->file, dol_print_date($this->date_echeance, "%d%m", 'gmt'));
		fputs($this->file, substr(dol_print_date($this->date_echeance, "%y", 'gmt'), 1));

		// Raison Sociale C2

		fputs($this->file, substr($this->raison_sociale."                           ", 0, 24));

		// Reference de la remise creancier D1 sur 7 caracteres

		fputs($this->file, substr($this->reference_remise."                           ", 0, 7));

		// Zone Reservee D1-2

		fputs($this->file, substr("                                    ", 0, 17));

		// Zone Reservee D2

		fputs($this->file, substr("                             ", 0, 2));
		fputs($this->file, "E");
		fputs($this->file, substr("                             ", 0, 5));

		// Code Guichet  D3

		fputs($this->file, $this->emetteur_code_guichet);

		// Numero de compte D4

		fputs($this->file, substr("000000000000000".$this->emetteur_numero_compte, -11));

		// Zone Reservee E

		fputs($this->file, substr("                                        ", 0, 16));

		// Zone Reservee F

		fputs($this->file, substr("                                        ", 0, 31));

		// Code etablissement

		fputs($this->file, $this->emetteur_code_banque);

		// Zone Reservee G

		fputs($this->file, substr("                                        ", 0, 5));

		fputs($this->file, "\n");
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write sender of request (me).
	 *  Note: The tag PmtInf is opened here but closed into caller
	 *
	 *	@param	Conf	$configuration		conf
	 *	@param	int     $ladate				Date
	 *	@param	int		$nombre				0 or 1
	 *	@param	float	$total				Total
	 *	@param	string	$CrLf				End of line character
	 *  @param	string	$format				FRST or RCUR or ALL
	 *  @param	string	$type				'direct-debit' or 'bank-transfer'
	 *  @param	int		$fk_bank_account	Bank account ID the receipt is generated for. Will use the ID into the setup of module Direct Debit or Credit Transfer if 0.
	 *	@return	string						String with SEPA Sender
	 *  @see EnregEmetteur()
	 */
	public function EnregEmetteurSEPA($configuration, $ladate, $nombre, $total, $CrLf = '\n', $format = 'FRST', $type = 'direct-debit', $fk_bank_account = 0)
	{
		// phpcs:enable
		// SEPA INITIALISATION
		global $conf;

		// Clean parameters
		$dateTime_YMD = dol_print_date($ladate, '%Y%m%d');
		$dateTime_ETAD = dol_print_date($ladate, '%Y-%m-%d');
		$dateTime_YMDHMS = dol_print_date($ladate, '%Y-%m-%dT%H:%M:%S');

		// Clean params
		if (empty($fk_bank_account)) {
			$fk_bank_account = ($type == 'bank-transfer' ? $conf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT : $conf->global->PRELEVEMENT_ID_BANKACCOUNT);
		}

		// Get data of bank account
		$account = new Account($this->db);
		if ($account->fetch($fk_bank_account) > 0) {
			$this->emetteur_code_banque = $account->code_banque;
			$this->emetteur_code_guichet = $account->code_guichet;
			$this->emetteur_numero_compte = $account->number;
			$this->emetteur_number_key = $account->cle_rib;
			$this->sepa_xml_pti_in_ctti = (bool) $account->pti_in_ctti;
			$this->emetteur_iban = $account->iban;
			$this->emetteur_bic = $account->bic;

			$this->emetteur_ics = ($type == 'bank-transfer' ? $account->ics_transfer : $account->ics);  // Ex: PRELEVEMENT_ICS = "FR78ZZZ123456";

			$this->raison_sociale = $account->proprio;
		}

		// Get pending payments
		$sql = "SELECT rowid, ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as pb";
		$sql .= " WHERE pb.rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);

			$country = explode(':', $configuration->global->MAIN_INFO_SOCIETE_COUNTRY);
			$IdBon  = sprintf("%05d", $obj->rowid);
			$RefBon = $obj->ref;

			if ($type != 'bank-transfer') {
				// SEPA Paiement Information of my company for Direct Debit
				$XML_SEPA_INFO = '';
				$XML_SEPA_INFO .= '		<PmtInf>'.$CrLf;
				$XML_SEPA_INFO .= '			<PmtInfId>'.('DD/'.$dateTime_YMD.'/ID'.$IdBon.'-'.$RefBon).'</PmtInfId>'.$CrLf;
				$XML_SEPA_INFO .= '			<PmtMtd>DD</PmtMtd>'.$CrLf;
				$XML_SEPA_INFO .= '			<NbOfTxs>'.$nombre.'</NbOfTxs>'.$CrLf;
				$XML_SEPA_INFO .= '			<CtrlSum>'.$total.'</CtrlSum>'.$CrLf;
				$XML_SEPA_INFO .= '			<PmtTpInf>'.$CrLf;
				$XML_SEPA_INFO .= '				<SvcLvl>'.$CrLf;
				$XML_SEPA_INFO .= '					<Cd>SEPA</Cd>'.$CrLf;
				$XML_SEPA_INFO .= '				</SvcLvl>'.$CrLf;
				$XML_SEPA_INFO .= '				<LclInstrm>'.$CrLf;
				$XML_SEPA_INFO .= '					<Cd>CORE</Cd>'.$CrLf;
				$XML_SEPA_INFO .= '				</LclInstrm>'.$CrLf;
				$XML_SEPA_INFO .= '				<SeqTp>'.$format.'</SeqTp>'.$CrLf;
				$XML_SEPA_INFO .= '			</PmtTpInf>'.$CrLf;
				$XML_SEPA_INFO .= '			<ReqdColltnDt>'.$dateTime_ETAD.'</ReqdColltnDt>'.$CrLf;
				$XML_SEPA_INFO .= '			<Cdtr>'.$CrLf;
				$XML_SEPA_INFO .= '				<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($this->raison_sociale), ' '))).'</Nm>'.$CrLf;
				$XML_SEPA_INFO .= '				<PstlAdr>'.$CrLf;
				$XML_SEPA_INFO .= '					<Ctry>'.$country[1].'</Ctry>'.$CrLf;
				$addressline1 = strtr($configuration->global->MAIN_INFO_SOCIETE_ADDRESS, array(CHR(13) => ", ", CHR(10) => ""));
				$addressline2 = strtr($configuration->global->MAIN_INFO_SOCIETE_ZIP.(($configuration->global->MAIN_INFO_SOCIETE_ZIP || ' '.$configuration->global->MAIN_INFO_SOCIETE_TOWN) ? ' ' : '').$configuration->global->MAIN_INFO_SOCIETE_TOWN, array(CHR(13) => ", ", CHR(10) => ""));
				if ($addressline1) {
					$XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline1), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
				}
				if ($addressline2) {
					$XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline2), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
				}
				$XML_SEPA_INFO .= '				</PstlAdr>'.$CrLf;
				$XML_SEPA_INFO .= '			</Cdtr>'.$CrLf;
				$XML_SEPA_INFO .= '			<CdtrAcct>'.$CrLf;
				$XML_SEPA_INFO .= '				<Id>'.$CrLf;
				$XML_SEPA_INFO .= '					<IBAN>'.preg_replace('/\s/', '', $this->emetteur_iban).'</IBAN>'.$CrLf;
				$XML_SEPA_INFO .= '				</Id>'.$CrLf;
				$XML_SEPA_INFO .= '			</CdtrAcct>'.$CrLf;
				$XML_SEPA_INFO .= '			<CdtrAgt>'.$CrLf;
				$XML_SEPA_INFO .= '				<FinInstnId>'.$CrLf;
				$XML_SEPA_INFO .= '					<BIC>'.$this->emetteur_bic.'</BIC>'.$CrLf;
				$XML_SEPA_INFO .= '				</FinInstnId>'.$CrLf;
				$XML_SEPA_INFO .= '			</CdtrAgt>'.$CrLf;
				/* $XML_SEPA_INFO .= '			<UltmtCdtr>'.$CrLf;
				 $XML_SEPA_INFO .= '				<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($this->raison_sociale), ' '))).'</Nm>'.$CrLf;
				 $XML_SEPA_INFO .= '				<PstlAdr>'.$CrLf;
				 $XML_SEPA_INFO .= '					<Ctry>'.$country[1].'</Ctry>'.$CrLf;
				 $XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_string_nospecial(dol_string_unaccent($conf->global->MAIN_INFO_SOCIETE_ADDRESS), ' ')).'</AdrLine>'.$CrLf;
				 $XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_string_nospecial(dol_string_unaccent($conf->global->MAIN_INFO_SOCIETE_ZIP.' '.$conf->global->MAIN_INFO_SOCIETE_TOWN), ' ')).'</AdrLine>'.$CrLf;
				 $XML_SEPA_INFO .= '				</PstlAdr>'.$CrLf;
				 $XML_SEPA_INFO .= '			</UltmtCdtr>'.$CrLf;*/
				$XML_SEPA_INFO .= '			<ChrgBr>SLEV</ChrgBr>'.$CrLf; // Field "Responsible of fees". Must be SLEV
				$XML_SEPA_INFO .= '			<CdtrSchmeId>'.$CrLf;
				$XML_SEPA_INFO .= '				<Id>'.$CrLf;
				$XML_SEPA_INFO .= '					<PrvtId>'.$CrLf;
				$XML_SEPA_INFO .= '						<Othr>'.$CrLf;
				$XML_SEPA_INFO .= '							<Id>'.$this->emetteur_ics.'</Id>'.$CrLf;
				$XML_SEPA_INFO .= '							<SchmeNm>'.$CrLf;
				$XML_SEPA_INFO .= '								<Prtry>SEPA</Prtry>'.$CrLf;
				$XML_SEPA_INFO .= '							</SchmeNm>'.$CrLf;
				$XML_SEPA_INFO .= '						</Othr>'.$CrLf;
				$XML_SEPA_INFO .= '					</PrvtId>'.$CrLf;
				$XML_SEPA_INFO .= '				</Id>'.$CrLf;
				$XML_SEPA_INFO .= '			</CdtrSchmeId>'.$CrLf;
			} else {
				// SEPA Paiement Information of my company for Credit Transfer
				$XML_SEPA_INFO = '';
				$XML_SEPA_INFO .= '		<PmtInf>'.$CrLf;
				$XML_SEPA_INFO .= '			<PmtInfId>'.('TRF/'.$dateTime_YMD.'/ID'.$IdBon.'-'.$RefBon).'</PmtInfId>'.$CrLf;
				$XML_SEPA_INFO .= '			<PmtMtd>TRF</PmtMtd>'.$CrLf;
				//$XML_SEPA_INFO .= '			<BtchBookg>False</BtchBookg>'.$CrLf;
				$XML_SEPA_INFO .= '			<NbOfTxs>'.$nombre.'</NbOfTxs>'.$CrLf;
				$XML_SEPA_INFO .= '			<CtrlSum>'.$total.'</CtrlSum>'.$CrLf;
				if (!empty($this->sepa_xml_pti_in_ctti) && !empty($format)) {	// @TODO Using $format (FRST ou RCUR) in a section for a Credit Transfer looks strange.
					$XML_SEPA_INFO .= '			<PmtTpInf>' . $CrLf;
					$XML_SEPA_INFO .= '				<SvcLvl>' . $CrLf;
					$XML_SEPA_INFO .= '					<Cd>SEPA</Cd>' . $CrLf;
					$XML_SEPA_INFO .= '				</SvcLvl>' . $CrLf;
					$XML_SEPA_INFO .= '				<LclInstrm>' . $CrLf;
					$XML_SEPA_INFO .= '					<Cd>CORE</Cd>' . $CrLf;
					$XML_SEPA_INFO .= '				</LclInstrm>' . $CrLf;
					$XML_SEPA_INFO .= '				<SeqTp>' . $format . '</SeqTp>' . $CrLf;
					$XML_SEPA_INFO .= '			</PmtTpInf>' . $CrLf;
				}
				$XML_SEPA_INFO .= '			<ReqdExctnDt>'.dol_print_date($dateTime_ETAD, 'dayrfc').'</ReqdExctnDt>'.$CrLf;
				$XML_SEPA_INFO .= '			<Dbtr>'.$CrLf;
				$XML_SEPA_INFO .= '				<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($this->raison_sociale), ' '))).'</Nm>'.$CrLf;
				$XML_SEPA_INFO .= '				<PstlAdr>'.$CrLf;
				$XML_SEPA_INFO .= '					<Ctry>'.$country[1].'</Ctry>'.$CrLf;
				$addressline1 = strtr($configuration->global->MAIN_INFO_SOCIETE_ADDRESS, array(CHR(13) => ", ", CHR(10) => ""));
				$addressline2 = strtr($configuration->global->MAIN_INFO_SOCIETE_ZIP.(($configuration->global->MAIN_INFO_SOCIETE_ZIP || ' '.$configuration->global->MAIN_INFO_SOCIETE_TOWN) ? ' ' : '').$configuration->global->MAIN_INFO_SOCIETE_TOWN, array(CHR(13) => ", ", CHR(10) => ""));
				if ($addressline1) {
					$XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline1), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
				}
				if ($addressline2) {
					$XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($addressline2), ' '), 70, 'right', 'UTF-8', 1)).'</AdrLine>'.$CrLf;
				}
				$XML_SEPA_INFO .= '				</PstlAdr>'.$CrLf;
				$XML_SEPA_INFO .= '			</Dbtr>'.$CrLf;
				$XML_SEPA_INFO .= '			<DbtrAcct>'.$CrLf;
				$XML_SEPA_INFO .= '				<Id>'.$CrLf;
				$XML_SEPA_INFO .= '					<IBAN>'.preg_replace('/\s/', '', $this->emetteur_iban).'</IBAN>'.$CrLf;
				$XML_SEPA_INFO .= '				</Id>'.$CrLf;
				$XML_SEPA_INFO .= '			</DbtrAcct>'.$CrLf;
				$XML_SEPA_INFO .= '			<DbtrAgt>'.$CrLf;
				$XML_SEPA_INFO .= '				<FinInstnId>'.$CrLf;
				$XML_SEPA_INFO .= '					<BIC>'.$this->emetteur_bic.'</BIC>'.$CrLf;
				$XML_SEPA_INFO .= '				</FinInstnId>'.$CrLf;
				$XML_SEPA_INFO .= '			</DbtrAgt>'.$CrLf;
				/* $XML_SEPA_INFO .= '			<UltmtCdtr>'.$CrLf;
				 $XML_SEPA_INFO .= '				<Nm>'.dolEscapeXML(strtoupper(dol_string_nospecial(dol_string_unaccent($this->raison_sociale), ' '))).'</Nm>'.$CrLf;
				 $XML_SEPA_INFO .= '				<PstlAdr>'.$CrLf;
				 $XML_SEPA_INFO .= '					<Ctry>'.$country[1].'</Ctry>'.$CrLf;
				 $XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_string_nospecial(dol_string_unaccent($conf->global->MAIN_INFO_SOCIETE_ADDRESS), ' ')).'</AdrLine>'.$CrLf;
				 $XML_SEPA_INFO .= '					<AdrLine>'.dolEscapeXML(dol_string_nospecial(dol_string_unaccent($conf->global->MAIN_INFO_SOCIETE_ZIP.' '.$conf->global->MAIN_INFO_SOCIETE_TOWN), ' ')).'</AdrLine>'.$CrLf;
				 $XML_SEPA_INFO .= '				</PstlAdr>'.$CrLf;
				 $XML_SEPA_INFO .= '			</UltmtCdtr>'.$CrLf;*/
				$XML_SEPA_INFO .= '			<ChrgBr>SLEV</ChrgBr>'.$CrLf; // Field "Responsible of fees". Must be SLEV
				/*$XML_SEPA_INFO .= '			<CdtrSchmeId>'.$CrLf;
				 $XML_SEPA_INFO .= '				<Id>'.$CrLf;
				 $XML_SEPA_INFO .= '					<PrvtId>'.$CrLf;
				 $XML_SEPA_INFO .= '						<Othr>'.$CrLf;
				 $XML_SEPA_INFO .= '							<Id>'.$this->emetteur_ics.'</Id>'.$CrLf;
				 $XML_SEPA_INFO .= '							<SchmeNm>'.$CrLf;
				 $XML_SEPA_INFO .= '								<Prtry>SEPA</Prtry>'.$CrLf;
				 $XML_SEPA_INFO .= '							</SchmeNm>'.$CrLf;
				 $XML_SEPA_INFO .= '						</Othr>'.$CrLf;
				 $XML_SEPA_INFO .= '					</PrvtId>'.$CrLf;
				 $XML_SEPA_INFO .= '				</Id>'.$CrLf;
				 $XML_SEPA_INFO .= '			</CdtrSchmeId>'.$CrLf;*/
			}
		} else {
			fputs($this->file, 'INCORRECT EMETTEUR '.$this->raison_sociale.$CrLf);
			$XML_SEPA_INFO = '';
		}
		return $XML_SEPA_INFO;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write end
	 *
	 *	@param	int		$total	total amount
	 *	@return	void
	 */
	public function EnregTotal($total)
	{
		// phpcs:enable
		fputs($this->file, "08");
		fputs($this->file, "08"); // Prelevement ordinaire

		fputs($this->file, "        "); // Zone Reservee B2

		fputs($this->file, $this->emetteur_ics); // ICS

		// Reserve C1

		fputs($this->file, substr("                           ", 0, 12));


		// Raison Sociale C2

		fputs($this->file, substr("                           ", 0, 24));

		// D1

		fputs($this->file, substr("                                    ", 0, 24));

		// Zone Reservee D2

		fputs($this->file, substr("                             ", 0, 8));

		// Code Guichet  D3

		fputs($this->file, substr("                             ", 0, 5));

		// Numero de compte D4

		fputs($this->file, substr("                             ", 0, 11));

		// Zone E Montant

		$montant = ($total * 100);

		fputs($this->file, substr("000000000000000".$montant, -16));

		// Zone Reservee F

		fputs($this->file, substr("                                        ", 0, 31));

		// Code etablissement

		fputs($this->file, substr("                                        ", 0, 5));

		// Zone Reservee F

		fputs($this->file, substr("                                        ", 0, 5));

		fputs($this->file, "\n");
	}

	/**
	 *  Return status label of object
	 *
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return	string     			Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return status label for a status
	 *
	 *  @param	int		$status     Id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return	string  		    Label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('StatusWaiting');
			$this->labelStatus[self::STATUS_TRANSFERED] = $langs->transnoentitiesnoconv('StatusTrans');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('StatusWaiting');
			$this->labelStatusShort[self::STATUS_TRANSFERED] = $langs->transnoentitiesnoconv('StatusTrans');
			if ($this->type == 'bank-transfer') {
				$this->labelStatus[self::STATUS_DEBITED] = $langs->transnoentitiesnoconv('StatusDebited');
				$this->labelStatusShort[self::STATUS_DEBITED] = $langs->transnoentitiesnoconv('StatusDebited');
			} else {
				$this->labelStatus[self::STATUS_CREDITED] = $langs->transnoentitiesnoconv('StatusCredited');
				$this->labelStatusShort[self::STATUS_CREDITED] = $langs->transnoentitiesnoconv('StatusCredited');
			}
		}

		$statusType = 'status1';
		if ($status == self::STATUS_TRANSFERED) {
			$statusType = 'status3';
		}
		if ($status == self::STATUS_CREDITED || $status == self::STATUS_DEBITED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param      User	$user       	Objet user
	 *      @param		string	$mode			Mode 'direct_debit' or 'credit_transfer'
	 *      @return 	WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		/*
		 if ($mode == 'direct_debit') {
		 $sql = "SELECT b.rowid, f.datedue as datefin";
		 $sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		 $sql .= " WHERE f.entity IN (".getEntity('facture').")";
		 $sql .= " AND f.total_ttc > 0";
		 } else {
		 $sql = "SELECT b.rowid, f.datedue as datefin";
		 $sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
		 $sql .= " WHERE f.entity IN (".getEntity('facture_fourn').")";
		 $sql .= " AND f.total_ttc > 0";
		 }

		 $resql = $this->db->query($sql);
		 if ($resql) {
		 $langs->load("banks");
		 $now = dol_now();

		 $response = new WorkboardResponse();
		 if ($mode == 'direct_debit') {
		 $response->warning_delay = $conf->prelevement->warning_delay / 60 / 60 / 24;
		 $response->label = $langs->trans("PendingDirectDebitToComplete");
		 $response->labelShort = $langs->trans("PendingDirectDebitToCompleteShort");
		 $response->url = DOL_URL_ROOT.'/compta/prelevement/index.php?leftmenu=checks&mainmenu=bank';
		 } else {
		 $response->warning_delay = $conf->paymentbybanktransfer->warning_delay / 60 / 60 / 24;
		 $response->label = $langs->trans("PendingCreditTransferToComplete");
		 $response->labelShort = $langs->trans("PendingCreditTransferToCompleteShort");
		 $response->url = DOL_URL_ROOT.'/compta/paymentbybanktransfer/index.php?leftmenu=checks&mainmenu=bank';
		 }
		 $response->img = img_object('', "payment");

		 while ($obj = $this->db->fetch_object($resql)) {
		 $response->nbtodo++;

		 if ($this->db->jdate($obj->datefin) < ($now - $conf->withdraw->warning_delay)) {
		 $response->nbtodolate++;
		 }
		 }

		 $response->nbtodo = 0;
		 $response->nbtodolate = 0;
		 // Return workboard only if quantity is not 0
		 if ($response->nbtodo) {
		 return $response;
		 } else {
		 return 0;
		 }
		 } else {
		 dol_print_error($this->db);
		 $this->error = $this->db->error();
		 return -1;
		 }
		 */
		return 0;
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
		$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';

		if (property_exists($this, 'date_echeance')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Date").'</span> : <span class="info-box-label">'.dol_print_date($this->db->jdate($this->date_echeance), 'day').'</span>';
		}
		if (property_exists($this, 'total')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Amount").'</span> : <span class="amount">'.price($this->total).'</span>';
		}
		if (method_exists($this, 'LibStatut')) {
			$return .= '<br><div class="info-box-status margintoponly">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
