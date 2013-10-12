<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
 *       \file       htdocs/user/fiche.php
 *       \brief      Tab of user card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
if (! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$subaction	= GETPOST('subaction','alpha');
$group		= GETPOST("group","int",3);
$message='';

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
	$result = restrictedArea($user, 'user', $id, '&user', $feature2);
}
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");

$object = new User($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard'));



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
    if ($id <> $user->id)
    {
        $object->fetch($id);

        if (!empty($conf->file->main_limit_users))
        {
            $nb = $object->getNbOfUsers("active");
            if ($nb >= $conf->file->main_limit_users)
            {
                $message='<div class="error">'.$langs->trans("YourQuotaOfUsersIsReached").'</div>';
            }
        }

        if (! $message)
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
            $message='<div class="error">'.$langs->trans("ErrorUserCannotBeDelete").'</div>';
        }
        else
        {
            header("Location: index.php");
            exit;
        }
    }
}

// Action ajout user
if ($action == 'add' && $canadduser)
{
    if (! $_POST["lastname"])
    {
        $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
        $action="create";       // Go back to create page
    }
    if (! $_POST["login"])
    {
        $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
        $action="create";       // Go back to create page
    }

    if (! empty($conf->file->main_limit_users)) // If option to limit users is set
    {
        $nb = $object->getNbOfUsers("active");
        if ($nb >= $conf->file->main_limit_users)
        {
            $message='<div class="error">'.$langs->trans("YourQuotaOfUsersIsReached").'</div>';
            $action="create";       // Go back to create page
        }
    }

    if (! $message)
    {
        $object->lastname		= GETPOST("lastname");
        $object->firstname	    = GETPOST("firstname");
        $object->login		    = GETPOST("login");
        $object->admin		    = GETPOST("admin");
        $object->office_phone	= GETPOST("office_phone");
        $object->office_fax	    = GETPOST("office_fax");
        $object->user_mobile	= GETPOST("user_mobile");
        $object->email		    = GETPOST("email");
        $object->job			= GETPOST("job");
        $object->signature	    = GETPOST("signature");
        $object->accountancy_code = GETPOST("accountancy_code");
        $object->note			= GETPOST("note");
        $object->ldap_sid		= GETPOST("ldap_sid");

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);

        // If multicompany is off, admin users must all be on entity 0.
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
        		$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
        	}
        }
        else
        {
        	$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
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
            if (is_array($object->errors) && count($object->errors)) $message='<div class="error">'.join('<br>',$langs->trans($object->errors)).'</div>';
            else $message='<div class="error">'.$langs->trans($object->error).'</div>';
            $action="create";       // Go back to create page
        }

    }
}

// Action ajout groupe utilisateur
if (($action == 'addgroup' || $action == 'removegroup') && $caneditfield)
{
    if ($group)
    {
        $editgroup = new UserGroup($db);
        $editgroup->fetch($group);
        $editgroup->oldcopy=dol_clone($editgroup);

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
            $message.=$object->error;
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
            $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
            $action="edit";       // Go back to create page
            $error++;
        }
        if (! $_POST["login"])
        {
            $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
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
					$message='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists").'</div>';
					$action="edit";       // Go back to create page
					$error++;
				}
            }
       }

       if (! $error)
       {
            $db->begin();

            $object->oldcopy=dol_clone($object);

            $object->lastname	= GETPOST("lastname");
            $object->firstname	= GETPOST("firstname");
            $object->login		= GETPOST("login");
            $object->pass		= GETPOST("password");
            $object->admin		= empty($user->admin)?0:GETPOST("admin"); // A user can only be set admin by an admin
            $object->office_phone=GETPOST("office_phone");
            $object->office_fax	= GETPOST("office_fax");
            $object->user_mobile= GETPOST("user_mobile");
            $object->email		= GETPOST("email");
            $object->job		= GETPOST("job");
            $object->signature	= GETPOST("signature");
			$object->accountancy_code	= GETPOST("accountancy_code");
            $object->openid		= GETPOST("openid");
            $object->fk_user    = GETPOST("fk_user")>0?GETPOST("fk_user"):0;

            // Fill array 'array_options' with data from add form
        	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

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
            		$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            	}
            }
            else
            {
            	$object->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            }

            if (GETPOST('deletephoto')) $object->photo='';
            if (! empty($_FILES['photo']['name'])) $object->photo = dol_sanitizeFileName($_FILES['photo']['name']);

            $ret=$object->update($user);

            if ($ret < 0)
            {
            	$error++;
                if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
                {
                    $langs->load("errors");
                    $message.='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$object->login).'</div>';
                }
                else
              {
                    $message.='<div class="error">'.$object->error.'</div>';
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
	            	$sql.= " SET fk_socpeople=".$contactid;
	            	if ($contact->socid) $sql.=", fk_societe=".$contact->socid;
	            	$sql.= " WHERE rowid=".$object->id;
            	}
            	else
            	{
            		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
            		$sql.= " SET fk_socpeople=NULL, fk_societe=NULL";
            		$sql.= " WHERE rowid=".$object->id;
            	}
            	$resql=$db->query($sql);
            	dol_syslog("fiche::update sql=".$sql, LOG_DEBUG);
            	if (! $resql)
            	{
            		$error++;
            		$message.='<div class="error">'.$db->lasterror().'</div>';
            	}
            }

            if (! $error && ! count($object->errors))
            {
                if (GETPOST('deletephoto') && $object->photo)
                {
                    $fileimg=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1).'/logos/'.$object->photo;
                    $dirthumbs=$conf->user->dir_output.'/'.get_exdir($object->id,2,0,1).'/logos/thumbs';
                    dol_delete_file($fileimg);
                    dol_delete_dir_recursive($dirthumbs);
                }

                if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
                {
                    $dir= $conf->user->dir_output . '/' . get_exdir($object->id,2,0,1);

                    dol_mkdir($dir);

                    if (@is_dir($dir))
                    {
                        $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                        $result=dol_move_uploaded_file($_FILES['photo']['tmp_name'],$newfile,1,0,$_FILES['photo']['error']);

                        if (! $result > 0)
                        {
                            $message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
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
                }
            }

            if (! $error && ! count($object->errors))
            {
                $message.='<div class="ok">'.$langs->trans("UserModified").'</div>';
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

        $object->oldcopy=dol_clone($object);

        $ret=$object->setPassword($user,$_POST["password"]);
        if ($ret < 0)
        {
            $message.='<div class="error">'.$object->error.'</div>';
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
        $message = '<div class="error">'.$langs->trans("ErrorFailedToSetNewPassword").'</div>';
    }
    else
    {
        // Succes
        if ($action == 'confirm_passwordsend' && $confirm == 'yes')
        {
            if ($object->send_password($user,$newpassword) > 0)
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedAndSentTo",$object->email).'</div>';
                //$message.=$newpassword;
            }
            else
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
                $message.= '<div class="error">'.$object->error.'</div>';
            }
        }
        else
        {
            $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
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
                $ldap_mail			= $attribute[$conf->global->LDAP_FIELD_MAIL];
                $ldap_sid			= $attribute[$conf->global->LDAP_FIELD_SID];
            }
        }
    }
    else
    {
        $message='<div class="error">'.$ldap->error.'</div>';
    }
}



/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("UserCard"));

if (($action == 'create') || ($action == 'adduserldap'))
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode creation                                           */
    /*                                                                            */
    /* ************************************************************************** */

    print_fiche_titre($langs->trans("NewUser"));

    print $langs->trans("CreateInternalUserDesc");
    print "<br>";
    print "<br>";

    dol_htmloutput_mesg($message);

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
                $message='<div class="error">'.$ldap->error.'</div>';
            }
        }
        else
        {
            $message='<div class="error">'.$ldap->error.'</div>';
        }

        // Si la liste des users est rempli, on affiche la liste deroulante
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
       	print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Get')).'"'.(count($liste)?'':' disabled="disabled"').'>';
       	print '</td></tr></table>';
       	print '</form>';

       	print "\n<!-- Form liste LDAP fin -->\n\n";
       	print '<br>';
    }

    print dol_set_focus('#lastname');

    print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST" name="createuser">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    if (! empty($ldap_sid)) print '<input type="hidden" name="ldap_sid" value="'.$ldap_sid.'">';
    print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

    print '<table class="border" width="100%">';

    print '<tr>';

    // Lastname
    print '<td valign="top" width="160"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
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
    print '<tr><td valign="top">'.$langs->trans("Firstname").'</td>';
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
    print '<tr><td valign="top">'.$langs->trans("PostOrFunction").'</td>';
    print '<td>';
    print '<input size="30" type="text" name="job" value="'.GETPOST('job').'">';
    print '</td></tr>';

    // Login
    print '<tr><td valign="top"><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
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
        $generated_password=getRandomPassword('');
    }
    $password=$generated_password;

    // Mot de passe
    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans("Password").'</td>';
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

    // Administrateur
    if (! empty($user->admin))
    {
        print '<tr><td valign="top">'.$langs->trans("Administrator").'</td>';
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
									 		.attr("disabled", true)
									 		.attr("checked", false);
									 	$("select[name=entity]")
											.attr("disabled", false);
									 } else {
									 	$("input[name=superadmin]")
									 		.attr("disabled", false);
									 }
								});
								$("input[name=superadmin]").change(function() {
									if ( $(this).attr("checked") == "checked" ) {
										$("select[name=entity]")
											.attr("disabled", true);
									} else {
										$("select[name=entity]")
											.attr("disabled", false);
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
    print '<tr><td valign="top">'.$langs->trans("Type").'</td>';
    print '<td>';
    print $form->textwithpicto($langs->trans("Internal"),$langs->trans("InternalExternalDesc"));
    print '</td></tr>';

    // Tel
    print '<tr><td valign="top">'.$langs->trans("PhonePro").'</td>';
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
    print '<tr><td valign="top">'.$langs->trans("PhoneMobile").'</td>';
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
    print '<tr><td valign="top">'.$langs->trans("Fax").'</td>';
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

    // EMail
    print '<tr><td valign="top"'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
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
    print '<tr><td valign="top">'.$langs->trans("Signature").'</td>';
    print '<td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor('signature',GETPOST('signature'),'',138,'dolibarr_mailings','In',true,true,empty($conf->global->FCKEDITOR_ENABLE_USERSIGN)?0:1,ROWS_4,90);
    print $doleditor->Create(1);
    print '</td></tr>';

    // Multicompany
    if (! empty($conf->multicompany->enabled))
    {
        if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            print "<tr>".'<td valign="top">'.$langs->trans("Entity").'</td>';
            print "<td>".$mc->select_entities($conf->entity);
            print "</td></tr>\n";
        }
        else
        {
            print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
        }
    }

    // Hierarchy
    print '<tr><td valign="top">'.$langs->trans("HierarchicalResponsible").'</td>';
    print '<td>';
    print $form->select_dolusers($object->fk_user,'fk_user',1,array($object->id),0,'',0,$conf->entity);
    print '</td>';
    print "</tr>\n";

    // Note
    print '<tr><td valign="top">';
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

    print '<center><br><input class="button" value="'.$langs->trans("CreateUser").'" name="create" type="submit"></center>';

    print "</form>";
}
else
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Visu et edition                                                            */
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
                    $message .= $ldap->error;
                }

                $passDoNotExpire = 0;
                $userChangePassNextLogon = 0;
                $userDisabled = 0;
                $statutUACF = '';

                //On verifie les options du compte
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
        dol_fiche_head($head, 'user', $title, 0, 'user');

        /*
         * Confirmation reinitialisation mot de passe
         */
        if ($action == 'password')
        {
            print $form->formconfirm("fiche.php?id=$object->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$object->login),"confirm_password", '', 0, 1);
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            print $form->formconfirm("fiche.php?id=$object->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$object->login),"confirm_passwordsend", '', 0, 1);
        }

        /*
         * Confirmation desactivation
         */
        if ($action == 'disable')
        {
            print $form->formconfirm("fiche.php?id=$object->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$object->login),"confirm_disable", '', 0, 1);
        }

        /*
         * Confirmation activation
         */
        if ($action == 'enable')
        {
            print $form->formconfirm("fiche.php?id=$object->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$object->login),"confirm_enable", '', 0, 1);
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            print $form->formconfirm("fiche.php?id=$object->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$object->login),"confirm_delete", '', 0, 1);
        }

        dol_htmloutput_mesg($message);

        /*
         * Fiche en mode visu
         */
        if ($action != 'edit')
        {
            $rowspan=16;

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
            print '</td>';
            print '</tr>'."\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
            if (! empty($conf->societe->enabled)) $rowspan++;
            if (! empty($conf->adherent->enabled)) $rowspan++;

            // Lastname
            print '<tr><td valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td>'.$object->lastname.'</td>';

            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $form->showphoto('userphoto',$object,100);
            print '</td>';

            print '</tr>'."\n";

            // Firstname
            print '<tr><td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td>'.$object->firstname.'</td>';
            print '</tr>'."\n";

            // Position/Job
            print '<tr><td valign="top">'.$langs->trans("PostOrFunction").'</td>';
            print '<td>'.$object->job.'</td>';
            print '</tr>'."\n";

            // Login
            print '<tr><td valign="top">'.$langs->trans("Login").'</td>';
            if (! empty($object->ldap_sid) && $object->statut==0)
            {
                print '<td class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
            }
            else
            {
                print '<td>'.$object->login.'</td>';
            }
            print '</tr>'."\n";

            // Password
            print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
            if (! empty($object->ldap_sid))
            {
                if ($passDoNotExpire)
                {
                    print '<td>'.$langs->trans("LdapUacf_".$statutUACF).'</td>';
                }
                else if($userChangePassNextLogon)
                {
                    print '<td class="warning">'.$langs->trans("UserMustChangePassNextLogon",$ldap->domainFQDN).'</td>';
                }
                else if($userDisabled)
                {
                    print '<td class="warning">'.$langs->trans("LdapUacf_".$statutUACF,$ldap->domainFQDN).'</td>';
                }
                else
                {
                    print '<td>'.$langs->trans("DomainPassword").'</td>';
                }
            }
            else
            {
                print '<td>';
                if ($object->pass) print preg_replace('/./i','*',$object->pass);
                else
                {
                    if ($user->admin) print $langs->trans("Crypted").': '.$object->pass_indatabase_crypted;
                    else print $langs->trans("Hidden");
                }
                print "</td>";
            }
            print '</tr>'."\n";

            // Administrator
            print '<tr><td valign="top">'.$langs->trans("Administrator").'</td><td>';
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
            print '<tr><td valign="top">'.$langs->trans("Type").'</td><td>';
            $type=$langs->trans("Internal");
            if ($object->societe_id) $type=$langs->trans("External");
            print $form->textwithpicto($type,$langs->trans("InternalExternalDesc"));
            if ($object->ldap_sid) print ' ('.$langs->trans("DomainUser").')';
            print '</td></tr>'."\n";

            // Ldap sid
            if ($object->ldap_sid)
            {
            	print '<tr><td valign="top">'.$langs->trans("Type").'</td><td>';
            	print $langs->trans("DomainUser",$ldap->domainFQDN);
            	print '</td></tr>'."\n";
            }

            // Tel pro
            print '<tr><td valign="top">'.$langs->trans("PhonePro").'</td>';
            print '<td>'.dol_print_phone($object->office_phone,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Tel mobile
            print '<tr><td valign="top">'.$langs->trans("PhoneMobile").'</td>';
            print '<td>'.dol_print_phone($object->user_mobile,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Fax
            print '<tr><td valign="top">'.$langs->trans("Fax").'</td>';
            print '<td>'.dol_print_phone($object->office_fax,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // EMail
            print '<tr><td valign="top">'.$langs->trans("EMail").'</td>';
            print '<td>'.dol_print_email($object->email,0,0,1).'</td>';
            print "</tr>\n";

            // Signature
            print '<tr><td valign="top">'.$langs->trans('Signature').'</td><td>';
            print dol_htmlentitiesbr($object->signature);
            print "</td></tr>\n";

            // Hierarchy
            print '<tr><td valign="top">'.$langs->trans("HierarchicalResponsible").'</td>';
            print '<td>';
            if (empty($object->fk_user)) print $langs->trans("None");
            else {
            	$huser=new User($db);
            	$huser->fetch($object->fk_user);
            	print $huser->getNomUrl(1);
            }
            print '</td>';
            print "</tr>\n";

			// Accountancy code
			if (! empty($conf->global->USER_ENABLE_ACCOUNTANCY_CODE))	// For the moment field is not used so must not appeared.
			{
				$rowspan++;
            	print '<tr><td valign="top">'.$langs->trans("AccountancyCode").'</td>';
            	print '<td>'.$object->accountancy_code.'</td>';
			}

            // Status
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $object->getLibStatut(4);
            print '</td>';
            print '</tr>'."\n";

            print '<tr><td valign="top">'.$langs->trans("LastConnexion").'</td>';
            print '<td>'.dol_print_date($object->datelastlogin,"dayhour").'</td>';
            print "</tr>\n";

            print '<tr><td valign="top">'.$langs->trans("PreviousConnexion").'</td>';
            print '<td>'.dol_print_date($object->datepreviouslogin,"dayhour").'</td>';
            print "</tr>\n";

            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER))
            {
                print '<tr><td valign="top">'.$langs->trans("OpenIDURL").'</td>';
                print '<td>'.$object->openid.'</td>';
                print "</tr>\n";
            }

            // Company / Contact
            if (! empty($conf->societe->enabled))
            {
                print '<tr><td valign="top">'.$langs->trans("LinkToCompanyContact").'</td>';
                print '<td>';
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
                    print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
                }
                print '</td>';
                print '</tr>'."\n";
            }

            // Module Adherent
            if (! empty($conf->adherent->enabled))
            {
                $langs->load("members");
                print '<tr><td valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
                print '<td>';
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
            if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            {
            	print '<tr><td valign="top">'.$langs->trans("Entity").'</td><td width="75%" class="valeur">';
            	if ($object->admin && ! $object->entity)
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

          	// Other attributes
			$parameters=array('colspan' => ' colspan="2"');
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
			if (empty($reshook) && ! empty($extrafields->attribute_label))
			{
				print $object->showOptionals($extrafields);
			}

			print "</table>\n";

            print "</div>\n";


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
                print_fiche_titre($langs->trans("ListOfGroupsForUser"),'','');

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
                            print '</td><td valign="top">'.$langs->trans("Entity").'</td>';
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
                            print '<a href="'.DOL_URL_ROOT.'/user/group/fiche.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->name.'</a>';
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
            $rowspan=15;
            if (isset($conf->file->main_authentication) && preg_match('/openid/',$conf->file->main_authentication) && ! empty($conf->global->MAIN_OPENIDURL_PERUSER)) $rowspan++;
            if (! empty($conf->societe->enabled)) $rowspan++;
            if (! empty($conf->adherent->enabled)) $rowspan++;

        	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="entity" value="'.$object->entity.'">';
            print '<table width="100%" class="border">';

            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $object->id;
            print '</td>';
            print '</tr>';

            // Lastname
            print "<tr>";
            print '<td valign="top" class="fieldrequired">'.$langs->trans("Lastname").'</td>';
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
            print $form->showphoto('userphoto',$object);
            if ($caneditfield)
            {
                if ($object->photo) print "<br>\n";
                print '<table class="nobordernopadding hideonsmartphone">';
                if ($object->photo) print '<tr><td align="center"><input type="checkbox" class="flat" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
                print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
                print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
                print '</table>';
            }
            print '</td>';

            print '</tr>';

            // Firstname
            print "<tr>".'<td valign="top">'.$langs->trans("Firstname").'</td>';
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
            print '<tr><td valign="top">'.$langs->trans("PostOrFunction").'</td>';
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

            // Login
            print "<tr>".'<td valign="top"><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
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
            print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
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

            // Administrator
            print '<tr><td valign="top">'.$langs->trans("Administrator").'</td>';
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
													.attr("disabled", true)
													.attr("checked", false);
										}
										if ($("input[name=superadmin]").attr("checked") == "checked") {
											$("select[name=entity]")
													.attr("disabled", true);
										}
										$("select[name=admin]").change(function() {
											 if ( $(this).val() == 0 ) {
											 	$("input[name=superadmin]")
											 		.attr("disabled", true)
											 		.attr("checked", false);
											 	$("select[name=entity]")
													.attr("disabled", false);
											 } else {
											 	$("input[name=superadmin]")
											 		.attr("disabled", false);
											 }
										});
										$("input[name=superadmin]").change(function() {
											if ( $(this).attr("checked") == "checked" ) {
												$("select[name=entity]")
													.attr("disabled", true);
											} else {
												$("select[name=entity]")
													.attr("disabled", false);
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
           	print '<tr><td width="25%" valign="top">'.$langs->trans("Type").'</td>';
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
            print "<tr>".'<td valign="top">'.$langs->trans("PhonePro").'</td>';
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
            print "<tr>".'<td valign="top">'.$langs->trans("PhoneMobile").'</td>';
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
            print "<tr>".'<td valign="top">'.$langs->trans("Fax").'</td>';
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

            // EMail
            print "<tr>".'<td valign="top"'.(! empty($conf->global->USER_MAIL_REQUIRED)?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
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
            print "<tr>".'<td valign="top">'.$langs->trans("Signature").'</td>';
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
                print "<tr>".'<td valign="top">'.$langs->trans("OpenIDURL").'</td>';
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
            print '<tr><td valign="top">'.$langs->trans("HierarchicalResponsible").'</td>';
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

			// Accountancy code
            print "<tr>";
            print '<td valign="top">'.$langs->trans("AccountancyCode").'</td>';
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

            // Status
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $object->getLibStatut(4);
            print '</td></tr>';

            // Company / Contact
            if (! empty($conf->societe->enabled))
            {
                print '<tr><td width="25%" valign="top">'.$langs->trans("LinkToCompanyContact").'</td>';
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
                        print ' / <a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$object->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
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
                print '<tr><td width="25%" valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
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
            if (! empty($conf->multicompany->enabled))
            {
            	if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            	{
            		print "<tr>".'<td valign="top">'.$langs->trans("Entity").'</td>';
            		print "<td>".$mc->select_entities($object->entity);
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

            print '<br><center>';
            print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
            print ' &nbsp; ';
            print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
            print '</center>';

            print '</form>';

            print '</div>';
        }

		if (! empty($conf->ldap->enabled) && ! empty($object->ldap_sid)) $ldap->close;
    }
}


llxFooter();
$db->close();
?>
