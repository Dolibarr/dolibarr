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

llxHeader("","","Fiche entrepôt");

if ($HTTP_POST_VARS["action"] == 'add')
{
  $entrepot = new Entrepot($db);

  $entrepot->ref         = $HTTP_POST_VARS["ref"];
  $entrepot->libelle     = $HTTP_POST_VARS["libelle"];
  $entrepot->description = $HTTP_POST_VARS["desc"];

  $id = $entrepot->create($user);
  $action = '';
}

if ($HTTP_POST_VARS["action"] == 'update' && $cancel <> 'Annuler')
{
  $entrepot = new Entrepot($db);
  if ($entrepot->fetch($id))
    {
      $entrepot->libelle     = $HTTP_POST_VARS["libelle"];
      $entrepot->description = $HTTP_POST_VARS["desc"];
      $entrepot->statut      = $HTTP_POST_VARS["statut"];
      
      if ( $entrepot->update($id, $user))
	{
	  $action = '';
	  $mesg = 'Fiche mise à jour';
	}
      else
	{
	  $action = 're-edit';
	  $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
	}
    }
  else
    {
      $action = 're-edit';
      $mesg = 'Fiche non mise à jour !' . "<br>" . $entrepot->mesg_error;
    }
}


if ($cancel == 'Annuler')
{
  $action = '';
}
/*
 * Affichage
 *
 */
if ($_GET["action"] == 'create')
{
  print "<form action=\"$PHP_SELF\" method=\"post\">\n";
  print '<input type="hidden" name="action" value="add">';
  print '<input type="hidden" name="type" value="'.$type.'">'."\n";
  print_titre("Nouvel entrepôt");
      
  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr><td width="20%">Libellé</td><td><input name="libelle" size="40" value=""></td></tr>';
  print '<tr><td width="20%" valign="top">Description</td><td>';
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
      if ($action <> 're-edit')
	{
	  $entrepot = new Entrepot($db);
	  $result = $entrepot->fetch($id);
	}

      if ( $result )
	{ 
	  if ($action <> 'edit' && $action <> 're-edit')
	    {
	      print_fiche_titre('Fiche entrepot', $mesg);
      
	      print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	      print '<tr><td width="20%">Libellé</td><td>'.$entrepot->libelle.'</td>';
	      print "<tr><td valign=\"top\">Description</td><td>".nl2br($entrepot->description)."</td></tr>";
	      print '<tr><td width="20%">statut</td><td>'.$entrepot->statuts[$entrepot->statut].'</td>';
	      print "</table>";
	    }
	}

    
      if (($action == 'edit' || $action == 're-edit') && 1)
	{
	  print_fiche_titre('Edition de la fiche entrepot', $mesg);

	  print "<form action=\"$PHP_SELF?id=$id\" method=\"post\">\n";
	  print '<input type="hidden" name="action" value="update">';
	  
	  print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
	  print '<tr><td width="20%">Libellé</td><td colspan="2"><input name="libelle" size="40" value="'.$entrepot->libelle.'"></td></tr>';
	  print '<tr><td valign="top">Description</td><td colspan="2">';
	  print '<textarea name="desc" rows="8" cols="50">';
	  print $entrepot->description;
	  print "</textarea></td></tr>";
	  print '<tr><td width="20%">Statut</td><td colspan="2">';
	  print '<select name="statut">';
	  if ($entrepot->statut == 0)
	    {
	      print '<option value="0" SELECTED>Fermé</option><option value="1">Ouvert</option>';
	    }
	  else
	    {
	      print '<option value="0">Fermé</option><option value="1" SELECTED>Ouvert</option>';
	    }
	  print '</td></tr>';
	  print "<tr>".'<td colspan="3" align="center"><input type="submit" value="Enregistrer">&nbsp;';
	  print '<input type="submit" name="cancel" value="Annuler"></td></tr>';
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

print '<br><table id="actions" width="100%" cellspacing="0" cellpadding="3">';
if ($action == '')
{
  if ($user->rights->produit->modifier || $user->rights->produit->creer)
    {
      print '<td width="20%" align="center">[<a href="fiche.php?action=edit_price&id='.$id.'">Changer le prix</a>]</td>';
    }
  else
    {
      print '<td width="20%" align="center">-</td>';    
    }
}
else
{
  print '<td width="20%" align="center">-</td>';
}
print '<td width="20%" align="center">-</td>';
print '<td width="20%" align="center">-</td>';

if ($action == '')
{
  if (1)
    {
      print '<td width="20%" align="center">[<a href="fiche.php?action=edit&id='.$id.'">Editer</a>]</td>';
    }
  else
    {
      print '<td width="20%" align="center">-</td>';    
    }
}
else
{
  print '<td width="20%" align="center">-</td>';
}
print '<td width="20%" align="center">-</td>';    
print '</table><br>';



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
