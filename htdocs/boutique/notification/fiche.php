<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/boutique/notification/fiche.php
		\ingroup    boutique
		\brief      Page fiche notification OS Commerce
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

if ($action == 'add') {
  $editeur = new Editeur($dbosc);

  $editeur->nom = $nom;

  $id = $editeur->create($user);
}

if ($action == 'addga') {
  $editeur = new Editeur($dbosc);

  $editeur->linkga($id, $ga);
}


if ($action == 'update' && !$cancel) {
  $editeur = new Editeur($dbosc);

  $editeur->nom = $nom;

  $editeur->update($id, $user);
}

/*
 *
 *
 */
if ($action == 'create')
{

  print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
  print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
  print '<input type="hidden" name="action" value="add">';

  print '<div class="titre">Nouvel Editeur</div><br>';
      
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

      $editeur = new Editeur($dbosc);
      $result = $editeur->fetch($id);

      if ( $result )
	{ 
	  if ($action == 'edit')
	    {
	      print '<div class="titre">Edition de la fiche Editeur : '.$editeur->titre.'</div><br>';
	      
	      print "<form action=\"fiche.php?id=$id\" method=\"post\">\n";
	      print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
	      print '<input type="hidden" name="action" value="update">';
	      
	      print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	      print "<tr>";
	      print '<td width="20%">Nom</td><td><input name="nom" size="40" value="'.$editeur->nom.'"></td>';


	      print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Save").'">&nbsp;<input type="submit" value="'.$langs->trans("Cancel").'" name="cancel"></td></tr>';
	      
	      print '</form>';

	      print '</table><hr>';
	      
	    }    

	  print '<div class="titre">Fiche Editeur : '.$editeur->titre.'</div><br>';

	  print '<table border="1" width="100%" cellspacing="0" cellpadding="4">';
	  print "<tr>";
	  print '<td width="20%">Nom</td><td width="30%">'.$editeur->nom.'</td></tr>';
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
  print '<a class="butAction" href="fiche.php?action=edit&id='.$id.'">'.$langs->trans("Modify").'</a>';
}
print '</div>';


$dbosc->close();

llxFooter('$Date$ - $Revision$');
?>
