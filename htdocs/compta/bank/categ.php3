<?PHP
/*
 * $Id$
 * $Source$
 */
require("pre.inc.php3");
require("../../lib/functions.inc.php3");
llxHeader();
$db = new Db();

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

if ($action == 'add') {
  $author = $GLOBALS["REMOTE_USER"];

  if ($credit > 0) {
    $amount = $credit ;
  } else {
    $amount = - $debit ;
  }

  $sql = "INSERT INTO llx_bank_categ (label) VALUES ('$label')";
  $result = $db->query($sql);
  if (!$result) {
    print $db->error();
    print "<p>$sql";
  }
}

print "<b>Categorie</b> <a href=\"$PHP_SELF\">reload</a>";
print "<form method=\"post\" action=\"$PHP_SELF\">";
print "<input type=\"hidden\" name=\"action\" value=\"add\">";
print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR bgcolor=\"orange\">";
print "<td>Num</td><td colspan=\"2\">Description</TD>";
print "</TR>\n";


$sql = "SELECT rowid, label FROM llx_bank_categ";

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  $i = 0; $total = 0;
    
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<tr $bc[$var]>";
    print "<td>$objp->rowid</td>";
    print "<td colspan=\"2\">$objp->label</td>";
    print "</tr>";
    $i++;
  }
  $db->free();
}
print "<tr>";
print "<td><td><input name=\"label\" type=\"text\" size=45></td>";
print "<td align=\"center\"><input type=\"submit\" value=\"ajouter\"</td></tr>";
print "</table></form>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
