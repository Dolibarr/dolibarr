<?php
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

/*!
	    \file       htdocs/contact/perso.php
        \ingroup    societe
		\brief      Onglet informations personnelles d'un contact
		\version    $Revision$
*/

require("./pre.inc.php");
require("../contact.class.php");
require (DOL_DOCUMENT_ROOT."/lib/vcard/vcard.class.php");

$langs->load("companies");


if ($_POST["action"] == 'update') 
{
  $contact = new Contact($db);
  $contact->id = $_POST["contactid"];

  if ($_POST["birthdayyear"]) {
    if ($_POST["birthdayyear"]<=1970 && $_SERVER["WINDIR"]) {
        # windows mktime does not support negative date timestamp so birthday is not supported for old persons
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

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);


$h=0;
$head[$h][0] = DOL_URL_ROOT.'fiche.php?id='.$_GET["id"];
$head[$h][1] = "Général";
$h++;

$head[$h][0] = DOL_URL_ROOT.'perso.php?id='.$_GET["id"];
$head[$h][1] = 'Informations personnelles';
$hselected=$h;
$h++;

$head[$h][0] = DOL_URL_ROOT.'vcard.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("VCard");
$h++;

$head[$h][0] = DOL_URL_ROOT.'info.php?id='.$_GET["id"];
$head[$h][1] = $langs->trans("Info");
$h++;

dolibarr_fiche_head($head, $hselected, $contact->firstname.' '.$contact->name);


if ($_GET["action"] == 'edit') 
{
  // Fiche info perso en mode edition

  print '<form method="post" action="perso.php?id='.$_GET["id"].'">';
  print '<input type="hidden" name="action" value="update">';
  print '<input type="hidden" name="contactid" value="'.$contact->id.'">';

  if ($contact->socid > 0)
    {
      $objsoc = new Societe($db);
      $objsoc->fetch($contact->socid);

      print $langs->trans("Company").' : '.$objsoc->nom_url.'<br>';
    }

  print $langs->trans("Name").' : '.$contact->name.' '.$contact->firstname ."<br>";

  print '<table class="border" width="100%">';

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
      print '<input type="checkbox" name="birthday_alert" checked></td>';
    }
  else
    {
      print '<input type="checkbox" name="birthday_alert"></td>';
    }

  print "</tr></table>";

  print '<div class="FicheSubmit"><input type="submit" value="'.$langs->trans("Save").'">';

  print "</form>";
}
else
{
  /*
   * Visualisation de la fiche
   *
   */
    print '<table width="100%"><tr><td>';
    
  if ($contact->socid > 0)
    {
      $objsoc = new Societe($db);
      $objsoc->fetch($contact->socid);

      print $langs->trans("Company").' : '.$objsoc->nom_url.'<br>';
    }

  if ($contact->birthday && $contact->birthday > 0) {
    print 'Date de naissance : '.dolibarr_print_date($contact->birthday);

    if ($contact->birthday_alert)
      print ' (alerte anniversaire active)<br>';
    else
      print ' (alerte anniversaire inactive)<br>';
  }
  else {
    print 'Date de naissance : '.$langs->trans("Unknown")."<br>";
  }
  
  print "<br>";
  print "</table>";

  print "</div>";

  // Barre d'actions
  if ($user->societe_id == 0)
    {
      print '<div class="tabsAction">';
      
      print '<a class="tabAction" href="perso.php?id='.$_GET["id"].'&amp;action=edit">'.$langs->trans('Edit').'</a>';    

      print "</div>";      
    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
