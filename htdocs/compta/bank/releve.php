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
 
/* $num
 * $rel
 * $ve
 */
require("./pre.inc.php");

if (!$user->rights->banque->lire)
  accessforbidden();

if ($_GET["action"] == 'dvnext')
{
  $ac = new Account($db);
  $ac->datev_next($_GET["dvid"]);
}


if ($_GET["action"] == 'dvprev')
{
  $ac = new Account($db);
  $ac->datev_previous($_GET["dvid"]);
}


llxHeader();


// Récupère info du compte
$acct = new Account($db);
$acct->fetch($_GET["account"]);

if (! isset($_GET["num"]))
{
  /*
   *
   * Vue liste
   *
   *
   */
  if ($page == -1) { $page = 0 ; }

  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;

  $sql = "SELECT distinct(b.num_releve) as numr";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank as b WHERE fk_account = ".$_GET["account"]." ORDER BY numr DESC";
  $sql .= $db->plimit($limit,$offset);

  $result = $db->query($sql);

  if ($result)
    {
      $var=True;  
      $numrows = $db->num_rows();
      $i = 0; 
      
      print_barre_liste("Relevés bancaires, compte : <a href=\"account.php?account=".$acct->id."\">".$acct->label."</a>", $page, $PHP_SELF,"&amp;account=$account",$sortfield,$sortorder,'',$numrows);

      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
      print "<tr class=\"liste_titre\">";
      print "<td>Relevé</td></tr>";

      while ($i < min($numrows,$limit))
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  if (! $objp->numr) { 
//	  	print "<tr $bc[$var]><td><a href=\"$PHP_SELF?num=&amp;account=".$_GET["account"]."\">Ecritures rapprochées à aucun relevé</a></td></tr>\n";
	  } else {
	  	print "<tr $bc[$var]><td><a href=\"$PHP_SELF?num=$objp->numr&amp;account=".$_GET["account"]."\">$objp->numr</a></td></tr>\n";
	  }
	  $i++;
	}
  	print "</table>\n";
    }


}
else
{
  /*
   * Vue d'un releves
   *
   */
  if ($rel == 'prev')
    {
      $sql = "SELECT distinct(num_releve) FROM ".MAIN_DB_PREFIX."bank WHERE num_releve < ".$_GET["num"]." AND fk_account = $account ORDER BY num_releve DESC";
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
      $sql = "SELECT distinct(num_releve) FROM ".MAIN_DB_PREFIX."bank WHERE num_releve > ".$_GET["num"]." AND fk_account = $account ORDER BY num_releve ASC";
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

  print_titre('Relevé num&eacute;ro '.$_GET["num"].', compte : <a href="account.php?account='.$acct->id.'">'.$acct->label.'</a>');
  
  print "<table border=0 width=100%><tr><td>&nbsp;</td>";
  print "<td align=right><a href=\"$PHP_SELF?rel=prev&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">&lt;- Relevé précédent</a>";
  print "&nbsp; - &nbsp;<a href=\"$PHP_SELF?rel=next&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">Relevé suivant -&gt;</a></td></tr>";
  print "</table>";
  print "<form method=\"post\" action=\"$PHP_SELF\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
  print "<TR class=\"liste_titre\">";
  print '<td>Date Ope</td><td>Valeur</td><td>Type</td><td width="30%">Description</TD>';
  print '<td align="right">Debit</TD>';
  print '<td align="right">Credit</TD>';
  print '<td align="right">Solde</TD>';
  print '<td>&nbsp;</td>';
  print "</TR>\n";
 

  $sql = "SELECT sum(amount) FROM ".MAIN_DB_PREFIX."bank WHERE num_releve < ".$_GET["num"]." AND fk_account = ".$acct->id;
  if ( $db->query($sql) )
    {
      $total = $db->result (0, 0);
      $db->free();
    }


  $sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do,".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank as b WHERE";
  $sql .= " num_releve='".$_GET["num"]."'";
  if (! $_GET["num"]) {
  	$sql .= " or num_releve is null";
  }
  $sql .= " AND fk_account = ".$acct->id;
  $sql .= " ORDER BY datev ASC";
  $result = $db->query($sql);
  if ($result)
    {
      $var=True;  
      $numrows = $db->num_rows();
      $i = 0; 
      print "<tr><td colspan=\"3\"><a href=\"$PHP_SELF?num=".$_GET["num"]."&amp;ve=1&amp;rel=$rel&amp;account=".$acct->id."\">Vue etendue</a></td>";
      print "<td align=\"right\" colspan=\"2\">&nbsp;</td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";

      while ($i < $numrows)
	{
	  $objp = $db->fetch_object( $i);
	  $total = $total + $objp->amount;
	  
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  
	  print '<td>'.strftime("%d %b %Y",$objp->do).'</td><td valign="center">';

	  /* Mise à jour de la date de valeur */

	  print '<a href="releve.php?action=dvprev&amp;num='.$_GET["num"].'&amp;account='.$_GET["account"].'&amp;dvid='.$objp->rowid.'">';
	  print img_previous() . "</a> ";
	  print strftime("%d/%m/%Y",$objp->dv) ." ";
	  print '<a href="releve.php?action=dvnext&amp;num='.$_GET["num"].'&amp;account='.$_GET["account"].'&amp;dvid='.$objp->rowid.'">';
	  print img_next();

	  print "</td>\n";
	  print '<td>'.$objp->fk_type.' '.($objp->num_chq?$objp->num_chq:'').'</td>';
	  print "<td>$objp->label";
	  
	  if ($ve)
	    {
	      $dc = $db->clone();
	      $sql = "SELECT label FROM ".MAIN_DB_PREFIX."bank_categ as ct, ".MAIN_DB_PREFIX."bank_class as cl WHERE ct.rowid=cl.fk_categ AND cl.lineid=$objp->rowid";
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
	      print '<td align="right">'.price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
	    }
	  else
	    {
	      $totalc = $totalc + abs($objp->amount);
	      print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</td>\n";
	    }
    
	  print "<td align=\"right\">".price($total)."</td>\n";
	  
	  if ($user->rights->banque->modifier)
	    {
	      print "<td align=\"center\"><a href=\"ligne.php?rowid=$objp->rowid&amp;account=".$acct->id."\">";
	      print img_edit();
	      print "</a></td>";
	    }
	  else
	    {
	      print "<td align=\"center\">&nbsp;</td>";
	    }
	  print "</tr>";
	  $i++;
	}
      $db->free();
    }
  print "<tr><td align=\"right\" colspan=\"3\">Total :</td><td align=\"right\">".price($totald)."</td><td align=\"right\">".price($totalc)."</td><td colspan=\"3\">&nbsp;</td></tr>";
  print "<tr><td align=\"right\" colspan=\"5\"><b>Solde :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
  print "</table></form>\n";
  
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
