<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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

$objectid = GETPOST('id', 'int');
$action=GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
if ($user->societe_id > 0)
{
	unset($_GET["action"]);
	$action='';
}
$result = restrictedArea($user, 'agenda', $objectid, 'actioncomm&societe', 'myactions&allactions', 'fk_soc', 'id');

$object = new ActionComm($db);

if ($objectid > 0)
{
	$ret = $object->fetch($objectid);
	if ($ret > 0) {
		$company=new Societe($db);
		$company->fetch($object->societe->id);
		$object->societe=$company; // For backward compatibility
		$object->thirdparty=$company;
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

$upload_dir = $conf->agenda->dir_output.'/'.dol_sanitizeFileName($object->ref);
$modulepart='contract';


/*
 * Actions
 */
include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);


if ($object->id > 0)
{
	$author=new User($db);
	$author->fetch($object->author->id);
	$object->author=$author;

	if ($object->contact->id) $object->fetch_contact($object->contact->id);

	$head=actions_prepare_head($object);
	dol_fiche_head($head, 'documents', $langs->trans("Action"),0,'action');

	// Affichage fiche action en mode visu
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/index.php">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '');
	print '</td></tr>';

	// Type
	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
	{
		print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$object->type.'</td></tr>';
	}

	// Title
	print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$object->label.'</td></tr>';

	// Full day event
	print '<tr><td>'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($object->fulldayevent).'</td></tr>';

	// Date start
	print '<tr><td width="30%">'.$langs->trans("DateActionStart").'</td><td colspan="2">';
	if (! $object->fulldayevent) print dol_print_date($object->datep,'dayhour');
	else print dol_print_date($object->datep,'day');
	if ($object->percentage == 0 && $object->datep && $object->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
	print '</td>';
	print '<td rowspan="4" align="center" valign="middle" width="180">'."\n";
	print '<form name="listactionsfiltermonth" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="show_month">';
	print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
	print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
	print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
	//print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
	print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" style="width: 120px" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'">';
	print '</form>'."\n";
	print '<form name="listactionsfilterweek" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="show_week">';
	print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
	print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
	print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
	//print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
	print img_picto($langs->trans("ViewCal"),'object_calendarweek').' <input type="submit" style="width: 120px" class="button" name="viewweek" value="'.$langs->trans("ViewWeek").'">';
	print '</form>'."\n";
	print '<form name="listactionsfilterday" action="'.DOL_URL_ROOT.'/comm/action/index.php" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="show_day">';
	print '<input type="hidden" name="year" value="'.dol_print_date($object->datep,'%Y').'">';
	print '<input type="hidden" name="month" value="'.dol_print_date($object->datep,'%m').'">';
	print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
	//print '<input type="hidden" name="day" value="'.dol_print_date($object->datep,'%d').'">';
	print img_picto($langs->trans("ViewCal"),'object_calendarday').' <input type="submit" style="width: 120px" class="button" name="viewday" value="'.$langs->trans("ViewDay").'">';
	print '</form>'."\n";
	print '</td>';
	print '</tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="2">';
	if (! $object->fulldayevent) print dol_print_date($object->datef,'dayhour');
	else print dol_print_date($object->datef,'day');
	if ($object->percentage > 0 && $object->percentage < 100 && $object->datef && $object->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
	print '</td></tr>';

	// Status
	print '<tr><td class="nowrap">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="2">';
	print $object->getLibStatut(4);
	print '</td></tr>';

	// Location
	print '<tr><td>'.$langs->trans("Location").'</td><td colspan="2">'.$object->location.'</td></tr>';


	print '</table><br><br><table class="border" width="100%">';


	// Third party - Contact
	print '<tr><td width="30%">'.$langs->trans("ActionOnCompany").'</td><td>'.($object->societe->id?$object->societe->getNomUrl(1):$langs->trans("None"));
	if ($object->societe->id && $object->type_code == 'AC_TEL')
	{
		if ($object->societe->fetch($object->societe->id))
		{
			print "<br>".dol_print_phone($object->societe->phone);
		}
	}
	print '</td>';
	print '<td>'.$langs->trans("Contact").'</td>';
	print '<td>';
	if ($object->contact->id > 0)
	{
		print $object->contact->getNomUrl(1);
		if ($object->contact->id && $object->type_code == 'AC_TEL')
		{
			if ($object->contact->fetch($object->contact->id))
			{
				print "<br>".dol_print_phone($object->contact->phone_pro);
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
		if ($object->fk_project)
		{
			$project=new Project($db);
			$project->fetch($object->fk_project);
			print $project->getNomUrl(1);
		}
		print '</td></tr>';
	}

	// Priority
	print '<tr><td class="nowrap">'.$langs->trans("Priority").'</td><td colspan="3">';
	print ($object->priority?$object->priority:'');
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

	$modulepart = 'actions';
	$permission = $user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create;
	$param = '&id=' . $object->id;
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	print $langs->trans("ErrorUnknown");
}

$db->close();

llxFooter();
?>
