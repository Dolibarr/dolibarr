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
 */
 
/* $num
 * $rel
 * $ve
 */
require("./pre.inc.php3");

llxHeader();


if (! strlen($num))
{
  /*
   *
   * Vue liste
   *
   *
   */
  print "<b>Releves bancaires</b>";

  print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="2">';
  print "<TR class=\"liste_titre\">";
  print "<td>Date</td>";

  print "</TR>\n";
 
  $sql = "SELECT distinct(b.num_releve) as numr";
  $sql .= " FROM llx_bank as b WHERE fk_account = $account ORDER BY numr DESC";
  $result = $db->query($sql);

  if ($result)
    {
      $var=True;  
      $numrows = $db->num_rows();
      $i = 0; 
      
      while ($i < $numrows)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<tr $bc[$var]><td><a href=\"$PHP_SELF?num=$objp->numr&account=$account\">$objp->numr</a></td></tr>\n";
	  $i++;
	}
    }
  print "</table>";

}
else
{
  /*
   * Vue d'un releves
   *
   */
  if ($rel == 'prev')
    {
      $sql = "SELECT distinct(num_releve) FROM llx_bank WHERE num_releve < $num AND fk_account = $account ORDER BY num_releve DESC";
      $result = $db->query($sql);
      if ($result)
	{
	  $var=True;  
	  $numrows = $db->num_rows();
	  $i = 0; 
	  if ($numrows > 0)
	    {
	      $row = $db->fetch_row(0);
	      $num = $row[0];
	    }
	}
    }
  elseif ($rel == 'next')
    {
      $sql = "SELECT distinct(num_releve) FROM llx_bank WHERE num_releve > $num AND fk_account = $account ORDER BY num_releve ASC";
      $result = $db->query($sql);
      if ($result)
	{
	  $var=True;  
	  $numrows = $db->num_rows();
	  $i = 0; 
	  if ($numrows > 0)
	    {
	      $row = $db->fetch_row(0);
	      $num = $row[0];
	    }
	}
    }
  

  print "<table border=0 width=100%><tr><td><b>Releve num&eacute;ro $num</b></td>";
  print "<td align=right><a href=\"$PHP_SELF?rel=prev&num=$num&ve=$ve\">&lt;- prev</a>";
  print "&nbsp;-&nbsp;<a href=\"$PHP_SELF?rel=next&num=$num&ve=$ve\">next -&gt;</a></td></tr></table>";
  print "<form method=\"post\" action=\"$PHP_SELF\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR class=\"liste_titre\">";
  print '<td>Date</td><td>Type</td><td width="30%">Description</TD>';
  print '<td align="right">Debit</TD>';
  print '<td align="right">Credit</TD>';
  print '<td align="right">Solde</TD>';
  print '<td>&nbsp;</td>';
  print "</TR>\n";
 

  $sql = "SELECT sum(amount) FROM llx_bank WHERE num_releve < $num AND fk_account = $account";
  if ( $db->query($sql) )
    {
      $total = $db->result (0, 0);
      $db->free();
    }


  $sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
  $sql .= " FROM llx_bank as b WHERE num_releve=$num AND fk_account = $account";
  $sql .= " ORDER BY dateo ASC";
  $result = $db->query($sql);
  if ($result)
    {
      $var=True;  
      $numrows = $db->num_rows();
      $i = 0; 
      print "<tr><td colspan=\"3\"><a href=\"$PHP_SELF?num=$num&ve=1&rel=$rel&account=$account\">vue etendue</a></td>";
      print "<td align=\"right\" colspan=\"2\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";

      while ($i < $numrows)
	{
	  $objp = $db->fetch_object( $i);
	  $total = $total + $objp->amount;
	  
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  
	  print "<td>".strftime("%d %b %Y",$objp->do)."</TD>\n";
	  print '<td>'.$objp->fk_type.'</td>';
	  print "<td>$objp->num_chq $objp->label";
	  
	  if ($ve)
	    {
	      $dc = $db->clone();
	      $sql = "SELECT label FROM llx_bank_categ as ct, llx_bank_class as cl WHERE ct.rowid=cl.fk_categ AND cl.lineid=$objp->rowid";
	      $resc = $dc->query($sql);
	      if ($resc)
		{
		  $numc = $dc->num_rows();
		  $ii = 0; 
		  while ($ii < $numc)
		    {
		      $objc = $dc->fetch_object($ii);
		      print "<br>-&nbsp;<i>$objc->label</i>";
		      $ii++;
		    }
		}
	      else
		{
		  print $dc->error();
		}
	    }
	  
	  print "</td>";
	  
	  if ($objp->amount < 0)
	    {
	      $totald = $totald + abs($objp->amount);
	      print "<td align=\"right\">".price($objp->amount * -1)."</TD><td>&nbsp;</td>\n";
	    }
	  else
	    {
	      $totalc = $totalc + abs($objp->amount);
	      print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</TD>\n";
	    }
    
	  print "<td align=\"right\">".price($total)."</TD>\n";
	  
	  print "<td align=\"center\">[<a href=\"ligne.php3?rowid=$objp->rowid&account=$account\">edit</a>]</td>";
	  
	  print "</tr>";
	  $i++;
	}
      $db->free();
    }
  print "<tr><td align=\"right\" colspan=\"3\">Total :</td><td align=\"right\">".price($totald)."</td><td align=\"right\">".price($totalc)."</td><td colspan=\"3\">&nbsp;</td></tr>";
  print "<tr><td align=\"right\" colspan=\"5\"><b>Solde :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
  print "</table></form>";
  
  print "<a href=\"bank/categ.php3\">Edit Categories</a>";
  print " <a href=\"bank/categories.php3\">Categories</a>";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
