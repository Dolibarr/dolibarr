<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");


/*
 *
 */
llxHeader();

if ($sortorder == "") {
  $sortfield="lower(s.nom)";
  $sortorder="ASC";
}

if ($action == 'stcomm') {
  $sql = "UPDATE societe SET fk_stcomm=$stcommid WHERE idp=$socid";
  $result = $db->query($sql);

    $sql = "INSERT INTO socstatutlog (datel, fk_soc, fk_statut, author) VALUES (now(),$socid,$stcommid,'" . $GLOBALS["REMOTE_USER"] . "')";
    $result = $db->query($sql);


  if ($actioncommid) {
    $sql = "INSERT INTO actioncomm (datea, fk_action, fk_soc) VALUES (now(),$actioncommid,$socid)";
    $result = $db->query($sql);
  }
}

if ($socid > 0) {

  $soc = new Societe($db, $socid);
  $soc->fetch($socid);
  /*
   *
   */

  print_titre($soc->nom);


  print '<table border="1" width="100%" cellspacing="0" cellpadding="3">';
  print "<tr><td>";
  print "<form method=\"post\" action=\"fiche.php3?socid=$soc->id\">";
  print "<input type=\"hidden\" name=\"action\" value=\"note\">";
  print "<textarea name=\"note\" cols=\"60\" rows=\"10\">$soc->note</textarea><br>";
  print "<input type=\"submit\">";
  print "</form></td></tr>";
  print "<tr><td>Notes actuelles</td></tr>";
  print "<tr><td>".nl2br($soc->note)."</td></tr>";
  print "</table>";
    

}

$db->close();

llxFooter();
?>
