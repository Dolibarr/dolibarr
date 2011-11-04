<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
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
 *       \file       htdocs/user/fiche.php
 *       \brief      Tab of user card
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/user.class.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/usergroups.lib.php");
if ($conf->ldap->enabled) require_once(DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
if (! empty($conf->multicompany->enabled)) dol_include_once("/multicompany/class/actions_multicompany.class.php");

$id			= GETPOST('id','int');
$action		= GETPOST("action");
$group		= GETPOST("group","int",3);
$confirm	= GETPOST("confirm");

// Define value to know what current user can do on users
$canadduser=($user->admin || $user->rights->user->user->creer);
$canreaduser=($user->admin || $user->rights->user->user->lire);
$canedituser=($user->admin || $user->rights->user->user->creer);
$candisableuser=($user->admin || $user->rights->user->user->supprimer);
$canreadgroup=$canreaduser;
$caneditgroup=$canedituser;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadgroup=($user->admin || $user->rights->user->group_advance->read);
    $caneditgroup=($user->admin || $user->rights->user->group_advance->write);
}
// Define value to know what current user can do on properties of edited user
if ($id)
{
    // $user est le user qui edite, $_GET["id"] est l'id de l'utilisateur edite
    $caneditfield=( (($user->id == $id) && $user->rights->user->self->creer)
    || (($user->id != $id) && $user->rights->user->user->creer) );
    $caneditpassword=( (($user->id == $id) && $user->rights->user->self->password)
    || (($user->id != $id) && $user->rights->user->user->password) );
}

//Multicompany in mode transversal
if(! empty($conf->multicompany->enabled) && $conf->entity > 1 && $conf->multicompany->transverse_mode)
{
    accessforbidden();
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2='user';
if ($user->id == $id) { $feature2=''; $canreaduser=1; } // A user can always read its own card
$result = restrictedArea($user, 'user', $id, '', $feature2);
if ($user->id <> $id && ! $canreaduser) accessforbidden();

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");

$form = new Form($db);


/**
 * Actions
 */
if ($_GET["subaction"] == 'addrights' && $canedituser)
{
    $edituser = new User($db);
    $edituser->fetch($id);
    $edituser->addrights($_GET["rights"]);
}

if ($_GET["subaction"] == 'delrights' && $canedituser)
{
    $edituser = new User($db);
    $edituser->fetch($id);
    $edituser->delrights($_GET["rights"]);
}

if ($action == 'confirm_disable' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $edituser = new User($db);
        $edituser->fetch($id);
        $edituser->setstatus(0);
        Header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
        exit;
    }
}
if ($action == 'confirm_enable' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $message='';

        $edituser = new User($db);
        $edituser->fetch($id);

        if (!empty($conf->file->main_limit_users))
        {
            $nb = $edituser->getNbOfUsers("active",1);
            if ($nb >= $conf->file->main_limit_users)
            {
                $message='<div class="error">'.$langs->trans("YourQuotaOfUsersIsReached").'</div>';
            }
        }

        if (! $message)
        {
            $edituser->setstatus(1);
            Header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
    }
}

if ($action == 'confirm_delete' && $confirm == "yes" && $candisableuser)
{
    if ($id <> $user->id)
    {
        $edituser = new User($db);
        $edituser->id=$id;
        $result = $edituser->delete();
        if ($result < 0)
        {
            $langs->load("errors");
            $message='<div class="error">'.$langs->trans("ErrorUserCannotBeDelete").'</div>';
        }
        else
        {
            Header("Location: index.php");
            exit;
        }
    }
}

// Action ajout user
if ($action == 'add' && $canadduser)
{
    $message="";
    if (! $_POST["nom"])
    {
        $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
        $action="create";       // Go back to create page
    }
    if (! $_POST["login"])
    {
        $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
        $action="create";       // Go back to create page
    }

    $edituser = new User($db);

    if (! empty($conf->file->main_limit_users)) // If option to limit users is set
    {
        $nb = $edituser->getNbOfUsers("active",1);
        if ($nb >= $conf->file->main_limit_users)
        {
            $message='<div class="error">'.$langs->trans("YourQuotaOfUsersIsReached").'</div>';
            $action="create";       // Go back to create page
        }
    }

    if (! $message)
    {
        $edituser->lastname		= $_POST["nom"];
        $edituser->firstname	= $_POST["prenom"];
        $edituser->login		= $_POST["login"];
        $edituser->admin		= $_POST["admin"];
        $edituser->office_phone	= $_POST["office_phone"];
        $edituser->office_fax	= $_POST["office_fax"];
        $edituser->user_mobile	= $_POST["user_mobile"];
        $edituser->email		= $_POST["email"];
        $edituser->webcal_login	= $_POST["webcal_login"];
        $edituser->signature	= $_POST["signature"];
        $edituser->phenix_login	= $_POST["phenix_login"];
        $edituser->phenix_pass	= $_POST["phenix_pass"];
        $edituser->note			= $_POST["note"];
        $edituser->ldap_sid		= $_POST["ldap_sid"];
        // If multicompany is off, admin users must all be on entity 0.
        if($conf->multicompany->enabled)
        {
        	if($conf->multicompany->transverse_mode || ! empty($_POST["superadmin"]))
        	{
        		$edituser->entity=0;
        	}
        	else
        	{
        		$edituser->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
        	}
        }
        else if(! empty($_POST["admin"]))
        {
        	$edituser->entity=0;
        }
        else
        {
        	$edituser->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
        }

        $db->begin();

        $id = $edituser->create($user);
        if ($id > 0)
        {
            if (isset($_POST['password']) && trim($_POST['password']))
            {
                $edituser->setPassword($user,trim($_POST['password']));
            }

            $db->commit();

            Header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            $langs->load("errors");
            $db->rollback();
            if (is_array($edituser->errors) && count($edituser->errors)) $message='<div class="error">'.join('<br>',$langs->trans($edituser->errors)).'</div>';
            else $message='<div class="error">'.$langs->trans($edituser->error).'</div>';
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

        $edituser = new User($db);
        $edituser->fetch($id);
        if ($action == 'addgroup')    $edituser->SetInGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity));
        if ($action == 'removegroup') $edituser->RemoveFromGroup($group,($conf->multicompany->transverse_mode?GETPOST("entity"):$editgroup->entity));

        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF'].'?id='.$id);
            exit;
        }
        else
        {
            $message.=$edituser->error;
        }
    }
}

if ($action == 'update' && ! $_POST["cancel"])
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    if ($caneditfield)	// Case we can edit all field
    {
        $message="";

        if (! $_POST["nom"])
        {
            $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
            $action="edit";       // Go back to create page
        }
        if (! $_POST["login"])
        {
            $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
            $action="edit";       // Go back to create page
        }

        if (! $message)
        {
            $db->begin();
            $edituser = new User($db);
            $edituser->fetch($id);

            $edituser->oldcopy=dol_clone($edituser);

            $edituser->lastname		= $_POST["nom"];
            $edituser->firstname	= $_POST["prenom"];
            $edituser->login		= $_POST["login"];
            $edituser->pass			= $_POST["password"];
            $edituser->admin		= $_POST["admin"];
            $edituser->office_phone	= $_POST["office_phone"];
            $edituser->office_fax	= $_POST["office_fax"];
            $edituser->user_mobile	= $_POST["user_mobile"];
            $edituser->email		= $_POST["email"];
            $edituser->signature	= $_POST["signature"];
            $edituser->openid		= $_POST["openid"];
            $edituser->webcal_login	= $_POST["webcal_login"];
            $edituser->phenix_login	= $_POST["phenix_login"];
            $edituser->phenix_pass	= $_POST["phenix_pass"];
            if($conf->multicompany->enabled)
            {
            	if($conf->multicompany->transverse_mode || ! empty($_POST["superadmin"]))
            	{
            		$edituser->entity=0;
            	}
            	else
            	{
            		$edituser->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            	}
            }
            else if(! empty($_POST["admin"]))
            {
            	$edituser->entity=0;
            }
            else
            {
            	$edituser->entity = (empty($_POST["entity"]) ? 0 : $_POST["entity"]);
            }

            if (GETPOST('deletephoto')) $edituser->photo='';
            if (! empty($_FILES['photo']['name'])) $edituser->photo = dol_sanitizeFileName($_FILES['photo']['name']);

            $ret=$edituser->update($user);
            if ($ret < 0)
            {
                if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
                {
                    $langs->load("errors");
                    $message.='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$edituser->login).'</div>';
                }
                else
                {
                    $message.='<div class="error">'.$edituser->error.'</div>';
                }
            }

            if ($ret >=0 && ! count($edituser->errors))
            {
                if (GETPOST('deletephoto') && $edituser->photo)
                {
                    $fileimg=$conf->user->dir_output.'/'.get_exdir($edituser->id,2,0,1).'/logos/'.$edituser->photo;
                    $dirthumbs=$conf->user->dir_output.'/'.get_exdir($edituser->id,2,0,1).'/logos/thumbs';
                    dol_delete_file($fileimg);
                    dol_delete_dir_recursive($dirthumbs);
                }

                if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
                {
                    $dir= $conf->user->dir_output . '/' . get_exdir($edituser->id,2,0,1);

                    create_exdir($dir);

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

            if ($ret >= 0 && ! count($edituser->errors))
            {
                $message.='<div class="ok">'.$langs->trans("UserModified").'</div>';
                $db->commit();
            }
            else
            {
                $db->rollback();
            }
        }
    }
    else if ($caneditpassword)	// Case we can edit only password
    {
        $edituser = new User($db);
        $edituser->fetch($id);

        $edituser->oldcopy=dol_clone($edituser);

        $ret=$edituser->setPassword($user,$_POST["password"]);
        if ($ret < 0)
        {
            $message.='<div class="error">'.$edituser->error.'</div>';
        }
    }
}

// Change password with a new generated one
if ((($action == 'confirm_password' && $confirm == 'yes')
|| ($action == 'confirm_passwordsend' && $confirm == 'yes')) && $caneditpassword)
{
    $edituser = new User($db);
    $edituser->fetch($id);

    $newpassword=$edituser->setPassword($user,'');
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
            if ($edituser->send_password($user,$newpassword) > 0)
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedAndSentTo",$edituser->email).'</div>';
                //$message.=$newpassword;
            }
            else
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
                $message.= '<div class="error">'.$edituser->error.'</div>';
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
                $ldap_nom			= $attribute[$conf->global->LDAP_FIELD_NAME];
                $ldap_prenom		= $attribute[$conf->global->LDAP_FIELD_FIRSTNAME];
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

llxHeader('',$langs->trans("UserCard"));

$html = new Form($db);

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

    if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
    {
        /*
         * Affiche formulaire d'ajout d'un compte depuis LDAP
         * si on est en synchro LDAP vers Dolibarr
         */

        $ldap = new Ldap();
        $result = $ldap->connect_bind();
        if ($result >= 0)
        {
            $required_fields=array($conf->global->LDAP_KEY_USERS,
            $conf->global->LDAP_FIELD_FULLNAME,
            $conf->global->LDAP_FIELD_NAME,
            $conf->global->LDAP_FIELD_FIRSTNAME,
            $conf->global->LDAP_FIELD_LOGIN,
            $conf->global->LDAP_FIELD_LOGIN_SAMBA);

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
    }

    dol_htmloutput_errors($message);

    if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
    {
        // Si la liste des users est rempli, on affiche la liste deroulante
        if (is_array($liste))
        {
            print "\n\n<!-- Form liste LDAP debut -->\n";

            print '<form name="add_user_ldap" action="'.$_SERVER["PHP_SELF"].'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table width="100%" class="border"><tr>';
            print '<td width="160">';
            print $langs->trans("LDAPUsers");
            print '</td>';
            print '<td>';
            print '<input type="hidden" name="action" value="adduserldap">';
            print $html->selectarray('users', $liste, '', 1);
            print '</td><td align="center">';
            print '<input type="submit" class="button" value="'.$langs->trans('Get').'">';
            print '</td></tr></table>';
            print '</form>';

            print "\n<!-- Form liste LDAP fin -->\n\n";
            print '<br>';
        }
    }

    print '<form action="fiche.php" method="post" name="createuser">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    if ($ldap_sid) print '<input type="hidden" name="ldap_sid" value="'.$ldap_sid.'">';
    print '<input type="hidden" name="entity" value="'.$conf->entity.'">';

    print '<table class="border" width="100%">';

    print '<tr>';

    // Nom
    print '<td valign="top" width="160"><span class="fieldrequired">'.$langs->trans("Lastname").'</span></td>';
    print '<td>';
    if ($ldap_nom)
    {
        print '<input type="hidden" name="nom" value="'.$ldap_nom.'">';
        print $ldap_nom;
    }
    else
    {
        print '<input size="30" type="text" name="nom" value="'.$_POST["nom"].'">';
    }
    print '</td></tr>';

    // Prenom
    print '<tr><td valign="top">'.$langs->trans("Firstname").'</td>';
    print '<td>';
    if ($ldap_prenom)
    {
        print '<input type="hidden" name="prenom" value="'.$ldap_prenom.'">';
        print $ldap_prenom;
    }
    else
    {
        print '<input size="30" type="text" name="prenom" value="'.$_POST["prenom"].'">';
    }
    print '</td></tr>';

    // Login
    print '<tr><td valign="top"><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
    print '<td>';
    if ($ldap_login)
    {
        print '<input type="hidden" name="login" value="'.$ldap_login.'">';
        print $ldap_login;
    }
    elseif ($ldap_loginsmb)
    {
        print '<input type="hidden" name="login" value="'.$ldap_loginsmb.'">';
        print $ldap_loginsmb;
    }
    else
    {
        print '<input size="20" maxsize="24" type="text" name="login" value="'.$_POST["login"].'">';
    }
    print '</td></tr>';

    $generated_password='';
    if (! $ldap_sid)
    {
        $generated_password=getRandomPassword('');
    }
    $password=$generated_password;

    // Mot de passe
    print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
    print '<td>';
    if ($ldap_sid)
    {
        print 'Mot de passe du domaine';
    }
    else
    {
        if ($ldap_pass)
        {
            print '<input type="hidden" name="password" value="'.$ldap_pass.'">';
            print preg_replace('/./i','*',$ldap_pass);
        }
        else
        {
            // We do not use a field password but a field text to show new password to use.
            print '<input size="30" maxsize="32" type="text" name="password" value="'.$password.'">';
        }
    }
    print '</td></tr>';

    // Administrateur
    if ($user->admin)
    {
        print '<tr><td valign="top">'.$langs->trans("Administrator").'</td>';
        print '<td>';
        print $form->selectyesno('admin',$_POST["admin"],1);

        if (! empty($conf->multicompany->enabled) && ! $user->entity && empty($conf->multicompany->transverse_mode))
        {
            if ($conf->use_javascript_ajax)
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

    //Multicompany
    if (! empty($conf->multicompany->enabled))
    {
        if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
        {
            $mc = new ActionsMulticompany($db);
            print "<tr>".'<td valign="top">'.$langs->trans("Entity").'</td>';
            print "<td>".$mc->select_entities($conf->entity);
            print "</td></tr>\n";
        }
        else
        {
            print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
        }
    }

    // Type
    print '<tr><td valign="top">'.$langs->trans("Type").'</td>';
    print '<td>';
    print $html->textwithpicto($langs->trans("Internal"),$langs->trans("InternalExternalDesc"));
    print '</td></tr>';

    // Tel
    print '<tr><td valign="top">'.$langs->trans("PhonePro").'</td>';
    print '<td>';
    if ($ldap_phone)
    {
        print '<input type="hidden" name="office_phone" value="'.$ldap_phone.'">';
        print $ldap_phone;
    }
    else
    {
        print '<input size="20" type="text" name="office_phone" value="'.$_POST["office_phone"].'">';
    }
    print '</td></tr>';

    // Tel portable
    print '<tr><td valign="top">'.$langs->trans("PhoneMobile").'</td>';
    print '<td>';
    if ($ldap_mobile)
    {
        print '<input type="hidden" name="user_mobile" value="'.$ldap_mobile.'">';
        print $ldap_mobile;
    }
    else
    {
        print '<input size="20" type="text" name="user_mobile" value="'.$_POST["user_mobile"].'">';
    }
    print '</td></tr>';

    // Fax
    print '<tr><td valign="top">'.$langs->trans("Fax").'</td>';
    print '<td>';
    if ($ldap_fax)
    {
        print '<input type="hidden" name="office_fax" value="'.$ldap_fax.'">';
        print $ldap_fax;
    }
    else
    {
        print '<input size="20" type="text" name="office_fax" value="'.$_POST["office_fax"].'">';
    }
    print '</td></tr>';

    // EMail
    print '<tr><td valign="top"'.($conf->global->USER_MAIL_REQUIRED?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
    print '<td>';
    if ($ldap_mail)
    {
        print '<input type="hidden" name="email" value="'.$ldap_mail.'">';
        print $ldap_mail;
    }
    else
    {
        print '<input size="40" type="text" name="email" value="'.$_POST["email"].'">';
    }
    print '</td></tr>';

    // Signature
    print '<tr><td valign="top">'.$langs->trans("Signature").'</td>';
    print '<td>';
    print '<textarea rows="'.ROWS_5.'" cols="90" name="signature">'.$_POST["signature"].'</textarea>';
    print '</td></tr>';

    // Note
    print '<tr><td valign="top">';
    print $langs->trans("Note");
    print '</td><td>';
    if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_USER)
    {
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note','','',180,'dolibarr_notes','',false);
        $doleditor->Create();
    }
    else
    {
        print '<textarea class="flat" name="note" rows="'.ROWS_4.'" cols="90">';
        print $_POST["note"];
        print '</textarea>';
    }
    print "</td></tr>\n";

    // Autres caracteristiques issus des autres modules

    // Module Webcalendar
    if ($conf->webcalendar->enabled)
    {
        print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
        print '<td><input size="30" type="text" name="webcal_login" value="'.$_POST["webcal_login"].'"></td></tr>';
    }

    // Module Phenix
    if ($conf->phenix->enabled)
    {
        print "<tr>".'<td valign="top">'.$langs->trans("LoginPenix").'</td>';
        print '<td><input size="30" type="text" name="phenix_login" value="'.$_POST["phenix_login"].'"></td></tr>';
        print "<tr>".'<td valign="top">'.$langs->trans("PassPenix").'</td>';
        print '<td><input size="30" type="text" name="phenix_pass" value="'.$_POST["phenix_pass"].'"></td></tr>';
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

    if ($id)
    {
        $fuser = new User($db);
        $fuser->fetch($id);

        // Connexion ldap
        // pour recuperer passDoNotExpire et userChangePassNextLogon
        if ($conf->ldap->enabled && $fuser->ldap_sid)
        {
            $ldap = new Ldap();
            $result=$ldap->connect_bind();
            if ($result > 0)
            {
                $userSearchFilter = '('.$conf->global->LDAP_FILTER_CONNECTION.'('.$this->getUserIdentifier().'='.$fuser->login.'))';
                $entries = $ldap->fetch($fuser->login,$userSearchFilter);
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
        $head = user_prepare_head($fuser);

        $title = $langs->trans("User");
        dol_fiche_head($head, 'user', $title, 0, 'user');

        /*
         * Confirmation reinitialisation mot de passe
         */
        if ($action == 'password')
        {
            $ret=$html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$fuser->login),"confirm_password", '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            $ret=$html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$fuser->login),"confirm_passwordsend", '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation desactivation
         */
        if ($action == 'disable')
        {
            $ret=$html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$fuser->login),"confirm_disable", '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation activation
         */
        if ($action == 'enable')
        {
            $ret=$html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$fuser->login),"confirm_enable", '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $ret=$html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$fuser->login),"confirm_delete", '', 0, 1);
            if ($ret == 'html') print '<br>';
        }

        dol_htmloutput_mesg($message);

        /*
         * Fiche en mode visu
         */
        if ($action != 'edit')
        {
            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $html->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
            print '</td>';
            print '</tr>'."\n";

            $rowspan=14;
            if ($conf->societe->enabled) $rowspan++;
            if ($conf->adherent->enabled) $rowspan++;
            if ($conf->webcalendar->enabled) $rowspan++;
            if ($conf->phenix->enabled) $rowspan+=2;

            // Lastname
            print '<tr><td valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td>'.$fuser->nom.'</td>';

            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $html->showphoto('userphoto',$fuser,100);
            print '</td>';

            print '</tr>'."\n";

            // Firstname
            print '<tr><td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td>'.$fuser->prenom.'</td>';
            print '</tr>'."\n";

            // Login
            print '<tr><td valign="top">'.$langs->trans("Login").'</td>';
            if ($fuser->ldap_sid && $fuser->statut==0)
            {
                print '<td class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
            }
            else
            {
                print '<td>'.$fuser->login.'</td>';
            }
            print '</tr>'."\n";

            // Password
            print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
            if ($fuser->ldap_sid)
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
                if ($fuser->pass) print preg_replace('/./i','*',$fuser->pass);
                else
                {
                    if ($user->admin) print $langs->trans("Crypted").': '.$fuser->pass_indatabase_crypted;
                    else print $langs->trans("Hidden");
                }
                print "</td>";
            }
            print '</tr>'."\n";

            // Administrator
            print '<tr><td valign="top">'.$langs->trans("Administrator").'</td><td>';
            if (! empty($conf->multicompany->enabled) && $fuser->admin && ! $fuser->entity)
            {
                print $html->textwithpicto(yn($fuser->admin),$langs->trans("SuperAdministratorDesc"),1,"superadmin");
            }
            else if ($fuser->admin)
            {
                print $html->textwithpicto(yn($fuser->admin),$langs->trans("AdministratorDesc"),1,"admin");
            }
            else
            {
                print yn($fuser->admin);
            }
            print '</td></tr>'."\n";

            // Multicompany
            if (! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            {
            	print '<tr><td valign="top">'.$langs->trans("Entity").'</td><td width="75%" class="valeur">';
            	if ($fuser->admin && ! $fuser->entity)
            	{
            		print $langs->trans("AllEntities");
            	}
            	else
            	{
            		$mc = new ActionsMulticompany($db);
            		$mc->getInfo($fuser->entity);
            		print $mc->label;
            	}
            	print "</td></tr>\n";
            }

            // Type
            print '<tr><td valign="top">'.$langs->trans("Type").'</td><td>';
            if ($fuser->societe_id)
            {
                print $html->textwithpicto($langs->trans("External"),$langs->trans("InternalExternalDesc"));
            }
            else if ($fuser->ldap_sid)
            {
                print $langs->trans("DomainUser",$ldap->domainFQDN);
            }
            else
            {
                print $html->textwithpicto($langs->trans("Internal"),$langs->trans("InternalExternalDesc"));
            }
            print '</td></tr>'."\n";

            // Tel pro
            print '<tr><td valign="top">'.$langs->trans("PhonePro").'</td>';
            print '<td>'.dol_print_phone($fuser->office_phone,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Tel mobile
            print '<tr><td valign="top">'.$langs->trans("PhoneMobile").'</td>';
            print '<td>'.dol_print_phone($fuser->user_mobile,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // Fax
            print '<tr><td valign="top">'.$langs->trans("Fax").'</td>';
            print '<td>'.dol_print_phone($fuser->office_fax,'',0,0,1).'</td>';
            print '</tr>'."\n";

            // EMail
            print '<tr><td valign="top">'.$langs->trans("EMail").'</td>';
            print '<td>'.dol_print_email($fuser->email,0,0,1).'</td>';
            print "</tr>\n";

            // Signature
            print '<tr><td valign="top">'.$langs->trans('Signature').'</td>';
            print '<td>'.$fuser->signature.'</td>';
            print "</tr>\n";

            // Statut
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $fuser->getLibStatut(4);
            print '</td>';
            print '</tr>'."\n";

            print '<tr><td valign="top">'.$langs->trans("LastConnexion").'</td>';
            print '<td>'.dol_print_date($fuser->datelastlogin,"dayhour").'</td>';
            print "</tr>\n";

            print '<tr><td valign="top">'.$langs->trans("PreviousConnexion").'</td>';
            print '<td>'.dol_print_date($fuser->datepreviouslogin,"dayhour").'</td>';
            print "</tr>\n";


            if (preg_match('/myopenid/',$conf->authmode))
            {
                print '<tr><td valign="top">'.$langs->trans("url_openid").'</td>';
                print '<td>'.$fuser->openid.'</td>';
                print "</tr>\n";
            }
            // Autres caracteristiques issus des autres modules

            // Module Webcalendar
            if ($conf->webcalendar->enabled)
            {
                $langs->load("other");
                print '<tr><td valign="top">'.$langs->trans("LoginWebcal").'</td>';
                print '<td>'.$fuser->webcal_login.'&nbsp;</td>';
                print '</tr>'."\n";
            }

            // Module Phenix
            if ($conf->phenix->enabled)
            {
                $langs->load("other");
                print '<tr><td valign="top">'.$langs->trans("LoginPhenix").'</td>';
                print '<td>'.$fuser->phenix_login.'&nbsp;</td>';
                print "</tr>\n";
                print '<tr><td valign="top">'.$langs->trans("PassPhenix").'</td>';
                print '<td>'.preg_replace('/./i','*',$fuser->phenix_pass_crypted).'&nbsp;</td>';
                print '</tr>'."\n";
            }

            // Company / Contact
            if ($conf->societe->enabled)
            {
                print '<tr><td valign="top">'.$langs->trans("LinkToCompanyContact").'</td>';
                print '<td>';
                if ($fuser->societe_id > 0)
                {
                    $societe = new Societe($db);
                    $societe->fetch($fuser->societe_id);
                    print $societe->getNomUrl(1,'');
                }
                else
                {
                    print $langs->trans("ThisUserIsNot");
                }
                if ($fuser->contact_id)
                {
                    $contact = new Contact($db);
                    $contact->fetch($fuser->contact_id);
                    if ($fuser->societe_id > 0) print ' / ';
                    else print '<br>';
                    print '<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$fuser->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
                }
                print '</td>';
                print '</tr>'."\n";
            }

            // Module Adherent
            if ($conf->adherent->enabled)
            {
                $langs->load("members");
                print '<tr><td valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
                print '<td>';
                if ($fuser->fk_member)
                {
                    $adh=new Adherent($db);
                    $adh->fetch($fuser->fk_member);
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

            print "</table>\n";

            print "</div>\n";


            /*
             * Barre d'actions
             */

            print '<div class="tabsAction">';

            if ($caneditfield && (empty($conf->multicompany->enabled) || (($fuser->entity == $conf->entity) || $fuser->entity == $user->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)) )
            {
                if (! empty($conf->global->MAIN_ONLY_LOGIN_ALLOWED))
                {
                    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("DisabledInMonoUserMode")).'">'.$langs->trans("Modify").'</a>';
                }
                else
                {
                    print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
                }
            }
            elseif ($caneditpassword && ! $fuser->ldap_sid &&
            (empty($conf->multicompany->enabled) || ($fuser->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)) )
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("EditPassword").'</a>';
            }

            // Si on a un gestionnaire de generation de mot de passe actif
            if ($conf->global->USER_PASSWORD_GENERATED != 'none')
            {
                if (($user->id != $id && $caneditpassword) && $fuser->login && !$fuser->ldap_sid &&
                (empty($conf->multicompany->enabled) || ($fuser->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)))
                {
                    print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a>';
                }

                if (($user->id != $id && $caneditpassword) && $fuser->login && !$fuser->ldap_sid &&
                (empty($conf->multicompany->enabled) || ($fuser->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)) )
                {
                    if ($fuser->email) print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a>';
                    else print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NoEMail")).'">'.$langs->trans("SendNewPassword").'</a>';
                }
            }

            // Activer
            if ($user->id <> $id && $candisableuser && $fuser->statut == 0 &&
            (empty($conf->multicompany->enabled) || ($fuser->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)) )
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a>';
            }
            // Desactiver
            if ($user->id <> $id && $candisableuser && $fuser->statut == 1 &&
            (empty($conf->multicompany->enabled) || ($fuser->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)) )
            {
                print '<a class="butActionDelete" href="fiche.php?action=disable&amp;id='.$fuser->id.'">'.$langs->trans("DisableUser").'</a>';
            }
            // Delete
            if ($user->id <> $id && $candisableuser &&
            (empty($conf->multicompany->enabled) || ($fuser->entity == $conf->entity) || ($conf->multicompany->transverse_mode && $conf->entity == 1)) )
            {
                print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$fuser->id.'">'.$langs->trans("DeleteUser").'</a>';
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
                $groupslist = $usergroup->listGroupsForUser($fuser->id);

                if (! empty($groupslist))
                {
                    if( ! ($conf->multicompany->enabled && $conf->multicompany->transverse_mode))
                    {
                        foreach($groupslist as $groupforuser)
                        {
                            $exclude[]=$groupforuser->id;
                        }
                    }
                }

                if ($caneditgroup)
                {
                    $form = new Form($db);
                    print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">'."\n";
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
                    print '<input type="hidden" name="action" value="addgroup" />';
                    print '<table class="noborder" width="100%">'."\n";
                    print '<tr class="liste_titre"><td class="liste_titre" width="25%">'.$langs->trans("GroupsToAdd").'</td>'."\n";
                    print '<td>';
                    print $form->select_dolgroups('','group',1,$exclude,0,'','',$fuser->entity);
                    print ' &nbsp; ';
                    // Multicompany
                    if (! empty($conf->multicompany->enabled))
                    {
                        if ($conf->entity == 1 && $conf->multicompany->transverse_mode)
                        {
                            $mc = new ActionsMulticompany($db);
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
                    print '</td></tr>'."\n";
                    print '</table></form>'."\n";

                    print '<br>';
                }

                /*
                 * Groupes affectes
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
                            print '<a href="'.DOL_URL_ROOT.'/user/group/fiche.php?id='.$group->id.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$group->nom.'</a>';
                        }
                        else
                        {
                            print img_object($langs->trans("ShowGroup"),"group").' '.$group->nom;
                        }
                        print '</td>';
                        if(! empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
                        {
                            $mc = new ActionsMulticompany($db);
                            $mc->getInfo($group->usergroup_entity);
                            print '<td class="valeur">'.$mc->label."</td>";
                        }
                        print '<td align="right">';
                        if ($caneditgroup)
                        {
                            print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$fuser->id.'&amp;action=removegroup&amp;group='.$group->id.'&amp;entity='.$group->usergroup_entity.'">';
                            print img_delete($langs->trans("RemoveFromGroup"));
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

        if ($action == 'edit' && ($canedituser || ($user->id == $fuser->id)))
        {

            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$fuser->id.'" method="POST" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
            print '<table width="100%" class="border">';

            $rowspan=12;

            if ($conf->societe->enabled) $rowspan++;
            if ($conf->adherent->enabled) $rowspan++;
            if ($conf->webcalendar->enabled) $rowspan++;
            if ($conf->phenix->enabled) $rowspan+=2;

            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $fuser->id;
            print '</td>';
            print '</tr>';

            // Lastname
            print "<tr>";
            print '<td valign="top" class="fieldrequired">'.$langs->trans("Lastname").'</td>';
            print '<td>';
            if ($caneditfield && !$fuser->ldap_sid)
            {
                print '<input size="30" type="text" class="flat" name="nom" value="'.$fuser->nom.'">';
            }
            else
            {
                print '<input type="hidden" name="nom" value="'.$fuser->nom.'">';
                print $fuser->nom;
            }
            print '</td>';
            // Photo
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            print $html->showphoto('userphoto',$fuser);
            if ($caneditfield)
            {
                if ($fuser->photo) print "<br>\n";
                print '<table class="nobordernopadding">';
                if ($fuser->photo) print '<tr><td align="center"><input type="checkbox" class="flat" name="deletephoto" id="photodelete"> '.$langs->trans("Delete").'<br><br></td></tr>';
                print '<tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
                print '<tr><td><input type="file" class="flat" name="photo" id="photoinput"></td></tr>';
                print '</table>';
            }
            print '</td>';

            print '</tr>';

            // Firstname
            print "<tr>".'<td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td>';
            if ($caneditfield && !$fuser->ldap_sid)
            {
                print '<input size="30" type="text" class="flat" name="prenom" value="'.$fuser->prenom.'">';
            }
            else
            {
                print '<input type="hidden" name="prenom" value="'.$fuser->prenom.'">';
                print $fuser->prenom;
            }
            print '</td></tr>';

            // Login
            print "<tr>".'<td valign="top"><span class="fieldrequired">'.$langs->trans("Login").'</span></td>';
            print '<td>';
            if ($user->admin  && !$fuser->ldap_sid)
            {
                print '<input size="12" maxlength="24" type="text" class="flat" name="login" value="'.$fuser->login.'">';
            }
            else
            {
                print '<input type="hidden" name="login" value="'.$fuser->login.'">';
                print $fuser->login;
            }
            print '</td>';
            print '</tr>';

            // Pass
            print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
            print '<td>';
            if ($fuser->ldap_sid)
            {
                $text=$langs->trans("DomainPassword");
            }
            else if ($caneditpassword)
            {
                $text='<input size="12" maxlength="32" type="password" class="flat" name="password" value="'.$fuser->pass.'">';
                if ($dolibarr_main_authentication && $dolibarr_main_authentication == 'http')
                {
                    $text=$html->textwithpicto($text,$langs->trans("DolibarrInHttpAuthenticationSoPasswordUseless",$dolibarr_main_authentication),1,'warning');
                }
            }
            else
            {
                $text=preg_replace('/./i','*',$fuser->pass);
            }
            print $text;
            print "</td></tr>\n";

            // Administrator
            print "<tr>".'<td valign="top">'.$langs->trans("Administrator").'</td>';
            if ($fuser->societe_id > 0)
            {
                print '<td>';
                print '<input type="hidden" name="admin" value="'.$fuser->admin.'">'.yn($fuser->admin);
                print ' ('.$langs->trans("ExternalUser").')';
                print '</td></tr>';
            }
            else
            {
                print '<td>';
                $nbSuperAdmin = $user->getNbOfUsers('superadmin');
                if ($user->admin
                && ($user->id != $fuser->id)                    // Don't downgrade ourself
                && ($fuser->entity > 0 || $nbSuperAdmin > 1)    // Don't downgrade a superadmin if alone
                )
                {
                    print $form->selectyesno('admin',$fuser->admin,1);

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

                        $checked=(($fuser->admin && ! $fuser->entity) ? ' checked' : '');
                        print '<input type="checkbox" name="superadmin" value="1"'.$checked.' /> '.$langs->trans("SuperAdministrator");
                    }
                }
                else
                {
                    $yn = yn($fuser->admin);
                    print '<input type="hidden" name="admin" value="'.$fuser->admin.'">';
                    if (! empty($conf->multicompany->enabled) && ! $fuser->entity) print $html->textwithpicto($yn,$langs->trans("DontDowngradeSuperAdmin"),1,'warning');
                    else print $yn;
                }
                print '</td></tr>';
            }

            //Multicompany
            if (! empty($conf->multicompany->enabled))
            {
            	if(empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            	{
            		$mc = new ActionsMulticompany($db);
            		print "<tr>".'<td valign="top">'.$langs->trans("Entity").'</td>';
            		print "<td>".$mc->select_entities($conf->entity);
            		print "</td></tr>\n";
            	}
            	else
            	{
            		print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
            	}
            }
            else
            {
            	// Type
            	print '<tr><td width="25%" valign="top">'.$langs->trans("Type").'</td>';
            	print '<td>';
            	if ($fuser->societe_id)
            	{
            		print $langs->trans("External");
            	}
            	else if ($fuser->ldap_sid)
            	{
            		print $langs->trans("DomainUser");
            	}
            	else
            	{
            		print $langs->trans("Internal");
            	}
            	print '</td></tr>';
            }

            // Tel pro
            print "<tr>".'<td valign="top">'.$langs->trans("PhonePro").'</td>';
            print '<td>';
            if ($caneditfield  && !$fuser->ldap_sid)
            {
                print '<input size="20" type="text" name="office_phone" class="flat" value="'.$fuser->office_phone.'">';
            }
            else
            {
                print '<input type="hidden" name="office_phone" value="'.$fuser->office_phone.'">';
                print $fuser->office_phone;
            }
            print '</td></tr>';

            // Tel mobile
            print "<tr>".'<td valign="top">'.$langs->trans("PhoneMobile").'</td>';
            print '<td>';
            if ($caneditfield && !$fuser->ldap_sid)
            {
                print '<input size="20" type="text" name="user_mobile" class="flat" value="'.$fuser->user_mobile.'">';
            }
            else
            {
                print '<input type="hidden" name="user_mobile" value="'.$fuser->user_mobile.'">';
                print $fuser->user_mobile;
            }
            print '</td></tr>';

            // Fax
            print "<tr>".'<td valign="top">'.$langs->trans("Fax").'</td>';
            print '<td>';
            if ($caneditfield  && !$fuser->ldap_sid)
            {
                print '<input size="20" type="text" name="office_fax" class="flat" value="'.$fuser->office_fax.'">';
            }
            else
            {
                print '<input type="hidden" name="office_fax" value="'.$fuser->office_fax.'">';
                print $fuser->office_fax;
            }
            print '</td></tr>';

            // EMail
            print "<tr>".'<td valign="top"'.($conf->global->USER_MAIL_REQUIRED?' class="fieldrequired"':'').'>'.$langs->trans("EMail").'</td>';
            print '<td>';
            if ($caneditfield  && !$fuser->ldap_sid)
            {
                print '<input size="40" type="text" name="email" class="flat" value="'.$fuser->email.'">';
            }
            else
            {
                print '<input type="hidden" name="email" value="'.$fuser->email.'">';
                print $fuser->email;
            }
            print '</td></tr>';

            // Signature
            print "<tr>".'<td valign="top">'.$langs->trans("Signature").'</td>';
            print '<td>';
            print '<textarea name="signature" rows="5" cols="90">'.dol_htmlentitiesbr_decode($fuser->signature).'</textarea>';
            print '</td></tr>';

            // openid
            if (preg_match('/myopenid/',$conf->authmode))
            {
                print "<tr>".'<td valign="top">'.$langs->trans("url_openid").'</td>';
                print '<td>';
                if ($caneditfield  && !$fuser->ldap_sid)
                {
                    print '<input size="40" type="text" name="openid" class="flat" value="'.$fuser->openid.'">';
                }
                else
                {
                    print '<input type="hidden" name="openid" value="'.$fuser->openid.'">';
                    print $fuser->openid;
                }
                print '</td></tr>';
            }

            // Statut
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $fuser->getLibStatut(4);
            print '</td></tr>';

            // Autres caracteristiques issus des autres modules

            // Module Webcalendar
            if ($conf->webcalendar->enabled)
            {
                $langs->load("other");
                print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
                print '<td>';
                if ($caneditfield) print '<input size="30" type="text" class="flat" name="webcal_login" value="'.$fuser->webcal_login.'">';
                else print $fuser->webcal_login;
                print '</td></tr>';
            }

            // Module Phenix
            if ($conf->phenix->enabled)
            {
                $langs->load("other");
                print "<tr>".'<td valign="top">'.$langs->trans("LoginPhenix").'</td>';
                print '<td>';
                if ($caneditfield) print '<input size="30" type="text" class="flat" name="phenix_login" value="'.$fuser->phenix_login.'">';
                else print $fuser->phenix_login;
                print '</td></tr>';
                print "<tr>".'<td valign="top">'.$langs->trans("PassPhenix").'</td>';
                print '<td>';
                if ($caneditfield) print '<input size="30" type="password" class="flat" name="phenix_pass" value="'.$fuser->phenix_pass_crypted.'">';
                else print preg_replace('/./i','*',$fuser->phenix_pass_crypted);
                print '</td></tr>';
            }

            // Company / Contact
            if ($conf->societe->enabled)
            {
                print '<tr><td width="25%" valign="top">'.$langs->trans("LinkToCompanyContact").'</td>';
                print '<td>';
                if ($fuser->societe_id > 0)
                {
                    $societe = new Societe($db);
                    $societe->fetch($fuser->societe_id);
                    print $societe->getNomUrl(1,'');
                    if ($fuser->contact_id)
                    {
                        $contact = new Contact($db);
                        $contact->fetch($fuser->contact_id);
                        print ' / <a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$fuser->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dol_trunc($contact->getFullName($langs),32).'</a>';
                    }
                }
                else
                {
                    print $langs->trans("ThisUserIsNot");
                }
                print '</td>';
                print "</tr>\n";
            }

            // Module Adherent
            if ($conf->adherent->enabled)
            {
                $langs->load("members");
                print '<tr><td width="25%" valign="top">'.$langs->trans("LinkedToDolibarrMember").'</td>';
                print '<td>';
                if ($fuser->fk_member)
                {
                    $adh=new Adherent($db);
                    $adh->fetch($fuser->fk_member);
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

            print '</table>';

            print '<br><center>';
            print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
            print ' &nbsp; ';
            print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
            print '</center>';

            print '</form>';

            print '</div>';
        }

        $ldap->close;
    }
}

$db->close();

llxFooter();

?>
