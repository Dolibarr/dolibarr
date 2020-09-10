<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005-2010	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015-2017	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2016		Ferran Marcet   		<fmarcet@2byte.es>
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
 *	\file       htdocs/compta/bank/class/account.class.php
 *	\ingroup    bank
 *	\brief      File of class to manage bank accounts
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage bank accounts
 */
class Account extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'bank_account';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'bank_account';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'account';

	/**
	 * @var	int		Use id instead of rowid
	 * @deprecated
	 * @see $id
	 */
	public $rowid;

	/**
	 * Account Label
	 * @var string
	 */
	public $label;

	/**
	 * Bank account type. Check TYPE_ constants
	 * @var int
	 */
	public $courant;

	/**
	 * Bank account type. Check TYPE_ constants
	 * @var int
	 */
	public $type;

	/**
	 * Bank name
	 * @var string
	 */
	public $bank;

	/**
	 * Status
	 * @var int
	 */
	public $clos = self::STATUS_OPEN;

	/**
	 * Does it need to be conciliated?
	 * @var int
	 */
	public $rappro = 1;

	/**
	 * Webpage
	 * @var string
	 */
	public $url;

	/**
	 * Bank number. If in SEPA area, you should move to IBAN field
	 * @var string
	 */
	public $code_banque;

	/**
	 * Branch number. If in SEPA area, you should move to IBAN field
	 * @var string
	 */
	public $code_guichet;

	/**
	 * Account number. If in SEPA area, you should move to IBAN field
	 * @var string
	 */
	public $number;

	/**
	 * Bank account number control digit. If in SEPA area, you should move to IBAN field
	 * @var string
	 */
	public $cle_rib;

	/**
	 * BIC/Swift code
	 * @var string
	 */
	public $bic;

	/**
	 * IBAN number (International Bank Account Number). Stored into iban_prefix field into database
	 * @var string
	 */
	public $iban;

	/**
	 * Name of account holder
	 * @var string
	 */
	public $proprio;

	/**
	 * Address of account holder
	 * @var string
	 */
	public $owner_address;

	public $state_id;
	public $state_code;
	public $state;

	/**
	 * Variable containing all account types with their respective translated label.
	 * Defined in __construct
	 * @var array
	 */
	public $type_lib = array();

	/**
	 * Variable containing all account statuses with their respective translated label.
	 * Defined in __construct
	 * @var array
	 */
	public $status = array();

	/**
	 * Accountancy code
	 * @var string
	 */
	public $account_number;

	/**
     * @var int ID
     */
	public $fk_accountancy_journal;

	/**
	 * Currency code
	 * @var string
	 */
	public $currency_code;

	/**
	 * Currency code
	 * @var string
	 * @deprecated Use currency_code instead
	 */
	public $account_currency_code;

	/**
	 * Authorized minimum balance
	 * @var float
	 */
	public $min_allowed;

	/**
	 * Desired minimum balance
	 * @var float
	 */
	public $min_desired;

	/**
	 * Notes
	 * @var string
	 */
	public $comment;

	/**
	 * Date of the initial balance. Used in Account::create
	 * @var int
	 */
	public $date_solde;


	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'ref' =>array('type'=>'varchar(12)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'showoncombobox'=>1, 'position'=>25),
		'label' =>array('type'=>'varchar(30)', 'label'=>'Label', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>30),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>35, 'index'=>1),
		'bank' =>array('type'=>'varchar(60)', 'label'=>'Bank', 'enabled'=>1, 'visible'=>-1, 'position'=>40),
		'code_banque' =>array('type'=>'varchar(128)', 'label'=>'Code banque', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'code_guichet' =>array('type'=>'varchar(6)', 'label'=>'Code guichet', 'enabled'=>1, 'visible'=>-1, 'position'=>50),
		'number' =>array('type'=>'varchar(255)', 'label'=>'Number', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'cle_rib' =>array('type'=>'varchar(5)', 'label'=>'Cle rib', 'enabled'=>1, 'visible'=>-1, 'position'=>60),
		'bic' =>array('type'=>'varchar(11)', 'label'=>'Bic', 'enabled'=>1, 'visible'=>-1, 'position'=>65),
		'iban_prefix' =>array('type'=>'varchar(34)', 'label'=>'Iban prefix', 'enabled'=>1, 'visible'=>-1, 'position'=>70),
		'country_iban' =>array('type'=>'varchar(2)', 'label'=>'Country iban', 'enabled'=>1, 'visible'=>-1, 'position'=>75),
		'cle_iban' =>array('type'=>'varchar(2)', 'label'=>'Cle iban', 'enabled'=>1, 'visible'=>-1, 'position'=>80),
		'domiciliation' =>array('type'=>'varchar(255)', 'label'=>'Domiciliation', 'enabled'=>1, 'visible'=>-1, 'position'=>85),
		'state_id' =>array('type'=>'integer', 'label'=>'State id', 'enabled'=>1, 'visible'=>-1, 'position'=>90),
		'fk_pays' =>array('type'=>'integer', 'label'=>'Fk pays', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>95),
		'proprio' =>array('type'=>'varchar(60)', 'label'=>'Proprio', 'enabled'=>1, 'visible'=>-1, 'position'=>100),
		'owner_address' =>array('type'=>'text', 'label'=>'Owner address', 'enabled'=>1, 'visible'=>-1, 'position'=>105),
		'courant' =>array('type'=>'smallint(6)', 'label'=>'Courant', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>110),
		'clos' =>array('type'=>'smallint(6)', 'label'=>'Clos', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>115),
		'rappro' =>array('type'=>'smallint(6)', 'label'=>'Rappro', 'enabled'=>1, 'visible'=>-1, 'position'=>120),
		'url' =>array('type'=>'varchar(128)', 'label'=>'Url', 'enabled'=>1, 'visible'=>-1, 'position'=>125),
		'account_number' =>array('type'=>'varchar(32)', 'label'=>'Account number', 'enabled'=>1, 'visible'=>-1, 'position'=>130),
		'accountancy_journal' =>array('type'=>'varchar(20)', 'label'=>'Accountancy journal', 'enabled'=>1, 'visible'=>-1, 'position'=>135),
		'currency_code' =>array('type'=>'varchar(3)', 'label'=>'Currency code', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>140),
		'min_allowed' =>array('type'=>'integer', 'label'=>'Min allowed', 'enabled'=>1, 'visible'=>-1, 'position'=>145),
		'min_desired' =>array('type'=>'integer', 'label'=>'Min desired', 'enabled'=>1, 'visible'=>-1, 'position'=>150),
		'comment' =>array('type'=>'text', 'label'=>'Comment', 'enabled'=>1, 'visible'=>-1, 'position'=>155),
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>156),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>157),
		'fk_user_author' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fk user author', 'enabled'=>1, 'visible'=>-1, 'position'=>160),
		'fk_user_modif' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>165),
		'note_public' =>array('type'=>'text', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>0, 'position'=>170),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>175),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>180),
		'extraparams' =>array('type'=>'varchar(255)', 'label'=>'Extraparams', 'enabled'=>1, 'visible'=>-1, 'position'=>185),
		'fk_accountancy_journal' =>array('type'=>'integer', 'label'=>'Fk accountancy journal', 'enabled'=>1, 'visible'=>-1, 'position'=>190),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * Current account
	 */
	const TYPE_CURRENT = 1;
	/**
	 * Cash account
	 */
	const TYPE_CASH = 2;
	/**
	 * Savings account
	 */
	const TYPE_SAVINGS = 0;

	const STATUS_OPEN = 0;
	const STATUS_CLOSED = 1;

	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db		Database handler
	 */
    public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;

		$this->solde = 0;

		$this->type_lib = array(
			self::TYPE_SAVINGS => $langs->trans("BankType0"),
			self::TYPE_CURRENT => $langs->trans("BankType1"),
			self::TYPE_CASH => $langs->trans("BankType2"),
		);

		$this->status = array(
			self::STATUS_OPEN => $langs->trans("StatusAccountOpened"),
			self::STATUS_CLOSED => $langs->trans("StatusAccountClosed")
		);
	}

	/**
	 * Shows the account number in the appropriate format
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = '';
		foreach ($this->getFieldsToShow() as $val) {
			if ($val == 'BankCode') {
				$string .= $this->code_banque.' ';
			} elseif ($val == 'BankAccountNumber') {
				$string .= $this->number.' ';
			} elseif ($val == 'DeskCode') {
				$string .= $this->code_guichet.' ';
			} elseif ($val == 'BankAccountNumberKey') {
				$string .= $this->cle_rib.' ';
			}elseif ($val == 'BIC') {
				$string .= $this->bic.' ';
			}elseif ($val == 'IBAN') {
				$string .= $this->iban.' ';
			}
		}

		return trim($string);
	}


	/**
	 *  Return if a bank account need to be conciliated
	 *
	 *  @return     int         1 if need to be concialiated, < 0 otherwise.
	 */
    public function canBeConciliated()
	{
		global $conf;

		if (empty($this->rappro)) return -1;
		if ($this->courant == Account::TYPE_CASH && empty($conf->global->BANK_CAN_RECONCILIATE_CASHACCOUNT)) return -2;
		if ($this->clos) return -3;
		return 1;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Add a link between bank line record and its source
	 *
	 *      @param	int		$line_id    Id ecriture bancaire
	 *      @param  int		$url_id     Id parametre url
	 *      @param  string	$url        Url
	 *      @param  string	$label      Link label
	 *      @param  string	$type       Type of link ('payment', 'company', 'member', ...)
	 *      @return int         		<0 if KO, id line if OK
	 */
    public function add_url_line($line_id, $url_id, $url, $label, $type)
	{
        // phpcs:enable
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_url (";
		$sql .= "fk_bank";
		$sql .= ", url_id";
		$sql .= ", url";
		$sql .= ", label";
		$sql .= ", type";
		$sql .= ") VALUES (";
		$sql .= "'".$line_id."'";
		$sql .= ", '".$url_id."'";
		$sql .= ", '".$url."'";
		$sql .= ", '".$this->db->escape($label)."'";
		$sql .= ", '".$type."'";
		$sql .= ")";

		dol_syslog(get_class($this)."::add_url_line", LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_url");
			return $rowid;
		}
		else
		{
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 		TODO Move this into AccountLine
	 *      Return array with links from llx_bank_url
	 *
	 *      @param  int         $fk_bank    To search using bank transaction id
	 *      @param  int         $url_id     To search using link to
	 *      @param  string      $type       To search using type
	 *      @return array|int               Array of links array('url'=>, 'url_id'=>, 'label'=>, 'type'=> 'fk_bank'=> ) or -1 on error
	 */
    public function get_url($fk_bank = '', $url_id = '', $type = '')
	{
        // phpcs:enable
		$lines = array();

		// Check parameters
		if (!empty($fk_bank) && (!empty($url_id) || !empty($type)))
		{
			$this->error = "ErrorBadParameter";
			return -1;
		}

		$sql = "SELECT fk_bank, url_id, url, label, type";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_url";
		if ($fk_bank > 0) {
			$sql .= " WHERE fk_bank = ".$fk_bank;
		}
		else { $sql .= " WHERE url_id = ".$url_id." AND type = '".$type."'";
		}
		$sql .= " ORDER BY type, label";

		dol_syslog(get_class($this)."::get_url", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$i = 0;
			$num = $this->db->num_rows($result);
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($result);
				// Anciens liens (pour compatibilite)
				$lines[$i][0] = $obj->url;
				$lines[$i][1] = $obj->url_id;
				$lines[$i][2] = $obj->label;
				$lines[$i][3] = $obj->type;
				// Nouveaux liens
				$lines[$i]['url'] = $obj->url;
				$lines[$i]['url_id'] = $obj->url_id;
				$lines[$i]['label'] = $obj->label;
				$lines[$i]['type'] = $obj->type;
				$lines[$i]['fk_bank'] = $obj->fk_bank;
				$i++;
			}
		}
		else dol_print_error($this->db);

		return $lines;
	}

	/**
	 *  Add an entry into table ".MAIN_DB_PREFIX."bank
	 *
	 *  @param	int	        $date			Date operation
	 *  @param	string		$oper			1,2,3,4... (deprecated) or 'TYP','VIR','PRE','LIQ','VAD','CB','CHQ'...
	 *  @param	string		$label			Descripton
	 *  @param	float		$amount			Amount
	 *  @param	string		$num_chq		Numero cheque or transfer
	 *  @param	int  		$categorie		Category id (optionnal)
	 *  @param	User		$user			User that create
	 *  @param	string		$emetteur		Name of cheque writer
	 *  @param	string		$banque			Bank of cheque writer
	 *  @param	string		$accountancycode	When we record a free bank entry, we must provide accounting account if accountancy module is on.
	 *  @param	int			$datev			Date value
	 *  @return	int							Rowid of added entry, <0 if KO
	 */
    public function addline($date, $oper, $label, $amount, $num_chq, $categorie, User $user, $emetteur = '', $banque = '', $accountancycode = '', $datev = null)
	{
		// Deprecation warning
		if (is_numeric($oper)) {
			dol_syslog(__METHOD__.": using numeric operations is deprecated", LOG_WARNING);
		}

		// Clean parameters
		$emetteur = trim($emetteur);
		$banque = trim($banque);

		$now = dol_now();

		if (is_numeric($oper))    // Clean operation to have a code instead of a rowid
		{
			$sql = "SELECT code FROM ".MAIN_DB_PREFIX."c_paiement";
			$sql .= " WHERE id=".$oper;
			$sql .= " AND entity IN (".getEntity('c_paiement').")";
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$obj = $this->db->fetch_object($resql);
				$oper = $obj->code;
			}
			else
			{
				dol_print_error($this->db, 'Failed to get payment type code');
				return -1;
			}
		}

		// Check parameters
		if (!$oper)
		{
			$this->error = "oper not defined";
			return -1;
		}
		if (!$this->rowid)
		{
			$this->error = "this->rowid not defined";
			return -2;
		}
		if ($this->courant == Account::TYPE_CASH && $oper != 'LIQ')
		{
			$this->error = "ErrorCashAccountAcceptsOnlyCashMoney";
			return -3;
		}

		$this->db->begin();

		if (is_null($datev) || empty($datev)) $datev = $date;

		$accline = new AccountLine($this->db);
		$accline->datec = $now;
		$accline->dateo = $date;
		$accline->datev = $datev;
		$accline->label = $label;
		$accline->amount = $amount;
		$accline->fk_user_author = $user->id;
		$accline->fk_account = $this->rowid;
		$accline->fk_type = $oper;
		$accline->numero_compte = $accountancycode;

		if ($num_chq) {
			$accline->num_chq = $num_chq;
		}

		if ($emetteur) {
			$accline->emetteur = $emetteur;
		}

		if ($banque) {
			$accline->bank_chq = $banque;
		}

		if ($accline->insert() > 0) {
			if ($categorie > 0) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (";
				$sql .= "lineid, fk_categ";
				$sql .= ") VALUES (";
				$sql .= $accline->id.", ".$categorie;
				$sql .= ")";

				$result = $this->db->query($sql);
				if (!$result) {
					$this->error = $this->db->lasterror();
					$this->db->rollback();

					return -3;
				}
			}

			$this->db->commit();

			return $accline->id;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();

			return -2;
		}
	}

	/**
	 *  Create bank account into database
	 *
	 *  @param	User	$user		Object user making creation
	 *  @param  int     $notrigger  1=Disable triggers
	 *  @return int        			< 0 if KO, > 0 if OK
	 */
    public function create(User $user, $notrigger = 0)
	{
		global $langs, $conf, $hookmanager;

		$error = 0;

		// Clean parameters
		if (!$this->min_allowed) $this->min_allowed = 0;
		if (!$this->min_desired) $this->min_desired = 0;
		$this->state_id = ($this->state_id ? $this->state_id : $this->state_id);
		$this->country_id = ($this->country_id ? $this->country_id : $this->country_id);

		// Check parameters
		if (empty($this->country_id))
		{
			$this->error = $langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Country"));
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}
		if (empty($this->ref))
		{
			$this->error = $langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}
		if (empty($this->date_solde))
		{
			$this->error = $langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateInitialBalance"));
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}

		// Chargement librairie pour acces fonction controle RIB
		require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_account (";
		$sql .= "datec";
		$sql .= ", ref";
		$sql .= ", label";
		$sql .= ", entity";
		$sql .= ", account_number";
		$sql .= ", fk_accountancy_journal";
		$sql .= ", bank";
		$sql .= ", code_banque";
		$sql .= ", code_guichet";
		$sql .= ", number";
		$sql .= ", cle_rib";
		$sql .= ", bic";
		$sql .= ", iban_prefix";
		$sql .= ", domiciliation";
		$sql .= ", proprio";
		$sql .= ", owner_address";
		$sql .= ", currency_code";
		$sql .= ", rappro";
		$sql .= ", min_allowed";
		$sql .= ", min_desired";
		$sql .= ", comment";
		$sql .= ", state_id";
		$sql .= ", fk_pays";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->idate($now)."'";
		$sql .= ", '".$this->db->escape($this->ref)."'";
		$sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", ".$conf->entity;
		$sql .= ", '".$this->db->escape($this->account_number)."'";
		$sql .= ", ".($this->fk_accountancy_journal > 0 ? $this->db->escape($this->fk_accountancy_journal) : "null");
		$sql .= ", '".$this->db->escape($this->bank)."'";
		$sql .= ", '".$this->db->escape($this->code_banque)."'";
		$sql .= ", '".$this->db->escape($this->code_guichet)."'";
		$sql .= ", '".$this->db->escape($this->number)."'";
		$sql .= ", '".$this->db->escape($this->cle_rib)."'";
		$sql .= ", '".$this->db->escape($this->bic)."'";
		$sql .= ", '".$this->db->escape($this->iban)."'";
		$sql .= ", '".$this->db->escape($this->domiciliation)."'";
		$sql .= ", '".$this->db->escape($this->proprio)."'";
		$sql .= ", '".$this->db->escape($this->owner_address)."'";
		$sql .= ", '".$this->db->escape($this->currency_code)."'";
		$sql .= ", ".$this->rappro;
		$sql .= ", ".price2num($this->min_allowed);
		$sql .= ", ".price2num($this->min_desired);
		$sql .= ", '".$this->db->escape($this->comment)."'";
		$sql .= ", ".($this->state_id > 0 ? $this->state_id : "null");
		$sql .= ", ".$this->country_id;
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."bank_account");

			$result = $this->update($user, 1);
			if ($result > 0)
			{
				$accline = new AccountLine($this->db);
				$accline->datec = $this->db->idate($now);
				$accline->label = '('.$langs->trans("InitialBankBalance").')';
				$accline->amount = price2num($this->solde);
				$accline->fk_user_author = $user->id;
				$accline->fk_account = $this->id;
				$accline->datev = $this->db->idate($this->date_solde);
				$accline->dateo = $this->db->idate($this->date_solde);
				$accline->fk_type = 'SOLD';

				if ($accline->insert() < 0) {
					$error++;
					$this->error = $accline->error;
					$this->errors = $accline->errors;
				}

				if (!$error)
				{
					$result = $this->insertExtraFields();
					if ($result < 0) $error++;
				}

				if (!$error && !$notrigger)
				{
					// Call trigger
					$result = $this->call_trigger('BANKACCOUNT_CREATE', $user);
					if ($result < 0) $error++;
					// End call triggers
				}
			}
			else
			{
				$error++;
			}
		}
		else
		{
			if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$this->error = $langs->trans("ErrorBankLabelAlreadyExists");
				$error++;
			}
			else {
				$this->error = $this->db->error()." sql=".$sql;
				$error++;
			}
		}

		if (!$error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 *    	Update bank account card
	 *
	 *    	@param	User	$user       Object user making action
	 *      @param  int     $notrigger  1=Disable triggers
	 *		@return	int					<0 if KO, >0 if OK
	 */
    public function update(User $user, $notrigger = 0)
	{
		global $langs, $conf, $hookmanager;

		$error = 0;

		$this->db->begin();

		// Clean parameters
		$this->state_id = ($this->state_id ? $this->state_id : $this->state_id);
		$this->country_id = ($this->country_id ? $this->country_id : $this->country_id);

		// Check parameters
		if (empty($this->country_id))
		{
			$this->error = $langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Country"));
			dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
			return -1;
		}
		if (empty($this->ref))
		{
			$this->error = $langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref"));
			dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
			return -1;
		}
		if (!$this->label) $this->label = "???";

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";

		$sql .= " ref   = '".$this->db->escape($this->ref)."'";
		$sql .= ",label = '".$this->db->escape($this->label)."'";

		$sql .= ",courant = ".$this->courant;
		$sql .= ",clos = ".$this->clos;
		$sql .= ",rappro = ".$this->rappro;
		$sql .= ",url = ".($this->url ? "'".$this->db->escape($this->url)."'" : "null");
		$sql .= ",account_number = '".$this->db->escape($this->account_number)."'";
		$sql .= ",fk_accountancy_journal = ".($this->fk_accountancy_journal > 0 ? $this->db->escape($this->fk_accountancy_journal) : "null");
		$sql .= ",bank  = '".$this->db->escape($this->bank)."'";
		$sql .= ",code_banque='".$this->db->escape($this->code_banque)."'";
		$sql .= ",code_guichet='".$this->db->escape($this->code_guichet)."'";
		$sql .= ",number='".$this->db->escape($this->number)."'";
		$sql .= ",cle_rib='".$this->db->escape($this->cle_rib)."'";
		$sql .= ",bic='".$this->db->escape($this->bic)."'";
		$sql .= ",iban_prefix = '".$this->db->escape($this->iban)."'";
		$sql .= ",domiciliation='".$this->db->escape($this->domiciliation)."'";
		$sql .= ",proprio = '".$this->db->escape($this->proprio)."'";
		$sql .= ",owner_address = '".$this->db->escape($this->owner_address)."'";

		$sql .= ",currency_code = '".$this->db->escape($this->currency_code)."'";

		$sql .= ",min_allowed = ".($this->min_allowed != '' ? price2num($this->min_allowed) : "null");
		$sql .= ",min_desired = ".($this->min_desired != '' ? price2num($this->min_desired) : "null");
		$sql .= ",comment     = '".$this->db->escape($this->comment)."'";

		$sql .= ",state_id = ".($this->state_id > 0 ? $this->state_id : "null");
		$sql .= ",fk_pays = ".$this->country_id;

		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			// Actions on extra fields (by external module or standard code)
			if (!$error)
			{
				$result = $this->insertExtraFields();
				if ($result < 0) $error++;
			}

			if (!$error && !$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('BANKACCOUNT_UPDATE', $user);
				if ($result < 0) $error++;
				// End call triggers
			}
		}
		else
		{
			$error++;
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
		}

		if (!$error)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			$this->db->rollback();
			return -1 * $error;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update BBAN (RIB) account fields
	 *
	 *  @param	User	$user       Object user making update
	 *	@return	int					<0 if KO, >0 if OK
	 */
    public function update_bban(User $user = null)
	{
        // phpcs:enable
		global $conf, $langs;

		// Clean parameters
		$this->state_id = ($this->state_id ? $this->state_id : $this->state_id);
		$this->country_id = ($this->country_id ? $this->country_id : $this->country_id);

		// Chargement librairie pour acces fonction controle RIB
		require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

		dol_syslog(get_class($this)."::update_bban $this->code_banque,$this->code_guichet,$this->number,$this->cle_rib,$this->iban");

		// Check parameters
		if (!$this->ref)
		{
			$this->error = $langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->trans("Ref"));
			return -2;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank_account SET ";
		$sql .= " bank  = '".$this->db->escape($this->bank)."'";
		$sql .= ",code_banque='".$this->db->escape($this->code_banque)."'";
		$sql .= ",code_guichet='".$this->db->escape($this->code_guichet)."'";
		$sql .= ",number='".$this->db->escape($this->number)."'";
		$sql .= ",cle_rib='".$this->db->escape($this->cle_rib)."'";
		$sql .= ",bic='".$this->db->escape($this->bic)."'";
		$sql .= ",iban_prefix = '".$this->db->escape($this->iban)."'";
		$sql .= ",domiciliation='".$this->db->escape($this->domiciliation)."'";
		$sql .= ",proprio = '".$this->db->escape($this->proprio)."'";
		$sql .= ",owner_address = '".$this->db->escape($this->owner_address)."'";
		$sql .= ",state_id = ".($this->state_id > 0 ? $this->state_id : "null");
		$sql .= ",fk_pays = ".$this->country_id;
		$sql .= " WHERE rowid = ".$this->id;
		$sql .= " AND entity = ".$conf->entity;

		dol_syslog(get_class($this)."::update_bban", LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result)
		{
			return 1;
		}
		else
		{
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *      Load a bank account into memory from database
	 *
	 *      @param	int		$id      	Id of bank account to get
	 *      @param  string	$ref     	Ref of bank account to get
	 *      @return	int					<0 if KO, >0 if OK
	 */
    public function fetch($id, $ref = '')
	{
		global $conf;

		if (empty($id) && empty($ref))
		{
			$this->error = "ErrorBadParameters";
			return -1;
		}

		$sql = "SELECT ba.rowid, ba.ref, ba.label, ba.bank, ba.number, ba.courant, ba.clos, ba.rappro, ba.url,";
		$sql .= " ba.code_banque, ba.code_guichet, ba.cle_rib, ba.bic, ba.iban_prefix as iban,";
		$sql .= " ba.domiciliation, ba.proprio, ba.owner_address, ba.state_id, ba.fk_pays as country_id,";
		$sql .= " ba.account_number, ba.fk_accountancy_journal, ba.currency_code,";
		$sql .= " ba.min_allowed, ba.min_desired, ba.comment,";
		$sql .= " ba.datec as date_creation, ba.tms as date_update,";
		$sql .= ' c.code as country_code, c.label as country,';
		$sql .= ' d.code_departement as state_code, d.nom as state';
        $sql .= ' , aj.code as accountancy_journal';
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON ba.fk_pays = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON ba.state_id = d.rowid';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'accounting_journal as aj ON aj.rowid=ba.fk_accountancy_journal';
		$sql .= " WHERE ba.entity IN (".getEntity($this->element).")";
		if ($id)  $sql .= " AND ba.rowid  = ".$id;
		if ($ref) $sql .= " AND ba.ref = '".$this->db->escape($ref)."'";

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id            = $obj->rowid;
				$this->rowid         = $obj->rowid;
				$this->ref           = $obj->ref;
				$this->label         = $obj->label;
				$this->type          = $obj->courant;
				$this->courant       = $obj->courant;
				$this->bank          = $obj->bank;
				$this->clos          = $obj->clos;
				$this->rappro        = $obj->rappro;
				$this->url           = $obj->url;

				$this->code_banque   = $obj->code_banque;
				$this->code_guichet  = $obj->code_guichet;
				$this->number        = $obj->number;
				$this->cle_rib       = $obj->cle_rib;
				$this->bic           = $obj->bic;
				$this->iban          = $obj->iban;
				$this->domiciliation = $obj->domiciliation;
				$this->proprio       = $obj->proprio;
				$this->owner_address = $obj->owner_address;

				$this->state_id        = $obj->state_id;
				$this->state_code      = $obj->state_code;
				$this->state           = $obj->state;

				$this->country_id    = $obj->country_id;
				$this->country_code  = $obj->country_code;
				$this->country       = $obj->country;

				$this->account_number = $obj->account_number;
				$this->fk_accountancy_journal = $obj->fk_accountancy_journal;
				$this->accountancy_journal = $obj->accountancy_journal;

				$this->currency_code  = $obj->currency_code;
				$this->account_currency_code = $obj->currency_code;
				$this->min_allowed    = $obj->min_allowed;
				$this->min_desired    = $obj->min_desired;
				$this->comment        = $obj->comment;

				$this->date_creation  = $this->db->jdate($obj->date_creation);
				$this->date_update    = $this->db->jdate($obj->date_update);

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error = $this->db->lasterror;
			$this->errors[] = $this->error;
			return -1;
		}
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param int[]|int $categories Category or categories IDs
     * @return void
	 */
    public function setCategories($categories)
    {
		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, Categorie::TYPE_ACCOUNT, 'id');

		// Diff
		if (is_array($existing)) {
			$to_del = array_diff($existing, $categories);
			$to_add = array_diff($categories, $existing);
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = $categories;
		}

		// Process
		foreach ($to_del as $del) {
			if ($c->fetch($del) > 0) {
				$c->del_type($this, Categorie::TYPE_ACCOUNT);
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, Categorie::TYPE_ACCOUNT);
			}
		}

		return;
	}

	/**
	 *  Delete bank account from database
	 *
	 *	@param	User	$user	User deleting
	 *  @return int             <0 if KO, >0 if OK
	 */
    public function delete(User $user = null)
	{
		global $conf;

		$error = 0;

		$this->db->begin();

		// Delete link between tag and bank account
		if (!$error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_account";
			$sql .= " WHERE fk_account = ".$this->id;

			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$error++;
				$this->error = "Error ".$this->db->lasterror();
			}
		}

		if (!$error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_account";
			$sql .= " WHERE rowid = ".$this->rowid;

			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				// Remove extrafields
				if (!$error)
				{
					$result = $this->deleteExtraFields();
					if ($result < 0)
					{
						$error++;
						dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
					}
				}
			}
			else
			{
				$error++;
				$this->error = "Error ".$this->db->lasterror();
			}
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Return label of object status
	 *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @return     string        		    Label
	 */
    public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->clos, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of given object status
	 *
	 *  @param	 int		$status        	Id status
	 *  @param   int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto, 6=Long label + picto
	 *  @return  string        			    Label
	 */
    public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;
		$langs->load('banks');

		if ($status == self::STATUS_OPEN) {
			$label = $langs->trans("StatusAccountOpened");
			$labelshort = $langs->trans("StatusAccountOpened");
			$statusType = 'status4';
		} else {
			$label = $langs->trans("StatusAccountClosed");
			$labelshort = $langs->trans("StatusAccountClosed");
			$statusType = 'status5';
		}

		return dolGetStatus($label, $labelshort, '', $statusType, $mode);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Renvoi si un compte peut etre supprimer ou non (sans mouvements)
	 *
	 *    @return     boolean     vrai si peut etre supprime, faux sinon
	 */
    public function can_be_deleted()
	{
        // phpcs:enable
		$can_be_deleted = false;

		$sql = "SELECT COUNT(rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE fk_account=".$this->id;

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj->nb <= 1) $can_be_deleted = true; // Juste le solde
		}
		else {
			dol_print_error($this->db);
		}
		return $can_be_deleted;
	}


	/**
	 *   Return error
	 *
	 *   @return	string		Error string
	 */
    public function error()
	{
		return $this->error;
	}

	/**
	 * 	Return current sold
	 *
	 * 	@param	int		$option		1=Exclude future operation date (this is to exclude input made in advance and have real account sold)
	 *	@return	int					Current sold (value date <= today)
	 */
    public function solde($option = 0)
	{
		$solde = 0;

		$sql = "SELECT sum(amount) as amount";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE fk_account = ".$this->id;
		if ($option == 1) $sql .= " AND dateo <= '".$this->db->idate(dol_now())."'";

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$solde = $obj->amount;
			}
			$this->db->free($resql);
		} else {
			$this->errors[] = $this->db->lasterror;
			return -1;
		}

		return price2num($solde, 'MU');
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user        		Objet user
	 *		@param	int		$filteraccountid	To get info for a particular account id
	 *      @return WorkboardResponse|int 		<0 if KO, WorkboardResponse if OK
	 */
    public function load_board(User $user, $filteraccountid = 0)
	{
        // phpcs:enable
		global $conf, $langs;

		if ($user->socid) return -1; // protection pour eviter appel par utilisateur externe

		$sql = "SELECT b.rowid, b.datev as datefin";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b,";
		$sql .= " ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.rappro=0";
		$sql .= " AND b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND (ba.rappro = 1 AND ba.courant != 2)"; // Compte rapprochable
		$sql .= " AND clos = 0";
		if ($filteraccountid) $sql .= " AND ba.rowid = ".$filteraccountid;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$langs->load("banks");
			$now = dol_now();

			require_once DOL_DOCUMENT_ROOT.'/core/class/workboardresponse.class.php';

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->bank->rappro->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("TransactionsToConciliate");
			$response->labelShort = $langs->trans("TransactionsToConciliateShort");
			$response->url = DOL_URL_ROOT.'/compta/bank/list.php?leftmenu=bank&amp;mainmenu=bank';
			$response->img = img_object('', "payment");

			while ($obj = $this->db->fetch_object($resql))
			{
				$response->nbtodo++;
				if ($this->db->jdate($obj->datefin) < ($now - $conf->bank->rappro->warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		}
		else
		{
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Charge indicateurs this->nb de tableau de bord
	 *		@param		int			$filteraccountid	To get info for a particular account id
	 *      @return     int         <0 if ko, >0 if ok
	 */
    public function load_state_board($filteraccountid = 0)
	{
        // phpcs:enable
		global $user;

		if ($user->socid) return -1; // protection pour eviter appel par utilisateur externe

		$sql = "SELECT count(b.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b,";
		$sql .= " ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		$sql .= " AND (ba.rappro = 1 AND ba.courant != 2)"; // Compte rapprochable
		$sql .= " AND clos = 0";
		if ($filteraccountid) $sql .= " AND ba.rowid = ".$filteraccountid;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->nb["banklines"] = $obj->nb;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @return int     Nb of account we can reconciliate
	 */
	public static function countAccountToReconcile()
	{
		global $db, $conf, $user;

		//Protection against external users
		if ($user->socid) {
			return 0;
		}

		$nb = 0;

		$sql = "SELECT COUNT(ba.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE ba.rappro > 0 and ba.clos = 0";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		if (empty($conf->global->BANK_CAN_RECONCILIATE_CASHACCOUNT)) $sql .= " AND ba.courant != 2";
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$nb = $obj->nb;
		}
		else dol_print_error($db);

		return $nb;
	}

	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpicto					Include picto into link
	 *  @param  string	$mode           			''=Link to card, 'transactions'=Link to transactions card
	 *  @param  string  $option         			''=Show ref, 'reflabel'=Show ref+label
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @param	int  	$notooltip		 			1=Disable tooltip
	 *	@return	string								Chaine avec URL
	 */
    public function getNomUrl($withpicto = 0, $mode = '', $option = '', $save_lastsearch_value = -1, $notooltip = 0)
	{
		global $conf, $langs, $user;

		$result = '';
		$label = '<u>'.$langs->trans("BankAccount").'</u>';
		$label .= '<br><b>'.$langs->trans('Label').':</b> '.$this->label;
		$label .= '<br><b>'.$langs->trans('AccountNumber').':</b> '.$this->number;
		$label .= '<br><b>'.$langs->trans('IBAN').':</b> '.$this->iban;
		$label .= '<br><b>'.$langs->trans('BIC').':</b> '.$this->bic;
		$label .= '<br><b>'.$langs->trans("AccountCurrency").':</b> '.$this->currency_code;

		if (empty($user->rights->banque->lire) || !empty($user->socid))
		{
			$option = 'nolink';
		}

		if (!empty($conf->accounting->enabled))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
			$langs->load("accountancy");
			$label .= '<br><b>'.$langs->trans('AccountAccounting').':</b> '.length_accountg($this->account_number);
			$label .= '<br><b>'.$langs->trans('AccountancyJournal').':</b> '.$this->accountancy_journal;
		}
		if (isset($this->status)) {
			$label .= '<br><b>'.$langs->trans("Status").":</b> ".$this->getLibStatut(5);
		}

		$linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';

		$url = DOL_URL_ROOT.'/compta/bank/card.php?id='.$this->id;
		if ($mode == 'transactions')
		{
			$url = DOL_URL_ROOT.'/compta/bank/bankentries_list.php?id='.$this->id;
		}
		elseif ($mode == 'receipts')
		{
			$url = DOL_URL_ROOT.'/compta/bank/releve.php?account='.$this->id;
		}

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkstart = '<a href="'.$url.$linkclose;
		$linkend = '</a>';

        if ($option == 'nolink') {
            $linkstart = '';
            $linkend = '';
        }

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->ref.($option == 'reflabel' && $this->label ? ' - '.$this->label : '');
		$result .= $linkend;

		return $result;
	}


	// Method after here are common to Account and CompanyBankAccount


	/**
	 *     Return if an account has valid information for Direct debit payment
	 *
	 *     @return     int         1 if correct, <=0 if wrong
	 */
    public function verif()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

		$this->error_number = 0;

		// Call function to check BAN

		if (!checkIbanForAccount($this) || !checkSwiftForAccount($this))
		{
			$this->error_number = 12;
			$this->error_message = 'IBANSWIFTControlError';
		}
		/*if (! checkBanForAccount($this))
        {
            $this->error_number = 12;
            $this->error_message = 'BANControlError';
        }*/

		if ($this->error_number == 0)
		{
			return 1;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * 	Return account country code
	 *
	 *	@return		string		country code
	 */
    public function getCountryCode()
	{
		global $mysoc;

		// We return country code of bank account
		if (!empty($this->country_code)) return $this->country_code;

		// For backward compatibility, we try to guess country from other information
		if (!empty($this->iban))
		{
			// If IBAN defined, we can know country of account from it
			$reg = array();
			if (preg_match("/^([a-zA-Z][a-zA-Z])/i", $this->iban, $reg)) return $reg[1];
		}

		// If this class is linked to a third party
		if (!empty($this->socid))
		{
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$company = new Societe($this->db);
			$result = $company->fetch($this->socid);
			if (!empty($company->country_code)) return $company->country_code;
		}

		// We return country code of managed company
		if (!empty($mysoc->country_code)) return $mysoc->country_code;

		return '';
	}

	/**
	 * Return if a bank account is defined with detailed information (bank code, desk code, number and key).
	 * More information on codes used by countries on page http://en.wikipedia.org/wiki/Bank_code
	 *
	 * @return		int        0=No bank code need + Account number is enough
	 *                         1=Need 2 fields for bank code: Bank, Desk (France, Spain, ...) + Account number and key
	 *                         2=Need 1 field for bank code:  Bank only (Sort code for Great Britain, BSB for Australia) + Account number
	 */
    public function useDetailedBBAN()
	{
		$country_code = $this->getCountryCode();

		if (in_array($country_code, array('FR', 'ES', 'GA', 'IT', 'NC'))) return 1; // France, Spain, Gabon, ... - Not valid for CH
		if (in_array($country_code, array('AU', 'BE', 'CA', 'DE', 'DK', 'GR', 'GB', 'ID', 'IE', 'IR', 'KR', 'NL', 'NZ', 'UK', 'US'))) return 2; // Australia, England...
		return 0;
	}

	/**
	 * Return 1 if IBAN / BIC is mandatory (otherwise option)
	 *
	 * @return		int        1 = mandatory / 0 = Not mandatory
	 */
    public function needIBAN()
	{
		$country_code = $this->getCountryCode();

		$country_code_in_EEC = array(
				'AT', // Austria
				'BE', // Belgium
				'BG', // Bulgaria
				'CY', // Cyprus
				'CZ', // Czech republic
				'DE', // Germany
				'DK', // Danemark
				'EE', // Estonia
				'ES', // Spain
				'FI', // Finland
				'FR', // France
				'GB', // United Kingdom
				'GR', // Greece
				'HR', // Croatia
				'NL', // Holland
				'HU', // Hungary
				'IE', // Ireland
				'IM', // Isle of Man - Included in UK
				'IT', // Italy
				'LT', // Lithuania
				'LU', // Luxembourg
				'LV', // Latvia
				'MC', // Monaco - Included in France
				'MT', // Malta
				//'NO',	// Norway
				'PL', // Poland
				'PT', // Portugal
				'RO', // Romania
				'SE', // Sweden
				'SK', // Slovakia
				'SI', // Slovenia
				'UK', // United Kingdom
				//'CH',	// Switzerland - No. Swizerland in not in EEC
		);

		if (in_array($country_code, $country_code_in_EEC)) return 1; // France, Spain, ...
		return 0;
	}

	/**
	 *	Load miscellaneous information for tab "Info"
	 *
	 *	@param  int		$id		Id of object to load
	 *	@return	void
	 */
    public function info($id)
	{
	}

	/**
	 * Returns the fields in order that this bank account should show to the user
	 * Will return an array with the following values:
	 * - BankAccountNumber
	 * - BankCode
	 * - BankAccountNumberKey
	 * - DeskCode
	 *
	 * Some countries show less or more bank account properties to the user
	 *
	 * @param  int     $includeibanbic         1=Return also key for IBAN and BIC
	 * @return array                           Array of fields to show
	 * @see useDetailedBBAN()
	 */
	public function getFieldsToShow($includeibanbic = 0)
	{
		//Get the required properties depending on the country
		$detailedBBAN = $this->useDetailedBBAN();

		if ($detailedBBAN == 0) {
			$fieldarray = array(
					'BankAccountNumber'
			);
		} elseif ($detailedBBAN == 2) {
			$fieldarray = array(
					'BankCode',
					'BankAccountNumber'
			);
		} else {
			$fieldarray = self::getAccountNumberOrder();
		}

		//if ($this->needIBAN()) {    // return always IBAN and BIC (this was old behaviour)
		if ($includeibanbic)
		{
			$fieldarray[] = 'IBAN';
			$fieldarray[] = 'BIC';
		}
		//}

		//Get the order the properties are shown
		return $fieldarray;
	}

	/**
	 * Returns the components of the bank account in order.
	 * Will return an array with the following values:
	 * - BankAccountNumber
	 * - BankCode
	 * - BankAccountNumberKey
	 * - DeskCode
	 *
	 * @return array
	 */
	public static function getAccountNumberOrder()
	{
		global $conf;

		$fieldlists = array(
				'BankCode',
				'DeskCode',
				'BankAccountNumber',
				'BankAccountNumberKey'
		);

		if (!empty($conf->global->BANK_SHOW_ORDER_OPTION)) {
			if (is_numeric($conf->global->BANK_SHOW_ORDER_OPTION)) {
				if ($conf->global->BANK_SHOW_ORDER_OPTION == '1') {
					$fieldlists = array(
						'BankCode',
						'DeskCode',
						'BankAccountNumberKey',
						'BankAccountNumber'
					);
				}
			} else {
				//Replace the old AccountNumber key with the new BankAccountNumber key
				$fieldlists = explode(
					' ',
                    preg_replace('/ ?[^Bank]AccountNumber ?/', 'BankAccountNumber', $conf->global->BANK_SHOW_ORDER_OPTION)
				);
			}
		}

		return $fieldlists;
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
		$this->ref             = 'MBA';
		$this->label           = 'My Big Company Bank account';
		$this->bank            = 'MyBank';
		$this->courant         = Account::TYPE_CURRENT;
		$this->clos            = Account::STATUS_OPEN;
		$this->code_banque     = '123';
		$this->code_guichet    = '456';
		$this->number          = 'ABC12345';
		$this->cle_rib         = '50';
		$this->bic             = 'AA12';
		$this->iban            = 'FR999999999';
		$this->domiciliation   = 'My bank address';
		$this->proprio         = 'Owner';
		$this->owner_address   = 'Owner address';
		$this->country_id      = 1;
	}
}


/**
 *	Class to manage bank transaction lines
 */
class AccountLine extends CommonObject
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'bank';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'bank';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'generic';

	/**
	 * @var int ID
	 */
	public $id;

    /**
     * @var string Ref
     */
    public $ref;

	/**
     * Date creation record (datec)
     *
     * @var integer
     */
    public $datec;

    /**
     * Date (dateo)
     *
     * @var integer
     */
	public $dateo;

	/**
     * Date value (datev)
     *
     * @var integer
     */
	public $datev;

	public $amount;

    /**
     * @var string bank transaction lines label
     */
    public $label;

    public $note;

    /**
     * @var int ID
     */
	public $fk_user_author;

	/**
     * @var int ID
     */
	public $fk_user_rappro;

	/**
     * @var int ID
     */
	public $fk_type;

	public $rappro; // Is it conciliated
	public $num_releve; // If conciliated, what is bank statement
	public $num_chq; // Num of cheque
	public $bank_chq; // Bank of cheque

	/**
     * @var int ID of cheque receipt
     */
	public $fk_bordereau;

	/**
     * @var int ID of bank account
     */
	public $fk_account;

	/**
	 * @var string		Label of bank account
	 */
	public $bank_account_label;

    /**
	 * @var string		Name of check issuer
	 */
	public $emetteur;

	/**
	 *  Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
    public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *  Load into memory content of a bank transaction line
	 *
	 *  @param		int		$rowid   	Id of bank transaction to load
	 *  @param      string	$ref     	Ref of bank transaction to load
	 *  @param      string	$num     	External num to load (ex: num of transaction for paypal fee)
	 *	@return		int					<0 if KO, 0 if OK but not found, >0 if OK and found
	 */
    public function fetch($rowid, $ref = '', $num = '')
	{
		global $conf;

		// Check parameters
		if (empty($rowid) && empty($ref) && empty($num)) return -1;

		$sql = "SELECT b.rowid, b.datec, b.datev, b.dateo, b.amount, b.label as label, b.fk_account,";
		$sql .= " b.fk_user_author, b.fk_user_rappro,";
		$sql .= " b.fk_type, b.num_releve, b.num_chq, b.rappro, b.note,";
		$sql .= " b.fk_bordereau, b.banque, b.emetteur,";
		//$sql.= " b.author"; // Is this used ?
		$sql .= " ba.ref as bank_account_ref, ba.label as bank_account_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."bank as b,";
		$sql .= " ".MAIN_DB_PREFIX."bank_account as ba";
		$sql .= " WHERE b.fk_account = ba.rowid";
		$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
		if ($num) $sql .= " AND b.num_chq='".$this->db->escape($num)."'";
		elseif ($ref) $sql .= " AND b.rowid='".$this->db->escape($ref)."'";
		else $sql .= " AND b.rowid=".$rowid;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$ret = 0;

			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id = $obj->rowid;
				$this->rowid			= $obj->rowid;
				$this->ref = $obj->rowid;

				$this->datec			= $obj->datec;
				$this->datev			= $obj->datev;
				$this->dateo			= $obj->dateo;
				$this->amount = $obj->amount;
				$this->label			= $obj->label;
				$this->note				= $obj->note;

				$this->fk_user_author	= $obj->fk_user_author;
				$this->fk_user_rappro	= $obj->fk_user_rappro;

				$this->fk_type = $obj->fk_type; // Type of transaction
				$this->rappro = $obj->rappro;
				$this->num_releve = $obj->num_releve;

				$this->num_chq = $obj->num_chq;
				$this->bank_chq = $obj->banque;
				$this->fk_bordereau = $obj->fk_bordereau;

				$this->fk_account = $obj->fk_account;
				$this->bank_account_ref   = $obj->bank_account_ref;
				$this->bank_account_label = $obj->bank_account_label;

				$ret = 1;
			}
			$this->db->free($result);
			return $ret;
		}
		else
		{
			return -1;
		}
	}

	/**
	 * Inserts a transaction to a bank account
	 *
	 * @return int <0 if KO, rowid of the line if OK
	 */
	public function insert()
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank (";
		$sql .= "datec";
		$sql .= ", dateo";
		$sql .= ", datev";
		$sql .= ", label";
		$sql .= ", amount";
		$sql .= ", fk_user_author";
		$sql .= ", num_chq";
		$sql .= ", fk_account";
		$sql .= ", fk_type";
		$sql .= ", emetteur,banque";
		$sql .= ", rappro";
		$sql .= ", numero_compte";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->idate($this->datec)."'";
		$sql .= ", '".$this->db->idate($this->dateo)."'";
		$sql .= ", '".$this->db->idate($this->datev)."'";
		$sql .= ", '".$this->db->escape($this->label)."'";
		$sql .= ", ".price2num($this->amount);
		$sql .= ", ".($this->fk_user_author > 0 ? $this->fk_user_author : "null");
		$sql .= ", ".($this->num_chq ? "'".$this->db->escape($this->num_chq)."'" : "null");
		$sql .= ", '".$this->db->escape($this->fk_account)."'";
		$sql .= ", '".$this->db->escape($this->fk_type)."'";
		$sql .= ", ".($this->emetteur ? "'".$this->db->escape($this->emetteur)."'" : "null");
		$sql .= ", ".($this->bank_chq ? "'".$this->db->escape($this->bank_chq)."'" : "null");
		$sql .= ", ".(int) $this->rappro;
		$sql .= ", ".($this->numero_compte ? "'".$this->db->escape($this->numero_compte)."'" : "''");
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'bank');

		return $this->id;
	}

	/**
	 *      Delete bank transaction record
	 *
	 *		@param	User	$user	User object that delete
	 *      @return	int 			<0 if KO, >0 if OK
	 */
    public function delete(User $user = null)
	{
    	global $conf;

		$nbko = 0;

		if ($this->rappro)
		{
			// Protection to avoid any delete of consolidated lines
			$this->error = "ErrorDeleteNotPossibleLineIsConsolidated";
			return -1;
		}

		$this->db->begin();

		// Protection to avoid any delete of accounted lines. Protection on by default
		if (empty($conf->global->BANK_ALLOW_TRANSACTION_DELETION_EVEN_IF_IN_ACCOUNTING))
		{
			$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE doc_type = 'bank' AND fk_doc = ".$this->id;
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj && $obj->nb) {
					$this->error = 'ErrorRecordAlreadyInAccountingDeletionNotPossible';
					$this->db->rollback();
					return -1;
				}
			}
			else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		}

		// Delete urls
		$result = $this->delete_urls($user);
		if ($result < 0)
		{
			$nbko++;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid=".(int) $this->rowid;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (!$result) $nbko++;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=".(int) $this->rowid;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (!$result) $nbko++;

		if (!$nbko)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -$nbko;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Delete bank line records
	 *
	 *		@param	User	$user	User object that delete
	 *      @return	int 			<0 if KO, >0 if OK
	 */
    public function delete_urls(User $user = null)
	{
        // phpcs:enable
		$nbko = 0;

		if ($this->rappro)
		{
			// Protection to avoid any delete of consolidated lines
			$this->error = "ErrorDeleteNotPossibleLineIsConsolidated";
			return -1;
		}

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_url WHERE fk_bank=".(int) $this->rowid;
		dol_syslog(get_class($this)."::delete_urls", LOG_DEBUG);
		$result = $this->db->query($sql);
		if (!$result) $nbko++;

		if (!$nbko)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -$nbko;
		}
	}


	/**
	 *		Update bank account record in database
	 *
	 *		@param	User	$user			Object user making update
	 *		@param 	int		$notrigger		0=Disable all triggers
	 *		@return	int						<0 if KO, >0 if OK
	 */
    public function update(User $user, $notrigger = 0)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
		$sql .= " amount = ".price2num($this->amount).",";
		$sql .= " datev='".$this->db->idate($this->datev)."',";
		$sql .= " dateo='".$this->db->idate($this->dateo)."'";
		$sql .= " WHERE rowid = ".$this->rowid;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error = $this->db->error();
			return -1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update conciliation field
	 *
	 *	@param	User	$user			Objet user making update
	 *	@param 	int		$cat			Category id
	 *	@param	int		$conciliated	1=Set transaction to conciliated, 0=Keep transaction non conciliated
	 *	@return	int						<0 if KO, >0 if OK
	 */
    public function update_conciliation(User $user, $cat, $conciliated = 1)
	{
        // phpcs:enable
		global $conf, $langs;

		$this->db->begin();

		// Check statement field
		if (!empty($conf->global->BANK_STATEMENT_REGEX_RULE))
		{
			if (!preg_match('/'.$conf->global->BANK_STATEMENT_REGEX_RULE.'/', $this->num_releve))
			{
				$this->errors[] = $langs->trans("ErrorBankStatementNameMustFollowRegex", $conf->global->BANK_STATEMENT_REGEX_RULE);
				return -1;
			}
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
		$sql .= " rappro = ".$conciliated;
		$sql .= ", num_releve = '".$this->db->escape($this->num_releve)."'";
		if ($conciliated) $sql .= ", fk_user_rappro = ".$user->id;
		$sql .= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update_conciliation", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			if (!empty($cat))
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (";
				$sql .= "lineid";
				$sql .= ", fk_categ";
				$sql .= ") VALUES (";
				$sql .= $this->id;
				$sql .= ", ".$cat;
				$sql .= ")";

				dol_syslog(get_class($this)."::update_conciliation", LOG_DEBUG);
				$this->db->query($sql);

				// No error check. Can fail if category already affected
			}

			$this->rappro = 1;

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Increase/decrease value date of a rowid
	 *
	 *	@param	int		$rowid		Id of line
	 *	@param	int		$sign		1 or -1
	 *	@return	int					>0 if OK, 0 if KO
	 */
    public function datev_change($rowid, $sign = 1)
	{
        // phpcs:enable
		$sql = "SELECT datev FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$newdate = $this->db->jdate($obj->datev) + (3600 * 24 * $sign);

			$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
			$sql .= " datev = '".$this->db->idate($newdate)."'";
			$sql .= " WHERE rowid = ".$rowid;

			$result = $this->db->query($sql);
			if ($result)
			{
				if ($this->db->affected_rows($result))
				{
					return 1;
				}
			}
			else
			{
				dol_print_error($this->db);
				return 0;
			}
		}
		else dol_print_error($this->db);
		return 0;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Increase value date of a rowid
	 *
	 *	@param	int		$id		Id of line to change
	 *	@return	int				>0 if OK, 0 if KO
	 */
    public function datev_next($id)
	{
        // phpcs:enable
		return $this->datev_change($id, 1);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Decrease value date of a rowid
	 *
	 *	@param	int		$id		Id of line to change
	 *	@return	int				>0 if OK, 0 if KO
	 */
    public function datev_previous($id)
	{
        // phpcs:enable
		return $this->datev_change($id, -1);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Increase/decrease operation date of a rowid
	 *
	 *	@param	int		$rowid		Id of line
	 *	@param	int		$sign		1 or -1
	 *	@return	int					>0 if OK, 0 if KO
	 */
    public function dateo_change($rowid, $sign = 1)
	{
        // phpcs:enable
		$sql = "SELECT dateo FROM ".MAIN_DB_PREFIX."bank WHERE rowid = ".$rowid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$newdate = $this->db->jdate($obj->dateo) + (3600 * 24 * $sign);

			$sql = "UPDATE ".MAIN_DB_PREFIX."bank SET";
			$sql .= " dateo = '".$this->db->idate($newdate)."'";
			$sql .= " WHERE rowid = ".$rowid;

			$result = $this->db->query($sql);
			if ($result)
			{
				if ($this->db->affected_rows($result))
				{
					return 1;
				}
			}
			else
			{
				dol_print_error($this->db);
				return 0;
			}
		}
		else dol_print_error($this->db);
		return 0;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Increase operation date of a rowid
	 *
	 *	@param	int		$id		Id of line to change
	 *	@return	int				>0 if OK, 0 if KO
	 */
    public function dateo_next($id)
	{
        // phpcs:enable
		return $this->dateo_change($id, 1);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Decrease operation date of a rowid
	 *
	 *	@param	int		$id		Id of line to change
	 *	@return	int				>0 if OK, 0 if KO
	 */
    public function dateo_previous($id)
	{
        // phpcs:enable
		return $this->dateo_change($id, -1);
	}


	/**
	 *	Load miscellaneous information for tab "Info"
	 *
	 *	@param  int		$id		Id of object to load
	 *	@return	void
	 */
    public function info($id)
	{
		$sql = 'SELECT b.rowid, b.datec, b.tms as datem,';
		$sql .= ' b.fk_user_author, b.fk_user_rappro';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'bank as b';
		$sql .= ' WHERE b.rowid = '.$id;

		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;

				if ($obj->fk_user_author)
				{
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}
				if ($obj->fk_user_rappro)
				{
					$ruser = new User($this->db);
					$ruser->fetch($obj->fk_user_rappro);
					$this->user_rappro = $ruser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				//$this->date_rappro       = $obj->daterappro;    // Not yet managed
			}
			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    	Return clickable name (with picto eventually)
	 *
	 *		@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
	 *		@param	int		$maxlen			Longueur max libelle
	 *		@param	string	$option			Option ('', 'showall', 'showconciliated', 'showconciliatedandaccounted'). Options may be slow.
	 * 		@param	int     $notooltip		1=Disable tooltip
	 *		@return	string					Chaine avec URL
	 */
    public function getNomUrl($withpicto = 0, $maxlen = 0, $option = '', $notooltip = 0)
	{
		global $langs;

		$result = '';
		$label = $langs->trans("ShowTransaction").': '.$this->rowid;
		$linkstart = '<a href="'.DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$this->rowid.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'account'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= ($this->ref ? $this->ref : $this->rowid);
		$result .= $linkend;

		if ($option == 'showall' || $option == 'showconciliated' || $option == 'showconciliatedandaccounted') $result .= ' <span class="opacitymedium">(';
		if ($option == 'showall')
		{
			$result .= $langs->trans("BankAccount").': ';
			$accountstatic = new Account($this->db);
			$accountstatic->id = $this->fk_account;
			$accountstatic->ref = $this->bank_account_ref;
			$accountstatic->label = $this->bank_account_label;
			$result .= $accountstatic->getNomUrl(0).', ';
		}
		if ($option == 'showall' || $option == 'showconciliated' || $option == 'showconciliatedandaccounted')
		{
			$result .= $langs->trans("BankLineConciliated").': ';
			$result .= yn($this->rappro);
		}
		if ($option == 'showall' || $option == 'showconciliatedandaccounted')
		{
			$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE doc_type = 'bank' AND fk_doc = ".$this->id;
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj && $obj->nb) {
					$result .= ' - '.$langs->trans("Accounted").': '.yn(1);
				} else {
					$result .= ' - '.$langs->trans("Accounted").': '.yn(0);
				}
			}
		}
		if ($option == 'showall' || $option == 'showconciliated' || $option == 'showconciliatedandaccounted') $result .= ')</span>';

		return $result;
	}


	/**
	 *    Return label of status (activity, closed)
	 *
	 *    @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *    @return   string        		Libelle
	 */
    public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status         Id statut
	 *  @param	int		$mode           0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string          		Libelle du statut
	 */
    public function LibStatut($status, $mode = 0)
	{
        // phpcs:enable
		global $langs;
		//$langs->load('companies');
		/*
        if ($mode == 0)
        {
            if ($status==0) return $langs->trans("ActivityCeased");
            if ($status==1) return $langs->trans("InActivity");
        }
        if ($mode == 1)
        {
            if ($status==0) return $langs->trans("ActivityCeased");
            if ($status==1) return $langs->trans("InActivity");
        }
        if ($mode == 2)
        {
            if ($status==0) return img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"').' '.$langs->trans("ActivityCeased");
            if ($status==1) return img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"').' '.$langs->trans("InActivity");
        }
        if ($mode == 3)
        {
            if ($status==0) return img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"');
            if ($status==1) return img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"');
        }
        if ($mode == 4)
        {
            if ($status==0) return img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"').' '.$langs->trans("ActivityCeased");
            if ($status==1) return img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"').' '.$langs->trans("InActivity");
        }
        if ($mode == 5)
        {
            if ($status==0) return $langs->trans("ActivityCeased").' '.img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"');
            if ($status==1) return $langs->trans("InActivity").' '.img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"');
        }*/
	}


	/**
	 *	Return if a bank line was dispatched into bookkeeping
	 *
	 *	@return     int         <0 if KO, 0=no, 1=yes
	 */
	public function getVentilExportCompta()
	{
		$alreadydispatched = 0;

		$type = 'bank';

		$sql = " SELECT COUNT(ab.rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab WHERE ab.doc_type='".$type."' AND ab.fk_doc = ".$this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$alreadydispatched = $obj->nb;
			}
		}
		else
		{
			$this->error = $this->db->lasterror();
			return -1;
		}

		if ($alreadydispatched)
		{
			return 1;
		}
		return 0;
	}
}
