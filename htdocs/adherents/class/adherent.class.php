<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2009-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2016	Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015-2018  Frédéric France			<frederic.france@netlogic.fr>
 * Copyright (C) 2015		Raphaël Doursenaud		<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016		Juanjo Menent			<jmenent@2byte.es>
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
 *	\file       htdocs/adherents/class/adherent.class.php
 *	\ingroup    member
 *	\brief      File of class to manage members of a foundation
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


/**
 *		Class to manage members of a foundation
 */
class Adherent extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element='member';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element='adherent';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	public $mesgs;

    /**
     * @var string login of member
     */
	public $login;

	//! Clear password in memory
	public $pass;
	//! Clear password in database (defined if DATABASE_PWD_ENCRYPTED=0)
	public $pass_indatabase;
	//! Encrypted password in database (always defined)
	public $pass_indatabase_crypted;

    /**
     * @var string company name
     * @deprecated
     */
	public $societe;

	/**
	 * @var string company name
	 */
	public $company;

	/**
	 * @var int Thirdparty ID
	 */
    public $fk_soc;

	/**
	 * @var string Address
	 */
	public $address;

    /**
     * @var string zipcode
     */
    public $zip;

    /**
     * @var string town
     */
	public $town;

    /**
     * @var int Id of state
     */
    public $state_id;

    /**
     * @var string Code of state
     */
    public $state_code;

    /**
     * @var string Label of state
     */
	public $state;

    /**
     * @var string email
     */
	public $email;

    /**
     * @var string skype account
     */
    public $skype;

    /**
     * @var string twitter account
     */
    public $twitter;

    /**
     * @var string facebook account
     */
	public $facebook;

    /**
     * @var string Phone number
     */
	public $phone;

    /**
     * @var string Private Phone number
     */
	public $phone_perso;

    /**
     * @var string Mobile phone number
     */
	public $phone_mobile;

    /**
     * @var string Fax number
     */
    public $fax;

    /**
     * @var string Function
     */
    public $poste;

	public $morphy;
	public $public;

    // -1:brouillon, 0:resilie, >=1:valide,paye
    // def in common object
    //public $statut;

    public $photo;

	public $datec;
	public $datem;
	public $datevalid;

	public $birth;

    /**
     * @var int id type member
     */
	public $typeid;

    /**
     * @var string label type member
     */
	public $type;
	public $need_subscription;

	public $user_id;
	public $user_login;

	public $datefin;	// From member table

	// Fields loaded by fetch_subscriptions()
	public $first_subscription_date;
	public $first_subscription_amount;
	public $last_subscription_date;
	public $last_subscription_date_start;
	public $last_subscription_date_end;
	public $last_subscription_amount;
	public $subscriptions=array();

    /**
     * @var Adherent To contains a clone of this when we need to save old properties of object
     */
	public $oldcopy;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		$this->statut = -1;
		// l'adherent n'est pas public par defaut
		$this->public = 0;
		// les champs optionnels sont vides
		$this->array_options=array();
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Function sending an email to the current member with the text supplied in parameter.
	 *
	 *  @param	string	$text				Content of message (not html entities encoded)
	 *  @param	string	$subject			Subject of message
	 *  @param 	array	$filename_list      Array of attached files
	 *  @param 	array	$mimetype_list      Array of mime types of attached files
	 *  @param 	array	$mimefilename_list  Array of public names of attached files
	 *  @param 	string	$addr_cc            Email cc
	 *  @param 	string	$addr_bcc           Email bcc
	 *  @param 	int		$deliveryreceipt	Ask a delivery receipt
	 *  @param	int		$msgishtml			1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
	 *  @param	string	$errors_to			erros to
	 *  @param	string	$moreinheader		Add more html headers
	 *  @return	int							<0 if KO, >0 if OK
	 */
	function send_an_email($text, $subject, $filename_list=array(), $mimetype_list=array(), $mimefilename_list=array(), $addr_cc="", $addr_bcc="", $deliveryreceipt=0, $msgishtml=-1, $errors_to='', $moreinheader='')
	{
        // phpcs:enable
		global $conf,$langs;

		// Detect if message is HTML
		if ($msgishtml == -1)
		{
			$msgishtml = 0;
			if (dol_textishtml($text,0)) $msgishtml = 1;
		}

		dol_syslog('send_an_email msgishtml='.$msgishtml);

		$texttosend=$this->makeSubstitution($text);
		$subjecttosend=$this->makeSubstitution($subject);
		if ($msgishtml) $texttosend=dol_htmlentitiesbr($texttosend);

		// Envoi mail confirmation
		$from=$conf->email_from;
		if (! empty($conf->global->ADHERENT_MAIL_FROM)) $from=$conf->global->ADHERENT_MAIL_FROM;

		$trackid = 'mem'.$this->id;

		// Send email (substitutionarray must be done just before this)
		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($subjecttosend, $this->email, $from, $texttosend, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml, '', '', $trackid, $moreinheader);
		if ($mailfile->sendfile())
		{
			return 1;
		}
		else
		{
			$this->error=$langs->trans("ErrorFailedToSendMail",$from,$this->email).'. '.$mailfile->error;
			return -1;
		}
	}


	/**
	 * Make substitution of tags into text with value of current object.
	 *
	 * @param	string	$text       Text to make substitution to
	 * @return  string      		Value of input text string with substitutions done
	 */
	function makeSubstitution($text)
	{
		global $conf,$langs;

		$birthday = dol_print_date($this->birth,'day');

		$msgishtml = 0;
		if (dol_textishtml($text,1)) $msgishtml = 1;

		$infos='';
		if ($this->civility_id) $infos.= $langs->transnoentities("UserTitle").": ".$this->getCivilityLabel()."\n";
		$infos.= $langs->transnoentities("id").": ".$this->id."\n";
		$infos.= $langs->transnoentities("Lastname").": ".$this->lastname."\n";
		$infos.= $langs->transnoentities("Firstname").": ".$this->firstname."\n";
		$infos.= $langs->transnoentities("Company").": ".$this->societe."\n";
		$infos.= $langs->transnoentities("Address").": ".$this->address."\n";
		$infos.= $langs->transnoentities("Zip").": ".$this->zip."\n";
		$infos.= $langs->transnoentities("Town").": ".$this->town."\n";
		$infos.= $langs->transnoentities("Country").": ".$this->country."\n";
		$infos.= $langs->transnoentities("EMail").": ".$this->email."\n";
		$infos.= $langs->transnoentities("PhonePro").": ".$this->phone."\n";
		$infos.= $langs->transnoentities("PhonePerso").": ".$this->phone_perso."\n";
		$infos.= $langs->transnoentities("PhoneMobile").": ".$this->phone_mobile."\n";
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			$infos.= $langs->transnoentities("Login").": ".$this->login."\n";
			$infos.= $langs->transnoentities("Password").": ".$this->pass."\n";
		}
		$infos.= $langs->transnoentities("Birthday").": ".$birthday."\n";
		$infos.= $langs->transnoentities("Photo").": ".$this->photo."\n";
		$infos.= $langs->transnoentities("Public").": ".yn($this->public);

		// Substitutions
		$substitutionarray=array(
		    '__ID__'=>$this->id,
		    '__MEMBER_ID__'=>$this->id,
			'__CIVILITY__'=>$this->getCivilityLabel(),
			'__FIRSTNAME__'=>$msgishtml?dol_htmlentitiesbr($this->firstname):($this->firstname?$this->firstname:''),
			'__LASTNAME__'=>$msgishtml?dol_htmlentitiesbr($this->lastname):($this->lastname?$this->lastname:''),
			'__FULLNAME__'=>$msgishtml?dol_htmlentitiesbr($this->getFullName($langs)):$this->getFullName($langs),
			'__COMPANY__'=>$msgishtml?dol_htmlentitiesbr($this->societe):($this->societe?$this->societe:''),
			'__ADDRESS__'=>$msgishtml?dol_htmlentitiesbr($this->address):($this->address?$this->address:''),
			'__ZIP__'=>$msgishtml?dol_htmlentitiesbr($this->zip):($this->zip?$this->zip:''),
			'__TOWN__'=>$msgishtml?dol_htmlentitiesbr($this->town):($this->town?$this->town:''),
			'__COUNTRY__'=>$msgishtml?dol_htmlentitiesbr($this->country):($this->country?$this->country:''),
			'__EMAIL__'=>$msgishtml?dol_htmlentitiesbr($this->email):($this->email?$this->email:''),
			'__BIRTH__'=>$msgishtml?dol_htmlentitiesbr($birthday):($birthday?$birthday:''),
			'__PHOTO__'=>$msgishtml?dol_htmlentitiesbr($this->photo):($this->photo?$this->photo:''),
			'__LOGIN__'=>$msgishtml?dol_htmlentitiesbr($this->login):($this->login?$this->login:''),
			'__PASSWORD__'=>$msgishtml?dol_htmlentitiesbr($this->pass):($this->pass?$this->pass:''),
			'__PHONE__'=>$msgishtml?dol_htmlentitiesbr($this->phone):($this->phone?$this->phone:''),
			'__PHONEPRO__'=>$msgishtml?dol_htmlentitiesbr($this->phone_perso):($this->phone_perso?$this->phone_perso:''),
			'__PHONEMOBILE__'=>$msgishtml?dol_htmlentitiesbr($this->phone_mobile):($this->phone_mobile?$this->phone_mobile:'')
		);

		complete_substitutions_array($substitutionarray, $langs, $this);

		return make_substitutions($text, $substitutionarray, $langs);
	}


	/**
	 *	Return translated label by the nature of a adherent (physical or moral)
	 *
	 *	@param	string		$morphy		Nature of the adherent (physical or moral)
	 *	@return	string					Label
	 */
	function getmorphylib($morphy='')
	{
		global $langs;
		if (! $morphy) { $morphy=$this->morphy; }
		if ($morphy == 'phy') { return $langs->trans("Physical"); }
		if ($morphy == 'mor') { return $langs->trans("Moral"); }
		return $morphy;
	}

	/**
	 *	Create a member into database
	 *
	 *	@param	User	$user        	Objet user qui demande la creation
	 *	@param  int		$notrigger		1 ne declenche pas les triggers, 0 sinon
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function create($user,$notrigger=0)
	{
		global $conf,$langs;

		$error=0;

		$now=dol_now();

		// Clean parameters
		$this->import_key = trim($this->import_key);

		// Check parameters
		if (! empty($conf->global->ADHERENT_MAIL_REQUIRED) && ! isValidEMail($this->email))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}
		if (! $this->datec) $this->datec=$now;
		if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED))
		{
			if (empty($this->login))
			{
				$this->error = $langs->trans("ErrorWrongValueForParameterX","Login");
				return -1;
			}
		}

		$this->db->begin();

		// Insert member
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."adherent";
		$sql.= " (datec,login,fk_user_author,fk_user_mod,fk_user_valid,morphy,fk_adherent_type,entity,import_key)";
		$sql.= " VALUES (";
		$sql.= " '".$this->db->idate($this->datec)."'";
		$sql.= ", ".($this->login?"'".$this->db->escape($this->login)."'":"null");
		$sql.= ", ".($user->id>0?$user->id:"null");	// Can be null because member can be created by a guest or a script
		$sql.= ", null, null, '".$this->db->escape($this->morphy)."'";
		$sql.= ", ".$this->typeid;
		$sql.= ", ".$conf->entity;
		$sql.= ", ".(! empty($this->import_key) ? "'".$this->db->escape($this->import_key)."'":"null");
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent");
			if ($id > 0)
			{
				$this->id=$id;
				$this->ref=(string) $id;

				// Update minor fields
				$result=$this->update($user,1,1,0,0,'add'); // nosync is 1 to avoid update data of user
				if ($result < 0)
				{
					$this->db->rollback();
					return -1;
				}

				// Add link to user
				if ($this->user_id)
				{
					// Add link to user
					$sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
					$sql.= " fk_member = ".$this->id;
					$sql.= " WHERE rowid = ".$this->user_id;
					dol_syslog(get_class($this)."::create", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql)
					{
						$this->error='Failed to update user to make link with member';
						$this->db->rollback();
						return -4;
					}
				}

				if (! $notrigger)
				{
					// Call trigger
					$result=$this->call_trigger('MEMBER_CREATE',$user);
					if ($result < 0) { $error++; }
					// End call triggers
				}

				if (count($this->errors))
				{
					dol_syslog(get_class($this)."::create ".implode(',',$this->errors), LOG_ERR);
					$this->db->rollback();
					return -3;
				}
				else
				{
					$this->db->commit();
					return $this->id;
				}
			}
			else
			{
				$this->error='Failed to get last insert id';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Update a member in database (standard information and password)
	 *
	 *	@param	User	$user				User making update
	 *	@param	int		$notrigger			1=disable trigger UPDATE (when called by create)
	 *	@param	int		$nosyncuser			0=Synchronize linked user (standard info), 1=Do not synchronize linked user
	 *	@param	int		$nosyncuserpass		0=Synchronize linked user (password), 1=Do not synchronize linked user
	 *	@param	int		$nosyncthirdparty	0=Synchronize linked thirdparty (standard info), 1=Do not synchronize linked thirdparty
	 * 	@param	string	$action				Current action for hookmanager
	 * 	@return	int							<0 if KO, >0 if OK
	 */
	function update($user,$notrigger=0,$nosyncuser=0,$nosyncuserpass=0,$nosyncthirdparty=0,$action='update')
	{
		global $conf, $langs, $hookmanager;

		$nbrowsaffected=0;
		$error=0;

		dol_syslog(get_class($this)."::update notrigger=".$notrigger.", nosyncuser=".$nosyncuser.", nosyncuserpass=".$nosyncuserpass." nosyncthirdparty=".$nosyncthirdparty.", email=".$this->email);

		// Clean parameters
		$this->lastname=trim($this->lastname)?trim($this->lastname):trim($this->lastname);
		$this->firstname=trim($this->firstname)?trim($this->firstname):trim($this->firstname);
		$this->address=($this->address?$this->address:$this->address);
		$this->zip=($this->zip?$this->zip:$this->zip);
		$this->town=($this->town?$this->town:$this->town);
		$this->country_id=($this->country_id > 0?$this->country_id:$this->country_id);
		$this->state_id=($this->state_id > 0?$this->state_id:$this->state_id);
		if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->lastname=ucwords(trim($this->lastname));
		if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->firstname=ucwords(trim($this->firstname));
		$this->note_public=($this->note_public?$this->note_public:$this->note_public);
		$this->note_private=($this->note_private?$this->note_private:$this->note_private);

		// Check parameters
		if (! empty($conf->global->ADHERENT_MAIL_REQUIRED) && ! isValidEMail($this->email))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " civility = ".($this->civility_id?"'".$this->db->escape($this->civility_id)."'":"null");
		$sql.= ", firstname = ".($this->firstname?"'".$this->db->escape($this->firstname)."'":"null");
		$sql.= ", lastname = ".($this->lastname?"'".$this->db->escape($this->lastname)."'":"null");
		$sql.= ", login = ".($this->login?"'".$this->db->escape($this->login)."'":"null");
		$sql.= ", societe = ".($this->societe?"'".$this->db->escape($this->societe)."'":"null");
		$sql.= ", fk_soc = ".($this->fk_soc > 0?$this->db->escape($this->fk_soc):"null");
		$sql.= ", address = ".($this->address?"'".$this->db->escape($this->address)."'":"null");
		$sql.= ", zip = ".($this->zip?"'".$this->db->escape($this->zip)."'":"null");
		$sql.= ", town = ".($this->town?"'".$this->db->escape($this->town)."'":"null");
		$sql.= ", country = ".($this->country_id>0?$this->db->escape($this->country_id):"null");
		$sql.= ", state_id = ".($this->state_id>0?$this->db->escape($this->state_id):"null");
		$sql.= ", email = '".$this->db->escape($this->email)."'";
		$sql.= ", skype = '".$this->db->escape($this->skype)."'";
		$sql.= ", twitter = '".$this->db->escape($this->twitter)."'";
		$sql.= ", facebook = '".$this->db->escape($this->facebook)."'";
		$sql.= ", phone = ".($this->phone?"'".$this->db->escape($this->phone)."'":"null");
		$sql.= ", phone_perso = ".($this->phone_perso?"'".$this->db->escape($this->phone_perso)."'":"null");
		$sql.= ", phone_mobile = ".($this->phone_mobile?"'".$this->db->escape($this->phone_mobile)."'":"null");
		$sql.= ", note_private = ".($this->note_private?"'".$this->db->escape($this->note_private)."'":"null");
		$sql.= ", note_public = ".($this->note_public?"'".$this->db->escape($this->note_public)."'":"null");
		$sql.= ", photo = ".($this->photo?"'".$this->db->escape($this->photo)."'":"null");
		$sql.= ", public = '".$this->db->escape($this->public)."'";
		$sql.= ", statut = ".$this->db->escape($this->statut);
		$sql.= ", fk_adherent_type = ".$this->db->escape($this->typeid);
		$sql.= ", morphy = '".$this->db->escape($this->morphy)."'";
		$sql.= ", birth = ".($this->birth?"'".$this->db->idate($this->birth)."'":"null");
		if ($this->datefin)   $sql.= ", datefin = '".$this->db->idate($this->datefin)."'";		// Must be modified only when deleting a subscription
		if ($this->datevalid) $sql.= ", datevalid = '".$this->db->idate($this->datevalid)."'";	// Must be modified only when validating a member
		$sql.= ", fk_user_mod = ".($user->id>0?$user->id:'null');	// Can be null because member can be create by a guest
		$sql.= " WHERE rowid = ".$this->id;

		// If we change the type of membership, we set also label of new type
		if (! empty($this->oldcopy) && $this->typeid != $this->oldcopy->typeid)
		{
			$sql2 = "SELECT libelle as label";
			$sql2.= " FROM ".MAIN_DB_PREFIX."adherent_type";
			$sql2.= " WHERE rowid = ".$this->typeid;
			$resql2 = $this->db->query($sql2);
			if ($resql2)
			{
			    while ($obj=$this->db->fetch_object($resql2))
			    {
				$this->type=$obj->label;
			    }
			}
		}

		dol_syslog(get_class($this)."::update update member", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			unset($this->country_code);
			unset($this->country);
			unset($this->state_code);
			unset($this->state);

			$nbrowsaffected+=$this->db->affected_rows($resql);

			$action='update';

			// Actions on extra fields
			if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			// Update password
			if (! $error && $this->pass)
			{
				dol_syslog(get_class($this)."::update update password");
				if ($this->pass != $this->pass_indatabase && $this->pass != $this->pass_indatabase_crypted)
				{
					$isencrypted = empty($conf->global->DATABASE_PWD_ENCRYPTED)?0:1;

					// If password to set differs from the one found into database
					$result=$this->setPassword($user,$this->pass,$isencrypted,$notrigger,$nosyncuserpass);
					if (! $nbrowsaffected) $nbrowsaffected++;
				}
			}

			// Remove links to user and replace with new one
			if (! $error)
			{
				dol_syslog(get_class($this)."::update update link to user");
				$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".$this->id;
				dol_syslog(get_class($this)."::update", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
				// If there is a user linked to this member
				if ($this->user_id > 0)
				{
					$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".$this->id." WHERE rowid = ".$this->user_id;
					dol_syslog(get_class($this)."::update", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
				}
			}

			if (! $error && $nbrowsaffected)	// If something has change in main data
			{
				// Update information on linked user if it is an update
				if (! $error && $this->user_id > 0 && ! $nosyncuser)
				{
					require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

					dol_syslog(get_class($this)."::update update linked user");

					$luser=new User($this->db);
					$result=$luser->fetch($this->user_id);

					if ($result >= 0)
					{
						//var_dump($this->user_login);exit;
						//var_dump($this->login);exit;

						// If option ADHERENT_LOGIN_NOT_REQUIRED is on, there is no login of member, so we do not overwrite user login to keep existing one.
						if (empty($conf->global->ADHERENT_LOGIN_NOT_REQUIRED)) $luser->login=$this->login;

						$luser->civility_id=$this->civility_id;
						$luser->firstname=$this->firstname;
						$luser->lastname=$this->lastname;
						$luser->pass=$this->pass;
						$luser->societe_id=$this->societe;

						$luser->birth=$this->birth;
                        $luser->address=$this->address;
                        $luser->zip=$this->zip;
                        $luser->town=$this->town;
                        $luser->country_id=$this->country_id;
                        $luser->state_id=$this->state_id;

						$luser->email=$this->email;
						$luser->skype=$this->skype;
						$luser->twitter=$this->twitter;
						$luser->facebook=$this->facebook;
						$luser->office_phone=$this->phone;
						$luser->user_mobile=$this->phone_mobile;

						$luser->fk_member=$this->id;

						$result=$luser->update($user,0,1,1);	// Use nosync to 1 to avoid cyclic updates
						if ($result < 0)
						{
							$this->error=$luser->error;
							dol_syslog(get_class($this)."::update ".$this->error,LOG_ERR);
							$error++;
						}
					}
					else
					{
						$this->error=$luser->error;
						$error++;
					}
				}

				// Update information on linked thirdparty if it is an update
				if (! $error && $this->fk_soc > 0 && ! $nosyncthirdparty)
				{
					require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

					dol_syslog(get_class($this)."::update update linked thirdparty");

					// This member is linked with a thirdparty, so we also update thirdparty informations
					// if this is an update.
					$lthirdparty=new Societe($this->db);
					$result=$lthirdparty->fetch($this->fk_soc);

					if ($result > 0)
					{
						$lthirdparty->address=$this->address;
						$lthirdparty->zip=$this->zip;
						$lthirdparty->town=$this->town;
						$lthirdparty->email=$this->email;
						$lthirdparty->skype=$this->skype;
						$lthirdparty->twitter=$this->twitter;
						$lthirdparty->facebook=$this->facebook;
						$lthirdparty->phone=$this->phone;
						$lthirdparty->state_id=$this->state_id;
						$lthirdparty->country_id=$this->country_id;
						//$lthirdparty->phone_mobile=$this->phone_mobile;

						$result=$lthirdparty->update($this->fk_soc, $user, 0, 1, 1, 'update');	// Use sync to 0 to avoid cyclic updates

						if ($result < 0)
						{
							$this->error=$lthirdparty->error;
							$this->errors=$lthirdparty->errors;
							dol_syslog(get_class($this)."::update ".$this->error,LOG_ERR);
							$error++;
						}
					}
					elseif ($result < 0)
					{
						$this->error=$lthirdparty->error;
						$error++;
					}
				}
			}

			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('MEMBER_MODIFY',$user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return $nbrowsaffected;
			}
			else
			{
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -2;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Update denormalized last subscription date.
	 * 	This function is called when we delete a subscription for example.
	 *
	 *	@param	User	$user			User making change
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function update_end_date($user)
	{
        // phpcs:enable
		$this->db->begin();

		// Search for last subscription id and end date
		$sql = "SELECT rowid, datec as dateop, dateadh as datedeb, datef as datefin";
		$sql.= " FROM ".MAIN_DB_PREFIX."subscription";
		$sql.= " WHERE fk_adherent=".$this->id;
		$sql.= " ORDER by dateadh DESC";	// Sort by start subscription date

		dol_syslog(get_class($this)."::update_end_date", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj=$this->db->fetch_object($resql);
			$dateop=$this->db->jdate($obj->dateop);
			$datedeb=$this->db->jdate($obj->datedeb);
			$datefin=$this->db->jdate($obj->datefin);

			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
			$sql.= " datefin=".($datefin != '' ? "'".$this->db->idate($datefin)."'" : "null");
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::update_end_date", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$this->last_subscription_date=$dateop;
				$this->last_subscription_date_start=$datedeb;
				$this->last_subscription_date_end=$datefin;
				$this->datefin=$datefin;
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
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Fonction qui supprime l'adherent et les donnees associees
	 *
	 *  @param	int		$rowid		Id of member to delete
	 *	@param	User		$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 *  @return	int					<0 if KO, 0=nothing to do, >0 if OK
	 */
	function delete($rowid, $user, $notrigger=0)
	{
		global $conf, $langs;

		$result = 0;
		$error=0;
		$errorflag=0;

		// Check parameters
		if (empty($rowid)) $rowid=$this->id;

		$this->db->begin();

		if (! $error && ! $notrigger)
		{
			// Call trigger
			$result=$this->call_trigger('MEMBER_DELETE',$user);
			if ($result < 0) $error++;
			// End call triggers
		}

		// Remove category
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_member WHERE fk_member = ".$rowid;
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (! $resql)
		{
			$error++;
			$this->error .= $this->db->lasterror();
			$errorflag=-1;
		}

		// Remove subscription
		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."subscription WHERE fk_adherent = ".$rowid;
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag=-2;
			}
		}

		// Remove linked user
		if (! $error)
		{
			$ret=$this->setUserId(0);
			if ($ret < 0)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag=-3;
			}
		}

		// Removed extrafields
		if (! $error)
		{
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result=$this->deleteExtraFields();
				if ($result < 0)
				{
					$error++;
					$errorflag=-4;
					dol_syslog(get_class($this)."::delete erreur ".$errorflag." ".$this->error, LOG_ERR);
				}
			}
		}

		// Remove adherent
		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent WHERE rowid = ".$rowid;
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql=$this->db->query($sql);
			if (! $resql)
			{
				$error++;
				$this->error .= $this->db->lasterror();
				$errorflag=-5;
			}
		}

		if (! $error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return $errorflag;
		}
	}


	/**
	 *    Change password of a user
	 *
	 *    @param	User	$user           Object user de l'utilisateur qui fait la modification
	 *    @param 	string	$password       New password (to generate if empty)
	 *    @param    int		$isencrypted    0 ou 1 si il faut crypter le mot de passe en base (0 par defaut)
	 *	  @param	int		$notrigger		1=Ne declenche pas les triggers
	 *    @param	int		$nosyncuser		Do not synchronize linked user
	 *    @return   string           		If OK return clear password, 0 if no change, < 0 if error
	 */
	function setPassword($user, $password='', $isencrypted=0, $notrigger=0, $nosyncuser=0)
	{
		global $conf, $langs;

		$error=0;

		dol_syslog(get_class($this)."::setPassword user=".$user->id." password=".preg_replace('/./i','*',$password)." isencrypted=".$isencrypted);

		// If new password not provided, we generate one
		if (! $password)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$password=getRandomPassword(false);
		}

		// Crypt password
		$password_crypted = dol_hash($password);

		$password_indatabase = '';
		if (! $isencrypted)
		{
			$password_indatabase = $password;
		}

		$this->db->begin();

		// Mise a jour
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent";
		$sql.= " SET pass_crypted = '".$this->db->escape($password_crypted)."'";
		//if (! empty($conf->global->DATABASE_PWD_ENCRYPTED))
		if ($isencrypted)
		{
			$sql.= ", pass = null";
		}
		else
		{
			$sql.= ", pass = '".$this->db->escape($password_indatabase)."'";
		}
		$sql.= " WHERE rowid = ".$this->id;

		//dol_syslog("Adherent::Password sql=hidden");
		dol_syslog(get_class($this)."::setPassword", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$nbaffectedrows=$this->db->affected_rows($result);

			if ($nbaffectedrows)
			{
				$this->pass=$password;
				$this->pass_indatabase=$password_indatabase;
				$this->pass_indatabase_crypted=$password_crypted;

				if ($this->user_id && ! $nosyncuser)
				{
					require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

					// This member is linked with a user, so we also update users informations
					// if this is an update.
					$luser=new User($this->db);
					$result=$luser->fetch($this->user_id);

					if ($result >= 0)
					{
						$result=$luser->setPassword($user,$this->pass,0,0,1);
						if ($result < 0)
						{
							$this->error=$luser->error;
							dol_syslog(get_class($this)."::setPassword ".$this->error,LOG_ERR);
							$error++;
						}
					}
					else
					{
						$this->error=$luser->error;
						$error++;
					}
				}

				if (! $error && ! $notrigger)
				{
					// Call trigger
					$result=$this->call_trigger('MEMBER_NEW_PASSWORD',$user);
					if ($result < 0) { $error++; $this->db->rollback(); return -1; }
					// End call triggers
				}

				$this->db->commit();
				return $this->pass;
			}
			else
			{
				$this->db->rollback();
				return 0;
			}
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *    Set link to a user
	 *
	 *    @param     int	$userid        	Id of user to link to
	 *    @return    int					1=OK, -1=KO
	 */
	function setUserId($userid)
	{
		global $conf, $langs;

		$this->db->begin();

		// If user is linked to this member, remove old link to this member
		$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".$this->id;
		dol_syslog(get_class($this)."::setUserId", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -1; }

		// Set link to user
		if ($userid > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".$this->id;
			$sql.= " WHERE rowid = ".$userid;
			dol_syslog(get_class($this)."::setUserId", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -2; }
		}

		$this->db->commit();

		return 1;
	}


	/**
	 *    Set link to a third party
	 *
	 *    @param     int	$thirdpartyid		Id of user to link to
	 *    @return    int						1=OK, -1=KO
	 */
	function setThirdPartyId($thirdpartyid)
	{
		global $conf, $langs;

		$this->db->begin();

		// Remove link to third party onto any other members
		if ($thirdpartyid > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = null";
			$sql.= " WHERE fk_soc = '".$thirdpartyid."'";
			$sql.= " AND entity = ".$conf->entity;
			dol_syslog(get_class($this)."::setThirdPartyId", LOG_DEBUG);
			$resql = $this->db->query($sql);
		}

		// Add link to third party for current member
		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = ".($thirdpartyid>0 ? $thirdpartyid : 'null');
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::setThirdPartyId", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Method to load member from its login
	 *
	 *	@param	string	$login		login of member
	 *	@return	void
	 */
	function fetch_login($login)
	{
        // phpcs:enable
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
		$sql.= " WHERE login='".$this->db->escape($login)."'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->fetch($obj->rowid);
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Method to load member from its name
	 *
	 *	@param	string	$firstname	Firstname
	 *	@param	string	$lastname	Lastname
	 *	@return	void
	 */
	function fetch_name($firstname,$lastname)
	{
        // phpcs:enable
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."adherent";
		$sql.= " WHERE firstname='".$this->db->escape($firstname)."'";
		$sql.= " AND lastname='".$this->db->escape($lastname)."'";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->fetch($obj->rowid);
			}
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Load member from database
	 *
	 *	@param	int		$rowid      			Id of object to load
	 * 	@param	string	$ref					To load member from its ref
	 * 	@param	int		$fk_soc					To load member from its link to third party
	 * 	@param	string	$ref_ext				External reference
	 *  @param	bool	$fetch_optionals		To load optionals (extrafields)
	 *  @param	bool	$fetch_subscriptions	To load member subscriptions
	 *	@return int								>0 if OK, 0 if not found, <0 if KO
	 */
	function fetch($rowid,$ref='',$fk_soc='',$ref_ext='',$fetch_optionals=true,$fetch_subscriptions=true)
	{
		global $langs;

		$sql = "SELECT d.rowid, d.ref_ext, d.civility as civility_id, d.firstname, d.lastname, d.societe as company, d.fk_soc, d.statut, d.public, d.address, d.zip, d.town, d.note_private,";
		$sql.= " d.note_public,";
		$sql.= " d.email, d.skype, d.twitter, d.facebook, d.phone, d.phone_perso, d.phone_mobile, d.login, d.pass, d.pass_crypted,";
		$sql.= " d.photo, d.fk_adherent_type, d.morphy, d.entity,";
		$sql.= " d.datec as datec,";
		$sql.= " d.tms as datem,";
		$sql.= " d.datefin as datefin,";
		$sql.= " d.birth as birthday,";
		$sql.= " d.datevalid as datev,";
		$sql.= " d.country,";
		$sql.= " d.state_id,";
		$sql.= " d.model_pdf,";
		$sql.= " c.rowid as country_id, c.code as country_code, c.label as country,";
		$sql.= " dep.nom as state, dep.code_departement as state_code,";
		$sql.= " t.libelle as type, t.subscription as subscription,";
		$sql.= " u.rowid as user_id, u.login as user_login";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON d.country = c.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as dep ON d.state_id = dep.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON d.rowid = u.fk_member";
		$sql.= " WHERE d.fk_adherent_type = t.rowid";
		if ($rowid) $sql.= " AND d.rowid=".$rowid;
		elseif ($ref || $fk_soc) {
			$sql.= " AND d.entity IN (".getEntity('adherent').")";
			if ($ref) $sql.= " AND d.rowid='".$this->db->escape($ref)."'";
			elseif ($fk_soc > 0) $sql.= " AND d.fk_soc=".$fk_soc;
		}
		elseif ($ref_ext)
		{
			$sql.= " AND d.ref_ext='".$this->db->escape($ref_ext)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->entity			= $obj->entity;
				$this->ref				= $obj->rowid;
				$this->id				= $obj->rowid;
				$this->ref_ext			= $obj->ref_ext;
				$this->civility_id		= $obj->civility_id;
				$this->firstname		= $obj->firstname;
				$this->lastname			= $obj->lastname;
				$this->login			= $obj->login;
				$this->societe			= $obj->company;
				$this->company			= $obj->company;
				$this->fk_soc			= $obj->fk_soc;
				$this->address			= $obj->address;
				$this->zip				= $obj->zip;
				$this->town				= $obj->town;

				$this->pass				= $obj->pass;
				$this->pass_indatabase  = $obj->pass;
				$this->pass_indatabase_crypted = $obj->pass_crypted;

				$this->state_id			= $obj->state_id;
				$this->state_code		= $obj->state_id?$obj->state_code:'';
				$this->state			= $obj->state_id?$obj->state:'';

				$this->country_id		= $obj->country_id;
				$this->country_code		= $obj->country_code;
				if ($langs->trans("Country".$obj->country_code) != "Country".$obj->country_code)
					$this->country = $langs->transnoentitiesnoconv("Country".$obj->country_code);
				else
					$this->country=$obj->country;

				$this->phone			= $obj->phone;
				$this->phone_perso		= $obj->phone_perso;
				$this->phone_mobile		= $obj->phone_mobile;
				$this->email			= $obj->email;

				$this->skype			= $obj->skype;
				$this->twitter			= $obj->twitter;
				$this->facebook			= $obj->facebook;

				$this->photo			= $obj->photo;
				$this->statut			= $obj->statut;
				$this->public			= $obj->public;

				$this->datec			= $this->db->jdate($obj->datec);
				$this->datem			= $this->db->jdate($obj->datem);
				$this->datefin			= $this->db->jdate($obj->datefin);
				$this->datevalid		= $this->db->jdate($obj->datev);
				$this->birth			= $this->db->jdate($obj->birthday);

				$this->note_private		= $obj->note_private;
				$this->note_public		= $obj->note_public;
				$this->morphy			= $obj->morphy;

				$this->typeid			= $obj->fk_adherent_type;
				$this->type				= $obj->type;
				$this->need_subscription 	= $obj->subscription;

				$this->user_id			= $obj->user_id;
				$this->user_login		= $obj->user_login;

				$this->model_pdf        = $obj->model_pdf;

				// Retreive all extrafield
				// fetch optionals attributes and labels
				if ($fetch_optionals) {
					$this->fetch_optionals();
				}

				// Load other properties
				if ($fetch_subscriptions) {
					$result=$this->fetch_subscriptions();
				}

				return $this->id;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Function to get member subscriptions data
	 *				first_subscription_date, first_subscription_date_start, first_subscription_date_end, first_subscription_amount
	 *				last_subscription_date, last_subscription_date_start, last_subscription_date_end, last_subscription_amount
	 *
	 *	@return		int			<0 si KO, >0 si OK
	 */
	function fetch_subscriptions()
	{
        // phpcs:enable
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

		$sql = "SELECT c.rowid, c.fk_adherent, c.subscription, c.note, c.fk_bank,";
		$sql.= " c.tms as datem,";
		$sql.= " c.datec as datec,";
		$sql.= " c.dateadh as dateh,";
		$sql.= " c.datef as datef";
		$sql.= " FROM ".MAIN_DB_PREFIX."subscription as c";
		$sql.= " WHERE c.fk_adherent = ".$this->id;
		$sql.= " ORDER BY c.dateadh";
		dol_syslog(get_class($this)."::fetch_subscriptions", LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->subscriptions=array();

			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				if ($i==0)
				{
					$this->first_subscription_date=$this->db->jdate($obj->datec);
					$this->first_subscription_date_start=$this->db->jdate($obj->dateh);
					$this->first_subscription_date_end=$this->db->jdate($obj->datef);
					$this->first_subscription_amount=$obj->subscription;
				}
				$this->last_subscription_date=$this->db->jdate($obj->datec);
				$this->last_subscription_date_start=$this->db->jdate($obj->datef);
				$this->last_subscription_date_end=$this->db->jdate($obj->datef);
				$this->last_subscription_amount=$obj->subscription;

				$subscription=new Subscription($this->db);
				$subscription->id=$obj->rowid;
				$subscription->fk_adherent=$obj->fk_adherent;
				$subscription->amount=$obj->subscription;
				$subscription->note=$obj->note;
				$subscription->fk_bank=$obj->fk_bank;
				$subscription->datem=$this->db->jdate($obj->datem);
				$subscription->datec=$this->db->jdate($obj->datec);
				$subscription->dateh=$this->db->jdate($obj->dateh);
				$subscription->datef=$this->db->jdate($obj->datef);

				$this->subscriptions[]=$subscription;

				$i++;
			}
			return 1;
		}
		else
		{
			$this->error=$this->db->error().' sql='.$sql;
			return -1;
		}
	}


	/**
	 *	Insert subscription into database and eventually add links to banks, mailman, etc...
	 *
	 *	@param	int	        $date        		Date of effect of subscription
	 *	@param	double		$amount     		Amount of subscription (0 accepted for some members)
	 *	@param	int			$accountid			Id bank account
	 *	@param	string		$operation			Type of payment (if Id bank account provided). Example: 'CB', ...
	 *	@param	string		$label				Label operation (if Id bank account provided)
	 *	@param	string		$num_chq			Numero cheque (if Id bank account provided)
	 *	@param	string		$emetteur_nom		Name of cheque writer
	 *	@param	string		$emetteur_banque	Name of bank of cheque
	 *	@param	int     	$datesubend			Date end subscription
	 *	@return int         					rowid of record added, <0 if KO
	 */
	function subscription($date, $amount, $accountid=0, $operation='', $label='', $num_chq='', $emetteur_nom='', $emetteur_banque='', $datesubend=0)
	{
		global $conf,$langs,$user;

		require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

		$error=0;

		// Clean parameters
		if (! $amount) $amount=0;

		$this->db->begin();

		if ($datesubend)
		{
			$datefin=$datesubend;
		}
		else
		{
			// If no end date, end date = date + 1 year - 1 day
			$datefin = dol_time_plus_duree($date,1,'y');
			$datefin = dol_time_plus_duree($datefin,-1,'d');
		}

		// Create subscription
		$subscription=new Subscription($this->db);
		$subscription->fk_adherent=$this->id;
		$subscription->dateh=$date;		// Date of new subscription
		$subscription->datef=$datefin;	// End data of new subscription
		$subscription->amount=$amount;
		$subscription->note=$label;		// deprecated
		$subscription->note_public=$label;

		$rowid=$subscription->create($user);
		if ($rowid > 0)
		{
			// Update denormalized subscription end date (read database subscription to find values)
			// This will also update this->datefin
			$result=$this->update_end_date($user);
			if ($result > 0)
			{
				// Change properties of object (used by triggers)
				$this->last_subscription_date=dol_now();
				$this->last_subscription_date_start=$date;
				$this->last_subscription_date_end=$datefin;
				$this->last_subscription_amount=$amount;
			}

			if (! $error)
			{
				$this->db->commit();
				return $rowid;
			}
			else
			{
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$subscription->error;
			$this->errors=$subscription->errors;
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Do complementary actions after subscription recording.
	 *
	 *	@param	int			$subscriptionid			Id of created subscription
	 *  @param	string		$option					Which action ('bankdirect', 'bankviainvoice', 'invoiceonly', ...)
	 *	@param	int			$accountid				Id bank account
	 *	@param	int			$datesubscription		Date of subscription
	 *	@param	int			$paymentdate			Date of payment
	 *	@param	string		$operation				Code of type of operation (if Id bank account provided). Example 'CB', ...
	 *	@param	string		$label					Label operation (if Id bank account provided)
	 *	@param	double		$amount     			Amount of subscription (0 accepted for some members)
	 *	@param	string		$num_chq				Numero cheque (if Id bank account provided)
	 *	@param	string		$emetteur_nom			Name of cheque writer
	 *	@param	string		$emetteur_banque		Name of bank of cheque
	 *  @param	string		$autocreatethirdparty	Auto create new thirdparty if member not yet linked to a thirdparty and we request an option that generate invoice.
	 *	@return int									<0 if KO, >0 if OK
	 */
	function subscriptionComplementaryActions($subscriptionid, $option, $accountid, $datesubscription, $paymentdate, $operation, $label, $amount, $num_chq, $emetteur_nom='', $emetteur_banque='', $autocreatethirdparty=0)
	{
		global $conf, $langs, $user, $mysoc;

		$error = 0;

		$this->invoice = null;	// This will contains invoice if an invoice is created

		dol_syslog("subscriptionComplementaryActions subscriptionid=".$subscriptionid." option=".$option." accountid=".$accountid." datesubscription=".$datesubscription." paymentdate=".$paymentdate." label=".$label." amount=".$amount." num_chq=".$num_chq." autocreatethirdparty=".$autocreatethirdparty);

		// Insert into bank account directlty (if option choosed for) + link to llx_subscription if option is 'bankdirect'
		if ($option == 'bankdirect' && $accountid)
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

			$acct=new Account($this->db);
			$result=$acct->fetch($accountid);

			$dateop=$paymentdate;

			$insertid=$acct->addline($dateop, $operation, $label, $amount, $num_chq, '', $user, $emetteur_nom, $emetteur_banque);
			if ($insertid > 0)
			{
				$inserturlid=$acct->add_url_line($insertid, $this->id, DOL_URL_ROOT.'/adherents/card.php?rowid=', $this->getFullname($langs), 'member');
				if ($inserturlid > 0)
				{
					// Update table subscription
					$sql ="UPDATE ".MAIN_DB_PREFIX."subscription SET fk_bank=".$insertid;
					$sql.=" WHERE rowid=".$subscriptionid;

					dol_syslog("subscription::subscription", LOG_DEBUG);
					$resql = $this->db->query($sql);
					if (! $resql)
					{
						$error++;
						$this->error=$this->db->lasterror();
						$this->errors[]=$this->error;
					}
				}
				else
				{
					$error++;
					$this->error=$acct->error;
					$this->errors=$acct->errors;
				}
			}
			else
			{
				$error++;
				$this->error=$acct->error;
				$this->errors=$acct->errors;
			}
		}

		// If option choosed, we create invoice
		if (($option == 'bankviainvoice' && $accountid) || $option == 'invoiceonly')
		{
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/paymentterm.class.php';

			$invoice=new Facture($this->db);
			$customer=new Societe($this->db);

			if (! $error)
			{
				if (! ($this->fk_soc > 0))	// If not yet linked to a company
				{
					if ($autocreatethirdparty)
					{
						// Create a linked thirdparty to member
						$companyalias='';
						$fullname = $this->getFullName($langs);

						if ($this->morphy == 'mor')
						{
							$companyname=$this->societe;
							if (! empty($fullname)) $companyalias=$fullname;
						}
						else
						{
							$companyname=$fullname;
							if (! empty($this->societe)) $companyalias=$this->societe;
						}

						$result=$customer->create_from_member($this, $companyname, $companyalias);
						if ($result < 0)
						{
							$this->error = $customer->error;
							$this->errors = $customer->errors;
							$error++;
						}
						else
						{
							$this->fk_soc = $result;
						}
					}
					else
					{
						 $langs->load("errors");
						 $this->error=$langs->trans("ErrorMemberNotLinkedToAThirpartyLinkOrCreateFirst");
						 $this->errors[]=$this->error;
						 $error++;
					}
				}
			}
			if (! $error)
			{
				$result=$customer->fetch($this->fk_soc);
				if ($result <= 0)
				{
					$this->error=$customer->error;
					$this->errors=$customer->errors;
					$error++;
				}
			}

			if (! $error)
			{
				// Create draft invoice
				$invoice->type=Facture::TYPE_STANDARD;
				$invoice->cond_reglement_id=$customer->cond_reglement_id;
				if (empty($invoice->cond_reglement_id))
				{
					$paymenttermstatic=new PaymentTerm($this->db);
					$invoice->cond_reglement_id=$paymenttermstatic->getDefaultId();
					if (empty($invoice->cond_reglement_id))
					{
						$error++;
						$this->error='ErrorNoPaymentTermRECEPFound';
						$this->errors[]=$this->error;
					}
				}
				$invoice->socid=$this->fk_soc;
				$invoice->date=$datesubscription;

				// Possibility to add external linked objects with hooks
				$invoice->linked_objects['subscription'] = $subscriptionid;
				if (! empty($_POST['other_linked_objects']) && is_array($_POST['other_linked_objects']))
				{
					$invoice->linked_objects = array_merge($invoice->linked_objects, $_POST['other_linked_objects']);
				}

				$result=$invoice->create($user);
				if ($result <= 0)
				{
					$this->error=$invoice->error;
					$this->errors=$invoice->errors;
					$error++;
				}
				else
				{
					$this->invoice = $invoice;
				}
			}

			if (! $error)
			{
				// Add line to draft invoice
				$idprodsubscription=0;
				if (! empty($conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled))) $idprodsubscription = $conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS;

				$vattouse=0;
				if (isset($conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS) && $conf->global->ADHERENT_VAT_FOR_SUBSCRIPTIONS == 'defaultforfoundationcountry')
				{
					$vattouse=get_default_tva($mysoc, $mysoc, $idprodsubscription);
				}
				//print xx".$vattouse." - ".$mysoc." - ".$customer;exit;
				$result=$invoice->addline($label,0,1,$vattouse,0,0,$idprodsubscription,0,$datesubscription,'',0,0,'','TTC',$amount,1);
				if ($result <= 0)
				{
					$this->error=$invoice->error;
					$this->errors=$invoice->errors;
					$error++;
				}
			}

			if (! $error)
			{
				// Validate invoice
				$result=$invoice->validate($user);
				if ($result <= 0)
				{
					$this->error=$invoice->error;
					$this->errors=$invoice->errors;
					$error++;
				}
			}

			if (! $error)
			{
				// TODO Link invoice with subscription ?
			}

			// Add payment onto invoice
			if (! $error && $option == 'bankviainvoice' && $accountid)
			{
				require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
				require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

				$amounts = array();
				$amounts[$invoice->id] = price2num($amount);

				$paiement = new Paiement($this->db);
				$paiement->datepaye     = $paymentdate;
				$paiement->amounts      = $amounts;
				$paiement->paiementid   = dol_getIdFromCode($this->db,$operation,'c_paiement','code','id',1);
				$paiement->num_paiement = $num_chq;
				$paiement->note         = $label;
				$paiement->note_public  = $label;

				if (! $error)
				{
					// Create payment line for invoice
					$paiement_id = $paiement->create($user);
					if (! $paiement_id > 0)
					{
						$this->error=$paiement->error;
						$this->errors=$paiement->errors;
						$error++;
					}
				}

				if (! $error)
				{
					// Add transaction into bank account
					$bank_line_id=$paiement->addPaymentToBank($user,'payment','(SubscriptionPayment)',$accountid,$emetteur_nom,$emetteur_banque);
					if (! ($bank_line_id > 0))
					{
						$this->error=$paiement->error;
						$this->errors=$paiement->errors;
						$error++;
					}
				}

				if (! $error && !empty($bank_line_id))
				{
					// Update fk_bank into subscription table
					$sql = 'UPDATE '.MAIN_DB_PREFIX.'subscription SET fk_bank='.$bank_line_id;
					$sql.= ' WHERE rowid='.$subscriptionid;

					$result = $this->db->query($sql);
					if (! $result)
					{
						$error++;
					}
				}

				if (! $error)
				{
					// Set invoice as paid
					$invoice->set_paid($user);
				}
			}

			if (! $error)
			{
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				$lang_id=GETPOST('lang_id');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($lang_id))
					$newlang = $lang_id;
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))
					$newlang = $customer->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				// Generate PDF (whatever is option MAIN_DISABLE_PDF_AUTOUPDATE) so we can include it into email
				//if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))

				$invoice->generateDocument($invoice->modelpdf, $outputlangs);
			}
		}

		if ($error)
		{
			return -1;
		}
		else
		{
			return 1;
		}
	}


	/**
	 *		Function that validate a member
	 *
	 *		@param	User	$user		user adherent qui valide
	 *		@return	int					<0 if KO, 0 if nothing done, >0 if OK
	 */
	function validate($user)
	{
		global $langs,$conf;

		$error=0;
		$now=dol_now();

		// Check parameters
		if ($this->statut == 1)
		{
			dol_syslog(get_class($this)."::validate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " statut = 1";
		$sql.= ", datevalid = '".$this->db->idate($now)."'";
		$sql.= ", fk_user_valid=".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->statut=1;

			// Call trigger
			$result=$this->call_trigger('MEMBER_VALIDATE',$user);
			if ($result < 0) { $error++; $this->db->rollback(); return -1; }
			// End call triggers

			$this->datevalid = $now;

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *		Fonction qui resilie un adherent
	 *
	 *		@param	User	$user		User making change
	 *		@return	int					<0 if KO, >0 if OK
	 */
	function resiliate($user)
	{
		global $langs,$conf;

		$error=0;

		// Check paramaters
		if ($this->statut == 0)
		{
			dol_syslog(get_class($this)."::resiliate statut of member does not allow this", LOG_WARNING);
			return 0;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
		$sql.= " statut = 0";
		$sql.= ", fk_user_valid=".$user->id;
		$sql.= " WHERE rowid = ".$this->id;

		$result = $this->db->query($sql);
		if ($result)
		{
			$this->statut=0;

			// Call trigger
			$result=$this->call_trigger('MEMBER_RESILIATE',$user);
			if ($result < 0) { $error++; $this->db->rollback(); return -1; }
			// End call triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			$this->db->rollback();
			return -1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Function to add member into external tools mailing-list, spip, etc.
	 *
	 *  @return		int		<0 if KO, >0 if OK
	 */
	function add_to_abo()
	{
        // phpcs:enable
		global $conf,$langs;

		include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
		$mailmanspip=new MailmanSpip($this->db);

		$err=0;

		// mailman
		if (! empty($conf->global->ADHERENT_USE_MAILMAN) && ! empty($conf->mailmanspip->enabled))
		{
			$result=$mailmanspip->add_to_mailman($this);

			if ($result < 0)
			{
				if (! empty($mailmanspip->error)) $this->errors[]=$mailmanspip->error;
				$err+=1;
			}
			foreach ($mailmanspip->mladded_ko as $tmplist => $tmpemail)
			{
				$langs->load("errors");
				$this->errors[]=$langs->trans("ErrorFailedToAddToMailmanList",$tmpemail,$tmplist);
			}
			foreach ($mailmanspip->mladded_ok as $tmplist => $tmpemail)
			{
				$langs->load("mailmanspip");
				$this->mesgs[]=$langs->trans("SuccessToAddToMailmanList",$tmpemail,$tmplist);
			}
		}

		// spip
		if (! empty($conf->global->ADHERENT_USE_SPIP) && ! empty($conf->mailmanspip->enabled))
		{
			$result=$mailmanspip->add_to_spip($this);
			if ($result < 0)
			{
				$this->errors[]=$mailmanspip->error;
				$err+=1;
			}
		}
		if ($err)
		{
			return -$err;
		}
		else
		{
			return 1;
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Function to delete a member from external tools like mailing-list, spip, etc.
	 *
	 *  @return     int     <0 if KO, >0 if OK
	 */
	function del_to_abo()
	{
        // phpcs:enable
		global $conf,$langs;

		include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
		$mailmanspip=new MailmanSpip($this->db);

		$err=0;

		// mailman
		if (! empty($conf->global->ADHERENT_USE_MAILMAN))
		{
			$result=$mailmanspip->del_to_mailman($this);
			if ($result < 0)
			{
				if (! empty($mailmanspip->error)) $this->errors[]=$mailmanspip->error;
				$err+=1;
			}

			foreach ($mailmanspip->mlremoved_ko as $tmplist => $tmpemail)
			{
				$langs->load("errors");
				$this->errors[]=$langs->trans("ErrorFailedToRemoveToMailmanList",$tmpemail,$tmplist);
			}
			foreach ($mailmanspip->mlremoved_ok as $tmplist => $tmpemail)
			{
				$langs->load("mailmanspip");
				$this->mesgs[]=$langs->trans("SuccessToRemoveToMailmanList",$tmpemail,$tmplist);
			}
		}

		if ($conf->global->ADHERENT_USE_SPIP && ! empty($conf->mailmanspip->enabled))
		{
			$result=$mailmanspip->del_to_spip($this);
			if ($result < 0)
			{
				$this->errors[]=$mailmanspip->error;
				$err+=1;
			}
		}
		if ($err)
		{
			// error
			return -$err;
		}
		else
		{
			return 1;
		}
	}


	/**
	 *    Return civility label of a member
	 *
	 *    @return   string              	Translated name of civility (translated with transnoentitiesnoconv)
	 */
	function getCivilityLabel()
	{
		global $langs;
		$langs->load("dict");

		$code=(empty($this->civility_id)?'':$this->civility_id);
		if (empty($code)) return '';
		return $langs->getLabelFromKey($this->db, "Civility".$code, "c_civility", "code", "label", $code);
	}

	/**
	 *  Return clicable name (with picto eventually)
	 *
	 *	@param	int		$withpictoimg				0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param	int		$maxlen						length max label
	 *	@param	string	$option						Page for link ('card', 'category', 'subscription', ...)
	 *  @param  string  $mode           			''=Show firstname+lastname as label (using default order), 'firstname'=Show only firstname, 'login'=Show login, 'ref'=Show ref
	 *  @param  string  $morecss        			Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								Chaine avec URL
	 */
	function getNomUrl($withpictoimg=0, $maxlen=0, $option='card', $mode='', $morecss='', $save_lastsearch_value=-1)
	{
		global $conf, $langs;

		if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg=0;

		$notooltip=0;

		$result=''; $label='';
		$link=''; $linkstart=''; $linkend='';

		if (! empty($this->photo))
		{
			$label.= '<div class="photointooltip">';
			$label.= Form::showphoto('memberphoto', $this, 80, 0, 0, 'photowithmargin photologintooltip', 'small', 0, 1);
			$label.= '</div><div style="clear: both;"></div>';
		}

		$label.= '<div class="centpercent">';
		$label.= '<u>' . $langs->trans("Member") . '</u>';
		if (! empty($this->ref))
			$label.= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
		if (! empty($this->firstname) || ! empty($this->lastname))
			$label.= '<br><b>' . $langs->trans('Name') . ':</b> ' . $this->getFullName($langs);
		if (! empty($this->societe))
			$label.= '<br><b>' . $langs->trans('Company') . ':</b> ' . $this->societe;
		$label.='</div>';

		$url = DOL_URL_ROOT.'/adherents/card.php?rowid='.$this->id;
		if ($option == 'subscription')
		{
			$url = DOL_URL_ROOT.'/adherents/subscription.php?rowid='.$this->id;
		}

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
			if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
		}

		$link = '<a href="'.$url.'"';
		$linkclose="";
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$langs->load("users");
				$label=$langs->trans("ShowUser");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.= ' class="classfortooltip'.($morecss?' '.$morecss:'').'"';
		}

		$link.=$linkclose.'>';
		$linkend='</a>';

		//if ($withpictoimg == -1) $result.='<div class="nowrap">';
		$result.=$link;
		if ($withpictoimg)
		{
			$paddafterimage='';
			if (abs($withpictoimg) == 1) $paddafterimage='style="margin-right: 3px;"';
			// Only picto
			if ($withpictoimg > 0) $picto='<div class="inline-block nopadding valignmiddle'.($morecss?' userimg'.$morecss:'').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).'</div>';
			// Picto must be a photo
			else $picto='<div class="inline-block nopadding valignmiddle'.($morecss?' userimg'.$morecss:'').'"'.($paddafterimage?' '.$paddafterimage:'').'>'.Form::showphoto('memberphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg==-3?'small':''), 'mini', 0, 1).'</div>';
			$result.=$picto;
		}
		if ($withpictoimg > -2 && $withpictoimg != 2)
		{
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result.='<div class="inline-block nopadding valignmiddle'.((! isset($this->statut) || $this->statut)?'':' strikefordisabled').($morecss?' usertext'.$morecss:'').'">';
			if ($mode == 'login') $result.=dol_trunc($this->login, $maxlen);
			elseif ($mode == 'ref') $result.=$this->id;
			else $result.=$this->getFullName($langs,'',($mode == 'firstname' ? 2 : -1),$maxlen);
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result.='</div>';
		}
		$result.=$linkend;
		//if ($withpictoimg == -1) $result.='</div>';

		return $result;
	}

	/**
	 *  Retourne le libelle du statut d'un adherent (brouillon, valide, resilie)
	 *
	 *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string				Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$this->need_subscription,$this->datefin,$mode);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int			$statut      			Id statut
	 *	@param	int			$need_subscription		1 if member type need subscription, 0 otherwise
	 *	@param	int     	$date_end_subscription	Date fin adhesion
	 *  @param  int		    $mode                   0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string      						Label
	 */
	function LibStatut($statut,$need_subscription,$date_end_subscription,$mode=0)
	{
        // phpcs:enable
		global $langs;
		$langs->load("members");
		if ($mode == 0)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraft");
			elseif ($statut >= 1) {
				if (! $date_end_subscription)            return $langs->trans("MemberStatusActive");
				elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate");
				else                                     return $langs->trans("MemberStatusPaid");
			}
			elseif ($statut == 0)  return $langs->trans("MemberStatusResiliated");
		}
		elseif ($mode == 1)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraftShort");
			elseif ($statut >= 1) {
				if (! $date_end_subscription)            return $langs->trans("MemberStatusActiveShort");
				elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLateShort");
				else                                     return $langs->trans("MemberStatusPaidShort");
			}
			elseif ($statut == 0)  return $langs->trans("MemberStatusResiliatedShort");
		}
		elseif ($mode == 2)
		{
			if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraftShort");
			elseif ($statut >= 1) {
				if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActiveShort");
				elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLateShort");
				else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4').' '.$langs->trans("MemberStatusPaidShort");
			}
			elseif ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliatedShort");
		}
		elseif ($mode == 3)
		{
			if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0');
			elseif ($statut >= 1) {
				if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1');
				elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
				else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4');
			}
			elseif ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
		elseif ($mode == 4)
		{
			if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraft");
			elseif ($statut >= 1) {
				if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActive");
				elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLate");
				else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4').' '.$langs->trans("MemberStatusPaid");
			}
			if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliated");
		}
		elseif ($mode == 5)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraftShort").' '.img_picto($langs->trans('MemberStatusDraft'), 'statut0');
			elseif ($statut >= 1) {
				if (! $date_end_subscription)            return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusActiveShort").' </span>'.img_picto($langs->trans('MemberStatusActive'),'statut1');
				elseif ($date_end_subscription < time()) return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusActiveLateShort").' </span>'.img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
				else                                     return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusPaidShort").' </span>'.img_picto($langs->trans('MemberStatusPaid'),'statut4');
			}
			if ($statut == 0)  return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusResiliated").' </span>'.img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
		elseif ($mode == 6)
		{
			if ($statut == -1) return $langs->trans("MemberStatusDraft").' '.img_picto($langs->trans('MemberStatusDraft'),'statut0');
			if ($statut >= 1) {
				if (! $date_end_subscription)            return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusActive").' </span>'.img_picto($langs->trans('MemberStatusActive'),'statut1');
				elseif ($date_end_subscription < time()) return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusActiveLate").' </span>'.img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
				else                                     return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusPaid").' </span>'.img_picto($langs->trans('MemberStatusPaid'),'statut4');
			}
			if ($statut == 0)  return '<span class="hideonsmartphone">'.$langs->trans("MemberStatusResiliated").' </span>'.img_picto($langs->trans('MemberStatusResiliated'),'statut5');
		}
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Charge indicateurs this->nb de tableau de bord
	 *
	 *      @return     int         <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
        // phpcs:enable
		global $conf;

		$this->nb = array();

		$sql = "SELECT count(a.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql.= " WHERE a.statut > 0";
		$sql.= " AND a.entity IN (".getEntity('adherent').")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["members"]=$obj->nb;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Objet user
	 *      @return WorkboardResponse|int 	<0 if KO, WorkboardResponse if OK
	 */
	function load_board($user)
	{
        // phpcs:enable
		global $conf, $langs;

		if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

		$now=dol_now();

		$sql = "SELECT a.rowid, a.datefin, a.statut";
		$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
		$sql.= " WHERE a.statut = 1";
		$sql.= " AND a.entity IN (".getEntity('adherent').")";
		$sql.= " AND (a.datefin IS NULL or a.datefin < '".$this->db->idate($now)."')";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$langs->load("members");

			$response = new WorkboardResponse();
			$response->warning_delay=$conf->adherent->subscription->warning_delay/60/60/24;
			$response->label=$langs->trans("MembersWithSubscriptionToReceive");
			$response->url=DOL_URL_ROOT.'/adherents/list.php?mainmenu=members&amp;statut=1&amp;filter=outofdate';
			$response->img=img_object('',"user");

			$adherentstatic = new Adherent($this->db);

			while ($obj=$this->db->fetch_object($resql))
			{
				$response->nbtodo++;

				$adherentstatic->datefin = $this->db->jdate($obj->datefin);
				$adherentstatic->statut = $obj->statut;

				if ($adherentstatic->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}


	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
         *  @param   null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $moreparams=null)
	{
		global $conf,$langs;

		$langs->load("orders");

		if (! dol_strlen($modele)) {

			$modele = 'standard';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->ADHERENT_ADDON_PDF)) {
				$modele = $conf->global->ADHERENT_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/member/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
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
		global $user,$langs;

		// Initialise parametres
		$this->id=0;
		$this->specimen=1;
		$this->civility_id = 0;
		$this->lastname = 'DOLIBARR';
		$this->firstname = 'SPECIMEN';
		$this->login='dolibspec';
		$this->pass='dolibspec';
		$this->societe = 'Societe ABC';
		$this->address = '61 jump street';
		$this->zip = '75000';
		$this->town = 'Paris';
		$this->country_id = 1;
		$this->country_code = 'FR';
		$this->country = 'France';
		$this->morphy = 1;
		$this->email = 'specimen@specimen.com';
		$this->skype = 'skypepseudo';
		$this->twitter = 'twitterpseudo';
		$this->facebook = 'facebookpseudo';
		$this->phone        = '0999999999';
		$this->phone_perso  = '0999999998';
		$this->phone_mobile = '0999999997';
		$this->note_private='No comment';
		$this->birth=time();
		$this->photo='';
		$this->public=1;
		$this->statut=0;

		$this->datefin=time();
		$this->datevalid=time();

		$this->typeid=1;				// Id type adherent
		$this->type='Type adherent';	// Libelle type adherent
		$this->need_subscription=0;

		$this->first_subscription_date=time();
		$this->first_subscription_date_start=$this->first_subscription_date;
		$this->first_subscription_date_end=dol_time_plus_duree($this->first_subscription_date_start, 1, 'y');
		$this->first_subscription_amount=10;

		$this->last_subscription_date=$this->first_subscription_date;
		$this->last_subscription_date_start=$this->first_subscription_date;
		$this->last_subscription_date_end=dol_time_plus_duree($this->last_subscription_date_start, 1, 'y');
		$this->last_subscription_amount=10;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param	array	$info		Info array loaded by _load_ldap_info
	 *	@param	int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *								1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *								2=Return key only (uid=qqq)
	 *	@return	string				DN
	 */
	function _load_ldap_dn($info,$mode=0)
	{
        // phpcs:enable
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_MEMBERS."=".$info[$conf->global->LDAP_KEY_MEMBERS].",".$conf->global->LDAP_MEMBER_DN;
		if ($mode==1) $dn=$conf->global->LDAP_MEMBER_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_MEMBERS."=".$info[$conf->global->LDAP_KEY_MEMBERS];
		return $dn;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Initialise tableau info (tableau des attributs LDAP)
	 *
	 *	@return		array		Tableau info des attributs
	 */
	function _load_ldap_info()
	{
        // phpcs:enable
		global $conf,$langs;

		$info=array();
		$keymodified=false;

		// Object classes
		$info["objectclass"]=explode(',',$conf->global->LDAP_MEMBER_OBJECT_CLASS);

		$this->fullname=$this->getFullName($langs);

		// For avoid ldap error when firstname and lastname are empty
		if ($this->morphy == 'mor' && (empty($this->fullname) || $this->fullname == $this->societe)) {
			$this->fullname = $this->societe;
			$this->lastname = $this->societe;
		}

		// Possible LDAP KEY (constname => varname)
		$ldapkey = array(
			'LDAP_MEMBER_FIELD_FULLNAME'		=> 'fullname',
			'LDAP_MEMBER_FIELD_NAME'			=> 'lastname',
			'LDAP_MEMBER_FIELD_LOGIN'		=> 'login',
			'LDAP_MEMBER_FIELD_LOGIN_SAMBA'	=> 'login',
			'LDAP_MEMBER_FIELD_MAIL'			=> 'email'
		);

		// Member
		foreach ($ldapkey as $constname => $varname)
		{
			if (! empty($this->$varname) && ! empty($conf->global->$constname))
			{
				$info[$conf->global->$constname] = $this->$varname;

				// Check if it is the LDAP key and if its value has been changed
				if (! empty($conf->global->LDAP_KEY_MEMBERS) && $conf->global->LDAP_KEY_MEMBERS == $conf->global->$constname)
				{
					if (! empty($this->oldcopy) && $this->$varname != $this->oldcopy->$varname) $keymodified=true; // For check if LDAP key has been modified
				}
			}
		}
		if ($this->firstname && ! empty($conf->global->LDAP_MEMBER_FIELD_FIRSTNAME))			$info[$conf->global->LDAP_MEMBER_FIELD_FIRSTNAME] = $this->firstname;
		if ($this->poste && ! empty($conf->global->LDAP_MEMBER_FIELD_TITLE))					$info[$conf->global->LDAP_MEMBER_FIELD_TITLE] = $this->poste;
		if ($this->societe && ! empty($conf->global->LDAP_MEMBER_FIELD_COMPANY))				$info[$conf->global->LDAP_MEMBER_FIELD_COMPANY] = $this->societe;
		if ($this->address && ! empty($conf->global->LDAP_MEMBER_FIELD_ADDRESS))				$info[$conf->global->LDAP_MEMBER_FIELD_ADDRESS] = $this->address;
		if ($this->zip && ! empty($conf->global->LDAP_MEMBER_FIELD_ZIP))						$info[$conf->global->LDAP_MEMBER_FIELD_ZIP] = $this->zip;
		if ($this->town && ! empty($conf->global->LDAP_MEMBER_FIELD_TOWN))						$info[$conf->global->LDAP_MEMBER_FIELD_TOWN] = $this->town;
		if ($this->country_code && ! empty($conf->global->LDAP_MEMBER_FIELD_COUNTRY))			$info[$conf->global->LDAP_MEMBER_FIELD_COUNTRY] = $this->country_code;
		if ($this->skype && ! empty($conf->global->LDAP_MEMBER_FIELD_SKYPE))					$info[$conf->global->LDAP_MEMBER_FIELD_SKYPE] = $this->skype;
		if ($this->twitter && ! empty($conf->global->LDAP_MEMBER_FIELD_TWITTER))				$info[$conf->global->LDAP_MEMBER_FIELD_TWITTER] = $this->twitter;
		if ($this->facebook && ! empty($conf->global->LDAP_MEMBER_FIELD_FACEBOOK))				$info[$conf->global->LDAP_MEMBER_FIELD_FACEBOOK] = $this->facebook;
		if ($this->phone && ! empty($conf->global->LDAP_MEMBER_FIELD_PHONE))					$info[$conf->global->LDAP_MEMBER_FIELD_PHONE] = $this->phone;
		if ($this->phone_perso && ! empty($conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO))		$info[$conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO] = $this->phone_perso;
		if ($this->phone_mobile && ! empty($conf->global->LDAP_MEMBER_FIELD_MOBILE))			$info[$conf->global->LDAP_MEMBER_FIELD_MOBILE] = $this->phone_mobile;
		if ($this->fax && ! empty($conf->global->LDAP_MEMBER_FIELD_FAX))						$info[$conf->global->LDAP_MEMBER_FIELD_FAX] = $this->fax;
		if ($this->note_private && ! empty($conf->global->LDAP_MEMBER_FIELD_DESCRIPTION))		$info[$conf->global->LDAP_MEMBER_FIELD_DESCRIPTION] = dol_string_nohtmltag($this->note_private, 2);
		if ($this->note_public && ! empty($conf->global->LDAP_MEMBER_FIELD_NOTE_PUBLIC))		$info[$conf->global->LDAP_MEMBER_FIELD_NOTE_PUBLIC] = dol_string_nohtmltag($this->note_public, 2);
		if ($this->birth && ! empty($conf->global->LDAP_MEMBER_FIELD_BIRTHDATE))				$info[$conf->global->LDAP_MEMBER_FIELD_BIRTHDATE] = dol_print_date($this->birth,'dayhourldap');
		if (isset($this->statut) && ! empty($conf->global->LDAP_FIELD_MEMBER_STATUS))			$info[$conf->global->LDAP_FIELD_MEMBER_STATUS] = $this->statut;
		if ($this->datefin && ! empty($conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION))	$info[$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION] = dol_print_date($this->datefin,'dayhourldap');

		// When password is modified
		if (! empty($this->pass))
		{
			if (! empty($conf->global->LDAP_MEMBER_FIELD_PASSWORD))				$info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD] = $this->pass;	// this->pass = mot de passe non crypte
			if (! empty($conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED))		$info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED] = dol_hash($this->pass, 4); // Create OpenLDAP MD5 password (TODO add type of encryption)
		}
		// Set LDAP password if possible
		else if ($conf->global->LDAP_SERVER_PROTOCOLVERSION !== '3') // If ldap key is modified and LDAPv3 we use ldap_rename function for avoid lose encrypt password
		{
			if (! empty($conf->global->DATABASE_PWD_ENCRYPTED))
			{
				// Just for the default MD5 !
				if (empty($conf->global->MAIN_SECURITY_HASH_ALGO))
				{
					if ($this->pass_indatabase_crypted && ! empty($conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED))	{
						// Create OpenLDAP MD5 password from Dolibarr MD5 password
						// Note: This suppose that "pass_indatabase_crypted" is a md5 (guaranted by the previous test if "(empty($conf->global->MAIN_SECURITY_HASH_ALGO))"
						$info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED] = '{md5}'.base64_encode(hex2bin($this->pass_indatabase_crypted));
					}
				}
			}
			// Use $this->pass_indatabase value if exists
			else if (! empty($this->pass_indatabase))
			{
				if (! empty($conf->global->LDAP_MEMBER_FIELD_PASSWORD))				$info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD] = $this->pass_indatabase;	// $this->pass_indatabase = mot de passe non crypte
				if (! empty($conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED))		$info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD_CRYPTED] = dol_hash($this->pass_indatabase, 4); // md5 for OpenLdap TODO add type of encryption
			}
		}

		// Subscriptions
		if ($this->first_subscription_date && ! empty($conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE))     $info[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]  = dol_print_date($this->first_subscription_date,'dayhourldap');
		if (isset($this->first_subscription_amount) && ! empty($conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT)) $info[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT] = $this->first_subscription_amount;
		if ($this->last_subscription_date && ! empty($conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE))       $info[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE]   = dol_print_date($this->last_subscription_date,'dayhourldap');
		if (isset($this->last_subscription_amount) && ! empty($conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT))   $info[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT] = $this->last_subscription_amount;

		return $info;
	}


	/**
	 *      Charge les informations d'ordre info dans l'objet adherent
	 *
	 *      @param  int		$id       Id of member to load
	 *      @return	void
	 */
	function info($id)
	{
		$sql = 'SELECT a.rowid, a.datec as datec,';
		$sql.= ' a.datevalid as datev,';
		$sql.= ' a.tms as datem,';
		$sql.= ' a.fk_user_author, a.fk_user_valid, a.fk_user_mod';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'adherent as a';
		$sql.= ' WHERE a.rowid = '.$id;

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result=$this->db->query($sql);
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
					$this->user_creation   = $cuser;
				}

				if ($obj->fk_user_valid)
				{
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_mod)
				{
					$muser = new User($this->db);
					$muser->fetch($obj->fk_user_mod);
					$this->user_modification = $muser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_validation   = $this->db->jdate($obj->datev);
				$this->date_modification = $this->db->jdate($obj->datem);
			}

			$this->db->free($result);
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *  Return number of mass Emailing received by this member with its email
	 *
	 *  @return       int     Number of EMailings
	 */
	function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
		$sql.= " WHERE mc.email = '".$this->db->escape($this->email)."'";
		$sql.= " AND mc.statut NOT IN (-1,0)";      // -1 erreur, 0 non envoye, 1 envoye avec succes

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
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, Categorie::TYPE_MEMBER, 'id');

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
				$c->del_type($this, 'member');
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, 'member');
			}
		}

		return;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB 	$db 			Database handler
	 * @param int 		$origin_id 		Old thirdparty id
	 * @param int 		$dest_id 		New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty($db, $origin_id, $dest_id)
	{
		$tables = array(
			'adherent'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

	/**
	 * Return if a member is late (subscription late) or not
	 *
	 * @return boolean     True if late, False if not late
	 */
	public function hasDelay()
	{
		global $conf;

		//Only valid members
		if ($this->statut <= 0) return false;
		if (! $this->datefin) return false;

		$now = dol_now();

		return $this->datefin < ($now - $conf->adherent->subscription->warning_delay);
	}



	/**
	 * Send reminders by emails before subscription end
	 * CAN BE A CRON TASK
	 *
	 * @param	string		$daysbeforeendlist		Nb of days before end of subscription (negative number = after subscription). Can be a list of delay, separated by a semicolon, for example '10;5;0;-5'
	 * @return	int									0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function sendReminderForExpiredSubscription($daysbeforeendlist='10')
	{
		global $conf, $langs, $mysoc, $user;

		$error = 0;
		$this->output = '';
		$this->error='';

		$blockingerrormsg = '';

		if (empty($conf->adherent->enabled))	// Should not happen. If module disabled, cron job should not be visible.
		{
			$langs->load("agenda");
			$this->output = $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv("Adherent"));
			return 0;
		}
		if (empty($conf->global->MEMBER_REMINDER_EMAIL))
		{
			$langs->load("agenda");
			$this->output = $langs->trans('EventRemindersByEmailNotEnabled', $langs->transnoentitiesnoconv("Adherent"));
			return 0;
		}

		$now = dol_now();
		$nbok = 0;
		$nbko = 0;

		$arraydaysbeforeend=explode(';',$daysbeforeendlist);
		foreach($arraydaysbeforeend as $daysbeforeend)			// Loop on each delay
		{
			dol_syslog(__METHOD__.' - Process delta = '.$daysbeforeend, LOG_DEBUG);

			if (! is_numeric($daysbeforeend))
			{
				$blockingerrormsg="Value for delta is not a positive or negative numeric";
				$nbko++;
				break;
			}

			$tmp=dol_getdate($now);
			$datetosearchfor = dol_time_plus_duree(dol_mktime(0, 0, 0, $tmp['mon'], $tmp['mday'], $tmp['year']), $daysbeforeend, 'd');

			$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'adherent';
			$sql.= " WHERE datefin = '".$this->db->idate($datetosearchfor)."'";

			$resql = $this->db->query($sql);
			if ($resql)
			{
				$num_rows = $this->db->num_rows($resql);

				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$adherent = new Adherent($this->db);
				$formmail = new FormMail($this->db);

				$i=0;
				while ($i < $num_rows)
				{
					$obj = $this->db->fetch_object($resql);

					$adherent->fetch($obj->rowid, '', '', '', true, true);

					if (empty($adherent->email))
					{
						$nbko++;
					}
					else
					{
						$adherent->fetch_thirdparty();

						// Language code to use ($languagecodeformember) is default language of thirdparty, if no thirdparty, the language found from country of member then country of thirdparty, and if still not found we use the language of company.
						$languagefromcountrycode = getLanguageCodeFromCountryCode($adherent->country_code ? $adherent->country_code : $adherent->thirdparty->country_code);
						$languagecodeformember = (empty($adherent->thirdparty->default_lang) ? ($languagefromcountrycode ? $languagefromcountrycode : $mysoc->default_lang) : $adherent->thirdparty->default_lang);

						// Send reminder email
						$outputlangs = new Translate('', $conf);
						$outputlangs->setDefaultLang($languagecodeformember);
						$outputlangs->loadLangs(array("main", "members"));
						dol_syslog("sendReminderForExpiredSubscription Language for member id ".$adherent->id." set to ".$outputlangs->defaultlang." mysoc->default_lang=".$mysoc->default_lang);

						$arraydefaultmessage=null;
						$labeltouse = $conf->global->ADHERENT_EMAIL_TEMPLATE_REMIND_EXPIRATION;

						if (! empty($labeltouse)) $arraydefaultmessage=$formmail->getEMailTemplate($this->db, 'member', $user, $outputlangs, 0, 1, $labeltouse);

						if (! empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0)
						{
							$substitutionarray=getCommonSubstitutionArray($outputlangs, 0, null, $adherent);
							//if (is_array($adherent->thirdparty)) $substitutionarraycomp = ...
							complete_substitutions_array($substitutionarray, $outputlangs, $adherent);

							$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $outputlangs);
							$msg     = make_substitutions($arraydefaultmessage->content, $substitutionarray, $outputlangs);
							$from = $conf->global->ADHERENT_MAIL_FROM;
							$to = $adherent->email;

							$trackid = 'mem'.$adherent->id;
							$moreinheader='X-Dolibarr-Info: sendReminderForExpiredSubscription'."\r\n";

							include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
							$cmail = new CMailFile($subject, $to, $from, $msg, array(), array(), array(), '', '', 0, 1, '', '', $trackid, $moreinheader);
							$result = $cmail->sendfile();
							if (! $result)
							{
								$error++;
								$this->error = $cmail->error;
								$this->errors += $cmail->errors;
								$nbko++;
							}
							else
							{
								$nbok++;

								$message = $msg;
								$sendto = $to;
								$sendtocc = '';
								$sendtobcc = '';
								$actioncode='EMAIL';
								$extraparams='';

								$actionmsg='';
								$actionmsg2=$langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($from,4,0,1).' '.$langs->transnoentities('To').' '.CMailFile::getValidAddress($sendto,4,0,1);
								if ($message)
								{
									$actionmsg=$langs->transnoentities('MailFrom').': '.dol_escape_htmltag($from);
									$actionmsg=dol_concatdesc($actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($sendto));
									if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . dol_escape_htmltag($sendtocc));
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
									$actionmsg = dol_concatdesc($actionmsg, $message);
								}

								require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

	    						// Insert record of emails sent
	    						$actioncomm = new ActionComm($this->db);

	    						$actioncomm->type_code   = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
	    						$actioncomm->code        = 'AC_'.$actioncode;
	    						$actioncomm->label       = $actionmsg2;
	    						$actioncomm->note        = $actionmsg;
	    						$actioncomm->fk_project  = 0;
	    						$actioncomm->datep       = $now;
	    						$actioncomm->datef       = $now;
	    						$actioncomm->percentage  = -1;   // Not applicable
	    						$actioncomm->socid       = $adherent->thirdparty->id;
	    						$actioncomm->contactid   = 0;
	    						$actioncomm->authorid    = $user->id;   // User saving action
	    						$actioncomm->userownerid = $user->id;	// Owner of action
	    						// Fields when action is en email (content should be added into note)
	    						$actioncomm->email_msgid = $cmail->msgid;
	    						$actioncomm->email_from  = $from;
	    						$actioncomm->email_sender= '';
	    						$actioncomm->email_to    = $to;
	    						$actioncomm->email_tocc  = $sendtocc;
	    						$actioncomm->email_tobcc = $sendtobcc;
	    						$actioncomm->email_subject = $subject;
	    						$actioncomm->errors_to   = '';

	    						$actioncomm->fk_element  = $adherent->id;
	    						$actioncomm->elementtype = $adherent->element;

	    						$actioncomm->extraparams = $extraparams;

	    						$actioncomm->create($user);
							}
						}
						else
						{
							$blockingerrormsg="Can't find email template, defined into member module setup, to use for reminding";
							$nbko++;
							break;
						}
					}

					$i++;
				}
			}
			else
			{
				$this->error = $this->db->lasterror();
				return 1;
			}
		}

		if ($blockingerrormsg)
		{
			$this->error = $blockingerrormsg;
			return 1;
		}
		else
		{
			$this->output = 'Found '.($nbok + $nbko).' members to send reminder to.';
			$this->output.= ' Send email successfuly to '.$nbok.' members';
			if ($nbko) $this->output.= ' - Canceled for '.$nbko.' member (no email or email sending error)';
		}

		return 0;
	}
}
