<?php
/* Copyright (C) 2007-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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

define("NOLOGIN",1);	// This means this output page does not require to be logged.

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
if (! empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/ldap.class.php';

$langs->load("errors");
$langs->load("users");
$langs->load("companies");
$langs->load("ldap");
$langs->load("other");

// Security check
if (! empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
{
    header("Location: ".DOL_URL_ROOT.'/');
    exit;
}

$action=GETPOST('action', 'alpha');
$mode=$dolibarr_main_authentication;
if (! $mode) $mode='http';

$username 		= GETPOST('username');
$passwordhash	= GETPOST('passwordhash');
$conf->entity 	= (GETPOST('entity') ? GETPOST('entity') : 1);

// Instantiate hooks of thirdparty module only if not already define
$hookmanager->initHooks(array('passwordforgottenpage'));


if (GETPOST('dol_hide_leftmenu') || ! empty($_SESSION['dol_hide_leftmenu']))               $conf->dol_hide_leftmenu=1;
if (GETPOST('dol_hide_topmenu') || ! empty($_SESSION['dol_hide_topmenu']))                 $conf->dol_hide_topmenu=1;
if (GETPOST('dol_optimize_smallscreen') || ! empty($_SESSION['dol_optimize_smallscreen'])) $conf->dol_optimize_smallscreen=1;
if (GETPOST('dol_no_mouse_hover') || ! empty($_SESSION['dol_no_mouse_hover']))             $conf->dol_no_mouse_hover=1;
if (GETPOST('dol_use_jmobile') || ! empty($_SESSION['dol_use_jmobile']))                   $conf->dol_use_jmobile=1;


/**
 * Actions
 */

// Validate new password
if ($action == 'validatenewpassword' && $username && $passwordhash)
{
    $edituser = new User($db);
    $result=$edituser->fetch('',$_GET["username"]);
    if ($result < 0)
    {
        $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$username).'</div>';
    }
    else
    {
        if (dol_hash($edituser->pass_temp) == $passwordhash)
        {
            $newpassword=$edituser->setPassword($user,$edituser->pass_temp,0);
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
    $ok=(array_key_exists($sessionkey, $_SESSION) === TRUE && (strtolower($_SESSION[$sessionkey]) == strtolower($_POST['code'])));

    // Verify code
    if (! $ok)
    {
        $message = '<div class="error">'.$langs->trans("ErrorBadValueForCode").'</div>';
    }
    else
    {
        $edituser = new User($db);
        $result=$edituser->fetch('',$username,'',1);
        if ($result <= 0 && $edituser->error == 'USERNOTFOUND')
        {
            $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$username).'</div>';
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
                $newpassword=$edituser->setPassword($user,'',1);
                if ($newpassword < 0)
                {
                    // Failed
                    $message = '<div class="error">'.$langs->trans("ErrorFailedToChangePassword").'</div>';
                }
                else
                {
                    // Success
                    if ($edituser->send_password($user,$newpassword,1) > 0)
                    {

                        $message = '<div class="ok">'.$langs->trans("PasswordChangeRequestSent",$edituser->login,dolObfuscateEmail($edituser->email)).'</div>';
                        //$message.=$newpassword;
                        $username='';
                    }
                    else
                    {
                        //$message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
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

$php_self = $_SERVER['PHP_SELF'];
$php_self.= $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';

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

// Note: $conf->css looks like '/theme/eldy/style.css.php'
$conf->css = "/theme/".(GETPOST('theme')?GETPOST('theme','alpha'):$conf->theme)."/style.css.php";
//$themepath=dol_buildpath((empty($conf->global->MAIN_FORCETHEMEDIR)?'':$conf->global->MAIN_FORCETHEMEDIR).$conf->css,1);
$themepath=dol_buildpath($conf->css,1);
if (! empty($conf->modules_parts['theme']))	// This slow down
{
	foreach($conf->modules_parts['theme'] as $reldir)
	{
		if (file_exists(dol_buildpath($reldir.$conf->css, 0)))
		{
			$themepath=dol_buildpath($reldir.$conf->css, 1);
			break;
		}
	}
}
$conf_css = $themepath."?lang=".$langs->defaultlang;

$jquerytheme = 'smoothness';
if (! empty($conf->global->MAIN_USE_JQUERY_THEME)) $jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;

if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
{
    $login_background = DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png';
}
else
{
    $login_background = DOL_URL_ROOT.'/theme/login_background.png';
}

if (! $username) $focus_element = 'username';
else $focus_element = 'password';

// Send password button enabled ?
$disabled='disabled';
if (preg_match('/dolibarr/i',$mode)) $disabled='';
if (! empty($conf->global->MAIN_SECURITY_ENABLE_SENDPASSWORD)) $disabled='';	 // To force button enabled

// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
$width=0;
$rowspan=2;
$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
}
elseif (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
{
	$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
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
	$captcha_refresh = img_picto($langs->trans("Refresh"),'refresh','id="captcha_refresh_img"');
}

// Execute hook getPasswordForgottenPageOptions
// Should be an array with differents options in $hookmanager->resArray
$parameters=array('entity' => GETPOST('entity','int'));
$hookmanager->executeHooks('getPasswordForgottenPageOptions',$parameters);    // Note that $action and $object may have been modified by some hooks

include $template_dir.'passwordforgotten.tpl.php';	// To use native PHP

