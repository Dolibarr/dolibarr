<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       htdoc/ecm/docmine.php
		\ingroup    ecm
		\brief      Main page for a section
		\version    $Id$
		\author		Laurent Destailleur
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/ecm/ecmdirectory.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ecm.lib.php");


// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Permissions
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$section=$_REQUEST["section"];
if (! $section)
{
	dolibarr_print_error('',"ErrorSectionParamNotDefined");
	exit;
}

$ecmdir = new ECMDirectory($db);
if (! empty($_REQUEST["section"]))
{
	$result=$ecmdir->fetch($_REQUEST["section"]);
	if (! $result > 0)
	{
		dolibarr_print_error($db,$ecmdir->error);
		exit;
	}
}

$upload_dir = $conf->ecm->dir_output.'/'.$ecmdir->label;


/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// Envoie fichier
if ( $_POST["sendit"] && $conf->upload != 0)
{
  if (! is_dir($upload_dir)) create_exdir($upload_dir);
  
  if (is_dir($upload_dir))
  {
  	$result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name']);
  	if ($result == 1)
  	{
    	$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
    	//print_r($_FILES);

    	$result=$ecmdir->changeNbOfFiles('+');
    }
    else if (!$result)
    {
    	// Echec transfert (fichier depassant la limite ?)
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
    	// print_r($_FILES);
    }
    else
    {
    	// Fichier infect? par un virus
    	$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
    }
  }
}

// Suppression fichier
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
  $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
  $result=dol_delete_file($file);
  
  $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';

  $result=$ecmdir->changeNbOfFiles('-');
}





/*******************************************************************
* PAGE
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

llxHeader();

$form=new Form($db);


// Construit liste des fichiers
clearstatcache();
$totalsize=0;
$filearray=array();
$errorlevel=error_reporting();
error_reporting(0);
$handle=opendir($upload_dir);
error_reporting($errorlevel);
if ($handle)
{
	$i=0;
	while (($file = readdir($handle))!==false)
	{
		if (!is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
		{
			$filearray[$i]->name=$file;
			$filearray[$i]->size=filesize($upload_dir."/".$file);
			$filearray[$i]->date=filemtime($upload_dir."/".$file);
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


$head = ecm_prepare_head($ecmdir);
dolibarr_fiche_head($head, 'card', $langs->trans("ECMManualOrg"));


print '<table class="border" width="100%">';
print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
$s='';
$tmpecmdir=$ecmdir;
$result = 1;
while ($tmpecmdir && $result > 0)
{
	$tmpecmdir->ref=$tmpecmdir->label;
	$s=' -> '.$tmpecmdir->getNomUrl(1).$s;
	if ($tmpecmdir->fk_parent)
	{
		$result=$tmpecmdir->fetch($tmpecmdir->fk_parent);
	}
	else
	{
		$tmpecmdir=0;
	}
}
print img_picto('','object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a>';
print $s;
print '</td></tr>';
print '<tr><td>'.$langs->trans("Description").'</td><td>';
print dol_nl2br($ecmdir->description);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationUser").'</td><td>';
$user=new User($db,$ecmdir->fk_user_c);
$user->fetch();
print $user->getNomUrl(1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationDate").'</td><td>';
print dolibarr_print_date($ecmdir->date_c,'dayhour');
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
print sizeof($filearray);
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print $totalsize;
print '</td></tr>';
print '</table>';

print '</div>';



/*
* Confirmation de la suppression d'une ligne produit
*/
if ($_GET['action'] == 'delete_file')
{
	$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	print '<br>';
}

if ($mesg) { print $mesg."<br>"; }

// Affiche formulaire upload
$formfile=new FormFile($db);
$formfile->form_attach_new_file(DOL_URL_ROOT.'/ecm/docmine.php','',0,$section);

// Affiche liste des documents existant
print_titre($langs->trans("AttachedFiles"));

/**
 * TODO Mettre cette section dans une zone AJAX
 */ 
$modulepart='ecm';
$url=$_SERVER["PHP_SELF"];
print '<table width="100%" class="noborder">';
print '<tr class="liste_titre">';
$param='&amp;socid='.$socid;
print_liste_field_titre($langs->trans("Document"),$_SERVER["PHP_SELF"],"name","",$param,'align="left"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Size"),$_SERVER["PHP_SELF"],"size","",$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"date","",$param,'align="center"',$sortfield,$sortorder);
print '<td>&nbsp;</td>';
print '</tr>';

usort($filearray,"dol_compare_file");

$var=true;
foreach($filearray as $key => $file)
{
	if (!is_dir($dir.$file->name) && substr($file->name, 0, 1) <> '.' && substr($file->name, 0, 3) <> 'CVS')
	{
		$var=!$var;
		print "<tr $bc[$var]><td>";
		echo '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&type=application/binary&file='.urlencode($prefix.$file->name).'">'.$file->name.'</a>';
		print "</td>\n";
		print '<td align="right">'.$file->size.' '.$langs->trans("bytes").'</td>';
		print '<td align="center">'.dolibarr_print_date($file->date,"dayhour").'</td>';
		print '<td align="center">';
		echo '<a href="'.$url.'?section='.$_REQUEST["section"].'&amp;action=delete_file&urlfile='.urlencode($file->name).'">'.img_delete().'</a>';
		print "</td></tr>\n";
	}
}
print "</table>";
// Fin de zone Ajax


// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
