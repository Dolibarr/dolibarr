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


// Security check
if ($user->societe_id > 0) $socid = $user->societe_id;

// Get parameters
$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";
if ($page == -1) { $page = 0 ; }
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$section=$_REQUEST["section"];
if (! $section)
{
	dolibarr_print_error('',"ErrorSectionParamNotDefined");
	exit;
}


// Load ecm object
$ecmdir = new ECMDirectory($db);
if (empty($_REQUEST["section"])) 
{
	dolibarr_print_error('','Error, section parameter missing');
	exit;
}
$result=$ecmdir->fetch($_REQUEST["section"]);
if (! $result > 0)
{
	dolibarr_print_error($db,$ecmdir->error);
	exit;
}
$relativepath=$ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

// Envoie fichier
if ( $_POST["sendit"] && $conf->upload != 0)
{
	if (! is_dir($upload_dir)) 
	{
		$result=create_exdir($upload_dir);
	}
	  
	if (is_dir($upload_dir))
	{
		$result = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0);
		if ($result > 0)
		{
		    //$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
		    //print_r($_FILES);
		    $result=$ecmdir->changeNbOfFiles('+');
	    }
	    else if ($result < 0)
	    {
	    	// Echec transfert (fichier depassant la limite ?)
		    $langs->load("errors");
	    	$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
	    	// print_r($_FILES);
	    }
	    else
	    {
	    	// File infected by a virus
		    $langs->load("errors");
	    	$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWith",$result).'</div>';
	    }
	}
	else
	{
	    // Echec transfert (fichier depassant la limite ?)
		$langs->load("errors");
		$mesg = '<div class="error">'.$langs->trans("ErrorFailToCreateDir",$upload_dir).'</div>';
	}
}

// Remove file
if ($_POST['action'] == 'confirm_deletefile' && $_POST['confirm'] == 'yes')
{
  $file = $upload_dir . "/" . urldecode($_GET["urlfile"]);
  $result=dol_delete_file($file);
  
  $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';

  $result=$ecmdir->changeNbOfFiles('-');
}

// Remove dir
if ($_POST['action'] == 'confirm_deletedir' && $_POST['confirm'] == 'yes')
{
	// Fetch was already done
	$result=$ecmdir->delete($user);
	header("Location: ".DOL_URL_ROOT."/ecm/index.php");
	exit;
	//	$mesg = '<div class="ok">'.$langs->trans("ECMSectionWasRemoved", $ecmdir->label).'</div>';
}

// Update description
if ($_POST['action'] == 'update' && ! $_POST['cancel'])
{
	$db->begin();
	
	$oldlabel=$ecmdir->label;
	$olddir=$ecmdir->getRelativePath(0);
	$olddir=$conf->ecm->dir_output.'/'.$olddir;
	
	// Fetch was already done
	$ecmdir->label = $_POST["label"];
	$ecmdir->description = $_POST["description"];
	$result=$ecmdir->update($user);
	if ($result > 0)
	{
		$error=0;
		
		// Try to rename file if changed
		if ($oldlabel != $ecmdir->label
			&& file_exists($olddir))
		{
			$newdir=$ecmdir->getRelativePath(1);		// return "xxx/zzz/" from ecm directory
			$newdir=$conf->ecm->dir_output.'/'.$newdir;
			//print $olddir.'-'.$newdir;
			$result=@rename($olddir,$newdir);
			if (! $result)
			{
				$langs->load('errors');
				$mesg='<div class="error">'.$langs->trans('ErrorFailToRenameDir',$olddir,$newdir).'</div>';
				$error++;
			}
		}
		
		if (! $error)
		{
			$db->commit();
			
			$relativepath=$ecmdir->getRelativePath();
			$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
		}
		else
		{
			$db->rollback();
		}			
	}
	else
	{
		$db->rollback();
		$mesg='<div class="error">'.$ecmdir->error.'</div>';
	}
}



/*******************************************************************
* PAGE
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

llxHeader();

$form=new Form($db);


// Construit liste des fichiers
$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
$totalsize=0;
foreach($filearray as $key => $file)
{
	$totalsize+=$file['size'];
}


$head = ecm_prepare_head($ecmdir);
dolibarr_fiche_head($head, 'card', $langs->trans("ECMManualOrg"));

if ($_GET["action"] == 'edit')
{
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="section" value="'.$section.'">';		
	print '<input type="hidden" name="action" value="update">';		
}

print '<table class="border" width="100%">';
print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td>';
$s='';
$tmpecmdir=new ECMDirectory($db);	// Need to create a new one
$tmpecmdir->fetch($ecmdir->id);
$result = 1;
$i=0;
while ($tmpecmdir && $result > 0)
{
	$tmpecmdir->ref=$tmpecmdir->label;
	if ($i == 0 && $_GET["action"] == 'edit')
	{
		$s='<input type="text" name="label" size="32" value="'.$tmpecmdir->label.'">';
	}
	else $s=$tmpecmdir->getNomUrl(1).$s;
	if ($tmpecmdir->fk_parent)
	{
		$s=' -> '.$s;
		$result=$tmpecmdir->fetch($tmpecmdir->fk_parent);
	}
	else
	{
		$tmpecmdir=0;
	}
	$i++;
}

print img_picto('','object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> ';
print $s;
print '</td></tr>';
print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
if ($_GET["action"] == 'edit')
{
	print '<textarea class="flat" name="description" cols="80">';
	print $ecmdir->description;
	print '</textarea>';
}
else print dol_nl2br($ecmdir->description);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationUser").'</td><td>';
$userecm=new User($db,$ecmdir->fk_user_c);
$userecm->fetch();
print $userecm->getNomUrl(1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationDate").'</td><td>';
print dolibarr_print_date($ecmdir->date_c,'dayhour');
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
//print $conf->ecm->dir_output;
print '/ecm/'.$relativepath;
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
print sizeof($filearray);
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
if ($_GET["action"] == 'edit')
{
	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
}
print '</table>';
if ($_GET["action"] == 'edit')
{
	print '</form>';
}
print '</div>';



// Actions buttons
if ($_GET["action"] != 'edit' && $_GET['action'] != 'delete_dir' && $_GET['action'] != 'delete')
{
	print '<div class="tabsAction">';
	
	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&section='.$section.'">'.$langs->trans('Edit').'</a>';
	}
	
	if (sizeof($filearray) == 0)
	{
		if ($user->rights->ecm->setup)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete_dir&section='.$section.'">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionDeleteRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("CannotRemoveDirectoryContainsFiles").'">'.$langs->trans('Delete').'</a>';
	}
	print '</div>';
}

if ($mesg) { print '<br>'.$mesg.'<br>'; }


// Confirm remove file
if ($_GET['action'] == 'delete')
{
	$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"].'&amp;urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	print '<br>';
}

// Confirm remove file
if ($_GET['action'] == 'delete_dir')
{
	$form->form_confirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"], $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection'), 'confirm_deletedir');
	print '<br>';
}

$formfile=new FormFile($db);

// Affiche formulaire upload
if ($user->rights->ecm->create)
{
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/ecm/docmine.php','',0,$section);
}

// List of document
// TODO: prevoir un droit sur la suppression des documents
if ($user->rights->ecm->read)
{
	$param='&amp;section='.$section;
	$formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->create);
}

// End of page
$db->close();

llxFooter('$Date$ - $Revision$');
?>
