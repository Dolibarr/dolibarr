<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == "yes")
{
  $deplacement = new Deplacement($db);
  $deplacement->delete($id);
  Header("Location: index.php");
}


if ($HTTP_POST_VARS["action"] == 'add' && $HTTP_POST_VARS["cancel"] <> 'Annuler')
{
  $deplacement = new Deplacement($db);

  $deplacement->date = mktime(12, 1 , 1, 
			      $HTTP_POST_VARS["remonth"], 
			      $HTTP_POST_VARS["reday"], 
			      $HTTP_POST_VARS["reyear"]);
  
  $deplacement->km = $HTTP_POST_VARS["km"];
  $deplacement->socid = $HTTP_POST_VARS["socid"];
  $deplacement->userid = $user->id; //$HTTP_POST_VARS["km"];
  $id = $deplacement->create($user);

  if ($id > 0)
    {
      Header ( "Location: fiche.php?id=$id");
    }
  else
    {
      print "Error";
    }
}

if ($HTTP_POST_VARS["action"] == 'update' && $HTTP_POST_VARS["cancel"] <> 'Annuler')
{
  $deplacement = new Deplacement($db);
  $result = $deplacement->fetch($id);

  $deplacement->date = mktime(12, 1 , 1, 
			      $HTTP_POST_VARS["remonth"], 
			      $HTTP_POST_VARS["reday"], 
			      $HTTP_POST_VARS["reyear"]);
  
  $deplacement->km     = $HTTP_POST_VARS["km"];

  $result = $deplacement->update($user);

  if ($result > 0)
    {
      Header ( "Location: fiche.php?id=$id");
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
if ($action == 'create')
{
  print "<form action=\"fiche.php\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="socid" value="'.$socid.'">';
  print '<div class="titre">Nouveau déplacement</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td width="20%">Personne</td><td>'.$user->fullname.'</td></tr>';    
  print "<tr>";
  print '<td>Date du déplacement</td><td>';
  print $html->select_date();
  print '</td></tr>';

  print '<tr><td>Kilomètres</td><TD><input name="km" size="10" value=""></td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer">&nbsp;';
  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
  print '</table>';
  print '</form>';      
}
else
{
  if ($id)
    {
      $deplacement = new Deplacement($db);
      $result = $deplacement->fetch($id);

      if ( $result )
	{ 
    
	  /*
	   * Confirmation de la suppression de l'adhérent
	   *
	   */
	  
	  if ($action == 'delete')
	    {
	      
	      print '<form method="post" action="'.$PHP_SELF.'?id='.$id.'">';
	      print '<input type="hidden" name="action" value="confirm_delete">';
	      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
	      
	      print '<tr><td colspan="3">Supprimer ce déplacement</td></tr>';	      
	      print '<tr><td class="delete">Etes-vous sur de vouloir supprimer ce déplacement ?</td><td class="delete">';
	      $htmls = new Form($db);
	      
	      $htmls->selectyesno("confirm","no");
	      
	      print "</td>\n";
	      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
	      print '</table>';
	      print "</form>\n";  
	    }


	  if ($action == 'edit')
	    {
	      print_fiche_titre('Fiche déplacement : '.$product->ref, $mesg);
	      
	      print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	            
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';

	      $soc = new Societe($db);
	      $soc->fetch($deplacement->socid);
	      print '<tr><td width="20%">Personne</td><td>'.$user->fullname.'</td></tr>';    
	      print '<tr><td width="20%">Société visitée</td><td>'.$soc->nom_url.'</td></tr>';    
	      print '<tr><td>Date du déplacement</td><td>';
	      print $html->select_date($deplacement->date);
	      print strftime("%A %d %B %Y",$deplacement->date);
	      print '</td></tr>';
	      print '<tr><td>Kilomètres</td><td><input name="km" size="10" value="'.$deplacement->km.'"> '.$deplacement->km.'</td></tr>';

	      print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer">&nbsp;';
	      print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
	      print '</table>';
	      print '</form>';
	    } 
	  else
	    {
	      print_fiche_titre('Fiche déplacement : '.$product->ref, $mesg);
      
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';

	      $soc = new Societe($db);
	      $soc->fetch($deplacement->socid);
	      print '<tr><td width="20%">Personne</td><td>'.$user->fullname.'</td></tr>';    
	      print '<tr><td width="20%">Société visitée</td><td>'.$soc->nom_url.'</td></tr>';    
	      print '<tr><td>Date du déplacement</td><td>';
	      print strftime("%A %d %B %Y",$deplacement->date);
	      print '</td></tr>';
	      
	      print '<tr><td>Kilomètres</td><td>'.$deplacement->km.'</td></tr>';    
	      print "</table>";
	    }
	  
	}
      else
	{
	  print "Error";
	}
    }
}
/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';

if ($action == 'create')
{
  print '<td width="20%" align="center">-</td>';
}
else
{
  print '<td width="20%" align="center">[<a href="fiche.php?action=edit&id='.$id.'">Editer</a>]</td>';
}
print '<td width="20%" align="center">-</td>';

print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">[<a href="fiche.php?action=delete&id='.$id.'">Supprimer</a>]</td>';
print '</table><br>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
