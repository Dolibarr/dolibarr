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
	$filesarray=array();
	
	if ($choice=='tempfiles')
	{
		// Delete temporary files
		if ($dolibarr_main_data_root)
		{
			$filesarray=dol_dir_list($dolibarr_main_data_root,"directories",1,'^temp$');
		}
	}

	if ($choice=='allfiles')
	{
		// Delete all files
		if ($dolibarr_main_data_root)
		{
			$filesarray=dol_dir_list($dolibarr_main_data_root,"all",0,'','install\.lock$');
		}
	}

	if ($choice=='logfile')
	{
		$filesarray[]=array('fullname'=>$filelog,'type'=>'file');
	}

	$count=0;
	if (count($filesarray))
	{
		foreach($filesarray as $key => $value)
		{
			//print "x ".$filesarray[$key]['fullname']."<br>\n";
			if ($filesarray[$key]['type'] == 'dir')
			{
				$count+=dol_delete_dir_recursive($filesarray[$key]['fullname']);
			}
			elseif ($filesarray[$key]['type'] == 'file')
			{
				// If (file that is not logfile) or (if logfile with option logfile)
				if ($filesarray[$key]['fullname'] != $filelog || $choice=='logfile')
				{
					$count+=(dol_delete_file($filesarray[$key]['fullname'])?1:0);
				}
			}
		}

		// Update cachenbofdoc
		if (! empty($conf->ecm->enabled) && $choice=='allfiles')
		{
			require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
			$ecmdirstatic = new EcmDirectory($db);
			$result = $ecmdirstatic->refreshcachenboffile(1);
		}
	}

	if ($count) $mesg=$langs->trans("PurgeNDirectoriesDeleted", $count);
	else $mesg=$langs->trans("PurgeNothingToDelete");
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
	print $form->formconfirm($_SERVER["PHP_SELF"].'?choice=allfiles', $langs->trans('Purge'), $langs->trans('ConfirmPurge').' '.img_warning(), 'purge', $formquestion, 'no', 2);
}


llxFooter();

$db->close();
