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

function llxHeader($head = "", $urlp = "") {
  global $user, $conf;


  /*
   *
   *
   */
  top_menu($head);


  print "<TABLE border=\"1\" width=\"100%\" cellpadding=\"0\">";
  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<TABLE border=\"1\" cellspacing=\"0\" width=\"100%\" cellpadding=\"3\">";
  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><b>" . $conf->db->name . " - " . $user->code ."</B></center>";
  print "<A href=\"".$urlp."../\">Accueil</A><br>";
  print "<A href=\"".$urlp."bookmark.php3\">Bookmark</A>";
  print "</td></tr>";
  print "<tr><td valign=\"top\" align=\"right\">";

  print "<CENTER><A href=\"".$urlp."index.php3\">Clients</A></CENTER>\n";
  print "<A href=\"".$urlp."../soc.php3?&action=create\">Nouvelle société</A><BR>\n";
  print "<A href=\"".$urlp."contact.php3\">Contacts</A><BR><br>\n";
  print "</TD></TR>";


  print '<TR><TD valign="top" align="right" bgcolor="#e0e0e0">';
  print "<center><A href=\"index.php3\">Fiches d'intervention</center></A>\n";
  print '</td></tr>';

  /*
   *
   */
  print '<TR><TD valign="top" align="right">';
  print '<div align="center"><A href="'.$urlp.'propal.php3">Propal</A></div>';
  print "</TD></TR>";
  /*
   *
   */
  print "<TR><TD valign=\"top\" align=\"right\">";
  print '<div align="center"><A href="'.$urlp.'../compta/">Factures</A></div>';
  print "</TD></TR>";
  /*
   *
   */

  /*
   *
   */
  print "<tr><td align=\"right\" valign=\"top\">";
  print "<CENTER><A href=\"".$urlp."index.php3\">Societes</A></CENTER>\n";
  print "<form action=\"index.php3\">";
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="soc">';
  print '<input type="text" name="socname" size="8">&nbsp;';
  print "<input type=\"submit\" value=\"go\">";
  print "</form>";

  print "<CENTER><A href=\"".$urlp."contact.php3\">Contacts</A></CENTER>\n";
  print "<form action=\"".$urlp."contact.php3\">";
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="contact">';
  print "<input type=\"text\" name=\"contactname\" size=\"8\">&nbsp;";
  print "<input type=\"submit\" value=\"go\">";
  print "</form>";


  print "<form action=\"index.php3\">";
  print "Soc : <input type=\"text\" name=\"socid\" size=\"5\">";
  print "<input type=\"submit\" value=\"id\">";
  print "</form>";
  print "</td></tr>";

  print "</table>";

  print "</td>";


  print "<TD valign=\"top\" width=\"85%\">\n";
}

/*
 * $Id$
 * $Source$
 */
?>
