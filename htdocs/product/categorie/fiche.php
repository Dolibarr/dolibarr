<?php
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

if ($action == 'add') {
  $album = new Album($db);

  $album->titre = $titre;
  $album->description = $desc;

  $id = $album->create($user);
}

if ($action == 'addga') {
  $album = new Album($db);

  $album->linkga($id, $ga);
}


if ($action == 'update') {
  $album = new Album($db);

  $album->titre = $titre;
  $album->description = $desc;

  $album->update($id, $user);
}

if ($action == 'updateosc') {
  $album = new Album($db);
  $result = $album->fetch($id);

  $album->updateosc($user);
}

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";

  print '<div class="titre">Nouvel album</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value=""></td></tr>';
  print '<td>Titre</td><td><input name="titre" size="40" value=""></td></tr>';
  print '<tr><td>'.$langs->trans("Price").'</td><TD><input name="price" size="10" value=""></td></tr>';    
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

      $album = new Album($db);
      $result = $album->fetch($id);

      if ( $result )
	{ 
	  print '<div class="titre">Fiche Album : '.$album->titre.'</div><br>';
      
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	  print '<tr>';
	  print '<td>'.$langs->trans("Ref")."</td><td>$album->ref</td>\n";
	  print '<td>'.$langs->trans("Status").'</td><td>$album->status</td></tr>\n";
	  print "<td>Titre</td><td>$album->titre</td>\n";
	  print '<td>'.$langs->trans("Price").'</td><TD>'.price($album->price).'</td></tr>';    
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td valign="top">'.nl2br($album->description)."</td>";

	  $gas = $album->liste_groupart();
	  print '<td valign="top">Artiste/Groupe</td><td><ul>';	      
	  foreach ($gas as $key => $value)
	    {
	      print '<li><a href="../groupart/fiche.php?id='.$key.'">'.$value."</a></li>";
	    }
	  print "</ul></td></tr>\n";
	  print "</table>";
	}
    
      if ($action == 'edit')
	{
	  print '<hr><div class="titre">Edition de la fiche Album : '.$album->titre.'</div><br>';

	  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="3">';
	  print "<tr>";
	  print '<td>'.$langs->trans("Ref").'</td><td><input name="ref" size="20" value="'.$album->ref.'"></td></tr>';
	  print '<td>'.$langs->trans("Label").'</td><td><input name="titre" size="40" value="'.$album->titre.'"></td></tr>';
	  print '<tr><td>'.$langs->trans("Price").'</td><td><input name="price" size="10" value="'.$album->price.'"></td></tr>';    
	  print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $album->description;
	  print "</textarea></td></tr>";
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';

	  print '</form>';

	  $htmls = new Form($db);
	  $ga = new Groupart($db);

	  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"addga\">";

	  foreach ($gas as $key => $value)
	    {
	      print '<tr><td>Artiste/Groupe</td>';
	      print '<td><a href="../groupart/fiche.php?id='.$key.'">'.$value."</td></tr>\n";
	    }

	  print "<tr><td>Artiste/Groupe</td><td>";
	  $htmls->select_array("ga",  $ga->liste_array());
	  print "</td></tr>";
	  print '<tr><td>&nbsp;</td><td><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
	  print "</form>";
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
