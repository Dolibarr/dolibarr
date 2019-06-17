<?php
/* Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Project");
	$head[$h][2] = 'project';
	$h++;

	$nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
	$head[$h][0] = DOL_URL_ROOT.'/projet/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ProjectContact");
	if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
	$head[$h][2] = 'contact';
	$h++;

	if (empty($conf->global->PROJECT_HIDE_TASKS))
	{
		// Then tab for sub level of projet, i mean tasks
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Tasks");

		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		$taskstatic=new Task($db);
		$nbTasks=count($taskstatic->getTasksArray(0, 0, $object->id, 0, 0));
		if ($nbTasks > 0) $head[$h][1].= ' <span class="badge">'.($nbTasks).'</span>';
		$head[$h][2] = 'tasks';
		$h++;

		$nbTimeSpent=0;
		$sql = "SELECT t.rowid";
		//$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
		//$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t, ".MAIN_DB_PREFIX."projet_task as pt";
		$sql .= " WHERE t.fk_task = pt.rowid";
		$sql .= " AND pt.fk_projet =".$object->id;
		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			if ($obj) $nbTimeSpent=1;
		}
		else dol_print_error($db);

		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?withproject=1&projectid='.$object->id;
		$head[$h][1] = $langs->trans("TimeSpent");
		if ($nbTimeSpent > 0) $head[$h][1].= ' <span class="badge">...</span>';
		$head[$h][2] = 'timespent';
		$h++;
	}

	if (! empty($conf->fournisseur->enabled) || ! empty($conf->propal->enabled) || ! empty($conf->commande->enabled)
		|| ! empty($conf->facture->enabled) || ! empty($conf->contrat->enabled)
		|| ! empty($conf->ficheinter->enabled) || ! empty($conf->agenda->enabled) || ! empty($conf->deplacement->enabled))
	{
		$head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$object->id;
		$head[$h][1] = $langs->trans("ProjectOverview");
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
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/projet/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'document';
	$h++;

	// Manage discussion
	if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_PROJECT))
	{
		$nbComments = $object->getNbComments();
		$head[$h][0] = DOL_URL_ROOT.'/projet/comment.php?id='.$object->id;
		$head[$h][1] = $langs->trans("CommentLink");
		if ($nbComments > 0) $head[$h][1].= ' <span class="badge">'.$nbComments.'</span>';
		$head[$h][2] = 'project_comment';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/projet/info.php?id='.$object->id;
	$head[$h][1].= $langs->trans("Events");
	if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
	{
		$head[$h][1].= '/';
		$head[$h][1].= $langs->trans("Agenda");
	}
	$head[$h][2] = 'agenda';
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
	global $db, $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/task.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'task_task';
	$h++;

	$nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');
	$head[$h][1] = $langs->trans("TaskRessourceLinks");
	if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
	$head[$h][2] = 'task_contact';
	$h++;

	// Is there timespent ?
	$nbTimeSpent=0;
	$sql = "SELECT t.rowid";
	//$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t, ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."user as u";
	//$sql .= " WHERE t.fk_user = u.rowid AND t.fk_task = pt.rowid";
	$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
	$sql .= " WHERE t.fk_task =".$object->id;
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj) $nbTimeSpent=1;
	}
	else dol_print_error($db);

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');
	$head[$h][1] = $langs->trans("TimeSpent");
	if ($nbTimeSpent > 0) $head[$h][1].= ' <span class="badge">...</span>';
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
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/note.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
		$head[$h][2] = 'task_notes';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/document.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');
	$filesdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->project->ref) . '/' .dol_sanitizeFileName($object->ref);
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$nbFiles = count(dol_dir_list($filesdir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'task_document';
	$h++;

	// Manage discussion
	if (!empty($conf->global->PROJECT_ALLOW_COMMENT_ON_TASK))
	{
		$nbComments = $object->getNbComments();
		$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/comment.php?id='.$object->id.(GETPOST('withproject')?'&withproject=1':'');
		$head[$h][1] = $langs->trans("CommentLink");
		if ($nbComments > 0) $head[$h][1].= ' <span class="badge">'.$nbComments.'</span>';
		$head[$h][2] = 'task_comment';
		$h++;
	}

	complete_head_from_modules($conf,$langs,$object,$head,$h,'task','remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param	string	$mode		Mode
 * @param   string  $fuser      Filter on user
 * @return  array				Array of tabs to show
 */
function project_timesheet_prepare_head($mode, $fuser=null)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$h = 0;

	$param='';
	$param.=($mode?'&mode='.$mode:'');
	if (is_object($fuser) && $fuser->id > 0 && $fuser->id != $user->id) $param.='&search_usertoprocessid='.$fuser->id;

	if (empty($conf->global->PROJECT_DISABLE_TIMESHEET_PERWEEK))
	{
		$head[$h][0] = DOL_URL_ROOT."/projet/activity/perweek.php".($param?'?'.$param:'');
		$head[$h][1] = $langs->trans("InputPerWeek");
		$head[$h][2] = 'inputperweek';
		$h++;
	}

	if (empty($conf->global->PROJECT_DISABLE_TIMESHEET_PERTIME))
	{
		$head[$h][0] = DOL_URL_ROOT."/projet/activity/perday.php".($param?'?'.$param:'');
		$head[$h][1] = $langs->trans("InputPerDay");
		$head[$h][2] = 'inputperday';
		$h++;
	}

	/*if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		$head[$h][0] = DOL_URL_ROOT."/projet/activity/perline.php".($param?'?'.$param:'');
		$head[$h][1] = $langs->trans("InputDetail");
		$head[$h][2] = 'inputperline';
		$h++;
	}*/

	complete_head_from_modules($conf,$langs,null,$head,$h,'project_timesheet');

	complete_head_from_modules($conf,$langs,null,$head,$h,'project_timesheet','remove');

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
 * @param	string	   	$inc				Line number (start to 0, then increased by recursive call)
 * @param   string		$parent				Id of parent project to show (0 to show all)
 * @param   Task[]		$lines				Array of lines
 * @param   int			$level				Level (start to 0, then increased/decrease by recursive call), or -1 to show all level in order of $lines without the recursive groupment feature.
 * @param 	string		$var				Color
 * @param 	int			$showproject		Show project columns
 * @param	int			$taskrole			Array of roles of user for each tasks
 * @param	int			$projectsListId		List of id of project allowed to user (string separated with comma)
 * @param	int			$addordertick		Add a tick to move task
 * @param   int         $projectidfortotallink     0 or Id of project to use on total line (link to see all time consumed for project)
 * @param   string      $filterprogresscalc     filter text
 * @return	void
 */
function projectLinesa(&$inc, $parent, &$lines, &$level, $var, $showproject, &$taskrole, $projectsListId='', $addordertick=0, $projectidfortotallink=0, $filterprogresscalc='')
{
	global $user, $bc, $langs, $conf, $db;
	global $projectstatic, $taskstatic;

	$lastprojectid=0;

	$projectsArrayId=explode(',',$projectsListId);
	if ($filterprogresscalc!=='') {
		foreach ($lines as $key=>$line) {
			if (!empty($line->planned_workload) && !empty($line->duration)) {
				$filterprogresscalc = str_replace(' = ', ' == ', $filterprogresscalc);
				if (!eval($filterprogresscalc)) {
					unset($lines[$key]);
				}
			}
		}
		$lines=array_values($lines);
	}

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
		if ($parent == 0 && $level >= 0) $level = 0;              // if $level = -1, we dont' use sublevel recursion, we show all lines

		// Process line
		// print "i:".$i."-".$lines[$i]->fk_project.'<br>';

		if ($lines[$i]->fk_parent == $parent || $level < 0)       // if $level = -1, we dont' use sublevel recursion, we show all lines
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
					//if ($showlineingray) print '<i>';
					$projectstatic->id=$lines[$i]->fk_project;
					$projectstatic->ref=$lines[$i]->projectref;
					$projectstatic->public=$lines[$i]->public;
					$projectstatic->title=$lines[$i]->projectlabel;
					if ($lines[$i]->public || in_array($lines[$i]->fk_project,$projectsArrayId) || ! empty($user->rights->projet->all->lire)) print $projectstatic->getNomUrl(1);
					else print $projectstatic->getNomUrl(1,'nolink');
					//if ($showlineingray) print '</i>';
					print "</td>";

					// Project status
					print '<td>';
					$projectstatic->statut=$lines[$i]->projectstatus;
					print $projectstatic->getLibStatut(2);
					print "</td>";
				}

				// Ref of task
				print '<td class="nowraponall">';
				if ($showlineingray)
				{
					print '<i>'.img_object('','projecttask').' '.$lines[$i]->ref.'</i>';
				}
				else
				{
					$taskstatic->id=$lines[$i]->id;
					$taskstatic->ref=$lines[$i]->ref;
					$taskstatic->label=($taskrole[$lines[$i]->id]?$langs->trans("YourRole").': '.$taskrole[$lines[$i]->id]:'');
					print $taskstatic->getNomUrl(1,'withproject');
				}
				print '</td>';

				// Title of task
				print "<td>";
				if ($showlineingray) print '<i>';
				//else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$lines[$i]->id.'&withproject=1">';
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp; &nbsp; &nbsp;";
				}
				print $lines[$i]->label;
				if ($showlineingray) print '</i>';
				//else print '</a>';
				print "</td>\n";

				// Date start
				print '<td align="center">';
				print dol_print_date($lines[$i]->date_start,'dayhour');
				print '</td>';

				// Date end
				print '<td align="center">';
				$taskstatic->projectstatus = $lines[$i]->projectstatus;
				$taskstatic->progress = $lines[$i]->progress;
				$taskstatic->fk_statut = $lines[$i]->status;
				$taskstatic->datee = $lines[$i]->date_end;
				print dol_print_date($lines[$i]->date_end,'dayhour');
				if ($taskstatic->hasDelay()) print img_warning($langs->trans("Late"));
				print '</td>';

				$plannedworkloadoutputformat='allhourmin';
				$timespentoutputformat='allhourmin';
				if (! empty($conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT)) $plannedworkloadoutputformat=$conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT;
				if (! empty($conf->global->PROJECT_TIMES_SPENT_FORMAT)) $timespentoutputformat=$conf->global->PROJECT_TIME_SPENT_FORMAT;

				// Planned Workload (in working hours)
				print '<td align="right">';
				$fullhour=convertSecondToTime($lines[$i]->planned_workload,$plannedworkloadoutputformat);
				$workingdelay=convertSecondToTime($lines[$i]->planned_workload,'all',86400,7);	// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
				if ($lines[$i]->planned_workload != '')
				{
					print $fullhour;
					// TODO Add delay taking account of working hours per day and working day per week
					//if ($workingdelay != $fullhour) print '<br>('.$workingdelay.')';
				}
				//else print '--:--';
				print '</td>';

				// Time spent
				print '<td align="right">';
				if ($showlineingray) print '<i>';
				else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.($showproject?'':'&withproject=1').'">';
				if ($lines[$i]->duration) print convertSecondToTime($lines[$i]->duration,$timespentoutputformat);
				else print '--:--';
				if ($showlineingray) print '</i>';
				else print '</a>';
				print '</td>';

				// Progress calculated (Note: ->duration is time spent)
				print '<td align="right">';
				if ($lines[$i]->planned_workload || $lines[$i]->duration)
				{
					if ($lines[$i]->planned_workload) print round(100 * $lines[$i]->duration / $lines[$i]->planned_workload,2).' %';
					else print '<span class="opacitymedium">'.$langs->trans('WorkloadNotDefined').'</span>';
				}
				print '</td>';

				// Progress declared
				print '<td align="right">';
				if ($lines[$i]->progress != '')
				{
					print $lines[$i]->progress.' %';
				}
				print '</td>';

				// Contacts of task
				if (! empty($conf->global->PROJECT_SHOW_CONTACTS_IN_LIST))
				{
					print '<td>';
					foreach(array('internal','external') as $source)
					{
						$tab = $lines[$i]->liste_contact(-1,$source);
						$num=count($tab);
						if (!empty($num)){
							foreach ($tab as $contacttask){
								//var_dump($contacttask);
								if ($source == 'internal') $c = new User($db);
								else $c = new Contact($db);
								$c->fetch($contacttask['id']);
								print $c->getNomUrl(1) . ' (' . $contacttask['libelle'] . ')' . '<br>';
							}
						}
					}
					print '</td>';
				}

				// Tick to drag and drop
				if ($addordertick)
				{
					print '<td align="center" class="tdlineupdown hideonsmartphone">&nbsp;</td>';
				}

				print "</tr>\n";

				if (! $showlineingray) $inc++;

				if ($level >= 0)    // Call sublevels
				{
					$level++;
					if ($lines[$i]->id) projectLinesa($inc, $lines[$i]->id, $lines, $level, $var, $showproject, $taskrole, $projectsListId, $addordertick);
					$level--;
				}

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

	if (($total_projectlinesa_planned > 0 || $total_projectlinesa_spent > 0) && $level <= 0)
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
		print '<td align="right" class="nowrap liste_total">';
		if ($projectidfortotallink > 0) print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?projectid='.$projectidfortotallink.($showproject?'':'&withproject=1').'">';
		print convertSecondToTime($total_projectlinesa_spent, 'allhourmin');
		if ($projectidfortotallink > 0) print '</a>';
		print '</td>';
		print '<td align="right" class="nowrap liste_total">';
		if ($total_projectlinesa_planned) print round(100 * $total_projectlinesa_spent / $total_projectlinesa_planned,2).' %';
		print '</td>';
		print '<td></td>';
		// Contacts of task
		if (! empty($conf->global->PROJECT_SHOW_CONTACTS_IN_LIST))
		{
			print '<td></td>';
		}
		if ($addordertick) print '<td class="hideonsmartphone"></td>';
		print '</tr>';
	}

	return $inc;
}


/**
 * Output a task line into a pertime intput mode
 *
 * @param	string	   	$inc					Line number (start to 0, then increased by recursive call)
 * @param   string		$parent					Id of parent task to show (0 to show all)
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   Task[]		$lines					Array of lines
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	string		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is a task i am affected to
 * @param	int			$preselectedday			Preselected day
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and substasks
 */
function projectLinesPerAction(&$inc, $parent, $fuser, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, $preselectedday, &$isavailable, $oldprojectforbreak=0)
{
	global $conf, $db, $user, $bc, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$lastprojectid=0;
	$totalforeachline=array();
	$workloadforid=array();
	$lineswithoutlevel0=array();

	$numlines=count($lines);

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) // Always and only if at first level
	{
		for ($i = 0 ; $i < $numlines ; $i++)
		{
			if ($lines[$i]->fk_task_parent) $lineswithoutlevel0[]=$lines[$i];
		}
	}

	if (empty($oldprojectforbreak))
	{
		$oldprojectforbreak = (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)?0:-1);	// 0 to start break , -1 no break
	}

	//dol_syslog('projectLinesPerDay inc='.$inc.' preselectedday='.$preselectedday.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));
	for ($i = 0 ; $i < $numlines ; $i++)
	{
		if ($parent == 0) $level = 0;

		//if ($lines[$i]->fk_task_parent == $parent)
		//{
			// If we want all or we have a role on task, we show it
			if (empty($mine) || ! empty($tasksrole[$lines[$i]->id]))
			{
				//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
				{
					$lastprojectid=$lines[$i]->fk_project;
					if ($preselectedday)
					{
						$projectstatic->id = $lines[$i]->fk_project;
					}
				}

				if (empty($workloadforid[$projectstatic->id]))
				{
					if ($preselectedday)
					{
						$projectstatic->loadTimeSpent($preselectedday, 0, $fuser->id);	// Load time spent from table projet_task_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
						$workloadforid[$projectstatic->id]=1;
					}
				}

				$projectstatic->id=$lines[$i]->fk_project;
				$projectstatic->ref=$lines[$i]->project_ref;
				$projectstatic->title=$lines[$i]->project_label;
				$projectstatic->public=$lines[$i]->public;

				$taskstatic->id=$lines[$i]->task_id;
				$taskstatic->ref=($lines[$i]->task_ref?$lines[$i]->task_ref:$lines[$i]->task_id);
				$taskstatic->label=$lines[$i]->task_label;
				$taskstatic->date_start=$lines[$i]->date_start;
				$taskstatic->date_end=$lines[$i]->date_end;

				$thirdpartystatic->id=$lines[$i]->socid;
				$thirdpartystatic->name=$lines[$i]->thirdparty_name;
				$thirdpartystatic->email=$lines[$i]->thirdparty_email;

				if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id))
				{
					print '<tr class="oddeven trforbreak">'."\n";
					print '<td colspan="11">';
					print $projectstatic->getNomUrl(1,'',0,$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
					if ($projectstatic->title)
					{
						print ' - ';
						print $projectstatic->title;
					}
					print '</td>';
					print '</tr>';
				}

				if ($oldprojectforbreak != -1) $oldprojectforbreak = $projectstatic->id;

				print '<tr class="oddeven">'."\n";

				// User
				/*
				 print '<td class="nowrap">';
				 print $fuser->getNomUrl(1, 'withproject', 'time');
				 print '</td>';
				 */

				// Project
				print "<td>";
				if ($oldprojectforbreak == -1)
				{
					print $projectstatic->getNomUrl(1,'',0,$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
					print '<br>'.$projectstatic->title;
				}
				print "</td>";

				// Thirdparty
				print '<td class="tdoverflowmax100">';
				if ($thirdpartystatic->id > 0) print $thirdpartystatic->getNomUrl(1, 'project', 10);
				print '</td>';

				// Ref
				print '<td>';
				print '<!-- Task id = '.$lines[$i]->id.' -->';
				for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				print $taskstatic->getNomUrl(1, 'withproject', 'time');
				// Label task
				print '<br>';
				for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				print $taskstatic->label;
				//print "<br>";
				//for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				//print get_date_range($lines[$i]->date_start,$lines[$i]->date_end,'',$langs,0);
				print "</td>\n";

				// Date
				print '<td align="center">';
				print dol_print_date($lines[$i]->timespent_datehour,'day');
				print '</td>';

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

				// Hour
				print '<td class="nowrap" align="center">';
				print dol_print_date($lines[$i]->timespent_datehour,'hour');
				print '</td>';

				$cssonholiday='';
				if (! $isavailable[$preselectedday]['morning'] && ! $isavailable[$preselectedday]['afternoon'])   $cssonholiday.='onholidayallday ';
				elseif (! $isavailable[$preselectedday]['morning'])   $cssonholiday.='onholidaymorning ';
				elseif (! $isavailable[$preselectedday]['afternoon']) $cssonholiday.='onholidayafternoon ';

				// Duration
				print '<td align="center" class="duration'.($cssonholiday?' '.$cssonholiday:'').'">';

				$dayWorkLoad = $lines[$i]->timespent_duration;
				$totalforeachline[$preselectedday]+=$lines[$i]->timespent_duration;

				$alreadyspent='';
				if ($dayWorkLoad > 0) $alreadyspent=convertSecondToTime($lines[$i]->timespent_duration,'allhourmin');

				print convertSecondToTime($lines[$i]->timespent_duration,'allhourmin');

				$modeinput='hours';

				print '<script type="text/javascript">';
				print "jQuery(document).ready(function () {\n";
				print " 	jQuery('.inputhour, .inputminute').bind('keyup', function(e) { updateTotal(0, '".$modeinput."') });";
				print "})\n";
				print '</script>';

				print '</td>';

				// Note
				print '<td align="center">';
				print '<textarea name="'.$lines[$i]->id.'note" rows="'.ROWS_2.'" id="'.$lines[$i]->id.'note"'.($disabledtask?' disabled="disabled"':'').'>';
				print $lines[$i]->timespent_note;
				print '</textarea>';
				print '</td>';

				// Warning
				print '<td align="right">';
				/*if ((! $lines[$i]->public) && $disabledproject) print $form->textwithpicto('',$langs->trans("UserIsNotContactOfProject"));
				else if ($disabledtask)
				{
					$titleassigntask = $langs->trans("AssignTaskToMe");
					if ($fuser->id != $user->id) $titleassigntask = $langs->trans("AssignTaskToUser", '...');

					print $form->textwithpicto('',$langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
				}*/
				print '</td>';

				print "</tr>\n";
			}
		//}
		//else
		//{
			//$level--;
		//}
	}

	return $totalforeachline;
}


/**
 * Output a task line into a pertime intput mode
 *
 * @param	string	   	$inc					Line number (start to 0, then increased by recursive call)
 * @param   string		$parent					Id of parent task to show (0 to show all)
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   Task[]		$lines					Array of lines
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	string		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param	int			$preselectedday			Preselected day
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and substasks
 */
function projectLinesPerDay(&$inc, $parent, $fuser, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, $preselectedday, &$isavailable, $oldprojectforbreak=0)
{
	global $conf, $db, $user, $bc, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$lastprojectid=0;
	$totalforeachday=array();
	$workloadforid=array();
	$lineswithoutlevel0=array();

	$numlines=count($lines);

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) // Always and only if at first level
	{
		for ($i = 0 ; $i < $numlines ; $i++)
		{
			if ($lines[$i]->fk_task_parent) $lineswithoutlevel0[]=$lines[$i];
		}
	}

	if (empty($oldprojectforbreak))
	{
		$oldprojectforbreak = (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)?0:-1);	// 0 to start break , -1 no break
	}

	//dol_syslog('projectLinesPerDay inc='.$inc.' preselectedday='.$preselectedday.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));
	for ($i = 0 ; $i < $numlines ; $i++)
	{
		if ($parent == 0) $level = 0;

		if ($lines[$i]->fk_task_parent == $parent)
		{
			// If we want all or we have a role on task, we show it
			if (empty($mine) || ! empty($tasksrole[$lines[$i]->id]))
			{
				//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

				if ($restricteditformytask == 2 && empty($tasksrole[$lines[$i]->id]))	// we have no role on task and we request to hide such cases
				{
					continue;
				}

				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
				{
					$lastprojectid=$lines[$i]->fk_project;
					if ($preselectedday)
					{
						$projectstatic->id = $lines[$i]->fk_project;
					}
				}

				if (empty($workloadforid[$projectstatic->id]))
				{
					if ($preselectedday)
					{
						$projectstatic->loadTimeSpent($preselectedday, 0, $fuser->id);	// Load time spent from table projet_task_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
		   				$workloadforid[$projectstatic->id]=1;
					}
				}

				$projectstatic->id=$lines[$i]->fk_project;
				$projectstatic->ref=$lines[$i]->projectref;
				$projectstatic->title=$lines[$i]->projectlabel;
				$projectstatic->public=$lines[$i]->public;

				$taskstatic->id=$lines[$i]->id;
				$taskstatic->ref=($lines[$i]->ref?$lines[$i]->ref:$lines[$i]->id);
				$taskstatic->label=$lines[$i]->label;
				$taskstatic->date_start=$lines[$i]->date_start;
				$taskstatic->date_end=$lines[$i]->date_end;

				$thirdpartystatic->id=$lines[$i]->socid;
				$thirdpartystatic->name=$lines[$i]->thirdparty_name;
				$thirdpartystatic->email=$lines[$i]->thirdparty_email;

				if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id))
				{
					print '<tr class="oddeven trforbreak">'."\n";
					print '<td colspan="9">';
					print $projectstatic->getNomUrl(1,'',0,'<strong>'.$langs->transnoentitiesnoconv("YourRole").':</strong> '.$projectsrole[$lines[$i]->fk_project]);
					if ($thirdpartystatic->id > 0) print ' - '.$thirdpartystatic->getNomUrl(1);
					if ($projectstatic->title)
					{
						print ' - ';
						print $projectstatic->title;
					}
					print '</td>';
					print '</tr>';
				}

				if ($oldprojectforbreak != -1) $oldprojectforbreak = $projectstatic->id;

				print '<tr class="oddeven">'."\n";

				// User
				/*
				print '<td class="nowrap">';
				print $fuser->getNomUrl(1, 'withproject', 'time');
				print '</td>';
				*/

				// Project
				/*print "<td>";
				if ($oldprojectforbreak == -1) print $projectstatic->getNomUrl(1,'',0,$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
				print "</td>";*/

				// Thirdparty
				/*print '<td class="tdoverflowmax100">';
				if ($thirdpartystatic->id > 0) print $thirdpartystatic->getNomUrl(1, 'project', 10);
				print '</td>';*/

				// Ref
				print '<td>';
				print '<!-- Task id = '.$lines[$i]->id.' -->';
				for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				print $taskstatic->getNomUrl(1, 'withproject', 'time');
				// Label task
				print '<br>';
				for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				print $taskstatic->label;
				//print "<br>";
				//for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				//print get_date_range($lines[$i]->date_start,$lines[$i]->date_end,'',$langs,0);
				print "</td>\n";

				// Planned Workload
				print '<td align="right" class="leftborder plannedworkload">';
				if ($lines[$i]->planned_workload) print convertSecondToTime($lines[$i]->planned_workload,'allhourmin');
				else print '--:--';
				print '</td>';

				// Progress declared %
				print '<td align="right">';
				print $formother->select_percent($lines[$i]->progress, $lines[$i]->id . 'progress');
				print '</td>';

				// Time spent by everybody
				print '<td align="right">';
				// $lines[$i]->duration is a denormalised field = summ of time spent by everybody for task. What we need is time consummed by user
				if ($lines[$i]->duration)
				{
					print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'">';
					print convertSecondToTime($lines[$i]->duration,'allhourmin');
					print '</a>';
				}
				else print '--:--';
				print "</td>\n";

				// Time spent by user
				print '<td align="right">';
				$tmptimespent=$taskstatic->getSummaryOfTimeSpent($fuser->id);
				if ($tmptimespent['total_duration']) print convertSecondToTime($tmptimespent['total_duration'],'allhourmin');
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
				print '<td class="nowrap leftborder" align="center">';
				$tableCell = $form->selectDate($preselectedday, $lines[$i]->id, 1, 1, 2, "addtime", 0, 0, $disabledtask);
				print $tableCell;
				print '</td>';

				$cssonholiday='';
				if (! $isavailable[$preselectedday]['morning'] && ! $isavailable[$preselectedday]['afternoon'])   $cssonholiday.='onholidayallday ';
				elseif (! $isavailable[$preselectedday]['morning'])   $cssonholiday.='onholidaymorning ';
				elseif (! $isavailable[$preselectedday]['afternoon']) $cssonholiday.='onholidayafternoon ';

				global $daytoparse;
				$tmparray = dol_getdate($daytoparse,true);	// detail of current day
				$idw = $tmparray['wday'];

				global $numstartworkingday, $numendworkingday;
				$cssweekend='';
				if (($idw + 1) < $numstartworkingday || ($idw + 1) > $numendworkingday)	// This is a day is not inside the setup of working days, so we use a week-end css.
				{
					$cssweekend='weekend';
				}

				// Duration
				print '<td class="center duration'.($cssonholiday?' '.$cssonholiday:'').($cssweekend?' '.$cssweekend:'').'">';
				$dayWorkLoad = $projectstatic->weekWorkLoadPerTask[$preselectedday][$lines[$i]->id];
				$totalforeachday[$preselectedday]+=$dayWorkLoad;

				$alreadyspent='';
				if ($dayWorkLoad > 0) $alreadyspent=convertSecondToTime($dayWorkLoad,'allhourmin');

				$idw = 0;

				$tableCell='';
				$tableCell.='<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center" size="2" disabled id="timespent['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="'.$alreadyspent.'"></span>';
				$tableCell.='<span class="hideonsmartphone"> + </span>';
				//$tableCell.='&nbsp;&nbsp;&nbsp;';
				$tableCell.=$form->select_duration($lines[$i]->id.'duration','',$disabledtask,'text',0,1);
				//$tableCell.='&nbsp;<input type="submit" class="button"'.($disabledtask?' disabled':'').' value="'.$langs->trans("Add").'">';
				print $tableCell;

				$modeinput='hours';

				print '<script type="text/javascript">';
				print "jQuery(document).ready(function () {\n";
				print " 	jQuery('.inputhour, .inputminute').bind('keyup', function(e) { updateTotal(0, '".$modeinput."') });";
				print "})\n";
				print '</script>';

				print '</td>';

				// Note
				print '<td align="center">';
				print '<textarea name="'.$lines[$i]->id.'note" rows="'.ROWS_2.'" id="'.$lines[$i]->id.'note"'.($disabledtask?' disabled="disabled"':'').'>';
				print '</textarea>';
				print '</td>';

				// Warning
				print '<td align="right">';
   				if ((! $lines[$i]->public) && $disabledproject) print $form->textwithpicto('',$langs->trans("UserIsNotContactOfProject"));
   				else if ($disabledtask)
   				{
   					$titleassigntask = $langs->trans("AssignTaskToMe");
   					if ($fuser->id != $user->id) $titleassigntask = $langs->trans("AssignTaskToUser", '...');

   					print $form->textwithpicto('',$langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
   				}
				print '</td>';

				print "</tr>\n";
			}

			$inc++;
			$level++;
			if ($lines[$i]->id > 0)
			{
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level);
				//var_dump($totalforeachday);
				$ret = projectLinesPerDay($inc, $lines[$i]->id, $fuser, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $preselectedday, $isavailable, $oldprojectforbreak);
				//var_dump('ret with parent='.$lines[$i]->id.' level='.$level);
				//var_dump($ret);
				foreach($ret as $key => $val)
				{
					$totalforeachday[$key]+=$val;
				}
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level.' + subtasks');
				//var_dump($totalforeachday);
			}
			$level--;
		}
		else
		{
			//$level--;
		}
	}

	return $totalforeachday;
}


/**
 * Output a task line into a perday intput mode
 *
 * @param	string	   	$inc					Line output identificator (start to 0, then increased by recursive call)
 * @param	int			$firstdaytoshow			First day to show
 * @param	User|null	$fuser					Restrict list to user if defined
 * @param   string		$parent					Id of parent task to show (0 to show all)
 * @param   Task[]		$lines					Array of lines (list of tasks but we will show only if we have a specific role on task)
 * @param   int			$level					Level (start to 0, then increased/decrease by recursive call)
 * @param   string		$projectsrole			Array of roles user has on project
 * @param   string		$tasksrole				Array of roles user has on task
 * @param	string		$mine					Show only task lines I am assigned to
 * @param   int			$restricteditformytask	0=No restriction, 1=Enable add time only if task is assigned to me, 2=Enable add time only if tasks is assigned to me and hide others
 * @param   array       $isavailable			Array with data that say if user is available for several days for morning and afternoon
 * @param	int			$oldprojectforbreak		Old project id of last project break
 * @return  array								Array with time spent for $fuser for each day of week on tasks in $lines and substasks
 */
function projectLinesPerWeek(&$inc, $firstdaytoshow, $fuser, $parent, $lines, &$level, &$projectsrole, &$tasksrole, $mine, $restricteditformytask, &$isavailable, $oldprojectforbreak=0)
{
	global $conf, $db, $user, $bc, $langs;
	global $form, $formother, $projectstatic, $taskstatic, $thirdpartystatic;

	$numlines=count($lines);

	$lastprojectid=0;
	$workloadforid=array();
	$totalforeachday=array();
	$lineswithoutlevel0=array();

	// Create a smaller array with sublevels only to be used later. This increase dramatically performances.
	if ($parent == 0) // Always and only if at first level
	{
		for ($i = 0 ; $i < $numlines ; $i++)
		{
		   if ($lines[$i]->fk_task_parent) $lineswithoutlevel0[]=$lines[$i];
		}
	}

	//dol_syslog('projectLinesPerWeek inc='.$inc.' firstdaytoshow='.$firstdaytoshow.' task parent id='.$parent.' level='.$level." count(lines)=".$numlines." count(lineswithoutlevel0)=".count($lineswithoutlevel0));

	if (empty($oldprojectforbreak))
	{
		$oldprojectforbreak = (empty($conf->global->PROJECT_TIMESHEET_DISABLEBREAK_ON_PROJECT)?0:-1);	// 0 = start break, -1 = never break
	}

	for ($i = 0 ; $i < $numlines ; $i++)
	{
		if ($parent == 0) $level = 0;

		if ($lines[$i]->fk_task_parent == $parent)
		{
			// If we want all or we have a role on task, we show it
			if (empty($mine) || ! empty($tasksrole[$lines[$i]->id]))
			{
				//dol_syslog("projectLinesPerWeek Found line ".$i.", a qualified task (i have role or want to show all tasks) with id=".$lines[$i]->id." project id=".$lines[$i]->fk_project);

				if ($restricteditformytask == 2 && empty($tasksrole[$lines[$i]->id]))	// we have no role on task and we request to hide such cases
				{
					continue;
				}

				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
				{
					$lastprojectid=$lines[$i]->fk_project;
					$projectstatic->id = $lines[$i]->fk_project;
				}

				//var_dump('--- '.$level.' '.$firstdaytoshow.' '.$fuser->id.' '.$projectstatic->id.' '.$workloadforid[$projectstatic->id]);
				//var_dump($projectstatic->weekWorkLoadPerTask);
				if (empty($workloadforid[$projectstatic->id]))
				{
					$projectstatic->loadTimeSpent($firstdaytoshow, 0, $fuser->id);	// Load time spent from table projet_task_time for the project into this->weekWorkLoad and this->weekWorkLoadPerTask for all days of a week
					$workloadforid[$projectstatic->id]=1;
				}
				//var_dump($projectstatic->weekWorkLoadPerTask);
				//var_dump('--- '.$projectstatic->id.' '.$workloadforid[$projectstatic->id]);

				$projectstatic->id=$lines[$i]->fk_project;
				$projectstatic->ref=$lines[$i]->projectref;
				$projectstatic->title=$lines[$i]->projectlabel;
				$projectstatic->public=$lines[$i]->public;
				$projectstatic->thirdparty_name=$lines[$i]->thirdparty_name;

				$taskstatic->id=$lines[$i]->id;
				$taskstatic->ref=($lines[$i]->ref?$lines[$i]->ref:$lines[$i]->id);
				$taskstatic->label=$lines[$i]->label;
				$taskstatic->date_start=$lines[$i]->date_start;
				$taskstatic->date_end=$lines[$i]->date_end;

				$thirdpartystatic->id=$lines[$i]->thirdparty_id;
				$thirdpartystatic->name=$lines[$i]->thirdparty_name;
				$thirdpartystatic->email=$lines[$i]->thirdparty_email;

				if (empty($oldprojectforbreak) || ($oldprojectforbreak != -1 && $oldprojectforbreak != $projectstatic->id))
				{
					print '<tr class="oddeven trforbreak">'."\n";
					print '<td colspan="13">';
					print $projectstatic->getNomUrl(1,'',0,'<strong>'.$langs->transnoentitiesnoconv("YourRole").':</strong> '.$projectsrole[$lines[$i]->fk_project]);
					if ($thirdpartystatic->id > 0) print ' - '.$thirdpartystatic->getNomUrl(1);
					if ($projectstatic->title)
					{
						print ' - ';
						print $projectstatic->title;
					}
					print '</td>';
					print '</tr>';
				}

				if ($oldprojectforbreak != -1) $oldprojectforbreak = $projectstatic->id;

				print '<tr class="oddeven">'."\n";

				// User
				/*
				print '<td class="nowrap">';
				print $fuser->getNomUrl(1, 'withproject', 'time');
				print '</td>';
				*/

				// Project
				/*print '<td class="nowrap">';
				if ($oldprojectforbreak == -1) print $projectstatic->getNomUrl(1,'',0,$langs->transnoentitiesnoconv("YourRole").': '.$projectsrole[$lines[$i]->fk_project]);
				print "</td>";*/

				// Thirdparty
				/*print '<td class="tdoverflowmax100">';
				if ($thirdpartystatic->id > 0) print $thirdpartystatic->getNomUrl(1, 'project');
				print '</td>';*/

				// Ref
				print '<td class="nowrap">';
				print '<!-- Task id = '.$lines[$i]->id.' -->';
				for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				print $taskstatic->getNomUrl(1, 'withproject', 'time');
				// Label task
				print '<br>';
				for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				//print $taskstatic->getNomUrl(0, 'withproject', 'time');
				print $taskstatic->label;
				//print "<br>";
				//for ($k = 0 ; $k < $level ; $k++) print "&nbsp;&nbsp;&nbsp;";
				//print get_date_range($lines[$i]->date_start,$lines[$i]->date_end,'',$langs,0);
				print "</td>\n";

				// Planned Workload
				print '<td align="right" class="leftborder plannedworkload">';
				if ($lines[$i]->planned_workload) print convertSecondToTime($lines[$i]->planned_workload,'allhourmin');
				else print '--:--';
				print '</td>';

				// Progress declared %
				print '<td align="right">';
				print $formother->select_percent($lines[$i]->progress, $lines[$i]->id . 'progress');
				print '</td>';

				// Time spent by everybody
				print '<td align="right">';
				// $lines[$i]->duration is a denormalised field = summ of time spent by everybody for task. What we need is time consummed by user
				if ($lines[$i]->duration)
				{
					print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.'">';
					print convertSecondToTime($lines[$i]->duration,'allhourmin');
					print '</a>';
				}
				else print '--:--';
				print "</td>\n";

				// Time spent by user
				print '<td align="right">';
				$tmptimespent=$taskstatic->getSummaryOfTimeSpent($fuser->id);
				if ($tmptimespent['total_duration']) print convertSecondToTime($tmptimespent['total_duration'],'allhourmin');
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

				//var_dump($projectstatic->weekWorkLoadPerTask);

				// Fields to show current time
				$tableCell=''; $modeinput='hours';
				for ($idw = 0; $idw < 7; $idw++)
				{
					$tmpday=dol_time_plus_duree($firstdaytoshow, $idw, 'd');

					$cssonholiday='';
					if (! $isavailable[$tmpday]['morning'] && ! $isavailable[$tmpday]['afternoon'])   $cssonholiday.='onholidayallday ';
					elseif (! $isavailable[$tmpday]['morning'])   $cssonholiday.='onholidaymorning ';
					elseif (! $isavailable[$tmpday]['afternoon']) $cssonholiday.='onholidayafternoon ';

					$tmparray=dol_getdate($tmpday);
					$dayWorkLoad = $projectstatic->weekWorkLoadPerTask[$tmpday][$lines[$i]->id];
					$totalforeachday[$tmpday]+=$dayWorkLoad;

					$alreadyspent='';
					if ($dayWorkLoad > 0) $alreadyspent=convertSecondToTime($dayWorkLoad,'allhourmin');
					$alttitle=$langs->trans("AddHereTimeSpentForDay",$tmparray['day'],$tmparray['mon']);

					global $numstartworkingday, $numendworkingday;
					$cssweekend='';
					if (($idw + 1) < $numstartworkingday || ($idw + 1) > $numendworkingday)	// This is a day is not inside the setup of working days, so we use a week-end css.
					{
						$cssweekend='weekend';
					}

					$tableCell ='<td align="center" class="hide'.$idw.($cssonholiday?' '.$cssonholiday:'').($cssweekend?' '.$cssweekend:'').'">';
					$placeholder='';
					if ($alreadyspent)
					{
						$tableCell.='<span class="timesheetalreadyrecorded" title="texttoreplace"><input type="text" class="center smallpadd" size="2" disabled id="timespent['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="'.$alreadyspent.'"></span>';
						//$placeholder=' placeholder="00:00"';
					 	//$tableCell.='+';
					}
				  	$tableCell.='<input type="text" alt="'.($disabledtask?'':$alttitle).'" title="'.($disabledtask?'':$alttitle).'" '.($disabledtask?'disabled':$placeholder).' class="center smallpadd" size="2" id="timeadded['.$inc.']['.$idw.']" name="task['.$lines[$i]->id.']['.$idw.']" value="" cols="2"  maxlength="5"';
					$tableCell.=' onkeypress="return regexEvent(this,event,\'timeChar\')"';
				   	$tableCell.=' onkeyup="updateTotal('.$idw.',\''.$modeinput.'\')"';
				   	$tableCell.=' onblur="regexEvent(this,event,\''.$modeinput.'\'); updateTotal('.$idw.',\''.$modeinput.'\')" />';
				   	$tableCell.='</td>';
					print $tableCell;
				}

				// Warning
				print '<td align="right">';
   				if ((! $lines[$i]->public) && $disabledproject) print $form->textwithpicto('',$langs->trans("UserIsNotContactOfProject"));
   				else if ($disabledtask)
   				{
   					$titleassigntask = $langs->trans("AssignTaskToMe");
   					if ($fuser->id != $user->id) $titleassigntask = $langs->trans("AssignTaskToUser", '...');

   					print $form->textwithpicto('',$langs->trans("TaskIsNotAssignedToUser", $titleassigntask));
   				}
				print '</td>';

				print "</tr>\n";
			}

			// Call to show task with a lower level (task under the current task)
			$inc++;
			$level++;
			if ($lines[$i]->id > 0)
			{
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level);
				//var_dump($totalforeachday);
				$ret = projectLinesPerWeek($inc, $firstdaytoshow, $fuser, $lines[$i]->id, ($parent == 0 ? $lineswithoutlevel0 : $lines), $level, $projectsrole, $tasksrole, $mine, $restricteditformytask, $isavailable, $oldprojectforbreak);
				//var_dump('ret with parent='.$lines[$i]->id.' level='.$level);
				//var_dump($ret);
				foreach($ret as $key => $val)
				{
					$totalforeachday[$key]+=$val;
				}
				//var_dump('totalforeachday after taskid='.$lines[$i]->id.' and previous one on level '.$level.' + subtasks');
				//var_dump($totalforeachday);
			}
			$level--;
		}
		else
		{
			//$level--;
		}
	}

	return $totalforeachday;
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
 * @param	Form	$form				Object form
 * @param   int		$socid				Id thirdparty
 * @param   int		$projectsListId     Id of project I have permission on
 * @param   int		$mytasks            Limited to task I am contact to
 * @param	int		$statut				-1=No filter on statut, 0 or 1 = Filter on status
 * @param	array	$listofoppstatus	List of opportunity status
 * @param   array   $hiddenfields       List of info to not show ('projectlabel', 'declaredprogress', '...', )
 * @return	void
 */
function print_projecttasks_array($db, $form, $socid, $projectsListId, $mytasks=0, $statut=-1, $listofoppstatus=array(),$hiddenfields=array())
{
	global $langs,$conf,$user,$bc;

	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

	$projectstatic=new Project($db);
	$thirdpartystatic=new Societe($db);

	$sortfield='';
	$sortorder='';
	$project_year_filter=0;

	$title=$langs->trans("Projects");
	if (strcmp($statut, '') && $statut >= 0) $title=$langs->trans("Projects").' '.$langs->trans($projectstatic->statuts_long[$statut]);

	$arrayidtypeofcontact=array();

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';

	$sql= " FROM ".MAIN_DB_PREFIX."projet as p";
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
	$sql.= " WHERE p.entity IN (".getEntity('project').")";
	$sql.= " AND p.rowid IN (".$projectsListId.")";
	if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
	if ($mytasks)
	{
		$sql.= " AND p.rowid = t.fk_projet";
		$sql.= " AND ec.element_id = t.rowid";
		$sql.= " AND ec.fk_socpeople = ".$user->id;
		$sql.= " AND ec.fk_c_type_contact = ctc.rowid";   // Replace the 2 lines with ec.fk_c_type_contact in $arrayidtypeofcontact
		$sql.= " AND ctc.element = 'project_task'";
	}
	if ($statut >= 0)
	{
		$sql.= " AND p.fk_statut = ".$statut;
	}
	if (!empty($conf->global->PROJECT_LIMIT_YEAR_RANGE))
	{
		$project_year_filter = GETPOST("project_year_filter");
		//Check if empty or invalid year. Wildcard ignores the sql check
		if ($project_year_filter != "*")
		{
			if (empty($project_year_filter) || !ctype_digit($project_year_filter))
			{
				$project_year_filter = date("Y");
			}
			$sql.= " AND (p.dateo IS NULL OR p.dateo <= ".$db->idate(dol_get_last_day($project_year_filter,12,false)).")";
			$sql.= " AND (p.datee IS NULL OR p.datee >= ".$db->idate(dol_get_first_day($project_year_filter,1,false)).")";
		}
	}

	// Get id of project we must show tasks
	$arrayidofprojects=array();
	$sql1 = "SELECT p.rowid as projectid";
	$sql1.= $sql;
	$resql = $db->query($sql1);
	if ($resql)
	{
		$i=0;
		$num = $db->num_rows($resql);
		while ($i < $num)
		{
			$objp = $db->fetch_object($resql);
			$arrayidofprojects[$objp->projectid]=$objp->projectid;
			$i++;
		}
	}
	else dol_print_error($db);
	if (empty($arrayidofprojects)) $arrayidofprojects[0]=-1;

	// Get list of project with calculation on tasks
	$sql2 = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_soc, s.nom as socname, p.fk_user_creat, p.public, p.fk_statut as status, p.fk_opp_status as opp_status, p.opp_amount,";
	$sql2.= " p.dateo, p.datee,";
	$sql2.= " COUNT(t.rowid) as nb, SUM(t.planned_workload) as planned_workload, SUM(t.planned_workload * t.progress / 100) as declared_progess_workload";
	$sql2.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql2.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
	$sql2.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
	$sql2.= " WHERE p.rowid IN (".join(',',$arrayidofprojects).")";
	$sql2.= " GROUP BY p.rowid, p.ref, p.title, p.fk_soc, s.nom, p.fk_user_creat, p.public, p.fk_statut, p.fk_opp_status, p.opp_amount, p.dateo, p.datee";
	$sql2.= " ORDER BY p.title, p.ref";

	$resql = $db->query($sql2);
	if ($resql)
	{
	   $total_task = 0;
		$total_opp_amount = 0;
		$ponderated_opp_amount = 0;

		$num = $db->num_rows($resql);
		$i = 0;

		print '<tr class="liste_titre">';
		print_liste_field_titre($title.' <span class="badge">'.$num.'</span>',$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
		print_liste_field_titre("ThirdParty",$_SERVER["PHP_SELF"],"","","","",$sortfield,$sortorder);
		if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
		{
			print_liste_field_titre("OpportunityAmount","","","","",'align="right"',$sortfield,$sortorder);
			print_liste_field_titre("OpportunityStatus","","","","",'align="right"',$sortfield,$sortorder);
		}
		if (empty($conf->global->PROJECT_HIDE_TASKS))
		{
			print_liste_field_titre("Tasks","","","","",'align="right"',$sortfield,$sortorder);
			if (! in_array('plannedworkload', $hiddenfields))  print_liste_field_titre("PlannedWorkload","","","","",'align="right"',$sortfield,$sortorder);
			if (! in_array('declaredprogress', $hiddenfields)) print_liste_field_titre("ProgressDeclared","","","","",'align="right"',$sortfield,$sortorder);
		}
		print_liste_field_titre("Status","","","","",'align="right"',$sortfield,$sortorder);
		print "</tr>\n";

		$total_plannedworkload=0;
		$total_declaredprogressworkload=0;
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
				$projectstatic->ref=$objp->ref;
				$projectstatic->statut = $objp->status;
				$projectstatic->title = $objp->title;
				$projectstatic->datee = $db->jdate($objp->datee);
				$projectstatic->dateo = $db->jdate($objp->dateo);


				print '<tr class="oddeven">';
				print '<td>';
				print $projectstatic->getNomUrl(1);
				if (! in_array('projectlabel', $hiddenfields)) print '<br>'.dol_trunc($objp->title,24);
				print '</td>';
				print '<td>';
				if ($objp->fk_soc > 0)
				{
					$thirdpartystatic->id=$objp->fk_soc;
					$thirdpartystatic->ref=$objp->socname;
					$thirdpartystatic->name=$objp->socname;
					print $thirdpartystatic->getNomUrl(1);
				}
				print '</td>';
				if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
				{
					print '<td align="right">';
					if ($objp->opp_amount) print price($objp->opp_amount, 0, '', 1, -1, -1, $conf->currency);
					print '</td>';
					print '<td align="right">';
					$code = dol_getIdFromCode($db, $objp->opp_status, 'c_lead_status', 'rowid', 'code');
					if ($code) print $langs->trans("OppStatus".$code);
					print '</td>';
				}
				if (empty($conf->global->PROJECT_HIDE_TASKS))
				{
					print '<td align="right">'.$objp->nb.'</td>';

					$plannedworkload=$objp->planned_workload;
					$total_plannedworkload+=$plannedworkload;
					if (! in_array('plannedworkload', $hiddenfields))
					{
						print '<td align="right">'.($plannedworkload?convertSecondToTime($plannedworkload):'').'</td>';
					}
					if (! in_array('declaredprogress', $hiddenfields))
					{
						$declaredprogressworkload=$objp->declared_progess_workload;
						$total_declaredprogressworkload+=$declaredprogressworkload;
						print '<td align="right">';
						//print $objp->planned_workload.'-'.$objp->declared_progess_workload."<br>";
						print ($plannedworkload?round(100*$declaredprogressworkload/$plannedworkload,0).'%':'');
						print '</td>';
					}
				}

				print '<td align="right">'.$projectstatic->getLibStatut(3).'</td>';
				print "</tr>\n";

				$total_task = $total_task + $objp->nb;
				$total_opp_amount = $total_opp_amount + $objp->opp_amount;
				$ponderated_opp_amount = $ponderated_opp_amount + price2num($listofoppstatus[$objp->opp_status] * $objp->opp_amount / 100);
			}

			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="2">'.$langs->trans("Total")."</td>";
		if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
		{
			print '<td class="liste_total" align="right">'.price($total_opp_amount, 0, '', 1, -1, -1, $conf->currency).'</td>';
			print '<td class="liste_total" align="right">'.$form->textwithpicto(price($ponderated_opp_amount, 0, '', 1, -1, -1, $conf->currency), $langs->trans("OpportunityPonderatedAmountDesc"), 1).'</td>';
		}
		if (empty($conf->global->PROJECT_HIDE_TASKS))
		{
			print '<td class="liste_total" align="right">'.$total_task.'</td>';
			if (! in_array('plannedworkload', $hiddenfields))  print '<td class="liste_total" align="right">'.($total_plannedworkload?convertSecondToTime($total_plannedworkload):'').'</td>';
			if (! in_array('declaredprogress', $hiddenfields)) print '<td class="liste_total" align="right">'.($total_plannedworkload?round(100*$total_declaredprogressworkload/$total_plannedworkload,0).'%':'').'</td>';
		}
		print '<td class="liste_total"></td>';
		print '</tr>';

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}

	print "</table>";
	print '</div>';

	if (!empty($conf->global->PROJECT_LIMIT_YEAR_RANGE))
	{
		//Add the year filter input
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';
		print '<table width="100%">';
		print '<tr>';
		print '<td>'.$langs->trans("Year").'</td>';
		print '<td style="text-align:right"><input type="text" size="4" class="flat" name="project_year_filter" value="'.$project_year_filter.'"/>';
		print "</tr>\n";
		print '</table></form>';
	}
}
