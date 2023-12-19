<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013   	Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2016       Marcos García           <marcosgdf@gmail.com>
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
 *  \file		htdocs/societe/class/companybankaccount.class.php
 *  \ingroup    societe
 *  \brief      File of class to manage bank accounts description of third parties
 */

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';


/**
 * 	Class to manage bank accounts description of third parties
 */
class CompanyBankAccount extends Account
{
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'societe_rib';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'societe_rib';

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
	 *		Note: Filter must be a Dolibarr Universal Filter syntax string. Example: "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.status:!=:0) or (t.nature:is:NULL)"
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
	 *  'help' and 'helplist' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
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
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>'1', 'position'=>10, 'notnull'=>1, 'visible'=>-1,),
		'type' => array('type'=>'varchar(32)', 'label'=>'Type', 'enabled'=>'1', 'position'=>15, 'notnull'=>1, 'visible'=>-1,),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'picto'=>'company', 'enabled'=>'1', 'position'=>20, 'notnull'=>1, 'visible'=>-1, 'css'=>'maxwidth500 widthcentpercentminusxx', 'csslist'=>'tdoverflowmax150',),
		'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>'1', 'position'=>25, 'notnull'=>0, 'visible'=>-1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>'1', 'position'=>30, 'notnull'=>1, 'visible'=>-1,),
		'label' => array('type'=>'varchar(200)', 'label'=>'Label', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1', 'css'=>'minwidth300', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax150',),
		'bank' => array('type'=>'varchar(255)', 'label'=>'Bank', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'code_banque' => array('type'=>'varchar(128)', 'label'=>'Codebanque', 'enabled'=>'1', 'position'=>45, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'code_guichet' => array('type'=>'varchar(6)', 'label'=>'Codeguichet', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'number' => array('type'=>'varchar(255)', 'label'=>'Number', 'enabled'=>'1', 'position'=>55, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'cle_rib' => array('type'=>'varchar(5)', 'label'=>'Clerib', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'bic' => array('type'=>'varchar(20)', 'label'=>'Bic', 'enabled'=>'1', 'position'=>65, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'iban_prefix' => array('type'=>'varchar(34)', 'label'=>'Ibanprefix', 'enabled'=>'1', 'position'=>70, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'domiciliation' => array('type'=>'varchar(255)', 'label'=>'Domiciliation', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'proprio' => array('type'=>'varchar(60)', 'label'=>'Proprio', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'owner_address' => array('type'=>'varchar(255)', 'label'=>'Owneraddress', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'default_rib' => array('type'=>'smallint(6)', 'label'=>'Defaultrib', 'enabled'=>'1', 'position'=>90, 'notnull'=>1, 'visible'=>-1, 'alwayseditable'=>'1',),
		'state_id' => array('type'=>'integer', 'label'=>'Stateid', 'enabled'=>'1', 'position'=>95, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'fk_country' => array('type'=>'integer', 'label'=>'Fkcountry', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1', 'css'=>'maxwidth500 widthcentpercentminusxx',),
		'currency_code' => array('type'=>'varchar(3)', 'label'=>'Currencycode', 'enabled'=>'1', 'position'=>105, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'rum' => array('type'=>'varchar(32)', 'label'=>'Rum', 'enabled'=>'1', 'position'=>110, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'date_rum' => array('type'=>'date', 'label'=>'Daterum', 'enabled'=>'1', 'position'=>115, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'frstrecur' => array('type'=>'varchar(16)', 'label'=>'Frstrecur', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'import_key' => array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>'1', 'position'=>900, 'notnull'=>0, 'visible'=>-2, 'alwayseditable'=>'1',),
		'last_four' => array('type'=>'varchar(4)', 'label'=>'Lastfour', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'card_type' => array('type'=>'varchar(255)', 'label'=>'Cardtype', 'enabled'=>'1', 'position'=>135, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'cvn' => array('type'=>'varchar(255)', 'label'=>'Cvn', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'exp_date_month' => array('type'=>'integer', 'label'=>'Expdatemonth', 'enabled'=>'1', 'position'=>145, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'exp_date_year' => array('type'=>'integer', 'label'=>'Expdateyear', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'country_code' => array('type'=>'varchar(10)', 'label'=>'Countrycode', 'enabled'=>'1', 'position'=>155, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'approved' => array('type'=>'integer', 'label'=>'Approved', 'enabled'=>'1', 'position'=>160, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'email' => array('type'=>'varchar(255)', 'label'=>'Email', 'enabled'=>'1', 'position'=>165, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'ending_date' => array('type'=>'date', 'label'=>'Endingdate', 'enabled'=>'1', 'position'=>170, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'max_total_amount_of_all_payments' => array('type'=>'double(24,8)', 'label'=>'Maxtotalamountofallpayments', 'enabled'=>'1', 'position'=>175, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'preapproval_key' => array('type'=>'varchar(255)', 'label'=>'Preapprovalkey', 'enabled'=>'1', 'position'=>180, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'starting_date' => array('type'=>'date', 'label'=>'Startingdate', 'enabled'=>'1', 'position'=>185, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'total_amount_of_all_payments' => array('type'=>'double(24,8)', 'label'=>'Totalamountofallpayments', 'enabled'=>'1', 'position'=>190, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'stripe_card_ref' => array('type'=>'varchar(128)', 'label'=>'Stripecardref', 'enabled'=>'1', 'position'=>195, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'status' => array('type'=>'integer', 'label'=>'Status', 'enabled'=>'1', 'position'=>500, 'notnull'=>1, 'visible'=>-1, 'alwayseditable'=>'1',),
		'comment' => array('type'=>'varchar(255)', 'label'=>'Comment', 'enabled'=>'1', 'position'=>205, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'ipaddress' => array('type'=>'varchar(68)', 'label'=>'Ipaddress', 'enabled'=>'1', 'position'=>210, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'stripe_account' => array('type'=>'varchar(128)', 'label'=>'Stripeaccount', 'enabled'=>'1', 'position'=>215, 'notnull'=>0, 'visible'=>-1, 'alwayseditable'=>'1',),
		'last_main_doc' =>array('type'=>'varchar(255)', 'label'=>'LastMainDoc', 'enabled'=>1, 'visible'=>0, 'position'=>230),
	);
	public $id;
	public $type;
	/**
	 * @var int		Thirdparty ID
	 * @deprecated	Use socid
	 */
	public $fk_soc;
	/**
	 * @var int		Thirdparty ID
	 */
	public $socid;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;
	public $label;
	public $bank;
	public $code_banque;
	public $code_guichet;
	public $number;
	public $cle_rib;
	public $bic;
	public $iban_prefix;
	public $domiciliation;
	public $proprio;
	public $owner_address;

	/**
	 * @var bool $default_rib  1 = this object is the third party's default bank information
	 */
	public $default_rib;
	public $state_id;
	public $fk_country;
	public $currency_code;
	public $rum;
	public $date_rum;

	/**
	 * Value 'FRST' or 'RCUR' (For SEPA mandate)
	 *
	 * @var string
	 */
	public $frstrecur;
	public $import_key;
	public $last_four;
	public $card_type;
	public $cvn;
	public $exp_date_month;
	public $exp_date_year;
	public $country_code;
	public $approved;
	public $email;
	public $ending_date;
	public $max_total_amount_of_all_payments;
	public $preapproval_key;
	public $starting_date;
	public $total_amount_of_all_payments;


	public $ext_payment_site;	// Name of the external payment system ('StripeLive', 'StripeTest', 'StancerLive', 'StancerTest', ...)
	public $comment;
	public $ipaddress;


	/**
	 * Account of the external payment system
	 *
	 * @var string
	 */
	public $stripe_account;

	/**
	 * ID of BAN into an external payment system
	 *
	 * @var string
	 */
	public $stripe_card_ref;

	/**
	 * Date modification record (tms)
	 *
	 * @var integer
	 */
	public $datem;

	/**
	 * @var string
	 * @see SetDocModel()
	 */
	public $model_pdf;

	/**
	 * @var string TRIGGER_PREFIX  Dolibarr 16.0 and above use the prefix to prevent the creation of inconsistently
	 *                             named triggers
	 * @see CommonObject::call_trigger()
	 */
	const TRIGGER_PREFIX = 'COMPANY_RIB';

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->socid = 0;
		$this->solde = 0;
		$this->balance = 0;
		$this->default_rib = 0;
		$this->type = "ban";
	}


	/**
	 * Create bank information record.
	 *
	 * @param   User|null   $user		User
	 * @param   int    		$notrigger  1=Disable triggers
	 * @return	int						Return integer <0 if KO, > 0 if OK (ID of newly created company bank account information)
	 */
	public function create(User $user = null, $notrigger = 0)
	{
		$now = dol_now();

		$error = 0;

		// Check paramaters
		if (empty($this->socid)) {
			$this->error = 'BadValueForParameter';
			$this->errors[] = $this->error;
			return -1;
		}

		if (empty($this->datec)) {
			$this->datec=$now;
		}

		// Correct ->default_rib to not set the new account as default, if there is already 1. We want to be sure to have always 1 default for type = 'ban'.
		// If we really want the new bank account to be the default, we must set it by calling setDefault() after creation.
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_rib where fk_soc = ".((int) $this->socid)." AND default_rib = 1 AND type = 'ban'";
		$result = $this->db->query($sql);
		if ($result) {
			$numrows = $this->db->num_rows($result);
			if ($this->default_rib && $numrows > 0) {
				$this->default_rib = 0;
			}
			if (empty($this->default_rib) && $numrows == 0) {
				$this->default_rib = 1;
			}
		}


		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_rib (fk_soc, type, datec, model_pdf)";
		$sql .= " VALUES (".((int) $this->socid).", '".$this->type."', '".$this->db->idate($this->datec)."',";
		$sql .= " '".$this->db->escape(getDolGlobalString("BANKADDON_PDF"))."'";
		$sql .= ")";
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->affected_rows($resql)) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe_rib");

				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('COMPANY_RIB_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
		}

		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update bank account
	 *
	 *	@param	User|null	$user	     Object user
	 *  @param  int     	$notrigger   1=Disable triggers
	 *	@return	int					     Return integer <=0 if KO, >0 if OK
	 */
	public function update(User $user = null, $notrigger = 0)
	{
		global $langs;

		$error = 0;

		if (!$this->id) {
			return -1;
		}

		if (dol_strlen($this->domiciliation) > 255) {
			$this->domiciliation = dol_trunc($this->domiciliation, 254, 'right', 'UTF-8', 1);
		}
		if (dol_strlen($this->owner_address) > 255) {
			$this->owner_address = dol_trunc($this->owner_address, 254, 'right', 'UTF-8', 1);
		}

		if (isset($this->model_pdf)) {
			$this->model_pdf = trim($this->model_pdf);
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET";
		$sql .= " bank = '".$this->db->escape($this->bank)."'";
		$sql .= ",code_banque='".$this->db->escape($this->code_banque)."'";
		$sql .= ",code_guichet='".$this->db->escape($this->code_guichet)."'";
		$sql .= ",number='".$this->db->escape($this->number)."'";
		$sql .= ",cle_rib='".$this->db->escape($this->cle_rib)."'";
		$sql .= ",bic='".$this->db->escape($this->bic)."'";
		$sql .= ",iban_prefix = '".$this->db->escape($this->iban)."'";
		$sql .= ",domiciliation = '".$this->db->escape($this->domiciliation)."'";
		$sql .= ",proprio = '".$this->db->escape($this->proprio)."'";
		$sql .= ",owner_address = '".$this->db->escape($this->owner_address)."'";
		$sql .= ",default_rib = ".((int) $this->default_rib);
		if (isModEnabled('prelevement')) {
			$sql .= ",frstrecur = '".$this->db->escape($this->frstrecur)."'";
			$sql .= ",rum = '".$this->db->escape($this->rum)."'";
			$sql .= ",date_rum = ".($this->date_rum ? "'".$this->db->idate($this->date_rum)."'" : "null");
		}
		if (trim($this->label) != '') {
			$sql .= ",label = '".$this->db->escape($this->label)."'";
		} else {
			$sql .= ",label = NULL";
		}
		$sql .= ",stripe_card_ref = '".$this->db->escape($this->stripe_card_ref)."'";
		$sql .= ",stripe_account = '".$this->db->escape($this->stripe_account)."'";
		$sql .= ",model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null");
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('COMPANY_RIB_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		} else {
			$error++;
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$this->error = $langs->trans('ErrorDuplicateField');
			} else {
				$this->error = $this->db->lasterror();
			}
			$this->errors[] = $this->error;
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
	 * 	Load record from database
	 *
	 *	@param	int		$id			Id of record
	 * 	@param	int		$socid		Id of company. If this is filled, function will return the first entry found (matching $default and $type)
	 *  @param	int		$default	If id of company filled, we say if we want first record among all (-1), default record (1) or non default record (0)
	 *  @param	int		$type		If id of company filled, we say if we want record of this type only
	 * 	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id, $socid = 0, $default = 1, $type = 'ban')
	{
		if (empty($id) && empty($socid)) {
			return -1;
		}

		$sql = "SELECT rowid, type, fk_soc as socid, bank, number, code_banque, code_guichet, cle_rib, bic, iban_prefix as iban, domiciliation, proprio,";
		$sql .= " owner_address, default_rib, label, datec, tms as datem, rum, frstrecur, date_rum,";
		$sql .= " stripe_card_ref, stripe_account, ext_payment_site";
		$sql .= " ,last_main_doc";
		$sql .= " ,model_pdf";

		$sql .= " FROM ".MAIN_DB_PREFIX."societe_rib";
		if ($id) {
			$sql .= " WHERE rowid = ".((int) $id);
		} elseif ($socid > 0) {
			$sql .= " WHERE fk_soc  = ".((int) $socid);
			if ($default > -1) {
				$sql .= " AND default_rib = ".((int) $default);
			}
			if ($type) {
				$sql .= " AND type = '".$this->db->escape($type)."'";
			}
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->ref = $obj->socid.'-'.$obj->label; // Generate an artificial ref

				$this->id = $obj->rowid;
				$this->type = $obj->type;
				$this->socid           = $obj->socid;
				$this->bank            = $obj->bank;
				$this->code_banque     = $obj->code_banque;
				$this->code_guichet    = $obj->code_guichet;
				$this->number          = $obj->number;
				$this->cle_rib         = $obj->cle_rib;
				$this->bic             = $obj->bic;
				$this->iban = $obj->iban;
				$this->domiciliation   = $obj->domiciliation;
				$this->proprio         = $obj->proprio;
				$this->owner_address   = $obj->owner_address;
				$this->label           = $obj->label;
				$this->default_rib     = $obj->default_rib;
				$this->datec           = $this->db->jdate($obj->datec);
				$this->datem           = $this->db->jdate($obj->datem);
				$this->rum             = $obj->rum;
				$this->frstrecur       = $obj->frstrecur;
				$this->date_rum        = $this->db->jdate($obj->date_rum);
				$this->stripe_card_ref = $obj->stripe_card_ref;		// External system payment mode ID
				$this->stripe_account  = $obj->stripe_account;		// External system customer ID
				$this->ext_payment_site= $obj->ext_payment_site;	// External system name ('StripeLive', 'StripeTest', 'StancerLive', 'StancerTest', ...)
				$this->last_main_doc   = $obj->last_main_doc;
				$this->model_pdf   	   = $obj->model_pdf;
			}
			$this->db->free($resql);

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *  Delete a rib from database
	 *
	 *	@param		User|null	$user		User deleting
	 *	@param  	int			$notrigger	1=Disable triggers
	 *  @return		int		    	        Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user = null, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(get_class($this)."::delete ".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('COMPANY_RIB_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_rib";
			$sql .= " WHERE rowid = ".((int) $this->id);

			if (!$this->db->query($sql)) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 * Return RIB
	 *
	 * @param   boolean     $displayriblabel     Prepend or Hide Label
	 * @return	string		RIB
	 */
	public function getRibLabel($displayriblabel = true)
	{
		$rib = '';

		if ($this->code_banque || $this->code_guichet || $this->number || $this->cle_rib || $this->iban || $this->bic) {
			if ($this->label && $displayriblabel) {
				$rib = $this->label." : ";
			}

			$rib .= $this->iban;
		}

		return $rib;
	}

	/**
	 * Set a BAN as Default
	 *
	 * @param   int     $rib    			RIB id
	 * @param	string	$resetolddefaultfor	Reset if we have already a default value for type = 'ban'
	 * @return  int             			0 if KO, 1 if OK
	 */
	public function setAsDefault($rib = 0, $resetolddefaultfor = 'ban')
	{
		$sql1 = "SELECT rowid as id, fk_soc as socid FROM ".MAIN_DB_PREFIX."societe_rib";
		$sql1 .= " WHERE rowid = ".($rib ? $rib : $this->id);

		dol_syslog(get_class($this).'::setAsDefault', LOG_DEBUG);
		$result1 = $this->db->query($sql1);
		if ($result1) {
			if ($this->db->num_rows($result1) == 0) {
				return 0;
			} else {
				$obj = $this->db->fetch_object($result1);

				$this->db->begin();

				$sql2 = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET default_rib = 0";
				$sql2 .= " WHERE fk_soc = ".((int) $obj->socid);
				if ($resetolddefaultfor) {
					$sql2 .= " AND type = '".$this->db->escape($resetolddefaultfor)."'";
				}
				$result2 = $this->db->query($sql2);

				$sql3 = "UPDATE ".MAIN_DB_PREFIX."societe_rib SET default_rib = 1";
				$sql3 .= " WHERE rowid = ".((int) $obj->id);
				$result3 = $this->db->query($sql3);

				if (!$result2 || !$result3) {
					dol_print_error($this->db);
					$this->db->rollback();
					return -1;
				} else {
					$this->db->commit();
					return 1;
				}
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
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
		$this->specimen        = 1;
		$this->ref             = 'CBA';
		$this->label           = 'CustomerCorp Bank account';
		$this->bank            = 'CustomerCorp Bank';
		$this->courant         = Account::TYPE_CURRENT;
		$this->clos            = Account::STATUS_OPEN;
		$this->code_banque     = '123';
		$this->code_guichet    = '456';
		$this->number          = 'CUST12345';
		$this->cle_rib         = 50;
		$this->bic             = 'CC12';
		$this->iban            = 'FR999999999';
		$this->domiciliation   = 'Bank address of customer corp';
		$this->proprio         = 'Owner';
		$this->owner_address   = 'Owner address';
		$this->country_id      = 1;

		$this->rum             = 'UMR-CU1212-0007-5-1475405262';
		$this->date_rum        = dol_now() - 10000;
		$this->frstrecur       = 'FRST';

		$this->socid           = 1;
	}
}
