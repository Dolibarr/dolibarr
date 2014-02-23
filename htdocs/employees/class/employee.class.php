<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		    Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		    Benoit Mortier			  <benoit.mortier@opensides.be>
 * Copyright (C) 2009-2012	Regis Houssin			    <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014  Alexandre Spangaro    <alexandre.spangaro@gmail.com> 
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
 *	\file       htdocs/employees/class/employee.class.php
 *	\ingroup    employee
 *	\brief      File of class to manage employees of a company
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *		Class to manage employees of a company
 */
class Employee extends CommonObject
{
    public $element='employee';
    public $table_element='employee';
    protected $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $error;
    var $errors;
    var $mesgs;

    var $id;
    var $ref;
    var $civility_id;
    var $firstname;
    var $lastname;
    var $login;
    var $pass;
    var $address;
    var $zip;
    var $town;

    var $state_id;              // Id of department
    var $state_code;            // Code of department
    var $state;                 // Label of department

    var $country_id;
    var $country_code;
    var $country;

    var $email;
    var $skype;
    var $phone_pro;
    var $phone_perso;
    var $phone_mobile;

    var $sex;
    var $public;
    var $note;				// Private note
    var $statut;			// 0:brouillon, 1:validé, >=2:désactivé
    var $photo;

    var $datec;
    var $datem;
    var $datevalid;
    var $birth;

    var $typeid;			// Id type salarié
    var $type;				// Libelle type salarié
    
    var $user_id;
    var $user_login;

    var $fk_user;

    //  var $public;
    var $array_options;

    var $oldcopy;		// To contains a clone of this when we need to save old properties of object


    /**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->statut = 0;
        // le salarié n'est pas public par defaut
        $this->public = 0;
        // les champs optionnels sont vides
        $this->array_options=array();
    }


    /**
     *  Fonction envoyant un email au salarié avec le texte fourni en parametre.
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
        if (! empty($conf->global->EMPLOYEE_MAIL_FROM)) $from=$conf->global->EMPLOYEE_MAIL_FROM;

        include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
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

		$birthday = dol_print_date($this->birth,'day');

		$msgishtml = 0;
		if (dol_textishtml($text,1)) $msgishtml = 1;

		$infos='';
		if ($this->civility_id) $infos.= $langs->transnoentities("UserTitle").": ".$this->getCivilityLabel(1)."\n";
		$infos.= $langs->transnoentities("id").": ".$this->id."\n";
		$infos.= $langs->transnoentities("Lastname").": ".$this->lastname."\n";
		$infos.= $langs->transnoentities("Firstname").": ".$this->firstname."\n";
		$infos.= $langs->transnoentities("User").": ".$this->user."\n";
		$infos.= $langs->transnoentities("Address").": ".$this->address."\n";
		$infos.= $langs->transnoentities("Zip").": ".$this->zip."\n";
		$infos.= $langs->transnoentities("Town").": ".$this->town."\n";
		$infos.= $langs->transnoentities("Country").": ".$this->country."\n";
		$infos.= $langs->transnoentities("EMail").": ".$this->email."\n";
		if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
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
				'%CIVILITY%'=>$this->getCivilityLabel($msgishtml?0:1),
				'%FIRSTNAME%'=>$msgishtml?dol_htmlentitiesbr($this->firstname):$this->firstname,
				'%LASTNAME%'=>$msgishtml?dol_htmlentitiesbr($this->lastname):$this->lastname,
				'%FULLNAME%'=>$msgishtml?dol_htmlentitiesbr($this->getFullName($langs)):$this->getFullName($langs),
				'%COMPANY%'=>$msgishtml?dol_htmlentitiesbr($this->societe):$this->societe,
				'%ADDRESS%'=>$msgishtml?dol_htmlentitiesbr($this->address):$this->address,
				'%ZIP%'=>$msgishtml?dol_htmlentitiesbr($this->zip):$this->zip,
				'%TOWN%'=>$msgishtml?dol_htmlentitiesbr($this->town):$this->town,
				'%COUNTRY%'=>$msgishtml?dol_htmlentitiesbr($this->country):$this->country,
				'%EMAIL%'=>$msgishtml?dol_htmlentitiesbr($this->email):$this->email,
				'%BIRTH%'=>$msgishtml?dol_htmlentitiesbr($birthday):$birthday,
				'%PHOTO%'=>$msgishtml?dol_htmlentitiesbr($this->photo):$this->photo,
				'%LOGIN%'=>$msgishtml?dol_htmlentitiesbr($this->login):$this->login,
				'%PASSWORD%'=>$msgishtml?dol_htmlentitiesbr($this->pass):$this->pass,
				// For backward compatibility
				'%INFOS%'=>$msgishtml?dol_htmlentitiesbr($infos):$infos,
				'%SOCIETE%'=>$msgishtml?dol_htmlentitiesbr($this->societe):$this->societe,
				'%PRENOM%'=>$msgishtml?dol_htmlentitiesbr($this->firstname):$this->firstname,
				'%NOM%'=>$msgishtml?dol_htmlentitiesbr($this->lastname):$this->lastname,
				'%CP%'=>$msgishtml?dol_htmlentitiesbr($this->zip):$this->zip,
				'%VILLE%'=>$msgishtml?dol_htmlentitiesbr($this->town):$this->town,
				'%PAYS%'=>$msgishtml?dol_htmlentitiesbr($this->country):$this->country,
		);

		complete_substitutions_array($substitutionarray, $langs);

		return make_substitutions($text,$substitutionarray);
	}
  
    /**
     *	Renvoie le libelle traduit du sexe d'un salarié (Female ou male)
     *
     *	@param	string		$sex		Sexe Féminin ou Masculin
     *	@return	string					Label
     */
    function getsexlib($morphy='')
    {
        global $langs;
        if (! $sex) { $sex=$this->sex; }
        if ($sex == 'fem') { return $langs->trans("Female"); }
        if ($sex == 'mal') { return $langs->trans("Male"); }
        return $sex;
    }

    /**
     *	Create an employee into database
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
        if (! empty($conf->global->EMPLOYEE_MAIL_REQUIRED) && ! isValidEMail($this->email))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEMail",$this->email);
            return -1;
        }
        if (! $this->datec) $this->datec=$now;
        if (empty($conf->global->EMPLOYEE_LOGIN_NOT_REQUIRED))
        {
            if (empty($this->login))
            {
                $this->error = $langs->trans("ErrorWrongValueForParameterX","Login");
                return -1;
            }
        }

        $this->db->begin();

        // Insert employee
        $sql = "INSERT INTO ".MAIN_DB_PREFIX."employee";
        $sql.= " (datec,login,fk_user_author,fk_user_mod,fk_user_valid,sex,fk_employee_type,entity,import_key)";
        $sql.= " VALUES (";
        $sql.= " '".$this->db->idate($this->datec)."'";
        $sql.= ", ".($this->login?"'".$this->db->escape($this->login)."'":"null");
        $sql.= ", ".($user->id>0?$user->id:"null");	// Can be null because employee can be createb by a guest or a script
        $sql.= ", null, null, '".$this->sex."'";
        $sql.= ", '".$this->typeid."'";
        $sql.= ", ".$conf->entity;
        $sql.= ", ".(! empty($this->import_key) ? "'".$this->import_key."'":"null");
        $sql.= ")";

        dol_syslog(get_class($this)."::create sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX."employee");
            if ($id > 0)
            {
                $this->id=$id;
                $this->ref=$id;

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
                    $sql.= " fk_employee = '".$this->id."'";
                    $sql.= " WHERE rowid = ".$this->user_id;
                    dol_syslog(get_class($this)."::create sql=".$sql);
                    $resql = $this->db->query($sql);
                    if (! $resql)
                    {
                        $this->error='Failed to update user to make link with employee';
                        $this->db->rollback();
                        return -4;
                    }
                }

                if (! $notrigger)
                {
                    // Appel des triggers
                    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('EMPLOYEE_CREATE',$this,$user,$langs,$conf);
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
     *	Update an employee in database (standard information and password)
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

        // Check parameters
        if (! empty($conf->global->EMPLOYEE_MAIL_REQUIRED) && ! isValidEMail($this->email))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEMail",$this->email);
            return -1;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."employee SET";
        $sql.= " civility = ".(!is_null($this->civility_id)?"'".$this->civility_id."'":"null");
        $sql.= ", firstname = ".($this->firstname?"'".$this->db->escape($this->firstname)."'":"null");
        $sql.= ", lastname=".($this->lastname?"'".$this->db->escape($this->lastname)."'":"null");
        $sql.= ", login=".($this->login?"'".$this->db->escape($this->login)."'":"null");
        //$sql.= ", user=".($this->user?"'".$this->db->escape($this->user)."'":"null");
        $sql.= ", fk_user=".($this->fk_user > 0?"'".$this->fk_user."'":"null");
        $sql.= ", address=".($this->address?"'".$this->db->escape($this->address)."'":"null");
        $sql.= ", zip=".($this->zip?"'".$this->db->escape($this->zip)."'":"null");
        $sql.= ", town=".($this->town?"'".$this->db->escape($this->town)."'":"null");
        $sql.= ", country=".($this->country_id>0?"'".$this->country_id."'":"null");
        $sql.= ", state_id=".($this->state_id>0?"'".$this->state_id."'":"null");
        $sql.= ", email='".$this->email."'";
        $sql.= ", skype='".$this->skype."'";
        $sql.= ", phone_pro=".($this->phone_pro?"'".$this->db->escape($this->phone_pro)."'":"null");
        $sql.= ", phone_perso=".($this->phone_perso?"'".$this->db->escape($this->phone_perso)."'":"null");
        $sql.= ", phone_mobile=".($this->phone_mobile?"'".$this->db->escape($this->phone_mobile)."'":"null");
        $sql.= ", note=".($this->note?"'".$this->db->escape($this->note)."'":"null");
        $sql.= ", photo=".($this->photo?"'".$this->photo."'":"null");
        $sql.= ", public='".$this->public."'";
        $sql.= ", statut=".$this->statut;
        $sql.= ", fk_employee_type=".$this->typeid;
        $sql.= ", sex='".$this->sex."'";
        $sql.= ", birth=".($this->birth?"'".$this->db->idate($this->birth)."'":"null");
        if ($this->datevalid) $sql.= ", datevalid='".$this->db->idate($this->datevalid)."'";	// Ne doit etre modifie que par validation du salarié
        $sql.= ", fk_user_mod=".($user->id>0?$user->id:'null');	// Can be null because employee can be create by a guest
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::update employee sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
		    unset($this->country_code);
		    unset($this->country);
		    unset($this->state_code);
		    unset($this->state);

		    $nbrowsaffected+=$this->db->affected_rows($resql);

            // Actions on extra fields (by external module)
            $hookmanager->initHooks(array('employeedao'));
            $parameters=array('id'=>$this->id);
            $action='';
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

            // Update password
            if (! $error && $this->pass)
            {
                dol_syslog(get_class($this)."::update password");
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
                dol_syslog(get_class($this)."::update link to user");
                $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_employee = NULL WHERE fk_employee = ".$this->id;
                dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
                $resql = $this->db->query($sql);
                if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
                // If there is a user linked to this employee
                if ($this->user_id > 0)
                {
                    $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_employee = ".$this->id." WHERE rowid = ".$this->user_id;
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
                    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

                    dol_syslog(get_class($this)."::update linked user");

                    $luser=new User($this->db);
                    $result=$luser->fetch($this->user_id);

                    if ($result >= 0)
                    {
                        $luser->civility_id=$this->civility_id;
                        $luser->firstname=$this->firstname;
                        $luser->lastname=$this->lastname;
                        $luser->login=$this->user_login;
                        $luser->pass=$this->pass;
                        $luser->societe_id=$this->societe;

                        $luser->email=$this->email;
                        $luser->skype=$this->skype;
                        $luser->office_phone=$this->phone;
                        $luser->user_mobile=$this->phone_mobile;

                        $luser->fk_employee=$this->id;

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
                if ($this->fk_user > 0 && ! $nosyncthirdparty)
                {
                    require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

                    dol_syslog(get_class($this)."::update linked thirdparty");

                    // This employee is linked with a thirdparty, so we also update thirdparty informations
                    // if this is an update.
                    $lthirdparty=new Societe($this->db);
                    $result=$lthirdparty->fetch($this->fk_user);

                    if ($result >= 0)
                    {
                        $lthirdparty->address=$this->address;
                        $lthirdparty->zip=$this->zip;
                        $lthirdparty->town=$this->town;
                        $lthirdparty->email=$this->email;
                        $lthirdparty->skype=$this->skype;
                        $lthirdparty->phone=$this->phone;
                        $lthirdparty->state_id=$this->state_id;
                        $lthirdparty->country_id=$this->country_id;
                        $lthirdparty->country_id=$this->country_id;
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
                    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('EMPLOYEE_MODIFY',$this,$user,$langs,$conf);
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
     *  Fonction qui supprime le salarié et les données associees
     *
     *  @param	int		$rowid		Id of employee to delete
     *  @return	int					<0 if KO, 0=nothing to do, >0 if OK
     */
    function delete($rowid)
    {
        global $conf, $langs, $user;

        $result = 0;
		    $error=0;
		    $errorflag=0;

		    // Check parameters
		    if (empty($rowid)) $rowid=$this->id;

        $this->db->begin();

        // Remove category
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_employee WHERE fk_employee = ".$rowid;
        dol_syslog(get_class($this)."::delete sql=".$sql);
        $resql=$this->db->query($sql);
        if (! $resql)
        {
        	$error++;
        	$this->error .= $this->db->lasterror();
        	$errorflag=-1;
        	dol_syslog(get_class($this)."::delete error ".$errorflag." ".$this->error, LOG_ERR);

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
        		dol_syslog(get_class($this)."::delete error ".$errorflag." ".$this->error, LOG_ERR);
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
        			dol_syslog(get_class($this)."::delete error ".$errorflag." ".$this->error, LOG_ERR);
        		}
        	}
        }

        // Remove employee
        if (! $error)
        {
        	$sql = "DELETE FROM ".MAIN_DB_PREFIX."employee WHERE rowid = ".$rowid;
        	dol_syslog(get_class($this)."::delete sql=".$sql);
        	$resql=$this->db->query($sql);
        	if (! $resql)
        	{
        		$error++;
        		$this->error .= $this->db->lasterror();
        		$errorflag=-5;
        		dol_syslog(get_class($this)."::delete error ".$errorflag." ".$this->error, LOG_ERR);
        	}
        }

        if (! $error)
        {
        	// Appel des triggers
       		include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
        	$interface=new Interfaces($this->db);
        	$result=$interface->run_triggers('EMPLOYEE_DELETE',$this,$user,$langs,$conf);
        	if ($result < 0) {$error++; $this->errors=$interface->errors;}
        	// Fin appel triggers
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
        $sql = "UPDATE ".MAIN_DB_PREFIX."employee SET pass = '".$this->db->escape($password_indatabase)."'";
        $sql.= " WHERE rowid = ".$this->id;

        //dol_syslog("Employee::Password sql=hidden");
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
                    require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

                    // This employee is linked with a user, so we also update users informations
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
                    include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('EMPLOYEE_NEW_PASSWORD',$this,$user,$langs,$conf);
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

        // If user is linked to this employee, remove old link to this employee
        $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_employee = NULL WHERE fk_employee = ".$this->id;
        dol_syslog(get_class($this)."::setUserId sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -1; }

        // Set link to user
        if ($userid > 0)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_employee = ".$this->id;
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
    function setThirdPartyId($userid)
    {
        global $conf, $langs;

        $this->db->begin();

        // Remove link to third party onto any other employees
        if ($thirdpartyid > 0)
        {
            $sql = "UPDATE ".MAIN_DB_PREFIX."employee SET fk_user = null";
            $sql.= " WHERE fk_user = '".$userid."'";
            $sql.= " AND entity = ".$conf->entity;
            dol_syslog(get_class($this)."::setUserId sql=".$sql);
            $resql = $this->db->query($sql);
        }

        // Add link to third party for current employee
        $sql = "UPDATE ".MAIN_DB_PREFIX."employee SET fk_user = ".($userid>0 ? $userid : 'null');
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
     *	Method to load employee from its login
     *
     *	@param	string	$login		login of employee
     *	@return	void
     */
    function fetch_login($login)
    {
        global $conf;

        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."employee";
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
     *	Method to load employee from its name
     *
     *	@param	string	$firstname	Firstname
     *	@param	string	$lastname	Lastname
     *	@return	void
     */
    function fetch_name($firstname,$lastname)
    {
    	global $conf;

    	$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."employee";
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
     *	Load employee from database
     *
     *	@param	int		$rowid      Id of object to load
     * 	@param	string	$ref		To load employee from its ref
     * 	@param	int		$fk_soc		To load employee from its link to third party
     * 	@param	int		$ref_ext	External reference
     *	@return int         		>0 if OK, 0 if not found, <0 if KO
     */
    function fetch($rowid,$ref='',$fk_soc='',$ref_ext='')
    {
        global $langs;

        $sql = "SELECT d.rowid, d.ref_ext, d.civility, d.firstname, d.lastname, d.fk_user, d.statut, d.public, d.address, d.zip, d.town, d.note,";
        $sql.= " d.email, d.skype, d.phone_pro, d.phone_perso, d.phone_mobile, d.login, d.pass,";
        $sql.= " d.photo, d.fk_employee_type, d.sex as sex, d.entity,";
        $sql.= " d.datec as datec,";
        $sql.= " d.tms as datem,";
        $sql.= " d.birth as birthday,";
        $sql.= " d.datevalid as datev,";
        $sql.= " d.country,";
        $sql.= " d.state_id,";
        $sql.= " p.rowid as country_id, p.code as country_code, p.libelle as country,";
        $sql.= " dep.nom as state, dep.code_departement as state_code,";
        $sql.= " t.label as type,";
        $sql.= " u.rowid as user_id, u.login as user_login";
        $sql.= " FROM ".MAIN_DB_PREFIX."employee_type as t, ".MAIN_DB_PREFIX."employee as d";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.country = p.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as dep ON d.state_id = dep.rowid";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON d.rowid = u.fk_employee";
        $sql.= " WHERE d.fk_employee_type = t.rowid";
        if ($rowid) $sql.= " AND d.rowid=".$rowid;
        elseif ($ref || $fk_user) {
        	$sql.= " AND d.entity IN (".getEntity().")";
        	if ($ref) $sql.= " AND d.rowid='".$ref."'";
        	elseif ($fk_soc) $sql.= " AND d.fk_user='".$fk_user."'";
        }
        elseif ($ref_ext)
        {
        	$sql.= " AND d.ref_ext='".$this->db->escape($ref_ext)."'";
        }

        dol_syslog(get_class($this)."::fetch sql=".$sql);
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
                $this->civility_id		= $obj->civility;
                $this->firstname		= $obj->firstname;
                $this->lastname			= $obj->lastname;
                $this->login			= $obj->login;
                $this->pass				= $obj->pass;
                $this->fk_user		= $obj->fk_user;
                $this->address			= $obj->address;
                $this->zip				= $obj->zip;
                $this->town				= $obj->town;

                $this->state_id			= $obj->state_id;
                $this->state_code		= $obj->state_id?$obj->state_code:'';
                $this->state			= $obj->state_id?$obj->state:'';

                $this->country_id		= $obj->country_id;
                $this->country_code		= $obj->country_code;
                if ($langs->trans("Country".$obj->country_code) != "Country".$obj->country_code)
                	$this->country = $langs->transnoentitiesnoconv("Country".$obj->country_code);
                else
                	$this->country=$obj->country;

                $this->phone_pro		= $obj->phone_pro;
                $this->phone_perso		= $obj->phone_perso;
                $this->phone_mobile		= $obj->phone_mobile;
                $this->email			= $obj->email;
                $this->skype			= $obj->skype;

                $this->photo			= $obj->photo;
                $this->statut			= $obj->statut;
                $this->public			= $obj->public;

                $this->datec			= $this->db->jdate($obj->datec);
                $this->datem			= $this->db->jdate($obj->datem);
                $this->datevalid		= $this->db->jdate($obj->datev);
                $this->birth			= $this->db->jdate($obj->birthday);

                $this->note				= $obj->note;
                $this->sex  			= $obj->sex;

                $this->typeid			= $obj->fk_employee_type;
                $this->type				= $obj->type;

                $this->user_id			= $obj->user_id;
                $this->user_login		= $obj->user_login;

                // Retreive all extrafield for thirdparty
                // fetch optionals attributes and labels
                require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
                $extrafields=new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
                $this->fetch_optionals($this->id,$extralabels);

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
     *		Function that validate an employee
     *
     *		@param	User	$user		user employee qui valide
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
            dol_syslog(get_class($this)."::validate statut of employee does not allow this", LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."employee SET";
        $sql.= " statut = 1";
        $sql.= ", datevalid = '".$this->db->idate($now)."'";
        $sql.= ", fk_user_valid=".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        dol_syslog(get_class($this)."::validate sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $this->statut=1;

            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('EMPLOYEE_VALIDATE',$this,$user,$langs,$conf);
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
    function deactivate($user)
    {
        global $langs,$conf;

		    $error=0;

		    // Check paramaters
        if ($this->statut >= 2)
        {
            dol_syslog(get_class($this)."::deactivate statut of employee does not allow this", LOG_WARNING);
            return 0;
        }

        $this->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."employee SET";
        $sql.= " statut = 2";
        $sql.= ", fk_user_valid=".$user->id;
        $sql.= " WHERE rowid = ".$this->id;

        $result = $this->db->query($sql);
        if ($result)
        {
            $this->statut=2;

            // Appel des triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('EMPLOYEE_DEACTIVATE',$this,$user,$langs,$conf);
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
     *  Function to add employee into external tools mailing-list, spip, etc.
     *
     *  @return		int		<0 if KO, >0 if OK
     */
    function add_to_abo()
    {
        global $conf,$langs;

        include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
        $mailmanspip=new MailmanSpip($this->db);

        $err=0;

        // mailman
        if (! empty($conf->global->EMPLOYEE_USE_MAILMAN))
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
        if ($conf->global->EMPLOYEE_USE_SPIP && ! empty($conf->mailmanspip->enabled))
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


    /**
     *  Function to delete an employee from external tools like mailing-list, spip, etc.
     *
     *  @return     int     <0 if KO, >0 if OK
     */
    function del_to_abo()
    {
        global $conf,$langs;

        include_once DOL_DOCUMENT_ROOT.'/mailmanspip/class/mailmanspip.class.php';
        $mailmanspip=new MailmanSpip($this->db);

        $err=0;

        // mailman
        if (! empty($conf->global->EMPLOYEE_USE_MAILMAN))
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
     *    Return civility label of a employee
     *
     *    @return   string              	Translated name of civility
     */
    function getCivilityLabel()
    {
    	global $langs;
    	$langs->load("dict");

    	$code=(! empty($this->civility_id)?$this->civility_id:(! empty($this->civility_id)?$this->civility_id:''));
    	if (empty($code)) return '';
    	return $langs->getLabelFromKey($this->db, "Civility".$code, "c_civility", "code", "civility", $code);
    }

    /**
     *    Renvoie nom clicable (avec eventuellement le picto)
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
            $lien = '<a href="'.DOL_URL_ROOT.'/employees/fiche.php?rowid='.$this->id.'">';
            $lienfin='</a>';
        }
        if ($option == 'category')
        {
        	$lien = '<a href="'.DOL_URL_ROOT.'/employees/categorie.php?id='.$this->id.'&type=3">';
        	$lienfin='</a>';
        }

        $picto='user';
        $label=$langs->trans("ShowEmployee");

        if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$lien.($maxlen?dol_trunc($this->ref,$maxlen):$this->ref).$lienfin;
        return $result;
    }

    /**
     *  Retourne le libelle du statut d'un salarié (brouillon, validé, Désactivé)
     *
     *  @param	int		$mode       0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return string				Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut,'','',$mode);
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
        $langs->load("employees");
        if ($mode == 0)
        {
            if ($statut == 0) return $langs->trans("EmployeeStatusDraft");
            if ($statut == 1) return $langs->trans("EmployeeStatutActive");
            if ($statut >= 2) return $langs->trans("EmployeeStatusNotActive");
        }
        if ($mode == 1)
        {
            if ($statut == 0) return $langs->trans("EmployeeStatusDraftShort");
            if ($statut == 1) return $langs->trans("MemberStatusActiveShort");
            if ($statut >= 2) return $langs->trans("MemberStatusNotActiveShort");
        }
        if ($mode == 2)
        {
            if ($statut == 0) return img_picto($langs->trans('EmployeeStatusDraft'),'statut0').' '.$langs->trans("EmployeeStatusDraftShort");
            if ($statut == 1) return img_picto($langs->trans('EmployeeStatusActive'),'statut4').' '.$langs->trans("EmployeeStatusActiveShort");
            if ($statut >= 2) return img_picto($langs->trans('EmployeeStatusNotActive'),'statut6').' '.$langs->trans("EmployeeStatusNotActiveShort");
        }
        if ($mode == 3)
        {
            if ($statut == 0) return img_picto($langs->trans('EmployeeStatusDraft'),'statut0');
            if ($statut == 1) return img_picto($langs->trans('EmployeeStatusActive'),'statut4');
            if ($statut >= 2) return img_picto($langs->trans('EmployeeStatusNotActive'),'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == 0) return img_picto($langs->trans('EmployeeStatusDraft'),'statut0').' '.$langs->trans("EmployeeStatusDraft");
            if ($statut == 1) return img_picto($langs->trans('EmployeeStatusActive'),'statut4').' '.$langs->trans("EmployeeStatusActive");
            if ($statut >= 2) return img_picto($langs->trans('EmployeeStatusNotActive'),'statut6').' '.$langs->trans("MemberStatusNotActive");
        }
        if ($mode == 5)
        {
            if ($statut == 0) return $langs->trans("EmployeeStatusDraft").' '.img_picto($langs->trans('EmployeeStatusDraft'),'statut0');
            if ($statut == 1) return '<span class="hideonsmartphone">'.$langs->trans("EmployeeStatusActive").' </span>'.img_picto($langs->trans('EmployeeStatusActive'),'statut4');
            if ($statut >= 2) return '<span class="hideonsmartphone">'.$langs->trans("EmployeeStatusNotActive").' </span>'.img_picto($langs->trans('EmployeeStatusNotActive'),'statut6');
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

        $sql = "SELECT count(e.rowid) as nb";
        $sql.= " FROM ".MAIN_DB_PREFIX."employee as e";
        $sql.= " WHERE e.statut > 0";
        $sql.= " AND e.entity = ".$conf->entity;

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nb["employees"]=$obj->nb;
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
        $this->address = '61 jump street';
        $this->zip = '75000';
        $this->town = 'Paris';
        $this->country_id = 1;
        $this->country_code = 'FR';
        $this->country = 'France';
        $this->sex = 'mal';
        $this->email = 'specimen@specimen.com';
        $this->skype = 'tom.hanson';
        $this->phone_pro = '0999999999';
        $this->phone_perso  = '0999999998';
        $this->phone_mobile = '0999999997';
        $this->note='No comment';
        $this->birth=time();
        $this->photo='';
        $this->public=1;
        $this->statut=0;

        $this->datevalid=time();

        $this->typeid=1;				// Id type adherent
        $this->type='Type employee';	// Libelle type adherent
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
        if ($mode==0) $dn=$conf->global->LDAP_KEY_EMPLOYEES."=".$info[$conf->global->LDAP_KEY_EMPLOYEES].",".$conf->global->LDAP_EMPLOYEE_DN;
        if ($mode==1) $dn=$conf->global->LDAP_EMPLOYEE_DN;
        if ($mode==2) $dn=$conf->global->LDAP_KEY_EMPLOYEES."=".$info[$conf->global->LDAP_KEY_EMPLOYEES];
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
        $info["objectclass"]=explode(',',$conf->global->LDAP_EMPLOYEE_OBJECT_CLASS);

        $this->fullname=$this->getFullName($langs);

        // Employee
        if ($this->fullname && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_FULLNAME)) $info[$conf->global->LDAP_EMPLOYEE_FIELD_FULLNAME] = $this->fullname;
        if ($this->lastname && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_NAME))     $info[$conf->global->LDAP_EMPLOYEE_FIELD_NAME] = $this->lastname;
        if ($this->firstname && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_FIRSTNAME)) $info[$conf->global->LDAP_EMPLOYEE_FIELD_FIRSTNAME] = $this->firstname;
        if ($this->login && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_LOGIN))      $info[$conf->global->LDAP_EMPLOYEE_FIELD_LOGIN] = $this->login;
        if ($this->pass && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_PASSWORD))    $info[$conf->global->LDAP_EMPLOYEE_FIELD_PASSWORD] = $this->pass;	// this->pass = mot de passe non crypte
        if ($this->poste && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_TITLE))      $info[$conf->global->LDAP_EMPLOYEE_FIELD_TITLE] = $this->poste;
        if ($this->address && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_ADDRESS))  $info[$conf->global->LDAP_EMPLOYEE_FIELD_ADDRESS] = $this->address;
        if ($this->zip && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_ZIP))           $info[$conf->global->LDAP_EMPLOYEE_FIELD_ZIP] = $this->zip;
        if ($this->town && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_TOWN))        $info[$conf->global->LDAP_EMPLOYEE_FIELD_TOWN] = $this->town;
        if ($this->country_code && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_COUNTRY))     $info[$conf->global->LDAP_EMPLOYEE_FIELD_COUNTRY] = $this->country_code;
        if ($this->email && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_MAIL))       $info[$conf->global->LDAP_EMPLOYEE_FIELD_MAIL] = $this->email;
        if ($this->skype && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_SKYPE))       $info[$conf->global->LDAP_EMPLOYEE_FIELD_SKYPE] = $this->skype;
        if ($this->phone_pro && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_PHONE_PRO))      $info[$conf->global->LDAP_EMPLOYEE_FIELD_PHONE_PRO] = $this->phone_pro;
        if ($this->phone_perso && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_PHONE_PERSO)) $info[$conf->global->LDAP_EMPLOYEE_FIELD_PHONE_PERSO] = $this->phone_perso;
        if ($this->phone_mobile && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_MOBILE)) $info[$conf->global->LDAP_EMPLOYEE_FIELD_MOBILE] = $this->phone_mobile;
        if ($this->fax && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_FAX))	      $info[$conf->global->LDAP_EMPLOYEE_FIELD_FAX] = $this->fax;
        if ($this->note && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_DESCRIPTION)) $info[$conf->global->LDAP_EMPLOYEE_FIELD_DESCRIPTION] = $this->note;
        if ($this->birth && ! empty($conf->global->LDAP_EMPLOYEE_FIELD_BIRTHDATE))  $info[$conf->global->LDAP_EMPLOYEE_FIELD_BIRTHDATE] = dol_print_date($this->birth,'dayhourldap');
        if (isset($this->statut) && ! empty($conf->global->LDAP_FIELD_EMPLOYEE_STATUS))  $info[$conf->global->LDAP_FIELD_EMPLOYEE_STATUS] = $this->statut;
        
        return $info;
    }


    /**
     *      Charge les informations d'ordre info dans l'objet employee
     *
     *      @param  int		$id       Id of employee to load
     *      @return	void
     */
    function info($id)
    {
        $sql = 'SELECT e.rowid, e.datec as datec,';
        $sql.= ' e.datevalid as datev,';
        $sql.= ' e.tms as datem,';
        $sql.= ' e.fk_user_author, e.fk_user_valid, e.fk_user_mod';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'employee as e';
        $sql.= ' WHERE e.rowid = '.$id;

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
