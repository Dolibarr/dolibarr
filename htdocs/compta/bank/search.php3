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

  if ($vline) {
    $viewline = $vline;
  } else {
    $viewline = 50;
  }

  print "<b>Bank</b> - <a href=\"$PHP_SELF\">Reload</a>&nbsp;-";
  print "<a href=\"$PHP_SELF?viewall=1&account=$account\">Voir tout</a>";
  
  print "<form method=\"post\" action=\"$PHP_SELF?viewall=$viewall&vline=$vline&account=$account\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR class=\"liste_titre\">";
  print "<td>Date</td><td>Description</TD>";
  print "<td align=\"right\">Debit</TD>";
  print "<td align=\"right\">Credit</TD>";
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

  $sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_account";
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
      
      if ($total > 0) {
	print "<td align=\"right\">".price($total)."</TD>\n";
      } else {
	print "<td align=\"right\"><b>".price($total)."</b></TD>\n";
      }
      
      if ($objp->rappro) {
	print "<td align=\"center\"><a href=\"releve.php3?num=$objp->num_releve&account=$objp->fk_account\">$objp->num_releve</a></td>";
      } else {
	print "<td align=\"center\"><a href=\"$PHP_SELF?action=del&rowid=$objp->rowid&account=$objp->fk_account\">[Del]</a></td>";
      }
      
      print "<td align=\"right\"><small>".$objp->fk_account."</small></TD>\n";
      
      print "</tr>";


      }


      $i++;
    }
    $db->free();
  }


  print "</table>";



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
