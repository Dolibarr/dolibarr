<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
 *	\file       htdocs/projet/tasks/task.php
 *	\ingroup    projet
 *	\brief      Fiche t�ches d'un projet
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

if (!$user->rights->projet->lire) accessforbidden();

/*
 * Actions
 */





/*
 * View
 */

llxHeader("",$langs->trans("Task"));

$html = new Form($db);

$projectstatic = new Project($db);


if ($_GET["id"] > 0)
{
	/*
	 * Fiche projet en mode visu
	 *
	 */
	$task = new Task($db);
	if ($task->fetch($_GET["id"]) >= 0 )
	{
		$projet = new Project($db);
		$result=$projet->fetch($task->fk_projet);
		if (! empty($projet->socid)) $projet->societe->fetch($projet->socid);

		$head=task_prepare_head($task);

		dol_fiche_head($head, 'time', $langs->trans("Task"),0,'projecttask');

		if ($mesg) print $mesg.'<br>';

		print '<form method="POST" action="fiche.php?id='.$projet->id.'">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="createtask">';
		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="30%">';
		print $langs->trans("Ref");
		print '</td><td colspan="3">';
		print $html->showrefnav($task,'id','',1,'rowid','ref','','');
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$task->title.'</td></tr>';

		print '<tr><td>'.$langs->trans("Project").'</td><td>';
		print $projet->getNomUrl(1);
		print '</td></tr>';

		print '<td>'.$langs->trans("Company").'</td><td>';
		if ($projet->societe->id) print $projet->societe->getNomUrl(1);
		else print '&nbsp;';
		print '</td></tr>';

		/* List of time spent */

		$sql = "SELECT t.task_date, t.task_duration, t.fk_user, u.login, u.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
		$sql .= " , ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE t.fk_task =".$task->id;
		$sql .= " AND t.fk_user = u.rowid";
		$sql .= " ORDER BY t.task_date DESC";

		$var=true;
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			$tasks = array();
			while ($i < $num)
			{
				$row = $db->fetch_object($resql);
				$tasks[$i] = $row;
				$i++;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}

		print '</table></form>';
		print '</div>';


		/*
		 * Actions
		 */
		print '<div class="tabsAction">';

		/*
		if ($user->rights->projet->creer)
		{
			print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?id='.$task->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
		}
		*/

		print '</div>';

		print '<br>';
		print '<input type="hidden" name="action" value="addtime">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td align="right">'.$langs->trans("TimeSpent").'</td>';
		print '<td align="right">'.$langs->trans("By").'</td>';
		print "</tr>\n";

		foreach ($tasks as $task_time)
		{
			$var=!$var;
  		    print "<tr ".$bc[$var].">";

  		    // Date
  		    print '<td>'.dol_print_date($db->jdate($task_time->task_date),'day').' '.dol_print_date($db->jdate($task_time->task_date),'%A').'</td>';

		    // Time spent
		    $heure = intval($task_time->task_duration);
			$minutes = round((($task_time->task_duration - $heure) * 60),0);
			$minutes = substr("00"."$minutes", -2);
			print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";

			// User
			$user->id=$task_time->rowid;
		    $user->nom=$task_time->login;
		    print '<td align="right">'.$user->getNomUrl(1).'</td>';
		    print "</tr>\n";
		}

		print "</table>";

	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
