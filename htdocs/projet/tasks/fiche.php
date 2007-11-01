<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
		\file       htdocs/projet/tasks/fiche.php
		\ingroup    projet
		\brief      Fiche tâches d'un projet
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/project.lib.php");

$user->getrights('projet');

if (!$user->rights->projet->lire) accessforbidden();

/*
 * Sécurité accés client
 */
$projetid='';
if ($_GET["id"]) { $projetid=$_GET["id"]; }

if ($projetid == '') accessforbidden();

if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}

// Protection restriction commercial
if ($projetid && !$user->rights->commercial->client->voir)
{
        $sql = "SELECT p.rowid, p.fk_soc";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc, ".MAIN_DB_PREFIX."projet as p";
        $sql .= " WHERE p.rowid = ".$projetid;
        if (!$user->rights->commercial->client->voir) $sql .= " AND sc.fk_soc = p.fk_soc AND fk_user = ".$user->id;
        if ($socid) $sql .= " AND p.fk_soc = ".$socid;

        if ( $db->query($sql) )
        {
          if ( $db->num_rows() == 0) accessforbidden();
        }
}

Function PLines(&$inc, $parent, $lines, &$level, $tasksrole)
{
	$form = new Form($db); // $db est null ici mais inutile pour la fonction select_date()
	global $user, $bc, $langs;

	for ($i = 0 ; $i < sizeof($lines) ; $i++)
	{
		if ($parent == 0)
		$level = 0;

		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;
			print "<tr $bc[$var]>\n";

			print "<td>".$lines[$i]->id."</td>";

			print "<td>";

			for ($k = 0 ; $k < $level ; $k++)
			{
				print "&nbsp;&nbsp;&nbsp;";
			}

			print '<a href="task.php?id='.$lines[$i]->id.'">'.$lines[$i]->title."</a></td>\n";

			$heure = intval($lines[$i]->duration);
			$minutes = (($lines[$i]->duration - $heure) * 60);
			$minutes = substr("00"."$minutes", -2);

			print '<td align="right">'.$heure."&nbsp;h&nbsp;".$minutes."</td>\n";

			// TODO améliorer le test

			if ($tasksrole[$lines[$i]->id] == 'admin')
			{
				print '<td><input size="4" type="text" class="flat" name="task'.$lines[$i]->id.'" value="">';
				print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Save").'"></td>';
				print "\n<td>";
				print $form->select_date('',$lines[$i]->id,'','','',"addtime");
				print '</td>';
			}
			else
			{
				print '<td colspan="2">&nbsp;</td>';
			}
			print "</tr>\n";
			$inc++;
			$level++;
			PLines($inc, $lines[$i]->id, $lines, $level, $tasksrole);
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

		if ($lines[$i]->fk_parent == $parent)
		{
			$var = !$var;
			print '<option value="'.$lines[$i]->id.'">';

			for ($k = 0 ; $k < $level ; $k++)
			{
				print "&nbsp;&nbsp;&nbsp;";
			}

			print $lines[$i]->title."</option>\n";

			$inc++;
			$level++;
			PLineSelect($inc, $lines[$i]->id, $lines, $level);
			$level--;
		}
	}
}


if ($_POST["action"] == 'createtask' && $user->rights->projet->creer)
{
  $project = new Project($db);

  $result = $project->fetch($_GET["id"]);
  
  if ($result == 0)
  {
  	$task_parent = $_POST["task_parent"]?$_POST["task_parent"]:0;
  	$project->CreateTask($user, $_POST["task_name"], $task_parent);

    Header("Location:fiche.php?id=".$project->id);
  }
}

if ($_POST["action"] == 'addtime' && $user->rights->projet->creer)
{
  $project = new Project($db);
  $result = $project->fetch($_GET["id"]);
  
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
		  $project->TaskAddTime($user, $id , $post, $date);
		}
	    }
	}
      
      Header("Location:fiche.php?id=".$project->id);
      exit;
    }
}



llxHeader("",$langs->trans("Tasks"),"Tasks");


if ($_GET["action"] == 'create' && $user->rights->projet->creer)
{
  	print_titre($langs->trans("NewTask"));

  if ($mesg) print $mesg;
  
  print '<form action="fiche.php?socid='.$_GET["socid"].'" method="post">';

  print '<table class="border" width="100%">';
  print '<input type="hidden" name="action" value="add">';

  print '<tr><td>'.$langs->trans("Ref").'</td><td><input size="10" type="text" name="ref"></td></tr>';
  print '<tr><td>'.$langs->trans("Label").'</td><td><input size="30" type="text" name="title"></td></tr>';

  print '<tr><td>'.$langs->trans("Company").'</td><td>';
  $societe = new Societe($db);
  $societe->fetch($_GET["socid"]); 
  print $societe->getNomUrl(1);
  print '</td></tr>';

  print '<tr><td>'.$langs->trans("Author").'</td><td>'.$user->fullname.'</td></tr>';

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

	$head=project_prepare_head($projet);
	dolibarr_fiche_head($head, 'tasks', $langs->trans("Project"));


	print '<form method="POST" action="fiche.php?id='.$projet->id.'">';
	print '<input type="hidden" name="action" value="createtask">';
	print '<table class="border" width="100%">';

	print '<tr><td>'.$langs->trans("Ref").'</td><td>'.$projet->ref.'</td></tr>';
	print '<tr><td>'.$langs->trans("Label").'</td><td>'.$projet->title.'</td></tr>';

	print '<td>'.$langs->trans("Company").'</td><td>'.$projet->societe->getNomUrl(1).'</td></tr>';

	$tasksrole=$projet->getTasksRoleForUser($user);

	$tasksarray=$projet->getTasksArray();
	
	/* Nouvelle tâche */
	print '<tr><td>'.$langs->trans("NewTask").'</td><td colspan="3">';
	print '<input type="text" size="25" name="task_name" class="flat">&nbsp;';
	if ($tasksarray)
	{
		print '<select class="flat" name="task_parent">';
		print '<option value="0" selected="true">&nbsp;</option>';
		PLineSelect($j, 0, $tasksarray, $level);
		print '</select>';
	}
	print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Add").'">';
	print '</td></tr>';

	print '</table></form><br />';

	print '<form name="addtime" method="POST" action="fiche.php?id='.$projet->id.'">';
	print '<input type="hidden" name="action" value="addtime">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ID").'</td>';
	print '<td>'.$langs->trans("Task").'</td>';
	print '<td align="right">'.$langs->trans("DurationEffective").'</td>';
	print '<td colspan="2">'.$langs->trans("AddDuration").'</td>';
	print "</tr>\n";
	PLines($j, 0, $tasksarray, $level, $tasksrole);
	print '</form>';


	print "</table>";
	print '</div>';


}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
