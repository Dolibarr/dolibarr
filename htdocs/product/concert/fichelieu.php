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

llxHeader();

if ($action == 'add') {
  $lieuconcert = new LieuConcert($db);

  $lieuconcert->nom = $nom;
  $lieuconcert->ville = $ville;
  $lieuconcert->description = $desc;

  $id = $lieuconcert->create($user);
}

if ($action == 'update') {
  $lieuconcert = new LieuConcert($db);

  $lieuconcert->nom = $nom;
  $lieuconcert->ville = $ville;
  $lieuconcert->description = $desc;

  $lieuconcert->update($id, $user);
}

if ($action == 'updateosc') {
  $lieuconcert = new LieuConcert($db);
  $result = $lieuconcert->fetch($id);

  $lieuconcert->updateosc($user);
}

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";

  print '<div class="titre">Nouveau lieu de concert</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Nom</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<td>Ville</td><td><input name="ville" size="40" value=""></td></tr>';
  print "<tr><td valign=\"top\">Description</td><td>";
  print '<textarea name="desc" rows="8" cols="50">';
  print "</textarea></td></tr>";
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
      

}
else
{
  if ($id)
    {

      $lieuconcert = new LieuConcert($db);
      $result = $lieuconcert->fetch($id);

      if ( $result )
	{ 
	  print '<div class="titre">Fiche lieu de concert : '.$lieuconcert->nom.'</div><br>';
      
	  print '<table border="1" width="50%" cellspacing="0" cellpadding="4">';
	  print "<tr><td>Nom</td><td>$lieuconcert->nom</td></tr>\n";
	  print "<tr><td>Ville</td><td>$lieuconcert->ville</td></tr>\n";
	  print '<tr><td valign="top">Description</td><td valign="top">';
	  print nl2br($lieuconcert->description)."</td></tr>";

	  print "</table>";
	}
    
      if ($action == 'edit')
	{
	  print '<hr><div class="titre">Edition de la fiche lieu de concert : '.$lieuconcert->nom.'</div><br>';

	  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	  
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td>Nom</td><td><input name="nom" size="40" value="'.$lieuconcert->nom.'"></td></tr>';
	  print '<tr><td>Ville</td><td><input name="ville" size="40" value="'.$lieuconcert->ville.'"></td></tr>';
	  print "<tr><td valign=\"top\">Description</td><td>";
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $lieuconcert->description;
	  print "</textarea></td></tr>";
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="Enregistrer"></td></tr>';
	  print '</form>';
	  print '</table>';

	}    
    }
  else
    {
      print "Error";
    }
}

/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';

if ($action == 'create')
{
  print '<td width="20%" align="center">-</td>';
}
else
{
  print '<td width="20%" align="center">[<a href="fichelieu.php?action=edit&id='.$id.'">Editer</a>]</td>';
}
print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
