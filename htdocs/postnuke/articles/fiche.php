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
require("./pnarticle.class.php");

$db = new Db();


if ($action == 'update' && !$cancel)
{
  $livre = new Livre($db);

  $livre->titre = $titre;
  $livre->ref = $ref;
  $livre->price = $price;
  $livre->annee = $annee;
  $livre->editeurid = $editeurid;
  $livre->description = $desc;

  if ($livre->update($id, $user))
    {
      $result = $livre->fetch($id);
      $livre->updateosc($user);
    }
  else
    {
      $action = 'edit';
    }
}

if ($action == 'updateosc')
{
  $livre = new Livre($db);
  $result = $livre->fetch($id);

  $livre->updateosc($user);
}

/*
 *
 *
 */

llxHeader();

if ($id)
{
  $article = new pnArticle($db);

  if ($id)
    {
      $result = $article->fetch($id, 0);
    }
  
  if ( $result )
    { 
      $htmls = new Form($db);



      if ($action == 'edit')
	{
	  print '<div class="titre">Edition de la fiche Livre : '.$livre->titre.'</div><br>';
	  
	  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	  print "<input type=\"hidden\" name=\"action\" value=\"update\">";
	  
	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Référence</td><td><input name="ref" size="20" value="'.$livre->ref.'"></td>';
	  print "<td valign=\"top\">Description</td></tr>";
	  
	  print "<tr><td>Statut</td><td>$livre->status_text";
	  if ($livre->status == 0)
	    {
	      print '<br><a href="fiche.php?id='.$id.'&status=1&action=status">Changer</a>';
	    }
	  else
	    {
	      print '<br><a href="fiche.php?id='.$id.'&status=0&action=status">Changer</a>';
	    }
	  print "</td>\n";
	  
	  print '<td valign="top" width="50%" rowspan="6"><textarea name="desc" rows="14" cols="60">';
	  print $livre->description;
	  print "</textarea></td></tr>";
	  
	  print '<tr><td>Titre</td><td><input name="titre" size="40" value="'.$livre->titre.'"></td></tr>';
	  
	  print '<tr><td>Année</td><TD><input name="annee" size="6" maxlenght="4" value="'.$livre->annee.'"></td></tr>';
	  print '<tr><td>Prix</td><TD><input name="price" size="10" value="'.price($livre->price).'"></td></tr>';    
	  $htmls = new Form($db);
	  $edits = new Editeur($db);
	  
	  print "<tr><td>Editeur</td><td>";
	  $htmls->select_array("editeurid",  $edits->liste_array(), $livre->editeurid);
	  print "</td></tr>";
	  

	  print '<tr><td align="center" colspan="3"><input type="submit" value="Enregistrer">&nbsp;<input type="submit" value="Annuler" name="cancel"></td></tr>';
	  print "</form>";
	  
	  print '</form>';	      
	  print '</table><hr>';
	      
	    }    

	  /*
	   * Affichage
	   */

	  print '<div class="titre">Fiche Article : '.$article->titre.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  
	  print "<tr><td>Titre</td><td>$article->titre</td></tr>\n";
	  print "<tr><td>Titre</td><td>$article->body</td></tr>\n";

	  
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


/* ************************************************************************** */
/*                                                                            */ 
/* Barre d'action                                                             */ 
/*                                                                            */ 
/* ************************************************************************** */

print '<br><table width="100%" border="1" cellspacing="0" cellpadding="3">';

if ($action == 'create')
{
  print '<td width="20%" align="center">-</td>';
}
else
{
  print '<td width="20%" align="center">[<a href="fiche.php?action=edit&id='.$id.'">Editer</a>]</td>';
}

print '<td width="20%" align="center">-</td>';
//print '<td width="20%" align="center">[<a href="fiche.php?action=updateosc&id='.$id.'">Update Osc</a>]</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">[<a href="fiche.php?action=delete&id='.$id.'">Supprimer</a>]</td>';
print '</table><br>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
