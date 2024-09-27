<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2007		Franky Van Liedekerke	<franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2020	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2010-2018	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2015	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2013		Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2018		Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2018-2022	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2021		Josep Lluís Amador		<joseplluis@lliuretic.cat>
 * Copyright (C) 2022		Gauthier VERDOL			<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		Solution Libre SAS		<contact@solution-libre.fr>
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
 *	\file       htdocs/fourn/class/fournisseur.commande.class.php
 *	\ingroup    fournisseur,commande
 *	\brief      File of class to manage suppliers orders
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
if (isModEnabled('productbatch')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
}


/**
 *	Class to manage predefined suppliers products
 */
class CommandeFournisseur extends CommonOrder
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'order_supplier';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'commande_fournisseur';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'commande_fournisseurdet';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_commande';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'supplier_order';

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
	 * @var int Purchase Order ID
	 */
	public $id;

	/**
	 * @var string Supplier order reference
	 */
	public $ref;

	/**
	 * @var string Supplier reference
	 */
	public $ref_supplier;

	/**
	 * @var string ref supplier
	 * @deprecated
	 * @see $ref_supplier
	 */
	public $ref_fourn;

	/**
	 * @var int
	 */
	public $statut; // 0=Draft -> 1=Validated -> 2=Approved -> 3=Ordered/Process running -> 4=Received partially -> 5=Received totally -> (reopen) 4=Received partially
	//                                                                                          -> 7=Canceled/Never received -> (reopen) 3=Process running
	//									                            -> 6=Canceled -> (reopen) 2=Approved
	//  		                                      -> 9=Refused  -> (reopen) 1=Validated
	//  Note: billed or not is on another field "billed"

	public $billed;

	/**
	 * @var int Company ID
	 */
	public $socid;

	/**
	 * @var int Supplier ID
	 */
	public $fourn_id;

	/**
	 * @var int Date
	 */
	public $date;

	/**
	 * @var int Date of the purchase order creation
	 */
	public $date_creation;

	/**
	 * @var int Date of the purchase order validation
	 */
	public $date_valid;

	/**
	 * @var int Date of the purchase order approval
	 */
	public $date_approve;

	/**
	 * @var int Date of the purchase order second approval
	 * Used when SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set
	 */
	public $date_approve2;

	/**
	 * @var int Date of the purchase order ordering
	 */
	public $date_commande;

	//For backward compatibility
	public $remise_percent;
	public $methode_commande_id;
	public $methode_commande;

	/**
	 *  @var int Expected Delivery Date
	 */
	public $delivery_date;

	/**
	 *  @var float Total value, excluding taxes (HT = "Hors Taxe" in French)
	 */
	public $total_ht;

	/**
	 *  @var float Total VAT
	 */
	public $total_tva;

	/**
	 *  @var float Total Local tax 1
	 */
	public $total_localtax1;

	/**
	 *  @var float Total Local tax 2
	 */
	public $total_localtax2;

	/**
	 *  @var float Total value, including taxes (TTC = "Toutes Taxes Comprises" in French)
	 */
	public $total_ttc;

	public $source;

	/**
	 * @var int ID
	 */
	public $fk_project;

	/**
	 * @var int Payment conditions ID
	 */
	public $cond_reglement_id;

	/**
	 * @var string Payment conditions code
	 */
	public $cond_reglement_code;

	/**
	 * @var string Payment conditions label
	 */
	public $cond_reglement_label;

	/**
	 * @var string Payment conditions label on documents
	 */
	public $cond_reglement_doc;

	/**
	 * @var int Account ID
	 */
	public $fk_account;

	/**
	 * @var int Payment choice ID
	 */
	public $mode_reglement_id;

	/**
	 * @var string Payment choice code
	 */
	public $mode_reglement_code;

	/**
	 * @var string Payment choice label
	 */
	public $mode_reglement;

	/**
	 * @var int User ID of the purchase order author
	 */
	public $user_author_id;

	/**
	 * @var int User ID of the purchase order approver
	 */
	public $user_approve_id;

	/**
	 * @var int User ID of the purchase order second approver
	 * Used when SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set
	 */
	public $user_approve_id2;

	public $refuse_note;

	public $extraparams = array();

	/**
	 * @var CommandeFournisseurLigne[]
	 */
	public $lines = array();

	/**
	 * @var CommandeFournisseurLigne
	 */
	public $line;

	// Add for supplier_proposal
	public $origin;
	public $origin_id;
	public $linked_objects = array();

	/**
	 * @var int Date of the purchase order payment deadline
	 */
	public $date_lim_reglement;
	public $receptions = array();

	// Multicurrency
	/**
	 * @var int ID
	 */
	public $fk_multicurrency;

	/**
	 * @var string
	 */
	public $multicurrency_code;

	/**
	 * @var float Rate
	 */
	public $multicurrency_tx;

	/**
	 * @var float Total value in the other currency, excluding taxes (HT = "Hors Taxes" in French)
	 */
	public $multicurrency_total_ht;

	/**
	 * @var float Total VAT in the other currency (TVA = "Taxe sur la Valeur Ajoutée" in French)
	 */
	public $multicurrency_total_tva;

	/**
	 * @var float Total value in the other currency, including taxes (TTC = "Toutes Taxes Comprises in French)
	 */
	public $multicurrency_total_ttc;

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:Sortfield]]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")' or 'isModEnabled("multicurrency")' ...)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
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
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 10),
		'ref' => array('type' => 'varchar(255)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'showoncombobox' => 1, 'position' => 25, 'searchall' => 1),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'Ref ext', 'enabled' => 1, 'visible' => 0, 'position' => 35),
		'ref_supplier' => array('type' => 'varchar(255)', 'label' => 'RefOrderSupplierShort', 'enabled' => 1, 'visible' => 1, 'position' => 40, 'searchall' => 1),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => "isModEnabled('project')", 'visible' => -1, 'position' => 45),
		'date_valid' => array('type' => 'datetime', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 710),
		'date_approve' => array('type' => 'datetime', 'label' => 'DateApprove', 'enabled' => 1, 'visible' => -1, 'position' => 720),
		'date_approve2' => array('type' => 'datetime', 'label' => 'DateApprove2', 'enabled' => 1, 'visible' => 3, 'position' => 725),
		'date_commande' => array('type' => 'date', 'label' => 'OrderDateShort', 'enabled' => 1, 'visible' => 1, 'position' => 70),
		'date_livraison' => array('type' => 'datetime', 'label' => 'DeliveryDate', 'enabled' => 'empty($conf->global->ORDER_DISABLE_DELIVERY_DATE)', 'visible' => 1, 'position' => 74),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => 3, 'position' => 41),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => 3, 'notnull' => -1, 'position' => 80),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => 3, 'position' => 711),
		'fk_user_approve' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserApproval', 'enabled' => 1, 'visible' => 3, 'position' => 721),
		'fk_user_approve2' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserApproval2', 'enabled' => 1, 'visible' => 3, 'position' => 726),
		'source' => array('type' => 'smallint(6)', 'label' => 'Source', 'enabled' => 1, 'visible' => 3, 'notnull' => 1, 'position' => 100),
		'billed' => array('type' => 'smallint(6)', 'label' => 'Billed', 'enabled' => 1, 'visible' => 1, 'position' => 710),
		'total_ht' => array('type' => 'double(24,8)', 'label' => 'AmountHT', 'enabled' => 1, 'visible' => 1, 'position' => 130, 'isameasure' => 1),
		'total_tva' => array('type' => 'double(24,8)', 'label' => 'AmountVAT', 'enabled' => 1, 'visible' => 1, 'position' => 135, 'isameasure' => 1),
		'localtax1' => array('type' => 'double(24,8)', 'label' => 'LT1', 'enabled' => 1, 'visible' => 3, 'position' => 140, 'isameasure' => 1),
		'localtax2' => array('type' => 'double(24,8)', 'label' => 'LT2', 'enabled' => 1, 'visible' => 3, 'position' => 145, 'isameasure' => 1),
		'total_ttc' => array('type' => 'double(24,8)', 'label' => 'AmountTTC', 'enabled' => 1, 'visible' => -1, 'position' => 150, 'isameasure' => 1),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 750, 'searchall' => 1),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 760, 'searchall' => 1),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'ModelPDF', 'enabled' => 1, 'visible' => 0, 'position' => 165),
		'fk_input_method' => array('type' => 'integer', 'label' => 'OrderMode', 'enabled' => 1, 'visible' => 3, 'position' => 170),
		'fk_cond_reglement' => array('type' => 'integer', 'label' => 'PaymentTerm', 'enabled' => 1, 'visible' => 3, 'position' => 175),
		'fk_mode_reglement' => array('type' => 'integer', 'label' => 'PaymentMode', 'enabled' => 1, 'visible' => 3, 'position' => 180),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => 0, 'position' => 190),
		'fk_account' => array('type' => 'integer', 'label' => 'BankAccount', 'enabled' => 'isModEnabled("bank")', 'visible' => 3, 'position' => 200),
		'fk_incoterms' => array('type' => 'integer', 'label' => 'IncotermCode', 'enabled' => 1, 'visible' => 3, 'position' => 205),
		'location_incoterms' => array('type' => 'varchar(255)', 'label' => 'IncotermLocation', 'enabled' => 1, 'visible' => 3, 'position' => 210),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'Fk multicurrency', 'enabled' => 1, 'visible' => 0, 'position' => 215),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'Currency', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 220),
		'multicurrency_tx' => array('type' => 'double(24,8)', 'label' => 'CurrencyRate', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 225),
		'multicurrency_total_ht' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountHT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 230),
		'multicurrency_total_tva' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountVAT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 235),
		'multicurrency_total_ttc' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountTTC', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 240),
		'date_creation' => array('type' => 'datetime', 'label' => 'Date creation', 'enabled' => 1, 'visible' => -1, 'position' => 500),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 'isModEnabled("societe")', 'visible' => 1, 'notnull' => 1, 'position' => 50),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'position' => 1000, 'index' => 1),
		'tms' => array('type' => 'datetime', 'label' => "DateModificationShort", 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 501),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => 0, 'position' => 700),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'position' => 701),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => 0, 'position' => 900),
	);


	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Accepted
	 */
	const STATUS_ACCEPTED = 2;

	/**
	 * Order sent, shipment on process
	 */
	const STATUS_ORDERSENT = 3;

	/**
	 * Received partially
	 */
	const STATUS_RECEIVED_PARTIALLY = 4;

	/**
	 * Received completely
	 */
	const STATUS_RECEIVED_COMPLETELY = 5;

	/**
	 * Order canceled
	 */
	const STATUS_CANCELED = 6;

	/**
	 * Order canceled/never received
	 */
	const STATUS_CANCELED_AFTER_ORDER = 7;

	/**
	 * Refused
	 */
	const STATUS_REFUSED = 9;


	/**
	 * The constant used into source field to track the order was generated by the replenishement feature
	 */
	const SOURCE_ID_REPLENISHMENT = 42;

	/**
	 * 	Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
	}


	/**
	 *	Get object and lines from database
	 *
	 * 	@param	int		$id			Id of order to load
	 * 	@param	string	$ref		Ref of object
	 *	@return int 		        >0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($id, $ref = '')
	{
		// Check parameters
		if (empty($id) && empty($ref)) {
			return -1;
		}

		$sql = "SELECT c.rowid, c.entity, c.ref, ref_supplier, c.fk_soc, c.fk_statut as status, c.amount_ht, c.total_ht, c.total_ttc, c.total_tva,";
		$sql .= " c.localtax1, c.localtax2, ";
		$sql .= " c.date_creation, c.date_valid, c.date_approve, c.date_approve2,";
		$sql .= " c.fk_user_author as user_author_id, c.fk_user_valid as user_validation_id, c.fk_user_approve as user_approve_id, c.fk_user_approve2 as user_approve_id2,";
		$sql .= " c.date_commande as date_commande, c.date_livraison as delivery_date, c.fk_cond_reglement, c.fk_mode_reglement, c.fk_projet as fk_project, c.remise_percent, c.source, c.fk_input_method,";
		$sql .= " c.fk_account,";
		$sql .= " c.note_private, c.note_public, c.model_pdf, c.extraparams, c.billed,";
		$sql .= " c.fk_multicurrency, c.multicurrency_code, c.multicurrency_tx, c.multicurrency_total_ht, c.multicurrency_total_tva, c.multicurrency_total_ttc,";
		$sql .= " cm.libelle as methode_commande,";
		$sql .= " cr.code as cond_reglement_code, cr.libelle as cond_reglement_label, cr.libelle_facture as cond_reglement_doc,";
		$sql .= " p.code as mode_reglement_code, p.libelle as mode_reglement_libelle";
		$sql .= ', c.fk_incoterms, c.location_incoterms';
		$sql .= ', i.libelle as label_incoterms';
		$sql .= " FROM ".$this->db->prefix()."commande_fournisseur as c";
		$sql .= " LEFT JOIN ".$this->db->prefix()."c_payment_term as cr ON c.fk_cond_reglement = cr.rowid";
		$sql .= " LEFT JOIN ".$this->db->prefix()."c_paiement as p ON c.fk_mode_reglement = p.id";
		$sql .= " LEFT JOIN ".$this->db->prefix()."c_input_method as cm ON cm.rowid = c.fk_input_method";
		$sql .= ' LEFT JOIN '.$this->db->prefix().'c_incoterms as i ON c.fk_incoterms = i.rowid';

		if (empty($id)) {
			$sql .= " WHERE c.entity IN (".getEntity('supplier_order').")";
		} else {
			$sql .= " WHERE c.rowid=".((int) $id);
		}

		if ($ref) {
			$sql .= " AND c.ref='".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if (!$obj) {
				$this->error = 'Bill with id '.$id.' not found';
				dol_syslog(get_class($this).'::fetch '.$this->error);
				return 0;
			}

			$this->id = $obj->rowid;
			$this->entity = $obj->entity;

			$this->ref = $obj->ref;
			$this->ref_supplier = $obj->ref_supplier;
			$this->socid = $obj->fk_soc;
			$this->fourn_id = $obj->fk_soc;
			$this->statut = $obj->status;	// deprecated
			$this->status = $obj->status;
			$this->billed = $obj->billed;
			$this->user_author_id = $obj->user_author_id;
			$this->user_validation_id = $obj->user_validation_id;
			$this->user_approve_id = $obj->user_approve_id;
			$this->user_approve_id2 = $obj->user_approve_id2;
			$this->total_ht				= $obj->total_ht;
			$this->total_tva			= $obj->total_tva;
			$this->total_localtax1		= $obj->localtax1;
			$this->total_localtax2		= $obj->localtax2;
			$this->total_ttc			= $obj->total_ttc;
			$this->date_creation = $this->db->jdate($obj->date_creation);
			$this->date_valid = $this->db->jdate($obj->date_valid);
			$this->date_approve			= $this->db->jdate($obj->date_approve);
			$this->date_approve2		= $this->db->jdate($obj->date_approve2);
			$this->date_commande		= $this->db->jdate($obj->date_commande); // date we make the order to supplier
			if (isset($obj->date_commande)) {
				$this->date = $this->date_commande;
			} else {
				$this->date = $this->date_creation;
			}
			$this->delivery_date = $this->db->jdate($obj->delivery_date);
			$this->remise_percent = $obj->remise_percent;
			$this->methode_commande_id = $obj->fk_input_method;
			$this->methode_commande = $obj->methode_commande;

			$this->source = $obj->source;
			$this->fk_project = $obj->fk_project;
			$this->cond_reglement_id = $obj->fk_cond_reglement;
			$this->cond_reglement_code = $obj->cond_reglement_code;
			$this->cond_reglement = $obj->cond_reglement_label;			// deprecated
			$this->cond_reglement_label = $obj->cond_reglement_label;
			$this->cond_reglement_doc = $obj->cond_reglement_doc;
			$this->fk_account = $obj->fk_account;
			$this->mode_reglement_id = $obj->fk_mode_reglement;
			$this->mode_reglement_code = $obj->mode_reglement_code;
			$this->mode_reglement = $obj->mode_reglement_libelle;
			$this->note = $obj->note_private; // deprecated
			$this->note_private = $obj->note_private;
			$this->note_public = $obj->note_public;
			$this->model_pdf = $obj->model_pdf;

			//Incoterms
			$this->fk_incoterms = $obj->fk_incoterms;
			$this->location_incoterms = $obj->location_incoterms;
			$this->label_incoterms = $obj->label_incoterms;

			// Multicurrency
			$this->fk_multicurrency 		= $obj->fk_multicurrency;
			$this->multicurrency_code = $obj->multicurrency_code;
			$this->multicurrency_tx 		= $obj->multicurrency_tx;
			$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
			$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
			$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

			$this->extraparams = isset($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();

			$this->db->free($resql);

			// Retrieve all extrafield
			// fetch optionals attributes and labels
			$this->fetch_optionals();

			// Lines
			$result = $this->fetch_lines();

			if ($result < 0) {
				return -1;
			} else {
				return 1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load array lines
	 *
	 * @param		int		$only_product	Return only physical products
	 * @return		int						Return integer <0 if KO, >0 if OK
	 */
	public function fetch_lines($only_product = 0)
	{
		// phpcs:enable

		$this->lines = array();

		$sql = "SELECT l.rowid, l.fk_commande, l.ref as ref_supplier, l.fk_product, l.product_type, l.label, l.description, l.qty,";
		$sql .= " l.vat_src_code, l.tva_tx, l.remise_percent, l.subprice,";
		$sql .= " l.localtax1_tx, l. localtax2_tx, l.localtax1_type, l. localtax2_type, l.total_localtax1, l.total_localtax2,";
		$sql .= " l.total_ht, l.total_tva, l.total_ttc, l.info_bits, l.special_code, l.fk_parent_line, l.rang,";
		$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label, p.description as product_desc, p.tobatch as product_tobatch, p.barcode as product_barcode,";
		$sql .= " l.fk_unit,";
		$sql .= " l.date_start, l.date_end,";
		$sql .= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc';
		$sql .= " FROM ".$this->db->prefix()."commande_fournisseurdet as l";
		$sql .= ' LEFT JOIN '.$this->db->prefix().'product as p ON l.fk_product = p.rowid';
		$sql .= " WHERE l.fk_commande = ".((int) $this->id);
		if ($only_product) {
			$sql .= ' AND p.fk_product_type = 0';
		}
		$sql .= " ORDER BY l.rang, l.rowid";
		//print $sql;

		dol_syslog(get_class($this)."::fetch_lines", LOG_DEBUG);

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($result);

				$line = new CommandeFournisseurLigne($this->db);

				$line->id                  = $objp->rowid;
				$line->fk_commande         = $objp->fk_commande;
				$line->desc                = $objp->description;
				$line->description         = $objp->description;
				$line->qty                 = $objp->qty;
				$line->tva_tx              = $objp->tva_tx;
				$line->localtax1_tx		   = $objp->localtax1_tx;
				$line->localtax2_tx		   = $objp->localtax2_tx;
				$line->localtax1_type	   = $objp->localtax1_type;
				$line->localtax2_type	   = $objp->localtax2_type;
				$line->subprice            = $objp->subprice;
				$line->pu_ht = $objp->subprice;
				$line->remise_percent      = $objp->remise_percent;

				$line->vat_src_code        = $objp->vat_src_code;
				$line->total_ht            = $objp->total_ht;
				$line->total_tva           = $objp->total_tva;
				$line->total_localtax1	   = $objp->total_localtax1;
				$line->total_localtax2	   = $objp->total_localtax2;
				$line->total_ttc           = $objp->total_ttc;
				$line->product_type        = $objp->product_type;

				$line->fk_product          = $objp->fk_product;

				$line->libelle             = $objp->product_label; // deprecated
				$line->product_label       = $objp->product_label;
				$line->product_desc        = $objp->product_desc;
				$line->product_tobatch     = $objp->product_tobatch;
				$line->product_barcode     = $objp->product_barcode;

				$line->ref                 = $objp->product_ref; // Ref of product
				$line->product_ref         = $objp->product_ref; // Ref of product
				$line->ref_fourn           = $objp->ref_supplier; // The supplier ref of price when product was added. May have change since
				$line->ref_supplier        = $objp->ref_supplier; // The supplier ref of price when product was added. May have change since

				if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					// TODO We should not fetch this properties into the fetch_lines. This is NOT properties of a line.
					// Move this into another method and call it when required.

					// Take better packaging for $objp->qty (first supplier ref quantity <= $objp->qty)
					$sqlsearchpackage = 'SELECT rowid, packaging FROM '.$this->db->prefix()."product_fournisseur_price";
					$sqlsearchpackage .= ' WHERE entity IN ('.getEntity('product_fournisseur_price').")";
					$sqlsearchpackage .= " AND fk_product = ".((int) $objp->fk_product);
					$sqlsearchpackage .= " AND ref_fourn = '".$this->db->escape($objp->ref_supplier)."'";
					$sqlsearchpackage .= " AND quantity <= ".((float) $objp->qty);	// required to be qualified
					$sqlsearchpackage .= " AND (packaging IS NULL OR packaging = 0 OR packaging <= ".((float) $objp->qty).")";	// required to be qualified
					$sqlsearchpackage .= " AND fk_soc = ".((int) $this->socid);
					$sqlsearchpackage .= " ORDER BY packaging ASC";		// Take the smaller package first
					$sqlsearchpackage .= " LIMIT 1";

					$resqlsearchpackage = $this->db->query($sqlsearchpackage);
					if ($resqlsearchpackage) {
						$objsearchpackage = $this->db->fetch_object($resqlsearchpackage);
						if ($objsearchpackage) {
							$line->fk_fournprice = $objsearchpackage->rowid;
							$line->packaging     = $objsearchpackage->packaging;
						}
					} else {
						$this->error = $this->db->lasterror();
						return -1;
					}
				}

				$line->date_start          = $this->db->jdate($objp->date_start);
				$line->date_end            = $this->db->jdate($objp->date_end);
				$line->fk_unit             = $objp->fk_unit;

				// Multicurrency
				$line->fk_multicurrency = $objp->fk_multicurrency;
				$line->multicurrency_code = $objp->multicurrency_code;
				$line->multicurrency_subprice = $objp->multicurrency_subprice;
				$line->multicurrency_total_ht = $objp->multicurrency_total_ht;
				$line->multicurrency_total_tva = $objp->multicurrency_total_tva;
				$line->multicurrency_total_ttc = $objp->multicurrency_total_ttc;

				$line->info_bits      	   = $objp->info_bits;
				$line->special_code        = $objp->special_code;
				$line->fk_parent_line      = $objp->fk_parent_line;

				$line->rang                = $objp->rang;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$line->fetch_optionals();

				$this->lines[$i] = $line;

				$i++;
			}
			$this->db->free($result);

			return $num;
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			return -1;
		}
	}

	/**
	 *	Validate an order
	 *
	 *	@param	User	$user			Validator User
	 *	@param	int		$idwarehouse	Id of warehouse to use for stock decrease
	 *  @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function valid($user, $idwarehouse = 0, $notrigger = 0)
	{
		global $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		dol_syslog(get_class($this)."::valid");
		$result = 0;
		if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer")))
			|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight("fournisseur", "supplier_order_advance", "validate"))) {
			$this->db->begin();

			// Definition of supplier order numbering model name
			$soc = new Societe($this->db);
			$soc->fetch($this->fourn_id);

			// Check if object has a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
				$num = $this->getNextNumRef($soc);
			} else {
				$num = $this->ref;
			}
			$this->newref = dol_sanitizeFileName($num);

			$sql = 'UPDATE '.$this->db->prefix()."commande_fournisseur";
			$sql .= " SET ref='".$this->db->escape($num)."',";
			$sql .= " fk_statut = ".((int) self::STATUS_VALIDATED).",";
			$sql .= " date_valid='".$this->db->idate(dol_now())."',";
			$sql .= " fk_user_valid = ".((int) $user->id);
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND fk_statut = ".((int) self::STATUS_DRAFT);

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ORDER_SUPPLIER_VALIDATE', $user);
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
					$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'fournisseur/commande/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'fournisseur/commande/".$this->db->escape($this->ref)."' and entity = ".((int) $conf->entity);
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}
					$sql = 'UPDATE '.$this->db->prefix()."ecm_files set filepath = 'fournisseur/commande/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filepath = 'fournisseur/commande/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->fournisseur->commande->dir_output.'/'.$oldref;
					$dirdest = $conf->fournisseur->commande->dir_output.'/'.$newref;
					if (!$error && file_exists($dirsource)) {
						dol_syslog(get_class($this)."::valid rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest)) {
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $newref
							$listoffiles = dol_dir_list($conf->fournisseur->commande->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

			if (!$error) {
				$result = 1;
				$this->status = self::STATUS_VALIDATED;
				$this->statut = self::STATUS_VALIDATED;	// deprecated
				$this->ref = $num;
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = 'NotAuthorized';
			dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Return label of the status of object
	 *
	 *  @param      int		$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=short label + picto
	 *  @return 	string        			Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->statut, $mode, $this->billed);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a status
	 *
	 * 	@param  int		$status		Id statut
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @param  int     $billed     1=Billed
	 *  @return string				Label of status
	 */
	public function LibStatut($status, $mode = 0, $billed = 0)
	{
		// phpcs:enable
		global $langs, $hookmanager;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			$langs->load('orders');

			$this->labelStatus[0] = 'StatusSupplierOrderDraft';
			$this->labelStatus[1] = 'StatusSupplierOrderValidated';
			$this->labelStatus[2] = 'StatusSupplierOrderApproved';
			if (!getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS')) {
				$this->labelStatus[3] = 'StatusSupplierOrderOnProcess';
			} else {
				$this->labelStatus[3] = 'StatusSupplierOrderOnProcessWithValidation';
			}
			$this->labelStatus[4] = 'StatusSupplierOrderReceivedPartially';
			$this->labelStatus[5] = 'StatusSupplierOrderReceivedAll';
			$this->labelStatus[6] = 'StatusSupplierOrderCanceled'; // Approved->Canceled
			$this->labelStatus[7] = 'StatusSupplierOrderCanceled'; // Process running->canceled
			$this->labelStatus[9] = 'StatusSupplierOrderRefused';

			// List of language codes for status
			$this->labelStatusShort[0] = 'StatusSupplierOrderDraftShort';
			$this->labelStatusShort[1] = 'StatusSupplierOrderValidatedShort';
			$this->labelStatusShort[2] = 'StatusSupplierOrderApprovedShort';
			$this->labelStatusShort[3] = 'StatusSupplierOrderOnProcessShort';
			$this->labelStatusShort[4] = 'StatusSupplierOrderReceivedPartiallyShort';
			$this->labelStatusShort[5] = 'StatusSupplierOrderReceivedAllShort';
			$this->labelStatusShort[6] = 'StatusSupplierOrderCanceledShort';
			$this->labelStatusShort[7] = 'StatusSupplierOrderCanceledShort';
			$this->labelStatusShort[9] = 'StatusSupplierOrderRefusedShort';
		}

		$statustrans = array(
			0 => 'status0',
			1 => 'status1b',
			2 => 'status1',
			3 => 'status4',
			4 => 'status4b',
			5 => 'status6',
			6 => 'status9',
			7 => 'status9',
			9 => 'status9',
		);

		$statusClass = 'status0';
		if (!empty($statustrans[$status])) {
			$statusClass = $statustrans[$status];
		}

		$billedtext = '';
		if ($billed) {
			$billedtext = ' - '.$langs->trans("Billed");
		}
		if ($status == 5 && $billed) {
			$statusClass = 'status6';
		}

		$statusLong = $langs->transnoentitiesnoconv($this->labelStatus[$status]).$billedtext;
		$statusShort = $langs->transnoentitiesnoconv($this->labelStatusShort[$status]);

		$parameters = array('status' => $status, 'mode' => $mode, 'billed' => $billed);
		$reshook = $hookmanager->executeHooks('LibStatut', $parameters, $this); // Note that $action and $object may have been modified by hook
		if ($reshook > 0) {
			return $hookmanager->resPrint;
		}

		return dolGetStatus($statusLong, $statusShort, '', $statusClass, $mode);
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$langs->loadLangs(['bills', 'orders']);

		$datas = [];
		$nofetch = !empty($params['nofetch']);

		if ($user->hasRight("fournisseur", "commande", "read")) {
			$datas['picto'] = '<u class="paddingrightonly">'.$langs->trans("SupplierOrder").'</u>';
			if (isset($this->statut)) {
				$datas['picto'] .= ' '.$this->getLibStatut(5);
			}
			if (!empty($this->ref)) {
				$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
			}
			if (!empty($this->ref_supplier)) {
				$datas['refsupplier'] = '<br><b>'.$langs->trans('RefSupplier').':</b> '.$this->ref_supplier;
			}
			if (!$nofetch) {
				$langs->load('companies');
				if (empty($this->thirdparty)) {
					$this->fetch_thirdparty();
				}
				$datas['supplier'] = '<br><b>'.$langs->trans('Supplier').':</b> '.$this->thirdparty->getNomUrl(1, '', 0, 1);
			}
			if (!empty($this->total_ht)) {
				$datas['totalht'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_tva)) {
				$datas['totaltva'] = '<br><b>'.$langs->trans('VAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_ttc)) {
				$datas['totalttc'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->date)) {
				$datas['date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->date, 'day');
			}
			if (!empty($this->delivery_date)) {
				$datas['deliverydate'] = '<br><b>'.$langs->trans('DeliveryDate').':</b> '.dol_print_date($this->delivery_date, 'dayhour');
			}
		}
		return $datas;
	}

	/**
	 *	Return clicable name (with picto eventually)
	 *
	 *	@param		int		$withpicto					0=No picto, 1=Include picto into link, 2=Only picto
	 *	@param		string	$option						On what the link points
	 *  @param	    int   	$notooltip					1=Disable tooltip
	 *  @param      int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param		int		$addlinktonotes				Add link to show notes
	 *	@return		string								Chain with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $save_lastsearch_value = -1, $addlinktonotes = 0)
	{
		global $langs, $user, $hookmanager;

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
			'nofetch' => 1
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

		$url = DOL_URL_ROOT.'/fourn/commande/card.php?id='.$this->id;

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

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowOrder");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
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
				$result .= '<a href="'.DOL_URL_ROOT.'/fourn/commande/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow).'">';
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
	 *  Returns the next order reference not used, based on the
	 *  numbering model defined within COMMANDE_SUPPLIER_ADDON_NUMBER
	 *
	 *  @param	    Societe		$soc  		company object
	 *  @return     string|int              free reference for the invoice. '', -1 or -2 if error.
	 */
	public function getNextNumRef($soc)
	{
		global $langs, $conf;
		$langs->load("orders");

		if (getDolGlobalString('COMMANDE_SUPPLIER_ADDON_NUMBER')) {
			$mybool = false;

			$file = getDolGlobalString('COMMANDE_SUPPLIER_ADDON_NUMBER').'.php';
			$classname = getDolGlobalString('COMMANDE_SUPPLIER_ADDON_NUMBER');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/supplier_order/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if ($mybool === false) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			$numref = $obj->getNextValue($soc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				return -1;
			}
		} else {
			$this->error = "Error_COMMANDE_SUPPLIER_ADDON_NotDefined";
			return -2;
		}
	}
	/**
	 *	Class invoiced the supplier order
	 *
	 *  @param      User        $user       Object user making the change
	 *	@return     int     	            Return integer <0 if KO, 0 if already billed,  >0 if OK
	 */
	public function classifyBilled(User $user)
	{
		$error = 0;

		if ($this->billed) {
			return 0;
		}

		$this->db->begin();

		$sql = 'UPDATE '.$this->db->prefix().'commande_fournisseur SET billed = 1';
		$sql .= " WHERE rowid = ".((int) $this->id).' AND fk_statut > '.self::STATUS_DRAFT;

		if ($this->db->query($sql)) {
			if (!$error) {
				// Call trigger
				$result = $this->call_trigger('ORDER_SUPPLIER_CLASSIFY_BILLED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->billed = 1;

				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_print_error($this->db);

			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * 	Approve a supplier order
	 *
	 *	@param	User	$user			Object user
	 *	@param	int		$idwarehouse	Id of warhouse for stock change
	 *  @param	int		$secondlevel	0=Standard approval, 1=Second level approval (used when option SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED is set)
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function approve($user, $idwarehouse = 0, $secondlevel = 0)
	{
		global $langs, $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		dol_syslog(get_class($this)."::approve");

		if ($user->hasRight("fournisseur", "commande", "approuver")) {
			$now = dol_now();

			$this->db->begin();

			// Definition of order numbering model name
			$soc = new Societe($this->db);
			$soc->fetch($this->fourn_id);

			// Check if object has a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
				$num = $this->getNextNumRef($soc);
			} else {
				$num = $this->ref;
			}
			$this->newref = dol_sanitizeFileName($num);

			// Do we have to change status now ? (If double approval is required and first approval, we keep status to 1 = validated)
			$movetoapprovestatus = true;
			$comment = '';

			$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur";
			$sql .= " SET ref='".$this->db->escape($num)."',";
			if (empty($secondlevel)) {	// standard or first level approval
				$sql .= " date_approve='".$this->db->idate($now)."',";
				$sql .= " fk_user_approve = ".$user->id;
				if (getDolGlobalString('SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED') && $this->total_ht >= $conf->global->SUPPLIER_ORDER_3_STEPS_TO_BE_APPROVED) {
					if (empty($this->user_approve_id2)) {
						$movetoapprovestatus = false; // second level approval not done
						$comment = ' (first level)';
					}
				}
			} else { // request a second level approval
				$sql .= " date_approve2='".$this->db->idate($now)."',";
				$sql .= " fk_user_approve2 = ".((int) $user->id);
				if (empty($this->user_approve_id)) {
					$movetoapprovestatus = false; // first level approval not done
				}
				$comment = ' (second level)';
			}
			// If double approval is required and first approval, we keep status to 1 = validated
			if ($movetoapprovestatus) {
				$sql .= ", fk_statut = ".self::STATUS_ACCEPTED;
			} else {
				$sql .= ", fk_statut = ".self::STATUS_VALIDATED;
			}
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND fk_statut = ".self::STATUS_VALIDATED;

			if ($this->db->query($sql)) {
				if (getDolGlobalString('SUPPLIER_ORDER_AUTOADD_USER_CONTACT')) {
					$result = $this->add_contact($user->id, 'SALESREPFOLL', 'internal', 1);
					if ($result < 0 && $result != -2) {	// -2 means already exists
						$error++;
					}
				}

				// If stock is incremented on validate order, we must increment it
				if (!$error && $movetoapprovestatus && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER')) {
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$langs->load("agenda");

					$cpt = count($this->lines);
					for ($i = 0; $i < $cpt; $i++) {
						// Product with reference
						if ($this->lines[$i]->fk_product > 0) {
							$this->line = $this->lines[$i];
							$mouvP = new MouvementStock($this->db);
							$mouvP->origin = &$this;
							$mouvP->setOrigin($this->element, $this->id);
							// We decrement stock of product (and sub-products)
							$up_ht_disc = $this->lines[$i]->subprice;
							if (!empty($this->lines[$i]->remise_percent) && !getDolGlobalString('STOCK_EXCLUDE_DISCOUNT_FOR_PMP')) {
								$up_ht_disc = price2num($up_ht_disc * (100 - $this->lines[$i]->remise_percent) / 100, 'MU');
							}
							$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $up_ht_disc, $langs->trans("OrderApprovedInDolibarr", $this->ref));
							if ($result < 0) {
								$error++;
							}
							unset($this->line);
						}
					}
				}

				if (!$error) {
					// Call trigger
					$result = $this->call_trigger('ORDER_SUPPLIER_APPROVE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					$this->ref = $this->newref;

					if ($movetoapprovestatus) {
						$this->statut = self::STATUS_ACCEPTED;
					} else {
						$this->statut = self::STATUS_VALIDATED;
					}
					if (empty($secondlevel)) {	// standard or first level approval
						$this->date_approve = $now;
						$this->user_approve_id = $user->id;
					} else { // request a second level approval
						$this->date_approve2 = $now;
						$this->user_approve_id2 = $user->id;
					}

					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::approve Not Authorized", LOG_ERR);
		}
		return -1;
	}

	/**
	 * 	Refuse an order
	 *
	 * 	@param		User	$user		User making action
	 *	@return		int					0 if Ok, <0 if Ko
	 */
	public function refuse($user)
	{
		global $conf, $langs;

		$error = 0;

		dol_syslog(get_class($this)."::refuse");
		$result = 0;
		if ($user->hasRight("fournisseur", "commande", "approuver")) {
			$this->db->begin();

			$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur SET fk_statut = ".self::STATUS_REFUSED;
			$sql .= " WHERE rowid = ".((int) $this->id);

			if ($this->db->query($sql)) {
				$result = 0;

				if ($error == 0) {
					// Call trigger
					$result = $this->call_trigger('ORDER_SUPPLIER_REFUSE', $user);
					if ($result < 0) {
						$error++;
						$this->db->rollback();
					} else {
						$this->db->commit();
					}
					// End call triggers
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::refuse Error -1");
				$result = -1;
			}
		} else {
			dol_syslog(get_class($this)."::refuse Not Authorized");
		}
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Cancel an approved order.
	 *	The cancellation is done after approval
	 *
	 * 	@param	User	$user			User making action
	 *	@param	int		$idwarehouse	Id warehouse to use for stock change (not used for supplier orders).
	 * 	@return	int						>0 if Ok, <0 if Ko
	 */
	public function Cancel($user, $idwarehouse = -1)
	{
		// phpcs:enable
		global $langs, $conf;

		$error = 0;

		//dol_syslog("CommandeFournisseur::Cancel");
		$result = 0;
		if ($user->hasRight("fournisseur", "commande", "commander")) {
			$statut = self::STATUS_CANCELED;

			$this->db->begin();

			$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur SET fk_statut = ".((int) $statut);
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this)."::cancel", LOG_DEBUG);
			if ($this->db->query($sql)) {
				$result = 0;

				// Call trigger
				$result = $this->call_trigger('ORDER_SUPPLIER_CANCEL', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				if ($error == 0) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::cancel ".$this->error);
				return -1;
			}
		} else {
			dol_syslog(get_class($this)."::cancel Not Authorized");
			return -1;
		}
	}

	/**
	 * 	Submit a supplier order to supplier
	 *
	 * 	@param		User	$user		User making change
	 * 	@param		integer	$date		Date
	 * 	@param		int		$methode	Method
	 * 	@param		string	$comment	Comment
	 * 	@return		int			        Return integer <0 if KO, >0 if OK
	 */
	public function commande($user, $date, $methode, $comment = '')
	{
		global $langs;
		dol_syslog(get_class($this)."::commande");
		$error = 0;
		if ($user->hasRight("fournisseur", "commande", "commander")) {
			$this->db->begin();

			$newnoteprivate = $this->note_private;
			if ($comment) {
				$newnoteprivate = dol_concatdesc($newnoteprivate, $langs->trans("Comment").': '.$comment);
			}

			$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur";
			$sql .= " SET fk_statut=".self::STATUS_ORDERSENT.", fk_input_method=".$methode.", date_commande='".$this->db->idate($date)."', ";
			$sql .= " note_private='".$this->db->escape($newnoteprivate)."'";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::commande", LOG_DEBUG);
			if ($this->db->query($sql)) {
				$this->statut = self::STATUS_ORDERSENT;
				$this->methode_commande_id = $methode;
				$this->date_commande = $date;
				$this->context = array('comments' => $comment);

				// Call trigger
				$result = $this->call_trigger('ORDER_SUPPLIER_SUBMIT', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			} else {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->db->lasterror();
			}

			if (!$error) {
				$this->db->commit();
			} else {
				$this->db->rollback();
			}
		} else {
			$error++;
			$this->error = $langs->trans('NotAuthorized');
			$this->errors[] = $langs->trans('NotAuthorized');
			dol_syslog(get_class($this)."::commande User not Authorized", LOG_WARNING);
		}

		return ($error ? -1 : 1);
	}

	/**
	 *  Create order with draft status
	 *
	 *  @param      User	$user       User making creation
	 *	@param		int		$notrigger	Disable all triggers
	 *  @return     int         		Return integer <0 if KO, Id of supplier order if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $langs, $conf, $hookmanager;

		$this->db->begin();

		$error = 0;
		$now = dol_now();

		// set tmp vars
		$date = ($this->date_commande ? $this->date_commande : $this->date); // in case of date is set
		if (empty($date)) {
			$date = $now;
		}
		$delivery_date = $this->delivery_date;

		// Clean parameters
		if (empty($this->source)) {
			$this->source = 0;
		}

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

		$this->statut = self::STATUS_DRAFT;	// deprecated
		$this->status = self::STATUS_DRAFT;

		$sql = "INSERT INTO ".$this->db->prefix()."commande_fournisseur (";
		$sql .= "ref";
		$sql .= ", ref_supplier";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", entity";
		$sql .= ", fk_soc";
		$sql .= ", fk_projet";
		$sql .= ", date_creation";
		$sql .= ", date_livraison";
		$sql .= ", fk_user_author";
		$sql .= ", fk_statut";
		$sql .= ", source";
		$sql .= ", model_pdf";
		$sql .= ", fk_mode_reglement";
		$sql .= ", fk_cond_reglement";
		$sql .= ", fk_account";
		$sql .= ", fk_incoterms, location_incoterms";
		$sql .= ", fk_multicurrency";
		$sql .= ", multicurrency_code";
		$sql .= ", multicurrency_tx";
		$sql .= ") ";
		$sql .= " VALUES (";
		$sql .= "'(PROV)'";
		$sql .= ", ".(isset($this->ref_supplier) ? "'".$this->db->escape($this->ref_supplier)."'" : "NULL");
		$sql .= ", '".$this->db->escape($this->note_private)."'";
		$sql .= ", '".$this->db->escape($this->note_public)."'";
		$sql .= ", ".setEntity($this);
		$sql .= ", ".((int) $this->socid);
		$sql .= ", ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
		$sql .= ", '".$this->db->idate($date)."'";
		$sql .= ", ".($delivery_date ? "'".$this->db->idate($delivery_date)."'" : "null");
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".self::STATUS_DRAFT;
		$sql .= ", ".((int) $this->source);
		$sql .= ", '".$this->db->escape(getDolGlobalString('COMMANDE_SUPPLIER_ADDON_PDF'))."'";
		$sql .= ", ".($this->mode_reglement_id > 0 ? $this->mode_reglement_id : 'null');
		$sql .= ", ".($this->cond_reglement_id > 0 ? $this->cond_reglement_id : 'null');
		$sql .= ", ".($this->fk_account > 0 ? $this->fk_account : 'NULL');
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".(float) $this->multicurrency_tx;
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."commande_fournisseur");

			if ($this->id) {
				$num = count($this->lines);

				// insert products details into database
				for ($i = 0; $i < $num; $i++) {
					$line = $this->lines[$i];
					if (!is_object($line)) {
						$line = (object) $line;
					}

					//$this->special_code = $line->special_code; // TODO : remove this in 9.0 and add special_code param to addline()

					// This include test on qty if option SUPPLIER_ORDER_WITH_NOPRICEDEFINED is not set
					$result = $this->addline(
						$line->desc,
						$line->subprice,
						$line->qty,
						$line->tva_tx,
						$line->localtax1_tx,
						$line->localtax2_tx,
						$line->fk_product,
						0,
						$line->ref_fourn, // $line->ref_fourn comes from field ref into table of lines. Value may ba a ref that does not exists anymore, so we first try with value of product
						$line->remise_percent,
						'HT',
						0,
						$line->product_type,
						$line->info_bits,
						false,
						$line->date_start,
						$line->date_end,
						$line->array_options,
						$line->fk_unit,
						$line->multicurrency_subprice,  // pu_ht_devise
						$line->origin,     // origin
						$line->origin_id,  // origin_id
						$line->rang,       // rang
						$line->special_code
					);
					if ($result < 0) {
						dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING); // do not use dol_print_error here as it may be a functional error
						$this->db->rollback();
						return -1;
					}
				}

				$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur";
				$sql .= " SET ref='(PROV".$this->id.")'";
				$sql .= " WHERE rowid=".((int) $this->id);

				dol_syslog(get_class($this)."::create", LOG_DEBUG);
				if ($this->db->query($sql)) {
					// Add link with price request and supplier order
					if ($this->id) {
						$this->ref = "(PROV".$this->id.")";

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
											dol_print_error($this->db);
											$error++;
										}
									}
								} else { // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
									$origin_id = $tmp_origin_id;
									$ret = $this->add_object_linked($origin, $origin_id);
									if (!$ret) {
										dol_print_error($this->db);
										$error++;
									}
								}
							}
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
						$result = $this->call_trigger('ORDER_SUPPLIER_CREATE', $user);
						if ($result < 0) {
							$this->db->rollback();

							return -1;
						}
						// End call triggers
					}

					$this->db->commit();
					return $this->id;
				} else {
					$this->error = $this->db->lasterror();
					$this->db->rollback();

					return -2;
				}
			} else {
				$this->error = 'Failed to get ID of inserted line';

				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();

			return -1;
		}
	}

	/**
	 *	Update Supplier Order
	 *
	 *	@param      User	$user        	User that modify
	 *	@param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	@return     int      			   	Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $conf;

		$error = 0;

		// Clean parameters
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->ref_supplier)) {
			$this->ref_supplier = trim($this->ref_supplier);
		}
		if (isset($this->note_private)) {
			$this->note_private = trim($this->note_private);
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

		// Update request
		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";

		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_supplier=".(isset($this->ref_supplier) ? "'".$this->db->escape($this->ref_supplier)."'" : "null").",";
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
		$sql .= " fk_user_author=".(isset($this->user_author_id) ? $this->user_author_id : "null").",";
		$sql .= " fk_user_valid=".(isset($this->user_validation_id) && $this->user_validation_id > 0 ? $this->user_validation_id : "null").",";
		$sql .= " fk_projet=".(isset($this->fk_project) ? $this->fk_project : "null").",";
		$sql .= " fk_cond_reglement=".(isset($this->cond_reglement_id) ? $this->cond_reglement_id : "null").",";
		$sql .= " fk_mode_reglement=".(isset($this->mode_reglement_id) ? $this->mode_reglement_id : "null").",";
		$sql .= " date_livraison=".(strval($this->delivery_date) != '' ? "'".$this->db->idate($this->delivery_date)."'" : 'null').",";
		//$sql .= " fk_shipping_method=".(isset($this->shipping_method_id) ? $this->shipping_method_id : "null").",";
		$sql .= " fk_account=".($this->fk_account > 0 ? $this->fk_account : "null").",";
		//$sql .= " fk_input_reason=".($this->demand_reason_id > 0 ? $this->demand_reason_id : "null").",";
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
			$result = $this->call_trigger('ORDER_SUPPLIER_MODIFY', $user);
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
	 *	Load an object from its id and create a new one in database
	 *
	 *  @param	    User	$user		User making the clone
	 *	@param		int		$socid		Id of thirdparty
	 *  @param 		int		$notrigger  Disable all triggers
	 *	@return		int					New id of clone
	 */
	public function createFromClone(User $user, $socid = 0, $notrigger = 0)
	{
		global $conf, $user, $hookmanager;

		$error = 0;

		$this->db->begin();

		// get extrafields so they will be clone
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
				$this->mode_reglement_id	= (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$this->fk_project = 0;
				$this->fk_delivery_address = 0;
			}

			// TODO Change product price if multi-prices
		}

		$this->id = 0;
		$this->statut = self::STATUS_DRAFT;

		// Clear fields
		$this->user_author_id     = $user->id;
		$this->user_validation_id = 0;

		$this->date               = dol_now();
		$this->date_creation      = 0;
		$this->date_validation    = 0;
		$this->date_commande      = 0;
		$this->ref_supplier       = '';
		$this->user_approve_id    = 0;
		$this->user_approve_id2   = 0;
		$this->date_approve       = 0;
		$this->date_approve2      = 0;

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $this->create($user, $notrigger);
		if ($result < 0) {
			$error++;
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
	 *	Add order line
	 *
	 *	@param      string		$desc            		Description
	 *	@param      float		$pu_ht              	Unit price (used if $price_base_type is 'HT')
	 *	@param      float		$qty             		Quantity
	 *	@param      float		$txtva           		VAT Rate
	 *	@param      float		$txlocaltax1        	Localtax1 tax
	 *	@param      float		$txlocaltax2        	Localtax2 tax
	 *	@param      int			$fk_product      		Id product
	 *	@param      int			$fk_prod_fourn_price	Id supplier price
	 *	@param      string		$ref_supplier			Supplier reference price
	 *	@param      float		$remise_percent  		Remise
	 *	@param      string		$price_base_type		HT or TTC
	 *	@param		float		$pu_ttc					Unit price TTC (used if $price_base_type is 'TTC')
	 *	@param		int			$type					Type of line (0=product, 1=service)
	 *	@param		int			$info_bits				More information
	 *	@param		int			$notrigger				Disable triggers
	 *	@param		int			$date_start				Date start of service
	 *	@param		int			$date_end				Date end of service
	 *	@param		array		$array_options			extrafields array
	 *	@param 		int|null	$fk_unit 				Code of the unit to use. Null to use the default one
	 *	@param 		int|string		$pu_ht_devise			Amount in currency
	 *	@param		string		$origin					'order', ...
	 *	@param		int			$origin_id				Id of origin object
	 *	@param		int			$rang					Rank
	 *	@param		int			$special_code			Special code
	 *	@return     int     	        				Return integer <=0 if KO, >0 if OK
	 */
	public function addline($desc, $pu_ht, $qty, $txtva, $txlocaltax1 = 0.0, $txlocaltax2 = 0.0, $fk_product = 0, $fk_prod_fourn_price = 0, $ref_supplier = '', $remise_percent = 0.0, $price_base_type = 'HT', $pu_ttc = 0.0, $type = 0, $info_bits = 0, $notrigger = 0, $date_start = null, $date_end = null, $array_options = [], $fk_unit = null, $pu_ht_devise = 0, $origin = '', $origin_id = 0, $rang = -1, $special_code = 0)
	{
		global $langs, $mysoc, $conf;

		dol_syslog(get_class($this)."::addline $desc, $pu_ht, $qty, $txtva, $txlocaltax1, $txlocaltax2, $fk_product, $fk_prod_fourn_price, $ref_supplier, $remise_percent, $price_base_type, $pu_ttc, $type, $info_bits, $notrigger, $date_start, $date_end, $fk_unit, $pu_ht_devise, $origin, $origin_id");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		if ($this->statut == self::STATUS_DRAFT) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

			// Clean parameters
			if (empty($qty)) {
				$qty = 0;
			}
			if (!$info_bits) {
				$info_bits = 0;
			}
			if (empty($txtva)) {
				$txtva = 0;
			}
			if (empty($rang)) {
				$rang = 0;
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

			$remise_percent = price2num($remise_percent);
			$qty = price2num($qty);
			$pu_ht = price2num($pu_ht);
			$pu_ht_devise = price2num($pu_ht_devise);
			$pu_ttc = price2num($pu_ttc);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
			}
			$txlocaltax1 = price2num($txlocaltax1);
			$txlocaltax2 = price2num($txlocaltax2);
			if ($price_base_type == 'HT') {
				$pu = $pu_ht;
			} else {
				$pu = $pu_ttc;
			}
			$desc = trim($desc);

			// Check parameters
			if ($qty < 0 && !$fk_product) {
				$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product"));
				return -1;
			}
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
			$label = '';	// deprecated

			if ($fk_product > 0) {
				if (getDolGlobalString('SUPPLIER_ORDER_WITH_PREDEFINED_PRICES_ONLY')) {	// Not the common case
					// Check quantity is enough
					dol_syslog(get_class($this)."::addline we check supplier prices fk_product=".$fk_product." fk_prod_fourn_price=".$fk_prod_fourn_price." qty=".$qty." ref_supplier=".$ref_supplier);
					$prod = new ProductFournisseur($this->db);
					if ($prod->fetch($fk_product) > 0) {
						$product_type = $prod->type;
						$label = $prod->label;

						// We use 'none' instead of $ref_supplier, because fourn_ref may not exists anymore. So we will take the first supplier price ok.
						// If we want a dedicated supplier price, we must provide $fk_prod_fourn_price.
						$result = $prod->get_buyprice($fk_prod_fourn_price, $qty, $fk_product, 'none', (isset($this->fk_soc) ? $this->fk_soc : $this->socid)); // Search on couple $fk_prod_fourn_price/$qty first, then on triplet $qty/$fk_product/$ref_supplier/$this->fk_soc

						// If supplier order created from sales order, we take best supplier price
						// If $pu (defined previously from pu_ht or pu_ttc) is not defined at all, we also take the best supplier price
						if ($result > 0 && ($origin == 'commande' || $pu === '')) {
							$pu = $prod->fourn_pu; // Unit price supplier price set by get_buyprice
							$ref_supplier = $prod->ref_supplier; // Ref supplier price set by get_buyprice
							// is remise percent not keyed but present for the product we add it
							if ($remise_percent == 0 && $prod->remise_percent != 0) {
								$remise_percent = $prod->remise_percent;
							}
						}
						if ($result == 0) {                   // If result == 0, we failed to found the supplier reference price
							$langs->load("errors");
							$this->error = "Ref ".$prod->ref." ".$langs->trans("ErrorQtyTooLowForThisSupplier");
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline we did not found supplier price, so we can't guess unit price");
							//$pu    = $prod->fourn_pu;     // We do not overwrite unit price
							//$ref   = $prod->ref_fourn;    // We do not overwrite ref supplier price
							return -1;
						}
						if ($result == -1) {
							$langs->load("errors");
							$this->error = "Ref ".$prod->ref." ".$langs->trans("ErrorQtyTooLowForThisSupplier");
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_DEBUG);
							return -1;
						}
						if ($result < -1) {
							$this->error = $prod->error;
							$this->db->rollback();
							dol_syslog(get_class($this)."::addline result=".$result." - ".$this->error, LOG_ERR);
							return -1;
						}
					} else {
						$this->error = $prod->error;
						$this->db->rollback();
						return -1;
					}
				}

				// Predefine quantity according to packaging
				if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					$prod = new Product($this->db);
					$prod->get_buyprice($fk_prod_fourn_price, $qty, $fk_product, 'none', (empty($this->fk_soc) ? $this->socid : $this->fk_soc));

					if ($qty < $prod->packaging) {
						$qty = $prod->packaging;
					} else {
						if (!empty($prod->packaging) && ($qty % $prod->packaging) > 0) {
							$coeff = intval($qty / $prod->packaging) + 1;
							$qty = $prod->packaging * $coeff;
							setEventMessages($langs->trans('QtyRecalculatedWithPackaging'), null, 'mesgs');
						}
					}
				}
			}

			if (isModEnabled("multicurrency") && $pu_ht_devise > 0) {
				$pu = 0;
			}

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $mysoc, $this->thirdparty);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);

			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu = $pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			$localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];

			if ($rang < 0) {
				$rangmax = $this->line_max();
				$rang = $rangmax + 1;
			}

			// Insert line
			$this->line = new CommandeFournisseurLigne($this->db);

			$this->line->context = $this->context;

			$this->line->fk_commande = $this->id;
			$this->line->label = $label;
			$this->line->ref_fourn = $ref_supplier;
			$this->line->ref_supplier = $ref_supplier;
			$this->line->desc = $desc;
			$this->line->qty = $qty;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx = ($total_localtax1 ? $localtaxes_type[1] : 0);
			$this->line->localtax2_tx = ($total_localtax2 ? $localtaxes_type[3] : 0);
			$this->line->localtax1_type = $localtax1_type;
			$this->line->localtax2_type = $localtax2_type;
			$this->line->fk_product = $fk_product;
			$this->line->product_type = $product_type;
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice = $pu_ht;
			$this->line->rang = $rang;
			$this->line->info_bits = $info_bits;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->total_ht = $total_ht;
			$this->line->total_tva = $total_tva;
			$this->line->total_localtax1 = $total_localtax1;
			$this->line->total_localtax2 = $total_localtax2;
			$this->line->total_ttc = $total_ttc;
			$this->line->product_type = $type;
			$this->line->special_code   = (!empty($special_code) ? $special_code : 0);
			$this->line->origin = $origin;
			$this->line->origin_id = $origin_id;
			$this->line->fk_unit = $fk_unit;

			$this->line->date_start = $date_start;
			$this->line->date_end = $date_end;

			// Multicurrency
			$this->line->fk_multicurrency = $this->fk_multicurrency;
			$this->line->multicurrency_code = $this->multicurrency_code;
			$this->line->multicurrency_subprice	= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			$this->line->subprice = $pu_ht;
			$this->line->price = $this->line->subprice;

			$this->line->remise_percent = $remise_percent;

			if (is_array($array_options) && count($array_options) > 0) {
				$this->line->array_options = $array_options;
			}

			$result = $this->line->insert($notrigger);
			if ($result > 0) {
				// Reorder if child line
				if (!empty($this->line->fk_parent_line)) {
					$this->line_order(true, 'DESC');
				} elseif ($rang > 0 && $rang <= count($this->lines)) { // Update all rank of all other lines
					$linecount = count($this->lines);
					for ($ii = $rang; $ii <= $linecount; $ii++) {
						$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
					}
				}

				// Mise a jour information denormalisees au niveau de la commande meme
				$result = $this->update_price(1, 'auto', 0, $this->thirdparty); // This method is designed to add line from user input so total calculation must be done using 'auto' mode.
				if ($result > 0) {
					$this->db->commit();
					return $this->line->id;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;
				dol_syslog(get_class($this)."::addline error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		return -1;
	}


	/**
	 * Save a receiving into the tracking table of receiving (receptiondet_batch) and add product into stock warehouse.
	 *
	 * @param 	User		$user					User object making change
	 * @param 	int			$product				Id of product to dispatch
	 * @param 	double		$qty					Qty to dispatch
	 * @param 	int			$entrepot				Id of warehouse to add product
	 * @param 	double		$price					Unit Price for PMP value calculation (Unit price without Tax and taking into account discount)
	 * @param	string		$comment				Comment for stock movement
	 * @param	int|string	$eatby					eat-by date
	 * @param	int|string	$sellby					sell-by date
	 * @param	string		$batch					Lot number
	 * @param	int			$fk_commandefourndet	Id of supplier order line
	 * @param	int			$notrigger          	1 = notrigger
	 * @param	int			$fk_reception          	Id of reception to link
	 * @return 	int						Return integer <0 if KO, >0 if OK
	 */
	public function dispatchProduct($user, $product, $qty, $entrepot, $price = 0, $comment = '', $eatby = '', $sellby = '', $batch = '', $fk_commandefourndet = 0, $notrigger = 0, $fk_reception = 0)
	{
		global $conf, $langs;

		$error = 0;
		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

		// Check parameters (if test are wrong here, there is bug into caller)
		if ($entrepot <= 0) {
			$this->error = 'ErrorBadValueForParameterWarehouse';
			return -1;
		}
		if ($qty == 0) {
			$this->error = 'ErrorBadValueForParameterQty';
			return -1;
		}

		$dispatchstatus = 1;
		if (getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS')) {
			$dispatchstatus = 0; // Setting dispatch status (a validation step after receiving products) will be done manually to 1 or 2 if this option is on
		}

		$now = dol_now();

		$inventorycode = dol_print_date(dol_now(), 'dayhourlog');

		if (($this->statut == self::STATUS_ORDERSENT || $this->statut == self::STATUS_RECEIVED_PARTIALLY || $this->statut == self::STATUS_RECEIVED_COMPLETELY)) {
			$this->db->begin();

			$sql = "INSERT INTO ".$this->db->prefix()."receptiondet_batch";
			$sql .= " (fk_element, fk_product, qty, fk_entrepot, fk_user, datec, fk_elementdet, status, comment, eatby, sellby, batch, fk_reception) VALUES";
			$sql .= " ('".$this->id."','".$product."','".$qty."',".($entrepot > 0 ? "'".$entrepot."'" : "null").",'".$user->id."','".$this->db->idate($now)."','".$fk_commandefourndet."', ".$dispatchstatus.", '".$this->db->escape($comment)."', ";
			$sql .= ($eatby ? "'".$this->db->idate($eatby)."'" : "null").", ".($sellby ? "'".$this->db->idate($sellby)."'" : "null").", ".($batch ? "'".$this->db->escape($batch)."'" : "null").", ".($fk_reception > 0 ? "'".$this->db->escape($fk_reception)."'" : "null");
			$sql .= ")";

			dol_syslog(get_class($this)."::dispatchProduct", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$notrigger) {
					global $conf, $langs, $user;
					// Call trigger
					$result = $this->call_trigger('LINEORDER_SUPPLIER_DISPATCH', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			} else {
				$this->error = $this->db->lasterror();
				$error++;
			}

			// If module stock is enabled and the stock increase is done on purchase order dispatching
			if (!$error && $entrepot > 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER')) {
				$mouv = new MouvementStock($this->db);
				if ($product > 0) {
					// $price should take into account discount (except if option STOCK_EXCLUDE_DISCOUNT_FOR_PMP is on)
					$mouv->origin = &$this;
					$mouv->setOrigin($this->element, $this->id);

					// Method change if qty < 0
					if (getDolGlobalString('SUPPLIER_ORDER_ALLOW_NEGATIVE_QTY_FOR_SUPPLIER_ORDER_RETURN') && $qty < 0) {
						$result = $mouv->livraison($user, $product, $entrepot, $qty * (-1), $price, $comment, $now, $eatby, $sellby, $batch, 0, $inventorycode);
					} else {
						$result = $mouv->reception($user, $product, $entrepot, $qty, $price, $comment, $eatby, $sellby, $batch, '', 0, $inventorycode);
					}

					if ($result < 0) {
						$this->error = $mouv->error;
						$this->errors = $mouv->errors;
						dol_syslog(get_class($this)."::dispatchProduct ".$this->error." ".implode(',', $this->errors), LOG_ERR);
						$error++;
					}
				}
			}

			if ($error == 0) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = 'BadStatusForObject';
			return -2;
		}
	}

	/**
	 * 	Delete line
	 *
	 *	@param	int		$idline		Id of line to delete
	 *	@param	int		$notrigger	1=Disable call to triggers
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function deleteLine($idline, $notrigger = 0)
	{
		global $user;

		if ($this->statut == 0) {
			$line = new CommandeFournisseurLigne($this->db);

			if ($line->fetch($idline) <= 0) {
				return 0;
			}

			// check if not yet received
			$dispatchedLines = $this->getDispachedLines();
			foreach ($dispatchedLines as $dispatchLine) {
				if ($dispatchLine['orderlineid'] == $idline) {
					$this->error = "LineAlreadyDispatched";
					$this->errors[] = $this->error;
					return -3;
				}
			}

			if ($line->delete($user, $notrigger) > 0) {
				$this->update_price(1);
				return 1;
			} else {
				$this->setErrorsFromObject($line);
				return -1;
			}
		} else {
			return -2;
		}
	}

	/**
	 *  Delete an order
	 *
	 *	@param	User	$user		Object user
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $langs, $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (empty($notrigger)) {
			// Call trigger
			$result = $this->call_trigger('ORDER_SUPPLIER_DELETE', $user);
			if ($result < 0) {
				$this->errors[] = 'ErrorWhenRunningTrigger';
				dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
			// End call triggers
		}

		// Test we can delete
		$this->fetchObjectLinked(null, 'order_supplier');
		if (!empty($this->linkedObjects) && array_key_exists('reception', $this->linkedObjects)) {
			foreach ($this->linkedObjects['reception'] as $element) {
				if ($element->statut >= 0) {
					$this->errors[] = $langs->trans('ReceptionExist');
					$error++;
					break;
				}
			}
		}

		$main = $this->db->prefix().'commande_fournisseurdet';
		$ef = $main."_extrafields";
		$sql = "DELETE FROM $ef WHERE fk_object IN (SELECT rowid FROM $main WHERE fk_commande = ".((int) $this->id).")";
		dol_syslog(get_class($this)."::delete extrafields lines", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			$error++;
		}

		$sql = "DELETE FROM ".$this->db->prefix()."commande_fournisseurdet WHERE fk_commande =".((int) $this->id);
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			$error++;
		}

		$sql = "DELETE FROM ".$this->db->prefix()."commande_fournisseur WHERE rowid =".((int) $this->id);
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		if ($resql = $this->db->query($sql)) {
			if ($this->db->affected_rows($resql) < 1) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->db->lasterror();
				$error++;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			$error++;
		}

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$this->error = 'FailToDeleteExtraFields';
				$this->errors[] = 'FailToDeleteExtraFields';
				$error++;
				dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
			}
		}

		// Delete linked object
		$res = $this->deleteObjectLinked();
		if ($res < 0) {
			$this->error = 'FailToDeleteObjectLinked';
			$this->errors[] = 'FailToDeleteObjectLinked';
			$error++;
		}

		if (!$error) {
			// Delete record into ECM index (Note that delete is also done when deleting files with the dol_delete_dir_recursive
			$this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
			$this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive

			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if ($conf->fournisseur->commande->dir_output) {
				$dir = $conf->fournisseur->commande->dir_output."/".$ref;
				$file = $dir."/".$ref.".pdf";
				if (file_exists($file)) {
					if (!dol_delete_file($file, 0, 0, 0, $this)) { // For triggers
						$this->error = 'ErrorFailToDeleteFile';
						$this->errors[] = 'ErrorFailToDeleteFile';
						$error++;
					}
				}
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);
					if (!$res) {
						$this->error = 'ErrorFailToDeleteDir';
						$this->errors[] = 'ErrorFailToDeleteDir';
						$error++;
					}
				}
			}
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete $this->id by $user->id", LOG_DEBUG);
			$this->db->commit();
			return 1;
		} else {
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -$error;
		}
	}


	/**
	 * Return array of dispatched lines waiting to be approved for this order
	 *
	 * @since 8.0 Return dispatched quantity (qty).
	 *
	 * @param	int		$status		Filter on stats (-1 = no filter, 0 = lines draft to be approved, 1 = approved lines)
	 * @return	array				Array of lines
	 */
	public function getDispachedLines($status = -1)
	{
		$ret = array();

		// List of already dispatched lines
		$sql = "SELECT p.ref, p.label,";
		$sql .= " e.rowid as warehouse_id, e.ref as entrepot,";
		$sql .= " cfd.rowid as dispatchedlineid, cfd.fk_product, cfd.qty, cfd.eatby, cfd.sellby, cfd.batch, cfd.comment, cfd.status, cfd.fk_elementdet";
		$sql .= " FROM ".$this->db->prefix()."product as p,";
		$sql .= " ".$this->db->prefix()."receptiondet_batch as cfd";
		$sql .= " LEFT JOIN ".$this->db->prefix()."entrepot as e ON cfd.fk_entrepot = e.rowid";
		$sql .= " WHERE cfd.fk_element = ".((int) $this->id);
		$sql .= " AND cfd.fk_product = p.rowid";
		if ($status >= 0) {
			$sql .= " AND cfd.status = ".((int) $status);
		}
		$sql .= " ORDER BY cfd.rowid ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				if ($objp) {
					$ret[] = array(
						'id' => $objp->dispatchedlineid,
						'productid' => $objp->fk_product,
						'warehouseid' => $objp->warehouse_id,
						'qty' => $objp->qty,
						'orderlineid' => $objp->fk_elementdet
					);
				}

				$i++;
			}
		} else {
			dol_print_error($this->db, 'Failed to execute request to get dispatched lines');
		}

		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Set a delivery in database for this supplier order
	 *
	 *	@param	User	$user		User that input data
	 *	@param	integer	$date		Date of reception
	 *	@param	string	$type		Type of receipt ('tot' = total/done, 'par' = partial, 'nev' = never, 'can' = cancel)
	 *	@param	string	$comment	Comment
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function Livraison($user, $date, $type, $comment)
	{
		// phpcs:enable
		global $conf, $langs;

		$result = 0;
		$error = 0;

		dol_syslog(get_class($this)."::Livraison");

		$usercanreceive = 0;
		if (!isModEnabled('reception')) {
			$usercanreceive = $user->hasRight("fournisseur", "commande", "receptionner");
		} else {
			$usercanreceive = $user->hasRight("reception", "creer");
		}

		if ($usercanreceive) {
			// Define the new status
			if ($type == 'par') {
				$statut = self::STATUS_RECEIVED_PARTIALLY;
			} elseif ($type == 'tot') {
				$statut = self::STATUS_RECEIVED_COMPLETELY;
			} elseif ($type == 'nev') {
				$statut = self::STATUS_CANCELED_AFTER_ORDER;
			} elseif ($type == 'can') {
				$statut = self::STATUS_CANCELED_AFTER_ORDER;
			} else {
				$error++;
				dol_syslog(get_class($this)."::Livraison Error -2", LOG_ERR);
				return -2;
			}

			// Some checks to accept the record
			if (getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS')) {
				// If option SUPPLIER_ORDER_USE_DISPATCH_STATUS is on, we check all reception are approved to allow status "total/done"
				if (!$error && ($type == 'tot')) {
					$dispatchedlinearray = $this->getDispachedLines(0);
					if (count($dispatchedlinearray) > 0) {
						$result = -1;
						$error++;
						$this->errors[] = 'ErrorCantSetReceptionToTotalDoneWithReceptionToApprove';
						dol_syslog('ErrorCantSetReceptionToTotalDoneWithReceptionToApprove', LOG_DEBUG);
					}
				}
				if (!$error && getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS_NEED_APPROVE') && ($type == 'tot')) {	// Accept to move to reception done, only if status of all line are ok (refuse denied)
					$dispatcheddenied = $this->getDispachedLines(2);
					if (count($dispatchedlinearray) > 0) {
						$result = -1;
						$error++;
						$this->errors[] = 'ErrorCantSetReceptionToTotalDoneWithReceptionDenied';
						dol_syslog('ErrorCantSetReceptionToTotalDoneWithReceptionDenied', LOG_DEBUG);
					}
				}
			}

			// TODO LDR01 Add a control test to accept only if ALL predefined products are received (same qty).

			if (empty($error)) {
				$this->db->begin();

				$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur";
				$sql .= " SET fk_statut = ".((int) $statut);
				$sql .= " WHERE rowid = ".((int) $this->id);
				$sql .= " AND fk_statut IN (".self::STATUS_ORDERSENT.",".self::STATUS_RECEIVED_PARTIALLY.")"; // Process running or Partially received

				dol_syslog(get_class($this)."::Livraison", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$result = 1;
					$old_statut = $this->statut;
					$this->statut = $statut;
					$this->context['actionmsg2'] = $comment;

					// Call trigger
					$result_trigger = $this->call_trigger('ORDER_SUPPLIER_RECEIVE', $user);
					if ($result_trigger < 0) {
						$error++;
					}
					// End call triggers

					if (empty($error)) {
						$this->db->commit();
					} else {
						$this->statut = $old_statut;
						$this->db->rollback();
						$this->error = $this->db->lasterror();
						$result = -1;
					}
				} else {
					$this->db->rollback();
					$this->error = $this->db->lasterror();
					$result = -1;
				}
			}
		} else {
			$this->error = $langs->trans('NotAuthorized');
			$this->errors[] = $langs->trans('NotAuthorized');
			dol_syslog(get_class($this)."::Livraison Not Authorized");
			$result = -3;
		}
		return $result;
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
	 *	@param      User			$user        		Object user making change
	 *	@param      integer  		$delivery_date     Planned delivery date
	 *  @param     	int				$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         						Return integer <0 if KO, >0 if OK
	 */
	public function setDeliveryDate($user, $delivery_date, $notrigger = 0)
	{
		if ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer")) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur";
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
				$result = $this->call_trigger('ORDER_SUPPLIER_MODIFY', $user);
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
	 *	Set the id projet
	 *
	 *	@param      User			$user        		Object utilisateur qui modifie
	 *	@param      int				$id_projet    	 	Delivery date
	 *  @param     	int				$notrigger			1=Does not execute triggers, 0= execute triggers
	 *	@return     int         						Return integer <0 si ko, >0 si ok
	 */
	public function set_id_projet($user, $id_projet, $notrigger = 0)
	{
		// phpcs:enable
		if ($user->hasRight("fournisseur", "commande", "creer") || $user->hasRight("supplier_order", "creer")) {
			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".$this->db->prefix()."commande_fournisseur";
			$sql .= " SET fk_projet = ".($id_projet > 0 ? (int) $id_projet : 'null');
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$error) {
				$this->oldcopy = clone $this;
				$this->fk_projet = $id_projet;
				$this->fk_project = $id_projet;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('ORDER_SUPPLIER_MODIFY', $user);
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

	/**
	 *  Update a supplier order from a sales order
	 *
	 *  @param  User	$user           User that create
	 *  @param  int		$idc			Id of purchase order to update
	 *  @param	int		$comclientid	Id of sale order to use as template
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function updateFromCommandeClient($user, $idc, $comclientid)
	{
		$comclient = new Commande($this->db);
		$comclient->fetch($comclientid);

		$this->id = $idc;

		$this->lines = array();

		$num = count($comclient->lines);
		for ($i = 0; $i < $num; $i++) {
			$prod = new Product($this->db);
			$label = '';
			$ref = '';
			if ($prod->fetch($comclient->lines[$i]->fk_product) > 0) {
				$label  = $prod->label;
				$ref    = $prod->ref;
			}

			$sql = "INSERT INTO ".$this->db->prefix()."commande_fournisseurdet";
			$sql .= " (fk_commande, label, description, fk_product, price, qty, tva_tx, localtax1_tx, localtax2_tx, remise_percent, subprice, remise, ref)";
			$sql .= " VALUES (".((int) $idc).", '".$this->db->escape($label)."', '".$this->db->escape($comclient->lines[$i]->desc)."'";
			$sql .= ",".$comclient->lines[$i]->fk_product.", ".price2num($comclient->lines[$i]->price, 'MU');
			$sql .= ", ".price2num($comclient->lines[$i]->qty, 'MS').", ".price2num($comclient->lines[$i]->tva_tx, 5).", ".price2num($comclient->lines[$i]->localtax1_tx, 5).", ".price2num($comclient->lines[$i]->localtax2_tx, 5).", ".price2num($comclient->lines[$i]->remise_percent, 3);
			$sql .= ", '".price2num($comclient->lines[$i]->subprice, 'MT')."','0', '".$this->db->escape($ref)."');";
			if ($this->db->query($sql)) {
				$this->update_price(1);
			}
		}

		return 1;
	}

	/**
	 *  Tag order with a particular status
	 *
	 *  @param      User	$user       Object user that change status
	 *  @param      int		$status		New status
	 *  @return     int         		Return integer <0 if KO, >0 if OK
	 */
	public function setStatus($user, $status)
	{
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.$this->db->prefix().'commande_fournisseur';
		$sql .= " SET fk_statut = ".$status;
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setStatus", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Trigger names for each status
			$triggerName = array();
			$triggerName[0] = 'DRAFT';
			$triggerName[1] = 'VALIDATED';
			$triggerName[2] = 'APPROVED';
			$triggerName[3] = 'ORDERED'; // Ordered
			$triggerName[4] = 'RECEIVED_PARTIALLY';
			$triggerName[5] = 'RECEIVED_COMPLETELY';
			$triggerName[6] = 'CANCELED';
			$triggerName[7] = 'CANCELED';
			$triggerName[9] = 'REFUSED';

			// Call trigger
			$result = $this->call_trigger("ORDER_SUPPLIER_STATUS_".$triggerName[$status], $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		} else {
			$error++;
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::setStatus ".$this->error);
		}

		if (!$error) {
			$this->statut = $status;
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Update line
	 *
	 *	@param     	int			$rowid           	ID de la ligne de facture
	 *	@param     	string		$desc            	Line description
	 *	@param     	int|float	$pu              	Unit price
	 *	@param     	int|float	$qty             	Quantity
	 *	@param     	int|float	$remise_percent  	Percent discount on line
	 *	@param     	int|float	$txtva          	VAT rate
	 *  @param     	int|float	$txlocaltax1	    Localtax1 tax
	 *  @param     	int|float	$txlocaltax2   		Localtax2 tax
	 *  @param     	string		$price_base_type 	Type of price base
	 *	@param		int			$info_bits			Miscellaneous information
	 *	@param		int			$type				Type of line (0=product, 1=service)
	 *  @param		int			$notrigger			Disable triggers
	 *  @param      integer     $date_start     	Date start of service
	 *  @param      integer     $date_end       	Date end of service
	 *  @param		array		$array_options		Extrafields array
	 * 	@param 		int|null	$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param		int|float	$pu_ht_devise		Unit price in currency
	 *  @param		string		$ref_supplier		Supplier ref
	 *	@return    	int         	    			Return integer < 0 if error, > 0 if ok
	 */
	public function updateline($rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $price_base_type = 'HT', $info_bits = 0, $type = 0, $notrigger = 0, $date_start = 0, $date_end = 0, $array_options = [], $fk_unit = null, $pu_ht_devise = 0, $ref_supplier = '')
	{
		global $mysoc, $conf, $langs;
		dol_syslog(get_class($this)."::updateline $rowid, $desc, $pu, $qty, $remise_percent, $txtva, $price_base_type, $info_bits, $type, $fk_unit");
		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		$error = 0;

		if ($this->statut == self::STATUS_DRAFT) {
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

			$remise_percent = (float) price2num($remise_percent);
			$qty = price2num($qty);
			if (!$qty) {
				$qty = 1;
			}
			$pu = price2num($pu);
			$pu_ht_devise = price2num($pu_ht_devise);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
			}
			$txlocaltax1 = (float) price2num($txlocaltax1);
			$txlocaltax2 = (float) price2num($txlocaltax2);

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

			// Calcul du total TTC et de la TVA pour la ligne a partir de
			// qty, pu, remise_percent et txtva
			// TRES IMPORTANT: C'est au moment de l'insertion ligne qu'on doit stocker
			// la part ht, tva et ttc, et ce au niveau de la ligne qui a son propre taux tva.

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $mysoc, $this->thirdparty);

			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $txtva, $reg)) {
				$vat_src_code = $reg[1];
				$txtva = preg_replace('/\s*\(.*\)/', '', $txtva); // Remove code into vatrate.
			}

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $this->thirdparty, $localtaxes_type, 100, $this->multicurrency_tx, $pu_ht_devise);
			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht  = $tabprice[3];
			$pu_tva = $tabprice[4];
			$pu_ttc = $tabprice[5];

			// MultiCurrency
			$multicurrency_total_ht = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			$localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$this->line = new CommandeFournisseurLigne($this->db);
			$this->line->fetch($rowid);

			$oldline = clone $this->line;
			$this->line->oldline = $oldline;

			$this->line->context = $this->context;

			$this->line->fk_commande = $this->id;
			//$this->line->label=$label;
			$this->line->desc = $desc;

			// redefine quantity according to packaging
			if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
				if ($qty < $this->line->packaging) {
					$qty = $this->line->packaging;
				} else {
					if (!empty($this->line->packaging) && ($qty % $this->line->packaging) > 0) {
						$coeff = intval($qty / $this->line->packaging) + 1;
						$qty = $this->line->packaging * $coeff;
						setEventMessage($langs->trans('QtyRecalculatedWithPackaging'), 'mesgs');
					}
				}
			}

			$this->line->qty = $qty;
			$this->line->ref_supplier = $ref_supplier;

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx         = $txtva;
			$this->line->localtax1_tx   = $txlocaltax1;
			$this->line->localtax2_tx   = $txlocaltax2;
			$this->line->localtax1_type = empty($localtaxes_type[0]) ? '' : $localtaxes_type[0];
			$this->line->localtax2_type = empty($localtaxes_type[2]) ? '' : $localtaxes_type[2];
			$this->line->remise_percent = $remise_percent;
			$this->line->subprice       = $pu_ht;
			$this->line->info_bits      = $info_bits;
			$this->line->total_ht       = $total_ht;
			$this->line->total_tva      = $total_tva;
			$this->line->total_localtax1 = $total_localtax1;
			$this->line->total_localtax2 = $total_localtax2;
			$this->line->total_ttc      = $total_ttc;
			$this->line->product_type   = $type;
			$this->line->special_code   = $oldline->special_code;
			$this->line->rang           = $oldline->rang;
			$this->line->origin         = $this->origin;
			$this->line->fk_unit        = $fk_unit;

			$this->line->date_start     = $date_start;
			$this->line->date_end       = $date_end;

			// Multicurrency
			$this->line->fk_multicurrency = $this->fk_multicurrency;
			$this->line->multicurrency_code = $this->multicurrency_code;
			$this->line->multicurrency_subprice		= $pu_ht_devise;
			$this->line->multicurrency_total_ht 	= $multicurrency_total_ht;
			$this->line->multicurrency_total_tva 	= $multicurrency_total_tva;
			$this->line->multicurrency_total_ttc 	= $multicurrency_total_ttc;

			$this->line->subprice = $pu_ht;
			$this->line->price = $this->line->subprice;

			$this->line->remise_percent = $remise_percent;

			if (is_array($array_options) && count($array_options) > 0) {
				// We replace values in this->line->array_options only for entries defined into $array_options
				foreach ($array_options as $key => $value) {
					$this->line->array_options[$key] = $array_options[$key];
				}
			}

			$result = $this->line->update($notrigger);


			// Mise a jour info denormalisees au niveau facture
			if ($result >= 0) {
				$this->update_price('1', 'auto');
				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = "Order status makes operation forbidden";
			dol_syslog(get_class($this)."::updateline ".$this->error, LOG_ERR);
			return -2;
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
		global $user, $langs, $conf;

		include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

		dol_syslog(get_class($this)."::initAsSpecimen");

		$now = dol_now();

		// Find first product
		$prodid = 0;
		$product = new ProductFournisseur($this->db);
		$sql = "SELECT rowid";
		$sql .= " FROM ".$this->db->prefix()."product";
		$sql .= " WHERE entity IN (".getEntity('product').")";
		$sql .= $this->db->order("rowid", "ASC");
		$sql .= $this->db->plimit(1);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$prodid = $obj->rowid;
		}

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date = $now;
		$this->date_commande = $now;
		$this->date_lim_reglement = $this->date + 3600 * 24 * 30;
		$this->cond_reglement_code = 'RECEP';
		$this->mode_reglement_code = 'CHQ';

		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->multicurrency_tx = 1;
		$this->multicurrency_code = $conf->currency;

		$this->statut = 0;

		// Lines
		$nbp = 5;
		$xnbp = 0;
		while ($xnbp < $nbp) {
			$line = new CommandeFournisseurLigne($this->db);
			$line->desc = $langs->trans("Description")." ".$xnbp;
			$line->qty = 1;
			$line->subprice = 100;
			$line->tva_tx = 19.6;
			$line->localtax1_tx = 0;
			$line->localtax2_tx = 0;
			if ($xnbp == 2) {
				$line->total_ht = 50;
				$line->total_ttc = 59.8;
				$line->total_tva = 9.8;
				$line->remise_percent = 50;
			} else {
				$line->total_ht = 100;
				$line->total_ttc = 119.6;
				$line->total_tva = 19.6;
				$line->remise_percent = 00;
			}
			$line->fk_product = $prodid;

			$this->lines[$xnbp] = $line;

			$this->total_ht       += $line->total_ht;
			$this->total_tva      += $line->total_tva;
			$this->total_ttc      += $line->total_ttc;

			$xnbp++;
		}

		return 1;
	}

	/**
	 *	Charge les information d'ordre info dans l'objet facture
	 *
	 *	@param  int		$id       	Id de la facture a charger
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, date_creation as datec, tms as datem, date_valid as date_validation, date_approve as datea, date_approve2 as datea2,';
		$sql .= ' fk_user_author, fk_user_modif, fk_user_valid, fk_user_approve, fk_user_approve2';
		$sql .= ' FROM '.$this->db->prefix().'commande_fournisseur as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->user_approve_id = $obj->fk_user_approve;
				$this->user_approve_id2 = $obj->fk_user_approve2;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_approve      = $this->db->jdate($obj->datea);
				$this->date_approve2     = $this->db->jdate($obj->datea2);
				$this->date_validation   = $this->db->jdate($obj->date_validation);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *	Load the indicators this->nb for the state board
	 *
	 *	@return     int         Return integer <0 si ko, >0 si ok
	 */
	public function loadStateBoard()
	{
		global $conf, $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(co.rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."commande_fournisseur as co";
		$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON co.fk_soc = s.rowid";
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." co.entity IN (".getEntity('supplier_order').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["supplier_orders"] = $obj->nb;
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
	 *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *	@param	User	$user   Object user
	 *  @param	string	$mode   "opened", "awaiting" for orders awaiting reception
	 *	@return WorkboardResponse|int 	Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode = 'opened')
	{
		// phpcs:enable
		global $conf, $langs;

		$sql = "SELECT c.rowid, c.date_creation as datec, c.date_commande, c.fk_statut, c.date_livraison as delivery_date, c.total_ht";
		$sql .= " FROM ".$this->db->prefix()."commande_fournisseur as c";
		if (!$user->hasRight("societe", "client", "voir") && !$user->socid) {
			$sql .= " JOIN ".$this->db->prefix()."societe_commerciaux as sc ON c.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		}
		$sql .= " WHERE c.entity = ".$conf->entity;
		if ($mode === 'awaiting') {
			$sql .= " AND c.fk_statut IN (".self::STATUS_ORDERSENT.", ".self::STATUS_RECEIVED_PARTIALLY.")";
		} else {
			$sql .= " AND c.fk_statut IN (".self::STATUS_VALIDATED.", ".self::STATUS_ACCEPTED.")";
		}
		if ($user->socid) {
			$sql .= " AND c.fk_soc = ".((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$commandestatic = new CommandeFournisseur($this->db);

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->commande->fournisseur->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("SuppliersOrdersToProcess");
			$response->labelShort = $langs->trans("Opened");
			$response->url = DOL_URL_ROOT.'/fourn/commande/list.php?search_status=1,2&mainmenu=commercial&leftmenu=orders_suppliers';
			$response->img = img_object('', "order");

			if ($mode === 'awaiting') {
				$response->label = $langs->trans("SuppliersOrdersAwaitingReception");
				$response->labelShort = $langs->trans("AwaitingReception");
				$response->url = DOL_URL_ROOT.'/fourn/commande/list.php?search_status=3,4&mainmenu=commercial&leftmenu=orders_suppliers';
			}

			while ($obj = $this->db->fetch_object($resql)) {
				$commandestatic->delivery_date = $this->db->jdate($obj->delivery_date);
				$commandestatic->date_commande = $this->db->jdate($obj->date_commande);
				$commandestatic->statut = $obj->fk_statut;

				$response->nbtodo++;
				$response->total += $obj->total_ht;

				if ($commandestatic->hasDelay()) {
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
	 * Returns the translated input method of object (defined if $this->methode_commande_id > 0).
	 * This function make a sql request to get translation. No cache yet, try to not use it inside a loop.
	 *
	 * @return string
	 */
	public function getInputMethod()
	{
		global $langs;

		if ($this->methode_commande_id > 0) {
			$sql = "SELECT rowid, code, libelle as label";
			$sql .= " FROM ".$this->db->prefix().'c_input_method';
			$sql .= " WHERE active=1 AND rowid = ".((int) $this->methode_commande_id);

			$resql = $this->db->query($sql);
			if ($resql) {
				if ($this->db->num_rows($resql)) {
					$obj = $this->db->fetch_object($resql);

					$string = $langs->trans($obj->code);
					if ($string == $obj->code) {
						$string = $obj->label != '-' ? $obj->label : '';
					}
					return $string;
				}
			} else {
				dol_print_error($this->db);
			}
		}

		return '';
	}

	/**
	 *  Create a document onto disk according to template model.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	Object lang to use for traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int          				Return integer < 0 if KO, 0 = no doc generated, > 0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		if (!dol_strlen($modele)) {
			$modele = '';	// No doc template/generation by default

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('COMMANDE_SUPPLIER_ADDON_PDF')) {
				$modele = getDolGlobalString('COMMANDE_SUPPLIER_ADDON_PDF');
			}
		}

		if (empty($modele)) {
			return 0;
		} else {
			$langs->load("suppliers");
			$outputlangs->load("products");

			$modelpath = "core/modules/supplier_order/doc/";
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
			return $result;
		}
	}

	/**
	 * Return the max number delivery delay in day
	 *
	 * @param	Translate	$langs		Language object
	 * @return 	string                  Translated string
	 */
	public function getMaxDeliveryTimeDay($langs)
	{
		if (empty($this->lines)) {
			return '';
		}

		$obj = new ProductFournisseur($this->db);

		$nb = 0;
		foreach ($this->lines as $line) {
			if ($line->fk_product > 0) {
				$idp = $obj->find_min_price_product_fournisseur($line->fk_product, $line->qty);
				if ($idp) {
					$obj->fetch($idp);
					if ($obj->delivery_time_days > $nb) {
						$nb = $obj->delivery_time_days;
					}
				}
			}
		}

		if ($nb === 0) {
			return '';
		} else {
			return $nb.' '.$langs->trans('Days');
		}
	}

	/**
	 * Returns the rights used for this class
	 * @return int
	 */
	public function getRights()
	{
		global $user;

		return $user->hasRight("fournisseur", "commande");
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
			'commande_fournisseur'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param DoliDB 	$dbs 		Database handler
	 * @param int 		$origin_id 	Old product id
	 * @param int 		$dest_id 	New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'commande_fournisseurdet'
		);

		return CommonObject::commonReplaceProduct($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Is the supplier order delayed?
	 * We suppose a purchase ordered as late if a the purchase order has been sent and the delivery date is set and before the delay.
	 * If order has not been sent, we use the order date.
	 *
	 * @return 	bool					True if object is delayed
	 */
	public function hasDelay()
	{
		global $conf;

		if ($this->statut == self::STATUS_ORDERSENT || $this->statut == self::STATUS_RECEIVED_PARTIALLY) {
			$now = dol_now();
			if (!empty($this->delivery_date)) {
				$date_to_test = $this->delivery_date;
				return $date_to_test && $date_to_test < ($now - $conf->commande->fournisseur->warning_delay);
			} else {
				//$date_to_test = $this->date_commande;
				//return $date_to_test && $date_to_test < ($now - $conf->commande->fournisseur->warning_delay);
				return false;
			}
		} else {
			$now = dol_now();
			$date_to_test = $this->date_commande;

			return ($this->statut > 0 && $this->statut < 5) && $date_to_test && $date_to_test < ($now - $conf->commande->fournisseur->warning_delay);
		}
	}

	/**
	 * Show the customer delayed info.
	 * We suppose a purchase ordered as late if a the purchase order has been sent and the delivery date is set and before the delay.
	 * If order has not been sent, we use the order date.
	 *
	 * @return string       Show delayed information
	 */
	public function showDelay()
	{
		global $conf, $langs;

		$langs->load('orders');

		$text = '';

		if ($this->statut == self::STATUS_ORDERSENT || $this->statut == self::STATUS_RECEIVED_PARTIALLY) {
			if (!empty($this->delivery_date)) {
				$text = $langs->trans("DeliveryDate").' '.dol_print_date($this->delivery_date, 'day');
			} else {
				$text = $langs->trans("OrderDate").' '.dol_print_date($this->date_commande, 'day');
			}
		} else {
			$text = $langs->trans("OrderDate").' '.dol_print_date($this->date_commande, 'day');
		}
		if ($text) {
			$text .= ' '.($conf->commande->fournisseur->warning_delay > 0 ? '+' : '-').' '.round(abs($conf->commande->fournisseur->warning_delay) / 3600 / 24, 1).' '.$langs->trans("days").' < '.$langs->trans("Today");
		}

		return $text;
	}


	/**
	 * Calc status regarding to dispatched stock
	 *
	 * @param 		User 	$user                   User action
	 * @param       int     $closeopenorder         Close if received
	 * @param		string	$comment				Comment
	 * @return		int		                        Return integer <0 if KO, 0 if not applicable, >0 if OK
	 */
	public function calcAndSetStatusDispatch(User $user, $closeopenorder = 1, $comment = '')
	{
		if (isModEnabled("supplier_order")) {
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';

			$qtydelivered = array();
			$qtywished = array();

			$supplierorderdispatch = new CommandeFournisseurDispatch($this->db);

			$filter = array('t.fk_element' => $this->id);
			if (getDolGlobalString('SUPPLIER_ORDER_USE_DISPATCH_STATUS')) {
				$filter['t.status'] = 1; // Restrict to lines with status validated
			}

			$ret = $supplierorderdispatch->fetchAll('', '', 0, 0, $filter);
			if ($ret < 0) {
				$this->error = $supplierorderdispatch->error;
				$this->errors = $supplierorderdispatch->errors;
				return $ret;
			} else {
				if (is_array($supplierorderdispatch->lines) && count($supplierorderdispatch->lines) > 0) {
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$date_liv = dol_now();

					// Build array with quantity deliverd by product
					foreach ($supplierorderdispatch->lines as $line) {
						$qtydelivered[$line->fk_product] += $line->qty;
					}
					foreach ($this->lines as $line) {
						// Exclude lines not qualified for shipment, similar code is found into interface_20_modWrokflow for customers
						if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES') && $line->product_type > 0) {
							continue;
						}
						$qtywished[$line->fk_product] += $line->qty;
					}

					//Compare array
					$diff_array = array_diff_assoc($qtydelivered, $qtywished); // Warning: $diff_array is done only on common keys.
					$keysinwishednotindelivered = array_diff(array_keys($qtywished), array_keys($qtydelivered)); // To check we also have same number of keys
					$keysindeliverednotinwished = array_diff(array_keys($qtydelivered), array_keys($qtywished)); // To check we also have same number of keys
					//var_dump(array_keys($qtydelivered));
					//var_dump(array_keys($qtywished));
					//var_dump($diff_array);
					//var_dump($keysinwishednotindelivered);
					//var_dump($keysindeliverednotinwished);
					//exit;

					if (count($diff_array) == 0 && count($keysinwishednotindelivered) == 0 && count($keysindeliverednotinwished) == 0) { //No diff => mean everything is received
						if ($closeopenorder) {
							//$ret=$this->setStatus($user,5);
							$ret = $this->Livraison($user, $date_liv, 'tot', $comment); // $type is 'tot', 'par', 'nev', 'can'
							if ($ret < 0) {
								return -1;
							}
							return 5;
						} else {
							//Diff => received partially
							//$ret=$this->setStatus($user,4);
							$ret = $this->Livraison($user, $date_liv, 'par', $comment); // $type is 'tot', 'par', 'nev', 'can'
							if ($ret < 0) {
								return -1;
							}
							return 4;
						}
					} elseif (getDolGlobalString('SUPPLIER_ORDER_MORE_THAN_WISHED')) {
						//set livraison to 'tot' if more products received than wished. (and if $closeopenorder is set to 1 of course...)

						$close = 0;

						if (count($diff_array) > 0) {
							//there are some difference between  the two arrays

							//scan the array of results
							foreach ($diff_array as $key => $value) {
								//if the quantity delivered is greater or equal to wish quantity
								if ($qtydelivered[$key] >= $qtywished[$key]) {
									$close++;
								}
							}
						}


						if ($close == count($diff_array)) {
							//all the products are received equal or more than the wished quantity
							if ($closeopenorder) {
								$ret = $this->Livraison($user, $date_liv, 'tot', $comment); // $type is 'tot', 'par', 'nev', 'can'
								if ($ret < 0) {
									return -1;
								}
								return 5;
							} else {
								//Diff => received partially
								$ret = $this->Livraison($user, $date_liv, 'par', $comment); // $type is 'tot', 'par', 'nev', 'can'
								if ($ret < 0) {
									return -1;
								}
								return 4;
							}
						} else {
							//all the products are not received
							$ret = $this->Livraison($user, $date_liv, 'par', $comment); // $type is 'tot', 'par', 'nev', 'can'
							if ($ret < 0) {
								return -1;
							}
							return 4;
						}
					} else {
						//Diff => received partially
						$ret = $this->Livraison($user, $date_liv, 'par', $comment); // $type is 'tot', 'par', 'nev', 'can'
						if ($ret < 0) {
							return -1;
						}
						return 4;
					}
				}
				return 1;
			}
		}
		return 0;
	}

	/**
	 *	Load array this->receptions of lines of shipments with nb of products sent for each order line
	 *  Note: For a dedicated shipment, the fetch_lines can be used to load the qty_asked and qty_shipped. This function is use to return qty_shipped cumulated for the order
	 *
	 *	@param      int		$filtre_statut      Filter on shipment status
	 * 	@return     int                			Return integer <0 if KO, Nb of lines found if OK
	 */
	public function loadReceptions($filtre_statut = -1)
	{
		$this->receptions = array();

		dol_syslog(get_class($this)."::loadReceptions", LOG_DEBUG);

		$sql = 'SELECT cd.rowid, cd.fk_product,';
		$sql .= ' sum(cfd.qty) as qty';
		$sql .= ' FROM '.$this->db->prefix().'receptiondet_batch as cfd,';
		if ($filtre_statut >= 0) {
			$sql .= ' '.$this->db->prefix().'reception as e,';
		}
		$sql .= ' '.$this->db->prefix().'commande_fournisseurdet as cd';
		$sql .= ' WHERE';
		if ($filtre_statut >= 0) {
			$sql .= ' cfd.fk_reception = e.rowid AND';
		}
		$sql .= ' cfd.fk_elementdet = cd.rowid';
		$sql .= ' AND cd.fk_commande ='.((int) $this->id);
		if (isset($this->fk_product) && !empty($this->fk_product) > 0) {
			$sql .= ' AND cd.fk_product = '.((int) $this->fk_product);
		}
		if ($filtre_statut >= 0) {
			$sql .= ' AND e.fk_statut >= '.((int) $filtre_statut);
		}
		$sql .= ' GROUP BY cd.rowid, cd.fk_product';

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				empty($this->receptions[$obj->rowid]) ? $this->receptions[$obj->rowid] = $obj->qty : $this->receptions[$obj->rowid] += $obj->qty;
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
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'socid') || property_exists($this, 'total_tva')) {
			$return .= '<br><span class="info-box-label amount">'.$this->socid.'</span>';
		}
		if (property_exists($this, 'billed')) {
			$return .= '<br><span class="opacitymedium">'.$langs->trans("Billed").' : </span><span class="info-box-label">'.yn($this->billed).'</span>';
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



/**
 *  Class to manage line orders
 */
class CommandeFournisseurLigne extends CommonOrderLine
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'commande_fournisseurdet';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'commande_fournisseurdet';

	/**
	 * @see CommonObjectLine
	 */
	public $parent_element = 'commande_fournisseur';

	/**
	 * @see CommonObjectLine
	 */
	public $fk_parent_attribute = 'fk_commande_fournisseur';

	public $oldline;

	/**
	 * Id of parent order
	 * @var int
	 */
	public $fk_commande;

	// From llx_commande_fournisseurdet
	/**
	 * @var int ID
	 */
	public $fk_parent_line;

	/**
	 * @var int ID
	 */
	public $fk_facture;

	public $rang = 0;

	/**
	 * @var int special code
	 */
	public $special_code = 0;

	/**
	 * Unit price without taxes
	 * @var float
	 */
	public $pu_ht;

	public $date_start;
	public $date_end;
	public $fk_fournprice;
	public $packaging;
	public $pa_ht;

	// From llx_product_fournisseur_price

	/**
	 * Supplier reference of price when we added the line. May have been changed after line was added.
	 * @var string
	 */
	public $ref_supplier;

	/**
	 * @var string ref supplier
	 * @deprecated
	 * @see $ref_supplier
	 */
	public $ref_fourn;

	public $remise;


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
	 *  Load line order
	 *
	 *  @param  int		$rowid      Id line order
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function fetch($rowid)
	{
		$sql = 'SELECT cd.rowid, cd.fk_commande, cd.fk_product, cd.product_type, cd.description, cd.qty, cd.tva_tx, cd.special_code,';
		$sql .= ' cd.localtax1_tx, cd.localtax2_tx, cd.localtax1_type, cd.localtax2_type, cd.ref as ref_supplier,';
		$sql .= ' cd.remise, cd.remise_percent, cd.subprice,';
		$sql .= ' cd.info_bits, cd.total_ht, cd.total_tva, cd.total_ttc,';
		$sql .= ' cd.total_localtax1, cd.total_localtax2,';
		$sql .= ' p.ref as product_ref, p.label as product_label, p.description as product_desc,';
		$sql .= ' cd.date_start, cd.date_end, cd.fk_unit,';
		$sql .= ' cd.multicurrency_subprice, cd.multicurrency_total_ht, cd.multicurrency_total_tva, cd.multicurrency_total_ttc,';
		$sql .= ' c.fk_soc as socid';
		$sql .= ' FROM '.$this->db->prefix().'commande_fournisseur as c, '.$this->db->prefix().'commande_fournisseurdet as cd';
		$sql .= ' LEFT JOIN '.$this->db->prefix().'product as p ON cd.fk_product = p.rowid';
		$sql .= ' WHERE cd.fk_commande = c.rowid AND cd.rowid = '.((int) $rowid);

		$result = $this->db->query($sql);
		if ($result) {
			$objp = $this->db->fetch_object($result);

			if (!empty($objp)) {
				$this->rowid = $objp->rowid;
				$this->id               = $objp->rowid;
				$this->fk_commande      = $objp->fk_commande;
				$this->desc             = $objp->description;
				$this->qty              = $objp->qty;
				$this->ref_fourn        = $objp->ref_supplier;
				$this->ref_supplier     = $objp->ref_supplier;
				$this->subprice         = $objp->subprice;
				$this->tva_tx           = $objp->tva_tx;
				$this->localtax1_tx		= $objp->localtax1_tx;
				$this->localtax2_tx		= $objp->localtax2_tx;
				$this->localtax1_type	= $objp->localtax1_type;
				$this->localtax2_type	= $objp->localtax2_type;
				$this->remise           = $objp->remise;
				$this->remise_percent   = $objp->remise_percent;
				$this->fk_product       = $objp->fk_product;
				$this->info_bits        = $objp->info_bits;
				$this->total_ht         = $objp->total_ht;
				$this->total_tva        = $objp->total_tva;
				$this->total_localtax1	= $objp->total_localtax1;
				$this->total_localtax2	= $objp->total_localtax2;
				$this->total_ttc        = $objp->total_ttc;
				$this->product_type     = $objp->product_type;
				$this->special_code     = $objp->special_code;

				$this->ref = $objp->product_ref;

				$this->product_ref      = $objp->product_ref;
				$this->product_label    = $objp->product_label;
				$this->product_desc     = $objp->product_desc;

				if (getDolGlobalInt('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					// TODO We should not fetch this properties into the fetch_lines. This is NOT properties of a line.
					// Move this into another method and call it when required.

					// Take better packaging for $objp->qty (first supplier ref quantity <= $objp->qty)
					$sqlsearchpackage = 'SELECT rowid, packaging FROM '.$this->db->prefix()."product_fournisseur_price";
					$sqlsearchpackage .= ' WHERE entity IN ('.getEntity('product_fournisseur_price').")";
					$sqlsearchpackage .= " AND fk_product = ".((int) $objp->fk_product);
					$sqlsearchpackage .= " AND ref_fourn = '".$this->db->escape($objp->ref_supplier)."'";
					$sqlsearchpackage .= " AND quantity <= ".((float) $objp->qty);	// required to be qualified
					$sqlsearchpackage .= " AND (packaging IS NULL OR packaging = 0 OR packaging <= ".((float) $objp->qty).")";	// required to be qualified
					$sqlsearchpackage .= " AND fk_soc = ".((int) $objp->socid);
					$sqlsearchpackage .= " ORDER BY packaging ASC";		// Take the smaller package first
					$sqlsearchpackage .= " LIMIT 1";

					$resqlsearchpackage = $this->db->query($sqlsearchpackage);
					if ($resqlsearchpackage) {
						$objsearchpackage = $this->db->fetch_object($resqlsearchpackage);
						if ($objsearchpackage) {
							$this->fk_fournprice = $objsearchpackage->rowid;
							$this->packaging     = $objsearchpackage->packaging;
						}
					} else {
						$this->error = $this->db->lasterror();
						return -1;
					}
				}

				$this->date_start       		= $this->db->jdate($objp->date_start);
				$this->date_end         		= $this->db->jdate($objp->date_end);
				$this->fk_unit = $objp->fk_unit;

				$this->multicurrency_subprice	= $objp->multicurrency_subprice;
				$this->multicurrency_total_ht	= $objp->multicurrency_total_ht;
				$this->multicurrency_total_tva	= $objp->multicurrency_total_tva;
				$this->multicurrency_total_ttc	= $objp->multicurrency_total_ttc;

				$this->fetch_optionals();

				$this->db->free($result);
				return 1;
			} else {
				$this->error = 'Supplier order line  with id='.$rowid.' not found';
				dol_syslog(get_class($this)."::fetch Error ".$this->error, LOG_ERR);
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Insert line into database
	 *
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function insert($notrigger = 0)
	{
		global $conf, $user;

		$error = 0;

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
			$this->localtax1_type = '0';
		}
		if (empty($this->localtax2_type)) {
			$this->localtax2_type = '0';
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

		// Multicurrency
		if (!empty($this->multicurrency_code)) {
			list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

		// Check parameters
		if ($this->product_type < 0) {
			return -1;
		}

		$this->db->begin();

		// Insertion dans base de la ligne
		$sql = 'INSERT INTO '.$this->db->prefix().$this->table_element;
		$sql .= " (fk_commande, label, description, date_start, date_end,";
		$sql .= " fk_product, product_type, special_code, rang,";
		$sql .= " qty, vat_src_code, tva_tx, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, remise_percent, subprice, ref,";
		$sql .= " total_ht, total_tva, total_localtax1, total_localtax2, total_ttc, fk_unit,";
		$sql .= " fk_multicurrency, multicurrency_code, multicurrency_subprice, multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc,";
		$sql .= " fk_parent_line)";
		$sql .= " VALUES (".$this->fk_commande.", '".$this->db->escape($this->label)."','".$this->db->escape($this->desc)."',";
		$sql .= " ".($this->date_start ? "'".$this->db->idate($this->date_start)."'" : "null").",";
		$sql .= " ".($this->date_end ? "'".$this->db->idate($this->date_end)."'" : "null").",";
		if ($this->fk_product) {
			$sql .= $this->fk_product.",";
		} else {
			$sql .= "null,";
		}
		$sql .= "'".$this->db->escape($this->product_type)."',";
		$sql .= (int) $this->special_code . ",";
		$sql .= "'".$this->db->escape($this->rang)."',";
		$sql .= "'".$this->db->escape($this->qty)."', ";
		$sql .= " ".(empty($this->vat_src_code) ? "''" : "'".$this->db->escape($this->vat_src_code)."'").",";
		$sql .= " ".price2num($this->tva_tx).", ";
		$sql .= " ".price2num($this->localtax1_tx).",";
		$sql .= " ".price2num($this->localtax2_tx).",";
		$sql .= " '".$this->db->escape($this->localtax1_type)."',";
		$sql .= " '".$this->db->escape($this->localtax2_type)."',";
		$sql .= " ".((float) $this->remise_percent).", ".price2num($this->subprice, 'MU').", '".$this->db->escape($this->ref_supplier)."',";
		$sql .= " ".price2num($this->total_ht).",";
		$sql .= " ".price2num($this->total_tva).",";
		$sql .= " ".price2num($this->total_localtax1).",";
		$sql .= " ".price2num($this->total_localtax2).",";
		$sql .= " ".price2num($this->total_ttc).",";
		$sql .= ($this->fk_unit ? "'".$this->db->escape($this->fk_unit)."'" : "null");
		$sql .= ", ".($this->fk_multicurrency ? ((int) $this->fk_multicurrency) : "null");
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".($this->multicurrency_subprice ? price2num($this->multicurrency_subprice) : '0');
		$sql .= ", ".($this->multicurrency_total_ht ? price2num($this->multicurrency_total_ht) : '0');
		$sql .= ", ".($this->multicurrency_total_tva ? price2num($this->multicurrency_total_tva) : '0');
		$sql .= ", ".($this->multicurrency_total_ttc ? price2num($this->multicurrency_total_ttc) : '0');
		$sql .= ", ".((!empty($this->fk_parent_line) && $this->fk_parent_line > 0) ? $this->fk_parent_line : 'null');
		$sql .= ")";

		dol_syslog(get_class($this)."::insert", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
			$this->rowid = $this->id;

			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_SUPPLIER_CREATE', $user);
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
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->errors[] = ($this->errors ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->errors[] = $this->db->error();
			$this->db->rollback();
			return -2;
		}
	}
	/**
	 *	Update the line object into db
	 *
	 *	@param      int		$notrigger		1 = disable triggers
	 *	@return		int		Return integer <0 si ko, >0 si ok
	 */
	public function update($notrigger = 0)
	{
		global $user;

		$error = 0;

		$this->db->begin();

		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
		$sql .= "  description='".$this->db->escape($this->desc)."'";
		$sql .= ", ref='".$this->db->escape($this->ref_supplier)."'";
		$sql .= ", subprice='".price2num($this->subprice)."'";
		//$sql.= ",remise='".price2num($remise)."'";
		$sql .= ", remise_percent='".price2num($this->remise_percent)."'";

		$sql .= ", vat_src_code = '".(empty($this->vat_src_code) ? '' : $this->vat_src_code)."'";
		$sql .= ", tva_tx='".price2num($this->tva_tx)."'";
		$sql .= ", localtax1_tx='".price2num($this->localtax1_tx)."'";
		$sql .= ", localtax2_tx='".price2num($this->localtax2_tx)."'";
		$sql .= ", localtax1_type='".$this->db->escape($this->localtax1_type)."'";
		$sql .= ", localtax2_type='".$this->db->escape($this->localtax2_type)."'";
		$sql .= ", qty='".price2num($this->qty)."'";
		$sql .= ", date_start=".(!empty($this->date_start) ? "'".$this->db->idate($this->date_start)."'" : "null");
		$sql .= ", date_end=".(!empty($this->date_end) ? "'".$this->db->idate($this->date_end)."'" : "null");
		$sql .= ", info_bits='".$this->db->escape($this->info_bits)."'";
		$sql .= ", total_ht='".price2num($this->total_ht)."'";
		$sql .= ", total_tva='".price2num($this->total_tva)."'";
		$sql .= ", total_localtax1='".price2num($this->total_localtax1)."'";
		$sql .= ", total_localtax2='".price2num($this->total_localtax2)."'";
		$sql .= ", total_ttc='".price2num($this->total_ttc)."'";
		$sql .= ", product_type=".$this->product_type;
		$sql .= ", special_code=".(!empty($this->special_code) ? $this->special_code : 0);
		$sql .= ($this->fk_unit ? ", fk_unit='".$this->db->escape($this->fk_unit)."'" : ", fk_unit=null");

		// Multicurrency
		$sql .= ", multicurrency_subprice=".price2num($this->multicurrency_subprice);
		$sql .= ", multicurrency_total_ht=".price2num($this->multicurrency_total_ht);
		$sql .= ", multicurrency_total_tva=".price2num($this->multicurrency_total_tva);
		$sql .= ", multicurrency_total_ttc=".price2num($this->multicurrency_total_ttc);

		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::updateline", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_SUPPLIER_MODIFY', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers
			}

			if (!$error) {
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

	/**
	 * 	Delete line in database
	 *
	 *  @param		User	$user		User making the change
	 *	@param      int     $notrigger  1=Disable call to triggers
	 *	@return     int                 Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		if (empty($user)) {
			global $user;
		}

		$error = 0;

		$this->db->begin();

		// extrafields
		$result = $this->deleteExtraFields();
		if ($result < 0) {
			$this->db->rollback();
			return -1;
		}

		$sql1 = 'UPDATE '.$this->db->prefix()."commandedet SET fk_commandefourndet = NULL WHERE fk_commandefourndet=".((int) $this->id);
		$resql = $this->db->query($sql1);
		if (!$resql) {
			$this->db->rollback();
			return -1;
		}

		$sql2 = 'DELETE FROM '.$this->db->prefix()."commande_fournisseurdet WHERE rowid=".((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql2);
		if ($resql) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('LINEORDER_SUPPLIER_DELETE', $user);
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
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
}
