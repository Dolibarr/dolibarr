<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/cotisation.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");


/**
 *      \class      Adherent
 *		\brief      Class to manage members of a foundation
 */
class Adherent extends CommonObject
{
    public $element='member';
    public $table_element='adherent';
    protected $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;
    var $ref;
    var $civilite_id;
    var $firstname;
    var $prenom;                // deprecated
    var $lastname;
    var $nom;                   // deprecated
    var $login;
    var $pass;
    var $societe;
    var $adresse;
    var $address;
    var $cp;
    var $zip;
    var $ville;
    var $town;

    var $state_id;              // Id of department
    var $state_code;            // Code of department
    var $state;                 // Label of department
    var $fk_departement;		// deprecated
    var $departement_code;		// deprecated
    var $departement;			// deprecated

    var $country_id;
    var $country_code;
    var $country;
    var $pays_id;              // deprecated
    var $pays_code;            // deprecated
    var $pays;                 // deprecated

    var $email;
    var $phone;
    var $phone_perso;
    var $phone_mobile;

    var $morphy;
    var $public;
    var $note;				// Private note
    var $statut;			// -1:brouillon, 0:resilie, >=1:valide,paye
    var $photo;

    var $datec;
    var $datem;
    var $datefin;
    var $datevalid;
    var $naiss;

    var $typeid;			// Id type adherent
    var $type;				// Libelle type adherent
    var $need_subscription;

    var $user_id;
    var $user_login;

    var $fk_soc;

    // Fields loaded by fetch_subscriptions()
    var $first_subscription_date;
    var $first_subscription_amount;
    var $last_subscription_date;
    var $last_subscription_amount;
    var $subscriptions=array();

    //  var $public;
    var $array_options;

    var $oldcopy;		// To contains a clone of this when we need to save old properties of object


    /**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
     */
    function Adherent($db)
    {
        $this->db = $db;
        $this->statut = -1;
        // l'adherent n'est pas public par defaut
        $this->public = 0;
        // les champs optionnels sont vides
        $this->array_options=array();
    }


    /**
     *  Fonction envoyant un email a l'adherent avec le texte fourni en parametre.
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
     *  @return	int							<0 if KO, >0 if OK
     */
    function send_an_email($text, $subject, $filename_list=array(), $mimetype_list=array(), $mimefilename_list=array(), $addr_cc="", $addr_bcc="", $deliveryreceipt=0, $msgishtml=-1, $errors_to='')
    {
        global $conf,$langs;

        // Detect if message is HTML
        if ($msgishtml == -1)
        {
            $msgishtml = 0;
            if (dol_textishtml($text,1)) $msgishtml = 1;
        }

        $texttosend=$this->makeSubstitution($text);
        $subjecttosend=$this->makeSubstitution($subject);
        if ($msgishtml) $texttosend=dol_htmlentitiesbr($texttosend);

        // Envoi mail confirmation
        $from=$conf->email_from;
        if ($conf->global->ADHERENT_MAIL_FROM) $from=$conf->global->ADHERENT_MAIL_FROM;

        include_once(DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");
        $mailfile = new CMailFile($subjecttosend, $this->email, $from, $texttosend, $filename_list, $mimetype_list, $mimefilename_list, $addr_cc, $addr_bcc, $deliveryreceipt, $msgishtml);
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
     * Make substitution
     *
     * @param	string	$text       Text to make substitution to
     * @return  string      		Value of input text string with substitutions done
     */
	function makeSubstitution($text)
	{
		global $conf,$langs;

		$birthday = dol_print_date($this->naiss,'day');

		$msgishtml = 0;
		if (dol_textishtml($text,1)) $msgishtml = 1;

		$infos='';
		if ($this->civilite_id) $infos.= $langs->transnoentities("UserTitle").": ".$this->getCivilityLabel(1)."\n";
		$infos.= $langs->transnoentities("id").": ".$this->id."\n";
		$infos.= $langs->transnoentities("Lastname").": ".$this->lastname."\n";
		$infos.= $langs->transnoentities("Firstname").": ".$this->firstname."\n";
		$infos.= $langs->transnoentities("Company").": ".$this->societe."\n";
		$infos.= $langs->transnoentities("Address").": ".$this->address."\n";
		$infos.= $langs->transnoentities("Zip").": ".$this->zip."\n";
		$infos.= $langs->transnoentities("Town").": ".$this->town."\n";
		$infos.= $langs->transnoentities("Country").": ".$this->country."\n";
		$infos.= $langs->transnoentities("EMail").": ".$this->email."\n";
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
				'%DOL_MAIN_URL_ROOT%'=>DOL_MAIN_URL_ROOT,
				'%ID%'=>$msgishtml?dol_htmlentitiesbr($this->id):$this->id,
				'%CIVILITE%'=>$this->getCivilityLabel($msgishtml?0:1),
				'%FIRSTNAME%'=>$msgishtml?dol_htmlentitiesbr($this->firstname):$this->firstname,
				'%LASTNAME%'=>$msgishtml?dol_htmlentitiesbr($this->lastname):$this->lastname,
				'%FULLNAME%'=>$msgishtml?dol_htmlentitiesbr($this->getFullName($langs)):$this->getFullName($langs),
				'%COMPANY%'=>$msgishtml?dol_htmlentitiesbr($this->societe):$this->societe,
				'%ADDRESS%'=>$msgishtml?dol_htmlentitiesbr($this->address):$this->address,
				'%ZIP%'=>$msgishtml?dol_htmlentitiesbr($this->zip):$this->zip,
				'%TOWN%'=>$msgishtml?dol_htmlentitiesbr($this->town):$this->town,
				'%COUNTRY%'=>$msgishtml?dol_htmlentitiesbr($this->country):$this->country,
				'%EMAIL%'=>$msgishtml?dol_htmlentitiesbr($this->email):$this->email,
				'%NAISS%'=>$msgishtml?dol_htmlentitiesbr($birthday):$birthday,
				'%PHOTO%'=>$msgishtml?dol_htmlentitiesbr($this->photo):$this->photo,
				'%LOGIN%'=>$msgishtml?dol_htmlentitiesbr($this->login):$this->login,
				'%PASSWORD%'=>$msgishtml?dol_htmlentitiesbr($this->pass):$this->pass,
				// For backward compatibility
				'%INFOS%'=>$msgishtml?dol_htmlentitiesbr($infos):$infos,
				'%PRENOM%'=>$msgishtml?dol_htmlentitiesbr($this->firstname):$this->firstname,
				'%NOM%'=>$msgishtml?dol_htmlentitiesbr($this->lastname):$this->lastname,
				'%SOCIETE%'=>$msgishtml?dol_htmlentitiesbr($this->societe):$this->societe,
				'%ADRESSE%'=>$msgishtml?dol_htmlentitiesbr($this->address):$this->address,
				'%CP%'=>$msgishtml?dol_htmlentitiesbr($this->zip):$this->zip,
				'%VILLE%'=>$msgishtml?dol_htmlentitiesbr($this->town):$this->town,
				'%PAYS%'=>$msgishtml?dol_htmlentitiesbr($this->country):$this->country,
		);

		complete_substitutions_array($substitutionarray, $langs);

		return make_substitutions($text,$substitutionarray);
	}


    /**
     *	Renvoie le libelle traduit de la nature d'un adherent (physique ou morale)
     *
     *	@param	string		$morphy		Nature physique ou morale de l'adherent
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
        $sql.= " (datec,login,fk_user_author,fk_user_mod,fk_user_valid,morphy,fk_adherent_type,entity)";
        $sql.= " VALUES (";
        $sql.= " '".$this->db->idate($this->datec)."'";
        $sql.= ", ".($this->login?"'".$this->db->escape($this->login)."'":"null");
        $sql.= ", ".($user->id>0?$user->id:"null");	// Can be null because member can be createb by a guest or a script
        $sql.= ", null, null, '".$this->morphy."'";
        $sql.= ", '".$this->typeid."'";
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::create sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX."adherent");
            if ($id > 0)
            {
                $this->id=$id;
                $this->ref=$id;

                // Update minor fields
                $result=$this->update($user,1,1); // nosync is 1 to avoid update data of user
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
                    $sql.= " fk_member = '".$this->id."'";
                    $sql.= " WHERE rowid = ".$this->user_id;
                    dol_syslog(get_class($this)."::create sql=".$sql);
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
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('MEMBER_CREATE',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }

                if (count($this->errors))
                {
                    dol_syslog(get_class($this)."::create ".join(',',$this->errors), LOG_ERR);
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
            dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
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
     * 	@return	int							<0 if KO, >0 if OK
     */
    function update($user,$notrigger=0,$nosyncuser=0,$nosyncuserpass=0,$nosyncthirdparty=0)
    {
        global $conf, $langs;

        $nbrowsaffected=0;
        $error=0;

        dol_syslog(get_class($this)."::update notrigger=".$notrigger.", nosyncuser=".$nosyncuser.", nosyncuserpass=".$nosyncuserpass.", email=".$this->email);

        // Clean parameters
		$this->lastname=trim($this->lastname)?trim($this->lastname):trim($this->nom);
		$this->firstname=trim($this->firstname)?trim($this->firstname):trim($this->prenom);
		$this->address=($this->address?$this->address:$this->adresse);
		$this->zip=($this->zip?$this->zip:$this->cp);
		$this->town=($this->town?$this->town:$this->ville);
		$this->country_id=($this->country_id > 0?$this->country_id:$this->fk_pays);
		$this->state_id=($this->state_id > 0?$this->state_id:$this->fk_departement);
		if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->lastname=ucwords(trim($this->lastname));
        if (! empty($conf->global->MAIN_FIRST_TO_UPPER)) $this->firstname=ucwords(trim($this->firstname));

        // Check parameters
        if (! empty($conf->global->ADHERENT_MAIL_REQUIRED) && ! isValidEMail($this->email))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEMail",$this->email);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET";
        $sql.= " civilite = ".($this->civilite_id?"'".$this->civilite_id."'":"null");
        $sql.= ", prenom = ".($this->firstname?"'".$this->db->escape($this->firstname)."'":"null");
        $sql.= ", nom="     .($this->lastname?"'".$this->db->escape($this->lastname)."'":"null");
        $sql.= ", login="   .($this->login?"'".$this->db->escape($this->login)."'":"null");
        $sql.= ", societe=" .($this->societe?"'".$this->db->escape($this->societe)."'":"null");
        $sql.= ", fk_soc="  .($this->fk_soc > 0?"'".$this->fk_soc."'":"null");
        $sql.= ", adresse=" .($this->address?"'".$this->db->escape($this->address)."'":"null");
        $sql.= ", cp="      .($this->zip?"'".$this->db->escape($this->zip)."'":"null");
        $sql.= ", ville="   .($this->town?"'".$this->db->escape($this->town)."'":"null");
        $sql.= ", pays="          .($this->country_id>0?"'".$this->country_id."'":"null");
        $sql.= ", fk_departement=".($this->state_id>0?"'".$this->state_id."'":"null");
        $sql.= ", email='".$this->email."'";
        $sql.= ", phone="   .($this->phone?"'".$this->db->escape($this->phone)."'":"null");
        $sql.= ", phone_perso="  .($this->phone_perso?"'".$this->db->escape($this->phone_perso)."'":"null");
        $sql.= ", phone_mobile=" .($this->phone_mobile?"'".$this->db->escape($this->phone_mobile)."'":"null");
        $sql.= ", note="    .($this->note?"'".$this->db->escape($this->note)."'":"null");
        $sql.= ", photo="   .($this->photo?"'".$this->photo."'":"null");
        $sql.= ", public='".$this->public."'";
        $sql.= ", statut="  .$this->statut;
        $sql.= ", fk_adherent_type=".$this->typeid;
        $sql.= ", morphy='".$this->morphy."'";
        $sql.= ", naiss="   .($this->naiss?"'".$this->db->idate($this->naiss)."'":"null");
        if ($this->datefin)   $sql.= ", datefin='".$this->db->idate($this->datefin)."'";		// Ne doit etre modifie que par effacement cotisation
        if ($this->datevalid) $sql.= ", datevalid='".$this->db->idate($this->datevalid)."'";	// Ne doit etre modifie que par validation adherent
        $sql.= ", fk_user_mod=".($user->id>0?$user->id:'null');	// Can be null because member can be create by a guest
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::update update member sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
		    unset($this->country_code);
		    unset($this->country);
		    unset($this->state_code);
		    unset($this->state);

		    $nbrowsaffected+=$this->db->affected_rows($resql);

            // Actions on extra fields (by external module)
            include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
            $hookmanager=new HookManager($this->db);
            $hookmanager->initHooks(array('memberdao'));
            $parameters=array('id'=>$this->id);
            $action='';
            $reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
            if (empty($reshook))
            {
                $result=$this->insertExtraFields();
                if ($result < 0)
                {
                    $error++;
                }
            }
            else if ($reshook < 0) $error++;

            // Update password
            if (! $error && $this->pass)
            {
                dol_syslog(get_class($this)."::update update password");
                if ($this->pass != $this->pass_indatabase && $this->pass != $this->pass_indatabase_crypted)
                {
                    // Si mot de passe saisi et different de celui en base
                    $result=$this->setPassword($user,$this->pass,0,$notrigger,$nosyncuserpass);
                    if (! $nbrowsaffected) $nbrowsaffected++;
                }
            }

            // Remove links to user and replace with new one
            if (! $error)
            {
                dol_syslog(get_class($this)."::update update link to user");
                $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL WHERE fk_member = ".$this->id;
                dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
                // If there is a user linked to this member
                if ($this->user_id > 0)
                {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".$this->id." WHERE rowid = ".$this->user_id;
                    dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
                    $resql = $this->db->query($sql);
                    if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
                }
            }

            if (! $error && $nbrowsaffected)	// If something has change in main data
            {
                // Update information on linked user if it is an update
                if ($this->user_id > 0 && ! $nosyncuser)
                {
                    require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");

                    dol_syslog(get_class($this)."::update update linked user");

                    $luser=new User($this->db);
                    $result=$luser->fetch($this->user_id);

                    if ($result >= 0)
                    {
                        $luser->civilite_id=$this->civilite_id;
                        $luser->firstname=$this->firstname;
                        $luser->lastname=$this->lastname;
                        $luser->prenom=$this->firstname;    // deprecated
                        $luser->nom=$this->lastname;        // deprecated
                        $luser->login=$this->user_login;
                        $luser->pass=$this->pass;
                        $luser->societe_id=$this->societe;

                        $luser->email=$this->email;
                        $luser->office_phone=$this->phone;
                        $luser->user_mobile=$this->phone_mobile;

                        $luser->note=$this->note;

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
                if ($this->fk_soc > 0 && ! $nosyncthirdparty)
                {
                    require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

                    dol_syslog(get_class($this)."::update update linked thirdparty");

                    // This member is linked with a thirdparty, so we also update thirdparty informations
                    // if this is an update.
                    $lthirdparty=new Societe($this->db);
                    $result=$lthirdparty->fetch($this->fk_soc);

                    if ($result >= 0)
                    {
                        $lthirdparty->address=$this->address;
                        $lthirdparty->zip=$this->zip;
                        $lthirdparty->town=$this->town;
                        $lthirdparty->email=$this->email;
                        $lthirdparty->tel=$this->phone;
                        $lthirdparty->state_id=$this->state_id;
                        $lthirdparty->country_id=$this->country_id;
                        $lthirdparty->pays_id=$this->country_id;
                        //$lthirdparty->phone_mobile=$this->phone_mobile;

                        $result=$lthirdparty->update($this->fk_soc,$user,0,1,1,'update');	// Use sync to 0 to avoid cyclic updates
                        if ($result < 0)
                        {
                            $this->error=$lthirdparty->error;
                            dol_syslog(get_class($this)."::update ".$this->error,LOG_ERR);
                            $error++;
                        }
                    }
                    else
                    {
                        $this->error=$lthirdparty->error;
                        $error++;
                    }
                }

                if (! $error && ! $notrigger)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('MEMBER_MODIFY',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }
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
            dol_syslog(get_class($this)."::Update ".$this->error,LOG_ERR);
            return -2;
        }
    }


    /**
     *	Update denormalized last subscription date.
     * 	This function is called when we delete a subscription for example.
     *
     *	@param	User	$user			User making change
     *	@return	int						<0 if KO, >0 if OK
     */
    function update_end_date($user)
    {
        global $conf, $langs;

        $error=0;

        $this->db->begin();

        // Search for last subscription id and end date
        $sql = "SELECT rowid, datec as dateop, dateadh as datedeb, datef as datefin";
        $sql.= " FROM ".MAIN_DB_PREFIX."cotisation";
        $sql.= " WHERE fk_adherent='".$this->id."'";
        $sql.= " ORDER by dateadh DESC";	// Sort by start subscription date

        dol_syslog(get_class($this)."::update_end_date sql=".$sql);
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

            dol_syslog(get_class($this)."::update_end_date sql=".$sql);
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
            dol_syslog(get_class($this)."::update_end_date ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }

    }

    /**
     *  Fonction qui supprime l'adherent et les donnees associees
     *
     *  @param	int		$rowid		Id of member to delete
     *  @return	int					<0 if KO, 0=nothing to do, >0 if OK
     */
    function delete($rowid)
    {
        global $conf, $langs, $user;

        $result = 0;
		$error=0;

        $this->db->begin();

        // Suppression options
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent_extrafields WHERE fk_object = ".$rowid;

        dol_syslog(get_class($this)."::delete sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_member WHERE fk_member = ".$rowid;
            dol_syslog(get_class($this)."::delete sql=".$sql);
            $resql=$this->db->query($sql);

            $sql = "DELETE FROM ".MAIN_DB_PREFIX."cotisation WHERE fk_adherent = ".$rowid;
            dol_syslog(get_class($this)."::delete sql=".$sql);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                $sql = "DELETE FROM ".MAIN_DB_PREFIX."adherent WHERE rowid = ".$rowid;
                dol_syslog(get_class($this)."::delete sql=".$sql);
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    if ($this->db->affected_rows($resql))
                    {
                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($this->db);
                        $result=$interface->run_triggers('MEMBER_DELETE',$this,$user,$langs,$conf);
                        if ($result < 0) { $error++; $this->errors=$interface->errors; }
                        // Fin appel triggers

                        $this->db->commit();
                        return 1;
                    }
                    else
                    {
                        // Rien a effacer
                        $this->db->rollback();
                        return 0;
                    }
                }
                else
                {
                    $this->error=$this->db->error();
                    $this->db->rollback();
                    return -3;
                }
            }
            else
            {
                $this->error=$this->db->error();
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
            require_once(DOL_DOCUMENT_ROOT."/core/lib/security2.lib.php");
            $password=getRandomPassword('');
        }

        // Cryptage mot de passe
        if ($isencrypted)
        {
            // Encryption
            $password_indatabase = dol_hash($password);
        }
        else
        {
            $password_indatabase = $password;
        }

        // Mise a jour
        $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET pass = '".$this->db->escape($password_indatabase)."'";
        $sql.= " WHERE rowid = ".$this->id;

        //dol_syslog("Adherent::Password sql=hidden");
        dol_syslog(get_class($this)."::setPassword sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $nbaffectedrows=$this->db->affected_rows($result);

            if ($nbaffectedrows)
            {
                $this->pass=$password;
                $this->pass_indatabase=$password_indatabase;

                if ($this->user_id && ! $nosyncuser)
                {
                    require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");

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
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('MEMBER_NEW_PASSWORD',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }

                return $this->pass;
            }
            else
            {
                return 0;
            }
        }
        else
        {
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
        dol_syslog(get_class($this)."::setUserId sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -1; }

        // Set link to user
        if ($userid > 0)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = ".$this->id;
            $sql.= " WHERE rowid = ".$userid;
            dol_syslog(get_class($this)."::setUserId sql=".$sql, LOG_DEBUG);
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

        // Update link to third party
        if ($thirdpartyid > 0)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = null";
            $sql.= " WHERE fk_soc = '".$thirdpartyid."'";
            $sql.= " AND entity = ".$conf->entity;
            dol_syslog(get_class($this)."::setThirdPartyId sql=".$sql);
            $resql = $this->db->query($sql);
        }

        // Update link to third party
        $sql = "UPDATE ".MAIN_DB_PREFIX."adherent SET fk_soc = ".($thirdpartyid>0 ? $thirdpartyid : 'null');
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::setThirdPartyId sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::setThirdPartyId ".$this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *	Method to load member from its login
     *
     *	@param	string	$login		login of member
     *	@return	void
     */
    function fetch_login($login)
    {
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


    /**
     *	Load member from database
     *
     *	@param	int		$rowid      Id of object to load
     * 	@param	string	$ref		To load member from its ref
     * 	@param	int		$fk_soc		To load member from its link to third party
     *	@return int         		>0 if OK, 0 if not found, <0 if KO
     */
    function fetch($rowid,$ref='',$fk_soc='')
    {
        global $langs;

        $sql = "SELECT d.rowid, d.civilite, d.prenom as firstname, d.nom as lastname, d.societe, d.fk_soc, d.statut, d.public, d.adresse as address, d.cp as zip, d.ville as town, d.note,";
        $sql.= " d.email, d.phone, d.phone_perso, d.phone_mobile, d.login, d.pass,";
        $sql.= " d.photo, d.fk_adherent_type, d.morphy,";
        $sql.= " d.datec as datec,";
        $sql.= " d.tms as datem,";
        $sql.= " d.datefin as datefin,";
        $sql.= " d.naiss as datenaiss,";
        $sql.= " d.datevalid as datev,";
        $sql.= " d.pays,";
        $sql.= " d.fk_departement,";
        $sql.= " p.rowid as country_id, p.code as country_code, p.libelle as country,";
        $sql.= " dep.nom as state, dep.code_departement as state_code,";
        $sql.= " t.libelle as type, t.cotisation as cotisation,";
        $sql.= " u.rowid as user_id, u.login as user_login";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as dep ON d.fk_departement = dep.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON d.rowid = u.fk_member";
        $sql.= " WHERE d.fk_adherent_type = t.rowid";
        if ($rowid) $sql.= " AND d.rowid=".$rowid;
        elseif ($ref || $fk_soc) {
        	$sql.= " AND d.entity IN (".getEntity().")";
        	if ($ref) $sql.= " AND d.rowid='".$ref."'";
        	elseif ($fk_soc) $sql.= " AND d.fk_soc='".$fk_soc."'";
        }

        dol_syslog(get_class($this)."::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->ref            = $obj->rowid;
                $this->id             = $obj->rowid;
                $this->civilite_id    = $obj->civilite;
                $this->prenom         = $obj->firstname;   // deprecated
                $this->firstname      = $obj->firstname;
                $this->nom            = $obj->lastname;    // deprecated
                $this->lastname       = $obj->lastname;
                $this->login          = $obj->login;
                $this->pass           = $obj->pass;
                $this->societe        = $obj->societe;
                $this->fk_soc         = $obj->fk_soc;
                $this->adresse        = $obj->address;	// deprecated
                $this->address        = $obj->address;
                $this->cp             = $obj->zip;		// deprecated
                $this->zip            = $obj->zip;
                $this->ville          = $obj->town;	    // deprecated
                $this->town           = $obj->town;

                $this->state_id       = $obj->fk_departement;
                $this->state_code     = $obj->fk_departement?$obj->state_code:'';
                $this->state          = $obj->fk_departement?$obj->state:'';
                $this->fk_departement   = $obj->fk_departement;                        // deprecated
                $this->departement_code = $obj->fk_departement?$obj->state_code:'';    // deprecated
                $this->departement	    = $obj->fk_departement?$obj->state:'';         // deprecated

                $this->country_id     = $obj->country_id;
                $this->country_code   = $obj->country_code;
                if ($langs->trans("Country".$obj->country_code) != "Country".$obj->country_code) $this->country = $langs->transnoentitiesnoconv("Country".$obj->country_code);
                else $this->country=$obj->country;
                $this->pays_id        = $obj->country_id;      // deprecated
                $this->pays_code      = $obj->country_code;    // deprecated
                $this->pays           = $this->country;        // deprecated

                $this->phone          = $obj->phone;
                $this->phone_perso    = $obj->phone_perso;
                $this->phone_mobile   = $obj->phone_mobile;
                $this->email          = $obj->email;

                $this->photo          = $obj->photo;
                $this->statut         = $obj->statut;
                $this->public         = $obj->public;

                $this->datec          = $this->db->jdate($obj->datec);
                $this->datem          = $this->db->jdate($obj->datem);
                $this->datefin        = $this->db->jdate($obj->datefin);
                $this->datevalid      = $this->db->jdate($obj->datev);
                $this->naiss          = $this->db->jdate($obj->datenaiss);

                $this->note           = $obj->note;
                $this->morphy         = $obj->morphy;

                $this->typeid         = $obj->fk_adherent_type;
                $this->type           = $obj->type;
                $this->need_subscription = ($obj->cotisation=='yes'?1:0);

                $this->user_id        = $obj->user_id;
                $this->user_login     = $obj->user_login;

                // Load other properties
                $result=$this->fetch_subscriptions();

                return $result;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *	Fonction qui recupere pour un adherent les parametres
     *				first_subscription_date
     *				first_subscription_amount
     *				last_subscription_date
     *				last_subscription_amount
     *
     *	@return		int			<0 si KO, >0 si OK
     */
    function fetch_subscriptions()
    {
        global $langs;

        $sql = "SELECT c.rowid, c.fk_adherent, c.cotisation, c.note, c.fk_bank,";
        $sql.= " c.tms as datem,";
        $sql.= " c.datec as datec,";
        $sql.= " c.dateadh as dateadh,";
        $sql.= " c.datef as datef";
        $sql.= " FROM ".MAIN_DB_PREFIX."cotisation as c";
        $sql.= " WHERE c.fk_adherent = ".$this->id;
        $sql.= " ORDER BY c.dateadh";
        dol_syslog(get_class($this)."::fetch_subscriptions sql=".$sql);

        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->subscriptions=array();

            $i=0;
            while ($obj = $this->db->fetch_object($resql))
            {
                if ($i==0)
                {
                    $this->first_subscription_date=$obj->dateadh;
                    $this->first_subscription_amount=$obj->cotisation;
                }
                $this->last_subscription_date=$obj->dateadh;
                $this->last_subscription_amount=$obj->cotisation;

                $subscription=new Cotisation($this->db);
                $subscription->id=$obj->rowid;
                $subscription->fk_adherent=$obj->fk_adherent;
                $subscription->amount=$obj->cotisation;
                $subscription->note=$obj->note;
                $subscription->fk_bank=$obj->fk_bank;
                $subscription->datem=$this->db->jdate($obj->datem);
                $subscription->datec=$this->db->jdate($obj->datec);
                $subscription->dateadh=$this->db->jdate($obj->dateadh);
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
     *	@param	timestamp	$date        		Date d'effet de la cotisation
     *	@param	amount		$montant     		Montant cotisation (accepte 0 pour les adherents non soumis e cotisation)
     *	@param	int			$accountid			Id compte bancaire
     *	@param	string		$operation			Type operation (si Id compte bancaire fourni)
     *	@param	string		$label				Label operation (si Id compte bancaire fourni)
     *	@param	string		$num_chq			Numero cheque (si Id compte bancaire fourni)
     *	@param	string		$emetteur_nom		Nom emetteur cheque
     *	@param	string		$emetteur_banque	Nom banque emetteur cheque
     *	@param	timestamp	$datesubend			Date fin adhesion
     *	@return int         					rowid of record added, <0 if KO
     */
    function cotisation($date, $montant, $accountid=0, $operation='', $label='', $num_chq='', $emetteur_nom='', $emetteur_banque='', $datesubend=0)
    {
        global $conf,$langs,$user;

		$error=0;

        // Clean parameters
        if (! $montant) $montant=0;

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
        $cotisation=new Cotisation($this->db);
        $cotisation->fk_adherent=$this->id;
        $cotisation->dateh=$date;		// Date of new subscription
        $cotisation->datef=$datefin;	// End data of new subscription
        $cotisation->amount=$montant;
        $cotisation->note=$label;

        $rowid=$cotisation->create($user);
        if ($rowid > 0)
        {
            // Update denormalized subscription end date (read database subscription to find values)
            // This will also update this->datefin
            $result=$this->update_end_date($user);
            if ($result > 0)
            {
                // Change properties of object (used by triggers)
                $this->last_subscription_date=dol_now();
                $this->last_subscription_amount=$montant;
                $this->last_subscription_date_start=$date;
                $this->last_subscription_date_end=$datefin;

                // Appel des triggers
                include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('MEMBER_SUBSCRIPTION',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // Fin appel triggers
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
            $this->error=$cotisation->error;
            $this->db->rollback();
            return -1;
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
        $sql.= ", datevalid = ".$this->db->idate($now);
        $sql.= ", fk_user_valid=".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::validate sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->statut=1;

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MEMBER_VALIDATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

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

            // Appel des triggers
            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('MEMBER_RESILIATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // Fin appel triggers

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
     *  Fonction qui ajoute l'adherent au abonnements automatiques mailing-list, spip, etc.
     *  TODO Move this into member creation trigger (trigger of mailmanspip module)
     *
     *  @return		int		<0 if KO, >0 if OK
     */
    function add_to_abo()
    {
        global $conf;

        include_once(DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php');
        $mailmanspip=new MailmanSpip($db);

        $err=0;

        // mailman
        if (! empty($conf->global->ADHERENT_USE_MAILMAN))
        {
            $result=$mailmanspip->add_to_mailman($this);
            if ($result < 0)
            {
                $err+=1;
            }
        }

        // spip
        if ($conf->global->ADHERENT_USE_SPIP && $conf->mailmanspip->enabled)
        {
            $result=$mailmanspip->add_to_spip($this);
            if ($result < 0)
            {
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
     *  Fonction qui supprime l'adherent des abonnements automatiques mailing-list, spip, etc.
     *  TODO Move this into member deletion trigger (trigger of mailmanspip module)
     *
     *  @return     int     <0 if KO, >0 if OK
     */
    function del_to_abo()
    {
        global $conf;

        include_once(DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php');
        $mailmanspip=new MailmanSpip($db);

        $err=0;

        // mailman
        if (! empty($conf->global->ADHERENT_USE_MAILMAN))
        {
            $result=$mailmanspip->del_to_mailman($this);
            if ($result < 0)
            {
                $err+=1;
            }
        }

        if ($conf->global->ADHERENT_USE_SPIP && $conf->mailmanspip->enabled)
        {
            $result=$mailmanspip->del_to_spip($this);
            if ($result < 0)
            {
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
     *    Return label of a civility of a contact
     *
     *    @param	int		$nohtmlentities     0=Encode with htmlentities for HTML output, 1=No htmlentities for memory translation
     *    @return   string              		Name translated of civility
     */
    function getCivilityLabel($nohtmlentities=0)
    {
        global $langs;
        $langs->load("dict");

        $code=$this->civilite_id;
        if ($nohtmlentities) return $langs->transnoentities("Civility".$code)!="Civility".$code ? $langs->transnoentities("Civility".$code) : $code;
        else return $langs->trans("Civility".$code)!="Civility".$code ? $langs->trans("Civility".$code) : $code;
    }

    /**
     *    	Renvoie nom clicable (avec eventuellement le picto)
     *
     *		@param	int		$withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *		@param	int		$maxlen			length max libelle
     *		@param	string	$option			Page lien
     *		@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0,$maxlen=0,$option='card')
    {
        global $langs;

        $result='';

        if ($option == 'card')
        {
            $lien = '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$this->id.'">';
            $lienfin='</a>';
        }
        if ($option == 'subscription')
        {
            $lien = '<a href="'.DOL_URL_ROOT.'/adherents/card_subscriptions.php?rowid='.$this->id.'">';
            $lienfin='</a>';
        }

        $picto='user';
        $label=$langs->trans("ShowMember");

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
        return $result;
    }


    /**
     * 	Return full address of member
     *
     * 	@param		int			$withcountry		1=Add country into address string
     *  @param		string		$sep				Separator to use to build string
     *	@return		string							Full address string
     */
    function getFullAddress($withcountry=0,$sep="\n")
    {
        $ret='';
        if ($withcountry && $this->country_id && (empty($this->country_code) || empty($this->country)))
        {
            require_once(DOL_DOCUMENT_ROOT ."/core/lib/company.lib.php");
            $tmparray=getCountry($this->country_id,'all');
            $this->country_code=$tmparray['code'];
            $this->country     =$tmparray['label'];
        }

        if (in_array($this->country_code,array('US')))
        {
	        $ret.=($this->address?$this->address.$sep:'');
	        $ret.=trim($this->zip.' '.$this->town);
	        if ($withcountry) $ret.=($this->country?$sep.$this->country:'');
        }
        else
        {
	        $ret.=($this->address?$this->address.$sep:'');
	        $ret.=trim($this->zip.' '.$this->town);
	        if ($withcountry) $ret.=($this->country?$sep.$this->country:'');
        }
        return trim($ret);
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

    /**
     *  Renvoi le libelle d'un statut donne
     *
     *  @param	int			$statut      			Id statut
     *	@param	int			$need_subscription		1 si type adherent avec cotisation, 0 sinon
     *	@param	timestamp	$date_end_subscription	Date fin adhesion
     *  @param  int			$mode        			0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return string      						Label
     */
    function LibStatut($statut,$need_subscription,$date_end_subscription,$mode=0)
    {
        global $langs;
        $langs->load("members");
        if ($mode == 0)
        {
            if ($statut == -1) return $langs->trans("MemberStatusDraft");
            if ($statut >= 1)
            {
                if (! $date_end_subscription)            return $langs->trans("MemberStatusActive");
                elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate");
                else                                     return $langs->trans("MemberStatusPaid");
            }
            if ($statut == 0)  return $langs->trans("MemberStatusResiliated");
        }
        if ($mode == 1)
        {
            if ($statut == -1) return $langs->trans("MemberStatusDraftShort");
            if ($statut >= 1)
            {
                if (! $date_end_subscription)            return $langs->trans("MemberStatusActiveShort");
                elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLateShort");
                else                                     return $langs->trans("MemberStatusPaidShort");
            }
            if ($statut == 0)  return $langs->trans("MemberStatusResiliatedShort");
        }
        if ($mode == 2)
        {
            if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraftShort");
            if ($statut >= 1)
            {
                if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActiveShort");
                elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLateShort");
                else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4').' '.$langs->trans("MemberStatusPaidShort");
            }
            if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliatedShort");
        }
        if ($mode == 3)
        {
            if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0');
            if ($statut >= 1)
            {
                if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1');
                elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
                else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4');
            }
            if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5');
        }
        if ($mode == 4)
        {
            if ($statut == -1) return img_picto($langs->trans('MemberStatusDraft'),'statut0').' '.$langs->trans("MemberStatusDraft");
            if ($statut >= 1)
            {
                if (! $date_end_subscription)            return img_picto($langs->trans('MemberStatusActive'),'statut1').' '.$langs->trans("MemberStatusActive");
                elseif ($date_end_subscription < time()) return img_picto($langs->trans('MemberStatusActiveLate'),'statut3').' '.$langs->trans("MemberStatusActiveLate");
                else                                     return img_picto($langs->trans('MemberStatusPaid'),'statut4').' '.$langs->trans("MemberStatusPaid");
            }
            if ($statut == 0)  return img_picto($langs->trans('MemberStatusResiliated'),'statut5').' '.$langs->trans("MemberStatusResiliated");
        }
        if ($mode == 5)
        {
            if ($statut == -1) return $langs->trans("MemberStatusDraft").' '.img_picto($langs->trans('MemberStatusDraft'),'statut0');
            if ($statut >= 1)
            {
                if (! $date_end_subscription)            return $langs->trans("MemberStatusActive").' '.img_picto($langs->trans('MemberStatusActive'),'statut1');
                elseif ($date_end_subscription < time()) return $langs->trans("MemberStatusActiveLate").' '.img_picto($langs->trans('MemberStatusActiveLate'),'statut3');
                else                                     return $langs->trans("MemberStatusPaid").' '.img_picto($langs->trans('MemberStatusPaid'),'statut4');
            }
            if ($statut == 0)  return $langs->trans("MemberStatusResiliated").' '.img_picto($langs->trans('MemberStatusResiliated'),'statut5');
        }
    }


    /**
     *      Charge indicateurs this->nb de tableau de bord
     *
     *      @return     int         <0 if KO, >0 if OK
     */
    function load_state_board()
    {
        global $conf;

        $this->nb=array();

        $sql = "SELECT count(a.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.statut > 0";
        $sql.= " AND a.entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["members"]=$obj->nb;
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }

    }

    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param	User	$user   	Objet user
     *      @return int     			<0 if KO, >0 if OK
     */
    function load_board($user)
    {
        global $conf;

        $now=dol_now();

        if ($user->societe_id) return -1;   // protection pour eviter appel par utilisateur externe

        $this->nbtodo=$this->nbtodolate=0;

        $sql = "SELECT a.rowid, a.datefin";
        $sql.= " FROM ".MAIN_DB_PREFIX."adherent as a";
        $sql.= " WHERE a.statut = 1";
        $sql.= " AND a.entity = ".$conf->entity;
        $sql.= " AND (a.datefin IS NULL or a.datefin < '".$this->db->idate($now)."')";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if ($this->db->jdate($obj->datefin) < ($now - $conf->adherent->cotisation->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            dol_print_error($this->db);
            $this->error=$this->db->error();
            return -1;
        }
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
        $this->civilite_id = 0;
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
        $this->phone        = '0999999999';
        $this->phone_perso  = '0999999998';
        $this->phone_mobile = '0999999997';
        $this->note='No comment';
        $this->naiss=time();
        $this->photo='';
        $this->public=1;
        $this->statut=0;

        $this->datefin=time();
        $this->datevalid=time();

        $this->typeid=1;				// Id type adherent
        $this->type='Type adherent';	// Libelle type adherent
        $this->need_subscription=0;

        $this->first_subscription_date=time();
        $this->first_subscription_amount=10;
        $this->last_subscription_date=time();
        $this->last_subscription_amount=10;
    }


    /**
     *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
     *
     *	@param	string	$info		Info string loaded by _load_ldap_info
     *	@param	int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
     *								1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
     *								2=Return key only (uid=qqq)
     *	@return	string				DN
     */
    function _load_ldap_dn($info,$mode=0)
    {
        global $conf;
        $dn='';
        if ($mode==0) $dn=$conf->global->LDAP_KEY_MEMBERS."=".$info[$conf->global->LDAP_KEY_MEMBERS].",".$conf->global->LDAP_MEMBER_DN;
        if ($mode==1) $dn=$conf->global->LDAP_MEMBER_DN;
        if ($mode==2) $dn=$conf->global->LDAP_KEY_MEMBERS."=".$info[$conf->global->LDAP_KEY_MEMBERS];
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

        $info=array();

        // Object classes
        $info["objectclass"]=explode(',',$conf->global->LDAP_MEMBER_OBJECT_CLASS);

        $this->fullname=$this->getFullName($langs);

        // Member
        if ($this->fullname && $conf->global->LDAP_MEMBER_FIELD_FULLNAME) $info[$conf->global->LDAP_MEMBER_FIELD_FULLNAME] = $this->fullname;
        if ($this->nom && $conf->global->LDAP_MEMBER_FIELD_NAME)         $info[$conf->global->LDAP_MEMBER_FIELD_NAME] = $this->nom;
        if ($this->prenom && $conf->global->LDAP_MEMBER_FIELD_FIRSTNAME) $info[$conf->global->LDAP_MEMBER_FIELD_FIRSTNAME] = $this->prenom;
        if ($this->login && $conf->global->LDAP_MEMBER_FIELD_LOGIN)      $info[$conf->global->LDAP_MEMBER_FIELD_LOGIN] = $this->login;
        if ($this->pass && $conf->global->LDAP_MEMBER_FIELD_PASSWORD)    $info[$conf->global->LDAP_MEMBER_FIELD_PASSWORD] = $this->pass;	// this->pass = mot de passe non crypte
        if ($this->poste && $conf->global->LDAP_MEMBER_FIELD_TITLE)      $info[$conf->global->LDAP_MEMBER_FIELD_TITLE] = $this->poste;
        if ($this->adresse && $conf->global->LDAP_MEMBER_FIELD_ADDRESS)  $info[$conf->global->LDAP_MEMBER_FIELD_ADDRESS] = $this->adresse;
        if ($this->cp && $conf->global->LDAP_MEMBER_FIELD_ZIP)           $info[$conf->global->LDAP_MEMBER_FIELD_ZIP] = $this->cp;
        if ($this->ville && $conf->global->LDAP_MEMBER_FIELD_TOWN)       $info[$conf->global->LDAP_MEMBER_FIELD_TOWN] = $this->ville;
        if ($this->country_code && $conf->global->LDAP_MEMBER_FIELD_COUNTRY)     $info[$conf->global->LDAP_MEMBER_FIELD_COUNTRY] = $this->country_code;
        if ($this->email && $conf->global->LDAP_MEMBER_FIELD_MAIL)       $info[$conf->global->LDAP_MEMBER_FIELD_MAIL] = $this->email;
        if ($this->phone && $conf->global->LDAP_MEMBER_FIELD_PHONE)      $info[$conf->global->LDAP_MEMBER_FIELD_PHONE] = $this->phone;
        if ($this->phone_perso && $conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO) $info[$conf->global->LDAP_MEMBER_FIELD_PHONE_PERSO] = $this->phone_perso;
        if ($this->phone_mobile && $conf->global->LDAP_MEMBER_FIELD_MOBILE) $info[$conf->global->LDAP_MEMBER_FIELD_MOBILE] = $this->phone_mobile;
        if ($this->fax && $conf->global->LDAP_MEMBER_FIELD_FAX)	      $info[$conf->global->LDAP_MEMBER_FIELD_FAX] = $this->fax;
        if ($this->note && $conf->global->LDAP_MEMBER_FIELD_DESCRIPTION) $info[$conf->global->LDAP_MEMBER_FIELD_DESCRIPTION] = $this->note;
        if ($this->naiss && $conf->global->LDAP_MEMBER_FIELD_BIRTHDATE)  $info[$conf->global->LDAP_MEMBER_FIELD_BIRTHDATE] = dol_print_date($this->naiss,'dayhourldap');
        if (isset($this->statut) && $conf->global->LDAP_FIELD_MEMBER_STATUS)  $info[$conf->global->LDAP_FIELD_MEMBER_STATUS] = $this->statut;
        if ($this->datefin && $conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION)  $info[$conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION] = dol_print_date($this->datefin,'dayhourldap');

        // Subscriptions
        if ($this->first_subscription_date && $conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE)     $info[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE]  = dol_print_date($this->first_subscription_date,'dayhourldap');
        if (isset($this->first_subscription_amount) && $conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT) $info[$conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT] = $this->first_subscription_amount;
        if ($this->last_subscription_date && $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE)       $info[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE]   = dol_print_date($this->last_subscription_date,'dayhourldap');
        if (isset($this->last_subscription_amount) && $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT)   $info[$conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT] = $this->last_subscription_amount;

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

        dol_syslog(get_class($this)."::info sql=".$sql, LOG_DEBUG);
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

}
?>
