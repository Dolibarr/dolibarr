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
/*
 *
 *
 */
llxHeader();

print '<div class="tabs">';
print '<a class="tab" href="fiche.php?id='.$_GET["id"].'">Général</a>';
print '<a class="tab" href="perso.php?id='.$_GET["id"].'">Informations personnelles</a>';
print '<a class="tab" href="vcard.php?id='.$_GET["id"].'">VCard</a>';
print '<a class="tab" href="info.php?id='.$_GET["id"].'" id="active">Info</a>';
print '</div>';
print '<div class="tabBar">';

/*
 * Visualisation de la fiche
 *
 */

$contact = new Contact($db);
$contact->fetch($_GET["id"], $user);
$contact->info($_GET["id"]);
  
print_fiche_titre ("Contact : ". $contact->firstname.' '.$contact->name);

if ($contact->socid > 0)
{
  $objsoc = new Societe($db);
  $objsoc->fetch($contact->socid);
  
  print 'Société : '.$objsoc->nom.'<br>';
}
print "Créé par  : " . $contact->user_creation->fullname . '<br>';
print "Date de création : " . strftime("%A %d %B %Y %H:%M:%S",$contact->date_creation) . '<br>';
print "Modifié par  : " . '<br>';
print "Date de modification : " . strftime("%A %d %B %Y %H:%M:%S",$contact->date_modification) . '<br>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
