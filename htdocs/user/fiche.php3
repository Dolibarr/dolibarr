<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./pre.inc.php3");
require("../service.class.php3");

llxHeader();

$db = new Db();

if ($action == 'add' && $user->admin)
{
  $service = new Service($db);

  $service->ref = $ref;
  $service->libelle = $label;
  $service->price = $price;
  $service->description = $desc;

  $id = $service->create($user->id);

  if ($comm_now && $id)
    {
      $service->start_comm($id, $user->id);
    }

}

if ($action == 'update' && $user->admin) 
{
  $edituser = new User($db, $id);
  $edituser->fetch();

  $edituser->nom = $nom;
  $edituser->prenom = $prenom;
  $edituser->login = $login;
  $edituser->email = $email;

  if (! $edituser->update($id, $user))
    {
      print $edituser->error();
    }
}

if ($action == 'password' && $user->admin) 
{
  $edituser = new User($db, $id);
  $edituser->fetch();

  if ($edituser->password())
    {
      print "Mot de passe changé et envoyé à $edituser->email<p>";
    }
}



/* ************************************************************************** */
/*                                                                            */
/* Nouvel utilisateur                                                         */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{

  print '<div class="titre">Nouvel utilisateur</div><br>';
  print 'A FINIR !!<p><form action="'.$PHP_SELF.'" method="post">';
  print '<input type="hidden" name="action" value="add">';


  print '<form action="'.$PHP_SELF.'?id='.$id.'" method="post">';
  print '<input type="hidden" name="action" value="update">';
  print '<table border="1" cellpadding="3" cellspacing="0">';
  
  print '<tr><td valign="top">Nom</td>';
  print '<td>'.$user->id.'</td></tr>';
  
  print '<tr><td valign="top">Nom</td>';
  print '<td><input size="12" type="text" name="ref" value="'.$user->nom.'"></td></tr>';
  
  print '<tr><td valign="top">Prénom</td>';
  print '<td><input size="30" type="text" name="label" value="'.$user->prenom.'"></td></tr>';
  
  print '<tr><td valign="top">Login</td>';
  print '<td><input size="30" type="text" name="label" value="'.$user->login.'"></td></tr>';

  
  print '<tr><td valign="top">Description</td><td>';
  print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
  print $user->description;
  print "</textarea></td></tr>";
      
  print '<tr><td align="center" colspan="2"><input value="Enregistrer" type="submit"></td></tr>';
  print '</form>';
  print '</table>';
}
/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
  if ($id) 
    {
      $fuser = new User($db, $id);
      $fuser->fetch();

      print '<div class="titre">Fiche utilisateur</div><br>';

      print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';
    
      print '<tr><td valign="top">Nom</td>';
      print '<td bgcolor="#e0e0e0">'.$fuser->nom.'</td>';
      print '<td valign="top">Prénom</td>';
      print '<td>'.$fuser->prenom.'</td></tr>';
  
      print '<tr><td valign="top">Login</td>';
      print '<td bgcolor="#e0e0e0">'.$fuser->login.'</td>';
      print '<td valign="top">Email</td>';
      print '<td>'.$fuser->email.'</td></tr>';
      
      print '<tr><td width="25%" valign="top">Webcal Login</td>';
      print '<td width="25%" bgcolor="#e0e0e0">'.$fuser->webcal_login.'</td>';
      print '<td width="25%" valign="top">Administrateur</td>';
      print '<td width="25%">'.$yn[$fuser->admin].'</td></tr>';
      
      print '</table>';

      print '<br><table width="100%" border="1" cellspacing="0" cellpadding="2">';

      if ($user->admin) 
	{
	  print '<td width="25%" bgcolor="#e0E0E0" align="center">[<a href="fiche.php3?action=edit&id='.$id.'">Editer</a>]</td>';
	}
      else
	{
	  print '<td width="25%" align="center">-</td>';
	}

      print '<td width="25%" align="center">-</td>';

      print '<td width="25%" align="center">[<a href="fiche.php3?action=password&id='.$id.'">Nouveau mot de passe</a>]</td>';
      print '<td width="25%" align="center">-</td>';
      

      print '</table><br>';

      /* ************************************************************************** */
      /*                                                                            */
      /* Edition                                                                    */
      /*                                                                            */
      /* ************************************************************************** */
      
      if ($action == 'edit' && $user->admin) 
	{
	  print '<hr><div class="titre">Edition de l\'utilisateur</div><br>';
	  print '<form action="'.$PHP_SELF.'?id='.$id.'" method="post">';
	  print '<input type="hidden" name="action" value="update">';
	  print '<table border="1" cellpadding="3" cellspacing="0">';
	  
	  print '<tr><td valign="top">Id</td>';
	  print '<td>'.$fuser->id.'</td></tr>';
	  
	  print '<tr><td valign="top">Nom</td>';
	  print '<td><input size="30" type="text" name="nom" value="'.$fuser->nom.'"></td></tr>';
	  
	  print '<tr><td valign="top">Prénom</td>';
	  print '<td><input size="20" type="text" name="prenom" value="'.$fuser->prenom.'"></td></tr>';
	  
	  print '<tr><td valign="top">Login</td>';
	  print '<td><input size="10" maxlength="8" type="text" name="login" value="'.$fuser->login.'"></td></tr>';
	  
	  print '<tr><td valign="top">Email</td>';
	  print '<td><input size="12" type="text" name="email" value="'.$fuser->email.'"></td></tr>';
	  
	  
	  print '<tr><td valign="top">Description</td><td>';
	  print "<textarea name=\"desc\" rows=\"12\" cols=\"40\">";
	  print $fuser->description;
	  print "</textarea></td></tr>";
	  
	  print '<tr><td align="center" colspan="2"><input value="Enregistrer" type="submit"></td></tr>';
	  print '</form>';
	  print '</table>';
	}
            
    }
  
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
