<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php3");

llxHeader();

print 'Utilisateur : ' . $user->prenom . ' ' . $user->nom .' ['.$user->code.']';


function valeur($sql) {
  global $db;
  if ( $db->query($sql) ) {
    if ( $db->num_rows() ) {
      $valeur = $db->result(0,0);
    }
    $db->free();
  }
  return $valeur;
}
/*
 *
 */
$db = new Db();

if ($db->ok) {

  print '<br><br><TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

  print '<tr><td valign="top" width="30%">';

  print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
  print "<TR class=\"liste_titre\">";
  print "<td colspan=\"2\">Propositions commerciales</td>";
  print "</TR>\n";

  $sql = "SELECT count(*) FROM llx_propal WHERE fk_statut = 0";
  if (valeur($sql)) {
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"comm/propal.php3?viewstatut=0\">Broullionsrouillons</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
  }

  $sql = "SELECT count(*) FROM llx_propal WHERE fk_statut = 1";
  if (valeur($sql)) {
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"comm/propal.php3?viewstatut=1\">Ouvertes</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
  }


  print "</table><br>";
  /*
   * Factures
   *
   */
  print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
  print "<TR class=\"liste_titre\">";
  print "<td colspan=\"2\">Factures</td>";
  print "</TR>\n";


  $sql = "SELECT count(*) FROM llx_facture WHERE paye=0";
  if (valeur($sql)) {
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"compta/index.php3\">Factures en attente de paiement</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
  }
  print "</table><br>";
  /*
   *
   *
   */

  /*
   *
   *
   */
  print '</td><td valign="top" width="70%">';

  print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
  print "<TR class=\"liste_titre\">";
  print "<td colspan=\"2\">Actions a faire</td>";
  print "</TR>\n";


  $sql = "SELECT datea, label FROM llx_todocomm WHERE fk_user_author = $user->id";
  if (valeur($sql)) {
    $i=0;
    if ( $db->query($sql) ) {
      while ($i < $db->num_rows() ) {
	$obj = $db->fetch_object($i);
	$var=!$var;
	
	print "<tr $bc[$var]><td>".$obj->datea."</td><td><a href=\"compta/index.php3\">$obj->label</a></td></tr>";
	$i++;
      }
      $db->free();
    }
  }
  print "</table><br>";


  print '</td></tr>';

  print '</table>';

  $db->close();
 
}
$Id = "Révision";
llxFooter("<em>$Id$</em>");
?>










