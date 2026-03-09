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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */


/**
 *  \file       htdocs/ecm/dir_card.php
 *	\ingroup    ecm
 *	\brief     	Card of a directory for ECM module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/htmlecm.form.class.php';

// Load translation files required by page
$langs->loadLangs(array('ecm', 'companies', 'other'));

$action     = GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm    = GETPOST('confirm', 'alpha');

$module  = GETPOST('module', 'alpha');
$website = GETPOST('website', 'alpha');
$pageid  = GETPOSTINT('pageid');
if (empty($module)) {
	$module = 'ecm';
}

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$section = GETPOST("section", 'alpha') ? GETPOST("section", 'alpha') : GETPOST("relativedir", 'alpha');
if (!$section) {
	dol_print_error(null, "ErrorSectionParamNotDefined");
	exit;
}

// Load ecm object
$ecmdir = new EcmDirectory($db);

if ($module == 'ecm') {
	// $section should be an int except if it is dir not yet created into EcmDirectory
	$result = $ecmdir->fetch($section);
	if ($result > 0) {
		$relativepath = $ecmdir->getRelativePath();
		$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
	} else {
		$relativepath = $section;
		$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
	}
} else { // For example $module == 'medias'
	$relativepath = $section;
	$upload_dir = $conf->medias->multidir_output[$conf->entity].'/'.$relativepath;
}

// Permissions
$permissiontoread = 0;
$permissiontoadd = 0;
$permissiontoupload = 0;
if ($module == 'ecm') {
	$permissiontoread = $user->hasRight("ecm", "read");
	$permissiontoadd = $user->hasRight("ecm", "setup");
	$permissiontoupload = $user->hasRight("ecm", "upload");
}
if ($module == 'medias') {
	$permissiontoread = ($user->hasRight("mailing", "lire") || $user->hasRight("website", "read"));
	$permissiontoadd = ($user->hasRight("mailing", "creer") || $user->hasRight("website", "write"));
	$permissiontoupload = ($user->hasRight("mailing", "creer") || $user->hasRight("website", "write"));
}

if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

// Upload file
if (GETPOST("sendit") && getDolGlobalString('MAIN_UPLOAD_DOC') && $permissiontoupload) {
	if (dol_mkdir($upload_dir) >= 0) {
		$resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir."/".dol_unescapefile($_FILES['userfile']['name']), 0, 0, $_FILES['userfile']['error']);
		if (is_numeric($resupload) && $resupload > 0) {
			$result = $ecmdir->changeNbOfFiles('+');
		} else {
			$langs->load("errors");
			if ($resupload < 0) {	// Unknown error
				setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
			} elseif (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload)) {
				// Files infected by a virus
				setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
			} else { // Known error
				setEventMessages($langs->trans($resupload), null, 'errors');
			}
		}
	} else {
		// Failed transfer (exceeding the limit file?)
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFailToCreateDir", $upload_dir), null, 'errors');
	}
}

// Remove file
if ($action == 'confirm_deletefile' && $confirm == 'yes' && $permissiontoupload) {
	$langs->load("other");
	$file = $upload_dir."/".GETPOST('urlfile'); // Do not use urldecode here
	$ret = dol_delete_file($file);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}

	$result = $ecmdir->changeNbOfFiles('-');
}

// Remove dir
if ($action == 'confirm_deletedir' && $confirm == 'yes' && $permissiontoupload) {
	$backtourl = DOL_URL_ROOT."/ecm/index.php";
	if ($module == 'medias') {
		$backtourl = DOL_URL_ROOT."/website/index.php?file_manager=1";
	}

	$deletedirrecursive = (GETPOST('deletedirrecursive', 'alpha') == 'on' ? 1 : 0);

	if ($module == 'ecm' && $ecmdir->id > 0) {	// If manual ECM and directory is indexed into database
		// Fetch was already done
		$result = $ecmdir->delete($user, 'all', $deletedirrecursive);
		if ($result <= 0) {
			$langs->load('errors');
			setEventMessages($langs->trans($ecmdir->error, $ecmdir->label), null, 'errors');
		}
	} else {
		if ($deletedirrecursive) {
			$resbool = dol_delete_dir_recursive($upload_dir, 0, 1);
		} else {
			$resbool = dol_delete_dir($upload_dir, 1);
		}
		if ($resbool) {
			$result = 1;
		} else {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorFailToDeleteDir", $upload_dir), null, 'errors');
			$result = 0;
		}
	}
	if ($result > 0) {
		header("Location: ".$backtourl);
		exit;
	}
}

// Update dirname or description
if ($action == 'update' && !GETPOST('cancel', 'alpha') && $permissiontoadd) {
	$error = 0;

	if ($module == 'ecm') {
		$oldlabel = $ecmdir->label;
		$olddir = $ecmdir->getRelativePath(0);
		$olddir = $conf->ecm->dir_output.'/'.$olddir;
	} else {
		$olddir = GETPOST('section', 'alpha');
		$olddir = $conf->medias->multidir_output[$conf->entity].'/'.$relativepath;
	}

	if ($module == 'ecm') {
		$db->begin();

		// Fetch was already done
		$ecmdir->label = dol_sanitizeFileName(GETPOST("label"));
		$fk_parent = GETPOSTINT("catParent");
		if ($fk_parent == -1) {
			$ecmdir->fk_parent = 0;
		} else {
			$ecmdir->fk_parent = $fk_parent;
		}
		$ecmdir->description = GETPOST("description");
		$ret = $extrafields->setOptionalsFromPost(null, $ecmdir);
		if ($ret < 0) {
			$error++;
		}
		if (!$error) {
			// Actions on extra fields
			$result = $ecmdir->insertExtraFields();
			if ($result < 0) {
				setEventMessages($ecmdir->error, $ecmdir->errors, 'errors');
				$error++;
			}
		}
		$result = $ecmdir->update($user);
		if ($result > 0) {
			$newdir = $ecmdir->getRelativePath(1);
			$newdir = $conf->ecm->dir_output.'/'.$newdir;
			// Try to rename file if changed
			if (($oldlabel != $ecmdir->label && file_exists($olddir)) || ($olddir != $newdir && file_exists($olddir))) {
				$newdir = $ecmdir->getRelativePath(1); // return "xxx/zzz/" from ecm directory
				$newdir = $conf->ecm->dir_output.'/'.$newdir;
				//print $olddir.'-'.$newdir;
				$result = @rename($olddir, $newdir);
				if (!$result) {
					$langs->load('errors');
					setEventMessages($langs->trans('ErrorFailToRenameDir', $olddir, $newdir), null, 'errors');
					$error++;
				}
			}

			if (!$error) {
				$db->commit();

				// Set new value after renaming
				$relativepath = $ecmdir->getRelativePath();
				$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
			} else {
				$db->rollback();
			}
		} else {
			$db->rollback();
			setEventMessages($ecmdir->error, $ecmdir->errors, 'errors');
		}
	} else {
		$newdir = $conf->medias->multidir_output[$conf->entity].'/'.GETPOST('oldrelparentdir', 'alpha').'/'.GETPOST('label', 'alpha');

		$result = @rename($olddir, $newdir);
		if (!$result) {
			$langs->load('errors');
			setEventMessages($langs->trans('ErrorFailToRenameDir', $olddir, $newdir), null, 'errors');
			$error++;
		}

		if (!$error) {
			// Set new value after renaming
			$relativepath = GETPOST('oldrelparentdir', 'alpha').'/'.GETPOST('label', 'alpha');
			$upload_dir = $conf->medias->multidir_output[$conf->entity].'/'.$relativepath;
			$section = $relativepath;
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formecm = new FormEcm($db);

$object = new EcmDirectory($db); // Need to create a new one instance
$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($module == 'ecm' && $ecmdir->id > 0) {
	$object->fetch($ecmdir->id);
}

llxHeader();

// Built the file List
$filearrayall = dol_dir_list($upload_dir, "all", 0, '', '', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
$totalsize = 0;
foreach ($filearray as $key => $file) {
	$totalsize += $file['size'];
}


$head = ecm_prepare_head($ecmdir, $module, $section);
print dol_get_fiche_head($head, 'card', $langs->trans("ECMSectionManual"), -1, 'dir');


if ($action == 'edit') {
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="section" value="'.$section.'">';
	print '<input type="hidden" name="module" value="'.$module.'">';
	print '<input type="hidden" name="action" value="update">';
}


$morehtml = '';

$morehtmlref = '/'.$module.'/'.$relativepath;

if ($module == 'ecm') {
	$s = '';
	$result = 1;
	$i = 0;
	$tmpecmdir = new EcmDirectory($db); // Need to create a new one
	if ($ecmdir->id > 0) {
		$tmpecmdir->fetch($ecmdir->id);
		while ($tmpecmdir && $result > 0) {
			$tmpecmdir->ref = $tmpecmdir->label;
			$s = $tmpecmdir->getNomUrl(1).$s;
			if ($tmpecmdir->fk_parent) {
				$s = ' -> '.$s;
				$result = $tmpecmdir->fetch($tmpecmdir->fk_parent);
			} else {
				$tmpecmdir = 0;
			}
			$i++;
		}
	} else {
		$s .= implode(' -> ', explode('/', $section));
	}
	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> '.$s;
}
if ($module == 'medias') {
	$s = 'medias -> ';
	$result = 1;
	$subdirs = explode('/', $section);
	$i = 0;
	foreach ($subdirs as $subdir) {
		if ($i == (count($subdirs) - 1)) {
			if ($action == 'edit') {
				$s .= '<input type="text" name="label" class="minwidth300" maxlength="32" value="'.$subdir.'">';
				$s .= '<input type="hidden" name="oldrelparentdir" value="'.dirname($section).'">';
				$s .= '<input type="hidden" name="oldreldir" value="'.basename($section).'">';
			} else {
				$s .= $subdir;
			}
		}
		if ($i < (count($subdirs) - 1)) {
			$s .= $subdir.' -> ';
		}
		$i++;
	}

	$morehtmlref = $s;
}


dol_banner_tab($object, '', $morehtml, 0, '', '', $morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';
/*print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
print img_picto('','object_dir').' <a href="'.DOL_URL_ROOT.'/ecm/index.php">'.$langs->trans("ECMRoot").'</a> -> ';
print $s;
print '</td></tr>';*/
if ($module == 'ecm') {
	if ($action == 'edit') {
		print '<tr><td class="titlefield tdtop">'.$langs->trans("ECMDirName").'</td><td>';
		print '<input type="text" name="label" class="minwidth300" maxlength="32" value="'.$ecmdir->label.'">';
		print '</td></tr>';
		print '<tr><td class="titlefield tdtop">'.$langs->trans("ECMParentDirectory").'</td><td>';
		print $formecm->selectAllSections($ecmdir->fk_parent, '', 'ecm', array($ecmdir->id));
		print '</td><td>';
		print '</td></tr>';
	}

	print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	if ($action == 'edit') {
		print '<textarea class="flat quatrevingtpercent" name="description">';
		print $ecmdir->description;
		print '</textarea>';
	} else {
		print dol_nl2br($ecmdir->description);
	}
	print '</td></tr>';

	print '<tr><td class="titlefield">'.$langs->trans("ECMCreationUser").'</td><td>';
	if ($ecmdir->fk_user_c > 0) {
		$userecm = new User($db);
		$userecm->fetch($ecmdir->fk_user_c);
		print $userecm->getNomUrl(1);
	}
	print '</td></tr>';
}
print '<tr><td class="titlefield">'.$langs->trans("ECMCreationDate").'</td><td>';
if ($module == 'ecm') {
	print dol_print_date($ecmdir->date_c, 'dayhour');
} else {
	//var_dump($upload_dir);
	print dol_print_date(dol_filemtime($upload_dir), 'dayhour');
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
if ($module == 'ecm') {
	print '/ecm/'.$relativepath;
} else {
	print '/'.$module.'/'.$relativepath;
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
$nbofiles = count($filearray);
print $nbofiles;
if ($ecmdir->id > 0) {
	// Test if nb is same than in cache
	if ($nbofiles != $ecmdir->cachenbofdoc) {
		$ecmdir->changeNbOfFiles((string) $nbofiles);
	}
}
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
print $object->showOptionals($extrafields, ($action == 'edit' ? 'edit' : 'view'));
print '</table>';

if ($action == 'edit') {
	print $form->buttonsSaveCancel();
}

print '</div>';
if ($action == 'edit') {
	print '</form>';
}

print dol_get_fiche_end();



// Actions buttons
if ($action != 'edit' && $action != 'delete' && $action != 'deletefile') {
	print '<div class="tabsAction">';

	if ($permissiontoadd) {
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().($module ? '&module='.$module : '').'&section='.$section.'">'.$langs->trans('Edit').'</a>';
	}

	if ($permissiontoadd) {
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/ecm/dir_add_card.php?action=create&token='.newToken().($module ? '&module='.$module : '').'&catParent='.$section.'">'.$langs->trans('ECMAddSection').'</a>';
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('ECMAddSection').'</a>';
	}

	print dolGetButtonAction($langs->trans('Delete'), '', 'delete', $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&token='.newToken().($module ? '&module='.urlencode($module) : '').'&section='.urlencode($section).($backtopage ? '&backtopage='.urlencode($backtopage) : ''), '', $permissiontoadd);

	print '</div>';
}


// Confirm remove file
if ($action == 'deletefile') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.urlencode(GETPOST("section", 'alpha')).'&urlfile='.urlencode(GETPOST("urlfile")).($backtopage ? '&backtopage='.urlencode($backtopage) : ''), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile');
}

// Confirm remove dir
if ($action == 'delete' || $action == 'delete_dir') {
	$relativepathwithoutslash = preg_replace('/[\/]$/', '', $relativepath);

	//Form to close proposal (signed or not)
	if (count($filearrayall) > 0) {
		$langs->load("other");
		$formquestion = array(
			array('type' => 'checkbox', 'name' => 'deletedirrecursive', 'label' => $langs->trans("ContentOfDirectoryIsNotEmpty").'<br>'.$langs->trans("DeleteAlsoContentRecursively"), 'value' => '0')				// Field to complete private note (not replace)
		);
	}

	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.urlencode(GETPOST('section', 'alpha')).($module ? '&module='.$module : '').($backtopage ? '&backtopage='.urlencode($backtopage) : ''), $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection', $relativepathwithoutslash), 'confirm_deletedir', $formquestion, 1, 1);
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
