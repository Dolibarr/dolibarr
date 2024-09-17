<?php
/* Copyright (C) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2005       Marc Barilley / Ocebo   <marc@ocebo.com>
 * Copyright (C) 2005-2014  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2010-2020  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012-2014  Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2013       Cedric Gross            <c.gross@kreiz-it.fr>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2022  Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2022       Sylvain Legrand         <contact@infras.fr>
 * Copyright (C) 2023      	Gauthier VERDOL       	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2023		Nick Fragoulis
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/compta/facture/class/facture.class.php
 *	\ingroup    invoice
 *	\brief      File of class to manage invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commoninvoice.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/factureligne.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}

/**
 *	Class to manage invoices
 */
class Facture extends CommonInvoice
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'facture';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'facture';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'facturedet';

	/**
	 * @var string Fieldname with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_facture';

	/**
	 * @var string String with name of icon for myobject.
	 */
	public $picto = 'bill';

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
	 * @var int ID
	 * @deprecated		Use $user_creation_id
	 */
	public $fk_user_author;

	/**
	 * @var int|null ID
	 * @deprecated		Use $user_validation_id
	 */
	public $fk_user_valid;

	/**
	 * @var int ID
	 * @deprecated		Use $user_modification_id
	 */
	public $fk_user_modif;

	/**
	 * @var string
	 */
	public $datem;

	/**
	 * @var null|int|''	Date expected for delivery
	 */
	public $delivery_date; // Date expected of shipment (date of start of shipment, not the reception that occurs some days after)

	/**
	 * @var string customer ref
	 * @deprecated
	 * @see $ref_customer
	 */
	public $ref_client;

	/**
	 * @var string customer ref
	 */
	public $ref_customer;

	/**
	 * @var float
	 */
	public $total_ht;
	/**
	 * @var float
	 */
	public $total_tva;
	/**
	 * @var float
	 */
	public $total_localtax1;
	/**
	 * @var float
	 */
	public $total_localtax2;
	/**
	 * @var float
	 */
	public $total_ttc;
	/**
	 * @var float
	 */
	public $revenuestamp;

	/**
	 * @var float|string
	 */
	public $resteapayer;

	/**
	 *
	 * @var int<0,1> 1 if invoice paid COMPLETELY, 0 otherwise
	 * @deprecated * Use statut and close_code)
	 */
	public $paye;

	/**
	 * @var string key of module source when invoice generated from a dedicated module ('cashdesk', 'takepos', ...)
	 */
	public $module_source;
	/**
	 * @var int key of pos source ('0', '1', ...)
	 */
	public $pos_source;
	/**
	 * @var int id of template invoice when generated from a template invoice
	 */
	public $fk_fac_rec_source;
	/**
	 * @var int id of source invoice if replacement invoice or credit note
	 */
	public $fk_facture_source;
	public $linked_objects = array();

	/**
	 * @var int ID Field to store bank id to use when payment mode is withdraw
	 */
	public $fk_bank;

	/**
	 * @var CommonInvoiceLine[]
	 */
	public $lines = array();

	/**
	 * @var FactureLigne
	 */
	public $line;
	/**
	 * @var array<string,string>  (Encoded as JSON in database)
	 */
	public $extraparams = array();

	/**
	 * @var int ID facture rec
	 */
	public $fac_rec;

	/**
	 * @var string
	 */
	public $date_pointoftax;


	/**
	 * @var int Situation cycle reference number
	 */
	public $situation_cycle_ref;

	/**
	 * @var int Situation counter inside the cycle
	 */
	public $situation_counter;

	/**
	 * @var int Final situation flag
	 */
	public $situation_final;

	/**
	 * @var Facture[] Table of previous situations
	 */
	public $tab_previous_situation_invoice = array();

	/**
	 * @var Facture[] Table of next situations
	 */
	public $tab_next_situation_invoice = array();

	/**
	 * @var static object oldcopy
	 */
	public $oldcopy;

	/**
	 * @var float percentage of retainage
	 */
	public $retained_warranty;

	/**
	 * @var int timestamp of date limit of retainage
	 */
	public $retained_warranty_date_limit;

	/**
	 * @var int Code in llx_c_paiement
	 */
	public $retained_warranty_fk_cond_reglement;

	/**
	 * @var int availability ID
	 */
	public $availability_id;

	/**
	 * @var string
	 */
	public $date_closing;

	/**
	 * @var int
	 */
	public $source;

	/**
	 * @var float	Percent of discount ("remise" in French)
	 * @deprecated The discount percent is on line level now
	 */
	public $remise_percent;

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
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>	Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 1),
		'ref' => array('type' => 'varchar(30)', 'label' => 'Ref', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'position' => 5),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'default' => '1', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'position' => 20, 'index' => 1),
		'ref_client' => array('type' => 'varchar(255)', 'label' => 'RefCustomer', 'enabled' => 1, 'visible' => -1, 'position' => 10),
		'ref_ext' => array('type' => 'varchar(255)', 'label' => 'Ref ext', 'enabled' => 1, 'visible' => 0, 'position' => 12),
		'type' => array('type' => 'smallint(6)', 'label' => 'Type', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 15),
		'subtype' => array('type' => 'smallint(6)', 'label' => 'InvoiceSubtype', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 15),
		//'increment' =>array('type'=>'varchar(10)', 'label'=>'Increment', 'enabled'=>1, 'visible'=>-1, 'position'=>45),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 50),
		'datef' => array('type' => 'date', 'label' => 'DateInvoice', 'enabled' => 1, 'visible' => 1, 'position' => 20),
		'date_valid' => array('type' => 'date', 'label' => 'DateValidation', 'enabled' => 1, 'visible' => -1, 'position' => 22),
		'date_lim_reglement' => array('type' => 'date', 'label' => 'DateDue', 'enabled' => 1, 'visible' => 1, 'position' => 25),
		'date_closing' => array('type' => 'datetime', 'label' => 'Date closing', 'enabled' => 1, 'visible' => -1, 'position' => 30),
		'paye' => array('type' => 'smallint(6)', 'label' => 'InvoicePaidCompletely', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 80),
		//'amount' =>array('type'=>'double(24,8)', 'label'=>'Amount', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>85),
		//'remise_percent' =>array('type'=>'double', 'label'=>'RelativeDiscount', 'enabled'=>1, 'visible'=>-1, 'position'=>90),
		//'remise_absolue' =>array('type'=>'double', 'label'=>'CustomerRelativeDiscount', 'enabled'=>1, 'visible'=>-1, 'position'=>91),
		//'remise' =>array('type'=>'double', 'label'=>'Remise', 'enabled'=>1, 'visible'=>-1, 'position'=>100),
		'close_code' => array('type' => 'varchar(16)', 'label' => 'EarlyClosingReason', 'enabled' => 1, 'visible' => -1, 'position' => 92),
		'close_note' => array('type' => 'varchar(128)', 'label' => 'EarlyClosingComment', 'enabled' => 1, 'visible' => -1, 'position' => 93),
		'total_ht' => array('type' => 'double(24,8)', 'label' => 'AmountHT', 'enabled' => 1, 'visible' => 1, 'position' => 95, 'isameasure' => 1),
		'total_tva' => array('type' => 'double(24,8)', 'label' => 'AmountVAT', 'enabled' => 1, 'visible' => -1, 'position' => 100, 'isameasure' => 1),
		'localtax1' => array('type' => 'double(24,8)', 'label' => 'LT1', 'enabled' => 1, 'visible' => -1, 'position' => 110, 'isameasure' => 1),
		'localtax2' => array('type' => 'double(24,8)', 'label' => 'LT2', 'enabled' => 1, 'visible' => -1, 'position' => 120, 'isameasure' => 1),
		'revenuestamp' => array('type' => 'double(24,8)', 'label' => 'RevenueStamp', 'enabled' => 1, 'visible' => -1, 'position' => 115, 'isameasure' => 1),
		'total_ttc' => array('type' => 'double(24,8)', 'label' => 'AmountTTC', 'enabled' => 1, 'visible' => 1, 'position' => 130, 'isameasure' => 1),
		'fk_facture_source' => array('type' => 'integer', 'label' => 'SourceInvoice', 'enabled' => 1, 'visible' => -1, 'position' => 170),
		'fk_projet' => array('type' => 'integer:Project:projet/class/project.class.php:1:(fk_statut:=:1)', 'label' => 'Project', 'enabled' => 1, 'visible' => -1, 'position' => 175),
		'fk_account' => array('type' => 'integer', 'label' => 'Fk account', 'enabled' => 1, 'visible' => -1, 'position' => 180),
		'fk_currency' => array('type' => 'varchar(3)', 'label' => 'CurrencyCode', 'enabled' => 1, 'visible' => -1, 'position' => 185),
		'fk_cond_reglement' => array('type' => 'integer', 'label' => 'PaymentTerm', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 190),
		'fk_mode_reglement' => array('type' => 'integer', 'label' => 'PaymentMode', 'enabled' => 1, 'visible' => -1, 'position' => 195),
		'note_private' => array('type' => 'html', 'label' => 'NotePrivate', 'enabled' => 1, 'visible' => 0, 'position' => 205),
		'note_public' => array('type' => 'html', 'label' => 'NotePublic', 'enabled' => 1, 'visible' => 0, 'position' => 210),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => 1, 'visible' => 0, 'position' => 215),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 225),
		'situation_cycle_ref' => array('type' => 'smallint(6)', 'label' => 'Situation cycle ref', 'enabled' => '$conf->global->INVOICE_USE_SITUATION', 'visible' => -1, 'position' => 230),
		'situation_counter' => array('type' => 'smallint(6)', 'label' => 'Situation counter', 'enabled' => '$conf->global->INVOICE_USE_SITUATION', 'visible' => -1, 'position' => 235),
		'situation_final' => array('type' => 'smallint(6)', 'label' => 'Situation final', 'enabled' => 'empty($conf->global->INVOICE_USE_SITUATION) ? 0 : 1', 'visible' => -1, 'position' => 240),
		'retained_warranty' => array('type' => 'double', 'label' => 'Retained warranty', 'enabled' => '$conf->global->INVOICE_USE_RETAINED_WARRANTY', 'visible' => -1, 'position' => 245),
		'retained_warranty_date_limit' => array('type' => 'date', 'label' => 'Retained warranty date limit', 'enabled' => '$conf->global->INVOICE_USE_RETAINED_WARRANTY', 'visible' => -1, 'position' => 250),
		'retained_warranty_fk_cond_reglement' => array('type' => 'integer', 'label' => 'Retained warranty fk cond reglement', 'enabled' => '$conf->global->INVOICE_USE_RETAINED_WARRANTY', 'visible' => -1, 'position' => 255),
		'fk_incoterms' => array('type' => 'integer', 'label' => 'IncotermCode', 'enabled' => '$conf->incoterm->enabled', 'visible' => -1, 'position' => 260),
		'location_incoterms' => array('type' => 'varchar(255)', 'label' => 'IncotermLabel', 'enabled' => '$conf->incoterm->enabled', 'visible' => -1, 'position' => 265),
		'date_pointoftax' => array('type' => 'date', 'label' => 'DatePointOfTax', 'enabled' => '$conf->global->INVOICE_POINTOFTAX_DATE', 'visible' => -1, 'position' => 270),
		'fk_multicurrency' => array('type' => 'integer', 'label' => 'MulticurrencyID', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 275),
		'multicurrency_code' => array('type' => 'varchar(255)', 'label' => 'Currency', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 280),
		'multicurrency_tx' => array('type' => 'double(24,8)', 'label' => 'CurrencyRate', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 285, 'isameasure' => 1),
		'multicurrency_total_ht' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountHT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 290, 'isameasure' => 1),
		'multicurrency_total_tva' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountVAT', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 291, 'isameasure' => 1),
		'multicurrency_total_ttc' => array('type' => 'double(24,8)', 'label' => 'MulticurrencyAmountTTC', 'enabled' => 'isModEnabled("multicurrency")', 'visible' => -1, 'position' => 292, 'isameasure' => 1),
		'fk_fac_rec_source' => array('type' => 'integer', 'label' => 'RecurringInvoiceSource', 'enabled' => 1, 'visible' => -1, 'position' => 305),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => 1, 'visible' => -1, 'position' => 310),
		'module_source' => array('type' => 'varchar(32)', 'label' => 'POSModule', 'enabled' => "(isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalInt('INVOICE_SHOW_POS'))", 'visible' => -1, 'position' => 315),
		'pos_source' => array('type' => 'varchar(32)', 'label' => 'POSTerminal', 'enabled' => "(isModEnabled('cashdesk') || isModEnabled('takepos') || getDolGlobalInt('INVOICE_SHOW_POS'))", 'visible' => -1, 'position' => 320),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -1, 'position' => 500),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModificationShort', 'enabled' => 1, 'visible' => -1, 'notnull' => 1, 'position' => 502),
		'fk_user_author' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => 1, 'visible' => -1, 'position' => 506),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModification', 'enabled' => 1, 'visible' => -1, 'notnull' => -1, 'position' => 508),
		'fk_user_valid' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserValidation', 'enabled' => 1, 'visible' => -1, 'position' => 510),
		'fk_user_closing' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserClosing', 'enabled' => 1, 'visible' => -1, 'position' => 512),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 900),
		'fk_statut' => array('type' => 'smallint(6)', 'label' => 'Status', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'position' => 1000, 'arrayofkeyval' => array(0 => 'Draft', 1 => 'Validated', 2 => 'Paid', 3 => 'Abandonned')),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * Standard invoice
	 */
	const TYPE_STANDARD = 0;

	/**
	 * Replacement invoice
	 */
	const TYPE_REPLACEMENT = 1;

	/**
	 * Credit note invoice
	 */
	const TYPE_CREDIT_NOTE = 2;

	/**
	 * Deposit invoice
	 */
	const TYPE_DEPOSIT = 3;

	/**
	 * Proforma invoice (should not be used. a proforma is an order)
	 */
	const TYPE_PROFORMA = 4;

	/**
	 * Situation invoice
	 */
	const TYPE_SITUATION = 5;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated (need to be paid)
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Classified paid.
	 * If paid partially, $this->close_code can be:
	 * - CLOSECODE_DISCOUNTVAT
	 * - CLOSECODE_BADDEBT
	 * If paid completely, this->close_code will be null
	 */
	const STATUS_CLOSED = 2;

	/**
	 * Classified abandoned and no payment done.
	 * $this->close_code can be:
	 * - CLOSECODE_BADDEBT
	 * - CLOSECODE_ABANDONED
	 * - CLOSECODE_REPLACED
	 */
	const STATUS_ABANDONED = 3;

	const CLOSECODE_DISCOUNTVAT = 'discount_vat'; // Abandoned remain - escompte
	const CLOSECODE_BADDEBT = 'badcustomer'; // Abandoned remain - bad customer
	const CLOSECODE_BANKCHARGE = 'bankcharge'; // Abandoned remain - bank charge
	const CLOSECODE_OTHER = 'other'; // Abandoned remain - other

	const CLOSECODE_ABANDONED = 'abandon'; // Abandoned - other
	const CLOSECODE_REPLACED = 'replaced'; // Closed after doing a replacement invoice


	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
	}

	/**
	 *	Create invoice in database.
	 *  Note: this->ref can be set or empty. If empty, we will use "(PROV999)"
	 *  Note: this->fac_rec must be set to create invoice from a recurring invoice
	 *
	 *	@param	User	$user      		Object user that create
	 *	@param  int		$notrigger		1=Does not execute triggers, 0 otherwise
	 * 	@param	int		$forceduedate	If set, do not recalculate due date from payment condition but force it with value
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function create(User $user, $notrigger = 0, $forceduedate = 0)
	{
		global $langs, $conf, $mysoc, $hookmanager;
		$error = 0;
		$origin_user_author_id = ($user->id > 0 ? (int) $user->id : 0);
		// Clean parameters
		if (empty($this->type)) {
			$this->type = self::TYPE_STANDARD;
		}

		$this->ref_client = trim($this->ref_client);

		$this->note_private = (isset($this->note_private) ? trim($this->note_private) : '');
		$this->note = (isset($this->note) ? trim($this->note) : $this->note_private); // deprecated
		$this->note_public = trim($this->note_public);
		if (!$this->cond_reglement_id) {
			$this->cond_reglement_id = 0;
		}
		if (!$this->mode_reglement_id) {
			$this->mode_reglement_id = 0;
		}
		$this->status = self::STATUS_DRAFT;
		$this->statut = self::STATUS_DRAFT;	// deprecated

		if (!empty($this->multicurrency_code)) {
			// Multicurrency (test on $this->multicurrency_tx because we should take the default rate of multicurrency_code only if not using original rate)
			if (empty($this->multicurrency_tx)) {
				// If original rate is not set, we take a default value from date
				list($this->fk_multicurrency, $this->multicurrency_tx) = MultiCurrency::getIdAndTxFromCode($this->db, $this->multicurrency_code, $this->date);
			} else {
				// original rate multicurrency_tx and multicurrency_code are set, we use them
				$this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
			}
		} else {
			$this->fk_multicurrency = 0;
		}
		if (empty($this->fk_multicurrency)) {
			$this->multicurrency_code = $conf->currency;
			$this->fk_multicurrency = 0;
			$this->multicurrency_tx = 1;
		}

		dol_syslog(get_class($this)."::create user=".$user->id." date=".$this->date);

		// Check parameters
		if (empty($this->date)) {
			$this->error = "Try to create an invoice with an empty parameter (date)";
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -3;
		}
		$soc = new Societe($this->db);
		$result = $soc->fetch($this->socid);
		if ($result < 0) {
			$this->error = "Failed to fetch company: ".$soc->error;
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -2;
		}

		$now = dol_now();
		$this->date_creation = $now;

		$this->db->begin();

		$originaldatewhen = null;
		$nextdatewhen = null;
		$previousdaynextdatewhen = null;

		$_facrec = null;

		// Erase some properties of the invoice to create with the one of the recurring invoice
		if ($this->fac_rec > 0) {
			$this->fk_fac_rec_source = $this->fac_rec;

			if (getDolGlobalString('MODEL_FAC_REC_AUTHOR')) {
				$origin_user_author_id = ($this->fk_user_author > 0 ? $this->fk_user_author : $origin_user_author_id);
			}
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
			$_facrec = new FactureRec($this->db);
			$result = $_facrec->fetch($this->fac_rec);
			$result = $_facrec->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0); // This load $_facrec->linkedObjectsIds

			// Define some dates
			$originaldatewhen = $_facrec->date_when;
			$nextdatewhen = null;
			$previousdaynextdatewhen = null;
			if ($originaldatewhen) {
				$nextdatewhen = dol_time_plus_duree($originaldatewhen, $_facrec->frequency, $_facrec->unit_frequency);
				$previousdaynextdatewhen = dol_time_plus_duree($nextdatewhen, -1, 'd');
			}

			if (!empty($_facrec->frequency)) {  // Invoice are created on same thirdparty than template when there is a recurrence, but not necessarily when there is no recurrence.
				$this->socid = $_facrec->socid;
			}
			$this->entity            = $_facrec->entity; // Invoice created in same entity than template

			// Fields coming from GUI.
			// @TODO Value of template should be used as default value on the form on the GUI, and we should here always use the value from GUI
			// set by posted page with $object->xxx = ... and this section should be removed.
			$this->fk_project        = GETPOSTINT('projectid') > 0 ? GETPOSTINT('projectid') : $_facrec->fk_project;
			$this->note_public       = GETPOSTISSET('note_public') ? GETPOST('note_public', 'restricthtml') : $_facrec->note_public;
			$this->note_private      = GETPOSTISSET('note_private') ? GETPOST('note_private', 'restricthtml') : $_facrec->note_private;
			$this->model_pdf = GETPOSTISSET('model') ? GETPOST('model', 'alpha') : $_facrec->model_pdf;
			$this->cond_reglement_id = GETPOSTINT('cond_reglement_id') > 0 ? GETPOSTINT('cond_reglement_id') : $_facrec->cond_reglement_id;
			$this->mode_reglement_id = GETPOSTINT('mode_reglement_id') > 0 ? GETPOSTINT('mode_reglement_id') : $_facrec->mode_reglement_id;
			$this->fk_account        = GETPOST('fk_account') > 0 ? GETPOSTINT('fk_account') : $_facrec->fk_account;

			// Set here to have this defined for substitution into notes, should be recalculated after adding lines to get same result
			$this->total_ht          = $_facrec->total_ht;
			$this->total_ttc         = $_facrec->total_ttc;

			// Fields always coming from template
			//$this->remise_absolue    = $_facrec->remise_absolue;
			//$this->remise_percent    = $_facrec->remise_percent;	// TODO deprecated
			$this->fk_incoterms = $_facrec->fk_incoterms;
			$this->location_incoterms = $_facrec->location_incoterms;

			// Clean parameters
			if (!$this->type) {
				$this->type = self::TYPE_STANDARD;
			}
			$this->ref_client = trim($this->ref_client);
			$this->ref_customer = trim($this->ref_customer);
			$this->note_public = trim($this->note_public);
			$this->note_private = trim($this->note_private);
			$this->note_private = dol_concatdesc($this->note_private, $langs->trans("GeneratedFromRecurringInvoice", $_facrec->ref));

			$this->array_options = $_facrec->array_options;

			if (!$this->mode_reglement_id) {
				$this->mode_reglement_id = 0;
			}
			$this->status = self::STATUS_DRAFT;
			$this->statut = self::STATUS_DRAFT;	// deprecated

			$this->linked_objects = $_facrec->linkedObjectsIds;
			// We do not add link to template invoice or next invoice will be linked to all generated invoices
			//$this->linked_objects['facturerec'][0] = $this->fac_rec;

			// For recurring invoices, update date and number of last generation of recurring template invoice, before inserting new invoice
			if ($_facrec->frequency > 0) {
				dol_syslog("This is a recurring invoice so we set date_last_gen and next date_when");
				if (empty($_facrec->date_when)) {
					$_facrec->date_when = $now;
				}
				$next_date = $_facrec->getNextDate(); // Calculate next date
				$result = $_facrec->setValueFrom('date_last_gen', $now, '', null, 'date', '', $user, '');
				//$_facrec->setValueFrom('nb_gen_done', $_facrec->nb_gen_done + 1);		// Not required, +1 already included into setNextDate when second param is 1.
				$result = $_facrec->setNextDate($next_date, 1);
			}

			// Define lang of customer
			$outputlangs = $langs;
			$newlang = '';

			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($this->thirdparty->default_lang)) {
				$newlang = $this->thirdparty->default_lang; // for proposal, order, invoice, ...
			}
			// @phan-suppress-next-line PhanUndeclaredProperty
			if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && isset($this->default_lang)) {
				$newlang = $this->default_lang; // for thirdparty @phan-suppress-current-line PhanUndeclaredProperty
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			// Array of possible substitutions (See also file mailing-send.php that should manage same substitutions)
			$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $this);
			$substitutionarray['__INVOICE_PREVIOUS_MONTH__'] = dol_print_date(dol_time_plus_duree($this->date, -1, 'm'), '%m');
			$substitutionarray['__INVOICE_MONTH__'] = dol_print_date($this->date, '%m');
			$substitutionarray['__INVOICE_NEXT_MONTH__'] = dol_print_date(dol_time_plus_duree($this->date, 1, 'm'), '%m');
			$substitutionarray['__INVOICE_PREVIOUS_MONTH_TEXT__'] = dol_print_date(dol_time_plus_duree($this->date, -1, 'm'), '%B');
			$substitutionarray['__INVOICE_MONTH_TEXT__'] = dol_print_date($this->date, '%B');
			$substitutionarray['__INVOICE_NEXT_MONTH_TEXT__'] = dol_print_date(dol_time_plus_duree($this->date, 1, 'm'), '%B');
			$substitutionarray['__INVOICE_PREVIOUS_YEAR__'] = dol_print_date(dol_time_plus_duree($this->date, -1, 'y'), '%Y');
			$substitutionarray['__INVOICE_YEAR__'] = dol_print_date($this->date, '%Y');
			$substitutionarray['__INVOICE_NEXT_YEAR__'] = dol_print_date(dol_time_plus_duree($this->date, 1, 'y'), '%Y');
			// Only for template invoice
			$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__'] = (isset($originaldatewhen) ? dol_print_date($originaldatewhen, 'dayhour') : '');
			$substitutionarray['__INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__'] = (isset($nextdatewhen) ? dol_print_date($nextdatewhen, 'dayhour') : '');
			$substitutionarray['__INVOICE_PREVIOUS_DATE_NEXT_INVOICE_AFTER_GEN__'] = (isset($previousdaynextdatewhen) ? dol_print_date($previousdaynextdatewhen, 'dayhour') : '');
			$substitutionarray['__INVOICE_COUNTER_CURRENT__'] = $_facrec->nb_gen_done;
			$substitutionarray['__INVOICE_COUNTER_MAX__'] = $_facrec->nb_gen_max;

			//var_dump($substitutionarray);exit;

			complete_substitutions_array($substitutionarray, $outputlangs);

			$this->note_public = make_substitutions($this->note_public, $substitutionarray);
			$this->note_private = make_substitutions($this->note_private, $substitutionarray);
		}

		// Define due date if not already defined
		if (empty($forceduedate)) {
			$duedate = $this->calculate_date_lim_reglement();
			/*if ($duedate < 0) {	Regression, a date can be negative if before 1970.
				dol_syslog(__METHOD__ . ' Error in calculate_date_lim_reglement. We got ' . $duedate, LOG_ERR);
				return -1;
			}*/
			$this->date_lim_reglement = $duedate;
		} else {
			$this->date_lim_reglement = $forceduedate;
		}

		// Insert into database
		$socid = $this->socid;

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture (";
		$sql .= " ref";
		$sql .= ", entity";
		$sql .= ", ref_ext";
		$sql .= ", type";
		$sql .= ", subtype";
		$sql .= ", fk_soc";
		$sql .= ", datec";
		$sql .= ", datef";
		$sql .= ", date_pointoftax";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", ref_client";
		$sql .= ", fk_account";
		$sql .= ", module_source, pos_source, fk_fac_rec_source, fk_facture_source, fk_user_author, fk_projet";
		$sql .= ", fk_cond_reglement, fk_mode_reglement, date_lim_reglement, model_pdf";
		$sql .= ", situation_cycle_ref, situation_counter, situation_final";
		$sql .= ", fk_incoterms, location_incoterms";
		$sql .= ", fk_multicurrency";
		$sql .= ", multicurrency_code";
		$sql .= ", multicurrency_tx";
		$sql .= ", retained_warranty";
		$sql .= ", retained_warranty_date_limit";
		$sql .= ", retained_warranty_fk_cond_reglement";
		$sql .= ")";
		$sql .= " VALUES (";
		$sql .= "'(PROV)'";
		$sql .= ", ".setEntity($this);
		$sql .= ", ".($this->ref_ext ? "'".$this->db->escape($this->ref_ext)."'" : "null");
		$sql .= ", '".$this->db->escape($this->type)."'";
		$sql .= ", ".($this->subtype ? "'".$this->db->escape($this->subtype)."'" : "null");
		$sql .= ", ".((int) $socid);
		$sql .= ", '".$this->db->idate($this->date_creation)."'";
		$sql .= ", '".$this->db->idate($this->date)."'";
		$sql .= ", ".(empty($this->date_pointoftax) ? "null" : "'".$this->db->idate($this->date_pointoftax)."'");
		$sql .= ", ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "null");
		$sql .= ", ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "null");
		$sql .= ", ".($this->ref_customer ? "'".$this->db->escape($this->ref_customer)."'" : ($this->ref_client ? "'".$this->db->escape($this->ref_client)."'" : "null"));
		$sql .= ", ".($this->fk_account > 0 ? $this->fk_account : 'NULL');
		$sql .= ", ".($this->module_source ? "'".$this->db->escape($this->module_source)."'" : "null");
		$sql .= ", ".($this->pos_source != '' ? "'".$this->db->escape($this->pos_source)."'" : "null");
		$sql .= ", ".($this->fk_fac_rec_source ? "'".$this->db->escape($this->fk_fac_rec_source)."'" : "null");
		$sql .= ", ".($this->fk_facture_source ? "'".$this->db->escape($this->fk_facture_source)."'" : "null");
		$sql .= ", ".($origin_user_author_id > 0 ? (int) $origin_user_author_id : "null");
		$sql .= ", ".($this->fk_project ? (int) $this->fk_project : "null");
		$sql .= ", ".((int) $this->cond_reglement_id);
		$sql .= ", ".((int) $this->mode_reglement_id);
		$sql .= ", '".$this->db->idate($this->date_lim_reglement)."'";
		$sql .= ", ".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null");
		$sql .= ", ".($this->situation_cycle_ref ? "'".$this->db->escape($this->situation_cycle_ref)."'" : "null");
		$sql .= ", ".($this->situation_counter ? "'".$this->db->escape($this->situation_counter)."'" : "null");
		$sql .= ", ".($this->situation_final ? (int) $this->situation_final : 0);
		$sql .= ", ".(int) $this->fk_incoterms;
		$sql .= ", '".$this->db->escape($this->location_incoterms)."'";
		$sql .= ", ".(int) $this->fk_multicurrency;
		$sql .= ", '".$this->db->escape($this->multicurrency_code)."'";
		$sql .= ", ".(float) $this->multicurrency_tx;
		$sql .= ", ".(empty($this->retained_warranty) ? "0" : $this->db->escape($this->retained_warranty));
		$sql .= ", ".(!empty($this->retained_warranty_date_limit) ? "'".$this->db->idate($this->retained_warranty_date_limit)."'" : 'NULL');
		$sql .= ", ".(int) $this->retained_warranty_fk_cond_reglement;
		$sql .= ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'facture');

			// Update ref with new one
			$this->ref = '(PROV'.$this->id.')';
			$sql = 'UPDATE '.MAIN_DB_PREFIX."facture SET ref='".$this->db->escape($this->ref)."' WHERE rowid=".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
			}

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

			// Propagate contacts
			if (!$error && $this->id && getDolGlobalString('MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN') && !empty($this->origin) && !empty($this->origin_id)) {   // Get contact from origin object
				$originforcontact = $this->origin;
				$originidforcontact = $this->origin_id;
				if ($originforcontact == 'shipping') {     // shipment and order share the same contacts. If creating from shipment we take data of order
					require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
					$exp = new Expedition($this->db);
					$exp->fetch($this->origin_id);
					$exp->fetchObjectLinked(null, '', null, '', 'OR', 1, 'sourcetype', 0);
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

			/*
			 *  Insert lines of invoices, if not from template invoice, into database
			 */
			if (!$error && empty($this->fac_rec) && count($this->lines) && is_object($this->lines[0])) {	// If this->lines is array of InvoiceLines (preferred mode)
				$fk_parent_line = 0;

				dol_syslog("There is ".count($this->lines)." lines into ->lines that are InvoiceLines");
				foreach ($this->lines as $i => $val) {
					$newinvoiceline = $this->lines[$i];
					'@phan-var-force FactureLigne $newinvoiceline';

					$newinvoiceline->context = $this->context;

					$newinvoiceline->fk_facture = $this->id;

					$newinvoiceline->origin = $this->lines[$i]->element;
					$newinvoiceline->origin_id = $this->lines[$i]->id;

					// Auto set date of service ?
					if ($this->lines[$i]->date_start_fill == 1 && $originaldatewhen) {		// $originaldatewhen is defined when generating from recurring invoice only
						$newinvoiceline->date_start = $originaldatewhen;
					}
					if ($this->lines[$i]->date_end_fill == 1 && $previousdaynextdatewhen) {	// $previousdaynextdatewhen is defined when generating from recurring invoice only
						$newinvoiceline->date_end = $previousdaynextdatewhen;
					}

					if ($result >= 0) {
						// Reset fk_parent_line for no child products and special product
						if (($newinvoiceline->product_type != 9 && empty($newinvoiceline->fk_parent_line)) || $newinvoiceline->product_type == 9) {
							$fk_parent_line = 0;
						}

						// Complete vat rate with code
						$vatrate = $newinvoiceline->tva_tx;
						if ($newinvoiceline->vat_src_code && ! preg_match('/\(.*\)/', (string) $vatrate)) {
							$vatrate .= ' ('.$newinvoiceline->vat_src_code.')';
						}

						$newinvoiceline->fk_parent_line = $fk_parent_line;

						if ($this->type == Facture::TYPE_REPLACEMENT && $newinvoiceline->fk_remise_except) {
							$discount = new DiscountAbsolute($this->db);
							$discount->fetch($newinvoiceline->fk_remise_except);

							$discountId = $soc->set_remise_except($discount->amount_ht, $user, $discount->description, $discount->tva_tx);
							$newinvoiceline->fk_remise_except = $discountId;
						}

						$result = $this->addline(
							$newinvoiceline->desc,
							$newinvoiceline->subprice,
							$newinvoiceline->qty,
							$vatrate,
							$newinvoiceline->localtax1_tx,
							$newinvoiceline->localtax2_tx,
							$newinvoiceline->fk_product,
							$newinvoiceline->remise_percent,
							$newinvoiceline->date_start,
							$newinvoiceline->date_end,
							$newinvoiceline->fk_code_ventilation,
							$newinvoiceline->info_bits,
							$newinvoiceline->fk_remise_except,
							'HT',
							0,
							$newinvoiceline->product_type,
							$newinvoiceline->rang,
							$newinvoiceline->special_code,
							$newinvoiceline->element,
							$newinvoiceline->id,
							$fk_parent_line,
							$newinvoiceline->fk_fournprice,
							$newinvoiceline->pa_ht,
							$newinvoiceline->label,
							$newinvoiceline->array_options,
							$newinvoiceline->situation_percent,
							$newinvoiceline->fk_prev_id,
							$newinvoiceline->fk_unit,
							$newinvoiceline->multicurrency_subprice,
							$newinvoiceline->ref_ext,
							1
						);

						// Defined the new fk_parent_line
						if ($result > 0 && $newinvoiceline->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
					if ($result < 0) {
						$this->error = $newinvoiceline->error;
						$this->errors = $newinvoiceline->errors;
						$error++;
						break;
					}
				}
			} elseif (!$error && empty($this->fac_rec)) { 		// If this->lines is an array of invoice line arrays
				$fk_parent_line = 0;

				dol_syslog("There is ".count($this->lines)." lines into ->lines as a simple array");

				foreach ($this->lines as $i => $val) {
					$line = $this->lines[$i];
					'@phan-var-force FactureLigne $line';

					// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
					//if (! is_object($line)) $line=json_decode(json_encode($line), false);  // convert recursively array into object.
					if (!is_object($line)) {
						$line = (object) $line;
					}

					if ($result >= 0) {
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

						// init ref_ext
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
							$line->date_start,
							$line->date_end,
							$line->fk_code_ventilation,
							$line->info_bits,
							$line->fk_remise_except,
							'HT',
							0,
							$line->product_type,
							$line->rang,
							$line->special_code,
							$origintype,
							$originid,
							$fk_parent_line,
							$line->fk_fournprice,
							$line->pa_ht,
							$line->label,
							$line->array_options,
							$line->situation_percent,
							$line->fk_prev_id,
							$line->fk_unit,
							$line->multicurrency_subprice,
							$line->ref_ext,
							1
						);
						if ($result < 0) {
							$this->error = $this->db->lasterror();
							dol_print_error($this->db);
							$this->db->rollback();
							return -1;
						}

						// Defined the new fk_parent_line
						if ($result > 0 && $line->product_type == 9) {
							$fk_parent_line = $result;
						}
					}
				}
			}

			/*
			 * Insert lines coming from the template invoice
			 */
			if (!$error && $this->fac_rec > 0 && is_object($_facrec)) {
				dol_syslog("There is ".count($_facrec->lines)." lines from recurring invoice");
				$fk_parent_line = 0;

				foreach ($_facrec->lines as $i => $val) {
					if ($_facrec->lines[$i]->fk_product) {
						$prod = new Product($this->db);
						$res = $prod->fetch($_facrec->lines[$i]->fk_product);
					}

					// Reset fk_parent_line for no child products and special product
					if (($_facrec->lines[$i]->product_type != 9 && empty($_facrec->lines[$i]->fk_parent_line)) || $_facrec->lines[$i]->product_type == 9) {
						$fk_parent_line = 0;
					}

					// For line from template invoice, we use data from template invoice
					/*
					$tva_tx = get_default_tva($mysoc,$soc,$prod->id);
					$tva_npr = get_default_npr($mysoc,$soc,$prod->id);
					if (empty($tva_tx)) $tva_npr=0;
					$localtax1_tx=get_localtax($tva_tx,1,$soc,$mysoc,$tva_npr);
					$localtax2_tx=get_localtax($tva_tx,2,$soc,$mysoc,$tva_npr);
					*/
					$tva_tx = $_facrec->lines[$i]->tva_tx.($_facrec->lines[$i]->vat_src_code ? '('.$_facrec->lines[$i]->vat_src_code.')' : '');
					$tva_npr = $_facrec->lines[$i]->info_bits;
					if (empty($tva_tx)) {
						$tva_npr = 0;
					}
					$localtax1_tx = $_facrec->lines[$i]->localtax1_tx;
					$localtax2_tx = $_facrec->lines[$i]->localtax2_tx;

					$fk_product_fournisseur_price = empty($_facrec->lines[$i]->fk_product_fournisseur_price) ? null : $_facrec->lines[$i]->fk_product_fournisseur_price;
					$buyprice = empty($_facrec->lines[$i]->buyprice) ? 0 : $_facrec->lines[$i]->buyprice;

					// If buyprice not defined from template invoice, we try to guess the best value
					if (!$buyprice && $_facrec->lines[$i]->fk_product > 0) {
						require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
						$producttmp = new ProductFournisseur($this->db);
						$producttmp->fetch($_facrec->lines[$i]->fk_product);

						// If margin module defined on costprice, we try the costprice
						// If not defined or if module margin defined and pmp and stock module enabled, we try pmp price
						// else we get the best supplier price
						if (getDolGlobalString('MARGIN_TYPE') == 'costprice' && !empty($producttmp->cost_price)) {
							$buyprice = $producttmp->cost_price;
						} elseif (isModEnabled('stock') && (getDolGlobalString('MARGIN_TYPE') == 'costprice' || getDolGlobalString('MARGIN_TYPE') == 'pmp') && !empty($producttmp->pmp)) {
							$buyprice = $producttmp->pmp;
						} else {
							if ($producttmp->find_min_price_product_fournisseur($_facrec->lines[$i]->fk_product) > 0) {
								if ($producttmp->product_fourn_price_id > 0) {
									$buyprice = price2num($producttmp->fourn_unitprice * (1 - $producttmp->fourn_remise_percent / 100) + $producttmp->fourn_remise, 'MU');
								}
							}
						}
					}

					$result_insert = $this->addline(
						$_facrec->lines[$i]->desc,
						$_facrec->lines[$i]->subprice,
						$_facrec->lines[$i]->qty,
						$tva_tx,
						$localtax1_tx,
						$localtax2_tx,
						$_facrec->lines[$i]->fk_product,
						$_facrec->lines[$i]->remise_percent,
						($_facrec->lines[$i]->date_start_fill == 1 && $originaldatewhen) ? $originaldatewhen : '',
						($_facrec->lines[$i]->date_end_fill == 1 && $previousdaynextdatewhen) ? $previousdaynextdatewhen : '',
						0,
						$tva_npr,
						0,  // fk_remise_except
						'HT',
						0,
						$_facrec->lines[$i]->product_type,
						$_facrec->lines[$i]->rang,
						$_facrec->lines[$i]->special_code,
						'',
						0,
						$fk_parent_line,
						$fk_product_fournisseur_price,
						$buyprice,
						$_facrec->lines[$i]->label,
						empty($_facrec->lines[$i]->array_options) ? null : $_facrec->lines[$i]->array_options,
						100,	// situation percent is undefined on recurring invoice lines
						0,  // fk_prev_id
						$_facrec->lines[$i]->fk_unit,
						$_facrec->lines[$i]->multicurrency_subprice,
						$_facrec->lines[$i]->ref_ext,
						1
					);

					// Defined the new fk_parent_line
					if ($result_insert > 0 && $_facrec->lines[$i]->product_type == 9) {
						$fk_parent_line = $result_insert;
					}

					if ($result_insert < 0) {
						$error++;
						$this->error = $this->db->error();
						break;
					}
				}
			}

			if (!$error) {
				$result = $this->update_price(1, 'auto', 0, $mysoc);
				if ($result > 0) {
					$action = 'create';

					// Actions on extra fields
					if (!$error) {
						$result = $this->insertExtraFields();
						if ($result < 0) {
							$error++;
						}
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger('BILL_CREATE', $user);
						if ($result < 0) {
							$error++;
						}
					}

					if (!$error) {
						$this->db->commit();
						return $this->id;
					} else {
						$this->db->rollback();
						return -4;
					}
				} else {
					$this->error = $langs->trans('FailedToUpdatePrice');
					$this->db->rollback();
					return -3;
				}
			} else {
				dol_syslog(get_class($this)."::create error ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Create a new invoice in database from current invoice
	 *
	 *	@param	User		$user    		Object user that ask creation
	 *	@param	int<0,1>	$invertdetail	Reverse sign of amounts for lines
	 *	@return	int<-1,1>					Return integer <0 if KO, >0 if OK
	 */
	public function createFromCurrent(User $user, $invertdetail = 0)
	{
		// Source invoice load
		$facture = new Facture($this->db);

		// Retrieve all extrafield
		// fetch optionals attributes and labels
		$this->fetch_optionals();

		if (!empty($this->array_options)) {
			$facture->array_options = $this->array_options;
		}

		foreach ($this->lines as &$line) {
			$line->fetch_optionals(); //fetch extrafields
		}

		$facture->fk_facture_source = $this->fk_facture_source;
		$facture->type 			    = $this->type;
		$facture->subtype 			= $this->subtype;
		$facture->socid 		    = $this->socid;
		$facture->date              = $this->date;
		$facture->date_pointoftax   = $this->date_pointoftax;
		$facture->note_public       = $this->note_public;
		$facture->note_private      = $this->note_private;
		$facture->ref_client        = $this->ref_client;
		$facture->ref_customer      = $this->ref_customer;
		$facture->model_pdf         = $this->model_pdf;
		$facture->fk_project        = $this->fk_project;
		$facture->cond_reglement_id = $this->cond_reglement_id;
		$facture->mode_reglement_id = $this->mode_reglement_id;
		//$facture->remise_absolue    = $this->remise_absolue;
		//$facture->remise_percent    = $this->remise_percent;	// TODO deprecated

		$facture->origin            = $this->origin;
		$facture->origin_id         = $this->origin_id;
		$facture->fk_account         = $this->fk_account;

		$facture->lines = $this->lines; // Array of lines of invoice
		$facture->situation_counter = $this->situation_counter;
		$facture->situation_cycle_ref = $this->situation_cycle_ref;
		$facture->situation_final = $this->situation_final;

		$facture->retained_warranty = $this->retained_warranty;
		$facture->retained_warranty_fk_cond_reglement = $this->retained_warranty_fk_cond_reglement;
		$facture->retained_warranty_date_limit = $this->retained_warranty_date_limit;

		$facture->fk_user_author = $user->id;
		$facture->user_creation_id = $user->id;


		// Loop on each line of new invoice
		foreach ($facture->lines as $i => $tmpline) {
			$facture->lines[$i]->fk_prev_id = $this->lines[$i]->rowid;
			if ($invertdetail) {
				$facture->lines[$i]->subprice  = -$facture->lines[$i]->subprice;
				$facture->lines[$i]->total_ht  = -$facture->lines[$i]->total_ht;
				$facture->lines[$i]->total_tva = -$facture->lines[$i]->total_tva;
				$facture->lines[$i]->total_localtax1 = -$facture->lines[$i]->total_localtax1;
				$facture->lines[$i]->total_localtax2 = -$facture->lines[$i]->total_localtax2;
				$facture->lines[$i]->total_ttc = -$facture->lines[$i]->total_ttc;
				$facture->lines[$i]->ref_ext = '';
			}
		}

		dol_syslog(get_class($this)."::createFromCurrent invertdetail=".$invertdetail." socid=".$this->socid." nboflines=".count($facture->lines));

		$facid = $facture->create($user);
		if ($facid <= 0) {
			$this->error = $facture->error;
			$this->errors = $facture->errors;
		} elseif ($this->type == self::TYPE_SITUATION && getDolGlobalString('INVOICE_USE_SITUATION')) {
			$this->fetchObjectLinked(null, '', $this->id, 'facture');

			foreach ($this->linkedObjectsIds as $typeObject => $Tfk_object) {
				foreach ($Tfk_object as $fk_object) {
					$facture->add_object_linked($typeObject, $fk_object);
				}
			}

			$facture->add_object_linked('facture', $this->fk_facture_source);
		}

		return $facid;
	}


	/**
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param      User	$user        	User that clone
	 *  @param  	int 	$fromid         Id of object to clone
	 * 	@return		int					    New id of clone
	 */
	public function createFromClone(User $user, $fromid = 0)
	{
		global $conf, $hookmanager;

		$error = 0;

		$object = new Facture($this->db);

		$this->db->begin();

		$object->fetch($fromid);

		// Load source object
		$objFrom = clone $object;

		// Change socid if needed
		if (!empty($this->socid) && $this->socid != $object->socid) {
			$objsoc = new Societe($this->db);

			if ($objsoc->fetch($this->socid) > 0) {
				$object->socid = $objsoc->id;
				$object->cond_reglement_id	= (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				$object->mode_reglement_id	= (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$object->fk_project = 0;
				$object->fk_delivery_address = 0;
			}

			// TODO Change product price if multi-prices
		}

		$object->id = 0;
		$object->statut = self::STATUS_DRAFT;
		$object->status = self::STATUS_DRAFT;

		// Clear fields
		$object->date               = (empty($this->date) ? dol_now() : $this->date);
		$object->user_creation_id   = $user->id;
		$object->user_validation_id = null;
		$object->fk_user_author     = $user->id;
		$object->fk_user_valid      = null;
		$object->fk_facture_source  = 0;
		$object->date_creation      = '';
		$object->date_modification = '';
		$object->date_validation    = '';
		$object->ref_client         = '';
		$object->close_code         = '';
		$object->close_note         = '';
		if (getDolGlobalInt('MAIN_DONT_KEEP_NOTE_ON_CLONING') == 1) {
			$object->note_private = '';
			$object->note_public = '';
		}

		// Loop on each line of new invoice
		foreach ($object->lines as $i => $line) {
			'@phan-var-force FactureLigne $line';
			if (($line->info_bits & 0x02) == 0x02) {	// We do not clone line of discounts
				unset($object->lines[$i]);
				continue;
			}

			// Block to update dates of service (month by month only if previously filled and similar to start and end of month)
			// If it's a service with start and end dates
			if (getDolGlobalString('INVOICE_AUTO_NEXT_MONTH_ON_LINES') && !empty($line->date_start) && !empty($line->date_end)) {
				// Get the dates
				$start = dol_getdate($line->date_start);
				$end = dol_getdate($line->date_end);

				// Get the first and last day of the month
				$first = dol_get_first_day($start['year'], $start['mon']);
				$last = dol_get_last_day($end['year'], $end['mon']);

				//print dol_print_date(dol_mktime(0, 0, 0, $start['mon'], $start['mday'], $start['year'], 'gmt'), 'dayhour').' '.dol_print_date($first, 'dayhour').'<br>';
				//print dol_mktime(23, 59, 59, $end['mon'], $end['mday'], $end['year'], 'gmt').' '.$last.'<br>';exit;
				// If start date is first date of month and end date is last date of month
				if (dol_mktime(0, 0, 0, $start['mon'], $start['mday'], $start['year'], 'gmt') == $first
					&& dol_mktime(23, 59, 59, $end['mon'], $end['mday'], $end['year'], 'gmt') == $last) {
					$nextMonth = dol_get_next_month($end['mon'], $end['year']);
					$newFirst = dol_get_first_day($nextMonth['year'], $nextMonth['month']);
					$newLast = dol_get_last_day($nextMonth['year'], $nextMonth['month']);
					$object->lines[$i]->date_start = $newFirst;
					$object->lines[$i]->date_end = $newLast;
				}
			}

			$object->lines[$i]->ref_ext = ''; // Do not clone ref_ext
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		} else {
			// copy internal contacts
			if ($object->copy_linked_contact($objFrom, 'internal') < 0) {
				$error++;
				$this->error = $object->error;
				$this->errors = $object->errors;
			} elseif ($object->socid == $objFrom->socid) {
				// copy external contacts if same company
				if ($object->copy_linked_contact($objFrom, 'external') < 0) {
					$error++;
					$this->error = $object->error;
					$this->errors = $object->errors;
				}
			}
		}

		if (!$error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array('objFrom' => $objFrom);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}
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
	 *	Load an object from an order and create a new invoice into database
	 *
	 *	@param	Facture		$object		Object source
	 *	@param	User		$user		Object user
	 *	@return	int<-1,1>				Return integer <0 if KO, 0 if nothing done, 1 if OK
	 */
	public function createFromOrder($object, User $user)
	{
		global $conf, $hookmanager;

		$error = 0;

		// Closed order
		$this->date = dol_now();
		$this->source = 0;

		$num = count($object->lines);
		for ($i = 0; $i < $num; $i++) {
			$line = new FactureLigne($this->db);
			$src_line = $object->lines[$i];
			'@phan-var-force FactureLigne $src_line';
			$line->libelle 			= $src_line->libelle; // deprecated
			$line->label			= $src_line->label;
			$line->desc				= $src_line->desc;
			$line->subprice			= $src_line->subprice;
			$line->total_ht			= $src_line->total_ht;
			$line->total_tva		= $src_line->total_tva;
			$line->total_localtax1	= $src_line->total_localtax1;
			$line->total_localtax2	= $src_line->total_localtax2;
			$line->total_ttc		= $src_line->total_ttc;
			$line->vat_src_code = $src_line->vat_src_code;
			$line->tva_tx = $src_line->tva_tx;
			$line->localtax1_tx		= $src_line->localtax1_tx;
			$line->localtax2_tx		= $src_line->localtax2_tx;
			$line->qty = $src_line->qty;
			$line->fk_remise_except = $src_line->fk_remise_except;
			$line->remise_percent = $src_line->remise_percent;
			$line->fk_product = $src_line->fk_product;
			$line->info_bits = $src_line->info_bits;
			$line->product_type		= $src_line->product_type;
			$line->rang = $src_line->rang;
			$line->special_code		= $src_line->special_code;
			$line->fk_parent_line = $src_line->fk_parent_line;
			$line->fk_unit = $src_line->fk_unit;
			$line->date_start = $src_line->date_start;
			$line->date_end = $src_line->date_end;

			// Multicurrency
			$line->fk_multicurrency = $src_line->fk_multicurrency;
			$line->multicurrency_code = $src_line->multicurrency_code;
			$line->multicurrency_subprice = $src_line->multicurrency_subprice;
			$line->multicurrency_total_ht = $src_line->multicurrency_total_ht;
			$line->multicurrency_total_tva = $src_line->multicurrency_total_tva;
			$line->multicurrency_total_ttc = $src_line->multicurrency_total_ttc;

			$line->fk_fournprice = $src_line->fk_fournprice;
			$marginInfos			= getMarginInfos($src_line->subprice, $src_line->remise_percent, $src_line->tva_tx, $src_line->localtax1_tx, $src_line->localtax2_tx, $src_line->fk_fournprice, $src_line->pa_ht);
			$line->pa_ht			= $marginInfos[0];

			// get extrafields from original line
			$src_line->fetch_optionals();
			foreach ($src_line->array_options as $options_key => $value) {
				$line->array_options[$options_key] = $value;
			}

			$this->lines[$i] = $line;
		}

		$this->socid                = $object->socid;
		$this->fk_project           = $object->fk_project;
		$this->fk_account = $object->fk_account;
		$this->cond_reglement_id    = $object->cond_reglement_id;
		$this->mode_reglement_id    = $object->mode_reglement_id;
		$this->availability_id      = $object->availability_id;
		$this->demand_reason_id     = $object->demand_reason_id;
		$this->delivery_date        = $object->delivery_date;
		$this->fk_delivery_address  = $object->fk_delivery_address; // deprecated
		$this->contact_id           = $object->contact_id;
		$this->ref_client           = $object->ref_client;

		if (!getDolGlobalString('MAIN_DISABLE_PROPAGATE_NOTES_FROM_ORIGIN')) {
			$this->note_private = $object->note_private;
			$this->note_public = $object->note_public;
		}

		$this->module_source = $object->module_source;
		$this->pos_source = $object->pos_source;

		$this->origin = $object->element;
		$this->origin_id = $object->id;

		$this->fk_user_author = $user->id;

		// get extrafields from original line
		$object->fetch_optionals();
		foreach ($object->array_options as $options_key => $value) {
			$this->array_options[$options_key] = $value;
		}

		// Possibility to add external linked objects with hooks
		$this->linked_objects[$this->origin] = $this->origin_id;
		if (!empty($object->other_linked_objects) && is_array($object->other_linked_objects)) {
			$this->linked_objects = array_merge($this->linked_objects, $object->other_linked_objects);
		}

		$ret = $this->create($user);

		if ($ret > 0) {
			// Actions hooked (by external module)
			$hookmanager->initHooks(array('invoicedao'));

			$parameters = array('objFrom' => $object);
			$action = '';
			$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->setErrorsFromObject($hookmanager);
				$error++;
			}

			if (!$error) {
				return 1;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}

	/**
	 *  Load an object from a contract and create a new invoice into database
	 *
	 *  @param      Facture			$object         	Object source
	 *  @param		User			$user				Object user
	 * 	@param		int[]			$lines				Ids of lines to use for invoice. If empty, all lines will be used.
	 *  @return     int             					Return integer <0 if KO, 0 if nothing done, 1 if OK
	 */
	public function createFromContract($object, User $user, $lines = array())
	{
		global $conf, $hookmanager;

		$error = 0;

		// Closed order
		$this->date = dol_now();
		$this->source = 0;

		$use_all_lines = empty($lines);
		$num = count($object->lines);
		for ($i = 0; $i < $num; $i++) {
			if (!$use_all_lines && !in_array($object->lines[$i]->id, $lines)) {
				continue;
			}

			$line = new FactureLigne($this->db);

			$line->libelle = $object->lines[$i]->libelle; // deprecated
			$line->label			= $object->lines[$i]->label;
			$line->desc				= $object->lines[$i]->desc;
			$line->subprice			= $object->lines[$i]->subprice;
			$line->total_ht			= $object->lines[$i]->total_ht;
			$line->total_tva		= $object->lines[$i]->total_tva;
			$line->total_localtax1	= $object->lines[$i]->total_localtax1;
			$line->total_localtax2	= $object->lines[$i]->total_localtax2;
			$line->total_ttc		= $object->lines[$i]->total_ttc;
			$line->vat_src_code = $object->lines[$i]->vat_src_code;
			$line->tva_tx = $object->lines[$i]->tva_tx;
			$line->localtax1_tx		= $object->lines[$i]->localtax1_tx;
			$line->localtax2_tx		= $object->lines[$i]->localtax2_tx;
			$line->qty = $object->lines[$i]->qty;
			$line->fk_remise_except = $object->lines[$i]->fk_remise_except;
			$line->remise_percent = $object->lines[$i]->remise_percent;
			$line->fk_product = $object->lines[$i]->fk_product;
			$line->info_bits = $object->lines[$i]->info_bits;
			$line->product_type		= $object->lines[$i]->product_type;
			$line->rang = $object->lines[$i]->rang;
			$line->special_code		= $object->lines[$i]->special_code;
			$line->fk_parent_line = $object->lines[$i]->fk_parent_line;
			$line->fk_unit = $object->lines[$i]->fk_unit;
			$line->date_start = $object->lines[$i]->date_start;
			$line->date_end = $object->lines[$i]->date_end;

			// Multicurrency
			$line->fk_multicurrency = $object->lines[$i]->fk_multicurrency;
			$line->multicurrency_code = $object->lines[$i]->multicurrency_code;
			$line->multicurrency_subprice = $object->lines[$i]->multicurrency_subprice;
			$line->multicurrency_total_ht = $object->lines[$i]->multicurrency_total_ht;
			$line->multicurrency_total_tva = $object->lines[$i]->multicurrency_total_tva;
			$line->multicurrency_total_ttc = $object->lines[$i]->multicurrency_total_ttc;

			$line->fk_fournprice = $object->lines[$i]->fk_fournprice;
			$marginInfos			= getMarginInfos($object->lines[$i]->subprice, $object->lines[$i]->remise_percent, $object->lines[$i]->tva_tx, $object->lines[$i]->localtax1_tx, $object->lines[$i]->localtax2_tx, $object->lines[$i]->fk_fournprice, $object->lines[$i]->pa_ht);
			$line->pa_ht			= $marginInfos[0];

			// get extrafields from original line
			$object->lines[$i]->fetch_optionals();
			foreach ($object->lines[$i]->array_options as $options_key => $value) {
				$line->array_options[$options_key] = $value;
			}

			$this->lines[$i] = $line;
		}

		$this->socid                = $object->socid;
		$this->fk_project           = $object->fk_project;
		$this->fk_account = $object->fk_account;
		$this->cond_reglement_id    = $object->cond_reglement_id;
		$this->mode_reglement_id    = $object->mode_reglement_id;
		$this->availability_id      = $object->availability_id;
		$this->demand_reason_id     = $object->demand_reason_id;
		$this->delivery_date        = $object->delivery_date;
		$this->fk_delivery_address  = $object->fk_delivery_address; // deprecated
		$this->contact_id           = $object->contact_id;
		$this->ref_client           = $object->ref_client;

		if (!getDolGlobalString('MAIN_DISABLE_PROPAGATE_NOTES_FROM_ORIGIN')) {
			$this->note_private = $object->note_private;
			$this->note_public = $object->note_public;
		}

		$this->module_source = $object->module_source;
		$this->pos_source = $object->pos_source;

		$this->origin = $object->element;
		$this->origin_id = $object->id;

		$this->fk_user_author = $user->id;

		// get extrafields from original line
		$object->fetch_optionals();
		foreach ($object->array_options as $options_key => $value) {
			$this->array_options[$options_key] = $value;
		}

		// Possibility to add external linked objects with hooks
		$this->linked_objects[$this->origin] = $this->origin_id;
		if (!empty($object->other_linked_objects) && is_array($object->other_linked_objects)) {
			$this->linked_objects = array_merge($this->linked_objects, $object->other_linked_objects);
		}

		$ret = $this->create($user);

		if ($ret > 0) {
			// Actions hooked (by external module)
			$hookmanager->initHooks(array('invoicedao'));

			$parameters = array('objFrom' => $object);
			$action = '';
			$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
			if ($reshook < 0) {
				$this->setErrorsFromObject($hookmanager);
				$error++;
			}

			if (!$error) {
				return 1;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}

	/**
	 * Creates a deposit from a proposal or an order by grouping lines by VAT rates
	 *
	 * @param	Propal|Commande	$origin					The original proposal or order
	 * @param	int				$date					Invoice date
	 * @param	int				$payment_terms_id		Invoice payment terms
	 * @param	User			$user					Object user
	 * @param	int<0,1>		$notrigger				1=Does not execute triggers, 0= execute triggers
	 * @param	bool			$autoValidateDeposit	Whether to automatically validate the deposit created
	 * @param	array<string,int|float|string>	$overrideFields	Array of fields to force values
	 * @return	?Facture								The deposit created, or null if error (populates $origin->error in this case)
	 */
	public static function createDepositFromOrigin(CommonObject $origin, $date, $payment_terms_id, User $user, $notrigger = 0, $autoValidateDeposit = false, $overrideFields = array())
	{
		global $conf, $langs, $hookmanager, $action;

		if (! in_array($origin->element, array('propal', 'commande'))) {
			$origin->error = 'ErrorCanOnlyAutomaticallyGenerateADepositFromProposalOrOrder';
			return null;
		}

		if (empty($date)) {
			$origin->error = $langs->trans('ErrorFieldRequired', $langs->transnoentities('DateInvoice'));
			return null;
		}

		require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

		if ($date > (dol_get_last_hour(dol_now('tzuserrel')) + getDolGlobalInt('INVOICE_MAX_FUTURE_DELAY'))) {
			$origin->error = 'ErrorDateIsInFuture';
			return null;
		}

		if ($payment_terms_id <= 0) {
			$origin->error = $langs->trans('ErrorFieldRequired', $langs->transnoentities('PaymentConditionsShort'));
			return null;
		}

		$payment_conditions_deposit_percent = getDictionaryValue('c_payment_term', 'deposit_percent', $origin->cond_reglement_id);

		if (empty($payment_conditions_deposit_percent)) {
			$origin->error = 'ErrorPaymentConditionsNotEligibleToDepositCreation';
			return null;
		}

		if (empty($origin->deposit_percent)) {
			$origin->error = $langs->trans('ErrorFieldRequired', $langs->transnoentities('DepositPercent'));
			return null;
		}

		$deposit = new self($origin->db);
		$deposit->socid = $origin->socid;
		$deposit->type = self::TYPE_DEPOSIT;
		$deposit->fk_project = $origin->fk_project;
		$deposit->ref_client = $origin->ref_client;
		$deposit->date = $date;
		$deposit->mode_reglement_id = $origin->mode_reglement_id;
		$deposit->cond_reglement_id = $payment_terms_id;
		$deposit->availability_id = $origin->availability_id;
		$deposit->demand_reason_id = $origin->demand_reason_id;
		$deposit->fk_account = $origin->fk_account;
		$deposit->fk_incoterms = $origin->fk_incoterms;
		$deposit->location_incoterms = $origin->location_incoterms;
		$deposit->fk_multicurrency = $origin->fk_multicurrency;
		$deposit->multicurrency_code = $origin->multicurrency_code;
		$deposit->multicurrency_tx = $origin->multicurrency_tx;
		$deposit->module_source = $origin->module_source;
		$deposit->pos_source = $origin->pos_source;
		$deposit->model_pdf = 'crabe';

		$modelByTypeConfName = 'FACTURE_ADDON_PDF_' . $deposit->type;

		if (getDolGlobalString($modelByTypeConfName)) {
			$deposit->model_pdf = getDolGlobalString($modelByTypeConfName);
		} elseif (getDolGlobalString('FACTURE_ADDON_PDF')) {
			$deposit->model_pdf = getDolGlobalString('FACTURE_ADDON_PDF');
		}

		if (!getDolGlobalString('MAIN_DISABLE_PROPAGATE_NOTES_FROM_ORIGIN')) {
			$deposit->note_private = $origin->note_private;
			$deposit->note_public = $origin->note_public;
		}

		$deposit->origin = $origin->element;
		$deposit->origin_id = $origin->id;

		$origin->fetch_optionals();

		foreach ($origin->array_options as $extrakey => $value) {
			$deposit->array_options[$extrakey] = $value;
		}

		$deposit->linked_objects[$deposit->origin] = $deposit->origin_id;

		foreach ($overrideFields as $key => $value) {
			$deposit->$key = $value;
		}

		$deposit->context['createdepositfromorigin'] = 'createdepositfromorigin';

		$origin->db->begin();

		// Facture::create() also imports contact from origin
		$createReturn = $deposit->create($user, $notrigger);

		if ($createReturn <= 0) {
			$origin->db->rollback();
			$origin->error = $deposit->error;
			$origin->errors = $deposit->errors;
			return null;
		}

		$amount_ttc_diff = 0;
		$amountdeposit = array();
		$descriptions = array();

		if (getDolGlobalString('MAIN_DEPOSIT_MULTI_TVA')) {
			$amount = $origin->total_ttc * ($origin->deposit_percent / 100);

			$TTotalByTva = array();
			foreach ($origin->lines as &$line) {
				if (!empty($line->special_code)) {
					continue;
				}
				$key = $line->tva_tx;
				if (!array_key_exists($key, $TTotalByTva)) {
					$TTotalByTva[$key] = 0;
					$descriptions[$key] = '';
				}
				$TTotalByTva[$key] += $line->total_ttc;
				$descriptions[$key] .= '<li>' . (!empty($line->product_ref) ? $line->product_ref . ' - ' : '');
				$descriptions[$key] .= (!empty($line->product_label) ? $line->product_label . ' - ' : '');
				$descriptions[$key] .= $langs->trans('Qty') . ' : ' . $line->qty;
				$descriptions[$key] .= ' - ' . $langs->trans('TotalHT') . ' : ' . price($line->total_ht) . '</li>';
			}

			foreach ($TTotalByTva as $tva => &$total) {
				$coef = $total / $origin->total_ttc; // Calc coef
				$am = $amount * $coef;
				$amount_ttc_diff += $am;
				$amountdeposit[$tva] += $am / (1 + $tva / 100); // Convert into HT for the addline
			}
		} else {
			$totalamount = 0;
			$lines = $origin->lines;
			$numlines = count($lines);
			for ($i = 0; $i < $numlines; $i++) {
				if (empty($lines[$i]->qty)) {
					continue; // We discard qty=0, it is an option
				}
				if (!empty($lines[$i]->special_code)) {
					continue; // We discard special_code (frais port, ecotaxe, option, ...)
				}

				$totalamount += $lines[$i]->total_ht; // Fixme : is it not for the customer ? Shouldn't we take total_ttc ?
				$tva_tx = $lines[$i]->tva_tx;
				$amountdeposit[$tva_tx] += ((float) $lines[$i]->total_ht * (float) $origin->deposit_percent) / 100;
				$descriptions[$tva_tx] .= '<li>' . (!empty($lines[$i]->product_ref) ? $lines[$i]->product_ref . ' - ' : '');
				$descriptions[$tva_tx] .= (!empty($lines[$i]->product_label) ? $lines[$i]->product_label . ' - ' : '');
				$descriptions[$tva_tx] .= $langs->trans('Qty') . ' : ' . $lines[$i]->qty;
				$descriptions[$tva_tx] .= ' - ' . $langs->trans('TotalHT') . ' : ' . price($lines[$i]->total_ht) . '</li>';
			}

			if ($totalamount == 0) {
				$amountdeposit[0] = 0;
			}

			$amount_ttc_diff = $amountdeposit[0];
		}

		foreach ($amountdeposit as $tva => $amount) {
			if (empty($amount)) {
				continue;
			}

			$descline = '(DEPOSIT) ('. $origin->deposit_percent .'%) - '.$origin->ref;

			// Hidden conf
			if (getDolGlobalString('INVOICE_DEPOSIT_VARIABLE_MODE_DETAIL_LINES_IN_DESCRIPTION') && !empty($descriptions[$tva])) {
				$descline .= '<ul>' . $descriptions[$tva] . '</ul>';
			}

			$addlineResult = $deposit->addline(
				$descline,
				$amount, // subprice
				1, // quantity
				$tva, // vat rate
				0, // localtax1_tx
				0, // localtax2_tx
				(!getDolGlobalString('INVOICE_PRODUCTID_DEPOSIT') ? 0 : $conf->global->INVOICE_PRODUCTID_DEPOSIT), // fk_product
				0, // remise_percent
				0, // date_start
				0, // date_end
				0,
				0, // info_bits
				0,
				'HT',
				0,
				0, // product_type
				1,
				0, // special_code
				$deposit->origin,
				0,
				0,
				0,
				0
				//,$langs->trans('Deposit') //Deprecated
			);

			if ($addlineResult < 0) {
				$origin->db->rollback();
				$origin->error = $deposit->error;
				$origin->errors = $deposit->errors;
				return null;
			}
		}

		$diff = $deposit->total_ttc - $amount_ttc_diff;

		if (getDolGlobalString('MAIN_DEPOSIT_MULTI_TVA') && $diff != 0) {
			$deposit->fetch_lines();
			$subprice_diff = $deposit->lines[0]->subprice - $diff / (1 + $deposit->lines[0]->tva_tx / 100);

			$updatelineResult = $deposit->updateline(
				$deposit->lines[0]->id,
				$deposit->lines[0]->desc,
				$subprice_diff,
				$deposit->lines[0]->qty,
				$deposit->lines[0]->remise_percent,
				$deposit->lines[0]->date_start,
				$deposit->lines[0]->date_end,
				$deposit->lines[0]->tva_tx,
				0,
				0,
				'HT',
				$deposit->lines[0]->info_bits,
				$deposit->lines[0]->product_type,
				0,
				0,
				0,
				$deposit->lines[0]->pa_ht,
				$deposit->lines[0]->label,
				0,
				array(),
				100
			);

			if ($updatelineResult < 0) {
				$origin->db->rollback();
				$origin->error = $deposit->error;
				$origin->errors = $deposit->errors;
				return null;
			}
		}

		$hookmanager->initHooks(array('invoicedao'));

		$parameters = array('objFrom' => $origin);
		$reshook = $hookmanager->executeHooks('createFrom', $parameters, $deposit, $action); // Note that $action and $object may have been
		// modified by hook
		if ($reshook < 0) {
			$origin->db->rollback();
			$origin->error = $hookmanager->error;
			$origin->errors = $hookmanager->errors;
			return null;
		}

		if (!empty($autoValidateDeposit)) {
			$validateReturn = $deposit->validate($user, '', 0, $notrigger);

			if ($validateReturn < 0) {
				$origin->db->rollback();
				$origin->error = $deposit->error;
				$origin->errors = $deposit->errors;
				return null;
			}
		}

		unset($deposit->context['createdepositfromorigin']);

		$origin->db->commit();

		return $deposit;
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
		global $conf, $langs, $mysoc, $user;

		$langs->load('bills');

		$datas = [];
		$moretitle = $params['moretitle'] ?? '';

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}

		if ($user->hasRight("facture", "read")) {
			$datas['picto'] = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->trans("Invoice").'</u>';

			$datas['picto'] .= '&nbsp;'.$this->getLibType(1);

			// Complete datas
			if (!empty($params['fromajaxtooltip']) && !isset($this->totalpaid)) {
				// Load the totalpaid field
				$this->totalpaid = $this->getSommePaiement(0);
			}
			if (isset($this->status) && isset($this->totalpaid)) {
				$datas['picto'] .= ' '.$this->getLibStatut(5, $this->totalpaid);
			}
			if ($moretitle) {
				$datas['picto'] .= ' - '.$moretitle;
			}
			if (!empty($this->ref)) {
				$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
			}
			if (!empty($this->ref_customer)) {
				$datas['refcustomer'] = '<br><b>'.$langs->trans('RefCustomer').':</b> '.$this->ref_customer;
			}
			if (!empty($this->date)) {
				$datas['date'] = '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($this->date, 'day');
			}
			if (!empty($this->total_ht)) {
				$datas['amountht'] = '<br><b>'.$langs->trans('AmountHT').':</b> '.price($this->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_tva)) {
				$datas['amountvat'] = '<br><b>'.$langs->trans('AmountVAT').':</b> '.price($this->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->revenuestamp) && $this->revenuestamp != 0) {
				$datas['amountrevenustamp'] = '<br><b>'.$langs->trans('RevenueStamp').':</b> '.price($this->revenuestamp, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_localtax1) && $this->total_localtax1 != 0) {
				// We keep test != 0 because $this->total_localtax1 can be '0.00000000'
				$datas['amountlt1'] = '<br><b>'.$langs->transcountry('AmountLT1', $mysoc->country_code).':</b> '.price($this->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_localtax2) && $this->total_localtax2 != 0) {
				$datas['amountlt2'] = '<br><b>'.$langs->transcountry('AmountLT2', $mysoc->country_code).':</b> '.price($this->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency);
			}
			if (!empty($this->total_ttc)) {
				$datas['amountttc'] = '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($this->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
			}
		}

		return $datas;
	}

	/**
	 *  Return clickable link of object (with eventually picto)
	 *
	 *  @param	int		$withpicto       			Add picto into link
	 *  @param  string	$option          			Where point the link
	 *  @param  int		$max             			Maxlength of ref
	 *  @param  int		$short           			1=Return just URL
	 *  @param  string  $moretitle       			Add more text to title tooltip
	 *  @param	int  	$notooltip		 			1=Disable tooltip
	 *  @param  int     $addlinktonotes  			1=Add link to notes
	 *  @param  int     $save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param  string  $target                     Target of link ('', '_self', '_blank', '_parent', '_backoffice', ...)
	 *  @return string 			         			String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $addlinktonotes = 0, $save_lastsearch_value = -1, $target = '')
	{
		global $langs, $conf, $user;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		if ($option == 'withdraw') {
			$url = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$this->id;
		} else {
			$url = DOL_URL_ROOT.'/compta/facture/card.php?id='.$this->id;
		}

		if (!$user->hasRight("facture", "read")) {
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

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}

		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'moretitle' => $moretitle,
			'option' => $option,
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

		$linkclose = ($target ? ' target="'.$target.'"' : '');
		if (empty($notooltip) && $user->hasRight("facture", "read")) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("Invoice");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($option == 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($picto ? $picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= ($max ? dol_trunc($this->ref, $max) : $this->ref);
		}
		$result .= $linkend;

		if ($addlinktonotes) {
			$txttoshow = ($user->socid > 0 ? $this->note_public : $this->note_private);
			if ($txttoshow) {
				//$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow, 1);
				$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.$txttoshow;
				$result .= ' <span class="note inline-block">';
				$result .= '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$this->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow, 1, 1).'">';
				$result .= img_picto('', 'note');
				$result .= '</a>';
				//$result.=img_picto($langs->trans("ViewNote"),'object_generic');
				//$result.='</a>';
				$result .= '</span>';
			}
		}

		global $action, $hookmanager;
		$hookmanager->initHooks(array('invoicedao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result, 'notooltip' => $notooltip, 'addlinktonotes' => $addlinktonotes, 'save_lastsearch_value' => $save_lastsearch_value, 'target' => $target);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *	Get object from database. Get also lines.
	 *
	 *	@param      int		$rowid       		Id of object to load
	 * 	@param		string	$ref				Reference of invoice
	 * 	@param		string	$ref_ext			External reference of invoice
	 * 	@param		int		$notused			Not used
	 *  @param		bool	$fetch_situation	Load also the previous and next situation invoice into $tab_previous_situation_invoice and $tab_next_situation_invoice
	 *	@return     int         				>0 if OK, <0 if KO, 0 if not found
	 */
	public function fetch($rowid, $ref = '', $ref_ext = '', $notused = 0, $fetch_situation = false)
	{
		if (empty($rowid) && empty($ref) && empty($ref_ext)) {
			return -1;
		}

		$sql = 'SELECT f.rowid, f.entity, f.ref, f.ref_client, f.ref_ext, f.type, f.subtype, f.fk_soc';
		$sql .= ', f.total_tva, f.localtax1, f.localtax2, f.total_ht, f.total_ttc, f.revenuestamp';
		$sql .= ', f.datef as df, f.date_pointoftax';
		$sql .= ', f.date_lim_reglement as dlr';
		$sql .= ', f.datec as datec';
		$sql .= ', f.date_valid as datev';
		$sql .= ', f.tms as datem';
		$sql .= ', f.note_private, f.note_public, f.fk_statut as status, f.paye, f.close_code, f.close_note, f.fk_user_author, f.fk_user_valid, f.fk_user_modif, f.model_pdf, f.last_main_doc';
		$sql .= ', f.fk_facture_source, f.fk_fac_rec_source';
		$sql .= ', f.fk_mode_reglement, f.fk_cond_reglement, f.fk_projet as fk_project, f.extraparams';
		$sql .= ', f.situation_cycle_ref, f.situation_counter, f.situation_final';
		$sql .= ', f.fk_account';
		$sql .= ", f.fk_multicurrency, f.multicurrency_code, f.multicurrency_tx, f.multicurrency_total_ht, f.multicurrency_total_tva, f.multicurrency_total_ttc";
		$sql .= ', p.code as mode_reglement_code, p.libelle as mode_reglement_libelle';
		$sql .= ', c.code as cond_reglement_code, c.libelle as cond_reglement_libelle, c.libelle_facture as cond_reglement_libelle_doc';
		$sql .= ', f.fk_incoterms, f.location_incoterms';
		$sql .= ', f.module_source, f.pos_source';
		$sql .= ", i.libelle as label_incoterms";
		$sql .= ", f.retained_warranty as retained_warranty, f.retained_warranty_date_limit as retained_warranty_date_limit, f.retained_warranty_fk_cond_reglement as retained_warranty_fk_cond_reglement";
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_payment_term as c ON f.fk_cond_reglement = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as p ON f.fk_mode_reglement = p.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON f.fk_incoterms = i.rowid';

		if ($rowid) {
			$sql .= " WHERE f.rowid = ".((int) $rowid);
		} else {
			$sql .= ' WHERE f.entity IN ('.getEntity('invoice').')'; // Don't use entity if you use rowid
			if ($ref) {
				$sql .= " AND f.ref = '".$this->db->escape($ref)."'";
			}
			if ($ref_ext) {
				$sql .= " AND f.ref_ext = '".$this->db->escape($ref_ext)."'";
			}
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;

				$this->ref					= $obj->ref;
				$this->ref_client			= $obj->ref_client;
				$this->ref_customer			= $obj->ref_client;
				$this->ref_ext				= $obj->ref_ext;
				$this->type					= $obj->type;
				$this->subtype				= $obj->subtype;
				$this->date					= $this->db->jdate($obj->df);
				$this->date_pointoftax		= $this->db->jdate($obj->date_pointoftax);
				$this->date_creation        = $this->db->jdate($obj->datec);
				$this->date_validation		= $this->db->jdate($obj->datev);
				$this->date_modification    = $this->db->jdate($obj->datem);
				$this->datem                = $this->db->jdate($obj->datem);
				$this->total_ht				= $obj->total_ht;
				$this->total_tva			= $obj->total_tva;
				$this->total_localtax1		= $obj->localtax1;
				$this->total_localtax2		= $obj->localtax2;
				$this->total_ttc			= $obj->total_ttc;
				$this->revenuestamp         = $obj->revenuestamp;
				$this->paye                 = $obj->paye;
				$this->close_code			= $obj->close_code;
				$this->close_note			= $obj->close_note;

				$this->socid = $obj->fk_soc;
				$this->thirdparty = null; // Clear if another value was already set by fetch_thirdparty

				$this->fk_project = $obj->fk_project;
				$this->project = null; // Clear if another value was already set by fetch_projet

				$this->statut = $obj->status;	// deprecated
				$this->status = $obj->status;

				$this->date_lim_reglement = $this->db->jdate($obj->dlr);
				$this->mode_reglement_id	= $obj->fk_mode_reglement;
				$this->mode_reglement_code	= $obj->mode_reglement_code;
				$this->mode_reglement		= $obj->mode_reglement_libelle;
				$this->cond_reglement_id	= $obj->fk_cond_reglement;
				$this->cond_reglement_code	= $obj->cond_reglement_code;
				$this->cond_reglement		= $obj->cond_reglement_libelle;
				$this->cond_reglement_doc = $obj->cond_reglement_libelle_doc;
				$this->fk_account = ($obj->fk_account > 0) ? $obj->fk_account : null;
				$this->fk_facture_source	= $obj->fk_facture_source;
				$this->fk_fac_rec_source	= $obj->fk_fac_rec_source;
				$this->note = $obj->note_private; // deprecated
				$this->note_private = $obj->note_private;
				$this->note_public			= $obj->note_public;
				$this->user_creation_id     = $obj->fk_user_author;
				$this->user_validation_id   = $obj->fk_user_valid;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->fk_user_author       = $obj->fk_user_author;
				$this->fk_user_valid        = $obj->fk_user_valid;
				$this->fk_user_modif        = $obj->fk_user_modif;
				$this->model_pdf = $obj->model_pdf;
				$this->last_main_doc = $obj->last_main_doc;
				$this->situation_cycle_ref  = $obj->situation_cycle_ref;
				$this->situation_counter    = $obj->situation_counter;
				$this->situation_final      = $obj->situation_final;
				$this->retained_warranty    = $obj->retained_warranty;
				$this->retained_warranty_date_limit         = $this->db->jdate($obj->retained_warranty_date_limit);
				$this->retained_warranty_fk_cond_reglement  = $obj->retained_warranty_fk_cond_reglement;

				$this->extraparams = !empty($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();

				//Incoterms
				$this->fk_incoterms         = $obj->fk_incoterms;
				$this->location_incoterms   = $obj->location_incoterms;
				$this->label_incoterms = $obj->label_incoterms;

				$this->module_source = $obj->module_source;
				$this->pos_source = $obj->pos_source;

				// Multicurrency
				$this->fk_multicurrency 		= $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;
				$this->multicurrency_tx 		= $obj->multicurrency_tx;
				$this->multicurrency_total_ht = $obj->multicurrency_total_ht;
				$this->multicurrency_total_tva 	= $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc 	= $obj->multicurrency_total_ttc;

				if (($this->type == self::TYPE_SITUATION || ($this->type == self::TYPE_CREDIT_NOTE && $this->situation_cycle_ref > 0)) && $fetch_situation) {
					$this->fetchPreviousNextSituationInvoice();
				}

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				// Lines
				$this->lines = array();

				$result = $this->fetch_lines();
				if ($result < 0) {
					$this->error = $this->db->error();
					return -3;
				}

				$this->db->free($resql);

				return 1;
			} else {
				$this->error = 'Invoice with id='.$rowid.' or ref='.$ref.' or ref_ext='.$ref_ext.' not found';

				dol_syslog(__METHOD__.$this->error, LOG_WARNING);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load all detailed lines into this->lines
	 *
	 *	@param		int		$only_product	Return only physical products
	 *	@param		int		$loadalsotranslation	Return translation for products
	 *
	 *	@return     int         1 if OK, < 0 if KO
	 */
	public function fetch_lines($only_product = 0, $loadalsotranslation = 0)
	{
		// phpcs:enable
		$this->lines = array();

		$sql = 'SELECT l.rowid, l.fk_facture, l.fk_product, l.fk_parent_line, l.label as custom_label, l.description, l.product_type, l.price, l.qty, l.vat_src_code, l.tva_tx,';
		$sql .= ' l.localtax1_tx, l.localtax2_tx, l.localtax1_type, l.localtax2_type, l.remise_percent, l.fk_remise_except, l.subprice, l.ref_ext,';
		$sql .= ' l.situation_percent, l.fk_prev_id,';
		$sql .= ' l.rang, l.special_code, l.batch, l.fk_warehouse,';
		$sql .= ' l.date_start as date_start, l.date_end as date_end,';
		$sql .= ' l.info_bits, l.total_ht, l.total_tva, l.total_localtax1, l.total_localtax2, l.total_ttc, l.fk_code_ventilation, l.fk_product_fournisseur_price as fk_fournprice, l.buy_price_ht as pa_ht,';
		$sql .= ' l.fk_unit,';
		$sql .= ' l.fk_multicurrency, l.multicurrency_code, l.multicurrency_subprice, l.multicurrency_total_ht, l.multicurrency_total_tva, l.multicurrency_total_ttc,';
		$sql .= ' p.ref as product_ref, p.fk_product_type as fk_product_type, p.label as product_label, p.description as product_desc, p.barcode as product_barcode';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facturedet as l';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product = p.rowid';
		$sql .= ' WHERE l.fk_facture = '.((int) $this->id);
		$sql .= ' ORDER BY l.rang, l.rowid';

		dol_syslog(get_class($this).'::fetch_lines', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num) {
				$objp = $this->db->fetch_object($result);
				$line = new FactureLigne($this->db);

				$line->id               = $objp->rowid;
				$line->rowid = $objp->rowid; // deprecated
				$line->fk_facture       = $objp->fk_facture;
				$line->label            = $objp->custom_label; // deprecated
				$line->desc             = $objp->description; // Description line
				$line->description      = $objp->description; // Description line
				$line->product_type     = $objp->product_type; // Type of line
				$line->ref              = $objp->product_ref; // Ref product
				$line->product_ref      = $objp->product_ref; // Ref product
				$line->libelle          = $objp->product_label; // deprecated
				$line->product_label 	= $objp->product_label; // Label product
				$line->product_barcode  = $objp->product_barcode; // Barcode number product
				$line->product_desc     = $objp->product_desc; // Description product
				$line->fk_product_type  = $objp->fk_product_type; // Type of product
				$line->qty              = $objp->qty;
				$line->subprice         = $objp->subprice;
				$line->ref_ext          = $objp->ref_ext; // line external ref

				$line->vat_src_code = $objp->vat_src_code;
				$line->tva_tx           = $objp->tva_tx;
				$line->localtax1_tx     = $objp->localtax1_tx;
				$line->localtax2_tx     = $objp->localtax2_tx;
				$line->localtax1_type   = $objp->localtax1_type;
				$line->localtax2_type   = $objp->localtax2_type;
				$line->remise_percent   = $objp->remise_percent;
				$line->fk_remise_except = $objp->fk_remise_except;
				$line->fk_product       = $objp->fk_product;
				$line->date_start       = $this->db->jdate($objp->date_start);
				$line->date_end         = $this->db->jdate($objp->date_end);
				$line->info_bits        = $objp->info_bits;
				$line->total_ht         = $objp->total_ht;
				$line->total_tva        = $objp->total_tva;
				$line->total_localtax1  = $objp->total_localtax1;
				$line->total_localtax2  = $objp->total_localtax2;
				$line->total_ttc        = $objp->total_ttc;

				$line->fk_fournprice = $objp->fk_fournprice;
				$marginInfos = getMarginInfos($objp->subprice, $objp->remise_percent, $objp->tva_tx, $objp->localtax1_tx, $objp->localtax2_tx, $line->fk_fournprice, $objp->pa_ht);
				$line->pa_ht = $marginInfos[0];
				$line->marge_tx			= $marginInfos[1];
				$line->marque_tx		= $marginInfos[2];
				$line->rang = $objp->rang;
				$line->special_code = $objp->special_code;
				$line->fk_parent_line = $objp->fk_parent_line;
				$line->situation_percent = $objp->situation_percent;
				$line->fk_prev_id = $objp->fk_prev_id;
				$line->fk_unit = $objp->fk_unit;

				$line->batch = $objp->batch;
				$line->fk_warehouse = $objp->fk_warehouse;

				// Accountancy
				$line->fk_accounting_account = $objp->fk_code_ventilation;

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
	 * Fetch previous and next situations invoices.
	 * Return all previous and next invoices (both standard and credit notes).
	 *
	 * @return	void
	 */
	public function fetchPreviousNextSituationInvoice()
	{
		global $conf;

		$this->tab_previous_situation_invoice = array();
		$this->tab_next_situation_invoice = array();

		$sql = 'SELECT rowid, type, situation_cycle_ref, situation_counter FROM '.MAIN_DB_PREFIX.'facture';
		$sql .= " WHERE rowid <> ".((int) $this->id);
		$sql .= ' AND entity = '.((int) $this->entity);
		$sql .= ' AND situation_cycle_ref = '.(int) $this->situation_cycle_ref;
		$sql .= ' ORDER BY situation_counter ASC';

		dol_syslog(get_class($this).'::fetchPreviousNextSituationInvoice ', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result && $this->db->num_rows($result) > 0) {
			while ($objp = $this->db->fetch_object($result)) {
				$invoice = new Facture($this->db);
				if ($invoice->fetch($objp->rowid) > 0) {
					if ($objp->situation_counter < $this->situation_counter
						|| ($objp->situation_counter == $this->situation_counter && $objp->rowid < $this->id) // This case appear when there are credit notes
					) {
						$this->tab_previous_situation_invoice[] = $invoice;
					} else {
						$this->tab_next_situation_invoice[] = $invoice;
					}
				}
			}
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
		$error = 0;

		// Clean parameters
		if (empty($this->type)) {
			$this->type = self::TYPE_STANDARD;
		}
		if (isset($this->subtype)) {
			$this->subtype = (int) trim((string) $this->subtype);
		}
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->ref_ext)) {
			$this->ref_ext = trim($this->ref_ext);
		}
		if (isset($this->ref_client)) {
			$this->ref_client = trim($this->ref_client);
		}
		if (isset($this->increment)) {
			$this->increment = trim($this->increment);
		}
		if (isset($this->close_code)) {
			$this->close_code = trim($this->close_code);
		}
		if (isset($this->close_note)) {
			$this->close_note = trim($this->close_note);
		}
		if (isset($this->note) || isset($this->note_private)) {
			$this->note = (isset($this->note) ? trim($this->note) : trim($this->note_private)); // deprecated
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
		if (isset($this->retained_warranty)) {
			$this->retained_warranty = (float) $this->retained_warranty;
		}


		// Check parameters
		// Put here code to add control on parameters values

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."facture SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " ref_ext=".(isset($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").",";
		$sql .= " type=".(isset($this->type) ? $this->db->escape($this->type) : "null").",";
		$sql .= " subtype=".(isset($this->subtype) ? $this->db->escape($this->subtype) : "null").",";
		$sql .= " ref_client=".(isset($this->ref_client) ? "'".$this->db->escape($this->ref_client)."'" : "null").",";
		$sql .= " increment=".(isset($this->increment) ? "'".$this->db->escape($this->increment)."'" : "null").",";
		$sql .= " fk_soc=".(isset($this->socid) ? $this->db->escape($this->socid) : "null").",";
		$sql .= " datec=".(strval($this->date_creation) != '' ? "'".$this->db->idate($this->date_creation)."'" : 'null').",";
		$sql .= " datef=".(strval($this->date) != '' ? "'".$this->db->idate($this->date)."'" : 'null').",";
		$sql .= " date_pointoftax=".(strval($this->date_pointoftax) != '' ? "'".$this->db->idate($this->date_pointoftax)."'" : 'null').",";
		$sql .= " date_valid=".(strval($this->date_validation) != '' ? "'".$this->db->idate($this->date_validation)."'" : 'null').",";
		$sql .= " paye=".(isset($this->paye) ? $this->db->escape($this->paye) : 0).",";
		$sql .= " close_code=".(isset($this->close_code) ? "'".$this->db->escape($this->close_code)."'" : "null").",";
		$sql .= " close_note=".(isset($this->close_note) ? "'".$this->db->escape($this->close_note)."'" : "null").",";
		$sql .= " total_tva=".(isset($this->total_tva) ? (float) $this->total_tva : "null").",";
		$sql .= " localtax1=".(isset($this->total_localtax1) ? (float) $this->total_localtax1 : "null").",";
		$sql .= " localtax2=".(isset($this->total_localtax2) ? (float) $this->total_localtax2 : "null").",";
		$sql .= " total_ht=".(isset($this->total_ht) ? (float) $this->total_ht : "null").",";
		$sql .= " total_ttc=".(isset($this->total_ttc) ? (float) $this->total_ttc : "null").",";
		$sql .= " revenuestamp=".((isset($this->revenuestamp) && $this->revenuestamp != '') ? (int) $this->revenuestamp : "null").",";
		$sql .= " fk_statut=".(isset($this->status) ? (int) $this->status : "null").",";
		$sql .= " fk_user_valid=".(isset($this->fk_user_valid) ? (int) $this->fk_user_valid : "null").",";
		$sql .= " fk_facture_source=".(isset($this->fk_facture_source) ? (int) $this->fk_facture_source : "null").",";
		$sql .= " fk_projet=".(isset($this->fk_project) ? (int) $this->fk_project : "null").",";
		$sql .= " fk_cond_reglement=".(isset($this->cond_reglement_id) ? (int) $this->cond_reglement_id : "null").",";
		$sql .= " fk_mode_reglement=".(isset($this->mode_reglement_id) ? (int) $this->mode_reglement_id : "null").",";
		$sql .= " date_lim_reglement=".(strval($this->date_lim_reglement) != '' ? "'".$this->db->idate($this->date_lim_reglement)."'" : 'null').",";
		$sql .= " note_private=".(isset($this->note_private) ? "'".$this->db->escape($this->note_private)."'" : "null").",";
		$sql .= " note_public=".(isset($this->note_public) ? "'".$this->db->escape($this->note_public)."'" : "null").",";
		$sql .= " model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " import_key=".(isset($this->import_key) ? "'".$this->db->escape($this->import_key)."'" : "null").",";
		$sql .= " situation_cycle_ref=".(empty($this->situation_cycle_ref) ? "null" : (int) $this->situation_cycle_ref).",";
		$sql .= " situation_counter=".(empty($this->situation_counter) ? "null" : (int) $this->situation_counter).",";
		$sql .= " situation_final=".(empty($this->situation_final) ? "0" : (int) $this->situation_final).",";
		$sql .= " retained_warranty=".(empty($this->retained_warranty) ? "0" : (float) $this->retained_warranty).",";
		$sql .= " retained_warranty_date_limit=".(strval($this->retained_warranty_date_limit) != '' ? "'".$this->db->idate($this->retained_warranty_date_limit)."'" : 'null').",";
		$sql .= " retained_warranty_fk_cond_reglement=".(isset($this->retained_warranty_fk_cond_reglement) ? (int) $this->retained_warranty_fk_cond_reglement : "null");
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
			$result = $this->call_trigger('BILL_MODIFY', $user);
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


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Add a discount line into an invoice (as an invoice line) using an existing absolute discount (Consume the discount)
	 *
	 *    @param     int	$idremise	Id of absolute discount from table llx_societe_remise_except
	 *    @return    int          		>0 if OK, <0 if KO
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

			$facligne = new FactureLigne($this->db);
			$facligne->fk_facture = $this->id;
			$facligne->fk_remise_except = $remise->id;
			$facligne->desc = $remise->description; // Description ligne
			$facligne->vat_src_code = $remise->vat_src_code;
			$facligne->tva_tx = $remise->tva_tx;
			$facligne->subprice = -$remise->amount_ht;
			$facligne->fk_product = 0; // Id produit predefini
			$facligne->qty = 1;
			$facligne->remise_percent = 0;
			$facligne->rang = -1;
			$facligne->info_bits = 2;

			if (getDolGlobalString('MAIN_ADD_LINE_AT_POSITION')) {
				$facligne->rang = 1;
				$linecount = count($this->lines);
				for ($ii = 1; $ii <= $linecount; $ii++) {
					$this->updateRangOfLine($this->lines[$ii - 1]->id, $ii + 1);
				}
			}

			// Get buy/cost price of invoice that is source of discount
			if ($remise->fk_facture_source > 0) {
				$srcinvoice = new Facture($this->db);
				$srcinvoice->fetch($remise->fk_facture_source);
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmargin.class.php'; // TODO Move this into commonobject
				$formmargin = new FormMargin($this->db);
				$arraytmp = $formmargin->getMarginInfosArray($srcinvoice, false);
				$facligne->pa_ht = $arraytmp['pa_total'];
			}

			$facligne->total_ht  = -$remise->amount_ht;
			$facligne->total_tva = -$remise->amount_tva;
			$facligne->total_ttc = -$remise->amount_ttc;

			$facligne->multicurrency_subprice = -$remise->multicurrency_subprice;
			$facligne->multicurrency_total_ht = -$remise->multicurrency_amount_ht;
			$facligne->multicurrency_total_tva = -$remise->multicurrency_amount_tva;
			$facligne->multicurrency_total_ttc = -$remise->multicurrency_amount_ttc;

			$lineid = $facligne->insert();
			if ($lineid > 0) {
				$result = $this->update_price(1);
				if ($result > 0) {
					// Create link between discount and invoice line
					$result = $remise->link_to_invoice($lineid, 0);
					if ($result < 0) {
						$this->error = $remise->error;
						$this->db->rollback();
						return -4;
					}

					$this->db->commit();
					return 1;
				} else {
					$this->error = $facligne->error;
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $facligne->error;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			return -3;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set customer ref
	 *
	 *	@param     	string	$ref_client		Customer ref
	 *  @param     	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function set_ref_client($ref_client, $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		if (empty($ref_client)) {
			$sql .= ' SET ref_client = NULL';
		} else {
			$sql .= ' SET ref_client = \''.$this->db->escape($ref_client).'\'';
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(__METHOD__.' this->id='.$this->id.', ref_client='.$ref_client, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->error();
			$error++;
		}

		if (!$error) {
			$this->ref_client = $ref_client;
		}

		if (!$notrigger && empty($error)) {
			// Call trigger
			$result = $this->call_trigger('BILL_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$this->ref_client = $ref_client;

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

	/**
	 *	Delete invoice
	 *
	 *	@param     	User	$user      	    User making the deletion.
	 *	@param		int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@param		int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return		int						Return integer <0 if KO, 0=Refused, >0 if OK
	 */
	public function delete($user, $notrigger = 0, $idwarehouse = -1)
	{
		global $langs, $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$rowid = $this->id;

		dol_syslog(get_class($this)."::delete rowid=".$rowid.", ref=".$this->ref.", thirdparty=".(empty($this->thirdparty) ? '' : $this->thirdparty->name), LOG_DEBUG);

		// Test to avoid invoice deletion (allowed if draft)
		$result = $this->is_erasable();

		if ($result <= 0) {
			return 0;
		}

		$error = 0;

		$this->db->begin();

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('BILL_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Removed extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error deleteExtraFields ".$this->error, LOG_ERR);
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
			// If invoice was converted into a discount not yet consumed, we remove discount
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql .= ' WHERE fk_facture_source = '.((int) $rowid);
			$sql .= ' AND fk_facture_line IS NULL';
			$resql = $this->db->query($sql);

			// If invoice has consumed discounts
			$this->fetch_lines();
			$list_rowid_det = array();
			foreach ($this->lines as $key => $invoiceline) {
				$list_rowid_det[] = $invoiceline->id;
			}

			// Consumed discounts are freed
			if (count($list_rowid_det)) {
				$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
				$sql .= ' SET fk_facture = NULL, fk_facture_line = NULL';
				$sql .= ' WHERE fk_facture_line IN ('.$this->db->sanitize(implode(',', $list_rowid_det)).')';

				if (!$this->db->query($sql)) {
					$this->error = $this->db->error()." sql=".$sql;
					$this->errors[] = $this->error;
					$this->db->rollback();
					return -5;
				}
			}

			// Remove other links to the deleted invoice

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'eventorganization_conferenceorboothattendee';
			$sql .= ' SET fk_invoice = NULL';
			$sql .= ' WHERE fk_invoice = '.((int) $rowid);

			if (!$this->db->query($sql)) {
				$this->error = $this->db->error()." sql=".$sql;
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -5;
			}

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'element_time';
			$sql .= ' SET invoice_id = NULL, invoice_line_id = NULL';
			$sql .= ' WHERE invoice_id = '.((int) $rowid);

			if (!$this->db->query($sql)) {
				$this->error = $this->db->error()." sql=".$sql;
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -5;
			}

			// If we decrease stock on invoice validation, we increase back if a warehouse id was provided
			if ($this->type != self::TYPE_DEPOSIT && $result >= 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_BILL') && $idwarehouse != -1) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						$mouvP->setOrigin($this->element, $this->id);
						// We decrease stock for product
						if ($this->type == self::TYPE_CREDIT_NOTE) {
							$result = $mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceDeleteDolibarr", $this->ref));
						} else {
							$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceDeleteDolibarr", $this->ref)); // we use 0 for price, to not change the weighted average value
						}
					}
				}
			}

			// Invoice line extrafileds
			$main = MAIN_DB_PREFIX.'facturedet';
			$ef = $main."_extrafields";
			$sqlef = "DELETE FROM ".$ef." WHERE fk_object IN (SELECT rowid FROM ".$main." WHERE fk_facture = ".((int) $rowid).")";
			// Delete invoice line
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facturedet WHERE fk_facture = '.((int) $rowid);

			if ($this->db->query($sqlef) && $this->db->query($sql) && $this->delete_linked_contact() >= 0) {
				$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'facture WHERE rowid = '.((int) $rowid);

				$resql = $this->db->query($sql);
				if ($resql) {
					// Delete record into ECM index (Note that delete is also done when deleting files with the dol_delete_dir_recursive
					$this->deleteEcmFiles(0); // Deleting files physically is done later with the dol_delete_dir_recursive
					$this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive

					// On efface le repertoire de pdf provisoire
					$ref = dol_sanitizeFileName($this->ref);
					if ($conf->facture->dir_output && !empty($this->ref)) {
						$dir = $conf->facture->dir_output."/".$ref;
						$file = $conf->facture->dir_output."/".$ref."/".$ref.".pdf";
						if (file_exists($file)) {	// We must delete all files before deleting directory
							$ret = dol_delete_preview($this);

							if (!dol_delete_file($file, 0, 0, 0, $this)) { // For triggers
								$langs->load("errors");
								$this->error = $langs->trans("ErrorFailToDeleteFile", $file);
								$this->errors[] = $this->error;
								$this->db->rollback();
								return 0;
							}
						}
						if (file_exists($dir)) {
							if (!dol_delete_dir_recursive($dir)) { // For remove dir and meta
								$langs->load("errors");
								$this->error = $langs->trans("ErrorFailToDeleteDir", $dir);
								$this->errors[] = $this->error;
								$this->db->rollback();
								return 0;
							}
						}
					}

					$this->db->commit();
					return 1;
				} else {
					$this->error = $this->db->lasterror()." sql=".$sql;
					$this->errors[] = $this->error;
					$this->db->rollback();
					return -6;
				}
			} else {
				$this->error = $this->db->lasterror()." sql=".$sql;
				$this->errors[] = $this->error;
				$this->db->rollback();
				return -4;
			}
		} else {
			$this->db->rollback();
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tag the invoice as paid completely (if close_code is filled) => this->fk_statut=2, this->paye=1
	 *  or partially (if close_code filled) + appel trigger BILL_PAYED => this->fk_statut=2, this->paye stay 0
	 *
	 *	@deprecated
	 *  @see setPaid()
	 *  @param	User	$user      	Object user that modify
	 *	@param  string	$close_code	Code set when forcing to set the invoice as fully paid while in practice it is incomplete (because of a discount (fr:escompte) for instance)
	 *	@param  string	$close_note	Comment set when forcing to set the invoice as fully paid while in practice it is incomplete (because of a discount (fr:escompte) for instance)
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function set_paid($user, $close_code = '', $close_note = '')
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_paid is deprecated, use setPaid instead", LOG_NOTICE);
		return $this->setPaid($user, $close_code, $close_note);
	}

	/**
	 *  Tag the invoice as :
	 *  - paid completely (if close_code is not filled) => this->fk_statut=2, this->paye=1
	 *  - or partially (if close_code filled) + appel trigger BILL_PAYED => this->fk_statut=2, this->paye stay 0
	 *
	 *  @param	User	$user      	Object user that modify
	 *	@param  string	$close_code	Code set when forcing to set the invoice as fully paid while in practice it is incomplete (because of a discount (fr:escompte) for instance)
	 *	@param  string	$close_note	Comment set when forcing to set the invoice as fully paid while in practice it is incomplete (because of a discount (fr:escompte) for instance)
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function setPaid($user, $close_code = '', $close_note = '')
	{
		$error = 0;

		if ($this->paye != 1) {
			$this->db->begin();

			$now = dol_now();

			dol_syslog(get_class($this)."::setPaid rowid=".((int) $this->id), LOG_DEBUG);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
			$sql .= ' fk_statut='.self::STATUS_CLOSED;
			if (!$close_code) {
				$sql .= ', paye=1';
			}
			if ($close_code) {
				$sql .= ", close_code='".$this->db->escape($close_code)."'";
			}
			if ($close_note) {
				$sql .= ", close_note='".$this->db->escape($close_note)."'";
			}
			$sql .= ', fk_user_closing = '.((int) $user->id);
			$sql .= ", date_closing = '".$this->db->idate($now)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				// Call trigger
				$result = $this->call_trigger('BILL_PAYED', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			} else {
				$error++;
				$this->error = $this->db->lasterror();
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			return 0;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Tags the invoice as incompletely paid and call the trigger BILL_UNPAYED
	 *	This method is used when a direct debit (fr:prelevement) is refused
	 * 	or when a canceled invoice is reopened.
	 *
	 *	@deprecated
	 *  @see setUnpaid()
	 *  @param	User	$user       Object user that change status
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function set_unpaid($user)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_unpaid is deprecated, use setUnpaid instead", LOG_NOTICE);
		return $this->setUnpaid($user);
	}

	/**
	 *  Tag the invoice as incompletely paid and call the trigger BILL_UNPAYED
	 *	This method is used when a direct debit (fr:prelevement) is refused
	 * 	or when a canceled invoice is reopened.
	 *
	 *  @param	User	$user       Object user that change status
	 *  @return int         		Return integer <0 if KO, >0 if OK
	 */
	public function setUnpaid($user)
	{
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
		$sql .= ' SET paye=0, fk_statut='.self::STATUS_VALIDATED.', close_code=null, close_note=null,';
		$sql .= ' date_closing=null,';
		$sql .= ' fk_user_closing=null';
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setUnpaid", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			$result = $this->call_trigger('BILL_UNPAYED', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		} else {
			$error++;
			$this->error = $this->db->error();
			dol_print_error($this->db);
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
	 *	Tag invoice as canceled, with no payment on it (example for replacement invoice or payment never received) + call trigger BILL_CANCEL
	 *	Warning, if option to decrease stock on invoice was set, this function does not change stock (it might be a cancel because
	 *  of no payment even if merchandises were sent).
	 *
	 *	@deprecated
	 *  @see setCanceled()
	 *	@param	User	$user        	Object user making change
	 *	@param	string	$close_code		Code of closing invoice (CLOSECODE_REPLACED, CLOSECODE_...)
	 *	@param	string	$close_note		Comment
	 *	@return int         			Return integer <0 if KO, >0 if OK
	 */
	public function set_canceled($user, $close_code = '', $close_note = '')
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_canceled is deprecated, use setCanceled instead", LOG_NOTICE);
		return $this->setCanceled($user, $close_code, $close_note);
	}

	/**
	 *	Tag invoice as canceled, with no payment on it (example for replacement invoice or payment never received) + call trigger BILL_CANCEL
	 *	Warning, if option to decrease stock on invoice was set, this function does not change stock (it might be a cancel because
	 *  of no payment even if merchandises were sent).
	 *
	 *	@param	User	$user        	Object user making change
	 *	@param	string	$close_code		Code of closing invoice (CLOSECODE_REPLACED, CLOSECODE_...)
	 *	@param	string	$close_note		Comment
	 *	@return int         			Return integer <0 if KO, >0 if OK
	 */
	public function setCanceled($user, $close_code = '', $close_note = '')
	{
		dol_syslog(get_class($this)."::setCanceled rowid=".((int) $this->id), LOG_DEBUG);

		$this->db->begin();
		$now = dol_now();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET';
		$sql .= ' fk_statut='.self::STATUS_ABANDONED;
		if ($close_code) {
			$sql .= ", close_code='".$this->db->escape($close_code)."'";
		}
		if ($close_note) {
			$sql .= ", close_note='".$this->db->escape($close_note)."'";
		}
		$sql .= ', fk_user_closing = '.((int) $user->id);
		$sql .= ", date_closing = '".$this->db->idate($now)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			// Bound discounts are deducted from the invoice
			// as they have not been used since the invoice is abandoned.
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'societe_remise_except';
			$sql .= ' SET fk_facture = NULL';
			$sql .= ' WHERE fk_facture = '.((int) $this->id);

			$resql = $this->db->query($sql);
			if ($resql) {
				// Call trigger
				$result = $this->call_trigger('BILL_CANCEL', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
				// End call triggers

				$this->db->commit();
				return 1;
			} else {
				$this->error = $this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->error()." sql=".$sql;
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 * Tag invoice as validated + call trigger BILL_VALIDATE
	 * Object must have lines loaded with fetch_lines
	 *
	 * @param	User	$user           Object user that validate
	 * @param   string	$force_number	Reference to force on invoice
	 * @param	int		$idwarehouse	Id of warehouse to use for stock decrease if option to decrease on stock is on (0=no decrease)
	 * @param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 * @param	int		$batch_rule		0=do not decrement batch, else batch rule to use: 1=take lot/serial ordered by sellby and eatby dates
	 * @return	int						Return integer <0 if KO, 0=Nothing done because invoice is not a draft, >0 if OK
	 */
	public function validate($user, $force_number = '', $idwarehouse = 0, $notrigger = 0, $batch_rule = 0)
	{
		global $conf, $langs, $mysoc;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$productStatic = null;
		$warehouseStatic = null;
		$productbatch = null;
		if ($batch_rule > 0) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
			$productStatic = new Product($this->db);
			$warehouseStatic = new Entrepot($this->db);
			$productbatch = new Productbatch($this->db);
		}

		$now = dol_now();

		$error = 0;
		dol_syslog(get_class($this).'::validate user='.$user->id.', force_number='.$force_number.', idwarehouse='.$idwarehouse);

		// Force to have object complete for checks
		$this->fetch_thirdparty();
		$this->fetch_lines();

		// Check parameters
		if ($this->status != self::STATUS_DRAFT) {
			dol_syslog(get_class($this)."::validate Current status is not draft. operation canceled.", LOG_WARNING);
			return 0;
		}
		if (count($this->lines) <= 0) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorObjectMustHaveLinesToBeValidated", $this->ref);
			return -1;
		}
		if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('facture', 'creer'))
		|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('facture', 'invoice_advance', 'validate'))) {
			$this->error = 'Permission denied';
			dol_syslog(get_class($this)."::validate ".$this->error.' MAIN_USE_ADVANCED_PERMS=' . getDolGlobalString('MAIN_USE_ADVANCED_PERMS'), LOG_ERR);
			return -1;
		}
		if ((preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) &&	// empty should not happened, but when it occurs, the test save life
			getDolGlobalString('FAC_FORCE_DATE_VALIDATION')						// If option enabled, we force invoice date
		) {
			$this->date = dol_now();
			$this->date_lim_reglement = $this->calculate_date_lim_reglement();
		}
		if (getDolGlobalString('INVOICE_CHECK_POSTERIOR_DATE')) {
			$last_of_type = $this->willBeLastOfSameType(true);
			if (!$last_of_type[0]) {
				$this->error = $langs->transnoentities("ErrorInvoiceIsNotLastOfSameType", $this->ref, dol_print_date($this->date, 'day'), dol_print_date($last_of_type[1], 'day'));
				return -1;
			}
		}

		// Check for mandatory fields in thirdparty (defined into setup)
		if (!empty($this->thirdparty) && is_object($this->thirdparty)) {
			$array_to_check = array('IDPROF1', 'IDPROF2', 'IDPROF3', 'IDPROF4', 'IDPROF5', 'IDPROF6', 'EMAIL', 'ACCOUNTANCY_CODE_CUSTOMER');
			foreach ($array_to_check as $key) {
				$keymin = strtolower($key);
				if (!property_exists($this->thirdparty, $keymin)) {
					continue;
				}
				$vallabel = $this->thirdparty->$keymin;

				$i = (int) preg_replace('/[^0-9]/', '', $key);
				if ($i > 0) {
					if ($this->thirdparty->isACompany()) {
						// Check for mandatory prof id (but only if country is other than ours)
						if ($mysoc->country_id > 0 && $this->thirdparty->country_id == $mysoc->country_id) {
							$idprof_mandatory = 'SOCIETE_'.$key.'_INVOICE_MANDATORY';
							if (!$vallabel && getDolGlobalString($idprof_mandatory)) {
								$langs->load("errors");
								$this->error = $langs->trans('ErrorProdIdIsMandatory', $langs->transcountry('ProfId'.$i, $this->thirdparty->country_code)).' ('.$langs->trans("ForbiddenBySetupRules").') ['.$langs->trans('Company').' : '.$this->thirdparty->name.']';
								dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
								return -1;
							}
						}
					}
				} else {
					if ($key == 'EMAIL') {
						// Check for mandatory
						if (getDolGlobalString('SOCIETE_EMAIL_INVOICE_MANDATORY') && !isValidEmail($this->thirdparty->email)) {
							$langs->load("errors");
							$this->error = $langs->trans("ErrorBadEMail", $this->thirdparty->email).' ('.$langs->trans("ForbiddenBySetupRules").') ['.$langs->trans('Company').' : '.$this->thirdparty->name.']';
							dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
							return -1;
						}
					}
					if ($key == 'ACCOUNTANCY_CODE_CUSTOMER') {
						// Check for mandatory
						if (getDolGlobalString('SOCIETE_ACCOUNTANCY_CODE_CUSTOMER_INVOICE_MANDATORY') && empty($this->thirdparty->code_compta_client)) {
							$langs->load("errors");
							$this->error = $langs->trans("ErrorAccountancyCodeCustomerIsMandatory", $this->thirdparty->name).' ('.$langs->trans("ForbiddenBySetupRules").')';
							dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
							return -1;
						}
					}
				}
			}
		}

		// Check for mandatory fields in $this
		$array_to_check = array('REF_CLIENT' => 'RefCustomer');
		foreach ($array_to_check as $key => $val) {
			$keymin = strtolower($key);
			$vallabel = $this->$keymin;

			// Check for mandatory
			$keymandatory = 'INVOICE_'.$key.'_MANDATORY_FOR_VALIDATION';
			if (!$vallabel && getDolGlobalString($keymandatory)) {
				$langs->load("errors");
				$error++;
				$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val));
			}
		}

		$this->db->begin();

		// Check parameters
		if ($this->type == self::TYPE_REPLACEMENT) {		// if this is a replacement invoice
			// Check that source invoice is known
			if ($this->fk_facture_source <= 0) {
				$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InvoiceReplacement"));
				$this->db->rollback();
				return -10;
			}

			// Load source invoice that has been replaced
			$facreplaced = new Facture($this->db);
			$result = $facreplaced->fetch($this->fk_facture_source);
			if ($result <= 0) {
				$this->error = $langs->trans("ErrorBadInvoice");
				$this->db->rollback();
				return -11;
			}

			// Check that source invoice not already replaced by another one.
			$idreplacement = $facreplaced->getIdReplacingInvoice('validated');
			if ($idreplacement && $idreplacement != $this->id) {
				$facreplacement = new Facture($this->db);
				$facreplacement->fetch($idreplacement);
				$this->error = $langs->trans("ErrorInvoiceAlreadyReplaced", $facreplaced->ref, $facreplacement->ref);
				$this->db->rollback();
				return -12;
			}

			$result = $facreplaced->setCanceled($user, self::CLOSECODE_REPLACED, '');
			if ($result < 0) {
				$this->error = $facreplaced->error;
				$this->db->rollback();
				return -13;
			}
		}

		// Define new ref
		if ($force_number) {
			$num = $force_number;
		} elseif (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref)) { // empty should not happened, but when it occurs, the test save life
			if (getDolGlobalString('FAC_FORCE_DATE_VALIDATION')) {	// If option enabled, we force invoice date
				$this->date = dol_now();
				$this->date_lim_reglement = $this->calculate_date_lim_reglement();
			}
			$num = $this->getNextNumRef($this->thirdparty);
		} else {
			$num = $this->ref;
		}

		$this->newref = dol_sanitizeFileName($num);

		if ($num) {
			$this->update_price(1);

			// Validate
			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql .= " SET ref = '".$this->db->escape($num)."', fk_statut = ".self::STATUS_VALIDATED.", fk_user_valid = ".($user->id > 0 ? $user->id : "null").", date_valid = '".$this->db->idate($now)."'";
			if (getDolGlobalString('FAC_FORCE_DATE_VALIDATION')) {	// If option enabled, we force invoice date
				$sql .= ", datef='".$this->db->idate($this->date)."'";
				$sql .= ", date_lim_reglement='".$this->db->idate($this->date_lim_reglement)."'";
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::validate", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$error++;
			}

			// We check if the invoice was provisional
			/*
			if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref))) {
				// La verif qu'une remise n'est pas utilisee 2 fois est faite au moment de l'insertion de ligne
			}
			*/

			if (!$error) {
				// Define third party as a customer
				$result = $this->thirdparty->setAsCustomer();

				// If active (STOCK_CALCULATE_ON_BILL), we decrement the main product and its components at invoice validation
				if ($this->type != self::TYPE_DEPOSIT && $result >= 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_BILL') && $idwarehouse > 0) {
					require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
					$langs->load("agenda");

					// Loop on each line
					$cpt = count($this->lines);
					for ($i = 0; $i < $cpt; $i++) {
						if ($this->lines[$i]->fk_product > 0) {
							$mouvP = new MouvementStock($this->db);
							$mouvP->origin = &$this;	// deprecated
							$mouvP->setOrigin($this->element, $this->id);

							// We decrease stock for product
							if ($this->type == self::TYPE_CREDIT_NOTE) {
								// TODO If warehouseid has been set into invoice line, we should use this value in priority
								// $newidwarehouse = $this->lines[$i]->fk_warehouse ? $this->lines[$i]->fk_warehouse : $idwarehouse;
								$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceValidatedInDolibarr", $num), '', '', $this->lines[$i]->batch);
								if ($result < 0) {
									$error++;
									$this->error = $mouvP->error;
									$this->errors = array_merge($this->errors, $mouvP->errors);
								}
							} else {
								// TODO If warehouseid has been set into invoice line, we should use this value in priority
								// $newidwarehouse = $this->lines[$i]->fk_warehouse ? $this->lines[$i]->fk_warehouse : $idwarehouse;

								$is_batch_line = false;
								if ($batch_rule > 0) {
									$productStatic->fetch($this->lines[$i]->fk_product);
									if ($productStatic->hasbatch() && is_object($productbatch)) {
										$is_batch_line = true;
										$product_qty_remain = $this->lines[$i]->qty;

										$sortfield = null;
										$sortorder = null;
										// find lot/serial by sellby (DLC) and eatby dates (DLUO) first
										if ($batch_rule == Productbatch::BATCH_RULE_SELLBY_EATBY_DATES_FIRST) {
											$sortfield = 'pl.sellby,pl.eatby,pb.qty,pl.rowid';
											$sortorder = 'ASC,ASC,ASC,ASC';
										}

										$resBatchList = $productbatch->findAllForProduct($productStatic->id, $idwarehouse, (getDolGlobalInt('STOCK_ALLOW_NEGATIVE_TRANSFER') ? null : 0), $sortfield, $sortorder);
										if (!is_array($resBatchList)) {
											$error++;
											$this->error = $this->db->lasterror();
										}

										if (!$error) {
											$batchList = $resBatchList;
											if (empty($batchList)) {
												$error++;
												$langs->load('errors');
												$warehouseStatic->fetch($idwarehouse);
												$this->error = $langs->trans('ErrorBatchNoFoundForProductInWarehouse', $productStatic->label, $warehouseStatic->ref);
												dol_syslog(__METHOD__.' Error: '.$langs->transnoentitiesnoconv('ErrorBatchNoFoundForProductInWarehouse', $productStatic->label, $warehouseStatic->ref), LOG_ERR);
											}

											foreach ($batchList as $batch) {
												if ($batch->qty <= 0) {
													continue; // try to decrement only batches have positive quantity first
												}

												// enough quantity in this batch
												if ($batch->qty >= $product_qty_remain) {
													$product_batch_qty = $product_qty_remain;
												} else {
													// not enough (take all in batch)
													$product_batch_qty = $batch->qty;
												}
												$result = $mouvP->livraison($user, $productStatic->id, $idwarehouse, $product_batch_qty, $this->lines[$i]->subprice, $langs->trans('InvoiceValidatedInDolibarr', $num), '', '', '', $batch->batch);
												if ($result < 0) {
													$error++;
													$this->error = $mouvP->error;
													$this->errors = array_merge($this->errors, $mouvP->errors);
													break;
												}

												$product_qty_remain -= $product_batch_qty;
												// all product quantity was decremented
												if ($product_qty_remain <= 0) {
													break;
												}
											}

											if (!$error && $product_qty_remain > 0) {
												if (getDolGlobalInt('STOCK_ALLOW_NEGATIVE_TRANSFER')) {
													// take in the first batch
													$batch = $batchList[0];
													$result = $mouvP->livraison($user, $productStatic->id, $idwarehouse, $product_qty_remain, $this->lines[$i]->subprice, $langs->trans('InvoiceValidatedInDolibarr', $num), '', '', '', $batch->batch);
													if ($result < 0) {
														$error++;
														$this->error = $mouvP->error;
														$this->errors = array_merge($this->errors, $mouvP->errors);
													}
												} else {
													$error++;
													$langs->load('errors');
													$warehouseStatic->fetch($idwarehouse);
													$this->error = $langs->trans('ErrorBatchNoFoundEnoughQuantityForProductInWarehouse', $productStatic->label, $warehouseStatic->ref);
													dol_syslog(__METHOD__.' Error: '.$langs->transnoentitiesnoconv('ErrorBatchNoFoundEnoughQuantityForProductInWarehouse', $productStatic->label, $warehouseStatic->ref), LOG_ERR);
												}
											}
										}
									}
								}

								if (!$is_batch_line) {	// If stock move not yet processed
									$result = $mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceValidatedInDolibarr", $num));
									if ($result < 0) {
										$error++;
										$this->error = $mouvP->error;
										$this->errors = array_merge($this->errors, $mouvP->errors);
									}
								}
							}
						}
					}
				}
			}

			/*
			 * Set situation_final to 0 if is a credit note and the invoice source is a invoice situation (case when invoice situation is at 100%)
			 * So we can continue to create new invoice situation
			 */
			if (!$error && $this->type == self::TYPE_CREDIT_NOTE && $this->fk_facture_source > 0) {
				$invoice_situation = new Facture($this->db);
				$result = $invoice_situation->fetch($this->fk_facture_source);
				if ($result > 0 && $invoice_situation->type == self::TYPE_SITUATION && $invoice_situation->situation_final == 1) {
					$invoice_situation->situation_final = 0;
					// Disable triggers because module can force situation_final to 1 by triggers (ex: SubTotal)
					$result = $invoice_situation->setFinal($user, 1);
				}
				if ($result < 0) {
					$this->error = $invoice_situation->error;
					$this->errors = array_merge($this->errors, $invoice_situation->errors);
					$error++;
				}
			}

			// Trigger calls
			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('BILL_VALIDATE', $user);
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
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filename = CONCAT('".$this->db->escape($this->newref)."', SUBSTR(filename, ".(strlen($this->ref) + 1).")), filepath = 'facture/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%' AND filepath = 'facture/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}
					$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'facture/".$this->db->escape($this->newref)."'";
					$sql .= " WHERE filepath = 'facture/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
					}

					// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
					$oldref = dol_sanitizeFileName($this->ref);
					$newref = dol_sanitizeFileName($num);
					$dirsource = $conf->facture->dir_output.'/'.$oldref;
					$dirdest = $conf->facture->dir_output.'/'.$newref;
					if (!$error && file_exists($dirsource)) {
						dol_syslog(get_class($this)."::validate rename dir ".$dirsource." into ".$dirdest);

						if (@rename($dirsource, $dirdest)) {
							dol_syslog("Rename ok");
							// Rename docs starting with $oldref with $newref
							$listoffiles = dol_dir_list($conf->facture->dir_output.'/'.$newref, 'files', 1, '^'.preg_quote($oldref, '/'));
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

			if (!$error && !$this->is_last_in_cycle()) {
				if (!$this->updatePriceNextInvoice($langs)) {
					$error++;
				}
			}

			// Set new ref and define current status
			if (!$error) {
				$this->ref = $num;
				$this->statut = self::STATUS_VALIDATED;	// deprecated
				$this->status = self::STATUS_VALIDATED;
				$this->date_validation = $now;
				$i = 0;

				if (getDolGlobalString('INVOICE_USE_SITUATION')) {
					$final = true;
					$nboflines = count($this->lines);
					while (($i < $nboflines) && $final) {
						$line = $this->lines[$i];
						'@phan-var-force FactureLigne $line';
						if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
							$previousprogress = $line->get_allprev_progress($line->fk_facture);
							$current_progress = (float) $line->situation_percent;
							$full_progress = $previousprogress + $current_progress;
							$final = ($full_progress == 100);
						} else {
							$final = ($line->situation_percent == 100);
						}
						$i++;
					}

					if (empty($final)) {
						$this->situation_final = 0;
					} else {
						$this->situation_final = 1;
					}

					$this->setFinal($user);
				}
			}
		} else {
			$error++;
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
	 * Update price of next invoice
	 *
	 * @param	Translate	$langs	Translate object
	 * @return 	bool				false if KO, true if OK
	 */
	public function updatePriceNextInvoice(&$langs)
	{
		foreach ($this->tab_next_situation_invoice as $next_invoice) {
			$is_last = $next_invoice->is_last_in_cycle();

			if ($next_invoice->status == self::STATUS_DRAFT && $is_last != 1) {
				$this->error = $langs->trans('updatePriceNextInvoiceErrorUpdateline', $next_invoice->ref);
				return false;
			}

			foreach ($next_invoice->lines as $line) {
				'@phan-var-force FactureLigne $line';
				$result = $next_invoice->updateline(
					$line->id,
					$line->desc,
					$line->subprice,
					$line->qty,
					$line->remise_percent,
					$line->date_start,
					$line->date_end,
					$line->tva_tx,
					$line->localtax1_tx,
					$line->localtax2_tx,
					'HT',
					$line->info_bits,
					$line->product_type,
					$line->fk_parent_line,
					0,
					$line->fk_fournprice,
					$line->pa_ht,
					$line->label,
					$line->special_code,
					$line->array_options,
					$line->situation_percent,
					$line->fk_unit
				);

				if ($result < 0) {
					$this->error = $langs->trans('updatePriceNextInvoiceErrorUpdateline', $next_invoice->ref);
					return false;
				}
			}

			break; // Only the next invoice and not each next invoice
		}

		return true;
	}

	/**
	 *	Set draft status
	 *
	 *	@param	User	$user			Object user that modify
	 *	@param	int		$idwarehouse	Id warehouse to use for stock change.
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setDraft($user, $idwarehouse = -1)
	{
		// phpcs:enable
		global $conf, $langs;

		$error = 0;

		if ($this->status == self::STATUS_DRAFT) {
			dol_syslog(__METHOD__." already draft status", LOG_WARNING);
			return 0;
		}

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."facture";
		$sql .= " SET fk_statut = ".self::STATUS_DRAFT;
		$sql .= " WHERE rowid = ".((int) $this->id);

		$result = $this->db->query($sql);
		if ($result) {
			if (!$error) {
				$this->oldcopy = clone $this;
			}

			// If we decrease stock on invoice validation, we increase back
			if ($this->type != self::TYPE_DEPOSIT && $result >= 0 && isModEnabled('stock') && getDolGlobalString('STOCK_CALCULATE_ON_BILL')) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
				$langs->load("agenda");

				$num = count($this->lines);
				for ($i = 0; $i < $num; $i++) {
					if ($this->lines[$i]->fk_product > 0) {
						$mouvP = new MouvementStock($this->db);
						$mouvP->origin = &$this;
						$mouvP->setOrigin($this->element, $this->id);
						// We decrease stock for product
						if ($this->type == self::TYPE_CREDIT_NOTE) {
							$result = $mouvP->livraison($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, $this->lines[$i]->subprice, $langs->trans("InvoiceBackToDraftInDolibarr", $this->ref));
						} else {
							$result = $mouvP->reception($user, $this->lines[$i]->fk_product, $idwarehouse, $this->lines[$i]->qty, 0, $langs->trans("InvoiceBackToDraftInDolibarr", $this->ref)); // we use 0 for price, to not change the weighted average value
						}
					}
				}
			}

			if ($error == 0) {
				$old_statut = $this->status;
				$this->statut = self::STATUS_DRAFT;	// deprecated
				$this->status = self::STATUS_DRAFT;

				// Call trigger
				$result = $this->call_trigger('BILL_UNVALIDATE', $user);
				if ($result < 0) {
					$error++;
					$this->statut = $old_statut; // deprecated
					$this->status = $old_statut;
				}
				// End call triggers
			} else {
				$this->db->rollback();
				return -1;
			}

			if ($error == 0) {
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


	/**
	 *  Add an invoice line into database (linked to product/service or not).
	 *  Note: ->thirdparty must be defined.
	 *  Les parameters sont deja cense etre juste et avec valeurs finales a l'appel
	 *  de cette method. Aussi, pour le taux tva, il doit deja avoir ete defini
	 *  par l'appelant par la method get_default_tva(societe_vendeuse,societe_acheteuse,produit)
	 *  et le desc doit deja avoir la bonne valeur (a l'appelant de gerer le multilangue)
	 *
	 *  @param	string		$desc            	Description of line
	 *  @param	float		$pu_ht              Unit price without tax (> 0 even for credit note)
	 *  @param	float		$qty             	Quantity
	 *  @param	float		$txtva           	Force Vat rate, -1 for auto (Can contain the vat_src_code too with syntax '9.9 (CODE)')
	 *  @param	float		$txlocaltax1		Local tax 1 rate (deprecated, use instead txtva with code inside)
	 *  @param	float		$txlocaltax2		Local tax 2 rate (deprecated, use instead txtva with code inside)
	 *  @param 	int			$fk_product      	Id of predefined product/service
	 *  @param	float		$remise_percent  	Percent of discount on line
	 *  @param 	int|string	$date_start      	Date start of service
	 *  @param 	int|string	$date_end        	Date end of service
	 *  @param 	int			$fk_code_ventilation   	Code of dispatching into accountancy
	 *  @param 	int			$info_bits			Bits of type of lines
	 *  @param 	int			$fk_remise_except	Id discount used
	 *  @param	string		$price_base_type	'HT' or 'TTC'
	 *  @param	float		$pu_ttc             Unit price with tax (> 0 even for credit note)
	 *  @param	int			$type				Type of line (0=product, 1=service). Not used if fk_product is defined, the type of product is used.
	 *  @param 	int			$rang               Position of line (-1 means last value + 1)
	 *  @param	int			$special_code		Special code (also used by externals modules!)
	 *  @param	string		$origin				Depend on global conf MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION can be 'orderdet', 'propaldet'..., else 'order','propal,'....
	 *  @param	int			$origin_id			Depend on global conf MAIN_CREATEFROM_KEEP_LINE_ORIGIN_INFORMATION can be Id of origin object (aka line id), else object id
	 *  @param	int			$fk_parent_line		Id of parent line
	 *  @param	int			$fk_fournprice		Supplier price id (to calculate margin) or ''
	 *  @param	int			$pa_ht				Buying price of line (to calculate margin) or ''
	 *  @param	string		$label				Label of the line (deprecated, do not use)
	 *  @param	array<string,mixed>	$array_options		extrafields array
	 *  @param	int         $situation_percent  Situation advance percentage
	 *  @param	int         $fk_prev_id         Previous situation line id reference
	 *  @param 	int|null	$fk_unit 			Code of the unit to use. Null to use the default one
	 *  @param	float		$pu_ht_devise		Unit price in foreign currency
	 *  @param	string		$ref_ext		    External reference of the line
	 *  @param	int			$noupdateafterinsertline	No update after insert of line
	 *  @return	int             				Return integer <0 if KO, Id of line if OK
	 */
	public function addline(
		$desc,
		$pu_ht,
		$qty,
		$txtva,
		$txlocaltax1 = 0,
		$txlocaltax2 = 0,
		$fk_product = 0,
		$remise_percent = 0,
		$date_start = '',
		$date_end = '',
		$fk_code_ventilation = 0,
		$info_bits = 0,
		$fk_remise_except = 0,
		$price_base_type = 'HT',
		$pu_ttc = 0,
		$type = 0,
		$rang = -1,
		$special_code = 0,
		$origin = '',
		$origin_id = 0,
		$fk_parent_line = 0,
		$fk_fournprice = null,
		$pa_ht = 0,
		$label = '',
		$array_options = array(),
		$situation_percent = 100,
		$fk_prev_id = 0,
		$fk_unit = null,
		$pu_ht_devise = 0,
		$ref_ext = '',
		$noupdateafterinsertline = 0
	) {
		// Deprecation warning
		if ($label) {
			dol_syslog(__METHOD__.": using line label is deprecated", LOG_WARNING);
			//var_dump(debug_backtrace(false));exit;
		}

		global $mysoc, $conf, $langs;

		dol_syslog(get_class($this)."::addline id=$this->id, pu_ht=$pu_ht, qty=$qty, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, fk_product=$fk_product, remise_percent=$remise_percent, date_start=$date_start, date_end=$date_end, fk_code_ventilation=$fk_code_ventilation, info_bits=$info_bits, fk_remise_except=$fk_remise_except, price_base_type=$price_base_type, pu_ttc=$pu_ttc, type=$type, fk_unit=$fk_unit, desc=".dol_trunc($desc, 25), LOG_DEBUG);

		if ($this->status == self::STATUS_DRAFT) {
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
			if (empty($fk_code_ventilation)) {
				$fk_code_ventilation = 0;
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
			if (empty($fk_prev_id)) {
				$fk_prev_id = 'null';
			}
			if (!isset($situation_percent) || $situation_percent > 100 || (string) $situation_percent == '') {
				$situation_percent = 100;
			}
			if (empty($ref_ext)) {
				$ref_ext = '';
			}

			$remise_percent = (float) price2num($remise_percent);
			$qty = price2num($qty);
			$pu_ht = price2num($pu_ht);
			$pu_ht_devise = price2num($pu_ht_devise);
			$pu_ttc = price2num($pu_ttc);
			$pa_ht = price2num($pa_ht);
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

				if (getDolGlobalString('STOCK_MUST_BE_ENOUGH_FOR_INVOICE') && $product_type == 0 && $product->stock_reel < $qty) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnInvoice', $product->ref);
					$this->db->rollback();
					return -3;
				}
			}

			$localtaxes_type = getLocalTaxesFromRate($txtva, 0, $this->thirdparty, $mysoc);

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

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $product_type, $mysoc, $localtaxes_type, $situation_percent, $this->multicurrency_tx, $pu_ht_devise);

			$total_ht  = $tabprice[0];
			$total_tva = $tabprice[1];
			$total_ttc = $tabprice[2];
			$total_localtax1 = $tabprice[9];
			$total_localtax2 = $tabprice[10];
			$pu_ht = $tabprice[3];

			// MultiCurrency
			$multicurrency_total_ht = $tabprice[16];
			$multicurrency_total_tva = $tabprice[17];
			$multicurrency_total_ttc = $tabprice[18];
			$pu_ht_devise = $tabprice[19];

			// Rank to use
			$ranktouse = $rang;
			if ($ranktouse == -1) {
				$rangmax = $this->line_max($fk_parent_line);
				$ranktouse = $rangmax + 1;
			}

			// Insert line
			$this->line = new FactureLigne($this->db);

			$this->line->context = $this->context;

			$this->line->fk_facture = $this->id;
			$this->line->label = $label; // deprecated
			$this->line->desc = $desc;
			$this->line->ref_ext = $ref_ext;

			$this->line->qty = ($this->type == self::TYPE_CREDIT_NOTE ? abs((float) $qty) : $qty); // For credit note, quantity is always positive and unit price negative
			$this->line->subprice = ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht) : $pu_ht); // For credit note, unit price always negative, always positive otherwise

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx = ($total_localtax1 ? $localtaxes_type[1] : 0);
			$this->line->localtax2_tx = ($total_localtax2 ? $localtaxes_type[3] : 0);
			$this->line->localtax1_type = empty($localtaxes_type[0]) ? 0 : $localtaxes_type[0];
			$this->line->localtax2_type = empty($localtaxes_type[2]) ? 0 : $localtaxes_type[2];

			$this->line->total_ht = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ht) : $total_ht); // For credit note and if qty is negative, total is negative
			$this->line->total_ttc = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ttc) : $total_ttc); // For credit note and if qty is negative, total is negative
			$this->line->total_tva = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_tva) : $total_tva); // For credit note and if qty is negative, total is negative
			$this->line->total_localtax1 = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_localtax1) : $total_localtax1); // For credit note and if qty is negative, total is negative
			$this->line->total_localtax2 = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_localtax2) : $total_localtax2); // For credit note and if qty is negative, total is negative

			$this->line->fk_product = $fk_product;
			$this->line->product_type = $product_type;
			$this->line->remise_percent = $remise_percent;
			$this->line->date_start = $date_start;
			$this->line->date_end = $date_end;
			$this->line->fk_code_ventilation = $fk_code_ventilation;
			$this->line->rang = $ranktouse;
			$this->line->info_bits = $info_bits;
			$this->line->fk_remise_except = $fk_remise_except;

			$this->line->special_code = $special_code;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->origin = $origin;
			$this->line->origin_id = $origin_id;
			$this->line->situation_percent = $situation_percent;
			$this->line->fk_prev_id = $fk_prev_id;
			$this->line->fk_unit = $fk_unit;

			// infos margin
			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->fk_multicurrency = $this->fk_multicurrency;
			$this->line->multicurrency_code = $this->multicurrency_code;
			$this->line->multicurrency_subprice	= ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht_devise) : $pu_ht_devise); // For credit note, unit price always negative, always positive otherwise

			$this->line->multicurrency_total_ht = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_ht) : $multicurrency_total_ht); // For credit note and if qty is negative, total is negative
			$this->line->multicurrency_total_tva = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_tva) : $multicurrency_total_tva); // For credit note and if qty is negative, total is negative
			$this->line->multicurrency_total_ttc = (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_ttc) : $multicurrency_total_ttc); // For credit note and if qty is negative, total is negative

			if (is_array($array_options) && count($array_options) > 0) {
				$this->line->array_options = $array_options;
			}

			$result = $this->line->insert();
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

				// Mise a jour information denormalisees au niveau de la facture meme
				if (empty($noupdateafterinsertline)) {
					$result = $this->update_price(1, 'auto', 0, $mysoc); // The addline method is designed to add line from user input so total calculation with update_price must be done using 'auto' mode.
				}

				if ($result > 0) {
					$this->db->commit();
					return $this->line->id;
				} else {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->line->error;
				$this->errors = $this->line->errors;
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->errors[] = 'status of invoice must be Draft to allow use of ->addline()';
			dol_syslog(get_class($this)."::addline status of invoice must be Draft to allow use of ->addline()", LOG_ERR);
			return -3;
		}
	}

	/**
	 *  Update a detail line
	 *
	 *  @param	int			$rowid           	Id of line to update
	 *  @param	string		$desc            	Description of line
	 *  @param	float		$pu              	Prix unitaire (HT ou TTC selon price_base_type) (> 0 even for credit note lines)
	 *  @param	float		$qty             	Quantity
	 *  @param	float		$remise_percent  	Percentage discount of the line
	 *  @param	int		    $date_start      	Date de debut de validite du service
	 *  @param	int		    $date_end        	Date de fin de validite du service
	 *  @param	float		$txtva          	VAT Rate (Can be '8.5', '8.5 (ABC)')
	 * 	@param	float		$txlocaltax1		Local tax 1 rate
	 *  @param	float		$txlocaltax2		Local tax 2 rate
	 * 	@param	string		$price_base_type 	HT or TTC
	 * 	@param	int			$info_bits 		    Miscellaneous information
	 * 	@param	int			$type				Type of line (0=product, 1=service)
	 * 	@param	int			$fk_parent_line		Id of parent line (0 in most cases, used by modules adding sublevels into lines).
	 * 	@param	int			$skip_update_total	Keep fields total_xxx to 0 (used for special lines by some modules)
	 * 	@param	int			$fk_fournprice		Id of origin supplier price
	 * 	@param	int			$pa_ht				Price (without tax) of product when it was bought
	 * 	@param	string		$label				Label of the line (deprecated, do not use)
	 * 	@param	int			$special_code		Special code (also used by externals modules!)
	 *  @param	array<string,mixed>	$array_options	extrafields array
	 * 	@param	int         $situation_percent  Situation advance percentage
	 * 	@param	?int		$fk_unit 			Code of the unit to use. Null to use the default one
	 * 	@param	float		$pu_ht_devise		Unit price in currency
	 * 	@param	int<0,1>	$notrigger			disable line update trigger
	 *  @param	string		$ref_ext		    External reference of the line
	 *  @param	integer		$rang		    	rank of line
	 *  @return	int								Return integer < 0 if KO, > 0 if OK
	 */
	public function updateline($rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1 = 0, $txlocaltax2 = 0, $price_base_type = 'HT', $info_bits = 0, $type = self::TYPE_STANDARD, $fk_parent_line = 0, $skip_update_total = 0, $fk_fournprice = null, $pa_ht = 0, $label = '', $special_code = 0, $array_options = array(), $situation_percent = 100, $fk_unit = null, $pu_ht_devise = 0, $notrigger = 0, $ref_ext = '', $rang = 0)
	{
		global $conf, $user;
		// Deprecation warning
		if ($label) {
			dol_syslog(__METHOD__.": using line label is deprecated", LOG_WARNING);
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		global $mysoc, $langs;

		dol_syslog(get_class($this)."::updateline rowid=$rowid, desc=$desc, pu=$pu, qty=$qty, remise_percent=$remise_percent, date_start=$date_start, date_end=$date_end, txtva=$txtva, txlocaltax1=$txlocaltax1, txlocaltax2=$txlocaltax2, price_base_type=$price_base_type, info_bits=$info_bits, type=$type, fk_parent_line=$fk_parent_line pa_ht=$pa_ht, special_code=$special_code, fk_unit=$fk_unit, pu_ht_devise=$pu_ht_devise", LOG_DEBUG);

		if ($this->status == self::STATUS_DRAFT) {
			if (!$this->is_last_in_cycle() && empty($this->error)) {
				if (!$this->checkProgressLine($rowid, $situation_percent)) {
					if (!$this->error) {
						$this->error = $langs->trans('invoiceLineProgressError');
					}
					return -3;
				}
			}

			if ($date_start && $date_end && $date_start > $date_end) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorStartDateGreaterEnd');
				return -1;
			}

			$this->db->begin();

			// Clean parameters
			if (empty($qty)) {
				$qty = 0;
			}
			if (empty($fk_parent_line) || $fk_parent_line < 0) {
				$fk_parent_line = 0;
			}
			if (empty($special_code) || $special_code == 3) {
				$special_code = 0;
			}
			if (!isset($situation_percent) || $situation_percent > 100 || (string) $situation_percent == '') {
				$situation_percent = 100;
			}
			if (empty($ref_ext)) {
				$ref_ext = '';
			}

			$remise_percent = (float) price2num($remise_percent);
			$qty			= price2num($qty);
			$pu 			= price2num($pu);
			$pu_ht_devise = price2num($pu_ht_devise);
			$pa_ht = price2num($pa_ht);
			if (!preg_match('/\((.*)\)/', (string) $txtva)) {
				$txtva = price2num($txtva); // $txtva can have format '5.0(XXX)' or '5'
			}
			$txlocaltax1	= (float) price2num($txlocaltax1);
			$txlocaltax2	= (float) price2num($txlocaltax2);

			// Check parameters
			if ($type < 0) {
				return -1;
			}

			// Calculate total with, without tax and tax from qty, pu, remise_percent and txtva
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

			$tabprice = calcul_price_total($qty, $pu, $remise_percent, $txtva, $txlocaltax1, $txlocaltax2, 0, $price_base_type, $info_bits, $type, $mysoc, $localtaxes_type, $situation_percent, $this->multicurrency_tx, $pu_ht_devise);

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

			// Old properties: $price, $remise (deprecated)
			$price = $pu;
			$remise = 0;
			if ($remise_percent > 0) {
				$remise = round(((float) $pu * (float) $remise_percent / 100), 2);
				$price = ((float) $pu - $remise);
			}
			$price = price2num($price);

			//Fetch current line from the database and then clone the object and set it in $oldline property
			$line = new FactureLigne($this->db);
			$line->fetch($rowid);
			$line->fetch_optionals();

			if (!empty($line->fk_product)) {
				$product = new Product($this->db);
				$result = $product->fetch($line->fk_product);
				$product_type = $product->type;

				if (getDolGlobalString('STOCK_MUST_BE_ENOUGH_FOR_INVOICE') && $product_type == 0 && $product->stock_reel < $qty) {
					$langs->load("errors");
					$this->error = $langs->trans('ErrorStockIsNotEnoughToAddProductOnInvoice', $product->ref);
					$this->db->rollback();
					return -3;
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
			$this->line->rowid = $rowid;
			$this->line->label = $label;
			$this->line->desc = $desc;
			$this->line->ref_ext = $ref_ext;
			$this->line->qty = ($this->type == self::TYPE_CREDIT_NOTE ? abs((float) $qty) : $qty); // For credit note, quantity is always positive and unit price negative

			$this->line->vat_src_code = $vat_src_code;
			$this->line->tva_tx = $txtva;
			$this->line->localtax1_tx		= $txlocaltax1;
			$this->line->localtax2_tx		= $txlocaltax2;
			$this->line->localtax1_type		= empty($localtaxes_type[0]) ? 0 : $localtaxes_type[0];
			$this->line->localtax2_type		= empty($localtaxes_type[2]) ? 0 : $localtaxes_type[2];

			$this->line->remise_percent		= $remise_percent;
			$this->line->subprice			= ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht) : $pu_ht); // For credit note, unit price always negative, always positive otherwise
			$this->line->date_start = $date_start;
			$this->line->date_end			= $date_end;
			$this->line->total_ht			= (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ht) : $total_ht); // For credit note and if qty is negative, total is negative
			$this->line->total_tva			= (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_tva) : $total_tva);
			$this->line->total_localtax1	= $total_localtax1;
			$this->line->total_localtax2	= $total_localtax2;
			$this->line->total_ttc			= (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($total_ttc) : $total_ttc);
			$this->line->info_bits			= $info_bits;
			$this->line->special_code		= $special_code;
			$this->line->product_type		= $type;
			$this->line->fk_parent_line = $fk_parent_line;
			$this->line->skip_update_total = $skip_update_total;
			$this->line->situation_percent = $situation_percent;
			$this->line->fk_unit = $fk_unit;

			$this->line->fk_fournprice = $fk_fournprice;
			$this->line->pa_ht = $pa_ht;

			// Multicurrency
			$this->line->multicurrency_subprice		= ($this->type == self::TYPE_CREDIT_NOTE ? -abs($pu_ht_devise) : $pu_ht_devise); // For credit note, unit price always negative, always positive otherwise
			$this->line->multicurrency_total_ht 	= (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_ht) : $multicurrency_total_ht); // For credit note and if qty is negative, total is negative
			$this->line->multicurrency_total_tva 	= (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_tva) : $multicurrency_total_tva);
			$this->line->multicurrency_total_ttc 	= (($this->type == self::TYPE_CREDIT_NOTE || $qty < 0) ? -abs($multicurrency_total_ttc) : $multicurrency_total_ttc);

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

				// Mise a jour info denormalisees au niveau facture
				$this->update_price(1, 'auto');
				$this->db->commit();
				return $result;
			} else {
				$this->error = $this->line->error;
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = "Invoice statut makes operation forbidden";
			return -2;
		}
	}

	/**
	 * Check if the percent edited is lower of next invoice line
	 *
	 * @param	int		$idline				id of line to check
	 * @param	float	$situation_percent	progress percentage need to be test
	 * @return 	bool						false if KO, true if OK
	 */
	public function checkProgressLine($idline, $situation_percent)
	{
		$sql = 'SELECT fd.situation_percent FROM '.MAIN_DB_PREFIX.'facturedet fd
				INNER JOIN '.MAIN_DB_PREFIX.'facture f ON (fd.fk_facture = f.rowid)
				WHERE fd.fk_prev_id = '.((int) $idline).' AND f.fk_statut <> 0';

		$result = $this->db->query($sql);
		if (!$result) {
			$this->error = $this->db->error();
			return false;
		}

		$obj = $this->db->fetch_object($result);

		if ($obj === null) {
			return true;
		} else {
			return ($situation_percent < $obj->situation_percent);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Update invoice line with percentage
	 *
	 * @param  FactureLigne $line       	Invoice line
	 * @param  int          $percent    	Percentage
	 * @param  boolean      $update_price   Update object price
	 * @return void
	 */
	public function update_percent($line, $percent, $update_price = true)
	{
		// phpcs:enable
		global $mysoc, $user;

		// Progress should never be changed for discount lines
		if (($line->info_bits & 2) == 2) {
			return;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		// Cap percentages to 100
		if ($percent > 100) {
			$percent = 100;
		}
		if (getDolGlobalInt('INVOICE_USE_SITUATION') == 2) {
			$previous_progress = $line->get_allprev_progress($line->fk_facture);
			$current_progress = $percent - $previous_progress;
			$line->situation_percent = $current_progress;
			$tabprice = calcul_price_total($line->qty, $line->subprice, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 0, 'HT', 0, $line->product_type, $mysoc, array(), $current_progress);
		} else {
			$line->situation_percent = $percent;
			$tabprice = calcul_price_total($line->qty, $line->subprice, $line->remise_percent, $line->tva_tx, $line->localtax1_tx, $line->localtax2_tx, 0, 'HT', 0, $line->product_type, $mysoc, array(), $percent);
		}
		$line->total_ht = $tabprice[0];
		$line->total_tva = $tabprice[1];
		$line->total_ttc = $tabprice[2];
		$line->total_localtax1 = $tabprice[9];
		$line->total_localtax2 = $tabprice[10];
		$line->multicurrency_total_ht  = $tabprice[16];
		$line->multicurrency_total_tva = $tabprice[17];
		$line->multicurrency_total_ttc = $tabprice[18];
		$line->update($user);

		// sometimes it is better to not update price for each line, ie when updating situation on all lines
		if ($update_price) {
			$this->update_price(1);
		}
	}

	/**
	 *	Delete line in database
	 *
	 *	@param		int		$rowid		Id of line to delete
	 *  @param		int		$id			Id of object (for a check)
	 *	@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function deleteLine($rowid, $id = 0)
	{
		global $user;

		dol_syslog(get_class($this)."::deleteline rowid=".((int) $rowid), LOG_DEBUG);

		if ($this->status != self::STATUS_DRAFT) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -1;
		}

		$line = new FactureLigne($this->db);

		$line->context = $this->context;

		// Load line
		$result = $line->fetch($rowid);
		if (!($result > 0)) {
			dol_print_error($this->db, $line->error, $line->errors);
			return -1;
		}

		if ($id > 0 && $line->fk_facture != $id) {
			$this->error = 'ErrorLineIDDoesNotMatchWithObjectID';
			return -1;
		}

		$this->db->begin();

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
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Set percent discount
	 *
	 *  @deprecated
	 *  @see setDiscount()
	 *	@param     	User	$user		User that set discount
	 *	@param     	double	$remise		Discount
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 				Return integer <0 if KO, >0 if OK
	 */
	public function set_remise($user, $remise, $notrigger = 0)
	{
		// phpcs:enable
		dol_syslog(get_class($this)."::set_remise is deprecated, use setDiscount instead", LOG_NOTICE);
		// @phan-suppress-next-line PhanDeprecatedFunction
		return $this->setDiscount($user, $remise, $notrigger);
	}

	/**
	 *	Set percent discount
	 *
	 *	@param     	User	$user		User that set discount
	 *	@param     	float	$remise		Discount
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 				Return integer <0 if KO, >0 if OK
	 */
	public function setDiscount($user, $remise, $notrigger = 0)
	{
		// Clean parameters
		if (empty($remise)) {
			$remise = 0;
		}

		if ($user->hasRight('facture', 'creer')) {
			$remise = (float) price2num($remise, 2);

			$error = 0;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."facture";
			$sql .= " SET remise_percent = ".((float) $remise);
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND fk_statut = ".((int) self::STATUS_DRAFT);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->error();
				$error++;
			}

			if (!$notrigger && empty($error)) {
				// Call trigger
				$result = $this->call_trigger('BILL_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->remise_percent = $remise;
				$this->update_price(1);

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
	 *	Set absolute discount
	 *
	 *	@param     	User	$user 		User that set discount
	 *	@param     	double	$remise		Discount
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 				Return integer <0 if KO, >0 if OK
	 */
	/*
	public function set_remise_absolue($user, $remise, $notrigger = 0)
	{
		// phpcs:enable
		if (empty($remise)) {
			$remise = 0;
		}

		if ($user->hasRight('facture', 'creer')) {
			$error = 0;

			$this->db->begin();

			$remise = price2num($remise);

			$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture';
			$sql .= ' SET remise_absolue = '.((float) $remise);
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= ' AND fk_statut = '.self::STATUS_DRAFT;

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
				$result = $this->call_trigger('BILL_MODIFY', $user);
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

	/**
	 *      Return next reference of customer invoice not already used (or last reference)
	 *      according to numbering module defined into constant FACTURE_ADDON
	 *
	 *      @param	   Societe		$soc		object company
	 *      @param     string		$mode		'next' for next value or 'last' for last value
	 *      @return    string					free ref or last ref
	 */
	public function getNextNumRef($soc, $mode = 'next')
	{
		global $conf, $langs;

		if ($this->module_source == 'takepos') {
			$langs->load('cashdesk');

			$moduleName = 'takepos';
			$moduleSourceName = 'Takepos';
			$addonConstName = 'TAKEPOS_REF_ADDON';

			// Clean parameters (if not defined or using deprecated value)
			if (!getDolGlobalString('TAKEPOS_REF_ADDON')) {
				$conf->global->TAKEPOS_REF_ADDON = 'mod_takepos_ref_simple';
			}

			$addon = getDolGlobalString('TAKEPOS_REF_ADDON');
		} else {
			$langs->load('bills');

			$moduleName = 'facture';
			$moduleSourceName = 'Invoice';
			$addonConstName = 'FACTURE_ADDON';

			// Clean parameters (if not defined or using deprecated value)
			if (!getDolGlobalString('FACTURE_ADDON')) {
				$conf->global->FACTURE_ADDON = 'mod_facture_terre';
			} elseif (getDolGlobalString('FACTURE_ADDON') == 'terre') {
				$conf->global->FACTURE_ADDON = 'mod_facture_terre';
			} elseif (getDolGlobalString('FACTURE_ADDON') == 'mercure') {
				$conf->global->FACTURE_ADDON = 'mod_facture_mercure';
			}

			$addon = getDolGlobalString('FACTURE_ADDON');
		}

		if (!empty($addon)) {
			dol_syslog("Call getNextNumRef with ".$addonConstName." = " . getDolGlobalString('FACTURE_ADDON').", thirdparty=".$soc->name.", type=".$soc->typent_code.", mode=".$mode, LOG_DEBUG);

			$mybool = false;

			$file = $addon.'.php';
			$classname = $addon;


			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir.'core/modules/'.$moduleName.'/');

				// Load file with numbering class (if found)
				if (is_file($dir.$file) && is_readable($dir.$file)) {
					$mybool = ((bool) include_once $dir.$file) || $mybool;
				}
			}

			// For compatibility
			if (!$mybool) {
				$file = $addon.'/'.$addon.'.modules.php';
				$classname = 'mod_'.$moduleName.'_'.$addon;
				$classname = preg_replace('/\-.*$/', '', $classname);
				// Include file with class
				foreach ($conf->file->dol_document_root as $dirroot) {
					$dir = $dirroot.'/core/modules/'.$moduleName.'/';

					// Load file with numbering class (if found)
					if (is_file($dir.$file) && is_readable($dir.$file)) {
						$mybool = (include_once $dir.$file) || $mybool;
					}
				}
			}

			if (!$mybool) {
				dol_print_error(null, 'Failed to include file '.$file);
				return '';
			}

			$obj = new $classname();
			'@phan-var-force ModeleNumRefFactures $obj';

			$numref = $obj->getNextValue($soc, $this, $mode);


			/**
			 * $numref can be empty in case we ask for the last value because if there is no invoice created with the
			 * set up mask.
			 */
			if ($mode != 'last' && !$numref) {
				$this->error = $obj->error;
				return '';
			}

			return $numref;
		} else {
			$langs->load('errors');
			print $langs->trans('Error').' '.$langs->trans('ErrorModuleSetupNotComplete', $langs->transnoentitiesnoconv($moduleSourceName));
			return '';
		}
	}

	/**
	 *	Load miscellaneous information for tab "Info"
	 *
	 *	@param  int		$id		Id of object to load
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, datec, date_valid as datev, tms as datem,';
		$sql .= ' date_closing as dateclosing,';
		$sql .= ' fk_user_author, fk_user_valid, fk_user_closing';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->user_creation_id = $obj->fk_user_author;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->user_closing_id = $obj->fk_user_closing;

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation   = $this->db->jdate($obj->datev);
				$this->date_closing      = $this->db->jdate($obj->dateclosing);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of invoices (eventually filtered on a user) into an array
	 *
	 *  @param		int		$shortlist		0=Return array[id]=ref, 1=Return array[](id=>id,ref=>ref,name=>name)
	 *  @param      int		$draft      	0=not draft, 1=draft
	 *  @param      User	$excluser      	Object user to exclude
	 *  @param    	int		$socid			Id third party
	 *  @param    	int		$limit			For pagination
	 *  @param    	int		$offset			For pagination
	 *  @param    	string	$sortfield		Sort criteria
	 *  @param    	string	$sortorder		Sort order
	 *  @return     array|int             	-1 if KO, array with result if OK
	 */
	public function liste_array($shortlist = 0, $draft = 0, $excluser = null, $socid = 0, $limit = 0, $offset = 0, $sortfield = 'f.datef,f.rowid', $sortorder = 'DESC')
	{
		// phpcs:enable
		global $user;

		$ga = array();

		$sql = "SELECT s.rowid, s.nom as name, s.client,";
		$sql .= " f.rowid as fid, f.ref as ref, f.datef as df";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.fk_soc = s.rowid";
		if ($draft) {
			$sql .= " AND f.fk_statut = ".self::STATUS_DRAFT;
		}
		if (is_object($excluser)) {
			$sql .= " AND f.fk_user_author <> ".((int) $excluser->id);
		}
		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$search_sale = $user->id;
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = f.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Search on socid
		if ($socid) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
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
						$ga[$obj->fid] = $obj->ref;
					} elseif ($shortlist == 2) {
						$ga[$obj->fid] = $obj->ref.' ('.$obj->name.')';
					} else {
						$ga[$i]['id'] = $obj->fid;
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


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of invoices qualified to be replaced by another invoice.
	 *	Invoices matching the following rules are returned:
	 *	(Status validated or abandoned for a reason 'other') + not paid + no payment at all + not already replaced
	 *
	 *	@param		int			$socid		Id thirdparty
	 *	@return    	array|int				Array of invoices ('id'=>id, 'ref'=>ref, 'status'=>status, 'paymentornot'=>0/1)
	 */
	public function list_replacable_invoices($socid = 0)
	{
		// phpcs:enable
		global $conf;

		$return = array();

		$sql = "SELECT f.rowid as rowid, f.ref, f.fk_statut as status, f.paye as paid,";
		$sql .= " ff.rowid as rowidnext";
		//$sql .= ", SUM(pf.amount) as alreadypaid";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as ff ON f.rowid = ff.fk_facture_source";
		$sql .= " WHERE (f.fk_statut = ".self::STATUS_VALIDATED." OR (f.fk_statut = ".self::STATUS_ABANDONED." AND f.close_code = '".self::CLOSECODE_ABANDONED."'))";
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.paye = 0"; // Not paid completely
		$sql .= " AND pf.fk_paiement IS NULL"; // No payment already done
		$sql .= " AND ff.fk_statut IS NULL"; // Return true if it is not a replacement invoice
		if ($socid > 0) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		//$sql .= " GROUP BY f.rowid, f.ref, f.fk_statut, f.paye, ff.rowid";
		$sql .= " ORDER BY f.ref";

		dol_syslog(get_class($this)."::list_replacable_invoices", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$return[$obj->rowid] = array(
					'id' => $obj->rowid,
					'ref' => $obj->ref,
					'status' => $obj->status,
					'paid' => $obj->paid,
					'alreadypaid' => 0
				);
			}
			//print_r($return);
			return $return;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Return list of invoices qualified to be corrected by a credit note.
	 *	Invoices matching the following rules are returned:
	 *	(validated + payment on process) or classified (paid completely or paid partiely) + not already replaced + not already a credit note
	 *
	 *	@param		int			$socid		Id thirdparty
	 *	@return    	array|int				Array of invoices ($id => array('ref'=>,'paymentornot'=>,'status'=>,'paye'=>)
	 */
	public function list_qualified_avoir_invoices($socid = 0)
	{
		// phpcs:enable
		global $conf;

		$return = array();


		$sql = "SELECT f.rowid as rowid, f.ref, f.fk_statut, f.type, f.subtype, f.paye, pf.fk_paiement";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON f.rowid = pf.fk_facture";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."facture as ff ON (f.rowid = ff.fk_facture_source AND ff.type=".self::TYPE_REPLACEMENT.")";
		$sql .= " WHERE f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.fk_statut in (".self::STATUS_VALIDATED.",".self::STATUS_CLOSED.")";
		//  $sql.= " WHERE f.fk_statut >= 1";
		//	$sql.= " AND (f.paye = 1";				// Classee payee completement
		//	$sql.= " OR f.close_code IS NOT NULL)";	// Classee payee partiellement
		$sql .= " AND ff.type IS NULL"; // Renvoi vrai si pas facture de replacement
		$sql .= " AND f.type <> ".self::TYPE_CREDIT_NOTE; // Exclude credit note invoices from selection

		if (getDolGlobalString('INVOICE_USE_SITUATION_CREDIT_NOTE')) {
			// Keep invoices that are not situation invoices or that are the last in series if it is a situation invoice
			$sql .= " AND (f.type <> ".self::TYPE_SITUATION." OR f.rowid IN ";
			$sql .= '(SELECT MAX(fs.rowid)'; // This select returns several ID because of the group by later
			$sql .= " FROM ".MAIN_DB_PREFIX."facture as fs";
			$sql .= " WHERE fs.entity IN (".getEntity('invoice').")";
			$sql .= " AND fs.type = ".self::TYPE_SITUATION;
			$sql .= " AND fs.fk_statut IN (".self::STATUS_VALIDATED.",".self::STATUS_CLOSED.")";
			if ($socid > 0) {
				$sql .= " AND fs.fk_soc = ".((int) $socid);
			}
			$sql .= " GROUP BY fs.situation_cycle_ref)"; // For each situation_cycle_ref, we take the higher rowid
			$sql .= ")";
		} else {
			$sql .= " AND f.type <> ".self::TYPE_SITUATION; // Keep invoices that are not situation invoices
		}

		if ($socid > 0) {
			$sql .= " AND f.fk_soc = ".((int) $socid);
		}
		$sql .= " ORDER BY f.ref";

		dol_syslog(get_class($this)."::list_qualified_avoir_invoices", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$qualified = 0;
				if ($obj->fk_statut == self::STATUS_VALIDATED) {
					$qualified = 1;
				}
				if ($obj->fk_statut == self::STATUS_CLOSED) {
					$qualified = 1;
				}
				if ($qualified) {
					//$ref=$obj->ref;
					$paymentornot = ($obj->fk_paiement ? 1 : 0);
					$return[$obj->rowid] = array('ref' => $obj->ref, 'status' => $obj->fk_statut, 'type' => $obj->type, 'paye' => $obj->paye, 'paymentornot' => $paymentornot);
				}
			}

			return $return;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *	@param  User					$user    	Object user
	 *	@return WorkboardResponse|int 				Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		$clause = " WHERE";

		$sql = "SELECT f.rowid, f.date_lim_reglement as datefin, f.fk_statut as status, f.total_ht";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON f.fk_soc = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = " AND";
		}
		$sql .= $clause." f.paye=0";
		$sql .= " AND f.entity IN (".getEntity('invoice').")";
		$sql .= " AND f.fk_statut = ".self::STATUS_VALIDATED;
		if ($user->socid) {
			$sql .= " AND f.fk_soc = ".((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("bills");
			$now = dol_now();

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->facture->client->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("CustomerBillsUnpaid");
			$response->labelShort = $langs->trans("Unpaid");
			$response->url = DOL_URL_ROOT.'/compta/facture/list.php?search_status=1&mainmenu=billing&leftmenu=customers_bills';
			$response->img = img_object('', "bill");

			$generic_facture = new Facture($this->db);

			while ($obj = $this->db->fetch_object($resql)) {
				$generic_facture->date_lim_reglement = $this->db->jdate($obj->datefin);
				$generic_facture->statut = $obj->status;
				$generic_facture->status = $obj->status;

				$response->nbtodo++;
				$response->total += $obj->total_ht;

				if ($generic_facture->hasDelay()) {
					$response->nbtodolate++;
					$response->url_late = DOL_URL_ROOT.'/compta/facture/list.php?search_option=late&mainmenu=billing&leftmenu=customers_bills';
				}
			}

			$this->db->free($resql);
			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}


	/* gestion des contacts d'une facture */

	/**
	 *	Retourne id des contacts clients de facturation
	 *
	 *	@return     int[]       Liste des id contacts facturation
	 */
	public function getIdBillingContact()
	{
		return $this->getIdContact('external', 'BILLING');
	}

	/**
	 *	Retourne id des contacts clients de livraison
	 *
	 *	@return     int[]       Liste des id contacts livraison
	 */
	public function getIdShippingContact()
	{
		return $this->getIdContact('external', 'SHIPPING');
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *	@param	string		$option		''=Create a specimen invoice with lines, 'nolines'=No lines
	 *  @return	int
	 */
	public function initAsSpecimen($option = '')
	{
		global $conf, $langs, $user;

		$now = dol_now();
		$arraynow = dol_getdate($now);
		$nownotime = dol_mktime(0, 0, 0, $arraynow['mon'], $arraynow['mday'], $arraynow['year']);

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
		//Avoid php warning Warning: mt_rand(): max(0) is smaller than min(1) when no product exists
		if (empty($num_prods)) {
			$num_prods = 1;
		}

		// Initialize parameters
		$this->id = 0;
		$this->entity = 1;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;
		$this->socid = 1;
		$this->date = $nownotime;
		$this->date_lim_reglement = $nownotime + 3600 * 24 * 30;
		$this->cond_reglement_id   = 1;
		$this->cond_reglement_code = 'RECEP';
		$this->date_lim_reglement = $this->calculate_date_lim_reglement();
		$this->mode_reglement_id   = 0; // Not forced to show payment mode CHQ + VIR
		$this->mode_reglement_code = ''; // Not forced to show payment mode CHQ + VIR

		$this->note_public = 'This is a comment (public)';
		$this->note_private = 'This is a comment (private)';
		$this->note = 'This is a comment (private)';

		$this->fk_user_author = $user->id;

		$this->multicurrency_tx = 1;
		$this->multicurrency_code = $conf->currency;

		$this->fk_incoterms = 0;
		$this->location_incoterms = '';

		if (empty($option) || $option != 'nolines') {
			// Lines
			$nbp = 5;
			$xnbp = 0;
			while ($xnbp < $nbp) {
				$line = new FactureLigne($this->db);
				$line->desc = $langs->trans("Description")." ".$xnbp;
				$line->qty = 1;
				$line->subprice = 100;
				$line->tva_tx = 19.6;
				$line->localtax1_tx = 0;
				$line->localtax2_tx = 0;
				$line->remise_percent = 0;
				if ($xnbp == 1) {        // Qty is negative (product line)
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
					$line->qty = -1;
					$line->total_ht = -100;
					$line->total_ttc = -119.6;
					$line->total_tva = -19.6;
					$line->multicurrency_total_ht = -200;
					$line->multicurrency_total_ttc = -239.2;
					$line->multicurrency_total_tva = -39.2;
				} elseif ($xnbp == 2) {    // UP is negative (free line)
					$line->subprice = -100;
					$line->total_ht = -100;
					$line->total_ttc = -119.6;
					$line->total_tva = -19.6;
					$line->remise_percent = 0;
					$line->multicurrency_total_ht = -200;
					$line->multicurrency_total_ttc = -239.2;
					$line->multicurrency_total_tva = -39.2;
				} elseif ($xnbp == 3) {    // Discount is 50% (product line)
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
					$line->total_ht = 50;
					$line->total_ttc = 59.8;
					$line->total_tva = 9.8;
					$line->multicurrency_total_ht = 100;
					$line->multicurrency_total_ttc = 119.6;
					$line->multicurrency_total_tva = 19.6;
					$line->remise_percent = 50;
				} else { // (product line)
					$prodid = mt_rand(1, $num_prods);
					$line->fk_product = $prodids[$prodid];
					$line->total_ht = 100;
					$line->total_ttc = 119.6;
					$line->total_tva = 19.6;
					$line->multicurrency_total_ht = 200;
					$line->multicurrency_total_ttc = 239.2;
					$line->multicurrency_total_tva = 39.2;
					$line->remise_percent = 0;
				}

				$this->lines[$xnbp] = $line;


				$this->total_ht       += $line->total_ht;
				$this->total_tva      += $line->total_tva;
				$this->total_ttc      += $line->total_ttc;

				$this->multicurrency_total_ht       += $line->multicurrency_total_ht;
				$this->multicurrency_total_tva      += $line->multicurrency_total_tva;
				$this->multicurrency_total_ttc      += $line->multicurrency_total_ttc;

				$xnbp++;
			}
			$this->revenuestamp = 0;

			// Add a line "offered"
			$line = new FactureLigne($this->db);
			$line->desc = $langs->trans("Description")." (offered line)";
			$line->qty = 1;
			$line->subprice = 100;
			$line->tva_tx = 19.6;
			$line->localtax1_tx = 0;
			$line->localtax2_tx = 0;
			$line->remise_percent = 100;
			$line->total_ht = 0;
			$line->total_ttc = 0; // 90 * 1.196
			$line->total_tva = 0;
			$line->multicurrency_total_ht = 0;
			$line->multicurrency_total_ttc = 0;
			$line->multicurrency_total_tva = 0;
			$prodid = mt_rand(1, $num_prods);
			$line->fk_product = $prodids[$prodid];

			$this->lines[$xnbp] = $line;
			$xnbp++;
		}

		return 1;
	}

	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @return         int     Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $conf, $user;

		$this->nb = array();

		$clause = "WHERE";

		$sql = "SELECT count(f.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." f.entity IN (".getEntity('invoice').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["invoices"] = $obj->nb;
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
	 * 	Create an array of invoice lines
	 *
	 * 	@return int<-1,1>	>0 if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		return $this->fetch_lines();
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string		$modele			Generator to use. Caller must set it to obj->model_pdf or GETPOST('model','alpha') for example.
	 *	@param	Translate	$outputlangs	Object lang to use for translation
	 *  @param  int<0,1>			$hidedetails    Hide details of lines
	 *  @param  int<0,1>	$hidedesc       Hide description
	 *  @param  int<0,1>	$hideref        Hide ref
	 *  @param  ?array<string,mixed>	$moreparams	Array to provide more information
	 *	@return int<-1,1>					Return integer <0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$outputlangs->loadLangs(array("bills", "products"));

		if (!dol_strlen($modele)) {
			$modele = 'crabe';
			$thisTypeConfName = 'FACTURE_ADDON_PDF_'.$this->type;

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString($thisTypeConfName)) {
				$modele = getDolGlobalString($thisTypeConfName);
			} elseif (getDolGlobalString('FACTURE_ADDON_PDF')) {
				$modele = getDolGlobalString('FACTURE_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/facture/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 * Gets the smallest reference available for a new cycle
	 *
	 * @return int >= 1 if OK, -1 if error
	 */
	public function newCycle()
	{
		$sql = "SELECT max(situation_cycle_ref) as maxsituationref";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE f.entity IN (".getEntity('invoice', 0).")";

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				$ref = 0;
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$ref = $obj->maxsituationref;
				}
				$ref++;
			} else {
				$ref = 1;
			}
			$this->db->free($resql);
			return $ref;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog("Error sql=".$sql.", error=".$this->error, LOG_ERR);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Checks if the invoice is the first of a cycle
	 *
	 * @return boolean
	 */
	public function is_first()
	{
		// phpcs:enable
		return ($this->situation_counter == 1);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Returns an array containing the previous situations as Facture objects
	 *
	 * @return mixed -1 if error, array of previous situations
	 */
	public function get_prev_sits()
	{
		// phpcs:enable
		global $conf;

		$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'facture';
		$sql .= ' WHERE situation_cycle_ref = '.((int) $this->situation_cycle_ref);
		$sql .= ' AND situation_counter < '.((int) $this->situation_counter);
		$sql .= ' AND entity = '.($this->entity > 0 ? $this->entity : $conf->entity);
		$resql = $this->db->query($sql);
		$res = array();
		if ($resql && $this->db->num_rows($resql) > 0) {
			while ($row = $this->db->fetch_object($resql)) {
				$id = $row->rowid;
				$situation = new Facture($this->db);
				$situation->fetch($id);
				$res[] = $situation;
			}
		} else {
			$this->error = $this->db->error();
			dol_syslog("Error sql=".$sql.", error=".$this->error, LOG_ERR);
			return -1;
		}

		return $res;
	}

	/**
	 * Sets the invoice as a final situation
	 *
	 *  @param  	User	$user    	Object user
	 *  @param     	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *	@return		int 				Return integer <0 if KO, >0 if OK
	 */
	public function setFinal(User $user, $notrigger = 0)
	{
		$error = 0;

		$this->db->begin();

		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET situation_final = '.((int) $this->situation_final).' WHERE rowid = '.((int) $this->id);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->error();
			$error++;
		}

		if (!$notrigger && empty($error)) {
			// Call trigger
			$result = $this->call_trigger('BILL_MODIFY', $user);
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Checks if the invoice is the last in its cycle
	 *
	 * @return bool Last of the cycle status
	 */
	public function is_last_in_cycle()
	{
		// phpcs:enable
		global $conf;

		if (!empty($this->situation_cycle_ref)) {
			// No point in testing anything if we're not inside a cycle
			$sql = 'SELECT max(situation_counter) FROM '.MAIN_DB_PREFIX.'facture';
			$sql .= ' WHERE situation_cycle_ref = '.((int) $this->situation_cycle_ref);
			$sql .= ' AND entity = '.($this->entity > 0 ? $this->entity : $conf->entity);
			$resql = $this->db->query($sql);

			if ($resql && $this->db->num_rows($resql) > 0 && $res = $this->db->fetch_array($resql)) {
				$last = $res['max(situation_counter)'];
				return ($last == $this->situation_counter);
			} else {
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::select Error ".$this->error, LOG_ERR);
				return false;
			}
		} else {
			return true;
		}
	}

	/**
	 * Replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'facture'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Replace a product id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old product id
	 * @param int $dest_id New product id
	 * @return bool
	 */
	public static function replaceProduct(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'facturedet'
		);

		return CommonObject::commonReplaceProduct($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Is the customer invoice delayed?
	 *
	 * @return bool
	 */
	public function hasDelay()
	{
		global $conf;

		$now = dol_now();

		// Paid invoices have status STATUS_CLOSED
		if ($this->status != Facture::STATUS_VALIDATED) {
			return false;
		}

		$hasDelay = $this->date_lim_reglement < ($now - $conf->facture->client->warning_delay);
		if ($hasDelay && !empty($this->retained_warranty) && !empty($this->retained_warranty_date_limit)) {
			$totalpaid = $this->getSommePaiement();
			$totalpaid = (float) $totalpaid;
			$RetainedWarrantyAmount = $this->getRetainedWarrantyAmount();
			if ($totalpaid >= 0 && $RetainedWarrantyAmount >= 0) {
				if (($totalpaid < $this->total_ttc - $RetainedWarrantyAmount) && $this->date_lim_reglement < ($now - $conf->facture->client->warning_delay)) {
					$hasDelay = 1;
				} elseif ($totalpaid < $this->total_ttc && $this->retained_warranty_date_limit < ($now - $conf->facture->client->warning_delay)) {
					$hasDelay = 1;
				} else {
					$hasDelay = 0;
				}
			}
		}

		return $hasDelay;
	}

	/**
	 * Currently used for documents generation : to know if retained warranty need to be displayed
	 * @return bool
	 */
	public function displayRetainedWarranty()
	{
		global $conf;

		// TODO : add a flag on invoices to store this conf : INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION

		// note : we don't need to test INVOICE_USE_RETAINED_WARRANTY because if $this->retained_warranty is not empty it's because it was set when this conf was active

		$displayWarranty = false;
		if (!empty($this->retained_warranty)) {
			$displayWarranty = true;

			if ($this->type == Facture::TYPE_SITUATION && getDolGlobalString('INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION')) {
				// Check if this situation invoice is 100% for real
				$displayWarranty = false;
				if (!empty($this->situation_final)) {
					$displayWarranty = true;
				} elseif (!empty($this->lines) && $this->status == Facture::STATUS_DRAFT) {
					// $object->situation_final need validation to be done so this test is need for draft
					$displayWarranty = true;

					foreach ($this->lines as $i => $line) {
						if ($line->product_type < 2 && $line->situation_percent < 100) {
							$displayWarranty = false;
							break;
						}
					}
				}
			}
		}

		return $displayWarranty;
	}

	/**
	 * @param	int			$rounding		Minimum number of decimal to show. If 0, no change, if -1, we use min($conf->global->MAIN_MAX_DECIMALS_UNIT,$conf->global->MAIN_MAX_DECIMALS_TOT)
	 * @return float or -1 if not available
	 */
	public function getRetainedWarrantyAmount($rounding = -1)
	{
		global $conf;
		if (empty($this->retained_warranty)) {
			return -1;
		}

		$retainedWarrantyAmount = 0;

		// Billed - retained warranty
		if ($this->type == Facture::TYPE_SITUATION && getDolGlobalString('INVOICE_RETAINED_WARRANTY_LIMITED_TO_FINAL_SITUATION')) {
			$displayWarranty = true;
			// Check if this situation invoice is 100% for real
			if (!empty($this->lines)) {
				foreach ($this->lines as $i => $line) {
					if ($line->product_type < 2 && $line->situation_percent < 100) {
						$displayWarranty = false;
						break;
					}
				}
			}

			if ($displayWarranty && !empty($this->situation_final)) {
				$this->fetchPreviousNextSituationInvoice();
				$TPreviousIncoice = $this->tab_previous_situation_invoice;

				$total2BillWT = 0;
				foreach ($TPreviousIncoice as &$fac) {
					$total2BillWT += $fac->total_ttc;
				}
				$total2BillWT += $this->total_ttc;

				$retainedWarrantyAmount = $total2BillWT * $this->retained_warranty / 100;
			} else {
				return -1;
			}
		} else {
			// Because one day retained warranty could be used on standard invoices
			$retainedWarrantyAmount = $this->total_ttc * $this->retained_warranty / 100;
		}

		if ($rounding < 0) {
			$rounding = min(getDolGlobalString('MAIN_MAX_DECIMALS_UNIT'), getDolGlobalString('MAIN_MAX_DECIMALS_TOT'));
		}

		if ($rounding > 0) {
			return round($retainedWarrantyAmount, $rounding);
		}

		return $retainedWarrantyAmount;
	}

	/**
	 *  Change the retained warranty
	 *
	 *  @param		float		$value		value of retained warranty
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setRetainedWarranty($value)
	{
		dol_syslog(get_class($this).'::setRetainedWarranty('.$value.')');

		if ($this->status >= 0) {
			$fieldname = 'retained_warranty';
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ".$fieldname." = ".((float) $value);
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->retained_warranty = (float) $value;
				return 1;
			} else {
				dol_syslog(get_class($this).'::setRetainedWarranty Erreur '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setRetainedWarranty, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}


	/**
	 *  Change the retained_warranty_date_limit
	 *
	 *  @param		int		$timestamp		date limit of retained warranty in timestamp format
	 *  @param		string	$dateYmd		date limit of retained warranty in Y m d format
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setRetainedWarrantyDateLimit($timestamp, $dateYmd = '')
	{
		if (!$timestamp && $dateYmd) {
			$timestamp = $this->db->jdate($dateYmd);
		}


		dol_syslog(get_class($this).'::setRetainedWarrantyDateLimit('.$timestamp.')');
		if ($this->status >= 0) {
			$fieldname = 'retained_warranty_date_limit';
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= " SET ".$fieldname." = ".(strval($timestamp) != '' ? "'".$this->db->idate($timestamp)."'" : 'null');
			$sql .= ' WHERE rowid = '.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->retained_warranty_date_limit = $timestamp;
				return 1;
			} else {
				dol_syslog(get_class($this).'::setRetainedWarrantyDateLimit Erreur '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setRetainedWarrantyDateLimit, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}


	/**
	 *  Send reminders by emails for invoices validated that are due.
	 *  CAN BE A CRON TASK
	 *
	 *  @param	int			$nbdays				Delay before due date (or after if delay is negative)
	 *  @param	string		$paymentmode		'' or 'all' by default (no filter), or 'LIQ', 'CHQ', CB', ...
	 *  @param	int|string	$template			Name (or id) of email template (Must be a template of type 'facture_send')
	 *  @param	string		$datetouse			'duedate' (default) or 'invoicedate'
	 *  @param	string		$forcerecipient		Force email of recipient (for example to send the email to an accountant supervisor instead of the customer)
	 *  @return int         					0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function sendEmailsRemindersOnInvoiceDueDate($nbdays = 0, $paymentmode = 'all', $template = '', $datetouse = 'duedate', $forcerecipient = '')
	{
		global $conf, $langs, $user;

		$error = 0;
		$this->output = '';
		$this->error = '';
		$nbMailSend = 0;
		$errorsMsg = array();

		$langs->load("bills");

		if (!isModEnabled('invoice')) {	// Should not happen. If module disabled, cron job should not be visible.
			$this->output .= $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv("Facture"));
			return 0;
		}
		if (!in_array($datetouse, array('duedate', 'invoicedate'))) {
			$this->output .= 'Bad value for parameter datetouse. Must be "duedate" or "invoicedate"';
			return 0;
		}
		/*if (empty($conf->global->FACTURE_REMINDER_EMAIL)) {
			$langs->load("bills");
			$this->output .= $langs->trans('EventRemindersByEmailNotEnabled', $langs->transnoentitiesnoconv("Facture"));
			return 0;
		}
		*/

		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$formmail = new FormMail($this->db);

		$now = dol_now();
		$tmpidate = dol_get_first_hour(dol_time_plus_duree($now, $nbdays, 'd'), 'gmt');

		$tmpinvoice = new Facture($this->db);

		dol_syslog(__METHOD__." start", LOG_INFO);

		// Select all action comm reminder
		$sql = "SELECT rowid as id FROM ".MAIN_DB_PREFIX."facture as f";
		if (!empty($paymentmode) && $paymentmode != 'all') {
			$sql .= ", ".MAIN_DB_PREFIX."c_paiement as cp";
		}
		$sql .= " WHERE f.paye = 0";	// Only unpaid
		$sql .= " AND f.fk_statut = ".self::STATUS_VALIDATED;	// Only validated status
		if ($datetouse == 'invoicedate') {
			$sql .= " AND f.datef = '".$this->db->idate($tmpidate, 'gmt')."'";
		} else {
			$sql .= " AND f.date_lim_reglement = '".$this->db->idate($tmpidate, 'gmt')."'";
		}
		$sql .= " AND f.entity IN (".getEntity('facture', 0).")";	// One batch process only one company (no sharing)
		if (!empty($paymentmode) && $paymentmode != 'all') {
			$sql .= " AND f.fk_mode_reglement = cp.id AND cp.code = '".$this->db->escape($paymentmode)."'";
		}
		// TODO Add a filter to check there is no payment started yet
		if ($datetouse == 'invoicedate') {
			$sql .= $this->db->order("datef", "ASC");
		} else {
			$sql .= $this->db->order("date_lim_reglement", "ASC");
		}

		$resql = $this->db->query($sql);

		$stmpidate = dol_print_date($tmpidate, 'day', 'gmt');
		if ($datetouse == 'invoicedate') {
			$this->output .= $langs->transnoentitiesnoconv("SearchValidatedInvoicesWithDate", $stmpidate);
		} else {
			$this->output .= $langs->transnoentitiesnoconv("SearchUnpaidInvoicesWithDueDate", $stmpidate);
		}
		if (!empty($paymentmode) && $paymentmode != 'all') {
			$this->output .= ' ('.$langs->transnoentitiesnoconv("PaymentMode").' '.$paymentmode.')';
		}
		$this->output .= '<br>';

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!$error) {
					// Load event
					$res = $tmpinvoice->fetch($obj->id);
					if ($res > 0) {
						$tmpinvoice->fetch_thirdparty();

						$outputlangs = new Translate('', $conf);
						if ($tmpinvoice->thirdparty->default_lang) {
							$outputlangs->setDefaultLang($tmpinvoice->thirdparty->default_lang);
							$outputlangs->loadLangs(array("main", "bills"));
						} else {
							$outputlangs = $langs;
						}

						// Select email template according to language of recipient
						$arraymessage = $formmail->getEMailTemplate($this->db, 'facture_send', $user, $outputlangs, (is_numeric($template) ? $template : 0), 1, (is_numeric($template) ? '' : $template));
						if (is_numeric($arraymessage) && $arraymessage <= 0) {
							$langs->load("errors");
							$this->output .= $langs->trans('ErrorFailedToFindEmailTemplate', $template);
							return 0;
						}

						// PREPARE EMAIL
						$errormesg = '';

						// Make substitution in email content
						$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $tmpinvoice);

						complete_substitutions_array($substitutionarray, $outputlangs, $tmpinvoice);

						// Topic
						$sendTopic = make_substitutions(empty($arraymessage->topic) ? $outputlangs->transnoentitiesnoconv('InformationMessage') : $arraymessage->topic, $substitutionarray, $outputlangs, 1);

						// Content
						$content = $outputlangs->transnoentitiesnoconv($arraymessage->content);

						$sendContent = make_substitutions($content, $substitutionarray, $outputlangs, 1);

						// Recipient
						$to = array();
						if ($forcerecipient) {	// If a recipient was forced
							$to = array($forcerecipient);
						} else {
							$res = $tmpinvoice->fetch_thirdparty();
							$recipient = $tmpinvoice->thirdparty;
							if ($res > 0) {
								$tmparraycontact = $tmpinvoice->liste_contact(-1, 'external', 0, 'BILLING');
								if (is_array($tmparraycontact) && count($tmparraycontact) > 0) {
									foreach ($tmparraycontact as $data_email) {
										if (!empty($data_email['email'])) {
											$to[] = $tmpinvoice->thirdparty->contact_get_property($data_email['id'], 'email');
										}
									}
								}
								if (empty($to) && !empty($recipient->email)) {
									$to[] = $recipient->email;
								}
								if (empty($to)) {
									$errormesg = "Failed to send remind to thirdparty id=".$tmpinvoice->socid.". No email defined for invoice or customer.";
									$error++;
								}
							} else {
								$errormesg = "Failed to load recipient with thirdparty id=".$tmpinvoice->socid;
								$error++;
							}
						}

						// Sender
						$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
						if (!empty($arraymessage->email_from)) {	// If a sender is defined into template, we use it in priority
							$from = $arraymessage->email_from;
						}
						if (empty($from)) {
							$errormesg = "Failed to get sender into global setup MAIN_MAIL_EMAIL_FROM";
							$error++;
						}

						if (!$error && !empty($to)) {
							$this->db->begin();

							$to = implode(',', $to);
							if (!empty($arraymessage->email_to)) {	// If a recipient is defined into template, we add it
								$to = $to.','.$arraymessage->email_to;
							}

							// Errors Recipient
							$errors_to = getDolGlobalString('MAIN_MAIL_ERRORS_TO');

							$trackid = 'inv'.$tmpinvoice->id;
							$sendcontext = 'standard';

							$email_tocc = '';
							if (!empty($arraymessage->email_tocc)) {	// If a CC is defined into template, we use it
								$email_tocc = $arraymessage->email_tocc;
							}

							$email_tobcc = '';
							if (!empty($arraymessage->email_tobcc)) {	// If a BCC is defined into template, we use it
								$email_tobcc = $arraymessage->email_tobcc;
							}

							//join file is asked
							$joinFile = [];
							$joinFileName = [];
							$joinFileMime = [];
							if ($arraymessage->joinfiles == 1 && !empty($tmpinvoice->last_main_doc)) {
								$joinFile[] = DOL_DATA_ROOT.'/'.$tmpinvoice->last_main_doc;
								$joinFileName[] = basename($tmpinvoice->last_main_doc);
								$joinFileMime[] = dol_mimetype(DOL_DATA_ROOT.'/'.$tmpinvoice->last_main_doc);
							}

							// Mail Creation
							$cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, $joinFile, $joinFileMime, $joinFileName, $email_tocc, $email_tobcc, 0, 1, $errors_to, '', $trackid, '', $sendcontext, '');

							// Sending Mail
							if ($cMailFile->sendfile()) {
								$nbMailSend++;

								// Add a line into event table
								require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

								// Insert record of emails sent
								$actioncomm = new ActionComm($this->db);

								$actioncomm->type_code = 'AC_OTH_AUTO'; // Event insert into agenda automatically
								$actioncomm->socid = $tmpinvoice->thirdparty->id; // To link to a company
								$actioncomm->contact_id = 0;

								$actioncomm->code = 'AC_EMAIL';
								$actioncomm->label = 'sendEmailsRemindersOnInvoiceDueDateOK (nbdays='.$nbdays.' paymentmode='.$paymentmode.' template='.$template.' datetouse='.$datetouse.' forcerecipient='.$forcerecipient.')';
								$actioncomm->note_private = $sendContent;
								$actioncomm->fk_project = $tmpinvoice->fk_project;
								$actioncomm->datep = dol_now();
								$actioncomm->datef = $actioncomm->datep;
								$actioncomm->percentage = -1; // Not applicable
								$actioncomm->authorid = $user->id; // User saving action
								$actioncomm->userownerid = $user->id; // Owner of action
								// Fields when action is an email (content should be added into note)
								$actioncomm->email_msgid = $cMailFile->msgid;
								$actioncomm->email_subject = $sendTopic;
								$actioncomm->email_from = $from;
								$actioncomm->email_sender = '';
								$actioncomm->email_to = $to;
								//$actioncomm->email_tocc = $sendtocc;
								//$actioncomm->email_tobcc = $sendtobcc;
								//$actioncomm->email_subject = $subject;
								$actioncomm->errors_to = $errors_to;

								$actioncomm->elementtype = 'invoice';
								$actioncomm->fk_element = $tmpinvoice->id;

								//$actioncomm->extraparams = $extraparams;

								$actioncomm->create($user);
							} else {
								$errormesg = $cMailFile->error.' : '.$to;
								$error++;

								// Add a line into event table
								require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

								// Insert record of emails sent
								$actioncomm = new ActionComm($this->db);

								$actioncomm->type_code = 'AC_OTH_AUTO'; // Event insert into agenda automatically
								$actioncomm->socid = $tmpinvoice->thirdparty->id; // To link to a company
								$actioncomm->contact_id = 0;

								$actioncomm->code = 'AC_EMAIL';
								$actioncomm->label = 'sendEmailsRemindersOnInvoiceDueDateKO';
								$actioncomm->note_private = $errormesg;
								$actioncomm->fk_project = $tmpinvoice->fk_project;
								$actioncomm->datep = dol_now();
								$actioncomm->datef = $actioncomm->datep;
								$actioncomm->percentage = -1; // Not applicable
								$actioncomm->authorid = $user->id; // User saving action
								$actioncomm->userownerid = $user->id; // Owner of action
								// Fields when action is an email (content should be added into note)
								$actioncomm->email_msgid = $cMailFile->msgid;
								$actioncomm->email_from = $from;
								$actioncomm->email_sender = '';
								$actioncomm->email_to = $to;
								//$actioncomm->email_tocc = $sendtocc;
								//$actioncomm->email_tobcc = $sendtobcc;
								//$actioncomm->email_subject = $subject;
								$actioncomm->errors_to = $errors_to;

								//$actioncomm->extraparams = $extraparams;

								$actioncomm->create($user);
							}

							$this->db->commit();	// We always commit
						}

						if ($errormesg) {
							$errorsMsg[] = $errormesg;
						}
					} else {
						$errorsMsg[] = 'Failed to fetch record invoice with ID = '.$obj->id;
						$error++;
					}
				}
			}
		} else {
			$error++;
		}

		if (!$error) {
			$this->output .= 'Nb of emails sent : '.$nbMailSend;

			dol_syslog(__METHOD__." end - ".$this->output, LOG_INFO);

			return 0;
		} else {
			$this->error = 'Nb of emails sent : '.$nbMailSend.', '.(!empty($errorsMsg) ? implode(', ', $errorsMsg) : $error);

			dol_syslog(__METHOD__." end - ".$this->error, LOG_INFO);

			return $error;
		}
	}

	/**
	 * See if current invoice date is posterior to the last invoice date among validated invoices of same type.
	 *
	 * @param 	boolean 	$allow_validated_drafts		return true if the invoice has been validated before returning to DRAFT state.
	 * @return 	array{0?:bool,1?:string}					return array
	 */
	public function willBeLastOfSameType($allow_validated_drafts = false)
	{
		// get date of last validated invoices of same type
		$sql  = "SELECT datef";
		$sql .= " FROM ".MAIN_DB_PREFIX."facture";
		$sql .= " WHERE type = " . (int) $this->type ;
		$sql .= " AND date_valid IS NOT NULL";
		$sql .= " AND entity IN (".getEntity('invoice').")";
		$sql .= " ORDER BY datef DESC LIMIT 1";

		$result = $this->db->query($sql);
		if ($result) {
			// compare with current validation date
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$last_date = $this->db->jdate($obj->datef);
				$invoice_date = $this->date;

				$is_last_of_same_type = $invoice_date >= $last_date;
				if ($allow_validated_drafts) {
					$is_last_of_same_type = $is_last_of_same_type || (!strpos($this->ref, 'PROV') && $this->status == self::STATUS_DRAFT);
				}

				return array($is_last_of_same_type, $last_date);
			} else {
				// element is first of type to be validated
				return array(true);
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param	string	    			$option			Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param	array{string,mixed}		$arraydata		Array of data
	 *  @return	string									HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$picto = $this->picto;
		if ($this->type == self::TYPE_REPLACEMENT) {
			$picto .= 'r'; // Replacement invoice
		}
		if ($this->type == self::TYPE_CREDIT_NOTE) {
			$picto .= 'a'; // Credit note
		}
		if ($this->type == self::TYPE_DEPOSIT) {
			$picto .= 'd'; // Deposit invoice
		}

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['thirdparty'])) {
			$return .= '<br><span class="info-box-label">'.$arraydata['thirdparty'].'</span>';
		}
		if (property_exists($this, 'date')) {
			$return .= '<br><span class="info-box-label">'.dol_print_date($this->date, 'day').'</span>';
		}
		if (property_exists($this, 'total_ht')) {
			$return .= ' &nbsp; <span class="info-box-label amount" title="'.dol_escape_htmltag($langs->trans("AmountHT")).'">'.price($this->total_ht);
			$return .= ' '.$langs->trans("HT");
			$return .= '</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$alreadypaid = (empty($arraydata['alreadypaid']) ? 0 : $arraydata['alreadypaid']);
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3, $alreadypaid).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
