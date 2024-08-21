<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2018  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2023  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2013       Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Rui Strecht			    <rui.strecht@aliartalentos.com>
 * Copyright (C) 2018	    Philippe Grand	        <philippe.grand@atoo-net.com>
 * Copyright (C) 2019-2020  Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2019-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Open-Dsi         		<support@open-dsi.fr>
 * Copyright (C) 2022		ButterflyOfFire         <butterflyoffire+dolibarr@protonmail.com>
 * Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		William Mead			<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/societe/class/societe.class.php
 *	\ingroup    societe
 *	\brief      File for third party class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonincoterm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonsocialnetworks.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonpeople.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';


/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class Societe extends CommonObject
{
	use CommonIncoterm;
	use CommonSocialNetworks;
	use CommonPeople;

	/**
	 * @var string ID of module.
	 */
	public $module = 'societe';

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'societe';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'societe';

	/**
	 * @var string Field with ID of parent key if this field has a parent or for child tables
	 */
	public $fk_element = 'fk_soc';

	/**
	 * @var string Fields for combobox
	 */
	public $fieldsforcombobox = 'nom,name_alias';

	/**
	 * @var array<string, array<string>>	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array(
		'supplier_proposal' => array('name' => 'SupplierProposal'),
		'propal' => array('name' => 'Proposal'),
		'commande' => array('name' => 'Order'),
		'facture' => array('name' => 'Invoice'),
		'facture_rec' => array('name' => 'RecurringInvoiceTemplate'),
		'contrat' => array('name' => 'Contract'),
		'fichinter' => array('name' => 'Fichinter'),
		'facture_fourn' => array('name' => 'SupplierInvoice'),
		'commande_fournisseur' => array('name' => 'SupplierOrder'),
		'projet' => array('name' => 'Project'),
		'expedition' => array('name' => 'Shipment'),
		'prelevement_lignes' => array('name' => 'DirectDebitRecord'),
	);

	/**
	 * @var string[]	List of child tables. To know object to delete on cascade.
	 *               if name like with @ClassName:FilePathClass:ParentFkFieldName' it will call method deleteByParentField (with parentId as parameters) and FieldName to fetch and delete child object
	 */
	protected $childtablesoncascade = array(
		'societe_prices',
		'product_fournisseur_price',
		'product_customer_price_log',
		'product_customer_price',
		'@Contact:/contact/class/contact.class.php:fk_soc',
		'adherent',
		'societe_account',
		'societe_rib',
		'societe_remise',
		'societe_remise_except',
		'societe_commerciaux',
		'categorie',
		'notify',
		'notify_def',
		'actioncomm',
	);

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'company';

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var int<0,1>
	 */
	public $restrictiononfksoc = 1;

	/**
	 * @var static To store a cloned copy of object before to edit it and keep track of old properties
	 */
	public $oldcopy;

	/**
	 * array of supplier categories
	 * @var string[]
	 */
	public $SupplierCategories = array();

	/**
	 * prefixCustomerIsRequired
	 * @var int
	 */
	public $prefixCustomerIsRequired;

	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString("MY_SETUP_PARAM")'
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	/**
	 * @var array<string,array{type:string,length?:string|int,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}>  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'noteditable' => 1, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id', 'css' => 'left'),
		'parent' => array('type' => 'integer', 'label' => 'Parent', 'enabled' => 1, 'visible' => -1, 'position' => 20),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 25),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 30),
		'nom' => array('type' => 'varchar(128)', 'length' => 128, 'label' => 'Nom', 'enabled' => 1, 'visible' => -1, 'position' => 35, 'showoncombobox' => 1, 'csslist' => 'tdoverflowmax150'),
		'name_alias' => array('type' => 'varchar(128)', 'label' => 'Name alias', 'enabled' => 1, 'visible' => -1, 'position' => 36, 'showoncombobox' => 2),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 40, 'index' => 1),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'RefExt', 'enabled' => 1, 'visible' => 0, 'position' => 45),
		'code_client' => array('type' => 'varchar(24)', 'label' => 'CustomerCode', 'enabled' => 1, 'visible' => -1, 'position' => 55),
		'code_fournisseur' => array('type' => 'varchar(24)', 'label' => 'SupplierCode', 'enabled' => 1, 'visible' => -1, 'position' => 60),
		'code_compta' => array('type' => 'varchar(24)', 'label' => 'CustomerAccountancyCode', 'enabled' => 1, 'visible' => -1, 'position' => 65),
		'code_compta_fournisseur' => array('type' => 'varchar(24)', 'label' => 'SupplierAccountancyCode', 'enabled' => 1, 'visible' => -1, 'position' => 70),
		'address' => array('type' => 'varchar(255)', 'label' => 'Address', 'enabled' => 1, 'visible' => -1, 'position' => 75),
		'zip' => array('type' => 'varchar(25)', 'label' => 'Zip', 'enabled' => 1, 'visible' => -1, 'position' => 80),
		'town' => array('type' => 'varchar(50)', 'label' => 'Town', 'enabled' => 1, 'visible' => -1, 'position' => 85),
		'fk_departement' => array('type' => 'integer', 'label' => 'State', 'enabled' => 1, 'visible' => -1, 'position' => 90),
		'fk_pays' => array('type' => 'integer:Ccountry:core/class/ccountry.class.php', 'label' => 'Country', 'enabled' => 1, 'visible' => -1, 'position' => 95),
		'phone' => array('type' => 'varchar(20)', 'label' => 'Phone', 'enabled' => 1, 'visible' => -1, 'position' => 100),
		'phone_mobile' => array('type' => 'varchar(20)', 'label' => 'PhoneMobile', 'enabled' => 1, 'visible' => -1, 'position' => 102),
		'fax' => array('type' => 'varchar(20)', 'label' => 'Fax', 'enabled' => 1, 'visible' => -1, 'position' => 105),
		'url' => array('type' => 'varchar(255)', 'label' => 'Url', 'enabled' => 1, 'visible' => -1, 'position' => 110),
		'email' => array('type' => 'varchar(128)', 'label' => 'Email', 'enabled' => 1, 'visible' => -1, 'position' => 115),
		'socialnetworks' => array('type' => 'text', 'label' => 'Socialnetworks', 'enabled' => 1, 'visible' => -1, 'position' => 120),
		'fk_effectif' => array('type' => 'integer', 'label' => 'Workforce', 'enabled' => 1, 'visible' => -1, 'position' => 170),
		'fk_typent' => array('type' => 'integer', 'label' => 'TypeOfCompany', 'enabled' => 1, 'visible' => -1, 'position' => 175, 'csslist' => 'minwidth200'),
		'fk_forme_juridique' => array('type' => 'integer', 'label' => 'JuridicalStatus', 'enabled' => 1, 'visible' => -1, 'position' => 180),
		'fk_currency' => array('type' => 'varchar(3)', 'label' => 'Currency', 'enabled' => 1, 'visible' => -1, 'position' => 185),
		'siren' => array('type' => 'varchar(128)', 'label' => 'Idprof1', 'enabled' => 1, 'visible' => -1, 'position' => 190),
		'siret' => array('type' => 'varchar(128)', 'label' => 'Idprof2', 'enabled' => 1, 'visible' => -1, 'position' => 195),
		'ape' => array('type' => 'varchar(128)', 'label' => 'Idprof3', 'enabled' => 1, 'visible' => -1, 'position' => 200),
		'idprof4' => array('type' => 'varchar(128)', 'label' => 'Idprof4', 'enabled' => 1, 'visible' => -1, 'position' => 205),
		'idprof5' => array('type' => 'varchar(128)', 'label' => 'Idprof5', 'enabled' => 1, 'visible' => -1, 'position' => 206),
		'idprof6' => array('type' => 'varchar(128)', 'label' => 'Idprof6', 'enabled' => 1, 'visible' => -1, 'position' => 207),
		'tva_intra' => array('type' => 'varchar(20)', 'label' => 'Tva intra', 'enabled' => 1, 'visible' => -1, 'position' => 210),
		'capital' => array('type' => 'double(24,8)', 'label' => 'Capital', 'enabled' => 1, 'visible' => -1, 'position' => 215),
		'fk_stcomm' => array('type' => 'integer', 'label' => 'CommercialStatus', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 220),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 225),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 230),
		'prefix_comm' => array('type' => 'varchar(5)', 'label' => 'Prefix comm', 'enabled' => "getDolGlobalInt('SOCIETE_USEPREFIX')", 'visible' => -1, 'position' => 235),
		'client' => array('type' => 'tinyint(4)', 'label' => 'Client', 'enabled' => 1, 'visible' => -1, 'position' => 240),
		'fournisseur' => array('type' => 'tinyint(4)', 'label' => 'Fournisseur', 'enabled' => 1, 'visible' => -1, 'position' => 245),
		'supplier_account' => array('type' => 'varchar(32)', 'label' => 'Supplier account', 'enabled' => 1, 'visible' => -1, 'position' => 250),
		'fk_prospectlevel' => array('type' => 'varchar(12)', 'label' => 'ProspectLevel', 'enabled' => 1, 'visible' => -1, 'position' => 255),
		'customer_bad' => array('type' => 'tinyint(4)', 'label' => 'Customer bad', 'enabled' => 1, 'visible' => -1, 'position' => 260),
		'customer_rate' => array('type' => 'double', 'label' => 'Customer rate', 'enabled' => 1, 'visible' => -1, 'position' => 265),
		'supplier_rate' => array('type' => 'double', 'label' => 'Supplier rate', 'enabled' => 1, 'visible' => -1, 'position' => 270),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -2, 'position' => 275),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => 1, 'visible' => -2, 'notnull' => -1, 'position' => 280),
		//'remise_client' =>array('type'=>'double', 'label'=>'CustomerDiscount', 'enabled'=>1, 'visible'=>-1, 'position'=>285, 'isameasure'=>1),
		//'remise_supplier' =>array('type'=>'double', 'label'=>'SupplierDiscount', 'enabled'=>1, 'visible'=>-1, 'position'=>290, 'isameasure'=>1),
		'mode_reglement' => array('type' => 'tinyint(4)', 'label' => 'Mode reglement', 'enabled' => 1, 'visible' => -1, 'position' => 295),
		'cond_reglement' => array('type' => 'tinyint(4)', 'label' => 'Cond reglement', 'enabled' => 1, 'visible' => -1, 'position' => 300),
		'deposit_percent' => array('type' => 'varchar(63)', 'label' => 'DepositPercent', 'enabled' => 1, 'visible' => -1, 'position' => 301),
		'mode_reglement_supplier' => array('type' => 'integer', 'label' => 'Mode reglement supplier', 'enabled' => 1, 'visible' => -1, 'position' => 305),
		'cond_reglement_supplier' => array('type' => 'integer', 'label' => 'Cond reglement supplier', 'enabled' => 1, 'visible' => -1, 'position' => 308),
		'outstanding_limit' => array('type' => 'double(24,8)', 'label' => 'OutstandingBill', 'enabled' => 1, 'visible' => -1, 'position' => 310, 'isameasure' => 1),
		'order_min_amount' => array('type' => 'double(24,8)', 'label' => 'Order min amount', 'enabled' => 'isModEnabled("order") && !empty($conf->global->ORDER_MANAGE_MIN_AMOUNT)', 'visible' => -1, 'position' => 315, 'isameasure' => 1),
		'supplier_order_min_amount' => array('type' => 'double(24,8)', 'label' => 'Supplier order min amount', 'enabled' => 'isModEnabled("order") && !empty($conf->global->ORDER_MANAGE_MIN_AMOUNT)', 'visible' => -1, 'position' => 320, 'isameasure' => 1),
		'fk_shipping_method' => array('type' => 'integer', 'label' => 'Fk shipping method', 'enabled' => 1, 'visible' => -1, 'position' => 330),
		'tva_assuj' => array('type' => 'tinyint(4)', 'label' => 'Tva assuj', 'enabled' => 1, 'visible' => -1, 'position' => 335),
		'localtax1_assuj' => array('type' => 'tinyint(4)', 'label' => 'Localtax1 assuj', 'enabled' => 1, 'visible' => -1, 'position' => 340),
		'localtax1_value' => array('type' => 'double(6,3)', 'label' => 'Localtax1 value', 'enabled' => 1, 'visible' => -1, 'position' => 345),
		'localtax2_assuj' => array('type' => 'tinyint(4)', 'label' => 'Localtax2 assuj', 'enabled' => 1, 'visible' => -1, 'position' => 350),
		'localtax2_value' => array('type' => 'double(6,3)', 'label' => 'Localtax2 value', 'enabled' => 1, 'visible' => -1, 'position' => 355),
		'vat_reverse_charge' => array('type' => 'tinyint(4)', 'label' => 'Vat reverse charge', 'enabled' => 1, 'visible' => -1, 'position' => 335),
		'barcode' => array('type' => 'varchar(255)', 'label' => 'Barcode', 'enabled' => 1, 'visible' => -1, 'position' => 360),
		'price_level' => array('type' => 'integer', 'label' => 'Price level', 'enabled' => '$conf->global->PRODUIT_MULTIPRICES || getDolGlobalString("PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES")', 'visible' => -1, 'position' => 365),
		'default_lang' => array('type' => 'varchar(6)', 'label' => 'Default lang', 'enabled' => 1, 'visible' => -1, 'position' => 370),
		'canvas' => array('type' => 'varchar(32)', 'label' => 'Canvas', 'enabled' => 1, 'visible' => -1, 'position' => 375),
		'fk_barcode_type' => array('type' => 'integer', 'label' => 'Fk barcode type', 'enabled' => 1, 'visible' => -1, 'position' => 405),
		'webservices_url' => array('type' => 'varchar(255)', 'label' => 'Webservices url', 'enabled' => 1, 'visible' => -1, 'position' => 410),
		'webservices_key' => array('type' => 'varchar(128)', 'label' => 'Webservices key', 'enabled' => 1, 'visible' => -1, 'position' => 415),
		'fk_incoterms' => array('type' => 'integer', 'label' => 'Fk incoterms', 'enabled' => 1, 'visible' => -1, 'position' => 425),
		'location_incoterms' => array('type' => 'varchar(255)', 'label' => 'Location incoterms', 'enabled' => 1, 'visible' => -1, 'position' => 430),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 435),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => -1, 'position' => 270),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'Fk multicurrency', 'enabled' => 1, 'visible' => -1, 'position' => 440),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'Multicurrency code', 'enabled' => 1, 'visible' => -1, 'position' => 445),
		'fk_account' => array('type' => 'integer', 'label' => 'PaymentBankAccount', 'enabled' => 1, 'visible' => -1, 'position' => 450),
		'fk_warehouse' => array('type' => 'integer', 'label' => 'Warehouse', 'enabled' => 1, 'visible' => -1, 'position' => 455),
		'logo' => array('type' => 'varchar(255)', 'label' => 'Logo', 'enabled' => 1, 'visible' => -1, 'position' => 400),
		'logo_squarred' => array('type' => 'varchar(255)', 'label' => 'Logo squarred', 'enabled' => 1, 'visible' => -1, 'position' => 401),
		'status' => array('type' => 'tinyint(4)', 'label' => 'Status', 'enabled' => 1, 'visible' => -1, 'position' => 500),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 1000),
	);

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * Thirdparty name
	 * @var string
	 * @deprecated Use $name instead
	 * @see $name
	 */
	public $nom;

	/**
	 * @var string Thirdparty name
	 */
	public $name;

	/**
	 * Alias names (commercial, trademark or alias names)
	 * @var string
	 */
	public $name_alias;

	/**
	 * @var int Physical thirdparty not a company
	 */
	public $particulier;

	/**
	 * Thirdparty status : 0=activity ceased, 1= in activity
	 * @var int
	 */
	public $status = 1;

	/**
	 * @var string	region code
	 */
	public $region_code;

	/**
	 * @var string Region name
	 */
	public $region;

	/**
	 * @var int country_id
	 */
	public $country_id;

	/**
	 * @var string State code
	 * @deprecated Use $state_code instead
	 * @see $state_code
	 */
	public $departement_code;

	/**
	 * @var string
	 * @deprecated Use $state instead
	 * @see $state
	 */
	public $departement;

	/**
	 * @var string
	 * @deprecated Use $country instead
	 * @see $country
	 */
	public $pays;

	/**
	 * Phone number
	 * @var string
	 */
	public $phone;
	/**
	 * PhoneMobile number
	 * @var string
	 */
	public $phone_mobile;
	/**
	 * Fax number
	 * @var string
	 */
	public $fax;

	/**
	 * Email
	 * @var string
	 */
	public $email;

	/**
	 * No Email
	 * @var int		Set if company email found into unsubscribe of emailing list table
	 */
	public $no_email;

	/**
	 * Skype username
	 * @var string
	 * @deprecated
	 */
	public $skype;

	/**
	 * Twitter username
	 * @var string
	 * @deprecated
	 */
	public $twitter;

	/**
	 * Facebook username
	 * @var string
	 * @deprecated
	 */
	public $facebook;

	/**
	 * LinkedIn username
	 * @var string
	 * @deprecated
	 */
	public $linkedin;

	/**
	 * Webpage
	 * @var string
	 */
	public $url;

	/**
	 * Barcode value
	 * @var string
	 */
	public $barcode;

	// 6 professional id (usage depends on country)

	/**
	 * Professional ID 1 (Ex: Siren in France)
	 * @var string
	 */
	public $idprof1;

	/**
	 * @var string Professional ID 1
	 * @deprecated Use $idprof1 instead
	 * @see $idprof1
	 */
	public $siren;


	/**
	 * Professional ID 2 (Ex: Siret in France)
	 * @var string
	 */
	public $idprof2;

	/**
	 * @var string Professional ID 2
	 * @deprecated Use $idprof2 instead
	 * @see $idprof2
	 */
	public $siret;

	/**
	 * Professional ID 3 (Ex: Ape in France)
	 * @var string
	 */
	public $idprof3;

	/**
	 * @var string Professional ID 3
	 * @deprecated Use $idprof3 instead
	 * @see $idprof3
	 */
	public $ape;

	/**
	 * Professional ID 4 (Ex: RCS in France)
	 * @var string
	 */
	public $idprof4;

	/**
	 * Professional ID 5
	 * @var string
	 */
	public $idprof5;

	/**
	 * Professional ID 6
	 * @var string
	 */
	public $idprof6;

	/**
	 * Professional ID 7
	 * @var string
	 */
	public $idprof7;

	/**
	 * Professional ID 8
	 * @var string
	 */
	public $idprof8;

	/**
	 * Professional ID 9
	 * @var string
	 */
	public $idprof9;

	/**
	 * Professional ID 10
	 * @var string
	 */
	public $idprof10;

	/**
	 * Social object of the company
	 * @var string
	 */
	public $socialobject;

	/**
	 * @var string Prefix comm
	 */
	public $prefix_comm;

	/**
	 * @var int 	Vat concerned
	 */
	public $tva_assuj = 1;

	/**
	 * @var string	Intracommunitary VAT ID
	 */
	public $tva_intra;

	/**
	 * @var int<0,1>	Vat reverse-charge concerned
	 */
	public $vat_reverse_charge = 0;

	// Local taxes
	/**
	 * @var int
	 */
	public $localtax1_assuj;
	/**
	 * @var string
	 */
	public $localtax1_value;
	/**
	 * @var int
	 */
	public $localtax2_assuj;
	/**
	 * @var string
	 */
	public $localtax2_value;

	/**
	 * @var string Manager
	 */
	public $managers;

	/**
	 * @var float Capital
	 */
	public $capital;

	/**
	 * @var int Type thirdparty
	 */
	public $typent_id = 0;
	/**
	 * @var string
	 */
	public $typent_code;
	/**
	 * @var int
	 */
	public $effectif;
	/**
	 * @var int
	 */
	public $effectif_id = 0;
	/**
	 * @var int
	 */
	public $forme_juridique_code = 0;
	/**
	 * @var string Label for Legal Form (of company)
	 * @see CommonDocGenerator::get_substitutionarray_mysoc()
	 */
	public $forme_juridique;

	/**
	 * @var string
	 */
	public $remise_percent;
	/**
	 * @var string
	 */
	public $remise_supplier_percent;

	/**
	 * @var int
	 */
	public $mode_reglement_id;
	/**
	 * @var int
	 */
	public $cond_reglement_id;
	/**
	 * @var string|float
	 */
	public $deposit_percent;
	/**
	 * @var int
	 */
	public $mode_reglement_supplier_id;
	/**
	 * @var int
	 */
	public $cond_reglement_supplier_id;
	/**
	 * @var int
	 */
	public $transport_mode_supplier_id;

	/**
	 * @var string	Prospect level. ie: 'PL_LOW', 'PL...'
	 */
	public $fk_prospectlevel;

	/**
	 * @var string second name
	 */
	public $name_bis;

	/**
	 * User that made last update
	 * @var User
	 * @deprecated
	 */
	public $user_modification;

	/**
	 * User that created the thirdparty
	 * @var User
	 * @deprecated
	 */
	public $user_creation;

	/**
	 * 0=no customer, 1=customer, 2=prospect, 3=customer and prospect
	 * @var int
	 */
	public $client = 0;

	/**
	 * 0=no prospect, 1=prospect
	 * @var int
	 */
	public $prospect = 0;

	/**
	 * 0=no supplier, 1=supplier
	 * @var int
	 */
	public $fournisseur;

	/**
	 * Client code. E.g: CU2014-003
	 * @var string
	 */
	public $code_client;

	/**
	 * Supplier code. E.g: SU2014-003
	 * @var string
	 */
	public $code_fournisseur;

	/**
	 * Accounting code for client
	 * @var string
	 */
	public $code_compta_client;

	/**
	 * Duplicate of code_compta_client (for backward compatibility)
	 * @var string
	 * @deprecated  Use $code_compta_client
	 * @see $code_compta_client
	 */
	public $code_compta;

	/**
	 * Accounting code for customer
	 * @var string
	 */
	public $accountancy_code_customer;

	/**
	 * Accounting code for supplier
	 * @var string
	 */
	public $code_compta_fournisseur;

	/**
	 * Accounting code for supplier
	 * @var string
	 */
	public $accountancy_code_supplier;

	/**
	 * Accounting code for product (for level 3 of suggestion of product accounting account)
	 * @var string
	 */
	public $code_compta_product;

	/**
	 * @var string
	 * @deprecated Use $note_public, $note_private - Note is split in public and private notes
	 * @see $note_public, $note_private
	 */
	public $note;

	/**
	 * Private note
	 * @var string
	 */
	public $note_private;

	/**
	 * Public note
	 * @var string
	 */
	public $note_public;

	/**
	 * Status prospect id
	 * @var int
	 */
	public $stcomm_id;

	/**
	 * Status prospect picto
	 * @var string
	 */
	public $stcomm_picto;

	/**
	 * Status prospect label
	 * @var int
	 */
	public $status_prospect_label;

	/**
	 * Assigned price level
	 * @var int
	 */
	public $price_level;

	/**
	 * @var string outstanding limit
	 */
	public $outstanding_limit;

	/**
	 * @var string Min order amount
	 */
	public $order_min_amount;

	/**
	 * @var string Supplier min order amount
	 */
	public $supplier_order_min_amount;

	/**
	 * Id of sales representative to link (used for thirdparty creation). Not filled by a fetch, because we can have several sales representatives.
	 * @var int
	 */
	public $commercial_id;

	/**
	 * Id of parent thirdparty (if one)
	 * @var int
	 */
	public $parent;

	/**
	 * Default language code of thirdparty (en_US, ...)
	 * @var string
	 */
	public $default_lang;

	/**
	 * @var string Ref
	 */
	public $ref;

	/**
	 * External user reference.
	 * This is to allow external systems to store their id and make self-developed synchronizing functions easier to build.
	 * @var string
	 */
	public $ref_ext;

	/**
	 * Import key.
	 * Set when the thirdparty has been created through an import process. This is to relate those created thirdparties
	 * to an import process
	 * @var string
	 */
	public $import_key;

	/**
	 * Supplier WebServices URL
	 * @var string
	 */
	public $webservices_url;

	/**
	 * Supplier WebServices Key
	 * @var string
	 */
	public $webservices_key;

	/**
	 * @var string Logo
	 */
	public $logo;

	/**
	 * @var string logo small
	 */
	public $logo_small;

	/**
	 * @var string Logo mini
	 */
	public $logo_mini;

	/**
	 * @var string Logo squarred
	 */
	public $logo_squarred;

	/**
	 * @var string Logo squarred small
	 */
	public $logo_squarred_small;

	/**
	 * @var string Logo squarred mini
	 */
	public $logo_squarred_mini;

	/**
	 * @var string Accountancy account for sales
	 */
	public $accountancy_code_sell;

	/**
	 * @var string Accountancy account for bought
	 */
	public $accountancy_code_buy;

	// Multicurrency
	/**
	 * @var int ID
	 */
	public $fk_multicurrency;

	// Warehouse
	/**
	 * @var int ID
	 */
	public $fk_warehouse;

	/**
	 * @var string Multicurrency code
	 */
	public $multicurrency_code;

	// Fields loaded by fetchPartnerships()

	public $partnerships = array();


	/**
	 * @var Account|string Default BAN account
	 */
	public $bank_account;


	const STATUS_CEASED = 0;
	const STATUS_INACTIVITY = 1;

	/**
	 * Third party type is no customer
	 */
	const NO_CUSTOMER = 0;

	/**
	 * Third party type is a customer
	 */
	const CUSTOMER = 1;

	/**
	 * Third party type is a prospect
	 */
	const PROSPECT = 2;

	/**
	 * Third party type is a customer and a prospect
	 */
	const CUSTOMER_AND_PROSPECT = 3;

	/**
	 * Third party supplier flag is not supplier
	 */
	const NO_SUPPLIER = 0;

	/**
	 * Third party supplier flag is a supplier
	 */
	const SUPPLIER = 1;


	/**
	 *    Constructor
	 *
	 *    @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
		$this->client = 0;
		$this->prospect = 0;
		$this->fournisseur = 0;
		$this->typent_id = 0;
		$this->effectif_id = 0;
		$this->forme_juridique_code = 0;
		$this->tva_assuj = 1;
		$this->vat_reverse_charge = 0;
		$this->status = 1;

		if (getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
			$this->fields['address']['showoncombobox'] = getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST');
			$this->fields['zip']['showoncombobox'] = getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST');
			$this->fields['town']['showoncombobox'] = getDolGlobalString('COMPANY_SHOW_ADDRESS_SELECTLIST');
			//$this->fields['fk_pays']['showoncombobox'] = $conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST;
		}
	}


	/**
	 *    Create third party in database.
	 *    $this->code_client = -1 and $this->code_fournisseur = -1 means automatic assignment.
	 *
	 *    @param	User	$user           Object of user that ask creation
	 *    @param    int		$notrigger	    1=Does not execute triggers, 0= execute triggers
	 *    @return   int         		    >=0 if OK, <0 if KO
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $langs, $conf;

		$error = 0;

		// Clean parameters
		if (empty($this->status)) {
			$this->status = 0;
		}
		$this->name = $this->name ? trim($this->name) : trim((string) $this->nom);
		$this->setUpperOrLowerCase();
		$this->nom = $this->name; // For backward compatibility
		if (empty($this->client)) {
			$this->client = 0;
		}
		if (empty($this->fournisseur)) {
			$this->fournisseur = 0;
		}
		$this->import_key = trim((string) $this->import_key);

		$this->code_compta_client = trim(empty($this->code_compta_client) ? $this->code_compta : $this->code_compta_client);

		$this->accountancy_code_customer = trim((string) $this->code_compta_client);
		$this->accountancy_code_supplier = trim((string) $this->code_compta_fournisseur);
		$this->accountancy_code_buy = trim((string) $this->accountancy_code_buy);
		$this->accountancy_code_sell = trim((string) $this->accountancy_code_sell);

		if (!empty($this->multicurrency_code)) {
			$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = '';
			$this->fk_multicurrency = 0;
		}

		dol_syslog(get_class($this)."::create ".$this->name);

		$now = dol_now();

		if (empty($this->date_creation)) {
			$this->date_creation = $now;
		}

		$this->db->begin();

		// For automatic creation during create action (not used by Dolibarr GUI, can be used by scripts)
		if ($this->code_client == -1 || $this->code_client === 'auto') {
			$this->get_codeclient($this, 0);
		}
		if ($this->code_fournisseur == '-1' || $this->code_fournisseur === 'auto') {
			$this->get_codefournisseur($this, 1);
		}

		// Check more parameters (including mandatory setup
		// If error, this->errors[] is filled
		$result = $this->verify();

		if ($result >= 0) {
			$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (";
			$sql .= "nom";
			$sql .= ", name_alias";
			$sql .= ", entity";
			$sql .= ", datec";
			$sql .= ", fk_user_creat";
			$sql .= ", fk_typent";
			$sql .= ", canvas";
			$sql .= ", status";
			$sql .= ", ref_ext";
			$sql .= ", fk_stcomm";
			$sql .= ", fk_incoterms";
			$sql .= ", location_incoterms";
			$sql .= ", import_key";
			$sql .= ", fk_multicurrency";
			$sql .= ", multicurrency_code";
			if (!getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql .= ", vat_reverse_charge";
				$sql .= ", accountancy_code_buy";
				$sql .= ", accountancy_code_sell";
			}
			$sql .= ") VALUES ('".$this->db->escape($this->name)."',";
			$sql .= " '".$this->db->escape($this->name_alias)."',";
			$sql .= " ".((int) $this->entity).",";
			$sql .= " '".$this->db->idate($this->date_creation)."'";
			$sql .= ", ".(!empty($user->id) ? ((int) $user->id) : "null");
			$sql .= ", ".(!empty($this->typent_id) ? ((int) $this->typent_id) : "null");
			$sql .= ", ".(!empty($this->canvas) ? "'".$this->db->escape($this->canvas)."'" : "null");
			$sql .= ", ".((int) $this->status);
			$sql .= ", ".(!empty($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null");
			$sql .= ", 0";
			$sql .= ", ".(int) $this->fk_incoterms;
			$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
			$sql .= ", ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
			$sql .= ", ".(int) $this->fk_multicurrency;
			$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
			if (!getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql .= ", ".(empty($this->vat_reverse_charge) ? '0' : '1');
				$sql .= ", '" . $this->db->escape($this->accountancy_code_buy) . "'";
				$sql .= ", '" . $this->db->escape($this->accountancy_code_sell) . "'";
			}
			$sql .= ")";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);

			$result = $this->db->query($sql);
			if ($result) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe");

				$ret = $this->update($this->id, $user, 0, 1, 1, 'add');

				// update accountancy for this entity
				if (!$error && getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
					$this->db->query("DELETE FROM ".MAIN_DB_PREFIX."societe_perentity WHERE fk_soc = ".((int) $this->id)." AND entity = ".((int) $conf->entity));

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_perentity (";
					$sql .= " fk_soc";
					$sql .= ", entity";
					$sql .= ", vat_reverse_charge";
					$sql .= ", accountancy_code_customer";
					$sql .= ", accountancy_code_supplier";
					$sql .= ", accountancy_code_buy";
					$sql .= ", accountancy_code_sell";
					$sql .= ") VALUES (";
					$sql .= $this->id;
					$sql .= ", ".((int) $conf->entity);
					$sql .= ", ".(empty($this->vat_reverse_charge) ? '0' : '1');
					$sql .= ", '".$this->db->escape($this->accountancy_code_customer)."'";
					$sql .= ", '".$this->db->escape($this->accountancy_code_supplier)."'";
					$sql .= ", '".$this->db->escape($this->accountancy_code_buy)."'";
					$sql .= ", '".$this->db->escape($this->accountancy_code_sell)."'";
					$sql .= ")";
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->error = 'ErrorFailedToUpdateAccountancyForEntity';
					}
				}

				// Ajout du commercial affecte
				if ($this->commercial_id != '' && $this->commercial_id != -1) {
					$this->add_commercial($user, $this->commercial_id);
				} elseif (!$user->hasRight('societe', 'client', 'voir')) {
					// si un commercial cree un client il lui est affecte automatiquement
					$this->add_commercial($user, $user->id);
				}

				if ($ret >= 0) {
					if (! $notrigger) {
						// Call trigger
						$result = $this->call_trigger('COMPANY_CREATE', $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}
				} else {
					$error++;
				}

				if (!$error) {
					dol_syslog(get_class($this)."::Create success id=".$this->id);
					$this->db->commit();
					return $this->id;
				} else {
					dol_syslog(get_class($this)."::Create echec update ".$this->error.(empty($this->errors) ? '' : ' '.implode(',', $this->errors)), LOG_ERR);
					$this->db->rollback();
					return -4;
				}
			} else {
				if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$this->error = $langs->trans("ErrorCompanyNameAlreadyExists", $this->name); // duplicate on a field (code or profid or ...)
					$result = -1;
				} else {
					$this->error = $this->db->lasterror();
					$result = -2;
				}
				$this->db->rollback();
				return $result;
			}
		} else {
			$this->db->rollback();
			dol_syslog(get_class($this)."::Create fails verify ".implode(',', $this->errors), LOG_WARNING);
			return -3;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Create a contact/address from thirdparty
	 *
	 * @param 	User		$user		    Object user
	 * @param 	int<0,1>	$no_email	    1=Do not send mailing, 0=Ok to receive mailing
	 * @param 	string[]	$tags		    Array of tag to affect to contact
	 * @param   int<0,1>    $notrigger	    1=Does not execute triggers, 0= execute triggers
	 * @return 	int<-1,1>				    Return integer <0 if KO, >0 if OK
	 */
	public function create_individual(User $user, $no_email = 0, $tags = array(), $notrigger = 0)
	{
		global $conf;

		$error = 0;

		$this->db->begin();

		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$contact = new Contact($this->db);

		$contact->name              = $this->name_bis;
		$contact->firstname         = $this->firstname;
		$contact->civility_id       = $this->civility_id;
		$contact->socid             = $this->id; // fk_soc
		$contact->statut            = 1; // deprecated
		$contact->status            = 1;
		$contact->priv              = 0;
		$contact->country_id        = $this->country_id;
		$contact->state_id          = $this->state_id;
		$contact->address           = $this->address;
		$contact->email             = $this->email;
		$contact->zip               = $this->zip;
		$contact->town              = $this->town;
		$this->setUpperOrLowerCase();
		$contact->phone_pro         = $this->phone;
		if (getDolGlobalString('CONTACTS_DEFAULT_ROLES')) {
			$contact->roles			= explode(',', getDolGlobalString('CONTACTS_DEFAULT_ROLES'));
		}

		$contactId = $contact->create($user, $notrigger);
		if ($contactId < 0) {
			$error++;
			$this->error = $contact->error;
			$this->errors = $contact->errors;
			dol_syslog(get_class($this)."::create_individual ERROR:".$this->error, LOG_ERR);
		}

		if (empty($error) && is_array($tags) && !empty($tags)) {
			$result = $contact->setCategories($tags);
			if ($result < 0) {
				$error++;
				$this->error = $contact->error;
				$this->errors = array_merge($this->errors, $contact->errors);
				dol_syslog(get_class($this)."::create_individual Affect Tag ERROR:".$this->error, LOG_ERR);
				$contactId = $result;
			}
		}

		if (empty($error) && isModEnabled('mailing') && !empty($contact->email) && isset($no_email)) {
			$result = $contact->setNoEmail($no_email);
			if ($result < 0) {
				$this->error = $contact->error;
				$this->errors = array_merge($this->errors, $contact->errors);
				dol_syslog(get_class($this)."::create_individual set mailing status ERROR:".$this->error, LOG_ERR);
				$contactId = $result;
			}
		}

		if (empty($error)) {
			dol_syslog(get_class($this)."::create_individual success");
			$this->db->commit();
		} else {
			$this->db->rollback();
		}

		return $contactId;
	}

	/**
	 *    Check properties of third party are ok (like name, third party codes, ...)
	 *    Used before an add or update.
	 *
	 *    @return     int		0 if OK, <0 if KO
	 */
	public function verify()
	{
		global $conf, $langs, $mysoc;

		$error = 0;
		$this->errors = array();

		$result = 0;
		$this->name = trim($this->name);
		$this->nom = $this->name; // For backward compatibility

		if (!$this->name) {
			$this->errors[] = 'ErrorBadThirdPartyName';
			$result = -2;
		}

		if ($this->client) {
			$rescode = $this->check_codeclient();
			if ($rescode != 0 && $rescode != -5) {
				if ($rescode == -1) {
					$this->errors[] = 'ErrorBadCustomerCodeSyntax';
				} elseif ($rescode == -2) {
					$this->errors[] = 'ErrorCustomerCodeRequired';
				} elseif ($rescode == -3) {
					$this->errors[] = 'ErrorCustomerCodeAlreadyUsed';
				} elseif ($rescode == -4) {
					$this->errors[] = 'ErrorPrefixRequired';
				} else {
					$this->errors[] = 'ErrorUnknownOnCustomerCodeCheck';
				}

				$result = -3;
			}
		}

		if ($this->fournisseur) {
			$rescode = $this->check_codefournisseur();
			if ($rescode != 0 && $rescode != -5) {
				if ($rescode == -1) {
					$this->errors[] = 'ErrorBadSupplierCodeSyntax';
				} elseif ($rescode == -2) {
					$this->errors[] = 'ErrorSupplierCodeRequired';
				} elseif ($rescode == -3) {
					$this->errors[] = 'ErrorSupplierCodeAlreadyUsed';
				} elseif ($rescode == -4) {
					$this->errors[] = 'ErrorPrefixRequired';
				} else {
					$this->errors[] = 'ErrorUnknownOnSupplierCodeCheck';
				}
				$result = -3;
			}
		}

		// Check for duplicate or mandatory fields defined into setup
		$array_to_check = array('IDPROF1', 'IDPROF2', 'IDPROF3', 'IDPROF4', 'IDPROF5', 'IDPROF6', 'EMAIL', 'TVA_INTRA', 'ACCOUNTANCY_CODE_CUSTOMER', 'ACCOUNTANCY_CODE_SUPPLIER');
		foreach ($array_to_check as $key) {
			$keymin = strtolower($key);
			if ($key == 'ACCOUNTANCY_CODE_CUSTOMER') {
				$keymin = 'code_compta';
			} elseif ($key == 'ACCOUNTANCY_CODE_SUPPLIER') {
				$keymin = 'code_compta_fournisseur';
			}
			$i = (int) preg_replace('/[^0-9]/', '', $key);
			$vallabel = $this->$keymin;

			if ($i > 0) {
				if ($this->isACompany()) {
					// Check for mandatory prof id (but only if country is same than ours)
					if ($mysoc->country_id > 0 && $this->country_id == $mysoc->country_id) {
						$idprof_mandatory = 'SOCIETE_'.$key.'_MANDATORY';
						if (!$vallabel && getDolGlobalString($idprof_mandatory)) {
							$langs->load("errors");
							$error++;
							$this->errors[] = $langs->trans("ErrorProdIdIsMandatory", $langs->transcountry('ProfId'.$i, $this->country_code)).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
					}
				}

				// Check for unicity on profid
				if (!$error && $vallabel && $this->id_prof_verifiable($i)) {
					if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0))) {
						$langs->load("errors");
						$error++;
						$this->errors[] = $langs->transcountry('ProfId'.$i, $this->country_code)." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel).' ('.$langs->trans("ForbiddenBySetupRules").')';
					}
				}
			} else {
				//var_dump($conf->global->SOCIETE_EMAIL_UNIQUE);
				//var_dump($conf->global->SOCIETE_EMAIL_MANDATORY);
				if ($key == 'EMAIL') {
					// Check for mandatory
					if (getDolGlobalString('SOCIETE_EMAIL_MANDATORY') && !isValidEmail($this->email)) {
						$langs->load("errors");
						$error++;
						$this->errors[] = $langs->trans("ErrorBadEMail", $this->email).' ('.$langs->trans("ForbiddenBySetupRules").')';
					}

					// Check for unicity
					if (!$error && $vallabel && getDolGlobalString('SOCIETE_EMAIL_UNIQUE')) {
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0))) {
							$langs->load("errors");
							$error++;
							$this->errors[] = $langs->trans('Email')." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
					}
				} elseif ($key == 'TVA_INTRA') {
					// Check for unicity
					if ($vallabel && getDolGlobalString('SOCIETE_VAT_INTRA_UNIQUE')) {
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0))) {
							$langs->load("errors");
							$error++;
							$this->errors[] = $langs->trans('VATIntra')." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
					}
				} elseif ($key == 'ACCOUNTANCY_CODE_CUSTOMER' && !empty($this->client)) {
					// Check for unicity
					if ($vallabel && getDolGlobalString('SOCIETE_ACCOUNTANCY_CODE_CUSTOMER_UNIQUE')) {
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0))) {
							$langs->loadLangs(array("errors", 'compta'));
							$error++;
							$this->errors[] = $langs->trans('CustomerAccountancyCodeShort') . " " . $langs->trans("ErrorProdIdAlreadyExist", $vallabel) . ' (' . $langs->trans("ForbiddenBySetupRules") . ')';
						}
					}

					// Check for mandatory
					if (getDolGlobalString('SOCIETE_ACCOUNTANCY_CODE_CUSTOMER_MANDATORY') && (!isset($vallabel) || trim($vallabel) === '')) {
						$langs->loadLangs(array("errors", 'compta'));
						$error++;
						$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('CustomerAccountancyCodeShort')) . ' (' . $langs->trans("ForbiddenBySetupRules") . ')';
					}
				} elseif ($key == 'ACCOUNTANCY_CODE_SUPPLIER' && !empty($this->fournisseur)) {
					// Check for unicity
					if ($vallabel && getDolGlobalString('SOCIETE_ACCOUNTANCY_CODE_SUPPLIER_UNIQUE')) {
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0))) {
							$langs->loadLangs(array("errors", 'compta'));
							$error++;
							$this->errors[] = $langs->trans('SupplierAccountancyCodeShort') . " " . $langs->trans("ErrorProdIdAlreadyExist", $vallabel) . ' (' . $langs->trans("ForbiddenBySetupRules") . ')';
						}
					}

					// Check for mandatory
					if (getDolGlobalString('SOCIETE_ACCOUNTANCY_CODE_SUPPLIER_MANDATORY') && (!isset($vallabel) || trim($vallabel) === '')) {
						$langs->loadLangs(array("errors", 'compta'));
						$error++;
						$this->errors[] = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('SupplierAccountancyCodeShort')) . ' (' . $langs->trans("ForbiddenBySetupRules") . ')';
					}
				}
			}
		}

		if ($error) {
			$result = -4;
		}

		return $result;
	}

	/**
	 *      Update parameters of third party
	 *
	 *      @param	int		$id              			Id of company (deprecated, use 0 here and call update on an object loaded by a fetch)
	 *      @param  User	$user            			User who requests the update
	 *      @param  int		$call_trigger    			0=no, 1=yes
	 *		@param	int		$allowmodcodeclient			Inclut modif code client et code compta
	 *		@param	int		$allowmodcodefournisseur	Inclut modif code fournisseur et code compta fournisseur
	 *		@param	string	$action						'add' or 'update' or 'merge'
	 *		@param	int		$nosyncmember				Do not synchronize info of linked member
	 *      @return int  			           			Return integer <0 if KO, >=0 if OK
	 */
	public function update($id, User $user, $call_trigger = 1, $allowmodcodeclient = 0, $allowmodcodefournisseur = 0, $action = 'update', $nosyncmember = 1)
	{
		global $langs, $conf, $hookmanager;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (empty($id)) {
			$id = $this->id;
		}

		$error = 0;

		dol_syslog(get_class($this)."::Update id=".$id." call_trigger=".$call_trigger." allowmodcodeclient=".$allowmodcodeclient." allowmodcodefournisseur=".$allowmodcodefournisseur);

		$now = dol_now();

		// Clean parameters
		$this->id 			= $id;
		$this->entity 		= ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);
		$this->name 		= $this->name ? trim($this->name) : trim((string) $this->nom);
		$this->nom 			= $this->name; // For backward compatibility
		$this->name_alias 	= trim((string) $this->name_alias);
		$this->ref_ext		= (empty($this->ref_ext) ? '' : trim($this->ref_ext));
		$this->address		= trim((string) $this->address);
		$this->zip 			= trim((string) $this->zip);
		$this->town 		= trim((string) $this->town);
		$this->state_id 	= (is_numeric($this->state_id)) ? (int) trim((string) $this->state_id) : 0;
		$this->country_id 	= ($this->country_id > 0) ? $this->country_id : 0;
		$this->phone		= trim((string) $this->phone);
		$this->phone		= preg_replace("/\s/", "", $this->phone);
		$this->phone		= preg_replace("/\./", "", $this->phone);
		$this->phone_mobile		= trim((string) $this->phone_mobile);
		$this->phone_mobile		= preg_replace("/\s/", "", $this->phone_mobile);
		$this->phone_mobile		= preg_replace("/\./", "", $this->phone_mobile);
		$this->fax			= trim((string) $this->fax);
		$this->fax			= preg_replace("/\s/", "", $this->fax);
		$this->fax			= preg_replace("/\./", "", $this->fax);
		$this->email		= trim((string) $this->email);
		$this->url			= $this->url ? clean_url($this->url, 0) : '';
		$this->note_private = (empty($this->note_private) ? '' : trim($this->note_private));
		$this->note_public  = (empty($this->note_public) ? '' : trim($this->note_public));
		$this->idprof1		= trim((string) $this->idprof1);
		$this->idprof2		= trim((string) $this->idprof2);
		$this->idprof3		= trim((string) $this->idprof3);
		$this->idprof4		= trim((string) $this->idprof4);
		$this->idprof5		= (!empty($this->idprof5) ? trim($this->idprof5) : '');
		$this->idprof6		= (!empty($this->idprof6) ? trim($this->idprof6) : '');
		$this->prefix_comm 	= trim((string) $this->prefix_comm);
		$this->outstanding_limit = price2num($this->outstanding_limit);
		$this->order_min_amount = price2num($this->order_min_amount);
		$this->supplier_order_min_amount = price2num($this->supplier_order_min_amount);

		$this->tva_assuj			= (is_numeric($this->tva_assuj)) ? (int) trim((string) $this->tva_assuj) : 0;
		$this->tva_intra			= dol_sanitizeFileName($this->tva_intra, '');
		$this->vat_reverse_charge	= empty($this->vat_reverse_charge) ? 0 : 1;
		if (empty($this->status)) {
			$this->status = 0;
		}

		if (!empty($this->multicurrency_code)) {
			$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = '';
			$this->fk_multicurrency = 0;
		}

		// Local taxes
		$this->localtax1_assuj = (int) trim((string) $this->localtax1_assuj);
		$this->localtax2_assuj = (int) trim((string) $this->localtax2_assuj);

		$this->localtax1_value = trim($this->localtax1_value);
		$this->localtax2_value = trim($this->localtax2_value);

		$this->capital = ($this->capital != '') ? (float) price2num(trim((string) $this->capital)) : null;

		$this->effectif_id = (int) trim((string) $this->effectif_id);
		$this->forme_juridique_code = (int) trim((string) $this->forme_juridique_code);

		//Gencod
		$this->barcode = trim($this->barcode);

		// For automatic creation
		if ($this->code_client == -1 || $this->code_client === 'auto') {
			$this->get_codeclient($this, 0);
		}
		if ($this->code_fournisseur == '-1' || $this->code_fournisseur === 'auto') {
			$this->get_codefournisseur($this, 1);
		}

		$this->code_compta_client = trim(empty($this->code_compta_client) ? $this->code_compta : $this->code_compta_client);
		$this->code_compta = $this->code_compta_client; // for backward compatibility
		$this->code_compta_fournisseur = (empty($this->code_compta_fournisseur) ? '' : trim($this->code_compta_fournisseur));

		// Check parameters. More tests are done later in the ->verify()
		if (!is_numeric($this->client) && !is_numeric($this->fournisseur)) {
			$langs->load("errors");
			$this->error = $langs->trans("BadValueForParameterClientOrSupplier");
			return -1;
		}

		$customer = false;
		if (!empty($allowmodcodeclient) && !empty($this->client)) {
			// If $allowmodcodeclient is set and value is not set, we generate it
			if (empty($this->code_compta_client)) {
				$ret = $this->get_codecompta('customer');
				if ($ret < 0) {
					return -1;
				}
			}

			$customer = true;
		}

		$supplier = false;
		if (!empty($allowmodcodefournisseur) && !empty($this->fournisseur)) {
			// If $allowmodcodefournisseur is set and value is not set, we generate it
			if (empty($this->code_compta_fournisseur)) {
				$ret = $this->get_codecompta('supplier');
				if ($ret < 0) {
					return -1;
				}
			}

			$supplier = true;
		}

		//Web services
		$this->webservices_url = $this->webservices_url ? clean_url($this->webservices_url, 0) : '';
		$this->webservices_key = trim($this->webservices_key);

		$this->accountancy_code_buy = (empty($this->accountancy_code_buy) ? '' : trim($this->accountancy_code_buy));
		$this->accountancy_code_sell = (empty($this->accountancy_code_sell) ? '' : trim($this->accountancy_code_sell));

		//Incoterms
		$this->fk_incoterms = (int) $this->fk_incoterms;
		$this->location_incoterms = trim($this->location_incoterms);

		$this->db->begin();

		// Check name is required and codes are ok or unique.
		// If error, this->errors[] is filled
		$result = 0;
		if ($action != 'add' && $action != 'merge') {
			// We don't check when update called during a create because verify was already done.
			// For a merge, we suppose source data is clean and a customer code of a deleted thirdparty must be accepted into a target thirdparty with empty code without duplicate error
			$result = $this->verify();

			// If there is only one error and error is ErrorBadCustomerCodeSyntax and we don't change customer code, we allow the update
			// So we can update record that were using and old numbering rule.
			if (is_array($this->errors)) {
				if (in_array('ErrorBadCustomerCodeSyntax', $this->errors) && is_object($this->oldcopy) && $this->oldcopy->code_client == $this->code_client) {
					if (($key = array_search('ErrorBadCustomerCodeSyntax', $this->errors)) !== false) {
						unset($this->errors[$key]); // Remove error message
					}
				}
				if (in_array('ErrorBadSupplierCodeSyntax', $this->errors) && is_object($this->oldcopy) && $this->oldcopy->code_fournisseur == $this->code_fournisseur) {
					if (($key = array_search('ErrorBadSupplierCodeSyntax', $this->errors)) !== false) {
						unset($this->errors[$key]); // Remove error message
					}
				}
				if (empty($this->errors)) {	// If there is no more error, we can make like if there is no error at all
					$result = 0;
				}
			}
		}
		$this->setUpperOrLowerCase();
		if ($result >= 0) {
			dol_syslog(get_class($this)."::update verify ok or not done");

			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
			$sql .= "entity = ".$this->db->escape($this->entity);
			$sql .= ",nom = '".$this->db->escape($this->name)."'"; // Required
			$sql .= ",name_alias = '".$this->db->escape($this->name_alias)."'";
			$sql .= ",ref_ext = ".(!empty($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null");
			$sql .= ",address = '".$this->db->escape($this->address)."'";

			$sql .= ",zip = ".(!empty($this->zip) ? "'".$this->db->escape($this->zip)."'" : "null");
			$sql .= ",town = ".(!empty($this->town) ? "'".$this->db->escape($this->town)."'" : "null");

			$sql .= ",fk_departement = ".((!empty($this->state_id) && $this->state_id > 0) ? ((int) $this->state_id) : 'null');
			$sql .= ",fk_pays = ".((!empty($this->country_id) && $this->country_id > 0) ? ((int) $this->country_id) : 'null');

			$sql .= ",phone = ".(!empty($this->phone) ? "'".$this->db->escape($this->phone)."'" : "null");
			$sql .= ",phone_mobile = ".(!empty($this->phone_mobile) ? "'".$this->db->escape($this->phone_mobile)."'" : "null");
			$sql .= ",fax = ".(!empty($this->fax) ? "'".$this->db->escape($this->fax)."'" : "null");
			$sql .= ",email = ".(!empty($this->email) ? "'".$this->db->escape($this->email)."'" : "null");
			$sql .= ",socialnetworks = '".$this->db->escape(json_encode($this->socialnetworks))."'";
			$sql .= ",url = ".(!empty($this->url) ? "'".$this->db->escape($this->url)."'" : "null");

			$sql .= ",parent = ".($this->parent > 0 ? $this->parent : "null");

			$sql .= ",note_private = ".(!empty($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null");
			$sql .= ",note_public = ".(!empty($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null");

			$sql .= ",siren   = '".$this->db->escape($this->idprof1)."'";
			$sql .= ",siret   = '".$this->db->escape($this->idprof2)."'";
			$sql .= ",ape     = '".$this->db->escape($this->idprof3)."'";
			$sql .= ",idprof4 = '".$this->db->escape($this->idprof4)."'";
			$sql .= ",idprof5 = '".$this->db->escape($this->idprof5)."'";
			$sql .= ",idprof6 = '".$this->db->escape($this->idprof6)."'";

			$sql .= ",tva_assuj = ".($this->tva_assuj != '' ? "'".$this->db->escape($this->tva_assuj)."'" : "null");
			$sql .= ",tva_intra = '".$this->db->escape($this->tva_intra)."'";
			if (!getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql .= ",vat_reverse_charge = " . ($this->vat_reverse_charge != '' ? "'" . $this->db->escape($this->vat_reverse_charge) . "'" : 0);
			}
			$sql .= ",status = ".((int) $this->status);

			// Local taxes
			$sql .= ",localtax1_assuj = ".($this->localtax1_assuj != '' ? "'".$this->db->escape($this->localtax1_assuj)."'" : "null");
			$sql .= ",localtax2_assuj = ".($this->localtax2_assuj != '' ? "'".$this->db->escape($this->localtax2_assuj)."'" : "null");
			if ($this->localtax1_assuj == 1) {
				if ($this->localtax1_value != '') {
					$sql .= ",localtax1_value =".$this->localtax1_value;
				} else {
					$sql .= ",localtax1_value =0.000";
				}
			} else {
				$sql .= ",localtax1_value =0.000";
			}

			if ($this->localtax2_assuj == 1) {
				if ($this->localtax2_value != '') {
					$sql .= ",localtax2_value =".$this->localtax2_value;
				} else {
					$sql .= ",localtax2_value =0.000";
				}
			} else {
				$sql .= ",localtax2_value =0.000";
			}

			$sql .= ",capital = ".($this->capital === null ? "null" : $this->capital);

			$sql .= ",prefix_comm = ".(!empty($this->prefix_comm) ? "'".$this->db->escape($this->prefix_comm)."'" : "null");

			$sql .= ",fk_effectif = ".($this->effectif_id > 0 ? ((int) $this->effectif_id) : "null");
			if (isset($this->stcomm_id)) {
				$sql .= ",fk_stcomm=".(int) $this->stcomm_id;
			}
			if (isset($this->typent_id)) {
				$sql .= ",fk_typent = ".($this->typent_id > 0 ? ((int) $this->typent_id) : "0");
			}

			$sql .= ",fk_forme_juridique = ".(!empty($this->forme_juridique_code) ? "'".$this->db->escape($this->forme_juridique_code)."'" : "null");

			$sql .= ",mode_reglement = ".(!empty($this->mode_reglement_id) ? "'".$this->db->escape($this->mode_reglement_id)."'" : "null");
			$sql .= ",cond_reglement = ".(!empty($this->cond_reglement_id) ? "'".$this->db->escape($this->cond_reglement_id)."'" : "null");
			$sql .= ",deposit_percent = ".(!empty($this->deposit_percent) ? "'".$this->db->escape($this->deposit_percent)."'" : "null");
			$sql .= ",transport_mode = ".(!empty($this->transport_mode_id) ? "'".$this->db->escape($this->transport_mode_id)."'" : "null");
			$sql .= ",mode_reglement_supplier = ".(!empty($this->mode_reglement_supplier_id) ? "'".$this->db->escape($this->mode_reglement_supplier_id)."'" : "null");
			$sql .= ",cond_reglement_supplier = ".(!empty($this->cond_reglement_supplier_id) ? "'".$this->db->escape($this->cond_reglement_supplier_id)."'" : "null");
			$sql .= ",transport_mode_supplier = ".(!empty($this->transport_mode_supplier_id) ? "'".$this->db->escape($this->transport_mode_supplier_id)."'" : "null");
			$sql .= ",fk_shipping_method = ".(!empty($this->shipping_method_id) ? "'".$this->db->escape($this->shipping_method_id)."'" : "null");

			$sql .= ",client = ".(!empty($this->client) ? $this->client : 0);
			$sql .= ",fournisseur = ".(!empty($this->fournisseur) ? $this->fournisseur : 0);
			$sql .= ",barcode = ".(!empty($this->barcode) ? "'".$this->db->escape($this->barcode)."'" : "null");
			$sql .= ",default_lang = ".(!empty($this->default_lang) ? "'".$this->db->escape($this->default_lang)."'" : "null");
			$sql .= ",logo = ".(!empty($this->logo) ? "'".$this->db->escape($this->logo)."'" : "null");
			$sql .= ",logo_squarred = ".(!empty($this->logo_squarred) ? "'".$this->db->escape($this->logo_squarred)."'" : "null");
			$sql .= ",outstanding_limit= ".($this->outstanding_limit != '' ? $this->outstanding_limit : 'null');
			$sql .= ",order_min_amount= ".($this->order_min_amount != '' ? $this->order_min_amount : 'null');
			$sql .= ",supplier_order_min_amount= ".($this->supplier_order_min_amount != '' ? $this->supplier_order_min_amount : 'null');
			$sql .= ",fk_prospectlevel='".$this->db->escape($this->fk_prospectlevel)."'";
			if (!getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
				$sql .= ", accountancy_code_buy = '" . $this->db->escape($this->accountancy_code_buy) . "'";
				$sql .= ", accountancy_code_sell= '" . $this->db->escape($this->accountancy_code_sell) . "'";
				if ($customer) {
					$sql .= ", code_compta = ".(!empty($this->code_compta_client) ? "'".$this->db->escape($this->code_compta_client)."'" : "null");
				}

				if ($supplier) {
					$sql .= ", code_compta_fournisseur = ".(($this->code_compta_fournisseur != "") ? "'".$this->db->escape($this->code_compta_fournisseur)."'" : "null");
				}
			}
			$sql .= ",webservices_url = ".(!empty($this->webservices_url) ? "'".$this->db->escape($this->webservices_url)."'" : "null");
			$sql .= ",webservices_key = ".(!empty($this->webservices_key) ? "'".$this->db->escape($this->webservices_key)."'" : "null");

			//Incoterms
			$sql .= ", fk_incoterms = ".((int) $this->fk_incoterms);
			$sql .= ", location_incoterms = ".(!empty($this->location_incoterms) ? "'".$this->db->escape($this->location_incoterms)."'" : "null");

			if ($customer) {
				$sql .= ", code_client = ".(!empty($this->code_client) ? "'".$this->db->escape($this->code_client)."'" : "null");
			}

			if ($supplier) {
				$sql .= ", code_fournisseur = ".(!empty($this->code_fournisseur) ? "'".$this->db->escape($this->code_fournisseur)."'" : "null");
			}
			$sql .= ", fk_user_modif = ".($user->id > 0 ? $user->id : "null");
			$sql .= ", fk_multicurrency = ".(int) $this->fk_multicurrency;
			$sql .= ", multicurrency_code = '".$this->db->escape($this->multicurrency_code)."'";
			$sql .= ", model_pdf = '".$this->db->escape($this->model_pdf)."'";
			$sql .= " WHERE rowid = ".(int) $id;

			$resql = $this->db->query($sql);
			if ($resql) {
				if (is_object($this->oldcopy)) {	// If we have information on old values
					if ($this->oldcopy->country_id != $this->country_id) {
						unset($this->country_code);
						unset($this->country);
					}
					if ($this->oldcopy->state_id != $this->state_id) {
						unset($this->state_code);
						unset($this->state);
					}
				} else {
					unset($this->country_code); // We clean this, in the doubt, because it may have been changed after an update of country_id
					unset($this->country);
					unset($this->state_code);
					unset($this->state);
				}

				$nbrowsaffected = $this->db->affected_rows($resql);

				if (!$error && $nbrowsaffected) {
					// Update information on linked member if it is an update
					if (!$nosyncmember && isModEnabled('member')) {
						require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

						dol_syslog(get_class($this)."::update update linked member");

						$lmember = new Adherent($this->db);
						$result = $lmember->fetch(0, 0, $this->id);

						if ($result > 0) {
							$lmember->company = $this->name;
							//$lmember->firstname=$this->firstname?$this->firstname:$lmember->firstname;	// We keep firstname and lastname of member unchanged
							//$lmember->lastname=$this->lastname?$this->lastname:$lmember->lastname;		// We keep firstname and lastname of member unchanged
							$lmember->address = $this->address;
							$lmember->zip = $this->zip;
							$lmember->town = $this->town;
							$lmember->email = $this->email;
							$lmember->socialnetworks = $this->socialnetworks;
							$lmember->phone = $this->phone;
							$lmember->state_id = $this->state_id;
							$lmember->country_id = $this->country_id;
							$lmember->default_lang = $this->default_lang;

							$result = $lmember->update($user, 0, 1, 1, 1); // Use nosync to 1 to avoid cyclic updates
							if ($result < 0) {
								$this->error = $lmember->error;
								$this->errors = array_merge($this->errors, $lmember->errors);
								dol_syslog(get_class($this)."::update ".$this->error, LOG_ERR);
								$error++;
							}
						} elseif ($result < 0) {
							$this->error = $lmember->error;
							$error++;
						}
					}
				}

				$action = 'update';

				// update accountancy for this entity
				if (!$error && getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
					$this->db->query("DELETE FROM ".MAIN_DB_PREFIX."societe_perentity WHERE fk_soc = ".((int) $this->id)." AND entity = ".((int) $conf->entity));

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_perentity (";
					$sql .= " fk_soc";
					$sql .= ", entity";
					$sql .= ", vat_reverse_charge";
					$sql .= ", accountancy_code_customer";
					$sql .= ", accountancy_code_supplier";
					$sql .= ", accountancy_code_buy";
					$sql .= ", accountancy_code_sell";
					$sql .= ") VALUES (";
					$sql .= $this->id;
					$sql .= ", ".$conf->entity;
					$sql .= ", ".(empty($this->vat_reverse_charge) ? '0' : '1');
					$sql .= ", '".$this->db->escape($this->code_compta_client)."'";
					$sql .= ", '".$this->db->escape($this->code_compta_fournisseur)."'";
					$sql .= ", '".$this->db->escape($this->accountancy_code_buy)."'";
					$sql .= ", '".$this->db->escape($this->accountancy_code_sell)."'";
					$sql .= ")";
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->error = 'ErrorFailedToUpdateAccountancyForEntity';
					}
				}

				// Actions on extra fields
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}
				// Actions on extra languages
				if (!$error && !getDolGlobalString('MAIN_EXTRALANGUAGES_DISABLED')) { // For avoid conflicts if trigger used
					$result = $this->insertExtraLanguages();
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error && $call_trigger) {
					// Call trigger
					$result = $this->call_trigger('COMPANY_MODIFY', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error) {
					dol_syslog(get_class($this)."::Update success");
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					// Doublon
					$this->error = $langs->trans("ErrorDuplicateField");
					$result = -1;
				} else {
					$this->error = $this->db->lasterror();
					$result = -2;
				}
				$this->db->rollback();
				return $result;
			}
		} else {
			$this->db->rollback();
			dol_syslog(get_class($this)."::Update fails verify ".implode(',', $this->errors), LOG_WARNING);
			return -3;
		}
	}

	/**
	 *    Load a third party from database into memory
	 *
	 *    @param	int		$rowid			Id of third party to load
	 *    @param    string	$ref			Reference of third party, name (Warning, this can return several records)
	 *    @param    string	$ref_ext       	External reference of third party (Warning, this information is a free field not provided by Dolibarr)
	 *    @param    string	$barcode       	Barcode of third party to load
	 *    @param    string	$idprof1		Prof id 1 of third party (Warning, this can return several records)
	 *    @param    string	$idprof2		Prof id 2 of third party (Warning, this can return several records)
	 *    @param    string	$idprof3		Prof id 3 of third party (Warning, this can return several records)
	 *    @param    string	$idprof4		Prof id 4 of third party (Warning, this can return several records)
	 *    @param    string	$idprof5		Prof id 5 of third party (Warning, this can return several records)
	 *    @param    string	$idprof6		Prof id 6 of third party (Warning, this can return several records)
	 *    @param    string	$email   		Email of third party (Warning, this can return several records)
	 *    @param    string	$ref_alias 		Name_alias of third party (Warning, this can return several records)
	 * 	  @param	int		$is_client		Only client third party
	 *    @param	int		$is_supplier	Only supplier third party
	 *    @return   int						>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '', $is_client = 0, $is_supplier = 0)
	{
		global $langs;
		global $conf;

		if (empty($rowid) && empty($ref) && empty($ref_ext) && empty($barcode) && empty($idprof1) && empty($idprof2) && empty($idprof3) && empty($idprof4) && empty($idprof5) && empty($idprof6) && empty($email) && empty($ref_alias)) {
			return -1;
		}

		$sql = 'SELECT s.rowid, s.nom as name, s.name_alias, s.entity, s.ref_ext, s.address, s.datec as date_creation, s.prefix_comm';
		$sql .= ', s.status, s.fk_warehouse';
		$sql .= ', s.price_level';
		$sql .= ', s.tms as date_modification, s.fk_user_creat, s.fk_user_modif';
		$sql .= ', s.phone, s.phone_mobile, s.fax, s.email';
		$sql .= ', s.socialnetworks';
		$sql .= ', s.url, s.zip, s.town, s.note_private, s.note_public, s.client, s.fournisseur';
		$sql .= ', s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6';
		$sql .= ', s.capital, s.tva_intra';
		$sql .= ', s.fk_typent as typent_id';
		$sql .= ', s.fk_effectif as effectif_id';
		$sql .= ', s.fk_forme_juridique as forme_juridique_code';
		$sql .= ', s.webservices_url, s.webservices_key, s.model_pdf, s.last_main_doc';
		if (!getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
			$sql .= ', s.code_compta, s.code_compta_fournisseur, s.accountancy_code_buy, s.accountancy_code_sell';
			$sql .= ', s.vat_reverse_charge as soc_vat_reverse_charge';
		} else {
			$sql .= ', spe.accountancy_code_customer as code_compta, spe.accountancy_code_supplier as code_compta_fournisseur, spe.accountancy_code_buy, spe.accountancy_code_sell';
			$sql .= ', spe.vat_reverse_charge as spe_vat_reverse_charge';
		}
		$sql .= ', s.code_client, s.code_fournisseur, s.parent, s.barcode';
		$sql .= ', s.fk_departement as state_id, s.fk_pays as country_id, s.fk_stcomm, s.mode_reglement, s.cond_reglement, s.deposit_percent, s.transport_mode';
		$sql .= ', s.fk_account, s.tva_assuj';
		$sql .= ', s.mode_reglement_supplier, s.cond_reglement_supplier, s.transport_mode_supplier';
		$sql .= ', s.localtax1_assuj, s.localtax1_value, s.localtax2_assuj, s.localtax2_value, s.fk_prospectlevel, s.default_lang, s.logo, s.logo_squarred';
		$sql .= ', s.fk_shipping_method';
		$sql .= ', s.outstanding_limit, s.import_key, s.canvas, s.fk_incoterms, s.location_incoterms';
		$sql .= ', s.order_min_amount, s.supplier_order_min_amount';
		$sql .= ', s.fk_multicurrency, s.multicurrency_code';
		$sql .= ', fj.libelle as forme_juridique';
		$sql .= ', e.libelle as effectif';
		$sql .= ', c.code as country_code, c.label as country';
		$sql .= ', d.code_departement as state_code, d.nom as state';
		$sql .= ', r.rowid as region_id, r.code_region as region_code';
		$sql .= ', st.libelle as stcomm, st.picto as stcomm_picto';
		$sql .= ', te.code as typent_code';
		$sql .= ', i.libelle as label_incoterms';
		if (!isModEnabled('multicompany')) {
			$sql .= ', s.remise_client, s.remise_supplier';
		} else {
			$sql .= ', sr.remise_client, sr2.remise_supplier';
		}
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_perentity as spe ON spe.fk_soc = s.rowid AND spe.entity = ".((int) $conf->entity);
		}
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as e ON s.fk_effectif = e.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as fj ON s.fk_forme_juridique = fj.code';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_regions as r ON d.fk_region = r.code_region ';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as te ON s.fk_typent = te.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON s.fk_incoterms = i.rowid';
		// With default setup, llx_societe_remise is a history table in default setup and current value is in llx_societe.
		// We use it for real value when multicompany is on. A better place would be into llx_societe_perentity.
		if (isModEnabled('multicompany')) {
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_remise as sr ON sr.rowid = (SELECT MAX(rowid) FROM '.MAIN_DB_PREFIX.'societe_remise WHERE fk_soc = s.rowid AND entity IN ('.getEntity('discount').'))';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_remise_supplier as sr2 ON sr2.rowid = (SELECT MAX(rowid) FROM '.MAIN_DB_PREFIX.'societe_remise_supplier WHERE fk_soc = s.rowid AND entity IN ('.getEntity('discount').'))';
		}
		$sql .= ' WHERE s.entity IN ('.getEntity($this->element).')';

		// Filter on client or supplier, for Client::fetch() and Fournisseur::fetch()
		if ($is_client) {
			$sql .= ' AND s.client > 0';
		}
		if ($is_supplier) {
			$sql .= ' AND s.fournisseur > 0';
		} // if both false, no test (the thirdparty can be client and/or supplier)

		if ($rowid) {
			$sql .= ' AND s.rowid = '.((int) $rowid);
		}
		if ($ref) {
			$sql .= " AND s.nom = '".$this->db->escape($ref)."'";
		}
		if ($ref_alias) {
			$sql .= " AND s.name_alias = '".$this->db->escape($ref_alias)."'";
		}
		if ($ref_ext) {
			$sql .= " AND s.ref_ext = '".$this->db->escape($ref_ext)."'";
		}
		if ($barcode) {
			$sql .= " AND s.barcode = '".$this->db->escape($barcode)."'";
		}
		if ($idprof1) {
			$sql .= " AND s.siren = '".$this->db->escape($idprof1)."'";
		}
		if ($idprof2) {
			$sql .= " AND s.siret = '".$this->db->escape($idprof2)."'";
		}
		if ($idprof3) {
			$sql .= " AND s.ape = '".$this->db->escape($idprof3)."'";
		}
		if ($idprof4) {
			$sql .= " AND s.idprof4 = '".$this->db->escape($idprof4)."'";
		}
		if ($idprof5) {
			$sql .= " AND s.idprof5 = '".$this->db->escape($idprof5)."'";
		}
		if ($idprof6) {
			$sql .= " AND s.idprof6 = '".$this->db->escape($idprof6)."'";
		}
		if ($email) {
			$sql .= " AND s.email = '".$this->db->escape($email)."'";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 1) {
				$this->error = 'Fetch found several records. Rename one of thirdparties to avoid duplicate.';
				dol_syslog($this->error, LOG_ERR);
				$result = -2;
			} elseif ($num) {   // $num = 1
				$obj = $this->db->fetch_object($resql);

				$this->id           = $obj->rowid;
				$this->entity       = $obj->entity;
				$this->canvas = $obj->canvas;

				$this->ref          = $obj->rowid;
				$this->name = $obj->name;
				$this->nom          = $obj->name; // deprecated
				$this->name_alias = $obj->name_alias;
				$this->ref_ext      = $obj->ref_ext;

				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->user_creation_id     = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;

				$this->address = $obj->address;
				$this->zip 			= $obj->zip;
				$this->town 		= $obj->town;

				$this->country_id   = $obj->country_id;
				$this->country_code = $obj->country_id ? $obj->country_code : '';
				$this->country = $obj->country_id ? (($langs->transnoentities('Country'.$obj->country_code) != 'Country'.$obj->country_code) ? $langs->transnoentities('Country'.$obj->country_code) : $obj->country) : '';

				$this->state_id     = $obj->state_id;
				$this->state_code   = $obj->state_code;
				$this->region_id    = $obj->region_id;
				$this->region_code  = $obj->region_code;
				$this->state        = ($obj->state != '-' ? $obj->state : '');

				$transcode = $langs->trans('StatusProspect'.$obj->fk_stcomm);
				$label = ($transcode != 'StatusProspect'.$obj->fk_stcomm ? $transcode : $obj->stcomm);
				$this->stcomm_id = $obj->fk_stcomm; // id status prospect
				$this->status_prospect_label = $label; // label status prospect
				$this->stcomm_picto = $obj->stcomm_picto; // picto statut commercial

				$this->email = $obj->email;
				$this->socialnetworks = ($obj->socialnetworks ? (array) json_decode($obj->socialnetworks, true) : array());

				$this->url = $obj->url;
				$this->phone = $obj->phone;
				$this->phone_mobile = $obj->phone_mobile;
				$this->fax = $obj->fax;

				$this->parent = $obj->parent;

				$this->idprof1		= $obj->idprof1;
				$this->idprof2		= $obj->idprof2;
				$this->idprof3		= $obj->idprof3;
				$this->idprof4		= $obj->idprof4;
				$this->idprof5		= $obj->idprof5;
				$this->idprof6		= $obj->idprof6;

				$this->capital = $obj->capital;

				$this->code_client = $obj->code_client;
				$this->code_fournisseur = $obj->code_fournisseur;

				$this->code_compta = $obj->code_compta;			// For backward compatibility
				$this->code_compta_client = $obj->code_compta;
				$this->code_compta_fournisseur = $obj->code_compta_fournisseur;

				$this->barcode = $obj->barcode;

				$this->tva_assuj			= $obj->tva_assuj;
				$this->tva_intra			= $obj->tva_intra;

				if (!empty($obj->spe_vat_reverse_charge)) {
					$this->vat_reverse_charge = $obj->spe_vat_reverse_charge;
				} elseif (!empty($obj->soc_vat_reverse_charge)) {
					$this->vat_reverse_charge = $obj->soc_vat_reverse_charge;
				} else {
					$this->vat_reverse_charge = 0;
				}

				$this->status				= $obj->status;

				// Local Taxes
				$this->localtax1_assuj      = $obj->localtax1_assuj;
				$this->localtax2_assuj      = $obj->localtax2_assuj;

				$this->localtax1_value		= $obj->localtax1_value;
				$this->localtax2_value		= $obj->localtax2_value;

				$this->typent_id      = $obj->typent_id;
				$this->typent_code    = $obj->typent_code;

				$this->effectif_id    = $obj->effectif_id;
				$this->effectif       = $obj->effectif_id ? $obj->effectif : '';

				$this->forme_juridique_code = $obj->forme_juridique_code;
				$this->forme_juridique = $obj->forme_juridique_code ? $obj->forme_juridique : '';

				$this->fk_prospectlevel = $obj->fk_prospectlevel;

				$this->prefix_comm = $obj->prefix_comm;

				$this->remise_percent = $obj->remise_client ? price2num($obj->remise_client) : 0; // 0.000000 must be 0
				$this->remise_supplier_percent = $obj->remise_supplier;

				$this->mode_reglement_id 	= $obj->mode_reglement;
				$this->cond_reglement_id 	= $obj->cond_reglement;
				$this->deposit_percent		= $obj->deposit_percent;
				$this->transport_mode_id 	= $obj->transport_mode;
				$this->mode_reglement_supplier_id 	= $obj->mode_reglement_supplier;
				$this->cond_reglement_supplier_id 	= $obj->cond_reglement_supplier;
				$this->transport_mode_supplier_id = $obj->transport_mode_supplier;
				$this->shipping_method_id = ($obj->fk_shipping_method > 0) ? $obj->fk_shipping_method : null;
				$this->fk_account = $obj->fk_account;

				$this->client = $obj->client;
				$this->fournisseur = $obj->fournisseur;

				$this->note = $obj->note_private; // TODO Deprecated for backward comtability
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->model_pdf = $obj->model_pdf;
				$this->default_lang = $obj->default_lang;
				$this->logo = $obj->logo;
				$this->logo_squarred = $obj->logo_squarred;

				$this->webservices_url = $obj->webservices_url;
				$this->webservices_key = $obj->webservices_key;

				$this->accountancy_code_buy     = $obj->accountancy_code_buy;
				$this->accountancy_code_sell    = $obj->accountancy_code_sell;

				$this->outstanding_limit		= $obj->outstanding_limit;
				$this->order_min_amount			= $obj->order_min_amount;
				$this->supplier_order_min_amount = $obj->supplier_order_min_amount;

				// multiprix
				$this->price_level = $obj->price_level;

				// warehouse
				$this->fk_warehouse = $obj->fk_warehouse;

				$this->import_key = $obj->import_key;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;

				// multicurrency
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;

				// pdf
				$this->model_pdf = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;

				$result = 1;

				// fetch optionals attributes and labels
				$this->fetch_optionals();
			} else {
				$result = 0;
			}

			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			$result = -3;
		}

		// Use first price level if level not defined for third party
		if ((getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) && empty($this->price_level)) {
			$this->price_level = 1;
		}

		return $result;
	}

	/**
	 *    Delete a third party from database and all its dependencies (contacts, rib...)
	 *
	 *    @param	int			$id             Id of third party to delete
	 *    @param    User|null   $fuser          User who ask to delete thirdparty
	 *    @param    int			$call_trigger   0=No, 1=yes
	 *    @return	int							Return integer <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function delete($id, User $fuser = null, $call_trigger = 1)
	{
		global $conf, $user;

		if (empty($fuser)) {
			$fuser = $user;
		}

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$entity = isset($this->entity) ? $this->entity : $conf->entity;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$error = 0;

		// Test if child exists
		$objectisused = $this->isObjectUsed($id);
		if (empty($objectisused)) {
			$this->db->begin();

			// User is mandatory for trigger call
			if (!$error && $call_trigger) {
				// Call trigger
				$result = $this->call_trigger('COMPANY_DELETE', $fuser);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
				$static_cat = new Categorie($this->db);
				$toute_categs = array();

				// Fill $toute_categs array with an array of (type => array of ("Categorie" instance))
				$toute_categs['customer'] = $static_cat->containing($this->id, Categorie::TYPE_CUSTOMER);
				$toute_categs['supplier'] = $static_cat->containing($this->id, Categorie::TYPE_SUPPLIER);

				// Remove each "Categorie"
				foreach ($toute_categs as $type => $categs_type) {
					foreach ($categs_type as $cat) {
						$cat->del_type($this, $type);
					}
				}
			}

			if (!$error) {
				foreach ($this->childtablesoncascade as $tabletodelete) {
					$deleteFromObject = explode(':', $tabletodelete, 4);
					if (count($deleteFromObject) >= 2) {
						$className = str_replace('@', '', $deleteFromObject[0]);
						$filepath = $deleteFromObject[1];
						$columnName = $deleteFromObject[2];
						if (dol_include_once($filepath)) {
							$child_object = new $className($this->db);
							'@phan-var-force CommonObject $child_object';
							$result = $child_object->deleteByParentField($id, $columnName);
							if ($result < 0) {
								$error++;
								$this->errors[] = $child_object->error;
								break;
							}
						} else {
							$error++;
							$this->errors[] = 'Cannot include child class file '.$filepath;
							break;
						}
					} else {
						$sql = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete;
						$sql .= " WHERE fk_soc = ".((int) $id);
						if (!$this->db->query($sql)) {
							$error++;
							$this->errors[] = $this->db->lasterror();
							break;
						}
					}
				}
			}

			// Removed extrafields
			if (!$error) {
				$result = $this->deleteExtraFields();
				if ($result < 0) {
					$error++;
					dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
				}
			}

			// Remove links to subsidiaries companies
			if (!$error) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
				$sql .= " SET parent = NULL";
				$sql .= " WHERE parent = ".((int) $id);
				if (!$this->db->query($sql)) {
					$error++;
					$this->errors[] = $this->db->lasterror();
				}
			}

			// Remove third party
			if (!$error) {
				if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
					$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_perentity";
					$sql .= " WHERE fk_soc = ".((int) $id);
					if (!$this->db->query($sql)) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}

				$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe";
				$sql .= " WHERE rowid = ".((int) $id);
				if (!$this->db->query($sql)) {
					$error++;
					$this->errors[] = $this->db->lasterror();
				}
			}

			if (!$error) {
				$this->db->commit();

				// Delete directory
				if (!empty($conf->societe->multidir_output[$entity])) {
					$docdir = $conf->societe->multidir_output[$entity]."/".$id;
					if (dol_is_dir($docdir)) {
						dol_delete_dir_recursive($docdir);
					}
				}

				return 1;
			} else {
				dol_syslog($this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_syslog("Can't remove thirdparty with id ".$id.". There are ".$objectisused." children", LOG_WARNING);
		}
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Define third party as a customer
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 *  @deprecated Use setAsCustomer() instead
	 *  @see setAsCustomer()
	 */
	public function set_as_client()
	{
		global $conf;
		// phpcs:enable
		dol_syslog(get_class($this)."::set_as_client is deprecated use setAsCustomer instead", LOG_NOTICE);
		return $this->setAsCustomer();
	}

	/**
	 *  Define third party as a customer
	 *
	 *	@return		int		Return integer <0 if KO, >0 if OK
	 *  @since dolibarr v19
	 */
	public function setAsCustomer()
	{
		if ($this->id) {
			$newclient = 1;
			if (($this->client == 2 || $this->client == 3) && !getDolGlobalInt('SOCIETE_DISABLE_PROSPECTSCUSTOMERS')) {
				$newclient = 3; //If prospect, we keep prospect tag
			}
			$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET client = ".((int) $newclient);
			$sql .= " WHERE rowid = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				$this->client = $newclient;
				return 1;
			} else {
				return -1;
			}
		}
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Defines the company as a customer
	 *
	 *  @param	float	$remise		Value in % of the discount
	 *  @param  string	$note		Note/Reason for changing the discount
	 *  @param  User	$user		User who sets the discount
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function set_remise_client($remise, $note, User $user)
	{
		// phpcs:enable
		global $conf, $langs;

		// Parameter cleaning
		$note = trim($note);
		if (!$note) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NoteReason"));
			return -2;
		}

		dol_syslog(get_class($this)."::set_remise_client ".$remise.", ".$note.", ".$user->id);

		if ($this->id) {
			$this->db->begin();

			$now = dol_now();

			// Position current discount
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql .= " SET remise_client = '".$this->db->escape($remise)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -1;
			}

			// Writes trace in discount history
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise";
			$sql .= " (entity, datec, fk_soc, remise_client, note, fk_user_author)";
			$sql .= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', ".((int) $this->id).", '".$this->db->escape($remise)."',";
			$sql .= " '".$this->db->escape($note)."',";
			$sql .= " ".((int) $user->id);
			$sql .= ")";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}

			$this->db->commit();

			return 1;
		}
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Defines the company as a customer
	 *
	 *  @param	float	$remise		Value in % of the discount
	 *  @param  string	$note		Note/Reason for changing the discount
	 *  @param  User	$user		User who sets the discount
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function set_remise_supplier($remise, $note, User $user)
	{
		// phpcs:enable
		global $conf, $langs;

		// Parameter cleaning
		$note = trim($note);
		if (!$note) {
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NoteReason"));
			return -2;
		}

		dol_syslog(get_class($this)."::set_remise_supplier ".$remise.", ".$note.", ".$user->id);

		if ($this->id) {
			$this->db->begin();

			$now = dol_now();

			// Position current discount
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql .= " SET remise_supplier = '".$this->db->escape($remise)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -1;
			}

			// Writes trace in discount history
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_supplier";
			$sql .= " (entity, datec, fk_soc, remise_supplier, note, fk_user_author)";
			$sql .= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', ".((int) $this->id).", '".$this->db->escape($remise)."',";
			$sql .= " '".$this->db->escape($note)."',";
			$sql .= " ".((int) $user->id);
			$sql .= ")";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}

			$this->db->commit();
			return 1;
		}

		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Add a discount for third party
	 *
	 *    	@param	float	$remise     		Amount of discount
	 *    	@param  User	$user       		User adding discount
	 *    	@param  string	$desc				Reason of discount
	 *      @param  string	$vatrate     		VAT rate (may contain the vat code too). Example: '1.23', '1.23 (ABC)', ...
	 *      @param	int		$discount_type		0 => customer discount, 1 => supplier discount
	 *      @param	string	$price_base_type	Price base type 'HT' or 'TTC'
	 *		@return	int							Return integer <0 if KO, id of discount record if OK
	 */
	public function set_remise_except($remise, User $user, $desc, $vatrate = '', $discount_type = 0, $price_base_type = 'HT')
	{
		// phpcs:enable
		global $langs;

		// Clean parameters
		$remise = price2num($remise);
		$desc = trim($desc);

		// Check parameters
		if (!($remise > 0)) {
			$this->error = $langs->trans("ErrorWrongValueForParameter", "1");
			return -1;
		}
		if (!$desc) {
			$this->error = $langs->trans("ErrorWrongValueForParameter", "3");
			return -2;
		}

		if ($this->id > 0) {
			// Clean vat code
			$reg = array();
			$vat_src_code = '';
			if (preg_match('/\((.*)\)/', $vatrate, $reg)) {
				$vat_src_code = $reg[1];
				$vatrate = preg_replace('/\s*\(.*\)/', '', $vatrate); // Remove code into vatrate.
			}

			require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

			$discount = new DiscountAbsolute($this->db);
			$discount->fk_soc = $this->id;
			$discount->socid = $this->id;

			$discount->discount_type = $discount_type;

			if ($price_base_type == 'TTC') {
				$discount->amount_ttc = $discount->multicurrency_amount_ttc = price2num($remise, 'MT');
				$discount->amount_ht = $discount->multicurrency_amount_ht = price2num((float) $remise / (1 + (float) $vatrate / 100), 'MT');
				$discount->amount_tva = $discount->multicurrency_amount_tva = price2num((float) $discount->amount_ttc - (float) $discount->amount_ht, 'MT');
			} else {
				$discount->amount_ht = $discount->multicurrency_amount_ht = price2num($remise, 'MT');
				$discount->amount_tva = $discount->multicurrency_amount_tva = price2num((float) $remise * (float) $vatrate / 100, 'MT');
				$discount->amount_ttc = $discount->multicurrency_amount_ttc = price2num((float) $discount->amount_ht + (float) $discount->amount_tva, 'MT');
			}

			$discount->tva_tx = (float) price2num($vatrate);
			$discount->vat_src_code = $vat_src_code;

			$discount->description = $desc;

			$result = $discount->create($user);
			if ($result > 0) {
				return $result;
			} else {
				$this->error = $discount->error;
				return -3;
			}
		} else {
			return 0;
		}
	}

	/**
	 * 	Returns amount of included taxes of the current discounts/credits available from the company
	 *
	 *	@param	?User		$user			Filter on a user author of discounts
	 * 	@param	string		$filter			Other filter
	 * 	@param	int			$maxvalue		Filter on max value for discount
	 * 	@param	int<0,1>	$discount_type	0 => customer discount, 1 => supplier discount
	 *	@return	float|int<-1,-1>		Return integer <0 if KO, Credit note amount otherwise
	 */
	public function getAvailableDiscounts($user = null, $filter = '', $maxvalue = 0, $discount_type = 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic = new DiscountAbsolute($this->db);
		$result = $discountstatic->getAvailableDiscounts($this, $user, $filter, $maxvalue, $discount_type);
		if ($result >= 0) {
			return $result;
		} else {
			$this->error = $discountstatic->error;
			return -1;
		}
	}

	/**
	 *  Return array of sales representatives
	 *
	 *  @param	User		$user			Object user (not used)
	 *  @param	int			$mode			0=Array with properties, 1=Array of IDs.
	 *  @param	string		$sortfield		List of sort fields, separated by comma. Example: 't1.fielda,t2.fieldb'
	 *  @param	string		$sortorder		Sort order, separated by comma. Example: 'ASC,DESC';
	 *  @return array|int      				Array of sales representatives of the current third party or <0 if KO
	 */
	public function getSalesRepresentatives(User $user, $mode = 0, $sortfield = null, $sortorder = null)
	{
		global $conf;

		$reparray = array();

		$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.office_phone, u.job, u.email, u.statut as status, u.entity, u.photo, u.gender";
		$sql .= ", u.office_fax, u.user_mobile, u.personal_mobile";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."user as u";
		// Condition here should be the same than into select_dolusers()
		if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
			$sql .= " WHERE u.rowid IN (SELECT ug.fk_user FROM ".$this->db->prefix()."usergroup_user as ug WHERE ug.entity IN (".getEntity('usergroup')."))";
		} else {
			$sql .= " WHERE entity IN (0, ".$this->db->sanitize($conf->entity).")";
		}

		$sql .= " AND u.rowid = sc.fk_user AND sc.fk_soc = ".((int) $this->id);
		if (empty($sortfield) && empty($sortorder)) {
			$sortfield = 'u.lastname,u.firstname';
			$sortorder = 'ASC,ASC';
		}
		$sql .= $this->db->order($sortfield, $sortorder);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if (empty($mode)) {
					$reparray[$i]['id'] = $obj->rowid;
					$reparray[$i]['lastname'] = $obj->lastname;
					$reparray[$i]['firstname'] = $obj->firstname;
					$reparray[$i]['email'] = $obj->email;
					$reparray[$i]['phone'] = $obj->office_phone;
					$reparray[$i]['office_phone'] = $obj->office_phone;			// Pro phone
					$reparray[$i]['office_fax'] = $obj->office_fax;
					$reparray[$i]['user_mobile'] = $obj->user_mobile;			// Pro mobile
					$reparray[$i]['personal_mobile'] = $obj->personal_mobile;	// Personal mobile
					$reparray[$i]['job'] = $obj->job;
					$reparray[$i]['statut'] = $obj->status; // deprecated
					$reparray[$i]['status'] = $obj->status;
					$reparray[$i]['entity'] = $obj->entity;
					$reparray[$i]['login'] = $obj->login;
					$reparray[$i]['photo'] = $obj->photo;
					$reparray[$i]['gender'] = $obj->gender;
				} else {
					$reparray[] = $obj->rowid;
				}
				$i++;
			}
			return $reparray;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Set the price level
	 *
	 * @param 	int		$price_level	Level of price
	 * @param 	User	$user			Use making change
	 * @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setPriceLevel($price_level, User $user)
	{
		if ($this->id) {
			$now = dol_now();

			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " SET price_level = ".((int) $price_level);
			$sql .= " WHERE rowid = ".((int) $this->id);

			if (!$this->db->query($sql)) {
				dol_print_error($this->db);
				return -1;
			}

			$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_prices";
			$sql .= " (datec, fk_soc, price_level, fk_user_author)";
			$sql .= " VALUES ('".$this->db->idate($now)."', ".((int) $this->id).", ".((int) $price_level).", ".((int) $user->id).")";

			if (!$this->db->query($sql)) {
				dol_print_error($this->db);
				return -1;
			}
			return 1;
		}
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add link to sales representative
	 *
	 *	@param	User	$user		Object user
	 *	@param	int		$commid		Id of user
	 *	@return	int					Return integer <=0 if KO, >0 if OK
	 */
	public function add_commercial(User $user, $commid)
	{
		// phpcs:enable
		$error = 0;

		if ($this->id > 0 && $commid > 0) {
			$this->db->begin();

			if (!$error) {
				$sql = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux";
				$sql .= " WHERE fk_soc = ".((int) $this->id)." AND fk_user = ".((int) $commid);

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_syslog(get_class($this)."::add_commercial Error ".$this->db->lasterror());
					$error++;
				}
			}

			if (!$error) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux";
				$sql .= " (fk_soc, fk_user)";
				$sql .= " VALUES (".((int) $this->id).", ".((int) $commid).")";

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_syslog(get_class($this)."::add_commercial Error ".$this->db->lasterror());
					$error++;
				}
			}

			if (!$error) {
				$this->context = array('commercial_modified' => $commid);

				$result = $this->call_trigger('COMPANY_LINK_SALE_REPRESENTATIVE', $user);
				if ($result < 0) {
					$error++;
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

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add link to sales representative
	 *
	 *	@param	User	$user		Object user
	 *	@param	int		$commid		Id of user
	 *	@return	int					Return <0 if KO, >0 if OK
	 */
	public function del_commercial(User $user, $commid)
	{
		// phpcs:enable
		$error = 0;
		$this->context = array('commercial_modified' => $commid);

		$result = $this->call_trigger('COMPANY_UNLINK_SALE_REPRESENTATIVE', $user);
		if ($result < 0) {
			$error++;
		}

		if ($this->id > 0 && $commid > 0) {
			$sql  = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux ";
			$sql .= " WHERE fk_soc = ".((int) $this->id)." AND fk_user = ".((int) $commid);

			if (!$this->db->query($sql)) {
				$error++;
				dol_syslog(get_class($this)."::del_commercial Erreur");
			}
		}

		if ($error) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @return array<string,string> Data to show in tooltip
	 * @since v18
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;

		$langs->loadLangs(['companies', 'commercial']);

		$datas = array();

		$option = $params['option'] ?? '';
		$nofetch = !empty($params['nofetch']);

		$noaliasinname = (empty($params['noaliasinname']) ? 0 : $params['noaliasinname']);

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			return ['optimize' => $langs->trans("ShowCompany")];
		}

		if (!empty($this->logo) && class_exists('Form')) {
			$photo = '<div class="photointooltip floatright">';
			$photo .= Form::showphoto('societe', $this, 0, 40, 0, 'photoref', 'mini', 0); // Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$photo .= '</div>';
			$datas['photo'] = $photo;
		} //elseif (!empty($this->logo_squarred) && class_exists('Form')) {
		/*$label.= '<div class="photointooltip">';
		$label.= Form::showphoto('societe', $this, 0, 40, 0, 'photowithmargin', 'mini', 0);	// Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
		$label.= '</div><div style="clear: both;"></div>';*/
		// }

		$datas['divopen'] = '<div class="centpercent">';

		if ($option == 'customer' || $option == 'compta' || $option == 'category') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Customer").'</u>';
		} elseif ($option == 'prospect' && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Prospect").'</u>';
		} elseif ($option == 'supplier' || $option == 'category_supplier') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Supplier").'</u>';
		} elseif ($option == 'agenda') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
		} elseif ($option == 'project') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
		} elseif ($option == 'margin') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
		} elseif ($option == 'contact') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
		} elseif ($option == 'ban') {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
		}

		// By default
		if (empty($datas['picto'])) {
			$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
		}
		if (isset($this->status)) {
			$datas['status'] = ' '.$this->getLibStatut(5);
		}
		if (isset($this->client) && isset($this->fournisseur)) {
			$datas['type'] = ' &nbsp; ' . $this->getTypeUrl(1);
		}
		$datas['name'] = '<br><b>'.$langs->trans('Name').':</b> '.dol_escape_htmltag(dol_string_nohtmltag($this->name));
		if (!empty($this->name_alias) && empty($noaliasinname)) {
			$datas['namealias'] = ' ('.dol_escape_htmltag(dol_string_nohtmltag($this->name_alias)).')';
		}
		if (!empty($this->email)) {
			$datas['email'] = '<br>'.img_picto('', 'email', 'class="pictofixedwidth"').$this->email;
		}
		if (!empty($this->url)) {
			$datas['url'] = '<br>'.img_picto('', 'globe', 'class="pictofixedwidth"').$this->url;
		}
		if (!empty($this->phone) || !empty($this->phone_mobile) || !empty($this->fax)) {
			$phonelist = array();
			if ($this->phone) {
				$phonelist[] = dol_print_phone($this->phone, $this->country_code, $this->id, 0, '', '&nbsp', 'phone');
			}
			// deliberately not making new list because fax uses same list as phone
			if ($this->phone_mobile) {
				$phonelist[] = dol_print_phone($this->phone_mobile, $this->country_code, $this->id, 0, '', '&nbsp', 'phone_mobile');
			}
			if ($this->fax) {
				$phonelist[] = dol_print_phone($this->fax, $this->country_code, $this->id, 0, '', '&nbsp', 'fax');
			}
			$datas['phonelist'] = '<br>'.implode('&nbsp;', $phonelist);
		}

		if (!empty($this->address)) {
			$datas['address'] = '<br><b>'.$langs->trans("Address").':</b> '.dol_format_address($this, 1, ' ', $langs); // Address + country
		} elseif (!empty($this->country_code)) {
			$datas['address'] = '<br><b>'.$langs->trans('Country').':</b> '.$this->country_code;
		}
		if (!empty($this->tva_intra) || (getDolGlobalString('SOCIETE_SHOW_FIELD_IN_TOOLTIP') && strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'vatnumber') !== false)) {
			$datas['vatintra'] = '<br><b>'.$langs->trans('VATIntra').':</b> '.dol_escape_htmltag($this->tva_intra);
		}

		if (getDolGlobalString('SOCIETE_SHOW_FIELD_IN_TOOLTIP')) {
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid1') !== false && $langs->trans('ProfId1'.$this->country_code) != '-') {
				$datas['profid1'] = '<br><b>'.$langs->trans('ProfId1'.$this->country_code).':</b> '.$this->idprof1;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid2') !== false && $langs->trans('ProfId2'.$this->country_code) != '-') {
				$datas['profid2'] = '<br><b>'.$langs->trans('ProfId2'.$this->country_code).':</b> '.$this->idprof2;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid3') !== false && $langs->trans('ProfId3'.$this->country_code) != '-') {
				$datas['profid3'] = '<br><b>'.$langs->trans('ProfId3'.$this->country_code).':</b> '.$this->idprof3;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid4') !== false && $langs->trans('ProfId4'.$this->country_code) != '-') {
				$datas['profid4'] = '<br><b>'.$langs->trans('ProfId4'.$this->country_code).':</b> '.$this->idprof4;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid5') !== false && $langs->trans('ProfId5'.$this->country_code) != '-') {
				$datas['profid5'] = '<br><b>'.$langs->trans('ProfId5'.$this->country_code).':</b> '.$this->idprof5;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid6') !== false && $langs->trans('ProfId6'.$this->country_code) != '-') {
				$datas['profid6'] = '<br><b>'.$langs->trans('ProfId6'.$this->country_code).':</b> '.$this->idprof6;
			}
		}

		$datas['separator'] = '<br>';

		if (!empty($this->code_client) && ($this->client == 1 || $this->client == 3)) {
			$datas['customercode'] = '<br><b>'.$langs->trans('CustomerCode').':</b> '.$this->code_client;
		}
		if (isModEnabled('accounting') && ($this->client == 1 || $this->client == 3)) {
			$langs->load('compta');
			$datas['accountancycustomercode'] = '<br><b>'.$langs->trans('CustomerAccountancyCode').':</b> '.($this->code_compta ? $this->code_compta : $this->code_compta_client);
		}
		// show categories for this record only in ajax to not overload lists
		if (!$nofetch && isModEnabled('category') && $this->client) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$form = new Form($this->db);
			$datas['categories_customer'] = '<br>' . $form->showCategories($this->id, Categorie::TYPE_CUSTOMER, 1, 1);
		}
		if (!empty($this->code_fournisseur) && $this->fournisseur) {
			$datas['suppliercode'] = '<br><b>'.$langs->trans('SupplierCode').':</b> '.$this->code_fournisseur;
		}
		if (isModEnabled('accounting') && $this->fournisseur) {
			$langs->load('compta');
			$datas['accountancysuppliercode'] = '<br><b>'.$langs->trans('SupplierAccountancyCode').':</b> '.$this->code_compta_fournisseur;
		}
		// show categories for this record only in ajax to not overload lists
		if (!$nofetch && isModEnabled('category') && $this->fournisseur) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$form = new Form($this->db);
			$datas['categories_supplier'] = '<br>' . $form->showCategories($this->id, Categorie::TYPE_SUPPLIER, 1, 1);
		}

		$datas['divclose'] = '</div>';

		return $datas;
	}

	/**
	 *    	Return a link on thirdparty (with picto)
	 *
	 *		@param	int		$withpicto		          	Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
	 *		@param	string	$option			          	Target of link (''=auto, 'nolink'=no link, 'customer', 'prospect', 'supplier', 'project', 'agenda', ...)
	 *		@param	int		$maxlen			          	Max length of name
	 *      @param	int  	$notooltip		          	1=Disable tooltip
	 *      @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *      @param	int		$noaliasinname			  	1=Do not add alias into the link ref
	 *      @param	string	$target			  		  	add attribute target
	 *      @param	string	$morecss					More CSS
	 *		@return	string					          	String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0, $notooltip = 0, $save_lastsearch_value = -1, $noaliasinname = 0, $target = '', $morecss = '')
	{
		global $conf, $langs, $hookmanager, $user;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$name = $this->name ? $this->name : $this->nom;

		if (getDolGlobalString('SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD')) {
			if (empty($option) && $this->client > 0) {
				$option = 'customer';
			}
			if (empty($option) && $this->fournisseur > 0) {
				$option = 'supplier';
			}
		}

		if (getDolGlobalString('SOCIETE_ADD_REF_IN_LIST') && (!empty($withpicto))) {
			$code = '';
			if (($this->client) && (!empty($this->code_client)) && (getDolGlobalInt('SOCIETE_ADD_REF_IN_LIST') == 1 || getDolGlobalInt('SOCIETE_ADD_REF_IN_LIST') == 2)) {
				$code = $this->code_client.' - ';
			}

			if (($this->fournisseur) && (!empty($this->code_fournisseur)) && (getDolGlobalInt('SOCIETE_ADD_REF_IN_LIST') == 1 || getDolGlobalInt('SOCIETE_ADD_REF_IN_LIST') == 3)) {
				$code .= $this->code_fournisseur.' - ';
			}

			if ($code) {
				if (getDolGlobalInt('SOCIETE_ADD_REF_IN_LIST') == 1) {
					$name = $code.' '.$name;
				} else {
					$name = $code;
				}
			}
		}

		if (!empty($this->name_alias) && empty($noaliasinname)) {
			$name .= ' ('.$this->name_alias.')';
		}

		$result = '';
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

		$linkstart = '';
		$linkend = '';

		if ($option == 'customer' || $option == 'compta' || $option == 'category') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
		} elseif ($option == 'prospect' && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
		} elseif ($option == 'supplier' || $option == 'category_supplier') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$this->id;
		} elseif ($option == 'agenda') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/agenda.php?socid='.$this->id;
		} elseif ($option == 'project') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/project.php?socid='.$this->id;
		} elseif ($option == 'margin') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/margin/tabs/thirdpartyMargins.php?socid='.$this->id.'&type=1';
		} elseif ($option == 'contact') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/contact.php?socid='.$this->id;
		} elseif ($option == 'ban') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$this->id;
		}

		// By default
		if (empty($linkstart)) {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$this->id;
		}

		// Add type of canvas
		$linkstart .= (!empty($this->canvas) ? '&canvas='.$this->canvas : '');
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$linkstart .= '&save_lastsearch_values=1';
		}
		$linkstart .= '"';

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowCompany");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').' refurl valignmiddle"';
			$target_value = array('_self', '_blank', '_parent', '_top');
			if (in_array($target, $target_value)) {
				$linkclose .= ' target="'.dol_escape_htmltag($target).'"';
			}
		} else {
			$linkclose .= ' class="valignmiddle'.($morecss ? ' '.$morecss : '').'"';
		}
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if (!$user->hasRight('societe', 'client', 'voir') && $user->socid > 0 && $this->id != $user->socid) {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (' class="'.(($withpicto != 2) ? 'paddingright' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= dol_escape_htmltag($maxlen ? dol_trunc($name, $maxlen) : $name);
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('thirdpartydao'));
		$parameters = array(
			'id' => $this->id,
			'getnomurl' => &$result,
			'withpicto ' => $withpicto,
			'option' => $option,
			'maxlen' => $maxlen,
			'notooltip' => $notooltip,
			'save_lastsearch_value' => $save_lastsearch_value
		);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *    	Return link(s) on type of thirdparty (with picto)
	 *
	 *		@param	int		$withpicto		        Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
	 *		@param	string	$option					''=All
	 *      @param	int  	$notooltip		        1=Disable tooltip
	 *      @param	string	$tag					Tag 'a' or 'span'
	 *		@return	string					        String with URL
	 */
	public function getTypeUrl($withpicto = 0, $option = '', $notooltip = 0, $tag = 'a')
	{
		global $conf, $langs;

		$s = '';
		if (empty($option) || preg_match('/prospect/', $option)) {
			if (($this->client == 2 || $this->client == 3) && !getDolGlobalString('SOCIETE_DISABLE_PROSPECTS')) {
				$s .= '<'.$tag.' class="customer-back opacitymedium" title="'.$langs->trans("Prospect").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id.'">'.dol_substr($langs->trans("Prospect"), 0, 1).'</'.$tag.'>';
			}
		}
		if (empty($option) || preg_match('/customer/', $option)) {
			if (($this->client == 1 || $this->client == 3) && !getDolGlobalString('SOCIETE_DISABLE_CUSTOMERS')) {
				$s .= '<'.$tag.' class="customer-back" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id.'">'.dol_substr($langs->trans("Customer"), 0, 1).'</'.$tag.'>';
			}
		}
		if (empty($option) || preg_match('/supplier/', $option)) {
			if ((isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && $this->fournisseur) {
				$s .= '<'.$tag.' class="vendor-back" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$this->id.'">'.dol_substr($langs->trans("Supplier"), 0, 1).'</'.$tag.'>';
			}
		}
		return $s;
	}


	/**
	 *	Return label of status (activity, closed)
	 *
	 *	@param	int<0,6>	$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *	@return	string     		   		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int			$status		Status id
	 *  @param	int<0,6>	$mode		0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string          		Status label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;
		$langs->load('companies');

		$statusType = 'status4';
		if ($status == 0) {
			$statusType = 'status6';
		}

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			$this->labelStatus[0] = $langs->transnoentitiesnoconv("ActivityCeased");
			$this->labelStatus[1] = $langs->transnoentitiesnoconv("InActivity");
			$this->labelStatusShort[0] = $langs->transnoentitiesnoconv("ActivityCeased");
			$this->labelStatusShort[1] = $langs->transnoentitiesnoconv("InActivity");
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of contacts emails existing for third party
	 *
	 *	@param	int<0,2>	$addthirdparty		1=Add also a record for thirdparty email, 2=Same than 1 but add text ThirdParty in grey
	 *	@return	array<'thirdparty'|int,string>	Array of contact's emails
	 */
	public function thirdparty_and_contact_email_array($addthirdparty = 0)
	{
		// phpcs:enable
		global $langs;

		$contact_emails = $this->contact_property_array('email', 1);

		if ($this->email && $addthirdparty) {
			if (empty($this->name)) {
				$this->name = $this->nom;
			}
			$contact_emails['thirdparty'] = ($addthirdparty == 2 ? '<span class="opacitymedium">' : '').$langs->transnoentitiesnoconv("ThirdParty").($addthirdparty == 2 ? '</span>' : '').': '.dol_trunc($this->name, 16)." <".$this->email.">";
		}

		//var_dump($contact_emails)
		return $contact_emails;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of contacts mobile phone existing for third party
	 *
	 *	@return	array<'thirdparty'|int,string>	Array of contacts mobile phone
	 */
	public function thirdparty_and_contact_phone_array()
	{
		// phpcs:enable
		global $langs;

		$contact_phone = $this->contact_property_array('mobile');

		if (!empty($this->phone)) {	// If a phone of thirdparty is defined, we add it to mobile of contacts
			if (empty($this->name)) {
				$this->name = $this->nom;
			}
			// TODO: Tester si tel non deja present dans tableau contact
			$contact_phone['thirdparty'] = $langs->transnoentitiesnoconv("ThirdParty").': '.dol_trunc($this->name, 16)." <".$this->phone.">";
		}
		return $contact_phone;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of contacts emails or mobile existing for third party
	 *
	 *  @param	'email'|'mobile'	$mode       	'email' or 'mobile'
	 * 	@param	int<0,1>			$hidedisabled	1=Hide contact if disabled
	 *  @return string[]    	   					Array of contacts emails or mobile. Example: array(id=>'Name <email>')
	 */
	public function contact_property_array($mode = 'email', $hidedisabled = 0)
	{
		// phpcs:enable
		global $langs;

		$contact_property = array();


		$sql = "SELECT rowid, email, statut as status, phone_mobile, lastname, poste, firstname";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql .= " WHERE fk_soc = ".((int) $this->id);
		$sql .= " AND entity IN (".getEntity($this->element).")";
		$sql .= " ORDER BY lastname, firstname";

		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$sepa = "(";
				$sepb = ")";
				if ($mode == 'email') {
					//$sepa="&lt;"; $sepb="&gt;";
					$sepa = "<";
					$sepb = ">";
				}
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);
					if ($mode == 'email') {
						$property = $obj->email;
					} elseif ($mode == 'mobile') {
						$property = $obj->phone_mobile;
					} else {
						$property = $obj->$mode;
					}

					// Show all contact. If hidedisabled is 1, showonly contacts with status = 1
					if ($obj->status == 1 || empty($hidedisabled)) {
						if (empty($property)) {
							if ($mode == 'email') {
								$property = $langs->transnoentitiesnoconv("NoEMail");
							} elseif ($mode == 'mobile') {
								$property = $langs->transnoentitiesnoconv("NoMobilePhone");
							}
						}

						if (!empty($obj->poste)) {
							$contact_property[$obj->rowid] = trim(dolGetFirstLastname($obj->firstname, $obj->lastname)).($obj->poste ? " - ".$obj->poste : "").(($mode != 'poste' && $property) ? " ".$sepa.$property.$sepb : '');
						} else {
							$contact_property[$obj->rowid] = trim(dolGetFirstLastname($obj->firstname, $obj->lastname)).(($mode != 'poste' && $property) ? " ".$sepa.$property.$sepb : '');
						}
					}
					$i++;
				}
			}
		} else {
			dol_print_error($this->db);
		}
		return $contact_property;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return the contact list of this company
	 *
	 *	@return	string[]	$contacts	array of contacts
	 */
	public function contact_array()
	{
		// phpcs:enable
		$contacts = array();

		$sql = "SELECT rowid, lastname, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);
					$contacts[$obj->rowid] = dolGetFirstLastname($obj->firstname, $obj->lastname);
					$i++;
				}
			}
		} else {
			dol_print_error($this->db);
		}
		return $contacts;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return the contact list of this company
	 *
	 *	@return	Contact[]	$contacts	array of contacts
	 */
	public function contact_array_objects()
	{
		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$contacts = array();

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($resql);
					$contact = new Contact($this->db);
					$contact->fetch($obj->rowid);
					$contacts[] = $contact;
					$i++;
				}
			}
		} else {
			dol_print_error($this->db);
		}
		return $contacts;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return property of contact from its id
	 *
	 *  @param	int		$rowid      id of contact
	 *  @param  string	$mode       'email' or 'mobile'
	 *  @return string  			Email of contact with format: "Full name <email>"
	 */
	public function contact_get_property($rowid, $mode)
	{
		// phpcs:enable
		$contact_property = '';

		if (empty($rowid)) {
			return '';
		}

		$sql = "SELECT rowid, email, phone_mobile, lastname, firstname";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql .= " WHERE rowid = ".((int) $rowid);

		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);

			if ($nump) {
				$obj = $this->db->fetch_object($resql);

				if ($mode == 'email') {
					$contact_property = dol_string_nospecial(dolGetFirstLastname($obj->firstname, $obj->lastname), ' ', array(","))." <".$obj->email.">";
				} elseif ($mode == 'mobile') {
					$contact_property = $obj->phone_mobile;
				}
			}
			return $contact_property;
		} else {
			dol_print_error($this->db);
		}

		return '';
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return bank number property of thirdparty (label or rum)
	 *
	 *	@param	string	$mode	'label' or 'rum' or 'format'
	 *  @return	string			Bank label or RUM or '' if no bank account found
	 */
	public function display_rib($mode = 'label')
	{
		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';

		$bac = new CompanyBankAccount($this->db);
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$bac->fetch(0, '', $this->id);

		if ($bac->id > 0) {		// If a bank account has been found for company $this->id
			if ($mode == 'label') {
				return $bac->getRibLabel(true);
			} elseif ($mode == 'rum') {
				if (empty($bac->rum)) {
					require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
					$prelevement = new BonPrelevement($this->db);
					$bac->fetch_thirdparty();
					$bac->rum = $prelevement->buildRumNumber($bac->thirdparty->code_client, $bac->datec, $bac->id);
				}
				return $bac->rum;
			} elseif ($mode == 'format') {
				return $bac->frstrecur;
			} else {
				return 'BadParameterToFunctionDisplayRib';
			}
		} else {
			return '';
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return Array of RIB
	 *
	 * @return    CompanyBankAccount[]|int        Return 0 if KO, Array of CompanyBankAccount if OK
	 */
	public function get_all_rib()
	{
		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_rib WHERE type='ban' AND fk_soc = ".((int) $this->id);
		$result = $this->db->query($sql);
		if (!$result) {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			return 0;
		} else {
			$num_rows = $this->db->num_rows($result);
			$rib_array = array();
			if ($num_rows) {
				while ($obj = $this->db->fetch_object($result)) {
					$rib = new CompanyBankAccount($this->db);
					$rib->fetch($obj->rowid);
					$rib_array[] = $rib;
				}
			}
			return $rib_array;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Assigns a customer code from the code control module.
	 *  Return value is stored into this->code_client
	 *
	 *	@param	Societe		$objsoc		Object thirdparty
	 *	@param	int			$type		Should be 0 to say customer
	 *  @return void
	 */
	public function get_codeclient($objsoc = null, $type = 0)
	{
		// phpcs:enable
		global $conf;
		if (getDolGlobalString('SOCIETE_CODECLIENT_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			/** @var ModeleThirdPartyCode $mod */
			$mod = new $module($this->db);
			'@phan-var-force ModeleThirdPartyCode $mod';

			$this->code_client = $mod->getNextValue($objsoc, $type);
			$this->prefixCustomerIsRequired = $mod->prefixIsRequired;

			dol_syslog(get_class($this)."::get_codeclient code_client=".$this->code_client." module=".$module);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Assigns a vendor code from the code control module.
	 *  Return value is stored into this->code_fournisseur
	 *
	 *	@param	Societe		$objsoc		Object thirdparty
	 *	@param	int			$type		Should be 1 to say supplier
	 *  @return void
	 */
	public function get_codefournisseur($objsoc = null, $type = 1)
	{
		// phpcs:enable
		global $conf;
		if (getDolGlobalString('SOCIETE_CODECLIENT_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			/** @var ModeleThirdPartyCode $mod */
			$mod = new $module($this->db);
			'@phan-var-force ModeleThirdPartyCode $mod';

			$this->code_fournisseur = $mod->getNextValue($objsoc, $type);

			dol_syslog(get_class($this)."::get_codefournisseur code_fournisseur=".$this->code_fournisseur." module=".$module);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Check if a client code is editable based on the parameters of the
	 *    code control module.
	 *
	 *    @return     int		0=No, 1=Yes
	 */
	public function codeclient_modifiable()
	{
		// phpcs:enable
		global $conf;
		if (getDolGlobalString('SOCIETE_CODECLIENT_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module($this->db);
			'@phan-var-force ModeleThirdPartyCode $mod';

			dol_syslog(get_class($this)."::codeclient_modifiable code_client=".$this->code_client." module=".$module);
			if ($mod->code_modifiable_null && !$this->code_client) {
				return 1;
			}
			if ($mod->code_modifiable_invalide && $this->check_codeclient() < 0) {
				return 1;
			}
			if ($mod->code_modifiable) {
				return 1; // A mettre en dernier
			}
			return 0;
		} else {
			return 0;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Check if a vendor code is editable in the code control module configuration
	 *
	 *    @return     int		0=No, 1=Yes
	 */
	public function codefournisseur_modifiable()
	{
		// phpcs:enable
		global $conf;
		if (getDolGlobalString('SOCIETE_CODECLIENT_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module($this->db);
			'@phan-var-force ModeleThirdPartyCode $mod';

			dol_syslog(get_class($this)."::codefournisseur_modifiable code_founisseur=".$this->code_fournisseur." module=".$module);
			if ($mod->code_modifiable_null && !$this->code_fournisseur) {
				return 1;
			}
			if ($mod->code_modifiable_invalide && $this->check_codefournisseur() < 0) {
				return 1;
			}
			if ($mod->code_modifiable) {
				return 1; // A mettre en dernier
			}
			return 0;
		} else {
			return 0;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Check customer code
	 *
	 *  @return     int				0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 * 								-5 NotConfigured - Setup empty so any value may be ok or not
	 * 								-6 Other (see this->error)
	 */
	public function check_codeclient()
	{
		// phpcs:enable
		global $conf;
		if (getDolGlobalString('SOCIETE_CODECLIENT_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module($this->db);
			'@phan-var-force ModeleThirdPartyCode $mod';

			dol_syslog(get_class($this)."::check_codeclient code_client=".$this->code_client." module=".$module);
			$result = $mod->verif($this->db, $this->code_client, $this, 0);
			if ($result) {	// If error
				$this->error = $mod->error;
				$this->errors = $mod->errors;
			}
			return $result;
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Check supplier code
	 *
	 *    @return     int		0 if OK
	 * 							-1 ErrorBadCustomerCodeSyntax
	 * 							-2 ErrorCustomerCodeRequired
	 * 							-3 ErrorCustomerCodeAlreadyUsed
	 * 							-4 ErrorPrefixRequired
	 * 							-5 NotConfigured - Setup empty so any value may be ok or not
	 * 							-6 Other (see this->error)
	 */
	public function check_codefournisseur()
	{
		// phpcs:enable
		global $conf;
		if (getDolGlobalString('SOCIETE_CODECLIENT_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECLIENT_ADDON');

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module($this->db);
			'@phan-var-force ModeleThirdPartyCode $mod';

			dol_syslog(get_class($this)."::check_codefournisseur code_fournisseur=".$this->code_fournisseur." module=".$module);
			$result = $mod->verif($this->db, $this->code_fournisseur, $this, 1);
			if ($result) {	// If error
				$this->error = $mod->error;
				$this->errors = $mod->errors;
			}
			return $result;
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Assigns a accounting code from the accounting code module.
	 *      Computed value is stored into this->code_compta or this->code_compta_fournisseur according to $type.
	 *      May be identical to the one entered or generated automatically. Currently, only the automatic generation is implemented.
	 *
	 *    	@param	string	$type		Type of thirdparty ('customer' or 'supplier')
	 *		@return	int					0 if OK, <0 if $type is not valid
	 */
	public function get_codecompta($type)
	{
		// phpcs:enable
		global $conf;

		if (getDolGlobalString('SOCIETE_CODECOMPTA_ADDON')) {
			$module = getDolGlobalString('SOCIETE_CODECOMPTA_ADDON');
			$res = false;
			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			if ($res) {
				$mod = new $module();
				'@phan-var-force ModeleAccountancyCode $mod';

				// Set code count in $mod->code
				$result = $mod->get_code($this->db, $this, $type);

				if ($type == 'customer') {
					$this->code_compta_client = $mod->code;
					$this->code_compta = $this->code_compta_client; // For backward compatibility
				} elseif ($type == 'supplier') {
					$this->code_compta_fournisseur = $mod->code;
				}

				return $result;
			} else {
				$this->error = 'ErrorAccountancyCodeNotDefined';
				return -1;
			}
		} else {
			if ($type == 'customer') {
				$this->code_compta_client = '';
				$this->code_compta = '';	// For backward compatibility
			} elseif ($type == 'supplier') {
				$this->code_compta_fournisseur = '';
			}

			return 0;
		}
	}

	/**
	 *    Define parent company of current company
	 *
	 *    @param	int		$id     Id of thirdparty to set or '' to remove
	 *    @return	int     		Return integer <0 if KO, >0 if OK
	 */
	public function setParent($id)
	{
		dol_syslog(get_class($this).'::setParent', LOG_DEBUG);

		if ($this->id) {
			// Check if the id we want to add as parent has not already one parent that is the current id we try to update
			if ($id > 0) {
				$sameparent = $this->validateFamilyTree($id, $this->id, 0);
				if ($sameparent < 0) {
					return -1;
				}
				if ($sameparent == 1) {
					setEventMessages('ParentCompanyToAddIsAlreadyAChildOfModifiedCompany', null, 'warnings');
					return -1;
				}
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe SET parent = '.($id > 0 ? $id : 'null').' WHERE rowid = '.((int) $this->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				$this->parent = $id;
				return 1;
			} else {
				return -1;
			}
		}

		return -1;
	}

	/**
	 *    Check if a thirdparty $idchild is or not inside the parents (or grand parents) of another thirdparty id $idparent.
	 *
	 *    @param	int		$idparent	Id of thirdparty to check
	 *    @param	int		$idchild	Id of thirdparty to compare to
	 *    @param    int     $counter    Counter to protect against infinite loops
	 *    @return	int     			Return integer <0 if KO, 0 if OK or 1 if at some level a parent company was the child to compare to
	 */
	public function validateFamilyTree($idparent, $idchild, $counter = 0)
	{
		if ($counter > 100) {
			dol_syslog("Too high level of parent - child for company. May be an infinite loop ?", LOG_WARNING);
		}

		$sql = 'SELECT s.parent';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql .= ' WHERE rowid = '.((int) $idparent);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj	= $this->db->fetch_object($resql);

			if ($obj->parent == '') {
				return 0;
			} elseif ($obj->parent == $idchild) {
				return 1;
			} else {
				$sameparent = $this->validateFamilyTree($obj->parent, $idchild, ($counter + 1));
			}
			return $sameparent;
		} else {
			return -1;
		}
	}

	/**
	 *	Get parents for company
	 *
	 * @param   int         $company_id     ID of company to search parent
	 * @param   int[]       $parents        List of companies ID found
	 * @return	int[]
	 */
	public function getParentsForCompany($company_id, $parents = array())
	{
		global $langs;

		if ($company_id > 0) {
			$sql = "SELECT parent FROM " . MAIN_DB_PREFIX . "societe WHERE rowid = ".((int) $company_id);
			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					$parent = $obj->parent;
					if ($parent > 0 && !in_array($parent, $parents)) {
						$parents[] = $parent;
						return $this->getParentsForCompany($parent, $parents);
					} else {
						return $parents;
					}
				}
				$this->db->free($resql);
			} else {
				setEventMessage($langs->trans('GetCompanyParentsError', $this->db->lasterror()), 'errors');
			}
		}
		// Return a default value when $company_id is not greater than 0
		return array();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Returns if a profid should be verified to be unique
	 *
	 *  @param	int		$idprof		1,2,3,4,5,6 (Example: 1=siren, 2=siret, 3=naf, 4=rcs/rm, 5=eori, 6=idprof6)
	 *  @return boolean         	true if the ID must be unique
	 */
	public function id_prof_verifiable($idprof)
	{
		// phpcs:enable
		global $conf;

		switch ($idprof) {
			case 1:
				$ret = getDolGlobalBool('SOCIETE_IDPROF1_UNIQUE');
				break;
			case 2:
				$ret = getDolGlobalBool('SOCIETE_IDPROF2_UNIQUE');
				break;
			case 3:
				$ret = getDolGlobalBool('SOCIETE_IDPROF3_UNIQUE');
				break;
			case 4:
				$ret = getDolGlobalBool('SOCIETE_IDPROF4_UNIQUE');
				break;
			case 5:
				$ret = getDolGlobalBool('SOCIETE_IDPROF5_UNIQUE');
				break;
			case 6:
				$ret = getDolGlobalBool('SOCIETE_IDPROF6_UNIQUE');
				break;
			default:
				$ret = false;
		}

		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Verify if a profid exists into database for others thirds
	 *
	 *    @param	string	$idprof		'idprof1','idprof2','idprof3','idprof4','idprof5','idprof6','email' (Example: idprof1=siren, idprof2=siret, idprof3=naf, idprof4=rcs/rm)
	 *    @param	string	$value		Value of profid
	 *    @param	int		$socid		Id of thirdparty to exclude (if update)
	 *    @return   boolean				True if exists, False if not
	 */
	public function id_prof_exists($idprof, $value, $socid = 0)
	{
		// phpcs:enable
		$field = $idprof;

		switch ($idprof) {	// For backward compatibility
			case '1':
			case 'idprof1':
				$field = "siren";
				break;
			case '2':
			case 'idprof2':
				$field = "siret";
				break;
			case '3':
			case 'idprof3':
				$field = "ape";
				break;
			case '4':
			case 'idprof4':
				$field = "idprof4";
				break;
			case '5':
				$field = "idprof5";
				break;
			case '6':
				$field = "idprof6";
				break;
		}

		//Verify duplicate entries
		$sql = "SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."societe WHERE ".$field." = '".$this->db->escape($value)."' AND entity IN (".getEntity('societe').")";
		if ($socid) {
			$sql .= " AND rowid <> ".$socid;
		}
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$count = $obj->nb;
		} else {
			$count = 0;
			print $this->db->error();
		}
		$this->db->free($resql);

		if ($count > 0) {
			return true;
		} else {
			return false;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Check the validity of a professional identifier according to the country of the company (siren, siret, ...)
	 *
	 *  @param	int			$idprof         1,2,3,4 (Example: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *  @param  Societe		$soc            Object societe
	 *  @return int             			Return integer <=0 if KO, >0 if OK
	 *  TODO better to have this in a lib than into a business class
	 */
	public function id_prof_check($idprof, $soc)
	{
		// phpcs:enable
		global $conf;

		// load the library necessary to check the professional identifiers
		require_once DOL_DOCUMENT_ROOT.'/core/lib/profid.lib.php';

		$ok = 1;

		if (getDolGlobalString('MAIN_DISABLEPROFIDRULES')) {
			return 1;
		}

		// Check SIREN
		if ($idprof == 1 && $soc->country_code == 'FR' && !isValidSiren($this->idprof1)) {
			return -1;
		}

		// Check SIRET
		if ($idprof == 2 && $soc->country_code == 'FR' && !isValidSiret($this->idprof2)) {
			return -1;
		}

		//Verify CIF/NIF/NIE if pays ES
		if ($idprof == 1 && $soc->country_code == 'ES') {
			return isValidTinForES($this->idprof1);
		}

		//Verify NIF if country is PT
		if ($idprof == 1 && $soc->country_code == 'PT' && !isValidTinForPT($this->idprof1)) {
			return -1;
		}

		//Verify NIF if country is DZ
		if ($idprof == 1 && $soc->country_code == 'DZ' && !isValidTinForDZ($this->idprof1)) {
			return -1;
		}

		//Verify ID Prof 1 if country is BE
		if ($idprof == 1 && $soc->country_code == 'BE' && !isValidTinForBE($this->idprof1)) {
			return -1;
		}

		return $ok;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Return an url to check online a professional id or empty string
	 *
	 *   @param		int		$idprof         1,2,3,4 (Example: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *   @param 	Societe	$thirdparty     Object thirdparty
	 *   @return	string          		Url or empty string if no URL known
	 *   TODO better in a lib than into business class
	 */
	public function id_prof_url($idprof, $thirdparty)
	{
		// phpcs:enable
		global $conf, $langs, $hookmanager;

		$url = '';
		$action = '';

		$hookmanager->initHooks(array('idprofurl'));
		$parameters = array('idprof' => $idprof, 'company' => $thirdparty);
		$reshook = $hookmanager->executeHooks('getIdProfUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			if (getDolGlobalString('MAIN_DISABLEPROFIDRULES')) {
				return '';
			}

			// TODO Move links to validate professional ID into a dictionary table "country" + "link"
			$strippedIdProf1 = str_replace(' ', '', $thirdparty->idprof1);
			if ($idprof == 1 && $thirdparty->country_code == 'FR') {
				$url = 'https://annuaire-entreprises.data.gouv.fr/entreprise/'.$strippedIdProf1; // See also http://avis-situation-sirene.insee.fr/
			}
			if ($idprof == 1 && ($thirdparty->country_code == 'GB' || $thirdparty->country_code == 'UK')) {
				$url = 'https://beta.companieshouse.gov.uk/company/'.$strippedIdProf1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'ES') {
				$url = 'http://www.e-informa.es/servlet/app/portal/ENTP/screen/SProducto/prod/ETIQUETA_EMPRESA/nif/'.$strippedIdProf1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'IN') {
				$url = 'http://www.tinxsys.com/TinxsysInternetWeb/dealerControllerServlet?tinNumber='.$strippedIdProf1.';&searchBy=TIN&backPage=searchByTin_Inter.jsp';
			}
			if ($idprof == 1 && $thirdparty->country_code == 'DZ') {
				$url = 'http://nif.mfdgi.gov.dz/nif.asp?Nif='.$strippedIdProf1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'PT') {
				$url = 'http://www.nif.pt/'.$strippedIdProf1;
			}

			if ($url) {
				return '<a target="_blank" rel="noopener noreferrer" href="'.$url.'">'.$langs->trans("Check").'</a>';
			}
		} else {
			return $hookmanager->resPrint;
		}

		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Indicates if the company has projects
	 *
	 *   @return     bool	   true if the company has projects, false otherwise
	 */
	public function has_projects()
	{
		// phpcs:enable
		$sql = "SELECT COUNT(*) as numproj FROM ".MAIN_DB_PREFIX."projet WHERE fk_soc = ".((int) $this->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$count = $obj->numproj;
		} else {
			$count = 0;
			print $this->db->error();
		}
		$this->db->free($resql);
		return ($count > 0);
	}


	/**
	 *  Load information for tab info
	 *
	 *  @param  int		$id     Id of thirdparty to load
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT s.rowid, s.nom as name, s.datec, tms as datem,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE s.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->user_creation_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);

				$this->ref = $obj->name;
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Check if third party is a company (Business) or an end user (Consumer)
	 *
	 *  @return		boolean		if a company: true || if a user: false
	 */
	public function isACompany()
	{
		// Define if third party is treated as company (or not) when nature is unknown
		$isACompany = getDolGlobalInt('MAIN_UNKNOWN_CUSTOMERS_ARE_COMPANIES');

		// Now try to guess using different tips
		if (!empty($this->tva_intra)) {
			$isACompany = 1;
		} elseif (!empty($this->idprof1) || !empty($this->idprof2) || !empty($this->idprof3) || !empty($this->idprof4) || !empty($this->idprof5) || !empty($this->idprof6)) {
			$isACompany = 1;
		} else {
			if (!getDolGlobalString('MAIN_CUSTOMERS_ARE_COMPANIES_EVEN_IF_SET_AS_INDIVIDUAL')) {
				// TODO Add a field is_a_company into dictionary
				if (preg_match('/^TE_PRIVATE/', $this->typent_code)) {
					$isACompany = 0;
				} else {
					$isACompany = 1;
				}
			} else {
				$isACompany = 1;
			}
		}

		return (bool) $isACompany;
	}

	/**
	 *  Return if a company is inside the EEC (European Economic Community)
	 *
	 *  @return     boolean		true = country inside EEC, false = country outside EEC
	 */
	public function isInEEC()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
		return isInEEC($this);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load the list of provider categories
	 *
	 *  @return    int      0 if success, <> 0 if error
	 */
	public function LoadSupplierCateg()
	{
		// phpcs:enable
		$this->SupplierCategories = array();
		$sql = "SELECT rowid, label";
		$sql .= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql .= " WHERE type = ".Categorie::TYPE_SUPPLIER;

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->SupplierCategories[$obj->rowid] = $obj->label;
			}
			return 0;
		} else {
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Insert link supplier - category
	 *
	 *	@param	int		$categorie_id		Id of category
	 *  @return int      					0 if success, <> 0 if error
	 */
	public function AddFournisseurInCategory($categorie_id)
	{
		// phpcs:enable
		if ($categorie_id > 0 && $this->id > 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_fournisseur (fk_categorie, fk_soc) ";
			$sql .= " VALUES (".((int) $categorie_id).", ".((int) $this->id).")";

			if ($resql = $this->db->query($sql)) {
				return 0;
			}
		} else {
			return 0;
		}
		return -1;
	}

	/**
	 *  Return number of mass Emailing received by this contacts with its email
	 *
	 *  @return       int     Number of EMailings
	 */
	public function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."mailing as m";
		$sql .= " WHERE mc.fk_mailing=m.rowid AND mc.email = '".$this->db->escape($this->email)."' ";
		$sql .= " AND m.entity IN (".getEntity($this->element).") AND mc.statut NOT IN (-1,0)"; // -1 error, 0 not sent, 1 sent with success

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Set "blacklist" mailing status
	 *
	 *  @param	int		$no_email	1=Do not send mailing, 0=Ok to receive mailing
	 *  @return int					Return integer <0 if KO, >0 if OK
	 */
	public function setNoEmail($no_email)
	{
		$error = 0;

		// Update mass emailing flag into table mailing_unsubscribe
		if ($this->email) {
			$this->db->begin();

			if ($no_email) {
				$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE entity IN (".getEntity('mailing', 0).") AND email = '".$this->db->escape($this->email)."'";
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$noemail = $obj->nb;
					if (empty($noemail)) {
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."mailing_unsubscribe(email, entity, date_creat) VALUES ('".$this->db->escape($this->email)."', ".getEntity('mailing', 0).", '".$this->db->idate(dol_now())."')";
						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							$this->error = $this->db->lasterror();
							$this->errors[] = $this->error;
						}
					}
				} else {
					$error++;
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
				}
			} else {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE email = '".$this->db->escape($this->email)."' AND entity IN (".getEntity('mailing', 0).")";
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
				}
			}

			if (empty($error)) {
				$this->no_email = $no_email;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return $error * -1;
			}
		}

		return 0;
	}

	/**
	 *  get "blacklist" mailing status
	 * 	set no_email attribute to 1 or 0
	 *
	 *  @return int					Return integer <0 if KO, >0 if OK
	 */
	public function getNoEmail()
	{
		if ($this->email) {
			$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."mailing_unsubscribe WHERE entity IN (".getEntity('mailing').") AND email = '".$this->db->escape($this->email)."'";
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				$this->no_email = $obj->nb;
				return 1;
			} else {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				return -1;
			}
		}
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Create a third party into database from a member object
	 *
	 *  @param	Adherent	$member			Object member
	 * 	@param	string		$socname		Name of third party to force
	 *	@param	string		$socalias		Alias name of third party to force
	 *  @param	string		$customercode	Customer code
	 *  @return int							Return integer <0 if KO, id of created account if OK
	 */
	public function create_from_member(Adherent $member, $socname = '', $socalias = '', $customercode = '')
	{
		// phpcs:enable
		global $conf, $user, $langs;

		dol_syslog(get_class($this)."::create_from_member", LOG_DEBUG);
		$fullname = $member->getFullName($langs);

		if ($member->morphy == 'mor') {
			if (empty($socname)) {
				$socname = $member->company ? $member->company : $member->societe;
			}
			if (!empty($fullname) && empty($socalias)) {
				$socalias = $fullname;
			}
		} elseif (empty($socname) && $member->morphy == 'phy') {
			if (empty($socname)) {
				$socname = $fullname;
			}
			if (!empty($member->company) && empty($socalias)) {
				$socalias = $member->company;
			}
		}

		$name = $socname;
		$alias = $socalias ? $socalias : '';

		// Positionne parameters
		$this->nom = $name; // TODO deprecated
		$this->name = $name;
		$this->name_alias = $alias;
		$this->address = $member->address;
		$this->zip = $member->zip;
		$this->town = $member->town;
		$this->country_code = $member->country_code;
		$this->country_id = $member->country_id;
		$this->phone = $member->phone; // Prof phone
		$this->email = $member->email;
		$this->socialnetworks = $member->socialnetworks;
		$this->entity = $member->entity;

		$this->client = 1; // A member is a customer by default
		$this->code_client = ($customercode ? $customercode : -1);
		$this->code_fournisseur = '-1';
		$this->typent_code = ($member->morphy == 'phy' ? 'TE_PRIVATE' : 0);
		$this->typent_id = $this->typent_code ? dol_getIdFromCode($this->db, $this->typent_code, 'c_typent', 'id', 'code') : 0;

		$this->db->begin();

		// Cree et positionne $this->id
		$result = $this->create($user);

		if ($result >= 0) {
			// Auto-create contact on thirdparty creation
			if (getDolGlobalString('THIRDPARTY_DEFAULT_CREATE_CONTACT')) {
				// Fill fields needed by contact
				$this->name_bis = $member->lastname;
				$this->firstname = $member->firstname;
				$this->civility_id = $member->civility_id;

				dol_syslog("We ask to create a contact/address too", LOG_DEBUG);
				$result = $this->create_individual($user);

				if ($result < 0) {
					setEventMessages($this->error, $this->errors, 'errors');
					$this->db->rollback();
					return -1;
				}
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
			$sql .= " SET fk_soc = ".((int) $this->id);
			$sql .= " WHERE rowid = ".((int) $member->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->error = $this->db->error();

				$this->db->rollback();
				return -1;
			}
		} else {
			// $this->error deja positionne
			dol_syslog(get_class($this)."::create_from_member - 2 - ".$this->error." - ".implode(',', $this->errors), LOG_ERR);

			$this->db->rollback();
			return $result;
		}
	}

	/**
	 * 	Set properties with value into $conf
	 *
	 * 	@param	Conf	$conf		Conf object (possibility to use another entity)
	 * 	@return	void
	 */
	public function setMysoc(Conf $conf)
	{
		global $langs;

		$this->id = 0;
		$this->entity = $conf->entity;
		$this->name = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
		$this->nom = $this->name; // deprecated
		$this->address = getDolGlobalString('MAIN_INFO_SOCIETE_ADDRESS');
		$this->zip = getDolGlobalString('MAIN_INFO_SOCIETE_ZIP');
		$this->town = getDolGlobalString('MAIN_INFO_SOCIETE_TOWN');
		$this->region_code = getDolGlobalString('MAIN_INFO_SOCIETE_REGION');

		$this->socialobject = getDolGlobalString('MAIN_INFO_SOCIETE_OBJECT');

		$this->note_private = getDolGlobalString('MAIN_INFO_SOCIETE_NOTE');

		// We define country_id, country_code and country
		$country_id = 0;
		$country_code = $country_label = '';
		if (getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY')) {
			$tmp = explode(':', getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY'));
			$country_id =  (is_numeric($tmp[0])) ? (int) $tmp[0] : 0;
			if (!empty($tmp[1])) {   // If $conf->global->MAIN_INFO_SOCIETE_COUNTRY is "id:code:label"
				$country_code = $tmp[1];
				$country_label = $tmp[2];
			} else {
				// For backward compatibility
				dol_syslog("Your country setup use an old syntax. Reedit it using setup area.", LOG_WARNING);
				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
				$country_code = getCountry($country_id, 2, $this->db); // This need a SQL request, but it's the old feature that should not be used anymore
				$country_label = getCountry($country_id, 0, $this->db); // This need a SQL request, but it's the old feature that should not be used anymore
			}
		}
		$this->country_id = $country_id;
		$this->country_code = $country_code;
		$this->country = $country_label;
		if (is_object($langs)) {
			$this->country = ($langs->trans('Country'.$country_code) != 'Country'.$country_code) ? $langs->trans('Country'.$country_code) : $country_label;
		}

		//TODO This could be replicated for region but function `getRegion` didn't exist, so I didn't added it.
		// We define state_id, state_code and state
		$state_id = 0;
		$state_code = '';
		$state_label = '';
		if (getDolGlobalString('MAIN_INFO_SOCIETE_STATE')) {
			$tmp = explode(':', getDolGlobalString('MAIN_INFO_SOCIETE_STATE'));
			$state_id = (int) $tmp[0];
			if (!empty($tmp[1])) {   // If $conf->global->MAIN_INFO_SOCIETE_STATE is "id:code:label"
				$state_code = $tmp[1];
				$state_label = $tmp[2];
			} else { // For backward compatibility
				dol_syslog("Your setup of State has an old syntax (entity=".$conf->entity."). Go in Home - Setup - Organization then Save should remove this error.", LOG_ERR);
				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
				$state_code = getState($state_id, '2', $this->db); // This need a SQL request, but it's the old feature that should not be used anymore
				$state_label = getState($state_id, '0', $this->db); // This need a SQL request, but it's the old feature that should not be used anymore
			}
		}
		$this->state_id = $state_id;
		$this->state_code = $state_code;
		$this->state = $state_label;
		if (is_object($langs)) {
			$this->state = ($langs->trans('State'.$state_code) != 'State'.$state_code) ? $langs->trans('State'.$state_code) : $state_label;
		}

		$this->phone = getDolGlobalString('MAIN_INFO_SOCIETE_TEL');
		$this->phone_mobile = getDolGlobalString('MAIN_INFO_SOCIETE_MOBILE');
		$this->fax = getDolGlobalString('MAIN_INFO_SOCIETE_FAX');
		$this->url = getDolGlobalString('MAIN_INFO_SOCIETE_WEB');

		// Social networks
		$facebook_url = getDolGlobalString('MAIN_INFO_SOCIETE_FACEBOOK_URL');
		$twitter_url = getDolGlobalString('MAIN_INFO_SOCIETE_TWITTER_URL');
		$linkedin_url = getDolGlobalString('MAIN_INFO_SOCIETE_LINKEDIN_URL');
		$instagram_url = getDolGlobalString('MAIN_INFO_SOCIETE_INSTAGRAM_URL');
		$youtube_url = getDolGlobalString('MAIN_INFO_SOCIETE_YOUTUBE_URL');
		$github_url = getDolGlobalString('MAIN_INFO_SOCIETE_GITHUB_URL');
		$this->socialnetworks = array();
		if (!empty($facebook_url)) {
			$this->socialnetworks['facebook'] = $facebook_url;
		}
		if (!empty($twitter_url)) {
			$this->socialnetworks['twitter'] = $twitter_url;
		}
		if (!empty($linkedin_url)) {
			$this->socialnetworks['linkedin'] = $linkedin_url;
		}
		if (!empty($instagram_url)) {
			$this->socialnetworks['instagram'] = $instagram_url;
		}
		if (!empty($youtube_url)) {
			$this->socialnetworks['youtube'] = $youtube_url;
		}
		if (!empty($github_url)) {
			$this->socialnetworks['github'] = $github_url;
		}

		// Id prof generiques
		$this->idprof1 = getDolGlobalString('MAIN_INFO_SIREN');
		$this->idprof2 = getDolGlobalString('MAIN_INFO_SIRET');
		$this->idprof3 = getDolGlobalString('MAIN_INFO_APE');
		$this->idprof4 = getDolGlobalString('MAIN_INFO_RCS');
		$this->idprof5 = getDolGlobalString('MAIN_INFO_PROFID5');
		$this->idprof6 = getDolGlobalString('MAIN_INFO_PROFID6');
		$this->tva_intra = getDolGlobalString('MAIN_INFO_TVAINTRA'); // VAT number, not necessarily INTRA.
		$this->managers = getDolGlobalString('MAIN_INFO_SOCIETE_MANAGERS');
		$this->capital = is_numeric(getDolGlobalString('MAIN_INFO_CAPITAL')) ? (float) price2num(getDolGlobalString('MAIN_INFO_CAPITAL')) : 0;
		$this->forme_juridique_code = getDolGlobalInt('MAIN_INFO_SOCIETE_FORME_JURIDIQUE');
		$this->email = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL');
		$this->default_lang = getDolGlobalString('MAIN_LANG_DEFAULT', 'auto');
		$this->logo = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO');
		$this->logo_small = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SMALL');
		$this->logo_mini = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_MINI');
		$this->logo_squarred = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED');
		$this->logo_squarred_small = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL');
		$this->logo_squarred_mini = getDolGlobalString('MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI');

		// Define if company use vat or not
		$this->tva_assuj = getDolGlobalInt('FACTURE_TVAOPTION');

		// Define if company use local taxes
		$this->localtax1_assuj = ((isset($conf->global->FACTURE_LOCAL_TAX1_OPTION) && (getDolGlobalString('FACTURE_LOCAL_TAX1_OPTION') == '1' || getDolGlobalString('FACTURE_LOCAL_TAX1_OPTION') == 'localtax1on')) ? 1 : 0);
		$this->localtax2_assuj = ((isset($conf->global->FACTURE_LOCAL_TAX2_OPTION) && (getDolGlobalString('FACTURE_LOCAL_TAX2_OPTION') == '1' || getDolGlobalString('FACTURE_LOCAL_TAX2_OPTION') == 'localtax2on')) ? 1 : 0);
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	int >0 if ok
	 */
	public function initAsSpecimen()
	{
		$now = dol_now();

		// Initialize parameters
		$this->id = 0;
		$this->entity = 1;
		$this->name = 'THIRDPARTY SPECIMEN '.dol_print_date($now, 'dayhourlog');
		$this->nom = $this->name; // For backward compatibility
		$this->ref_ext = 'Ref ext';
		$this->specimen = 1;
		$this->address = '21 jump street';
		$this->zip = '99999';
		$this->town = 'MyTown';
		$this->state_id = 1;
		$this->state_code = 'AA';
		$this->state = 'MyState';
		$this->country_id = 1;
		$this->country_code = 'FR';
		$this->email = 'specimen@specimen.com';
		$this->socialnetworks = array(
			'skype' => 'skypepseudo',
			'twitter' => 'twitterpseudo',
			'facebook' => 'facebookpseudo',
			'linkedin' => 'linkedinpseudo',
		);
		$this->url = 'http://www.specimen.com';

		$this->phone = '0909090901';
		$this->phone_mobile = '0909090901';
		$this->fax = '0909090909';

		$this->code_client = 'CC-'.dol_print_date($now, 'dayhourlog');
		$this->code_fournisseur = 'SC-'.dol_print_date($now, 'dayhourlog');
		$this->typent_code = 'TE_OTHER';
		$this->capital = 10000;
		$this->client = 1;
		$this->prospect = 1;
		$this->fournisseur = 1;
		$this->tva_assuj = 1;
		$this->tva_intra = 'EU1234567';
		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';

		$this->idprof1 = 'idprof1';
		$this->idprof2 = 'idprof2';
		$this->idprof3 = 'idprof3';
		$this->idprof4 = 'idprof4';
		$this->idprof5 = 'idprof5';
		$this->idprof6 = 'idprof6';

		return 1;
	}

	/**
	 *  Check if we must use localtax feature or not according to country (country of $mysoc in most cases).
	 *
	 *	@param		int		$localTaxNum	To get info for only localtax1 or localtax2
	 *  @return		boolean					true or false
	 */
	public function useLocalTax($localTaxNum = 0)
	{
		$sql  = "SELECT t.localtax1, t.localtax2";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$this->db->escape($this->country_code)."'";
		$sql .= " AND t.active = 1";
		$sql .= " AND t.entity IN (".getEntity('c_tva').")";
		if (empty($localTaxNum)) {
			$sql .= " AND (t.localtax1_type <> '0' OR t.localtax2_type <> '0')";
		} elseif ($localTaxNum == 1) {
			$sql .= " AND t.localtax1_type <> '0'";
		} elseif ($localTaxNum == 2) {
			$sql .= " AND t.localtax2_type <> '0'";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			return ($this->db->num_rows($resql) > 0);
		} else {
			return false;
		}
	}

	/**
	 *  Check if we must use NPR Vat (french stupid rule) or not according to country (country of $mysoc in most cases).
	 *
	 *  @return		boolean					true or false
	 */
	public function useNPR()
	{
		$sql  = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$this->db->escape($this->country_code)."'";
		$sql .= " AND t.active = 1 AND t.recuperableonly = 1";
		$sql .= " AND t.entity IN (".getEntity('c_tva').")";

		dol_syslog("useNPR", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			return ($this->db->num_rows($resql) > 0);
		} else {
			return false;
		}
	}

	/**
	 *  Check if we must use revenue stamps feature or not according to country (country of $mysocin most cases).
	 *  Table c_revenuestamp contains the country and value of stamp per invoice.
	 *
	 *  @return		boolean			true or false
	 */
	public function useRevenueStamp()
	{
		$sql  = "SELECT COUNT(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_revenuestamp as r, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE r.fk_pays = c.rowid AND c.code = '".$this->db->escape($this->country_code)."'";
		$sql .= " AND r.active = 1";

		dol_syslog("useRevenueStamp", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			return ($obj->nb > 0);
		} else {
			$this->error = $this->db->lasterror();
			return false;
		}
	}

	/**
	 *	Return prostect level
	 *
	 *  @return     string        Label of prospect status
	 */
	public function getLibProspLevel()
	{
		return $this->LibProspLevel($this->fk_prospectlevel);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of prospect level
	 *
	 *  @param	string	$fk_prospectlevel   	Prospect level
	 *  @return string        					label of level
	 */
	public function LibProspLevel($fk_prospectlevel)
	{
		// phpcs:enable
		global $langs;

		$label = '';
		if ($fk_prospectlevel != '') {
			$label = $langs->trans("ProspectLevel".$fk_prospectlevel);
			// If label is not found in language file, we get label from cache/database
			if ($label == "ProspectLevel".$fk_prospectlevel) {
				$label = $langs->getLabelFromKey($this->db, $fk_prospectlevel, 'c_prospectlevel', 'code', 'label');
			}
		}

		return $label;
	}

	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=label long, 1=label short, 2=Picto + Label short, 3=Picto, 4=Picto + Label long
	 *  @param	string	$label		Label to use for status for added status
	 *  @return string        		Label
	 */
	public function getLibProspCommStatut($mode = 0, $label = '')
	{
		return $this->LibProspCommStatut($this->stcomm_id, $mode, $label, $this->stcomm_picto);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of a given status
	 *
	 *  @param	int|string	$status        	Id or code for prospection status
	 *  @param  int			$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @param	string		$label			Label to use for status for added status
	 *	@param 	string		$picto      	Name of image file to show ('filenew', ...)
	 *                                      If no extension provided, we use '.png'. Image must be stored into theme/xxx/img directory.
	 *                                      Example: picto.png                  if picto.png is stored into htdocs/theme/mytheme/img
	 *                                      Example: picto.png@mymodule         if picto.png is stored into htdocs/mymodule/img
	 *                                      Example: /mydir/mysubdir/picto.png  if picto.png is stored into htdocs/mydir/mysubdir (pictoisfullpath must be set to 1)
	 *  @return string       	 			Label of prospection status
	 */
	public function LibProspCommStatut($status, $mode = 0, $label = '', $picto = '')
	{
		// phpcs:enable
		global $langs;

		$langs->load('customers');

		if ($mode == 2) {
			if ($status == '-1' || $status == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), '-1', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect-1");
			} elseif ($status == '0' || $status == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), '0', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect0");
			} elseif ($status == '1' || $status == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), '1', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect1");
			} elseif ($status == '2' || $status == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), '2', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect2");
			} elseif ($status == '3' || $status == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), '3', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect3");
			} else {
				return img_action(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label, '0', $picto, 'class="inline-block valignmiddle"').' '.(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label);
			}
		} elseif ($mode == 3) {
			if ($status == '-1' || $status == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), '-1', $picto, 'class="inline-block valignmiddle"');
			} elseif ($status == '0' || $status == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), '0', $picto, 'class="inline-block valignmiddle"');
			} elseif ($status == '1' || $status == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), '1', $picto, 'class="inline-block valignmiddle"');
			} elseif ($status == '2' || $status == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), '2', $picto, 'class="inline-block valignmiddle"');
			} elseif ($status == '3' || $status == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), '3', $picto, 'class="inline-block valignmiddle"');
			} else {
				return img_action(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label, '0', $picto, 'class="inline-block valignmiddle"');
			}
		} elseif ($mode == 4) {
			if ($status == '-1' || $status == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), '-1', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect-1");
			} elseif ($status == '0' || $status == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), '0', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect0");
			} elseif ($status == '1' || $status == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), '1', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect1");
			} elseif ($status == '2' || $status == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), '2', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect2");
			} elseif ($status == '3' || $status == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), '3', $picto, 'class="inline-block valignmiddle"').' '.$langs->trans("StatusProspect3");
			} else {
				return img_action(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label, '0', $picto, 'class="inline-block valignmiddle"').' '.(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label);
			}
		}

		return "Error, mode/status not found";
	}

	/**
	 *  Return amount of proposal not yet paid and total an dlist of all proposals
	 *
	 *  @param     'customer'|'supplier'	$mode	'customer' or 'supplier'
	 *  @return    array{opened:float,total_ht:float,total_ttc:float}|array{}	array('opened'=>Amount including tax that remains to pay, 'total_ht'=>Total amount without tax of all objects paid or not, 'total_ttc'=>Total amount including tax of all object paid or not)
	 */
	public function getOutstandingProposals($mode = 'customer')
	{
		$table = 'propal';
		if ($mode == 'supplier') {
			$table = 'supplier_proposal';
		}

		$sql  = "SELECT rowid, ref, total_ht, total_ttc, fk_statut as status FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ".((int) $this->id);
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('supplier_proposal').")";
		} else {
			$sql .= " AND entity IN (".getEntity('propal').")";
		}

		dol_syslog("getOutstandingProposals for fk_soc = ".((int) $this->id), LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			$arrayofref = array();
			while ($obj = $this->db->fetch_object($resql)) {
				$arrayofref[$obj->rowid] = $obj->ref;
				$outstandingTotal += $obj->total_ht;
				$outstandingTotalIncTax += $obj->total_ttc;
				if ($obj->status != 0) {
					// Not a draft
					$outstandingOpened += $obj->total_ttc;
				}
			}
			return array('opened' => $outstandingOpened, 'total_ht' => $outstandingTotal, 'total_ttc' => $outstandingTotalIncTax, 'refs' => $arrayofref); // 'opened' is 'incl taxes'
		} else {
			return array();
		}
	}

	/**
	 *  Return amount of order not yet paid and total and list of all orders
	 *
	 *  @param     'customer'|'supplier'	$mode	'customer' or 'supplier'
	 *  @return    array{opened:float,total_ht:float,total_ttc:float}|array{}	array('opened'=>Amount including tax that remains to pay, 'total_ht'=>Total amount without tax of all objects paid or not, 'total_ttc'=>Total amount including tax of all object paid or not)
	 */
	public function getOutstandingOrders($mode = 'customer')
	{
		$table = 'commande';
		if ($mode == 'supplier') {
			$table = 'commande_fournisseur';
		}

		$sql  = "SELECT rowid, ref, total_ht, total_ttc, fk_statut as status FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ".((int) $this->id);
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('supplier_order').")";
		} else {
			$sql .= " AND entity IN (".getEntity('commande').")";
		}

		dol_syslog("getOutstandingOrders", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			$arrayofref = array();
			while ($obj = $this->db->fetch_object($resql)) {
				$arrayofref[$obj->rowid] = $obj->ref;
				$outstandingTotal += $obj->total_ht;
				$outstandingTotalIncTax += $obj->total_ttc;
				if ($obj->status != 0) {
					// Not a draft
					$outstandingOpened += $obj->total_ttc;
				}
			}
			return array('opened' => $outstandingOpened, 'total_ht' => $outstandingTotal, 'total_ttc' => $outstandingTotalIncTax, 'refs' => $arrayofref); // 'opened' is 'incl taxes'
		} else {
			return array();
		}
	}

	/**
	 *  Return amount of bill not yet paid and total of all invoices
	 *
	 *  @param     'customer'|'supplier'	$mode	'customer' or 'supplier'
	 *  @param     int<0,1>					$late    	0 => all invoice, 1=> only late
	 *  @return    array{opened:float,total_ht:float,total_ttc:float}|array{}	array('opened'=>Amount including tax that remains to pay, 'total_ht'=>Total amount without tax of all objects paid or not, 'total_ttc'=>Total amount including tax of all object paid or not)
	 */
	public function getOutstandingBills($mode = 'customer', $late = 0)
	{
		$table = 'facture';
		if ($mode == 'supplier') {
			$table = 'facture_fourn';
		}

		/* Accurate value of remain to pay is to sum remaintopay for each invoice
		 $paiement = $invoice->getSommePaiement();
		 $creditnotes=$invoice->getSumCreditNotesUsed();
		 $deposits=$invoice->getSumDepositsUsed();
		 $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
		 $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
		 */
		$sql = "SELECT rowid, ref, total_ht, total_ttc, paye, type, fk_statut as status, close_code FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ".((int) $this->id);
		if (!empty($late)) {
			$sql .= " AND date_lim_reglement < '".$this->db->idate(dol_now())."'";
		}
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('facture_fourn').")";
		} else {
			$sql .= " AND entity IN (".getEntity('invoice').")";
		}

		dol_syslog("getOutstandingBills", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			$arrayofref = array();
			$arrayofrefopened = array();
			if ($mode == 'supplier') {
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$tmpobject = new FactureFournisseur($this->db);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$tmpobject = new Facture($this->db);
			}
			while ($obj = $this->db->fetch_object($resql)) {
				$arrayofref[$obj->rowid] = $obj->ref;
				$tmpobject->id = $obj->rowid;

				if ($obj->status != $tmpobject::STATUS_DRAFT                                           // Not a draft
					&& !($obj->status == $tmpobject::STATUS_ABANDONED && $obj->close_code == 'replaced')  // Not a replaced invoice
				) {
					$outstandingTotal += $obj->total_ht;
					$outstandingTotalIncTax += $obj->total_ttc;
				}

				$remaintopay = 0;

				if ($obj->paye == 0
					&& $obj->status != $tmpobject::STATUS_DRAFT    		// Not a draft
					&& $obj->status != $tmpobject::STATUS_ABANDONED	    // Not abandoned
					&& $obj->status != $tmpobject::STATUS_CLOSED) {		// Not classified as paid
					//$sql .= " AND (status <> 3 OR close_code <> 'abandon')";		// Not abandoned for undefined reason
					$paiement = $tmpobject->getSommePaiement();
					$creditnotes = $tmpobject->getSumCreditNotesUsed();
					$deposits = $tmpobject->getSumDepositsUsed();

					$remaintopay = ($obj->total_ttc - $paiement - $creditnotes - $deposits);
					$outstandingOpened += $remaintopay;
				}

				//if credit note is converted but not used
				// TODO Do this also for customer ?
				if ($mode == 'supplier' && $obj->type == FactureFournisseur::TYPE_CREDIT_NOTE && $tmpobject->isCreditNoteUsed()) {
					$remainingcreditnote = $tmpobject->getSumFromThisCreditNotesNotUsed();
					$remaintopay -= $remainingcreditnote;
					$outstandingOpened -= $remainingcreditnote;
				}

				if ($remaintopay) {
					$arrayofrefopened[$obj->rowid] = $obj->ref;
				}
			}
			return array('opened' => $outstandingOpened, 'total_ht' => $outstandingTotal, 'total_ttc' => $outstandingTotalIncTax, 'refs' => $arrayofref, 'refsopened' => $arrayofrefopened); // 'opened' is 'incl taxes'
		} else {
			dol_syslog("Sql error ".$this->db->lasterror, LOG_ERR);
			return array();
		}
	}

	/**
	 * Return label of status customer is prospect/customer
	 *
	 * @return   string        	Label
	 * @see getTypeUrl()
	 */
	public function getLibCustProspStatut()
	{
		return $this->LibCustProspStatut($this->client);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of the customer/prospect status
	 *
	 *  @param	int		$status         Id of prospection status
	 *  @return	string          		Label of prospection status
	 */
	public function LibCustProspStatut($status)
	{
		// phpcs:enable
		global $langs;
		$langs->load('companies');

		if ($status == 0) {
			return $langs->trans("NorProspectNorCustomer");
		} elseif ($status == 1) {
			return $langs->trans("Customer");
		} elseif ($status == 2) {
			return $langs->trans("Prospect");
		} elseif ($status == 3) {
			return $langs->trans("ProspectCustomer");
		}

		return '';
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string					$modele			Generator to use. Caller must set it to obj->model_pdf.
	 *	@param	Translate				$outputlangs	object lang a utiliser pour traduction
	 *  @param  int<0,1>				$hidedetails    Hide details of lines
	 *  @param  int<0,1>				$hidedesc       Hide description
	 *  @param  int<0,1>				$hideref        Hide ref
	 *  @param  ?array<string,mixed>	$moreparams	Array to provide more information
	 *	@return int        							Return integer <0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $langs;

		if (!empty($moreparams) && !empty($moreparams['use_companybankid'])) {
			$modelpath = "core/modules/bank/doc/";

			include_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			$companybankaccount = new CompanyBankAccount($this->db);
			$result = $companybankaccount->fetch($moreparams['use_companybankid']);
			if (!$result) {
				dol_print_error($this->db, $companybankaccount->error, $companybankaccount->errors);
			}
			$result = $companybankaccount->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
			$this->last_main_doc = $companybankaccount->last_main_doc;
		} else {
			// Positionne le modele sur le nom du modele a utiliser
			if (!dol_strlen($modele)) {
				if (getDolGlobalString('COMPANY_ADDON_PDF')) {
					$modele = getDolGlobalString('COMPANY_ADDON_PDF');
				} else {
					print $langs->trans("Error")." ".$langs->trans("Error_COMPANY_ADDON_PDF_NotDefined");
					return 0;
				}
			}

			if (!isset($this->bank_account)) {
				require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
				$bac = new CompanyBankAccount($this->db);
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				$result = $bac->fetch(0, '', $this->id);
				if ($result > 0) {
					$this->bank_account = $bac;
				} else {
					$this->bank_account = '';
				}
			}

			$modelpath = "core/modules/societe/doc/";

			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}


	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param 	int[]|int 	$categories 	Category ID or array of Categories IDs
	 * @param 	string 		$type_categ 	Category type ('customer' or 'supplier')
	 * @return	int							Return integer <0 if KO, >0 if OK
	 */
	public function setCategories($categories, $type_categ)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		// Decode type
		if (!in_array($type_categ, array(Categorie::TYPE_CUSTOMER, Categorie::TYPE_SUPPLIER))) {
			dol_syslog(__METHOD__.': Type '.$type_categ.'is an unknown company category type. Done nothing.', LOG_ERR);
			return -1;
		}

		return parent::setCategoriesCommon($categories, $type_categ);
	}

	/**
	 * Sets sales representatives of the thirdparty
	 *
	 * @param 	int[]|int 	$salesrep	 	User ID or array of user IDs
	 * @param   bool        $onlyAdd        Only add (no delete before)
	 * @return	int<-1,1>					Return integer <0 if KO, >0 if OK
	 */
	public function setSalesRep($salesrep, $onlyAdd = false)
	{
		global $user;

		// Handle single user
		if (!is_array($salesrep)) {
			$salesrep = array($salesrep);
		}

		$to_del = array(); // Nothing to delete
		$to_add = $salesrep;
		if ($onlyAdd === false) {
			// Get current users
			$existing = $this->getSalesRepresentatives($user, 1);

			// Diff
			if (is_array($existing)) {
				$to_del = array_diff($existing, $salesrep);
				$to_add = array_diff($salesrep, $existing);
			}
		}

		$error = 0;

		// Process
		foreach ($to_del as $del) {
			$this->del_commercial($user, $del);
		}
		foreach ($to_add as $add) {
			$result = $this->add_commercial($user, $add);
			if ($result < 0) {
				$error++;
				break;
			}
		}

		return $error ? -1 : 1;
	}

	/**
	 *    Define third-party type of current company
	 *
	 *    @param	int		$typent_id	third party type rowid in llx_c_typent
	 *    @return	int     			Return integer <0 if KO, >0 if OK
	 */
	public function setThirdpartyType($typent_id)
	{
		global $user;

		dol_syslog(__METHOD__, LOG_DEBUG);

		if ($this->id) {
			$result = $this->setValueFrom('fk_typent', $typent_id, '', null, '', '', $user, 'COMPANY_MODIFY');

			if ($result > 0) {
				$this->typent_id = $typent_id;
				$this->typent_code = dol_getIdFromCode($this->db, $this->typent_id, 'c_typent', 'id', 'code');
				return 1;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 * It must be used within a transaction to avoid trouble
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id (will be removed)
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool				True if success, False if error
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		if ($origin_id == $dest_id) {
			dol_syslog('Error: Try to merge a thirdparty into itself');
			return false;
		}

		// Sales representationves cannot be twice in the same thirdparties so we look for them and remove the one that are common some to avoid duplicate.
		// Because this function is meant to be executed within a transaction, we won't take care of begin/commit.
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'societe_commerciaux ';
		$sql .= ' WHERE fk_soc = '.(int) $dest_id.' AND fk_user IN ( ';
		$sql .= ' SELECT fk_user ';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe_commerciaux ';
		$sql .= ' WHERE fk_soc = '.(int) $origin_id.') ';

		$resql = $dbs->query($sql);
		while ($obj = $dbs->fetch_object($resql)) {
			$dbs->query('DELETE FROM '.MAIN_DB_PREFIX.'societe_commerciaux WHERE rowid = '.((int) $obj->rowid));
		}

		// llx_societe_extrafields table must not be here because we don't care about the old thirdparty extrafields that are managed directly into mergeCompany.
		// Do not include llx_societe because it will be replaced later.
		$tables = array(
			'societe_account',
			'societe_commerciaux',
			'societe_prices',
			'societe_remise',
			'societe_remise_except',
			'societe_rib'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Sets an accountancy code for a thirdparty.
	 * Also calls COMPANY_MODIFY trigger when modified
	 *
	 * @param   string  $type   It can be only 'buy' or 'sell'
	 * @param   string  $value  Accountancy code
	 * @return  int             Return integer <0 KO >0 OK
	 */
	public function setAccountancyCode($type, $value)
	{
		global $user, $langs, $conf;

		$this->db->begin();

		if ($type == 'buy') {
			$field = 'accountancy_code_buy';
		} elseif ($type == 'sell') {
			$field = 'accountancy_code_sell';
		} else {
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET ";
		$sql .= $field." = '".$this->db->escape($value)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::".__FUNCTION__, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			// Call triggers
			include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
			$interface = new Interfaces($this->db);
			$result = $interface->run_triggers('COMPANY_MODIFY', $this, $user, $langs, $conf);
			if ($result < 0) {
				$this->errors = $interface->errors;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->$field = $value;

			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *	Function to get partnerships array
	 *
	 *  @param		string		$mode		'member' or 'thirdparty'
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function fetchPartnerships($mode)
	{
		require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';

		$this->partnerships[] = array();

		return 1;
	}

	/**
	 *	Return clickable link of object (with optional picto)
	 *
	 *	@param	string				$option			Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	array<string,mixed>	$arraydata		Array of data
	 *  @return	string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = array())
	{
		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<div class="info-box-ref inline-block tdoverflowmax125 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref);
		$return .= '</div>';
		if (!empty($this->phone)) {
			$return .= '<div class="inline-block valignmiddle">';
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$return .= dol_print_phone($this->phone, $this->country_code, 0, $this->id, 'tel', 'hidenum', 'phone', $this->phone, 0, 'paddingleft paddingright');
			$return .= '</div>';
		}
		if (!empty($this->email)) {
			$return .= '<div class="inline-block valignmiddle">';
			$return .= dol_print_email($this->email, 0, $this->id, 'thirdparty', -1, 1, 2, 'paddingleft paddingright');
			$return .= '</div>';
		}
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'code_client')) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$this->code_client.'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';	// end info-box-content
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *    Get array of all contacts for a society (stored in societe_contacts instead of element_contacts for all other objects)
	 *
	 *    @param	int         $list       0:Return array contains all properties, 1:Return array contains just id
	 *    @param    string      $code       Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
	 *	  @param    string      $element    Filter on this element of default contact type ('facture', 'propal', 'commande' ...)
	 *    @return	array|int		        Array of contacts, -1 if error
	 *
	 */
	public function getContacts($list = 0, $code = '', $element = '')
	{
		// phpcs:enable
		global $langs;

		$tab = array();

		$sql = "SELECT sc.rowid, sc.fk_socpeople as id, sc.fk_c_type_contact"; // This field contains id of llx_socpeople or id of llx_user
		$sql .= ", t.fk_soc as socid, t.statut as statuscontact";
		$sql .= ", t.civility as civility, t.lastname as lastname, t.firstname, t.email";
		$sql .= ", tc.source, tc.element, tc.code, tc.libelle as type_label";
		$sql .= " FROM ".$this->db->prefix()."c_type_contact tc";
		$sql .= ", ".$this->db->prefix()."societe_contacts sc";
		$sql .= " LEFT JOIN ".$this->db->prefix()."socpeople t on sc.fk_socpeople = t.rowid";
		$sql .= " WHERE sc.fk_soc = ".((int) $this->id);
		$sql .= " AND sc.fk_c_type_contact = tc.rowid";
		if (!empty($element)) {
			$sql .= " AND tc.element = '".$this->db->escape($element)."'";
		}
		if ($code) {
			$sql .= " AND tc.code = '".$this->db->escape($code)."'";
		}
		$sql .= " AND sc.entity IN (".getEntity($this->element).")";
		$sql .= " AND tc.source = 'external'";
		$sql .= " AND tc.active = 1";

		$sql .= " ORDER BY t.lastname ASC";

		dol_syslog(get_class($this)."::getContacts", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if (!$list) {
					$transkey = "TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
					$libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->type_label);
					$tab[$i] = array(
						'source' => $obj->source,
						'socid' => $obj->socid,
						'id' => $obj->id,
						'nom' => $obj->lastname, // For backward compatibility
						'civility' => $obj->civility,
						'lastname' => $obj->lastname,
						'firstname' => $obj->firstname,
						'email' => $obj->email,
						'login' => (empty($obj->login) ? '' : $obj->login),
						'photo' => (empty($obj->photo) ? '' : $obj->photo),
						'statuscontact' => $obj->statuscontact,
						'rowid' => $obj->rowid,
						'code' => $obj->code,
						'element' => $obj->element,
						'libelle' => $libelle_type,
						'status' => $obj->statuslink,
						'fk_c_type_contact' => $obj->fk_c_type_contact
					);
				} else {
					$tab[$i] = $obj->id;
				}

				$i++;
			}

			return $tab;
		} else {
			$this->error = $this->db->lasterror();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    Merge a company with current one, deleting the given company $soc_origin_id.
	 *    The company given in parameter will be removed.
	 *    This is called for example by the societe/card.php file.
	 *    It calls the method replaceThirdparty() of each object with relation with thirdparties,
	 *    including hook 'replaceThirdparty' for external modules.
	 *
	 *    @param	int     $soc_origin_id		Company to merge the data from
	 *    @return	int							-1 if error, >=0 if OK
	 */
	public function mergeCompany($soc_origin_id)
	{
		global $conf, $langs, $hookmanager, $user, $action;

		$error = 0;
		$soc_origin = new Societe($this->db);		// The thirdparty that we will delete

		dol_syslog("mergeCompany merge thirdparty id=".$soc_origin_id." (will be deleted) into the thirdparty id=".$this->id);

		if (!$error && $soc_origin->fetch($soc_origin_id) < 1) {
			$this->error = $langs->trans('ErrorRecordNotFound');
			$error++;
		}

		if (!$error) {
			$this->db->begin();

			// Recopy some data
			$this->client |= $soc_origin->client;
			$this->fournisseur |= $soc_origin->fournisseur;
			$listofproperties = array(
				'address', 'zip', 'town', 'state_id', 'country_id', 'phone', 'phone_mobile', 'fax', 'email', 'socialnetworks', 'url', 'barcode',
				'idprof1', 'idprof2', 'idprof3', 'idprof4', 'idprof5', 'idprof6',
				'tva_intra', 'effectif_id', 'forme_juridique', 'remise_percent', 'remise_supplier_percent', 'mode_reglement_supplier_id', 'cond_reglement_supplier_id', 'name_bis',
				'stcomm_id', 'outstanding_limit', 'order_min_amount', 'supplier_order_min_amount', 'price_level', 'parent', 'default_lang', 'ref', 'ref_ext', 'import_key', 'fk_incoterms', 'fk_multicurrency',
				'code_client', 'code_fournisseur', 'code_compta', 'code_compta_fournisseur',
				'model_pdf', 'webservices_url', 'webservices_key', 'accountancy_code_sell', 'accountancy_code_buy', 'typent_id'
			);
			foreach ($listofproperties as $property) {
				if (empty($this->$property)) {
					$this->$property = $soc_origin->$property;
				}
			}

			if ($this->typent_id == -1) {
				$this->typent_id = $soc_origin->typent_id;
			}

			// Concat some data
			$listofproperties = array(
				'note_public', 'note_private'
			);
			foreach ($listofproperties as $property) {
				$this->$property = dol_concatdesc($this->$property, $soc_origin->$property);
			}

			// Merge extrafields
			if (is_array($soc_origin->array_options)) {
				foreach ($soc_origin->array_options as $key => $val) {
					if (empty($this->array_options[$key])) {
						$this->array_options[$key] = $val;
					}
				}
			}

			// If alias name is not defined on target thirdparty, we can store in it the old name of company.
			if (empty($this->name_bis) && $this->name != $soc_origin->name) {
				$this->name_bis = $this->name;
			}

			// Merge categories
			include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
			$static_cat = new Categorie($this->db);

			$custcats_ori = $static_cat->containing($soc_origin->id, 'customer', 'id');
			$custcats = $static_cat->containing($this->id, 'customer', 'id');
			$custcats = array_merge($custcats, $custcats_ori);
			$this->setCategories($custcats, 'customer');

			$suppcats_ori = $static_cat->containing($soc_origin->id, 'supplier', 'id');
			$suppcats = $static_cat->containing($this->id, 'supplier', 'id');
			$suppcats = array_merge($suppcats, $suppcats_ori);
			$this->setCategories($suppcats, 'supplier');

			// If thirdparty has a new code that is same than origin, we clean origin code to avoid duplicate key from database unique keys.
			if ($soc_origin->code_client == $this->code_client
				|| $soc_origin->code_fournisseur == $this->code_fournisseur
				|| $soc_origin->barcode == $this->barcode) {
				dol_syslog("We clean customer and supplier code so we will be able to make the update of target");
				$soc_origin->code_client = '';
				$soc_origin->code_fournisseur = '';
				$soc_origin->barcode = '';
				$soc_origin->update($soc_origin->id, $user, 0, 1, 1, 'merge');
			}

			// Update
			$result = $this->update($this->id, $user, 0, 1, 1, 'merge');

			if ($result < 0) {
				$error++;
			}

			// Move links
			if (!$error) {
				$objects = array(
					'Adherent' => '/adherents/class/adherent.class.php',
					//'Categorie' => '/categories/class/categorie.class.php',	// Already processed previously
					'ActionComm' => '/comm/action/class/actioncomm.class.php',
					'Propal' => '/comm/propal/class/propal.class.php',
					'Commande' => '/commande/class/commande.class.php',
					'Facture' => '/compta/facture/class/facture.class.php',
					'FactureRec' => '/compta/facture/class/facture-rec.class.php',
					'LignePrelevement' => '/compta/prelevement/class/ligneprelevement.class.php',
					'Contact' => '/contact/class/contact.class.php',
					'Contrat' => '/contrat/class/contrat.class.php',
					'Expedition' => '/expedition/class/expedition.class.php',
					'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
					'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
					'FactureFournisseurRec' => '/fourn/class/fournisseur.facture-rec.class.php',
					'Reception' => '/reception/class/reception.class.php',
					'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
					'ProductFournisseur' => '/fourn/class/fournisseur.product.class.php',
					'Product' => '/product/class/product.class.php',
					//'ProductThirparty' => '...', 	// for llx_product_thirdparty
					'Project' => '/projet/class/project.class.php',
					'User' => '/user/class/user.class.php',
					'Account' => '/compta/bank/class/account.class.php',
					'ConferenceOrBoothAttendee' => '/eventorganization/class/conferenceorboothattendee.class.php',
					'Societe' => '/societe/class/societe.class.php',
					//'SocieteAccount', 'SocietePrice', 'SocieteRib',... are processed into the replaceThirparty of Societe.
				);
				if ($this->db->DDLListTables($conf->db->name, $this->db->prefix().'delivery')) {
					$objects['Delivery'] = '/delivery/class/delivery.class.php';
				}
				if ($this->db->DDLListTables($conf->db->name, $this->db->prefix().'mrp_mo')) {
					$objects['Mo'] = '/mrp/class/mo.class.php';
				}
				if ($this->db->DDLListTables($conf->db->name, $this->db->prefix().'don')) {
					$objects['Don'] = '/don/class/don.class.php';
				}
				if ($this->db->DDLListTables($conf->db->name, $this->db->prefix().'partnership')) {
					$objects['PartnerShip'] = '/partnership/class/partnership.class.php';
				}
				if ($this->db->DDLListTables($conf->db->name, $this->db->prefix().'fichinter')) {
					$objects['Fichinter'] = '/fichinter/class/fichinter.class.php';
				}
				if ($this->db->DDLListTables($conf->db->name, $this->db->prefix().'ticket')) {
					$objects['Ticket'] = '/ticket/class/ticket.class.php';
				}

				//First, all core objects must update their tables
				foreach ($objects as $object_name => $object_file) {
					/* $object_file is never an array -> code commented
					if (is_array($object_file)) {
						if (empty($object_file['enabled'])) {
							continue;
						}
						$object_file = $object_file['file'];
					}
					*/

					require_once DOL_DOCUMENT_ROOT.$object_file;

					if (!$error && !$object_name::replaceThirdparty($this->db, $soc_origin->id, $this->id)) {
						$error++;
						$this->error = $this->db->lasterror();
						break;
					}
				}
			}

			// External modules should update their ones too
			if (!$error) {
				$parameters = array('soc_origin' => $soc_origin->id, 'soc_dest' => $this->id);
				$reshook = $hookmanager->executeHooks('replaceThirdparty', $parameters, $this, $action);

				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
					$error++;
				}
			}


			if (!$error) {
				$this->context = array('merge' => 1, 'mergefromid' => $soc_origin->id, 'mergefromname' => $soc_origin->name);

				// Call trigger
				$result = $this->call_trigger('COMPANY_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				// Move files from the dir of the third party to delete into the dir of the third party to keep
				if (!empty($conf->societe->multidir_output[$this->entity])) {
					$srcdir = $conf->societe->multidir_output[$this->entity]."/".$soc_origin->id;
					$destdir = $conf->societe->multidir_output[$this->entity]."/".$this->id;

					if (dol_is_dir($srcdir)) {
						$dirlist = dol_dir_list($srcdir, 'files', 1);
						foreach ($dirlist as $filetomove) {
							$destfile = $destdir.'/'.$filetomove['relativename'];
							//var_dump('Move file '.$filetomove['relativename'].' into '.$destfile);
							dol_move($filetomove['fullname'], $destfile, '0', 0, 0, 1);
						}
						//exit;
					}
				}
			}


			if (!$error) {
				// We finally remove the old thirdparty
				if ($soc_origin->delete($soc_origin->id, $user) < 1) {
					$this->error = $soc_origin->error;
					$this->errors = $soc_origin->errors;
					$error++;
				}
			}

			if (!$error) {
				$this->db->commit();
				return 0;
			} else {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorsThirdpartyMerge');
				$this->db->rollback();
				return -1;
			}
		}

		return -1;
	}
}
