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

Function PLines(&$inc, $parent, $lines, &$level)
{
  $form = new Form($db); // $db est null ici mais inutile pour la fonction select_date()
  global $bc, $langs;
  for ($i = 0 ; $i < sizeof($lines) ; $i++)
    {
      if ($parent == 0)
	$level = 0;

      if ($lines[$i][1] == $parent)
	{
	  $var = !$var;
	  print "<tr $bc[$var]>\n<td>";
	  print $lines[$i][4].'</td><td>';

	  for ($k = 0 ; $k < $level ; $k++)
	    {
	      print "&nbsp;&nbsp;&nbsp;";
	    }

	  print '<a href="task.php?id='.$lines[$i][2].'">'.$lines[$i][0]."</a></td>\n";

	  $heure = intval($lines[$i][3]);
	  $minutes = (($lines[$i][3] - $heure) * 60);
	  $minutes = substr("00"."$minutes", -2);

	  print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";
	  print '<td><input size="4" type="text" class="flat" name="task'.$lines[$i][2].'" value="">';
	  print '&nbsp;<input type="submit" class="flat" value="'.$langs->trans("Save").'"></td>';
	  print "</tr>\n";
	  $inc++;
	  $level++;
	  PLines($inc, $lines[$i][2], $lines, $level);
	  $level--;
	}
      else
	{
	  //$level--;
	}
    }
}

llxHeader("",$langs->trans("Mytasks"),"Projet");

/*
 * Fiche projet en mode visu
 *
 */

$h=0;
$head[$h][0] = DOL_URL_ROOT.'/projet/fiche.php?id='.$projet->id;
$head[$h][1] = $langs->trans("Myprojects");
$h++;

dolibarr_fiche_head($head,  $hselected, $langs->trans("Mytasks"));

/* Liste des tâches */

$sql = "SELECT t.rowid, t.title, t.fk_task_parent, t.duration_effective, p.title as ptitle";
$sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t";
$sql .= " , ".MAIN_DB_PREFIX."projet as p";
$sql .= " WHERE p.rowid = t.fk_projet";
$sql .= " ORDER BY t.fk_task_parent";

$var=true;
$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  $tasks = array();      
  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      $tasks[$i][0] = $obj->title; 
      $tasks[$i][1] = $obj->fk_task_parent; 
      $tasks[$i][2] = $obj->rowid;
      $tasks[$i][3] = $obj->duration_effective; 
      $tasks[$i][4] = $obj->ptitle;
      $i++;
    }
  $db->free();
}
else
{
  dolibarr_print_error($db);
}

print '<form method="POST" action="fiche.php?id='.$projet->id.'">';
print '<input type="hidden" name="action" value="addtime">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Project").'</td>';
print '<td>'.$langs->trans("Task").'</td>';
print '<td align="right">'.$langs->trans("DurationEffective").'</td>';
print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
print "</tr>\n";      
PLines($j, 0, $tasks, $level);
print '</form>';

print "</table>";    
print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
