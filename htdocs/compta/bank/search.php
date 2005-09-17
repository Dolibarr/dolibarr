<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/compta/bank/search.php
		\ingroup    banque
		\brief      Page de recherche de transactions bancaires
		\version    $Revision$
*/

require("./pre.inc.php");
require("./bank.lib.php");

$user->getrights('banque');

if (!$user->rights->banque->lire)
  accessforbidden();

$description=$_POST["description"];
$debit=$_POST["debit"];
$credit=$_POST["credit"];
$type=$_POST["type"];
$account=$_POST["account"];


llxHeader();

if ($vline) $viewline = $vline;
else $viewline = 50;


print_titre($langs->trans("SearchBankMovement"));
print '<br>';

print '<table class="liste" width="100%">';
print '<tr class="liste_titre">';
print '<td class="liste_titre">'.$langs->trans("Date").'</td>';
print '<td class="liste_titre">'.$langs->trans("Description").'</td>';
print '<td class="liste_titre" align="right">'.$langs->trans("Debit").'</td>';
print '<td class="liste_titre" align="right">'.$langs->trans("Credit").'</td>';
print '<td class="liste_titre" align="center">'.$langs->trans("Type").'</td>';
print '<td class="liste_titre" align="left">'.$langs->trans("Account").'</td>';
print "</tr>\n";
?>

<form method="post" action="search.php">
<tr class="liste_titre">
<td class="liste_titre">&nbsp;</td>
<td class="liste_titre">
<input type="text" class="flat" name="description" size="40" value=<?php echo $description ?>>
</td>
<td class="liste_titre" align="right">
<input type="text" class="flat" name="debit" size="6" value=<?php echo $debit ?>>
</td>
<td class="liste_titre" align="right">
<input type="text" class="flat" name="credit" size="6" value=<?php echo $credit ?>>
</td>
<td class="liste_titre" align="center">
<select class="flat" name="type">
<option value="%">Toutes</option>
<option value="CHQ">CHQ</option>
<option value="CB">CB</option>
<option value="VIR">VIR</option>
<option value="PRE">PRE</option>
</select>
</td>
<?php
print '<td class="liste_titre" align="right">';
print '<input type="hidden" name="action" value="search">';
print '<input type="image" class="liste_titre" name="submit" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
print '</td>';
print '</tr>';


// Compte le nombre total d'écritures
$sql = "SELECT count(*) FROM ".MAIN_DB_PREFIX."bank";
if ($account) { $sql .= " WHERE b.fk_account=$account"; }

$resql=$db->query($sql);
if ($resql)
{
    $nbline = $db->result (0, 0);
    $db->free($resql);
}
else {
    dolibarr_print_error($db);    
}

// Defini la liste des catégories dans $options
$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ;";
$result = $db->query($sql);
if ($result) {
    $var=True;  
    $num = $db->num_rows($result);
    $i = 0;
    $options = "<option value=\"0\" selected=\"true\">&nbsp;</option>";
    while ($i < $num) {
        $obj = $db->fetch_object($result);
        $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
    }
    $db->free($result);
}
else {
    dolibarr_print_error($db);    
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
  $num = $db->num_rows($result);
  $i = 0;   
  
  while ($i < $num) {
    $objp = $db->fetch_object($result);

    $var=!$var;

    print "<tr $bc[$var]>";
    print "<td>".strftime("%d %b %y",$objp->do)."</td>\n";
      
    print "<td><a href=\"ligne.php?rowid=$objp->rowid&amp;account=$objp->fk_account\">$objp->label</a>&nbsp;";
    
    if ($objp->amount < 0)
      {
	print "<td align=\"right\">".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
      }
    else
      {
	print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</td>\n";
      }
    
    print "<td align=\"center\">".$objp->fk_type."</td>\n";

      
    print "<td align=\"left\"><small>".$objp->labelaccount."</small></td>\n";
    print "</tr>";
    
    $i++;
  }

  $db->free($result);
}
else
{
  dolibarr_print_error($db);
}

print "</table>";

// Si accès issu d'une recherche et rien de trouvé
if ($_POST["action"] == "search" && ! $num) {
	print "Aucune écriture bancaire répondant aux critères n'a été trouvée.";
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
