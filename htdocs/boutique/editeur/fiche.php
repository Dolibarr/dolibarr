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

if ($action == 'add') {
  $editeur = new Editeur($db);

  $editeur->nom = $nom;

  $id = $editeur->create($user);
}

if ($action == 'addga') {
  $editeur = new Editeur($db);

  $editeur->linkga($id, $ga);
}

if ($action == 'update' && !$cancel)
{
  $editeur = new Editeur($db);

  $editeur->nom = $nom;

  $editeur->update($id, $user);
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  $editeur = new Editeur($db);
  $result = $editeur->fetch($id);
  $editeur->delete();
  Header("Location: index.php");
}

llxHeader();

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouvel Editeur</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td width="20%">Nom</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';
      

}
else
{
  if ($id)
    {

      $editeur = new Editeur($db);
      $result = $editeur->fetch($id);

      if ( $result )
	{ 
	  $livres = $editeur->liste_livre();

	  /*
	   * Confirmation de la suppression de l'editeur
	   *
	   */
	  
	  if ($action == 'delete')
	    {
	      
	      print '<form method="post" action="fiche.php?id='.$id.'">';
	      print '<input type="hidden" name="action" value="confirm_delete">';
	      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
	      
	      print '<tr><td colspan="3">Supprimer un éditeur</td></tr>';
	      
	      print '<tr><td class="delete">Etes-vous sur de vouloir supprimer cet éditeur ?</td><td class="delete">';
	      $htmls = new Form($db);
	      
	      $htmls->selectyesno("confirm","no");
	      
	      print "</td>\n";
	      print '<td class="delete" align="center"><input type="submit" value="Confirmer"</td></tr>';
	      print '</table>';
	      print "</form>\n";  
	    }
	  
	  /*
	   * Edition de la fiche
	   *
	   */


	  if ($action == 'edit')
	    {
	      print '<div class="titre">Edition de la fiche Editeur : '.$editeur->titre.'</div><br>';
	      
	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	      print "<tr>";
	      print '<td width="20%">Nom</td><td width="80%"><input name="nom" size="40" value="'.$editeur->nom.'"></td>';


	      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer">&nbsp;<input type="submit" value="Annuler" name="cancel"></td></tr>';
	      
	      print '</form>';

	      print '</table><hr>';
	      
	    }    

	  print '<div class="titre">Fiche Editeur : '.$editeur->titre.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Nom</td><td width="80%">'.$editeur->nom.'</td></tr>';

	  print '<tr><td>Livres</td><td>';

	  foreach ($livres as $key => $value)
	    {
	      print '<a href="../livre/fiche.php?id='.$key.'">'.$value."<br>\n";
	    }
	  print "</td></tr>";

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
print '<td width="20%" align="center">-</td>';

if(sizeof($livres)==0 && $id)
{
  print '<td width="20%" align="center">[<a href="fiche.php?action=delete&id='.$id.'">Supprimer</a>]</td>';
}
else
{
  print '<td width="20%" align="center">[Supprimer]</td>';
}


print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
