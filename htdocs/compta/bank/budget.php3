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

/*
 *
 *
 *           TODO attention des sommes positives sont a consideres
 *
 *
 *           exemple remboursement de frais de gestion par la banque
 *
 *
 *
 *
 */

if ($bid == 0) {
  /*
   *   Liste
   */
  print "<b>Budgets</b>";

  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR bgcolor=\"orange\">";
  echo '<td>Description</TD><td>Nb</td><td colspan="2">Total</td>';
  print "</TR>\n";

  $sql = "SELECT sum(d.amount) as somme, count(*) as nombre, c.label, c.rowid ";
  $sql .= " FROM llx_bank_categ as c, llx_bank_class as l, llx_bank as d";
  $sql .= " WHERE d.rowid=l.lineid AND c.rowid = l.fk_categ GROUP BY c.label, c.rowid ORDER BY c.label";
  
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0;
    
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"$PHP_SELF?bid=$objp->rowid\">$objp->label</a></td>";
      print "<td>$objp->nombre</td>";
      print "<td align=\"right\">".price(abs($objp->somme))."</td>";
      print "<td align=\"right\"><small>".francs(abs($objp->somme))."</small></td>";
      print "</tr>";
      $i++;
      $total = $total + abs($objp->somme);
    }
    $db->free();
    print "<tr><td colspan=\"2\" align=\"right\">Total</td><td align=\"right\"><b>".price($total)."</b></td></tr>";
    print "<tr><td colspan=\"3\" align=\"right\"><small>soit en francs</td><td align=\"right\"><small>".francs($total)."</td></tr>\n";
  } else {
    print $db->error();
  }
  print "</table>";

} else {
  /*
   *  Vue
   */
  $sql = "SELECT label FROM llx_bank_categ WHERE rowid=$bid";
  if ( $db->query($sql) ) {
    if ( $db->num_rows() ) {
      $budget_name = $db->result(0,0);
    }
    $db->free();
  }

  print "<b>Budget : $budget_name</b>";

  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR bgcolor=\"orange\">";
  echo '<td align="right">Date</td><td width="60%">Description</td><td align="right">Montant</td><td>&nbsp;</td>';
  print "</TR>\n";

  $sql = "SELECT d.amount, d.label, ".$db->pdate("d.dateo")." as do, d.rowid";
  $sql .= " FROM llx_bank_class as l, llx_bank as d";
  $sql .= " WHERE d.rowid=l.lineid AND l.fk_categ=$bid ORDER by d.dateo DESC";
  
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0;
    
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td align=\"right\">".strftime("%d %B %Y",$objp->do)."</TD>\n";

      print "<td><a href=\"ligne.php3?rowid=$objp->rowid\">$objp->label</a></td>";
      print "<td align=\"right\">".price(abs($objp->amount))."</td>";
      print "<td align=\"right\"><small>".francs(abs($objp->amount))."</small></td>";

      print "</tr>";
      $i++;
      $total = $total + $objp->amount;
    }
    $db->free();
    print "<tr><td colspan=\"2\" align=\"right\">Total</td><td align=\"right\"><b>".price(abs($total))."</b></td><td>euros</td></tr>";
    print "<tr><td colspan=\"2\" align=\"right\"><small>soit</td><td align=\"right\"><small>".francs(abs($total))."</td><td><small>francs</small></tr>\n";
  } else {
    print $db->error();
  }
  print "</table>";

}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
