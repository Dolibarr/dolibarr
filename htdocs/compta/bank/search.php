<?PHP
/********************************************************************************
 * Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *******************************************************************************/

require("./pre.inc.php");
require("./bank.lib.php");
llxHeader();

if ($vline) {
  $viewline = $vline;
} else {
  $viewline = 50;
}

print_titre("Bank");

print "<input type=\"hidden\" name=\"action\" value=\"add\">";
print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
print "<TR class=\"liste_titre\">";
print "<td>Date</td><td>Description</TD>";
print "<td align=\"right\">Debit</TD>";
print "<td align=\"right\">Credit</TD>";
print "<td align=\"right\">Type</TD>";
print "<td align=\"right\">Compte</td>";
print "</TR>\n";
?>

<form method="post" action="search.php">
<tr>
<td></td>
<td>
<input type="text" name="description">
</td>
<td>
<input type="text" name="credit">
</td>
<td>
<select name="type">
<option value="%">%
<option value="CHQ">CHQ
<option value="CB">CB
<option value="VIR">VIR
<option value="PRE">PRE
</select>
</td>
<td colspan="2">
<input type="submit">
</td>
</tr>
<?PHP
$sql = "SELECT count(*) FROM llx_bank";
if ($account) { $sql .= " WHERE fk_account=$account"; }
if ( $db->query($sql) ) {
  $nbline = $db->result (0, 0);
  $db->free();
    
  if ($nbline > $viewline ) {
    $limit = $nbline - $viewline ;
  } else {
    $limit = $viewline;
  }
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


if ($viewall) { $nbline=0; }


$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_account, b.fk_type";
$sql .= " FROM llx_bank as b "; 
$sql .= " WHERE fk_type like '" . $type . "'";

$si=0;

if ($credit) {
  $si++;
  $sqlw[$si] .= " b.amount " . $credit;
}

if ($description) {
  $si++;
  $sqlw[$si] .= " b.label like '%" . $description . "%'";
}

for ($i = 1 ; $i <= $si; $i++) {
 $sql .= " AND " . $sqlw[$i];
}


$sql .= " ORDER BY b.dateo ASC";

$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0;   
  
  while ($i < $num) {
    $objp = $db->fetch_object( $i);

    $var=!$var;

    print "<tr $bc[$var]>";
    print "<td>".strftime("%d %b %y",$objp->do)."</TD>\n";
      
    if ($objp->num_chq) {
      print "<td>CHQ $objp->num_chq - $objp->label</td>";
    } else {
      print "<td>$objp->label</td>";
    }
    
    if ($objp->amount < 0) {
      print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
    } else {
      print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
    }
    
    print "<td align=\"right\">".$obj->fktype."</TD>\n";

      
    print "<td align=\"right\"><small>".$objp->fk_account."</small></TD>\n";
    print "</tr>";
    
    $i++;
  }
  $db->free();
} else {
  print $db->error() ."<p>" . $sql;
}


print "</table><p><small>";

print "$sql</small>";
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
