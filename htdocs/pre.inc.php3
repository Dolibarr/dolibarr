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

require ("./main.inc.php3");

function llxHeader($head = "") {
  global $user, $conf;

  /*
   *
   *
   */
  top_menu($head);
     

  print "<TABLE border=\"1\" width=\"100%\">";
  print "<TR><TD valign=\"top\" align=\"right\">";
  /*
   * Colonne de gauche
   *
   */
  print "<TABLE border=\"1\" cellspacing=\"0\" width=\"100%\" cellpadding=\"3\">";
  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<center><b>" . $conf->db->name . " - " . $user->code ."</B></center>";
  print "<A href=\"/\">Accueil</A><br>";

  print '</td></tr>';

  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><A href=\"/comm/propal.php3\">Propales</A></center>\n";
  print '</td></tr>';

  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><A href=\"/compta/\">Factures</center></A>\n";
  print '</td></tr>';

  if ($conf->fichinter->enabled) {

    print "<TR><TD valign=\"top\" align=\"right\">";
    print "<center><A href=\"/fichinter/\">Fiches d'intervention</center></A>\n";
    print '</td></tr>';

  }

  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><A href=\"/product/\">Produits</center></A>\n";

  print "<center><A href=\"/service/\">Services</center></A>\n";
  print '</td></tr>';


  print "<TR><TD valign=\"top\" align=\"right\">";
  print '<div align="center"><A href="user.php3">Utilisateurs</div></A>';
  print '</td></tr>';

  print '<TR><TD valign="top" align="center">';

  print '<A href="comm/index.php3">Societes</A>';
  print '<form action="comm/index.php3">';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="soc">';
  print '<input type="text" name="socname" size="8">&nbsp;';
  print "<input type=\"submit\" value=\"go\">";
  print "</form>";

  print '<A href="comm/contact.php3">Contacts</A>';
  print '<form action="comm/contact.php3">';
  print '<input type="hidden" name="mode" value="search">';
  print '<input type="hidden" name="mode-search" value="contact">';
  print "<input type=\"text\" name=\"contactname\" size=\"8\">&nbsp;";
  print "<input type=\"submit\" value=\"go\">";
  print '</form>';
  print '</td></tr>';

  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<A href=\"info.php3\">Configuration</A><br>";
  print '</td></tr></table>';
  /*
   *
   *
   */
  print "</TD>\n<TD valign=\"top\" width=\"85%\">\n";

}
?>
