<?PHP
/*
 * $Id$
 * $Source$
 */
require("./pre.inc.php3");

llxHeader();
$db = new Db();


if ($action == 'rappro') {
  $author = $GLOBALS["REMOTE_USER"];
  if ($num_releve > 0) {
    $sql = "UPDATE llx_bank set rappro=$rappro, num_releve=$num_releve WHERE rowid=$rowid";
    $result = $db->query($sql);
    if ($result) {
      if ($cat1 && $rappro) {
	$sql = "INSERT INTO llx_bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
	$result = $db->query($sql);
      }
    } else {
      print $db->error();
      print "<p>$sql";
    }
  }
}
if ($action == 'del') {
  $sql = "DELETE FROM llx_bank WHERE rowid=$rowid";
  $result = $db->query($sql);
  if (!$result) {
    print $db->error();
    print "<p>$sql";
  }
}
$sql = "SELECT rowid, label FROM llx_bank_categ ORDER BY label;";
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

$sql = "SELECT max(num_releve) FROM llx_bank";
if ( $db->query($sql) ) {
  if ( $db->num_rows() ) {
    $last_releve = $db->result(0, 0);
  }
  $db->free();
} else { print $db->error(); }

print "<b>Rapprochement bancaire</b>";
print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR bgcolor=\"orange\">";
print "<td>Date</td><td>Description</TD>";
print "<td align=\"right\"><a href=\"$PHP_SELF?vue=debit\">Debit</a></TD>";
print "<td align=\"right\"><a href=\"$PHP_SELF?vue=credit\">Credit</a></TD>";
print "<td align=\"center\">Releve</TD>";
print "<td align=\"right\">Rappro</td>";
print "</TR>\n";

$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve";
$sql .= " FROM llx_bank as b WHERE rappro=0";
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
    print "<input type=\"hidden\" name=\"action\" value=\"rappro\">";
    print "<input type=\"hidden\" name=\"rowid\" value=\"$objp->rowid\">";

    print "<td>".strftime("%d %b %Y",$objp->do)."</TD>\n";
    print "<td>$objp->label</td>";
    if ($objp->amount < 0) {
      print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
    } else {
      print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
    }
    
    print "<td align=\"right\">";
    print "<input name=\"num_releve\" type=\"text\" value=\"$last_releve\" size=\"6\" maxlength=\"6\"></td>";
    print "<td align=\"center\"><select name=\"rappro\"><option value=\"1\">oui</option><option value=\"0\" selected>non</option></select></td>";
    print "<td align=\"center\"><input type=\"submit\" value=\"do\"></td>";

    if ($objp->rappro) {
      print "<td align=\"center\"><a href=\"releve.php3?num=$objp->num_releve\">$objp->num_releve</a></td>";
    } else {
      print "<td align=\"center\"><a href=\"$PHP_SELF?action=del&rowid=$objp->rowid\">[Del]</a></td>";
    }
    print "</tr>";
    print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"7\">";
    print "<select name=\"cat1\">$options";

    print "</select>";
    print "</tr>";
    echo '<tr><td colspan="8"><hr></td></tr>';
    print "</form>";
    $i++;
  }
  $db->free();
}
print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
