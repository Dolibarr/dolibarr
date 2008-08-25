<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/projet/tasks/task.php
   \ingroup    projet
   \brief      Fiche tâches d'un projet
   \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

if (!$user->rights->projet->lire) accessforbidden();

/*
 * View
 */

llxHeader("",$langs->trans("Task"));

if ($_GET["id"] > 0)
{
  
  /*
   * Fiche projet en mode visu
   *
   */
  $task = new Task($db);
  if ($task->fetch($_GET["id"]) == 0 )
    {
      $projet = new Project($db);
      $projet->fetch($task->projet_id);
      $projet->societe->fetch($projet->societe->id);
      
      $h=0;
      $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/task.php?id='.$task->id;
      $head[$h][1] = $langs->trans("Tasks");
      $head[$h][2] = 'tasks';
      $h++;
      
      dolibarr_fiche_head($head, 'tasks', $langs->trans("Tasks"));
      
      print '<form method="POST" action="fiche.php?id='.$projet->id.'">';
      print '<input type="hidden" name="action" value="createtask">';
      print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';      

      print '<td>'.$langs->trans("Company").'</td><td>'.$projet->societe->getNomUrl(1).'</td></tr>';
      print '<tr><td>'.$langs->trans("Task").'</td><td colspan="3">'.$task->title.'</td></tr>';
      
      /* Liste des tâches */
      
      $sql = "SELECT t.task_date, t.task_duration, t.fk_user, u.login";
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
	      $row = $db->fetch_row($resql);
	      $tasks[$i] = $row; 
	      $i++;
	    }
	  $db->free();
	}
      else
	{
	  dolibarr_print_error($db);
	}
      
      
      /* Nouvelle tâche */          
      print '</table></form><br />';

      print '<input type="hidden" name="action" value="addtime">';
      print '<table class="noborder" width="100%">';
      print '<tr class="liste_titre">';
      print '<td>'.$langs->trans("Date").'</td>';
      print '<td>'.$langs->trans("DurationEffective").'</td>';
      print '<td colspan="2">'.$langs->trans("User").'</td>';
      print "</tr>\n";      
      
      foreach ($tasks as $task_time)
	{
		$var=!$var;
	  print "<tr ".$bc[$var].">";
	  print '<td>'.dolibarr_print_date($task_time[0]).'</td>';
	  print '<td>'.$task_time[1].'</td>';
	  print '<td>'.$task_time[3].'</td>';
	  print "</tr>\n";
	}
            
      print "</table>";    
      print '</div>';
    }    
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
