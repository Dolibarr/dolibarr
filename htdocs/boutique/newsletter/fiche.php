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

$db = new Db();

if ($action == 'add') {
  $newsletter = new Newsletter($db);

  $newsletter->nom = $nom;

  $id = $newsletter->create($user);
}

if ($action == 'addga') {
  $newsletter = new Newsletter($db);

  $newsletter->linkga($id, $ga);
}

if ($action == 'update' && !$cancel)
{
  $newsletter = new Newsletter($db);

  $newsletter->nom = $nom;

  $newsletter->update($id, $user);
}

if ($HTTP_POST_VARS["action"] == 'confirm_delete' && $HTTP_POST_VARS["confirm"] == yes)
{
  $newsletter = new Newsletter($db);
  $result = $newsletter->fetch($id);
  $newsletter->delete();
  Header("Location: index.php");
}

llxHeader();

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouvelle Newsletter</div><br>';
      
  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
  print "<tr>";
  print '<td>Emetteur nom</td><td><input name="nom" size="30" value=""></td></tr>';
  print '<td>Emetteur email</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<td>Email de réponse</td><td><input name="nom" size="40" value=""></td><td>Si vide, le mail de l\'émetteur est utilisé</tr>';
  print '<td>Sujet</td><td><input name="nom" size="30" value=""></td></tr>';
  print '<td>Cible</td><td><input name="nom" size="40" value=""></td></tr>';
  print '<td>Texte</td><td><textarea name="body" rows="10" cols="60"></textarea></td></tr>';
  print '<tr><td>&nbsp;</td><td><input type="submit" value="Créer"></td></tr>';
  print '</table>';
  print '</form>';

}
else
{
  if ($id)
    {

      $newsletter = new Newsletter($db);
      $result = $newsletter->fetch($id);

      if ( $result )
	{ 

	  /*
	   * Confirmation de la suppression de la newsletter
	   *
	   */
	  
	  if ($action == 'delete')
	    {
	      
	      print '<form method="post" action="'.$PHP_SELF.'?id='.$id.'">';
	      print '<input type="hidden" name="action" value="confirm_delete">';
	      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';
	      
	      print '<tr><td colspan="3">Supprimer un éditeur</td></tr>';
	      
	      print '<tr><td class="delete">Etes-vous sur de vouloir supprimer cette newsletter ?</td><td class="delete">';
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
	      print '<div class="titre">Edition de la fiche Newsletter : '.$newsletter->titre.'</div><br>';
	      
	      print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	      print "<tr>";
	      print '<td width="20%">Nom</td><td><input name="nom" size="40" value="'.$newsletter->nom.'"></td>';


	      print '<tr><td colspan="2" align="center"><input type="submit" value="Enregistrer">&nbsp;<input type="submit" value="Annuler" name="cancel"></td></tr>';
	      
	      print '</form>';

	      print '</table><hr>';
	      
	    }    

	  print '<div class="titre">Fiche Newsletter : '.$newsletter->titre.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Nom</td><td width="80%">'.$newsletter->nom.'</td></tr>';

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
