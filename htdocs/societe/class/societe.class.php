<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2008      Patrick Raguin       <patrick.raguin@auguria.net>
 * Copyright (C) 2010-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013      Alexandre Spangaro 	<alexandre.spangaro@gmail.com>
 * Copyright (C) 2013      Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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


/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class Societe extends CommonObject
{
    public $element='societe';
    public $table_element = 'societe';
	public $fk_element='fk_soc';
    protected $childtables=array("askpricesupplier","propal","commande","facture","contrat","facture_fourn","commande_fournisseur","projet");    // To test if we can delete object

    /**
     * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     * @var int
     */
    protected $ismultientitymanaged = 1;

    var $id;
    var $name;
    var $entity;

    /**
     * @deprecated Use $name instead
     */
    var $nom;

    var $firstname;
    var $lastname;
    var $particulier;
    var $civility_id;
    var $address;
    var $zip;
    var $town;

    /**
     * 0=activity ceased, 1= in activity
     * @var int
     */
    var $status;

    /**
     * Id of department
     */
    var $state_id;
    var $state_code;
    var $state;
    /**
     * @deprecated Use state_code instead
     */
    var $departement_code;
    /**
     * @deprecated Use state instead
     */
    var $departement;

    /**
     * @deprecated Use country instead
     */
    var $pays;
    var $country_id;
    var $country_code;
    var $country;

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
    /**
     * ID of bardode type
     * @var int
     */
    var $barcode_type;
    /**
     * code (loaded by fetch_barcode)
     * @var string
     */
    var $barcode_type_code;
    /**
     * label (loaded by fetch_barcode)
     * @var string
     */
    var $barcode_type_label;
    /**
     * coder (loaded by fetch_barcode)
     * @var string
     */
    var $barcode_type_coder;

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
    var $mode_reglement_id;
    var $cond_reglement_id;
    var $mode_reglement_supplier_id;
    var $cond_reglement_supplier_id;
	var $fk_prospectlevel;
	var $name_bis;

	//Log data

	/**
	 * Date of last update
	 */
	var $date_modification;
	/**
	 * User that made last update
	 */
	var $user_modification;
	/**
	 * Date of creation
	 */
	var $date_creation;
	/**
	 * User that created the thirdparty
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
     * @deprecated Note is split in public and private notes
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
     * Id of sales representative to link (used for thirdparty creation). Not filled by a fetch, because we can have several sales representatives.
     */
    var $commercial_id;
    var $parent;
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

    /**
     * To contains a clone of this when we need to save old properties of object
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

        return 1;
    }


    /**
     *    Create third party in database
     *
     *    @param	User	$user       Object of user that ask creation
     *    @return   int         		>= 0 if OK, < 0 if KO
     */
    function create($user='')
    {
        global $langs,$conf;

		$error=0;

        // Clean parameters
        if (empty($this->status)) $this->status=0;
        $this->name=$this->name?trim($this->name):trim($this->nom);
        if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->name=ucwords($this->name);
        $this->nom=$this->name; // For backward compatibility
        if (empty($this->client))      $this->client=0;
        if (empty($this->fournisseur)) $this->fournisseur=0;
        $this->import_key = trim($this->import_key);

        dol_syslog(get_class($this)."::create ".$this->name);

        // Check parameters
        if (! empty($conf->global->SOCIETE_MAIL_REQUIRED) && ! isValidEMail($this->email))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEMail",$this->email);
            return -1;
        }

        $now=dol_now();

        $this->db->begin();

        // For automatic creation during create action (not used by Dolibarr GUI, can be used by scripts)
        if ($this->code_client == -1)      $this->get_codeclient($this,0);
        if ($this->code_fournisseur == -1) $this->get_codefournisseur($this,1);

        // Check more parameters
        // If error, this->errors[] is filled
        $result = $this->verify();

        if ($result >= 0)
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe (nom, entity, datec, fk_user_creat, canvas, status, ref_int, ref_ext, fk_stcomm, fk_incoterms, location_incoterms ,import_key)";
            $sql.= " VALUES ('".$this->db->escape($this->name)."', ".$conf->entity.", '".$this->db->idate($now)."'";
            $sql.= ", ".(! empty($user->id) ? "'".$user->id."'":"null");
            $sql.= ", ".(! empty($this->canvas) ? "'".$this->canvas."'":"null");
            $sql.= ", ".$this->status;
            $sql.= ", ".(! empty($this->ref_int) ? "'".$this->ref_int."'":"null");
            $sql.= ", ".(! empty($this->ref_ext) ? "'".$this->ref_ext."'":"null");
            $sql.= ", 0";
			$sql.= ", ".(int) $this->fk_incoterms;
			$sql.= ", '".$this->db->escape($this->location_incoterms)."'";
            $sql.= ", ".(! empty($this->import_key) ? "'".$this->import_key."'":"null").")";

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
                else if (!$user->rights->societe->client->voir)
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
                    dol_syslog(get_class($this)."::Create echec update ".$this->error, LOG_ERR);
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
                {

                    $this->error=$langs->trans("ErrorCompanyNameAlreadyExists",$this->name);
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

        return $result;
    }

    /**
     *      Update parameters of third party
     *
     *      @param	int		$id              			id societe
     *      @param  User	$user            			Utilisateur qui demande la mise a jour
     *      @param  int		$call_trigger    			0=non, 1=oui
     *		@param	int		$allowmodcodeclient			Inclut modif code client et code compta
     *		@param	int		$allowmodcodefournisseur	Inclut modif code fournisseur et code compta fournisseur
     *		@param	string	$action						'add' or 'update'
     *		@param	int		$nosyncmember				Do not synchronize info of linked member
     *      @return int  			           			<0 if KO, >=0 if OK
     */
    function update($id, $user='', $call_trigger=1, $allowmodcodeclient=0, $allowmodcodefournisseur=0, $action='update', $nosyncmember=1)
    {
        global $langs,$conf,$hookmanager;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$error=0;

        dol_syslog(get_class($this)."::Update id=".$id." call_trigger=".$call_trigger." allowmodcodeclient=".$allowmodcodeclient." allowmodcodefournisseur=".$allowmodcodefournisseur);

        $now=dol_now();

        // Clean parameters
        $this->id			= $id;
        $this->name			= $this->name?trim($this->name):trim($this->nom);
        $this->nom			= $this->name;	// For backward compatibility
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
        $this->idprof1		= trim($this->idprof1);
        $this->idprof2		= trim($this->idprof2);
        $this->idprof3		= trim($this->idprof3);
        $this->idprof4		= trim($this->idprof4);
        $this->idprof5		= (! empty($this->idprof5)?trim($this->idprof5):'');
        $this->idprof6		= (! empty($this->idprof6)?trim($this->idprof6):'');
        $this->prefix_comm	= trim($this->prefix_comm);

        $this->tva_assuj	= trim($this->tva_assuj);
        $this->tva_intra	= dol_sanitizeFileName($this->tva_intra,'');
        if (empty($this->status)) $this->status = 0;

        // Local taxes
        $this->localtax1_assuj=trim($this->localtax1_assuj);
        $this->localtax2_assuj=trim($this->localtax2_assuj);

        $this->localtax1_value=trim($this->localtax1_value);
        $this->localtax2_value=trim($this->localtax2_value);

        $this->capital=price2num(trim($this->capital),'MT');
        if (empty($this->capital)) $this->capital = 0;

        $this->effectif_id=trim($this->effectif_id);
        $this->forme_juridique_code=trim($this->forme_juridique_code);

        //Gencod
        $this->barcode=trim($this->barcode);

        // For automatic creation
        if ($this->code_client == -1) $this->get_codeclient($this,0);
        if ($this->code_fournisseur == -1) $this->get_codefournisseur($this,1);

        $this->code_compta=trim($this->code_compta);
        $this->code_compta_fournisseur=trim($this->code_compta_fournisseur);

        // Check parameters
        if (! empty($conf->global->SOCIETE_MAIL_REQUIRED) && ! isValidEMail($this->email))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEMail",$this->email);
            return -1;
        }
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
        if ($action != 'add') $result = $this->verify();	// We don't check when update called during a create because verify was already done

        if ($result >= 0)
        {
            dol_syslog(get_class($this)."::update verify ok or not done");

            $sql  = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
            $sql .= "nom = '" . $this->db->escape($this->name) ."'"; // Required
            $sql .= ",ref_ext = " .(! empty($this->ref_ext)?"'".$this->db->escape($this->ref_ext) ."'":"null");
            $sql .= ",address = '" . $this->db->escape($this->address) ."'";

            $sql .= ",zip = ".(! empty($this->zip)?"'".$this->zip."'":"null");
            $sql .= ",town = ".(! empty($this->town)?"'".$this->db->escape($this->town)."'":"null");

            $sql .= ",fk_departement = '" . (! empty($this->state_id)?$this->state_id:'0') ."'";
            $sql .= ",fk_pays = '" . (! empty($this->country_id)?$this->country_id:'0') ."'";

            $sql .= ",phone = ".(! empty($this->phone)?"'".$this->db->escape($this->phone)."'":"null");
            $sql .= ",fax = ".(! empty($this->fax)?"'".$this->db->escape($this->fax)."'":"null");
            $sql .= ",email = ".(! empty($this->email)?"'".$this->db->escape($this->email)."'":"null");
            $sql .= ",skype = ".(! empty($this->skype)?"'".$this->db->escape($this->skype)."'":"null");
            $sql .= ",url = ".(! empty($this->url)?"'".$this->db->escape($this->url)."'":"null");

            $sql .= ",siren   = '". $this->db->escape($this->idprof1) ."'";
            $sql .= ",siret   = '". $this->db->escape($this->idprof2) ."'";
            $sql .= ",ape     = '". $this->db->escape($this->idprof3) ."'";
            $sql .= ",idprof4 = '". $this->db->escape($this->idprof4) ."'";
            $sql .= ",idprof5 = '". $this->db->escape($this->idprof5) ."'";
            $sql .= ",idprof6 = '". $this->db->escape($this->idprof6) ."'";

            $sql .= ",tva_assuj = ".($this->tva_assuj!=''?"'".$this->tva_assuj."'":"null");
            $sql .= ",tva_intra = '" . $this->db->escape($this->tva_intra) ."'";
            $sql .= ",status = " .$this->status;

            // Local taxes
            $sql .= ",localtax1_assuj = ".($this->localtax1_assuj!=''?"'".$this->localtax1_assuj."'":"null");
            $sql .= ",localtax2_assuj = ".($this->localtax2_assuj!=''?"'".$this->localtax2_assuj."'":"null");
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

            $sql .= ",capital = ".$this->capital;

            $sql .= ",prefix_comm = ".(! empty($this->prefix_comm)?"'".$this->db->escape($this->prefix_comm)."'":"null");

            $sql .= ",fk_effectif = ".(! empty($this->effectif_id)?"'".$this->effectif_id."'":"null");

            $sql .= ",fk_typent = ".(! empty($this->typent_id)?"'".$this->typent_id."'":"0");

            $sql .= ",fk_forme_juridique = ".(! empty($this->forme_juridique_code)?"'".$this->forme_juridique_code."'":"null");

            $sql .= ",client = " . (! empty($this->client)?$this->client:0);
            $sql .= ",fournisseur = " . (! empty($this->fournisseur)?$this->fournisseur:0);
            $sql .= ",barcode = ".(! empty($this->barcode)?"'".$this->barcode."'":"null");
            $sql .= ",default_lang = ".(! empty($this->default_lang)?"'".$this->default_lang."'":"null");
            $sql .= ",logo = ".(! empty($this->logo)?"'".$this->logo."'":"null");

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
            $sql .= ", fk_user_modif = ".(! empty($user->id)?"'".$user->id."'":"null");
            $sql .= " WHERE rowid = '" . $id ."'";


            dol_syslog(get_class($this)."::Update", LOG_DEBUG);
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
                // FIXME le hook fait double emploi avec le trigger !!
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
                    $result =  -1;
                }
                else
                {
                    $result =  -2;
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
     *    @param    string	$ref_int       	Internal reference of third party
     *    @param    string	$idprof1		Prof id 1 of third party (Warning, this can return several records)
     *    @param    string	$idprof2		Prof id 2 of third party (Warning, this can return several records)
     *    @param    string	$idprof3		Prof id 3 of third party (Warning, this can return several records)
     *    @param    string	$idprof4		Prof id 4 of third party (Warning, this can return several records)
     *    @return   int						>0 if OK, <0 if KO or if two records found for same ref or idprof, 0 if not found.
     */
    function fetch($rowid, $ref='', $ref_ext='', $ref_int='', $idprof1='',$idprof2='',$idprof3='',$idprof4='')
    {
        global $langs;
        global $conf;

        if (empty($rowid) && empty($ref) && empty($ref_ext) && empty($ref_int)) return -1;

        $sql = 'SELECT s.rowid, s.nom as name, s.entity, s.ref_ext, s.ref_int, s.address, s.datec as date_creation, s.prefix_comm';
        $sql .= ', s.status';
        $sql .= ', s.price_level';
        $sql .= ', s.tms as date_modification';
        $sql .= ', s.phone, s.fax, s.email, s.skype, s.url, s.zip, s.town, s.note_private, s.note_public, s.client, s.fournisseur';
        $sql .= ', s.siren as idprof1, s.siret as idprof2, s.ape as idprof3, s.idprof4, s.idprof5, s.idprof6';
        $sql .= ', s.capital, s.tva_intra';
        $sql .= ', s.fk_typent as typent_id';
        $sql .= ', s.fk_effectif as effectif_id';
        $sql .= ', s.fk_forme_juridique as forme_juridique_code';
        $sql .= ', s.webservices_url, s.webservices_key';
        $sql .= ', s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.parent, s.barcode';
        $sql .= ', s.fk_departement, s.fk_pays as country_id, s.fk_stcomm, s.remise_client, s.mode_reglement, s.cond_reglement, s.tva_assuj';
        $sql .= ', s.mode_reglement_supplier, s.cond_reglement_supplier, s.localtax1_assuj, s.localtax1_value, s.localtax2_assuj, s.localtax2_value, s.fk_prospectlevel, s.default_lang, s.logo';
        $sql .= ', s.outstanding_limit, s.import_key, s.canvas, s.fk_incoterms, s.location_incoterms';
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
        if ($rowid) $sql .= ' WHERE s.rowid = '.$rowid;
        if ($ref)   $sql .= " WHERE s.nom = '".$this->db->escape($ref)."' AND s.entity IN (".getEntity($this->element, 1).")";
        if ($ref_ext) $sql .= " WHERE s.ref_ext = '".$this->db->escape($ref_ext)."' AND s.entity IN (".getEntity($this->element, 1).")";
        if ($ref_int) $sql .= " WHERE s.ref_int = '".$this->db->escape($ref_int)."' AND s.entity IN (".getEntity($this->element, 1).")";
        if ($idprof1) $sql .= " WHERE s.siren = '".$this->db->escape($idprof1)."' AND s.entity IN (".getEntity($this->element, 1).")";
        if ($idprof2) $sql .= " WHERE s.siret = '".$this->db->escape($idprof2)."' AND s.entity IN (".getEntity($this->element, 1).")";
        if ($idprof3) $sql .= " WHERE s.ape = '".$this->db->escape($idprof3)."' AND s.entity IN (".getEntity($this->element, 1).")";
        if ($idprof4) $sql .= " WHERE s.idprof4 = '".$this->db->escape($idprof4)."' AND s.entity IN (".getEntity($this->element, 1).")";

        $resql=$this->db->query($sql);
        dol_syslog(get_class($this)."::fetch ".$sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            if ($num > 1)
            {
                $this->error='Fetch several records found for ref='.$ref;
                dol_syslog($this->error, LOG_ERR);
                $result = -2;
            }
            if ($num)
            {
                $obj = $this->db->fetch_object($resql);

                $this->id           = $obj->rowid;
                $this->entity       = $obj->entity;
                $this->canvas		= $obj->canvas;

                $this->ref          = $obj->rowid;
                $this->name 		= $obj->name;
                $this->nom          = $obj->name;		// deprecated
                $this->ref_ext      = $obj->ref_ext;
                $this->ref_int      = $obj->ref_int;

                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->date_modification = $this->db->jdate($obj->date_modification);

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
                $this->mode_reglement_id 	= $obj->mode_reglement;
                $this->cond_reglement_id 	= $obj->cond_reglement;
                $this->mode_reglement_supplier_id 	= $obj->mode_reglement_supplier;
                $this->cond_reglement_supplier_id 	= $obj->cond_reglement_supplier;

                $this->client      = $obj->client;
                $this->fournisseur = $obj->fournisseur;

                $this->note = $obj->note_private; // TODO Deprecated for backward comtability
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
                $this->default_lang = $obj->default_lang;
                $this->logo = $obj->logo;

                $this->webservices_url = $obj->webservices_url;
                $this->webservices_key = $obj->webservices_key;

                $this->outstanding_limit		= $obj->outstanding_limit;

                // multiprix
                $this->price_level = $obj->price_level;

                $this->import_key = $obj->import_key;

				//Incoterms
				$this->fk_incoterms = $obj->fk_incoterms;
				$this->location_incoterms = $obj->location_incoterms;
				$this->libelle_incoterms = $obj->libelle_incoterms;

                $result = 1;

                // Retreive all extrafield for thirdparty
                // fetch optionals attributes and labels
                require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
                $extrafields=new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
               	$this->fetch_optionals($this->id,$extralabels);
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
    	$sql.= " WHERE entity IN (".getEntity('category',1).")";
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
     *    @param	int		$id     Id of third party to delete
     *    @return	int				<0 if KO, 0 if nothing done, >0 if OK
     */
    function delete($id)
    {
        global $user, $langs, $conf;

        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

        $entity=isset($this->entity)?$this->entity:$conf->entity;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        $error = 0;

        // Test if child exists
        $objectisused = $this->isObjectUsed($id);
		if (empty($objectisused))
		{
            $this->db->begin();

            // Call trigger
            $result=$this->call_trigger('COMPANY_DELETE',$user);
            if ($result < 0) $error++;
            // End call triggers

			if (! $error)
			{
	            require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
	            $static_cat = new Categorie($this->db);
	            $toute_categs = array();

	            // Fill $toute_categs array with an array of (type => array of ("Categorie" instance))
	            if ($this->client || $this->prospect)
	            {
	                $toute_categs ['societe'] = $static_cat->containing($this->id,2);
	            }
	            if ($this->fournisseur)
	            {
	                $toute_categs ['fournisseur'] = $static_cat->containing($this->id,1);
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

            // Remove contacts
            if (! $error)
            {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
                $sql.= " WHERE fk_soc = " . $id;
                dol_syslog(get_class($this)."::delete", LOG_DEBUG);
                if (! $this->db->query($sql))
                {
                    $error++;
                    $this->error .= $this->db->lasterror();
                }
            }

            // Update link in member table
            if (! $error)
            {
                $sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
                $sql.= " SET fk_soc = NULL WHERE fk_soc = " . $id;
                dol_syslog(get_class($this)."::delete", LOG_DEBUG);
                if (! $this->db->query($sql))
                {
                    $error++;
                    $this->error .= $this->db->lasterror();
                    dol_syslog(get_class($this)."::delete erreur -1 ".$this->error, LOG_ERR);
                }
            }

            // Remove ban
            if (! $error)
            {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_rib";
                $sql.= " WHERE fk_soc = " . $id;
                dol_syslog(get_class($this)."::Delete", LOG_DEBUG);
                if (! $this->db->query($sql))
                {
                    $error++;
                    $this->error = $this->db->lasterror();
                }
            }

            // Remove associated users
            if (! $error)
            {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."societe_commerciaux";
                $sql.= " WHERE fk_soc = " . $id;
                dol_syslog(get_class($this)."::Delete", LOG_DEBUG);
                if (! $this->db->query($sql))
                {
                    $error++;
                    $this->error = $this->db->lasterror();
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
                dol_syslog(get_class($this)."::delete", LOG_DEBUG);
                if (! $this->db->query($sql))
                {
                    $error++;
                    $this->error = $this->db->lasterror();
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
        global $langs;

        // Nettoyage parametres
        $note=trim($note);
        if (! $note)
        {
            $this->error=$langs->trans("ErrorFieldRequired",$langs->trans("Note"));
            return -2;
        }

        dol_syslog(get_class($this)."::set_remise_client ".$remise.", ".$note.", ".$user->id);

        if ($this->id)
        {
            $this->db->begin();

            $now=dol_now();

            // Positionne remise courante
            $sql = "UPDATE ".MAIN_DB_PREFIX."societe ";
            $sql.= " SET remise_client = '".$remise."'";
            $sql.= " WHERE rowid = " . $this->id .";";
            $resql=$this->db->query($sql);
            if (! $resql)
            {
                $this->db->rollback();
                $this->error=$this->db->error();
                return -1;
            }

            // Ecrit trace dans historique des remises
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."societe_remise";
            $sql.= " (datec, fk_soc, remise_client, note, fk_user_author)";
            $sql.= " VALUES ('".$this->db->idate($now)."', ".$this->id.", '".$remise."',";
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
     *    	@param	float	$remise     Amount of discount
     *    	@param  User	$user       User adding discount
     *    	@param  string	$desc		Reason of discount
     *      @param  float	$tva_tx     VAT rate
     *		@return	int					<0 if KO, id of discount record if OK
     */
    function set_remise_except($remise, User $user, $desc, $tva_tx=0)
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
     *	@param	User	$user		Filtre sur un user auteur des remises
     * 	@param	string	$filter		Filtre autre
     * 	@param	string	$maxvalue	Filter on max value for discount
     *	@return	int					<0 if KO, Credit note amount otherwise
     */
    function getAvailableDiscounts($user='',$filter='',$maxvalue=0)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/class/discount.class.php';

        $discountstatic=new DiscountAbsolute($this->db);
        $result=$discountstatic->getAvailableDiscounts($this,$user,$filter,$maxvalue);
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
     *  @return array       		Array of sales representatives of third party
     */
    function getSalesRepresentatives(User $user)
    {
        global $conf;

        $reparray=array();

        $sql = "SELECT u.rowid, u.lastname, u.firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."user as u";
        $sql.= " WHERE u.rowid = sc.fk_user AND sc.fk_soc =".$this->id;
        $sql.= " AND entity in (0, ".$conf->entity.")";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i=0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $reparray[$i]['id']=$obj->rowid;
                $reparray[$i]['lastname']=$obj->lastname;
                $reparray[$i]['firstname']=$obj->firstname;
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
            $sql .= " SET price_level = '".$price_level."'";
            $sql .= " WHERE rowid = " . $this->id;

            if (! $this->db->query($sql))
            {
                dol_print_error($this->db);
                return -1;
            }

            $sql  = "INSERT INTO ".MAIN_DB_PREFIX."societe_prices";
            $sql .= " (datec, fk_soc, price_level, fk_user_author)";
            $sql .= " VALUES ('".$this->db->idate($now)."',".$this->id.",'".$price_level."',".$user->id.")";

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
     *		@param	int		$withpicto		Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
     *		@param	string	$option			Target of link ('', 'customer', 'prospect', 'supplier')
     *		@param	int		$maxlen			Max length of text
     *      @param	string	$notooltip		1=Disable tooltip
     *		@return	string					String with URL
     */
    function getNomUrl($withpicto=0,$option='',$maxlen=0,$notooltip=0)
    {
        global $conf,$langs;

        $name=$this->name?$this->name:$this->nom;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;

		if ($conf->global->SOCIETE_ADD_REF_IN_LIST && (!empty($withpicto))) {
			if (($this->client) && (! empty ( $this->code_client ))) {
				$code = $this->code_client . ' - ';
			}
			if (($this->fournisseur) && (! empty ( $this->code_fournisseur ))) {
				$code .= $this->code_fournisseur . ' - ';
			}
			$name =$code.' '.$name;
		}

        $result=''; $label='';
        $link=''; $linkend='';

        $label.= '<div width="100%">';

        if ($option == 'customer' || $option == 'compta')
        {
           $label.= '<u>' . $langs->trans("ShowCustomer") . '</u>';
           $link = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
        }
        else if ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
        {
            $label.= '<u>' . $langs->trans("ShowProspect") . '</u>';
            $link = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$this->id;
        }
        else if ($option == 'supplier')
        {
            $label.= '<u>' . $langs->trans("ShowSupplier") . '</u>';
            $link = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$this->id;
        }
        else if ($option == 'category')
        {
            $label.= '<u>' . $langs->trans("ShowCategory") . '</u>';
        	$link = '<a href="'.DOL_URL_ROOT.'/categories/categorie.php?id='.$this->id.'&type=2';
        }
        else if ($option == 'category_supplier')
        {
            $label.= '<u>' . $langs->trans("ShowCategorySupplier") . '</u>';
        	$link = '<a href="'.DOL_URL_ROOT.'/categories/categorie.php?id='.$this->id.'&type=1';
        }

        // By default
        if (empty($link))
        {
            $label.= '<u>' . $langs->trans("ShowCompany") . '</u>';
            $link = '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$this->id;
        }

        if (! empty($this->name))
            $label.= '<br><b>' . $langs->trans('Name') . ':</b> '. $this->name;
        if (! empty($this->code_client))
            $label.= '<br><b>' . $langs->trans('CustomerCode') . ':</b> '. $this->code_client;
        if (! empty($this->code_fournisseur))
            $label.= '<br><b>' . $langs->trans('SupplierCode') . ':</b> '. $this->code_fournisseur;

        if (! empty($this->logo))
        {
            $label.= '</br><div class="photointooltip">';
            //if (! is_object($form)) $form = new Form($db);
            $label.= Form::showphoto('societe', $this, 80);
            $label.= '</div><div style="clear: both;"></div>';
        }
        $label.= '</div>';

        // Add type of canvas
        $link.=(!empty($this->canvas)?'&canvas='.$this->canvas:'').'"';
        $link.=($notooltip?'':' title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip"');
        $link.='>';
        $linkend='</a>';

        if ($withpicto) $result.=($link.img_object(($notooltip?'':$label), 'company', ($notooltip?'':'class="classfortooltip"')).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$link.($maxlen?dol_trunc($name,$maxlen):$name).$linkend;

        return $result;
    }

    /**
     *    Return label of status (activity, closed)
     *
     *    @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
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
     *  @param	int		$mode           0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
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
            if ($statut==0) return img_picto($langs->trans("ActivityCeased"),'statut5').' '.$langs->trans("ActivityCeased");
            if ($statut==1) return img_picto($langs->trans("InActivity"),'statut4').' '.$langs->trans("InActivity");
        }
        if ($mode == 3)
        {
            if ($statut==0) return img_picto($langs->trans("ActivityCeased"),'statut5');
            if ($statut==1) return img_picto($langs->trans("InActivity"),'statut4');
        }
        if ($mode == 4)
        {
            if ($statut==0) return img_picto($langs->trans("ActivityCeased"),'statut5').' '.$langs->trans("ActivityCeased");
            if ($statut==1) return img_picto($langs->trans("InActivity"),'statut4').' '.$langs->trans("InActivity");
        }
        if ($mode == 5)
        {
            if ($statut==0) return $langs->trans("ActivityCeased").' '.img_picto($langs->trans("ActivityCeased"),'statut5');
            if ($statut==1) return $langs->trans("InActivity").' '.img_picto($langs->trans("InActivity"),'statut4');
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

        $contact_emails = $this->contact_property_array('email');
        if ($this->email && $addthirdparty)
        {
            if (empty($this->name)) $this->name=$this->nom;
            // TODO: Tester si email non deja present dans tableau contact
            $contact_emails['thirdparty']=$langs->trans("ThirdParty").': '.dol_trunc($this->name,16)." &lt;".$this->email."&gt;";
        }
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
            $contact_phone['thirdparty']=$langs->trans("ThirdParty").': '.dol_trunc($this->name,16)." &lt;".$this->phone."&gt;";
        }
        return $contact_phone;
    }

    /**
     *  Return list of contacts emails or mobile existing for third party
     *
     *  @param	string	$mode       		'email' or 'mobile'
	 * 	@param	int		$hidedisabled		1=Hide contact if disabled
     *  @return array       				Array of contacts emails or mobile array(id=>'Name <email>')
     */
    function contact_property_array($mode='email', $hidedisabled=0)
    {
    	global $langs;

        $contact_property = array();


        $sql = "SELECT rowid, email, statut, phone_mobile, lastname, poste, firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."socpeople";
        $sql.= " WHERE fk_soc = '".$this->id."'";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);
            if ($nump)
            {
            	$sepa="("; $sepb=")";
            	if ($mode == 'email')
            	{
            		$sepa="&lt;"; $sepb="&gt;";
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
                    		if ($mode == 'email') $property=$langs->trans("NoEMail");
                    		else if ($mode == 'mobile') $property=$langs->trans("NoMobilePhone");
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

        $sql = "SELECT rowid, lastname, firstname FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '".$this->id."'";
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

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople WHERE fk_soc = '".$this->id."'";
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
     *  @return string  			email of contact
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
     *    Return bank number property of thirdparty
     *
     *    @return	string		Bank number
     */
    function display_rib()
    {
        require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
        $bac = new CompanyBankAccount($this->db);
        $bac->fetch(0,$this->id);
        return $bac->getRibLabel(true);
    }


    /**
     * Return Array of RIB
     *
     * @return     array|int        0 if KO, Array of CompanyBanckAccount if OK
     */
    function get_all_rib()
    {
        require_once DOL_DOCUMENT_ROOT . '/societe/class/companybankaccount.class.php';
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe_rib WHERE fk_soc = ".$this->id;
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
        	$file='';
            $dirsociete=array_merge(array('/core/modules/societe/'), $conf->modules_parts['societe']);
            foreach ($dirsociete as $dirroot)
            {
            	if (file_exists(DOL_DOCUMENT_ROOT.'/'.$dirroot.$conf->global->SOCIETE_CODECOMPTA_ADDON.".php"))
            	{
            		$file=$dirroot.$conf->global->SOCIETE_CODECOMPTA_ADDON.".php";
            		break;
            	}
            }

            if (! empty($file))
            {
            	dol_include_once($file);

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
     *  @param	int		$idprof		1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
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
        	default:
        		$ret=false;
        }

        return $ret;
    }

	/**
     *    Verify if a profid exists into database for others thirds
     *
     *    @param	int		$idprof		1,2,3,4 (Example: 1=siren,2=siret,3=naf,4=rcs/rm)
     *    @param	string	$value		Value of profid
     *    @param	int		$socid		Id of thirdparty if update
     *    @return   boolean				true if exists, false if not
     */
    function id_prof_exists($idprof,$value,$socid=0)
    {
     	switch($idprof)
        {
        	case 1:
        		$field="siren";
        		break;
        	case 2:
        		$field="siret";
        		break;
        	case 3:
        		$field="ape";
        		break;
        	case 4:
        		$field="idprof4";
        		break;
        }

         //Verify duplicate entries
        $sql  = "SELECT COUNT(*) as idprof FROM ".MAIN_DB_PREFIX."societe WHERE ".$field." = '".$value."'";
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
     *  TODO not in business class
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

            if (dol_strlen($chaine) != 9) return -1;

            $sum = 0;

            for ($i = 0 ; $i < 10 ; $i = $i+2)
            {
                $sum = $sum + substr($this->idprof1, (8 - $i), 1);
            }

            for ($i = 1 ; $i < 9 ; $i = $i+2)
            {
                $ps = 2 * substr($this->idprof1, (8 - $i), 1);

                if ($ps > 9)
                {
                    $ps = substr($ps, 0,1) + substr($ps, 1, 1);
                }
                $sum = $sum + $ps;
            }

            if (substr($sum, -1) != 0) return -1;
        }

        // Verifie SIRET si pays FR
        if ($idprof == 2 && $soc->country_code == 'FR')
        {
            $chaine=trim($this->idprof2);
            $chaine=preg_replace('/(\s)/','',$chaine);

            if (dol_strlen($chaine) != 14) return -1;
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

        return $ok;
    }

    /**
     *   Renvoi url de verification d'un identifiant professionnal
     *
     *   @param		int		$idprof         1,2,3,4 (Exemple: 1=siren,2=siret,3=naf,4=rcs/rm)
     *   @param 	Societe	$soc            Objet societe
     *   @return	string          		url ou chaine vide si aucune url connue
     *   TODO not in business class
     */
    function id_prof_url($idprof,$soc)
    {
        global $conf,$langs;

        if (! empty($conf->global->MAIN_DISABLEPROFIDRULES)) return '';

        $url='';

        if ($idprof == 1 && $soc->country_code == 'FR') $url='http://www.societe.com/cgi-bin/recherche?rncs='.$soc->idprof1;
        if ($idprof == 1 && ($soc->country_code == 'GB' || $soc->country_code == 'UK')) $url='http://www.companieshouse.gov.uk/WebCHeck/findinfolink/';
        if ($idprof == 1 && $soc->country_code == 'ES') $url='http://www.e-informa.es/servlet/app/portal/ENTP/screen/SProducto/prod/ETIQUETA_EMPRESA/nif/'.$soc->idprof1;
        if ($idprof == 1 && $soc->country_code == 'IN') $url='http://www.tinxsys.com/TinxsysInternetWeb/dealerControllerServlet?tinNumber='.$soc->idprof1.';&searchBy=TIN&backPage=searchByTin_Inter.jsp';

        if ($url) return '<a target="_blank" href="'.$url.'">['.$langs->trans("Check").']</a>';
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
        else if (! empty($this->typent_code) && in_array($this->typent_code,array('TE_SMALL','TE_MEDIUM','TE_LARGE'))) $isacompany=1;

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
        $sql.= " WHERE type = 1";

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
     *  Charge la liste des categories fournisseurs
     *
     *	@param	int		$categorie_id		Id of category
     *  @return int      					0 if success, <> 0 if error
     */
    function AddFournisseurInCategory($categorie_id)
    {
        if ($categorie_id > 0)
        {
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."categorie_fournisseur (fk_categorie, fk_societe) ";
            $sql.= " VALUES ('".$categorie_id."','".$this->id."');";

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
     * 	@param	string	$socname	Name of third party to force
     *  @return int					<0 if KO, id of created account if OK
     */
    function create_from_member(Adherent $member,$socname='')
    {
        global $user,$langs;

        $name = $socname?$socname:$member->societe;
        if (empty($name)) $name=$member->getFullName($langs);

        // Positionne parametres
        $this->nom=$name;				// TODO obsolete
        $this->name=$name;
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

            dol_syslog(get_class($this)."::create_from_member", LOG_DEBUG);
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

        /* Disabled: we don't want any SQL request into method setMySoc. This method set object from env only.
        If we need label, label must be loaded by output that need it from id (label depends on output language)
        require_once DOL_DOCUMENT_ROOT .'/core/lib/company.lib.php';
        if (!empty($conf->global->MAIN_INFO_SOCIETE_STATE)) {
            $this->state_id= $conf->global->MAIN_INFO_SOCIETE_STATE;
            $this->state = getState($this->state_id);
        }
		*/

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
     *  Check if we must use localtax feature or not according to country (country of $mysocin most cases).
     *
     *	@param		int		$localTaxNum	To get info for only localtax1 or localtax2
     *  @return		boolean					true or false
     */
    function useLocalTax($localTaxNum=0)
    {
    	$sql  = "SELECT t.localtax1, t.localtax2";
    	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
    	$sql .= " WHERE t.fk_pays = c.rowid AND c.code = '".$this->country_code."'";
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
     *  Check if we must use revenue stamps feature or not according to country (country of $mysocin most cases).
     *
     *  @return		boolean			true or false
     */
    function useRevenueStamp()
    {
		$sql  = "SELECT COUNT(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_revenuestamp as r, ".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE r.fk_pays = c.rowid AND c.code = '".$this->country_code."'";
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
	 */
	function set_prospect_level(User $user)
	{
		if ($this->id)
		{
			$this->db->begin();

			// Positionne remise courante
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
			$sql.= " fk_prospectlevel='".$this->fk_prospectlevel."'";
			$sql.= ",fk_user_modif='".$user->id."'";
			$sql.= " WHERE rowid = ".$this->id;
			dol_syslog(get_class($this)."::set_prospect_level", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -1;
			}

			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Return status of prospect
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long
	 *  @return string        		Libelle
	 */
	function getLibProspCommStatut($mode=0)
	{
		return $this->LibProspCommStatut($this->stcomm_id,$mode);
	}

	/**
	 *  Return label of a given status
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @return string        			Libelle du statut
	 */
	function LibProspCommStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('customers');

		if ($mode == 2)
		{
			if ($statut == -1) return img_action($langs->trans("StatusProspect-1"),-1).' '.$langs->trans("StatusProspect-1");
			if ($statut ==  0) return img_action($langs->trans("StatusProspect0"), 0).' '.$langs->trans("StatusProspect0");
			if ($statut ==  1) return img_action($langs->trans("StatusProspect1"), 1).' '.$langs->trans("StatusProspect1");
			if ($statut ==  2) return img_action($langs->trans("StatusProspect2"), 2).' '.$langs->trans("StatusProspect2");
			if ($statut ==  3) return img_action($langs->trans("StatusProspect3"), 3).' '.$langs->trans("StatusProspect3");
		}
		if ($mode == 3)
		{
			if ($statut == -1) return img_action($langs->trans("StatusProspect-1"),-1);
			if ($statut ==  0) return img_action($langs->trans("StatusProspect0"), 0);
			if ($statut ==  1) return img_action($langs->trans("StatusProspect1"), 1);
			if ($statut ==  2) return img_action($langs->trans("StatusProspect2"), 2);
			if ($statut ==  3) return img_action($langs->trans("StatusProspect3"), 3);
		}
		if ($mode == 4)
		{
			if ($statut == -1) return img_action($langs->trans("StatusProspect-1"),-1).' '.$langs->trans("StatusProspect-1");
			if ($statut ==  0) return img_action($langs->trans("StatusProspect0"), 0).' '.$langs->trans("StatusProspect0");
			if ($statut ==  1) return img_action($langs->trans("StatusProspect1"), 1).' '.$langs->trans("StatusProspect1");
			if ($statut ==  2) return img_action($langs->trans("StatusProspect2"), 2).' '.$langs->trans("StatusProspect2");
			if ($statut ==  3) return img_action($langs->trans("StatusProspect3"), 3).' '.$langs->trans("StatusProspect3");
		}

		return "Error, mode/status not found";
	}

	/**
	 *  Set commnunication level
	 *
	 *  @param  User	$user		User making change
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function set_commnucation_level($user)
	{
		if ($this->id)
		{
			$this->db->begin();

			// Positionne remise courante
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
			$sql.= " fk_stcomm='".$this->stcomm_id."'";
			$sql.= ",fk_user_modif='".$user->id."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::set_commnucation_level", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$this->db->rollback();
				$this->error=$this->db->error();
				return -1;
			}

			$this->db->commit();
			return 1;
		}
	}

	/**
	 *  Set outstanding value
	 *
	 *  @param  User	$user		User making change
	 *	@return	int					<0 if KO, >0 if OK
	 */
	function set_OutstandingBill (User $user)
	{
		if ($this->id)
		{
			$this->db->begin();

			// Clean parameters
			$outstanding = price2num($this->outstanding_limit);

			// Set outstanding amount
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET ";
			$sql.= " outstanding_limit= '".($outstanding!=''?$outstanding:'null')."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::set_outstanding", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				$this->db->rollback();
				$this->error=$this->db->lasterror();
				return -1;
			}
		}
	}

    /**
     *  Return amount of bill not paid
     *
     *  @return		int				Amount in debt for thirdparty
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
		$sql  = "SELECT sum(total) as amount FROM ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE fk_soc = ". $this->id;
		$sql .= " AND paye = 0";
		$sql .= " AND fk_statut <> 0";	// Not a draft
		//$sql .= " AND (fk_statut <> 3 OR close_code <> 'abandon')";		// Not abandonned for undefined reason
		$sql .= " AND fk_statut <> 3";		// Not abandonned

		dol_syslog("get_OutstandingBill", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
   			return ($obj->amount);
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

}
