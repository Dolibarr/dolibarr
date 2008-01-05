<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2005      Lionel COUSTEIX      <etm_ltd@tiscali.co.uk>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 */

/**     
        \file       htdocs/user/fiche.php
        \brief      Onglet user et permissions de la fiche utilisateur
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");
if ($conf->ldap->enabled) require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
if ($conf->adherent->enabled) require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");

$user->getrights('user');

// Defini si peux creer un utilisateur ou gerer groupe sur un utilisateur
$canadduser=($user->admin || $user->rights->user->user->creer);
// Defini si peux lire/modifier permisssions
$canreadperms=($user->admin || $user->rights->user->user->lire);
$caneditperms=($user->admin || $user->rights->user->user->creer);
$candisableperms=($user->admin || $user->rights->user->user->supprimer);
// Defini si peux lire/modifier info user ou mot de passe
if ($_GET["id"])
{
  // $user est le user qui edite, $_GET["id"] est l'id de l'utilisateur edite
  $caneditfield=( (($user->id == $_GET["id"]) && $user->rights->user->self->creer)
		  || (($user->id != $_GET["id"]) && $user->rights->user->user->creer) );
  $caneditpassword=( (($user->id == $_GET["id"]) && $user->rights->user->self->password)
		     || (($user->id != $_GET["id"]) && $user->rights->user->user->password) );
}
if ($user->id <> $_GET["id"] && ! $canreadperms)
{
  accessforbidden();
}

$langs->load("users");
$langs->load("companies");
$langs->load("ldap");

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];

$form = new Form($db);



/**
 * Actions
 */
if ($_GET["subaction"] == 'addrights' && $caneditperms)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->addrights($_GET["rights"]);
}

if ($_GET["subaction"] == 'delrights' && $caneditperms)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->delrights($_GET["rights"]);
}

if ($_POST["action"] == 'confirm_disable' && $_POST["confirm"] == "yes")
{
    if ($_GET["id"] <> $user->id)
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->fetch($_GET["id"]);
        $edituser->setstatus(0);
        Header("Location: ".DOL_URL_ROOT.'/user/fiche.php?id='.$_GET["id"]);
        exit;
    }
}
if ($_POST["action"] == 'confirm_enable' && $_POST["confirm"] == "yes")
{
    if ($_GET["id"] <> $user->id)
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->fetch($_GET["id"]);
        $edituser->setstatus(1);
        Header("Location: ".DOL_URL_ROOT.'/user/fiche.php?id='.$_GET["id"]);
        exit;
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes")
{
    if ($_GET["id"] <> $user->id)
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->id=$_GET["id"];
        $result = $edituser->delete();
        if ($result < 0)
        {
			$langs->load("errors");
        	$message='<div class="error">'.$langs->trans("UserCannotBeDelete").'</div>';
        }
        else
        {
        	Header("Location: index.php");
          exit;
        }
    }
}

// Action ajout user
if ($_POST["action"] == 'add' && $canadduser)
{
	$message="";
	if (! $_POST["nom"]) {
		$message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
		$action="create";       // Go back to create page
	}
	if (! $_POST["login"]) {
		$message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
		$action="create";       // Go back to create page
	}

	if (! $message)
	{
		$edituser = new User($db);

		$edituser->nom           = $_POST["nom"];
		$edituser->prenom        = $_POST["prenom"];
		$edituser->login         = $_POST["login"];
		$edituser->admin         = $_POST["admin"];
		$edituser->office_phone  = $_POST["office_phone"];
		$edituser->office_fax    = $_POST["office_fax"];
		$edituser->user_mobile   = $_POST["user_mobile"];
		$edituser->email         = $_POST["email"];
		$edituser->webcal_login  = $_POST["webcal_login"];
		$edituser->phenix_login  = $_POST["phenix_login"];
		$edituser->phenix_pass   = $_POST["phenix_pass"];
		$edituser->note          = $_POST["note"];
		$edituser->ldap_sid      = $_POST["ldap_sid"];
		
		$db->begin();
		
		$id = $edituser->create($user);
		
		if ($id > 0)
		{
			if (isset($_POST['password']) && trim($_POST['password']))
			{
				$edituser->setPassword($user,trim($_POST['password']),$conf->global->DATABASE_PWD_ENCRYPTED);
			}
			
			$db->commit();
			
			Header("Location: fiche.php?id=$id");
			exit;
		}
		else
		{
			$db->rollback();
			
			//$message='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$edituser->login).'</div>';
			$message='<div class="error">'.$edituser->error.'</div>';
			
			$action="create";       // Go back to create page
		}

	}
}

// Action ajout groupe utilisateur
if ($_POST["action"] == 'addgroup' && $caneditfield)
{
    if ($_POST["group"])
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->SetInGroup($_POST["group"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
        exit;
    }
}

if ($_GET["action"] == 'removegroup' && $caneditfield)
{
    if ($_GET["group"])
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->RemoveFromGroup($_GET["group"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
        exit;
    }
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"] && $caneditfield)
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

		$edituser = new User($db, $_GET["id"]);
		$edituser->fetch();

		//$edituser->oldpass_indatabase = $edituser->pass_indatabase;

		$edituser->nom           = $_POST["nom"];
		$edituser->prenom        = $_POST["prenom"];
		$edituser->login         = $_POST["login"];
		$edituser->pass          = $_POST["pass"];
		$edituser->admin         = $_POST["admin"];
		$edituser->office_phone  = $_POST["office_phone"];
		$edituser->office_fax    = $_POST["office_fax"];
		$edituser->user_mobile   = $_POST["user_mobile"];
		$edituser->email         = $_POST["email"];
		$edituser->webcal_login  = $_POST["webcal_login"];
		$edituser->phenix_login  = $_POST["phenix_login"];
		$edituser->phenix_pass   = $_POST["phenix_pass"];

		$ret=$edituser->update($user);
		if ($ret < 0)
		{
			if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			{
				$message.='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$edituser->login).'</div>';
			}
			else
			{
				$message.='<div class="error">'.$edituser->error.'</div>';
			}
		}
		if ($ret >= 0 && isset($_POST["password"]) && $_POST["password"] !='')
		{
			$ret=$edituser->setPassword($user,$_POST["password"],1);
			if ($ret < 0)
			{
				$message.='<div class="error">'.$edituser->error.'</div>';
			}
		}

		if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
		{
			// If photo is provided
			if (! is_dir($conf->users->dir_output))
			{
				create_exdir($conf->users->dir_output);
			}
			if (is_dir($conf->users->dir_output))
			{
				$newfile=$conf->users->dir_output . "/" . $edituser->id . ".jpg";
				if (! doliMoveFileUpload($_FILES['photo']['tmp_name'],$newfile))
				{
					$message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
				}
			}
		}

		if ($ret >= 0)
		{
			$message.='<div class="ok">'.$langs->trans("UserModified").'</div>';
			$db->commit();
		} else
		{
			$db->rollback();
		}
	}
}

// Action modif mot de passe
if ((($_POST["action"] == 'confirm_password' && $_POST["confirm"] == 'yes')
      || $_GET["action"] == 'confirm_passwordsend') && $caneditpassword)
{
    $edituser = new User($db, $_GET["id"]);
    $edituser->fetch();

    $newpassword=$edituser->setPassword($user,'');
    if ($newpassword < 0)
    {
        // Echec
        $message = '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
    }
    else 
    {
        // Succes
        if ($_GET["action"] == 'confirm_passwordsend')
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
if ($_POST["action"] == 'adduserldap')
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
		$required_fields=array_unique(array_values(array_filter($required_fields, "dolValidElement")));

		$ldapusers = $ldap->getRecords($selecteduser, $conf->global->LDAP_USER_DN, $conf->global->LDAP_KEY_USERS, $required_fields);
		//print_r($ldapusers);

		if (is_array($ldapusers))
		{
			foreach ($ldapusers as $key => $attribute)
			{
				$ldap_nom    = $attribute[$conf->global->LDAP_FIELD_NAME];
				$ldap_prenom = $attribute[$conf->global->LDAP_FIELD_FIRSTNAME];
				$ldap_login  = $attribute[$conf->global->LDAP_FIELD_LOGIN];
				$ldap_loginsmb = $attribute[$conf->global->LDAP_FIELD_LOGIN_SAMBA];
				$ldap_pass         = $attribute[$conf->global->LDAP_FIELD_PASSWORD];
				$ldap_pass_crypted = $attribute[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED];
				$ldap_phone  = $attribute[$conf->global->LDAP_FIELD_PHONE];
				$ldap_fax    = $attribute[$conf->global->LDAP_FIELD_FAX];
				$ldap_mobile = $attribute[$conf->global->LDAP_FIELD_MOBILE];
				$ldap_mail   = $attribute[$conf->global->LDAP_FIELD_MAIL];
				$ldap_sid    = $attribute[$conf->global->LDAP_FIELD_SID];
			}
		}
	}
	else
	{
		$message='<div class="error">'.$ldap->error.'</div>';
	}
}



/*
 * Affichage page
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
	
	print_titre($langs->trans("NewUser"));
	print "<br>";
	
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
			$required_fields=array_unique(array_values(array_filter($required_fields, "dolValidElement")));
			
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
	
	if ($message) { print $message.'<br>'; }
	
	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr')
	{
		// Si la liste des users est rempli, on affiche la liste deroulante
		if (is_array($liste))
		{
			print "\n\n<!-- Form liste LDAP debut -->\n";
	
			print '<form name="add_user_ldap" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<table width="100%" class="border"><tr>';
			print '<td width="160">';
			print $langs->trans("LDAPUsers");
			print '</td>';
			print '<td>';
			print '<input type="hidden" name="action" value="adduserldap">';
			print $html->select_array('users', $liste, '', 1);
			print '</td><td align="center">';
			print '<input type="submit" class="button" value="'.$langs->trans('Get').'">';
			print '</td></tr></table>';
			print '</form>';

			print "\n<!-- Form liste LDAP fin -->\n\n";
			print '<br>';
		}
	}
	
	print '<form action="fiche.php" method="post" name="createuser">';
	print '<input type="hidden" name="action" value="add">';
	if ($ldap_sid) print '<input type="hidden" name="ldap_sid" value="'.$ldap_sid.'">';
	
	print '<table class="border" width="100%">';
	
	print '<tr>';

	// Nom
	print '<td valign="top" width="160">'.$langs->trans("Lastname").'*</td>';
	print '<td>';
	if ($ldap_nom)
	{
		print '<input type="hidden" name="nom" value="'.$ldap_nom.'">';
		print $ldap_nom;
	}
	else
	{
		print '<input size="30" type="text" name="nom" value="">';
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
		print '<input size="30" type="text" name="prenom" value="">';
	}
	print '</td></tr>';
	
	// Login
	print '<tr><td valign="top">'.$langs->trans("Login").'*</td>';
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
		print '<input size="20" maxsize="24" type="text" name="login" value="">';
	}
	print '</td></tr>';
	
	if (!$ldap_sid)
	{
		$generated_password='';
		if ($conf->global->USER_PASSWORD_GENERATED)
		{
			$nomclass="modGeneratePass".ucfirst($conf->global->USER_PASSWORD_GENERATED);
			$nomfichier=$nomclass.".class.php";
			//print DOL_DOCUMENT_ROOT."/includes/modules/security/generate/".$nomclass;
			require_once(DOL_DOCUMENT_ROOT."/includes/modules/security/generate/".$nomfichier);
			$genhandler=new $nomclass($db,$conf,$lang,$user);
			$generated_password=$genhandler->getNewGeneratedPassword();
		}
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
			print eregi_replace('.','*',$ldap_pass);
		}
		else
		{
			print '<input size="30" maxsize="32" type="text" name="password" value="'.$password.'">';
		}
	}
	print '</td></tr>';
	
	// Administrateur
	if ($user->admin)
	{
		print '<tr><td valign="top">'.$langs->trans("Administrator").'</td>';
		print '<td>';
		print $form->selectyesno('admin',0,1);
		print "</td></tr>\n";
	}
	
	// Type
	print '<tr><td valign="top">'.$langs->trans("Type").'</td>';
	print '<td>';
	print $html->textwithhelp($langs->trans("Internal"),$langs->trans("InternalExternalDesc"));
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
		print '<input size="20" type="text" name="office_phone" value="">';
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
		print '<input size="20" type="text" name="user_mobile" value="">';
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
		print '<input size="20" type="text" name="office_fax" value="">';
	}
	print '</td></tr>';
	
	// EMail
	print '<tr><td valign="top">'.$langs->trans("EMail").'</td>';
	print '<td>';
	if ($ldap_mail)
	{
		print '<input type="hidden" name="email" value="'.$ldap_mail.'">';
		print $ldap_mail;
	}
	else
	{
		print '<input size="40" type="text" name="email" value="">';
	}
	print '</td></tr>';
	
	// Note
	print '<tr><td valign="top">';
	print $langs->trans("Note");
	print '</td><td>';
	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_USER)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('note','',180,'dolibarr_notes','',false);
		$doleditor->Create();
	}
	else
	{
		print '<textarea class="flat" name="note" rows="'.ROWS_4.'" cols="90">';
		print '</textarea>';
	}
	print "</td></tr>\n";
	
	// Autres caracteristiques issus des autres modules
	
	// Module Webcalendar
	if ($conf->webcal->enabled)
	{
		print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
		print '<td><input size="30" type="text" name="webcal_login" value=""></td></tr>';
	}
	
	// Module Phenix
	if ($conf->phenix->enabled)
	{
		print "<tr>".'<td valign="top">'.$langs->trans("LoginPenix").'</td>';
		print '<td><input size="30" type="text" name="phenix_login" value=""></td></tr>';
		print "<tr>".'<td valign="top">'.$langs->trans("PassPenix").'</td>';
		print '<td><input size="30" type="text" name="phenix_pass" value=""></td></tr>';
	}
	
	print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("CreateUser").'" type="submit"></td></tr>';
	print "</table>\n";
	print "</form>";
}
else
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Visu et edition                                                            */
    /*                                                                            */
    /* ************************************************************************** */

    if ($_GET["id"])
    {
    	$fuser = new User($db, $_GET["id"]);
    	$fuser->fetch();

    	// Connexion ldap
    	// pour recuperer passDoNotExpire et userChangePassNextLogon
    	if ($conf->ldap->enabled && $fuser->ldap_sid)
    	{
    		$ldap = new Ldap();
    		$result=$ldap->connect_bind();
    		if ($result > 0)
    		{
    			$entries = $ldap->fetch($fuser->login);
    			if (! $entries)
    			{
    				$message .= $ldap->error;
    			}
    			
    			$passDoNotExpire = 0;
    			$userChangePassNextLogon = 0;

	    		//On verifie les options du compte
	    		foreach ($ldap->uacf as $key => $statut)
	    		{
	    			if ($key == 65536)
	    			{
	    				$passDoNotExpire = 1;
	    			}
	    		}
	    		if ($ldap->pwdlastset == 0)
	    		{
	    			$userChangePassNextLogon = 1;
	    		}
    		}
    	}

		/*
		 * Affichage onglets
		 */
		$head = user_prepare_head($fuser);
	
		dolibarr_fiche_head($head, 'user', $langs->trans("User"));


        /*
         * Confirmation reinitialisation mot de passe
         */
        if ($action == 'password')
        {
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$fuser->login),"confirm_password");
            print '<br>';
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$fuser->login),"confirm_passwordsend");
            print '<br>';
        }

        /*
         * Confirmation desactivation
         */
        if ($action == 'disable')
        {
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$fuser->login),"confirm_disable");
            print '<br>';
        }

        /*
         * Confirmation activation
         */
        if ($action == 'enable')
        {
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("EnableAUser"),$langs->trans("ConfirmEnableUser",$fuser->login),"confirm_enable");
            print '<br>';
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$fuser->login),"confirm_delete");
            print '<br>';
        }


        /*
         * Fiche en mode visu
         */
        if ($_GET["action"] != 'edit')
        {
            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
			print $html->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
			print '</td>';
			print '</tr>';

            // Nom
            print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td colspan="2">'.$fuser->nom.'</td>';
            print "</tr>\n";

            // Prenom
            print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td colspan="2">'.$fuser->prenom.'</td>';
            print "</tr>\n";

            $rowspan=12;

			// Login
            print '<tr><td width="25%" valign="top">'.$langs->trans("Login").'</td>';
            if ($fuser->ldap_sid && $fuser->statut==0)
            {
            	print '<td width="50%" class="error">'.$langs->trans("LoginAccountDisableInDolibarr").'</td>';
            }
            else
            {
            	print '<td width="50%">'.$fuser->login.'</td>';
            }
			print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            if (file_exists($conf->users->dir_output."/".$fuser->id.".jpg"))
            {
                print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&file='.$fuser->id.'.jpg">';
            }
            else
            {
                print '<img width="100" src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
            }
            print '</td>';
            print '</tr>';

            // Password
            print '<tr><td width="25%" valign="top">'.$langs->trans("Password").'</td>';
            if ($fuser->ldap_sid)
            {
            	if ($passDoNotExpire)
            	{
            		print '<td>'.$langs->trans("LdapUacf_".$statut).'</td>';
            	}
            	else if($userChangePassNextLogon)
            	{
            		print '<td class="warning">'.$langs->trans("UserMustChangePassNextLogon",$ldap->domainFQDN).'</td>';
            	}
            	else
            	{
            		print '<td>'.$langs->trans("DomainPassword").'</td>';
            	}
            }
            else
            {
            	print '<td>';
            	if ($fuser->pass) print eregi_replace('.','*',$fuser->pass);
            	else
            	{
	            	if ($user->admin) print $langs->trans("Crypted").': '.$fuser->pass_indatabase_crypted;
            		else print $langs->trans("Hidden");
            	}
            	print "</td>";
            }
            print "</tr>\n";

            // Administrateur
            print '<tr><td width="25%" valign="top">'.$langs->trans("Administrator").'</td>';
            print '<td>'.yn($fuser->admin);
            if ($fuser->admin) print ' '.img_picto($langs->trans("Administrator"),"star");
            print '</td>';
            print "</tr>\n";
            
            // Type
            print '<tr><td width="25%" valign="top">'.$langs->trans("Type").'</td>';
            print '<td>';
            if ($fuser->societe_id)
            {
                print $html->textwithhelp($langs->trans("External"),$langs->trans("InternalExternalDesc"));
            }
            else if ($fuser->ldap_sid)
            {
            	print $langs->trans("DomainUser",$ldap->domainFQDN);
            }
            else
            {
                print $html->textwithhelp($langs->trans("Internal"),$langs->trans("InternalExternalDesc"));
            }
            print '</td></tr>';

            // Company / Contact
            print '<tr><td width="25%" valign="top">'.$langs->trans("Company").' / '.$langs->trans("Contact").'</td>';
            print '<td>';
            if ($fuser->societe_id > 0)
            {
                $societe = new Societe($db);
                $societe->fetch($fuser->societe_id);
                print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$fuser->societe_id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.dolibarr_trunc($societe->nom,32).'</a>';
                if ($fuser->contact_id)
                {
                    $contact = new Contact($db);
                    $contact->fetch($fuser->contact_id);
                    print ' / '.'<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$fuser->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dolibarr_trunc($contact->getFullName($langs),32).'</a>';
                }
            }            
            else
            {
                print $langs->trans("ThisUserIsNot");
            }
            print '</td>';
            print "</tr>\n";

            // Tel pro
            print '<tr><td width="25%" valign="top">'.$langs->trans("PhonePro").'</td>';
            print '<td>'.$fuser->office_phone.'</td>';
            
            // Tel mobile
            print '<tr><td width="25%" valign="top">'.$langs->trans("PhoneMobile").'</td>';
            print '<td>'.$fuser->user_mobile.'</td>';
            
            // Fax
            print '<tr><td width="25%" valign="top">'.$langs->trans("Fax").'</td>';
            print '<td>'.$fuser->office_fax.'</td>';
            
            // EMail
            print '<tr><td width="25%" valign="top">'.$langs->trans("EMail").'</td>';
            print '<td><a href="mailto:'.$fuser->email.'">'.$fuser->email.'</a></td>';
            print "</tr>\n";
            
            // Statut
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $fuser->getLibStatut(4);
            print '</td></tr>';
		
            print '<tr><td width="25%" valign="top">'.$langs->trans("LastConnexion").'</td>';
            print '<td>'.dolibarr_print_date($fuser->datelastlogin,"dayhour").'</td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("PreviousConnexion").'</td>';
            print '<td>'.dolibarr_print_date($fuser->datepreviouslogin,"dayhour").'</td>';
            print "</tr>\n";

            // Autres caracteristiques issus des autres modules
            
            // Module Webcalendar
            if ($conf->webcal->enabled)
            {
                $langs->load("other");
                print '<tr><td width="25%" valign="top">'.$langs->trans("LoginWebcal").'</td>';
                print '<td colspan="2">'.$fuser->webcal_login.'&nbsp;</td>';
                print "</tr>\n";
            }
            
            // Module Phenix
            if ($conf->phenix->enabled)
            {
                $langs->load("other");
                print '<tr><td width="25%" valign="top">'.$langs->trans("LoginPhenix").'</td>';
                print '<td colspan="2">'.$fuser->phenix_login.'&nbsp;</td>';
                print "</tr>\n";
                print '<tr><td width="25%" valign="top">'.$langs->trans("PassPhenix").'</td>';
                print '<td colspan="2">'.eregi_replace('.','*',$fuser->phenix_pass_crypted).'&nbsp;</td>';
                print "</tr>\n";
            }
            
            // Module Adherent
            if ($conf->adherent->enabled)
            {
            	$langs->load("members");
            	print '<tr><td width="25%" valign="top">'.$langs->trans("MemberAccount").'</td>';
            	print '<td colspan="2">';
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

            print "</table>\n";

            print "</div>\n";

            if ($message) { print $message; }


            /*
             * Barre d'actions
             */
             
            print '<div class="tabsAction">';


            if ($caneditfield)
            {
               	print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
            }
            elseif ($caneditpassword && ! $fuser->ldap_sid)
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("EditPassword").'</a>';
            }

	       	// Si on a un gestionnaire de generation de mot de passe actif
			if ($conf->global->USER_PASSWORD_GENERATED != 'none')
			{
	            if (($user->id != $_GET["id"] && $caneditpassword) && $fuser->login && !$fuser->ldap_sid)
	            {
	                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a>';
	            }
			
	            if (($user->id != $_GET["id"] && $caneditpassword) && $fuser->email && $fuser->login && !$fuser->ldap_sid)
	            {
	                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a>';
	            }
			}

            // Activer
            if ($user->id <> $_GET["id"] && $candisableperms && $fuser->statut == 0)
            {
            	print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=enable">'.$langs->trans("Reactivate").'</a>';
            }
            // Desactiver
            if ($user->id <> $_GET["id"] && $candisableperms && $fuser->statut == 1)
            {
                print '<a class="butActionDelete" href="fiche.php?action=disable&amp;id='.$fuser->id.'">'.$langs->trans("DisableUser").'</a>';
            }

            if ($user->id <> $_GET["id"] && $candisableperms)
            {
                print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$fuser->id.'">'.$langs->trans("DeleteUser").'</a>';
            }

            print "</div>\n";
            print "<br>\n";



            /*
             * Liste des groupes dans lequel est l'utilisateur
             */

            print_fiche_titre($langs->trans("ListOfGroupsForUser"));

            // On selectionne les groups
            $uss = array();

            $sql = "SELECT ug.rowid, ug.nom ";
            $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as ug ";
            #      $sql .= " LEFT JOIN llx_usergroup_user ug ON u.rowid = ug.fk_user";
            #      $sql .= " WHERE ug.fk_usergroup IS NULL";
            $sql .= " ORDER BY ug.nom";

            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $i = 0;

                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);

                    $uss[$obj->rowid] = $obj->nom;
                    $i++;
                }
            }
            else {
                dolibarr_print_error($db);
            }

            if ($caneditperms)
            {
                $form = new Form($db);
                print '<form action="fiche.php?id='.$_GET["id"].'" method="post">'."\n";
                print '<input type="hidden" name="action" value="addgroup">';
                print '<table class="noborder" width="100%">'."\n";
                //	  print '<tr class="liste_titre"><td width="25%">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
                print '<tr class="liste_titre"><td class="liste_titre" width="25%">'.$langs->trans("GroupsToAdd").'</td>'."\n";
                print '<td>';
                print $form->select_array("group",$uss);
                print ' &nbsp; ';
                print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                print '</td></tr>'."\n";
                print '</table></form>'."\n";

                print '<br>';
            }

            /*
             * Groupes affectes
             */
            $sql = "SELECT g.rowid, g.nom ";
            $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
            $sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
            $sql .= " WHERE ug.fk_usergroup = g.rowid";
            $sql .= " AND ug.fk_user = ".$_GET["id"];
            $sql .= " ORDER BY g.nom";

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;

                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td class="liste_titre" width="25%">'.$langs->trans("Group").'</td>';
                print "<td>&nbsp;</td></tr>\n";

                if ($num) {
                    $var=True;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($result);
                        $var=!$var;

                        print "<tr $bc[$var]>";
                        print '<td>';
                        if ($canreadperms)
                        {
                        	print '<a href="'.DOL_URL_ROOT.'/user/group/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$obj->nom.'</a>';
                        }
                        else
                        {
                        	print img_object($langs->trans("ShowGroup"),"group").' '.$obj->nom;
                        }
                        print '</td>';
                        print '<td>';

                        if ($caneditperms)
                        {
                            print '<a href="fiche.php?id='.$_GET["id"].'&amp;action=removegroup&amp;group='.$obj->rowid.'">';
                            print img_delete($langs->trans("RemoveFromGroup"));
                        }
                        else
                        {
                            print "-";
                        }
                        print "</td></tr>\n";
                        $i++;
                    }
                }
                else
                {
                    print '<tr><td colspan=2>'.$langs->trans("None").'</td></tr>';
                }
                print "</table>";
                print "<br>";
                $db->free($result);
            }
            else {
                dolibarr_print_error($db);
            }

        }

        /*
         * Fiche en mode edition
         */
        if ($_GET["action"] == 'edit' && ($caneditperms || ($user->id == $fuser->id)))
        {

            print '<form action="fiche.php?id='.$fuser->id.'" method="post" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="action" value="update">';
            print '<table width="100%" class="border">';

            $rowspan=10;

            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $fuser->id;
            print '</td>';
            print '</tr>';

            // Nom
            print "<tr>".'<td valign="top">'.$langs->trans("Name").'*</td>';
            print '<td colspan="2">';
            if ($caneditfield && !$fuser->ldap_sid)
            {
            	print '<input size="30" type="text" class="flat" name="nom" value="'.$fuser->nom.'">';
            }
            else
            {
            	print '<input type="hidden" name="nom" value="'.$fuser->nom.'">';
            	print $fuser->nom;
            }
            print '</td></tr>';
            
            // Prenom
            print "<tr>".'<td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td colspan="2">';
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
            print "<tr>".'<td valign="top">'.$langs->trans("Login").'*</td>';
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
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            if (file_exists($conf->users->dir_output."/".$fuser->id.".jpg"))
            {
                print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&file='.$fuser->id.'.jpg">';
            }
            else
            {
                print '<img src="'.DOL_URL_ROOT.'/theme/common/nophoto.jpg">';
            }
            if ($caneditfield)
            {
	            print '<br><br><table class="noborder"><tr><td>'.$langs->trans("PhotoFile").'</td></tr>';
	            print '<tr><td>';
	            print '<input type="file" class="flat" name="photo">';
	            print '</td></tr></table>';
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
            	$text='<input size="12" maxlength="32" type="password" class="flat" name="pass" value="'.$fuser->pass.'">';
            	if ($dolibarr_main_authentication && $dolibarr_main_authentication == 'http')
            	{
            		$text=$html->textwithwarning($text,$langs->trans("DolibarrInHttpAuthenticationSoPasswordUseless",$dolibarr_main_authentication));
            	}
            }
            else
            {
                $text=eregi_replace('.','*',$fuser->pass);
            }
			      print $text;
            print "</td></tr>\n";
            
            // Administrateur
            print "<tr>".'<td valign="top">'.$langs->trans("Administrator").'</td>';
            if ($fuser->societe_id > 0)
            {
                print '<td>';
                print '<input type="hidden" name="admin" value="'.$fuser->admin.'">'.yn($fuser->admin);
                print '</td></tr>';
            }
            else
            {
                print '<td>';
                if ($user->admin)
                {
                    print $form->selectyesno('admin',$fuser->admin,1);
                }
                else
                {
                    print '<input type="hidden" name="admin" value="'.$fuser->admin.'">'.yn($fuser->admin);
                }
                print '</td></tr>';
            }

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

            // Company / Contact
            print '<tr><td width="25%" valign="top">'.$langs->trans("Company").' / '.$langs->trans("Contact").'</td>';
            print '<td>';
            if ($fuser->societe_id > 0)
            {
                $societe = new Societe($db);
                $societe->fetch($fuser->societe_id);
                print '<a href="'.DOL_URL_ROOT.'/soc.php?id='.$fuser->societe_id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.dolibarr_trunc($societe->nom,32).'</a>';
                if ($fuser->contact_id)
                {
                    $contact = new Contact($db);
                    $contact->fetch($fuser->contact_id);
                    print ' / '.'<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$fuser->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dolibarr_trunc($contact->getFullName($langs),32).'</a>';
                }
            }            
            else
            {
                print $langs->trans("ThisUserIsNot");
            }
            print '</td>';
            print "</tr>\n";

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
            print "<tr>".'<td valign="top">'.$langs->trans("EMail").'</td>';
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
            
            // Statut
            print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
            print '<td>';
            print $fuser->getLibStatut(4);
            print '</td></tr>';
            
            // Autres caracteristiques issus des autres modules
            
            // Module Webcalendar
            if ($conf->webcal->enabled)
            {
            		$langs->load("other");
            		print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
            		print '<td colspan="2">';
            		if ($caneditfield) print '<input size="30" type="text" class="flat" name="webcal_login" value="'.$fuser->webcal_login.'">';
            		else print $fuser->webcal_login;
            		print '</td></tr>';
            }
            
            // Module Phenix
            if ($conf->phenix->enabled)
            {
            		$langs->load("other");
            		print "<tr>".'<td valign="top">'.$langs->trans("LoginPhenix").'</td>';
            		print '<td colspan="2">';
            		if ($caneditfield) print '<input size="30" type="text" class="flat" name="phenix_login" value="'.$fuser->phenix_login.'">';
            		else print $fuser->phenix_login;
            		print '</td></tr>';
            		print "<tr>".'<td valign="top">'.$langs->trans("PassPhenix").'</td>';
            		print '<td colspan="2">';
            		if ($caneditfield) print '<input size="30" type="password" class="flat" name="phenix_pass" value="'.$fuser->phenix_pass_crypted.'">';
            		else print eregi_replace('.','*',$fuser->phenix_pass_crypted);
            		print '</td></tr>';
            }

            print '<tr><td align="center" colspan="3">';
            print '<input value="'.$langs->trans("Save").'" class="button" type="submit" name="save">';
            print ' &nbsp; ';
            print '<input value="'.$langs->trans("Cancel").'" class="button" type="submit" name="cancel">';
            print '</td></tr>';

            print '</table>';
            print '</form>';

			      print '</div>';
         }

        $ldap->close;
    }
}

$db->close();

function dolValidElement($element) {
	return (trim($element) != '');
}

llxFooter('$Date$ - $Revision$');
?>
