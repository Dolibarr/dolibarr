<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur   <eldy@users.sourceforge.net>
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

$id = GETPOST('id', 'int');
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
$result = restrictedArea($user, 'agenda', $id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');

$object = new ActionComm($db);

if ($id > 0)
{
	$ret = $object->fetch($id);
	$object->fetch_thirdparty();
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
include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);


if ($object->id > 0)
{
	$result1=$object->fetch($id);
	$result2=$object->fetch_thirdparty();
	$result3=$object->fetch_contact();
	$result4=$object->fetch_userassigned();
	$result5=$object->fetch_optionals($id,$extralabels);

	if ($result1 < 0 || $result2 < 0 || $result3 < 0 || $result4 < 0 || $result5 < 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}

	if ($object->authorid > 0)		{ $tmpuser=new User($db); $res=$tmpuser->fetch($object->authorid); $object->author=$tmpuser; }
	if ($object->usermodid > 0)		{ $tmpuser=new User($db); $res=$tmpuser->fetch($object->usermodid); $object->usermod=$tmpuser; }

	$author=new User($db);
	$author->fetch($object->author->id);
	$object->author=$author;


	$head=actions_prepare_head($object);

	$now=dol_now();
	$delay_warning=$conf->global->MAIN_DELAY_ACTIONS_TODO*24*60*60;

	dol_fiche_head($head, 'documents', $langs->trans("Action"),0,'action');

	$linkback = img_picto($langs->trans("BackToList"),'object_list','class="hideonsmartphone pictoactionview"');
	$linkback.= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php">'.$langs->trans("BackToList").'</a>';

	// Link to other agenda views
	$out='';
	$out.=img_picto($langs->trans("ViewPerUser"),'object_calendarperuser','class="hideonsmartphone pictoactionview"');
	$out.='<a href="'.DOL_URL_ROOT.'/comm/action/peruser.php?action=show_peruser&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewPerUser").'</a>';
	$out.='<br>';
	$out.=img_picto($langs->trans("ViewCal"),'object_calendar','class="hideonsmartphone pictoactionview"');
	$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_month&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewCal").'</a>';
	$out.=img_picto($langs->trans("ViewWeek"),'object_calendarweek','class="hideonsmartphone pictoactionview"');
	$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewWeek").'</a>';
	$out.=img_picto($langs->trans("ViewDay"),'object_calendarday','class="hideonsmartphone pictoactionview"');
	$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($object->datep,'%Y').'&month='.dol_print_date($object->datep,'%m').'&day='.dol_print_date($object->datep,'%d').'">'.$langs->trans("ViewDay").'</a>';
	
	$linkback.=$out;

	dol_banner_tab($object, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '');
	
	print '<div class="underbanner clearboth"></div>';
	
	// Affichage fiche action en mode visu
	print '<table class="border" width="100%">';

	// Ref
	/*print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
	print $form->showrefnav($object, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '');
	print '</td></tr>';*/

	// Type
	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
	{
		print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td colspan="3">'.$object->type.'</td></tr>';
	}

	// Title
	//print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$object->label.'</td></tr>';

	// Full day event
	print '<tr><td class="titlefield">'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($object->fulldayevent, 3).'</td></tr>';

	// Date start
	print '<tr><td>'.$langs->trans("DateActionStart").'</td><td colspan="3">';
	if (! $object->fulldayevent) print dol_print_date($object->datep,'dayhour');
	else print dol_print_date($object->datep,'day');
	if ($object->percentage == 0 && $object->datep && $object->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
	print '</td>';
	print '</tr>';

	// Date end
	print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
	if (! $object->fulldayevent) print dol_print_date($object->datef,'dayhour');
	else print dol_print_date($object->datef,'day');
	if ($object->percentage > 0 && $object->percentage < 100 && $object->datef && $object->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
	print '</td></tr>';

	// Status
	/*print '<tr><td class="nowrap">'.$langs->trans("Status").' / '.$langs->trans("Percentage").'</td><td colspan="2">';
	print $object->getLibStatut(4);
	print '</td></tr>';*/

	// Location
	if (empty($conf->global->AGENDA_DISABLE_LOCATION))
	{
		print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$object->location.'</td></tr>';
	}

	// Assigned to
	print '<tr><td class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
	$listofuserid=array();
	if (empty($donotclearsession))
	{
		if ($object->userownerid > 0) $listofuserid[$object->userownerid]=array('id'=>$object->userownerid,'transparency'=>$object->transparency);	// Owner first
		if (! empty($object->userassigned))	// Now concat assigned users
		{
			// Restore array with key with same value than param 'id'
			$tmplist1=$object->userassigned; $tmplist2=array();
			foreach($tmplist1 as $key => $val)
			{
				if ($val['id'] && $val['id'] != $object->userownerid) $listofuserid[$val['id']]=$val;
			}
		}
		$_SESSION['assignedtouser']=json_encode($listofuserid);
	}
	else
	{
		if (!empty($_SESSION['assignedtouser']))
		{
			$listofuserid=json_decode($_SESSION['assignedtouser'], true);
		}
	}
	print '<div class="assignedtouser">';
	print $form->select_dolusers_forevent('view', 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	print '</div>';
	if (in_array($user->id,array_keys($listofuserid))) 
	{
		print '<div class="myavailability">';
		print $langs->trans("MyAvailability").': '.(($object->userassigned[$user->id]['transparency'] > 0)?$langs->trans("Busy"):$langs->trans("Available"));	// We show nothing if event is assigned to nobody
		print '</div>';
	}
	print '	</td></tr>';

	print '</table>';
	
	print '<br><br>';
	
	print '<table class="border" width="100%">';


	// Third party - Contact
	print '<tr><td class="titlefield">'.$langs->trans("ActionOnCompany").'</td><td>'.($object->thirdparty->id?$object->thirdparty->getNomUrl(1):$langs->trans("None"));
	if (is_object($object->thirdparty) && $object->thirdparty->id > 0 && $object->type_code == 'AC_TEL')
	{
		if ($object->thirdparty->fetch($object->thirdparty->id))
		{
			print "<br>".dol_print_phone($object->thirdparty->phone);
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
		print '<tr><td class="tdtop">'.$langs->trans("Project").'</td><td colspan="3">';
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

	// Other attributes
	$parameters=array('colspan'=>' colspan="3"', 'colspanvalue'=>'3', 'id'=>$object->id);
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit');
	}


	print '</table>';

	print '<br><br>';

	print '<table class="border" width="100%">';

	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	print '<tr><td class="titlefield" class="nowrap">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	dol_fiche_end();


	$modulepart = 'actions';
	$permission = $user->rights->agenda->myactions->create||$user->rights->agenda->allactions->create;
	$param = '&id=' . $object->id;
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
