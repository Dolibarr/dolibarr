<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
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
 *		\file 		htdocs/admin/tools/update.php
 *		\brief      Page to make a Dolibarr online upgrade
 */

require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

$langs->load("admin");
$langs->load("other");

if (! $user->admin) accessforbidden();

if (GETPOST('msg','alpha')) {
	setEventMessage(GETPOST('msg','alpha'), 'errors');
}


$urldolibarr='http://www.dolibarr.org/downloads/';
$urldolibarrmodules='http://www.dolistore.com/';
$urldolibarrthemes='http://www.dolistore.com/';
$dolibarrroot=preg_replace('/([\\/]+)$/i','',DOL_DOCUMENT_ROOT);
$dolibarrroot=preg_replace('/([^\\/]+)$/i','',$dolibarrroot);
$dolibarrdataroot=preg_replace('/([\\/]+)$/i','',DOL_DATA_ROOT);

/*
 *	Actions
 */

if (GETPOST('action','alpha')=='install')
{
	$error=0;

	$original_file=basename($_FILES["fileinstall"]["name"]);
	$newfile=$conf->admin->dir_temp.'/'.$original_file.'/'.$original_file;

	if (! $original_file)
	{
		$langs->load("Error");
		setEventMessage($langs->trans("ErrorFileRequired"), 'warnings');
		$error++;
	}
	else
	{
		if (! preg_match('/\.zip/i',$original_file))
		{
			$langs->load("errors");
			setEventMessage($langs->trans("ErrorFileMustBeADolibarrPackage",$original_file), 'errors');
			$error++;
		}
	}

	if (! $error)
	{
		@dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$original_file);
		dol_mkdir($conf->admin->dir_temp.'/'.$original_file);

		$result=dol_move_uploaded_file($_FILES['fileinstall']['tmp_name'],$newfile,1,0,$_FILES['fileinstall']['error']);
		if ($result > 0)
		{
			$documentrootalt=DOL_DOCUMENT_ROOT.'/extensions';
			$result=dol_uncompress($newfile,$documentrootalt);
			if (! empty($result['error']))
			{
				$langs->load("errors");
				setEventMessage($langs->trans($result['error'],$original_file), 'errors');
			}
			else
			{
				setEventMessage($langs->trans("SetupIsReadyForUse"));
			}
		}
	}
}

/*
 * View
 */

$dirins=DOL_DOCUMENT_ROOT.'/extensions';
$dirins_ok=(is_dir($dirins));

$wikihelp='EN:Installation_-_Upgrade|FR:Installation_-_Mise_à_jour|ES:Instalación_-_Actualización';
llxHeader('',$langs->trans("Upgrade"),$wikihelp);

print_fiche_titre($langs->trans("Upgrade"),'','setup');

print $langs->trans("CurrentVersion").' : <b>'.DOL_VERSION.'</b><br>';
print $langs->trans("LastStableVersion").' : <b>'.$langs->trans("FeatureNotYetAvailable").'</b><br>';
print '<br>';

print $langs->trans("Upgrade").'<br>';
print '<hr>';
print $langs->trans("ThisIsProcessToFollow").'<br>';
print '<b>'.$langs->trans("StepNb",1).'</b>: ';
$fullurl='<a href="'.$urldolibarr.'" target="_blank">'.$urldolibarr.'</a>';
print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",2).'</b>: ';
print $langs->trans("UnpackPackageInDolibarrRoot",$dolibarrroot).'<br>';
print '<b>'.$langs->trans("StepNb",3).'</b>: ';
print $langs->trans("RemoveLock",$dolibarrdataroot.'/install.lock').'<br>';
print '<b>'.$langs->trans("StepNb",4).'</b>: ';
$fullurl='<a href="'.DOL_URL_ROOT.'/install/" target="_blank">'.DOL_URL_ROOT.'/install/</a>';
print $langs->trans("CallUpdatePage",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",5).'</b>: ';
print $langs->trans("RestoreLock",$dolibarrdataroot.'/install.lock').'<br>';

print '<br>';
print '<br>';

$fullurl='<a href="'.$urldolibarrmodules.'" target="_blank">'.$urldolibarrmodules.'</a>';
print $langs->trans("AddExtensionThemeModuleOrOther").'<br>';
print '<hr>';
print $langs->trans("ThisIsProcessToFollow").'<br>';
print '<b>'.$langs->trans("StepNb",1).'</b>: ';
print $langs->trans("FindPackageFromWebSite",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",2).'</b>: ';
print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",3).'</b>: ';
print $langs->trans("UnpackPackageInDolibarrRoot",$dolibarrroot).'<br>';
if (! empty($conf->global->MAIN_ONLINE_INSTALL_MODULE))
{
	if ($dirins_ok)
	{
		print '<form enctype="multipart/form-data" method="POST" class="noborder" action="'.$_SERVER["PHP_SELF"].'" name="forminstall">';
		print '<input type="hidden" name="action" value="install">';
		print $langs->trans("YouCanSubmitFile").' <input type="file" name="fileinstall"> ';
		print '<input type="submit" name="'.dol_escape_htmltag($langs->trans("Send")).'" class="button">';
		print '</form>';
	}
	else
	{
		$message=info_admin($langs->trans("NotExistsDirect",$dirins).$langs->trans("InfDirAlt").$langs->trans("InfDirExample"));
		setEventMessage($message, 'warnings');
	}
}
else
{
	print '<b>'.$langs->trans("StepNb",4).'</b>: ';
	print $langs->trans("SetupIsReadyForUse").'<br>';
}
print '</form>';

if (! empty($result['return']))
{
	print '<br>';

	foreach($result['return'] as $value)
	{
		echo $value.'<br>';
	}
}

llxFooter();

$db->close();
