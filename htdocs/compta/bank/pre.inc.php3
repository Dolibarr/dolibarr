<?PHP
// $Id$

$root = "/$PREFIX";

require ("/$GLJ_WWW_ROOT/conf/$GLJ_PREFIX.$GLJ_COUNTRY.inc.php3");
require ("/$GLJ_WWW_ROOT/../www/lib/db.lib.php3");

function llxHeader($head = "") {
  global $PREFIX;

  print "<HTML>\n<HEAD>$head\n</HEAD>\n";
  ?>
  <BODY BGCOLOR="#c0c0c0" TOPMARGIN="0" BOTTOMMARGIN="0" LEFTMARGIN="0" RIGHTMARGIN="0" MARGINHEIGHT="0" MARGINWIDTH="0">
  <?PHP

  print "<TABLE border=\"0\" width=\"100%\">\n";
  print "<TR bgcolor=\"".$GLOBALS["TOPBAR_BGCOLOR"]."\">";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\"><B>" . $GLOBALS["MAIN_TITLE"] . "</B></TD>";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\" align=\"center\"><A href=\"../../tech/\">Technique</A></TD>";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\" align=\"center\"><A href=\"../../comm/\">Commercial</A></TD>";
  print "<TD width=\"20%\" align=\"center\"><A href=\"../\">Compta</A></TD>";
  print "<TD width=\"20%\" bgcolor=\"#e0e0e0\" align=\"center\"><A href=\"../../stats/\">Stats</A></TD>";
  print "</TR></TABLE>\n";

  print "<TABLE border=\"1\" width=\"100%\">";
  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<TABLE border=\"1\" cellspacing=\"0\" width=\"100%\" cellpadding=\"3\">";
  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<center><b>" . $GLOBALS["dbname"] . " - " . $GLOBALS["REMOTE_USER"] ."</B></center>";
  print "<A href=\"/\">Accueil</A>";

  print "</td></tr>";

  print "<TR><TD valign=\"top\" align=\"right\"><center>";
  print "<A href=\"index.php3\">Bank</A></center>\n";
  print "<A href=\"rappro.php3\">Rappro</A><p>\n";
  print "<A href=\"budget.php3\">Budgets</A><BR>\n";
  print "<A href=\"bilan.php3\">Bilan</A><p>\n";
  print '<a href="releve.php3">Relevés bancaires</a>';
  print "</td></tr>";

  print "<tr><td valign=\"top\" align=\"right\">";
  print "<center><a href=\"../index.php3\">Factures</a></center><p>\n";
  print "<a href=\"../paiement.php3\">Paiements</A><BR>\n";

  print "</td></tr>";
  print "<TR><TD valign=\"top\" align=\"right\">";

  print "<CENTER>Chiffres d'affaires</CENTER><br>\n";
  print "<A href=\"../ca.php3\">Réalisé</A><BR>\n";
  print "<A href=\"../prev.php3\">Prévisionnel</A><BR>\n";
  print "<A href=\"../comp.php3\">Comparatif</A>\n";

  print "</td></tr>";

  print "<TR><TD valign=\"top\" align=\"center\">";
  print "<A href=\"../../comm/propal.php3\">Propal</A><BR>\n";
  print "</td></tr>";




  print "</table>";


  print "</TD>\n<TD valign=\"top\" width=\"85%\">\n";
}
/*
 *
 */
function llxFooter($foot='') {
  print "</TD></TR></TABLE>\n";
  print "$foot</BODY></HTML>";
}
// $Id$
// $Source$
?>
