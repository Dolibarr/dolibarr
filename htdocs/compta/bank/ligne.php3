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

if ($action == 'class') {
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
  $result = $db->query($sql);

}

$sql = "SELECT rowid, label FROM llx_bank_categ;";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0;
  $options = "<option value=\"0\" SELECTED></option>";
  while ($i < $num) {
    $obj = $db->fetch_object($i);
    $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
  }
  $db->free();
}

print "<b>Edition de la ligne</b>";
print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR bgcolor=\"orange\">";
print "<td>Date</td><td>Description</TD>";
print "<td align=\"right\"><a href=\"$PHP_SELF?vue=debit\">Debit</a></TD>";
print "<td align=\"right\"><a href=\"$PHP_SELF?vue=credit\">Credit</a></TD>";
print "<td align=\"center\">Releve</TD>";
print "<td align=\"center\">Auteur</TD>";

print "</TR>\n";

$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.author";
$sql .= " FROM llx_bank as b WHERE rowid=$rowid";
$sql .= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $total = $total + $objp->amount;

    $var=!$var;
    print "<tr $bc[$var]>";
    print "<form method=\"post\" action=\"$PHP_SELF\">";
    print "<input type=\"hidden\" name=\"action\" value=\"class\">";
    print "<input type=\"hidden\" name=\"rowid\" value=\"$objp->rowid\">";

    print "<td>".strftime("%d %b %Y",$objp->do)."</TD>\n";
    print "<td>$objp->label</td>";
    if ($objp->amount < 0) {
      print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
    } else {
      print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
    }
    
    print "<td align=\"center\"><a href=\"releve.php3?num=$objp->num_releve\">$objp->num_releve</a></td>";
    print "<td align=\"center\">$objp->author</td>";

    print "</tr>";
    print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"5\">";
    print "<select name=\"cat1\">$options";

    print "</select>&nbsp;";
    print "<input type=\"submit\" value=\"add\"></td>";
    print "</tr>";

    print "</form>";
    $i++;
  }
  $db->free();
}
print "</table>";

print "<p>Classé dans</p>";

print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR bgcolor=\"orange\">";
print "<td>Description</TD>";
print "</TR>\n";

$sql = "SELECT c.label, c.rowid";
$sql .= " FROM llx_bank_class as a, llx_bank_categ as c WHERE a.lineid=$rowid AND a.fk_categ = c.rowid ";
$sql .= " ORDER BY c.label";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);

    $var=!$var;
    print "<tr $bc[$var]>";

    print "<td>$objp->label</td>";
    print "<td align=\"center\"><a href=\"budget.php3?bid=$objp->rowid\">voir</a></td>";
    print "</tr>";

    $i++;
  }
  $db->free();
}
print "</table>";




$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
