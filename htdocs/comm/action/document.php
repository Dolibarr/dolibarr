<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
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
 *       \file       htdocs/comm/action/document.php
 *       \ingroup    agenda
 *       \brief      Page des documents joints sur les actions
 *       \version    $Id: document.php,v 1.58 2011/07/31 22:23:21 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/agenda.lib.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/cactioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/comm/action/class/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");

if (isset($_GET["error"])) $error=$_GET["error"];
$objectid = isset($_GET["id"])?$_GET["id"]:'';

// Security check
if ($user->societe_id > 0)
{
	unset($_GET["action"]);
	$action='';
	$socid = $user->societe_id;
}

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


/*
 * Action envoie fichier
 */
if ( $_POST["sendit"] && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	// Creation repertoire si n'existe pas
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);

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

/*
 * Efface fichier
 */
if ($_GET["action"] == 'delete')
{
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);
	$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	dol_delete_file($file);
}


/*
 * View
 */

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);


if ($objectid > 0)
{
	$act = new ActionComm($db);
	if ($act->fetch($objectid))
	{
		$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);

		$company=new Societe($db);
		$company->fetch($act->societe->id);
		$act->societe=$company;

		$author=new User($db);
		$author->fetch($act->author->id);
		$act->author=$author;

		$contact=new Contact($db);
		$contact->fetch($act->contact->id);
		$act->contact=$contact;

		$head=actions_prepare_head($act);
		dol_fiche_head($head, 'documents', $langs->trans("Action"),0,'action');

		// Affichage fiche action en mode visu
		print '<table class="border" width="100%"';

		// Ref
		print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">'.$act->id.'</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';

		// Title
		print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

		// Location
		print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$act->location.'</td></tr>';

		// Societe - contact
		print '<tr><td>'.$langs->trans("Company").'</td><td>'.($act->societe->id?$act->societe->getNomUrl(1):$langs->trans("None"));
		if ($act->societe->id && $act->type_code == 'AC_TEL')
		{
			if ($act->societe->fetch($act->societe->id))
			{
				print "<br>".dol_print_phone($act->societe->tel);
			}
		}
		print '</td>';
		print '<td>'.$langs->trans("Contact").'</td>';
		print '<td>';
		if ($act->contact->id > 0)
		{
			print $act->contact->getNomUrl(1);
			if ($act->contact->id && $act->type_code == 'AC_TEL')
			{
				if ($act->contact->fetch($act->contact->id))
				{
					print "<br>".dol_print_phone($act->contact->phone_pro);
				}
			}
		}
		else
		{
			print $langs->trans("None");
		}

		print '</td></tr>';

		// Project
		if ($conf->projet->enabled)
		{
			print '<tr><td valign="top">'.$langs->trans("Project").'</td><td colspan="3">';
			if ($act->fk_project)
			{
				$project=new Project($db);
				$project->fetch($act->fk_project);
				print $project->getNomUrl(1);
			}
			print '</td></tr>';
		}

		print '</table><br><table class="border" width="100%">';

		// Construit liste des fichiers
		$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
		$totalsize=0;
		foreach($filearray as $key => $file)
		{
			$totalsize+=$file['size'];
		}


		print '<tr><td width="30%" nowrap>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.sizeof($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
		print '</table>';

		print '</div>';

		if ($mesg) { print $mesg."<br>"; }


		// Affiche formulaire upload
	   	$formfile=new FormFile($db);
		$formfile->form_attach_new_file(DOL_URL_ROOT.'/comm/action/document.php?id='.$act->id,'',0,0,($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create));


		// List of document
		$param='&id='.$act->id;
		$formfile->list_of_documents($filearray,$act,'actions',$param,0,'',$user->rights->agenda->myactions->create);
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

llxFooter('$Date: 2011/07/31 22:23:21 $ - $Revision: 1.58 $');
?>
