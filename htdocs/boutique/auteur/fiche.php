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

$db = new Db();

if ($action == 'add') {
  $auteur = new Auteur($db);

  $auteur->nom = $nom;

  $id = $auteur->create($user);
}

if ($action == 'addga') {
  $auteur = new Auteur($db);

  $auteur->linkga($id, $ga);
}


if ($action == 'update' && !$cancel) {
  $auteur = new Auteur($db);

  $auteur->nom = $nom;

  $auteur->update($id, $user);
}

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouvel Auteur</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Nom</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
      

}
else
{
  if ($id)
    {

      $auteur = new Auteur($db);
      $result = $auteur->fetch($id);

      if ( $result )
	{ 
	  if ($action == 'edit')
	    {
	      print '<div class="titre">Edition de la fiche Auteur : '.$auteur->nom.'</div><br>';
	      
	      print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	      print "<tr>";
	      print '<td width="20%">Nom</td><td><input name="nom" size="40" value="'.$auteur->nom.'"></td>';


	      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer">&nbsp;<input type="submit" value="Annuler" name="cancel"></td></tr>';
	      
	      print '</form>';

	      print '</table><hr>';
	      
	    }    

	  print '<div class="titre">Fiche Auteur : '.$auteur->nom.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Nom</td><td width="30%">'.$auteur->nom.'</td></tr>';
	  print "</table>";



	}
      else
	{
	  print "Fetch failed";
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
  print '<td width="20%" align="center">[<a href="fiche.php?action=edit&id='.$id.'">Editer</a>]</td>';
}
print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
