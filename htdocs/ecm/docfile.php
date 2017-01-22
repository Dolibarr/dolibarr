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
 *	\file      	htdocs/ecm/docfile.php
 *	\ingroup   	ecm
 *	\brief     	Card of a file for ECM module
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
$langs->load("users");
$langs->load("orders");
$langs->load("propal");
$langs->load("bills");
$langs->load("contracts");
$langs->load("categories");

if (!$user->rights->ecm->setup) accessforbidden();

// Get parameters
$socid = GETPOST("socid","int");

// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="label";

$section=GETPOST("section");
if (! $section)
{
    dol_print_error('','Error, section parameter missing');
    exit;
}
$urlfile=GETPOST("urlfile");
if (! $urlfile)
{
    dol_print_error('',"ErrorParamNotDefined");
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
$ecmfile = new ECMFile($db);
if (! empty($_GET["fileid"]))
{
	$result=$ecmfile->fetch($_GET["fileid"]);
	if (! $result > 0)
	{
		dol_print_error($db,$ecmfile->error);
		exit;
	}
}
*/



/*******************************************************************
 * ACTIONS
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

// Rename file
if (GETPOST('action') == 'update' && ! GETPOST('cancel'))
{
    $error=0;

    $oldlabel=GETPOST('urlfile');
    $newlabel=GETPOST('label');

    //$db->begin();

    $olddir=$ecmdir->getRelativePath(0);
    $olddir=$conf->ecm->dir_output.'/'.$olddir;
    $newdir=$olddir;

    $oldfile=$olddir.$oldlabel;
    $newfile=$newdir.$newlabel;

    //print $oldfile.' - '.$newfile;
    if ($newlabel != $oldlabel)
    {
        $result=dol_move($oldfile,$newfile);
        if (! $result)
        {
            $langs->load('errors');
            $mesg='<div class="error">'.$langs->trans('ErrorFailToRenameFile',$oldfile,$newfile).'</div>';
            $error++;
        }
    }

    if (! $error)
    {
        //$db->commit();
        $urlfile=$newlabel;
    }
    else
    {
        //$db->rollback();
    }
}



/*******************************************************************
 * PAGE
 *
 * Put here all code to do according to value of "action" parameter
 ********************************************************************/

llxHeader();

$form=new Form($db);

$fullpath=$conf->ecm->dir_output.'/'.$relativepath.$urlfile;

$file = new stdClass();
$file->section_id=$ecmdir->id;
$file->label=$urlfile;

$head = ecm_file_prepare_head($file);

if ($_GET["action"] == 'edit')
{
	print '<form name="update" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="section" value="'.$section.'">';
    print '<input type="hidden" name="urlfile" value="'.$urlfile.'">';
	print '<input type="hidden" name="action" value="update">';
}

dol_fiche_head($head, 'card', $langs->trans("File"), 0, 'generic');

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
    $s=$tmpecmdir->getNomUrl(1).$s;
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
print ' -> ';
if (GETPOST('action') == 'edit') print '<input type="text" name="label" class="quatrevingtpercent" value="'.$urlfile.'">';
else print $urlfile;
print '</td></tr>';
/*print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td>';
if ($_GET["action"] == 'edit')
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
*/
print '<tr><td>'.$langs->trans("ECMCreationDate").'</td><td>';
print dol_print_date(dol_filemtime($fullpath),'dayhour');
print '</td></tr>';
/*print '<tr><td>'.$langs->trans("ECMDirectoryForFiles").'</td><td>';
print '/ecm/'.$relativepath;
print '</td></tr>';
print '<tr><td>'.$langs->trans("ECMNbOfDocs").'</td><td>';
print count($filearray);
print '</td></tr>';
print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>';
print dol_print_size($totalsize);
print '</td></tr>';
*/

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

print '<tr><td>'.$langs->trans("DirectDownloadLink").'</td><td>';
$modulepart='ecm';
$forcedownload=1;
$rellink='/document.php?modulepart='.$modulepart;
if ($forcedownload) $rellink.='&attachment=1';
if (! empty($object->entity)) $rellink.='&entity='.$object->entity;
$filepath=$relativepath.$file->label;
$rellink.='&file='.urlencode($filepath);
$fulllink=$urlwithroot.$rellink;
print img_picto('','object_globe.png').' ';
print '<input type="text" class="quatrevingtpercent" name="downloadlink" value="'.dol_escape_htmltag($fulllink).'">';
print ' <a data-ajax="false" href="'.$fulllink.'">'.$langs->trans("Download").'</a>';
print '</td></tr>';
print '</table>';

dol_fiche_end();

if ($_GET["action"] == 'edit')
{
    print '<div class="center">';
    print '<input type="submit" class="button" name="submit" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print '</form>';
}


// Confirmation de la suppression d'une ligne categorie
if ($_GET['action'] == 'delete_file')
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.urlencode($_GET["section"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile',$urlfile), 'confirm_deletefile', '', 1, 1);

}

if ($_GET["action"] != 'edit')
{

	if ($mesg) { print $mesg."<br>"; }


	// Actions buttons
	print '<div class="tabsAction">';

    if ($user->rights->ecm->setup)
    {
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&section='.$section.'&urlfile='.urlencode($urlfile).'">'.$langs->trans('Edit').'</a>';
    }
/*
	if ($user->rights->ecm->setup)
	{
		print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=delete_file&section='.$section.'&urlfile='.urlencode($urlfile).'">'.$langs->trans('Delete').'</a>';
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans('Delete').'</a>';
	}
*/
	print '</div>';
}


// End of page
llxFooter();
$db->close();
