<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$user->getrights('compta');

if (!$user->admin && !$user->rights->compta->bank)
  accessforbidden();

llxHeader();

/*
 * Action rapprochement
 */
if ($_POST["action"] == 'rappro')
{
  if ($_POST["num_releve"] > 0) {

    $valrappro=$_POST["rappro"]=='yes'?1:0;

    $sql = "UPDATE ".MAIN_DB_PREFIX."bank set rappro=$valrappro, num_releve=".$_POST["num_releve"];
    if ($_POST["rappro"]) {
        # Si on fait un rapprochement, le user de rapprochement est inclus dans l'update
        $sql .= ", fk_user_rappro=".$user->id;
    }
    $sql .= " WHERE rowid=".$_POST["rowid"];

    $result = $db->query($sql);

    if ($result) {
      if ($cat1 && $_POST["action"]) {
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
	$result = $db->query($sql);
      }
    } else {
      print dolibarr_print_error($db,$sql);
    }
  }
}

/*
 * Action suppression ecriture
 */
if ($_GET["action"] == 'del') {
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."bank WHERE rowid=".$_GET["rowid"];
  $result = $db->query($sql);
  if (!$result) {
    print dolibarr_print_error($db,$sql);
  }
}

$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."bank_categ ORDER BY label";
$result = $db->query($sql);
$options="";
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num) {
    if ($options == "") { $options = "<option value=\"0\" selected>&nbsp;</option>"; }
    $obj = $db->fetch_object($i);
    $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
  }
  $db->free();
}


/*
 * Affichage page
 */
$sql = "SELECT max(num_releve) FROM ".MAIN_DB_PREFIX."bank WHERE fk_account=".$_GET["account"];
if ( $db->query($sql) )
{
  if ( $db->num_rows() )
    {
      $last_releve = $db->result(0, 0);
    }
  $db->free();
}
else
{
  print $db->error();
}

$acct = new Account($db);
$acct->fetch($_GET["account"]);


$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b WHERE rappro=0 AND fk_account=".$_GET["account"];
$sql .= " ORDER BY dateo ASC LIMIT 10";


$result = $db->query($sql);
if ($result)
{
  $var=True;  
  $num = $db->num_rows();

  if ($num == 0) {
	//print "<br>Pas ou plus de transactions saisies, en attente de rapprochement, pour ce compte bancaire.<br>";
    header("Location: /compta/bank/account.php?account=".$account);
    exit;
  }
  else {

    print_titre('Rapprochement compte bancaire: <a href="account.php?account='.$account.'">'.$acct->label.'</a>');
    print '<br>';

	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
	print "<tr class=\"liste_titre\">";
	print '<td>Date</td><td>'.$langs->trans("Description").'</td>';
	print "<td align=\"right\">Debit</td>";
	print "<td align=\"right\">Credit</td>";
	print "<td align=\"center\">Releve</td>";
	print '<td align="center" colspan="2">Rappro</td>';
	print '<td align="center">&nbsp;</td>';
	print "</tr>\n";
  }

  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);

      $var=!$var;
      print "<tr $bc[$var]>";
      print '<form method="post" action="rappro.php?account='.$_GET["account"].'">';
      print "<input type=\"hidden\" name=\"action\" value=\"rappro\">";
      print "<input type=\"hidden\" name=\"account\" value=\"".$_GET["account"]."\">";
      print "<input type=\"hidden\" name=\"rowid\" value=\"".$objp->rowid."\">";
      
      print "<td>".dolibarr_print_date($objp->do)."</td>\n";
      print "<td>$objp->label</td>";

      if ($objp->amount < 0)
	{
	  print "<td align=\"right\">".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
	}
      else
	{
	  print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
	}
    
      if ($objp->do <= mktime() ) {
	      print "<td align=\"center\">";
	      print "<input name=\"num_releve\" type=\"text\" value=\"$last_releve\" size=\"8\" maxlength=\"6\"></td>";
	      print "<td align=\"center\">";
          $html=new Form($db);
          $html->selectyesno("rappro","no");
	      print "</td>";
	      print "<td align=\"center\"><input type=\"submit\" value=\"".$langs->trans("Rapprocher")."\"></td>";
	  }
	  else {
	      print "<td align=\"right\" colspan=\"3\">";
		  print "Ecriture future. Ne peut pas encore être rapprochée.";
		  print "</td>";
      }
              
      if ($objp->rappro)
	{
	  print "<td align=\"center\"><a href=\"releve.php?num=$objp->num_releve&amp;account=$acct->id\">$objp->num_releve</a></td>";
	}
      else
	{
	  if ($user->rights->banque->modifier)
	    {
	      print "<td align=\"center\">";
	      if ($objp->do <= mktime() ) {
	      	print "<a href=\"rappro.php?action=del&amp;rowid=$objp->rowid&amp;account=$acct->id\">";
	      	print img_delete();
	      	print "</a>";
	      }
	      else {
	      	print "&nbsp;";	// On n'empeche la suppression car le raprochement ne pourra se faire qu'après la date passée et que l'écriture apparaissent bien sur le compte.
	      }
	      print "</td>";
	    }
	  else
	    {
	      print "<td align=\"center\">&nbsp;</td>";
	    }
	}

      print "</tr>";
      print "<tr $bc[$var]><td>&nbsp;</td><td>".$objp->fk_type.($objp->num_chq?" ".$objp->num_chq:"")."</td><td colspan=\"6\">";
      if ($options) {
      	print "<select name=\"cat1\">$options";
      	print "</select>";
      }
      else {
      	print "&nbsp;";
      }
      print "</td></tr>";
      print "</form>";
      $i++;
    }
  $db->free();

	if ($num != 0) {
		print "</table>";
	}

} else {
    print "Erreur : ".$db->error()." : ".$sql."<br>\n";
}

print '<br>Dernier relevé : <a href="releve.php?account='.$_GET["account"].'&amp;num='.$last_releve.'">'.$last_releve.'</a>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
