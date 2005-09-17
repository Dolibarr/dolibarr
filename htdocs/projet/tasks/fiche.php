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

Function PLines(&$inc, $parent, $lines, &$level, $actors)
{
  $form = new Form($db); // $db est null ici mais inutile pour la fonction select_date()
  global $user, $bc, $langs;
  for ($i = 0 ; $i < sizeof($lines) ; $i++)
    {
      if ($parent == 0)
	$level = 0;

      if ($lines[$i][1] == $parent)
	{
	  $var = !$var;
	  print "<tr $bc[$var]>\n<td>";

	  for ($k = 0 ; $k < $level ; $k++)
	    {
	      print "&nbsp;&nbsp;&nbsp;";
	    }

	  print '<a href="task.php?id='.$lines[$i][2].'">'.$lines[$i][0]."</a></td>\n";

	  $heure = intval($lines[$i][3]);
	  $minutes = (($lines[$i][3] - $heure) * 60);
	  $minutes = substr("00"."$minutes", -2);

	  print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";

	  // TODO améliorer le test

	  if ($actors[$lines[$i][2]] == 'admin')
	    {
	      print '<td><input size="4" type="text" class="flat" name="task'.$lines[$i][2].'" value="">';
	      print '&nbsp;<input type="submit" class="flat" value="'.$langs->trans("Save").'"></td>';
	      print "\n<td>";
	      print $form->select_date('',$lines[$i][2]);
	      print '</td>';
	    }
	  else
	    {
	      print '<td colspan="2">&nbsp;</td>';
	    }
	  print "</tr>\n";
	  $inc++;
	  $level++;
	  PLines($inc, $lines[$i][2], $lines, $level, $actors);
	  $level--;
	}
      else
	{
	  //$level--;
	}
    }
}

Function PLineSelect(&$inc, $parent, $lines, &$level)
{
  for ($i = 0 ; $i < sizeof($lines) ; $i++)
    {
      if ($parent == 0)
	$level = 0;

      if ($lines[$i][1] == $parent)
	{
	  $var = !$var;
	  print '<option value="'.$lines[$i][2].'">';

	  for ($k = 0 ; $k < $level ; $k++)
	    {
	      print "&nbsp;&nbsp;&nbsp;";
	    }

	  print $lines[$i][0]."</option>\n";

	  $inc++;
	  $level++;
	  PLineSelect($inc, $lines[$i][2], $lines, $level);
	  $level--;
	}
    }
}


if ($_POST["action"] == 'createtask' && $user->rights->projet->creer)
{
  $pro = new Project($db);

  $result = $pro->fetch($_GET["id"]);
  
  if ($result == 0)
    {

      $pro->CreateTask($user, $_POST["task_name"], $_POST["task_parent"]);

      Header("Location:fiche.php?id=".$pro->id);
    }
}

if ($_POST["action"] == 'addtime' && $user->rights->projet->creer)
{
  $pro = new Project($db);
  $result = $pro->fetch($_GET["id"]);
  
  if ($result == 0)
    {
      foreach ($_POST as $key => $post)
	{
	  //$pro->CreateTask($user, $_POST["task_name"]);
	  if (substr($key,0,4) == 'task')
	    {
	      if ($post > 0)
		{
		  $id = ereg_replace("task","",$key);

		  $date = mktime(12,12,12,$_POST["$id"."month"],$_POST["$id"."day"],$_POST["$id"."year"]);
		  $pro->TaskAddTime($user, $id , $post, $date);
		}
	    }
	}
      
      Header("Location:fiche.php?id=".$pro->id);
    }
}



llxHeader("",$langs->trans("Project"),"Projet");


if ($_GET["action"] == 'create' && $user->rights->projet->creer)
{
  print_titre($langs->trans("NewProject"));

  if ($mesg) print $mesg;
  
  print '<form action="fiche.php?socidp='.$_GET["socidp"].'" method="post">';

  print '<table class="border" width="100%">';
  print '<input type="hidden" name="action" value="add">';
  print '<tr><td>'.$langs->trans("Company").'</td><td>';

  $societe = new Societe($db);
  $societe->fetch($_GET["socidp"]); 
  print $societe->nom_url;

  print '</td></tr>';

  print '<tr><td>'.$langs->trans("Author").'</td><td>'.$user->fullname.'</td></tr>';

  print '<tr><td>'.$langs->trans("Ref").'</td><td><input size="10" type="text" name="ref"></td></tr>';
  print '<tr><td>'.$langs->trans("Label").'</td><td><input size="30" type="text" name="title"></td></tr>';
  print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Create").'"></td></tr>';
  print '</table>';
  print '</form>';

} else {

  /*
   * Fiche projet en mode visu
   *
   */

  $projet = new Project($db);
  $projet->fetch($_GET["id"]);
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


  /* Liste des acteurs */
  $sql = "SELECT a.fk_projet_task, a.role";
  $sql .= " FROM ".MAIN_DB_PREFIX."projet_task_actors as a";
  $sql .= " WHERE a.fk_user = ".$user->id;
  
  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0;
      $actors = array();      
      while ($i < $num)
	{
	  $row = $db->fetch_row($resql);
	  $actors[$row[0]] = $row[1]; 
	  $i++;
	}
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }
  
  /* Liste des tâches */

  $sql = "SELECT t.rowid, t.title, t.fk_task_parent, t.duration_effective";
  $sql .= " FROM ".MAIN_DB_PREFIX."projet_task as t";
  $sql .= " WHERE t.fk_projet =".$projet->id;
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
	  $i++;
	}
      $db->free();
    }
  else
    {
      dolibarr_print_error($db);
    }


  /* Nouvelle tâche */

  print '<tr><td>'.$langs->trans("NewTask").'</td><td colspan="3">';
  print '<input type="text" size="25" name="task_name" class="flat">&nbsp;';
  print '<select class="flat" name="task_parent">';
  print '<option value="0" selected="true">&nbsp;</option>';
  PLineSelect($j, 0, $tasks, $level);  
  print '</select>&nbsp;';
  print '<input type="submit" class="flat">';
  print '</td></tr>';

  print '</table></form><br />';

  print '<form method="POST" action="fiche.php?id='.$projet->id.'">';
  print '<input type="hidden" name="action" value="addtime">';
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Task").'</td>';
  print '<td align="right">'.$langs->trans("DurationEffective").'</td>';
  print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
  print "</tr>\n";      
  PLines($j, 0, $tasks, $level, $actors);
  print '</form>';

  
  print "</table>";    
  print '</div>';
  

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
