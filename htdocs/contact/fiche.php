<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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

$error = array();

if ($_POST["action"] == 'add') 
{
  if (! $_POST["name"] && ! $_POST["firstname"]) {
    array_push($error,"Le champ nom ou prénom est obligatoire");
    $_GET["id"]=0;
    // TODO Mettre lien back
  }
  else {
      $contact = new Contact($db);
    
      $contact->socid        = $_POST["socid"];
    
      $contact->name         = $_POST["name"];
      $contact->firstname    = $_POST["firstname"];
      $contact->civilite_id	 = $_POST["civilite_id"];
      $contact->poste        = $_POST["poste"];
      $contact->address      = $_POST["adresse"];
      $contact->cp           = $_POST["cp"];
      $contact->ville        = $_POST["ville"];
      $contact->email        = $_POST["email"];
      $contact->phone_pro    = $_POST["phone_pro"];
      $contact->phone_perso  = $_POST["phone_perso"];
      $contact->phone_mobile = $_POST["phone_mobile"];  
      $contact->fax          = $_POST["fax"];
      $contact->jabberid     = $_POST["jabberid"];
    
      $contact->note         = $_POST["note"];
    
      $_GET["id"] =  $contact->create($user);
  }
}

if ($_GET["action"] == 'delete') 
{
  $contact = new Contact($db);

  $contact->old_name      = $_POST["old_name"];
  $contact->old_firstname = $_POST["old_firstname"];

  $result = $contact->delete($_GET["id"]);

  Header("Location: index.php");
}


if ($_POST["action"] == 'update') 
{
  $contact = new Contact($db);

  $contact->old_name      = $_POST["old_name"];
  $contact->old_firstname = $_POST["old_firstname"];

  $contact->socid         = $_POST["socid"];
  $contact->name          = $_POST["name"];
  $contact->firstname     = $_POST["firstname"];
  $contact->civilite_id	  = $_POST["civilite_id"];
  $contact->poste         = $_POST["poste"];

  $contact->address       = $_POST["adresse"];
  $contact->cp            = $_POST["cp"];
  $contact->ville         = $_POST["ville"];

  $contact->email         = $_POST["email"];
  $contact->phone_pro     = $_POST["phone_pro"];
  $contact->phone_perso   = $_POST["phone_perso"];
  $contact->phone_mobile  = $_POST["phone_mobile"];
  $contact->fax           = $_POST["fax"];
  $contact->jabberid      = $_POST["jabberid"];

  $contact->note          = $_POST["note"];

  $result = $contact->update($_POST["contactid"], $user);

  if ($contact->error) { array_push($error,$contact->error); }
}

if ($_GET["action"] == 'create_user')
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
$form = new Form($db);

/*
 * Onglets
 */
print '<div class="tabs">';
if ($_GET["id"] > 0)
{
    # Si edition contact deja existant
    print '<a href="fiche.php?id='.$_GET["id"].'" id="active" class="tab">Général</a>';
    print '<a href="perso.php?id='.$_GET["id"].'" class="tab">Informations personnelles</a>';
    print '<a class="tab" href="vcard.php?id='.$_GET["id"].'">VCard</a>';
    print '<a class="tab" href="info.php?id='.$_GET["id"].'">Info</a>';
}
else {
    print '<a href="'.$_SERVER["PHP_SELF"].'?socid='.$_GET["socid"].'&amp;action=create" id="active" class="tab">Général</a>';
}
print '</div>';


// Affiche les erreurs
if (sizeof($error))
{
  print '<div class="message"><br>';
  print join("<br>",$error);
  print '<br><br></div>';
}


print '<div class="tabBar">';

if ($_GET["socid"] > 0)
{
  $objsoc = new Societe($db);
  $objsoc->fetch($_GET["socid"]);
}

if ($_GET["action"] == 'create')
{
  // Fiche en mode creation
  print '<br>';

  print "<form method=\"post\" action=\"fiche.php\">";
  print '<input type="hidden" name="action" value="add">';
  print '<table class="border" width="100%">';

  if ($_GET["socid"] > 0)
    {
      print '<tr><td>Société</td><td colspan="5">'.$objsoc->nom.'</td>';
      print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
    }

  print '<tr><td>Titre</td><td colspan="5">';
  print $form->select_civilite();
  print '</td></tr>';

  print '<tr><td>Nom</td><td><input name="name" type="text" size="20" maxlength="80"></td>';
  print '<td>Prenom</td><td><input name="firstname" type="text" size="15" maxlength="80"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td></tr>';

  print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

  print '<tr><td>Adresse</td><td colspan="3"><input name="adresse" type="text" size="50" maxlength="80"></td>';

  print '<td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td></tr>';

  print '<tr><td>CP Ville</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>Fax</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';
  print '<tr><td>Email</td><td colspan="5"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

  print '<tr><td>Jabberid</td><td colspan="5"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

//  print '<tr><td>Date de naissance</td><td colspan="5">';
//  print $form->select_date('','birthday',0,0,1);
//  print '</td></tr>';

  print '<tr><td>Note</td><td colspan="5"><textarea name="note" cols="60" rows="3"></textarea></td></tr>';
  print '<tr><td align="center" colspan="6"><input type="submit" value="Ajouter"></td></tr>';
  print "</table>";
  print "</form>";
}
elseif ($_GET["action"] == 'edit') 
{
  // Fiche en mode edition
  print '<br>';
    
  $contact = new Contact($db);
  $contact->fetch($_GET["id"], $user);

  print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="contactid" value="'.$contact->id.'">';
  print '<input type="hidden" name="old_name" value="'.$contact->name.'">';
  print '<input type="hidden" name="old_firstname" value="'.$contact->firstname.'">';
  print '<table class="border" width="100%">';

  if ($_GET["socid"] > 0)
    {
      print '<tr><td>Société</td><td colspan="5">'.$objsoc->nom.'</td>';
      print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
    }

  print '<tr><td>Titre</td><td colspan="5">';
  print $form->select_civilite($contact->civilite_id);
  print '</td></tr>';

  print '<tr><td>Nom</td><td><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
  print '<td>Prénom</td><td><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->firstname.'"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td></tr>';

  print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

  print '<tr><td>Adresse</td><td colspan="3"><input name="adresse" type="text" size="50" maxlength="80" value="'.$contact->address.'"></td>';

  print '<td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td></tr>';

  print '<tr><td>CP Ville</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>Fax</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.$contact->fax.'"></td></tr>';
  print '<tr><td>Email</td><td colspan="5"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

  print '<tr><td>Jabberid</td><td colspan="5"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

  print '<tr><td>Note</td><td colspan="5">';
  print '<textarea name="note" cols="60" rows="3">';
  print nl2br($contact->note);
  print '</textarea></td></tr>';
  print '<tr><td colspan="6" align="center"><input type="submit" value="Enregistrer"></td></tr>';
  print "</table>";

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


  //TODO Aller chercher le libellé de la civilite a partir de l'id $contact->civilite_id
  //print '<tr><td valign="top">Titre : '.$contact->civilite."<br>";

  print '<tr><td valign="top">Nom : '.$contact->name.' '.$contact->firstname ."<br>";


  if ($contact->poste)
    print 'Poste : '.$contact->poste ."<br>";

  if ($contact->email)
    print 'Email : '.$contact->email ."<br>";

  if ($contact->jabberid)
    print 'Jabber : '.$contact->jabberid ."<br>";

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

  if ($contact->note) {
    print '<tr><td>';
    print nl2br($contact->note);
    print '</td></tr>';
  }

  print "</table><br>";
  
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
