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
require("../main.inc.php3");

function llxHeader($head = "") {
  global $PREFIX, $user, $conf;


  /*
   *
   *
   */
  top_menu($head);


  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<TABLE border=\"1\" cellspacing=\"0\" width=\"100%\" cellpadding=\"3\">";

  print '<TR><TD class="barre_select" valign="top" align="right">';
  print '<div align="center"><A href="/compta/index.php3">Factures</A></div><p>';
  print "<A href=\"paiement.php3\">Paiements</A><BR>\n";
  print "<A href=\"fac.php3\">admin fac</A><BR>\n";
  print "</td></tr>";

  print "<TR><TD class=\"barre\" valign=\"top\" align=\"right\">";
  print "<div align=\"center\">Chiffres d'affaires</div><br>\n";
  print "<A href=\"ca.php3\">Réalisé</A><BR>\n";
  print "<A href=\"prev.php3\">Prévisionnel</A><BR>\n";
  print "<A href=\"comp.php3\">Comparatif</A>\n";
  print "</td></tr>";

  print "<TR><TD class=\"barre\" valign=\"top\" align=\"right\">";
  print "<CENTER>Analyses</CENTER><br>\n";
  print "<A href=\"casoc.php3\">CA par societe</A><BR>\n";
  print "<A href=\"pointmort.php3\">Point mort</A><BR>\n";
  print "<A href=\"tva.php3\">TVA</A><BR>\n";
  print "</td></tr>";

  print "<TR><TD class=\"barre\" valign=\"top\" align=\"right\">";
  print "<A href=\"/comm/propal.php3\">Propal</A><BR>\n";
  print "</td></tr>";

  print "<TR><TD class=\"barre\" valign=\"top\" align=\"right\">";
  print "<A href=\"bank/index.php3\">Bank</A><BR>\n";
  print "</td></tr>";


  print "<TR><TD class=\"barre\" valign=\"top\" align=\"right\">";
  print "<A href=\"/comm/index.php3\">Societes</A>\n";
  print "<form action=\"/comm/index.php3\">";
  print "<input type=\"text\" name=\"socname\" size=\"8\">";
  print "<input type=\"submit\" value=\"nom\">";
  print "</form>";

  print "<form action=\"/comm/index.php3\">";
  print "<input type=\"text\" name=\"socid\" size=\"5\">";
  print "<input type=\"submit\" value=\"id\">";
  print "</form>";
  print "</td></tr>";

  print "</table>";


  print "</TD>\n<TD valign=\"top\" width=\"85%\">\n";
}
/*
 *
 */

// $Id$
// $Source$
?>
