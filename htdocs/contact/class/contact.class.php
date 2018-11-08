<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004-2013 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@capnetworks.com>
 * Copyright (C) 2007      Franky Van Liedekerke       <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2013      Florian Henry		  	       <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Alexandre Spangaro 	       <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2013      Juanjo Menent	 	       <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García               <marcosgdf@gmail.com>
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
 *	\file       htdocs/contact/class/contact.class.php
 *	\ingroup    societe
 *	\brief      File of contacts class
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';


/**
 *	Class to manage contact/addresses
 */
class Contact extends CommonObject
{
	public $element='contact';
	public $table_element='socpeople';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	public $picto = 'contact';


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields=array(
		'rowid'         =>array('type'=>'integer',      'label'=>'TechnicalID',      'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'index'=>1, 'position'=>1, 'comment'=>'Id'),
		'lastname'      =>array('type'=>'varchar(128)', 'label'=>'Name',             'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1),
		'firstname'     =>array('type'=>'varchar(128)', 'label'=>'Firstname',        'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>11, 'searchall'=>1),
		'entity'        =>array('type'=>'integer',      'label'=>'Entity',           'enabled'=>1, 'visible'=>0,  'default'=>1, 'notnull'=>1,  'index'=>1, 'position'=>20),
		'note_public'   =>array('type'=>'text',			'label'=>'NotePublic',		 'enabled'=>1, 'visible'=>0,  'position'=>60),
		'note_private'  =>array('type'=>'text',			'label'=>'NotePrivate',		 'enabled'=>1, 'visible'=>0,  'position'=>61),
		'datec'         =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>500),
		'tms'           =>array('type'=>'timestamp',    'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>501),
		//'date_valid'    =>array('type'=>'datetime',     'label'=>'DateCreation',     'enabled'=>1, 'visible'=>-2, 'position'=>502),
		'fk_user_creat' =>array('type'=>'integer',      'label'=>'UserAuthor',       'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'position'=>510),
		'fk_user_modif' =>array('type'=>'integer',      'label'=>'UserModif',        'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'position'=>511),
		//'fk_user_valid' =>array('type'=>'integer',      'label'=>'UserValidation',        'enabled'=>1, 'visible'=>-1, 'position'=>512),
		'import_key'    =>array('type'=>'varchar(14)',  'label'=>'ImportId',         'enabled'=>1, 'visible'=>-2, 'notnull'=>-1, 'index'=>1,  'position'=>1000),
	);

	public $civility_id;      // In fact we store civility_code
	public $civility_code;
	public $address;
	public $zip;
	public $town;

	/**
	 * @deprecated
	 * @see state_id
	 */
	public $fk_departement;
	/**
	 * @deprecated
	 * @see state_code
	 */
	public $departement_code;
	/**
	 * @deprecated
	 * @see state
	 */
	public $departement;
	public $state_id;	        	// Id of department
	public $state_code;		    // Code of department
	public $state;			        // Label of department

    	public $poste;                 // Position

	public $socid;					// fk_soc
	public $statut;				// 0=inactif, 1=actif

	public $code;
	public $email;
	public $skype;
	public $photo;
	public $jabberid;
	public $phone_pro;
	public $phone_perso;
	public $phone_mobile;
	public $fax;

	public $priv;

	public $birthday;
	public $default_lang;
	public $no_email;				// 1=Don't send e-mail to this contact, 0=do

	public $ref_facturation;       // Reference number of invoice for which it is contact
	public $ref_contrat;           // Nb de reference contrat pour lequel il est contact
	public $ref_commande;          // Nb de reference commande pour lequel il est contact
	public $ref_propal;            // Nb de reference propal pour lequel il est contact

	public $user_id;
	public $user_login;

	// END MODULEBUILDER PROPERTIES


	public $oldcopy;				// To contains a clone of this when we need to save old properties of object





	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->statut = 1;	// By default, status is enabled
	}

	/**
	 *  Load indicators into this->nb for board
	 *
	 *  @return     int         <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
		global $user;

		$this->nb=array();
		$clause = "WHERE";

		$sql = "SELECT count(sp.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as sp";
		if (!$user->rights->societe->client->voir && !$user->societe_id)
		{
		    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
		    $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			$sql.= " WHERE sp.fk_soc = s.rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
			$clause = "AND";
		}
		$sql.= ' '.$clause.' sp.entity IN ('.getEntity($this->element).')';
		$sql.= " AND (sp.priv='0' OR (sp.priv='1' AND sp.fk_user_creat=".$user->id."))";
        if ($user->societe_id > 0) $sql.=" AND sp.fk_soc = ".$user->societe_id;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["contacts"]=$obj->nb;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Add a contact into database
	 *
	 *  @param      User	$user       Object user that create
	 *  @return     int      			<0 if KO, >0 if OK
	 */
	function create($user)
	{
		global $conf, $langs;

		$error=0;
		$now=dol_now();

		$this->db->begin();

		// Clean parameters
		$this->lastname=$this->lastname?trim($this->lastname):trim($this->name);
        $this->firstname=trim($this->firstname);
        if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->lastname=ucwords($this->lastname);
        if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->firstname=ucwords($this->firstname);
        if (empty($this->socid)) $this->socid = 0;
		if (empty($this->priv)) $this->priv = 0;
		if (empty($this->statut)) $this->statut = 0; // This is to convert '' into '0' to avoid bad sql request

		$this->entity = ((isset($this->entity) && is_numeric($this->entity))?$this->entity:$conf->entity);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."socpeople (";
		$sql.= " datec";
		$sql.= ", fk_soc";
        $sql.= ", lastname";
        $sql.= ", firstname";
        $sql.= ", fk_user_creat";
		$sql.= ", priv";
		$sql.= ", statut";
		$sql.= ", canvas";
		$sql.= ", entity";
		$sql.= ", ref_ext";
		$sql.= ", import_key";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->idate($now)."',";
		if ($this->socid > 0) $sql.= " ".$this->db->escape($this->socid).",";
		else $sql.= "null,";
		$sql.= "'".$this->db->escape($this->lastname)."',";
        $sql.= "'".$this->db->escape($this->firstname)."',";
        $sql.= " ".($user->id > 0 ? "'".$this->db->escape($user->id)."'":"null").",";
		$sql.= " ".$this->db->escape($this->priv).",";
		$sql.= " ".$this->db->escape($this->statut).",";
        $sql.= " ".(! empty($this->canvas)?"'".$this->db->escape($this->canvas)."'":"null").",";
        $sql.= " ".$this->db->escape($this->entity).",";
        $sql.= "'".$this->db->escape($this->ref_ext)."',";
        $sql.= " ".(! empty($this->import_key)?"'".$this->db->escape($this->import_key)."'":"null");
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."socpeople");

			if (! $error)
			{
                $result=$this->update($this->id, $user, 1, 'add');
                if ($result < 0)
                {
                    $error++;
				    $this->error=$this->db->lasterror();
                }
			}

			if (! $error)
            {
                $result=$this->update_perso($this->id, $user, 1);   // TODO Remove function update_perso, should be same than update
                if ($result < 0)
                {
                    $error++;
                    $this->error=$this->db->lasterror();
                }
            }

			if (! $error)
            {
                // Call trigger
                $result=$this->call_trigger('CONTACT_CREATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
            }

            if (! $error)
            {
                $this->db->commit();
                return $this->id;
            }
            else
            {
                $this->db->rollback();
                dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
                return -2;
            }
		}
		else
		{
			$this->error=$this->db->lasterror();

			$this->db->rollback();
			dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *      Update informations into database
	 *
	 *      @param      int		$id          	Id of contact/address to update
	 *      @param      User	$user        	Objet user making change
	 *      @param      int		$notrigger	    0=no, 1=yes
	 *      @param		string	$action			Current action for hookmanager
	 *      @param		int		$nosyncuser		No sync linked user (external users and contacts are linked)
	 *      @return     int      			   	<0 if KO, >0 if OK
	 */
	function update($id, $user=null, $notrigger=0, $action='update', $nosyncuser=0)
	{
		global $conf, $langs, $hookmanager;

		$error=0;

		$this->id = $id;

		$this->entity = ((isset($this->entity) && is_numeric($this->entity))?$this->entity:$conf->entity);

		// Clean parameters
		$this->lastname=trim($this->lastname)?trim($this->lastname):trim($this->lastname);
		$this->firstname=trim($this->firstname);
		$this->email=trim($this->email);
		$this->phone_pro=trim($this->phone_pro);
		$this->phone_perso=trim($this->phone_perso);
		$this->phone_mobile=trim($this->phone_mobile);
		$this->jabberid=trim($this->jabberid);
		$this->skype=trim($this->skype);
		$this->photo=trim($this->photo);
		$this->fax=trim($this->fax);
		$this->zip=(empty($this->zip)?'':$this->zip);
		$this->town=(empty($this->town)?'':$this->town);
		$this->country_id=($this->country_id > 0?$this->country_id:$this->country_id);
		$this->state_id=($this->state_id > 0?$this->state_id:$this->fk_departement);
		if (empty($this->statut)) $this->statut = 0;

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET ";
		if ($this->socid > 0) $sql .= " fk_soc='".$this->db->escape($this->socid)."',";
		else if ($this->socid == -1) $sql .= " fk_soc=null,";
		$sql .= "  civility='".$this->db->escape($this->civility_id)."'";
		$sql .= ", lastname='".$this->db->escape($this->lastname)."'";
		$sql .= ", firstname='".$this->db->escape($this->firstname)."'";
		$sql .= ", address='".$this->db->escape($this->address)."'";
		$sql .= ", zip='".$this->db->escape($this->zip)."'";
		$sql .= ", town='".$this->db->escape($this->town)."'";
		$sql .= ", fk_pays=".($this->country_id>0?$this->country_id:'NULL');
		$sql .= ", fk_departement=".($this->state_id>0?$this->state_id:'NULL');
		$sql .= ", poste='".$this->db->escape($this->poste)."'";
		$sql .= ", fax='".$this->db->escape($this->fax)."'";
		$sql .= ", email='".$this->db->escape($this->email)."'";
		$sql .= ", skype='".$this->db->escape($this->skype)."'";
		$sql .= ", photo='".$this->db->escape($this->photo)."'";
		$sql .= ", birthday=".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		$sql .= ", note_private = ".(isset($this->note_private)?"'".$this->db->escape($this->note_private)."'":"null");
		$sql .= ", note_public = ".(isset($this->note_public)?"'".$this->db->escape($this->note_public)."'":"null");
		$sql .= ", phone = ".(isset($this->phone_pro)?"'".$this->db->escape($this->phone_pro)."'":"null");
		$sql .= ", phone_perso = ".(isset($this->phone_perso)?"'".$this->db->escape($this->phone_perso)."'":"null");
		$sql .= ", phone_mobile = ".(isset($this->phone_mobile)?"'".$this->db->escape($this->phone_mobile)."'":"null");
		$sql .= ", jabberid = ".(isset($this->jabberid)?"'".$this->db->escape($this->jabberid)."'":"null");
		$sql .= ", priv = '".$this->db->escape($this->priv)."'";
		$sql .= ", statut = ".$this->db->escape($this->statut);
		$sql .= ", fk_user_modif=".($user->id > 0 ? "'".$this->db->escape($user->id)."'":"NULL");
		$sql .= ", default_lang=".($this->default_lang?"'".$this->db->escape($this->default_lang)."'":"NULL");
		$sql .= ", no_email=".($this->no_email?"'".$this->db->escape($this->no_email)."'":"0");
		$sql .= ", entity = " . $this->db->escape($this->entity);
		$sql .= " WHERE rowid=".$this->db->escape($id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
		    unset($this->country_code);
		    unset($this->country);
		    unset($this->state_code);
		    unset($this->state);

		    $action='update';

		    // Actions on extra fields
		    if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))
		    {
		    	$result=$this->insertExtraFields();
		    	if ($result < 0)
		    	{
		    		$error++;
		    	}
		    }

			if (! $error && $this->user_id > 0)
			{
				$tmpobj = new User($this->db);
				$tmpobj->fetch($this->user_id);
				$usermustbemodified = 0;
				if ($tmpobj->office_phone != $this->phone_pro)
				{
					$tmpobj->office_phone = $this->phone_pro;
					$usermustbemodified++;
				}
				if ($tmpobj->office_fax != $this->fax)
				{
					$tmpobj->office_fax = $this->fax;
					$usermustbemodified++;
				}
				if ($tmpobj->address != $this->address)
				{
					$tmpobj->address = $this->address;
					$usermustbemodified++;
				}
				if ($tmpobj->town != $this->town)
				{
					$tmpobj->town = $this->town;
					$usermustbemodified++;
				}
				if ($tmpobj->zip != $this->zip)
				{
					$tmpobj->zip = $this->zip;
					$usermustbemodified++;
				}
				if ($tmpobj->zip != $this->zip)
				{
					$tmpobj->state_id=$this->state_id;
					$usermustbemodified++;
				}
				if ($tmpobj->country_id != $this->country_id)
				{
					$tmpobj->country_id = $this->country_id;
					$usermustbemodified++;
				}
				if ($tmpobj->email != $this->email)
				{
					$tmpobj->email = $this->email;
					$usermustbemodified++;
				}
				if ($tmpobj->skype != $this->skype)
				{
					$tmpobj->skype = $this->skype;
					$usermustbemodified++;
				}
				if ($usermustbemodified)
				{
					$result=$tmpobj->update($user, 0, 1, 1, 1);
					if ($result < 0) { $error++; }
				}
			}

			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('CONTACT_MODIFY',$user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return 1;
			}
			else
			{
				dol_syslog(get_class($this)."::update Error ".$this->error,LOG_ERR);
				$this->db->rollback();
				return -$error;
			}
		}
		else
		{
			$this->error=$this->db->lasterror().' sql='.$sql;
            $this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param		array	$info		Info string loaded by _load_ldap_info
	 *	@param		int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *									1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *									2=Return key only (uid=qqq)
	 *	@return		string				DN
	 */
	function _load_ldap_dn($info,$mode=0)
	{
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS].",".$conf->global->LDAP_CONTACT_DN;
		if ($mode==1) $dn=$conf->global->LDAP_CONTACT_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_CONTACTS."=".$info[$conf->global->LDAP_KEY_CONTACTS];
		return $dn;
	}


	/**
	 *	Initialise tableau info (tableau des attributs LDAP)
	 *
	 *	@return		array		Tableau info des attributs
	 */
	function _load_ldap_info()
	{
		global $conf,$langs;

        $info = array();

        // Object classes
		$info["objectclass"]=explode(',',$conf->global->LDAP_CONTACT_OBJECT_CLASS);

		$this->fullname=$this->getFullName($langs);

		// Fields
		if ($this->fullname && ! empty($conf->global->LDAP_CONTACT_FIELD_FULLNAME)) $info[$conf->global->LDAP_CONTACT_FIELD_FULLNAME] = $this->fullname;
		if ($this->lastname && ! empty($conf->global->LDAP_CONTACT_FIELD_NAME)) $info[$conf->global->LDAP_CONTACT_FIELD_NAME] = $this->lastname;
		if ($this->firstname && ! empty($conf->global->LDAP_CONTACT_FIELD_FIRSTNAME)) $info[$conf->global->LDAP_CONTACT_FIELD_FIRSTNAME] = $this->firstname;

		if ($this->poste) $info["title"] = $this->poste;
		if ($this->socid > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[$conf->global->LDAP_CONTACT_FIELD_COMPANY] = $soc->name;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}
		if ($this->address && ! empty($conf->global->LDAP_CONTACT_FIELD_ADDRESS)) $info[$conf->global->LDAP_CONTACT_FIELD_ADDRESS] = $this->address;
		if ($this->zip && ! empty($conf->global->LDAP_CONTACT_FIELD_ZIP))          $info[$conf->global->LDAP_CONTACT_FIELD_ZIP] = $this->zip;
		if ($this->town && ! empty($conf->global->LDAP_CONTACT_FIELD_TOWN))      $info[$conf->global->LDAP_CONTACT_FIELD_TOWN] = $this->town;
		if ($this->country_code && ! empty($conf->global->LDAP_CONTACT_FIELD_COUNTRY))      $info[$conf->global->LDAP_CONTACT_FIELD_COUNTRY] = $this->country_code;
		if ($this->phone_pro && ! empty($conf->global->LDAP_CONTACT_FIELD_PHONE)) $info[$conf->global->LDAP_CONTACT_FIELD_PHONE] = $this->phone_pro;
		if ($this->phone_perso && ! empty($conf->global->LDAP_CONTACT_FIELD_HOMEPHONE)) $info[$conf->global->LDAP_CONTACT_FIELD_HOMEPHONE] = $this->phone_perso;
		if ($this->phone_mobile && ! empty($conf->global->LDAP_CONTACT_FIELD_MOBILE)) $info[$conf->global->LDAP_CONTACT_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && ! empty($conf->global->LDAP_CONTACT_FIELD_FAX))	    $info[$conf->global->LDAP_CONTACT_FIELD_FAX] = $this->fax;
        if ($this->skype && ! empty($conf->global->LDAP_CONTACT_FIELD_SKYPE))	    $info[$conf->global->LDAP_CONTACT_FIELD_SKYPE] = $this->skype;
        if ($this->note_private && ! empty($conf->global->LDAP_CONTACT_FIELD_DESCRIPTION)) $info[$conf->global->LDAP_CONTACT_FIELD_DESCRIPTION] = dol_string_nohtmltag($this->note_private, 2);
		if ($this->email && ! empty($conf->global->LDAP_CONTACT_FIELD_MAIL))     $info[$conf->global->LDAP_CONTACT_FIELD_MAIL] = $this->email;

		if ($conf->global->LDAP_SERVER_TYPE == 'egroupware')
		{
			$info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware

			$info['uidnumber'] = $this->id;

			$info['phpgwTz']      = 0;
			$info['phpgwMailType'] = 'INTERNET';
			$info['phpgwMailHomeType'] = 'INTERNET';

			$info["phpgwContactTypeId"] = 'n';
			$info["phpgwContactCatId"] = 0;
			$info["phpgwContactAccess"] = "public";

			if (dol_strlen($this->egroupware_id) == 0)
			{
				$this->egroupware_id = 1;
			}

			$info["phpgwContactOwner"] = $this->egroupware_id;

			if ($this->email) $info["rfc822Mailbox"] = $this->email;
			if ($this->phone_mobile) $info["phpgwCellTelephoneNumber"] = $this->phone_mobile;
		}

		return $info;
	}


	/**
	 *  Update field alert birthday
	 *
	 *  @param      int			$id         Id of contact
	 *  @param      User		$user		User asking to change alert or birthday
	 *  @param      int		    $notrigger	0=no, 1=yes
     *  @return     int         			<0 if KO, >=0 if OK
	 */
	function update_perso($id, $user=null, $notrigger=0)
	{
	    $error=0;
	    $result=false;

	    $this->db->begin();

		// Mis a jour contact
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET";
		$sql.= " birthday=".($this->birthday ? "'".$this->db->idate($this->birthday)."'" : "null");
		$sql.= ", photo = ".($this->photo? "'".$this->db->escape($this->photo)."'" : "null");
		if ($user) $sql .= ", fk_user_modif=".$user->id;
		$sql.= " WHERE rowid=".$this->db->escape($id);

		dol_syslog(get_class($this)."::update_perso this->birthday=".$this->birthday." -", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql)
		{
			$error++;
			$this->error=$this->db->lasterror();
		}

		// Mis a jour alerte birthday
		if ($this->birthday_alert)
		{
			//check existing
			$sql_check = "SELECT * FROM ".MAIN_DB_PREFIX."user_alert WHERE type=1 AND fk_contact=".$this->db->escape($id)." AND fk_user=".$user->id;
			$result_check = $this->db->query($sql_check);
			if (! $result_check || ($this->db->num_rows($result_check)<1))
			{
				//insert
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_alert(type,fk_contact,fk_user) ";
				$sql.= "VALUES (1,".$this->db->escape($id).",".$user->id.")";
				$result = $this->db->query($sql);
				if (! $result)
				{
                    $error++;
                    $this->error=$this->db->lasterror();
				}
			}
			else
			{
				$result = true;
			}
		}
		else
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_alert ";
			$sql.= "WHERE type=1 AND fk_contact=".$this->db->escape($id)." AND fk_user=".$user->id;
			$result = $this->db->query($sql);
			if (! $result)
			{
                $error++;
                $this->error=$this->db->lasterror();
			}
		}

		if (! $error && ! $notrigger)
		{
		    // Call trigger
		    $result=$this->call_trigger('CONTACT_MODIFY',$user);
		    if ($result < 0) { $error++; }
		    // End call triggers
		}

		if (! $error)
		{
		    $this->db->commit();
		    return 1;
		}
		else
		{
		    dol_syslog(get_class($this)."::update Error ".$this->error,LOG_ERR);
		    $this->db->rollback();
		    return -$error;
		}
	}


	/**
	 *  Load object contact
	 *
	 *  @param      int		$id          id du contact
	 *  @param      User	$user        Utilisateur (abonnes aux alertes) qui veut les alertes de ce contact
     *  @param      string  $ref_ext     External reference, not given by Dolibarr
	 *  @return     int     		     -1 if KO, 0 if OK but not found, 1 if OK
	 */
	function fetch($id, $user=0, $ref_ext='')
	{
		global $langs;

		dol_syslog(get_class($this)."::fetch id=".$id, LOG_DEBUG);

		if (empty($id) && empty($ref_ext))
		{
			$this->error='BadParameter';
			return -1;
		}

		$langs->load("companies");

		$sql = "SELECT c.rowid, c.entity, c.fk_soc, c.ref_ext, c.civility as civility_id, c.lastname, c.firstname,";
		$sql.= " c.address, c.statut, c.zip, c.town,";
		$sql.= " c.fk_pays as country_id,";
		$sql.= " c.fk_departement,";
		$sql.= " c.birthday,";
		$sql.= " c.poste, c.phone, c.phone_perso, c.phone_mobile, c.fax, c.email, c.jabberid, c.skype,";
        $sql.= " c.photo,";
		$sql.= " c.priv, c.note_private, c.note_public, c.default_lang, c.no_email, c.canvas,";
		$sql.= " c.import_key,";
		$sql.= " c.datec as date_creation, c.tms as date_modification,";
		$sql.= " co.label as country, co.code as country_code,";
		$sql.= " d.nom as state, d.code_departement as state_code,";
		$sql.= " u.rowid as user_id, u.login as user_login,";
		$sql.= " s.nom as socname, s.address as socaddress, s.zip as soccp, s.town as soccity, s.default_lang as socdefault_lang";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as co ON c.fk_pays = co.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON c.fk_departement = d.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON c.rowid = u.fk_socpeople";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON c.fk_soc = s.rowid";
		if ($id) $sql.= " WHERE c.rowid = ". $id;
		elseif ($ref_ext) {
			$sql .= " WHERE c.entity IN (".getEntity($this->element).")";
			$sql .= " AND c.ref_ext = '".$this->db->escape($ref_ext)."'";
		}

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id				= $obj->rowid;
				$this->entity			= $obj->entity;
				$this->ref				= $obj->rowid;
				$this->ref_ext			= $obj->ref_ext;
				$this->civility_id		= $obj->civility_id;
				$this->civility_code		= $obj->civility_id;
				$this->lastname			= $obj->lastname;
				$this->firstname			= $obj->firstname;
				$this->address			= $obj->address;
				$this->zip				= $obj->zip;
				$this->town				= $obj->town;

				$this->date_creation     = $this->db->jdate($obj->date_creation);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				
				$this->fk_departement	= $obj->fk_departement;    // deprecated
				$this->state_id			= $obj->fk_departement;
				$this->departement_code	= $obj->state_code;	       // deprecated
				$this->state_code		= $obj->state_code;
				$this->departement		= $obj->state;	           // deprecated
				$this->state				= $obj->state;

				$this->country_id 		= $obj->country_id;
				$this->country_code		= $obj->country_id?$obj->country_code:'';
				$this->country			= $obj->country_id?($langs->trans('Country'.$obj->country_code)!='Country'.$obj->country_code?$langs->transnoentities('Country'.$obj->country_code):$obj->country):'';

				$this->socid				= $obj->fk_soc;
				$this->socname			= $obj->socname;
				$this->poste				= $obj->poste;
				$this->statut			= $obj->statut;

				$this->phone_pro			= trim($obj->phone);
				$this->fax				= trim($obj->fax);
				$this->phone_perso		= trim($obj->phone_perso);
				$this->phone_mobile		= trim($obj->phone_mobile);

				$this->email				= $obj->email;
				$this->jabberid			= $obj->jabberid;
				$this->skype				= $obj->skype;
				$this->photo				= $obj->photo;
				$this->priv				= $obj->priv;
				$this->mail				= $obj->email;

				$this->birthday			= $this->db->jdate($obj->birthday);
				$this->note				= $obj->note_private;		// deprecated
				$this->note_private		= $obj->note_private;
				$this->note_public		= $obj->note_public;
				$this->default_lang		= $obj->default_lang;
				$this->no_email			= $obj->no_email;
				$this->user_id			= $obj->user_id;
				$this->user_login		= $obj->user_login;
				$this->canvas			= $obj->canvas;

				$this->import_key		= $obj->import_key;

				// Define gender according to civility
				$this->setGenderFromCivility();

				// Search Dolibarr user linked to this contact
				$sql = "SELECT u.rowid ";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE u.fk_socpeople = ". $this->id;

				$resql=$this->db->query($sql);
				if ($resql)
				{
					if ($this->db->num_rows($resql))
					{
						$uobj = $this->db->fetch_object($resql);

						$this->user_id = $uobj->rowid;
					}
					$this->db->free($resql);
				}
				else
				{
					$this->error=$this->db->error();
					return -1;
				}

				// Charge alertes du user
				if ($user)
				{
					$sql = "SELECT fk_user";
					$sql .= " FROM ".MAIN_DB_PREFIX."user_alert";
					$sql .= " WHERE fk_user = ".$user->id." AND fk_contact = ".$this->db->escape($id);

					$resql=$this->db->query($sql);
					if ($resql)
					{
						if ($this->db->num_rows($resql))
						{
							$obj = $this->db->fetch_object($resql);

							$this->birthday_alert = 1;
						}
						$this->db->free($resql);
					}
					else
					{
						$this->error=$this->db->error();
						return -1;
					}
				}

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				return 1;
			}
			else
			{
				$this->error=$langs->trans("RecordNotFound");
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 * Set property ->gender from property ->civility_id
	 *
	 * @return void
	 */
	function setGenderFromCivility()
	{
	    unset($this->gender);
    	if (in_array($this->civility_id, array('MR'))) {
    	    $this->gender = 'man';
    	} else if(in_array($this->civility_id, array('MME','MLE'))) {
    	    $this->gender = 'woman';
    	}
	}

	/**
	 *  Load number of elements the contact is used as a link for
	 *  ref_facturation
	 *  ref_contrat
	 *  ref_commande (for order and/or shipments)
	 *  ref_propale
	 *
     *  @return     int             					<0 if KO, >=0 if OK
	 */
	function load_ref_elements()
	{
		// Compte les elements pour lesquels il est contact
		$sql ="SELECT tc.element, count(ec.rowid) as nb";
		$sql.=" FROM ".MAIN_DB_PREFIX."element_contact as ec, ".MAIN_DB_PREFIX."c_type_contact as tc";
		$sql.=" WHERE ec.fk_c_type_contact = tc.rowid";
		$sql.=" AND fk_socpeople = ". $this->id;
		$sql.=" AND tc.source = 'external'";
		$sql.=" GROUP BY tc.element";

		dol_syslog(get_class($this)."::load_ref_elements", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while($obj=$this->db->fetch_object($resql))
			{
				if ($obj->nb)
				{
					if ($obj->element=='facture')  $this->ref_facturation = $obj->nb;
					if ($obj->element=='contrat')  $this->ref_contrat = $obj->nb;
					if ($obj->element=='commande') $this->ref_commande = $obj->nb;
					if ($obj->element=='propal')   $this->ref_propal = $obj->nb;
				}
			}
			$this->db->free($resql);
			return 0;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *   	Efface le contact de la base
	 *
	 *   	@param		int		$notrigger		Disable all trigger
	 *		@return		int						<0 if KO, >0 if OK
	 */
	function delete($notrigger=0)
	{
		global $conf, $langs, $user;

		$error=0;

		$this->old_lastname       = $obj->lastname;
		$this->old_firstname      = $obj->firstname;

		$this->db->begin();

		if (! $error)
		{
			// Get all rowid of element_contact linked to a type that is link to llx_socpeople
			$sql = "SELECT ec.rowid";
			$sql.= " FROM ".MAIN_DB_PREFIX."element_contact ec,";
			$sql.= " ".MAIN_DB_PREFIX."c_type_contact tc";
			$sql.= " WHERE ec.fk_socpeople=".$this->id;
			$sql.= " AND ec.fk_c_type_contact=tc.rowid";
			$sql.= " AND tc.source='external'";
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num=$this->db->num_rows($resql);

				$i=0;
				while ($i < $num && ! $error)
				{
					$obj = $this->db->fetch_object($resql);

					$sqldel = "DELETE FROM ".MAIN_DB_PREFIX."element_contact";
					$sqldel.=" WHERE rowid = ".$obj->rowid;
					dol_syslog(get_class($this)."::delete", LOG_DEBUG);
					$result = $this->db->query($sqldel);
					if (! $result)
					{
						$error++;
						$this->error=$this->db->error().' sql='.$sqldel;
					}

					$i++;
				}
			}
			else
			{
				$error++;
				$this->error=$this->db->error().' sql='.$sql;
			}
		}

		if (! $error)
		{
			// Remove category
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_contact WHERE fk_socpeople = ".$this->id;
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag=-1;
			}
		}

		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."socpeople";
			$sql .= " WHERE rowid=".$this->id;
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$result = $this->db->query($sql);
			if (! $result)
			{
				$error++;
				$this->error=$this->db->error().' sql='.$sql;
			}
		}

		// Removed extrafields
		 if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) { // For avoid conflicts if trigger used
			$result=$this->deleteExtraFields($this);
			if ($result < 0) $error++;
		}

		if (! $error && ! $notrigger)
		{
            // Call trigger
            $result=$this->call_trigger('CONTACT_DELETE',$user);
            if ($result < 0) { $error++; }
            // End call triggers
		}

		if (! $error)
		{

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_syslog("Error ".$this->error,LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Charge les informations sur le contact, depuis la base
	 *
	 *  @param		int		$id      Id du contact a charger
	 *  @return		void
	 */
	function info($id)
	{
		$sql = "SELECT c.rowid, c.datec as datec, c.fk_user_creat,";
		$sql.= " c.tms as tms, c.fk_user_modif";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c";
		$sql.= " WHERE c.rowid = ".$this->db->escape($id);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->id                = $obj->rowid;

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

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
			}

			$this->db->free($resql);
		}
		else
		{
			print $this->db->error();
		}
	}

	/**
	 *  Return number of mass Emailing received by this contacts with its email
	 *
	 *  @return       int     Number of EMailings
	 */
	function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc, ".MAIN_DB_PREFIX."mailing as m";
		$sql.= " WHERE mc.fk_mailing=m.rowid AND mc.email = '".$this->db->escape($this->email)."' ";
		$sql.= " AND m.entity IN (".getEntity($this->element).") AND mc.statut NOT IN (-1,0)";      // -1 error, 0 not sent, 1 sent with success

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$nb=$obj->nb;

			$this->db->free($resql);
			return $nb;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Return name of contact with link (and eventually picto)
	 *	Use $this->id, $this->lastname, $this->firstname, this->civility_id
	 *
	 *	@param		int			$withpicto					Include picto with link
	 *	@param		string		$option						Where the link point to
	 *	@param		int			$maxlen						Max length of
	 *  @param		string		$moreparam					Add more param into URL
     *  @param      int     	$save_lastsearch_value		-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@param		int			$notooltip					1=Disable tooltip
	 *	@return		string									String with URL
	 */
	function getNomUrl($withpicto=0, $option='', $maxlen=0, $moreparam='', $save_lastsearch_value=-1, $notooltip=0)
	{
		global $conf, $langs, $hookmanager;

		$result='';

        $label = '<u>' . $langs->trans("ShowContact") . '</u>';
        $label.= '<br><b>' . $langs->trans("Name") . ':</b> '.$this->getFullName($langs);
        //if ($this->civility_id) $label.= '<br><b>' . $langs->trans("Civility") . ':</b> '.$this->civility_id;		// TODO Translate cibilty_id code
        if (! empty($this->poste)) $label.= '<br><b>' . $langs->trans("Poste") . ':</b> '.$this->poste;
        $label.= '<br><b>' . $langs->trans("EMail") . ':</b> '.$this->email;
        $phonelist=array();
        if ($this->phone_pro) $phonelist[]=$this->phone_pro;
        if ($this->phone_mobile) $phonelist[]=$this->phone_mobile;
        if ($this->phone_perso) $phonelist[]=$this->phone_perso;
        $label.= '<br><b>' . $langs->trans("Phone") . ':</b> '.join(', ',$phonelist);
        $label.= '<br><b>' . $langs->trans("Address") . ':</b> '.dol_format_address($this, 1, ' ', $langs);

        $url = DOL_URL_ROOT.'/contact/card.php?id='.$this->id;

        if ($option !== 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        	if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $url .= $moreparam;

        $linkclose="";
		if (empty($notooltip)) {
	    	if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
    	    {
        	    $label=$langs->trans("ShowContact");
            	$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
        	}
	       	$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
    	   	$linkclose.= ' class="classfortooltip"';

    	   	/*
    	   	 $hookmanager->initHooks(array('contactdao'));
    	   	 $parameters=array('id'=>$this->id);
    	   	 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
    	   	 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
    	   	 */
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		if ($option == 'xxx')
		{
			$linkstart = '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$this->id.$moreparam.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
			$linkend='</a>';
		}

		$result.=$linkstart;
		if ($withpicto) $result.=img_object(($notooltip?'':$label), ($this->picto?$this->picto:'generic'), ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip valigntextbottom"'), 0, 0, $notooltip?0:1);
		if ($withpicto != 2) $result.=($maxlen?dol_trunc($this->getFullName($langs),$maxlen):$this->getFullName($langs));
		$result.=$linkend;

		global $action;
		$hookmanager->initHooks(array('contactdao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *    Return civility label of contact
	 *
	 *    @return	string      			Translated name of civility
	 */
	function getCivilityLabel()
	{
		global $langs;
		$langs->load("dict");

		$code=(! empty($this->civility_id)?$this->civility_id:(! empty($this->civilite_id)?$this->civilite_id:''));
		if (empty($code)) return '';
        return $langs->getLabelFromKey($this->db, "Civility".$code, "c_civility", "code", "label", $code);
	}

	/**
	 *	Return label of contact status
	 *
	 *	@param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 * 	@return 	string					Label of contact status
	 */
	function getLibStatut($mode)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *	Renvoi le libelle d'un statut donne
	 *
	 *  @param      int			$statut     Id statut
	 *  @param      int			$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return     string					Libelle
	 */
	function LibStatut($statut,$mode)
	{
		global $langs;

		if ($mode == 0)
		{
			if ($statut==0 || $statut==5) return $langs->trans('Disabled');
			elseif ($statut==1 || $statut==4) return $langs->trans('Enabled');
		}
		elseif ($mode == 1)
		{
			if ($statut==0 || $statut==5) return $langs->trans('Disabled');
			elseif ($statut==1 || $statut==4) return $langs->trans('Enabled');
		}
		elseif ($mode == 2)
		{
			if ($statut==0 || $statut==5) return img_picto($langs->trans('Disabled'),'statut5', 'class="pictostatus"').' '.$langs->trans('Disabled');
			elseif ($statut==1 || $statut==4) return img_picto($langs->trans('Enabled'),'statut4', 'class="pictostatus"').' '.$langs->trans('Enabled');

		}
		elseif ($mode == 3)
		{
			if ($statut==0 || $statut==5) return img_picto($langs->trans('Disabled'),'statut5', 'class="pictostatus"');
			elseif ($statut==1 || $statut==4) return img_picto($langs->trans('Enabled'),'statut4', 'class="pictostatus"');
		}
		elseif ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans('Disabled'),'statut5', 'class="pictostatus"').' '.$langs->trans('Disabled');
			elseif ($statut==1 || $statut==4) return img_picto($langs->trans('Enabled'),'statut4', 'class="pictostatus"').' '.$langs->trans('Enabled');
		}
		elseif ($mode == 5)
		{
			if ($statut==0 || $statut==5) return '<span class="hideonsmartphone">'.$langs->trans('Disabled').' </span>'.img_picto($langs->trans('Disabled'),'statut5', 'class="pictostatus"');
			elseif ($statut==1 || $statut==4) return '<span class="hideonsmartphone">'.$langs->trans('Enabled').' </span>'.img_picto($langs->trans('Enabled'),'statut4', 'class="pictostatus"');
		}
	}


	/**
	 *	Return translated label of Public or Private
	 *
	 * 	@param      int			$statut		Type (0 = public, 1 = private)
	 *  @return     string					Label translated
	 */
	function LibPubPriv($statut)
	{
		global $langs;
		if ($statut=='1') return $langs->trans('ContactPrivate');
		else return $langs->trans('ContactPublic');
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
		// Get first id of existing company and save it into $socid
		$socid = 0;
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe ORDER BY rowid LIMIT 1";
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj) $socid=$obj->rowid;
		}

		// Initialise parameters
		$this->id=0;
		$this->specimen=1;
		$this->lastname = 'DOLIBARR';
		$this->firstname = 'SPECIMEN';
		$this->address = '21 jump street';
		$this->zip = '99999';
		$this->town = 'MyTown';
		$this->country_id = 1;
		$this->country_code = 'FR';
		$this->country = 'France';
		$this->email = 'specimen@specimen.com';
    	$this->skype = 'tom.hanson';

		$this->phone_pro = '0909090901';
		$this->phone_perso = '0909090902';
		$this->phone_mobile = '0909090903';
		$this->fax = '0909090909';

		$this->note_public='This is a comment (public)';
		$this->note_private='This is a comment (private)';

		$this->socid = $socid;
		$this->statut=1;
	}

	/**
	 *  Change status of a user
	 *
	 *	@param	int		$statut		Status to set
	 *  @return int     			<0 if KO, 0 if nothing is done, >0 if OK
	 */
	function setstatus($statut)
	{
		global $conf,$langs,$user;

		$error=0;

		// Check parameters
		if ($this->statut == $statut) return 0;
		else $this->statut = $statut;

		$this->db->begin();

		// Desactive utilisateur
		$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople";
		$sql.= " SET statut = ".$this->statut;
		$sql.= " WHERE rowid = ".$this->id;
		$result = $this->db->query($sql);

		dol_syslog(get_class($this)."::setstatus", LOG_DEBUG);
		if ($result)
		{
            // Call trigger
            $result=$this->call_trigger('CONTACT_ENABLEDISABLE',$user);
            if ($result < 0) { $error++; }
            // End call triggers
		}

		if ($error)
		{
			$this->db->rollback();
			return -$error;
		}
		else
		{
			$this->db->commit();
			return 1;
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
	 */
	public function setCategories($categories)
	{
		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, Categorie::TYPE_CONTACT, 'id');

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
				$c->del_type($this, 'contact');
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, 'contact');
			}
		}

		return;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'socpeople'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}
