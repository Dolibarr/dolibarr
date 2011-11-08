<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/facture/document.php
 *	\ingroup    facture
 *	\brief      Page for attached files on invoices
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/core/class/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/invoice.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");

$langs->load('propal');
$langs->load('compta');
$langs->load('other');
$langs->load("bills");


$action		= GETPOST('action');
$confirm	= GETPOST('confirm');
$id			= GETPOST('facid');
$ref		= GETPOST('ref');

// Security check
if ($user->societe_id)
{
	$action='';
	$socid = $user->societe_id;
}
$result=restrictedArea($user,'facture',$id,'');

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

$object = new Facture($db);


/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if ($object->fetch($id))
	{
		$object->fetch_thirdparty();

		$upload_dir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($object->ref);

		if (create_exdir($upload_dir) >= 0)
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
}

// Delete
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
	if ($object->fetch($id))
	{
		$object->fetch_thirdparty();

		$upload_dir = $conf->facture->dir_output . "/" . dol_sanitizeFileName($object->ref);
		$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
		dol_delete_file($file,0,0,0,'FILE_DELETE',$object);
		$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
	}
}

/*
 * View
 */

llxHeader();

$form = new Form($db);

$id = $_GET['facid']?$_GET['facid']:$_GET['id'];
$ref= $_GET['ref'];
if ($id > 0 || ! empty($ref))
{
	if ($object->fetch($id,$ref) > 0)
	{
		$object->fetch_thirdparty();

		$upload_dir = $conf->facture->dir_output.'/'.dol_sanitizeFileName($object->ref);

		$head = facture_prepare_head($object);
		dol_fiche_head($head, 'documents', $langs->trans('InvoiceCustomer'), 0, 'bill');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}



		print '<table class="border"width="100%">';

		// Ref
		print '<tr><td width="30%">'.$langs->trans('Ref').'</td>';
		print '<td colspan="3">';
		$morehtmlref='';
		$discount=new DiscountAbsolute($db);
		$result=$discount->fetch(0,$object->id);
		if ($result > 0)
		{
			$morehtmlref=' ('.$langs->trans("CreditNoteConvertedIntoDiscount",$discount->getNomUrl(1,'discount')).')';
		}
		if ($result < 0)
		{
			dol_print_error('',$discount->error);
		}
		print $form->showrefnav($object,'ref','',1,'facnumber','ref',$morehtmlref);
		print '</td></tr>';

		// Company
		print '<tr><td>'.$langs->trans('Company').'</td><td colspan="3">'.$object->thirdparty->getNomUrl(1).'</td></tr>';

		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print "</table>\n";
		print "</div>\n";

		dol_htmloutput_mesg($mesg,$mesgs);

    	/*
		 * Confirmation suppression fichier
		 */
		if ($action == 'delete')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?facid='.$id.'&urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
			if ($ret == 'html') print '<br>';
		}


		// Affiche formulaire upload
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/compta/facture/document.php?facid='.$object->id,'',0,0,$user->rights->facture->creer);


		// List of document
		$param='&facid='.$object->id;
		$formfile->list_of_documents($filearray,$object,'facture',$param);

	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter();
?>
