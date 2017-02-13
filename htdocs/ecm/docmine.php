<?php
/* Copyright (C) 2008-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/ecm/docmine.php
 *	\ingroup    ecm
 *	\brief     	Card of a directory for ECM module
 *	\author		Laurent Destailleur
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';

// Load traductions files
$langs->load("ecm");
$langs->load("companies");
$langs->load("other");

$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$section=GETPOST("section");
if (! $section)
{
	dol_print_error('',"ErrorSectionParamNotDefined");
	exit;
}


// Load ecm object
$ecmdir = new EcmDirectory($db);
$result=$ecmdir->fetch(GETPOST("section"));
if (! $result > 0)
{
	dol_print_error($db,$ecmdir->error);
	exit;
}
$relativepath=$ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;



/*
 * Actions
 */

// Upload file
if (GETPOST("sendit") && ! empty($conf->global->MAIN_UPLOAD_DOC))
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
	    // Failed transfer (exceeding the limit file?)
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFailToCreateDir",$upload_dir), null, 'errors');
	}
}

// Remove file
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
    $langs->load("other");
    $file = $upload_dir . "/" . GETPOST('urlfile');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
    $ret=dol_delete_file($file);
    if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
    else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');

    $result=$ecmdir->changeNbOfFiles('-');
}

// Remove dir
if ($action == 'confirm_deletedir' && $confirm == 'yes')
{
	// Fetch was already done
	$result=$ecmdir->delete($user);
	if ($result > 0)
	{
		header("Location: ".DOL_URL_ROOT."/ecm/index.php");
		exit;
	}
	else
	{
		$langs->load('errors');
		setEventMessages($langs->trans($ecmdir->error,$ecmdir->label), null, 'errors');
	}
}

// Update description
if ($action == 'update' && ! GETPOST('cancel'))
{
	$error=0;

    $db->begin();

	$oldlabel=$ecmdir->label;
	$olddir=$ecmdir->getRelativePath(0);
	$olddir=$conf->ecm->dir_output.'/'.$olddir;

	// Fetch was already done
	$ecmdir->label = dol_sanitizeFileName(GETPOST("label"));
	$ecmdir->description = GETPOST("description");
	$result=$ecmdir->update($user);
	if ($result > 0)
	{
		// Try to rename file if changed
		if ($oldlabel != $ecmdir->label	&& file_exists($olddir))
		{
			$newdir=$ecmdir->getRelativePath(1);		// return "xxx/zzz/" from ecm directory
			$newdir=$conf->ecm->dir_output.'/'.$newdir;
			//print $olddir.'-'.$newdir;
			$result=@rename($olddir,$newdir);
			if (! $result)
			{
				$langs->load('errors');
				setEventMessages($langs->trans('ErrorFailToRenameDir',$olddir,$newdir), null, 'errors');
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
		setEventMessages($ecmdir->error, $ecmdir->errors, 'errors');
	}
}



/*******************************************************************
* PAGE
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

llxHeader();

$form=new Form($db);


// Built the file List
$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
$totalsize=0;
foreach($filearray as $key => $file)
{
	$totalsize+=$file['size'];
}


$head = ecm_prepare_head($ecmdir);
dol_fiche_head($head, 'card', $langs->trans("ECMSectionManual"), '', 'dir');

if ($action == 'edit')
{
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="section" value="'.$section.'">';
	print '<input type="hidden" name="action" value="update">';
}

print '<table class="border" width="100%">';
print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
$s='';
$tmpecmdir=new EcmDirectory($db);	// Need to create a new one
$tmpecmdir->fetch($ecmdir->id);
$result = 1;
$i=0;
while ($tmpecmdir && $result > 0)
{
	$tmpecmdir->ref=$tmpecmdir->label;
	if ($i == 0 && $action == 'edit')
	{
		$s='<input type="text" name="label" class="minwidth300" maxlength="32" value="'.$tmpecmdir->label.'">';
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
print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
if ($action == 'edit')
{
	print '<textarea class="flat" name="description" cols="80">';
	print $ecmdir->description;
	print '</textarea>';
}
else print dol_nl2br($ecmdir->description);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationUser").'</td><td>';
$userecm=new User($db);
$userecm->fetch($ecmdir->fk_user_c);
print $userecm->getNomUrl(1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMCreationDate").'</td><td>';
print dol_print_date($ecmdir->date_c,'dayhour');
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
print '/ecm/'.$relativepath;
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
$nbofiles=count($filearray);
print $nbofiles;
// Test if nb is same than in cache
if ($nbofiles != $ecmdir->cachenbofdoc)
{
    $ecmdir->changeNbOfFiles((string) $nbofiles);
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
if ($action == 'edit')
{
	print '<tr><td colspan="2" align="center">';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</td></tr>';
}
print '</table>';
if ($action == 'edit')
{
	print '</form>';
}
print '</div>';



// Actions buttons
if ($action != 'edit' && $action != 'delete')
{
	print '<div class="tabsAction">';

	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&section='.$section.'">'.$langs->trans('Edit').'</a>';
	}

	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/ecm/docdir.php?action=create&catParent='.$section.'">'.$langs->trans('ECMAddSection').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ECMAddSection').'</a>';
	}

	if (count($filearray) == 0)
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

// Confirm remove file
if ($action == 'delete')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"].'&amp;urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
	
}

// Confirm remove file
if ($action == 'delete_dir')
{
	$relativepathwithoutslash=preg_replace('/[\/]$/','',$relativepath);
    print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$_REQUEST["section"], $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$relativepathwithoutslash), 'confirm_deletedir', '', 1, 1);
	
}

$formfile=new FormFile($db);

/*
// Display upload form
if ($user->rights->ecm->upload)
{
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/ecm/docmine.php','',0,$section);
}

// List of document
if ($user->rights->ecm->read)
{
	$param='&amp;section='.$section;
	$formfile->list_of_documents($filearray,'','ecm',$param,1,$relativepath,$user->rights->ecm->upload);
}
*/

// End of page
llxFooter();
$db->close();
