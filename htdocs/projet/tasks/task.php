<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 *
 */

/**
   \file       htdocs/projet/tasks/fiche.php
   \ingroup    projet
   \brief      Fiche tâches d'un projet
   \version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->projet->lire) accessforbidden();


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
      $head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$projet->id;
      $head[$h][1] = $langs->trans("Project");
      $h++;
      
      $head[$h][0] = DOL_URL_ROOT.'/projet/tasks/fiche.php?id='.$projet->id;
      $head[$h][1] = $langs->trans("Tasks");
      $hselected=$h;
      $h++;
      
      if ($conf->propal->enabled)
	{
	  $langs->load("propal");
	  $head[$h][0] = DOL_URL_ROOT.'/projet/propal.php?id='.$projet->id;
	  $head[$h][1] = $langs->trans("Proposals");
	  $h++;
	}  
      
      if ($conf->commande->enabled)
	{
	  $langs->load("orders");
	  $head[$h][0] = DOL_URL_ROOT.'/projet/commandes.php?id='.$projet->id;
	  $head[$h][1] = $langs->trans("Orders");
	  $h++;
	}
      
      if ($conf->facture->enabled)
	{
	  $langs->load("bills");
	  $head[$h][0] = DOL_URL_ROOT.'/projet/facture.php?id='.$projet->id;
	  $head[$h][1] = $langs->trans("Bills");
	  $h++;
	}
      
      dolibarr_fiche_head($head,  $hselected, $langs->trans("Project").": ".$projet->ref);
      
      print '<form method="POST" action="fiche.php?id='.$projet->id.'">';
      print '<input type="hidden" name="action" value="createtask">';
      print '<table class="border" width="100%">';
      print '<tr><td>'.$langs->trans("Project").'</td><td>'.$projet->title.'</td>';
      print '<td>'.$langs->trans("Company").'</td><td>'.$projet->societe->nom_url.'</td></tr>';
      print '<tr><td>'.$langs->trans("Task").'</td><td colspan="3">'.$task->title.'</td></tr>';
      
      /* Liste des tâches */
      
      $sql = "SELECT t.task_date, t.task_duration, t.fk_user, u.code";
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
      print '<td>'.$langs->trans("Task").'</td>';
      print '<td align="right">'.$langs->trans("DurationEffective").'</td>';
      print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
      print "</tr>\n";      
      
      foreach ($tasks as $task_time)
	{
	  print "<tr $bc[$var]>";
	  print '<td>'.$task_time[0].'</td>';
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
