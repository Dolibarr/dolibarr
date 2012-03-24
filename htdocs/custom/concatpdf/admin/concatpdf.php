<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/concatpdf/admin/concatpdf.php
 *      \ingroup    concatpdf
 *      \brief      Page to setup module ConcatPdf
 */

define('NOCSRFCHECK',1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");

// Use on dev env only
preg_match('/^\/([^\/]+)\//', dirname($_SERVER["SCRIPT_NAME"]), $regs);
$realpath = readlink($_SERVER['DOCUMENT_ROOT'].'/'.$regs[1]);
if (! $res && file_exists($realpath."main.inc.php")) $res=@include($realpath."main.inc.php");

if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res && file_exists("../../../../../dolibarr/htdocs/main.inc.php")) $res=@include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (! $res) die("Include of main fails");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php');
require_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');


if (!$user->admin) accessforbidden();

$langs->load("admin");
$langs->load("other");
$langs->load("concatpdf@concatpdf");

$def = array();
$action=GETPOST("action");
$actionsave=GETPOST("save");

$modules = array('proposals','orders','invoices');


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if (preg_match('/\.pdf$/', $_FILES['userfile']['name']))
	{
		$upload_dir = $conf->concatpdf->dir_output.'/'.$_POST['module'];
		
		if (dol_mkdir($upload_dir) >= 0)
		{
			$resupload=dol_move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_dir . "/" . $_FILES['userfile']['name'],0,0,$_FILES['userfile']['error']);
			if (is_numeric($resupload) && $resupload > 0)
			{
				$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
			}
			else
			{
				$langs->load("errors");
				if ($resupload < 0)	// Unknown error
				{
					$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
				}
				else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
				{
					$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
				}
				else	// Known error
				{
					$mesg = '<div class="error">'.$langs->trans($resupload).'</div>';
				}
			}
		}
	}
	else
	{
		$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
	}
}


/*
 * View
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('','ConcatPdf',$linktohelp);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ConcatPdfSetup"),$linkback,'setup');
print '<br>';

clearstatcache();

dol_htmloutput_mesg($mesg,$mesgs);

print $langs->trans("ConcatPDfTakeFileFrom").'<br>';
$langs->load("propal"); $langs->load("orders"); $langs->load("bills");
foreach ($modules as $module)
{
	$outputdir=$conf->concatpdf->dir_output.'/'.$module;
	print '* '.$langs->trans("ConcatPDfTakeFileFrom2",$langs->transnoentitiesnoconv(ucfirst($module)),$outputdir).'<br>';
}
print '<br><br>';

//print $langs->trans("ConcatPDfPutFileManually");
//print '<br><br><br>';

$select_module=$form->selectarray('module', $modules,'',0,0,1,'',1);
$formfile->form_attach_new_file($_SERVER['PHP_SELF'],'',0,0,1,50,'',$select_module);

print '<br><br>';

foreach ($modules as $module)
{
	$outputdir=$conf->concatpdf->dir_output.'/'.$module;
	$listoffiles=dol_dir_list($outputdir,'files');
	if (count($listoffiles)) print $formfile->showdocuments('concatpdf',$module,$outputdir,$_SERVER["PHP_SELF"],0,$user->admin,'',0,0,0,0,0,'',$langs->trans("PathDirectory").' '.$outputdir);
	else
	{
		print '<div class="titre">'.$langs->trans("PathDirectory").' '.$outputdir.' :</div>';
		print $langs->trans("NoPDFFileFound").'<br>';
	}
	
	print '<br><br>';
}

llxFooter();

if (is_object($db)) $db->close();
?>
