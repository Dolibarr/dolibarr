<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

$mesg = '';

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes")
{
  $deplacement = new Deplacement($db);
  $deplacement->delete($_GET["id"]);
  Header("Location: index.php");
}


if ($_POST["action"] == 'add' && $_POST["cancel"] <> 'Annuler')
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

  if ($_POST["id"])
    {
      Header ( "Location: fiche.php?id=".$_POST["id"]);
    }
  else
    {
      print "Error";
    }
}

if ($_POST["action"] == 'update' && $_POST["cancel"] <> 'Annuler')
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
      print "Error";
    }
}


llxHeader();

/*
 *
 *
 */
$html = new Form($db);
if ($_GET["action"] == 'create')
{
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouveau déplacement</div><br>';
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
  print '<tr><td width="20%">Personne</td><td>'.$user->fullname.'</td></tr>';    

  print "<tr>";
  print '<td>Société visitée</td><td>';
  print $html->select_societes();
  print '</td></tr>';

  print "<tr>";
  print '<td>Date du déplacement</td><td>';
  print $html->select_date();
  print '</td></tr>';

  print '<tr><td>Kilomètres</td><td><input name="km" size="10" value=""></td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer">&nbsp;';
  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($_GET["id"])
    {
      $deplacement = new Deplacement($db);
      $result = $deplacement->fetch($_GET["id"]);

      if ( $result )
	{ 
    
	  /*
	   * Confirmation de la suppression du déplacement
	   *
	   */
	  
	  if ($_GET["action"] == 'delete')
	    {
	      
            print_fiche_titre("Suppression déplacement ",$message);
            print '<br>';

            $html = new Form($db);
            $html->form_confirm("fiche.php?id=".$_GET["id"],"Supprimer ce déplacement","Etes-vous sûr de vouloir supprimer ce déplacement ?","confirm_delete");
	    }


	  if ($_GET["action"] == 'edit')
	    {
	      print_fiche_titre('Fiche déplacement', $mesg);
	      
	      print "<form action=\"fiche.php\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
	            
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';

	      $soc = new Societe($db);
	      $soc->fetch($deplacement->socid);

	      print '<tr><td width="20%">Personne</td><td>'.$user->fullname.'</td></tr>';    

          print "<tr>";
          print '<td>Société visitée</td><td>';
          print $html->select_societes($soc->id);
          print '</td></tr>';
        
	      print '<tr><td>Date du déplacement</td><td>';
	      print $html->select_date($deplacement->date);
	      print '</td></tr>';
	      print '<tr><td>Kilomètres</td><td><input name="km" size="10" value="'.$deplacement->km.'"></td></tr>';

	      print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer">&nbsp;';
	      print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
	      print '</table>';
	      print '</form>';
	    } 
	  else
	    {
	      print_fiche_titre('Fiche déplacement', $mesg);
      
	      $soc = new Societe($db);
	      $soc->fetch($deplacement->socid);

	      print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	      print '<tr><td width="20%">Personne</td><td>'.$user->fullname.'</td></tr>';    
	      print '<tr><td width="20%">Société visitée</td><td>'.$soc->nom_url.'</td></tr>';    
	      print '<tr><td>Date du déplacement</td><td>';
	      print dolibarr_print_date($deplacement->date);
	      print '</td></tr>';
	      print '<tr><td>Kilomètres</td><td>'.$deplacement->km.'</td></tr>';    
	      print "</table>";
	    }
	  
	}
      else
	{
	  print "Error:".$db->error();
	}
    }
}


/*
 * Barre d'actions
 *
 */
print '<br>';

print '<div class="tabsAction">';

if ($_GET["action"] != 'create')
{
  print '<a class="tabAction" href="fiche.php?action=edit&id='.$_GET["id"].'">Editer</a>';
  print '<a class="tabAction" href="fiche.php?action=delete&id='.$_GET["id"].'">Supprimer</a>';
}

print '</div>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
