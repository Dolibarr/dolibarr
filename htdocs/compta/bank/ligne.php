<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Xavier DUTOIT <doli@sydesy.com>
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
require("./pre.inc.php");

llxHeader();

if ($action == 'class')
{
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
  $result = $db->query($sql);
}

if ($action == 'update')
{
  $author = $GLOBALS["REMOTE_USER"];
  //avant de modifier la date ou le montant, on controle si ce n'est pas encore rapproche
//print_r ($_POST);
  if (!empty($_POST['amount']))
  {
    $sql = "SELECT b.rappro FROM llx_bank as b WHERE rowid=$rowid";
    $result = $db->query($sql);
    if ($result)
    {
      $var=True;  
      $num = $db->num_rows();
      $objp = $db->fetch_object( 0);
      if ($objp->rappro)
        die ("Vous ne pouvez pas modifier une écriture déjà rapprochée");
      $sql = "update llx_bank set label='$label' , dateo = '$date', amount='$amount' where rowid = $rowid;";
    }
  }
  else 
    $sql = "update llx_bank set label='$label' where rowid = $rowid;";
$result = $db->query($sql);
}

if ($HTTP_POST_VARS["action"] == 'type')
{
  $sql = "update llx_bank set fk_type='$value' where rowid = $rowid;";
  $result = $db->query($sql);
}

if ($HTTP_POST_VARS["action"] == 'num_releve')
{
  $sql = "update llx_bank set num_releve=$num_rel where rowid = $rowid;";
  $result = $db->query($sql);
}

$sql = "SELECT rowid, label FROM llx_bank_categ;";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
  $i = 0;
  $options = "<option value=\"0\" SELECTED></option>";
  while ($i < $num)
    {
      $obj = $db->fetch_object($i);
      $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
    }
  $db->free();
}

$acct=new Account($db,$account);
$acct->fetch($account);

print_titre("Edition de la ligne");
print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
print "<TR class=\"liste_titre\">";
print "<td>Date</td><td>Description</TD>";
print "<td align=\"right\">Debit</TD>";
print "<td align=\"right\">Credit</TD>";
print "<td align=\"center\">Releve</TD>";
print "<td align=\"center\">Auteur</TD>";

print "</TR>\n";

$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.author, b.num_chq, b.fk_type";
$sql .= " FROM llx_bank as b WHERE rowid=$rowid";
$sql .= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $total = $total + $objp->amount;
      
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<form method=\"post\" action=\"$PHP_SELF\">";
      print "<input type=\"hidden\" name=\"action\" value=\"class\">";
      print "<input type=\"hidden\" name=\"rowid\" value=\"$objp->rowid\">";
      
 
      print "<td>".strftime("%d %b %Y",$objp->do)."</TD>\n";
      print "<td>$objp->label</td>";
      if ($objp->amount < 0)
	{
	  print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
	}
      else
	{
	  print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
	}
    
      print "<td align=\"center\"><a href=\"releve.php?num=$objp->num_releve&ve=1\">$objp->num_releve</a></td>";
      print "<td align=\"center\">$objp->author</td>";      
      print "</tr>";
      
      print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"5\">";
      print $objp->fk_type .' - ';
      print $objp->num_chq;
      print "</tr>";

      print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"5\">";
      print "<select name=\"cat1\">$options";
      
      print "</select>&nbsp;";
      print "<input type=\"submit\" value=\"Ajouter\"></td>";
      print "</tr>";
      
      print "</form>";

      print "<tr $bc[$var]><td>Compte</td><td colspan=\"5\"><a href=\"account.php?account=$account\">".$acct->label."</a></td></tr>";

      print "<form method=\"post\" action=\"$PHP_SELF?rowid=$objp->rowid\">";
      print "<input type=\"hidden\" name=\"action\" value=\"update\">";
    
      print "<tr $bc[$var]><td>Libell&eacute;</td><td colspan=\"5\">";
      print '<input name="label" value="'.$objp->label.'">';
      print "<input type=\"submit\" value=\"update\"></td>";
      print "</tr>";
      
      if (!$objp->rappro)
      {
        print "<tr $bc[$var]><td>Date</td><td colspan=\"5\">";
        print '<input name="date" value="'.strftime("%Y%m%d",$objp->do).'">';
        print "<input type=\"submit\" value=\"update\"></td>";
        print "</tr>";
        print "<tr $bc[$var]><td>Montant</td><td colspan=\"5\">";
        print '<input name="amount" value="'.price($objp->amount).'">';
        print "<input type=\"submit\" value=\"update\"></td>";
        print "</tr>";
      }
      print "</form>";
      
      
      print "<form method=\"post\" action=\"$PHP_SELF?rowid=$objp->rowid\">";
      print '<input type="hidden" name="action" value="type">';
      
      print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"5\">";
      print '<select name="value">';
      print '<option value="CHQ">CHQ';
      print '<option value="PRE">PRE';
      print '<option value="VIR">VIR';
      print '<option value="CB">CB';
      print '<option value="DEP">Dépôt';
      
      print "</select><input type=\"submit\" value=\"update\"></td>";
      print "</tr></form>";

      print "<form method=\"post\" action=\"$PHP_SELF?rowid=$objp->rowid\">";
      print '<input type="hidden" name="action" value="num_releve">';
      print "<tr $bc[$var]><td>Relevé</td><td colspan=\"5\">";
      print '<input name="num_rel" value="'.$objp->num_releve.'">';
      print "<input type=\"submit\" value=\"Mettre à jour\"></td>";
      print "</tr>";

      print "</form>";
            
      $i++;
    }
  $db->free();
}
print "</table>";

print "<p>Classé dans</p>";

print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
print "<TR class=\"liste_titre\">";
print "<td>Description</TD>";
print "</TR>\n";

$sql = "SELECT c.label, c.rowid";
$sql .= " FROM llx_bank_class as a, llx_bank_categ as c WHERE a.lineid=$rowid AND a.fk_categ = c.rowid ";
$sql .= " ORDER BY c.label";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);

      $var=!$var;
      print "<tr $bc[$var]>";
      
      print "<td>$objp->label</td>";
      print "<td align=\"center\"><a href=\"budget.php?bid=$objp->rowid\">voir</a></td>";
      print "</tr>";

      $i++;
    }
  $db->free();
}
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
