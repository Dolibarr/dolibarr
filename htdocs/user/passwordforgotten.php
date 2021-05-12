<?php
/* Copyright (C) 2007-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2008-2011	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014       Teddy Andreotti    	<125155@supinfo.com>
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
 *       \file       htdocs/user/passwordforgotten.php
 *       \brief      Page to ask a new password
 */

<<<<<<< HEAD
define("NOLOGIN",1);	// This means this output page does not require to be logged.
=======
define("NOLOGIN", 1);	// This means this output page does not require to be logged.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';

// Load translation files required by page
$langs->loadLangs(array('errors', 'users', 'companies', 'ldap', 'other'));

// Security check
if (! empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
{
    header("Location: ".DOL_URL_ROOT.'/');
    exit;
}

$action=GETPOST('action', 'alpha');
$mode=$dolibarr_main_authentication;
if (! $mode) $mode='http';

<<<<<<< HEAD
$username 		= GETPOST('username','alpha');
$passwordhash	= GETPOST('passwordhash','alpha');
$conf->entity 	= (GETPOST('entity','int') ? GETPOST('entity','int') : 1);
=======
$username = GETPOST('username', 'alpha');
$passwordhash = GETPOST('passwordhash', 'alpha');
$conf->entity = (GETPOST('entity', 'int') ? GETPOST('entity', 'int') : 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Instantiate hooks of thirdparty module only if not already define
$hookmanager->initHooks(array('passwordforgottenpage'));


<<<<<<< HEAD
if (GETPOST('dol_hide_leftmenu','alpha') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu','alpha') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen','alpha') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover','alpha') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile','alpha') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;
=======
if (GETPOST('dol_hide_leftmenu', 'alpha') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu', 'alpha') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen', 'alpha') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover', 'alpha') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile', 'alpha') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


/**
 * Actions
 */

// Validate new password
if ($action == 'validatenewpassword' && $username && $passwordhash)
{
    $edituser = new User($db);
<<<<<<< HEAD
    $result=$edituser->fetch('',$_GET["username"]);
    if ($result < 0)
    {
        $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$username).'</div>';
=======
    $result=$edituser->fetch('', $_GET["username"]);
    if ($result < 0)
    {
        $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists", $username).'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }
    else
    {
        if (dol_verifyHash($edituser->pass_temp, $passwordhash))
        {
<<<<<<< HEAD
            $newpassword=$edituser->setPassword($user,$edituser->pass_temp,0);
=======
            $newpassword=$edituser->setPassword($user, $edituser->pass_temp, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            dol_syslog("passwordforgotten.php new password for user->id=".$edituser->id." validated in database");
            header("Location: ".DOL_URL_ROOT.'/');
            exit;
        }
        else
        {
        	$langs->load("errors");
            $message = '<div class="error">'.$langs->trans("ErrorFailedToValidatePasswordReset").'</div>';
        }
    }
}
// Action modif mot de passe
if ($action == 'buildnewpassword' && $username)
{
    $sessionkey = 'dol_antispam_value';
    $ok=(array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));

    // Verify code
    if (! $ok)
    {
        $message = '<div class="error">'.$langs->trans("ErrorBadValueForCode").'</div>';
    }
    else
    {
        $edituser = new User($db);
<<<<<<< HEAD
        $result=$edituser->fetch('',$username,'',1);
        if ($result <= 0 && $edituser->error == 'USERNOTFOUND')
        {
            $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$username).'</div>';
=======
        $result=$edituser->fetch('', $username, '', 1);
        if ($result <= 0 && $edituser->error == 'USERNOTFOUND')
        {
            $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists", $username).'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $username='';
        }
        else
        {
            if (! $edituser->email)
            {
                $message = '<div class="error">'.$langs->trans("ErrorLoginHasNoEmail").'</div>';
            }
            else
            {
<<<<<<< HEAD
                $newpassword=$edituser->setPassword($user,'',1);
=======
                $newpassword=$edituser->setPassword($user, '', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                if ($newpassword < 0)
                {
                    // Failed
                    $message = '<div class="error">'.$langs->trans("ErrorFailedToChangePassword").'</div>';
                }
                else
                {
<<<<<<< HEAD
                    // Success
                    if ($edituser->send_password($user,$newpassword,1) > 0)
                    {

                        $message = '<div class="ok">'.$langs->trans("PasswordChangeRequestSent",$edituser->login,dolObfuscateEmail($edituser->email)).'</div>';
=======

                    // Success
                    if ($edituser->send_password($edituser, $newpassword, 1) > 0)
                    {

                        $message = '<div class="ok">'.$langs->trans("PasswordChangeRequestSent", $edituser->login, dolObfuscateEmail($edituser->email)).'</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
                        $username='';
                    }
                    else
                    {
                        $message.= '<div class="error">'.$edituser->error.'</div>';
                    }
                }
            }
        }
    }
}


/**
 * View
 */

$dol_url_root = DOL_URL_ROOT;

// Title
$title='Dolibarr '.DOL_VERSION;
if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;

// Select templates
if (file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/passwordforgotten.tpl.php"))
{
    $template_dir = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/";
}
else
{
    $template_dir = DOL_DOCUMENT_ROOT."/core/tpl/";
}

if (! $username) $focus_element = 'username';
else $focus_element = 'password';

// Send password button enabled ?
$disabled='disabled';
<<<<<<< HEAD
if (preg_match('/dolibarr/i',$mode)) $disabled='';
=======
if (preg_match('/dolibarr/i', $mode)) $disabled='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (! empty($conf->global->MAIN_SECURITY_ENABLE_SENDPASSWORD)) $disabled='';	 // To force button enabled

// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
$width=0;
$rowspan=2;
$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
{
<<<<<<< HEAD
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
}
elseif (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode($mysoc->logo);
=======
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
}
elseif (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$width=128;
}
elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png'))
{
	$urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png';
}
elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
{
	$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
}

// Security graphical code
if (function_exists("imagecreatefrompng") && ! $disabled)
{
	$captcha = 1;
<<<<<<< HEAD
	$captcha_refresh = img_picto($langs->trans("Refresh"),'refresh','id="captcha_refresh_img"');
}

// Execute hook getPasswordForgottenPageOptions (for table)
$parameters=array('entity' => GETPOST('entity','int'));
$hookmanager->executeHooks('getPasswordForgottenPageOptions',$parameters);    // Note that $action and $object may have been modified by some hooks
=======
	$captcha_refresh = img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"');
}

// Execute hook getPasswordForgottenPageOptions (for table)
$parameters=array('entity' => GETPOST('entity', 'int'));
$hookmanager->executeHooks('getPasswordForgottenPageOptions', $parameters);    // Note that $action and $object may have been modified by some hooks
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (is_array($hookmanager->resArray) && ! empty($hookmanager->resArray)) {
	$morelogincontent = $hookmanager->resArray; // (deprecated) For compatibility
} else {
	$morelogincontent = $hookmanager->resPrint;
}

// Execute hook getPasswordForgottenPageExtraOptions (eg for js)
<<<<<<< HEAD
$parameters=array('entity' => GETPOST('entity','int'));
$reshook = $hookmanager->executeHooks('getPasswordForgottenPageExtraOptions',$parameters);    // Note that $action and $object may have been modified by some hooks.
=======
$parameters=array('entity' => GETPOST('entity', 'int'));
$reshook = $hookmanager->executeHooks('getPasswordForgottenPageExtraOptions', $parameters);    // Note that $action and $object may have been modified by some hooks.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$moreloginextracontent = $hookmanager->resPrint;

include $template_dir.'passwordforgotten.tpl.php';	// To use native PHP
