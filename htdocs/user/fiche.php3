<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
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
require($GLOBALS["DOCUMENT_ROOT"]."/service.class.php3");

llxHeader();

$db = new Db();
$form = new Form($db);

if ($HTTP_POST_VARS["action"] == 'add' && $user->admin)
{
  $edituser = new User($db,0);

  $edituser->nom    = $HTTP_POST_VARS["nom"];
  $edituser->note   = $HTTP_POST_VARS["note"];
  $edituser->prenom = $HTTP_POST_VARS["prenom"];
  $edituser->login  = $HTTP_POST_VARS["login"];
  $edituser->email  = $HTTP_POST_VARS["email"];
  $edituser->admin  = $HTTP_POST_VARS["admin"];
  $edituser->webcal_login  = $HTTP_POST_VARS["webcal_login"];
  if (isset($HTTP_POST_VARS["module_compta"]) && $HTTP_POST_VARS["module_compta"] ==1){
    $edituser->compta  = 1;
  }else{
    $edituser->compta  = 0;
  }
  if (isset($HTTP_POST_VARS["module_comm"]) && $HTTP_POST_VARS["module_comm"] ==1){
    $edituser->comm  = 1;
  }else{
    $edituser->comm  = 0;
  }

  //$id = $edituser->create($user->id);
  $id = $edituser->create();
  if (isset($_POST['password']) && $_POST['password']!='' ){
    $edituser->password($_POST['password'],$conf->password_encrypted);
  }
}

if ($_POST["action"] == 'update' && $user->admin) 
{
  $edituser = new User($db, $id);
  $edituser->fetch();

  $edituser->nom           = $_POST["nom"];
  $edituser->note          = $_POST["note"];
  $edituser->prenom        = $_POST["prenom"];
  $edituser->login         = $_POST["login"];
  $edituser->email         = $_POST["email"];
  $edituser->admin         = $_POST["admin"];
  $edituser->webcal_login  = $_POST["webcal_login"];

  if (isset($_POST["module_compta"]) && $_POST["module_compta"] ==1)
    {
      $edituser->compta  = 1;
    }
  else
    {
      $edituser->compta  = 0;
    }
  
  if (isset($_POST["module_comm"]) && $_POST["module_comm"] ==1)
    {
      $edituser->comm  = 1;
    }
  else
    {
      $edituser->comm  = 0;
    }

  //  if (! $edituser->update($id, $user))
  if (! $edituser->update())
    {
      print $edituser->error();
    }
  if (isset($password) && $password !='' )
    {
      $edituser->password($password,$conf->password_encrypted);
    }
}

if ($action == 'password' && $user->admin) 
{
  $edituser = new User($db, $id);
  $edituser->fetch();

  if ($edituser->password('',$conf->password_encrypted))
    {
      $message = "Mot de passe changé et envoyé à $edituser->email";
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
  print '<p><form action="'.$PHP_SELF.'" method="post">';
  print '<input type="hidden" name="action" value="add">';

  print '<table border="1" cellpadding="3" cellspacing="0">';

  print '<tr><td valign="top">Prénom</td>';
  print '<td class="valeur"><input size="30" type="text" name="prenom" value=""></td></tr>';
  
  print '<tr><td valign="top">Nom</td>';
  print '<td class="valeur"><input size="20" type="text" name="nom" value=""></td></tr>';
  
  print '<tr><td valign="top">Login</td>';
  print '<td class="valeur"><input size="30" type="text" name="login" value=""></td></tr>';

  print '<tr><td valign="top">Password</td>';
  print '<td class="valeur"><input size="30" type="text" name="password" value=""></td></tr>';

  print '<tr><td valign="top">Email</td>';
  print '<td class="valeur"><input size="30" type="text" name="email" value=""></td></tr>';
  
  print '<tr><td valign="top">Admin ?</td>';
  print '<td class="valeur">';
  $form->selectyesnonum('admin',0);
  print '</td></tr>';
  
  print '<tr><td valign="top">Login Webcal</td>';
  print '<td class="valeur"><input size="30" type="text" name="webcal_login" value=""></td></tr>';
  
  print '<tr><td valign="top">Module Commercial ?</td>';
  print '<td class="valeur">';
  $form->checkbox('module_comm',0,1);
  print '</td></tr>';

  print '<tr><td valign="top">Module Compta ?</td>';
  print '<td class="valeur">';
  $form->checkbox('module_compta',0,1);
  print '</td></tr>';

  print '<tr><td valign="top">Note</td><td>';
  print "<textarea name=\"note\" rows=\"12\" cols=\"40\">";
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

      print '<div class="titre">Fiche utilisateur</div><b>'.$message.'</b><br>';

      print '<table width="100%" border="1" cellpadding="3" cellspacing="0">';
    
      print '<tr><td width="25%" valign="top">Nom</td>';
      print '<td width="25%" class="valeur">'.$fuser->nom.'</td>';
      print '<td width="25%" valign="top">Prénom</td>';
      print '<td width="25%" class="valeur">'.$fuser->prenom.'</td></tr>';
  
      print '<tr><td width="25%" valign="top">Login</td>';
      print '<td width="25%"  class="valeur">'.$fuser->login.'</td>';
      print '<td width="25%" valign="top">Email</td>';
      print '<td width="25%"  class="valeur">'.$fuser->email.'</td></tr>';
      
      print '<tr><td width="25%" valign="top">Webcal Login</td>';
      print '<td width="25%"  class="valeur">'.$fuser->webcal_login.'&nbsp;</td>';
      print '<td width="25%" valign="top">Administrateur</td>';
      print '<td width="25%"  class="valeur">'.$yn[$fuser->admin].'</td></tr>';
      
      print '<tr><td width="25%" valign="top">Module Compta</td>';
      print '<td width="25%"  class="valeur">'.$yn[$fuser->compta].'&nbsp;</td>';
      print '<td width="25%" valign="top">Module Commercial</td>';
      print '<td width="25%"  class="valeur">'.$yn[$fuser->comm].'</td></tr>';

      print '<tr><td width="25%" valign="top">Id Société</td>';
      print '<td width="25%"  class="valeur">'.$fuser->societe_id.'&nbsp;</td>';
      print '<td width="25%" valign="top">&nbsp;</td>';
      print '<td width="25%"  class="valeur">&nbsp;</td></tr>';

      print '<tr><td width="25%" valign="top">Note</td>';
      print '<td colspan="3"  class="valeur">'.nl2br($fuser->note).'&nbsp;</td></tr>';

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

      if ($user->id == $id or $user->admin)
	{
	  print '<td width="25%" align="center">[<a href="fiche.php3?action=password&id='.$id.'">Nouveau mot de passe</a>]</td>';
	}
      else 
	{
      print '<td width="25%" align="center">-</td>';
	}
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
	  print '<td><input size="30" type="text" name="email" value="'.$fuser->email.'"></td></tr>';
	  
	  print '<tr><td valign="top">Admin ?</td>';
	  print '<td class="valeur">';
	  $form->selectyesnonum('admin',$fuser->admin);
	  print '</td></tr>';

	  print '<tr><td valign="top">Login Webcal</td>';
	  print '<td class="valeur"><input size="30" type="text" name="webcal_login" value="'.$fuser->webcal_login.'"></td></tr>';
	  
	  print '<tr><td valign="top">Module Commercial ?</td>';
	  print '<td class="valeur">';
	  $form->checkbox('module_comm',$fuser->comm,1);
	  print '</td></tr>';

	  print '<tr><td valign="top">Module Compta ?</td>';
	  print '<td class="valeur">';
	  $form->checkbox('module_compta',$fuser->compta,1);
	  print '</td></tr>';

	  print '<tr><td valign="top">Description</td><td>';
	  print "<textarea name=\"note\" rows=\"12\" cols=\"40\">";
	  print $fuser->note;
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
