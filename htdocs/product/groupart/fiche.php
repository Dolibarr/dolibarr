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

llxHeader();

if ($action == 'add')
{
  $groupart = new Groupart($db);
  $groupart->nom = $nom;
  $groupart->desc = $desc;
  $groupart->grar = $grar;
  $id = $groupart->create($user);
}

if ($action == 'update') {
  $groupart = new Groupart($db);
  $groupart->nom = $nom;
  $groupart->desc = $desc;
  $groupart->grar = $grar;
  $groupart->update($id, $user);
}

if ($action == 'updateosc') {
  $groupart = new Groupart($db);
  $result = $groupart->fetch($id);
  $groupart->updateosc($user);
}

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";

  print '<div class="titre">Nouvel Artiste/Groupe</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Nom</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<tr><td>Artiste/Groupe</td><td><select name="grar"><option value="artiste">Artiste</option>';
  print '<option value="groupe">Groupe</option></select></td></tr>';
  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
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

      $groupart = new Groupart($db);
      $result = $groupart->fetch($id);

      if ( $result )
	{ 
	  print '<div class="titre">Fiche Artiste/Groupe</div><br>';
      
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print "<td>Nom</td><td>".$groupart->nom."</td></tr>\n";
	  print "<tr>";
	  print "<td>Groupe/Artiste</td><td>".ucfirst(strtolower(strtoupper($groupart->grar)))."</td></tr>\n";
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>'.nl2br($groupart->desc).'</td></tr>';

	  $gas = $groupart->liste_albums();
	  print '<tr><td>Album(s)</td><td><ul>';
	  foreach ($gas as $key => $value)
	    {
	      print '<li><a href="../album/fiche.php?id='.$key.'">'.$value."</a></li>";
	    }
	  print "</ul></td></tr>\n";
	  print "</table>";
	}
    
      if ($action == 'edit')
	{
	  if ($groupart->grar == 'artiste')
	    {
	      $grar_opt = 'Groupe';
	    }
	  else 
	    {
	      $grar_opt = 'Artiste';
	    }
	  print '<hr><div class="titre">Edition de la fiche produit : '.$groupart->ref.'</div><br>';

	  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	  
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td>Nom</td><td><input name="nom" size="40" value="'.$groupart->nom.'"></td></tr>';
      print '<tr><td>Artiste/Groupe</td><td><select name="grar"><option value="'.ucfirst(strtolower(strtoupper($groupart->grar))).'">'.ucfirst(strtolower(strtoupper($groupart->grar))).'</option>';
      print '<option value="'.$grar_opt.'">'.$grar_opt.'</option></select></td></tr>';
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $groupart->desc;
	  print "</textarea></td></tr>";
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
	  print '</table>';
	  print '</form>';
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
print '<td width="20%" align="center">[<a href="fiche.php?action=updateosc&id='.$id.'">Update Osc</a>]</td>';
print '<td width="20%" align="center">-</td>';

if ($action == 'create')
{
  print '<td width="20%" align="center">-</td>';
}
else
{
  print '<td width="20%" align="center">[<a href="fiche.php?action=edit&id='.$id.'">'.$langs->trans("Edit").'</a>]</td>';
}
print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
