<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2015      Ari Elbaz (elarifr)  <github@accedinfo.com>
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
 *       \file       htdocs/user/card.php
 *       \brief      Tab of user card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);

// Define value to know what current user can do on users
$canadduser=(! empty($user->admin) || $user->rights->user->user->creer);
$canreaduser=(! empty($user->admin) || $user->rights->user->user->lire);
$canedituser=(! empty($user->admin) || $user->rights->user->user->creer);
$candisableuser=(! empty($user->admin) || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadgroup=(! empty($user->admin) || $user->rights->user->group_advance->read);
    $caneditgroup=(! empty($user->admin) || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id)
{
    // $user est le user qui edite, $id est l'id de l'utilisateur edite
    $caneditfield=((($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer));
    $caneditpassword=((($user->id == $id) && $user->rights->user->self->password)
    || (($user->id != $id) && $user->rights->user->user->password));
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2='user';
if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
if (!$canreaduser) {
	$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");
$langs->load("admin");

$object = new User($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard','globalcard'));



/**
 * Actions
 */

if ($action == 'confirm_disable' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object->fetch($id);
        $object->setstatus(0);
        header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        exit;
    }
}
if ($action == 'confirm_enable' && $confirm == "yes" && $candisableuser)
{
	$error = 0;

    if ($id <> $user->id)
    {
        $object->fetch($id);

        if (!empty($conf->file->main_limit_users))
        {
            $nb = $object->getNbOfUsers("active");
            if ($nb >= $conf->file->main_limit_users)
            {
	            $error++;
                setEventMessage($langs->trans("YourQuotaOfUsersIsReached"), 'errors');
            }
        }

        if (! $error)
        {
            $object->setstatus(1);
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
    }
}

if ($action == 'confirm_delete' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $object = new User($db);
        $object->id=$id;
        $result = $object->delete();
        if ($result < 0)
        {
            $langs->load("errors");
            setEventMessage($langs->trans("ErrorUserCannotBeDelete"), 'errors');
        }
        else
        {
            header("Location: index.php");
            exit;
        }
    }
}

// Action Add user
if ($action == 'add' && $canadduser)
{
	$error = 0;

    if (! $_POST["lastname"])
    {
	    $error++;
        setEventMessage($langs->trans("NameNotDefined"), 'errors');
        $action="create";       // Go back to create page
    }
    if (! $_POST["login"])
    {
	    $error++;
	    setEventMessage($langs->trans("LoginNotDefined"), 'errors');
        $action="create";       // Go back to create page
    }

    if (! empty($conf->file->main_limit_users)) // If option to limit users is set
    {
        $nb = $object->getNbOfUsers("active");
        if ($nb >= $conf->file->main_limit_users)
        {
	        $error++;
	        setEventMessage($langs->trans("YourQuotaOfUsersIsReached"), 'errors');
            $action="create";       // Go back to create page
        }
    }

    if (!$error)
    {
        $object->lastname		= GETPOST("lastname",'alpha');
        $object->firstname	    = GETPOST("firstname",'alpha');
        $object->login		    = GETPOST("login",'alpha');
        $object->api_key		= GETPOST("api_key",'alpha');
        $object->gender		    = GETPOST("gender",'alpha');
        $object->admin		    = GETPOST("admin",'alpha');
        $object->office_phone	= GETPOST("office_phone",'alpha');
        $object->office_fax	    = GETPOST("office_fax",'alpha');
        $object->user_mobile	= GETPOST("user_mobile");
        $object->skype          = GETPOST("skype");
        $object->email		    = GETPOST("email",'alpha');
        $object->job			= GETPOST("job",'alpha');
        $object->signature	    = GETPOST("signature");
        $object->accountancy_code = GETPOST("accountancy_code");
        $object->note			= GETPOST("note");
        $object->ldap_sid		= GETPOST("ldap_sid");
        $object->fk_user        = GETPOST("fk_user")>0?GETPOST("fk_user"):0;

        $object->thm            = GETPOST("thm")!=''?GETPOST("thm"):'';
        $object->tjm            = GETPOST("tjm")!=''?GETPOST("tjm"):'';
        $object->salary         = GETPOST("salary")!=''?GETPOST("salary"):'';
        $object->salaryextra    = GETPOST("salaryextra")!=''?GETPOST("salaryextra"):'';
        $object->weeklyhours    = GETPOST("weeklyhours")!=''?GETPOST("weeklyhours"):'';

		$object->color			= GETPOST("color")!=''?GETPOST("color"):'';

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

        // Set entity property
        $entity=GETPOST('entity','int');
        if (! empty($conf->multicompany->enabled))
        {
        	if (! empty($_POST["superadmin"]))
        	{
        		$object->entity = 0;
        	}
        	else if ($conf->multicompany->transverse_mode)
        	{
        		$object->entity = 1; // all users are forced into master entity
        	}
        	else
        	{
        		$object->entity = ($entity == '' ? 1 : $entity);
        	}
        }
        else
		{
        	$object->entity = ($entity == '' ? 1 : $entity);
        	/*if ($user->admin && $user->entity == 0 && GETPOST("admin",'alpha'))
        	{
        	}*/
        }

        $db->begin();

        $id = $object->create($user);
        if ($id > 0)
        {
            if (isset($_POST['password']) && trim($_POST['password']))
            {
                $object->setPassword($user,trim($_POST['password']));
            }

            $db->commit();

            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            $langs->load("errors");
            $db->rollback();
            if (is_array($object->errors) && count($object->errors)) setEventMessage($object->errors,'errors');
            else setEventMessage($object->error, 'errors');
            $action="create";       // Go back to create page
        }

    }
}

// Action add usergroup
if (($action == 'addgroup' || $action == 'removegroup') && $caneditfield)
{
    if ($group)
    {
        $editgroup = new UserGroup($db);
        $editgroup->fetch($group);
		$editgroup->oldcopy=clone $editgroup;

        $object->fetch($id);
        if ($action == 'addgroup')    $object->SetInGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity));
        if ($action == 'removegroup') $object->RemoveFromGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity));

        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            setEventMessage($object->error, 'errors');
        }
    }
}

if ($action == 'update' && ! $_POST["cancel"])
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if ($caneditfield)	// Case we can edit all field
    {
        $error=0;

    	if (! $_POST["lastname"])
        {
	        setEventMessage($langs->trans("NameNotDefined"), 'errors');
            $action="edit";       // Go back to create page
            $error++;
        }
        if (! $_POST["login"])
        {
	        setEventMessage($langs->trans("LoginNotDefined"), 'errors');
            $action="edit";       // Go back to create page
            $error++;
        }

        if (! $error)
        {
            $object->fetch($id);

            // Test if new login
            if (GETPOST("login") && GETPOST("login") != $object->login)
            {
				dol_syslog("New login ".$object->login." is requested. We test it does not exists.");
				$tmpuser=new User($db);
				$result=$tmpuser->fetch(0, GETPOST("login"));
				if ($result > 0)
				{
					setEventMessage($langs->trans("ErrorLoginAlreadyExists", GETPOST('login')), 'errors');
					$action="edit";       // Go back to create page
					$error++;
				}
            }
       }

       if (! $error)
       {
            $db->begin();

			$object->oldcopy = clone $object;

            $object->lastname	= GETPOST("lastname",'alpha');
            $object->firstname	= GETPOST("firstname",'alpha');
            $object->login		= GETPOST("login",'alpha');
            $object->gender		= GETPOST("gender",'alpha');
            $object->pass		= GETPOST("password");
            $object->api_key    = (GETPOST("api_key", 'alpha'))?GETPOST("api_key", 'alpha'):$object->api_key;
            $object->admin		= empty($user->admin)?0:GETPOST("admin"); // A user can only be set admin by an admin
            $object->office_phone=GETPOST("office_phone",'alpha');
            $object->office_fax	= GETPOST("office_fax",'alpha');
            $object->user_mobile= GETPOST("user_mobile");
            $object->skype    	= GETPOST("skype");
            $object->email		= GETPOST("email",'alpha');
            $object->job		= GETPOST("job",'alpha');
            $object->signature	= GETPOST("signature");
            $object->accountancy_code	= GETPOST("accountancy_code");
            $object->openid		= GETPOST("openid");
            $object->fk_user    = GETPOST("fk_user")>0?GETPOST("fk_user"):0;

	        $object->thm            = GETPOST("thm")!=''?GETPOST("thm"):'';
	        $object->tjm            = GETPOST("tjm")!=''?GETPOST("tjm"):'';
	        $object->salary         = GETPOST("salary")!=''?GETPOST("salary"):'';
	        $object->salaryextra    = GETPOST("salaryextra")!=''?GETPOST("salaryextra"):'';
	        $object->weeklyhours    = GETPOST("weeklyhours")!=''?GETPOST("weeklyhours"):'';

			$object->color    	= GETPOST("color")!=''?GETPOST("color"):'';

            // Fill array 'array_options' with data from add form
        	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

            if (! empty($conf->multicompany->enabled))
            {
            	if (! empty($_POST["superadmin"]))
            	{
            		$object->entity = 0;
            	}
            	else if ($conf->multicompany->transverse_mode)
            	{
            		$object->entity = 1; // all users in master entity
            	}
            	else
            	{
            		$object->entity = (! GETPOST('entity', 'int') ? 0 : GETPOST('entity', 'int'));
            	}
            }
            else
            {
            	$object->entity = (! GETPOST('entity', 'int') ? 0 : GETPOST('entity', 'int'));
            }

            if (GETPOST('deletephoto')) $object->photo='';
            if (! empty($_FILES['photo']['name'])) $object->photo = dol_sanitizeFileName($_FILES['photo']['name']);

            if (! $error)
            {
	            $ret=$object->update($user);
	            if ($ret < 0)
	            {
	            	$error++;
	                if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
	                {
	                    $langs->load("errors");
		                setEventMessage($langs->trans("ErrorLoginAlreadyExists",$object->login), 'errors');
	                }
	                else
	              {
		            	setEventMessages($object->error, $object->errors, 'errors');
	                }
	            }
            }

            if (! $error && isset($_POST['contactid']))
            {
            	$contactid=GETPOST('contactid');

            	if ($contactid > 0)
            	{
	            	$contact=new Contact($db);
	            	$contact->fetch($contactid);

	            	$sql = "UPDATE ".MAIN_DB_PREFIX."user";
	            	$sql.= " SET fk_socpeople=".$db->escape($contactid);
	            	if ($contact->socid) $sql.=", fk_soc=".$db->escape($contact->socid);
	            	$sql.= " WHERE rowid=".$object->id;
            	}
            	else
            	{
            		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
            		$sql.= " SET fk_socpeople=NULL, fk_soc=NULL";
            		$sql.= " WHERE rowid=".$object->id;
            	}
	            dol_syslog("fiche::update", LOG_DEBUG);
            	$resql=$db->query($sql);
            	if (! $resql)
            	{
            		$error++;
            		setEventMessage($db->lasterror(), 'errors');
            	}
            }

            if (! $error && ! count($object->errors))
            {
                if (GETPOST('deletephoto') && $object->photo)
                {
                    $fileimg=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1,$object,'user').'/logos/'.$object->photo;
                    $dirthumbs=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1,$object,'user').'/logos/thumbs';
                    dol_delete_file($fileimg);
                    dol_delete_dir_recursive($dirthumbs);
                }

                if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
                {
                    $dir= $conf->user->dir_output . '/' . get_exdir($object->id,2,0,1,$object,'user');

                    dol_mkdir($dir);

                    if (@is_dir($dir))
                    {
                        $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                        $result=dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1,0,$_FILES['photo']['error']);

                        if (! $result > 0)
                        {
	                        setEventMessage($langs->trans("ErrorFailedToSaveFile"), 'errors');
                        }
                        else
                        {
                            // Create small thumbs for company (Ratio is near 16/9)
                            // Used on logon for example
                            $imgThumbSmall = vignette($newfile, $maxwidthsmall, $maxheightsmall, '_small', $quality);

                            // Create mini thumbs for company (Ratio is near 16/9)
                            // Used on menu or for setup page for example
                            $imgThumbMini = vignette($newfile, $maxwidthmini, $maxheightmini, '_mini', $quality);
                        }
                    }
                    else
                    {
                    	$error++;
                    	$langs->load("errors");
                    	setEventMessages($langs->transnoentitiesnoconv("ErrorFailedToCreateDir", $dir), $mesgs, 'errors');
                    }
                }
            }

            if (! $error && ! count($object->errors))
            {
	            setEventMessage($langs->trans("UserModified"));
                $db->commit();

                $login=$_SESSION["dol_login"];
                if ($login && $login == $object->oldcopy->login && $object->oldcopy->login != $object->login)	// Current user has changed its login
                {
                	$_SESSION["dol_login"]=$object->login;	// Set new login to avoid disconnect at next page
                }
            }
            else
            {
                $db->rollback();
            }
        }
    }
    else if ($caneditpassword)	// Case we can edit only password
    {
        $object->fetch($id);

		$object->oldcopy = clone $object;

        $ret=$object->setPassword($user,$_POST["password"]);
        if ($ret < 0)
        {
	        setEventMessage($object->error, 'errors');
        }
    }
}

// Change password with a new generated one
if ((($action == 'confirm_password' && $confirm == 'yes')
|| ($action == 'confirm_passwordsend' && $confirm == 'yes')) && $caneditpassword)
{
    $object->fetch($id);

    $newpassword=$object->setPassword($user,'');
    if ($newpassword < 0)
    {
        // Echec
        setEventMessage($langs->trans("ErrorFailedToSetNewPassword"), 'errors');
    }
    else
    {
        // Succes
        if ($action == 'confirm_passwordsend' && $confirm == 'yes')
        {
            if ($object->send_password($user,$newpassword) > 0)
            {
                setEventMessage($langs->trans("PasswordChangedAndSentTo",$object->email));
            }
            else
            {
	            setEventMessage($object->error, 'errors');
            }
        }
        else
        {
	        setEventMessage($langs->trans("PasswordChangedTo",$newpassword), 'errors');
        }
    }
}

// Action initialisation donnees depuis record LDAP
if ($action == 'adduserldap')
{
    $selecteduser = $_POST['users'];

    $required_fields = array(
	$conf->global->LDAP_KEY_USERS,
    $conf->global->LDAP_FIELD_NAME,
    $conf->global->LDAP_FIELD_FIRSTNAME,
    $conf->global->LDAP_FIELD_LOGIN,
    $conf->global->LDAP_FIELD_LOGIN_SAMBA,
    $conf->global->LDAP_FIELD_PASSWORD,
    $conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
    $conf->global->LDAP_FIELD_PHONE,
    $conf->global->LDAP_FIELD_FAX,
    $conf->global->LDAP_FIELD_MOBILE,
    $conf->global->LDAP_FIELD_SKYPE,
    $conf->global->LDAP_FIELD_MAIL,
    $conf->global->LDAP_FIELD_TITLE,
	$conf->global->LDAP_FIELD_DESCRIPTION,
    $conf->global->LDAP_FIELD_SID);

    $ldap = new Ldap();
    $result = $ldap->connect_bind();
    if ($result >= 0)
    {
        // Remove from required_fields all entries not configured in LDAP (empty) and duplicated
        $required_fields=array_unique(array_values(array_filter($required_fields, "dol_validElement")));

        $ldapusers = $ldap->getRecords($selecteduser, $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields);
        //print_r($ldapusers);

        if (is_array($ldapusers))
        {
            foreach ($ldapusers as $key => $attribute)
            {
                $ldap_lastname		= $attribute[$conf->global->LDAP_FIELD_NAME];
                $ldap_firstname		= $attribute[$conf->global->LDAP_FIELD_FIRSTNAME];
                $ldap_login			= $attribute[$conf->global->LDAP_FIELD_LOGIN];
                $ldap_loginsmb		= $attribute[$conf->global->LDAP_FIELD_LOGIN_SAMBA];
                $ldap_pass			= $attribute[$conf->global->LDAP_FIELD_PASSWORD];
                $ldap_pass_crypted	= $attribute[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED];
                $ldap_phone			= $attribute[$conf->global->LDAP_FIELD_PHONE];
                $ldap_fax			= $attribute[$conf->global->LDAP_FIELD_FAX];
                $ldap_mobile		= $attribute[$conf->global->LDAP_FIELD_MOBILE];
                $ldap_skype			= $attribute[$conf->global->LDAP_FIELD_SKYPE];
                $ldap_mail			= $attribute[$conf->global->LDAP_FIELD_MAIL];
                $ldap_sid			= $attribute[$conf->global->LDAP_FIELD_SID];
            }
        }
    }
    else
    {
        setEventMessage($ldap->error, 'errors');
    }
}



/*
 * View
 */

$form = new Form($db);
$formother=new FormOther($db);

llxHeader('',$langs->trans("UserCard"));

if (($action == 'create') || ($action == 'adduserldap'))
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode creation                                           */
    /*                                                                            */
    /* ************************************************************************** */

    print load_fiche_titre($langs->trans("NewUser"));

    print $langs->trans("CreateInternalUserDesc")."<br>\n";
    print "<br>";


    if (! empty($conf->ldap->enabled) && (isset($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr'))
    {
        /*
         * Affiche formulaire d'ajout d'un compte depuis LDAP
         * si on est en synchro LDAP vers Dolibarr
         */

        $ldap = new Ldap();
        $result = $ldap->connect_bind();
        if ($result >= 0)
        {
            $required_fields=array(
				$conf->global->LDAP_KEY_USERS,
	            $conf->global->LDAP_FIELD_FULLNAME,
				$conf->global->LDAP_FIELD_NAME,
				$conf->global->LDAP_FIELD_FIRSTNAME,
				$conf->global->LDAP_FIELD_LOGIN,
				$conf->global->LDAP_FIELD_LOGIN_SAMBA,
				$conf->global->LDAP_FIELD_PASSWORD,
				$conf->global->LDAP_FIELD_PASSWORD_CRYPTED,
				$conf->global->LDAP_FIELD_PHONE,
				$conf->global->LDAP_FIELD_FAX,
				$conf->global->LDAP_FIELD_MOBILE,
				$conf->global->LDAP_FIELD_SKYPE,
				$conf->global->LDAP_FIELD_MAIL,
				$conf->global->LDAP_FIELD_TITLE,
				$conf->global->LDAP_FIELD_DESCRIPTION,
            	$conf->global->LDAP_FIELD_SID
            );

            // Remove from required_fields all entries not configured in LDAP (empty) and duplicated
            $required_fields=array_unique(array_values(array_filter($required_fields, "dol_validElement")));

            // Get from LDAP database an array of results
            $ldapusers = $ldap->getRecords('*', $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields, 1);

            if (is_array($ldapusers))
            {
                $liste=array();
                foreach ($ldapusers as $key => $ldapuser)
                {
                    // Define the label string for this user
                    $label='';
                    foreach ($required_fields as $value)
                    {
                        if ($value)
                        {
                            $label.=$value."=".$ldapuser[$value]." ";
                        }
                    }
                    $liste[$key] = $label;
                }

            }
            else
            {
                setEventMessage($ldap->error, 'errors');
            }
        }
        else
        {
	        setEventMessage($ldap->error, 'errors');
        }

        // If user list is full, we show drop-down list
       	print "\n\n<!-- Form liste LDAP debut -->\n";

       	print '<form name="add_user_ldap" action="'.$_SERVER["PHP_SELF"].'" method="post">';
       	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
       	print '<table width="100%" class="border"><tr>';
       	print '<td width="160">';
       	print $langs->trans("LDAPUsers");
       	print '</td>';
       	print '<td>';
       	print '<input type="hidden" name="action" value="adduserldap">';
        if (is_array($liste) && count($liste))
        {
        	print $form->selectarray('users', $liste, '', 1);
        }
       	print '</td><td align="center">';
       	print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Get')).'"'.(count($liste)?'':' disabled').'>';
       	print '</td></tr></table>';
       	print '</form>';

       	print "\n<!-- Form liste LDAP fin -->\n\n";
       	print '<br>';
    }


    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createuser">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    if (! empty($ldap_sid)) print '<input type="hidden" name="ldap_sid" value="'.dol_escape_htmltag($ldap_sid).'">';
    print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

    dol_fiche_head('', '', '', 0, '');

    print dol_set_focus('#lastname');

    print '<table class="border" width="100%">';

    print '<tr>';

    // Lastname
    print '<td width="160"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
    print '<td>';
    if (! empty($ldap_lastname))
    {
        print '<input type="hidden" id="lastname" name="lastname" value="'.$ldap_lastname.'">';
        print $ldap_lastname;
    }
    else
    {
        print '<input size="30" type="text" id="lastname" name="lastname" value="'.GETPOST('lastname').'">';
    }
    print '</td></tr>';

    // Firstname
    print '<tr><td>'.$langs->trans("Firstname").'</td>';
    print '<td>';
    if (! empty($ldap_firstname))
    {
        print '<input type="hidden" name="firstname" value="'.$ldap_firstname.'">';
        print $ldap_firstname;
    }
    else
    {
        print '<input size="30" type="text" name="firstname" value="'.GETPOST('firstname').'">';
    }
    print '</td></tr>';

    // Position/Job
    print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
    print '<td>';
    print '<input size="30" type="text" name="job" value="'.GETPOST('job').'">';
    print '</td></tr>';

    // Gender
    print '<tr><td>'.$langs->trans("Gender").'</td>';
    print '<td>';
    $arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
    print $form->selectarray('gender', $arraygender, GETPOST('gender'), 1);
    print '</td></tr>';

    // Login
    print '<tr><td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
    print '<td>';
    if (! empty($ldap_login))
    {
        print '<input type="hidden" name="login" value="'.$ldap_login.'">';
        print $ldap_login;
    }
    elseif (! empty($ldap_loginsmb))
    {
        print '<input type="hidden" name="login" value="'.$ldap_loginsmb.'">';
        print $ldap_loginsmb;
    }
    else
    {
        print '<input size="20" maxsize="24" type="text" name="login" value="'.GETPOST('login').'">';
    }
    print '</td></tr>';

    $generated_password='';
    if (empty($ldap_sid))    // ldap_sid is for activedirectory
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
        $generated_password=getRandomPassword(false);
    }
    $password=$generated_password;

    // Password
    print '<tr><td class="fieldrequired">'.$langs->trans("Password").'</td>';
    print '<td>';
    if (! empty($ldap_sid))
    {
        print 'Mot de passe du domaine';
    }
    else
    {
        if (! empty($ldap_pass))
        {
            print '<input type="hidden" name="password" value="'.$ldap_pass.'">';
            print preg_replace('/./i','*',$ldap_pass);
        }
        else
        {
            // We do not use a field password but a field text to show new password to use.
            print '<input size="30" maxsize="32" type="text" name="password" value="'.$password.'" autocomplete="off">';
        }
    }
    print '</td></tr>';

    if(! empty($conf->api->enabled))
    {
        // API key
        $generated_api_key = '';
        require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
            $generated_password=getRandomPassword(false);
        print '<tr><td>'.$langs->trans("ApiKey").'</td>';
        print '<td>';
        print '<input size="30" maxsize="32" type="text" id="api_key" name="api_key" value="'.$api_key.'" autocomplete="off">';
        if (! empty($conf->use_javascript_ajax))
            print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject"');
        print '</td></tr>';
    }
    else
    {
    	require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
        // PARTIAL WORKAROUND
        $generated_fake_api_key=getRandomPassword(false);
        print '<input type="hidden" name="api_key" value="'.$generated_fake_api_key.'">';
    }

    // Administrator
    if (! empty($user->admin))
    {
        print '<tr><td>'.$langs->trans("Administrator").'</td>';
        print '<td>';
        print $form->selectyesno('admin',GETPOST('admin'),1);

        if (! empty($conf->multicompany->enabled) && ! $user->entity && empty($conf->multicompany->transverse_mode))
        {
            if (! empty($conf->use_javascript_ajax))
            {
                print '<script type="text/javascript">
                            $(function() {
                                $("select[name=admin]").change(function() {
                                     if ( $(this).val() == 0 ) {
                                        $("input[name=superadmin]")
                                            .prop("disabled", true)
                                            .prop("checked", false);
                                        $("select[name=entity]")
                                            .prop("disabled", false);
                                     } else {
                                        $("input[name=superadmin]")
                                            .prop("disabled", false);
                                     }
                                });
                                $("input[name=superadmin]").change(function() {
                                    if ( $(this).is(":checked") ) {
                                        $("select[name=entity]")
                                            .prop("disabled", true);
                                    } else {
                                        $("select[name=entity]")
                                            .prop("disabled", false);
                                    }
                                });
                            });
                    </script>';
            }
            $checked=($_POST["superadmin"]?' checked':'');
            $disabled=($_POST["superadmin"]?'':' disabled');
            print '<input type="checkbox" name="superadmin" value="1"'.$checked.$disabled.' /> '.$langs->trans("SuperAdministrator");
        }
        print "</td></tr>\n";
    }

    // Type
    print '<tr><td>'.$langs->trans("Type").'</td>';
    print '<td>';
    print $form->textwithpicto($langs->trans("Internal"),$langs->trans("InternalExternalDesc"), 1, 'help', '', 0, 2);
    print '</td></tr>';

    // Tel
    print '<tr><td>'.$langs->trans("PhonePro").'</td>';
    print '<td>';
    if (! empty($ldap_phone))
    {
        print '<input type="hidden" name="office_phone" value="'.$ldap_phone.'">';
        print $ldap_phone;
    }
    else
    {
        print '<input size="20" type="text" name="office_phone" value="'.GETPOST('office_phone').'">';
    }
    print '</td></tr>';

    // Tel portable
    print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
    print '<td>';
    if (! empty($ldap_mobile))
    {
        print '<input type="hidden" name="user_mobile" value="'.$ldap_mobile.'">';
        print $ldap_mobile;
    }
    else
    {
        print '<input size="20" type="text" name="user_mobile" value="'.GETPOST('user_mobile').'">';
    }
    print '</td></tr>';

    // Fax
    print '<tr><td>'.$langs->trans("Fax").'</td>';
    print '<td>';
    if (! empty($ldap_fax))
    {
        print '<input type="hidden" name="office_fax" value="'.$ldap_fax.'">';
        print $ldap_fax;
    }
    else
    {
        print '<input size="20" type="text" name="office_fax" value="'.GETPOST('office_fax').'">';
    }
    print '</td></tr>';

    // Skype
    if (! empty($conf->skype->enabled))
    {
        print '<tr><td>'.$langs->trans("Skype").'</td>';
        print '<td>';
        if (! empty($ldap_skype))
        {
            print '<input type="hidden" name="skype" value="'.$ldap_skype.'">';
            print $ldap_skype;
        }
        else
        {
            print '<input size="40" type="text" name="skype" value="'.GETPOST('skype').'">';
        }
        print '</td></tr>';
    }

    // EMail
    print '<tr><td'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
    print '<td>';
    if (! empty($ldap_mail))
    {
        print '<input type="hidden" name="email" value="'.$ldap_mail.'">';
        print $ldap_mail;
    }
    else
    {
        print '<input size="40" type="text" name="email" value="'.GETPOST('email').'">';
    }
    print '</td></tr>';

    // Signature
    print '<tr><td class="tdtop">'.$langs->trans("Signature").'</td>';
    print '<td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('signature',GETPOST('signature'),'',138,'dolibarr_mailings','In',true,true,empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1,ROWS_4,90);
    print $doleditor->Create(1);
    print '</td></tr>';

    // Multicompany
    if (! empty($conf->multicompany->enabled))
    {
        if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity && is_object($mc))
        {
            print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
            print "<td>".$mc->select_entities($conf->entity);
            print "</td></tr>\n";
        }
        else
        {
            print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
        }
    }

    // Hierarchy
    print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
    print '<td>';
    print $form->select_dolusers($object->fk_user,'fk_user',1,array($object->id),0,'',0,$conf->entity);
    print '</td>';
    print "</tr>\n";

	if ($conf->salaries->enabled && ! empty($user->rights->salaries->read))
	{
		$langs->load("salaries");

	    // THM
	    print '<tr><td>'.$langs->trans("THM").'</td>';
	    print '<td>';
	    print '<input size="8" type="text" name="thm" value="'.GETPOST('thm').'">';
	    print '</td>';
	    print "</tr>\n";

	    // TJM
	    print '<tr><td>'.$langs->trans("TJM").'</td>';
	    print '<td>';
	    print '<input size="8" type="text" name="tjm" value="'.GETPOST('tjm').'">';
	    print '</td>';
	    print "</tr>\n";

	    // Salary
	    print '<tr><td>'.$langs->trans("Salary").'</td>';
	    print '<td>';
	    print '<input size="8" type="text" name="salary" value="'.GETPOST('salary').'">';
	    print '</td>';
	    print "</tr>\n";
	}

    // Weeklyhours
    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
    print '<td>';
    print '<input size="8" type="text" name="weeklyhours" value="'.GETPOST('weeklyhours').'">';
    print '</td>';
    print "</tr>\n";

	// Accountancy code
	if ($conf->salaries->enabled)
	{
		print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		print '<input size="30" type="text" name="accountancy_code" value="'.GETPOST('accountancy_code').'">';
		print '</td></tr>';
	}

	// User color
	if (! empty($conf->agenda->enabled))
	{
		print '<tr><td>'.$langs->trans("ColorUser").'</td>';
		print '<td>';
		print $formother->selectColor(GETPOST('color')?GETPOST('color'):$object->color, 'color', null, 1, '', 'hideifnotset');
		print '</td></tr>';
	}

    // Note
    print '<tr><td class="tdtop">';
    print $langs->trans("Note");
    print '</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('note','','',180,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_4,90);
    $doleditor->Create();
    print "</td></tr>\n";

    // Other attributes
    $parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
    	print $object->showOptionals($extrafields,'edit');
    }

 	print "</table>\n";

 	dol_fiche_end();

    print '<div align="center">';
    print '<input class="button" value="'.$langs->trans("CreateUser").'" name="create" type="submit">';
    //print '&nbsp; &nbsp; &nbsp;';
    //print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
    print '</div>';

    print "</form>";
}
else
{
    /* ************************************************************************** */
    /*                                                                            */
    /* View and edition                                                            */
    /*                                                                            */
    /* ************************************************************************** */

    if ($id > 0)
    {
        $object->fetch($id);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals($object->id,$extralabels);

        // Connexion ldap
        // pour recuperer passDoNotExpire et userChangePassNextLogon
        if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid))
        {
            $ldap = new Ldap();
            $result=$ldap->connect_bind();
            if ($result > 0)
            {
                $userSearchFilter = '('.$conf->global->LDAP_FILTER_CONNECTION.'('.$ldap->getUserIdentifier().'='.$object->login.'))';
                $entries = $ldap->fetch($object->login,$userSearchFilter);
                if (! $entries)
                {
                    setEventMessage($ldap->error, 'errors');
                }

                $passDoNotExpire = 0;
                $userChangePassNextLogon = 0;
                $userDisabled = 0;
                $statutUACF = '';

                // Check options of user account
                if (count($ldap->uacf) > 0)
                {
                    foreach ($ldap->uacf as $key => $statut)
                    {
                        if ($key == 65536)
                        {
                            $passDoNotExpire = 1;
                            $statutUACF = $statut;
                        }
                    }
                }
                else
                {
                    $userDisabled = 1;
                    $statutUACF = "ACCOUNTDISABLE";
                }

                if ($ldap->pwdlastset == 0)
                {
                    $userChangePassNextLogon = 1;
                }
            }
        }

        // Show tabs
        $head = user_prepare_head($object);
        $title = $langs->trans("User");

        /*
         * Confirmation reinitialisation mot de passe
         */
        if ($action == 'password')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$object->login),"confirm_password", '', 0, 1);
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$object->login),"confirm_passwordsend", '', 0, 1);
        }

        /*
         * Confirm deactivation
         */
        if ($action == 'disable')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$object->login),"confirm_disable", '', 0, 1);
        }

        /*
         * Confirm activation
         */
        if ($action == 'enable')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$object->login),"confirm_enable", '', 0, 1);
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            print $form->formconfirm("card.php?id=$object->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$object->login),"confirm_delete", '', 0, 1);
        }

        /*
         * Fiche en mode visu
         */
        if ($action != 'edit')
        {
			dol_fiche_head($head, 'user', $title, 0, 'user');

            $rowspan=19;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
            print '<td colspan="3">';
            print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
            print '</td>';
            print '</tr>'."\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
            if (! empty($conf->societe->enabled)) $rowspan++;
            if (! empty($conf->adherent->enabled)) $rowspan++;
            if (! empty($conf->skype->enabled)) $rowspan++;
			if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read)) $rowspan = $rowspan+3;
			if (! empty($conf->agenda->enabled)) $rowspan++;

            // Lastname
            print '<tr><td>'.$langs->trans("Lastname").'</td>';
            print '<td colspan="2">'.$object->lastname.'</td>';

            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $form->showphoto('userphoto',$object,100);
            print '</td>';

            print '</tr>'."\n";

            // Firstname
            print '<tr><td>'.$langs->trans("Firstname").'</td>';
            print '<td colspan="2">'.$object->firstname.'</td>';
            print '</tr>'."\n";

            // Position/Job
            print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
            print '<td colspan="2">'.$object->job.'</td>';
            print '</tr>'."\n";

            // Gender
		    print '<tr><td>'.$langs->trans("Gender").'</td>';
		    print '<td>';
		    if ($object->gender) print $langs->trans("Gender".$object->gender);
		    print '</td></tr>';

            // Login
            print '<tr><td>'.$langs->trans("Login").'</td>';
            if (! empty($object->ldap_sid) && $object->statut==0)
            {
                print '<td colspan="2" class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
            }
            else
            {
                print '<td colspan="2">'.$object->login.'</td>';
            }
            print '</tr>'."\n";

            // Password
            print '<tr><td>'.$langs->trans("Password").'</td>';
            if (! empty($object->ldap_sid))
            {
                if ($passDoNotExpire)
                {
                    print '<td colspan="2">'.$langs->trans("LdapUacf_".$statutUACF).'</td>';
                }
                else if($userChangePassNextLogon)
                {
                    print '<td colspan="2" class="warning">'.$langs->trans("UserMustChangePassNextLogon",$ldap->domainFQDN).'</td>';
                }
                else if($userDisabled)
                {
                    print '<td colspan="2" class="warning">'.$langs->trans("LdapUacf_".$statutUACF,$ldap->domainFQDN).'</td>';
                }
                else
                {
                    print '<td colspan="2">'.$langs->trans("DomainPassword").'</td>';
                }
            }
            else
            {
                print '<td colspan="2">';
                if ($object->pass) print preg_replace('/./i','*',$object->pass);
                else
                {
                    if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
                    else print $langs->trans("Hidden");
                }
                print "</td>";
            }
            print '</tr>'."\n";

            // API key
            if(! empty($conf->api->enabled) && $user->admin) {
                print '<tr><td>'.$langs->trans("ApiKey").'</td>';
                print '<td colspan="2">';
                if (! empty($object->api_key))
                    print $langs->trans("Hidden");
                print '<td>';
            }

            // Administrator
            print '<tr><td>'.$langs->trans("Administrator").'</td><td colspan="2">';
            if (! empty($conf->multicompany->enabled) && $object->admin && ! $object->entity)
            {
                print $form->textwithpicto(yn($object->admin),$langs->trans("SuperAdministratorDesc"),1,"superadmin");
            }
            else if ($object->admin)
            {
                print $form->textwithpicto(yn($object->admin),$langs->trans("AdministratorDesc"),1,"admin");
            }
            else
            {
                print yn($object->admin);
            }
            print '</td></tr>'."\n";

            // Type
            print '<tr><td>';
            $text=$langs->trans("Type");
            print $form->textwithpicto($text, $langs->trans("InternalExternalDesc"));
            print '</td><td colspan="2">';
            $type=$langs->trans("Internal");
            if ($object->societe_id > 0) $type=$langs->trans("External");
			print $type;
            if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
            print '</td></tr>'."\n";

            // Ldap sid
            if ($object->ldap_sid)
            {
            	print '<tr><td>'.$langs->trans("Type").'</td><td colspan="2">';
            	print $langs->trans("DomainUser",$ldap->domainFQDN);
            	print '</td></tr>'."\n";
            }

            // Tel pro
            print '<tr><td>'.$langs->trans("PhonePro").'</td>';
            print '<td colspan="2">'.dol_print_phone($object->office_phone,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Tel mobile
            print '<tr><td>'.$langs->trans("PhoneMobile").'</td>';
            print '<td colspan="2">'.dol_print_phone($object->user_mobile,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Fax
            print '<tr><td>'.$langs->trans("Fax").'</td>';
            print '<td colspan="2">'.dol_print_phone($object->office_fax,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Skype
            if (! empty($conf->skype->enabled))
            {
				print '<tr><td>'.$langs->trans("Skype").'</td>';
                print '<td colspan="2">'.dol_print_skype($object->skype,0,0,1).'</td>';
                print "</tr>\n";
            }

            // EMail
            print '<tr><td>'.$langs->trans("EMail").'</td>';
            print '<td colspan="2">'.dol_print_email($object->email,0,0,1).'</td>';
            print "</tr>\n";

            // Signature
            print '<tr><td class="tdtop">'.$langs->trans('Signature').'</td><td colspan="2">';
            print dol_htmlentitiesbr($object->signature);
            print "</td></tr>\n";

            // Hierarchy
            print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
            print '<td colspan="2">';
            if (empty($object->fk_user)) print $langs->trans("None");
            else {
            	$huser=new User($db);
            	$huser->fetch($object->fk_user);
            	print $huser->getNomUrl(1);
            }
            print '</td>';
            print "</tr>\n";

            if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
            {
            	$langs->load("salaries");

	            // THM
			    print '<tr><td>';
			    $text=$langs->trans("THM");
			    print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
			    print '</td>';
			    print '<td colspan="2">';
			    print ($object->thm!=''?price($object->thm,'',$langs,1,-1,-1,$conf->currency):'');
			    print '</td>';
			    print "</tr>\n";

	            // TJM
			    print '<tr><td>';
			    $text=$langs->trans("TJM");
			    print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
			    print '</td>';
			    print '<td colspan="2">';
			    print ($object->tjm!=''?price($object->tjm,'',$langs,1,-1,-1,$conf->currency):'');
			    print '</td>';
			    print "</tr>\n";

			    // Salary
			    print '<tr><td>'.$langs->trans("Salary").'</td>';
			    print '<td colspan="2">';
			    print ($object->salary!=''?price($object->salary,'',$langs,1,-1,-1,$conf->currency):'');
			    print '</td>';
			    print "</tr>\n";
            }

		    // Weeklyhours
		    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
		    print '<td colspan="2">';
			print price2num($object->weeklyhours);
		    print '</td>';
		    print "</tr>\n";

			// Accountancy code
			if ($conf->salaries->enabled)
			{
				print '<tr><td>'.$langs->trans("AccountancyCode").'</td>';
				print '<td colspan="2">'.$object->accountancy_code.'</td>';
			}

			// Color user
			if (! empty($conf->agenda->enabled))
            {
				print '<tr><td>'.$langs->trans("ColorUser").'</td>';
				print '<td colspan="2">';
				print $formother->showColor($object->color, '');
				print '</td>';
				print "</tr>\n";
			}

            // Status
            print '<tr><td>'.$langs->trans("Status").'</td>';
            print '<td colspan="2">';
            print $object->getLibStatut(4);
            print '</td>';
            print '</tr>'."\n";

            print '<tr><td>'.$langs->trans("LastConnexion").'</td>';
            print '<td colspan="2">'.dol_print_date($object->datelastlogin,"dayhour").'</td>';
            print "</tr>\n";

            print '<tr><td>'.$langs->trans("PreviousConnexion").'</td>';
            print '<td colspan="2">'.dol_print_date($object->datepreviouslogin,"dayhour").'</td>';
            print "</tr>\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
            {
                print '<tr><td>'.$langs->trans("OpenIDURL").'</td>';
                print '<td colspan="2">'.$object->openid.'</td>';
                print "</tr>\n";
            }

            // Company / Contact
            if (! empty($conf->societe->enabled))
            {
                print '<tr><td>'.$langs->trans("LinkToCompanyContact").'</td>';
                print '<td colspan="2">';
                if (isset($object->societe_id) && $object->societe_id > 0)
                {
                    $societe = new Societe($db);
                    $societe->fetch($object->societe_id);
                    print $societe->getNomUrl(1,'');
                }
                else
                {
                    print $langs->trans("ThisUserIsNot");
                }
                if (! empty($object->contact_id))
                {
                    $contact = new Contact($db);
                    $contact->fetch($object->contact_id);
                    if ($object->societe_id > 0) print ' / ';
                    else print '<br>';
                    print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
                }
                print '</td>';
                print '</tr>'."\n";
            }

            // Module Adherent
            if (! empty($conf->adherent->enabled))
            {
                $langs->load("members");
                print '<tr><td>'.$langs->trans("LinkedToDolibarrMember").'</td>';
                print '<td colspan="2">';
                if ($object->fk_member)
                {
                    $adh=new Adherent($db);
                    $adh->fetch($object->fk_member);
                    $adh->ref=$adh->getFullname($langs);	// Force to show login instead of id
                    print $adh->getNomUrl(1);
                }
                else
                {
                    print $langs->trans("UserNotLinkedToMember");
                }
                print '</td>';
                print '</tr>'."\n";
            }

            // Multicompany
            // TODO This should be done with hook formObjectOption
            if (is_object($mc))
            {
	            if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
	            {
	            	print '<tr><td>'.$langs->trans("Entity").'</td><td width="75%" class="valeur">';
	            	if (empty($object->entity))
	            	{
	            		print $langs->trans("AllEntities");
	            	}
	            	else
	            	{
	            		$mc->getInfo($object->entity);
	            		print $mc->label;
	            	}
	            	print "</td></tr>\n";
	            }
            }

          	// Other attributes
			$parameters=array('colspan' => ' colspan="2"');
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
			if (empty($reshook) && ! empty($extrafields->attribute_label))
			{
				print $object->showOptionals($extrafields);
			}

			print "</table>\n";

            dol_fiche_end();


            /*
             * Buttons actions
             */

            print '<div class="tabsAction">';

            if ($caneditfield && (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
                if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
                {
                    print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("DisabledInMonoUserMode")).'">'.$langs->trans("Modify").'</a></div>';
                }
                else
                {
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a></div>';
                }
            }
            elseif ($caneditpassword && ! $object->ldap_sid &&
            (empty($conf->multicompany->enabled) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("EditPassword").'</a></div>';
            }

            // Si on a un gestionnaire de generation de mot de passe actif
            if ($conf->global->USER_PASSWORD_GENERATED != 'none')
            {
				if ($object->statut == 0)
				{
	                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("ReinitPassword").'</a></div>';
				}
                elseif (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
                ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
                {
                    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a></div>';
                }

				if ($object->statut == 0)
				{
	                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("UserDisabled")).'">'.$langs->trans("SendNewPassword").'</a></div>';
				}
                else if (($user->id != $id && $caneditpassword) && $object->login && !$object->ldap_sid &&
                ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
                {
                    if ($object->email) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a></div>';
                    else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendNewPassword").'</a></div>';
                }
            }

            // Activer
            if ($user->id <> $id && $candisableuser && $object->statut == 0 &&
            ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a></div>';
            }
            // Desactiver
            if ($user->id <> $id && $candisableuser && $object->statut == 1 &&
            ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=disable&amp;id='.$object->id.'">'.$langs->trans("DisableUser").'</a></div>';
            }
            // Delete
            if ($user->id <> $id && $candisableuser &&
            ((empty($conf->multicompany->enabled) && $object->entity == $user->entity) || ! $user->entity || ($object->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
            {
            	if ($user->admin || ! $object->admin) // If user edited is admin, delete is possible on for an admin
            	{
                	print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteUser").'</a></div>';
            	}
            	else
            	{
            		print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("MustBeAdminToDeleteOtherAdmin")).'">'.$langs->trans("DeleteUser").'</a></div>';
            	}
            }

            print "</div>\n";
            print "<br>\n";



            /*
             * Liste des groupes dans lequel est l'utilisateur
             */

            if ($canreadgroup)
            {
                print load_fiche_titre($langs->trans("ListOfGroupsForUser"),'','');

                // On selectionne les groupes auquel fait parti le user
                $exclude = array();

                $usergroup=new UserGroup($db);
                $groupslist = $usergroup->listGroupsForUser($object->id);

                if (! empty($groupslist))
                {
                    if (! (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)))
                    {
                        foreach($groupslist as $groupforuser)
                        {
                            $exclude[]=$groupforuser->id;
                        }
                    }
                }

                if ($caneditgroup)
                {
                    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
                    print '<input type="hidden" name="action" value="addgroup" />';
                    print '<table class="noborder" width="100%">'."\n";
                    print '<tr class="liste_titre"><th class="liste_titre" width="25%">'.$langs->trans("GroupsToAdd").'</th>'."\n";
                    print '<th>';
                    print $form->select_dolgroups('', 'group', 1, $exclude, 0, '', '', $object->entity);
                    print ' &nbsp; ';
                    // Multicompany
                    if (! empty($conf->multicompany->enabled))
                    {
                        if ($conf->entity == 1 && $conf->multicompany->transverse_mode)
                        {
                            print '</td><td>'.$langs->trans("Entity").'</td>';
                            print "<td>".$mc->select_entities($conf->entity);
                        }
                        else
                        {
                            print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
                        }
                    }
                    else
                    {
                    	print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
                    }
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'" />';
                    print '</th></tr>'."\n";
                    print '</table></form>'."\n";

                    print '<br>';
                }

                /*
                 * Groups assigned to user
                 */
                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td class="liste_titre" width="25%">'.$langs->trans("Groups").'</td>';
                if(! empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                {
                	print '<td class="liste_titre" width="25%">'.$langs->trans("Entity").'</td>';
                }
                print "<td>&nbsp;</td></tr>\n";

                if (! empty($groupslist))
                {
                    $var=true;

                    foreach($groupslist as $group)
                    {
                        $var=!$var;

                        print "<tr ".$bc[$var].">";
                        print '<td>';
                        if ($caneditgroup)
                        {
                            print '<a href="'.DOL_URL_ROOT.'/user/group/card.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
                        }
                        else
                        {
                            print img_object($langs->trans("ShowGroup"),"group").' '.$group->name;
                        }
                        print '</td>';
                        if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                        {
                        	print '<td class="valeur">';
                        	if (! empty($group->usergroup_entity))
                        	{
                        		$nb=0;
                        		foreach($group->usergroup_entity as $group_entity)
                        		{
                        			$mc->getInfo($group_entity);
                        			print ($nb > 0 ? ', ' : '').$mc->label;
                        			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'&amp;entity='.$group_entity.'">';
                        			print img_delete($langs->trans("RemoveFromGroup"));
                        			print '</a>';
                        			$nb++;
                        		}
                        	}
                        }
                        print '<td align="right">';
                        if ($caneditgroup && empty($conf->multicompany->transverse_mode))
                        {
                            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegroup&amp;group='.$group->id.'">';
                            print img_delete($langs->trans("RemoveFromGroup"));
                            print '</a>';
                        }
                        else
                        {
                            print "&nbsp;";
                        }
                        print "</td></tr>\n";
                    }
                }
                else
                {
                    print '<tr '.$bc[false].'><td colspan="3">'.$langs->trans("None").'</td></tr>';
                }

                print "</table>";
                print "<br>";
            }
        }

        /*
         * Fiche en mode edition
         */
        if ($action == 'edit' && ($canedituser || $caneditfield || $caneditpassword || ($user->id == $object->id)))
        {
        	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="entity" value="'.$object->entity.'">';

            dol_fiche_head($head, 'user', $title, 0, 'user');

        	$rowspan=17;
            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
            if (! empty($conf->societe->enabled)) $rowspan++;
            if (! empty($conf->adherent->enabled)) $rowspan++;
			if (! empty($conf->skype->enabled)) $rowspan++;
			if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read)) $rowspan = $rowspan+3;
			if (! empty($conf->agenda->enabled)) $rowspan++;

            print '<table width="100%" class="border">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $object->id;
            print '</td>';
            print '</tr>';

            // Lastname
            print "<tr>";
            print '<td class="fieldrequired">'.$langs->trans("Lastname").'</td>';
            print '<td>';
            if ($caneditfield && !$object->ldap_sid)
            {
                print '<input size="30" type="text" class="flat" name="lastname" value="'.$object->lastname.'">';
            }
            else
            {
                print '<input type="hidden" name="lastname" value="'.$object->lastname.'">';
                print $object->lastname;
            }
            print '</td>';

            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $form->showphoto('userphoto',$object,100,0,$caneditfield);
            print '</td>';

            print '</tr>';

            // Firstname
            print "<tr>".'<td>'.$langs->trans("Firstname").'</td>';
            print '<td>';
            if ($caneditfield && !$object->ldap_sid)
            {
                print '<input size="30" type="text" class="flat" name="firstname" value="'.$object->firstname.'">';
            }
            else
            {
                print '<input type="hidden" name="firstname" value="'.$object->firstname.'">';
                print $object->firstname;
            }
            print '</td></tr>';

            // Position/Job
            print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
            print '<td>';
            if ($caneditfield)
            {
            	print '<input size="30" type="text" name="job" value="'.$object->job.'">';
            }
            else
			{
                print '<input type="hidden" name="job" value="'.$object->job.'">';
          		print $object->job;
            }
            print '</td></tr>';

		    // Gender
    		print '<tr><td>'.$langs->trans("Gender").'</td>';
    		print '<td>';
    		$arraygender=array('man'=>$langs->trans("Genderman"),'woman'=>$langs->trans("Genderwoman"));
    		print $form->selectarray('gender', $arraygender, GETPOST('gender')?GETPOST('gender'):$object->gender, 1);
    		print '</td></tr>';

            // Login
            print "<tr>".'<td><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
            print '<td>';
            if ($user->admin  && !$object->ldap_sid)
            {
                print '<input size="12" maxlength="24" type="text" class="flat" name="login" value="'.$object->login.'">';
            }
            else
            {
                print '<input type="hidden" name="login" value="'.$object->login.'">';
                print $object->login;
            }
            print '</td>';
            print '</tr>';

            // Pass
            print '<tr><td>'.$langs->trans("Password").'</td>';
            print '<td>';
            if ($object->ldap_sid)
            {
                $text=$langs->trans("DomainPassword");
            }
            else if ($caneditpassword)
            {
                $text='<input size="12" maxlength="32" type="password" class="flat" name="password" value="'.$object->pass.'" autocomplete="off">';
                if ($dolibarr_main_authentication && $dolibarr_main_authentication == 'http')
                {
                    $text=$form->textwithpicto($text,$langs->trans("DolibarrInHttpAuthenticationSoPasswordUseless",$dolibarr_main_authentication),1,'warning');
                }
            }
            else
            {
                $text=preg_replace('/./i','*',$object->pass);
            }
            print $text;
            print "</td></tr>\n";

            // API key
            if(! empty($conf->api->enabled) && $user->admin) {
                print '<tr><td>'.$langs->trans("ApiKey").'</td>';
                print '<td>';
                print '<input size="30" maxsize="32" type="text" id="api_key" name="api_key" value="'.$object->api_key.'" autocomplete="off">';
                if (! empty($conf->use_javascript_ajax))
                    print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_api_key" class="linkobject"');
                print '</td></tr>';
            }

            // Administrator
            print '<tr><td>'.$langs->trans("Administrator").'</td>';
            if ($object->societe_id > 0)
            {
            	$langs->load("admin");
                print '<td>';
                print '<input type="hidden" name="admin" value="'.$object->admin.'">'.yn($object->admin);
                print ' ('.$langs->trans("ExternalUser").')';
                print '</td></tr>';
            }
            else
            {
                print '<td>';
                $nbSuperAdmin = $user->getNbOfUsers('superadmin');
                if ($user->admin
                && ($user->id != $object->id)                    // Don't downgrade ourself
                && ($object->entity > 0 || $nbSuperAdmin > 1)    // Don't downgrade a superadmin if alone
                )
                {
                    print $form->selectyesno('admin',$object->admin,1);

                    if (! empty($conf->multicompany->enabled) && ! $user->entity && empty($conf->multicompany->transverse_mode))
                    {
                        if ($conf->use_javascript_ajax)
                        {
                            print '<script type="text/javascript">
									$(function() {
										var admin = $("select[name=admin]").val();
										if (admin == 0) {
											$("input[name=superadmin]")
													.prop("disabled", true)
													.prop("checked", false);
										}
										if ($("input[name=superadmin]").is(":checked")) {
											$("select[name=entity]")
													.prop("disabled", true);
										}
										$("select[name=admin]").change(function() {
											 if ( $(this).val() == 0 ) {
											 	$("input[name=superadmin]")
													.prop("disabled", true)
													.prop("checked", false);
											 	$("select[name=entity]")
													.prop("disabled", false);
											 } else {
											 	$("input[name=superadmin]")
													.prop("disabled", false);
											 }
										});
										$("input[name=superadmin]").change(function() {
											if ( $(this).is(":checked")) {
												$("select[name=entity]")
													.prop("disabled", true);
											} else {
												$("select[name=entity]")
													.prop("disabled", false);
											}
										});
									});
								</script>';
                        }

                        $checked=(($object->admin && ! $object->entity) ? ' checked' : '');
                        print '<input type="checkbox" name="superadmin" value="1"'.$checked.' /> '.$langs->trans("SuperAdministrator");
                    }
                }
                else
                {
                    $yn = yn($object->admin);
                    print '<input type="hidden" name="admin" value="'.$object->admin.'">';
                    print '<input type="hidden" name="superadmin" value="'.(empty($object->entity) ? 1 : 0).'">';
                    if (! empty($conf->multicompany->enabled) && empty($object->entity)) print $form->textwithpicto($yn,$langs->trans("DontDowngradeSuperAdmin"),1,'warning');
                    else print $yn;
                }
                print '</td></tr>';
            }

           	// Type
           	print '<tr><td width="25%">'.$langs->trans("Type").'</td>';
           	print '<td>';
           	if ($user->id == $object->id || ! $user->admin)
           	{
	           	$type=$langs->trans("Internal");
    	       	if ($object->societe_id) $type=$langs->trans("External");
        	   	print $form->textwithpicto($type,$langs->trans("InternalExternalDesc"));
	           	if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
           	}
           	else
			{
				$type=0;
	            if ($object->contact_id) $type=$object->contact_id;
	            print $form->selectcontacts(0,$type,'contactid',2,'','',1,'',false,1);
	           	if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
            }
           	print '</td></tr>';

            // Tel pro
            print "<tr>".'<td>'.$langs->trans("PhonePro").'</td>';
            print '<td>';
            if ($caneditfield  && empty($object->ldap_sid))
            {
                print '<input size="20" type="text" name="office_phone" class="flat" value="'.$object->office_phone.'">';
            }
            else
            {
                print '<input type="hidden" name="office_phone" value="'.$object->office_phone.'">';
                print $object->office_phone;
            }
            print '</td></tr>';

            // Tel mobile
            print "<tr>".'<td>'.$langs->trans("PhoneMobile").'</td>';
            print '<td>';
            if ($caneditfield && empty($object->ldap_sid))
            {
                print '<input size="20" type="text" name="user_mobile" class="flat" value="'.$object->user_mobile.'">';
            }
            else
            {
                print '<input type="hidden" name="user_mobile" value="'.$object->user_mobile.'">';
                print $object->user_mobile;
            }
            print '</td></tr>';

            // Fax
            print "<tr>".'<td>'.$langs->trans("Fax").'</td>';
            print '<td>';
            if ($caneditfield  && empty($object->ldap_sid))
            {
                print '<input size="20" type="text" name="office_fax" class="flat" value="'.$object->office_fax.'">';
            }
            else
            {
                print '<input type="hidden" name="office_fax" value="'.$object->office_fax.'">';
                print $object->office_fax;
            }
            print '</td></tr>';

            // Skype
            if (! empty($conf->skype->enabled))
            {
                print '<tr><td>'.$langs->trans("Skype").'</td>';
                print '<td>';
                if ($caneditfield  && empty($object->ldap_sid))
                {
                    print '<input size="40" type="text" name="skype" class="flat" value="'.$object->skype.'">';
                }
                else
                {
                    print '<input type="hidden" name="skype" value="'.$object->skype.'">';
                    print $object->skype;
                }
                print '</td></tr>';
            }

            // EMail
            print "<tr>".'<td'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
            print '<td>';
            if ($caneditfield  && empty($object->ldap_sid))
            {
                print '<input size="40" type="text" name="email" class="flat" value="'.$object->email.'">';
            }
            else
            {
                print '<input type="hidden" name="email" value="'.$object->email.'">';
                print $object->email;
            }
            print '</td></tr>';

            // Signature
            print "<tr>".'<td class="tdtop">'.$langs->trans("Signature").'</td>';
            print '<td>';
            if ($caneditfield)
            {
	            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	            $doleditor=new DolEditor('signature',$object->signature,'',138,'dolibarr_mailings','In',false,true,empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1,ROWS_4,72);
	            print $doleditor->Create(1);
            }
            else
			{
          		print dol_htmlentitiesbr($object->signature);
            }
            print '</td></tr>';

            // OpenID url
            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
            {
                print "<tr>".'<td>'.$langs->trans("OpenIDURL").'</td>';
                print '<td>';
                if ($caneditfield)
                {
                    print '<input size="40" type="url" name="openid" class="flat" value="'.$object->openid.'">';
                }
                else
              {
                    print '<input type="hidden" name="openid" value="'.$object->openid.'">';
                    print $object->openid;
                }
                print '</td></tr>';
            }

            // Hierarchy
            print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
            print '<td>';
            if ($caneditfield)
            {
            	print $form->select_dolusers($object->fk_user,'fk_user',1,array($object->id),0,'',0,$object->entity);
            }
            else
          {
          		print '<input type="hidden" name="fk_user" value="'.$object->fk_user.'">';
            	$huser=new User($db);
            	$huser->fetch($object->fk_user);
            	print $huser->getNomUrl(1);
            }
            print '</td>';
            print "</tr>\n";

            if (! empty($conf->salaries->enabled) && ! empty($user->rights->salaries->read))
            {
            	$langs->load("salaries");

            	// THM
			    print '<tr><td>';
			    $text=$langs->trans("THM");
			    print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
			    print '</td>';
			    print '<td>';
			    print '<input size="8" type="text" name="thm" value="'.price2num(GETPOST('thm')?GETPOST('thm'):$object->thm).'">';
			    print '</td>';
			    print "</tr>\n";

			    // TJM
			    print '<tr><td>';
			    $text=$langs->trans("TJM");
			    print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classthm');
			    print '</td>';
			    print '<td>';
			    print '<input size="8" type="text" name="tjm" value="'.price2num(GETPOST('tjm')?GETPOST('tjm'):$object->tjm).'">';
			    print '</td>';
			    print "</tr>\n";

			    // Salary
			    print '<tr><td>'.$langs->trans("Salary").'</td>';
			    print '<td>';
			    print '<input size="8" type="text" name="salary" value="'.price2num(GETPOST('salary')?GETPOST('salary'):$object->salary).'">';
			    print '</td>';
			    print "</tr>\n";
            }

		    // Weeklyhours
		    print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
		    print '<td>';
		    print '<input size="8" type="text" name="weeklyhours" value="'.price2num(GETPOST('weeklyhours')?GETPOST('weeklyhours'):$object->weeklyhours).'">';
		    print '</td>';
		    print "</tr>\n";

		    // Accountancy code
			if ($conf->salaries->enabled)
			{
				print "<tr>";
				print '<td>'.$langs->trans("AccountancyCode").'</td>';
				print '<td>';
				if ($caneditfield)
				{
					print '<input size="30" type="text" class="flat" name="accountancy_code" value="'.$object->accountancy_code.'">';
				}
				else
				{
					print '<input type="hidden" name="accountancy_code" value="'.$object->accountancy_code.'">';
					print $object->accountancy_code;
				}
				print '</td>';
				print "</tr>";
			}

			// User color
			if (! empty($conf->agenda->enabled))
            {
				print '<tr><td>'.$langs->trans("ColorUser").'</td>';
				print '<td>';
				print $formother->selectColor(GETPOST('color')?GETPOST('color'):$object->color, 'color', null, 1, '', 'hideifnotset');
				print '</td></tr>';
			}

            // Status
            print '<tr><td>'.$langs->trans("Status").'</td>';
            print '<td>';
            print $object->getLibStatut(4);
            print '</td></tr>';

            // Company / Contact
            if (! empty($conf->societe->enabled))
            {
                print '<tr><td width="25%">'.$langs->trans("LinkToCompanyContact").'</td>';
                print '<td>';
                if ($object->societe_id > 0)
                {
                    $societe = new Societe($db);
                    $societe->fetch($object->societe_id);
                    print $societe->getNomUrl(1,'');
                    if ($object->contact_id)
                    {
                        $contact = new Contact($db);
                        $contact->fetch($object->contact_id);
                        print ' / <a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
                    }
                }
                else
                {
                    print $langs->trans("ThisUserIsNot");
                }
                print ' ('.$langs->trans("UseTypeFieldToChange").')';
                print '</td>';
                print "</tr>\n";
            }

            // Module Adherent
            if (! empty($conf->adherent->enabled))
            {
                $langs->load("members");
                print '<tr><td width="25%">'.$langs->trans("LinkedToDolibarrMember").'</td>';
                print '<td>';
                if ($object->fk_member)
                {
                    $adh=new Adherent($db);
                    $adh->fetch($object->fk_member);
                    $adh->ref=$adh->login;	// Force to show login instead of id
                    print $adh->getNomUrl(1);
                }
                else
                {
                    print $langs->trans("UserNotLinkedToMember");
                }
                print '</td>';
                print "</tr>\n";
            }

            // Multicompany
            // TODO check if user not linked with the current entity before change entity (thirdparty, invoice, etc.) !!
            if (! empty($conf->multicompany->enabled) && is_object($mc))
            {
            	if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            	{
            		print "<tr>".'<td>'.$langs->trans("Entity").'</td>';
            		print "<td>".$mc->select_entities($object->entity, 'entity', '', 0, 1);		// last parameter 1 means, show also a choice 0=>'all entities'
            		print "</td></tr>\n";
            	}
            	else
            	{
            		print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
            	}
            }

            // Other attributes
            $parameters=array('colspan' => ' colspan="2"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
            	print $object->showOptionals($extrafields,'edit');
            }

            print '</table>';

            dol_fiche_end();

            print '<div align="center">';
            print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
            print '&nbsp; &nbsp; &nbsp;';
            print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
            print '</div>';

            print '</form>';
        }

		if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid)) $ldap->close;
    }
}

if (! empty($conf->api->enabled) && ! empty($conf->use_javascript_ajax))
{
    print "\n".'<script type="text/javascript">';
    print '$(document).ready(function () {
            $("#generate_api_key").click(function() {
                $.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
                    action: \'getrandompassword\',
                    generic: true
                },
                function(token) {
                    $("#api_key").val(token);
                });
            });
    });';
    print '</script>';
}

llxFooter();
$db->close();
