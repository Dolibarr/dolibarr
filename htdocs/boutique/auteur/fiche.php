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

if ($action == 'add') {
  $auteur = new Auteur($db);

  $auteur->nom = $nom;

  $id = $auteur->create($user);
}

if ($action == 'addga') {
  $auteur = new Auteur($db);

  $auteur->linkga($id, $ga);
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == yes)
{
  $auteur = new Auteur($db);
  $result = $auteur->fetch($id);
  $auteur->delete();
  Header("Location: index.php");
}


if ($action == 'update' && !$cancel) {
  $auteur = new Auteur($db);

  $auteur->nom = $nom;

  $auteur->update($id, $user);
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

	  $livres = $auteur->liste_livre();

	  /*
	   * Confirmation de la suppression de l'auteur
	   *
	   */
	  
	  if ($action == 'delete')
	    {
	      $htmls = new Form($db);
          $htmls->form_confirm("fiche.php?id=$id","Supprimer un auteur","Etes-vous sur de vouloir supprimer cet auteur ?","confirm_delete");
	    }
	  
	  /*
	   * Edition
	   *
	   */


	  if ($action == 'edit')
	    {
	      print '<div class="titre">Edition de la fiche Auteur : '.$auteur->nom.'</div><br>';
	      
	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table class="border" width="100%">';
	      print "<tr>";
	      print '<td width="20%">Nom</td><td><input name="nom" size="40" value="'.$auteur->nom.'"></td>';

	      print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;<input type="submit" value="'.$langs->trans("Cancel").'" name="cancel"></td></tr>';
	      
	      print '</form>';

	      print '</table><hr>';
	      
	    }    

	  print '<div class="titre">Fiche Auteur : '.$auteur->nom.'</div><br>';

	  print '<table class="border" width="100%">';
	  print "<tr>";
	  print '<td width="20%">Nom</td><td width="80%">'.$auteur->nom.'</td></tr>';

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

  print '<div class="tabsAction">';

    if ($action != 'create')
    {
      print '<a class="tabAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans("Edit").'</a>';
    }
    
    if(sizeof($livres)==0 && $id)
    {
      print '<a class="tabAction" href="fiche.php?action=delete&id='.$id.'">'.$langs->trans("Delete").'</a>';
    }

  print '</div>';


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
