<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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
  $acct=new Account($db,$account);
  $insertid=$acct->addline($dateop, $operation, $label, $amount, $num_chq,$cat1);
  //  $insertid=bank_add_line($db,$dateop, $label, $amount,$author,$num_chq,$account,$operation,$cat1);
  if ($insertid == ''){
    print "<p> Probleme d'insertion : ".$db->error();
  }else{
    Header("Location: $PHP_SELF?account=$account");
  }
    /*
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
    */
}
if ($action == 'del' && $account)
{
  $acct=new Account($db,$account);
  $acct->deleteline($rowid);
  //bank_delete_line($db, $rowid);
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
  $acct=new Account($db);
  $acct->fetch($account);

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


  print_titre("Compte : " .$acct->label);
  /*
   *
   */
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
  /*
   *
   *
   */
  if ($HTTP_POST_VARS["req_desc"]) 
    { 
      $sql_rech = " AND lower(b.label) like '%".strtolower($HTTP_POST_VARS["req_desc"])."%'";
      $mode_search = 1;
    }
  /*
   *
   *
   */
  $sql = "SELECT count(*) FROM llx_bank as b WHERE 1=1";
  if ($account) { $sql .= " AND b.fk_account=$account"; }
  $sql .= $sql_rech;
  if ( $db->query($sql) )
    {
      $nbline = $db->result (0, 0);
      $total_lines = $nbline;
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

  if ($page > 0 && $mode_search == 0)
    {
      $limitsql = $nbline - ($page * $viewline);
      if ($limitsql < $viewline)
	{
	  $limitsql = $viewline;
	}
      $nbline = $limitsql;
    }
  else
    {
      $page = 0;
      $limitsql = $nbline;
    }

  /*
   * Formulaire de recherche
   *
   */  
  print '<form method="post" action="'."$PHP_SELF?account=$account".'">';
  print '<input type="hidden" name="action" value="search">';
  print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
  print "<TR>";
  print '<td>';
  if ($limitsql > $viewline)
    {
      print '<a href="account.php?account='.$account.'&amp;page='.($page+1).'"><img alt="Page précédente" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1leftarrow.png" border="0"></a>';
    }
  if ($total_lines > $limitsql )
    {
      print '<a href="account.php?account='.$account.'&amp;page='.($page-1).'"><img alt="Page suivante" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/1rightarrow.png" border="0"></a>';
    }
  print '</td>';
  print '<td>&nbsp;</td><td><input type="text" name="req_desc" value="'.$HTTP_POST_VARS["req_desc"].'" size="24"></TD>';
  print '<td align="right"><input type="text" name="req_debit" size="6"></TD>';
  print '<td align="right"><input type="text" name="req_credit" size="6"></TD>';
  print '<td align="center"><input type="submit" value="Chercher"></td>';
  print '<td align="center"><a href="rappro.php?account='.$account.'">Rappro</a></td>';
  print "</tr>\n";
  print "</form>\n";
  /*
   *
   *
   */
  print "<form method=\"post\" action=\"$PHP_SELF?vline=$vline&amp;account=$account\">";
  print '<input type="hidden" name="action" value="add">';
  print "<tr class=\"liste_titre\">";
  print "<td>Date</td><td>Type</td><td>Description</TD>";
  print "<td align=\"right\">Débit</TD>";
  print "<td align=\"right\">Crédit</TD>";
  print "<td align=\"right\">Solde</td>";
  print "<td align=\"right\">Relevé</td></tr>";
  // DEBUG
  // print "<tr><td>$nbline</td><td>$viewline</td><td>total_lines $total_lines</td><td>limitsql $limitsql</td></tr>";
      
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

  $sql .= $sql_rech;

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
  $sql .= $db->plimit($limitsql, 0);

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
	      $var=!$var;

	      if ($objp->do > $time && !$sep)
		{
		  $sep = 1 ;
		  print "<tr><td align=\"right\" colspan=\"5\">&nbsp;</td>";
		  print "<td align=\"right\"><b>".price($total - $objp->amount)."</b></td>";
		  print "<td>&nbsp;</td>";
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
		  print "<td><a href=\"ligne.php?rowid=$objp->rowid&amp;account=$account\">CHQ $objp->num_chq - $objp->label</a></td>";
		}
	      else
		{
		  //Xavier DUTOIT : Ajout d'un lien pour modifier la ligne
		  print "<td><a href=\"ligne.php?rowid=$objp->rowid&amp;account=$account\">$objp->label</a>&nbsp;";
		  /*
		   * Ajout les liens
		   */
		  $urls_line = $acct->get_url($objp->rowid);
		  $numurl = sizeof($urls_line);
		  $k = 0;
		  while ($k < $numurl)
		    {
		      print ' <a href="'.$urls_line[$k][0].$urls_line[$k][1].'">'.$urls_line[$k][2].'</a>';
		      $k++;
		    }
		  print '</td>';
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
		  print "<td align=\"center\"><a href=\"releve.php?num=$objp->num_releve&amp;account=$account\">$objp->num_releve</a></td>";
		}
	      else
		{
		  print "<td align=\"center\"><a href=\"$PHP_SELF?action=del&amp;rowid=$objp->rowid&amp;account=$account&amp;page=$page\">[Del]</a></td>";
		}
	      
	      print "</tr>";
	      
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
      print "<td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
    }
  else
    {

      print "<tr><td align=\"right\" colspan=\"5\">&nbsp;</td>";
      print "<td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
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
      print "<td colspan=\"2\" align=\"center\"><select name=\"cat1\">$options</select></td>";
      print '</tr><tr><td colspan="3"><small>YYYYMMDD</small></td><td>0000.00</td>';
      
      print '<td colspan="3" align="center"><input type="submit" value="Ajouter"></td></tr>';
      
  }
  print "</table></form>";

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

  //$acc = new Account($db);

  print "</table>";
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
