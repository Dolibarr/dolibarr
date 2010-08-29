<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/adherents/document.php
 *  \brief      Tab for documents linked to third party
 *  \ingroup    societe
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent_type.class.php");

$langs->load("companies");
$langs->load('other');

$mesg = "";

// Security check
$id = GETPOST('id');
if ($user->societe_id > 0)
{
	$id = $user->societe_id;
}
//$result = restrictedArea($user, 'societe', $id);

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

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$upload_dir = $conf->adherent->dir_output . "/" . get_exdir($id,2,0,1);



/*
 * Actions
 */

// Envoie fichier
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

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

// Suppression fichier
if ($_REQUEST['action'] == 'confirm_deletefile' && $_REQUEST['confirm'] == 'yes')
{
	$file = $upload_dir . "/" . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
	$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
}


/*
 * View
 */

$form = new Form($db);
$member=new Adherent($db);
$membert=new AdherentType($db);

llxHeader();

if ($id > 0)
{
    $result=$member->fetch($id);
    $result=$membert->fetch($member->typeid);
	if ($result > 0)
	{
		/*
		 * Affichage onglets
		 */
		if ($conf->notification->enabled) $langs->load("mails");
		$head = member_prepare_head($member);

		$html=new Form($db);

		dol_fiche_head($head, 'document', $langs->trans("Member"),0,'user');


		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_ASC:SORT_DESC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<table class="border"width="100%">';

        // Ref
        print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
        print '<td class="valeur">';
        print $html->showrefnav($member,'rowid');
        print '</td></tr>';

        // Nom
        print '<tr><td>'.$langs->trans("Lastname").'</td><td class="valeur">'.$member->nom.'&nbsp;</td>';
        print '</tr>';

        // Prenom
        print '<tr><td>'.$langs->trans("Firstname").'</td><td class="valeur">'.$member->prenom.'&nbsp;</td>';
        print '</tr>';

        // Login
        print '<tr><td>'.$langs->trans("Login").'</td><td class="valeur">'.$member->login.'&nbsp;</td></tr>';

        // Type
        print '<tr><td>'.$langs->trans("Type").'</td><td class="valeur">'.$membert->getNomUrl(1)."</td></tr>\n";

        // Status
        print '<tr><td>'.$langs->trans("Status").'</td><td class="valeur">'.$member->getLibStatut(4).'</td></tr>';

    	// Nbre fichiers
		print '<tr><td>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';

		//Total taille
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

		print '</table>';

		print '</div>';

		if ($mesg) { print "$mesg<br>"; }

		/*
		 * Confirmation suppression fichier
		 */
		if ($_GET['action'] == 'delete')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$_GET["id"].'&urlfile='.urldecode($_GET["urlfile"]), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
			if ($ret == 'html') print '<br>';
		}


		// Affiche formulaire upload
		$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/adherents/document.php?id='.$id,'',0,0,$user->rights->adherent->creer);


		// List of document
		$param='&socid='.$societe->id;
		$formfile->list_of_documents($filearray,$member,'member',$param);

		print "<br><br>";
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
    $langs->load("errors");
	print $langs->trans("ErrorRecordNotFound");
}

$db->close();


llxFooter('$Date$ - $Revision$');

?>
