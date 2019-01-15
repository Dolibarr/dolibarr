<?php
/* Copyright (C) 2004-2017	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/security_file.php
 *      \ingroup    core
 *      \brief      Security options setup
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array('users', 'admin', 'other'));

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

if ($action == 'updateform')
{
	$antivircommand = GETPOST('MAIN_ANTIVIRUS_COMMAND','none');			// Use GETPOST none because we must accept ". Example c:\Progra~1\ClamWin\bin\clamscan.exe
	$antivirparam = GETPOST('MAIN_ANTIVIRUS_PARAM','none');				// Use GETPOST none because we must accept ". Example --database="C:\Program Files (x86)\ClamWin\lib"
	$antivircommand = dol_string_nospecial($antivircommand, '', array("|", ";", "<", ">", "&"));	// Sanitize command
	$antivirparam = dol_string_nospecial($antivirparam, '', array("|", ";", "<", ">", "&"));		// Sanitize params

	$res3=dolibarr_set_const($db, 'MAIN_UPLOAD_DOC',GETPOST('MAIN_UPLOAD_DOC','alpha'),'chaine',0,'',$conf->entity);
	$res4=dolibarr_set_const($db, "MAIN_UMASK", GETPOST('MAIN_UMASK','alpha'),'chaine',0,'',$conf->entity);
	$res5=dolibarr_set_const($db, "MAIN_ANTIVIRUS_COMMAND", trim($antivircommand),'chaine',0,'',$conf->entity);
	$res6=dolibarr_set_const($db, "MAIN_ANTIVIRUS_PARAM", trim($antivirparam),'chaine',0,'',$conf->entity);
	if ($res3 && $res4 && $res5 && $res6) setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
}



// Delete file
else if ($action == 'delete')
{
	$langs->load("other");
	$file = $conf->admin->dir_temp . '/' . GETPOST('urlfile','alpha');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret=dol_delete_file($file);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile','alpha')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile','alpha')), null, 'errors');
	Header('Location: '.$_SERVER["PHP_SELF"]);
	exit;
}


/*
 * View
 */

$form = new Form($db);

$wikihelp='EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('',$langs->trans("Files"),$wikihelp);

print load_fiche_titre($langs->trans("SecuritySetup"),'','title_setup');

print $langs->trans("SecurityFilesDesc")."<br>\n";
print "<br>\n";


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="updateform">';

$head=security_prepare_head();

dol_fiche_head($head, 'file', $langs->trans("Security"), -1);


// Upload options
$var=false;

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Parameters").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td colspan="2">'.$langs->trans("MaxSizeForUploadedFiles").'.';
$max=@ini_get('upload_max_filesize');
if ($max) print ' '.$langs->trans("MustBeLowerThanPHPLimit",$max*1024,$langs->trans("Kb")).'.';
else print ' '.$langs->trans("NoMaxSizeByPHPLimit").'.';
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_UPLOAD_DOC" type="text" size="6" value="'.htmlentities($conf->global->MAIN_UPLOAD_DOC).'"> '.$langs->trans("Kb");
print '</td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans("UMask").'</td><td align="right">';
print $form->textwithpicto('',$langs->trans("UMaskExplanation"));
print '</td>';
print '<td class="nowrap">';
print '<input class="flat" name="MAIN_UMASK" type="text" size="6" value="'.htmlentities($conf->global->MAIN_UMASK).'">';
print '</td>';
print '</tr>';

// Use anti virus

print '<tr class="oddeven">';
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
print '<input type="text" name="MAIN_ANTIVIRUS_COMMAND" class="minwidth500imp" value="'.(! empty($conf->global->MAIN_ANTIVIRUS_COMMAND)?dol_escape_htmltag($conf->global->MAIN_ANTIVIRUS_COMMAND):'').'">';
print "</td>";
print '</tr>';

// Use anti virus

print '<tr class="oddeven">';
print '<td colspan="2">'.$langs->trans("AntiVirusParam").'<br>';
print $langs->trans("AntiVirusParamExample");
print '</td>';
print '<td>';
print '<input type="text" name="MAIN_ANTIVIRUS_PARAM" class="minwidth500imp" value="'.(! empty($conf->global->MAIN_ANTIVIRUS_PARAM)?dol_escape_htmltag($conf->global->MAIN_ANTIVIRUS_PARAM):'').'">';
print "</td>";
print '</tr>';

print '</table>';
print '</div>';

dol_fiche_end();

print '<div class="center"><input type="submit" class="button" name="button" value="'.$langs->trans("Modify").'"></div>';

print '</form>';


// Form to test upload
print '<br>';
$formfile=new FormFile($db);
$formfile->form_attach_new_file($_SERVER['PHP_SELF'], $langs->trans("FormToTestFileUploadForm"), 0, 0, 1, 50, '', '', 1, '', 0);

// List of document
$filearray=dol_dir_list($upload_dir, "files", 0, '', '', 'name', SORT_ASC, 1);
$formfile->list_of_documents($filearray, null, 'admin_temp', '');

// End of page
llxFooter();
$db->close();
