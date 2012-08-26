<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/projet/ganttview.php
 *	\ingroup    projet
 *	\brief      Gantt diagramm of a project
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);
if ($ref)
{
    $object->fetch(0,$ref);
    $id=$object->id;
}

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
$result = restrictedArea($user, 'projet', $id);

$langs->load("users");
$langs->load("projects");


/*
 * Actions
 */

// None


/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$userstatic=new User($db);
$companystatic=new Societe($db);
$task = new Task($db);
$object = new Project($db);

$arrayofcss=array('/includes/jsgantt/jsgantt.css');

if (! empty($conf->use_javascript_ajax))
{
	$arrayofjs=array(
	'/includes/jsgantt/jsgantt.js',
	'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
	);
}

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$langs->trans("Tasks"),$help_url,'',0,0,$arrayofjs,$arrayofcss);

if ($id > 0 || ! empty($ref))
{
	$object->fetch($id,$ref);
	if ($object->societe->id > 0)  $result=$object->societe->fetch($object->societe->id);

	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite  = $object->restrictedProjectArea($user,'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;


    $tab='gantt';

    $head=project_prepare_head($object);
    dol_fiche_head($head, $tab, $langs->trans("Project"),0,($object->public?'projectpub':'project'));

    $param=($_REQUEST["mode"]=='mine'?'&mode=mine':'');

    print '<table class="border" width="100%">';

    $linkback = '<a href="'.DOL_URL_ROOT.'/projet/liste.php">'.$langs->trans("BackToList").'</a>';

    // Ref
    print '<tr><td width="30%">';
    print $langs->trans("Ref");
    print '</td><td>';
    // Define a complementary filter for search of next/prev ref.
    $objectsListId = $object->getProjectsAuthorizedForUser($user,$mine,1);
    $object->next_prev_filter=" rowid in (".$objectsListId.")";
    print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '', $param);
    print '</td></tr>';

    print '<tr><td>'.$langs->trans("Label").'</td><td>'.$object->title.'</td></tr>';

    print '<tr><td>'.$langs->trans("Company").'</td><td>';
    if (! empty($object->societe->id)) print $object->societe->getNomUrl(1);
    else print '&nbsp;';
    print '</td>';
    print '</tr>';

    // Visibility
    print '<tr><td>'.$langs->trans("Visibility").'</td><td>';
    if ($object->public) print $langs->trans('SharedProject');
    else print $langs->trans('PrivateProject');
    print '</td></tr>';

    // Statut
    print '<tr><td>'.$langs->trans("Status").'</td><td>'.$object->getLibStatut(4).'</td></tr>';

    print '</table>';

    print '</div>';
}


/*
 * Actions
 */
print '<div class="tabsAction">';

if ($user->rights->projet->all->creer || $user->rights->projet->creer)
{
    if ($object->public || $userWrite > 0)
    {
        print '<a class="butAction" href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id.'&action=create'.$param.'&tab=gantt&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.$object->id).'">'.$langs->trans('AddTask').'</a>';
    }
    else
    {
        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotOwnerOfProject").'">'.$langs->trans('AddTask').'</a>';
    }
}
else
{
    print '<a class="butActionRefused" href="#" title="'.$langs->trans("NoPermission").'">'.$langs->trans('AddTask').'</a>';
}

print '</div>';

print '<br>';


// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task to user
// can have a parent that is not affected to him).
$tasksarray=$task->getTasksArray(0, 0, $object->id, $socid, 0);
// We load also tasks limited to a particular user
//$tasksrole=($_REQUEST["mode"]=='mine' ? $task->getUserRolesForProjectsOrTasks(0,$user,$object->id,0) : '');
//var_dump($tasksarray);
//var_dump($tasksrole);


if (count($tasksarray)>0)
{

	// Show Gant diagram from $taskarray using JSGantt

	$dateformat=$langs->trans("FormatDateShort");
	$dateformat=strtolower($langs->trans("FormatDateShortJava"));
	$array_contacts=array();
	$tasks=array();
	$project_dependencies=array();
	$taskcursor=0;
	foreach($tasksarray as $key => $val)
	{
		$task->fetch($val->id);
		$tasks[$taskcursor]['task_id']=$val->id;
		$tasks[$taskcursor]['task_parent']=$val->fk_parent;
		$tasks[$taskcursor]['task_is_group']=0;
		$tasks[$taskcursor]['task_milestone']=0;
		$tasks[$taskcursor]['task_percent_complete']=$val->progress;
		//$tasks[$taskcursor]['task_name']=$task->getNomUrl(1);
		$tasks[$taskcursor]['task_name']=$val->label;
		$tasks[$taskcursor]['task_start_date']=$val->date_start;
		$tasks[$taskcursor]['task_end_date']=$val->date_end;
		$tasks[$taskcursor]['task_color']='b4d1ea';
		$idofusers=$task->getListContactId('internal');
		$idofthirdparty=$task->getListContactId('external');
		$s='';
		if (count($idofusers)>0)
		{
			$s.=$langs->trans("Internals").': ';
			$i=0;
			foreach($idofusers as $key => $valid)
			{
				$userstatic->fetch($valid);
				if ($i) $s.=',';
				$s.=$userstatic->login;
				$i++;
			}
		}
		if (count($idofusers)>0 && (count($idofthirdparty)>0)) $s.=' - ';
		if (count($idofthirdparty)>0)
		{
			if ($s) $s.=' - ';
			$s.=$langs->trans("Externals").': ';
			$i=0;
			foreach($idofthirdparty as $key => $valid)
			{
				$companystatic->fetch($valid);
				if ($i) $s.=',';
				$s.=$companystatic->name;
				$i++;
			}
		}
		if ($s) $tasks[$taskcursor]['task_resources']='<a href="'.DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$val->id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$langs->trans("List").'</a>';
		//print "xxx".$val->id.$tasks[$taskcursor]['task_resources'];
		$taskcursor++;
	}
	//var_dump($tasks);

	print "\n";

	if (! empty($conf->use_javascript_ajax))
	{
	    //var_dump($_SESSION);
		print '<div id="tabs" class="ganttcontainer" style="border: 1px solid #ACACAC;">'."\n";
		include_once DOL_DOCUMENT_ROOT.'/projet/ganttchart.php';
		print '</div>'."\n";
	}
	else
	{
		$langs->load("admin");
		print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
	}
}
else
{
	print $langs->trans("NoTasks");
}


llxFooter();

$db->close();
?>
