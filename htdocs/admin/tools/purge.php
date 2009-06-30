<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *		\file 		htdocs/admin/tools/purge.php
 *		\brief      Page to purge files (temporary or not)
 *		\version    $Id$
 */

require("./pre.inc.php");
include_once(DOL_DOCUMENT_ROOT."/lib/databases/".$conf->db->type.".lib.php");
include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

$langs->load("admin");

if (! $user->admin)
accessforbidden();

if ($_GET["msg"]) $message='<div class="error">'.$_GET["msg"].'</div>';

// Define filelog to discard it from purge
$filelog='';
if ($conf->syslog->enabled)
{
	$filelog=SYSLOG_FILE;
	$filelog=eregi_replace('DOL_DATA_ROOT',DOL_DATA_ROOT,$filelog);
}



/*
 *	Actions
 */
if ($_POST["action"]=='purge')
{
	$filesarray=array();

	if ($_POST["choice"]=='tempfiles')
	{
		// Delete temporary files
		if ($dolibarr_main_data_root)
		{
			$filesarray=dol_dir_list($dolibarr_main_data_root,"directories",1,'temp');
		}
	}

	if ($_POST["choice"]=='allfiles')
	{
		// Delete all files
		if ($dolibarr_main_data_root)
		{
			$filesarray=dol_dir_list($dolibarr_main_data_root,"all",0);
		}
	}

	if ($_POST["choice"]=='logfile')
	{
		$filesarray[]=array('fullname'=>$filelog,'type'=>'file');
	}

	$count=0;
	if (sizeof($filesarray))
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
				if ($filesarray[$key]['fullname'] != $filelog || $_POST["choice"]=='logfile')
				{
					$count+=dol_delete_file($filesarray[$key]['fullname']);
				}
			}
		}

		// Update cachenbofdoc
		if ($conf->ecm->enabled && $_POST["choice"]=='allfiles')
		{
			require_once(DOL_DOCUMENT_ROOT."/ecm/ecmdirectory.class.php");
			$ecmdirstatic = new ECMDirectory($db);
			$result = $ecmdirstatic->refreshcachenboffile(1);
		}
	}

	if ($count) $message=$langs->trans("PurgeNDirectoriesDeleted",$count);
	else $message=$langs->trans("PurgeNothingToDelete");
	$message='<div class="ok">'.$message.'</div>';
}


/*
 * View
 */

llxHeader();

$html=new Form($db);

print_fiche_titre($langs->trans("Purge"),'','setup');
print '<br>';

print $langs->trans("PurgeAreaDesc",$dolibarr_main_data_root).'<br>';
print '<br>';

if ($message)
{
	print $message.'<br>';
	print "\n";
}

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<input type="hidden" name="action" value="purge">';

print '<table class="border" width="100%">';

print '<tr><td>';

print '<input type="radio" name="choice" value="tempfiles"';
print (! $_POST["choice"] || $_POST["choice"]=='tempfiles') ? ' checked="true"' : '';
print '> '.$langs->trans("PurgeDeleteTemporaryFiles").'<br>';

print '<input type="radio" name="choice" value="allfiles"';
print ($_POST["choice"] && $_POST["choice"]=='allfiles') ? ' checked="true"' : '';
print '> '.$langs->trans("PurgeDeleteAllFilesInDocumentsDir",$dolibarr_main_data_root).'<br>';

if ($conf->syslog->enabled)
{
	print '<input type="radio" name="choice" value="logfile"';
	print ($_POST["choice"] && $_POST["choice"]=='logfile') ? ' checked="true"' : '';
	print '> '.$langs->trans("PurgeDeleteLogFile",$filelog).'<br>';
}

print '</td></tr></table>';

print '<br>';
print '<center><input class="button" type="submit" value="'.$langs->trans("PurgeRunNow").'"></center>';


print '</form>';

llxFooter('$Date$ - $Revision$');
?>