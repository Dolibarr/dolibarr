<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2010-2020 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Jean Heimburger      <jean@tiaris.info>
 * Copyright (C) 2012-2014 Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Nicolas ZABOURI	    <info@inovea-conseil.com>
 * Copyright (C) 2016-2022 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2021-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/commande/class/commande.class.php
 *  \ingroup    order
 *  \brief      class for orders
 */

include_once DOL_DOCUMENT_ROOT.'/core/class/commonorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


/**
 *  Class to manage customers orders
 */
class Commande extends CommonOrder
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'commande';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'commande';

	/**
	 * @var string Name of subtable line
	 */
	public $table_element_line = 'commandedet';

	/**
	 * @var string Name of class line
	 */
	public $class_element_line = 'OrderLine';

	/**
	 * @var string Field name with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_commande';

	/**
	 * @var string String with name of icon for commande class. Here is object_order.png
	 */
	public $picto = 'order';

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * @var int Thirdparty ID
	 */
	public $socid;

	/**
	 * @var string Thirdparty ref of order
	 */
	public $ref_client;

	/**
	 * @var string Thirdparty ref of order
	 */
	public $ref_customer;

	/**
	 * @var int Contact ID
	 */
	public $contactid;

	/**
	 * Status of the order
	 * @var int
	 */
	public $statut;

	/**
	 * @var int Status Billed or not
	 */
	public $billed;

	/**
	 * @var int Deadline for payment
	 */
	public $date_lim_reglement;
	/**
	 * @var string Condition payment code
	 */
	public $cond_reglement_code;

	/**
	 * @var string Condition payment label
	 */
	public $cond_reglement_doc;

	/**
	 * @var float 	Deposit percent for payment terms.
	 *				Populated by $CommonObject->setPaymentTerms().
	 * @see setPaymentTerms()
	 */
	public $deposit_percent;

	/**
	 * @var int bank account ID
	 */
	public $fk_account;

	/**
	 * @var string It holds the label of the payment mode. Use it in case translation cannot be found.
	 */
	public $mode_reglement;

	/**
	 * @var int Payment mode id
	 */
	public $mode_reglement_id;

	/**
	 * @var string Payment mode code
	 */
	public $mode_reglement_code;

	/**
	 * Availability delivery time id
	 * @var int
	 */
	public $availability_id;

	/**
	 * Availability delivery time code
	 * @var string
	 */
	public $availability_code;

	/**
	 * Label of availability delivery time. Use it in case translation cannot be found.
	 * @var string
	 */
	public $availability;

	/**
	 * @var int Source demand reason Id
	 */
	public $demand_reason_id;

	/**
	 * @var string Source reason code. Why we receive order (after a phone campaign, ...)
	 */
	public $demand_reason_code;

	/**
	 * @var null|int|'' Date of order
	 */
	public $date;

	/**
	 * @var null|int|'' Date of order
	 * @deprecated
	 * @see $date
	 */
	public $date_commande;

	/**
	 * @var null|int|''	Date expected of shipment (date of start of shipment, not the reception that occurs some days after)
	 */
	public $delivery_date;

	/**
	 * @var int ID
	 */
	public $fk_remise_except;

	/**
	 * @deprecated
	 */
	public $remise_percent;

	public $source; // Order mode. How we received order (by phone, by email, ...)

	/**
	 * Status of the contract (0=NoSignature, 1=SignedBySender, 2=SignedByReceiver, 9=SignedByAll)
	 * @var int
	 */
	public $signed_status = 0;

	/**
	 * @var int Warehouse Id
	 */
	public $warehouse_id;

	public $extraparams = array();

	public $linked_objects = array();

	/**
	 * @var int User author ID
	 */
	public $user_author_id;

	/**
	 * @var OrderLine one line of an order
	 */
	public $line;

	/**
	 * @var OrderLine[]
	 */
	public $lines = array();


	//! key of module source when order generated from a dedicated module ('cashdesk', 'takepos', ...)
	public $module_source;
	//! key of pos source ('0', '1', ...)
	public $pos_source;

	/**
	 * @var array	Array with line of all shipments
	 */
	public $expeditions;

	/**
	 * @var string payment url
	 */
	public $online_payment_url;



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
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int,string>,comment?:string,validate?:int<0,1>}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 10),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 20, 'index' => 1),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 25),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'RefExt', 'enabled' => 1, 'visible' => 0, 'position' => 26),
		'ref_client' => array('type' => 'varchar(255)', 'label' => 'RefCustomer', 'enabled' => 1, 'visible' => -1, 'position' => 28),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => -1, 'notnull' => 1, 'position' => 20),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => "isModEnabled('project')", 'visible' => -1, 'position' => 25),
		'date_commande' => array('type' => 'date', 'label' => 'Date', 'enabled' => 1, 'visible' => 1, 'position' => 60, 'csslist' => 'nowraponall'),
		'date_valid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 62, 'csslist' => 'nowraponall'),
		'date_cloture' => array('type' => 'datetime', 'label' => 'DateClosing', 'enabled' => 1, 'visible' => -1, 'position' => 65, 'csslist' => 'nowraponall'),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'fk_user_cloture' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserClosing', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'source' => array('type' => 'smallint(6)', 'label' => 'Source', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'total_tva' => array('type' => 'double(24,8)', 'label' => 'VAT', 'enabled' => 1, 'visible' => -1, 'position' => 125, 'isameasure' => 1),
		'localtax1' => array('type' => 'double(24,8)', 'label' => 'LocalTax1', 'enabled' => 1, 'visible' => -1, 'position' => 130, 'isameasure' => 1),
		'localtax2' => array('type' => 'double(24,8)', 'label' => 'LocalTax2', 'enabled' => 1, 'visible' => -1, 'position' => 135, 'isameasure' => 1),
		'total_ht' => array('type' => 'double(24,8)', 'label' => 'TotalHT', 'enabled' => 1, 'visible' => -1, 'position' => 140, 'isameasure' => 1),
		'total_ttc' => array('type' => 'double(24,8)', 'label' => 'TotalTTC', 'enabled' => 1, 'visible' => -1, 'position' => 145, 'isameasure' => 1),
		'signed_status' => array('type' => 'smallint(6)', 'label' => 'SignedStatus', 'enabled' => 1, 'visible' => -1, 'position' => 146, 'arrayofkeyval' => array(0 => 'NoSignature', 1 => 'SignedSender', 2 => 'SignedReceiver', 9 => 'SignedAll')),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 150),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 155),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'PDFTemplate', 'enabled' => 1, 'visible' => 0, 'position' => 160),
		'fk_account' => array('type' => 'integer', 'label' => 'BankAccount', 'enabled' => 'isModEnabled("bank")', 'visible' => -1, 'position' => 170),
		'fk_currency' => array('type' => 'varchar(3)', 'label' => 'MulticurrencyID', 'enabled' => 1, 'visible' => -1, 'position' => 175),
		'fk_cond_reglement' => array('type' => 'integer', 'label' => 'PaymentTerm', 'enabled' => 1, 'visible' => -1, 'position' => 180),
		'deposit_percent' => array('type' => 'varchar(63)', 'label' => 'DepositPercent', 'enabled' => 1, 'visible' => -1, 'position' => 181),
		'fk_mode_reglement' => array('type' => 'integer', 'label' => 'PaymentMode', 'enabled' => 1, 'visible' => -1, 'position' => 185),
		'date_livraison' => array('type' => 'date', 'label' => 'DateDeliveryPlanned', 'enabled' => 1, 'visible' => -1, 'position' => 190, 'csslist' => 'nowraponall'),
		'fk_shipping_method' => array('type' => 'integer', 'label' => 'ShippingMethod', 'enabled' => 1, 'visible' => -1, 'position' => 195),
		'fk_warehouse' => array('type' => 'integer:Entrepot:product/stock/class/entrepot.class.php', 'label' => 'Fk warehouse', 'enabled' => 'isModEnabled("stock")', 'visible' => -1, 'position' => 200),
		'fk_availability' => array('type' => 'integer', 'label' => 'Availability', 'enabled' => 1, 'visible' => -1, 'position' => 205),
		'fk_input_reason' => array('type' => 'integer', 'label' => 'InputReason', 'enabled' => 1, 'visible' => -1, 'position' => 210),
		//'fk_delivery_address' =>array('type'=>'integer', 'label'=>'DeliveryAddress', 'enabled'=>1, 'visible'=>-1, 'position'=>215),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 225),
		'fk_incoterms' => array('type' => 'integer', 'label' => 'IncotermCode', 'enabled' => '$conf->incoterm->enabled', 'visible' => -1, 'position' => 230),
		'location_incoterms' => array('type' => 'varchar(255)', 'label' => 'IncotermLabel', 'enabled' => '$conf->incoterm->enabled', 'visible' => -1, 'position' => 235),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'Fk multicurrency', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 240),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'MulticurrencyCurrency', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 245),
		'multicurrency_tx' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyRate', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 250, 'isameasure' => 1),
		'multicurrency_total_ht' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountHT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 255, 'isameasure' => 1),
		'multicurrency_total_tva' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountVAT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 260, 'isameasure' => 1),
		'multicurrency_total_ttc' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountTTC', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 265, 'isameasure' => 1),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => -1, 'position' => 270),
		'module_source' => array('type' => 'varchar(32)', 'label' => 'POSModule', 'enabled' => 1, 'visible' => -1, 'position' => 275),
		'pos_source' => array('type' => 'varchar(32)', 'label' => 'POSTerminal', 'enabled' => 1, 'visible' => -1, 'position' => 280),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -1, 'position' => 300),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 302),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 304, 'csslist' => 'nowraponall'),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 306),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 400),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => -1, 'position' => 500),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * ERR Not enough stock
	 */
	const STOCK_NOT_ENOUGH_FOR_ORDER = -3;

	/**
	 * Canceled status
	 */
	const STATUS_CANCELED = -1;
	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Shipment on process
	 */
	const STATUS_SHIPMENTONPROCESS = 2;		// We set this status when a shipment is validated

	/**
	 * For backward compatibility. Use key STATUS_SHIPMENTONPROCESS instead.
	 * @deprecated
	 */
	const STATUS_ACCEPTED = 2;

	/**
	 * Closed (Sent, billed or not)
	 */
	const STATUS_CLOSED = 3;

	/*
	 * No signature
	 */
	const STATUS_NO_SIGNATURE    = 0;

	/*
	 * Signed by sender
	 */
	const STATUS_SIGNED_SENDER   = 1;

	/*
	 * Signed by receiver
	 */
	const STATUS_SIGNED_RECEIVER = 2;

	/*
	 * Signed by all
	 */
	const STATUS_SIGNED_ALL      = 9; // To handle future kind of signature (ex: tripartite contract)


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
	}

	/**
	 *  Returns the reference to the following non used Order depending on the active numbering module
	 *  defined into COMMANDE_ADDON
	 *
	 *  @param	Societe		$soc  	Object thirdparty
	 *  @return string      		Order free reference
	 */
	public function getNextNumRef($soc)
	{
		global $langs, $conf;
		$langs->load("order");

		if (getDolGlobalString('COMMANDE_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('COMMANDE_ADDON') . ".php";
			$classname = getDolGlobalString('COMMANDE_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/commande/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_ADDON_NotDefined");
			return "";
		}
	}


	/**
	 *	Validate order
	 *
	 *	@param		User	$user     		User making status change
	 *	@param		int		$idwarehouse	Id of warehouse to use for stock decrease
	 *  @param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return  	int						Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function valid($user, $idwarehouse = 0, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->statut == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::valid action abandoned: already validated", LOG_WARNING);
			return 0;
		}

		if (!((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'creer'))
			|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'order_advance', 'validate')))) {
			$this->error = 'NotEnoughPermissions';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}

		$now = dol_now();

		$this->db->begin();

		// Definition du nom de module de numerotation de commande
		$soc = new Societe($this->db);
		$soc->fetch($this->socid);

		// Class of company linked to order
		$result = $soc->setAsCustomer();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef($soc);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		// Validate
		$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
		$sql .= " SET ref = '".$this->db->escape($num)."',";
		$sql .= " fk_statut = ".self::STATUS_VALIDATED.",";
		$sql .= " date_valid='".$this->db->idate($now)."',";
		$sql .= " fk_user_valid = ".($user->id > 0 ? (int) $user->id : "null").",";
		$sql .= " fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::valid", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error) {
			// If stock is incremented on validate order, we must increment it
			if ($result >= 0 && isModEnabled('stock') && getDolGlobalInt('STOCK_CALCULATE_ON_VALIDATE_ORDER') == 1) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				// Loop on each line
				$cpt = count($this->lines);
				for ($i = 0; $i < $cpt; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						$mouvP->setOrigin($this->element, $this->id);
						// We decrement stock of product (and sub-products)
						$result = $mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("OrderValidatedInDolibarr", $num));
						if ($result < 0) {
							$error++;
							$this->error = $mouvP->error;
						}
					}
					if ($error) {
						break;
					}
				}
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('ORDER_VALIDATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'commande/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'commande/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'commande/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'commande/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->commande->multidir_output[$this->entity].'/'.$oldref;
				$dirdest = $conf->commande->multidir_output[$this->entity].'/'.$newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->commande->multidir_output[$this->entity].'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
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
		if (!$error) {
			$this->ref = $num;
			$this->statut = self::STATUS_VALIDATED;	// deprecated
			$this->status = self::STATUS_VALIDATED;
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
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *	@param	int		$idwarehouse	Warehouse ID to use for stock change (Used only if option STOCK_CALCULATE_ON_VALIDATE_ORDER is on)
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $idwarehouse = -1)
	{
		//phpcs:enable
		global $conf, $langs;

		$error = 0;

		// Protection
		if ($this->statut <= self::STATUS_DRAFT && !getDolGlobalInt('ORDER_REOPEN_TO_DRAFT')) {
			return 0;
		}

		if (!((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'creer'))
			|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'order_advance', 'validate')))) {
			$this->error = 'Permission denied';
			return -1;
		}

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT.",";
		$sql .= " fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
			}

			// If stock is decremented on validate order, we must reincrement it
			if (isModEnabled('stock') && getDolGlobalInt('STOCK_CALCULATE_ON_VALIDATE_ORDER') == 1) {
				$result = 0;

				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						$mouvP->setOrigin($this->element, $this->id);
						// We increment stock of product (and sub-products)
						$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("OrderBackToDraftInDolibarr", $this->ref));
						if ($result < 0) {
							$error++;
							$this->error = $mouvP->error;
							break;
						}
					}
				}
			}

			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('ORDER_UNVALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$this->statut = self::STATUS_DRAFT;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Tag the order as validated (opened)
	 *	Function used when order is reopend after being closed.
	 *
	 *	@param      User	$user       Object user that change status
	 *	@return     int         		Return integer <0 if KO, 0 if nothing is done, >0 if OK
	 */
	public function set_reopen($user)
	{
		// phpcs:enable
		$error = 0;

		if ($this->statut != self::STATUS_CANCELED && $this->statut != self::STATUS_CLOSED) {
			dol_syslog(get_class($this)."::set_reopen order has not status closed", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
		$sql .= ' SET fk_statut='.self::STATUS_VALIDATED.', facture=0,';
		$sql .= " fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::set_reopen", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			$result = $this->call_trigger('ORDER_REOPEN', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		} else {
			$error++;
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
		}

		if (!$error) {
			$this->statut = self::STATUS_VALIDATED;
			$this->billed = 0;

			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::set_reopen ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
	}

	/**
	 *  Close order
	 *
	 * 	@param      User	$user       Object user that close
	 *  @param		int		$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function cloture($user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		$usercanclose = ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'creer'))
			|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('commande', 'order_advance', 'close')));

		if ($usercanclose) {
			if ($this->statut == self::STATUS_CLOSED) {
				return 0;
			}
			$this->db->begin();

			$now = dol_now();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' SET fk_statut = '.self::STATUS_CLOSED.',';
			$sql .= ' fk_user_cloture = '.((int) $user->id).',';
			$sql .= " date_cloture = '".$this->db->idate($now)."',";
			$sql .= " fk_user_modif = ".((int) $user->id);
			$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > '.self::STATUS_DRAFT;

			if ($this->db->query($sql)) {
				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('ORDER_CLOSE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					$this->statut = self::STATUS_CLOSED;

					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->db->lasterror();

				$this->db->rollback();
				return -1;
			}
		}
		return 0;
	}

	/**
	 * 	Cancel an order
	 * 	If stock is decremented on order validation, we must reincrement it
	 *
	 *	@param	int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function cancel($idwarehouse = -1)
	{
		global $conf, $user, $langs;

		$error = 0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
		$sql .= " SET fk_statut = ".self::STATUS_CANCELED.",";
		$sql .= " fk_user_modif = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);
		$sql .= " AND fk_statut = ".self::STATUS_VALIDATED;

		dol_syslog(get_class($this)."::cancel", LOG_DEBUG);
		if ($this->db->query($sql)) {
			// If stock is decremented on validate order, we must reincrement it
			if (isModEnabled('stock') && getDolGlobalInt('STOCK_CALCULATE_ON_VALIDATE_ORDER') == 1) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->setOrigin($this->element, $this->id);
						// We increment stock of product (and sub-products)
						$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("OrderCanceledInDolibarr", $this->ref)); // price is 0, we don't want WAP to be changed
						if ($result < 0) {
							$error++;
							$this->error = $mouvP->error;
							break;
						}
					}
				}
			}

			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('ORDER_CANCEL', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->statut = self::STATUS_CANCELED;
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::cancel ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Create order
	 *	Note that this->ref can be set or empty. If empty, we will use "(PROV)"
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int	    $notrigger	Disable all triggers
	 *	@return 	int			        Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs, $mysoc;
		$error = 0;

		// Clean parameters

		// Set tmp vars
		$date = ($this->date_commande ? $this->date_commande : $this->date);
		$delivery_date = $this->delivery_date;

		// Multicurrency (test on $this->multicurrency_tx because we should take the default rate only if not using origin rate)
		if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) {
			list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code, $date);
		} else {
			$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

		dol_syslog(get_class($this)."::create user=".$user->id);

		// Check parameters
		if (!empty($this->ref)) {	// We check that ref is not already used
			$result = self::isExistingObject($this->element, 0, $this->ref); // Check ref is not yet used
			if ($result > 0) {
				$this->error = 'ErrorRefAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}

		$soc = new Societe($this->db);
		$result = $soc->fetch($this->socid);
		if ($result < 0) {
			$this->error = "Failed to fetch company";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -2;
		}
		if (getDolGlobalString('ORDER_REQUIRE_SOURCE') && $this->source < 0) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Source"));
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."commande (";
		$sql .= " ref, fk_soc, date_creation, fk_user_author, fk_projet, date_commande, source, note_private, note_public, ref_ext, ref_client";
		$sql .= ", model_pdf, fk_cond_reglement, deposit_percent, fk_mode_reglement, fk_account, fk_availability, fk_input_reason, date_livraison, fk_delivery_address";
		$sql .= ", fk_shipping_method";
		$sql .= ", fk_warehouse";
		$sql .= ", fk_incoterms, location_incoterms";
		$sql .= ", entity, module_source, pos_source";
		$sql .= ", fk_multicurrency";
		$sql .= ", multicurrency_code";
		$sql .= ", multicurrency_tx";
		$sql .= ")";
		$sql .= " VALUES ('(PROV)', ".((int) $this->socid).", '".$this->db->idate($now)."', ".((int) $user->id);
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
		$sql .= ", '".$this->db->idate($date)."'";
		$sql .= ", ".($this->source >= 0 && $this->source != '' ? $this->db->escape($this->source) : 'null');
		$sql .= ", '".$this->db->escape($this->note_private)."'";
		$sql .= ", '".$this->db->escape($this->note_public)."'";
		$sql .= ", ".($this->ref_ext ? "'".$this->db->escape($this->ref_ext)."'" : "null");
		$sql .= ", ".($this->ref_client ? "'".$this->db->escape($this->ref_client)."'" : "null");
		$sql .= ", '".$this->db->escape($this->model_pdf)."'";
		$sql .= ", ".($this->cond_reglement_id > 0 ? ((int) $this->cond_reglement_id) : "null");
		$sql .= ", ".(!empty($this->deposit_percent) ? "'".$this->db->escape($this->deposit_percent)."'" : "null");
		$sql .= ", ".($this->mode_reglement_id > 0 ? ((int) $this->mode_reglement_id) : "null");
		$sql .= ", ".($this->fk_account > 0 ? ((int) $this->fk_account) : 'NULL');
		$sql .= ", ".($this->availability_id > 0 ? ((int) $this->availability_id) : "null");
		$sql .= ", ".($this->demand_reason_id > 0 ? ((int) $this->demand_reason_id) : "null");
		$sql .= ", ".($delivery_date ? "'".$this->db->idate($delivery_date)."'" : "null");
		$sql .= ", ".($this->fk_delivery_address > 0 ? ((int) $this->fk_delivery_address) : 'NULL');
		$sql .= ", ".(!empty($this->shipping_method_id) && $this->shipping_method_id > 0 ? ((int) $this->shipping_method_id) : 'NULL');
		$sql .= ", ".(!empty($this->warehouse_id) && $this->warehouse_id > 0 ? ((int) $this->warehouse_id) : 'NULL');
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ", ".setEntity($this);
		$sql .= ", ".($this->module_source ? "'".$this->db->escape($this->module_source)."'" : "null");
		$sql .= ", ".($this->pos_source != '' ? "'".$this->db->escape($this->pos_source)."'" : "null");
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".(float) $this->multicurrency_tx;
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commande');

			if ($this->id) {
				$fk_parent_line = 0;
				$num = count($this->lines);

				/*
				 *  Insert products details into db
				 */
				for ($i = 0; $i < $num; $i++) {
					$line = $this->lines[$i];

					// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
					//if (! is_object($line)) $line=json_decode(json_encode($line), false);  // convert recursively array into object.
					if (!is_object($line)) {
						$line = (object) $line;
					}

					// Reset fk_parent_line for no child products and special product
					if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
						$fk_parent_line = 0;
					}

					// Complete vat rate with code
					$vatrate = $line->tva_tx;
					if ($line->vat_src_code && !preg_match('/\(.*\)/', (string) $vatrate)) {
						$vatrate .= ' ('.$line->vat_src_code.')';
					}

					if (getDolGlobalString('MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION')) {
						$originid = $line->origin_id;
						$origintype = $line->origin;
					} else {
						$originid = $line->id;
						$origintype = $this->element;
					}

					// ref_ext
					if (empty($line->ref_ext)) {
						$line->ref_ext = '';
					}

					$result = $this->addline(
						$line->desc,
						$line->subprice,
						$line->qty,
						$vatrate,
						$line->localtax1_tx,
						$line->localtax2_tx,
						$line->fk_product,
						$line->remise_percent,
						$line->info_bits,
						$line->fk_remise_except,
						'HT',
						0,
						$line->date_start,
						$line->date_end,
						$line->product_type,
						$line->rang,
						$line->special_code,
						$fk_parent_line,
						$line->fk_fournprice,
						$line->pa_ht,
						$line->label,
						$line->array_options,
						$line->fk_unit,
						$origintype,
						$originid,
						0,
						$line->ref_ext,
						1
					);
					if ($result < 0) {
						if ($result != self::STOCK_NOT_ENOUGH_FOR_ORDER) {
							$this->error = $this->db->lasterror();
							$this->errors[] = $this->error;
							dol_print_error($this->db);
						}
						$this->db->rollback();
						return -1;
					}
					// Defined the new fk_parent_line
					if ($result > 0 && $line->product_type == 9) {
						$fk_parent_line = $result;
					}
				}

				$result = $this->update_price(1, 'auto', 0, $mysoc); // This method is designed to add line from user input so total calculation must be done using 'auto' mode.

				// update ref
				$initialref = '(PROV'.$this->id.')';
				if (!empty($this->ref)) {
					$initialref = $this->ref;
				}

				$sql = 'UPDATE '.MAIN_DB_PREFIX."commande SET ref='".$this->db->escape($initialref)."' WHERE rowid=".((int) $this->id);
				if ($this->db->query($sql)) {
					$this->ref = $initialref;

					if (!empty($this->linkedObjectsIds) && empty($this->linked_objects)) {	// To use new linkedObjectsIds instead of old linked_objects
						$this->linked_objects = $this->linkedObjectsIds; // TODO Replace linked_objects with linkedObjectsIds
					}

					// Add object linked
					if (!$error && $this->id && !empty($this->linked_objects) && is_array($this->linked_objects)) {
						foreach ($this->linked_objects as $origin => $tmp_origin_id) {
							if (is_array($tmp_origin_id)) {       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
								foreach ($tmp_origin_id as $origin_id) {
									$ret = $this->add_object_linked($origin, $origin_id);
									if (!$ret) {
										$this->error = $this->db->lasterror();
										$error++;
									}
								}
							} else { // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
								$origin_id = $tmp_origin_id;
								$ret = $this->add_object_linked($origin, $origin_id);
								if (!$ret) {
									$this->error = $this->db->lasterror();
									$error++;
								}
							}
						}
					}

					if (!$error && $this->id && getDolGlobalString('MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN') && !empty($this->origin) && !empty($this->origin_id)) {   // Get contact from origin object
						$originforcontact = $this->origin;
						$originidforcontact = $this->origin_id;
						if ($originforcontact == 'shipping') {     // shipment and order share the same contacts. If creating from shipment we take data of order
							require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
							$exp = new Expedition($this->db);
							$exp->fetch($this->origin_id);
							$exp->fetchObjectLinked();
							if (count($exp->linkedObjectsIds['commande']) > 0) {
								foreach ($exp->linkedObjectsIds['commande'] as $key => $value) {
									$originforcontact = 'commande';
									if (is_object($value)) {
										$originidforcontact = $value->id;
									} else {
										$originidforcontact = $value;
									}
									break; // We take first one
								}
							}
						}

						$sqlcontact = "SELECT ctc.code, ctc.source, ec.fk_socpeople FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as ctc";
						$sqlcontact .= " WHERE element_id = ".((int) $originidforcontact)." AND ec.fk_c_type_contact = ctc.rowid AND ctc.element = '".$this->db->escape($originforcontact)."'";

						$resqlcontact = $this->db->query($sqlcontact);
						if ($resqlcontact) {
							while ($objcontact = $this->db->fetch_object($resqlcontact)) {
								//print $objcontact->code.'-'.$objcontact->source.'-'.$objcontact->fk_socpeople."\n";
								$this->add_contact($objcontact->fk_socpeople, $objcontact->code, $objcontact->source); // May failed because of duplicate key or because code of contact type does not exists for new object
							}
						} else {
							dol_print_error($this->db, $resqlcontact);
						}
					}

					if (!$error) {
						$result = $this->insertExtraFields();
						if ($result < 0) {
							$error++;
						}
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger('ORDER_CREATE', $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}

					if (!$error) {
						$this->db->commit();
						return $this->id;
					} else {
						$this->db->rollback();
						return -1 * $error;
					}
				} else {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					return -1;
				}
			}

			return 0;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	    User	$user		User making the clone
	 *	@param		int		$socid		Id of thirdparty
	 *	@return		int					New id of clone
	 */
	public function createFromClone(User $user, $socid = 0)
	{
		global $conf, $user, $hookmanager;

		$error = 0;

		$this->db->begin();

		// get lines so they will be clone
		foreach ($this->lines as $line) {
			$line->fetch_optionals();
		}

		// Load source object
		$objFrom = clone $this;

		// Change socid if needed
		if (!empty($socid) && $socid != $this->socid) {
			$objsoc = new Societe($this->db);

			if ($objsoc->fetch($socid) > 0) {
				$this->socid = $objsoc->id;
				$this->cond_reglement_id	= (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				$this->deposit_percent		= (!empty($objsoc->deposit_percent) ? $objsoc->deposit_percent : 0);
				$this->mode_reglement_id	= (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$this->fk_project = 0;
				$this->fk_delivery_address = 0;
			}

			// TODO Change product price if multi-prices
		}

		$this->id = 0;
		$this->ref = '';
		$this->statut = self::STATUS_DRAFT;

		// Clear fields
		$this->user_author_id     = $user->id;
		$this->user_validation_id = 0;
		$this->date = dol_now();
		$this->date_commande = dol_now();
		$this->date_creation      = '';
		$this->date_validation    = '';
		if (!getDolGlobalString('MAIN_KEEP_REF_CUSTOMER_ON_CLONING')) {
			$this->ref_client = '';
			$this->ref_customer = '';
		}

		// Do not clone ref_ext
		$num = count($this->lines);
		for ($i = 0; $i < $num; $i++) {
			$this->lines[$i]->ref_ext = '';
		}

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $this->create($user);
		if ($result < 0) {
			$error++;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($objFrom, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if ($this->socid == $objFrom->socid) {
				if ($this->copy_linked_contact($objFrom, 'external') < 0) {
					$error++;
				}
			}
		}

		if (!$error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array('objFrom' => $objFrom);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}
		}

		unset($this->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Load an object from a proposal and create a new order into database
	 *
	 *  @param      Object			$object 	        Object source
	 *  @param		User			$user				User making creation
	 *  @return     int             					Return integer <0 if KO, 0 if nothing done, 1 if OK
	 */
	public function createFromProposal($object, User $user)
	{
		global $conf, $hookmanager;

		require_once DOL_DOCUMENT_ROOT . '/multicurrency/class/multicurrency.class.php';
		require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

		$error = 0;

		$this->date_commande = dol_now();
		$this->date = dol_now();
		$this->source = 0;

		$num = count($object->lines);
		for ($i = 0; $i < $num; $i++) {
			$line = new OrderLine($this->db);

			$line->libelle           = $object->lines[$i]->libelle;
			$line->label             = $object->lines[$i]->label;
			$line->desc              = $object->lines[$i]->desc;
			$line->price             = $object->lines[$i]->price;
			$line->subprice          = $object->lines[$i]->subprice;
			$line->vat_src_code      = $object->lines[$i]->vat_src_code;
			$line->tva_tx            = $object->lines[$i]->tva_tx;
			$line->localtax1_tx      = $object->lines[$i]->localtax1_tx;
			$line->localtax2_tx      = $object->lines[$i]->localtax2_tx;
			$line->qty               = $object->lines[$i]->qty;
			$line->fk_remise_except  = $object->lines[$i]->fk_remise_except;
			$line->remise_percent    = $object->lines[$i]->remise_percent;
			$line->fk_product        = $object->lines[$i]->fk_product;
			$line->info_bits         = $object->lines[$i]->info_bits;
			$line->product_type      = $object->lines[$i]->product_type;
			$line->rang              = $object->lines[$i]->rang;
			$line->special_code      = $object->lines[$i]->special_code;
			$line->fk_parent_line    = $object->lines[$i]->fk_parent_line;
			$line->fk_unit = $object->lines[$i]->fk_unit;

			$line->date_start 		= $object->lines[$i]->date_start;
			$line->date_end    		= $object->lines[$i]->date_end;

			$line->fk_fournprice	= $object->lines[$i]->fk_fournprice;
			$marginInfos			= getMarginInfos($object->lines[$i]->subprice, $object->lines[$i]->remise_percent, $object->lines[$i]->tva_tx, $object->lines[$i]->localtax1_tx, $object->lines[$i]->localtax2_tx, $object->lines[$i]->fk_fournprice, $object->lines[$i]->pa_ht);
			$line->pa_ht			= $marginInfos[0];
			$line->marge_tx			= $marginInfos[1];
			$line->marque_tx		= $marginInfos[2];

			$line->origin           = $object->element;
			$line->origin_id        = $object->lines[$i]->id;

			// get extrafields from original line
			$object->lines[$i]->fetch_optionals();
			foreach ($object->lines[$i]->array_options as $options_key => $value) {
				$line->array_options[$options_key] = $value;
			}

			$this->lines[$i] = $line;
		}

		$this->entity               = $object->entity;
		$this->socid                = $object->socid;
		$this->fk_project           = $object->fk_project;
		$this->cond_reglement_id    = $object->cond_reglement_id;
		$this->deposit_percent      = $object->deposit_percent;
		$this->mode_reglement_id    = $object->mode_reglement_id;
		$this->fk_account           = $object->fk_account;
		$this->availability_id      = $object->availability_id;
		$this->demand_reason_id     = $object->demand_reason_id;
		$this->delivery_date        = $object->delivery_date;
		$this->shipping_method_id   = $object->shipping_method_id;
		$this->warehouse_id         = $object->warehouse_id;
		$this->fk_delivery_address  = $object->fk_delivery_address;
		$this->contact_id           = $object->contact_id;
		$this->ref_client           = $object->ref_client;
		$this->ref_customer         = $object->ref_client;

		if (!getDolGlobalString('MAIN_DISABLE_PROPAGATE_NOTES_FROM_ORIGIN')) {
			$this->note_private         = $object->note_private;
			$this->note_public          = $object->note_public;
		}

		$this->origin = $object->element;
		$this->origin_id = $object->id;

		// Multicurrency (test on $this->multicurrency_tx because we should take the default rate only if not using origin rate)
		if (!empty($conf->multicurrency->enabled)) {
			if (!empty($object->multicurrency_code)) {
				$this->multicurrency_code = $object->multicurrency_code;
			}
			if (getDolGlobalString('MULTICURRENCY_USE_ORIGIN_TX') && !empty($object->multicurrency_tx)) {
				$this->multicurrency_tx = $object->multicurrency_tx;
			}

			if (!empty($this->multicurrency_code) && empty($this->multicurrency_tx)) {
				$tmparray = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code, $this->date_commande);
				$this->fk_multicurrency = $tmparray[0];
				$this->multicurrency_tx = $tmparray[1];
			} else {
				$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
			}
			if (empty($this->fk_multicurrency)) {
				$this->multicurrency_code = $conf->currency;
				$this->fk_multicurrency = 0;
				$this->multicurrency_tx = 1;
			}
		}

		// get extrafields from original line
		$object->fetch_optionals();

		$e = new ExtraFields($this->db);
		$element_extrafields = $e->fetch_name_optionals_label($this->table_element);

		foreach ($object->array_options as $options_key => $value) {
			if (array_key_exists(str_replace('options_', '', $options_key), $element_extrafields)) {
				$this->array_options[$options_key] = $value;
			}
		}
		// Possibility to add external linked objects with hooks
		$this->linked_objects[$this->origin] = $this->origin_id;
		if (isset($object->other_linked_objects) && is_array($object->other_linked_objects) && !empty($object->other_linked_objects)) {
			$this->linked_objects = array_merge($this->linked_objects, $object->other_linked_objects);
		}

		$ret = $this->create($user);

		if ($ret > 0) {
			// Actions hooked (by external module)
			$hookmanager->initHooks(array('orderdao'));

			$parameters = array('objFrom' => $object);
			$action = '';
			$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->setErrorsFromObject($hookmanager);
				$error++;
			}

			if (!$error) {
				// Validate immediately the order
				if (getDolGlobalString('ORDER_VALID_AFTER_CLOSE_PROPAL')) {
					$this->fetch($ret);
					$this->valid($user);
				}
				return $ret;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}


	/**
	 *	Add an order line into database (linked to product/service or not)
	 *
	 *	@param      string			$desc            	Description of line
	 *	@param      float			$pu_ht    	        Unit price (without tax)
	 *	@param      float			$qty             	Quantite
	 * 	@param    	float			$txtva           	Force Vat rate, -1 for auto (Can contain the vat_src_code too with syntax '9.9 (CODE)')
	 * 	@param		float			$txlocaltax1		Local tax 1 rate (deprecated, use instead txtva with code inside)
	 * 	@param		float			$txlocaltax2		Local tax 2 rate (deprecated, use instead txtva with code inside)
	 *	@param      int				$fk_product      	Id of product
	 *	@param      float			$remise_percent  	Percentage discount of the line
	 *	@param      int				$info_bits			Bits of type of lines
	 *	@param      int				$fk_remise_except	Id remise
	 *	@param      string			$price_base_type	HT or TTC
	 *	@param      float			$pu_ttc    		    Prix unitaire TTC
	 *	@param      int|string		$date_start       	Start date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
	 *	@param      int|string		$date_end         	End date of the line - Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
	 *	@param      int				$type				Type of line (0=product, 1=service). Not used if fk_product is defined, the type of product is used.
	 *	@param      int				$rang             	Position of line
	 *	@param		int				$special_code		Special code (also used by externals modules!)
	 *	@param		int				$fk_parent_line		Parent line
	 *  @param		int				$fk_fournprice		Id supplier price
	 *  @param		int				$pa_ht				Buying price (without tax)
	 *  @param		string			$label				Label
	 *  @param		array			$array_options		extrafields array. Example array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
	 * 	@param 		int|null		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param		string		    $origin				Depend on global conf MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION can be 'orderdet', 'propaldet'..., else 'order','propal,'....
	 *  @param		int			    $origin_id			Depend on global conf MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION can be Id of origin object (aka line id), else object id
	 * 	@param		double			$pu_ht_devise		Unit price in currency
	 * 	@param		string			$ref_ext		    line external reference
	 *  @param		int				$noupdateafterinsertline	No update after insert of line
	 *	@return     int             					>0 if OK, <0 if KO
	 *
	 *	@see        add_product()
	 *
	 *	Les parameters sont deja cense etre juste et avec valeurs finales a l'appel
	 *	de cette methode. Aussi, pour le taux tva, il doit deja avoir ete defini
	 *	par l'appelant par la methode get_default_tva(societe_vendeuse,societe_acheteuse,produit)
	 *	et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
	 */
	public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $fk_product = 0, $remise_percent = 0, $info_bits = 0, $fk_remise_except = 0, $price_base_type = 'HT', $pu_ttc = 0, $date_start = '', $date_end = '', $type = 0, $rang = -1, $special_code = 0, $fk_parent_line = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $array_options = array(), $fk_unit = null, $origin = '', $origin_id = 0, $pu_ht_devise = 0, $ref_ext = '', $noupdateafterinsertline = 0)
	{
		global $mysoc, $conf, $langs, $user;

		$logtext = "::addline commandeid=$this->id, desc=$desc, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, fk_product=$fk_product, remise_percent=$remise_percent";
		$logtext .= ", info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, date_start=$date_start";
		$logtext .= ", date_end=$date_end, type=$type special_code=$special_code, fk_unit=$fk_unit, origin=$origin, origin_id=$origin_id, pu_ht_devise=$pu_ht_devise, ref_ext=$ref_ext";
		dol_syslog(get_class($this).$logtext, LOG_DEBUG);

		if ($this->statut == self::STATUS_DRAFT) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			// Clean parameters

			if (empty($remise_percent)) {
				$remise_percent = 0;
			}
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($info_bits)) {
				$info_bits = 0;
			}
			if (empty($rang)) {
				$rang = 0;
			}
			if (empty($txtva)) {
				$txtva = 0;
			}
			if (empty($txlocaltax1)) {
				$txlocaltax1 = 0;
			}
			if (empty($txlocaltax2)) {
				$txlocaltax2 = 0;
			}
			if (empty($fk_parent_line) || $fk_parent_line < 0) {
				$fk_parent_line = 0;
			}
			if (empty($this->fk_multicurrency)) {
				$this->fk_multicurrency = 0;
			}
			if (empty($ref_ext)) {
				$ref_ext = '';
			}

			$remise_percent = (float) price2num($remise_percent);
			$qty = (float) price2num($qty);
			$pu_ht = price2num($pu_ht);
			$pu_ht_devise = price2num($pu_ht_devise);
			$pu_ttc = price2num($pu_ttc);
			$pa_ht = (float) price2num($pa_ht);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5,1' or '5.1' or '5.1(XXX)', we must clean only if '5,1'
			}
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);
			if ($price_base_type == 'HT') {
				$pu = $pu_ht;
			} else {
				$pu = $pu_ttc;
			}
			$label = trim($label);
			$desc = trim($desc);

			// Check parameters
			if ($type < 0) {
				return -1;
			}

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$this->db->begin();

			$product_type = $type;
			if (!empty($fk_product) && $fk_product > 0) {
				$product = new Product($this->db);
				$result = $product->fetch($fk_product);
				$product_type = $product->type;

				if (getDolGlobalString('STOCK_MUST_BE_ENOUGH_FOR_ORDER') && $product_type == 0 && $product->stock_reel < $qty) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnOrder', $product->ref);
					$this->errors[] = $this->error;
					dol_syslog(get_class($this)."::addline error=Product ".$product->ref.": ".$this->error, LOG_ERR);
					$this->db->rollback();
					return self::STOCK_NOT_ENOUGH_FOR_ORDER;
				}
			}
			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

			/*var_dump($txlocaltax1);
			 var_dump($txlocaltax2);
			 var_dump($localtaxes_type);
			 var_dump($tabprice);
			 var_dump($tabprice[9]);
			 var_dump($tabprice[10]);
			 exit;*/

			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht  = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Rang to use
			$ranktouse = $rang;
			if ($ranktouse == -1) {
				$rangmax = $this->line_max($fk_parent_line);
				$ranktouse = $rangmax + 1;
			}

			// TODO A virer
			// Anciens indicateurs: $price, $remise (a ne plus utiliser)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0) {
				$remise = round(((float) $pu * $remise_percent / 100), 2);
				$price = (float) $pu - $remise;
			}

			// Insert line
			$this->line = new OrderLine($this->db);

			$this->line->context = $this->context;

			$this->line->fk_commande = $this->id;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty = $qty;
			$this->line->ref_ext = $ref_ext;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx = ($total_localtax1 ? $localtaxes_type[1] : 0);
			$this->line->localtax2_tx = ($total_localtax2 ? $localtaxes_type[3] : 0);
			$this->line->localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->fk_product = $fk_product;
			$this->line->product_type = $product_type;
			$this->line->fk_remise_except = $fk_remise_except;
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice = $pu_ht;
			$this->line->rang = $ranktouse;
			$this->line->info_bits = $info_bits;
			$this->line->total_ht = $total_ht;
			$this->line->total_tva = $total_tva;
			$this->line->total_localtax1 = $total_localtax1;
			$this->line->total_localtax2 = $total_localtax2;
			$this->line->total_ttc = $total_ttc;
			$this->line->special_code = $special_code;
			$this->line->origin = $origin;
			$this->line->origin_id = $origin_id;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->fk_unit = $fk_unit;

			$this->line->date_start = $date_start;
			$this->line->date_end = $date_end;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->fk_multicurrency = $this->fk_multicurrency;
			$this->line->multicurrency_code = $this->multicurrency_code;
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			// TODO Ne plus utiliser
			$this->line->price = $price;

			if (is_array($array_options) && count($array_options) > 0) {
				$this->line->array_options = $array_options;
			}

			$result = $this->line->insert($user);
			if ($result > 0) {
				// Reorder if child line
				if (!empty($fk_parent_line)) {
					$this->line_order(true, 'DESC');
				} elseif ($ranktouse > 0 && $ranktouse <= count($this->lines)) { // Update all rank of all other lines
					$linecount = count($this->lines);
					for ($ii = $ranktouse; $ii <= $linecount; $ii++) {
						$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
					}
				}

				// Mise a jour information denormalisees au niveau de la commande meme
				if (empty($noupdateafterinsertline)) {
					$result = $this->update_price(1, 'auto', 0, $mysoc); // This method is designed to add line from user input so total calculation must be done using 'auto' mode.
				}

				if ($result > 0) {
					$this->db->commit();
					$this->lines[] = $this->line;
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
			dol_syslog(get_class($this)."::addline status of order must be Draft to allow use of ->addline()", LOG_ERR);
			return -3;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add line into array
	 *	$this->client must be loaded
	 *
	 *	@param  int     $idproduct          Product Id
	 *	@param  float   $qty                Quantity
	 *	@param  float   $remise_percent     Product discount relative
	 * 	@param  int|string   $date_start         Start date of the line
	 * 	@param  int|string   $date_end           End date of the line
	 * 	@return void
	 *
	 *	TODO	Remplacer les appels a cette fonction par generation object Ligne
	 */
	public function add_product($idproduct, $qty, $remise_percent = 0.0, $date_start = '', $date_end = '')
	{
		// phpcs:enable
		global $conf, $mysoc;

		if (!$qty) {
			$qty = 1;
		}

		if ($idproduct > 0) {
			$prod = new Product($this->db);
			$prod->fetch($idproduct);

			$tva_tx = get_default_tva($mysoc, $this->thirdparty, $prod->id);
			$tva_npr = get_default_npr($mysoc, $this->thirdparty, $prod->id);
			if (empty($tva_tx)) {
				$tva_npr = 0;
			}
			$vat_src_code = ''; // May be defined into tva_tx

			$localtax1_tx = get_localtax($tva_tx, 1, $this->thirdparty, $mysoc, $tva_npr);
			$localtax2_tx = get_localtax($tva_tx, 2, $this->thirdparty, $mysoc, $tva_npr);

			// multiprix
			if ($conf->global->PRODUIT_MULTIPRICES && $this->thirdparty->price_level) {
				$price = $prod->multiprices[$this->thirdparty->price_level];
			} else {
				$price = $prod->price;
			}

			$line = new OrderLine($this->db);

			$line->context = $this->context;

			$line->fk_product = $idproduct;
			$line->desc = $prod->description;
			$line->qty = $qty;
			$line->subprice = $price;
			$line->remise_percent = $remise_percent;
			$line->vat_src_code = $vat_src_code;
			$line->tva_tx = $tva_tx;
			$line->localtax1_tx = $localtax1_tx;
			$line->localtax2_tx = $localtax2_tx;

			$line->product_ref = $prod->ref;
			$line->product_label = $prod->label;
			$line->product_desc = $prod->description;
			$line->fk_unit = $prod->fk_unit;

			// Save the start and end date of the line in the object
			if ($date_start) {
				$line->date_start = $date_start;
			}
			if ($date_end) {
				$line->date_end = $date_end;
			}

			$this->lines[] = $line;

			/** POUR AJOUTER AUTOMATIQUEMENT LES SOUSPRODUITS a LA COMMANDE
			 if (!empty($conf->global->PRODUIT_SOUSPRODUITS))
			 {
			 $prod = new Product($this->db);
			 $prod->fetch($idproduct);
			 $prod -> get_sousproduits_arbo();
			 $prods_arbo = $prod->get_arbo_each_prod();
			 if(count($prods_arbo) > 0)
			 {
				 foreach($prods_arbo as $key => $value)
				 {
					 // print "id : ".$value[1].' :qty: '.$value[0].'<br>';
					 if not in lines {
						$this->add_product($value[1], $value[0]);
					 }
				 }
			 }
			 **/
		}
	}


	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int			$id       		Id of object to load
	 * 	@param		string		$ref			Ref of object
	 * 	@param		string		$ref_ext		External reference of object
	 * 	@param		string		$notused		Internal reference of other object
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($id, $ref = '', $ref_ext = '', $notused = '')
	{
		// Check parameters
		if (empty($id) && empty($ref) && empty($ref_ext)) {
			return -1;
		}

		$sql = 'SELECT c.rowid, c.entity, c.date_creation, c.ref, c.fk_soc, c.fk_user_author, c.fk_user_valid, c.fk_user_modif, c.fk_statut';
		$sql .= ', c.amount_ht, c.total_ht, c.total_ttc, c.total_tva, c.localtax1 as total_localtax1, c.localtax2 as total_localtax2, c.fk_cond_reglement, c.deposit_percent, c.fk_mode_reglement, c.fk_availability, c.fk_input_reason';
		$sql .= ', c.fk_account';
		$sql .= ', c.date_commande, c.date_valid, c.tms';
		$sql .= ', c.date_livraison as delivery_date';
		$sql .= ', c.fk_shipping_method';
		$sql .= ', c.fk_warehouse';
		$sql .= ', c.fk_projet as fk_project, c.source, c.facture as billed';
		$sql .= ', c.note_private, c.note_public, c.ref_client, c.ref_ext, c.model_pdf, c.last_main_doc, c.fk_delivery_address, c.extraparams';
		$sql .= ', c.fk_incoterms, c.location_incoterms';
		$sql .= ", c.fk_multicurrency, c.multicurrency_code, c.multicurrency_tx, c.multicurrency_total_ht, c.multicurrency_total_tva, c.multicurrency_total_ttc";
		$sql .= ", c.module_source, c.pos_source";
		$sql .= ", i.libelle as label_incoterms";
		$sql .= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql .= ', cr.code as cond_reglement_code, cr.libelle as cond_reglement_libelle, cr.libelle_facture as cond_reglement_libelle_doc';
		$sql .= ', ca.code as availability_code, ca.label as availability_label';
		$sql .= ', dr.code as demand_reason_code';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as cr ON c.fk_cond_reglement = cr.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON c.fk_mode_reglement = p.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_availability as ca ON c.fk_availability = ca.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_input_reason as dr ON c.fk_input_reason = dr.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON c.fk_incoterms = i.rowid';

		if ($id) {
			$sql .= " WHERE c.rowid=".((int) $id);
		} else {
			$sql .= " WHERE c.entity IN (".getEntity('commande').")"; // Don't use entity if you use rowid
		}

		if ($ref) {
			$sql .= " AND c.ref='".$this->db->escape($ref)."'";
		}
		if ($ref_ext) {
			$sql .= " AND c.ref_ext='".$this->db->escape($ref_ext)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->entity = $obj->entity;

				$this->ref = $obj->ref;
				$this->ref_client = $obj->ref_client;
				$this->ref_customer = $obj->ref_client;
				$this->ref_ext = $obj->ref_ext;

				$this->socid = $obj->fk_soc;
				$this->thirdparty = null; // Clear if another value was already set by fetch_thirdparty

				$this->fk_project = $obj->fk_project;
				$this->project = null; // Clear if another value was already set by fetch_projet

				$this->statut = $obj->fk_statut;
				$this->status = $obj->fk_statut;

				$this->user_author_id = $obj->fk_user_author;
				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->total_ht				= $obj->total_ht;
				$this->total_tva			= $obj->total_tva;
				$this->total_localtax1		= $obj->total_localtax1;
				$this->total_localtax2		= $obj->total_localtax2;
				$this->total_ttc			= $obj->total_ttc;
				$this->date = $this->db->jdate($obj->date_commande);
				$this->date_commande		= $this->db->jdate($obj->date_commande);
				$this->date_creation		= $this->db->jdate($obj->date_creation);
				$this->date_validation      = $this->db->jdate($obj->date_valid);
				$this->date_modification    = $this->db->jdate($obj->tms);
				$this->source				= $obj->source;
				$this->billed				= $obj->billed;
				$this->note = $obj->note_private; // deprecated
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->model_pdf = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;
				$this->mode_reglement_id	= $obj->fk_mode_reglement;
				$this->mode_reglement_code	= $obj->mode_reglement_code;
				$this->mode_reglement		= $obj->mode_reglement_libelle;
				$this->cond_reglement_id	= $obj->fk_cond_reglement;
				$this->cond_reglement_code	= $obj->cond_reglement_code;
				$this->cond_reglement		= $obj->cond_reglement_libelle;
				$this->cond_reglement_doc = $obj->cond_reglement_libelle_doc;
				$this->deposit_percent = $obj->deposit_percent;
				$this->fk_account = $obj->fk_account;
				$this->availability_id = $obj->fk_availability;
				$this->availability_code	= $obj->availability_code;
				$this->availability	    	= $obj->availability_label;
				$this->demand_reason_id		= $obj->fk_input_reason;
				$this->demand_reason_code = $obj->demand_reason_code;
				$this->delivery_date = $this->db->jdate($obj->delivery_date);
				$this->shipping_method_id   = ($obj->fk_shipping_method > 0) ? $obj->fk_shipping_method : null;
				$this->warehouse_id         = ($obj->fk_warehouse > 0) ? $obj->fk_warehouse : null;
				$this->fk_delivery_address = $obj->fk_delivery_address;
				$this->module_source        = $obj->module_source;
				$this->pos_source           = $obj->pos_source;

				//Incoterms
				$this->fk_incoterms         = $obj->fk_incoterms;
				$this->location_incoterms   = $obj->location_incoterms;
				$this->label_incoterms    = $obj->label_incoterms;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				$this->extraparams = !empty($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();

				$this->lines = array();

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($result);

				// Lines
				$result = $this->fetch_lines();
				if ($result < 0) {
					return -3;
				}
				return 1;
			} else {
				$this->error = 'Order with id '.$id.' not found sql='.$sql;
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add a discount line into a sale order (as a sale order line) using an existing absolute discount (Consume the discount)
	 *
	 *	@param     int	$idremise			Id for the fixed discount from table llx_societe_remise_except
	 *	@return    int          			>0 if OK, <0 if KO
	 */
	public function insert_discount($idremise)
	{
		// phpcs:enable
		global $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$this->db->begin();

		$remise = new DiscountAbsolute($this->db);
		$result = $remise->fetch($idremise);

		if ($result > 0) {
			if ($remise->fk_facture) {	// Protection against multiple submission
				$this->error = $langs->trans("ErrorDiscountAlreadyUsed");
				$this->db->rollback();
				return -5;
			}

			$line = new OrderLine($this->db);

			$line->fk_commande = $this->id;
			$line->fk_remise_except = $remise->id;
			$line->desc = $remise->description; // Description ligne
			$line->vat_src_code = $remise->vat_src_code;
			$line->tva_tx = $remise->tva_tx;
			$line->subprice = -$remise->amount_ht;
			$line->price = -$remise->amount_ht;
			$line->fk_product = 0; // Id produit predefini
			$line->qty = 1;
			$line->remise_percent = 0;
			$line->rang = -1;
			$line->info_bits = 2;

			$line->total_ht  = -$remise->amount_ht;
			$line->total_tva = -$remise->amount_tva;
			$line->total_ttc = -$remise->amount_ttc;

			$result = $line->insert();
			if ($result > 0) {
				$result = $this->update_price(1);
				if ($result > 0) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $line->error;
				$this->errors = $line->errors;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -2;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load array lines
	 *
	 *	@param		int		$only_product			Return only physical products, not services
	 *	@param		int		$loadalsotranslation	Return translation for products
	 *	@return		int								Return integer <0 if KO, >0 if OK
	 */
	public function fetch_lines($only_product = 0, $loadalsotranslation = 0)
	{
		// phpcs:enable
		global $langs, $conf;

		$this->lines = array();

		$sql = 'SELECT l.rowid, l.fk_product, l.fk_parent_line, l.product_type, l.fk_commande, l.label as custom_label, l.description, l.price, l.qty, l.vat_src_code, l.tva_tx, l.ref_ext,';
		$sql .= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.fk_remise_except, l.remise_percent, l.subprice, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht, l.rang, l.info_bits, l.special_code,';
		$sql .= ' l.total_ht, l.total_ttc, l.total_tva, l.total_localtax1, l.total_localtax2, l.date_start, l.date_end,';
		$sql .= ' l.fk_unit,';
		$sql .= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.description as product_desc, p.fk_product_type, p.label as product_label, p.tosell as product_tosell, p.tobuy as product_tobuy, p.tobatch as product_tobatch, p.barcode as product_barcode,';
		$sql .= ' p.weight, p.weight_units, p.volume, p.volume_units';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON (p.rowid = l.fk_product)';
		$sql .= ' WHERE l.fk_commande = '.((int) $this->id);
		if ($only_product) {
			$sql .= ' AND p.fk_product_type = 0';
		}
		$sql .= ' ORDER BY l.rang, l.rowid';

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);

			$i = 0;
			while ($i < $num) {
				$objp = $this->db->fetch_object($result);

				$line = new OrderLine($this->db);

				$line->rowid            = $objp->rowid;
				$line->id               = $objp->rowid;
				$line->fk_commande      = $objp->fk_commande;
				$line->commande_id      = $objp->fk_commande;
				$line->label            = $objp->custom_label;
				$line->desc             = $objp->description;
				$line->description      = $objp->description; // Description line
				$line->product_type     = $objp->product_type;
				$line->qty              = $objp->qty;
				$line->ref_ext          = $objp->ref_ext;

				$line->vat_src_code     = $objp->vat_src_code;
				$line->tva_tx           = $objp->tva_tx;
				$line->localtax1_tx     = $objp->localtax1_tx;
				$line->localtax2_tx     = $objp->localtax2_tx;
				$line->localtax1_type	= $objp->localtax1_type;
				$line->localtax2_type	= $objp->localtax2_type;
				$line->total_ht         = $objp->total_ht;
				$line->total_ttc        = $objp->total_ttc;
				$line->total_tva        = $objp->total_tva;
				$line->total_localtax1  = $objp->total_localtax1;
				$line->total_localtax2  = $objp->total_localtax2;
				$line->subprice         = $objp->subprice;
				$line->fk_remise_except = $objp->fk_remise_except;
				$line->remise_percent   = $objp->remise_percent;
				$line->price            = $objp->price;
				$line->fk_product       = $objp->fk_product;
				$line->fk_fournprice = $objp->fk_fournprice;
				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
				$line->pa_ht = $marginInfos[0];
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];
				$line->rang             = $objp->rang;
				$line->info_bits        = $objp->info_bits;
				$line->special_code = $objp->special_code;
				$line->fk_parent_line = $objp->fk_parent_line;

				$line->ref = $objp->product_ref;
				$line->libelle = $objp->product_label;

				$line->product_ref = $objp->product_ref;
				$line->product_label = $objp->product_label;
				$line->product_tosell   = $objp->product_tosell;
				$line->product_tobuy    = $objp->product_tobuy;
				$line->product_desc     = $objp->product_desc;
				$line->product_tobatch  = $objp->product_tobatch;
				$line->product_barcode  = $objp->product_barcode;

				$line->fk_product_type  = $objp->fk_product_type; // Produit ou service
				$line->fk_unit          = $objp->fk_unit;

				$line->weight           = $objp->weight;
				$line->weight_units     = $objp->weight_units;
				$line->volume           = $objp->volume;
				$line->volume_units     = $objp->volume_units;

				$line->date_start       = $this->db->jdate($objp->date_start);
				$line->date_end         = $this->db->jdate($objp->date_end);

				// Multicurrency
				$line->fk_multicurrency = $objp->fk_multicurrency;
				$line->multicurrency_code = $objp->multicurrency_code;
				$line->multicurrency_subprice 	= $objp->multicurrency_subprice;
				$line->multicurrency_total_ht 	= $objp->multicurrency_total_ht;
				$line->multicurrency_total_tva 	= $objp->multicurrency_total_tva;
				$line->multicurrency_total_ttc 	= $objp->multicurrency_total_ttc;

				$line->fetch_optionals();

				// multilangs
				if (getDolGlobalInt('MAIN_MULTILANGS') && !empty($objp->fk_product) && !empty($loadalsotranslation)) {
					$tmpproduct = new Product($this->db);
					$tmpproduct->fetch($objp->fk_product);
					$tmpproduct->getMultiLangs();

					$line->multilangs = $tmpproduct->multilangs;
				}

				$this->lines[$i] = $line;

				$i++;
			}

			$this->db->free($result);

			return 1;
		} else {
			$this->error = $this->db->error();
			return -3;
		}
	}


	/**
	 *	Return number of line with type product.
	 *
	 *	@return		int		Return integer <0 if KO, Nbr of product lines if OK
	 */
	public function getNbOfProductsLines()
	{
		$nb = 0;
		foreach ($this->lines as $line) {
			if ($line->product_type == 0) {
				$nb++;
			}
		}
		return $nb;
	}

	/**
	 *	Return number of line with type service.
	 *
	 *	@return		int		Return integer <0 if KO, Nbr of service lines if OK
	 */
	public function getNbOfServicesLines()
	{
		$nb = 0;
		foreach ($this->lines as $line) {
			if ($line->product_type == 1) {
				$nb++;
			}
		}
		return $nb;
	}

	/**
	 *	Count number of shipments for this order
	 *
	 * 	@return     int                			Return integer <0 if KO, Nb of shipment found if OK
	 */
	public function getNbOfShipments()
	{
		$nb = 0;

		$sql = 'SELECT COUNT(DISTINCT ed.fk_expedition) as nb';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expeditiondet as ed,';
		$sql .= ' '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql .= ' WHERE';
		$sql .= ' ed.fk_elementdet = cd.rowid';
		$sql .= ' AND cd.fk_commande = '.((int) $this->id);
		//print $sql;

		dol_syslog(get_class($this)."::getNbOfShipments", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$nb = $obj->nb;
			}

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Load array this->expeditions of lines of shipments with nb of products sent for each order line
	 *  Note: For a dedicated shipment, the fetch_lines can be used to load the qty_asked and qty_shipped. This function is use to return qty_shipped cumulated for the order
	 *
	 *	@param      int		$filtre_statut      Filter on shipment status
	 *  @param		int		$fk_product			Add a filter on a product
	 * 	@return     int                			Return integer <0 if KO, Nb of lines found if OK
	 */
	public function loadExpeditions($filtre_statut = -1, $fk_product = 0)
	{
		$this->expeditions = array();

		$sql = 'SELECT cd.rowid, cd.fk_product,';
		$sql .= ' sum(ed.qty) as qty';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expeditiondet as ed,';
		if ($filtre_statut >= 0) {
			$sql .= ' '.MAIN_DB_PREFIX.'expedition as e,';
		}
		$sql .= ' '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql .= ' WHERE';
		if ($filtre_statut >= 0) {
			$sql .= ' ed.fk_expedition = e.rowid AND';
		}
		$sql .= ' ed.fk_elementdet = cd.rowid';
		$sql .= ' AND cd.fk_commande = '.((int) $this->id);
		if ($fk_product > 0) {
			$sql .= ' AND cd.fk_product = '.((int) $fk_product);
		}
		if ($filtre_statut >= 0) {
			$sql .= ' AND e.fk_statut >= '.((int) $filtre_statut);
		}
		$sql .= ' GROUP BY cd.rowid, cd.fk_product';
		//print $sql;

		dol_syslog(get_class($this)."::loadExpeditions", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->expeditions[$obj->rowid] = $obj->qty;
				$i++;
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Returns an array with expeditions lines number
	 *
	 * @return	int		Nb of shipments
	 */
	public function countNbOfShipments()
	{
		$sql = 'SELECT count(*)';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'expedition as e';
		$sql .= ', '.MAIN_DB_PREFIX.'element_element as el';
		$sql .= ' WHERE el.fk_source = '.((int) $this->id);
		$sql .= " AND el.sourcetype = 'commande'";
		$sql .= " AND el.fk_target = e.rowid";
		$sql .= " AND el.targettype = 'shipping'";

		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return $row[0];
		} else {
			dol_print_error($this->db);
		}

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return a array with the pending stock by product
	 *
	 *	@param      int		$filtre_statut      Filtre sur statut
	 *	@return     int                 		0 si OK, <0 si KO
	 *
	 *	TODO		FONCTION NON FINIE A FINIR
	 */
	/*public function stock_array($filtre_statut = self::STATUS_CANCELED)
	{
		// phpcs:enable
		$this->stocks = array();

		// Tableau des id de produit de la commande
		$array_of_product = array();

		// Recherche total en stock pour chaque produit
		// TODO $array_of_product est défini vide juste au dessus !!
		if (count($array_of_product)) {
			$sql = "SELECT fk_product, sum(ps.reel) as total";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps";
			$sql .= " WHERE ps.fk_product IN (".$this->db->sanitize(join(',', $array_of_product)).")";
			$sql .= ' GROUP BY fk_product';
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);
					$this->stocks[$obj->fk_product] = $obj->total;
					$i++;
				}
				$this->db->free($resql);
			}
		}
		return 0;
	}*/

	/**
	 *  Delete an order line
	 *
	 *	@param      User	$user		User object
	 *  @param      int		$lineid		Id of line to delete
	 *  @param		int		$id			Id of object (for a check)
	 *  @return     int        		 	>0 if OK, 0 if nothing to do, <0 if KO
	 */
	public function deleteLine($user = null, $lineid = 0, $id = 0)
	{
		if ($this->statut == self::STATUS_DRAFT) {
			$this->db->begin();

			// Delete line
			$line = new OrderLine($this->db);

			$line->context = $this->context;

			// Load data
			$line->fetch($lineid);

			if ($id > 0 && $line->fk_commande != $id) {
				$this->error = 'ErrorLineIDDoesNotMatchWithObjectID';
				return -1;
			}

			// Memorize previous line for triggers
			$staticline = clone $line;
			$line->oldline = $staticline;

			if ($line->delete($user) > 0) {
				$result = $this->update_price(1);

				if ($result > 0) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = $this->db->lasterror();
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error = $line->error;
				return -1;
			}
		} else {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Applique une remise relative
	 *
	 *  @deprecated Use setDiscount() instead.
	 *  @see setDiscount()
	 * 	@param     	User		$user		User qui positionne la remise
	 * 	@param     	float		$remise		Discount (percent)
	 * 	@param     	int			$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 					Return integer <0 if KO, >0 if OK
	 */
	public function set_remise($user, $remise, $notrigger = 0)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_remise is deprecated, use setDiscount instead", LOG_NOTICE);
		// @phan-suppress-next-line PhanDeprecatedFunction
		return $this->setDiscount($user, $remise, $notrigger);
	}

	/**
	 * 	Set a percentage discount
	 *
	 * 	@param     	User		$user		User setting the discount
	 * 	@param     	float|string	$remise		Discount (percent)
	 * 	@param     	int<0,1>	$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int<-1,1> 					Return integer <0 if KO, >0 if OK
	 */
	public function setDiscount($user, $remise, $notrigger = 0)
	{
		$remise = trim((string) $remise) ? trim((string) $remise) : 0;

		if ($user->hasRight('commande', 'creer')) {
			$error = 0;

			$this->db->begin();

			$remise = price2num($remise, 2);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET remise_percent = '.((float) $remise);
			$sql .= ' WHERE rowid = '.((int) $this->id).' AND fk_statut = '.((int) self::STATUS_DRAFT);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->remise_percent = $remise;
				$this->update_price(1);
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		}

		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 		Set a fixed amount discount
	 *
	 * 		@param     	User		$user 		User qui positionne la remise
	 * 		@param     	float		$remise		Discount
	 * 		@param     	int			$notrigger	1=Does not execute triggers, 0= execute triggers
	 *		@return		int 					Return integer <0 if KO, >0 if OK
	 */
	/*
	public function set_remise_absolue($user, $remise, $notrigger = 0)
	{
		// phpcs:enable
		if (empty($remise)) {
			$remise = 0;
		}

		$remise = price2num($remise);

		if ($user->hasRight('commande', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET remise_absolue = '.((float) $remise);
			$sql .= ' WHERE rowid = '.((int) $this->id).' AND fk_statut = '.self::STATUS_DRAFT;

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->remise_absolue = $remise;
				$this->update_price(1);
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		}

		return 0;
	}
	*/

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set the order date
	 *
	 *	@param      User	$user       Object user making change
	 *	@param      int		$date		Date
	 * 	@param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return     int         		Return integer <0 if KO, >0 if OK
	 */
	public function set_date($user, $date, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('commande', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
			$sql .= " SET date_commande = ".($date ? "'".$this->db->idate($date)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut = ".((int) self::STATUS_DRAFT);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->date = $date;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set delivery date
	 *
	 *	@param      User 	$user        		Object user that modify
	 *	@param      int		$delivery_date		Delivery date
	 *  @param  	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         				Return integer <0 if ko, >0 if ok
	 *	@deprecated Use  setDeliveryDate
	 */
	public function set_date_livraison($user, $delivery_date, $notrigger = 0)
	{
		// phpcs:enable
		return $this->setDeliveryDate($user, $delivery_date, $notrigger);
	}

	/**
	 *	Set the planned delivery date
	 *
	 *	@param      User	$user        		Object utilisateur qui modifie
	 *	@param      int		$delivery_date     Delivery date
	 *  @param     	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         				Return integer <0 si ko, >0 si ok
	 */
	public function setDeliveryDate($user, $delivery_date, $notrigger = 0)
	{
		if ($user->hasRight('commande', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."commande";
			$sql .= " SET date_livraison = ".($delivery_date ? "'".$this->db->idate($delivery_date)."'" : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->delivery_date = $delivery_date;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of orders (eventuelly filtered on a user) into an array
	 *
	 *  @param		int		$shortlist		0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
	 *  @param      int		$draft      	0=not draft, 1=draft
	 *  @param      User	$excluser      	Object user to exclude
	 *  @param    	int		$socid			Id third party
	 *  @param    	int		$limit			For pagination
	 *  @param    	int		$offset			For pagination
	 *  @param    	string	$sortfield		Sort criteria
	 *  @param    	string	$sortorder		Sort order
	 *  @return     int|array             		-1 if KO, array with result if OK
	 */
	public function liste_array($shortlist = 0, $draft = 0, $excluser = null, $socid = 0, $limit = 0, $offset = 0, $sortfield = 'c.date_commande', $sortorder = 'DESC')
	{
		// phpcs:enable
		global $user;

		$ga = array();

		$sql = "SELECT s.rowid, s.nom as name, s.client,";
		$sql .= " c.rowid as cid, c.ref";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", sc.fk_soc, sc.fk_user";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql .= " WHERE c.entity IN (".getEntity('commande').")";
		$sql .= " AND c.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		if ($socid) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
		if ($draft) {
			$sql .= " AND c.fk_statut = ".self::STATUS_DRAFT;
		}
		if (is_object($excluser)) {
			$sql .= " AND c.fk_user_author <> ".((int) $excluser->id);
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		$sql .= $this->db->plimit($limit, $offset);

		$result = $this->db->query($sql);
		if ($result) {
			$numc = $this->db->num_rows($result);
			if ($numc) {
				$i = 0;
				while ($i < $numc) {
					$obj = $this->db->fetch_object($result);

					if ($shortlist == 1) {
						$ga[$obj->cid] = $obj->ref;
					} elseif ($shortlist == 2) {
						$ga[$obj->cid] = $obj->ref.' ('.$obj->name.')';
					} else {
						$ga[$i]['id'] = $obj->cid;
						$ga[$i]['ref'] 	= $obj->ref;
						$ga[$i]['name'] = $obj->name;
					}
					$i++;
				}
			}
			return $ga;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Update delivery delay
	 *
	 *	@param      int		$availability_id	Id du nouveau mode
	 *  @param     	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         				>0 if OK, <0 if KO
	 */
	public function availability($availability_id, $notrigger = 0)
	{
		global $user;

		dol_syslog('Commande::availability('.$availability_id.')');
		if ($this->statut >= self::STATUS_DRAFT) {
			$error = 0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET fk_availability = '.((int) $availability_id);
			$sql .= ' WHERE rowid='.((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->availability_id = $availability_id;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$error_str = 'Command status do not meet requirement '.$this->statut;
			dol_syslog(__METHOD__.$error_str, LOG_ERR);
			$this->error = $error_str;
			$this->errors[] = $this->error;
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update order demand_reason
	 *
	 *  @param      int		$demand_reason_id	Id of new demand
	 *  @param     	int		$notrigger			1=Does not execute triggers, 0= execute triggers
	 *  @return     int        			 		>0 if ok, <0 if ko
	 */
	public function demand_reason($demand_reason_id, $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		dol_syslog('Commande::demand_reason('.$demand_reason_id.')');
		if ($this->statut >= self::STATUS_DRAFT) {
			$error = 0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande';
			$sql .= ' SET fk_input_reason = '.((int) $demand_reason_id);
			$sql .= ' WHERE rowid='.((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->demand_reason_id = $demand_reason_id;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$error_str = 'order status do not meet requirement '.$this->statut;
			dol_syslog(__METHOD__.$error_str, LOG_ERR);
			$this->error = $error_str;
			$this->errors[] = $this->error;
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set customer ref
	 *
	 *	@param      User	$user           User that make change
	 *	@param      string	$ref_client     Customer ref
	 *  @param     	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return     int             		Return integer <0 if KO, >0 if OK
	 */
	public function set_ref_client($user, $ref_client, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight('commande', 'creer')) {
			$error = 0;

			$this->db->begin();

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET';
			$sql .= ' ref_client = '.(empty($ref_client) ? 'NULL' : "'".$this->db->escape($ref_client)."'");
			$sql .= ' WHERE rowid = '.((int) $this->id);

			dol_syslog(__METHOD__.' this->id='.$this->id.', ref_client='.$ref_client, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->ref_client = $ref_client;
				$this->ref_customer = $ref_client;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(__METHOD__.' Error: '.$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			return -1;
		}
	}

	/**
	 * Classify the order as invoiced
	 *
	 * @param	User    $user       Object user making the change
	 * @param	int		$notrigger	1=Does not execute triggers, 0=execute triggers
	 * @return	int                 Return integer <0 if KO, 0 if already billed,  >0 if OK
	 */
	public function classifyBilled(User $user, $notrigger = 0)
	{
		$error = 0;

		if ($this->billed) {
			return 0;
		}

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET facture = 1';
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > '.self::STATUS_DRAFT;

		dol_syslog(get_class($this)."::classifyBilled", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
				$this->billed = 1;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_CLASSIFY_BILLED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::classifyBilled ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Classify the order as not invoiced
	 *
	 * @param	User    $user       Object user making the change
	 * @param	int		$notrigger	1=Does not execute triggers, 0=execute triggers
	 * @return  int     			Return integer <0 if ko, >0 if ok
	 */
	public function classifyUnBilled(User $user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande SET facture = 0';
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > '.self::STATUS_DRAFT;

		dol_syslog(get_class($this)."::classifyUnBilled", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
				$this->billed = 1;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_CLASSIFY_UNBILLED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->billed = 0;

				$this->db->commit();
				return 1;
			} else {
				foreach ($this->errors as $errmsg) {
					dol_syslog(get_class($this)."::classifyUnBilled ".$errmsg, LOG_ERR);
					$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
				}
				$this->db->rollback();
				return -1 * $error;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Update a line in database
	 *
	 *  @param    	int				$rowid            	Id of line to update
	 *  @param    	string			$desc             	Description of line
	 *  @param    	float			$pu               	Unit price
	 *  @param    	float			$qty              	Quantity
	 *  @param    	float			$remise_percent   	Percent of discount
	 *  @param    	float			$txtva           	Taux TVA
	 * 	@param		float			$txlocaltax1		Local tax 1 rate
	 *  @param		float			$txlocaltax2		Local tax 2 rate
	 *  @param    	string			$price_base_type	HT or TTC
	 *  @param    	int				$info_bits        	Miscellaneous information on line
	 *  @param    	int|string		$date_start        	Start date of the line
	 *  @param    	int|string		$date_end          	End date of the line
	 * 	@param		int				$type				Type of line (0=product, 1=service)
	 * 	@param		int				$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param		int				$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 *  @param		int				$fk_fournprice		Id of origin supplier price
	 *  @param		int				$pa_ht				Price (without tax) of product when it was bought
	 *  @param		string			$label				Label
	 *  @param		int				$special_code		Special code (also used by externals modules!)
	 *  @param		array			$array_options		extrafields array
	 * 	@param 		int|null		$fk_unit 			Code of the unit to use. Null to use the default one
	 *  @param		double			$pu_ht_devise		Amount in currency
	 * 	@param		int				$notrigger			disable line update trigger
	 * 	@param		string			$ref_ext			external reference
	 * @param       integer $rang   line rank
	 *  @return   	int              					Return integer < 0 if KO, > 0 if OK
	 */
	public function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0.0, $txlocaltax2 = 0.0, $price_base_type = 'HT', $info_bits = 0, $date_start = '', $date_end = '', $type = 0, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $special_code = 0, $array_options = array(), $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0, $ref_ext = '', $rang = 0)
	{
		global $conf, $mysoc, $langs, $user;

		dol_syslog(get_class($this)."::updateline id=$rowid, desc=$desc, pu=$pu, qty=$qty, remise_percent=$remise_percent, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, price_base_type=$price_base_type, info_bits=$info_bits, date_start=$date_start, date_end=$date_end, type=$type, fk_parent_line=$fk_parent_line, pa_ht=$pa_ht, special_code=$special_code, ref_ext=$ref_ext");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		if ($this->statut == Commande::STATUS_DRAFT) {
			// Clean parameters
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($info_bits)) {
				$info_bits = 0;
			}
			if (empty($txtva)) {
				$txtva = 0;
			}
			if (empty($txlocaltax1)) {
				$txlocaltax1 = 0;
			}
			if (empty($txlocaltax2)) {
				$txlocaltax2 = 0;
			}
			if (empty($remise_percent)) {
				$remise_percent = 0;
			}
			if (empty($special_code) || $special_code == 3) {
				$special_code = 0;
			}
			if (empty($ref_ext)) {
				$ref_ext = '';
			}

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$remise_percent = (float) price2num($remise_percent);
			$qty = (float) price2num($qty);
			$pu = price2num($pu);
			$pa_ht = (float) price2num($pa_ht);
			$pu_ht_devise = price2num($pu_ht_devise);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
			}
			$txlocaltax1 = (float) price2num($txlocaltax1);
			$txlocaltax2 = (float) price2num($txlocaltax2);

			$this->db->begin();

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

			// Clean vat code
			$vat_src_code = '';
			$reg = array();
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

			$total_ht = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Anciens indicateurs: $price, $subprice (a ne plus utiliser)
			$price = $pu_ht;
			if ($price_base_type == 'TTC') {
				$subprice = $pu_ttc;
			} else {
				$subprice = $pu_ht;
			}
			$remise = 0;
			if ($remise_percent > 0) {
				$remise = round(((float) $pu * $remise_percent / 100), 2);
				$price = ((float) $pu - $remise);
			}

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new OrderLine($this->db);
			$line->fetch($rowid);
			$line->fetch_optionals();

			if (!empty($line->fk_product)) {
				$product = new Product($this->db);
				$result = $product->fetch($line->fk_product);
				$product_type = $product->type;

				if (getDolGlobalString('STOCK_MUST_BE_ENOUGH_FOR_ORDER') && $product_type == 0 && $product->stock_reel < $qty) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnOrder', $product->ref);
					$this->errors[] = $this->error;

					dol_syslog(get_class($this)."::addline error=Product ".$product->ref.": ".$this->error, LOG_ERR);

					$this->db->rollback();
					return self::STOCK_NOT_ENOUGH_FOR_ORDER;
				}
			}

			$staticline = clone $line;

			$line->oldline = $staticline;
			$this->line = $line;
			$this->line->context = $this->context;
			$this->line->rang = $rang;

			// Reorder if fk_parent_line change
			if (!empty($fk_parent_line) && !empty($staticline->fk_parent_line) && $fk_parent_line != $staticline->fk_parent_line) {
				$rangmax = $this->line_max($fk_parent_line);
				$this->line->rang = $rangmax + 1;
			}

			$this->line->id = $rowid;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->qty = $qty;
			$this->line->ref_ext = $ref_ext;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx         = $txtva;
			$this->line->localtax1_tx   = $txlocaltax1;
			$this->line->localtax2_tx   = $txlocaltax2;
			$this->line->localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice       = $pu_ht;
			$this->line->info_bits      = $info_bits;
			$this->line->special_code   = $special_code;
			$this->line->total_ht       = $total_ht;
			$this->line->total_tva      = $total_tva;
			$this->line->total_localtax1 = $total_localtax1;
			$this->line->total_localtax2 = $total_localtax2;
			$this->line->total_ttc      = $total_ttc;
			$this->line->date_start     = $date_start;
			$this->line->date_end       = $date_end;
			$this->line->product_type   = $type;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->skip_update_total = $skip_update_total;
			$this->line->fk_unit        = $fk_unit;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			// TODO deprecated
			$this->line->price = $price;

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$this->line->array_options[$key] = $array_options[$key];
				}
			}

			$result = $this->line->update($user, $notrigger);
			if ($result > 0) {
				// Reorder if child line
				if (!empty($fk_parent_line)) {
					$this->line_order(true, 'DESC');
				}

				// Mise a jour info denormalisees
				$this->update_price(1, 'auto');

				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->line->error;

				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = get_class($this)."::updateline Order status makes operation forbidden";
			$this->errors = array('OrderStatusMakeOperationForbidden');
			return -2;
		}
	}

	/**
	 *      Update database
	 *
	 *      @param      User	$user        	User that modify
	 *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int      			   	Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean parameters
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->ref_client)) {
			$this->ref_client = trim($this->ref_client);
		}
		if (isset($this->ref_customer)) {
			$this->ref_customer = trim($this->ref_customer);
		}
		if (isset($this->note) || isset($this->note_private)) {
			$this->note_private = (isset($this->note_private) ? trim($this->note_private) : trim($this->note));
		}
		if (isset($this->note_public)) {
			$this->note_public = trim($this->note_public);
		}
		if (isset($this->model_pdf)) {
			$this->model_pdf = trim($this->model_pdf);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		$delivery_date = $this->delivery_date;

		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."commande SET";

		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_client=".(isset($this->ref_client) ? "'".$this->db->escape($this->ref_client)."'" : "null").",";
		$sql .= " ref_ext=".(isset($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").",";
		$sql .= " fk_soc=".(isset($this->socid) ? $this->socid : "null").",";
		$sql .= " date_commande=".(strval($this->date_commande) != '' ? "'".$this->db->idate($this->date_commande)."'" : 'null').",";
		$sql .= " date_valid=".(strval($this->date_validation) != '' ? "'".$this->db->idate($this->date_validation)."'" : 'null').",";
		$sql .= " total_tva=".(isset($this->total_tva) ? $this->total_tva : "null").",";
		$sql .= " localtax1=".(isset($this->total_localtax1) ? $this->total_localtax1 : "null").",";
		$sql .= " localtax2=".(isset($this->total_localtax2) ? $this->total_localtax2 : "null").",";
		$sql .= " total_ht=".(isset($this->total_ht) ? $this->total_ht : "null").",";
		$sql .= " total_ttc=".(isset($this->total_ttc) ? $this->total_ttc : "null").",";
		$sql .= " fk_statut=".(isset($this->statut) ? $this->statut : "null").",";
		$sql .= " fk_user_modif=".(isset($user->id) ? $user->id : "null").",";
		$sql .= " fk_user_valid=".((isset($this->user_validation_id) && $this->user_validation_id > 0) ? $this->user_validation_id : "null").",";
		$sql .= " fk_projet=".(isset($this->fk_project) ? $this->fk_project : "null").",";
		$sql .= " fk_cond_reglement=".(isset($this->cond_reglement_id) ? $this->cond_reglement_id : "null").",";
		$sql .= " deposit_percent=".(!empty($this->deposit_percent) ? strval($this->deposit_percent) : "null").",";
		$sql .= " fk_mode_reglement=".(isset($this->mode_reglement_id) ? $this->mode_reglement_id : "null").",";
		$sql .= " date_livraison=".(strval($this->delivery_date) != '' ? "'".$this->db->idate($this->delivery_date)."'" : 'null').",";
		$sql .= " fk_shipping_method=".(isset($this->shipping_method_id) ? $this->shipping_method_id : "null").",";
		$sql .= " fk_account=".($this->fk_account > 0 ? $this->fk_account : "null").",";
		$sql .= " fk_input_reason=".($this->demand_reason_id > 0 ? $this->demand_reason_id : "null").",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " import_key=".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");

		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('ORDER_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
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
	 *	Delete the sales order
	 *
	 *	@param	User	$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 * 	@return	int					Return integer <=0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		dol_syslog(get_class($this)."::delete ".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('ORDER_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Test we can delete
		if ($this->countNbOfShipments() != 0) {
			$this->errors[] = $langs->trans('SomeShipmentExists');
			$error++;
		}

		// Delete extrafields of lines and lines
		if (!$error && !empty($this->table_element_line)) {
			$tabletodelete = $this->table_element_line;
			$sqlef = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete."_extrafields WHERE fk_object IN (SELECT rowid FROM ".MAIN_DB_PREFIX.$tabletodelete." WHERE ".$this->fk_element." = ".((int) $this->id).")";
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete." WHERE ".$this->fk_element." = ".((int) $this->id);
			if (!$this->db->query($sqlef) || !$this->db->query($sql)) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
				$error++;
			}
		}

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				$error++;
			}
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
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
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
			$res = $this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive
			if (!$res) {
				$error++;
			}
		}

		if (!$error) {
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if ($conf->commande->multidir_output[$this->entity] && !empty($this->ref)) {
				$dir = $conf->commande->multidir_output[$this->entity]."/".$ref;
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


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *	@param		User	$user   Object user
	 *	@param		string	$mode   Mode ('toship', 'tobill', 'shippedtobill')
	 *	@return WorkboardResponse|int Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $langs;

		$clause = " WHERE";

		$sql = "SELECT c.rowid, c.date_creation as datec, c.date_commande, c.date_livraison as delivery_date, c.fk_statut, c.total_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as c";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = " AND";
		}
		$sql .= $clause." c.entity IN (".getEntity('commande').")";
		//$sql.= " AND c.fk_statut IN (1,2,3) AND c.facture = 0";
		if ($mode == 'toship') {
			// An order to ship is an open order (validated or in progress)
			$sql .= " AND c.fk_statut IN (" . self::STATUS_VALIDATED . "," . self::STATUS_SHIPMENTONPROCESS . ")";
		}
		if ($mode == 'tobill') {
			// An order to bill is an order not already billed
			$sql .= " AND c.fk_statut IN (" . self::STATUS_VALIDATED . "," . self::STATUS_SHIPMENTONPROCESS . ", " . self::STATUS_CLOSED . ") AND c.facture = 0";
		}
		if ($mode == 'shippedtobill') {
			// An order shipped and to bill is a delivered order not already billed
			$sql .= " AND c.fk_statut IN (" . self::STATUS_CLOSED . ") AND c.facture = 0";
		}
		if ($user->socid) {
			$sql .= " AND c.fk_soc = ".((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$delay_warning = 0;
			$label = $labelShort = $url = '';
			if ($mode == 'toship') {
				$delay_warning = $conf->commande->client->warning_delay / 60 / 60 / 24;
				$url = DOL_URL_ROOT.'/commande/list.php?search_status=-2&mainmenu=commercial&leftmenu=orders';
				$label = $langs->transnoentitiesnoconv("OrdersToProcess");
				$labelShort = $langs->transnoentitiesnoconv("Opened");
			}
			if ($mode == 'tobill') {
				$url = DOL_URL_ROOT.'/commande/list.php?search_status=-3&search_billed=0&mainmenu=commercial&leftmenu=orders';
				$label = $langs->trans("OrdersToBill"); // We set here bill but may be billed or ordered
				$labelShort = $langs->trans("ToBill");
			}
			if ($mode == 'shippedtobill') {
				$url = DOL_URL_ROOT.'/commande/list.php?search_status=3&search_billed=0&mainmenu=commercial&leftmenu=orders';
				$label = $langs->trans("OrdersToBill"); // We set here bill but may be billed or ordered
				$labelShort = $langs->trans("StatusOrderDelivered").' '.$langs->trans("and").' '.$langs->trans("ToBill");
			}

			$response = new WorkboardResponse();
			$response->warning_delay = $delay_warning;
			$response->label = $label;
			$response->labelShort = $labelShort;
			$response->url = $url;
			$response->img = img_object('', "order");

			$generic_commande = new Commande($this->db);

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
				$response->total += $obj->total_ht;

				$generic_commande->statut = $obj->fk_statut;
				$generic_commande->date_commande = $this->db->jdate($obj->date_commande);
				$generic_commande->date = $this->db->jdate($obj->date_commande);
				$generic_commande->delivery_date = $this->db->jdate($obj->delivery_date);

				if ($mode == 'toship' && $generic_commande->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	Return source label of order
	 *
	 *	@return     string      Label
	 */
	public function getLabelSource()
	{
		global $langs;

		$label = $langs->trans('OrderSource'.$this->source);

		if ($label == 'OrderSource') {
			return '';
		}
		return $label;
	}

	/**
	 *	Return status label of Order
	 *
	 *	@param      int		$mode       0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@return     string      		Label of status
	 */
	public function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut, $this->billed, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return label of status
	 *
	 *	@param		int		$status      	  Id status
	 *  @param      int		$billed    		  If invoiced
	 *	@param      int		$mode        	  0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @param      int     $donotshowbilled  Do not show billed status after order status
	 *  @return     string					  Label of status
	 */
	public function LibStatut($status, $billed, $mode, $donotshowbilled = 0)
	{
		// phpcs:enable
		global $langs, $hookmanager;

		$billedtext = '';
		if (empty($donotshowbilled)) {
			$billedtext .= ($billed ? ' - '.$langs->transnoentitiesnoconv("Billed") : '');
		}

		$labelTooltip = '';

		if ($status == self::STATUS_CANCELED) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusOrderCanceled');
			$labelStatusShort = $langs->transnoentitiesnoconv('StatusOrderCanceledShort');
			$statusType = 'status9';
		} elseif ($status == self::STATUS_DRAFT) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusOrderDraft');
			$labelStatusShort = $langs->transnoentitiesnoconv('StatusOrderDraftShort');
			$statusType = 'status0';
		} elseif ($status == self::STATUS_VALIDATED) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusOrderValidated').$billedtext;
			$labelStatusShort = $langs->transnoentitiesnoconv('StatusOrderValidatedShort').$billedtext;
			$statusType = 'status1';
		} elseif ($status == self::STATUS_SHIPMENTONPROCESS) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusOrderSent').$billedtext;
			$labelStatusShort = $langs->transnoentitiesnoconv('StatusOrderSentShort').$billedtext;
			$labelTooltip = $langs->transnoentitiesnoconv("StatusOrderSent");
			if (!empty($this->delivery_date)) {
				$labelTooltip .= ' - '.$langs->transnoentitiesnoconv("DateDeliveryPlanned").dol_print_date($this->delivery_date, 'day').$billedtext;
			}
			$statusType = 'status4';
		} elseif ($status == self::STATUS_CLOSED) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusOrderDelivered').$billedtext;
			$labelStatusShort = $langs->transnoentitiesnoconv('StatusOrderDeliveredShort').$billedtext;
			$statusType = 'status6';
		} else {
			$labelStatus = $langs->transnoentitiesnoconv('Unknown');
			$labelStatusShort = '';
			$statusType = '';
			$mode = 0;
		}

		$parameters = array(
			'status'          => $status,
			'mode'            => $mode,
			'billed'          => $billed,
			'donotshowbilled' => $donotshowbilled
		);

		$reshook = $hookmanager->executeHooks('LibStatut', $parameters, $this); // Note that $action and $object may have been modified by hook

		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode, '', array('tooltip' => $labelTooltip));
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

		$langs->load('orders');
		$datas = [];
		$nofetch = !empty($params['nofetch']);

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("Order")];
		}

		if ($user->hasRight('commande', 'lire')) {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Order").'</u>';
			if (isset($this->statut)) {
				$datas['status'] = ' '.$this->getLibStatut(5);
			}
			$datas['Ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
			if (!$nofetch) {
				$langs->load('companies');
				if (empty($this->thirdparty)) {
					$this->fetch_thirdparty();
				}
				$datas['customer'] = '<br><b>'.$langs->trans('Customer').':</b> '.$this->thirdparty->getNomUrl(1, '', 0, 1);
			}
			$datas['RefCustomer'] = '<br><b>'.$langs->trans('RefCustomer').':</b> '.(empty($this->ref_customer) ? (empty($this->ref_client) ? '' : $this->ref_client) : $this->ref_customer);
			if (!$nofetch) {
				$langs->load('project');
				if (is_null($this->project) || (is_object($this->project) && $this->project->isEmpty())) {
					$res = $this->fetchProject();
					if ($res > 0 && $this->project instanceof Project) {
						$datas['project'] = '<br><b>'.$langs->trans('Project').':</b> '.$this->project->getNomUrl(1, '', 0, 1);
					}
				}
			}
			if (!empty($this->total_ht)) {
				$datas['AmountHT'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_tva)) {
				$datas['VAT'] = '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_ttc)) {
				$datas['AmountTTC'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->date)) {
				$datas['Date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->date, 'day');
			}
			if (!empty($this->delivery_date)) {
				$datas['DeliveryDate'] = '<br><b>'.$langs->trans('DeliveryDate').':</b> '.dol_print_date($this->delivery_date, 'dayhour');
			}
		}

		return $datas;
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      int			$withpicto                Add picto into link
	 *	@param      string	    $option                   Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *	@param      int			$max          	          Max length to show
	 *	@param      int			$short			          ???
	 *  @param	    int   	    $notooltip		          1=Disable tooltip
	 *  @param      int         $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param		int			$addlinktonotes			  Add link to notes
	 *  @param		string		$target			  		  attribute target for link
	 *	@return     string          			          String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = 0, $target = '')
	{
		global $conf, $langs, $user, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		if (isModEnabled("shipping") && ($option == '1' || $option == '2')) {
			$url = DOL_URL_ROOT.'/expedition/shipment.php?id='.$this->id;
		} else {
			$url = DOL_URL_ROOT.'/commande/card.php?id='.$this->id;
		}

		if (!$user->hasRight('commande', 'lire')) {
			$option = 'nolink';
		}

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		if ($short) {
			return $url;
		}
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1,
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

		$linkclose = '';
		if (empty($notooltip) && $user->hasRight('commande', 'lire')) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("Order");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';

			$target_value = array('_self', '_blank', '_parent', '_top');
			if (in_array($target, $target_value)) {
				$linkclose .= ' target="'.dol_escape_htmltag($target).'"';
			}
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($option === 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $this->picto, (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		if ($addlinktonotes) {
			$txttoshow = ($user->socid > 0 ? $this->note_public : $this->note_private);
			if ($txttoshow) {
				$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow, 1);
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/commande/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
				$result .= img_picto('', 'note');
				$result .= '</a>';
				//$result.=img_picto($langs->trans("ViewNote"),'object_generic');
				//$result.='</a>';
				$result .= '</span>';
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
	 *	Charge les information d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, date_creation as datec, tms as datem,';
		$sql .= ' date_valid as datev,';
		$sql .= ' date_cloture as datecloture,';
		$sql .= ' fk_user_author, fk_user_valid, fk_user_cloture';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$this->user_creation_id = $obj->fk_user_author;
				}
				if ($obj->fk_user_valid) {
					$this->user_validation_id = $obj->fk_user_valid;
				}
				if ($obj->fk_user_cloture) {
					$this->user_closing_id = $obj->fk_user_cloture;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
				$this->date_cloture      = $this->db->jdate($obj->datecloture);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	int
	 */
	public function initAsSpecimen()
	{
		global $conf, $langs;

		dol_syslog(get_class($this)."::initAsSpecimen");

		// Load array of products prodids
		$num_prods = 0;
		$prodids = array();
		$sql = "SELECT rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= $this->db->plimit(100);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_prods = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_prods) {
				$i++;
				$row = $this->db->fetch_row($resql);
				$prodids[$i] = $row[0];
			}
		}

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->entity = $conf->entity;
		$this->socid = 1;
		$this->date = time();
		$this->date_lim_reglement = $this->date + 3600 * 24 * 30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';
		$this->availability_code   = 'DSP';
		$this->demand_reason_code  = 'SRC_00';

		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->multicurrency_tx = 1;
		$this->multicurrency_code = $conf->currency;

		$this->status = $this::STATUS_DRAFT;

		// Lines
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new OrderLine($this->db);

			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->qty = 1;
			$line->subprice = 100;
			$line->price = 100;
			$line->tva_tx = 20;
			if ($xnbp == 2) {
				$line->total_ht = 50;
				$line->total_ttc = 60;
				$line->total_tva = 10;
				$line->remise_percent = 50;
			} else {
				$line->total_ht = 100;
				$line->total_ttc = 120;
				$line->total_tva = 20;
				$line->remise_percent = 0;
			}
			if ($num_prods > 0) {
				$prodid = mt_rand(1, $num_prods);
				$line->fk_product = $prodids[$prodid];
				$line->product_ref = 'SPECIMEN';
			}

			$this->lines[$xnbp] = $line;

			$this->total_ht       += $line->total_ht;
			$this->total_tva      += $line->total_tva;
			$this->total_ttc      += $line->total_ttc;

			$xnbp++;
		}

		return 1;
	}


	/**
	 *	Load the indicators this->nb for the state board
	 *
	 *	@return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(co.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."commande as co";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON co.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." co.entity IN (".getEntity('commande').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["orders"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 * 	Create an array of order lines
	 *
	 * 	@return int		>0 if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		return $this->fetch_lines();
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$langs->load("orders");
		$outputlangs->load("products");

		if (!dol_strlen($modele)) {
			$modele = 'einstein';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('COMMANDE_ADDON_PDF')) {
				$modele = getDolGlobalString('COMMANDE_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/commande/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}


	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
		'commande'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'commandedet',
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Is the sales order delayed?
	 *
	 * @return bool     true if late, false if not
	 */
	public function hasDelay()
	{
		global $conf;

		if (!($this->statut > Commande::STATUS_DRAFT && $this->statut < Commande::STATUS_CLOSED)) {
			return false; // Never late if not inside this status range
		}

		$now = dol_now();

		return max($this->date, $this->delivery_date) < ($now - $conf->commande->client->warning_delay);
	}

	/**
	 * Show the customer delayed info
	 *
	 * @return string       Show delayed information
	 */
	public function showDelay()
	{
		global $conf, $langs;

		if (empty($this->delivery_date)) {
			$text = $langs->trans("OrderDate").' '.dol_print_date($this->date, 'day');
		} else {
			$text = $text = $langs->trans("DeliveryDate").' '.dol_print_date($this->delivery_date, 'day');
		}
		$text .= ' '.($conf->commande->client->warning_delay > 0 ? '+' : '-').' '.round(abs($conf->commande->client->warning_delay) / 3600 / 24, 1).' '.$langs->trans("days").' < '.$langs->trans("Today");

		return $text;
	}

	/**
	 * Set signed status
	 *
	 * @param  User   $user        Object user that modify
	 * @param  int    $status      Newsigned  status to set (often a constant like self::STATUS_XXX)
	 * @param  int    $notrigger   1 = Does not execute triggers, 0 = Execute triggers
	 * @param  string $triggercode Trigger code to use
	 * @return int                 0 < if KO, > 0 if OK
	 */
	public function setSignedStatus(User $user, int $status = 0, int $notrigger = 0, $triggercode = ''): int
	{
		return $this->setSignedStatusCommon($user, $status, $notrigger, $triggercode);
	}
}


/**
 *  Class to manage order lines
 */
class OrderLine extends CommonOrderLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'commandedet';

	public $table_element = 'commandedet';

	public $oldline;

	/**
	 * Id of parent order
	 * @var int
	 */
	public $fk_commande;

	/**
	 * Id of parent order
	 * @var int
	 * @deprecated Use fk_commande
	 * @see $fk_commande
	 */
	public $commande_id;

	public $fk_parent_line;

	/**
	 * @var int Id of invoice
	 */
	public $fk_facture;

	/**
	 * @var string External ref
	 */
	public $ref_ext;

	public $fk_remise_except;

	/**
	 * @var int line rank
	 */
	public $rang = 0;
	public $fk_fournprice;

	/**
	 * Buy price without taxes
	 * @var float
	 */
	public $pa_ht;
	public $marge_tx;
	public $marque_tx;

	/**
	 * @deprecated
	 * @see $remise_percent, $fk_remise_except
	 */
	public $remise;

	// Start and end date of the line
	public $date_start;
	public $date_end;

	public $skip_update_total; // Skip update price total for special lines


	/**
	 *      Constructor
	 *
	 *      @param     DoliDB	$db      handler d'acces base de donnee
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 *  Load line order
	 *
	 *  @param  int		$rowid          Id line order
	 *  @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_parent_line, cd.fk_product, cd.product_type, cd.label as custom_label, cd.description, cd.price, cd.qty, cd.tva_tx, cd.localtax1_tx, cd.localtax2_tx,';
		$sql .= ' cd.remise, cd.remise_percent, cd.fk_remise_except, cd.subprice, cd.ref_ext,';
		$sql .= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_localtax1, cd.total_localtax2, cd.total_ttc, cd.fk_product_fournisseur_price as fk_fournprice, cd.buy_price_ht as pa_ht, cd.rang, cd.special_code,';
		$sql .= ' cd.fk_unit,';
		$sql .= ' cd.fk_multicurrency, cd.multicurrency_code, cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc, p.tobatch as product_tobatch,';
		$sql .= ' cd.date_start, cd.date_end, cd.vat_src_code';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'commandedet as cd';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON cd.fk_product = p.rowid';
		$sql .= ' WHERE cd.rowid = '.((int) $rowid);
		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			if (!$objp) {
				$this->error = 'OrderLine with id '. $rowid .' not found sql='.$sql;
				return 0;
			}

			$this->rowid            = $objp->rowid;
			$this->id = $objp->rowid;
			$this->fk_commande      = $objp->fk_commande;
			$this->fk_parent_line   = $objp->fk_parent_line;
			$this->label            = $objp->custom_label;
			$this->desc             = $objp->description;
			$this->qty              = $objp->qty;
			$this->price            = $objp->price;
			$this->subprice         = $objp->subprice;
			$this->ref_ext          = $objp->ref_ext;
			$this->vat_src_code     = $objp->vat_src_code;
			$this->tva_tx           = $objp->tva_tx;
			$this->localtax1_tx		= $objp->localtax1_tx;
			$this->localtax2_tx		= $objp->localtax2_tx;
			$this->remise           = $objp->remise;
			$this->remise_percent   = $objp->remise_percent;
			$this->fk_remise_except = $objp->fk_remise_except;
			$this->fk_product       = $objp->fk_product;
			$this->product_type     = $objp->product_type;
			$this->info_bits        = $objp->info_bits;
			$this->special_code = $objp->special_code;
			$this->total_ht         = $objp->total_ht;
			$this->total_tva        = $objp->total_tva;
			$this->total_localtax1  = $objp->total_localtax1;
			$this->total_localtax2  = $objp->total_localtax2;
			$this->total_ttc        = $objp->total_ttc;
			$this->fk_fournprice = $objp->fk_fournprice;
			$marginInfos			= getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $this->fk_fournprice, $objp->pa_ht);
			$this->pa_ht			= $marginInfos[0];
			$this->marge_tx			= $marginInfos[1];
			$this->marque_tx		= $marginInfos[2];
			$this->special_code = $objp->special_code;
			$this->rang = $objp->rang;

			$this->ref = $objp->product_ref; // deprecated

			$this->product_ref      = $objp->product_ref;
			$this->product_label    = $objp->product_label;
			$this->product_desc     = $objp->product_desc;
			$this->product_tobatch  = $objp->product_tobatch;
			$this->fk_unit          = $objp->fk_unit;

			$this->date_start       = $this->db->jdate($objp->date_start);
			$this->date_end         = $this->db->jdate($objp->date_end);

			$this->fk_multicurrency = $objp->fk_multicurrency;
			$this->multicurrency_code = $objp->multicurrency_code;
			$this->multicurrency_subprice	= $objp->multicurrency_subprice;
			$this->multicurrency_total_ht	= $objp->multicurrency_total_ht;
			$this->multicurrency_total_tva	= $objp->multicurrency_total_tva;
			$this->multicurrency_total_ttc	= $objp->multicurrency_total_ttc;

			$this->fetch_optionals();

			$this->db->free($result);

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Delete line in database
	 *
	 *	@param      User	$user        	User that modify
	 *  @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return	 int  Return integer <0 si ko, >0 si ok
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		if (empty($this->id) && !empty($this->rowid)) {        // For backward compatibility
			$this->id = $this->rowid;
		}

		// check if order line is not in a shipment line before deleting
		$sqlCheckShipmentLine = "SELECT";
		$sqlCheckShipmentLine .= " ed.rowid";
		$sqlCheckShipmentLine .= " FROM " . MAIN_DB_PREFIX . "expeditiondet ed";
		$sqlCheckShipmentLine .= " WHERE ed.fk_elementdet = " . ((int) $this->id);

		$resqlCheckShipmentLine = $this->db->query($sqlCheckShipmentLine);
		if (!$resqlCheckShipmentLine) {
			$error++;
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
		} else {
			$langs->load('errors');
			$num = $this->db->num_rows($resqlCheckShipmentLine);
			if ($num > 0) {
				$error++;
				$objCheckShipmentLine = $this->db->fetch_object($resqlCheckShipmentLine);
				$this->error = $langs->trans('ErrorRecordAlreadyExists') . ' : ' . $langs->trans('ShipmentLine') . ' ' . $objCheckShipmentLine->rowid;
				$this->errors[] = $this->error;
			}
			$this->db->free($resqlCheckShipmentLine);
		}
		if ($error) {
			dol_syslog(__METHOD__ . 'Error ; ' . $this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('LINEORDER_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . "commandedet WHERE rowid = " . ((int) $this->id);

			dol_syslog("OrderLine::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this) . "::delete error -4 " . $this->error, LOG_ERR);
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		}

		foreach ($this->errors as $errmsg) {
			dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
			$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
		}
		$this->db->rollback();
		return -1 * $error;
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function insert($user = null, $notrigger = 0)
	{
		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

		dol_syslog(get_class($this)."::insert rang=".$this->rang);

		// Clean parameters
		if (empty($this->tva_tx)) {
			$this->tva_tx = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->rang)) {
			$this->rang = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->info_bits)) {
			$this->info_bits = 0;
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}
		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
		}

		// Check parameters
		if ($this->product_type < 0) {
			return -1;
		}

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.MAIN_DB_PREFIX.'commandedet';
		$sql .= ' (fk_commande, fk_parent_line, label, description, qty, ref_ext,';
		$sql .= ' vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type,';
		$sql .= ' fk_product, product_type, remise_percent, subprice, price, fk_remise_except,';
		$sql .= ' special_code, rang, fk_product_fournisseur_price, buy_price_ht,';
		$sql .= ' info_bits, total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, date_start, date_end,';
		$sql .= ' fk_unit,';
		$sql .= ' fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
		$sql .= ')';
		$sql .= " VALUES (".$this->fk_commande.",";
		$sql .= " ".($this->fk_parent_line > 0 ? "'".$this->db->escape($this->fk_parent_line)."'" : "null").",";
		$sql .= " ".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null").",";
		$sql .= " '".$this->db->escape($this->desc)."',";
		$sql .= " '".price2num($this->qty)."',";
		$sql .= " '".$this->db->escape($this->ref_ext)."',";
		$sql .= " ".(empty($this->vat_src_code) ? "''" : "'".$this->db->escape($this->vat_src_code)."'").",";
		$sql .= " '".price2num($this->tva_tx)."',";
		$sql .= " '".price2num($this->localtax1_tx)."',";
		$sql .= " '".price2num($this->localtax2_tx)."',";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= ' '.((!empty($this->fk_product) && $this->fk_product > 0) ? $this->fk_product : "null").',';
		$sql .= " '".$this->db->escape($this->product_type)."',";
		$sql .= " '".price2num($this->remise_percent)."',";
		$sql .= " ".(price2num($this->subprice) !== '' ? price2num($this->subprice) : "null").",";
		$sql .= " ".($this->price != '' ? "'".price2num($this->price)."'" : "null").",";
		$sql .= ' '.(!empty($this->fk_remise_except) ? $this->fk_remise_except : "null").',';
		$sql .= ' '.((int) $this->special_code).',';
		$sql .= ' '.((int) $this->rang).',';
		$sql .= ' '.(!empty($this->fk_fournprice) ? $this->fk_fournprice : "null").',';
		$sql .= ' '.price2num($this->pa_ht).',';
		$sql .= " ".((int) $this->info_bits).",";
		$sql .= " ".price2num($this->total_ht, 'MT').",";
		$sql .= " ".price2num($this->total_tva, 'MT').",";
		$sql .= " ".price2num($this->total_localtax1, 'MT').",";
		$sql .= " ".price2num($this->total_localtax2, 'MT').",";
		$sql .= " ".price2num($this->total_ttc, 'MT').",";
		$sql .= " ".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null").',';
		$sql .= " ".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null").',';
		$sql .= ' '.(!$this->fk_unit ? 'NULL' : ((int) $this->fk_unit));
		$sql .= ", ".(!empty($this->fk_multicurrency) ? ((int) $this->fk_multicurrency) : 'NULL');
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".price2num($this->multicurrency_subprice, 'CU');
		$sql .= ", ".price2num($this->multicurrency_total_ht, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_tva, 'CT');
		$sql .= ", ".price2num($this->multicurrency_total_ttc, 'CT');
		$sql .= ')';

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'commandedet');
			$this->rowid = $this->id;

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_INSERT', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::insert ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *	Update the line object into db
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		$pa_ht_isemptystring = (empty($this->pa_ht) && $this->pa_ht == ''); // If true, we can use a default value. If this->pa_ht = '0', we must use '0'.

		// Clean parameters
		if (empty($this->tva_tx)) {
			$this->tva_tx = 0;
		}
		if (empty($this->localtax1_tx)) {
			$this->localtax1_tx = 0;
		}
		if (empty($this->localtax2_tx)) {
			$this->localtax2_tx = 0;
		}
		if (empty($this->localtax1_type)) {
			$this->localtax1_type = 0;
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = 0;
		}
		if (empty($this->qty)) {
			$this->qty = 0;
		}
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}
		if (empty($this->marque_tx)) {
			$this->marque_tx = 0;
		}
		if (empty($this->marge_tx)) {
			$this->marge_tx = 0;
		}
		if (empty($this->remise_percent)) {
			$this->remise_percent = 0;
		}
		if (empty($this->remise)) {
			$this->remise = 0;
		}
		if (empty($this->info_bits)) {
			$this->info_bits = 0;
		}
		if (empty($this->special_code)) {
			$this->special_code = 0;
		}
		if (empty($this->product_type)) {
			$this->product_type = 0;
		}
		if (empty($this->fk_parent_line)) {
			$this->fk_parent_line = 0;
		}
		if (empty($this->pa_ht)) {
			$this->pa_ht = 0;
		}
		if (empty($this->ref_ext)) {
			$this->ref_ext = '';
		}

		// if buy price not defined, define buyprice as configured in margin admin
		if ($this->pa_ht == 0 && $pa_ht_isemptystring) {
			$result = $this->defineBuyPrice($this->subprice, $this->remise_percent, $this->fk_product);
			if ($result < 0) {
				return $result;
			} else {
				$this->pa_ht = $result;
			}
		}

		$this->db->begin();

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql .= " description='".$this->db->escape($this->desc)."'";
		$sql .= " , label=".(!empty($this->label) ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= " , vat_src_code=".(!empty($this->vat_src_code) ? "'".$this->db->escape($this->vat_src_code)."'" : "''");
		$sql .= " , tva_tx=".price2num($this->tva_tx);
		$sql .= " , localtax1_tx=".price2num($this->localtax1_tx);
		$sql .= " , localtax2_tx=".price2num($this->localtax2_tx);
		$sql .= " , localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= " , localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= " , qty=".price2num($this->qty);
		$sql .= " , ref_ext='".$this->db->escape($this->ref_ext)."'";
		$sql .= " , subprice=".price2num($this->subprice);
		$sql .= " , remise_percent=".price2num($this->remise_percent);
		$sql .= " , price=".price2num($this->price); // TODO A virer
		$sql .= " , remise=".price2num($this->remise); // TODO A virer
		if (empty($this->skip_update_total)) {
			$sql .= " , total_ht=".price2num($this->total_ht);
			$sql .= " , total_tva=".price2num($this->total_tva);
			$sql .= " , total_ttc=".price2num($this->total_ttc);
			$sql .= " , total_localtax1=".price2num($this->total_localtax1);
			$sql .= " , total_localtax2=".price2num($this->total_localtax2);
		}
		$sql .= " , fk_product_fournisseur_price=".(!empty($this->fk_fournprice) ? $this->fk_fournprice : "null");
		$sql .= " , buy_price_ht='".price2num($this->pa_ht)."'";
		$sql .= " , info_bits=".((int) $this->info_bits);
		$sql .= " , special_code=".((int) $this->special_code);
		$sql .= " , date_start=".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= " , date_end=".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= " , product_type=".$this->product_type;
		$sql .= " , fk_parent_line=".(!empty($this->fk_parent_line) ? $this->fk_parent_line : "null");
		if (!empty($this->rang)) {
			$sql .= ", rang=".((int) $this->rang);
		}
		$sql .= " , fk_unit=".(!$this->fk_unit ? 'NULL' : $this->fk_unit);

		// Multicurrency
		$sql .= " , multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= " , multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= " , multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= " , multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= " WHERE rowid = ".((int) $this->rowid);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$this->id = $this->rowid;
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			}

			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update DB line fields total_xxx
	 *	Used by migration
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 */
	public function update_total()
	{
		// phpcs:enable
		$this->db->begin();

		// Clean parameters
		if (empty($this->total_localtax1)) {
			$this->total_localtax1 = 0;
		}
		if (empty($this->total_localtax2)) {
			$this->total_localtax2 = 0;
		}

		// Mise a jour ligne en base
		$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet SET";
		$sql .= " total_ht='".price2num($this->total_ht)."'";
		$sql .= ",total_tva='".price2num($this->total_tva)."'";
		$sql .= ",total_localtax1='".price2num($this->total_localtax1)."'";
		$sql .= ",total_localtax2='".price2num($this->total_localtax2)."'";
		$sql .= ",total_ttc='".price2num($this->total_ttc)."'";
		$sql .= " WHERE rowid = ".((int) $this->rowid);

		dol_syslog("OrderLine::update_total", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
}
