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

/*
 *
 * $viewall
 *
 */
require("./pre.inc.php3");

require("./bank.lib.php3");
llxHeader();
$db = new Db();



if ($account) {

  if ($action == 'add') {
    $author = $GLOBALS["REMOTE_USER"];
    if ($credit > 0) {
      $amount = $credit ;
    } else {
    $amount = - $debit ;
    }
    
    if ($num_chq) {
      $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author, num_chq,fk_account)";
      $sql .= " VALUES (now(), $dateo, '$label', $amount,'$author',$num_chq,$account)";
    } else {
      $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author,fk_account)";
      $sql .= " VALUES (now(), $dateo, '$label', $amount,'$author',$account)";
    }

    $result = $db->query($sql);
    if ($result) {
      $rowid = $db->last_insert_id();
      if ($cat1) {
	$sql = "INSERT INTO llx_bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
	$result = $db->query($sql);
      }
    } else {
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

  print "<b>Bank</b> - <a href=\"$PHP_SELF\">Reload</a>&nbsp;-";
  print "<a href=\"$PHP_SELF?viewall=1&account=$account\">Voir tout</a>";
  
  print "<form method=\"post\" action=\"$PHP_SELF?viewall=$viewall&vline=$vline&account=$account\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR class=\"liste_titre\">";
  print "<td>Date</td><td>Description</TD>";
  print "<td align=\"right\"><a href=\"$PHP_SELF?vue=debit\">Debit</a></TD>";
  print "<td align=\"right\"><a href=\"$PHP_SELF?vue=credit\">Credit</a></TD>";
  print "<td align=\"right\">Solde</TD>";
  print "<td align=\"right\">Francs</td>";
  print "</TR>\n";
  
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
 
  /* Another solution
   * create temporary table solde type=heap select amount from llx_bank limit 100 ;
   * select sum(amount) from solde ;
   */

  $sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq";
  $sql .= " FROM llx_bank as b "; if ($account) { $sql .= " WHERE fk_account=$account"; }
  if ($vue) {
    if ($vue == 'credit') {
      $sql .= " AND b.amount >= 0 ";
    } else {
      $sql .= " AND b.amount < 0 ";
  }
  }
  $sql .= " ORDER BY b.dateo ASC";
  
  $result = $db->query($sql);
  if ($result) {
    $var=True;  
    $num = $db->num_rows();
    $i = 0; $total = 0;
  
    $sep = 0;
  
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $total = $total + $objp->amount;
      $time = time();
      if ($i > ($nbline - $viewline)) {

	if (!$psol) {
	  print "<tr $bc[$var]><td colspan=\"4\">&nbsp;</td><td align=\"right\">".price($total)."</b></td><td align=\"right\">".francs($total)."</td></tr>\n";
	  $psol = 1;

	} else {
	  $var=!$var;

	  if ($objp->do > $time && !$sep) {
	    $sep = 1 ;
	    print "<tr><td align=\"right\" colspan=\"4\">Total :</td>";
	    print "<td align=\"right\"><b>".price($total - $objp->amount)."</b></td><td align=\"right\">".francs($total - $objp->amount)."</td></tr>\n";
	    print "<tr>";
	    print "<td><input name=\"dateo\" type=\"text\" size=8 maxlength=8></td>";
	    print "<td>CHQ<input name=\"num_chq\" type=\"text\" size=4>&nbsp;-";
	    print "<input name=\"label\" type=\"text\" size=40></td>";
	    print "<td><input name=\"debit\" type=\"text\" size=8></td>";
	    print "<td><input name=\"credit\" type=\"text\" size=8></td>";
	    print "<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"ajouter\"</td>";
	    print "</tr><tr><td colspan=\"2\">Format : YYYYMMDD - 20010826</td><td>0000.00</td>";
	    print "<td colspan=\"4\"><select name=\"cat1\">$options";
  
	    print "</select></td></tr>";

	  }

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
	
	  if ($total > 0) {
	    print "<td align=\"right\">".price($total)."</TD>\n";
	  } else {
	    print "<td align=\"right\"><b>".price($total)."</b></TD>\n";
	  }

	  if ($objp->rappro) {
	    print "<td align=\"center\"><a href=\"releve.php3?num=$objp->num_releve\">$objp->num_releve</a></td>";
	  } else {
	    print "<td align=\"center\"><a href=\"$PHP_SELF?action=del&rowid=$objp->rowid&account=$account\">[Del]</a></td>";
	  }

	  print "<td align=\"right\"><small>".francs($objp->amount)."</small></TD>\n";

	  print "</tr>";

	}
      }


      $i++;
    }
    $db->free();
  }

  if ($sep) {
    print "<tr><td align=\"right\" colspan=\"4\">Total :</td>";
    print "<td align=\"right\"><b>".price($total)."</b></td><td align=\"right\">".francs($total)."</td></tr>\n";
  } else {

    print "<tr><td align=\"right\" colspan=\"4\">Total :</td>";
    print "<td align=\"right\"><b>".price($total)."</b></td><td align=\"right\">".francs($total)."</td></tr>\n";
    print "<tr>";
    print "<td><input name=\"dateo\" type=\"text\" size=8 maxlength=8></td>";
    print "<td>CHQ<input name=\"num_chq\" type=\"text\" size=4>&nbsp;-";
    print "<input name=\"label\" type=\"text\" size=40></td>";
    print "<td><input name=\"debit\" type=\"text\" size=8></td>";
    print "<td><input name=\"credit\" type=\"text\" size=8></td>";
    print "<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"ajouter\"</td>";
    print "</tr><tr><td colspan=\"2\">Format : YYYYMMDD - 20010826</td><td>0000.00</td>";

    print "<td colspan=\"4\"><select name=\"cat1\">$options";

    print "</select></td></tr>";



  }

  print "</table></form>";

  print "<a href=\"categ.php3\">Edit Categories</a>";
  print " <a href=\"budget.php3\">Budgets</a>";


} else {

  print_titre ("Comptes bancaires");

  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR class=\"liste_titre\">";
  print "<td>Label</td><td>Banque</TD>";
  print "<td align=\"left\">Numéro</a></TD>";
  print "</TR>\n";

  $sql = "SELECT rowid, label,number,bank FROM llx_bank_account";

  $result = $db->query($sql);
  if ($result) {
    $var=True;  
    $num = $db->num_rows();
    $i = 0; $total = 0;

    $sep = 0;

    while ($i < $num) {
      $objp = $db->fetch_object( $i);


      print "<tr><td>$objp->label</td><td>$objp->bank</td><td>$objp->number</td></tr>";


      $i++;
    }
    $db->free();
  }


  $acc = new Account($db);

  print "</table>";
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
