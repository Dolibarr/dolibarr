<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
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
 *	    \file       htdocs/admin/security_other.php
 *      \ingroup    core
 *      \brief      Security options setup
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load("users");
$langs->load("admin");
$langs->load("other");

if (!$user->admin) accessforbidden();

$upload_dir=$conf->admin->dir_temp;


/*
 * Actions
 */

if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    $result=dol_mkdir($upload_dir);	// Create dir if not exists
    if ($result >= 0)
    {
        $resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],1,0,$_FILES['userfile']['error']);

        if (is_numeric($resupload) && $resupload > 0)
        {
            $mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';

            include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
            $formmail = new FormMail($db);
            $formmail->add_attached_files($upload_dir . "/" . $_FILES['addedfile']['name'],$_FILES['addedfile']['name'],$_FILES['addedfile']['type']);
        }
        else
        {
            $langs->load("errors");
            if ($resupload < 0)	// Unknown error
            {
                $mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
            }
            else if (preg_match('/ErrorFileIsInfectedWithAVirus.(.*)/',$resupload,$reg))	// Files infected by a virus
            {
                $mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus");
                $mesg.= '<br>'.$langs->trans("Information").': '.$langs->trans($reg[1]);
                $mesg.= '</div>';
            }
            else	// Known error
            {
                $mesg = '<div class="error">'.$langs->trans($resupload).'</div>';
            }
        }
    }
}

if ($_GET["action"] == 'activate_captcha')
{
    dolibarr_set_const($db, "MAIN_SECURITY_ENABLECAPTCHA", '1','chaine',0,'',$conf->entity);
    Header("Location: security_other.php");
    exit;
}
else if ($_GET["action"] == 'disable_captcha')
{
    dolibarr_del_const($db, "MAIN_SECURITY_ENABLECAPTCHA",$conf->entity);
    Header("Location: security_other.php");
    exit;
}

if ($_GET["action"] == 'activate_advancedperms')
{
    dolibarr_set_const($db, "MAIN_USE_ADVANCED_PERMS", '1','chaine',0,'',$conf->entity);
    Header("Location: security_other.php");
    exit;
}
else if ($_GET["action"] == 'disable_advancedperms')
{
    dolibarr_del_const($db, "MAIN_USE_ADVANCED_PERMS",$conf->entity);
    Header("Location: security_other.php");
    exit;
}

if ($_GET["action"] == 'MAIN_SESSION_TIMEOUT')
{
    if (! dolibarr_set_const($db, "MAIN_SESSION_TIMEOUT", $_POST["MAIN_SESSION_TIMEOUT"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}

if ($_GET["action"] == 'MAIN_UPLOAD_DOC')
{
    if (! dolibarr_set_const($db, 'MAIN_UPLOAD_DOC',$_POST["MAIN_UPLOAD_DOC"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}

if ($_GET["action"] == 'MAIN_UMASK')
{
    if (! dolibarr_set_const($db, "MAIN_UMASK", $_POST["MAIN_UMASK"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}

if ($_GET["action"] == 'MAIN_ANTIVIRUS_COMMAND')
{
    if (! dolibarr_set_const($db, "MAIN_ANTIVIRUS_COMMAND", $_POST["MAIN_ANTIVIRUS_COMMAND"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}

if ($_GET["action"] == 'MAIN_ANTIVIRUS_PARAM')
{
    if (! dolibarr_set_const($db, "MAIN_ANTIVIRUS_PARAM", $_POST["MAIN_ANTIVIRUS_PARAM"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}


/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("Miscellanous"));

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("MiscellanousDesc")."<br>\n";
print "<br>\n";

$head=security_prepare_head();

dol_fiche_head($head, 'misc', $langs->trans("Security"));


// Timeout
$var=true;

print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td width="100">&nbsp;</td>';
print "</tr>\n";

$var=!$var;
if (empty($conf->global->MAIN_SESSION_TIMEOUT)) $conf->global->MAIN_SESSION_TIMEOUT=ini_get("session.gc_maxlifetime");
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_SESSION_TIMEOUT" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SessionTimeOut").'</td><td align="right">';
print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_SESSION_TIMEOUT" type="text" size="6" value="'.htmlentities($conf->global->MAIN_SESSION_TIMEOUT).'"> '.$langs->trans("seconds");
print '</td>';
print '<td align="right">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr></form>';

print '</table>';

print '<br>';


// Other Options
$var=true;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td align="right" width="100">'.$langs->trans("Status").'</td>';
print '</tr>';

// Enable Captcha code
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("UseCaptchaCode").'</td>';
print '<td align="right">';
if (function_exists("imagecreatefrompng"))
{
    if ($conf->global->MAIN_SECURITY_ENABLECAPTCHA == 0)
    {
        print '<a href="security_other.php?action=activate_captcha">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
    }
    if($conf->global->MAIN_SECURITY_ENABLECAPTCHA == 1)
    {
        print '<a href="security_other.php?action=disable_captcha">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
    }
}
else
{
    $form = new Form($db);
    $desc = $form->textwithpicto('',$langs->transnoentities("EnableGDLibraryDesc"),1,'warning');
    print $desc;
}
print "</td>";

print "</td>";
print '</tr>';

// Enable advanced perms
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("UseAdvancedPerms").'</td>';
print '<td align="right">';
if ($conf->global->MAIN_USE_ADVANCED_PERMS == 0)
{
    print '<a href="security_other.php?action=activate_advancedperms">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
if($conf->global->MAIN_USE_ADVANCED_PERMS == 1)
{
    print '<a href="security_other.php?action=disable_advancedperms">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print "</td>";

print "</td>";
print '</tr>';

print '</table>';


print '<br>';


// Upload options
$var=false;

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '<td width="100">&nbsp;</td>';
print '</tr>';

print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_UPLOAD_DOC" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'>';
print '<td colspan="2">'.$langs->trans("MaxSizeForUploadedFiles").'.';
$max=@ini_get('upload_max_filesize');
if ($max) print ' '.$langs->trans("MustBeLowerThanPHPLimit",$max*1024,$langs->trans("Kb")).'.';
else print ' '.$langs->trans("NoMaxSizeByPHPLimit").'.';
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_UPLOAD_DOC" type="text" size="6" value="'.htmlentities($conf->global->MAIN_UPLOAD_DOC).'"> '.$langs->trans("Kb");
print '</td>';
print '<td align="right">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr></form>';

$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_UMASK" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UMask").'</td><td align="right">';
print $form->textwithpicto('',$langs->trans("UMaskExplanation"));
print '</td>';
print '<td nowrap="nowrap">';
print '<input class="flat" name="MAIN_UMASK" type="text" size="6" value="'.htmlentities($conf->global->MAIN_UMASK).'">';
print '</td>';
print '<td align="right">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr></form>';

// Use anti virus
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_ANTIVIRUS_COMMAND" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<tr ".$bc[$var].">";
print '<td colspan="2">'.$langs->trans("AntiVirusCommand").'<br>';
print $langs->trans("AntiVirusCommandExample");
// Check command in inside safe_mode
print '</td>';
print '<td>';
if (ini_get('safe_mode') && ! empty($conf->global->MAIN_ANTIVIRUS_COMMAND))
{
    $langs->load("errors");
    $basedir=preg_replace('/"/','',dirname($conf->global->MAIN_ANTIVIRUS_COMMAND));
    $listdir=explode(';',ini_get('safe_mode_exec_dir'));
    if (! in_array($basedir,$listdir))
    {
        print img_warning($langs->trans('WarningSafeModeOnCheckExecDir'));
        dol_syslog("safe_mode is on, basedir is ".$basedir.", safe_mode_exec_dir is ".ini_get('safe_mode_exec_dir'), LOG_WARNING);
    }
}
print '<input type="text" name="MAIN_ANTIVIRUS_COMMAND" size="72" value="'.htmlentities($conf->global->MAIN_ANTIVIRUS_COMMAND).'">';
print "</td>";
print '<td align="right">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';

// Use anti virus
$var=!$var;
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_ANTIVIRUS_PARAM" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<tr ".$bc[$var].">";
print '<td colspan="2">'.$langs->trans("AntiVirusParam").'<br>';
print $langs->trans("AntiVirusParamExample");
print '</td>';
print '<td>';
print '<input type="text" name="MAIN_ANTIVIRUS_PARAM" size="72" value="'.htmlentities($conf->global->MAIN_ANTIVIRUS_PARAM).'">';
print "</td>";
print '<td align="right">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';

print '</table>';

print '</div>';


// Form to test upload
dol_htmloutput_mesg($mesg);

// Affiche formulaire upload
print '<br>';
$formfile=new FormFile($db);
$formfile->form_attach_new_file(DOL_URL_ROOT.'/admin/security_other.php',$langs->trans("FormToTestFileUploadForm"),0,0,1);


llxFooter();

$db->close();
?>
