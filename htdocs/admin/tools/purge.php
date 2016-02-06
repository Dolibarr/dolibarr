<?php
/* Copyright (C) 2006-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 *		\file 		htdocs/admin/tools/purge.php
 *		\brief      Page to purge files (temporary or not)
 */

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$choice=GETPOST('choice');


// Define filelog to discard it from purge
$filelog='';
if (! empty($conf->syslog->enabled))
{
	$filelog=SYSLOG_FILE;
	$filelog=preg_replace('/DOL_DATA_ROOT/i',DOL_DATA_ROOT,$filelog);
}


/*
 *	Actions
 */
if ($action=='purge' && ! preg_match('/^confirm/i',$choice) && ($choice != 'allfiles' || $confirm == 'yes') )
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
	$utils = new Utils($db);
	$result = $utils->purgeFiles($choice);

	$mesg = $utils->output;
	setEventMessages($mesg, null, 'mesgs');
}


/*
 * View
 */

llxHeader();

$form=new Form($db);

print load_fiche_titre($langs->trans("Purge"),'','title_setup');

print $langs->trans("PurgeAreaDesc",$dolibarr_main_data_root).'<br>';
print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'" />';
print '<input type="hidden" name="action" value="purge" />';

print '<table class="border" width="100%">';

print '<tr class="border"><td style="padding: 4px">';

if (! empty($conf->syslog->enabled))
{
	print '<input type="radio" name="choice" value="logfile"';
	print ($choice && $choice=='logfile') ? ' checked' : '';
	print '> '.$langs->trans("PurgeDeleteLogFile",$filelog).'<br><br>';
}

print '<input type="radio" name="choice" value="tempfiles"';
print (! $choice || $choice=='tempfiles' || $choice=='allfiles') ? ' checked' : '';
print '> '.$langs->trans("PurgeDeleteTemporaryFiles").'<br><br>';

print '<input type="radio" name="choice" value="confirm_allfiles"';
print ($choice && $choice=='confirm_allfiles') ? ' checked' : '';
print '> '.$langs->trans("PurgeDeleteAllFilesInDocumentsDir",$dolibarr_main_data_root).'<br>';

print '</td></tr></table>';

//if ($choice != 'confirm_allfiles')
//{
	print '<br>';
	print '<div class="center"><input class="button" type="submit" value="'.$langs->trans("PurgeRunNow").'"></div>';
//}

print '</form>';

if (preg_match('/^confirm/i',$choice))
{
	print '<br>';
	$formquestion=array();
	print $form->formconfirm($_SERVER["PHP_SELF"].'?choice=allfiles', $langs->trans('Purge'), $langs->trans('ConfirmPurge').img_warning().' ', 'purge', $formquestion, 'no', 2);
}


llxFooter();

$db->close();
