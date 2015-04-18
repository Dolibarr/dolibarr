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
include_once DOL_DOCUMENT_ROOT . '/core/lib/geturl.lib.php';

$langs->load("admin");
$langs->load("other");

$action=GETPOST('action','alpha');

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

$dirins=DOL_DOCUMENT_ROOT.'/custom';


/*
 *	Actions
 */

if ($action=='install')
{
	$error=0;

	// $original_file should match format module_modulename-x.y[.z].zip
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
		if ($original_file)
		{
			@dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$original_file);
			dol_mkdir($conf->admin->dir_temp.'/'.$original_file);
		}

		$tmpdir=preg_replace('/\.zip$/','',$original_file).'.dir';
		if ($tmpdir)
		{
			@dol_delete_dir_recursive($conf->admin->dir_temp.'/'.$tmpdir);
			dol_mkdir($conf->admin->dir_temp.'/'.$tmpdir);
		}

		$result=dol_move_uploaded_file($_FILES['fileinstall']['tmp_name'],$newfile,1,0,$_FILES['fileinstall']['error']);
		if ($result > 0)
		{
			$result=dol_uncompress($newfile,$conf->admin->dir_temp.'/'.$tmpdir);

			if (! empty($result['error']))
			{
				$langs->load("errors");
				setEventMessage($langs->trans($result['error'],$original_file), 'errors');
				$error++;
			}
			else
			{
				// Now we move the dir of the module
				$modulename=preg_replace('/module_/', '', $original_file);
				$modulename=preg_replace('/\-[\d]+\.[\d]+.*$/', '', $modulename);
				// Search dir $modulename
				$modulenamedir=$conf->admin->dir_temp.'/'.$tmpdir.'/'.$modulename;
				//var_dump($modulenamedir);
				if (! dol_is_dir($modulenamedir))
				{
					$modulenamedir=$conf->admin->dir_temp.'/'.$tmpdir.'/htdocs/'.$modulename;
					//var_dump($modulenamedir);
					if (! dol_is_dir($modulenamedir))
					{
						setEventMessage($langs->trans("ErrorModuleFileSeemsToHaveAWrongFormat"), 'errors');
						$error++;
					}
				}

				if (! $error)
				{
					//var_dump($dirins);
					@dol_delete_dir_recursive($dirins.'/'.$modulename);
					$result=dolCopyDir($modulenamedir, $dirins.'/'.$modulename, '0444', 1);
					if ($result <= 0)
					{
						setEventMessage($langs->trans("ErrorFailedToCopy"), 'errors');
						$error++;
					}
				}
			}
		}
		else
		{
			$error++;
		}
	}

	if (! $error)
	{
		setEventMessage($langs->trans("SetupIsReadyForUse"));
	}
}


/*
 * View
 */



// Set dir where external modules are installed
if (! dol_is_dir($dirins))
{
	dol_mkdir($dirins);
}
$dirins_ok=(dol_is_dir($dirins));

$wikihelp='EN:Installation_-_Upgrade|FR:Installation_-_Mise_à_jour|ES:Instalación_-_Actualización';
llxHeader('',$langs->trans("Upgrade"),$wikihelp);

print_fiche_titre($langs->trans("Upgrade"),'','title_setup');

print $langs->trans("CurrentVersion").' : <b>'.DOL_VERSION.'</b><br>';

$result = getURLContent('http://sourceforge.net/projects/dolibarr/rss');
//var_dump($result['content']);
$sfurl = simplexml_load_string($result['content']);
if ($sfurl)
{
    $title=$sfurl->channel[0]->item[0]->title;

	function word_limiter($text, $limit = 30, $chars = '0123456789.')
	{
	    if (strlen( $text ) > $limit)
	    {
	        $words = str_word_count($text, 2, $chars);
	        $words = array_reverse($words, TRUE);
	        foreach($words as $length => $word) {
	            if ($length + strlen( $word ) >= $limit)
	            {
	                array_shift($words);
	            } else {
	                break;
	            }
	        }
	        $words = array_reverse($words);
	        $text = implode(" ", $words) . '';
	    }
	    return $text;
	}

	$str = $title;
	print $langs->trans("LastStableVersion").' : <b>'. word_limiter( $str ).'</b><br>';
}
else
{
    print $langs->trans("LastStableVersion").' : <b>' .$langs->trans("UpdateServerOffline").'</b><br>';
}
print '<br>';


// Upgrade
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


// Install external module

$allowonlineinstall=true;
$allowfromweb=1;
if (dol_is_file($dolibarrdataroot.'/installmodules.lock')) $allowonlineinstall=false;

$fullurl='<a href="'.$urldolibarrmodules.'" target="_blank">'.$urldolibarrmodules.'</a>';
$message='';
if (! empty($allowonlineinstall))
{
	if (! in_array('/custom',explode(',',$dolibarr_main_url_root_alt)))
	{
		$message=info_admin($langs->trans("ConfFileMuseContainCustom", DOL_DOCUMENT_ROOT.'/custom', DOL_DOCUMENT_ROOT));
		$allowfromweb=-1;
	}
	else
	{
		if ($dirins_ok)
		{
			if (! is_writable(dol_osencode($dirins)))
			{
				$langs->load("errors");
				$message=info_admin($langs->trans("ErrorFailedToWriteInDir",$dirins));
				$allowfromweb=0;
			}
		}
		else
		{

			$message=info_admin($langs->trans("NotExistsDirect",$dirins).$langs->trans("InfDirAlt").$langs->trans("InfDirExample"));
			$allowfromweb=0;
		}
	}
}
else
{
	$message=info_admin($langs->trans("InstallModuleFromWebHasBeenDisabledByFile",$dolibarrdataroot.'/installmodules.lock'));
	$allowfromweb=0;
}





print $langs->trans("AddExtensionThemeModuleOrOther").'<br>';
print '<hr>';

if ($allowfromweb < 1)
{
	print $langs->trans("SomethingMakeInstallFromWebNotPossible");
	print $message;
	//print $langs->trans("SomethingMakeInstallFromWebNotPossible2");
	print '<br>';
}


if ($allowfromweb >= 0)
{
	if ($allowfromweb == 1) print $langs->trans("ThisIsProcessToFollow").'<br>';
	else print $langs->trans("ThisIsAlternativeProcessToFollow").'<br>';
	print '<b>'.$langs->trans("StepNb",1).'</b>: ';
	print $langs->trans("FindPackageFromWebSite",$fullurl).'<br>';
	print '<b>'.$langs->trans("StepNb",2).'</b>: ';
	print $langs->trans("DownloadPackageFromWebSite",$fullurl).'<br>';
	print '<b>'.$langs->trans("StepNb",3).'</b>: ';

	if ($allowfromweb == 1)
	{
		print $langs->trans("UnpackPackageInDolibarrRoot",$dirins).'<br>';
		print '<form enctype="multipart/form-data" method="POST" class="noborder" action="'.$_SERVER["PHP_SELF"].'" name="forminstall">';
		print '<input type="hidden" name="action" value="install">';
		print $langs->trans("YouCanSubmitFile").' <input type="file" name="fileinstall"> ';
		print '<input type="submit" name="'.dol_escape_htmltag($langs->trans("Send")).'" class="button">';
		print '</form>';
	}
	else
	{
		print $langs->trans("UnpackPackageInDolibarrRoot",$dirins).'<br>';
		print '<b>'.$langs->trans("StepNb",4).'</b>: ';
		print $langs->trans("SetupIsReadyForUse").'<br>';
	}
}


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
