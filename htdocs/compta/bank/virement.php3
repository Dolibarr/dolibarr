<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * $Id$
 * $Source$
 *
 */
require("./pre.inc.php3");

require("./bank.lib.php3");
llxHeader();
$db = new Db();


if ($action == 'add')
{
  $dateo = $reyear."-".$remonth."-".$reday;

  $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, fk_user_author,fk_account, fk_type)";
  $sql .= " VALUES (now(), '$dateo', '$label', (0 - $amount),$user->id,$account_from, 'VIR')";

  $result = $db->query($sql);
  if (!$result)
    {
      print $db->error();
      print "<p>$sql";
    }

  $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, fk_user_author,fk_account, fk_type)";
  $sql .= " VALUES (now(), '$dateo', '$label', $amount,$user->id, $account_to, 'VIR')";


  $result = $db->query($sql);
  if (!$result)
    {
      print $db->error();
      print "<p>$sql";
    } 
}

print_titre("Virement");

print "<form method=\"post\" action=\"$PHP_SELF\">";

print '<input type="hidden" name="action" value="add">';

print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="2">';
print "<tr><td>De</td><td>Vers</td><td>Date</td><td>Libelle</td><td>Montant</td></tr>";
print "<tr><td>";
print "<select name=\"account_from\">";
$sql = "SELECT rowid, label FROM llx_bank_account";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
    $i = 0; $total = 0;
    
    while ($i < $num)
      {
	$objp = $db->fetch_object($i);
	print "<option value=\"$objp->rowid\">$objp->label</option><br>";
	$i++;
      }
}
print "</select></td><td>\n";

print "<select name=\"account_to\">";
$sql = "SELECT rowid, label FROM llx_bank_account";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
    $i = 0; $total = 0;
    
    while ($i < $num)
      {
	$objp = $db->fetch_object($i);
	print "<option value=\"$objp->rowid\">$objp->label</option><br>";
	$i++;
      }
}
print "</select></td>\n";

print "<td>";
print_date_select();
print "</td>\n";
print "<td><input name=\"label\" type=\"text\" size=40></td>";
print "<td><input name=\"amount\" type=\"text\" size=8></td>";


print '<tr><td colspan="5" align="center"><input type="submit" value="ajouter"</td></tr>';

print "</table></form>";



$db->close();

llxFooter(" - <em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
