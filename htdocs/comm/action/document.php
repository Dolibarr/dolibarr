<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
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
 *       \file       htdocs/comm/action/document.php
 *       \ingroup    agenda
 *       \brief      Page of documents linked to actions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("other");
$langs->load("bills");

$objectid = GETPOST('id','int');
$action=GETPOST('action','alpha');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
if ($user->societe_id > 0)
{
	unset($_GET["action"]);
	$action='';
}
$result = restrictedArea($user, 'agenda', $objectid, 'actioncomm&societe', 'myactions&allactions', 'fk_soc', 'id');

$act = new ActionComm($db);

if ($objectid > 0)
{
	$ret = $act->fetch($objectid);
	if ($ret > 0) {
		$company=new Societe($db);
		$company->fetch($act->societe->id);
		$act->societe=$company; // For backward compatibility
		$act->thirdparty=$company;
	}
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
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);
	dol_add_file_process($upload_dir,0,1,'userfile');
}

/*
 * Efface fichier
 */
if ($action == 'delete')
{
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);
	$file = $upload_dir . '/' . $_GET['urlfile'];	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
	$ret=dol_delete_file($file,0,0,0,$act);
	if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	$action='';
}


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);


if ($act->id > 0)
{
	$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($objectid);

	$author=new User($db);
	$author->fetch($act->author->id);
	$act->author=$author;

	if ($act->contact->id) $act->fetch_contact($act->contact->id);

	$head=actions_prepare_head($act);
	dol_fiche_head($head, 'documents', $langs->trans("Action"),0,'action');

	// Affichage fiche action en mode visu
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/index.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($act, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '');
	print '</td></tr>';

	// Type
	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
	{
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';
	}

	// Title
	print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';

	// Full day event
	print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($act->fulldayevent).'</td></tr>';

	// Date start
	print '<tr><td width="30%">'.$langs->trans("DateActionStart").'</td><td colspan="2">';
	if (! $act->fulldayevent) print dol_print_date($act->datep,'dayhour');
	else print dol_print_date($act->datep,'day');
	if ($act->percentage == 0 && $act->datep && $act->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
	print '</td>';
	print '<td rowspan="4" align="center" valign="middle" width="180">'."\n";
	print '<form name="listactionsfiltermonth" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="show_month">';
	print '<input type="hidden" name="year" value="'.dol_print_date($act->datep,'%Y').'">';
	print '<input type="hidden" name="month" value="'.dol_print_date($act->datep,'%m').'">';
	print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
	//print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
	print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" style="width: 120px" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'">';
	print '</form>'."\n";
	print '<form name="listactionsfilterweek" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="show_week">';
	print '<input type="hidden" name="year" value="'.dol_print_date($act->datep,'%Y').'">';
	print '<input type="hidden" name="month" value="'.dol_print_date($act->datep,'%m').'">';
	print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
	//print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
	print img_picto($langs->trans("ViewCal"),'object_calendarweek').' <input type="submit" style="width: 120px" class="button" name="viewweek" value="'.$langs->trans("ViewWeek").'">';
	print '</form>'."\n";
	print '<form name="listactionsfilterday" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="show_day">';
	print '<input type="hidden" name="year" value="'.dol_print_date($act->datep,'%Y').'">';
	print '<input type="hidden" name="month" value="'.dol_print_date($act->datep,'%m').'">';
	print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
	//print '<input type="hidden" name="day" value="'.dol_print_date($act->datep,'%d').'">';
	print img_picto($langs->trans("ViewCal"),'object_calendarday').' <input type="submit" style="width: 120px" class="button" name="viewday" value="'.$langs->trans("ViewDay").'">';
	print '</form>'."\n";
	print '</td>';
	print '</tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="2">';
	if (! $act->fulldayevent) print dol_print_date($act->datef,'dayhour');
	else print dol_print_date($act->datef,'day');
	if ($act->percentage > 0 && $act->percentage < 100 && $act->datef && $act->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
	print '</td></tr>';

	// Status
	print '<tr><td nowrap>'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="2">';
	print $act->getLibStatut(4);
	print '</td></tr>';

	// Location
	print '<tr><td>'.$langs->trans("Location").'</td><td colspan="2">'.$act->location.'</td></tr>';


	print '</table><br><br><table class="border" width="100%">';


	// Third party - Contact
	print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td><td>'.($act->societe->id?$act->societe->getNomUrl(1):$langs->trans("None"));
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
	if (! empty($conf->projet->enabled))
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

	// Priority
	print '<tr><td nowrap>'.$langs->trans("Priority").'</td><td colspan="3">';
	print ($act->priority?$act->priority:'');
	print '</td></tr>';


	print '</table><br><br><table class="border" width="100%">';

	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','\.meta$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	print '<tr><td width="30%" nowrap>'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
	print '</table>';

	print '</div>';


	// Affiche formulaire upload
	$formfile=new FormFile($db);
	$formfile->form_attach_new_file(DOL_URL_ROOT.'/comm/action/document.php?id='.$act->id,'',0,0,($user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create),50,$act);


	// List of document
	$param='&id='.$act->id;
	$formfile->list_of_documents($filearray,$act,'actions',$param,0,'',$user->rights->agenda->myactions->create);
}
else
{
	print $langs->trans("UnkownError");
}

$db->close();

llxFooter();
?>
