<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
function project_prepare_head($objsoc)
{
	global $langs, $conf, $user;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$objsoc->id;
	$head[$h][1] = $langs->trans("Project");
    $head[$h][2] = 'project';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$objsoc->id;
	$head[$h][1] = $langs->trans("Tasks");
    $head[$h][2] = 'tasks';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$objsoc->id.'&mode=mine';
	$head[$h][1] = $langs->trans("MyTasks");
    $head[$h][2] = 'mytasks';
	$h++;

	if ($conf->propal->enabled || $conf->commande->enabled || $conf->facture->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT.'/projet/element.php?id='.$objsoc->id;
		$head[$h][1] = $langs->trans("Referers");
	    $head[$h][2] = 'element';
		$h++;
	}

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

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id;
	$head[$h][1] = $langs->trans("TimeSpent");
	$head[$h][2] = 'time';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/who.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Affectations");
	$head[$h][2] = 'who';
	$h++;

	return $head;
}



/**
 *		\brief      Show a combo list with projects qualified for a third party)
 *		\param      socid       Id third party
 *		\param      selected    Id project preselected
 *		\param      htmlname    Nom de la zone html
 *		\return     int         Nbre de projet si ok, <0 si ko
 */
function select_projects($socid, $selected='', $htmlname='projectid')
{
	global $db;

	// On recherche les projets
	$sql = 'SELECT p.rowid, p.ref, p.title FROM ';
	$sql.= MAIN_DB_PREFIX .'projet as p';
	$sql.= " WHERE (fk_soc='".$socid."' or fk_soc IS NULL)";
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
				if (!empty($selected) && $selected == $obj->rowid)
				{
					print '<option value="'.$obj->rowid.'" selected="true">'.dol_trunc($obj->ref,12).' - '.dol_trunc($obj->title,12).'</option>';
				}
				else
				{
					print '<option value="'.$obj->rowid.'">'.dol_trunc($obj->ref,12).' - '.dol_trunc($obj->title,12).'</option>';
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
		dol_print_error($this->db);
		return -1;
	}
}



function PLinesb(&$inc, $parent, $lines, &$level, $tasksrole)
{
	global $user, $bc, $langs;
	global $form;

	$projectstatic = new Project($db);

	$var=true;

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		if ($parent == 0)
		$level = 0;

		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;
			print "<tr $bc[$var]>\n";

			// Ref
			print '<td><a href="'.DOL_URL_ROOT.'/projet/tasks/task.php?id='.$lines[$i]->id.'">'.$lines[$i]->id.'</a></td>';

			// Label task
			print "<td>";

			for ($k = 0 ; $k < $level ; $k++)
			{
				print "&nbsp;&nbsp;&nbsp;";
			}

			print $lines[$i]->title;
			print "</td>\n";

			// Project
			print "<td>";
			$projectstatic->id=$lines[$i]->projectid;
			$projectstatic->ref=$lines[$i]->projectref;
			print $projectstatic->getNomUrl(1);
			print "</td>";

			$heure = intval($lines[$i]->duration);
			$minutes = round((($lines[$i]->duration - $heure) * 60),0);
			$minutes = substr("00"."$minutes", -2);
			print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";

			if ($tasksrole[$lines[$i]->id] == 'admin')
			{
				print '<td nowrap="nowrap">';
				print $form->select_date('',$lines[$i]->id,'','','',"addtime");
				print '&nbsp;<input size="4" type="text" class="flat" name="task'.$lines[$i]->id.'" value="">';
				print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Add").'">';
				print '</td>';
				print "<td>&nbsp;";
				print '</td>';
			}
			else
			{
				print '<td colspan="2">&nbsp;</td>';
			}
			print "</tr>\n";
			$inc++;
			$level++;
			if ($lines[$i]->id) PLinesb($inc, $lines[$i]->id, $lines, $level, $tasksrole);
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
 * @param 	$parent				Id of parent task
 * @param 	$lines				Array of all tasks
 * @param 	$level				Level of task
 * @param 	$var				Color
 * @param 	$showproject		Show project columns
 * @param	$linesfiltered		''=No filter on users, Array=Shown tasks filtered on a particular user, the array contains tasks filtered
 */
function PLines(&$inc, $parent, &$lines, &$level, $var, $showproject=1, &$taskrole='')
{
	global $user, $bc, $langs;

	$lastprojectid=0;

	$projectstatic = new Project($db);

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		// Process line $lines[$i]

		if ($parent == 0) $level = 0;

		if ($lines[$i]->fk_parent == $parent)
		{
			// Break on a new project
			if ($parent == 0 && $lines[$i]->projectid != $lastprojectid)
			{
				$var = !$var;
				$lastprojectid=$lines[$i]->projectid;
			}

			// Show task line.
			$showline=1;
			$showlineingray=0;

			// If there is filters to use
			if (is_array($taskrole))
			{
				// If task not legitimate to show, search if a task exists later in tree
				if (! isset($taskrole[$lines[$i]->id]))
				{
					// So search if task has a subtask legitimate to show
					// FIXME
					//SearchPLine($foundtaskforuserafter,$lines[$i]->id,$lines,$taskrole);
					if ($foundtaskforuserlater)
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
				print "<tr ".$bc[$var].">\n";

				print '<td>';
				if (! $showlineingray) print '<a href="task.php?id='.$lines[$i]->id.'">';
				print $lines[$i]->id;
				if (! $showlineingray) print '</a>';
				print '</td>';

				print "<td>";
				for ($k = 0 ; $k < $level ; $k++)
				{
					print "&nbsp;&nbsp;&nbsp;";
				}
				print $lines[$i]->title;
				print "</td>\n";

				if ($showproject)
				{
					print "<td>";
					$projectstatic->id=$lines[$i]->projectid;
					$projectstatic->ref=$lines[$i]->projectref;
					print $projectstatic->getNomUrl(1);
					print "</td>";
				}

				$heure = intval($lines[$i]->duration);
				$minutes = round((($lines[$i]->duration - $heure) * 60),0);
				$minutes = substr("00"."$minutes", -2);
				print '<td align="right">';
				if (! $showlineingray) print '<a href="task.php?id='.$lines[$i]->id.'">';
				print $heure."&nbsp;h&nbsp;".$minutes;
				if (! $showlineingray) print '</a>';
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
 * Clean task not linked to a parent
 * @param unknown_type $db
 * @return		int		Nb of records deleted
 */
function clean_orphelins($db)
{
	$nb=0;

	// There is orphelins. We clean that
	$listofid=array();
	$sql='SELECT rowid from '.MAIN_DB_PREFIX.'projet_task';
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
		print 'Some orphelins were found and restored to be parents so records are visible again.';
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'projet_task set fk_task_parent = 0 where fk_task_parent';
		$sql.= ' NOT IN ('.join(',',$listofid).')';
		$resql = $db->query($sql);
		$nb=$db->affected_rows($sql);
	}

	return $nb;
}

?>