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
  global $PREFIX, $user;

  print "<HTML>\n<HEAD>$head\n</HEAD>\n";
  ?>
  <BODY BGCOLOR="#c0c0c0" TOPMARGIN="0" BOTTOMMARGIN="0" LEFTMARGIN="0" RIGHTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">
  <?PHP

  print "<TABLE border=\"0\" width=\"100%\">\n";
  print "<TR bgcolor=\"".$GLOBALS["SYS_TOPBAR_BGCOLOR"]."\">";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\"><B>" . $GLOBALS["MAIN_TITLE"] . "</B></TD>";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\" align=\"center\"><A href=\"".$urlp."../tech/\">Technique</A></TD>";

  print '<TD width="20%" bgcolor="#e0e0e0" align="center">';
  if ($user->comm > 0) {
    print '<A href="../comm/">Commercial</A></TD>';
  } else {
    print 'Commercial';
  }
  
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\" align=\"center\"><A href=\"../compta/\">Compta</A></TD>";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\" align=\"center\"><A href=\"../stats/\">Stats</A></TD>";
  print "</TR></TABLE>\n";

  print "<TABLE border=\"1\" width=\"100%\">";
  print "<TR><TD valign=\"top\" align=\"right\">";
  /*
   * Colonne de gauche
   *
   */
  print "<TABLE border=\"1\" cellspacing=\"0\" width=\"100%\" cellpadding=\"3\">";
  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<center><b>" . $GLOBALS["DB_NAME"] . " - " . $user->code ."</B></center>";
  print "<A href=\"/\">Accueil</A><br>";

  print "<A href=\"/graph/\">Stat. quantitatives</A><br>\n";
  print "<A href=\"/stats/\">Stat. qualitatives</A><br>\n";
  print "<A href=\"/dict/\">Dictionnaires</A>\n";

  print '</td></tr>';
  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><A href=\"/comm/propal.php3\">Propal</A></center>\n";
  print '</td></tr>';

  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><A href=\"/compta/\">Factures</center></A>\n";
  print '</td></tr>';

  print "<TR><TD valign=\"top\" align=\"right\">";
  print "<center><A href=\"/product/\">Produits</center></A>\n";
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

function llxFooter($foot='') {
  print "</TD></TR></TABLE>\n";
  print "$foot</BODY></HTML>";
}

?>
