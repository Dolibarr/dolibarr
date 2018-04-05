<?php
/* Copyright (C) 2002-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2003       Brian Fraval            <brian@fraval.org>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2008       Patrick Raguin          <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2013       Peter Fontaine          <contact@peterfontaine.fr>
 * Copyright (C) 2014-2015  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2017       Rui Strecht			    <rui.strecht@aliartalentos.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/societe/class/societe.class.php
 *	\ingroup    societe
 *	\brief      File for third party class
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class Societe extends CommonObject
{
	public $element='societe';
	public $table_element = 'societe';
	public $fk_element='fk_soc';
	public $fieldsforcombobox='nom,name_alias';
	protected $childtables=array("supplier_proposal"=>'SupplierProposal',"propal"=>'Proposal',"commande"=>'Order',"facture"=>'Invoice',"facture_rec"=>'RecurringInvoiceTemplate',"contrat"=>'Contract',"fichinter"=>'Fichinter',"facture_fourn"=>'SupplierInvoice',"commande_fournisseur"=>'SupplierOrder',"projet"=>'Project',"expedition"=>'Shipment',"prelevement_lignes"=>'DirectDebitRecord');    // To test if we can delete object
	protected $childtablesoncascade=array("societe_prices", "societe_log", "societe_address", "product_fournisseur_price", "product_customer_price_log", "product_customer_price", "socpeople", "adherent", "societe_rib", "societe_remise", "societe_remise_except", "societe_commerciaux", "categorie", "notify", "notify_def", "actioncomm");
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


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'         =>array('type'=>'integer',      'label'=>'TechnicalID',      'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'index'=>1, 'position'=>1, 'comment'=>'Id'),
		'nom'           =>array('type'=>'varchar(128)', 'label'=>'Name',            'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Reference of object'),
		'name_alias'    =>array('type'=>'varchar(128)', 'label'=>'Name',            'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Reference of object'),
		'entity'        =>array('type'=>'integer',      'label'=>'Entity',           'enabled'=>1, 'visible'=>0,  'default'=>1, 'notnull'=>1,  'index'=>1, 'position'=>20),
		'note_public'   =>array('type'=>'text',			'label'=>'NotePublic',		 'enabled'=>1, 'visible'=>0,  'position'=>60),
		'note_private'  =>array('type'=>'text',			'label'=>'NotePrivate',		 'enabled'=>1, 'visible'=>0,  'position'=>61),
		'date_creation' =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>500),
		'tms'           =>array('type'=>'timestamp',    'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>501),
		//'date_valid'    =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'position'=>502),
		'fk_user_creat' =>array('type'=>'integer',      'label'=>'UserAuthor',       'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>510),
		'fk_user_modif' =>array('type'=>'integer',      'label'=>'UserModif',        'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>511),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key'    =>array('type'=>'varchar(14)',  'label'=>'ImportId',         'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>1,  'position'=>1000),
	);


	public $entity;

	/**
	 * Thirdparty name
	 * @var string
	 * @deprecated Use $name instead
	 * @see name
	 */
	public $nom;

	/**
	 * Alias names (commercial, trademark or alias names)
	 * @var string
	 */
	public $name_alias;

	public $particulier;
	public $address;
	public $zip;
	public $town;

	/**
	 * Thirdparty status : 0=activity ceased, 1= in activity
	 * @var int
	 */
	var $status;

	/**
	 * Id of department
	 * @var int
	 */
	var $state_id;
	var $state_code;
	var $state;

	/**
	 * Id of region
	 * @var int
	 */
	var $region_code;
	var $region;

	/**
	 * State code
	 * @var string
	 * @deprecated Use state_code instead
	 * @see state_code
	 */
	var $departement_code;

	/**
	 * @var string
	 * @deprecated Use state instead
	 * @see state
	 */
	var $departement;

	/**
	 * @var string
	 * @deprecated Use country instead
	 * @see country
	 */
	var $pays;

	/**
	 * Phone number
	 * @var string
	 */
	var $phone;
	/**
	 * Fax number
	 * @var string
	 */
	var $fax;
	/**
	 * Email
	 * @var string
	 */
	var $email;
	/**
	 * Skype username
	 * @var string
	 */
	var $skype;
	/**
	 * Webpage
	 * @var string
	 */
	var $url;

	//! barcode
	/**
	 * Barcode value
	 * @var string
	 */
	var $barcode;

	// 6 professional id (usage depends on country)

	/**
	 * Professional ID 1 (Ex: Siren in France)
	 * @var string
	 */
	var $idprof1;

	/**
	 * Professional ID 2 (Ex: Siret in France)
	 * @var string
	 */
	var $idprof2;

	/**
	 * Professional ID 3 (Ex: Ape in France)
	 * @var string
	 */
	var $idprof3;

	/**
	 * Professional ID 4 (Ex: RCS in France)
	 * @var string
	 */
	var $idprof4;

	/**
	 * Professional ID 5
	 * @var string
	 */
	var $idprof5;

	/**
	 * Professional ID 6
	 * @var string
	 */
	var $idprof6;

	var $prefix_comm;

	var $tva_assuj;
	/**
	 * Intracommunitary VAT ID
	 * @var string
	 */
	var $tva_intra;

	// Local taxes
	var $localtax1_assuj;
	var $localtax1_value;
	var $localtax2_assuj;
	var $localtax2_value;

	var $managers;
	var $capital;
	var $typent_id;
	var $typent_code;
	var $effectif;
	var $effectif_id;
	var $forme_juridique_code;
	var $forme_juridique;

	var $remise_percent;
	var $remise_supplier_percent;
	var $mode_reglement_supplier_id;
	var $cond_reglement_supplier_id;
	var $fk_prospectlevel;
	var $name_bis;

	//Log data

	/**
	 * Date of last update
	 * @var string
	 */
	var $date_modification;
	/**
	 * User that made last update
	 * @var string
	 */
	var $user_modification;
	/**
	 * Date of creation
	 * @var string
	 */
	var $date_creation;
	/**
	 * User that created the thirdparty
	 * @var User
	 */
	var $user_creation;


	var $specimen;

	/**
	 * 0=no customer, 1=customer, 2=prospect, 3=customer and prospect
	 * @var int
	 */
	var $client;
	/**
	 * 0=no prospect, 1=prospect
	 * @var int
	 */
	var $prospect;
	/**
	 * 0=no supplier, 1=supplier
	 * @var int
	 */
	var $fournisseur;

	/**
	 * Client code. E.g: CU2014-003
	 * @var string
	 */
	var $code_client;

	/**
	 * Supplier code. E.g: SU2014-003
	 * @var string
	 */
	var $code_fournisseur;

	/**
	 * Accounting code for client
	 * @var string
	 */
	var $code_compta;

	/**
	 * Accounting code for suppliers
	 * @var string
	 */
	var $code_compta_fournisseur;

	/**
	 * @var string
	 * @deprecated Note is split in public and private notes
	 * @see note_public, note_private
	 */
	var $note;

	/**
	 * Private note
	 * @var string
	 */
	var $note_private;

	/**
	 * Public note
	 * @var string
	 */
	var $note_public;

	//! code statut prospect
	var $stcomm_id;
	var $statut_commercial;

	/**
	 * Assigned price level
	 * @var int
	 */
	var $price_level;
	var $outstanding_limit;

	/**
	 * Min order amounts
	 */
	var $order_min_amount;
	var $supplier_order_min_amount;

	/**
	 * Id of sales representative to link (used for thirdparty creation). Not filled by a fetch, because we can have several sales representatives.
	 * @var int
	 */
	var $commercial_id;
	/**
	 * Id of parent thirdparty (if one)
	 * @var int
	 */
	var $parent;
	/**
	 * Default language code of thirdparty (en_US, ...)
	 * @var string
	 */
	var $default_lang;

	var $ref;
	var $ref_int;
	/**
	 * External user reference.
	 * This is to allow external systems to store their id and make self-developed synchronizing functions easier to
	 * build.
	 * @var string
	 */
	var $ref_ext;

	/**
	 * Import key.
	 * Set when the thirdparty has been created through an import process. This is to relate those created thirdparties
	 * to an import process
	 * @var string
	 */
	var $import_key;

	/**
	 * Supplier WebServices URL
	 * @var string
	 */
	var $webservices_url;

	/**
	 * Supplier WebServices Key
	 * @var string
	 */
	var $webservices_key;

	var $logo;
	var $logo_small;
	var $logo_mini;

	var $array_options;

	// Incoterms
	var $fk_incoterms;
	var $location_incoterms;
	var $libelle_incoterms;  //Used into tooltip

	// Multicurrency
	var $fk_multicurrency;
	var $multicurrency_code;


	// END MODULEBUILDER PROPERTIES


	/**
	 * To contains a clone of this when we need to save old properties of object
	 *  @var Societe
	 */
	var $oldcopy;

	/**
	 *    Constructor
	 *
	 *    @param	DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->client = 0;
		$this->prospect = 0;
		$this->fournisseur = 0;
		$this->typent_id  = 0;
		$this->effectif_id  = 0;
		$this->forme_juridique_code  = 0;
		$this->tva_assuj = 1;
		$this->status = 1;
	}


	/**
	 *    Create third party in database.
	 *    $this->code_client = -1 and $this->code_fournisseur = -1 means automatic assignement.
	 *
	 *    @param	User	$user       Object of user that ask creation
	 *    @return   int         		>= 0 if OK, < 0 if KO
	 */
	function create(User $user)
	{
		global $langs,$conf,$mysoc;

		$error=0;

		// Clean parameters
		if (empty($this->status)) $this->status=0;
		$this->name=$this->name?trim($this->name):trim($this->nom);
		if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->name=ucwords($this->name);
		$this->nom=$this->name; // For backward compatibility
		if (empty($this->client))      $this->client=0;
		if (empty($this->fournisseur)) $this->fournisseur=0;
		$this->import_key = trim($this->import_key);

		if (!empty($this->multicurrency_code)) $this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		if (empty($this->fk_multicurrency))
		{
			$this->multicurrency_code = '';
			$this->fk_multicurrency = 0;
		}

		dol_syslog(get_class($this)."::create ".$this->name);

		$now=dol_now();

		$this->db->begin();

		// For automatic creation during create action (not used by Dolibarr GUI, can be used by scripts)
		if ($this->code_client == -1 || $this->code_client === 'auto')           $this->get_codeclient($this,0);
		if ($this->code_fournisseur == -1 || $this->code_fournisseur === 'auto') $this->get_codefournisseur($this,1);

		// Check more parameters (including mandatory setup
		// If error, this->errors[] is filled
		$result = $this->verify();

		if ($result >= 0)
		{
			$entity = ((isset($this->entity) && is_numeric($this->entity))?$this->entity:$conf->entity);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, name_alias, entity, datec, fk_user_creat, canvas, status, ref_int, ref_ext, fk_stcomm, fk_incoterms, location_incoterms ,import_key, fk_multicurrency, multicurrency_code)";
			$sql.= " VALUES ('".$this->db->escape($this->name)."', '".$this->db->escape($this->name_alias)."', ".$entity.", '".$this->db->idate($now)."'";
			$sql.= ", ".(! empty($user->id) ? "'".$user->id."'":"null");
			$sql.= ", ".(! empty($this->canvas) ? "'".$this->db->escape($this->canvas)."'":"null");
			$sql.= ", ".$this->status;
			$sql.= ", ".(! empty($this->ref_int) ? "'".$this->db->escape($this->ref_int)."'":"null");
			$sql.= ", ".(! empty($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'":"null");
			$sql.= ", 0";
			$sql.= ", ".(int) $this->fk_incoterms;
			$sql.= ", '".$this->db->escape($this->location_incoterms)."'";
			$sql.= ", ".(! empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'":"null");
			$sql.= ", ".(int) $this->fk_multicurrency;
			$sql.= ", '".$this->db->escape($this->multicurrency_code)."')";

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$result=$this->db->query($sql);
			if ($result)
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."societe");

				$ret = $this->update($this->id,$user,0,1,1,'add');

				// Ajout du commercial affecte
				if ($this->commercial_id != '' && $this->commercial_id != -1)
				{
					$this->add_commercial($user, $this->commercial_id);
				}
				// si un commercial cree un client il lui est affecte automatiquement
				else if (empty($user->rights->societe->client->voir))
				{
					$this->add_commercial($user, $user->id);
				}

				if ($ret >= 0)
				{
					// Call trigger
					$result=$this->call_trigger('COMPANY_CREATE',$user);
					if ($result < 0) $error++;
					// End call triggers
				}
				else $error++;

				if (! $error)
				{
					dol_syslog(get_class($this)."::Create success id=".$this->id);
					$this->db->commit();
					return $this->id;
				}
				else
				{
					dol_syslog(get_class($this)."::Create echec update ".$this->error." ".join(',',$this->errors), LOG_ERR);
					$this->db->rollback();
					return -4;
				}
			}
			else
			{
				if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					$this->error=$langs->trans("ErrorCompanyNameAlreadyExists",$this->name);    // duplicate on a field (code or profid or ...)
					$result=-1;
				}
				else
				{
					$this->error=$this->db->lasterror();
					$result=-2;
				}
				$this->db->rollback();
				return $result;
			}

		}
		else
		{
			$this->db->rollback();
			dol_syslog(get_class($this)."::Create fails verify ".join(',',$this->errors), LOG_WARNING);
			return -3;
		}
	}


	/**
	 * Create a contact/address from thirdparty
	 *
	 * @param 	User	$user		Object user
	 * @return 	int					<0 if KO, >0 if OK
	 */
	function create_individual(User $user)
	{
		require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		$contact=new Contact($this->db);

		$contact->name              = $this->name_bis;
		$contact->firstname         = $this->firstname;
		$contact->civility_id       = $this->civility_id;
		$contact->socid             = $this->id;	// fk_soc
		$contact->statut            = 1;
		$contact->priv              = 0;
		$contact->country_id        = $this->country_id;
		$contact->state_id          = $this->state_id;
		$contact->address           = $this->address;
		$contact->email             = $this->email;
		$contact->zip               = $this->zip;
		$contact->town              = $this->town;
		$contact->phone_pro         = $this->phone;

		$result = $contact->create($user);
		if ($result < 0)
		{
			$this->error = $contact->error;
			$this->errors = $contact->errors;
			dol_syslog(get_class($this)."::create_individual ERROR:" . $this->error, LOG_ERR);
		}

		return $result;
	}

	/**
	 *    Check properties of third party are ok (like name, third party codes, ...)
	 *    Used before an add or update.
	 *
	 *    @return     int		0 if OK, <0 if KO
	 */
	function verify()
	{
		global $conf, $langs, $mysoc;

		$error = 0;
		$this->errors=array();

		$result = 0;
		$this->name	= trim($this->name);
		$this->nom=$this->name; // For backward compatibility

		if (! $this->name)
		{
			$this->errors[] = 'ErrorBadThirdPartyName';
			$result = -2;
		}

		if ($this->client)
		{
			$rescode = $this->check_codeclient();
			if ($rescode <> 0)
			{
				if ($rescode == -1)
				{
					$this->errors[] = 'ErrorBadCustomerCodeSyntax';
				}
				if ($rescode == -2)
				{
					$this->errors[] = 'ErrorCustomerCodeRequired';
				}
				if ($rescode == -3)
				{
					$this->errors[] = 'ErrorCustomerCodeAlreadyUsed';
				}
				if ($rescode == -4)
				{
					$this->errors[] = 'ErrorPrefixRequired';
				}
				$result = -3;
			}
		}

		if ($this->fournisseur)
		{
			$rescode = $this->check_codefournisseur();
			if ($rescode <> 0)
			{
				if ($rescode == -1)
				{
					$this->errors[] = 'ErrorBadSupplierCodeSyntax';
				}
				if ($rescode == -2)
				{
					$this->errors[] = 'ErrorSupplierCodeRequired';
				}
				if ($rescode == -3)
				{
					$this->errors[] = 'ErrorSupplierCodeAlreadyUsed';
				}
				if ($rescode == -5)
				{
					$this->errors[] = 'ErrorprefixRequired';
				}
				$result = -3;
			}
		}

		// Check for duplicate or mandatory fields defined into setup
		$array_to_check=array('IDPROF1','IDPROF2','IDPROF3','IDPROF4','IDPROF5','IDPROF6','EMAIL');
		foreach($array_to_check as $key)
		{
			$keymin=strtolower($key);
			$i=(int) preg_replace('/[^0-9]/','',$key);
			$vallabel=$this->$keymin;

			if ($i > 0)
			{
				if ($this->isACompany())
				{
					// Check for unicity
					if ($vallabel && $this->id_prof_verifiable($i))
					{
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0)))
						{
							$langs->load("errors");
							$error++; $this->errors[] = $langs->transcountry('ProfId'.$i, $this->country_code)." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
					}

					// Check for mandatory prof id (but only if country is other than ours)
					if ($mysoc->country_id > 0 && $this->country_id == $mysoc->country_id)
					{
						$idprof_mandatory ='SOCIETE_'.$key.'_MANDATORY';
						if (! $vallabel && ! empty($conf->global->$idprof_mandatory))
						{
							$langs->load("errors");
							$error++;
							$this->errors[] = $langs->trans("ErrorProdIdIsMandatory", $langs->transcountry('ProfId'.$i, $this->country_code)).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
					}
				}
			}
			else
			{
				//var_dump($conf->global->SOCIETE_EMAIL_UNIQUE);
				//var_dump($conf->global->SOCIETE_EMAIL_MANDATORY);
				if ($key == 'EMAIL')
				{
					// Check for unicity
					if ($vallabel && ! empty($conf->global->SOCIETE_EMAIL_UNIQUE))
					{
						if ($this->id_prof_exists($keymin, $vallabel, ($this->id > 0 ? $this->id : 0)))
						{
							$langs->load("errors");
							$error++; $this->errors[] = $langs->trans('Email')." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel).' ('.$langs->trans("ForbiddenBySetupRules").')';
						}
					}

					// Check for mandatory
					if (! empty($conf->global->SOCIETE_EMAIL_MANDATORY) && ! isValidEMail($this->email))
					{
						$langs->load("errors");
						$error++;
						$this->errors[] = $langs->trans("ErrorBadEMail", $this->email).' ('.$langs->trans("ForbiddenBySetupRules").')';
					}
				}
			}
		}

		if ($error) $result = -4;

		return $result;
	}

	/**
	 *      Update parameters of third party
	 *
	 *      @param	int		$id              			Id of company (deprecated, use 0 here and call update on an object loaded by a fetch)
	 *      @param  User	$user            			Utilisateur qui demande la mise a jour
	 *      @param  int		$call_trigger    			0=no, 1=yes
	 *		@param	int		$allowmodcodeclient			Inclut modif code client et code compta
	 *		@param	int		$allowmodcodefournisseur	Inclut modif code fournisseur et code compta fournisseur
	 *		@param	string	$action						'add' or 'update' or 'merge'
	 *		@param	int		$nosyncmember				Do not synchronize info of linked member
	 *      @return int  			           			<0 if KO, >=0 if OK
	 */
	function update($id, $user='', $call_trigger=1, $allowmodcodeclient=0, $allowmodcodefournisseur=0, $action='update', $nosyncmember=1)
	{
		global $langs,$conf,$hookmanager;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		if (empty($id)) $id = $this->id;

		$error=0;

		dol_syslog(get_class($this)."::Update id=".$id." call_trigger=".$call_trigger." allowmodcodeclient=".$allowmodcodeclient." allowmodcodefournisseur=".$allowmodcodefournisseur);

		$now=dol_now();

		// Clean parameters
		$this->id			= $id;
		$this->entity		= ((isset($this->entity) && is_numeric($this->entity))?$this->entity:$conf->entity);
		$this->name			= $this->name?trim($this->name):trim($this->nom);
		$this->nom			= $this->name;	// For backward compatibility
		$this->name_alias	= trim($this->name_alias);
		$this->ref_ext		= trim($this->ref_ext);
		$this->address		= $this->address?trim($this->address):trim($this->address);
		$this->zip			= $this->zip?trim($this->zip):trim($this->zip);
		$this->town			= $this->town?trim($this->town):trim($this->town);
		$this->state_id		= trim($this->state_id);
		$this->country_id	= ($this->country_id > 0)?$this->country_id:0;
		$this->phone		= trim($this->phone);
		$this->phone		= preg_replace("/\s/","",$this->phone);
		$this->phone		= preg_replace("/\./","",$this->phone);
		$this->fax			= trim($this->fax);
		$this->fax			= preg_replace("/\s/","",$this->fax);
		$this->fax			= preg_replace("/\./","",$this->fax);
		$this->email		= trim($this->email);
		$this->skype		= trim($this->skype);
		$this->url			= $this->url?clean_url($this->url,0):'';
		$this->note_private = trim($this->note_private);
		$this->note_public  = trim($this->note_public);
		$this->idprof1		= trim($this->idprof1);
		$this->idprof2		= trim($this->idprof2);
		$this->idprof3		= trim($this->idprof3);
		$this->idprof4		= trim($this->idprof4);
		$this->idprof5		= (! empty($this->idprof5)?trim($this->idprof5):'');
		$this->idprof6		= (! empty($this->idprof6)?trim($this->idprof6):'');
		$this->prefix_comm	= trim($this->prefix_comm);
		$this->outstanding_limit = price2num($this->outstanding_limit);
		$this->order_min_amount = price2num($this->order_min_amount);
		$this->supplier_order_min_amount = price2num($this->supplier_order_min_amount);

		$this->tva_assuj	= trim($this->tva_assuj);
		$this->tva_intra	= dol_sanitizeFileName($this->tva_intra,'');
		if (empty($this->status)) $this->status = 0;

		if (!empty($this->multicurrency_code)) $this->fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $this->multicurrency_code);
		if (empty($this->fk_multicurrency))
		{
			$this->multicurrency_code = '';
			$this->fk_multicurrency = 0;
		}

		// Local taxes
		$this->localtax1_assuj=trim($this->localtax1_assuj);
		$this->localtax2_assuj=trim($this->localtax2_assuj);

		$this->localtax1_value=trim($this->localtax1_value);
		$this->localtax2_value=trim($this->localtax2_value);

		if ($this->capital != '') $this->capital=price2num(trim($this->capital));
		if (! is_numeric($this->capital)) $this->capital = '';     // '' = undef

		$this->effectif_id=trim($this->effectif_id);
		$this->forme_juridique_code=trim($this->forme_juridique_code);

		//Gencod
		$this->barcode=trim($this->barcode);

		// For automatic creation
		if ($this->code_client == -1 || $this->code_client === 'auto')           $this->get_codeclient($this,0);
		if ($this->code_fournisseur == -1 || $this->code_fournisseur === 'auto') $this->get_codefournisseur($this,1);

		$this->code_compta=trim($this->code_compta);
		$this->code_compta_fournisseur=trim($this->code_compta_fournisseur);

		// Check parameters. More tests are done later in the ->verify()
		if (! is_numeric($this->client) && ! is_numeric($this->fournisseur))
		{
			$langs->load("errors");
			$this->error = $langs->trans("BadValueForParameterClientOrSupplier");
			return -1;
		}

		$customer=false;
		if (! empty($allowmodcodeclient) && ! empty($this->client))
		{
			// Attention get_codecompta peut modifier le code suivant le module utilise
			if (empty($this->code_compta))
			{
				$ret=$this->get_codecompta('customer');
				if ($ret < 0) return -1;
			}

			$customer=true;
		}

		$supplier=false;
		if (! empty($allowmodcodefournisseur) && ! empty($this->fournisseur))
		{
			// Attention get_codecompta peut modifier le code suivant le module utilise
			if (empty($this->code_compta_fournisseur))
			{
				$ret=$this->get_codecompta('supplier');
				if ($ret < 0) return -1;
			}

			$supplier=true;
		}

		//Web services
		$this->webservices_url = $this->webservices_url?clean_url($this->webservices_url,0):'';
		$this->webservices_key = trim($this->webservices_key);

		//Incoterms
		$this->fk_incoterms = (int) $this->fk_incoterms;
		$this->location_incoterms = trim($this->location_incoterms);

		$this->db->begin();

		// Check name is required and codes are ok or unique.
		// If error, this->errors[] is filled
		$result = 0;
		if ($action != 'add' && $action != 'merge')
		{
			// We don't check when update called during a create because verify was already done.
			// For a merge, we suppose source data is clean and a customer code of a deleted thirdparty must be accepted into a target thirdparty with empty code without duplicate error
			$result = $this->verify();
		}

		if ($result >= 0)
		{
			dol_syslog(get_class($this)."::update verify ok or not done");

			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
			$sql .= "entity = " . $this->entity;
			$sql .= ",nom = '" . $this->db->escape($this->name) ."'"; // Required
			$sql .= ",name_alias = '" . $this->db->escape($this->name_alias) ."'";
			$sql .= ",ref_ext = " .(! empty($this->ref_ext)?"'".$this->db->escape($this->ref_ext) ."'":"null");
			$sql .= ",address = '" . $this->db->escape($this->address) ."'";

			$sql .= ",zip = ".(! empty($this->zip)?"'".$this->db->escape($this->zip)."'":"null");
			$sql .= ",town = ".(! empty($this->town)?"'".$this->db->escape($this->town)."'":"null");

			$sql .= ",fk_departement = '" . (! empty($this->state_id)?$this->state_id:'0') ."'";
			$sql .= ",fk_pays = '" . (! empty($this->country_id)?$this->country_id:'0') ."'";

			$sql .= ",phone = ".(! empty($this->phone)?"'".$this->db->escape($this->phone)."'":"null");
			$sql .= ",fax = ".(! empty($this->fax)?"'".$this->db->escape($this->fax)."'":"null");
			$sql .= ",email = ".(! empty($this->email)?"'".$this->db->escape($this->email)."'":"null");
			$sql .= ",skype = ".(! empty($this->skype)?"'".$this->db->escape($this->skype)."'":"null");
			$sql .= ",url = ".(! empty($this->url)?"'".$this->db->escape($this->url)."'":"null");

			$sql .= ",note_private = ".(! empty($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null");
			$sql .= ",note_public = ".(! empty($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null");

			$sql .= ",siren   = '". $this->db->escape($this->idprof1) ."'";
			$sql .= ",siret   = '". $this->db->escape($this->idprof2) ."'";
			$sql .= ",ape     = '". $this->db->escape($this->idprof3) ."'";
			$sql .= ",idprof4 = '". $this->db->escape($this->idprof4) ."'";
			$sql .= ",idprof5 = '". $this->db->escape($this->idprof5) ."'";
			$sql .= ",idprof6 = '". $this->db->escape($this->idprof6) ."'";

			$sql .= ",tva_assuj = ".($this->tva_assuj!=''?"'".$this->db->escape($this->tva_assuj)."'":"null");
			$sql .= ",tva_intra = '" . $this->db->escape($this->tva_intra) ."'";
			$sql .= ",status = " .$this->status;

			// Local taxes
			$sql .= ",localtax1_assuj = ".($this->localtax1_assuj!=''?"'".$this->db->escape($this->localtax1_assuj)."'":"null");
			$sql .= ",localtax2_assuj = ".($this->localtax2_assuj!=''?"'".$this->db->escape($this->localtax2_assuj)."'":"null");
			if($this->localtax1_assuj==1)
			{
				if($this->localtax1_value!='')
				{
					$sql .=",localtax1_value =".$this->localtax1_value;
				}
				else $sql .=",localtax1_value =0.000";

			}
			else $sql .=",localtax1_value =0.000";

			if($this->localtax2_assuj==1)
			{
				if($this->localtax2_value!='')
				{
					$sql .=",localtax2_value =".$this->localtax2_value;
				}
				else $sql .=",localtax2_value =0.000";

			}
			else $sql .=",localtax2_value =0.000";

			$sql .= ",capital = ".($this->capital == '' ? "null" : $this->capital);

			$sql .= ",prefix_comm = ".(! empty($this->prefix_comm)?"'".$this->db->escape($this->prefix_comm)."'":"null");

			$sql .= ",fk_effectif = ".(! empty($this->effectif_id)?"'".$this->db->escape($this->effectif_id)."'":"null");
			if (isset($this->stcomm_id))
			{
				$sql .= ",fk_stcomm=".($this->stcomm_id > 0 ? $this->stcomm_id : "0");
			}
			$sql .= ",fk_typent = ".(! empty($this->typent_id)?"'".$this->db->escape($this->typent_id)."'":"0");

			$sql .= ",fk_forme_juridique = ".(! empty($this->forme_juridique_code)?"'".$this->db->escape($this->forme_juridique_code)."'":"null");

			$sql .= ",mode_reglement = ".(! empty($this->mode_reglement_id)?"'".$this->db->escape($this->mode_reglement_id)."'":"null");
			$sql .= ",cond_reglement = ".(! empty($this->cond_reglement_id)?"'".$this->db->escape($this->cond_reglement_id)."'":"null");
			$sql .= ",mode_reglement_supplier = ".(! empty($this->mode_reglement_supplier_id)?"'".$this->db->escape($this->mode_reglement_supplier_id)."'":"null");
			$sql .= ",cond_reglement_supplier = ".(! empty($this->cond_reglement_supplier_id)?"'".$this->db->escape($this->cond_reglement_supplier_id)."'":"null");
			$sql .= ",fk_shipping_method = ".(! empty($this->shipping_method_id)?"'".$this->db->escape($this->shipping_method_id)."'":"null");

			$sql .= ",client = " . (! empty($this->client)?$this->client:0);
			$sql .= ",fournisseur = " . (! empty($this->fournisseur)?$this->fournisseur:0);
			$sql .= ",barcode = ".(! empty($this->barcode)?"'".$this->db->escape($this->barcode)."'":"null");
			$sql .= ",default_lang = ".(! empty($this->default_lang)?"'".$this->db->escape($this->default_lang)."'":"null");
			$sql .= ",logo = ".(! empty($this->logo)?"'".$this->db->escape($this->logo)."'":"null");
			$sql .= ",outstanding_limit= ".($this->outstanding_limit!=''?$this->outstanding_limit:'null');
			$sql .= ",order_min_amount= ".($this->order_min_amount!=''?$this->order_min_amount:'null');
			$sql .= ",supplier_order_min_amount= ".($this->supplier_order_min_amount!=''?$this->supplier_order_min_amount:'null');
			$sql .= ",fk_prospectlevel='".$this->db->escape($this->fk_prospectlevel)."'";

			$sql .= ",webservices_url = ".(! empty($this->webservices_url)?"'".$this->db->escape($this->webservices_url)."'":"null");
			$sql .= ",webservices_key = ".(! empty($this->webservices_key)?"'".$this->db->escape($this->webservices_key)."'":"null");

			//Incoterms
			$sql.= ", fk_incoterms = ".$this->fk_incoterms;
			$sql.= ", location_incoterms = ".(! empty($this->location_incoterms)?"'".$this->db->escape($this->location_incoterms)."'":"null");

			if ($customer)
			{
				$sql .= ", code_client = ".(! empty($this->code_client)?"'".$this->db->escape($this->code_client)."'":"null");
				$sql .= ", code_compta = ".(! empty($this->code_compta)?"'".$this->db->escape($this->code_compta)."'":"null");
			}

			if ($supplier)
			{
				$sql .= ", code_fournisseur = ".(! empty($this->code_fournisseur)?"'".$this->db->escape($this->code_fournisseur)."'":"null");
				$sql .= ", code_compta_fournisseur = ".(! empty($this->code_compta_fournisseur)?"'".$this->db->escape($this->code_compta_fournisseur)."'":"null");
			}
			$sql .= ", fk_user_modif = ".($user->id > 0 ? $user->id:"null");
			$sql .= ", fk_multicurrency = ".(int) $this->fk_multicurrency;
			$sql .= ", multicurrency_code = '".$this->db->escape($this->multicurrency_code)."'";
			$sql .= " WHERE rowid = " . (int) $id;

			$resql=$this->db->query($sql);
			if ($resql)
			{
				unset($this->country_code);		// We clean this because it may have been changed after an update of country_id
				unset($this->country);
				unset($this->state_code);
				unset($this->state);

				$nbrowsaffected = $this->db->affected_rows($resql);

				if (! $error && $nbrowsaffected)
				{
					// Update information on linked member if it is an update
					if (! $nosyncmember && ! empty($conf->adherent->enabled))
					{
						require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

						dol_syslog(get_class($this)."::update update linked member");

						$lmember=new Adherent($this->db);
						$result=$lmember->fetch(0, 0, $this->id);

						if ($result > 0)
						{
							$lmember->societe=$this->name;
							//$lmember->firstname=$this->firstname?$this->firstname:$lmember->firstname;	// We keep firstname and lastname of member unchanged
							//$lmember->lastname=$this->lastname?$this->lastname:$lmember->lastname;		// We keep firstname and lastname of member unchanged
							$lmember->address=$this->address;
							$lmember->email=$this->email;
							$lmember->skype=$this->skype;
							$lmember->phone=$this->phone;

							$result=$lmember->update($user,0,1,1,1);	// Use nosync to 1 to avoid cyclic updates
							if ($result < 0)
							{
								$this->error=$lmember->error;
								dol_syslog(get_class($this)."::update ".$this->error,LOG_ERR);
								$error++;
							}
						}
						else if ($result < 0)
						{
							$this->error=$lmember->error;
							$error++;
						}
					}
				}

				$action='update';

				// Actions on extra fields (by external module or standard code)
				// TODO le hook fait double emploi avec le trigger !!
				$hookmanager->initHooks(array('thirdpartydao'));
				$parameters=array('socid'=>$this->id);
				$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
				if (empty($reshook))
				{
					if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
					{
						$result=$this->insertExtraFields();
						if ($result < 0)
						{
							$error++;
						}
					}
				}
				else if ($reshook < 0) $error++;

				if (! $error && $call_trigger)
				{
					// Call trigger
					$result=$this->call_trigger('COMPANY_MODIFY',$user);
					if ($result < 0) $error++;
					// End call triggers
				}

				if (! $error)
				{
					dol_syslog(get_class($this)."::Update success");
					$this->db->commit();
					return 1;
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
			}
			else
			{
				if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					// Doublon
					$this->error = $langs->trans("ErrorDuplicateField");
					$result = -1;
				}
				else
				{
					$this->error = $this->db->lasterror();
					$result = -2;
				}
				$this->db->rollback();
				return $result;
			}
		}
		else
	   {
			$this->db->rollback();
			dol_syslog(get_class($this)."::Update fails verify ".join(',',$this->errors), LOG_WARNING);
			return -3;
		}
	}

	/**
	 *    Load a third party from database into memory
	 *
	 *    @param	int		$rowid			Id of third party to load
	 *    @param    string	$ref			Reference of third party, name (Warning, this can return several records)
	 *    @param    string	$ref_ext       	External reference of third party (Warning, this information is a free field not provided by Dolibarr)
	 *    @param    string	$ref_int       	Internal reference of third party (not used by dolibarr)
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
	function fetch($rowid, $ref='', $ref_ext='', $ref_int='', $idprof1='',$idprof2='',$idprof3='',$idprof4='',$idprof5='',$idprof6='', $email='', $ref_alias='')
	{
		global $langs;
		global $conf;

		if (empty($rowid) && empty($ref) && empty($ref_ext) && empty($ref_int) && empty($idprof1) && empty($idprof2) && empty($idprof3) && empty($idprof4) && empty($idprof5) && empty($idprof6) && empty($email)) return -1;

		$sql = 'SELECT s.rowid, s.nom as name, s.name_alias, s.entity, s.ref_ext, s.ref_int, s.address, s.datec as date_creation, s.prefix_comm';
		$sql .= ', s.status';
		$sql .= ', s.price_level';
		$sql .= ', s.tms as date_modification, s.fk_user_creat, s.fk_user_modif';
		$sql .= ', s.phone, s.fax, s.email, s.skype, s.url, s.zip, s.town, s.note_private, s.note_public, s.model_pdf, s.client, s.fournisseur';
		$sql .= ', s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6';
		$sql .= ', s.capital, s.tva_intra';
		$sql .= ', s.fk_typent as typent_id';
		$sql .= ', s.fk_effectif as effectif_id';
		$sql .= ', s.fk_forme_juridique as forme_juridique_code';
		$sql .= ', s.webservices_url, s.webservices_key';
		$sql .= ', s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.parent, s.barcode';
		$sql .= ', s.fk_departement, s.fk_pays as country_id, s.fk_stcomm, s.remise_client, s.remise_supplier, s.mode_reglement, s.cond_reglement, s.fk_account, s.tva_assuj';
		$sql .= ', s.mode_reglement_supplier, s.cond_reglement_supplier, s.localtax1_assuj, s.localtax1_value, s.localtax2_assuj, s.localtax2_value, s.fk_prospectlevel, s.default_lang, s.logo';
		$sql .= ', s.fk_shipping_method';
		$sql .= ', s.outstanding_limit, s.import_key, s.canvas, s.fk_incoterms, s.location_incoterms';
		$sql .= ', s.order_min_amount, s.supplier_order_min_amount';
		$sql .= ', s.fk_multicurrency, s.multicurrency_code';
		$sql .= ', fj.libelle as forme_juridique';
		$sql .= ', e.libelle as effectif';
		$sql .= ', c.code as country_code, c.label as country';
		$sql .= ', d.code_departement as state_code, d.nom as state';
		$sql .= ', st.libelle as stcomm';
		$sql .= ', te.code as typent_code';
		$sql .= ', i.libelle as libelle_incoterms';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as e ON s.fk_effectif = e.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as c ON s.fk_pays = c.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as fj ON s.fk_forme_juridique = fj.code';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as te ON s.fk_typent = te.id';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_incoterms as i ON s.fk_incoterms = i.rowid';

		$sql .= ' WHERE s.entity IN ('.getEntity($this->element).')';
		if ($rowid)     $sql .= ' AND s.rowid = '.$rowid;
		if ($ref)       $sql .= " AND s.nom = '".$this->db->escape($ref)."'";
		if ($ref_alias) $sql .= " AND s.nom_alias = '".$this->db->escape($ref_alias)."'";
		if ($ref_ext)   $sql .= " AND s.ref_ext = '".$this->db->escape($ref_ext)."'";
		if ($ref_int)   $sql .= " AND s.ref_int = '".$this->db->escape($ref_int)."'";
		if ($idprof1)   $sql .= " AND s.siren = '".$this->db->escape($idprof1)."'";
		if ($idprof2)   $sql .= " AND s.siret = '".$this->db->escape($idprof2)."'";
		if ($idprof3)   $sql .= " AND s.ape = '".$this->db->escape($idprof3)."'";
		if ($idprof4)   $sql .= " AND s.idprof4 = '".$this->db->escape($idprof4)."'";
		if ($idprof5)   $sql .= " AND s.idprof5 = '".$this->db->escape($idprof5)."'";
		if ($idprof6)   $sql .= " AND s.idprof6 = '".$this->db->escape($idprof6)."'";
		if ($email)     $sql .= " AND s.email = '".$this->db->escape($email)."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num=$this->db->num_rows($resql);
			if ($num > 1)
			{
				$this->error='Fetch found several records. Rename one of tirdparties to avoid duplicate.';
				dol_syslog($this->error, LOG_ERR);
				$result = -2;
			}
			elseif ($num)   // $num = 1
			{
				$obj = $this->db->fetch_object($resql);

				$this->id           = $obj->rowid;
				$this->entity       = $obj->entity;
				$this->canvas		= $obj->canvas;

				$this->ref          = $obj->rowid;
				$this->name 		= $obj->name;
				$this->nom          = $obj->name;		// deprecated
				$this->name_alias = $obj->name_alias;
				$this->ref_ext      = $obj->ref_ext;
				$this->ref_int      = $obj->ref_int;

				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->user_creation     = $obj->fk_user_creat;
				$this->user_modification = $obj->fk_user_modif;

				$this->address 		= $obj->address;
				$this->zip 			= $obj->zip;
				$this->town 		= $obj->town;

				$this->country_id   = $obj->country_id;
				$this->country_code = $obj->country_id?$obj->country_code:'';
				$this->country 		= $obj->country_id?($langs->trans('Country'.$obj->country_code)!='Country'.$obj->country_code?$langs->transnoentities('Country'.$obj->country_code):$obj->country):'';

				$this->state_id     = $obj->fk_departement;
				$this->state_code   = $obj->state_code;
				$this->state        = ($obj->state!='-'?$obj->state:'');

				$transcode=$langs->trans('StatusProspect'.$obj->fk_stcomm);
				$libelle=($transcode!='StatusProspect'.$obj->fk_stcomm?$transcode:$obj->stcomm);
				$this->stcomm_id = $obj->fk_stcomm;     // id statut commercial
				$this->statut_commercial = $libelle;    // libelle statut commercial

				$this->email = $obj->email;
				$this->skype = $obj->skype;
				$this->url = $obj->url;
				$this->phone = $obj->phone;
				$this->fax = $obj->fax;

				$this->parent    = $obj->parent;

				$this->idprof1		= $obj->idprof1;
				$this->idprof2		= $obj->idprof2;
				$this->idprof3		= $obj->idprof3;
				$this->idprof4		= $obj->idprof4;
				$this->idprof5		= $obj->idprof5;
				$this->idprof6		= $obj->idprof6;

				$this->capital   = $obj->capital;

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
				$this->effectif       = $obj->effectif_id?$obj->effectif:'';

				$this->forme_juridique_code= $obj->forme_juridique_code;
				$this->forme_juridique     = $obj->forme_juridique_code?$obj->forme_juridique:'';

				$this->fk_prospectlevel = $obj->fk_prospectlevel;

				$this->prefix_comm = $obj->prefix_comm;

				$this->remise_percent		= $obj->remise_client;
				$this->remise_supplier_percent		= $obj->remise_supplier;
				$this->mode_reglement_id 	= $obj->mode_reglement;
				$this->cond_reglement_id 	= $obj->cond_reglement;
				$this->mode_reglement_supplier_id 	= $obj->mode_reglement_supplier;
				$this->cond_reglement_supplier_id 	= $obj->cond_reglement_supplier;
				$this->shipping_method_id   = ($obj->fk_shipping_method>0)?$obj->fk_shipping_method:null;
				$this->fk_account			= $obj->fk_account;

				$this->client      = $obj->client;
				$this->fournisseur = $obj->fournisseur;

				$this->note = $obj->note_private; // TODO Deprecated for backward comtability
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->modelpdf = $obj->model_pdf;
				$this->default_lang = $obj->default_lang;
				$this->logo = $obj->logo;

				$this->webservices_url = $obj->webservices_url;
				$this->webservices_key = $obj->webservices_key;

				$this->outstanding_limit		= $obj->outstanding_limit;
				$this->order_min_amount			= $obj->order_min_amount;
				$this->supplier_order_min_amount	= $obj->supplier_order_min_amount;

				// multiprix
				$this->price_level = $obj->price_level;

				$this->import_key = $obj->import_key;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

				// multicurrency
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->multicurrency_code = $obj->multicurrency_code;

				$result = 1;

				// fetch optionals attributes and labels
				$this->fetch_optionals();
			}
			else
			{
				$result = 0;
			}

			$this->db->free($resql);
		}
		else
		{
			$this->error=$this->db->lasterror();
			$result = -3;
		}

		// Use first price level if level not defined for third party
		if (! empty($conf->global->PRODUIT_MULTIPRICES) && empty($this->price_level)) $this->price_level=1;

		return $result;
	}

	/**
	 * 	Search and fetch thirparties by name
	 *
	 * 	@param		string		$name		Name
	 * 	@param		int			$type		Type of thirdparties (0=any, 1=customer, 2=prospect, 3=supplier)
	 * 	@param		array		$filters	Array of couple field name/value to filter the companies with the same name
	 * 	@param		boolean		$exact		Exact string search (true/false)
	 * 	@param		boolean		$case		Case sensitive (true/false)
	 * 	@param		boolean		$similar	Add test if string inside name into database, or name into database inside string. Do not use this: Not compatible with other database.
	 * 	@param		string		$clause		Clause for filters
	 * 	@return		array|int				<0 if KO, array of thirdparties object if OK
	 */
	function searchByName($name, $type='0', $filters = array(), $exact = false, $case = false, $similar = false, $clause = 'AND')
	{
		$thirdparties = array();

		dol_syslog("searchByName name=".$name." type=".$type." exact=".$exact);

		// Check parameter
		if (empty($name))
		{
			$this->errors[]='ErrorBadValueForParameter';
			return -1;
		}

		// Generation requete recherche
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe";
		$sql.= " WHERE entity IN (".getEntity('category').")";
		if (! empty($type))
		{
			if ($type == 1 || $type == 2)
				$sql.= " AND client = ".$type;
			elseif ($type == 3)
				$sql.= " AND fournisseur = 1";
		}
		if (! empty($name))
		{
			if (! $exact)
			{
				if (preg_match('/^([\*])?[^*]+([\*])?$/', $name, $regs) && count($regs) > 1)
				{
					$name = str_replace('*', '%', $name);
				}
				else
				{
					$name = '%'.$name.'%';
				}
			}
			$sql.= " AND ";
			if (is_array($filters) && ! empty($filters))
				$sql.= "(";
			if ($similar)
			{
				// For test similitude (string inside name into database, or name into database inside string)
				// Do not use this. Not compatible with other database.
				$sql.= "(LOCATE('".$this->db->escape($name)."', nom) > 0 OR LOCATE(nom, '".$this->db->escape($name)."') > 0)";
			}
			else
			{
				if (! $case)
					$sql.= "nom LIKE '".$this->db->escape($name)."'";
				else
					$sql.= "nom LIKE BINARY '".$this->db->escape($name)."'";
			}
		}
		if (is_array($filters) && ! empty($filters))
		{
			foreach($filters as $field => $value)
			{
				if (! $exact)
				{
					if (preg_match('/^([\*])?[^*]+([\*])?$/', $value, $regs) && count($regs) > 1)
					{
						$value = str_replace('*', '%', $value);
					}
					else
					{
						$value = '%'.$value.'%';
					}
				}
				if (! $case)
					$sql.= " ".$clause." ".$field." LIKE '".$this->db->escape($value)."'";
				else
					$sql.= " ".$clause." ".$field." LIKE BINARY '".$this->db->escape($value)."'";
			}
			if (! empty($name))
				$sql.= ")";
		}

		$res  = $this->db->query($sql);
		if ($res)
		{
			while ($rec = $this->db->fetch_array($res))
			{
				$soc = new Societe($this->db);
				$soc->fetch($rec['rowid']);
				$thirdparties[] = $soc;
			}

			return $thirdparties;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Delete a third party from database and all its dependencies (contacts, rib...)
	 *
	 *    @param	int		$id             Id of third party to delete
	 *    @param    User    $fuser          User who ask to delete thirparty
	 *    @param    int		$call_trigger   0=No, 1=yes
	 *    @return	int						<0 if KO, 0 if nothing done, >0 if OK
	 */
	function delete($id, User $fuser=null, $call_trigger=1)
	{
		global $langs, $conf, $user;

		if (empty($fuser)) $fuser=$user;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$entity=isset($this->entity)?$this->entity:$conf->entity;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$error = 0;

		// Test if child exists
		$objectisused = $this->isObjectUsed($id);
		if (empty($objectisused))
		{
			$this->db->begin();

			// User is mandatory for trigger call
			if (! $error && $call_trigger)
			{
				// Call trigger
				$result=$this->call_trigger('COMPANY_DELETE',$fuser);
				if ($result < 0) $error++;
				// End call triggers
			}

			if (! $error)
			{
				require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
				$static_cat = new Categorie($this->db);
				$toute_categs = array();

				// Fill $toute_categs array with an array of (type => array of ("Categorie" instance))
				if ($this->client || $this->prospect)
				{
					$toute_categs['customer'] = $static_cat->containing($this->id,Categorie::TYPE_CUSTOMER);
				}
				if ($this->fournisseur)
				{
					$toute_categs['supplier'] = $static_cat->containing($this->id,Categorie::TYPE_SUPPLIER);
				}

				// Remove each "Categorie"
				foreach ($toute_categs as $type => $categs_type)
				{
					foreach ($categs_type as $cat)
					{
						$cat->del_type($this, $type);
					}
				}
			}

			foreach ($this->childtablesoncascade as $tabletodelete)
			{
				if (! $error)
				{
					$sql = "DELETE FROM ".MAIN_DB_PREFIX.$tabletodelete;
					$sql.= " WHERE fk_soc = " . $id;
					if (! $this->db->query($sql))
					{
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}
			}

			// Removed extrafields
			if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
			{
				$result=$this->deleteExtraFields();
				if ($result < 0)
				{
					$error++;
					dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
				}
			}

			// Remove third party
			if (! $error)
			{
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."societe";
				$sql.= " WHERE rowid = " . $id;
				if (! $this->db->query($sql))
				{
					$error++;
					$this->errors[] = $this->db->lasterror();
				}
			}

			if (! $error)
			{
				$this->db->commit();

				// Delete directory
				if (! empty($conf->societe->multidir_output[$entity]))
				{
					$docdir = $conf->societe->multidir_output[$entity] . "/" . $id;
					if (dol_is_dir($docdir))
					{
						dol_delete_dir_recursive($docdir);
					}
				}

				return 1;
			}
			else
			{
				dol_syslog($this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else dol_syslog("Can't remove thirdparty with id ".$id.". There is ".$objectisused." childs", LOG_WARNING);
		return 0;
	}

	/**
	 *  Define third party as a customer
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	function set_as_client()
	{
		if ($this->id)
		{
			$newclient=1;
			if ($this->client == 2 || $this->client == 3) $newclient=3;	//If prospect, we keep prospect tag
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql.= " SET client = ".$newclient;
			$sql.= " WHERE rowid = " . $this->id;

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->client = $newclient;
				return 1;
			}
			else return -1;
		}
		return 0;
	}

	/**
	 *  Definit la societe comme un client
	 *
	 *  @param	float	$remise		Valeur en % de la remise
	 *  @param  string	$note		Note/Motif de modification de la remise
	 *  @param  User	$user		Utilisateur qui definie la remise
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function set_remise_client($remise, $note, User $user)
	{
		global $conf, $langs;

		// Nettoyage parametres
		$note=trim($note);
		if (! $note)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("NoteReason"));
			return -2;
		}

		dol_syslog(get_class($this)."::set_remise_client ".$remise.", ".$note.", ".$user->id);

		if ($this->id)
		{
			$this->db->begin();

			$now=dol_now();

			// Positionne remise courante
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql.= " SET remise_client = '".$this->db->escape($remise)."'";
			$sql.= " WHERE rowid = " . $this->id;
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -1;
			}

			// Ecrit trace dans historique des remises
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise";
			$sql.= " (entity, datec, fk_soc, remise_client, note, fk_user_author)";
			$sql.= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', ".$this->id.", '".$this->db->escape($remise)."',";
			$sql.= " '".$this->db->escape($note)."',";
			$sql.= " ".$user->id;
			$sql.= ")";

			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				return -1;
			}

			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Definit la societe comme un client
	 *
	 *  @param	float	$remise		Valeur en % de la remise
	 *  @param  string	$note		Note/Motif de modification de la remise
	 *  @param  User	$user		Utilisateur qui definie la remise
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function set_remise_supplier($remise, $note, User $user)
	{
		global $conf, $langs;

		// Nettoyage parametres
		$note=trim($note);
		if (! $note)
		{
			$this->error=$langs->trans("ErrorFieldRequired",$langs->trans("NoteReason"));
			return -2;
		}

		dol_syslog(get_class($this)."::set_remise_supplier ".$remise.", ".$note.", ".$user->id);

		if ($this->id)
		{
			$this->db->begin();

			$now=dol_now();

			// Positionne remise courante
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
			$sql.= " SET remise_supplier = '".$this->db->escape($remise)."'";
			$sql.= " WHERE rowid = " . $this->id;
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -1;
			}

			// Ecrit trace dans historique des remises
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise_supplier";
			$sql.= " (entity, datec, fk_soc, remise_supplier, note, fk_user_author)";
			$sql.= " VALUES (".$conf->entity.", '".$this->db->idate($now)."', ".$this->id.", '".$this->db->escape($remise)."',";
			$sql.= " '".$this->db->escape($note)."',";
			$sql.= " ".$user->id;
			$sql.= ")";

			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				return -1;
			}

			$this->db->commit();
			return 1;
		}
	}

	/**
	 *    	Add a discount for third party
	 *
	 *    	@param	float	$remise     	Amount of discount
	 *    	@param  User	$user       	User adding discount
	 *    	@param  string	$desc			Reason of discount
	 *      @param  float	$tva_tx     	VAT rate
	 *      @param	int		$discount_type	0 => customer discount, 1 => supplier discount
	 *		@return	int					<0 if KO, id of discount record if OK
	 */
	function set_remise_except($remise, User $user, $desc, $tva_tx=0, $discount_type=0)
	{
		global $langs;

		// Clean parameters
		$remise = price2num($remise);
		$desc = trim($desc);

		// Check parameters
		if (! $remise > 0)
		{
			$this->error=$langs->trans("ErrorWrongValueForParameter","1");
			return -1;
		}
		if (! $desc)
		{
			$this->error=$langs->trans("ErrorWrongValueForParameter","3");
			return -2;
		}

		if ($this->id)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

			$discount = new DiscountAbsolute($this->db);
			$discount->fk_soc=$this->id;
			$discount->discount_type=$discount_type;
			$discount->amount_ht=price2num($remise,'MT');
			$discount->amount_tva=price2num($remise*$tva_tx/100,'MT');
			$discount->amount_ttc=price2num($discount->amount_ht+$discount->amount_tva,'MT');
			$discount->tva_tx=price2num($tva_tx,'MT');
			$discount->description=$desc;

			$result=$discount->create($user);
			if ($result > 0)
			{
				return $result;
			}
			else
			{
				$this->error=$discount->error;
				return -3;
			}
		}
		else return 0;
	}

	/**
	 *  Renvoie montant TTC des reductions/avoirs en cours disponibles de la societe
	 *
	 *	@param	User	$user			Filtre sur un user auteur des remises
	 * 	@param	string	$filter			Filtre autre
	 * 	@param	integer	$maxvalue		Filter on max value for discount
	 * 	@param	int		$discount_type	0 => customer discount, 1 => supplier discount
	 *	@return	int					<0 if KO, Credit note amount otherwise
	 */
	function getAvailableDiscounts($user='',$filter='',$maxvalue=0,$discount_type=0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

		$discountstatic=new DiscountAbsolute($this->db);
		$result=$discountstatic->getAvailableDiscounts($this,$user,$filter,$maxvalue,$discount_type);
		if ($result >= 0)
		{
			return $result;
		}
		else
		{
			$this->error=$discountstatic->error;
			return -1;
		}
	}

	/**
	 *  Return array of sales representatives
	 *
	 *  @param	User	$user		Object user
	 *  @param	int		$mode		0=Array with properties, 1=Array of id.
	 *  @return array       		Array of sales representatives of third party
	 */
	function getSalesRepresentatives(User $user, $mode=0)
	{
		global $conf;

		$reparray=array();

		$sql = "SELECT DISTINCT u.rowid, u.login, u.lastname, u.firstname, u.email, u.statut, u.entity, u.photo";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."user as u";
		if (! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
		{
			$sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
			$sql.= " WHERE ((ug.fk_user = sc.fk_user";
			$sql.= " AND ug.entity = ".$conf->entity.")";
			$sql.= " OR u.admin = 1)";
		}
		else
			$sql.= " WHERE entity in (0, ".$conf->entity.")";

		$sql.= " AND u.rowid = sc.fk_user AND sc.fk_soc = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				if (empty($mode))
				{
					$reparray[$i]['id']=$obj->rowid;
					$reparray[$i]['lastname']=$obj->lastname;
					$reparray[$i]['firstname']=$obj->firstname;
					$reparray[$i]['email']=$obj->email;
					$reparray[$i]['statut']=$obj->statut;
					$reparray[$i]['entity']=$obj->entity;
					$reparray[$i]['login']=$obj->login;
					$reparray[$i]['photo']=$obj->photo;
				}
				else
				{
					$reparray[]=$obj->rowid;
				}
				$i++;
			}
			return $reparray;
		}
		else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Set the price level
	 *
	 * @param 	int		$price_level	Level of price
	 * @param 	User	$user			Use making change
	 * @return	int						<0 if KO, >0 if OK
	 */
	function set_price_level($price_level, User $user)
	{
		if ($this->id)
		{
			$now=dol_now();

			$sql  = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " SET price_level = '".$this->db->escape($price_level)."'";
			$sql .= " WHERE rowid = " . $this->id;

			if (! $this->db->query($sql))
			{
				dol_print_error($this->db);
				return -1;
			}

			$sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_prices";
			$sql .= " (datec, fk_soc, price_level, fk_user_author)";
			$sql .= " VALUES ('".$this->db->idate($now)."', ".$this->id.", '".$this->db->escape($price_level)."', ".$user->id.")";

			if (! $this->db->query($sql))
			{
				dol_print_error($this->db);
				return -1;
			}
			return 1;
		}
		return -1;
	}

	/**
	 *	Add link to sales representative
	 *
	 *	@param	User	$user		Object user
	 *	@param	int		$commid		Id of user
	 *	@return	void
	 */
	function add_commercial(User $user, $commid)
	{
		if ($this->id > 0 && $commid > 0)
		{
			$sql = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux";
			$sql.= " WHERE fk_soc = ".$this->id." AND fk_user =".$commid;

			$this->db->query($sql);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_commerciaux";
			$sql.= " ( fk_soc, fk_user )";
			$sql.= " VALUES (".$this->id.",".$commid.")";

			if (! $this->db->query($sql) )
			{
				dol_syslog(get_class($this)."::add_commercial Erreur");
			}
		}
	}

	/**
	 *	Add link to sales representative
	 *
	 *	@param	User	$user		Object user
	 *	@param	int		$commid		Id of user
	 *	@return	void
	 */
	function del_commercial(User $user, $commid)
	{
		if ($this->id > 0 && $commid > 0)
		{
			$sql  = "DELETE FROM  ".MAIN_DB_PREFIX."societe_commerciaux ";
			$sql .= " WHERE fk_soc = ".$this->id." AND fk_user =".$commid;

			if (! $this->db->query($sql) )
			{
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
	function getNomUrl($withpicto=0, $option='', $maxlen=0, $notooltip=0, $save_lastsearch_value=-1)
	{
		global $conf, $langs, $hookmanager;

		if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

		$name=$this->name?$this->name:$this->nom;

		if (! empty($conf->global->SOCIETE_ADD_REF_IN_LIST) && (!empty($withpicto)))
		{
			if (($this->client) && (! empty ( $this->code_client ))
				&& ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1
				|| $conf->global->SOCIETE_ADD_REF_IN_LIST == 2
				)
			)
			$code = $this->code_client . ' - ';

			if (($this->fournisseur) && (! empty ( $this->code_fournisseur ))
				&& ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1
				|| $conf->global->SOCIETE_ADD_REF_IN_LIST == 3
				)
			)
			$code .= $this->code_fournisseur . ' - ';

			if ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1)
				$name =$code.' '.$name;
			else
				$name =$code;
		}

		if (!empty($this->name_alias)) $name .= ' ('.$this->name_alias.')';

		$result=''; $label='';
		$linkstart=''; $linkend='';

		if (! empty($this->logo) && class_exists('Form'))
		{
			$label.= '<div class="photointooltip">';
			$label.= Form::showphoto('societe', $this, 0, 40, 0, 'photowithmargin', 'mini', 0);	// Important, we must force height so image will have height tags and if image is inside a tooltip, the tooltip manager can calculate height and position correctly the tooltip.
			$label.= '</div><div style="clear: both;"></div>';
		}

		$label.= '<div class="centpercent">';

		if ($option == 'customer' || $option == 'compta' || $option == 'category' || $option == 'category_supplier')
		{
		   $label.= '<u>' . $langs->trans("ShowCustomer") . '</u>';
		   $linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
		}
		else if ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
		{
			$label.= '<u>' . $langs->trans("ShowProspect") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
		}
		else if ($option == 'supplier')
		{
			$label.= '<u>' . $langs->trans("ShowSupplier") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$this->id;
		}
		else if ($option == 'agenda')
		{
			$label.= '<u>' . $langs->trans("ShowAgenda") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/agenda.php?socid='.$this->id;
		}
		else if ($option == 'project')
		{
			$label.= '<u>' . $langs->trans("ShowProject") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/project.php?socid='.$this->id;
		}
		else if ($option == 'margin')
		{
			$label.= '<u>' . $langs->trans("ShowMargin") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/margin/tabs/thirdpartyMargins.php?socid='.$this->id.'&type=1';
		}
		else if ($option == 'contact')
		{
			$label.= '<u>' . $langs->trans("ShowContacts") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/contact.php?socid='.$this->id;
		}
		else if ($option == 'ban')
		{
			$label.= '<u>' . $langs->trans("ShowBan") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/paymentmodes.php?socid='.$this->id;
		}

		// By default
		if (empty($linkstart))
		{
			$label.= '<u>' . $langs->trans("ShowCompany") . '</u>';
			$linkstart = '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$this->id;
		}

		if (! empty($this->name))
		{
			$label.= '<br><b>' . $langs->trans('Name') . ':</b> '. $this->name;
			if (! empty($this->name_alias)) $label.=' ('.$this->name_alias.')';
			$label.= '<br><b>' . $langs->trans('Email') . ':</b> '. $this->email;
		}
		if (! empty($this->country_code))
			$label.= '<br><b>' . $langs->trans('Country') . ':</b> '. $this->country_code;
		if (! empty($this->tva_intra))
			$label.= '<br><b>' . $langs->trans('VATIntra') . ':</b> '. $this->tva_intra;
		if (! empty($this->code_client) && $this->client)
			$label.= '<br><b>' . $langs->trans('CustomerCode') . ':</b> '. $this->code_client;
		if (! empty($this->code_fournisseur) && $this->fournisseur)
			$label.= '<br><b>' . $langs->trans('SupplierCode') . ':</b> '. $this->code_fournisseur;
		if (! empty($conf->accounting->enabled) && $this->client)
			$label.= '<br><b>' . $langs->trans('CustomerAccountancyCode') . ':</b> '. ($this->code_compta ? $this->code_compta : $this->code_compta_client);
		if (! empty($conf->accounting->enabled) && $this->fournisseur)
			$label.= '<br><b>' . $langs->trans('SupplierAccountancyCode') . ':</b> '. $this->code_compta_fournisseur;

		$label.= '</div>';

		// Add type of canvas
		$linkstart.=(!empty($this->canvas)?'&canvas='.$this->canvas:'');
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
		if ($add_save_lastsearch_values) $linkstart.='&save_lastsearch_values=1';
		$linkstart.='"';

		$linkclose='';
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$label=$langs->trans("ShowCompany");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.=' class="classfortooltip refurl"';

		 	/*if (! is_object($hookmanager))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
				$hookmanager=new HookManager($this->db);
			}
			$hookmanager->initHooks(array('thirdpartydao'));
			$parameters=array('id'=>$this->id);
			$reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			*/
		}
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		global $user;
		if (! $user->rights->societe->client->voir && $user->societe_id > 0 && $this->id != $user->societe_id)
		{
			$linkstart='';
			$linkend='';
		}

		$result.=$linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip valigntextbottom"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.=($maxlen?dol_trunc($name,$maxlen):$name);
		$result.=$linkend;

		global $action;
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('thirdpartydao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *    Return label of status (activity, closed)
	 *
	 *    @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *    @return   string        		Libelle
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->status,$mode);
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$statut         Id statut
	 *  @param	int		$mode           0=Long label, 1=Short label, 2=Picto + Short label, 3=Picto, 4=Picto + Long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string          		Libelle du statut
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('companies');

		if ($mode == 0)
		{
			if ($statut==0) return $langs->trans("ActivityCeased");
			if ($statut==1) return $langs->trans("InActivity");
		}
		if ($mode == 1)
		{
			if ($statut==0) return $langs->trans("ActivityCeased");
			if ($statut==1) return $langs->trans("InActivity");
		}
		if ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"').' '.$langs->trans("ActivityCeased");
			if ($statut==1) return img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"').' '.$langs->trans("InActivity");
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"');
			if ($statut==1) return img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"').' '.$langs->trans("ActivityCeased");
			if ($statut==1) return img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"').' '.$langs->trans("InActivity");
		}
		if ($mode == 5)
		{
			if ($statut==0) return '<span class="hideonsmartphone">'.$langs->trans("ActivityCeased").'</span> '.img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"');
			if ($statut==1) return '<span class="hideonsmartphone">'.$langs->trans("InActivity").'</span> '.img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"');
		}
		if ($mode == 6)
		{
			if ($statut==0) return '<span class="hideonsmartphone">'.$langs->trans("ActivityCeased").'</span> '.img_picto($langs->trans("ActivityCeased"),'statut5', 'class="pictostatus"');
			if ($statut==1) return '<span class="hideonsmartphone">'.$langs->trans("InActivity").'</span> '.img_picto($langs->trans("InActivity"),'statut4', 'class="pictostatus"');
		}
	}

	/**
	 *    Return list of contacts emails existing for third party
	 *
	 *	  @param	  int		$addthirdparty		1=Add also a record for thirdparty email
	 *    @return     array       					Array of contacts emails
	 */
	function thirdparty_and_contact_email_array($addthirdparty=0)
	{
		global $langs;

		$contact_emails = $this->contact_property_array('email',1);
		if ($this->email && $addthirdparty)
		{
			if (empty($this->name)) $this->name=$this->nom;
			$contact_emails['thirdparty']=$langs->transnoentitiesnoconv("ThirdParty").': '.dol_trunc($this->name,16)." <".$this->email.">";
		}
		//var_dump($contact_emails)
		return $contact_emails;
	}

	/**
	 *    Return list of contacts mobile phone existing for third party
	 *
	 *    @return     array       Array of contacts emails
	 */
	function thirdparty_and_contact_phone_array()
	{
		global $langs;

		$contact_phone = $this->contact_property_array('mobile');

		if (! empty($this->phone))	// If a phone of thirdparty is defined, we add it ot mobile of contacts
		{
			if (empty($this->name)) $this->name=$this->nom;
			// TODO: Tester si tel non deja present dans tableau contact
			$contact_phone['thirdparty']=$langs->transnoentitiesnoconv("ThirdParty").': '.dol_trunc($this->name,16)." <".$this->phone.">";
		}
		return $contact_phone;
	}

	/**
	 *  Return list of contacts emails or mobile existing for third party
	 *
	 *  @param	string	$mode       		'email' or 'mobile'
	 * 	@param	int		$hidedisabled		1=Hide contact if disabled
	 *  @return array       				Array of contacts emails or mobile. Example: array(id=>'Name <email>')
	 */
	function contact_property_array($mode='email', $hidedisabled=0)
	{
		global $langs;

		$contact_property = array();


		$sql = "SELECT rowid, email, statut, phone_mobile, lastname, poste, firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql.= " WHERE fk_soc = ".$this->id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			if ($nump)
			{
				$sepa="("; $sepb=")";
				if ($mode == 'email')
				{
					//$sepa="&lt;"; $sepb="&gt;";
					$sepa="<"; $sepb=">";
				}
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);
					if ($mode == 'email') $property=$obj->email;
					else if ($mode == 'mobile') $property=$obj->phone_mobile;
					else $property=$obj->$mode;

					// Show all contact. If hidedisabled is 1, showonly contacts with status = 1
					if ($obj->statut == 1 || empty($hidedisabled))
					{
						if (empty($property))
						{
							if ($mode == 'email') $property=$langs->transnoentitiesnoconv("NoEMail");
							else if ($mode == 'mobile') $property=$langs->transnoentitiesnoconv("NoMobilePhone");
						}

						if (!empty($obj->poste))
						{
							$contact_property[$obj->rowid] = trim(dolGetFirstLastname($obj->firstname,$obj->lastname)).($obj->poste?" - ".$obj->poste:"").(($mode != 'poste' && $property)?" ".$sepa.$property.$sepb:'');
						}
						else
						{
							$contact_property[$obj->rowid] = trim(dolGetFirstLastname($obj->firstname,$obj->lastname)).(($mode != 'poste' && $property)?" ".$sepa.$property.$sepb:'');
						}
					}
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $contact_property;
	}


	/**
	 *    Renvoie la liste des contacts de cette societe
	 *
	 *    @return     array      tableau des contacts
	 */
	function contact_array()
	{
		$contacts = array();

		$sql = "SELECT rowid, lastname, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$this->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);
					$contacts[$obj->rowid] = dolGetFirstLastname($obj->firstname,$obj->lastname);
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $contacts;
	}

	/**
	 *    Renvoie la liste des contacts de cette societe
	 *
	 *    @return    array    $contacts    tableau des contacts
	 */
	function contact_array_objects()
	{
		require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
		$contacts = array();

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = ".$this->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);
			if ($nump)
			{
				$i = 0;
				while ($i < $nump)
				{
					$obj = $this->db->fetch_object($resql);
					$contact = new Contact($this->db);
					$contact->fetch($obj->rowid);
					$contacts[] = $contact;
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($this->db);
		}
		return $contacts;
	}

	/**
	 *  Return property of contact from its id
	 *
	 *  @param	int		$rowid      id of contact
	 *  @param  string	$mode       'email' or 'mobile'
	 *  @return string  			Email of contact with format: "Full name <email>"
	 */
	function contact_get_property($rowid,$mode)
	{
		$contact_property='';

		if (empty($rowid)) return '';

		$sql = "SELECT rowid, email, phone_mobile, lastname, firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
		$sql.= " WHERE rowid = '".$rowid."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);

			if ($nump)
			{
				$obj = $this->db->fetch_object($resql);

				if ($mode == 'email') $contact_property = dolGetFirstLastname($obj->firstname, $obj->lastname)." <".$obj->email.">";
				else if ($mode == 'mobile') $contact_property = $obj->phone_mobile;
			}
			return $contact_property;
		}
		else
		{
			dol_print_error($this->db);
		}

	}


	/**
	 *  Return bank number property of thirdparty (label or rum)
	 *
	 *	@param	string	$mode	'label' or 'rum' or 'format'
	 *  @return	string			Bank number
	 */
	function display_rib($mode='label')
	{
		require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';

		$bac = new CompanyBankAccount($this->db);
		$bac->fetch(0,$this->id);

		if ($mode == 'label')
		{
			return $bac->getRibLabel(true);
		}
		elseif ($mode == 'rum')
		{
			if (empty($bac->rum))
			{
				require_once DOL_DOCUMENT_ROOT . '/compta/prelevement/class/bonprelevement.class.php';
				$prelevement = new BonPrelevement($this->db);
				$bac->fetch_thirdparty();
				$bac->rum = $prelevement->buildRumNumber($bac->thirdparty->code_client, $bac->datec, $bac->id);
			}
			return $bac->rum;
		}
		elseif ($mode == 'format')
		{
			return $bac->frstrecur;
		}

		return 'BadParameterToFunctionDisplayRib';
	}

	/**
	 * Return Array of RIB
	 *
	 * @return     array|int        0 if KO, Array of CompanyBanckAccount if OK
	 */
	function get_all_rib()
	{
		require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
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

	/**
	 *  Attribut un code client a partir du module de controle des codes.
	 *  Return value is stored into this->code_client
	 *
	 *	@param	Societe		$objsoc		Object thirdparty
	 *	@param	int			$type		Should be 0 to say customer
	 *  @return void
	 */
	function get_codeclient($objsoc=0,$type=0)
	{
		global $conf;
		if (! empty($conf->global->SOCIETE_CODECLIENT_ADDON))
		{
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$module.'.php');
				if ($res) break;
			}
			$mod = new $module();

			$this->code_client = $mod->getNextValue($objsoc,$type);
			$this->prefixCustomerIsRequired = $mod->prefixIsRequired;

			dol_syslog(get_class($this)."::get_codeclient code_client=".$this->code_client." module=".$module);
		}
	}

	/**
	 *  Attribut un code fournisseur a partir du module de controle des codes.
	 *  Return value is stored into this->code_fournisseur
	 *
	 *	@param	Societe		$objsoc		Object thirdparty
	 *	@param	int			$type		Should be 1 to say supplier
	 *  @return void
	 */
	function get_codefournisseur($objsoc=0,$type=1)
	{
		global $conf;
		if (! empty($conf->global->SOCIETE_CODECLIENT_ADDON))
		{
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$module.'.php');
				if ($res) break;
			}
			$mod = new $module();

			$this->code_fournisseur = $mod->getNextValue($objsoc,$type);

			dol_syslog(get_class($this)."::get_codefournisseur code_fournisseur=".$this->code_fournisseur." module=".$module);
		}
	}

	/**
	 *    Verifie si un code client est modifiable en fonction des parametres
	 *    du module de controle des codes.
	 *
	 *    @return     int		0=No, 1=Yes
	 */
	function codeclient_modifiable()
	{
		global $conf;
		if (! empty($conf->global->SOCIETE_CODECLIENT_ADDON))
		{
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$module.'.php');
				if ($res) break;
			}

			$mod = new $module();

			dol_syslog(get_class($this)."::codeclient_modifiable code_client=".$this->code_client." module=".$module);
			if ($mod->code_modifiable_null && ! $this->code_client) return 1;
			if ($mod->code_modifiable_invalide && $this->check_codeclient() < 0) return 1;
			if ($mod->code_modifiable) return 1;	// A mettre en dernier
			return 0;
		}
		else
		{
			return 0;
		}
	}


	/**
	 *    Verifie si un code fournisseur est modifiable dans configuration du module de controle des codes
	 *
	 *    @return     int		0=No, 1=Yes
	 */
	function codefournisseur_modifiable()
	{
		global $conf;
		if (! empty($conf->global->SOCIETE_CODECLIENT_ADDON))
		{
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$module.'.php');
				if ($res) break;
			}

			$mod = new $module();

			dol_syslog(get_class($this)."::codefournisseur_modifiable code_founisseur=".$this->code_fournisseur." module=".$module);
			if ($mod->code_modifiable_null && ! $this->code_fournisseur) return 1;
			if ($mod->code_modifiable_invalide && $this->check_codefournisseur() < 0) return 1;
			if ($mod->code_modifiable) return 1;	// A mettre en dernier
			return 0;
		}
		else
		{
			return 0;
		}
	}


	/**
	 *  Check customer code
	 *
	 *  @return     int				0 if OK
	 * 								-1 ErrorBadCustomerCodeSyntax
	 * 								-2 ErrorCustomerCodeRequired
	 * 								-3 ErrorCustomerCodeAlreadyUsed
	 * 								-4 ErrorPrefixRequired
	 */
	function check_codeclient()
	{
		global $conf;
		if (! empty($conf->global->SOCIETE_CODECLIENT_ADDON))
		{
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$module.'.php');
				if ($res) break;
			}

			$mod = new $module();

		   	dol_syslog(get_class($this)."::check_codeclient code_client=".$this->code_client." module=".$module);
		   	$result = $mod->verif($this->db, $this->code_client, $this, 0);
			return $result;
		}
		else
		{
			return 0;
		}
	}

	/**
	 *    Check supplier code
	 *
	 *    @return     int		0 if OK
	 * 							-1 ErrorBadCustomerCodeSyntax
	 * 							-2 ErrorCustomerCodeRequired
	 * 							-3 ErrorCustomerCodeAlreadyUsed
	 * 							-4 ErrorPrefixRequired
	 */
	function check_codefournisseur()
	{
		global $conf;
		if (! empty($conf->global->SOCIETE_CODECLIENT_ADDON))
		{
			$module=$conf->global->SOCIETE_CODECLIENT_ADDON;

			$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$module.'.php');
				if ($res) break;
			}

			$mod = new $module();

			dol_syslog(get_class($this)."::check_codefournisseur code_fournisseur=".$this->code_fournisseur." module=".$module);
			$result = $mod->verif($this->db, $this->code_fournisseur, $this, 1);
			return $result;
		}
		else
		{
			return 0;
		}
	}

	/**
	 *    	Renvoie un code compta, suivant le module de code compta.
	 *      Peut etre identique a celui saisit ou genere automatiquement.
	 *      A ce jour seule la generation automatique est implementee
	 *
	 *    	@param	string	$type		Type of thirdparty ('customer' or 'supplier')
	 *		@return	string				Code compta si ok, 0 si aucun, <0 si ko
	 */
	function get_codecompta($type)
	{
		global $conf;

		if (! empty($conf->global->SOCIETE_CODECOMPTA_ADDON))
		{
			$res=false;
			$dirsociete=array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
			foreach ($dirsociete as $dirroot)
			{
				$res=dol_include_once($dirroot.$conf->global->SOCIETE_CODECOMPTA_ADDON.'.php');
				if ($res) break;
			}

			if ($res)
			{
				$classname = $conf->global->SOCIETE_CODECOMPTA_ADDON;
				$mod = new $classname;

				// Defini code compta dans $mod->code
				$result = $mod->get_code($this->db, $this, $type);

				if ($type == 'customer') $this->code_compta = $mod->code;
				else if ($type == 'supplier') $this->code_compta_fournisseur = $mod->code;

				return $result;
			}
			else
			{
				$this->error = 'ErrorAccountancyCodeNotDefined';
				return -1;
			}
		}
		else
		{
			if ($type == 'customer') $this->code_compta = '';
			else if ($type == 'supplier') $this->code_compta_fournisseur = '';

			return 0;
		}
	}

	/**
	 *    Define parent commany of current company
	 *
	 *    @param	int		$id     Id of thirdparty to set or '' to remove
	 *    @return	int     		<0 if KO, >0 if OK
	 */
	function set_parent($id)
	{
		if ($this->id)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql.= " SET parent = ".($id > 0 ? $id : "null");
			$sql.= " WHERE rowid = " . $this->id;
			dol_syslog(get_class($this).'::set_parent', LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->parent = $id;
				return 1;
			}
			else
			{
				return -1;
			}
		}
		else return -1;
	}

	/**
	 *  Returns if a profid sould be verified
	 *
	 *  @param	int		$idprof		1,2,3,4,5,6 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm,5=idprof5,6=idprof6)
	 *  @return boolean         	true , false
	 */
	function id_prof_verifiable($idprof)
	{
		global $conf;

	 	switch($idprof)
		{
			case 1:
				$ret=(!$conf->global->SOCIETE_IDPROF1_UNIQUE?false:true);
				break;
			case 2:
				$ret=(!$conf->global->SOCIETE_IDPROF2_UNIQUE?false:true);
				break;
			case 3:
				$ret=(!$conf->global->SOCIETE_IDPROF3_UNIQUE?false:true);
				break;
			case 4:
				$ret=(!$conf->global->SOCIETE_IDPROF4_UNIQUE?false:true);
				break;
			case 5:
				$ret=(!$conf->global->SOCIETE_IDPROF5_UNIQUE?false:true);
				break;
			case 6:
				$ret=(!$conf->global->SOCIETE_IDPROF6_UNIQUE?false:true);
				break;
			default:
				$ret=false;
		}

		return $ret;
	}

	/**
	 *    Verify if a profid exists into database for others thirds
	 *
	 *    @param	string	$idprof		'idprof1','idprof2','idprof3','idprof4','idprof5','idprof6','email' (Example: idprof1=siren, idprof2=siret, idprof3=naf, idprof4=rcs/rm)
	 *    @param	string	$value		Value of profid
	 *    @param	int		$socid		Id of thirdparty to exclude (if update)
	 *    @return   boolean				True if exists, False if not
	 */
	function id_prof_exists($idprof, $value, $socid=0)
	{
		$field = $idprof;

	 	switch($idprof)	// For backward compatibility
		{
			case '1':
			case 'idprof1':
				$field="siren";
				break;
			case '2':
			case 'idprof2':
				$field="siret";
				break;
			case '3':
			case 'idprof3':
				$field="ape";
				break;
			case '4':
			case 'idprof4':
				$field="idprof4";
				break;
			case '5':
				$field="idprof5";
				break;
			case '6':
				$field="idprof6";
				break;
	 	}

		 //Verify duplicate entries
		$sql  = "SELECT COUNT(*) as idprof FROM ".MAIN_DB_PREFIX."societe WHERE ".$field." = '".$value."' AND entity IN (".getEntity('societe').")";
		if($socid) $sql .= " AND rowid <> ".$socid;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$count = $obj->idprof;
		}
		else
		{
			$count = 0;
			print $this->db->error();
		}
		$this->db->free($resql);

		if ($count > 0) return true;
		else return false;
	}

	/**
	 *  Verifie la validite d'un identifiant professionnel en fonction du pays de la societe (siren, siret, ...)
	 *
	 *  @param	int			$idprof         1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *  @param  Societe		$soc            Objet societe
	 *  @return int             			<=0 if KO, >0 if OK
	 *  TODO better to have this in a lib than into a business class
	 */
	function id_prof_check($idprof,$soc)
	{
		global $conf;

		$ok=1;

		if (! empty($conf->global->MAIN_DISABLEPROFIDRULES)) return 1;

		// Verifie SIREN si pays FR
		if ($idprof == 1 && $soc->country_code == 'FR')
		{
			$chaine=trim($this->idprof1);
			$chaine=preg_replace('/(\s)/','',$chaine);

			if (!is_numeric($chaine)) return -1;
			if (dol_strlen($chaine) != 9) return -1;

			// on prend chaque chiffre un par un
			// si son index (position dans la chaîne en commence à 0 au premier caractère) est impair
			// on double sa valeur et si cette dernière est supérieure à 9, on lui retranche 9
			// on ajoute cette valeur à la somme totale

			for ($index = 0; $index < 9; $index ++)
			{
				$number = (int) $siren[$index];
				if (($index % 2) != 0) { if (($number *= 2) > 9) $number -= 9; }
				$sum += $number;
			}

			// le numéro est valide si la somme des chiffres est multiple de 10
			if (($sum % 10) != 0) return -1;
		}

		// Verifie SIRET si pays FR
		if ($idprof == 2 && $soc->country_code == 'FR')
		{
			$chaine=trim($this->idprof2);
			$chaine=preg_replace('/(\s)/','',$chaine);

			if (!is_numeric($chaine)) return -1;
			if (dol_strlen($chaine) != 14) return -1;

			// on prend chaque chiffre un par un
			// si son index (position dans la chaîne en commence à 0 au premier caractère) est pair
			// on double sa valeur et si cette dernière est supérieure à 9, on lui retranche 9
			// on ajoute cette valeur à la somme totale

			for ($index = 0; $index < 14; $index ++)
			{
				$number = (int) $chaine[$index];
				if (($index % 2) == 0) { if (($number *= 2) > 9) $number -= 9; }
				$sum += $number;
			}

			// le numéro est valide si la somme des chiffres est multiple de 10
			if (($sum % 10) != 0) return -1;
		}

		//Verify CIF/NIF/NIE if pays ES
		//Returns: 1 if NIF ok, 2 if CIF ok, 3 if NIE ok, -1 if NIF bad, -2 if CIF bad, -3 if NIE bad, 0 if unexpected bad
		if ($idprof == 1 && $soc->country_code == 'ES')
		{
			$string=trim($this->idprof1);
			$string=preg_replace('/(\s)/','',$string);
			$string = strtoupper($string);

			for ($i = 0; $i < 9; $i ++)
			$num[$i] = substr($string, $i, 1);

			//Check format
			if (!preg_match('/((^[A-Z]{1}[0-9]{7}[A-Z0-9]{1}$|^[T]{1}[A-Z0-9]{8}$)|^[0-9]{8}[A-Z]{1}$)/', $string))
			return 0;

			//Check NIF
			if (preg_match('/(^[0-9]{8}[A-Z]{1}$)/', $string))
				if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($string, 0, 8) % 23, 1))
				return 1;
				else
				return -1;

			//algorithm checking type code CIF
			$sum = $num[2] + $num[4] + $num[6];
			for ($i = 1; $i < 8; $i += 2)
			$sum += substr((2 * $num[$i]),0,1) + substr((2 * $num[$i]),1,1);
			$n = 10 - substr($sum, strlen($sum) - 1, 1);

			//Chek special NIF
			if (preg_match('/^[KLM]{1}/', $string))
				if ($num[8] == chr(64 + $n) || $num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr($string, 1, 8) % 23, 1))
				return 1;
				else
				return -1;

			//Check CIF
			if (preg_match('/^[ABCDEFGHJNPQRSUVW]{1}/', $string))
				if ($num[8] == chr(64 + $n) || $num[8] == substr($n, strlen($n) - 1, 1))
				return 2;
				else
				return -2;

			//Check NIE T
			if (preg_match('/^[T]{1}/', $string))
				if ($num[8] == preg_match('/^[T]{1}[A-Z0-9]{8}$/', $string))
				return 3;
				else
				return -3;

			//Check NIE XYZ
			if (preg_match('/^[XYZ]{1}/', $string))
				if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr(str_replace(array('X','Y','Z'), array('0','1','2'), $string), 0, 8) % 23, 1))
				return 3;
				else
				return -3;

			//Can not be verified
			return -4;
		}

		//Verify NIF if country is PT
		//Returns: 1 if NIF ok, -1 if NIF bad, 0 if unexpected bad
		if ($idprof == 1 && $soc->country_code == 'PT')
		{
			$string=trim($this->idprof1);
			$string=preg_replace('/(\s)/','',$string);

			for ($i = 0; $i < 9; $i ++) {
				$num[$i] = substr($string, $i, 1);
			}

			//Check NIF
			if (preg_match('/(^[0-9]{9}$)/', $string)) {
				return 1;
			}
			else {
				return -1;
			}
		}

		return $ok;
	}

	/**
	 *   Return an url to check online a professional id or empty string
	 *
	 *   @param		int		$idprof         1,2,3,4 (Example: 1=siren,2=siret,3=naf,4=rcs/rm)
	 *   @param 	Societe	$thirdparty     Object thirdparty
	 *   @return	string          		Url or empty string if no URL known
	 *   TODO better in a lib than into business class
	 */
	function id_prof_url($idprof,$thirdparty)
	{
		global $conf,$langs,$hookmanager;

		$url='';
		$action = '';

		$hookmanager->initHooks(array('idprofurl'));
		$parameters=array('idprof'=>$idprof, 'company'=>$thirdparty);
		$reshook=$hookmanager->executeHooks('getIdProfUrl',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
		if (empty($reshook)) {
			if (! empty($conf->global->MAIN_DISABLEPROFIDRULES)) {
				return '';
			}

			// TODO Move links to validate professional ID into a dictionary table "country" + "link"
			if ($idprof == 1 && $thirdparty->country_code == 'FR') {
				$url='http://www.societe.com/cgi-bin/search?champs='.$thirdparty->idprof1;    // See also http://avis-situation-sirene.insee.fr/
			}
			if ($idprof == 1 && ($thirdparty->country_code == 'GB' || $thirdparty->country_code == 'UK')) {
				$url='https://beta.companieshouse.gov.uk/company/'.$thirdparty->idprof1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'ES') {
				$url='http://www.e-informa.es/servlet/app/portal/ENTP/screen/SProducto/prod/ETIQUETA_EMPRESA/nif/'.$thirdparty->idprof1;
			}
			if ($idprof == 1 && $thirdparty->country_code == 'IN') {
				$url='http://www.tinxsys.com/TinxsysInternetWeb/dealerControllerServlet?tinNumber='.$thirdparty->idprof1.';&searchBy=TIN&backPage=searchByTin_Inter.jsp';
			}
			if ($idprof == 1 && $thirdparty->country_code == 'PT') {
				$url='http://www.nif.pt/'.$thirdparty->idprof1;
			}

			if ($url) {
				return '<a target="_blank" href="'.$url.'">'.$langs->trans("Check").'</a>';
			}
		}
		else {
			return $hookmanager->resPrint;
		}

		return '';
	}

	/**
	 *   Indique si la societe a des projets
	 *
	 *   @return     bool	   true si la societe a des projets, false sinon
	 */
	function has_projects()
	{
		$sql = 'SELECT COUNT(*) as numproj FROM '.MAIN_DB_PREFIX.'projet WHERE fk_soc = ' . $this->id;
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$count = $obj->numproj;
		}
		else
		{
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
	function info($id)
	{
		$sql = "SELECT s.rowid, s.nom as name, s.datec as date_creation, tms as date_modification,";
		$sql.= " fk_user_creat, fk_user_modif";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE s.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				if ($obj->fk_user_creat) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_creat);
					$this->user_creation     = $cuser;
				}

				if ($obj->fk_user_modif) {
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_modif);
					$this->user_modification = $muser;
				}

				$this->ref			     = $obj->name;
				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return if third party is a company (Business) or an end user (Consumer)
	 *
	 *  @return    boolean     true=is a company, false=a and user
	 */
	function isACompany()
	{
		global $conf;

		// Define if third party is treated as company (or not) when nature is unknown
		$isacompany=empty($conf->global->MAIN_UNKNOWN_CUSTOMERS_ARE_COMPANIES)?0:1; // 0 by default
		if (! empty($this->tva_intra)) $isacompany=1;
		else if (! empty($this->typent_code) && in_array($this->typent_code,array('TE_PRIVATE'))) $isacompany=0;
		else if (! empty($this->typent_code) && in_array($this->typent_code,array('TE_SMALL','TE_MEDIUM','TE_LARGE','TE_GROUP'))) $isacompany=1;

		return $isacompany;
	}

	/**
	 *  Charge la liste des categories fournisseurs
	 *
	 *  @return    int      0 if success, <> 0 if error
	 */
	function LoadSupplierCateg()
	{
		$this->SupplierCategories = array();
		$sql = "SELECT rowid, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."categorie";
		$sql.= " WHERE type = ".Categorie::TYPE_SUPPLIER;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql) )
			{
				$this->SupplierCategories[$obj->rowid] = $obj->label;
			}
			return 0;
		}
		else
		{
			return -1;
		}
	}

	/**
	 *  Insert link supplier - category
	 *
	 *	@param	int		$categorie_id		Id of category
	 *  @return int      					0 if success, <> 0 if error
	 */
	function AddFournisseurInCategory($categorie_id)
	{
		if ($categorie_id > 0 && $this->id > 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_fournisseur (fk_categorie, fk_soc) ";
			$sql.= " VALUES (".$categorie_id.", ".$this->id.")";

			if ($resql=$this->db->query($sql)) return 0;
		}
		else
		{
			return 0;
		}
		return -1;
	}


	/**
	 *  Create a third party into database from a member object
	 *
	 *  @param	Adherent	$member		Object member
	 * 	@param	string	$socname		Name of third party to force
	 *	@param	string	$socalias	Alias name of third party to force
	 *  @return int					<0 if KO, id of created account if OK
	 */
	function create_from_member(Adherent $member, $socname='', $socalias='')
	{
		global $user,$langs;

		dol_syslog(get_class($this)."::create_from_member", LOG_DEBUG);

		$name = $socname?$socname:$member->societe;
		if (empty($name)) $name=$member->getFullName($langs);

		$alias = $socalias?$socalias:'';

		// Positionne parametres
		$this->nom=$name;				// TODO deprecated
		$this->name=$name;
		$this->name_alias=$alias;
		$this->address=$member->address;
		$this->zip=$member->zip;
		$this->town=$member->town;
		$this->country_code=$member->country_code;
		$this->country_id=$member->country_id;
		$this->phone=$member->phone;       // Prof phone
		$this->email=$member->email;
		$this->skype=$member->skype;

		$this->client = 1;				// A member is a customer by default
		$this->code_client = -1;
		$this->code_fournisseur = -1;

		$this->db->begin();

		// Cree et positionne $this->id
		$result=$this->create($user);
		if ($result >= 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
			$sql.= " SET fk_soc=".$this->id;
			$sql.= " WHERE rowid=".$member->id;

			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->error=$this->db->error();

				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			// $this->error deja positionne
			dol_syslog(get_class($this)."::create_from_member - 2 - ".$this->error." - ".join(',',$this->errors), LOG_ERR);

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
	function setMysoc(Conf $conf)
	{
		global $langs;

		$this->id=0;
		$this->name=empty($conf->global->MAIN_INFO_SOCIETE_NOM)?'':$conf->global->MAIN_INFO_SOCIETE_NOM;
		$this->address=empty($conf->global->MAIN_INFO_SOCIETE_ADDRESS)?'':$conf->global->MAIN_INFO_SOCIETE_ADDRESS;
		$this->zip=empty($conf->global->MAIN_INFO_SOCIETE_ZIP)?'':$conf->global->MAIN_INFO_SOCIETE_ZIP;
		$this->town=empty($conf->global->MAIN_INFO_SOCIETE_TOWN)?'':$conf->global->MAIN_INFO_SOCIETE_TOWN;
		$this->state_id=empty($conf->global->MAIN_INFO_SOCIETE_STATE)?'':$conf->global->MAIN_INFO_SOCIETE_STATE;
		$this->region_code=empty($conf->global->MAIN_INFO_SOCIETE_REGION)?'':$conf->global->MAIN_INFO_SOCIETE_REGION;

		$this->note_private=empty($conf->global->MAIN_INFO_SOCIETE_NOTE)?'':$conf->global->MAIN_INFO_SOCIETE_NOTE;

		$this->nom=$this->name; 									// deprecated

		// We define country_id, country_code and country
		$country_id=$country_code=$country_label='';
		if (! empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY))
		{
			$tmp=explode(':',$conf->global->MAIN_INFO_SOCIETE_COUNTRY);
			$country_id=$tmp[0];
			if (! empty($tmp[1]))   // If $conf->global->MAIN_INFO_SOCIETE_COUNTRY is "id:code:label"
			{
				$country_code=$tmp[1];
				$country_label=$tmp[2];
			}
			else                    // For backward compatibility
			{
				dol_syslog("Your country setup use an old syntax. Reedit it using setup area.", LOG_ERR);
				include_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
				$country_code=getCountry($country_id,2,$this->db);  // This need a SQL request, but it's the old feature that should not be used anymore
				$country_label=getCountry($country_id,0,$this->db);  // This need a SQL request, but it's the old feature that should not be used anymore
			}
		}
		$this->country_id=$country_id;
		$this->country_code=$country_code;
		$this->country=$country_label;
		if (is_object($langs)) $this->country=($langs->trans('Country'.$country_code)!='Country'.$country_code)?$langs->trans('Country'.$country_code):$country_label;

		$this->phone=empty($conf->global->MAIN_INFO_SOCIETE_TEL)?'':$conf->global->MAIN_INFO_SOCIETE_TEL;
		$this->fax=empty($conf->global->MAIN_INFO_SOCIETE_FAX)?'':$conf->global->MAIN_INFO_SOCIETE_FAX;
		$this->url=empty($conf->global->MAIN_INFO_SOCIETE_WEB)?'':$conf->global->MAIN_INFO_SOCIETE_WEB;
		// Id prof generiques
		$this->idprof1=empty($conf->global->MAIN_INFO_SIREN)?'':$conf->global->MAIN_INFO_SIREN;
		$this->idprof2=empty($conf->global->MAIN_INFO_SIRET)?'':$conf->global->MAIN_INFO_SIRET;
		$this->idprof3=empty($conf->global->MAIN_INFO_APE)?'':$conf->global->MAIN_INFO_APE;
		$this->idprof4=empty($conf->global->MAIN_INFO_RCS)?'':$conf->global->MAIN_INFO_RCS;
		$this->idprof5=empty($conf->global->MAIN_INFO_PROFID5)?'':$conf->global->MAIN_INFO_PROFID5;
		$this->idprof6=empty($conf->global->MAIN_INFO_PROFID6)?'':$conf->global->MAIN_INFO_PROFID6;
		$this->tva_intra=empty($conf->global->MAIN_INFO_TVAINTRA)?'':$conf->global->MAIN_INFO_TVAINTRA;	// VAT number, not necessarly INTRA.
		$this->managers=empty($conf->global->MAIN_INFO_SOCIETE_MANAGERS)?'':$conf->global->MAIN_INFO_SOCIETE_MANAGERS;
		$this->capital=empty($conf->global->MAIN_INFO_CAPITAL)?'':$conf->global->MAIN_INFO_CAPITAL;
		$this->forme_juridique_code=empty($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE)?'':$conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE;
		$this->email=empty($conf->global->MAIN_INFO_SOCIETE_MAIL)?'':$conf->global->MAIN_INFO_SOCIETE_MAIL;
		$this->logo=empty($conf->global->MAIN_INFO_SOCIETE_LOGO)?'':$conf->global->MAIN_INFO_SOCIETE_LOGO;
		$this->logo_small=empty($conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL)?'':$conf->global->MAIN_INFO_SOCIETE_LOGO_SMALL;
		$this->logo_mini=empty($conf->global->MAIN_INFO_SOCIETE_LOGO_MINI)?'':$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;

		// Define if company use vat or not
		$this->tva_assuj=$conf->global->FACTURE_TVAOPTION;

		// Define if company use local taxes
		$this->localtax1_assuj=((isset($conf->global->FACTURE_LOCAL_TAX1_OPTION) && ($conf->global->FACTURE_LOCAL_TAX1_OPTION=='1' || $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on'))?1:0);
		$this->localtax2_assuj=((isset($conf->global->FACTURE_LOCAL_TAX2_OPTION) && ($conf->global->FACTURE_LOCAL_TAX2_OPTION=='1' || $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on'))?1:0);
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	function initAsSpecimen()
	{
		$now=dol_now();

		// Initialize parameters
		$this->id=0;
		$this->name = 'THIRDPARTY SPECIMEN '.dol_print_date($now,'dayhourlog');
		$this->nom = $this->name;   // For backward compatibility
		$this->ref_ext = 'Ref ext';
		$this->specimen=1;
		$this->address='21 jump street';
		$this->zip='99999';
		$this->town='MyTown';
		$this->state_id=1;
		$this->state_code='AA';
		$this->state='MyState';
		$this->country_id=1;
		$this->country_code='FR';
		$this->email='specimen@specimen.com';
		$this->skype='tom.hanson';
		$this->url='http://www.specimen.com';

		$this->phone='0909090901';
		$this->fax='0909090909';

		$this->code_client='CC-'.dol_print_date($now,'dayhourlog');
		$this->code_fournisseur='SC-'.dol_print_date($now,'dayhourlog');
		$this->capital=10000;
		$this->client=1;
		$this->prospect=1;
		$this->fournisseur=1;
		$this->tva_assuj=1;
		$this->tva_intra='EU1234567';
		$this->note_public='This is a comment (public)';
		$this->note_private='This is a comment (private)';

		$this->idprof1='idprof1';
		$this->idprof2='idprof2';
		$this->idprof3='idprof3';
		$this->idprof4='idprof4';
		$this->idprof5='idprof5';
		$this->idprof6='idprof6';
	}

	/**
	 *  Check if we must use localtax feature or not according to country (country of $mysoc in most cases).
	 *
	 *	@param		int		$localTaxNum	To get info for only localtax1 or localtax2
	 *  @return		boolean					true or false
	 */
	function useLocalTax($localTaxNum=0)
	{
		$sql  = "SELECT t.localtax1, t.localtax2";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$this->db->escape($this->country_code)."'";
		$sql .= " AND t.active = 1";
		if (empty($localTaxNum))   $sql .= " AND (t.localtax1_type <> '0' OR t.localtax2_type <> '0')";
		elseif ($localTaxNum == 1) $sql .= " AND t.localtax1_type <> '0'";
		elseif ($localTaxNum == 2) $sql .= " AND t.localtax2_type <> '0'";

		dol_syslog("useLocalTax", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
   			return ($this->db->num_rows($resql) > 0);
		}
		else return false;
	}

	/**
	 *  Check if we must use NPR Vat (french stupid rule) or not according to country (country of $mysoc in most cases).
	 *
	 *  @return		boolean					true or false
	 */
	function useNPR()
	{
		$sql  = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$this->db->escape($this->country_code)."'";
		$sql .= " AND t.active = 1 AND t.recuperableonly = 1";

		dol_syslog("useNPR", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			return ($this->db->num_rows($resql) > 0);
		}
		else return false;
	}

	/**
	 *  Check if we must use revenue stamps feature or not according to country (country of $mysocin most cases).
	 *
	 *  @return		boolean			true or false
	 */
	function useRevenueStamp()
	{
		$sql  = "SELECT COUNT(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_revenuestamp as r, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE r.fk_pays = c.rowid AND c.code = '".$this->db->escape($this->country_code)."'";
		$sql .= " AND r.active = 1";

		dol_syslog("useRevenueStamp", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
   			return (($obj->nb > 0)?true:false);
		}
		else
		{
			$this->error=$this->db->lasterror();
			return false;
		}
	}

	/**
	 *	Return prostect level
	 *
	 *  @return     string        Libelle
	 */
	function getLibProspLevel()
	{
		return $this->LibProspLevel($this->fk_prospectlevel);
	}

	/**
	 *  Return label of prospect level
	 *
	 *  @param	int		$fk_prospectlevel   	Prospect level
	 *  @return string        					label of level
	 */
	function LibProspLevel($fk_prospectlevel)
	{
		global $langs;

		$lib=$langs->trans("ProspectLevel".$fk_prospectlevel);
		// If lib not found in language file, we get label from cache/databse
		if ($lib == $langs->trans("ProspectLevel".$fk_prospectlevel))
		{
			$lib=$langs->getLabelFromKey($this->db,$fk_prospectlevel,'c_prospectlevel','code','label');
		}
		return $lib;
	}


	/**
	 *  Set prospect level
	 *
	 *  @param  User	$user		Utilisateur qui definie la remise
	 *	@return	int					<0 if KO, >0 if OK
	 * @deprecated Use update function instead
	 */
	function set_prospect_level(User $user)
	{
		return $this->update($this->id, $user);
	}

	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @param	string	$label		Label to use for status for added status
	 *  @return string        		Libelle
	 */
	function getLibProspCommStatut($mode=0, $label='')
	{
		return $this->LibProspCommStatut($this->stcomm_id, $mode, $label);
	}

	/**
	 *  Return label of a given status
	 *
	 *  @param	int|string	$statut        	Id or code for prospection status
	 *  @param  int			$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @param	string		$label			Label to use for status for added status
	 *  @return string       	 			Libelle du statut
	 */
	function LibProspCommStatut($statut, $mode=0, $label='')
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 2)
		{
			if ($statut == '-1' || $statut == 'ST_NO')         return img_action($langs->trans("StatusProspect-1"),-1).' '.$langs->trans("StatusProspect-1");
			elseif ($statut ==  '0' || $statut == 'ST_NEVER') return img_action($langs->trans("StatusProspect0"), 0).' '.$langs->trans("StatusProspect0");
			elseif ($statut ==  '1' || $statut == 'ST_TODO')  return img_action($langs->trans("StatusProspect1"), 1).' '.$langs->trans("StatusProspect1");
			elseif ($statut ==  '2' || $statut == 'ST_PEND')  return img_action($langs->trans("StatusProspect2"), 2).' '.$langs->trans("StatusProspect2");
			elseif ($statut ==  '3' || $statut == 'ST_DONE')  return img_action($langs->trans("StatusProspect3"), 3).' '.$langs->trans("StatusProspect3");
			else
			{
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, 0).' '.(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label);
			}
		}
		if ($mode == 3)
		{
			if ($statut == '-1' || $statut == 'ST_NO')         return img_action($langs->trans("StatusProspect-1"),-1);
			elseif ($statut ==  '0' || $statut == 'ST_NEVER') return img_action($langs->trans("StatusProspect0"), 0);
			elseif ($statut ==  '1' || $statut == 'ST_TODO')  return img_action($langs->trans("StatusProspect1"), 1);
			elseif ($statut ==  '2' || $statut == 'ST_PEND')  return img_action($langs->trans("StatusProspect2"), 2);
			elseif ($statut ==  '3' || $statut == 'ST_DONE')  return img_action($langs->trans("StatusProspect3"), 3);
			else
			{
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, 0);
			}
		}
		if ($mode == 4)
		{
			if ($statut == '-1' || $statut == 'ST_NO')         return img_action($langs->trans("StatusProspect-1"),-1).' '.$langs->trans("StatusProspect-1");
			elseif ($statut ==  '0' || $statut == 'ST_NEVER') return img_action($langs->trans("StatusProspect0"), 0).' '.$langs->trans("StatusProspect0");
			elseif ($statut ==  '1' || $statut == 'ST_TODO')  return img_action($langs->trans("StatusProspect1"), 1).' '.$langs->trans("StatusProspect1");
			elseif ($statut ==  '2' || $statut == 'ST_PEND')  return img_action($langs->trans("StatusProspect2"), 2).' '.$langs->trans("StatusProspect2");
			elseif ($statut ==  '3' || $statut == 'ST_DONE')  return img_action($langs->trans("StatusProspect3"), 3).' '.$langs->trans("StatusProspect3");
			else
			{
				return img_action(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label, 0).' '.(($langs->trans("StatusProspect".$statut) != "StatusProspect".$statut) ? $langs->trans("StatusProspect".$statut) : $label);
			}
		}

		return "Error, mode/status not found";
	}

	/**
	 *  Set outstanding value
	 *
	 *  @param  User	$user		User making change
	 *	@return	int					<0 if KO, >0 if OK
	 * @deprecated Use update function instead
	 */
	function set_OutstandingBill(User $user)
	{
		return $this->update($this->id, $user);
	}

	/**
	 *  Return amount of order not paid and total
	 *
	 *  @param     string      $mode    'customer' or 'supplier'
	 *  @return    array				array('opened'=>Amount, 'total'=>Total amount)
	 */
	function getOutstandingProposals($mode='customer')
	{
		$table='propal';
		if ($mode == 'supplier') $table = 'supplier_proposal';

		$sql  = "SELECT rowid, total_ht, total as total_ttc, fk_statut FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ". $this->id;
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('supplier_proposal').")";
		} else {
			$sql .= " AND entity IN (".getEntity('propal').")";
		}

		dol_syslog("getOutstandingProposals", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			while($obj=$this->db->fetch_object($resql)) {
				$outstandingTotal+= $obj->total_ht;
				$outstandingTotalIncTax+= $obj->total_ttc;
				if ($obj->fk_statut != 0)    // Not a draft
				{
					$outstandingOpened+=$obj->total_ttc;
				}
			}
			return array('opened'=>$outstandingOpened, 'total_ht'=>$outstandingTotal, 'total_ttc'=>$outstandingTotalIncTax);	// 'opened' is 'incl taxes'
		}
		else
			return array();
	}

	/**
	 *  Return amount of order not paid and total
	 *
	 *  @param     string      $mode    'customer' or 'supplier'
	 *  @return		array				array('opened'=>Amount, 'total'=>Total amount)
	 */
	function getOutstandingOrders($mode='customer')
	{
		$table='commande';
		if ($mode == 'supplier') $table = 'commande_fournisseur';

		$sql  = "SELECT rowid, total_ht, total_ttc, fk_statut FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ". $this->id;
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('supplier_order').")";
		} else {
			$sql .= " AND entity IN (".getEntity('commande').")";
		}

		dol_syslog("getOutstandingOrders", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			while($obj=$this->db->fetch_object($resql)) {
				$outstandingTotal+= $obj->total_ht;
				$outstandingTotalIncTax+= $obj->total_ttc;
				if ($obj->fk_statut != 0)    // Not a draft
				{
					$outstandingOpened+=$obj->total_ttc;
				}
			}
			return array('opened'=>$outstandingOpened, 'total_ht'=>$outstandingTotal, 'total_ttc'=>$outstandingTotalIncTax);	// 'opened' is 'incl taxes'
		}
		else
			return array();
	}

	/**
	 *  Return amount of bill not paid and total
	 *
	 *  @param     string      $mode    'customer' or 'supplier'
	 *  @return		array				array('opened'=>Amount, 'total'=>Total amount)
	 */
	function getOutstandingBills($mode='customer')
	{
		$table='facture';
		if ($mode == 'supplier') $table = 'facture_fourn';

		/* Accurate value of remain to pay is to sum remaintopay for each invoice
		 $paiement = $invoice->getSommePaiement();
		 $creditnotes=$invoice->getSumCreditNotesUsed();
		 $deposits=$invoice->getSumDepositsUsed();
		 $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
		 $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
		 */
		if ($mode == 'supplier') $sql  = "SELECT rowid, total_ht as total_ht, total_ttc, paye, fk_statut, close_code FROM ".MAIN_DB_PREFIX.$table." as f";
		else $sql  = "SELECT rowid, total as total_ht, total_ttc, paye, fk_statut, close_code FROM ".MAIN_DB_PREFIX.$table." as f";
		$sql .= " WHERE fk_soc = ". $this->id;
		if ($mode == 'supplier') {
			$sql .= " AND entity IN (".getEntity('facture_fourn').")";
		} else {
			$sql .= " AND entity IN (".getEntity('facture').")";
		}

		dol_syslog("getOutstandingBills", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$outstandingOpened = 0;
			$outstandingTotal = 0;
			$outstandingTotalIncTax = 0;
			if ($mode == 'supplier')
			{
				require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
				$tmpobject=new FactureFournisseur($this->db);
			}
			else
			{
				require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
				$tmpobject=new Facture($this->db);
			}
			while($obj=$this->db->fetch_object($resql)) {
				$tmpobject->id=$obj->rowid;
				if ($obj->fk_statut != 0                                           // Not a draft
					&& ! ($obj->fk_statut == 3 && $obj->close_code == 'replaced')  // Not a replaced invoice
					)
				{
					$outstandingTotal+= $obj->total_ht;
					$outstandingTotalIncTax+= $obj->total_ttc;
				}
				if ($obj->paye == 0
					&& $obj->fk_statut != 0    // Not a draft
					&& $obj->fk_statut != 3	   // Not abandonned
					&& $obj->fk_statut != 2)   // Not classified as paid
				//$sql .= " AND (fk_statut <> 3 OR close_code <> 'abandon')";		// Not abandonned for undefined reason
				{
					$paiement = $tmpobject->getSommePaiement();
					$creditnotes = $tmpobject->getSumCreditNotesUsed();
					$deposits = $tmpobject->getSumDepositsUsed();
					$outstandingOpened+=$obj->total_ttc - $paiement - $creditnotes - $deposits;
				}
			}
			return array('opened'=>$outstandingOpened, 'total_ht'=>$outstandingTotal, 'total_ttc'=>$outstandingTotalIncTax);	// 'opened' is 'incl taxes'
		}
		else
		{
			return array();
		}
	}

	/**
	 *  Return amount of bill not paid
	 *
	 *  @return		int				Amount in debt for thirdparty
	 *  @deprecated
	 */
	function get_OutstandingBill()
	{
		/* Accurate value of remain to pay is to sum remaintopay for each invoice
	     $paiement = $invoice->getSommePaiement();
	     $creditnotes=$invoice->getSumCreditNotesUsed();
	     $deposits=$invoice->getSumDepositsUsed();
	     $alreadypayed=price2num($paiement + $creditnotes + $deposits,'MT');
	     $remaintopay=price2num($invoice->total_ttc - $paiement - $creditnotes - $deposits,'MT');
	     */
		$sql  = "SELECT rowid, total_ttc FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE fk_soc = ". $this->id;
		$sql .= " AND paye = 0";
		$sql .= " AND fk_statut <> 0";	// Not a draft
		//$sql .= " AND (fk_statut <> 3 OR close_code <> 'abandon')";		// Not abandonned for undefined reason
		$sql .= " AND fk_statut <> 3";		// Not abandonned
		$sql .= " AND fk_statut <> 2";		// Not clasified as paid

		dol_syslog("get_OutstandingBill", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$outstandingAmount = 0;
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			$tmpobject=new Facture($this->db);
			while($obj=$this->db->fetch_object($resql)) {
				$tmpobject->id=$obj->rowid;
				$paiement = $tmpobject->getSommePaiement();
				$creditnotes = $tmpobject->getSumCreditNotesUsed();
				$deposits = $tmpobject->getSumDepositsUsed();
				$outstandingAmount+= $obj->total_ttc - $paiement - $creditnotes - $deposits;
			}
			return $outstandingAmount;
		}
		else
			return 0;
	}

	/**
	 * Return label of status customer is prospect/customer
	 *
	 * @return   string        	Label
	 */
	function getLibCustProspStatut()
	{
		return $this->LibCustProspStatut($this->client);
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$statut         Id statut
	 *  @return	string          		Libelle du statut
	 */
	function LibCustProspStatut($statut)
	{
		global $langs;
		$langs->load('companies');

		if ($statut==0) return $langs->trans("NorProspectNorCustomer");
		if ($statut==1) return $langs->trans("Customer");
		if ($statut==2) return $langs->trans("Prospect");
		if ($statut==3) return $langs->trans("ProspectCustomer");

	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *	@param	string		$modele			Generator to use. Caller must set it to obj->modelpdf or GETPOST('modelpdf') for example.
	 *	@param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @param  null|array  $moreparams     Array to provide more information
	 *	@return int        					<0 if KO, >0 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $moreparams=null)
	{
		global $conf,$user,$langs;

		if (! empty($moreparams) && ! empty($moreparams['use_companybankid']))
		{
			$modelpath = "core/modules/bank/doc/";

			include_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
			$companybankaccount = new CompanyBankAccount($this->db);
			$result = $companybankaccount->fetch($moreparams['use_companybankid']);
			if (! $result) dol_print_error($this->db, $companybankaccount->error, $companybankaccount->errors);
			$result=$companybankaccount->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}
		else
		{
			// Positionne le modele sur le nom du modele a utiliser
			if (! dol_strlen($modele))
			{
				if (! empty($conf->global->COMPANY_ADDON_PDF))
				{
					$modele = $conf->global->COMPANY_ADDON_PDF;
				}
				else
				{
					print $langs->trans("Error")." ".$langs->trans("Error_COMPANY_ADDON_PDF_NotDefined");
					return 0;
				}
			}

			$modelpath = "core/modules/societe/doc/";

			$result=$this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
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
	 * @param 	string 		$type 			Category type ('customer' or 'supplier')
	 * @return	int							<0 if KO, >0 if OK
	 */
	public function setCategories($categories, $type)
	{
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

		// Decode type
		if ($type == 'customer') {
			$type_id = Categorie::TYPE_CUSTOMER;
			$type_text = 'customer';
		} elseif ($type == 'supplier') {
			$type_id = Categorie::TYPE_SUPPLIER;
			$type_text = 'supplier';
		} else {
			dol_syslog(__METHOD__ . ': Type ' . $type .  'is an unknown company category type. Done nothing.', LOG_ERR);
			return -1;
		}

		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, $type_id, 'id');

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
				$c->del_type($this, $type_text);
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0)
			{
				$result = $c->add_type($this, $type_text);
				if ($result < 0)
				{
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
	 * @return	int							<0 if KO, >0 if OK
	 */
	public function setSalesRep($salesrep)
	{
		global $user;

		// Handle single user
		if (!is_array($salesrep)) {
			$salesrep = array($salesrep);
		}

		// Get current users
		$existing = $this->getSalesRepresentatives($user, 1);

		// Diff
		if (is_array($existing)) {
			$to_del = array_diff($existing, $salesrep);
			$to_add = array_diff($salesrep, $existing);
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = $salesrep;
		}

		$error = 0;

		// Process
		foreach ($to_del as $del) {
			$this->del_commercial($user, $del);
		}
		foreach ($to_add as $add) {
			$result = $this->add_commercial($user, $add);
			if ($result < 0)
			{
				$error++;
				$this->error = $c->error;
				$this->errors = $c->errors;
				break;
			}
		}

		return $error ? -1 : 1;
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
		if ($origin_id == $dest_id)
		{
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

		$query = $db->query($sql);

		while ($result = $db->fetch_object($query)) {
			$db->query('DELETE FROM '.MAIN_DB_PREFIX.'societe_commerciaux WHERE rowid = '.$result->rowid);
		}

		/**
		 * llx_societe_extrafields table must not be here because we don't care about the old thirdparty data
		 * Do not include llx_societe because it will be replaced later
		 */
		$tables = array(
			'societe_address',
			'societe_commerciaux',
			'societe_log',
			'societe_prices',
			'societe_remise',
			'societe_remise_except',
			'societe_rib'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}
