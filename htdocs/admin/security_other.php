<?php
/* Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/admin/security_other.php
 *      \ingroup    core
 *      \brief      Security options setup
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("users");
$langs->load("admin");
$langs->load("other");

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action','alpha');

$upload_dir=$conf->admin->dir_temp;


/*
 * Actions
 */

if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    dol_add_file_process($upload_dir, 0, 0, 'userfile');
}

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	$value=(GETPOST($code) ? GETPOST($code) : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

else if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

else if ($action == 'MAIN_SESSION_TIMEOUT')
{
    if (! dolibarr_set_const($db, "MAIN_SESSION_TIMEOUT", $_POST["MAIN_SESSION_TIMEOUT"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}
else if ($action == 'MAIN_UPLOAD_DOC')
{
    if (! dolibarr_set_const($db, 'MAIN_UPLOAD_DOC',$_POST["MAIN_UPLOAD_DOC"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}
else if ($action == 'MAIN_UMASK')
{
    if (! dolibarr_set_const($db, "MAIN_UMASK", $_POST["MAIN_UMASK"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}
else if ($action == 'MAIN_ANTIVIRUS_COMMAND')
{
    if (! dolibarr_set_const($db, "MAIN_ANTIVIRUS_COMMAND", $_POST["MAIN_ANTIVIRUS_COMMAND"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}
else if ($action == 'MAIN_ANTIVIRUS_PARAM')
{
    if (! dolibarr_set_const($db, "MAIN_ANTIVIRUS_PARAM", $_POST["MAIN_ANTIVIRUS_PARAM"],'chaine',0,'',$conf->entity)) dol_print_error($db);
    else $mesg=$langs->trans("RecordModifiedSuccessfully");
}

// Delete file
else if ($action == 'delete')
{
	$langs->load("other");
	$file = $conf->admin->dir_temp . '/' . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret=dol_delete_file($file);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	Header('Location: '.$_SERVER["PHP_SELF"]);
	exit;
}

/*
 * View
 */

$form = new Form($db);

llxHeader('',$langs->trans("Miscellaneous"));

print_fiche_titre($langs->trans("SecuritySetup"),'','setup');

print $langs->trans("MiscellaneousDesc")."<br>\n";
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
$sessiontimeout=ini_get("session.gc_maxlifetime");
if (empty($conf->global->MAIN_SESSION_TIMEOUT)) $conf->global->MAIN_SESSION_TIMEOUT=$sessiontimeout;
print '<form action="'.$_SERVER["PHP_SELF"].'?action=MAIN_SESSION_TIMEOUT" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("SessionTimeOut").'</td><td align="right">';
print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_SESSION_TIMEOUT" type="text" size="6" value="'.htmlentities($conf->global->MAIN_SESSION_TIMEOUT).'"> '.strtolower($langs->trans("Seconds"));
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
	if (! empty($conf->use_javascript_ajax))
	{
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA');
	}
	else
	{
		if (empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA">'.img_picto($langs->trans("Disabled"),'off').'</a>';
		}
		else
		{
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA">'.img_picto($langs->trans("Enabled"),'on').'</a>';
		}
	}
}
else
{
    $desc = $form->textwithpicto('',$langs->transnoentities("EnableGDLibraryDesc"),1,'warning');
    print $desc;
}
print '</td></tr>';

// Enable advanced perms
$var=!$var;
print "<tr ".$bc[$var].">";
print '<td colspan="3">'.$langs->trans("UseAdvancedPerms").'</td>';
print '<td align="right">';
if (! empty($conf->use_javascript_ajax))
{
	print ajax_constantonoff('MAIN_USE_ADVANCED_PERMS');
}
else
{
	if (empty($conf->global->MAIN_USE_ADVANCED_PERMS))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_USE_ADVANCED_PERMS">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_USE_ADVANCED_PERMS">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print "</td></tr>";

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
print '<td class="nowrap">';
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
print '<td class="nowrap">';
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
print '<input type="text" name="MAIN_ANTIVIRUS_COMMAND" size="72" value="'.(! empty($conf->global->MAIN_ANTIVIRUS_COMMAND)?dol_htmlentities($conf->global->MAIN_ANTIVIRUS_COMMAND):'').'">';
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
print '<input type="text" name="MAIN_ANTIVIRUS_PARAM" size="72" value="'.(! empty($conf->global->MAIN_ANTIVIRUS_PARAM)?dol_htmlentities($conf->global->MAIN_ANTIVIRUS_PARAM):'').'">';
print "</td>";
print '<td align="right">';
print '<input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'">';
print '</td>';
print '</tr>';
print '</form>';

print '</table>';

dol_fiche_end();

// Form to test upload
print '<br>';
$formfile=new FormFile($db);
$formfile->form_attach_new_file($_SERVER['PHP_SELF'], $langs->trans("FormToTestFileUploadForm"), 0, 0, 1);

// List of document
$filearray=dol_dir_list($upload_dir, "files", 0, '', '', 'name', SORT_ASC, 1);
$formfile->list_of_documents($filearray, '', 'admin_temp', '');


llxFooter();
$db->close();
?>
