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

require("./pre.inc.php3");
require("./bank.lib.php3");

llxHeader();
$db = new Db();

if ($action == 'add') {
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_bank_account (label, number, bank) VALUES ('$label','$number','$bank')";
  $result = $db->query($sql);
}
if ($action == 'del') {
  bank_delete_line($db, $rowid);
}


print "<b>Configuration</b>";

print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR bgcolor=\"orange\">";
print "<td>id</td><td>Label</td><td>Description</TD>";
print "<td align=\"left\">Number</a></TD>";
print "</TR>\n";

$sql = "SELECT rowid, label,number,bank from llx_bank_account";

$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;

  $sep = 0;

  while ($i < $num) {
    $objp = $db->fetch_object( $i);


    print "<tr><td>$objp->rowid</td><td>$objp->label</td><td>$objp->bank</td><td>$objp->number</td></tr>";


    $i++;
  }
  $db->free();
}
print "</table>";
echo '<br><br>';
print "<form method=\"post\" action=\"$PHP_SELF?viewall=$viewall&vline=$vline\">";
print "<input type=\"hidden\" name=\"action\" value=\"add\">";
print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
echo '<tr><td>Label&nbsp;:&nbsp;<input type="text" name="label"></td>';
echo '<td>Bank&nbsp;:&nbsp;<input type="text" name="bank"></td>';
echo '<td>Number&nbsp;:&nbsp;<input type="text" name="number"></td>';
echo '<td><input type="submit" value="ajouter"></td></tr>';

print "</table></form>";


$db->close();

llxFooter(strftime("%H:%M",time()). " - <em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
