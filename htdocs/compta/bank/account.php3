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

$db = new Db();

if ($HTTP_POST_VARS["action"] == 'add' && $account)
{
    
  if ($credit > 0)
    {
      $amount = $credit ;
    }
  else
    {
      $amount = - $debit ;
    }
  
  $dateop = "$dateoy" . "$dateo";
  
  if ($num_chq)
    {
      $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author, num_chq,fk_account, fk_type)";
      $sql .= " VALUES (now(), $dateop, '$label', $amount,'$author',$num_chq,$account,'$operation')";
    }
  else
    {
      $sql = "INSERT INTO llx_bank (datec, dateo, label, amount, author,fk_account,fk_type)";
      $sql .= " VALUES (now(), $dateop, '$label', $amount,'$author',$account,'$operation')";
    }
  
  $result = $db->query($sql);
  if ($result)
    {
      $rowid = $db->last_insert_id();
      if ($cat1)
	{
	  $sql = "INSERT INTO llx_bank_class (lineid, fk_categ) VALUES ($rowid, $cat1)";
	  $result = $db->query($sql);
	}
      Header("Location: $PHP_SELF?account=$account");
    }
  else
    {
      print $db->error();
      print "<p>$sql";
    }
  
}
if ($action == 'del' && $account)
{
  bank_delete_line($db, $rowid);
}


llxHeader();

if ($account)
{

  if ($vline)
    {
      $viewline = $vline;
    }
  else
    {
      $viewline = 20;
    }

  print "<b>Bank</b> - &nbsp;-";
  print "<a href=\"$PHP_SELF?viewall=1&account=$account\">Voir tout</a>";

  /*
   * Formulaire de recherche
   *
   */  
  print '<form method="post" action="'."$PHP_SELF?viewall=$viewall&vline=$vline&account=$account".'">';
  print '<input type="hidden" name="action" value="search">';
  print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="2">';
  print "<TR>";
  print '<td>&nbsp;</td>';
  print '<td>&nbsp;</td><td><input type="text" name="req_desc" class="flat" size="24"></TD>';
  print '<td align="right"><input type="text" name="req_debit" class="flat" size="6"></TD>';
  print '<td align="right"><input type="text" name="req_credit" class="flat" size="6"></TD>';
  print "<td align=\"right\">-</TD>";
  print "<td align=\"right\">-</td>";
  print '<td align="right"><input type="submit" value="Chercher" class="flat"></td>';
  print "</TR>\n";
  print "</form>";
  /*
   *
   *
   */
  print "<form method=\"post\" action=\"$PHP_SELF?viewall=$viewall&vline=$vline&account=$account\">";
  print '<input type="hidden" name="action" value="add">';
  print "<TR class=\"liste_titre\">";
  print "<td>Date</td><td>Type</td><td>Description</TD>";
  print "<td align=\"right\">Débit</TD>";
  print "<td align=\"right\">Crédit</TD>";
  print "<td align=\"right\">Solde</TD>";
  print "<td align=\"right\">Rel</td>";
  print "<td align=\"right\">Francs</td>";
  print "</TR>\n";
  
  $sql = "SELECT count(*) FROM llx_bank";
  if ($account) { $sql .= " WHERE fk_account=$account"; }
  if ( $db->query($sql) )
    {
      $nbline = $db->result (0, 0);
      $db->free();
    
      if ($nbline > $viewline )
	{
	  $limit = $nbline - $viewline ;
	}
      else
	{
	  $limit = $viewline;
	}
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
  
  
  if ($viewall) { $nbline=0; }
 
  /* Another solution
   * create temporary table solde type=heap select amount from llx_bank limit 100 ;
   * select sum(amount) from solde ;
   */

  $sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
  $sql .= " FROM llx_bank as b ";

  if ($account) 
    { 
      $sql .= " WHERE fk_account=$account";
    }

  if ($req_debit) 
    { 
      $sql .= " AND b.amount = -".$req_debit;
    }

  if ($vue)
    {
      if ($vue == 'credit')
	{
	  $sql .= " AND b.amount >= 0 ";
	}
      else
	{
	  $sql .= " AND b.amount < 0 ";
	}
    }
  $sql .= " ORDER BY b.dateo ASC";
  
  $result = $db->query($sql);
  if ($result)
    {
      $var=True;  
      $num = $db->num_rows();
      $i = 0; $total = 0;
      
      $sep = 0;
      
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $total = $total + $objp->amount;
	  $time = time();
	  if ($i > ($nbline - $viewline))
	    {

	      if (!$psol && $action !='search')
		{
		  print "<tr $bc[$var]><td colspan=\"4\">&nbsp;</td>";
		  print "<td align=\"right\">".price($total)."</b></td><td>&nbsp;</td>";
		  print "<td align=\"right\">".francs($total)."</td>\n";
		  print '<td colspan="2">&nbsp;</td></tr>';
		  $psol = 1;
		  
		}
	      else
		{
		  $var=!$var;

		  if ($objp->do > $time && !$sep)
		    {
		      $sep = 1 ;
		      print "<tr><td align=\"right\" colspan=\"5\">&nbsp;</td>";
		      print "<td align=\"right\"><b>".price($total - $objp->amount)."</b></td>";
		      print "<td>&nbsp;</td>";
		      print '<td align="right"><small>'.francs($total - $objp->amount).'</small></td>';
		      print '</tr><tr>';
		      print '<td><input name="dateoy" type="text" size="4" value="'.strftime("%Y",time()).'" maxlength="4">';
		      print '<input name="dateo" type="text" size="4" maxlength="4"></td>';
		      print '<td>';
		      print '<select name="operation">';
		      print '<option value="CB">CB';
		      print '<option value="CHQ">CHQ';
		      print '<option value="DEP">DEP';
		      print '<option value="TIP">TIP';
		      print '<option value="PRE">PRE';
		      print '<option value="VIR">VIR';
		      print '</select></td>';
		      print '<td><input name="num_chq" type="text" size="6">&nbsp;-';
		      print "<input name=\"label\" type=\"text\" size=40></td>";
		      print "<td><input name=\"debit\" type=\"text\" size=8></td>";
		      print "<td><input name=\"credit\" type=\"text\" size=8></td>";
		      print "<td colspan=\"3\" align=\"center\"><select name=\"cat1\">$options</select></td>";
		      print "</tr><tr><td colspan=\"3\"><small>YYYYMMDD</small></td><td>0000.00</td>";
		      print '<td colspan="4" align="center"><input type="submit" value="ajouter"></td></tr>';
		    }
		  
		  print "<tr $bc[$var]>";
		  print "<td>".strftime("%d %b %y",$objp->do)."</TD>\n";
		  print "<td>".$objp->fk_type."</TD>\n";
		  
		  if ($objp->num_chq)
		    {
		      print "<td>CHQ $objp->num_chq - $objp->label</td>";
		    }
		  else
		    {
		      print "<td>$objp->label&nbsp;</td>";
		    }

		  if ($objp->amount < 0)
		    {
		      print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
		    }
		  else
		    {
		      print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
		    }
		  
		  if ($action !='search')
		    {
		      if ($total > 0)
			{
			  print '<td align="right">'.price($total)."</TD>\n";
			}
		      else
			{
			  print "<td align=\"right\"><b>".price($total)."</b></TD>\n";
			}
		    }
		  else
		    {
		      print '<td align="right">-</TD>';
		    }

		  if ($objp->rappro)
		    {
		      print "<td align=\"center\"><a href=\"releve.php3?num=$objp->num_releve&account=$account\">$objp->num_releve</a></td>";
		    }
		  else
		    {
		      print "<td align=\"center\"><a href=\"$PHP_SELF?action=del&rowid=$objp->rowid&account=$account\">[Del]</a></td>";
		    }
		  
		  if ($action !='search')
		    {
		      print "<td align=\"right\"><small>".francs($objp->amount)."</small></TD>\n";
		    }
		  else
		    {
		      print '<td align="right">-</TD>';
		    }
		  
		  print "</tr>";
		  
		}
	    }
	  
	  
	  $i++;
	}
      $db->free();
    }
  /*
   * Opérations futures
   *
   */
  if ($sep)
    {
      print "<tr><td align=\"right\" colspan=\"5\">&nbsp;</td>";
      print "<td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td><td align=\"right\">".francs($total)."</td></tr>\n";
    }
  else
    {

      print "<tr><td align=\"right\" colspan=\"5\">&nbsp;</td>";
      print "<td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td><td align=\"right\">".francs($total)."</td></tr>\n";
      print "<tr>";
      print '<td><input name="dateoy" type="text" size="4" value="'.strftime("%Y",time()).'" maxlength="4">';
      print '<input name="dateo" type="text" size="4" maxlength="4"></td>';
      print "<td>";
      
      print '<select name="operation">';
      print '<option value="CB">CB';
      print '<option value="CHQ">CHQ';
      print '<option value="DEP">DEP';
      print '<option value="TIP">TIP';
      print '<option value="PRE">PRE';
      print '<option value="VIR">VIR';
      print '</select></td>';
      print "<td><input name=\"num_chq\" type=\"text\" size=4>";
      print '<input name="label" type="text" size=40></td>';
      print '<td><input name="debit" type="text" size="8"></td>';
      print '<td><input name="credit" type="text" size="8"></td>';
      print "<td colspan=\"3\" align=\"center\"><select name=\"cat1\">$options</select></td>";
      print '</tr><tr><td colspan="2"><small>YYYYMMDD</small></td><td>0000.00</td>';
      
      print '<td colspan="5" align="center"><input type="submit" value="Ajouter"></td></tr>';
      
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
