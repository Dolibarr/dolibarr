<?PHP
/*
 * $Id$
 * $Source$
 */
require("./pre.inc.php3");
require("../../lib/functions.inc.php3");
llxHeader();
$db = new Db();

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

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


print "<b>Bilan</b>";

print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR bgcolor=\"orange\">";
print "<td>Description</td><td align=\"right\">Montant</TD><td align=\"right\">francs</TD>";
print "</TR>\n";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_paiement";
$paiem = valeur($sql);
print "<tr $bc[$var]><td>Somme des paiements</td><td align=\"right\">".price($paiem)."</td><td align=\"right\">".francs($paiem)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_bank WHERE amount > 0";
$credits = valeur($sql);
print "<tr $bc[$var]><td>Somme des credits</td><td align=\"right\">".price($credits)."</td><td align=\"right\">".francs($credits)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_bank WHERE amount < 0";
$debits = valeur($sql);
print "<tr $bc[$var]><td>Somme des debits</td><td align=\"right\">".price($debits)."</td><td align=\"right\">".francs($debits)."</td></tr>";

$var=!$var;
$sql = "SELECT sum(amount) FROM llx_bank ";
$solde = valeur($sql);
print "<tr $bc[$var]><td>Solde compte</td><td align=\"right\">".price($solde)."</td><td align=\"right\">".francs($solde)."</td></tr>";


print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
