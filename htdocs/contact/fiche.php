<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../contact.class.php");

if ($sortorder == "") 
{
  $sortfield="lower(s.nom)";
  $sortorder="ASC";
}

if ($HTTP_POST_VARS["action"] == 'add') 
{
  $contact = new Contact($db);

  $contact->name         = $HTTP_POST_VARS["name"];
  $contact->firstname    = $HTTP_POST_VARS["firstname"];

  $contact->poste        = $HTTP_POST_VARS["poste"];

  $contact->fax          = $HTTP_POST_VARS["fax"];
  $contact->note         = $HTTP_POST_VARS["note"];
  $contact->email        = $HTTP_POST_VARS["email"];
  $contact->phone_pro    = $HTTP_POST_VARS["phone_pro"];
  $contact->phone_perso  = $HTTP_POST_VARS["phone_perso"];
  $contact->phone_mobile = $HTTP_POST_VARS["phone_mobile"];  

  $_GET["id"] =  $contact->create($user);
}

if ($_GET["action"] == 'delete') 
{
  $contact = new Contact($db);

  $contact->old_name      = $HTTP_POST_VARS["old_name"];
  $contact->old_firstname = $HTTP_POST_VARS["old_firstname"];

  $result = $contact->delete($_GET["id"]);

  Header("Location: index.php");

}


if ($action == 'update') 
{
  $contact = new Contact($db);

  $contact->old_name      = $HTTP_POST_VARS["old_name"];
  $contact->old_firstname = $HTTP_POST_VARS["old_firstname"];

  $contact->name          = $HTTP_POST_VARS["name"];
  $contact->firstname     = $HTTP_POST_VARS["firstname"];
  $contact->poste         = $poste;

  $contact->phone_pro     = $HTTP_POST_VARS["phone_pro"];
  $contact->phone_perso   = $HTTP_POST_VARS["phone_perso"];
  $contact->phone_mobile  = $HTTP_POST_VARS["phone_mobile"];
  $contact->fax           = $HTTP_POST_VARS["fax"];
  $contact->note = $note;
  $contact->email         = $HTTP_POST_VARS["email"];
  
  $result = $contact->update($HTTP_POST_VARS["contactid"]);

}

if ($action == 'create_user') 
{
  $nuser = new User($db);
  $contact = new Contact($db);
  $nuser->nom = $contact->nom;
  $nuser->prenom = $contact->prenom;
  $result = $contact->fetch($contactid);
  $nuser->create_from_contact($contact);
}

/*
 *
 *
 */

llxHeader();

if ($_GET["action"] == 'create') 
{
  print_fiche_titre ("Création d'un nouveau contact");

  print "<form method=\"post\" action=\"fiche.php\">";
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" border="0" width="100%">';
  print '<tr><td>Nom</td><td><input name="name" type="text" size="20" maxlength="80"></td>';
  print '<td>Prenom</td><td><input name="firstname" type="text" size="15" maxlength="80"></td>';

  print '<td>Tel Pro</td><td><input name="phone" type="text" size="18" maxlength="80"></td></tr>';


  print '<tr><td>Poste</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80"></td>';

  print '<td>Tel Perso</td><td><input name="phone" type="text" size="18" maxlength="80"></td></tr>';

  print '<tr><td>Adresse</td><td colspan="3"><input name="adresse" type="text" size="18" maxlength="80"></td>';

  print '<td>Mobile</td><td><input name="phone" type="text" size="18" maxlength="80"></td></tr>';

  print '<tr><td>CP Ville</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>Fax</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';
  print '<tr><td>Email</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80"></td></tr>';
  print '<tr><td align="center" colspan="4"><input type="submit" value="Ajouter"></td></tr>';
  print "</table>";
  print "</form>";
}
elseif ($_GET["action"] == 'edit') 
{

  print_fiche_titre ("Edition d'un contact");

  $contact = new Contact($db);
  $contact->fetch($_GET["id"]);

  print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="contactid" value="'.$contact->id.'">';
  print '<input type="hidden" name="old_name" value="'.$contact->name.'">';
  print '<input type="hidden" name="old_firstname" value="'.$contact->firstname.'">';
  print '<table class="border" border="0" width="100%">';
  print '<tr><td>Nom</td><td><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
  print '<td>Prenom</td><td><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->firstname.'"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td></tr>';

  print '<tr><td>Poste</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->perso.'"></td></tr>';

  print '<tr><td>Adresse</td><td colspan="3"><input name="adresse" type="text" size="18" maxlength="80"></td>';

  print '<td>Mobile</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td></tr>';

  print '<tr><td>CP Ville</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>Fax</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';
  print '<tr><td>Email</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

  print '<tr><td>Jabberid</td><td colspan="3"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

  print '<tr><td align="center" colspan="4"><input type="submit" value="Enregistrer"></td></tr>';
  print "</table>";
  print "</form>";
}
else
{
  $contact = new Contact($db);
  $contact->fetch($_GET["id"]);


  print_fiche_titre ("Contact : ". $contact->firstname.' '.$contact->name);


  print '<table class="noborder" width="100%">';
  print '<tr><td valign="top">Nom : '.$contact->name.' '.$contact->firstname;

  if ($contact->email)
    print '<br>Email : '.$contact->email;

  print '</td><td valign="top">';

  if ($contact->phone_perso)
    print 'Tel Pro : '.$contact->phone_pro ."<br>";

  if ($contact->phone_perso)
    print 'Tel Perso : '.$contact->phone_perso."<br>";

  if($contact->phone_mobile)
    print 'Portable : '.$contact->phone_mobile."<br>";

  if($contact->fax)
    print 'Fax : '.$contact->fax."<br>";


    print '</td></tr>';



  print "</table>";
  

  if ($user->societe_id == 0)
    {
      print '<p><table id="actions" width="100%"><tr>';
      
      print '<td align="center" width="20%"><a href="fiche.php?id='.$_GET["id"].'&amp;action=edit">Editer</a></td>';

      print '<td align="center" width="20%">-</td>';
      print '<td align="center" width="20%">-</td>';
      print '<td align="center" width="20%">-</td>';

      print '<td align="center" width="20%"><a href="fiche.php?id='.$_GET["id"].'&amp;action=delete">Supprimer</a></td>';
      print "</tr></table>";
    }
  print "<p>\n";

}
 

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
