<?php
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

/*!
  \file       htdocs/contact/fiche.php
  \ingroup    societe
  \brief      Onglet général d'un contact
  \version    $Revision$
*/

require("./pre.inc.php");
require_once("../contact.class.php");
require (DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

$langs->load("companies");


$error = array();


if ($_GET["action"] == 'create_user' && $user->admin) 
{
    // Recuperation contact actuel
    $contact = new Contact($db);
    $result = $contact->fetch($_GET["id"]);

    // Creation user
    $nuser = new User($db);
    $nuser->nom = $contact->nom;
    $nuser->prenom = $contact->prenom;
    $nuser->create_from_contact($contact);
}

if ($_POST["action"] == 'add') 
{
  if (! $_POST["name"] && ! $_POST["firstname"]) {
    array_push($error,"Le champ nom ou prénom est obligatoire. Cliquez sur <a href=# onclick=history.back()>Retour</a> et réessayez.");
    $_GET["id"]=0;
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

if ($_POST["action"] == 'confirm_delete' AND $_POST["confirm"] == 'yes') 
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

  if ($contact->error) 
    {
      $error = $contact->error;
    }
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
if ($_GET["id"] > 0)
{
  // Si edition contact deja existant

  $contact = new Contact($db);
  $contact->fetch($_GET["id"], $user);
  
  $h=0;
  $head[$h][0] = DOL_URL_ROOT.'/contact/fiche.php?id='.$_GET["id"];
  $head[$h][1] = "Général";
  $hselected=$h;
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/contact/perso.php?id='.$_GET["id"];
  $head[$h][1] = 'Informations personnelles';
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/contact/vcard.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("VCard");
  $h++;
  
  $head[$h][0] = DOL_URL_ROOT.'/contact/info.php?id='.$_GET["id"];
  $head[$h][1] = $langs->trans("Info");
  $h++;
  
  dolibarr_fiche_head($head, $hselected, $contact->firstname.' '.$contact->name);
}


/*
 * Confirmation de la suppression du contact
 *
 */
if ($_GET["action"] == 'delete')
{
    $form->form_confirm($_SERVER["PHP_SELF"]."?id=".$_GET["id"],"Supprimer le contact","Êtes-vous sûr de vouloir supprimer ce contact&nbsp;?","confirm_delete");
    print '<br>';
}

// Affiche les erreurs
if (sizeof($error))
{
  print '<div class="message"><br>'.LDAP_SERVER_TYPE.'<br>';
  print join("<br>",$error);
  print '<br><br></div>';
}


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
      print '<tr><td>'.$langs->trans("Company").'</td><td colspan="5">'.$objsoc->nom.'</td>';
      print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
      print '</td></tr>';
    }
  else {
      print '<tr><td>'.$langs->trans("Company").'</td><td colspan="5">';
      print $form->select_societes('','socid');
      print '</td></tr>';
  }

  print '<tr><td>'.$langs->trans("Title").'</td><td colspan="5">';
  print $form->select_civilite($obj->civilite);
  print '</td></tr>';

  print '<tr><td>'.$langs->trans("LastName").'</td><td><input name="name" type="text" size="20" maxlength="80"></td>';
  print '<td>'.$langs->trans("FirstName").'</td><td><input name="firstname" type="text" size="15" maxlength="80"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td></tr>';

  print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

  print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><input name="adresse" type="text" size="50" maxlength="80"></td>';

  print '<td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td></tr>';

  print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80"></td></tr>';
  print '<tr><td>'.$langs->trans("Email").'</td><td colspan="5"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

  print '<tr><td>Jabberid</td><td colspan="5"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

  print '<tr><td>'.$langs->trans("Note").'</td><td colspan="5"><textarea name="note" cols="60" rows="3"></textarea></td></tr>';

  print '<tr><td>Contact facturation</td><td colspan="5"><select name="facturation"><option value="0">Non<option value="1">Oui</select></td></tr>';

  print '<tr><td align="center" colspan="6"><input type="submit" value="'.$langs->trans("Add").'"></td></tr>';
  print "</table><br>";

  print "</form>";
}
elseif ($_GET["action"] == 'edit') 
{
  // Fiche en mode edition
    
  print '<form method="post" action="fiche.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="contactid" value="'.$contact->id.'">';
  print '<input type="hidden" name="old_name" value="'.$contact->name.'">';
  print '<input type="hidden" name="old_firstname" value="'.$contact->firstname.'">';
  print '<table class="border" width="100%">';

  if ($_GET["socid"] > 0)
    {
      print '<tr><td>'.$langs->trans("Company").'</td><td colspan="5">'.$objsoc->nom.'</td>';
      print '<input type="hidden" name="socid" value="'.$objsoc->id.'">';
    }

  print '<tr><td>Titre</td><td colspan="5">';
  print $form->select_civilite($contact->civilite_id);
  print '</td></tr>';

  print '<tr><td>'.$langs->trans("Lastname").'</td><td><input name="name" type="text" size="20" maxlength="80" value="'.$contact->name.'"></td>';
  print '<td>'.$langs->trans("Firstname").'</td><td><input name="firstname" type="text" size="15" maxlength="80" value="'.$contact->firstname.'"></td>';

  print '<td>Tel Pro</td><td><input name="phone_pro" type="text" size="18" maxlength="80" value="'.$contact->phone_pro.'"></td></tr>';

  print '<tr><td>Poste/Fonction</td><td colspan="3"><input name="poste" type="text" size="50" maxlength="80" value="'.$contact->poste.'"></td>';

  print '<td>Tel Perso</td><td><input name="phone_perso" type="text" size="18" maxlength="80" value="'.$contact->phone_perso.'"></td></tr>';

  print '<tr><td>'.$langs->trans("Address").'</td><td colspan="3"><input name="adresse" type="text" size="50" maxlength="80" value="'.$contact->address.'"></td>';

  print '<td>Portable</td><td><input name="phone_mobile" type="text" size="18" maxlength="80" value="'.$contact->phone_mobile.'"></td></tr>';

  print '<tr><td>'.$langs->trans("Zip").' / '.$langs->trans("Town").'</td><td colspan="3"><input name="cp" type="text" size="6" maxlength="80">&nbsp;<input name="cp" type="text" size="20" maxlength="80"></td>';

  print '<td>'.$langs->trans("Fax").'</td><td><input name="fax" type="text" size="18" maxlength="80" value="'.$contact->fax.'"></td></tr>';
  print '<tr><td>'.$langs->trans("EMail").'</td><td colspan="5"><input name="email" type="text" size="50" maxlength="80" value="'.$contact->email.'"></td></tr>';

  print '<tr><td>Jabberid</td><td colspan="5"><input name="jabberid" type="text" size="50" maxlength="80" value="'.$contact->jabberid.'"></td></tr>';

  print '<tr><td>'.$langs->trans("Note").'</td><td colspan="5">';
  print '<textarea name="note" cols="60" rows="3">';
  print nl2br($contact->note);
  print '</textarea></td></tr>';

  print '<tr><td>Contact facturation</td><td colspan="5"><select name="facturation"><option value="0">Non<option value="1">Oui</select></td></tr>';

  print '<tr><td colspan="6" align="center"><input type="submit" value="'.$langs->trans("Save").'"></td></tr>';
  print "</table><br>";

  print "</form>";
}
else
{
  /*
   * Visualisation de la fiche
   *
   */
    
  print '<table class="noborder" width="100%">';

  if ($contact->socid > 0)
    {
      $objsoc = new Societe($db);
      $objsoc->fetch($contact->socid);

      print '<tr><td>'.$langs->trans("Company").' : '.$objsoc->nom_url.'</td></tr>';
    }

  //TODO Aller chercher le libellé de la civilite a partir de l'id $contact->civilite_id
  //print '<tr><td valign="top">Titre : '.$contact->civilite."<br>";

  print '<tr><td valign="top">'.$langs->trans("Name").' : '.$contact->name.' '.$contact->firstname ."<br>";

  if ($contact->poste)
    print 'Poste : '.$contact->poste ."<br>";

  if ($contact->email) {
    print $langs->trans("EMail").' : '.$contact->email ."<br>";
    
    if (!ValidEmail($contact->email))
    {
        print "<b>".$langs->trans("ErrorBadEMail",$contact->email)."</b><br>";
    }
  
    /*
     * Pose des problèmes en cas de non connexion au Réseau
     * et en cas ou la fonction checkdnsrr n'est pas disponible dans php
     * (cas fréquent sur certains hébergeurs)
     */
    /*
    if (!check_mail($contact->email))
    {
      print "<b>Email invalide, nom de domaine incorrecte !</b><br>";
    }
    */

  }

  if ($contact->jabberid)
    print 'Jabber : '.$contact->jabberid ."<br>";

  if($contact->user_id)
    print 'Utilisateur avec accés : <a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$contact->user_id.'">Fiche utilisateur</a><br>';

  print '</td><td valign="top">';

  if ($contact->phone_pro)
    print 'Tel Pro : '.$contact->phone_pro ."<br>";

  if ($contact->phone_perso)
    print 'Tel Perso : '.$contact->phone_perso."<br>";

  if($contact->phone_mobile)
    print 'Portable : '.$contact->phone_mobile."<br>";

  if($contact->fax)
    print $langs->trans("Fax").' : '.$contact->fax."<br>";

  print '</td></tr>';

  if ($contact->note) {
    print '<tr><td>';
    print nl2br($contact->note);
    print '</td></tr>';
  }

  print "</table><br>";
  
  print "</div>";


  // Barre d'actions
  if ($user->societe_id == 0)
    {
      print '<div class="tabsAction">';
      
      print '<a class="tabAction" href="fiche.php?id='.$contact->id.'&amp;action=edit">'.$langs->trans('Edit').'</a>';    

      print '<a class="tabAction" href="fiche.php?id='.$contact->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';

      if ($contact->user_id == 0 && $user->admin)
	{
	  print '<a class="tabAction" href="fiche.php?id='.$contact->id.'&amp;action=create_user">Créer un compte</a>';
	}
      
      print "</div>";      
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
