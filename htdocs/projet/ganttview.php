<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/projet/ganttview.php
 *	\ingroup    projet
 *	\brief      Gantt diagramm of a project
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$id=GETPOST('id','intcomma');
$ref=GETPOST('ref','alpha');

$mode = GETPOST('mode', 'alpha');
$mine = ($mode == 'mine' ? 1 : 0);
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once
if(! empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT) && method_exists($object, 'fetchComments') && empty($object->comments)) $object->fetchComments();

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

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
$contactstatic=new Contact($db);
$task = new Task($db);

$arrayofcss=array('/includes/jsgantt/jsgantt.css');

if (! empty($conf->use_javascript_ajax))
{
	$arrayofjs=array(
	'/includes/jsgantt/jsgantt.js',
	'/projet/jsgantt_language.js.php?lang='.$langs->defaultlang
	);
}

$title=$langs->trans("Project").' - '.$langs->trans("Gantt").($object->ref?' - '.$object->ref.' '.$object->name:'');
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=($object->ref?$object->ref.' '.$object->name.' - ':'').$langs->trans("Gantt");
$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("",$title,$help_url,'',0,0,$arrayofjs,$arrayofcss);

if (($id > 0 && is_numeric($id)) || ! empty($ref))
{
	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite  = $object->restrictedProjectArea($user,'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

    $tab='gantt';

    $head=project_prepare_head($object);
    dol_fiche_head($head, $tab, $langs->trans("Project"), -1, ($object->public?'projectpub':'project'));

    $param=($mode=='mine'?'&mode=mine':'');



    // Project card

    $linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    $morehtmlref='<div class="refidno">';
    // Title
    $morehtmlref.=$object->title;
    // Thirdparty
    if ($object->thirdparty->id > 0)
    {
        $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1, 'project');
    }
    $morehtmlref.='</div>';

    // Define a complementary filter for search of next/prev ref.
    if (! $user->rights->projet->all->lire)
    {
        $objectsListId = $object->getProjectsAuthorizedForUser($user,0,0);
        $object->next_prev_filter=" rowid in (".(count($objectsListId)?join(',',array_keys($objectsListId)):'0').")";
    }

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border" width="100%">';

    // Visibility
    print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
    if ($object->public) print $langs->trans('SharedProject');
    else print $langs->trans('PrivateProject');
    print '</td></tr>';

    // Date start - end
    print '<tr><td>'.$langs->trans("DateStart").' - '.$langs->trans("DateEnd").'</td><td>';
	$start = dol_print_date($object->date_start,'day');
	print ($start?$start:'?');
	$end = dol_print_date($object->date_end,'day');
	print ' - ';
	print ($end?$end:'?');
	if ($object->hasDelay()) print img_warning("Late");
    print '</td></tr>';

    // Budget
    print '<tr><td>'.$langs->trans("Budget").'</td><td>';
    if (strcmp($object->budget_amount, '')) print price($object->budget_amount,'',$langs,1,0,0,$conf->currency);
    print '</td></tr>';

    // Other attributes
    $cols = 2;
    include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

    print '</table>';

    print '</div>';
    print '<div class="fichehalfright">';
    print '<div class="ficheaddleft">';
    print '<div class="underbanner clearboth"></div>';

    print '<table class="border" width="100%">';

    // Description
    print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
    print nl2br($object->description);
    print '</td></tr>';

    // Categories
    if($conf->categorie->enabled) {
        print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td>';
        print $form->showCategories($object->id,'project',1);
        print "</td></tr>";
    }

    print '</table>';

    print '</div>';
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

    dol_fiche_end();
}


/*
 * Buttons actions
 */

if ($id > 0 && is_numeric($id))
{

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
	    print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('AddTask').'</a>';
	}

	print '</div>';

	print '<br>';
}
else
{
	print_fiche_titre($langs->trans("GanttView"));
}


// Get list of tasks in tasksarray and taskarrayfiltered
// We need all tasks (even not limited to a user because a task to user
// can have a parent that is not affected to him).
$tasksarray=$task->getTasksArray(0, 0, ($object->id ? $object->id : $id), $socid, 0);
// We load also tasks limited to a particular user
//$tasksrole=($_REQUEST["mode"]=='mine' ? $task->getUserRolesForProjectsOrTasks(0,$user,$object->id,0) : '');
//var_dump($tasksarray);
//var_dump($tasksrole);


if (count($tasksarray)>0)
{

	// Show Gant diagram from $taskarray using JSGantt

	$dateformat=$langs->trans("FormatDateShortJQuery");			// Used by include ganttchart.inc.php later
	$datehourformat=$langs->trans("FormatDateShortJQuery").' '.$langs->trans("FormatHourShortJQuery");	// Used by include ganttchart.inc.php later
	$array_contacts=array();
	$tasks=array();
	$task_dependencies=array();
	$taskcursor=0;
	foreach($tasksarray as $key => $val)	// Task array are sorted by "project, position, dateo"
	{
		$task->fetch($val->id, '');

		$idparent = ($val->fk_parent ? $val->fk_parent : '-'.$val->fk_project);	// If start with -, id is a project id

		$tasks[$taskcursor]['task_id']=$val->id;
		$tasks[$taskcursor]['task_alternate_id']=($taskcursor+1);		// An id that has same order than position (requird by ganttchart)
		$tasks[$taskcursor]['task_project_id']=$val->fk_project;
		$tasks[$taskcursor]['task_parent']=$idparent;

		$tasks[$taskcursor]['task_is_group'] = 0;
        $tasks[$taskcursor]['task_css'] = 'gtaskblue';
        $tasks[$taskcursor]['task_position'] = $val->rang;
        $tasks[$taskcursor]['task_planned_workload'] = $val->planned_workload;

        if ($val->fk_parent != 0 && $task->hasChildren()> 0){
            $tasks[$taskcursor]['task_is_group']=1;
        	$tasks[$taskcursor]['task_css']='ggroupblack';
            //$tasks[$taskcursor]['task_css'] = 'gtaskblue';
        }
        elseif ($task->hasChildren()> 0) {
            $tasks[$taskcursor]['task_is_group'] = 1;
        	//$tasks[$taskcursor]['task_is_group'] = 0;
            $tasks[$taskcursor]['task_css'] = 'ggroupblack';
            //$tasks[$taskcursor]['task_css'] = 'gtaskblue';
        }
		$tasks[$taskcursor]['task_milestone']='0';
		$tasks[$taskcursor]['task_percent_complete']=$val->progress;
		//$tasks[$taskcursor]['task_name']=$task->getNomUrl(1);
		//print dol_print_date($val->date_start).dol_print_date($val->date_end).'<br>'."\n";
		$tasks[$taskcursor]['task_name']=$val->ref.' - '.$val->label;
		$tasks[$taskcursor]['task_start_date']=$val->date_start;
		$tasks[$taskcursor]['task_end_date']=$val->date_end;
		$tasks[$taskcursor]['task_color']='b4d1ea';

		$idofusers=$task->getListContactId('internal');
		$idofcontacts=$task->getListContactId('external');
  		$s='';
		if (count($idofusers)>0)
		{
			$s.=$langs->trans("Internals").': ';
			$i=0;
			foreach($idofusers as $valid)
			{
				$userstatic->fetch($valid);
				if ($i) $s.=', ';
				$s.=$userstatic->login;
				$i++;
			}
		}
		//if (count($idofusers)>0 && (count($idofcontacts)>0)) $s.=' - ';
		if (count($idofcontacts)>0)
		{
			if ($s) $s.=' - ';
			$s.=$langs->trans("Externals").': ';
			$i=0;
			$contactidfound=array();
			foreach($idofcontacts as $valid)
			{
				if (empty($contactidfound[$valid]))
				{
					$res = $contactstatic->fetch($valid);
					if ($res > 0)
					{
						if ($i) $s.=', ';
						$s.=$contactstatic->getFullName($langs);
						$contactidfound[$valid]=1;
						$i++;
					}
				}
			}
		}
		//if ($s) $tasks[$taskcursor]['task_resources']='<a href="'.DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$val->id.'&withproject=1" title="'.dol_escape_htmltag($s).'">'.$langs->trans("List").'</a>';
		/* For JSGanttImproved */
		//if ($s) $tasks[$taskcursor]['task_resources']=implode(',',$idofusers);
        $tasks[$taskcursor]['task_resources'] = $s;
		//print "xxx".$val->id.$tasks[$taskcursor]['task_resources'];
        $tasks[$taskcursor]['note']=$task->note_public;
		$taskcursor++;
	}

	// Search parent to set task_parent_alternate_id (requird by ganttchart)
	foreach($tasks as $tmpkey => $tmptask)
	{
		foreach($tasks as $tmptask2)
		{
			if ($tmptask2['task_id'] == $tmptask['task_parent'])
			{
				$tasks[$tmpkey]['task_parent_alternate_id']=$tmptask2['task_alternate_id'];
				break;
			}
		}
		if (empty($tasks[$tmpkey]['task_parent_alternate_id'])) $tasks[$tmpkey]['task_parent_alternate_id'] = $tasks[$tmpkey]['task_parent'];
	}

	print "\n";

 	if (! empty($conf->use_javascript_ajax))
	{
	    //var_dump($_SESSION);

		// How the date for data are formated (format used bu jsgantt)
	    $dateformatinput='yyyy-mm-dd';
	    // How the date for data are formated (format used by dol_print_date)
	    $dateformatinput2='standard';
	    //var_dump($dateformatinput);
  		//var_dump($dateformatinput2);

	    print '<div class="div-table-responsive">';

	    print '<div id="tabs" class="gantt" style="width: 80vw;">'."\n";
		include_once DOL_DOCUMENT_ROOT.'/projet/ganttchart.inc.php';
		print '</div>'."\n";

		print '</div>';
	}
	else
	{
		$langs->load("admin");
		print $langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled");
	}
}
else
{
	print '<div class="opacitymedium">'.$langs->trans("NoTasks").'</div>';
}


llxFooter();

$db->close();
