<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

require("./pre.inc.php");
require("./bank.lib.php");

$user->getrights('banque');

if (!$user->rights->banque->lire)
  accessforbidden();


llxHeader();

if ($vline) {
  $viewline = $vline;
} else {
  $viewline = 50;
}

print_titre("Recherche écriture bancaire");
print '<br>';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
print "<tr class=\"liste_titre\">";
print '<td>Date</td><td>'.$langs->trans("Description").'</td>';
print "<td align=\"right\">Debit</td>";
print "<td align=\"right\">Credit</td>";
print "<td align=\"center\">".$langs->trans("Type")."</td>";
print "<td align=\"left\">Compte</td>";
print "</tr>\n";
?>

<form method="post" action="search.php">
<tr class="liste_titre">
<td>&nbsp;</td>
<td>
<input type="text" name="description" size="40" value=<?php echo $description ?>>
</td>
<td align="right">
<input type="text" name="debit" size="6" value=<?php echo $debit ?>>
</td>
<td align="right">
<input type="text" name="credit" size="6" value=<?php echo $credit ?>>
</td>
<td align="center">
<select name="type">
<option value="%">Toutes</option>
<option value="CHQ">CHQ</option>
<option value="CB">CB</option>
<option value="VIR">VIR</option>
<option value="PRE">PRE</option>
</select>
</td>
<td align="left">
<input type="submit" name="submit" value="<?php echo $langs->trans("Search") ?>">
</td>
</tr>
<?PHP

// Compte le nombre total d'écritures
$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."bank";
if ($account) { $sql .= " WHERE b.fk_account=$account"; }
if ( $db->query($sql) )
{
  $nbline = $db->result (0, 0);
  $db->free();    
}
  
// Defini la liste des catégories dans $options
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ;";
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

$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_account, b.fk_type, ba.label as labelaccount";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."bank_account as ba";
$sql .= " WHERE b.fk_account=ba.rowid";

$sql .= " AND fk_type like '" . $type . "'";

$si=0;

$debit = ereg_replace(',','.',$debit);
$credit = ereg_replace(',','.',$credit);
if (is_numeric($debit)) {
  $si++;
  $sqlw[$si] .= " b.amount = " . -$debit;
}
if (is_numeric($credit)) {
  $si++;
  $sqlw[$si] .= " b.amount = " . $credit;
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

if ($result)
{
  $var=True;  
  $num = $db->num_rows();
  $i = 0;   
  
  while ($i < $num) {
    $objp = $db->fetch_object( $i);

    $var=!$var;

    print "<tr $bc[$var]>";
    print "<td>".strftime("%d %b %y",$objp->do)."</TD>\n";
      
    print "<td><a href=\"ligne.php?rowid=$objp->rowid&amp;account=$objp->fk_account\">$objp->label</a>&nbsp;";
    
    if ($objp->amount < 0)
      {
	print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
      }
    else
      {
	print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
      }
    
    print "<td align=\"center\">".$objp->fk_type."</TD>\n";

      
    print "<td align=\"left\"><small>".$objp->labelaccount."</small></TD>\n";
    print "</tr>";
    
    $i++;
  }

  $db->free();
} else {
  print $db->error() .' <div class="div.titre">' . $sql .'</div>';
}

print "</table>";

// Si accès issu d'une recherche et rien de trouvé
if ($_POST["submit"] && ! $num) {
	print "Aucune écriture bancaire répondant aux critères n'a été trouvée.";
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
