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
require (DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

if ($HTTP_POST_VARS["action"] == 'add') 
{
  $contact = new Contact($db);

  $contact->socid        = $HTTP_POST_VARS["socid"];

  $contact->name         = $HTTP_POST_VARS["name"];
  $contact->firstname    = $HTTP_POST_VARS["firstname"];

  $contact->poste        = $HTTP_POST_VARS["poste"];

  $contact->address       = $HTTP_POST_VARS["adresse"];
  $contact->cp            = $HTTP_POST_VARS["cp"];
  $contact->ville         = $HTTP_POST_VARS["ville"];

  $contact->fax          = $HTTP_POST_VARS["fax"];
  $contact->note         = $HTTP_POST_VARS["note"];
  $contact->email        = $HTTP_POST_VARS["email"];
  $contact->phone_pro    = $HTTP_POST_VARS["phone_pro"];
  $contact->phone_perso  = $HTTP_POST_VARS["phone_perso"];
  $contact->phone_mobile = $HTTP_POST_VARS["phone_mobile"];  
  $contact->jabberid     = $HTTP_POST_VARS["jabberid"];

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
  $contact->poste         = $HTTP_POST_VARS["poste"];

  $contact->address       = $HTTP_POST_VARS["adresse"];
  $contact->cp            = $HTTP_POST_VARS["cp"];
  $contact->ville         = $HTTP_POST_VARS["ville"];

  $contact->phone_pro     = $HTTP_POST_VARS["phone_pro"];
  $contact->phone_perso   = $HTTP_POST_VARS["phone_perso"];
  $contact->phone_mobile  = $HTTP_POST_VARS["phone_mobile"];
  $contact->fax           = $HTTP_POST_VARS["fax"];
  $contact->note          = $HTTP_POST_VARS["note"];
  $contact->email         = $HTTP_POST_VARS["email"];
  $contact->jabberid      = $HTTP_POST_VARS["jabberid"];

  $result = $contact->update($HTTP_POST_VARS["contactid"], $user);

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

print '<div class="tabs">';
print '<a href="fiche.php?id='.$_GET["id"].'" id="active" class="tab">Général</a>';
print '<a href="perso.php?id='.$_GET["id"].'" class="tab">Informations personnelles</a>';
print '<a class="tab" href="vcard.php?id='.$_GET["id"].'">VCard</a>';
print '<a class="tab" href="info.php?id='.$_GET["id"].'">Info</a>';
print '</div>';

if ($mesg)
{
  print '<div class="message">'.$mesg;
  print '</div>';
}


print '<div class="tabBar">';

if ($_GET["socid"] > 0)
{
  $objsoc = new Societe($db);
  $objsoc->fetch($_GET["socid"]);
}

if ($_GET["action"] == 'create') 
{
  print_fiche_titre ("Création d'un nouveau contact");

  print "<form method=\"post\" action=\"fiche.php\">";
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" border="0" width="100%">';

  if ($_GET["socid"] > 0)
    {
      print '<tr><td>Société</td><td colspan="5">'.$objsoc->nom.'</td>';
      print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
    }

  print '<tr><td>Nom</td><td><input name="name" type="text" size="20" maxlength="80"></td>';
  print '<td>Prenom</td><td><input name="firstname" type="text" size="15" maxlength="80"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80"></td></tr>';

  print '<tr><td>Poste</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80"></td></tr>';

  print '<tr><td>Adresse</td><td colspan="3"><input name="adresse" type="text" size="18" maxlength="80"></td>';

  print '<td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80"></td></tr>';

  print '<tr><td>CP Ville</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="ville" type="text" size="20" maxlength="80"></td>';

  print '<td>Fax</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';
  print '<tr><td>Email</td><td colspan="3"><input name="email" type="text" size="50" maxlength="80"></td></tr>';
  print '<tr><td>Note</td><td colspan="5"><textarea name="note"></textarea></td></tr>';
  print '<tr><td align="center" colspan="6"><input type="submit" value="Ajouter"></td></tr>';
  print "</table>";
  print "</form>";
}
elseif ($_GET["action"] == 'edit') 
{
  print_fiche_titre ("Edition d'un contact");

  $contact = new Contact($db);
  $contact->fetch($_GET["id"], $user);

  print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="contactid" value="'.$contact->id.'">';
  print '<input type="hidden" name="old_name" value="'.$contact->name.'">';
  print '<input type="hidden" name="old_firstname" value="'.$contact->firstname.'">';
  print '<table class="border" cellpadding="3" celspacing="0" border="0" width="100%">';

  if ($_GET["socid"] > 0)
    {
      print '<tr><td>Société</td><td colspan="5">'.$objsoc->nom.'</td>';
    }

  print '<tr><td>Nom</td><td><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
  print '<td>Prenom</td><td><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->firstname.'"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td></tr>';

  print '<tr><td>Poste</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

  print '<tr><td>Adresse</td><td colspan="3"><input name="adresse" type="text" size="18" maxlength="80"></td>';

  print '<td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td></tr>';

  print '<tr><td>CP Ville</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>Fax</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';
  print '<tr><td>Email</td><td colspan="5"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

  print '<tr><td>Jabberid</td><td colspan="5"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

  print '<tr><td>Note</td><td colspan="5"><textarea name="note"></textarea></td></tr>';
  print "</table>";

  print '<div class="FicheSubmit"><input type="submit" value="Enregistrer">';
  

  print "</form>";
}
else
{
  /*
   * Visualisation de la fiche
   *
   */
    
  $contact = new Contact($db);
  $contact->fetch($_GET["id"], $user);

  print_fiche_titre ("Contact : ". $contact->firstname.' '.$contact->name);

  print '<table class="noborder" width="100%">';


  if ($contact->socid > 0)
    {
      $objsoc = new Societe($db);
      $objsoc->fetch($contact->socid);

      print '<tr><td>Société : '.$objsoc->nom_url.'</td></tr>';
    }

  print '<tr><td valign="top">Nom : '.$contact->name.' '.$contact->firstname ."<br>";

  if ($contact->poste)
    print 'Poste : '.$contact->poste ."<br>";

  if ($contact->email)
    print 'Email : '.$contact->email ."<br>";

  if ($contact->jabberid)
    print 'Jabber : '.$contact->jabberid ."<br>";

  print "<br>";

  print '</td><td valign="top">';

  if ($contact->phone_pro)
    print 'Tel Pro : '.$contact->phone_pro ."<br>";

  if ($contact->phone_perso)
    print 'Tel Perso : '.$contact->phone_perso."<br>";

  if($contact->phone_mobile)
    print 'Portable : '.$contact->phone_mobile."<br>";

  if($contact->fax)
    print 'Fax : '.$contact->fax."<br>";

  print '</td></tr>';
  print "</table>";

  print nl2br($contact->note);

  print "</div>";

  if ($user->societe_id == 0)
    {
      print '<div class="tabsAction">';
      
      print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=edit">Editer</a>';    

      print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=deleteWARNING">Supprimer</a>';
      
      print "</div>";      
    }
}
  $db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
