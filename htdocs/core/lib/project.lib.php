<?php
/* Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/core/lib/project.lib.php
 *		\brief      Functions used by project module
 *      \ingroup    project
 */
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function project_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Project");
	$head[$h][2] = 'project';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ProjectContact");
	$head[$h][2] = 'contact';
	$h++;

	if (! empty($conf->fournisseur->enabled) || ! empty($conf->propal->enabled) || ! empty($conf->commande->enabled)
	|| ! empty($conf->facture->enabled) || ! empty($conf->contrat->enabled)
	|| ! empty($conf->ficheinter->enabled) || ! empty($conf->agenda->enabled) || ! empty($conf->deplacement->enabled))
	{
		$head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ProjectReferers");
		$head[$h][2] = 'element';
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'project');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/projet/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
		$head[$h][2] = 'notes';
		$h++;
    }

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$upload_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview\.png)$'));
	$head[$h][0] = DOL_URL_ROOT.'/projet/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if($nbFiles > 0) $head[$h][1].= ' <span class="badge">'.$nbFiles.'</span>';
	$head[$h][2] = 'document';
	$h++;

	// Then tab for sub level of projet, i mean tasks
	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Tasks");
	$head[$h][2] = 'tasks';
	$h++;

	/* Now this is a filter in the Task tab.
	 $head[$h][0] = DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id.'&mode=mine';
	$head[$h][1] = $langs->trans("MyTasks");
	$head[$h][2] = 'mytasks';
	$h++;
	*/

	$head[$h][0] = DOL_URL_ROOT.'/projet/ganttview.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Gantt");
	$head[$h][2] = 'gantt';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'project','remove');

	return $head;
}


/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function task_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/task.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'task_task';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
	$head[$h][1] = $langs->trans("TaskRessourceLinks");
	$head[$h][2] = 'task_contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
	$head[$h][1] = $langs->trans("TimeSpent");
	$head[$h][2] = 'task_time';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'task');

	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
    	$nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
		if(!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/note.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
		$head[$h][2] = 'task_notes';
		$h++;
    }

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/document.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');;
	$filesdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->project->ref) . '/' .dol_sanitizeFileName($object->ref);
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	$listoffiles=dol_dir_list($filesdir,'files',1,'','thumbs');
	$head[$h][1] = (count($listoffiles)?$langs->trans('DocumentsNb',count($listoffiles)):$langs->trans('Documents'));
	$head[$h][2] = 'task_document';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'task','remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function project_admin_prepare_head()
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT."/projet/admin/project.php";
	$head[$h][1] = $langs->trans("Projects");
	$head[$h][2] = 'project';
	$h++;

	complete_head_from_modules($conf,$langs,null,$head,$h,'project_admin');

	$head[$h][0] = DOL_URL_ROOT."/projet/admin/project_extrafields.php";
	$head[$h][1] = $langs->trans("ExtraFieldsProject");
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/admin/project_task_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFieldsProjectTask");
	$head[$h][2] = 'attributes_task';
	$h++;

	complete_head_from_modules($conf,$langs,null,$head,$h,'project_admin','remove');

	return $head;
}


/**
 * Show task lines with a particular parent
 *
 * @param	string	 	$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	int			$parent				Id of parent task to start
 * @param 	array		$lines				Array of all tasks
 * @param 	int			$level				Level of task
 * @param 	string		$var				Color
 * @param 	int			$showproject		Show project columns
 * @param	int			$taskrole			Array of roles of user for each tasks
 * @param	int			$projectsListId		List of id of project allowed to user (string separated with comma)
 * @param	int			$addordertick		Add a tick to move task
 * @return	void
 */
function projectLinesa(&$inc, $parent, &$lines, &$level, $var, $showproject, &$taskrole, $projectsListId='', $addordertick=0)
{
	global $user, $bc, $langs;
	global $projectstatic, $taskstatic;

	$lastprojectid=0;

	$projectsArrayId=explode(',',$projectsListId);

	$numlines=count($lines);

	// We declare counter as global because we want to edit them into recursive call
	global $total_projectlinesa_spent,$total_projectlinesa_planned,$total_projectlinesa_spent_if_planned;
	if ($level == 0)
	{
		$total_projectlinesa_spent=0;
		$total_projectlinesa_planned=0;
		$total_projectlinesa_spent_if_planned=0;
	}

	for ($i = 0 ; $i < $numlines ; $i++)
	{
		if ($parent == 0) $level = 0;

		// Process line
		// print "i:".$i."-".$lines[$i]->fk_project.'<br>';

		if ($lines[$i]->fk_parent == $parent)
		{
			// Show task line.
			$showline=1;
			$showlineingray=0;

			// If there is filters to use
			if (is_array($taskrole))
			{
				// If task not legitimate to show, search if a legitimate task exists later in tree
				if (! isset($taskrole[$lines[$i]->id]) && $lines[$i]->id != $lines[$i]->fk_parent)
				{
					// So search if task has a subtask legitimate to show
					$foundtaskforuserdeeper=0;
					searchTaskInChild($foundtaskforuserdeeper,$lines[$i]->id,$lines,$taskrole);
					//print '$foundtaskforuserpeeper='.$foundtaskforuserdeeper.'<br>';
					if ($foundtaskforuserdeeper > 0)
					{
						$showlineingray=1;		// We will show line but in gray
					}
					else
					{
						$showline=0;			// No reason to show line
					}
				}
			}
			else
			{
				// Caller did not ask to filter on tasks of a specific user (this probably means he want also tasks of all users, into public project
				// or into all other projects if user has permission to).
				if (empty($user->rights->projet->all->lire))
				{
					// User is not allowed on this project and project is not public, so we hide line
					if (! in_array($lines[$i]->fk_project, $projectsArrayId))
					{
						// Note that having a user assigned to a task into a project user has no permission on, should not be possible
						// because assignement on task can be done only on contact of project.
						// If assignement was done and after, was removed from contact of project, then we can hide the line.
						$showline=0;
					}
				}
			}

			if ($showline)
			{
				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
				{
					$var = !$var;
					$lastprojectid=$lines[$i]->fk_project;
				}

				print '<tr '.$bc[$var].' id="row-'.$lines[$i]->id.'">'."\n";

				if ($showproject)
				{
					// Project ref
					print "<td>";
					if ($showlineingray) print '<i>';
					$projectstatic->id=$lines[$i]->fk_project;
					$projectstatic->ref=$lines[$i]->projectref;
					$projectstatic->public=$lines[$i]->public;
					if ($lines[$i]->public || in_array($lines[$i]->fk_project,$projectsArrayId)) print $projectstatic->getNomUrl(1);
					else print $projectstatic->getNomUrl(1,'nolink');
					if ($showlineingray) print '</i>';
					print "</td>";

					// Project status
					print '<td>';
					$projectstatic->statut=$lines[$i]->projectstatus;
					print $projectstatic->getLibStatut(2);
					print "</td>";
				}

				// Ref of task
				print '<td>';
				if ($showlineingray)
				{
					print '<i>'.img_object('','projecttask').' '.$lines[$i]->ref.'</i>';
				}
				else
				{
					$taskstatic->id=$lines[$i]->id;
					$taskstatic->ref=$lines[$i]->ref;
					$taskstatic->label=($taskrole[$lines[$i]->id]?$langs->trans("YourRole").': '.$taskrole[$lines[$i]->id]:'');
					print $taskstatic->getNomUrl(1,($showproject?'':'withproject'));
				}
				print '</td>';

				// Title of task
				print "<td>";
				if ($showlineingray) print '<i>';
				else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$lines[$i]->id.($showproject?'':'&withproject=1').'">';
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp; &nbsp; &nbsp;";
				}
				print $lines[$i]->label;
				if ($showlineingray) print '</i>';
				else print '</a>';
				print "</td>\n";

				// Date start
				print '<td align="center">';
				print dol_print_date($lines[$i]->date_start,'dayhour');
				print '</td>';

				// Date end
				print '<td align="center">';
				print dol_print_date($lines[$i]->date_end,'dayhour');
				print '</td>';

				// Planned Workload (in working hours)
				print '<td align="right">';
				$fullhour=convertSecondToTime($lines[$i]->planned_workload,'allhourmin');
				$workingdelay=convertSecondToTime($lines[$i]->planned_workload,'all',86400,7);	// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
				if ($lines[$i]->planned_workload)
				{
					print $fullhour;
					// TODO Add delay taking account of working hours per day and working day per week
					//if ($workingdelay != $fullhour) print '<br>('.$workingdelay.')';
				}
				//else print '--:--';
				print '</td>';

				// Progress declared
				print '<td align="right">';
				print $lines[$i]->progress.' %';
				print '</td>';

				// Time spent
				print '<td align="right">';
				if ($showlineingray) print '<i>';
				else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.($showproject?'':'&withproject=1').'">';
				if ($lines[$i]->duration) print convertSecondToTime($lines[$i]->duration,'allhourmin');
				else print '--:--';
				if ($showlineingray) print '</i>';
				else print '</a>';
				print '</td>';

				// Progress calculated (Note: ->duration is time spent)
				print '<td align="right">';
				if ($lines[$i]->planned_workload || $lines[$i]->duration)
				{
					if ($lines[$i]->planned_workload) print round(100 * $lines[$i]->duration / $lines[$i]->planned_workload,2).' %';
					else print $langs->trans('WorkloadNotDefined');
				}
				print '</td>';

				// Tick to drag and drop
				if ($addordertick)
				{
					print '<td align="center" class="tdlineupdown hideonsmartphone">&nbsp;</td>';
				}

				print "</tr>\n";

				if (! $showlineingray) $inc++;

				$level++;
				if ($lines[$i]->id) projectLinesa($inc, $lines[$i]->id, $lines, $level, $var, $showproject, $taskrole, $projectsListId, $addordertick);
				$level--;
				$total_projectlinesa_spent += $lines[$i]->duration;
				$total_projectlinesa_planned += $lines[$i]->planned_workload;
				if ($lines[$i]->planned_workload) $total_projectlinesa_spent_if_planned += $lines[$i]->duration;
			}
		}
		else
		{
			//$level--;
		}
	}

	if (($total_projectlinesa_planned > 0 || $total_projectlinesa_spent > 0) && $level==0)
	{
		print '<tr class="liste_total nodrag nodrop">';
		print '<td class="liste_total">'.$langs->trans("Total").'</td>';
		if ($showproject) print '<td></td><td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td align="right" class="nowrap liste_total">';
		print convertSecondToTime($total_projectlinesa_planned, 'allhourmin');
		print '</td>';
		print '<td></td>';
		print '<td align="right" class="nowrap liste_total">';
		print convertSecondToTime($total_projectlinesa_spent, 'allhourmin');
		print '</td>';
		print '<td align="right" class="nowrap liste_total">';
		if ($total_projectlinesa_planned) print round(100 * $total_projectlinesa_spent / $total_projectlinesa_planned,2).' %';
		print '</td>';
		if ($addordertick) print '<td class="hideonsmartphone"></td>';
		print '</tr>';
	}

	return $inc;
}


/**
 * Output a task line
 *
 * @param	string	   	$inc					?
 * @param   string		$parent					?
 * @param   Task[]		$lines					?
 * @param   int			$level					?
 * @param   string		$projectsrole			?
 * @param   string		$tasksrole				?
 * @param	string		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is a task i am affected to
 * @return  $inc
 */
function projectLinesb(&$inc, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask=0)
{
	global $db, $user, $bc, $langs;
	global $form, $formother, $projectstatic, $taskstatic;

	if (! is_object($formother))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
		$formother = new FormOther($db);
	}

	$lastprojectid=0;

	$var=true;

	$numlines=count($lines);
	for ($i = 0 ; $i < $numlines ; $i++)
	{
		if ($parent == 0) $level = 0;

		if ($lines[$i]->fk_parent == $parent)
		{
			// Break on a new project
			if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
			{
				$var = !$var;
				$lastprojectid=$lines[$i]->fk_project;
			}

			// If we want all or we have a role on task, we show it
			if (empty($mine) || ! empty($tasksrole[$lines[$i]->id]))
			{
				print "<tr ".$bc[$var].">\n";

				// Project
				print "<td>";
				$projectstatic->id=$lines[$i]->fk_project;
				$projectstatic->ref=$lines[$i]->projectref;
				$projectstatic->public=$lines[$i]->public;
				$projectstatic->label=$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project];
				print $projectstatic->getNomUrl(1);
				print "</td>";

				// Ref
				print '<td>';
				$taskstatic->id=$lines[$i]->id;
				$taskstatic->ref=$lines[$i]->id;
				print $taskstatic->getNomUrl(1);
				print '</td>';

				// Label task
				print "<td>";
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp;&nbsp;&nbsp;";
				}
				$taskstatic->id=$lines[$i]->id;
				$taskstatic->ref=$lines[$i]->label;
				print $taskstatic->getNomUrl(0);
				print "</td>\n";

				// Date start
				print '<td align="center">';
				print dol_print_date($lines[$i]->date_start,'dayhour');
				print '</td>';

				// Date end
				print '<td align="center">';
				print dol_print_date($lines[$i]->date_end,'dayhour');
				print '</td>';

				// Planned Workload
				print '<td align="right">';
				if ($lines[$i]->planned_workload) print convertSecondToTime($lines[$i]->planned_workload,'allhourmin');
				else print '--:--';
				print '</td>';

				// Progress declared %
				print '<td align="right">';
				print $formother->select_percent($lines[$i]->progress, $lines[$i]->id . 'progress');
				print '</td>';

				// Time spent
				print '<td align="right">';
				if ($lines[$i]->duration)
				{
					print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'">';
					print convertSecondToTime($lines[$i]->duration,'allhourmin');
					print '</a>';
				}
				else print '--:--';
				print "</td>\n";

				$disabledproject=1;$disabledtask=1;
				//print "x".$lines[$i]->fk_project;
				//var_dump($lines[$i]);
				//var_dump($projectsrole[$lines[$i]->fk_project]);
				// If at least one role for project
				if ($lines[$i]->public || ! empty($projectsrole[$lines[$i]->fk_project]) || $user->rights->projet->all->creer)
				{
					$disabledproject=0;
					$disabledtask=0;
				}
				// If $restricteditformytask is on and I have no role on task, i disable edit
				if ($restricteditformytask && empty($tasksrole[$lines[$i]->id]))
				{
					$disabledtask=1;
				}

				// Form to add new time
				print '<td class="nowrap" align="right">';
				$s='';
				$s.=$form->select_date('',$lines[$i]->id,0,0,2,"addtime",1,0,1,$disabledtask);
				$s.='&nbsp;&nbsp;&nbsp;';
				$s.=$form->select_duration($lines[$i]->id,'',$disabledtask,'text',0,1);
				$s.='&nbsp;<input type="submit" class="button"'.($disabledtask?' disabled="disabled"':'').' value="'.$langs->trans("Add").'">';
				print $s;
				print '</td>';

				print '<td align="right">';
				if ((! $lines[$i]->public) && $disabledproject) print $form->textwithpicto('',$langs->trans("YouAreNotContactOfProject"));
				else if ($disabledtask) print $form->textwithpicto('',$langs->trans("TaskIsNotAffectedToYou"));
				print '</td>';

				print "</tr>\n";
			}

			$inc++;
			$level++;
			if ($lines[$i]->id) projectLinesb($inc, $lines[$i]->id, $lines, $level, $projectsrole, $tasksrole, $mine, $restricteditformytask);
			$level--;
		}
		else
		{
			//$level--;
		}
	}

	return $inc;
}


/**
 * Search in task lines with a particular parent if there is a task for a particular user (in taskrole)
 *
 * @param 	string	$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	int		$parent				Id of parent task to start
 * @param 	array	$lines				Array of all tasks
 * @param	string	$taskrole			Array of task filtered on a particular user
 * @return	int							1 if there is
 */
function searchTaskInChild(&$inc, $parent, &$lines, &$taskrole)
{
	//print 'Search in line with parent id = '.$parent.'<br>';
	$numlines=count($lines);
	for ($i = 0 ; $i < $numlines ; $i++)
	{
		// Process line $lines[$i]
		if ($lines[$i]->fk_parent == $parent && $lines[$i]->id != $lines[$i]->fk_parent)
		{
			// If task is legitimate to show, no more need to search deeper
			if (isset($taskrole[$lines[$i]->id]))
			{
				//print 'Found a legitimate task id='.$lines[$i]->id.'<br>';
				$inc++;
				return $inc;
			}

			searchTaskInChild($inc, $lines[$i]->id, $lines, $taskrole);
			//print 'Found inc='.$inc.'<br>';

			if ($inc > 0) return $inc;
		}
	}

	return $inc;
}

/**
 * Return HTML table with list of projects and number of opened tasks
 *
 * @param	DoliDB	$db					Database handler
 * @param   int		$socid				Id thirdparty
 * @param   int		$projectsListId     Id of project i have permission on
 * @param   int		$mytasks            Limited to task i am contact to
 * @param	int		$statut				-1=No filter on statut, 0 or 1 = Filter on status
 * @return	void
 */
function print_projecttasks_array($db, $socid, $projectsListId, $mytasks=0, $statut=-1)
{
	global $langs,$conf,$user,$bc;

	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

	$projectstatic=new Project($db);

	$sortfield='';
	$sortorder='';
	$project_year_filter=0;

	$title=$langs->trans("Project");
	if ($statut == 0) $title=$langs->trans("ProjectDraft");
	if ($statut == 1) $title=$langs->trans("Project").' ('.$langs->trans("Validated").')';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($title,"index.php","","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Tasks"),"","","","",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"","","","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut, COUNT(t.rowid) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	if ($mytasks)
	{
		$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
	}
	else
	{
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
	}
	$sql.= " WHERE p.entity = ".$conf->entity;
	$sql.= " AND p.rowid IN (".$projectsListId.")";
	if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
	if ($mytasks)
	{
		$sql.= " AND p.rowid = t.fk_projet";
		$sql.= " AND ec.element_id = t.rowid";
		$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
		$sql.= " AND ctc.element = 'project_task'";
		$sql.= " AND ec.fk_socpeople = ".$user->id;
	}
	if ($statut >= 0)
	{
		$sql.= " AND p.fk_statut = ".$statut;
	}
	if (!empty($conf->global->PROJECT_LIMIT_YEAR_RANGE)) {
		$project_year_filter = GETPOST("project_year_filter");
		//Check if empty or invalid year. Wildcard ignores the sql check
		if ($project_year_filter != "*") {
			if (empty($project_year_filter) || !ctype_digit($project_year_filter)) { //
				$project_year_filter = date("Y");
			}
			$sql.= " AND (p.dateo IS NULL OR p.dateo <= ".$db->idate(dol_get_last_day($project_year_filter,12,false)).")";
			$sql.= " AND (p.datee IS NULL OR p.datee >= ".$db->idate(dol_get_first_day($project_year_filter,1,false)).")";
		}
	}
	$sql.= " GROUP BY p.rowid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut";
	$sql.= " ORDER BY p.title, p.ref";

	$var=true;
	$resql = $db->query($sql);
	if ( $resql )
	{
		$num = $db->num_rows($resql);
		$i = 0;

		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);

			$projectstatic->id = $objp->projectid;
			$projectstatic->user_author_id = $objp->fk_user_creat;
			$projectstatic->public = $objp->public;

			// Check is user has read permission on project
			$userAccess = $projectstatic->restrictedProjectArea($user);
			if ($userAccess >= 0)
			{
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td class="nowrap">';
				$projectstatic->ref=$objp->ref;
				print $projectstatic->getNomUrl(1);
				print ' - '.dol_trunc($objp->title,24).'</td>';
				print '<td align="right">'.$objp->nb.'</td>';
				$projectstatic->statut = $objp->fk_statut;
				print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';
				print "</tr>\n";
			}

			$i++;
		}

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print "</table>";

	if (!empty($conf->global->PROJECT_LIMIT_YEAR_RANGE)) {
		//Add the year filter input
		print '<table width="100%">';
		print '<tr>';
		print '<td>'.$langs->trans("Year").'</td>';
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
		print '<td style="text-align:right"><input type="text" size="4" class="flat" name="project_year_filter" value="'.$project_year_filter.'"/>';
		print '</form>';
		print "</tr>\n";
		print '</table>';
	}
}

