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
require("../../paiement.class.php");

/*
 *
 *
 */

llxHeader();

print '<div class="tabs">';
print '<a href="fiche.php?id='.$_GET["id"].'" id="active" class="tab">Paiement</a>';
//print '<a class="tab" href="info.php?id='.$_GET["id"].'">Info</a>';
print '</div>';

print '<div class="tabBar">';

  /*
   * Visualisation de la fiche
   *
   */
    
  $paiement = new Paiement($db);
  $paiement->fetch($_GET["id"]);

  print '<table class="noborder" width="100%">';

  print '<tr><td valign="top">Numéro : '.$paiement->numero."<br>";

  print 'Montant : '.$paiement->montant."&nbsp;".MAIN_MONNAIE."<br>";

  print '</td></tr>';
  print "</table>";

  print nl2br($paiement->note);

  print "</div>";

  if ($user->societe_id == 0)
    {
      print '<div class="tabsAction">';
      
      print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=edit">Editer</a>';    

      print '<a class="tabAction" href="fiche.php?id='.$_GET["id"].'&amp;action=deleteWARNING">Supprimer</a>';
      
      print "</div>";      
    }

  $db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
