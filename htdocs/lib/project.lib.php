<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/lib/project.lib.php
 *		\brief      Ensemble de fonctions de base pour le module projet
 *      \ingroup    societe
 *      \version    $Id$
 */
function project_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Project");
    $head[$h][2] = 'project';
	$h++;

	if ($conf->propal->enabled || $conf->commande->enabled || $conf->facture->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Referers");
	    $head[$h][2] = 'element';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/projet/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans("ProjectContact");
    $head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/document.php?id='.$object->id;
	/*$filesdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['project']))
    {
        $i=0;
        foreach ($conf->tabs_modules['project'] as $value)
        {
            $values=explode(':',$value);
            if ($values[2]) $langs->load($values[2]);
            $head[$h][0] = DOL_URL_ROOT . preg_replace('/__ID__/i',$object->id,$values[3]);
            $head[$h][1] = $langs->trans($values[1]);
            $head[$h][2] = 'tab'.$values[1];
            $h++;
        }
    }

    // Then tab for sub level of projet, i mean tasks
	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Tasks");
    $head[$h][2] = 'tasks';
	$h++;

	/* Now this is a filter in the Task tab.
	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$object->id.'&mode=mine';
	$head[$h][1] = $langs->trans("MyTasks");
    $head[$h][2] = 'mytasks';
	$h++;
	*/

	$head[$h][0] = DOL_URL_ROOT.'/projet/ganttview.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Gantt");
   	$head[$h][2] = 'gantt';
   	$h++;

	return $head;
}


/**
 *	    \file       htdocs/lib/project.lib.php
 *		\brief      Ensemble de fonctions de base pour le module projet
 *      \ingroup    societe
 *      \version    $Id$
 */
function task_prepare_head($object)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/task.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'task';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/contact.php?id='.$object->id;
	$head[$h][1] = $langs->trans("TaskRessourceLinks");
	$head[$h][2] = 'contact';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id;
	$head[$h][1] = $langs->trans("TimeSpent");
	$head[$h][2] = 'time';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/document.php?id='.$object->id;
	/*$filesdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($object->ref);
	include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');
	$listoffiles=dol_dir_list($filesdir,'files',1);
	$head[$h][1] = (sizeof($listoffiles)?$langs->trans('DocumentsNb',sizeof($listoffiles)):$langs->trans('Documents'));*/
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'document';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Notes');
	$head[$h][2] = 'note';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:MyModule:@mymodule:/mymodule/mypage.php?id=__ID__');
	if (is_array($conf->tabs_modules['task']))
    {
        $i=0;
        foreach ($conf->tabs_modules['task'] as $value)
        {
            $values=explode(':',$value);
            if ($values[2]) $langs->load($values[2]);
            $head[$h][0] = DOL_URL_ROOT . preg_replace('/__ID__/i',$objsoc->id,$values[3]);
            $head[$h][1] = $langs->trans($values[1]);
            $head[$h][2] = 'tab'.$values[1];
            $h++;
        }
    }

	return $head;
}



/**
 *		\brief      Show a combo list with projects qualified for a third party)
 *		\param      socid       Id third party (-1=all, 0=projects not linked to a third party, id=projects not linked or linked to third party id)
 *		\param      selected    Id project preselected
 *		\param      htmlname    Nom de la zone html
 *		\return     int         Nbre de projet si ok, <0 si ko
 */
function select_projects($socid=-1, $selected='', $htmlname='projectid')
{
	global $db,$user,$conf,$langs;

	// On recherche les projets
	$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public';
	$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
	$sql.= " WHERE p.entity = ".$conf->entity;
	if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
	if ($socid > 0) $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc='0' OR p.fk_soc IS NULL)";
	$sql.= " ORDER BY p.title ASC";

	dol_syslog("project.lib::select_projects sql=".$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		print '<select class="flat" name="'.$htmlname.'">';
		print '<option value="0">&nbsp;</option>';
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				$labeltoshow=dol_trunc($obj->ref,12).' - '.dol_trunc($obj->title,12);
				//if ($obj->public) $labeltoshow.=' ('.$langs->trans("SharedProject").')';
				//else $labeltoshow.=' ('.$langs->trans("Private").')';
				if (!empty($selected) && $selected == $obj->rowid && $obj->fk_statut > 0)
				{
					print '<option value="'.$obj->rowid.'" selected="true">'.$labeltoshow.'</option>';
				}
				else
				{
					print '<option value="'.$obj->rowid.'"';
					if (! $obj->fk_statut > 0)
					{
						print ' disabled="true"';
						$labeltoshow.=' - '.$langs->trans("Draft");
					}
					print '>'.$labeltoshow.'</option>';
				}
				$i++;
			}
		}
		print '</select>';
		$db->free($resql);
		return $num;
	}
	else
	{
		dol_print_error($db);
		return -1;
	}
}


/**
 * Enter description here...
 *
 * @param unknown_type $inc
 * @param unknown_type $parent
 * @param unknown_type $lines
 * @param unknown_type $level
 * @param unknown_type $tasksrole
 * @return unknown
 */
function PLinesb(&$inc, $parent, $lines, &$level, &$projectsrole)
{
	global $user, $bc, $langs;
	global $form;

	$lastprojectid=0;

	$projectstatic = new Project($db);
	$taskstatic = new Task($db);

	$var=true;

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
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

			print "<tr $bc[$var]>\n";

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
			print $lines[$i]->label;
			print "</td>\n";

			// Time spent
			print '<td align="right">';
			if ($lines[$i]->duration) print ConvertSecondToTime($lines[$i]->duration,'all');
			else print '--:--';
			print "</td>\n";

			$disabled=1;
			//print "x".$lines[$i]->fk_project;
			//var_dump($lines[$i]);
			//var_dump($projectsrole[$lines[$i]->fk_project]);
			// If at least one role for project
			if ($lines[$i]->public || ! empty($projectsrole[$lines[$i]->fk_project]) || $user->rights->projet->all->creer) $disabled=0;

			print '<td nowrap="nowrap">';
			print $form->select_date('',$lines[$i]->id,'','','',"addtime");
			print '&nbsp;&nbsp;&nbsp;';
			print $form->select_duration($lines[$i]->id,'',$disabled);
			print '&nbsp;<input type="submit" class="button"'.($disabled?' disabled="true"':'').' value="'.$langs->trans("Add").'">';
			if ((! $lines[$i]->public) && $disabled) print '('.$langs->trans("YouAreNotContactOfProject").')';
			print '</td>';
			print "<td>&nbsp;";
			print '</td>';

			print "</tr>\n";
			$inc++;
			$level++;
			if ($lines[$i]->id) PLinesb($inc, $lines[$i]->id, $lines, $level, $projectsrole);
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
 * Show task lines with a particular parent
 * @param 	$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	$parent				Id of parent task to start
 * @param 	$lines				Array of all tasks
 * @param 	$level				Level of task
 * @param 	$var				Color
 * @param 	$showproject		Show project columns
 * @param	$taskrole			Array of roles of user for each tasks
 */
function PLines(&$inc, $parent, &$lines, &$level, $var, $showproject, &$taskrole)
{
	global $user, $bc, $langs;

	$lastprojectid=0;

	$projectstatic = new Project($db);
	$taskstatic = new Task($db);

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
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
					SearchTaskInChild($foundtaskforuserdeeper,$lines[$i]->id,$lines,$taskrole);
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

			if ($showline)
			{
				// Break on a new project
				if ($parent == 0 && $lines[$i]->fk_project != $lastprojectid)
				{
					$var = !$var;
					$lastprojectid=$lines[$i]->fk_project;
				}

				print "<tr ".$bc[$var].">\n";

				// Project
				if ($showproject)
				{
					print "<td>";
					//var_dump($taskrole);
					if ($showlineingray) print '<i>';
					$projectstatic->id=$lines[$i]->fk_project;
					$projectstatic->ref=$lines[$i]->projectref;
					$projectstatic->public=$lines[$i]->public;
					print $projectstatic->getNomUrl(1);
					if ($showlineingray) print '</i>';
					print "</td>";
				}

				// Ref of task
				print '<td>';
				if ($showlineingray)
				{
					print '<i>'.img_object('','projecttask').' '.$lines[$i]->id.'</i>';
				}
				else
				{
					$taskstatic->id=$lines[$i]->id;
					$taskstatic->ref=$lines[$i]->id;
					$taskstatic->label=($taskrole[$lines[$i]->id]?$langs->trans("YourRole").': '.$taskrole[$lines[$i]->id]:'');
					print $taskstatic->getNomUrl(1);
				}
				print '</td>';

				// Title of task
				print "<td>";
				if ($showlineingray) print '<i>';
				else print '<a href="task.php?id='.$lines[$i]->id.'">';
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp; &nbsp; &nbsp;";
				}
				print $lines[$i]->label;
				if ($showlineingray) print '</i>';
				else print '</a>';
				print "</td>\n";

				// Progress
				print '<td align="right">';
				print $lines[$i]->progress.' %';
				print '</td>';

				// Time spent
				print '<td align="right">';
				if ($showlineingray) print '<i>';
				else print '<a href="time.php?id='.$lines[$i]->id.'">';
				if ($lines[$i]->duration) print ConvertSecondToTime($lines[$i]->duration,'all');
				else print '--:--';
				if ($showlineingray) print '</i>';
				else print '</a>';
				print '</td>';

				print "</tr>\n";

				if (! $showlineingray) $inc++;

				$level++;
				if ($lines[$i]->id) PLines($inc, $lines[$i]->id, $lines, $level, $var, $showproject, $taskrole);
				$level--;
			}
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
 * @param 	$inc				Counter that count number of lines legitimate to show (for return)
 * @param 	$parent				Id of parent task to start
 * @param 	$lines				Array of all tasks
 * @param	$taskrole			Array of task filtered on a particular user
 * @return	int					1 if there is
 */
function SearchTaskInChild(&$inc, $parent, &$lines, &$taskrole)
{
	//print 'Search in line with parent id = '.$parent.'<br>';
	for ($i = 0 ; $i < sizeof($lines) ; $i++)
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

			SearchTaskInChild($inc, $lines[$i]->id, $lines, $taskrole);
			//print 'Found inc='.$inc.'<br>';

			if ($inc > 0) return $inc;
		}
	}

	return $inc;
}


/**
 * Clean task not linked to a parent
 * @param unknown_type $db
 * @return		int		Nb of records deleted
 */
function clean_orphelins($db)
{
	$nb=0;

	// There is orphelins. We clean that
	$listofid=array();

	// Get list of id in array listofid
	$sql='SELECT rowid FROM '.MAIN_DB_PREFIX.'projet_task';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num && $i < 100)
		{
			$obj = $db->fetch_object($resql);
			$listofid[]=$obj->rowid;
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}

	if (sizeof($listofid))
	{
		// Removed orphelins records
		print 'Some orphelins were found and restored to be parents so records are visible again: ';
		print join(',',$listofid);

		$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
		$sql.= " SET fk_task_parent = 0";
		$sql.= " WHERE fk_task_parent NOT IN (".join(',',$listofid).")";

		$resql = $db->query($sql);
		if ($resql)
		{
			$nb=$db->affected_rows($sql);

			return $nb;
		}
		else
		{
			return -1;
		}
	}
}


/**
 * Return HTML table with list of projects and number of opened tasks
 *
 * @param unknown_type $db
 * @param unknown_type $mine
 * @param unknown_type $socid
 * @param unknown_type $projectsListId
 */
function print_projecttasks_array($db,$mine,$socid,$projectsListId)
{
	global $langs,$conf,$user,$bc;

	require_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");

	$projectstatic=new Project($db);

	$sortfield='';
	$sortorder='';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Project"),"index.php","","","","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("NbOpenTasks"),"","","","",'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"","","","",'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut, COUNT(t.rowid) as nb";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task as t ON p.rowid = t.fk_projet";
	$sql.= " WHERE p.entity = ".$conf->entity;
	if ($mine) $sql.= " AND p.rowid IN (".$projectsListId.")";
	if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
	$sql.= " GROUP BY p.rowid, p.ref, p.title, p.fk_user_creat, p.public, p.fk_statut";

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

			$userAccess = $projectstatic->restrictedProjectArea($user,1);

			if ($userAccess >= 0)
			{
				$var=!$var;
				print "<tr ".$bc[$var].">";
				print '<td nowrap="nowrap">';
				$projectstatic->ref=$objp->ref;
				print $projectstatic->getNomUrl(1);
				print ' - '.$objp->title.'</td>';
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
}

?>