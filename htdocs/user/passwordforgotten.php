<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/user/passwordforgotten.php
 *       \brief      Page to ask a new password
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("other");
$langs->load("users");
$langs->load("companies");
$langs->load("ldap");

// Security check
if ($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK)
{
    header("Location: ".DOL_URL_ROOT.'/');
    exit;
}

$action=GETPOST('action');
$mode=$dolibarr_main_authentication;
if (! $mode) $mode='http';

$username 		= GETPOST('username');
$passwordmd5	= GETPOST('passwordmd5');
$conf->entity 	= (GETPOST('entity') ? GETPOST('entity') : 1);


/**
 * Actions
 */

// Validate new password
if ($action == 'validatenewpassword' && $username && $passwordmd5)
{
    $edituser = new User($db);
    $result=$edituser->fetch('',$_GET["username"]);
    if ($result < 0)
    {
        $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$username).'</div>';
    }
    else
    {
        if (dol_hash($edituser->pass_temp) == $passwordmd5)
        {
            $newpassword=$edituser->setPassword($user,$edituser->pass_temp,0);
            dol_syslog("passwordforgotten.php new password for user->id=".$edituser->id." validated in database");
            //session_start();
            //$_SESSION["loginmesg"]=$langs->trans("PasswordChanged");
            header("Location: ".DOL_URL_ROOT.'/');
            exit;
        }
        else
        {
            $message = '<div class="error">'.$langs->trans("ErrorFailedToValidatePassword").'</div>';
        }
    }
}
// Action modif mot de passe
if ($action == 'buildnewpassword' && $username)
{
    require_once DOL_DOCUMENT_ROOT.'/includes/artichow/Artichow.cfg.php';
    require_once ARTICHOW."/AntiSpam.class.php";

    // We create anti-spam object
    $object = new AntiSpam();

    // Verify code
    if (! $object->check('dol_antispam_value',$_POST['code'],true))
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
                        $message = '<div class="ok">'.$langs->trans("PasswordChangeRequestSent",$edituser->login,$edituser->email).'</div>';
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
if (preg_match('/^smartphone/',$conf->smart_menu) && isset($conf->browser->phone))
{
    $template_dir = DOL_DOCUMENT_ROOT.'/theme/phones/smartphone/tpl/';
}
else
{
    if (file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/passwordforgotten.tpl.php"))
    {
        $template_dir = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/";
    }
    else
    {
        $template_dir = DOL_DOCUMENT_ROOT."/core/tpl/";
    }
}

$conf->css  = "/theme/".$conf->theme."/style.css.php?lang=".$langs->defaultlang;
$conf_css = DOL_URL_ROOT.$conf->css;

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
if ($conf->global->MAIN_SECURITY_ENABLE_SENDPASSWORD) $disabled='';	 // To force button enabled

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
}elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/dolibarr_logo.png';
}elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
    $urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
}

// Entity combobox
$select_entity='';
if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)  && empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX) && ! $disabled)
{
    $rowspan++;
    $lastuser='';
    $lastentity = GETPOST('entity');

    if (! empty($conf->global->MAIN_MULTICOMPANY_COOKIE))
    {
        $prefix=dol_getprefix();
        $entityCookieName = 'DOLENTITYID_'.$prefix;
        if (isset($_COOKIE[$entityCookieName]))
        {
            include_once(DOL_DOCUMENT_ROOT . "/core/class/cookie.class.php");

            $cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

            $entityCookie = new DolCookie($cryptkey);
            $cookieValue = $entityCookie->_getCookie($entityCookieName);
            list($lastuser, $lastentity) = explode('|', $cookieValue);
        }
    }

    $res=dol_include_once('/multicompany/class/actions_multicompany.class.php');
    if ($res)
    {
        $mc = new ActionsMulticompany($db);
        $select_entity = $mc->select_entities($lastentity,'tabindex="2"');
    }
}

// Security graphical code
if (function_exists("imagecreatefrompng") && ! $disabled)
{
    $captcha = 1;
    $captcha_refresh = img_picto($langs->trans("Refresh"),'refresh');
}

include($template_dir.'passwordforgotten.tpl.php');	// To use native PHP

?>