<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
  \file       htdocs/user/clicktodial.php
  \brief      Gestion des infos de click to dial
  \version    $Revision$
*/

require("./pre.inc.php");
require_once DOL_DOCUMENT_ROOT."/bookmark4u.class.php";

$langs->load("users");

$form = new Form($db);

if ($_POST["action"] == 'update')
{
  $edituser = new User($db, $_GET["id"]);

  $edituser->clicktodial_login    = $_POST["login"];
  $edituser->clicktodial_password = $_POST["password"];
  $edituser->clicktodial_poste    = $_POST["poste"];

  $edituser->update_clicktodial();

  Header("Location: clicktodial.php?id=".$_GET["id"]);
}

llxHeader("","Addon Utilisateur");


/* ************************************************************************** */
/*                                                                            */
/* Nouvel utilisateur                                                         */
/*                                                                            */
/* ************************************************************************** */


if ($_GET["id"])
{
  $fuser = new User($db, $_GET["id"]);
  $fuser->fetch();
  $fuser->fetch_clicktodial();

  $bk4u = new Bookmark4u($db);
  $bk4u->get_bk4u_uid($fuser);

  /*
   * Affichage onglets
   */
  
  $h = 0;
  
  $head[$h][0] = DOL_URL_ROOT.'/user/fiche.php?id='.$fuser->id;
  $head[$h][1] = $langs->trans("UserCard");
  if ($_GET["action"] != 'perms') { $hselected=$h; }
  $h++;
  
  if ($user->admin)
    {
      $head[$h][0] = DOL_URL_ROOT.'/user/fiche.php?action=perms&amp;id='.$fuser->id;
      $head[$h][1] = $langs->trans("Permissions");
      $h++;
    }
  
  if ($conf->bookmark4u->enabled)
    {
  $head[$h][0] = DOL_URL_ROOT.'/user/addon.php?id='.$fuser->id;
  $head[$h][1] = $langs->trans("Bookmark4u");
  $h++;
    }
  
  if ($conf->clicktodial->enabled)
    {
      $head[$h][0] = DOL_URL_ROOT.'/user/clicktodial.php?id='.$fuser->id;
      $head[$h][1] = $langs->trans("ClickToDial");
      $hselected=$h;
      $h++;
    }
    
  dolibarr_fiche_head($head, $hselected, $langs->trans("User")." : ".$fuser->fullname);
  
  /*
   * Fiche en mode visu
   */
  
  print '<table class="border" width="100%">';
  
  print "<tr>".'<td width="25%" valign="top">'.$langs->trans("LastName").'</td>';
  print '<td width="25%" class="valeur">'.$fuser->nom.'</td>';
  print '<td width="25%" valign="top">'.$langs->trans("FirstName").'</td>';
  print '<td width="25%" class="valeur">'.$fuser->prenom.'</td>';
  print "</tr>\n";
  
  print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Login").'</td>';
  print '<td width="25%" class="valeur">'.$fuser->login.'</td>';
  print '<td width="25%" valign="top">'.$langs->trans("EMail").'</td>';
  print '<td width="25%" class="valeur"><a href="mailto:'.$fuser->email.'">'.$fuser->email.'</a></td>';
  print "</tr>\n";
  
  print "</table>\n";
  print "<br>\n";

  if ($_GET["action"] == 'edit')
    {
      print '<form action="clicktodial.php?id='.$_GET["id"].'" method="post">';
      print '<input type="hidden" name="action" value="update"></td>';
      print '<table class="border" width="100%">'; 
      print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Login").'</td>';
      print '<td width="25%" class="valeur">';
      print '<input name="login" value="'.$fuser->clicktodial_login.'"></td>';
      
      print '<td width="25%" valign="top">'.$langs->trans("Password").'</td>';
      print '<td width="25%" class="valeur">';
      print '<input name="password" value="'.$fuser->clicktodial_password.'"></td>';
      print "</tr>\n";
      
      print "<tr>".'<td width="25%" valign="top">Poste</td>';
      print '<td width="25%" class="valeur">';
      print '<input name="poste" value="'.$fuser->clicktodial_poste.'"></td>';
      print '<td width="25%" valign="top">&nbsp;</td>';
      print '<td width="25%" valign="top">&nbsp;</td>';
      
      print "</tr>\n";
      
      print '</table><input type="submit"</form>';
    }
  else
    {

      print '<table class="border" width="100%">';
      
      print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Login").'</td>';
      print '<td width="25%" class="valeur">'.$fuser->clicktodial_login.'</td>';
      print '<td width="25%" valign="top">'.$langs->trans("Password").'</td>';
      print '<td width="25%" class="valeur">XXXXXX</a></td>';
      print "</tr>\n";
      
      print "<tr>".'<td width="25%" valign="top">Poste</td>';
      print '<td width="25%" class="valeur">'.$fuser->clicktodial_poste.'</td>';
      print '<td width="25%" valign="top">&nbsp;</td>';
      print '<td width="25%" valign="top">&nbsp;</td>';
      
      print "</tr></table>\n";
    }

  print "<br>\n";
  print "</div>\n";
  
  /*
   * Barre d'actions
   *
   */
  print '<div class="tabsAction">';
  
  if ($user->admin && $_GET["action"] <> 'edit')
    {
      print '<a class="tabAction" href="clicktodial.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
    }
    
  if ($user->admin && $_GET["action"] == 'edit')
    {
      print '<a class="tabAction" href="clicktodial.php?id='.$fuser->id.'">'.$langs->trans("Cancel").'</a>';
    }

  print "</div>\n";
  print "<br>\n";
  
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
