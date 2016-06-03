<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
 *  \file       htdocs/ecm/docother.php
 *  \ingroup    ecm
 *  \brief      Main ecm page
 *  \author		Laurent Destailleur
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

// Get parameters
$socid = GETPOST("socid","int");

// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}

$section=$_GET["section"];
if (! $section) $section='misc';
$upload_dir = $conf->ecm->dir_output.'/'.$section;



/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if (dol_mkdir($upload_dir) >= 0)
	{
		$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . dol_unescapefile($_FILES['userfile']['name']),0,0,$_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0)
		{
		    $result=$ecmdir->changeNbOfFiles('+');
	    }
	    else
	    {
   			$langs->load("errors");
			if ($resupload < 0)	// Unknown error
			{
				setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
			}
			else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
			{
				setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
			}
			else	// Known error
			{
				setEventMessages($langs->trans($resupload), null, 'errors');
			}
	    }
	}
	else
	{
	    // Echec transfert (fichier depassant la limite ?)
		$langs->load("errors");
		$mesg = '<div class="error">'.$langs->trans("ErrorFailToCreateDir",$upload_dir).'</div>';
	}
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
    $langs->load("other");
	$file = $upload_dir . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret=dol_delete_file($file);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
}





/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

llxHeader();

$form=new Form($db);

print load_fiche_titre($langs->trans("ECMAutoOrg"));

//$head = societe_prepare_head($societe);



/*
 * Confirmation of deleting a product line
 */
if ($_GET['action'] == 'delete_file')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?socid='.$socid.'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');

}

// Construct files list
clearstatcache();
$totalsize=0;
$filearray=array();
$errorlevel=error_reporting();
error_reporting(0);
$handle=opendir($upload_dir);
error_reporting($errorlevel);
if (is_resource($handle))
{
	$i=0;
	while (($file = readdir($handle))!==false)
	{
		if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
		{
			$filearray[$i]->name=$file;
			$filearray[$i]->size=dol_filesize($upload_dir."/".$file);
			$filearray[$i]->date=dol_filemtime($upload_dir."/".$file);
			$totalsize+=$filearray[$i]->size;
			$i++;
		}
	}
	closedir($handle);
}
else
{
	//            print '<div class="error">'.$langs->trans("ErrorCanNotReadDir",$upload_dir).'</div>';
}


/*

print '<table class="border"width="100%">';

// Nbre fichiers
print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

//Total taille
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

print '</table>';

print '</div>';

*/


if ($mesg) { print $mesg."<br>"; }


print $langs->trans("FeatureNotYetAvailable");

// End of page
llxFooter();
$db->close();
