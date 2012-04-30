<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *		\file 		htdocs/admin/tools/update.php
 *		\brief      Page to make a Dolibarr online upgrade
 */

require("../../main.inc.php");
include_once $dolibarr_main_document_root."/core/lib/files.lib.php";

$langs->load("admin");
$langs->load("other");

if (! $user->admin) accessforbidden();

if ($_GET["msg"]) $message='<div class="error">'.$_GET["msg"].'</div>';


$urldolibarr='http://www.dolibarr.org/downloads/';
$urldolibarrmodules='http://www.dolistore.com/';
$urldolibarrthemes='http://www.dolistore.com/';
$dolibarrroot=preg_replace('/([\\/]+)$/i','',DOL_DOCUMENT_ROOT);
$dolibarrroot=preg_replace('/([^\\/]+)$/i','',$dolibarrroot);


/*
 *	Actions
 */

if ($_POST["action"]=='install')
{
	$error=0;

	$original_file=basename($_FILES["fileinstall"]["name"]);
	$newfile=$conf->admin->dir_temp.'/'.$original_file.'/'.$original_file;

	if (! $original_file)
	{
		$mesg=$langs->trans("ErrorFieldRequired",$langs->transnoentities("File"));
		$error++;
	}
	else
	{
		if (! preg_match('/\.tgz/i',$original_file))
		{
			$mesg=$langs->trans("ErrorFileMustBeADolibarrPackage");
			$error++;
		}
	}

	if (! $error)
	{
		@dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$original_file);
		dol_mkdir($conf->admin->dir_temp.'/'.$original_file);

		$result=dol_move_uploaded_file($_FILES["fileinstall"]["tmp_name"],$newfile,1,0,$_FILES['fileinstall']['error']);
		if ($result > 0)
		{
			//dol_uncompress($newfile);
		}
	}
}


/*
 * View
 */

$wikihelp='EN:Installation_-_Upgrade|FR:Installation_-_Mise_à_jour|ES:Instalaci&omodulon_-_Actualizaci&omodulon';
llxHeader('',$langs->trans("Upgrade"),$wikihelp);

print_fiche_titre($langs->trans("Upgrade"),'','setup');

print $langs->trans("CurrentVersion").' : <b>'.DOL_VERSION.'</b><br>';
print $langs->trans("LastStableVersion").' : <b>'.$langs->trans("FeatureNotYetAvailable").'</b><br>';
print '<br>';

if ($mesg)
{
	print '<div class="error">'.$mesg.'</div><br>';
}

print $langs->trans("Upgrade").'<br>';
print '<hr>';
print $langs->trans("ThisIsProcessToFollow").'<br>';
print '<b>'.$langs->trans("StepNb",1).'</b>: ';
$fullurl='<a href="'.$urldolibarr.'" target="_blank">'.$urldolibarr.'</a>';
print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",2).'</b>: ';
print $langs->trans("UnpackPackageInDolibarrRoot",$dolibarrroot).'<br>';
print '<b>'.$langs->trans("StepNb",3).'</b>: ';
print $langs->trans("RemoveLock",$dolibarrroot.'install.lock').'<br>';
print '<b>'.$langs->trans("StepNb",4).'</b>: ';
$fullurl='<a href="'.DOL_URL_ROOT.'/install/" target="_blank">'.DOL_URL_ROOT.'/install/</a>';
print $langs->trans("CallUpdatePage",$fullurl).'<br>';
print '<b>'.$langs->trans("StepNb",5).'</b>: ';
print $langs->trans("RestoreLock",$dolibarrroot.'install.lock').'<br>';

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
	print '<form enctype="multipart/form-data" method="POST" class="noborder" action="'.$_SERVER["PHP_SELF"].'" name="forminstall">';
	print '<input type="hidden" name="action" value="install">';
	print $langs->trans("YouCanSubmitFile").' <input type="file" name="fileinstall"> ';
	print '<input type="submit" name="'.dol_escape_htmltag($langs->trans("Send")).'" class="button">';
	print '</form>';
}
print '<b>'.$langs->trans("StepNb",4).'</b>: ';
print $langs->trans("SetupIsReadyForUse").'<br>';

print '</form>';

llxFooter();
?>
