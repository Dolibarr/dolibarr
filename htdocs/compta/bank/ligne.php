<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier DUTOIT        <doli@sydesy.com>
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
 *
 */
require("./pre.inc.php");

if (!$user->rights->banque->modifier)
  accessforbidden();

llxHeader();

if ($_GET["action"] == 'dvnext')
{
  $ac = new Account($db);
  $ac->datev_next($_GET["rowid"]);
}


if ($_GET["action"] == 'dvprev')
{
  $ac = new Account($db);
  $ac->datev_previous($_GET["rowid"]);
}



if ($_POST["action"] == 'confirm_delete_categ' && $_POST["confirm"] == yes)
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = $rowid AND fk_categ = $cat1";
  $db->query($sql);
}


if ($action == 'class')
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_class WHERE lineid = $rowid AND fk_categ = $cat1";
  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
  if ($db->query($sql))
    {
      
    }
  else
    {
      print $db->error();
    }
}

if ($action == 'UPDATE')
{
  // Avant de modifier la date ou le montant, on controle si ce n'est pas encore rapproche
  if (!empty($_POST['amount']))
  {
    $sql = "SELECT b.rappro FROM ".MAIN_DB_PREFIX."bank as b WHERE rowid=$rowid";
    $result = $db->query($sql);
    if ($result)
    {
      $var=True;  
      $amount = str_replace(' ','',$_POST['amount']);
      $num = $db->num_rows();
      $objp = $db->fetch_object( 0);
      if ($objp->rappro)
        die ("Vous ne pouvez pas modifier une écriture déjà rapprochée");
      $sql = "UPDATE ".MAIN_DB_PREFIX."bank set label='$label' , dateo = '$date', amount='$amount' WHERE rowid = $rowid;";
    }
  }
  else 
    $sql = "UPDATE ".MAIN_DB_PREFIX."bank set label='$label' WHERE rowid = $rowid;";
$result = $db->query($sql);
}

if ($_POST["action"] == 'type')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."bank set fk_type='$value' WHERE rowid = $rowid;";
  $result = $db->query($sql);
}

if ($_POST["action"] == 'num_releve')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."bank set num_releve=$num_rel WHERE rowid = $rowid;";
  $result = $db->query($sql);
}

$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ;";
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
      $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n";
      $i++;
    }
  $db->free();
}

if ($action == 'delete_categ')
{
  $html = new Form($db);
  $html->form_confirm("ligne.php?rowid=$rowid&amp;cat1=$fk_categ","Supprimer dans la catégorie","Etes-vous sûr de vouloir supprimer le classement dans la catégorie ?","confirm_delete_categ");
}

print_titre("Edition de la ligne");
print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
print "<tr class=\"liste_titre\">";
print '<td>Date</td><td>'.$langs->trans("Description").'</td>';
print "<td align=\"right\">Debit</td>";
print "<td align=\"right\">Credit</td>";
print "<td align=\"center\">Releve</td>";
print "<td align=\"center\">".$langs->trans("Author")."</td>";

print "</TR>\n";

$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do,".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro, b.num_releve, b.author, b.num_chq, b.fk_type, fk_account";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b WHERE rowid=$rowid";
$sql .= " ORDER BY dateo ASC";
$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($i);
      $total = $total + $objp->amount;
      
      $acct=new Account($db,$objp->fk_account);
      $acct->fetch($objp->fk_account);
      $account = $acct->id;

      $var=!$var;
      print "<tr $bc[$var]>";
      print "<form method=\"post\" action=\"ligne.php?rowid=$rowid&amp;account=$account\">";
      print "<input type=\"hidden\" name=\"action\" value=\"class\">";
 
      print "<td>".strftime("%d %b %Y",$objp->do)."</td>\n";
      print "<td>$objp->label</td>";
      if ($objp->amount < 0)
	{
	  print "<td align=\"right\">".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
	}
      else
	{
	  print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
	}
    
      print "<td align=\"center\"><a href=\"releve.php?num=$objp->num_releve&amp;ve=1&amp;account=$account\">$objp->num_releve</a></td>";
      print "<td align=\"center\">$objp->author</td>";      
      print "</tr>";
      
      print "<tr $bc[$var]>";
      print '<td colspan="5">Date de valeur : '.strftime("%d %b %Y",$objp->dv)."</td>\n";
      
      print '<td><a href="ligne.php?action=dvprev&amp;account='.$_GET["account"].'&amp;rowid='.$objp->rowid.'">';
      print img_previous() . "</a> ";
      print '<a href="ligne.php?action=dvnext&amp;account='.$_GET["account"].'&amp;rowid='.$objp->rowid.'">';
      print img_next() ."</a></td>";

      print '</tr>';

      print "<tr $bc[$var]><td>&nbsp;</td><td colspan=\"5\">";
      
      print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
      print '<input type="hidden" name="action" value="type">';
      print '<select name="value">';
      print '<option value="CHQ"'.($objp->fk_type == 'CHQ'?' selected':'').'>CHQ</option>';
      print '<option value="PRE"'.($objp->fk_type == 'PRE'?' selected':'').'>PRE</option>';
      print '<option value="VIR"'.($objp->fk_type == 'VIR'?' selected':'').'>VIR</option>';
      print '<option value="CB"'.($objp->fk_type == 'CB'?' selected':'').'>CB</option>';
      print '<option value="DEP"'.($objp->fk_type == 'DEP'?' selected':'').'>Dépôt</option>';
      print "</select>";
      print $objp->num_chq?" - $objp->num_chq":'';
      print "<input type=\"submit\" value=\"UPDATE\">";
      print "</form>";

      print "</td></tr>";

      print "<tr $bc[$var]><td>Catégorie</td><td colspan=\"5\">";
      print "<select name=\"cat1\">$options";
      
      print "</select>&nbsp;";
      print '<input type="submit" value="'.$langs->trans("Add").'"></td>';
      print "</tr>";
      
      print "</form>";

      print "<tr $bc[$var]><td>Compte</td><td colspan=\"5\"><a href=\"account.php?account=$account\">".$acct->label."</a></td></tr>";

      print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
      print "<input type=\"hidden\" name=\"action\" value=\"UPDATE\">";
    
      print "<tr $bc[$var]><td>Libell&eacute;</td><td colspan=\"5\">";
      print '<input name="label" value="'.$objp->label.'">';
      print "<input type=\"submit\" value=\"UPDATE\"></td>";
      print "</tr>";
      
      if (!$objp->rappro)
      {
        print "<tr $bc[$var]><td>Date</td><td colspan=\"5\">";
        print '<input name="date" value="'.strftime("%Y%m%d",$objp->do).'">';
        print "<input type=\"submit\" value=\"UPDATE\"></td>";
        print "</tr>";
        print "<tr $bc[$var]><td>Montant</td><td colspan=\"5\">";
        print '<input name="amount" value="'.price($objp->amount).'">';
        print "<input type=\"submit\" value=\"UPDATE\"></td>";
        print "</tr>";
      }
      print "</form>";
      
      print "<form method=\"post\" action=\"ligne.php?rowid=$objp->rowid\">";
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
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Description").'</td>';
print "</tr>\n";

$sql = "SELECT c.label, c.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_class as a, ".MAIN_DB_PREFIX."bank_categ as c WHERE a.lineid=$rowid AND a.fk_categ = c.rowid ";
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
      print "<td align=\"center\"><a href=\"ligne.php?action=delete_categ&amp;rowid=$rowid&amp;fk_categ=$objp->rowid\">Supprimer</a></td>";
      print "</tr>";

      $i++;
    }
  $db->free();
}
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
