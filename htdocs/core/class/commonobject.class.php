<?php
/* Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2020 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012-2013 Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2011-2022 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2012-2015 Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015-2022 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016      Bahfir abbes         <bafbes@gmail.com>
 * Copyright (C) 2017      ATM Consulting       <support@atm-consulting.fr>
 * Copyright (C) 2017-2019 Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2017      Rui Strecht          <rui.strecht@aliartalentos.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2018      Josep Lluís Amador   <joseplluis@lliuretic.cat>
 * Copyright (C) 2023      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2021      Grégory Blémand      <gregory.blemand@atm-consulting.fr>
 * Copyright (C) 2023      Lenin Rivas      	<lenin.rivas777@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 *	\file       htdocs/core/class/commonobject.class.php
 *	\ingroup    core
 *	\brief      File of parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/doldeprecationhandler.class.php';

/**
 *	Parent class of all other business classes (invoices, contracts, proposals, orders, ...)
 *
 * @phan-forbid-undeclared-magic-properties
 */
abstract class CommonObject
{
	use DolDeprecationHandler;

	const TRIGGER_PREFIX = ''; // to be overridden in child class implementations, i.e. 'BILL', 'TASK', 'PROPAL', etc.

	/**
	 * @var string		ID of module.
	 */
	public $module;

	/**
	 * @var DoliDB		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var int 		The object identifier
	 */
	public $id;

	/**
	 * @var int 		The environment ID when using a multicompany module
	 */
	public $entity;

	/**
	 * @var string 		Error string
	 * @see             $errors
	 */
	public $error;

	/**
	 * @var string 		Error string that is hidden but can be used to store additional technical code
	 */
	public $errorhidden;

	/**
	 * @var string[]	Array of error strings
	 */
	public $errors = array();

	/**
	 * @var array<string,string>	To store error results of ->validateField()
	 */
	private $validateFieldsErrors = array();

	/**
	 * @var string 		ID to identify managed object
	 */
	public $element;

	/**
	 * @var string|int	Field with ID of parent key if this field has a parent (a string). For example 'fk_product'.
	 *					ID of parent key itself (an int). For example in few classes like 'Comment', 'ActionComm' or 'AdvanceTargetingMailing'.
	 */
	public $fk_element;

	/**
	 * @var string 		Name to use for 'features' parameter to check module permissions user->rights->feature with restrictedArea().
	 * 					Undefined means same value than $element.
	 *					Can be use to force a check on another element (for example for class of a line, we mention here its parent element).
	 */
	public $element_for_permission;

	/**
	 * @var string 		Name of table without prefix where object is stored
	 */
	public $table_element;

	/**
	 * @var string 		Name of subtable line
	 */
	public $table_element_line = '';

	/**
	 * @var int<0,1>|string  	Does this object support multicompany module ?
	 * 							0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table (example 'fk_soc@societe')
	 */
	public $ismultientitymanaged;

	/**
	 * @var string		Key value used to track if data is coming from import wizard
	 */
	public $import_key;

	/**
	 * @var array<string,mixed>	Contains data to manage extrafields
	 */
	public $array_options = array();


	/**
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>	Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array();

	/**
	 * @var array<string,array<string,string>>	Array to store alternative languages values of object
	 *
	 * Note: call fetchValuesForExtraLanguages() before using this
	 */
	public $array_languages = null; // Value is array() when load already tried

	/**
	 * @var array<int,array{parentId:int,source:string,socid:int,id:int,nom:string,civility:string,lastname:string,firstname:string,email:string,login:string,photo:string,statuscontact:int,rowid:int,code:string,libelle:string,status:string,fk_c_type_contact:int}>	 	To store result of ->liste_contact()
	 */
	public $contacts_ids;

	/**
	 * @var mixed		Array of linked objects, set and used when calling ->create() to be able to create links during the creation of object
	 */
	public $linked_objects;

	/**
	 * @var int[][]		Array of linked objects ids. Loaded by ->fetchObjectLinked
	 */
	public $linkedObjectsIds;

	/**
	 * @var array<string,CommonObject[]>	Array of linked objects. Loaded by ->fetchObjectLinked, key = object type
	 */
	public $linkedObjects;

	/**
	 * @var array<int,bool>	Array of boolean with object id as key and value as true if linkedObjects full loaded for object id. Loaded by ->fetchObjectLinked. Important for pdf generation time reduction.
	 */
	private $linkedObjectsFullLoaded = array();

	/**
	 * @var ?static		To store a cloned copy of the object before editing it (to keep track of its former properties)
	 */
	public $oldcopy;

	/**
	 * @var string 		To store the old value of a modified reference
	 */
	public $oldref;

	/**
	 * @var string		Column name of the ref field.
	 */
	protected $table_ref_field = '';

	/**
	 * @var integer 	0=Default, 1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 */
	public $restrictiononfksoc = 0;


	// The following vars are used by some objects only.
	// We keep these properties in CommonObject in order to provide common methods using them.

	/**
	 * @var array<string,mixed>		Can be used to pass information when only the object is provided to the method
	 */
	public $context = array();

	/**
	 * @var string	Properties set and used by Agenda trigger
	 */
	public $actionmsg;
	/**
	 * @var string	Properties set and used by Agenda trigger
	 */
	public $actionmsg2;

	/**
	 * @var string			Contains canvas name if record is an alternative canvas record
	 */
	public $canvas;

	/**
	 * @var Project|null 	The related project object
	 * @see fetch_projet()
	 */
	public $project;

	/**
	 * @var int 			The related project ID
	 * @see setProject(), project
	 */
	public $fk_project;

	/**
	 * @var ?Project	 	The related project object
	 * @deprecated  Use $project instead.
	 * @see $project
	 */
	private $projet;

	/**
	 * @var int
	 * @deprecated  		Use $fk_project instead.
	 * @see $fk_project
	 */
	public $fk_projet;

	/**
	 * @var Contact|null 	A related contact object
	 * @see fetch_contact()
	 */
	public $contact;

	/**
	 * @var int 			The related contact ID
	 * @see fetch_contact()
	 */
	public $contact_id;

	/**
	 * @var Societe|null 	A related thirdparty object
	 * @see fetch_thirdparty()
	 */
	public $thirdparty;

	/**
	 * @var User 			A related user
	 * @see fetch_user()
	 */
	public $user;

	/**
	 * @var string 		The type of originating object. Combined with $origin_id, it allows to reload $origin_object
	 * @see fetch_origin()
	 */
	public $origin_type;

	/**
	 * @var int 		The id of originating object. Combined with $origin_type, it allows to reload $origin_object
	 * @see fetch_origin()
	 */
	public $origin_id;

	/**
	 * @var	?CommonObject	Origin object. This is set by fetch_origin() from this->origin_type and this->origin_id.
	 */
	public $origin_object;

	/**
	 * @var CommonObject|string|null	Sometimes the type of the originating object ('commande', 'facture', ...), sometimes the object (as with MouvementStock)
	 * @deprecated						Use $origin_type and $origin_id instead.
	 * @see fetch_origin()
	 */
	public $origin;

	/**
	 * TODO Remove this. Has been replaced with ->origin_object.
	 * This is set by fetch_origin() from this->origin and this->origin_id
	 *
	 * @var CommonObject
	 * @deprecated Use $origin_object instead.
	 * @see $origin_object
	 */
	private $expedition;

	/**
	 * @var CommonObject
	 * @deprecated Use $origin_object instead.
	 * @see $origin_object
	 */
	private $livraison;

	/**
	 * @var CommonObject
	 * @deprecated Use $origin_object instead.
	 * @see $origin_object
	 */
	private $commandeFournisseur;


	/**
	 * @var string 		The object's reference
	 */
	public $ref;

	/**
	 * @var string 		An external reference to the object
	 */
	public $ref_ext;

	/**
	 * @var string 		The object's previous reference
	 */
	public $ref_previous;

	/**
	 * @var string 		The object's next reference
	 */
	public $ref_next;

	/**
	 * @var string 		Ref to store on object to save the new ref to use for example when making a validate() of an object
	 */
	public $newref;

	/**
	 * @var int|array<int, string>      The object's status. Use status instead.
	 * @deprecated  Use $status instead.
	 * @see $status
	 * @see setStatut(), $status
	 */
	public $statut;

	/**
	 * @var int|array<int, string>   The object's status (an int).
	 *                 						Or an array listing all the potential status of the object:
	 *                                    	array: int of the status => translated label of the status
	 *                                    	See for example the Account class.
	 * @see setStatut()
	 */
	public $status;


	/**
	 * @var string		Country name
	 * @see getFullAddress()
	 */
	public $country;

	/**
	 * @var int			Country ID
	 * @see getFullAddress(), country
	 */
	public $country_id;

	/**
	 * @var string		ISO country code on 2 chars
	 * @see getFullAddress(), isInEEC(), country
	 */
	public $country_code;

	/**
	 * @var string		State name
	 * @see getFullAddress()
	 */
	public $state;

	/**
	 * @var int			State ID
	 * @see getFullAddress(), state
	 */
	public $state_id;

	/**
	 * @var	int			State ID
	 * @deprecated	Use $state_id. We can remove this property when the field 'fk_departement' have been renamed into 'state_id' in all tables
	 */
	public $fk_departement;

	/**
	 * @var string		State code
	 * @see getFullAddress(), $state
	 */
	public $state_code;

	/**
	 * @var int			Region ID
	 * @see getFullAddress(), $region_code, $region
	 */
	public $region_id;

	/**
	 * @var string		Region code
	 * @see getFullAddress(), $region_id, $region
	 */
	public $region_code;

	/**
	 * @var string		Region name
	 * @see getFullAddress(), $region_id, $region_code
	 */
	public $region;


	/**
	 * @var int			Barcode type
	 * @see fetch_barcode()
	 */
	public $barcode_type;

	/**
	 * @var string		Code of the barcode type
	 * @see fetch_barcode(), barcode_type
	 */
	public $barcode_type_code;

	/**
	 * @var string		Label of the barcode type
	 * @see fetch_barcode(), barcode_type
	 */
	public $barcode_type_label;

	/**
	 * @var string
	 * @see fetch_barcode(), barcode_type
	 */
	public $barcode_type_coder;

	/**
	 * @var int 		Payment method ID (cheque, cash, ...)
	 * @see setPaymentMethods()
	 */
	public $mode_reglement_id;

	/**
	 * @var int 		Payment terms ID
	 * @see setPaymentTerms()
	 */
	public $cond_reglement_id;

	/**
	 * @var int 		Demand reason ID
	 */
	public $demand_reason_id;

	/**
	 * @var int 		Transport mode ID (For module intracomm report)
	 * @see setTransportMode()
	 */
	public $transport_mode_id;

	/**
	 * @var int|string 		Payment terms ID
	 * @deprecated  Use $cond_reglement_id instead - Kept for compatibility
	 * @see $cond_reglement_id
	 *
	 * Note: cond_reglement can not be aliased to cond_reglement!!!
	 */
	private $cond_reglement;  // Private to call DolDeprecationHandler
	/**
	 * @var int|string Internal to detect deprecated access
	 */
	protected $depr_cond_reglement;  // Internal value for deprecation

	/**
	 * @var int 		Delivery address ID
	 * @see setDeliveryAddress()
	 * @deprecated
	 */
	public $fk_delivery_address;

	/**
	 * @var int 		Shipping method ID
	 * @see setShippingMethod()
	 */
	public $shipping_method_id;

	/**
	 * @var string 		Shipping method label
	 * @see setShippingMethod()
	 */
	public $shipping_method;

	// Multicurrency
	/**
	 * @var int ID
	 */
	public $fk_multicurrency;

	/**
	 * @var string|string[]             Multicurrency code
	 *                                  Or, just for the Paiement object, an array: invoice ID => currency code for that invoice.
	 */
	public $multicurrency_code;

	/**
	 * @var float|float[]               Multicurrency rate ("tx" = "taux" in French)
	 *                                  Or, just for the Paiement object, an array: invoice ID => currency rate for that invoice.
	 */
	public $multicurrency_tx;

	/**
	 * @var float 		Multicurrency total amount excluding taxes (HT = "Hors Taxe" in French)
	 */
	public $multicurrency_total_ht;

	/**
	 * @var float 		Multicurrency total VAT amount (TVA = "Taxe sur la Valeur Ajoutée" in French)
	 */
	public $multicurrency_total_tva;

	/**
	 * @var float 		Multicurrency total amount including taxes (TTC = "Toutes Taxes Comprises" in French)
	 */
	public $multicurrency_total_ttc;

	/**
	 * @var float Multicurrency total localta1
	 */
	public $multicurrency_total_localtax1;	// not in database

	/**
	 * @var float Multicurrency total localtax2
	 */
	public $multicurrency_total_localtax2;	// not in database

	/**
	 * @var string
	 * @see SetDocModel()
	 */
	public $model_pdf;

	/**
	 * @var string
	 * Contains relative path of last generated main file
	 */
	public $last_main_doc;

	/**
	 * @var int 		Bank account ID sometimes, ID of record into llx_bank sometimes
	 * @deprecated
	 * @see $fk_account
	 */
	public $fk_bank;

	/**
	 * @var int 		Bank account ID
	 * @see SetBankAccount()
	 */
	public $fk_account;

	/**
	 * @var string 		Public note
	 * @see update_note()
	 */
	public $note_public;

	/**
	 * @var string 		Private note
	 * @see update_note()
	 */
	public $note_private;

	/**
	 * @var string
	 * @deprecated Use $note_private instead.
	 * @see $note_private
	 */
	public $note;

	/**
	 * @var float 		Total amount excluding taxes (HT = "Hors Taxe" in French)
	 * @see update_price()
	 */
	public $total_ht;

	/**
	 * @var float 		Total VAT amount (TVA = "Taxe sur la Valeur Ajoutée" in French)
	 * @see update_price()
	 */
	public $total_tva;

	/**
	 * @var float 		Total local tax 1 amount
	 * @see update_price()
	 */
	public $total_localtax1;

	/**
	 * @var float 		Total local tax 2 amount
	 * @see update_price()
	 */
	public $total_localtax2;

	/**
	 * @var float 		Total amount including taxes (TTC = "Toutes Taxes Comprises" in French)
	 * @see update_price()
	 */
	public $total_ttc;


	/**
	 * @var CommonObjectLine[]|CommonObject[]|stdClass[]
	 */
	public $lines;

	/**
	 * @var string	Action type code to use to record auto event in agenda. For example 'AC_OTH_AUTO'
	 */
	public $actiontypecode;

	/**
	 * @var mixed		Comments
	 * @see fetchComments()
	 */
	public $comments = array();

	/**
	 * @var string 		The name
	 */
	public $name;

	/**
	 * @var string 		The lastname
	 */
	public $lastname;

	/**
	 * @var string 		The firstname
	 */
	public $firstname;

	/**
	 * @var string 		The civility code, not an integer
	 */
	public $civility_id;

	// Dates
	/**
	 * @var integer|''|null		Object creation date
	 */
	public $date_creation;

	/**
	 * @var integer|''|null		Object last validation date
	 */
	public $date_validation;

	/**
	 * @var integer|''|null		Object last modification date
	 */
	public $date_modification;

	/**
	 * Update timestamp record (tms)
	 * @var integer
	 * @deprecated					Use $date_modification
	 */
	public $tms;

	/**
	 * @var int|''|null
	 * @deprecated Use $date_modification instead.
	 * @see $date_modification
	 */
	private $date_update;

	/**
	 * @var integer|''|null		Object closing date
	 */
	public $date_cloture;

	/**
	 * @var User		User author/creation
	 * @deprecated		Store only id in user_creation_id
	 */
	public $user_author;

	/**
	 * @var User		User author/creation
	 * @deprecated
	 */
	public $user_creation;

	/**
	 * @var int			User id author/creation
	 */
	public $user_creation_id;

	/**
	 * @var User		User of validation
	 * @deprecated
	 */
	public $user_valid;

	/**
	 * @var User		User of validation
	 * @deprecated
	 */
	public $user_validation;

	/**
	 * @var int|null		User id of validation
	 */
	public $user_validation_id;

	/**
	 * @var int			User id closing object
	 */
	public $user_closing_id;

	/**
	 * @var User	User last modifier
	 * @deprecated
	 */
	public $user_modification;

	/**
	 * @var int			User ID who last modified the object
	 */
	public $user_modification_id;

	/**
	 * @var int ID
	 * @deprecated	Use $user_creation_id
	 */
	public $fk_user_creat;

	/**
	 * @var int ID
	 * @deprecated 	Use $user_modification_id
	 */
	public $fk_user_modif;


	/**
	 * @var string XX
	 */
	public $next_prev_filter;

	/**
	 * @var int<0,1> 1 if object is specimen
	 */
	public $specimen = 0;

	/**
	 * @var	int[]		Id of contacts to send objects (mails, etc.)
	 */
	public $sendtoid;

	/**
	 * @var	float		Amount already paid from getSommePaiement() (used to show correct status)
	 * @deprecated		Use $totalpaid instead
	 * @see $totalpaid
	 */
	private $alreadypaid;

	/**
	 * @var	float		Amount already paid from getSommePaiement() (used to show correct status)
	 */
	public $totalpaid;

	/**
	 * @var array<int,string>		Array with labels of status
	 */
	public $labelStatus = array();

	/**
	 * @var array<int,string>	Array with short labels of status
	 */
	public $labelStatusShort = array();

	/**
	 * @var array<string,int|string>	Array to store lists of tpl
	 */
	public $tpl;


	/**
	 * @var int 		show photo on popup
	 */
	public $showphoto_on_popup;

	/**
	 * @var array{actionscomm?:int,banklines?:int,cheques?:int,contacts?:int,contracts?:int,customers?:int,dolresource?:int,donations?:int,expensereports?:int,holidays?:int,interventions?:int,invoices?:int,members?:int,orders?:int,products?:int,projects?:int,proposals?:int,prospects?:int,services?:int,supplier_invoices?:int,supplier_orders?:int,supplier_proposals?:int,suppliers?:int,tasks?:int,ticket?:int,users?:int}		nb used in load_stateboard
	 */
	public $nb = array();

	/**
	 * @var int			used for the return of show_photos()
	 */
	public $nbphoto;

	/**
	 * @var string 		output content. Used topropagate information by cron jobs.
	 */
	public $output;

	/**
	 * @var array<string,string>|string	extra parameters. Try to store here the array of parameters. Old code is sometimes storing a string.
	 */
	public $extraparams = array();

	/**
	 * @var string[]|array<string,string[]|array{parent:string,parentkey:string}>	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var string[]	List of child tables. To know object to delete on cascade.
	 *               If name is like '@ClassName:FilePathClass:ParentFkFieldName', it will
	 *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object.
	 */
	protected $childtablesoncascade = array();

	/**
	 * @var Product 	Populated by fetch_product()
	 */
	public $product;

	/**
	 * @var int 		Populated by setPaymentTerms()
	 */
	public $cond_reglement_supplier_id;

	/**
	 * @var float|string Deposit percent for payment terms.
	 *                   Populated by setPaymentTerms().
	 * @see setPaymentTerms()
	 */
	public $deposit_percent;


	/**
	 * @var int 		Populated by setRetainedWarrantyPaymentTerms()
	 */
	public $retained_warranty_fk_cond_reglement;

	/**
	 * @var int 		Populated by setWarehouse()
	 */
	public $warehouse_id;

	/**
	 * @var int<0,1>	Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;


	// No constructor as it is an abstract class

	/**
	 * Provide list of deprecated properties and replacements
	 *
	 * @return array<string,string>
	 */
	protected function deprecatedProperties()
	{
		return array(
			'alreadypaid' => 'totalpaid',
			'cond_reglement' => 'depr_cond_reglement',
			//'note' => 'note_private',		// Some classes needs ->note and others need ->note_public/private so we can't manage deprecation for this field with dolDeprecationHandler
			'commandeFournisseur' => 'origin_object',
			'expedition' => 'origin_object',
			'fk_project' => 'fk_project',
			'livraison' => 'origin_object',
			'projet' => 'project',
			'statut' => 'status',
		);
	}


	/**
	 * Check if an object id or ref exists
	 * If you don't need or want to instantiate the object and just need to know if the object exists, use this method instead of fetch
	 *
	 *  @param	string	$element   	String of element ('product', 'facture', ...)
	 *  @param	int		$id      	Id of object
	 *  @param  string	$ref     	Ref of object to check
	 *  @param	string	$ref_ext	Ref ext of object to check
	 *  @return int     			Return integer <0 if KO, 0 if OK but not found, >0 if OK and exists
	 */
	public static function isExistingObject($element, $id, $ref = '', $ref_ext = '')
	{
		global $db, $conf;

		$sql = "SELECT rowid, ref, ref_ext";
		$sql .= " FROM ".$db->prefix().$element;
		$sql .= " WHERE entity IN (".getEntity($element).")";

		if ($id > 0) {
			$sql .= " AND rowid = ".((int) $id);
		} elseif ($ref) {
			$sql .= " AND ref = '".$db->escape($ref)."'";
		} elseif ($ref_ext) {
			$sql .= " AND ref_ext = '".$db->escape($ref_ext)."'";
		} else {
			$error = 'ErrorWrongParameters';
			dol_syslog(get_class()."::isExistingObject ".$error, LOG_ERR);
			return -1;
		}
		if ($ref || $ref_ext) {		// Because the same ref can exists in 2 different entities, we force the current one in priority
			$sql .= " AND entity = ".((int) $conf->entity);
		}

		dol_syslog(get_class()."::isExistingObject", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			if ($num > 0) {
				return 1;
			} else {
				return 0;
			}
		}
		return -1;
	}

	/**
	 * isEmpty We consider CommonObject isEmpty if this->id is empty
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return (empty($this->id));
	}

	/**
	 * setErrorsFromObject
	 *
	 * @param CommonObject $object commonobject
	 * @return void
	 */
	public function setErrorsFromObject($object)
	{
		if (!empty($object->error)) {
			$this->error = $object->error;
		}
		if (!empty($object->errors)) {
			$this->errors = array_merge($this->errors, $object->errors);
		}
	}

	/**
	 * Return array of data to show into a tooltip. This method must be implemented in each object class.
	 *
	 * @since v18
	 * @param array<string,mixed> $params params to construct tooltip data
	 * @return array<string,string>	Data to show in tooltip
	 */
	public function getTooltipContentArray($params)
	{
		return [];
	}

	/**
	 * getTooltipContent
	 *
	 * @param array<string,mixed>	$params	params
	 * @since v18
	 * @return string
	 */
	public function getTooltipContent($params)
	{
		global $action, $extrafields, $langs, $hookmanager;

		// If there is too much extrafields, we do not include them into tooltip
		$MAX_EXTRAFIELDS_TO_SHOW_IN_TOOLTIP = getDolGlobalInt('MAX_EXTRAFIELDS_TO_SHOW_IN_TOOLTIP', 3);

		$data = $this->getTooltipContentArray($params);
		$count = 0;

		// Add extrafields
		if (!empty($extrafields->attributes[$this->table_element]['label'])) {
			$data['opendivextra'] = '<div class="centpercent wordbreak divtooltipextra">';
			foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
				if ($extrafields->attributes[$this->table_element]['type'][$key] == 'separate') {
					continue;
				}
				if ($count >= abs($MAX_EXTRAFIELDS_TO_SHOW_IN_TOOLTIP)) {
					$data['more_extrafields'] = '<br>...';
					break;
				}
				$enabled = 1;
				if ($enabled && isset($extrafields->attributes[$this->table_element]['enabled'][$key])) {
					$enabled = (int) dol_eval((string) $extrafields->attributes[$this->table_element]['enabled'][$key], 1, 1, '2');
				}
				if ($enabled && isset($extrafields->attributes[$this->table_element]['list'][$key])) {
					$enabled = (int) dol_eval($extrafields->attributes[$this->table_element]['list'][$key], 1, 1, '2');
				}
				$perms = 1;
				if ($perms && isset($extrafields->attributes[$this->table_element]['perms'][$key])) {
					$perms = (int) dol_eval($extrafields->attributes[$this->table_element]['perms'][$key], 1, 1, '2');
				}
				if (empty($enabled)) {
					continue; // 0 = Never visible field
				}
				if (abs($enabled) != 1 && abs($enabled) != 3 && abs($enabled) != 5 && abs($enabled) != 4) {
					continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list <> 4 = not visible at the creation
				}
				if (empty($perms)) {
					continue; // 0 = Not visible
				}
				if (!empty($extrafields->attributes[$this->table_element]['langfile'][$key])) {
					$langs->load($extrafields->attributes[$this->table_element]['langfile'][$key]);
				}
				$labelextra = $langs->trans((string) $extrafields->attributes[$this->table_element]['label'][$key]);
				if ($extrafields->attributes[$this->table_element]['type'][$key] == 'separate') {
					$data[$key] = '<br><b><u>'. $labelextra . '</u></b>';
				} else {
					$value = (empty($this->array_options['options_' . $key]) ? '' : $this->array_options['options_' . $key]);
					$data[$key] = '<br><b>'. $labelextra . ':</b> ' . $extrafields->showOutputField($key, $value, '', $this->table_element);
					$count++;
				}
			}
			$data['closedivextra'] = '</div>';
		}

		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array(
			'tooltipcontentarray' => &$data,
			'params' => $params,
		);
		// Note that $action and $object may have been modified by some hooks
		$hookmanager->executeHooks('getTooltipContent', $parameters, $this, $action);

		//var_dump($data);
		$label = implode($data);

		return $label;
	}


	/**
	 * Method to output saved errors
	 *
	 * @return	string		String with errors
	 */
	public function errorsToString()
	{
		return $this->error.(is_array($this->errors) ? (($this->error != '' ? ', ' : '').implode(', ', $this->errors)) : '');
	}


	/**
	 * Return customer ref for screen output.
	 *
	 * @param  string      $objref        Customer ref
	 * @return string                     Customer ref formatted
	 */
	public function getFormatedCustomerRef($objref)
	{
		global $hookmanager;

		$parameters = array('objref' => $objref);
		$action = '';
		$reshook = $hookmanager->executeHooks('getFormatedCustomerRef', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			return $hookmanager->resArray['objref'];
		}
		return $objref.(isset($hookmanager->resArray['objref']) ? $hookmanager->resArray['objref'] : '');
	}

	/**
	 * Return supplier ref for screen output.
	 *
	 * @param  string      $objref        Supplier ref
	 * @return string                     Supplier ref formatted
	 */
	public function getFormatedSupplierRef($objref)
	{
		global $hookmanager;

		$parameters = array('objref' => $objref);
		$action = '';
		$reshook = $hookmanager->executeHooks('getFormatedSupplierRef', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			return $hookmanager->resArray['objref'];
		}
		return $objref.(isset($hookmanager->resArray['objref']) ? $hookmanager->resArray['objref'] : '');
	}

	/**
	 * 	Return full address of contact
	 *
	 * 	@param		int<0,1>	$withcountry		1=Add country into address string
	 *  @param		string		$sep				Separator to use to build string
	 *  @param		int<0,1>    $withregion			1=Add region into address string
	 *  @param		string		$extralangcode		User extralanguages as value
	 *	@return		string							Full address string
	 */
	public function getFullAddress($withcountry = 0, $sep = "\n", $withregion = 0, $extralangcode = '')
	{
		if ($withcountry && $this->country_id && (empty($this->country_code) || empty($this->country))) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			$tmparray = getCountry($this->country_id, 'all');
			$this->country_code = $tmparray['code'];
			$this->country = $tmparray['label'];
		}

		if ($withregion && $this->state_id && (empty($this->state_code) || empty($this->state) || empty($this->region) || empty($this->region_code))) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			$tmparray = getState($this->state_id, 'all', null, 1);
			$this->state_code   = $tmparray['code'];
			$this->state        = $tmparray['label'];
			$this->region_code  = $tmparray['region_code'];
			$this->region       = $tmparray['region'];
		}

		return dol_format_address($this, $withcountry, $sep, null, 0, $extralangcode);
	}


	/**
	 * Return the link of last main doc file for direct public download.
	 *
	 * @param	string	$modulepart			Module related to document
	 * @param	int		$initsharekey		Init the share key if it was not yet defined
	 * @param	int		$relativelink		0=Return full external link, 1=Return link relative to root of file
	 * @return	string|-1					Returns the link, or an empty string if no link was found, or -1 if error.
	 */
	public function getLastMainDocLink($modulepart, $initsharekey = 0, $relativelink = 0)
	{
		global $user, $dolibarr_main_url_root;

		if (empty($this->last_main_doc)) {
			return ''; // No way to known which document name to use
		}

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile = new EcmFiles($this->db);
		$result = $ecmfile->fetch(0, '', $this->last_main_doc);
		if ($result < 0) {
			$this->error = $ecmfile->error;
			$this->errors = $ecmfile->errors;
			return -1;
		}

		if (empty($ecmfile->id)) {	// No entry into file index already exists, we should initialize the shared key manually.
			// Add entry into index
			if ($initsharekey) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

				// TODO We can't, we don't have full path of file, only last_main_doc and ->element, so we must first rebuild full path $destfull
				/*
				$ecmfile->filepath = $rel_dir;
				$ecmfile->filename = $filename;
				$ecmfile->label = md5_file(dol_osencode($destfull));    // hash of file content
				$ecmfile->fullpath_orig = '';
				$ecmfile->gen_or_uploaded = 'generated';
				$ecmfile->description = '';    // indexed content
				$ecmfile->keywords = '';        // keyword content
				$ecmfile->share = getRandomPassword(true);
				$result = $ecmfile->create($user);
				if ($result < 0)
				{
					$this->error = $ecmfile->error;
					$this->errors = $ecmfile->errors;
				}
				*/
			} else {
				return '';
			}
		} elseif (empty($ecmfile->share)) {	// Entry into file index already exists but no share key is defined.
			// Add entry into index
			if ($initsharekey) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
				$ecmfile->share = getRandomPassword(true);
				$ecmfile->update($user);
			} else {
				return '';
			}
		}
		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		// This is to use external domain name found into config file
		//if (DOL_URL_ROOT && ! preg_match('/\/$/', $urlwithouturlroot) && ! preg_match('/^\//', DOL_URL_ROOT)) $urlwithroot=$urlwithouturlroot.'/'.DOL_URL_ROOT;
		//else
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		$forcedownload = 0;

		$paramlink = '';
		//if (!empty($modulepart)) $paramlink.=($paramlink?'&':'').'modulepart='.$modulepart;		// For sharing with hash (so public files), modulepart is not required.
		//if (!empty($ecmfile->entity)) $paramlink.='&entity='.$ecmfile->entity; 					// For sharing with hash (so public files), entity is not required.
		//$paramlink.=($paramlink?'&':'').'file='.urlencode($filepath);								// No need of name of file for public link, we will use the hash
		if (!empty($ecmfile->share)) {
			$paramlink .= ($paramlink ? '&' : '').'hashp='.$ecmfile->share; // Hash for public share
		}
		if ($forcedownload) {
			$paramlink .= ($paramlink ? '&' : '').'attachment=1';
		}

		if ($relativelink) {
			$linktoreturn = 'document.php'.($paramlink ? '?'.$paramlink : '');
		} else {
			$linktoreturn = $urlwithroot.'/document.php'.($paramlink ? '?'.$paramlink : '');
		}

		// Here $ecmfile->share is defined
		return $linktoreturn;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add a link between element $this->element and a contact
	 *
	 *  @param	int			$fk_socpeople       Id of thirdparty contact (if source = 'external') or id of user (if source = 'internal') to link
	 *  @param 	int|string	$type_contact 		Type of contact (code or id). Must be id or code found into table llx_c_type_contact. For example: SALESREPFOLL
	 *  @param  string		$source             external=Contact extern (llx_socpeople), internal=Contact intern (llx_user)
	 *  @param  int			$notrigger			Disable all triggers
	 *  @return int         	        		Return integer <0 if KO, 0 if already added or code not valid, >0 if OK
	 */
	public function add_contact($fk_socpeople, $type_contact, $source = 'external', $notrigger = 0)
	{
		// phpcs:enable
		global $user, $langs;


		dol_syslog(get_class($this)."::add_contact $fk_socpeople, $type_contact, $source, $notrigger");

		// Check parameters
		if ($fk_socpeople <= 0) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorWrongValueForParameterX", "1");
			dol_syslog(get_class($this)."::add_contact ".$this->error, LOG_ERR);
			return -1;
		}
		if (!$type_contact) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorWrongValueForParameterX", "2");
			dol_syslog(get_class($this)."::add_contact ".$this->error, LOG_ERR);
			return -2;
		}

		$id_type_contact = 0;
		if (is_numeric($type_contact)) {
			$id_type_contact = $type_contact;
		} else {
			// We look for id type_contact
			$sql = "SELECT tc.rowid";
			$sql .= " FROM ".$this->db->prefix()."c_type_contact as tc";
			$sql .= " WHERE tc.element='".$this->db->escape($this->element)."'";
			$sql .= " AND tc.source='".$this->db->escape($source)."'";
			$sql .= " AND tc.code='".$this->db->escape($type_contact)."' AND tc.active=1";
			//print $sql;
			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$id_type_contact = $obj->rowid;
				}
			}
		}

		if ($id_type_contact == 0) {
			dol_syslog("CODE_NOT_VALID_FOR_THIS_ELEMENT: Code type of contact '".$type_contact."' does not exists or is not active for element ".$this->element.", we can ignore it");
			return 0;
		}

		$datecreate = dol_now();

		// Socpeople must have already been added by some trigger, then we have to check it to avoid DB_ERROR_RECORD_ALREADY_EXISTS error
		$TListeContacts = $this->liste_contact(-1, $source);
		$already_added = false;
		if (is_array($TListeContacts) && !empty($TListeContacts)) {
			foreach ($TListeContacts as $array_contact) {
				if ($array_contact['status'] == 4 && $array_contact['id'] == $fk_socpeople && $array_contact['fk_c_type_contact'] == $id_type_contact) {
					$already_added = true;
					break;
				}
			}
		}

		if (!$already_added) {
			$this->db->begin();

			// Insert into database
			$sql = "INSERT INTO ".$this->db->prefix()."element_contact";
			$sql .= " (element_id, fk_socpeople, datecreate, statut, fk_c_type_contact) ";
			$sql .= " VALUES (".$this->id.", ".((int) $fk_socpeople)." , ";
			$sql .= "'".$this->db->idate($datecreate)."'";
			$sql .= ", 4, ".((int) $id_type_contact);
			$sql .= ")";

			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$notrigger) {
					$result = $this->call_trigger(strtoupper($this->element).'_ADD_CONTACT', $user);
					if ($result < 0) {
						$this->db->rollback();
						return -1;
					}
				}

				$this->db->commit();
				return 1;
			} else {
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$this->error = $this->db->errno();
					$this->db->rollback();
					return -2;
				} else {
					$this->error = $this->db->lasterror();
					$this->db->rollback();
					return -1;
				}
			}
		} else {
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Copy contact from one element to current
	 *
	 *    @param    CommonObject    $objFrom    Source element
	 *    @param    'internal'|'external'	$source	Nature of contact ('internal' or 'external')
	 *    @return   int                         >0 if OK, <0 if KO
	 */
	public function copy_linked_contact($objFrom, $source = 'internal')
	{
		// phpcs:enable
		$contacts = $objFrom->liste_contact(-1, $source);
		foreach ($contacts as $contact) {
			if ($this->add_contact($contact['id'], $contact['fk_c_type_contact'], $contact['source']) < 0) {
				return -1;
			}
		}
		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Update a link to contact line
	 *
	 *      @param	int		$rowid              Id of line contact-element
	 * 		@param	int		$statut	            New status of link
	 *      @param  int		$type_contact_id    Id of contact type (not modified if 0)
	 *      @param  int		$fk_socpeople	    Id of soc_people to update (not modified if 0)
	 *      @return int                 		Return integer <0 if KO, >= 0 if OK
	 */
	public function update_contact($rowid, $statut, $type_contact_id = 0, $fk_socpeople = 0)
	{
		// phpcs:enable
		// Insert into database
		$sql = "UPDATE ".$this->db->prefix()."element_contact set";
		$sql .= " statut = ".$statut;
		if ($type_contact_id) {
			$sql .= ", fk_c_type_contact = ".((int) $type_contact_id);
		}
		if ($fk_socpeople) {
			$sql .= ", fk_socpeople = ".((int) $fk_socpeople);
		}
		$sql .= " where rowid = ".((int) $rowid);
		$resql = $this->db->query($sql);
		if ($resql) {
			return 0;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Delete a link to contact line
	 *
	 *    @param	int		$rowid			Id of contact link line to delete
	 *    @param	int		$notrigger		Disable all triggers
	 *    @return   int						>0 if OK, <0 if KO
	 */
	public function delete_contact($rowid, $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		$error = 0;

		$this->db->begin();

		if (!$error && empty($notrigger)) {
			// Call trigger
			$this->context['contact_id'] = ((int) $rowid);
			$result = $this->call_trigger(strtoupper($this->element).'_DELETE_CONTACT', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete_contact", LOG_DEBUG);

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
			$sql .= " WHERE rowid = ".((int) $rowid);

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Delete all links between an object $this and all its contacts in llx_element_contact
	 *
	 *	  @param	string	$source		'' or 'internal' or 'external'
	 *	  @param	string	$code		Type of contact (code or id)
	 *    @return   int					Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function delete_linked_contact($source = '', $code = '')
	{
		// phpcs:enable
		$listId = '';
		$temp = array();
		$typeContact = $this->liste_type_contact($source, '', 0, 0, $code);

		if (!empty($typeContact)) {
			foreach ($typeContact as $key => $value) {
				array_push($temp, $key);
			}
			$listId = implode(",", $temp);
		}

		// If $listId is empty, we have not criteria on fk_c_type_contact so we will delete record on element_id for
		// any type or record instead of only the ones of the current object. So we do nothing in such a case.
		if (empty($listId)) {
			return 0;
		}

		$sql = "DELETE FROM ".$this->db->prefix()."element_contact";
		$sql .= " WHERE element_id = ".((int) $this->id);
		$sql .= " AND fk_c_type_contact IN (".$this->db->sanitize($listId).")";

		dol_syslog(get_class($this)."::delete_linked_contact", LOG_DEBUG);
		if ($this->db->query($sql)) {
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Get array of all contacts for an object
	 *
	 *    @param	int			$statusoflink	Status of links to get (-1=all). Not used.
	 *    @param	'external'|'thirdparty'|'internal'		$source			Source of contact: 'external' or 'thirdparty' (llx_socpeople) or 'internal' (llx_user)
	 *    @param	int<0,1>	$list       	0:Returned array contains all properties, 1:Return array contains just id
	 *    @param    string      $code       	Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
	 *    @param	int			$status			Status of user or company
	 *    @param	int[]		$arrayoftcids	Array with ID of type of contacts. If we provide this, we can make a ec.fk_c_type_contact in ($arrayoftcids) to avoid link on tc table. TODO Not implemented.
	 *    @return array<int,array{parentId:int,source:string,socid:int,id:int,nom:string,civility:string,lastname:string,firstname:string,email:string,login:string,photo:string,gender:string,statuscontact:int,rowid:int,code:string,libelle:string,status:string,fk_c_type_contact:int}>|int<-1,-1>        	Array of contacts, -1 if error
	 */
	public function liste_contact($statusoflink = -1, $source = 'external', $list = 0, $code = '', $status = -1, $arrayoftcids = array())
	{
		// phpcs:enable
		global $langs;

		$tab = array();

		$sql = "SELECT ec.rowid, ec.statut as statuslink, ec.fk_socpeople as id, ec.fk_c_type_contact"; // This field contains id of llx_socpeople or id of llx_user
		if ($source == 'internal') {
			$sql .= ", '-1' as socid, t.statut as statuscontact, t.login, t.photo, t.gender";
		}
		if ($source == 'external' || $source == 'thirdparty') {
			$sql .= ", t.fk_soc as socid, t.statut as statuscontact";
		}
		$sql .= ", t.civility as civility, t.lastname as lastname, t.firstname, t.email";
		$sql .= ", tc.source, tc.element, tc.code, tc.libelle as type_label";
		$sql .= " FROM ".$this->db->prefix()."c_type_contact tc,";
		$sql .= " ".$this->db->prefix()."element_contact ec";
		if ($source == 'internal') {	// internal contact (user)
			$sql .= " LEFT JOIN ".$this->db->prefix()."user t on ec.fk_socpeople = t.rowid";
		}
		if ($source == 'external' || $source == 'thirdparty') {	// external contact (socpeople)
			$sql .= " LEFT JOIN ".$this->db->prefix()."socpeople t on ec.fk_socpeople = t.rowid";
		}
		$sql .= " WHERE ec.element_id = ".((int) $this->id);
		$sql .= " AND ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = '".$this->db->escape($this->element)."'";
		if ($code) {
			$sql .= " AND tc.code = '".$this->db->escape($code)."'";
		}
		if ($source == 'internal') {
			$sql .= " AND tc.source = 'internal'";
			if ($status >= 0) {
				$sql .= " AND t.statut = ".((int) $status);
			}
		}
		if ($source == 'external' || $source == 'thirdparty') {
			$sql .= " AND tc.source = 'external'";
			if ($status >= 0) {
				$sql .= " AND t.statut = ".((int) $status);	// t is llx_socpeople
			}
		}
		$sql .= " AND tc.active = 1";
		if ($statusoflink >= 0) {
			$sql .= " AND ec.statut = ".((int) $statusoflink);
		}
		$sql .= " ORDER BY t.lastname ASC";

		dol_syslog(get_class($this)."::liste_contact", LOG_DEBUG);
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
						'parentId' => $this->id,
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
						'gender' => (empty($obj->gender) ? '' : $obj->gender),
						'statuscontact' => $obj->statuscontact,
						'rowid' => $obj->rowid,
						'code' => $obj->code,
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
	 * 		Update status of a contact linked to object
	 *
	 * 		@param	int		$rowid		Id of link between object and contact
	 * 		@return	int					Return integer <0 if KO, >=0 if OK
	 */
	public function swapContactStatus($rowid)
	{
		$sql = "SELECT ec.datecreate, ec.statut, ec.fk_socpeople, ec.fk_c_type_contact,";
		$sql .= " tc.code, tc.libelle as type_label";
		$sql .= " FROM (".$this->db->prefix()."element_contact as ec, ".$this->db->prefix()."c_type_contact as tc)";
		$sql .= " WHERE ec.rowid =".((int) $rowid);
		$sql .= " AND ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = '".$this->db->escape($this->element)."'";

		dol_syslog(get_class($this)."::swapContactStatus", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$newstatut = ($obj->statut == 4) ? 5 : 4;
			$result = $this->update_contact($rowid, $newstatut);
			$this->db->free($resql);
			return $result;
		} else {
			$this->error = $this->db->error();
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Return array with list of possible values for type of contacts
	 *
	 *      @param	'internal'|'external'|'all'	$source	'internal', 'external' or 'all'
	 *      @param	string	$order		Sort order by : 'position', 'code', 'rowid'...
	 *      @param  int<0,1>	$option     0=Return array id->label, 1=Return array code->label
	 *      @param  int<0,1>	$activeonly 0=all status of contact, 1=only the active
	 *		@param	string	$code		Type of contact (Example: 'CUSTOMER', 'SERVICE')
	 *      @return array<int,string>|array<string,string>|null	Array list of type of contacts (id->label if option=0, code->label if option=1), or null if error
	 */
	public function liste_type_contact($source = 'internal', $order = 'position', $option = 0, $activeonly = 0, $code = '')
	{
		// phpcs:enable
		global $langs;

		if (empty($order)) {
			$order = 'position';
		}
		if ($order == 'position') {
			$order .= ',code';
		}

		$tab = array();
		$sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle as type_label, tc.position";
		$sql .= " FROM ".$this->db->prefix()."c_type_contact as tc";
		$sql .= " WHERE tc.element='".$this->db->escape($this->element)."'";
		if ($activeonly == 1) {
			$sql .= " AND tc.active=1"; // only the active types
		}
		if (!empty($source) && $source != 'all') {
			$sql .= " AND tc.source='".$this->db->escape($source)."'";
		}
		if (!empty($code)) {
			$sql .= " AND tc.code='".$this->db->escape($code)."'";
		}
		$sql .= $this->db->order($order, 'ASC');

		//print "sql=".$sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$transkey = "TypeContact_".$this->element."_".$source."_".$obj->code;
				$libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->type_label);
				if (empty($option)) {
					$tab[$obj->rowid] = $libelle_type;
				} else {
					$tab[$obj->code] = $libelle_type;
				}
				$i++;
			}
			return $tab;
		} else {
			$this->error = $this->db->lasterror();
			//dol_print_error($this->db);
			return null;
		}
	}

	/**
	 *      Return array with list of possible values for type of contacts
	 *
	 *      @param	string	$source     		'internal', 'external' or 'all'
	 *      @param  int<0,1>	$option     		0=Return array id->label, 1=Return array code->label
	 *      @param  int<0,1>	$activeonly 		0=all status of contact, 1=only the active
	 *		@param	string	$code				Type of contact (Example: 'CUSTOMER', 'SERVICE')
	 *		@param	string	$element			Filter on 1 element type
	 *      @param	string	$excludeelement		Exclude 1 element type. Example: 'agenda'
	 *      @return array<int,string>|array<string,string>|null	Array list of type of contacts (id->label if option=0, code->label if option=1), or null if error
	 */
	public function listeTypeContacts($source = 'internal', $option = 0, $activeonly = 0, $code = '', $element = '', $excludeelement = '')
	{
		global $langs, $conf;

		$langs->loadLangs(array('bills', 'contracts', 'interventions', 'orders', 'projects', 'propal', 'ticket', 'agenda'));

		$tab = array();

		$sql = "SELECT DISTINCT tc.rowid, tc.code, tc.libelle as type_label, tc.position, tc.element";
		$sql .= " FROM ".$this->db->prefix()."c_type_contact as tc";

		$sqlWhere = array();
		if (!empty($element)) {
			$sqlWhere[] = " tc.element='".$this->db->escape($element)."'";
		}
		if (!empty($excludeelement)) {
			$sqlWhere[] = " tc.element <> '".$this->db->escape($excludeelement)."'";
		}

		if ($activeonly == 1) {
			$sqlWhere[] = " tc.active=1"; // only the active types
		}

		if (!empty($source) && $source != 'all') {
			$sqlWhere[] = " tc.source='".$this->db->escape($source)."'";
		}

		if (!empty($code)) {
			$sqlWhere[] = " tc.code='".$this->db->escape($code)."'";
		}

		if (count($sqlWhere) > 0) {
			$sql .= " WHERE ".implode(' AND ', $sqlWhere);
		}

		$sql .= $this->db->order('tc.element, tc.position', 'ASC');

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 0) {
				$langs->loadLangs(array("propal", "orders", "bills", "suppliers", "contracts", "supplier_proposal"));

				while ($obj = $this->db->fetch_object($resql)) {
					$modulename = $obj->element;
					if (strpos($obj->element, 'project') !== false) {
						$modulename = 'projet';
					} elseif ($obj->element == 'contrat') {
						$element = 'contract';
					} elseif ($obj->element == 'action') {
						$modulename = 'agenda';
					} elseif (strpos($obj->element, 'supplier') !== false && $obj->element != 'supplier_proposal') {
						$modulename = 'fournisseur';
					}
					if (!empty($conf->{$modulename}->enabled)) {
						$libelle_element = $langs->trans('ContactDefault_'.$obj->element);
						$tmpelement = $obj->element;
						$transkey = "TypeContact_".$tmpelement."_".$source."_".$obj->code;
						$libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->type_label);
						$tab[$obj->rowid] = $libelle_element.' - '.$libelle_type;
					}
				}
			}
			return $tab;
		} else {
			$this->error = $this->db->lasterror();
			return null;
		}
	}

	/**
	 *      Return id of contacts for a source and a contact code.
	 *      Example: contact client de facturation ('external', 'BILLING')
	 *      Example: contact client de livraison ('external', 'SHIPPING')
	 *      Example: contact interne suivi paiement ('internal', 'SALESREPFOLL')
	 *
	 *		@param	string	$source		'external' or 'internal'
	 *		@param	string	$code		'BILLING', 'SHIPPING', 'SALESREPFOLL', ...
	 *		@param	int		$status		limited to a certain status
	 *      @return int[]|null     		List of id for such contacts, or null if error
	 */
	public function getIdContact($source, $code, $status = 0)
	{
		global $conf;

		$result = array();
		$i = 0;
		// Particular case for shipping
		if ($this->element == 'shipping' && $this->origin_id != 0) {
			$id = $this->origin_id;
			$element = 'commande';
		} elseif ($this->element == 'reception' && $this->origin_id != 0) {
			$id = $this->origin_id;
			$element = 'order_supplier';
		} else {
			$id = $this->id;
			$element = $this->element;
		}

		$sql = "SELECT ec.fk_socpeople";
		$sql .= " FROM ".$this->db->prefix()."element_contact as ec,";
		if ($source == 'internal') {
			$sql .= " ".$this->db->prefix()."user as c,";
		}
		if ($source == 'external') {
			$sql .= " ".$this->db->prefix()."socpeople as c,";
		}
		$sql .= " ".$this->db->prefix()."c_type_contact as tc";
		$sql .= " WHERE ec.element_id = ".((int) $id);
		$sql .= " AND ec.fk_socpeople = c.rowid";
		if ($source == 'internal') {
			$sql .= " AND c.entity IN (".getEntity('user').")";
		}
		if ($source == 'external') {
			$sql .= " AND c.entity IN (".getEntity('societe').")";
		}
		$sql .= " AND ec.fk_c_type_contact = tc.rowid";
		$sql .= " AND tc.element = '".$this->db->escape($element)."'";
		$sql .= " AND tc.source = '".$this->db->escape($source)."'";
		if ($code) {
			$sql .= " AND tc.code = '".$this->db->escape($code)."'";
		}
		$sql .= " AND tc.active = 1";
		if ($status) {
			$sql .= " AND ec.statut = ".((int) $status);
		}

		dol_syslog(get_class($this)."::getIdContact", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$result[$i] = $obj->fk_socpeople;
				$i++;
			}
		} else {
			$this->error = $this->db->error();
			return null;
		}

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Load object contact with id=$this->contact_id into $this->contact
	 *
	 *		@param	?int		$contactid	Id du contact. Use this->contact_id if empty.
	 *		@return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function fetch_contact($contactid = null)
	{
		// phpcs:enable
		if (empty($contactid)) {
			$contactid = $this->contact_id;
		}

		if (empty($contactid)) {
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$contact = new Contact($this->db);
		$result = $contact->fetch($contactid);
		$this->contact = $contact;
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    	Load the third party of object, from id $this->socid or $this->fk_soc, into this->thirdparty
	 *
	 *		@param		int<0,1>	$force_thirdparty_id	Force thirdparty id
	 *		@return		int<-1,1>						Return integer <0 if KO, >0 if OK
	 *		@phan-suppress PhanUndeclaredProperty
	 */
	public function fetch_thirdparty($force_thirdparty_id = 0)
	{
		// phpcs:enable
		if (empty($this->socid) && empty($this->fk_soc) && empty($force_thirdparty_id)) {
			return 0;
		}

		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

		$idtofetch = isset($this->socid) ? $this->socid : (isset($this->fk_soc) ? $this->fk_soc : 0);
		if ($force_thirdparty_id) {
			$idtofetch = $force_thirdparty_id;
		}

		if ($idtofetch) {
			$thirdparty = new Societe($this->db);
			$result = $thirdparty->fetch($idtofetch);
			if ($result < 0) {
				$this->errors = array_merge($this->errors, $thirdparty->errors);
			}
			$this->thirdparty = $thirdparty;

			// Use first price level if level not defined for third party
			if (getDolGlobalString('PRODUIT_MULTIPRICES') && empty($this->thirdparty->price_level)) {
				$this->thirdparty->price_level = 1;
			}

			return $result;
		} else {
			return -1;
		}
	}


	/**
	 * Looks for an object with ref matching the wildcard provided
	 * It does only work when $this->table_ref_field is set
	 *
	 * @param 	string 	$ref 	Wildcard
	 * @return 	int<-1,1>		>1 = OK, 0 = Not found or table_ref_field not defined, <0 = KO
	 */
	public function fetchOneLike($ref)
	{
		if (!$this->table_ref_field) {
			return 0;
		}

		$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE ".$this->table_ref_field." LIKE '".$this->db->escape($ref)."'";	// no escapeforlike here
		$sql .= " LIMIT 1";

		$query = $this->db->query($sql);

		if (!$this->db->num_rows($query)) {
			return 0;
		}

		$result = $this->db->fetch_object($query);

		if (method_exists($this, 'fetch')) {
			return $this->fetch($result->rowid);
		} else {
			$this->error = 'Fetch method not implemented on '.get_class($this);
			dol_syslog(get_class($this).'::fetchOneLike Error='.$this->error, LOG_ERR);
			array_push($this->errors, $this->error);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load data for barcode into properties ->barcode_type*
	 *	Properties ->barcode_type that is id of barcode. Type is used to find other properties, but
	 *  if it is not defined, ->element must be defined to know default barcode type.
	 *
	 *	@return		int<-1,1>		Return integer <0 if KO, 0 if can't guess type of barcode (ISBN, EAN13...), >0 if OK (all barcode properties loaded)
	 */
	public function fetch_barcode()
	{
		// phpcs:enable
		global $conf;

		dol_syslog(get_class($this).'::fetch_barcode this->element='.$this->element.' this->barcode_type='.$this->barcode_type);

		$idtype = $this->barcode_type;
		if (empty($idtype) && $idtype != '0') {	// If type of barcode no set, we try to guess. If set to '0' it means we forced to have type remain not defined
			if ($this->element == 'product' && getDolGlobalString('PRODUIT_DEFAULT_BARCODE_TYPE')) {
				$idtype = getDolGlobalString('PRODUIT_DEFAULT_BARCODE_TYPE');
			} elseif ($this->element == 'societe') {
				$idtype = getDolGlobalString('GENBARCODE_BARCODETYPE_THIRDPARTY');
			} else {
				dol_syslog('Call fetch_barcode with barcode_type not defined and cannot be guessed', LOG_WARNING);
			}
		}

		if ($idtype > 0) {
			if (empty($this->barcode_type) || empty($this->barcode_type_code) || empty($this->barcode_type_label) || empty($this->barcode_type_coder)) {    // If data not already loaded
				$sql = "SELECT rowid, code, libelle as label, coder";
				$sql .= " FROM ".$this->db->prefix()."c_barcode_type";
				$sql .= " WHERE rowid = ".((int) $idtype);
				dol_syslog(get_class($this).'::fetch_barcode', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$obj = $this->db->fetch_object($resql);
					$this->barcode_type       = $obj->rowid;
					$this->barcode_type_code  = $obj->code;
					$this->barcode_type_label = $obj->label;
					$this->barcode_type_coder = $obj->coder;
					return 1;
				} else {
					dol_print_error($this->db);
					return -1;
				}
			}
		}
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Load the project with id $this->fk_project into this->project
	 *
	 *		@return		int<-1,1>		Return integer <0 if KO, >=0 if OK
	 */
	public function fetch_project()
	{
		// phpcs:enable
		return $this->fetch_projet();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Load the project with id $this->fk_project into this->project
	 *
	 *		@return		int			Return integer <0 if KO, >=0 if OK
	 */
	public function fetch_projet()
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

		if (empty($this->fk_project) && !empty($this->fk_projet)) {
			$this->fk_project = $this->fk_projet; // For backward compatibility
		}
		if (empty($this->fk_project)) {
			return 0;
		}

		$project = new Project($this->db);
		$result = $project->fetch($this->fk_project);

		$this->projet = $project; // deprecated
		$this->project = $project;
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Load the product with id $this->fk_product into this->product
	 *
	 *		@return		int<-1,1>	Return integer <0 if KO, >=0 if OK
	 */
	public function fetch_product()
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		// @phan-suppress-next-line PhanUndeclaredProperty
		if (empty($this->fk_product)) {
			return 0;
		}

		$product = new Product($this->db);
		// @phan-suppress-next-line PhanUndeclaredProperty
		$result = $product->fetch($this->fk_product);

		$this->product = $product;
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *		Load the user with id $userid into this->user
	 *
	 *		@param	int		$userid 		Id du contact
	 *		@return	int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function fetch_user($userid)
	{
		// phpcs:enable
		$user = new User($this->db);
		$result = $user->fetch($userid);
		$this->user = $user;
		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Read linked origin object.
	 *	Set ->origin_object
	 *
	 *	@return		void
	 */
	public function fetch_origin()
	{
		// phpcs:enable
		$origin = $this->origin ? $this->origin : $this->origin_type;

		// Manage classes with non standard name
		if ($origin == 'shipping') {
			$origin = 'expedition';
		}
		if ($origin == 'delivery') {
			$origin = 'livraison';
		}
		if ($origin == 'order_supplier' || $origin == 'supplier_order') {
			$origin = 'commandeFournisseur';
		}

		$classname = ucfirst($origin);
		$this->origin_object = new $classname($this->db);
		// @phan-suppress-next-line PhanPluginUnknownObjectMethodCall
		$this->origin_object->fetch($this->origin_id);
	}

	/**
	 *  Load object from specific field
	 *
	 *  @param	string	$table		Table element or element line
	 *  @param	string	$field		Field selected
	 *  @param	string	$key		Import key
	 *  @param	string	$element	Element name
	 *	@return	int<-1,1>|false		Return -1 or false if KO, >0 if OK
	 */
	public function fetchObjectFrom($table, $field, $key, $element = null)
	{
		global $conf;

		$result = false;

		$sql = "SELECT rowid FROM ".$this->db->prefix().$table;
		$sql .= " WHERE ".$field." = '".$this->db->escape($key)."'";
		if (!empty($element)) {
			$sql .= " AND entity IN (".getEntity($element).")";
		} else {
			$sql .= " AND entity = ".((int) $conf->entity);
		}

		dol_syslog(get_class($this).'::fetchObjectFrom', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			// Test for avoid error -1
			if ($obj) {
				if (method_exists($this, 'fetch')) {
					$result = $this->fetch($obj->rowid);
				} else {
					$this->error = 'fetch() method not implemented on '.get_class($this);
					dol_syslog(get_class($this).'::fetchOneLike Error='.$this->error, LOG_ERR);
					array_push($this->errors, $this->error);
					$result = -1;
				}
			}
		}

		return $result;
	}

	/**
	 *	Getter generic. Load value from a specific field
	 *
	 *	@param	string	$table		Table of element or element line
	 *	@param	int		$id			Element id
	 *	@param	string	$field		Field selected
	 *	@return	int<-1,1>			Return integer <0 if KO, >0 if OK
	 */
	public function getValueFrom($table, $id, $field)
	{
		$result = false;
		if (!empty($id) && !empty($field) && !empty($table)) {
			$sql = "SELECT ".$field." FROM ".$this->db->prefix().$table;
			$sql .= " WHERE rowid = ".((int) $id);

			dol_syslog(get_class($this).'::getValueFrom', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$row = $this->db->fetch_row($resql);
				$result = $row[0];
			}
		}
		return $result;
	}

	/**
	 *	Setter generic. Update a specific field into database.
	 *  Warning: Trigger is run only if param trigkey is provided.
	 *
	 *	@param	string			$field			Field to update
	 *	@param	mixed			$value			New value
	 *	@param	string			$table			To force other table element or element line (should not be used)
	 *	@param	?int			$id				To force other object id (should not be used)
	 *	@param	string			$format			Data format ('text', 'int', 'date'). 'text' is used if not defined
	 *	@param	string			$id_field		To force rowid field name. 'rowid' is used if not defined
	 *	@param	User|string|null	$fuser	Update the user of last update field with this user. If not provided, current user is used except if value is 'none'
	 *  @param  string      $trigkey    	Trigger key to run (in most cases something like 'XXX_MODIFY')
	 *  @param	string		$fk_user_field	Name of field to save user id making change
	 *	@return	int<-2,1>					Return integer <0 if KO, >0 if OK
	 *  @see updateExtraField()
	 */
	public function setValueFrom($field, $value, $table = '', $id = null, $format = '', $id_field = '', $fuser = null, $trigkey = '', $fk_user_field = 'fk_user_modif')
	{
		global $user;

		if (empty($table)) {
			$table = $this->table_element;
		}
		if (empty($id)) {
			$id = $this->id;
		}
		if (empty($format)) {
			$format = 'text';
		}
		if (empty($id_field)) {
			$id_field = 'rowid';
		}

		// Special case
		if ($table == 'product' && $field == 'note_private') {
			$field = 'note';
		}

		if (in_array($table, array('actioncomm', 'adherent', 'advtargetemailing', 'cronjob', 'establishment'))) {
			$fk_user_field = 'fk_user_mod';
		}
		if (in_array($table, array('prelevement_bons'))) {	// TODO Add a field fk_user_modif into llx_prelevement_bons
			$fk_user_field = '';
		}

		$oldvalue = null;
		if ($trigkey) {
			$sql = "SELECT " . $field;
			$sql .= " FROM " . MAIN_DB_PREFIX . $table;
			$sql .= " WHERE " . $id_field . " = " . ((int) $id);

			$resql = $this->db->query($sql);
			if ($resql) {
				if ($obj = $this->db->fetch_object($resql)) {
					if ($format == 'date') {
						$oldvalue = $this->db->jdate($obj->$field);
					} else {
						$oldvalue = $obj->$field;
					}
				}
			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}
		}

		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->db->begin();

		$sql = "UPDATE ".$this->db->prefix().$table." SET ";

		if ($format == 'text') {
			$sql .= $field." = '".$this->db->escape($value)."'";
		} elseif ($format == 'int') {
			$sql .= $field." = ".((int) $value);
		} elseif ($format == 'date') {
			$sql .= $field." = ".($value ? "'".$this->db->idate($value)."'" : "null");
		} elseif ($format == 'dategmt') {
			$sql .= $field." = ".($value ? "'".$this->db->idate($value, 'gmt')."'" : "null");
		}

		if ($fk_user_field) {
			if (!empty($fuser) && is_object($fuser)) {
				$sql .= ", ".$fk_user_field." = ".((int) $fuser->id);
			} elseif (empty($fuser) || $fuser != 'none') {
				$sql .= ", ".$fk_user_field." = ".((int) $user->id);
			}
		}

		$sql .= " WHERE ".$id_field." = ".((int) $id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($trigkey) {
				// call trigger with updated object values
				if (method_exists($this, 'fetch')) {
					$result = $this->fetch($id);
				} else {
					$result = $this->fetchCommon($id);
				}
				$this->oldcopy = clone $this;
				if (property_exists($this->oldcopy, $field)) {
					$this->oldcopy->$field = $oldvalue;
				}

				if ($result >= 0) {
					$result = $this->call_trigger($trigkey, (!empty($fuser) && is_object($fuser)) ? $fuser : $user); // This may set this->errors
				}
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				if (property_exists($this, $field)) {
					$this->$field = $value;
				}
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				$this->error = 'DB_ERROR_RECORD_ALREADY_EXISTS';
			} else {
				$this->error = $this->db->lasterror();
			}
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load properties id_previous and id_next by comparing $fieldid with $this->ref
	 *
	 *      @param	string	$filter		Optional SQL filter. Use SQL or Universal Search Filter.
	 *      							Example: "(t.field1 = 'aa' OR t.field2 = 'bb')". Do not allow user input data here with this syntax.
	 *      							Example: "((t.field1:=:'aa') OR (t.field2:=:'bb'))".
	 *	 	@param  string	$fieldid   	Name of field to use for the select MAX and MIN
	 *		@param	int<0,1>	$nodbprefix	Do not include DB prefix to forge table name
	 *      @return int<-2,1>      		Return integer <0 if KO, >0 if OK
	 */
	public function load_previous_next_ref($filter, $fieldid, $nodbprefix = 0)
	{
		// phpcs:enable
		global $conf, $user;

		if (!$this->table_element) {
			dol_print_error(null, get_class($this)."::load_previous_next_ref was called on object with property table_element not defined");
			return -1;
		}
		if ($fieldid == 'none') {
			return 1;
		}

		// For backward compatibility
		if (in_array($this->table_element, array('facture_rec', 'facture_fourn_rec')) && $fieldid == 'title') {
			$fieldid = 'titre';
		}

		// Security on socid
		$socid = 0;
		if ($user->socid > 0) {
			$socid = $user->socid;
		}

		// this->ismultientitymanaged contains
		// 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
		$aliastablesociete = 's';
		if ($this->element == 'societe') {
			$aliastablesociete = 'te'; // te as table_element
		}
		$restrictiononfksoc = empty($this->restrictiononfksoc) ? 0 : $this->restrictiononfksoc;
		$sql = "SELECT MAX(te.".$fieldid.")";
		$sql .= " FROM ".(empty($nodbprefix) ? $this->db->prefix() : '').$this->table_element." as te";
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
			$tmparray = explode('@', $this->ismultientitymanaged);
			$sql .= ", ".$this->db->prefix().$tmparray[1]." as ".($tmparray[1] == 'societe' ? 's' : 'parenttable'); // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", ".$this->db->prefix()."societe as s"; // If we need to link to societe to limit select to socid
		} elseif ($restrictiononfksoc == 2 && $this->element != 'societe' && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON te.fk_soc = s.rowid"; // If we need to link to societe to limit select to socid
		}
		if ($restrictiononfksoc && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe_commerciaux as sc ON ".$aliastablesociete.".rowid = sc.fk_soc";
		}
		if ($fieldid == 'rowid') {
			$sql .= " WHERE te.".$fieldid." < ".((int) $this->id);
		} else {
			$sql .= " WHERE te.".$fieldid." < '".$this->db->escape($this->ref)."'"; // ->ref must always be defined (set to id if field does not exists)
		}
		if ($restrictiononfksoc == 1 && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND sc.fk_user = ".((int) $user->id);
		}
		if ($restrictiononfksoc == 2 && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND (sc.fk_user = ".((int) $user->id).' OR te.fk_soc IS NULL)';
		}

		$filtermax = $filter;

		// Manage filter
		$errormessage = '';
		$tmpsql = forgeSQLFromUniversalSearchCriteria($filtermax, $errormessage);
		if ($errormessage) {
			if (!preg_match('/^\s*AND/i', $filtermax)) {
				$sql .= " AND ";
			}
			$sql .= $filtermax;
		} else {
			$sql .= $tmpsql;
		}

		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
			$tmparray = explode('@', $this->ismultientitymanaged);
			$sql .= " AND te.".$tmparray[0]." = ".($tmparray[1] == "societe" ? "s" : "parenttable").".rowid"; // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ' AND te.fk_soc = s.rowid'; // If we need to link to societe to limit select to socid
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			if ($this->element == 'user' && getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE')) {
				if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
					$sql .= " AND te.entity IS NOT NULL"; // Show all users
				} else {
					$sql .= " AND te.rowid IN (SELECT ug.fk_user FROM ".$this->db->prefix()."usergroup_user as ug WHERE ug.entity IN (".getEntity('usergroup')."))";
				}
			} else {
				$sql .= ' AND te.entity IN ('.getEntity($this->element).')';
			}
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged) && $this->element != 'societe') {
			$tmparray = explode('@', $this->ismultientitymanaged);
			$sql .= ' AND parenttable.entity IN ('.getEntity($tmparray[1]).')';
		}
		if ($restrictiononfksoc == 1 && $socid && $this->element != 'societe') {
			$sql .= ' AND te.fk_soc = '.((int) $socid);
		}
		if ($restrictiononfksoc == 2 && $socid && $this->element != 'societe') {
			$sql .= ' AND (te.fk_soc = '.((int) $socid).' OR te.fk_soc IS NULL)';
		}
		if ($restrictiononfksoc && $socid && $this->element == 'societe') {
			$sql .= ' AND te.rowid = '.((int) $socid);
		}
		//print 'socid='.$socid.' restrictiononfksoc='.$restrictiononfksoc.' ismultientitymanaged = '.$this->ismultientitymanaged.' filter = '.$filter.' -> '.$sql."<br>";

		$result = $this->db->query($sql);
		if (!$result) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_previous = $row[0];

		$sql = "SELECT MIN(te.".$fieldid.")";
		$sql .= " FROM ".(empty($nodbprefix) ? $this->db->prefix() : '').$this->table_element." as te";
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
			$tmparray = explode('@', $this->ismultientitymanaged);
			$sql .= ", ".$this->db->prefix().$tmparray[1]." as ".($tmparray[1] == 'societe' ? 's' : 'parenttable'); // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ", ".$this->db->prefix()."societe as s"; // If we need to link to societe to limit select to socid
		} elseif ($restrictiononfksoc == 2 && $this->element != 'societe' && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe as s ON te.fk_soc = s.rowid"; // If we need to link to societe to limit select to socid
		}
		if ($restrictiononfksoc && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " LEFT JOIN ".$this->db->prefix()."societe_commerciaux as sc ON ".$aliastablesociete.".rowid = sc.fk_soc";
		}
		if ($fieldid == 'rowid') {
			$sql .= " WHERE te.".$fieldid." > ".((int) $this->id);
		} else {
			$sql .= " WHERE te.".$fieldid." > '".$this->db->escape($this->ref)."'"; // ->ref must always be defined (set to id if field does not exists)
		}
		if ($restrictiononfksoc == 1 && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND (sc.fk_user = ".((int) $user->id);
			if (getDolGlobalInt('MAIN_SEE_SUBORDINATES')) {
				$userschilds = $user->getAllChildIds();
				$sql .= " OR sc.fk_user IN (".$this->db->sanitize(implode(',', $userschilds)).")";
			}
			$sql .= ')';
		}
		if ($restrictiononfksoc == 2 && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " AND (sc.fk_user = ".((int) $user->id).' OR te.fk_soc IS NULL)';
		}

		$filtermin = $filter;

		// Manage filter
		$errormessage = '';
		$tmpsql = forgeSQLFromUniversalSearchCriteria($filtermin, $errormessage);
		if ($errormessage) {
			if (!preg_match('/^\s*AND/i', $filtermin)) {
				$sql .= " AND ";
			}
			$sql .= $filtermin;

			$filtermin = '';
		} else {
			$sql .= $tmpsql;
		}

		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged)) {
			$tmparray = explode('@', $this->ismultientitymanaged);
			$sql .= " AND te.".$tmparray[0]." = ".($tmparray[1] == "societe" ? "s" : "parenttable").".rowid"; // If we need to link to this table to limit select to entity
		} elseif ($restrictiononfksoc == 1 && $this->element != 'societe' && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= ' AND te.fk_soc = s.rowid'; // If we need to link to societe to limit select to socid
		}
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			if ($this->element == 'user' && getDolGlobalInt('MULTICOMPANY_TRANSVERSE_MODE')) {
				if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
					$sql .= " AND te.entity IS NOT NULL"; // Show all users
				} else {
					$sql .= " AND te.rowid IN (SELECT ug.fk_user FROM ".$this->db->prefix()."usergroup_user as ug WHERE ug.entity IN (".getEntity('usergroup')."))";
				}
			} else {
				$sql .= ' AND te.entity IN ('.getEntity($this->element).')';
			}
		}
		if (isset($this->ismultientitymanaged) && !is_numeric($this->ismultientitymanaged) && $this->element != 'societe') {
			$tmparray = explode('@', $this->ismultientitymanaged);
			$sql .= ' AND parenttable.entity IN ('.getEntity($tmparray[1]).')';
		}
		if ($restrictiononfksoc == 1 && $socid && $this->element != 'societe') {
			$sql .= ' AND te.fk_soc = '.((int) $socid);
		}
		if ($restrictiononfksoc == 2 && $socid && $this->element != 'societe') {
			$sql .= ' AND (te.fk_soc = '.((int) $socid).' OR te.fk_soc IS NULL)';
		}
		if ($restrictiononfksoc && $socid && $this->element == 'societe') {
			$sql .= ' AND te.rowid = '.((int) $socid);
		}
		//print 'socid='.$socid.' restrictiononfksoc='.$restrictiononfksoc.' ismultientitymanaged = '.$this->ismultientitymanaged.' filter = '.$filter.' -> '.$sql."<br>";
		// Rem: Bug in some mysql version: SELECT MIN(rowid) FROM llx_socpeople WHERE rowid > 1 when one row in database with rowid=1, returns 1 instead of null

		$result = $this->db->query($sql);
		if (!$result) {
			$this->error = $this->db->lasterror();
			return -2;
		}
		$row = $this->db->fetch_row($result);
		$this->ref_next = $row[0];

		return 1;
	}


	/**
	 *      Return list of id of contacts of object
	 *
	 *      @param	string	$source     Source of contact: external (llx_socpeople) or internal (llx_user) or thirdparty (llx_societe)
	 *      @return int[]				Array of id of contacts (if source=external or internal)
	 * 									Array of id of third parties with at least one contact on object (if source=thirdparty)
	 */
	public function getListContactId($source = 'external')
	{
		$contactAlreadySelected = array();
		$tab = $this->liste_contact(-1, $source);
		$num = count($tab);
		$i = 0;
		while ($i < $num) {
			if ($source == 'thirdparty') {
				$contactAlreadySelected[$i] = $tab[$i]['socid'];
			} else {
				$contactAlreadySelected[$i] = $tab[$i]['id'];
			}
			$i++;
		}
		return $contactAlreadySelected;
	}


	/**
	 *	Link element with a project
	 *
	 *	@param     	int			$projectid	Project id to link element to
	 *  @param		int<0,1>	$notrigger	Disable the trigger
	 *	@return		int<-1,1>				Return integer <0 if KO, >0 if OK
	 */
	public function setProject($projectid, $notrigger = 0)
	{
		global $user;
		$error = 0;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setProject was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		// @phan-suppress-next-line PhanTypeMismatchProperty
		if (!empty($this->fields['fk_project'])) {		// Common case
			if ($projectid) {
				$sql .= " SET fk_project = ".((int) $projectid);
			} else {
				$sql .= " SET fk_project = NULL";
			}
			$sql .= ' WHERE rowid = '.((int) $this->id);
		} elseif ($this->table_element == 'actioncomm') {	// Special case for actioncomm
			if ($projectid) {
				$sql .= " SET fk_project = ".((int) $projectid);
			} else {
				$sql .= " SET fk_project = NULL";
			}
			$sql .= ' WHERE id = '.((int) $this->id);
		} else { // Special case for old architecture objects
			if ($projectid) {
				$sql .= ' SET fk_projet = '.((int) $projectid);
			} else {
				$sql .= ' SET fk_projet = NULL';
			}
			$sql .= " WHERE rowid = ".((int) $this->id);
		}

		$this->db->begin();

		dol_syslog(get_class($this)."::setProject", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->fk_project = ((int) $projectid);
		} else {
			dol_print_error($this->db);
			$error++;
		}

		// Triggers
		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger(strtoupper($this->element) . '_MODIFY', $user);
			if ($result < 0) {
				$error++;
			} //Do also here what you must do to rollback action if trigger fail
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Change the payments methods
	 *
	 *  @param		int		$id		Id of new payment method
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setPaymentMethods($id)
	{
		global $user;

		$error = 0;
		$notrigger = 0;

		dol_syslog(get_class($this).'::setPaymentMethods('.$id.')');

		if ($this->status >= 0 || $this->element == 'societe') {
			// TODO uniformize field name
			$fieldname = 'fk_mode_reglement';
			if ($this->element == 'societe') {
				$fieldname = 'mode_reglement';
			}
			if (get_class($this) == 'Fournisseur') {
				$fieldname = 'mode_reglement_supplier';
			}
			if (get_class($this) == 'Tva') {
				$fieldname = 'fk_typepayment';
			}
			if (get_class($this) == 'Salary') {
				$fieldname = 'fk_typepayment';
			}

			$sql = "UPDATE ".$this->db->prefix().$this->table_element;
			$sql .= " SET ".$fieldname." = ".(($id > 0 || $id == '0') ? ((int) $id) : 'NULL');
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->mode_reglement_id = $id;
				// for supplier
				if (get_class($this) == 'Fournisseur') {
					$this->mode_reglement_supplier_id = $id;
				}
				// Triggers
				if (!$error && !$notrigger) {
					// Call triggers
					if (get_class($this) == 'Commande') {
						$result = $this->call_trigger('ORDER_MODIFY', $user);
					} else {
						$result = $this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $user);
					}
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
				return 1;
			} else {
				dol_syslog(get_class($this).'::setPaymentMethods Error '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setPaymentMethods, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}

	/**
	 *  Change the multicurrency code
	 *
	 *  @param		string	$code	multicurrency code
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setMulticurrencyCode($code)
	{
		dol_syslog(get_class($this).'::setMulticurrencyCode('.$code.')');
		if ($this->status >= 0 || $this->element == 'societe') {
			$fieldname = 'multicurrency_code';

			$sql = 'UPDATE '.$this->db->prefix().$this->table_element;
			$sql .= " SET ".$fieldname." = '".$this->db->escape($code)."'";
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->multicurrency_code = $code;

				list($fk_multicurrency, $rate) = MultiCurrency::getIdAndTxFromCode($this->db, $code);
				if ($rate) {
					$this->setMulticurrencyRate($rate, 2);
				}

				return 1;
			} else {
				dol_syslog(get_class($this).'::setMulticurrencyCode Error '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setMulticurrencyCode, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}

	/**
	 *  Change the multicurrency rate
	 *
	 *  @param		double	$rate	multicurrency rate
	 *  @param		int		$mode	mode 1 : amounts in company currency will be recalculated, mode 2 : amounts in foreign currency will be recalculated
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setMulticurrencyRate($rate, $mode = 1)
	{
		dol_syslog(get_class($this).'::setMulticurrencyRate('.$rate.', '.$mode.')');
		if ($this->status >= 0 || $this->element == 'societe') {
			$fieldname = 'multicurrency_tx';

			$sql = 'UPDATE '.$this->db->prefix().$this->table_element;
			$sql .= " SET ".$fieldname." = ".((float) $rate);
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->multicurrency_tx = $rate;

				// Update line price
				if (!empty($this->lines)) {
					foreach ($this->lines as &$line) {
						// Amounts in company currency will be recalculated
						if ($mode == 1) {
							$line->subprice = 0;
						}

						// Amounts in foreign currency will be recalculated
						if ($mode == 2) {
							$line->multicurrency_subprice = 0;
						}

						switch ($this->element) {
							case 'propal':
								/** @var Propal $this */
								/** @var PropaleLigne $line */
								$this->updateline(
									$line->id,
									$line->subprice,
									$line->qty,
									$line->remise_percent,
									$line->tva_tx,
									$line->localtax1_tx,
									$line->localtax2_tx,
									($line->description ? $line->description : $line->desc),
									'HT',
									$line->info_bits,
									$line->special_code,
									$line->fk_parent_line,
									$line->skip_update_total,
									$line->fk_fournprice,
									$line->pa_ht,
									$line->label,
									$line->product_type,
									$line->date_start,
									$line->date_end,
									$line->array_options,
									$line->fk_unit,
									$line->multicurrency_subprice
								);
								break;
							case 'commande':
								/** @var Commande $this */
								/** @var OrderLine $line */
								$this->updateline(
									$line->id,
									($line->description ? $line->description : $line->desc),
									$line->subprice,
									$line->qty,
									$line->remise_percent,
									$line->tva_tx,
									$line->localtax1_tx,
									$line->localtax2_tx,
									'HT',
									$line->info_bits,
									$line->date_start,
									$line->date_end,
									$line->product_type,
									$line->fk_parent_line,
									$line->skip_update_total,
									$line->fk_fournprice,
									$line->pa_ht,
									$line->label,
									$line->special_code,
									$line->array_options,
									$line->fk_unit,
									$line->multicurrency_subprice
								);
								break;
							case 'facture':
								/** @var Facture $this */
								/** @var FactureLigne $line */
								$this->updateline(
									$line->id,
									($line->description ? $line->description : $line->desc),
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
									$line->skip_update_total,
									$line->fk_fournprice,
									$line->pa_ht,
									$line->label,
									$line->special_code,
									$line->array_options,
									$line->situation_percent,
									$line->fk_unit,
									$line->multicurrency_subprice
								);
								break;
							case 'supplier_proposal':
								/** @var SupplierProposal $this */
								/** @var SupplierProposalLine $line */
								$this->updateline(
									$line->id,
									$line->subprice,
									$line->qty,
									$line->remise_percent,
									$line->tva_tx,
									$line->localtax1_tx,
									$line->localtax2_tx,
									($line->description ? $line->description : $line->desc),
									'HT',
									$line->info_bits,
									$line->special_code,
									$line->fk_parent_line,
									$line->skip_update_total,
									$line->fk_fournprice,
									$line->pa_ht,
									$line->label,
									$line->product_type,
									$line->array_options,
									$line->ref_fourn,
									$line->multicurrency_subprice
								);
								break;
							case 'order_supplier':
								/** @var CommandeFournisseur $this */
								/** @var CommandeFournisseurLigne $line */
								$this->updateline(
									$line->id,
									($line->description ? $line->description : $line->desc),
									$line->subprice,
									$line->qty,
									$line->remise_percent,
									$line->tva_tx,
									$line->localtax1_tx,
									$line->localtax2_tx,
									'HT',
									$line->info_bits,
									$line->product_type,
									0,
									$line->date_start,
									$line->date_end,
									$line->array_options,
									$line->fk_unit,
									$line->multicurrency_subprice,
									$line->ref_supplier
								);
								break;
							case 'invoice_supplier':
								/** @var FactureFournisseur $this */
								/** @var SupplierInvoiceLine $line */
								$this->updateline(
									$line->id,
									($line->description ? $line->description : $line->desc),
									$line->subprice,
									$line->tva_tx,
									$line->localtax1_tx,
									$line->localtax2_tx,
									$line->qty,
									0,
									'HT',
									$line->info_bits,
									$line->product_type,
									$line->remise_percent,
									0,
									$line->date_start,
									$line->date_end,
									$line->array_options,
									$line->fk_unit,
									$line->multicurrency_subprice,
									$line->ref_supplier
								);
								break;
							default:
								dol_syslog(get_class($this).'::setMulticurrencyRate no updateline defined', LOG_DEBUG);
								break;
						}
					}
				}

				return 1;
			} else {
				dol_syslog(get_class($this).'::setMulticurrencyRate Error '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setMulticurrencyRate, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}

	/**
	 *  Change the payments terms
	 *
	 *  @param		int		$id					Id of new payment terms
	 *  @param		float	$deposit_percent	% of deposit if needed by payment terms
	 *  @return		int							>0 if OK, <0 if KO
	 */
	public function setPaymentTerms($id, $deposit_percent = null)
	{
		dol_syslog(get_class($this).'::setPaymentTerms('.$id.', '.var_export($deposit_percent, true).')');
		if ($this->status >= 0 || $this->element == 'societe') {
			// TODO uniformize field name
			$fieldname = 'fk_cond_reglement';
			if ($this->element == 'societe') {
				$fieldname = 'cond_reglement';
			}
			if (get_class($this) == 'Fournisseur') {
				$fieldname = 'cond_reglement_supplier';
			}

			if (empty($deposit_percent) || $deposit_percent < 0) {
				$deposit_percent = (float) getDictionaryValue('c_payment_term', 'deposit_percent', $id);
			}

			if ($deposit_percent > 100) {
				$deposit_percent = 100;
			}

			$sql = 'UPDATE '.$this->db->prefix().$this->table_element;
			$sql .= " SET ".$fieldname." = ".(($id > 0 || $id == '0') ? ((int) $id) : 'NULL');
			if (in_array($this->table_element, array('propal', 'commande', 'societe'))) {
				$sql .= " , deposit_percent = " . (empty($deposit_percent) ? 'NULL' : "'".$this->db->escape($deposit_percent)."'");
			}
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->cond_reglement_id = $id;
				// for supplier
				if (get_class($this) == 'Fournisseur') {
					$this->cond_reglement_supplier_id = $id;
				}
				$this->cond_reglement = $id; // for compatibility
				$this->deposit_percent = $deposit_percent;
				return 1;
			} else {
				dol_syslog(get_class($this).'::setPaymentTerms Error '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setPaymentTerms, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}

	/**
	 *  Change the transport mode methods
	 *
	 *  @param		int		$id		Id of transport mode
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setTransportMode($id)
	{
		dol_syslog(get_class($this).'::setTransportMode('.$id.')');
		if ($this->status >= 0 || $this->element == 'societe') {
			$fieldname = 'fk_transport_mode';
			if ($this->element == 'societe') {
				$fieldname = 'transport_mode';
			}
			if (get_class($this) == 'Fournisseur') {
				$fieldname = 'transport_mode_supplier';
			}

			$sql = 'UPDATE '.$this->db->prefix().$this->table_element;
			$sql .= " SET ".$fieldname." = ".(($id > 0 || $id == '0') ? ((int) $id) : 'NULL');
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->transport_mode_id = $id;
				// for supplier
				if (get_class($this) == 'Fournisseur') {
					$this->transport_mode_supplier_id = $id;
				}
				return 1;
			} else {
				dol_syslog(get_class($this).'::setTransportMode Error '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setTransportMode, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}

	/**
	 *  Change the retained warranty payments terms
	 *
	 *  @param		int		$id		Id of new payment terms
	 *  @return		int				>0 if OK, <0 if KO
	 */
	public function setRetainedWarrantyPaymentTerms($id)
	{
		dol_syslog(get_class($this).'::setRetainedWarrantyPaymentTerms('.$id.')');
		if ($this->status >= 0 || $this->element == 'societe') {
			$fieldname = 'retained_warranty_fk_cond_reglement';

			$sql = 'UPDATE '.$this->db->prefix().$this->table_element;
			$sql .= " SET ".$fieldname." = ".((int) $id);
			$sql .= ' WHERE rowid='.((int) $this->id);

			if ($this->db->query($sql)) {
				$this->retained_warranty_fk_cond_reglement = $id;
				return 1;
			} else {
				dol_syslog(get_class($this).'::setRetainedWarrantyPaymentTerms Error '.$sql.' - '.$this->db->error());
				$this->error = $this->db->error();
				return -1;
			}
		} else {
			dol_syslog(get_class($this).'::setRetainedWarrantyPaymentTerms, status of the object is incompatible');
			$this->error = 'Status of the object is incompatible '.$this->status;
			return -2;
		}
	}

	/**
	 *	Define delivery address
	 *  @deprecated
	 *
	 *	@param      int		$id		Address id
	 *	@return     int				Return integer <0 si ko, >0 si ok
	 */
	public function setDeliveryAddress($id)
	{
		$fieldname = 'fk_delivery_address';
		if ($this->element == 'delivery' || $this->element == 'shipping') {
			$fieldname = 'fk_address';
		}

		$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET ".$fieldname." = ".((int) $id);
		$sql .= " WHERE rowid = ".((int) $this->id)." AND fk_statut = 0";

		if ($this->db->query($sql)) {
			$this->fk_delivery_address = $id;
			return 1;
		} else {
			$this->error = $this->db->error();
			dol_syslog(get_class($this).'::setDeliveryAddress Error '.$this->error);
			return -1;
		}
	}


	/**
	 *  Change the shipping method
	 *
	 *  @param      int     $shipping_method_id     Id of shipping method
	 *  @param      int    	$notrigger              0=launch triggers after, 1=disable triggers
	 *  @param      User	$userused               Object user
	 *  @return     int              				1 if OK, 0 if KO
	 */
	public function setShippingMethod($shipping_method_id, $notrigger = 0, $userused = null)
	{
		global $user;

		if (empty($userused)) {
			$userused = $user;
		}

		$error = 0;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setShippingMethod was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$this->db->begin();

		if ($shipping_method_id < 0) {
			$shipping_method_id = 'NULL';
		}
		dol_syslog(get_class($this).'::setShippingMethod('.$shipping_method_id.')');

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET fk_shipping_method = ".((int) $shipping_method_id);
		$sql .= " WHERE rowid=".((int) $this->id);
		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this).'::setShippingMethod Error ', LOG_DEBUG);
			$this->error = $this->db->lasterror();
			$error++;
		} else {
			if (!$notrigger) {
				// Call trigger
				$this->context = array('shippingmethodupdate' => 1);
				$result = $this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $userused);
				if ($result < 0) {
					$error++;
				}
				// End call trigger
			}
		}
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->shipping_method_id = ($shipping_method_id == 'NULL') ? null : $shipping_method_id;
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Change the warehouse
	 *
	 *  @param      int     $warehouse_id     Id of warehouse
	 *  @return     int              1 if OK, 0 if KO
	 */
	public function setWarehouse($warehouse_id)
	{
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setWarehouse was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}
		if ($warehouse_id < 0) {
			$warehouse_id = 'NULL';
		}
		dol_syslog(get_class($this).'::setWarehouse('.$warehouse_id.')');

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET fk_warehouse = ".((int) $warehouse_id);
		$sql .= " WHERE rowid=".((int) $this->id);

		if ($this->db->query($sql)) {
			$this->warehouse_id = ($warehouse_id == 'NULL') ? null : $warehouse_id;
			return 1;
		} else {
			dol_syslog(get_class($this).'::setWarehouse Error ', LOG_DEBUG);
			$this->error = $this->db->error();
			return 0;
		}
	}


	/**
	 *		Set last model used by doc generator
	 *
	 *		@param		User	$user		User object that make change
	 *		@param		string	$modelpdf	Modele name
	 *		@return		int					Return integer <0 if KO, >0 if OK
	 */
	public function setDocModel($user, $modelpdf)
	{
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setDocModel was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$newmodelpdf = dol_trunc($modelpdf, 255);

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET model_pdf = '".$this->db->escape($newmodelpdf)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setDocModel", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->model_pdf = $modelpdf;
			return 1;
		} else {
			dol_print_error($this->db);
			return 0;
		}
	}


	/**
	 *  Change the bank account
	 *
	 *  @param		int		$fk_account		Id of bank account
	 *  @param      int    	$notrigger      0=launch triggers after, 1=disable triggers
	 *  @param      User	$userused		Object user
	 *  @return		int						1 if OK, 0 if KO
	 */
	public function setBankAccount($fk_account, $notrigger = 0, $userused = null)
	{
		global $user;

		if (empty($userused)) {
			$userused = $user;
		}

		$error = 0;

		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setBankAccount was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}
		$this->db->begin();

		if ($fk_account < 0) {
			$fk_account = 'NULL';
		}
		dol_syslog(get_class($this).'::setBankAccount('.$fk_account.')');

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET fk_account = ".((int) $fk_account);
		$sql .= " WHERE rowid=".((int) $this->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this).'::setBankAccount Error '.$sql.' - '.$this->db->error());
			$this->error = $this->db->lasterror();
			$error++;
		} else {
			if (!$notrigger) {
				// Call trigger
				$this->context['bankaccountupdate'] = 1;
				$triggerName = strtoupper(get_class($this)).'_MODIFY';
				// Special cases
				if ($triggerName == 'FACTUREREC_MODIFY') {
					$triggerName = 'BILLREC_MODIFY';
				}
				$result = $this->call_trigger($triggerName, $userused);
				if ($result < 0) {
					$error++;
				}
				// End call trigger
			}
		}
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->fk_account = ($fk_account == 'NULL') ? null : $fk_account;
			$this->db->commit();
			return 1;
		}
	}


	// TODO: Move line related operations to CommonObjectLine?

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Save a new position (field rang) for details lines.
	 *  You can choose to set position for lines with already a position or lines without any position defined.
	 *
	 * 	@param		boolean		$renum			   True to renum all already ordered lines, false to renum only not already ordered lines.
	 * 	@param		string		$rowidorder		   ASC or DESC
	 * 	@param		boolean		$fk_parent_line    Table with fk_parent_line field or not
	 * 	@return		int                            Return integer <0 if KO, >0 if OK
	 */
	public function line_order($renum = false, $rowidorder = 'ASC', $fk_parent_line = true)
	{
		// phpcs:enable
		if (!$this->table_element_line) {
			dol_syslog(get_class($this)."::line_order was called on object with property table_element_line not defined", LOG_ERR);
			return -1;
		}
		if (!$this->fk_element) {
			dol_syslog(get_class($this)."::line_order was called on object with property fk_element not defined", LOG_ERR);
			return -1;
		}

		$fieldposition = 'rang'; // @todo Rename 'rang' into 'position'
		if (in_array($this->table_element_line, array('bom_bomline', 'ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
			$fieldposition = 'position';
		}

		// Count number of lines to reorder (according to choice $renum)
		$nl = 0;
		$sql = "SELECT count(rowid) FROM ".$this->db->prefix().$this->table_element_line;
		$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
		if (!$renum) {
			$sql .= " AND " . $fieldposition . " = 0";
		}
		if ($renum) {
			$sql .= " AND " . $fieldposition . " <> 0";
		}

		dol_syslog(get_class($this)."::line_order", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			$nl = $row[0];
		} else {
			dol_print_error($this->db);
		}
		if ($nl > 0) {
			// The goal of this part is to reorder all lines, with all children lines sharing the same counter that parents.
			$rows = array();

			// We first search all lines that are parent lines (for multilevel details lines)
			$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element_line;
			$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
			if ($fk_parent_line) {
				$sql .= ' AND fk_parent_line IS NULL';
			}
			$sql .= " ORDER BY " . $fieldposition . " ASC, rowid " . $rowidorder;

			dol_syslog(get_class($this)."::line_order search all parent lines", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$i = 0;
				$num = $this->db->num_rows($resql);
				while ($i < $num) {
					$row = $this->db->fetch_row($resql);
					$rows[] = $row[0]; // Add parent line into array rows
					$children = $this->getChildrenOfLine($row[0]);
					if (!empty($children)) {
						foreach ($children as $child) {
							array_push($rows, $child);
						}
					}
					$i++;
				}

				// Now we set a new number for each lines (parent and children with children included into parent tree)
				if (!empty($rows)) {
					foreach ($rows as $key => $row) {
						$this->updateRangOfLine($row, ($key + 1));
					}
				}
			} else {
				dol_print_error($this->db);
			}
		}
		return 1;
	}

	/**
	 * 	Get children of line
	 *
	 * 	@param	int			$id				Id of parent line
	 * 	@param	int<0,1>	$includealltree	0 = 1st level child, 1 = All level child
	 * 	@return	int[]						Array with list of children lines id
	 */
	public function getChildrenOfLine($id, $includealltree = 0)
	{
		$fieldposition = 'rang'; // @todo Rename 'rang' into 'position'
		if (in_array($this->table_element_line, array('bom_bomline', 'ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
			$fieldposition = 'position';
		}

		$rows = array();

		$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element_line;
		$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
		$sql .= ' AND fk_parent_line = '.((int) $id);
		$sql .= " ORDER BY " . $fieldposition . " ASC";

		dol_syslog(get_class($this)."::getChildrenOfLine search children lines for line ".$id, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql) > 0) {
				while ($row = $this->db->fetch_row($resql)) {
					$rows[] = $row[0];
					if (!empty($includealltree) && $includealltree <= 1000) {	// Test <= 1000 is a protection in depth of recursive call to avoid infinite loop
						$rows = array_merge($rows, $this->getChildrenOfLine($row[0], $includealltree + 1));
					}
				}
			}
		}
		return $rows;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Update a line to have a lower rank
	 *
	 * 	@param 	int			$rowid				Id of line
	 * 	@param	boolean		$fk_parent_line		Table with fk_parent_line field or not
	 * 	@return	void
	 */
	public function line_up($rowid, $fk_parent_line = true)
	{
		// phpcs:enable
		$this->line_order(false, 'ASC', $fk_parent_line);

		// Get rang of line
		$rang = $this->getRangOfLine($rowid);

		// Update position of line
		$this->updateLineUp($rowid, $rang);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Update a line to have a higher rank
	 *
	 * 	@param	int			$rowid				Id of line
	 * 	@param	boolean		$fk_parent_line		Table with fk_parent_line field or not
	 * 	@return	void
	 */
	public function line_down($rowid, $fk_parent_line = true)
	{
		// phpcs:enable
		$this->line_order(false, 'ASC', $fk_parent_line);

		// Get rang of line
		$rang = $this->getRangOfLine($rowid);

		// Get max value for rang
		$max = $this->line_max();

		// Update position of line
		$this->updateLineDown($rowid, $rang, $max);
	}

	/**
	 * 	Update position of line (rang)
	 *
	 * 	@param	int		$rowid		Id of line
	 * 	@param	int		$rang		Position
	 * 	@return	int<-1,1>			Return integer <0 if KO, >0 if OK
	 */
	public function updateRangOfLine($rowid, $rang)
	{
		global $hookmanager;
		$fieldposition = 'rang'; // @todo Rename 'rang' into 'position'
		if (in_array($this->table_element_line, array('bom_bomline', 'ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
			$fieldposition = 'position';
		}

		$sql = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldposition." = ".((int) $rang);
		$sql .= ' WHERE rowid = '.((int) $rowid);

		dol_syslog(get_class($this)."::updateRangOfLine", LOG_DEBUG);
		if (!$this->db->query($sql)) {
			dol_print_error($this->db);
			return -1;
		} else {
			$parameters = array('rowid' => $rowid, 'rang' => $rang, 'fieldposition' => $fieldposition);
			$action = '';
			$reshook = $hookmanager->executeHooks('afterRankOfLineUpdate', $parameters, $this, $action);
			return 1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Update position of line with ajax (rang)
	 *
	 * 	@param	int[]	$rows	Array of rows
	 * 	@return	void
	 */
	public function line_ajaxorder($rows)
	{
		// phpcs:enable
		$num = count($rows);
		for ($i = 0; $i < $num; $i++) {
			$this->updateRangOfLine($rows[$i], ($i + 1));
		}
	}

	/**
	 * 	Update position of line up (rang)
	 *
	 * 	@param	int		$rowid		Id of line
	 * 	@param	int		$rang		Position
	 * 	@return	void
	 */
	public function updateLineUp($rowid, $rang)
	{
		if ($rang > 1) {
			$fieldposition = 'rang';
			if (in_array($this->table_element_line, array('ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
				$fieldposition = 'position';
			}

			$sql = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldposition." = ".((int) $rang);
			$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
			$sql .= " AND " . $fieldposition . " = " . ((int) ($rang - 1));
			if ($this->db->query($sql)) {
				$sql = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldposition." = ".((int) ($rang - 1));
				$sql .= ' WHERE rowid = '.((int) $rowid);
				if (!$this->db->query($sql)) {
					dol_print_error($this->db);
				}
			} else {
				dol_print_error($this->db);
			}
		}
	}

	/**
	 * 	Update position of line down (rang)
	 *
	 * 	@param	int		$rowid		Id of line
	 * 	@param	int		$rang		Position
	 * 	@param	int		$max		Max
	 * 	@return	void
	 */
	public function updateLineDown($rowid, $rang, $max)
	{
		if ($rang < $max) {
			$fieldposition = 'rang';
			if (in_array($this->table_element_line, array('ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
				$fieldposition = 'position';
			}

			$sql = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldposition." = ".((int) $rang);
			$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
			$sql .= " AND " . $fieldposition . " = " . ((int) ($rang + 1));
			if ($this->db->query($sql)) {
				$sql = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldposition." = ".((int) ($rang + 1));
				$sql .= ' WHERE rowid = '.((int) $rowid);
				if (!$this->db->query($sql)) {
					dol_print_error($this->db);
				}
			} else {
				dol_print_error($this->db);
			}
		}
	}

	/**
	 * 	Get position of line (rang)
	 *
	 * 	@param		int		$rowid		Id of line
	 *  @return		int     			Value of rang in table of lines
	 */
	public function getRangOfLine($rowid)
	{
		$fieldposition = 'rang';
		if (in_array($this->table_element_line, array('ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
			$fieldposition = 'position';
		}

		$sql = "SELECT " . $fieldposition . " FROM ".$this->db->prefix().$this->table_element_line;
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog(get_class($this)."::getRangOfLine", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return $row[0];
		}

		return 0;
	}

	/**
	 * 	Get rowid of the line relative to its position
	 *
	 * 	@param		int		$rang		Rang value
	 *  @return     int     			Rowid of the line
	 */
	public function getIdOfLine($rang)
	{
		$fieldposition = 'rang';
		if (in_array($this->table_element_line, array('ecm_files', 'emailcollector_emailcollectoraction', 'product_attribute_value'))) {
			$fieldposition = 'position';
		}

		$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element_line;
		$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
		$sql .= " AND " . $fieldposition . " = ".((int) $rang);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return $row[0];
		}

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Get max value used for position of line (rang)
	 *
	 * 	@param		int		$fk_parent_line		Parent line id
	 *  @return     int  			   			Max value of rang in table of lines
	 */
	public function line_max($fk_parent_line = 0)
	{
		// phpcs:enable
		$positionfield = 'rang';
		if (in_array($this->table_element, array('bom_bom', 'product_attribute'))) {
			$positionfield = 'position';
		}

		// Search the last rang with fk_parent_line
		if ($fk_parent_line) {
			$sql = "SELECT max(".$positionfield.") FROM ".$this->db->prefix().$this->table_element_line;
			$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
			$sql .= " AND fk_parent_line = ".((int) $fk_parent_line);

			dol_syslog(get_class($this)."::line_max", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$row = $this->db->fetch_row($resql);
				if (!empty($row[0])) {
					return $row[0];
				} else {
					return $this->getRangOfLine($fk_parent_line);
				}
			}
		} else {
			// If not, search the last rang of element
			$sql = "SELECT max(".$positionfield.") FROM ".$this->db->prefix().$this->table_element_line;
			$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);

			dol_syslog(get_class($this)."::line_max", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$row = $this->db->fetch_row($resql);
				return $row[0];
			}
		}

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update external ref of element
	 *
	 *  @param      string		$ref_ext	Update field ref_ext
	 *  @return     int      		   		Return integer <0 if KO, >0 if OK
	 */
	public function update_ref_ext($ref_ext)
	{
		// phpcs:enable
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::update_ref_ext was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET ref_ext = '".$this->db->escape($ref_ext)."'";
		$sql .= " WHERE ".(isset($this->table_rowid) ? $this->table_rowid : 'rowid')." = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_ref_ext", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->ref_ext = $ref_ext;
			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update note of element
	 *
	 *  @param      string		$note		New value for note
	 *  @param		string		$suffix		'', '_public' or '_private'
	 *  @param      int         $notrigger  1=Does not execute triggers, 0=execute triggers
	 *  @return     int      		   		Return integer <0 if KO, >0 if OK
	 */
	public function update_note($note, $suffix = '', $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		if (!$this->table_element) {
			$this->error = 'update_note was called on object with property table_element not defined';
			dol_syslog(get_class($this)."::update_note was called on object with property table_element not defined", LOG_ERR);
			return -1;
		}
		if (!in_array($suffix, array('', '_public', '_private'))) {
			$this->error = 'update_note Parameter suffix must be empty, \'_private\' or \'_public\'';
			dol_syslog(get_class($this)."::update_note Parameter suffix must be empty, '_private' or '_public'", LOG_ERR);
			return -2;
		}

		$newsuffix = $suffix;

		// Special case
		if ($this->table_element == 'product' && $newsuffix == '_private') {
			$newsuffix = '';
		}
		if (in_array($this->table_element, array('actioncomm', 'adherent', 'advtargetemailing', 'cronjob', 'establishment'))) {
			$fieldusermod =  "fk_user_mod";
		} elseif ($this->table_element == 'ecm_files') {
			$fieldusermod = "fk_user_m";
		} else {
			$fieldusermod = "fk_user_modif";
		}
		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET note".$newsuffix." = ".(!empty($note) ? ("'".$this->db->escape($note)."'") : "NULL");
		$sql .= ", ".$fieldusermod." = ".((int) $user->id);
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_note", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if ($suffix == '_public') {
				$this->note_public = $note;
			} elseif ($suffix == '_private') {
				$this->note_private = $note;
			} else {
				$this->note = $note; // deprecated
				$this->note_private = $note;
			}
			if (empty($notrigger)) {
				switch ($this->element) {
					case 'societe':
						$trigger_name = 'COMPANY_MODIFY';
						break;
					case 'commande':
						$trigger_name = 'ORDER_MODIFY';
						break;
					case 'facture':
						$trigger_name = 'BILL_MODIFY';
						break;
					case 'invoice_supplier':
						$trigger_name = 'BILL_SUPPLIER_MODIFY';
						break;
					case 'facturerec':
						$trigger_name = 'BILLREC_MODIFIY';
						break;
					case 'expensereport':
						$trigger_name = 'EXPENSE_REPORT_MODIFY';
						break;
					default:
						$trigger_name = strtoupper($this->element) . '_MODIFY';
				}
				$ret = $this->call_trigger($trigger_name, $user);
				if ($ret < 0) {
					return -1;
				}
			}
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Update public note (kept for backward compatibility)
	 *
	 * @param      string		$note		New value for note
	 * @return     int      		   		Return integer <0 if KO, >0 if OK
	 * @deprecated
	 * @see update_note()
	 */
	public function update_note_public($note)
	{
		// phpcs:enable
		return $this->update_note($note, '_public');
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Update total_ht, total_ttc, total_vat, total_localtax1, total_localtax2 for an object (sum of lines).
	 *  Must be called at end of methods addline or updateline.
	 *
	 *	@param	int		$exclspec          	>0 = Exclude special product (product_type=9)
	 *  @param  'none'|'auto'|'0'|'1'	$roundingadjust		'none'=Do nothing, 'auto'=Use default method (MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND if defined, or '0'), '0'=Force mode Total of rounding, '1'=Force mode Rounding of total
	 *  @param	int<0,1>	$nodatabaseupdate	1=Do not update database total fields of the main object. Update only properties in memory. Can be used to save SQL when this method is called several times, so we can do it only once at end.
	 *  @param	?Societe	$seller				If roundingadjust is '0' or '1' or maybe 'auto', it means we recalculate total for lines before calculating total for object and for this, we need seller object (used to analyze lines to check corrupted data).
	 *	@return	int<-1,1>					Return integer <0 if KO, >0 if OK
	 */
	public function update_price($exclspec = 0, $roundingadjust = 'auto', $nodatabaseupdate = 0, $seller = null)
	{
		// phpcs:enable
		global $conf, $hookmanager, $action;

		$parameters = array('exclspec' => $exclspec, 'roundingadjust' => $roundingadjust, 'nodatabaseupdate' => $nodatabaseupdate, 'seller' => $seller);
		$reshook = $hookmanager->executeHooks('updateTotalPrice', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			return 1; // replacement code
		} elseif ($reshook < 0) {
			return -1; // failure
		} // reshook = 0 => execute normal code

		// Some external module want no update price after a trigger because they have another method to calculate the total (ex: with an extrafield)
		$isElementForSupplier = false;
		$roundTotalConstName = 'MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND'; // const for customer by default
		$MODULE = "";
		if ($this->element == 'propal') {
			$MODULE = "MODULE_DISALLOW_UPDATE_PRICE_PROPOSAL";
		} elseif ($this->element == 'commande' || $this->element == 'order') {
			$MODULE = "MODULE_DISALLOW_UPDATE_PRICE_ORDER";
		} elseif ($this->element == 'facture' || $this->element == 'invoice') {
			$MODULE = "MODULE_DISALLOW_UPDATE_PRICE_INVOICE";
		} elseif ($this->element == 'facture_fourn' || $this->element == 'supplier_invoice' || $this->element == 'invoice_supplier' || $this->element == 'invoice_supplier_rec') {
			$isElementForSupplier = true;
			$MODULE = "MODULE_DISALLOW_UPDATE_PRICE_SUPPLIER_INVOICE";
		} elseif ($this->element == 'order_supplier' || $this->element == 'supplier_order') {
			$isElementForSupplier = true;
			$MODULE = "MODULE_DISALLOW_UPDATE_PRICE_SUPPLIER_ORDER";
		} elseif ($this->element == 'supplier_proposal') {
			$isElementForSupplier = true;
			$MODULE = "MODULE_DISALLOW_UPDATE_PRICE_SUPPLIER_PROPOSAL";
		}
		if ($isElementForSupplier) {
			$roundTotalConstName = 'MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND_SUPPLIER'; // const for supplier
		}

		if (!empty($MODULE)) {
			if (getDolGlobalString($MODULE)) {
				$modsactivated = explode(',', getDolGlobalString($MODULE));
				foreach ($modsactivated as $mod) {
					if (isModEnabled($mod)) {
						return 1; // update was disabled by specific setup
					}
				}
			}
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

		$forcedroundingmode = $roundingadjust;
		if ($forcedroundingmode == 'auto' && isset($conf->global->{$roundTotalConstName})) {
			$forcedroundingmode = getDolGlobalString($roundTotalConstName);
		} elseif ($forcedroundingmode == 'auto') {
			$forcedroundingmode = '0';
		}

		$error = 0;

		$multicurrency_tx = !empty($this->multicurrency_tx) ? $this->multicurrency_tx : 1;

		// Define constants to find lines to sum (field name int the table_element_line not into table_element)
		$fieldtva = 'total_tva';
		$fieldlocaltax1 = 'total_localtax1';
		$fieldlocaltax2 = 'total_localtax2';
		$fieldup = 'subprice';
		$base_price_type = 'HT';
		if ($this->element == 'facture_fourn' || $this->element == 'invoice_supplier') {
			$fieldtva = 'tva';
			$fieldup = 'pu_ht';
		}
		if ($this->element == 'invoice_supplier_rec') {
			$fieldup = 'pu_ht';
		}
		if ($this->element == 'expensereport') {
			// Force rounding mode to '0', otherwise when you set MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND to 1, you may have lines with different totals.
			// For example, if you have 2 lines with same TTC amounts (6,2 Unit price TTC and VAT rate 20%), on the first line you got 5,17 on HT total
			// and 5,16 on HT total and 1,04 on VAT total to get 6,20 on TTT total on second line (see #30051).
			$forcedroundingmode = '0';
			$fieldup = 'value_unit';
			$base_price_type = 'TTC';
		}

		$sql = "SELECT rowid, qty, ".$fieldup." as up, remise_percent, total_ht, ".$fieldtva." as total_tva, total_ttc, ".$fieldlocaltax1." as total_localtax1, ".$fieldlocaltax2." as total_localtax2,";
		$sql .= ' tva_tx as vatrate, localtax1_tx, localtax2_tx, localtax1_type, localtax2_type, info_bits, product_type';
		if ($this->table_element_line == 'facturedet') {
			$sql .= ', situation_percent';
		}
		$sql .= ', multicurrency_total_ht, multicurrency_total_tva, multicurrency_total_ttc';
		$sql .= " FROM ".$this->db->prefix().$this->table_element_line;
		$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);
		if ($exclspec) {
			$product_field = 'product_type';
			if ($this->table_element_line == 'contratdet') {
				$product_field = ''; // contratdet table has no product_type field
			}
			if ($product_field) {
				$sql .= " AND ".$product_field." <> 9";
			}
		}
		$sql .= ' ORDER by rowid'; // We want to be certain to always use same order of line to not change lines differently when option MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND is used

		dol_syslog(get_class($this)."::update_price", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->total_ht  = 0;
			$this->total_tva = 0;
			$this->total_localtax1 = 0;
			$this->total_localtax2 = 0;
			$this->total_ttc = 0;
			$total_ht_by_vats  = array();
			$total_tva_by_vats = array();
			$total_ttc_by_vats = array();
			$this->multicurrency_total_ht = 0;
			$this->multicurrency_total_tva	= 0;
			$this->multicurrency_total_ttc	= 0;

			$this->db->begin();

			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				// Note: There is no check on detail line and no check on total, if $forcedroundingmode = '0'
				$parameters = array('fk_element' => $obj->rowid);
				$reshook = $hookmanager->executeHooks('changeRoundingMode', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				if (empty($reshook) && $forcedroundingmode == '0') {	// Check if data on line are consistent. This may solve lines that were not consistent because set with $forcedroundingmode='auto'
					// This part of code is to fix data. We should not call it too often.
					$localtax_array = array($obj->localtax1_type, $obj->localtax1_tx, $obj->localtax2_type, $obj->localtax2_tx);
					$tmpcal = calcul_price_total($obj->qty, $obj->up, $obj->remise_percent, $obj->vatrate, $obj->localtax1_tx, $obj->localtax2_tx, 0, $base_price_type, $obj->info_bits, $obj->product_type, $seller, $localtax_array, (isset($obj->situation_percent) ? $obj->situation_percent : 100), $multicurrency_tx);

					$diff_when_using_price_ht = price2num($tmpcal[1] - $obj->total_tva, 'MT', 1); // If price was set with tax price and unit price HT has a low number of digits, then we may have a diff on recalculation from unit price HT.
					$diff_on_current_total = price2num($obj->total_ttc - $obj->total_ht - $obj->total_tva - $obj->total_localtax1 - $obj->total_localtax2, 'MT', 1);
					//var_dump($obj->total_ht.' '.$obj->total_tva.' '.$obj->total_localtax1.' '.$obj->total_localtax2.' => '.$obj->total_ttc);
					//var_dump($diff_when_using_price_ht.' '.$diff_on_current_total);

					if ($diff_on_current_total) {
						// This should not happen, we should always have in table: total_ttc = total_ht + total_vat + total_localtax1 + total_localtax2
						$sqlfix = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldtva." = ".price2num((float) $tmpcal[1]).", total_ttc = ".price2num((float) $tmpcal[2])." WHERE rowid = ".((int) $obj->rowid);
						dol_syslog('We found inconsistent data into detailed line (diff_on_current_total = '.$diff_on_current_total.') for line rowid = '.$obj->rowid." (ht=".$obj->total_ht." vat=".$obj->total_tva." tax1=".$obj->total_localtax1." tax2=".$obj->total_localtax2." ttc=".$obj->total_ttc."). We fix the total_vat and total_ttc of line by running sqlfix = ".$sqlfix, LOG_WARNING);
						$resqlfix = $this->db->query($sqlfix);
						if (!$resqlfix) {
							dol_print_error($this->db, 'Failed to update line');
						}
						$obj->total_tva = $tmpcal[1];
						$obj->total_ttc = $tmpcal[2];
					} elseif ($diff_when_using_price_ht) {
						// After calculation from HT, total is consistent but we have found a difference between VAT part in calculation and into database and
						// we ask to force the use of rounding on line (like done on calculation) so we force update of line
						$sqlfix = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldtva." = ".price2num((float) $tmpcal[1]).", total_ttc = ".price2num((float) $tmpcal[2])." WHERE rowid = ".((int) $obj->rowid);
						dol_syslog('We found a line with different rounding data into detailed line (diff_when_using_price_ht = '.$diff_when_using_price_ht.' and diff_on_current_total = '.$diff_on_current_total.') for line rowid = '.$obj->rowid." (total vat of line calculated=".$tmpcal[1].", database=".$obj->total_tva."). We fix the total_vat and total_ttc of line by running sqlfix = ".$sqlfix);
						$resqlfix = $this->db->query($sqlfix);
						if (!$resqlfix) {
							dol_print_error($this->db, 'Failed to update line');
						}
						$obj->total_tva = $tmpcal[1];
						$obj->total_ttc = $tmpcal[2];
					}
				}

				$this->total_ht        += $obj->total_ht; // The field visible at end of line detail
				$this->total_tva       += $obj->total_tva;
				$this->total_localtax1 += $obj->total_localtax1;
				$this->total_localtax2 += $obj->total_localtax2;
				$this->total_ttc       += $obj->total_ttc;
				$this->multicurrency_total_ht        += $obj->multicurrency_total_ht; // The field visible at end of line detail
				$this->multicurrency_total_tva       += $obj->multicurrency_total_tva;
				$this->multicurrency_total_ttc       += $obj->multicurrency_total_ttc;

				if (!isset($total_ht_by_vats[$obj->vatrate])) {
					$total_ht_by_vats[$obj->vatrate] = 0;
				}
				if (!isset($total_tva_by_vats[$obj->vatrate])) {
					$total_tva_by_vats[$obj->vatrate] = 0;
				}
				if (!isset($total_ttc_by_vats[$obj->vatrate])) {
					$total_ttc_by_vats[$obj->vatrate] = 0;
				}
				$total_ht_by_vats[$obj->vatrate]  += $obj->total_ht;
				$total_tva_by_vats[$obj->vatrate] += $obj->total_tva;
				$total_ttc_by_vats[$obj->vatrate] += $obj->total_ttc;

				if ($forcedroundingmode == '1') {	// Check if we need adjustment onto line for vat. TODO This works on the company currency but not on foreign currency
					if ($base_price_type == 'TTC') {
						$tmpvat = price2num($total_ttc_by_vats[$obj->vatrate] * $obj->vatrate / (100 + $obj->vatrate), 'MT', 1);
					} else {
						$tmpvat = price2num($total_ht_by_vats[$obj->vatrate] * $obj->vatrate / 100, 'MT', 1);
					}
					$diff = price2num($total_tva_by_vats[$obj->vatrate] - (float) $tmpvat, 'MT', 1);
					//print 'Line '.$i.' rowid='.$obj->rowid.' vat_rate='.$obj->vatrate.' total_ht='.$obj->total_ht.' total_tva='.$obj->total_tva.' total_ttc='.$obj->total_ttc.' total_ht_by_vats='.$total_ht_by_vats[$obj->vatrate].' total_tva_by_vats='.$total_tva_by_vats[$obj->vatrate].' (new calculation = '.$tmpvat.') total_ttc_by_vats='.$total_ttc_by_vats[$obj->vatrate].($diff?" => DIFF":"")."<br>\n";
					if ($diff) {
						$maxdiff = (10 * pow(10, -1 * getDolGlobalInt('MAIN_MAX_DECIMALS_TOT', 0)));
						if (abs((float) $diff) > $maxdiff) {
							// If error is more than 10 times the accuracy of rounding. This should not happen.
							$errmsg = 'We found a rounding difference after line '.($obj->rowid).' between HT*VAT='.$tmpvat.' and total in database='.$total_tva_by_vats[$obj->vatrate].' (calculated with UP*qty) but diff='.$diff.' is too high (> '.$maxdiff.') to be corrected. Some data in your lines may be corrupted. Try to edit each line manually to fix this before restarting.';
							dol_syslog($errmsg, LOG_WARNING);
							$this->error = $errmsg;
							$error++;
							break;
						}

						if ($base_price_type == 'TTC') {
							$sqlfix = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldtva." = ".price2num($obj->total_tva - (float) $diff).", total_ht = ".price2num($obj->total_ht + (float) $diff)." WHERE rowid = ".((int) $obj->rowid);
							dol_syslog('We found a difference of '.$diff.' for line rowid = '.$obj->rowid.' between TotalHT('.$total_ht_by_vats[$obj->vatrate].')*VATrate('.$obj->vatrate.')='.$tmpvat.' and total in database='.$total_tva_by_vats[$obj->vatrate]." (calculated with UP*qty). We fix the total_vat and total_ht of line by running sqlfix = ".$sqlfix);
						} else {
							$sqlfix = "UPDATE ".$this->db->prefix().$this->table_element_line." SET ".$fieldtva." = ".price2num($obj->total_tva - (float) $diff).", total_ttc = ".price2num($obj->total_ttc - (float) $diff)." WHERE rowid = ".((int) $obj->rowid);
							dol_syslog('We found a difference of '.$diff.' for line rowid = '.$obj->rowid.' between TotalHT('.$total_ht_by_vats[$obj->vatrate].')*VATrate('.$obj->vatrate.')='.$tmpvat.' and total in database='.$total_tva_by_vats[$obj->vatrate]." (calculated with UP*qty). We fix the total_vat and total_ttc of line by running sqlfix = ".$sqlfix);
						}

						$resqlfix = $this->db->query($sqlfix);

						if (!$resqlfix) {
							dol_print_error($this->db, 'Failed to update line');
						}

						$this->total_tva = (float) price2num($this->total_tva - (float) $diff, '', 1);
						$total_tva_by_vats[$obj->vatrate] = (float) price2num($total_tva_by_vats[$obj->vatrate] - (float) $diff, '', 1);
						if ($base_price_type == 'TTC') {
							$this->total_ht = (float) price2num($this->total_ht + (float) $diff, '', 1);
							$total_ht_by_vats[$obj->vatrate] = (float) price2num($total_ht_by_vats[$obj->vatrate] + (float) $diff, '', 1);
						} else {
							$this->total_ttc = (float) price2num($this->total_ttc - (float) $diff, '', 1);
							$total_ttc_by_vats[$obj->vatrate] = (float) price2num($total_ttc_by_vats[$obj->vatrate] - (float) $diff, '', 1);
						}
					}
				}

				$i++;
			}

			// Add revenue stamp to total
			$this->total_ttc += isset($this->revenuestamp) ? $this->revenuestamp : 0;
			$this->multicurrency_total_ttc += isset($this->revenuestamp) ? ($this->revenuestamp * $multicurrency_tx) : 0;

			// Situations totals
			if (!empty($this->situation_cycle_ref) && !empty($this->situation_counter) && $this->situation_counter > 1 && method_exists($this, 'get_prev_sits')) {
				include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				if ($this->type != Facture::TYPE_CREDIT_NOTE) {	// @phpstan-ignore-line
					if (getDolGlobalInt('INVOICE_USE_SITUATION') != 2) {
						$prev_sits = $this->get_prev_sits();

						foreach ($prev_sits as $sit) {                // $sit is an object Facture loaded with a fetch.
							$this->total_ht -= $sit->total_ht;
							$this->total_tva -= $sit->total_tva;
							$this->total_localtax1 -= $sit->total_localtax1;
							$this->total_localtax2 -= $sit->total_localtax2;
							$this->total_ttc -= $sit->total_ttc;
							$this->multicurrency_total_ht -= $sit->multicurrency_total_ht;
							$this->multicurrency_total_tva -= $sit->multicurrency_total_tva;
							$this->multicurrency_total_ttc -= $sit->multicurrency_total_ttc;
						}
					}
				}
			}

			// Clean total
			$this->total_ht = (float) price2num($this->total_ht);
			$this->total_tva = (float) price2num($this->total_tva);
			$this->total_localtax1 = (float) price2num($this->total_localtax1);
			$this->total_localtax2 = (float) price2num($this->total_localtax2);
			$this->total_ttc = (float) price2num($this->total_ttc);

			$this->db->free($resql);

			// Now update global fields total_ht, total_ttc, total_tva, total_localtax1, total_localtax2, multicurrency_total_* of main object
			$fieldht = 'total_ht';
			$fieldtva = 'tva';
			$fieldlocaltax1 = 'localtax1';
			$fieldlocaltax2 = 'localtax2';
			$fieldttc = 'total_ttc';
			// Specific code for backward compatibility with old field names
			if (in_array($this->element, array('propal', 'commande', 'facture', 'facturerec', 'supplier_proposal', 'order_supplier', 'facture_fourn', 'invoice_supplier', 'invoice_supplier_rec', 'expensereport'))) {
				$fieldtva = 'total_tva';
			}

			if (!$error && empty($nodatabaseupdate)) {
				$sql = "UPDATE ".$this->db->prefix().$this->table_element.' SET';
				$sql .= " ".$fieldht." = ".((float) price2num($this->total_ht, 'MT', 1)).",";
				$sql .= " ".$fieldtva." = ".((float) price2num($this->total_tva, 'MT', 1)).",";
				$sql .= " ".$fieldlocaltax1." = ".((float) price2num($this->total_localtax1, 'MT', 1)).",";
				$sql .= " ".$fieldlocaltax2." = ".((float) price2num($this->total_localtax2, 'MT', 1)).",";
				$sql .= " ".$fieldttc." = ".((float) price2num($this->total_ttc, 'MT', 1));
				$sql .= ", multicurrency_total_ht = ".((float) price2num($this->multicurrency_total_ht, 'MT', 1));
				$sql .= ", multicurrency_total_tva = ".((float) price2num($this->multicurrency_total_tva, 'MT', 1));
				$sql .= ", multicurrency_total_ttc = ".((float) price2num($this->multicurrency_total_ttc, 'MT', 1));
				$sql .= " WHERE rowid = ".((int) $this->id);

				dol_syslog(get_class($this)."::update_price", LOG_DEBUG);
				$resql = $this->db->query($sql);

				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->db->lasterror();
				}
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			dol_print_error($this->db, 'Bad request in update_price');
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add an object link into llx_element_element.
	 *
	 *	@param		string	$origin		Linked element type
	 *	@param		int		$origin_id	Linked element id
	 * 	@param		User	$f_user		User that create
	 * 	@param		int		$notrigger	1=Does not execute triggers, 0=execute triggers
	 *	@return		int					Return integer <=0 if KO, >0 if OK
	 *	@see		fetchObjectLinked(), updateObjectLinked(), deleteObjectLinked()
	 */
	public function add_object_linked($origin = null, $origin_id = null, $f_user = null, $notrigger = 0)
	{
		// phpcs:enable
		global $user, $hookmanager, $action;
		$origin = (!empty($origin) ? $origin : $this->origin);
		$origin_id = (!empty($origin_id) ? $origin_id : $this->origin_id);
		$f_user = isset($f_user) ? $f_user : $user;

		// Special case
		if ($origin == 'order') {
			$origin = 'commande';
		}
		if ($origin == 'invoice') {
			$origin = 'facture';
		}
		if ($origin == 'invoice_template') {
			$origin = 'facturerec';
		}
		if ($origin == 'supplierorder') {
			$origin = 'order_supplier';
		}

		// Add module part to target type
		$targettype = $this->getElementType();

		$parameters = array('targettype' => $targettype);
		// Hook for explicitly set the targettype if it must be different than $this->element
		$reshook = $hookmanager->executeHooks('setLinkedObjectSourceTargetType', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			if (!empty($hookmanager->resArray['targettype'])) {
				$targettype = $hookmanager->resArray['targettype'];
			}
		}

		$this->db->begin();
		$error = 0;

		$sql = "INSERT INTO " . $this->db->prefix() . "element_element (";
		$sql .= "fk_source";
		$sql .= ", sourcetype";
		$sql .= ", fk_target";
		$sql .= ", targettype";
		$sql .= ") VALUES (";
		$sql .= ((int) $origin_id);
		$sql .= ", '" . $this->db->escape($origin) . "'";
		$sql .= ", " . ((int) $this->id);
		$sql .= ", '" . $this->db->escape($targettype) . "'";
		$sql .= ")";

		dol_syslog(get_class($this) . "::add_object_linked", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if (!$notrigger) {
				// Call trigger
				$this->context['link_origin'] = $origin;
				$this->context['link_origin_id'] = $origin_id;
				$result = $this->call_trigger('OBJECT_LINK_INSERT', $f_user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		} else {
			$this->error = $this->db->lasterror();
			$error++;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 * Return an element type string formatted like element_element target_type and source_type
	 *
	 * @return string
	 */
	public function getElementType()
	{
		// Elements of the core modules having a `$module` property but for which we may not want to prefix the element name with the module name for finding the linked object in llx_element_element.
		// It's because existing llx_element_element entries inserted prior to this modification (version <=14.2) may already use the element name alone in fk_source or fk_target (without the module name prefix).
		$coreModule = array('knowledgemanagement', 'partnership', 'workstation', 'ticket', 'recruitment', 'eventorganization', 'asset');
		// Add module part to target type if object has $module property and isn't in core modules.
		return ((!empty($this->module) && !in_array($this->module, $coreModule)) ? $this->module.'_' : '').$this->element;
	}


	/**
	 *	Fetch array of objects linked to current object (object of enabled modules only). Links are loaded into
	 *		this->linkedObjectsIds array +
	 *		this->linkedObjects array if $loadalsoobjects = 1 or $loadalsoobjects = type
	 *  Possible usage for parameters:
	 *  - all parameters empty -> we look all link to current object (current object can be source or target)
	 *  - source id+type -> will get list of targets linked to source
	 *  - target id+type -> will get list of sources linked to target
	 *  - source id+type + target type -> will get list of targets of the type linked to source
	 *  - target id+type + source type -> will get list of sources of the type linked to target
	 *
	 *	@param	?int		$sourceid			Object source id (if not defined, $this->id)
	 *	@param  string		$sourcetype			Object source type (if not defined, $this->element)
	 *	@param  ?int		$targetid			Object target id (if not defined, $this->id)
	 *	@param  string		$targettype			Object target type (if not defined, $this->element)
	 *	@param  string		$clause				'OR' or 'AND' clause used when both source id and target id are provided
	 *  @param  int<0,1>	$alsosametype		0=Return only links to object that differs from source type. 1=Include also link to objects of same type.
	 *  @param  string		$orderby			SQL 'ORDER BY' clause
	 *  @param	int<0,1>|string	$loadalsoobjects	Load also the array $this->linkedObjects. Use 0 to not load (increase performances), Use 1 to load all, Use value of type ('facture', 'facturerec', ...) to load only a type of object.
	 *	@return int<-1,1>						Return integer <0 if KO, >0 if OK
	 *  @see	add_object_linked(), updateObjectLinked(), deleteObjectLinked()
	 */
	public function fetchObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $clause = 'OR', $alsosametype = 1, $orderby = 'sourcetype', $loadalsoobjects = 1)
	{
		global $conf, $hookmanager, $action;

		// Important for pdf generation time reduction
		// This boolean is true if $this->linkedObjects has already been loaded with all objects linked without filter
		// If you need to force the reload, you can call clearObjectLinkedCache() before calling fetchObjectLinked()
		if ($this->id > 0 && !empty($this->linkedObjectsFullLoaded[$this->id])) {
			return 1;
		}

		$this->linkedObjectsIds = array();
		$this->linkedObjects = array();

		$justsource = false;
		$justtarget = false;
		$withtargettype = false;
		$withsourcetype = false;

		$parameters = array('sourcetype' => $sourcetype, 'sourceid' => $sourceid, 'targettype' => $targettype, 'targetid' => $targetid);
		// Hook for explicitly set the targettype if it must be differtent than $this->element
		$reshook = $hookmanager->executeHooks('setLinkedObjectSourceTargetType', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			if (!empty($hookmanager->resArray['sourcetype'])) {
				$sourcetype = $hookmanager->resArray['sourcetype'];
			}
			if (!empty($hookmanager->resArray['sourceid'])) {
				$sourceid = $hookmanager->resArray['sourceid'];
			}
			if (!empty($hookmanager->resArray['targettype'])) {
				$targettype = $hookmanager->resArray['targettype'];
			}
			if (!empty($hookmanager->resArray['targetid'])) {
				$targetid = $hookmanager->resArray['targetid'];
			}
		}

		if (!empty($sourceid) && !empty($sourcetype) && empty($targetid)) {
			$justsource = true; // the source (id and type) is a search criteria
			if (!empty($targettype)) {
				$withtargettype = true;
			}
		}
		if (!empty($targetid) && !empty($targettype) && empty($sourceid)) {
			$justtarget = true; // the target (id and type) is a search criteria
			if (!empty($sourcetype)) {
				$withsourcetype = true;
			}
		}

		$sourceid = (!empty($sourceid) ? $sourceid : $this->id);
		$targetid = (!empty($targetid) ? $targetid : $this->id);
		$sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);
		$targettype = (!empty($targettype) ? $targettype : $this->element);

		/*if (empty($sourceid) && empty($targetid))
		 {
		 dol_syslog('Bad usage of function. No source nor target id defined (nor as parameter nor as object id)', LOG_ERR);
		 return -1;
		 }*/

		// Links between objects are stored in table element_element
		$sql = "SELECT rowid, fk_source, sourcetype, fk_target, targettype";
		$sql .= " FROM ".$this->db->prefix()."element_element";
		$sql .= " WHERE ";
		if ($justsource || $justtarget) {
			if ($justsource) {
				$sql .= "fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."'";
				if ($withtargettype) {
					$sql .= " AND targettype = '".$this->db->escape($targettype)."'";
				}
			} elseif ($justtarget) {
				$sql .= "fk_target = ".((int) $targetid)." AND targettype = '".$this->db->escape($targettype)."'";
				if ($withsourcetype) {
					$sql .= " AND sourcetype = '".$this->db->escape($sourcetype)."'";
				}
			}
		} else {
			$sql .= "(fk_source = ".((int) $sourceid)." AND sourcetype = '".$this->db->escape($sourcetype)."')";
			$sql .= " ".$clause." (fk_target = ".((int) $targetid)." AND targettype = '".$this->db->escape($targettype)."')";
			if ($loadalsoobjects && $this->id > 0 && $sourceid == $this->id && $sourcetype == $this->element && $targetid == $this->id && $targettype == $this->element && $clause == 'OR') {
				$this->linkedObjectsFullLoaded[$this->id] = true;
			}
		}
		$sql .= " ORDER BY ".$orderby;

		dol_syslog(get_class($this)."::fetchObjectLink", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if ($justsource || $justtarget) {
					if ($justsource) {
						$this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
					} elseif ($justtarget) {
						$this->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
					}
				} else {
					if ($obj->fk_source == $sourceid && $obj->sourcetype == $sourcetype) {
						$this->linkedObjectsIds[$obj->targettype][$obj->rowid] = $obj->fk_target;
					}
					if ($obj->fk_target == $targetid && $obj->targettype == $targettype) {
						$this->linkedObjectsIds[$obj->sourcetype][$obj->rowid] = $obj->fk_source;
					}
				}
				$i++;
			}

			if (!empty($this->linkedObjectsIds)) {
				$tmparray = $this->linkedObjectsIds;
				foreach ($tmparray as $objecttype => $objectids) {       // $objecttype is a module name ('facture', 'mymodule', ...) or a module name with a suffix ('project_task', 'mymodule_myobj', ...)
					$element_properties = getElementProperties($objecttype);
					$element = $element_properties['element'];
					$classPath = $element_properties['classpath'];
					$classFile = $element_properties['classfile'];
					$className = $element_properties['classname'];
					$module = $element_properties['module'];

					// Here $module, $classFile and $className are set, we can use them.
					if (isModEnabled($module) && (($element != $this->element) || $alsosametype)) {
						if ($loadalsoobjects && (is_numeric($loadalsoobjects) || ($loadalsoobjects === $objecttype))) {
							dol_include_once('/'.$classPath.'/'.$classFile.'.class.php');
							if (class_exists($className)) {
								foreach ($objectids as $i => $objectid) {	// $i is rowid into llx_element_element
									$object = new $className($this->db);
									$ret = $object->fetch($objectid);
									if ($ret >= 0) {
										$this->linkedObjects[$objecttype][$i] = $object;
									}
								}
							}
						}
					} else {
						unset($this->linkedObjectsIds[$objecttype]);
					}
				}
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Clear the cache saying that all linked object were already loaded. So next fetchObjectLinked will reload all links.
	 *
	 *	@return int						Return integer <0 if KO, >0 if OK
	 *  @see	fetchObjectLinked()
	 */
	public function clearObjectLinkedCache()
	{
		if ($this->id > 0 && !empty($this->linkedObjectsFullLoaded[$this->id])) {
			unset($this->linkedObjectsFullLoaded[$this->id]);
		}

		return 1;
	}

	/**
	 *	Update object linked of a current object
	 *
	 *	@param	int		$sourceid		Object source id
	 *	@param  string	$sourcetype		Object source type
	 *	@param  int		$targetid		Object target id
	 *	@param  string	$targettype		Object target type
	 * 	@param	User	$f_user			User that create
	 * 	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return							int	>0 if OK, <0 if KO
	 *	@see	add_object_linked(), fetObjectLinked(), deleteObjectLinked()
	 */
	public function updateObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $f_user = null, $notrigger = 0)
	{
		global $user;
		$updatesource = false;
		$updatetarget = false;
		$f_user = isset($f_user) ? $f_user : $user;

		if (!empty($sourceid) && !empty($sourcetype) && empty($targetid) && empty($targettype)) {
			$updatesource = true;
		} elseif (empty($sourceid) && empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
			$updatetarget = true;
		}

		$this->db->begin();
		$error = 0;

		$sql = "UPDATE " . $this->db->prefix() . "element_element SET ";
		if ($updatesource) {
			$sql .= "fk_source = " . ((int) $sourceid);
			$sql .= ", sourcetype = '" . $this->db->escape($sourcetype) . "'";
			$sql .= " WHERE fk_target = " . ((int) $this->id);
			$sql .= " AND targettype = '" . $this->db->escape($this->element) . "'";
		} elseif ($updatetarget) {
			$sql .= "fk_target = " . ((int) $targetid);
			$sql .= ", targettype = '" . $this->db->escape($targettype) . "'";
			$sql .= " WHERE fk_source = " . ((int) $this->id);
			$sql .= " AND sourcetype = '" . $this->db->escape($this->element) . "'";
		}

		dol_syslog(get_class($this) . "::updateObjectLinked", LOG_DEBUG);
		if ($this->db->query($sql)) {
			if (!$notrigger) {
				// Call trigger
				$this->context['link_source_id'] = $sourceid;
				$this->context['link_source_type'] = $sourcetype;
				$this->context['link_target_id'] = $targetid;
				$this->context['link_target_type'] = $targettype;
				$result = $this->call_trigger('OBJECT_LINK_MODIFY', $f_user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		} else {
			$this->error = $this->db->lasterror();
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
	 *	Delete all links between an object $this
	 *
	 *	@param	int		$sourceid		Object source id
	 *	@param  string	$sourcetype		Object source type
	 *	@param  int		$targetid		Object target id
	 *	@param  string	$targettype		Object target type
	 *  @param	int		$rowid			Row id of line to delete. If defined, other parameters are not used.
	 * 	@param	User	$f_user			User that create
	 * 	@param	int		$notrigger		1=Does not execute triggers, 0= execute triggers
	 *	@return     					int	>0 if OK, <0 if KO
	 *	@see	add_object_linked(), updateObjectLinked(), fetchObjectLinked()
	 */
	public function deleteObjectLinked($sourceid = null, $sourcetype = '', $targetid = null, $targettype = '', $rowid = 0, $f_user = null, $notrigger = 0)
	{
		global $user;
		$deletesource = false;
		$deletetarget = false;
		$f_user = isset($f_user) ? $f_user : $user;

		if (!empty($sourceid) && !empty($sourcetype) && empty($targetid) && empty($targettype)) {
			$deletesource = true;
		} elseif (empty($sourceid) && empty($sourcetype) && !empty($targetid) && !empty($targettype)) {
			$deletetarget = true;
		}

		$sourceid = (!empty($sourceid) ? $sourceid : $this->id);
		$sourcetype = (!empty($sourcetype) ? $sourcetype : $this->element);
		$targetid = (!empty($targetid) ? $targetid : $this->id);
		$targettype = (!empty($targettype) ? $targettype : $this->element);
		$this->db->begin();
		$error = 0;

		if (!$notrigger) {
			// Call trigger
			$this->context['link_id'] = $rowid;
			$this->context['link_source_id'] = $sourceid;
			$this->context['link_source_type'] = $sourcetype;
			$this->context['link_target_id'] = $targetid;
			$this->context['link_target_type'] = $targettype;
			$result = $this->call_trigger('OBJECT_LINK_DELETE', $f_user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			$sql = "DELETE FROM " . $this->db->prefix() . "element_element";
			$sql .= " WHERE";
			if ($rowid > 0) {
				$sql .= " rowid = " . ((int) $rowid);
			} else {
				if ($deletesource) {
					$sql .= " fk_source = " . ((int) $sourceid) . " AND sourcetype = '" . $this->db->escape($sourcetype) . "'";
					$sql .= " AND fk_target = " . ((int) $this->id) . " AND targettype = '" . $this->db->escape($this->element) . "'";
				} elseif ($deletetarget) {
					$sql .= " fk_target = " . ((int) $targetid) . " AND targettype = '" . $this->db->escape($targettype) . "'";
					$sql .= " AND fk_source = " . ((int) $this->id) . " AND sourcetype = '" . $this->db->escape($this->element) . "'";
				} else {
					$sql .= " (fk_source = " . ((int) $this->id) . " AND sourcetype = '" . $this->db->escape($this->element) . "')";
					$sql .= " OR";
					$sql .= " (fk_target = " . ((int) $this->id) . " AND targettype = '" . $this->db->escape($this->element) . "')";
				}
			}

			dol_syslog(get_class($this) . "::deleteObjectLinked", LOG_DEBUG);
			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 * Function used to get an array with all items linked to an object id in association table
	 *
	 * @param	int		$fk_object_where		id of object we need to get linked items
	 * @param	string	$field_select			name of field we need to get a list
	 * @param	string	$field_where			name of field of object we need to get linked items
	 * @param	string	$table_element			name of association table
	 * @return 	array|int						Array of record, -1 if empty
	 */
	public static function getAllItemsLinkedByObjectID($fk_object_where, $field_select, $field_where, $table_element)
	{
		if (empty($fk_object_where) || empty($field_where) || empty($table_element)) {
			return -1;
		}
		if (!preg_match('/^[_a-zA-Z0-9]+$/', $field_select)) {
			dol_syslog('Invalid value $field_select for parameter '.$field_select.' in call to getAllItemsLinkedByObjectID(). Must be a single field name.', LOG_ERR);
		}

		global $db;

		$sql = "SELECT ".$field_select." FROM ".$db->prefix().$table_element." WHERE ".$field_where." = ".((int) $fk_object_where);
		$resql = $db->query($sql);

		$TRes = array();
		if (!empty($resql)) {
			while ($res = $db->fetch_object($resql)) {
				$TRes[] = $res->{$field_select};
			}
		}

		return $TRes;
	}

	/**
	 * Count items linked to an object id in association table
	 *
	 * @param	int		$fk_object_where		id of object we need to get linked items
	 * @param	string	$field_where			name of field of object we need to get linked items
	 * @param	string	$table_element			name of association table
	 * @return 	array|int						Array of record, -1 if empty
	 */
	public static function getCountOfItemsLinkedByObjectID($fk_object_where, $field_where, $table_element)
	{
		if (empty($fk_object_where) || empty($field_where) || empty($table_element)) {
			return -1;
		}

		global $db;

		$sql = "SELECT COUNT(*) as nb FROM ".$db->prefix().$table_element." WHERE ".$field_where." = ".((int) $fk_object_where);
		$resql = $db->query($sql);
		$n = 0;
		if ($resql) {
			$res = $db->fetch_object($resql);
			if ($res) {
				$n = $res->nb;
			}
		}

		return $n;
	}

	/**
	 * Function used to remove all items linked to an object id in association table
	 *
	 * @param	int		$fk_object_where		id of object we need to remove linked items
	 * @param	string	$field_where			name of field of object we need to delete linked items
	 * @param	string	$table_element			name of association table
	 * @return 	int								Return integer <0 if KO, 0 if nothing done, >0 if OK and something done
	 */
	public static function deleteAllItemsLinkedByObjectID($fk_object_where, $field_where, $table_element)
	{
		if (empty($fk_object_where) || empty($field_where) || empty($table_element)) {
			return -1;
		}

		global $db;

		$sql = "DELETE FROM ".$db->prefix().$table_element." WHERE ".$field_where." = ".((int) $fk_object_where);
		$resql = $db->query($sql);

		if (empty($resql)) {
			return 0;
		}

		return 1;
	}

	/**
	 *      Set status of an object.
	 *
	 *      @param	int		$status			Status to set
	 *      @param	int		$elementId		Id of element to force (use this->id by default if null)
	 *      @param	string	$elementType	Type of element to force (use this->table_element by default)
	 *      @param	string	$trigkey		Trigger key to use for trigger. Use '' means automatic but it is not recommended and is deprecated.
	 *      @param	string	$fieldstatus	Name of status field in this->table_element
	 *      @return int						Return integer <0 if KO, >0 if OK
	 */
	public function setStatut($status, $elementId = null, $elementType = '', $trigkey = '', $fieldstatus = 'fk_statut')
	{
		global $user;

		$savElementId = $elementId; // To be used later to know if we were using the method using the id of this or not.

		$elementId = (!empty($elementId) ? $elementId : $this->id);
		$elementTable = (!empty($elementType) ? $elementType : $this->table_element);

		$this->db->begin();

		if ($elementTable == 'facture_rec') {
			$fieldstatus = "suspended";
		}
		if ($elementTable == 'mailing') {
			$fieldstatus = "statut";
		}
		if ($elementTable == 'cronjob') {
			$fieldstatus = "status";
		}
		if ($elementTable == 'user') {
			$fieldstatus = "statut";
		}
		if ($elementTable == 'expensereport') {
			$fieldstatus = "fk_statut";
		}
		if ($elementTable == 'receptiondet_batch') {
			$fieldstatus = "status";
		}
		if ($elementTable == 'prelevement_bons') {
			$fieldstatus = "statut";
		}
		if (isset($this->fields) && is_array($this->fields) && array_key_exists('status', $this->fields)) {
			$fieldstatus = 'status';
		}

		$sql = "UPDATE ".$this->db->prefix().$this->db->sanitize($elementTable);
		$sql .= " SET ".$this->db->sanitize($fieldstatus)." = ".((int) $status);
		// If status = 1 = validated, update also fk_user_valid
		// TODO Replace the test on $elementTable by doing a test on existence of the field in $this->fields
		if ($status == 1 && in_array($elementTable, array('expensereport', 'inventory'))) {
			$sql .= ", fk_user_valid = ".((int) $user->id);
		}
		if ($status == 1 && in_array($elementTable, array('expensereport'))) {
			$sql .= ", date_valid = '".$this->db->idate(dol_now())."'";
		}
		if ($status == 1 && in_array($elementTable, array('inventory'))) {
			$sql .= ", date_validation = '".$this->db->idate(dol_now())."'";
		}
		$sql .= " WHERE rowid = ".((int) $elementId);
		$sql .= " AND ".$fieldstatus." <> ".((int) $status);	// We avoid update if status already correct

		dol_syslog(get_class($this)."::setStatut", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$error = 0;

			$nb_rows_affected = $this->db->affected_rows($resql);	// should be 1 or 0 if status was already correct

			if ($nb_rows_affected > 0) {
				if (empty($trigkey)) {
					// Try to guess trigkey (for backward compatibility, now we should have trigkey defined into the call of setStatus)
					if ($this->element == 'supplier_proposal' && $status == 2) {
						$trigkey = 'SUPPLIER_PROPOSAL_SIGN'; // 2 = SupplierProposal::STATUS_SIGNED. Can't use constant into this generic class
					}
					if ($this->element == 'supplier_proposal' && $status == 3) {
						$trigkey = 'SUPPLIER_PROPOSAL_REFUSE'; // 3 = SupplierProposal::STATUS_REFUSED. Can't use constant into this generic class
					}
					if ($this->element == 'supplier_proposal' && $status == 4) {
						$trigkey = 'SUPPLIER_PROPOSAL_CLOSE'; // 4 = SupplierProposal::STATUS_CLOSED. Can't use constant into this generic class
					}
					if ($this->element == 'fichinter' && $status == 3) {
						$trigkey = 'FICHINTER_CLASSIFY_DONE';
					}
					if ($this->element == 'fichinter' && $status == 2) {
						$trigkey = 'FICHINTER_CLASSIFY_BILLED';
					}
					if ($this->element == 'fichinter' && $status == 1) {
						$trigkey = 'FICHINTER_CLASSIFY_UNBILLED';
					}
				}

				$this->context = array_merge($this->context, array('newstatus' => $status));

				if ($trigkey) {
					// Call trigger
					$result = $this->call_trigger($trigkey, $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			} else {
				// The status was probably already good. We do nothing more, no triggers.
			}

			if (!$error) {
				$this->db->commit();

				if (empty($savElementId)) {
					// If the element we update is $this (so $elementId was provided as null)
					if ($fieldstatus == 'tosell') {
						$this->status = $status;
					} elseif ($fieldstatus == 'tobuy') {
						$this->status_buy = $status;	// @phpstan-ignore-line
					} else {
						$this->status = $status;
					}
				}

				return 1;
			} else {
				$this->db->rollback();
				dol_syslog(get_class($this)."::setStatut ".$this->error, LOG_ERR);
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Load type of canvas of an object if it exists
	 *
	 *  @param      int		$id     Record id
	 *  @param      string	$ref    Record ref
	 *  @return		int				Return integer <0 if KO, 0 if nothing done, >0 if OK
	 */
	public function getCanvas($id = 0, $ref = '')
	{
		global $conf;

		if (empty($id) && empty($ref)) {
			return 0;
		}
		if (getDolGlobalString('MAIN_DISABLE_CANVAS')) {
			return 0; // To increase speed. Not enabled by default.
		}

		// Clean parameters
		$ref = trim($ref);

		$sql = "SELECT rowid, canvas";
		$sql .= " FROM ".$this->db->prefix().$this->table_element;
		$sql .= " WHERE entity IN (".getEntity($this->element).")";
		if (!empty($id)) {
			$sql .= " AND rowid = ".((int) $id);
		}
		if (!empty($ref)) {
			$sql .= " AND ref = '".$this->db->escape($ref)."'";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$this->canvas = $obj->canvas;
				return 1;
			} else {
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 * 	Get special code of a line
	 *
	 * 	@param	int		$lineid		Id of line
	 * 	@return	int					Special code
	 */
	public function getSpecialCode($lineid)
	{
		$sql = "SELECT special_code FROM ".$this->db->prefix().$this->table_element_line;
		$sql .= " WHERE rowid = ".((int) $lineid);
		$resql = $this->db->query($sql);
		if ($resql) {
			$row = $this->db->fetch_row($resql);
			return (!empty($row[0]) ? $row[0] : 0);
		}

		return 0;
	}

	/**
	 *  Function to check if an object is used by others (by children).
	 *  Check is done into this->childtables. There is no check into llx_element_element.
	 *
	 *  @param	int		$id			Force id of object
	 *  @param	int		$entity		Force entity to check
	 *  @return	int					Return integer <0 if KO, 0 if not used, >0 if already used
	 */
	public function isObjectUsed($id = 0, $entity = 0)
	{
		global $langs;

		if (empty($id)) {
			$id = $this->id;
		}

		// Check parameters
		if (!isset($this->childtables) || !is_array($this->childtables) || count($this->childtables) == 0) {
			dol_print_error(null, 'Called isObjectUsed on a class with property this->childtables not defined');
			return -1;
		}

		$arraytoscan = $this->childtables;		// array('tablename'=>array('fk_element'=>'parentfield'), ...) or array('tablename'=>array('parent'=>table_parent, 'parentkey'=>'nameoffieldforparentfkkey'), ...)
		// For backward compatibility, we check if array is old format array('tablename1', 'tablename2', ...)
		$tmparray = array_keys($this->childtables);
		if (is_numeric($tmparray[0])) {
			$arraytoscan = array_flip($this->childtables);
		}

		// Test if child exists
		$haschild = 0;
		foreach ($arraytoscan as $table => $element) {
			//print $id.'-'.$table.'-'.$elementname.'<br>';
			// Check if element can be deleted
			$sql = "SELECT COUNT(*) as nb";
			$sql .= " FROM ".$this->db->prefix().$table." as c";
			if (!empty($element['parent']) && !empty($element['parentkey'])) {
				$sql .= ", ".$this->db->prefix().$element['parent']." as p";
			}
			if (!empty($element['fk_element'])) {
				$sql .= " WHERE c.".$element['fk_element']." = ".((int) $id);
			} else {
				$sql .= " WHERE c.".$this->fk_element." = ".((int) $id);
			}
			if (!empty($element['parent']) && !empty($element['parentkey'])) {
				$sql .= " AND c.".$element['parentkey']." = p.rowid";
			}
			if (!empty($element['parent']) && !empty($element['parenttypefield']) && !empty($element['parenttypevalue'])) {
				$sql .= " AND c.".$element['parenttypefield']." = '".$this->db->escape($element['parenttypevalue'])."'";
			}
			if (!empty($entity)) {
				if (!empty($element['parent']) && !empty($element['parentkey'])) {
					$sql .= " AND p.entity = ".((int) $entity);
				} else {
					$sql .= " AND c.entity = ".((int) $entity);
				}
			}

			$resql = $this->db->query($sql);
			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj->nb > 0) {
					$langs->load("errors");
					//print 'Found into table '.$table.', type '.$langs->transnoentitiesnoconv($elementname).', haschild='.$haschild;
					$haschild += $obj->nb;
					if (is_numeric($element)) {	// very old usage array('table1', 'table2', ...)
						$this->errors[] = $langs->transnoentitiesnoconv("ErrorRecordHasAtLeastOneChildOfType", method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref, $table);
					} elseif (is_string($element)) { // old usage array('table1' => 'TranslateKey1', 'table2' => 'TranslateKey2', ...)
						$this->errors[] = $langs->transnoentitiesnoconv("ErrorRecordHasAtLeastOneChildOfType", method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref, $langs->transnoentitiesnoconv($element));
					} else { // new usage: $element['name']=Translation key
						$this->errors[] = $langs->transnoentitiesnoconv("ErrorRecordHasAtLeastOneChildOfType", method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref, $langs->transnoentitiesnoconv($element['name']));
					}
					break; // We found at least one, we stop here
				}
			} else {
				$this->errors[] = $this->db->lasterror();
				return -1;
			}
		}
		if ($haschild > 0) {
			$this->errors[] = "ErrorRecordHasChildren";
			return $haschild;
		} else {
			return 0;
		}
	}

	/**
	 *  Function to say how many lines object contains
	 *
	 *	@param	int		$predefined		-1=All, 0=Count free product/service only, 1=Count predefined product/service only, 2=Count predefined product, 3=Count predefined service
	 *  @return	int						Return integer <0 if KO, 0 if no predefined products, nb of lines with predefined products if found
	 */
	public function hasProductsOrServices($predefined = -1)
	{
		$nb = 0;

		foreach ($this->lines as $key => $val) {
			$qualified = 0;
			if ($predefined == -1) {
				$qualified = 1;
			}
			if ($predefined == 1 && $val->fk_product > 0) {
				$qualified = 1;
			}
			if ($predefined == 0 && $val->fk_product <= 0) {
				$qualified = 1;
			}
			if ($predefined == 2 && $val->fk_product > 0 && $val->product_type == 0) {
				$qualified = 1;
			}
			if ($predefined == 3 && $val->fk_product > 0 && $val->product_type == 1) {
				$qualified = 1;
			}
			if ($qualified) {
				$nb++;
			}
		}
		dol_syslog(get_class($this).'::hasProductsOrServices we found '.$nb.' qualified lines of products/servcies');
		return $nb;
	}

	/**
	 * Function that returns the total amount HT of discounts applied for all lines.
	 *
	 * @return 	float|null			Total amount of discount, or null if $table_element_line is empty
	 */
	public function getTotalDiscount()
	{
		if (!empty($this->table_element_line)) {
			$total_discount = 0.00;

			$sql = "SELECT subprice as pu_ht, qty, remise_percent, total_ht";
			$sql .= " FROM ".$this->db->prefix().$this->table_element_line;
			$sql .= " WHERE ".$this->fk_element." = ".((int) $this->id);

			dol_syslog(get_class($this).'::getTotalDiscount', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$pu_ht = $obj->pu_ht;
					$qty = $obj->qty;
					$total_ht = $obj->total_ht;

					$total_discount_line = (float) price2num(($pu_ht * $qty) - $total_ht, 'MT');
					$total_discount += $total_discount_line;

					$i++;
				}
			}

			//print $total_discount; exit;
			return (float) price2num($total_discount);
		}

		return null;
	}


	/**
	 * Return into unit=0, the calculated total of weight and volume of all lines * qty
	 * Calculate by adding weight and volume of each product line, so properties ->volume/volume_units/weight/weight_units must be loaded on line.
	 *
	 * @return	array{weight:int|float,volume:int|float,ordered:int|float,toship:int|float}|array{}		array('weight'=>...,'volume'=>...)
	 */
	public function getTotalWeightVolume()
	{
		$totalWeight = 0;
		$totalVolume = 0;
		// defined for shipment only
		$totalOrdered = 0;
		// defined for shipment only
		$totalToShip = 0;

		if (empty($this->lines)) {
			return array('weight' => $totalWeight, 'volume' => $totalVolume, 'ordered' => $totalOrdered, 'toship' => $totalToShip);
		}

		foreach ($this->lines as $line) {
			if (isset($line->qty_asked)) {
				$totalOrdered += $line->qty_asked; // defined for shipment only
			}
			if (isset($line->qty_shipped)) {
				$totalToShip += $line->qty_shipped; // defined for shipment only
			} elseif ($line->element == 'commandefournisseurdispatch' && isset($line->qty)) {
				if (empty($totalToShip)) {
					$totalToShip = 0;
				}
				$totalToShip += $line->qty; // defined for reception only
			}

			// Define qty, weight, volume, weight_units, volume_units
			if ($this->element == 'shipping') {
				// for shipments
				$qty = $line->qty_shipped ? $line->qty_shipped : 0;
			} else {
				$qty = $line->qty ? $line->qty : 0;
			}

			$weight = !empty($line->weight) ? $line->weight : 0;
			($weight == 0 && !empty($line->product->weight)) ? $weight = $line->product->weight : 0;
			$volume = !empty($line->volume) ? $line->volume : 0;
			($volume == 0 && !empty($line->product->volume)) ? $volume = $line->product->volume : 0;

			$weight_units = !empty($line->weight_units) ? $line->weight_units : 0;
			($weight_units == 0 && !empty($line->product->weight_units)) ? $weight_units = $line->product->weight_units : 0;
			$volume_units = !empty($line->volume_units) ? $line->volume_units : 0;
			($volume_units == 0 && !empty($line->product->volume_units)) ? $volume_units = $line->product->volume_units : 0;

			$weightUnit = 0;
			$volumeUnit = 0;
			if (!empty($weight_units)) {
				$weightUnit = $weight_units;
			}
			if (!empty($volume_units)) {
				$volumeUnit = $volume_units;
			}

			if (empty($totalWeight)) {
				$totalWeight = 0; // Avoid warning because $totalWeight is ''
			}
			if (empty($totalVolume)) {
				$totalVolume = 0; // Avoid warning because $totalVolume is ''
			}

			//var_dump($line->volume_units);
			if ($weight_units < 50) {   // < 50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
				$trueWeightUnit = pow(10, $weightUnit);
				$totalWeight += $weight * $qty * $trueWeightUnit;
			} else {
				if ($weight_units == 99) {
					// conversion 1 Pound = 0.45359237 KG
					$trueWeightUnit = 0.45359237;
					$totalWeight += $weight * $qty * $trueWeightUnit;
				} elseif ($weight_units == 98) {
					// conversion 1 Ounce = 0.0283495 KG
					$trueWeightUnit = 0.0283495;
					$totalWeight += $weight * $qty * $trueWeightUnit;
				} else {
					$totalWeight += $weight * $qty; // This may be wrong if we mix different units
				}
			}
			if ($volume_units < 50) {   // >50 means a standard unit (power of 10 of official unit), > 50 means an exotic unit (like inch)
				//print $line->volume."x".$line->volume_units."x".($line->volume_units < 50)."x".$volumeUnit;
				$trueVolumeUnit = pow(10, $volumeUnit);
				//print $line->volume;
				$totalVolume += $volume * $qty * $trueVolumeUnit;
			} else {
				$totalVolume += $volume * $qty; // This may be wrong if we mix different units
			}
		}

		return array('weight' => $totalWeight, 'volume' => $totalVolume, 'ordered' => $totalOrdered, 'toship' => $totalToShip);
	}


	/**
	 *	Set extra parameters
	 *
	 *	@return	int      Return integer <0 if KO, >0 if OK
	 */
	public function setExtraParameters()
	{
		$this->db->begin();

		$extraparams = (!empty($this->extraparams) ? json_encode($this->extraparams) : null);

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET extraparams = ".(!empty($extraparams) ? "'".$this->db->escape($extraparams)."'" : "null");
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::setExtraParameters", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	// --------------------
	// TODO: All functions here must be redesigned and moved as they are not business functions but output functions
	// --------------------

	/* This is to show add lines */

	/**
	 *	Show add free and predefined products/services form
	 *
	 *  @param	int		        $dateSelector       1=Show also date range input fields
	 *  @param	Societe			$seller				Object thirdparty who sell
	 *  @param	?Societe		$buyer				Object thirdparty who buy
	 *  @param	string			$defaulttpldir		Directory where to find the template
	 *	@return	void
	 */
	public function formAddObjectLine($dateSelector, $seller, $buyer, $defaulttpldir = '/core/tpl')
	{
		global $conf, $user, $langs, $object, $hookmanager, $extrafields, $form;

		// Line extrafield
		if (!is_object($extrafields)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
		}
		$extrafields->fetch_name_optionals_label($this->table_element_line);

		// Output template part (modules that overwrite templates must declare this into descriptor)
		// Use global variables + $dateSelector + $seller and $buyer
		// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook 'formAddObjectLine'.
		$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
		foreach ($dirtpls as $module => $reldir) {
			if (!empty($module)) {
				$tpl = dol_buildpath($reldir.'/objectline_create.tpl.php');
			} else {
				$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_create.tpl.php';
			}

			if (empty($conf->file->strict_mode)) {
				$res = @include $tpl;
			} else {
				$res = include $tpl; // for debug
			}
			if ($res) {
				break;
			}
		}
	}



	/* This is to show array of line of details */


	/**
	 *	Return HTML table for object lines
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *	If lines are into a template, title must also be into a template
	 *	But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 *	@param	string		$action				Action code
	 *	@param  Societe		$seller            	Object of seller third party
	 *	@param  ?Societe  	$buyer             	Object of buyer third party
	 *	@param	int			$selected		   	ID line selected
	 *	@param  int	    	$dateSelector      	1=Show also date range input fields
	 *  @param	string		$defaulttpldir		Directory where to find the template
	 *	@return	void
	 */
	public function printObjectLines($action, $seller, $buyer, $selected = 0, $dateSelector = 0, $defaulttpldir = '/core/tpl')
	{
		global $conf, $hookmanager, $langs, $user, $form, $extrafields, $object;
		// TODO We should not use global var for this
		global $inputalsopricewithtax, $usemargins, $disableedit, $disablemove, $disableremove, $outputalsopricetotalwithtax;

		// Define $usemargins (used by objectline_xxx.tpl.php files)
		$usemargins = 0;
		if (isModEnabled('margin') && !empty($this->element) && in_array($this->element, array('facture', 'facturerec', 'propal', 'commande'))) {
			$usemargins = 1;
		}

		$num = count($this->lines);

		// Line extrafield
		if (!is_object($extrafields)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
		}
		$extrafields->fetch_name_optionals_label($this->table_element_line);

		$parameters = array('num' => $num, 'dateSelector' => $dateSelector, 'seller' => $seller, 'buyer' => $buyer, 'selected' => $selected, 'table_element_line' => $this->table_element_line);
		$reshook = $hookmanager->executeHooks('printObjectLineTitle', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				$res = 0;
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir.'/objectline_title.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_title.tpl.php';
				}
				if (file_exists($tpl)) {
					if (empty($conf->file->strict_mode)) {
						$res = @include $tpl;
					} else {
						$res = include $tpl; // for debug
					}
				}
				if ($res) {
					break;
				}
			}
		}

		$i = 0;

		print "<!-- begin printObjectLines() --><tbody>\n";
		foreach ($this->lines as $line) {
			//Line extrafield
			$line->fetch_optionals();

			//if (is_object($hookmanager) && (($line->product_type == 9 && !empty($line->special_code)) || !empty($line->fk_parent_line)))
			if (is_object($hookmanager)) {   // Old code is commented on preceding line.
				if (empty($line->fk_parent_line)) {
					$parameters = array('line' => $line, 'num' => $num, 'i' => $i, 'dateSelector' => $dateSelector, 'seller' => $seller, 'buyer' => $buyer, 'selected' => $selected, 'table_element_line' => $line->table_element, 'defaulttpldir' => $defaulttpldir);
					$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				} else {
					$parameters = array('line' => $line, 'num' => $num, 'i' => $i, 'dateSelector' => $dateSelector, 'seller' => $seller, 'buyer' => $buyer, 'selected' => $selected, 'table_element_line' => $line->table_element, 'fk_parent_line' => $line->fk_parent_line, 'defaulttpldir' => $defaulttpldir);
					$reshook = $hookmanager->executeHooks('printObjectSubLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				}
			}
			if (empty($reshook)) {
				$this->printObjectLine($action, $line, '', $num, $i, $dateSelector, $seller, $buyer, $selected, $extrafields, $defaulttpldir);
			}

			$i++;
		}
		print "</tbody><!-- end printObjectLines() -->\n";
	}

	/**
	 *	Return HTML content of a detail line
	 *	TODO Move this into an output class file (htmlline.class.php)
	 *
	 *	@param	string      		$action				GET/POST action
	 *	@param  CommonObjectLine 	$line			    Selected object line to output
	 *	@param  ''		    		$var               	Not used
	 *	@param  int		    		$num               	Number of line (0)
	 *	@param  int		    		$i					I
	 *	@param  int		    		$dateSelector      	1=Show also date range input fields
	 *	@param  Societe	    		$seller            	Object of seller third party
	 *	@param  Societe	    		$buyer             	Object of buyer third party
	 *	@param	int					$selected		   	ID line selected
	 *  @param  Extrafields			$extrafields		Object of extrafields
	 *  @param	string				$defaulttpldir		Directory where to find the template (deprecated)
	 *	@return	void
	 */
	public function printObjectLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafields = null, $defaulttpldir = '/core/tpl')
	{
		global $conf, $langs, $user, $object, $hookmanager;
		global $form;
		global $disableedit, $disablemove, $disableremove; // TODO We should not use global var for this !

		// var used into tpl
		$text = '';
		$description = '';

		// Line in view mode
		if ($action != 'editline' || $selected != $line->id) {
			// Product
			if (!empty($line->fk_product) && $line->fk_product > 0) {
				$product_static = new Product($this->db);
				$product_static->fetch($line->fk_product);

				$product_static->ref = $line->ref; //can change ref in hook
				$product_static->label = !empty($line->label) ? $line->label : ""; //can change label in hook

				$text = $product_static->getNomUrl(1);

				// Define output language and label
				if (getDolGlobalInt('MAIN_MULTILANGS')) {
					if (property_exists($this, 'socid') && !is_object($this->thirdparty)) {
						dol_print_error(null, 'Error: Method printObjectLine was called on an object and object->fetch_thirdparty was not done before');
						return;
					}

					$prod = new Product($this->db);
					$prod->fetch($line->fk_product);

					$outputlangs = $langs;
					$newlang = '';
					if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if (getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE') && empty($newlang) && is_object($this->thirdparty)) {
						$newlang = $this->thirdparty->default_lang; // To use language of customer
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $line->product_label;
				} else {
					$label = $line->product_label;
				}

				$text .= ' - '.(!empty($line->label) ? $line->label : $label);
				$description .= (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : (!empty($line->description) ? dol_htmlentitiesbr($line->description) : '')); // Description is what to show on popup. We shown nothing if already into desc.
			}

			$line->pu_ttc = price2num((!empty($line->subprice) ? $line->subprice : 0) * (1 + ((!empty($line->tva_tx) ? $line->tva_tx : 0) / 100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				$res = 0;
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir.'/objectline_view.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_view.tpl.php';
				}
				//var_dump($tpl);
				if (file_exists($tpl)) {
					if (empty($conf->file->strict_mode)) {
						$res = @include $tpl;
					} else {
						$res = include $tpl; // for debug
					}
				}
				if ($res) {
					break;
				}
			}
		}

		// Line in update mode
		if ($this->status == 0 && $action == 'editline' && $selected == $line->id) {
			$label = (!empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));

			$line->pu_ttc = price2num($line->subprice * (1 + ($line->tva_tx / 100)), 'MU');

			// Output template part (modules that overwrite templates must declare this into descriptor)
			// Use global variables + $dateSelector + $seller and $buyer
			// Note: This is deprecated. If you need to overwrite the tpl file, use instead the hook printObjectLine and printObjectSubLine.
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
			foreach ($dirtpls as $module => $reldir) {
				if (!empty($module)) {
					$tpl = dol_buildpath($reldir.'/objectline_edit.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/objectline_edit.tpl.php';
				}

				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res) {
					break;
				}
			}
		}
	}


	/* This is to show array of line of details of source object */


	/**
	 * 	Return HTML table table of source object lines
	 *  TODO Move this and previous function into output html class file (htmlline.class.php).
	 *  If lines are into a template, title must also be into a template
	 *  But for the moment we don't know if it's possible, so we keep the method available on overloaded objects.
	 *
	 *	@param	''|'services'	$restrictlist	''=All lines, 'services'=Restrict to services only
	 *  @param  int[]       $selectedLines      Array of lines id for selected lines
	 *  @return	void
	 */
	public function printOriginLinesList($restrictlist = '', $selectedLines = array())
	{
		global $langs, $hookmanager, $form, $action;

		print '<tr class="liste_titre">';
		print '<td class="linecolref">'.$langs->trans('Ref').'</td>';
		print '<td class="linecoldescription">'.$langs->trans('Description').'</td>';
		print '<td class="linecolvat right">'.$langs->trans('VATRate').'</td>';
		print '<td class="linecoluht right">'.$langs->trans('PriceUHT').'</td>';
		if (isModEnabled("multicurrency")) {
			print '<td class="linecoluht_currency right">'.$langs->trans('PriceUHTCurrency').'</td>';
		}
		print '<td class="linecolqty right">'.$langs->trans('Qty').'</td>';
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			print '<td class="linecoluseunit left">'.$langs->trans('Unit').'</td>';
		}
		print '<td class="linecoldiscount right">'.$langs->trans('ReductionShort').'</td>';
		print '<td class="linecolht right">'.$langs->trans('TotalHT').'</td>';
		print '<td class="center">'.$form->showCheckAddButtons('checkforselect', 1).'</td>';
		print '</tr>';
		$i = 0;

		if (!empty($this->lines)) {
			foreach ($this->lines as $line) {
				$reshook = 0;
				//if (is_object($hookmanager) && (($line->product_type == 9 && !empty($line->special_code)) || !empty($line->fk_parent_line))) {
				if (is_object($hookmanager)) {   // Old code is commented on preceding line.
					$parameters = array('line' => $line, 'i' => $i, 'restrictlist' => $restrictlist, 'selectedLines' => $selectedLines);
					if (!empty($line->fk_parent_line)) {
						$parameters['fk_parent_line'] = $line->fk_parent_line;
					}
					$reshook = $hookmanager->executeHooks('printOriginObjectLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				}
				if (empty($reshook)) {
					$this->printOriginLine($line, '', $restrictlist, '/core/tpl', $selectedLines);
				}

				$i++;
			}
		}
	}

	/**
	 * 	Return HTML with a line of table array of source object lines
	 *  TODO Move this and previous function into output html class file (htmlline.class.php).
	 *  If lines are into a template, titles must also be into a template
	 *  But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
	 *
	 * 	@param	CommonObjectLine	$line				Line
	 * 	@param	string				$var				Not used
	 *	@param	string				$restrictlist		''=All lines, 'services'=Restrict to services only (strike line if not)
	 *  @param	string				$defaulttpldir		Directory where to find the template
	 *  @param  int[]       		$selectedLines      Array of lines id for selected lines
	 * 	@return	void
	 */
	public function printOriginLine($line, $var, $restrictlist = '', $defaulttpldir = '/core/tpl', $selectedLines = array())
	{
		global $langs, $conf;

		//var_dump($line);
		if (!empty($line->date_start)) {
			$date_start = $line->date_start;
		} else {
			$date_start = $line->date_debut_prevue;
			if ($line->date_debut_reel) {
				$date_start = $line->date_debut_reel;
			}
		}
		if (!empty($line->date_end)) {
			$date_end = $line->date_end;
		} else {
			$date_end = $line->date_fin_prevue;
			if ($line->date_fin_reel) {
				$date_end = $line->date_fin_reel;
			}
		}

		$this->tpl['id'] = $line->id;

		$this->tpl['label'] = '';
		if (!empty($line->fk_parent_line)) {
			$this->tpl['label'] .= img_picto('', 'rightarrow');
		}

		if (($line->info_bits & 2) == 2) {  // TODO Not sure this is used for source object
			$discount = new DiscountAbsolute($this->db);
			if (property_exists($this, 'socid')) {
				$discount->fk_soc = $this->socid;
				$discount->socid = $this->socid;
			}
			$this->tpl['label'] .= $discount->getNomUrl(0, 'discount');
		} elseif (!empty($line->fk_product)) {
			$productstatic = new Product($this->db);
			$productstatic->id = $line->fk_product;
			$productstatic->ref = $line->ref;
			$productstatic->type = $line->fk_product_type;
			if (empty($productstatic->ref)) {
				$line->fetch_product();
				$productstatic = $line->product;
			}

			$this->tpl['label'] .= $productstatic->getNomUrl(1);
			$this->tpl['label'] .= ' - '.(!empty($line->label) ? $line->label : $line->product_label);
			// Dates
			if ($line->product_type == 1 && ($date_start || $date_end)) {
				$this->tpl['label'] .= get_date_range($date_start, $date_end);
			}
		} else {
			$this->tpl['label'] .= ($line->product_type == -1 ? '&nbsp;' : ($line->product_type == 1 ? img_object($langs->trans(''), 'service') : img_object($langs->trans(''), 'product')));
			if (!empty($line->desc)) {
				$this->tpl['label'] .= $line->desc;
			} else {
				$this->tpl['label'] .= ($line->label ? '&nbsp;'.$line->label : '');
			}

			// Dates
			if ($line->product_type == 1 && ($date_start || $date_end)) {
				$this->tpl['label'] .= get_date_range($date_start, $date_end);
			}
		}

		if (!empty($line->desc)) {
			if ($line->desc == '(CREDIT_NOTE)') {  // TODO Not sure this is used for source object
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromCreditNote", $discount->getNomUrl(0));
			} elseif ($line->desc == '(DEPOSIT)') {  // TODO Not sure this is used for source object
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromDeposit", $discount->getNomUrl(0));
			} elseif ($line->desc == '(EXCESS RECEIVED)') {
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromExcessReceived", $discount->getNomUrl(0));
			} elseif ($line->desc == '(EXCESS PAID)') {
				$discount = new DiscountAbsolute($this->db);
				$discount->fetch($line->fk_remise_except);
				$this->tpl['description'] = $langs->transnoentities("DiscountFromExcessPaid", $discount->getNomUrl(0));
			} else {
				$this->tpl['description'] = dol_trunc($line->desc, 60);
			}
		} else {
			$this->tpl['description'] = '&nbsp;';
		}

		// VAT Rate
		$this->tpl['vat_rate'] = vatrate($line->tva_tx, true);
		$this->tpl['vat_rate'] .= (($line->info_bits & 1) == 1) ? '*' : '';
		if (!empty($line->vat_src_code) && !preg_match('/\(/', $this->tpl['vat_rate'])) {
			$this->tpl['vat_rate'] .= ' ('.$line->vat_src_code.')';
		}

		$this->tpl['price'] = price($line->subprice);
		$this->tpl['total_ht'] = price($line->total_ht);
		$this->tpl['multicurrency_price'] = price($line->multicurrency_subprice);
		$this->tpl['qty'] = (($line->info_bits & 2) != 2) ? $line->qty : '&nbsp;';
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$this->tpl['unit'] = $langs->transnoentities($line->getLabelOfUnit('long'));
		}
		$this->tpl['remise_percent'] = (($line->info_bits & 2) != 2) ? vatrate($line->remise_percent, true) : '&nbsp;';

		// Is the line strike or not
		$this->tpl['strike'] = 0;
		if ($restrictlist == 'services' && $line->product_type != Product::TYPE_SERVICE) {
			$this->tpl['strike'] = 1;
		}

		// Output template part (modules that overwrite templates must declare this into descriptor)
		// Use global variables + $dateSelector + $seller and $buyer
		$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir));
		foreach ($dirtpls as $module => $reldir) {
			if (!empty($module)) {
				$tpl = dol_buildpath($reldir.'/originproductline.tpl.php');
			} else {
				$tpl = DOL_DOCUMENT_ROOT.$reldir.'/originproductline.tpl.php';
			}

			if (empty($conf->file->strict_mode)) {
				$res = @include $tpl;
			} else {
				$res = include $tpl; // for debug
			}
			if ($res) {
				break;
			}
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Add resources to the current object : add entry into llx_element_resources
	 *	Need $this->element & $this->id
	 *
	 *	@param		int		$resource_id		Resource id
	 *	@param		string	$resource_type		'resource'
	 *	@param		int		$busy				Busy or not
	 *	@param		int		$mandatory			Mandatory or not
	 *	@return		int							Return integer <=0 if KO, >0 if OK
	 */
	public function add_element_resource($resource_id, $resource_type, $busy = 0, $mandatory = 0)
	{
		// phpcs:enable
		$this->db->begin();

		$sql = "INSERT INTO ".$this->db->prefix()."element_resources (";
		$sql .= "resource_id";
		$sql .= ", resource_type";
		$sql .= ", element_id";
		$sql .= ", element_type";
		$sql .= ", busy";
		$sql .= ", mandatory";
		$sql .= ") VALUES (";
		$sql .= ((int) $resource_id);
		$sql .= ", '".$this->db->escape($resource_type)."'";
		$sql .= ", '".$this->db->escape($this->id)."'";
		$sql .= ", '".$this->db->escape($this->element)."'";
		$sql .= ", '".$this->db->escape($busy)."'";
		$sql .= ", '".$this->db->escape($mandatory)."'";
		$sql .= ")";

		dol_syslog(get_class($this)."::add_element_resource", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return  0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Delete a link to resource line
	 *
	 *    @param	int		$rowid			Id of resource line to delete
	 *    @param	string	$element		element name (for trigger) TODO: use $this->element into commonobject class
	 *    @param	int		$notrigger		Disable all triggers
	 *    @return   int						>0 if OK, <0 if KO
	 */
	public function delete_resource($rowid, $element, $notrigger = 0)
	{
		// phpcs:enable
		global $user;

		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."element_resources";
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog(get_class($this)."::delete_resource", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		} else {
			if (!$notrigger) {
				$result = $this->call_trigger(strtoupper($element).'_DELETE_RESOURCE', $user);
				if ($result < 0) {
					$this->db->rollback();
					return -1;
				}
			}
			$this->db->commit();
			return 1;
		}
	}


	/**
	 * Overwrite magic function to solve problem of cloning object that are kept as references
	 *
	 * @return void
	 */
	public function __clone()
	{
		// Force a copy of this->lines, otherwise it will point to same object.
		if (isset($this->lines) && is_array($this->lines)) {
			$nboflines = count($this->lines);
			for ($i = 0; $i < $nboflines; $i++) {
				if (is_object($this->lines[$i])) {
					$this->lines[$i] = clone $this->lines[$i];
				}
			}
		}
	}

	/**
	 * Common function for all objects extending CommonObject for generating documents
	 *
	 * @param	string 		$modelspath 	Relative folder where generators are placed
	 * @param	string 		$modele 		Generator to use. Caller must set it to from obj->model_pdf or from GETPOST for example.
	 * @param	Translate 	$outputlangs 	Output language to use
	 * @param	int<0,1>	$hidedetails 	1 to hide details. 0 by default
	 * @param	int<0,1>	$hidedesc 		1 to hide product description. 0 by default
	 * @param	int<0,1>	$hideref 		1 to hide product reference. 0 by default
	 * @param	?array<string,mixed>	$moreparams	Array to provide more information
	 * @return	int<-1,1> 				>0 if OK, <0 if KO
	 * @see	addFileIntoDatabaseIndex()
	 */
	protected function commonGenerateDocument($modelspath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams = null)
	{
		global $conf, $langs, $user, $hookmanager, $action;

		$srctemplatepath = '';

		$parameters = array('modelspath' => $modelspath, 'modele' => $modele, 'outputlangs' => $outputlangs, 'hidedetails' => $hidedetails, 'hidedesc' => $hidedesc, 'hideref' => $hideref, 'moreparams' => $moreparams);
		$reshook = $hookmanager->executeHooks('commonGenerateDocument', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		if (!empty($reshook)) {
			return $reshook;
		}

		dol_syslog("commonGenerateDocument modele=".$modele." outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (empty($modele)) {
			$this->error = 'BadValueForParameterModele';
			return -1;
		}

		// Increase limit for PDF build
		$err = error_reporting();
		error_reporting(0);
		@set_time_limit(120);
		error_reporting($err);

		// If selected model is a filename template (then $modele="modelname" or "modelname:filename")
		$tmp = explode(':', $modele, 2);
		$saved_model = $modele;
		if (!empty($tmp[1])) {
			$modele = $tmp[0];
			$srctemplatepath = $tmp[1];
		}

		// Search template files
		$file = '';
		$classname = '';
		$filefound = '';
		$dirmodels = array('/');
		if (is_array($conf->modules_parts['models'])) {
			$dirmodels = array_merge($dirmodels, $conf->modules_parts['models']);
		}
		foreach ($dirmodels as $reldir) {
			foreach (array('doc', 'pdf') as $prefix) {
				if (in_array(get_class($this), array('Adherent'))) {
					// Member module use prefix_modele.class.php
					$file = $prefix."_".$modele.".class.php";
				} else {
					// Other module use prefix_modele.modules.php
					$file = $prefix."_".$modele.".modules.php";
				}

				$file = dol_sanitizeFileName($file);

				// We check if the file exists
				$file = dol_buildpath($reldir.$modelspath.$file, 0);
				if (file_exists($file)) {
					$filefound = $file;
					$classname = $prefix.'_'.$modele;
					break;
				}
			}
			if ($filefound) {
				break;
			}
		}

		if ($filefound === '' || $classname === '') {
			$this->error = $langs->trans("Error").' Failed to load doc generator with modelpaths='.$modelspath.' - modele='.$modele;
			$this->errors[] = $this->error;
			dol_syslog($this->error, LOG_ERR);
			return -1;
		}

		// Sanitize $filefound
		$filefound = dol_sanitizePathName($filefound);

		// If generator was found
		global $db; // Required to solve a conception error making an include of some code that uses $db instead of $this->db just after.

		require_once $filefound;

		$obj = new $classname($this->db);

		// TODO: Check the following classes that seem possible for $obj, but removed for compatibility:
		//  ModeleBankAccountDoc|ModeleExpenseReport|ModelePDFBom|ModelePDFCommandes|ModelePDFContract|
		//  ModelePDFDeliveryOrder|ModelePDFEvaluation|ModelePDFFactures|ModelePDFFicheinter|
		//  ModelePDFMo|ModelePDFMovement|ModelePDFProduct|ModelePDFProjects|ModelePDFPropales|
		//  ModelePDFRecruitmentJobPosition|ModelePDFSupplierProposal|ModelePDFSuppliersInvoices|
		//  ModelePDFSuppliersOrders|ModelePDFSuppliersPayments|ModelePdfExpedition|ModelePdfReception|
		//  ModelePDFStock|ModelePDFStockTransfer|
		//  ModeleDon|ModelePDFTask|
		//  ModelePDFAsset|ModelePDFTicket|ModelePDFUserGroup|ModeleThirdPartyDoc|ModelePDFUser
		//  Has no write_file: ModeleBarCode|ModeleImports|ModeleExports|
		'@phan-var-force ModelePDFMember $obj';
		// '@phan-var-force ModelePDFMember|ModeleBarCode|ModeleDon|ModeleExports|ModeleImports|ModelePDFAsset|ModelePDFContract|ModelePDFDeliveryOrder|ModelePDFEvaluation|ModelePDFFactures|ModelePDFFicheinter|ModelePDFMo|ModelePDFMovement|ModelePDFProduct|ModelePDFProjects|ModelePDFPropales|ModelePDFRecruitmentJobPosition|ModelePDFStock|ModelePDFStockTransfer|ModelePDFSupplierProposal|ModelePDFSuppliersInvoices|ModelePDFSuppliersOrders|ModelePDFSuppliersPayments|ModelePDFTask|ModelePDFTicket|ModelePDFUser|ModelePDFUserGroup|ModelePdfExpedition|ModelePdfReception|ModeleThirdPartyDoc $obj';

		// If generator is ODT, we must have srctemplatepath defined, if not we set it.
		if ($obj->type == 'odt' && empty($srctemplatepath)) {
			$varfortemplatedir = $obj->scandir;
			if ($varfortemplatedir && getDolGlobalString($varfortemplatedir)) {
				$dirtoscan = getDolGlobalString($varfortemplatedir);

				$listoffiles = array();

				// Now we add first model found in directories scanned
				$listofdir = explode(',', $dirtoscan);
				foreach ($listofdir as $key => $tmpdir) {
					$tmpdir = trim($tmpdir);
					$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
					if (!$tmpdir) {
						unset($listofdir[$key]);
						continue;
					}
					if (is_dir($tmpdir)) {
						$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.od(s|t)$', '', 'name', SORT_ASC, 0);
						if (count($tmpfiles)) {
							$listoffiles = array_merge($listoffiles, $tmpfiles);
						}
					}
				}

				if (count($listoffiles)) {
					foreach ($listoffiles as $record) {
						$srctemplatepath = $record['fullname'];
						break;
					}
				}
			}

			if (empty($srctemplatepath)) {
				$this->error = 'ErrorGenerationAskedForOdtTemplateWithSrcFileNotDefined';
				return -1;
			}
		}

		if ($obj->type == 'odt' && !empty($srctemplatepath)) {
			if (!dol_is_file($srctemplatepath)) {
				dol_syslog("Failed to locate template file ".$srctemplatepath, LOG_WARNING);
				$this->error = 'ErrorGenerationAskedForOdtTemplateWithSrcFileNotFound';
				return -1;
			}
		}

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output = empty($outputlangs->charset_output) ? '' : $outputlangs->charset_output;

		// update model_pdf in object
		$this->model_pdf = $saved_model;

		if ($obj instanceof ModelePDFMember) {
			$resultwritefile = $obj->write_file($this, $outputlangs, $srctemplatepath, 'member', 1, 'tmp_cards');
		} else {
			$resultwritefile = $obj->write_file($this, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $moreparams);
		}
		// After call of write_file $obj->result['fullpath'] is set with generated file. It will be used to update the ECM database index.

		if ($resultwritefile > 0) {
			$outputlangs->charset_output = $sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($this);

			// Index file in database
			if (!empty($obj->result['fullpath'])) {
				$destfull = $obj->result['fullpath'];

				// Update the last_main_doc field into main object (if document generator has property ->update_main_doc_field set)
				$update_main_doc_field = 0;
				if (!empty($obj->update_main_doc_field)) {
					$update_main_doc_field = 1;
				}

				// Check that the file exists, before indexing it.
				// Hint: It does not exist, if we create a PDF and auto delete the ODT File
				if (dol_is_file($destfull)) {
					$this->indexFile($destfull, $update_main_doc_field);
				}
			} else {
				dol_syslog('Method ->write_file was called on object '.get_class($obj).' and return a success but the return array ->result["fullpath"] was not set.', LOG_WARNING);
			}

			// Success in building document. We build meta file.
			dol_meta_create($this);

			return 1;
		} else {
			$outputlangs->charset_output = $sav_charset_output;
			$this->error = $obj->error;
			$this->errors = $obj->errors;
			dol_syslog("Error generating document for ".__CLASS__.". Error: ".$obj->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Index a file into the ECM database
	 *
	 * @param	string	$destfull				Full path of file to index
	 * @param	int		$update_main_doc_field	Update field main_doc field into the table of the object.
	 * 											This param is set when called for a document generation if document generator hase
	 * 											->update_main_doc_field set and returns ->result['fullpath'].
	 * @return	int								Return integer <0 if KO, >0 if OK
	 */
	public function indexFile($destfull, $update_main_doc_field)
	{
		global $conf, $user;

		$upload_dir = dirname($destfull);
		$destfile = basename($destfull);
		$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dir);

		if (!preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $rel_dir)) {     // If not a tmp dir
			$filename = basename($destfile);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

			include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
			$ecmfile = new EcmFiles($this->db);
			$result = $ecmfile->fetch(0, '', ($rel_dir ? $rel_dir.'/' : '').$filename);

			// Set the public "share" key
			$setsharekey = false;
			if ($this->element == 'propal' || $this->element == 'proposal') {
				if (getDolGlobalInt("PROPOSAL_ALLOW_ONLINESIGN")) {
					$setsharekey = true;	// feature to make online signature is not set or set to on (default)
				}
				if (getDolGlobalInt("PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD")) {
					$setsharekey = true;
				}
			}
			if ($this->element == 'commande' && getDolGlobalInt("ORDER_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'facture' && getDolGlobalInt("INVOICE_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'bank_account' && getDolGlobalInt("BANK_ACCOUNT_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'product' && getDolGlobalInt("PRODUCT_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'contrat' && getDolGlobalInt("CONTRACT_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'fichinter' && getDolGlobalInt("FICHINTER_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'supplier_proposal' && getDolGlobalInt("SUPPLIER_PROPOSAL_ALLOW_EXTERNAL_DOWNLOAD")) {
				$setsharekey = true;
			}
			if ($this->element == 'societe_rib' && getDolGlobalInt("SOCIETE_RIB_ALLOW_ONLINESIGN")) {
				$setsharekey = true;
			}

			if ($setsharekey) {
				if (empty($ecmfile->share)) {	// Because object not found or share not set yet
					require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
					$ecmfile->share = getRandomPassword(true);
				}
			}

			if ($result > 0) {
				$ecmfile->label = md5_file(dol_osencode($destfull)); // hash of file content
				$ecmfile->fullpath_orig = '';
				$ecmfile->gen_or_uploaded = 'generated';
				$ecmfile->description = ''; // indexed content
				$ecmfile->keywords = ''; // keyword content
				$result = $ecmfile->update($user);
				if ($result < 0) {
					setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					return -1;
				}
			} else {
				$ecmfile->entity = $conf->entity;
				$ecmfile->filepath = $rel_dir;
				$ecmfile->filename = $filename;
				$ecmfile->label = md5_file(dol_osencode($destfull)); // hash of file content
				$ecmfile->fullpath_orig = '';
				$ecmfile->gen_or_uploaded = 'generated';
				$ecmfile->description = ''; // indexed content
				$ecmfile->keywords = ''; // keyword content
				$ecmfile->src_object_type = $this->table_element;	// $this->table_name is 'myobject' or 'mymodule_myobject'.
				$ecmfile->src_object_id   = $this->id;

				$result = $ecmfile->create($user);
				if ($result < 0) {
					setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					return -1;
				}
			}

			/*$this->result['fullname']=$destfull;
			 $this->result['filepath']=$ecmfile->filepath;
			 $this->result['filename']=$ecmfile->filename;*/
			//var_dump($obj->update_main_doc_field);exit;

			if ($update_main_doc_field && !empty($this->table_element)) {
				$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET last_main_doc = '".$this->db->escape($ecmfile->filepath."/".$ecmfile->filename)."'";
				$sql .= " WHERE rowid = ".((int) $this->id);

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_print_error($this->db);
					return -1;
				} else {
					$this->last_main_doc = $ecmfile->filepath.'/'.$ecmfile->filename;
				}
			}
		}

		return 1;
	}

	/**
	 *  Build thumb
	 *  @todo Move this into files.lib.php
	 *
	 *  @param      string	$file           Path file in UTF8 to original file to create thumbs from.
	 *	@return		void
	 */
	public function addThumbs($file)
	{
		$file_osencoded = dol_osencode($file);

		if (file_exists($file_osencoded)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

			$tmparraysize = getDefaultImageSizes();
			$maxwidthsmall = $tmparraysize['maxwidthsmall'];
			$maxheightsmall = $tmparraysize['maxheightsmall'];
			$maxwidthmini = $tmparraysize['maxwidthmini'];
			$maxheightmini = $tmparraysize['maxheightmini'];
			//$quality = $tmparraysize['quality'];
			$quality = 50;	// For thumbs, we force quality to 50

			// Create small thumbs for company (Ratio is near 16/9)
			// Used on logon for example
			vignette($file_osencoded, $maxwidthsmall, $maxheightsmall, '_small', $quality);

			// Create mini thumbs for company (Ratio is near 16/9)
			// Used on menu or for setup page for example
			vignette($file_osencoded, $maxwidthmini, $maxheightmini, '_mini', $quality);
		}
	}

	/**
	 *  Delete thumbs
	 *  @todo Move this into files.lib.php
	 *
	 *  @param      string	$file           Path file in UTF8 to original file to delete thumbs.
	 *	@return		void
	 */
	public function delThumbs($file)
	{
		$imgThumbName = getImageFileNameForSize($file, '_small'); // Full path of thumb file
		dol_delete_file($imgThumbName);
		$imgThumbName = getImageFileNameForSize($file, '_mini'); // Full path of thumb file
		dol_delete_file($imgThumbName);
	}


	/* Functions common to commonobject and commonobjectline */

	/* For default values */

	/**
	 * Return the default value to use for a field when showing the create form of object.
	 * Return values in this order:
	 * 1) If parameter is available into POST, we return it first.
	 * 2) If not but an alternate value was provided as parameter of function, we return it.
	 * 3) If not but a constant $conf->global->OBJECTELEMENT_FIELDNAME is set, we return it (It is better to use the dedicated table).
	 * 4) Return value found into database (TODO No yet implemented)
	 *
	 * @param   string              $fieldname          Name of field
	 * @param   string              $alternatevalue     Alternate value to use
	 * @param   string              $type    			Type of data
	 * @return  string|string[]                         Default value (can be an array if the GETPOST return an array)
	 **/
	public function getDefaultCreateValueFor($fieldname, $alternatevalue = null, $type = 'alphanohtml')
	{
		// If param here has been posted, we use this value first.
		if (GETPOSTISSET($fieldname)) {
			return GETPOST($fieldname, $type, 3);
		}

		if (isset($alternatevalue)) {
			return $alternatevalue;
		}

		$newelement = $this->element;
		if ($newelement == 'facture') {
			$newelement = 'invoice';
		}
		if ($newelement == 'commande') {
			$newelement = 'order';
		}
		if (empty($newelement)) {
			dol_syslog("Ask a default value using common method getDefaultCreateValueForField on an object with no property ->element defined. Return empty string.", LOG_WARNING);
			return '';
		}

		$keyforfieldname = strtoupper($newelement.'_DEFAULT_'.$fieldname);
		//var_dump($keyforfieldname);
		if (getDolGlobalString($keyforfieldname)) {
			return getDolGlobalString($keyforfieldname);
		}

		// TODO Ad here a scan into table llx_overwrite_default with a filter on $this->element and $fieldname
		// store content into $conf->cache['overwrite_default']

		return '';
	}


	/* For triggers */


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Call trigger based on this instance.
	 * Some context information may also be provided into array property this->context.
	 * NB:  Error from trigger are stacked in interface->errors
	 * NB2: If return code of triggers are < 0, action calling trigger should cancel all transaction.
	 *
	 * @param   string    $triggerName   trigger's name to execute
	 * @param   User      $user           Object user
	 * @return  int                       Result of run_triggers
	 */
	public function call_trigger($triggerName, $user)
	{
		// phpcs:enable
		global $langs, $conf;

		if (!empty(self::TRIGGER_PREFIX) && strpos($triggerName, self::TRIGGER_PREFIX . '_') !== 0) {
			dol_print_error(null, 'The trigger "' . $triggerName . '" does not start with "' . self::TRIGGER_PREFIX . '_" as required.');
			exit;
		}
		if (!is_object($langs)) {	// If lang was not defined, we set it. It is required by run_triggers().
			include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';
			$langs = new Translate('', $conf);
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
		$interface = new Interfaces($this->db);
		$result = $interface->run_triggers($triggerName, $this, $user, $langs, $conf);

		if ($result < 0) {
			if (!empty($this->errors)) {
				$this->errors = array_unique(array_merge($this->errors, $interface->errors)); // We use array_unique because when a trigger call another trigger on same object, this->errors is added twice.
			} else {
				$this->errors = $interface->errors;
			}
		}
		return $result;
	}


	/* Functions for data in other language */


	/**
	 *  Function to get alternative languages of a data into $this->array_languages
	 *  This method is NOT called by method fetch of objects but must be called separately.
	 *
	 *  @return	int<-1,1>					Return integer <0 if error, 0 if no values of alternative languages to find nor found, 1 if a value was found and loaded
	 *  @see fetch_optionnals()
	 */
	public function fetchValuesForExtraLanguages()
	{
		// To avoid SQL errors. Probably not the better solution though
		if (!$this->element) {
			return 0;
		}
		if (!($this->id > 0)) {
			return 0;
		}
		if (is_array($this->array_languages)) {
			return 1;
		}

		$this->array_languages = array();

		$element = $this->element;
		if ($element == 'categorie') {
			$element = 'categories'; // For compatibility
		}

		// Request to get translation values for object
		$sql = "SELECT rowid, property, lang , value";
		$sql .= " FROM ".$this->db->prefix()."object_lang";
		$sql .= " WHERE type_object = '".$this->db->escape($element)."'";
		$sql .= " AND fk_object = ".((int) $this->id);

		//dol_syslog(get_class($this)."::fetch_optionals get extrafields data for ".$this->table_element, LOG_DEBUG);		// Too verbose
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$i = 0;
				while ($i < $numrows) {
					$obj = $this->db->fetch_object($resql);
					$key = $obj->property;
					$value = $obj->value;
					$codelang = $obj->lang;
					$type = $this->fields[$key]['type'];

					// we can add this attribute to object
					if (preg_match('/date/', $type)) {
						$this->array_languages[$key][$codelang] = $this->db->jdate($value);
					} else {
						$this->array_languages[$key][$codelang] = $value;
					}

					$i++;
				}
			}

			$this->db->free($resql);

			if ($numrows) {
				return $numrows;
			} else {
				return 0;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Fill array_options property of object by extrafields value (using for data sent by forms)
	 *
	 * @param	string	$onlykey		Only the following key is filled. When we make update of only one language field ($action = 'update_languages'), calling page must set this to avoid to have other languages being reset.
	 * @return	int<-1,1>				1 if array_options set, 0 if no value, -1 if error (field required missing for example)
	 */
	public function setValuesForExtraLanguages($onlykey = '')
	{
		// Get extra fields
		foreach ($_POST as $postfieldkey => $postfieldvalue) {
			$tmparray = explode('-', $postfieldkey);
			if ($tmparray[0] != 'field') {
				continue;
			}

			$element = $tmparray[1];
			$key = $tmparray[2];
			$codelang = $tmparray[3];
			//var_dump("postfieldkey=".$postfieldkey." element=".$element." key=".$key." codelang=".$codelang);

			if (!empty($onlykey) && $key != $onlykey) {
				continue;
			}
			if ($element != $this->element) {
				continue;
			}

			$key_type = $this->fields[$key]['type'];

			$enabled = 1;
			if (isset($this->fields[$key]['enabled'])) {
				$enabled = (int) dol_eval($this->fields[$key]['enabled'], 1, 1, '1');
			}
			/*$perms = 1;
			if (isset($this->fields[$key]['perms']))
			{
				$perms = (int) dol_eval($this->fields[$key]['perms'], 1, 1, '1');
			}*/
			if (empty($enabled)) {
				continue;
			}
			//if (empty($perms)) continue;

			if (in_array($key_type, array('date'))) {
				// Clean parameters
				// TODO GMT date in memory must be GMT so we should add gm=true in parameters
				$value_key = dol_mktime(0, 0, 0, GETPOSTINT($postfieldkey."month"), GETPOSTINT($postfieldkey."day"), GETPOSTINT($postfieldkey."year"));
			} elseif (in_array($key_type, array('datetime'))) {
				// Clean parameters
				// TODO GMT date in memory must be GMT so we should add gm=true in parameters
				$value_key = dol_mktime(GETPOSTINT($postfieldkey."hour"), GETPOSTINT($postfieldkey."min"), 0, GETPOSTINT($postfieldkey."month"), GETPOSTINT($postfieldkey."day"), GETPOSTINT($postfieldkey."year"));
			} elseif (in_array($key_type, array('checkbox', 'chkbxlst'))) {
				$value_arr = GETPOST($postfieldkey, 'array'); // check if an array
				if (!empty($value_arr)) {
					$value_key = implode(',', $value_arr);
				} else {
					$value_key = '';
				}
			} elseif (in_array($key_type, array('price', 'double'))) {
				$value_arr = GETPOST($postfieldkey, 'alpha');
				$value_key = price2num($value_arr);
			} else {
				$value_key = GETPOST($postfieldkey);
				if (in_array($key_type, array('link')) && $value_key == '-1') {
					$value_key = '';
				}
			}

			$this->array_languages[$key][$codelang] = $value_key;

			/*if ($nofillrequired) {
				$langs->load('errors');
				setEventMessages($langs->trans('ErrorFieldsRequired').' : '.implode(', ', $error_field_required), null, 'errors');
				return -1;
			}*/
		}

		return 1;
	}


	/* Functions for extrafields */

	/**
	 * Function to make a fetch but set environment to avoid to load computed values before.
	 *
	 * @param	int		$id			ID of object
	 * @return	int<-1,1>			>0 if OK, 0 if not found, <0 if KO
	 */
	public function fetchNoCompute($id)
	{
		global $conf;

		$savDisableCompute = $conf->disable_compute;
		$conf->disable_compute = 1;

		$ret = $this->fetch($id);	/* @phpstan-ignore-line */

		$conf->disable_compute = $savDisableCompute;

		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to get extra fields of an object into $this->array_options
	 *  This method is in most cases called by method fetch of objects but you can call it separately.
	 *
	 *  @param	int		$rowid			Id of line. Use the id of object if not defined. Deprecated. Function must be called without parameters.
	 *  @param array{}|array{label:array<string,string>,type:array<string,string>,size:array<string,string>,default:array<string,string>,computed:array<string,string>,unique:array<string,int>,required:array<string,int>,param:array<string,mixed>,perms:array<string,mixed[]>,list:array<string,int>|array<string,string>,pos:array<string,int>,totalizable:array<string,int>,help:array<string,string>,printable:array<string,int>,enabled:array<string,int>,langfile:array<string,string>,css:array<string,string>,csslist:array<string,string>,hidden:array<string,int>,mandatoryfieldsofotherentities?:array<string,string>,loaded?:int,count:int}		$optionsArray	Array resulting of call of extrafields->fetch_name_optionals_label(). Deprecated. Function must be called without parameters.
	 *  @return	int<-1,1>				Return integer <0 if error, 0 if no values of extrafield to find nor found, 1 if an attribute is found and value loaded
	 *  @see fetchValuesForExtraLanguages()
	 */
	public function fetch_optionals($rowid = null, $optionsArray = null)
	{
		// phpcs:enable
		global $conf, $extrafields;

		if (empty($rowid)) {
			$rowid = $this->id;
		}
		if (empty($rowid) && isset($this->rowid)) {
			$rowid = $this->rowid; // deprecated
		}

		// To avoid SQL errors. Probably not the better solution though
		if (!$this->table_element) {
			return 0;
		}

		$this->array_options = array();

		if (!is_array($optionsArray)) {
			// If $extrafields is not a known object, we initialize it. Best practice is to have $extrafields defined into card.php or list.php page.
			if (!isset($extrafields) || !is_object($extrafields)) {
				require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
				$extrafields = new ExtraFields($this->db);
			}

			// Load array of extrafields for elementype = $this->table_element
			if (empty($extrafields->attributes[$this->table_element]['loaded'])) {
				$extrafields->fetch_name_optionals_label($this->table_element);
			}
			$optionsArray = (!empty($extrafields->attributes[$this->table_element]['label']) ? $extrafields->attributes[$this->table_element]['label'] : null);
		} else {
			global $extrafields;
			dol_syslog("Warning: fetch_optionals was called with param optionsArray defined when you should pass null now", LOG_WARNING);
		}

		$table_element = $this->table_element;
		if ($table_element == 'categorie') {
			$table_element = 'categories'; // For compatibility
		}

		// Request to get complementary values
		if (is_array($optionsArray) && count($optionsArray) > 0) {
			$sql = "SELECT rowid";
			foreach ($optionsArray as $name => $label) {
				if (empty($extrafields->attributes[$this->table_element]['type'][$name]) || (!in_array($extrafields->attributes[$this->table_element]['type'][$name], ['separate', 'point', 'multipts', 'linestrg','polygon']))) {
					$sql .= ", ".$name;
				}
				// use geo sql fonction to read as text
				if (empty($extrafields->attributes[$this->table_element]['type'][$name]) || $extrafields->attributes[$this->table_element]['type'][$name] == 'point') {
					$sql .= ", ST_AsWKT(".$name.") as ".$name;
				}
				if (empty($extrafields->attributes[$this->table_element]['type'][$name]) || $extrafields->attributes[$this->table_element]['type'][$name] == 'multipts') {
					$sql .= ", ST_AsWKT(".$name.") as ".$name;
				}
				if (empty($extrafields->attributes[$this->table_element]['type'][$name]) || $extrafields->attributes[$this->table_element]['type'][$name] == 'linestrg') {
					$sql .= ", ST_AsWKT(".$name.") as ".$name;
				}
				if (empty($extrafields->attributes[$this->table_element]['type'][$name]) || $extrafields->attributes[$this->table_element]['type'][$name] == 'polygon') {
					$sql .= ", ST_AsWKT(".$name.") as ".$name;
				}
			}
			$sql .= " FROM ".$this->db->prefix().$table_element."_extrafields";
			$sql .= " WHERE fk_object = ".((int) $rowid);

			//dol_syslog(get_class($this)."::fetch_optionals get extrafields data for ".$this->table_element, LOG_DEBUG);		// Too verbose
			$resql = $this->db->query($sql);
			if ($resql) {
				$numrows = $this->db->num_rows($resql);
				if ($numrows) {
					$tab = $this->db->fetch_array($resql);

					foreach ($tab as $key => $value) {
						// Test fetch_array ! is_int($key) because fetch_array result is a mix table with Key as alpha and Key as int (depend db engine)
						if ($key != 'rowid' && $key != 'tms' && $key != 'fk_member' && !is_int($key)) {
							// we can add this attribute to object
							if (!empty($extrafields->attributes[$this->table_element]) && in_array($extrafields->attributes[$this->table_element]['type'][$key], array('date', 'datetime'))) {
								//var_dump($extrafields->attributes[$this->table_element]['type'][$key]);
								$this->array_options["options_".$key] = $this->db->jdate($value);
							} else {
								$this->array_options["options_".$key] = $value;
							}

							//var_dump('key '.$key.' '.$value.' type='.$extrafields->attributes[$this->table_element]['type'][$key].' '.$this->array_options["options_".$key]);
						}
						if (!empty($extrafields->attributes[$this->table_element]['type'][$key]) && $extrafields->attributes[$this->table_element]['type'][$key] == 'password') {
							if (!empty($value) && preg_match('/^dolcrypt:/', $value)) {
								$this->array_options["options_".$key] = dolDecrypt($value);
							}
						}
					}
				} else {
					/**
					We are in a situation where the current object has no values in its extra fields.
					We want to initialize all the values to null so that the array_option is accessible in other contexts (especially in document generation).
					 **/
					if (is_array($extrafields->attributes[$this->table_element]['label'])) {
						foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
							$this->array_options['options_' . $key] = null;
						}
					}
				}

				// If field is a computed field, value must become result of compute (regardless of whether a row exists
				// in the element's extrafields table)
				if (is_array($extrafields->attributes[$this->table_element]['label'])) {
					foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
						if (!empty($extrafields->attributes[$this->table_element]) && !empty($extrafields->attributes[$this->table_element]['computed'][$key])) {
							//var_dump($conf->disable_compute);
							if (empty($conf->disable_compute)) {
								global $objectoffield;        // We set a global variable to $objectoffield so
								$objectoffield = $this;        // we can use it inside computed formula
								$this->array_options['options_' . $key] = dol_eval($extrafields->attributes[$this->table_element]['computed'][$key], 1, 0, '2');
							}
						}
					}
				}

				$this->db->free($resql);

				if ($numrows) {
					return $numrows;
				} else {
					return 0;
				}
			} else {
				$this->errors[] = $this->db->lasterror;
				return -1;
			}
		}
		return 0;
	}

	/**
	 *	Delete all extra fields values for the current object.
	 *
	 *  @return	int<-1,1>	Return integer <0 if KO, >0 if OK
	 *  @see deleteExtraLanguages(), insertExtraField(), updateExtraField(), setValueFrom()
	 */
	public function deleteExtraFields()
	{
		global $conf;

		if (getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
			return 0;
		}

		$this->db->begin();

		$table_element = $this->table_element;
		if ($table_element == 'categorie') {
			$table_element = 'categories'; // For compatibility
		}

		dol_syslog(get_class($this)."::deleteExtraFields delete", LOG_DEBUG);

		$sql_del = "DELETE FROM ".$this->db->prefix().$table_element."_extrafields WHERE fk_object = ".((int) $this->id);

		$resql = $this->db->query($sql_del);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Add/Update all extra fields values for the current object.
	 *  Data to describe values to insert/update are stored into $this->array_options=array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
	 *  This function delete record with all extrafields and insert them again from the array $this->array_options.
	 *
	 *  @param	string		$trigger		If defined, call also the trigger (for example COMPANY_MODIFY)
	 *  @param	User		$userused		Object user
	 *  @return int<-1,1>					-1=error, O=did nothing, 1=OK
	 *  @see insertExtraLanguages(), updateExtraField(), deleteExtraField(), setValueFrom()
	 */
	public function insertExtraFields($trigger = '', $userused = null)
	{
		global $conf, $langs, $user;

		if (getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
			return 0;
		}

		if (empty($userused)) {
			$userused = $user;
		}

		$error = 0;

		if (!empty($this->array_options)) {
			// Check parameters
			$langs->load('admin');
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			$target_extrafields = $extrafields->fetch_name_optionals_label($this->table_element);

			// Eliminate copied source object extra fields that do not exist in target object
			$new_array_options = array();
			foreach ($this->array_options as $key => $value) {
				if (in_array(substr($key, 8), array_keys($target_extrafields))) {	// We remove the 'options_' from $key for test
					$new_array_options[$key] = $value;
				} elseif (in_array($key, array_keys($target_extrafields))) {		// We test on $key that does not contain the 'options_' prefix
					$new_array_options['options_'.$key] = $value;
				}
			}

			foreach ($new_array_options as $key => $value) {
				$attributeKey      = substr($key, 8); // Remove 'options_' prefix
				$attributeType     = $extrafields->attributes[$this->table_element]['type'][$attributeKey];
				$attributeLabel    = $langs->transnoentities($extrafields->attributes[$this->table_element]['label'][$attributeKey]);
				$attributeParam    = $extrafields->attributes[$this->table_element]['param'][$attributeKey];
				$attributeRequired = $extrafields->attributes[$this->table_element]['required'][$attributeKey];
				$attributeUnique   = $extrafields->attributes[$this->table_element]['unique'][$attributeKey];
				$attrfieldcomputed = $extrafields->attributes[$this->table_element]['computed'][$attributeKey];

				// If we clone, we have to clean unique extrafields to prevent duplicates.
				// This behaviour can be prevented by external code by changing $this->context['createfromclone'] value in createFrom hook
				if (!empty($this->context['createfromclone']) && $this->context['createfromclone'] == 'createfromclone' && !empty($attributeUnique)) {
					$new_array_options[$key] = null;
				}

				// Similar code than into insertExtraFields
				if ($attributeRequired) {
					$v = $this->array_options[$key];
					if (ExtraFields::isEmptyValue($v, $attributeType)) {
						$langs->load("errors");
						dol_syslog("Mandatory field '".$key."' is empty during create and set to required into definition of extrafields");
						$this->errors[] = $langs->trans('ErrorFieldRequired', $attributeLabel);
						return -1;
					}
				}

				//dol_syslog("attributeLabel=".$attributeLabel, LOG_DEBUG);
				//dol_syslog("attributeType=".$attributeType, LOG_DEBUG);

				if (!empty($attrfieldcomputed)) {
					if (getDolGlobalString('MAIN_STORE_COMPUTED_EXTRAFIELDS')) {
						$value = dol_eval($attrfieldcomputed, 1, 0, '2');
						dol_syslog($langs->trans("Extrafieldcomputed")." on ".$attributeLabel."(".$value.")", LOG_DEBUG);
						$new_array_options[$key] = $value;
					} else {
						$new_array_options[$key] = null;
					}
				}

				switch ($attributeType) {
					case 'int':
						if (!is_numeric($value) && $value != '') {
							$this->errors[] = $langs->trans("ExtraFieldHasWrongValue", $attributeLabel);
							return -1;
						} elseif ($value == '') {
							$new_array_options[$key] = null;
						}
						break;
					case 'price':
					case 'double':
						$value = price2num($value);
						if (!is_numeric($value) && $value != '') {
							dol_syslog($langs->trans("ExtraFieldHasWrongValue")." for ".$attributeLabel."(".$value."is not '".$attributeType."')", LOG_DEBUG);
							$this->errors[] = $langs->trans("ExtraFieldHasWrongValue", $attributeLabel);
							return -1;
						} elseif ($value == '') {
							$value = null;
						}
						//dol_syslog("double value"." on ".$attributeLabel."(".$value." is '".$attributeType."')", LOG_DEBUG);
						$new_array_options[$key] = $value;
						break;
						/*case 'select':	// Not required, we chose value='0' for undefined values
							 if ($value=='-1')
							 {
								 $this->array_options[$key] = null;
							 }
							 break;*/
					case 'password':
						$algo = '';
						if ($this->array_options[$key] != '' && is_array($extrafields->attributes[$this->table_element]['param'][$attributeKey]['options'])) {
							// If there is an encryption choice, we use it to encrypt data before insert
							$tmparrays = array_keys($extrafields->attributes[$this->table_element]['param'][$attributeKey]['options']);
							$algo = reset($tmparrays);
							if ($algo != '') {
								//global $action;		// $action may be 'create', 'update', 'update_extras'...
								//var_dump($action);
								//var_dump($this->oldcopy);exit;
								if (is_object($this->oldcopy)) {	// If this->oldcopy is not defined, we can't know if we change attribute or not, so we must keep value
									//var_dump('algo='.$algo.' '.$this->oldcopy->array_options[$key].' -> '.$this->array_options[$key]);
									if (isset($this->oldcopy->array_options[$key]) && $this->array_options[$key] == $this->oldcopy->array_options[$key]) {
										// If old value encrypted in database is same than submitted new value, it means we don't change it, so we don't update.
										if ($algo == 'dolcrypt') {	// dolibarr reversible encryption
											if (!preg_match('/^dolcrypt:/', $this->array_options[$key])) {
												$new_array_options[$key] = dolEncrypt($this->array_options[$key]);	// warning, must be called when on the master
											} else {
												$new_array_options[$key] = $this->array_options[$key]; // Value is kept
											}
										} else {
											$new_array_options[$key] = $this->array_options[$key]; // Value is kept
										}
									} else {
										// If value has changed
										if ($algo == 'dolcrypt') {	// dolibarr reversible encryption
											if (!preg_match('/^dolcrypt:/', $this->array_options[$key])) {
												$new_array_options[$key] = dolEncrypt($this->array_options[$key]);	// warning, must be called when on the master
											} else {
												$new_array_options[$key] = $this->array_options[$key]; // Value is kept
											}
										} else {
											$new_array_options[$key] = dol_hash($this->array_options[$key], $algo);
										}
									}
								} else {
									//var_dump('jjj'.$algo.' '.$this->oldcopy->array_options[$key].' -> '.$this->array_options[$key]);
									// If this->oldcopy is not defined, we can't know if we change attribute or not, so we must keep value
									if ($algo == 'dolcrypt' && !preg_match('/^dolcrypt:/', $this->array_options[$key])) {	// dolibarr reversible encryption
										$new_array_options[$key] = dolEncrypt($this->array_options[$key]);	// warning, must be called when on the master
									} else {
										$new_array_options[$key] = $this->array_options[$key]; // Value is kept
									}
								}
							} else {
								// No encryption
								$new_array_options[$key] = $this->array_options[$key]; // Value is kept
							}
						} else { // Common usage
							$new_array_options[$key] = $this->array_options[$key]; // Value is kept
						}
						break;
					case 'date':
					case 'datetime':
						// If data is a string instead of a timestamp, we convert it
						if (!is_numeric($this->array_options[$key]) || $this->array_options[$key] != intval($this->array_options[$key])) {
							$this->array_options[$key] = strtotime($this->array_options[$key]);
						}
						$new_array_options[$key] = $this->db->idate($this->array_options[$key]);
						break;
					case 'datetimegmt':
						// If data is a string instead of a timestamp, we convert it
						if (!is_numeric($this->array_options[$key]) || $this->array_options[$key] != intval($this->array_options[$key])) {
							$this->array_options[$key] = strtotime($this->array_options[$key]);
						}
						$new_array_options[$key] = $this->db->idate($this->array_options[$key], 'gmt');
						break;
					case 'link':
						$param_list = array_keys($attributeParam['options']);
						// 0 : ObjectName
						// 1 : classPath
						$InfoFieldList = explode(":", $param_list[0]);
						dol_include_once($InfoFieldList[1]);
						if ($InfoFieldList[0] && class_exists($InfoFieldList[0])) {
							if ($value == '-1') {	// -1 is key for no defined in combo list of objects
								$new_array_options[$key] = '';
							} elseif ($value) {
								$object = new $InfoFieldList[0]($this->db);
								if (is_numeric($value)) {
									$res = $object->fetch($value); // Common case
								} else {
									$res = $object->fetch(0, $value); // For compatibility
								}

								if ($res > 0) {
									$new_array_options[$key] = $object->id;
								} else {
									$this->error = "Id/Ref '".$value."' for object '".$object->element."' not found";
									return -1;
								}
							}
						} else {
							dol_syslog('Error bad setup of extrafield', LOG_WARNING);
						}
						break;
					case 'checkbox':
					case 'chkbxlst':
						if (is_array($this->array_options[$key])) {
							$new_array_options[$key] = implode(',', $this->array_options[$key]);
						} else {
							$new_array_options[$key] = $this->array_options[$key];
						}
						break;
				}
			}

			$this->db->begin();

			$table_element = $this->table_element;
			if ($table_element == 'categorie') {
				$table_element = 'categories'; // For compatibility
			}

			dol_syslog(get_class($this)."::insertExtraFields delete then insert", LOG_DEBUG);

			$sql_del = "DELETE FROM ".$this->db->prefix().$table_element."_extrafields WHERE fk_object = ".((int) $this->id);
			$this->db->query($sql_del);

			$sql = "INSERT INTO ".$this->db->prefix().$table_element."_extrafields (fk_object";
			foreach ($new_array_options as $key => $value) {
				$attributeKey = substr($key, 8); // Remove 'options_' prefix
				// Add field of attribute
				if ($extrafields->attributes[$this->table_element]['type'][$attributeKey] != 'separate') { // Only for other type than separator
					$sql .= ",".$attributeKey;
				}
			}
			// We must insert a default value for fields for other entities that are mandatory to avoid not null error
			if (!empty($extrafields->attributes[$this->table_element]['mandatoryfieldsofotherentities']) && is_array($extrafields->attributes[$this->table_element]['mandatoryfieldsofotherentities'])) {
				foreach ($extrafields->attributes[$this->table_element]['mandatoryfieldsofotherentities'] as $tmpkey => $tmpval) {
					if (!isset($extrafields->attributes[$this->table_element]['type'][$tmpkey])) {    // If field not already added previously
						$sql .= ",".$tmpkey;
					}
				}
			}
			$sql .= ") VALUES (".$this->id;

			foreach ($new_array_options as $key => $value) {
				$attributeKey = substr($key, 8); // Remove 'options_' prefix
				// Add field of attribute
				if (!in_array($extrafields->attributes[$this->table_element]['type'][$attributeKey], ['separate', 'point', 'multipts', 'linestrg', 'polygon'])) { // Only for other type than separator)
					if ($new_array_options[$key] != '' || $new_array_options[$key] == '0') {
						$sql .= ",'".$this->db->escape($new_array_options[$key])."'";
					} else {
						$sql .= ",null";
					}
				}
				if ($extrafields->attributes[$this->table_element]['type'][$attributeKey] == 'point') { // for point type
					if (!empty($new_array_options[$key])) {
						$sql .= ",ST_PointFromText('".$this->db->escape($new_array_options[$key])."')";
					} else {
						$sql .= ",null";
					}
				}
				if ($extrafields->attributes[$this->table_element]['type'][$attributeKey] == 'multipts') { // for point type
					if (!empty($new_array_options[$key])) {
						$sql .= ",ST_MultiPointFromText('".$this->db->escape($new_array_options[$key])."')";
					} else {
						$sql .= ",null";
					}
				}
				if ($extrafields->attributes[$this->table_element]['type'][$attributeKey] == 'linestrg') { // for linestring type
					if (!empty($new_array_options[$key])) {
						$sql .= ",ST_LineFromText('".$this->db->escape($new_array_options[$key])."')";
					} else {
						$sql .= ",null";
					}
				}
				if ($extrafields->attributes[$this->table_element]['type'][$attributeKey] == 'polygon') { // for polygon type
					if (!empty($new_array_options[$key])) {
						$sql .= ",ST_PolyFromText('".$this->db->escape($new_array_options[$key])."')";
					} else {
						$sql .= ",null";
					}
				}
			}
			// We must insert a default value for fields for other entities that are mandatory to avoid not null error
			if (!empty($extrafields->attributes[$this->table_element]['mandatoryfieldsofotherentities']) && is_array($extrafields->attributes[$this->table_element]['mandatoryfieldsofotherentities'])) {
				foreach ($extrafields->attributes[$this->table_element]['mandatoryfieldsofotherentities'] as $tmpkey => $tmpval) {
					if (!isset($extrafields->attributes[$this->table_element]['type'][$tmpkey])) {   // If field not already added previously
						if (in_array($tmpval, array('int', 'double', 'price'))) {
							$sql .= ", 0";
						} else {
							$sql .= ", ''";
						}
					}
				}
			}

			$sql .= ")";

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && $trigger) {
				// Call trigger
				$this->context = array('extrafieldaddupdate' => 1);
				$result = $this->call_trigger($trigger, $userused);
				if ($result < 0) {
					$error++;
				}
				// End call trigger
			}

			if ($error) {
				$this->db->rollback();
				return -1;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			return 0;
		}
	}

	/**
	 *	Add/Update all extra fields values for the current object.
	 *  Data to describe values to insert/update are stored into $this->array_options=array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
	 *  This function delete record with all extrafields and insert them again from the array $this->array_options.
	 *
	 *  @param	string		$trigger		If defined, call also the trigger (for example COMPANY_MODIFY)
	 *  @param	User		$userused		Object user
	 *  @return int<-1,1>					-1=error, O=did nothing, 1=OK
	 *  @see insertExtraFields(), updateExtraField(), setValueFrom()
	 */
	public function insertExtraLanguages($trigger = '', $userused = null)
	{
		global $conf, $langs, $user;

		if (empty($userused)) {
			$userused = $user;
		}

		$error = 0;

		if (getDolGlobalString('MAIN_EXTRALANGUAGES_DISABLED')) {
			return 0; // For avoid conflicts if trigger used
		}

		if (is_array($this->array_languages)) {
			$new_array_languages = $this->array_languages;

			foreach ($new_array_languages as $key => $value) {
				$attributeKey      = $key;
				$attributeType     = $this->fields[$attributeKey]['type'];
				$attributeLabel    = $this->fields[$attributeKey]['label'];

				//dol_syslog("attributeLabel=".$attributeLabel, LOG_DEBUG);
				//dol_syslog("attributeType=".$attributeType, LOG_DEBUG);

				switch ($attributeType) {
					case 'int':
						if (is_array($value) || (!is_numeric($value) && $value != '')) {
							$this->errors[] = $langs->trans("ExtraLanguageHasWrongValue", $attributeLabel);
							return -1;
						} elseif ($value == '') {
							$new_array_languages[$key] = null;
						}
						break;
					case 'double':
						$value = price2num($value);
						if (!is_numeric($value) && $value != '') {
							dol_syslog($langs->trans("ExtraLanguageHasWrongValue")." on ".$attributeLabel."(".$value."is not '".$attributeType."')", LOG_DEBUG);
							$this->errors[] = $langs->trans("ExtraLanguageHasWrongValue", $attributeLabel);
							return -1;
						} elseif ($value == '') {
							$new_array_languages[$key] = null;
						} else {
							$new_array_languages[$key] = $value;
						}
						break;
						/*case 'select':	// Not required, we chose value='0' for undefined values
						 if ($value=='-1')
						 {
						 $this->array_options[$key] = null;
						 }
						 break;*/
				}
			}

			$this->db->begin();

			$table_element = $this->table_element;
			if ($table_element == 'categorie') {	// TODO Rename table llx_categories_extrafields into llx_categorie_extrafields so we can remove this.
				$table_element = 'categories'; // For compatibility
			}

			dol_syslog(get_class($this)."::insertExtraLanguages delete then insert", LOG_DEBUG);

			foreach ($new_array_languages as $key => $langcodearray) {	// $key = 'name', 'town', ...
				foreach ($langcodearray as $langcode => $value) {
					$sql_del = "DELETE FROM ".$this->db->prefix()."object_lang";
					$sql_del .= " WHERE fk_object = ".((int) $this->id)." AND property = '".$this->db->escape($key)."' AND type_object = '".$this->db->escape($table_element)."'";
					$sql_del .= " AND lang = '".$this->db->escape($langcode)."'";
					$this->db->query($sql_del);

					if ($value !== '') {
						$sql = "INSERT INTO ".$this->db->prefix()."object_lang (fk_object, property, type_object, lang, value";
						$sql .= ") VALUES (".$this->id.", '".$this->db->escape($key)."', '".$this->db->escape($table_element)."', '".$this->db->escape($langcode)."', '".$this->db->escape($value)."'";
						$sql .= ")";

						$resql = $this->db->query($sql);
						if (!$resql) {
							$this->error = $this->db->lasterror();
							$error++;
							break;
						}
					}
				}
			}

			if (!$error && $trigger) {
				// Call trigger
				$this->context = array('extralanguagesaddupdate' => 1);
				$result = $this->call_trigger($trigger, $userused);
				if ($result < 0) {
					$error++;
				}
				// End call trigger
			}

			if ($error) {
				$this->db->rollback();
				return -1;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			return 0;
		}
	}

	/**
	 *	Update 1 extra field value for the current object. Keep other fields unchanged.
	 *  Data to describe values to update are stored into $this->array_options=array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
	 *
	 *  @param  string      $key    		Key of the extrafield to update (without starting 'options_')
	 *  @param	string		$trigger		If defined, call also the trigger (for example COMPANY_MODIFY)
	 *  @param	User		$userused		Object user
	 *  @return int<-1,1>              		-1=error, O=did nothing, 1=OK
	 *  @see updateExtraLanguages(), insertExtraFields(), deleteExtraFields(), setValueFrom()
	 */
	public function updateExtraField($key, $trigger = null, $userused = null)
	{
		global $conf, $langs, $user, $hookmanager;

		if (getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
			return 0;
		}

		if (empty($userused)) {
			$userused = $user;
		}

		$error = 0;

		if (!empty($this->array_options) && isset($this->array_options["options_".$key])) {
			// Check parameters
			$langs->load('admin');
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			$extrafields->fetch_name_optionals_label($this->table_element);

			$value = $this->array_options["options_".$key];

			$attributeKey      = $key;
			$attributeType     = $extrafields->attributes[$this->table_element]['type'][$key];
			$attributeLabel    = $extrafields->attributes[$this->table_element]['label'][$key];
			$attributeParam    = $extrafields->attributes[$this->table_element]['param'][$key];
			$attributeRequired = $extrafields->attributes[$this->table_element]['required'][$key];
			$attributeUnique   = $extrafields->attributes[$this->table_element]['unique'][$attributeKey];
			$attrfieldcomputed = $extrafields->attributes[$this->table_element]['computed'][$key];

			// Similar code than into insertExtraFields
			if ($attributeRequired) {
				$mandatorypb = false;
				if ($attributeType == 'link' && $this->array_options["options_".$key] == '-1') {
					$mandatorypb = true;
				}
				if ($this->array_options["options_".$key] === '') {
					$mandatorypb = true;
				}
				if ($mandatorypb) {
					$langs->load("errors");
					dol_syslog("Mandatory field 'options_".$key."' is empty during update and set to required into definition of extrafields");
					$this->errors[] = $langs->trans('ErrorFieldRequired', $attributeLabel);
					return -1;
				}
			}

			// $new_array_options will be used for direct update, so must contains formatted data for the UPDATE.
			$new_array_options = $this->array_options;

			//dol_syslog("attributeLabel=".$attributeLabel, LOG_DEBUG);
			//dol_syslog("attributeType=".$attributeType, LOG_DEBUG);
			if (!empty($attrfieldcomputed)) {
				if (getDolGlobalString('MAIN_STORE_COMPUTED_EXTRAFIELDS')) {
					$value = dol_eval($attrfieldcomputed, 1, 0, '2');
					dol_syslog($langs->trans("Extrafieldcomputed")." on ".$attributeLabel."(".$value.")", LOG_DEBUG);

					$new_array_options["options_".$key] = $value;

					$this->array_options["options_".$key] = $new_array_options["options_".$key];
				} else {
					$new_array_options["options_".$key] = null;

					$this->array_options["options_".$key] = $new_array_options["options_".$key];
				}
			}

			switch ($attributeType) {
				case 'int':
					if (!is_numeric($value) && $value != '') {
						$this->errors[] = $langs->trans("ExtraFieldHasWrongValue", $attributeLabel);
						return -1;
					} elseif ($value === '') {
						$new_array_options["options_".$key] = null;

						$this->array_options["options_".$key] = $new_array_options["options_".$key];
					}
					break;
				case 'price':
				case 'double':
					$value = price2num($value);
					if (!is_numeric($value) && $value != '') {
						dol_syslog($langs->trans("ExtraFieldHasWrongValue")." on ".$attributeLabel."(".$value."is not '".$attributeType."')", LOG_DEBUG);
						$this->errors[] = $langs->trans("ExtraFieldHasWrongValue", $attributeLabel);
						return -1;
					} elseif ($value === '') {
						$value = null;
					}
					//dol_syslog("double value"." on ".$attributeLabel."(".$value." is '".$attributeType."')", LOG_DEBUG);
					$new_array_options["options_".$key] = $value;

					$this->array_options["options_".$key] = $new_array_options["options_".$key];
					break;
					/*case 'select':	// Not required, we chose value='0' for undefined values
						 if ($value=='-1')
						 {
							$new_array_options["options_".$key] = $value;

							$this->array_options["options_".$key] = $new_array_options["options_".$key];
						 }
						 break;*/
				case 'password':
					$algo = '';
					if ($this->array_options["options_".$key] != '' && is_array($extrafields->attributes[$this->table_element]['param'][$attributeKey]['options'])) {
						// If there is an encryption choice, we use it to encrypt data before insert
						$tmparrays = array_keys($extrafields->attributes[$this->table_element]['param'][$attributeKey]['options']);
						$algo = reset($tmparrays);
						if ($algo != '') {
							//global $action;		// $action may be 'create', 'update', 'update_extras'...
							//var_dump($action);
							//var_dump($this->oldcopy);exit;
							//var_dump($key.' '.$this->array_options["options_".$key].' '.$algo);
							if (is_object($this->oldcopy)) {		// If this->oldcopy is not defined, we can't know if we change attribute or not, so we must keep value
								//var_dump($this->oldcopy->array_options["options_".$key]); var_dump($this->array_options["options_".$key]);
								if (isset($this->oldcopy->array_options["options_".$key]) && $this->array_options["options_".$key] == $this->oldcopy->array_options["options_".$key]) {	// If old value encrypted in database is same than submitted new value, it means we don't change it, so we don't update.
									if ($algo == 'dolcrypt') {	// dolibarr reversible encryption
										if (!preg_match('/^dolcrypt:/', $this->array_options["options_".$key])) {
											$new_array_options["options_".$key] = dolEncrypt($this->array_options["options_".$key]);	// warning, must be called when on the master
										} else {
											$new_array_options["options_".$key] = $this->array_options["options_".$key]; // Value is kept
										}
									} else {
										$new_array_options["options_".$key] = $this->array_options["options_".$key]; // Value is kept
									}
								} else {
									if ($algo == 'dolcrypt') {	// dolibarr reversible encryption
										if (!preg_match('/^dolcrypt:/', $this->array_options["options_".$key])) {
											$new_array_options["options_".$key] = dolEncrypt($this->array_options["options_".$key]);
										} else {
											$new_array_options["options_".$key] = $this->array_options["options_".$key]; // Value is kept
										}
									} else {
										$new_array_options["options_".$key] = dol_hash($this->array_options["options_".$key], $algo);
									}
								}
							} else {
								if ($algo == 'dolcrypt' && !preg_match('/^dolcrypt:/', $this->array_options["options_".$key])) {	// dolibarr reversible encryption
									$new_array_options["options_".$key] = dolEncrypt($this->array_options["options_".$key]);	// warning, must be called when on the master
								} else {
									$new_array_options["options_".$key] = $this->array_options["options_".$key]; // Value is kept
								}
							}
						} else {
							// No encryption
							$new_array_options["options_".$key] = $this->array_options["options_".$key]; // Value is kept
						}
					} else { // Common usage
						$new_array_options["options_".$key] = $this->array_options["options_".$key]; // Value is kept
					}

					$this->array_options["options_".$key] = $new_array_options["options_".$key];
					break;
				case 'date':
				case 'datetime':
					if (empty($this->array_options["options_".$key])) {
						$new_array_options["options_".$key] = null;

						$this->array_options["options_".$key] = $new_array_options["options_".$key];
					} else {
						$new_array_options["options_".$key] = $this->db->idate($this->array_options["options_".$key]);
					}
					break;
				case 'datetimegmt':
					if (empty($this->array_options["options_".$key])) {
						$new_array_options["options_".$key] = null;

						$this->array_options["options_".$key] = $new_array_options["options_".$key];
					} else {
						$new_array_options["options_".$key] = $this->db->idate($this->array_options["options_".$key], 'gmt');
					}
					break;
				case 'boolean':
					if (empty($this->array_options["options_".$key])) {
						$new_array_options["options_".$key] = null;

						$this->array_options["options_".$key] = $new_array_options["options_".$key];
					}
					break;
				case 'link':
					if ($this->array_options["options_".$key] === '') {
						$new_array_options["options_".$key] = null;

						$this->array_options["options_".$key] = $new_array_options["options_".$key];
					}
					break;
					/*
					case 'link':
						$param_list = array_keys($attributeParam['options']);
						// 0 : ObjectName
						// 1 : classPath
						$InfoFieldList = explode(":", $param_list[0]);
						dol_include_once($InfoFieldList[1]);
						if ($InfoFieldList[0] && class_exists($InfoFieldList[0]))
						{
							if ($value == '-1')	// -1 is key for no defined in combo list of objects
							{
								$new_array_options[$key] = '';
							} elseif ($value) {
								$object = new $InfoFieldList[0]($this->db);
								if (is_numeric($value)) $res = $object->fetch($value);	// Common case
								else $res = $object->fetch(0, $value);					// For compatibility

								if ($res > 0) $new_array_options[$key] = $object->id;
								else {
									$this->error = "Id/Ref '".$value."' for object '".$object->element."' not found";
									$this->db->rollback();
									return -1;
								}
							}
						} else {
							dol_syslog('Error bad setup of extrafield', LOG_WARNING);
						}
						break;
					*/
				case 'checkbox':
				case 'chkbxlst':
					$new_array_options = array();
					if (is_array($this->array_options["options_".$key])) {
						$new_array_options["options_".$key] = implode(',', $this->array_options["options_".$key]);
					} else {
						$new_array_options["options_".$key] = $this->array_options["options_".$key];
					}

					$this->array_options["options_".$key] = $new_array_options["options_".$key];
					break;
			}

			$this->db->begin();

			$linealreadyfound = 0;

			// Check if there is already a line for this object (in most cases, it is, but sometimes it is not, for example when extra field has been created after), so we must keep this overload)
			$table_element = $this->table_element;
			if ($table_element == 'categorie') {	// TODO Rename table llx_categories_extrafields into llx_categorie_extrafields so we can remove this.
				$table_element = 'categories'; // For compatibility
			}

			$sql = "SELECT COUNT(rowid) as nb FROM ".$this->db->prefix().$table_element."_extrafields WHERE fk_object = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if ($resql) {
				$tmpobj = $this->db->fetch_object($resql);
				if ($tmpobj) {
					$linealreadyfound = $tmpobj->nb;
				}
			}

			//var_dump('linealreadyfound='.$linealreadyfound.' sql='.$sql); exit;
			if ($linealreadyfound) {
				if ($this->array_options["options_".$key] === null) {
					$sql = "UPDATE ".$this->db->prefix().$table_element."_extrafields SET ".$key." = null";
				} else {
					$sql = "UPDATE ".$this->db->prefix().$table_element."_extrafields SET ".$key." = '".$this->db->escape($new_array_options["options_".$key])."'";
				}
				$sql .= " WHERE fk_object = ".((int) $this->id);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
			} else {
				$result = $this->insertExtraFields('', $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$parameters = array('key' => $key);
				global $action;
				$reshook = $hookmanager->executeHooks('updateExtraFieldBeforeCommit', $parameters, $this, $action);
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}
			}

			if (!$error && $trigger) {
				// Call trigger
				$this->context = array('extrafieldupdate' => 1);
				$result = $this->call_trigger($trigger, $userused);
				if ($result < 0) {
					$error++;
				}
				// End call trigger
			}

			if ($error) {
				dol_syslog(__METHOD__.$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			} else {
				$this->db->commit();
				return 1;
			}
		} else {
			return 0;
		}
	}

	/**
	 * Convenience method for retrieving the value of an extrafield without actually fetching it from the database.
	 *
	 * @param string $key Name of the extrafield
	 * @return mixed|null
	 */
	public function getExtraField($key)
	{
		return $this->array_options['options_'.$key] ?? null;
	}

	/**
	 * Convenience method for setting the value of an extrafield without actually updating it in the database.
	 *
	 * @param string $key   Name of the extrafield
	 * @param mixed  $value Value to be assigned to the extrafield
	 * @return void
	 */
	public function setExtraField($key, $value)
	{
		$this->array_options['options_'.$key] = $value;
	}

	/**
	 *	Update an extra language value for the current object.
	 *  Data to describe values to update are stored into $this->array_options=array('options_codeforfield1'=>'valueforfield1', 'options_codeforfield2'=>'valueforfield2', ...)
	 *
	 *  @param  string      $key    		Key of the extrafield (without starting 'options_')
	 *  @param	string		$trigger		If defined, call also the trigger (for example COMPANY_MODIFY)
	 *  @param	User		$userused		Object user
	 *  @return int                 		-1=error, O=did nothing, 1=OK
	 *  @see updateExtraField(), insertExtraLanguages()
	 */
	public function updateExtraLanguages($key, $trigger = null, $userused = null)
	{
		global $conf, $langs, $user;

		if (empty($userused)) {
			$userused = $user;
		}

		$error = 0;

		if (getDolGlobalString('MAIN_EXTRALANGUAGES_DISABLED')) {
			return 0; // For avoid conflicts if trigger used
		}

		return 0;
	}


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param ?array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$val	Array of properties for field to show (used only if ->fields not defined)
	 *                                                                                                                                                                                                                                                                                                                                          Array of properties of field to show
	 * @param  string  		$key           Key of attribute
	 * @param  string|string[]	$value         Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param  string  		$moreparam     To add more parameters on html input tag
	 * @param  string  		$keysuffix     Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  		$keyprefix     Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string|int	$morecss       Value for css to define style/length of field. May also be a numeric.
	 * @param  int<0,1>		$nonewbutton   Force to not show the new button on field that are links to object
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		if (!empty($this->fields)) {
			$val = $this->fields[$key];
		}

		// Validation tests and output
		$fieldValidationErrorMsg = '';
		$validationClass = '';
		$fieldValidationErrorMsg = $this->getFieldError($key);
		if (!empty($fieldValidationErrorMsg)) {
			$validationClass = ' --error'; // the -- is use as class state in css :  .--error can't be be defined alone it must be define with another class like .my-class.--error or input.--error
		} else {
			$validationClass = ' --success'; // the -- is use as class state in css :  .--success can't be be defined alone it must be define with another class like .my-class.--success or input.--success
		}

		$valuemultiselectinput = array();
		$out = '';
		$type = '';
		$isDependList = 0;
		$param = array();
		$param['options'] = array();
		$reg = array();
		// @phan-suppress-next-line PhanTypeMismatchProperty
		$size = !empty($this->fields[$key]['size']) ? $this->fields[$key]['size'] : 0;
		// Because we work on extrafields
		if (preg_match('/^(integer|link):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4].':'.$reg[5] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4].':'.$reg[5] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2].':'.$reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^chkbxlst:(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/varchar\((\d+)\)/', $val['type'], $reg)) {
			$param['options'] = array();
			$type = 'varchar';
			$size = $reg[1];
		} elseif (preg_match('/varchar/', $val['type'])) {
			$param['options'] = array();
			$type = 'varchar';
		} else {
			$param['options'] = array();
			$type = $this->fields[$key]['type'];
		}
		//var_dump($type); var_dump($param['options']);

		// Special case that force options and type ($type can be integer, varchar, ...)
		if (!empty($this->fields[$key]['arrayofkeyval']) && is_array($this->fields[$key]['arrayofkeyval'])) {
			$param['options'] = $this->fields[$key]['arrayofkeyval'];
			// Special case that prevent to force $type to have multiple input @phan-suppress-next-line PhanTypeMismatchProperty
			if (empty($this->fields[$key]['multiinput'])) {
				$type = (($this->fields[$key]['type'] == 'checkbox') ? $this->fields[$key]['type'] : 'select');
			}
		}

		$label = $this->fields[$key]['label'];
		//$elementtype=$this->fields[$key]['elementtype'];	// Seems not used
		// @phan-suppress-next-line PhanTypeArraySuspiciousNullable
		$default = (!empty($this->fields[$key]['default']) ? $this->fields[$key]['default'] : '');
		// @phan-suppress-next-line PhanTypeMismatchProperty
		$computed = (!empty($this->fields[$key]['computed']) ? $this->fields[$key]['computed'] : '');
		// @phan-suppress-next-line PhanTypeMismatchProperty
		$unique = (!empty($this->fields[$key]['unique']) ? $this->fields[$key]['unique'] : 0);
		// @phan-suppress-next-line PhanTypeMismatchProperty
		$required = (!empty($this->fields[$key]['required']) ? $this->fields[$key]['required'] : 0);
		// @phan-suppress-next-line PhanTypeMismatchProperty
		$autofocusoncreate = (!empty($this->fields[$key]['autofocusoncreate']) ? $this->fields[$key]['autofocusoncreate'] : 0);

		// @phan-suppress-next-line PhanTypeMismatchProperty
		$langfile = (!empty($this->fields[$key]['langfile']) ? $this->fields[$key]['langfile'] : '');
		// @phan-suppress-next-line PhanTypeMismatchProperty
		$list = (!empty($this->fields[$key]['list']) ? $this->fields[$key]['list'] : 0);
		$hidden = (in_array(abs($this->fields[$key]['visible']), array(0, 2)) ? 1 : 0);

		$objectid = $this->id;

		if ($computed) {
			if (!preg_match('/^search_/', $keyprefix)) {
				return '<span class="opacitymedium">'.$langs->trans("AutomaticallyCalculated").'</span>';
			} else {
				return '';
			}
		}

		// Set value of $morecss. For this, we use in priority showsize from parameters, then $val['css'] then autodefine
		if (empty($morecss) && !empty($val['css'])) {
			$morecss = $val['css'];
		} elseif (empty($morecss)) {
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'link') {	// link means an foreign key to another primary id
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'integer', 'price')) || preg_match('/^double(\([0-9],[0-9]\)){0,1}/', (string) $type)) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} else {
				if (is_numeric($size) && round((float) $size) < 12) {
					$morecss = 'minwidth100';
				} elseif (is_numeric($size) && round((float) $size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
		}

		// Add validation state class
		if (!empty($validationClass)) {
			$morecss .= $validationClass;
		}

		if (in_array($type, array('date'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 0;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
		} elseif (in_array($type, array('datetime'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$showtime = 1;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') {
				$value = '-1';
			}

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix.$key.$keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1, '', '', '', 1, '', '', 'tzuserrel');
		} elseif (in_array($type, array('duration'))) {
			$out = $form->select_duration($keyprefix.$key.$keysuffix, $value, 0, 'text', 0, 1);
		} elseif (in_array($type, array('int', 'integer'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"'.($newsize > 0 ? ' maxlength="'.$newsize.'"' : '').' value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (in_array($type, array('real'))) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (preg_match('/varchar/', (string) $type)) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"'.($size > 0 ? ' maxlength="'.$size.'"' : '').' value="'.dol_escape_htmltag($value).'"'.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (in_array($type, array('email', 'mail', 'phone', 'url', 'ip'))) {
			$out = '<input type="text" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').($autofocusoncreate ? ' autofocus' : '').'>';
		} elseif (preg_match('/^text/', (string) $type)) {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				if (!empty($param['options'])) {
					// If the textarea field has a list of arrayofkeyval into its definition, we suggest a combo with possible values to fill the textarea.
					//var_dump($param['options']);
					$out .= $form->selectarray($keyprefix.$key.$keysuffix."_multiinput", $param['options'], '', 1, 0, 0, "flat maxwidthonphone".$morecss);
					$out .= '<input id="'.$keyprefix.$key.$keysuffix.'_multiinputadd" type="button" class="button" value="'.$langs->trans("Add").'">';
					$out .= "<script>";
					$out .= '
					function handlemultiinputdisabling(htmlname){
						console.log("We handle the disabling of used options for "+htmlname+"_multiinput");
						multiinput = $("#"+htmlname+"_multiinput");
						multiinput.find("option").each(function(){
							tmpval = $("#"+htmlname).val();
							tmpvalarray = tmpval.split("\n");
							valtotest = $(this).val();
							if(tmpvalarray.includes(valtotest)){
								$(this).prop("disabled",true);
							} else {
								if($(this).prop("disabled") == true){
									console.log(valtotest)
									$(this).prop("disabled", false);
								}
							}
						});
					}

					$(document).ready(function () {
						$("#'.$keyprefix.$key.$keysuffix.'_multiinputadd").on("click",function() {
							tmpval = $("#'.$keyprefix.$key.$keysuffix.'").val();
							tmpvalarray = tmpval.split(",");
							valtotest = $("#'.$keyprefix.$key.$keysuffix.'_multiinput").val();
							if(valtotest != -1 && !tmpvalarray.includes(valtotest)){
								console.log("We add the selected value to the text area '.$keyprefix.$key.$keysuffix.'");
								if(tmpval == ""){
									tmpval = valtotest;
								} else {
									tmpval = tmpval + "\n" + valtotest;
								}
								$("#'.$keyprefix.$key.$keysuffix.'").val(tmpval);
								handlemultiinputdisabling("'.$keyprefix.$key.$keysuffix.'");
								$("#'.$keyprefix.$key.$keysuffix.'_multiinput").val(-1);
							} else {
								console.log("We add nothing the text area '.$keyprefix.$key.$keysuffix.'");
							}
						});
						$("#'.$keyprefix.$key.$keysuffix.'").on("change",function(){
							handlemultiinputdisabling("'.$keyprefix.$key.$keysuffix.'");
						});
						handlemultiinputdisabling("'.$keyprefix.$key.$keysuffix.'");
					})';
					$out .= "</script>";
					$value = str_replace(',', "\n", $value);
				}
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				$out .= (string) $doleditor->Create(1, '', true, '', '', '', $morecss);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif (preg_match('/^html/', (string) $type)) {
			if (!preg_match('/search_/', $keyprefix)) {		// If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix.$key.$keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, isModEnabled('fckeditor') && getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_5, '90%');
				$out = (string) $doleditor->Create(1, '', true, '', '', $moreparam, $morecss);
			} else {
				$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.dol_escape_htmltag($value).'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked value="1" ';
			} else {
				$checked = ' value="1" ';
			}
			$out = '<input type="checkbox" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.$checked.' '.($moreparam ? $moreparam : '').'>';
		} elseif ($type == 'price') {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> '.$langs->getCurrencySymbol($conf->currency);
		} elseif (preg_match('/^double(\([0-9],[0-9]\)){0,1}/', (string) $type)) {
			if (!empty($value)) {		// $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'> ';
		} elseif ($type == 'select') {	// combo list
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$tmpselect = '';
			$nbchoice = 0;
			foreach ($param['options'] as $keyb => $valb) {
				if ((string) $keyb == '') {
					continue;
				}
				if (strpos($valb, "|") !== false) {
					list($valb, $parent) = explode('|', $valb);
				}
				$nbchoice++;
				$tmpselect .= '<option value="'.$keyb.'"';
				$tmpselect .= (((string) $value == (string) $keyb) ? ' selected' : '');
				if (!empty($parent)) {
					$isDependList = 1;
				}
				$tmpselect .= (!empty($parent) ? ' parent="'.$parent.'"' : '');
				$tmpselect .= '>'.$langs->trans($valb).'</option>';
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if ((!isset($this->fields[$key]['default'])) || ($this->fields[$key]['notnull'] != 1) || $nbchoice >= 2) {
				$out .= '<option value="0">&nbsp;</option>';
			}
			$out .= $tmpselect;
			$out .= '</select>';
		} elseif ($type == 'sellist') {
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_EXTRAFIELDS_DISABLE_SELECT2')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix.$key.$keysuffix, array(), 0);
			}

			$out .= '<select class="flat '.$morecss.' maxwidthonsmartphone" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '').'>';
			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0], 5);
				if (! empty($InfoFieldList[4])) {
					$pos = 0;
					$parenthesisopen = 0;
					while (substr($InfoFieldList[4], $pos, 1) !== '' && ($parenthesisopen || $pos == 0 || substr($InfoFieldList[4], $pos, 1) != ':')) {
						if (substr($InfoFieldList[4], $pos, 1) == '(') {
							$parenthesisopen++;
						}
						if (substr($InfoFieldList[4], $pos, 1) == ')') {
							$parenthesisopen--;
						}
						$pos++;
					}
					$tmpbefore = substr($InfoFieldList[4], 0, $pos);
					$tmpafter = substr($InfoFieldList[4], $pos + 1);
					//var_dump($InfoFieldList[4].' -> '.$pos); var_dump($tmpafter);
					$InfoFieldList[4] = $tmpbefore;
					if ($tmpafter !== '') {
						$InfoFieldList = array_merge($InfoFieldList, explode(':', $tmpafter));
					}
					//var_dump($InfoFieldList);
				}
				$parentName = '';
				$parentField = '';

				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				// 5 : id category type
				// 6 : ids categories list separated by comma for category root
				// 7 : sort field
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if (!$filter_categorie) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT " . $keyList;
					$sql .= " FROM " . $this->db->prefix() . $InfoFieldList[0];
					if (!empty($InfoFieldList[4])) {
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
							$InfoFieldList[4] = str_replace('$ID$', (string) $objectid, $InfoFieldList[4]);
						} else {
							$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
						}

						// We have to join on extrafield table
						$errstr = '';
						if (strpos($InfoFieldList[4], 'extra') !== false) {
							$sql .= " as main, " . $this->db->prefix() . $InfoFieldList[0] . "_extrafields as extra";
							$sqlwhere .= " WHERE extra.fk_object=main." . $InfoFieldList[2];
							$sqlwhere .= " AND " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						} else {
							$sqlwhere .= " WHERE " . forgeSQLFromUniversalSearchCriteria($InfoFieldList[4], $errstr, 1);
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}
					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= " AND entity = " . ((int) $conf->entity);
					}
					$sql .= $sqlwhere;
					//print $sql;

					// Note: $InfoFieldList can be 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter[:CategoryIdType[:CategoryIdList[:Sortfield]]]]]]'
					if (isset($InfoFieldList[7]) && preg_match('/^[a-z0-9_\-,]+$/i', $InfoFieldList[7])) {
						$sql .= " ORDER BY ".$this->db->escape($InfoFieldList[7]);
					} else {
						$sql .= " ORDER BY ".$this->db->sanitize(implode(', ', $fields_label));
					}

					dol_syslog(get_class($this) . '::showInputField type=sellist', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$out .= '<option value="0">&nbsp;</option>';
						$num = $this->db->num_rows($resql);
						$i = 0;
						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							// Several field into label (eq table:code|libelle:rowid)
							$notrans = false;
							$fields_label = explode('|', $InfoFieldList[1]);
							if (count($fields_label) > 1) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow . ' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}
							$labeltoshow = dol_trunc($labeltoshow, 45);

							if ($value == $obj->rowid) {
								foreach ($fields_label as $field_toshow) {
									$translabel = $langs->trans($obj->$field_toshow);
									if ($translabel != $obj->$field_toshow) {
										$labeltoshow = dol_trunc($translabel) . ' ';
									} else {
										$labeltoshow = dol_trunc($obj->$field_toshow) . ' ';
									}
								}
								$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									if ($translabel != $obj->{$InfoFieldList[1]}) {
										$labeltoshow = dol_trunc($translabel, 18);
									} else {
										$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]});
									}
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}
								if ($value == $obj->rowid) {
									$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName . ':' . $obj->{$parentField};
									$isDependList = 1;
								}

								$out .= '<option value="' . $obj->rowid . '"';
								$out .= ($value == $obj->rowid ? ' selected' : '');
								$out .= (!empty($parent) ? ' parent="' . $parent . '"' : '');
								$out .= '>' . $labeltoshow . '</option>';
							}

							$i++;
						}
						$this->db->free($resql);
					} else {
						print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					$data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out .= '<option value="0">&nbsp;</option>';
					foreach ($data as $data_key => $data_value) {
						$out .= '<option value="' . $data_key . '"';
						$out .= ($value == $data_key ? ' selected' : '');
						$out .= '>' . $data_value . '</option>';
					}
				}
			}
			$out .= '</select>';
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$out = $form->multiselectarray($keyprefix.$key.$keysuffix, (empty($param['options']) ? null : $param['options']), $value_arr, 0, 0, $morecss, 0, '100%');
		} elseif ($type == 'radio') {
			$out = '';
			foreach ($param['options'] as $keyopt => $valopt) {
				$out .= '<input class="flat '.$morecss.'" type="radio" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" '.($moreparam ? $moreparam : '');
				$out .= ' value="'.$keyopt.'"';
				$out .= ' id="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'"';
				$out .= ($value == $keyopt ? 'checked' : '');
				$out .= '/><label for="'.$keyprefix.$key.$keysuffix.'_'.$keyopt.'">'.$valopt.'</label><br>';
			}
		} elseif ($type == 'chkbxlst') {
			if (is_array($value)) {
				$value_arr = $value;
			} else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				// 5 : id category type
				// 6 : ids categories list separated by comma for category root
				'@phan-var-force array{0:string,1:string,2:string,3:string,3:string,5:string,6:string} $InfoFieldList';

				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2].' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', '.$parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.'.$InfoFieldList[2].' as rowid';
					} else {
						$keyList = $InfoFieldList[2].' as rowid';
					}
				}

				$filter_categorie = false;
				if (count($InfoFieldList) > 5) {
					if ($InfoFieldList[0] == 'categorie') {
						$filter_categorie = true;
					}
				}

				if (!$filter_categorie) {
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($fields_label)) {
						$keyList .= ', ';
						$keyList .= implode(', ', $fields_label);
					}

					$sqlwhere = '';
					$sql = "SELECT " . $keyList;
					$sql .= ' FROM ' . $this->db->prefix() . $InfoFieldList[0];
					if (!empty($InfoFieldList[4])) {
						// can use SELECT request
						if (strpos($InfoFieldList[4], '$SEL$') !== false) {
							$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
						}

						// current object id can be use into filter
						if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
							$InfoFieldList[4] = str_replace('$ID$', (string) $objectid, $InfoFieldList[4]);
						} else {
							$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
						}

						// We have to join on extrafield table
						if (strpos($InfoFieldList[4], 'extra') !== false) {
							$sql .= ' as main, ' . $this->db->prefix() . $InfoFieldList[0] . '_extrafields as extra';
							$sqlwhere .= " WHERE extra.fk_object=main." . $InfoFieldList[2] . " AND " . $InfoFieldList[4];
						} else {
							$sqlwhere .= " WHERE " . $InfoFieldList[4];
						}
					} else {
						$sqlwhere .= ' WHERE 1=1';
					}
					// Some tables may have field, some other not. For the moment we disable it.
					if (in_array($InfoFieldList[0], array('tablewithentity'))) {
						$sqlwhere .= " AND entity = " . ((int) $conf->entity);
					}
					// $sql.=preg_replace('/^ AND /','',$sqlwhere);
					// print $sql;

					$sql .= $sqlwhere;
					dol_syslog(get_class($this) . '::showInputField type=chkbxlst', LOG_DEBUG);
					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						$i = 0;

						$data = array();

						while ($i < $num) {
							$labeltoshow = '';
							$obj = $this->db->fetch_object($resql);

							$notrans = false;
							// Several field into label (eq table:code|libelle:rowid)
							$fields_label = explode('|', $InfoFieldList[1]);
							if (count($fields_label) > 1) {
								$notrans = true;
								foreach ($fields_label as $field_toshow) {
									$labeltoshow .= $obj->$field_toshow . ' ';
								}
							} else {
								$labeltoshow = $obj->{$InfoFieldList[1]};
							}
							$labeltoshow = dol_trunc($labeltoshow, 45);

							if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
								foreach ($fields_label as $field_toshow) {
									$translabel = $langs->trans($obj->$field_toshow);
									if ($translabel != $obj->$field_toshow) {
										$labeltoshow = dol_trunc($translabel, 18) . ' ';
									} else {
										$labeltoshow = dol_trunc($obj->$field_toshow, 18) . ' ';
									}
								}

								$data[$obj->rowid] = $labeltoshow;
							} else {
								if (!$notrans) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
									if ($translabel != $obj->{$InfoFieldList[1]}) {
										$labeltoshow = dol_trunc($translabel, 18);
									} else {
										$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
									}
								}
								if (empty($labeltoshow)) {
									$labeltoshow = '(not defined)';
								}

								if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
									$data[$obj->rowid] = $labeltoshow;
								}

								if (!empty($InfoFieldList[3]) && $parentField) {
									$parent = $parentName . ':' . $obj->{$parentField};
									$isDependList = 1;
								}

								$data[$obj->rowid] = $labeltoshow;
							}

							$i++;
						}
						$this->db->free($resql);

						$out = $form->multiselectarray($keyprefix . $key . $keysuffix, $data, $value_arr, 0, 0, $morecss, 0, '100%');
					} else {
						print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
					}
				} else {
					require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
					$data = $form->select_all_categories(Categorie::$MAP_ID_TO_CODE[$InfoFieldList[5]], '', 'parent', 64, $InfoFieldList[6], 1, 1);
					$out = $form->multiselectarray($keyprefix . $key . $keysuffix, $data, $value_arr, 0, 0, $morecss, 0, '100%');
				}
			}
		} elseif ($type == 'link') {
			// $param_list='ObjectName:classPath[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
			// Filter can contains some ':' inside.
			$param_list = array_keys($param['options']);
			$param_list_array = explode(':', $param_list[0], 4);

			$showempty = (($required && $default != '') ? 0 : 1);

			if (!preg_match('/search_/', $keyprefix)) {
				if (!empty($param_list_array[2])) {		// If the entry into $fields is set to add a create button
					// @phan-suppress-next-line PhanTypeMismatchProperty
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusxx';
					} else {
						$morecss .= ' widthcentpercentminusx';
					}
				} else {
					// @phan-suppress-next-line PhanTypeMismatchProperty
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusx';
					}
				}
			}
			$objectfield = $this->element.($this->module ? '@'.$this->module : '').':'.$key.$keysuffix;
			$out = $form->selectForForms($param_list_array[0], $keyprefix.$key.$keysuffix, $value, $showempty, '', '', $morecss, $moreparam, 0, (empty($val['disabled']) ? 0 : 1), '', $objectfield);

			if (!empty($param_list_array[2])) {		// If the entry into $fields is set, we must add a create button
				if ((!GETPOSTISSET('backtopage') || strpos(GETPOST('backtopage'), $_SERVER['PHP_SELF']) === 0)	// // To avoid to open several times the 'Plus' button (we accept only one level)
					&& empty($val['disabled']) && empty($nonewbutton)) {	// and to avoid to show the button if the field is protected by a "disabled".
					list($class, $classfile) = explode(':', $param_list[0]);
					if (file_exists(dol_buildpath(dirname(dirname($classfile)).'/card.php'))) {
						$url_path = dol_buildpath(dirname(dirname($classfile)).'/card.php', 1);
					} else {
						$url_path = dol_buildpath(dirname(dirname($classfile)).'/'.strtolower($class).'_card.php', 1);
					}
					$paramforthenewlink = '';
					$paramforthenewlink .= (GETPOSTISSET('action') ? '&action='.GETPOST('action', 'aZ09') : '');
					$paramforthenewlink .= (GETPOSTISSET('id') ? '&id='.GETPOSTINT('id') : '');
					$paramforthenewlink .= (GETPOSTISSET('origin') ? '&origin='.GETPOST('origin', 'aZ09') : '');
					$paramforthenewlink .= (GETPOSTISSET('originid') ? '&originid='.GETPOSTINT('originid') : '');
					$paramforthenewlink .= '&fk_'.strtolower($class).'=--IDFORBACKTOPAGE--';
					// TODO Add JavaScript code to add input fields already filled into $paramforthenewlink so we won't loose them when going back to main page
					$out .= '<a class="butActionNew" title="'.$langs->trans("New").'" href="'.$url_path.'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF'].($paramforthenewlink ? '?'.$paramforthenewlink : '')).'"><span class="fa fa-plus-circle valignmiddle"></span></a>';
				}
			}
		} elseif ($type == 'password') {
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			if ($keyprefix.$key.$keysuffix == 'pass_crypted') {
				$out = '<input type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="pass" id="pass" value="" '.($moreparam ? $moreparam : '').'>';
				$out .= '<input type="hidden" name="pass_crypted" id="pass_crypted" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
			} else {
				$out = '<input type="'.($keyprefix == 'search_' ? 'text' : 'password').'" class="flat '.$morecss.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'" value="'.$value.'" '.($moreparam ? $moreparam : '').'>';
			}
		} elseif ($type == 'array') {
			$newval = $val;
			$newval['type'] = 'varchar(256)';

			$out = '';
			if (!empty($value)) {
				foreach ($value as $option) {
					$out .= '<span><a class="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
					$out .= $this->showInputField($newval, $keyprefix.$key.$keysuffix.'[]', $option, $moreparam, '', '', $morecss).'<br></span>';
				}
			}
			$out .= '<a id="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_add" href="javascript:;"><span class="fa fa-plus-circle valignmiddle"></span></a>';

			$newInput = '<span><a class="'.dol_escape_htmltag($keyprefix.$key.$keysuffix).'_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
			$newInput .= $this->showInputField($newval, $keyprefix.$key.$keysuffix.'[]', '', $moreparam, '', '', $morecss).'<br></span>';

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '
					<script nonce="'.getNonce().'">
					$(document).ready(function() {
						$("a#'.dol_escape_js($keyprefix.$key.$keysuffix).'_add").click(function() {
							$("'.dol_escape_js($newInput).'").insertBefore(this);
						});

						$(document).on("click", "a.'.dol_escape_js($keyprefix.$key.$keysuffix).'_del", function() {
							$(this).parent().remove();
						});
					});
					</script>';
			}
		}
		if (!empty($hidden)) {
			$out = '<input type="hidden" value="'.$value.'" name="'.$keyprefix.$key.$keysuffix.'" id="'.$keyprefix.$key.$keysuffix.'"/>';
		}

		if ($isDependList == 1) {
			$out .= $this->getJSListDependancies('_common');
		}
		/* Add comments
		 if ($type == 'date') $out.=' (YYYY-MM-DD)';
		 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
		 */

		// Display error message for field
		if (!empty($fieldValidationErrorMsg) && function_exists('getFieldErrorIcon')) {
			$out .= ' '.getFieldErrorIcon($fieldValidationErrorMsg);
		}

		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$val	Array of properties of field to show
	 * @param  string  	$key            	Key of attribute
	 * @param  string  	$value          	Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param  string  	$moreparam      	To add more parameters on html tag
	 * @param  string  	$keysuffix      	Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  string  	$keyprefix      	Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param  mixed   	$morecss        	Value for CSS to use (Old usage: May also be a numeric to define a size).
	 * @return string
	 */
	public function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{
		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		//$label = empty($val['label']) ? '' : $val['label'];
		$type  = empty($val['type']) ? '' : $val['type'];
		$size  = empty($val['css']) ? '' : $val['css'];
		$reg = array();

		// Convert var to be able to share same code than showOutputField of extrafields
		if (preg_match('/varchar\((\d+)\)/', $type, $reg)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
			$size = $reg[1];
		} elseif (preg_match('/varchar/', $type)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
		}
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			// @phan-suppress-next-line PhanTypeMismatchProperty
			if (empty($this->fields[$key]['multiinput'])) {
				$type = (($this->fields[$key]['type'] == 'checkbox') ? $this->fields[$key]['type'] : 'select');
			}
		}
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type = 'link';
		}

		$default = empty($val['default']) ? '' : $val['default'];
		$computed = empty($val['computed']) ? '' : $val['computed'];
		$unique = empty($val['unique']) ? '' : $val['unique'];
		$required = empty($val['required']) ? '' : $val['required'];
		$param = array();
		$param['options'] = array();

		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			$param['options'] = $val['arrayofkeyval'];
		}
		if (preg_match('/^integer:([^:]*):([^:]*)/i', $val['type'], $reg)) {	// ex: integer:User:user/class/user.class.php
			$type = 'link';
			$stringforoptions = $reg[1].':'.$reg[2];
			// Special case: Force addition of getnomurlparam1 to -1 for users
			if ($reg[1] == 'User') {
				$stringforoptions .= ':#getnomurlparam1=-1';
			}
			$param['options'] = array($stringforoptions => $stringforoptions);
		} elseif (preg_match('/^sellist:(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2].':'.$reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^chkbxlst:(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] => 'N');
			$type = 'chkbxlst';
		}

		$langfile = empty($val['langfile']) ? '' : $val['langfile'];
		$list = (empty($val['list']) ? '' : $val['list']);
		$help = (empty($val['help']) ? '' : $val['help']);
		$hidden = (($val['visible'] == 0) ? 1 : 0); // If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		if ($hidden) {
			return '';
		}

		// If field is a computed field, value must become result of compute
		if ($computed) {
			// Make the eval of compute string
			//var_dump($computed);
			$value = dol_eval($computed, 1, 0, '2');
		}

		if (empty($morecss)) {
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'timestamp') {
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'double', 'price'))) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} else {
				if (is_numeric($size) && round((float) $size) < 12) {
					$morecss = 'minwidth100';
				} elseif (is_numeric($size) && round((float) $size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
		}

		// Format output value differently according to properties of field
		if (in_array($key, array('rowid', 'ref')) && method_exists($this, 'getNomUrl')) {
			// @phan-suppress-next-line PhanTypeMismatchProperty
			if ($key != 'rowid' || empty($this->fields['ref'])) {	// If we want ref field or if we want ID and there is no ref field, we show the link.
				$value = $this->getNomUrl(1, '', 0, '', 1);
			}
		} elseif ($key == 'status' && method_exists($this, 'getLibStatut')) {
			$value = $this->getLibStatut(3);
		} elseif ($type == 'date') {
			if (!empty($value)) {
				$value = dol_print_date($value, 'day');	// We suppose dates without time are always gmt (storage of course + output)
			} else {
				$value = '';
			}
		} elseif ($type == 'datetime' || $type == 'timestamp') {
			if (!empty($value)) {
				$value = dol_print_date($value, 'dayhour', 'tzuserrel');
			} else {
				$value = '';
			}
		} elseif ($type == 'duration') {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
			if (!is_null($value) && $value !== '') {
				$value = convertSecondToTime($value, 'allhourmin');
			}
		} elseif ($type == 'double' || $type == 'real') {
			if (!is_null($value) && $value !== '') {
				$value = price($value);
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked ';
			}
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER') < 2) {
				$value = '<input type="checkbox" '.$checked.' '.($moreparam ? $moreparam : '').' readonly disabled>';
			} else {
				$value = yn($value ? 1 : 0);
			}
		} elseif ($type == 'mail' || $type == 'email') {
			$value = dol_print_email($value, 0, 0, 0, 64, 1, 1);
		} elseif ($type == 'url') {
			$value = dol_print_url($value, '_blank', 32, 1);
		} elseif ($type == 'phone') {
			$value = dol_print_phone($value, '', 0, 0, '', '&nbsp;', 'phone');
		} elseif ($type == 'ip') {
			$value = dol_print_ip($value, 0);
		} elseif ($type == 'price') {
			if (!is_null($value) && $value !== '') {
				$value = price($value, 0, $langs, 0, 0, -1, $conf->currency);
			}
		} elseif ($type == 'select') {
			$value = isset($param['options'][(string) $value]) ? $param['options'][(string) $value] : '';
			if (strpos($value, "|") !== false) {
				$value = $langs->trans(explode('|', $value)[0]);
			} elseif (! is_numeric($value)) {
				$value = $langs->trans($value);
			}
		} elseif ($type == 'sellist') {
			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) > 2 && !empty($InfoFieldList[2])) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$filter_categorie = false;
			if (count($InfoFieldList) > 5) {
				if ($InfoFieldList[0] == 'categorie') {
					$filter_categorie = true;
				}
			}

			$sql = "SELECT ".$keyList;
			$sql .= ' FROM '.$this->db->prefix().$InfoFieldList[0];
			if (isset($InfoFieldList[4]) && strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			if ($selectkey == 'rowid' && empty($value)) {
				$sql .= " WHERE ".$selectkey." = 0";
			} elseif ($selectkey == 'rowid') {
				$sql .= " WHERE ".$selectkey." = ".((int) $value);
			} else {
				$sql .= " WHERE ".$selectkey." = '".$this->db->escape($value)."'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$filter_categorie) {
					$value = ''; // value was used, so now we reset it to use it to build final output
					$numrows = $this->db->num_rows($resql);
					if ($numrows) {
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);

						if (is_array($fields_label) && count($fields_label) > 1) {
							foreach ($fields_label as $field_toshow) {
								$translabel = '';
								if (!empty($obj->$field_toshow)) {
									$translabel = $langs->trans($obj->$field_toshow);
								}
								if ($translabel != $field_toshow) {
									$value .= dol_trunc($translabel, 18) . ' ';
								} else {
									$value .= $obj->$field_toshow . ' ';
								}
							}
						} else {
							$translabel = '';
							if (!empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$value = dol_trunc($translabel, 18);
							} else {
								$value = $obj->{$InfoFieldList[1]};
							}
						}
					}
				} else {
					require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

					$toprint = array();
					$obj = $this->db->fetch_object($resql);
					$c = new Categorie($this->db);
					$c->fetch($obj->rowid);
					$ways = $c->print_all_ways(); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
					foreach ($ways as $way) {
						$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #aaa"') . '>' . img_object('', 'category') . ' ' . $way . '</li>';
					}
					$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
				}
			} else {
				dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'radio') {
			$value = $param['options'][(string) $value];
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', (string) $value);
			$value = '';
			if (is_array($value_arr) && count($value_arr) > 0) {
				$toprint = array();
				foreach ($value_arr as $keyval => $valueval) {
					if (!empty($valueval)) {
						$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $param['options'][$valueval] . '</li>';
					}
				}
				if (!empty($toprint)) {
					$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
				}
			}
		} elseif ($type == 'chkbxlst') {
			$value_arr = (isset($value) ? explode(',', $value) : array());

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2].' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$filter_categorie = false;
			if (count($InfoFieldList) > 5) {
				if ($InfoFieldList[0] == 'categorie') {
					$filter_categorie = true;
				}
			}

			$sql = "SELECT ".$keyList;
			$sql .= ' FROM '.$this->db->prefix().$InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this).':showOutputField:$type=chkbxlst', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				if (!$filter_categorie) {
					$value = ''; // value was used, so now we reset it to use it to build final output
					$toprint = array();
					while ($obj = $this->db->fetch_object($resql)) {
						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							if (is_array($fields_label) && count($fields_label) > 1) {
								foreach ($fields_label as $field_toshow) {
									$translabel = '';
									if (!empty($obj->$field_toshow)) {
										$translabel = $langs->trans($obj->$field_toshow);
									}
									if ($translabel != $field_toshow) {
										$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . dol_trunc($translabel, 18) . '</li>';
									} else {
										$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $obj->$field_toshow . '</li>';
									}
								}
							} else {
								$translabel = '';
								if (!empty($obj->{$InfoFieldList[1]})) {
									$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								}
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . dol_trunc($translabel, 18) . '</li>';
								} else {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $obj->{$InfoFieldList[1]} . '</li>';
								}
							}
						}
					}
				} else {
					require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

					$toprint = array();
					while ($obj = $this->db->fetch_object($resql)) {
						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							$c = new Categorie($this->db);
							$c->fetch($obj->rowid);
							$ways = $c->print_all_ways(); // $ways[0] = "ccc2 >> ccc2a >> ccc2a1" with html formatted text
							foreach ($ways as $way) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories"' . ($c->color ? ' style="background: #' . $c->color . ';"' : ' style="background: #aaa"') . '>' . img_object('', 'category') . ' ' . $way . '</li>';
							}
						}
					}
				}
				$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">'.implode(' ', $toprint).'</ul></div>';
			} else {
				dol_syslog(get_class($this).'::showOutputField error '.$this->db->lasterror(), LOG_WARNING);
			}
		} elseif ($type == 'link') {
			$out = '';

			// only if something to display (perf)
			if ($value) {
				$param_list = array_keys($param['options']);
				// Example: $param_list='ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter[:Sortfield]]]'
				// Example: $param_list='ObjectClass:PathToClass:#getnomurlparam1=-1#getnomurlparam2=customer'

				$InfoFieldList = explode(":", $param_list[0]);

				$classname = $InfoFieldList[0];
				$classpath = $InfoFieldList[1];

				// Set $getnomurlparam1 et getnomurlparam2
				$getnomurlparam = 3;
				$getnomurlparam2 = '';
				$regtmp = array();
				if (preg_match('/#getnomurlparam1=([^#]*)/', $param_list[0], $regtmp)) {
					$getnomurlparam = $regtmp[1];
				}
				if (preg_match('/#getnomurlparam2=([^#]*)/', $param_list[0], $regtmp)) {
					$getnomurlparam2 = $regtmp[1];
				}

				if (!empty($classpath)) {
					dol_include_once($InfoFieldList[1]);

					if ($classname && !class_exists($classname)) {
						// from V19 of Dolibarr, In some cases link use element instead of class, example project_task
						// TODO use newObjectByElement() introduce in V20 by PR #30036 for better errors management
						$element_prop = getElementProperties($classname);
						if ($element_prop) {
							$classname = $element_prop['classname'];
						}
					}


					if ($classname && class_exists($classname)) {
						$object = new $classname($this->db);
						if ($object->element === 'product') {	// Special case for product because default valut of fetch are wrong
							$result = $object->fetch($value, '', '', '', 0, 1, 1);
						} else {
							$result = $object->fetch($value);
						}
						if ($result > 0) {
							if ($object->element === 'product') {
								$get_name_url_param_arr = array($getnomurlparam, $getnomurlparam2, 0, -1, 0, '', 0);
								if (isset($val['get_name_url_params'])) {
									$get_name_url_params = explode(':', $val['get_name_url_params']);
									if (!empty($get_name_url_params)) {
										$param_num_max = count($get_name_url_param_arr) - 1;
										foreach ($get_name_url_params as $param_num => $param_value) {
											if ($param_num > $param_num_max) {
												break;
											}
											$get_name_url_param_arr[$param_num] = $param_value;
										}
									}
								}

								/**
								 * @var Product $object
								 */
								$value = $object->getNomUrl($get_name_url_param_arr[0], $get_name_url_param_arr[1], $get_name_url_param_arr[2], $get_name_url_param_arr[3], $get_name_url_param_arr[4], $get_name_url_param_arr[5], $get_name_url_param_arr[6]);
							} else {
								$value = $object->getNomUrl($getnomurlparam, $getnomurlparam2);
							}
						} else {
							$value = '';
						}
					}
				} else {
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			} else {
				$value = '';
			}
		} elseif ($type == 'password') {
			$value = '<span class="opacitymedium">'.$langs->trans("Encrypted").'</span>';
			//$value = preg_replace('/./i', '*', $value);
		} elseif ($type == 'array') {
			if (is_array($value)) {
				$value = implode('<br>', $value);
			} else {
				dol_syslog(__METHOD__.' Expected array from dol_eval, but got '.gettype($value), LOG_ERR);
				return 'Error unexpected result from code evaluation';
			}
		} else {	// text|html|varchar
			if (!empty($value) && preg_match('/^text/', (string) $type) && !preg_match('/search_/', $keyprefix) && !empty($param['options'])) {
				$value = str_replace(',', "\n", $value);
			}
			$value = dol_htmlentitiesbr($value);
		}

		//print $type.'-'.$size.'-'.$value;
		$out = $value;

		return is_null($out) ? '' : $out;
	}

	/**
	 * clear validation message result for a field
	 *
	 * @param string $fieldKey Key of attribute to clear
	 * @return void
	 */
	public function clearFieldError($fieldKey)
	{
		$this->error = '';
		unset($this->validateFieldsErrors[$fieldKey]);
	}

	/**
	 * set validation error message a field
	 *
	 * @param string $fieldKey Key of attribute
	 * @param string $msg the field error message
	 * @return void
	 */
	public function setFieldError($fieldKey, $msg = '')
	{
		global $langs;
		if (empty($msg)) {
			$msg = $langs->trans("UnknownError");
		}

		$this->error = $this->validateFieldsErrors[$fieldKey] = $msg;
	}

	/**
	 * get field error message
	 *
	 * @param  string  $fieldKey            Key of attribute
	 * @return string						Error message of validation ('' if no error)
	 */
	public function getFieldError($fieldKey)
	{
		if (!empty($this->validateFieldsErrors[$fieldKey])) {
			return $this->validateFieldsErrors[$fieldKey];
		}
		return '';
	}

	/**
	 * Return validation test result for a field
	 *
	 * @param array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-2,5>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,2>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,comment?:string,validate?:int<0,1>}>	$fields	Array of properties of field to show
	 * @param  	string  $fieldKey           Key of attribute
	 * @param	string  $fieldValue         Value of attribute
	 * @return 	bool 						Return false if fail true on success, see $this->error for error message
	 */
	public function validateField($fields, $fieldKey, $fieldValue)
	{
		global $langs;

		if (!class_exists('Validate')) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/validate.class.php';
		}

		$this->clearFieldError($fieldKey);

		if (!array_key_exists($fieldKey, $fields) || !is_array($fields[$fieldKey])) {
			$this->setFieldError($fieldKey, $langs->trans('FieldNotFoundInObject'));
			return false;
		}

		$val = $fields[$fieldKey];

		$param = array();
		$param['options'] = array();
		$type  = $val['type'];

		$required = false;
		if (isset($val['notnull']) && $val['notnull'] === 1) {
			// 'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
			$required = true;
		}

		$maxSize = 0;
		$minSize = 0;

		//
		// PREPARE Elements
		//
		$reg = array();

		// Convert var to be able to share same code than showOutputField of extrafields
		if (preg_match('/varchar\((\d+)\)/', $type, $reg)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
			$maxSize = $reg[1];
		} elseif (preg_match('/varchar/', $type)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
		}

		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			$type = 'select';
		}

		if (!empty($val['type']) && preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type = 'link';
		}

		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
			$param['options'] = $val['arrayofkeyval'];
		}

		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type = 'link';
			$param['options'] = array($reg[1].':'.$reg[2] => $reg[1].':'.$reg[2]);
		} elseif (preg_match('/^sellist:(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2].':'.$reg[3].':'.$reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2].':'.$reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1].':'.$reg[2] => 'N');
			$type = 'sellist';
		}

		//
		// TEST Value
		//

		// Use Validate class to allow external Modules to use data validation part instead of concentrate all test here (factoring) or just for reuse
		$validate = new Validate($this->db, $langs);


		// little trick : to perform tests with good performances sort tests by quick to low

		//
		// COMMON TESTS
		//

		// Required test and empty value
		if ($required && !$validate->isNotEmptyString($fieldValue)) {
			$this->setFieldError($fieldKey, $validate->error);
			return false;
		} elseif (!$required && !$validate->isNotEmptyString($fieldValue)) {
			// if no value sent and the field is not mandatory, no need to perform tests
			return true;
		}

		// MAX Size test
		if (!empty($maxSize) && !$validate->isMaxLength($fieldValue, $maxSize)) {
			$this->setFieldError($fieldKey, $validate->error);
			return false;
		}

		// MIN Size test
		if (!empty($minSize) && !$validate->isMinLength($fieldValue, $minSize)) {
			$this->setFieldError($fieldKey, $validate->error);
			return false;
		}

		//
		// TESTS for TYPE
		//

		if (in_array($type, array('date', 'datetime', 'timestamp'))) {
			if (!$validate->isTimestamp($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'duration') {
			if (!$validate->isDuration($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif (in_array($type, array('double', 'real', 'price'))) {
			// is numeric
			if (!$validate->isNumeric($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'boolean') {
			if (!$validate->isBool($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'mail') {
			if (!$validate->isEmail($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			}
		} elseif ($type == 'url') {
			if (!$validate->isUrl($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'phone') {
			if (!$validate->isPhone($fieldValue)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'select' || $type == 'radio') {
			if (!isset($param['options'][$fieldValue])) {
				$this->error = $langs->trans('RequireValidValue');
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'sellist' || $type == 'chkbxlst') {
			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);
			$value_arr = explode(',', $fieldValue);
			$value_arr = array_map(array($this->db, 'escape'), $value_arr);

			$selectkey = "rowid";
			if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
				$selectkey = $InfoFieldList[2];
			}

			if (!$validate->isInDb($value_arr, $InfoFieldList[0], $selectkey)) {
				$this->setFieldError($fieldKey, $validate->error);
				return false;
			} else {
				return true;
			}
		} elseif ($type == 'link') {
			$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath'
			$InfoFieldList = explode(":", $param_list[0]);
			$classname = $InfoFieldList[0];
			$classpath = $InfoFieldList[1];
			if (!$validate->isFetchable($fieldValue, $classname, $classpath)) {
				$lastIsFetchableError = $validate->error;

				// from V19 of Dolibarr, In some cases link use element instead of class, example project_task
				if ($validate->isFetchableElement($fieldValue, $classname)) {
					return true;
				}

				$this->setFieldError($fieldKey, $lastIsFetchableError);
				return false;
			} else {
				return true;
			}
		}

		// if no test failed all is ok
		return true;
	}

	/**
	 * Function to show lines of extrafields with output data.
	 * This function is responsible to output the <tr> and <td> according to correct number of columns received into $params['colspan'] or <div> according to $display_type
	 *
	 * @param 	Extrafields $extrafields    Extrafield Object
	 * @param 	string      $mode           Show output ('view') or input ('create' or 'edit') for extrafield
	 * @param 	array<string,mixed>	$params	Optional parameters. Example: array('style'=>'class="oddeven"', 'colspan'=>$colspan)
	 * @param 	string      $keysuffix      Suffix string to add after name and id of field (can be used to avoid duplicate names)
	 * @param 	string      $keyprefix      Prefix string to add before name and id of field (can be used to avoid duplicate names)
	 * @param	string		$onetrtd		All fields in same tr td. Used by objectline_create.tpl.php for example.
	 * @param	string		$display_type	"card" for form display, "line" for document line display (extrafields on propal line, order line, etc...)
	 * @return 	string						String with html content to show
	 */
	public function showOptionals($extrafields, $mode = 'view', $params = null, $keysuffix = '', $keyprefix = '', $onetrtd = '', $display_type = 'card')
	{
		global $db, $conf, $langs, $action, $form, $hookmanager;

		if (!is_object($form)) {
			$form = new Form($db);
		}
		if (!is_object($extrafields)) {
			dol_syslog('Bad parameter extrafields for showOptionals', LOG_ERR);
			return 'Bad parameter extrafields for showOptionals';
		}
		if (!is_array($extrafields->attributes[$this->table_element])) {
			dol_syslog("extrafields->attributes was not loaded with extrafields->fetch_name_optionals_label(table_element);", LOG_WARNING);
		}

		$out = '';

		$parameters = array('mode' => $mode, 'params' => $params, 'keysuffix' => $keysuffix, 'keyprefix' => $keyprefix, 'display_type' => $display_type);
		$reshook = $hookmanager->executeHooks('showOptionals', $parameters, $this, $action); // Note that $action and $object may have been modified by hook

		if (empty($reshook)) {
			if (is_array($extrafields->attributes[$this->table_element]) && array_key_exists('label', $extrafields->attributes[$this->table_element]) && is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label']) > 0) {
				$out .= "\n";
				$out .= '<!-- commonobject:showOptionals --> ';
				$out .= "\n";

				$nbofextrafieldsshown = 0;
				$e = 0;	// var to manage the modulo (odd/even)

				$lastseparatorkeyfound = '';
				$extrafields_collapse_num = '';
				$extrafields_collapse_num_old = '';
				$i = 0;

				foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $label) {
					$i++;

					// Show only the key field in params  @phan-suppress-next-line PhanTypeArraySuspiciousNullable
					if (is_array($params) && array_key_exists('onlykey', $params) && $key != $params['onlykey']) {
						continue;
					}

					// Test on 'enabled' ('enabled' is different than 'list' = 'visibility')
					$enabled = 1;
					if ($enabled && isset($extrafields->attributes[$this->table_element]['enabled'][$key])) {
						$enabled = (int) dol_eval((string) $extrafields->attributes[$this->table_element]['enabled'][$key], 1, 1, '2');
					}
					if (empty($enabled)) {
						continue;
					}

					$visibility = 1;
					if (isset($extrafields->attributes[$this->table_element]['list'][$key])) {
						$visibility = (int) dol_eval($extrafields->attributes[$this->table_element]['list'][$key], 1, 1, '2');
					}

					$perms = 1;
					if ($perms && isset($extrafields->attributes[$this->table_element]['perms'][$key])) {
						$perms = (int) dol_eval($extrafields->attributes[$this->table_element]['perms'][$key], 1, 1, '2');
					}

					if (($mode == 'create') && !in_array(abs($visibility), array(1, 3))) {
						continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list
					} elseif (($mode == 'edit') && !in_array(abs($visibility), array(1, 3, 4))) {
						continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list and <> 4 = not visible at the creation
					} elseif ($mode == 'view' && empty($visibility)) {
						continue;
					}
					if (empty($perms)) {
						continue;
					}

					// Load language if required
					if (!empty($extrafields->attributes[$this->table_element]['langfile'][$key])) {
						$langs->load($extrafields->attributes[$this->table_element]['langfile'][$key]);
					}

					$colspan = 0;
					$value = null;
					if (is_array($params) && count($params) > 0 && $display_type == 'card') {
						if (array_key_exists('cols', $params)) {
							$colspan = $params['cols'];
						} elseif (array_key_exists('colspan', $params)) {	// For backward compatibility. Use cols instead now.
							$reg = array();
							if (preg_match('/colspan="(\d+)"/', $params['colspan'], $reg)) {
								$colspan = $reg[1];
							} else {
								$colspan = $params['colspan'];
							}
						}
					}
					$colspan = intval($colspan);

					switch ($mode) {
						case "view":
							$value = ((!empty($this->array_options) && array_key_exists("options_".$key.$keysuffix, $this->array_options)) ? $this->array_options["options_".$key.$keysuffix] : null); // Value may be cleaned or formatted later
							break;
						case "create":
						case "edit":
							// We get the value of property found with GETPOST so it takes into account:
							// default values overwrite, restore back to list link, ... (but not 'default value in database' of field)
							$check = 'alphanohtml';
							if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('html', 'text'))) {
								$check = 'restricthtml';
							}
							$getposttemp = GETPOST($keyprefix.'options_'.$key.$keysuffix, $check, 3); // GETPOST can get value from GET, POST or setup of default values overwrite.
							// GETPOST("options_" . $key) can be 'abc' or array(0=>'abc')
							if (is_array($getposttemp) || $getposttemp != '' || GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix)) {
								if (is_array($getposttemp)) {
									// $getposttemp is an array but following code expects a comma separated string
									$value = implode(",", $getposttemp);
								} else {
									$value = $getposttemp;
								}
							} elseif (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('int'))) {
								$value = (!empty($this->array_options["options_".$key]) || $this->array_options["options_".$key] === '0') ? $this->array_options["options_".$key] : '';
							} else {
								$value = (!empty($this->array_options["options_".$key]) ? $this->array_options["options_".$key] : ''); // No GET, no POST, no default value, so we take value of object.
							}
							//var_dump($keyprefix.' - '.$key.' - '.$keysuffix.' - '.$keyprefix.'options_'.$key.$keysuffix.' - '.$this->array_options["options_".$key.$keysuffix].' - '.$getposttemp.' - '.$value);
							break;
					}

					$nbofextrafieldsshown++;

					// Output value of the current field
					if ($extrafields->attributes[$this->table_element]['type'][$key] == 'separate') {
						$extrafields_collapse_num = $key;
						/*
						$extrafield_param = $extrafields->attributes[$this->table_element]['param'][$key];
						if (!empty($extrafield_param) && is_array($extrafield_param)) {
							$extrafield_param_list = array_keys($extrafield_param['options']);

							if (count($extrafield_param_list) > 0) {
								$extrafield_collapse_display_value = intval($extrafield_param_list[0]);

								if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
									//$extrafields_collapse_num = $extrafields->attributes[$this->table_element]['pos'][$key];
									$extrafields_collapse_num = $key;
								}
							}
						}
						*/

						// if colspan=0 or 1, the second column is not extended, so the separator must be on 2 columns
						$out .= $extrafields->showSeparator($key, $this, ($colspan ? $colspan + 1 : 2), $display_type, $mode);

						$lastseparatorkeyfound = $key;
					} else {
						$collapse_group = $extrafields_collapse_num.(!empty($this->id) ? '_'.$this->id : '');

						$class = (!empty($extrafields->attributes[$this->table_element]['hidden'][$key]) ? 'hideobject ' : '');
						$csstyle = '';
						if (is_array($params) && count($params) > 0) {
							if (array_key_exists('class', $params)) {
								$class .= $params['class'].' ';
							}
							if (array_key_exists('style', $params)) {
								$csstyle = $params['style'];
							}
						}

						// add html5 elements
						$domData  = ' data-element="extrafield"';
						$domData .= ' data-targetelement="'.$this->element.'"';
						$domData .= ' data-targetid="'.$this->id.'"';

						$html_id = (empty($this->id) ? '' : 'extrarow-'.$this->element.'_'.$key.'_'.$this->id);
						if ($display_type == 'card') {
							if (getDolGlobalString('MAIN_EXTRAFIELDS_USE_TWO_COLUMS') && ($e % 2) == 0) {
								$colspan = 0;
							}

							if ($action == 'selectlines') {
								$colspan++;
							}
						}

						// Convert date into timestamp format (value in memory must be a timestamp)
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('date'))) {
							$datenotinstring = null;
							if (array_key_exists('options_'.$key, $this->array_options)) {
								$datenotinstring = $this->array_options['options_'.$key];
								if (!is_numeric($this->array_options['options_'.$key])) {	// For backward compatibility
									$datenotinstring = $this->db->jdate($datenotinstring);
								}
							}
							$datekey = $keyprefix.'options_'.$key.$keysuffix;
							$value = (GETPOSTISSET($datekey)) ? dol_mktime(12, 0, 0, GETPOSTINT($datekey.'month', 3), GETPOSTINT($datekey.'day', 3), GETPOSTINT($datekey.'year', 3)) : $datenotinstring;
						}
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('datetime'))) {
							$datenotinstring = null;
							if (array_key_exists('options_'.$key, $this->array_options)) {
								$datenotinstring = $this->array_options['options_'.$key];
								if (!is_numeric($this->array_options['options_'.$key])) {	// For backward compatibility
									$datenotinstring = $this->db->jdate($datenotinstring);
								}
							}
							$timekey = $keyprefix.'options_'.$key.$keysuffix;
							$value = (GETPOSTISSET($timekey)) ? dol_mktime(GETPOSTINT($timekey.'hour', 3), GETPOSTINT($timekey.'min', 3), GETPOSTINT($timekey.'sec', 3), GETPOSTINT($timekey.'month', 3), GETPOSTINT($timekey.'day', 3), GETPOSTINT($timekey.'year', 3), 'tzuserrel') : $datenotinstring;
						}
						// Convert float submitted string into real php numeric (value in memory must be a php numeric)
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('price', 'double'))) {
							if (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) {
								$value = price2num($value);
							} elseif (isset($this->array_options['options_'.$key])) {
								$value = $this->array_options['options_'.$key];
							}
						}

						// HTML, text, select, integer and varchar: take into account default value in database if in create mode
						if (in_array($extrafields->attributes[$this->table_element]['type'][$key], array('html', 'text', 'varchar', 'select', 'radio', 'int', 'boolean'))) {
							if ($action == 'create' || $mode == 'create') {
								$value = (GETPOSTISSET($keyprefix.'options_'.$key.$keysuffix) || $value) ? $value : $extrafields->attributes[$this->table_element]['default'][$key];
							}
						}

						$labeltoshow = $langs->trans($label);
						$helptoshow = $langs->trans($extrafields->attributes[$this->table_element]['help'][$key]);
						if ($display_type == 'card') {
							$out .= '<tr '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="field_options_'.$key.' '.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$collapse_group.'" '.$domData.' >';
							if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER') && ($action == 'view' || $action == 'valid' || $action == 'editline' || $action == 'confirm_valid' || $action == 'confirm_cancel')) {
								$out .= '<td></td>';
							}
							$out .= '<td class="'.(empty($params['tdclass']) ? 'titlefieldcreate' : $params['tdclass']).' wordbreak';
							if ($extrafields->attributes[$this->table_element]['type'][$key] == 'text') {
								$out .= ' tdtop';
							}
						} elseif ($display_type == 'line') {
							$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').$csstyle.' class="fieldline_options_'.$key.' '.$class.$this->element.'_extras_'.$key.' trextrafields_collapse'.$collapse_group.'" '.$domData.' >';
							$out .= '<div style="display: inline-block; padding-right:4px" class="wordbreak';
						}
						//$out .= "titlefield";
						//if (GETPOST('action', 'restricthtml') == 'create') $out.='create';
						// BUG #11554 : For public page, use red dot for required fields, instead of bold label
						$tpl_context = isset($params["tpl_context"]) ? $params["tpl_context"] : "none";
						if ($tpl_context != "public") {	// Public page : red dot instead of fieldrequired characters
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) {
								$out .= ' fieldrequired';
							}
						}
						$out .= '">';
						if ($tpl_context == "public") {	// Public page : red dot instead of fieldrequired characters
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) {
								$out .= $form->textwithpicto($labeltoshow, $helptoshow);
							} else {
								$out .= $labeltoshow;
							}
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) {
								$out .= '&nbsp;<span style="color: red">*</span>';
							}
						} else {
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) {
								$out .= $form->textwithpicto($labeltoshow, $helptoshow);
							} else {
								$out .= $labeltoshow;
							}
						}

						$out .= ($display_type == 'card' ? '</td>' : '</div>');

						// Second column
						$html_id = !empty($this->id) ? $this->element.'_extras_'.$key.'_'.$this->id : '';
						if ($display_type == 'card') {
							// a first td column was already output (and may be another on before if MAIN_VIEW_LINE_NUMBER set), so this td is the next one
							$out .= '<td '.($html_id ? 'id="'.$html_id.'" ' : '').' class="valuefieldcreate '.$this->element.'_extras_'.$key;
							$out .= '" '.($colspan ? ' colspan="'.$colspan.'"' : '');
							$out .= '>';
						} elseif ($display_type == 'line') {
							$out .= '<div '.($html_id ? 'id="'.$html_id.'" ' : '').' style="display: inline-block" class="valuefieldcreate '.$this->element.'_extras_'.$key.' extra_inline_'.$extrafields->attributes[$this->table_element]['type'][$key].'">';
						}

						switch ($mode) {
							case "view":
								$out .= $extrafields->showOutputField($key, $value, '', $this->table_element);
								break;
							case "create":
								$listoftypestoshowpicto = explode(',', getDolGlobalString('MAIN_TYPES_TO_SHOW_PICOT', 'email,phone,ip,password'));
								if (in_array($extrafields->attributes[$this->table_element]['type'][$key], $listoftypestoshowpicto)) {
									$out .= getPictoForType($extrafields->attributes[$this->table_element]['type'][$key], ($extrafields->attributes[$this->table_element]['type'][$key] == 'text' ? 'tdtop' : ''));
								}
								//$out .= '<!-- type = '.$extrafields->attributes[$this->table_element]['type'][$key].' -->';
								$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $this->table_element);
								break;
							case "edit":
								$listoftypestoshowpicto = explode(',', getDolGlobalString('MAIN_TYPES_TO_SHOW_PICOT', 'email,phone,ip,password'));
								if (in_array($extrafields->attributes[$this->table_element]['type'][$key], $listoftypestoshowpicto)) {
									$out .= getPictoForType($extrafields->attributes[$this->table_element]['type'][$key], ($extrafields->attributes[$this->table_element]['type'][$key] == 'text' ? 'tdtop' : ''));
								}
								$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $this->id, $this->table_element);
								break;
						}

						$out .= ($display_type == 'card' ? '</td>' : '</div>');
						$out .= ($display_type == 'card' ? '</tr>'."\n" : '</div>');
						$e++;
					}
				}
				$out .= "\n";
				// Add code to manage list depending on others
				if (!empty($conf->use_javascript_ajax)) {
					$out .= $this->getJSListDependancies();
				}

				$out .= '<!-- commonobject:showOptionals end --> '."\n";

				if (empty($nbofextrafieldsshown)) {
					$out = '';
				}
			}
		}

		$out .= $hookmanager->resPrint;

		return $out;
	}

	/**
	 * @param 	string 	$type	Type for prefix
	 * @return 	string			JavaScript code to manage dependency
	 */
	public function getJSListDependancies($type = '_extra')
	{
		$out = '
					<script nonce="'.getNonce().'">
					jQuery(document).ready(function() {
						function showOptions'.$type.'(child_list, parent_list, orig_select)
						{
							var val = $("select[name=\""+parent_list+"\"]").val();
							var parentVal = parent_list + ":" + val;
							if(typeof val == "string"){
								if(val != "") {
									var options = orig_select.find("option[parent=\""+parentVal+"\"]").clone();
									$("select[name=\""+child_list+"\"] option[parent]").remove();
									$("select[name=\""+child_list+"\"]").append(options);
								} else {
									var options = orig_select.find("option[parent]").clone();
									$("select[name=\""+child_list+"\"] option[parent]").remove();
									$("select[name=\""+child_list+"\"]").append(options);
								}
							} else if(val > 0) {
								var options = orig_select.find("option[parent=\""+parentVal+"\"]").clone();
								$("select[name=\""+child_list+"\"] option[parent]").remove();
								$("select[name=\""+child_list+"\"]").append(options);
							} else {
								var options = orig_select.find("option[parent]").clone();
								$("select[name=\""+child_list+"\"] option[parent]").remove();
								$("select[name=\""+child_list+"\"]").append(options);
							}
						}
						function setListDependencies'.$type.'() {
							jQuery("select option[parent]").parent().each(function() {
								var orig_select = {};
								var child_list = $(this).attr("name");
								orig_select[child_list] = $(this).clone();
								var parent = $(this).find("option[parent]:first").attr("parent");
								var infos = parent.split(":");
								var parent_list = infos[0];

								//Hide daughters lists
								if ($("#"+child_list).val() == 0 && $("#"+parent_list).val() == 0){
									$("#"+child_list).hide();
								//Show mother lists
								} else if ($("#"+parent_list).val() != 0){
									$("#"+parent_list).show();
								}
								//Show the child list if the parent list value is selected
								$("select[name=\""+parent_list+"\"]").click(function() {
									if ($(this).val() != 0){
										$("#"+child_list).show()
									}
								});

								//When we change parent list
								$("select[name=\""+parent_list+"\"]").change(function() {
									showOptions'.$type.'(child_list, parent_list, orig_select[child_list]);
									//Select the value 0 on child list after a change on the parent list
									$("#"+child_list).val(0).trigger("change");
									//Hide child lists if the parent value is set to 0
									if ($(this).val() == 0){
								   		$("#"+child_list).hide();
									}
								});
							});
						}

						setListDependencies'.$type.'();
					});
					</script>'."\n";
		return $out;
	}

	/**
	 * Returns the rights used for this class
	 *
	 * @return null|int|stdClass		Object of permission for the module
	 */
	public function getRights()
	{
		global $user;

		$module = empty($this->module) ? '' : $this->module;
		$element = $this->element;

		if ($element == 'facturerec') {
			$element = 'facture';
		} elseif ($element == 'invoice_supplier_rec') {
			return !$user->hasRight('fournisseur', 'facture') ? null : $user->hasRight('fournisseur', 'facture');
		} elseif ($module && $user->hasRight($module, $element)) {
			// for modules built with ModuleBuilder
			return $user->hasRight($module, $element);
		}

		return $user->rights->$element;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 * This function is meant to be called from replaceThirdparty with the appropriate tables
	 * Column name fk_soc MUST exist.
	 *
	 * @param  DoliDB	$dbs			Database handler
	 * @param  int		$origin_id		Old thirdparty id (the thirdparty to delete)
	 * @param  int		$dest_id		New thirdparty id (the thirdparty that will received element of the other)
	 * @param  string[]	$tables			Tables that need to be changed
	 * @param  int<0,1>	$ignoreerrors	Ignore errors. Return true even if errors. We need this when replacement can fails like for categories (categorie of old thirdparty may already exists on new one)
	 * @return bool						True if success, False if error
	 */
	public static function commonReplaceThirdparty(DoliDB $dbs, $origin_id, $dest_id, array $tables, $ignoreerrors = 0)
	{
		foreach ($tables as $table) {
			$sql = 'UPDATE '.$dbs->prefix().$table.' SET fk_soc = '.((int) $dest_id).' WHERE fk_soc = '.((int) $origin_id);

			if (!$dbs->query($sql)) {
				if ($ignoreerrors) {
					return true; // FIXME Not enough. If there is A-B on the kept thirdparty and B-C on the old one, we must get A-B-C after merge. Not A-B.
				}
				//$this->errors = $db->lasterror();
				return false;
			}
		}

		return true;
	}

	/**
	 * Function used to replace a product id with another one.
	 * This function is meant to be called from replaceProduct with the appropriate tables
	 * Column name fk_product MUST be used to identify products
	 *
	 * @param  DoliDB		$dbs			Database handler
	 * @param  int			$origin_id		Old product id (the product to delete)
	 * @param  int 			$dest_id		New product id (the product that will received element of the other)
	 * @param  string[]		$tables			Tables that need to be changed
	 * @param  int<0,1>		$ignoreerrors	Ignore errors. Return true even if errors. We need this when replacement can fails like for categories (categorie of old product may already exists on new one)
	 * @return bool							True if success, False if error
	 */
	public static function commonReplaceProduct(DoliDB $dbs, $origin_id, $dest_id, array $tables, $ignoreerrors = 0)
	{
		foreach ($tables as $table) {
			$sql = 'UPDATE '.MAIN_DB_PREFIX.$table.' SET fk_product = '.((int) $dest_id).' WHERE fk_product = '.((int) $origin_id);

			if (!$dbs->query($sql)) {
				if ($ignoreerrors) {
					return true; // TODO Not enough. If there is A-B on kept product and B-C on old one, we must get A-B-C after merge. Not A-B.
				}
				//$this->errors = $db->lasterror();
				return false;
			}
		}

		return true;
	}

	/**
	 * Get buy price to use for margin calculation. This function is called when buy price is unknown.
	 *	 Set buy price = sell price if ForceBuyingPriceIfNull configured,
	 *   elseif calculation MARGIN_TYPE = 'costprice' and costprice is defined, use costprice as buyprice
	 *	 elseif calculation MARGIN_TYPE = 'pmp' and pmp is calculated, use pmp as buyprice
	 *	 else set min buy price as buy price
	 *
	 * @param float		$unitPrice			Product unit price
	 * @param float		$discountPercent	Line discount percent
	 * @param int		$fk_product			Product id
	 * @return float|int<-1,-1>				Return buy price if OK, integer <0 if KO
	 */
	public function defineBuyPrice($unitPrice = 0.0, $discountPercent = 0.0, $fk_product = 0)
	{
		global $conf;

		$buyPrice = 0;

		if (($unitPrice > 0) && (isset($conf->global->ForceBuyingPriceIfNull) && getDolGlobalInt('ForceBuyingPriceIfNull') > 0)) {
			// When ForceBuyingPriceIfNull is set
			$buyPrice = $unitPrice * (1 - $discountPercent / 100);
		} else {
			// Get cost price for margin calculation
			if (!empty($fk_product) && $fk_product > 0) {
				$result = 0;
				if (getDolGlobalString('MARGIN_TYPE') == 'costprice') {
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$product = new Product($this->db);
					$result = $product->fetch($fk_product);
					if ($result <= 0) {
						$this->errors[] = 'ErrorProductIdDoesNotExists';
						return -1;
					}
					if ($product->cost_price > 0) {
						$buyPrice = $product->cost_price;
					} elseif ($product->pmp > 0) {
						$buyPrice = $product->pmp;
					}
				} elseif (getDolGlobalString('MARGIN_TYPE') == 'pmp') {
					require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
					$product = new Product($this->db);
					$result = $product->fetch($fk_product);
					if ($result <= 0) {
						$this->errors[] = 'ErrorProductIdDoesNotExists';
						return -1;
					}
					if ($product->pmp > 0) {
						$buyPrice = $product->pmp;
					}
				}

				if (empty($buyPrice) && isset($conf->global->MARGIN_TYPE) && in_array($conf->global->MARGIN_TYPE, array('1', 'pmp', 'costprice'))) {
					require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
					$productFournisseur = new ProductFournisseur($this->db);
					if (($result = $productFournisseur->find_min_price_product_fournisseur($fk_product)) > 0) {
						$buyPrice = $productFournisseur->fourn_unitprice;
					} elseif ($result < 0) {
						$this->errors[] = $productFournisseur->error;
						return -2;
					}
				}
			}
		}
		return $buyPrice;
	}

	/**
	 * Function used to get the logos or photos of an object
	 *
	 * @param 	string	$modulepart		Module part
	 * @param 	string	$imagesize		Image size ('', 'mini' or 'small')
	 * @return	array{dir:string,file:string,originalfile:string,altfile:string,email:string,capture:string}	Array of data to show photo
	 */
	public function getDataToShowPhoto($modulepart, $imagesize)
	{
		// See getDataToShowPhoto() implemented by Product for example.
		return array('dir' => '', 'file' => '', 'originalfile' => '', 'altfile' => '', 'email' => '', 'capture' => '');
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show photos of an object (nbmax maximum), into several columns
	 *
	 *  @param		string					$modulepart		'product', 'ticket', ...
	 *  @param      string					$sdir        	Directory to scan (full absolute path)
	 *  @param      int<0,1>|''|'small'		$size        	0 or ''=original size, 1 or 'small'=use thumbnail if possible
	 *  @param      int						$nbmax       	Nombre maximum de photos (0=pas de max)
	 *  @param      int						$nbbyrow     	Number of image per line or -1 to use div separator or 0 to use no separator. Used only if size=1 or 'small'.
	 * 	@param		int						$showfilename	1=Show filename
	 * 	@param		int						$showaction		1=Show icon with action links (resize, delete)
	 * 	@param		int						$maxHeight		Max height of original image when size='small' (so we can use original even if small requested). If 0, always use 'small' thumb image.
	 * 	@param		int						$maxWidth		Max width of original image when size='small'
	 *  @param      int     				$nolink         Do not add a href link to view enlarged imaged into a new tab
	 *  @param      int|string  			$overwritetitle Do not add title tag on image
	 *  @param		int						$usesharelink	Use the public shared link of image (if not available, the 'nophoto' image will be shown instead)
	 *  @param		string					$cache			A string if we want to use a cached version of image
	 *  @param		string					$addphotorefcss	Add CSS to img of photos
	 *  @return     string									Html code to show photo. Number of photos shown is saved in this->nbphoto
	 */
	public function show_photos($modulepart, $sdir, $size = 0, $nbmax = 0, $nbbyrow = 5, $showfilename = 0, $showaction = 0, $maxHeight = 120, $maxWidth = 160, $nolink = 0, $overwritetitle = 0, $usesharelink = 0, $cache = '', $addphotorefcss = 'photoref')
	{
		// phpcs:enable
		global $user, $langs;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

		$sortfield = 'position_name';
		$sortorder = 'asc';

		$dir = $sdir.'/';
		$pdir = '/';

		$dir .= get_exdir(0, 0, 0, 0, $this, $modulepart);
		$pdir .= get_exdir(0, 0, 0, 0, $this, $modulepart);

		// For backward compatibility
		if ($modulepart == 'product') {
			if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
				$dir = $sdir.'/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
				$pdir = '/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
			}
		}
		if ($modulepart == 'category') {
			$dir = $sdir.'/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
			$pdir = '/'.get_exdir($this->id, 2, 0, 0, $this, $modulepart).$this->id."/photos/";
		}

		// Defined relative dir to DOL_DATA_ROOT
		$relativedir = '';
		if ($dir) {
			$relativedir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);
			$relativedir = preg_replace('/^[\\/]/', '', $relativedir);
			$relativedir = preg_replace('/[\\/]$/', '', $relativedir);
		}

		$dirthumb = $dir.'thumbs/';
		$pdirthumb = $pdir.'thumbs/';

		$return = '<!-- Photo -->'."\n";
		$nbphoto = 0;

		$filearray = dol_dir_list($dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

		/*if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO'))    // For backward compatibility, we scan also old dirs
		 {
		 $filearrayold=dol_dir_list($dirold,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		 $filearray=array_merge($filearray, $filearrayold);
		 }*/

		completeFileArrayWithDatabaseInfo($filearray, $relativedir);

		if (count($filearray)) {
			if ($sortfield && $sortorder) {
				$filearray = dol_sort_array($filearray, $sortfield, $sortorder);
			}

			foreach ($filearray as $key => $val) {
				$photo = '';
				$file = $val['name'];

				//if (dol_is_file($dir.$file) && image_format_supported($file) >= 0)
				if (image_format_supported($file) >= 0) {
					$nbphoto++;
					$photo = $file;
					$viewfilename = $file;

					if ($size == 1 || $size == 'small') {   // Format vignette
						// Find name of thumb file
						$photo_vignette = basename(getImageFileNameForSize($dir.$file, '_small'));
						if (!dol_is_file($dirthumb.$photo_vignette)) {
							// The thumb does not exists, so we will use the original file
							$dirthumb = $dir;
							$pdirthumb = $pdir;
							$photo_vignette = basename($file);
						}

						// Get filesize of original file
						$imgarray = dol_getImageSize($dir.$photo);

						if ($nbbyrow > 0) {
							if ($nbphoto == 1) {
								$return .= '<table class="valigntop center centpercent" style="border: 0; padding: 2px; border-spacing: 2px; border-collapse: separate;">';
							}

							if ($nbphoto % $nbbyrow == 1) {
								$return .= '<tr class="center valignmiddle" style="border: 1px">';
							}
							$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%" class="photo">'."\n";
						} elseif ($nbbyrow < 0) {
							$return .= '<div class="inline-block">'."\n";
						}

						$relativefile = preg_replace('/^\//', '', $pdir.$photo);
						if (empty($nolink)) {
							$urladvanced = getAdvancedPreviewUrl($modulepart, $relativefile, 0, 'entity='.$this->entity);
							if ($urladvanced) {
								$return .= '<a href="'.$urladvanced.'">';
							} else {
								$return .= '<a href="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'" class="aphoto" target="_blank" rel="noopener noreferrer">';
							}
						}

						// Show image (width height=$maxHeight)
						// Si fichier vignette disponible et image source trop grande, on utilise la vignette, sinon on utilise photo origine
						$alt = $langs->transnoentitiesnoconv('File').': '.$relativefile;
						$alt .= ' - '.$langs->transnoentitiesnoconv('Size').': '.$imgarray['width'].'x'.$imgarray['height'];
						if ($overwritetitle) {
							if (is_numeric($overwritetitle)) {
								$alt = '';
							} else {
								$alt = $overwritetitle;
							}
						}
						if (empty($cache) && !empty($val['label'])) {
							// label is md5 of file
							// use it in url to say we want to cache this version of the file
							$cache = $val['label'];
						}
						if ($usesharelink) {
							if (array_key_exists('share', $val) && $val['share']) {
								if (empty($maxHeight) || ($photo_vignette && $imgarray['height'] > $maxHeight)) {
									$return .= '<!-- Show original file (thumb not yet available with shared links) -->';
									$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'"'.($maxHeight ? ' height="'.$maxHeight.'"' : '').' src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).($cache ? '&cache='.urlencode($cache) : '').'" title="'.dol_escape_htmltag($alt).'">';
								} else {
									$return .= '<!-- Show original file -->';
									$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?hashp='.urlencode($val['share']).($cache ? '&cache='.urlencode($cache) : '').'" title="'.dol_escape_htmltag($alt).'">';
								}
							} else {
								$return .= '<!-- Show nophoto file (because file is not shared) -->';
								$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" title="'.dol_escape_htmltag($alt).'">';
							}
						} else {
							if (empty($maxHeight) || ($photo_vignette && $imgarray['height'] > $maxHeight)) {
								$return .= '<!-- Show thumb -->';
								$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').' maxwidth150onsmartphone maxwidth200"'.($maxHeight ? ' height="'.$maxHeight.'"' : '').' src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.($cache ? '&cache='.urlencode($cache) : '').'&file='.urlencode($pdirthumb.$photo_vignette).'" title="'.dol_escape_htmltag($alt).'">';
							} else {
								$return .= '<!-- Show original file -->';
								$return .= '<img class="photo photowithmargin'.($addphotorefcss ? ' '.$addphotorefcss : '').'" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.($cache ? '&cache='.urlencode($cache) : '').'&file='.urlencode($pdir.$photo).'" title="'.dol_escape_htmltag($alt).'">';
							}
						}

						if (empty($nolink)) {
							$return .= '</a>';
						}

						if ($showfilename) {
							$return .= '<br>'.$viewfilename;
						}
						if ($showaction) {
							$return .= '<br>';
							// If $photo_vignette set, we add a link to generate thumbs if file is an image and width or height higher than limits
							if ($photo_vignette && (image_format_supported($photo) > 0) && ((isset($imgarray['width']) && $imgarray['width'] > $maxWidth) || (isset($imgarray['width']) && $imgarray['width'] > $maxHeight))) {
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=addthumb&token='.newToken().'&file='.urlencode($pdir.$viewfilename).'">'.img_picto($langs->trans('GenerateThumb'), 'refresh').'&nbsp;&nbsp;</a>';
							}
							// Special case for product
							if ($modulepart == 'product' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
								// Link to resize
								$return .= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"), 'resize', '').'</a> &nbsp; ';

								// Link to delete
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=delete&token='.newToken().'&file='.urlencode($pdir.$viewfilename).'">';
								$return .= img_delete().'</a>';
							}
						}
						$return .= "\n";

						if ($nbbyrow > 0) {
							$return .= '</td>';
							if (($nbphoto % $nbbyrow) == 0) {
								$return .= '</tr>';
							}
						} elseif ($nbbyrow < 0) {
							$return .= '</div>'."\n";
						}
					}

					if (empty($size)) {     // Format origine
						$return .= '<img class="photo photowithmargin" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.$this->entity.'&file='.urlencode($pdir.$photo).'">';

						if ($showfilename) {
							$return .= '<br>'.$viewfilename;
						}
						if ($showaction) {
							// Special case for product
							if ($modulepart == 'product' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
								// Link to resize
								$return .= '<a href="'.DOL_URL_ROOT.'/core/photos_resize.php?modulepart='.urlencode('produit|service').'&id='.$this->id.'&file='.urlencode($pdir.$viewfilename).'" title="'.dol_escape_htmltag($langs->trans("Resize")).'">'.img_picto($langs->trans("Resize"), 'resize', '').'</a> &nbsp; ';

								// Link to delete
								$return .= '<a href="'.$_SERVER["PHP_SELF"].'?id='.$this->id.'&action=delete&token='.newToken().'&file='.urlencode($pdir.$viewfilename).'">';
								$return .= img_delete().'</a>';
							}
						}
					}

					// On continue ou on arrete de boucler ?
					if ($nbmax && $nbphoto >= $nbmax) {
						break;
					}
				}
			}

			if ($size == 1 || $size == 'small') {
				if ($nbbyrow > 0) {
					// Ferme tableau
					while ($nbphoto % $nbbyrow) {
						$return .= '<td style="width: '.ceil(100 / $nbbyrow).'%">&nbsp;</td>';
						$nbphoto++;
					}

					if ($nbphoto) {
						$return .= '</table>';
					}
				}
			}
		}

		$this->nbphoto = $nbphoto;

		return $return;
	}


	/**
	 * Function test if type is array
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return  bool			true if array
	 */
	protected function isArray($info)
	{
		if (is_array($info)) {
			if (isset($info['type']) && $info['type'] == 'array') {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Function test if type is date
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return  bool			true if date
	 */
	public function isDate($info)
	{
		if (isset($info['type']) && ($info['type'] == 'date' || $info['type'] == 'datetime' || $info['type'] == 'timestamp')) {
			return true;
		}
		return false;
	}

	/**
	 * Function test if type is duration
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return  bool			true if field of type duration
	 */
	public function isDuration($info)
	{
		if (is_array($info)) {
			if (isset($info['type']) && ($info['type'] == 'duration')) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Function test if type is integer
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	Properties of field
	 * @return  bool			true if integer
	 */
	public function isInt($info)
	{
		if (is_array($info)) {
			if (isset($info['type']) && (preg_match('/(^int|int$)/i', $info['type']))) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Function test if type is float
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return  bool			true if float
	 */
	public function isFloat($info)
	{
		if (is_array($info)) {
			if (isset($info['type']) && (preg_match('/^(double|real|price)/i', $info['type']))) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Function test if type is text
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	Properties of field
	 * @return  bool			true if type text
	 */
	public function isText($info)
	{
		if (is_array($info)) {
			if (isset($info['type']) && $info['type'] == 'text') {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Function test if field can be null
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return  bool			true if it can be null
	 */
	protected function canBeNull($info)
	{
		if (is_array($info)) {
			if (array_key_exists('notnull', $info) && $info['notnull'] != '1') {
				return true;
			} else {
				return false;
			}
		}
		return true;
	}

	/**
	 * Function test if field is forced to null if zero or empty
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return  bool			true if forced to null
	 */
	protected function isForcedToNullIfZero($info)
	{
		if (is_array($info)) {
			if (array_key_exists('notnull', $info) && $info['notnull'] == '-1') {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	/**
	 * Function test if is indexed
	 *
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$info	content information of field
	 * @return                  bool
	 */
	protected function isIndex($info)
	{
		if (is_array($info)) {
			if (array_key_exists('index', $info) && $info['index'] == true) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}


	/**
	 * Function to return the array of data key-value from the ->fields and all the ->properties of an object.
	 *
	 * Note: $this->${field} are set by the page that make the createCommon() or the updateCommon().
	 * $this->${field} should be a clean and string value (so date are formatted for SQL insert).
	 *
	 * @return array<string,null|int|float|string>	Array with all values of each property to update
	 */
	protected function setSaveQuery()
	{
		global $conf;

		$queryarray = array();
		foreach ($this->fields as $field => $info) {	// Loop on definition of fields
			// Depending on field type ('datetime', ...)
			if ($this->isDate($info)) {
				if (empty($this->{$field})) {
					$queryarray[$field] = null;
				} else {
					$queryarray[$field] = $this->db->idate($this->{$field});
				}
			} elseif ($this->isDuration($info)) {
				// $this->{$field} may be null, '', 0, '0', 123, '123'
				if ((isset($this->{$field}) && $this->{$field} != '') || !empty($info['notnull'])) {
					if (!isset($this->{$field})) {
						if (!empty($info['default'])) {
							$queryarray[$field] = $info['default'];
						} else {
							$queryarray[$field] = 0;
						}
					} else {
						$queryarray[$field] = (int) $this->{$field};		// If '0', it may be set to null later if $info['notnull'] == -1
					}
				} else {
					$queryarray[$field] = null;
				}
			} elseif ($this->isInt($info) || $this->isFloat($info)) {
				if ($field == 'entity' && is_null($this->{$field})) {
					$queryarray[$field] = ((int) $conf->entity);
				} else {
					// $this->{$field} may be null, '', 0, '0', 123, '123'
					if ((isset($this->{$field}) && ((string) $this->{$field}) != '') || !empty($info['notnull'])) {
						if (!isset($this->{$field})) {
							$queryarray[$field] = 0;
						} elseif ($this->isInt($info)) {
							$queryarray[$field] = (int) $this->{$field};	// If '0', it may be set to null later if $info['notnull'] == -1
						} elseif ($this->isFloat($info)) {
							$queryarray[$field] = (float) $this->{$field};	// If '0', it may be set to null later if $info['notnull'] == -1
						}
					} else {
						$queryarray[$field] = null;
					}
				}
			} else {
				// Note: If $this->{$field} is not defined, it means there is a bug into definition of ->fields or a missing declaration of property
				// We should keep the warning generated by this because it is a bug somewhere else in code, not here.
				$queryarray[$field] = $this->{$field};
			}

			if (array_key_exists('type', $info) && $info['type'] == 'timestamp' && empty($queryarray[$field])) {
				unset($queryarray[$field]);
			}
			if (!empty($info['notnull']) && $info['notnull'] == -1 && empty($queryarray[$field])) {
				$queryarray[$field] = null; // May force 0 to null
			}
		}

		return $queryarray;
	}

	/**
	 * Function to load data from a SQL pointer into properties of current object $this
	 *
	 * @param   stdClass    $obj    Contain data of object from database
	 * @return void
	 */
	public function setVarsFromFetchObj(&$obj)
	{
		global $db;

		foreach ($this->fields as $field => $info) {
			if ($this->isDate($info)) {
				if (!isset($obj->$field) || is_null($obj->$field) || $obj->$field === '' || $obj->$field === '0000-00-00 00:00:00' || $obj->$field === '1000-01-01 00:00:00') {
					$this->$field = '';
				} else {
					$this->$field = $db->jdate($obj->$field);
				}
			} elseif ($this->isInt($info)) {
				if ($field == 'rowid') {
					$this->id = (int) $obj->$field;
				} else {
					if ($this->isForcedToNullIfZero($info)) {
						if (empty($obj->$field)) {
							$this->$field = null;
						} else {
							$this->$field = (int) $obj->$field;
						}
					} else {
						if (isset($obj->$field) && (!is_null($obj->$field) || (array_key_exists('notnull', $info) && $info['notnull'] == 1))) {
							$this->$field = (int) $obj->$field;
						} else {
							$this->$field = null;
						}
					}
				}
			} elseif ($this->isFloat($info)) {
				if ($this->isForcedToNullIfZero($info)) {
					if (empty($obj->$field)) {
						$this->$field = null;
					} else {
						$this->$field = (float) $obj->$field;
					}
				} else {
					if (isset($obj->$field) && (!is_null($obj->$field) || (array_key_exists('notnull', $info) && $info['notnull'] == 1))) {
						$this->$field = (float) $obj->$field;
					} else {
						$this->$field = null;
					}
				}
			} else {
				$this->$field = isset($obj->$field) ? $obj->$field : null;
			}
		}

		// If there is no 'ref' field, we force property ->ref to ->id for a better compatibility with common functions.
		if (!isset($this->fields['ref']) && isset($this->id)) {
			$this->ref = (string) $this->id;
		}
	}

	/**
	 * Sets all object fields to null. Useful for example in lists, when printing multiple lines and a different object os fetched for each line.
	 * @return void
	 */
	public function emtpyObjectVars()
	{
		foreach ($this->fields as $field => $arr) {
			$this->$field = null;
		}
	}

	/**
	 * Function to concat keys of fields
	 *
	 * @param   string		$alias			String of alias of table for fields. For example 't'. It is recommended to use '' and set alias into fields definition.
	 * @param	string[]	$excludefields	Array of fields to exclude
	 * @return  string						List of alias fields
	 */
	public function getFieldList($alias = '', $excludefields = array())
	{
		$keys = array_keys($this->fields);
		if (!empty($alias)) {
			$keys_with_alias = array();
			foreach ($keys as $fieldname) {
				if (!empty($excludefields)) {
					if (in_array($fieldname, $excludefields)) {	// The field is excluded and must not be in output
						continue;
					}
				}
				$keys_with_alias[] = $alias . '.' . $fieldname;
			}
			return implode(',', $keys_with_alias);
		} else {
			return implode(',', $keys);
		}
	}

	/**
	 * Add quote to field value if necessary
	 *
	 * @param 	string|int	$value			Value to protect
	 * @param array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$fieldsentry	Properties of field
	 * @return 	string|int
	 */
	protected function quote($value, $fieldsentry)
	{
		if (is_null($value)) {
			return 'NULL';
		} elseif (preg_match('/^(int|double|real|price)/i', $fieldsentry['type'])) {
			return price2num((string) $value);
		} elseif (preg_match('/int$/i', $fieldsentry['type'])) {
			return (int) $value;
		} elseif ($fieldsentry['type'] == 'boolean') {
			if ($value) {
				return 'true';
			} else {
				return 'false';
			}
		} else {
			return "'".$this->db->escape($value)."'";
		}
	}


	/**
	 * Create object in the database
	 *
	 * @param  User		$user		User that creates
	 * @param  int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return int<-1,max>			Return integer <0 if KO, Id of created object if OK
	 */
	public function createCommon(User $user, $notrigger = 0)
	{
		global $langs;

		dol_syslog(get_class($this)."::createCommon create", LOG_DEBUG);

		$error = 0;

		$now = dol_now();

		$fieldvalues = $this->setSaveQuery();

		// Note: Here, $fieldvalues contains same keys (or less) that are inside ->fields

		if (array_key_exists('date_creation', $fieldvalues) && empty($fieldvalues['date_creation'])) {
			$fieldvalues['date_creation'] = $this->db->idate($now);
		}
		if (array_key_exists('fk_user_creat', $fieldvalues) && !($fieldvalues['fk_user_creat'] > 0)) {
			$fieldvalues['fk_user_creat'] = $user->id;
			$this->fk_user_creat = $user->id;
		}
		if (array_key_exists('user_creation_id', $fieldvalues) && !($fieldvalues['user_creation_id'] > 0)) {
			$fieldvalues['user_creation_id'] = $user->id;
			$this->user_creation_id = $user->id;
		}
		if (array_key_exists('pass_crypted', $fieldvalues) && property_exists($this, 'pass')) {
			// @phan-suppress-next-line PhanUndeclaredProperty
			$fieldvalues['pass_crypted'] = dol_hash($this->pass);
		}
		if (array_key_exists('ref', $fieldvalues)) {
			$fieldvalues['ref'] = dol_string_nospecial($fieldvalues['ref']); // If field is a ref, we sanitize data
		}

		unset($fieldvalues['rowid']); // The field 'rowid' is reserved field name for autoincrement field so we don't need it into insert.

		$keys = array();
		$values = array(); // Array to store string forged for SQL syntax
		foreach ($fieldvalues as $k => $v) {
			$keys[$k] = $k;
			$value = $this->fields[$k];
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$values[$k] = $this->quote($v, $value); // May return string 'NULL' if $value is null
		}

		// Clean and check mandatory
		foreach ($keys as $key) {
			if (!isset($this->fields[$key])) {
				continue;
			}
			$key_fields = $this->fields[$key];

			// If field is an implicit foreign key field (so type = 'integer:...')
			if (preg_match('/^integer:/i', $key_fields['type']) && $values[$key] == '-1') {
				$values[$key] = '';
			}
			if (!empty($key_fields['foreignkey']) && $values[$key] == '-1') {
				$values[$key] = '';
			}

			if (isset($key_fields['notnull']) && $key_fields['notnull'] == 1 && (!isset($values[$key]) || $values[$key] === 'NULL') && (!isset($key_fields['default']) || is_null($key_fields['default']))) {
				$error++;
				$langs->load("errors");
				dol_syslog("Mandatory field '".$key."' is empty and required into ->fields definition of class");
				$this->errors[] = $langs->trans("ErrorFieldRequired", isset($key_fields['label']) ? $key_fields['label'] : $key);
			}

			// If value is null and there is a default value for field @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
			if (isset($key_fields['notnull']) && $key_fields['notnull'] == 1 && (!isset($values[$key]) || $values[$key] === 'NULL') && !is_null($key_fields['default'])) {
				$values[$key] = $this->quote($key_fields['default'], $key_fields);
			}

			// If field is an implicit foreign key field (so type = 'integer:...')
			if (isset($key_fields['type']) && preg_match('/^integer:/i', $key_fields['type']) && empty($values[$key])) {
				if (isset($key_fields['default'])) {
					$values[$key] = ((int) $key_fields['default']);
				} else {
					$values[$key] = 'null';
				}
			}
			if (!empty($key_fields['foreignkey']) && empty($values[$key])) {
				$values[$key] = 'null';
			}
		}

		if ($error) {
			return -1;
		}

		$this->db->begin();

		if (!$error) {
			$sql = "INSERT INTO ".$this->db->prefix().$this->table_element;
			$sql .= " (".implode(", ", $keys).')';
			$sql .= " VALUES (".implode(", ", $values).")";		// $values can contains 'abc' or 123

			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$this->errors[] = "ErrorRefAlreadyExists";
				} else {
					$this->errors[] = $this->db->lasterror();
				}
			}
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
		}

		// If we have a field ref with a default value of (PROV)
		if (!$error) {
			// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset
			if (array_key_exists('ref', $this->fields) && array_key_exists('notnull', $this->fields['ref']) && $this->fields['ref']['notnull'] > 0 && array_key_exists('default', $this->fields['ref']) && $this->fields['ref']['default'] == '(PROV)') {
				$sql = "UPDATE ".$this->db->prefix().$this->table_element." SET ref = '(PROV".((int) $this->id).")' WHERE (ref = '(PROV)' OR ref = '') AND rowid = ".((int) $this->id);
				$resqlupdate = $this->db->query($sql);

				if ($resqlupdate === false) {
					$error++;
					$this->errors[] = $this->db->lasterror();
				} else {
					$this->ref = '(PROV'.$this->id.')';
				}
			}
		}

		// Create extrafields
		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		// Create lines
		if (!empty($this->table_element_line) && !empty($this->fk_element)) {
			foreach ($this->lines as $line) {
				$keyforparent = $this->fk_element;
				$line->$keyforparent = $this->id;

				// Test and convert into object this->lines[$i]. When coming from REST API, we may still have an array
				//if (! is_object($line)) $line=json_decode(json_encode($line), false);  // convert recursively array into object.
				if (!is_object($line)) {
					$line = (object) $line;
				}

				$result = 0;
				if (method_exists($line, 'insert')) {
					$result = $line->insert($user, 1);
				} elseif (method_exists($line, 'create')) {
					$result = $line->create($user, 1);
				}
				if ($result < 0) {
					$this->error = $line->error;
					$this->db->rollback();
					return -1;
				}
			}
		}

		// Triggers
		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger(strtoupper(get_class($this)).'_CREATE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 * Load object in memory from the database. This does not load line. This is done by parent fetch() that call fetchCommon
	 *
	 * @param	int			$id				Id object
	 * @param	string		$ref			Ref
	 * @param	string		$morewhere		More SQL filters (' AND ...')
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @return 	int<-4,1>	      			Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchCommon($id, $ref = null, $morewhere = '', $noextrafields = 0)
	{
		if (empty($id) && empty($ref) && empty($morewhere)) {
			return -1;
		}

		$fieldlist = $this->getFieldList('t');
		if (empty($fieldlist)) {
			return 0;
		}

		$sql = "SELECT ".$fieldlist;
		$sql .= " FROM ".$this->db->prefix().$this->table_element.' as t';

		if (!empty($id)) {
			$sql .= ' WHERE t.rowid = '.((int) $id);
		} elseif (!empty($ref)) {
			$sql .= " WHERE t.ref = '".$this->db->escape($ref)."'";
		} else {
			$sql .= ' WHERE 1 = 1'; // usage with empty id and empty ref is very rare
		}
		if (empty($id) && isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' AND t.entity IN ('.getEntity($this->element).')';
		}
		if ($morewhere) {
			$sql .= $morewhere;
		}
		$sql .= ' LIMIT 1'; // This is a fetch, to be certain to get only one record

		$res = $this->db->query($sql);
		if ($res) {
			$obj = $this->db->fetch_object($res);
			if ($obj) {
				$this->setVarsFromFetchObj($obj);

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				if (empty($noextrafields)) {
					$result = $this->fetch_optionals();
					if ($result < 0) {
						$this->error = $this->db->lasterror();
						$this->errors[] = $this->error;
						return -4;
					}
				}

				return $this->id;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param	string		$morewhere		More SQL filters (' AND ...')
	 * @param	int<0,1>	$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @return 	int<-1,1>        			Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLinesCommon($morewhere = '', $noextrafields = 0)
	{
		$objectlineclassname = get_class($this).'Line';
		if (!class_exists($objectlineclassname)) {
			$this->error = 'Error, class '.$objectlineclassname.' not found during call of fetchLinesCommon';
			return -1;
		}

		$objectline = new $objectlineclassname($this->db);
		'@phan-var-force CommonObjectLine $objectline';

		$sql = "SELECT ".$objectline->getFieldList('l');
		$sql .= " FROM ".$this->db->prefix().$objectline->table_element." as l";
		$sql .= " WHERE l.fk_".$this->db->escape($this->element)." = ".((int) $this->id);
		if ($morewhere) {
			$sql .= $morewhere;
		}
		if (isset($objectline->fields['position'])) {
			$sql .= $this->db->order('position', 'ASC');
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num_rows) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					$newline = new $objectlineclassname($this->db);
					'@phan-var-force CommonObjectLine $newline';
					$newline->setVarsFromFetchObj($obj);

					// Note: extrafields load of line not yet supported
					/*
					if (empty($noextrafields)) {
						// Load extrafields of line
					}*/

					$this->lines[] = $newline;
				}
				$i++;
			}

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->error;
			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User		$user     	User that modifies
	 * @param  int<0,1>	$notrigger	0=launch triggers after, 1=disable triggers
	 * @return int<-1,1>           	Return integer <0 if KO, >0 if OK
	 */
	public function updateCommon(User $user, $notrigger = 0)
	{
		dol_syslog(get_class($this)."::updateCommon update", LOG_DEBUG);

		$error = 0;

		$now = dol_now();

		// $this->oldcopy should have been set by the caller of update
		//if (empty($this->oldcopy)) {
		//	dol_syslog("this->oldcopy should have been set by the caller of update (here properties were already modified)", LOG_WARNING);
		//	$this->oldcopy = dol_clone($this, 2);
		//}

		$fieldvalues = $this->setSaveQuery();

		// Note: Here, $fieldvalues contains same keys (or less) that are inside ->fields

		if (array_key_exists('date_modification', $fieldvalues) && empty($fieldvalues['date_modification'])) {
			$fieldvalues['date_modification'] = $this->db->idate($now);
		}
		if (array_key_exists('fk_user_modif', $fieldvalues) && !($fieldvalues['fk_user_modif'] > 0)) {
			$fieldvalues['fk_user_modif'] = $user->id;
		}
		if (array_key_exists('user_modification_id', $fieldvalues) && !($fieldvalues['user_modification_id'] > 0)) {
			$fieldvalues['user_modification_id'] = $user->id;
		}
		if (array_key_exists('ref', $fieldvalues)) {
			$fieldvalues['ref'] = dol_string_nospecial($fieldvalues['ref']); // If field is a ref, we sanitize data
		}

		unset($fieldvalues['rowid']); // The field 'rowid' is reserved field name for autoincrement field so we don't need it into update.

		// Add quotes and escape on fields with type string
		$keys = array();
		$values = array();
		$tmp = array();
		foreach ($fieldvalues as $k => $v) {
			$keys[$k] = $k;
			$value = $this->fields[$k];
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$values[$k] = $this->quote($v, $value);
			if (($value["type"] == "text") && !empty($value['arrayofkeyval']) && is_array($value['arrayofkeyval'])) {
				// Clean values for text with selectbox
				$v = preg_replace('/\s/', ',', $v);
				$v = preg_replace('/,+/', ',', $v);
			}
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$tmp[] = $k.'='.$this->quote($v, $this->fields[$k]);
		}

		// Clean and check mandatory fields
		foreach ($keys as $key) {
			if (preg_match('/^integer:/i', $this->fields[$key]['type']) && $values[$key] == '-1') {
				$values[$key] = ''; // This is an implicit foreign key field
			}
			if (!empty($this->fields[$key]['foreignkey']) && $values[$key] == '-1') {
				$values[$key] = ''; // This is an explicit foreign key field
			}

			//var_dump($key.'-'.$values[$key].'-'.($this->fields[$key]['notnull'] == 1));
			/*
			if ($this->fields[$key]['notnull'] == 1 && empty($values[$key]))
			{
				$error++;
				$this->errors[]=$langs->trans("ErrorFieldRequired", $this->fields[$key]['label']);
			}*/
		}

		$sql = 'UPDATE '.$this->db->prefix().$this->table_element.' SET '.implode(', ', $tmp).' WHERE rowid='.((int) $this->id);

		$this->db->begin();

		if (!$error) {
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Update extrafield
		if (!$error) {
			$result = $this->insertExtraFields();	// This delete and reinsert extrafields
			if ($result < 0) {
				$error++;
			}
		}

		// Triggers
		if (!$error && !$notrigger) {
			// Call triggers
			$result = $this->call_trigger(strtoupper(get_class($this)).'_MODIFY', $user);
			if ($result < 0) {
				$error++;
			} //Do also here what you must do to rollback action if trigger fail
			// End call triggers
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param 	User 		$user       		User that deletes
	 * @param 	int<0,1>	$notrigger  		0=launch triggers after, 1=disable triggers
	 * @param	int<0,1>	$forcechilddeletion	0=no, 1=Force deletion of children
	 * @return 	int<-1,1>						Return integer <0 if KO, 0=Nothing done because object has child, >0 if OK
	 */
	public function deleteCommon(User $user, $notrigger = 0, $forcechilddeletion = 0)
	{
		dol_syslog(get_class($this)."::deleteCommon delete", LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if ($forcechilddeletion) {	// Force also delete of childtables that should lock deletion in standard case when option force is off
			foreach ($this->childtables as $table) {
				$sql = "DELETE FROM ".$this->db->prefix().$table." WHERE ".$this->fk_element." = ".((int) $this->id);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = $this->db->lasterror();
					$this->errors[] = $this->error;
					$this->db->rollback();
					return -1;
				}
			}
		} elseif (!empty($this->childtables)) {	// If object has children linked with a foreign key field, we check all child tables.
			$objectisused = $this->isObjectUsed($this->id);
			if (!empty($objectisused)) {
				dol_syslog(get_class($this)."::deleteCommon Can't delete record as it has some child", LOG_WARNING);
				$this->error = 'ErrorRecordHasChildren';
				$this->errors[] = $this->error;
				$this->db->rollback();
				return 0;
			}
		}

		// Delete cascade first
		if (is_array($this->childtablesoncascade) && !empty($this->childtablesoncascade)) {
			foreach ($this->childtablesoncascade as $tabletodelete) {
				$deleteFromObject = explode(':', $tabletodelete, 4);
				if (count($deleteFromObject) >= 2) {
					$className = str_replace('@', '', $deleteFromObject[0]);
					$filePath = $deleteFromObject[1];
					$columnName = $deleteFromObject[2];
					$filter = '';
					if (!empty($deleteFromObject[3])) {
						$filter = $deleteFromObject[3];
					}
					if (dol_include_once($filePath)) {
						$childObject = new $className($this->db);
						if (method_exists($childObject, 'deleteByParentField')) {
							'@phan-var-force CommonObject $childObject';
							$result = $childObject->deleteByParentField($this->id, $columnName, $filter);
							if ($result < 0) {
								$error++;
								$this->errors[] = $childObject->error;
								break;
							}
						} else {
							$error++;
							$this->errors[] = "You defined a cascade delete on an object $className/$this->id but there is no method deleteByParentField for it";
							break;
						}
					} else {
						$error++;
						$this->errors[] = 'Cannot include child class file '.$filePath;
						break;
					}
				} else {
					// Delete record in child table
					$sql = "DELETE FROM ".$this->db->prefix().$tabletodelete." WHERE ".$this->fk_element." = ".((int) $this->id);

					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->error = $this->db->lasterror();
						$this->errors[] = $this->error;
						break;
					}
				}
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call triggers
				$result = $this->call_trigger(strtoupper(get_class($this)).'_DELETE', $user);
				if ($result < 0) {
					$error++;
				} // Do also here what you must do to rollback action if trigger fail
				// End call triggers
			}
		}

		// Delete llx_ecm_files
		if (!$error) {
			$res = $this->deleteEcmFiles(1); // Deleting files physically is done later with the dol_delete_dir_recursive
			if (!$res) {
				$error++;
			}
		}

		// Delete linked object
		$res = $this->deleteObjectLinked();
		if ($res < 0) {
			$error++;
		}

		if (!$error && !empty($this->isextrafieldmanaged)) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			$sql = 'DELETE FROM '.$this->db->prefix().$this->table_element.' WHERE rowid='.((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Delete all child object from a parent ID
	 *
	 * @param	int			$parentId      	Parent Id
	 * @param	string		$parentField   	Name of Foreign key parent column
	 * @param 	string		$filter       	Filter as an Universal Search string.
	 * 										Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * @param  	string      $filtermode   	No more used
	 * @return	int							Return integer <0 if KO, >0 if OK
	 * @throws 	Exception
	 */
	public function deleteByParentField($parentId = 0, $parentField = '', $filter = '', $filtermode = "AND")
	{
		global $user;

		$error = 0;
		$deleted = 0;

		if (!empty($parentId) && !empty($parentField)) {
			$this->db->begin();

			$sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element;
			$sql .= " WHERE ".$this->db->sanitize($parentField)." = ".(int) $parentId;

			// Manage filter
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
			if ($errormessage) {
				$this->errors[] = $errormessage;
				dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
				return -1;
			}

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->lasterror();
				$error++;
			} else {
				while ($obj = $this->db->fetch_object($resql)) {
					$result = $this->fetch($obj->rowid);	// @phpstan-ignore-line
					if ($result < 0) {
						$error++;
						$this->errors[] = $this->error;
					} else {
						$result = $this->delete($user);	// @phpstan-ignore-line
						if ($result < 0) {
							$error++;
							$this->errors[] = $this->error;
						} else {
							$deleted++;
						}
					}
				}
			}

			if (empty($error)) {
				$this->db->commit();
				return $deleted;
			} else {
				$this->error = implode(', ', $this->errors);
				$this->db->rollback();
				return $error * -1;
			}
		}

		return $deleted;
	}

	/**
	 *  Delete a line of object in database
	 *
	 *	@param  User	$user       User that delete
	 *  @param	int		$idline		Id of line to delete
	 *  @param 	int 	$notrigger  0=launch triggers after, 1=disable triggers
	 *  @return int         		>0 if OK, <0 if KO
	 */
	public function deleteLineCommon(User $user, $idline, $notrigger = 0)
	{
		$error = 0;

		$tmpforobjectclass = get_class($this);
		$tmpforobjectlineclass = ucfirst($tmpforobjectclass).'Line';

		$this->db->begin();

		// Call trigger
		$result = $this->call_trigger('LINE'.strtoupper($tmpforobjectclass).'_DELETE', $user);
		if ($result < 0) {
			$error++;
		}
		// End call triggers

		if (empty($error)) {
			$sql = "DELETE FROM ".$this->db->prefix().$this->table_element_line;
			$sql .= " WHERE rowid = ".((int) $idline);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = "Error ".$this->db->lasterror();
				$error++;
			}
		}

		if (empty($error)) {
			// Remove extrafields
			$tmpobjectline = new $tmpforobjectlineclass($this->db);
			'@phan-var-force CommonObjectLine $tmpobjectline';
			if (!isset($tmpobjectline->isextrafieldmanaged) || !empty($tmpobjectline->isextrafieldmanaged)) {
				$tmpobjectline->id = $idline;
				$result = $tmpobjectline->deleteExtraFields();
				if ($result < 0) {
					$error++;
					$this->error = "Error ".get_class($this)."::deleteLineCommon deleteExtraFields error -4 ".$tmpobjectline->error;
				}
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			dol_syslog(get_class($this)."::deleteLineCommon ERROR:".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Set to a status
	 *
	 *	@param	User	$user			Object user that modify
	 *  @param	int		$status			New status to set (often a constant like self::STATUS_XXX)
	 *  @param	int		$notrigger		1=Does not execute triggers, 0=Execute triggers
	 *  @param  string  $triggercode    Trigger code to use
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function setStatusCommon($user, $status, $notrigger = 0, $triggercode = '')
	{
		$error = 0;

		$this->db->begin();

		$statusfield = 'status';
		if (in_array($this->element, array('don', 'donation', 'shipping'))) {
			$statusfield = 'fk_statut';
		}

		$sql = "UPDATE ".$this->db->prefix().$this->table_element;
		$sql .= " SET ".$statusfield." = ".((int) $status);
		$sql .= " WHERE rowid = ".((int) $this->id);

		if ($this->db->query($sql)) {
			if (!$error) {
				$this->oldcopy = clone $this;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger($triggercode, $user);
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error) {
				$this->status = $status;
				if (property_exists($this, 'statut')) {	// For backward compatibility
					$this->statut = $status;
				}
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
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return int
	 */
	public function initAsSpecimenCommon()
	{
		global $user;

		$this->id = 0;
		$this->specimen = 1;
		$fields = array(
			'label' => 'This is label',
			'ref' => 'ABCD1234',
			'description' => 'This is a description',
			'qty' => 123.12,
			'note_public' => 'Public note',
			'note_private' => 'Private note',
			'date_creation' => (dol_now() - 3600 * 48),
			'date_modification' => (dol_now() - 3600 * 24),
			'fk_user_creat' => $user->id,
			'fk_user_modif' => $user->id,
			'date' => dol_now(),
		);
		foreach ($fields as $key => $value) {
			if (array_key_exists($key, $this->fields)) {
				$this->{$key} = $value;		// @phpstan-ignore-line
			}
		}

		// Force values to default values when known
		if (property_exists($this, 'fields')) {
			foreach ($this->fields as $key => $value) {
				// If fields are already set, do nothing
				if (array_key_exists($key, $fields)) {
					continue;
				}

				if (!empty($value['default'])) {
					$this->$key = $value['default'];
				}
			}
		}

		return 1;
	}


	/* Part for comments */

	/**
	 * Load comments linked with current task
	 *
	 * @return int<0,max>|-1        Returns the number of comments if OK, -1 if error
	 */
	public function fetchComments()
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/comment.class.php';

		$comment = new Comment($this->db);
		$result = $comment->fetchAllFor($this->element, $this->id);
		if ($result < 0) {
			$this->errors = array_merge($this->errors, $comment->errors);
			return -1;
		} else {
			$this->comments = $comment->comments;
		}
		return count($this->comments);
	}

	/**
	 * Return nb comments already posted
	 *
	 * @return int
	 */
	public function getNbComments()
	{
		return count($this->comments);
	}

	/**
	 * Trim object parameters
	 *
	 * @param string[] $parameters array of parameters to trim
	 * @return void
	 */
	public function trimParameters($parameters)
	{
		if (!is_array($parameters)) {
			return;
		}
		foreach ($parameters as $parameter) {
			if (isset($this->$parameter)) {
				$this->$parameter = trim($this->$parameter);
			}
		}
	}

	/* Part for categories/tags */

	/**
	 * Sets object to given categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param 	string 		$type_categ 	Category type ('customer', 'supplier', 'website_page', ...)
	 * @return	int[]|int					Array of category IDs or < 0 if KO
	 */
	public function getCategoriesCommon($type_categ)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		// Get current categories
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, $type_categ, 'id');

		return $existing;
	}

	/**
	 * Sets object to given categories.
	 *
	 * Adds it to non existing supplied categories.
	 * Deletes object from existing categories not supplied (if remove_existing==true).
	 * Existing categories are left untouch.
	 *
	 * @param 	int[]|int 	$categories 		Category ID or array of Categories IDs
	 * @param 	string 		$type_categ 		Category type ('customer', 'supplier', 'website_page', ...) defined into const class Categorie type
	 * @param 	boolean		$remove_existing 	True: Remove existings categories from Object if not supplies by $categories, False: let them
	 * @return	int								Return integer <0 if KO, >0 if OK
	 */
	public function setCategoriesCommon($categories, $type_categ = '', $remove_existing = true)
	{
		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		dol_syslog(get_class($this)."::setCategoriesCommon Object Id:".$this->id.' type_categ:'.$type_categ.' nb tag add:'.count($categories), LOG_DEBUG);

		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		if (empty($type_categ)) {
			dol_syslog(__METHOD__.': Type '.$type_categ.'is an unknown category type. Done nothing.', LOG_ERR);
			return -1;
		}

		// Get current categories
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, $type_categ, 'id');
		if ($remove_existing) {
			// Diff
			if (is_array($existing)) {
				$to_del = array_diff($existing, $categories);
				$to_add = array_diff($categories, $existing);
			} else {
				$to_del = array(); // Nothing to delete
				$to_add = $categories;
			}
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = array_diff($categories, $existing);
		}

		$error = 0;
		$ok = 0;

		// Process
		foreach ($to_del as $del) {
			if ($c->fetch($del) > 0) {
				$result = $c->del_type($this, $type_categ);
				if ($result < 0) {
					$error++;
					$this->error = $c->error;
					$this->errors = $c->errors;
					break;
				} else {
					$ok += $result;
				}
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
				} else {
					$ok += $result;
				}
			}
		}

		return $error ? (-1 * $error) : $ok;
	}

	/**
	 * Copy related categories to another object
	 *
	 * @param  int		$fromId	Id object source
	 * @param  int		$toId	Id object cible
	 * @param  string	$type	Type of category ('product', ...)
	 * @return int      Return integer < 0 if error, > 0 if ok
	 */
	public function cloneCategories($fromId, $toId, $type = '')
	{
		$this->db->begin();

		if (empty($type)) {
			$type = $this->table_element;
		}

		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categorystatic = new Categorie($this->db);

		$sql = "INSERT INTO ".$this->db->prefix()."categorie_".(empty($categorystatic->MAP_CAT_TABLE[$type]) ? $type : $categorystatic->MAP_CAT_TABLE[$type])." (fk_categorie, fk_product)";
		$sql .= " SELECT fk_categorie, $toId FROM ".$this->db->prefix()."categorie_".(empty($categorystatic->MAP_CAT_TABLE[$type]) ? $type : $categorystatic->MAP_CAT_TABLE[$type]);
		$sql .= " WHERE fk_product = ".((int) $fromId);

		if (!$this->db->query($sql)) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();
		return 1;
	}

	/**
	 * Delete related files of object in database
	 *
	 * @param	integer		$mode		0=Use path to find record, 1=Use src_object_xxx fields (Mode 1 is recommended for new objects)
	 * @return 	bool					True if OK, False if KO
	 */
	public function deleteEcmFiles($mode = 0)
	{
		global $conf;

		$this->db->begin();

		// Delete in database with mode 0
		if ($mode == 0) {
			switch ($this->element) {
				case 'propal':
					$element = 'propale';
					break;
				case 'product':
					$element = 'produit';
					break;
				case 'order_supplier':
					$element = 'fournisseur/commande';
					break;
				case 'invoice_supplier':
					// Special cases that need to use get_exdir to get real dir of object
					// In future, all object should use this to define path of documents.
					$element = 'fournisseur/facture/'.get_exdir($this->id, 2, 0, 1, $this, 'invoice_supplier');
					break;
				case 'shipping':
					$element = 'expedition/sending';
					break;
				case 'task':
				case 'project_task':
					require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

					$project_result = $this->fetch_projet();
					if ($project_result >= 0) {
						$element = 'projet/'.dol_sanitizeFileName($this->project->ref).'/';
					}
					// no break
				default:
					$element = $this->element;
			}
			'@phan-var-force string $element';

			// Delete ecm_files_extrafields with mode 0 (using name)
			$sql = "DELETE FROM ".$this->db->prefix()."ecm_files_extrafields WHERE fk_object IN (";
			$sql .= " SELECT rowid FROM ".$this->db->prefix()."ecm_files WHERE filename LIKE '".$this->db->escape($this->ref)."%'";
			$sql .= " AND filepath = '".$this->db->escape($element)."/".$this->db->escape($this->ref)."' AND entity = ".((int) $conf->entity); // No need of getEntity here
			$sql .= ")";

			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return false;
			}

			// Delete ecm_files with mode 0 (using name)
			$sql = "DELETE FROM ".$this->db->prefix()."ecm_files";
			$sql .= " WHERE filename LIKE '".$this->db->escape($this->ref)."%'";
			$sql .= " AND filepath = '".$this->db->escape($element)."/".$this->db->escape($this->ref)."' AND entity = ".((int) $conf->entity); // No need of getEntity here

			if (!$this->db->query($sql)) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return false;
			}
		}

		// Delete in database with mode 1
		if ($mode == 1) {
			$sql = 'DELETE FROM '.$this->db->prefix()."ecm_files_extrafields";
			$sql .= " WHERE fk_object IN (SELECT rowid FROM ".$this->db->prefix()."ecm_files WHERE src_object_type = '".$this->db->escape($this->table_element.(empty($this->module) ? "" : "@".$this->module))."' AND src_object_id = ".((int) $this->id).")";
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return false;
			}

			$sql = 'DELETE FROM '.$this->db->prefix()."ecm_files";
			$sql .= " WHERE src_object_type = '".$this->db->escape($this->table_element.(empty($this->module) ? "" : "@".$this->module))."' AND src_object_id = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$this->db->rollback();
				return false;
			}
		}

		$this->db->commit();
		return true;
	}
}
