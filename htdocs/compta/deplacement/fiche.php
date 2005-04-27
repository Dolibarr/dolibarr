<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/compta/deplacement/fiche.php
		\brief      Page fiche d'un déplacement
*/

require("./pre.inc.php");

$langs->load("trips");

$id=isset($_GET["id"])?$_GET["id"]:$_POST["id"];


$mesg = '';


/*
 * Actions
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes")
{
  $deplacement = new Deplacement($db);
  $deplacement->delete($_GET["id"]);
  Header("Location: index.php");
}

if ($_POST["action"] == 'add' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $deplacement = new Deplacement($db);

  $deplacement->date = mktime(12, 1 , 1, 
			      $_POST["remonth"], 
			      $_POST["reday"], 
			      $_POST["reyear"]);
  
  $deplacement->km = $_POST["km"];
  $deplacement->socid = $_POST["soc_id"];
  $deplacement->userid = $user->id; //$_POST["km"];
  $id = $deplacement->create($user);

  if ($id)
    {
      Header ( "Location: fiche.php?id=".$id);
    }
  else
    {
      dolibarr_print_error($db);
    }
}

if ($_POST["action"] == 'update' && $_POST["cancel"] <> $langs->trans("Cancel"))
{
  $deplacement = new Deplacement($db);
  $result = $deplacement->fetch($_POST["id"]);

  $deplacement->date = mktime(12, 1 , 1, 
			      $_POST["remonth"], 
			      $_POST["reday"], 
			      $_POST["reyear"]);
  
  $deplacement->km     = $_POST["km"];

  $result = $deplacement->update($user);

  if ($result > 0)
    {
      Header ( "Location: fiche.php?id=".$_POST["id"]);
    }
  else
    {
      print $mesg=$langs->trans("ErrorUnknown");
    }
}



llxHeader();

$html = new Form($db);

/*
 * Action create
 */
if ($_GET["action"] == 'create')
{
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">'.$langs->trans("NewTrip").'</div><br>';
      
  print '<table class="border" width="100%">';
  print '<tr><td width="20%">'.$langs->trans("Person").'</td><td>'.$user->fullname.'</td></tr>';    

  print "<tr>";
  print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
  print $html->select_societes();
  print '</td></tr>';

  print "<tr>";
  print '<td>'.$langs->trans("Date").'</td><td>';
  print $html->select_date();
  print '</td></tr>';

  print '<tr><td>'.$langs->trans("Kilometers").'</td><td><input name="km" size="10" value=""></td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Save").'">&nbsp;';
  print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($id)
    {
      $deplacement = new Deplacement($db);
      $result = $deplacement->fetch($id);
      if ($result)
	{ 
    
      if ($mesg) print "$mesg<br>";

	  if ($_GET["action"] == 'edit')
	    {
          $h=0;
            
          $head[$h][0] = DOL_URL_ROOT."/compta/deplacement/fiche.php?id=$deplacement->id";
          $head[$h][1] = $langs->trans("TripCard");
            
          dolibarr_fiche_head($head, $hselected, $langs->trans("Ref").' '.$deplacement->id);

	      print "<form action=\"fiche.php\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      print '<input type="hidden" name="id" value="'.$id.'">';
	            
	      print '<table class="border" width="100%">';

	      $soc = new Societe($db);
	      $soc->fetch($deplacement->socid);

	      print '<tr><td width="20%">'.$langs->trans("Personn").'</td><td>'.$user->fullname.'</td></tr>';    

          print "<tr>";
          print '<td>'.$langs->trans("CompanyVisited").'</td><td>';
          print $html->select_societes($soc->id);
          print '</td></tr>';
        
	      print '<tr><td>'.$langs->trans("Date").'</td><td>';
	      print $html->select_date($deplacement->date);
	      print '</td></tr>';
	      print '<tr><td>'.$langs->trans("Kilometers").'</td><td><input name="km" class="flat" size="10" value="'.$deplacement->km.'"></td></tr>';

	      print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="'.$langs->trans("Save").'">&nbsp;';
	      print '<input type="submit" name="cancel" class="button" value="'.$langs->trans("Cancel").'"></td></tr>';
	      print '</table>';
	      print '</form><br>';
	      
	      print '</div>';
	    } 
	  else
	    {
          $h=0;
            
          $head[$h][0] = DOL_URL_ROOT."/compta/deplacement/fiche.php?id=$deplacement->id";
          $head[$h][1] = $langs->trans("TripCard");
            
          dolibarr_fiche_head($head, $hselected, $langs->trans("Ref").' '.$deplacement->id);
      
    	  /*
    	   * Confirmation de la suppression du déplacement
    	   */
          if ($_GET["action"] == 'delete')
    	    {
    	      
                $html = new Form($db);
                $html->form_confirm("fiche.php?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete");
    
                print '<br>';
    	    }

	      $soc = new Societe($db);
	      $soc->fetch($deplacement->socid);

	      print '<table class="border" width="100%">';
	      print '<tr><td width="20%">'.$langs->trans("Personn").'</td><td><a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$user->id.'">'.$user->fullname.'</a></td></tr>';
	      print '<tr><td width="20%">'.$langs->trans("CompanyVisited").'</td><td>'.$soc->nom_url.'</td></tr>';
	      print '<tr><td>'.$langs->trans("Date").'</td><td>';
	      print dolibarr_print_date($deplacement->date);
	      print '</td></tr>';
	      print '<tr><td>'.$langs->trans("Kilometers").'</td><td>'.$deplacement->km.'</td></tr>';    
	      print "</table><br>";
	      
	      print '</div>';
	    }
	  
	}
      else
	{
	  dolibarr_print_error($db);
	}
    }
}


/*
 * Barre d'actions
 *
 */

print '<div class="tabsAction">';

if ($_GET["action"] != 'create' && $_GET["action"] != 'edit')
{
  print '<a class="tabAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans('Edit').'</a>';
  print '<a class="butDelete" href="fiche.php?action=delete&id='.$id.'">'.$langs->trans('Delete').'</a>';
}

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
