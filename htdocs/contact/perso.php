<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../contact.class.php");
require (DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

if ($_POST["action"] == 'update') 
{
  $contact = new Contact($db);
  $contact->id = $_POST["contactid"];

  if ($_POST["birthdayyear"]) {
    if ($_POST["birthdayyear"]<=1970 && $_SERVER["WINDIR"]) {
        # windows mktime does not support negative date timestamp so birthday is not support for old persons
        $contact->birthday = $_POST["birthdayyear"].'-'.$_POST["birthdaymonth"].'-'.$_POST["birthdayday"];
        //array_push($error,"Windows ne sachant pas gérer des dates avant 1970, les dates de naissance avant cette date ne seront pas sauvegardées");
    } else {
        $contact->birthday     = mktime(0,0,0,$_POST["birthdaymonth"],$_POST["birthdayday"],$_POST["birthdayyear"]);
    }
  }

  $contact->birthday_alert = $_POST["birthday_alert"];

  $result = $contact->update_perso($_POST["contactid"], $user);
}

/*
 *
 *
 */
llxHeader();

print '<div class="tabs">';
print '<a class="tab" href="fiche.php?id='.$_GET["id"].'">Général</a>';
print '<a class="tab" href="perso.php?id='.$_GET["id"].'" id="active">Informations personnelles</a>';
print '<a class="tab" href="vcard.php?id='.$_GET["id"].'">VCard</a>';
print '<a class="tab" href="info.php?id='.$_GET["id"].'">Info</a>';
print '</div>';
print '<div class="tabBar">';

if ($_GET["action"] == 'edit') 
{
  // Fiche info perso en mode edition
 
  $contact = new Contact($db);
  $contact->fetch($_GET["id"], $user);

  print_fiche_titre ("Contact : ". $contact->firstname.' '.$contact->name);

  print '<form method="post" action="perso.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="contactid" value="'.$contact->id.'">';

  if ($contact->socid > 0)
    {
      $objsoc = new Societe($db);
      $objsoc->fetch($contact->socid);

      print 'Société : '.$objsoc->nom_url.'<br>';
    }

  print 'Nom : '.$contact->name.' '.$contact->firstname ."<br>";

  print '<table class="border" cellpadding="3" celspacing="0" border="0" width="100%">';

  $html = new Form($db);

  print '<tr><td>Date de naissance</td><td>';
  if ($contact->birthday && $contact->birthday > 0) { 
    print $html->select_date($contact->birthday,'birthday',0,0,0);
  } else {
    print $html->select_date(0,'birthday',0,0,1);
  }
  print '</td>';

  print '<td>Alerte : ';
  if ($contact->birthday_alert)
    {
      print '<input type="checkbox" name="birthday_alert" checked></td></tr>';
    }
  else
    {
      print '<input type="checkbox" name="birthday_alert"></td></tr>';
    }

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

  if ($contact->socid > 0)
    {
      $objsoc = new Societe($db);
      $objsoc->fetch($contact->socid);

      print 'Société : '.$objsoc->nom_url.'<br>';
    }

  if ($contact->birthday && $contact->birthday > 0) {
    print 'Date de naissance : '.dolibarr_print_date($contact->birthday);

    if ($contact->birthday_alert)
      print ' (alerte anniversaire active)<br>';
    else
      print ' (alerte anniversaire inactive)<br>';
  }
  print "<br>";

  print "</div>";

  // Barre d'actions
  if ($user->societe_id == 0)
    {
      print '<div class="tabsAction">';
      
      print '<a class="tabAction" href="perso.php?id='.$_GET["id"].'&amp;action=edit">'.$langs->trans('Edit').'</a>';    

      print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=deleteWARNING">'.$langs->trans('Delete').'</a>';
      
      print "</div>";      
    }
}
  $db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
