<?PHP
/*
 * $Id$
 * $Source$
 *
 *
 * $viewall
 *
 */
require("./pre.inc.php3");

require("./bank.lib.php3");
llxHeader();
$db = new Db();


if ($action == 'add') {
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author,fk_account)";
  $sql .= " VALUES (now(), $dateo, '$label', (0 - $amount),'$author',$account_from)";


  $result = $db->query($sql);
  if (!$result) {
    print $db->error();
    print "<p>$sql";
  }

  $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author,fk_account)";
  $sql .= " VALUES (now(), $dateo, '$label', $amount,'$author',$account_to)";


  $result = $db->query($sql);
  if (!$result) {
    print $db->error();
    print "<p>$sql";
  }


}
if ($action == 'del') {
  bank_delete_line($db, $rowid);
}

if ($vline) {
  $viewline = $vline;
} else {
  $viewline = 20;
}

print "<b>Virement</b> - <a href=\"$PHP_SELF\">Reload</a>&nbsp;-";
print "<a href=\"$PHP_SELF?viewall=1\">Voir tout</a>";

print "<form method=\"post\" action=\"$PHP_SELF?viewall=$viewall&vline=$vline&account=$account\">";

print "<input type=\"hidden\" name=\"action\" value=\"add\">";

print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<tr><td>De</td><td>Vers</td><td>Date</td><td>Libelle</td><td>Montant</td></tr>";
print "<tr><td>";
print "<select name=\"account_from\">";
$sql = "SELECT rowid, label FROM llx_bank_account";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
    $i = 0; $total = 0;
    
    while ($i < $num) {
      $objp = $db->fetch_object($i);
      print "<option value=\"$objp->rowid\">$objp->label</option><br>";
      $i++;
    }
}
print "</select></td><td>";

print "<select name=\"account_to\">";
$sql = "SELECT rowid, label FROM llx_bank_account";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
    $i = 0; $total = 0;
    
    while ($i < $num) {
      $objp = $db->fetch_object($i);
      print "<option value=\"$objp->rowid\">$objp->label</option><br>";
      $i++;
    }
}
print "</select></td>";

print "<td><input name=\"dateo\" type=\"text\" size=8 maxlength=8></td>";
print "<td><input name=\"label\" type=\"text\" size=40></td>";
print "<td><input name=\"amount\" type=\"text\" size=8></td>";
print "<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"ajouter\"</td>";
print "</tr><tr><td colspan=\"2\">Format : YYYYMMDD - 20010826</td><td colspan=\"2\">0000.00</td></tr>";


print "</table></form>";

print "<a href=\"categ.php3\">Edit Categories</a>";
print " <a href=\"budget.php3\">Budgets</a>";

$db->close();

llxFooter(strftime("%H:%M",time()). " - <em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
