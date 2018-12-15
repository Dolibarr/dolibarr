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
 *  \file       htdocs/ecm/dir_card.php
 *	\ingroup    ecm
 *	\brief     	Card of a directory for ECM module
 *	\author		Laurent Destailleur
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';

// Load translation files required by page
$langs->loadLangs(array('ecm', 'companies', 'other'));

$action     = GETPOST('action','alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm    = GETPOST('confirm','alpha');

$module  = GETPOST('module', 'alpha');
$website = GETPOST('website', 'alpha');
$pageid  = GETPOST('pageid', 'int');
if (empty($module)) $module='ecm';

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$section=GETPOST("section", 'alpha')?GETPOST("section", 'alpha'):GETPOST("relativedir", 'alpha');
if (! $section)
{
	dol_print_error('',"ErrorSectionParamNotDefined");
	exit;
}

// Load ecm object
$ecmdir = new EcmDirectory($db);

if ($module == 'ecm')
{
	$result=$ecmdir->fetch($section);
	if (! $result > 0)
	{
		dol_print_error($db, $ecmdir->error);
		exit;
	}

	$relativepath=$ecmdir->getRelativePath();
	$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
}
else	// For example $module == 'medias'
{
	$relativepath = $section;
	$upload_dir = $conf->medias->multidir_output[$conf->entity].'/'.$relativepath;
}

// Permissions
$permtoadd = 0;
$permtoupload = 0;
if ($module == 'ecm')
{
	$permtoadd = $user->rights->ecm->setup;
	$permtoupload = $user->rights->ecm->upload;
}
if ($module == 'medias')
{
	$permtoadd = ($user->rights->mailing->creer || $user->rights->website->write);
	$permtoupload = ($user->rights->mailing->creer || $user->rights->website->write);
}



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
	$backtourl = DOL_URL_ROOT."/ecm/index.php";
	if ($module == 'medias') $backtourl = DOL_URL_ROOT."/website/index.php?file_manager=1";

	$deletedirrecursive = (GETPOST('deletedirrecursive','alpha') == 'on' ? 1 : 0);

	if ($module == 'ecm')
	{
		// Fetch was already done
		$result=$ecmdir->delete($user, 'all', $deletedirrecursive);
		if ($result <= 0)
		{
			$langs->load('errors');
			setEventMessages($langs->trans($ecmdir->error,$ecmdir->label), null, 'errors');
		}
	}
	else
	{
		if ($deletedirrecursive)
		{
			$resbool = dol_delete_dir_recursive($upload_dir, 0, 1);
		}
		else
		{
			$resbool = dol_delete_dir($upload_dir, 1);
		}
		if ($resbool) $result = 1;
		else
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFailToDeleteDir", $upload_dir), null, 'errors');
			$result = 0;
		}
	}
	if ($result > 0)
	{
		header("Location: ".$backtourl);
		exit;
	}
}

// Update dirname or description
if ($action == 'update' && ! GETPOST('cancel','alpha'))
{
	$error=0;

    if ($module == 'ecm')
    {
    	$oldlabel=$ecmdir->label;
		$olddir=$ecmdir->getRelativePath(0);
		$olddir=$conf->ecm->dir_output.'/'.$olddir;
    }
    else
    {
    	$olddir=GETPOST('section','alpha');
    	$olddir=$conf->medias->multidir_output[$conf->entity].'/'.$relativepath;
    }

    if ($module == 'ecm')
    {
    	$db->begin();

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

				// Set new value after renaming
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
    else
    {
    	$newdir = $conf->medias->multidir_output[$conf->entity].'/'.GETPOST('oldrelparentdir','alpha').'/'.GETPOST('label','alpha');

    	$result=@rename($olddir,$newdir);
    	if (! $result)
    	{
    		$langs->load('errors');
    		setEventMessages($langs->trans('ErrorFailToRenameDir',$olddir,$newdir), null, 'errors');
    		$error++;
    	}

    	if (! $error)
    	{
    		// Set new value after renaming
    		$relativepath=GETPOST('oldrelparentdir','alpha').'/'.GETPOST('label','alpha');
    		$upload_dir = $conf->medias->multidir_output[$conf->entity].'/'.$relativepath;
    		$section = $relativepath;
    	}
    }
}



/*******************************************************************
* View
********************************************************************/

$form=new Form($db);

$object=new EcmDirectory($db);	// Need to create a new one instance

if ($module == 'ecm')
{
	$object->fetch($ecmdir->id);
}

llxHeader();

// Built the file List
$filearrayall=dol_dir_list($upload_dir,"all",0,'','',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
$totalsize=0;
foreach($filearray as $key => $file)
{
	$totalsize+=$file['size'];
}


$head = ecm_prepare_head($ecmdir, $module, $section);
dol_fiche_head($head, 'card', $langs->trans("ECMSectionManual"), -1, 'dir');


if ($action == 'edit')
{
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="section" value="'.$section.'">';
	print '<input type="hidden" name="module" value="'.$module.'">';
	print '<input type="hidden" name="action" value="update">';
}


$morehtml='';

$morehtmlref = '/'.$module.'/'.$relativepath;

if ($module == 'ecm')
{
	$s='';
	$result = 1;
	$i=0;
	$tmpecmdir=new EcmDirectory($db);	// Need to create a new one
	$tmpecmdir->fetch($ecmdir->id);
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

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> '.$s;
}
if ($module == 'medias')
{
	$s='medias -> ';
	$result = 1;
	$subdirs=explode('/', $section);
	$i=0;
	foreach($subdirs as $subdir)
	{
		if ($i == (count($subdirs) - 1))
		{
			if ($action == 'edit')
			{
				$s.='<input type="text" name="label" class="minwidth300" maxlength="32" value="'.$subdir.'">';
				$s.='<input type="hidden" name="oldrelparentdir" value="'.dirname($section).'">';
				$s.='<input type="hidden" name="oldreldir" value="'.basename($section).'">';
			}
			else $s.=$subdir;
		}
		if ($i < (count($subdirs) - 1))
		{
			$s.=$subdir.' -> ';
		}
		$i++;
	}

	$morehtmlref = $s;
}


dol_banner_tab($object, '', $morehtml, 0, '', '', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent">';
/*print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
print img_picto('','object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> ';
print $s;
print '</td></tr>';*/
if ($module == 'ecm')
{
	print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	if ($action == 'edit')
	{
		print '<textarea class="flat quatrevingtpercent" name="description">';
		print $ecmdir->description;
		print '</textarea>';
	}
	else print dol_nl2br($ecmdir->description);
	print '</td></tr>';

	print '<tr><td class="titlefield">'.$langs->trans("ECMCreationUser").'</td><td>';
	$userecm=new User($db);
	$userecm->fetch($ecmdir->fk_user_c);
	print $userecm->getNomUrl(1);
	print '</td></tr>';
}
print '<tr><td class="titlefield">'.$langs->trans("ECMCreationDate").'</td><td>';
if ($module == 'ecm')
{
	print dol_print_date($ecmdir->date_c,'dayhour');
}
else
{
	//var_dump($upload_dir);
	print dol_print_date(dol_filemtime($upload_dir), 'dayhour');
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
if ($module == 'ecm')
{
	print '/ecm/'.$relativepath;
}
else
{
	print '/'.$module.'/'.$relativepath;
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
$nbofiles=count($filearray);
print $nbofiles;
if ($ecmdir->id > 0)
{
	// Test if nb is same than in cache
	if ($nbofiles != $ecmdir->cachenbofdoc)
	{
	    $ecmdir->changeNbOfFiles((string) $nbofiles);
	}
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
print '</table>';

if ($action == 'edit')
{
	print '<br><div align="center">';
	print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
	print ' &nbsp; &nbsp; ';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
}

print '</div>';
if ($action == 'edit')
{
	print '</form>';
}

dol_fiche_end();



// Actions buttons
if ($action != 'edit' && $action != 'delete')
{
	print '<div class="tabsAction">';

	if ($permtoadd)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit'.($module?'&module='.$module:'').'&section='.$section.'">'.$langs->trans('Edit').'</a>';
	}

	if ($permtoadd)
	{
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/ecm/dir_add_card.php?action=create'.($module?'&module='.$module:'').'&catParent='.$section.'">'.$langs->trans('ECMAddSection').'</a>';
	}
	else
	{
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ECMAddSection').'</a>';
	}

	//if (count($filearrayall) == 0)
	//{
		if ($permtoadd)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete_dir'.($module?'&module='.$module:'').'&section='.$section.'">'.$langs->trans('Delete').'</a>';
		}
		else
		{
			print '<a class="butActionDeleteRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
		}
	/*}
	else
	{
		if (count($filearray) > 0)
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("CannotRemoveDirectoryContainsFiles").'">'.$langs->trans('Delete').'</a>';
		else
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("CannotRemoveDirectoryContainsFilesOrDirs").'">'.$langs->trans('Delete').'</a>';
	}*/
	print '</div>';
}

// Confirm remove file
if ($action == 'delete')
{
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.GETPOST("section",'alpha').'&urlfile='.urlencode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
}

// Confirm remove file
if ($action == 'delete_dir')
{
	$relativepathwithoutslash=preg_replace('/[\/]$/','',$relativepath);

	//Form to close proposal (signed or not)
	if (count($filearrayall) > 0)
	{
		$langs->load("other");
		$formquestion = array(
			array('type' => 'checkbox', 'name' => 'deletedirrecursive', 'label' => $langs->trans("ContentOfDirectoryIsNotEmpty").'<br>'.$langs->trans("DeleteAlsoContentRecursively"),'value' => '0')				// Field to complete private note (not replace)
		);
	}

	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.GETPOST('section','alpha').($module?'&module='.$module:''), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection',$relativepathwithoutslash), 'confirm_deletedir', $formquestion, 1, 1);
}


/*
$formfile=new FormFile($db);

// Display upload form
if ($user->rights->ecm->upload)
{
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/ecm/dir_card.php','',0,$section);
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
