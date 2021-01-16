<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2018  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2013       Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Rui Strecht			    <rui.strecht@aliartalentos.com>
 * Copyright (C) 2018	    Philippe Grand	        <philippe.grand@atoo-net.com>
 * Copyright (C) 2019-2020  Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Open-Dsi         		<support@open-dsi.fr>
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
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class Societe extends CommonObject
{
	use CommonIncoterm;

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
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array(
		"supplier_proposal" => 'SupplierProposal',
		"propal" => 'Proposal',
		"commande" => 'Order',
		"facture" => 'Invoice',
		"facture_rec" => 'RecurringInvoiceTemplate',
		"contrat" => 'Contract',
		"fichinter" => 'Fichinter',
		"facture_fourn" => 'SupplierInvoice',
		"commande_fournisseur" => 'SupplierOrder',
		"projet" => 'Project',
		"expedition" => 'Shipment',
		"prelevement_lignes" => 'DirectDebitRecord',
	);

	/**
	 * @var array    List of child tables. To know object to delete on cascade.
	 *               if name like with @ClassNAme:FilePathClass;ParentFkFieldName' it will call method deleteByParentField (with parentId as parameters) and FieldName to fetch and delete child object
	 */
	protected $childtablesoncascade = array(
		"societe_prices",
		"societe_address",
		"product_fournisseur_price",
		"product_customer_price_log",
		"product_customer_price",
		"@Contact:/contact/class/contact.class.php:fk_soc",
		"adherent",
		"societe_account",
		"societe_rib",
		"societe_remise",
		"societe_remise_except",
		"societe_commerciaux",
		"categorie",
		"notify",
		"notify_def",
		"actioncomm",
	);

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'company';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * 0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 * @var integer
	 */
	public $restrictiononfksoc = 1;

	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'parent' =>array('type'=>'integer', 'label'=>'Parent', 'enabled'=>1, 'visible'=>-1, 'position'=>20),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>25),
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-1, 'position'=>30),
		'nom' =>array('type'=>'varchar(128)', 'label'=>'Nom', 'enabled'=>1, 'visible'=>-1, 'position'=>35, 'showoncombobox'=>1),
		'name_alias' =>array('type'=>'varchar(128)', 'label'=>'Name alias', 'enabled'=>1, 'visible'=>-1, 'position'=>36, 'showoncombobox'=>1),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>40, 'index'=>1),
		'ref_ext' =>array('type'=>'varchar(255)', 'label'=>'RefExt', 'enabled'=>1, 'visible'=>0, 'position'=>45),
		'code_client' =>array('type'=>'varchar(24)', 'label'=>'CustomerCode', 'enabled'=>1, 'visible'=>-1, 'position'=>55),
		'code_fournisseur' =>array('type'=>'varchar(24)', 'label'=>'SupplierCode', 'enabled'=>1, 'visible'=>-1, 'position'=>60),
		'code_compta' =>array('type'=>'varchar(24)', 'label'=>'CodeCompta', 'enabled'=>1, 'visible'=>-1, 'position'=>65),
		'code_compta_fournisseur' =>array('type'=>'varchar(24)', 'label'=>'CodeComptaSupplier', 'enabled'=>1, 'visible'=>-1, 'position'=>70),
		'address' =>array('type'=>'varchar(255)', 'label'=>'Address', 'enabled'=>1, 'visible'=>-1, 'position'=>75),
		'zip' =>array('type'=>'varchar(25)', 'label'=>'Zip', 'enabled'=>1, 'visible'=>-1, 'position'=>80),
		'town' =>array('type'=>'varchar(50)', 'label'=>'Town', 'enabled'=>1, 'visible'=>-1, 'position'=>85),
		'fk_departement' =>array('type'=>'integer', 'label'=>'State', 'enabled'=>1, 'visible'=>-1, 'position'=>90),
		'fk_pays' =>array('type'=>'integer:Ccountry:core/class/ccountry.class.php', 'label'=>'Country', 'enabled'=>1, 'visible'=>-1, 'position'=>95),
		'phone' =>array('type'=>'varchar(20)', 'label'=>'Phone', 'enabled'=>1, 'visible'=>-1, 'position'=>100),
		'fax' =>array('type'=>'varchar(20)', 'label'=>'Fax', 'enabled'=>1, 'visible'=>-1, 'position'=>105),
		'url' =>array('type'=>'varchar(255)', 'label'=>'Url', 'enabled'=>1, 'visible'=>-1, 'position'=>110),
		'email' =>array('type'=>'varchar(128)', 'label'=>'Email', 'enabled'=>1, 'visible'=>-1, 'position'=>115),
		'socialnetworks' =>array('type'=>'text', 'label'=>'Socialnetworks', 'enabled'=>1, 'visible'=>-1, 'position'=>120),
		/*'skype' =>array('type'=>'varchar(255)', 'label'=>'Skype', 'enabled'=>1, 'visible'=>-1, 'position'=>125),
		'whatsapp' =>array('type'=>'varchar(255)', 'label'=>'Whatsapp', 'enabled'=>1, 'visible'=>-1, 'position'=>130),
		'linkedin' =>array('type'=>'varchar(255)', 'label'=>'Linkedin', 'enabled'=>1, 'visible'=>-1, 'position'=>135),
		'youtube' =>array('type'=>'varchar(255)', 'label'=>'Youtube', 'enabled'=>1, 'visible'=>-1, 'position'=>140),
		'googleplus' =>array('type'=>'varchar(255)', 'label'=>'Googleplus', 'enabled'=>1, 'visible'=>-1, 'position'=>145),
		'snapchat' =>array('type'=>'varchar(255)', 'label'=>'Snapchat', 'enabled'=>1, 'visible'=>-1, 'position'=>150),
		'instagram' =>array('type'=>'varchar(255)', 'label'=>'Instagram', 'enabled'=>1, 'visible'=>-1, 'position'=>155),
		'facebook' =>array('type'=>'varchar(255)', 'label'=>'Facebook', 'enabled'=>1, 'visible'=>-1, 'position'=>160),
		'twitter' =>array('type'=>'varchar(255)', 'label'=>'Twitter', 'enabled'=>1, 'visible'=>-1, 'position'=>165),*/
		'fk_effectif' =>array('type'=>'integer', 'label'=>'Workforce', 'enabled'=>1, 'visible'=>-1, 'position'=>170),
		'fk_typent' =>array('type'=>'integer', 'label'=>'TypeOfCompany', 'enabled'=>1, 'visible'=>-1, 'position'=>175),
		'fk_forme_juridique' =>array('type'=>'integer', 'label'=>'JuridicalStatus', 'enabled'=>1, 'visible'=>-1, 'position'=>180),
		'fk_currency' =>array('type'=>'varchar(3)', 'label'=>'Currency', 'enabled'=>1, 'visible'=>-1, 'position'=>185),
		'siren' =>array('type'=>'varchar(128)', 'label'=>'Idprof1', 'enabled'=>1, 'visible'=>-1, 'position'=>190),
		'siret' =>array('type'=>'varchar(128)', 'label'=>'Idprof2', 'enabled'=>1, 'visible'=>-1, 'position'=>195),
		'ape' =>array('type'=>'varchar(128)', 'label'=>'Idprof3', 'enabled'=>1, 'visible'=>-1, 'position'=>200),
		'idprof4' =>array('type'=>'varchar(128)', 'label'=>'Idprof4', 'enabled'=>1, 'visible'=>-1, 'position'=>205),
		'idprof5' =>array('type'=>'varchar(128)', 'label'=>'Idprof5', 'enabled'=>1, 'visible'=>-1, 'position'=>206),
		'idprof6' =>array('type'=>'varchar(128)', 'label'=>'Idprof6', 'enabled'=>1, 'visible'=>-1, 'position'=>207),
		'tva_intra' =>array('type'=>'varchar(20)', 'label'=>'Tva intra', 'enabled'=>1, 'visible'=>-1, 'position'=>210),
		'capital' =>array('type'=>'double(24,8)', 'label'=>'Capital', 'enabled'=>1, 'visible'=>-1, 'position'=>215),
		'fk_stcomm' =>array('type'=>'integer', 'label'=>'CommercialStatus', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>220),
		'note_private' =>array('type'=>'text', 'label'=>'NotePublic', 'enabled'=>1, 'visible'=>0, 'position'=>225),
		'note_public' =>array('type'=>'text', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>0, 'position'=>230),
		'prefix_comm' =>array('type'=>'varchar(5)', 'label'=>'Prefix comm', 'enabled'=>'$conf->global->SOCIETE_USEPREFIX', 'visible'=>-1, 'position'=>235),
		'client' =>array('type'=>'tinyint(4)', 'label'=>'Client', 'enabled'=>1, 'visible'=>-1, 'position'=>240),
		'fournisseur' =>array('type'=>'tinyint(4)', 'label'=>'Fournisseur', 'enabled'=>1, 'visible'=>-1, 'position'=>245),
		'supplier_account' =>array('type'=>'varchar(32)', 'label'=>'Supplier account', 'enabled'=>1, 'visible'=>-1, 'position'=>250),
		'fk_prospectlevel' =>array('type'=>'varchar(12)', 'label'=>'ProspectLevel', 'enabled'=>1, 'visible'=>-1, 'position'=>255),
		'customer_bad' =>array('type'=>'tinyint(4)', 'label'=>'Customer bad', 'enabled'=>1, 'visible'=>-1, 'position'=>260),
		'customer_rate' =>array('type'=>'double', 'label'=>'Customer rate', 'enabled'=>1, 'visible'=>-1, 'position'=>265),
		'supplier_rate' =>array('type'=>'double', 'label'=>'Supplier rate', 'enabled'=>1, 'visible'=>-1, 'position'=>270),
		'fk_user_creat' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserAuthor', 'enabled'=>1, 'visible'=>-2, 'position'=>275),
		'fk_user_modif' =>array('type'=>'integer:User:user/class/user.class.php', 'label'=>'UserModif', 'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>280),
		//'remise_client' =>array('type'=>'double', 'label'=>'CustomerDiscount', 'enabled'=>1, 'visible'=>-1, 'position'=>285, 'isameasure'=>1),
		//'remise_supplier' =>array('type'=>'double', 'label'=>'SupplierDiscount', 'enabled'=>1, 'visible'=>-1, 'position'=>290, 'isameasure'=>1),
		'mode_reglement' =>array('type'=>'tinyint(4)', 'label'=>'Mode reglement', 'enabled'=>1, 'visible'=>-1, 'position'=>295),
		'cond_reglement' =>array('type'=>'tinyint(4)', 'label'=>'Cond reglement', 'enabled'=>1, 'visible'=>-1, 'position'=>300),
		'mode_reglement_supplier' =>array('type'=>'integer', 'label'=>'Mode reglement supplier', 'enabled'=>1, 'visible'=>-1, 'position'=>305),
		'outstanding_limit' =>array('type'=>'double(24,8)', 'label'=>'OutstandingBill', 'enabled'=>1, 'visible'=>-1, 'position'=>310, 'isameasure'=>1),
		'order_min_amount' =>array('type'=>'double(24,8)', 'label'=>'Order min amount', 'enabled'=>'!empty($conf->commande->enabled) && !empty($conf->global->ORDER_MANAGE_MIN_AMOUNT)', 'visible'=>-1, 'position'=>315, 'isameasure'=>1),
		'supplier_order_min_amount' =>array('type'=>'double(24,8)', 'label'=>'Supplier order min amount', 'enabled'=>'!empty($conf->commande->enabled) && !empty($conf->global->ORDER_MANAGE_MIN_AMOUNT)', 'visible'=>-1, 'position'=>320, 'isameasure'=>1),
		'cond_reglement_supplier' =>array('type'=>'integer', 'label'=>'Cond reglement supplier', 'enabled'=>1, 'visible'=>-1, 'position'=>325),
		'fk_shipping_method' =>array('type'=>'integer', 'label'=>'Fk shipping method', 'enabled'=>1, 'visible'=>-1, 'position'=>330),
		'tva_assuj' =>array('type'=>'tinyint(4)', 'label'=>'Tva assuj', 'enabled'=>1, 'visible'=>-1, 'position'=>335),
		'localtax1_assuj' =>array('type'=>'tinyint(4)', 'label'=>'Localtax1 assuj', 'enabled'=>1, 'visible'=>-1, 'position'=>340),
		'localtax1_value' =>array('type'=>'double(6,3)', 'label'=>'Localtax1 value', 'enabled'=>1, 'visible'=>-1, 'position'=>345),
		'localtax2_assuj' =>array('type'=>'tinyint(4)', 'label'=>'Localtax2 assuj', 'enabled'=>1, 'visible'=>-1, 'position'=>350),
		'localtax2_value' =>array('type'=>'double(6,3)', 'label'=>'Localtax2 value', 'enabled'=>1, 'visible'=>-1, 'position'=>355),
		'barcode' =>array('type'=>'varchar(255)', 'label'=>'Barcode', 'enabled'=>1, 'visible'=>-1, 'position'=>360),
		'price_level' =>array('type'=>'integer', 'label'=>'Price level', 'enabled'=>'$conf->global->PRODUIT_MULTIPRICES || $conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES', 'visible'=>-1, 'position'=>365),
		'default_lang' =>array('type'=>'varchar(6)', 'label'=>'Default lang', 'enabled'=>1, 'visible'=>-1, 'position'=>370),
		'canvas' =>array('type'=>'varchar(32)', 'label'=>'Canvas', 'enabled'=>1, 'visible'=>-1, 'position'=>375),
		'fk_barcode_type' =>array('type'=>'integer', 'label'=>'Fk barcode type', 'enabled'=>1, 'visible'=>-1, 'position'=>405),
		'webservices_url' =>array('type'=>'varchar(255)', 'label'=>'Webservices url', 'enabled'=>1, 'visible'=>-1, 'position'=>410),
		'webservices_key' =>array('type'=>'varchar(128)', 'label'=>'Webservices key', 'enabled'=>1, 'visible'=>-1, 'position'=>415),
		'fk_incoterms' =>array('type'=>'integer', 'label'=>'Fk incoterms', 'enabled'=>1, 'visible'=>-1, 'position'=>425),
		'location_incoterms' =>array('type'=>'varchar(255)', 'label'=>'Location incoterms', 'enabled'=>1, 'visible'=>-1, 'position'=>430),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'Model pdf', 'enabled'=>1, 'visible'=>0, 'position'=>435),
		'fk_multicurrency' =>array('type'=>'integer', 'label'=>'Fk multicurrency', 'enabled'=>1, 'visible'=>-1, 'position'=>440),
		'multicurrency_code' =>array('type'=>'varchar(255)', 'label'=>'Multicurrency code', 'enabled'=>1, 'visible'=>-1, 'position'=>445),
		'fk_account' =>array('type'=>'integer', 'label'=>'Fk account', 'enabled'=>1, 'visible'=>-1, 'position'=>450),
		'fk_entrepot' =>array('type'=>'integer', 'label'=>'Fk entrepot', 'enabled'=>1, 'visible'=>-1, 'position'=>455),
		'logo' =>array('type'=>'varchar(255)', 'label'=>'Logo', 'enabled'=>1, 'visible'=>-1, 'position'=>400),
		'logo_squarred' =>array('type'=>'varchar(255)', 'label'=>'Logo squarred', 'enabled'=>1, 'visible'=>-1, 'position'=>401),
		'status' =>array('type'=>'tinyint(4)', 'label'=>'Status', 'enabled'=>1, 'visible'=>-1, 'position'=>500),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>1000),
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
	 * @var string Address
	 */
	public $address;

	/**
	 * @var string Zip code of thirdparty
	 */
	public $zip;

	/**
	 * @var string Town of thirdparty
	 */
	public $town;

	/**
	 * Thirdparty status : 0=activity ceased, 1= in activity
	 * @var int
	 */
	public $status = 1;

	/**
	 * Id of department
	 * @var int
	 */
	public $state_id;

	/**
	 * @var string State code
	 */
	public $state_code;

	/**
	 * @var string State name
	 */
	public $state;

	/**
	 * Id of region
	 * @var int
	 */
	public $region_code;

	/**
	 * @var string Region name
	 */
	public $region;

	/**
	 * @var string State code
	 * @deprecated Use state_code instead
	 * @see $state_code
	 */
	public $departement_code;

	/**
	 * @var string
	 * @deprecated Use state instead
	 * @see $state
	 */
	public $departement;

	/**
	 * @var string
	 * @deprecated Use country instead
	 * @see $country
	 */
	public $pays;

	/**
	 * Phone number
	 * @var string
	 */
	public $phone;
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
	 * @var array array of socialnetworks
	 */
	public $socialnetworks;

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
	 * Professional ID 2 (Ex: Siret in France)
	 * @var string
	 */
	public $idprof2;

	/**
	 * Professional ID 3 (Ex: Ape in France)
	 * @var string
	 */
	public $idprof3;

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
	 * @var string Prefix comm
	 */
	public $prefix_comm;

	/**
	 * @var int Vat concerned
	 */
	public $tva_assuj = 1;

	/**
	 * Intracommunitary VAT ID
	 * @var string
	 */
	public $tva_intra;

	// Local taxes
	public $localtax1_assuj;
	public $localtax1_value;
	public $localtax2_assuj;
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
	public $typent_code;
	public $effectif;
	public $effectif_id = 0;
	public $forme_juridique_code;
	public $forme_juridique = 0;

	public $remise_percent;
	public $remise_supplier_percent;
	public $mode_reglement_supplier_id;
	public $cond_reglement_supplier_id;
	public $transport_mode_supplier_id;

	/**
	 * @var int ID
	 */
	public $fk_prospectlevel;

	/**
	 * @var string second name
	 */
	public $name_bis;

	//Log data

	/**
	 * Date of last update
	 * @var string
	 */
	public $date_modification;

	/**
	 * User that made last update
	 * @var string
	 */
	public $user_modification;

	/**
	 * @var integer|string date_creation
	 */
	public $date_creation;

	/**
	 * User that created the thirdparty
	 * @var User
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
	public $code_compta;

	/**
	 * Accounting code for client
	 * @var string
	 */
	public $code_compta_client;

	/**
	 * Accounting code for suppliers
	 * @var string
	 */
	public $code_compta_fournisseur;

	/**
	 * @var string
	 * @deprecated Note is split in public and private notes
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
	 * @var string Internal ref
	 * @deprecated
	 */
	public $ref_int;

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


	// Multicurrency
	/**
	 * @var int ID
	 */
	public $fk_multicurrency;

	/**
	 * @var string Multicurrency code
	 */
	public $multicurrency_code;


	/**
	 * @var Account|string Default BAN account
	 */
	public $bank_account;

	/**
	 * Third party is no customer
	 */
	const NO_CUSTOMER = 0;

	/**
	 * Third party is a customer
	 */
	const CUSTOMER = 1;

	/**
	 * Third party is a prospect
	 */
	const PROSPECT = 2;

	/**
	 * Third party is a customer and a prospect
	 */
	const CUSTOMER_AND_PROSPECT = 3;

	/**
	 * Third party is no supplier
	 */
	const NO_SUPPLIER = 0;

	/**
	 * Third party is a supplier
	 */
	const SUPPLIER = 1;

	/**
	 *    Constructor
	 *
	 *    @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->client = 0;
		$this->prospect = 0;
		$this->fournisseur = 0;
		$this->typent_id = 0;
		$this->effectif_id = 0;
		$this->forme_juridique_code = 0;
		$this->tva_assuj = 1;
		$this->status = 1;

		if (!empty($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST)) {
			$this->fields['address']['showoncombobox'] = $conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST;
			$this->fields['zip']['showoncombobox'] = $conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST;
			$this->fields['town']['showoncombobox'] = $conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST;
			//$this->fields['fk_pays']['showoncombobox'] = $conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST;
		}
	}


	/**
	 *    Create third party in database.
	 *    $this->code_client = -1 and $this->code_fournisseur = -1 means automatic assignement.
	 *
	 *    @param	User	$user       Object of user that ask creation
	 *    @return   int         		>=0 if OK, <0 if KO
	 */
	public function create(User $user)
	{
		global $langs, $conf, $mysoc;

		$error = 0;

		// Clean parameters
		if (empty($this->status)) {
			$this->status = 0;
		}
		$this->name = $this->name ?trim($this->name) : trim($this->nom);
		$this->setUpperOrLowerCase();
		$this->nom = $this->name; // For backward compatibility
		if (empty($this->client)) {
			$this->client = 0;
		}
		if (empty($this->fournisseur)) {
			$this->fournisseur = 0;
		}
		$this->import_key = trim($this->import_key);

		if (!empty($this->multicurrency_code)) {
			$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = '';
			$this->fk_multicurrency = 0;
		}

		dol_syslog(get_class($this)."::create ".$this->name);

		$now = dol_now();

		$this->db->begin();

		// For automatic creation during create action (not used by Dolibarr GUI, can be used by scripts)
		if ($this->code_client == -1 || $this->code_client === 'auto') {
			$this->get_codeclient($this, 0);
		}
		if ($this->code_fournisseur == -1 || $this->code_fournisseur === 'auto') {
			$this->get_codefournisseur($this, 1);
		}

		// Check more parameters (including mandatory setup
		// If error, this->errors[] is filled
		$result = $this->verify();

		if ($result >= 0) {
			$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, name_alias, entity, datec, fk_user_creat, canvas, status, ref_ext, fk_stcomm, fk_incoterms, location_incoterms ,import_key, fk_multicurrency, multicurrency_code)";
			$sql .= " VALUES ('".$this->db->escape($this->name)."', '".$this->db->escape($this->name_alias)."', ".$this->db->escape($this->entity).", '".$this->db->idate($now)."'";
			$sql .= ", ".(!empty($user->id) ? ((int) $user->id) : "null");
			$sql .= ", ".(!empty($this->canvas) ? "'".$this->db->escape($this->canvas)."'" : "null");
			$sql .= ", ".$this->status;
			$sql .= ", ".(!empty($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null");
			$sql .= ", 0";
			$sql .= ", ".(int) $this->fk_incoterms;
			$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
			$sql .= ", ".(!empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null");
			$sql .= ", ".(int) $this->fk_multicurrency;
			$sql .= ", '".$this->db->escape($this->multicurrency_code)."')";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe");

				$ret = $this->update($this->id, $user, 0, 1, 1, 'add');

				// Ajout du commercial affecte
				if ($this->commercial_id != '' && $this->commercial_id != -1) {
					$this->add_commercial($user, $this->commercial_id);
				}
				// si un commercial cree un client il lui est affecte automatiquement
				elseif (empty($user->rights->societe->client->voir)) {
					$this->add_commercial($user, $user->id);
				}

				if ($ret >= 0) {
					// Call trigger
					$result = $this->call_trigger('COMPANY_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				} else {
					$error++;
				}

				if (!$error) {
					dol_syslog(get_class($this)."::Create success id=".$this->id);
					$this->db->commit();
					return $this->id;
				} else {
					dol_syslog(get_class($this)."::Create echec update ".$this->error.(empty($this->errors) ? '' : ' '.join(',', $this->errors)), LOG_ERR);
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
			dol_syslog(get_class($this)."::Create fails verify ".join(',', $this->errors), LOG_WARNING);
			return -3;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Create a contact/address from thirdparty
	 *
	 * @param 	User	$user		Object user
	 * @return 	int					<0 if KO, >0 if OK
	 */
	public function create_individual(User $user)
	{
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
		$contact->phone_pro         = $this->phone;

		$result = $contact->create($user);
		if ($result < 0) {
			$this->error = $contact->error;
			$this->errors = $contact->errors;
			dol_syslog(get_class($this)."::create_individual ERROR:".$this->error, LOG_ERR);
		}

		return $result;
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
		$array_to_check = array('IDPROF1', 'IDPROF2', 'IDPROF3', 'IDPROF4', 'IDPROF5', 'IDPROF6', 'EMAIL');
		foreach ($array_to_check as $key) {
			$keymin = strtolower($key);
			$i = (int) preg_replace('/[^0-9]/', '', $key);
			$vallabel = $this->$keymin;

			if ($i > 0) {
				if ($this->isACompany()) {
					// Check for mandatory prof id (but only if country is same than ours)
					if ($mysoc->country_id > 0 && $this->country_id == $mysoc->country_id) {
						$idprof_mandatory = 'SOCIETE_'.$key.'_MANDATORY';
						if (!$vallabel && !empty($conf->global->$idprof_mandatory)) {
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
					if (!empty($conf->global->SOCIETE_EMAIL_MANDATORY) && !isValidEMail($this->email)) {
						$langs->load("errors");
						$error++;
						$this->errors[] = $langs->trans("ErrorBadEMail", $this->email).' ('.$langs->trans("ForbiddenBySetupRules").')';
					}

					// Check for unicity
					if (!$error && $vallabel && !empty($conf->global->SOCIETE_EMAIL_UNIQUE)) {
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0))) {
							$langs->load("errors");
							$error++; $this->errors[] = $langs->trans('Email')." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
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
	 *      @return int  			           			<0 if KO, >=0 if OK
	 */
	public function update($id, $user = '', $call_trigger = 1, $allowmodcodeclient = 0, $allowmodcodefournisseur = 0, $action = 'update', $nosyncmember = 1)
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
		$this->id = $id;
		$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);
		$this->name = $this->name ?trim($this->name) : trim($this->nom);
		$this->nom = $this->name; // For backward compatibility
		$this->name_alias = trim($this->name_alias);
		$this->ref_ext		= trim($this->ref_ext);
		$this->address		= $this->address ?trim($this->address) : trim($this->address);
		$this->zip = $this->zip ?trim($this->zip) : trim($this->zip);
		$this->town = $this->town ?trim($this->town) : trim($this->town);
		$this->setUpperOrLowerCase();
		$this->state_id = trim($this->state_id);
		$this->country_id = ($this->country_id > 0) ? $this->country_id : 0;
		$this->phone		= trim($this->phone);
		$this->phone		= preg_replace("/\s/", "", $this->phone);
		$this->phone		= preg_replace("/\./", "", $this->phone);
		$this->fax			= trim($this->fax);
		$this->fax			= preg_replace("/\s/", "", $this->fax);
		$this->fax			= preg_replace("/\./", "", $this->fax);
		$this->email = trim($this->email);
		$this->url			= $this->url ?clean_url($this->url, 0) : '';
		$this->note_private = trim($this->note_private);
		$this->note_public  = trim($this->note_public);
		$this->idprof1		= trim($this->idprof1);
		$this->idprof2		= trim($this->idprof2);
		$this->idprof3		= trim($this->idprof3);
		$this->idprof4		= trim($this->idprof4);
		$this->idprof5		= (!empty($this->idprof5) ?trim($this->idprof5) : '');
		$this->idprof6		= (!empty($this->idprof6) ?trim($this->idprof6) : '');
		$this->prefix_comm = trim($this->prefix_comm);
		$this->outstanding_limit = price2num($this->outstanding_limit);
		$this->order_min_amount = price2num($this->order_min_amount);
		$this->supplier_order_min_amount = price2num($this->supplier_order_min_amount);

		$this->tva_assuj	= trim($this->tva_assuj);
		$this->tva_intra	= dol_sanitizeFileName($this->tva_intra, '');
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
		$this->localtax1_assuj = trim($this->localtax1_assuj);
		$this->localtax2_assuj = trim($this->localtax2_assuj);

		$this->localtax1_value = trim($this->localtax1_value);
		$this->localtax2_value = trim($this->localtax2_value);

		if ($this->capital != '') {
			$this->capital = price2num(trim($this->capital));
		}
		if (!is_numeric($this->capital)) {
			$this->capital = ''; // '' = undef
		}

		$this->effectif_id = trim($this->effectif_id);
		$this->forme_juridique_code = trim($this->forme_juridique_code);

		//Gencod
		$this->barcode = trim($this->barcode);

		// For automatic creation
		if ($this->code_client == -1 || $this->code_client === 'auto') {
			$this->get_codeclient($this, 0);
		}
		if ($this->code_fournisseur == -1 || $this->code_fournisseur === 'auto') {
			$this->get_codefournisseur($this, 1);
		}

		$this->code_compta = trim($this->code_compta);
		$this->code_compta_fournisseur = trim($this->code_compta_fournisseur);

		// Check parameters. More tests are done later in the ->verify()
		if (!is_numeric($this->client) && !is_numeric($this->fournisseur)) {
			$langs->load("errors");
			$this->error = $langs->trans("BadValueForParameterClientOrSupplier");
			return -1;
		}

		$customer = false;
		if (!empty($allowmodcodeclient) && !empty($this->client)) {
			// Attention get_codecompta peut modifier le code suivant le module utilise
			if (empty($this->code_compta)) {
				$ret = $this->get_codecompta('customer');
				if ($ret < 0) {
					return -1;
				}
			}

			$customer = true;
		}

		$supplier = false;
		if (!empty($allowmodcodefournisseur) && !empty($this->fournisseur)) {
			// Attention get_codecompta peut modifier le code suivant le module utilise
			if ($this->code_compta_fournisseur == "") {
				$ret = $this->get_codecompta('supplier');
				if ($ret < 0) {
					return -1;
				}
			}

			$supplier = true;
		}

		//Web services
		$this->webservices_url = $this->webservices_url ?clean_url($this->webservices_url, 0) : '';
		$this->webservices_key = trim($this->webservices_key);

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

			$sql .= ",fk_departement = '".(!empty($this->state_id) ? $this->state_id : '0')."'";
			$sql .= ",fk_pays = '".(!empty($this->country_id) ? $this->country_id : '0')."'";

			$sql .= ",phone = ".(!empty($this->phone) ? "'".$this->db->escape($this->phone)."'" : "null");
			$sql .= ",fax = ".(!empty($this->fax) ? "'".$this->db->escape($this->fax)."'" : "null");
			$sql .= ",email = ".(!empty($this->email) ? "'".$this->db->escape($this->email)."'" : "null");
			$sql .= ", socialnetworks = '".$this->db->escape(json_encode($this->socialnetworks))."'";
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
			$sql .= ",status = ".$this->status;

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

			$sql .= ",capital = ".($this->capital == '' ? "null" : $this->capital);

			$sql .= ",prefix_comm = ".(!empty($this->prefix_comm) ? "'".$this->db->escape($this->prefix_comm)."'" : "null");

			$sql .= ",fk_effectif = ".(!empty($this->effectif_id) ? "'".$this->db->escape($this->effectif_id)."'" : "null");
			if (isset($this->stcomm_id)) {
				$sql .= ",fk_stcomm=".(!empty($this->stcomm_id) ? $this->stcomm_id : "0");
			}
			$sql .= ",fk_typent = ".(!empty($this->typent_id) ? "'".$this->db->escape($this->typent_id)."'" : "0");

			$sql .= ",fk_forme_juridique = ".(!empty($this->forme_juridique_code) ? "'".$this->db->escape($this->forme_juridique_code)."'" : "null");

			$sql .= ",mode_reglement = ".(!empty($this->mode_reglement_id) ? "'".$this->db->escape($this->mode_reglement_id)."'" : "null");
			$sql .= ",cond_reglement = ".(!empty($this->cond_reglement_id) ? "'".$this->db->escape($this->cond_reglement_id)."'" : "null");
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

			$sql .= ",webservices_url = ".(!empty($this->webservices_url) ? "'".$this->db->escape($this->webservices_url)."'" : "null");
			$sql .= ",webservices_key = ".(!empty($this->webservices_key) ? "'".$this->db->escape($this->webservices_key)."'" : "null");

			//Incoterms
			$sql .= ", fk_incoterms = ".$this->fk_incoterms;
			$sql .= ", location_incoterms = ".(!empty($this->location_incoterms) ? "'".$this->db->escape($this->location_incoterms)."'" : "null");

			if ($customer) {
				$sql .= ", code_client = ".(!empty($this->code_client) ? "'".$this->db->escape($this->code_client)."'" : "null");
				$sql .= ", code_compta = ".(!empty($this->code_compta) ? "'".$this->db->escape($this->code_compta)."'" : "null");
			}

			if ($supplier) {
				$sql .= ", code_fournisseur = ".(!empty($this->code_fournisseur) ? "'".$this->db->escape($this->code_fournisseur)."'" : "null");
				$sql .= ", code_compta_fournisseur = ".(($this->code_compta_fournisseur != "") ? "'".$this->db->escape($this->code_compta_fournisseur)."'" : "null");
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
					if (!$nosyncmember && !empty($conf->adherent->enabled)) {
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

				// Actions on extra fields
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}
				// Actions on extra languages
				if (!$error && empty($conf->global->MAIN_EXTRALANGUAGES_DISABLED)) { // For avoid conflicts if trigger used
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
			dol_syslog(get_class($this)."::Update fails verify ".join(',', $this->errors), LOG_WARNING);
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
	 *    @return   int						>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $barcode = '', $idprof1 = '', $idprof2 = '', $idprof3 = '', $idprof4 = '', $idprof5 = '', $idprof6 = '', $email = '', $ref_alias = '')
	{
		global $langs;
		global $conf;

		if (empty($rowid) && empty($ref) && empty($ref_ext) && empty($barcode) && empty($idprof1) && empty($idprof2) && empty($idprof3) && empty($idprof4) && empty($idprof5) && empty($idprof6) && empty($email)) {
			return -1;
		}

		$sql = 'SELECT s.rowid, s.nom as name, s.name_alias, s.entity, s.ref_ext, s.address, s.datec as date_creation, s.prefix_comm';
		$sql .= ', s.status';
		$sql .= ', s.price_level';
		$sql .= ', s.tms as date_modification, s.fk_user_creat, s.fk_user_modif';
		$sql .= ', s.phone, s.fax, s.email';
		$sql .= ', s.socialnetworks';
		$sql .= ', s.url, s.zip, s.town, s.note_private, s.note_public, s.model_pdf, s.client, s.fournisseur';
		$sql .= ', s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6';
		$sql .= ', s.capital, s.tva_intra';
		$sql .= ', s.fk_typent as typent_id';
		$sql .= ', s.fk_effectif as effectif_id';
		$sql .= ', s.fk_forme_juridique as forme_juridique_code';
		$sql .= ', s.webservices_url, s.webservices_key';
		$sql .= ', s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.parent, s.barcode';
		$sql .= ', s.fk_departement as state_id, s.fk_pays as country_id, s.fk_stcomm, s.remise_supplier, s.mode_reglement, s.cond_reglement, s.transport_mode';
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
		$sql .= ', st.libelle as stcomm, st.picto as stcomm_picto';
		$sql .= ', te.code as typent_code';
		$sql .= ', i.libelle as label_incoterms';
		$sql .= ', sr.remise_client, model_pdf';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as e ON s.fk_effectif = e.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as fj ON s.fk_forme_juridique = fj.code';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as te ON s.fk_typent = te.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON s.fk_incoterms = i.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe_remise as sr ON sr.rowid = (SELECT MAX(rowid) FROM '.MAIN_DB_PREFIX.'societe_remise WHERE fk_soc = s.rowid AND entity IN ('.getEntity('discount').'))';

		$sql .= ' WHERE s.entity IN ('.getEntity($this->element).')';
		if ($rowid) {
			$sql .= ' AND s.rowid = '.$rowid;
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
				$this->user_creation     = $obj->fk_user_creat;
				$this->user_modification = $obj->fk_user_modif;

				$this->address = $obj->address;
				$this->zip 			= $obj->zip;
				$this->town 		= $obj->town;

				$this->country_id   = $obj->country_id;
				$this->country_code = $obj->country_id ? $obj->country_code : '';
				$this->country = $obj->country_id ? ($langs->transnoentities('Country'.$obj->country_code) != 'Country'.$obj->country_code ? $langs->transnoentities('Country'.$obj->country_code) : $obj->country) : '';

				$this->state_id     = $obj->state_id;
				$this->state_code   = $obj->state_code;
				$this->state        = ($obj->state != '-' ? $obj->state : '');

				$transcode = $langs->trans('StatusProspect'.$obj->fk_stcomm);
				$label = ($transcode != 'StatusProspect'.$obj->fk_stcomm ? $transcode : $obj->stcomm);
				$this->stcomm_id = $obj->fk_stcomm; // id status prospect
				$this->status_prospect_label = $label; // label status prospect
				$this->stcomm_picto = $obj->stcomm_picto; // picto statut commercial

				$this->email = $obj->email;
				$this->socialnetworks = (array) json_decode($obj->socialnetworks, true);

				$this->url = $obj->url;
				$this->phone = $obj->phone;
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

				$this->code_compta = $obj->code_compta;
				$this->code_compta_fournisseur = $obj->code_compta_fournisseur;

				$this->barcode = $obj->barcode;

				$this->tva_assuj      = $obj->tva_assuj;
				$this->tva_intra      = $obj->tva_intra;
				$this->status = $obj->status;

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
				$this->modelpdf = $obj->model_pdf; // deprecated
				$this->default_lang = $obj->default_lang;
				$this->logo = $obj->logo;
				$this->logo_squarred = $obj->logo_squarred;

				$this->webservices_url = $obj->webservices_url;
				$this->webservices_key = $obj->webservices_key;

				$this->outstanding_limit		= $obj->outstanding_limit;
				$this->order_min_amount			= $obj->order_min_amount;
				$this->supplier_order_min_amount = $obj->supplier_order_min_amount;

				// multiprix
				$this->price_level = $obj->price_level;

				$this->import_key = $obj->import_key;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;

				// multicurrency
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->model_pdf = $obj->model_pdf;

				$result = 1;

				// fetch optionals attributes and labels
				$this->fetch_optionals();
			} else {
				$result = 0;
			}

			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			$result = -3;
		}

		// Use first price level if level not defined for third party
		if ((!empty($conf->global->PRODUIT_MULTIPRICES) || !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) && empty($this->price_level)) {
			$this->price_level = 1;
		}

		return $result;
	}

	/**
	 *    Delete a third party from database and all its dependencies (contacts, rib...)
	 *
	 *    @param	int		$id             Id of third party to delete
	 *    @param    User    $fuser          User who ask to delete thirdparty
	 *    @param    int		$call_trigger   0=No, 1=yes
	 *    @return	int						<0 if KO, 0 if nothing done, >0 if OK
	 */
	public function delete($id, User $fuser = null, $call_trigger = 1)
	{
		global $langs, $conf, $user;

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
				if ($this->client || $this->prospect) {
					$toute_categs['customer'] = $static_cat->containing($this->id, Categorie::TYPE_CUSTOMER);
				}
				if ($this->fournisseur) {
					$toute_categs['supplier'] = $static_cat->containing($this->id, Categorie::TYPE_SUPPLIER);
				}

				// Remove each "Categorie"
				foreach ($toute_categs as $type => $categs_type) {
					foreach ($categs_type as $cat) {
						$cat->del_type($this, $type);
					}
				}
			}

			if (!$error) {
				foreach ($this->childtablesoncascade as $tabletodelete) {
					$deleteFromObject = explode(':', $tabletodelete);
					if (count($deleteFromObject) >= 2) {
						$className = str_replace('@', '', $deleteFromObject[0]);
						$filepath = $deleteFromObject[1];
						$columnName = $deleteFromObject[2];
						if (dol_include_once($filepath)) {
							$child_object = new $className($this->db);
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
						$sql .= " WHERE fk_soc = ".$id;
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
				$sql .= " WHERE parent = ".$id;
				if (!$this->db->query($sql)) {
					$error++;
					$this->errors[] = $this->db->lasterror();
				}
			}

			// Remove third party
			if (!$error) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe";
				$sql .= " WHERE rowid = ".$id;
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
			dol_syslog("Can't remove thirdparty with id ".$id.". There is ".$objectisused." childs", LOG_WARNING);
		}
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Define third party as a customer
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	public function set_as_client()
	{
		// phpcs:enable
		if ($this->id) {
			$newclient = 1;
			if ($this->client == 2 || $this->client == 3) {
				$newclient = 3; //If prospect, we keep prospect tag
			}
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " SET client = ".$newclient;
			$sql .= " WHERE rowid = ".$this->id;

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
	 *	@return	int					<0 if KO, >0 if OK
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
			$sql .= " WHERE rowid = ".$this->id;
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -1;
			}

			// Writes trace in discount history
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise";
			$sql .= " (entity, datec, fk_soc, remise_client, note, fk_user_author)";
			$sql .= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', ".$this->id.", '".$this->db->escape($remise)."',";
			$sql .= " '".$this->db->escape($note)."',";
			$sql .= " ".$user->id;
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
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Defines the company as a customer
	 *
	 *  @param	float	$remise		Value in % of the discount
	 *  @param  string	$note		Note/Reason for changing the discount
	 *  @param  User	$user		User who sets the discount
	 *	@return	int					<0 if KO, >0 if OK
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
			$sql .= " WHERE rowid = ".$this->id;
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->db->rollback();
				$this->error = $this->db->error();
				return -1;
			}

			// Writes trace in discount history
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_supplier";
			$sql .= " (entity, datec, fk_soc, remise_supplier, note, fk_user_author)";
			$sql .= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', ".$this->id.", '".$this->db->escape($remise)."',";
			$sql .= " '".$this->db->escape($note)."',";
			$sql .= " ".$user->id;
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
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Add a discount for third party
	 *
	 *    	@param	float	$remise     	Amount of discount
	 *    	@param  User	$user       	User adding discount
	 *    	@param  string	$desc			Reason of discount
	 *      @param  string	$vatrate     	VAT rate (may contain the vat code too). Exemple: '1.23', '1.23 (ABC)', ...
	 *      @param	int		$discount_type	0 => customer discount, 1 => supplier discount
	 *		@return	int						<0 if KO, id of discount record if OK
	 */
	public function set_remise_except($remise, User $user, $desc, $vatrate = '', $discount_type = 0)
	{
		// phpcs:enable
		global $langs;

		// Clean parameters
		$remise = price2num($remise);
		$desc = trim($desc);

		// Check parameters
		if (!$remise > 0) {
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

			$discount->discount_type = $discount_type;

			$discount->amount_ht = $discount->multicurrency_amount_ht = price2num($remise, 'MT');
			$discount->amount_tva = $discount->multicurrency_amount_tva = price2num($remise * $vatrate / 100, 'MT');
			$discount->amount_ttc = $discount->multicurrency_amount_ttc = price2num($discount->amount_ht + $discount->amount_tva, 'MT');

			$discount->tva_tx = price2num($vatrate);
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
	 *	@param	User	$user			Filter on a user author of discounts
	 * 	@param	string	$filter			Other filter
	 * 	@param	integer	$maxvalue		Filter on max value for discount
	 * 	@param	int		$discount_type	0 => customer discount, 1 => supplier discount
	 *	@return	int					<0 if KO, Credit note amount otherwise
	 */
	public function getAvailableDiscounts($user = '', $filter = '', $maxvalue = 0, $discount_type = 0)
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
	 *  @param	User		$user			Object user
	 *  @param	int			$mode			0=Array with properties, 1=Array of id.
	 *  @param	string		$sortfield		List of sort fields, separated by comma. Example: 't1.fielda,t2.fieldb'
	 *  @param	string		$sortorder		Sort order, separated by comma. Example: 'ASC,DESC';
	 *  @return array       				Array of sales representatives of third party
	 */
	public function getSalesRepresentatives(User $user, $mode = 0, $sortfield = null, $sortorder = null)
	{
		global $conf;

		$reparray = array();

		$sql = "SELECT DISTINCT u.rowid, u.login, u.lastname, u.firstname, u.office_phone, u.job, u.email, u.statut as status, u.entity, u.photo";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."user as u";
		if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
			$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
			$sql .= " WHERE ((ug.fk_user = sc.fk_user";
			$sql .= " AND ug.entity = ".$conf->entity.")";
			$sql .= " OR u.admin = 1)";
		} else {
			$sql .= " WHERE entity in (0, ".$conf->entity.")";
		}

		$sql .= " AND u.rowid = sc.fk_user AND sc.fk_soc = ".$this->id;
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
					$reparray[$i]['job'] = $obj->job;
					$reparray[$i]['statut'] = $obj->status; // deprecated
					$reparray[$i]['status'] = $obj->status;
					$reparray[$i]['entity'] = $obj->entity;
					$reparray[$i]['login'] = $obj->login;
					$reparray[$i]['photo'] = $obj->photo;
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Set the price level
	 *
	 * @param 	int		$price_level	Level of price
	 * @param 	User	$user			Use making change
	 * @return	int						<0 if KO, >0 if OK
	 */
	public function set_price_level($price_level, User $user)
	{
		// phpcs:enable
		if ($this->id) {
			$now = dol_now();

			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " SET price_level = '".$this->db->escape($price_level)."'";
			$sql .= " WHERE rowid = ".$this->id;

			if (!$this->db->query($sql)) {
				dol_print_error($this->db);
				return -1;
			}

			$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_prices";
			$sql .= " (datec, fk_soc, price_level, fk_user_author)";
			$sql .= " VALUES ('".$this->db->idate($now)."', ".$this->id.", '".$this->db->escape($price_level)."', ".$user->id.")";

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
	 *	@return	int					<=0 if KO, >0 if OK
	 */
	public function add_commercial(User $user, $commid)
	{
		// phpcs:enable
		$error = 0;

		if ($this->id > 0 && $commid > 0) {
			$this->db->begin();

			if (!$error) {
				$sql = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux";
				$sql .= " WHERE fk_soc = ".$this->id." AND fk_user =".$commid;

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_syslog(get_class($this)."::add_commercial Error ".$this->db->lasterror());
					$error++;
				}
			}

			if (!$error) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux";
				$sql .= " (fk_soc, fk_user)";
				$sql .= " VALUES (".$this->id.", ".$commid.")";

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_syslog(get_class($this)."::add_commercial Error ".$this->db->lasterror());
					$error++;
				}
			}

			if (!$error) {
				$this->context = array('commercial_modified'=>$commid);

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
	 *	@return	void
	 */
	public function del_commercial(User $user, $commid)
	{
		// phpcs:enable
		$error = 0;
		$this->context = array('commercial_modified'=>$commid);

		$result = $this->call_trigger('COMPANY_UNLINK_SALE_REPRESENTATIVE', $user);
		if ($result < 0) {
			$error++;
		}

		if ($this->id > 0 && $commid > 0) {
			$sql  = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux ";
			$sql .= " WHERE fk_soc = ".$this->id." AND fk_user =".$commid;

			if (!$this->db->query($sql)) {
				dol_syslog(get_class($this)."::del_commercial Erreur");
			}
		}
	}


	/**
	 *    	Return a link on thirdparty (with picto)
	 *
	 *		@param	int		$withpicto		          Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
	 *		@param	string	$option			          Target of link ('', 'customer', 'prospect', 'supplier', 'project')
	 *		@param	int		$maxlen			          Max length of name
	 *      @param	int  	$notooltip		          1=Disable tooltip
	 *      @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *		@return	string					          String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlen = 0, $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$name = $this->name ? $this->name : $this->nom;

		if (!empty($conf->global->SOCIETE_ON_SEARCH_AND_LIST_GO_ON_CUSTOMER_OR_SUPPLIER_CARD)) {
			if (empty($option) && $this->client > 0) {
				$option = 'customer';
			}
			if (empty($option) && $this->fournisseur > 0) {
				$option = 'supplier';
			}
		}

		if (!empty($conf->global->SOCIETE_ADD_REF_IN_LIST) && (!empty($withpicto))) {
			$code = '';
			if (($this->client) && (!empty($this->code_client)) && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1 || $conf->global->SOCIETE_ADD_REF_IN_LIST == 2)) {
				$code = $this->code_client.' - ';
			}

			if (($this->fournisseur) && (!empty($this->code_fournisseur)) && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1 || $conf->global->SOCIETE_ADD_REF_IN_LIST == 3)) {
				$code .= $this->code_fournisseur.' - ';
			}

			if ($code) {
				if ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1) {
					$name = $code.' '.$name;
				} else {
					$name = $code;
				}
			}
		}

		if (!empty($this->name_alias)) {
			$name .= ' ('.$this->name_alias.')';
		}

		$result = ''; $label = '';
		$linkstart = ''; $linkend = '';

		if (!empty($this->logo) && class_exists('Form')) {
			$label .= '<div class="photointooltip">';
			$label .= Form::showphoto('societe', $this, 0, 40, 0, '', 'mini', 0); // Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$label .= '</div><div style="clear: both;"></div>';
		} elseif (!empty($this->logo_squarred) && class_exists('Form')) {
			/*$label.= '<div class="photointooltip">';
			$label.= Form::showphoto('societe', $this, 0, 40, 0, 'photowithmargin', 'mini', 0);	// Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$label.= '</div><div style="clear: both;"></div>';*/
		}

		$label .= '<div class="centpercent">';

		if ($option == 'customer' || $option == 'compta' || $option == 'category') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Customer").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
		} elseif ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Prospect").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
		} elseif ($option == 'supplier' || $option == 'category_supplier') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Supplier").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$this->id;
		} elseif ($option == 'agenda') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/agenda.php?socid='.$this->id;
		} elseif ($option == 'project') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/project.php?socid='.$this->id;
		} elseif ($option == 'margin') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/margin/tabs/thirdpartyMargins.php?socid='.$this->id.'&type=1';
		} elseif ($option == 'contact') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/contact.php?socid='.$this->id;
		} elseif ($option == 'ban') {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$this->id;
		}

		// By default
		if (empty($linkstart)) {
			$label .= img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("ThirdParty").'</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$this->id;
		}
		if (isset($this->status)) {
			$label .= ' '.$this->getLibStatut(5);
		}

		if (!empty($this->name)) {
			$label .= '<br><b>'.$langs->trans('Name').':</b> '.dol_escape_htmltag($this->name);
			if (!empty($this->name_alias)) {
				$label .= ' ('.dol_escape_htmltag($this->name_alias).')';
			}
		}
		$label .= '<br><b>'.$langs->trans('Email').':</b> '.$this->email;
		if (!empty($this->country_code)) {
			$label .= '<br><b>'.$langs->trans('Country').':</b> '.$this->country_code;
		}
		if (!empty($this->tva_intra) || (!empty($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP) && strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'vatnumber') !== false)) {
			$label .= '<br><b>'.$langs->trans('VATIntra').':</b> '.dol_escape_htmltag($this->tva_intra);
		}
		if (!empty($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP)) {
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid1') !== false) {
				$label .= '<br><b>'.$langs->trans('ProfId1'.$this->country_code).':</b> '.$this->idprof1;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid2') !== false) {
				$label .= '<br><b>'.$langs->trans('ProfId2'.$this->country_code).':</b> '.$this->idprof2;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid3') !== false) {
				$label .= '<br><b>'.$langs->trans('ProfId3'.$this->country_code).':</b> '.$this->idprof3;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid4') !== false) {
				$label .= '<br><b>'.$langs->trans('ProfId4'.$this->country_code).':</b> '.$this->idprof4;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid5') !== false) {
				$label .= '<br><b>'.$langs->trans('ProfId5'.$this->country_code).':</b> '.$this->idprof5;
			}
			if (strpos($conf->global->SOCIETE_SHOW_FIELD_IN_TOOLTIP, 'profid6') !== false) {
				$label .= '<br><b>'.$langs->trans('ProfId6'.$this->country_code).':</b> '.$this->idprof6;
			}
		}
		if (!empty($this->code_client) && ($this->client == 1 || $this->client == 3)) {
			$label .= '<br><b>'.$langs->trans('CustomerCode').':</b> '.$this->code_client;
		}
		if (!empty($this->code_fournisseur) && $this->fournisseur) {
			$label .= '<br><b>'.$langs->trans('SupplierCode').':</b> '.$this->code_fournisseur;
		}
		if (!empty($conf->accounting->enabled) && ($this->client == 1 || $this->client == 3)) {
			$label .= '<br><b>'.$langs->trans('CustomerAccountancyCode').':</b> '.($this->code_compta ? $this->code_compta : $this->code_compta_client);
		}
		if (!empty($conf->accounting->enabled) && $this->fournisseur) {
			$label .= '<br><b>'.$langs->trans('SupplierAccountancyCode').':</b> '.$this->code_compta_fournisseur;
		}
		$label .= '</div>';

		// Add type of canvas
		$linkstart .= (!empty($this->canvas) ? '&canvas='.$this->canvas : '');
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$linkstart .= '&save_lastsearch_values=1';
		}
		$linkstart .= '"';

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowCompany");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip refurl"';

			/*
			$hookmanager->initHooks(array('thirdpartydao'));
			$parameters=array('id'=>$this->id);
			$reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			*/
		}
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		global $user;
		if (!$user->rights->societe->client->voir && $user->socid > 0 && $this->id != $user->socid) {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= dol_escape_htmltag($maxlen ? dol_trunc($name, $maxlen) : $name);
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('thirdpartydao'));
		$parameters = array(
			'id'=>$this->id,
			'getnomurl'=>$result,
			'withpicto '=> $withpicto,
			'option'=> $option,
			'maxlen'=> $maxlen,
			'notooltip'=> $notooltip,
			'save_lastsearch_value'=> $save_lastsearch_value
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
	 *    Return label of status (activity, closed)
	 *
	 *    @param  	int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *    @return   string     	   		Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status         Status id
	 *  @param	int		$mode           0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
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
			$this->labelStatus[0] = $langs->trans("ActivityCeased");
			$this->labelStatus[1] = $langs->trans("InActivity");
			$this->labelStatusShort[0] = $langs->trans("ActivityCeased");
			$this->labelStatusShort[1] = $langs->trans("InActivity");
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of contacts emails existing for third party
	 *
	 *	  @param	  int		$addthirdparty		1=Add also a record for thirdparty email
	 *    @return     array       					Array of contacts emails
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
			$contact_emails['thirdparty'] = $langs->transnoentitiesnoconv("ThirdParty").': '.dol_trunc($this->name, 16)." <".$this->email.">";
		}
		//var_dump($contact_emails)
		return $contact_emails;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return list of contacts mobile phone existing for third party
	 *
	 *    @return     array       Array of contacts emails
	 */
	public function thirdparty_and_contact_phone_array()
	{
		// phpcs:enable
		global $langs;

		$contact_phone = $this->contact_property_array('mobile');

		if (!empty($this->phone)) {	// If a phone of thirdparty is defined, we add it ot mobile of contacts
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
	 *  @param	string	$mode       		'email' or 'mobile'
	 * 	@param	int		$hidedisabled		1=Hide contact if disabled
	 *  @return array       				Array of contacts emails or mobile. Example: array(id=>'Name <email>')
	 */
	public function contact_property_array($mode = 'email', $hidedisabled = 0)
	{
		// phpcs:enable
		global $langs;

		$contact_property = array();


		$sql = "SELECT rowid, email, statut as status, phone_mobile, lastname, poste, firstname";
		$sql .= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql .= " WHERE fk_soc = ".$this->id;
		$sql .= " ORDER BY lastname, firstname";

		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);
			if ($nump) {
				$sepa = "("; $sepb = ")";
				if ($mode == 'email') {
					//$sepa="&lt;"; $sepb="&gt;";
					$sepa = "<"; $sepb = ">";
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
	 *    Returns the contact list of this company
	 *
	 *    @return     array      array of contacts
	 */
	public function contact_array()
	{
		// phpcs:enable
		$contacts = array();

		$sql = "SELECT rowid, lastname, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$this->id;
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
	 *    Returns the contact list of this company
	 *
	 *    @return    array    $contacts    array of contacts
	 */
	public function contact_array_objects()
	{
		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$contacts = array();

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$this->id;
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
		$bac->fetch(0, $this->id);

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
	 * @return     array|int        0 if KO, Array of CompanyBanckAccount if OK
	 */
	public function get_all_rib()
	{
		// phpcs:enable
		require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_rib WHERE type='ban' AND fk_soc = ".$this->id;
		$result = $this->db->query($sql);
		if (!$result) {
			$this->error++;
			$this->errors[] = $this->db->lasterror;
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
	public function get_codeclient($objsoc = 0, $type = 0)
	{
		// phpcs:enable
		global $conf;
		if (!empty($conf->global->SOCIETE_CODECLIENT_ADDON)) {
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			$mod = new $module();

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
	public function get_codefournisseur($objsoc = 0, $type = 1)
	{
		// phpcs:enable
		global $conf;
		if (!empty($conf->global->SOCIETE_CODECLIENT_ADDON)) {
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}
			$mod = new $module();

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
		if (!empty($conf->global->SOCIETE_CODECLIENT_ADDON)) {
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module();

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
		if (!empty($conf->global->SOCIETE_CODECLIENT_ADDON)) {
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module();

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
		if (!empty($conf->global->SOCIETE_CODECLIENT_ADDON)) {
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module();

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
		if (!empty($conf->global->SOCIETE_CODECLIENT_ADDON)) {
			$module = $conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$module.'.php');
				if ($res) {
					break;
				}
			}

			$mod = new $module();

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

		if (!empty($conf->global->SOCIETE_CODECOMPTA_ADDON)) {
			$res = false;
			$dirsociete = array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot) {
				$res = dol_include_once($dirroot.$conf->global->SOCIETE_CODECOMPTA_ADDON.'.php');
				if ($res) {
					break;
				}
			}

			if ($res) {
				$classname = $conf->global->SOCIETE_CODECOMPTA_ADDON;
				$mod = new $classname;

				// Set code count in $mod->code
				$result = $mod->get_code($this->db, $this, $type);

				if ($type == 'customer') {
					$this->code_compta = $mod->code;
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
				$this->code_compta = '';
			} elseif ($type == 'supplier') {
				$this->code_compta_fournisseur = '';
			}

			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Define parent commany of current company
	 *
	 *    @param	int		$id     Id of thirdparty to set or '' to remove
	 *    @return	int     		<0 if KO, >0 if OK
	 */
	public function set_parent($id)
	{
		// phpcs:enable
		if ($this->id) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " SET parent = ".($id > 0 ? $id : "null");
			$sql .= " WHERE rowid = ".$this->id;
			dol_syslog(get_class($this).'::set_parent', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->parent = $id;
				return 1;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Returns if a profid sould be verified to be unique
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
				$ret = (empty($conf->global->SOCIETE_IDPROF1_UNIQUE) ? false : true);
				break;
			case 2:
				$ret = (empty($conf->global->SOCIETE_IDPROF2_UNIQUE) ? false : true);
				break;
			case 3:
				$ret = (empty($conf->global->SOCIETE_IDPROF3_UNIQUE) ? false : true);
				break;
			case 4:
				$ret = (empty($conf->global->SOCIETE_IDPROF4_UNIQUE) ? false : true);
				break;
			case 5:
				$ret = (empty($conf->global->SOCIETE_IDPROF5_UNIQUE) ? false : true);
				break;
			case 6:
				$ret = (empty($conf->global->SOCIETE_IDPROF6_UNIQUE) ? false : true);
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
		$sql = "SELECT COUNT(*) as idprof FROM ".MAIN_DB_PREFIX."societe WHERE ".$field." = '".$this->db->escape($value)."' AND entity IN (".getEntity('societe').")";
		if ($socid) {
			$sql .= " AND rowid <> ".$socid;
		}
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$count = $obj->idprof;
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
	 *  @param	int			$idprof         1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *  @param  Societe		$soc            Objet societe
	 *  @return int             			<=0 if KO, >0 if OK
	 *  TODO better to have this in a lib than into a business class
	 */
	public function id_prof_check($idprof, $soc)
	{
		// phpcs:enable
		global $conf;

		$ok = 1;

		if (!empty($conf->global->MAIN_DISABLEPROFIDRULES)) {
			return 1;
		}

		// Check SIREN if country FR
		if ($idprof == 1 && $soc->country_code == 'FR') {
			$chaine = trim($this->idprof1);
			$chaine = preg_replace('/(\s)/', '', $chaine);

			if (!is_numeric($chaine)) {
				return -1;
			}
			if (dol_strlen($chaine) != 9) {
				return -1;
			}

			// on prend chaque chiffre un par un
			// si son index (position dans la chaîne en commence à 0 au premier caractère) est impair
			// on double sa valeur et si cette dernière est supérieure à 9, on lui retranche 9
			// on ajoute cette valeur à la somme totale
			$sum = 0;
			for ($index = 0; $index < 9; $index++) {
				$number = (int) $chaine[$index];
				if (($index % 2) != 0) {
					if (($number *= 2) > 9) {
						$number -= 9;
					}
				}
				$sum += $number;
			}

			// le numéro est valide si la somme des chiffres est multiple de 10
			if (($sum % 10) != 0) {
				return -1;
			}
		}

		// Verifie SIRET si pays FR
		if ($idprof == 2 && $soc->country_code == 'FR') {
			$chaine = trim($this->idprof2);
			$chaine = preg_replace('/(\s)/', '', $chaine);

			if (!is_numeric($chaine)) {
				return -1;
			}
			if (dol_strlen($chaine) != 14) {
				return -1;
			}

			// on prend chaque chiffre un par un
			// si son index (position dans la chaîne en commence à 0 au premier caractère) est pair
			// on double sa valeur et si cette dernière est supérieure à 9, on lui retranche 9
			// on ajoute cette valeur à la somme totale
			$sum = 0;
			for ($index = 0; $index < 14; $index++) {
				$number = (int) $chaine[$index];
				if (($index % 2) == 0) {
					if (($number *= 2) > 9) {
						$number -= 9;
					}
				}
				$sum += $number;
			}

			// le numéro est valide si la somme des chiffres est multiple de 10
			if (($sum % 10) != 0) {
				return -1;
			}
		}

		//Verify CIF/NIF/NIE if pays ES
		//Returns: 1 if NIF ok, 2 if CIF ok, 3 if NIE ok, -1 if NIF bad, -2 if CIF bad, -3 if NIE bad, 0 if unexpected bad
		if ($idprof == 1 && $soc->country_code == 'ES') {
			$string = trim($this->idprof1);
			$string = preg_replace('/(\s)/', '', $string);
			$string = strtoupper($string);

			//Check format
			if (!preg_match('/((^[A-Z]{1}[0-9]{7}[A-Z0-9]{1}$|^[T]{1}[A-Z0-9]{8}$)|^[0-9]{8}[A-Z]{1}$)/', $string)) {
				return 0;
			}

			$num = array();
			for ($i = 0; $i < 9; $i++) {
				$num[$i] = substr($string, $i, 1);
			}

			//Check NIF
			if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $string)) {
				if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($string, 0, 8) % 23, 1)) {
					return 1;
				} else {
					return -1;
				}
			}

			//algorithm checking type code CIF
			$sum = $num[2] + $num[4] + $num[6];
			for ($i = 1; $i < 8; $i += 2) {
				$sum += intval(substr((2 * $num[$i]), 0, 1)) + intval(substr((2 * $num[$i]), 1, 1));
			}
			$n = 10 - substr($sum, strlen($sum) - 1, 1);

			//Chek special NIF
			if (preg_match('/^[KLM]{1}/', $string)) {
				if ($num[8] == chr(64 + $n) || $num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($string, 1, 8) % 23, 1)) {
					return 1;
				} else {
					return -1;
				}
			}

			//Check CIF
			if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $string)) {
				if ($num[8] == chr(64 + $n) || $num[8] == substr($n, strlen($n) - 1, 1)) {
					return 2;
				} else {
					return -2;
				}
			}

			//Check NIE T
			if (preg_match('/^[T]{1}/', $string)) {
				if ($num[8] == preg_match('/^[T]{1}[A-Z0-9]{8}$/', $string)) {
					return 3;
				} else {
					return -3;
				}
			}

			//Check NIE XYZ
			if (preg_match('/^[XYZ]{1}/', $string)) {
				if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr(str_replace(array('X', 'Y', 'Z'), array('0', '1', '2'), $string), 0, 8) % 23, 1)) {
					return 3;
				} else {
					return -3;
				}
			}

			//Can not be verified
			return -4;
		}

		//Verify NIF if country is PT
		//Returns: 1 if NIF ok, -1 if NIF bad, 0 if unexpected bad
		if ($idprof == 1 && $soc->country_code == 'PT') {
			$string = trim($this->idprof1);
			$string = preg_replace('/(\s)/', '', $string);

			//Check NIF
			if (preg_match('/(^[0-9]{9}$)/', $string)) {
				return 1;
			} else {
				return -1;
			}
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
		$parameters = array('idprof'=>$idprof, 'company'=>$thirdparty);
		$reshook = $hookmanager->executeHooks('getIdProfUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			if (!empty($conf->global->MAIN_DISABLEPROFIDRULES)) {
				return '';
			}

			// TODO Move links to validate professional ID into a dictionary table "country" + "link"
			$strippedIdProf1 = str_replace(' ', '', $thirdparty->idprof1);
			if ($idprof == 1 && $thirdparty->country_code == 'FR') {
				$url = 'http://www.societe.com/cgi-bin/search?champs='.$strippedIdProf1; // See also http://avis-situation-sirene.insee.fr/
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
			if ($idprof == 1 && $thirdparty->country_code == 'PT') {
				$url = 'http://www.nif.pt/'.$strippedIdProf1;
			}

			if ($url) {
				return '<a target="_blank" href="'.$url.'">'.$langs->trans("Check").'</a>';
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
		$sql = 'SELECT COUNT(*) as numproj FROM '.MAIN_DB_PREFIX.'projet WHERE fk_soc = '.$this->id;
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
		$sql = "SELECT s.rowid, s.nom as name, s.datec as date_creation, tms as date_modification,";
		$sql .= " fk_user_creat, fk_user_modif";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE s.rowid = ".$id;

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}

				$this->ref = $obj->name;
				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return if third party is a company (Business) or an end user (Consumer)
	 *
	 *  @return    boolean     true=is a company, false=a and user
	 */
	public function isACompany()
	{
		global $conf;

		// Define if third party is treated as company (or not) when nature is unknown
		$isacompany = empty($conf->global->MAIN_UNKNOWN_CUSTOMERS_ARE_COMPANIES) ? 0 : 1; // 0 by default
		if (!empty($this->tva_intra)) {
			$isacompany = 1;
		} elseif (!empty($this->idprof1) || !empty($this->idprof2) || !empty($this->idprof3) || !empty($this->idprof4) || !empty($this->idprof5) || !empty($this->idprof6)) {
			$isacompany = 1;
		} elseif (!empty($this->typent_code) && $this->typent_code != 'TE_UNKNOWN') {
			// TODO Add a field is_a_company into dictionary
			if (preg_match('/^TE_PRIVATE/', $this->typent_code)) {
				$isacompany = 0;
			} else {
				$isacompany = 1;
			}
		}

		return $isacompany;
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
			$sql .= " VALUES (".$categorie_id.", ".$this->id.")";

			if ($resql = $this->db->query($sql)) {
				return 0;
			}
		} else {
			return 0;
		}
		return -1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Create a third party into database from a member object
	 *
	 *  @param	Adherent	$member			Object member
	 * 	@param	string		$socname		Name of third party to force
	 *	@param	string		$socalias		Alias name of third party to force
	 *  @param	string		$customercode	Customer code
	 *  @return int							<0 if KO, id of created account if OK
	 */
	public function create_from_member(Adherent $member, $socname = '', $socalias = '', $customercode = '')
	{
        // phpcs:enable
		global $conf, $user, $langs;

		dol_syslog(get_class($this)."::create_from_member", LOG_DEBUG);

		$name = $socname ? $socname : $member->societe;
		if (empty($name)) {
			$name = $member->getFullName($langs);
		}

		$alias = $socalias ? $socalias : '';

		// Positionne parametres
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
		$this->code_fournisseur = -1;

		$this->db->begin();

		// Cree et positionne $this->id
		$result = $this->create($user);
		if ($result >= 0) {
			// Auto-create contact on thirdparty creation
			if (!empty($conf->global->THIRDPARTY_DEFAULT_CREATE_CONTACT)) {
				// Fill fields needed by contact
				$this->name_bis = $member->lastname;
				$this->firstname = $member->firstname;
				$this->civility_id = $member->civility_id;

				dol_syslog("We ask to create a contact/address too", LOG_DEBUG);
				$result = $this->create_individual($user);
				if ($result < 0)
				{
					setEventMessages($this->error, $this->errors, 'errors');
					$this->db->rollback();
					return -1;
				}
			}

			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
			$sql .= " SET fk_soc=".$this->id;
			$sql .= " WHERE rowid=".$member->id;

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
			dol_syslog(get_class($this)."::create_from_member - 2 - ".$this->error." - ".join(',', $this->errors), LOG_ERR);

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
		$this->name = empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOM;
		$this->address = empty($conf->global->MAIN_INFO_SOCIETE_ADDRESS) ? '' : $conf->global->MAIN_INFO_SOCIETE_ADDRESS;
		$this->zip = empty($conf->global->MAIN_INFO_SOCIETE_ZIP) ? '' : $conf->global->MAIN_INFO_SOCIETE_ZIP;
		$this->town = empty($conf->global->MAIN_INFO_SOCIETE_TOWN) ? '' : $conf->global->MAIN_INFO_SOCIETE_TOWN;
		$this->region_code = empty($conf->global->MAIN_INFO_SOCIETE_REGION) ? '' : $conf->global->MAIN_INFO_SOCIETE_REGION;
		$this->object = empty($conf->global->MAIN_INFO_SOCIETE_OBJECT) ? '' : $conf->global->MAIN_INFO_SOCIETE_OBJECT;

		$this->note_private = empty($conf->global->MAIN_INFO_SOCIETE_NOTE) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOTE;

		$this->nom = $this->name; // deprecated

		// We define country_id, country_code and country
		$country_id = $country_code = $country_label = '';
		if (!empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
			$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
			$country_id = $tmp[0];
			if (!empty($tmp[1])) {   // If $conf->global->MAIN_INFO_SOCIETE_COUNTRY is "id:code:label"
				$country_code = $tmp[1];
				$country_label = $tmp[2];
			} else // For backward compatibility
			{
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
		$state_id = 0; $state_code = $state_label = '';
		if (!empty($conf->global->MAIN_INFO_SOCIETE_STATE)) {
			$tmp = explode(':', $conf->global->MAIN_INFO_SOCIETE_STATE);
			$state_id = $tmp[0];
			if (!empty($tmp[1])) {   // If $conf->global->MAIN_INFO_SOCIETE_STATE is "id:code:label"
				$state_code = $tmp[1];
				$state_label = $tmp[2];
			} else { // For backward compatibility
				dol_syslog("Your state setup use an old syntax (entity=".$conf->entity."). Reedit it using setup area.", LOG_ERR);
				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
				$state_code = getState($state_id, 2, $this->db); // This need a SQL request, but it's the old feature that should not be used anymore
				$state_label = getState($state_id, 0, $this->db); // This need a SQL request, but it's the old feature that should not be used anymore
			}
		}
		$this->state_id = $state_id;
		$this->state_code = $state_code;
		$this->state = $state_label;
		if (is_object($langs)) {
			$this->state = ($langs->trans('State'.$state_code) != 'State'.$state_code) ? $langs->trans('State'.$state_code) : $state_label;
		}

		$this->phone = empty($conf->global->MAIN_INFO_SOCIETE_TEL) ? '' : $conf->global->MAIN_INFO_SOCIETE_TEL;
		$this->fax = empty($conf->global->MAIN_INFO_SOCIETE_FAX) ? '' : $conf->global->MAIN_INFO_SOCIETE_FAX;
		$this->url = empty($conf->global->MAIN_INFO_SOCIETE_WEB) ? '' : $conf->global->MAIN_INFO_SOCIETE_WEB;

		// Social networks
		$this->facebook_url = empty($conf->global->MAIN_INFO_SOCIETE_FACEBOOK_URL) ? '' : $conf->global->MAIN_INFO_SOCIETE_FACEBOOK_URL;
		$this->twitter_url = empty($conf->global->MAIN_INFO_SOCIETE_TWITTER_URL) ? '' : $conf->global->MAIN_INFO_SOCIETE_TWITTER_URL;
		$this->linkedin_url = empty($conf->global->MAIN_INFO_SOCIETE_LINKEDIN_URL) ? '' : $conf->global->MAIN_INFO_SOCIETE_LINKEDIN_URL;
		$this->instagram_url = empty($conf->global->MAIN_INFO_SOCIETE_INSTAGRAM_URL) ? '' : $conf->global->MAIN_INFO_SOCIETE_INSTAGRAM_URL;
		$this->youtube_url = empty($conf->global->MAIN_INFO_SOCIETE_YOUTUBE_URL) ? '' : $conf->global->MAIN_INFO_SOCIETE_YOUTUBE_URL;
		$this->github_url = empty($conf->global->MAIN_INFO_SOCIETE_GITHUB_URL) ? '' : $conf->global->MAIN_INFO_SOCIETE_GITHUB_URL;
		$this->socialnetworks = array();
		if (!empty($this->facebook_url)) {
			$this->socialnetworks['facebook'] = $this->facebook_url;
		}
		if (!empty($this->twitter_url)) {
			$this->socialnetworks['twitter'] = $this->twitter_url;
		}
		if (!empty($this->linkedin_url)) {
			$this->socialnetworks['linkedin'] = $this->linkedin_url;
		}
		if (!empty($this->instagram_url)) {
			$this->socialnetworks['instagram'] = $this->instagram_url;
		}
		if (!empty($this->youtube_url)) {
			$this->socialnetworks['youtube'] = $this->youtube_url;
		}
		if (!empty($this->github_url)) {
			$this->socialnetworks['github'] = $this->github_url;
		}

		// Id prof generiques
		$this->idprof1 = empty($conf->global->MAIN_INFO_SIREN) ? '' : $conf->global->MAIN_INFO_SIREN;
		$this->idprof2 = empty($conf->global->MAIN_INFO_SIRET) ? '' : $conf->global->MAIN_INFO_SIRET;
		$this->idprof3 = empty($conf->global->MAIN_INFO_APE) ? '' : $conf->global->MAIN_INFO_APE;
		$this->idprof4 = empty($conf->global->MAIN_INFO_RCS) ? '' : $conf->global->MAIN_INFO_RCS;
		$this->idprof5 = empty($conf->global->MAIN_INFO_PROFID5) ? '' : $conf->global->MAIN_INFO_PROFID5;
		$this->idprof6 = empty($conf->global->MAIN_INFO_PROFID6) ? '' : $conf->global->MAIN_INFO_PROFID6;
		$this->tva_intra = empty($conf->global->MAIN_INFO_TVAINTRA) ? '' : $conf->global->MAIN_INFO_TVAINTRA; // VAT number, not necessarly INTRA.
		$this->managers = empty($conf->global->MAIN_INFO_SOCIETE_MANAGERS) ? '' : $conf->global->MAIN_INFO_SOCIETE_MANAGERS;
		$this->capital = empty($conf->global->MAIN_INFO_CAPITAL) ? '' : $conf->global->MAIN_INFO_CAPITAL;
		$this->forme_juridique_code = empty($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE) ? '' : $conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
		$this->email = empty($conf->global->MAIN_INFO_SOCIETE_MAIL) ? '' : $conf->global->MAIN_INFO_SOCIETE_MAIL;
		$this->default_lang = (empty($conf->global->MAIN_LANG_DEFAULT) ? 'auto' : $conf->global->MAIN_LANG_DEFAULT);
		$this->logo = empty($conf->global->MAIN_INFO_SOCIETE_LOGO) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO;
		$this->logo_small = empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
		$this->logo_mini = empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;
		$this->logo_squarred = empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED;
		$this->logo_squarred_small = empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_SMALL;
		$this->logo_squarred_mini = empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI) ? '' : $conf->global->MAIN_INFO_SOCIETE_LOGO_SQUARRED_MINI;

		// Define if company use vat or not
		$this->tva_assuj = $conf->global->FACTURE_TVAOPTION;

		// Define if company use local taxes
		$this->localtax1_assuj = ((isset($conf->global->FACTURE_LOCAL_TAX1_OPTION) && ($conf->global->FACTURE_LOCAL_TAX1_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION == 'localtax1on')) ? 1 : 0);
		$this->localtax2_assuj = ((isset($conf->global->FACTURE_LOCAL_TAX2_OPTION) && ($conf->global->FACTURE_LOCAL_TAX2_OPTION == '1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION == 'localtax2on')) ? 1 : 0);
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
			'skype' => 'tom.hanson',
			'twitter' => 'tomhanson',
			'facebook' => 'tomhanson',
			'linkedin' => 'tomhanson',
		);
		$this->url = 'http://www.specimen.com';

		$this->phone = '0909090901';
		$this->fax = '0909090909';

		$this->code_client = 'CC-'.dol_print_date($now, 'dayhourlog');
		$this->code_fournisseur = 'SC-'.dol_print_date($now, 'dayhourlog');
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
			return (($obj->nb > 0) ?true:false);
		} else {
			$this->error = $this->db->lasterror();
			return false;
		}
	}

	/**
	 *	Return prostect level
	 *
	 *  @return     string        Libelle
	 */
	public function getLibProspLevel()
	{
		return $this->LibProspLevel($this->fk_prospectlevel);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of prospect level
	 *
	 *  @param	int		$fk_prospectlevel   	Prospect level
	 *  @return string        					label of level
	 */
	public function LibProspLevel($fk_prospectlevel)
	{
		// phpcs:enable
		global $langs;

		$lib = $langs->trans("ProspectLevel".$fk_prospectlevel);
		// If lib not found in language file, we get label from cache/databse
		if ($lib == $langs->trans("ProspectLevel".$fk_prospectlevel)) {
			$lib = $langs->getLabelFromKey($this->db, $fk_prospectlevel, 'c_prospectlevel', 'code', 'label');
		}
		return $lib;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set prospect level
	 *
	 *  @param  User	$user		User who sets the discount
	 *	@return	int					<0 if KO, >0 if OK
	 * @deprecated Use update function instead
	 */
	public function set_prospect_level(User $user)
	{
		// phpcs:enable
		return $this->update($this->id, $user);
	}

	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @param	string	$label		Label to use for status for added status
	 *  @return string        		Libelle
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
				return img_action($langs->trans("StatusProspect-1"), -1, $picto).' '.$langs->trans("StatusProspect-1");
			} elseif ($status == '0' || $status == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), 0, $picto).' '.$langs->trans("StatusProspect0");
			} elseif ($status == '1' || $status == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), 1, $picto).' '.$langs->trans("StatusProspect1");
			} elseif ($status == '2' || $status == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), 2, $picto).' '.$langs->trans("StatusProspect2");
			} elseif ($status == '3' || $status == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), 3, $picto).' '.$langs->trans("StatusProspect3");
			} else {
				return img_action(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label, 0, $picto).' '.(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label);
			}
		} elseif ($mode == 3) {
			if ($status == '-1' || $status == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), -1, $picto);
			} elseif ($status == '0' || $status == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), 0, $picto);
			} elseif ($status == '1' || $status == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), 1, $picto);
			} elseif ($status == '2' || $status == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), 2, $picto);
			} elseif ($status == '3' || $status == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), 3, $picto);
			} else {
				return img_action(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label, 0, $picto);
			}
		} elseif ($mode == 4) {
			if ($status == '-1' || $status == 'ST_NO') {
				return img_action($langs->trans("StatusProspect-1"), -1, $picto).' '.$langs->trans("StatusProspect-1");
			} elseif ($status == '0' || $status == 'ST_NEVER') {
				return img_action($langs->trans("StatusProspect0"), 0, $picto).' '.$langs->trans("StatusProspect0");
			} elseif ($status == '1' || $status == 'ST_TODO') {
				return img_action($langs->trans("StatusProspect1"), 1, $picto).' '.$langs->trans("StatusProspect1");
			} elseif ($status == '2' || $status == 'ST_PEND') {
				return img_action($langs->trans("StatusProspect2"), 2, $picto).' '.$langs->trans("StatusProspect2");
			} elseif ($status == '3' || $status == 'ST_DONE') {
				return img_action($langs->trans("StatusProspect3"), 3, $picto).' '.$langs->trans("StatusProspect3");
			} else {
				return img_action(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label, 0, $picto).' '.(($langs->trans("StatusProspect".$status) != "StatusProspect".$status) ? $langs->trans("StatusProspect".$status) : $label);
			}
		}

		return "Error, mode/status not found";
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Set outstanding value
	 *
	 *  @param  User	$user		User making change
	 *	@return	int					<0 if KO, >0 if OK
	 * @deprecated Use update function instead
	 */
	public function set_OutstandingBill(User $user)
	{
		// phpcs:enable
		return $this->update($this->id, $user);
	}

	/**
	 *  Return amount of order not paid and total
	 *
	 *  @param     string      $mode    'customer' or 'supplier'
	 *  @return    array				array('opened'=>Amount, 'total'=>Total amount)
	 */
	public function getOutstandingProposals($mode = 'customer')
	{
		$table = 'propal';
		if ($mode == 'supplier') {
			$table = 'supplier_proposal';
		}

		$sql  = "SELECT rowid, total_ht, total as total_ttc, fk_statut as status FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ".$this->id;
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('supplier_proposal').")";
		} else {
			$sql .= " AND entity IN (".getEntity('propal').")";
		}

		dol_syslog("getOutstandingProposals", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			while ($obj = $this->db->fetch_object($resql)) {
				$outstandingTotal += $obj->total_ht;
				$outstandingTotalIncTax += $obj->total_ttc;
				if ($obj->status != 0) {
					// Not a draft
					$outstandingOpened += $obj->total_ttc;
				}
			}
			return array('opened'=>$outstandingOpened, 'total_ht'=>$outstandingTotal, 'total_ttc'=>$outstandingTotalIncTax); // 'opened' is 'incl taxes'
		} else {
			return array();
		}
	}

	/**
	 *  Return amount of order not paid and total
	 *
	 *  @param     string      $mode    'customer' or 'supplier'
	 *  @return		array				array('opened'=>Amount, 'total'=>Total amount)
	 */
	public function getOutstandingOrders($mode = 'customer')
	{
		$table = 'commande';
		if ($mode == 'supplier') {
			$table = 'commande_fournisseur';
		}

		$sql  = "SELECT rowid, total_ht, total_ttc, fk_statut as status FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ".$this->id;
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
			while ($obj = $this->db->fetch_object($resql)) {
				$outstandingTotal += $obj->total_ht;
				$outstandingTotalIncTax += $obj->total_ttc;
				if ($obj->status != 0) {
					// Not a draft
					$outstandingOpened += $obj->total_ttc;
				}
			}
			return array('opened'=>$outstandingOpened, 'total_ht'=>$outstandingTotal, 'total_ttc'=>$outstandingTotalIncTax); // 'opened' is 'incl taxes'
		} else {
			return array();
		}
	}

	/**
	 *  Return amount of bill not paid and total
	 *
	 *  @param     string      $mode    'customer' or 'supplier'
	 *  @param     int      $late    	0 => all invoice, 1=> only late
	 *  @return		array				array('opened'=>Amount, 'total'=>Total amount)
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
		if ($mode == 'supplier') {
			$sql = "SELECT rowid, total_ht as total_ht, total_ttc, paye, type, fk_statut as status, close_code FROM ".MAIN_DB_PREFIX.$table." as f";
		} else {
			$sql = "SELECT rowid, total as total_ht, total_ttc, paye, fk_statut as status, close_code FROM ".MAIN_DB_PREFIX.$table." as f";
		}
		$sql .= " WHERE fk_soc = ".$this->id;
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
			if ($mode == 'supplier') {
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$tmpobject = new FactureFournisseur($this->db);
			} else {
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$tmpobject = new Facture($this->db);
			}
			while ($obj = $this->db->fetch_object($resql)) {
				$tmpobject->id = $obj->rowid;

				if ($obj->status != $tmpobject::STATUS_DRAFT                                           // Not a draft
					&& !($obj->status == $tmpobject::STATUS_ABANDONED && $obj->close_code == 'replaced')  // Not a replaced invoice
					) {
					$outstandingTotal += $obj->total_ht;
					$outstandingTotalIncTax += $obj->total_ttc;
				}
				if ($obj->paye == 0
					&& $obj->status != $tmpobject::STATUS_DRAFT    		// Not a draft
					&& $obj->status != $tmpobject::STATUS_ABANDONED	    // Not abandonned
					&& $obj->status != $tmpobject::STATUS_CLOSED) {		// Not classified as paid
					//$sql .= " AND (status <> 3 OR close_code <> 'abandon')";		// Not abandonned for undefined reason
					$paiement = $tmpobject->getSommePaiement();
					$creditnotes = $tmpobject->getSumCreditNotesUsed();
					$deposits = $tmpobject->getSumDepositsUsed();

					$outstandingOpened += $obj->total_ttc - $paiement - $creditnotes - $deposits;
				}

				//if credit note is converted but not used
				// TODO Do this also for customer ?
				if ($mode == 'supplier' && $obj->type == FactureFournisseur::TYPE_CREDIT_NOTE && $tmpobject->isCreditNoteUsed()) {
					$outstandingOpened -= $tmpobject->getSumFromThisCreditNotesNotUsed();
				}
			}
			return array('opened'=>$outstandingOpened, 'total_ht'=>$outstandingTotal, 'total_ttc'=>$outstandingTotalIncTax); // 'opened' is 'incl taxes'
		} else {
			return array();
		}
	}

	/**
	 * Return label of status customer is prospect/customer
	 *
	 * @return   string        	Label
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
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string		$modele			Generator to use. Caller must set it to obj->model_pdf or GETPOST('model','alpha') for example.
	 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @param  null|array  $moreparams     Array to provide more information
	 *	@return int        					<0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $user, $langs;

		if (!empty($moreparams) && !empty($moreparams['use_companybankid'])) {
			$modelpath = "core/modules/bank/doc/";

			include_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			$companybankaccount = new CompanyBankAccount($this->db);
			$result = $companybankaccount->fetch($moreparams['use_companybankid']);
			if (!$result) {
				dol_print_error($this->db, $companybankaccount->error, $companybankaccount->errors);
			}
			$result = $companybankaccount->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		} else {
			// Positionne le modele sur le nom du modele a utiliser
			if (!dol_strlen($modele)) {
				if (!empty($conf->global->COMPANY_ADDON_PDF)) {
					$modele = $conf->global->COMPANY_ADDON_PDF;
				} else {
					print $langs->trans("Error")." ".$langs->trans("Error_COMPANY_ADDON_PDF_NotDefined");
					return 0;
				}
			}

			if (!isset($this->bank_account)) {
				require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
				$bac = new CompanyBankAccount($this->db);
				$result = $bac->fetch(0, $this->id);
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
	 * @param 	string 		$type_categ 			Category type ('customer' or 'supplier')
	 * @return	int							<0 if KO, >0 if OK
	 */
	public function setCategories($categories, $type_categ)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		// Decode type
		if (!in_array($type_categ, array(Categorie::TYPE_CUSTOMER, Categorie::TYPE_SUPPLIER))) {
			dol_syslog(__METHOD__.': Type '.$type_categ.'is an unknown company category type. Done nothing.', LOG_ERR);
			return -1;
		}

		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, $type_categ, 'id');

		// Diff
		if (is_array($existing)) {
			$to_del = array_diff($existing, $categories);
			$to_add = array_diff($categories, $existing);
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = $categories;
		}

		$error = 0;

		// Process
		foreach ($to_del as $del) {
			if ($c->fetch($del) > 0) {
				$c->del_type($this, $type_categ);
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$result = $c->add_type($this, $type_categ);
				if ($result < 0) {
					$error++;
					$this->error = $c->error;
					$this->errors = $c->errors;
					break;
				}
			}
		}

		return $error ? -1 : 1;
	}

	/**
	 * Sets sales representatives of the thirdparty
	 *
	 * @param 	int[]|int 	$salesrep	 	User ID or array of user IDs
	 * @param   bool        $onlyAdd        Only add (no delete before)
	 * @return	int							<0 if KO, >0 if OK
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
	 *    @return	int     			<0 if KO, >0 if OK
	 */
	public function setThirdpartyType($typent_id)
	{
		if ($this->id) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " SET fk_typent = ".($typent_id > 0 ? $typent_id : "null");
			$sql .= " WHERE rowid = ".$this->id;
			dol_syslog(get_class($this).'::setThirdpartyType', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->typent_id = $typent_id;
				$this->typent_code = dol_getIdFromCode($this->db, $this->$typent_id, 'c_typent', 'id', 'code');
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
	 * @param 	DoliDB 	$db 		Database handler
	 * @param 	int 	$origin_id 	Old thirdparty id (will be removed)
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool				True if success, False if error
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		if ($origin_id == $dest_id) {
			dol_syslog('Error: Try to merge a thirdparty into itself');
			return false;
		}

		/**
		 * Thirdparty commercials cannot be the same in both thirdparties so we look for them and remove some to avoid duplicate.
		 * Because this function is meant to be executed within a transaction, we won't take care of begin/commit.
		 */
		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'societe_commerciaux ';
		$sql .= ' WHERE fk_soc = '.(int) $dest_id.' AND fk_user IN ( ';
		$sql .= ' SELECT fk_user ';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe_commerciaux ';
		$sql .= ' WHERE fk_soc = '.(int) $origin_id.') ';

		$resql = $db->query($sql);
		while ($obj = $db->fetch_object($resql)) {
			$db->query('DELETE FROM '.MAIN_DB_PREFIX.'societe_commerciaux WHERE rowid = '.$obj->rowid);
		}

		/**
		 * llx_societe_extrafields table must not be here because we don't care about the old thirdparty data
		 * Do not include llx_societe because it will be replaced later
		 */
		$tables = array(
			'societe_address',
			'societe_commerciaux',
			'societe_prices',
			'societe_remise',
			'societe_remise_except',
			'societe_rib'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}
